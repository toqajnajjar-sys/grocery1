<?php

namespace App\Strategies\Orders;

use App\Contracts\OrderPlacementStrategy;

final class ImmediateOrderPlacement implements OrderPlacementStrategy
{
    public function attributes(): array
    {
        return ['status' => 'placed', 'placed_at' => now()];
    }
}
