<?php

namespace Tests\Feature\Domain\Betting;

use App\Domain\Betting\Exceptions\BetPlacementException;
use App\Domain\Betting\Services\BetPlacementService;
use App\Domain\Betting\Services\CancelBetService;
use App\Domain\Betting\Data\PlaceBetInput;
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

class CancelBetServiceTest extends TestCase
{
    use RefreshDatabase;

    private CancelBetService $service;
    private BetPlacementService $placementService;
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

        $this->service          = app(CancelBetService::class);
        $this->placementService = app(BetPlacementService::class);
        $this->walletService    = app(WalletService::class);

        $this->user   = User::factory()->create(['status' => 'ACTIVE']);
        $this->season = Season::factory()->create(['default_starting_leaves' => 0]);

        $this->wallet = Wallet::create([
            'user_id'           => $this->user->id,
            'season_id'         => $this->season->id,
            'available_balance' => 0,
            'locked_balance'    => 0,
            'total_staked'      => 0,
            'total_payout'      => 0,
            'net_profit'        => 0,
            'status'            => 'ACTIVE',
        ]);

        $this->match = FootballMatch::create([
            'season_id'   => $this->season->id,
            'match_code'  => 'M-TEST-01',
            'stage'       => 'Group',
            'home_team'   => 'Brazil',
            'away_team'   => 'Germany',
            'kickoff_at'  => now()->addDay(),
            'timezone'    => 'Asia/Ho_Chi_Minh',
            'status'      => 'SCHEDULED',
        ]);

        $this->market = Market::create([
            'match_id'      => $this->match->id,
            'period_type'   => 'FULL_TIME',
            'market_type'   => 'ASIAN_HANDICAP',
            'name'          => 'Kèo chấp',
            'open_at'       => now()->subHour(),
            'close_at'      => now()->addHour(),
            'status'        => 'OPEN',
            'display_order' => 1,
        ]);

        $this->outcome = MarketOutcome::create([
            'market_id'     => $this->market->id,
            'label'         => 'Home -0.5',
            'selection_side'=> 'HOME',
            'line_value'    => '-0.50',
            'profit_rate'   => '0.9000',
            'status'        => 'ACTIVE',
            'display_order' => 1,
        ]);

