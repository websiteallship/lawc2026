<?php

namespace App\Domain\Betting\Services;

use App\Models\Bet;
use App\Models\UserStatistic;
use Illuminate\Support\Facades\DB;

class UserStatisticsService
{
    /**
     * Recalculate statistics for a specific user.
     *
     * @param int $userId
     * @return UserStatistic
     */
    public function recalculateForUser(int $userId): UserStatistic
    {
        return DB::transaction(function () use ($userId) {
            $stats = UserStatistic::firstOrCreate(['user_id' => $userId]);

            $totalBets = Bet::where('user_id', $userId)->count();

            // Settled bets (Only select necessary columns for memory efficiency)
            $settledBets = Bet::where('user_id', $userId)
                ->whereIn('status', ['WON', 'HALF_WON', 'LOST', 'HALF_LOST', 'PUSH', 'VOIDED', 'CORRECTED'])
                ->orderBy('settled_at')
                ->select(['id', 'status', 'stake', 'gross_payout', 'market_type_snapshot', 'net_result'])
                ->get();

            $settledCount = $settledBets->count();
            
            $wonBets = 0;
            $lostBets = 0;
            $pushBets = 0;
            $voidedBets = 0;
            $totalStaked = 0;
            $totalPayout = 0;
            $exactScoreWins = 0;

            $currentStreak = 0;
            $longestStreak = 0;

            foreach ($settledBets as $bet) {
                $statusVal = $bet->status?->value ?? $bet->status;

                if ($statusVal === 'VOIDED') {
                    $voidedBets++;
                    continue; 
                }

                $totalStaked += $bet->stake;
                $totalPayout += $bet->gross_payout ?? 0;

                // Determine effective status for CORRECTED bets
                $effectiveStatus = $statusVal;
                if ($statusVal === 'CORRECTED') {
                    if ($bet->net_result > 0) $effectiveStatus = 'WON';
                    elseif ($bet->net_result < 0) $effectiveStatus = 'LOST';
                    else $effectiveStatus = 'PUSH';
                }

                if (in_array($effectiveStatus, ['WON', 'HALF_WON'])) {
                    $wonBets++;
                    $currentStreak++;
                    if ($currentStreak > $longestStreak) {
                        $longestStreak = $currentStreak;
                    }
                    if ($bet->market_type_snapshot === 'EXACT_SCORE' && in_array($effectiveStatus, ['WON', 'HALF_WON'])) {
                        $exactScoreWins++;
                    }
                } elseif (in_array($effectiveStatus, ['LOST', 'HALF_LOST'])) {
                    $lostBets++;
                    $currentStreak = 0; // Reset streak
                } elseif ($effectiveStatus === 'PUSH') {
                    $pushBets++;
                    // Push does not break streak
                }
            }

            $netProfit = $totalPayout - $totalStaked;
            
            $roi = 0;
            if ($totalStaked > 0) {
                $roi = ($netProfit / $totalStaked) * 100;
            }

            $winRate = 0;
            $validBetsForWinRate = $wonBets + $lostBets;
            if ($validBetsForWinRate > 0) {
                $winRate = ($wonBets / $validBetsForWinRate) * 100;
            }

            $stats->update([
                'total_bets' => $totalBets,
                'settled_bets' => $settledCount,
                'won_bets' => $wonBets,
                'lost_bets' => $lostBets,
                'push_bets' => $pushBets,
                'voided_bets' => $voidedBets,
                'total_staked' => $totalStaked,
                'total_payout' => $totalPayout,
                'net_profit' => $netProfit,
                'roi' => $roi,
                'win_rate' => $winRate,
                'exact_score_wins' => $exactScoreWins,
                'current_win_streak' => $currentStreak,
                'longest_win_streak' => $longestStreak,
            ]);

            return $stats;
        });
    }
}
