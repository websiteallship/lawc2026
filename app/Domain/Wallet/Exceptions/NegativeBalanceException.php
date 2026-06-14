<?php

namespace App\Domain\Wallet\Exceptions;

use RuntimeException;

class NegativeBalanceException extends RuntimeException
{
    public function __construct(string $field, int $value)
    {
        parent::__construct(
            "Số dư âm không hợp lệ. Field: {$field}, giá trị: {$value}."
        );
    }
}
