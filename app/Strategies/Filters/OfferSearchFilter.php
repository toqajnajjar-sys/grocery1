<?php

namespace App\Strategies\Filters;

use App\Contracts\QueryFilter;
use Illuminate\Database\Eloquent\Builder;

final class OfferSearchFilter implements QueryFilter
{
    public function apply(Builder $query, mixed $value): void
    {
        $query->where(function (Builder $query) use ($value) {
            $query->where('title', 'like', "%{$value}%")->orWhere('code', 'like', "%{$value}%");
        });
    }
}
