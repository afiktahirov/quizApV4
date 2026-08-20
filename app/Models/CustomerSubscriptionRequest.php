<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/** Müştərinin paket alma/uzatma sorğusu (ödəniş bu sorğuya bağlanır). */
class CustomerSubscriptionRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id', 'customer_plan_id', 'periods', 'amount', 'currency',
        'status', 'note', 'reviewed_by', 'reviewed_at',
    ];

    protected $casts = [
        'amount'      => 'decimal:2',
        'periods'     => 'integer',
        'reviewed_at' => 'datetime',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function plan()
    {
        return $this->belongsTo(CustomerPlan::class, 'customer_plan_id');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function payments()
    {
        return $this->hasMany(Payment::class, 'customer_subscription_request_id');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }
}
