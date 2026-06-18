<?php

namespace Tests\Feature\Livewire\Player;

use App\Domain\Modal\Contracts\CelebrationCheckerInterface;
use App\Domain\Modal\Contracts\DailyBriefingCheckerInterface;
use App\Domain\Modal\Contracts\DailyRankingCheckerInterface;
use App\Domain\Modal\Contracts\MatchReminderCheckerInterface;
use App\Domain\Modal\Contracts\ReengagementCheckerInterface;
use App\Domain\Modal\Contracts\SettlementSummaryCheckerInterface;
use App\Livewire\Player\ModalOrchestrator;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

class ModalOrchestratorTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    private function mockChecker(string $interface, bool $shouldShow, array $data = []): MockObject
    {
        $mock = $this->createMock($interface);
        $mock->method('shouldShow')->willReturn($shouldShow);
        $mock->method('getData')->willReturn($data);
        
        $this->app->instance($interface, $mock);
        
        return $mock;
    }

    public function test_it_does_not_show_any_modal_if_no_conditions_are_met(): void
    {
        $this->mockChecker(CelebrationCheckerInterface::class, false);
        $this->mockChecker(SettlementSummaryCheckerInterface::class, false);
        $this->mockChecker(DailyBriefingCheckerInterface::class, false);
        $this->mockChecker(DailyRankingCheckerInterface::class, false);
        $this->mockChecker(MatchReminderCheckerInterface::class, false);
        $this->mockChecker(ReengagementCheckerInterface::class, false);

        Livewire::test(ModalOrchestrator::class)
            ->assertSet('currentModal', null)
            ->assertSet('queue', []);
    }

    public function test_it_prioritizes_celebration_over_everything_else(): void
    {
        // Even if all are true, celebration should be first, settlement second, daily third, ranking fourth...
        $this->mockChecker(CelebrationCheckerInterface::class, true, ['achievements' => []]);
        $this->mockChecker(SettlementSummaryCheckerInterface::class, true, ['total' => 100]);
        $this->mockChecker(DailyBriefingCheckerInterface::class, true, ['wallet' => []]);
        $this->mockChecker(DailyRankingCheckerInterface::class, true, ['rank' => 1]);
        $this->mockChecker(MatchReminderCheckerInterface::class, true, ['matches' => []]);
        $this->mockChecker(ReengagementCheckerInterface::class, true, ['absent' => 5]);

        Livewire::test(ModalOrchestrator::class)
            ->assertSet('currentModal', 'celebration')
            ->assertSet('queue', [
                'settlement',
                'daily',
                'ranking',
                'reminder',
                'reengagement'
            ]);
    }

    public function test_dismissing_modal_pops_next_from_queue(): void
    {
        $celebrationMock = $this->mockChecker(CelebrationCheckerInterface::class, true, ['achievements' => []]);
        $celebrationMock->expects($this->once())->method('markAsSeen');

        $this->mockChecker(SettlementSummaryCheckerInterface::class, true, ['total' => 100]);
        
        // Disable the rest
        $this->mockChecker(DailyBriefingCheckerInterface::class, false);
        $this->mockChecker(DailyRankingCheckerInterface::class, false);
        $this->mockChecker(MatchReminderCheckerInterface::class, false);
        $this->mockChecker(ReengagementCheckerInterface::class, false);

        $component = Livewire::test(ModalOrchestrator::class);
        
        $component->assertSet('currentModal', 'celebration')
            ->assertSet('queue', ['settlement'])
            ->call('dismiss')
            ->assertSet('currentModal', null)
            ->assertDispatched('modal-queue-next');

        // Trigger the event to show next
        $component->dispatch('modal-queue-next')
            ->assertSet('currentModal', 'settlement')
            ->assertSet('queue', []);
    }

    public function test_daily_briefing_and_ranking_pair(): void
    {
        $this->mockChecker(CelebrationCheckerInterface::class, false);
        $this->mockChecker(SettlementSummaryCheckerInterface::class, false);
        $this->mockChecker(DailyBriefingCheckerInterface::class, true, ['data' => 1]);
        $this->mockChecker(DailyRankingCheckerInterface::class, true, ['data' => 2]);
        $this->mockChecker(MatchReminderCheckerInterface::class, false);
        $this->mockChecker(ReengagementCheckerInterface::class, false);

        $component = Livewire::test(ModalOrchestrator::class)
            ->assertSet('currentModal', 'daily')
            ->assertSet('queue', ['ranking']);

        $component->call('dismiss');
        
        $component->dispatch('modal-queue-next')
            ->assertSet('currentModal', 'ranking')
            ->assertSet('queue', []);
    }
}
