<?php

namespace Tests\Feature\Console;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

use App\Models\Mission;
use Illuminate\Support\Facades\Artisan;

class RotateWeeklyMissionsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_rotates_weekly_missions()
    {
        // Create 10 weekly missions
        for ($i = 1; $i <= 10; $i++) {
            Mission::create([
                'code' => 'WEEKLY_W' . $i,
                'title' => 'Test ' . $i,
                'type' => 'weekly',
                'target_value' => 1,
                'is_active' => false,
            ]);
        }
        
        // Active one to make sure it gets reset
        Mission::where('code', 'WEEKLY_W1')->update(['is_active' => true]);

        Artisan::call('missions:rotate-weekly');

        $activeMissionsCount = Mission::where('type', 'weekly')->where('is_active', true)->count();
        
        $this->assertEquals(5, $activeMissionsCount);
    }
}
