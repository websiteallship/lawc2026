<?php

namespace App\Filament\Player\Pages;

use App\Enums\LedgerType;
use App\Filament\Player\Widgets\PlayerWalletFlowChart;
use App\Models\Bet;
use App\Models\Season;
use App\Models\Wallet;
use App\Models\WalletLedger;
use App\Support\DatePeriodFilter;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\StreamedResponse;

class WalletHistoryPage extends Page
{
    use WithPagination;

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-wallet';
    }

    protected static ?string $navigationLabel = 'Ví lá';

    protected static ?string $title = 'Ví lá của tôi';

    protected static ?int $navigationSort = 40;

    public static function getNavigationGroup(): ?string
    {
        return 'Quản lý cá nhân';
    }

    protected string $view = 'filament.player.pages.wallet-history';

    public ?Wallet $wallet = null;

    public string $filterType = 'all';

    public string $searchQuery = '';

    /** @var string Flow period for stat cards & reconciliation — see DatePeriodFilter::options() */
    public string $flowPeriod = 'this_week';

    // Advanced filters
    public string $filterPeriod = 'this_week'; // all, or keys from DatePeriodFilter::options()
    public string $filterTypeGroup = 'all'; // all, bet_placed, bet_win, bet_lose, bet_refund, admin_adj
    public string $filterAmount = 'all'; // all, 1k, 10k, 100k

    public function updatedSearchQuery(): void
    {
        $this->resetPage();
    }

    public function updatedFilterType(): void
    {
        $this->resetPage();
    }

    public function updatedFlowPeriod(): void
    {
        // Triggers reactive recompute for stat cards & reconciliation
    }

    public function updatedFilterPeriod(): void
    {
        $this->resetPage();
    }

    public function updatedFilterTypeGroup(): void
    {
        $this->resetPage();
    }

    public function updatedFilterAmount(): void
    {
        $this->resetPage();
    }

    public function mount(): void
    {
        $user = Auth::user();
        $activeSeason = Season::where('status', 'active')->first();

        if ($activeSeason) {
            $this->wallet = Wallet::where('user_id', $user->id)
                ->where('season_id', $activeSeason->id)
                ->first();
        }
    }

    protected function getHeaderWidgets(): array
    {
        return [
            PlayerWalletFlowChart::class,
        ];
    }

    // ──────────────────────────────────────────────────────────
    // LEDGER LIST & QUERY BUILDER
    // ──────────────────────────────────────────────────────────

    private function buildLedgerQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = WalletLedger::with(['bet.market.match'])
            ->where('wallet_id', $this->wallet->id)
            ->orderByDesc('created_at');

        if ($this->searchQuery) {
            $query->where(function ($q) {
                $q->where('reason', 'like', '%' . $this->searchQuery . '%')
                  ->orWhereHas('bet', function ($betQuery) {
                      $betQuery->where('public_code', 'like', '%' . $this->searchQuery . '%')
                               ->orWhereHas('market.match', function ($matchQuery) {
                                   $matchQuery->where('home_team', 'like', '%' . $this->searchQuery . '%')
                                              ->orWhere('away_team', 'like', '%' . $this->searchQuery . '%');
                               });
                  });
            });
        }

        // Filter Type (backward compatibility / simple toggle)
        if ($this->filterType !== 'all') {
            $query->where('type', $this->filterType);
        }

        // Filter Period
        if ($this->filterPeriod !== 'all') {
            [$start, $end] = DatePeriodFilter::resolve($this->filterPeriod);
            $query->where('created_at', '>=', $start)
                  ->where('created_at', '<=', $end);
        }

        // Filter Type Group
        if ($this->filterTypeGroup !== 'all') {
            switch ($this->filterTypeGroup) {
                case 'bet_placed':
                    $query->where('type', LedgerType::BET_PLACED);
                    break;
                case 'bet_win':
                    $query->whereIn('type', [LedgerType::BET_WON, LedgerType::BET_HALF_WON]);
                    break;
                case 'bet_lose':
                    $query->whereIn('type', [LedgerType::BET_LOST, LedgerType::BET_HALF_LOST]);
                    break;
                case 'bet_refund':
                    $query->whereIn('type', [LedgerType::BET_PUSH, LedgerType::BET_VOIDED]);
                    break;
                case 'admin_adj':
                    $query->whereIn('type', [LedgerType::ADMIN_GRANT, LedgerType::ADMIN_DEDUCT, LedgerType::SETTLEMENT_CORRECTION]);
                    break;
            }
        }

        // Filter Amount
        if ($this->filterAmount !== 'all') {
            switch ($this->filterAmount) {
                case '1k':
                    $query->where(function ($q) {
                        $q->where(DB::raw('abs(amount_available)'), '>=', 1000)
                          ->orWhere(DB::raw('abs(amount_locked)'), '>=', 1000);
                    });
                    break;
                case '10k':
                    $query->where(function ($q) {
                        $q->where(DB::raw('abs(amount_available)'), '>=', 10000)
                          ->orWhere(DB::raw('abs(amount_locked)'), '>=', 10000);
                    });
                    break;
                case '100k':
                    $query->where(function ($q) {
                        $q->where(DB::raw('abs(amount_available)'), '>=', 100000)
                          ->orWhere(DB::raw('abs(amount_locked)'), '>=', 100000);
                    });
                    break;
            }
        }

        return $query;
    }

    public function getLedgers(): LengthAwarePaginator|Collection
    {
        if (! $this->wallet) {
            return new Collection;
        }

        return $this->buildLedgerQuery()->paginate(10);
    }

    public function getLedgerTypeOptions(): array
    {
        $options = ['all' => 'Tất cả'];
        foreach (LedgerType::cases() as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
    }

    // ──────────────────────────────────────────────────────────
    // FLOW STATS (stat cards hàng 2)
    // ──────────────────────────────────────────────────────────

    public function getFlowStats(): array
    {
        if (! $this->wallet) {
            return ['received' => 0, 'spent' => 0, 'net' => 0];
        }

        [$start, $end] = DatePeriodFilter::resolve($this->flowPeriod);

        $result = WalletLedger::where('wallet_id', $this->wallet->id)
            ->where('created_at', '>=', $start)
            ->where('created_at', '<=', $end)
            ->selectRaw(
                'SUM(CASE WHEN (amount_available + amount_locked) > 0 THEN (amount_available + amount_locked) ELSE 0 END) as received,
                 SUM(CASE WHEN (amount_available + amount_locked) < 0 THEN ABS(amount_available + amount_locked) ELSE 0 END) as spent'
            )->first();

        $received = (int) ($result->received ?? 0);
        $spent    = (int) ($result->spent ?? 0);

        return [
            'received' => $received,
            'spent'    => $spent,
            'net'      => $received - $spent,
        ];
    }

    // ──────────────────────────────────────────────────────────
    // RECONCILIATION TABLE (bảng đối soát kế toán)
    // ──────────────────────────────────────────────────────────

    public function getReconciliation(): array
    {
        if (! $this->wallet) {
            return ['rows' => [], 'total_received' => 0, 'total_spent' => 0, 'opening_balance' => 0, 'closing_balance' => 0];
        }

        [$start, $end] = DatePeriodFilter::resolve($this->flowPeriod);

        $rows = WalletLedger::where('wallet_id', $this->wallet->id)
            ->where('created_at', '>=', $start)
            ->where('created_at', '<=', $end)
            ->select(
                'type',
                DB::raw('SUM(CASE WHEN (amount_available + amount_locked) > 0 THEN (amount_available + amount_locked) ELSE 0 END) as received'),
                DB::raw('SUM(CASE WHEN (amount_available + amount_locked) < 0 THEN ABS(amount_available + amount_locked) ELSE 0 END) as spent')
            )
            ->groupBy('type')
            ->orderBy('type')
            ->get()
            ->map(function ($row) {
                $type = LedgerType::tryFrom($row->type instanceof LedgerType ? $row->type->value : $row->type);
                return [
                    'label'    => $type ? $type->label() : $row->type,
                    'received' => (int) $row->received,
                    'spent'    => (int) $row->spent,
                    'net'      => (int) $row->received - (int) $row->spent,
                ];
            })
            ->filter(fn ($row) => $row['received'] > 0 || $row['spent'] > 0)
            ->values()
            ->toArray();

        $totalReceived = array_sum(array_column($rows, 'received'));
        $totalSpent    = array_sum(array_column($rows, 'spent'));

        // Opening balance = earliest ledger in period balance_available_after - amount_available
        $earliest = WalletLedger::where('wallet_id', $this->wallet->id)
            ->where('created_at', '>=', $start)
            ->where('created_at', '<=', $end)
            ->orderBy('created_at')
            ->first();

        $openingBalance = $earliest
            ? (($earliest->balance_available_after + $earliest->balance_locked_after) - ($earliest->amount_available + $earliest->amount_locked))
            : $this->wallet->total_balance;

        $closingBalance = $this->wallet->total_balance;

        return [
            'rows'            => $rows,
            'total_received'  => $totalReceived,
            'total_spent'     => $totalSpent,
            'total_net'       => $totalReceived - $totalSpent,
            'opening_balance' => $openingBalance,
            'closing_balance' => $closingBalance,
        ];
    }

    // ──────────────────────────────────────────────────────────
    // EXPORT CSV
    // ──────────────────────────────────────────────────────────

    public function exportCsv(): StreamedResponse
    {
        $user   = Auth::user();
        $wallet = $this->wallet;

        $filename = 'doi-soat-vi-la-' . now()->setTimezone('Asia/Ho_Chi_Minh')->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($wallet, $user) {
            $handle = fopen('php://output', 'w');

            // UTF-8 BOM for Excel
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            // ── Header info
            fputcsv($handle, ['BÁO CÁO ĐỐI SOÁT VÍ LÁ']);
            fputcsv($handle, ['Người dùng:', $user->username ?? $user->email]);
            fputcsv($handle, ['Thời gian xuất:', now()->setTimezone('Asia/Ho_Chi_Minh')->format('d/m/Y H:i:s')]);
            fputcsv($handle, ['Kỳ báo cáo:', match($this->flowPeriod) {
                '7'   => '7 ngày qua',
                '30'  => '30 ngày qua',
                default => 'Toàn mùa giải',
            }]);
            fputcsv($handle, []);

            // ── Reconciliation summary
            $recon = $this->getReconciliation();
            fputcsv($handle, ['BẢNG ĐỐI SOÁT']);
            fputcsv($handle, ['Loại giao dịch', 'Nhận vào (+)', 'Chi ra (-)', 'Ròng']);
            foreach ($recon['rows'] as $row) {
                fputcsv($handle, [
                    $row['label'],
                    $row['received'],
                    $row['spent'],
                    $row['net'],
                ]);
            }
            fputcsv($handle, ['TỔNG CỘNG', $recon['total_received'], $recon['total_spent'], $recon['total_net']]);
            fputcsv($handle, []);
            fputcsv($handle, ['Số dư đầu kỳ', '', '', $recon['opening_balance']]);
            fputcsv($handle, ['Số dư cuối kỳ', '', '', $recon['closing_balance']]);
            fputcsv($handle, []);

            // ── Detail ledger
            fputcsv($handle, ['CHI TIẾT GIAO DỊCH']);
            fputcsv($handle, ['Ngày giờ', 'Loại', 'Mã phiếu', 'Trận đấu', 'Lý do', 'Nhận vào (+)', 'Chi ra (-)', 'Số dư sau']);

            if ($wallet) {
                $query = $this->buildLedgerQuery();

                $query->chunk(200, function ($ledgers) use ($handle) {
                    foreach ($ledgers as $ledger) {
                        $match = $ledger->bet?->market?->match;
                        $matchName = $match
                            ? ($match->home_team . ' vs ' . $match->away_team)
                            : '';

                        $netChange = $ledger->amount_available + $ledger->amount_locked;

                        fputcsv($handle, [
                            $ledger->created_at?->setTimezone('Asia/Ho_Chi_Minh')->format('d/m/Y H:i'),
                            $ledger->type instanceof LedgerType ? $ledger->type->label() : $ledger->type,
                            $ledger->bet?->public_code ?? '',
                            $matchName,
                            $ledger->reason ?? '',
                            $netChange > 0 ? $netChange : 0,
                            $netChange < 0 ? abs($netChange) : 0,
                            $ledger->balance_available_after + $ledger->balance_locked_after,
                        ]);
                    }
                });
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    // ──────────────────────────────────────────────────────────
    // ACTIONS
    // ──────────────────────────────────────────────────────────

    public function viewBetAction(): Action
    {
        return Action::make('viewBet')
            ->label('Xem phiếu')
            ->icon('heroicon-m-eye')
            ->color('gray')
            ->modalHeading('Chi tiết phiếu dự đoán')
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Đóng')
            ->modalContent(function (array $arguments) {
                $bet = Bet::with('market.match')->find($arguments['bet_id'] ?? null);

                if (! $bet) {
                    return null;
                }

                return view('filament.player.components.bet-card', ['bet' => $bet]);
            });
    }
}
