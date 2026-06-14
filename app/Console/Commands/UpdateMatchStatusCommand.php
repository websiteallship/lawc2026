<?php

namespace App\Console\Commands;

use App\Models\FootballMatch;
use App\Settings\ApiSettings;
use Illuminate\Console\Command;

class UpdateMatchStatusCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'matches:update-status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Tự động cập nhật trạng thái trận đấu (SCHEDULED -> LIVE -> FINISHED) dựa trên thời gian thực';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $settings = app(ApiSettings::class);
        if ($settings->is_auto_sync_enabled && ! empty($settings->football_data_api_token)) {
            $this->info('Tự động đồng bộ API đang BẬT. Bỏ qua cập nhật bằng thời gian (fallback).');

            return Command::SUCCESS;
        }

        $now = now();

        // 1. SCHEDULED -> LIVE (khi tới giờ đá)
        $liveCount = FootballMatch::where('status', 'SCHEDULED')
            ->where('kickoff_at', '<=', $now)
            ->update(['status' => 'LIVE']);

        // 2. LIVE -> FINISHED (sau giờ đá 150 phút để bao gồm thời gian bù giờ, nghỉ giữa hiệp, hiệp phụ...)
        $finishedCount = FootballMatch::where('status', 'LIVE')
            ->where('kickoff_at', '<=', $now->copy()->subMinutes(150))
            ->update(['status' => 'FINISHED']);

        if ($liveCount > 0 || $finishedCount > 0) {
            $this->info("Đã cập nhật: {$liveCount} trận sang LIVE, {$finishedCount} trận sang FINISHED.");
        }

        return Command::SUCCESS;
    }
}
