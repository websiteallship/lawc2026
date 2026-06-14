<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Ví lá --}}
        <div class="grid grid-cols-3 gap-4">
            <x-filament::card>
                <div class="text-center">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Lá khả dụng</p>
                    <p class="text-3xl font-bold text-emerald-600">
                        {{ $wallet ? number_format($wallet->available_balance) : 0 }} lá
                    </p>
                </div>
            </x-filament::card>
            <x-filament::card>
                <div class="text-center">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Đang khóa</p>
                    <p class="text-3xl font-bold text-amber-500">
                        {{ $wallet ? number_format($wallet->locked_balance) : 0 }} lá
                    </p>
                </div>
            </x-filament::card>
            <x-filament::card>
                <div class="text-center">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Tổng lá</p>
                    <p class="text-3xl font-bold text-gray-700 dark:text-gray-200">
                        {{ $wallet ? number_format($wallet->total_balance) : 0 }} lá
                    </p>
                </div>
            </x-filament::card>
        </div>

        {{-- Phiếu đang chờ + Hạng --}}
        <div class="grid grid-cols-2 gap-4">
            <x-filament::card>
                <p class="font-semibold text-gray-700 dark:text-gray-200 mb-2 flex items-center gap-2">
                    <x-filament::icon icon="heroicon-m-ticket" class="h-5 w-5 text-blue-500" />
                    Phiếu đang chờ
                </p>
                @if($pendingBetCount > 0)
                    <p class="text-2xl font-bold text-blue-600">{{ $pendingBetCount }} phiếu</p>
                    <p class="text-sm text-gray-500">Tổng lá đang khóa: {{ number_format($pendingStakeTotal) }}</p>
                @else
                    <p class="text-gray-400 text-sm">Chưa có phiếu đang chờ</p>
                @endif
            </x-filament::card>
            <x-filament::card>
                <p class="font-semibold text-gray-700 dark:text-gray-200 mb-2 flex items-center gap-2">
                    <x-filament::icon icon="heroicon-m-trophy" class="h-5 w-5 text-amber-500" />
                    Thứ hạng
                </p>
                @if($netProfit !== null)
                    <p class="text-2xl font-bold {{ $netProfit >= 0 ? 'text-emerald-600' : 'text-red-500' }}">
                        {{ $netProfit >= 0 ? '+' : '' }}{{ number_format($netProfit) }} lá
                    </p>
                    <p class="text-sm text-gray-500">Lãi/lỗ mùa này</p>
                @else
                    <p class="text-gray-400 text-sm">Chưa có dữ liệu</p>
                @endif
            </x-filament::card>
        </div>

        {{-- Trận/market sắp đóng --}}
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

        {{-- Quick nav --}}
        <div class="grid grid-cols-2 gap-4">
            <x-filament::button
                tag="a"
                :href="App\Filament\Player\Pages\MatchListPage::getUrl()"
                icon="heroicon-o-calendar"
                size="xl"
                color="primary"
            >
                Xem trận đấu
            </x-filament::button>
            <x-filament::button
                tag="a"
                href="#"
                icon="heroicon-o-ticket"
                size="xl"
                color="gray"
            >
                Phiếu của tôi
            </x-filament::button>
        </div>
    </div>
</x-filament-panels::page>
