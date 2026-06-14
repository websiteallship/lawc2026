<?php

namespace App\Domain\Settlement\Calculators;

use App\Domain\Settlement\Data\MatchResult;
use App\Domain\Settlement\Data\SettlementResult;
use App\Enums\BetStatus;
use App\Models\Bet;

/**
 * Calculator cho kèo Chấp Châu Á (ASIAN_HANDICAP).
 *
 * === Quy tắc (từ AGENTS.md) ===
 *
 * - selection_side: HOME = đội nhà chấp, AWAY = đội khách nhận chấp
 * - line_value âm: đội nhà chấp (ví dụ -0.5 = nhà chấp 0.5)
 * - Adjusted score = (goal_diff) + line_value (nếu đặt HOME)
 *                  = -(goal_diff) - line_value (nếu đặt AWAY)
 *
 * === Quarter-line split (AGENTS.md) ===
 * ±0.25  → split thành [0, ±0.5]
 * ±0.75  → split thành [±0.5, ±1]
 *
 * === Full-line kết quả ===
 * adjusted > 0 → WIN
 * adjusted = 0 → PUSH
 * adjusted < 0 → LOSE
 */
class AsianHandicapSettlementCalculator extends AbstractSettlementCalculator
{
    public function calculate(Bet $bet, MatchResult $result): SettlementResult
    {
        $stake = $bet->stake;
        $profitRate = (float) $bet->profit_rate_snapshot;
        $lineValue = (float) ($bet->line_snapshot ?? $bet->outcome->line_value ?? 0);
        $side = $bet->selection_side_snapshot ?? $bet->outcome->selection_side ?? 'HOME';

        // Goal diff từ góc nhìn HOME
        $goalDiff = $result->goalDifference(); // home - away

        // Nếu đặt AWAY, đảo chiều
        if ($side === 'AWAY') {
            $goalDiff = -$goalDiff;
            $lineValue = -$lineValue;
        }

        // Kiểm tra quarter line bằng thuật toán: (line * 4) là số lẻ => quarter line
        $isQuarter = (abs((int) round($lineValue * 4)) % 2) !== 0;

        if ($isQuarter) {
            return $this->calculateQuarter($bet, $stake, $profitRate, $lineValue, $goalDiff, $side);
        }

        return $this->calculateFullLine($stake, $profitRate, $lineValue, $goalDiff, $side);
    }

    /**
     * Full line: 0, ±0.5, ±1, ±1.5, ±2 ...
     */
    private function calculateFullLine(
        int $stake,
        float $profitRate,
        float $lineValue,
        int $goalDiff,
        string $side
    ): SettlementResult {
        $adjusted = $goalDiff + $lineValue; // Kết quả đã điều chỉnh

        if ($adjusted > 0) {
            $payout = $this->fullWinPayout($stake, $profitRate);

            return $this->makeResult(BetStatus::WON, $stake, $payout, $this->detail('FULL_WIN', $side, $lineValue, $goalDiff, $adjusted, $profitRate));
        }

        if ($adjusted === 0.0 || $adjusted == 0) {
            $payout = $this->pushPayout($stake);

            return $this->makeResult(BetStatus::PUSH, $stake, $payout, $this->detail('PUSH', $side, $lineValue, $goalDiff, $adjusted, $profitRate));
        }

        return $this->makeResult(BetStatus::LOST, $stake, 0, $this->detail('FULL_LOSE', $side, $lineValue, $goalDiff, $adjusted, $profitRate));
    }

    /**
     * Quarter line: split stake làm đôi, tính mỗi nửa trên line riêng.
     */
    private function calculateQuarter(
        Bet $bet,
        int $stake,
        float $profitRate,
        float $lineValue,
        int $goalDiff,
        string $side
    ): SettlementResult {
        $lineA = $lineValue - 0.25;
        $lineB = $lineValue + 0.25;

        $halfStake = (int) round($stake / 2, 0, PHP_ROUND_HALF_UP);
        $remainStake = $stake - $halfStake; // Xử lý stake lẻ

        $resultA = $this->singleLineResult($halfStake, $profitRate, $lineA, $goalDiff);
        $resultB = $this->singleLineResult($remainStake, $profitRate, $lineB, $goalDiff);

        $totalPayout = $resultA['payout'] + $resultB['payout'];

        // Xác định BetStatus tổng
        $status = $this->mergeQuarterStatus($resultA['status'], $resultB['status']);

        return $this->makeResult($status, $stake, $totalPayout, [
            'type' => 'QUARTER_LINE',
            'line' => $lineValue,
            'side' => $side,
            'goal_diff' => $goalDiff,
            'line_a' => $lineA,
            'line_b' => $lineB,
            'result_a' => $resultA,
            'result_b' => $resultB,
        ]);
    }

    private function singleLineResult(int $stake, float $profitRate, float $line, int $goalDiff): array
    {
        $adjusted = $goalDiff + $line;

        if ($adjusted > 0) {
            return ['status' => 'WIN', 'payout' => $this->fullWinPayout($stake, $profitRate)];
        }
        if ($adjusted == 0) {
            return ['status' => 'PUSH', 'payout' => $this->pushPayout($stake)];
        }

        return ['status' => 'LOSE', 'payout' => 0];
    }

    private function mergeQuarterStatus(string $a, string $b): BetStatus
    {
        if ($a === 'WIN' && $b === 'WIN') {
            return BetStatus::WON;
        }
        if ($a === 'LOSE' && $b === 'LOSE') {
            return BetStatus::LOST;
        }
        if ($a === 'WIN' && $b === 'PUSH') {
            return BetStatus::HALF_WON;
        }
        if ($a === 'PUSH' && $b === 'WIN') {
            return BetStatus::HALF_WON;
        }
        if ($a === 'LOSE' && $b === 'PUSH') {
            return BetStatus::HALF_LOST;
        }
        if ($a === 'PUSH' && $b === 'LOSE') {
            return BetStatus::HALF_LOST;
        }
        if ($a === 'WIN' && $b === 'LOSE') {
            return BetStatus::PUSH;
        }
        if ($a === 'LOSE' && $b === 'WIN') {
            return BetStatus::PUSH;
        }

        return BetStatus::PUSH;
    }

    private function detail(string $type, string $side, float $line, int $goalDiff, float $adjusted, float $profitRate): array
    {
        return compact('type', 'side', 'line', 'goalDiff', 'adjusted', 'profitRate');
    }
}
