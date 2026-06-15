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

    protected function getData(): array
    {
        $user = Auth::user();
        
        $chartData = \Illuminate\Support\Facades\Cache::remember("player_profit_chart_{$user->id}", 900, function () use ($user) {
            $bets = Bet::where('user_id', $user->id)
                ->whereNotNull('settled_at')
                ->orderBy('settled_at', 'asc')
                ->get();

            $data = [];
            $labels = [];
            
            $cumulativeProfit = 0;
            
            $data[] = 0;
            $labels[] = 'Bắt đầu';

            foreach ($bets as $bet) {
                $net = ($bet->gross_payout ?? 0) - $bet->stake;
                $cumulativeProfit += $net;
                $data[] = $cumulativeProfit;
                $labels[] = $bet->settled_at->format('d/m H:i');
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
