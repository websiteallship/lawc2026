@php
    $wallet   = $wallet ?? null;
    $missions = $missions ?? [];
    $matches  = $matches ?? [];
    $userName = auth()->user()?->name ?? 'Bạn'; // resolve 1 lần, tránh gọi lại trong template
@endphp
<div
    x-data="{ show: false }"
    x-init="setTimeout(() => show = true, 800)"
    x-show="show"
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0 translate-y-4"
    x-transition:enter-end="opacity-100 translate-y-0"
    class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-0 bg-gray-900/50 backdrop-blur-sm"
>
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-lg w-full max-h-[90vh] flex flex-col overflow-hidden relative">
        <!-- Header -->
        <div class="p-6 pb-4 bg-gradient-to-r from-primary-600 to-primary-500 text-white">
            <h2 class="text-2xl font-bold flex items-center gap-2">
                <x-filament::icon icon="heroicon-s-sun" class="w-7 h-7 text-yellow-300" />
                Chào ngày mới, {{ $userName }}!
            </h2>
            <p class="text-primary-100 mt-1 text-sm">{{ now()->timezone('Asia/Ho_Chi_Minh')->translatedFormat('l, d F Y') }}</p>
            
            <div class="mt-4 flex gap-4">
                <div class="bg-white/20 rounded-lg p-3 flex-1 backdrop-blur-md">
                    <div class="text-primary-100 text-xs font-medium uppercase tracking-wider">Số dư hiện tại</div>
                    <div class="text-xl font-bold mt-1">{{ number_format($wallet['available_balance'] ?? 0) }} lá</div>
                </div>
            </div>
        </div>

        <!-- Body -->
        <div class="p-6 overflow-y-auto flex-1 space-y-6">
            
            <!-- Nhiệm vụ -->
            <div>
                <h3 class="text-sm font-bold text-gray-900 dark:text-white flex items-center gap-2 mb-3">
                    <x-filament::icon icon="heroicon-o-clipboard-document-check" class="w-5 h-5 text-gray-500 dark:text-gray-400" />
                    Nhiệm vụ hôm nay
                </h3>
                
                @if(empty($missions) || count($missions) === 0)
                    <div class="text-center p-4 rounded-xl bg-gray-50 dark:bg-gray-700/50 border border-dashed border-gray-200 dark:border-gray-600">
                        <p class="text-sm text-gray-500 dark:text-gray-400">Bạn chưa có nhiệm vụ nào. Hãy chờ đợi nhé!</p>
                    </div>
                @else
                    <div class="space-y-3">
                        @foreach($missions as $mission)
                        <div class="p-3 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50 flex flex-col gap-2">
                            <div class="flex justify-between items-center">
                                <div class="text-sm font-bold text-gray-900 dark:text-white">{{ $mission['title'] ?? '' }}</div>
                                @if(($mission['user_mission']['is_completed'] ?? false))
                                    <span class="text-xs px-2 py-1 bg-success-100 text-success-700 rounded-md font-semibold">Hoàn thành</span>
                                @else
                                    <span class="text-xs text-gray-500 font-medium">{{ $mission['user_mission']['current_value'] ?? 0 }}/{{ $mission['target_value'] ?? 1 }}</span>
                                @endif
                            </div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">{{ $mission['description'] ?? '' }}</div>
                            @if(!($mission['user_mission']['is_completed'] ?? false))
                                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-1.5 mt-1">
                                    <div class="bg-primary-500 h-1.5 rounded-full" style="width: {{ max(0, min(100, (($mission['user_mission']['current_value'] ?? 0) / max(1, $mission['target_value'] ?? 1)) * 100)) }}%"></div>
                                </div>
                            @endif
                        </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <!-- Trận sắp đóng -->
            @if(!empty($matches))
            <div>
                <h3 class="text-sm font-bold text-gray-900 dark:text-white flex items-center gap-2 mb-3">
                    <x-filament::icon icon="heroicon-o-fire" class="w-5 h-5 text-danger-500" />
                    Trận sắp đóng kèo
                </h3>
                <div class="space-y-2">
                    @foreach($matches as $market)
                    <a href="/player/match/{{ $market['match_id'] ?? '' }}" class="group block p-3 rounded-xl border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                        <div class="flex justify-between items-center">
                            <div class="text-sm font-bold group-hover:text-primary-600 transition-colors flex items-center gap-2">
                                <span class="text-gray-900 dark:text-white">{{ $market['home_team_name'] ?? 'Home' }}</span>
                                <span class="text-gray-400 text-xs font-normal">vs</span>
                                <span class="text-gray-900 dark:text-white">{{ $market['away_team_name'] ?? 'Away' }}</span>
                                <span class="px-2 py-0.5 rounded text-[10px] bg-primary-100 text-primary-700 ml-2 font-semibold">{{ $market['name'] ?? '' }}</span>
                            </div>
                            @if(\Carbon\Carbon::parse($market['close_at'] ?? now())->diffInMinutes() < 60)
                                <span class="relative flex h-3 w-3">
                                  <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-danger-400 opacity-75"></span>
                                  <span class="relative inline-flex rounded-full h-3 w-3 bg-danger-500"></span>
                                </span>
                            @else
                                <div class="text-xs text-gray-500">{{ \Carbon\Carbon::parse($market['close_at'] ?? now())->diffForHumans() }}</div>
                            @endif
                        </div>
                    </a>
                    @endforeach
                </div>
            </div>
            @endif

        </div>

        <!-- Footer -->
        <div class="p-4 border-t border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 flex justify-end gap-3">
            <button wire:click="dismiss" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-200 dark:hover:bg-gray-700 rounded-lg transition-colors">
                Để sau
            </button>
            <button wire:click="dismissAndRedirect('/player/matches')" class="px-4 py-2 text-sm font-medium text-white bg-primary-600 hover:bg-primary-500 rounded-lg transition-colors shadow-sm">
                Vào chơi ngay
            </button>
        </div>
    </div>
</div>
