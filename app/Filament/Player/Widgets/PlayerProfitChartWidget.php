<?php

namespace App\Filament\Player\Widgets;

use App\Models\Bet;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class PlayerProfitChartWidget extends ChartWidget
{
    protected ?string $heading = 'Biến Động Lãi Ròng (Net Profit)';

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected ?string $maxHeight = '300px';

    public ?string $filter = 'week';

    protected function getFilters(): ?array
    {
        return [
            'today' => 'Hôm nay',
            'week'  => '7 ngày qua',
            'month' => '30 ngày qua',
            'all'   => 'Toàn thời gian',
        ];
    }

    protected function getData(): array
    {
        $user = Auth::user();
        $activeFilter = $this->filter;
        
        $chartData = \Illuminate\Support\Facades\Cache::remember("player_profit_chart_{$user->id}_{$activeFilter}", 300, function () use ($user, $activeFilter) {
            $query = Bet::where('user_id', $user->id)
                ->whereNotIn('status', ['PENDING', 'VOIDED'])
                ->whereNotNull('settled_at');
                
            if ($activeFilter === 'today') {
                $query->where('settled_at', '>=', now()->startOfDay());
            } elseif ($activeFilter === 'week') {
                $query->where('settled_at', '>=', now()->subDays(7));
            } elseif ($activeFilter === 'month') {
                $query->where('settled_at', '>=', now()->subDays(30));
            }

            $bets = $query->orderBy('settled_at', 'asc')->get();

            $data = [];
            $labels = [];
            
            $cumulativeProfit = 0;
            
            $data[] = 0;
            $labels[] = 'Bắt đầu';

            foreach ($bets as $bet) {
                $net = $bet->net_result ?? (($bet->gross_payout ?? 0) - $bet->stake);
                $cumulativeProfit += $net;
                $data[] = $cumulativeProfit;
                $labels[] = $bet->settled_at->timezone('Asia/Ho_Chi_Minh')->format('d/m H:i');
            }

            return [
                'data' => $data,
                'labels' => $labels,
            ];
        });

        return [
            'datasets' => [
                [
                    'label' => 'Lãi Ròng (Lá)',
                    'data' => $chartData['data'],
                    'borderColor' => '#f59e0b',
                    'backgroundColor' => 'rgba(245, 158, 11, 0.2)',
                    'fill' => true,
                ],
            ],
            'labels' => $chartData['labels'],
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
