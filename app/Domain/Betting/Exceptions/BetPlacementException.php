<?php

namespace App\Domain\Betting\Exceptions;

use RuntimeException;

class BetPlacementException extends RuntimeException
{
    public function __construct(
        string $message,
        private readonly string $errorCode,
    ) {
        parent::__construct($message);
    }

    public function getErrorCode(): string
    {
        return $this->errorCode;
    }
}
