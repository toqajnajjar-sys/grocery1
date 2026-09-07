<?php

namespace App\Contracts;

use Illuminate\Database\Eloquent\Builder;

interface QueryFilter
{
    public function apply(Builder $query, mixed $value): void;
}
