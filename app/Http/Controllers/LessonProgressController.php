<?php

namespace App\Http\Controllers;

use App\Models\LessonProgress;
use App\Models\User;
use App\Models\Lesson;
use Illuminate\Http\Request;

class LessonProgressController extends Controller
{
    /**
     * Display a listing of the lesson progress.
     */
    public function index(Request $request)
    {
        // Build query with filters
        $query = LessonProgress::with(['user', 'lesson.course'])
            ->when($request->filled('user_id'), function ($q) use ($request) {
                $q->where('user_id', $request->user_id);
            })
            ->when($request->filled('lesson_id'), function ($q) use ($request) {
                $q->where('lesson_id', $request->lesson_id);
            })
            ->when($request->filled('status'), function ($q) use ($request) {
                if ($request->status === 'completed') {
                    $q->where('completed', true);
                } elseif ($request->status === 'incomplete') {
                    $q->where('completed', false);
                }
            })
            ->when($request->filled('search'), function ($q) use ($request) {
                $q->whereHas('user', function ($query) use ($request) {
                    $query->where('name', 'like', '%' . $request->search . '%')
                        ->orWhere('email', 'like', '%' . $request->search . '%');
                })->orWhereHas('lesson', function ($query) use ($request) {
                    $query->where('title', 'like', '%' . $request->search . '%');
                });
            })
            ->latest();

        // Get paginated results
        $lessonProgresses = $query->paginate(20)
            ->appends($request->query());

        // Get statistics
        $totalProgress = LessonProgress::count();
        $completedCount = LessonProgress::where('completed', true)->count();
        $incompleteCount = LessonProgress::where('completed', false)->count();
        
        // Latest completion timestamp
        $latestCompletion = LessonProgress::where('completed', true)
            ->latest('completed_at')
            ->value('completed_at');

        return view('lesson-progress.index', [
            'lessonProgresses' => $lessonProgresses,
            'users' => User::all(),
            'lessons' => Lesson::with('course')->get(),
            'lessonProgressesCount' => $totalProgress,
            'completedCount' => $completedCount,
            'incompleteCount' => $incompleteCount,
            'completionPercentage' => $totalProgress > 0 ? ($completedCount / $totalProgress) * 100 : 0,
            'latestCompletion' => $latestCompletion,
        ]);
    }

    /**
     * Show the form for creating a new lesson progress.
     */
    public function create()
    {
        $users = User::all();
        $lessons = Lesson::with('course')->get();
        
        return view('lesson-progress.create', compact('users', 'lessons'));
    }

    /**
     * Store a newly created lesson progress in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'lesson_id' => 'required|exists:lessons,id',
            'completed' => 'boolean',
        ]);

        LessonProgress::create([
            'user_id' => $request->user_id,
            'lesson_id' => $request->lesson_id,
            'completed' => $request->completed ?? false,
            'completed_at' => $request->completed ? now() : null,
        ]);

        return redirect()->route('lesson-progress.index')->with('success', 'Lesson progress created successfully!');
    }

    /**
     * Display the specified lesson progress.
     */
    public function show(string $id)
    {
        $progress = LessonProgress::with(['user', 'lesson.course'])->findOrFail($id);
        return view('lesson-progress.show', compact('progress'));
    }

    /**
     * Show the form for editing the specified lesson progress.
     */
    public function edit($id)
    {
        $progress = LessonProgress::findOrFail($id);
        $users = User::all();
        $lessons = Lesson::with('course')->get();
        
        return view('lesson-progress.edit', compact('progress', 'users', 'lessons'));
    }

    /**
     * Update the specified lesson progress in storage.
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'lesson_id' => 'required|exists:lessons,id',
            'completed' => 'boolean',
        ]);

        $progress = LessonProgress::findOrFail($id);
        $updateData = [
            'user_id' => $request->user_id,
            'lesson_id' => $request->lesson_id,
            'completed' => $request->completed ?? false,
        ];

        // Update completed_at if marking as completed
        if ($request->completed && !$progress->completed) {
            $updateData['completed_at'] = now();
        } elseif (!$request->completed && $progress->completed) {
            $updateData['completed_at'] = null;
        }

        $progress->update($updateData);

        return redirect()->route('lesson-progress.index')->with('success', 'Lesson progress updated successfully!');
    }

    /**
     * Remove the specified lesson progress from storage.
     */
    public function destroy(string $id)
    {
        $progress = LessonProgress::findOrFail($id);
        $progress->delete();

        return redirect()->route('lesson-progress.index')->with('success', 'Lesson progress deleted successfully.');
    }

