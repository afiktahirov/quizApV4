<?php

namespace App\Filament\Concerns;

use Filament\Facades\Filament;

/**
 * Yeni qeydiyyatdan keçmiş mağazalar bir paket (pulsuz sınaq da daxil olmaqla) seçənə
 * qədər admin paneldə HEÇ bir funksionallıq görməməlidir — yalnız "Abunəliyim" səhifəsi
 * açıq qalır. Bu trait-i merchant_admin/cashier üçün funksional resurslara əlavə et.
 *
 * Super admin bu şərtdən həmişə azaddır.
 */
trait RequiresActivePlan
{
    public static function merchantHasSelectedPlan(): bool
    {
        $user = Filament::auth()->user();

        if (! $user || $user->is_admin) {
            return true;
        }

        return $user->merchant?->plan_id !== null;
    }
}
