<?php

namespace App\Filament\Player\Widgets;

use App\Models\Bet;
use App\Support\DatePeriodFilter;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class PlayerProfitChartWidget extends ChartWidget
{
    protected ?string $heading = 'Biến Động Lãi Ròng (Net Profit)';

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected ?string $maxHeight = '300px';

    public ?string $filter = '30';

    protected function getFilters(): ?array
    {
        return DatePeriodFilter::options();
    }

    protected function getData(): array
    {
        $user = Auth::user();
        $filter = $this->filter ?? '30';

        [$start, $end] = DatePeriodFilter::resolve($filter);

        $cacheKey = "player_profit_chart_{$user->id}_{$filter}";

        $chartData = Cache::remember($cacheKey, 300, function () use ($user, $start, $end) {
            $bets = Bet::where('user_id', $user->id)
                ->whereNotIn('status', ['PENDING', 'VOIDED'])
                ->whereNotNull('settled_at')
                ->where('settled_at', '>=', $start)
                ->where('settled_at', '<=', $end)
                ->orderBy('settled_at', 'asc')
                ->get();

            $data = [0];
            $labels = ['Bắt đầu'];
            $cumulativeProfit = 0;

            foreach ($bets as $bet) {
                $net = $bet->net_result ?? (($bet->gross_payout ?? 0) - $bet->stake);
                $cumulativeProfit += $net;
                $data[] = $cumulativeProfit;
                $labels[] = $bet->settled_at->timezone('Asia/Ho_Chi_Minh')->format('d/m H:i');
            }

            return ['data' => $data, 'labels' => $labels];
        });

        return [
            'datasets' => [
                [
                    'label'           => 'Lãi Ròng (Lá)',
                    'data'            => $chartData['data'],
                    'borderColor'     => '#f59e0b',
                    'backgroundColor' => 'rgba(245, 158, 11, 0.2)',
                    'fill'            => true,
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
