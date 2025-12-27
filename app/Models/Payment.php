<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'course_id',
        'amount',
        'status',
        'payment_method',
        'transaction_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Payment statuses.
     */
    const STATUSES = [
        'pending',
        'completed',
        'failed',
        'refunded',
    ];

    /**
     * Payment methods.
     */
    const METHODS = [
        'stripe',
        'paypal',
        'manual',
    ];

    /**
     * Get the user that owns the payment.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the course that owns the payment.
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }
}