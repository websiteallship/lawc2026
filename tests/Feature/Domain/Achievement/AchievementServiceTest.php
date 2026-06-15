<?php

namespace Tests\Feature\Domain\Achievement;

use App\Domain\Achievement\Services\AchievementService;
use App\Models\Achievement;
use App\Models\Bet;
use App\Models\User;
use App\Models\UserAchievement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AchievementServiceTest extends TestCase
{
    use RefreshDatabase;

    protected AchievementService $service;

    protected function setUp(): void
    {
        parent::setUp();
        \Illuminate\Support\Facades\Schema::disableForeignKeyConstraints();
        $this->service = app(AchievementService::class);
        $this->artisan('db:seed', ['--class' => 'AchievementSeeder']);
    }

    public function test_user_unlocks_apprentice_level_on_first_bet(): void
    {
        $user = User::factory()->create();
        
        // No bets yet
        $this->service->checkAndAward($user->id);
        $this->assertDatabaseMissing('user_achievements', ['user_id' => $user->id]);

        // Place a bet
        Bet::factory()->create(['user_id' => $user->id, 'status' => 'PENDING']);
        
        $this->service->checkAndAward($user->id);
        
        $achievement = Achievement::where('code', 'LV1_APPRENTICE')->first();
        $this->assertDatabaseHas('user_achievements', [
            'user_id' => $user->id,
            'achievement_id' => $achievement->id,
        ]);
    }

    public function test_user_unlocks_lucky_hunter_level_on_5_wins(): void
    {
        $user = User::factory()->create();
        
        // Mock 5 won bets
        for ($i = 0; $i < 5; $i++) {
            Bet::factory()->create(['user_id' => $user->id, 'status' => 'WON']);
        }
        
        $this->service->checkAndAward($user->id);
        
        $achievement = Achievement::where('code', 'LV2_LUCKY_HUNTER')->first();
        $this->assertDatabaseHas('user_achievements', [
            'user_id' => $user->id,
            'achievement_id' => $achievement->id,
        ]);
    }

    public function test_unlocks_high_roller(): void
    {
        $user = User::factory()->create();
        Bet::factory()->create(['user_id' => $user->id, 'stake' => 5000000]);
        
        $this->service->checkAndAward($user->id);
        $this->assertDatabaseHas('user_achievements', [
            'user_id' => $user->id,
            'achievement_id' => Achievement::where('code', 'HIGH_ROLLER')->first()->id,
        ]);
    }

    public function test_unlocks_veteran_100(): void
    {
        $user = User::factory()->create();
        for ($i = 0; $i < 100; $i++) {
            Bet::factory()->create(['user_id' => $user->id]);
        }
        
        $this->service->checkAndAward($user->id);
        $this->assertDatabaseHas('user_achievements', [
            'user_id' => $user->id,
            'achievement_id' => Achievement::where('code', 'DEDICATION_100_BETS')->first()->id,
        ]);
    }

    public function test_unlocks_multi_market(): void
    {
        $user = User::factory()->create();
        Bet::factory()->create(['user_id' => $user->id, 'market_type_snapshot' => 'ASIAN_HANDICAP']);
        Bet::factory()->create(['user_id' => $user->id, 'market_type_snapshot' => 'OVER_UNDER']);
        Bet::factory()->create(['user_id' => $user->id, 'market_type_snapshot' => 'EXACT_SCORE']);
        
        $this->service->checkAndAward($user->id);
        $this->assertDatabaseHas('user_achievements', [
            'user_id' => $user->id,
            'achievement_id' => Achievement::where('code', 'MULTI_MARKET')->first()->id,
        ]);
    }

    public function test_unlocks_night_owl(): void
    {
        $user = User::factory()->create();
        for ($i = 0; $i < 7; $i++) {
            Bet::factory()->create([
                'user_id' => $user->id,
                'placed_at' => now()->subDays($i)->setTime(2, 30, 0),
            ]);
        }
        
        $this->service->checkAndAward($user->id);

        $nightDaysCount = Bet::where('user_id', $user->id)
            ->whereTime('placed_at', '>=', '00:00:00')
            ->whereTime('placed_at', '<=', '05:00:00')
            ->selectRaw('DATE(placed_at) as date')
            ->groupBy('date')
            ->get()
            ->count();

        $this->assertDatabaseHas('user_achievements', [
            'user_id' => $user->id,
            'achievement_id' => Achievement::where('code', 'NIGHT_OWL')->first()->id,
        ]);
    }

    public function test_unlocks_dedication_7_days(): void
    {
        $user = User::factory()->create();
        for ($i = 0; $i < 7; $i++) {
            Bet::factory()->create([
                'user_id' => $user->id,
                'placed_at' => now()->subDays($i)->startOfDay(),
            ]);
        }
        
        $this->service->checkAndAward($user->id);
        
        $recentDays = Bet::where('user_id', $user->id)
            ->selectRaw('DATE(placed_at) as date')
            ->groupBy('date')
            ->orderBy('date', 'desc')
            ->limit(7)
            ->pluck('date')
            ->toArray();
        $firstDate = \Carbon\Carbon::parse($recentDays[6])->startOfDay();
        $lastDate = \Carbon\Carbon::parse($recentDays[0])->startOfDay();

        $this->assertDatabaseHas('user_achievements', [
            'user_id' => $user->id,
            'achievement_id' => Achievement::where('code', 'DEDICATION_7_DAYS')->first()->id,
        ]);
    }
}
