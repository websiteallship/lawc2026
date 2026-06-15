<?php

namespace App\Listeners;

use App\Events\BetPlaced;
use App\Jobs\EvaluateMissionsJob;

class EvaluateMissionOnBetPlaced
{
    public function __construct()
    {
        //
    }

    public function handle(BetPlaced $event): void
    {
        $bet = $event->bet;
        $userId = $bet->user_id;

        EvaluateMissionsJob::dispatch($userId, 'DAILY_ONE_BET', 1);
        EvaluateMissionsJob::dispatch($userId, 'WEEKLY_ACTIVE_PLAYER', 1);
        EvaluateMissionsJob::dispatch($userId, 'WEEKLY_MARKET_EXPLORER', 1);
        EvaluateMissionsJob::dispatch($userId, 'SMART_STAKE', 1);

        if ($bet->match && $bet->match->stage !== 'GROUP_STAGE') {
            EvaluateMissionsJob::dispatch($userId, 'KNOCKOUT_PARTICIPANT', 1);
        }
    }
}
