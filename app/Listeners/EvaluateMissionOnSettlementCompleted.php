<?php

namespace App\Listeners;

use App\Events\SettlementCompleted;
use App\Jobs\EvaluateMissionsJob;

class EvaluateMissionOnSettlementCompleted
{
    public function __construct()
    {
        //
    }

    public function handle(SettlementCompleted $event): void
    {
        // No-op: weekly and daily missions are currently evaluated on bet placement or scheduled commands
    }
}
