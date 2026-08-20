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
        'merchant_id', 'customer_id',
        'subscription_request_id', 'customer_subscription_request_id',
        'provider', 'external_order_id', 'save_card',
        'amount', 'currency', 'status', 'raw_response', 'paid_at',
    ];

    protected $casts = [
        'amount'       => 'decimal:2',
        'raw_response' => 'array',
        'paid_at'      => 'datetime',
        'save_card'    => 'boolean',
    ];

    public function merchant()
    {
        return $this->belongsTo(Merchant::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function subscriptionRequest()
    {
        return $this->belongsTo(SubscriptionRequest::class);
    }

    public function customerSubscriptionRequest()
    {
        return $this->belongsTo(CustomerSubscriptionRequest::class, 'customer_subscription_request_id');
    }

    /** Ödəniş müştəri abunəliyinə aiddir? (əks halda mağaza abunəliyidir) */
    public function isCustomerPayment(): bool
    {
        return $this->customer_id !== null;
    }

    public function isPaid(): bool
    {
        return $this->status === self::STATUS_PAID;
    }
}
