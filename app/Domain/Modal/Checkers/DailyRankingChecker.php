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
                ->whereHas('user')
                ->orderByDesc('net_profit')
                ->limit(50)
                ->get()
                ->map(fn($w) => [
                    'user_id'    => $w->user_id,
                    'name'       => $this->resolveAnonymousName($w->user_id),
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

    private function resolveAnonymousName(int $userId): string
    {
        $animals = [
            'Hươu cao cổ', 'Voi', 'Ngựa vằn', 'Bò sữa', 'Cừu', 'Dê',
            'Nai', 'Thỏ', 'Gấu trúc', 'Koala', 'Kangaroo', 'Lười',
            'Hà mã', 'Gorilla', 'Lạc đà', 'Ngựa', 'Tê giác', 'Trâu',
            'Sóc', 'Hải ly', 'Chuột lang', 'Rùa', 'Đười ươi', 'Cự đà',
            'Ốc sên', 'Cào cào', 'Bướm', 'Sâu',
        ];
        $adjectives = [
            'Vui vẻ', 'Nhanh nhẹn', 'Lười biếng', 'Ham ăn', 'Ngái ngủ',
            'Láu lỉnh', 'Nhút nhát', 'Dũng cảm', 'Thông minh', 'Ngốc nghếch',
            'Hào phóng', 'Thân thiện', 'Hay cáu', 'Bướng bỉnh', 'Chậm chạp',
            'Mạnh mẽ', 'Xinh xắn', 'Đáng yêu', 'Mũm mĩm', 'Trầm ngâm',
            'Hay quên', 'Thích đùa',
        ];

        $hash        = md5($userId . config('app.key'));
        $animalIndex = hexdec(substr($hash, 0, 4)) % count($animals);
        $adjIndex    = hexdec(substr($hash, 4, 4)) % count($adjectives);

        return $animals[$animalIndex] . ' ' . strtolower($adjectives[$adjIndex]);
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
