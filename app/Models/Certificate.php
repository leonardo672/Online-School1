<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class Certificate extends Model
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
        'certificate_code',
        'issued_at',
        'expires_at',
        'download_count', // Added: track downloads
        'verification_url', // Added: unique verification URL
        'metadata', // Added: for additional certificate data
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'issued_at' => 'datetime',
        'expires_at' => 'datetime',
        'download_count' => 'integer',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Certificate validity period in years (null means never expires).
     */
    public const DEFAULT_VALIDITY_YEARS = 2;

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Generate certificate code when creating
        static::creating(function ($certificate) {
            if (empty($certificate->certificate_code)) {
                $certificate->certificate_code = self::generateCertificateCode();
            }

            if (empty($certificate->issued_at)) {
                $certificate->issued_at = now();
            }

            // Set expiry date if course has certificate validity
            if (empty($certificate->expires_at) && $certificate->course->certificate_validity_years) {
                $certificate->expires_at = now()->addYears($certificate->course->certificate_validity_years);
            }

            // Generate verification URL
            if (empty($certificate->verification_url)) {
                $certificate->verification_url = self::generateVerificationUrl($certificate->certificate_code);
            }
        });

        // Increment download count when downloading
        static::updated(function ($certificate) {
            if ($certificate->isDirty('download_count')) {
                // Log download activity
                // You can add logging here if needed
            }
        });
    }

    /**
     * Get the user that owns the certificate.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the course that owns the certificate.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * Scope a query to only include valid certificates (not expired).
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeValid(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
                ->orWhere('expires_at', '>', now());
        });
    }

    /**
     * Scope a query to only include expired certificates.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeExpired(Builder $query): Builder
    {
        return $query->whereNotNull('expires_at')
            ->where('expires_at', '<=', now());
    }

    /**
     * Scope a query to only include certificates for a specific user.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $userId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope a query to only include certificates for a specific course.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $courseId
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function scopeForCourse(Builder $query, int $courseId): Builder
    {
        return $query->where('course_id', $courseId);
    }

    /**
     * Scope a query to only include certificates issued within a date range.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $startDate
     * @param string $endDate
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeIssuedBetween(Builder $query, string $startDate, string $endDate): Builder
    {
        return $query->whereBetween('issued_at', [$startDate, $endDate]);
    }

    /**
     * Scope a query to order by latest issued certificates.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeLatest(Builder $query): Builder
    {
        return $query->orderByDesc('issued_at');
    }

    /**
     * Scope a query to only include certificates that are downloadable.
     * (Valid certificates that haven't reached download limit)
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $maxDownloads
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeDownloadable(Builder $query, int $maxDownloads = 5): Builder
    {
        return $query->valid()
            ->where('download_count', '<', $maxDownloads);
    }

    /**
     * Check if the certificate is valid (not expired).
     *
     * @return bool
     */
    public function isValid(): bool
    {
        return !$this->isExpired();
    }

    /**
     * Check if the certificate is expired.
     *
     * @return bool
     */
    public function isExpired(): bool
    {
        if (!$this->expires_at) {
            return false; // Never expires
        }

        return $this->expires_at->isPast();
    }

    /**
     * Check if certificate can be downloaded.
     *
     * @param int $maxDownloads
     * @return bool
     */
    public function canBeDownloaded(int $maxDownloads = 5): bool
    {
        return $this->isValid() && $this->download_count < $maxDownloads;
    }

    /**
     * Check if certificate has expiration date.
     *
     * @return bool
     */
    public function hasExpiry(): bool
    {
        return !is_null($this->expires_at);
    }

    /**
     * Get days until expiration.
     *
     * @return int|null
     */
    public function getDaysUntilExpirationAttribute(): ?int
    {
        if (!$this->expires_at) {
            return null;
        }

        return now()->diffInDays($this->expires_at, false);
    }

    /**
     * Get formatted issued date.
     *
     * @return string
     */
    public function getFormattedIssuedAtAttribute(): string
    {
        return $this->issued_at->format('F j, Y');
    }

    /**
     * Get formatted expiry date.
     *
     * @return string|null
     */
    public function getFormattedExpiresAtAttribute(): ?string
    {
        return $this->expires_at?->format('F j, Y');
    }

    /**
     * Get human-readable expiry status.
     *
     * @return string
     */
    public function getExpiryStatusAttribute(): string
    {
        if (!$this->expires_at) {
            return 'Never Expires';
        }

        if ($this->isExpired()) {
            return 'Expired';
        }

        $days = $this->days_until_expiration;

        if ($days > 365) {
            $years = floor($days / 365);
            return "Expires in {$years} year" . ($years > 1 ? 's' : '');
        }

        if ($days > 30) {
            $months = floor($days / 30);
            return "Expires in {$months} month" . ($months > 1 ? 's' : '');
        }

        if ($days > 0) {
            return "Expires in {$days} day" . ($days > 1 ? 's' : '');
        }

        return 'Expires today';
    }

    /**
     * Get certificate validity period in years.
     *
     * @return int|null
     */
    public function getValidityYearsAttribute(): ?int
    {
        if (!$this->expires_at) {
            return null;
        }

        return $this->issued_at->diffInYears($this->expires_at);
    }

    /**
     * Generate a unique certificate code.
     *
     * @return string
     */
    public static function generateCertificateCode(): string
    {
        $prefix = 'CERT';
        $date = date('Ymd');
        $random = strtoupper(substr(md5(uniqid()), 0, 8));
        
        $code = $prefix . '-' . $date . '-' . $random;
        
        // Ensure uniqueness
        while (static::where('certificate_code', $code)->exists()) {
            $random = strtoupper(substr(md5(uniqid()), 0, 8));
            $code = $prefix . '-' . $date . '-' . $random;
        }
        
        return $code;
    }

    /**
     * Generate verification URL for certificate.
     *
     * @param string $certificateCode
     * @return string
     */
    public static function generateVerificationUrl(string $certificateCode): string
    {
        $hash = hash('sha256', $certificateCode . config('app.key'));
        return url('/certificates/verify/' . $certificateCode . '/' . substr($hash, 0, 16));
    }

    /**
     * Verify certificate code and hash.
     *
     * @param string $certificateCode
     * @param string $hash
     * @return bool
     */
    public static function verify(string $certificateCode, string $hash): bool
    {
        $expectedHash = substr(hash('sha256', $certificateCode . config('app.key')), 0, 16);
        return hash_equals($expectedHash, $hash);
    }

    /**
     * Issue a certificate to a user for a course.
     *
     * @param int $userId
     * @param int $courseId
     * @param array $metadata
     * @return Certificate|null
     */
    public static function issue(int $userId, int $courseId, array $metadata = []): ?Certificate
    {
        // Check if certificate already exists
        if (static::where('user_id', $userId)->where('course_id', $courseId)->exists()) {
            return null;
        }

        // Check if user has completed the course
        $user = User::find($userId);
        if (!$user || !$user->hasCompletedCourse($courseId)) {
            return null;
        }

        // Check if course offers certificates
        $course = Course::find($courseId);
        if (!$course || !$course->certificate_available) {
            return null;
        }

        try {
            return static::create([
                'user_id' => $userId,
                'course_id' => $courseId,
                'metadata' => $metadata,
            ]);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Record a download of the certificate.
     *
     * @return bool
     */
    public function recordDownload(): bool
    {
        return $this->increment('download_count');
    }

    /**
     * Get certificate analytics.
     *
     * @param int|null $courseId
     * @return array
     */
    public static function getAnalytics(?int $courseId = null): array
    {
        $query = static::query();
        
        if ($courseId) {
            $query->where('course_id', $courseId);
        }

        $total = $query->count();
        $valid = $query->valid()->count();
        $expired = $query->expired()->count();
        $recent = $query->where('issued_at', '>=', now()->subDays(30))->count();

        return [
            'total' => $total,
            'valid' => $valid,
            'expired' => $expired,
            'recent' => $recent,
            'validity_rate' => $total > 0 ? round(($valid / $total) * 100, 2) : 0,
            'expiry_rate' => $total > 0 ? round(($expired / $total) * 100, 2) : 0,
        ];
    }

    /**
     * Get user's certificates with pagination.
     *
     * @param int $userId
     * @param int $limit
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public static function getUserCertificates(int $userId, int $limit = 10): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return static::forUser($userId)
            ->with('course.instructor')
            ->latest()
            ->paginate($limit);
    }

    /**
     * Get course certificates with pagination.
     *
     * @param int $courseId
     * @param int $limit
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public static function getCourseCertificates(int $courseId, int $limit = 10): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return static::forCourse($courseId)
            ->with('user')
            ->latest()
            ->paginate($limit);
    }

    /**
     * Get recent certificates.
     *
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getRecentCertificates(int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        return static::with(['user', 'course'])
            ->latest()
            ->limit($limit)
            ->get();
    }

    /**
     * Get certificate by code.
     *
     * @param string $certificateCode
     * @return Certificate|null
     */
    public static function findByCode(string $certificateCode): ?Certificate
    {
        return static::where('certificate_code', $certificateCode)
            ->with(['user', 'course.instructor', 'course.category'])
            ->first();
    }

    /**
     * Validate certificate for public verification.
     *
     * @param string $certificateCode
     * @return array
     */
    public static function validateForVerification(string $certificateCode): array
    {
        $certificate = static::findByCode($certificateCode);

        if (!$certificate) {
            return [
                'valid' => false,
                'message' => 'Certificate not found',
            ];
        }

        if ($certificate->isExpired()) {
            return [
                'valid' => false,
                'message' => 'Certificate has expired',
                'certificate' => $certificate,
            ];
        }

        return [
            'valid' => true,
            'message' => 'Certificate is valid',
            'certificate' => $certificate,
        ];
    }

    /**
     * Get certificate data for PDF generation.
     *
     * @return array
     */
    public function getPdfData(): array
    {
        return [
            'certificate_code' => $this->certificate_code,
            'student_name' => $this->user->name,
            'course_title' => $this->course->title,
            'instructor_name' => $this->course->instructor->name,
            'category' => $this->course->category->name,
            'issued_date' => $this->formatted_issued_at,
            'expiry_date' => $this->formatted_expires_at,
            'verification_url' => $this->verification_url,
            'level' => $this->course->level_name,
            'duration' => $this->course->formatted_duration,
            'issued_at_timestamp' => $this->issued_at->timestamp,
        ];
    }

    /**
     * Get certificate metadata value.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getMetadata(string $key, $default = null)
    {
        return $this->metadata[$key] ?? $default;
    }

    /**
     * Set certificate metadata value.
     *
     * @param string $key
     * @param mixed $value
     * @return bool
     */
    public function setMetadata(string $key, $value): bool
    {
        $metadata = $this->metadata ?? [];
        $metadata[$key] = $value;
        
        return $this->update(['metadata' => $metadata]);
    }
}