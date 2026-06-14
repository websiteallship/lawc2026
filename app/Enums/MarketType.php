<?php

namespace App\Enums;

enum MarketType: string
{
    case EXACT_SCORE = 'EXACT_SCORE';
    case ASIAN_HANDICAP = 'ASIAN_HANDICAP';
    case OVER_UNDER = 'OVER_UNDER';
    case PENALTY_WINNER = 'PENALTY_WINNER';
}
