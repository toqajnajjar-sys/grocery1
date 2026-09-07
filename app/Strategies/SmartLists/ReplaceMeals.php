<?php

namespace App\Strategies\SmartLists;

use App\Contracts\SmartListMealStrategy;
use App\Models\SmartList;

final class ReplaceMeals implements SmartListMealStrategy
{
    public function apply(SmartList $list, array $mealIds): void
    {
        $list->meals()->sync($mealIds);
    }
}
