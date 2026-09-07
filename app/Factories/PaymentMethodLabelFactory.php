<?php

namespace App\Factories;

use App\Contracts\PaymentMethodLabel;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Container\Container;
use InvalidArgumentException;

final class PaymentMethodLabelFactory
{
    public function __construct(private Container $container, private Repository $config) {}

    public function make(string $method): PaymentMethodLabel
    {
        $definitions = $this->config->get('controller_strategies.payment_labels');
        $definition = $definitions[$method] ?? $definitions['default'];
        $strategy = $this->container->make($definition['class'], $definition['parameters'] ?? []);
        if (! $strategy instanceof PaymentMethodLabel) {
            throw new InvalidArgumentException("Payment label [$method] must implement PaymentMethodLabel.");
        }

        return $strategy;
    }
}
