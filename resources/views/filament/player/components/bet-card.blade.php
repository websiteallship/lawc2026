@props(['bet', 'isAdmin' => false])

@if($isAdmin)
    @vite('resources/css/filament/player/theme.css')
@endif

<div class="relative overflow-hidden group border-l-4 rounded-xl bg-white dark:bg-gray-900 shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 p-4 {{ match($bet->status->value) {
    'WON', 'HALF_WON' => 'border-l-emerald-500',
    'LOST', 'HALF_LOST' => 'border-l-red-500',
    'PUSH' => 'border-l-blue-500',
    'VOIDED' => 'border-l-gray-400',
    default => 'border-l-amber-500',
} }}">
    <div class="flex justify-between items-start mb-3 border-b border-gray-100 dark:border-gray-800 pb-3">
        <div>
            <p class="text-xs text-gray-500 font-medium">Mã phiếu</p>
            <p class="font-mono text-sm font-bold text-gray-800 dark:text-gray-200">{{ $bet->public_code }}</p>
        </div>
        <div class="flex items-center gap-2">
            <x-filament::badge
                :color="match($bet->status->value) {
                    'WON', 'HALF_WON' => 'success',
                    'LOST', 'HALF_LOST', 'VOIDED' => 'danger',
                    'PUSH' => 'info',
                    'PENDING' => 'gray',
                    'CORRECTED' => 'warning',
                    default => 'gray'
                }"
            >
                {{ match($bet->status->value) {
                    'PENDING' => 'Chưa mở',
                    'WON' => 'Thắng đủ',
                    'HALF_WON' => 'Thắng nửa',
                    'LOST' => 'Thua đủ',
                    'HALF_LOST' => 'Thua nửa',
                    'PUSH' => 'Hòa (Hoàn)',
                    'VOIDED' => 'Hoàn',
                    'CORRECTED' => match(true) {
                        $bet->net_result > 0 => 'Điều chỉnh (Thắng)',
                        $bet->net_result < 0 && $bet->gross_payout == 0 => 'Điều chỉnh (Thua)',
                        $bet->net_result == 0 && $bet->gross_payout > 0 => 'Điều chỉnh (Hòa)',
                        default => 'Đã điều chỉnh'
                    },
                    default => $bet->status->value
                } }}
            </x-filament::badge>
        </div>
    </div>

    @if($bet->market && $bet->market->match)
        <div class="mb-3">
            <p class="font-bold text-base text-gray-800 dark:text-white flex items-center gap-2">
                {{ $bet->market->match->home_team }} vs {{ $bet->market->match->away_team }}
            </p>
        </div>
    @endif

    <div class="bg-gray-50 dark:bg-gray-800/50 rounded-xl p-3 mb-3 border border-gray-100 dark:border-gray-700">
        <p class="text-xs text-gray-600 dark:text-gray-300 font-medium leading-relaxed">
            <span class="font-bold">{{ match($bet->period_type_snapshot) {
                'FULL_TIME' => 'Cả trận',
                'FIRST_HALF' => 'Hiệp 1',
                'SECOND_HALF' => 'Hiệp 2',
                'EXTRA_TIME' => 'Hiệp phụ',
                'PENALTY' => 'Luân lưu',
                default => $bet->period_type_snapshot
            } }}</span>
            <span class="mx-1 text-gray-400">|</span>
            <span class="font-bold">{{ match($bet->market_type_snapshot) {
                'ASIAN_HANDICAP' => 'Handicap',
                'OVER_UNDER' => 'Tài / Xỉu',
                'EXACT_SCORE' => 'Tỉ số chính xác',
                '1X2' => 'Thắng/Hòa/Thua',
                default => $bet->market_type_snapshot
            } }}</span>
        </p>
        <p class="text-emerald-700 dark:text-emerald-400 font-black mt-1">
            @php
                $homeTeam = $bet->market?->match?->home_team ?? 'Đội nhà';
                $awayTeam = $bet->market?->match?->away_team ?? 'Đội khách';
                $displayOdds = str_replace(
                    ['Home', 'Away', 'Draw', 'Over', 'Under'],
                    ["Đội nhà ({$homeTeam})", "Đội khách ({$awayTeam})", 'Hòa', 'Tài', 'Xỉu'],
                    $bet->display_odds_snapshot
                );
            @endphp
            {{ $displayOdds }}
        </p>
    </div>

    @php
        $correctionAmount = 0;
        if ($bet->status->value === 'CORRECTED') {
            $correctionAmount = \App\Models\WalletLedger::where('bet_id', $bet->id)
                ->where('type', 'SETTLEMENT_CORRECTION')
                ->sum('amount_available');
        }
    @endphp

    <div class="flex justify-between items-end">
        <div class="space-y-1">
            <p class="text-xs text-gray-500">Đặt lúc: {{ $bet->placed_at?->format('d/m/Y H:i') }}</p>
            <p class="text-xs font-bold text-gray-700 dark:text-gray-300">Cược: {{ number_format($bet->stake) }} lá</p>
        </div>

        @if($bet->status->isSettled())
            <div class="text-right">
                <p class="text-xs text-gray-500">Nhận về</p>
                <p class="font-black text-lg {{ $bet->net_result > 0 ? 'text-emerald-600 dark:text-emerald-400' : ($bet->net_result < 0 ? 'text-red-500' : 'text-gray-700 dark:text-gray-300') }}">
                    {{ number_format($bet->gross_payout) }}
                </p>
                <p class="text-[10px] font-bold {{ $bet->net_result > 0 ? 'text-emerald-600' : ($bet->net_result < 0 ? 'text-red-500' : 'text-gray-500') }}">
                    ({{ $bet->net_result > 0 ? '+' : '' }}{{ number_format($bet->net_result) }})
                </p>
                @if($correctionAmount !== 0)
                    <div class="mt-1.5 pt-1.5 border-t border-gray-100 dark:border-gray-800">
                        <p class="text-[9px] text-gray-400 uppercase tracking-wider mb-0.5">Kế toán điều chỉnh ví</p>
                        <p class="text-xs font-black {{ $correctionAmount > 0 ? 'text-emerald-500' : 'text-red-500' }}">
                            {{ $correctionAmount > 0 ? '+' : '' }}{{ number_format($correctionAmount) }}
                        </p>
                    </div>
                @endif
            </div>
        @else
            <div class="text-right">
                <p class="text-xs text-gray-500">Khả năng nhận</p>
                <p class="font-black text-lg text-gray-400">
                    {{ number_format($bet->stake * (1 + $bet->profit_rate_snapshot)) }}
                </p>
            </div>
        @endif
    </div>

    @if($isAdmin)
        <div class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-800 space-y-2">
            <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Thông tin quản trị</p>
            <div class="grid grid-cols-2 gap-2 text-sm">
                <div>
                    <p class="text-xs text-gray-400">Người chơi</p>
                    <p class="font-semibold text-gray-800 dark:text-gray-200">{{ $bet->user->name ?? 'N/A' }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-400">Database ID</p>
                    <p class="font-mono text-gray-800 dark:text-gray-200">#{{ $bet->id }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-400">Market ID</p>
                    <p class="font-mono text-gray-800 dark:text-gray-200">#{{ $bet->market_id }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-400">Tỷ lệ (Profit Rate)</p>
                    <p class="font-mono text-gray-800 dark:text-gray-200">{{ $bet->profit_rate_snapshot }}</p>
                </div>
            </div>
        </div>
    @endif
</div>
