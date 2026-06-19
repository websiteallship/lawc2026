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
    protected static ?string $heading = 'Lãi/Lỗ Nhà Cái';

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected function getFilters(): ?array
    {
        return [
            '7'   => '7 ngày qua',
            '30'  => '30 ngày qua',
            '90'  => '90 ngày qua',
            'all' => 'Toàn bộ',
        ];
    }

    /**
     * Chart options (series, labels, types, size, animations...)
     * https://apexcharts.com/docs/options
     *
     * @return array
     */
    protected function getOptions(): array
    {
        $filter = $this->filter ?? '7';

        $types = [
            'BET_WON', 'BET_LOST', 'BET_HALF_WON', 'BET_HALF_LOST', 'BET_PUSH', 'SETTLEMENT_CORRECTION', 'BET_VOIDED'
        ];

        $testUserIds = \App\Models\User::where('is_test_user', true)->pluck('id');

        $query = WalletLedger::select(
            DB::raw('date(created_at) as date'),
            DB::raw('SUM(-1 * (amount_available + amount_locked)) as profit')
        )->whereIn('type', $types)
         ->whereNotIn('user_id', $testUserIds);

        if ($filter === 'all') {
            $query->whereNotNull('created_at');
            $startDate = WalletLedger::whereIn('type', $types)->min('created_at');
            $startDate = $startDate ? Carbon::parse($startDate)->startOfDay() : now()->subDays(6)->startOfDay();
            $days = (int) now()->startOfDay()->diffInDays($startDate) + 1;
            // Use weekly grouping if range > 90 days
            $useWeekly = $days > 90;
        } else {
            $days = (int) $filter;
            $useWeekly = $days > 90;
            $startDate = now()->subDays($days - 1)->startOfDay();
            $query->where('created_at', '>=', $startDate);
        }

        if ($useWeekly) {
            // Group by ISO week for long ranges
            $data = WalletLedger::select(
                DB::raw("to_char(date_trunc('week', created_at), 'IYYY-IW') as week_key"),
                DB::raw("MIN(date(created_at)) as date"),
                DB::raw('SUM(-1 * (amount_available + amount_locked)) as profit')
            )
                ->whereIn('type', $types)
                ->when($filter !== 'all', fn ($q) => $q->where('created_at', '>=', $startDate))
                ->groupBy('week_key')
                ->orderBy('date')
                ->get();

            $labels = $data->map(fn ($row) => 'T' . Carbon::parse($row->date)->format('W/y'))->toArray();
            $values = $data->map(fn ($row) => (int) ($row->profit ?? 0))->toArray();
        } else {
            $data = $query->groupBy('date')->orderBy('date')->get();

            $labels = [];
            $values = [];

            for ($i = $days - 1; $i >= 0; $i--) {
                $date = now()->subDays($i)->format('Y-m-d');
                $labels[] = Carbon::parse($date)->format('d/m');
                $values[] = (int) ($data->firstWhere('date', $date)->profit ?? 0);
            }
        }

        // Cumulative line series
        $cumulative = [];
        $running = 0;
        foreach ($values as $v) {
            $running += $v;
            $cumulative[] = $running;
        }

        return [
            'chart' => [
                'type'   => 'bar',
                'height' => 350,
                'toolbar' => ['show' => true],
                'zoom'   => ['enabled' => true],
            ],
            'series' => [
                [
                    'name' => 'Lãi/Lỗ theo kỳ (Lá)',
                    'type' => 'bar',
                    'data' => $values,
                ],
                [
                    'name' => 'Tích lũy (Lá)',
                    'type' => 'line',
                    'data' => $cumulative,
                ],
            ],
            'stroke' => [
                'width' => [0, 3],
                'curve' => 'smooth',
            ],
            'xaxis' => [
                'categories' => $labels,
                'labels' => [
                    'rotate'   => -45,
                    'style'    => ['fontFamily' => 'inherit', 'fontSize' => '11px'],
                    'maxHeight' => 80,
                ],
                'tickAmount' => min(count($labels), 30),
            ],
            'yaxis' => [
                [
                    'title'  => ['text' => 'Lá mỗi kỳ'],
                    'labels' => ['style' => ['fontFamily' => 'inherit']],
                ],
                [
                    'opposite' => true,
                    'title'    => ['text' => 'Tích lũy'],
                    'labels'   => ['style' => ['fontFamily' => 'inherit']],
                ],
            ],
            'plotOptions' => [
                'bar' => [
                    'colors' => [
                        'ranges' => [
                            ['from' => -999999999, 'to' => -1,        'color' => '#ef4444'],
                            ['from' => 0,          'to' => 999999999, 'color' => '#10b981'],
                        ],
                    ],
                ],
            ],
            'dataLabels' => ['enabled' => false],
            'colors'     => ['#10b981', '#f59e0b'],
            'legend'     => ['position' => 'top'],
            'tooltip'    => [
                'shared' => true,
                'intersect' => false,
            ],
        ];
    }
}

