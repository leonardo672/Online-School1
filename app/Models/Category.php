<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory; // Add this
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany; // Add this
use Illuminate\Support\Str;

class Category extends Model
{
    use HasFactory; // Add this for factory support

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'slug',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Automatically generate slug from name when creating
        static::creating(function ($category) {
            if (empty($category->slug)) {
                $category->slug = $category->generateUniqueSlug($category->name);
            }
        });

        // Automatically update slug when name changes
        static::updating(function ($category) {
            if ($category->isDirty('name')) {
                // Only regenerate slug if name changed AND slug is empty or using default pattern
                if (empty($category->slug) || $category->slug === Str::slug($category->getOriginal('name'))) {
                    $category->slug = $category->generateUniqueSlug($category->name, $category->id);
                }
            }
        });
    }

    /**
     * Generate a unique slug for the category.
     *
     * @param string $name
     * @param int|null $excludeId
     * @return string
     */
    private function generateUniqueSlug(string $name, int $excludeId = null): string
    {
        $slug = Str::slug($name);
        $originalSlug = $slug;
        $count = 1;

        // Check if slug already exists (excluding current category if updating)
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
     * (Use slug instead of ID for route model binding)
     *
     * @return string
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Get the courses for this category.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function courses(): HasMany
    {
        return $this->hasMany(Course::class);
    }

    /**
     * Get the published courses for this category.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function publishedCourses(): HasMany
    {
        return $this->hasMany(Course::class)->where('published', true);
    }

    /**
     * Get the courses count for this category.
     *
     * @return int
     */
    public function getCoursesCountAttribute(): int
    {
        return $this->courses()->count();
    }

    /**
     * Get the published courses count for this category.
     *
     * @return int
     */
    public function getPublishedCoursesCountAttribute(): int
    {
        return $this->publishedCourses()->count();
    }

    /**
     * Scope a query to only include categories with courses.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeHasCourses($query)
    {
        return $query->whereHas('courses');
    }

    /**
     * Scope a query to only include categories with published courses.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeHasPublishedCourses($query)
    {
        return $query->whereHas('courses', function ($q) {
            $q->where('published', true);
        });
    }

    /**
     * Get the display name (capitalized).
     *
     * @return string
     */
    public function getDisplayNameAttribute(): string
    {
        return ucwords($this->name);
    }

    /**
     * Check if category has any courses.
     *
     * @return bool
     */
    public function hasCourses(): bool
    {
        return $this->courses()->exists();
    }

    /**
     * Check if category has any published courses.
     *
     * @return bool
     */
    public function hasPublishedCourses(): bool
    {
        return $this->publishedCourses()->exists();
    }
}