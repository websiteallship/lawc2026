<?php

namespace App\Filament\Widgets;

use App\Models\Bet;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class AdminMarketTypeChart extends ChartWidget
{
    protected ?string $heading = 'Phân bổ loại kèo';

    protected static ?int $sort = 2;

    protected function getData(): array
    {
        $data = Bet::select(
            'market_type_snapshot',
            DB::raw('count(*) as aggregate')
        )
            ->groupBy('market_type_snapshot')
            ->get();

        $labels = [];
        $values = [];
        $colors = [];

        $colorMap = [
            'EXACT_SCORE' => '#f59e0b', // amber-500
            'ASIAN_HANDICAP' => '#3b82f6', // blue-500
            'OVER_UNDER' => '#10b981', // emerald-500
        ];

        foreach ($data as $row) {
            $labels[] = $row->market_type_snapshot;
            $values[] = $row->aggregate;
            $colors[] = $colorMap[$row->market_type_snapshot] ?? '#6b7280'; // gray-500 fallback
        }

        return [
            'datasets' => [
                [
                    'label' => 'Số lượng vé',
                    'data' => $values,
                    'backgroundColor' => $colors,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
