<?php

namespace App\Domain\Market\DTOs;

class MarketDataDto
{
    /**
     * @param  int  $id  The RapidAPI Bet ID (e.g., 1 for Match Winner, 5 for Goals Over/Under)
     * @param  string  $name  The RapidAPI Bet Name
     * @param  OutcomeDataDto[]  $outcomes  The associated values and odds
     */
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly array $outcomes,
    ) {}
}
