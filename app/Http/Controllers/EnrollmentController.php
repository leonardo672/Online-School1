<?php

namespace App\Http\Controllers;

use App\Models\Enrollment;
use App\Models\Course;
use App\Models\User;
use App\Models\LessonProgress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class EnrollmentController extends Controller
{
    /**
     * Display a listing of enrollments.
     */
    public function index(Request $request)
    {
        try {
            $query = Enrollment::query();

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

            // Filter by user email
            if ($request->has('user_email') && !empty($request->user_email)) {
                $user = User::where('email', $request->user_email)->first();
                if ($user) {
                    $query->where('user_id', $user->id);
                }
            }

            // Filter by date range
            if ($request->has('date_from') && !empty($request->date_from)) {
                $query->whereDate('enrolled_at', '>=', $request->date_from);
            }
            if ($request->has('date_to') && !empty($request->date_to)) {
                $query->whereDate('enrolled_at', '<=', $request->date_to);
            }

            // Filter by recent enrollments (last 7 days)
            if ($request->has('recent') && $request->recent) {
                $query->where('enrolled_at', '>=', now()->subDays(7));
            }

            // Ordering
            $orderBy = $request->get('order_by', 'enrolled_at');
            $orderDirection = $request->get('order_direction', 'desc');
            
            if (in_array($orderBy, ['enrolled_at', 'created_at', 'updated_at'])) {
                $query->orderBy($orderBy, $orderDirection);
            }

            // Include progress data if requested
            $includeProgress = $request->get('include_progress', false);
            if ($includeProgress) {
                $query->with(['course.lessons' => function ($query) use ($request) {
                    if ($request->has('user_id')) {
                        $query->with(['progress' => function ($q) use ($request) {
                            $q->where('user_id', $request->user_id);
                        }]);
                    }
                }]);
            }

            // Pagination
            $perPage = $request->get('per_page', 15);
            $enrollments = $perPage === 'all' 
                ? $query->get()
                : $query->paginate($perPage);

            // Add computed attributes if requested
            if ($request->has('include_computed') && $request->include_computed) {
                $enrollments->transform(function ($enrollment) {
                    $enrollment->formatted_enrolled_at = $enrollment->formatted_enrolled_at;
                    $enrollment->human_duration = $enrollment->human_duration;
                    $enrollment->enrollment_duration = $enrollment->enrollment_duration;
                    $enrollment->is_recent = $enrollment->isRecent();
                    return $enrollment;
                });
            }

            // Add progress statistics if requested
            if ($includeProgress) {
                $enrollments->transform(function ($enrollment) {
                    $completedLessons = 0;
                    $totalLessons = $enrollment->course->lessons->count();
                    
                    if ($enrollment->course->lessons) {
                        foreach ($enrollment->course->lessons as $lesson) {
                            if ($lesson->progress && $lesson->progress->isNotEmpty() && $lesson->progress->first()->completed) {
                                $completedLessons++;
                            }
                        }
                    }
                    
                    $enrollment->progress = [
                        'completed_lessons' => $completedLessons,
                        'total_lessons' => $totalLessons,
                        'percentage' => $totalLessons > 0 ? round(($completedLessons / $totalLessons) * 100, 2) : 0,
                    ];
                    
                    return $enrollment;
                });
            }

            return response()->json([
                'success' => true,
                'data' => $enrollments,
                'message' => 'Enrollments retrieved successfully.'
            ]);

        } catch (\Exception $e) {
            Log::error('Enrollment index error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve enrollments.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Store a newly created enrollment.
     */
    public function store(Request $request)
    {
        DB::beginTransaction();

        try {
            $validator = Validator::make($request->all(), [
                'user_id' => 'required|exists:users,id',
                'course_id' => 'required|exists:courses,id',
                'enrolled_at' => 'nullable|date',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Check if user is already enrolled
            if (Enrollment::isEnrolled($request->user_id, $request->course_id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'User is already enrolled in this course.'
                ], 409); // Conflict status code
            }

            // Check if course is published
            $course = Course::find($request->course_id);
            if (!$course->published && !$request->has('force')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Course is not published. Use force option to enroll anyway.'
                ], 422);
            }

            $enrollment = Enrollment::create($request->all());

            DB::commit();

            // Load relationships if needed
            if ($request->has('load_relations')) {
                $enrollment->load(['user', 'course']);
            }

            return response()->json([
                'success' => true,
                'data' => $enrollment,
                'message' => 'User enrolled successfully.'
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Enrollment store error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to create enrollment.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Enroll current user in a course.
     */
    public function enrollSelf(Request $request)
    {
        DB::beginTransaction();

        try {
            $user = auth()->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated.'
                ], 401);
            }

            $validator = Validator::make($request->all(), [
                'course_id' => 'required|exists:courses,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Check if user is already enrolled
            if (Enrollment::isEnrolled($user->id, $request->course_id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are already enrolled in this course.'
                ], 409);
            }

            // Check if course is published
            $course = Course::find($request->course_id);
            if (!$course->published) {
                return response()->json([
                    'success' => false,
                    'message' => 'Course is not published.'
                ], 422);
            }

            // Check if course is free or user has paid
            if ($course->price > 0) {
                // Check if user has paid for the course
                if (!$course->hasUserPaid($user->id) && !$request->has('force')) {
                    return response()->json([
                        'success' => false,
                        'message' => 'This course requires payment. Purchase the course to enroll.'
                    ], 402); // Payment required
                }
            }

            $enrollment = Enrollment::enroll($user->id, $request->course_id);

            if (!$enrollment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to enroll in course.'
                ], 400);
            }

            DB::commit();

            $enrollment->load(['course']);

            return response()->json([
                'success' => true,
                'data' => $enrollment,
                'message' => 'Successfully enrolled in course.'
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Self enrollment error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to enroll in course.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Display the specified enrollment.
     */
    public function show($id)
    {
        try {
            $query = Enrollment::query();

            // Load relationships if requested
            if (request()->has('with')) {
                $with = explode(',', request()->with);
                $allowedRelations = ['user', 'course', 'course.instructor', 'course.category', 'course.lessons'];
                $query->with(array_intersect($with, $allowedRelations));
            }

            $enrollment = $query->findOrFail($id);

            // Include progress if requested
            if (request()->has('include_progress') && request()->include_progress) {
                $completedLessons = LessonProgress::where('user_id', $enrollment->user_id)
                    ->where('completed', true)
                    ->whereHas('lesson', function ($q) use ($enrollment) {
                        $q->where('course_id', $enrollment->course_id);
                    })
                    ->count();
                
                $totalLessons = $enrollment->course->lessons->count();
                
                $enrollment->progress = [
                    'completed_lessons' => $completedLessons,
                    'total_lessons' => $totalLessons,
                    'percentage' => $totalLessons > 0 ? round(($completedLessons / $totalLessons) * 100, 2) : 0,
                ];
            }

            // Add computed attributes
            $enrollment->formatted_enrolled_at = $enrollment->formatted_enrolled_at;
            $enrollment->human_duration = $enrollment->human_duration;
            $enrollment->is_recent = $enrollment->isRecent();

            return response()->json([
                'success' => true,
                'data' => $enrollment,
                'message' => 'Enrollment retrieved successfully.'
            ]);

        } catch (\Exception $e) {
            Log::error('Enrollment show error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Enrollment not found.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 404);
        }
    }

    /**
     * Update the specified enrollment.
     */
    public function update(Request $request, $id)
    {
        DB::beginTransaction();

        try {
            $enrollment = Enrollment::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'enrolled_at' => 'nullable|date',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Update enrollment
            $enrollment->update($request->all());

            DB::commit();

            $enrollment->refresh();

            return response()->json([
                'success' => true,
                'data' => $enrollment,
                'message' => 'Enrollment updated successfully.'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Enrollment update error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to update enrollment.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Remove the specified enrollment.
     */
    public function destroy($id)
    {
        DB::beginTransaction();

        try {
            $enrollment = Enrollment::findOrFail($id);

            // Delete associated lesson progress
            LessonProgress::where('user_id', $enrollment->user_id)
                ->whereHas('lesson', function ($q) use ($enrollment) {
                    $q->where('course_id', $enrollment->course_id);
                })
                ->delete();

            $enrollment->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Enrollment deleted successfully.'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Enrollment destroy error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete enrollment.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Unenroll a user from a course.
     */
    public function unenroll(Request $request)
    {
        DB::beginTransaction();

        try {
            $validator = Validator::make($request->all(), [
                'user_id' => 'required_without:user_email|exists:users,id',
                'user_email' => 'required_without:user_id|email|exists:users,email',
                'course_id' => 'required_without:course_slug|exists:courses,id',
                'course_slug' => 'required_without:course_id|exists:courses,slug',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Get user ID
            $userId = $request->user_id;
            if (!$userId && $request->user_email) {
                $user = User::where('email', $request->user_email)->first();
                $userId = $user->id;
            }

            // Get course ID
            $courseId = $request->course_id;
            if (!$courseId && $request->course_slug) {
                $course = Course::where('slug', $request->course_slug)->first();
                $courseId = $course->id;
            }

            // Check if user is enrolled
            if (!Enrollment::isEnrolled($userId, $courseId)) {
                return response()->json([
                    'success' => false,
                    'message' => 'User is not enrolled in this course.'
                ], 404);
            }

            // Delete lesson progress
            LessonProgress::where('user_id', $userId)
                ->whereHas('lesson', function ($q) use ($courseId) {
                    $q->where('course_id', $courseId);
                })
                ->delete();

            // Unenroll
            $success = Enrollment::unenroll($userId, $courseId);

            if (!$success) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to unenroll user.'
                ], 400);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'User unenrolled successfully.'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Unenroll error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to unenroll user.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Unenroll current user from a course.
     */
    public function unenrollSelf(Request $request)
    {
        DB::beginTransaction();

        try {
            $user = auth()->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated.'
                ], 401);
            }

            $validator = Validator::make($request->all(), [
                'course_id' => 'required_without:course_slug|exists:courses,id',
                'course_slug' => 'required_without:course_id|exists:courses,slug',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Get course ID
            $courseId = $request->course_id;
            if (!$courseId && $request->course_slug) {
                $course = Course::where('slug', $request->course_slug)->first();
                $courseId = $course->id;
            }

            // Check if user is enrolled
            if (!Enrollment::isEnrolled($user->id, $courseId)) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not enrolled in this course.'
                ], 404);
            }

            // Delete lesson progress
            LessonProgress::where('user_id', $user->id)
                ->whereHas('lesson', function ($q) use ($courseId) {
                    $q->where('course_id', $courseId);
                })
                ->delete();

            // Unenroll
            $success = Enrollment::unenroll($user->id, $courseId);

            if (!$success) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to unenroll from course.'
                ], 400);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Successfully unenrolled from course.'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Self unenroll error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to unenroll from course.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Check if a user is enrolled in a course.
     */
    public function checkEnrollment(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'user_id' => 'required_without:user_email|exists:users,id',
                'user_email' => 'required_without:user_id|email|exists:users,email',
                'course_id' => 'required_without:course_slug|exists:courses,id',
                'course_slug' => 'required_without:course_id|exists:courses,slug',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Get user ID
            $userId = $request->user_id;
            if (!$userId && $request->user_email) {
                $user = User::where('email', $request->user_email)->first();
                $userId = $user->id;
            }

            // Get course ID
            $courseId = $request->course_id;
            if (!$courseId && $request->course_slug) {
                $course = Course::where('slug', $request->course_slug)->first();
                $courseId = $course->id;
            }

            $isEnrolled = Enrollment::isEnrolled($userId, $courseId);

            return response()->json([
                'success' => true,
                'data' => [
                    'is_enrolled' => $isEnrolled,
                    'user_id' => $userId,
                    'course_id' => $courseId,
                ],
                'message' => 'Enrollment status retrieved.'
            ]);

        } catch (\Exception $e) {
            Log::error('Check enrollment error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to check enrollment status.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Check if current user is enrolled in a course.
     */
    public function checkSelfEnrollment(Request $request)
    {
        try {
            $user = auth()->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated.'
                ], 401);
            }

            $validator = Validator::make($request->all(), [
                'course_id' => 'required_without:course_slug|exists:courses,id',
                'course_slug' => 'required_without:course_id|exists:courses,slug',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Get course ID
            $courseId = $request->course_id;
            if (!$courseId && $request->course_slug) {
                $course = Course::where('slug', $request->course_slug)->first();
                $courseId = $course->id;
            }

            $isEnrolled = Enrollment::isEnrolled($user->id, $courseId);
            $enrollment = null;

            if ($isEnrolled) {
                $enrollment = Enrollment::where('user_id', $user->id)
                    ->where('course_id', $courseId)
                    ->first();
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'is_enrolled' => $isEnrolled,
                    'enrollment' => $enrollment,
                    'course_id' => $courseId,
                ],
                'message' => 'Enrollment status retrieved.'
            ]);

        } catch (\Exception $e) {
            Log::error('Check self enrollment error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to check enrollment status.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Get enrollment statistics.
     */
    public function statistics(Request $request)
    {
        try {
            $query = Enrollment::query();

            // Apply filters if any
            if ($request->has('course_id') && !empty($request->course_id)) {
                $query->where('course_id', $request->course_id);
            }

            if ($request->has('date_from') && !empty($request->date_from)) {
                $query->whereDate('enrolled_at', '>=', $request->date_from);
            }

            if ($request->has('date_to') && !empty($request->date_to)) {
                $query->whereDate('enrolled_at', '<=', $request->date_to);
            }

            $totalEnrollments = $query->count();
            $todayEnrollments = $query->whereDate('enrolled_at', today())->count();
            $weekEnrollments = $query->where('enrolled_at', '>=', now()->subDays(7))->count();
            $monthEnrollments = $query->where('enrolled_at', '>=', now()->subDays(30))->count();

            // Get enrollments by course
            $enrollmentsByCourse = Enrollment::select('course_id', DB::raw('count(*) as count'))
                ->groupBy('course_id')
                ->orderByDesc('count')
                ->with('course')
                ->limit(10)
                ->get();

            // Get enrollments by day for the last 30 days
            $enrollmentsByDay = Enrollment::select(
                    DB::raw('DATE(enrolled_at) as date'),
                    DB::raw('count(*) as count')
                )
                ->where('enrolled_at', '>=', now()->subDays(30))
                ->groupBy('date')
                ->orderBy('date')
                ->get();

            // Get top enrolled courses
            $topCourses = Course::withCount('enrollments')
                ->orderBy('enrollments_count', 'desc')
                ->limit(10)
                ->get();

            // Get recent enrollments
            $recentEnrollments = Enrollment::with(['user', 'course'])
                ->latestFirst()
                ->limit(10)
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'total_enrollments' => $totalEnrollments,
                    'today_enrollments' => $todayEnrollments,
                    'week_enrollments' => $weekEnrollments,
                    'month_enrollments' => $monthEnrollments,
                    'enrollments_by_course' => $enrollmentsByCourse,
                    'enrollments_by_day' => $enrollmentsByDay,
                    'top_courses' => $topCourses,
                    'recent_enrollments' => $recentEnrollments,
                ],
                'message' => 'Enrollment statistics retrieved successfully.'
            ]);

        } catch (\Exception $e) {
            Log::error('Enrollment statistics error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve enrollment statistics.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Get course enrollment statistics.
     */
    public function courseStatistics($courseId)
    {
        try {
            $course = Course::findOrFail($courseId);

            $totalEnrollments = $course->enrollments()->count();
            $todayEnrollments = $course->enrollments()->whereDate('enrolled_at', today())->count();
            $weekEnrollments = $course->enrollments()->where('enrolled_at', '>=', now()->subDays(7))->count();
            $monthEnrollments = $course->enrollments()->where('enrolled_at', '>=', now()->subDays(30))->count();

            // Get enrollment growth
            $enrollmentsByDay = $course->enrollments()
                ->select(
                    DB::raw('DATE(enrolled_at) as date'),
                    DB::raw('count(*) as count')
                )
                ->where('enrolled_at', '>=', now()->subDays(30))
                ->groupBy('date')
                ->orderBy('date')
                ->get();

            // Get recent enrollments for this course
            $recentEnrollments = $course->enrollments()
                ->with('user')
                ->latestFirst()
                ->limit(10)
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'course' => $course->only(['id', 'title', 'slug']),
                    'total_enrollments' => $totalEnrollments,
                    'today_enrollments' => $todayEnrollments,
                    'week_enrollments' => $weekEnrollments,
                    'month_enrollments' => $monthEnrollments,
                    'enrollments_by_day' => $enrollmentsByDay,
                    'recent_enrollments' => $recentEnrollments,
                ],
                'message' => 'Course enrollment statistics retrieved successfully.'
            ]);

        } catch (\Exception $e) {
            Log::error('Course enrollment statistics error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve course enrollment statistics.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Get user enrollment statistics.
     */
    public function userStatistics($userId)
    {
        try {
            $user = User::findOrFail($userId);

            $totalEnrollments = $user->enrollments()->count();
            $completedCourses = 0;
            $inProgressCourses = 0;
            $totalProgress = 0;

            $enrollments = $user->enrollments()->with(['course', 'course.lessons'])->get();

            foreach ($enrollments as $enrollment) {
                $completedLessons = LessonProgress::where('user_id', $user->id)
                    ->where('completed', true)
                    ->whereHas('lesson', function ($q) use ($enrollment) {
                        $q->where('course_id', $enrollment->course_id);
                    })
                    ->count();
                
                $totalLessons = $enrollment->course->lessons->count();
                
                if ($totalLessons > 0) {
                    $progress = ($completedLessons / $totalLessons) * 100;
                    $totalProgress += $progress;
                    
                    if ($progress >= 100) {
                        $completedCourses++;
                    } else if ($progress > 0) {
                        $inProgressCourses++;
                    }
                }
            }

            $averageProgress = $totalEnrollments > 0 ? ($totalProgress / $totalEnrollments) : 0;

            // Get recent enrollments
            $recentEnrollments = $user->enrollments()
                ->with('course')
                ->latestFirst()
                ->limit(10)
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'user' => $user->only(['id', 'name', 'email']),
                    'total_enrollments' => $totalEnrollments,
                    'completed_courses' => $completedCourses,
                    'in_progress_courses' => $inProgressCourses,
                    'average_progress' => round($averageProgress, 2),
                    'recent_enrollments' => $recentEnrollments,
                ],
                'message' => 'User enrollment statistics retrieved successfully.'
            ]);

        } catch (\Exception $e) {
            Log::error('User enrollment statistics error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve user enrollment statistics.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Bulk enroll users in a course.
     */
    public function bulkEnroll(Request $request)
    {
        DB::beginTransaction();

        try {
            $validator = Validator::make($request->all(), [
                'course_id' => 'required|exists:courses,id',
                'user_ids' => 'required|array',
                'user_ids.*' => 'exists:users,id',
                'skip_existing' => 'boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors' => $validator->errors()
                ], 422);
            }

            $course = Course::find($request->course_id);
            $enrolledCount = 0;
            $skippedCount = 0;
            $failedCount = 0;
            $errors = [];

            foreach ($request->user_ids as $userId) {
                try {
                    // Check if already enrolled
                    if (Enrollment::isEnrolled($userId, $course->id)) {
                        if ($request->skip_existing) {
                            $skippedCount++;
                            continue;
                        } else {
                            $failedCount++;
                            $errors[] = "User {$userId} is already enrolled in this course.";
                            continue;
                        }
                    }

                    // Check if course is published
                    if (!$course->published && !$request->has('force')) {
                        $failedCount++;
                        $errors[] = "Course is not published. User {$userId} not enrolled.";
                        continue;
                    }

                    Enrollment::enroll($userId, $course->id);
                    $enrolledCount++;
                } catch (\Exception $e) {
                    $failedCount++;
                    $errors[] = "Failed to enroll user {$userId}: " . $e->getMessage();
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Bulk enrollment completed.',
                'data' => [
                    'course' => $course->only(['id', 'title', 'slug']),
                    'enrolled_count' => $enrolledCount,
                    'skipped_count' => $skippedCount,
                    'failed_count' => $failedCount,
                    'errors' => $errors,
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Bulk enroll error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to perform bulk enrollment.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Bulk unenroll users from a course.
     */
    public function bulkUnenroll(Request $request)
    {
        DB::beginTransaction();

        try {
            $validator = Validator::make($request->all(), [
                'course_id' => 'required|exists:courses,id',
                'user_ids' => 'required|array',
                'user_ids.*' => 'exists:users,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors' => $validator->errors()
                ], 422);
            }

            $course = Course::find($request->course_id);
            $unenrolledCount = 0;
            $failedCount = 0;
            $errors = [];

            foreach ($request->user_ids as $userId) {
                try {
                    // Check if enrolled
                    if (!Enrollment::isEnrolled($userId, $course->id)) {
                        $failedCount++;
                        $errors[] = "User {$userId} is not enrolled in this course.";
                        continue;
                    }

                    // Delete lesson progress
                    LessonProgress::where('user_id', $userId)
                        ->whereHas('lesson', function ($q) use ($course) {
                            $q->where('course_id', $course->id);
                        })
                        ->delete();

                    // Unenroll
                    $success = Enrollment::unenroll($userId, $course->id);
                    
                    if ($success) {
                        $unenrolledCount++;
                    } else {
                        $failedCount++;
                        $errors[] = "Failed to unenroll user {$userId}.";
                    }
                } catch (\Exception $e) {
                    $failedCount++;
                    $errors[] = "Failed to unenroll user {$userId}: " . $e->getMessage();
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Bulk unenrollment completed.',
                'data' => [
                    'course' => $course->only(['id', 'title', 'slug']),
                    'unenrolled_count' => $unenrolledCount,
                    'failed_count' => $failedCount,
                    'errors' => $errors,
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Bulk unenroll error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to perform bulk unenrollment.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Transfer enrollment from one user to another.
     */
    public function transfer(Request $request)
    {
        DB::beginTransaction();

        try {
            $validator = Validator::make($request->all(), [
                'from_user_id' => 'required|exists:users,id',
                'to_user_id' => 'required|exists:users,id',
                'course_id' => 'required|exists:courses,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Check if from user is enrolled
            if (!Enrollment::isEnrolled($request->from_user_id, $request->course_id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Source user is not enrolled in this course.'
                ], 404);
            }

            // Check if to user is already enrolled
            if (Enrollment::isEnrolled($request->to_user_id, $request->course_id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Destination user is already enrolled in this course.'
                ], 409);
            }

            // Get the enrollment
            $enrollment = Enrollment::where('user_id', $request->from_user_id)
                ->where('course_id', $request->course_id)
                ->first();

            // Transfer lesson progress
            LessonProgress::where('user_id', $request->from_user_id)
                ->whereHas('lesson', function ($q) use ($request) {
                    $q->where('course_id', $request->course_id);
                })
                ->update(['user_id' => $request->to_user_id]);

            // Create new enrollment for destination user
            $newEnrollment = Enrollment::create([
                'user_id' => $request->to_user_id,
                'course_id' => $request->course_id,
                'enrolled_at' => $enrollment->enrolled_at,
            ]);

            // Delete old enrollment
            $enrollment->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $newEnrollment,
                'message' => 'Enrollment transferred successfully.'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Enrollment transfer error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to transfer enrollment.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Sync user enrollments (remove all and add new ones).
     */
    public function sync(Request $request)
    {
        DB::beginTransaction();

        try {
            $validator = Validator::make($request->all(), [
                'user_id' => 'required|exists:users,id',
                'course_ids' => 'required|array',
                'course_ids.*' => 'exists:courses,id',
                'remove_others' => 'boolean|default:true',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors' => $validator->errors()
                ], 422);
            }

            $userId = $request->user_id;
            $courseIds = $request->course_ids;
            $enrolledCount = 0;
            $removedCount = 0;
            $errors = [];

            // Remove existing enrollments if requested
            if ($request->remove_others) {
                $existingEnrollments = Enrollment::where('user_id', $userId)->get();
                
                foreach ($existingEnrollments as $enrollment) {
                    if (!in_array($enrollment->course_id, $courseIds)) {
                        // Delete lesson progress
                        LessonProgress::where('user_id', $userId)
                            ->whereHas('lesson', function ($q) use ($enrollment) {
                                $q->where('course_id', $enrollment->course_id);
                            })
                            ->delete();
                        
                        $enrollment->delete();
                        $removedCount++;
                    }
                }
            }

            // Add new enrollments
            foreach ($courseIds as $courseId) {
                try {
                    if (!Enrollment::isEnrolled($userId, $courseId)) {
                        $course = Course::find($courseId);
                        
                        if (!$course->published && !$request->has('force')) {
                            $errors[] = "Course {$courseId} is not published.";
                            continue;
                        }

                        Enrollment::enroll($userId, $courseId);
                        $enrolledCount++;
                    }
                } catch (\Exception $e) {
                    $errors[] = "Failed to enroll in course {$courseId}: " . $e->getMessage();
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Enrollments synced successfully.',
                'data' => [
                    'user_id' => $userId,
                    'enrolled_count' => $enrolledCount,
                    'removed_count' => $removedCount,
                    'total_enrollments' => Enrollment::where('user_id', $userId)->count(),
                    'errors' => $errors,
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Enrollments sync error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to sync enrollments.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
}