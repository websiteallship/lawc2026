<div>
    @if($isOpen && $outcome && $outcome->market && $outcome->market->match)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/50 backdrop-blur-sm p-4 overflow-y-auto">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl w-full max-w-md overflow-hidden" @click.away="$wire.closeModal()">
                
                {{-- Header --}}
                <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 flex justify-between items-center">
                    <h3 class="text-lg font-bold text-gray-800 dark:text-gray-100 flex items-center gap-2">
                        <x-filament::icon icon="heroicon-o-ticket" class="w-5 h-5 text-emerald-500" />
                        Xác nhận dự đoán
                    </h3>
                    <button wire:click="closeModal" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition-colors">
                        <x-filament::icon icon="heroicon-o-x-mark" class="w-5 h-5" />
                    </button>
                </div>

                <div class="p-5 space-y-4">
                    {{-- Match info --}}
                    <div class="bg-gray-50 dark:bg-gray-900/50 rounded-xl p-4 border border-gray-100 dark:border-gray-700">
                        <p class="text-xs text-gray-500 font-medium mb-1 uppercase tracking-wider">Trận đấu</p>
                        <p class="font-bold text-gray-800 dark:text-gray-200 text-base">
                            {{ $outcome->market->match->home_team }} vs {{ $outcome->market->match->away_team }}
                        </p>
                    </div>

                    {{-- Market info --}}
                    <div class="grid grid-cols-2 gap-3">
                        <div class="bg-blue-50 dark:bg-blue-900/20 rounded-xl p-3 border border-blue-100 dark:border-blue-800/30">
                            <p class="text-[10px] text-blue-600/70 dark:text-blue-400/70 font-semibold mb-0.5 uppercase tracking-wider">Mốc</p>
                            <p class="font-bold text-blue-800 dark:text-blue-300 text-sm truncate">
                                {{ match($outcome->market->period_type) {
                                    'FULL_TIME' => 'Cả trận 90 phút',
                                    'FIRST_HALF' => 'Hiệp 1',
                                    'SECOND_HALF' => 'Hiệp 2',
                                    'EXTRA_TIME' => 'Hiệp phụ',
                                    'PENALTY' => 'Luân lưu',
                                    default => $outcome->market->period_type
                                } }}
                            </p>
                        </div>
                        <div class="bg-indigo-50 dark:bg-indigo-900/20 rounded-xl p-3 border border-indigo-100 dark:border-indigo-800/30">
                            <p class="text-[10px] text-indigo-600/70 dark:text-indigo-400/70 font-semibold mb-0.5 uppercase tracking-wider">Loại kèo</p>
                            <p class="font-bold text-indigo-800 dark:text-indigo-300 text-sm truncate">
                                {{ match($outcome->market->market_type) {
                                    'ASIAN_HANDICAP' => 'Handicap',
                                    'OVER_UNDER' => 'Tài / Xỉu',
                                    'EXACT_SCORE' => 'Tỉ số chính xác',
                                    '1X2' => 'Thắng/Hòa/Thua',
                                    default => $outcome->market->market_type
                                } }}
                            </p>
                        </div>
                    </div>

                    {{-- Selection info --}}
                    <div class="bg-emerald-50 dark:bg-emerald-900/20 rounded-xl p-4 border border-emerald-100 dark:border-emerald-800/30 flex justify-between items-center">
                        @php
                            $displayName = $outcome->label;
                            if ($displayName === 'Đội Nhà' || $outcome->selection_side === 'HOME') {
                                $displayName = $outcome->market->match->home_team;
                            } elseif ($displayName === 'Đội Khách' || $outcome->selection_side === 'AWAY') {
                                $displayName = $outcome->market->match->away_team;
                            }
                            
                            $displayLine = null;
                            if (in_array($outcome->market->market_type, ['ASIAN_HANDICAP', 'OVER_UNDER']) && !is_null($outcome->line_value)) {
                                $displayLine = (float) $outcome->line_value;
                                if ($outcome->market->market_type === 'ASIAN_HANDICAP' && $displayLine > 0) {
                                    $displayLine = '+' . $displayLine;
                                }
                            }
                        @endphp
                        <div>
                            <p class="text-[10px] text-emerald-600/70 dark:text-emerald-400/70 font-semibold mb-0.5 uppercase tracking-wider">Lựa chọn của bạn</p>
                            <p class="font-black text-emerald-800 dark:text-emerald-300 text-lg flex items-center gap-2">
                                {{ $displayName }}
                                @if($displayLine !== null)
                                    <span class="text-sm font-bold text-emerald-700 dark:text-emerald-400 bg-emerald-100 dark:bg-emerald-900/40 px-2 py-0.5 rounded-md">
                                        {{ $displayLine }}
                                    </span>
                                @endif
                            </p>
                        </div>
                        <div class="text-right">
                            <p class="text-[10px] text-emerald-600/70 dark:text-emerald-400/70 font-semibold mb-0.5 uppercase tracking-wider">Tỷ lệ ăn</p>
                            <p class="font-black text-emerald-600 dark:text-emerald-400 text-lg">
                                {{ number_format($outcome->profit_rate, 2) }}
                            </p>
                        </div>
                    </div>

                    {{-- Stake Input --}}
                    <div class="space-y-3 pt-2">
                        <div class="flex justify-between items-end">
                            <label class="text-sm font-bold text-gray-700 dark:text-gray-300">Nhập số Lá</label>
                            <span class="text-xs text-gray-500 font-medium">Khả dụng: <span class="font-bold {{ $availableBalance > 0 ? 'text-emerald-600' : 'text-red-500' }}">{{ number_format($availableBalance) }}</span></span>
                        </div>
                        
                        <div class="relative">
                            <input type="number" wire:model.live.debounce.300ms="stake" min="{{ $minStake }}" max="{{ $maxStake }}" step="10"
                                class="w-full bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-600 text-gray-900 dark:text-white text-lg rounded-xl focus:ring-emerald-500 focus:border-emerald-500 block p-3 font-bold text-center shadow-sm"
                                placeholder="Nhập số lá (min {{ number_format($minStake) }})">
                        </div>
                        
                        @error('stake')
                            <p class="text-red-500 text-xs font-semibold">{{ $message }}</p>
                        @enderror

                        @if($stake < $minStake)
                            <p class="text-amber-500 text-xs font-medium text-center">Tối thiểu {{ number_format($minStake) }} lá.</p>
                        @elseif($stake > $maxStake)
                            <p class="text-amber-500 text-xs font-medium text-center">Tối đa {{ number_format($maxStake) }} lá mỗi vé.</p>
                        @elseif($stake > $availableBalance)
                            <p class="text-red-500 text-xs font-medium text-center">Số dư không đủ!</p>
                        @endif
                    </div>

                    {{-- Calculation --}}
                    @if($stake >= $minStake && $stake <= $availableBalance && $stake <= $maxStake)
                        @php
                            $payout = $stake * (1 + $outcome->profit_rate);
                            $profit = $stake * $outcome->profit_rate;
                        @endphp
                        <div class="bg-gray-50 dark:bg-gray-800/50 rounded-xl p-3 border border-gray-100 dark:border-gray-700 space-y-2 mt-4">
                            <div class="flex justify-between items-center">
                                <span class="text-xs text-gray-500 font-medium">Có thể nhận:</span>
                                <span class="font-bold text-gray-800 dark:text-gray-200">{{ number_format($payout) }} lá</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-xs text-gray-500 font-medium">Lãi nếu thắng đủ:</span>
                                <span class="font-bold text-emerald-600">+{{ number_format($profit) }} lá</span>
                            </div>
                        </div>
                    @endif
                </div>

                {{-- Footer --}}
                <div class="px-5 py-4 border-t border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 flex gap-3">
                    <button wire:click="closeModal" class="flex-1 bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-200 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600 font-bold py-2.5 px-4 rounded-xl transition-colors">
                        Hủy
                    </button>
                    <x-filament::button wire:click="placeBet" wire:loading.attr="disabled"
                        color="success"
                        class="flex-1 font-bold py-2.5 rounded-xl transition-colors shadow-sm"
                        :disabled="!($stake >= $minStake && $stake <= $availableBalance && $stake <= $maxStake)"
                    >
                        <span wire:loading.remove wire:target="placeBet">Xác nhận</span>
                        <span wire:loading wire:target="placeBet" class="inline-flex items-center gap-2 justify-center">
                            <x-filament::icon icon="heroicon-m-arrow-path" class="w-5 h-5 animate-spin" /> Đang xử lý...
                        </span>
                    </x-filament::button>
                </div>
            </div>
        </div>
    @endif
</div>
