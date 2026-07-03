<x-filament-panels::page>
    <!-- HERO BANNER -->
    <div class="relative overflow-hidden rounded-[2rem] p-8 md:p-10 lg:p-12 bg-white dark:bg-gray-900 shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10">
        <!-- Abstract glowing circles -->
        <div class="absolute -right-10 -top-10 w-64 h-64 bg-primary-500/10 dark:bg-primary-500/20 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute -left-10 -bottom-10 w-64 h-64 bg-warning-500/5 dark:bg-warning-500/10 rounded-full blur-3xl pointer-events-none"></div>
        
        <div class="relative flex flex-col lg:flex-row items-center justify-between gap-10">
            <div class="flex items-center gap-6 md:gap-8 w-full lg:w-auto">
                <div class="relative flex items-center justify-center rounded-2xl shadow-sm shrink-0 bg-warning-50 dark:bg-warning-500/10 ring-1 ring-warning-200 dark:ring-warning-500/20" style="width: 88px; height: 88px;">
                    <x-filament::icon icon="heroicon-o-calendar-days" class="text-warning-500 dark:text-warning-400" style="width: 44px; height: 44px;" />
                </div>
                <div>
                    <span class="text-xs font-black uppercase tracking-widest block mb-1 text-warning-600 dark:text-warning-400">Tuần Này</span>
                    <h1 class="text-2xl md:text-4xl font-extrabold tracking-tight text-gray-950 dark:text-white">Thử Thách Tuần</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-2 max-w-md">Hoàn thành 5 nhiệm vụ ngẫu nhiên của tuần để nhận thêm phần thưởng hấp dẫn.</p>
                </div>
            </div>

            <!-- Global Progress -->
            <div class="w-full lg:w-[450px] p-6 rounded-2xl shrink-0 bg-gray-50 dark:bg-gray-800/50 ring-1 ring-gray-200 dark:ring-gray-700/50 space-y-4">
                <div class="flex justify-between items-center">
                    <span class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Tiến độ chung</span>
                    <span class="text-xs font-extrabold text-warning-600 dark:text-warning-400 bg-warning-50 dark:bg-warning-500/10 px-2 py-1 rounded-md flex items-center gap-1">
                        <x-filament::icon icon="heroicon-m-clock" class="w-3 h-3"/> Còn {{ $timeLeft }}
                    </span>
                </div>
                
                <div class="space-y-2">
                    <div class="w-full h-3 rounded-full overflow-hidden bg-gray-200 dark:bg-gray-700">
                        <div class="h-full rounded-full transition-all duration-500 bg-warning-500" style="width: {{ $weeklyProgress['percent'] }}%;"></div>
                    </div>
                    <div class="flex justify-between text-[11px] font-bold text-gray-500 dark:text-gray-400 mt-1">
                        <span>Hoàn tất: {{ $weeklyProgress['completed'] }}/{{ $weeklyProgress['total'] }}</span>
                        <span>{{ $weeklyProgress['percent'] }}%</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- WEEKLY MISSIONS GRID -->
    <div class="mt-8">
        <h2 class="text-lg font-bold text-gray-950 dark:text-white mb-6 flex items-center gap-2">
            <x-filament::icon icon="heroicon-s-star" class="w-5 h-5 text-warning-500" />
            Nhiệm Vụ Tuần (Độ khó tăng dần)
        </h2>
        
        @php
            $activeWeekly = $weeklyMissions->filter(fn($m) => !($userMissions->get($m->id)?->is_completed ?? false))->sortBy('difficulty');
            $completedWeekly = $weeklyMissions->filter(fn($m) => ($userMissions->get($m->id)?->is_completed ?? false))->sortBy('difficulty');
        @endphp

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            {{-- Active Weekly Missions --}}
            @foreach($activeWeekly as $mission)
                @php
                    $missionProgress = $userMissions->get($mission->id) ?? null;
                    $mCurrent = $missionProgress ? $missionProgress->current_value : 0;
                    $mTarget = $mission->target_value;
                    $mPercent = max(0, min(100, $mTarget > 0 ? round(($mCurrent / $mTarget) * 100) : 0));
                    $mCompleted = false;
                    
                    $diffColor = match($mission->difficulty) {
                        1 => 'text-success-500 bg-success-50',
                        2 => 'text-info-500 bg-info-50',
                        3 => 'text-warning-500 bg-warning-50',
                        4 => 'text-danger-500 bg-danger-50',
                        5 => 'text-purple-500 bg-purple-50',
                        default => 'text-gray-500 bg-gray-50',
                    };
                    $diffStars = str_repeat('★', $mission->difficulty) . str_repeat('☆', 5 - $mission->difficulty);
                    
                    $reward = $mission->reward_achievement_id ? $achievements->get($mission->reward_achievement_id) : null;
                @endphp
                <div class="relative overflow-hidden p-6 rounded-2xl flex flex-col justify-between cursor-pointer transition-all duration-300 hover:-translate-y-1 hover:shadow-xl bg-white ring-1 ring-gray-950/5 hover:ring-2 hover:ring-primary-500 dark:bg-gray-900 dark:ring-white/10">
                    <div>
                        <div class="flex items-start justify-between gap-3 mb-4">
                            <div class="p-3 rounded-2xl bg-gray-50 text-gray-400 dark:bg-gray-800 dark:text-gray-500">
                                <x-filament::icon icon="heroicon-o-flag" class="w-8 h-8 text-primary-500" />
                            </div>
                            <div class="text-right">
                                <span class="text-[10px] font-bold uppercase tracking-wider px-2 py-1 rounded-md {{ $diffColor }} dark:bg-opacity-10">Độ khó: {{ $diffStars }}</span>
                            </div>
                        </div>
                        
                        <h3 class="text-base font-bold text-gray-900 dark:text-white mb-2">{{ $mission->title }}</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-6 leading-relaxed">{{ $mission->description }}</p>
                    </div>

                    @if($reward)
                        <div class="mb-4 p-3 bg-gray-50 dark:bg-gray-800/50 rounded-xl flex items-center gap-3 border border-gray-100 dark:border-gray-800 group">
                            <div class="w-10 h-10 rounded-lg bg-gray-200 dark:bg-gray-700 flex items-center justify-center shrink-0 grayscale opacity-60">
                                <x-filament::icon icon="heroicon-s-trophy" class="w-5 h-5 text-gray-500" />
                            </div>
                            <div class="flex-1 min-w-0">
                                <span class="text-[9px] font-bold text-gray-400 uppercase block">Phần thưởng Huy Hiệu</span>
                                <span class="text-xs font-bold text-gray-700 dark:text-gray-300 block truncate">{{ $reward->name }}</span>
                            </div>
                            <a href="{{ \App\Filament\Player\Pages\AchievementsPage::getUrl() }}" class="text-[10px] font-bold text-primary-600 dark:text-primary-400 hover:underline shrink-0">
                                Xem
                            </a>
                        </div>
                    @endif
                    
                    <div class="mt-auto">
                        <div class="flex justify-between text-[11px] font-bold mb-2 text-gray-600 dark:text-gray-400">
                            <span>Tiến độ</span>
                            <span>{{ $mCurrent }}/{{ $mTarget }} ({{ $mPercent }}%)</span>
                        </div>
                        <div class="w-full bg-gray-100 dark:bg-gray-800 h-2.5 rounded-full overflow-hidden mb-4">
                            <div class="bg-primary-500 h-full rounded-full transition-all duration-500" style="width: {{ $mPercent }}%"></div>
                        </div>
                        
                        <a href="{{ \App\Filament\Player\Pages\MatchListPage::getUrl() }}" class="block w-full py-2.5 text-center rounded-xl text-xs font-bold bg-primary-50 text-primary-600 hover:bg-primary-100 hover:text-primary-700 dark:bg-primary-500/10 dark:text-primary-400 dark:hover:bg-primary-500/20 transition-colors">
                            Thực hiện ngay
                        </a>
                    </div>
                </div>
            @endforeach

            {{-- Separator if both active and completed exist --}}
            @if($activeWeekly->count() > 0 && $completedWeekly->count() > 0)
                <div class="col-span-full my-4 flex items-center gap-4">
                    <div class="h-px bg-gray-200 dark:bg-gray-800 flex-1"></div>
                    <span class="text-xs font-bold text-success-600 dark:text-success-400 uppercase tracking-widest bg-success-50 dark:bg-success-950/20 px-4 py-1.5 rounded-full border border-success-200 dark:border-success-800/50 shadow-sm">Đã hoàn tất thử thách tuần</span>
                    <div class="h-px bg-gray-200 dark:bg-gray-800 flex-1"></div>
                </div>
            @endif

            {{-- Completed Weekly Missions --}}
            @foreach($completedWeekly as $mission)
                @php
                    $missionProgress = $userMissions->get($mission->id) ?? null;
                    $mCurrent = $missionProgress ? $missionProgress->current_value : 0;
                    $mTarget = $mission->target_value;
                    $mPercent = 100;
                    
                    $diffColor = match($mission->difficulty) {
                        1 => 'text-success-500 bg-success-50',
                        2 => 'text-info-500 bg-info-50',
                        3 => 'text-warning-500 bg-warning-50',
                        4 => 'text-danger-500 bg-danger-50',
                        5 => 'text-purple-500 bg-purple-50',
                        default => 'text-gray-500 bg-gray-50',
                    };
                    $diffStars = str_repeat('★', $mission->difficulty) . str_repeat('☆', 5 - $mission->difficulty);
                    
                    $reward = $mission->reward_achievement_id ? $achievements->get($mission->reward_achievement_id) : null;
                @endphp
                <div class="relative overflow-hidden p-6 rounded-2xl flex flex-col justify-between transition-all duration-300 bg-success-50 ring-2 ring-success-500 shadow-lg shadow-success-100/50 dark:bg-success-950/10 dark:ring-success-500/30 dark:shadow-none">
                    
                    <!-- Done Stamp Micro-animation -->
                    <div class="absolute -right-2 top-8 transform rotate-12 z-10 pointer-events-none animate-bounce" style="animation-duration: 3s;">
                        <span class="border-4 border-success-500 text-success-600 dark:text-success-400 font-black text-sm uppercase tracking-widest px-3 py-1 rounded-lg">DONE</span>
                    </div>

                    <div>
                        <div class="flex items-start justify-between gap-3 mb-4">
                            <div class="p-3 rounded-2xl bg-success-500 text-white shadow-md shadow-success-500/30">
                                <x-filament::icon icon="heroicon-s-check-circle" class="w-8 h-8" />
                            </div>
                            <div class="text-right">
                                <span class="text-[10px] font-bold uppercase tracking-wider px-2 py-1 rounded-md {{ $diffColor }} dark:bg-opacity-10">Độ khó: {{ $diffStars }}</span>
                            </div>
                        </div>
                        
                        <h3 class="text-base font-bold text-gray-900 dark:text-white mb-2">{{ $mission->title }}</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-6 leading-relaxed opacity-80">{{ $mission->description }}</p>
                    </div>

                    @if($reward)
                        <div class="mb-4 p-3 bg-white dark:bg-gray-800/80 rounded-xl flex items-center gap-3 border border-success-100 dark:border-success-900/50">
                            <div class="w-10 h-10 rounded-lg bg-warning-500 text-white flex items-center justify-center shrink-0 shadow-sm animate-pulse">
                                <x-filament::icon icon="heroicon-s-trophy" class="w-5 h-5" />
                            </div>
                            <div class="flex-1 min-w-0">
                                <span class="text-[9px] font-bold text-warning-600 dark:text-warning-400 uppercase block">Đã nhận Huy Hiệu!</span>
                                <span class="text-xs font-bold text-gray-900 dark:text-white block truncate">{{ $reward->name }}</span>
                            </div>
                            <a href="{{ \App\Filament\Player\Pages\AchievementsPage::getUrl() }}" class="text-[10px] font-extrabold text-success-600 dark:text-success-400 hover:underline shrink-0">
                                Xem Danh hiệu
                            </a>
                        </div>
                    @endif
                    
                    <div class="mt-auto">
                        <div class="flex justify-between text-[11px] font-bold mb-2 text-success-700 dark:text-success-400">
                            <span>Hoàn tất</span>
                            <span>{{ $mCurrent }}/{{ $mTarget }} (100%)</span>
                        </div>
                        <div class="w-full bg-success-200 dark:bg-success-900/40 h-2.5 rounded-full overflow-hidden mb-4">
                            <div class="bg-success-500 h-full rounded-full transition-all duration-500" style="width: 100%"></div>
                        </div>
                        
                        <div class="block w-full py-2.5 text-center rounded-xl text-xs font-bold bg-success-500 text-white shadow-sm">
                            Đã Hoàn Thành
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
    
    <!-- DAILY & SEASON MISSIONS -->
    @if($dailyMissions->count() > 0 || $seasonMissions->count() > 0)
    <div class="mt-12 grid grid-cols-1 lg:grid-cols-2 gap-8">
        @if($dailyMissions->count() > 0)
        <div>
            <h2 class="text-base font-bold text-gray-950 dark:text-white mb-4 flex items-center gap-2">
                <x-filament::icon icon="heroicon-s-sun" class="w-5 h-5 text-primary-500" />
                Nhiệm Vụ Hàng Ngày
            </h2>
            <div class="space-y-4">
                @foreach($dailyMissions as $mission)
                    @php
                        $missionProgress = $userMissions->get($mission->id) ?? null;
                        $mCurrent = $missionProgress ? $missionProgress->current_value : 0;
                        $mTarget = $mission->target_value;
                        $mPercent = max(0, min(100, $mTarget > 0 ? round(($mCurrent / $mTarget) * 100) : 0));
                        $mCompleted = $missionProgress ? $missionProgress->is_completed : false;
                    @endphp
                    <div class="p-4 rounded-xl ring-1 {{ $mCompleted ? 'bg-success-50 ring-success-200 dark:bg-success-900/20' : 'bg-white ring-gray-950/5 dark:bg-gray-900' }} flex items-center justify-between gap-4">
                        <div class="flex-1">
                            <h3 class="text-sm font-bold text-gray-900 dark:text-white">{{ $mission->title }}</h3>
                            <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-1">{{ $mission->description }}</p>
                        </div>
                        <div class="w-24 shrink-0 text-right">
                            <span class="text-[10px] font-bold block mb-1 {{ $mCompleted ? 'text-success-600' : 'text-gray-500' }}">{{ $mCurrent }}/{{ $mTarget }}</span>
                            <div class="w-full bg-gray-100 dark:bg-gray-800 h-1.5 rounded-full overflow-hidden">
                                <div class="{{ $mCompleted ? 'bg-success-500' : 'bg-primary-500' }} h-full rounded-full transition-all duration-500" style="width: {{ $mPercent }}%"></div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
        @endif
        
        @if($seasonMissions->count() > 0)
        <div>
            <h2 class="text-base font-bold text-gray-950 dark:text-white mb-4 flex items-center gap-2">
                <x-filament::icon icon="heroicon-s-globe-americas" class="w-5 h-5 text-indigo-500" />
                Nhiệm Vụ Mùa Giải
            </h2>
            <div class="space-y-4">
                @foreach($seasonMissions as $mission)
                    @php
                        $missionProgress = $userMissions->get($mission->id) ?? null;
                        $mCurrent = $missionProgress ? $missionProgress->current_value : 0;
                        $mTarget = $mission->target_value;
                        $mPercent = max(0, min(100, $mTarget > 0 ? round(($mCurrent / $mTarget) * 100) : 0));
                        $mCompleted = $missionProgress ? $missionProgress->is_completed : false;
                    @endphp
                    <div class="p-4 rounded-xl ring-1 {{ $mCompleted ? 'bg-success-50 ring-success-200 dark:bg-success-900/20' : 'bg-white ring-gray-950/5 dark:bg-gray-900' }} flex items-center justify-between gap-4">
                        <div class="flex-1">
                            <h3 class="text-sm font-bold text-gray-900 dark:text-white">{{ $mission->title }}</h3>
                            <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-1">{{ $mission->description }}</p>
                        </div>
                        <div class="w-24 shrink-0 text-right">
                            <span class="text-[10px] font-bold block mb-1 {{ $mCompleted ? 'text-success-600' : 'text-gray-500' }}">{{ $mCurrent }}/{{ $mTarget }}</span>
                            <div class="w-full bg-gray-100 dark:bg-gray-800 h-1.5 rounded-full overflow-hidden">
                                <div class="{{ $mCompleted ? 'bg-success-500' : 'bg-primary-500' }} h-full rounded-full transition-all duration-500" style="width: {{ $mPercent }}%"></div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>
    @endif
</x-filament-panels::page>
