<?php

namespace App\Domain\Leaderboard\Data;

/**
 * DTO một dòng bảng xếp hạng — kết quả tổng hợp per user/season.
 */
final class LeaderboardEntry
{
    public function __construct(
        public readonly int $userId,
        public readonly string $userName,
        public readonly int $availableBalance,
        public readonly int $lockedBalance,
        public readonly int $totalBalance,
        public readonly int $totalStaked,
        public readonly int $totalPayout,
        public readonly int $netProfit,
        public readonly int $totalBets,
        public readonly int $wonBets,
        public readonly int $lostBets,
        public readonly int $pushBets,
        public readonly int $exactScoreWins,
        public readonly float $roi,        // (net_profit / total_staked) × 100, 0 nếu staked=0
        public readonly float $winRate,    // won / total_settled × 100, 0 nếu chưa settle
        public readonly int $rank = 0,
    ) {}
}
