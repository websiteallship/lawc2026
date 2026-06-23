@php
    $bets           = $bets ?? collect();
    $byMatch        = $byMatch ?? [];
    $totalNetResult = $totalNetResult ?? 0;
    $hasBets        = $hasBets ?? false;

    $headerBg = match(true) {
        $totalNetResult > 0 => 'from-emerald-600 to-teal-500',
        $totalNetResult < 0 => 'from-red-600 to-rose-500',
        default             => 'from-gray-600 to-gray-500',
    };
    $headerIcon = match(true) {
        $totalNetResult > 0 => 'heroicon-s-trophy',
        $totalNetResult < 0 => 'heroicon-s-face-frown',
        default             => 'heroicon-s-scale',
    };
    $headerTitle = match(true) {
        $totalNetResult > 0 => 'Phiên này thắng rồi!',
        $totalNetResult < 0 => 'Phiên này chưa may mắn',
        default             => 'Kết quả phiên này',
    };
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
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-lg w-full max-h-[90vh] flex flex-col overflow-hidden">

        {{-- Gradient header --}}
        <div class="p-5 pb-4 bg-gradient-to-r {{ $headerBg }} text-white">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-white/20 flex items-center justify-center flex-shrink-0">
                    <x-filament::icon :icon="$headerIcon" class="w-6 h-6" />
                </div>
                <div>
                    <h2 class="text-lg font-extrabold">{{ $headerTitle }}</h2>
                    <p class="text-white/80 text-xs mt-0.5">Kết quả vé dự đoán vừa được mở thưởng</p>
                </div>
            </div>

            {{-- Total net result --}}
            <div class="mt-4 bg-white/15 backdrop-blur rounded-xl p-3 flex items-center justify-between">
                <span class="text-sm font-medium text-white/80">Tổng kết phiên này</span>
                <span class="text-2xl font-black">
                    {{ $totalNetResult >= 0 ? '+' : '' }}{{ number_format($totalNetResult) }} lá
                </span>
            </div>
        </div>

        {{-- Bet list --}}
        <div class="flex-1 overflow-y-auto p-4 space-y-4">

            @if(!$hasBets)
                <div class="text-center py-8 text-gray-400 dark:text-gray-500 text-sm">
                    Không có vé nào cần tổng kết.
                </div>
            @else
                @foreach($byMatch as $group)
                <div>
                    {{-- Match label --}}
                    <div class="flex items-center gap-2 mb-2">
                        <x-filament::icon icon="heroicon-o-ticket" class="w-4 h-4 text-gray-400" />
                        <span class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 truncate">
                            {{ $group['match_label'] }}
                        </span>
                        @php $sub = $group['subtotal']; @endphp
                        <span class="ml-auto text-xs font-bold {{ $sub > 0 ? 'text-emerald-500' : ($sub < 0 ? 'text-red-500' : 'text-gray-400') }}">
                            {{ $sub >= 0 ? '+' : '' }}{{ number_format($sub) }}
                        </span>
                    </div>

                    {{-- Bets in this match --}}
                    <div class="space-y-2">
                        @foreach($group['bets'] as $bet)
                        @php
                            $bStatus = $bet->status instanceof \UnitEnum ? $bet->status : \App\Enums\BetStatus::tryFrom($bet->status ?? '');
                            $statusConfig = match($bStatus) {
                                \App\Enums\BetStatus::WON       => ['bg'=>'bg-emerald-50 dark:bg-emerald-900/20','border'=>'border-emerald-200 dark:border-emerald-700/50','icon'=>'heroicon-s-check-circle','iconColor'=>'text-emerald-500','label'=>'THẮNG','labelColor'=>'text-emerald-600 dark:text-emerald-400'],
                                \App\Enums\BetStatus::HALF_WON  => ['bg'=>'bg-emerald-50/60 dark:bg-emerald-900/10','border'=>'border-emerald-200/60 dark:border-emerald-700/30','icon'=>'heroicon-s-check-circle','iconColor'=>'text-emerald-400','label'=>'THẮNG ½','labelColor'=>'text-emerald-500 dark:text-emerald-400'],
                                \App\Enums\BetStatus::LOST       => ['bg'=>'bg-red-50 dark:bg-red-900/20','border'=>'border-red-200 dark:border-red-700/50','icon'=>'heroicon-s-x-circle','iconColor'=>'text-red-500','label'=>'THUA','labelColor'=>'text-red-600 dark:text-red-400'],
                                \App\Enums\BetStatus::HALF_LOST  => ['bg'=>'bg-red-50/60 dark:bg-red-900/10','border'=>'border-red-200/60 dark:border-red-700/30','icon'=>'heroicon-s-x-circle','iconColor'=>'text-red-400','label'=>'THUA ½','labelColor'=>'text-red-500 dark:text-red-400'],
                                \App\Enums\BetStatus::PUSH       => ['bg'=>'bg-gray-50 dark:bg-gray-700/30','border'=>'border-gray-200 dark:border-gray-600','icon'=>'heroicon-s-minus-circle','iconColor'=>'text-gray-400','label'=>'HOÀ VỐN','labelColor'=>'text-gray-500 dark:text-gray-400'],
                                \App\Enums\BetStatus::CORRECTED  => [
                                    'bg'=>'bg-amber-50 dark:bg-amber-900/20',
                                    'border'=>'border-amber-200 dark:border-amber-700/50',
                                    'icon'=>'heroicon-s-arrow-path',
                                    'iconColor'=>'text-amber-500',
                                    'label'=> match(true) {
                                        $bet->net_result > 0 => 'ĐIỀU CHỈNH (THẮNG)',
                                        $bet->net_result < 0 && $bet->gross_payout == 0 => 'ĐIỀU CHỈNH (THUA)',
                                        $bet->net_result == 0 && $bet->gross_payout > 0 => 'ĐIỀU CHỈNH (HÒA)',
                                        default => 'ĐIỀU CHỈNH'
                                    },
                                    'labelColor'=>'text-amber-600 dark:text-amber-400'
                                ],
                                default                          => ['bg'=>'bg-gray-50 dark:bg-gray-700/30','border'=>'border-gray-200 dark:border-gray-600','icon'=>'heroicon-s-clock','iconColor'=>'text-gray-400','label'=> is_string($bet->status) ? $bet->status : ($bStatus?->value ?? 'UNKNOWN'),'labelColor'=>'text-gray-500'],
                            };
                        @endphp
                        <div class="flex items-center gap-3 p-3 rounded-xl border {{ $statusConfig['bg'] }} {{ $statusConfig['border'] }}">
                            <x-filament::icon
                                :icon="$statusConfig['icon']"
                                class="w-5 h-5 flex-shrink-0 {{ $statusConfig['iconColor'] }}" />

                            <div class="min-w-0 flex-1">
                                <div class="text-xs font-medium text-gray-900 dark:text-white truncate">
                                    {{ $bet->display_odds_snapshot ?? $bet->label_snapshot ?? '—' }}
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                    Cược: {{ number_format($bet->stake) }} lá
                                </div>
                            </div>

                            <div class="text-right flex-shrink-0">
                                <div class="text-xs font-bold {{ $statusConfig['labelColor'] }}">
                                    {{ $statusConfig['label'] }}
                                </div>
                                <div class="text-sm font-black {{ $bet->net_result > 0 ? 'text-emerald-600' : ($bet->net_result < 0 ? 'text-red-600' : 'text-gray-500') }} mt-0.5">
                                    {{ $bet->net_result >= 0 ? '+' : '' }}{{ number_format($bet->net_result) }}
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endforeach
            @endif

        </div>

        {{-- Footer --}}
        <div class="p-4 border-t border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50 flex justify-between items-center gap-3">
            <button
                wire:click="dismissAndRedirect('/player/my-bets-page')"
                class="text-sm font-medium text-primary-600 dark:text-primary-400 hover:underline flex items-center gap-1"
            >
                <x-filament::icon icon="heroicon-o-arrow-right" class="w-4 h-4" />
                Xem lịch sử cược
            </button>
            <button
                wire:click="dismiss"
                class="px-5 py-2 text-sm font-bold text-white rounded-xl transition-all active:scale-95
                    {{ $totalNetResult > 0
                        ? 'bg-gradient-to-r from-emerald-500 to-teal-500 hover:from-emerald-400 hover:to-teal-400 shadow-md shadow-emerald-500/30'
                        : 'bg-gray-700 hover:bg-gray-600 dark:bg-gray-600 dark:hover:bg-gray-500'
                    }}"
            >
                {{ $totalNetResult > 0 ? 'Tuyệt! Tiếp tục' : 'Đã hiểu' }}
            </button>
        </div>

    </div>
</div>

{{-- Fire confetti only when net positive --}}
@if($totalNetResult > 0)
<script>
    document.addEventListener('DOMContentLoaded', function() {
        setTimeout(function() {
            if (typeof window.fireConfetti === 'function') {
                window.fireConfetti('achievement');
            }
        }, 700);
    });
</script>
@endif
