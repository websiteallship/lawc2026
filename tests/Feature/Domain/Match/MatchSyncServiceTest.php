<?php

namespace Tests\Feature\Domain\Match;

use App\Domain\Match\Services\MatchSyncService;
use App\Enums\BetStatus;
use App\Enums\MarketStatus;
use App\Models\Bet;
use App\Models\FootballMatch;
use App\Models\Market;
use App\Models\MarketOutcome;
use App\Models\MatchPeriodResult;
use App\Models\Season;
use App\Models\User;
use App\Models\Wallet;
use App\Settings\ApiSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MatchSyncServiceTest extends TestCase
{
    use RefreshDatabase;

    private MatchSyncService $service;

    private Season $season;

    private ApiSettings $settings;

    protected function setUp(): void
    {
        parent::setUp();

        $this->settings = app(ApiSettings::class);
        $this->settings->football_data_api_token = 'test-token';
        $this->settings->competition_id = '2000';
        $this->settings->is_auto_sync_enabled = true;
        $this->settings->live_score_provider = 'football_data';
        $this->settings->save();

        $this->service = app(MatchSyncService::class);
        $this->season = Season::factory()->create(['code' => 'WC2026']);
    }

    public function test_sync_schedules_maps_existing_matches_by_name_and_time(): void
    {
        // 1. Tạo trận đấu có sẵn trong DB (Chưa có api_id)
        $dbMatch = FootballMatch::create([
            'season_id' => $this->season->id,
            'match_code' => 'M001',
            'stage' => 'Group',
            'home_team' => 'Brazil',
            'away_team' => 'Morocco',
            'kickoff_at' => '2026-06-12 18:00:00', // Giờ VN
            'timezone' => 'Asia/Ho_Chi_Minh',
            'status' => 'SCHEDULED',
        ]);

        // 2. Giả lập API trả về trận này
        // Giờ API là UTC: 2026-06-12T11:00:00Z -> Ho Chi Minh là 18:00:00
        Http::fake([
            'https://api.football-data.org/v4/competitions/2000/matches*' => Http::response([
                'matches' => [
                    [
                        'id' => 12345,
                        'utcDate' => '2026-06-12T11:00:00Z',
                        'status' => 'SCHEDULED',
                        'homeTeam' => ['name' => 'Brazil FC'], // Gần đúng tên
                        'awayTeam' => ['name' => 'Morocco'],
                        'score' => [
                            'fullTime' => ['home' => null, 'away' => null],
                            'halfTime' => ['home' => null, 'away' => null],
                        ],
                    ],
                ],
            ], 200),
        ]);

        // 3. Thực thi
        $this->service->syncSchedules();

        // 4. Kiểm chứng: api_id đã được map thành 12345
        $dbMatch = $dbMatch->fresh();
        $this->assertEquals('12345', $dbMatch->api_id);
    }

    public function test_sync_live_scores_updates_scores_and_status(): void
    {
        $dbMatch = FootballMatch::create([
            'season_id' => $this->season->id,
            'match_code' => 'M001',
            'api_id' => '12345',
            'stage' => 'Group',
            'home_team' => 'Brazil',
            'away_team' => 'Morocco',
            'kickoff_at' => now()->subHour(),
            'timezone' => 'Asia/Ho_Chi_Minh',
            'status' => 'LIVE',
        ]);

        Http::fake([
            'https://api.football-data.org/v4/competitions/2000/matches*' => Http::response([
                'matches' => [
                    [
                        'id' => 12345,
                        'utcDate' => '2026-06-12T11:00:00Z',
                        'status' => 'IN_PLAY',
                        'homeTeam' => ['name' => 'Brazil'],
                        'awayTeam' => ['name' => 'Morocco'],
                        'score' => [
                            'fullTime' => ['home' => 2, 'away' => 1],
                        ],
                    ]
                ]
            ], 200),
        ]);

        $this->service->syncLiveScores();

        $dbMatch = $dbMatch->fresh();
        $this->assertEquals(2, $dbMatch->home_score);
        $this->assertEquals(1, $dbMatch->away_score);
        $this->assertEquals('LIVE', $dbMatch->status);
    }

    public function test_sync_live_scores_finished_creates_confirmed_period_results_and_locks_markets(): void
    {
        \Illuminate\Support\Facades\Queue::fake();

        $dbMatch = FootballMatch::create([
            'season_id' => $this->season->id,
            'match_code' => 'M001',
            'api_id' => '12345',
            'stage' => 'Group',
            'home_team' => 'Brazil',
            'away_team' => 'Morocco',
            'kickoff_at' => now()->subHours(2),
            'timezone' => 'Asia/Ho_Chi_Minh',
            'status' => 'LIVE',
        ]);

        $market = Market::create([
            'match_id' => $dbMatch->id,
            'period_type' => 'FULL_TIME',
            'market_type' => 'EXACT_SCORE',
            'name' => 'Tỷ số chính xác',
            'open_at' => now()->subHours(3),
            'close_at' => now()->subHour(),
            'status' => MarketStatus::OPEN->value,
            'display_order' => 1,
        ]);

        Http::fake([
            'https://api.football-data.org/v4/competitions/2000/matches*' => Http::response([
                'matches' => [
                    [
                        'id' => 12345,
                        'utcDate' => '2026-06-12T11:00:00Z',
                        'status' => 'FINISHED',
                        'homeTeam' => ['name' => 'Brazil'],
                        'awayTeam' => ['name' => 'Morocco'],
                        'score' => [
                            'fullTime' => ['home' => 3, 'away' => 0],
                            'halfTime' => ['home' => 1, 'away' => 0],
                        ],
                    ]
                ]
            ], 200),
        ]);

        $this->service->syncLiveScores();

        // 1. Kiểm tra score & status của match
        $dbMatch = $dbMatch->fresh();
        $this->assertEquals('FINISHED', $dbMatch->status);
        $this->assertEquals(3, $dbMatch->home_score);
        $this->assertEquals(0, $dbMatch->away_score);

        // 2. Kiểm tra match_period_results được tạo với CONFIRMED
        $fullTimeResult = MatchPeriodResult::where('match_id', $dbMatch->id)->where('period_type', 'FULL_TIME')->first();
        $this->assertNotNull($fullTimeResult);
        $this->assertEquals('CONFIRMED', $fullTimeResult->status);
        $this->assertEquals(3, $fullTimeResult->home_score);
        $this->assertEquals(0, $fullTimeResult->away_score);

        $halfTimeResult = MatchPeriodResult::where('match_id', $dbMatch->id)->where('period_type', 'FIRST_HALF')->first();
        $this->assertNotNull($halfTimeResult);
        $this->assertEquals('CONFIRMED', $halfTimeResult->status);
        $this->assertEquals(1, $halfTimeResult->home_score);
        $this->assertEquals(0, $halfTimeResult->away_score);

        // 3. Kiểm tra market được khóa
        $market = $market->fresh();
        $this->assertEquals(MarketStatus::LOCKED->value, $market->status);
    }

    public function test_sync_live_scores_cancelled_voids_markets_and_refunds_bets(): void
    {
        $dbMatch = FootballMatch::create([
            'season_id' => $this->season->id,
            'match_code' => 'M001',
            'api_id' => '12345',
            'stage' => 'Group',
            'home_team' => 'Brazil',
            'away_team' => 'Morocco',
            'kickoff_at' => now()->subHour(),
            'timezone' => 'Asia/Ho_Chi_Minh',
            'status' => 'LIVE',
        ]);

        $market = Market::create([
            'match_id' => $dbMatch->id,
            'period_type' => 'FULL_TIME',
            'market_type' => 'EXACT_SCORE',
            'name' => 'Tỷ số chính xác',
            'open_at' => now()->subHours(2),
            'close_at' => now()->addHour(),
            'status' => MarketStatus::OPEN->value,
            'display_order' => 1,
        ]);

        $user = User::factory()->create();
        $wallet = Wallet::create([
            'user_id' => $user->id,
            'season_id' => $this->season->id,
            'available_balance' => 900,
            'locked_balance' => 100,
            'total_staked' => 100,
            'status' => 'ACTIVE',
        ]);

        $outcome = MarketOutcome::create([
            'market_id' => $market->id,
            'label' => '3-0',
            'selection_side' => 'HOME',
            'line_value' => '3.0',
            'profit_rate' => '0.9000',
            'status' => 'ACTIVE',
            'display_order' => 1,
        ]);

        // Tạo bet pending
        $bet = Bet::create([
            'public_code' => 'DL-20260612-00001',
            'user_id' => $user->id,
            'wallet_id' => $wallet->id,
            'season_id' => $this->season->id,
            'match_id' => $dbMatch->id,
            'market_id' => $market->id,
            'outcome_id' => $outcome->id,
            'stake' => 100,
            'profit_rate_snapshot' => 0.90,
            'label_snapshot' => '3-0',
            'display_odds_snapshot' => '3-0 ăn 0.90',
            'close_at_snapshot' => now()->addHour(),
            'market_type_snapshot' => 'EXACT_SCORE',
            'period_type_snapshot' => 'FULL_TIME',
            'status' => BetStatus::PENDING->value,
            'placed_at' => now(),
        ]);

        Http::fake([
            'https://api.football-data.org/v4/competitions/2000/matches*' => Http::response([
                'matches' => [
                    [
                        'id' => 12345,
                        'utcDate' => '2026-06-12T11:00:00Z',
                        'status' => 'CANCELLED',
                        'homeTeam' => ['name' => 'Brazil'],
                        'awayTeam' => ['name' => 'Morocco'],
                        'score' => [
                            'fullTime' => ['home' => null, 'away' => null],
                        ],
                    ]
                ]
            ], 200),
        ]);

        $this->service->syncLiveScores();

        // 1. Kiểm tra status của match
        $dbMatch = $dbMatch->fresh();
        $this->assertEquals('CANCELLED', $dbMatch->status);

        // 2. Kiểm tra market bị VOIDED
        $market = $market->fresh();
        $this->assertEquals(MarketStatus::VOIDED->value, $market->status);

        // 3. Kiểm tra bet bị VOIDED
        $bet = $bet->fresh();
        $this->assertEquals(BetStatus::VOIDED, $bet->status);

        // 4. Kiểm tra ví được hoàn trả lá
        $wallet = $wallet->fresh();
        $this->assertEquals(1000, $wallet->available_balance);
        $this->assertEquals(0, $wallet->locked_balance);
        $this->assertEquals(100, $wallet->total_staked); // walletService::voidBet không thay đổi total_staked vì nó chưa được cộng vào
    }
}
