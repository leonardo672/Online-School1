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
        $lessons = Lesson::all(); // Fetch all lessons
        return view('lessons.index', compact('lessons')); // Pass lessons data to the view
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
        $lesson = Lesson::findOrFail($id); // Find the lesson by id
        return view('lessons.show', compact('lesson')); // Pass the lesson data to the view
    }

    /**
     * Show the form for editing the specified lesson.
     */
    public function edit($id)
    {
        $lesson = Lesson::findOrFail($id); // Retrieve the lesson by ID
        $courses = Course::all(); // Retrieve all courses
        
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
        $lesson = Lesson::findOrFail($id); // Find the lesson by id
        $lesson->delete(); // Delete the lesson

        return redirect()->route('lessons.index')->with('success', 'Lesson deleted successfully.');
    }
}