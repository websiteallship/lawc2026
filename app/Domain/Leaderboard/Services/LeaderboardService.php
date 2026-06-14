<?php

namespace App\Domain\Leaderboard\Services;

use App\Domain\Leaderboard\Data\LeaderboardEntry;
use App\Enums\BetStatus;
use App\Models\Bet;
use App\Models\LeaderboardSnapshot;
use App\Models\Season;
use App\Models\Wallet;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * LeaderboardService — tính và snapshot bảng xếp hạng mùa giải.
 *
 * Sort order (từ implementation_plan.md v0.10.0):
 *   1. net_profit DESC
 *   2. ROI DESC
 *   3. exact_score_wins DESC
 *   4. win_rate DESC
 *
 * Xếp theo net_profit (không theo balance) — tránh méo khi admin cấp thêm lá.
 */
class LeaderboardService
{
    /**
     * Tính bảng xếp hạng cho một mùa giải — không ghi DB.
     *
     * @return Collection<int, LeaderboardEntry>
     */
    public function computeSeason(Season $season): Collection
    {
        // Lấy tất cả wallet + user cho season
        $wallets = Wallet::with('user')
            ->where('season_id', $season->id)
            ->where('status', 'ACTIVE')
            ->get()
            ->keyBy('user_id');

        if ($wallets->isEmpty()) {
            return collect();
        }

        $userIds = $wallets->keys()->toArray();

        // Tổng hợp stats từ bets đã settled (không tính PENDING)
        $settledStatuses = [
            BetStatus::WON->value,
            BetStatus::LOST->value,
            BetStatus::PUSH->value,
            BetStatus::HALF_WON->value,
            BetStatus::HALF_LOST->value,
        ];

        $betStats = Bet::selectRaw("
            user_id,
            COUNT(*) AS total_bets,
            SUM(CASE WHEN status IN ('".implode("','", $settledStatuses)."') THEN 1 ELSE 0 END) AS settled_bets,
            SUM(CASE WHEN status = 'WON' THEN 1 ELSE 0 END) AS won_bets,
            SUM(CASE WHEN status = 'LOST' THEN 1 ELSE 0 END) AS lost_bets,
            SUM(CASE WHEN status IN ('PUSH','HALF_WON','HALF_LOST') THEN 1 ELSE 0 END) AS push_bets,
            SUM(CASE WHEN status = 'WON' AND market_type_snapshot = 'EXACT_SCORE' THEN 1 ELSE 0 END) AS exact_score_wins
        ")
            ->where('season_id', $season->id)
            ->whereIn('user_id', $userIds)
            ->groupBy('user_id')
            ->get()
            ->keyBy('user_id');

        $entries = [];

        foreach ($wallets as $userId => $wallet) {
            $user = $wallet->user;
            if (! $user) {
                continue;
            }

            $stats = $betStats->get($userId);

            $totalStaked = $wallet->total_staked;
            $totalPayout = $wallet->total_payout;
            $netProfit = $wallet->net_profit;

            $totalBets = (int) ($stats?->total_bets ?? 0);
            $settledBets = (int) ($stats?->settled_bets ?? 0);
            $wonBets = (int) ($stats?->won_bets ?? 0);
            $lostBets = (int) ($stats?->lost_bets ?? 0);
            $pushBets = (int) ($stats?->push_bets ?? 0);
            $exactScoreWins = (int) ($stats?->exact_score_wins ?? 0);

            $roi = $totalStaked > 0
                ? round(($netProfit / $totalStaked) * 100, 4)
                : 0.0;

            $winRate = $settledBets > 0
                ? round(($wonBets / $settledBets) * 100, 4)
                : 0.0;

            $entries[] = new LeaderboardEntry(
                userId: $userId,
                userName: $user->name,
                availableBalance: $wallet->available_balance,
                lockedBalance: $wallet->locked_balance,
                totalBalance: $wallet->available_balance + $wallet->locked_balance,
                totalStaked: $totalStaked,
                totalPayout: $totalPayout,
                netProfit: $netProfit,
                totalBets: $totalBets,
                wonBets: $wonBets,
                lostBets: $lostBets,
                pushBets: $pushBets,
                exactScoreWins: $exactScoreWins,
                roi: $roi,
                winRate: $winRate,
            );
        }

        // Sort: net_profit → ROI → exact_score_wins → win_rate (tất cả DESC)
        usort($entries, function (LeaderboardEntry $a, LeaderboardEntry $b) {
            if ($a->netProfit !== $b->netProfit) {
                return $b->netProfit <=> $a->netProfit;
            }
            if ($a->roi !== $b->roi) {
                return $b->roi <=> $a->roi;
            }
            if ($a->exactScoreWins !== $b->exactScoreWins) {
                return $b->exactScoreWins <=> $a->exactScoreWins;
            }

            return $b->winRate <=> $a->winRate;
        });

        // Gán rank
        $ranked = [];
        foreach (array_values($entries) as $i => $entry) {
            $ranked[] = new LeaderboardEntry(
                userId: $entry->userId,
                userName: $entry->userName,
                availableBalance: $entry->availableBalance,
                lockedBalance: $entry->lockedBalance,
                totalBalance: $entry->totalBalance,
                totalStaked: $entry->totalStaked,
                totalPayout: $entry->totalPayout,
                netProfit: $entry->netProfit,
                totalBets: $entry->totalBets,
                wonBets: $entry->wonBets,
                lostBets: $entry->lostBets,
                pushBets: $entry->pushBets,
                exactScoreWins: $entry->exactScoreWins,
                roi: $entry->roi,
                winRate: $entry->winRate,
                rank: $i + 1,
            );
        }

        return collect($ranked);
    }

    /**
     * Tính và lưu snapshot bảng xếp hạng vào DB.
     * Dùng DB transaction để đảm bảo toàn vẹn.
     */
    public function snapshot(Season $season): Collection
    {
        $entries = $this->computeSeason($season);

        DB::transaction(function () use ($season, $entries) {
            foreach ($entries as $entry) {
                LeaderboardSnapshot::create([
                    'season_id' => $season->id,
                    'user_id' => $entry->userId,
                    'rank' => $entry->rank,
                    'available_balance' => $entry->availableBalance,
                    'locked_balance' => $entry->lockedBalance,
                    'total_balance' => $entry->totalBalance,
                    'total_staked' => $entry->totalStaked,
                    'total_payout' => $entry->totalPayout,
                    'net_profit' => $entry->netProfit,
                    'total_bets' => $entry->totalBets,
                    'won_bets' => $entry->wonBets,
                    'lost_bets' => $entry->lostBets,
                    'push_bets' => $entry->pushBets,
                    'exact_score_wins' => $entry->exactScoreWins,
                    'roi' => $entry->roi,
                    'win_rate' => $entry->winRate,
                    'snapshot_at' => now(),
                ]);
            }
        });

        return $entries;
    }

    /**
     * Lấy snapshot mới nhất của một season.
     *
     * @return Collection<int, LeaderboardSnapshot>
     */
    public function getLatestSnapshot(Season $season): Collection
    {
        $latestAt = LeaderboardSnapshot::where('season_id', $season->id)
            ->max('snapshot_at');

        if (! $latestAt) {
            return collect();
        }

        return LeaderboardSnapshot::with('user')
            ->where('season_id', $season->id)
            ->where('snapshot_at', $latestAt)
            ->orderBy('rank')
            ->get();
    }
}
