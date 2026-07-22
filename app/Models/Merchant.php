<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Merchant extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'slug', 'status', 'bio', 'photo',
        'latitude', 'longitude', 'geojson', 'address',
        'plan_id', 'subscription_ends_at',
        'coupon_discount_type', 'coupon_value', 'coupon_ttl_hours',
    ];

    protected $casts = [
        'settings'             => 'array',
        'geojson'              => 'array',
        'subscription_ends_at' => 'datetime',
        'coupon_value'         => 'decimal:2',
    ];

    protected function location(): Attribute
    {
        return Attribute::make(
            get: fn ($value, array $attributes) => [
                'lat'     => $attributes['latitude'] ?? null,
                'lng'     => $attributes['longitude'] ?? null,
                'geojson' => $this->geojson ?? null,
            ],
            set: fn (?array $value) => [
                'latitude'  => $value['lat'] ?? null,
                'longitude' => $value['lng'] ?? null,
                'geojson'   => $value['geojson'] ?? null,
            ],
        );
    }

    /**
     * Abunəlik aktivdir? (status aktiv + müddəti bitməyib; null => limitsiz)
     */
    public function isSubscribed(): bool
    {
        return $this->status === 'active'
            && ($this->subscription_ends_at === null || $this->subscription_ends_at->isFuture());
    }

    public function scopeSubscribed(Builder $q): Builder
    {
        return $q->where('status', 'active')
            ->where(function (Builder $q) {
                $q->whereNull('subscription_ends_at')
                  ->orWhere('subscription_ends_at', '>=', now());
            });
    }

    public function users()     { return $this->hasMany(User::class); }
    public function stores()    { return $this->hasMany(Store::class); }
    public function quizzes()   { return $this->hasMany(Quiz::class); }
    public function questions() { return $this->hasMany(Question::class); }
    public function coupons()   { return $this->hasMany(Coupon::class); }
    public function sessions()  { return $this->hasMany(QuizSession::class); }
    public function ads()       { return $this->hasMany(Ad::class); }

    public function plan()          { return $this->belongsTo(Plan::class); }
    public function subscriptions() { return $this->hasMany(MerchantSubscription::class); }

    /**
     * Verilmiş resurs üçün paket limiti (null => limitsiz).
     * $key: 'quizzes' | 'questions' | 'stores' | 'ads'
     */
    public function planLimit(string $key): ?int
    {
        return $this->plan?->{'max_' . $key};
    }

    /** Həmin resursdan neçəsi mövcuddur (yalnız merchant-a aid olanlar sayılır) */
    public function usageCount(string $key): int
    {
        return match ($key) {
            'quizzes'   => $this->quizzes()->count(),
            'questions' => $this->questions()->count(), // yalnız öz sualları (qlobal baza sayılmır)
            'stores'    => $this->stores()->count(),
            'ads'       => $this->ads()->count(),
            default     => 0,
        };
    }

    /** Bu resursdan daha bir ədəd əlavə etməyə paket limiti icazə verir? */
    public function canAdd(string $key): bool
    {
        $limit = $this->planLimit($key);

        // Limit təyin olunmayıbsa (paket yoxdur və ya limitsiz) — icazə var
        if ($limit === null) {
            return true;
        }

        return $this->usageCount($key) < $limit;
    }
}
