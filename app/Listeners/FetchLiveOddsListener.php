<?php

namespace App\Listeners;

use App\Domain\Market\Services\MarketSyncService;
use App\Domain\Market\Services\OddsIntegrationService;
use App\Events\MatchStatusChanged;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class FetchLiveOddsListener implements ShouldQueue
{
    public function __construct(
        private readonly OddsIntegrationService $oddsService,
        private readonly MarketSyncService $syncService
    ) {}

    public function handle(MatchStatusChanged $event): void
    {
        $match = $event->match;
        $status = $match->status;

        $targetStatuses = ['HALFTIME', 'REGULAR_TIME_FINISHED', 'EXTRA_TIME_FINISHED'];

        if (! in_array($status, $targetStatuses)) {
            return;
        }

        $milestone = strtolower($status);
        $fetchStatus = $match->odds_fetch_status ?? [];

        if (in_array($milestone, $fetchStatus)) {
            return;
        }

        try {
            $date = $match->kickoff_at->toDateString();
            $oddsList = $this->oddsService->fetchDailyOdds($date);

            foreach ($oddsList as $dto) {
                if (str_contains($match->home_team, $dto->homeTeam) && str_contains($match->away_team, $dto->awayTeam)) {
                    $this->syncService->syncOddsForMatch($match, $dto);

                    $fetchStatus[] = $milestone;
                    $match->odds_fetch_status = array_unique($fetchStatus);
                    $match->save();
                    break;
                }
            }
        } catch (\Exception $e) {
            Log::error("Failed to fetch live odds for match {$match->id} at $status: ".$e->getMessage());
        }
    }
}
