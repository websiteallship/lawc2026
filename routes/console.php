<?php

use App\Jobs\SyncLiveMatchScoresJob;
use App\Jobs\SyncPreMatchOddsJob;
use App\Jobs\SyncScheduledMatchesJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ===================== MARKET AUTO-LOCK =====================
// Khóa market hết giờ mỗi phút
Schedule::command('markets:lock-expired')->everyMinute();

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

// ===================== API RAPIDAPI ODDS =====================
// Kéo tỷ lệ Pre-match
if ($apiSettings?->is_rapidapi_auto_sync_enabled ?? false) {
    $oddsEvent = Schedule::job(new SyncPreMatchOddsJob);
    match ((int) ($apiSettings?->rapidapi_auto_sync_interval_minutes ?? 60)) {
        5 => $oddsEvent->everyFiveMinutes(),
        15 => $oddsEvent->everyFifteenMinutes(),
        30 => $oddsEvent->everyThirtyMinutes(),
        60 => $oddsEvent->hourly(),
        default => $oddsEvent->hourly(),
    };
}

// ===================== BACKUP =====================
// Backup DB hằng ngày lúc 2:00 AM (giờ VN)
Schedule::command('backup:run --only-db')->dailyAt('02:00');

// Dọn backup cũ mỗi tuần (giữ theo strategy trong config/backup.php)
Schedule::command('backup:clean')->weekly();

// ===================== LEADERBOARD SNAPSHOT =====================
// Snapshot leaderboard WC2026 mỗi giờ (thay đổi season_code tuỳ môi trường)
Schedule::command('snapshot:leaderboard WC2026')->hourly();
