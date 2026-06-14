<?php

namespace App\Domain\Wallet\Exceptions;

use RuntimeException;

class InsufficientBalanceException extends RuntimeException
{
    public function __construct(int $available, int $required)
    {
        parent::__construct(
            "Số lá không đủ. Cần: {$required}, hiện có: {$available}."
        );
    }
}
