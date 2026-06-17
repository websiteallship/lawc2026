<?php

namespace App\Observers;

use App\Models\Bet;
use App\Domain\Betting\Services\UserStatisticsService;
use Illuminate\Support\Facades\Cache;

class BetObserver
{
    /**
     * Handle the Bet "saved" event.
     */
    public function saved(Bet $bet): void
    {
        if ($bet->user_id) {
            app(UserStatisticsService::class)->recalculateForUser($bet->user_id);
            Cache::forget("player_combined_stats_{$bet->user_id}");
        }
    }

    /**
     * Handle the Bet "deleted" event.
     */
    public function deleted(Bet $bet): void
    {
        if ($bet->user_id) {
            app(UserStatisticsService::class)->recalculateForUser($bet->user_id);
            Cache::forget("player_combined_stats_{$bet->user_id}");
        }
    }
}
