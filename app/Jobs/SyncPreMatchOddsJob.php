<?php

namespace App\Jobs;

use App\Domain\Market\Services\MarketSyncService;
use App\Domain\Market\Services\OddsIntegrationService;
use App\Models\FootballMatch;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncPreMatchOddsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(OddsIntegrationService $oddsService, MarketSyncService $syncService): void
    {
        // Tìm các trận sắp diễn ra trong 24 tiếng tới
        $matches = FootballMatch::where('status', 'SCHEDULED')
            ->whereBetween('kickoff_at', [now(), now()->addHours(24)])
            ->get();

        if ($matches->isEmpty()) {
            return;
        }

        $dates = $matches->map(function ($match) {
            return $match->kickoff_at->timezone('UTC')->toDateString();
        })->unique();

        foreach ($dates as $targetDate) {
            $oddsList = $oddsService->fetchDailyOdds($targetDate);

            foreach ($oddsList as $dto) {
                $match = FootballMatch::where('api_id', $dto->fixtureId)->first();
                
                if (! $match) {
                    $match = FootballMatch::where(function ($q) use ($dto) {
                            $q->where('home_team', 'like', "%{$dto->homeTeam}%")
                                ->orWhere('away_team', 'like', "%{$dto->awayTeam}%");
                        })
                        ->whereBetween('kickoff_at', [now()->subDays(2), now()->addDays(2)])
                        ->first();
                }

                if ($match) {
                    $fetchStatus = $match->odds_fetch_status ?? [];
                    $syncService->syncOddsForMatch($match, $dto);

                    if (! in_array('pre_match', $fetchStatus)) {
                        $fetchStatus[] = 'pre_match';
                        $match->odds_fetch_status = array_unique($fetchStatus);
                        $match->save();
                    }
                }
            }
        }
    }
}
