<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class RotateWeeklyMissionsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'missions:rotate-weekly';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rotate weekly missions (Select 5 out of 10 weekly missions globally)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // 1. Reset (Deactivate) all weekly missions
        \App\Models\Mission::where('type', 'weekly')->update(['is_active' => false]);
        
        // 2. Select 5 random weekly missions, ordered by difficulty
        $selectedMissions = \App\Models\Mission::where('type', 'weekly')
            ->where('code', 'like', 'WEEKLY_W%') // only pick from the 10 fixed ones
            ->inRandomOrder()
            ->limit(5)
            ->get();
            
        // 3. Activate them
        foreach ($selectedMissions as $mission) {
            $mission->update(['is_active' => true]);
        }
        
        $this->info('Successfully rotated weekly missions. Activated 5 missions.');
    }
}
