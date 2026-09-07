<?php

namespace App\Factories;

use App\Contracts\SmartListMealStrategy;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Container\Container;
use InvalidArgumentException;

final class SmartListMealStrategyFactory
{
    public function __construct(private Container $container, private Repository $config) {}

    public function make(string $key): SmartListMealStrategy
    {
        $definition = $this->config->get("controller_strategies.smart_list_meals.$key");
        if (! is_array($definition) || ! isset($definition['class'])) {
            throw new InvalidArgumentException("Unknown smart_list_meals strategy [$key].");
        }
        $strategy = $this->container->make($definition['class'], $definition['parameters'] ?? []);
        if (! $strategy instanceof SmartListMealStrategy) {
            throw new InvalidArgumentException("Strategy [$key] must implement SmartListMealStrategy.");
        }

        return $strategy;
    }
}
