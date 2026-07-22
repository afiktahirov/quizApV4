<?php

namespace App\Services\Payments;

use InvalidArgumentException;

class PaymentGatewayManager
{
    /** @var array<string, PaymentGateway> */
    protected array $resolved = [];

    public function defaultProvider(): string
    {
        return (string) config('payments.default');
    }

    public function gateway(?string $provider = null): PaymentGateway
    {
        $provider ??= $this->defaultProvider();

        if (isset($this->resolved[$provider])) {
            return $this->resolved[$provider];
        }

        $config = config("payments.providers.{$provider}");

        if (! $config || ! isset($config['driver'])) {
            throw new InvalidArgumentException("Naməlum ödəniş provayderi: [{$provider}]");
        }

        return $this->resolved[$provider] = new $config['driver']($config);
    }
}
