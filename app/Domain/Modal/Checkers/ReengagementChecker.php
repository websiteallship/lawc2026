<?php

namespace App\Domain\Modal\Checkers;

use App\Domain\Modal\Contracts\ReengagementCheckerInterface;
use App\Models\User;
use App\Settings\AppSettings;
use App\Enums\MarketStatus;
use Illuminate\Support\Facades\DB;

class ReengagementChecker implements ReengagementCheckerInterface
{
    public function shouldShow(User $user): bool
    {
        $settings = app(AppSettings::class);
        $absentDaysConfig = $settings->reengagement_absent_days ?? 3;
        
        $lastActive = $user->last_active_at ?? $user->last_login_at;
        
        // If user has never been active, don't show re-engagement
        if (!$lastActive) {
            return false;
        }

        $daysAbsent = (int) $lastActive->diffInDays(now());
        if ($daysAbsent < $absentDaysConfig) {
            return false;
        }

        // Only show once every 7 days
        if ($user->last_reengagement_shown_at && $user->last_reengagement_shown_at->diffInDays(now()) < 7) {
            return false;
        }

        // Must have OPEN markets
        $hasOpenMarkets = DB::table('markets')->where('status', MarketStatus::OPEN->value)->exists();
        if (!$hasOpenMarkets) {
            return false;
        }

        return true;
    }

    public function getData(User $user): array
    {
        $lastActive = $user->last_active_at ?? $user->last_login_at;
        $daysAbsent = $lastActive ? (int) $lastActive->diffInDays(now()) : 0;
        
        $openMatchesCount = DB::table('matches')
            ->join('markets', 'matches.id', '=', 'markets.match_id')
            ->where('markets.status', MarketStatus::OPEN->value)
            ->distinct('matches.id')
            ->count('matches.id');

        return [
            'daysAbsent'       => $daysAbsent,
            'openMatchesCount' => $openMatchesCount,
        ];
    }

    public function markAsSeen(User $user): void
    {
        $user->updateQuietly(['last_reengagement_shown_at' => now()]);
    }
}
