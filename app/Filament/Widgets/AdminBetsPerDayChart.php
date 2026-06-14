<?php

namespace App\Filament\Widgets;

use App\Models\Bet;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class AdminBetsPerDayChart extends ChartWidget
{
    protected ?string $heading = 'Số phiếu dự đoán (7 ngày qua)';

    protected static ?int $sort = 2;

    protected function getData(): array
    {
        $data = Bet::select(
            DB::raw('date(created_at) as date'),
            DB::raw('count(*) as aggregate')
        )
            ->where('created_at', '>=', now()->subDays(7)->startOfDay())
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $labels = [];
        $values = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $labels[] = Carbon::parse($date)->format('d/m');
            $values[] = $data->firstWhere('date', $date)->aggregate ?? 0;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Số phiếu đặt',
                    'data' => $values,
                    'borderColor' => '#10b981', // emerald-500
                    'backgroundColor' => 'rgba(16, 185, 129, 0.2)',
                    'fill' => true,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
