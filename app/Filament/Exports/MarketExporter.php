<?php

namespace App\Filament\Exports;

use App\Models\Market;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Support\Number;

class MarketExporter extends Exporter
{
    protected static ?string $model = Market::class;

    public function getFileName(Export $export): string
    {
        return "keo-du-doan-" . now()->format('Y-m-d_H-i-s') . ".csv";
    }

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')
                ->label('ID'),
            ExportColumn::make('match.id'),
            ExportColumn::make('period_type'),
            ExportColumn::make('market_type'),
            ExportColumn::make('name'),
            ExportColumn::make('open_at'),
            ExportColumn::make('close_at'),
            ExportColumn::make('status'),
            ExportColumn::make('display_order'),
            ExportColumn::make('created_by'),
            ExportColumn::make('locked_at'),
            ExportColumn::make('settled_at'),
            ExportColumn::make('voided_at'),
            ExportColumn::make('void_reason'),
            ExportColumn::make('created_at'),
            ExportColumn::make('updated_at'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your market export has completed and ' . Number::format($export->successful_rows) . ' ' . str('row')->plural($export->successful_rows) . ' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . Number::format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to export.';
        }

        return $body;
    }
}
