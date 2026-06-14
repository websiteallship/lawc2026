<?php

namespace App\Domain\Market\DTOs;

class OutcomeDataDto
{
    public function __construct(
        public readonly string $value,
        public readonly float $odd,
    ) {}
}
