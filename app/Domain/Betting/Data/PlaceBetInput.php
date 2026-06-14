<?php

namespace App\Domain\Betting\Data;

use App\Models\Market;
use App\Models\MarketOutcome;
use App\Models\User;
use App\Models\Wallet;

/**
 * DTO — dữ liệu đầu vào để đặt dự đoán.
 */
final class PlaceBetInput
{
    public function __construct(
        public readonly User $user,
        public readonly Wallet $wallet,
        public readonly Market $market,
        public readonly MarketOutcome $outcome,
        public readonly int $stake,
    ) {}
}
