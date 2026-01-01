<?php

namespace App\Http\Controllers;

use App\Models\Lesson;
use App\Models\Course;
use Illuminate\Http\Request;

class LessonController extends Controller
{
    /**
     * Display a listing of the lessons.
     */
    public function index()
    {
        $lessons = Lesson::with('course')->latest()->paginate(10); // Fetch lessons with pagination and eager loading
        return view('lessons.index', compact('lessons'));
    }

    /**
     * Show the form for creating a new lesson.
     */
    public function create()
    {
        $courses = Course::all(); // Retrieve all courses
        return view('lessons.create', compact('courses'));
    }

    /**
     * Store a newly created lesson in storage.
     */
    public function store(Request $request)
    {
        // Validate the request data
        $request->validate([
            'course_id' => 'required|exists:courses,id',
            'title' => 'required|string|max:255',
            'content' => 'nullable|string',
            'video_url' => 'nullable|url',
            'position' => 'required|integer|min:1',
        ]);

        // Create a new lesson
        Lesson::create([
            'course_id' => $request->course_id,
            'title' => $request->title,
            'content' => $request->content,
            'video_url' => $request->video_url,
            'position' => $request->position,
        ]);

        // Redirect to the lessons list with a success message
        return redirect()->route('lessons.index')->with('success', 'Lesson created successfully!');
    }

    /**
     * Display the specified lesson.
     */
    public function show(string $id)
    {
        $lesson = Lesson::with('course')->findOrFail($id);
        
        // Get previous and next lessons in the same course
        $previousLesson = Lesson::where('course_id', $lesson->course_id)
            ->where('id', '<', $lesson->id)
            ->orderBy('id', 'desc')
            ->first();

        $nextLesson = Lesson::where('course_id', $lesson->course_id)
            ->where('id', '>', $lesson->id)
            ->orderBy('id', 'asc')
            ->first();

        // Get all lessons in the course for statistics
        $courseLessons = Lesson::where('course_id', $lesson->course_id)
            ->orderBy('position')
            ->get();

        // Calculate lesson position in course
        $lessonPosition = $courseLessons->search(function ($item) use ($lesson) {
            return $item->id == $lesson->id;
        }) + 1;

        $totalLessons = $courseLessons->count();

        // Get related lessons (other lessons in the same course)
        $relatedLessons = Lesson::where('course_id', $lesson->course_id)
            ->where('id', '!=', $lesson->id)
            ->with('course')
            ->orderBy('position')
            ->take(5)
            ->get();

        return view('lessons.show', [
            'lesson' => $lesson,
            'previousLesson' => $previousLesson,
            'nextLesson' => $nextLesson,
            'courseLessons' => $courseLessons,
            'lessonPosition' => $lessonPosition,
            'totalLessons' => $totalLessons,
            'relatedLessons' => $relatedLessons
        ]);
    }

    /**
     * Show the form for editing the specified lesson.
     */
    public function edit($id)
    {
        $lesson = Lesson::with('course')->findOrFail($id);
        $courses = Course::all();
        
        return view('lessons.edit', compact('lesson', 'courses'));
    }

    /**
     * Update the specified lesson in storage.
     */
    public function update(Request $request, $id)
    {
        // Validate the request data
        $request->validate([
            'course_id' => 'required|exists:courses,id',
            'title' => 'required|string|max:255',
            'content' => 'nullable|string',
            'video_url' => 'nullable|url',
            'position' => 'required|integer|min:1',
        ]);

        // Update the lesson
        $lesson = Lesson::findOrFail($id);
        $lesson->update([
            'course_id' => $request->course_id,
            'title' => $request->title,
            'content' => $request->content,
            'video_url' => $request->video_url,
            'position' => $request->position,
        ]);

        // Redirect to the lessons list with a success message
        return redirect()->route('lessons.index')->with('success', 'Lesson updated successfully!');
    }

    /**
     * Remove the specified lesson from storage.
     */
    public function destroy(string $id)
    {
        $lesson = Lesson::findOrFail($id);
        $lesson->delete();

        return redirect()->route('lessons.index')->with('success', 'Lesson deleted successfully.');
    }

    /**
     * Mark lesson as completed for the authenticated user.
     */
    public function markAsComplete(Request $request, $id)
    {
        $lesson = Lesson::findOrFail($id);
        
        // Assuming you have a lesson_user pivot table for tracking completion
        auth()->user()->completedLessons()->syncWithoutDetaching([$lesson->id]);
        
        return back()->with('success', 'Lesson marked as complete!');
    }

    /**
     * Get lessons by course.
     */
    public function byCourse($courseId)
    {
        $lessons = Lesson::where('course_id', $courseId)
            ->with('course')
            ->orderBy('position')
            ->paginate(10);
            
        $course = Course::findOrFail($courseId);
        
        return view('lessons.by-course', compact('lessons', 'course'));
    }
}