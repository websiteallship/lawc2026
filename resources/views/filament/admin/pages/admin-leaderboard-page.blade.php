<x-filament-panels::page>
    <div class="space-y-4">
        {{-- Summary stats bar --}}
        @php
            $activeSeason = \App\Models\Season::where('status', 'active')->first();
            $wallets = \App\Models\Wallet::query()
                ->when($activeSeason, fn ($q) => $q->where('season_id', $activeSeason->id))
                ->whereHas('user', fn ($q) => $q->where('status', 'ACTIVE')
                    ->whereHas('roles', fn ($r) => $r->where('name', 'player')))
                ->get();

            $totalPlayers   = $wallets->count();
            $totalInPlay    = $wallets->sum(fn ($w) => $w->available_balance + $w->locked_balance);
            $totalNetProfit = $wallets->sum('net_profit');
            $houseEdge      = -$totalNetProfit; // House profit = inverse of player net
            $profitable     = $wallets->where('net_profit', '>', 0)->count();
            $losing         = $wallets->where('net_profit', '<', 0)->count();
        @endphp

        <div class="grid grid-cols-2 gap-4 md:grid-cols-3 lg:grid-cols-5">
            <x-filament::card>
                <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Tổng người chơi</div>
                <div class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ $totalPlayers }}</div>
            </x-filament::card>

            <x-filament::card>
                <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Tổng lá trong hệ thống</div>
                <div class="mt-1 text-2xl font-bold text-primary-600 dark:text-primary-400">{{ number_format($totalInPlay) }}</div>
            </x-filament::card>

            <x-filament::card>
                <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Tổng lãi/lỗ người chơi</div>
                <div class="mt-1 text-2xl font-bold {{ $totalNetProfit >= 0 ? 'text-success-600 dark:text-success-400' : 'text-danger-600 dark:text-danger-400' }}">
                    {{ ($totalNetProfit >= 0 ? '+' : '') . number_format($totalNetProfit) }}
                </div>
            </x-filament::card>

            <x-filament::card>
                <div class="text-sm font-medium text-gray-500 dark:text-gray-400">House Edge (Lãi nhà)</div>
                <div class="mt-1 text-2xl font-bold {{ $houseEdge >= 0 ? 'text-success-600 dark:text-success-400' : 'text-danger-600 dark:text-danger-400' }}">
                    {{ ($houseEdge >= 0 ? '+' : '') . number_format($houseEdge) }}
                </div>
            </x-filament::card>

            <x-filament::card>
                <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Đang lãi / Đang lỗ</div>
                <div class="mt-1 text-2xl font-bold">
                    <span class="text-success-600 dark:text-success-400">{{ $profitable }}</span>
                    <span class="text-gray-400 mx-1">/</span>
                    <span class="text-danger-600 dark:text-danger-400">{{ $losing }}</span>
                </div>
            </x-filament::card>
        </div>
    </div>

    {{ $this->table }}
</x-filament-panels::page>
