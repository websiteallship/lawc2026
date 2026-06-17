<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Domain\Betting\Services\UserStatisticsService;
use Illuminate\Support\Facades\Cache;

class SyncUserStatsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:sync-stats';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recalculate user statistics for all users';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting user statistics sync...');
        $users = User::all();
        $service = app(UserStatisticsService::class);
        $bar = $this->output->createProgressBar(count($users));

        foreach ($users as $user) {
            $service->recalculateForUser($user->id);
            Cache::forget("player_combined_stats_{$user->id}");
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('Successfully synchronized all user statistics!');
    }
}
