<?php

use App\Jobs\SyncLiveMatchScoresJob;
use App\Jobs\SyncPreMatchOddsJob;
use App\Jobs\SyncScheduledMatchesJob;
use App\Jobs\SyncAllMatchDetailsJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ===================== MARKET AUTO-LOCK =====================
// Khóa market hết giờ mỗi phút
Schedule::command('markets:lock-expired')->everyMinute();

// Thông báo nhắc nhở các trận sắp đóng
Schedule::command('notify:closing-soon')->everyMinute();

// ===================== MATCH AUTO-UPDATE =====================
// Cập nhật trạng thái trận đấu (SCHEDULED -> LIVE -> FINISHED) mỗi phút
Schedule::command('matches:update-status')->everyMinute();

// ===================== API FOOTBALL-DATA SYNC =====================
// Đồng bộ lịch thi đấu (kiểm tra dời lịch) mỗi giờ
Schedule::job(new SyncScheduledMatchesJob)->hourly();

$apiSettings = null;
if (app()->bound(\App\Settings\ApiSettings::class)) {
    try {
        if (\Illuminate\Support\Facades\Schema::hasTable('settings')) {
            $apiSettings = app(\App\Settings\ApiSettings::class);
        }
    } catch (\Throwable $e) {
        // Fallback if settings table or DB is not yet available
    }
}

// Đồng bộ tỉ số trực tiếp
if ($apiSettings?->is_auto_sync_enabled ?? true) {
    $liveEvent = Schedule::job(new SyncLiveMatchScoresJob);
    match ((int) ($apiSettings?->auto_sync_interval_minutes ?? 1)) {
        1 => $liveEvent->everyMinute(),
        3 => $liveEvent->everyThreeMinutes(),
        5 => $liveEvent->everyFiveMinutes(),
        default => $liveEvent->everyMinute(),
    };
}

// ===================== API RAPIDAPI ODDS & DETAILS =====================
// Kéo tỷ lệ Pre-match và sự kiện trận đấu
if ($apiSettings?->is_rapidapi_auto_sync_enabled ?? false) {
    $oddsEvent = Schedule::job(new SyncPreMatchOddsJob);
    $detailsEvent = Schedule::job(new SyncAllMatchDetailsJob);
    match ((int) ($apiSettings?->rapidapi_auto_sync_interval_minutes ?? 60)) {
        5 => [$oddsEvent->everyFiveMinutes(), $detailsEvent->everyFiveMinutes()],
        15 => [$oddsEvent->everyFifteenMinutes(), $detailsEvent->everyFifteenMinutes()],
        30 => [$oddsEvent->everyThirtyMinutes(), $detailsEvent->everyThirtyMinutes()],
        60 => [$oddsEvent->hourly(), $detailsEvent->hourly()],
        default => [$oddsEvent->hourly(), $detailsEvent->hourly()],
    };
}

// ===================== BACKUP =====================
// Backup DB 2 lần/ngày: 02:00 và 14:00 (giờ VN = UTC+7, server UTC thì 19:00 và 07:00 UTC)
Schedule::command('backup:run --only-db')->twiceDaily(2, 14);

// Dọn backup cũ mỗi tuần (giữ theo strategy trong config/backup.php)
Schedule::command('backup:clean')->weekly();

// Export CSV (Bets/Markets/Settlements) → zip → backup disk 2 lần/ngày: 02:00 và 14:00 (khớp với backup:run)
Schedule::command('csv:backup --type=all')->twiceDaily(2, 14)
    ->appendOutputTo(storage_path('logs/csv-backup.log'));

// ===================== LEADERBOARD SNAPSHOT & NOTIFY =====================
// Snapshot leaderboard WC2026 mỗi giờ (thay đổi season_code tuỳ môi trường)
Schedule::command('snapshot:leaderboard WC2026')->hourly();

// Thông báo bảng xếp hạng hằng ngày lúc 8:00 sáng
Schedule::command('notify:leaderboard --type=daily')->dailyAt('08:00');

// Thông báo bảng xếp hạng hằng tuần vào sáng thứ 2 lúc 8:00 sáng
Schedule::command('notify:leaderboard --type=weekly')->weeklyOn(1, '08:00');
