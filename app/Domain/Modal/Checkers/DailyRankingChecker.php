<?php

namespace App\Domain\Modal\Checkers;

use App\Domain\Modal\Contracts\DailyRankingCheckerInterface;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class DailyRankingChecker implements DailyRankingCheckerInterface
{
    public function shouldShow(User $user): bool
    {
        $last = $user->last_daily_ranking_shown_at;
        return $last === null
            || !Carbon::parse($last)->timezone('Asia/Ho_Chi_Minh')->isToday();
    }

    public function getData(User $user): array
    {
        $rankings = Cache::remember('leaderboard_season_top50', 900, function () {
            return \App\Models\Wallet::select(['user_id', 'net_profit'])
                ->with('user:id,name')
                ->whereHas('user')
                ->orderByDesc('net_profit')
                ->limit(50)
                ->get()
                ->map(fn($w) => [
                    'user_id'    => $w->user_id,
                    'name'       => $w->user->name ?? 'Người chơi',
                    'net_profit' => $w->net_profit,
                ])
                ->toArray();
        });
        
        $rankIndex = collect($rankings)->search(fn($r) => isset($r['user_id']) && $r['user_id'] === $user->id);
        $userRank = $rankIndex !== false ? $rankIndex + 1 : null;
        $top3 = array_slice($rankings, 0, 3);
        $gapToAbove = $this->calcGap($rankings, $rankIndex);
        $messageKey = $this->resolveMessage($userRank, count($rankings));

        return compact('userRank', 'top3', 'gapToAbove', 'messageKey', 'rankings');
    }

    public function markAsSeen(User $user): void
    {
        $user->updateQuietly(['last_daily_ranking_shown_at' => now()]);
    }

    private function calcGap(array $rankings, $rankIndex): ?int
    {
        if ($rankIndex === false || $rankIndex === 0) return null;
        $currentProfit = $rankings[$rankIndex]['net_profit'] ?? 0;
        $aboveProfit = $rankings[$rankIndex - 1]['net_profit'] ?? 0;
        return max(0, $aboveProfit - $currentProfit);
    }

    private function resolveMessage(?int $rank, int $total): string
    {
        if ($rank === null) return 'new';
        return match(true) {
            $rank === 1 => 'top1',
            $rank === 2 => 'top2',
            $rank === 3 => 'top3',
            $rank <= 10 => 'chasing',
            $rank <= 20 => 'climbing',
            default     => 'learning',
        };
    }
}
