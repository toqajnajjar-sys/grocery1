<?php

namespace App\Strategies\Orders;

use App\Contracts\OrderPlacementStrategy;

final class HostedCheckoutOrderPlacement implements OrderPlacementStrategy
{
    public function attributes(): array
    {
        return ['status' => 'awaiting_payment', 'placed_at' => null];
    }
}
