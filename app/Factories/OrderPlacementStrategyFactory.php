<?php

namespace App\Factories;

use App\Contracts\OrderPlacementStrategy;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Container\Container;
use InvalidArgumentException;

final class OrderPlacementStrategyFactory
{
    public function __construct(private Container $container, private Repository $config) {}

    public function make(string $key): OrderPlacementStrategy
    {
        $definition = $this->config->get("controller_strategies.order_placement.$key");
        if (! is_array($definition) || ! isset($definition['class'])) {
            throw new InvalidArgumentException("Unknown order_placement strategy [$key].");
        }
        $strategy = $this->container->make($definition['class'], $definition['parameters'] ?? []);
        if (! $strategy instanceof OrderPlacementStrategy) {
            throw new InvalidArgumentException("Strategy [$key] must implement OrderPlacementStrategy.");
        }

        return $strategy;
    }
}
