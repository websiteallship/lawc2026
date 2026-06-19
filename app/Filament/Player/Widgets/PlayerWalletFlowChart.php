<?php

namespace App\Filament\Player\Widgets;

use App\Models\WalletLedger;
use App\Models\Season;
use App\Models\Wallet;
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

    public ?string $filter = '7';

    protected function getFilters(): ?array
    {
        return [
            '7'  => '7 ngày qua',
            '30' => '30 ngày qua',
        ];
    }

    protected function getOptions(): array
    {
        $user = Auth::user();
        $days = (int) ($this->filter ?? 7);
        $startDate = now()->subDays($days - 1)->startOfDay();

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

        $data = WalletLedger::where('wallet_id', $wallet->id)
            ->where('created_at', '>=', $startDate)
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

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $row = $data->get($date);

            $labels[] = Carbon::parse($date)->setTimezone('Asia/Ho_Chi_Minh')->format('d/m');
            $r = (int) ($row->received ?? 0);
            $s = (int) ($row->spent ?? 0);
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
                [
                    'name' => 'Nhận vào (Lá)',
                    'type' => 'bar',
                    'data' => $received,
                ],
                [
                    'name' => 'Chi ra (Lá)',
                    'type' => 'bar',
                    'data' => array_map(fn ($v) => -$v, $spent),
                ],
                [
                    'name' => 'Ròng tích lũy',
                    'type' => 'line',
                    'data' => $cumulative,
                ],
            ],
            'stroke' => [
                'width' => [0, 0, 3],
                'curve' => 'smooth',
            ],
            'xaxis' => [
                'categories' => $labels,
                'labels'     => [
                    'style' => ['fontFamily' => 'inherit', 'fontSize' => '11px'],
                ],
            ],
            'yaxis' => [
                [
                    'title'  => ['text' => 'Lá/ngày'],
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
                    'borderRadius'    => 4,
                    'columnWidth'     => '60%',
                ],
            ],
            'colors'     => ['#10b981', '#ef4444', '#f59e0b'],
            'dataLabels' => ['enabled' => false],
            'legend'     => ['position' => 'top'],
            'tooltip'    => [
                'shared'    => true,
                'intersect' => false,
                'y' => [
                    'formatter' => 'function(val) { return Math.abs(val).toLocaleString() + " lá"; }',
                ],
            ],
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
