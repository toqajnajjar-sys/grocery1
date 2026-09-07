<?php

namespace App\Contracts;

interface OrderPlacementStrategy
{
    /** @return array{status: string, placed_at: mixed} */
    public function attributes(): array;
}
