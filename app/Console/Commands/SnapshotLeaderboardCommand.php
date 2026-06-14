<?php

namespace App\Console\Commands;

use App\Domain\Leaderboard\Services\LeaderboardService;
use App\Models\Season;
use Illuminate\Console\Command;

/**
 * Chụp snapshot leaderboard tại thời điểm hiện tại.
 *
 * Usage:
 *   php artisan snapshot:leaderboard WC2026
 */
class SnapshotLeaderboardCommand extends Command
{
    protected $signature = 'snapshot:leaderboard {season_code : Mã mùa giải}';

    protected $description = 'Chụp snapshot bảng xếp hạng vào DB';

    public function handle(LeaderboardService $service): int
    {
        $season = Season::where('code', $this->argument('season_code'))->first();

        if (! $season) {
            $this->error("Không tìm thấy season: {$this->argument('season_code')}");

            return Command::FAILURE;
        }

        $entries = $service->snapshot($season);
        $this->info("Snapshot hoàn tất: {$entries->count()} user | Season: {$season->code}");

        return Command::SUCCESS;
    }
}
