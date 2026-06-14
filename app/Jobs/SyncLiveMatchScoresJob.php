<?php

namespace App\Jobs;

use App\Domain\Match\Services\MatchSyncService;
use App\Models\FootballMatch;
use App\Settings\ApiSettings;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncLiveMatchScoresJob implements ShouldQueue
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
        if (! $settings->is_auto_sync_enabled) {
            return;
        }

        $provider = $settings->live_score_provider ?? 'rapidapi_fallback_footballdata';
        if ($provider === 'football_data' && empty($settings->football_data_api_token)) {
            return;
        }
        if (($provider === 'rapidapi' || $provider === 'rapidapi_fallback_footballdata') && empty($settings->rapidapi_key)) {
            return;
        }

        // Điều kiện chạy RẤT QUAN TRỌNG: Chỉ chạy nếu DB có ít nhất 1 trận kickoff_at <= now() và status != FINISHED/CANCELLED
        $hasActiveMatches = FootballMatch::whereNotNull('api_id')
            ->where('kickoff_at', '<=', now())
            ->where('status', '!=', 'CANCELLED')
            ->where(function ($q) {
                $q->where('status', '!=', 'FINISHED')
                    ->orWhereNull('home_score')
                    ->orWhereNull('finished_at');
            })
            ->exists();

        if (! $hasActiveMatches) {
            return;
        }

        try {
            $syncService->syncLiveScores();
        } catch (\Exception $e) {
            Log::error('Failed to sync live match scores via API: '.$e->getMessage());
            if (str_contains($e->getMessage(), '429')) {
                Log::warning('Football-Data API rate limit reached (429), skipping this run.');
            }
        }
    }
}
