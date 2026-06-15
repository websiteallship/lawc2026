<?php

namespace Tests\Feature\Domain\Mission;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

use App\Models\User;
use App\Models\Mission;
use App\Models\UserMission;
use App\Domain\Mission\Services\MissionService;

class MissionServiceTest extends TestCase
{
    use RefreshDatabase;

    protected MissionService $missionService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->missionService = app(MissionService::class);
    }

    public function test_it_tracks_progress()
    {
        $user = User::factory()->create();
        $mission = Mission::create([
            'code' => 'TEST_MISSION',
            'title' => 'Test',
            'type' => 'daily',
            'target_value' => 5,
            'is_active' => true,
        ]);

        $this->missionService->trackProgress($user->id, 'TEST_MISSION', 2);

        $this->assertDatabaseHas('user_missions', [
            'user_id' => $user->id,
            'mission_id' => $mission->id,
            'current_value' => 2,
            'is_completed' => false,
        ]);
        
        $this->missionService->trackProgress($user->id, 'TEST_MISSION', 3);

        $this->assertDatabaseHas('user_missions', [
            'user_id' => $user->id,
            'mission_id' => $mission->id,
            'current_value' => 5,
            'is_completed' => true,
        ]);
    }

    public function test_it_does_not_track_inactive_missions()
    {
        $user = User::factory()->create();
        $mission = Mission::create([
            'code' => 'INACTIVE_MISSION',
            'title' => 'Test',
            'type' => 'daily',
            'target_value' => 5,
            'is_active' => false,
        ]);

        $this->missionService->trackProgress($user->id, 'INACTIVE_MISSION', 1);

        $this->assertDatabaseMissing('user_missions', [
            'user_id' => $user->id,
            'mission_id' => $mission->id,
        ]);
    }

    public function test_it_evaluates_daily_missions()
    {
        $user = User::factory()->create();
        $mission = Mission::create([
            'code' => 'DAILY_TEST',
            'title' => 'Test',
            'type' => 'daily',
            'target_value' => 1,
            'is_active' => true,
        ]);

        UserMission::create([
            'user_id' => $user->id,
            'mission_id' => $mission->id,
            'current_value' => 1,
            'is_completed' => true,
        ]);

        $this->missionService->evaluateDailyMissions();

        $this->assertDatabaseMissing('user_missions', [
            'user_id' => $user->id,
            'mission_id' => $mission->id,
        ]);
    }
}
