<?php

namespace App\Services;

use App\Factories\QueryFilterFactory;
use Illuminate\Database\Eloquent\Builder;

final class QueryFilterPipeline
{
    public function __construct(private QueryFilterFactory $factory) {}

    public function apply(string $resource, Builder $query, array $input): Builder
    {
        foreach ($this->factory->for($resource) as $key => $filter) {
            if (array_key_exists($key, $input)) {
                $filter->apply($query, $input[$key]);
            }
        }

        return $query;
    }
}
