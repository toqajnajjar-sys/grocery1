<?php

namespace App\Factories;

use App\Contracts\QueryFilter;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Container\Container;
use InvalidArgumentException;

final class QueryFilterFactory
{
    public function __construct(private Container $container, private Repository $config) {}

    /** @return array<string, QueryFilter> */
    public function for(string $resource): array
    {
        $filters = [];
        foreach ($this->config->get("controller_strategies.filters.$resource", []) as $key => $definition) {
            $filter = $this->container->make($definition['class'], $definition['parameters'] ?? []);
            if (! $filter instanceof QueryFilter) {
                throw new InvalidArgumentException("Filter [$resource.$key] must implement QueryFilter.");
            }
            $filters[$key] = $filter;
        }

        return $filters;
    }
}
