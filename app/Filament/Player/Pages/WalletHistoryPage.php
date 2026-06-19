<?php

namespace App\Filament\Player\Pages;

use App\Enums\LedgerType;
use App\Models\Season;
use App\Models\Wallet;
use App\Models\WalletLedger;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Filament\Actions\Action;
use Filament\Infolists\Infolist;
use App\Models\Bet;
use Livewire\WithPagination;

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

    public function updatedSearchQuery()
    {
        $this->resetPage();
    }

    public function updatedFilterType()
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

    public function getLedgers(): LengthAwarePaginator|Collection
    {
        if (! $this->wallet) {
            return new Collection;
        }

        $query = WalletLedger::where('wallet_id', $this->wallet->id)
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

        if ($this->filterType !== 'all') {
            $query->where('type', $this->filterType);
        }

        return $query->paginate(10);
    }

    public function getLedgerTypeOptions(): array
    {
        $options = ['all' => 'Tất cả'];
        foreach (LedgerType::cases() as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
    }

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
