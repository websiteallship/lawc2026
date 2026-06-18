@php 
    $daysAbsent = $daysAbsent ?? 3; 
    $openMatchesCount = $openMatchesCount ?? 0;
@endphp
<div
    x-data="{ show: false }"
    x-init="setTimeout(() => show = true, 400)"
    x-show="show"
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0 translate-y-4"
    x-transition:enter-end="opacity-100 translate-y-0"
    class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-sm"
    x-cloak
>
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-sm w-full overflow-hidden text-center">

        {{-- Header Illustration --}}
        <div class="bg-gradient-to-br from-primary-500 to-indigo-600 p-8 flex flex-col items-center justify-center text-white">
            <div class="w-16 h-16 bg-white/20 rounded-full flex items-center justify-center mb-3 animate-bounce">
                <x-filament::icon icon="heroicon-s-hand-raised" class="w-8 h-8 text-white" />
            </div>
            <h2 class="text-xl font-extrabold tracking-wide">Lâu rồi không gặp!</h2>
            <p class="text-primary-100 text-sm mt-1">Bạn đã vắng mặt {{ $daysAbsent }} ngày...</p>
        </div>

        {{-- Body --}}
        <div class="p-6 space-y-4">
            @if($openMatchesCount > 0)
            <div class="p-4 bg-primary-50 dark:bg-primary-900/20 rounded-xl border border-primary-100 dark:border-primary-800 flex items-center gap-4 text-left">
                <div class="w-10 h-10 rounded-full bg-primary-100 dark:bg-primary-800 flex items-center justify-center flex-shrink-0">
                    <x-filament::icon icon="heroicon-s-play" class="w-5 h-5 text-primary-600 dark:text-primary-400" />
                </div>
                <div>
                    <div class="font-bold text-gray-900 dark:text-white">Đang có {{ $openMatchesCount }} trận chờ dự đoán!</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Vào xem kèo ngay kẻo lỡ.</div>
                </div>
            </div>
            @endif
            
            <div class="p-4 bg-amber-50 dark:bg-amber-900/20 rounded-xl border border-amber-100 dark:border-amber-800 flex items-center gap-4 text-left">
                <div class="w-10 h-10 rounded-full bg-amber-100 dark:bg-amber-800 flex items-center justify-center flex-shrink-0">
                    <x-filament::icon icon="heroicon-s-check-badge" class="w-5 h-5 text-amber-600 dark:text-amber-400" />
                </div>
                <div>
                    <div class="font-bold text-gray-900 dark:text-white">Nhiệm vụ tuần đang diễn ra</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Tích luỹ thành tựu để đua top nào.</div>
                </div>
            </div>
        </div>

        {{-- Footer --}}
        <div class="p-4 pt-0 flex gap-3">
            <button
                wire:click="dismiss"
                class="flex-1 py-2.5 px-4 text-sm font-bold text-gray-700 bg-gray-100 hover:bg-gray-200 dark:text-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 rounded-xl transition-colors"
            >
                Nhắc lại sau
            </button>
            <a
                href="{{ url('/player/matches') }}"
                wire:click="dismiss"
                class="flex-1 py-2.5 px-4 text-sm font-bold text-white bg-primary-600 hover:bg-primary-500 rounded-xl transition-colors shadow-md shadow-primary-500/20 flex items-center justify-center"
            >
                Vào xem ngay
            </a>
        </div>

    </div>
</div>
