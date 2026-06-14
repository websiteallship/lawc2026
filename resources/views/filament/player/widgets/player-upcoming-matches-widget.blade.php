<x-filament-widgets::widget>
    <x-filament::card>
        <p class="font-semibold text-gray-700 dark:text-gray-200 mb-3 flex items-center gap-2">
            <x-filament::icon icon="heroicon-m-bolt" class="h-5 w-5 text-amber-400" />
            Market sắp đóng
        </p>
        @forelse($upcomingMarkets as $mkt)
            <div class="flex items-center justify-between py-2 border-b border-gray-100 dark:border-gray-700 last:border-0">
                <div>
                    <p class="font-medium text-sm">
                        {{ $mkt['name'] ?? 'Market #'.$mkt['id'] }}
                    </p>
                    <p class="text-xs text-gray-400">
                        Đóng: {{ \Carbon\Carbon::parse($mkt['close_at'])->setTimezone('Asia/Ho_Chi_Minh')->format('d/m H:i') }}
                    </p>
                </div>
                <x-filament::badge color="success">OPEN</x-filament::badge>
            </div>
        @empty
            <p class="text-gray-400 text-sm">Chưa có trận nào đang mở. Vui lòng quay lại sau.</p>
        @endforelse
    </x-filament::card>
</x-filament-widgets::widget>
