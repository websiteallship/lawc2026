<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Domain\Mission\Services\MissionService;

class EvaluateMissionsCommand extends Command
{
    protected $signature = 'app:evaluate-missions {--type= : Type of missions to evaluate (daily, weekly)}';

    protected $description = 'Evaluate and reset missions (daily/weekly)';

    public function handle(MissionService $missionService)
    {
        $type = $this->option('type');

        if ($type === 'daily') {
            $missionService->evaluateDailyMissions();
            $this->info('Daily missions evaluated/reset.');
        } elseif ($type === 'weekly') {
            $missionService->evaluateWeeklyMissions();
            $this->info('Weekly missions evaluated/reset.');
        } else {
            $missionService->evaluateDailyMissions();
            $missionService->evaluateWeeklyMissions();
            $this->info('All missions evaluated/reset.');
        }
    }
}
