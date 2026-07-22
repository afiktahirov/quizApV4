<?php

namespace App\Services;

use App\Models\Merchant;
use App\Models\MerchantSubscription;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class SubscriptionService
{
    /**
     * Merchant-a paket təyin edir / abunəliyi uzadır.
     * Mövcud abunəlik hələ bitməyibsə, üstünə əlavə olunur (uzadılır).
     * Hər çağırış gəlir ledger-inə (merchant_subscriptions) bir sətir yazır.
     *
     * @param int $periods Neçə dövr (aylıq paket üçün ay sayı, illik üçün il sayı)
     */
    public function grant(Merchant $merchant, Plan $plan, int $periods = 1, ?User $by = null, ?string $note = null): MerchantSubscription
    {
        $periods = max(1, $periods);
        $months  = $plan->periodMonths() * $periods;

        return DB::transaction(function () use ($merchant, $plan, $periods, $months, $by, $note) {
            // Uzatma: mövcud müddət gələcəkdədirsə onun üstünə, deyilsə indidən
            $base    = ($merchant->subscription_ends_at && $merchant->subscription_ends_at->isFuture())
                ? $merchant->subscription_ends_at->copy()
                : now();
            $newEnd  = $base->copy()->addMonths($months);

            $merchant->update([
                'plan_id'              => $plan->id,
                'status'               => 'active',
                'subscription_ends_at' => $newEnd,
            ]);

            // Əvvəlki aktiv abunəni tarixçədə "expired" kimi işarələ (tək aktiv qalsın)
            $merchant->subscriptions()->where('status', 'active')->update(['status' => 'expired']);

            return $merchant->subscriptions()->create([
                'plan_id'    => $plan->id,
                'plan_name'  => $plan->name,
                'amount'     => (float) $plan->price * $periods,
                'currency'   => $plan->currency,
                'starts_at'  => now(),
                'ends_at'    => $newEnd,
                'status'     => 'active',
                'note'       => $note,
                'created_by' => $by?->id,
            ]);
        });
    }

    /** Merchant-ı bloklayır (abunəlik statusu inactive). */
    public function block(Merchant $merchant): void
    {
        $merchant->update(['status' => 'inactive']);
    }

    /** Bloku açır (status active). */
    public function unblock(Merchant $merchant): void
    {
        $merchant->update(['status' => 'active']);
    }
}
