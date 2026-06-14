<?php

namespace Tests\Feature\Domain\Settlement;

use App\Domain\Settlement\Calculators\AsianHandicapSettlementCalculator;
use App\Domain\Settlement\Calculators\ExactScoreSettlementCalculator;
use App\Domain\Settlement\Calculators\OverUnderSettlementCalculator;
use App\Domain\Settlement\Data\MatchResult;
use App\Domain\Settlement\Exceptions\SettlementException;
use App\Domain\Settlement\Services\SettlementEngine;
use App\Domain\Wallet\Services\WalletService;
use App\Enums\BetStatus;
use App\Models\Bet;
use App\Models\FootballMatch;
use App\Models\Market;
use App\Models\MarketOutcome;
use App\Models\Season;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettlementEngineTest extends TestCase
{
    use RefreshDatabase;

    private SettlementEngine $engine;

    private WalletService $walletService;

    private ExactScoreSettlementCalculator $exactCalc;

    private AsianHandicapSettlementCalculator $ahCalc;

    private OverUnderSettlementCalculator $ouCalc;

    private User $user;

    private Season $season;

    private Wallet $wallet;

    private FootballMatch $match;

    protected function setUp(): void
    {
        parent::setUp();

        $this->engine = app(SettlementEngine::class);
        $this->walletService = app(WalletService::class);
        $this->exactCalc = app(ExactScoreSettlementCalculator::class);
        $this->ahCalc = app(AsianHandicapSettlementCalculator::class);
        $this->ouCalc = app(OverUnderSettlementCalculator::class);

        $this->user = User::factory()->create(['status' => 'ACTIVE']);
        $this->season = Season::factory()->create();

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

        $this->walletService->grant($this->wallet, 10000, null);
        $this->wallet->refresh();

        $this->match = FootballMatch::create([
            'season_id' => $this->season->id,
            'match_code' => 'M001',
            'stage' => 'Group',
            'home_team' => 'Brazil',
            'away_team' => 'Germany',
            'kickoff_at' => now()->subHours(2),
            'timezone' => 'Asia/Ho_Chi_Minh',
            'status' => 'FINISHED',
        ]);
    }

    // ===================== HELPERS =====================

    private function makeMarket(string $type, string $status = 'LOCKED'): Market
    {
        return Market::create([
            'match_id' => $this->match->id,
            'period_type' => 'FULL_TIME',
            'market_type' => $type,
            'name' => $type,
            'open_at' => now()->subHours(3),
            'close_at' => now()->subHours(2),
            'status' => $status,
            'locked_at' => now()->subHours(2),
            'display_order' => 1,
        ]);
    }

    private function makeOutcome(Market $market, array $attrs): MarketOutcome
    {
        return MarketOutcome::create(array_merge([
            'market_id' => $market->id,
            'status' => 'ACTIVE',
            'display_order' => 1,
        ], $attrs));
    }

    private function makePendingBet(Market $market, MarketOutcome $outcome, int $stake, array $extra = []): Bet
    {
        $this->walletService->grant($this->wallet, $stake, null);
        $this->wallet->refresh();

        $bet = Bet::create(array_merge([
            'public_code' => 'BET-'.uniqid(),
            'user_id' => $this->user->id,
            'wallet_id' => $this->wallet->id,
            'season_id' => $this->season->id,
            'match_id' => $this->match->id,
            'market_id' => $market->id,
            'outcome_id' => $outcome->id,
            'stake' => $stake,
            'profit_rate_snapshot' => $outcome->profit_rate,
            'line_snapshot' => $outcome->line_value,
            'label_snapshot' => $outcome->label,
            'display_odds_snapshot' => "{$outcome->label} ăn {$outcome->profit_rate}",
            'close_at_snapshot' => now()->subHours(2),
            'market_type_snapshot' => $market->market_type,
            'period_type_snapshot' => $market->period_type,
            'selection_side_snapshot' => $outcome->selection_side,
            'status' => 'PENDING',
            'placed_at' => now()->subHour(),
        ], $extra));

        $this->walletService->lockStake($this->wallet, $stake, $bet);
        $this->wallet->refresh();

        return $bet;
    }

    // ===================== EXACT SCORE =====================

    public function test_exact_score_win(): void
    {
        $market = $this->makeMarket('EXACT_SCORE');
        $outcome = $this->makeOutcome($market, [
            'label' => '2-1', 'score_home' => 2, 'score_away' => 1,
            'profit_rate' => '7.00', 'selection_side' => 'HOME',
        ]);

        $bet = $this->makePendingBet($market, $outcome, 100);
        $result = $this->exactCalc->calculate($bet, new MatchResult(2, 1));

        $this->assertEquals(BetStatus::WON, $result->status);
        $this->assertEquals(800, $result->grossPayout); // 100 × (1 + 7.00) = 800
        $this->assertEquals(700, $result->netResult);
    }

    public function test_exact_score_lose(): void
    {
        $market = $this->makeMarket('EXACT_SCORE');
        $outcome = $this->makeOutcome($market, [
            'label' => '2-1', 'score_home' => 2, 'score_away' => 1,
            'profit_rate' => '7.00', 'selection_side' => 'HOME',
        ]);

        $bet = $this->makePendingBet($market, $outcome, 100);
        $result = $this->exactCalc->calculate($bet, new MatchResult(1, 0));

        $this->assertEquals(BetStatus::LOST, $result->status);
        $this->assertEquals(0, $result->grossPayout);
        $this->assertEquals(-100, $result->netResult);
    }

    // ===================== ASIAN HANDICAP =====================

    /** AH 0 (Level): home win → HOME WIN, away win → HOME LOSE, draw → PUSH */
    public function test_ah_level_home_wins(): void
    {
        $market = $this->makeMarket('ASIAN_HANDICAP');
        $outcome = $this->makeOutcome($market, [
            'label' => 'Home 0', 'line_value' => '0.00',
            'profit_rate' => '0.90', 'selection_side' => 'HOME',
        ]);
        $bet = $this->makePendingBet($market, $outcome, 100);
        $result = $this->ahCalc->calculate($bet, new MatchResult(2, 1));

        $this->assertEquals(BetStatus::WON, $result->status);
        $this->assertEquals(190, $result->grossPayout);
    }

    public function test_ah_level_draw_is_push(): void
    {
        $market = $this->makeMarket('ASIAN_HANDICAP');
        $outcome = $this->makeOutcome($market, [
            'label' => 'Home 0', 'line_value' => '0.00',
            'profit_rate' => '0.90', 'selection_side' => 'HOME',
        ]);
        $bet = $this->makePendingBet($market, $outcome, 100);
        $result = $this->ahCalc->calculate($bet, new MatchResult(1, 1));

        $this->assertEquals(BetStatus::PUSH, $result->status);
        $this->assertEquals(100, $result->grossPayout);
    }

    /** AH -0.5: nhà thắng → WIN, nhà hòa/thua → LOSE */
    public function test_ah_minus_half_win(): void
    {
        $market = $this->makeMarket('ASIAN_HANDICAP');
        $outcome = $this->makeOutcome($market, [
            'label' => 'Home -0.5', 'line_value' => '-0.50',
            'profit_rate' => '0.90', 'selection_side' => 'HOME',
        ]);
        $bet = $this->makePendingBet($market, $outcome, 100);
        $result = $this->ahCalc->calculate($bet, new MatchResult(1, 0));

        $this->assertEquals(BetStatus::WON, $result->status);
        $this->assertEquals(190, $result->grossPayout);
    }

    public function test_ah_minus_half_draw_lose(): void
    {
        $market = $this->makeMarket('ASIAN_HANDICAP');
        $outcome = $this->makeOutcome($market, [
            'label' => 'Home -0.5', 'line_value' => '-0.50',
            'profit_rate' => '0.90', 'selection_side' => 'HOME',
        ]);
        $bet = $this->makePendingBet($market, $outcome, 100);
        $result = $this->ahCalc->calculate($bet, new MatchResult(1, 1));

        $this->assertEquals(BetStatus::LOST, $result->status);
        $this->assertEquals(0, $result->grossPayout);
    }

    /** AH -0.25 (Quarter): thắng 1+ → FULL WIN; hòa → HALF LOSE */
    public function test_ah_minus_quarter_home_wins_is_full_win(): void
    {
        $market = $this->makeMarket('ASIAN_HANDICAP');
        $outcome = $this->makeOutcome($market, [
            'label' => 'Home -0.25', 'line_value' => '-0.25',
            'profit_rate' => '0.90', 'selection_side' => 'HOME',
        ]);
        $bet = $this->makePendingBet($market, $outcome, 100);
        $result = $this->ahCalc->calculate($bet, new MatchResult(2, 0));

        // Split: [0, -0.5]: cả 2 WIN
        $this->assertEquals(BetStatus::WON, $result->status);
        $this->assertEquals(190, $result->grossPayout);
    }

    public function test_ah_minus_quarter_draw_is_half_lose(): void
    {
        $market = $this->makeMarket('ASIAN_HANDICAP');
        $outcome = $this->makeOutcome($market, [
            'label' => 'Home -0.25', 'line_value' => '-0.25',
            'profit_rate' => '0.90', 'selection_side' => 'HOME',
        ]);
        $bet = $this->makePendingBet($market, $outcome, 100);
        $result = $this->ahCalc->calculate($bet, new MatchResult(1, 1));

        // Split: line 0 = PUSH (50 lá), line -0.5 = LOSE (0 lá) → HALF_LOST
        $this->assertEquals(BetStatus::HALF_LOST, $result->status);
        $this->assertEquals(50, $result->grossPayout);
    }

    /** AH -0.75: thắng 2+ → FULL WIN; thắng 1 → HALF WIN */
    public function test_ah_minus_three_quarter_win_by_1_is_half_win(): void
    {
        $market = $this->makeMarket('ASIAN_HANDICAP');
        $outcome = $this->makeOutcome($market, [
            'label' => 'Home -0.75', 'line_value' => '-0.75',
            'profit_rate' => '0.90', 'selection_side' => 'HOME',
        ]);
        $bet = $this->makePendingBet($market, $outcome, 100);
        $result = $this->ahCalc->calculate($bet, new MatchResult(2, 1)); // diff = 1

        // Split: [-0.5, -1]: line -0.5 = WIN (50×1.90=95), line -1 = PUSH (50) → HALF_WON
        $this->assertEquals(BetStatus::HALF_WON, $result->status);
        $this->assertEquals(145, $result->grossPayout); // 95 + 50
    }

    public function test_ah_minus_three_quarter_win_by_2_is_full_win(): void
    {
        $market = $this->makeMarket('ASIAN_HANDICAP');
        $outcome = $this->makeOutcome($market, [
            'label' => 'Home -0.75', 'line_value' => '-0.75',
            'profit_rate' => '0.90', 'selection_side' => 'HOME',
        ]);
        $bet = $this->makePendingBet($market, $outcome, 100);
        $result = $this->ahCalc->calculate($bet, new MatchResult(3, 1)); // diff = 2

        // Split: [-0.5, -1]: cả 2 WIN → 190
        $this->assertEquals(BetStatus::WON, $result->status);
        $this->assertEquals(190, $result->grossPayout);
    }

    // ===================== OVER/UNDER =====================

    /** O/U 2.0 (full line) */
    public function test_ou_full_line_over_win(): void
    {
        $market = $this->makeMarket('OVER_UNDER');
        $outcome = $this->makeOutcome($market, [
            'label' => 'Tài 2.0', 'line_value' => '2.00',
            'profit_rate' => '0.90', 'selection_side' => 'OVER',
        ]);
        $bet = $this->makePendingBet($market, $outcome, 100);
        $result = $this->ouCalc->calculate($bet, new MatchResult(2, 1)); // 3 goals

        $this->assertEquals(BetStatus::WON, $result->status);
        $this->assertEquals(190, $result->grossPayout);
    }

    public function test_ou_full_line_push_on_exact_match(): void
    {
        $market = $this->makeMarket('OVER_UNDER');
        $outcome = $this->makeOutcome($market, [
            'label' => 'Tài 2.0', 'line_value' => '2.00',
            'profit_rate' => '0.90', 'selection_side' => 'OVER',
        ]);
        $bet = $this->makePendingBet($market, $outcome, 100);
        $result = $this->ouCalc->calculate($bet, new MatchResult(1, 1)); // 2 goals = line

        $this->assertEquals(BetStatus::PUSH, $result->status);
        $this->assertEquals(100, $result->grossPayout);
    }

    /** O/U 2.25 (quarter line) — từ ví dụ AGENTS.md */
    public function test_ou_quarter_line_2_25_over_goals_2_is_half_lose(): void
    {
        $market = $this->makeMarket('OVER_UNDER');
        $outcome = $this->makeOutcome($market, [
            'label' => 'Tài 2.25', 'line_value' => '2.25',
            'profit_rate' => '0.90', 'selection_side' => 'OVER',
        ]);
        $bet = $this->makePendingBet($market, $outcome, 100);
        $result = $this->ouCalc->calculate($bet, new MatchResult(1, 1)); // 2 goals

        // Over 2.0 = PUSH (50), Over 2.5 = LOSE (0) → HALF_LOST, payout = 50
        $this->assertEquals(BetStatus::HALF_LOST, $result->status);
        $this->assertEquals(50, $result->grossPayout);
    }

    public function test_ou_quarter_line_2_25_under_goals_2_is_half_win(): void
    {
        $market = $this->makeMarket('OVER_UNDER');
        $outcome = $this->makeOutcome($market, [
            'label' => 'Xỉu 2.25', 'line_value' => '2.25',
            'profit_rate' => '0.90', 'selection_side' => 'UNDER',
        ]);
        $bet = $this->makePendingBet($market, $outcome, 100);
        $result = $this->ouCalc->calculate($bet, new MatchResult(1, 1)); // 2 goals

        // Under 2.0 = PUSH (50), Under 2.5 = WIN (50×1.90=95) → HALF_WON, payout = 145
        $this->assertEquals(BetStatus::HALF_WON, $result->status);
        $this->assertEquals(145, $result->grossPayout);
    }

    public function test_ou_quarter_line_2_75_over_goals_3_is_half_win(): void
    {
        $market = $this->makeMarket('OVER_UNDER');
        $outcome = $this->makeOutcome($market, [
            'label' => 'Tài 2.75', 'line_value' => '2.75',
            'profit_rate' => '0.90', 'selection_side' => 'OVER',
        ]);
        $bet = $this->makePendingBet($market, $outcome, 100);
        $result = $this->ouCalc->calculate($bet, new MatchResult(2, 1)); // 3 goals

        // Over 2.5 = WIN (50×1.90=95), Over 3.0 = PUSH (50) → HALF_WON, payout = 145
        $this->assertEquals(BetStatus::HALF_WON, $result->status);
        $this->assertEquals(145, $result->grossPayout);
    }

    // ===================== SETTLEMENT ENGINE EXECUTE =====================

    public function test_engine_executes_and_updates_wallet(): void
    {
        $market = $this->makeMarket('ASIAN_HANDICAP');
        $outcome = $this->makeOutcome($market, [
            'label' => 'Home -0.5', 'line_value' => '-0.50',
            'profit_rate' => '0.90', 'selection_side' => 'HOME',
        ]);

        $bet = $this->makePendingBet($market, $outcome, 100);
        $balanceBefore = $this->wallet->available_balance;
        $lockedBefore = $this->wallet->locked_balance;

        // Brazil 2-0 Germany → Home -0.5 WIN
        $settlement = $this->engine->execute($market, new MatchResult(2, 0));

        $this->assertEquals('EXECUTED', $settlement->status);
        $this->assertEquals(1, $settlement->total_bets);
        $this->assertEquals(100, $settlement->total_stake);
        $this->assertEquals(190, $settlement->total_payout);

        // Market settle
        $market->refresh();
        $this->assertEquals('SETTLED', $market->status);

        // Bet update
        $bet->refresh();
        $this->assertEquals('WON', $bet->status->value);
        $this->assertEquals(190, $bet->gross_payout);
        $this->assertEquals(90, $bet->net_result);

        // Wallet: 0 locked, payout 190 về available
        $this->wallet->refresh();
        $this->assertEquals(0, $this->wallet->locked_balance);
        $this->assertEquals($balanceBefore + 190, $this->wallet->available_balance);

        // SettlementItem
        $item = $settlement->items()->first();
        $this->assertNotNull($item);
        $this->assertEquals('WON', $item->result_status);
        $this->assertEquals(190, $item->gross_payout);
    }

    public function test_engine_is_idempotent_throws_on_second_execute(): void
    {
        $market = $this->makeMarket('OVER_UNDER');
        $outcome = $this->makeOutcome($market, [
            'label' => 'Tài 2.5', 'line_value' => '2.50',
            'profit_rate' => '0.90', 'selection_side' => 'OVER',
        ]);
        $this->makePendingBet($market, $outcome, 100);

        $this->engine->execute($market, new MatchResult(2, 1));

        $market->refresh();
        $this->expectException(SettlementException::class);

        // Market đã SETTLED, phải throw
        $this->engine->execute($market, new MatchResult(2, 1));
    }

    public function test_engine_throws_when_market_not_locked(): void
    {
        $market = $this->makeMarket('OVER_UNDER', 'OPEN'); // Sai — phải LOCKED

        $this->expectException(SettlementException::class);

        $this->engine->execute($market, new MatchResult(1, 1));
    }

    public function test_integration_place_bet_then_settle_ledger_correct(): void
    {
        // Full flow: grant → place → lock → settle → kiểm tra ledger
        $market = $this->makeMarket('EXACT_SCORE');
        $outcome = $this->makeOutcome($market, [
            'label' => '1-0', 'score_home' => 1, 'score_away' => 0,
            'profit_rate' => '7.00', 'selection_side' => 'HOME',
        ]);

        $bet = $this->makePendingBet($market, $outcome, 100);

        // Before settle: locked = 100
        $this->wallet->refresh();
        $this->assertEquals(100, $this->wallet->locked_balance);

        $this->engine->execute($market, new MatchResult(1, 0)); // Exact score match!

        $this->wallet->refresh();
        $this->assertEquals(0, $this->wallet->locked_balance);
        // grant 10000 + grant 100 (makePendingBet) - stake 100 (locked) + payout 800 = 10800
        $this->assertEquals(10800, $this->wallet->available_balance);

        // Tổng ledgers: ADMIN_GRANT x2 + BET_PLACED + BET_WON
        $ledgers = $this->wallet->ledgers()->orderBy('id')->get();
        $types = $ledgers->pluck('type')->map(fn ($t) => is_string($t) ? $t : $t->value)->toArray();
        $this->assertContains('ADMIN_GRANT', $types);
        $this->assertContains('BET_PLACED', $types);
        $this->assertContains('BET_WON', $types);
    }
}
