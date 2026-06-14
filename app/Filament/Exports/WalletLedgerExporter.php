<?php

namespace App\Filament\Exports;

use App\Models\WalletLedger;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Support\Number;

class WalletLedgerExporter extends Exporter
{
    protected static ?string $model = WalletLedger::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')
                ->label('ID'),
            ExportColumn::make('wallet.id'),
            ExportColumn::make('user.name'),
            ExportColumn::make('season_id'),
            ExportColumn::make('type'),
            ExportColumn::make('amount_available'),
            ExportColumn::make('amount_locked'),
            ExportColumn::make('balance_available_after'),
            ExportColumn::make('balance_locked_after'),
            ExportColumn::make('bet_id'),
            ExportColumn::make('settlement_id'),
            ExportColumn::make('actor.name'),
            ExportColumn::make('reason'),
            ExportColumn::make('metadata'),
            ExportColumn::make('created_at'),
            ExportColumn::make('deleted_at'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your wallet ledger export has completed and ' . Number::format($export->successful_rows) . ' ' . str('row')->plural($export->successful_rows) . ' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . Number::format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to export.';
        }

        return $body;
    }
}
