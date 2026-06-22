{{-- Knockout Bracket View --}}
@php
    $stageConfig = [
        'ROUND_OF_32'         => ['label' => 'Vòng 32',       'short' => 'V32',  'color' => '#6366f1'],
        'ROUND_OF_16'         => ['label' => 'Vòng 16',       'short' => 'V16',  'color' => '#8b5cf6'],
        'QUARTER_FINAL'       => ['label' => 'Tứ kết',        'short' => 'TK',   'color' => '#f59e0b'],
        'SEMI_FINAL'          => ['label' => 'Bán kết',       'short' => 'BK',   'color' => '#f97316'],
        'THIRD_PLACE_PLAYOFF' => ['label' => 'Tranh hạng 3',  'short' => 'H3',   'color' => '#64748b'],
        'FINAL'               => ['label' => 'Chung kết',     'short' => 'CK',   'color' => '#eab308'],
    ];
    $stageOrder = ['ROUND_OF_32', 'ROUND_OF_16', 'QUARTER_FINAL', 'SEMI_FINAL', 'THIRD_PLACE_PLAYOFF', 'FINAL'];
    $knockoutData = $this->knockoutData;
@endphp

@if($knockoutData->isEmpty())
    <div class="py-16 text-center bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 border-dashed">
        <x-filament::icon icon="heroicon-o-trophy" class="w-12 h-12 mx-auto text-gray-300 dark:text-gray-600 mb-4" />
        <p class="text-sm text-gray-500 dark:text-gray-400">Chưa có trận đấu vòng loại trực tiếp.</p>
    </div>
