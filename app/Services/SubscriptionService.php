<?php

namespace App\Services;

use App\Models\Merchant;
use App\Models\MerchantSubscription;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\SubscriptionRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

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

    /**
     * Mağaza öz adına paket sorğusu yaradır (onlayn ödəniş inteqrasiyası gələnə qədər
     * super admin bu sorğunu əl ilə təsdiqləyir). Eyni anda bir aktiv (pending) sorğu ola bilər.
     */
    public function requestUpgrade(Merchant $merchant, Plan $plan, int $periods = 1): SubscriptionRequest
    {
        if ($merchant->subscriptionRequests()->where('status', 'pending')->exists()) {
            throw ValidationException::withMessages([
                'plan_id' => 'Artıq gözləmədə olan bir sorğunuz var.',
            ]);
        }

        $periods = max(1, $periods);

        return $merchant->subscriptionRequests()->create([
            'plan_id'  => $plan->id,
            'periods'  => $periods,
            'amount'   => (float) $plan->price * $periods,
            'currency' => $plan->currency,
            'status'   => 'pending',
        ]);
    }

    /** Super admin sorğunu təsdiqləyir → abunəlik faktiki tətbiq olunur. */
    public function approve(SubscriptionRequest $request, User $by): void
    {
        DB::transaction(function () use ($request, $by) {
            $this->grant($request->merchant, $request->plan, $request->periods, $by, 'Sorğu #' . $request->id . ' üzrə təsdiq');

            $request->update([
                'status'      => 'approved',
                'reviewed_by' => $by->id,
                'reviewed_at' => now(),
            ]);
        });
    }

    /** Onlayn ödəniş uğurla təsdiqləndikdə (bank tərəfindən) avtomatik təsdiq — admin iştirakı yoxdur. */
    public function approveViaPayment(SubscriptionRequest $request, Payment $payment): void
    {
        DB::transaction(function () use ($request, $payment) {
            $this->grant(
                $request->merchant,
                $request->plan,
                $request->periods,
                null,
                'Sorğu #' . $request->id . ' — onlayn ödəniş #' . $payment->id . ' (' . $payment->provider . ') ilə avtomatik təsdiq',
            );

            $request->update([
                'status'      => 'approved',
                'reviewed_at' => now(),
            ]);
        });
    }

    /** Super admin sorğunu rədd edir. */
    public function reject(SubscriptionRequest $request, User $by, ?string $note = null): void
    {
        $request->update([
            'status'      => 'rejected',
            'note'        => $note,
            'reviewed_by' => $by->id,
            'reviewed_at' => now(),
        ]);
    }

    /** Mağaza öz gözləmədəki sorğusunu ləğv edir. */
    public function cancelRequest(SubscriptionRequest $request): void
    {
        $request->update(['status' => 'cancelled']);
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