    /**
     * Mark lesson as complete for authenticated user.
     */
    public function markComplete(Request $request, $lessonId)
    {
        $progress = LessonProgress::firstOrCreate([
            'user_id' => auth()->id(),
            'lesson_id' => $lessonId,
        ], [
            'completed' => false,
        ]);

        $progress->update([
            'completed' => true,
            'completed_at' => now(),
        ]);

        return back()->with('success', 'Lesson marked as complete!');
    }

    /**
     * Mark lesson as incomplete for authenticated user.
     */
    public function markIncomplete(Request $request, $lessonId)
    {
        $progress = LessonProgress::where('user_id', auth()->id())
            ->where('lesson_id', $lessonId)
            ->firstOrFail();

        $progress->update([
            'completed' => false,
            'completed_at' => null,
        ]);

        return back()->with('success', 'Lesson marked as incomplete!');
    }

    /**
     * Toggle completion status via AJAX
     */
    public function toggle(Request $request, $id)
    {
        $progress = LessonProgress::findOrFail($id);
        
        $completed = $request->input('completed');
        $progress->completed = $completed;
        $progress->completed_at = $completed ? now() : null;
        $progress->save();

        return response()->json([
            'success' => true,
            'message' => 'Progress updated successfully',
            'completed' => $progress->completed
        ]);
    }

    /**
     * Bulk complete progress records
     */
    public function bulkComplete(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:lesson_progress,id'
        ]);

        $updated = LessonProgress::whereIn('id', $request->ids)
            ->update([
                'completed' => true,
                'completed_at' => now()
            ]);

        return response()->json([
            'success' => true,
            'message' => "{$updated} records marked as complete",
            'updated' => $updated
        ]);
    }

    /**
     * Get quick stats via AJAX
     */
    public function stats()
    {
        $total = LessonProgress::count();
        $completed = LessonProgress::where('completed', true)->count();
        
        return response()->json([
            'total' => $total,
            'completed' => $completed,
            'in_progress' => $total - $completed
        ]);
    }

    // ADD THESE NEW METHODS FOR API ENDPOINTS
    
    /**
     * Get user progress statistics for API
     */
    public function getUserProgress($id)
    {
        $user = User::findOrFail($id);
        
        $totalLessons = Lesson::count();
        $completedLessons = LessonProgress::where('user_id', $id)
            ->where('completed', true)
            ->count();
        $inProgress = LessonProgress::where('user_id', $id)
            ->where('completed', false)
            ->count();
        
        return response()->json([
            'total_lessons' => $totalLessons,
            'completed_lessons' => $completedLessons,
            'in_progress' => $inProgress,
            'user_name' => $user->name,
            'last_activity' => LessonProgress::where('user_id', $id)
                ->latest()
                ->value('updated_at')
        ]);
    }

    /**
     * Get lesson progress statistics for API
     */
    public function getLessonProgressStats($id)
    {
        $lesson = Lesson::with('course')->findOrFail($id);
        
        $completedCount = LessonProgress::where('lesson_id', $id)
            ->where('completed', true)
            ->count();
        $inProgressCount = LessonProgress::where('lesson_id', $id)
            ->where('completed', false)
            ->count();
        $totalUsers = LessonProgress::where('lesson_id', $id)
            ->distinct('user_id')
            ->count('user_id');
        
        return response()->json([
            'lesson_title' => $lesson->title,
            'course_title' => $lesson->course->title ?? null,
            'position' => $lesson->position,
            'is_published' => $lesson->is_published ?? true,
            'has_video' => !empty($lesson->video_url),
            'completed_count' => $completedCount,
            'in_progress_count' => $inProgressCount,
            'total_users' => $totalUsers
        ]);
    }

    /**
     * Check for duplicate progress record
     */
    public function checkDuplicate($userId, $lessonId)
    {
        $request = request();
        $excludeId = $request->get('exclude');
        
        $query = LessonProgress::where('user_id', $userId)
            ->where('lesson_id', $lessonId);
            
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }
        
        $exists = $query->exists();
        
        if ($exists) {
            $progress = $query->first();
                
            return response()->json([
                'exists' => true,
                'progress_id' => $progress->id
            ]);
        }
        
        return response()->json(['exists' => false]);
    }
}