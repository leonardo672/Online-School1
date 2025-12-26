<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use App\Models\Course;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Hash;

class CertificateController extends Controller
{
    /**
     * Display a listing of certificates.
     */
    public function index(Request $request)
    {
        try {
            $query = Certificate::query();

            // Eager load relationships if requested
            if ($request->has('with')) {
                $with = explode(',', $request->with);
                $allowedRelations = ['user', 'course', 'course.instructor', 'course.category'];
                $query->with(array_intersect($with, $allowedRelations));
            }

            // Filter by user
            if ($request->has('user_id') && !empty($request->user_id)) {
                $query->where('user_id', $request->user_id);
            }

            // Filter by user email
            if ($request->has('user_email') && !empty($request->user_email)) {
                $user = User::where('email', $request->user_email)->first();
                if ($user) {
                    $query->where('user_id', $user->id);
                }
            }

            // Filter by course
            if ($request->has('course_id') && !empty($request->course_id)) {
                $query->where('course_id', $request->course_id);
            }

            // Filter by course slug
            if ($request->has('course_slug') && !empty($request->course_slug)) {
                $course = Course::where('slug', $request->course_slug)->first();
                if ($course) {
                    $query->where('course_id', $course->id);
                }
            }

            // Filter by status
            if ($request->has('status')) {
                if ($request->status === 'valid') {
                    $query->valid();
                } elseif ($request->status === 'expired') {
                    $query->expired();
                } elseif ($request->status === 'downloadable') {
                    $query->downloadable($request->get('max_downloads', 5));
                }
            }

            // Filter by certificate code
            if ($request->has('certificate_code') && !empty($request->certificate_code)) {
                $query->where('certificate_code', 'like', '%' . $request->certificate_code . '%');
            }

            // Filter by date range (issued date)
            if ($request->has('issued_from') && !empty($request->issued_from)) {
                $query->whereDate('issued_at', '>=', $request->issued_from);
            }
            if ($request->has('issued_to') && !empty($request->issued_to)) {
                $query->whereDate('issued_at', '<=', $request->issued_to);
            }

            // Filter by expiry date
            if ($request->has('expires_from') && !empty($request->expires_from)) {
                $query->whereDate('expires_at', '>=', $request->expires_from);
            }
            if ($request->has('expires_to') && !empty($request->expires_to)) {
                $query->whereDate('expires_at', '<=', $request->expires_to);
            }

            // Ordering
            $orderBy = $request->get('order_by', 'issued_at');
            $orderDirection = $request->get('order_direction', 'desc');
            
            if (in_array($orderBy, ['issued_at', 'expires_at', 'created_at', 'updated_at', 'download_count'])) {
                $query->orderBy($orderBy, $orderDirection);
            } elseif ($orderBy === 'user_name') {
                $query->join('users', 'certificates.user_id', '=', 'users.id')
                    ->orderBy('users.name', $orderDirection)
                    ->select('certificates.*');
            } elseif ($orderBy === 'course_title') {
                $query->join('courses', 'certificates.course_id', '=', 'courses.id')
                    ->orderBy('courses.title', $orderDirection)
                    ->select('certificates.*');
            }

            // Pagination
            $perPage = $request->get('per_page', 15);
            $certificates = $perPage === 'all' 
                ? $query->get()
                : $query->paginate($perPage);

            // Add computed attributes if requested
            if ($request->has('include_computed') && $request->include_computed) {
                $certificates->transform(function ($certificate) {
                    $certificate->is_valid = $certificate->isValid();
                    $certificate->is_expired = $certificate->isExpired();
                    $certificate->has_expiry = $certificate->hasExpiry();
                    $certificate->days_until_expiration = $certificate->days_until_expiration;
                    $certificate->formatted_issued_at = $certificate->formatted_issued_at;
                    $certificate->formatted_expires_at = $certificate->formatted_expires_at;
                    $certificate->expiry_status = $certificate->expiry_status;
                    $certificate->validity_years = $certificate->validity_years;
                    $certificate->can_be_downloaded = $certificate->canBeDownloaded();
                    return $certificate;
                });
            }

            return response()->json([
                'success' => true,
                'data' => $certificates,
                'message' => 'Certificates retrieved successfully.'
            ]);

        } catch (\Exception $e) {
            Log::error('Certificate index error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve certificates.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Store a newly created certificate.
     */
    public function store(Request $request)
    {
        DB::beginTransaction();

        try {
            $validator = Validator::make($request->all(), [
                'user_id' => 'required|exists:users,id',
                'course_id' => 'required|exists:courses,id',
                'certificate_code' => 'nullable|string|max:100|unique:certificates,certificate_code',
                'issued_at' => 'nullable|date',
                'expires_at' => 'nullable|date|after:issued_at',
                'verification_url' => 'nullable|url',
                'metadata' => 'nullable|array',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Check if user is enrolled in the course
            $course = Course::find($request->course_id);
            $isEnrolled = $course->hasUserEnrolled($request->user_id);
            
            if (!$isEnrolled && !$request->has('force')) {
                return response()->json([
                    'success' => false,
                    'message' => 'User is not enrolled in this course. Use force option to create certificate anyway.'
                ], 403);
            }

            // Check if course offers certificates
            if (!$course->certificate_available && !$request->has('force')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Course does not offer certificates. Use force option to create certificate anyway.'
                ], 422);
            }

            // Check if user has already received certificate for this course
            $existingCertificate = Certificate::where('user_id', $request->user_id)
                ->where('course_id', $request->course_id)
                ->first();

            if ($existingCertificate) {
                return response()->json([
                    'success' => false,
                    'message' => 'User already has a certificate for this course.'
                ], 409);
            }

            // Check if user has completed the course (optional requirement)
            if ($request->has('require_completion') && $request->require_completion) {
                $progress = $course->getUserCompletionPercentage($request->user_id);
                if ($progress < 100) {
                    return response()->json([
                        'success' => false,
                        'message' => 'User must complete the course before receiving certificate.',
                        'completion_percentage' => $progress
                    ], 403);
                }
            }

            $certificate = Certificate::create($request->all());

            DB::commit();

            // Load relationships if needed
            if ($request->has('load_relations')) {
                $certificate->load(['user', 'course', 'course.instructor']);
            }

            return response()->json([
                'success' => true,
                'data' => $certificate,
                'message' => 'Certificate created successfully.'
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Certificate store error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to create certificate.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Issue certificate to a user for a course.
     */
    public function issueCertificate(Request $request)
    {
        DB::beginTransaction();

        try {
            $validator = Validator::make($request->all(), [
                'user_id' => 'required|exists:users,id',
                'course_id' => 'required|exists:courses,id',
                'metadata' => 'nullable|array',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors' => $validator->errors()
                ], 422);
            }

            $certificate = Certificate::issue($request->user_id, $request->course_id, $request->metadata ?? []);

            if (!$certificate) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to issue certificate. User may not have completed the course, course may not offer certificates, or certificate already exists.'
                ], 400);
            }

            DB::commit();

            $certificate->load(['user', 'course']);

            return response()->json([
                'success' => true,
                'data' => $certificate,
                'message' => 'Certificate issued successfully.'
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Issue certificate error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to issue certificate.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Display the specified certificate.
     */
    public function show($id)
    {
        try {
            $query = Certificate::query();

            // Load relationships if requested
            if (request()->has('with')) {
                $with = explode(',', request()->with);
                $allowedRelations = ['user', 'course', 'course.instructor', 'course.category'];
                $query->with(array_intersect($with, $allowedRelations));
            }

            $certificate = $query->findOrFail($id);

            // Add computed attributes
            $certificate->is_valid = $certificate->isValid();
            $certificate->is_expired = $certificate->isExpired();
            $certificate->has_expiry = $certificate->hasExpiry();
            $certificate->days_until_expiration = $certificate->days_until_expiration;
            $certificate->formatted_issued_at = $certificate->formatted_issued_at;
            $certificate->formatted_expires_at = $certificate->formatted_expires_at;
            $certificate->expiry_status = $certificate->expiry_status;
            $certificate->validity_years = $certificate->validity_years;
            $certificate->can_be_downloaded = $certificate->canBeDownloaded();

            // Add PDF data if requested
            if (request()->has('include_pdf_data') && request()->include_pdf_data) {
                $certificate->pdf_data = $certificate->getPdfData();
            }

            return response()->json([
                'success' => true,
                'data' => $certificate,
                'message' => 'Certificate retrieved successfully.'
            ]);

        } catch (\Exception $e) {
            Log::error('Certificate show error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Certificate not found.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 404);
        }
    }

    /**
     * Update the specified certificate.
     */
    public function update(Request $request, $id)
    {
        DB::beginTransaction();

        try {
            $certificate = Certificate::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'certificate_code' => 'nullable|string|max:100|unique:certificates,certificate_code,' . $certificate->id,
                'issued_at' => 'nullable|date',
                'expires_at' => 'nullable|date|after:issued_at',
                'verification_url' => 'nullable|url',
                'metadata' => 'nullable|array',
                'download_count' => 'nullable|integer|min:0',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Update certificate
            $certificate->update($request->all());

            DB::commit();

            $certificate->refresh();

            return response()->json([
                'success' => true,
                'data' => $certificate,
                'message' => 'Certificate updated successfully.'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Certificate update error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to update certificate.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Remove the specified certificate.
     */
    public function destroy($id)
    {
        DB::beginTransaction();

        try {
            $certificate = Certificate::findOrFail($id);
            $certificate->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Certificate deleted successfully.'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Certificate destroy error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete certificate.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Download certificate as PDF.
     */
    public function download($id)
    {
        try {
            $certificate = Certificate::with(['user', 'course.instructor', 'course.category'])
                ->findOrFail($id);

            // Check if certificate can be downloaded
            if (!$certificate->canBeDownloaded() && !request()->has('force')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Certificate cannot be downloaded. It may be expired or download limit reached.'
                ], 403);
            }

            // Record the download
            $certificate->recordDownload();

            // Generate PDF data
            $pdfData = $certificate->getPdfData();

            // Generate PDF
            $pdf = Pdf::loadView('certificates.template', $pdfData);

            // Return PDF download
            return $pdf->download('certificate-' . $certificate->certificate_code . '.pdf');

        } catch (\Exception $e) {
            Log::error('Certificate download error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to download certificate.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Preview certificate as PDF (without recording download).
     */
    public function preview($id)
    {
        try {
            $certificate = Certificate::with(['user', 'course.instructor', 'course.category'])
                ->findOrFail($id);

            // Generate PDF data
            $pdfData = $certificate->getPdfData();

            // Generate PDF
            $pdf = Pdf::loadView('certificates.template', $pdfData);

            // Return PDF preview in browser
            return $pdf->stream('certificate-' . $certificate->certificate_code . '.pdf');

        } catch (\Exception $e) {
            Log::error('Certificate preview error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to preview certificate.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Verify certificate by code.
     */
    public function verify(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'certificate_code' => 'required|string',
                'hash' => 'nullable|string', // For public verification URLs
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors' => $validator->errors()
                ], 422);
            }

            // If hash is provided, verify it first
            if ($request->has('hash') && !empty($request->hash)) {
                if (!Certificate::verify($request->certificate_code, $request->hash)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid verification URL.'
                    ], 403);
                }
            }

            $certificate = Certificate::findByCode($request->certificate_code);

            if (!$certificate) {
                return response()->json([
                    'success' => false,
                    'message' => 'Certificate not found.',
                    'certificate_code' => $request->certificate_code
                ], 404);
            }

            // Load relationships
            $certificate->load(['user', 'course.instructor', 'course.category']);

            // Add verification information
            $verificationResult = [
                'found' => true,
                'valid' => $certificate->isValid(),
                'expired' => $certificate->isExpired(),
                'verification_date' => now()->toDateTimeString(),
                'certificate' => $certificate->only([
                    'id', 'certificate_code', 'issued_at', 'expires_at', 
                    'download_count', 'verification_url'
                ]),
                'user' => $certificate->user->only(['id', 'name', 'email']),
                'course' => $certificate->course->only(['id', 'title', 'slug']),
                'instructor' => $certificate->course->instructor->only(['id', 'name', 'email']),
                'category' => $certificate->course->category->only(['id', 'name', 'slug']),
            ];

            return response()->json([
                'success' => true,
                'data' => $verificationResult,
                'message' => $certificate->isValid() ? 'Certificate is valid.' : 'Certificate is not valid.'
            ]);

        } catch (\Exception $e) {
            Log::error('Certificate verify error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to verify certificate.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Public verification endpoint (for sharing).
     */
    public function publicVerify($certificateCode, $hash)
    {
        try {
            if (!Certificate::verify($certificateCode, $hash)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid verification URL.'
                ], 403);
            }

            $result = Certificate::validateForVerification($certificateCode);

            if (!$result['valid']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'],
                    'certificate_code' => $certificateCode
                ], 404);
            }

            $certificate = $result['certificate'];
            $certificate->load(['user', 'course.instructor', 'course.category']);

            // Return minimal information for public verification
            $publicInfo = [
                'certificate_code' => $certificate->certificate_code,
                'issued_to' => $certificate->user->name,
                'course_title' => $certificate->course->title,
                'instructor_name' => $certificate->course->instructor->name,
                'issued_date' => $certificate->formatted_issued_at,
                'expiry_date' => $certificate->formatted_expires_at,
                'status' => $certificate->isValid() ? 'Valid' : 'Invalid',
                'verification_date' => now()->toDateTimeString(),
            ];

            return response()->json([
                'success' => true,
                'data' => $publicInfo,
                'message' => 'Certificate verification successful.'
            ]);

        } catch (\Exception $e) {
            Log::error('Certificate public verify error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to verify certificate.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Get certificate statistics.
     */
    public function statistics(Request $request)
    {
        try {
            $query = Certificate::query();

            // Apply filters if any
            if ($request->has('course_id') && !empty($request->course_id)) {
                $query->where('course_id', $request->course_id);
            }

            if ($request->has('date_from') && !empty($request->date_from)) {
                $query->whereDate('issued_at', '>=', $request->date_from);
            }

            if ($request->has('date_to') && !empty($request->date_to)) {
                $query->whereDate('issued_at', '<=', $request->date_to);
            }

            $totalCertificates = $query->count();
            $validCertificates = $query->valid()->count();
            $expiredCertificates = $query->expired()->count();
            $todayCertificates = $query->clone()->whereDate('issued_at', today())->count();
            $weekCertificates = $query->clone()->where('issued_at', '>=', now()->subDays(7))->count();
            $monthCertificates = $query->clone()->where('issued_at', '>=', now()->subDays(30))->count();

            // Get certificate analytics
            $analytics = Certificate::getAnalytics($request->course_id ?? null);

            // Get certificates by date for the last 30 days
            $certificatesByDate = Certificate::select(
                    DB::raw('DATE(issued_at) as date'),
                    DB::raw('count(*) as count')
                )
                ->where('issued_at', '>=', now()->subDays(30))
                ->groupBy('date')
                ->orderBy('date')
                ->get();

            // Get top courses by certificates issued
            $topCourses = Course::withCount('certificates')
                ->has('certificates')
                ->orderBy('certificates_count', 'desc')
                ->limit(10)
                ->get();

            // Get top students by certificates earned
            $topStudents = User::withCount('certificates')
                ->has('certificates')
                ->orderBy('certificates_count', 'desc')
                ->limit(10)
                ->get();

            // Get recent certificates
            $recentCertificates = Certificate::with(['user', 'course'])
                ->latest()
                ->limit(10)
                ->get()
                ->map(function ($certificate) {
                    return [
                        'id' => $certificate->id,
                        'certificate_code' => $certificate->certificate_code,
                        'user' => $certificate->user->only(['id', 'name', 'email']),
                        'course' => $certificate->course->only(['id', 'title', 'slug']),
                        'issued_at' => $certificate->issued_at,
                        'formatted_issued_at' => $certificate->formatted_issued_at,
                        'is_valid' => $certificate->isValid(),
                    ];
                });

            // Get download statistics
            $totalDownloads = Certificate::sum('download_count');
            $averageDownloads = $totalCertificates > 0 ? round($totalDownloads / $totalCertificates, 2) : 0;

            return response()->json([
                'success' => true,
                'data' => [
                    'total_certificates' => $totalCertificates,
                    'valid_certificates' => $validCertificates,
                    'expired_certificates' => $expiredCertificates,
                    'today_certificates' => $todayCertificates,
                    'week_certificates' => $weekCertificates,
                    'month_certificates' => $monthCertificates,
                    'analytics' => $analytics,
                    'certificates_by_date' => $certificatesByDate,
                    'top_courses' => $topCourses,
                    'top_students' => $topStudents,
                    'recent_certificates' => $recentCertificates,
                    'download_statistics' => [
                        'total_downloads' => $totalDownloads,
                        'average_downloads_per_certificate' => $averageDownloads,
                    ],
                ],
                'message' => 'Certificate statistics retrieved successfully.'
            ]);

        } catch (\Exception $e) {
            Log::error('Certificate statistics error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve certificate statistics.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Get course certificate statistics.
     */
    public function courseStatistics($courseId)
    {
        try {
            $course = Course::findOrFail($courseId);

            $totalCertificates = $course->certificates()->count();
            $validCertificates = $course->certificates()->valid()->count();
            $expiredCertificates = $course->certificates()->expired()->count();
            $todayCertificates = $course->certificates()->whereDate('issued_at', today())->count();
            $weekCertificates = $course->certificates()->where('issued_at', '>=', now()->subDays(7))->count();
            $monthCertificates = $course->certificates()->where('issued_at', '>=', now()->subDays(30))->count();

            // Get certificate trend
            $certificateTrend = $course->certificates()
                ->select(
                    DB::raw('DATE(issued_at) as date'),
                    DB::raw('count(*) as count')
                )
                ->where('issued_at', '>=', now()->subDays(30))
                ->groupBy('date')
                ->orderBy('date')
                ->get();

            // Get recent certificates for this course
            $recentCertificates = $course->certificates()
                ->with('user')
                ->latest()
                ->limit(10)
                ->get();

            // Get analytics
            $analytics = Certificate::getAnalytics($courseId);

            return response()->json([
                'success' => true,
                'data' => [
                    'course' => $course->only(['id', 'title', 'slug', 'certificate_available', 'certificate_validity_years']),
                    'total_certificates' => $totalCertificates,
                    'valid_certificates' => $validCertificates,
                    'expired_certificates' => $expiredCertificates,
                    'today_certificates' => $todayCertificates,
                    'week_certificates' => $weekCertificates,
                    'month_certificates' => $monthCertificates,
                    'certificate_trend' => $certificateTrend,
                    'recent_certificates' => $recentCertificates,
                    'analytics' => $analytics,
                ],
                'message' => 'Course certificate statistics retrieved successfully.'
            ]);

        } catch (\Exception $e) {
            Log::error('Course certificate statistics error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve course certificate statistics.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Get user certificate statistics.
     */
    public function userStatistics($userId)
    {
        try {
            $user = User::findOrFail($userId);

            $totalCertificates = $user->certificates()->count();
            $validCertificates = $user->certificates()->valid()->count();
            $expiredCertificates = $user->certificates()->expired()->count();
            $recentCertificates = $user->certificates()->where('issued_at', '>=', now()->subDays(30))->count();

            // Get certificate history
            $certificateHistory = $user->certificates()
                ->with('course')
                ->latest()
                ->paginate(10);

            // Get certificates by course
            $certificatesByCourse = $user->certificates()
                ->select('course_id', DB::raw('count(*) as count'))
                ->groupBy('course_id')
                ->with('course')
                ->get();

            // Add computed attributes to certificate history
            $certificateHistory->getCollection()->transform(function ($certificate) {
                $certificate->is_valid = $certificate->isValid();
                $certificate->is_expired = $certificate->isExpired();
                $certificate->formatted_issued_at = $certificate->formatted_issued_at;
                $certificate->formatted_expires_at = $certificate->formatted_expires_at;
                $certificate->expiry_status = $certificate->expiry_status;
                return $certificate;
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'user' => $user->only(['id', 'name', 'email']),
                    'total_certificates' => $totalCertificates,
                    'valid_certificates' => $validCertificates,
                    'expired_certificates' => $expiredCertificates,
                    'recent_certificates' => $recentCertificates,
                    'certificate_history' => $certificateHistory,
                    'certificates_by_course' => $certificatesByCourse,
                ],
                'message' => 'User certificate statistics retrieved successfully.'
            ]);

        } catch (\Exception $e) {
            Log::error('User certificate statistics error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve user certificate statistics.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Get current user's certificates.
     */
    public function selfCertificates(Request $request)
    {
        try {
            $user = auth()->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated.'
                ], 401);
            }

            $query = $user->certificates()->with('course');

            // Apply filters
            if ($request->has('status')) {
                if ($request->status === 'valid') {
                    $query->valid();
                } elseif ($request->status === 'expired') {
                    $query->expired();
                }
            }

            if ($request->has('course_id') && !empty($request->course_id)) {
                $query->where('course_id', $request->course_id);
            }

            if ($request->has('search') && !empty($request->search)) {
                $query->whereHas('course', function ($q) use ($request) {
                    $q->where('title', 'like', '%' . $request->search . '%');
                });
            }

            // Ordering
            $orderBy = $request->get('order_by', 'issued_at');
            $orderDirection = $request->get('order_direction', 'desc');
            
            if (in_array($orderBy, ['issued_at', 'expires_at'])) {
                $query->orderBy($orderBy, $orderDirection);
            }

            // Pagination
            $perPage = $request->get('per_page', 15);
            $certificates = $perPage === 'all' 
                ? $query->get()
                : $query->paginate($perPage);

            // Add computed attributes
            $certificates->transform(function ($certificate) {
                $certificate->is_valid = $certificate->isValid();
                $certificate->is_expired = $certificate->isExpired();
                $certificate->formatted_issued_at = $certificate->formatted_issued_at;
                $certificate->formatted_expires_at = $certificate->formatted_expires_at;
                $certificate->expiry_status = $certificate->expiry_status;
                $certificate->can_be_downloaded = $certificate->canBeDownloaded();
                $certificate->course_title = $certificate->course->title ?? null;
                $certificate->course_slug = $certificate->course->slug ?? null;
                $certificate->course_instructor = $certificate->course->instructor->name ?? null;
                return $certificate;
            });

            // Get statistics
            $totalCertificates = $user->certificates()->count();
            $validCertificates = $user->certificates()->valid()->count();
            $expiredCertificates = $user->certificates()->expired()->count();

            return response()->json([
                'success' => true,
                'data' => [
                    'certificates' => $certificates,
                    'statistics' => [
                        'total_certificates' => $totalCertificates,
                        'valid_certificates' => $validCertificates,
                        'expired_certificates' => $expiredCertificates,
                    ],
                ],
                'message' => 'Your certificates retrieved successfully.'
            ]);

        } catch (\Exception $e) {
            Log::error('Self certificates error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve your certificates.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Bulk issue certificates for a course.
     */
    public function bulkIssue(Request $request)
    {
        DB::beginTransaction();

        try {
            $validator = Validator::make($request->all(), [
                'course_id' => 'required|exists:courses,id',
                'user_ids' => 'required|array',
                'user_ids.*' => 'exists:users,id',
                'skip_existing' => 'boolean',
                'require_completion' => 'boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors' => $validator->errors()
                ], 422);
            }

            $course = Course::find($request->course_id);
            $issuedCount = 0;
            $skippedCount = 0;
            $failedCount = 0;
            $errors = [];

            foreach ($request->user_ids as $userId) {
                try {
                    // Check if certificate already exists
                    $existingCertificate = Certificate::where('user_id', $userId)
                        ->where('course_id', $course->id)
                        ->first();

                    if ($existingCertificate) {
                        if ($request->skip_existing) {
                            $skippedCount++;
                            continue;
                        } else {
                            $failedCount++;
                            $errors[] = "User {$userId} already has a certificate for this course.";
                            continue;
                        }
                    }

                    // Check if user has completed the course
                    if ($request->require_completion) {
                        $user = User::find($userId);
                        if (!$user || !$user->hasCompletedCourse($course->id)) {
                            $failedCount++;
                            $errors[] = "User {$userId} has not completed the course.";
                            continue;
                        }
                    }

                    // Issue certificate
                    $certificate = Certificate::issue($userId, $course->id);
                    
                    if ($certificate) {
                        $issuedCount++;
                    } else {
                        $failedCount++;
                        $errors[] = "Failed to issue certificate to user {$userId}.";
                    }
                } catch (\Exception $e) {
                    $failedCount++;
                    $errors[] = "Error issuing certificate to user {$userId}: " . $e->getMessage();
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Bulk certificate issuance completed.',
                'data' => [
                    'course' => $course->only(['id', 'title', 'slug']),
                    'issued_count' => $issuedCount,
                    'skipped_count' => $skippedCount,
                    'failed_count' => $failedCount,
                    'errors' => $errors,
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Bulk issue certificates error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to perform bulk certificate issuance.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Bulk delete certificates.
     */
    public function bulkDestroy(Request $request)
    {
        DB::beginTransaction();

        try {
            $validator = Validator::make($request->all(), [
                'ids' => 'required|array',
                'ids.*' => 'exists:certificates,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors' => $validator->errors()
                ], 422);
            }

            $deletedCount = Certificate::whereIn('id', $request->ids)->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Bulk delete completed.',
                'data' => [
                    'deleted_count' => $deletedCount,
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Bulk delete certificates error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to perform bulk delete.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Renew an expired certificate.
     */
    public function renew($id)
    {
        DB::beginTransaction();

        try {
            $certificate = Certificate::findOrFail($id);

            if (!$certificate->isExpired()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Certificate is not expired. Cannot renew a valid certificate.'
                ], 400);
            }

            // Check if course still offers certificates
            if (!$certificate->course->certificate_available) {
                return response()->json([
                    'success' => false,
                    'message' => 'Course no longer offers certificates.'
                ], 400);
            }

            // Renew certificate (set new expiry date)
            $validityYears = $certificate->course->certificate_validity_years ?? Certificate::DEFAULT_VALIDITY_YEARS;
            $certificate->expires_at = now()->addYears($validityYears);
            $certificate->save();

            DB::commit();

            $certificate->refresh();

            return response()->json([
                'success' => true,
                'data' => $certificate,
                'message' => 'Certificate renewed successfully.'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Renew certificate error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to renew certificate.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Get certificate metadata.
     */
    public function getMetadata($id, $key)
    {
        try {
            $certificate = Certificate::findOrFail($id);
            $value = $certificate->getMetadata($key);

            return response()->json([
                'success' => true,
                'data' => [
                    'key' => $key,
                    'value' => $value,
                    'exists' => $value !== null,
                ],
                'message' => 'Certificate metadata retrieved.'
            ]);

        } catch (\Exception $e) {
            Log::error('Get certificate metadata error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve certificate metadata.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Set certificate metadata.
     */
    public function setMetadata(Request $request, $id)
    {
        try {
            $certificate = Certificate::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'key' => 'required|string',
                'value' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors' => $validator->errors()
                ], 422);
            }

            $success = $certificate->setMetadata($request->key, $request->value);

            if (!$success) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to set metadata.'
                ], 400);
            }

            $certificate->refresh();

            return response()->json([
                'success' => true,
                'data' => [
                    'key' => $request->key,
                    'value' => $certificate->getMetadata($request->key),
                    'certificate' => $certificate->only(['id', 'certificate_code']),
                ],
                'message' => 'Certificate metadata set successfully.'
            ]);

        } catch (\Exception $e) {
            Log::error('Set certificate metadata error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to set certificate metadata.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Generate certificate verification QR code data.
     */
    public function generateQrCode($id)
    {
        try {
            $certificate = Certificate::findOrFail($id);

            $qrData = [
                'certificate_code' => $certificate->certificate_code,
                'verification_url' => $certificate->verification_url,
                'student_name' => $certificate->user->name,
                'course_title' => $certificate->course->title,
                'issued_date' => $certificate->formatted_issued_at,
            ];

            // In a real application, you would generate an actual QR code image
            // For now, we return the data that would be encoded in the QR code
            $qrCodeText = json_encode($qrData);

            return response()->json([
                'success' => true,
                'data' => [
                    'qr_data' => $qrData,
                    'qr_code_text' => $qrCodeText,
                    'certificate' => $certificate->only(['id', 'certificate_code', 'verification_url']),
                ],
                'message' => 'QR code data generated successfully.'
            ]);

        } catch (\Exception $e) {
            Log::error('Generate QR code error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to generate QR code data.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
}