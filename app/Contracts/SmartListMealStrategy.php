<?php

namespace App\Contracts;

use App\Models\SmartList;

interface SmartListMealStrategy
{
    public function apply(SmartList $list, array $mealIds): void;
}
