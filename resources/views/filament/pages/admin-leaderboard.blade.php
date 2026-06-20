<x-filament-panels::page>
    <div class="space-y-4" x-data="{ openModal: null }">
        <x-filament::tabs label="Leaderboard Tabs">
            <x-filament::tabs.item wire:click="$set('activeTab', 'season')" :active="$activeTab === 'season'" icon="heroicon-o-trophy">
                Mùa giải
            </x-filament::tabs.item>
            <x-filament::tabs.item wire:click="$set('activeTab', 'week')" :active="$activeTab === 'week'" icon="heroicon-o-calendar">
                Tuần
            </x-filament::tabs.item>
            <x-filament::tabs.item wire:click="$set('activeTab', 'round')" :active="$activeTab === 'round'" icon="heroicon-o-flag">
                Vòng đấu (7 ngày)
            </x-filament::tabs.item>
            <x-filament::tabs.item wire:click="$set('activeTab', 'roi')" :active="$activeTab === 'roi'" icon="heroicon-o-chart-pie">
                ROI (Top)
            </x-filament::tabs.item>
            <x-filament::tabs.item wire:click="$set('activeTab', 'exact_score')" :active="$activeTab === 'exact_score'" icon="heroicon-o-bolt">
                Cao thủ Tỉ số
            </x-filament::tabs.item>
        </x-filament::tabs>

        <x-filament::card>
            @if(count($rankings) === 0)
                <p class="text-center text-gray-400 py-8">Chưa có dữ liệu xếp hạng.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-gray-500 border-b border-gray-200 dark:border-gray-700">
                                <th class="py-2 pr-4 w-10">#</th>
                                <th class="py-2 pr-4">Người chơi (Username)</th>
                                <th class="py-2 pr-4 text-right">Tổng Lá</th>
                                <th class="py-2 pr-4 text-right">Lãi / Lỗ</th>
                                <th class="py-2 pr-4 text-right">ROI</th>
                                <th class="py-2 pr-4 text-right">Win%</th>
                                @if($activeTab === 'exact_score')
                                <th class="py-2 pr-4 text-right">Tỉ số đúng</th>
                                @endif
                                <th class="py-2 text-right">Phiếu</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($rankings as $index => $row)
                                <tr class="border-b border-gray-100 dark:border-gray-800 hover:bg-amber-50/40 dark:hover:bg-amber-500/5 transition cursor-pointer"
                                    @click="openModal = {{ $index }}">

                                    {{-- Rank --}}
                                    <td class="py-3 pr-4">
                                        @if($row['rank'] === 1)
                                            <x-filament::icon icon="heroicon-s-trophy" class="h-5 w-5 text-yellow-400" />
                                        @elseif($row['rank'] === 2)
                                            <x-filament::icon icon="heroicon-s-trophy" class="h-5 w-5 text-gray-400" />
                                        @elseif($row['rank'] === 3)
                                            <x-filament::icon icon="heroicon-s-trophy" class="h-5 w-5 text-amber-700" />
                                        @else
                                            <span class="font-medium text-gray-500">{{ $row['rank'] }}</span>
                                        @endif
                                    </td>

                                    {{-- Name --}}
                                    <td class="py-3 pr-4">
                                        <div class="flex items-center gap-2">
                                            <span class="font-semibold text-gray-800 dark:text-gray-100">{{ $row['name'] }}</span>
                                            @if($row['level'] > 0)
                                                <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-primary-100 dark:bg-primary-500/20 text-primary-600 dark:text-primary-400">
                                                    LV {{ $row['level'] }}
                                                </span>
                                            @endif
                                        </div>
                                    </td>

                                    {{-- Tổng Lá --}}
                                    <td class="py-3 pr-4 text-right font-mono text-gray-700 dark:text-gray-300">
                                        {{ number_format($row['total_balance']) }}
                                    </td>

                                    {{-- Lãi / Lỗ — số cụ thể cho admin --}}
                                    <td class="py-3 pr-4 text-right font-bold font-mono">
                                        @if($row['net_profit'] > 0)
                                            <span class="text-emerald-600 dark:text-emerald-400">+{{ number_format($row['net_profit']) }}</span>
                                        @elseif($row['net_profit'] < 0)
                                            <span class="text-red-500 dark:text-red-400">{{ number_format($row['net_profit']) }}</span>
                                        @else
                                            <span class="text-gray-400">0</span>
                                        @endif
                                    </td>

                                    {{-- ROI --}}
                                    <td class="py-3 pr-4 text-right text-gray-600 dark:text-gray-300">
                                        {{ $row['roi'] !== null ? $row['roi'].'%' : '—' }}
                                    </td>

                                    {{-- Win% --}}
                                    <td class="py-3 pr-4 text-right text-gray-600 dark:text-gray-300">
                                        {{ $row['win_rate'] }}%
                                    </td>

                                    @if($activeTab === 'exact_score')
                                    <td class="py-3 pr-4 text-right text-amber-600 font-semibold">
                                        {{ $row['exact_score_wins'] }}
                                    </td>
                                    @endif

                                    {{-- Phiếu --}}
                                    <td class="py-3 text-right text-gray-500">
                                        {{ $row['bets_count'] }}
                                    </td>
                                </tr>

                                {{-- Player Detail Modal --}}
                                <template x-teleport="body">
                                    <div x-show="openModal === {{ $index }}"
                                         style="display: none;"
                                         class="fixed inset-0 z-[9999] flex items-center justify-center p-4">

                                        <div class="fixed inset-0 bg-gray-950/60 backdrop-blur-sm"
                                             x-show="openModal === {{ $index }}"
                                             x-transition.opacity
                                             @click="openModal = null"></div>

                                        <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-2xl w-full max-w-lg border border-gray-200 dark:border-gray-800 flex flex-col max-h-[85vh] relative z-10"
                                             x-data="{ activeAchIndex: 0 }"
                                             x-show="openModal === {{ $index }}"
                                             x-transition:enter="transition ease-out duration-200"
                                             x-transition:enter-start="opacity-0 scale-95"
                                             x-transition:enter-end="opacity-100 scale-100"
                                             x-transition:leave="transition ease-in duration-100"
                                             x-transition:leave-start="opacity-100 scale-100"
                                             x-transition:leave-end="opacity-0 scale-95"
                                             @click.stop>

                                            {{-- Header --}}
                                            <div class="p-5 border-b border-gray-100 dark:border-gray-800 flex justify-between items-center bg-gray-50 dark:bg-gray-900 rounded-t-2xl">
                                                <div>
                                                    <h3 class="text-lg font-extrabold text-gray-900 dark:text-white">{{ $row['name'] }}</h3>
                                                    <div class="flex items-center gap-2 mt-1 flex-wrap">
                                                        @if($row['level'] > 0)
                                                            <span class="px-2 py-0.5 rounded-md text-[10px] font-black bg-primary-500 text-white shadow-sm">LV {{ $row['level'] }}</span>
                                                        @endif
                                                        <span class="text-[10px] font-bold text-gray-500">{{ count($row['achievements']) }} Huy hiệu</span>
                                                    </div>
                                                </div>
                                                <button @click="openModal = null"
                                                        class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition bg-white dark:bg-gray-800 rounded-full p-1.5 shadow-sm border border-gray-200 dark:border-gray-700">
                                                    <x-filament::icon icon="heroicon-o-x-mark" class="w-5 h-5" />
                                                </button>
                                            </div>

                                            {{-- Stats summary --}}
                                            <div class="grid grid-cols-4 gap-3 px-5 pt-4">
                                                <div class="bg-gray-50 dark:bg-gray-800 rounded-xl p-3 text-center">
                                                    <div class="text-[10px] text-gray-400 font-medium uppercase tracking-wider mb-1">Tổng Lá</div>
                                                    <div class="text-sm font-bold text-gray-800 dark:text-gray-100 font-mono">{{ number_format($row['total_balance']) }}</div>
                                                </div>
                                                <div class="bg-gray-50 dark:bg-gray-800 rounded-xl p-3 text-center">
                                                    <div class="text-[10px] text-gray-400 font-medium uppercase tracking-wider mb-1">Lãi/Lỗ</div>
                                                    <div class="text-sm font-bold font-mono
                                                        @if($row['net_profit'] > 0) text-emerald-600 dark:text-emerald-400
                                                        @elseif($row['net_profit'] < 0) text-red-500 dark:text-red-400
                                                        @else text-gray-400 @endif">
                                                        {{ ($row['net_profit'] >= 0 ? '+' : '') . number_format($row['net_profit']) }}
                                                    </div>
                                                </div>
                                                <div class="bg-gray-50 dark:bg-gray-800 rounded-xl p-3 text-center">
                                                    <div class="text-[10px] text-gray-400 font-medium uppercase tracking-wider mb-1">ROI</div>
                                                    <div class="text-sm font-bold text-gray-700 dark:text-gray-200">{{ $row['roi'] !== null ? $row['roi'].'%' : '—' }}</div>
                                                </div>
                                                <div class="bg-gray-50 dark:bg-gray-800 rounded-xl p-3 text-center">
                                                    <div class="text-[10px] text-gray-400 font-medium uppercase tracking-wider mb-1">Win%</div>
                                                    <div class="text-sm font-bold text-gray-700 dark:text-gray-200">{{ $row['win_rate'] }}%</div>
                                                </div>
                                            </div>

                                            {{-- Achievements --}}
                                            <div class="p-5 overflow-y-auto flex-1 flex flex-col">
                                                @if(count($row['achievements']) === 0)
                                                    <div class="py-8 flex flex-col items-center justify-center text-center">
                                                        <x-filament::icon icon="heroicon-o-face-frown" class="w-10 h-10 text-gray-300 dark:text-gray-600 mb-2" />
                                                        <p class="text-sm text-gray-400">Người chơi này chưa có huy hiệu.</p>
                                                    </div>
                                                @else
                                                    <div class="grid grid-cols-4 sm:grid-cols-5 gap-2 max-h-[200px] overflow-y-auto p-2 bg-gray-50 dark:bg-gray-950 rounded-xl border border-gray-100 dark:border-gray-800 shadow-inner">
                                                        @foreach($row['achievements'] as $achIndex => $ach)
                                                            @php
                                                                $colorClass = match($ach['color']) {
                                                                    'primary'  => 'text-primary-600 bg-primary-50 border-primary-200 dark:text-primary-400 dark:bg-primary-500/10 dark:border-primary-500/20',
                                                                    'success'  => 'text-success-600 bg-success-50 border-success-200 dark:text-success-400 dark:bg-success-500/10 dark:border-success-500/20',
                                                                    'warning'  => 'text-warning-600 bg-warning-50 border-warning-200 dark:text-warning-400 dark:bg-warning-500/10 dark:border-warning-500/20',
                                                                    'danger'   => 'text-danger-600 bg-danger-50 border-danger-200 dark:text-danger-400 dark:bg-danger-500/10 dark:border-danger-500/20',
                                                                    'indigo'   => 'text-indigo-600 bg-indigo-50 border-indigo-200 dark:text-indigo-400 dark:bg-indigo-500/10 dark:border-indigo-500/20',
                                                                    'fuchsia'  => 'text-fuchsia-600 bg-fuchsia-50 border-fuchsia-200 dark:text-fuchsia-400 dark:bg-fuchsia-500/10 dark:border-fuchsia-500/20',
                                                                    default    => 'text-gray-600 bg-gray-50 border-gray-200 dark:text-gray-400 dark:bg-gray-800 dark:border-gray-700',
                                                                };
                                                                $iconColorClass = match($ach['color']) {
                                                                    'primary'  => 'text-primary-500',
                                                                    'success'  => 'text-success-500',
                                                                    'warning'  => 'text-warning-500',
                                                                    'danger'   => 'text-danger-500',
                                                                    'indigo'   => 'text-indigo-500',
                                                                    'fuchsia'  => 'text-fuchsia-500',
                                                                    default    => 'text-gray-500',
                                                                };
                                                            @endphp
                                                            <button @click="activeAchIndex = {{ $achIndex }}"
                                                                    :class="activeAchIndex === {{ $achIndex }} ? 'ring-2 ring-primary-500 border-primary-500' : 'opacity-80 hover:opacity-100'"
                                                                    class="aspect-square rounded-xl border {{ $colorClass }} flex flex-col items-center justify-center p-1.5 transition hover:scale-105 relative shadow-sm">
                                                                <x-filament::icon :icon="$ach['icon']" class="w-7 h-7 {{ $iconColorClass }}" />
                                                                <span class="text-[9px] font-semibold mt-1 truncate w-full text-center">{{ $ach['name'] }}</span>
                                                                @if($ach['is_main'])
                                                                    <span class="absolute -top-1 -right-1 flex h-3.5 w-3.5 items-center justify-center rounded-full bg-amber-500 text-[8px] font-black text-white shadow-sm ring-1 ring-white">★</span>
                                                                @endif
                                                            </button>
                                                        @endforeach
                                                    </div>

                                                    <div class="mt-4 p-4 rounded-xl bg-gray-50 dark:bg-gray-950 border border-gray-100 dark:border-gray-800 flex flex-col min-h-[90px]">
                                                        @foreach($row['achievements'] as $achIndex => $ach)
                                                            @php
                                                                $iconColorClass = match($ach['color']) {
                                                                    'primary'  => 'text-primary-500',
                                                                    'success'  => 'text-success-500',
                                                                    'warning'  => 'text-warning-500',
                                                                    'danger'   => 'text-danger-500',
                                                                    'indigo'   => 'text-indigo-500',
                                                                    'fuchsia'  => 'text-fuchsia-500',
                                                                    default    => 'text-gray-500',
                                                                };
                                                            @endphp
                                                            <div x-show="activeAchIndex === {{ $achIndex }}"
                                                                 class="flex items-center gap-4 w-full"
                                                                 x-transition:enter="transition ease-out duration-150"
                                                                 x-transition:enter-start="opacity-0 translate-y-1"
                                                                 x-transition:enter-end="opacity-100 translate-y-0"
                                                                 style="display: none;">
                                                                <div class="w-12 h-12 rounded-xl flex items-center justify-center shrink-0 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 shadow-sm">
                                                                    <x-filament::icon :icon="$ach['icon']" class="w-7 h-7 {{ $iconColorClass }}" />
                                                                </div>
                                                                <div class="flex-1 min-w-0">
                                                                    <div class="flex items-center gap-2">
                                                                        <h4 class="text-sm font-extrabold text-gray-900 dark:text-white truncate">{{ $ach['name'] }}</h4>
                                                                        @if($ach['is_main'])
                                                                            <span class="px-1.5 py-0.5 rounded text-[8px] font-black uppercase bg-amber-500 text-white">Cấp chính</span>
                                                                        @else
                                                                            <span class="px-1.5 py-0.5 rounded text-[8px] font-black uppercase bg-gray-200 dark:bg-gray-800 text-gray-500">Phụ</span>
                                                                        @endif
                                                                    </div>
                                                                    <p class="text-xs mt-1 text-gray-600 dark:text-gray-400 leading-relaxed">{{ $ach['description'] }}</p>
                                                                </div>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <p class="text-xs text-gray-400 mt-3 text-center">
                    @if($activeTab === 'season') * Xếp theo lãi/lỗ tích lũy toàn bộ mùa giải (nguồn: Wallet live).
                    @elseif($activeTab === 'week') * Hiệu suất tuần hiện tại (Thứ 2 00:00 VN).
                    @elseif($activeTab === 'round') * Hiệu suất 7 ngày gần nhất.
                    @elseif($activeTab === 'roi') * Chỉ hiển thị người chơi có ≥5 phiếu đã settle. ROI = lãi ròng / tổng cược.
                    @elseif($activeTab === 'exact_score') * Xếp theo số lần dự đoán tỉ số chính xác.
                    @endif
                    <span class="ml-2 font-semibold text-amber-600">⚠ Dữ liệu nội bộ Admin — không chia sẻ bên ngoài.</span>
                </p>
            @endif
        </x-filament::card>
    </div>
</x-filament-panels::page>
