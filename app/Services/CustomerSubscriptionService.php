<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\CustomerPlan;
use App\Models\CustomerSubscription;
use App\Models\CustomerSubscriptionRequest;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * İstifadəçi (müştəri) abunəliklərinin biznes məntiqi.
 * Mağaza tərəfindəki SubscriptionService ilə eyni prinsiplə işləyir,
 * amma customers / customer_plans cədvəlləri üzərində.
 */
class CustomerSubscriptionService
{
    /**
     * Müştəriyə paket verir / abunəliyi uzadır.
     * Mövcud abunəlik hələ bitməyibsə, üstünə əlavə olunur (uzadılır).
     *
     * @param int $periods Neçə dövr (aylıq paket üçün ay sayı, illik üçün il sayı)
     */
    public function grant(Customer $customer, CustomerPlan $plan, int $periods = 1, ?User $by = null, ?string $note = null): CustomerSubscription
    {
        $periods = max(1, $periods);

        return DB::transaction(function () use ($customer, $plan, $periods, $by, $note) {
            $base = ($customer->subscription_ends_at && $customer->subscription_ends_at->isFuture())
                ? $customer->subscription_ends_at->copy()
                : now();

            // Sınaq paketləri gün-əsaslı, digərləri ay-əsaslı müddətlə işləyir.
            $newEnd = $plan->isTrial()
                ? $base->copy()->addDays(($plan->trial_days ?: 1) * $periods)
                : $base->copy()->addMonths($plan->periodMonths() * $periods);

            $customer->update([
                'customer_plan_id'     => $plan->id,
                'subscription_ends_at' => $newEnd,
            ]);

            // Əvvəlki aktiv sətri tarixçədə bağla — tək aktiv abunəlik qalsın
            $customer->subscriptions()->where('status', 'active')->update(['status' => 'expired']);

            return $customer->subscriptions()->create([
                'customer_plan_id' => $plan->id,
                'plan_name'        => $plan->name,
                'amount'           => (float) $plan->price * $periods,
                'currency'         => $plan->currency,
                'starts_at'        => now(),
                'ends_at'          => $newEnd,
                'status'           => 'active',
                'note'             => $note,
                'created_by'       => $by?->id,
            ]);
        });
    }

    /**
     * Müştəri paket sorğusu yaradır. Eyni anda yalnız bir gözləmədə (pending) sorğu ola bilər.
     * Sınaq paketi hər müştəriyə yalnız bir dəfə verilir.
     */
    public function requestPlan(Customer $customer, CustomerPlan $plan, int $periods = 1): CustomerSubscriptionRequest
    {
        if (! $plan->is_active) {
            throw ValidationException::withMessages([
                'plan_id' => 'Bu paket artıq satışda deyil.',
            ]);
        }

        if ($customer->subscriptionRequests()->where('status', 'pending')->exists()) {
            throw ValidationException::withMessages([
                'plan_id' => 'Artıq gözləmədə olan bir sorğunuz var.',
            ]);
        }

        if ($plan->isTrial() && $customer->subscriptions()->where('customer_plan_id', $plan->id)->exists()) {
            throw ValidationException::withMessages([
                'plan_id' => 'Pulsuz sınaq paketindən yalnız bir dəfə istifadə edə bilərsiniz.',
            ]);
        }

        $periods = $plan->isTrial() ? 1 : max(1, $periods);

        return $customer->subscriptionRequests()->create([
            'customer_plan_id' => $plan->id,
            'periods'          => $periods,
            'amount'           => (float) $plan->price * $periods,
            'currency'         => $plan->currency,
            'status'           => 'pending',
        ]);
    }

    /** Super admin sorğunu əl ilə təsdiqləyir (məs. bank xaricində ödəniş). */
    public function approve(CustomerSubscriptionRequest $request, User $by): void
    {
        DB::transaction(function () use ($request, $by) {
            $this->grant($request->customer, $request->plan, $request->periods, $by, 'Sorğu #' . $request->id . ' üzrə təsdiq');

            $request->update([
                'status'      => 'approved',
                'reviewed_by' => $by->id,
                'reviewed_at' => now(),
            ]);
        });
    }

    /** Pulsuz paket / sınaq — ödənişə ehtiyac yoxdur, dərhal aktivləşir. */
    public function approveFree(CustomerSubscriptionRequest $request): void
    {
        DB::transaction(function () use ($request) {
            $this->grant($request->customer, $request->plan, $request->periods, null, 'Pulsuz paket/sınaq — avtomatik təsdiq');

            $request->update([
                'status'      => 'approved',
                'reviewed_at' => now(),
            ]);
        });
    }

    /** Onlayn ödəniş bank tərəfindən təsdiqləndikdə avtomatik aktivləşdirmə. */
    public function approveViaPayment(CustomerSubscriptionRequest $request, Payment $payment): void
    {
        DB::transaction(function () use ($request, $payment) {
            $this->grant(
                $request->customer,
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

    public function reject(CustomerSubscriptionRequest $request, ?User $by, ?string $note = null): void
    {
        $request->update([
            'status'      => 'rejected',
            'note'        => $note,
            'reviewed_by' => $by?->id,
            'reviewed_at' => now(),
        ]);
    }

    public function cancelRequest(CustomerSubscriptionRequest $request): void
    {
        $request->update(['status' => 'cancelled']);
    }

    /**
     * Müştərinin CARİ paketini 1 dövr üçün avtomatik yenilənmə sorğusu
     * (yadda saxlanılan kartla ödəniş üçün). Bax: PaymentService::chargeCustomerRenewal.
     */
    public function createRenewalRequest(Customer $customer): CustomerSubscriptionRequest
    {
        if (! $customer->customer_plan_id) {
            throw new \RuntimeException('Müştərinin aktiv paketi yoxdur, avtomatik yenilənə bilməz.');
        }

        if ($customer->subscriptionRequests()->where('status', 'pending')->exists()) {
            throw ValidationException::withMessages([
                'plan_id' => 'Artıq gözləmədə olan bir sorğu var.',
            ]);
        }

        $plan = $customer->plan;

        // Sınaq/pulsuz paket avtomatik yenilənmir
        if ($plan->isTrial() || $plan->isFree()) {
            throw new \RuntimeException('Pulsuz/sınaq paketi avtomatik yenilənmir.');
        }

        return $customer->subscriptionRequests()->create([
            'customer_plan_id' => $plan->id,
            'periods'          => 1,
            'amount'           => (float) $plan->price,
            'currency'         => $plan->currency,
            'status'           => 'pending',
            'note'             => 'Avtomatik yenilənmə',
        ]);
    }

    /** Avtomatik yenilənməni söndürür — mövcud müddət saxlanılır. */
    public function cancelAutoRenew(Customer $customer): void
    {
        $customer->update(['auto_renew' => false]);
    }
}
