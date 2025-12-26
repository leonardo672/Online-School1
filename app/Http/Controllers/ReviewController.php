<?php

namespace App\Http\Controllers;

use App\Models\Review;
use App\Models\Course;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ReviewController extends Controller
{
    /**
     * Display a listing of reviews.
     */
    public function index(Request $request)
    {
        try {
            $query = Review::query();

            // Eager load relationships if requested
            if ($request->has('with')) {
                $with = explode(',', $request->with);
                $allowedRelations = ['user', 'course', 'course.instructor', 'course.category'];
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

            // Filter by user
            if ($request->has('user_id') && !empty($request->user_id)) {
                $query->where('user_id', $request->user_id);
            }

            // Filter by user email
            if ($request->has('user_email') && !empty($request->user_email)) {
                $user = User::where('email', $request->user_email)->first();
                if ($user) {
                    $query->where('user_id', $user->id);
                }
            }

            // Filter by rating
            if ($request->has('rating') && !empty($request->rating)) {
                $query->where('rating', $request->rating);
            }

            // Filter by minimum rating
            if ($request->has('min_rating') && !empty($request->min_rating)) {
                $query->where('rating', '>=', $request->min_rating);
            }

            // Filter by maximum rating
            if ($request->has('max_rating') && !empty($request->max_rating)) {
                $query->where('rating', '<=', $request->max_rating);
            }

            // Filter by rating range
            if ($request->has('rating_range') && is_array($request->rating_range)) {
                $query->whereBetween('rating', $request->rating_range);
            }

            // Filter by comments only
            if ($request->has('has_comments') && $request->has_comments) {
                $query->whereNotNull('comment')->where('comment', '!=', '');
            }

            // Filter by date range
            if ($request->has('date_from') && !empty($request->date_from)) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }
            if ($request->has('date_to') && !empty($request->date_to)) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }

            // Filter by recent reviews (last 7 days)
            if ($request->has('recent') && $request->recent) {
                $query->where('created_at', '>=', now()->subDays(7));
            }

            // Ordering
            $orderBy = $request->get('order_by', 'created_at');
            $orderDirection = $request->get('order_direction', 'desc');
            
            if ($orderBy === 'rating') {
                $query->orderBy('rating', $orderDirection);
            } elseif (in_array($orderBy, ['created_at', 'updated_at'])) {
                $query->orderBy($orderBy, $orderDirection);
            } elseif ($orderBy === 'highest') {
                $query->highestRated();
            } elseif ($orderBy === 'lowest') {
                $query->lowestRated();
            } else {
                $query->latest();
            }

            // Include counts if requested
            $withCounts = $request->get('with_counts', false);
            if ($withCounts) {
                // You can add counts here if needed
            }

            // Pagination
            $perPage = $request->get('per_page', 15);
            $reviews = $perPage === 'all' 
                ? $query->get()
                : $query->paginate($perPage);

            // Add computed attributes if requested
            if ($request->has('include_computed') && $request->include_computed) {
                $reviews->transform(function ($review) {
                    $review->stars = $review->stars;
                    $review->rating_text = $review->rating_text;
                    $review->formatted_date = $review->formatted_date;
                    $review->time_ago = $review->time_ago;
                    $review->is_recent = $review->isRecent();
                    $review->has_comment = $review->hasComment();
                    $review->excerpt = $review->excerpt();
                    return $review;
                });
            }

            // Include rating stats if requested for course reviews
            if ($request->has('include_rating_stats') && $request->include_rating_stats && $request->has('course_id')) {
                $ratingStats = Review::getCourseRatingStats($request->course_id);
                return response()->json([
                    'success' => true,
                    'data' => [
                        'reviews' => $reviews,
                        'rating_stats' => $ratingStats,
                    ],
                    'message' => 'Reviews retrieved successfully with rating statistics.'
                ]);
            }

            return response()->json([
                'success' => true,
                'data' => $reviews,
                'message' => 'Reviews retrieved successfully.'
            ]);

        } catch (\Exception $e) {
            Log::error('Review index error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve reviews.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Store a newly created review.
     */
    public function store(Request $request)
    {
        DB::beginTransaction();

        try {
            $validator = Validator::make($request->all(), [
                'user_id' => 'required|exists:users,id',
                'course_id' => 'required|exists:courses,id',
                'rating' => 'required|integer|min:1|max:5',
                'comment' => 'nullable|string|max:1000',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Check if user is enrolled in the course
            $course = Course::find($request->course_id);
            $isEnrolled = $course->hasUserEnrolled($request->user_id);
            
            if (!$isEnrolled && !$request->has('force')) {
                return response()->json([
                    'success' => false,
                    'message' => 'User is not enrolled in this course. Use force option to create review anyway.'
                ], 403);
            }

            // Check if user has already reviewed the course
            $existingReview = Review::where('user_id', $request->user_id)
                ->where('course_id', $request->course_id)
                ->first();

            if ($existingReview) {
                return response()->json([
                    'success' => false,
                    'message' => 'User has already reviewed this course. Use update instead.'
                ], 409);
            }

            $review = Review::create($request->all());

            DB::commit();

            // Load relationships if needed
            if ($request->has('load_relations')) {
                $review->load(['user', 'course']);
            }

            return response()->json([
                'success' => true,
                'data' => $review,
                'message' => 'Review created successfully.'
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Review store error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to create review.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Create or update a review for current user.
     */
    public function createOrUpdateSelfReview(Request $request)
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
                'course_id' => 'required|exists:courses,id',
                'rating' => 'required|integer|min:1|max:5',
                'comment' => 'nullable|string|max:1000',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors' => $validator->errors()
                ], 422);
            }

            $course = Course::find($request->course_id);

            // Check if user is enrolled in the course
            $isEnrolled = $course->hasUserEnrolled($user->id);
            
            if (!$isEnrolled) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not enrolled in this course.'
                ], 403);
            }

            // Check if user has completed the course (optional requirement)
            if ($request->has('require_completion') && $request->require_completion) {
                $progress = $course->getUserCompletionPercentage($user->id);
                if ($progress < 100) {
                    return response()->json([
                        'success' => false,
                        'message' => 'You must complete the course before reviewing it.',
                        'completion_percentage' => $progress
                    ], 403);
                }
            }

            $review = Review::createOrUpdate(
                $user->id,
                $course->id,
                $request->rating,
                $request->comment
            );

            DB::commit();

            $review->load(['course']);

            $action = $review->wasRecentlyCreated ? 'created' : 'updated';

            return response()->json([
                'success' => true,
                'data' => $review,
                'message' => 'Review ' . $action . ' successfully.'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Create or update self review error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to create or update review.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Display the specified review.
     */
    public function show($id)
    {
        try {
            $query = Review::query();

            // Load relationships if requested
            if (request()->has('with')) {
                $with = explode(',', request()->with);
                $allowedRelations = ['user', 'course', 'course.instructor', 'course.category'];
                $query->with(array_intersect($with, $allowedRelations));
            }

            $review = $query->findOrFail($id);

            // Add computed attributes
            $review->stars = $review->stars;
            $review->rating_text = $review->rating_text;
            $review->formatted_date = $review->formatted_date;
            $review->time_ago = $review->time_ago;
            $review->is_recent = $review->isRecent();
            $review->has_comment = $review->hasComment();
            $review->excerpt = $review->excerpt();

            // Check if review is by current user
            $review->is_by_current_user = $review->isByCurrentUser();

            return response()->json([
                'success' => true,
                'data' => $review,
                'message' => 'Review retrieved successfully.'
            ]);

        } catch (\Exception $e) {
            Log::error('Review show error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Review not found.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 404);
        }
    }

    /**
     * Update the specified review.
     */
    public function update(Request $request, $id)
    {
        DB::beginTransaction();

        try {
            $review = Review::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'rating' => 'nullable|integer|min:1|max:5',
                'comment' => 'nullable|string|max:1000',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Check if user is authorized to update (admin or review owner)
            $user = auth()->user();
            if ($user && $review->user_id !== $user->id && !$user->is_admin) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not authorized to update this review.'
                ], 403);
            }

            // Update review
            $review->update($request->all());

            DB::commit();

            $review->refresh();

            return response()->json([
                'success' => true,
                'data' => $review,
                'message' => 'Review updated successfully.'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Review update error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to update review.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Remove the specified review.
     */
    public function destroy($id)
    {
        DB::beginTransaction();

        try {
            $review = Review::findOrFail($id);

            // Check if user is authorized to delete (admin or review owner)
            $user = auth()->user();
            if ($user && $review->user_id !== $user->id && !$user->is_admin) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not authorized to delete this review.'
                ], 403);
            }

            $review->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Review deleted successfully.'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Review destroy error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete review.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Get course reviews with statistics.
     */
    public function courseReviews($courseId)
    {
        try {
            $course = Course::findOrFail($courseId);

            $query = $course->reviews()->with('user');

            // Filter by rating if requested
            if (request()->has('rating')) {
                $query->where('rating', request()->rating);
            }

            // Filter by comments only
            if (request()->has('has_comments') && request()->has_comments) {
                $query->whereNotNull('comment')->where('comment', '!=', '');
            }

            // Ordering
            $orderBy = request()->get('order_by', 'created_at');
            $orderDirection = request()->get('order_direction', 'desc');
            
            if ($orderBy === 'rating') {
                $query->orderBy('rating', $orderDirection);
            } elseif ($orderBy === 'highest') {
                $query->highestRated();
            } elseif ($orderBy === 'lowest') {
                $query->lowestRated();
            } else {
                $query->latest();
            }

            // Pagination
            $perPage = request()->get('per_page', 15);
            $reviews = $perPage === 'all' 
                ? $query->get()
                : $query->paginate($perPage);

            // Get rating statistics
            $ratingStats = Review::getCourseRatingStats($courseId);

            // Add computed attributes
            $reviews->transform(function ($review) {
                $review->stars = $review->stars;
                $review->rating_text = $review->rating_text;
                $review->formatted_date = $review->formatted_date;
                $review->time_ago = $review->time_ago;
                $review->has_comment = $review->hasComment();
                $review->excerpt = $review->excerpt(100);
                return $review;
            });

            // Get user's review if authenticated
            $userReview = null;
            $user = auth()->user();
            if ($user) {
                $userReview = Review::getUserReview($user->id, $courseId);
                if ($userReview) {
                    $userReview->stars = $userReview->stars;
                    $userReview->rating_text = $userReview->rating_text;
                    $userReview->formatted_date = $userReview->formatted_date;
                }
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'course' => $course->only(['id', 'title', 'slug']),
                    'reviews' => $reviews,
                    'rating_stats' => $ratingStats,
                    'user_review' => $userReview,
                    'total_reviews' => $ratingStats['total'],
                    'average_rating' => $ratingStats['average'],
                ],
                'message' => 'Course reviews retrieved successfully.'
            ]);

        } catch (\Exception $e) {
            Log::error('Course reviews error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve course reviews.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Get user's reviews.
     */
    public function userReviews($userId)
    {
        try {
            $user = User::findOrFail($userId);

            $query = $user->reviews()->with('course');

            // Filter by rating
            if (request()->has('rating')) {
                $query->where('rating', request()->rating);
            }

            // Filter by course
            if (request()->has('course_id') && !empty(request()->course_id)) {
                $query->where('course_id', request()->course_id);
            }

            // Filter by date range
            if (request()->has('date_from') && !empty(request()->date_from)) {
                $query->whereDate('created_at', '>=', request()->date_from);
            }
            if (request()->has('date_to') && !empty(request()->date_to)) {
                $query->whereDate('created_at', '<=', request()->date_to);
            }

            // Ordering
            $orderBy = request()->get('order_by', 'created_at');
            $orderDirection = request()->get('order_direction', 'desc');
            
            if ($orderBy === 'rating') {
                $query->orderBy('rating', $orderDirection);
            } elseif ($orderBy === 'course') {
                $query->join('courses', 'reviews.course_id', '=', 'courses.id')
                    ->orderBy('courses.title', $orderDirection)
                    ->select('reviews.*');
            } else {
                $query->latest();
            }

            // Pagination
            $perPage = request()->get('per_page', 15);
            $reviews = $perPage === 'all' 
                ? $query->get()
                : $query->paginate($perPage);

            // Add computed attributes
            $reviews->transform(function ($review) {
                $review->stars = $review->stars;
                $review->rating_text = $review->rating_text;
                $review->formatted_date = $review->formatted_date;
                $review->time_ago = $review->time_ago;
                $review->has_comment = $review->hasComment();
                $review->excerpt = $review->excerpt(100);
                return $review;
            });

            // Get user review statistics
            $totalReviews = $user->reviews()->count();
            $averageRating = $user->reviews()->avg('rating');
            $ratingDistribution = [];

            for ($i = 1; $i <= 5; $i++) {
                $count = $user->reviews()->where('rating', $i)->count();
                $ratingDistribution[$i] = $count;
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'user' => $user->only(['id', 'name', 'email']),
                    'reviews' => $reviews,
                    'statistics' => [
                        'total_reviews' => $totalReviews,
                        'average_rating' => round($averageRating, 1),
                        'rating_distribution' => $ratingDistribution,
                    ],
                ],
                'message' => 'User reviews retrieved successfully.'
            ]);

        } catch (\Exception $e) {
            Log::error('User reviews error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve user reviews.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Get current user's reviews.
     */
    public function selfReviews(Request $request)
    {
        try {
            $user = auth()->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated.'
                ], 401);
            }

            $query = $user->reviews()->with('course');

            // Apply filters
            if ($request->has('rating')) {
                $query->where('rating', $request->rating);
            }

            if ($request->has('course_id') && !empty($request->course_id)) {
                $query->where('course_id', $request->course_id);
            }

            if ($request->has('has_comments') && $request->has_comments) {
                $query->whereNotNull('comment')->where('comment', '!=', '');
            }

            // Ordering
            $orderBy = $request->get('order_by', 'created_at');
            $orderDirection = $request->get('order_direction', 'desc');
            
            if ($orderBy === 'rating') {
                $query->orderBy('rating', $orderDirection);
            } elseif ($orderBy === 'course') {
                $query->join('courses', 'reviews.course_id', '=', 'courses.id')
                    ->orderBy('courses.title', $orderDirection)
                    ->select('reviews.*');
            } else {
                $query->latest();
            }

            // Pagination
            $perPage = $request->get('per_page', 15);
            $reviews = $perPage === 'all' 
                ? $query->get()
                : $query->paginate($perPage);

            // Add computed attributes
            $reviews->transform(function ($review) {
                $review->stars = $review->stars;
                $review->rating_text = $review->rating_text;
                $review->formatted_date = $review->formatted_date;
                $review->time_ago = $review->time_ago;
                $review->has_comment = $review->hasComment();
                $review->excerpt = $review->excerpt(100);
                $review->course_title = $review->course->title ?? null;
                $review->course_slug = $review->course->slug ?? null;
                return $review;
            });

            // Get statistics
            $totalReviews = $user->reviews()->count();
            $averageRating = $user->reviews()->avg('rating');
            $coursesReviewed = $user->reviews()->distinct('course_id')->count('course_id');

            return response()->json([
                'success' => true,
                'data' => [
                    'reviews' => $reviews,
                    'statistics' => [
                        'total_reviews' => $totalReviews,
                        'average_rating' => round($averageRating, 1),
                        'courses_reviewed' => $coursesReviewed,
                    ],
                ],
                'message' => 'Your reviews retrieved successfully.'
            ]);

        } catch (\Exception $e) {
            Log::error('Self reviews error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve your reviews.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Get review statistics.
     */
    public function statistics(Request $request)
    {
        try {
            $query = Review::query();

            // Apply filters if any
            if ($request->has('course_id') && !empty($request->course_id)) {
                $query->where('course_id', $request->course_id);
            }

            if ($request->has('date_from') && !empty($request->date_from)) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }

            if ($request->has('date_to') && !empty($request->date_to)) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }

            $totalReviews = $query->count();
            $reviewsWithComments = $query->clone()->whereNotNull('comment')->where('comment', '!=', '')->count();
            $averageRating = $query->clone()->avg('rating');
            $todayReviews = $query->clone()->whereDate('created_at', today())->count();
            $weekReviews = $query->clone()->where('created_at', '>=', now()->subDays(7))->count();
            $monthReviews = $query->clone()->where('created_at', '>=', now()->subDays(30))->count();

            // Get rating distribution
            $ratingDistribution = [];
            for ($i = 1; $i <= 5; $i++) {
                $count = $query->clone()->where('rating', $i)->count();
                $ratingDistribution[$i] = $count;
            }

            // Get reviews by date for the last 30 days
            $reviewsByDate = Review::select(
                    DB::raw('DATE(created_at) as date'),
                    DB::raw('count(*) as count'),
                    DB::raw('avg(rating) as avg_rating')
                )
                ->where('created_at', '>=', now()->subDays(30))
                ->groupBy('date')
                ->orderBy('date')
                ->get();

            // Get top reviewed courses
            $topReviewedCourses = Course::withCount('reviews')
                ->has('reviews')
                ->orderBy('reviews_count', 'desc')
                ->limit(10)
                ->get();

            // Get top rating courses
            $topRatedCourses = Course::withAvg('reviews', 'rating')
                ->withCount('reviews')
                ->having('reviews_avg_rating', '>', 0)
                ->orderByDesc('reviews_avg_rating')
                ->orderByDesc('reviews_count')
                ->limit(10)
                ->get();

            // Get most active reviewers
            $topReviewers = User::withCount('reviews')
                ->has('reviews')
                ->orderBy('reviews_count', 'desc')
                ->limit(10)
                ->get();

            // Get recent reviews
            $recentReviews = Review::with(['user', 'course'])
                ->latest()
                ->limit(10)
                ->get()
                ->map(function ($review) {
                    $review->stars = $review->stars;
                    $review->rating_text = $review->rating_text;
                    $review->time_ago = $review->time_ago;
                    return $review;
                });

            return response()->json([
                'success' => true,
                'data' => [
                    'total_reviews' => $totalReviews,
                    'reviews_with_comments' => $reviewsWithComments,
                    'average_rating' => round($averageRating, 2),
                    'today_reviews' => $todayReviews,
                    'week_reviews' => $weekReviews,
                    'month_reviews' => $monthReviews,
                    'rating_distribution' => $ratingDistribution,
                    'reviews_by_date' => $reviewsByDate,
                    'top_reviewed_courses' => $topReviewedCourses,
                    'top_rated_courses' => $topRatedCourses,
                    'top_reviewers' => $topReviewers,
                    'recent_reviews' => $recentReviews,
                ],
                'message' => 'Review statistics retrieved successfully.'
            ]);

        } catch (\Exception $e) {
            Log::error('Review statistics error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve review statistics.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Get course rating statistics.
     */
    public function courseRatingStats($courseId)
    {
        try {
            $course = Course::findOrFail($courseId);

            $ratingStats = Review::getCourseRatingStats($courseId);

            return response()->json([
                'success' => true,
                'data' => [
                    'course' => $course->only(['id', 'title', 'slug']),
                    'rating_stats' => $ratingStats,
                ],
                'message' => 'Course rating statistics retrieved successfully.'
            ]);

        } catch (\Exception $e) {
            Log::error('Course rating stats error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve course rating statistics.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Check if user has reviewed a course.
     */
    public function checkUserReview(Request $request)
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

            $hasReviewed = Review::hasUserReviewed($userId, $courseId);
            $review = null;

            if ($hasReviewed) {
                $review = Review::getUserReview($userId, $courseId);
                if ($review) {
                    $review->stars = $review->stars;
                    $review->rating_text = $review->rating_text;
                    $review->formatted_date = $review->formatted_date;
                }
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'has_reviewed' => $hasReviewed,
                    'review' => $review,
                    'user_id' => $userId,
                    'course_id' => $courseId,
                ],
                'message' => 'Review status retrieved.'
            ]);

        } catch (\Exception $e) {
            Log::error('Check user review error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to check review status.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Check if current user has reviewed a course.
     */
    public function checkSelfReview(Request $request)
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

            $hasReviewed = Review::hasUserReviewed($user->id, $courseId);
            $review = null;

            if ($hasReviewed) {
                $review = Review::getUserReview($user->id, $courseId);
                if ($review) {
                    $review->stars = $review->stars;
                    $review->rating_text = $review->rating_text;
                    $review->formatted_date = $review->formatted_date;
                    $review->time_ago = $review->time_ago;
                }
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'has_reviewed' => $hasReviewed,
                    'review' => $review,
                    'course_id' => $courseId,
                ],
                'message' => 'Your review status retrieved.'
            ]);

        } catch (\Exception $e) {
            Log::error('Check self review error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to check your review status.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Get top rated courses.
     */
    public function topRatedCourses(Request $request)
    {
        try {
            $limit = $request->get('limit', 10);
            $minReviews = $request->get('min_reviews', 1);
            $minRating = $request->get('min_rating', 0);

            $query = Course::withAvg('reviews', 'rating')
                ->withCount('reviews')
                ->has('reviews');

            if ($minReviews > 0) {
                $query->having('reviews_count', '>=', $minReviews);
            }

            if ($minRating > 0) {
                $query->having('reviews_avg_rating', '>=', $minRating);
            }

            $topCourses = $query->orderByDesc('reviews_avg_rating')
                ->orderByDesc('reviews_count')
                ->limit($limit)
                ->get()
                ->map(function ($course) {
                    return [
                        'course' => $course->only(['id', 'title', 'slug', 'thumbnail_url', 'level_name', 'instructor_id']),
                        'average_rating' => round($course->reviews_avg_rating, 1),
                        'reviews_count' => $course->reviews_count,
                        'stars' => str_repeat('★', round($course->reviews_avg_rating)) . str_repeat('☆', 5 - round($course->reviews_avg_rating)),
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => [
                    'top_courses' => $topCourses,
                    'limit' => $limit,
                    'min_reviews' => $minReviews,
                    'min_rating' => $minRating,
                ],
                'message' => 'Top rated courses retrieved successfully.'
            ]);

        } catch (\Exception $e) {
            Log::error('Top rated courses error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve top rated courses.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Get recent reviews.
     */
    public function recentReviews(Request $request)
    {
        try {
            $limit = $request->get('limit', 10);

            $recentReviews = Review::with(['user', 'course'])
                ->latest()
                ->limit($limit)
                ->get()
                ->map(function ($review) {
                    $review->stars = $review->stars;
                    $review->rating_text = $review->rating_text;
                    $review->formatted_date = $review->formatted_date;
                    $review->time_ago = $review->time_ago;
                    $review->has_comment = $review->hasComment();
                    $review->excerpt = $review->excerpt(100);
                    return $review;
                });

            return response()->json([
                'success' => true,
                'data' => [
                    'recent_reviews' => $recentReviews,
                    'limit' => $limit,
                ],
                'message' => 'Recent reviews retrieved successfully.'
            ]);

        } catch (\Exception $e) {
            Log::error('Recent reviews error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve recent reviews.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Bulk create reviews (for testing or admin purposes).
     */
    public function bulkCreate(Request $request)
    {
        DB::beginTransaction();

        try {
            $validator = Validator::make($request->all(), [
                'reviews' => 'required|array|min:1',
                'reviews.*.user_id' => 'required|exists:users,id',
                'reviews.*.course_id' => 'required|exists:courses,id',
                'reviews.*.rating' => 'required|integer|min:1|max:5',
                'reviews.*.comment' => 'nullable|string|max:1000',
                'skip_existing' => 'boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors' => $validator->errors()
                ], 422);
            }

            $createdCount = 0;
            $skippedCount = 0;
            $failedCount = 0;
            $errors = [];

            foreach ($request->reviews as $reviewData) {
                try {
                    // Check if user is enrolled in the course
                    $course = Course::find($reviewData['course_id']);
                    $isEnrolled = $course->hasUserEnrolled($reviewData['user_id']);
                    
                    if (!$isEnrolled && !$request->has('force')) {
                        $failedCount++;
                        $errors[] = "User {$reviewData['user_id']} is not enrolled in course {$reviewData['course_id']}.";
                        continue;
                    }

                    // Check if review already exists
                    $existingReview = Review::where('user_id', $reviewData['user_id'])
                        ->where('course_id', $reviewData['course_id'])
                        ->first();

                    if ($existingReview) {
                        if ($request->skip_existing) {
                            $skippedCount++;
                            continue;
                        } else {
                            $failedCount++;
                            $errors[] = "User {$reviewData['user_id']} has already reviewed course {$reviewData['course_id']}.";
                            continue;
                        }
                    }

                    Review::create($reviewData);
                    $createdCount++;
                } catch (\Exception $e) {
                    $failedCount++;
                    $errors[] = "Failed to create review for user {$reviewData['user_id']} on course {$reviewData['course_id']}: " . $e->getMessage();
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Bulk create completed.',
                'data' => [
                    'created_count' => $createdCount,
                    'skipped_count' => $skippedCount,
                    'failed_count' => $failedCount,
                    'errors' => $errors,
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Bulk create reviews error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to perform bulk create.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Bulk delete reviews.
     */
    public function bulkDestroy(Request $request)
    {
        DB::beginTransaction();

        try {
            $validator = Validator::make($request->all(), [
                'ids' => 'required|array',
                'ids.*' => 'exists:reviews,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors' => $validator->errors()
                ], 422);
            }

            $deletedCount = Review::whereIn('id', $request->ids)->delete();

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
            Log::error('Bulk delete reviews error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to perform bulk delete.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Get instructor's course reviews.
     */
    public function instructorCourseReviews($instructorId)
    {
        try {
            $instructor = User::findOrFail($instructorId);

            $courses = Course::where('instructor_id', $instructorId)->pluck('id');
            
            $reviews = Review::whereIn('course_id', $courses)
                ->with(['user', 'course'])
                ->latest()
                ->paginate(15);

            // Add computed attributes
            $reviews->transform(function ($review) {
                $review->stars = $review->stars;
                $review->rating_text = $review->rating_text;
                $review->formatted_date = $review->formatted_date;
                $review->time_ago = $review->time_ago;
                $review->has_comment = $review->hasComment();
                $review->excerpt = $review->excerpt(100);
                return $review;
            });

            // Get statistics for instructor's courses
            $totalReviews = Review::whereIn('course_id', $courses)->count();
            $averageRating = Review::whereIn('course_id', $courses)->avg('rating');

            return response()->json([
                'success' => true,
                'data' => [
                    'instructor' => $instructor->only(['id', 'name', 'email']),
                    'reviews' => $reviews,
                    'statistics' => [
                        'total_reviews' => $totalReviews,
                        'average_rating' => round($averageRating, 1),
                        'courses_count' => $courses->count(),
                    ],
                ],
                'message' => 'Instructor course reviews retrieved successfully.'
            ]);

        } catch (\Exception $e) {
            Log::error('Instructor course reviews error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve instructor course reviews.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
}