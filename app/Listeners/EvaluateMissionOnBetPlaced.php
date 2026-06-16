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

        $activeCodes = \App\Models\Mission::where('is_active', true)->pluck('code')->toArray();
        foreach ($activeCodes as $code) {
            if ($code === 'DAILY_ONE_BET' || $code === 'KNOCKOUT_PARTICIPANT') continue;
            
            $value = 1;
            if ($code === 'WEEKLY_W8') {
                $value = $bet->stake; // Tay chơi lớn: cộng dồn số lá cược
            }
            EvaluateMissionsJob::dispatch($userId, $code, $value);
        }

        if ($bet->match && $bet->match->stage !== 'GROUP_STAGE') {
            EvaluateMissionsJob::dispatch($userId, 'KNOCKOUT_PARTICIPANT', 1);
        }
    }
}
