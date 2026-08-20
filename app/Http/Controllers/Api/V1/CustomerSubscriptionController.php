<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\CustomerPlan;
use App\Models\CustomerSubscriptionRequest;
use App\Services\CustomerSubscriptionService;
use App\Services\Payments\PaymentGatewayException;
use App\Services\Payments\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * İstifadəçi abunəliyi API-si.
 * Axın: paketlər → sorğu yarat → bank HPP-yə yönləndir → bank qayıdışı
 * (PaymentReturnController) → status server-server yoxlanılır → abunəlik aktivləşir.
 */
class CustomerSubscriptionController extends Controller
{
    public function __construct(
        protected CustomerSubscriptionService $subscriptions,
        protected PaymentService $payments,
    ) {}

    /** PUBLIC — satışda olan paketlər */
    public function plans()
    {
        $plans = CustomerPlan::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('price')
            ->get();

        return response()->json([
            'plans'    => $plans->map(fn (CustomerPlan $p) => $p->toApiArray())->values(),
            'required' => (bool) config('subscriptions.customer.enabled'),
            'gate'     => config('subscriptions.customer.gate'),
        ]);
    }

    /** Cari abunəlik vəziyyəti */
    public function show(Request $request)
    {
        /** @var Customer $customer */
        $customer = $request->user('customer')->loadMissing('plan');

        return response()->json($this->statePayload($customer));
    }

    /**
     * Paket sorğusu yarat.
     * Pulsuz/sınaq paketdə dərhal aktivləşir, ödənişli paketdə bank URL-i qaytarılır.
     */
    public function subscribe(Request $request)
    {
        /** @var Customer $customer */
        $customer = $request->user('customer');

        $data = $request->validate([
            'plan_id'   => 'required|integer|exists:customer_plans,id',
            'periods'   => 'nullable|integer|min:1|max:24',
            'save_card' => 'nullable|boolean',
        ]);

        $plan = CustomerPlan::findOrFail($data['plan_id']);

        try {
            $subRequest = $this->subscriptions->requestPlan($customer, $plan, (int) ($data['periods'] ?? 1));
        } catch (ValidationException $e) {
            return response()->json([
                'message' => collect($e->errors())->flatten()->first(),
                'errors'  => $e->errors(),
            ], 422);
        }

        // Pulsuz paket / sınaq — ödəniş tələb olunmur
        if ($plan->isFree()) {
            $this->subscriptions->approveFree($subRequest);

            return response()->json([
                'status'       => 'activated',
                'message'      => $plan->isTrial() ? 'Pulsuz sınaq aktivləşdirildi.' : 'Paket aktivləşdirildi.',
                'subscription' => $this->statePayload($customer->fresh()->load('plan')),
            ]);
        }

        return $this->startPayment($subRequest, (bool) ($data['save_card'] ?? false));
    }

    /** Gözləmədə qalmış sorğunun ödənişini (yenidən) başlat */
    public function pay(Request $request)
    {
        /** @var Customer $customer */
        $customer = $request->user('customer');

        $data = $request->validate(['save_card' => 'nullable|boolean']);

        $pending = $this->pendingRequest($customer);

        if (! $pending) {
            return response()->json(['message' => 'Gözləmədə olan sorğunuz yoxdur.'], 422);
        }

        return $this->startPayment($pending, (bool) ($data['save_card'] ?? false));
    }

    /** Gözləmədə olan sorğunu ləğv et */
    public function cancelRequest(Request $request)
    {
        /** @var Customer $customer */
        $customer = $request->user('customer');

        $pending = $this->pendingRequest($customer);

        if ($pending) {
            $this->subscriptions->cancelRequest($pending);
        }

        return response()->json([
            'message'      => 'Sorğu ləğv edildi.',
            'subscription' => $this->statePayload($customer->fresh()->load('plan')),
        ]);
    }

    /** Avtomatik yenilənməni aç/söndür (yalnız yadda saxlanılan kart varsa) */
    public function toggleAutoRenew(Request $request)
    {
        /** @var Customer $customer */
        $customer = $request->user('customer');

        $data = $request->validate(['auto_renew' => 'required|boolean']);

        if ($data['auto_renew'] && ! $customer->defaultPaymentMethod()) {
            return response()->json([
                'message' => 'Avtomatik yenilənmə üçün əvvəlcə ödəniş zamanı kartınızı yadda saxlamalısınız.',
            ], 422);
        }

        $customer->update(['auto_renew' => (bool) $data['auto_renew']]);

        return response()->json([
            'message'      => $customer->auto_renew ? 'Avtomatik yenilənmə aktivdir.' : 'Avtomatik yenilənmə söndürüldü.',
            'subscription' => $this->statePayload($customer->fresh()->load('plan')),
        ]);
    }

