<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/** Müştərinin abunəlik tarixçəsi (eyni zamanda gəlir ledger-i). */
class CustomerSubscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id', 'customer_plan_id', 'plan_name', 'amount', 'currency',
        'starts_at', 'ends_at', 'status', 'note', 'created_by',
    ];

    protected $casts = [
        'amount'    => 'decimal:2',
        'starts_at' => 'datetime',
        'ends_at'   => 'datetime',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function plan()
    {
        return $this->belongsTo(CustomerPlan::class, 'customer_plan_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
