<?php

namespace App\Support;

/**
 * Çoxdilli ({"az": "...", "en": "...", "ru": "..."}) sahələri mətnə çevirmək üçün
 * köməkçi. Admin panelindəki cədvəl/sütunlarda istifadə olunur — API isə obyekti
 * olduğu kimi qaytarır, dili front seçir (bax: src/i18n).
 */
class Translatable
{
    public const LOCALES = ['az', 'en', 'ru'];

    /**
     * Dəyəri verilmiş dildə qaytarır. Sahə sadə mətndirsə olduğu kimi,
     * tərcümə yoxdursa fallback zənciri ilə (az → en → ru → ilk dolu) qaytarılır.
     */
    public static function text(mixed $value, ?string $locale = null): string
    {
        if ($value === null) {
            return '';
        }

        if (! is_array($value)) {
            return (string) $value;
        }

        foreach (array_filter([$locale, ...self::LOCALES]) as $code) {
            if (! empty($value[$code]) && is_string($value[$code])) {
                return $value[$code];
            }
        }

        $first = collect($value)->first(fn ($v) => is_string($v) && $v !== '');

        return (string) ($first ?? '');
    }

    /** Tək dilli mətni çoxdilli formata gətirir (seeder/import üçün). */
    public static function make(string $az, ?string $en = null, ?string $ru = null): array
    {
        return ['az' => $az, 'en' => $en ?? $az, 'ru' => $ru ?? $az];
    }
}
