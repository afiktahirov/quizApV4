<?php

namespace App\Models;

use App\Support\Translatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Ad extends Model
{
    use HasFactory;

    protected $fillable = [
        'merchant_id',
        'title',
        'image_path',
        'content',
        'status',
        'starts_at',
        'ends_at',
    ];

    protected $casts = [
        // Çoxdilli mətnlər — {"az": "...", "en": "...", "ru": "..."}
        'title'     => 'array',
        'content'   => 'array',
        'starts_at' => 'datetime',
        'ends_at'   => 'datetime',
    ];

    public function merchant()
    {
        return $this->belongsTo(Merchant::class);
    }

    /** Hazırda göstərilməli olan reklamlar (status + tarix aralığı) */
    public function scopeVisible(Builder $q): Builder
    {
        return $q->where('status', 'active')
            ->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', now()));
    }

    /** Panel/loglar üçün başlığın oxunaqlı mətni */
    public function titleText(?string $locale = null): string
    {
        return Translatable::text($this->title, $locale);
    }

    /** Şəklin tam URL-i (yüklənməyibsə null) */
    public function imageUrl(): ?string
    {
        return $this->image_path ? asset('storage/' . $this->image_path) : null;
    }
}
