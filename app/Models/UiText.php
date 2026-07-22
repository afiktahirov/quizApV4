<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class UiText extends Model
{
    protected $fillable = ['key', 'value', 'group'];

    protected $casts = ['value' => 'array'];

    protected static function booted(): void
    {
        // Admin mətn dəyişən kimi API cache-i təzələnsin
        static::saved(fn () => Cache::forget('ui_texts_map'));
        static::deleted(fn () => Cache::forget('ui_texts_map'));
    }

    /** key => {az,en,ru} xəritəsi (cache-lənmiş) */
    public static function map(): array
    {
        return Cache::remember('ui_texts_map', now()->addMinutes(10), function () {
            return static::query()->pluck('value', 'key')->toArray();
        });
    }
}
