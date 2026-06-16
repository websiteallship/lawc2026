<?php

namespace App\Filament\Widgets;

use App\Models\WalletLedger;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class AdminHouseProfitChart extends ApexChartWidget
{
    /**
     * Chart Id
     *
     * @var string
     */
    protected static ?string $chartId = 'adminHouseProfitChart';

    /**
     * Widget Title
     *
     * @var string|null
     */
    protected static ?string $heading = 'Dòng Lá Thu/Chi (Lợi nhuận Nhà cái - 7 ngày)';

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    /**
     * Chart options (series, labels, types, size, animations...)
     * https://apexcharts.com/docs/options
     *
     * @return array
     */
    protected function getOptions(): array
    {
        $types = [
            'BET_WON', 'BET_LOST', 'BET_HALF_WON', 'BET_HALF_LOST', 'BET_PUSH', 'SETTLEMENT_CORRECTION', 'BET_VOIDED'
        ];

        $data = WalletLedger::select(
            DB::raw('date(created_at) as date'),
            DB::raw('SUM(-1 * (amount_available + amount_locked)) as profit')
        )
            ->whereIn('type', $types)
            ->where('created_at', '>=', now()->subDays(7)->startOfDay())
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $labels = [];
        $values = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $labels[] = Carbon::parse($date)->format('d/m');
            $values[] = (int) ($data->firstWhere('date', $date)->profit ?? 0);
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
                    'name' => 'Lãi/Lỗ nhà cái (Lá)',
                    'data' => $values,
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
            'plotOptions' => [
                'bar' => [
                    'colors' => [
                        'ranges' => [
                            [
                                'from' => -999999999,
                                'to' => -1,
                                'color' => '#ef4444', // red-500
                            ],
                            [
                                'from' => 0,
                                'to' => 999999999,
                                'color' => '#10b981', // emerald-500
                            ],
                        ],
                    ],
                ],
            ],
            'dataLabels' => [
                'enabled' => false,
            ],
            'colors' => ['#10b981'], // Default fallback color
        ];
    }
}
