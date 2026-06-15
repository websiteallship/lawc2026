<?php

namespace App\Filament\Player\Pages;

use App\Enums\LedgerType;
use App\Models\Season;
use App\Models\Wallet;
use App\Models\WalletLedger;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

class WalletHistoryPage extends Page
{
    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-wallet';
    }

    protected static ?string $navigationLabel = 'Ví lá';

    protected static ?string $title = 'Ví lá của tôi';

    protected static ?int $navigationSort = 40;

    protected string $view = 'filament.player.pages.wallet-history';

    public ?Wallet $wallet = null;

    public string $filterType = 'all';

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

    public function getLedgers(): Collection
    {
        if (! $this->wallet) {
            return new Collection;
        }

        $query = WalletLedger::where('wallet_id', $this->wallet->id)
            ->orderByDesc('created_at');

        if ($this->filterType !== 'all') {
            $query->where('type', $this->filterType);
        }

        return $query->take(100)->get();
    }

    public function getLedgerTypeOptions(): array
    {
        $options = ['all' => 'Tất cả'];
        foreach (LedgerType::cases() as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
    }
}
