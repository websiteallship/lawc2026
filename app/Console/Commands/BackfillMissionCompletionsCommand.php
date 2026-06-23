<?php

namespace App\Console\Commands;

use App\Models\Mission;
use App\Models\UserMission;
use App\Models\UserMissionCompletion;
use Illuminate\Console\Command;

class BackfillMissionCompletionsCommand extends Command
{
    protected $signature = 'app:backfill-mission-completions
                            {--dry-run : Chạy thử, không ghi DB}';

    protected $description = 'Backfill user_mission_completions từ user_missions đã hoàn thành (one-time)';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $currentWeekKey = UserMissionCompletion::weekKey();

        // Lấy toàn bộ user_missions đã hoàn thành
        $completed = UserMission::where('is_completed', true)
            ->with('mission')
            ->get();

        $this->info("Tổng user_missions đã hoàn thành: {$completed->count()}");
        $this->info("Week key hiện tại: {$currentWeekKey}");
        $this->newLine();

        $inserted = 0;
        $skipped  = 0;

        $bar = $this->output->createProgressBar($completed->count());
        $bar->start();

        foreach ($completed as $um) {
            $mission = $um->mission;
            if (!$mission) { $bar->advance(); $skipped++; continue; }

            // Xác định week_key:
            // - Nếu completed_at có giá trị → dùng nó để tính week_key đúng tuần
            // - Nếu không → dùng tuần hiện tại (fallback an toàn)
            $completedAt = $um->completed_at ?? now();

            $weekKey = match($mission->type) {
                'weekly'    => UserMissionCompletion::weekKey($completedAt),
                'daily'     => $completedAt->timezone('Asia/Ho_Chi_Minh')->format('Y-m-d'),
                default     => null,
            };

            $this->line(
                "  User #{$um->user_id} | {$mission->code} | week_key={$weekKey}" .
                ($dryRun ? ' [DRY RUN]' : '')
            );

            if (!$dryRun) {
                $existing = UserMissionCompletion::where('user_id', $um->user_id)
                    ->where('mission_id', $mission->id)
                    ->where('week_key', $weekKey)
                    ->exists();

                if (!$existing) {
                    UserMissionCompletion::create([
                        'user_id'      => $um->user_id,
                        'mission_id'   => $mission->id,
                        'mission_code' => $mission->code,
                        'mission_type' => $mission->type,
                        'week_key'     => $weekKey,
                        'completed_at' => $completedAt,
                    ]);
                    $inserted++;
                } else {
                    $skipped++;
                }
            } else {
                $inserted++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("Backfill hoàn tất. Inserted: {$inserted} | Skipped: {$skipped}" . ($dryRun ? ' (DRY RUN)' : ''));

        return 0;
    }
}
