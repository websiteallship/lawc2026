<?php

namespace App\Filament\Player\Pages;

use App\Enums\BetStatus;
use App\Models\Bet;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\WithPagination;

class MyBetsPage extends Page
{
    use WithPagination;

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-ticket';
    }

    protected static ?string $navigationLabel = 'Phiếu của tôi';

    protected static ?string $title = 'Phiếu dự đoán của tôi';

    protected static ?int $navigationSort = 30;

    public static function getNavigationGroup(): ?string
    {
        return 'Quản lý cá nhân';
    }

    protected string $view = 'filament.player.pages.my-bets-page';

    public string $activeTab = 'all';

    public string $searchQuery = '';

    public function updatedActiveTab()
    {
        $this->resetPage();
    }

    public function updatedSearchQuery()
    {
        $this->resetPage();
    }

    #[Computed]
    public function bets()
    {
        $query = Bet::with(['market.match', 'outcome'])
            ->where('user_id', Auth::id())
            ->orderByDesc('created_at');

        if ($this->searchQuery) {
            $query->where(function ($q) {
                $q->where('public_code', 'like', '%' . $this->searchQuery . '%')
                  ->orWhereHas('market.match', function ($matchQuery) {
                      $matchQuery->where('home_team', 'like', '%' . $this->searchQuery . '%')
                                 ->orWhere('away_team', 'like', '%' . $this->searchQuery . '%');
                  });
            });
        }

        if ($this->activeTab !== 'all') {
            if ($this->activeTab === 'pending') {
                $query->where('status', BetStatus::PENDING);
            } elseif ($this->activeTab === 'settled') {
                $query->whereIn('status', [
                    BetStatus::WON,
                    BetStatus::LOST,
                    BetStatus::PUSH,
                    BetStatus::HALF_WON,
                    BetStatus::HALF_LOST,
                ]);
            } elseif ($this->activeTab === 'voided') {
                $query->where('status', BetStatus::VOIDED);
            } elseif ($this->activeTab === 'corrected') {
                $query->where('status', BetStatus::CORRECTED);
            }
        }

        return $query->paginate(10);
    }
}
