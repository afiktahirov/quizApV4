<?php

namespace App\Services\Payments\KapitalBank;

use App\Models\Merchant;
use App\Models\Payment;
use App\Services\Payments\PaymentGateway;
use App\Services\Payments\PaymentGatewayException;
use App\Services\Payments\PaymentSession;
use App\Services\Payments\PaymentStatusResult;
use Illuminate\Support\Facades\Http;

/**
 * Kapital Bank (Birbank Business) e-commerce ödəniş şlüzü.
 *
 * Rəsmi API: https://txpgtst.kapitalbank.az/api (test) / https://e-commerce.kapitalbank.az/api (production)
 * Autentifikasiya: HTTP Basic Auth (bank tərəfindən verilən terminal login/şifrə).
 *
 * Axın:
 *  1. POST /order — sifariş yaradılır, cavabda hppUrl + id + password gəlir.
 *  2. Müştəri "{hppUrl}?id={id}&password={password}" ünvanına yönləndirilir (bankın ödəniş səhifəsi).
 *  3. Ödənişdən sonra bank müştərini bizim redirect URL-imizə qaytarır.
 *  4. Biz həmin an brauzerdən gələn parametrlərə etibar ETMİRİK — GET /order/{id} ilə
 *     serverdən-serverə statusu yenidən yoxlayırıq (fetchStatus()).
 */
class KapitalBankGateway implements PaymentGateway
{
    public const PROVIDER_KEY = 'kapital_bank';

    protected string $baseUrl;
    protected string $hppUrl;

    public function __construct(protected array $config)
    {
        $mode = $config['mode'] ?? 'test';
        $this->baseUrl = $config['base_url'][$mode];
        $this->hppUrl  = $config['hpp_url'][$mode];
    }

    public function createPayment(
        Merchant $merchant,
        string $referenceId,
        float $amount,
        string $currency,
        string $description,
    ): PaymentSession {
        $response = $this->request('POST', '/order', [
            'order' => [
                'typeRid'        => 'Order_SMS',
                'amount'         => (string) $amount,
                'currency'       => $currency,
                'language'       => $this->config['language'] ?? 'az',
                'description'    => $description,
                'hppRedirectUrl' => route('payments.return', ['provider' => self::PROVIDER_KEY]),
            ],
        ]);

        $order = $response['order'] ?? [];

        if (! isset($order['id'], $order['hppUrl'], $order['password'])) {
            throw new PaymentGatewayException('Kapital Bank sifariş cavabı natamamdır', 'INVALID_RESPONSE', $response);
        }

        return new PaymentSession(
            externalOrderId: (string) $order['id'],
            redirectUrl: $order['hppUrl'] . '?id=' . $order['id'] . '&password=' . $order['password'],
            rawResponse: $response,
        );
    }

    public function fetchStatus(string $externalOrderId): PaymentStatusResult
    {
        $response = $this->request('GET', "/order/{$externalOrderId}?tranDetailLevel=2&orderDetailLevel=2");

        $status = $response['order']['status'] ?? 'Unknown';

        return new PaymentStatusResult($this->mapStatus($status), $response);
    }

    public function refund(string $externalOrderId, ?float $amount = null): PaymentStatusResult
    {
        $tran = ['phase' => 'Single', 'type' => 'Refund'];

        if ($amount !== null) {
            $tran['amount'] = (string) $amount;
        }

        $this->request('POST', "/order/{$externalOrderId}/exec-tran", ['tran' => $tran]);

        return $this->fetchStatus($externalOrderId);
    }

    protected function mapStatus(string $bankStatus): string
    {
        return match ($bankStatus) {
            'FullyPaid', 'PartiallyPaid', 'Approved' => Payment::STATUS_PAID,
            'Declined' => Payment::STATUS_FAILED,
            'Refunded' => Payment::STATUS_REFUNDED,
            'Reversed' => Payment::STATUS_REVERSED,
            'Expired'  => Payment::STATUS_EXPIRED,
            default    => Payment::STATUS_PENDING,
        };
    }

    protected function request(string $method, string $endpoint, array $data = []): array
    {
        $request = Http::withBasicAuth($this->config['username'], $this->config['password'])
            ->timeout($this->config['timeout'] ?? 30)
            ->acceptJson();

        $response = $method === 'GET'
            ? $request->get($this->baseUrl . $endpoint)
            : $request->asJson()->post($this->baseUrl . $endpoint, $data);

        $body = $response->json() ?? [];

        if ($response->failed()) {
            throw new PaymentGatewayException(
                $body['errorDescription'] ?? 'Kapital Bank API xətası',
                $body['errorCode'] ?? 'UNKNOWN',
                $body,
            );
        }

        return $body;
    }
}
