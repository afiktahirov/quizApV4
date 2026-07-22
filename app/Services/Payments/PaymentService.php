<?php

namespace App\Services\Payments;

use App\Models\Payment;
use App\Models\SubscriptionRequest;
use App\Services\SubscriptionService;

/**
 * Ödəniş axınını orkestrasiya edir: hansı bank seçildiyindən asılı olmayaraq
 * eyni məntiqi işlədir (PaymentGatewayManager provayderi həll edir).
 */
class PaymentService
{
    public function __construct(
        protected PaymentGatewayManager $gateways,
        protected SubscriptionService $subscriptions,
    ) {}

    /** Sorğu üçün ödəniş sessiyası başladır, "pending" Payment sətri yaradır və bank HPP URL-ini qaytarır. */
    public function initiate(SubscriptionRequest $request, ?string $provider = null): PaymentSession
    {
        $provider ??= $this->gateways->defaultProvider();
        $gateway    = $this->gateways->gateway($provider);
        $merchant   = $request->merchant;

        $session = $gateway->createPayment(
            $merchant,
            (string) $request->id,
            (float) $request->amount,
            $request->currency,
            'Abunəlik: ' . $request->plan->name . ' (sorğu #' . $request->id . ')',
        );

        Payment::create([
            'merchant_id'              => $merchant->id,
            'subscription_request_id' => $request->id,
            'provider'                 => $provider,
            'external_order_id'       => $session->externalOrderId,
            'amount'                   => $request->amount,
            'currency'                 => $request->currency,
            'status'                   => Payment::STATUS_PENDING,
            'raw_response'             => $session->rawResponse,
        ]);

        return $session;
    }

    /**
     * Bankdan qayıdışdan sonra (və ya planlaşdırılmış sinxronizasiyada) statusu
     * serverdən-serverə yenidən yoxlayır və uğurludursa abunəliyi avtomatik tətbiq edir.
     * Brauzerdən gələn redirect parametrlərinə HEÇ VAXT etibar edilmir.
     */
    public function handleReturn(string $provider, string $externalOrderId): Payment
    {
        $payment = Payment::where('provider', $provider)
            ->where('external_order_id', $externalOrderId)
            ->latest()
            ->firstOrFail();

        // Artıq son (terminal) statusdadırsa təkrar işlənmir — idempotentlik.
        if ($payment->status !== Payment::STATUS_PENDING) {
            return $payment;
        }

        $result = $this->gateways->gateway($provider)->fetchStatus($externalOrderId);

        $payment->update([
            'status'       => $result->status,
            'raw_response' => $result->rawResponse,
            'paid_at'      => $result->isPaid() ? now() : null,
        ]);

        if ($result->isPaid() && $payment->subscriptionRequest?->isPending()) {
            $this->subscriptions->approveViaPayment($payment->subscriptionRequest, $payment);
        }

        return $payment;
    }

    /**
     * Bank tərəfindən brauzer redirect-i heç vaxt baş vermədiyi (istifadəçi səhifəni bağladığı)
     * hallar üçün ehtiyat mexanizmi. Planlaşdırılmış tapşırıq kimi işlədilməlidir.
     */
    public function syncPending(int $olderThanMinutes = 2): int
    {
        $checked = 0;

        Payment::where('status', Payment::STATUS_PENDING)
            ->whereNotNull('external_order_id')
            ->where('created_at', '<', now()->subMinutes($olderThanMinutes))
            ->each(function (Payment $payment) use (&$checked) {
                $this->handleReturn($payment->provider, $payment->external_order_id);
                $checked++;
            });

        return $checked;
    }
}
