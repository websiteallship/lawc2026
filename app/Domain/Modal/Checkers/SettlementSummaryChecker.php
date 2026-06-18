<?php

namespace App\Domain\Modal\Checkers;

use App\Domain\Modal\Contracts\SettlementSummaryCheckerInterface;
use App\Enums\BetStatus;
use App\Models\Bet;
use App\Models\User;

class SettlementSummaryChecker implements SettlementSummaryCheckerInterface
{
    /** Statuses có ý nghĩa hiển thị với user (VOIDED không cần show) */
    private const SETTLED_STATUSES = [
        BetStatus::WON,
        BetStatus::LOST,
        BetStatus::PUSH,
        BetStatus::HALF_WON,
        BetStatus::HALF_LOST,
        BetStatus::CORRECTED,
    ];

    public function shouldShow(User $user): bool
    {
        $since = $user->last_settlement_summary_shown_at;

        return Bet::where('user_id', $user->id)
            ->whereNotNull('settled_at')
            ->whereIn('status', $this->statusValues())
            ->when($since, fn($q) => $q->where('settled_at', '>', $since))
            ->exists();
    }

    public function getData(User $user): array
    {
        $since = $user->last_settlement_summary_shown_at;

        $bets = Bet::where('user_id', $user->id)
            ->whereNotNull('settled_at')
            ->whereIn('status', $this->statusValues())
            ->when($since, fn($q) => $q->where('settled_at', '>', $since))
            ->with('match')
            ->orderBy('settled_at', 'desc')
            ->limit(10)
            ->get();

        $totalNetResult = $bets->sum('net_result');

        $byMatch = $bets->groupBy('match_id')->map(function ($matchBets) {
            $match = $matchBets->first()->match;
            return [
                'match_label' => $match
                    ? ($match->home_team . ' vs ' . $match->away_team)
                    : 'Trận đấu',
                'bets'        => $matchBets->values(),
                'subtotal'    => $matchBets->sum('net_result'),
            ];
        })->values()->toArray();

        return [
            'bets'           => $bets,
            'byMatch'        => $byMatch,
            'totalNetResult' => $totalNetResult,
            'hasBets'        => $bets->isNotEmpty(),
        ];
    }

    public function markAsSeen(User $user): void
    {
        $user->updateQuietly(['last_settlement_summary_shown_at' => now()]);
    }

    /** @return array<string> */
    private function statusValues(): array
    {
        return array_map(fn(BetStatus $s) => $s->value, self::SETTLED_STATUSES);
    }
}
