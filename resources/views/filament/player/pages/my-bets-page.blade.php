<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Tabs --}}
        <div class="flex gap-2 overflow-x-auto pb-2 scrollbar-hide border-b border-gray-200 dark:border-gray-700">
            @foreach([
                'all' => 'Tất cả',
                'pending' => 'Chưa mở thưởng',
                'settled' => 'Đã mở thưởng',
                'voided' => 'Đã hoàn'
            ] as $key => $label)
                <button wire:click="$set('activeTab', '{{ $key }}')"
                    @class([
                        'px-4 py-3 text-sm font-medium whitespace-nowrap border-b-2 transition-colors',
                        'border-emerald-500 text-emerald-600 dark:text-emerald-400' => $activeTab === $key,
                        'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:hover:text-gray-300' => $activeTab !== $key,
                    ])>
                    {{ $label }}
                </button>
            @endforeach
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
                                    'CORRECTED' => 'Đã điều chỉnh',
                                    default => $bet->status->value
                                } }}
                            </x-filament::badge>
                            @if($bet->status->value === 'PENDING' && $bet->market && $bet->market->status === 'OPEN' && now()->lt($bet->market->close_at))
                                <button wire:click="$dispatch('openEditBetModal', { betId: {{ $bet->id }} })" 
                                    class="bg-amber-100 hover:bg-amber-200 text-amber-700 dark:bg-amber-900/50 dark:hover:bg-amber-900/70 dark:text-amber-400 p-1.5 rounded-lg shadow-sm border border-amber-200 dark:border-amber-800 transition-colors"
                                    title="Sửa dự đoán">
                                    <x-filament::icon icon="heroicon-o-pencil-square" class="w-4 h-4" />
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
                            {{ $bet->display_odds_snapshot }}
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
</x-filament-panels::page>
