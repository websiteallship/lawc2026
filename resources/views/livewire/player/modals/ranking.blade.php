@php $messageKey = $messageKey ?? 'new'; $userRank = $userRank ?? null; $gapToAbove = $gapToAbove ?? null; $top3 = $top3 ?? []; $rankings = $rankings ?? []; @endphp
<div
    x-data="{
        show: false,
        messageKey: '{{ $messageKey }}',
        init() {
            setTimeout(() => {
                this.show = true;
                // Fire confetti sau khi transition 300ms xong
                if (['top1','top2','top3'].includes(this.messageKey) && typeof window.fireConfetti === 'function') {
                    setTimeout(() => window.fireConfetti('ranking_' + this.messageKey), 350);
                }
            }, 100);
        }
    }"
    x-show="show"
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0 scale-95"
    x-transition:enter-end="opacity-100 scale-100"
    class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-0 bg-gray-900/50 backdrop-blur-sm"
>
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-md w-full overflow-hidden relative border-t-4 
        @if($messageKey === 'top1') border-yellow-400
        @elseif($messageKey === 'top2') border-gray-300
        @elseif($messageKey === 'top3') border-amber-600
        @else border-primary-500 @endif
    ">
        
        <!-- Header -->
        <div class="p-6 pb-2 text-center">
            @if($messageKey === 'top1')
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-yellow-100 dark:bg-yellow-900/30 mb-4 animate-bounce">
                    <x-filament::icon icon="heroicon-s-trophy" class="w-8 h-8 text-yellow-500" />
                </div>
                <h2 class="text-2xl font-black text-gray-900 dark:text-white">Độc Cô Cầu Bại! 👑</h2>
                <p class="text-gray-500 dark:text-gray-400 mt-1">Vị trí đỉnh bảng là của bạn. Hãy bảo vệ ngai vàng!</p>
            @elseif($messageKey === 'top2')
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gray-200 dark:bg-gray-700/50 mb-4">
                    <x-filament::icon icon="heroicon-s-trophy" class="w-8 h-8 text-gray-400" />
                </div>
                <h2 class="text-2xl font-black text-gray-900 dark:text-white">Sát Nút Ngôi Vương! 🥈</h2>
                <p class="text-gray-500 dark:text-gray-400 mt-1">Chỉ còn một cú sút nữa thôi. Đừng chùn bước!</p>
            @elseif($messageKey === 'top3')
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-amber-100 dark:bg-amber-900/30 mb-4">
                    <x-filament::icon icon="heroicon-s-trophy" class="w-8 h-8 text-amber-600" />
                </div>
                <h2 class="text-2xl font-black text-gray-900 dark:text-white">Top 3 Huyền Thoại! 🥉</h2>
                <p class="text-gray-500 dark:text-gray-400 mt-1">Vững vàng trên đỉnh vinh quang. Tiến lên nào!</p>
            @elseif(in_array($messageKey, ['chasing', 'climbing']))
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-primary-100 dark:bg-primary-900/30 mb-4">
                    <x-filament::icon icon="heroicon-s-arrow-trending-up" class="w-8 h-8 text-primary-500" />
                </div>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Phong Độ Tốt!</h2>
                <p class="text-gray-500 dark:text-gray-400 mt-1">Bạn đang bám đuổi sát nút trên bảng xếp hạng.</p>
            @else
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-blue-100 dark:bg-blue-900/30 mb-4">
                    <x-filament::icon icon="heroicon-s-academic-cap" class="w-8 h-8 text-blue-500" />
                </div>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Khởi Đầu Tốt!</h2>
                <p class="text-gray-500 dark:text-gray-400 mt-1">Hành trình ngàn dặm bắt đầu từ bước chân đầu tiên.</p>
            @endif
        </div>

        <!-- Body -->
        <div class="p-6 pt-4 space-y-6">
            
            <div class="bg-gray-50 dark:bg-gray-700/30 rounded-xl p-4 border border-gray-100 dark:border-gray-700 flex justify-between items-center">
                <div>
                    <div class="text-xs text-gray-500 uppercase font-semibold tracking-wider">Hạng hiện tại</div>
                    <div class="text-3xl font-black mt-1 text-gray-900 dark:text-white">
                        {{ $userRank ? '#' . $userRank : '--' }}
                    </div>
                </div>
                
                @if($gapToAbove > 0)
                <div class="text-right">
                    <div class="text-xs text-gray-500 uppercase font-semibold tracking-wider">Trạng thái</div>
                    <div class="text-lg font-bold text-primary-500 mt-1">Còn bám đuổi</div>
                </div>
                @endif
            </div>

            @if(count($top3) > 0)
            <div>
                <h3 class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-3">Top 3 Hiện Tại</h3>
                <div class="space-y-2">
                    @foreach($top3 as $idx => $leader)
                    <div class="flex items-center justify-between p-2 rounded-lg {{ isset($leader['user_id']) && $leader['user_id'] === auth()->id() ? 'bg-primary-50 dark:bg-primary-900/20 border border-primary-200 dark:border-primary-800' : '' }}">
                        <div class="flex items-center gap-3">
                            <span class="font-bold text-gray-400 w-4">{{ $idx + 1 }}</span>
                            <div class="font-medium text-sm text-gray-900 dark:text-gray-100">{{ $leader['name'] ?? 'User' }}</div>
                        </div>
                        <div class="font-bold text-sm {{ ($leader['net_profit'] ?? 0) > 0 ? 'text-success-600 dark:text-success-400' : (($leader['net_profit'] ?? 0) < 0 ? 'text-danger-500' : 'text-gray-400') }}">
                            @if(($leader['net_profit'] ?? 0) > 0)
                                <x-filament::icon icon="heroicon-m-arrow-trending-up" class="w-5 h-5 inline" />
                            @elseif(($leader['net_profit'] ?? 0) < 0)
                                <x-filament::icon icon="heroicon-m-arrow-trending-down" class="w-5 h-5 inline" />
                            @else
                                <x-filament::icon icon="heroicon-m-minus" class="w-5 h-5 inline" />
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>

        <!-- Footer -->
        <div class="p-4 border-t border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 flex justify-between gap-3">
            <button wire:click="dismiss" class="flex-1 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 border border-gray-200 dark:border-gray-600 rounded-lg transition-colors text-center shadow-sm">
                Đóng
            </button>
            <button wire:click="dismissAndRedirect('/player/leaderboard-page')" class="flex-1 px-4 py-2 text-sm font-medium text-white bg-primary-600 hover:bg-primary-500 rounded-lg transition-colors text-center shadow-sm">
                Xem BXH
            </button>
        </div>
    </div>
</div>
