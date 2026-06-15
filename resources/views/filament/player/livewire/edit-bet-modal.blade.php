<div wire:poll.10s="checkMarketStatus">
    @if($isOpen && $oldBet && $market)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/50 backdrop-blur-sm p-4 overflow-y-auto">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl w-full max-w-md overflow-hidden" @click.away="$wire.closeModal()">
                
                {{-- Header --}}
                <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 flex justify-between items-center">
                    <h3 class="text-lg font-bold text-gray-800 dark:text-gray-100 flex items-center gap-2">
                        <x-filament::icon icon="heroicon-o-pencil-square" class="w-5 h-5 text-amber-500" />
                        Sửa dự đoán
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
                            {{ $market->match->home_team }} vs {{ $market->match->away_team }}
                        </p>
                        <div class="mt-2 flex gap-2">
                            <span class="text-xs font-semibold px-2 py-1 rounded bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300">
                                {{ match($market->period_type) { 'FULL_TIME' => 'Cả trận', 'FIRST_HALF' => 'Hiệp 1', 'SECOND_HALF' => 'Hiệp 2', default => $market->period_type } }}
                            </span>
                            <span class="text-xs font-semibold px-2 py-1 rounded bg-indigo-100 text-indigo-700 dark:bg-indigo-900/50 dark:text-indigo-300">
                                {{ match($market->market_type) { 'ASIAN_HANDICAP' => 'Handicap', 'OVER_UNDER' => 'Tài/Xỉu', 'EXACT_SCORE' => 'Tỉ số', default => $market->market_type } }}
                            </span>
                        </div>
                    </div>

                    {{-- Outcomes Selection --}}
                    <div>
                        <p class="text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">Chọn lại cửa</p>
                        <div class="space-y-2 max-h-48 overflow-y-auto pr-1">
                            @foreach($outcomes as $outcome)
                                @php
                                    $displayName = $outcome->label;
                                    if ($displayName === 'Đội Nhà' || $outcome->selection_side === 'HOME') {
                                        $displayName = $market->match->home_team;
                                    } elseif ($displayName === 'Đội Khách' || $outcome->selection_side === 'AWAY') {
                                        $displayName = $market->match->away_team;
                                    }

                                    if (str_starts_with(strtolower($displayName), 'over ')) {
                                        $displayName = preg_replace('/^over /i', 'Tài ', $displayName);
                                    } elseif (str_starts_with(strtolower($displayName), 'under ')) {
                                        $displayName = preg_replace('/^under /i', 'Xỉu ', $displayName);
                                    } elseif (strtolower($displayName) === 'over') {
                                        $displayName = 'Tài';
                                    } elseif (strtolower($displayName) === 'under') {
                                        $displayName = 'Xỉu';
                                    }
                                    
                                    $displayLine = null;
                                    if (in_array($market->market_type, ['ASIAN_HANDICAP', 'OVER_UNDER']) && !is_null($outcome->line_value)) {
                                        $displayLine = (float) $outcome->line_value;
                                        if ($market->market_type === 'ASIAN_HANDICAP' && $displayLine > 0) {
                                            $displayLine = '+' . $displayLine;
                                        }
                                    }
                                    $isSelected = $selectedOutcomeId === $outcome->id;
                                @endphp
                                <label class="flex items-center justify-between p-3 border rounded-xl cursor-pointer transition-colors {{ $isSelected ? 'bg-amber-50 border-amber-500 dark:bg-amber-900/20 dark:border-amber-500' : 'bg-white border-gray-200 dark:bg-gray-800 dark:border-gray-700 hover:border-amber-300' }}">
                                    <div class="flex items-center gap-3">
                                        <input type="radio" wire:model.live="selectedOutcomeId" name="outcome" value="{{ $outcome->id }}" class="text-amber-500 focus:ring-amber-500 w-4 h-4">
                                        <div>
                                            <p class="font-bold text-sm text-gray-800 dark:text-gray-200">
                                                {{ $displayName }}
                                                @if($displayLine !== null)
                                                    <span class="text-xs font-bold text-gray-500 ml-1">({{ $displayLine }})</span>
                                                @endif
                                            </p>
                                        </div>
                                    </div>
                                    <div class="font-black {{ $isSelected ? 'text-amber-600 dark:text-amber-400' : 'text-gray-600 dark:text-gray-400' }}">
                                        {{ number_format($outcome->profit_rate, 2) }}
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    {{-- Stake Input --}}
                    <div class="space-y-3 pt-2">
                        <div class="flex justify-between items-end">
                            <label class="text-sm font-bold text-gray-700 dark:text-gray-300">Nhập số Lá mới</label>
                            <span class="text-xs text-gray-500 font-medium">Khả dụng (đã gồm vé cũ): <span class="font-bold {{ $effectiveBalance > 0 ? 'text-emerald-600' : 'text-red-500' }}">{{ number_format($effectiveBalance) }}</span></span>
                        </div>
                        
                        <div class="relative">
                            <input type="number" wire:model.live.debounce.300ms="stake" min="{{ $minStake }}" max="{{ $maxStake }}" step="10"
                                class="w-full bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-600 text-gray-900 dark:text-white text-lg rounded-xl focus:ring-amber-500 focus:border-amber-500 block p-3 font-bold text-center shadow-sm"
                                placeholder="Nhập số lá (min {{ number_format($minStake) }})">
                        </div>
                        
                        @error('stake')
                            <p class="text-red-500 text-xs font-semibold">{{ $message }}</p>
                        @enderror

                        @if($stake < $minStake)
                            <p class="text-amber-500 text-xs font-medium text-center">Tối thiểu {{ number_format($minStake) }} lá.</p>
                        @elseif($stake > $maxStake)
                            <p class="text-amber-500 text-xs font-medium text-center">Tối đa {{ number_format($maxStake) }} lá mỗi vé.</p>
                        @elseif($stake > $effectiveBalance)
                            <p class="text-red-500 text-xs font-medium text-center">Số dư không đủ!</p>
                        @endif
                    </div>
                    
                    {{-- Warning --}}
                    <div class="bg-blue-50 dark:bg-blue-900/30 p-3 rounded-lg border border-blue-100 dark:border-blue-800">
                        <p class="text-xs text-blue-700 dark:text-blue-300 flex gap-2">
                            <x-filament::icon icon="heroicon-o-information-circle" class="w-4 h-4 shrink-0" />
                            <span>Vé cũ sẽ bị <b>Hủy</b> (hoàn lá) và hệ thống sẽ tạo một vé <b>Mới</b> với tỷ lệ cược hiện tại.</span>
                        </p>
                    </div>
                </div>

                {{-- Footer --}}
                <div class="px-5 py-4 border-t border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 flex gap-3">
                    <button wire:click="closeModal" class="flex-1 bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-200 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600 font-bold py-2.5 px-4 rounded-xl transition-colors">
                        Đóng
                    </button>
                    <x-filament::button wire:click="updateBet" wire:loading.attr="disabled"
                        color="warning"
                        class="flex-1 font-bold py-2.5 rounded-xl transition-colors shadow-sm"
                        :disabled="!($stake >= $minStake && $stake <= $effectiveBalance && $stake <= $maxStake && $selectedOutcomeId)"
                    >
                        <span wire:loading.remove wire:target="updateBet">Xác nhận sửa</span>
                        <span wire:loading wire:target="updateBet" class="inline-flex items-center gap-2 justify-center">
                            <x-filament::icon icon="heroicon-m-arrow-path" class="w-5 h-5 animate-spin" /> Đang xử lý...
                        </span>
                    </x-filament::button>
                </div>
            </div>
        </div>
    @endif
</div>
