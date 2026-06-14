<?php

namespace App\Domain\Settlement\Data;

/**
 * DTO kết quả thi đấu — đầu vào cho calculators.
 */
final class MatchResult
{
    public function __construct(
        public readonly int $homeScore,
        public readonly int $awayScore,
    ) {}

    public function totalGoals(): int
    {
        return $this->homeScore + $this->awayScore;
    }

    public function goalDifference(): int
    {
        return $this->homeScore - $this->awayScore;
    }
}
