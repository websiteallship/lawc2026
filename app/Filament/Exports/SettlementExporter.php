<?php

namespace App\Filament\Exports;

use App\Models\Settlement;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Support\Number;

class SettlementExporter extends Exporter
{
    protected static ?string $model = Settlement::class;

    public function getFileName(Export $export): string
    {
        return "ket-qua-du-doan-" . now()->format('Y-m-d_H-i-s') . ".csv";
    }

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')
                ->label('ID'),
            ExportColumn::make('market.name'),
            ExportColumn::make('match.id'),
            ExportColumn::make('period_type'),
            ExportColumn::make('status'),
            ExportColumn::make('result_home_score'),
            ExportColumn::make('result_away_score'),
            ExportColumn::make('total_bets'),
            ExportColumn::make('total_stake'),
            ExportColumn::make('total_payout'),
            ExportColumn::make('executed_by'),
            ExportColumn::make('executed_at'),
            ExportColumn::make('reason'),
            ExportColumn::make('metadata'),
            ExportColumn::make('created_at'),
            ExportColumn::make('updated_at'),
            ExportColumn::make('deleted_at'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your settlement export has completed and ' . Number::format($export->successful_rows) . ' ' . str('row')->plural($export->successful_rows) . ' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . Number::format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to export.';
        }

        return $body;
    }
}