        // Cấp 5000 lá
        $this->walletService->grant($this->wallet, 5000, null, 'Test setup');
        $this->wallet->refresh();
    }

    /** Helper: đặt 1 vé và trả về Bet */
    private function placeBet(int $stake = 100): Bet
    {
        $input = new PlaceBetInput(
            user:    $this->user,
            wallet:  $this->wallet,
            market:  $this->market,
            outcome: $this->outcome,
            stake:   $stake,
        );
        $bet = $this->placementService->placeBet($input);
        $this->wallet->refresh();
        return $bet;
    }

    // ===================== TC1: HAPPY PATH =====================

    public function test_cancel_pending_bet_returns_voided_status_and_restores_wallet(): void
    {
        $bet = $this->placeBet(100);

        $availableBefore = $this->wallet->available_balance;
        $lockedBefore    = $this->wallet->locked_balance;

        $result = $this->service->cancelBet($bet, $this->user);

        $this->assertArrayHasKey('bet', $result);
        $this->assertArrayHasKey('remaining', $result);
        $this->assertArrayHasKey('show_warning', $result);

        $this->assertEquals(BetStatus::VOIDED->value, $result['bet']->status->value);
        $this->assertNotNull($result['bet']->voided_at);

        $this->wallet->refresh();
        $this->assertEquals($availableBefore + 100, $this->wallet->available_balance);
        $this->assertEquals($lockedBefore - 100, $this->wallet->locked_balance);
    }

    // ===================== TC2: BET NOT PENDING =====================

    public function test_throws_when_bet_is_not_pending(): void
    {
        $bet = $this->placeBet(100);
        // Force status VOIDED
        $bet->update(['status' => BetStatus::VOIDED]);

        $this->expectException(BetPlacementException::class);

        try {
            $this->service->cancelBet($bet, $this->user);
        } catch (BetPlacementException $e) {
            $this->assertEquals('BET_NOT_PENDING', $e->getErrorCode());
            throw $e;
        }
    }

    // ===================== TC3: MARKET NOT OPEN =====================

    public function test_throws_when_market_is_not_open(): void
    {
        $bet = $this->placeBet(100);
        $this->market->update(['status' => 'LOCKED']);

        $this->expectException(BetPlacementException::class);

        try {
            $this->service->cancelBet($bet, $this->user);
        } catch (BetPlacementException $e) {
            $this->assertEquals('MARKET_CLOSED', $e->getErrorCode());
            throw $e;
        }
    }

    // ===================== TC4: MARKET EXPIRED =====================

    public function test_throws_when_market_close_at_has_passed(): void
    {
        $bet = $this->placeBet(100);
        $this->market->update(['close_at' => now()->subMinute()]);

        $this->expectException(BetPlacementException::class);

        try {
            $this->service->cancelBet($bet, $this->user);
        } catch (BetPlacementException $e) {
            $this->assertEquals('MARKET_CLOSED', $e->getErrorCode());
            throw $e;
        }
    }

    // ===================== TC5: WRONG USER =====================

    public function test_throws_when_user_does_not_own_bet(): void
    {
        $bet       = $this->placeBet(100);
        $otherUser = User::factory()->create(['status' => 'ACTIVE']);

        $this->expectException(BetPlacementException::class);

        try {
            $this->service->cancelBet($bet, $otherUser);
        } catch (BetPlacementException $e) {
            $this->assertEquals('BET_NOT_OWNED', $e->getErrorCode());
            throw $e;
        }
    }

    // ===================== TC6: ALREADY VOIDED =====================

    public function test_throws_when_cancelling_an_already_voided_bet(): void
    {
        $bet = $this->placeBet(100);
        $bet->update(['status' => BetStatus::VOIDED]);

        $this->expectException(BetPlacementException::class);

        try {
            $this->service->cancelBet($bet, $this->user);
        } catch (BetPlacementException $e) {
            $this->assertEquals('BET_NOT_PENDING', $e->getErrorCode());
            throw $e;
        }
    }

    // ===================== TC7: WALLET BALANCES EXACT =====================

    public function test_wallet_balances_are_exact_after_cancel(): void
    {
        $bet = $this->placeBet(150);
        $this->wallet->refresh();

        $availableBefore = $this->wallet->available_balance; // 5000-150=4850
        $lockedBefore    = $this->wallet->locked_balance;    // 150

        $this->service->cancelBet($bet, $this->user);
        $this->wallet->refresh();

        $this->assertEquals($availableBefore + 150, $this->wallet->available_balance);
        $this->assertEquals($lockedBefore - 150, $this->wallet->locked_balance);
        $this->assertEquals(0, $this->wallet->locked_balance);
    }

    // ===================== TC8: METADATA VOID_REASON =====================

    public function test_cancelled_bet_has_void_reason_in_metadata(): void
    {
        $bet    = $this->placeBet(100);
        $result = $this->service->cancelBet($bet, $this->user);

        $this->assertEquals('CANCELLED_BY_USER', $result['bet']->metadata['void_reason']);
    }

    // ===================== TC8b: LEDGER BET_VOIDED CREATED =====================

    public function test_ledger_bet_voided_entry_is_created(): void
    {
        $bet = $this->placeBet(100);
        $this->service->cancelBet($bet, $this->user);

        $this->wallet->refresh();
        $ledger = $this->wallet->ledgers()
            ->where('type', LedgerType::BET_VOIDED->value)
            ->where('bet_id', $bet->id)
            ->first();

        $this->assertNotNull($ledger);
        $this->assertEquals(100, $ledger->amount_available);
        $this->assertEquals(-100, $ledger->amount_locked);
    }

    // ===================== TC9: MAX 20 CANCELS =====================

    public function test_throws_when_max_20_cancels_per_match_exceeded(): void
    {
        // Simulasikan 20 vé đã huỷ bằng CANCELLED_BY_USER
        // Mỗi vé voided_at cách nhau 2 phút (> 30s cooldown) và xảy ra từ lâu
        for ($i = 0; $i < 20; $i++) {
            $fakeBet = Bet::create([
                'public_code'              => 'DL-FAKE-' . str_pad($i, 5, '0', STR_PAD_LEFT),
                'user_id'                  => $this->user->id,
                'wallet_id'                => $this->wallet->id,
                'season_id'               => $this->season->id,
                'match_id'                => $this->match->id,
                'market_id'               => $this->market->id,
                'outcome_id'              => $this->outcome->id,
                'stake'                   => 10,
                'profit_rate_snapshot'    => '0.9000',
                'line_snapshot'           => '-0.50',
                'label_snapshot'          => 'Home -0.5',
                'display_odds_snapshot'   => 'Home -0.5 ăn 0.90',
                'close_at_snapshot'       => now()->addHour(),
                'market_type_snapshot'    => 'ASIAN_HANDICAP',
                'period_type_snapshot'    => 'FULL_TIME',
                'selection_side_snapshot' => 'HOME',
                'status'                  => BetStatus::VOIDED->value,
                'placed_at'               => now()->subHours(10),
                // Mỗi vé cách nhau 2 phút, vé gần nhất cách now() 61s (> 30s)
                'voided_at'               => now()->subSeconds(61 + $i * 120),
                'metadata'                => ['void_reason' => 'CANCELLED_BY_USER'],
            ]);
        }

        // Bây giờ đặt vé thật và thử huỷ lần thứ 21
        $realBet = $this->placeBet(10);

        $this->expectException(BetPlacementException::class);

        try {
            $this->service->cancelBet($realBet, $this->user);
        } catch (BetPlacementException $e) {
            $this->assertEquals('MAX_CANCELS_EXCEEDED', $e->getErrorCode());
            throw $e;
        }
    }

    // ===================== TC10: COOLDOWN 30s =====================

    public function test_throws_when_cancel_cooldown_not_elapsed(): void
    {
        // Simulasikan lần huỷ gần nhất là 10 giây trước
        Bet::create([
            'public_code'              => 'DL-COOL-00001',
            'user_id'                  => $this->user->id,
            'wallet_id'                => $this->wallet->id,
            'season_id'               => $this->season->id,
            'match_id'                => $this->match->id,
            'market_id'               => $this->market->id,
            'outcome_id'              => $this->outcome->id,
            'stake'                   => 10,
            'profit_rate_snapshot'    => '0.9000',
            'line_snapshot'           => '-0.50',
            'label_snapshot'          => 'Home -0.5',
            'display_odds_snapshot'   => 'Home -0.5 ăn 0.90',
            'close_at_snapshot'       => now()->addHour(),
            'market_type_snapshot'    => 'ASIAN_HANDICAP',
            'period_type_snapshot'    => 'FULL_TIME',
            'selection_side_snapshot' => 'HOME',
            'status'                  => BetStatus::VOIDED->value,
            'placed_at'               => now()->subMinutes(5),
            'voided_at'               => now()->subSeconds(10), // 10s trước < 30s cooldown
            'metadata'                => ['void_reason' => 'CANCELLED_BY_USER'],
        ]);

        $bet = $this->placeBet(100);

        $this->expectException(BetPlacementException::class);

        try {
            $this->service->cancelBet($bet, $this->user);
        } catch (BetPlacementException $e) {
            $this->assertEquals('CANCEL_COOLDOWN', $e->getErrorCode());
            $this->assertStringContainsString('giây', $e->getMessage());
            throw $e;
        }
    }

    // ===================== TC11: WARNING AT remaining=4 (lần thứ 16) =====================

    public function test_show_warning_true_when_remaining_is_4(): void
    {
        // Tạo 15 vé đã huỷ → lần này là lần thứ 16 → remaining = 4 → show_warning = true
        // voided_at đủ xa (> 30s) để không trigger cooldown
        for ($i = 0; $i < 15; $i++) {
            Bet::create([
                'public_code'              => 'DL-WARN-' . str_pad($i, 5, '0', STR_PAD_LEFT),
                'user_id'                  => $this->user->id,
                'wallet_id'                => $this->wallet->id,
                'season_id'               => $this->season->id,
                'match_id'                => $this->match->id,
                'market_id'               => $this->market->id,
                'outcome_id'              => $this->outcome->id,
                'stake'                   => 10,
                'profit_rate_snapshot'    => '0.9000',
                'line_snapshot'           => '-0.50',
                'label_snapshot'          => 'Home -0.5',
                'display_odds_snapshot'   => 'Home -0.5 ăn 0.90',
                'close_at_snapshot'       => now()->addHour(),
                'market_type_snapshot'    => 'ASIAN_HANDICAP',
                'period_type_snapshot'    => 'FULL_TIME',
                'selection_side_snapshot' => 'HOME',
                'status'                  => BetStatus::VOIDED->value,
                'placed_at'               => now()->subHours(10),
                // Gần nhất: 61s trước, các vé cũ hơn
                'voided_at'               => now()->subSeconds(61 + $i * 120),
                'metadata'                => ['void_reason' => 'CANCELLED_BY_USER'],
            ]);
        }

        $bet    = $this->placeBet(10);
        $result = $this->service->cancelBet($bet, $this->user);

        $this->assertEquals(4, $result['remaining']);
        $this->assertTrue($result['show_warning']);
    }

    // ===================== TC12: NO WARNING AT remaining=6 (lần thứ 14) =====================

    public function test_show_warning_false_when_remaining_is_6(): void
    {
        // Tạo 13 vé đã huỷ → lần này là lần thứ 14 → remaining = 6 → show_warning = false
        // voided_at đủ xa (> 30s) để không trigger cooldown
        for ($i = 0; $i < 13; $i++) {
            Bet::create([
                'public_code'              => 'DL-NOWRN-' . str_pad($i, 5, '0', STR_PAD_LEFT),
                'user_id'                  => $this->user->id,
                'wallet_id'                => $this->wallet->id,
                'season_id'               => $this->season->id,
                'match_id'                => $this->match->id,
                'market_id'               => $this->market->id,
                'outcome_id'              => $this->outcome->id,
                'stake'                   => 10,
                'profit_rate_snapshot'    => '0.9000',
                'line_snapshot'           => '-0.50',
                'label_snapshot'          => 'Home -0.5',
                'display_odds_snapshot'   => 'Home -0.5 ăn 0.90',
                'close_at_snapshot'       => now()->addHour(),
                'market_type_snapshot'    => 'ASIAN_HANDICAP',
                'period_type_snapshot'    => 'FULL_TIME',
                'selection_side_snapshot' => 'HOME',
                'status'                  => BetStatus::VOIDED->value,
                'placed_at'               => now()->subHours(10),
                // Gần nhất: 61s trước
                'voided_at'               => now()->subSeconds(61 + $i * 120),
                'metadata'                => ['void_reason' => 'CANCELLED_BY_USER'],
            ]);
        }

        $bet    = $this->placeBet(10);
        $result = $this->service->cancelBet($bet, $this->user);

        $this->assertEquals(6, $result['remaining']);
        $this->assertFalse($result['show_warning']);
    }
}
