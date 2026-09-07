<?php

namespace App\Strategies\Payments;

use App\Contracts\PaymentMethodLabel;

final class ConfiguredPaymentLabel implements PaymentMethodLabel
{
    public function __construct(private string $text) {}

    public function label(string $method): string
    {
        return $this->text;
    }
}
