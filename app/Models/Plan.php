<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'slug', 'price', 'currency', 'billing_period',
        'max_quizzes', 'max_questions', 'max_stores', 'max_ads',
        'description', 'is_active', 'sort_order',
    ];

    protected $casts = [
        'price'         => 'decimal:2',
        'is_active'     => 'boolean',
        'max_quizzes'   => 'integer',
        'max_questions' => 'integer',
        'max_stores'    => 'integer',
        'max_ads'       => 'integer',
    ];

    /** Bir dövrün (billing_period) neçə aya bərabər olduğu */
    public function periodMonths(): int
    {
        return $this->billing_period === 'yearly' ? 12 : 1;
    }

    public function merchants()
    {
        return $this->hasMany(Merchant::class);
    }

    public function subscriptions()
    {
        return $this->hasMany(MerchantSubscription::class);
    }
}
