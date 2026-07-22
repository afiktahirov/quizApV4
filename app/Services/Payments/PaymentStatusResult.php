<?php

namespace App\Services\Payments;

use App\Models\Payment;

class PaymentStatusResult
{
    /**
     * @param  string  $status  App\Models\Payment::STATUS_* dəyərlərindən biri (provayderə görə normallaşdırılmış)
     */
    public function __construct(
        public readonly string $status,
        public readonly array $rawResponse = [],
        /** Yalnız yeni sifariş yaradan əməliyyatlarda dolur (məs. chargeStoredCard). */
        public readonly ?string $externalOrderId = null,
    ) {}

    public function isPaid(): bool
    {
        return $this->status === Payment::STATUS_PAID;
    }
}
