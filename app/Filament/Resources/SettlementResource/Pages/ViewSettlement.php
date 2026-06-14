<?php

namespace App\Filament\Resources\SettlementResource\Pages;

use App\Filament\Resources\SettlementResource;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Database\Eloquent\Builder;

class ViewSettlement extends ViewRecord
{
    protected static string $resource = SettlementResource::class;

    protected function getSingleRecordQuery(): Builder
    {
        return parent::getSingleRecordQuery()
            ->with(['items.bet', 'items.user']);
    }
}
