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
    public function index()
    {
        $lessonProgresses = LessonProgress::all(); // Fetch all lesson progresses
        return view('lesson-progress.index', compact('lessonProgresses')); // Pass lesson progresses data to the view
    }

    /**
     * Show the form for creating a new lesson progress.
     */
    public function create()
    {
        $users = User::all(); // Retrieve all users
        $lessons = Lesson::all(); // Retrieve all lessons
        
        return view('lesson-progress.create', compact('users', 'lessons'));
    }

    /**
     * Store a newly created lesson progress in storage.
     */
    public function store(Request $request)
    {
        // Validate the request data
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'lesson_id' => 'required|exists:lessons,id',
            'completed' => 'boolean',
        ]);

        // Create a new lesson progress
        LessonProgress::create([
            'user_id' => $request->user_id,
            'lesson_id' => $request->lesson_id,
            'completed' => $request->completed ?? false,
            'completed_at' => $request->completed ? now() : null,
        ]);

        // Redirect to the lesson progress list with a success message
        return redirect()->route('lesson-progress.index')->with('success', 'Lesson progress created successfully!');
    }

    /**
     * Display the specified lesson progress.
     */
    public function show(string $id)
    {
        $lessonProgress = LessonProgress::findOrFail($id); // Find the lesson progress by id
        return view('lesson-progress.show', compact('lessonProgress')); // Pass the lesson progress data to the view
    }

    /**
     * Show the form for editing the specified lesson progress.
     */
    public function edit($id)
    {
        $lessonProgress = LessonProgress::findOrFail($id); // Retrieve the lesson progress by ID
        $users = User::all(); // Retrieve all users
        $lessons = Lesson::all(); // Retrieve all lessons
        
        return view('lesson-progress.edit', compact('lessonProgress', 'users', 'lessons'));
    }

    /**
     * Update the specified lesson progress in storage.
     */
    public function update(Request $request, $id)
    {
        // Validate the request data
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'lesson_id' => 'required|exists:lessons,id',
            'completed' => 'boolean',
        ]);

        // Update the lesson progress
        $lessonProgress = LessonProgress::findOrFail($id);
        $lessonProgress->update([
            'user_id' => $request->user_id,
            'lesson_id' => $request->lesson_id,
            'completed' => $request->completed ?? false,
            'completed_at' => $request->completed ? ($lessonProgress->completed_at ?? now()) : null,
        ]);

        // Redirect to the lesson progress list with a success message
        return redirect()->route('lesson-progress.index')->with('success', 'Lesson progress updated successfully!');
    }

    /**
     * Remove the specified lesson progress from storage.
     */
    public function destroy(string $id)
    {
        $lessonProgress = LessonProgress::findOrFail($id); // Find the lesson progress by id
        $lessonProgress->delete(); // Delete the lesson progress

        return redirect()->route('lesson-progress.index')->with('success', 'Lesson progress deleted successfully.');
    }
}