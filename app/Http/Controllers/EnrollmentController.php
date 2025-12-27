<?php

namespace App\Http\Controllers;

use App\Models\Enrollment;
use App\Models\User;
use App\Models\Course;
use Illuminate\Http\Request;

class EnrollmentController extends Controller
{
    /**
     * Display a listing of the enrollments.
     */
    public function index()
    {
        $enrollments = Enrollment::all(); // Fetch all enrollments
        return view('enrollments.index', compact('enrollments')); // Pass enrollments data to the view
    }

    /**
     * Show the form for creating a new enrollment.
     */
    public function create()
    {
        $users = User::all(); // Retrieve all users
        $courses = Course::all(); // Retrieve all courses
        
        return view('enrollments.create', compact('users', 'courses'));
    }

    /**
     * Store a newly created enrollment in storage.
     */
    public function store(Request $request)
    {
        // Validate the request data
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'course_id' => 'required|exists:courses,id',
        ]);

        // Create a new enrollment
        Enrollment::create([
            'user_id' => $request->user_id,
            'course_id' => $request->course_id,
            'enrolled_at' => now(),
        ]);

        // Redirect to the enrollments list with a success message
        return redirect()->route('enrollments.index')->with('success', 'Enrollment created successfully!');
    }

    /**
     * Display the specified enrollment.
     */
    public function show(string $id)
    {
        $enrollment = Enrollment::findOrFail($id); // Find the enrollment by id
        return view('enrollments.show', compact('enrollment')); // Pass the enrollment data to the view
    }

    /**
     * Show the form for editing the specified enrollment.
     */
    public function edit($id)
    {
        $enrollment = Enrollment::findOrFail($id); // Retrieve the enrollment by ID
        $users = User::all(); // Retrieve all users
        $courses = Course::all(); // Retrieve all courses
        
        return view('enrollments.edit', compact('enrollment', 'users', 'courses'));
    }

    /**
     * Update the specified enrollment in storage.
     */
    public function update(Request $request, $id)
    {
        // Validate the request data
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'course_id' => 'required|exists:courses,id',
        ]);

        // Update the enrollment
        $enrollment = Enrollment::findOrFail($id);
        $enrollment->update([
            'user_id' => $request->user_id,
            'course_id' => $request->course_id,
        ]);

        // Redirect to the enrollments list with a success message
        return redirect()->route('enrollments.index')->with('success', 'Enrollment updated successfully!');
    }

    /**
     * Remove the specified enrollment from storage.
     */
    public function destroy(string $id)
    {
        $enrollment = Enrollment::findOrFail($id); // Find the enrollment by id
        $enrollment->delete(); // Delete the enrollment

        return redirect()->route('enrollments.index')->with('success', 'Enrollment deleted successfully.');
    }
}