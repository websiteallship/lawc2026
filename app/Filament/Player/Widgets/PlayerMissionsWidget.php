<?php

namespace App\Filament\Player\Widgets;

use App\Filament\Player\Pages\MatchListPage;
use App\Filament\Player\Pages\MissionsPage;
use App\Models\Mission;
use Filament\Actions\Action;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\HtmlString;

class PlayerMissionsWidget extends BaseWidget
{
    protected static ?string $heading = 'Nhiệm Vụ Của Bạn';

    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Mission::query()
                    ->where('is_active', true)
                    ->with([
                        'userMissions' => function ($query) {
                            $query->where('user_id', Auth::id());
                        },
                        'rewardAchievement',
                    ])
                    ->limit(3)
            )
            ->contentFooter(view('filament.player.widgets.missions-footer', ['url' => MissionsPage::getUrl()]))
            ->contentGrid([
                'default' => 1,
                'md' => 2,
                'lg' => 3,
            ])
            ->columns([
                Tables\Columns\Layout\Stack::make([
                    Tables\Columns\TextColumn::make('title')
                        ->weight('bold')
                        ->html()
                        ->formatStateUsing(function (string $state, Mission $record): string {
                            $typeLabel = match ($record->type) {
                                'daily' => 'Hàng ngày',
                                'season' => 'Mùa giải',
                                default => 'Hàng tuần',
                            };
                            $typeColorClass = match ($record->type) {
                                'daily' => 'bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 border-blue-200/50 dark:border-blue-800/30',
                                'season' => 'bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 border-indigo-200/50 dark:border-indigo-800/30',
                                default => 'bg-purple-50 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400 border-purple-200/50 dark:border-purple-800/30',
                            };

                            $userMission = $record->userMissions->first();
                            $statusLabel = ($userMission && $userMission->is_completed) ? 'Hoàn thành' : 'Đang tiến hành';
                            $statusColorClass = ($userMission && $userMission->is_completed)
                                ? 'bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 border-emerald-200/50 dark:border-emerald-800/30'
                                : 'bg-amber-50 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400 border-amber-200/50 dark:border-amber-800/30';

                            return "
                                <div class='flex flex-wrap items-center gap-2 mb-1'>
                                    <span class='text-sm font-bold text-gray-900 dark:text-white'>{$state}</span>
                                    <div class='flex gap-1.5 ml-auto'>
                                        <span class='px-1.5 py-0.5 text-[9px] font-semibold uppercase tracking-wider rounded border {$typeColorClass}'>{$typeLabel}</span>
                                        <span class='px-1.5 py-0.5 text-[9px] font-semibold uppercase tracking-wider rounded border {$statusColorClass}'>{$statusLabel}</span>
                                    </div>
                                </div>
                            ";
                        })
                        ->description(fn (Mission $record): string => $record->description ?? ''),

                    Tables\Columns\TextColumn::make('progress_state')
                        ->html()
                        ->getStateUsing(function (Mission $record): string {
                            $userMission = $record->userMissions->first();
                            $current = $userMission ? $userMission->current_value : 0;
                            $target = $record->target_value;
                            $percent = min(100, round(($current / $target) * 100));

                            return "
                                <div class='flex flex-col gap-1 w-full my-2'>
                                    <div class='flex justify-between text-xs font-semibold text-gray-500 dark:text-gray-400'>
                                        <span>Tiến độ: {$current} / {$target}</span>
                                        <span>{$percent}%</span>
                                    </div>
                                    <div class='w-full bg-gray-100 dark:bg-gray-800 h-2 rounded-full overflow-hidden border border-gray-200/50 dark:border-gray-700/50'>
                                        <div class='bg-amber-500 h-full rounded-full transition-all duration-500' style='width: {$percent}%'></div>
                                    </div>
                                </div>
                            ";
                        }),

                    Tables\Columns\TextColumn::make('rewardAchievement.name')
                        ->html()
                        ->getStateUsing(function (Mission $record): string {
                            if (! $record->rewardAchievement) {
                                return '';
                            }
                            $ach = $record->rewardAchievement;

                            return "
                                <div class='flex items-center gap-1.5 text-xs font-semibold text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-950/30 px-2.5 py-1 rounded-full border border-amber-200/40 dark:border-amber-800/20 w-fit mt-1'>
                                    <svg class='w-3.5 h-3.5' fill='none' stroke='currentColor' stroke-width='2' viewBox='0 0 24 24'>
                                        <path stroke-linecap='round' stroke-linejoin='round' d='M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z'></path>
                                    </svg>
                                    <span>Phần thưởng: {$ach->name}</span>
                                </div>
                            ";
                        }),
                ])->space(3),
            ])
            ->actions([
                Action::make('play')
                    ->label('Tham gia')
                    ->icon('heroicon-m-play')
                    ->url(MatchListPage::getUrl())
                    ->visible(function (Mission $record) {
                        $userMission = $record->userMissions->first();

                        return ! ($userMission && $userMission->is_completed);
                    })
                    ->button()
                    ->color('primary')
                    ->size('sm'),
            ])
            ->paginated(false);
    }
}
