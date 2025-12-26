<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Category;
use App\Models\User;
use App\Models\Certificate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class CourseController extends Controller
{
    /**
     * Display a listing of courses.
     */
    public function index(Request $request)
    {
        try {
            $query = Course::query();

            // Eager load relationships if requested
            if ($request->has('with')) {
                $with = explode(',', $request->with);
                $allowedRelations = ['instructor', 'category', 'lessons', 'reviews', 'enrollments'];
                $query->with(array_intersect($with, $allowedRelations));
            }

            // Search functionality
            if ($request->has('search') && !empty($request->search)) {
                $searchTerm = $request->search;
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('title', 'like', '%' . $searchTerm . '%')
                      ->orWhere('description', 'like', '%' . $searchTerm . '%')
                      ->orWhere('slug', 'like', '%' . $searchTerm . '%')
                      ->orWhereHas('instructor', function ($q) use ($searchTerm) {
                          $q->where('name', 'like', '%' . $searchTerm . '%');
                      })
                      ->orWhereHas('category', function ($q) use ($searchTerm) {
                          $q->where('name', 'like', '%' . $searchTerm . '%');
                      });
                });
            }

            // Filter by category
            if ($request->has('category_id') && !empty($request->category_id)) {
                $query->where('category_id', $request->category_id);
            }

            // Filter by category slug
            if ($request->has('category_slug') && !empty($request->category_slug)) {
                $query->whereHas('category', function ($q) use ($request) {
                    $q->where('slug', $request->category_slug);
                });
            }

            // Filter by instructor
            if ($request->has('instructor_id') && !empty($request->instructor_id)) {
                $query->where('instructor_id', $request->instructor_id);
            }

            // Filter by level
            if ($request->has('level') && !empty($request->level)) {
                $query->where('level', $request->level);
            }

            // Filter by price
            if ($request->has('price_type')) {
                switch ($request->price_type) {
                    case 'free':
                        $query->free();
                        break;
                    case 'paid':
                        $query->paid();
                        break;
                    case 'discounted':
                        $query->discounted();
                        break;
                }
            }

            // Filter by certificate availability
            if ($request->has('certificate_available') && $request->certificate_available !== '') {
                $query->where('certificate_available', filter_var($request->certificate_available, FILTER_VALIDATE_BOOLEAN));
            }

            // Filter by published status
            if ($request->has('published') && $request->published !== '') {
                if ($request->published) {
                    $query->published();
                } else {
                    $query->draft();
                }
            }

            // Filter by language
            if ($request->has('language') && !empty($request->language)) {
                $query->where('language', $request->language);
            }

            // Filter by date range
            if ($request->has('date_from') && !empty($request->date_from)) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }
            if ($request->has('date_to') && !empty($request->date_to)) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }

            // Special scopes
            if ($request->has('featured') && $request->featured) {
                $query->featured();
            }
            if ($request->has('trending') && $request->trending) {
                $query->trending();
            }
            if ($request->has('new_arrivals') && $request->new_arrivals) {
                $query->newArrivals();
            }

            // Ordering
            $orderBy = $request->get('order_by', 'created_at');
            $orderDirection = $request->get('order_direction', 'desc');
            
            if (in_array($orderBy, ['title', 'price', 'created_at', 'updated_at', 'duration'])) {
                $query->orderBy($orderBy, $orderDirection);
            } elseif ($orderBy === 'rating') {
                $query->orderByRating($orderDirection);
            } elseif ($orderBy === 'popularity') {
                $query->orderByPopularity($orderDirection);
            } elseif ($orderBy === 'revenue') {
                $query->orderByRevenue($orderDirection);
            } elseif ($orderBy === 'enrollments') {
                $query->withCount('enrollments')->orderBy('enrollments_count', $orderDirection);
            } elseif ($orderBy === 'reviews') {
                $query->withCount('reviews')->orderBy('reviews_count', $orderDirection);
            }

            // Include counts if requested
            $withCounts = $request->get('with_counts', false);
            if ($withCounts) {
                $query->withCount(['lessons', 'enrollments', 'reviews', 'certificates', 'completedPayments']);
            }

            // Include stats if requested
            if ($request->has('with_stats') && $request->with_stats) {
                $query->withCount(['lessons', 'enrollments', 'reviews', 'certificates']);
            }

            // Pagination
            $perPage = $request->get('per_page', 15);
            $courses = $perPage === 'all' 
                ? $query->get()
                : $query->paginate($perPage);

            // Add stats to each course if needed
            if ($request->has('include_stats') && $request->include_stats) {
                $courses->transform(function ($course) {
                    $course->stats = $course->stats;
                    return $course;
                });
            }

            return response()->json([
                'success' => true,
                'data' => $courses,
                'message' => 'Courses retrieved successfully.'
            ]);

        } catch (\Exception $e) {
            Log::error('Course index error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve courses.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Store a newly created course.
     */
    public function store(Request $request)
    {
        DB::beginTransaction();

        try {
            $validator = Validator::make($request->all(), [
                'category_id' => 'required|exists:categories,id',
                'instructor_id' => 'required|exists:users,id',
                'title' => 'required|string|max:255|unique:courses,title',
                'slug' => 'nullable|string|max:255|unique:courses,slug|regex:/^[a-z0-9-]+$/',
                'description' => 'required|string',
                'price' => 'required|numeric|min:0',
                'level' => 'required|in:beginner,intermediate,advanced',
                'published' => 'boolean',
                'thumbnail' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
                'duration' => 'nullable|integer|min:1',
                'discount_price' => 'nullable|numeric|min:0|lt:price',
                'discount_end_date' => 'nullable|date|after:today',
                'prerequisites' => 'nullable|string',
                'learning_outcomes' => 'nullable|string',
                'certificate_available' => 'boolean',
                'certificate_template' => 'nullable|string|max:255',
                'certificate_validity_years' => 'nullable|integer|min:1|max:10',
                'language' => 'nullable|string|max:50',
                'subtitle' => 'nullable|string|max:255',
                'whats_included' => 'nullable|string',
                'target_audience' => 'nullable|string',
            ], [
                'slug.regex' => 'Slug can only contain lowercase letters, numbers, and hyphens.',
                'discount_price.lt' => 'Discount price must be less than the original price.',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors' => $validator->errors()
                ], 422);
            }

            $data = $request->all();

            // Handle thumbnail upload
            if ($request->hasFile('thumbnail')) {
                $path = $request->file('thumbnail')->store('courses/thumbnails', 'public');
                $data['thumbnail'] = $path;
            }

            // Create course
            $course = Course::create($data);

            DB::commit();

            // Load relationships if needed
            if ($request->has('load_relations')) {
                $course->load(['category', 'instructor']);
            }

            return response()->json([
                'success' => true,
                'data' => $course,
                'message' => 'Course created successfully.'
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Course store error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to create course.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Display the specified course.
     */
    public function show($slug)
    {
        try {
            $query = Course::where('slug', $slug);

            // Load relationships if requested
            if (request()->has('with')) {
                $with = explode(',', request()->with);
                $allowedRelations = ['instructor', 'category', 'lessons', 'reviews', 'enrollments', 'certificates', 'payments'];
                $query->with(array_intersect($with, $allowedRelations));
            }

            // Include counts
            $query->withCount(['lessons', 'enrollments', 'reviews', 'certificates']);

            $course = $query->firstOrFail();

            // Include additional data if requested
            if (request()->has('include_stats') && request()->include_stats) {
                $course->stats = $course->stats;
                $course->analytics = $course->analytics;
            }

            // Include rating stats if requested
            if (request()->has('include_rating_stats') && request()->include_rating_stats) {
                $course->rating_stats = $course->rating_stats;
            }

            // Include similar courses if requested
            if (request()->has('include_similar') && request()->include_similar) {
                $limit = request()->get('similar_limit', 4);
                $course->similar_courses = $course->getSimilarCourses($limit);
            }

            // Include top reviews if requested
            if (request()->has('include_top_reviews') && request()->include_top_reviews) {
                $limit = request()->get('reviews_limit', 5);
                $course->top_reviews = $course->getTopReviews($limit);
            }

            return response()->json([
                'success' => true,
                'data' => $course,
                'message' => 'Course retrieved successfully.'
            ]);

        } catch (\Exception $e) {
            Log::error('Course show error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Course not found.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 404);
        }
    }

    /**
     * Update the specified course.
     */
    public function update(Request $request, $slug)
    {
        DB::beginTransaction();

        try {
            $course = Course::where('slug', $slug)->firstOrFail();

            $validator = Validator::make($request->all(), [
                'category_id' => 'nullable|exists:categories,id',
                'instructor_id' => 'nullable|exists:users,id',
                'title' => [
                    'nullable',
                    'string',
                    'max:255',
                    Rule::unique('courses', 'title')->ignore($course->id),
                ],
                'slug' => [
                    'nullable',
                    'string',
                    'max:255',
                    'regex:/^[a-z0-9-]+$/',
                    Rule::unique('courses', 'slug')->ignore($course->id),
                ],
                'description' => 'nullable|string',
                'price' => 'nullable|numeric|min:0',
                'level' => 'nullable|in:beginner,intermediate,advanced',
                'published' => 'boolean',
                'thumbnail' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
                'duration' => 'nullable|integer|min:1',
                'discount_price' => 'nullable|numeric|min:0',
                'discount_end_date' => 'nullable|date',
                'prerequisites' => 'nullable|string',
                'learning_outcomes' => 'nullable|string',
                'certificate_available' => 'boolean',
                'certificate_template' => 'nullable|string|max:255',
                'certificate_validity_years' => 'nullable|integer|min:1|max:10',
                'language' => 'nullable|string|max:50',
                'subtitle' => 'nullable|string|max:255',
                'whats_included' => 'nullable|string',
                'target_audience' => 'nullable|string',
            ], [
                'slug.regex' => 'Slug can only contain lowercase letters, numbers, and hyphens.',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors' => $validator->errors()
                ], 422);
            }

            $data = $request->all();

            // Handle thumbnail upload
            if ($request->hasFile('thumbnail')) {
                // Delete old thumbnail if exists
                if ($course->thumbnail) {
                    Storage::disk('public')->delete($course->thumbnail);
                }
                
                $path = $request->file('thumbnail')->store('courses/thumbnails', 'public');
                $data['thumbnail'] = $path;
            }

            // Remove thumbnail if requested
            if ($request->has('remove_thumbnail') && $request->remove_thumbnail) {
                if ($course->thumbnail) {
                    Storage::disk('public')->delete($course->thumbnail);
                }
                $data['thumbnail'] = null;
            }

            // Validate discount price
            if (isset($data['discount_price']) && $data['discount_price'] >= $data['price']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Discount price must be less than the original price.',
                    'errors' => ['discount_price' => ['Discount price must be less than the original price.']]
                ], 422);
            }

            // Update course
            $course->update($data);

            DB::commit();

            // Refresh course to get updated attributes
            $course->refresh();

            return response()->json([
                'success' => true,
                'data' => $course,
                'message' => 'Course updated successfully.'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Course update error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to update course.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Remove the specified course.
     */
    public function destroy($slug)
    {
        DB::beginTransaction();

        try {
            $course = Course::where('slug', $slug)->firstOrFail();

            // Check if course has enrollments before deletion
            if ($course->enrollments()->exists() && !request()->has('force')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete course because it has enrollments. Use force option to delete anyway.',
                    'enrollments_count' => $course->enrollments_count
                ], 422);
            }

            // Delete thumbnail if exists
            if ($course->thumbnail) {
                Storage::disk('public')->delete($course->thumbnail);
            }

            $course->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Course deleted successfully.'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Course destroy error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete course.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Get course statistics.
     */
    public function statistics($slug = null)
    {
        try {
            if ($slug) {
                // Get statistics for a specific course
                $course = Course::where('slug', $slug)->firstOrFail();
                
                $stats = $course->analytics;
                $stats['course'] = $course->only(['id', 'title', 'slug', 'instructor_id', 'category_id']);

                return response()->json([
                    'success' => true,
                    'data' => $stats,
                    'message' => 'Course statistics retrieved successfully.'
                ]);
            }

            // Get general statistics
            $totalCourses = Course::count();
            $publishedCourses = Course::published()->count();
            $draftCourses = Course::draft()->count();
            $freeCourses = Course::free()->count();
            $paidCourses = Course::paid()->count();
            $discountedCourses = Course::discounted()->count();
            $coursesWithCertificate = Course::withCertificate()->count();

            // Get total enrollments across all courses
            $totalEnrollments = DB::table('enrollments')->count();

            // Get total revenue
            $totalRevenue = DB::table('payments')
                ->where('status', 'completed')
                ->sum('amount');

            // Get top courses by enrollments
            $topCoursesByEnrollments = Course::withCount('enrollments')
                ->orderBy('enrollments_count', 'desc')
                ->limit(10)
                ->get();

            // Get top courses by revenue
            $topCoursesByRevenue = Course::withSum('completedPayments', 'amount')
                ->orderBy('completed_payments_sum_amount', 'desc')
                ->limit(10)
                ->get();

            // Get courses by level
            $coursesByLevel = Course::groupBy('level')
                ->select('level', DB::raw('count(*) as count'))
                ->get();

            // Get courses by category
            $coursesByCategory = Course::with('category')
                ->select('category_id', DB::raw('count(*) as count'))
                ->groupBy('category_id')
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'total_courses' => $totalCourses,
                    'published_courses' => $publishedCourses,
                    'draft_courses' => $draftCourses,
                    'free_courses' => $freeCourses,
                    'paid_courses' => $paidCourses,
                    'discounted_courses' => $discountedCourses,
                    'courses_with_certificate' => $coursesWithCertificate,
                    'total_enrollments' => $totalEnrollments,
                    'total_revenue' => $totalRevenue,
                    'formatted_total_revenue' => '$' . number_format($totalRevenue, 2),
                    'top_courses_by_enrollments' => $topCoursesByEnrollments,
                    'top_courses_by_revenue' => $topCoursesByRevenue,
                    'courses_by_level' => $coursesByLevel,
                    'courses_by_category' => $coursesByCategory,
                ],
                'message' => 'Courses statistics retrieved successfully.'
            ]);

        } catch (\Exception $e) {
            Log::error('Course statistics error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve course statistics.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Toggle publish status.
     */
    public function togglePublish($slug)
    {
        DB::beginTransaction();

        try {
            $course = Course::where('slug', $slug)->firstOrFail();

            $course->togglePublish();

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => [
                    'published' => $course->published,
                    'status' => $course->published ? 'published' : 'draft'
                ],
                'message' => 'Course ' . ($course->published ? 'published' : 'unpublished') . ' successfully.'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Course toggle publish error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to toggle publish status.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Apply discount to course.
     */
    public function applyDiscount(Request $request, $slug)
    {
        DB::beginTransaction();

        try {
            $course = Course::where('slug', $slug)->firstOrFail();

            $validator = Validator::make($request->all(), [
                'discount_price' => 'required|numeric|min:0|lt:' . $course->price,
                'discount_end_date' => 'nullable|date|after:today',
            ], [
                'discount_price.lt' => 'Discount price must be less than the original price.',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors' => $validator->errors()
                ], 422);
            }

            $success = $course->applyDiscount(
                $request->discount_price,
                $request->discount_end_date ? new \DateTime($request->discount_end_date) : null
            );

            if (!$success) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to apply discount. Please check that discount price is less than original price.'
                ], 400);
            }

            DB::commit();

            $course->refresh();

            return response()->json([
                'success' => true,
                'data' => [
                    'discount_price' => $course->discount_price,
                    'discount_end_date' => $course->discount_end_date,
                    'discount_percentage' => $course->discount_percentage,
                    'current_price' => $course->current_price,
                    'has_discount' => $course->hasDiscount(),
                ],
                'message' => 'Discount applied successfully.'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Course apply discount error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to apply discount.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Remove discount from course.
     */
    public function removeDiscount($slug)
    {
        DB::beginTransaction();

        try {
            $course = Course::where('slug', $slug)->firstOrFail();

            if (!$course->hasDiscount()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Course does not have an active discount.'
                ], 400);
            }

            $success = $course->removeDiscount();

            if (!$success) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to remove discount.'
                ], 400);
            }

            DB::commit();

            $course->refresh();

            return response()->json([
                'success' => true,
                'data' => [
                    'discount_price' => null,
                    'discount_end_date' => null,
                    'current_price' => $course->price,
                    'has_discount' => false,
                ],
                'message' => 'Discount removed successfully.'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Course remove discount error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to remove discount.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Enable certificate for course.
     */
    public function enableCertificate(Request $request, $slug)
    {
        DB::beginTransaction();

        try {
            $course = Course::where('slug', $slug)->firstOrFail();

            $validator = Validator::make($request->all(), [
                'certificate_validity_years' => 'nullable|integer|min:1|max:10',
                'certificate_template' => 'nullable|string|max:255',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors' => $validator->errors()
                ], 422);
            }

            $success = $course->enableCertificate(
                $request->certificate_validity_years,
                $request->certificate_template
            );

            if (!$success) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to enable certificate.'
                ], 400);
            }

            DB::commit();

            $course->refresh();

            return response()->json([
                'success' => true,
                'data' => [
                    'certificate_available' => $course->certificate_available,
                    'certificate_validity_years' => $course->certificate_validity_years,
                    'certificate_template' => $course->certificate_template,
                ],
                'message' => 'Certificate enabled successfully.'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Course enable certificate error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to enable certificate.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Disable certificate for course.
     */
    public function disableCertificate($slug)
    {
        DB::beginTransaction();

        try {
            $course = Course::where('slug', $slug)->firstOrFail();

            if (!$course->certificate_available) {
                return response()->json([
                    'success' => false,
                    'message' => 'Certificate is already disabled for this course.'
                ], 400);
            }

            $success = $course->disableCertificate();

            if (!$success) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to disable certificate.'
                ], 400);
            }

            DB::commit();

            $course->refresh();

            return response()->json([
                'success' => true,
                'data' => [
                    'certificate_available' => false,
                    'certificate_validity_years' => null,
                    'certificate_template' => null,
                ],
                'message' => 'Certificate disabled successfully.'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Course disable certificate error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to disable certificate.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Get students eligible for certificate.
     */
    public function certificateEligibleStudents($slug)
    {
        try {
            $course = Course::where('slug', $slug)->firstOrFail();

            if (!$course->certificate_available) {
                return response()->json([
                    'success' => false,
                    'message' => 'Certificate is not available for this course.'
                ], 400);
            }

            $students = $course->getCertificateEligibleStudents();

            return response()->json([
                'success' => true,
                'data' => [
                    'students' => $students,
                    'count' => $students->count(),
                    'course' => $course->only(['id', 'title', 'slug']),
                ],
                'message' => 'Certificate eligible students retrieved successfully.'
            ]);

        } catch (\Exception $e) {
            Log::error('Course certificate eligible students error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve certificate eligible students.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Issue certificates to all eligible students.
     */
    public function issueCertificates(Request $request, $slug)
    {
        DB::beginTransaction();

        try {
            $course = Course::where('slug', $slug)->firstOrFail();

            if (!$course->certificate_available) {
                return response()->json([
                    'success' => false,
                    'message' => 'Certificate is not available for this course.'
                ], 400);
            }

            $issuedCount = $course->issueCertificatesToEligibleStudents();

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => [
                    'issued_count' => $issuedCount,
                    'course' => $course->only(['id', 'title', 'slug']),
                ],
                'message' => $issuedCount . ' certificate(s) issued successfully.'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Course issue certificates error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to issue certificates.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Issue certificate to a specific student.
     */
    public function issueCertificateToStudent(Request $request, $slug)
    {
        DB::beginTransaction();

        try {
            $course = Course::where('slug', $slug)->firstOrFail();

            $validator = Validator::make($request->all(), [
                'user_id' => 'required|exists:users,id',
                'metadata' => 'nullable|array',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors' => $validator->errors()
                ], 422);
            }

            if (!$course->certificate_available) {
                return response()->json([
                    'success' => false,
                    'message' => 'Certificate is not available for this course.'
                ], 400);
            }

            $certificate = $course->issueCertificateToStudent($request->user_id, $request->metadata ?? []);

            if (!$certificate) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to issue certificate. User may not have completed the course or already has a certificate.'
                ], 400);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $certificate,
                'message' => 'Certificate issued successfully.'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Course issue certificate to student error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to issue certificate.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Get similar courses.
     */
    public function similarCourses($slug)
    {
        try {
            $course = Course::where('slug', $slug)->firstOrFail();

            $limit = request()->get('limit', 4);
            $similarCourses = $course->getSimilarCourses($limit);

            return response()->json([
                'success' => true,
                'data' => [
                    'course' => $course->only(['id', 'title', 'slug', 'category_id', 'level']),
                    'similar_courses' => $similarCourses,
                    'count' => $similarCourses->count(),
                ],
                'message' => 'Similar courses retrieved successfully.'
            ]);

        } catch (\Exception $e) {
            Log::error('Course similar courses error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve similar courses.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Get course enrollments.
     */
    public function enrollments($slug)
    {
        try {
            $course = Course::where('slug', $slug)->firstOrFail();

            $query = $course->enrollments()->with('user');

            // Pagination
            $perPage = request()->get('per_page', 15);
            $enrollments = $perPage === 'all' 
                ? $query->get()
                : $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => [
                    'course' => $course->only(['id', 'title', 'slug']),
                    'enrollments' => $enrollments,
                    'total_count' => $course->enrollments_count,
                ],
                'message' => 'Course enrollments retrieved successfully.'
            ]);

        } catch (\Exception $e) {
            Log::error('Course enrollments error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve course enrollments.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Get course reviews.
     */
    public function reviews($slug)
    {
        try {
            $course = Course::where('slug', $slug)->firstOrFail();

            $query = $course->reviews()->with('user');

            // Filter by rating
            if (request()->has('rating')) {
                $query->where('rating', request()->rating);
            }

            // Ordering
            $orderBy = request()->get('order_by', 'created_at');
            $orderDirection = request()->get('order_direction', 'desc');
            
            if (in_array($orderBy, ['rating', 'created_at'])) {
                $query->orderBy($orderBy, $orderDirection);
            }

            // Pagination
            $perPage = request()->get('per_page', 15);
            $reviews = $perPage === 'all' 
                ? $query->get()
                : $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => [
                    'course' => $course->only(['id', 'title', 'slug']),
                    'reviews' => $reviews,
                    'total_count' => $course->reviews_count,
                    'average_rating' => $course->average_rating,
                    'rating_stats' => $course->rating_stats,
                ],
                'message' => 'Course reviews retrieved successfully.'
            ]);

        } catch (\Exception $e) {
            Log::error('Course reviews error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve course reviews.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Get course lessons.
     */
    public function lessons($slug)
    {
        try {
            $course = Course::where('slug', $slug)->firstOrFail();

            $query = $course->lessons();

            // Order by position by default
            if (!request()->has('order_by')) {
                $query->orderBy('position');
            }

            // Ordering
            $orderBy = request()->get('order_by');
            $orderDirection = request()->get('order_direction', 'asc');
            
            if (in_array($orderBy, ['title', 'created_at', 'position'])) {
                $query->orderBy($orderBy, $orderDirection);
            }

            // Pagination or all
            $perPage = request()->get('per_page', 'all');
            $lessons = $perPage === 'all' 
                ? $query->get()
                : $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => [
                    'course' => $course->only(['id', 'title', 'slug']),
                    'lessons' => $lessons,
                    'total_count' => $course->lessons_count,
                ],
                'message' => 'Course lessons retrieved successfully.'
            ]);

        } catch (\Exception $e) {
            Log::error('Course lessons error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve course lessons.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Get course certificates.
     */
    public function certificates($slug)
    {
        try {
            $course = Course::where('slug', $slug)->firstOrFail();

            $query = $course->certificates()->with('user');

            // Order by issue date by default
            $query->orderBy('issued_at', 'desc');

            // Pagination
            $perPage = request()->get('per_page', 15);
            $certificates = $perPage === 'all' 
                ? $query->get()
                : $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => [
                    'course' => $course->only(['id', 'title', 'slug']),
                    'certificates' => $certificates,
                    'total_count' => $course->certificates_count,
                    'certificate_analytics' => $course->certificate_analytics,
                ],
                'message' => 'Course certificates retrieved successfully.'
            ]);

        } catch (\Exception $e) {
            Log::error('Course certificates error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve course certificates.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Bulk update courses.
     */
    public function bulkUpdate(Request $request)
    {
        DB::beginTransaction();

        try {
            $validator = Validator::make($request->all(), [
                'ids' => 'required|array',
                'ids.*' => 'exists:courses,id',
                'action' => 'required|in:publish,unpublish,enable_certificate,disable_certificate',
                'data' => 'nullable|array',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors' => $validator->errors()
                ], 422);
            }

            $courses = Course::whereIn('id', $request->ids)->get();
            $updatedCount = 0;
            $failedCount = 0;
            $errors = [];

            foreach ($courses as $course) {
                try {
                    switch ($request->action) {
                        case 'publish':
                            $course->publish();
                            $updatedCount++;
                            break;
                        case 'unpublish':
                            $course->unpublish();
                            $updatedCount++;
                            break;
                        case 'enable_certificate':
                            $course->enableCertificate(
                                $request->data['certificate_validity_years'] ?? null,
                                $request->data['certificate_template'] ?? null
                            );
                            $updatedCount++;
                            break;
                        case 'disable_certificate':
                            $course->disableCertificate();
                            $updatedCount++;
                            break;
                    }
                } catch (\Exception $e) {
                    $failedCount++;
                    $errors[] = "Failed to update course '{$course->title}': " . $e->getMessage();
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Bulk update completed.',
                'data' => [
                    'updated_count' => $updatedCount,
                    'failed_count' => $failedCount,
                    'errors' => $errors,
                    'action' => $request->action,
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Course bulk update error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to perform bulk update.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Bulk delete courses.
     */
    public function bulkDestroy(Request $request)
    {
        DB::beginTransaction();

        try {
            $validator = Validator::make($request->all(), [
                'ids' => 'required|array',
                'ids.*' => 'exists:courses,id',
                'force' => 'boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors' => $validator->errors()
                ], 422);
            }

            $courses = Course::whereIn('id', $request->ids)->get();
            $deletedCount = 0;
            $failedCount = 0;
            $errors = [];

            foreach ($courses as $course) {
                try {
                    if ($request->force || !$course->enrollments()->exists()) {
                        // Delete thumbnail if exists
                        if ($course->thumbnail) {
                            Storage::disk('public')->delete($course->thumbnail);
                        }
                        
                        $course->delete();
                        $deletedCount++;
                    } else {
                        $failedCount++;
                        $errors[] = "Course '{$course->title}' has enrollments and cannot be deleted.";
                    }
                } catch (\Exception $e) {
                    $failedCount++;
                    $errors[] = "Failed to delete course '{$course->title}': " . $e->getMessage();
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Bulk delete completed.',
                'data' => [
                    'deleted_count' => $deletedCount,
                    'failed_count' => $failedCount,
                    'errors' => $errors,
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Course bulk destroy error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to perform bulk delete.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Get dropdown data for courses (for select boxes).
     */
    public function dropdown()
    {
        try {
            $courses = Course::select('id', 'title', 'slug', 'instructor_id', 'category_id')
                ->when(request()->has('instructor_id'), function ($query) {
                    $query->where('instructor_id', request()->instructor_id);
                })
                ->when(request()->has('category_id'), function ($query) {
                    $query->where('category_id', request()->category_id);
                })
                ->when(request()->has('published'), function ($query) {
                    $query->where('published', request()->published);
                })
                ->orderBy('title')
                ->get()
                ->map(function ($course) {
                    return [
                        'id' => $course->id,
                        'title' => $course->title,
                        'slug' => $course->slug,
                        'instructor_name' => $course->instructor->name ?? null,
                        'category_name' => $course->category->name ?? null,
                        'current_price' => $course->formatted_current_price,
                        'is_free' => $course->isFree(),
                        'has_discount' => $course->hasDiscount(),
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $courses,
                'message' => 'Courses dropdown retrieved successfully.'
            ]);

        } catch (\Exception $e) {
            Log::error('Course dropdown error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve courses dropdown.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
}