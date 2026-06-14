<?php

namespace App\Enums;

enum LedgerType: string
{
    case ADMIN_GRANT = 'ADMIN_GRANT';
    case ADMIN_DEDUCT = 'ADMIN_DEDUCT';
    case BET_PLACED = 'BET_PLACED';
    case BET_WON = 'BET_WON';
    case BET_LOST = 'BET_LOST';
    case BET_PUSH = 'BET_PUSH';
    case BET_HALF_WON = 'BET_HALF_WON';
    case BET_HALF_LOST = 'BET_HALF_LOST';
    case BET_VOIDED = 'BET_VOIDED';
    case SETTLEMENT_CORRECTION = 'SETTLEMENT_CORRECTION';
    case SEASON_RESET = 'SEASON_RESET';

    public function label(): string
    {
        return match ($this) {
            self::ADMIN_GRANT => 'Được thưởng Lá',
            self::ADMIN_DEDUCT => 'Bị trừ Lá',
            self::BET_PLACED => 'Đặt cược',
            self::BET_WON => 'Thắng cược',
            self::BET_LOST => 'Thua cược',
            self::BET_PUSH => 'Hòa cược (Hoàn Lá)',
            self::BET_HALF_WON => 'Thắng nửa',
            self::BET_HALF_LOST => 'Thua nửa',
            self::BET_VOIDED => 'Hủy cược',
            self::SETTLEMENT_CORRECTION => 'Điều chỉnh kết quả',
            self::SEASON_RESET => 'Reset mùa giải',
        };
    }
}
