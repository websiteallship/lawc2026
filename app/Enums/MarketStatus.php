<?php

namespace App\Enums;

enum MarketStatus: string
{
    case DRAFT = 'DRAFT';
    case OPEN = 'OPEN';
    case LOCKED = 'LOCKED';
    case SETTLING = 'SETTLING';
    case SETTLED = 'SETTLED';
    case VOIDED = 'VOIDED';
    case CANCELLED = 'CANCELLED';
}
