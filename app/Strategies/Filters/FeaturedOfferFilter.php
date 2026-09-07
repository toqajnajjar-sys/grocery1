<?php

namespace App\Strategies\Filters;

use App\Contracts\QueryFilter;
use Illuminate\Database\Eloquent\Builder;

final class FeaturedOfferFilter implements QueryFilter
{
    public function apply(Builder $query, mixed $value): void
    {
        if (filter_var($value, FILTER_VALIDATE_BOOLEAN)) {
            $query->featured();
        }
    }
}
