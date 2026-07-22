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
     *
     * @param  bool  $saveCard  true olarsa, müvəffəqiyyətli ödənişdən sonra kart bank tərəfində
     *                          tokenləşdirilir və extractStoredToken() ilə çıxarıla bilər.
     */
    public function createPayment(
        Merchant $merchant,
        string $referenceId,
        float $amount,
        string $currency,
        string $description,
        bool $saveCard = false,
    ): PaymentSession;

    /** Bankdan sifarişin cari statusunu (server-server sorğu ilə) alır. */
    public function fetchStatus(string $externalOrderId): PaymentStatusResult;

    /** Ödənişi geri qaytarır (tam və ya qismən). */
    public function refund(string $externalOrderId, ?float $amount = null): PaymentStatusResult;

    /**
     * Yadda saxlanılan kartla, müştərinin iştirakı olmadan (server-server) yeni ödəniş icra edir.
     * Abunəlik avtomatik yenilənməsi üçün istifadə olunur.
     */
    public function chargeStoredCard(
        string $externalTokenId,
        string $referenceId,
        float $amount,
        string $currency,
        string $description,
    ): PaymentStatusResult;

    /** Uğurlu ödəniş cavabından (əgər varsa) kart tokenini çıxarır. */
    public function extractStoredToken(array $rawResponse): ?StoredCardToken;
}
