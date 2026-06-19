<?php

namespace App\Filament\Player\Widgets;

use App\Models\Season;
use App\Models\Wallet;
use App\Models\WalletLedger;
use App\Support\DatePeriodFilter;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class PlayerWalletFlowChart extends ApexChartWidget
{
    protected static ?string $chartId = 'playerWalletFlowChart';

    protected static ?string $heading = 'Dòng Lá Lưu Hành';

    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    public ?string $filter = 'this_week';

    protected function getFilters(): ?array
    {
        return DatePeriodFilter::options();
    }

    protected function getOptions(): array
    {
        $user = Auth::user();
        $activeSeason = Season::where('status', 'active')->first();

        if (! $activeSeason) {
            return $this->emptyChartOptions();
        }

        $wallet = Wallet::where('user_id', $user->id)
            ->where('season_id', $activeSeason->id)
            ->first();

        if (! $wallet) {
            return $this->emptyChartOptions();
        }

        $seasonStart = Carbon::parse($activeSeason->created_at);
        [$start, $end, $days] = DatePeriodFilter::resolve($this->filter, $seasonStart);

        $data = WalletLedger::where('wallet_id', $wallet->id)
            ->where('created_at', '>=', $start)
            ->where('created_at', '<=', $end)
            ->select(
                DB::raw('date(created_at) as date'),
                DB::raw('SUM(CASE WHEN amount_available > 0 THEN amount_available ELSE 0 END) as received'),
                DB::raw('SUM(CASE WHEN amount_available < 0 THEN ABS(amount_available) ELSE 0 END) as spent')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        $labels = [];
        $received = [];
        $spent = [];
        $cumulative = [];
        $running = 0;

        foreach (DatePeriodFilter::dateRange($start, $end) as $date) {
            $key = $date->format('Y-m-d');
            $row = $data->get($key);

            $labels[] = $date->format('d/m');
            $r = (int) ($row?->received ?? 0);
            $s = (int) ($row?->spent ?? 0);
            $received[] = $r;
            $spent[] = $s;
            $running += ($r - $s);
            $cumulative[] = $running;
        }

        return [
            'chart' => [
                'type'    => 'bar',
                'height'  => 300,
                'toolbar' => ['show' => true],
                'stacked' => false,
            ],
            'series' => [
                ['name' => 'Nhận vào (Lá)', 'type' => 'bar',  'data' => $received],
                ['name' => 'Chi ra (Lá)',   'type' => 'bar',  'data' => array_map(fn ($v) => -$v, $spent)],
                ['name' => 'Ròng tích lũy', 'type' => 'line', 'data' => $cumulative],
            ],
            'stroke' => ['width' => [0, 0, 3], 'curve' => 'smooth'],
            'xaxis'  => [
                'categories' => $labels,
                'labels' => [
                    'rotate'    => -45,
                    'style'     => ['fontFamily' => 'inherit', 'fontSize' => '11px'],
                    'maxHeight' => 70,
                ],
                'tickAmount' => min(count($labels), 30),
            ],
            'yaxis' => [
                ['title' => ['text' => 'Lá/ngày'], 'labels' => ['style' => ['fontFamily' => 'inherit']]],
                ['opposite' => true, 'title' => ['text' => 'Tích lũy'], 'labels' => ['style' => ['fontFamily' => 'inherit']]],
            ],
            'plotOptions' => ['bar' => ['borderRadius' => 4, 'columnWidth' => '60%']],
            'colors'      => ['#10b981', '#ef4444', '#f59e0b'],
            'dataLabels'  => ['enabled' => false],
            'legend'      => ['position' => 'top'],
            'tooltip'     => ['shared' => true, 'intersect' => false],
        ];
    }

    private function emptyChartOptions(): array
    {
        return [
            'chart'  => ['type' => 'bar', 'height' => 200],
            'series' => [],
            'xaxis'  => ['categories' => []],
        ];
    }
}
