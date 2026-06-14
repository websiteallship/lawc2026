<?php

namespace App\Filament\Resources\FootballMatchResource\Pages;

use App\Enums\MarketStatus;
use App\Filament\Resources\FootballMatchResource;
use App\Models\Market;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditFootballMatch extends EditRecord
{
    protected static string $resource = FootballMatchResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }

    protected function afterSave(): void
    {
        $match = $this->record;

        // Tự động chuyển trạng thái sang LIVE nếu đã qua giờ đá mà vẫn là SCHEDULED
        if ($match->kickoff_at && $match->kickoff_at->isPast() && $match->status === 'SCHEDULED') {
            $match->update(['status' => 'LIVE']);
        }

        // Tự động đồng bộ giờ đóng kèo cho các kèo chưa khóa hoặc đã khóa
        Market::where('match_id', $match->id)
            ->whereIn('status', [MarketStatus::DRAFT->value, MarketStatus::OPEN->value, MarketStatus::LOCKED->value])
            ->update(['close_at' => $match->kickoff_at]);
    }
}
