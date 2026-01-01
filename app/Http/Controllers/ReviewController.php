<?php

namespace App\Http\Controllers;

use App\Models\Review;
use App\Models\User;
use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReviewController extends Controller
{
    /**
     * Display a listing of the reviews.
     */
    public function index(Request $request)
    {
        // Build query with filters
        $query = Review::with(['user', 'course'])
            ->when($request->filled('user_id'), function ($q) use ($request) {
                $q->where('user_id', $request->user_id);
            })
            ->when($request->filled('course_id'), function ($q) use ($request) {
                $q->where('course_id', $request->course_id);
            })
            ->when($request->filled('rating'), function ($q) use ($request) {
                $q->where('rating', $request->rating);
            })
            ->when($request->filled('approved'), function ($q) use ($request) {
                $q->where('approved', $request->approved);
            })
            ->when($request->filled('search'), function ($q) use ($request) {
                $q->where(function ($query) use ($request) {
                    $query->whereHas('user', function ($userQuery) use ($request) {
                        $userQuery->where('name', 'like', '%' . $request->search . '%')
                            ->orWhere('email', 'like', '%' . $request->search . '%');
                    })->orWhereHas('course', function ($courseQuery) use ($request) {
                        $courseQuery->where('title', 'like', '%' . $request->search . '%');
                    })->orWhere('comment', 'like', '%' . $request->search . '%');
                });
            });

        // Apply sorting
        switch ($request->get('sort', 'newest')) {
            case 'oldest':
                $query->oldest();
                break;
            case 'highest':
                $query->orderBy('rating', 'desc');
                break;
            case 'lowest':
                $query->orderBy('rating', 'asc');
                break;
            default:
                $query->latest();
        }

        // Get paginated results
        $reviews = $query->paginate(20)->appends($request->query());
        
        // Get statistics using optimized queries
        $reviewsCount = Review::count();
        $averageRating = Review::avg('rating') ?? 0;
        $approvedReviews = Review::where('approved', true)->count();
        $pendingReviews = Review::where('approved', false)->count();
        $fiveStarCount = Review::where('rating', 5)->count();
        $uniqueReviewers = Review::distinct('user_id')->count('user_id');
        
        // Calculate rating distribution
        $ratingDistribution = Review::selectRaw('rating, COUNT(*) as count')
            ->groupBy('rating')
            ->orderBy('rating')
            ->pluck('count', 'rating')
            ->toArray();
        
        // Fill missing ratings
        for ($i = 1; $i <= 5; $i++) {
            if (!isset($ratingDistribution[$i])) {
                $ratingDistribution[$i] = 0;
            }
        }
        
        // Get most reviewed course
        $mostReviewedCourse = Course::select('courses.*')
            ->selectRaw('COUNT(reviews.id) as review_count')
            ->join('reviews', 'courses.id', '=', 'reviews.course_id')
            ->groupBy('courses.id')
            ->orderByDesc('review_count')
            ->first();
        
        $mostReviewedCourseCount = $mostReviewedCourse ? $mostReviewedCourse->review_count : 0;
        
        // Get most active reviewer
        $mostActiveReviewer = User::select('users.*')
            ->selectRaw('COUNT(reviews.id) as review_count')
            ->join('reviews', 'users.id', '=', 'reviews.user_id')
            ->groupBy('users.id')
            ->orderByDesc('review_count')
            ->first();
        
        $mostActiveReviewerCount = $mostActiveReviewer ? $mostActiveReviewer->review_count : 0;
        
        // Get all users and courses for filters
        $users = User::orderBy('name')->get();
        $courses = Course::orderBy('title')->get();
        
        // Get recent reviews for quick view
        $recentReviews = Review::with(['user', 'course'])
            ->latest()
            ->limit(5)
            ->get();
        
        // Get top reviewers for leaderboard
        $topReviewers = User::select('users.*')
            ->selectRaw('COUNT(reviews.id) as review_count')
            ->selectRaw('AVG(reviews.rating) as average_rating')
            ->leftJoin('reviews', 'users.id', '=', 'reviews.user_id')
            ->groupBy('users.id')
            ->orderByDesc('review_count')
            ->limit(5)
            ->get();
        
        // Get most reviewed courses for leaderboard
        $mostReviewedCourses = Course::select('courses.*')
            ->selectRaw('COUNT(reviews.id) as review_count')
            ->selectRaw('AVG(reviews.rating) as average_rating')
            ->leftJoin('reviews', 'courses.id', '=', 'reviews.course_id')
            ->groupBy('courses.id')
            ->orderByDesc('review_count')
            ->limit(5)
            ->get();
        
        return view('reviews.index', [
            // Main data
            'reviews' => $reviews,
            'users' => $users,
            'courses' => $courses,
            
            // Statistics
            'reviewsCount' => $reviewsCount,
            'averageRating' => $averageRating,
            'approvedReviews' => $approvedReviews,
            'pendingReviews' => $pendingReviews,
            'fiveStarCount' => $fiveStarCount,
            'totalReviews' => $reviewsCount,
            'uniqueReviewers' => $uniqueReviewers,
            
            // Distribution
            'ratingDistribution' => $ratingDistribution,
            
            // Leaderboards and summaries
            'mostReviewedCourse' => $mostReviewedCourse,
            'mostReviewedCourseCount' => $mostReviewedCourseCount,
            'mostActiveReviewer' => $mostActiveReviewer,
            'mostActiveReviewerCount' => $mostActiveReviewerCount,
            'topReviewers' => $topReviewers,
            'mostReviewedCourses' => $mostReviewedCourses,
            'recentReviews' => $recentReviews,
        ]);
    }

    /**
     * Show the form for creating a new review.
     */
    public function create()
    {
        $users = User::orderBy('name')->get();
        $courses = Course::orderBy('title')->get();
        
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
            'comment' => 'nullable|string|max:1000',
            'approved' => 'nullable|boolean',
        ]);

        // Check for duplicate review
        $existingReview = Review::where('user_id', $request->user_id)
            ->where('course_id', $request->course_id)
            ->first();
            
        if ($existingReview) {
            return redirect()->back()->with('error', 'This user has already reviewed this course.');
        }

        // Create a new review
        Review::create([
            'user_id' => $request->user_id,
            'course_id' => $request->course_id,
            'rating' => $request->rating,
            'comment' => $request->comment,
            'approved' => $request->approved ?? false,
        ]);

        // Redirect to the reviews list with a success message
        return redirect()->route('reviews.index')->with('success', 'Review created successfully!');
    }

    /**
     * Display the specified review.
     */
    public function show(string $id)
    {
        $review = Review::with(['user', 'course'])->findOrFail($id);
        return view('reviews.show', compact('review'));
    }

    /**
     * Show the form for editing the specified review.
     */
    public function edit($id)
    {
        $review = Review::findOrFail($id);
        $users = User::orderBy('name')->get();
        $courses = Course::orderBy('title')->get();
        
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
            'comment' => 'nullable|string|max:1000',
            'approved' => 'nullable|boolean',
        ]);

        // Check for duplicate review (excluding current review)
        $existingReview = Review::where('user_id', $request->user_id)
            ->where('course_id', $request->course_id)
            ->where('id', '!=', $id)
            ->first();
            
        if ($existingReview) {
            return redirect()->back()->with('error', 'This user has already reviewed this course.');
        }

        // Update the review
        $review = Review::findOrFail($id);
        $review->update([
            'user_id' => $request->user_id,
            'course_id' => $request->course_id,
            'rating' => $request->rating,
            'comment' => $request->comment,
            'approved' => $request->approved ?? $review->approved,
        ]);

        // Redirect to the reviews list with a success message
        return redirect()->route('reviews.index')->with('success', 'Review updated successfully!');
    }

    /**
     * Remove the specified review from storage.
     */
    public function destroy(string $id)
    {
        $review = Review::findOrFail($id);
        $review->delete();

        return redirect()->route('reviews.index')->with('success', 'Review deleted successfully.');
    }
    
    /**
     * Approve or disapprove a review (updated for PUT method support)
     */
    public function approve(Request $request, $id)
    {
        $review = Review::findOrFail($id);
        
        // Get status from request (default to 1 for approve)
        $status = $request->input('status', 1);
        
        // Handle both PUT (new) and POST (old) methods
        if ($request->isMethod('PUT')) {
            // New functionality with status parameter
            $review->update(['approved' => $status]);
            
            $action = $status ? 'approved' : 'disapproved';
            $message = "Review has been $action successfully.";
            
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $message
                ]);
            }
            
            return redirect()->route('reviews.index')->with('success', $message);
            
        } else if ($request->isMethod('POST')) {
            // Old functionality - always approve
            $review->update(['approved' => true]);
            return redirect()->route('reviews.index')->with('success', 'Review approved successfully.');
            
        } else {
            // GET request - show info message
            return redirect()->route('reviews.index')
                ->with('info', 'Please use the approve button in the reviews list.');
        }
    }
    
    /**
     * Disapprove a review (alias for approve with status 0)
     */
    public function disapprove(Request $request, $id)
    {
        $review = Review::findOrFail($id);
        
        // Handle both PUT (new) and POST (old) methods
        if ($request->isMethod('PUT')) {
            $review->update(['approved' => false]);
            
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Review has been disapproved successfully.'
                ]);
            }
            
            return redirect()->route('reviews.index')->with('success', 'Review disapproved successfully.');
            
        } else if ($request->isMethod('POST')) {
            // Old functionality
            $review->update(['approved' => false]);
            return redirect()->route('reviews.index')->with('success', 'Review disapproved successfully.');
            
        } else {
            // GET request
            return redirect()->route('reviews.index')
                ->with('info', 'Please use the disapprove button in the reviews list.');
        }
    }
    
    /**
     * Bulk approve reviews (updated for AJAX support)
     */
    public function bulkApprove(Request $request)
    {
        $request->validate([
            'review_ids' => 'required|array',
            'review_ids.*' => 'exists:reviews,id',
            'status' => 'sometimes|boolean', // Optional status parameter
        ]);
        
        // Get status (default to 1 for approve)
        $status = $request->input('status', 1);
        
        $count = Review::whereIn('id', $request->review_ids)->update(['approved' => $status]);
        
        $action = $status ? 'approved' : 'disapproved';
        $message = $count . ' reviews ' . $action . ' successfully.';
        
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'count' => $count,
                'message' => $message
            ]);
        }
        
        return redirect()->route('reviews.index')->with('success', $message);
    }
    
    /**
     * Bulk delete reviews (updated for AJAX support)
     */
    public function bulkDelete(Request $request)
    {
        $request->validate([
            'review_ids' => 'required|array',
            'review_ids.*' => 'exists:reviews,id',
        ]);
        
        $count = Review::whereIn('id', $request->review_ids)->delete();
        
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'count' => $count,
                'message' => $count . ' reviews deleted successfully.'
            ]);
        }
        
        return redirect()->route('reviews.index')->with('success', $count . ' reviews deleted successfully.');
    }
    
    /**
     * Update review rating via AJAX.
     */
    public function updateRating(Request $request, $id)
    {
        $request->validate([
            'rating' => 'required|integer|min:1|max:5'
        ]);

        $review = Review::findOrFail($id);
        $review->update(['rating' => $request->rating]);

        return response()->json([
            'success' => true,
            'message' => 'Rating updated successfully',
            'rating' => $review->rating
        ]);
    }
    
    /**
     * Export reviews.
     */
    public function export(Request $request, $format = 'csv')
    {
        $query = Review::with(['user', 'course'])
            ->when($request->filled('user_id'), function ($q) use ($request) {
                $q->where('user_id', $request->user_id);
            })
            ->when($request->filled('course_id'), function ($q) use ($request) {
                $q->where('course_id', $request->course_id);
            })
            ->when($request->filled('rating'), function ($q) use ($request) {
                $q->where('rating', $request->rating);
            })
            ->when($request->filled('approved'), function ($q) use ($request) {
                $q->where('approved', $request->approved);
            });

        $reviews = $query->get();

        if ($format === 'pdf') {
            // PDF export - show info message
            return redirect()->back()->with('info', 'PDF export requires dompdf package. Please install: composer require barryvdh/laravel-dompdf');
        } else {
            // CSV export
            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="reviews-export-' . date('Y-m-d') . '.csv"',
                'Pragma' => 'no-cache',
                'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
                'Expires' => '0'
            ];

            $callback = function() use ($reviews) {
                $file = fopen('php://output', 'w');
                
                // Add BOM for UTF-8
                fwrite($file, "\xEF\xBB\xBF");
                
                // Headers
                fputcsv($file, ['ID', 'User Name', 'User Email', 'Course Title', 'Rating', 'Comment', 'Approved', 'Created At']);
                
                // Data rows
                foreach ($reviews as $review) {
                    fputcsv($file, [
                        $review->id,
                        $review->user->name ?? 'N/A',
                        $review->user->email ?? 'N/A',
                        $review->course->title ?? 'N/A',
                        $review->rating,
                        $review->comment,
                        $review->approved ? 'Yes' : 'No',
                        $review->created_at->format('Y-m-d H:i:s')
                    ]);
                }
                
                fclose($file);
            };

            return response()->stream($callback, 200, $headers);
        }
    }
    
    /**
     * Get review statistics for API
     */
    public function apiStats(Request $request)
    {
        $stats = [
            'total' => Review::count(),
            'average_rating' => Review::avg('rating'),
            'approved' => Review::where('approved', true)->count(),
            'pending' => Review::where('approved', false)->count(),
            'rating_distribution' => Review::selectRaw('rating, COUNT(*) as count')
                ->groupBy('rating')
                ->orderBy('rating')
                ->pluck('count', 'rating')
                ->toArray(),
        ];
        
        return response()->json($stats);
    }
    
    /**
     * Get reviews for a specific course
     */
    public function getCourseReviews($courseId)
    {
        $reviews = Review::with('user')
            ->where('course_id', $courseId)
            ->where('approved', true)
            ->latest()
            ->paginate(10);
            
        return response()->json($reviews);
    }
    
    /**
     * Get reviews by a specific user
     */
    public function getUserReviews($userId)
    {
        $reviews = Review::with('course')
            ->where('user_id', $userId)
            ->latest()
            ->paginate(10);
            
        return response()->json($reviews);
    }
    
    /**
     * Toggle approve status via API
     */
    public function toggleApprove($id)
    {
        $review = Review::findOrFail($id);
        $review->update(['approved' => !$review->approved]);
        
        return response()->json([
            'success' => true,
            'approved' => $review->approved,
            'message' => 'Review status updated'
        ]);
    }
    
    /**
     * Quick approve for AJAX requests (API route)
     */
    public function quickApprove($id)
    {
        try {
            $review = Review::findOrFail($id);
            $newStatus = !$review->approved; // Toggle status
            $review->update(['approved' => $newStatus]);
            
            return response()->json([
                'success' => true,
                'approved' => $newStatus,
                'message' => $newStatus ? 'Review approved' : 'Review disapproved'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update review status'
            ], 500);
        }
    }
    
    /**
     * Student view of their own reviews
     */
    public function studentIndex(Request $request)
    {
        // Assuming user is authenticated
        $userId = auth()->id();
        
        $reviews = Review::with('course')
            ->where('user_id', $userId)
            ->latest()
            ->paginate(10);
            
        return view('reviews.student-index', compact('reviews'));
    }
    
    /**
     * Student store review
     */
    public function studentStore(Request $request)
    {
        $request->validate([
            'course_id' => 'required|exists:courses,id',
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string',
        ]);
        
        // Check if user has already reviewed this course
        $existingReview = Review::where('user_id', auth()->id())
            ->where('course_id', $request->course_id)
            ->first();
            
        if ($existingReview) {
            return redirect()->back()->with('error', 'You have already reviewed this course.');
        }
        
        Review::create([
            'user_id' => auth()->id(),
            'course_id' => $request->course_id,
            'rating' => $request->rating,
            'comment' => $request->comment,
            'approved' => false, // Reviews from students need approval
        ]);
        
        return redirect()->back()->with('success', 'Review submitted successfully. It will be visible after approval.');
    }
    
    /**
     * Get review statistics page
     */
    public function stats()
    {
        $stats = [
            'total' => Review::count(),
            'average_rating' => Review::avg('rating'),
            'approved' => Review::where('approved', true)->count(),
            'pending' => Review::where('approved', false)->count(),
            'rating_distribution' => Review::selectRaw('rating, COUNT(*) as count')
                ->groupBy('rating')
                ->orderBy('rating')
                ->pluck('count', 'rating')
                ->toArray(),
            'recent_reviews' => Review::with(['user', 'course'])
                ->latest()
                ->limit(10)
                ->get(),
        ];
        
        return view('reviews.stats', $stats);
    }
}