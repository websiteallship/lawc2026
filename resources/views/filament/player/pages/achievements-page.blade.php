<x-filament-panels::page>
    @php
        $mainAchievements = $achievements->whereNotNull('level')->sortBy('level');
        $sideQuests = $achievements->whereNull('level');
    @endphp

    @if(isset($newAchievementsToShow) && count($newAchievementsToShow) > 0)
        <!-- Include Confetti Library -->
        <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.9.3/dist/confetti.browser.min.js"></script>
        
        <div x-data="{ 
            achievements: {{ json_encode($newAchievementsToShow->map(function($a) { return ['name' => $a->name, 'desc' => $a->description, 'icon' => 'heroicon-o-trophy']; })->toArray()) }},
            currentIndex: 0,
            showModal: false,
            triggerConfetti() {
                if (typeof confetti !== 'undefined') {
                    var duration = 3000;
                    var animationEnd = Date.now() + duration;
                    var defaults = { startVelocity: 30, spread: 360, ticks: 60, zIndex: 100 };
                    function randomInRange(min, max) { return Math.random() * (max - min) + min; }
                    var interval = setInterval(function() {
                        var timeLeft = animationEnd - Date.now();
                        if (timeLeft <= 0) { return clearInterval(interval); }
                        var particleCount = 50 * (timeLeft / duration);
                        confetti(Object.assign({}, defaults, { particleCount, origin: { x: randomInRange(0.1, 0.3), y: Math.random() - 0.2 } }));
                        confetti(Object.assign({}, defaults, { particleCount, origin: { x: randomInRange(0.7, 0.9), y: Math.random() - 0.2 } }));
                    }, 250);
                }
            },
            init() {
                if (this.achievements.length > 0) {
                    setTimeout(() => {
                        this.showModal = true;
                        this.triggerConfetti();
                    }, 500);
                }
            },
            next() {
                this.showModal = false;
                setTimeout(() => {
                    this.currentIndex++;
                    if (this.currentIndex < this.achievements.length) {
                        this.showModal = true;
                        this.triggerConfetti();
                    }
                }, 300);
            }
        }" class="relative z-50" aria-labelledby="modal-title" role="dialog" aria-modal="true" x-show="currentIndex < achievements.length" x-cloak>
            <div x-show="showModal" 
                 x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" 
                 x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" 
                 class="fixed inset-0 bg-gray-950/80 backdrop-blur-sm transition-opacity"></div>
            
            <div class="fixed inset-0 z-50 w-screen overflow-y-auto">
                <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                    <div x-show="showModal" @click.away="next()"
                         x-transition:enter="ease-out duration-500" x-transition:enter-start="opacity-0 translate-y-8 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" 
                         x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-8 sm:translate-y-0 sm:scale-95" 
                         class="relative transform overflow-hidden rounded-[2rem] bg-white dark:bg-gray-900 px-4 pb-6 pt-8 text-left shadow-2xl shadow-warning-500/20 ring-1 ring-gray-950/5 dark:ring-white/10 transition-all sm:my-8 sm:w-full sm:max-w-md sm:p-8">
                        
                        <!-- Confetti/Sparkles Decoration -->
                        <div class="absolute -top-20 -right-20 w-40 h-40 bg-warning-500/20 blur-3xl rounded-full pointer-events-none"></div>
                        <div class="absolute -bottom-20 -left-20 w-40 h-40 bg-primary-500/20 blur-3xl rounded-full pointer-events-none"></div>
                        
                        <div class="relative text-center">
                            <div class="mx-auto flex h-24 w-24 items-center justify-center rounded-full bg-warning-50 dark:bg-warning-500/10 ring-8 ring-warning-50 dark:ring-warning-500/5 mb-8 relative">
                                <x-filament::icon icon="heroicon-s-trophy" class="w-12 h-12 text-warning-500 animate-bounce" />
                                <div class="absolute inset-0 rounded-full animate-ping bg-warning-400/30 dark:bg-warning-500/20" style="animation-duration: 2s;"></div>
                            </div>
                            
                            <h3 class="text-[10px] font-black uppercase tracking-widest text-warning-500 dark:text-warning-400 mb-2">Thành Tựu Mới!</h3>
                            <h2 class="text-3xl font-extrabold leading-tight text-gray-950 dark:text-white" x-text="achievements[currentIndex]?.name"></h2>
                            
                            <div class="mt-5 bg-gray-50 dark:bg-gray-800/50 p-5 rounded-2xl ring-1 ring-gray-950/5 dark:ring-white/5">
                                <p class="text-sm font-medium text-gray-600 dark:text-gray-400" x-text="achievements[currentIndex]?.desc"></p>
                            </div>
                        </div>
                        
                        <div class="mt-8">
                            <button type="button" @click="next()" class="inline-flex w-full items-center justify-center rounded-xl bg-primary-600 px-4 py-3.5 text-sm font-bold text-white shadow-sm hover:bg-primary-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-600 transition-colors">
                                <span x-text="currentIndex < achievements.length - 1 ? 'Tiếp tục nhận (' + (currentIndex + 1) + '/' + achievements.length + ')' : 'Tuyệt vời'"></span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Dynamic Hero Progress Banner -->
    <div class="relative overflow-hidden rounded-[2rem] p-8 md:p-10 lg:p-12 bg-white dark:bg-gray-900 shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10">
        <!-- Abstract glowing circles -->
        <div class="absolute -right-10 -top-10 w-64 h-64 bg-primary-500/10 dark:bg-primary-500/20 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute -left-10 -bottom-10 w-64 h-64 bg-primary-500/5 dark:bg-primary-500/10 rounded-full blur-3xl pointer-events-none"></div>
        
        <div class="relative flex flex-col lg:flex-row items-center justify-between gap-10">
            <div class="flex items-center gap-6 md:gap-8 w-full lg:w-auto">
                <!-- Circular Badge for Current Level -->
                <div class="relative flex items-center justify-center rounded-2xl shadow-sm shrink-0 bg-gray-50 dark:bg-gray-800 ring-1 ring-gray-200 dark:ring-gray-700" style="width: 88px; height: 88px;">
                    <x-filament::icon icon="heroicon-o-academic-cap" class="text-primary-500 dark:text-primary-400" style="width: 44px; height: 44px;" />
                    <span class="absolute -bottom-3 px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-wider shadow-sm bg-primary-500 text-white" style="white-space: nowrap;">
                        LV {{ $levelStatus['currentNo'] }}
                    </span>
                </div>
                <div>
                    <span class="text-xs font-black uppercase tracking-widest block mb-1 text-primary-600 dark:text-primary-400">Cấp bậc hiện tại</span>
                    <h1 class="text-2xl md:text-4xl font-extrabold tracking-tight text-gray-950 dark:text-white">{{ $levelStatus['currentName'] }}</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-2 max-w-md">Hãy tích lũy chiến tích dự đoán để tiếp tục nâng cấp cấp bậc danh vọng của bạn.</p>
                </div>
            </div>

            <!-- Next Level Progress Tracker -->
            @if($levelStatus['nextName'])
                <div class="w-full lg:w-[450px] p-6 rounded-2xl shrink-0 bg-gray-50 dark:bg-gray-800/50 ring-1 ring-gray-200 dark:ring-gray-700/50 space-y-4">
                    <div class="flex justify-between items-center">
                        <span class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Lên Cấp {{ $levelStatus['nextNo'] }}</span>
                        <span class="text-sm font-extrabold text-primary-600 dark:text-primary-400">{{ $levelStatus['nextName'] }}</span>
                    </div>
                    
                    <div class="space-y-2">
                        <div class="w-full h-3 rounded-full overflow-hidden bg-gray-200 dark:bg-gray-700">
                            <div class="h-full rounded-full transition-all duration-500 bg-primary-500" style="width: {{ $levelStatus['nextPercent'] }}%;"></div>
                        </div>
                        <div class="flex justify-between text-[11px] font-bold text-gray-500 dark:text-gray-400 mt-1">
                            <span class="truncate max-w-[250px]">{{ $levelStatus['nextReq'] }}</span>
                            <span style="white-space: nowrap;">{{ $levelStatus['nextCurrent'] }}/{{ $levelStatus['nextTarget'] }} ({{ $levelStatus['nextPercent'] }}%)</span>
                        </div>
                    </div>
                </div>
            @else
                <div class="p-5 rounded-2xl bg-success-50 dark:bg-success-500/10 ring-1 ring-success-200 dark:ring-success-500/20 text-success-600 dark:text-success-400 text-sm font-semibold flex items-center gap-3">
                    <x-filament::icon icon="heroicon-m-sparkles" class="w-6 h-6 text-success-500 dark:text-success-400" />
                    <span>Chúc mừng! Bạn đã đạt Cấp bậc tối cao của Con Đường Danh Vọng!</span>
                </div>
            @endif
        </div>

        <!-- Horizontal Timeline/Stepper -->
        <div class="mt-12 pt-10 border-t border-gray-100 dark:border-gray-800 overflow-x-auto scrollbar-none pb-4">
            <div class="flex items-center min-w-[900px] justify-between relative px-6">
                <!-- Connection line background -->
                <div class="absolute top-6 left-12 right-12 h-[3px] z-0 bg-gray-200 dark:bg-gray-700"></div>
                
                @foreach($mainAchievements as $achievement)
                    @php
                        $lvl = $achievement->level;
                        $isCurrent = $lvl == $levelStatus['currentNo'];
                        $isUnlocked = in_array($achievement->id, $userAchievements);
                        $isNext = $lvl == $levelStatus['nextNo'];
                    @endphp
                    <div class="flex flex-col items-center z-10 relative px-2 shrink-0" style="width: 90px;">
                        <!-- Step Circle -->
                        @if($isUnlocked)
                            <div class="rounded-full flex items-center justify-center transition-all duration-300 border-[3px] shrink-0 shadow-md shadow-primary-500/20 bg-primary-500 border-primary-500 text-white"
                                 style="width: 48px; height: 48px;">
                                <x-filament::icon icon="heroicon-m-check" style="width: 24px; height: 24px;" />
                            </div>
                        @elseif($isNext)
                            <div class="rounded-full flex items-center justify-center transition-all duration-300 border-[3px] shrink-0 animate-pulse bg-white dark:bg-gray-900 border-primary-500 text-primary-600 dark:text-primary-400"
                                 style="width: 48px; height: 48px;">
                                <span class="text-sm font-bold">{{ $lvl }}</span>
                            </div>
                        @else
                            <div class="rounded-full flex items-center justify-center transition-all duration-300 border-[3px] shrink-0 bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700 text-gray-400 dark:text-gray-500"
                                 style="width: 48px; height: 48px;">
                                <span class="text-sm font-bold">{{ $lvl }}</span>
                            </div>
                        @endif
                        <span class="text-[10px] font-bold mt-3 text-center max-w-full truncate {{ $isUnlocked || $isNext ? 'text-gray-900 dark:text-white' : 'text-gray-400 dark:text-gray-500' }}">
                            {{ $achievement->name }}
                        </span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- MAIN PROGRESSION GRID -->
    <div class="mt-10">
        <h2 class="text-lg font-bold text-gray-950 dark:text-white mb-6 flex items-center gap-2">
            <x-filament::icon icon="heroicon-s-star" class="w-5 h-5 text-warning-500" />
            Cấp Bậc Danh Vọng (Main Progression)
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($mainAchievements as $achievement)
                @include('filament.player.pages.partials.achievement-card', [
                    'achievement' => $achievement, 
                    'userAchievements' => $userAchievements,
                    'progress' => $progressMap[$achievement->code] ?? null,
                    'extraStats' => $extraStats
                ])
            @endforeach
        </div>
    </div>

    <!-- SIDE QUESTS GRID -->
    <div class="mt-10">
        <h2 class="text-lg font-bold text-gray-950 dark:text-white mb-6 flex items-center gap-2">
            <x-filament::icon icon="heroicon-s-sparkles" class="w-5 h-5 text-primary-500" />
            Huy Hiệu Phụ (Side Quests)
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            @foreach($sideQuests as $achievement)
                @include('filament.player.pages.partials.achievement-card', [
                    'achievement' => $achievement, 
                    'userAchievements' => $userAchievements, 
                    'compact' => true,
                    'progress' => $progressMap[$achievement->code] ?? null,
                    'extraStats' => $extraStats
                ])
            @endforeach
        </div>
    </div>
</x-filament-panels::page>
