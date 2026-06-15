<?php

namespace App\Filament\Player\Widgets;

use App\Models\Mission;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\Auth;

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
                        'rewardAchievement'
                    ])
            )
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Nhiệm Vụ')
                    ->description(fn (Mission $record): string => $record->description ?? '')
                    ->weight('bold')
                    ->html()
                    ->formatStateUsing(function (string $state, Mission $record): string {
                        $typeLabel = match($record->type) {
                            'daily' => 'Hàng ngày',
                            'season' => 'Mùa giải',
                            default => 'Hàng tuần',
                        };
                        $typeColorClass = match($record->type) {
                            'daily' => 'bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 border-blue-200/50 dark:border-blue-800/30',
                            'season' => 'bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 border-indigo-200/50 dark:border-indigo-800/30',
                            default => 'bg-purple-50 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400 border-purple-200/50 dark:border-purple-800/30',
                        };
                        
                        return "
                            <div class='flex items-center gap-2'>
                                <span class='font-bold text-gray-800 dark:text-gray-200'>{$state}</span>
                                <span class='px-1.5 py-0.5 text-[10px] font-semibold uppercase tracking-wider rounded border {$typeColorClass}'>{$typeLabel}</span>
                            </div>
                        ";
                    }),
                
                Tables\Columns\TextColumn::make('progress_state')
                    ->label('Tiến Độ')
                    ->html()
                    ->getStateUsing(function (Mission $record): string {
                        $userMission = $record->userMissions->first();
                        $current = $userMission ? $userMission->current_value : 0;
                        $target = $record->target_value;
                        $percent = min(100, round(($current / $target) * 100));
                        
                        return "
                            <div class='flex flex-col gap-1.5 min-w-[120px] max-w-[180px]'>
                                <div class='flex justify-between text-xs font-semibold text-gray-500 dark:text-gray-400'>
                                    <span>{$current} / {$target}</span>
                                    <span>{$percent}%</span>
                                </div>
                                <div class='w-full bg-gray-100 dark:bg-gray-800 h-2 rounded-full overflow-hidden border border-gray-200/50 dark:border-gray-700/50'>
                                    <div class='bg-amber-500 h-full rounded-full transition-all duration-500' style='width: {$percent}%'></div>
                                </div>
                            </div>
                        ";
                    }),

                Tables\Columns\TextColumn::make('rewardAchievement.name')
                    ->label('Phần Thưởng')
                    ->html()
                    ->getStateUsing(function (Mission $record): string {
                        if (!$record->rewardAchievement) {
                            return '<span class="text-xs text-gray-400">-</span>';
                        }
                        $ach = $record->rewardAchievement;
                        return "
                            <div class='flex items-center gap-1.5 text-xs font-semibold text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-950/30 px-2.5 py-1 rounded-full border border-amber-200/40 dark:border-amber-800/20 w-fit'>
                                <svg class='w-4.5 h-4.5' fill='none' stroke='currentColor' stroke-width='2' viewBox='0 0 24 24'>
                                    <path stroke-linecap='round' stroke-linejoin='round' d='M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z'></path>
                                </svg>
                                <span>{$ach->name}</span>
                            </div>
                        ";
                    }),

                Tables\Columns\TextColumn::make('status_state')
                    ->badge()
                    ->label('Trạng Thái')
                    ->getStateUsing(function (Mission $record): string {
                        $userMission = $record->userMissions->first();
                        return ($userMission && $userMission->is_completed) ? 'Hoàn thành' : 'Đang thực hiện';
                    })
                    ->color(function (Mission $record): string {
                        $userMission = $record->userMissions->first();
                        return ($userMission && $userMission->is_completed) ? 'success' : 'primary';
                    }),
            ])
            ->actions([
                \Filament\Actions\Action::make('play')
                    ->label('Tham gia')
                    ->icon('heroicon-m-play')
                    ->url(\App\Filament\Player\Pages\MatchListPage::getUrl())
                    ->visible(function (Mission $record) {
                        $userMission = $record->userMissions->first();
                        return !($userMission && $userMission->is_completed);
                    })
                    ->button()
                    ->color('primary')
                    ->size('sm'),
            ])
            ->paginated(false);
    }
}
