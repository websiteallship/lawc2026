<?php

namespace App\Enums;

enum BetStatus: string
{
    case PENDING = 'PENDING';
    case WON = 'WON';
    case LOST = 'LOST';
    case PUSH = 'PUSH';
    case HALF_WON = 'HALF_WON';
    case HALF_LOST = 'HALF_LOST';
    case VOIDED = 'VOIDED';
    case CORRECTED = 'CORRECTED';

    public function isSettled(): bool
    {
        return match ($this) {
            self::WON, self::LOST, self::PUSH, self::HALF_WON, self::HALF_LOST, self::VOIDED, self::CORRECTED => true,
            default => false,
        };
    }
}
