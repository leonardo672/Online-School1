<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use App\Models\User;
use App\Models\Course;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CertificateController extends Controller
{
    /**
     * Display a listing of the certificates.
     */
    public function index(Request $request)
    {
        // Build query with filters
        $query = Certificate::with(['user', 'course'])
            ->when($request->filled('user_id'), function ($q) use ($request) {
                $q->where('user_id', $request->user_id);
            })
            ->when($request->filled('course_id'), function ($q) use ($request) {
                $q->where('course_id', $request->course_id);
            })
            ->when($request->filled('search'), function ($q) use ($request) {
                $q->whereHas('user', function ($query) use ($request) {
                    $query->where('name', 'like', '%' . $request->search . '%')
                        ->orWhere('email', 'like', '%' . $request->search . '%');
                })->orWhereHas('course', function ($query) use ($request) {
                    $query->where('title', 'like', '%' . $request->search . '%');
                });
            })
            ->latest();

        // Get paginated results
        $certificates = $query->paginate(20)->appends($request->query());
        
        // Get general statistics
        $certificatesCount = Certificate::count();
        $latestCertificate = Certificate::with(['user', 'course'])->latest()->first();
        
        // Calculate certificate status counts
        $validCount = Certificate::where(function($query) {
            $query->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
        })->where(function($query) {
            $query->whereNotNull('issued_at')
                  ->where('issued_at', '<=', now());
        })->count();

        $expiredCount = Certificate::whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->count();

        $expiringSoonCount = Certificate::whereNotNull('expires_at')
            ->where('expires_at', '>', now())
            ->where('expires_at', '<=', now()->addDays(30))
            ->count();

        $notIssuedCount = Certificate::whereNull('issued_at')
            ->orWhere('issued_at', '>', now())
            ->count();
        
        // Calculate the most certified course with count
        $mostCertifiedCourseData = Certificate::select('course_id')
            ->selectRaw('COUNT(*) as certificate_count')
            ->groupBy('course_id')
            ->orderByDesc('certificate_count')
            ->first();
        
        $mostCertifiedCourse = null;
        $mostCertifiedCourseCount = 0;
        
        if ($mostCertifiedCourseData) {
            $mostCertifiedCourse = Course::find($mostCertifiedCourseData->course_id);
            $mostCertifiedCourseCount = $mostCertifiedCourseData->certificate_count;
        }
        
        // Get course statistics for all courses - FIXED: Use DB raw query
        $courseStats = DB::table('courses')
            ->leftJoin('certificates', 'courses.id', '=', 'certificates.course_id')
            ->select('courses.*', DB::raw('COUNT(certificates.id) as certificates_count'))
            ->groupBy('courses.id')
            ->orderByDesc('certificates_count')
            ->get();
        
        // Get user statistics - FIXED: Use DB raw query
        $topUsers = DB::table('users')
            ->leftJoin('certificates', 'users.id', '=', 'certificates.user_id')
            ->select('users.*', DB::raw('COUNT(certificates.id) as certificates_count'))
            ->groupBy('users.id')
            ->orderByDesc('certificates_count')
            ->limit(5)
            ->get();
        
        // Get recent certificates
        $recentCertificates = Certificate::with(['user', 'course'])
            ->latest()
            ->take(5)
            ->get();
        
        // Get certificates by month for chart (last 6 months) - FIXED for PostgreSQL
        $certificatesByMonth = Certificate::selectRaw("EXTRACT(MONTH FROM issued_at) as month, COUNT(*) as count")
            ->whereNotNull('issued_at')
            ->where('issued_at', '>=', now()->subMonths(6))
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->mapWithKeys(function ($item) {
                return [
                    date('F', mktime(0, 0, 0, $item->month, 1)) => $item->count
                ];
            });
        
        return view('certificates.index', [
            'certificates' => $certificates,
            'users' => User::all(),
            'courses' => Course::all(),
            
            // Statistics
            'certificatesCount' => $certificatesCount,
            'latestCertificate' => $latestCertificate,
            
            // Status counts
            'validCount' => $validCount,
            'expiredCount' => $expiredCount,
            'expiringSoonCount' => $expiringSoonCount,
            'notIssuedCount' => $notIssuedCount,
            
            // Course statistics
            'mostCertifiedCourse' => $mostCertifiedCourse,
            'mostCertifiedCourseCount' => $mostCertifiedCourseCount,
            'courseStats' => $courseStats,
            
            // User statistics
            'topUsers' => $topUsers,
            'recentCertificates' => $recentCertificates,
            'certificatesByMonth' => $certificatesByMonth,
        ]);
    }

    /**
     * Show the form for creating a new certificate.
     */
    public function create()
    {
        $users = User::all();
        $courses = Course::all();
        
        return view('certificates.create', compact('users', 'courses'));
    }

    /**
     * Store a newly created certificate in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'course_id' => 'required|exists:courses,id',
            'certificate_code' => 'required|string|unique:certificates,certificate_code',
            'expires_at' => 'nullable|date',
        ]);

        Certificate::create([
            'user_id' => $request->user_id,
            'course_id' => $request->course_id,
            'certificate_code' => $request->certificate_code,
            'issued_at' => now(),
            'expires_at' => $request->expires_at,
        ]);

        return redirect()->route('certificates.index')->with('success', 'Certificate created successfully!');
    }

    /**
     * Display the specified certificate.
     */
    public function show(string $id)
    {
        $certificate = Certificate::with(['user', 'course'])->findOrFail($id);
        return view('certificates.show', compact('certificate'));
    }

    /**
     * Show the form for editing the specified certificate.
     */
    public function edit($id)
    {
        $certificate = Certificate::findOrFail($id);
        $users = User::all();
        $courses = Course::all();
        
        return view('certificates.edit', compact('certificate', 'users', 'courses'));
    }

    /**
     * Update the specified certificate in storage.
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'course_id' => 'required|exists:courses,id',
            'certificate_code' => 'required|string|unique:certificates,certificate_code,' . $id,
            'expires_at' => 'nullable|date',
        ]);

        $certificate = Certificate::findOrFail($id);
        $certificate->update([
            'user_id' => $request->user_id,
            'course_id' => $request->course_id,
            'certificate_code' => $request->certificate_code,
            'expires_at' => $request->expires_at,
        ]);

        return redirect()->route('certificates.index')->with('success', 'Certificate updated successfully!');
    }

    /**
     * Remove the specified certificate from storage.
     */
    public function destroy(string $id)
    {
        $certificate = Certificate::findOrFail($id);
        $certificate->delete();

        return redirect()->route('certificates.index')->with('success', 'Certificate deleted successfully.');
    }
    
    /**
     * Show statistics dashboard.
     */
    public function dashboard()
    {
        // Total statistics
        $totalCertificates = Certificate::count();
        $totalUsers = User::count();
        $totalCourses = Course::count();
        
        // Certificate status counts
        $validCount = Certificate::where(function($query) {
            $query->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
        })->where(function($query) {
            $query->whereNotNull('issued_at')
                  ->where('issued_at', '<=', now());
        })->count();

        $expiredCount = Certificate::whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->count();

        $expiringSoonCount = Certificate::whereNotNull('expires_at')
            ->where('expires_at', '>', now())
            ->where('expires_at', '<=', now()->addDays(30))
            ->count();
        
        // Most certified course
        $mostCertifiedCourseData = Certificate::select('course_id')
            ->selectRaw('COUNT(*) as certificate_count')
            ->groupBy('course_id')
            ->orderByDesc('certificate_count')
            ->first();
        
        $mostCertifiedCourse = null;
        $mostCertifiedCourseCount = 0;
        
        if ($mostCertifiedCourseData) {
            $mostCertifiedCourse = Course::find($mostCertifiedCourseData->course_id);
            $mostCertifiedCourseCount = $mostCertifiedCourseData->certificate_count;
        }
        
        // Recent activity
        $recentCertificates = Certificate::with(['user', 'course'])
            ->latest()
            ->take(10)
            ->get();
        
        // Course distribution - FIXED: Use DB raw query
        $courseDistribution = DB::table('courses')
            ->leftJoin('certificates', 'courses.id', '=', 'certificates.course_id')
            ->select('courses.*', DB::raw('COUNT(certificates.id) as certificates_count'))
            ->groupBy('courses.id')
            ->orderByDesc('certificates_count')
            ->get();
        
        // Monthly trends (last 12 months) - FIXED for PostgreSQL
        $monthlyTrends = Certificate::selectRaw("TO_CHAR(issued_at, 'YYYY-MM') as month, COUNT(*) as count")
            ->whereNotNull('issued_at')
            ->where('issued_at', '>=', now()->subMonths(12))
            ->groupBy('month')
            ->orderBy('month')
            ->get();
        
        return view('certificates.dashboard', [
            'totalCertificates' => $totalCertificates,
            'totalUsers' => $totalUsers,
            'totalCourses' => $totalCourses,
            'validCount' => $validCount,
            'expiredCount' => $expiredCount,
            'expiringSoonCount' => $expiringSoonCount,
            'mostCertifiedCourse' => $mostCertifiedCourse,
            'mostCertifiedCourseCount' => $mostCertifiedCourseCount,
            'recentCertificates' => $recentCertificates,
            'courseDistribution' => $courseDistribution,
            'monthlyTrends' => $monthlyTrends,
        ]);
    }
}