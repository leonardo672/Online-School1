<?php

namespace App\Http\Controllers;

use App\Models\Lesson;
use App\Models\Course;
use App\Models\LessonProgress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class LessonController extends Controller
{
    /**
     * Display a listing of lessons.
     */
    public function index(Request $request)
    {
        try {
            $query = Lesson::query();

            // Eager load relationships if requested
            if ($request->has('with')) {
                $with = explode(',', $request->with);
                $allowedRelations = ['course', 'progress'];
                $query->with(array_intersect($with, $allowedRelations));
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

            // Filter by lesson type
            if ($request->has('type') && !empty($request->type)) {
                $query->where(function ($q) use ($request) {
                    if ($request->type === 'video') {
                        $q->whereNotNull('video_url');
                    } elseif ($request->type === 'text') {
                        $q->whereNotNull('content')->whereNull('video_url');
                    } elseif ($request->type === 'video_and_text') {
                        $q->whereNotNull('video_url')->whereNotNull('content');
                    }
                });
            }

            // Search functionality
            if ($request->has('search') && !empty($request->search)) {
                $searchTerm = $request->search;
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('title', 'like', '%' . $searchTerm . '%')
                      ->orWhere('content', 'like', '%' . $searchTerm . '%');
                });
            }

            // Ordering (default by position)
            $orderBy = $request->get('order_by', 'position');
            $orderDirection = $request->get('order_direction', 'asc');
            
            if (in_array($orderBy, ['position', 'title', 'created_at', 'updated_at'])) {
                $query->orderBy($orderBy, $orderDirection);
            }

            // Include counts if requested
            $withCounts = $request->get('with_counts', false);
            if ($withCounts) {
                $query->withCount('progress');
            }

            // Pagination or all
            $perPage = $request->get('per_page', 15);
            $lessons = $perPage === 'all' 
                ? $query->get()
                : $query->paginate($perPage);

            // Add computed attributes if requested
            if ($request->has('include_computed') && $request->include_computed) {
                $lessons->transform(function ($lesson) {
                    $lesson->reading_time = $lesson->reading_time;
                    $lesson->type = $lesson->type;
                    $lesson->has_video = $lesson->hasVideo();
                    $lesson->has_content = $lesson->hasContent();
                    return $lesson;
                });
            }

            return response()->json([
                'success' => true,
                'data' => $lessons,
                'message' => 'Lessons retrieved successfully.'
            ]);

        } catch (\Exception $e) {
            Log::error('Lesson index error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve lessons.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Store a newly created lesson.
     */
    public function store(Request $request)
    {
        DB::beginTransaction();

        try {
            $validator = Validator::make($request->all(), [
                'course_id' => 'required|exists:courses,id',
                'title' => 'required|string|max:255',
                'content' => 'nullable|string',
                'video_url' => 'nullable|url|max:500',
                'position' => 'nullable|integer|min:1',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors' => $validator->errors()
                ], 422);
            }

            // If position is not provided, get the next available position
            if (!$request->has('position') || empty($request->position)) {
                $maxPosition = Lesson::where('course_id', $request->course_id)
                    ->max('position');
                $request->merge(['position' => ($maxPosition ?: 0) + 1]);
            } else {
                // Adjust positions if inserting at a specific position
                $existingPosition = Lesson::where('course_id', $request->course_id)
                    ->where('position', '>=', $request->position)
                    ->exists();
                
                if ($existingPosition) {
                    Lesson::where('course_id', $request->course_id)
                        ->where('position', '>=', $request->position)
                        ->increment('position');
                }
            }

            $lesson = Lesson::create($request->all());

            DB::commit();

            // Load relationships if needed
            if ($request->has('load_relations')) {
                $lesson->load(['course']);
            }

            return response()->json([
                'success' => true,
                'data' => $lesson,
                'message' => 'Lesson created successfully.'
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Lesson store error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to create lesson.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Display the specified lesson.
     */
    public function show($id)
    {
        try {
            $query = Lesson::query();

            // Load relationships if requested
            if (request()->has('with')) {
                $with = explode(',', request()->with);
                $allowedRelations = ['course', 'progress', 'completedByUsers'];
                $query->with(array_intersect($with, $allowedRelations));
            }

            // Include counts
            $query->withCount('progress');

            $lesson = $query->findOrFail($id);

            // Include computed attributes if requested
            if (request()->has('include_computed') && request()->include_computed) {
                $lesson->reading_time = $lesson->reading_time;
                $lesson->formatted_reading_time = $lesson->formatted_reading_time;
                $lesson->type = $lesson->type;
                $lesson->video_embed_url = $lesson->video_embed_url;
                $lesson->has_embeddable_video = $lesson->hasEmbeddableVideo();
                $lesson->lesson_number = $lesson->lesson_number;
                $lesson->total_lessons = $lesson->total_lessons;
                $lesson->progress_percentage = $lesson->progress_percentage;
            }

            // Include next and previous lessons if requested
            if (request()->has('include_navigation') && request()->include_navigation) {
                $lesson->next_lesson = $lesson->nextLesson();
                $lesson->previous_lesson = $lesson->previousLesson();
            }

            return response()->json([
                'success' => true,
                'data' => $lesson,
                'message' => 'Lesson retrieved successfully.'
            ]);

        } catch (\Exception $e) {
            Log::error('Lesson show error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Lesson not found.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 404);
        }
    }

    /**
     * Update the specified lesson.
     */
    public function update(Request $request, $id)
    {
        DB::beginTransaction();

        try {
            $lesson = Lesson::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'course_id' => 'nullable|exists:courses,id',
                'title' => 'nullable|string|max:255',
                'content' => 'nullable|string',
                'video_url' => 'nullable|url|max:500',
                'position' => 'nullable|integer|min:1',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors' => $validator->errors()
                ], 422);
            }

            $data = $request->all();

            // Handle position change
            if ($request->has('position') && $request->position != $lesson->position) {
                $courseId = $request->has('course_id') ? $request->course_id : $lesson->course_id;
                $maxPosition = Lesson::where('course_id', $courseId)->max('position');
                $newPosition = min(max(1, $request->position), $maxPosition);
                
                $lesson->moveToPosition($newPosition);
                unset($data['position']); // Position already updated
            }

            // Handle course change
            if ($request->has('course_id') && $request->course_id != $lesson->course_id) {
                // Reorder positions in old course
                Lesson::where('course_id', $lesson->course_id)
                    ->where('position', '>', $lesson->position)
                    ->decrement('position');
                
                // Set position in new course
                $maxPosition = Lesson::where('course_id', $request->course_id)
                    ->max('position');
                $data['position'] = ($maxPosition ?: 0) + 1;
            }

            // Update lesson
            $lesson->update($data);

            DB::commit();

            // Refresh lesson to get updated attributes
            $lesson->refresh();

            return response()->json([
                'success' => true,
                'data' => $lesson,
                'message' => 'Lesson updated successfully.'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Lesson update error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to update lesson.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Remove the specified lesson.
     */
    public function destroy($id)
    {
        DB::beginTransaction();

        try {
            $lesson = Lesson::findOrFail($id);

            // Check if lesson has progress records
            if ($lesson->progress()->exists() && !request()->has('force')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete lesson because it has progress records. Use force option to delete anyway.',
                    'progress_count' => $lesson->progress()->count()
                ], 422);
            }

            // Delete associated progress records
            $lesson->progress()->delete();

            $lesson->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Lesson deleted successfully.'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Lesson destroy error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete lesson.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Reorder lessons in a course.
     */
    public function reorder(Request $request, $courseId)
    {
        DB::beginTransaction();

        try {
            $course = Course::findOrFail($courseId);

            $validator = Validator::make($request->all(), [
                'order' => 'required|array',
                'order.*' => 'required|integer|exists:lessons,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Verify all lessons belong to the course
            $lessonIds = $request->order;
            $lessonCount = Lesson::where('course_id', $courseId)
                ->whereIn('id', $lessonIds)
                ->count();

            if ($lessonCount !== count($lessonIds)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Some lessons do not belong to the specified course.'
                ], 422);
            }

            // Reorder lessons
            foreach ($lessonIds as $position => $lessonId) {
                Lesson::where('id', $lessonId)
                    ->update(['position' => $position + 1]);
            }

            DB::commit();

            // Get updated lessons
            $lessons = Lesson::where('course_id', $courseId)
                ->ordered()
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'course' => $course->only(['id', 'title', 'slug']),
                    'lessons' => $lessons,
                ],
                'message' => 'Lessons reordered successfully.'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Lesson reorder error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to reorder lessons.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Move a lesson to a new position.
     */
    public function move(Request $request, $id)
    {
        DB::beginTransaction();

        try {
            $lesson = Lesson::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'new_position' => 'required|integer|min:1',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors' => $validator->errors()
                ], 422);
            }

            $maxPosition = Lesson::where('course_id', $lesson->course_id)->max('position');
            $newPosition = min(max(1, $request->new_position), $maxPosition);

            $success = $lesson->moveToPosition($newPosition);

            if (!$success) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to move lesson.'
                ], 400);
            }

            DB::commit();

            $lesson->refresh();

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $lesson->id,
                    'title' => $lesson->title,
                    'old_position' => $request->old_position ?? 'unknown',
                    'new_position' => $lesson->position,
                    'course_id' => $lesson->course_id,
                ],
                'message' => 'Lesson moved successfully.'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Lesson move error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to move lesson.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Mark lesson as completed for current user.
     */
    public function markAsCompleted(Request $request, $id)
    {
        try {
            $lesson = Lesson::findOrFail($id);
            $user = auth()->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated.'
                ], 401);
            }

            // Check if user is enrolled in the course
            if (!$lesson->course->hasUserEnrolled($user->id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not enrolled in this course.'
                ], 403);
            }

            $progress = $lesson->markAsCompletedForUser($user->id);

            return response()->json([
                'success' => true,
                'data' => $progress,
                'message' => 'Lesson marked as completed.'
            ]);

        } catch (\Exception $e) {
            Log::error('Lesson mark as completed error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to mark lesson as completed.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Mark lesson as incomplete for current user.
     */
    public function markAsIncomplete(Request $request, $id)
    {
        try {
            $lesson = Lesson::findOrFail($id);
            $user = auth()->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated.'
                ], 401);
            }

            $success = $lesson->markAsIncompleteForUser($user->id);

            if (!$success) {
                return response()->json([
                    'success' => false,
                    'message' => 'No progress record found for this lesson.'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Lesson marked as incomplete.'
            ]);

        } catch (\Exception $e) {
            Log::error('Lesson mark as incomplete error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to mark lesson as incomplete.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Mark lesson as completed for a specific user (admin only).
     */
    public function markAsCompletedForUser(Request $request, $id)
    {
        try {
            $lesson = Lesson::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'user_id' => 'required|exists:users,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors' => $validator->errors()
                ], 422);
            }

            $progress = $lesson->markAsCompletedForUser($request->user_id);

            return response()->json([
                'success' => true,
                'data' => $progress,
                'message' => 'Lesson marked as completed for user.'
            ]);

        } catch (\Exception $e) {
            Log::error('Lesson mark as completed for user error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to mark lesson as completed for user.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Mark lesson as incomplete for a specific user (admin only).
     */
    public function markAsIncompleteForUser(Request $request, $id)
    {
        try {
            $lesson = Lesson::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'user_id' => 'required|exists:users,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors' => $validator->errors()
                ], 422);
            }

            $success = $lesson->markAsIncompleteForUser($request->user_id);

            if (!$success) {
                return response()->json([
                    'success' => false,
                    'message' => 'No progress record found for this lesson and user.'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Lesson marked as incomplete for user.'
            ]);

        } catch (\Exception $e) {
            Log::error('Lesson mark as incomplete for user error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to mark lesson as incomplete for user.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Check if lesson is completed by current user.
     */
    public function checkCompletion($id)
    {
        try {
            $lesson = Lesson::findOrFail($id);
            $user = auth()->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated.'
                ], 401);
            }

            $isCompleted = $lesson->isCompletedByUser($user->id);
            $progress = null;

            if ($isCompleted) {
                $progress = $lesson->progress()
                    ->where('user_id', $user->id)
                    ->first();
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'is_completed' => $isCompleted,
                    'progress' => $progress,
                    'lesson' => $lesson->only(['id', 'title', 'course_id']),
                ],
                'message' => 'Completion status retrieved.'
            ]);

        } catch (\Exception $e) {
            Log::error('Lesson check completion error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to check completion status.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Get lesson navigation for current user.
     */
    public function navigation($id)
    {
        try {
            $lesson = Lesson::findOrFail($id);
            $user = auth()->user();

            $navigation = [
                'current' => $lesson->only(['id', 'title', 'position', 'course_id']),
                'next' => null,
                'previous' => null,
                'course' => $lesson->course->only(['id', 'title', 'slug']),
            ];

            if ($user) {
                $navigation['next'] = $lesson->getNextIncompleteForUser($user->id);
                $navigation['previous'] = $lesson->getPreviousIncompleteForUser($user->id);
                $navigation['is_completed'] = $lesson->isCompletedByUser($user->id);
            } else {
                $navigation['next'] = $lesson->nextLesson();
                $navigation['previous'] = $lesson->previousLesson();
            }

            return response()->json([
                'success' => true,
                'data' => $navigation,
                'message' => 'Lesson navigation retrieved.'
            ]);

        } catch (\Exception $e) {
            Log::error('Lesson navigation error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to get lesson navigation.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Get lesson progress statistics.
     */
    public function progressStats($id)
    {
        try {
            $lesson = Lesson::findOrFail($id);

            $completionCount = $lesson->completion_count;
            $enrolledUsersCount = $lesson->course->enrollments()->count();
            $completionPercentage = $lesson->getCompletionPercentage($enrolledUsersCount);

            // Get recent completions
            $recentCompletions = $lesson->progress()
                ->where('completed', true)
                ->with('user')
                ->orderBy('completed_at', 'desc')
                ->limit(10)
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'lesson' => $lesson->only(['id', 'title', 'course_id']),
                    'completion_count' => $completionCount,
                    'enrolled_users_count' => $enrolledUsersCount,
                    'completion_percentage' => round($completionPercentage, 2),
                    'recent_completions' => $recentCompletions,
                    'course' => $lesson->course->only(['id', 'title', 'slug']),
                ],
                'message' => 'Lesson progress statistics retrieved.'
            ]);

        } catch (\Exception $e) {
            Log::error('Lesson progress stats error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to get lesson progress statistics.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Get users who completed the lesson.
     */
    public function completedBy($id)
    {
        try {
            $lesson = Lesson::findOrFail($id);

            $query = $lesson->completedByUsers();

            // Pagination
            $perPage = request()->get('per_page', 15);
            $users = $perPage === 'all' 
                ? $query->get()
                : $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => [
                    'lesson' => $lesson->only(['id', 'title', 'course_id']),
                    'users' => $users,
                    'total_count' => $lesson->completion_count,
                ],
                'message' => 'Users who completed the lesson retrieved.'
            ]);

        } catch (\Exception $e) {
            Log::error('Lesson completed by error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to get users who completed the lesson.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Bulk create lessons for a course.
     */
    public function bulkCreate(Request $request)
    {
        DB::beginTransaction();

        try {
            $validator = Validator::make($request->all(), [
                'course_id' => 'required|exists:courses,id',
                'lessons' => 'required|array|min:1',
                'lessons.*.title' => 'required|string|max:255',
                'lessons.*.content' => 'nullable|string',
                'lessons.*.video_url' => 'nullable|url|max:500',
                'lessons.*.position' => 'nullable|integer|min:1',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors' => $validator->errors()
                ], 422);
            }

            $courseId = $request->course_id;
            $createdLessons = [];
            $startPosition = Lesson::where('course_id', $courseId)->max('position') ?? 0;

            foreach ($request->lessons as $index => $lessonData) {
                // Set position if not provided
                if (!isset($lessonData['position']) || empty($lessonData['position'])) {
                    $lessonData['position'] = $startPosition + $index + 1;
                }

                $lessonData['course_id'] = $courseId;
                $lesson = Lesson::create($lessonData);
                $createdLessons[] = $lesson;
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => [
                    'created_count' => count($createdLessons),
                    'lessons' => $createdLessons,
                    'course' => Course::find($courseId)->only(['id', 'title', 'slug']),
                ],
                'message' => count($createdLessons) . ' lesson(s) created successfully.'
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Lesson bulk create error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to create lessons.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Bulk delete lessons.
     */
    public function bulkDestroy(Request $request)
    {
        DB::beginTransaction();

        try {
            $validator = Validator::make($request->all(), [
                'ids' => 'required|array',
                'ids.*' => 'exists:lessons,id',
                'force' => 'boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors' => $validator->errors()
                ], 422);
            }

            $lessons = Lesson::whereIn('id', $request->ids)->get();
            $deletedCount = 0;
            $failedCount = 0;
            $errors = [];

            foreach ($lessons as $lesson) {
                try {
                    if ($request->force || !$lesson->progress()->exists()) {
                        // Delete progress records
                        $lesson->progress()->delete();
                        $lesson->delete();
                        $deletedCount++;
                    } else {
                        $failedCount++;
                        $errors[] = "Lesson '{$lesson->title}' has progress records and cannot be deleted.";
                    }
                } catch (\Exception $e) {
                    $failedCount++;
                    $errors[] = "Failed to delete lesson '{$lesson->title}': " . $e->getMessage();
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
            Log::error('Lesson bulk destroy error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to perform bulk delete.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Get lessons by course slug.
     */
    public function byCourseSlug($slug)
    {
        try {
            $course = Course::where('slug', $slug)->firstOrFail();

            $query = $course->lessons()->ordered();

            // Eager load relationships if requested
            if (request()->has('with')) {
                $with = explode(',', request()->with);
                $allowedRelations = ['progress'];
                $query->with(array_intersect($with, $allowedRelations));
            }

            // Include progress for current user if authenticated
            $user = auth()->user();
            if ($user && request()->has('include_user_progress')) {
                $query->with(['progress' => function ($query) use ($user) {
                    $query->where('user_id', $user->id);
                }]);
            }

            $lessons = $query->get();

            // Add completion status for current user
            if ($user && request()->has('include_completion_status')) {
                $lessons->transform(function ($lesson) use ($user) {
                    $lesson->is_completed = $lesson->isCompletedByUser($user->id);
                    return $lesson;
                });
            }

            // Add computed attributes if requested
            if (request()->has('include_computed')) {
                $lessons->transform(function ($lesson) {
                    $lesson->reading_time = $lesson->reading_time;
                    $lesson->type = $lesson->type;
                    $lesson->lesson_number = $lesson->lesson_number;
                    $lesson->total_lessons = $lesson->total_lessons;
                    return $lesson;
                });
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'course' => $course->only(['id', 'title', 'slug']),
                    'lessons' => $lessons,
                    'total_count' => $lessons->count(),
                ],
                'message' => 'Lessons for course retrieved successfully.'
            ]);

        } catch (\Exception $e) {
            Log::error('Lessons by course slug error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve lessons for course.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Get lesson analytics for a course.
     */
    public function analytics($courseId)
    {
        try {
            $course = Course::findOrFail($courseId);

            $lessons = $course->lessons()
                ->withCount(['progress as completed_count' => function ($query) {
                    $query->where('completed', true);
                }])
                ->ordered()
                ->get();

            $totalEnrollments = $course->enrollments()->count();
            $analytics = [];

            foreach ($lessons as $lesson) {
                $completionPercentage = $totalEnrollments > 0 
                    ? ($lesson->completed_count / $totalEnrollments) * 100 
                    : 0;

                $analytics[] = [
                    'lesson' => $lesson->only(['id', 'title', 'position']),
                    'completed_count' => $lesson->completed_count,
                    'enrollment_count' => $totalEnrollments,
                    'completion_percentage' => round($completionPercentage, 2),
                    'has_video' => $lesson->hasVideo(),
                    'has_content' => $lesson->hasContent(),
                    'reading_time' => $lesson->reading_time,
                ];
            }

            // Overall course completion statistics
            $totalCompletedProgress = DB::table('lesson_progress')
                ->join('lessons', 'lesson_progress.lesson_id', '=', 'lessons.id')
                ->where('lessons.course_id', $courseId)
                ->where('lesson_progress.completed', true)
                ->count();

            $totalPossibleProgress = $totalEnrollments * $lessons->count();
            $overallCompletionRate = $totalPossibleProgress > 0 
                ? ($totalCompletedProgress / $totalPossibleProgress) * 100 
                : 0;

            return response()->json([
                'success' => true,
                'data' => [
                    'course' => $course->only(['id', 'title', 'slug']),
                    'analytics' => $analytics,
                    'summary' => [
                        'total_lessons' => $lessons->count(),
                        'total_enrollments' => $totalEnrollments,
                        'total_completed_progress' => $totalCompletedProgress,
                        'overall_completion_rate' => round($overallCompletionRate, 2),
                        'average_completion_per_lesson' => $lessons->count() > 0 
                            ? round($lessons->avg('completed_count'), 2) 
                            : 0,
                    ],
                ],
                'message' => 'Lesson analytics retrieved successfully.'
            ]);

        } catch (\Exception $e) {
            Log::error('Lesson analytics error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve lesson analytics.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
}