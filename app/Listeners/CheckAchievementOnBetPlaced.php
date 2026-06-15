<?php

namespace App\Listeners;

use App\Events\BetPlaced;
use App\Jobs\CheckAchievementJob;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class CheckAchievementOnBetPlaced
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
    public function handle(BetPlaced $event): void
    {
        CheckAchievementJob::dispatch($event->bet->user_id);
    }
}
