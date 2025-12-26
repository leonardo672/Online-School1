<?php

namespace App\Http\Controllers;

use App\Models\LessonProgress;
use App\Models\Lesson;
use App\Models\Course;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class LessonProgressController extends Controller
{
    /**
     * Display a listing of lesson progress records.
     */
    public function index(Request $request)
    {
        try {
            $query = LessonProgress::query();

            // Eager load relationships if requested
            if ($request->has('with')) {
                $with = explode(',', $request->with);
                $allowedRelations = ['user', 'lesson', 'lesson.course'];
                $query->with(array_intersect($with, $allowedRelations));
            }

            // Filter by user
            if ($request->has('user_id') && !empty($request->user_id)) {
                $query->where('user_id', $request->user_id);
            }

            // Filter by lesson
            if ($request->has('lesson_id') && !empty($request->lesson_id)) {
                $query->where('lesson_id', $request->lesson_id);
            }

            // Filter by course
            if ($request->has('course_id') && !empty($request->course_id)) {
                $query->whereHas('lesson', function ($q) use ($request) {
                    $q->where('course_id', $request->course_id);
                });
            }

            // Filter by course slug
            if ($request->has('course_slug') && !empty($request->course_slug)) {
                $course = Course::where('slug', $request->course_slug)->first();
                if ($course) {
                    $query->whereHas('lesson', function ($q) use ($course) {
                        $q->where('course_id', $course->id);
                    });
                }
            }

            // Filter by completion status
            if ($request->has('completed') && $request->completed !== '') {
                $query->where('completed', filter_var($request->completed, FILTER_VALIDATE_BOOLEAN));
            }

            // Filter by date range
            if ($request->has('date_from') && !empty($request->date_from)) {
                $query->whereDate('completed_at', '>=', $request->date_from);
            }
            if ($request->has('date_to') && !empty($request->date_to)) {
                $query->whereDate('completed_at', '<=', $request->date_to);
            }

            // Filter by recent completions (last 24 hours)
            if ($request->has('recent') && $request->recent) {
                $query->where('completed_at', '>=', now()->subDay());
            }

            // Ordering
            $orderBy = $request->get('order_by', 'completed_at');
            $orderDirection = $request->get('order_direction', 'desc');
            
            if (in_array($orderBy, ['completed_at', 'created_at', 'updated_at'])) {
                $query->orderBy($orderBy, $orderDirection);
            } elseif ($orderBy === 'lesson_position') {
                $query->join('lessons', 'lesson_progress.lesson_id', '=', 'lessons.id')
                    ->orderBy('lessons.position', $orderDirection)
                    ->select('lesson_progress.*');
            }

            // Pagination
            $perPage = $request->get('per_page', 15);
            $progressRecords = $perPage === 'all' 
                ? $query->get()
                : $query->paginate($perPage);

            // Add computed attributes if requested
            if ($request->has('include_computed') && $request->include_computed) {
                $progressRecords->transform(function ($progress) {
                    $progress->formatted_completed_at = $progress->formatted_completed_at;
                    $progress->human_completed_at = $progress->human_completed_at;
                    $progress->completion_duration = $progress->completion_duration;
                    $progress->is_recent_completion = $progress->isRecentCompletion();
                    return $progress;
                });
            }

            return response()->json([
                'success' => true,
                'data' => $progressRecords,
                'message' => 'Lesson progress records retrieved successfully.'
            ]);

        } catch (\Exception $e) {
            Log::error('LessonProgress index error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve lesson progress records.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Store a newly created lesson progress record.
     */
    public function store(Request $request)
    {
        DB::beginTransaction();

        try {
            $validator = Validator::make($request->all(), [
                'user_id' => 'required|exists:users,id',
                'lesson_id' => 'required|exists:lessons,id',
                'completed' => 'boolean',
                'completed_at' => 'nullable|date',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Check if user is enrolled in the course
            $lesson = Lesson::find($request->lesson_id);
            $isEnrolled = $lesson->course->hasUserEnrolled($request->user_id);
            
            if (!$isEnrolled && !$request->has('force')) {
                return response()->json([
                    'success' => false,
                    'message' => 'User is not enrolled in this course. Use force option to create progress anyway.'
                ], 403);
            }

            // Check if progress already exists
            $existingProgress = LessonProgress::where('user_id', $request->user_id)
                ->where('lesson_id', $request->lesson_id)
                ->first();

            if ($existingProgress) {
                return response()->json([
                    'success' => false,
                    'message' => 'Progress record already exists for this user and lesson. Use update instead.'
                ], 409);
            }

            $progress = LessonProgress::create($request->all());

            DB::commit();

            // Load relationships if needed
            if ($request->has('load_relations')) {
                $progress->load(['user', 'lesson', 'lesson.course']);
            }

            return response()->json([
                'success' => true,
                'data' => $progress,
                'message' => 'Lesson progress created successfully.'
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('LessonProgress store error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to create lesson progress.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Update or create lesson progress for current user.
     */
    public function updateProgress(Request $request)
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
                'lesson_id' => 'required|exists:lessons,id',
                'completed' => 'required|boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Check if user is enrolled in the course
            $lesson = Lesson::find($request->lesson_id);
            $isEnrolled = $lesson->course->hasUserEnrolled($user->id);
            
            if (!$isEnrolled) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not enrolled in this course.'
                ], 403);
            }

            $progress = LessonProgress::updateProgress($user->id, $request->lesson_id, $request->completed);

            DB::commit();

            $progress->load(['lesson']);

            return response()->json([
                'success' => true,
                'data' => $progress,
                'message' => $progress->completed ? 'Lesson marked as completed.' : 'Lesson marked as incomplete.'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Update progress error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to update lesson progress.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Display the specified lesson progress record.
     */
    public function show($id)
    {
        try {
            $query = LessonProgress::query();

            // Load relationships if requested
            if (request()->has('with')) {
                $with = explode(',', request()->with);
                $allowedRelations = ['user', 'lesson', 'lesson.course'];
                $query->with(array_intersect($with, $allowedRelations));
            }

            $progress = $query->findOrFail($id);

            // Add computed attributes
            $progress->formatted_completed_at = $progress->formatted_completed_at;
            $progress->human_completed_at = $progress->human_completed_at;
            $progress->completion_duration = $progress->completion_duration;
            $progress->is_recent_completion = $progress->isRecentCompletion();

            return response()->json([
                'success' => true,
                'data' => $progress,
                'message' => 'Lesson progress record retrieved successfully.'
            ]);

        } catch (\Exception $e) {
            Log::error('LessonProgress show error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Lesson progress record not found.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 404);
        }
    }

    /**
     * Update the specified lesson progress record.
     */
    public function update(Request $request, $id)
    {
        DB::beginTransaction();

        try {
            $progress = LessonProgress::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'completed' => 'boolean',
                'completed_at' => 'nullable|date',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Update progress
            $progress->update($request->all());

            DB::commit();

            $progress->refresh();

            return response()->json([
                'success' => true,
                'data' => $progress,
                'message' => 'Lesson progress updated successfully.'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('LessonProgress update error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to update lesson progress.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Mark lesson progress as completed.
     */
    public function markAsCompleted($id)
    {
        DB::beginTransaction();

        try {
            $progress = LessonProgress::findOrFail($id);

            $success = $progress->markAsCompleted();

            if (!$success) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to mark as completed.'
                ], 400);
            }

            DB::commit();

            $progress->refresh();

            return response()->json([
                'success' => true,
                'data' => $progress,
                'message' => 'Lesson progress marked as completed.'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Mark as completed error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to mark as completed.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Mark lesson progress as incomplete.
     */
    public function markAsIncomplete($id)
    {
        DB::beginTransaction();

        try {
            $progress = LessonProgress::findOrFail($id);

            $success = $progress->markAsIncomplete();

            if (!$success) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to mark as incomplete.'
                ], 400);
            }

            DB::commit();

            $progress->refresh();

            return response()->json([
                'success' => true,
                'data' => $progress,
                'message' => 'Lesson progress marked as incomplete.'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Mark as incomplete error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to mark as incomplete.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Toggle completion status.
     */
    public function toggleCompletion($id)
    {
        DB::beginTransaction();

        try {
            $progress = LessonProgress::findOrFail($id);

            $success = $progress->toggleCompletion();

            if (!$success) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to toggle completion status.'
                ], 400);
            }

            DB::commit();

            $progress->refresh();
            $status = $progress->completed ? 'completed' : 'incomplete';

            return response()->json([
                'success' => true,
                'data' => $progress,
                'message' => 'Lesson progress marked as ' . $status . '.'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Toggle completion error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to toggle completion status.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Remove the specified lesson progress record.
     */
    public function destroy($id)
    {
        DB::beginTransaction();

        try {
            $progress = LessonProgress::findOrFail($id);
            $progress->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Lesson progress deleted successfully.'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('LessonProgress destroy error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete lesson progress.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Get course progress for a user.
     */
    public function courseProgress(Request $request)
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

            $progress = LessonProgress::getCourseProgress($userId, $courseId);

            // Get individual lesson progress if requested
            $lessonProgress = [];
            if ($request->has('include_lessons') && $request->include_lessons) {
                $lessons = Lesson::where('course_id', $courseId)
                    ->orderBy('position')
                    ->get();

                foreach ($lessons as $lesson) {
                    $lessonProgressRecord = LessonProgress::where('user_id', $userId)
                        ->where('lesson_id', $lesson->id)
                        ->first();

                    $lessonProgress[] = [
                        'lesson' => $lesson->only(['id', 'title', 'position']),
                        'progress' => $lessonProgressRecord ? [
                            'id' => $lessonProgressRecord->id,
                            'completed' => $lessonProgressRecord->completed,
                            'completed_at' => $lessonProgressRecord->completed_at,
                            'formatted_completed_at' => $lessonProgressRecord->formatted_completed_at,
                        ] : null,
                    ];
                }
            }

            // Get course details
            $course = Course::find($courseId);

            return response()->json([
                'success' => true,
                'data' => [
                    'user_id' => $userId,
                    'course' => $course->only(['id', 'title', 'slug']),
                    'progress' => $progress,
                    'lesson_progress' => $lessonProgress,
                    'next_lesson' => $request->has('include_next_lesson') 
                        ? LessonProgress::getNextIncompleteLesson($userId, $courseId)
                        : null,
                ],
                'message' => 'Course progress retrieved successfully.'
            ]);

        } catch (\Exception $e) {
            Log::error('Course progress error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve course progress.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Get course progress for current user.
     */
    public function selfCourseProgress(Request $request)
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

            // Check if user is enrolled
            $isEnrolled = Course::find($courseId)->hasUserEnrolled($user->id);
            if (!$isEnrolled) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not enrolled in this course.'
                ], 403);
            }

            $progress = LessonProgress::getCourseProgress($user->id, $courseId);

            // Get individual lesson progress
            $lessons = Lesson::where('course_id', $courseId)
                ->orderBy('position')
                ->get();

            $lessonProgress = [];
            foreach ($lessons as $lesson) {
                $lessonProgressRecord = LessonProgress::where('user_id', $user->id)
                    ->where('lesson_id', $lesson->id)
                    ->first();

                $lessonProgress[] = [
                    'lesson' => $lesson->only(['id', 'title', 'position', 'has_video', 'has_content']),
                    'progress' => $lessonProgressRecord ? [
                        'id' => $lessonProgressRecord->id,
                        'completed' => $lessonProgressRecord->completed,
                        'completed_at' => $lessonProgressRecord->completed_at,
                        'formatted_completed_at' => $lessonProgressRecord->formatted_completed_at,
                        'human_completed_at' => $lessonProgressRecord->human_completed_at,
                        'is_recent_completion' => $lessonProgressRecord->isRecentCompletion(),
                    ] : null,
                    'is_completed' => $lessonProgressRecord ? $lessonProgressRecord->completed : false,
                ];
            }

            // Get next incomplete lesson
            $nextLesson = LessonProgress::getNextIncompleteLesson($user->id, $courseId);

            // Get course details
            $course = Course::find($courseId);

            return response()->json([
                'success' => true,
                'data' => [
                    'course' => $course->only(['id', 'title', 'slug', 'thumbnail_url', 'level_name']),
                    'progress' => $progress,
                    'lesson_progress' => $lessonProgress,
                    'next_lesson' => $nextLesson,
                    'is_enrolled' => true,
                ],
                'message' => 'Course progress retrieved successfully.'
            ]);

        } catch (\Exception $e) {
            Log::error('Self course progress error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve course progress.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Get user's progress across all enrolled courses.
     */
    public function userOverallProgress(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'user_id' => 'required_without:user_email|exists:users,id',
                'user_email' => 'required_without:user_id|email|exists:users,email',
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

            $user = User::find($userId);
            $enrollments = $user->enrollments()->with('course')->get();

            $overallProgress = [
                'total_courses' => $enrollments->count(),
                'completed_courses' => 0,
                'in_progress_courses' => 0,
                'not_started_courses' => 0,
                'total_lessons' => 0,
                'completed_lessons' => 0,
                'courses' => [],
            ];

            foreach ($enrollments as $enrollment) {
                $course = $enrollment->course;
                $progress = LessonProgress::getCourseProgress($userId, $course->id);

                $courseProgress = [
                    'course' => $course->only(['id', 'title', 'slug', 'thumbnail_url', 'level_name']),
                    'progress' => $progress,
                    'status' => $this->getCourseStatus($progress),
                ];

                $overallProgress['total_lessons'] += $progress['total'];
                $overallProgress['completed_lessons'] += $progress['completed'];

                if ($progress['percentage'] >= 100) {
                    $overallProgress['completed_courses']++;
                } elseif ($progress['percentage'] > 0) {
                    $overallProgress['in_progress_courses']++;
                } else {
                    $overallProgress['not_started_courses']++;
                }

                $overallProgress['courses'][] = $courseProgress;
            }

            // Calculate overall percentage
            $overallProgress['overall_percentage'] = $overallProgress['total_lessons'] > 0 
                ? round(($overallProgress['completed_lessons'] / $overallProgress['total_lessons']) * 100, 2)
                : 0;

            return response()->json([
                'success' => true,
                'data' => [
                    'user' => $user->only(['id', 'name', 'email']),
                    'overall_progress' => $overallProgress,
                ],
                'message' => 'User overall progress retrieved successfully.'
            ]);

        } catch (\Exception $e) {
            Log::error('User overall progress error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve user overall progress.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Get current user's progress across all enrolled courses.
     */
    public function selfOverallProgress(Request $request)
    {
        try {
            $user = auth()->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated.'
                ], 401);
            }

            $enrollments = $user->enrollments()
                ->with(['course' => function ($query) {
                    $query->withCount('lessons');
                }])
                ->get();

            $overallProgress = [
                'total_courses' => $enrollments->count(),
                'completed_courses' => 0,
                'in_progress_courses' => 0,
                'not_started_courses' => 0,
                'total_lessons' => 0,
                'completed_lessons' => 0,
                'courses' => [],
            ];

            foreach ($enrollments as $enrollment) {
                $course = $enrollment->course;
                $progress = LessonProgress::getCourseProgress($user->id, $course->id);

                $courseProgress = [
                    'course' => $course->only(['id', 'title', 'slug', 'thumbnail_url', 'level_name', 'lessons_count']),
                    'progress' => $progress,
                    'status' => $this->getCourseStatus($progress),
                    'last_activity' => $this->getLastActivity($user->id, $course->id),
                ];

                $overallProgress['total_lessons'] += $progress['total'];
                $overallProgress['completed_lessons'] += $progress['completed'];

                if ($progress['percentage'] >= 100) {
                    $overallProgress['completed_courses']++;
                } elseif ($progress['percentage'] > 0) {
                    $overallProgress['in_progress_courses']++;
                } else {
                    $overallProgress['not_started_courses']++;
                }

                $overallProgress['courses'][] = $courseProgress;
            }

            // Sort courses by progress percentage (descending)
            usort($overallProgress['courses'], function ($a, $b) {
                return $b['progress']['percentage'] <=> $a['progress']['percentage'];
            });

            // Calculate overall percentage
            $overallProgress['overall_percentage'] = $overallProgress['total_lessons'] > 0 
                ? round(($overallProgress['completed_lessons'] / $overallProgress['total_lessons']) * 100, 2)
                : 0;

            // Get recent activity
            $recentActivity = LessonProgress::where('user_id', $user->id)
                ->where('completed', true)
                ->with('lesson.course')
                ->orderBy('completed_at', 'desc')
                ->limit(10)
                ->get()
                ->map(function ($progress) {
                    return [
                        'lesson' => $progress->lesson->only(['id', 'title', 'course_id']),
                        'course' => $progress->lesson->course->only(['id', 'title', 'slug']),
                        'completed_at' => $progress->completed_at,
                        'human_completed_at' => $progress->human_completed_at,
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => [
                    'user' => $user->only(['id', 'name', 'email']),
                    'overall_progress' => $overallProgress,
                    'recent_activity' => $recentActivity,
                ],
                'message' => 'Your overall progress retrieved successfully.'
            ]);

        } catch (\Exception $e) {
            Log::error('Self overall progress error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve your overall progress.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Get progress statistics.
     */
    public function statistics(Request $request)
    {
        try {
            // Overall statistics
            $totalProgressRecords = LessonProgress::count();
            $completedProgressRecords = LessonProgress::completed()->count();
            $incompleteProgressRecords = LessonProgress::incomplete()->count();

            // Recent completions (last 7 days)
            $recentCompletions = LessonProgress::where('completed', true)
                ->where('completed_at', '>=', now()->subDays(7))
                ->count();

            // Daily completion trend
            $dailyTrend = LessonProgress::select(
                    DB::raw('DATE(completed_at) as date'),
                    DB::raw('count(*) as count')
                )
                ->where('completed', true)
                ->where('completed_at', '>=', now()->subDays(30))
                ->groupBy('date')
                ->orderBy('date')
                ->get();

            // Top courses by completion
            $topCourses = Course::withCount(['lessonProgress as completed_count' => function ($query) {
                    $query->where('completed', true);
                }])
                ->has('lessonProgress')
                ->orderBy('completed_count', 'desc')
                ->limit(10)
                ->get();

            // Top users by completion
            $topUsers = User::withCount(['lessonProgress as completed_count' => function ($query) {
                    $query->where('completed', true);
                }])
                ->has('lessonProgress')
                ->orderBy('completed_count', 'desc')
                ->limit(10)
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'total_progress_records' => $totalProgressRecords,
                    'completed_records' => $completedProgressRecords,
                    'incomplete_records' => $incompleteProgressRecords,
                    'completion_rate' => $totalProgressRecords > 0 
                        ? round(($completedProgressRecords / $totalProgressRecords) * 100, 2)
                        : 0,
                    'recent_completions' => $recentCompletions,
                    'daily_trend' => $dailyTrend,
                    'top_courses' => $topCourses,
                    'top_users' => $topUsers,
                ],
                'message' => 'Progress statistics retrieved successfully.'
            ]);

        } catch (\Exception $e) {
            Log::error('Progress statistics error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve progress statistics.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Get lesson completion statistics for a course.
     */
    public function lessonCompletionStats($courseId)
    {
        try {
            $course = Course::findOrFail($courseId);

            $lessons = $course->lessons()
                ->withCount(['progress as completed_count' => function ($query) {
                    $query->where('completed', true);
                }])
                ->withCount('progress')
                ->orderBy('position')
                ->get();

            $totalEnrollments = $course->enrollments()->count();

            $stats = $lessons->map(function ($lesson) use ($totalEnrollments) {
                $completionPercentage = $totalEnrollments > 0 
                    ? ($lesson->completed_count / $totalEnrollments) * 100
                    : 0;

                return [
                    'lesson' => $lesson->only(['id', 'title', 'position']),
                    'total_progress_records' => $lesson->progress_count,
                    'completed_count' => $lesson->completed_count,
                    'total_enrollments' => $totalEnrollments,
                    'completion_rate' => round($completionPercentage, 2),
                    'has_video' => $lesson->hasVideo(),
                    'has_content' => $lesson->hasContent(),
                    'reading_time' => $lesson->reading_time,
                ];
            });

            // Overall course completion
            $totalCompletedProgress = $stats->sum('completed_count');
            $totalPossibleProgress = $totalEnrollments * $lessons->count();
            $overallCompletionRate = $totalPossibleProgress > 0 
                ? ($totalCompletedProgress / $totalPossibleProgress) * 100
                : 0;

            return response()->json([
                'success' => true,
                'data' => [
                    'course' => $course->only(['id', 'title', 'slug']),
                    'stats' => $stats,
                    'summary' => [
                        'total_lessons' => $lessons->count(),
                        'total_enrollments' => $totalEnrollments,
                        'total_completed_progress' => $totalCompletedProgress,
                        'overall_completion_rate' => round($overallCompletionRate, 2),
                        'average_completion_per_lesson' => round($stats->avg('completion_rate'), 2),
                    ],
                ],
                'message' => 'Lesson completion statistics retrieved successfully.'
            ]);

        } catch (\Exception $e) {
            Log::error('Lesson completion stats error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve lesson completion statistics.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Bulk update lesson progress.
     */
    public function bulkUpdate(Request $request)
    {
        DB::beginTransaction();

        try {
            $validator = Validator::make($request->all(), [
                'user_id' => 'required|exists:users,id',
                'lesson_ids' => 'required|array',
                'lesson_ids.*' => 'exists:lessons,id',
                'completed' => 'required|boolean',
                'skip_existing' => 'boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors' => $validator->errors()
                ], 422);
            }

            $userId = $request->user_id;
            $updatedCount = 0;
            $skippedCount = 0;
            $failedCount = 0;
            $errors = [];

            foreach ($request->lesson_ids as $lessonId) {
                try {
                    // Check if user is enrolled in the course
                    $lesson = Lesson::find($lessonId);
                    $isEnrolled = $lesson->course->hasUserEnrolled($userId);
                    
                    if (!$isEnrolled && !$request->has('force')) {
                        $failedCount++;
                        $errors[] = "User is not enrolled in course for lesson {$lessonId}.";
                        continue;
                    }

                    // Check if progress already exists
                    $existingProgress = LessonProgress::where('user_id', $userId)
                        ->where('lesson_id', $lessonId)
                        ->first();

                    if ($existingProgress && $request->skip_existing) {
                        $skippedCount++;
                        continue;
                    }

                    // Update or create progress
                    LessonProgress::updateProgress($userId, $lessonId, $request->completed);
                    $updatedCount++;
                } catch (\Exception $e) {
                    $failedCount++;
                    $errors[] = "Failed to update progress for lesson {$lessonId}: " . $e->getMessage();
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Bulk update completed.',
                'data' => [
                    'user_id' => $userId,
                    'updated_count' => $updatedCount,
                    'skipped_count' => $skippedCount,
                    'failed_count' => $failedCount,
                    'errors' => $errors,
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Bulk update progress error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to perform bulk update.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Bulk delete lesson progress.
     */
    public function bulkDestroy(Request $request)
    {
        DB::beginTransaction();

        try {
            $validator = Validator::make($request->all(), [
                'ids' => 'required|array',
                'ids.*' => 'exists:lesson_progress,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors' => $validator->errors()
                ], 422);
            }

            $deletedCount = LessonProgress::whereIn('id', $request->ids)->delete();

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
            Log::error('Bulk delete progress error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to perform bulk delete.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Helper method to determine course status based on progress.
     */
    private function getCourseStatus(array $progress): string
    {
        if ($progress['percentage'] >= 100) {
            return 'completed';
        } elseif ($progress['percentage'] > 0) {
            return 'in_progress';
        } else {
            return 'not_started';
        }
    }

    /**
     * Helper method to get last activity for a user in a course.
     */
    private function getLastActivity(int $userId, int $courseId): ?array
    {
        $lastProgress = LessonProgress::where('user_id', $userId)
            ->whereHas('lesson', function ($q) use ($courseId) {
                $q->where('course_id', $courseId);
            })
            ->where('completed', true)
            ->orderBy('completed_at', 'desc')
            ->first();

        if (!$lastProgress) {
            return null;
        }

        return [
            'lesson_title' => $lastProgress->lesson->title,
            'completed_at' => $lastProgress->completed_at,
            'human_completed_at' => $lastProgress->human_completed_at,
        ];
    }
}