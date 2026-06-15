<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;
use Filament\Support\Contracts\HasColor;

enum LedgerType: string implements HasLabel, HasColor
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

    public function getLabel(): ?string
    {
        return $this->label();
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::ADMIN_GRANT => 'success',
            self::ADMIN_DEDUCT => 'danger',
            self::BET_PLACED => 'warning',
            self::BET_WON => 'success',
            self::BET_LOST => 'danger',
            self::BET_PUSH => 'info',
            self::BET_HALF_WON => 'success',
            self::BET_HALF_LOST => 'danger',
            self::BET_VOIDED => 'gray',
            self::SETTLEMENT_CORRECTION => 'warning',
            self::SEASON_RESET => 'danger',
        };
    }
}
