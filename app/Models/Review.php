<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class Review extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'course_id',
        'rating',
        'comment',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'rating' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Minimum and maximum rating values.
     */
    public const MIN_RATING = 1;
    public const MAX_RATING = 5;

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Validate rating range
        static::creating(function ($review) {
            $review->rating = max(self::MIN_RATING, min(self::MAX_RATING, $review->rating));
        });

        static::updating(function ($review) {
            if ($review->isDirty('rating')) {
                $review->rating = max(self::MIN_RATING, min(self::MAX_RATING, $review->rating));
            }
        });
    }

    /**
     * Get the user that owns the review.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the course that owns the review.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * Scope a query to only include reviews for a specific course.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $courseId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForCourse(Builder $query, int $courseId): Builder
    {
        return $query->where('course_id', $courseId);
    }

    /**
     * Scope a query to only include reviews by a specific user.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $userId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope a query to only include reviews with a specific rating or higher.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $minRating
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithRatingAtLeast(Builder $query, int $minRating): Builder
    {
        return $query->where('rating', '>=', $minRating);
    }

    /**
     * Scope a query to only include reviews with a specific rating.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $rating
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithRating(Builder $query, int $rating): Builder
    {
        return $query->where('rating', $rating);
    }

    /**
     * Scope a query to only include reviews that have comments.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithComments(Builder $query): Builder
    {
        return $query->whereNotNull('comment')->where('comment', '!=', '');
    }

    /**
     * Scope a query to order by latest reviews.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeLatest(Builder $query): Builder
    {
        return $query->orderByDesc('created_at');
    }

    /**
     * Scope a query to order by highest rating.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeHighestRated(Builder $query): Builder
    {
        return $query->orderByDesc('rating')->orderByDesc('created_at');
    }

    /**
     * Scope a query to order by lowest rating.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeLowestRated(Builder $query): Builder
    {
        return $query->orderBy('rating')->orderByDesc('created_at');
    }

    /**
     * Check if the review has a comment.
     *
     * @return bool
     */
    public function hasComment(): bool
    {
        return !empty($this->comment);
    }

    /**
     * Get the excerpt of the comment.
     *
     * @param int $length
     * @return string
     */
    public function excerpt(int $length = 100): string
    {
        if (!$this->hasComment()) {
            return '';
        }

        return \Illuminate\Support\Str::limit(strip_tags($this->comment), $length);
    }

    /**
     * Get the formatted rating as stars.
     *
     * @return string
     */
    public function getStarsAttribute(): string
    {
        $fullStars = str_repeat('★', $this->rating);
        $emptyStars = str_repeat('☆', self::MAX_RATING - $this->rating);
        return $fullStars . $emptyStars;
    }

    /**
     * Get the formatted rating text.
     *
     * @return string
     */
    public function getRatingTextAttribute(): string
    {
        $ratingTexts = [
            1 => 'Poor',
            2 => 'Fair',
            3 => 'Good',
            4 => 'Very Good',
            5 => 'Excellent',
        ];

        return $ratingTexts[$this->rating] ?? 'Unknown';
    }

    /**
     * Get the formatted created date.
     *
     * @return string
     */
    public function getFormattedDateAttribute(): string
    {
        return $this->created_at->format('F j, Y');
    }

    /**
     * Get human-readable time since review.
     *
     * @return string
     */
    public function getTimeAgoAttribute(): string
    {
        return $this->created_at->diffForHumans();
    }

    /**
     * Check if the review is recent (within the last 7 days).
     *
     * @return bool
     */
    public function isRecent(): bool
    {
        return $this->created_at->greaterThan(now()->subDays(7));
    }

    /**
     * Check if the review is by the authenticated user.
     *
     * @return bool
     */
    public function isByCurrentUser(): bool
    {
        $user = auth()->user();
        return $user && $this->user_id === $user->id;
    }

    /**
     * Create or update a review.
     *
     * @param int $userId
     * @param int $courseId
     * @param int $rating
     * @param string|null $comment
     * @return Review
     */
    public static function createOrUpdate(int $userId, int $courseId, int $rating, ?string $comment = null): Review
    {
        $review = static::firstOrNew([
            'user_id' => $userId,
            'course_id' => $courseId,
        ]);

        $review->rating = $rating;
        $review->comment = $comment;
        $review->save();

        return $review;
    }

    /**
     * Get average rating for a course.
     *
     * @param int $courseId
     * @return array
     */
    public static function getCourseRatingStats(int $courseId): array
    {
        $reviews = static::forCourse($courseId);

        $total = $reviews->count();
        
        if ($total === 0) {
            return [
                'average' => 0,
                'total' => 0,
                'distribution' => array_fill(1, 5, 0),
                'percentage_distribution' => array_fill(1, 5, 0),
            ];
        }

        $average = $reviews->avg('rating');
        $distribution = [];

        for ($i = 1; $i <= 5; $i++) {
            $count = $reviews->withRating($i)->count();
            $distribution[$i] = $count;
        }

        $percentageDistribution = array_map(function ($count) use ($total) {
            return ($count / $total) * 100;
        }, $distribution);

        return [
            'average' => round($average, 1),
            'total' => $total,
            'distribution' => $distribution,
            'percentage_distribution' => $percentageDistribution,
        ];
    }

    /**
     * Check if a user has reviewed a course.
     *
     * @param int $userId
     * @param int $courseId
     * @return bool
     */
    public static function hasUserReviewed(int $userId, int $courseId): bool
    {
        return static::where('user_id', $userId)
            ->where('course_id', $courseId)
            ->exists();
    }

    /**
     * Get user's review for a course.
     *
     * @param int $userId
     * @param int $courseId
     * @return Review|null
     */
    public static function getUserReview(int $userId, int $courseId): ?Review
    {
        return static::where('user_id', $userId)
            ->where('course_id', $courseId)
            ->first();
    }

    /**
     * Get recent reviews with pagination.
     *
     * @param int $limit
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public static function getRecentReviews(int $limit = 10): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return static::with(['user', 'course'])
            ->latest()
            ->paginate($limit);
    }

    /**
     * Get top-rated courses based on reviews.
     *
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getTopRatedCourses(int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        return Course::withAvg('reviews', 'rating')
            ->withCount('reviews')
            ->having('reviews_avg_rating', '>', 0)
            ->orderByDesc('reviews_avg_rating')
            ->orderByDesc('reviews_count')
            ->limit($limit)
            ->get();
    }
}