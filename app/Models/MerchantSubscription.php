<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MerchantSubscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'merchant_id', 'plan_id', 'plan_name', 'amount', 'currency',
        'starts_at', 'ends_at', 'status', 'note', 'created_by',
    ];

    protected $casts = [
        'amount'     => 'decimal:2',
        'starts_at'  => 'datetime',
        'ends_at'    => 'datetime',
    ];

    public function merchant()
    {
        return $this->belongsTo(Merchant::class);
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
