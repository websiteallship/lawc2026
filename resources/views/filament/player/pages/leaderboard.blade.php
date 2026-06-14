<x-filament-panels::page>
    <div class="space-y-4">
        <x-filament::card>
            @if(count($rankings) === 0)
                <p class="text-center text-gray-400 py-4">Chưa có dữ liệu xếp hạng. Hãy là người đầu tiên đặt dự đoán!</p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-gray-500 border-b border-gray-200 dark:border-gray-700">
                                <th class="py-2 pr-4">#</th>
                                <th class="py-2 pr-4">Người chơi</th>
                                <th class="py-2 pr-4 text-right">Lãi/lỗ</th>
                                <th class="py-2 pr-4 text-right">ROI</th>
                                <th class="py-2 pr-4 text-right">Win%</th>
                                <th class="py-2 text-right">Phiếu</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($rankings as $row)
                                <tr class="border-b border-gray-100 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-800/50 transition">
                                    <td class="py-3 pr-4">
                                        @if($row['rank'] === 1) <x-filament::icon icon="heroicon-s-trophy" class="h-5 w-5 text-yellow-400" />
                                        @elseif($row['rank'] === 2) <x-filament::icon icon="heroicon-s-trophy" class="h-5 w-5 text-gray-400" />
                                        @elseif($row['rank'] === 3) <x-filament::icon icon="heroicon-s-trophy" class="h-5 w-5 text-amber-700" />
                                        @else <span class="font-medium text-gray-500">{{ $row['rank'] }}</span>
                                        @endif
                                    </td>
                                    <td class="py-3 pr-4">
                                        <div class="flex items-center gap-2">
                                            <span class="text-2xl" title="Avatar">{{ $row['avatar'] }}</span>
                                            <span class="font-medium text-gray-700 dark:text-gray-200">{{ $row['name'] }}</span>
                                        </div>
                                    </td>
                                    <td class="py-3 pr-4 text-right
                                        {{ $row['net_profit'] >= 0 ? 'text-emerald-600' : 'text-red-500' }}">
                                        {{ $row['net_profit'] >= 0 ? '+' : '' }}{{ number_format($row['net_profit']) }}
                                    </td>
                                    <td class="py-3 pr-4 text-right text-gray-600 dark:text-gray-300">
                                        {{ $row['roi'] !== null ? $row['roi'].'%' : '—' }}
                                    </td>
                                    <td class="py-3 pr-4 text-right text-gray-600 dark:text-gray-300">
                                        {{ $row['win_rate'] }}%
                                    </td>
                                    <td class="py-3 text-right text-gray-500">
                                        {{ $row['bets_count'] }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <p class="text-xs text-gray-400 mt-3 text-center">
                    * ROI và Win% tính trên tất cả phiếu đã settle. Phải có ít nhất 1 phiếu để xuất hiện.
                </p>
            @endif
        </x-filament::card>
    </div>
</x-filament-panels::page>
