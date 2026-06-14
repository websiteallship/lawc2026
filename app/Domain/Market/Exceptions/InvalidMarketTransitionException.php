<?php

namespace App\Domain\Market\Exceptions;

use RuntimeException;

class InvalidMarketTransitionException extends RuntimeException
{
    public function __construct(string $from, string $to)
    {
        parent::__construct("Không thể chuyển trạng thái market từ [{$from}] sang [{$to}].");
    }
}
