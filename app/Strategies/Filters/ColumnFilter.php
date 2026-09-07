<?php

namespace App\Strategies\Filters;

use App\Contracts\QueryFilter;
use Illuminate\Database\Eloquent\Builder;

final class ColumnFilter implements QueryFilter
{
    public function __construct(private string $column, private string $operator = '=') {}

    public function apply(Builder $query, mixed $value): void
    {
        $query->where($this->column, $this->operator, $value);
    }
}
