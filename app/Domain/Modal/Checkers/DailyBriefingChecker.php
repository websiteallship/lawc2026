<?php

namespace App\Domain\Modal\Checkers;

use App\Domain\Modal\Contracts\DailyBriefingCheckerInterface;
use App\Models\Mission;
use App\Models\User;
use App\Models\Market;
use App\Models\UserMission;
use App\Settings\AppSettings;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class DailyBriefingChecker implements DailyBriefingCheckerInterface
{
    public function shouldShow(User $user): bool
    {
        if (!app(AppSettings::class)->welcome_modal_enabled) return false;
        $last = $user->last_daily_briefing_at;
        return $last === null
            || !Carbon::parse($last)->timezone('Asia/Ho_Chi_Minh')->isToday();
    }

    public function getData(User $user): array
    {
        return Cache::remember("modal_briefing_{$user->id}", 600, function () use ($user) {
            $wallet = $user->wallets()->first();
            $walletData = $wallet ? ['available_balance' => $wallet->available_balance] : null;

            $missions = Mission::where('is_active', true)->limit(3)->get();
            $missionsData = [];
            if ($missions->isNotEmpty()) {
                $missionIds = $missions->pluck('id');
                $userMissions = UserMission::where('user_id', $user->id)
                    ->whereIn('mission_id', $missionIds)
                    ->get()
                    ->keyBy('mission_id');

                $missionsData = $missions->map(function ($m) use ($userMissions) {
                    $um = $userMissions->get($m->id);
                    return [
                        'title'        => $m->title,
                        'description'  => $m->description,
                        'target_value' => $m->target_value,
                        'user_mission' => $um ? [
                            'is_completed'  => $um->is_completed,
                            'current_value' => $um->current_value,
                        ] : null,
                    ];
                })->toArray();
            }

            $matchesData = Market::with('match')
                ->where('status', 'OPEN')
                ->where('close_at', '>', now())
                ->orderBy('close_at')
                ->limit(5)
                ->get()
                ->map(function ($m) {
                    return [
                        'match_id'       => $m->match_id,
                        'name'           => $m->name,
                        'close_at'       => $m->close_at,
                        'home_team_name' => $m->match->home_team ?? 'Home',
                        'away_team_name' => $m->match->away_team ?? 'Away',
                    ];
                })->toArray();

            return [
                'wallet'   => $walletData,
                'missions' => $missionsData,
                'matches'  => $matchesData,
            ];
        });
    }

    public function markAsSeen(User $user): void
    {
        $user->updateQuietly(['last_daily_briefing_at' => now()]);
        Cache::forget("modal_briefing_{$user->id}");
    }
}
