<?php

namespace App\Contracts;

interface PaymentMethodLabel
{
    public function label(string $method): string;
}
