<?php

namespace App\Services\Payments;

class StoredCardToken
{
    public function __construct(
        public readonly string $externalTokenId,
        public readonly ?string $cardMask = null,
    ) {}
}
