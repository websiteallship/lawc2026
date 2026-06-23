<?php

namespace Tests\Feature\Domain\Betting;

use App\Domain\Betting\Data\PlaceBetInput;
use App\Domain\Betting\Exceptions\BetPlacementException;
use App\Domain\Betting\Services\BetPlacementService;
use App\Domain\Wallet\Services\WalletService;
use App\Enums\LedgerType;
use App\Models\FootballMatch;
use App\Models\Market;
use App\Models\MarketOutcome;
use App\Models\Season;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BetPlacementServiceTest extends TestCase
{
    use RefreshDatabase;

    private BetPlacementService $service;

    private WalletService $walletService;

    private User $user;

    private Season $season;

    private Wallet $wallet;

    private FootballMatch $match;

    private Market $market;

    private MarketOutcome $outcome;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(BetPlacementService::class);
        $this->walletService = app(WalletService::class);

        $this->user = User::factory()->create(['status' => 'ACTIVE']);
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

        $this->match = FootballMatch::create([
            'season_id' => $this->season->id,
            'match_code' => 'M001',
            'stage' => 'Group',
            'home_team' => 'Brazil',
            'away_team' => 'Germany',
            'kickoff_at' => now()->addDay(),
            'timezone' => 'Asia/Ho_Chi_Minh',
            'status' => 'SCHEDULED',
        ]);

        $this->market = Market::create([
            'match_id' => $this->match->id,
            'period_type' => 'FULL_TIME',
            'market_type' => 'ASIAN_HANDICAP',
            'name' => 'Kèo chấp',
            'open_at' => now()->subHour(),
            'close_at' => now()->addHour(),
            'status' => 'OPEN',
            'display_order' => 1,
        ]);

        $this->outcome = MarketOutcome::create([
            'market_id' => $this->market->id,
            'label' => 'Home -0.5',
            'selection_side' => 'HOME',
            'line_value' => '-0.50',
            'profit_rate' => '0.9000',
            'status' => 'ACTIVE',
            'display_order' => 1,
        ]);

        // Cấp 1000 lá cho user
        $this->walletService->grant($this->wallet, 1000, null, 'Test setup');
        $this->wallet->refresh();
    }

    private function input(int $stake = 100): PlaceBetInput
    {
        return new PlaceBetInput(
            user: $this->user,
            wallet: $this->wallet,
            market: $this->market,
            outcome: $this->outcome,
            stake: $stake,
        );
    }

    // ===================== HAPPY PATH =====================

    public function test_place_bet_successfully_creates_bet_and_locks_stake(): void
    {
        $bet = $this->service->placeBet($this->input(100));

        $this->assertEquals('PENDING', $bet->status->value);
        $this->assertEquals(100, $bet->stake);
        $this->assertStringStartsWith('DL-', $bet->public_code);

        // Snapshot fields
        $this->assertEquals('0.9000', $bet->profit_rate_snapshot);
        $this->assertEquals('Home -0.5', $bet->label_snapshot);
        $this->assertEquals('ASIAN_HANDICAP', $bet->market_type_snapshot);

        // Wallet: stake đã khóa
        $this->wallet->refresh();
        $this->assertEquals(900, $this->wallet->available_balance);
        $this->assertEquals(100, $this->wallet->locked_balance);
    }

    public function test_ledger_bet_placed_row_created(): void
    {
        $bet = $this->service->placeBet($this->input(100));

        $ledger = $this->wallet->ledgers()
            ->where('type', LedgerType::BET_PLACED->value)
            ->where('bet_id', $bet->id)
            ->first();

        $this->assertNotNull($ledger);
        $this->assertEquals(-100, $ledger->amount_available);
        $this->assertEquals(100, $ledger->amount_locked);
    }

    public function test_multiple_bets_accumulate_correctly(): void
    {
        $this->service->placeBet($this->input(100));
        $this->wallet->refresh();

        // Tạo outcome thứ 2
        $outcome2 = MarketOutcome::create([
            'market_id' => $this->market->id,
            'label' => 'Away +0.5',
            'selection_side' => 'AWAY',
            'line_value' => '0.50',
            'profit_rate' => '0.8500',
            'status' => 'ACTIVE',
            'display_order' => 2,
        ]);

        $input2 = new PlaceBetInput($this->user, $this->wallet, $this->market, $outcome2, 50);
        $this->service->placeBet($input2);
        $this->wallet->refresh();

        $this->assertEquals(850, $this->wallet->available_balance);
        $this->assertEquals(150, $this->wallet->locked_balance);
    }

    // ===================== VALIDATION FAILURES =====================

    public function test_throws_when_user_is_inactive(): void
    {
        $this->user->update(['status' => 'INACTIVE']);
        $this->user->refresh();

        $this->expectException(BetPlacementException::class);

        try {
            $this->service->placeBet($this->input(100));
        } catch (BetPlacementException $e) {
            $this->assertEquals('USER_INACTIVE', $e->getErrorCode());
            throw $e;
        }
    }

    public function test_throws_when_market_is_not_open(): void
    {
        $this->market->update(['status' => 'LOCKED']);

        $this->expectException(BetPlacementException::class);

        try {
            $this->service->placeBet($this->input(100));
        } catch (BetPlacementException $e) {
            $this->assertEquals('MARKET_NOT_OPEN', $e->getErrorCode());
            throw $e;
        }
    }

    public function test_throws_when_market_is_past_close_at(): void
    {
        $this->market->update(['close_at' => now()->subMinute()]);

        $this->expectException(BetPlacementException::class);

        try {
            $this->service->placeBet($this->input(100));
        } catch (BetPlacementException $e) {
            $this->assertEquals('MARKET_CLOSED', $e->getErrorCode());
            throw $e;
        }
    }

    public function test_throws_when_outcome_is_inactive(): void
    {
        $this->outcome->update(['status' => 'INACTIVE']);

        $this->expectException(BetPlacementException::class);

        try {
            $this->service->placeBet($this->input(100));
        } catch (BetPlacementException $e) {
            $this->assertEquals('OUTCOME_INACTIVE', $e->getErrorCode());
            throw $e;
        }
    }

    public function test_throws_when_stake_below_minimum(): void
    {
        $this->expectException(BetPlacementException::class);

        try {
            $this->service->placeBet($this->input(5)); // min = 10
        } catch (BetPlacementException $e) {
            $this->assertEquals('STAKE_TOO_LOW', $e->getErrorCode());
            throw $e;
        }
    }

    public function test_throws_when_stake_exceeds_max_per_bet(): void
    {
        $this->expectException(BetPlacementException::class);

        try {
            $this->service->placeBet($this->input(300)); // max = 200
        } catch (BetPlacementException $e) {
            $this->assertEquals('STAKE_EXCEEDS_MAX_PER_BET', $e->getErrorCode());
            throw $e;
        }
    }

    public function test_throws_when_stake_exceeds_max_per_match(): void
    {
        // Đặt 200 + 200 = 400 => thêm 200 = 600 > max 500
        $this->service->placeBet($this->input(200));
        $this->wallet->refresh();
        $this->service->placeBet($this->input(200));
        $this->wallet->refresh();

        $this->expectException(BetPlacementException::class);

        try {
            $this->service->placeBet($this->input(200)); // tổng = 600 > 500
        } catch (BetPlacementException $e) {
            $this->assertEquals('STAKE_EXCEEDS_MAX_PER_MATCH', $e->getErrorCode());
            throw $e;
        }
    }

    public function test_throws_when_insufficient_balance(): void
    {
        // Wallet chỉ còn 1000 lá, thử đặt đúng max 200 x 5 = 1000 trước
        // Sau đó cố đặt thêm
        $this->service->placeBet($this->input(200));
        $this->wallet->refresh();
        $this->service->placeBet($this->input(200));
        $this->wallet->refresh();
        // Còn 600 lá, limit match = 500 đã qua. Cấp thêm và thử tài khoản âm
        $this->walletService->grant($this->wallet, 5000, null);
        $this->wallet->refresh();

        // Tạo match mới để bypass max_per_match
        $newMatch = FootballMatch::create([
            'season_id' => $this->season->id,
            'match_code' => 'M002',
            'stage' => 'Group',
            'home_team' => 'France',
            'away_team' => 'Spain',
            'kickoff_at' => now()->addDay(),
            'timezone' => 'Asia/Ho_Chi_Minh',
            'status' => 'SCHEDULED',
        ]);
        $newMarket = Market::create([
            'match_id' => $newMatch->id,
            'period_type' => 'FULL_TIME',
            'market_type' => 'OVER_UNDER',
            'name' => 'Tài/Xỉu',
            'open_at' => now()->subHour(),
            'close_at' => now()->addHour(),
            'status' => 'OPEN',
            'display_order' => 1,
        ]);
        $newOutcome = MarketOutcome::create([
            'market_id' => $newMarket->id,
            'label' => 'Tài 2.5',
            'selection_side' => 'OVER',
            'line_value' => '2.50',
            'profit_rate' => '0.9000',
            'status' => 'ACTIVE',
            'display_order' => 1,
        ]);

        // Trừ hết số dư về 0 để test
        $this->wallet->refresh();
        $currentBalance = $this->wallet->available_balance;
        $this->walletService->deduct($this->wallet, $currentBalance, null);
        $this->wallet->refresh();

        $this->expectException(BetPlacementException::class);

        $input = new PlaceBetInput($this->user, $this->wallet, $newMarket, $newOutcome, 100);

        try {
            $this->service->placeBet($input);
        } catch (BetPlacementException $e) {
            $this->assertEquals('INSUFFICIENT_BALANCE', $e->getErrorCode());
            throw $e;
        }
    }

    public function test_wallet_not_changed_on_failed_placement(): void
    {
        $this->market->update(['status' => 'LOCKED']);

        $balanceBefore = $this->wallet->available_balance;

        try {
            $this->service->placeBet($this->input(100));
        } catch (BetPlacementException) {
            // expected
        }

        $this->wallet->refresh();
        $this->assertEquals($balanceBefore, $this->wallet->available_balance);
        $this->assertEquals(0, $this->wallet->locked_balance);
    }
}