    /** Yadda saxlanılan kartı sil (avtomatik yenilənmə də söndürülür) */
    public function removeCard(Request $request)
    {
        /** @var Customer $customer */
        $customer = $request->user('customer');

        $customer->defaultPaymentMethod()?->delete();
        $customer->update(['auto_renew' => false]);

        return response()->json([
            'message'      => 'Kart silindi.',
            'subscription' => $this->statePayload($customer->fresh()->load('plan')),
        ]);
    }

    /** Abunəlik və ödəniş tarixçəsi */
    public function history(Request $request)
    {
        /** @var Customer $customer */
        $customer = $request->user('customer');

        return response()->json([
            'subscriptions' => $customer->subscriptions()->with('plan')->latest()->limit(24)->get()
                ->map(fn ($s) => [
                    'id'         => $s->id,
                    'plan_name'  => $s->plan_name,
                    'amount'     => (float) $s->amount,
                    'currency'   => $s->currency,
                    'starts_at'  => $s->starts_at,
                    'ends_at'    => $s->ends_at,
                    'status'     => $s->status,
                    'note'       => $s->note,
                ])->values(),
            'requests' => $customer->subscriptionRequests()->with('plan')->latest()->limit(24)->get()
                ->map(fn ($r) => [
                    'id'         => $r->id,
                    'plan_name'  => $r->plan?->name,
                    'periods'    => $r->periods,
                    'amount'     => (float) $r->amount,
                    'currency'   => $r->currency,
                    'status'     => $r->status,
                    'created_at' => $r->created_at,
                ])->values(),
        ]);
    }

    /* ==================== köməkçilər ==================== */

    protected function pendingRequest(Customer $customer): ?CustomerSubscriptionRequest
    {
        return $customer->subscriptionRequests()->where('status', 'pending')->latest()->first();
    }

    protected function startPayment(CustomerSubscriptionRequest $request, bool $saveCard)
    {
        try {
            $session = $this->payments->initiateForCustomer($request, saveCard: $saveCard);
        } catch (PaymentGatewayException $e) {
            return response()->json([
                'message' => 'Ödəniş başladıla bilmədi: ' . $e->getMessage(),
            ], 502);
        }

        return response()->json([
            'status'       => 'payment_required',
            'payment_url'  => $session->redirectUrl,
            'request_id'   => $request->id,
            'amount'       => (float) $request->amount,
            'currency'     => $request->currency,
        ]);
    }

    /** Front-un abunəlik ekranı üçün tam vəziyyət */
    public function statePayload(Customer $customer): array
    {
        $pending = $this->pendingRequest($customer);
        $card    = $customer->defaultPaymentMethod();
        $plan    = $customer->plan;

        return [
            'is_active'            => $customer->hasActiveSubscription(),
            'required'             => (bool) config('subscriptions.customer.enabled'),
            'gate'                 => config('subscriptions.customer.gate'),
            'plan'                 => $plan?->toApiArray(),
            'subscription_ends_at' => $customer->subscription_ends_at,
            'days_left'            => $customer->daysLeft(),
            'auto_renew'           => (bool) $customer->auto_renew,
            'usage'                => [
                'quizzes_today'         => $customer->quizzesPlayedToday(),
                'max_quizzes_per_day'   => $customer->planLimit('quizzes_per_day'),
                'coupons_this_month'    => $customer->couponsThisMonth(),
                'max_coupons_per_month' => $customer->planLimit('coupons_per_month'),
            ],
            'pending_request' => $pending ? [
                'id'        => $pending->id,
                'plan_name' => $pending->plan?->name,
                'periods'   => $pending->periods,
                'amount'    => (float) $pending->amount,
                'currency'  => $pending->currency,
            ] : null,
            'payment_method' => $card ? [
                'provider'  => $card->provider,
                'card_mask' => $card->card_mask,
            ] : null,
        ];
    }
}
