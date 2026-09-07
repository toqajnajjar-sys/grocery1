<?php

namespace App\Strategies\Payments;

use App\Contracts\PaymentMethodLabel;

final class HumanizedPaymentLabel implements PaymentMethodLabel
{
    public function label(string $method): string
    {
        return ucfirst(str_replace('_', ' ', $method));
    }
}
