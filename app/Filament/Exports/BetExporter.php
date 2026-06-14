<?php

namespace App\Filament\Exports;

use App\Models\Bet;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Support\Number;

class BetExporter extends Exporter
{
    protected static ?string $model = Bet::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')
                ->label('ID'),
            ExportColumn::make('public_code'),
            ExportColumn::make('user.name'),
            ExportColumn::make('wallet.id'),
            ExportColumn::make('season_id'),
            ExportColumn::make('match_id'),
            ExportColumn::make('market.name'),
            ExportColumn::make('outcome.id'),
            ExportColumn::make('stake'),
            ExportColumn::make('profit_rate_snapshot'),
            ExportColumn::make('line_snapshot'),
            ExportColumn::make('label_snapshot'),
            ExportColumn::make('display_odds_snapshot'),
            ExportColumn::make('close_at_snapshot'),
            ExportColumn::make('market_type_snapshot'),
            ExportColumn::make('period_type_snapshot'),
            ExportColumn::make('selection_side_snapshot'),
            ExportColumn::make('status'),
            ExportColumn::make('gross_payout'),
            ExportColumn::make('net_result'),
            ExportColumn::make('placed_at'),
            ExportColumn::make('settled_at'),
            ExportColumn::make('voided_at'),
            ExportColumn::make('metadata'),
            ExportColumn::make('created_at'),
            ExportColumn::make('updated_at'),
            ExportColumn::make('deleted_at'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your bet export has completed and ' . Number::format($export->successful_rows) . ' ' . str('row')->plural($export->successful_rows) . ' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . Number::format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to export.';
        }

        return $body;
    }
}