@else

    {{-- ============================================================
         DESKTOP: Horizontal Bracket Tree (hidden on mobile)
         ============================================================ --}}
    <div class="hidden md:block overflow-x-auto pb-4 cursor-grab active:cursor-grabbing select-none"
         x-data="{ isDown: false, startX: 0, scrollLeft: 0 }"
         x-on:mousedown="isDown = true; startX = $event.pageX - $el.offsetLeft; scrollLeft = $el.scrollLeft"
         x-on:mouseleave="isDown = false"
         x-on:mouseup="isDown = false"
         x-on:mousemove="if(!isDown) return; $event.preventDefault(); const x = $event.pageX - $el.offsetLeft; const walk = (x - startX) * 2; $el.scrollLeft = scrollLeft - walk;"
    >
        <div class="bracket-tree flex gap-0 min-w-max">
            @foreach($stageOrder as $stage)
                @if(!$knockoutData->has($stage)) @continue @endif
                @php
                    $cfg = $stageConfig[$stage];
                    $stageMatches = $knockoutData[$stage];
                    $hasNext = !in_array($stage, ['FINAL', 'THIRD_PLACE_PLAYOFF']);
                    $hasPrev = !in_array($stage, ['ROUND_OF_32', 'THIRD_PLACE_PLAYOFF']);
                @endphp

                <div class="bracket-column flex flex-col w-[280px] shrink-0 relative {{ $hasNext ? 'has-next-round' : '' }} {{ $hasPrev ? 'has-prev-round' : '' }}">
                    {{-- Column header --}}
                    <div class="bracket-col-header text-center mb-1 px-4">
                        <span class="inline-block px-3 py-1 rounded-full text-xs font-black text-white shadow-sm" style="background: {{ $cfg['color'] }};">
                            {{ $cfg['label'] }}
                        </span>
                    </div>

                    {{-- Matches spaced to align vertically --}}
                    <div class="bracket-col-matches flex flex-col flex-1">
                        @foreach($stageMatches as $match)
                            <div class="bracket-node relative flex-1 flex flex-col justify-center px-4 py-2.5">
                                <a href="{{ App\Filament\Player\Pages\MatchDetailPage::getUrl(['record' => $match->id]) }}"
                                   class="block bg-white dark:bg-gray-800 border rounded-xl overflow-hidden shadow-sm hover:shadow-md transition-all hover:border-emerald-400 group relative z-10"
                                   style="border-color: {{ $match->status === 'LIVE' ? '#ef4444' : ($match->status === 'FINISHED' ? '#d1d5db' : $cfg['color'].'44') }};">

                                    {{-- Match date --}}
                                    <div class="px-2 py-1 text-center border-b border-gray-100 dark:border-gray-700" style="background: {{ $cfg['color'] }}11;">
                                        <span class="text-[9px] font-semibold text-gray-500 dark:text-gray-400">
                                            {{ $match->kickoff_at->format('H:i d/m/Y') }}
                                        </span>
                                        @if($match->status === 'LIVE')
                                            <span class="ml-1 text-[9px] font-black text-red-600 animate-pulse">LIVE</span>
                                        @endif
                                        @if($match->markets_count > 0)
                                            <span class="ml-1 text-[9px] font-bold text-emerald-600 dark:text-emerald-400">{{ $match->markets_count }}kèo</span>
                                        @endif
                                    </div>

                                    {{-- Home team --}}
                                    <div class="flex items-center gap-2 px-2 py-1.5 {{ !is_null($match->home_score) && $match->home_score > $match->away_score ? 'bg-emerald-50 dark:bg-emerald-900/20' : '' }}">
                                        <div class="w-5 h-4 shrink-0 overflow-hidden rounded-sm">
                                            {!! App\Helpers\CountryFlagHelper::renderFlagOnly($match->home_team) !!}
                                        </div>
                                        <span class="text-xs font-semibold text-gray-800 dark:text-gray-200 flex-1 truncate group-hover:text-emerald-600 dark:group-hover:text-emerald-400 transition-colors">
                                            {{ $match->home_team }}
                                        </span>
                                        @if(!is_null($match->home_score))
                                            <span class="text-sm font-black tabular-nums {{ $match->status === 'LIVE' ? 'text-red-600' : 'text-gray-800 dark:text-white' }}">{{ $match->home_score }}</span>
                                        @endif
                                    </div>

                                    {{-- Divider --}}
                                    <div class="h-px bg-gray-100 dark:bg-gray-700 mx-2"></div>

                                    {{-- Away team --}}
                                    <div class="flex items-center gap-2 px-2 py-1.5 {{ !is_null($match->away_score) && $match->away_score > $match->home_score ? 'bg-emerald-50 dark:bg-emerald-900/20' : '' }}">
                                        <div class="w-5 h-4 shrink-0 overflow-hidden rounded-sm">
                                            {!! App\Helpers\CountryFlagHelper::renderFlagOnly($match->away_team) !!}
                                        </div>
                                        <span class="text-xs font-semibold text-gray-800 dark:text-gray-200 flex-1 truncate group-hover:text-emerald-600 dark:group-hover:text-emerald-400 transition-colors">
                                            {{ $match->away_team }}
                                        </span>
                                        @if(!is_null($match->away_score))
                                            <span class="text-sm font-black tabular-nums {{ $match->status === 'LIVE' ? 'text-red-600' : 'text-gray-800 dark:text-white' }}">{{ $match->away_score }}</span>
                                        @endif
                                    </div>
                                </a>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- ============================================================
         MOBILE: Vertical Timeline (visible only on mobile)
         ============================================================ --}}
    <div class="md:hidden space-y-6">
        @foreach($stageOrder as $stage)
            @if(!$knockoutData->has($stage)) @continue @endif
            @php
                $cfg = $stageConfig[$stage];
                $stageMatches = $knockoutData[$stage];
            @endphp

            {{-- Stage header --}}
            <div class="relative">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-8 h-8 rounded-xl flex items-center justify-center text-white font-black text-xs shadow-sm shrink-0" style="background: {{ $cfg['color'] }};">
                        {{ $cfg['short'] }}
                    </div>
                    <h3 class="font-bold text-base text-gray-900 dark:text-white">{{ $cfg['label'] }}</h3>
                    <div class="flex-1 h-px bg-gray-200 dark:bg-gray-700"></div>
                    <span class="text-xs text-gray-500 dark:text-gray-400">{{ $stageMatches->count() }} trận</span>
                </div>

                {{-- Matches vertical list --}}
                <div class="space-y-3 pl-4 border-l-2 ml-4" style="border-color: {{ $cfg['color'] }}44;">
                    @foreach($stageMatches as $match)
                        <a href="{{ App\Filament\Player\Pages\MatchDetailPage::getUrl(['record' => $match->id]) }}"
                           class="block bg-white dark:bg-gray-800 rounded-xl border shadow-sm hover:shadow-md transition-all hover:border-emerald-400 overflow-hidden group relative"
                           style="border-color: {{ $match->status === 'LIVE' ? '#ef4444' : '#e5e7eb' }};">

                            {{-- Timeline dot --}}
                            <div class="absolute -left-[21px] top-1/2 -translate-y-1/2 w-3 h-3 rounded-full border-2 border-white dark:border-gray-900 shadow-sm" style="background: {{ $cfg['color'] }};"></div>

                            {{-- Header row: date + status --}}
                            <div class="flex items-center justify-between px-3 py-1.5 border-b border-gray-100 dark:border-gray-700" style="background: {{ $cfg['color'] }}0d;">
                                <span class="text-[10px] font-semibold text-gray-500 dark:text-gray-400">
                                    {{ $match->kickoff_at->format('H:i — d/m/Y') }}
                                </span>
                                <div class="flex items-center gap-1">
                                    @if($match->status === 'LIVE')
                                        <span class="flex items-center gap-1 text-[9px] font-black text-red-600">
                                            <span class="w-1.5 h-1.5 rounded-full bg-red-500 animate-ping"></span>LIVE
                                        </span>
                                    @elseif($match->status === 'FINISHED')
                                        <span class="text-[9px] font-medium text-gray-400">Kết thúc</span>
                                    @elseif($match->markets_count > 0)
                                        <span class="text-[9px] font-bold text-emerald-600 dark:text-emerald-400">{{ $match->markets_count }} kèo mở</span>
                                    @endif
                                </div>
                            </div>

                            {{-- Teams row --}}
                            <div class="flex items-center gap-3 px-3 py-2.5">
                                {{-- Home --}}
                                <div class="flex items-center gap-1.5 flex-1 min-w-0 justify-end">
                                    <span class="text-sm font-bold text-gray-800 dark:text-white truncate text-right group-hover:text-emerald-600 dark:group-hover:text-emerald-400 transition-colors">{{ $match->home_team }}</span>
                                    <div class="w-6 h-5 shrink-0 overflow-hidden rounded-sm">
                                        {!! App\Helpers\CountryFlagHelper::renderFlagOnly($match->home_team) !!}
                                    </div>
                                </div>

                                {{-- Score --}}
                                <div class="shrink-0 text-center min-w-[44px]">
                                    @if(!is_null($match->home_score) && !is_null($match->away_score))
                                        <span class="text-lg font-black tabular-nums {{ $match->status === 'LIVE' ? 'text-red-600 dark:text-red-400' : 'text-emerald-600 dark:text-emerald-400' }}">
                                            {{ $match->home_score }}-{{ $match->away_score }}
                                        </span>
                                    @else
                                        <span class="text-xs font-bold text-gray-400 bg-gray-100 dark:bg-gray-700 px-2 py-0.5 rounded">VS</span>
                                    @endif
                                </div>

                                {{-- Away --}}
                                <div class="flex items-center gap-1.5 flex-1 min-w-0 justify-start">
                                    <div class="w-6 h-5 shrink-0 overflow-hidden rounded-sm">
                                        {!! App\Helpers\CountryFlagHelper::renderFlagOnly($match->away_team) !!}
                                    </div>
                                    <span class="text-sm font-bold text-gray-800 dark:text-white truncate group-hover:text-emerald-600 dark:group-hover:text-emerald-400 transition-colors">{{ $match->away_team }}</span>
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
@endif

