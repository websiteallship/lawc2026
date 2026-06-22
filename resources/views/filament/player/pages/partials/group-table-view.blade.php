{{-- Group Table View: hiển thị trận đấu vòng bảng theo từng bảng --}}
@php
    $stageNames = ['GROUP_STAGE' => 'Vòng bảng', 'ROUND_OF_32' => 'Vòng 32', 'ROUND_OF_16' => 'Vòng 16', 'QUARTER_FINAL' => 'Tứ kết', 'SEMI_FINAL' => 'Bán kết', 'THIRD_PLACE_PLAYOFF' => 'Tranh hạng 3', 'FINAL' => 'Chung kết'];
    $groupColors = ['A' => '#10b981', 'B' => '#3b82f6', 'C' => '#f59e0b', 'D' => '#ef4444', 'E' => '#8b5cf6', 'F' => '#ec4899', 'G' => '#06b6d4', 'H' => '#f97316', 'I' => '#84cc16', 'J' => '#14b8a6', 'K' => '#a855f7', 'L' => '#e11d48'];
@endphp

@if($this->groupStageData->isEmpty())
    <div class="py-16 text-center bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 border-dashed">
        <x-filament::icon icon="heroicon-o-calendar-days" class="w-12 h-12 mx-auto text-gray-300 dark:text-gray-600 mb-4" />
        <p class="text-sm text-gray-500 dark:text-gray-400">Chưa có trận vòng bảng nào.</p>
    </div>
@else
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-5">
        @foreach($this->groupStageData as $groupName => $matches)
            @php
                $letter = strtoupper(last(explode(' ', $groupName)));
                $color = $groupColors[$letter] ?? '#10b981';
            @endphp
            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 overflow-hidden shadow-sm">
                {{-- Group header --}}
                <div class="px-4 py-3 flex items-center gap-3" style="background: linear-gradient(135deg, {{ $color }}22, {{ $color }}11); border-bottom: 2px solid {{ $color }}44;">
                    <div class="w-9 h-9 rounded-xl flex items-center justify-center text-white font-black text-base shadow-sm" style="background: {{ $color }};">
                        {{ $letter }}
                    </div>
                    <div>
                        <h3 class="font-bold text-gray-900 dark:text-white text-sm">{{ $groupName }}</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $matches->count() }} trận</p>
                    </div>
                </div>

                {{-- Match rows --}}
                <div class="divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach($matches as $match)
                        <a href="{{ App\Filament\Player\Pages\MatchDetailPage::getUrl(['record' => $match->id]) }}"
                           class="flex items-center gap-2 px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors group">

                            {{-- Date/Time col --}}
                            <div class="w-[52px] shrink-0 text-center">
                                @if($match->status === 'LIVE')
                                    <span class="inline-flex items-center gap-1 text-[10px] font-black text-red-600 dark:text-red-400">
                                        <span class="w-1.5 h-1.5 rounded-full bg-red-500 animate-ping inline-block"></span>
                                        LIVE
                                    </span>
                                @elseif($match->status === 'FINISHED')
                                    <span class="text-[10px] font-medium text-gray-400 dark:text-gray-500 block">KT</span>
                                @else
                                    <span class="text-[10px] font-semibold text-gray-500 dark:text-gray-400 block">{{ $match->kickoff_at->format('H:i') }}</span>
                                    <span class="text-[9px] text-gray-400 dark:text-gray-500 block">{{ $match->kickoff_at->format('d/m') }}</span>
                                @endif
                            </div>

                            {{-- Home team --}}
                            <div class="flex items-center gap-1.5 flex-1 min-w-0 justify-end">
                                <span class="text-xs font-semibold text-gray-800 dark:text-gray-200 truncate text-right group-hover:text-emerald-600 dark:group-hover:text-emerald-400 transition-colors">{{ $match->home_team }}</span>
                                <div class="w-6 h-5 flex items-center justify-center shrink-0 overflow-hidden rounded-sm">
                                    {!! App\Helpers\CountryFlagHelper::renderFlagOnly($match->home_team) !!}
                                </div>
                            </div>

                            {{-- Score / VS --}}
                            <div class="shrink-0 w-12 text-center">
                                @if(!is_null($match->home_score) && !is_null($match->away_score))
                                    <span class="text-sm font-black {{ $match->status === 'LIVE' ? 'text-red-600 dark:text-red-400' : 'text-emerald-600 dark:text-emerald-400' }} tabular-nums">
                                        {{ $match->home_score }}-{{ $match->away_score }}
                                    </span>
                                @else
                                    <span class="text-[10px] font-bold text-gray-400 bg-gray-100 dark:bg-gray-700 px-1.5 py-0.5 rounded">VS</span>
                                @endif
                            </div>

                            {{-- Away team --}}
                            <div class="flex items-center gap-1.5 flex-1 min-w-0 justify-start">
                                <div class="w-6 h-5 flex items-center justify-center shrink-0 overflow-hidden rounded-sm">
                                    {!! App\Helpers\CountryFlagHelper::renderFlagOnly($match->away_team) !!}
                                </div>
                                <span class="text-xs font-semibold text-gray-800 dark:text-gray-200 truncate group-hover:text-emerald-600 dark:group-hover:text-emerald-400 transition-colors">{{ $match->away_team }}</span>
                            </div>

                            {{-- Market badge --}}
                            <div class="shrink-0">
                                @if($match->markets_count > 0)
                                    <span class="text-[9px] font-bold px-1.5 py-0.5 rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-400">{{ $match->markets_count }}kèo</span>
                                @endif
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
@endif

