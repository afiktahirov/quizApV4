<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    public const STATUS_PENDING  = 'pending';
    public const STATUS_PAID     = 'paid';
    public const STATUS_FAILED   = 'failed';
    public const STATUS_REFUNDED = 'refunded';
    public const STATUS_REVERSED = 'reversed';
    public const STATUS_EXPIRED  = 'expired';

    protected $fillable = [
        'merchant_id', 'subscription_request_id', 'provider', 'external_order_id',
        'amount', 'currency', 'status', 'raw_response', 'paid_at',
    ];

    protected $casts = [
        'amount'       => 'decimal:2',
        'raw_response' => 'array',
        'paid_at'      => 'datetime',
    ];

    public function merchant()
    {
        return $this->belongsTo(Merchant::class);
    }

    public function subscriptionRequest()
    {
        return $this->belongsTo(SubscriptionRequest::class);
    }

    public function isPaid(): bool
    {
        return $this->status === self::STATUS_PAID;
    }
}
