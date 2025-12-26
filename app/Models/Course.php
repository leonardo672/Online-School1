<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Collection;

class Course extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'category_id',
        'instructor_id',
        'title',
        'slug',
        'description',
        'price',
        'level',
        'published',
        'thumbnail',
        'duration',
        'discount_price',
        'discount_end_date',
        'prerequisites',
        'learning_outcomes',
        'certificate_available',
        'certificate_template', // Added: certificate template identifier
        'certificate_validity_years', // Added: how long certificate is valid
        'language', // Added: course language
        'subtitle', // Added: course subtitle
        'whats_included', // Added: what's included in the course
        'target_audience', // Added: who this course is for
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'price' => 'decimal:2',
        'discount_price' => 'decimal:2',
        'published' => 'boolean',
        'duration' => 'integer',
        'discount_end_date' => 'datetime',
        'certificate_available' => 'boolean',
        'certificate_validity_years' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * The allowed levels for a course.
     */
    public const LEVELS = [
        'beginner' => 'Beginner',
        'intermediate' => 'Intermediate',
        'advanced' => 'Advanced',
    ];

    /**
     * Default certificate validity years.
     */
    public const DEFAULT_CERTIFICATE_VALIDITY = 2;

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Automatically generate slug from title when creating
        static::creating(function ($course) {
            if (empty($course->slug)) {
                $course->slug = $course->generateUniqueSlug($course->title);
            }

            // Set default certificate validity if not set
            if ($course->certificate_available && empty($course->certificate_validity_years)) {
                $course->certificate_validity_years = self::DEFAULT_CERTIFICATE_VALIDITY;
            }
        });

        // Automatically update slug when title changes
        static::updating(function ($course) {
            if ($course->isDirty('title') && empty($course->slug)) {
                $course->slug = $course->generateUniqueSlug($course->title, $course->id);
            }
        });
    }

    /**
     * Generate a unique slug for the course.
     *
     * @param string $title
     * @param int|null $excludeId
     * @return string
     */
    private function generateUniqueSlug(string $title, int $excludeId = null): string
    {
        $slug = Str::slug($title);
        $originalSlug = $slug;
        $count = 1;

        // Check if slug already exists (excluding current course if updating)
        $query = static::where('slug', $slug);
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        while ($query->exists()) {
            $slug = $originalSlug . '-' . $count++;
            $query = static::where('slug', $slug);
            if ($excludeId) {
                $query->where('id', '!=', $excludeId);
            }
        }

        return $slug;
    }

    /**
     * Get the route key for the model.
     *
     * @return string
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    // ====================
    // Relationships
    // ====================

    /**
     * Get the instructor of the course.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function instructor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }

    /**
     * Get the category of the course.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the lessons for the course.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function lessons(): HasMany
    {
        return $this->hasMany(Lesson::class)->orderBy('position');
    }

    /**
     * Get the enrollments for the course.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    /**
     * Get the enrolled users for the course.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function enrolledUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'enrollments')
            ->withTimestamps()
            ->withPivot('enrolled_at')
            ->orderByDesc('enrollments.enrolled_at');
    }

    /**
     * Get the reviews for the course.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    /**
     * Get the payments for the course.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Get completed payments for the course.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function completedPayments(): HasMany
    {
        return $this->payments()->where('status', 'completed');
    }

    /**
     * Get the certificates issued for this course.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function certificates(): HasMany
    {
        return $this->hasMany(Certificate::class);
    }

    // ====================
    // Scopes
    // ====================

    /**
     * Scope a query to only include published courses.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePublished($query)
    {
        return $query->where('published', true);
    }

    /**
     * Scope a query to only include draft courses.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeDraft($query)
    {
        return $query->where('published', false);
    }

    /**
     * Scope a query to filter by level.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $level
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeLevel($query, string $level)
    {
        return $query->where('level', $level);
    }

    /**
     * Scope a query to only include free courses.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFree($query)
    {
        return $query->where('price', 0);
    }

    /**
     * Scope a query to only include paid courses.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePaid($query)
    {
        return $query->where('price', '>', 0);
    }

    /**
     * Scope a query to only include courses with discount.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeDiscounted($query)
    {
        return $query->whereNotNull('discount_price')
            ->where('discount_price', '>', 0)
            ->where(function ($q) {
                $q->whereNull('discount_end_date')
                    ->orWhere('discount_end_date', '>', now());
            });
    }

    /**
     * Scope a query to only include courses with minimum rating.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param float $minRating
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithRatingAtLeast($query, float $minRating)
    {
        return $query->whereHas('reviews', function ($q) use ($minRating) {
            $q->select('course_id')
                ->groupBy('course_id')
                ->havingRaw('AVG(rating) >= ?', [$minRating]);
        });
    }

    /**
     * Scope a query to order by rating.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $direction
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOrderByRating($query, string $direction = 'desc')
    {
        return $query->withAvg('reviews', 'rating')
            ->orderBy('reviews_avg_rating', $direction)
            ->orderBy('reviews_count', $direction);
    }

    /**
     * Scope a query to order by enrollment count.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $direction
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOrderByPopularity($query, string $direction = 'desc')
    {
        return $query->withCount('enrollments')
            ->orderBy('enrollments_count', $direction);
    }

    /**
     * Scope a query to order by revenue.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $direction
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOrderByRevenue($query, string $direction = 'desc')
    {
        return $query->withSum('completedPayments', 'amount')
            ->orderBy('completed_payments_sum_amount', $direction);
    }

    /**
     * Scope a query to order by certificate issuance.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $direction
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOrderByCertificates($query, string $direction = 'desc')
    {
        return $query->withCount('certificates')
            ->orderBy('certificates_count', $direction);
    }

    /**
     * Scope a query to only include courses by a specific instructor.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $instructorId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByInstructor($query, int $instructorId)
    {
        return $query->where('instructor_id', $instructorId);
    }

    /**
     * Scope a query to only include courses in a specific category.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $categoryId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeInCategory($query, int $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    /**
     * Scope a query to only include courses with certificate.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithCertificate($query)
    {
        return $query->where('certificate_available', true);
    }

    /**
     * Scope a query to only include courses created within a date range.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $startDate
     * @param string $endDate
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCreatedBetween($query, string $startDate, string $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Scope a query to only include featured courses.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFeatured($query)
    {
        return $query->published()
            ->withRatingAtLeast(4.0)
            ->orderByPopularity()
            ->limit(10);
    }

    /**
     * Scope a query to only include new courses (created within last 30 days).
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeNewArrivals($query)
    {
        return $query->published()
            ->where('created_at', '>=', now()->subDays(30))
            ->orderBy('created_at', 'desc');
    }

    /**
     * Scope a query to only include trending courses (high enrollment growth).
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeTrending($query)
    {
        return $query->published()
            ->whereHas('enrollments', function ($q) {
                $q->where('enrolled_at', '>=', now()->subDays(7));
            })
            ->withCount(['enrollments' => function ($q) {
                $q->where('enrolled_at', '>=', now()->subDays(7));
            }])
            ->orderBy('enrollments_count', 'desc')
            ->limit(10);
    }

    /**
     * Scope a query to only include courses with certificates issued.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $minCertificates
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithCertificatesIssued($query, int $minCertificates = 1)
    {
        return $query->whereHas('certificates')
            ->withCount('certificates')
            ->having('certificates_count', '>=', $minCertificates);
    }

    // ====================
    // Accessors
    // ====================

    /**
     * Get the current price (considering discount).
     *
     * @return float
     */
    public function getCurrentPriceAttribute(): float
    {
        if ($this->hasDiscount()) {
            return $this->discount_price;
        }
        return $this->price;
    }

    /**
     * Get the formatted current price.
     *
     * @return string
     */
    public function getFormattedCurrentPriceAttribute(): string
    {
        if ($this->isFree()) {
            return 'Free';
        }
        return '$' . number_format($this->current_price, 2);
    }

    /**
     * Get the formatted original price.
     *
     * @return string
     */
    public function getFormattedPriceAttribute(): string
    {
        if ($this->isFree()) {
            return 'Free';
        }
        return '$' . number_format($this->price, 2);
    }

    /**
     * Get the discount percentage.
     *
     * @return float|null
     */
    public function getDiscountPercentageAttribute(): ?float
    {
        if (!$this->hasDiscount() || $this->price == 0) {
            return null;
        }

        $discount = (($this->price - $this->discount_price) / $this->price) * 100;
        return round($discount, 0);
    }

    /**
     * Get the amount saved.
     *
     * @return float|null
     */
    public function getAmountSavedAttribute(): ?float
    {
        if (!$this->hasDiscount()) {
            return null;
        }
        return $this->price - $this->discount_price;
    }

    /**
     * Get formatted amount saved.
     *
     * @return string|null
     */
    public function getFormattedAmountSavedAttribute(): ?string
    {
        $amount = $this->amount_saved;
        return $amount ? '$' . number_format($amount, 2) : null;
    }

    /**
     * Get the human-readable level name.
     *
     * @return string
     */
    public function getLevelNameAttribute(): string
    {
        return self::LEVELS[$this->level] ?? ucfirst($this->level);
    }

    /**
     * Get the lessons count.
     *
     * @return int
     */
    public function getLessonsCountAttribute(): int
    {
        return $this->lessons()->count();
    }

    /**
     * Get the enrollments count.
     *
     * @return int
     */
    public function getEnrollmentsCountAttribute(): int
    {
        return $this->enrollments()->count();
    }

    /**
     * Get the average rating for the course.
     *
     * @return float
     */
    public function getAverageRatingAttribute(): float
    {
        return $this->reviews()->avg('rating') ?? 0;
    }

    /**
     * Get the reviews count.
     *
     * @return int
     */
    public function getReviewsCountAttribute(): int
    {
        return $this->reviews()->count();
    }

    /**
     * Get the rating stats for the course.
     *
     * @return array
     */
    public function getRatingStatsAttribute(): array
    {
        return Review::getCourseRatingStats($this->id);
    }

    /**
     * Get the thumbnail URL.
     *
     * @return string
     */
    public function getThumbnailUrlAttribute(): string
    {
        if ($this->thumbnail) {
            return asset('storage/' . $this->thumbnail);
        }

        // Default course thumbnail
        return asset('images/default-course-thumbnail.jpg');
    }

    /**
     * Get the estimated total duration in minutes.
     *
     * @return int
     */
    public function getTotalDurationAttribute(): int
    {
        if ($this->duration) {
            return $this->duration * 60; // Convert hours to minutes
        }

        // Calculate from lessons (assuming 10 minutes per lesson as default)
        return $this->lessons_count * 10;
    }

    /**
     * Get formatted duration.
     *
     * @return string
     */
    public function getFormattedDurationAttribute(): string
    {
        $totalMinutes = $this->total_duration;
        
        if ($totalMinutes >= 60) {
            $hours = floor($totalMinutes / 60);
            $minutes = $totalMinutes % 60;
            
            if ($minutes > 0) {
                return "{$hours}h {$minutes}m";
            }
            return "{$hours}h";
        }
        
        return "{$totalMinutes}m";
    }

    /**
     * Get course revenue.
     *
     * @return float
     */
    public function getRevenueAttribute(): float
    {
        return $this->completedPayments()->sum('amount');
    }

    /**
     * Get formatted revenue.
     *
     * @return string
     */
    public function getFormattedRevenueAttribute(): string
    {
        $revenue = $this->revenue;
        if ($revenue >= 1000) {
            return '$' . number_format($revenue / 1000, 1) . 'K';
        }
        return '$' . number_format($revenue, 2);
    }

    /**
     * Get total purchases count.
     *
     * @return int
     */
    public function getPurchaseCountAttribute(): int
    {
        return $this->completedPayments()->count();
    }

    /**
     * Get certificates count.
     *
     * @return int
     */
    public function getCertificatesCountAttribute(): int
    {
        return $this->certificates()->count();
    }

    /**
     * Get certificate validity years.
     *
     * @return int|null
     */
    public function getCertificateValidityYearsAttribute(): ?int
    {
        if (!$this->certificate_available) {
            return null;
        }

        return $this->attributes['certificate_validity_years'] ?? self::DEFAULT_CERTIFICATE_VALIDITY;
    }

    /**
     * Get excerpt of description.
     *
     * @param int $length
     * @return string
     */
    public function excerpt(int $length = 100): string
    {
        return Str::limit(strip_tags($this->description), $length);
    }

    /**
     * Get prerequisites as array.
     *
     * @return array
     */
    public function getPrerequisitesArrayAttribute(): array
    {
        if (empty($this->prerequisites)) {
            return [];
        }

        return array_filter(
            array_map('trim', explode(',', $this->prerequisites))
        );
    }

    /**
     * Get learning outcomes as array.
     *
     * @return array
     */
    public function getLearningOutcomesArrayAttribute(): array
    {
        if (empty($this->learning_outcomes)) {
            return [];
        }

        return array_filter(
            array_map('trim', explode("\n", $this->learning_outcomes))
        );
    }

    /**
     * Get what's included as array.
     *
     * @return array
     */
    public function getWhatsIncludedArrayAttribute(): array
    {
        if (empty($this->whats_included)) {
            return [];
        }

        return array_filter(
            array_map('trim', explode("\n", $this->whats_included))
        );
    }

    /**
     * Get target audience as array.
     *
     * @return array
     */
    public function getTargetAudienceArrayAttribute(): array
    {
        if (empty($this->target_audience)) {
            return [];
        }

        return array_filter(
            array_map('trim', explode("\n", $this->target_audience))
        );
    }

    /**
     * Get course status.
     *
     * @return string
     */
    public function getStatusAttribute(): string
    {
        if (!$this->published) {
            return 'draft';
        }

        if ($this->hasDiscount()) {
            return 'discounted';
        }

        if ($this->isFree()) {
            return 'free';
        }

        return 'published';
    }

    /**
     * Get certificate analytics for the course.
     *
     * @return array
     */
    public function getCertificateAnalyticsAttribute(): array
    {
        return Certificate::getAnalytics($this->id);
    }

    // ====================
    // Methods
    // ====================

    /**
     * Check if the course is free.
     *
     * @return bool
     */
    public function isFree(): bool
    {
        return $this->price == 0;
    }

    /**
     * Check if the course has discount.
     *
     * @return bool
     */
    public function hasDiscount(): bool
    {
        if (!$this->discount_price || $this->discount_price >= $this->price) {
            return false;
        }

        if ($this->discount_end_date && $this->discount_end_date->isPast()) {
            return false;
        }

        return true;
    }

    /**
     * Check if the course is published.
     *
     * @return bool
     */
    public function isPublished(): bool
    {
        return $this->published;
    }

    /**
     * Check if discount is expired.
     *
     * @return bool
     */
    public function isDiscountExpired(): bool
    {
        return $this->discount_end_date && $this->discount_end_date->isPast();
    }

    /**
     * Check if course offers certificates.
     *
     * @return bool
     */
    public function offersCertificate(): bool
    {
        return $this->certificate_available;
    }

    /**
     * Get the first lesson.
     *
     * @return Lesson|null
     */
    public function firstLesson(): ?Lesson
    {
        return $this->lessons()->orderBy('position')->first();
    }

    /**
     * Get the top reviews for the course.
     *
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getTopReviews(int $limit = 5): Collection
    {
        return $this->reviews()
            ->with('user')
            ->highestRated()
            ->limit($limit)
            ->get();
    }

    /**
     * Check if a specific user has reviewed this course.
     *
     * @param int $userId
     * @return bool
     */
    public function hasUserReviewed(int $userId): bool
    {
        return $this->reviews()->where('user_id', $userId)->exists();
    }

    /**
     * Get a user's review for this course.
     *
     * @param int $userId
     * @return Review|null
     */
    public function getUserReview(int $userId): ?Review
    {
        return $this->reviews()->where('user_id', $userId)->first();
    }

    /**
     * Check if a user is enrolled in this course.
     *
     * @param int $userId
     * @return bool
     */
    public function hasUserEnrolled(int $userId): bool
    {
        return $this->enrollments()->where('user_id', $userId)->exists();
    }

    /**
     * Check if a user has paid for this course.
     *
     * @param int $userId
     * @return bool
     */
    public function hasUserPaid(int $userId): bool
    {
        return $this->completedPayments()->where('user_id', $userId)->exists();
    }

    /**
     * Check if a user has certificate for this course.
     *
     * @param int $userId
     * @return bool
     */
    public function hasUserCertificate(int $userId): bool
    {
        return $this->certificates()->where('user_id', $userId)->exists();
    }

    /**
     * Get user's certificate for this course.
     *
     * @param int $userId
     * @return Certificate|null
     */
    public function getUserCertificate(int $userId): ?Certificate
    {
        return $this->certificates()->where('user_id', $userId)->first();
    }

    /**
     * Get the latest enrollments.
     *
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function latestEnrollments(int $limit = 10): Collection
    {
        return $this->enrollments()
            ->with('user')
            ->latestFirst()
            ->limit($limit)
            ->get();
    }

    /**
     * Get recent purchases for the course.
     *
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getRecentPurchases(int $limit = 10): Collection
    {
        return $this->completedPayments()
            ->with('user')
            ->latest()
            ->limit($limit)
            ->get();
    }

    /**
     * Get recent certificates issued for the course.
     *
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getRecentCertificates(int $limit = 10): Collection
    {
        return $this->certificates()
            ->with('user')
            ->latest()
            ->limit($limit)
            ->get();
    }

    /**
     * Get course completion percentage for a user.
     *
     * @param int $userId
     * @return float
     */
    public function getUserCompletionPercentage(int $userId): float
    {
        $totalLessons = $this->lessons_count;
        if ($totalLessons === 0) {
            return 0;
        }

        $completedLessons = LessonProgress::where('user_id', $userId)
            ->whereHas('lesson', function ($query) {
                $query->where('course_id', $this->id);
            })
            ->where('completed', true)
            ->count();

        return ($completedLessons / $totalLessons) * 100;
    }

    /**
     * Apply discount to the course.
     *
     * @param float $discountPrice
     * @param \DateTime|null $endDate
     * @return bool
     */
    public function applyDiscount(float $discountPrice, ?\DateTime $endDate = null): bool
    {
        if ($discountPrice >= $this->price) {
            return false;
        }

        return $this->update([
            'discount_price' => $discountPrice,
            'discount_end_date' => $endDate,
        ]);
    }

    /**
     * Remove discount from the course.
     *
     * @return bool
     */
    public function removeDiscount(): bool
    {
        return $this->update([
            'discount_price' => null,
            'discount_end_date' => null,
        ]);
    }

    /**
     * Enable certificate for the course.
     *
     * @param int|null $validityYears
     * @param string|null $template
     * @return bool
     */
    public function enableCertificate(?int $validityYears = null, ?string $template = null): bool
    {
        $updates = ['certificate_available' => true];

        if ($validityYears) {
            $updates['certificate_validity_years'] = $validityYears;
        }

        if ($template) {
            $updates['certificate_template'] = $template;
        }

        return $this->update($updates);
    }

    /**
     * Disable certificate for the course.
     *
     * @return bool
     */
    public function disableCertificate(): bool
    {
        return $this->update([
            'certificate_available' => false,
            'certificate_validity_years' => null,
            'certificate_template' => null,
        ]);
    }

    /**
     * Publish the course.
     *
     * @return bool
     */
    public function publish(): bool
    {
        return $this->update(['published' => true]);
    }

    /**
     * Unpublish the course.
     *
     * @return bool
     */
    public function unpublish(): bool
    {
        return $this->update(['published' => false]);
    }

    /**
     * Toggle publish status.
     *
     * @return bool
     */
    public function togglePublish(): bool
    {
        return $this->update(['published' => !$this->published]);
    }

    /**
     * Get similar courses (by category or level).
     *
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getSimilarCourses(int $limit = 4): Collection
    {
        return static::published()
            ->where('id', '!=', $this->id)
            ->where(function ($query) {
                $query->where('category_id', $this->category_id)
                    ->orWhere('level', $this->level);
            })
            ->orderByRating()
            ->limit($limit)
            ->get();
    }

    /**
     * Get students who completed the course.
     *
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getCompletedStudents(int $limit = 10): Collection
    {
        $enrolledUserIds = $this->enrollments()->pluck('user_id');
        
        return User::whereIn('id', $enrolledUserIds)
            ->whereHas('lessonProgress', function ($query) {
                $query->whereHas('lesson', function ($q) {
                    $q->where('course_id', $this->id);
                })
                ->where('completed', true)
                ->groupBy('user_id')
                ->havingRaw('COUNT(*) = ?', [$this->lessons_count]);
            })
            ->with(['lessonProgress' => function ($query) {
                $query->whereHas('lesson', function ($q) {
                    $q->where('course_id', $this->id);
                })
                ->where('completed', true);
            }])
            ->limit($limit)
            ->get();
    }

    /**
     * Get students eligible for certificate.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getCertificateEligibleStudents(): Collection
    {
        $enrolledUserIds = $this->enrollments()->pluck('user_id');
        
        return User::whereIn('id', $enrolledUserIds)
            ->whereDoesntHave('certificates', function ($query) {
                $query->where('course_id', $this->id);
            })
            ->whereHas('lessonProgress', function ($query) {
                $query->whereHas('lesson', function ($q) {
                    $q->where('course_id', $this->id);
                })
                ->where('completed', true)
                ->groupBy('user_id')
                ->havingRaw('COUNT(*) = ?', [$this->lessons_count]);
            })
            ->get();
    }

    /**
     * Issue certificates to all eligible students.
     *
     * @return int Number of certificates issued
     */
    public function issueCertificatesToEligibleStudents(): int
    {
        if (!$this->certificate_available) {
            return 0;
        }

        $eligibleStudents = $this->getCertificateEligibleStudents();
        $issuedCount = 0;

        foreach ($eligibleStudents as $student) {
            $certificate = Certificate::issue($student->id, $this->id);
            if ($certificate) {
                $issuedCount++;
            }
        }

        return $issuedCount;
    }

    /**
     * Issue certificate to a specific student.
     *
     * @param int $userId
     * @param array $metadata
     * @return Certificate|null
     */
    public function issueCertificateToStudent(int $userId, array $metadata = []): ?Certificate
    {
        if (!$this->certificate_available) {
            return null;
        }

        // Check if user has completed the course
        $user = User::find($userId);
        if (!$user || !$user->hasCompletedCourse($this->id)) {
            return null;
        }

        return Certificate::issue($userId, $this->id, $metadata);
    }

    /**
     * Get course statistics.
     *
     * @return array
     */
    public function getStatsAttribute(): array
    {
        return [
            'lessons' => $this->lessons_count,
            'enrollments' => $this->enrollments_count,
            'reviews' => $this->reviews_count,
            'rating' => $this->average_rating,
            'duration' => $this->formatted_duration,
            'is_free' => $this->isFree(),
            'has_discount' => $this->hasDiscount(),
            'discount_percentage' => $this->discount_percentage,
            'level' => $this->level_name,
            'revenue' => $this->formatted_revenue,
            'purchases' => $this->purchase_count,
            'certificate_available' => $this->certificate_available,
            'certificates_issued' => $this->certificates_count,
            'certificate_validity_years' => $this->certificate_validity_years,
        ];
    }

    /**
     * Get course analytics.
     *
     * @return array
     */
    public function getAnalyticsAttribute(): array
    {
        $totalStudents = $this->enrollments_count;
        $completedStudents = $this->getCompletedStudents()->count();
        $completionRate = $totalStudents > 0 ? ($completedStudents / $totalStudents) * 100 : 0;
        $certificateRate = $totalStudents > 0 ? ($this->certificates_count / $totalStudents) * 100 : 0;

        // Calculate average completion percentage
        $averageCompletion = 0;
        if ($totalStudents > 0) {
            $completionSum = 0;
            $enrollments = $this->enrollments()->with('user')->get();
            
            foreach ($enrollments as $enrollment) {
                $completionSum += $this->getUserCompletionPercentage($enrollment->user_id);
            }
            
            $averageCompletion = $completionSum / $totalStudents;
        }

        return [
            'total_students' => $totalStudents,
            'completed_students' => $completedStudents,
            'certificates_issued' => $this->certificates_count,
            'completion_rate' => round($completionRate, 2),
            'certificate_rate' => round($certificateRate, 2),
            'average_completion' => round($averageCompletion, 2),
            'revenue' => $this->revenue,
            'average_rating' => $this->average_rating,
            'total_lessons' => $this->lessons_count,
            'engagement_score' => round(($this->average_rating * 0.3) + ($completionRate * 0.3) + ($certificateRate * 0.2) + (($this->enrollments_count / max($this->created_at->diffInDays(now()), 1)) * 0.2), 2),
        ];
    }

    /**
     * Check if course is popular (based on enrollments and rating).
     *
     * @return bool
     */
    public function isPopular(): bool
    {
        return $this->enrollments_count >= 100 && $this->average_rating >= 4.0;
    }

    /**
     * Check if course is trending (recent enrollment growth).
     *
     * @return bool
     */
    public function isTrending(): bool
    {
        $recentEnrollments = $this->enrollments()
            ->where('enrolled_at', '>=', now()->subDays(7))
            ->count();

        return $recentEnrollments >= 10;
    }

    /**
     * Get course difficulty level.
     *
     * @return string
     */
    public function getDifficultyAttribute(): string
    {
        $totalLessons = $this->lessons_count;
        $avgCompletion = $this->analytics['average_completion'] ?? 0;

        if ($totalLessons >= 20 && $avgCompletion < 30) {
            return 'challenging';
        } elseif ($totalLessons >= 10 && $avgCompletion < 60) {
            return 'moderate';
        } else {
            return 'beginner_friendly';
        }
    }

    /**
     * Get course rating badge.
     *
     * @return array|null
     */
    public function getRatingBadgeAttribute(): ?array
    {
        $rating = $this->average_rating;
        $reviews = $this->reviews_count;

        if ($reviews < 5) {
            return null;
        }

        if ($rating >= 4.5) {
            return ['label' => 'Excellent', 'color' => 'success', 'icon' => '⭐'];
        } elseif ($rating >= 4.0) {
            return ['label' => 'Very Good', 'color' => 'primary', 'icon' => '👍'];
        } elseif ($rating >= 3.0) {
            return ['label' => 'Good', 'color' => 'info', 'icon' => '👌'];
        } else {
            return ['label' => 'Average', 'color' => 'warning', 'icon' => '📊'];
        }
    }

    /**
     * Get course certificate badge.
     *
     * @return array|null
     */
    public function getCertificateBadgeAttribute(): ?array
    {
        if (!$this->certificate_available) {
            return null;
        }

        if ($this->certificates_count >= 50) {
            return ['label' => 'Certificate Awarded', 'color' => 'success', 'icon' => '🏆'];
        }

        return ['label' => 'Certificate Available', 'color' => 'info', 'icon' => '📜'];
    }
}