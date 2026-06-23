<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Tabs & Search --}}
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-2 border-b border-gray-200 dark:border-gray-700">
            <div class="flex gap-2 overflow-x-auto scrollbar-hide">
                @foreach([
                    'all' => 'Tất cả',
                    'pending' => 'Chưa mở',
                    'settled' => 'Đã mở',
                    'voided' => 'Đã hoàn',
                    'corrected' => 'Đã sửa'
                ] as $key => $label)
                    <button wire:click="$set('activeTab', '{{ $key }}')"
                        @class([
                            'px-4 py-2 text-sm font-medium whitespace-nowrap border-b-2 transition-colors',
                            'border-emerald-500 text-emerald-600 dark:text-emerald-400' => $activeTab === $key,
                            'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:hover:text-gray-300' => $activeTab !== $key,
                        ])>
                        {{ $label }} <span class="ml-1 opacity-70">({{ $this->tabCounts[$key] ?? 0 }})</span>
                    </button>
                @endforeach
            </div>

            <div class="w-full md:w-64 relative">
                <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                    <x-filament::icon icon="heroicon-m-magnifying-glass" class="w-4 h-4 text-gray-400" />
                </div>
                <input type="search" wire:model.live.debounce.500ms="searchQuery" 
                       class="block w-full py-2 pl-9 pr-3 text-sm text-gray-900 border border-gray-300 rounded-lg bg-gray-50 focus:ring-emerald-500 focus:border-emerald-500 dark:bg-gray-800 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-emerald-500 dark:focus:border-emerald-500 shadow-sm transition-colors" 
                       placeholder="Mã phiếu, tên đội bóng...">
            </div>
        </div>

        {{-- Bet Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @forelse($this->bets as $bet)
                <x-filament::card class="relative overflow-hidden group border-l-4 {{ match($bet->status->value) {
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
                            @if($bet->status->value === 'PENDING' && $bet->market && $bet->market->status === 'OPEN' && now()->lt($bet->market->close_at))
                                <button wire:click="$dispatch('openEditBetModal', { betId: {{ $bet->id }} })" 
                                    class="bg-amber-100 hover:bg-amber-200 text-amber-700 dark:bg-amber-900/50 dark:hover:bg-amber-900/70 dark:text-amber-400 p-1.5 rounded-lg shadow-sm border border-amber-200 dark:border-amber-800 transition-colors"
                                    title="Sửa dự đoán">
                                    <x-filament::icon icon="heroicon-o-pencil-square" class="w-4 h-4" />
                                </button>
                                <button wire:click="$dispatch('openCancelBetModal', { betId: {{ $bet->id }} })"
                                    class="bg-red-100 hover:bg-red-200 text-red-700 dark:bg-red-900/50 dark:hover:bg-red-900/70 dark:text-red-400 p-1.5 rounded-lg shadow-sm border border-red-200 dark:border-red-800 transition-colors"
                                    title="Huỷ phiếu">
                                    <x-filament::icon icon="heroicon-o-x-circle" class="w-4 h-4" />
                                </button>
                            @endif
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
                </x-filament::card>
            @empty
                <div class="col-span-full py-16 text-center border border-dashed border-gray-300 dark:border-gray-700 rounded-xl bg-white dark:bg-gray-800/50">
                    <x-filament::icon icon="heroicon-o-ticket" class="w-12 h-12 mx-auto text-gray-300 dark:text-gray-600 mb-3" />
                    <p class="text-gray-500 font-medium">Bạn chưa có phiếu dự đoán nào ở mục này.</p>
                </div>
            @endforelse
        </div>

        <div class="mt-4">
            <x-filament::pagination :paginator="$this->bets" />
        </div>
    </div>
    
    @livewire(\App\Filament\Player\Livewire\EditBetModal::class)
    @livewire(\App\Filament\Player\Livewire\CancelBetModal::class)
</x-filament-panels::page>
