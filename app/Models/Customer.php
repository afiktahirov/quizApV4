<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;

class Customer extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $fillable = [
        'name',
        'phone',
        'email',
        'password',
        'customer_plan_id',
        'subscription_ends_at',
        'auto_renew',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'password'             => 'hashed',
        'subscription_ends_at' => 'datetime',
        'auto_renew'           => 'boolean',
    ];

    /* ==================== ABUNƏLİK ==================== */

    public function plan()
    {
        return $this->belongsTo(CustomerPlan::class, 'customer_plan_id');
    }

    public function subscriptions()
    {
        return $this->hasMany(CustomerSubscription::class);
    }

    public function subscriptionRequests()
    {
        return $this->hasMany(CustomerSubscriptionRequest::class);
    }

    public function sessions()
    {
        return $this->hasMany(QuizSession::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function paymentMethods()
    {
        return $this->hasMany(PaymentMethod::class);
    }

    public function defaultPaymentMethod(?string $provider = null): ?PaymentMethod
    {
        return $this->paymentMethods()->where('provider', $provider ?? config('payments.default'))->first();
    }

    /**
     * Abunəliyi aktivdirmi? (paket seçilib + müddət bitməyib, güzəşt günləri nəzərə alınır)
     */
    public function hasActiveSubscription(): bool
    {
        if (! $this->customer_plan_id || ! $this->subscription_ends_at) {
            return false;
        }

        $grace = (int) config('subscriptions.customer.grace_days', 0);

        return $this->subscription_ends_at->copy()->addDays($grace)->isFuture();
    }

    public function scopeSubscribed(Builder $q): Builder
    {
        return $q->whereNotNull('customer_plan_id')
            ->whereNotNull('subscription_ends_at')
            ->where('subscription_ends_at', '>=', now());
    }

    /** Abunəliyin bitməsinə neçə gün qalıb (bitibsə mənfi/0) */
    public function daysLeft(): ?int
    {
        return $this->subscription_ends_at
            ? (int) now()->startOfDay()->diffInDays($this->subscription_ends_at->copy()->startOfDay(), false)
            : null;
    }

    /** Paket limiti (null => limitsiz). $key: 'quizzes_per_day' | 'coupons_per_month' */
    public function planLimit(string $key): ?int
    {
        return $this->plan?->{'max_' . $key};
    }

    /** Bugün oynadığı quiz sayı */
    public function quizzesPlayedToday(): int
    {
        return $this->sessions()->whereDate('started_at', today())->count();
    }

    /** Bu ay qazandığı kupon sayı */
    public function couponsThisMonth(): int
    {
        return Coupon::whereHas('session', fn ($q) => $q->where('customer_id', $this->id))
            ->where('created_at', '>=', now()->startOfMonth())
            ->count();
    }

    /** Paket limitinə görə daha bir quiz oynaya bilər? */
    public function canPlayMoreToday(): bool
    {
        $limit = $this->planLimit('quizzes_per_day');

        return $limit === null || $this->quizzesPlayedToday() < $limit;
    }

    /** Paket limitinə görə daha bir kupon qazana bilər? */
    public function canEarnMoreCoupons(): bool
    {
        $limit = $this->planLimit('coupons_per_month');

        return $limit === null || $this->couponsThisMonth() < $limit;
    }
}
