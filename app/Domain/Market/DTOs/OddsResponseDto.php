<?php

namespace App\Domain\Market\DTOs;

class OddsResponseDto
{
    /**
     * @param  int  $fixtureId  The external API fixture ID
     * @param  string  $updateAt  ISO8601 timestamp of last odds update
     * @param  MarketDataDto[]  $markets  Array of extracted markets for the specified bookmaker
     */
    public function __construct(
        public readonly int $fixtureId,
        public readonly string $homeTeam,
        public readonly string $awayTeam,
        public readonly string $updateAt,
        public readonly array $markets,
    ) {}
}
