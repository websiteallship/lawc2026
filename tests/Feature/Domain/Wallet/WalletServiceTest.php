<?php

namespace Tests\Feature\Domain\Wallet;

use App\Domain\Wallet\Exceptions\InsufficientBalanceException;
use App\Domain\Wallet\Services\WalletService;
use App\Enums\BetStatus;
use App\Enums\LedgerType;
use App\Models\Bet;
use App\Models\FootballMatch;
use App\Models\Market;
use App\Models\MarketOutcome;
use App\Models\Season;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WalletServiceTest extends TestCase
{
    use RefreshDatabase;

    private WalletService $service;

    private User $user;

    private Season $season;

    private Wallet $wallet;

    private int $matchId;

    private int $marketId;

    private int $outcomeId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(WalletService::class);
        $this->user = User::factory()->create();
        $this->season = Season::factory()->create(['default_starting_leaves' => 0]);
        $this->wallet = Wallet::create([
            'user_id' => $this->user->id,
            'season_id' => $this->season->id,
            'available_balance' => 0,
            'locked_balance' => 0,
            'total_staked' => 0,
            'total_payout' => 0,
            'net_profit' => 0,
            'status' => 'ACTIVE',
        ]);

        // Tạo stub FK dependencies
        $match = FootballMatch::create([
            'season_id' => $this->season->id,
            'match_code' => 'M001',
            'stage' => 'Group',
            'home_team' => 'Brazil',
            'away_team' => 'Germany',
            'kickoff_at' => now()->addDay(),
            'timezone' => 'Asia/Ho_Chi_Minh',
            'status' => 'SCHEDULED',
        ]);
        $market = Market::create([
            'match_id' => $match->id,
            'period_type' => 'FULL_TIME',
            'market_type' => 'ASIAN_HANDICAP',
            'name' => 'Kèo chấp',
            'open_at' => now(),
            'close_at' => now()->addHour(),
            'status' => 'OPEN',
            'display_order' => 1,
        ]);
        $outcome = MarketOutcome::create([
            'market_id' => $market->id,
            'label' => 'Home -0.5',
            'selection_side' => 'HOME',
            'line_value' => '-0.50',
            'profit_rate' => '0.9000',
            'status' => 'ACTIVE',
            'display_order' => 1,
        ]);

        $this->matchId = $match->id;
        $this->marketId = $market->id;
        $this->outcomeId = $outcome->id;
    }

    private function makeBet(int $stake = 100): Bet
    {
        return Bet::create([
            'public_code' => 'BET-'.uniqid(),
            'user_id' => $this->user->id,
            'wallet_id' => $this->wallet->id,
            'season_id' => $this->season->id,
            'match_id' => $this->matchId,
            'market_id' => $this->marketId,
            'outcome_id' => $this->outcomeId,
            'stake' => $stake,
            'profit_rate_snapshot' => '0.9000',
            'label_snapshot' => 'Home -0.5',
            'display_odds_snapshot' => 'Home -0.5 ăn 0.90',
            'close_at_snapshot' => now()->addHour(),
            'market_type_snapshot' => 'ASIAN_HANDICAP',
            'period_type_snapshot' => 'FULL_TIME',
            'selection_side_snapshot' => 'HOME',
            'status' => 'PENDING',
            'placed_at' => now(),
        ]);
    }

    public function test_grant_increases_available_balance_and_creates_ledger(): void
    {
        $ledger = $this->service->grant($this->wallet, 500, null, 'Test grant');

        $this->wallet->refresh();

        $this->assertEquals(500, $this->wallet->available_balance);
        $this->assertEquals(0, $this->wallet->locked_balance);
        $this->assertEquals(LedgerType::ADMIN_GRANT, $ledger->type);
        $this->assertEquals(500, $ledger->amount_available);
        $this->assertEquals(500, $ledger->balance_available_after);
    }

    public function test_deduct_decreases_available_balance_and_creates_ledger(): void
    {
        $this->service->grant($this->wallet, 1000, null);
        $this->wallet->refresh();

        $ledger = $this->service->deduct($this->wallet, 300, null, 'Test deduct');
        $this->wallet->refresh();

        $this->assertEquals(700, $this->wallet->available_balance);
        $this->assertEquals(LedgerType::ADMIN_DEDUCT, $ledger->type);
        $this->assertEquals(-300, $ledger->amount_available);
    }

    public function test_deduct_throws_when_insufficient_balance(): void
    {
        $this->service->grant($this->wallet, 100, null);
        $this->wallet->refresh();

        $this->expectException(InsufficientBalanceException::class);

        $this->service->deduct($this->wallet, 200, null);
    }

    public function test_lock_stake_moves_from_available_to_locked(): void
    {
        $this->service->grant($this->wallet, 500, null);
        $this->wallet->refresh();

        $bet = $this->makeBet(100);

        $this->service->lockStake($this->wallet, 100, $bet);
        $this->wallet->refresh();

        $this->assertEquals(400, $this->wallet->available_balance);
        $this->assertEquals(100, $this->wallet->locked_balance);
        $this->assertEquals(100, $this->wallet->total_staked);
    }

    public function test_lock_stake_throws_when_insufficient_balance(): void
    {
        $this->service->grant($this->wallet, 50, null);
        $this->wallet->refresh();

        $bet = $this->makeBet(100);

        $this->expectException(InsufficientBalanceException::class);

        $this->service->lockStake($this->wallet, 100, $bet);
    }

    public function test_settle_bet_won_releases_locked_and_adds_payout(): void
    {
        $this->service->grant($this->wallet, 500, null);
        $this->wallet->refresh();

        $bet = $this->makeBet(100);
        $this->service->lockStake($this->wallet, 100, $bet);
        $this->wallet->refresh();

        // stake 100, profit_rate 0.90 → gross_payout = 190
        $this->service->settleBet($this->wallet, $bet, 190, BetStatus::WON);
        $this->wallet->refresh();

        $this->assertEquals(590, $this->wallet->available_balance); // 400 + 190
        $this->assertEquals(0, $this->wallet->locked_balance);
        $this->assertEquals(190, $this->wallet->total_payout);
        $this->assertEquals(90, $this->wallet->net_profit); // 190 - 100
    }

    public function test_settle_bet_lost_releases_locked_with_zero_payout(): void
    {
        $this->service->grant($this->wallet, 500, null);
        $this->wallet->refresh();

        $bet = $this->makeBet(100);
        $this->service->lockStake($this->wallet, 100, $bet);
        $this->wallet->refresh();

        $this->service->settleBet($this->wallet, $bet, 0, BetStatus::LOST);
        $this->wallet->refresh();

        $this->assertEquals(400, $this->wallet->available_balance);
        $this->assertEquals(0, $this->wallet->locked_balance);
        $this->assertEquals(0, $this->wallet->total_payout);
        $this->assertEquals(-100, $this->wallet->net_profit);
    }

    public function test_void_bet_returns_stake_from_locked_to_available(): void
    {
        $this->service->grant($this->wallet, 500, null);
        $this->wallet->refresh();

        $bet = $this->makeBet(150);
        $this->service->lockStake($this->wallet, 150, $bet);
        $this->wallet->refresh();

        $this->service->voidBet($this->wallet, $bet);
        $this->wallet->refresh();

        $this->assertEquals(500, $this->wallet->available_balance);
        $this->assertEquals(0, $this->wallet->locked_balance);

        $ledger = $this->wallet->ledgers()->where('type', LedgerType::BET_VOIDED->value)->first();
        $this->assertNotNull($ledger);
        $this->assertEquals($bet->id, $ledger->bet_id);
    }

    public function test_available_balance_never_goes_negative(): void
    {
        $this->service->grant($this->wallet, 100, null);
        $this->wallet->refresh();

        $this->expectException(InsufficientBalanceException::class);

        $this->service->deduct($this->wallet, 101, null);
    }

    public function test_grant_with_invalid_amount_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->service->grant($this->wallet, 0, null);
    }
}
