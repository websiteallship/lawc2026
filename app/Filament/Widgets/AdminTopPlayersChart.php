<?php

namespace App\Filament\Widgets;

use App\Models\Season;
use App\Models\Wallet;
use Filament\Widgets\ChartWidget;

class AdminTopPlayersChart extends ChartWidget
{
    protected ?string $heading = 'Top 10 Người Chơi (Theo số dư)';

    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    protected function getData(): array
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
            $labels[] = $wallet->user ? $wallet->user->name : 'N/A';
            $data[] = $wallet->total_balance;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Tổng Lá',
                    'data' => $data,
                    'backgroundColor' => '#f59e0b', // amber-500
                    'borderRadius' => 4,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
