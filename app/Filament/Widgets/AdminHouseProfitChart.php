<?php

namespace App\Filament\Widgets;

use App\Models\WalletLedger;
use App\Models\Season;
use App\Support\DatePeriodFilter;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class AdminHouseProfitChart extends ApexChartWidget
{
    protected static ?string $chartId = 'adminHouseProfitChart';

    protected static ?string $heading = 'Lãi/Lỗ Nhà Cái';

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    public ?string $filter = '30';

    protected function getFilters(): ?array
    {
        return DatePeriodFilter::options();
    }

    protected function getOptions(): array
    {
        $types = [
            'BET_WON', 'BET_LOST', 'BET_HALF_WON', 'BET_HALF_LOST',
            'BET_PUSH', 'SETTLEMENT_CORRECTION', 'BET_VOIDED',
        ];

        $testUserIds = \App\Models\User::where('is_test_user', true)->pluck('id');

        $activeSeason = Season::where('status', 'active')->first();
        $seasonStart = $activeSeason
            ? Carbon::parse($activeSeason->created_at)
            : null;

        [$start, $end, $days] = DatePeriodFilter::resolve($this->filter, $seasonStart);
        $useWeekly = DatePeriodFilter::useWeekly($days);

        if ($useWeekly) {
            $data = WalletLedger::select(
                DB::raw("to_char(date_trunc('week', created_at), 'IYYY-IW') as week_key"),
                DB::raw('MIN(date(created_at)) as date'),
                DB::raw('SUM(-1 * (amount_available + amount_locked)) as profit')
            )
                ->whereIn('type', $types)
                ->whereNotIn('user_id', $testUserIds)
                ->where('created_at', '>=', $start)
                ->where('created_at', '<=', $end)
                ->groupBy('week_key')
                ->orderBy('date')
                ->get();

            $labels = $data->map(fn ($row) => 'T' . Carbon::parse($row->date)->format('W/y'))->toArray();
            $values = $data->map(fn ($row) => (int) ($row->profit ?? 0))->toArray();
        } else {
            $data = WalletLedger::select(
                DB::raw('date(created_at) as date'),
                DB::raw('SUM(-1 * (amount_available + amount_locked)) as profit')
            )
                ->whereIn('type', $types)
                ->whereNotIn('user_id', $testUserIds)
                ->where('created_at', '>=', $start)
                ->where('created_at', '<=', $end)
                ->groupBy('date')
                ->orderBy('date')
                ->get()
                ->keyBy('date');

            $labels = [];
            $values = [];
            foreach (DatePeriodFilter::dateRange($start, $end) as $date) {
                $key = $date->format('Y-m-d');
                $labels[] = $date->format('d/m');
                $values[] = (int) ($data->get($key)?->profit ?? 0);
            }
        }

        $cumulative = [];
        $running = 0;
        foreach ($values as $v) {
            $running += $v;
            $cumulative[] = $running;
        }

        return [
            'chart' => [
                'type'    => 'bar',
                'height'  => 350,
                'toolbar' => ['show' => true],
                'zoom'    => ['enabled' => true],
            ],
            'series' => [
                ['name' => 'Lãi/Lỗ theo kỳ (Lá)', 'type' => 'bar',  'data' => $values],
                ['name' => 'Tích lũy (Lá)',          'type' => 'line', 'data' => $cumulative],
            ],
            'stroke' => ['width' => [0, 3], 'curve' => 'smooth'],
            'xaxis'  => [
                'categories' => $labels,
                'labels'     => ['rotate' => -45, 'style' => ['fontFamily' => 'inherit', 'fontSize' => '11px'], 'maxHeight' => 80],
                'tickAmount' => min(count($labels), 30),
            ],
            'yaxis' => [
                ['title' => ['text' => 'Lá mỗi kỳ'],  'labels' => ['style' => ['fontFamily' => 'inherit']]],
                ['opposite' => true, 'title' => ['text' => 'Tích lũy'], 'labels' => ['style' => ['fontFamily' => 'inherit']]],
            ],
            'plotOptions' => [
                'bar' => [
                    'colors' => [
                        'ranges' => [
                            ['from' => -999999999, 'to' => -1, 'color' => '#ef4444'],
                            ['from' => 0, 'to' => 999999999, 'color' => '#10b981'],
                        ],
                    ],
                ],
            ],
            'dataLabels' => ['enabled' => false],
            'colors'     => ['#10b981', '#f59e0b'],
            'legend'     => ['position' => 'top'],
            'tooltip'    => ['shared' => true, 'intersect' => false],
        ];
    }
}
