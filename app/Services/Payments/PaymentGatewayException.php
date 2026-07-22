<?php

namespace App\Services\Payments;

use Exception;

class PaymentGatewayException extends Exception
{
    public function __construct(
        string $message = '',
        protected string $errorCode = 'UNKNOWN',
        protected array $errorDetails = [],
    ) {
        parent::__construct($message);
    }

    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    public function getErrorDetails(): array
    {
        return $this->errorDetails;
    }
}
