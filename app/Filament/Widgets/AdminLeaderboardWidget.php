<?php

namespace App\Filament\Widgets;

use App\Models\Season;
use App\Models\Wallet;
use App\Models\Bet;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\DB;

class AdminLeaderboardWidget extends BaseWidget
{
    protected static ?int $sort = 6;

    protected static ?string $heading = 'Top 10 Bảng Xếp Hạng (Live)';

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        $activeSeason = Season::where('status', 'active')->first();

        $query = Wallet::query()
            ->with('user')
            ->when($activeSeason, fn ($q) => $q->where('season_id', $activeSeason->id))
            ->whereHas('user', fn ($q) => $q
                ->where('status', 'ACTIVE')
                ->whereHas('roles', fn ($r) => $r->where('name', 'player'))
            )
            ->orderByDesc('net_profit')
            ->limit(10)
            ->select([
                'wallets.*',
                DB::raw('(available_balance + locked_balance) as total_balance_live'),
            ]);

        // Attach settled bet counts via subquery for each wallet user
        $betStats = Bet::whereNotIn('status', ['PENDING', 'VOIDED'])
            ->selectRaw('user_id,
                COUNT(*) as total_bets,
                SUM(CASE WHEN status IN (\'WON\',\'HALF_WON\') THEN 1 ELSE 0 END) as won_bets,
                COUNT(*) as settled_bets')
            ->groupBy('user_id')
            ->get()
            ->keyBy('user_id');

        return $table
            ->query($query)
            ->columns([
                Tables\Columns\TextColumn::make('rank')
                    ->label('#')
                    ->badge()
                    ->state(fn ($record, $rowLoop) => $rowLoop->iteration)
                    ->color(fn ($state): string => match (true) {
                        $state === 1 => 'warning',
                        $state <= 3 => 'success',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Người chơi')
                    ->searchable(),
                Tables\Columns\TextColumn::make('total_balance_live')
                    ->label('Tổng Lá')
                    ->state(fn ($record) => $record->available_balance + $record->locked_balance)
                    ->numeric()
                    ->sortable(false),
                Tables\Columns\TextColumn::make('net_profit')
                    ->label('Lãi/Lỗ')
                    ->numeric()
                    ->color(fn ($state) => $state > 0 ? 'success' : ($state < 0 ? 'danger' : 'gray'))
                    ->formatStateUsing(fn ($state) => ($state >= 0 ? '+' : '').number_format($state)),
                Tables\Columns\TextColumn::make('total_bets')
                    ->label('Số phiếu')
                    ->state(function ($record) use ($betStats) { return (int) ($betStats->get($record->user_id)?->total_bets ?? 0); }),
                Tables\Columns\TextColumn::make('won_bets')
                    ->label('Thắng')
                    ->state(function ($record) use ($betStats) { return (int) ($betStats->get($record->user_id)?->won_bets ?? 0); }),
                Tables\Columns\TextColumn::make('win_rate')
                    ->label('Win rate')
                    ->state(function ($record) use ($betStats) {
                        $s = $betStats->get($record->user_id);
                        if (!$s || $s->settled_bets == 0) return '–';
                        return round($s->won_bets / $s->settled_bets * 100, 1).'%';
                    }),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Cập nhật lúc')
                    ->dateTime('d/m H:i')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->paginated(false);
    }
}
