<?php

namespace App\Services\Payments;

use App\Models\Merchant;

/**
 * Bütün bank/ödəniş provayderlərinin implement etməli olduğu interfeys.
 * Yeni bank qoşmaq üçün bu interfeysi implement edən sinif yazıb
 * config/payments.php-də qeydiyyatdan keçirmək kifayətdir.
 */
interface PaymentGateway
{
    /**
     * Ödəniş sessiyası yaradır və müştərinin yönləndiriləcəyi HPP (Hosted Payment Page) URL-ini qaytarır.
     */
    public function createPayment(
        Merchant $merchant,
        string $referenceId,
        float $amount,
        string $currency,
        string $description,
    ): PaymentSession;

    /** Bankdan sifarişin cari statusunu (server-server sorğu ilə) alır. */
    public function fetchStatus(string $externalOrderId): PaymentStatusResult;

    /** Ödənişi geri qaytarır (tam və ya qismən). */
    public function refund(string $externalOrderId, ?float $amount = null): PaymentStatusResult;
}
