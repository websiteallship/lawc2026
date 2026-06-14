<?php

namespace App\Jobs;

use App\Domain\Leaderboard\Services\LeaderboardService;
use App\Models\Season;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Queue job — rebuild leaderboard sau mỗi lần settlement.
 * Không block luồng chính (SettlementEngine dispatch job này sau execute).
 */
class RebuildLeaderboardJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 30;

    public function __construct(
        public readonly int $seasonId,
    ) {}

    public function handle(LeaderboardService $service): void
    {
        $season = Season::findOrFail($this->seasonId);
        $service->snapshot($season);
    }
}
