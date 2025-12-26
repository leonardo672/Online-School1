<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Enrollment extends Model
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
        'enrolled_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'enrolled_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * The model's default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'enrolled_at' => null,
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Set enrolled_at timestamp when creating
        static::creating(function ($enrollment) {
            if (empty($enrollment->enrolled_at)) {
                $enrollment->enrolled_at = now();
            }
        });
    }

    /**
     * Get the user that owns the enrollment.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the course that owns the enrollment.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * Scope a query to only include enrollments for a specific user.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $userId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope a query to only include enrollments for a specific course.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $courseId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForCourse($query, int $courseId)
    {
        return $query->where('course_id', $courseId);
    }

    /**
     * Scope a query to order by enrollment date (newest first).
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeLatestFirst($query)
    {
        return $query->orderByDesc('enrolled_at');
    }

    /**
     * Scope a query to order by enrollment date (oldest first).
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOldestFirst($query)
    {
        return $query->orderBy('enrolled_at');
    }

    /**
     * Check if enrollment is recent (within the last 7 days).
     *
     * @return bool
     */
    public function isRecent(): bool
    {
        return $this->enrolled_at->greaterThan(now()->subDays(7));
    }

    /**
     * Get the enrollment duration in days.
     *
     * @return int
     */
    public function getEnrollmentDurationAttribute(): int
    {
        return $this->enrolled_at->diffInDays(now());
    }

    /**
     * Get formatted enrolled date.
     *
     * @return string
     */
    public function getFormattedEnrolledAtAttribute(): string
    {
        return $this->enrolled_at->format('F j, Y');
    }

    /**
     * Get human-readable enrollment duration.
     *
     * @return string
     */
    public function getHumanDurationAttribute(): string
    {
        $duration = $this->enrollment_duration;

        if ($duration === 0) {
            return 'Today';
        }

        if ($duration === 1) {
            return 'Yesterday';
        }

        if ($duration < 7) {
            return $duration . ' days ago';
        }

        if ($duration < 30) {
            $weeks = floor($duration / 7);
            return $weeks . ' week' . ($weeks > 1 ? 's' : '') . ' ago';
        }

        if ($duration < 365) {
            $months = floor($duration / 30);
            return $months . ' month' . ($months > 1 ? 's' : '') . ' ago';
        }

        $years = floor($duration / 365);
        return $years . ' year' . ($years > 1 ? 's' : '') . ' ago';
    }

    /**
     * Get the progress percentage in the course.
     * This assumes you have a way to track lesson completion (not in this model)
     *
     * @param int $completedLessons
     * @param int $totalLessons
     * @return float
     */
    public function getProgressPercentage(int $completedLessons, int $totalLessons): float
    {
        if ($totalLessons === 0) {
            return 0;
        }

        return ($completedLessons / $totalLessons) * 100;
    }

    /**
     * Check if a user is enrolled in a course.
     *
     * @param int $userId
     * @param int $courseId
     * @return bool
     */
    public static function isEnrolled(int $userId, int $courseId): bool
    {
        return static::where('user_id', $userId)
            ->where('course_id', $courseId)
            ->exists();
    }

    /**
     * Enroll a user in a course.
     *
     * @param int $userId
     * @param int $courseId
     * @return Enrollment|null
     */
    public static function enroll(int $userId, int $courseId): ?Enrollment
    {
        // Check if already enrolled
        if (self::isEnrolled($userId, $courseId)) {
            return null;
        }

        try {
            return static::create([
                'user_id' => $userId,
                'course_id' => $courseId,
            ]);
        } catch (\Exception $e) {
            // Handle duplicate enrollment attempt (shouldn't happen with the check above)
            return null;
        }
    }

    /**
     * Unenroll a user from a course.
     *
     * @param int $userId
     * @param int $courseId
     * @return bool
     */
    public static function unenroll(int $userId, int $courseId): bool
    {
        return static::where('user_id', $userId)
            ->where('course_id', $courseId)
            ->delete() > 0;
    }
}