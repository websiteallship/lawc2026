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

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('correct')
                ->label('Sửa kết quả (Correction)')
                ->color('warning')
                ->icon('heroicon-o-pencil-square')
                ->visible(fn (\App\Models\Settlement $record) => $record->status === 'EXECUTED' && auth()->user()->can('update', $record))
                ->form([
                    \Filament\Forms\Components\TextInput::make('home_score')
                        ->label('Tỉ số Đội nhà')
                        ->numeric()
                        ->required()
                        ->default(fn (\App\Models\Settlement $record) => $record->result_home_score),
                    \Filament\Forms\Components\TextInput::make('away_score')
                        ->label('Tỉ số Đội khách')
                        ->numeric()
                        ->required()
                        ->default(fn (\App\Models\Settlement $record) => $record->result_away_score),
                    \Filament\Forms\Components\Textarea::make('reason')
                        ->label('Lý do sửa')
                        ->required(),
                ])
                ->action(function (array $data, \App\Models\Settlement $record, \App\Domain\Settlement\Services\CorrectionService $correctionService) {
                    $correction = $correctionService->createCorrection(
                        $record->id,
                        [
                            'home_score' => $data['home_score'],
                            'away_score' => $data['away_score'],
                        ],
                        $data['reason'],
                        auth()->user()
                    );

                    $correctionService->executeCorrection($correction, auth()->user());

                    \Filament\Notifications\Notification::make()
                        ->title('Đã sửa kết quả Settlement và tự động điều chỉnh Wallet/Bet.')
                        ->success()
                        ->send();
                })
                ->requiresConfirmation()
                ->modalHeading('Sửa kết quả Settlement')
                ->modalDescription('Hệ thống sẽ tính toán lại mức trả thưởng, cộng/trừ bù vào ví người chơi và đánh dấu Bet thành CORRECTED. Hành động này sẽ được ghi vào Audit Log.'),
        ];
    }
}
