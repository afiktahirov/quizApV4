<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * İstifadəçi (müştəri) abunəlik paketi — super admin tərəfindən yaradılır.
 * Mağaza paketləri (Plan) ilə qarışdırılmamalıdır.
 */
class CustomerPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'slug', 'price', 'currency', 'billing_period', 'trial_days',
        'max_quizzes_per_day', 'max_coupons_per_month',
        'description', 'features', 'is_active', 'sort_order',
    ];

    protected $casts = [
        'price'                 => 'decimal:2',
        'features'              => 'array',
        'is_active'             => 'boolean',
        'trial_days'            => 'integer',
        'max_quizzes_per_day'   => 'integer',
        'max_coupons_per_month' => 'integer',
    ];

    /** Bir dövr neçə aya bərabərdir (sınaq paketlərində istifadə olunmur) */
    public function periodMonths(): int
    {
        return $this->billing_period === 'yearly' ? 12 : 1;
    }

    public function isTrial(): bool
    {
        return $this->billing_period === 'trial';
    }

    public function isFree(): bool
    {
        return (float) $this->price <= 0;
    }

    public function customers()
    {
        return $this->hasMany(Customer::class);
    }

    public function subscriptions()
    {
        return $this->hasMany(CustomerSubscription::class);
    }

    /** API/front üçün sadə təsvir */
    public function toApiArray(): array
    {
        return [
            'id'                    => $this->id,
            'name'                  => $this->name,
            'slug'                  => $this->slug,
            'price'                 => (float) $this->price,
            'currency'              => $this->currency,
            'billing_period'        => $this->billing_period,
            'trial_days'            => $this->trial_days,
            'max_quizzes_per_day'   => $this->max_quizzes_per_day,
            'max_coupons_per_month' => $this->max_coupons_per_month,
            'description'           => $this->description,
            'features'              => $this->features ?? [],
            'is_free'               => $this->isFree(),
            'is_trial'              => $this->isTrial(),
        ];
    }
}
