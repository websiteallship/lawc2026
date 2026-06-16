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
            if (in_array($code, ['DAILY_ONE_BET', 'KNOCKOUT_PARTICIPANT'])) continue;

            $value = 0;
            if ($code === 'WEEKLY_W1') {
                $value = 1;
            } elseif ($code === 'WEEKLY_W2') {
                $hour = $bet->placed_at->hour;
                if ($hour >= 0 && $hour < 5) {
                    $hasBetThisNight = \App\Models\Bet::where('user_id', $userId)->where('id', '!=', $bet->id)
                        ->whereDate('placed_at', $bet->placed_at->toDateString())
                        ->whereRaw('EXTRACT(HOUR FROM placed_at) >= 0 AND EXTRACT(HOUR FROM placed_at) < 5')
                        ->exists();
                    if (!$hasBetThisNight) $value = 1;
                }
            } elseif ($code === 'WEEKLY_W3') {
                $hasBetThisMarket = \App\Models\Bet::where('user_id', $userId)->where('id', '!=', $bet->id)
                    ->where('market_type_snapshot', $bet->market_type_snapshot)
                    ->where('placed_at', '>=', now()->startOfWeek())
                    ->exists();
                if (!$hasBetThisMarket) $value = 1;
            } elseif ($code === 'WEEKLY_W4') {
                $hasBetThisDay = \App\Models\Bet::where('user_id', $userId)->where('id', '!=', $bet->id)
                    ->whereDate('placed_at', $bet->placed_at->toDateString())
                    ->exists();
                if (!$hasBetThisDay) $value = 1;
            } elseif ($code === 'WEEKLY_W8') {
                $value = $bet->stake;
            }

            if ($value > 0) {
                EvaluateMissionsJob::dispatch($userId, $code, $value);
            }
        }

        if ($bet->match && $bet->match->stage !== 'GROUP_STAGE') {
            EvaluateMissionsJob::dispatch($userId, 'KNOCKOUT_PARTICIPANT', 1);
        }
    }
}
