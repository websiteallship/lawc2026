<?php

namespace App\Filament\Widgets;

use App\Models\Season;
use App\Models\Wallet;
use App\Models\Bet;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
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
                ->where('is_test_user', false)
                ->whereHas('roles', fn ($r) => $r->where('name', 'player'))
            )
            ->select([
                'wallets.*',
                DB::raw('(available_balance + locked_balance) as total_balance_live'),
            ]);

        // Static stats fallback (toàn mùa, không lọc ngày)
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
                SelectFilter::make('period')
                    ->label('Khoảng thời gian')
                    ->options([
                        'today'      => 'Hôm nay',
                        'yesterday'  => 'Hôm qua',
                        'this_week'  => 'Tuần này',
                        'last_week'  => 'Tuần trước',
                        'this_month' => 'Tháng này',
                        'last_30'    => '30 ngày qua',
                        'last_90'    => '90 ngày qua',
                        'all'        => 'Toàn mùa',
                    ])
                    ->default('all')
                    ->query(function (Builder $query, array $data): Builder {
                        $period = $data['value'] ?? 'all';

                        [$from, $to] = match ($period) {
                            'today'      => [Carbon::today(), Carbon::today()->endOfDay()],
                            'yesterday'  => [Carbon::yesterday(), Carbon::yesterday()->endOfDay()],
                            'this_week'  => [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()],
                            'last_week'  => [Carbon::now()->subWeek()->startOfWeek(), Carbon::now()->subWeek()->endOfWeek()],
                            'this_month' => [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()],
                            'last_30'    => [Carbon::now()->subDays(30)->startOfDay(), Carbon::now()->endOfDay()],
                            'last_90'    => [Carbon::now()->subDays(90)->startOfDay(), Carbon::now()->endOfDay()],
                            default      => [null, null], // 'all' – toàn mùa
                        };

                        $betSubquery = fn () => DB::table('bets')
                            ->whereColumn('wallet_id', 'wallets.id')
                            ->whereNotIn('status', ['PENDING', 'VOIDED'])
                            ->when($from, fn ($q) => $q->where('settled_at', '>=', $from))
                            ->when($to,   fn ($q) => $q->where('settled_at', '<=', $to));

                        $query->addSelect([
                            'period_profit' => (clone $betSubquery())->selectRaw('COALESCE(SUM(net_result), 0)'),
                            'calc_total_bets'   => (clone $betSubquery())->selectRaw('COUNT(*)'),
                            'calc_won_bets'     => (clone $betSubquery())->selectRaw('SUM(CASE WHEN status IN (\'WON\',\'HALF_WON\') THEN 1 ELSE 0 END)'),
                            'calc_settled_bets' => (clone $betSubquery())->selectRaw('COUNT(*)'),
                        ]);

                        // Khi lọc theo khoảng, sort theo period_profit thay vì net_profit
                        $query->getQuery()->orders = null;
                        $query->orderByDesc('period_profit');

                        return $query;
                    }),
            ])
            ->columns([
                Tables\Columns\TextColumn::make('rank')
                    ->label('#')
                    ->badge()
                    ->state(fn ($record, $rowLoop) => $rowLoop->iteration)
                    ->color(fn ($state): string => match (true) {
                        $state === 1 => 'warning',
                        $state <= 3  => 'success',
                        default      => 'gray',
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
                    ->state(fn ($record) => isset($record->calc_total_bets)
                        ? (int) $record->calc_total_bets
                        : (int) ($betStats->get($record->user_id)?->total_bets ?? 0)),
                Tables\Columns\TextColumn::make('won_bets')
                    ->label('Thắng')
                    ->state(fn ($record) => isset($record->calc_won_bets)
                        ? (int) $record->calc_won_bets
                        : (int) ($betStats->get($record->user_id)?->won_bets ?? 0)),
                Tables\Columns\TextColumn::make('win_rate')
                    ->label('Win rate')
                    ->state(function ($record) use ($betStats) {
                        $settled = isset($record->calc_settled_bets)
                            ? (int) $record->calc_settled_bets
                            : (int) ($betStats->get($record->user_id)?->settled_bets ?? 0);
                        $won = isset($record->calc_won_bets)
                            ? (int) $record->calc_won_bets
                            : (int) ($betStats->get($record->user_id)?->won_bets ?? 0);
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
