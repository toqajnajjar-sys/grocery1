<?php

namespace App\Strategies\Filters;

use App\Contracts\QueryFilter;
use Illuminate\Database\Eloquent\Builder;

final class MinimumPurchaseFilter implements QueryFilter
{
    public function apply(Builder $query, mixed $value): void
    {
        $query->where(function (Builder $query) use ($value) {
            $query->where('minimum_purchase', '<=', $value)->orWhereNull('minimum_purchase');
        });
    }
}
