<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class Lesson extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'course_id',
        'title',
        'content',
        'video_url',
        'position',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'position' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Set default position when creating
        static::creating(function ($lesson) {
            if (empty($lesson->position)) {
                $lesson->position = static::where('course_id', $lesson->course_id)
                    ->max('position') + 1;
            }
        });

        // Reorder positions when deleting
        static::deleted(function ($lesson) {
            static::where('course_id', $lesson->course_id)
                ->where('position', '>', $lesson->position)
                ->decrement('position');
        });
    }

    /**
     * Get the course that owns the lesson.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * Get the lesson progress for the lesson.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function progress(): HasMany
    {
        return $this->hasMany(LessonProgress::class);
    }

    /**
     * Get users who have completed this lesson.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function completedByUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'lesson_progress')
            ->withPivot(['completed', 'completed_at'])
            ->wherePivot('completed', true);
    }

    /**
     * Scope a query to order by position.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('position');
    }

    /**
     * Scope a query to only include lessons for a specific course.
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
     * Get the next lesson in the course.
     *
     * @return Lesson|null
     */
    public function nextLesson(): ?Lesson
    {
        return static::where('course_id', $this->course_id)
            ->where('position', '>', $this->position)
            ->orderBy('position')
            ->first();
    }

    /**
     * Get the previous lesson in the course.
     *
     * @return Lesson|null
     */
    public function previousLesson(): ?Lesson
    {
        return static::where('course_id', $this->course_id)
            ->where('position', '<', $this->position)
            ->orderByDesc('position')
            ->first();
    }

    /**
     * Check if this lesson has a video.
     *
     * @return bool
     */
    public function hasVideo(): bool
    {
        return !empty($this->video_url);
    }

    /**
     * Check if this lesson has content.
     *
     * @return bool
     */
    public function hasContent(): bool
    {
        return !empty($this->content);
    }

    /**
     * Get the excerpt of the content.
     *
     * @param int $length
     * @return string
     */
    public function excerpt(int $length = 100): string
    {
        return Str::limit(strip_tags($this->content ?? ''), $length);
    }

    /**
     * Move the lesson to a new position.
     *
     * @param int $newPosition
     * @return bool
     */
    public function moveToPosition(int $newPosition): bool
    {
        $maxPosition = static::where('course_id', $this->course_id)->max('position');
        $newPosition = max(1, min($newPosition, $maxPosition));

        if ($newPosition === $this->position) {
            return true;
        }

        DB::transaction(function () use ($newPosition) {
            if ($newPosition > $this->position) {
                // Moving down - decrease positions in between
                static::where('course_id', $this->course_id)
                    ->whereBetween('position', [$this->position + 1, $newPosition])
                    ->decrement('position');
            } else {
                // Moving up - increase positions in between
                static::where('course_id', $this->course_id)
                    ->whereBetween('position', [$newPosition, $this->position - 1])
                    ->increment('position');
            }

            $this->position = $newPosition;
            $this->save();
        });

        return true;
    }

    /**
     * Get the lesson number within the course.
     *
     * @return int
     */
    public function getLessonNumberAttribute(): int
    {
        return $this->position;
    }

    /**
     * Get the total lessons count in the course.
     *
     * @return int
     */
    public function getTotalLessonsAttribute(): int
    {
        return static::where('course_id', $this->course_id)->count();
    }

    /**
     * Get the progress percentage for this lesson.
     * Useful for showing progress in a course
     *
     * @return float
     */
    public function getProgressPercentageAttribute(): float
    {
        $totalLessons = $this->total_lessons;
        return $totalLessons > 0 ? ($this->position / $totalLessons) * 100 : 0;
    }

    /**
     * Check if a specific user has completed this lesson.
     *
     * @param int $userId
     * @return bool
     */
    public function isCompletedByUser(int $userId): bool
    {
        return $this->progress()
            ->where('user_id', $userId)
            ->where('completed', true)
            ->exists();
    }

    /**
     * Check if the current authenticated user has completed this lesson.
     *
     * @return bool
     */
    public function isCompletedByCurrentUser(): bool
    {
        $user = auth()->user();
        if (!$user) {
            return false;
        }

        return $this->isCompletedByUser($user->id);
    }

    /**
     * Get completion count for this lesson.
     *
     * @return int
     */
    public function getCompletionCountAttribute(): int
    {
        return $this->progress()->where('completed', true)->count();
    }

    /**
     * Get completion percentage for this lesson.
     *
     * @param int|null $enrolledUsersCount If null, will use course enrollment count
     * @return float
     */
    public function getCompletionPercentage(int $enrolledUsersCount = null): float
    {
        if ($enrolledUsersCount === null) {
            // Get enrolled users count from the course
            $enrolledUsersCount = $this->course->enrollments()->count();
        }

        if ($enrolledUsersCount === 0) {
            return 0;
        }

        return ($this->completion_count / $enrolledUsersCount) * 100;
    }

    /**
     * Mark this lesson as completed for a user.
     *
     * @param int $userId
     * @return LessonProgress
     */
    public function markAsCompletedForUser(int $userId): LessonProgress
    {
        return LessonProgress::updateProgress($userId, $this->id, true);
    }

    /**
     * Mark this lesson as incomplete for a user.
     *
     * @param int $userId
     * @return bool
     */
    public function markAsIncompleteForUser(int $userId): bool
    {
        $progress = $this->progress()
            ->where('user_id', $userId)
            ->first();

        if ($progress) {
            return $progress->markAsIncomplete();
        }

        return false;
    }

    /**
     * Get the next incomplete lesson for a user.
     *
     * @param int $userId
     * @return Lesson|null
     */
    public function getNextIncompleteForUser(int $userId): ?Lesson
    {
        $completedLessonIds = LessonProgress::where('user_id', $userId)
            ->where('completed', true)
            ->pluck('lesson_id');

        return static::where('course_id', $this->course_id)
            ->where('position', '>', $this->position)
            ->whereNotIn('id', $completedLessonIds)
            ->orderBy('position')
            ->first();
    }

    /**
     * Get the previous incomplete lesson for a user.
     *
     * @param int $userId
     * @return Lesson|null
     */
    public function getPreviousIncompleteForUser(int $userId): ?Lesson
    {
        $completedLessonIds = LessonProgress::where('user_id', $userId)
            ->where('completed', true)
            ->pluck('lesson_id');

        return static::where('course_id', $this->course_id)
            ->where('position', '<', $this->position)
            ->whereNotIn('id', $completedLessonIds)
            ->orderByDesc('position')
            ->first();
    }

    /**
     * Get video embed URL for supported platforms.
     *
     * @return string|null
     */
    public function getVideoEmbedUrlAttribute(): ?string
    {
        if (!$this->hasVideo()) {
            return null;
        }

        $url = $this->video_url;

        // YouTube
        if (preg_match('/youtube\.com\/watch\?v=([a-zA-Z0-9_-]+)/', $url, $matches)) {
            return "https://www.youtube.com/embed/{$matches[1]}";
        }

        if (preg_match('/youtu\.be\/([a-zA-Z0-9_-]+)/', $url, $matches)) {
            return "https://www.youtube.com/embed/{$matches[1]}";
        }

        // Vimeo
        if (preg_match('/vimeo\.com\/(\d+)/', $url, $matches)) {
            return "https://player.vimeo.com/video/{$matches[1]}";
        }

        // Return original URL if not a supported platform
        return $url;
    }

    /**
     * Check if video is from a supported embeddable platform.
     *
     * @return bool
     */
    public function hasEmbeddableVideo(): bool
    {
        if (!$this->hasVideo()) {
            return false;
        }

        $url = $this->video_url;
        return str_contains($url, 'youtube.com') || 
               str_contains($url, 'youtu.be') || 
               str_contains($url, 'vimeo.com');
    }

    /**
     * Get the estimated reading time for the content.
     *
     * @param int $wordsPerMinute
     * @return int
     */
    public function getReadingTimeAttribute(int $wordsPerMinute = 200): int
    {
        if (empty($this->content)) {
            return 0;
        }

        $wordCount = str_word_count(strip_tags($this->content));
        $minutes = ceil($wordCount / $wordsPerMinute);

        return max(1, $minutes); // At least 1 minute
    }

    /**
     * Get the formatted reading time.
     *
     * @return string
     */
    public function getFormattedReadingTimeAttribute(): string
    {
        $minutes = $this->reading_time;

        if ($minutes === 1) {
            return '1 min read';
        }

        return "{$minutes} min read";
    }

    /**
     * Get the type of lesson based on content.
     *
     * @return string
     */
    public function getTypeAttribute(): string
    {
        if ($this->hasVideo() && $this->hasContent()) {
            return 'video_and_text';
        }

        if ($this->hasVideo()) {
            return 'video';
        }

        if ($this->hasContent()) {
            return 'text';
        }

        return 'unknown';
    }
}