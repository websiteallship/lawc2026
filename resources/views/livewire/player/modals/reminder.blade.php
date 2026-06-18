@php
    $matches = $matches ?? collect();
    $count = count($matches);
    $isToast = $count === 1;
@endphp
<div
    x-data="{ show: false }"
    x-init="setTimeout(() => show = true, 300)"
    x-show="show"
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0 {{ $isToast ? 'translate-y-8' : 'scale-95' }}"
    x-transition:enter-end="opacity-100 {{ $isToast ? 'translate-y-0' : 'scale-100' }}"
    class="fixed z-50 {{ $isToast ? 'bottom-4 left-4 right-4 sm:left-auto sm:right-4 flex justify-end pointer-events-none' : 'inset-0 flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-sm' }}"
    x-cloak
>
    <div class="pointer-events-auto bg-white dark:bg-gray-800 rounded-2xl shadow-2xl flex flex-col overflow-hidden {{ $isToast ? 'w-full sm:w-96 border border-gray-100 dark:border-gray-700' : 'max-w-md w-full max-h-[90vh]' }}">

        {{-- Header --}}
        <div class="p-4 bg-gradient-to-r from-danger-600 to-rose-500 text-white flex items-center gap-3">
            <div class="w-10 h-10 rounded-full bg-white/20 flex items-center justify-center flex-shrink-0 animate-pulse">
                <x-filament::icon icon="heroicon-s-bell-alert" class="w-6 h-6" />
            </div>
            <div>
                <h2 class="text-base font-extrabold uppercase tracking-wide">Đừng lỡ trận này!</h2>
                <p class="text-white/80 text-xs mt-0.5">Bạn chưa dự đoán trận đấu sắp diễn ra</p>
            </div>
        </div>

        {{-- Match list --}}
        <div class="{{ $isToast ? 'p-4' : 'p-4 overflow-y-auto flex-1' }} space-y-3">
            @foreach($matches as $match)
                @php
                    $minutesLeft = now()->diffInMinutes($match->kickoff_at, false);
                    $timeLeftStr = $minutesLeft > 60
                        ? floor($minutesLeft / 60) . ' giờ ' . ($minutesLeft % 60) . ' phút'
                        : $minutesLeft . ' phút';
                    $isUrgent = $minutesLeft < 30;
                @endphp
                <div class="p-3 rounded-xl border border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50">
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-xs font-bold {{ $isUrgent ? 'text-danger-600 dark:text-danger-400 animate-pulse' : 'text-gray-500' }}">
                            Đóng kèo sau: {{ $timeLeftStr }}
                        </span>
                    </div>
                    <div class="flex items-center gap-2 justify-between">
                        <div class="text-sm font-bold text-gray-900 dark:text-white truncate">
                            {{ $match->home_team }}
                        </div>
                        <span class="text-xs text-gray-400 font-bold px-1">vs</span>
                        <div class="text-sm font-bold text-gray-900 dark:text-white truncate">
                            {{ $match->away_team }}
                        </div>
                    </div>

                    <div class="mt-3 flex gap-2">
                        <a href="{{ url('/player/match/' . $match->id) }}"
                           wire:click="dismissReminder({{ $match->id }})"
                           class="flex-1 flex justify-center items-center py-1.5 px-3 rounded-lg bg-primary-600 hover:bg-primary-500 text-white text-xs font-bold transition-colors">
                            Dự đoán ngay
                        </a>
                        <button wire:click="snoozeReminder({{ $match->id }})"
                                class="flex justify-center items-center py-1.5 px-3 rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 text-gray-600 dark:text-gray-300 text-xs font-bold transition-colors">
                            Nhắc lại sau
                        </button>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Footer actions (only for modal view) --}}
        @if(!$isToast)
            <div class="p-4 border-t border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50 flex justify-end">
                <button wire:click="dismiss"
                        class="text-sm font-medium text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                    Bỏ qua tất cả
                </button>
            </div>
        @else
            <button wire:click="dismiss" class="absolute top-3 right-3 text-white/70 hover:text-white">
                <x-filament::icon icon="heroicon-o-x-mark" class="w-5 h-5" />
            </button>
        @endif

    </div>
</div>