<style>
    .bracket-tree { align-items: stretch; }
    
    /* Bracket connecting lines */
    .bracket-column.has-next-round .bracket-node:nth-child(odd)::after {
        content: '';
        position: absolute;
        right: 0;
        top: 50%;
        bottom: 0;
        width: 16px;
        border-top: 2px solid #cbd5e1;
        border-right: 2px solid #cbd5e1;
        border-top-right-radius: 6px;
    }
    .bracket-column.has-next-round .bracket-node:nth-child(even)::after {
        content: '';
        position: absolute;
        right: 0;
        top: 0;
        height: 50%;
        width: 16px;
        border-bottom: 2px solid #cbd5e1;
        border-right: 2px solid #cbd5e1;
        border-bottom-right-radius: 6px;
    }

    .bracket-column.has-prev-round .bracket-node::before {
        content: '';
        position: absolute;
        left: 0;
        top: 50%;
        width: 16px;
        border-top: 2px solid #cbd5e1;
    }
    
    /* Dark mode for lines */
    .dark .bracket-column.has-next-round .bracket-node:nth-child(odd)::after,
    .dark .bracket-column.has-next-round .bracket-node:nth-child(even)::after,
    .dark .bracket-column.has-prev-round .bracket-node::before {
        border-color: #475569;
    }
</style>
