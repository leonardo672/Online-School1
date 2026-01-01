<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Course extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'instructor_id',
        'title',
        'slug',
        'description',
        'price',
        'level',
        'published',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'published' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Course levels.
     */
    const LEVELS = [
        'beginner',
        'intermediate',
        'advanced',
    ];

    /**
     * Get the category that owns the course.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the instructor that owns the course.
     */
    public function instructor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }

    /**
     * Get the certificates for this course.
     */
    public function certificates(): HasMany
    {
        return $this->hasMany(Certificate::class);
    }

    /**
     * Get the users who have certificates for this course.
     */
    public function certifiedUsers()
    {
        return $this->belongsToMany(User::class, 'certificates')
                    ->withPivot('certificate_code', 'issued_at', 'expires_at')
                    ->withTimestamps();
    }

    /**
     * Scope a query to only include published courses.
     */
    public function scopePublished($query)
    {
        return $query->where('published', true);
    }

    /**
     * Scope a query to only include courses by a specific instructor.
     */
    public function scopeByInstructor($query, $instructorId)
    {
        return $query->where('instructor_id', $instructorId);
    }

    /**
     * Get the number of certificates issued for this course.
     */
    public function getCertificateCountAttribute()
    {
        return $this->certificates()->count();
    }

    /**
     * Get the most recent certificate for this course.
     */
    public function getLatestCertificateAttribute()
    {
        return $this->certificates()->latest()->first();
    }

    /**
     * Check if a user has a certificate for this course.
     */
    public function userHasCertificate($userId)
    {
        return $this->certificates()->where('user_id', $userId)->exists();
    }

    /**
     * Get certificate for a specific user.
     */
    public function getUserCertificate($userId)
    {
        return $this->certificates()->where('user_id', $userId)->first();
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}