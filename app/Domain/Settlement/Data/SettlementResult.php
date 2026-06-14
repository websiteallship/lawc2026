<?php

namespace App\Domain\Settlement\Data;

use App\Enums\BetStatus;

/**
 * DTO kết quả tính toán cho một bet.
 */
final class SettlementResult
{
    public function __construct(
        public readonly BetStatus $status,      // WON / LOST / PUSH / HALF_WON / HALF_LOST
        public readonly int $grossPayout,        // Số lá nhận về (đã round)
        public readonly int $netResult,          // grossPayout - stake
        public readonly array $calculationDetail = [], // Debug / audit info
    ) {}
}
