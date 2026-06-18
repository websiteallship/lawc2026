<?php

namespace Tests\Feature;

use App\Domain\Modal\Checkers\CelebrationChecker;
use App\Domain\Modal\Checkers\DailyBriefingChecker;
use App\Domain\Modal\Checkers\DailyRankingChecker;
use App\Domain\Modal\Checkers\MatchReminderChecker;
use App\Domain\Modal\Checkers\ReengagementChecker;
use App\Domain\Modal\Checkers\SettlementSummaryChecker;
use App\Models\User;
use App\Settings\AppSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModalSystemTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create([
            'last_daily_briefing_at' => null,
            'last_daily_ranking_shown_at' => null,
            'last_celebration_shown_at' => null,
            'last_settlement_summary_shown_at' => null,
            'last_reengagement_shown_at' => null,
            'last_active_at' => now()->subDays(5),
        ]);
        
        $settings = app(AppSettings::class);
        $settings->welcome_modal_enabled = true;
    }

    public function test_daily_briefing_checker()
    {
        $checker = new DailyBriefingChecker();
        
        $this->assertTrue($checker->shouldShow($this->user));
        
        $checker->markAsSeen($this->user);
        
        $this->assertFalse($checker->shouldShow($this->user->refresh()));
    }

    public function test_daily_ranking_checker()
    {
        $checker = new DailyRankingChecker();
        
        $this->assertTrue($checker->shouldShow($this->user));
        
        $checker->markAsSeen($this->user);
        
        $this->assertFalse($checker->shouldShow($this->user->refresh()));
    }

    public function test_celebration_checker()
    {
        $checker = new CelebrationChecker();
        
        // No achievement/mission yet
        $this->assertFalse($checker->shouldShow($this->user));
        
        // Mark as seen updates the timestamp
        $checker->markAsSeen($this->user);
        $this->assertNotNull($this->user->refresh()->last_celebration_shown_at);
    }

    public function test_settlement_summary_checker()
    {
        $checker = new SettlementSummaryChecker();
        
        // No bets settled yet
        $this->assertFalse($checker->shouldShow($this->user));
        
        $checker->markAsSeen($this->user);
        $this->assertNotNull($this->user->refresh()->last_settlement_summary_shown_at);
    }

    public function test_match_reminder_checker()
    {
        $checker = new MatchReminderChecker();
        
        // No open match within next 5h that user hasn't bet on
        $this->assertFalse($checker->shouldShow($this->user));
    }

    public function test_reengagement_checker()
    {
        $checker = new ReengagementChecker();
        
        // User was active 5 days ago, default inactive threshold is 3 days
        // But no open matches to show
        $this->assertFalse($checker->shouldShow($this->user));
        
        $checker->markAsSeen($this->user);
        $this->assertNotNull($this->user->refresh()->last_reengagement_shown_at);
    }
}
