@php
    $isUnlocked = in_array($achievement->id, $userAchievements);
    $color = $achievement->color ?: 'primary';
    
    // progress calculations
    $current = $progress['current'] ?? 0;
    $target = $progress['target'] ?? 1;
    $percent = max(0, min(100, $target > 0 ? round(($current / $target) * 100) : 0));
    
    $compact = $compact ?? false;

    // Hardcode styling class combinations to guarantee tailwind compiler picks them up
    $colorStyles = [
        'primary' => [
            'ring' => 'ring-primary-500/50 dark:ring-primary-400/50',
            'border' => 'border-primary-500 dark:border-primary-400',
            'text' => 'text-primary-600 dark:text-primary-400',
            'bg' => 'bg-primary-50/50 dark:bg-primary-950/20',
            'badge' => 'bg-primary-50 text-primary-700 dark:bg-primary-500/10 dark:text-primary-400',
            'progress' => 'bg-primary-500 dark:bg-primary-400',
        ],
        'success' => [
            'ring' => 'ring-success-500/50 dark:ring-success-400/50',
            'border' => 'border-success-500 dark:border-success-400',
            'text' => 'text-success-600 dark:text-success-400',
            'bg' => 'bg-success-50/50 dark:bg-success-950/20',
            'badge' => 'bg-success-50 text-success-700 dark:bg-success-500/10 dark:text-success-400',
            'progress' => 'bg-success-500 dark:bg-success-400',
        ],
        'warning' => [
            'ring' => 'ring-warning-500/50 dark:ring-warning-400/50',
            'border' => 'border-warning-500 dark:border-warning-400',
            'text' => 'text-warning-600 dark:text-warning-400',
            'bg' => 'bg-warning-50/50 dark:bg-warning-950/20',
            'badge' => 'bg-warning-50 text-warning-700 dark:bg-warning-500/10 dark:text-warning-400',
            'progress' => 'bg-warning-500 dark:bg-warning-400',
        ],
        'danger' => [
            'ring' => 'ring-danger-500/50 dark:ring-danger-400/50',
            'border' => 'border-danger-500 dark:border-danger-400',
            'text' => 'text-danger-600 dark:text-danger-400',
            'bg' => 'bg-danger-50/50 dark:bg-danger-950/20',
            'badge' => 'bg-danger-50 text-danger-700 dark:bg-danger-500/10 dark:text-danger-400',
            'progress' => 'bg-danger-500 dark:bg-danger-400',
        ],
        'info' => [
            'ring' => 'ring-info-500/50 dark:ring-info-400/50',
            'border' => 'border-info-500 dark:border-info-400',
            'text' => 'text-info-600 dark:text-info-400',
            'bg' => 'bg-info-50/50 dark:bg-info-950/20',
            'badge' => 'bg-info-50 text-info-700 dark:bg-info-500/10 dark:text-info-400',
            'progress' => 'bg-info-500 dark:bg-info-400',
        ],
    ];

    $style = $colorStyles[$color] ?? $colorStyles['primary'];
@endphp

