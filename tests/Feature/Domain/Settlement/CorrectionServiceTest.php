<?php

namespace Tests\Feature\Domain\Settlement;

use App\Domain\Settlement\Data\MatchResult;
use App\Domain\Settlement\Services\CorrectionService;
use App\Domain\Settlement\Services\SettlementEngine;
use App\Enums\BetStatus;
use App\Enums\MarketStatus;
use App\Models\Bet;
use App\Models\FootballMatch;
use App\Models\Market;
use App\Models\MarketOutcome;
use App\Models\Season;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletLedger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CorrectionServiceTest extends TestCase
{
    use RefreshDatabase;

    private CorrectionService $correctionService;
    private SettlementEngine $settlementEngine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->correctionService = app(CorrectionService::class);
        $this->settlementEngine = app(SettlementEngine::class);
    }

    public function test_it_corrects_settlement_and_updates_wallets()
    {
        // Setup User & Season
        $user = User::factory()->create(['status' => 'ACTIVE']);
        $season = Season::factory()->create();

        // Setup Match
        $match = FootballMatch::create([
            'season_id' => $season->id,
            'match_code' => 'M001',
            'stage' => 'Group',
            'home_team' => 'A',
            'away_team' => 'B',
            'kickoff_at' => now()->subHours(2),
            'timezone' => 'UTC',
            'status' => 'FINISHED',
        ]);

        // Setup Market
        $market = Market::create([
            'match_id' => $match->id,
            'period_type' => 'FULL_TIME',
            'market_type' => 'EXACT_SCORE',
            'name' => 'EXACT_SCORE',
            'open_at' => now()->subHours(3),
            'close_at' => now()->subHours(2),
            'status' => 'LOCKED',
            'locked_at' => now()->subHours(2),
            'display_order' => 1,
        ]);

        $outcome = MarketOutcome::create([
            'market_id' => $market->id,
            'label' => '1-0',
            'score_home' => 1,
            'score_away' => 0,
            'profit_rate' => '7.00',
            'selection_side' => 'HOME',
            'status' => 'ACTIVE',
            'display_order' => 1,
        ]);
        
        $wallet = Wallet::create([
            'user_id' => $user->id,
            'season_id' => $season->id,
            'available_balance' => 1000,
            'locked_balance' => 0,
            'total_staked' => 0,
            'total_payout' => 0,
            'net_profit' => 0,
            'status' => 'ACTIVE',
        ]);

        $bet = Bet::create([
            'public_code' => 'BET-1',
            'market_id' => $market->id,
            'outcome_id' => $outcome->id,
            'wallet_id' => $wallet->id,
            'user_id' => $user->id,
            'season_id' => $season->id,
            'match_id' => $match->id,
            'stake' => 100,
            'profit_rate_snapshot' => 7.00,
            'line_snapshot' => '1-0',
            'label_snapshot' => '1-0',
            'display_odds_snapshot' => '1-0 ăn 7.00',
            'close_at_snapshot' => now()->subHours(2),
            'market_type_snapshot' => 'EXACT_SCORE',
            'period_type_snapshot' => 'FULL_TIME',
            'selection_side_snapshot' => 'HOME',
            'status' => BetStatus::PENDING->value,
            'placed_at' => now()->subHour(),
        ]);

        // Simulate Wallet lock
        $wallet->available_balance -= 100;
        $wallet->locked_balance += 100;
        $wallet->total_staked += 100;
        $wallet->save();

        // 2. Settle lần 1: Kết quả sai (0-0) -> Bet thua
        $wrongResult = new MatchResult(0, 0);
        $settlement = $this->settlementEngine->execute($market, $wrongResult);

        $wallet->refresh();
        $bet->refresh();

        $this->assertEquals(BetStatus::LOST, $bet->status);
        $this->assertEquals(0, $bet->gross_payout);
        $this->assertEquals(900, $wallet->available_balance); // Còn 900
        $this->assertEquals(0, $wallet->locked_balance);

        // 3. Thực thi Correction -> Kết quả đúng (1-0) -> Bet thắng
        $correction = $this->correctionService->createCorrection(
            $settlement->id,
            ['home_score' => 1, 'away_score' => 0],
            'Nhập nhầm tỉ số',
            $user
        );

        $this->correctionService->executeCorrection($correction, $user);

        // 4. Assertions
        $wallet->refresh();
        $bet->refresh();
        $correction->refresh();
        $settlement->refresh();

        $this->assertEquals('EXECUTED', $correction->status);
        $this->assertEquals(1, $settlement->result_home_score);
        $this->assertEquals(0, $settlement->result_away_score);

        $this->assertEquals(BetStatus::CORRECTED, $bet->status);
        $this->assertEquals(800, $bet->gross_payout);

        $this->assertEquals(1700, $wallet->available_balance); // 900 + 800

        $this->assertTrue(WalletLedger::where('wallet_id', $wallet->id)
            ->where('type', 'SETTLEMENT_CORRECTION')
            ->where('amount_available', 800)
            ->exists());
    }
}
