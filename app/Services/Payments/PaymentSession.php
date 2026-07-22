<?php

namespace App\Services\Payments;

class PaymentSession
{
    public function __construct(
        public readonly string $externalOrderId,
        public readonly string $redirectUrl,
        public readonly array $rawResponse = [],
    ) {}
}
