<?php

namespace App\Domain\Settlement\Calculators;

use App\Domain\Settlement\Data\MatchResult;
use App\Domain\Settlement\Data\SettlementResult;
use App\Enums\BetStatus;
use App\Models\Bet;

/**
 * Calculator cho kèo Tỉ số chính xác (EXACT_SCORE).
 *
 * Logic: so khớp score_home_snapshot == home_score VÀ score_away_snapshot == away_score.
 * Outcome lưu score_home / score_away trực tiếp trên MarketOutcome.
 * Bet snapshot lưu qua label_snapshot (e.g. "2-1") hoặc dùng outcome relation.
 */
class ExactScoreSettlementCalculator extends AbstractSettlementCalculator
{
    public function calculate(Bet $bet, MatchResult $result): SettlementResult
    {
        $stake = $bet->stake;
        $profitRate = (float) $bet->profit_rate_snapshot;

        // Lấy tỉ số dự đoán từ outcome
        $outcome = $bet->outcome;

        if ($outcome->selection_side === 'OTHER' || $outcome->label === 'Tỉ số khác') {
            $isWin = ($result->homeScore > 4 || $result->awayScore > 4);
            $predictedHome = 'Khác';
            $predictedAway = 'Khác';
            $predictedText = 'Tỉ số khác';
        } else {
            $predictedHome = (int) $outcome->score_home;
            $predictedAway = (int) $outcome->score_away;
            $isWin = ($predictedHome === $result->homeScore && $predictedAway === $result->awayScore);
            $predictedText = "{$predictedHome}-{$predictedAway}";
        }

        if ($isWin) {
            $payout = $this->fullWinPayout($stake, $profitRate);

            return $this->makeResult(BetStatus::WON, $stake, $payout, [
                'type' => 'EXACT_SCORE',
                'predicted' => $predictedText,
                'actual' => "{$result->homeScore}-{$result->awayScore}",
                'profit_rate' => $profitRate,
                'formula' => "stake({$stake}) × (1 + {$profitRate}) = {$payout}",
            ]);
        }

        return $this->makeResult(BetStatus::LOST, $stake, 0, [
            'type' => 'EXACT_SCORE',
            'predicted' => $predictedText,
            'actual' => "{$result->homeScore}-{$result->awayScore}",
        ]);
    }
}
