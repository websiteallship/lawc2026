<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Domain\Match\Services\MatchSyncService;

class SyncAllMatchDetailsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300; // Allow up to 5 minutes

    public function __construct()
    {
        //
    }

    public function handle(MatchSyncService $syncService): void
    {
        $syncService->syncAllMatchDetailsInChunks();
    }
}
