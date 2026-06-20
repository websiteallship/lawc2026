<?php

namespace App\Filament\Pages;

use App\Models\Bet;
use App\Models\Season;
use App\Models\Wallet;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;

class AdminLeaderboardPage extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-trophy';

    protected static string|\BackedEnum|null $activeNavigationIcon = 'heroicon-s-trophy';

    protected static ?string $navigationLabel = 'Bảng Xếp Hạng';

    protected static ?string $title = 'Bảng Xếp Hạng Người Chơi';

    protected static ?int $navigationSort = 1; // Ngay sau Dashboard (sort=0)

    public function getView(): string
    {
        return 'filament.admin.pages.admin-leaderboard-page';
    }

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
            ->select('wallets.*');

        // Tổng hợp stats từ bet đã đóng (không tính PENDING/VOIDED)
        $betStats = Bet::whereNotIn('status', ['PENDING', 'VOIDED'])
            ->selectRaw("
                user_id,
                COUNT(*) as total_bets,
                SUM(CASE WHEN status IN ('WON','HALF_WON') THEN 1 ELSE 0 END) as won_bets,
                SUM(CASE WHEN status = 'LOST' THEN 1 ELSE 0 END) as lost_bets,
                SUM(CASE WHEN status IN ('PUSH','HALF_WON','HALF_LOST') THEN 1 ELSE 0 END) as push_bets,
                COUNT(*) as settled_bets,
                SUM(stake) as total_staked_db,
                SUM(gross_payout) as total_payout_db
            ")
            ->groupBy('user_id')
            ->get()
            ->keyBy('user_id');

        return $table
            ->query($query)
            ->columns([
                TextColumn::make('rank')
                    ->label('#')
                    ->badge()
                    ->state(fn ($record, $rowLoop) => $rowLoop->iteration)
                    ->color(fn ($state): string => match (true) {
                        $state === 1 => 'warning',
                        $state <= 3 => 'success',
                        default => 'gray',
                    }),

                TextColumn::make('user.name')
                    ->label('Người chơi')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('total_balance')
                    ->label('Tổng Lá')
                    ->state(fn ($record) => $record->available_balance + $record->locked_balance)
                    ->numeric(thousandsSeparator: ',')
                    ->sortable(false)
                    ->color('primary'),

                TextColumn::make('available_balance')
                    ->label('Lá khả dụng')
                    ->numeric(thousandsSeparator: ',')
                    ->sortable(false)
                    ->toggleable(),

                TextColumn::make('locked_balance')
                    ->label('Đang khóa')
                    ->numeric(thousandsSeparator: ',')
                    ->sortable(false)
                    ->color('warning')
                    ->toggleable(),

                TextColumn::make('net_profit')
                    ->label('Lãi / Lỗ')
                    ->sortable()
                    ->color(fn ($state) => $state > 0 ? 'success' : ($state < 0 ? 'danger' : 'gray'))
                    ->weight(\Filament\Support\Enums\FontWeight::Bold)
                    ->formatStateUsing(fn ($state) => ($state >= 0 ? '+' : '') . number_format($state) . ' lá'),

                TextColumn::make('total_staked')
                    ->label('Tổng đã cược')
                    ->numeric(thousandsSeparator: ',')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('total_payout')
                    ->label('Tổng payout')
                    ->numeric(thousandsSeparator: ',')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('total_bets')
                    ->label('Số phiếu')
                    ->state(function ($record) use ($betStats) {
                        return (int) ($betStats->get($record->user_id)?->total_bets ?? 0);
                    }),

                TextColumn::make('won_bets')
                    ->label('Thắng')
                    ->state(function ($record) use ($betStats) {
                        return (int) ($betStats->get($record->user_id)?->won_bets ?? 0);
                    })
                    ->color('success'),

                TextColumn::make('lost_bets')
                    ->label('Thua')
                    ->state(function ($record) use ($betStats) {
                        return (int) ($betStats->get($record->user_id)?->lost_bets ?? 0);
                    })
                    ->color('danger'),

                TextColumn::make('push_bets')
                    ->label('Hòa/Hoàn')
                    ->state(function ($record) use ($betStats) {
                        return (int) ($betStats->get($record->user_id)?->push_bets ?? 0);
                    })
                    ->color('gray')
                    ->toggleable(),

                TextColumn::make('win_rate')
                    ->label('Win rate')
                    ->state(function ($record) use ($betStats) {
                        $s = $betStats->get($record->user_id);
                        if (! $s || $s->settled_bets == 0) {
                            return '–';
                        }

                        return round($s->won_bets / $s->settled_bets * 100, 1) . '%';
                    }),

                TextColumn::make('roi')
                    ->label('ROI')
                    ->state(function ($record) use ($betStats) {
                        $s = $betStats->get($record->user_id);
                        if (! $s || $s->total_staked_db == 0) {
                            return '–';
                        }
                        $roi = (($s->total_payout_db - $s->total_staked_db) / $s->total_staked_db) * 100;

                        return ($roi >= 0 ? '+' : '') . round($roi, 1) . '%';
                    })
                    ->color(function ($record) use ($betStats) {
                        $s = $betStats->get($record->user_id);
                        if (! $s || $s->total_staked_db == 0) {
                            return 'gray';
                        }
                        $roi = $s->total_payout_db - $s->total_staked_db;

                        return $roi > 0 ? 'success' : ($roi < 0 ? 'danger' : 'gray');
                    }),

                TextColumn::make('updated_at')
                    ->label('Cập nhật lúc')
                    ->dateTime('d/m H:i')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('net_profit', 'desc')
            ->striped()
            ->paginated([10, 25, 50, 100]);
    }
}
