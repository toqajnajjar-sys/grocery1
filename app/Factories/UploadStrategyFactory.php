<?php

namespace App\Factories;

use App\Contracts\UploadStrategy;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Container\Container;
use InvalidArgumentException;

final class UploadStrategyFactory
{
    public function __construct(private Container $container, private Repository $config) {}

    public function make(string $key): UploadStrategy
    {
        $definition = $this->config->get("controller_strategies.uploads.$key");
        if (! is_array($definition) || ! isset($definition['class'])) {
            throw new InvalidArgumentException("Unknown uploads strategy [$key].");
        }
        $strategy = $this->container->make($definition['class'], $definition['parameters'] ?? []);
        if (! $strategy instanceof UploadStrategy) {
            throw new InvalidArgumentException("Strategy [$key] must implement UploadStrategy.");
        }

        return $strategy;
    }
}
