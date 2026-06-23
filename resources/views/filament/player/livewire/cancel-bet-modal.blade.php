<div wire:poll.10s="checkMarketStatus">
    @if($isOpen && $bet && $market)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/50 backdrop-blur-sm p-4 overflow-y-auto">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl w-full max-w-md overflow-hidden" @click.away="$wire.closeModal()">

                {{-- Header --}}
                <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-700 bg-red-50 dark:bg-red-900/20 flex justify-between items-center">
                    <h3 class="text-lg font-bold text-red-700 dark:text-red-400 flex items-center gap-2">
                        <x-filament::icon icon="heroicon-o-exclamation-triangle" class="w-5 h-5 text-red-500" />
                        Huỷ phiếu dự đoán
                    </h3>
                    <button wire:click="closeModal" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition-colors">
                        <x-filament::icon icon="heroicon-o-x-mark" class="w-5 h-5" />
                    </button>
                </div>

                <div class="p-5 space-y-4">
                    {{-- Bet Info --}}
                    <div class="bg-gray-50 dark:bg-gray-900/50 rounded-xl p-4 border border-gray-100 dark:border-gray-700 space-y-2">
                        <div class="flex justify-between items-center">
                            <span class="text-xs text-gray-500 font-medium uppercase tracking-wider">Mã phiếu</span>
                            <span class="font-mono text-sm font-bold text-gray-800 dark:text-gray-200">{{ $bet->public_code }}</span>
                        </div>

                        @if($market->match)
                            <div class="flex justify-between items-center">
                                <span class="text-xs text-gray-500 font-medium uppercase tracking-wider">Trận đấu</span>
                                <span class="font-bold text-sm text-gray-800 dark:text-gray-200 text-right">
                                    {{ $market->match->home_team }} vs {{ $market->match->away_team }}
                                </span>
                            </div>
                        @endif

                        <div class="flex justify-between items-center">
                            <span class="text-xs text-gray-500 font-medium uppercase tracking-wider">Lựa chọn</span>
                            <span class="font-bold text-sm text-emerald-700 dark:text-emerald-400 text-right">
                                {{ $bet->display_odds_snapshot }}
                            </span>
                        </div>

                        <div class="flex justify-between items-center pt-1 border-t border-gray-200 dark:border-gray-700">
                            <span class="text-xs text-gray-500 font-medium uppercase tracking-wider">Số lá đặt</span>
                            <span class="font-black text-base text-gray-800 dark:text-gray-200">{{ number_format($bet->stake) }} lá</span>
                        </div>
                    </div>

                    {{-- Refund notice --}}
                    <div class="bg-blue-50 dark:bg-blue-900/30 p-3 rounded-lg border border-blue-100 dark:border-blue-800">
                        <p class="text-sm text-blue-700 dark:text-blue-300 flex gap-2 items-start">
                            <x-filament::icon icon="heroicon-o-information-circle" class="w-4 h-4 shrink-0 mt-0.5" />
                            <span>Bạn sẽ được hoàn lại <b>{{ number_format($bet->stake) }} lá</b> vào số dư khả dụng. Thao tác này <b>không thể hoàn tác</b>.</span>
                        </p>
                    </div>

                    {{-- Remaining cancels warning --}}
                    @if($showRemainingWarning)
                        <div class="bg-amber-50 dark:bg-amber-900/30 p-3 rounded-lg border border-amber-200 dark:border-amber-700 flex items-center gap-3">
                            <x-filament::icon icon="heroicon-o-exclamation-circle" class="w-5 h-5 text-amber-500 shrink-0" />
                            <div>
                                <p class="text-sm font-bold text-amber-700 dark:text-amber-400">
                                    Cảnh báo: Còn <span class="text-lg">{{ $remainingCancels }}</span> lần huỷ cho trận này!
                                </p>
                                <p class="text-xs text-amber-600 dark:text-amber-500 mt-0.5">Sau khi hết lượt, bạn không thể huỷ thêm phiếu trong trận này.</p>
                            </div>
                        </div>
                    @endif
                </div>

                {{-- Footer --}}
                <div class="px-5 py-4 border-t border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 flex gap-3">
                    <button wire:click="closeModal"
                        class="flex-1 bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-200 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600 font-bold py-2.5 px-4 rounded-xl transition-colors">
                        Đóng
                    </button>
                    <button wire:click="confirmCancel" wire:loading.attr="disabled"
                        class="flex-1 bg-red-600 hover:bg-red-700 disabled:opacity-60 text-white font-bold py-2.5 px-4 rounded-xl transition-colors shadow-sm flex items-center justify-center gap-2">
                        <span wire:loading.remove wire:target="confirmCancel">
                            <x-filament::icon icon="heroicon-o-x-circle" class="w-4 h-4 inline-block mr-1" />
                            Xác nhận huỷ
                        </span>
                        <span wire:loading wire:target="confirmCancel" class="inline-flex items-center gap-2 justify-center">
                            <x-filament::icon icon="heroicon-m-arrow-path" class="w-4 h-4 animate-spin" />
                            Đang xử lý...
                        </span>
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
