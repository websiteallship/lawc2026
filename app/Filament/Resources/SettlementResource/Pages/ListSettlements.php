<?php

namespace App\Filament\Resources\SettlementResource\Pages;

use App\Filament\Resources\SettlementResource;
use Filament\Resources\Pages\ListRecords;

class ListSettlements extends ListRecords
{
    protected static string $resource = SettlementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\ExportAction::make()
                ->exporter(\App\Filament\Exports\SettlementExporter::class)
                ->label('Xuất kết quả mở thưởng'),
        ];
    }
}