<div class="relative overflow-hidden bg-white dark:bg-gray-900 rounded-2xl p-6 flex flex-col justify-between items-center text-center cursor-pointer transition-all duration-300 hover:scale-102 shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 hover:ring-2 {{ $isUnlocked ? 'ring-2 ' . $style['ring'] . ' shadow-md shadow-gray-200/50 dark:shadow-none' : 'hover:ring-primary-500' }}">
    <!-- Background subtle glow for unlocked achievements -->
    @if($isUnlocked)
        <div class="absolute -right-8 -top-8 w-20 h-20 {{ $style['bg'] }} rounded-full blur-2xl pointer-events-none"></div>
    @endif

    <div class="flex flex-col items-center w-full">
        <!-- Icon container -->
        <div class="mb-4 p-3.5 rounded-2xl transition duration-300 {{ $isUnlocked ? $style['bg'] : 'bg-gray-50 dark:bg-gray-800/50' }}">
            <x-filament::icon
                icon="{{ $achievement->icon }}"
                class="{{ $compact ? 'w-10 h-10' : 'w-14 h-14' }} {{ $isUnlocked ? $style['text'] : 'text-gray-400 dark:text-gray-500' }} drop-shadow"
            />
        </div>
        
        <!-- Level label (for main levels) -->
        @if($achievement->level)
            <span class="text-[10px] font-bold uppercase tracking-widest {{ $isUnlocked ? $style['text'] : 'text-gray-400 dark:text-gray-500' }} mb-1">
                CẤP {{ $achievement->level }}
            </span>
        @endif

        <!-- Title -->
        <h3 class="{{ $compact ? 'text-sm' : 'text-base' }} font-bold tracking-tight text-gray-900 dark:text-white mb-2">
            {{ $achievement->name }}
        </h3>
        
        <!-- Description -->
        <p class="text-xs text-gray-500 dark:text-gray-400 leading-relaxed mb-4 min-h-[32px]">
            {{ $achievement->description }}
        </p>
    </div>

    <!-- Progress & Action button -->
    <div class="w-full mt-auto pt-2">
        @php
            $mission = isset($missions) ? $missions->where('reward_achievement_id', $achievement->id)->first() : null;
            $missionProgress = $mission ? ($userMissions->get($mission->id) ?? null) : null;
            $mCurrent = $missionProgress ? $missionProgress->current_value : 0;
            $mTarget = $mission ? $mission->target_value : 1;
            $mPercent = $mission ? max(0, min(100, $mTarget > 0 ? round(($mCurrent / $mTarget) * 100) : 0)) : 0;
        @endphp

        <!-- Progress bar shown when locked -->
        @if(!$isUnlocked && $mission)
            <div class="space-y-1.5 mb-4 text-left">
                <div class="flex justify-between text-[10px] font-bold text-gray-700 dark:text-gray-300">
                    <span class="truncate pr-2">Nhiệm vụ: {{ $mission->title }}</span>
                    <span>{{ $mCurrent }}/{{ $mTarget }}</span>
                </div>
                <div class="w-full bg-gray-100 dark:bg-gray-850 h-2 rounded-full overflow-hidden">
                    <div class="bg-warning-500 h-full rounded-full transition-all duration-500" style="width: {{ $mPercent }}%"></div>
                </div>
                <a href="/player" class="mt-2 block w-full py-1.5 text-center rounded-lg text-xs font-bold bg-primary-50 text-primary-600 hover:bg-primary-100 dark:bg-primary-500/10 dark:text-primary-400 dark:hover:bg-primary-500/20 transition-colors">
                    Thực hiện ngay
                </a>
            </div>
        @elseif(!$isUnlocked && $progress)
            @php $subConditions = $progress['sub_conditions'] ?? null; @endphp

            @if($subConditions)
                {{-- Multi-condition progress bars --}}
                <div class="space-y-2 mb-4 text-left">
                    @foreach($subConditions as $cond)
                        @php
                            $isBool   = $cond['bool'] ?? false;
                            $cSuffix  = $cond['suffix'] ?? '';
                            $cCurrent = $cond['current'];
                            $cTarget  = $cond['target'];
                            $cPct     = max(0, min(100, $cTarget > 0 ? round($cCurrent / $cTarget * 100) : 0));
                            $cDone    = $cCurrent >= $cTarget;
                        @endphp
                        <div>
                            <div class="flex justify-between items-center mb-0.5">
                                <span class="text-[10px] font-medium {{ $cDone ? 'text-success-600 dark:text-success-400' : 'text-gray-500 dark:text-gray-400' }}">
                                    {{ $cond['label'] }}
                                </span>
                                @if($isBool)
                                    <x-filament::icon
                                        :icon="$cDone ? 'heroicon-m-check-circle' : 'heroicon-m-x-circle'"
                                        class="w-3.5 h-3.5 {{ $cDone ? 'text-success-500' : 'text-gray-400' }}"
                                    />
                                @else
                                    <span class="text-[10px] font-semibold {{ $cDone ? 'text-success-600 dark:text-success-400' : 'text-gray-500 dark:text-gray-400' }}">
                                        {{ $cCurrent }}{{ $cSuffix }}/{{ $cTarget }}{{ $cSuffix }}
                                    </span>
                                @endif
                            </div>
                            @if(!$isBool)
                                <div class="w-full bg-gray-100 dark:bg-gray-800 h-1.5 rounded-full overflow-hidden">
                                    <div class="h-full rounded-full transition-all duration-500 {{ $cDone ? 'bg-success-500 dark:bg-success-400' : 'bg-gray-400 dark:bg-gray-600' }}"
                                         style="width: {{ $cPct }}%"></div>
                                </div>
                            @else
                                <div class="w-full bg-gray-100 dark:bg-gray-800 h-1.5 rounded-full overflow-hidden">
                                    <div class="h-full rounded-full transition-all duration-500 {{ $cDone ? 'bg-success-500' : 'bg-gray-300' }}"
                                         style="width: {{ $cDone ? 100 : 0 }}%"></div>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @else
                {{-- Single progress bar --}}
                <div class="space-y-1.5 mb-4">
                    <div class="flex justify-between text-[10px] font-semibold text-gray-500 dark:text-gray-400">
                        <span>Tiến độ</span>
                        <span>{{ $current }}/{{ $target }} ({{ $percent }}%)</span>
                    </div>
                    <div class="w-full bg-gray-100 dark:bg-gray-850 h-2 rounded-full overflow-hidden">
                        <div class="bg-gray-400 dark:bg-gray-600 h-full rounded-full transition-all duration-500" style="width: {{ $percent }}%"></div>
                    </div>
                </div>
            @endif
        @endif

        @if($isUnlocked)
            <div class="w-full py-1.5 inline-flex items-center justify-center gap-1.5 px-3 rounded-lg text-xs font-semibold {{ $style['badge'] }}">
                <x-filament::icon icon="heroicon-m-check-badge" class="w-4 h-4" />
                <span>Đã Đạt Được</span>
            </div>
        @else
            <div class="w-full py-1.5 inline-flex items-center justify-center gap-1.5 px-3 rounded-lg text-xs font-semibold bg-gray-50 text-gray-500 dark:bg-gray-800 dark:text-gray-400">
                <x-filament::icon icon="heroicon-m-lock-closed" class="w-4 h-4" />
                <span>Chưa Đạt</span>
            </div>
        @endif
    </div>
</div>
