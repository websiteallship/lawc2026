<?php

namespace Tests\Feature;

use App\Domain\Betting\Data\PlaceBetInput;
use App\Domain\Betting\Exceptions\BetPlacementException;
use App\Domain\Betting\Services\BetPlacementService;
use App\Domain\Market\Services\MarketLockService;
use App\Domain\Settlement\Data\MatchResult;
use App\Domain\Settlement\Services\SettlementEngine;
use App\Domain\Wallet\Services\WalletService;
use App\Domain\Leaderboard\Services\LeaderboardService;
use App\Enums\LedgerType;
use App\Enums\MarketStatus;
use App\Models\Bet;
use App\Models\FootballMatch;
use App\Models\Market;
use App\Models\MarketOutcome;
use App\Models\Season;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UatChecklistTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Season $season;
    private Wallet $wallet;
    private WalletService $walletService;
    private BetPlacementService $betService;
    private SettlementEngine $settlementEngine;
    private LeaderboardService $leaderboardService;
    private MarketLockService $marketLockService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->walletService = app(WalletService::class);
        $this->betService = app(BetPlacementService::class);
        $this->settlementEngine = app(SettlementEngine::class);
        $this->leaderboardService = app(LeaderboardService::class);
        $this->marketLockService = app(MarketLockService::class);

        $this->season = Season::factory()->create();
        $this->user = User::factory()->create(['status' => 'ACTIVE']);
        
        $this->wallet = Wallet::create([
            'user_id' => $this->user->id,
            'season_id' => $this->season->id,
            'available_balance' => 0,
            'locked_balance' => 0,
            'status' => 'ACTIVE',
        ]);
        
        $this->walletService->grant($this->wallet, 1000, null, 'Test setup');
        $this->wallet->refresh();
    }

    private function createMatchAndMarket(string $marketType = 'ASIAN_HANDICAP', string $period = 'FULL_TIME'): Market
    {
        $match = FootballMatch::create([
            'season_id' => $this->season->id,
            'match_code' => 'M' . uniqid(),
            'stage' => 'Group',
            'home_team' => 'Home',
            'away_team' => 'Away',
            'kickoff_at' => now()->addDay(),
            'timezone' => 'Asia/Ho_Chi_Minh',
            'status' => 'SCHEDULED',
        ]);

        return Market::create([
            'match_id' => $match->id,
            'period_type' => $period,
            'market_type' => $marketType,
            'name' => 'Market ' . $marketType,
            'open_at' => now()->subHour(),
            'close_at' => now()->addHour(),
            'status' => 'OPEN',
        ]);
    }
    
    private function createOutcome(Market $market, string $label, ?string $line, string $profit, ?int $scoreHome = null, ?int $scoreAway = null): MarketOutcome
    {
        $side = 'HOME';
        if (str_contains($label, 'Away')) $side = 'AWAY';
        if (str_contains($label, 'Tài') || str_contains($label, 'OVER')) $side = 'OVER';
        if (str_contains($label, 'Xỉu') || str_contains($label, 'UNDER')) $side = 'UNDER';
        if (str_contains($label, '-')) $side = 'EXACT';
        
        return MarketOutcome::create([
            'market_id' => $market->id,
            'label' => $label,
            'selection_side' => $side,
            'line_value' => $line,
            'profit_rate' => $profit,
            'status' => 'ACTIVE',
            'score_home' => $scoreHome,
            'score_away' => $scoreAway,
        ]);
    }

    public function test_tc1_luong_co_ban()
    {
        $market = $this->createMatchAndMarket();
        $outcome = $this->createOutcome($market, 'Home -0.5', '-0.50', '0.9000');
        
        // 1. User đặt
        $bet = $this->betService->placeBet(new PlaceBetInput($this->user, $this->wallet, $market, $outcome, 100));
        $this->assertEquals('PENDING', $bet->status->value);
        $this->assertEquals(900, $this->wallet->fresh()->available_balance);
        $this->assertEquals(100, $this->wallet->fresh()->locked_balance);

        // 2. Chờ hết hạn (Giả lập Scheduler)
        $market->update(['close_at' => now()->subMinute()]);
        $this->marketLockService->lockExpiredMarkets();
        $this->assertEquals('LOCKED', $market->fresh()->status);

        // 3. Admin nhập kết quả -> 4 & 5. Admin confirm settle
        $market->refresh();
        $this->settlementEngine->execute($market, new MatchResult(2, 0), $this->user, 'UAT');

        // Bet updated
        $bet->refresh();
        $this->assertEquals('WON', $bet->status->value);
        $this->assertEquals(190, $bet->gross_payout);

        // Wallet updated
        $this->assertEquals(1090, $this->wallet->fresh()->available_balance);
        
        // 6. Leaderboard cập nhật
        $this->leaderboardService->snapshot($this->season);
        $rank = \Illuminate\Support\Facades\DB::table('leaderboard_snapshots')
            ->where('season_id', $this->season->id)
            ->where('user_id', $this->user->id)
            ->first();
        $this->assertNotNull($rank);
        $this->assertEquals(90, $rank->net_profit); // Thắng 90
    }

    public function test_tc2_void_market()
    {
        $this->markTestIncomplete('Void market and refund bets logic needs to be implemented or mapped to existing service.');
        // $market = $this->createMatchAndMarket();
        // $outcome = $this->createOutcome($market, 'Home -0.5', '-0.50', '0.9000');
        // $bet = $this->betService->placeBet(new PlaceBetInput($this->user, $this->wallet, $market, $outcome, 100));

        // // 1. Void market
        // $this->marketLockService->transition($market, MarketStatus::VOIDED);
        
        // // 2. Admin void bets PENDING
        // // Logic for refunding bets when market is voided.
        
        // $bet->refresh();
        // $this->assertEquals('VOIDED', $bet->status->value);
        
        // $this->assertEquals(1000, $this->wallet->fresh()->available_balance);
        // $this->assertEquals(0, $this->wallet->fresh()->locked_balance);

        // $ledger = $this->wallet->ledgers()->where('type', LedgerType::BET_VOIDED->value)->first();
        // $this->assertNotNull($ledger);
    }

    public function test_tc3_dong_thoi_sat_gio_dong()
    {
        $market = $this->createMatchAndMarket();
        $market->update(['close_at' => now()->addSeconds(30)]);
        $outcome = $this->createOutcome($market, 'Home -0.5', '-0.50', '0.9000');
        
        // 1. User đặt thành công 1 phút trước khi close
        $bet1 = $this->betService->placeBet(new PlaceBetInput($this->user, $this->wallet, $market, $outcome, 10));
        $this->assertEquals('PENDING', $bet1->status->value);

        // 2. Scheduler lock market
        $market->update(['close_at' => now()->subSecond()]);
        $this->marketLockService->lockExpiredMarkets();
        $this->assertEquals('LOCKED', $market->fresh()->status);

        // 3. User thử đặt sau close_at
        try {
            $this->betService->placeBet(new PlaceBetInput($this->user, $this->wallet, $market, $outcome, 10));
            $this->fail('Expected exception for market locked');
        } catch (BetPlacementException $e) {
            $this->assertEquals('MARKET_NOT_OPEN', $e->getErrorCode());
        }
    }

    public function test_tc4_stake_limit()
    {
        $market = $this->createMatchAndMarket();
        $outcome = $this->createOutcome($market, 'Home -0.5', '-0.50', '0.9000');
        
        // Min stake (< 10)
        try {
            $this->betService->placeBet(new PlaceBetInput($this->user, $this->wallet, $market, $outcome, 5));
            $this->fail('Expected exception for min stake');
        } catch (BetPlacementException $e) {
            $this->assertEquals('STAKE_TOO_LOW', $e->getErrorCode());
        }

        // Max per bet (> 200)
        try {
            $this->betService->placeBet(new PlaceBetInput($this->user, $this->wallet, $market, $outcome, 300));
            $this->fail('Expected exception for max per bet');
        } catch (BetPlacementException $e) {
            $this->assertEquals('STAKE_EXCEEDS_MAX_PER_BET', $e->getErrorCode());
        }

        // Total per match > 500
        $this->betService->placeBet(new PlaceBetInput($this->user, $this->wallet->fresh(), $market, $outcome, 200));
        $this->betService->placeBet(new PlaceBetInput($this->user, $this->wallet->fresh(), $market, $outcome, 200));
        try {
            $this->betService->placeBet(new PlaceBetInput($this->user, $this->wallet->fresh(), $market, $outcome, 200)); // tổng 600
            $this->fail('Expected exception for max per match');
        } catch (BetPlacementException $e) {
            $this->assertEquals('STAKE_EXCEEDS_MAX_PER_MATCH', $e->getErrorCode());
        }

        // Insufficient balance
        // Tru het tien
        $currentBalance = $this->wallet->fresh()->available_balance;
        if ($currentBalance > 0) {
            $this->walletService->deduct($this->wallet->fresh(), $currentBalance, null, 'deduct');
        }
        
        $newMarket = $this->createMatchAndMarket();
        $newOut = $this->createOutcome($newMarket, 'Home -0.5', '-0.50', '0.9000');

        try {
            $this->betService->placeBet(new PlaceBetInput($this->user, $this->wallet->fresh(), $newMarket, $newOut, 100));
            $this->fail('Expected exception for balance');
        } catch (BetPlacementException $e) {
            $this->assertEquals('INSUFFICIENT_BALANCE', $e->getErrorCode());
        }
    }

    public function test_tc5_settlement_correctness()
    {
        // 1. EXACT_SCORE 2-1 | 2-1 | WON
        $market1 = $this->createMatchAndMarket('EXACT_SCORE');
        $out1 = $this->createOutcome($market1, 'EXACT 2-1', null, '7.0000', 2, 1);
        $out1->update(['selection_side' => 'EXACT']);
        $bet1 = $this->betService->placeBet(new PlaceBetInput($this->user, $this->wallet->fresh(), $market1, $out1, 100));
        
        // 2. AH Home -0.25 | 1-1 | HALF_LOST
        $market2 = $this->createMatchAndMarket('ASIAN_HANDICAP');
        $out2 = $this->createOutcome($market2, 'Home -0.25', '-0.25', '0.9000');
        $out2->update(['selection_side' => 'HOME']);
        $bet2 = $this->betService->placeBet(new PlaceBetInput($this->user, $this->wallet->fresh(), $market2, $out2, 100));

        // 3. O/U Tai 2.25 | 2 goals | HALF_LOST
        $market3 = $this->createMatchAndMarket('OVER_UNDER');
        $out3 = $this->createOutcome($market3, 'Tài 2.25', '2.25', '0.9000');
        $out3->update(['selection_side' => 'OVER']);
        $bet3 = $this->betService->placeBet(new PlaceBetInput($this->user, $this->wallet->fresh(), $market3, $out3, 100));

        // 4. O/U Xiu 2.25 | 2 goals | HALF_WON
        $market4 = $this->createMatchAndMarket('OVER_UNDER');
        $out4 = $this->createOutcome($market4, 'Xỉu 2.25', '2.25', '0.9000');
        $out4->update(['selection_side' => 'UNDER']);
        $bet4 = $this->betService->placeBet(new PlaceBetInput($this->user, $this->wallet->fresh(), $market4, $out4, 100));

        // Set results and execute
        $market1->update(['status' => 'LOCKED']);
        $this->settlementEngine->execute($market1, new MatchResult(2, 1));

        $market2->update(['status' => 'LOCKED']);
        $this->settlementEngine->execute($market2, new MatchResult(1, 1));

        $market3->update(['status' => 'LOCKED']);
        $this->settlementEngine->execute($market3, new MatchResult(2, 0)); // total 2 goals

        $market4->update(['status' => 'LOCKED']);
        $this->settlementEngine->execute($market4, new MatchResult(1, 1)); // total 2 goals

        $this->assertEquals('WON', $bet1->fresh()->status->value);
        $this->assertEquals(800, $bet1->fresh()->gross_payout); // 100 + 100*7 = 800

        $this->assertEquals('HALF_LOST', $bet2->fresh()->status->value);
        $this->assertEquals(50, $bet2->fresh()->gross_payout);

        $this->assertEquals('HALF_LOST', $bet3->fresh()->status->value);
        $this->assertEquals(50, $bet3->fresh()->gross_payout);

        $this->assertEquals('HALF_WON', $bet4->fresh()->status->value);
        $this->assertEquals(145, $bet4->fresh()->gross_payout); // 50 + 50*0.9 = 95 payout + 50 original stake = 145
    }

    public function test_tc6_correction()
    {
        $this->markTestIncomplete('Correction Module is currently not implemented yet in the system core logic.');
    }
}
