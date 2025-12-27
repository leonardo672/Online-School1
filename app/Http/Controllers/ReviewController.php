<?php

namespace App\Http\Controllers;

use App\Models\Review;
use App\Models\User;
use App\Models\Course;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    /**
     * Display a listing of the reviews.
     */
    public function index()
    {
        $reviews = Review::all(); // Fetch all reviews
        return view('reviews.index', compact('reviews')); // Pass reviews data to the view
    }

    /**
     * Show the form for creating a new review.
     */
    public function create()
    {
        $users = User::all(); // Retrieve all users
        $courses = Course::all(); // Retrieve all courses
        
        return view('reviews.create', compact('users', 'courses'));
    }

    /**
     * Store a newly created review in storage.
     */
    public function store(Request $request)
    {
        // Validate the request data
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'course_id' => 'required|exists:courses,id',
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string',
        ]);

        // Create a new review
        Review::create([
            'user_id' => $request->user_id,
            'course_id' => $request->course_id,
            'rating' => $request->rating,
            'comment' => $request->comment,
        ]);

        // Redirect to the reviews list with a success message
        return redirect()->route('reviews.index')->with('success', 'Review created successfully!');
    }

    /**
     * Display the specified review.
     */
    public function show(string $id)
    {
        $review = Review::findOrFail($id); // Find the review by id
        return view('reviews.show', compact('review')); // Pass the review data to the view
    }

    /**
     * Show the form for editing the specified review.
     */
    public function edit($id)
    {
        $review = Review::findOrFail($id); // Retrieve the review by ID
        $users = User::all(); // Retrieve all users
        $courses = Course::all(); // Retrieve all courses
        
        return view('reviews.edit', compact('review', 'users', 'courses'));
    }

    /**
     * Update the specified review in storage.
     */
    public function update(Request $request, $id)
    {
        // Validate the request data
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'course_id' => 'required|exists:courses,id',
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string',
        ]);

        // Update the review
        $review = Review::findOrFail($id);
        $review->update([
            'user_id' => $request->user_id,
            'course_id' => $request->course_id,
            'rating' => $request->rating,
            'comment' => $request->comment,
        ]);

        // Redirect to the reviews list with a success message
        return redirect()->route('reviews.index')->with('success', 'Review updated successfully!');
    }

    /**
     * Remove the specified review from storage.
     */
    public function destroy(string $id)
    {
        $review = Review::findOrFail($id); // Find the review by id
        $review->delete(); // Delete the review

        return redirect()->route('reviews.index')->with('success', 'Review deleted successfully.');
    }
}