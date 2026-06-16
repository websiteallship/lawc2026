<?php

namespace App\Filament\Widgets;

use App\Models\Season;
use App\Models\Wallet;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class AdminTopPlayersChart extends ApexChartWidget
{
    /**
     * Chart Id
     *
     * @var string
     */
    protected static ?string $chartId = 'adminTopPlayersChart';

    /**
     * Widget Title
     *
     * @var string|null
     */
    protected static ?string $heading = 'Top 10 Người Chơi (Theo số dư)';

    protected static ?int $sort = 5;

    protected int|string|array $columnSpan = 'full';

    /**
     * Chart options (series, labels, types, size, animations...)
     * https://apexcharts.com/docs/options
     *
     * @return array
     */
    protected function getOptions(): array
    {
        $activeSeason = Season::where('status', 'active')->first();

        $wallets = Wallet::with('user')
            ->when($activeSeason, fn ($query) => $query->where('season_id', $activeSeason->id))
            ->orderByRaw('(available_balance + locked_balance) DESC')
            ->take(10)
            ->get();

        $labels = [];
        $data = [];

        foreach ($wallets as $wallet) {
            $name = $wallet->user ? $wallet->user->name : 'N/A';
            // Truncate name if it's too long so it fits nicely on the X axis
            $labels[] = mb_strlen($name) > 15 ? mb_substr($name, 0, 15) . '...' : $name;
            $data[] = (int) $wallet->total_balance;
        }

        return [
            'chart' => [
                'type' => 'bar',
                'height' => 300,
                'toolbar' => [
                    'show' => false,
                ],
            ],
            'series' => [
                [
                    'name' => 'Tổng Lá',
                    'data' => $data,
                ],
            ],
            'xaxis' => [
                'categories' => $labels,
                'labels' => [
                    'style' => [
                        'fontFamily' => 'inherit',
                    ],
                ],
            ],
            'yaxis' => [
                'labels' => [
                    'style' => [
                        'fontFamily' => 'inherit',
                    ],
                ],
            ],
            'colors' => ['#f59e0b'], // amber-500
            'plotOptions' => [
                'bar' => [
                    'borderRadius' => 4,
                    'horizontal' => false,
                ],
            ],
            'dataLabels' => [
                'enabled' => false,
            ],
        ];
    }
}
