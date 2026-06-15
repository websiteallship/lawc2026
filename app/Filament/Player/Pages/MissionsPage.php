<?php

namespace App\Filament\Player\Pages;

use Filament\Pages\Page;
use App\Models\Mission;
use App\Models\UserMission;
use App\Models\Achievement;
use Illuminate\Support\Carbon;

class MissionsPage extends Page
{
    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-clipboard-document-check';
    }

    public static function getNavigationLabel(): string
    {
        return 'Nhiệm vụ tuần';
    }

    public function getTitle(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return 'Thử Thách Tuần';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Nhiệm vụ & Danh hiệu';
    }

    public static function getNavigationSort(): ?int
    {
        return 4;
    }

    protected string $view = 'filament.player.pages.missions-page';

    protected function getViewData(): array
    {
        $user = auth()->user();
        
        $missions = Mission::where('is_active', true)->orderBy('difficulty', 'asc')->get();
        
        $dailyMissions = $missions->where('type', 'daily');
        $weeklyMissions = $missions->where('type', 'weekly');
        $seasonMissions = $missions->where('type', 'season');
        
        $userMissions = UserMission::where('user_id', $user->id)->get()->keyBy('mission_id');
        $achievements = Achievement::all()->keyBy('id');
        
        $weeklyCount = $weeklyMissions->count();
        $weeklyCompleted = $weeklyMissions->filter(function($m) use ($userMissions) {
            return ($userMissions->get($m->id)?->is_completed ?? false);
        })->count();
        
        $endOfWeek = Carbon::now()->endOfWeek();
        $daysLeft = (int) Carbon::now()->diffInDays($endOfWeek);
        $hoursLeft = (int) (Carbon::now()->diffInHours($endOfWeek) % 24);
        
        return [
            'dailyMissions' => $dailyMissions,
            'weeklyMissions' => $weeklyMissions,
            'seasonMissions' => $seasonMissions,
            'userMissions' => $userMissions,
            'achievements' => $achievements,
            'weeklyProgress' => [
                'completed' => $weeklyCompleted,
                'total' => $weeklyCount,
                'percent' => $weeklyCount > 0 ? round(($weeklyCompleted / $weeklyCount) * 100) : 0
            ],
            'timeLeft' => "{$daysLeft} ngày {$hoursLeft} giờ",
        ];
    }
}
