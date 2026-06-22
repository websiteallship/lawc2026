<?php

namespace App\Filament\Widgets;

use App\Models\Bet;
use App\Support\DatePeriodFilter;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class AdminBetsPerDayChart extends ApexChartWidget
{
    protected static ?string $chartId = 'adminBetsPerDayChart';

    protected static ?string $heading = 'Số phiếu dự đoán';

    protected static ?int $sort = 3;

    public ?string $filter = 'season';

    protected function getFilters(): ?array
    {
        return DatePeriodFilter::options();
    }

    protected function getOptions(): array
    {
        [$start, $end, $days] = DatePeriodFilter::resolve($this->filter);

        $data = Bet::select(
            DB::raw('date(created_at) as date'),
            DB::raw('count(*) as aggregate')
        )
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
            $values[] = (int) ($data->get($key)?->aggregate ?? 0);
        }

        return [
            'chart' => ['type' => 'area', 'height' => 300],
            'series' => [['name' => 'Số phiếu đặt', 'data' => $values]],
            'xaxis'  => [
                'categories' => $labels,
                'labels' => ['rotate' => -45, 'style' => ['fontFamily' => 'inherit', 'fontSize' => '11px'], 'maxHeight' => 70],
                'tickAmount' => min(count($labels), 30),
            ],
            'yaxis'  => ['labels' => ['style' => ['fontFamily' => 'inherit']]],
            'colors' => ['#10b981'],
            'fill'   => [
                'type'     => 'gradient',
                'gradient' => ['shadeIntensity' => 1, 'opacityFrom' => 0.7, 'opacityTo' => 0.1, 'stops' => [0, 100]],
            ],
            'dataLabels' => ['enabled' => false],
            'stroke'     => ['curve' => 'smooth'],
        ];
    }
}
