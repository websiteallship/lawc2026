<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\MarketOutcome;

class CleanupBadOutcomesCommand extends Command
{
    protected $signature = 'odds:cleanup-bad';
    protected $description = 'Suspend all outcomes and their pairs that have profit_rate < 0.50';

    public function handle()
    {
        $this->info("Scanning active outcomes...");

        $outcomes = MarketOutcome::with('market')
            ->where('status', 'ACTIVE')
            ->whereHas('market', function($q) {
                $q->whereIn('market_type', ['ASIAN_HANDICAP', 'OVER_UNDER']);
            })
            ->get()
            ->groupBy(function($o) {
                return $o->market_id . '_' . $o->line_value;
            });

        $suspendIds = [];

        foreach ($outcomes as $key => $lineOutcomes) {
            $hasBad = false;
            foreach ($lineOutcomes as $o) {
                if ($o->profit_rate < 0.50) {
                    $hasBad = true;
                    break;
                }
            }
            if ($hasBad) {
                foreach ($lineOutcomes as $o) {
                    $suspendIds[] = $o->id;
                }
            }
        }

        if (empty($suspendIds)) {
            $this->info("No bad outcomes found.");
            return;
        }

        MarketOutcome::whereIn('id', $suspendIds)->update(['status' => 'SUSPENDED']);
        
        $this->info("Suspended " . count($suspendIds) . " bad outcomes.");
    }
}
