<?php

namespace Tests\Feature\Domain\Leaderboard;

use App\Domain\Leaderboard\Services\LeaderboardService;
use App\Domain\Settlement\Data\MatchResult;
use App\Domain\Settlement\Services\SettlementEngine;
use App\Domain\Wallet\Services\WalletService;
use App\Jobs\RebuildLeaderboardJob;
use App\Models\Bet;
use App\Models\FootballMatch;
use App\Models\Market;
use App\Models\MarketOutcome;
use App\Models\Season;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class LeaderboardServiceTest extends TestCase
{
    use RefreshDatabase;

    private LeaderboardService $service;

    private WalletService $walletService;

    private Season $season;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(LeaderboardService::class);
        $this->walletService = app(WalletService::class);
        $this->season = Season::factory()->create(['default_starting_leaves' => 0]);
    }

    // ===================== HELPERS =====================

    private function makeUser(string $name = 'User'): User
    {
        return User::factory()->create(['name' => $name, 'status' => 'ACTIVE']);
    }

    private function makeWallet(User $user, int $balance = 0): Wallet
    {
        $wallet = Wallet::create([
            'user_id' => $user->id,
            'season_id' => $this->season->id,
            'available_balance' => 0,
            'locked_balance' => 0,
            'total_staked' => 0,
            'total_payout' => 0,
            'net_profit' => 0,
            'status' => 'ACTIVE',
        ]);

        if ($balance > 0) {
            $this->walletService->grant($wallet, $balance, null);
            $wallet->refresh();
        }

        return $wallet;
    }

    private function makeSettledBet(
        Wallet $wallet,
        string $matchCode,
        string $betStatus,
        int $stake,
        int $grossPayout,
        string $marketType = 'ASIAN_HANDICAP'
    ): Bet {
        $match = FootballMatch::create([
            'season_id' => $this->season->id,
            'match_code' => $matchCode,
            'stage' => 'Group',
            'home_team' => 'TeamA',
            'away_team' => 'TeamB',
            'kickoff_at' => now()->subDay(),
            'timezone' => 'Asia/Ho_Chi_Minh',
            'status' => 'FINISHED',
        ]);

        $market = Market::create([
            'match_id' => $match->id,
            'period_type' => 'FULL_TIME',
            'market_type' => $marketType,
            'name' => 'Test Market',
            'open_at' => now()->subDay(),
            'close_at' => now()->subHours(2),
            'status' => 'SETTLED',
            'display_order' => 1,
        ]);

        $outcome = MarketOutcome::create([
            'market_id' => $market->id,
            'label' => 'Test',
            'profit_rate' => '0.90',
            'status' => 'ACTIVE',
            'display_order' => 1,
        ]);

        $netResult = $grossPayout - $stake;

        return Bet::create([
            'public_code' => 'BET-'.uniqid(),
            'user_id' => $wallet->user_id,
            'wallet_id' => $wallet->id,
            'season_id' => $this->season->id,
            'match_id' => $match->id,
            'market_id' => $market->id,
            'outcome_id' => $outcome->id,
            'stake' => $stake,
            'profit_rate_snapshot' => '0.9000',
            'label_snapshot' => 'Test',
            'display_odds_snapshot' => 'Test ăn 0.90',
            'close_at_snapshot' => now()->subHours(2),
            'market_type_snapshot' => $marketType,
            'period_type_snapshot' => 'FULL_TIME',
            'status' => $betStatus,
            'gross_payout' => $grossPayout,
            'net_result' => $netResult,
            'placed_at' => now()->subDay(),
            'settled_at' => now()->subHour(),
        ]);
    }

    // ===================== UNIT TESTS =====================

    public function test_compute_returns_empty_for_season_with_no_wallets(): void
    {
        $result = $this->service->computeSeason($this->season);
        $this->assertCount(0, $result);
    }

    public function test_compute_ranks_by_net_profit_desc(): void
    {
        $userA = $this->makeUser('Alpha');
        $walletA = $this->makeWallet($userA, 1000);

        $userB = $this->makeUser('Beta');
        $walletB = $this->makeWallet($userB, 1000);

        // Alpha: net_profit = +300 (won 400, staked 100)
        $this->walletService->grant($walletA, 100, null);
        $walletA->refresh();
        $walletA->update([
            'total_staked' => 100,
            'total_payout' => 400,
            'net_profit' => 300,
        ]);
        $this->makeSettledBet($walletA, 'MA1', 'WON', 100, 400);

        // Beta: net_profit = -50 (lost 100, staked 100)
        $this->walletService->grant($walletB, 100, null);
        $walletB->refresh();
        $walletB->update([
            'total_staked' => 100,
            'total_payout' => 50,
            'net_profit' => -50,
        ]);
        $this->makeSettledBet($walletB, 'MB1', 'LOST', 100, 0);

        $entries = $this->service->computeSeason($this->season);

        $this->assertCount(2, $entries);
        $this->assertEquals(1, $entries->firstWhere('userId', $userA->id)->rank);
        $this->assertEquals(2, $entries->firstWhere('userId', $userB->id)->rank);
    }

    public function test_roi_calculated_correctly(): void
    {
        $user = $this->makeUser('Roi User');
        $wallet = $this->makeWallet($user, 1000);

        // net_profit = 90, total_staked = 100 → ROI = 90%
        $wallet->update([
            'total_staked' => 100,
            'total_payout' => 190,
            'net_profit' => 90,
        ]);
        $this->makeSettledBet($wallet, 'MR1', 'WON', 100, 190);

        $entries = $this->service->computeSeason($this->season);
        $entry = $entries->firstWhere('userId', $user->id);

        $this->assertEquals(90.0, $entry->roi);
        $this->assertEquals(100.0, $entry->winRate); // 1 won / 1 settled
    }

    public function test_win_rate_zero_when_no_settled_bets(): void
    {
        $user = $this->makeUser('New User');
        $this->makeWallet($user, 1000);

        // Chưa có bet nào settled
        $entries = $this->service->computeSeason($this->season);
        $entry = $entries->firstWhere('userId', $user->id);

        $this->assertNotNull($entry);
        $this->assertEquals(0.0, $entry->winRate);
        $this->assertEquals(0.0, $entry->roi);
    }

    public function test_exact_score_wins_counted_correctly(): void
    {
        $user = $this->makeUser('Exact User');
        $wallet = $this->makeWallet($user, 2000);

        // 2 EXACT_SCORE WON + 1 AH WON
        $this->makeSettledBet($wallet, 'ME1', 'WON', 100, 800, 'EXACT_SCORE');
        $this->makeSettledBet($wallet, 'ME2', 'WON', 100, 800, 'EXACT_SCORE');
        $this->makeSettledBet($wallet, 'ME3', 'WON', 100, 190, 'ASIAN_HANDICAP');

        $entries = $this->service->computeSeason($this->season);
        $entry = $entries->firstWhere('userId', $user->id);

        $this->assertEquals(2, $entry->exactScoreWins);
        $this->assertEquals(3, $entry->wonBets);
    }

    public function test_tiebreak_by_roi_when_net_profit_equal(): void
    {
        $userA = $this->makeUser('TieA');
        $walletA = $this->makeWallet($userA, 1000);
        $walletA->update(['total_staked' => 100, 'total_payout' => 190, 'net_profit' => 90]);
        $this->makeSettledBet($walletA, 'TIE1', 'WON', 100, 190);

        $userB = $this->makeUser('TieB');
        $walletB = $this->makeWallet($userB, 2000);
        // Cũng net_profit = 90, nhưng staked 200 → ROI = 45%
        $walletB->update(['total_staked' => 200, 'total_payout' => 290, 'net_profit' => 90]);
        $this->makeSettledBet($walletB, 'TIE2', 'WON', 200, 290);

        $entries = $this->service->computeSeason($this->season);

        // A rank 1 (ROI 90% > 45%)
        $entryA = $entries->firstWhere('userId', $userA->id);
        $entryB = $entries->firstWhere('userId', $userB->id);
        $this->assertEquals(1, $entryA->rank);
        $this->assertEquals(2, $entryB->rank);
    }

    public function test_snapshot_persists_to_db(): void
    {
        $user = $this->makeUser('Snap User');
        $wallet = $this->makeWallet($user, 1000);
        $wallet->update(['total_staked' => 100, 'total_payout' => 190, 'net_profit' => 90]);
        $this->makeSettledBet($wallet, 'SN1', 'WON', 100, 190);

        $this->service->snapshot($this->season);

        $this->assertDatabaseHas('leaderboard_snapshots', [
            'season_id' => $this->season->id,
            'user_id' => $user->id,
            'rank' => 1,
            'net_profit' => 90,
        ]);
    }

    public function test_get_latest_snapshot_returns_latest(): void
    {
        $user = $this->makeUser('Latest User');
        $this->makeWallet($user, 1000);

        $this->service->snapshot($this->season);

        $snapshots = $this->service->getLatestSnapshot($this->season);
        $this->assertCount(1, $snapshots);
        $this->assertEquals($user->id, $snapshots->first()->user_id);
    }

    // ===================== QUEUE JOB =====================

    public function test_rebuild_leaderboard_job_dispatched_after_settlement(): void
    {
        Queue::fake();

        $user = $this->makeUser('Job User');
        $wallet = $this->makeWallet($user, 1000);
        $this->walletService->grant($wallet, 100, null);
        $wallet->refresh();

        $match = FootballMatch::create([
            'season_id' => $this->season->id,
            'match_code' => 'JOB-M1',
            'stage' => 'Group',
            'home_team' => 'A', 'away_team' => 'B',
            'kickoff_at' => now()->subHours(3),
            'timezone' => 'Asia/Ho_Chi_Minh',
            'status' => 'FINISHED',
        ]);
        $market = Market::create([
            'match_id' => $match->id, 'period_type' => 'FULL_TIME',
            'market_type' => 'OVER_UNDER', 'name' => 'Tài/Xỉu',
            'open_at' => now()->subHours(4), 'close_at' => now()->subHours(2),
            'status' => 'LOCKED', 'display_order' => 1,
        ]);
        $outcome = MarketOutcome::create([
            'market_id' => $market->id, 'label' => 'Tài 2.5',
            'selection_side' => 'OVER', 'line_value' => '2.50',
            'profit_rate' => '0.90', 'status' => 'ACTIVE', 'display_order' => 1,
        ]);

        $bet = Bet::create([
            'public_code' => 'JOB-BET-1', 'user_id' => $user->id, 'wallet_id' => $wallet->id,
            'season_id' => $this->season->id, 'match_id' => $match->id,
            'market_id' => $market->id, 'outcome_id' => $outcome->id,
            'stake' => 100, 'profit_rate_snapshot' => '0.9000',
            'label_snapshot' => 'Tài 2.5', 'display_odds_snapshot' => 'Tài 2.5 ăn 0.90',
            'close_at_snapshot' => now()->subHours(2), 'market_type_snapshot' => 'OVER_UNDER',
            'period_type_snapshot' => 'FULL_TIME', 'selection_side_snapshot' => 'OVER',
            'status' => 'PENDING', 'placed_at' => now()->subHours(3),
        ]);

        $this->walletService->lockStake($wallet, 100, $bet);

        app(SettlementEngine::class)
            ->execute($market, new MatchResult(2, 1)); // 3 goals → Tài thắng

        Queue::assertPushed(RebuildLeaderboardJob::class, function ($job) {
            return $job->seasonId === $this->season->id;
        });
    }
}
