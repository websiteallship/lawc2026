<div class="relative overflow-hidden rounded-2xl p-6 bg-gradient-to-r from-success-500/10 to-primary-500/10 dark:from-success-500/20 dark:to-primary-500/20 ring-1 ring-success-500/20 dark:ring-success-500/30 shadow-sm">
    <div class="absolute -right-10 -top-10 w-40 h-40 bg-success-500/10 rounded-full blur-2xl pointer-events-none"></div>
    <div class="relative flex flex-col md:flex-row items-center justify-between gap-6">
        <div class="flex items-center gap-4 w-full md:w-auto">
            <div class="p-3 rounded-xl bg-success-500 text-white shrink-0 shadow-md shadow-success-500/30">
                <x-filament::icon icon="heroicon-s-trophy" class="w-6 h-6" />
            </div>
            <div class="min-w-0 flex-1">
                <div class="flex items-center gap-2 flex-wrap">
                    <span class="text-[10px] font-black uppercase tracking-wider bg-warning-500 text-white px-2 py-0.5 rounded shadow-sm">Tuần Này</span>
                    <span class="text-xs font-bold text-gray-500 dark:text-gray-400 font-mono">{{ $timeRemaining }}</span>
                </div>
                <h3 class="text-base font-extrabold text-gray-900 dark:text-white mt-1">Nhiệm Vụ Tuần ({{ $completedCount }}/{{ $totalCount }})</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 truncate hidden sm:block">Hoàn thành toàn bộ nhiệm vụ tuần để tích lũy điểm ảo và nâng cấp Cấp Bậc Danh Vọng.</p>
            </div>
        </div>

        <div class="flex items-center gap-4 w-full md:w-auto justify-between md:justify-end">
            <!-- Progress bar -->
            <div class="w-full md:w-64 space-y-1">
                <div class="w-full bg-gray-200 dark:bg-gray-800 h-2.5 rounded-full overflow-hidden">
                    <div class="h-full bg-success-500 rounded-full transition-all duration-500" style="width: {{ min(100, round(($completedCount / $totalCount) * 100)) }}%"></div>
                </div>
                <div class="flex justify-between text-[10px] font-bold text-gray-500 dark:text-gray-400">
                    <span>Tiến độ tuần</span>
                    <span>{{ min(100, round(($completedCount / $totalCount) * 100)) }}%</span>
                </div>
            </div>
            <a href="{{ \App\Filament\Player\Pages\MissionsPage::getUrl() }}" class="px-4 py-2 rounded-xl text-xs font-bold text-white bg-success-600 hover:bg-success-500 shadow-sm transition-colors duration-200 shrink-0">
                Chi tiết
            </a>
        </div>
    </div>
</div>
