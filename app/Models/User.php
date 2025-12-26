<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Collection;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'avatar',
        'bio',
        'phone',
        'address',
        'website',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Available user roles.
     */
    public const ROLES = [
        'admin' => 'Administrator',
        'instructor' => 'Instructor',
        'student' => 'Student',
    ];

    /**
     * Default role for new users.
     */
    public const DEFAULT_ROLE = 'student';

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Set default role when creating
        static::creating(function ($user) {
            if (empty($user->role)) {
                $user->role = self::DEFAULT_ROLE;
            }
        });
    }

    // ====================
    // Role Methods
    // ====================

    /**
     * Check if user has a specific role.
     */
    public function hasRole(string $role): bool
    {
        return $this->role === $role;
    }

    /**
     * Check if user is admin.
     */
    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    /**
     * Check if user is instructor.
     */
    public function isInstructor(): bool
    {
        return $this->hasRole('instructor');
    }

    /**
     * Check if user is student.
     */
    public function isStudent(): bool
    {
        return $this->hasRole('student');
    }

    /**
     * Get human-readable role name.
     */
    public function getRoleNameAttribute(): string
    {
        return self::ROLES[$this->role] ?? ucfirst($this->role);
    }

    /**
     * Scope a query to only include admins.
     */
    public function scopeAdmins($query)
    {
        return $query->where('role', 'admin');
    }

    /**
     * Scope a query to only include instructors.
     */
    public function scopeInstructors($query)
    {
        return $query->where('role', 'instructor');
    }

    /**
     * Scope a query to only include students.
     */
    public function scopeStudents($query)
    {
        return $query->where('role', 'student');
    }

    /**
     * Scope a query to only include verified users.
     */
    public function scopeVerified($query)
    {
        return $query->whereNotNull('email_verified_at');
    }

    /**
     * Scope a query to only include active users (logged in last 30 days).
     */
    public function scopeActive($query)
    {
        return $query->whereHas('enrollments', function ($q) {
            $q->where('enrolled_at', '>=', now()->subDays(30));
        })->orWhereHas('lessonProgress', function ($q) {
            $q->where('completed_at', '>=', now()->subDays(30));
        });
    }

    /**
     * Scope a query to only include users with payments.
     */
    public function scopeWithPayments($query)
    {
        return $query->whereHas('payments');
    }

    /**
     * Scope a query to only include users with certificates.
     */
    public function scopeWithCertificates($query)
    {
        return $query->whereHas('certificates');
    }

    // ====================
    // Relationships
    // ====================

    /**
     * Get the courses taught by the user (as instructor).
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function taughtCourses(): HasMany
    {
        return $this->hasMany(Course::class, 'instructor_id');
    }

    /**
     * Get the published courses taught by the user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function publishedTaughtCourses(): HasMany
    {
        return $this->taughtCourses()->where('published', true);
    }

    /**
     * Get the enrollments for the user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    /**
     * Get the enrolled courses for the user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function enrolledCourses(): BelongsToMany
    {
        return $this->belongsToMany(Course::class, 'enrollments')
            ->withTimestamps()
            ->withPivot('enrolled_at')
            ->orderByDesc('enrollments.enrolled_at');
    }

    /**
     * Get the published enrolled courses for the user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function publishedEnrolledCourses(): BelongsToMany
    {
        return $this->enrolledCourses()->where('published', true);
    }

    /**
     * Get the lesson progress for the user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function lessonProgress(): HasMany
    {
        return $this->hasMany(LessonProgress::class);
    }

    /**
     * Get completed lessons for the user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function completedLessons(): BelongsToMany
    {
        return $this->belongsToMany(Lesson::class, 'lesson_progress')
            ->withPivot(['completed', 'completed_at'])
            ->wherePivot('completed', true);
    }

    /**
     * Get the reviews written by the user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    /**
     * Get the payments made by the user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Get completed payments for the user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function completedPayments(): HasMany
    {
        return $this->payments()->where('status', 'completed');
    }

    /**
     * Get the certificates earned by the user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function certificates(): HasMany
    {
        return $this->hasMany(Certificate::class);
    }

    /**
     * Get valid certificates (not expired).
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function validCertificates(): HasMany
    {
        return $this->certificates()->valid();
    }

    // ====================
    // Enrollment Methods
    // ====================

    /**
     * Check if user is enrolled in a specific course.
     *
     * @param int $courseId
     * @return bool
     */
    public function isEnrolledIn(int $courseId): bool
    {
        return $this->enrollments()->where('course_id', $courseId)->exists();
    }

    /**
     * Check if user is enrolled in a specific course by slug.
     *
     * @param string $courseSlug
     * @return bool
     */
    public function isEnrolledInBySlug(string $courseSlug): bool
    {
        return $this->enrolledCourses()->where('slug', $courseSlug)->exists();
    }

    /**
     * Enroll in a course.
     *
     * @param int $courseId
     * @return Enrollment|null
     */
    public function enrollIn(int $courseId): ?Enrollment
    {
        // Check if already enrolled
        if ($this->isEnrolledIn($courseId)) {
            return null;
        }

        try {
            return Enrollment::create([
                'user_id' => $this->id,
                'course_id' => $courseId,
            ]);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Unenroll from a course.
     *
     * @param int $courseId
     * @return bool
     */
    public function unenrollFrom(int $courseId): bool
    {
        return $this->enrollments()->where('course_id', $courseId)->delete() > 0;
    }

    // ====================
    // Lesson Progress Methods
    // ====================

    /**
     * Mark a lesson as completed.
     *
     * @param int $lessonId
     * @return LessonProgress
     */
    public function completeLesson(int $lessonId): LessonProgress
    {
        return LessonProgress::updateProgress($this->id, $lessonId, true);
    }

    /**
     * Mark a lesson as incomplete.
     *
     * @param int $lessonId
     * @return bool
     */
    public function incompleteLesson(int $lessonId): bool
    {
        $progress = $this->lessonProgress()
            ->where('lesson_id', $lessonId)
            ->first();

        if ($progress) {
            return $progress->markAsIncomplete();
        }

        return false;
    }

    /**
     * Check if user has completed a lesson.
     *
     * @param int $lessonId
     * @return bool
     */
    public function hasCompletedLesson(int $lessonId): bool
    {
        return $this->lessonProgress()
            ->where('lesson_id', $lessonId)
            ->where('completed', true)
            ->exists();
    }

    /**
     * Check if user has completed all lessons in a course.
     *
     * @param int $courseId
     * @return bool
     */
    public function hasCompletedCourse(int $courseId): bool
    {
        $totalLessons = Lesson::where('course_id', $courseId)->count();
        if ($totalLessons === 0) {
            return false;
        }

        $completedLessons = $this->lessonProgress()
            ->whereHas('lesson', function ($query) use ($courseId) {
                $query->where('course_id', $courseId);
            })
            ->where('completed', true)
            ->count();

        return $completedLessons === $totalLessons;
    }

    /**
     * Get progress for a specific course.
     *
     * @param int $courseId
     * @return array
     */
    public function getCourseProgress(int $courseId): array
    {
        return LessonProgress::getCourseProgress($this->id, $courseId);
    }

    /**
     * Get next incomplete lesson in a course.
     *
     * @param int $courseId
     * @return Lesson|null
     */
    public function getNextIncompleteLesson(int $courseId): ?Lesson
    {
        return LessonProgress::getNextIncompleteLesson($this->id, $courseId);
    }

    /**
     * Get all course progress for the user.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAllCourseProgress(): Collection
    {
        $enrolledCourseIds = $this->enrolledCourses()->pluck('courses.id');
        
        $progress = [];
        foreach ($enrolledCourseIds as $courseId) {
            $progress[$courseId] = $this->getCourseProgress($courseId);
        }
        
        return collect($progress);
    }

    /**
     * Get recently completed lessons.
     *
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function recentlyCompletedLessons(int $limit = 5): Collection
    {
        return $this->completedLessons()
            ->orderByDesc('lesson_progress.completed_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Get completion streak (consecutive days with completed lessons).
     *
     * @return int
     */
    public function getCompletionStreakAttribute(): int
    {
        $recentCompletions = $this->lessonProgress()
            ->where('completed', true)
            ->whereDate('completed_at', '>=', now()->subDays(30))
            ->orderByDesc('completed_at')
            ->get()
            ->pluck('completed_at')
            ->map(fn ($date) => $date->format('Y-m-d'))
            ->unique()
            ->values();

        if ($recentCompletions->isEmpty()) {
            return 0;
        }

        $streak = 1;
        $currentDate = now()->format('Y-m-d');

        // If no completion today, start from yesterday
        if (!$recentCompletions->contains($currentDate)) {
            $currentDate = now()->subDay()->format('Y-m-d');
            $streak = 0;
        }

        foreach ($recentCompletions as $index => $completionDate) {
            if ($index === 0) continue;

            $prevDate = $recentCompletions[$index - 1];
            $dateDiff = date_diff(
                date_create($completionDate),
                date_create($prevDate)
            )->days;

            if ($dateDiff === 1) {
                $streak++;
            } else {
                break;
            }
        }

        return $streak;
    }

    // ====================
    // Certificate Methods
    // ====================

    /**
     * Get certificate count.
     *
     * @return int
     */
    public function getCertificatesCountAttribute(): int
    {
        return $this->certificates()->count();
    }

    /**
     * Get valid certificate count.
     *
     * @return int
     */
    public function getValidCertificatesCountAttribute(): int
    {
        return $this->validCertificates()->count();
    }

    /**
     * Check if user has certificate for a specific course.
     *
     * @param int $courseId
     * @return bool
     */
    public function hasCertificateForCourse(int $courseId): bool
    {
        return $this->certificates()->where('course_id', $courseId)->exists();
    }

    /**
     * Get certificate for a specific course.
     *
     * @param int $courseId
     * @return Certificate|null
     */
    public function getCertificateForCourse(int $courseId): ?Certificate
    {
        return $this->certificates()->where('course_id', $courseId)->first();
    }

    /**
     * Get recent certificates.
     *
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getRecentCertificates(int $limit = 5): Collection
    {
        return $this->certificates()
            ->with('course.instructor')
            ->latest()
            ->limit($limit)
            ->get();
    }

    /**
     * Check if user is eligible for certificate in a course.
     *
     * @param int $courseId
     * @return bool
     */
    public function isEligibleForCertificate(int $courseId): bool
    {
        // Check if already has certificate
        if ($this->hasCertificateForCourse($courseId)) {
            return false;
        }

        // Check if course offers certificates
        $course = Course::find($courseId);
        if (!$course || !$course->certificate_available) {
            return false;
        }

        // Check if user has completed the course
        return $this->hasCompletedCourse($courseId);
    }

    /**
     * Request certificate for a course.
     *
     * @param int $courseId
     * @param array $metadata
     * @return Certificate|null
     */
    public function requestCertificate(int $courseId, array $metadata = []): ?Certificate
    {
        if (!$this->isEligibleForCertificate($courseId)) {
            return null;
        }

        return Certificate::issue($this->id, $courseId, $metadata);
    }

    /**
     * Get certificate analytics for the user.
     *
     * @return array
     */
    public function getCertificateAnalyticsAttribute(): array
    {
        $total = $this->certificates_count;
        $valid = $this->validCertificatesCount;
        $expired = $total - $valid;

        return [
            'total' => $total,
            'valid' => $valid,
            'expired' => $expired,
            'validity_rate' => $total > 0 ? round(($valid / $total) * 100, 2) : 0,
            'expiry_rate' => $total > 0 ? round(($expired / $total) * 100, 2) : 0,
        ];
    }

    // ====================
    // Review Methods
    // ====================

    /**
     * Get the user's review for a specific course.
     *
     * @param int $courseId
     * @return Review|null
     */
    public function getReviewForCourse(int $courseId): ?Review
    {
        return $this->reviews()->where('course_id', $courseId)->first();
    }

    /**
     * Check if user has reviewed a specific course.
     *
     * @param int $courseId
     * @return bool
     */
    public function hasReviewedCourse(int $courseId): bool
    {
        return $this->reviews()->where('course_id', $courseId)->exists();
    }

    /**
     * Get user's average rating given.
     *
     * @return float
     */
    public function getAverageRatingGivenAttribute(): float
    {
        return $this->reviews()->avg('rating') ?? 0;
    }

    /**
     * Get user's recent reviews.
     *
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getRecentReviews(int $limit = 5): Collection
    {
        return $this->reviews()
            ->with('course')
            ->latest()
            ->limit($limit)
            ->get();
    }

    /**
     * Create or update a review for a course.
     *
     * @param int $courseId
     * @param int $rating
     * @param string|null $comment
     * @return Review
     */
    public function createOrUpdateReview(int $courseId, int $rating, ?string $comment = null): Review
    {
        return Review::createOrUpdate($this->id, $courseId, $rating, $comment);
    }

    /**
     * Delete review for a course.
     *
     * @param int $courseId
     * @return bool
     */
    public function deleteReview(int $courseId): bool
    {
        $review = $this->getReviewForCourse($courseId);
        if ($review) {
            return $review->delete();
        }
        return false;
    }

    // ====================
    // Payment Methods
    // ====================

    /**
     * Get total amount spent by user.
     *
     * @return float
     */
    public function getTotalSpentAttribute(): float
    {
        return $this->completedPayments()->sum('amount');
    }

    /**
     * Check if user has paid for a specific course.
     *
     * @param int $courseId
     * @return bool
     */
    public function hasPaidForCourse(int $courseId): bool
    {
        return Payment::hasUserPaidForCourse($this->id, $courseId);
    }

    /**
     * Get user's payment history.
     *
     * @param int $limit
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getPaymentHistory(int $limit = 10): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return Payment::getUserPaymentHistory($this->id, $limit);
    }

    /**
     * Get recent payments.
     *
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getRecentPayments(int $limit = 5): Collection
    {
        return $this->payments()
            ->with('course')
            ->latest()
            ->limit($limit)
            ->get();
    }

    /**
     * Create a payment for a course.
     *
     * @param int $courseId
     * @param float $amount
     * @param string $paymentMethod
     * @param string $status
     * @param array $metadata
     * @return Payment
     */
    public function createPayment(
        int $courseId,
        float $amount,
        string $paymentMethod = 'manual',
        string $status = 'pending',
        array $metadata = []
    ): Payment {
        return Payment::createPayment($this->id, $courseId, $amount, $paymentMethod, $status, $metadata);
    }

    /**
     * Get instructor revenue (total earnings from courses).
     *
     * @return float
     */
    public function getInstructorRevenueAttribute(): float
    {
        return Payment::getInstructorRevenue($this->id);
    }

    /**
     * Get formatted instructor revenue.
     *
     * @return string
     */
    public function getFormattedInstructorRevenueAttribute(): string
    {
        $revenue = $this->instructor_revenue;
        
        if ($revenue >= 1000) {
            return '$' . number_format($revenue / 1000, 1) . 'K';
        }
        
        return '$' . number_format($revenue, 2);
    }

    /**
     * Get user's monthly spending.
     *
     * @return float
     */
    public function getMonthlySpendingAttribute(): float
    {
        return $this->completedPayments()
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('amount');
    }

    // ====================
    // Instructor Methods
    // ====================

    /**
     * Get the total students enrolled in user's courses.
     *
     * @return int
     */
    public function getTotalStudentsAttribute(): int
    {
        return $this->taughtCourses()->withCount('enrollments')->get()->sum('enrollments_count');
    }

    /**
     * Get the total published courses count.
     *
     * @return int
     */
    public function getPublishedCoursesCountAttribute(): int
    {
        return $this->publishedTaughtCourses()->count();
    }

    /**
     * Get the total enrolled courses count.
     *
     * @return int
     */
    public function getEnrolledCoursesCountAttribute(): int
    {
        return $this->enrollments()->count();
    }

    /**
     * Get the total completed lessons count.
     *
     * @return int
     */
    public function getCompletedLessonsCountAttribute(): int
    {
        return $this->lessonProgress()->where('completed', true)->count();
    }

    /**
     * Get instructor's average course rating.
     *
     * @return float
     */
    public function getInstructorRatingAttribute(): float
    {
        $courses = $this->taughtCourses()->withAvg('reviews', 'rating')->get();
        
        if ($courses->isEmpty()) {
            return 0;
        }

        $totalRating = 0;
        $count = 0;
        
        foreach ($courses as $course) {
            if ($course->reviews_avg_rating) {
                $totalRating += $course->reviews_avg_rating;
                $count++;
            }
        }

        return $count > 0 ? round($totalRating / $count, 1) : 0;
    }

    /**
     * Get instructor's total reviews across all courses.
     *
     * @return int
     */
    public function getInstructorReviewsCountAttribute(): int
    {
        return $this->taughtCourses()->withCount('reviews')->get()->sum('reviews_count');
    }

    /**
     * Get instructor's total certificates issued across all courses.
     *
     * @return int
     */
    public function getInstructorCertificatesCountAttribute(): int
    {
        if (!$this->isInstructor()) {
            return 0;
        }

        $courseIds = $this->taughtCourses()->pluck('id');
        return Certificate::whereIn('course_id', $courseIds)->count();
    }

    /**
     * Get instructor's total revenue across all courses.
     *
     * @return float
     */
    public function getTotalRevenueAttribute(): float
    {
        if (!$this->isInstructor()) {
            return 0;
        }

        return $this->instructor_revenue;
    }

    // ====================
    // Helper Methods
    // ====================

    /**
     * Get the user's avatar URL.
     * Uses Gravatar as fallback if no avatar is set.
     *
     * @return string
     */
    public function getAvatarUrlAttribute(): string
    {
        if ($this->avatar) {
            return asset('storage/' . $this->avatar);
        }

        // Fallback to Gravatar
        $hash = md5(strtolower(trim($this->email)));
        return "https://www.gravatar.com/avatar/{$hash}?d=mp&s=200";
    }

    /**
     * Get user's initials for avatar placeholder.
     *
     * @return string
     */
    public function getInitialsAttribute(): string
    {
        $words = explode(' ', $this->name);
        $initials = '';

        foreach ($words as $word) {
            if (!empty($word)) {
                $initials .= strtoupper($word[0]);
            }
            if (strlen($initials) >= 2) {
                break;
            }
        }

        return $initials ?: 'U';
    }

    /**
     * Check if user can access a course.
     * Users can access if they are enrolled, are the instructor, or are admin.
     *
     * @param int $courseId
     * @return bool
     */
    public function canAccessCourse(int $courseId): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        // Check if user is the instructor of this course
        if ($this->taughtCourses()->where('id', $courseId)->exists()) {
            return true;
        }

        // Check if user is enrolled
        return $this->isEnrolledIn($courseId);
    }

    /**
     * Check if user can edit a course.
     * Users can edit if they are the instructor or are admin.
     *
     * @param Course $course
     * @return bool
     */
    public function canEditCourse(Course $course): bool
    {
        return $this->isAdmin() || $this->id === $course->instructor_id;
    }

    /**
     * Check if user can view lesson progress.
     * Users can view if they are admin, instructor of the course, or the student themselves.
     *
     * @param int $userId
     * @param int $courseId
     * @return bool
     */
    public function canViewProgress(int $userId, int $courseId): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        if ($this->id === $userId) {
            return true;
        }

        // Check if user is the instructor of this course
        return $this->taughtCourses()->where('id', $courseId)->exists();
    }

    /**
     * Check if user can delete a review.
     * Users can delete if they are admin, wrote the review, or are instructor of the course.
     *
     * @param Review $review
     * @return bool
     */
    public function canDeleteReview(Review $review): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        if ($this->id === $review->user_id) {
            return true;
        }

        // Check if user is the instructor of the course being reviewed
        return $this->taughtCourses()->where('id', $review->course_id)->exists();
    }

    /**
     * Check if user can refund a payment.
     * Users can refund if they are admin or instructor of the course.
     *
     * @param Payment $payment
     * @return bool
     */
    public function canRefundPayment(Payment $payment): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        // Check if user is the instructor of the course
        return $this->taughtCourses()->where('id', $payment->course_id)->exists();
    }

    /**
     * Check if user can issue certificate for a course.
     * Users can issue if they are admin or instructor of the course.
     *
     * @param int $courseId
     * @return bool
     */
    public function canIssueCertificate(int $courseId): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        // Check if user is the instructor of the course
        return $this->taughtCourses()->where('id', $courseId)->exists();
    }

    /**
     * Get user's learning statistics.
     *
     * @return array
     */
    public function getLearningStatsAttribute(): array
    {
        return [
            'enrolled_courses' => $this->enrolled_courses_count,
            'completed_lessons' => $this->completed_lessons_count,
            'completion_streak' => $this->completion_streak,
            'certificates' => $this->certificates_count,
            'valid_certificates' => $this->valid_certificates_count,
            'taught_courses' => $this->published_courses_count,
            'total_students' => $this->total_students,
            'average_rating_given' => $this->average_rating_given,
            'instructor_rating' => $this->instructor_rating,
            'instructor_reviews' => $this->instructor_reviews_count,
            'instructor_certificates' => $this->instructor_certificates_count,
            'total_spent' => $this->total_spent,
            'monthly_spending' => $this->monthly_spending,
            'instructor_revenue' => $this->instructor_revenue,
        ];
    }

    /**
     * Get user's activity timeline.
     *
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getActivityTimeline(int $limit = 30): Collection
    {
        $enrollments = $this->enrollments()
            ->with('course')
            ->orderByDesc('enrolled_at')
            ->limit($limit / 5)
            ->get()
            ->map(function ($enrollment) {
                return [
                    'type' => 'enrollment',
                    'title' => 'Enrolled in ' . $enrollment->course->title,
                    'date' => $enrollment->enrolled_at,
                    'course' => $enrollment->course,
                ];
            });

        $completions = $this->lessonProgress()
            ->with('lesson.course')
            ->where('completed', true)
            ->orderByDesc('completed_at')
            ->limit($limit / 5)
            ->get()
            ->map(function ($progress) {
                return [
                    'type' => 'completion',
                    'title' => 'Completed lesson: ' . $progress->lesson->title,
                    'date' => $progress->completed_at,
                    'lesson' => $progress->lesson,
                    'course' => $progress->lesson->course,
                ];
            });

        $reviews = $this->reviews()
            ->with('course')
            ->orderByDesc('created_at')
            ->limit($limit / 5)
            ->get()
            ->map(function ($review) {
                return [
                    'type' => 'review',
                    'title' => 'Reviewed ' . $review->course->title . ' (' . $review->rating . '★)',
                    'date' => $review->created_at,
                    'course' => $review->course,
                    'rating' => $review->rating,
                ];
            });

        $payments = $this->payments()
            ->with('course')
            ->orderByDesc('created_at')
            ->limit($limit / 5)
            ->get()
            ->map(function ($payment) {
                return [
                    'type' => 'payment',
                    'title' => 'Payment for ' . ($payment->course ? $payment->course->title : 'course'),
                    'date' => $payment->created_at,
                    'course' => $payment->course,
                    'amount' => $payment->amount,
                    'status' => $payment->status,
                ];
            });

        $certificates = $this->certificates()
            ->with('course')
            ->orderByDesc('issued_at')
            ->limit($limit / 5)
            ->get()
            ->map(function ($certificate) {
                return [
                    'type' => 'certificate',
                    'title' => 'Earned certificate for ' . $certificate->course->title,
                    'date' => $certificate->issued_at,
                    'course' => $certificate->course,
                    'certificate_code' => $certificate->certificate_code,
                ];
            });

        return $enrollments->merge($completions)->merge($reviews)->merge($payments)->merge($certificates)
            ->sortByDesc('date')
            ->take($limit);
    }

    /**
     * Get user's profile completion percentage.
     *
     * @return int
     */
    public function getProfileCompletionAttribute(): int
    {
        $fields = [
            'name' => $this->name,
            'email' => $this->email,
            'avatar' => $this->avatar,
            'bio' => $this->bio,
            'phone' => $this->phone,
            'address' => $this->address,
        ];

        $filled = 0;
        foreach ($fields as $field) {
            if (!empty($field)) {
                $filled++;
            }
        }

        return round(($filled / count($fields)) * 100);
    }

    /**
     * Check if user has verified email.
     *
     * @return bool
     */
    public function hasVerifiedEmail(): bool
    {
        return $this->email_verified_at !== null;
    }

    /**
     * Get user's join date formatted.
     *
     * @return string
     */
    public function getJoinDateAttribute(): string
    {
        return $this->created_at->format('F Y');
    }

    /**
     * Get user's join time ago.
     *
     * @return string
     */
    public function getJoinTimeAgoAttribute(): string
    {
        return $this->created_at->diffForHumans();
    }

    /**
     * Get user's dashboard statistics.
     *
     * @return array
     */
    public function getDashboardStatsAttribute(): array
    {
        $stats = [
            'profile_completion' => $this->profile_completion,
            'completion_streak' => $this->completion_streak,
            'enrolled_courses' => $this->enrolled_courses_count,
            'completed_lessons' => $this->completed_lessons_count,
            'certificates' => $this->certificates_count,
            'total_spent' => $this->total_spent,
            'join_date' => $this->join_date,
        ];

        if ($this->isInstructor()) {
            $stats['published_courses'] = $this->published_courses_count;
            $stats['total_students'] = $this->total_students;
            $stats['instructor_rating'] = $this->instructor_rating;
            $stats['instructor_revenue'] = $this->instructor_revenue;
            $stats['instructor_certificates'] = $this->instructor_certificates_count;
        }

        if ($this->isAdmin()) {
            $stats['total_users'] = User::count();
            $stats['total_courses'] = Course::count();
            $stats['total_enrollments'] = Enrollment::count();
            $stats['total_certificates'] = Certificate::count();
            $stats['total_revenue'] = Payment::getTotalRevenue();
        }

        return $stats;
    }

    /**
     * Get user's achievement badges.
     *
     * @return array
     */
    public function getAchievementsAttribute(): array
    {
        $achievements = [];

        // Course completion achievements
        if ($this->completed_lessons_count >= 10) {
            $achievements[] = [
                'name' => 'Quick Learner',
                'description' => 'Completed 10 lessons',
                'icon' => '🎓',
            ];
        }

        if ($this->completed_lessons_count >= 50) {
            $achievements[] = [
                'name' => 'Dedicated Student',
                'description' => 'Completed 50 lessons',
                'icon' => '🏆',
            ];
        }

        if ($this->completed_lessons_count >= 100) {
            $achievements[] = [
                'name' => 'Master Learner',
                'description' => 'Completed 100 lessons',
                'icon' => '👑',
            ];
        }

        // Certificate achievements
        if ($this->certificates_count >= 1) {
            $achievements[] = [
                'name' => 'Certificate Holder',
                'description' => 'Earned first certificate',
                'icon' => '📜',
            ];
        }

        if ($this->certificates_count >= 5) {
            $achievements[] = [
                'name' => 'Certified Expert',
                'description' => 'Earned 5 certificates',
                'icon' => '⭐',
            ];
        }

        if ($this->certificates_count >= 10) {
            $achievements[] = [
                'name' => 'Master of Learning',
                'description' => 'Earned 10 certificates',
                'icon' => '👑',
            ];
        }

        // Streak achievements
        if ($this->completion_streak >= 7) {
            $achievements[] = [
                'name' => 'Weekly Warrior',
                'description' => '7-day learning streak',
                'icon' => '🔥',
            ];
        }

        if ($this->completion_streak >= 30) {
            $achievements[] = [
                'name' => 'Monthly Master',
                'description' => '30-day learning streak',
                'icon' => '⭐',
            ];
        }

        // Review achievements
        if ($this->reviews()->count() >= 5) {
            $achievements[] = [
                'name' => 'Helpful Reviewer',
                'description' => 'Left 5 reviews',
                'icon' => '📝',
            ];
        }

        // Instructor achievements
        if ($this->isInstructor() && $this->total_students >= 10) {
            $achievements[] = [
                'name' => 'Popular Instructor',
                'description' => 'Taught 10+ students',
                'icon' => '👨‍🏫',
            ];
        }

        if ($this->isInstructor() && $this->instructor_rating >= 4.5) {
            $achievements[] = [
                'name' => 'Top Rated Instructor',
                'description' => '4.5+ average rating',
                'icon' => '⭐',
            ];
        }

        if ($this->isInstructor() && $this->instructor_certificates_count >= 20) {
            $achievements[] = [
                'name' => 'Certificate Issuer',
                'description' => 'Issued 20+ certificates',
                'icon' => '🏅',
            ];
        }

        // Spending achievements
        if ($this->total_spent >= 100) {
            $achievements[] = [
                'name' => 'Investor in Learning',
                'description' => 'Spent $100+ on courses',
                'icon' => '💰',
            ];
        }

        if ($this->total_spent >= 500) {
            $achievements[] = [
                'name' => 'Learning Enthusiast',
                'description' => 'Spent $500+ on courses',
                'icon' => '💎',
            ];
        }

        return $achievements;
    }
}