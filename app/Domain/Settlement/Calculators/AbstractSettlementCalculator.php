<?php

namespace App\Domain\Settlement\Calculators;

use App\Domain\Settlement\Data\MatchResult;
use App\Domain\Settlement\Data\SettlementResult;
use App\Enums\BetStatus;
use App\Models\Bet;

/**
 * Calculator dùng chung — cung cấp payout math theo AGENTS.md.
 */
abstract class AbstractSettlementCalculator
{
    /**
     * Tính toán kết quả cho một bet.
     */
    abstract public function calculate(Bet $bet, MatchResult $result): SettlementResult;

    // ===================== PAYOUT HELPERS =====================

    /**
     * Full win: stake × (1 + profit_rate)
     */
    protected function fullWinPayout(int $stake, float $profitRate): int
    {
        return (int) round($stake * (1 + $profitRate), 0, PHP_ROUND_HALF_UP);
    }

    /**
     * Lose: 0
     */
    protected function losePayout(): int
    {
        return 0;
    }

    /**
     * Push: stake
     */
    protected function pushPayout(int $stake): int
    {
        return $stake;
    }

    /**
     * Half win: (stake/2) × (1 + profit_rate) + (stake/2)
     */
    protected function halfWinPayout(int $stake, float $profitRate): int
    {
        $half = $stake / 2;

        return (int) round($half * (1 + $profitRate) + $half, 0, PHP_ROUND_HALF_UP);
    }

    /**
     * Half lose: stake / 2
     */
    protected function halfLosePayout(int $stake): int
    {
        return (int) round($stake / 2, 0, PHP_ROUND_HALF_UP);
    }

    /**
     * Build SettlementResult từ status và payout.
     */
    protected function makeResult(BetStatus $status, int $stake, int $grossPayout, array $detail = []): SettlementResult
    {
        return new SettlementResult(
            status: $status,
            grossPayout: $grossPayout,
            netResult: $grossPayout - $stake,
            calculationDetail: $detail,
        );
    }
}
