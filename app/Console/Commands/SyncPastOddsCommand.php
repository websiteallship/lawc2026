<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Domain\Market\Services\OddsIntegrationService;
use App\Domain\Market\Services\MarketSyncService;
use App\Models\FootballMatch;
use Illuminate\Support\Facades\Artisan;

class SyncPastOddsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'odds:sync-past {date : The date to sync (YYYY-MM-DD)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync odds for a past date to recover missing markets due to system downtime';

    /**
     * Execute the console command.
     */
    public function handle(OddsIntegrationService $oddsService, MarketSyncService $syncService)
    {
        $date = $this->argument('date');
        $this->info("Fetching odds for $date...");
        
        $oddsList = $oddsService->fetchDailyOdds($date);
        
        if (empty($oddsList)) {
            $this->warn("No odds found for $date");
            return;
        }
        
        $this->info("Found " . count($oddsList) . " matches with odds data.");

        foreach ($oddsList as $dto) {
            $match = FootballMatch::where('api_id', $dto->fixtureId)->first();
            if ($match) {
                $this->info("Syncing odds for match {$match->id} ({$match->home_team} vs {$match->away_team})...");
                $syncService->syncOddsForMatch($match, $dto);
                
                if ($match->status === 'FINISHED') {
                    $this->info("Match is finished. Triggering settlement...");
                    // Lock open markets
                    $match->markets()->where('status', 'OPEN')->update(['status' => 'LOCKED', 'close_at' => now()]);
                    
                    // Trigger settlement
                    Artisan::call('settle:markets', ['match_code' => $match->match_code]);
                    $this->line(Artisan::output());
                }
            }
        }
        
        $this->info("Done syncing past odds for $date!");
    }
}
