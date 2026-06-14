<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Ví stats --}}
        @if($wallet)
            <div class="grid grid-cols-3 gap-4">
                <x-filament::card>
                    <div class="text-center">
                        <p class="text-xs text-gray-500 mb-1">Lá khả dụng</p>
                        <p class="text-2xl font-bold text-emerald-600">{{ number_format($wallet->available_balance) }}</p>
                    </div>
                </x-filament::card>
                <x-filament::card>
                    <div class="text-center">
                        <p class="text-xs text-gray-500 mb-1">Đang khóa</p>
                        <p class="text-2xl font-bold text-amber-500">{{ number_format($wallet->locked_balance) }}</p>
                    </div>
                </x-filament::card>
                <x-filament::card>
                    <div class="text-center">
                        <p class="text-xs text-gray-500 mb-1">Tổng lá</p>
                        <p class="text-2xl font-bold text-gray-700 dark:text-gray-200">{{ number_format($wallet->total_balance) }}</p>
                    </div>
                </x-filament::card>
            </div>
        @else
            <x-filament::card>
                <p class="text-center text-gray-400">Chưa có ví lá cho mùa giải này.</p>
            </x-filament::card>
        @endif

        {{-- Filter --}}
        <x-filament::card>
            <div class="flex flex-wrap gap-2 mb-4">
                <p class="text-sm font-medium text-gray-600 dark:text-gray-300 self-center">Lọc:</p>
                @foreach($this->getLedgerTypeOptions() as $val => $label)
                    <button
                        wire:click="$set('filterType', '{{ $val }}')"
                        class="text-xs px-3 py-1 rounded-full border
                            {{ $filterType === $val
                                ? 'bg-emerald-600 text-white border-emerald-600'
                                : 'bg-white text-gray-600 border-gray-300 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600'
                            }}"
                    >
                        {{ $label }}
                    </button>
                @endforeach
            </div>

            {{-- Ledger list --}}
            @php $ledgers = $this->getLedgers() @endphp
            @forelse($ledgers as $ledger)
                @php
                    $netAvail = $ledger->amount_available;
                    $netLocked = $ledger->amount_locked;
                    $isPositive = $netAvail > 0;
                @endphp
                <div class="flex items-start justify-between py-3 border-b border-gray-100 dark:border-gray-700 last:border-0">
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
        </x-filament::card>
    </div>
</x-filament-panels::page>
