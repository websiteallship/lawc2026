<x-filament-panels::page>
    @php
        $stageNames = [
            'GROUP_STAGE' => 'Vòng bảng',
            'ROUND_OF_32' => 'Vòng 32',
            'ROUND_OF_16' => 'Vòng 16',
            'QUARTER_FINAL' => 'Tứ kết',
            'SEMI_FINAL' => 'Bán kết',
            'THIRD_PLACE_PLAYOFF' => 'Tranh hạng 3',
            'FINAL' => 'Chung kết'
        ];
    @endphp
    <div class="space-y-6" wire:poll.30s>
        {{-- Nút Quay Lại --}}
        <div>
            <a href="{{ App\Filament\Player\Pages\MatchListPage::getUrl() }}" class="inline-flex items-center gap-1 text-sm font-medium text-emerald-600 hover:text-emerald-700">
                <x-filament::icon icon="heroicon-m-arrow-left" class="w-4 h-4" /> Trở lại danh sách
            </a>
        </div>

        {{-- Header Trận Đấu --}}
        <x-filament::card class="relative overflow-hidden">
            <div class="flex flex-col items-center p-2 sm:p-4">
                <div class="flex flex-wrap justify-center items-center gap-2 mb-4">
                    @if($match->season)
                        <span class="text-xs font-bold text-gray-500 dark:text-gray-400 tracking-wider uppercase bg-gray-100 dark:bg-gray-700/60 px-2 py-0.5 rounded" title="Mùa giải">
                            <x-filament::icon icon="heroicon-m-trophy" class="inline w-3 h-3 mr-1"/>{{ $match->season->name }}
                        </span>
                    @endif
                    <span class="text-xs font-bold text-gray-500 dark:text-gray-400 tracking-wider uppercase bg-gray-100 dark:bg-gray-700/60 px-2 py-0.5 rounded" title="Mã trận">
                        <x-filament::icon icon="heroicon-m-hashtag" class="inline w-3 h-3 mr-1"/>{{ $match->match_code }}
                    </span>
                    <span class="text-xs font-bold text-gray-500 dark:text-gray-400 tracking-wider uppercase bg-gray-100 dark:bg-gray-700/60 px-2 py-0.5 rounded" title="Vòng đấu">
                        {{ $stageNames[$match->stage] ?? $match->stage }}
                    </span>
                    @if($match->group)
                        <span class="px-2 py-0.5 rounded text-[10px] font-extrabold bg-indigo-50 dark:bg-indigo-950/50 text-indigo-600 dark:text-indigo-400 border border-indigo-200 dark:border-indigo-800/80 shadow-sm" title="Bảng đấu">{{ $match->group }}</span>
                    @endif
                </div>
                
                <div class="flex items-start justify-between w-full max-w-xl gap-1 sm:gap-8 px-1 sm:px-0">
                    <div class="flex flex-col items-center flex-1 min-w-0">
                        <div class="text-6xl mb-2 sm:mb-3 shadow-md rounded-lg overflow-hidden flex items-center justify-center w-14 h-10 sm:w-28 sm:h-20 bg-gray-100 dark:bg-gray-700 shrink-0">
                            {!! App\Helpers\CountryFlagHelper::renderHtml($match->home_team) !!}
                        </div>
                        <span class="font-bold text-xs sm:text-xl text-center leading-tight line-clamp-2 text-gray-800 dark:text-white">{{ $match->home_team }}</span>
                    </div>

                    <div class="flex flex-col items-center shrink-0 px-1 sm:px-4">
                        @if(!is_null($match->home_score) && !is_null($match->away_score))
                            <div class="text-2xl sm:text-5xl font-black text-emerald-600 dark:text-emerald-400 mb-1 sm:mb-2 tracking-tighter">
                                {{ $match->home_score }} - {{ $match->away_score }}
                            </div>
                        @else
                            <span class="text-[9px] sm:text-xs font-bold text-gray-400 mb-1 sm:mb-2 px-1.5 sm:px-2 py-0.5 bg-gray-100 dark:bg-gray-800 rounded-full">VS</span>
                            <div class="text-lg sm:text-2xl font-black text-gray-900 dark:text-white bg-gray-50 dark:bg-gray-900 border border-gray-100 dark:border-gray-800 rounded px-2 py-1 sm:px-3 sm:py-1.5 font-mono shadow-sm mb-1 sm:mb-2">
                                {{ $match->kickoff_at->format('H:i') }}
                            </div>
                        @endif

                        <div class="flex flex-col items-center space-y-0.5 text-center">
                            <div class="text-[10px] sm:text-[11px] text-gray-500 flex flex-col sm:flex-row items-center gap-0.5 sm:gap-1">
                                <span class="hidden sm:inline">Bắt đầu:</span>
                                <span class="font-semibold text-gray-700 dark:text-gray-300 whitespace-nowrap">{{ $match->kickoff_at->format('H:i d/m/Y') }}</span>
                            </div>
                            @if($match->finished_at)
                                <div class="text-[10px] sm:text-[11px] text-gray-500 flex flex-col sm:flex-row items-center gap-0.5 sm:gap-1">
                                    <span class="hidden sm:inline">Kết thúc:</span>
                                    <span class="font-semibold text-gray-700 dark:text-gray-300 whitespace-nowrap">{{ $match->finished_at->format('H:i d/m/Y') }}</span>
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="flex flex-col items-center flex-1 min-w-0">
                        <div class="text-6xl mb-2 sm:mb-3 shadow-md rounded-lg overflow-hidden flex items-center justify-center w-14 h-10 sm:w-28 sm:h-20 bg-gray-100 dark:bg-gray-700 shrink-0">
                            {!! App\Helpers\CountryFlagHelper::renderHtml($match->away_team) !!}
                        </div>
                        <span class="font-bold text-xs sm:text-xl text-center leading-tight line-clamp-2 text-gray-800 dark:text-white">{{ $match->away_team }}</span>
                    </div>
                </div>

                <div class="mt-6 flex flex-col items-center gap-3">
                    @if($match->venue)
                        <div class="flex items-center gap-1.5 text-sm font-medium text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-gray-800/50 px-3 py-1.5 rounded-lg border border-gray-100 dark:border-gray-700">
                            <x-filament::icon icon="heroicon-o-map-pin" class="w-4 h-4 text-gray-400" />
                            <span>{{ $match->venue }}</span>
                        </div>
                    @endif
                    <div>
                        @if(in_array($match->status, ['FINISHED', 'SETTLED']))
                            <span class="px-3 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300">Đã kết thúc</span>
                        @elseif(in_array($match->status, ['POSTPONED', 'CANCELLED']))
                            <span class="px-3 py-1 rounded-full text-sm font-medium bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400">Đã hủy/Hoãn</span>
                        @elseif($match->status === 'LIVE')
                            <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full text-sm font-bold tracking-wide bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-400 border border-red-200 dark:border-red-800 shadow-sm animate-pulse">
                                <span class="w-2 h-2 rounded-full bg-red-600 dark:bg-red-500"></span>
                                @if(in_array($match->detailed_status, ['HT', 'BT', 'P', 'SUSP', 'INT']))
                                    {{ match($match->detailed_status) {
                                        'HT' => 'Nghỉ giữa hiệp',
                                        'BT' => 'Nghỉ hiệp phụ',
                                        'P' => 'Đá luân lưu',
                                        'SUSP' => 'Tạm dừng',
                                        'INT' => 'Gián đoạn',
                                        default => $match->detailed_status
                                    } }}
                                @else
                                    LIVE {{ $match->elapsed_minutes ? $match->elapsed_minutes . "'" : '' }}
                                @endif
                            </span>
                        @elseif($match->kickoff_at > now())
                            <span class="px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400">Sắp diễn ra</span>
                        @else
                            <span class="px-3 py-1 rounded-full text-sm font-medium bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400">Đã khóa</span>
                        @endif
                    </div>
                </div>
            </div>
        </x-filament::card>



        {{-- Match Timeline (Events) --}}
        @if($match->events && $match->events->count() > 0)
            <x-filament::card>
                <div x-data="{ showAll: false, limit: 5 }">
                    <h3 class="text-base font-bold text-gray-800 dark:text-white mb-4 flex items-center gap-2">
                        <x-filament::icon icon="heroicon-o-clock" class="w-5 h-5 text-blue-500" />
                        Diễn biến trận đấu
                    </h3>
                    <div class="relative border-l-2 border-gray-100 dark:border-gray-800 ml-3 md:ml-6 space-y-4 pb-2">
                        @foreach($match->events->sortByDesc('minute')->values() as $index => $event)
                            @php
                                $isGoal = $event->type === 'GOAL';
                                $icon = match($event->type) {
                                    'GOAL' => 'heroicon-s-star',
                                    'CARD' => 'heroicon-o-exclamation-triangle',
                                    'SUB' => 'heroicon-o-arrows-right-left',
                                    default => 'heroicon-o-information-circle'
                                };
                                $iconColor = match($event->type) {
                                    'GOAL' => 'text-white bg-emerald-50 border-emerald-200 dark:bg-emerald-900/50 dark:border-emerald-700 shadow-sm animate-[bounce_2s_infinite]',
                                    'CARD' => str_contains(strtoupper($event->detail ?? ''), 'YELLOW') ? 'text-yellow-500 bg-white dark:bg-gray-800' : 'text-red-500 bg-white dark:bg-gray-800',
                                    'SUB' => 'text-blue-500 bg-white dark:bg-gray-800',
                                    default => 'text-gray-500 bg-white dark:bg-gray-800'
                                };
                                $isHome = $event->team_type === 'HOME';
                            @endphp
                            <div x-show="showAll || {{ $index }} < limit" x-transition.opacity.duration.300ms class="relative pl-6 md:pl-8 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-2 border-b border-gray-50 dark:border-gray-800/50 pb-3 last:border-0 last:pb-0 {{ $isGoal ? 'bg-gradient-to-r from-emerald-50/50 to-transparent dark:from-emerald-900/10' : '' }}">
                                {{-- Icon marker --}}
                                <div class="absolute -left-[13px] top-1 w-6 h-6 rounded-full flex items-center justify-center shadow-sm border border-gray-200 dark:border-gray-700 {!! $iconColor !!}">
                                    @if($isGoal)
                                        <span class="text-[12px] leading-none">⚽</span>
                                    @else
                                        <x-filament::icon icon="{{ $icon }}" class="w-3.5 h-3.5" />
                                    @endif
                                </div>
                                
                                <div class="flex items-center gap-3 w-full">
                                    <span class="font-bold text-sm {{ $isGoal ? 'text-emerald-600 dark:text-emerald-400 text-base' : 'text-gray-500' }} w-8 shrink-0">{{ $event->minute }}'{{ $event->injury_time ? '+'.$event->injury_time : '' }}</span>
                                    
                                    <div class="flex-1 flex flex-col">
                                        <div class="flex items-center gap-2">
                                            <span class="font-bold text-sm {{ $isGoal ? 'text-emerald-700 dark:text-emerald-300 text-base' : 'text-gray-800 dark:text-gray-200' }}">{{ $event->player_name ?? 'Cầu thủ' }}</span>
                                            @if($event->type === 'GOAL')
                                                @php
                                                    $goalDetail = match(strtoupper($event->detail ?? '')) {
                                                        'PENALTY' => 'Penalty',
                                                        'OWN GOAL', 'OWN_GOAL' => 'Phản lưới',
                                                        'MISSED PENALTY' => 'Hỏng Pen',
                                                        default => 'Vào'
                                                    };
                                                @endphp
                                                <span class="text-[10px] sm:text-xs font-bold text-emerald-600 bg-emerald-100 dark:bg-emerald-900/50 dark:text-emerald-300 px-2 py-0.5 rounded shadow-sm border border-emerald-200 dark:border-emerald-800 uppercase tracking-wider">{{ $goalDetail }}</span>
                                            @elseif($event->type === 'CARD')
                                                @php
                                                    $isYellow = str_contains(strtoupper($event->detail ?? ''), 'YELLOW');
                                                @endphp
                                                <span class="text-xs font-bold {{ $isYellow ? 'text-yellow-600 bg-yellow-100 dark:bg-yellow-900/40 dark:text-yellow-400' : 'text-red-600 bg-red-100 dark:bg-red-900/40 dark:text-red-400' }} px-1.5 py-0.5 rounded">{{ $isYellow ? 'Thẻ Vàng' : 'Thẻ Đỏ' }}</span>
                                            @elseif($event->type === 'SUB')
                                                <span class="text-xs font-bold text-blue-600 bg-blue-100 dark:bg-blue-900/40 dark:text-blue-400 px-1.5 py-0.5 rounded">Vào sân</span>
                                            @endif
                                        </div>
                                        @if($event->related_player_name)
                                            <div class="text-xs {{ $isGoal ? 'text-emerald-600/90 dark:text-emerald-400/90 font-medium' : 'text-gray-500' }} mt-0.5 flex items-center gap-1">
                                                @if($event->type === 'GOAL')
                                                    <x-filament::icon icon="heroicon-m-arrow-turn-down-right" class="w-3 h-3 {{ $isGoal ? 'text-emerald-500' : 'text-gray-400' }}" /> Kiến tạo: {{ $event->related_player_name }}
                                                @elseif($event->type === 'SUB')
                                                    <x-filament::icon icon="heroicon-m-arrow-left-on-rectangle" class="w-3 h-3 text-red-400 dark:text-red-500" /> Ra sân: {{ $event->related_player_name }}
                                                @endif
                                            </div>
                                        @endif
                                    </div>

                                    <div class="shrink-0 text-right w-24">
                                        <span class="text-xs font-bold {{ $isGoal ? 'text-emerald-600 dark:text-emerald-400' : 'text-gray-500 dark:text-gray-400' }} uppercase">{{ $isHome ? $match->home_team : $match->away_team }}</span>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    
                    @if($match->events->count() > 5)
                        <div class="mt-4 flex justify-center pb-2">
                            <button 
                                type="button" 
                                x-on:click="showAll = !showAll" 
                                class="text-sm font-medium text-emerald-600 hover:text-emerald-700 bg-emerald-50 hover:bg-emerald-100 dark:bg-emerald-900/30 dark:hover:bg-emerald-900/50 dark:text-emerald-400 px-5 py-2 rounded-xl transition-all active:scale-95 flex items-center gap-2 border border-emerald-100 dark:border-emerald-800"
                            >
                                <span x-text="showAll ? 'Thu gọn' : 'Xem tất cả diễn biến ({{ $match->events->count() }})'"></span>
                                <x-filament::icon icon="heroicon-m-chevron-down" class="w-4 h-4 transition-transform duration-300" x-bind:class="showAll ? 'rotate-180' : ''" />
                            </button>
                        </div>
                    @endif
                </div>
            </x-filament::card>
        @endif

        {{-- My Bets (User's Logs) --}}
        @if($this->myBets->count() > 0)
            <x-filament::card>
                <h3 class="text-base font-bold text-gray-800 dark:text-white mb-4 flex items-center gap-2">
                    <x-filament::icon icon="heroicon-o-ticket" class="w-5 h-5 text-emerald-500" />
                    Dự đoán của bạn
                </h3>
                <div class="space-y-3">
                    @foreach($this->myBets as $bet)
                        <div class="border border-gray-100 dark:border-gray-700/50 rounded-xl p-3 bg-gray-50 dark:bg-gray-800/30 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                            <div class="space-y-1">
                                <div class="text-xs text-gray-500 font-medium flex items-center gap-2">
                                    <span class="px-2 py-0.5 rounded bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300">{{ match($bet->market_type_snapshot) {
                                        'ASIAN_HANDICAP' => 'Handicap',
                                        'OVER_UNDER' => 'Tài / Xỉu',
                                        'EXACT_SCORE' => 'Tỉ số',
                                        '1X2' => '1X2',
                                        default => $bet->market_type_snapshot
                                    } }}</span>
                                    <span>-</span>
                                    <span>{{ match($bet->period_type_snapshot) {
                                        'FULL_TIME' => 'Cả trận',
                                        'FIRST_HALF' => 'Hiệp 1',
                                        'SECOND_HALF' => 'Hiệp 2',
                                        'EXTRA_TIME' => 'Hiệp phụ',
                                        'PENALTY' => 'Luân lưu',
                                        default => $bet->period_type_snapshot
                                    } }}</span>
                                    <span class="text-gray-400">&bull;</span>
                                    <span>{{ $bet->created_at->format('H:i d/m') }}</span>
                                </div>
                                @php
                                    $displayName = $bet->label_snapshot;
                                    if ($displayName === 'Đội Nhà' || $bet->selection_side_snapshot === 'HOME') {
                                        $displayName = $match->home_team;
                                    } elseif ($displayName === 'Đội Khách' || $bet->selection_side_snapshot === 'AWAY') {
                                        $displayName = $match->away_team;
                                    }
                                @endphp
                                <div class="font-bold text-sm text-gray-800 dark:text-gray-200 flex items-center gap-2">
                                    <span>{{ $displayName }}</span>
                                    @if(in_array($bet->market_type_snapshot, ['ASIAN_HANDICAP', 'OVER_UNDER']) && !is_null($bet->line_snapshot))
                                        @php
                                            $line = (float)$bet->line_snapshot;
                                            if ($bet->market_type_snapshot === 'ASIAN_HANDICAP' && $line > 0) $line = '+' . $line;
                                        @endphp
                                        <span class="text-xs bg-gray-200 dark:bg-gray-700 px-1.5 py-0.5 rounded text-gray-700 dark:text-gray-300">{{ $line }}</span>
                                    @endif
                                    <span class="text-emerald-600 dark:text-emerald-400 font-extrabold">ăn {{ number_format((float)$bet->profit_rate_snapshot, 2) }}</span>
                                </div>
                            </div>
                            
                            <div class="flex items-center justify-between sm:justify-end gap-3 pt-2 sm:pt-0 border-t border-gray-100 dark:border-gray-800/60 sm:border-t-0 shrink-0 w-full sm:w-auto">
                                <div class="flex flex-row sm:flex-col items-center sm:items-end justify-between sm:justify-start flex-1 sm:flex-initial gap-2 sm:gap-1">
                                    <div class="text-xs text-gray-500 font-medium">
                                        Mức cược: <span class="font-bold text-gray-700 dark:text-gray-300">{{ number_format($bet->stake) }} Lá</span>
                                    </div>
                                    @if(in_array($bet->status->value, ['WON', 'HALF_WON', 'LOST', 'HALF_LOST', 'PUSH']))
                                        @php
                                            $net = $bet->net_result ?? (($bet->gross_payout ?? 0) - $bet->stake);
                                        @endphp
                                        <div class="flex items-center gap-1.5 sm:flex-col sm:items-end">
                                            <div class="font-black text-sm sm:text-base {{ $net > 0 ? 'text-emerald-600' : ($net < 0 ? 'text-red-500' : 'text-gray-500') }}">
                                                {{ $net > 0 ? '+' : '' }}{{ number_format($net) }} Lá
                                            </div>
                                            <div class="text-[9px] sm:text-[10px] uppercase font-bold {{ $net > 0 ? 'text-emerald-600' : ($net < 0 ? 'text-red-500' : 'text-gray-500') }}">
                                                {{ match($bet->status->value) {
                                                    'WON' => 'Thắng đủ',
                                                    'HALF_WON' => 'Thắng nửa',
                                                    'LOST' => 'Thua đủ',
                                                    'HALF_LOST' => 'Thua nửa',
                                                    'PUSH' => 'Hòa tiền',
                                                    default => $bet->status->value
                                                } }}
                                            </div>
                                        </div>
                                    @elseif($bet->status->value === 'VOIDED')
                                        <div class="font-bold text-sm text-gray-500">Đã hoàn</div>
                                    @else
                                        <div class="inline-flex items-center gap-1.5 px-2 py-1 rounded-md bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400 text-xs font-bold shrink-0">
                                            <x-filament::icon icon="heroicon-m-clock" class="w-3.5 h-3.5" /> Chờ kết quả
                                        </div>
                                    @endif
                                </div>
                                
                                @if($bet->status->value === 'PENDING' && $bet->market && $bet->market->status === 'OPEN' && now()->lt($bet->market->close_at))
                                    <button wire:click="$dispatch('openEditBetModal', { betId: {{ $bet->id }} })" 
                                        class="bg-amber-50 hover:bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:hover:bg-amber-900/50 dark:text-amber-400 p-2 rounded-lg shadow-sm border border-amber-200 dark:border-amber-800 transition-colors flex items-center justify-center shrink-0"
                                        title="Sửa dự đoán">
                                        <x-filament::icon icon="heroicon-o-pencil-square" class="w-4 h-4" />
                                    </button>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </x-filament::card>
        @endif

        {{-- Period Tabs --}}
        <div class="flex gap-2 overflow-x-auto pb-2 scrollbar-hide border-b border-gray-100 dark:border-gray-800">
            @foreach([
                'FULL_TIME' => 'Cả trận',
                'FIRST_HALF' => 'Hiệp 1',
                'SECOND_HALF' => 'Hiệp 2',
                'EXTRA_TIME' => 'Hiệp phụ',
                'PENALTY' => 'Luân lưu',
            ] as $key => $label)
                <button wire:click="$set('activePeriod', '{{ $key }}')"
                    @class([
                        'px-4 py-3 text-sm font-medium whitespace-nowrap border-b-2 transition-colors',
                        'border-emerald-500 text-emerald-600 dark:text-emerald-400' => $activePeriod === $key,
                        'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:hover:text-gray-300' => $activePeriod !== $key,
                    ])>
                    {{ $label }}
                </button>
            @endforeach
        </div>

        {{-- Markets --}}
        @php
            $marketsByType = $this->marketsByType;
            $typeNames = [
                'ASIAN_HANDICAP' => 'Dự đoán Handicap (Châu Á)',
                'OVER_UNDER' => 'Dự đoán Tài / Xỉu',
                'EXACT_SCORE' => 'Dự đoán Tỉ số chính xác',
                '1X2' => 'Dự đoán Thắng / Hòa / Thua'
            ];
        @endphp

        <div class="space-y-6">
            @forelse($marketsByType as $type => $markets)
                <x-filament::card>
                    <h3 class="text-lg font-bold text-gray-800 dark:text-gray-100 border-b border-gray-100 dark:border-gray-700 pb-3 mb-4 flex flex-wrap items-center justify-between gap-2">
                        <div class="flex flex-wrap items-center gap-2">
                            <div class="w-1.5 h-5 bg-emerald-500 rounded-full"></div>
                            <span>{{ $typeNames[$type] ?? $type }}</span>
                            
                            @php
                                $keoTren = null;
                                $keoDuoi = null;
                                if ($type === 'ASIAN_HANDICAP') {
                                    foreach ($markets as $m) {
                                        foreach ($m->outcomes as $oc) {
                                            if (!is_null($oc->line_value) && (float)$oc->line_value < 0) {
                                                $keoTren = in_array($oc->selection_side, ['HOME']) || $oc->label === 'Đội Nhà' || $oc->label === $match->home_team ? $match->home_team : $match->away_team;
                                                $keoDuoi = ($keoTren === $match->home_team) ? $match->away_team : $match->home_team;
                                                break 2;
                                            }
                                        }
                                    }
                                }
                            @endphp

                            <span class="text-sm font-medium bg-gray-50 dark:bg-gray-800/80 border border-gray-200 dark:border-gray-700 px-3 py-1 rounded-full ml-0 sm:ml-2 shadow-sm">
                                @if($keoTren && $keoDuoi)
                                    @php
                                        $keoTrenColor = $keoTren === $match->home_team ? 'text-emerald-600 dark:text-emerald-400' : 'text-blue-600 dark:text-blue-400';
                                        $keoDuoiColor = $keoDuoi === $match->home_team ? 'text-emerald-600 dark:text-emerald-400' : 'text-blue-600 dark:text-blue-400';
                                    @endphp
                                    <span class="text-gray-500 dark:text-gray-400 text-xs">Kèo trên:</span> <span class="{{ $keoTrenColor }} font-bold">{{ $keoTren }}</span> 
                                    <span class="mx-1.5 text-gray-300 dark:text-gray-600">|</span> 
                                    <span class="text-gray-500 dark:text-gray-400 text-xs">Kèo dưới:</span> <span class="{{ $keoDuoiColor }} font-bold">{{ $keoDuoi }}</span>
                                @else
                                    <span class="text-gray-500 dark:text-gray-400 text-xs">Chủ:</span> <span class="text-emerald-600 dark:text-emerald-400 font-bold">{{ $match->home_team }}</span> 
                                    <span class="mx-1.5 text-gray-300 dark:text-gray-600">|</span> 
                                    <span class="text-gray-500 dark:text-gray-400 text-xs">Khách:</span> <span class="text-blue-600 dark:text-blue-400 font-bold">{{ $match->away_team }}</span>
                                @endif
                            </span>
                        </div>
                        @if(!in_array($match->status, ['FINISHED', 'SETTLED', 'POSTPONED', 'CANCELLED']) && $lastUpdated = $markets->max('updated_at'))
                            @php
                                $parsedTime = \Carbon\Carbon::parse($lastUpdated);
                            @endphp
                            <div class="text-xs font-medium text-red-600 dark:text-red-400 flex items-center gap-1.5 bg-red-50 dark:bg-red-900/20 px-2 py-1 rounded-md">
                                <x-filament::icon icon="heroicon-m-clock" class="w-3.5 h-3.5" />
                                <span>Cập nhật: {{ $parsedTime->diffForHumans() }} ({{ $parsedTime->format('H:i d/m') }})</span>
                            </div>
                        @endif
                    </h3>

                    <div class="grid grid-cols-1 @if($type !== 'EXACT_SCORE') md:grid-cols-2 @endif gap-4">
                        @foreach($markets as $market)
                            <div class="border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden shadow-sm hover:shadow transition-shadow bg-white dark:bg-gray-900">
                                <div class="bg-gray-50 dark:bg-gray-800/50 px-4 py-3 flex flex-wrap gap-2 justify-between items-center border-b border-gray-200 dark:border-gray-700 relative">
                                    <div class="flex flex-col z-10">
                                        @php
                                            $marketName = $market->name ?: 'Kèo: ' . $market->line;
                                            $marketName = str_replace(['Goals Over/Under', 'Exact Score'], ['Tài/Xỉu', 'Tỉ số chính xác'], $marketName);
                                        @endphp
                                        <span class="font-bold text-sm text-gray-700 dark:text-gray-300">
                                            {{ $marketName }}
                                        </span>
                                        @if($type === 'EXACT_SCORE')
                                            <span class="sm:hidden text-[11px] font-medium text-gray-500 mt-0.5 flex items-center gap-1">
                                                <span class="text-emerald-600 dark:text-emerald-400 font-bold">{{ $match->home_team }}</span> - <span class="text-blue-600 dark:text-blue-400 font-bold">{{ $match->away_team }}</span>
                                            </span>
                                        @endif
                                    </div>
                                    
                                    @if($type === 'EXACT_SCORE')
                                        <div class="absolute inset-0 flex items-center justify-center pointer-events-none hidden sm:flex">
                                            <div class="flex items-center gap-2 text-base md:text-lg font-black tracking-tight opacity-90">
                                                <span class="text-emerald-600 dark:text-emerald-400">{{ $match->home_team }}</span>
                                                <span class="text-gray-300 dark:text-gray-600 mx-1">-</span>
                                                <span class="text-blue-600 dark:text-blue-400">{{ $match->away_team }}</span>
                                            </div>
                                        </div>
                                    @endif
                                    
                                    <div class="z-10">
                                        @if($market->status === 'SETTLED')
                                            <span class="inline-flex items-center gap-1 text-xs font-semibold bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400 px-2 py-0.5 rounded-md">
                                                <x-filament::icon icon="heroicon-m-check-circle" class="w-3.5 h-3.5" /> Quyết toán
                                            </span>
                                        @elseif($market->status === 'VOIDED')
                                            <span class="inline-flex items-center gap-1 text-xs font-semibold bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-400 px-2 py-0.5 rounded-md">
                                                <x-filament::icon icon="heroicon-m-x-circle" class="w-3.5 h-3.5" /> Đã hủy
                                            </span>
                                        @elseif($market->status === 'LOCKED')
                                            <span class="inline-flex items-center gap-1 text-xs font-semibold bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400 px-2 py-0.5 rounded-md">
                                                <x-filament::icon icon="heroicon-m-lock-closed" class="w-3.5 h-3.5" /> Đã đóng
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 text-xs font-semibold bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400 px-2 py-0.5 rounded-md">
                                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span> Mở
                                            </span>
                                        @endif
                                    </div>
                                </div>
                                
                                @if($type === 'EXACT_SCORE')
                                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-2.5 p-3">
                                        @foreach($market->outcomes as $outcome)
                                            <button 
                                                @if(in_array($market->status, ['OPEN'])) wire:click="selectOutcome({{ $outcome->id }})" @endif
                                                @class([
                                                    'relative flex flex-col items-center justify-center p-3 rounded-xl border transition-all duration-200',
                                                    'bg-gray-50 border-gray-200 dark:bg-gray-800/50 dark:border-gray-700 hover:border-emerald-400 hover:bg-emerald-50/50 dark:hover:bg-emerald-900/20 dark:hover:border-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-500/50' => in_array($market->status, ['OPEN']),
                                                    'bg-gray-100 border-gray-200 dark:bg-gray-800 dark:border-gray-700 opacity-60 cursor-not-allowed' => !in_array($market->status, ['OPEN']),
                                                ])
                                                @if(!in_array($market->status, ['OPEN'])) disabled @endif
                                            >
                                                @php
                                                    $scoreParts = explode(':', str_replace('-', ':', $outcome->label));
                                                @endphp
                                                @if(count($scoreParts) === 2)
                                                    <div class="flex items-center gap-1.5 text-lg font-black">
                                                        <span class="text-emerald-600 dark:text-emerald-400">{{ trim($scoreParts[0]) }}</span>
                                                        <span class="text-gray-400 text-sm">-</span>
                                                        <span class="text-blue-600 dark:text-blue-400">{{ trim($scoreParts[1]) }}</span>
                                                    </div>
                                                @else
                                                    <span class="text-lg font-black text-gray-800 dark:text-gray-100">{{ $outcome->label }}</span>
                                                @endif
                                                <span class="text-xs font-bold text-emerald-600 dark:text-emerald-400 mt-1">ăn {{ number_format($outcome->profit_rate, 2) }}</span>
                                                
                                                @if($market->status !== 'LOCKED')
                                                    <div class="absolute inset-0 border-2 border-transparent hover:border-emerald-500 rounded-xl transition-colors pointer-events-none"></div>
                                                @endif
                                            </button>
                                        @endforeach
                                    </div>
                                @else
                                    <div class="divide-y divide-gray-100 dark:divide-gray-700/50">
                                        @foreach($market->outcomes as $outcome)
                                            @php
                                                $displayName = $outcome->label;
                                                if ($displayName === 'Đội Nhà' || $outcome->selection_side === 'HOME') {
                                                    $displayName = $match->home_team;
                                                } elseif ($displayName === 'Đội Khách' || $outcome->selection_side === 'AWAY') {
                                                    $displayName = $match->away_team;
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
                                            @endphp
                                            <div class="flex justify-between items-center p-3 {{ $loop->even ? 'bg-gray-50/80 dark:bg-gray-800/40' : 'bg-white dark:bg-gray-900' }} hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors group">
                                                <div class="flex flex-wrap items-center gap-2">
                                                    @php
                                                        $nameColor = 'text-gray-800 dark:text-gray-200';
                                                        if ($displayName === $match->home_team || str_starts_with($displayName, 'Tài')) {
                                                            $nameColor = 'text-emerald-600 dark:text-emerald-400';
                                                        } elseif ($displayName === $match->away_team || str_starts_with($displayName, 'Xỉu')) {
                                                            $nameColor = 'text-blue-600 dark:text-blue-400';
                                                        }
                                                    @endphp
                                                    <span class="text-sm font-bold {{ $nameColor }}">{{ $displayName }}</span>
                                                    
                                                    @if($displayLine !== null)
                                                        <span class="text-sm font-bold text-gray-600 dark:text-gray-400 bg-gray-100 dark:bg-gray-800 px-2 py-0.5 rounded-md">
                                                            {{ $displayLine }}
                                                        </span>
                                                    @endif
                                                    
                                                    <span class="text-sm font-bold text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-900/20 px-2 py-1 rounded-md whitespace-nowrap">
                                                        ăn {{ number_format($outcome->profit_rate, 2) }}
                                                    </span>
                                                </div>
                                                
                                                <div class="shrink-0 ml-2">
                                                    @if($market->status === 'SETTLED')
                                                        <x-filament::button size="sm" color="info" disabled>
                                                            Quyết toán
                                                        </x-filament::button>
                                                    @elseif($market->status === 'VOIDED')
                                                        <x-filament::button size="sm" color="gray" disabled>
                                                            Đã hủy
                                                        </x-filament::button>
                                                    @elseif($market->status === 'LOCKED')
                                                        <x-filament::button size="sm" color="warning" disabled>
                                                            Đã đóng
                                                        </x-filament::button>
                                                    @else
                                                        <x-filament::button size="sm" wire:click="selectOutcome({{ $outcome->id }})" class="opacity-90 group-hover:opacity-100 transition-opacity">
                                                            Chọn
                                                        </x-filament::button>
                                                    @endif
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </x-filament::card>
            @empty
                <div class="py-16 text-center border border-dashed border-gray-300 dark:border-gray-700 rounded-xl bg-white dark:bg-gray-800/50">
                    <x-filament::icon icon="heroicon-o-face-frown" class="w-12 h-12 mx-auto text-gray-300 dark:text-gray-600 mb-3" />
                    <p class="text-gray-500 font-medium">Chưa có kèo nào được mở cho hiệp đấu này.</p>
                </div>
            @endforelse
        </div>
    </div>

    {{-- Fix lỗi CSS cho svg do CountryFlagHelper render thẻ img/svg không dùng class tailwind --}}
    <style>
        .flex-1 .w-14.h-10 svg, .flex-1 .w-20.h-14 svg, .flex-1 .w-28.h-20 svg {
            width: 100%;
            height: 100%;
            object-fit: cover;
            margin: 0 !important;
        }
        .flex-1 .w-14.h-10 span, .flex-1 .w-20.h-14 span, .flex-1 .w-28.h-20 span {
            display: none;
        }
    </style>

    @livewire(\App\Filament\Player\Livewire\PlaceBetModal::class)
    @livewire(\App\Filament\Player\Livewire\EditBetModal::class)
</x-filament-panels::page>
