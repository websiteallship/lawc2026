<x-filament-widgets::widget>
    <x-filament::card>
        <p class="font-semibold text-gray-700 dark:text-gray-200 mb-3 flex items-center gap-2">
            <x-filament::icon icon="heroicon-m-bolt" class="h-5 w-5 text-amber-400" />
            Market sắp đóng
        </p>
        @forelse($upcomingMarkets as $mkt)
            <div class="flex items-center justify-between py-3 border-b border-gray-100 dark:border-gray-700 last:border-0">
                <div class="flex-1">
                    <p class="font-bold text-sm text-gray-900 dark:text-white mb-1">
                        {{ $mkt['match']['home_team'] ?? 'N/A' }} 
                        @if(isset($mkt['match']['home_score']) && isset($mkt['match']['away_score']) && $mkt['match']['status'] !== 'PRE_MATCH')
                            <span class="text-primary-600 px-1">{{ $mkt['match']['home_score'] }} - {{ $mkt['match']['away_score'] }}</span>
                        @else
                            <span class="text-gray-400 px-1">vs</span>
                        @endif
                        {{ $mkt['match']['away_team'] ?? 'N/A' }}
                    </p>
                    <div class="flex items-center gap-2 mt-1 flex-wrap">
                        <x-filament::badge size="sm" color="info">{{ $mkt['name'] ?? 'Market #'.$mkt['id'] }}</x-filament::badge>
                        <p class="text-xs text-gray-500">
                            <x-filament::icon icon="heroicon-m-clock" class="inline h-3 w-3 mr-0.5" />
                            Đóng: {{ \Carbon\Carbon::parse($mkt['close_at'])->setTimezone('Asia/Ho_Chi_Minh')->format('d/m H:i') }}
                        </p>
                    </div>
                </div>
                <div class="ml-4">
                    <x-filament::button size="sm" tag="a" href="{{ \App\Filament\Player\Pages\MatchDetailPage::getUrl(['record' => $mkt['match_id']]) }}">
                        Xem
                    </x-filament::button>
                </div>
            </div>
        @empty
            <p class="text-gray-400 text-sm">Chưa có trận nào đang mở. Vui lòng quay lại sau.</p>
        @endforelse
    </x-filament::card>
</x-filament-widgets::widget>
