<?php

namespace App\Domain\Settlement\Calculators;

use App\Domain\Settlement\Data\MatchResult;
use App\Domain\Settlement\Data\SettlementResult;
use App\Enums\BetStatus;
use App\Models\Bet;

/**
 * Calculator cho kèo Tài/Xỉu (OVER_UNDER).
 *
 * === Quy tắc (từ AGENTS.md) ===
 *
 * selection_side: OVER = Tài, UNDER = Xỉu
 *
 * Full lines (2.0, 2.5, 3.0...):
 *   goals > line → Tài WIN  / Xỉu LOSE
 *   goals < line → Tài LOSE / Xỉu WIN
 *   goals = line → PUSH (chỉ xảy ra với line tròn 2.0, 3.0...)
 *
 * Quarter lines (2.25, 2.75...):
 *   2.25 = split [2.0, 2.5]
 *   2.75 = split [2.5, 3.0]
 *
 * Ví dụ từ AGENTS.md:
 *   Tài 2.25, goals=2:
 *     Over 2.0 = PUSH → 50 lá
 *     Over 2.5 = LOSE → 0
 *     Gross = 50
 *
 *   Xỉu 2.25, goals=2:
 *     Under 2.0 = PUSH → 50
 *     Under 2.5 = WIN → 50 × 1.90 ≈ 95
 *     Gross = 50 + 95 = 145
 */
class OverUnderSettlementCalculator extends AbstractSettlementCalculator
{
    public function calculate(Bet $bet, MatchResult $result): SettlementResult
    {
        $stake = $bet->stake;
        $profitRate = (float) $bet->profit_rate_snapshot;
        $lineValue = (float) ($bet->line_snapshot ?? $bet->outcome->line_value ?? 0);
        $side = $bet->selection_side_snapshot ?? $bet->outcome->selection_side ?? 'OVER';
        $totalGoals = $result->totalGoals();

        // Kiểm tra quarter line bằng thuật toán: (line * 4) là số lẻ => quarter line
        $isQuarter = (abs((int) round($lineValue * 4)) % 2) !== 0;

        if ($isQuarter) {
            return $this->calculateQuarter($stake, $profitRate, $lineValue, $side, $totalGoals);
        }

        return $this->calculateFullLine($stake, $profitRate, $lineValue, $side, $totalGoals);
    }

    private function calculateFullLine(
        int $stake,
        float $profitRate,
        float $line,
        string $side,
        int $totalGoals
    ): SettlementResult {
        if ($totalGoals > $line) {
            // Tài thắng, Xỉu thua
            if ($side === 'OVER') {
                $payout = $this->fullWinPayout($stake, $profitRate);

                return $this->makeResult(BetStatus::WON, $stake, $payout, $this->detail('FULL_WIN', $side, $line, $totalGoals, $profitRate));
            }

            return $this->makeResult(BetStatus::LOST, $stake, 0, $this->detail('FULL_LOSE', $side, $line, $totalGoals, $profitRate));
        }

        if ($totalGoals < $line) {
            // Tài thua, Xỉu thắng
            if ($side === 'UNDER') {
                $payout = $this->fullWinPayout($stake, $profitRate);

                return $this->makeResult(BetStatus::WON, $stake, $payout, $this->detail('FULL_WIN', $side, $line, $totalGoals, $profitRate));
            }

            return $this->makeResult(BetStatus::LOST, $stake, 0, $this->detail('FULL_LOSE', $side, $line, $totalGoals, $profitRate));
        }

        // goals === line → PUSH
        $payout = $this->pushPayout($stake);

        return $this->makeResult(BetStatus::PUSH, $stake, $payout, $this->detail('PUSH', $side, $line, $totalGoals, $profitRate));
    }

    private function calculateQuarter(
        int $stake,
        float $profitRate,
        float $lineValue,
        string $side,
        int $totalGoals
    ): SettlementResult {
        $lineA = $lineValue - 0.25;
        $lineB = $lineValue + 0.25;

        $halfStake = (int) round($stake / 2, 0, PHP_ROUND_HALF_UP);
        $remainStake = $stake - $halfStake;

        $resultA = $this->singleLineResult($halfStake, $profitRate, $lineA, $side, $totalGoals);
        $resultB = $this->singleLineResult($remainStake, $profitRate, $lineB, $side, $totalGoals);

        $totalPayout = $resultA['payout'] + $resultB['payout'];
        $status = $this->mergeQuarterStatus($resultA['status'], $resultB['status']);

        return $this->makeResult($status, $stake, $totalPayout, [
            'type' => 'QUARTER_LINE',
            'line' => $lineValue,
            'side' => $side,
            'total_goals' => $totalGoals,
            'line_a' => $lineA,
            'line_b' => $lineB,
            'result_a' => $resultA,
            'result_b' => $resultB,
        ]);
    }

    private function singleLineResult(int $stake, float $profitRate, float $line, string $side, int $totalGoals): array
    {
        if ($totalGoals > $line) {
            $status = ($side === 'OVER') ? 'WIN' : 'LOSE';
            $payout = ($side === 'OVER') ? $this->fullWinPayout($stake, $profitRate) : 0;
        } elseif ($totalGoals < $line) {
            $status = ($side === 'UNDER') ? 'WIN' : 'LOSE';
            $payout = ($side === 'UNDER') ? $this->fullWinPayout($stake, $profitRate) : 0;
        } else {
            $status = 'PUSH';
            $payout = $this->pushPayout($stake);
        }

        return ['status' => $status, 'payout' => $payout, 'line' => $line, 'stake' => $stake];
    }

    private function mergeQuarterStatus(string $a, string $b): BetStatus
    {
        if ($a === 'WIN' && $b === 'WIN') {
            return BetStatus::WON;
        }
        if ($a === 'LOSE' && $b === 'LOSE') {
            return BetStatus::LOST;
        }
        if (($a === 'WIN' && $b === 'PUSH') || ($a === 'PUSH' && $b === 'WIN')) {
            return BetStatus::HALF_WON;
        }
        if (($a === 'LOSE' && $b === 'PUSH') || ($a === 'PUSH' && $b === 'LOSE')) {
            return BetStatus::HALF_LOST;
        }

        return BetStatus::PUSH;
    }

    private function detail(string $type, string $side, float $line, int $totalGoals, float $profitRate): array
    {
        return compact('type', 'side', 'line', 'totalGoals', 'profitRate');
    }
}
