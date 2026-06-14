<?php

namespace App\Domain\Match\Data;

class ApiScoreDto
{
    public function __construct(
        public readonly ?int $home,
        public readonly ?int $away,
    ) {}

    public static function fromArray(?array $data): ?self
    {
        if (! $data) {
            return null;
        }

        // If both are null, the period hasn't happened or no score yet
        if ($data['home'] === null && $data['away'] === null) {
            return null;
        }

        return new self(
            home: $data['home'] ?? 0,
            away: $data['away'] ?? 0,
        );
    }
}
