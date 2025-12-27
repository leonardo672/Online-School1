<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Category;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CourseController extends Controller
{
    /**
     * Display a listing of the courses.
     */
    public function index()
    {
        $courses = Course::all(); // Fetch all courses
        return view('courses.index', compact('courses')); // Pass courses data to the view
    }

    /**
     * Show the form for creating a new course.
     */
    public function create()
    {
        $categories = Category::all(); // Retrieve all categories
        $instructors = User::where('role', 'instructor')->get(); // Retrieve all instructors
        $levels = Course::LEVELS;
        
        return view('courses.create', compact('categories', 'instructors', 'levels'));
    }

    /**
     * Store a newly created course in storage.
     */
    public function store(Request $request)
    {
        // Validate the request data
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'category_id' => 'required|exists:categories,id',
            'instructor_id' => 'required|exists:users,id',
            'price' => 'required|numeric|min:0',
            'level' => 'required|in:' . implode(',', Course::LEVELS),
        ]);

        // Create a new course
        Course::create([
            'title' => $request->title,
            'slug' => Str::slug($request->title) . '-' . uniqid(),
            'description' => $request->description,
            'category_id' => $request->category_id,
            'instructor_id' => $request->instructor_id,
            'price' => $request->price,
            'level' => $request->level,
            'published' => $request->has('published'),
        ]);

        // Redirect to the courses list with a success message
        return redirect()->route('courses.index')->with('success', 'Course created successfully!');
    }

    /**
     * Display the specified course.
     */
    public function show(string $id)
    {
        $course = Course::findOrFail($id); // Find the course by id
        return view('courses.show', compact('course')); // Pass the course data to the view
    }

    /**
     * Show the form for editing the specified course.
     */
    public function edit($id)
    {
        $course = Course::findOrFail($id); // Retrieve the course by ID
        $categories = Category::all(); // Retrieve all categories
        $instructors = User::where('role', 'instructor')->get(); // Retrieve all instructors
        $levels = Course::LEVELS;
        
        return view('courses.edit', compact('course', 'categories', 'instructors', 'levels'));
    }

    /**
     * Update the specified course in storage.
     */
    public function update(Request $request, $id)
    {
        // Validate the request data
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'category_id' => 'required|exists:categories,id',
            'instructor_id' => 'required|exists:users,id',
            'price' => 'required|numeric|min:0',
            'level' => 'required|in:' . implode(',', Course::LEVELS),
        ]);

        // Update the course
        $course = Course::findOrFail($id);
        $course->update([
            'title' => $request->title,
            'description' => $request->description,
            'category_id' => $request->category_id,
            'instructor_id' => $request->instructor_id,
            'price' => $request->price,
            'level' => $request->level,
            'published' => $request->has('published'),
        ]);

        // Redirect to the courses list with a success message
        return redirect()->route('courses.index')->with('success', 'Course updated successfully!');
    }

    /**
     * Remove the specified course from storage.
     */
    public function destroy(string $id)
    {
        $course = Course::findOrFail($id); // Find the course by id
        $course->delete(); // Delete the course

        return redirect()->route('courses.index')->with('success', 'Course deleted successfully.');
    }
}