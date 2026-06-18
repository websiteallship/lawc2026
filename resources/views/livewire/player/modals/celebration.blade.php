@php $hasAchievements = $hasAchievements ?? false; $hasMissions = $hasMissions ?? false; $achievements = $achievements ?? collect(); $missions = $missions ?? collect(); @endphp
<div
    x-data="{
        show: false,
        hasAchievements: @js(($hasAchievements ?? false)),
        hasMissions: @js(($hasMissions ?? false)),
        fireConfetti(type) {
            if (typeof window.fireConfetti === 'function') {
                window.fireConfetti(type);
            }
        },
        init() {
            setTimeout(() => {
                this.show = true;
                this.$nextTick(() => {
                    if (this.hasAchievements) this.fireConfetti('achievement');
                    if (this.hasMissions) setTimeout(() => this.fireConfetti('fireworks'), 800);
                });
            }, 500);
        }
    }"
    x-show="show"
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0 scale-95"
    x-transition:enter-end="opacity-100 scale-100"
    class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-sm"
>
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-md w-full max-h-[90vh] flex flex-col overflow-hidden relative">

        {{-- Decorative top bar --}}
        <div class="h-1.5 w-full bg-gradient-to-r from-yellow-400 via-orange-400 to-pink-500"></div>

        {{-- Header --}}
        <div class="p-6 pb-4 text-center bg-gradient-to-b from-amber-50 to-white dark:from-gray-800 dark:to-gray-800">
            @if(($hasAchievements ?? false) && ($hasMissions ?? false))
                <x-filament::icon icon="heroicon-s-star" class="w-16 h-16 mx-auto mb-3 text-amber-500 animate-bounce" />
                <h2 class="text-2xl font-extrabold text-gray-900 dark:text-white">Xuất sắc!</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Thành tựu mới & nhiệm vụ hoàn thành!</p>
            @elseif($hasAchievements ?? false)
                <x-filament::icon icon="heroicon-s-trophy" class="w-16 h-16 mx-auto mb-3 text-amber-500 animate-bounce" />
                <h2 class="text-2xl font-extrabold text-gray-900 dark:text-white">Thành Tựu Mới!</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Chúc mừng bạn đã đạt được thành tựu!</p>
            @else
                <x-filament::icon icon="heroicon-s-check-badge" class="w-16 h-16 mx-auto mb-3 text-emerald-500 animate-bounce" />
                <h2 class="text-2xl font-extrabold text-gray-900 dark:text-white">Nhiệm Vụ Hoàn Thành!</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Bạn đã hoàn thành nhiệm vụ xuất sắc!</p>
            @endif
        </div>

        {{-- Body --}}
        <div class="p-6 overflow-y-auto flex-1 space-y-5">

            {{-- Achievements section --}}
            @if($hasAchievements ?? false)
            <div>
                <h3 class="text-xs font-bold uppercase tracking-widest text-amber-600 dark:text-amber-400 mb-3 flex items-center gap-2">
                    <x-filament::icon icon="heroicon-s-trophy" class="w-4 h-4" />
                    Thành Tựu Vừa Đạt
                </h3>
                <div class="space-y-3">
                    @foreach(($achievements ?? []) as $ua)
                    <div
                        class="flex items-center gap-4 p-4 rounded-xl border border-amber-200 dark:border-amber-700/50 bg-amber-50 dark:bg-amber-900/20 achievement-glow"
                        style="animation-delay: {{ $loop->index * 200 }}ms"
                    >
                        <div class="w-12 h-12 rounded-full bg-gradient-to-br from-yellow-400 to-orange-500 flex items-center justify-center shadow-lg flex-shrink-0 badge-bounce-in"
                             style="animation-delay: {{ $loop->index * 200 + 100 }}ms">
                            @if($ua->achievement->icon)
                                @if(str_starts_with($ua->achievement->icon, 'heroicon-'))
                                    <x-filament::icon :icon="$ua->achievement->icon" class="w-6 h-6 text-white" />
                                @else
                                    <span class="text-2xl">{{ $ua->achievement->icon }}</span>
                                @endif
                            @else
                                <x-filament::icon icon="heroicon-s-trophy" class="w-6 h-6 text-white" />
                            @endif
                        </div>
                        <div class="min-w-0">
                            <div class="font-bold text-gray-900 dark:text-white text-sm truncate">
                                {{ $ua->achievement->name ?? 'Thành tựu' }}
                            </div>
                            @if($ua->achievement?->description)
                            <div class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 truncate">
                                {{ $ua->achievement->description }}
                            </div>
                            @endif
                            <div class="text-xs text-amber-600 dark:text-amber-400 mt-1">
                                Đạt lúc {{ $ua->awarded_at->timezone('Asia/Ho_Chi_Minh')->format('H:i d/m') }}
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Divider --}}
            @if($hasAchievements && $hasMissions)
            <div class="flex items-center gap-3">
                <div class="flex-1 h-px bg-gray-200 dark:bg-gray-700"></div>
                <span class="text-xs text-gray-400">và</span>
                <div class="flex-1 h-px bg-gray-200 dark:bg-gray-700"></div>
            </div>
            @endif

            {{-- Missions section --}}
            @if($hasMissions ?? false)
            <div>
                <h3 class="text-xs font-bold uppercase tracking-widest text-emerald-600 dark:text-emerald-400 mb-3 flex items-center gap-2">
                    <x-filament::icon icon="heroicon-s-check-badge" class="w-4 h-4" />
                    Nhiệm Vụ Hoàn Thành
                </h3>
                <div class="space-y-3">
                    @foreach(($missions ?? []) as $um)
                    <div class="flex items-center gap-4 p-4 rounded-xl border border-emerald-200 dark:border-emerald-700/50 bg-emerald-50 dark:bg-emerald-900/20">
                        {{-- Animated checkmark SVG --}}
                        <div class="w-12 h-12 flex-shrink-0 flex items-center justify-center rounded-full bg-emerald-500">
                            <svg class="w-7 h-7 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="20 6 9 17 4 12" class="check-draw" />
                            </svg>
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="font-bold text-gray-900 dark:text-white text-sm truncate">
                                {{ $um->mission->title ?? 'Nhiệm vụ' }}
                            </div>
                            @if($um->mission?->description)
                            <div class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 truncate">
                                {{ $um->mission->description }}
                            </div>
                            @endif
                            @if($um->mission?->rewardAchievement)
                            <div class="text-xs text-emerald-600 dark:text-emerald-400 mt-1 flex items-center gap-1">
                                <x-filament::icon icon="heroicon-s-gift" class="w-3 h-3" />
                                Phần thưởng: {{ $um->mission->rewardAchievement->name }}
                            </div>
                            @endif
                            <div class="text-xs text-gray-400 dark:text-gray-500 mt-1">
                                Hoàn thành lúc {{ $um->completed_at->timezone('Asia/Ho_Chi_Minh')->format('H:i d/m') }}
                            </div>
                        </div>
                        <x-filament::icon icon="heroicon-s-sparkles" class="w-6 h-6 text-emerald-500 flex-shrink-0" />
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

        </div>

        {{-- Footer --}}
        <div class="p-4 border-t border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50 flex justify-between items-center gap-3">
            <a href="{{ route('filament.player.pages.achievements') }}"
               class="text-sm font-medium text-primary-600 dark:text-primary-400 hover:underline flex items-center gap-1"
               wire:click="dismiss">
                <x-filament::icon icon="heroicon-o-arrow-right" class="w-4 h-4" />
                Xem thành tựu
            </a>
            <button
                wire:click="dismiss"
                class="px-6 py-2 text-sm font-bold text-white bg-gradient-to-r from-amber-500 to-orange-500 hover:from-amber-400 hover:to-orange-400 rounded-xl transition-all shadow-md shadow-amber-500/30 active:scale-95">
                Tuyệt vời!
            </button>
        </div>

    </div>
</div>


