<?php

namespace App\Exceptions;

use RuntimeException;

final class OperationRejected extends RuntimeException
{
    public function __construct(public readonly string $reason, public readonly array $details)
    {
        parent::__construct($details['message'] ?? 'Operation rejected');
    }
}
