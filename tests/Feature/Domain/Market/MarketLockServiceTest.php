<?php

namespace Tests\Feature\Domain\Market;

use App\Domain\Market\Exceptions\InvalidMarketTransitionException;
use App\Domain\Market\Services\MarketLockService;
use App\Enums\MarketStatus;
use App\Models\FootballMatch;
use App\Models\Market;
use App\Models\Season;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarketLockServiceTest extends TestCase
{
    use RefreshDatabase;

    private MarketLockService $service;

    private Season $season;

    private FootballMatch $match;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(MarketLockService::class);
        $this->season = Season::factory()->create();
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
    }

    private function makeMarket(string $status = 'DRAFT', ?string $closeAt = null): Market
    {
        return Market::create([
            'match_id' => $this->match->id,
            'period_type' => 'FULL_TIME',
            'market_type' => 'ASIAN_HANDICAP',
            'name' => 'Kèo chấp',
            'open_at' => now()->subHour(),
            'close_at' => $closeAt ?? now()->addHour(),
            'status' => $status,
            'display_order' => 1,
        ]);
    }

    public function test_publish_transitions_draft_to_open(): void
    {
        $market = $this->makeMarket('DRAFT');
        $result = $this->service->publish($market);
        $this->assertEquals('OPEN', $result->status);
    }

    public function test_transition_open_to_locked(): void
    {
        $market = $this->makeMarket('OPEN');
        $result = $this->service->transition($market, MarketStatus::LOCKED);
        $this->assertEquals('LOCKED', $result->status);
        $this->assertNotNull($result->locked_at);
    }

    public function test_transition_locked_to_settling(): void
    {
        $market = $this->makeMarket('LOCKED');
        $result = $this->service->transition($market, MarketStatus::SETTLING);
        $this->assertEquals('SETTLING', $result->status);
    }

    public function test_invalid_transition_throws(): void
    {
        $market = $this->makeMarket('SETTLED');
        $this->expectException(InvalidMarketTransitionException::class);
        $this->service->transition($market, MarketStatus::OPEN);
    }

    public function test_cannot_transition_voided_to_open(): void
    {
        $market = $this->makeMarket('VOIDED');
        $this->expectException(InvalidMarketTransitionException::class);
        $this->service->transition($market, MarketStatus::OPEN);
    }

    public function test_lock_expired_markets_locks_only_open_past_close_at(): void
    {
        // Quá giờ đóng — nên bị lock
        $expired = $this->makeMarket('OPEN', now()->subMinute()->toDateTimeString());
        // Chưa đến giờ đóng — không được lock
        $active = $this->makeMarket('OPEN', now()->addHour()->toDateTimeString());
        // Đã DRAFT — không được lock
        $draft = $this->makeMarket('DRAFT', now()->subMinute()->toDateTimeString());

        $count = $this->service->lockExpiredMarkets();

        $this->assertEquals(1, $count);
        $this->assertEquals('LOCKED', $expired->fresh()->status);
        $this->assertEquals('OPEN', $active->fresh()->status);
        $this->assertEquals('DRAFT', $draft->fresh()->status);
    }

    public function test_lock_expired_markets_is_idempotent(): void
    {
        $expired = $this->makeMarket('OPEN', now()->subMinute()->toDateTimeString());

        $count1 = $this->service->lockExpiredMarkets();
        $count2 = $this->service->lockExpiredMarkets(); // Chạy lần 2

        $this->assertEquals(1, $count1);
        $this->assertEquals(0, $count2); // Không lock lại
        $this->assertEquals('LOCKED', $expired->fresh()->status);
    }

    public function test_void_market_sets_void_reason(): void
    {
        $market = $this->makeMarket('OPEN');
        $result = $this->service->transition($market, MarketStatus::VOIDED, 'Kết quả sai');
        $this->assertEquals('VOIDED', $result->status);
        $this->assertNotNull($result->voided_at);
        $this->assertEquals('Kết quả sai', $result->void_reason);
    }
}
