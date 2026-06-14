<?php

namespace App\Jobs;

use App\Domain\Match\Services\MatchSyncService;
use App\Settings\ApiSettings;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncScheduledMatchesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(ApiSettings $settings, MatchSyncService $syncService): void
    {
        if (! $settings->is_auto_sync_enabled || empty($settings->football_data_api_token)) {
            return;
        }

        try {
            $syncService->syncSchedules();
        } catch (\Exception $e) {
            Log::error('Failed to sync scheduled matches via API: '.$e->getMessage());
        }
    }
}
