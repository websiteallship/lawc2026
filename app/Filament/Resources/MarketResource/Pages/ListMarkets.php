<?php

namespace App\Filament\Resources\MarketResource\Pages;

use App\Filament\Resources\MarketResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMarkets extends ListRecords
{
    protected static string $resource = MarketResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\ExportAction::make()
                ->exporter(\App\Filament\Exports\MarketExporter::class),
            Actions\Action::make('bulk-create')
                ->label('Tạo kèo hàng loạt')
                ->icon('heroicon-o-squares-plus')
                ->color('success')
                ->url(MarketResource::getUrl('bulk-create')),
            Actions\CreateAction::make()
                ->label('Tạo 1 kèo'),
        ];
    }
}
