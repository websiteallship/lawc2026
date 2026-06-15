<?php

namespace App\Listeners;

use App\Events\SettlementCompleted;
use App\Jobs\CheckAchievementJob;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class CheckAchievementOnSettlementCompleted
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(SettlementCompleted $event): void
    {
        CheckAchievementJob::dispatch($event->userId);
    }
}
