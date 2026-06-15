<?php

namespace Tests\Feature\Domain\Betting;

use App\Domain\Betting\Services\UserStatisticsService;
use App\Models\Bet;
use App\Models\Market;
use App\Models\FootballMatch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;

class UserStatisticsServiceTest extends TestCase
{
    use RefreshDatabase;

    protected UserStatisticsService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new UserStatisticsService();
    }

    public function test_it_calculates_statistics_correctly()
    {
        $user = User::factory()->create();

        // Bet 1: Won, Stake 100, Payout 190, EXACT_SCORE (streak: 1)
        Bet::factory()->create([
            'user_id' => $user->id,
            'match_id' => 1,
            'market_id' => 1,
            'stake' => 100,
            'gross_payout' => 190,
            'status' => 'WON',
            'market_type_snapshot' => 'EXACT_SCORE',
            'settled_at' => Carbon::now()->subDays(5),
        ]);

        // Bet 2: Lost, Stake 50, Payout 0 (streak: 0)
        Bet::factory()->create([
            'user_id' => $user->id,
            'match_id' => 1,
            'market_id' => 1,
            'stake' => 50,
            'gross_payout' => 0,
            'status' => 'LOST',
            'market_type_snapshot' => 'ASIAN_HANDICAP',
            'settled_at' => Carbon::now()->subDays(4),
        ]);

        // Bet 3: Won, Stake 100, Payout 200 (streak: 1)
        Bet::factory()->create([
            'user_id' => $user->id,
            'match_id' => 1,
            'market_id' => 1,
            'stake' => 100,
            'gross_payout' => 200,
            'status' => 'WON',
            'market_type_snapshot' => 'OVER_UNDER',
            'settled_at' => Carbon::now()->subDays(3),
        ]);

        // Bet 4: Push, Stake 100, Payout 100 (streak: 1)
        Bet::factory()->create([
            'user_id' => $user->id,
            'match_id' => 1,
            'market_id' => 1,
            'stake' => 100,
            'gross_payout' => 100,
            'status' => 'PUSH',
            'market_type_snapshot' => 'ASIAN_HANDICAP',
            'settled_at' => Carbon::now()->subDays(2),
        ]);

        // Bet 5: Pending, Stake 200, Payout null
        Bet::factory()->create([
            'user_id' => $user->id,
            'match_id' => 1,
            'market_id' => 1,
            'stake' => 200,
            'gross_payout' => null,
            'status' => 'PENDING',
            'market_type_snapshot' => 'EXACT_SCORE',
            'settled_at' => null,
        ]);

        $stats = $this->service->recalculateForUser($user->id);

        $this->assertEquals(5, $stats->total_bets);
        $this->assertEquals(4, $stats->settled_bets);
        $this->assertEquals(2, $stats->won_bets);
        $this->assertEquals(1, $stats->lost_bets);
        $this->assertEquals(1, $stats->push_bets);
        $this->assertEquals(0, $stats->voided_bets);
        
        $this->assertEquals(350, $stats->total_staked); // 100 + 50 + 100 + 100
        $this->assertEquals(490, $stats->total_payout); // 190 + 0 + 200 + 100
        $this->assertEquals(140, $stats->net_profit); // 490 - 350
        
        $this->assertEquals(40.00, $stats->roi); // (140 / 350) * 100
        $this->assertEquals(66.67, round($stats->win_rate, 2)); // 2 / 3 * 100
        
        $this->assertEquals(1, $stats->exact_score_wins);
        $this->assertEquals(1, $stats->current_win_streak);
        $this->assertEquals(1, $stats->longest_win_streak);
    }
}
