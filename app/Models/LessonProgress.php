<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LessonProgress extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'lesson_progress';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'lesson_id',
        'completed',
        'completed_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'completed' => 'boolean',
        'completed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Set completed_at timestamp when marking as completed
        static::updating(function ($progress) {
            if ($progress->isDirty('completed') && $progress->completed && empty($progress->completed_at)) {
                $progress->completed_at = now();
            }

            // Clear completed_at if marked as incomplete
            if ($progress->isDirty('completed') && !$progress->completed) {
                $progress->completed_at = null;
            }
        });

        // Also handle when creating with completed=true
        static::creating(function ($progress) {
            if ($progress->completed && empty($progress->completed_at)) {
                $progress->completed_at = now();
            }
        });
    }

    /**
     * Get the user that owns the lesson progress.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the lesson that owns the lesson progress.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    /**
     * Scope a query to only include completed progress.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCompleted($query)
    {
        return $query->where('completed', true);
    }

    /**
     * Scope a query to only include incomplete progress.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeIncomplete($query)
    {
        return $query->where('completed', false);
    }

    /**
     * Scope a query to only include progress for a specific user.
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
     * Scope a query to only include progress for a specific lesson.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $lessonId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForLesson($query, int $lessonId)
    {
        return $query->where('lesson_id', $lessonId);
    }

    /**
     * Scope a query to only include progress for lessons in a specific course.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $courseId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForCourse($query, int $courseId)
    {
        return $query->whereHas('lesson', function ($q) use ($courseId) {
            $q->where('course_id', $courseId);
        });
    }

    /**
     * Mark the lesson as completed.
     *
     * @return bool
     */
    public function markAsCompleted(): bool
    {
        return $this->update([
            'completed' => true,
            'completed_at' => now(),
        ]);
    }

    /**
     * Mark the lesson as incomplete.
     *
     * @return bool
     */
    public function markAsIncomplete(): bool
    {
        return $this->update([
            'completed' => false,
            'completed_at' => null,
        ]);
    }

    /**
     * Toggle completion status.
     *
     * @return bool
     */
    public function toggleCompletion(): bool
    {
        if ($this->completed) {
            return $this->markAsIncomplete();
        }
        return $this->markAsCompleted();
    }

    /**
     * Check if the progress is recent (completed within last 24 hours).
     *
     * @return bool
     */
    public function isRecentCompletion(): bool
    {
        if (!$this->completed_at) {
            return false;
        }

        return $this->completed_at->greaterThan(now()->subDay());
    }

    /**
     * Get the completion duration (time taken to complete).
     * Assumes user started when they first accessed the lesson.
     *
     * @return string|null
     */
    public function getCompletionDurationAttribute(): ?string
    {
        if (!$this->completed_at) {
            return null;
        }

        $diff = $this->created_at->diff($this->completed_at);

        if ($diff->d > 0) {
            return $diff->d . ' day' . ($diff->d > 1 ? 's' : '');
        }

        if ($diff->h > 0) {
            return $diff->h . ' hour' . ($diff->h > 1 ? 's' : '');
        }

        if ($diff->i > 0) {
            return $diff->i . ' minute' . ($diff->i > 1 ? 's' : '');
        }

        return $diff->s . ' second' . ($diff->s > 1 ? 's' : '');
    }

    /**
     * Get formatted completed date.
     *
     * @return string|null
     */
    public function getFormattedCompletedAtAttribute(): ?string
    {
        return $this->completed_at?->format('F j, Y g:i A');
    }

    /**
     * Get human-readable completion time.
     *
     * @return string|null
     */
    public function getHumanCompletedAtAttribute(): ?string
    {
        if (!$this->completed_at) {
            return null;
        }

        return $this->completed_at->diffForHumans();
    }

    /**
     * Create or update lesson progress.
     *
     * @param int $userId
     * @param int $lessonId
     * @param bool $completed
     * @return LessonProgress
     */
    public static function updateProgress(int $userId, int $lessonId, bool $completed = true): LessonProgress
    {
        $progress = static::firstOrCreate(
            ['user_id' => $userId, 'lesson_id' => $lessonId],
            ['completed' => $completed]
        );

        if ($progress->completed !== $completed) {
            $progress->update(['completed' => $completed]);
        }

        return $progress;
    }

    /**
     * Get progress for a user in a specific course.
     *
     * @param int $userId
     * @param int $courseId
     * @return array
     */
    public static function getCourseProgress(int $userId, int $courseId): array
    {
        $totalLessons = Lesson::where('course_id', $courseId)->count();
        
        if ($totalLessons === 0) {
            return [
                'completed' => 0,
                'total' => 0,
                'percentage' => 0,
            ];
        }

        $completedLessons = static::where('user_id', $userId)
            ->whereHas('lesson', function ($q) use ($courseId) {
                $q->where('course_id', $courseId);
            })
            ->where('completed', true)
            ->count();

        $percentage = ($completedLessons / $totalLessons) * 100;

        return [
            'completed' => $completedLessons,
            'total' => $totalLessons,
            'percentage' => round($percentage, 2),
        ];
    }

    /**
     * Get next incomplete lesson for a user in a course.
     *
     * @param int $userId
     * @param int $courseId
     * @return Lesson|null
     */
    public static function getNextIncompleteLesson(int $userId, int $courseId): ?Lesson
    {
        $completedLessonIds = static::where('user_id', $userId)
            ->whereHas('lesson', function ($q) use ($courseId) {
                $q->where('course_id', $courseId);
            })
            ->where('completed', true)
            ->pluck('lesson_id');

        return Lesson::where('course_id', $courseId)
            ->whereNotIn('id', $completedLessonIds)
            ->orderBy('position')
            ->first();
    }
}