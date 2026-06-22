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
    <div class="space-y-5">
        @livewire('player.weekly-missions-widget')

        {{-- ============================================================
             View Mode Toggle
             ============================================================ --}}
        <div class="flex items-center justify-between flex-wrap gap-3">
            <div x-data="{ highlight: false, timer: null }"
                 x-on:feature-popup-closed.window="
                    highlight = true; 
                    clearTimeout(timer); 
                    timer = setTimeout(() => { 
                        if (highlight) {
                            highlight = false; 
                            $dispatch('feature-highlight-ended'); 
                        }
                    }, 5000);
                 "
                 x-on:click="
                    if (highlight) { 
                        highlight = false; 
                        $dispatch('feature-highlight-ended'); 
                    }
                 "
                 :class="highlight ? 'ring-4 ring-indigo-500 dark:ring-indigo-400 ring-offset-2 dark:ring-offset-gray-900 scale-105 shadow-2xl z-50 bg-white dark:bg-gray-800' : 'relative z-10'"
                 class="flex items-center gap-1.5 bg-gray-100 dark:bg-gray-800 p-1 rounded-xl border border-gray-200 dark:border-gray-700 transition-all duration-500"
            >
                {{-- List view --}}
                <button wire:click="$set('viewMode', 'list')"
                        @class([
                            'flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-sm font-semibold transition-all duration-200',
                            'bg-white dark:bg-gray-700 text-gray-900 dark:text-white shadow-sm' => $viewMode === 'list',
                            'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200' => $viewMode !== 'list',
                        ])
                        title="Danh sách">
                    <x-filament::icon icon="heroicon-o-list-bullet" class="w-4 h-4" />
                    <span class="hidden sm:inline">Danh sách</span>
                </button>
                {{-- Groups view --}}
                <button wire:click="$set('viewMode', 'groups')"
                        @class([
                            'flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-sm font-semibold transition-all duration-200',
                            'bg-white dark:bg-gray-700 text-gray-900 dark:text-white shadow-sm' => $viewMode === 'groups',
                            'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200' => $viewMode !== 'groups',
                        ])
                        title="Vòng bảng">
                    <x-filament::icon icon="heroicon-o-table-cells" class="w-4 h-4" />
                    <span class="hidden sm:inline">Vòng bảng</span>
                </button>
                {{-- Bracket view --}}
                <button wire:click="$set('viewMode', 'bracket')"
                        @class([
                            'flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-sm font-semibold transition-all duration-200',
                            'bg-white dark:bg-gray-700 text-gray-900 dark:text-white shadow-sm' => $viewMode === 'bracket',
                            'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200' => $viewMode !== 'bracket',
                        ])
                        title="Sơ đồ knockout">
                    <x-filament::icon icon="heroicon-o-trophy" class="w-4 h-4" />
                    <span class="hidden sm:inline">Sơ đồ knockout</span>
                </button>
            </div>

            {{-- Context hint --}}
            <p class="text-xs text-gray-400 dark:text-gray-500">
                @if($viewMode === 'list') Lọc theo trạng thái / vòng đấu
                @elseif($viewMode === 'groups') Tất cả trận vòng bảng theo bảng
                @else Cây nhánh loại trực tiếp
                @endif
            </p>
        </div>

        {{-- ============================================================
             LIST VIEW (default): filters + card grid
             ============================================================ --}}
        @if($viewMode === 'list')
            {{-- Bộ lọc Trạng thái --}}
            <div class="flex gap-2 overflow-x-auto pb-2 scrollbar-hide">
                @foreach([
                    'all' => 'Tất cả',
                    'live' => 'Đang diễn ra',
                    'open' => 'Đang mở',
                    'upcoming' => 'Sắp diễn ra',
                    'finished' => 'Đã có kết quả'
                ] as $key => $label)
                    <button wire:click="$set('activeTab', '{{ $key }}')"
                        @class([
                            'px-4 py-2 rounded-full text-sm font-medium whitespace-nowrap transition-colors',
                            'bg-emerald-600 text-white shadow-sm' => $activeTab === $key,
                            'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-300 border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700' => $activeTab !== $key,
                        ])>
                        {{ $label }}
                    </button>
                @endforeach
            </div>

            {{-- Bộ lọc Vòng đấu --}}
            <div class="flex gap-2 overflow-x-auto pb-2 scrollbar-hide">
                @foreach([
                    'all' => 'Tất cả vòng',
                    'GROUP_STAGE' => 'Vòng bảng',
                    'ROUND_OF_32' => 'Vòng 32',
                    'ROUND_OF_16' => 'Vòng 16',
                    'QUARTER_FINAL' => 'Tứ kết',
                    'SEMI_FINAL' => 'Bán kết',
                    'THIRD_PLACE_PLAYOFF' => 'Tranh hạng 3',
                    'FINAL' => 'Chung kết'
                ] as $key => $label)
                    <button wire:click="$set('activeStage', '{{ $key }}')"
                        @class([
                            'px-3 py-1.5 rounded text-xs font-medium whitespace-nowrap transition-colors',
                            'bg-indigo-100 dark:bg-indigo-900/50 text-indigo-700 dark:text-indigo-300 ring-1 ring-indigo-600/20' => $activeStage === $key,
                            'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700' => $activeStage !== $key,
                        ])>
                        {{ $label }}
                    </button>
                @endforeach
            </div>

            {{-- Danh sách trận đấu (card grid) --}}
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                @forelse($this->matches as $match)
                    <a href="{{ App\Filament\Player\Pages\MatchDetailPage::getUrl(['record' => $match->id]) }}"
                       class="block group bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 hover:border-emerald-500 hover:shadow-md transition-all overflow-hidden relative">

                        {{-- Header card --}}
                        <div class="bg-gray-50 dark:bg-gray-800/50 px-4 py-3 border-b border-gray-100 dark:border-gray-700 flex justify-between items-center">
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-bold text-gray-500 dark:text-gray-400 tracking-wider uppercase bg-gray-100 dark:bg-gray-700/60 px-2 py-0.5 rounded">{{ $stageNames[$match->stage] ?? $match->stage }}</span>
                                @if($match->group)
                                    <span class="px-2 py-0.5 rounded text-[10px] font-extrabold bg-indigo-50 dark:bg-indigo-950/50 text-indigo-600 dark:text-indigo-400 border border-indigo-200 dark:border-indigo-800/80 shadow-sm">{{ $match->group }}</span>
                                @endif
                            </div>

                            {{-- Status Badge --}}
                            @if($match->status === 'FINISHED')
                                <span class="inline-flex items-center gap-1 px-2 py-1 rounded-md text-xs font-medium bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                                    Đã kết thúc
                                </span>
                            @elseif($match->status === 'LIVE')
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-black tracking-wider shadow-sm" style="background-color: #dc2626 !important; color: #ffffff !important; box-shadow: 0 2px 4px 0 rgba(220, 38, 38, 0.5) !important; border: 1px solid #f87171 !important;">
                                    <span class="relative flex h-2.5 w-2.5">
                                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full opacity-75" style="background-color: #ffffff !important;"></span>
                                        <span class="relative inline-flex rounded-full h-2.5 w-2.5" style="background-color: #ffffff !important;"></span>
                                    </span>
                                    @if(in_array($match->detailed_status, ['HT', 'BT', 'P', 'SUSP', 'INT']))
                                        {{ match($match->detailed_status) {
                                            'HT' => 'HT',
                                            'BT' => 'BT',
                                            'P' => 'PEN',
                                            'SUSP' => 'PAUSE',
                                            'INT' => 'PAUSE',
                                            default => $match->detailed_status
                                        } }}
                                    @else
                                        LIVE {{ $match->elapsed_minutes ? $match->elapsed_minutes . "'" : '' }}
                                    @endif
                                </span>
                            @elseif($match->markets_count > 0)
                                <span class="inline-flex items-center gap-1.5 px-2 py-1 rounded-md text-xs font-medium bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                    {{ $match->markets_count }} kèo mở
                                </span>
                            @elseif($match->kickoff_at > now())
                                <span class="inline-flex items-center gap-1 px-2 py-1 rounded-md text-xs font-medium bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400">
                                    Sắp diễn ra
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 px-2 py-1 rounded-md text-xs font-medium bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400">
                                    Đã khóa
                                </span>
                            @endif
                        </div>

                        {{-- Body card --}}
                        <div class="p-5">
                            <div class="flex justify-between items-center mb-4">
                                {{-- Home Team --}}
                                <div class="flex flex-col items-center gap-3 flex-1">
                                    <div class="shadow-sm rounded-md overflow-hidden bg-gray-100 dark:bg-gray-700 flex items-center justify-center w-16 h-12">
                                        {!! App\Helpers\CountryFlagHelper::renderFlagOnly($match->home_team) !!}
                                    </div>
                                    <span class="text-sm font-bold text-center text-gray-800 dark:text-white leading-tight line-clamp-2 min-h-[2.5rem] flex items-center">{{ $match->home_team }}</span>
                                </div>

                                {{-- VS & Time --}}
                                <div class="px-4 flex flex-col items-center justify-center shrink-0 min-w-[80px]">
                                    @if(!is_null($match->home_score) && !is_null($match->away_score))
                                        <span class="text-2xl sm:text-3xl font-black text-emerald-600 dark:text-emerald-400 mb-1 tracking-tighter">
                                            {{ $match->home_score }} - {{ $match->away_score }}
                                        </span>
                                    @else
                                        <span class="text-[10px] text-gray-400 font-bold mb-2 bg-gray-100 dark:bg-gray-700 px-2 py-0.5 rounded-full">VS</span>
                                    @endif

                                    @if($match->status !== 'FINISHED' && $match->status !== 'LIVE' && (is_null($match->home_score) || is_null($match->away_score)))
                                        <span class="text-base font-bold text-gray-900 dark:text-white font-mono bg-gray-50 dark:bg-gray-900/50 px-2 py-1 rounded">{{ $match->kickoff_at->format('H:i') }}</span>
                                    @endif
                                </div>

                                {{-- Away Team --}}
                                <div class="flex flex-col items-center gap-3 flex-1">
                                    <div class="shadow-sm rounded-md overflow-hidden bg-gray-100 dark:bg-gray-700 flex items-center justify-center w-16 h-12">
                                        {!! App\Helpers\CountryFlagHelper::renderFlagOnly($match->away_team) !!}
                                    </div>
                                    <span class="text-sm font-bold text-center text-gray-800 dark:text-white leading-tight line-clamp-2 min-h-[2.5rem] flex items-center">{{ $match->away_team }}</span>
                                </div>
                            </div>

                            {{-- Footer/Countdown --}}
                            <div class="mt-5 pt-4 border-t border-gray-100 dark:border-gray-700 flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                                <div class="flex items-center gap-1.5 font-medium">
                                    <x-filament::icon icon="heroicon-o-calendar" class="w-4 h-4" />
                                    {{ $match->kickoff_at->format('H:i d/m/Y') }}
                                </div>

                                @if($match->next_close_at && $match->next_close_at > now())
                                    <div class="flex items-center gap-1.5 text-emerald-600 dark:text-emerald-400 font-semibold bg-emerald-50 dark:bg-emerald-900/20 px-2 py-1 rounded-md">
                                        <x-filament::icon icon="heroicon-o-clock" class="w-4 h-4" />
                                        Đóng sau: {{ \Carbon\Carbon::parse($match->next_close_at)->locale('vi')->diffForHumans(['parts' => 2, 'short' => true, 'syntax' => \Carbon\CarbonInterface::DIFF_ABSOLUTE]) }}
                                    </div>
                                @elseif($match->kickoff_at > now())
                                    <div class="flex items-center gap-1.5 text-blue-600 dark:text-blue-400 font-semibold">
                                        <x-filament::icon icon="heroicon-o-clock" class="w-4 h-4" />
                                        Đóng sau: {{ $match->kickoff_at->locale('vi')->diffForHumans(['parts' => 2, 'short' => true, 'syntax' => \Carbon\CarbonInterface::DIFF_ABSOLUTE]) }}
                                    </div>
                                @endif
                            </div>
                        </div>
                    </a>
                @empty
                    <div class="col-span-full py-16 text-center bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 border-dashed">
                        <x-filament::icon icon="heroicon-o-calendar-days" class="w-12 h-12 mx-auto text-gray-300 dark:text-gray-600 mb-4" />
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">Không tìm thấy trận đấu nào</h3>
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Thử thay đổi bộ lọc trạng thái hoặc vòng đấu để xem thêm các trận khác.</p>
                    </div>
                @endforelse
            </div>
        @endif

        {{-- ============================================================
             GROUPS VIEW
             ============================================================ --}}
        @if($viewMode === 'groups')
            @include('filament.player.pages.partials.group-table-view')
        @endif

        {{-- ============================================================
             BRACKET VIEW
             ============================================================ --}}
        @if($viewMode === 'bracket')
            @include('filament.player.pages.partials.bracket-view')
        @endif
    </div>

    {{-- Feature Announcement Popup --}}
    @if($this->showFeaturePopup)
        <div x-data="{ open: true, countdown: 5 }" 
             x-show="open" 
             x-init="
                const interval = setInterval(() => {
                    if(countdown > 0) countdown--;
                    else clearInterval(interval);
                }, 1000);
             "
             class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-sm"
             style="display: none;"
        >
            <div @click.outside="if(countdown === 0) { open = false; $wire.set('showFeaturePopup', false); $dispatch('feature-popup-closed'); }" 
                 class="bg-white dark:bg-gray-800 rounded-3xl shadow-2xl max-w-lg w-full p-8 text-center transform transition-all border border-gray-100 dark:border-gray-700">
                
                <div class="w-16 h-16 mx-auto bg-emerald-100 dark:bg-emerald-900/30 rounded-2xl flex items-center justify-center mb-6">
                    <x-filament::icon icon="heroicon-o-trophy" class="w-8 h-8 text-emerald-600 dark:text-emerald-400" />
                </div>

                <h2 class="text-2xl font-black text-gray-900 dark:text-white mb-3 tracking-tight">Tính năng mới ra mắt!</h2>
                <p class="text-gray-600 dark:text-gray-400 mb-8 leading-relaxed">
                    Đã cập nhật chế độ xem <strong class="text-gray-900 dark:text-gray-200">Vòng bảng</strong> và <strong class="text-indigo-600 dark:text-indigo-400">Sơ đồ loại trực tiếp</strong> (Knockout).<br>
                    Bạn có thể dễ dàng theo dõi hành trình của các đội bóng thông qua thanh chuyển đổi ngay phía trên màn hình.
                </p>
                
                <div class="flex items-center justify-center gap-3 mb-8 bg-gray-50 dark:bg-gray-900/50 p-4 rounded-2xl border border-gray-100 dark:border-gray-800">
                    <span class="px-3 py-1.5 bg-white dark:bg-gray-800 rounded-lg shadow-sm font-bold text-xs text-gray-500 border border-gray-200 dark:border-gray-700">Danh sách</span>
                    <x-filament::icon icon="heroicon-m-chevron-right" class="w-4 h-4 text-gray-400" />
                    <span class="px-3 py-1.5 bg-indigo-600 text-white rounded-lg shadow-md font-bold text-xs">Sơ đồ knockout</span>
                </div>

                <button x-on:click="if(countdown === 0) { open = false; $wire.set('showFeaturePopup', false); $dispatch('feature-popup-closed'); }" 
                        x-bind:disabled="countdown > 0"
                        x-bind:class="countdown > 0 ? 'bg-gray-200 text-gray-400 cursor-not-allowed dark:bg-gray-700 dark:text-gray-500' : 'bg-emerald-600 hover:bg-emerald-700 text-white shadow-lg shadow-emerald-600/20 active:scale-95'"
                        class="w-full py-3.5 rounded-xl font-bold transition-all flex justify-center items-center gap-2">
                    <span x-show="countdown > 0">Đóng thông báo sau <span x-text="countdown"></span>s</span>
                    <span x-show="countdown === 0">Đã hiểu & Trải nghiệm ngay</span>
                </button>
            </div>
        </div>
    @endif

    {{-- Spotlight Backdrop Overlay --}}
    <div x-data="{ show: false }"
         x-show="show"
         x-on:feature-popup-closed.window="show = true"
         x-on:feature-highlight-ended.window="show = false"
         x-transition:enter="transition ease-out duration-500"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-500"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 bg-gray-950/70 backdrop-blur-sm z-40"
         style="display: none;"
    ></div>

</x-filament-panels::page>
