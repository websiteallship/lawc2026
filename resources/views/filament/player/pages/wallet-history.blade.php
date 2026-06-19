<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Ví stats --}}
        @if($wallet)
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <x-filament::card>
                    <div class="text-center">
                        <p class="text-xs text-gray-500 mb-1">Lá khả dụng</p>
                        <p class="text-2xl font-bold text-emerald-600 break-words">{{ number_format($wallet->available_balance) }}</p>
                    </div>
                </x-filament::card>
                <x-filament::card>
                    <div class="text-center">
                        <p class="text-xs text-gray-500 mb-1">Đang khóa</p>
                        <p class="text-2xl font-bold text-amber-500 break-words">{{ number_format($wallet->locked_balance) }}</p>
                    </div>
                </x-filament::card>
                <x-filament::card>
                    <div class="text-center">
                        <p class="text-xs text-gray-500 mb-1">Tổng lá</p>
                        <p class="text-2xl font-bold text-gray-700 dark:text-gray-200 break-words">{{ number_format($wallet->total_balance) }}</p>
                    </div>
                </x-filament::card>
            </div>
        @else
            <x-filament::card>
                <p class="text-center text-gray-400">Chưa có ví lá cho mùa giải này.</p>
            </x-filament::card>
        @endif

        {{-- Filter & Search --}}
        <x-filament::card>
            <div class="space-y-4 mb-4 border-b border-gray-100 dark:border-gray-800 pb-4">
                <!-- Search input -->
                <div class="w-full max-w-md relative">
                    <div class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none">
                        <x-filament::icon icon="heroicon-m-magnifying-glass" class="w-4 h-4 text-gray-400" />
                    </div>
                    <input type="search" wire:model.live.debounce.500ms="searchQuery" 
                           class="block w-full py-2 pl-10 pr-4 text-sm text-gray-900 border border-gray-200 rounded-xl bg-gray-50 focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 dark:bg-gray-800 dark:border-gray-700 dark:placeholder-gray-400 dark:text-white dark:focus:ring-emerald-500/20 dark:focus:border-emerald-500 shadow-sm transition-all duration-200" 
                           placeholder="Tìm kiếm mã phiếu, đội bóng, lý do...">
                </div>

                <!-- Filters -->
                <div class="flex flex-wrap items-center gap-1.5">
                    <span class="text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider mr-2">Bộ lọc:</span>
                    @foreach($this->getLedgerTypeOptions() as $val => $label)
                        <button
                            wire:click="$set('filterType', '{{ $val }}')"
                            class="text-xs px-3 py-1 rounded-full border transition-all duration-200
                                {{ $filterType === $val
                                    ? 'bg-emerald-650 text-white border-emerald-600 bg-emerald-600 shadow-sm font-semibold'
                                    : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50 hover:text-gray-900 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-700 dark:hover:bg-gray-700'
                                }}"
                        >
                            {{ $label }}
                        </button>
                    @endforeach
                </div>
            </div>

            {{-- Ledger list --}}
            @php $ledgers = $this->getLedgers() @endphp
            @forelse($ledgers as $ledger)
                @php
                    $netAvail = $ledger->amount_available;
                    $netLocked = $ledger->amount_locked;
                    $isPositive = $netAvail > 0;
                @endphp
                <div 
                    @if($ledger->bet_id)
                        wire:click="mountAction('viewBet', { bet_id: {{ $ledger->bet_id }} })"
                        class="flex items-start justify-between py-3 px-2 -mx-2 rounded-xl border-b border-gray-100 dark:border-gray-700 last:border-0 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800/40 transition-colors duration-200"
                    @else
                        class="flex items-start justify-between py-3 border-b border-gray-100 dark:border-gray-700 last:border-0"
                    @endif
                >
                    <div class="flex-1">
                        <div class="flex items-center gap-2">
                            <x-filament::badge
                                :color="match($ledger->type->value) {
                                    'ADMIN_GRANT' => 'success',
                                    'ADMIN_DEDUCT' => 'danger',
                                    'BET_PLACED' => 'warning',
                                    'BET_WON', 'BET_HALF_WON' => 'success',
                                    'BET_LOST', 'BET_HALF_LOST' => 'danger',
                                    'BET_PUSH', 'BET_VOIDED' => 'info',
                                    default => 'gray'
                                }"
                            >
                                {{ $ledger->type->label() }}
                            </x-filament::badge>
                        </div>
                        @if($ledger->reason)
                            <p class="text-xs text-gray-500 mt-1">{{ $ledger->reason }}</p>
                        @endif
                        <p class="text-xs text-gray-400 mt-1">
                            {{ $ledger->created_at?->setTimezone('Asia/Ho_Chi_Minh')->format('d/m/Y H:i') }}
                        </p>
                    </div>
                    <div class="text-right ml-4">
                        @if($netAvail !== 0)
                            <p class="text-sm font-semibold {{ $netAvail > 0 ? 'text-emerald-600' : 'text-red-500' }}">
                                {{ $netAvail > 0 ? '+' : '' }}{{ number_format($netAvail) }} lá
                            </p>
                        @endif
                        @if($netLocked !== 0)
                            <p class="text-xs text-gray-400">
                                khóa: {{ $netLocked > 0 ? '+' : '' }}{{ number_format($netLocked) }}
                            </p>
                        @endif
                        <p class="text-xs text-gray-400">
                            Còn: {{ number_format($ledger->balance_available_after) }}
                        </p>
                    </div>
                </div>
            @empty
                <p class="text-center text-gray-400 py-4">Chưa có lịch sử ví.</p>
            @endforelse

            @if($ledgers instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator)
                <div class="mt-4 border-t border-gray-100 dark:border-gray-800 pt-4">
                    <x-filament::pagination :paginator="$ledgers" />
                </div>
            @endif
        </x-filament::card>
    </div>
</x-filament-panels::page>
