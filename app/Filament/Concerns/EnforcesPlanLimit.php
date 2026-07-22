<?php

namespace App\Filament\Concerns;

use Filament\Facades\Filament;

trait EnforcesPlanLimit
{
    /**
     * Paket limitinə görə yeni qeyd yaratmaq olar?
     * - super admin: həmişə
     * - merchant admin: abunəlik aktiv VƏ paket limiti dolmayıbsa
     * - digərləri (kassir): xeyr
     *
     * $key: 'quizzes' | 'questions' | 'stores' | 'ads'
     */
    protected static function canCreateWithinPlan(string $key): bool
    {
        $user = Filament::auth()->user();

        if (! $user) {
            return false;
        }

        if ($user->is_admin) {
            return true;
        }

        $merchant = $user->merchant;

        return $user->isMerchantAdmin()
            && $merchant !== null
            && $merchant->isSubscribed()
            && $merchant->canAdd($key);
    }
}
