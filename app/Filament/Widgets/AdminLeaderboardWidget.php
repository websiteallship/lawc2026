<?php

namespace App\Filament\Widgets;

use App\Models\Season;
use App\Models\Wallet;
use App\Models\Bet;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\DatePicker;
use Illuminate\Database\Eloquent\Builder;
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
            ->select([
                'wallets.*',
                DB::raw('(available_balance + locked_balance) as total_balance_live'),
            ]);

        // Static stats fallback for when no date filter is applied
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
            ->defaultSort('net_profit', 'desc')
            ->filters([
                Filter::make('date_range')
                    ->form([
                        DatePicker::make('date_from')->label('Từ ngày'),
                        DatePicker::make('date_to')->label('Đến ngày'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $from = $data['date_from'] ?? null;
                        $to = $data['date_to'] ?? null;

                        $query->addSelect([
                            'period_profit' => DB::table('bets')
                                ->selectRaw('COALESCE(SUM(net_result), 0)')
                                ->whereColumn('wallet_id', 'wallets.id')
                                ->whereNotIn('status', ['PENDING', 'VOIDED'])
                                ->when($from, fn ($q) => $q->where('settled_at', '>=', $from))
                                ->when($to, fn ($q) => $q->where('settled_at', '<=', $to . ' 23:59:59')),
                            'calc_total_bets' => DB::table('bets')
                                ->selectRaw('COUNT(*)')
                                ->whereColumn('wallet_id', 'wallets.id')
                                ->whereNotIn('status', ['PENDING', 'VOIDED'])
                                ->when($from, fn ($q) => $q->where('settled_at', '>=', $from))
                                ->when($to, fn ($q) => $q->where('settled_at', '<=', $to . ' 23:59:59')),
                            'calc_won_bets' => DB::table('bets')
                                ->selectRaw('SUM(CASE WHEN status IN (\'WON\',\'HALF_WON\') THEN 1 ELSE 0 END)')
                                ->whereColumn('wallet_id', 'wallets.id')
                                ->whereNotIn('status', ['PENDING', 'VOIDED'])
                                ->when($from, fn ($q) => $q->where('settled_at', '>=', $from))
                                ->when($to, fn ($q) => $q->where('settled_at', '<=', $to . ' 23:59:59')),
                            'calc_settled_bets' => DB::table('bets')
                                ->selectRaw('COUNT(*)')
                                ->whereColumn('wallet_id', 'wallets.id')
                                ->whereNotIn('status', ['PENDING', 'VOIDED'])
                                ->when($from, fn ($q) => $q->where('settled_at', '>=', $from))
                                ->when($to, fn ($q) => $q->where('settled_at', '<=', $to . ' 23:59:59')),
                        ]);

                        if ($from || $to) {
                            $query->getQuery()->orders = null; // Xóa order mặc định
                            $query->orderByDesc('period_profit');
                        }

                        return $query;
                    })
            ])
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
                    ->state(fn ($record) => $record->period_profit ?? $record->net_profit)
                    ->color(fn ($state) => $state > 0 ? 'success' : ($state < 0 ? 'danger' : 'gray'))
                    ->formatStateUsing(fn ($state) => ($state >= 0 ? '+' : '').number_format((float)$state)),
                Tables\Columns\TextColumn::make('total_bets')
                    ->label('Số phiếu')
                    ->state(fn ($record) => isset($record->calc_total_bets) ? (int) $record->calc_total_bets : (int) ($betStats->get($record->user_id)?->total_bets ?? 0)),
                Tables\Columns\TextColumn::make('won_bets')
                    ->label('Thắng')
                    ->state(fn ($record) => isset($record->calc_won_bets) ? (int) $record->calc_won_bets : (int) ($betStats->get($record->user_id)?->won_bets ?? 0)),
                Tables\Columns\TextColumn::make('win_rate')
                    ->label('Win rate')
                    ->state(function ($record) use ($betStats) {
                        $settled = isset($record->calc_settled_bets) ? (int) $record->calc_settled_bets : (int) ($betStats->get($record->user_id)?->settled_bets ?? 0);
                        $won = isset($record->calc_won_bets) ? (int) $record->calc_won_bets : (int) ($betStats->get($record->user_id)?->won_bets ?? 0);
                        if ($settled == 0) return '–';
                        return round($won / $settled * 100, 1).'%';
                    }),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Cập nhật lúc')
                    ->dateTime('d/m H:i')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->modifyQueryUsing(fn (Builder $query) => $query->limit(10))
            ->paginated(false);
    }
}
