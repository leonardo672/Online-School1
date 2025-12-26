<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
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
        'amount',
        'status',
        'payment_method',
        'transaction_id',
        'currency', // Added: for currency support
        'description', // Added: payment description
        'metadata', // Added: for additional payment data
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'amount' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * Payment statuses.
     */
    public const STATUSES = [
        'pending' => 'Pending',
        'completed' => 'Completed',
        'failed' => 'Failed',
        'refunded' => 'Refunded',
        'cancelled' => 'Cancelled',
    ];

    /**
     * Payment methods.
     */
    public const PAYMENT_METHODS = [
        'stripe' => 'Stripe',
        'paypal' => 'PayPal',
        'manual' => 'Manual',
        'bank_transfer' => 'Bank Transfer',
        'card' => 'Credit/Debit Card',
    ];

    /**
     * Default currency.
     */
    public const DEFAULT_CURRENCY = 'USD';

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Set default values when creating
        static::creating(function ($payment) {
            if (empty($payment->currency)) {
                $payment->currency = self::DEFAULT_CURRENCY;
            }

            if (empty($payment->transaction_id)) {
                $payment->transaction_id = self::generateTransactionId();
            }

            if (empty($payment->description) && $payment->course_id) {
                $course = Course::find($payment->course_id);
                $payment->description = $course ? "Payment for course: {$course->title}" : 'Course payment';
            }
        });
    }

    /**
     * Get the user that owns the payment.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the course that owns the payment.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * Scope a query to only include completed payments.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope a query to only include pending payments.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope a query to only include failed payments.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope a query to only include refunded payments.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeRefunded($query)
    {
        return $query->where('status', 'refunded');
    }

    /**
     * Scope a query to only include payments for a specific user.
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
     * Scope a query to only include payments for a specific course.
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
     * Scope a query to only include payments using a specific method.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $method
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithMethod($query, string $method)
    {
        return $query->where('payment_method', $method);
    }

    /**
     * Scope a query to order by latest payments.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeLatest($query)
    {
        return $query->orderByDesc('created_at');
    }

    /**
     * Scope a query to only include payments within a date range.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $startDate
     * @param string $endDate
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeBetweenDates($query, string $startDate, string $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Check if payment is completed.
     *
     * @return bool
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if payment is pending.
     *
     * @return bool
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if payment is failed.
     *
     * @return bool
     */
    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Check if payment is refunded.
     *
     * @return bool
     */
    public function isRefunded(): bool
    {
        return $this->status === 'refunded';
    }

    /**
     * Check if payment is for a course.
     *
     * @return bool
     */
    public function isForCourse(): bool
    {
        return $this->course_id !== null;
    }

    /**
     * Get the formatted amount.
     *
     * @return string
     */
    public function getFormattedAmountAttribute(): string
    {
        $symbols = [
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
            'JPY' => '¥',
        ];

        $symbol = $symbols[$this->currency] ?? $this->currency;
        return $symbol . number_format($this->amount, 2);
    }

    /**
     * Get human-readable status.
     *
     * @return string
     */
    public function getStatusTextAttribute(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst($this->status);
    }

    /**
     * Get human-readable payment method.
     *
     * @return string
     */
    public function getMethodTextAttribute(): string
    {
        return self::PAYMENT_METHODS[$this->payment_method] ?? ucfirst($this->payment_method);
    }

    /**
     * Get formatted created date.
     *
     * @return string
     */
    public function getFormattedDateAttribute(): string
    {
        return $this->created_at->format('F j, Y g:i A');
    }

    /**
     * Get time ago since payment.
     *
     * @return string
     */
    public function getTimeAgoAttribute(): string
    {
        return $this->created_at->diffForHumans();
    }

    /**
     * Check if payment is recent (within last 24 hours).
     *
     * @return bool
     */
    public function isRecent(): bool
    {
        return $this->created_at->greaterThan(now()->subDay());
    }

    /**
     * Mark payment as completed.
     *
     * @param array $metadata
     * @return bool
     */
    public function markAsCompleted(array $metadata = []): bool
    {
        $this->status = 'completed';
        
        if (!empty($metadata)) {
            $currentMetadata = $this->metadata ?? [];
            $this->metadata = array_merge($currentMetadata, $metadata);
        }
        
        return $this->save();
    }

    /**
     * Mark payment as failed.
     *
     * @param string|null $reason
     * @return bool
     */
    public function markAsFailed(?string $reason = null): bool
    {
        $this->status = 'failed';
        
        if ($reason) {
            $currentMetadata = $this->metadata ?? [];
            $currentMetadata['failure_reason'] = $reason;
            $this->metadata = $currentMetadata;
        }
        
        return $this->save();
    }

    /**
     * Mark payment as refunded.
     *
     * @param string|null $refundId
     * @return bool
     */
    public function markAsRefunded(?string $refundId = null): bool
    {
        $this->status = 'refunded';
        
        if ($refundId) {
            $currentMetadata = $this->metadata ?? [];
            $currentMetadata['refund_id'] = $refundId;
            $currentMetadata['refunded_at'] = now()->toDateTimeString();
            $this->metadata = $currentMetadata;
        }
        
        return $this->save();
    }

    /**
     * Generate a unique transaction ID.
     *
     * @return string
     */
    public static function generateTransactionId(): string
    {
        $prefix = 'TXN';
        $date = date('Ymd');
        $random = strtoupper(substr(md5(uniqid()), 0, 8));
        
        return $prefix . '-' . $date . '-' . $random;
    }

    /**
     * Create a payment record.
     *
     * @param int $userId
     * @param int|null $courseId
     * @param float $amount
     * @param string $paymentMethod
     * @param string $status
     * @param array $metadata
     * @return Payment
     */
    public static function createPayment(
        int $userId,
        ?int $courseId,
        float $amount,
        string $paymentMethod = 'manual',
        string $status = 'pending',
        array $metadata = []
    ): Payment {
        return static::create([
            'user_id' => $userId,
            'course_id' => $courseId,
            'amount' => $amount,
            'payment_method' => $paymentMethod,
            'status' => $status,
            'metadata' => $metadata,
        ]);
    }

    /**
     * Get total revenue (sum of completed payments).
     *
     * @param string|null $startDate
     * @param string|null $endDate
     * @return float
     */
    public static function getTotalRevenue(?string $startDate = null, ?string $endDate = null): float
    {
        $query = static::completed();

        if ($startDate && $endDate) {
            $query->betweenDates($startDate, $endDate);
        }

        return $query->sum('amount');
    }

    /**
     * Get revenue statistics.
     *
     * @param string $period (daily, weekly, monthly, yearly)
     * @return array
     */
    public static function getRevenueStats(string $period = 'monthly'): array
    {
        $query = static::completed();
        
        switch ($period) {
            case 'daily':
                $dateFormat = 'Y-m-d';
                $groupBy = 'DATE(created_at)';
                $query->whereDate('created_at', now()->toDateString());
                break;
                
            case 'weekly':
                $dateFormat = 'Y-W';
                $groupBy = 'YEARWEEK(created_at)';
                $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
                break;
                
            case 'yearly':
                $dateFormat = 'Y';
                $groupBy = 'YEAR(created_at)';
                $query->whereYear('created_at', now()->year);
                break;
                
            case 'monthly':
            default:
                $dateFormat = 'Y-m';
                $groupBy = 'DATE_FORMAT(created_at, "%Y-%m")';
                $query->whereMonth('created_at', now()->month);
                break;
        }

        $stats = $query->selectRaw("
                {$groupBy} as period,
                COUNT(*) as total_transactions,
                SUM(amount) as total_amount,
                AVG(amount) as average_amount
            ")
            ->groupBy('period')
            ->orderBy('period')
            ->get();

        return [
            'period' => $period,
            'total_revenue' => $stats->sum('total_amount'),
            'total_transactions' => $stats->sum('total_transactions'),
            'average_transaction' => $stats->avg('average_amount'),
            'breakdown' => $stats,
        ];
    }

    /**
     * Get user's payment history.
     *
     * @param int $userId
     * @param int $limit
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public static function getUserPaymentHistory(int $userId, int $limit = 10)
    {
        return static::forUser($userId)
            ->with('course')
            ->latest()
            ->paginate($limit);
    }

    /**
     * Check if user has paid for a course.
     *
     * @param int $userId
     * @param int $courseId
     * @return bool
     */
    public static function hasUserPaidForCourse(int $userId, int $courseId): bool
    {
        return static::where('user_id', $userId)
            ->where('course_id', $courseId)
            ->where('status', 'completed')
            ->exists();
    }

    /**
     * Get course revenue.
     *
     * @param int $courseId
     * @return float
     */
    public static function getCourseRevenue(int $courseId): float
    {
        return static::forCourse($courseId)
            ->completed()
            ->sum('amount');
    }

    /**
     * Get instructor revenue.
     *
     * @param int $instructorId
     * @return float
     */
    public static function getInstructorRevenue(int $instructorId): float
    {
        $courseIds = Course::where('instructor_id', $instructorId)->pluck('id');
        
        return static::whereIn('course_id', $courseIds)
            ->completed()
            ->sum('amount');
    }

    /**
     * Get payment analytics.
     *
     * @return array
     */
    public static function getAnalytics(): array
    {
        $total = static::count();
        $completed = static::completed()->count();
        $pending = static::pending()->count();
        $failed = static::failed()->count();
        $refunded = static::refunded()->count();

        return [
            'total' => $total,
            'completed' => $completed,
            'pending' => $pending,
            'failed' => $failed,
            'refunded' => $refunded,
            'completion_rate' => $total > 0 ? round(($completed / $total) * 100, 2) : 0,
            'failure_rate' => $total > 0 ? round(($failed / $total) * 100, 2) : 0,
        ];
    }
}