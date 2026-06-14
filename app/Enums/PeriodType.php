<?php

namespace App\Enums;

enum PeriodType: string
{
    case FULL_TIME = 'FULL_TIME';
    case FIRST_HALF = 'FIRST_HALF';
    case SECOND_HALF = 'SECOND_HALF';
    case EXTRA_TIME = 'EXTRA_TIME';
    case PENALTY = 'PENALTY';
}
