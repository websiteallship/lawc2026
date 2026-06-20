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
                Vòng đấu
            </x-filament::tabs.item>
            <x-filament::tabs.item wire:click="$set('activeTab', 'roi')" :active="$activeTab === 'roi'" icon="heroicon-o-chart-pie">
                ROI (Top)
            </x-filament::tabs.item>
            <x-filament::tabs.item wire:click="$set('activeTab', 'exact_score')" :active="$activeTab === 'exact_score'" icon="heroicon-o-bolt">
                Cao thủ Tỉ số
            </x-filament::tabs.item>
            <x-filament::tabs.item wire:click="$set('activeTab', 'missions')" :active="$activeTab === 'missions'" icon="heroicon-o-check-badge">
                Nhiệm vụ
            </x-filament::tabs.item>
            <x-filament::tabs.item wire:click="$set('activeTab', 'level')" :active="$activeTab === 'level'" icon="heroicon-o-arrow-trending-up">
                Level
            </x-filament::tabs.item>
            <x-filament::tabs.item wire:click="$set('activeTab', 'badges')" :active="$activeTab === 'badges'" icon="heroicon-o-shield-check">
                Danh hiệu
            </x-filament::tabs.item>
        </x-filament::tabs>

        <x-filament::card>
            @if(count($rankings) === 0)
                <p class="text-center text-gray-400 py-4">Chưa có dữ liệu xếp hạng cho mục này.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-gray-500 border-b border-gray-200 dark:border-gray-700">
                                @php $isBetting = in_array($activeTab, ['season','week','round','roi','exact_score']); @endphp
                                <th class="py-2 pr-4">#</th>
                                <th class="py-2 pr-4">Người chơi</th>
                                @if($isBetting)
                                    <th class="py-2 pr-4 text-right">Xu hướng</th>
                                    <th class="py-2 pr-4 text-right">ROI</th>
                                    <th class="py-2 pr-4 text-right">Win%</th>
                                    @if($activeTab === 'exact_score')
                                    <th class="py-2 pr-4 text-right">Tỉ số đúng</th>
                                    @endif
                                    <th class="py-2 text-right">Phiếu</th>
                                @elseif($activeTab === 'missions')
                                    <th class="py-2 pr-4 text-right">NV Hoàn thành</th>
                                    <th class="py-2 pr-4 text-right">Tiến độ tuần</th>
                                    <th class="py-2 text-right">Tuần 100%</th>
                                @elseif($activeTab === 'level')
                                    <th class="py-2 pr-4 text-right">Level</th>
                                    <th class="py-2 text-right">Danh hiệu</th>
                                @elseif($activeTab === 'badges')
                                    <th class="py-2 pr-4 text-right">Danh hiệu</th>
                                    <th class="py-2 text-right">Level</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($rankings as $index => $row)
                                <tr class="border-b border-gray-100 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-800/50 transition">
                                    <td class="py-3 pr-4">
                                        @if($row['rank'] === 1) <x-filament::icon icon="heroicon-s-trophy" class="h-5 w-5 text-yellow-400" />
                                        @elseif($row['rank'] === 2) <x-filament::icon icon="heroicon-s-trophy" class="h-5 w-5 text-gray-400" />
                                        @elseif($row['rank'] === 3) <x-filament::icon icon="heroicon-s-trophy" class="h-5 w-5 text-amber-700" />
                                        @else <span class="font-medium text-gray-500">{{ $row['rank'] }}</span>
                                        @endif
                                    </td>
                                    <td class="py-3 pr-4">
                                        <div class="flex items-center gap-2 cursor-pointer hover:opacity-80 transition"
                                             @click="openModal = {{ $index }}">
                                            <span class="text-2xl" title="Avatar">{{ $row['avatar'] }}</span>
                                            <span class="font-medium text-gray-700 dark:text-gray-200">{{ $row['name'] }}</span>
                                            <span class="ml-2 px-2 py-0.5 rounded-full text-[10px] font-bold bg-primary-50 dark:bg-primary-500/10 text-primary-600 dark:text-primary-400 border border-primary-200 dark:border-primary-500/20 shadow-sm shrink-0">
                                                LV {{ $row['level'] }}
                                            </span>
                                        </div>
                                    </td>
                                    @if($isBetting)
                                        <td class="py-3 pr-4 text-right">
                                            @if($row['profit_trend'] === 'positive')
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-bold bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-400">
                                                    <x-filament::icon icon="heroicon-m-arrow-trending-up" class="w-4 h-4" />
                                                    Lãi
                                                </span>
                                            @elseif($row['profit_trend'] === 'negative')
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-bold bg-red-50 text-red-500 dark:bg-red-500/10 dark:text-red-400">
                                                    <x-filament::icon icon="heroicon-m-arrow-trending-down" class="w-4 h-4" />
                                                    Lỗ
                                                </span>
                                            @else
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-bold bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400">
                                                    <x-filament::icon icon="heroicon-m-minus" class="w-4 h-4" />
                                                    Hòa
                                                </span>
                                            @endif
                                        </td>
                                        <td class="py-3 pr-4 text-right text-gray-600 dark:text-gray-300">
                                            {{ $row['roi'] !== null ? $row['roi'].'%' : '—' }}
                                        </td>
                                        <td class="py-3 pr-4 text-right text-gray-600 dark:text-gray-300">
                                            {{ $row['win_rate'] }}%
                                        </td>
                                        @if($activeTab === 'exact_score')
                                        <td class="py-3 pr-4 text-right text-amber-600 font-semibold">
                                            {{ $row['exact_score_wins'] }}
                                        </td>
                                        @endif
                                        <td class="py-3 text-right text-gray-500">{{ $row['bets_count'] }}</td>
                                    @elseif($activeTab === 'missions')
                                        <td class="py-3 pr-4 text-right text-primary-600 font-semibold">{{ $row['total_missions'] }}</td>
                                        <td class="py-3 pr-4 text-right text-gray-600 dark:text-gray-300">{{ $row['weekly_rate'] }}%</td>
                                        <td class="py-3 text-right text-amber-600 font-bold">{{ $row['perfect_weeks'] > 0 ? $row['perfect_weeks'] : '—' }}</td>
                                    @elseif($activeTab === 'level')
                                        <td class="py-3 pr-4 text-right text-amber-600 font-bold text-lg">LV {{ $row['level'] }}</td>
                                        <td class="py-3 text-right text-gray-500">{{ $row['badge_count'] }}</td>
                                    @elseif($activeTab === 'badges')
                                        <td class="py-3 pr-4 text-right text-indigo-600 font-semibold">{{ $row['badge_count'] }}</td>
                                        <td class="py-3 text-right text-amber-600 font-bold">LV {{ $row['level'] }}</td>
                                    @endif
                                </tr>


                                <!-- Alpine Modal for this specific user -->
                                <template x-teleport="body">
                                    <div x-show="openModal === {{ $index }}" 
                                         style="display: none;" 
                                         class="fixed inset-0 z-[9999] flex items-center justify-center p-4">
                                        
                                        <!-- Backdrop -->
                                        <div class="fixed inset-0 bg-gray-950/50 backdrop-blur-sm transition-opacity" 
                                             x-show="openModal === {{ $index }}"
                                             x-transition.opacity
                                             @click="openModal = null"></div>
                                             
                                        <!-- Modal Panel -->
                                        <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-xl w-full max-w-lg border border-gray-200 dark:border-gray-800 flex flex-col max-h-[85vh] relative z-10"
                                             x-data="{ activeAchIndex: 0 }"
                                             x-show="openModal === {{ $index }}"
                                             x-transition:enter="transition ease-out duration-200"
                                             x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                                             x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                                             x-transition:leave="transition ease-in duration-100"
                                             x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                                             x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                                             @click.stop>
                                             
                                            <!-- Header -->
                                            <div class="p-5 border-b border-gray-100 dark:border-gray-800 flex justify-between items-center bg-gray-50 dark:bg-gray-900 rounded-t-2xl">
                                                <div class="flex items-center gap-3">
                                                    <span class="text-4xl leading-none drop-shadow-sm">{{ $row['avatar'] }}</span>
                                                    <div>
                                                        <h3 class="text-lg font-extrabold text-gray-900 dark:text-white leading-tight">{{ $row['name'] }}</h3>
                                                        <div class="flex items-center gap-2 mt-1">
                                                            <span class="px-2 py-0.5 rounded-md text-[10px] font-black bg-primary-500 text-white shadow-sm tracking-wider">
                                                                LV {{ $row['level'] }}
                                                            </span>
                                                            <span class="text-[10px] font-bold text-gray-500">{{ count($row['achievements']) }} Huy Hiệu</span>
                                                        </div>
                                                    </div>
                                                </div>
                                                <button @click="openModal = null" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition bg-white dark:bg-gray-800 rounded-full p-1.5 shadow-sm border border-gray-200 dark:border-gray-700">
                                                    <x-filament::icon icon="heroicon-o-x-mark" class="w-5 h-5" />
                                                </button>
                                            </div>
                                            
                                            <!-- Body / Achievements Cabinet -->
                                            <div class="p-5 overflow-y-auto flex-1 flex flex-col">
                                                @if(count($row['achievements']) === 0)
                                                    <div class="py-10 flex flex-col items-center justify-center text-center">
                                                        <x-filament::icon icon="heroicon-o-face-frown" class="w-12 h-12 text-gray-300 dark:text-gray-600 mb-3" />
                                                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Người chơi này chưa đạt thành tựu nào.</p>
                                                    </div>
                                                @else
                                                    <!-- Grid Cabinet -->
                                                    <div class="grid grid-cols-4 sm:grid-cols-5 gap-2 max-h-[220px] overflow-y-auto p-2 bg-gray-50 dark:bg-gray-950 rounded-xl border border-gray-100 dark:border-gray-800/80 shadow-inner">
                                                        @foreach($row['achievements'] as $achIndex => $ach)
                                                            @php
                                                                $colorClass = match($ach['color']) {
                                                                    'primary' => 'text-primary-600 dark:text-primary-400 bg-primary-50 dark:bg-primary-500/10 border-primary-200 dark:border-primary-500/20',
                                                                    'success' => 'text-success-600 dark:text-success-400 bg-success-50 dark:bg-success-500/10 border-success-200 dark:border-success-500/20',
                                                                    'warning' => 'text-warning-600 dark:text-warning-400 bg-warning-50 dark:bg-warning-500/10 border-warning-200 dark:border-warning-500/20',
                                                                    'danger' => 'text-danger-600 dark:text-danger-400 bg-danger-50 dark:bg-danger-500/10 border-danger-200 dark:border-danger-500/20',
                                                                    'info' => 'text-info-600 dark:text-info-400 bg-info-50 dark:bg-info-500/10 border-info-200 dark:border-info-500/20',
                                                                    'indigo' => 'text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-500/10 border-indigo-200 dark:border-indigo-500/20',
                                                                    'fuchsia' => 'text-fuchsia-600 dark:text-fuchsia-400 bg-fuchsia-50 dark:bg-fuchsia-500/10 border-fuchsia-200 dark:border-fuchsia-500/20',
                                                                    default => 'text-gray-600 dark:text-gray-400 bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700',
                                                                };
                                                                $iconColorClass = match($ach['color']) {
                                                                    'primary' => 'text-primary-500',
                                                                    'success' => 'text-success-500',
                                                                    'warning' => 'text-warning-500',
                                                                    'danger' => 'text-danger-500',
                                                                    'info' => 'text-info-500',
                                                                    'indigo' => 'text-indigo-500',
                                                                    'fuchsia' => 'text-fuchsia-500',
                                                                    default => 'text-gray-500',
                                                                };
                                                            @endphp
                                                            <button @click="activeAchIndex = {{ $achIndex }}"
                                                                    :class="activeAchIndex === {{ $achIndex }} ? 'ring-2 ring-primary-500 bg-white dark:bg-gray-900 border-primary-500 dark:border-primary-500' : 'opacity-80 hover:opacity-100'"
                                                                    class="aspect-square rounded-xl border {{ $colorClass }} flex flex-col items-center justify-center p-1.5 transition hover:scale-105 relative shadow-sm">
                                                                <x-filament::icon :icon="$ach['icon']" class="w-7 h-7 {{ $iconColorClass }}" />
                                                                <span class="text-[9px] font-semibold mt-1 truncate w-full text-center">{{ $ach['name'] }}</span>
                                                                @if($ach['is_main'])
                                                                    <span class="absolute -top-1 -right-1 flex h-3.5 w-3.5 items-center justify-center rounded-full bg-amber-500 text-[8px] font-black text-white shadow-sm ring-1 ring-white">★</span>
                                                                @endif
                                                            </button>
                                                        @endforeach
                                                    </div>

                                                    <!-- Details Panel -->
                                                    <div class="mt-4 p-4 rounded-xl bg-gray-50 dark:bg-gray-950 border border-gray-100 dark:border-gray-800 flex flex-col min-h-[110px] relative overflow-hidden">
                                                        @foreach($row['achievements'] as $achIndex => $ach)
                                                            @php
                                                                $colorClass = match($ach['color']) {
                                                                    'primary' => 'text-primary-600 dark:text-primary-400 bg-primary-50 dark:bg-primary-500/10 border-primary-200 dark:border-primary-500/20',
                                                                    'success' => 'text-success-600 dark:text-success-400 bg-success-50 dark:bg-success-500/10 border-success-200 dark:border-success-500/20',
                                                                    'warning' => 'text-warning-600 dark:text-warning-400 bg-warning-50 dark:bg-warning-500/10 border-warning-200 dark:border-warning-500/20',
                                                                    'danger' => 'text-danger-600 dark:text-danger-400 bg-danger-50 dark:bg-danger-500/10 border-danger-200 dark:border-danger-500/20',
                                                                    'info' => 'text-info-600 dark:text-info-400 bg-info-50 dark:bg-info-500/10 border-info-200 dark:border-info-500/20',
                                                                    'indigo' => 'text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-500/10 border-indigo-200 dark:border-indigo-500/20',
                                                                    'fuchsia' => 'text-fuchsia-600 dark:text-fuchsia-400 bg-fuchsia-50 dark:bg-fuchsia-500/10 border-fuchsia-200 dark:border-fuchsia-500/20',
                                                                    default => 'text-gray-600 dark:text-gray-400 bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700',
                                                                };
                                                                $iconColorClass = match($ach['color']) {
                                                                    'primary' => 'text-primary-500',
                                                                    'success' => 'text-success-500',
                                                                    'warning' => 'text-warning-500',
                                                                    'danger' => 'text-danger-500',
                                                                    'info' => 'text-info-500',
                                                                    'indigo' => 'text-indigo-500',
                                                                    'fuchsia' => 'text-fuchsia-500',
                                                                    default => 'text-gray-500',
                                                                };
                                                            @endphp
                                                            <div x-show="activeAchIndex === {{ $achIndex }}" 
                                                                 class="flex items-center gap-4 w-full" 
                                                                 x-transition:enter="transition ease-out duration-200"
                                                                 x-transition:enter-start="opacity-0 translate-y-1"
                                                                 x-transition:enter-end="opacity-100 translate-y-0"
                                                                 style="display: none;">
                                                                <div class="w-14 h-14 rounded-2xl flex items-center justify-center shrink-0 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 shadow-sm">
                                                                    <x-filament::icon :icon="$ach['icon']" class="w-8 h-8 {{ $iconColorClass }}" />
                                                                </div>
                                                                <div class="flex-1 min-w-0">
                                                                    <div class="flex items-center gap-2">
                                                                        <h4 class="text-sm font-extrabold text-gray-900 dark:text-white truncate">{{ $ach['name'] }}</h4>
                                                                        @if($ach['is_main'])
                                                                            <span class="px-1.5 py-0.5 rounded text-[8px] font-black uppercase tracking-wider bg-amber-500 text-white shadow-sm">Cấp chính</span>
                                                                        @else
                                                                            <span class="px-1.5 py-0.5 rounded text-[8px] font-black uppercase tracking-wider bg-gray-200 dark:bg-gray-800 text-gray-500 dark:text-gray-400 shadow-sm">Phụ</span>
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
                    @if($activeTab === 'season') * Xếp theo hiệu suất tích lũy toàn bộ mùa giải.
                    @elseif($activeTab === 'week') * Hiệu suất tính từ đầu tuần hiện tại (Thứ 2 00:00 giờ VN).
                    @elseif($activeTab === 'round') * Hiệu suất tính trong 7 ngày gần nhất.
                    @elseif($activeTab === 'roi') * Chỉ hiển thị người chơi có ≥5 phiếu đã settle. ROI = lãi ròng / tổng cược.
                    @elseif($activeTab === 'exact_score') * Xếp theo số lần dự đoán tỉ số chính xác.
                    @elseif($activeTab === 'missions') * Xếp theo tổng NV đã hoàn thành. Tiến độ tuần tính trên các NV Tuần hiện đang mở.
                    @elseif($activeTab === 'level') * Xếp theo Level cao nhất đạt được từ danh hiệu chính.
                    @elseif($activeTab === 'badges') * Xếp theo tổng số danh hiệu đạt được (chính + phụ).
                    @endif
                </p>
            @endif
        </x-filament::card>
    </div>
</x-filament-panels::page>
