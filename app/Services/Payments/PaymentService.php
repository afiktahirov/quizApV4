<?php

namespace App\Services\Payments;

use App\Models\Merchant;
use App\Models\Payment;
use App\Models\PaymentMethod;
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
    public function initiate(SubscriptionRequest $request, ?string $provider = null, bool $saveCard = false): PaymentSession
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
            $saveCard,
        );

        Payment::create([
            'merchant_id'              => $merchant->id,
            'subscription_request_id' => $request->id,
            'provider'                 => $provider,
            'external_order_id'       => $session->externalOrderId,
            'save_card'                => $saveCard,
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

        $gateway = $this->gateways->gateway($provider);
        $result  = $gateway->fetchStatus($externalOrderId);

        $payment->update([
            'status'       => $result->status,
            'raw_response' => $result->rawResponse,
            'paid_at'      => $result->isPaid() ? now() : null,
        ]);

        if ($result->isPaid()) {
            if ($payment->save_card) {
                $this->captureCardToken($payment, $gateway, $result);
            }

            if ($payment->subscriptionRequest?->isPending()) {
                $this->subscriptions->approveViaPayment($payment->subscriptionRequest, $payment);
            }
        }

        return $payment;
    }

    protected function captureCardToken(Payment $payment, PaymentGateway $gateway, PaymentStatusResult $result): void
    {
        $token = $gateway->extractStoredToken($result->rawResponse);

        if (! $token) {
            return;
        }

        PaymentMethod::updateOrCreate(
            ['merchant_id' => $payment->merchant_id, 'provider' => $payment->provider],
            ['external_token_id' => $token->externalTokenId, 'card_mask' => $token->cardMask],
        );
    }

    /**
     * Yadda saxlanılan kartla mağazanın CARİ paketini 1 dövr üçün avtomatik yeniləyir.
     * Müştərinin iştirakı olmadan (server-server) icra olunur — planlaşdırılmış tapşırıqdan çağırılır.
     */
    public function chargeForRenewal(Merchant $merchant, ?string $provider = null): Payment
    {
        $provider ??= $this->gateways->defaultProvider();
        $method     = $merchant->defaultPaymentMethod($provider);

        if (! $method) {
            throw new PaymentGatewayException('Mağazanın yadda saxlanılan kartı yoxdur', 'NO_STORED_CARD');
        }

        $request = $this->subscriptions->createRenewalRequest($merchant);
        $gateway = $this->gateways->gateway($provider);

        $result = $gateway->chargeStoredCard(
            $method->external_token_id,
            (string) $request->id,
            (float) $request->amount,
            $request->currency,
            'Avtomatik yenilənmə: ' . $request->plan->name . ' (sorğu #' . $request->id . ')',
        );

        $payment = Payment::create([
            'merchant_id'              => $merchant->id,
            'subscription_request_id' => $request->id,
            'provider'                 => $provider,
            'external_order_id'       => $result->externalOrderId,
            'amount'                   => $request->amount,
            'currency'                 => $request->currency,
            'status'                   => $result->status,
            'raw_response'             => $result->rawResponse,
            'paid_at'                  => $result->isPaid() ? now() : null,
        ]);

        if ($result->isPaid()) {
            $this->subscriptions->approveViaPayment($request, $payment);
        } else {
            $this->subscriptions->reject($request, null, 'Avtomatik yenilənmə uğursuz oldu (bank cavabı: ' . $result->status . ')');
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
