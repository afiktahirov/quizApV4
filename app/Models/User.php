<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements FilamentUser
{
    use HasApiTokens;

    public const ROLE_SUPER_ADMIN    = 'super_admin';
    public const ROLE_MERCHANT_ADMIN = 'merchant_admin';
    public const ROLE_CASHIER        = 'cashier';

    protected $fillable = ['name', 'email', 'password', 'merchant_id', 'role'];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = ['password' => 'hashed'];

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    public function getIsAdminAttribute(): bool
    {
        return $this->role === self::ROLE_SUPER_ADMIN;
    }

    public function isMerchantAdmin(): bool
    {
        return $this->role === self::ROLE_MERCHANT_ADMIN;
    }

    public function canAccessPanel(Panel $panel): bool
    {
        // super admin həmişə girir; merchant istifadəçiləri yalnız merchant-a bağlıdırsa
        if ($this->role === self::ROLE_SUPER_ADMIN) {
            return true;
        }

        return in_array($this->role, [self::ROLE_MERCHANT_ADMIN, self::ROLE_CASHIER], true)
            && $this->merchant_id !== null;
    }
}
