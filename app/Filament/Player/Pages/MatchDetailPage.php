<?php

namespace App\Filament\Player\Pages;

use App\Models\Bet;
use App\Models\FootballMatch;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;

class MatchDetailPage extends Page
{
    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-document-text';
    }

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'match/{record}';

    protected string $view = 'filament.player.pages.match-detail';

    public FootballMatch $match;

    public string $activePeriod = 'FULL_TIME';

    public function mount(int $record): void
    {
        $this->match = FootballMatch::with(['season', 'events', 'periodResults', 'markets' => function ($q) {
            $q->whereIn('status', ['OPEN', 'LOCKED', 'SETTLED', 'VOIDED'])->orderBy('display_order');
        }, 'markets.outcomes' => function ($q) {
            $q->where('status', 'ACTIVE')->orderBy('display_order');
        }])->findOrFail($record);

    }

    #[Computed]
    public function marketsByType(): Collection
    {
        // Livewire rehydrates the model but loses nested relationships (markets.outcomes)
        if (! $this->match->relationLoaded('markets') || ($this->match->markets->isNotEmpty() && ! $this->match->markets->first()->relationLoaded('outcomes'))) {
            $this->match->load(['markets' => function ($q) {
                $q->whereIn('status', ['OPEN', 'LOCKED', 'SETTLED', 'VOIDED'])->orderBy('display_order');
            }, 'markets.outcomes' => function ($q) {
                $q->where('status', 'ACTIVE')->orderBy('display_order');
            }]);
        }

        return $this->match->markets
            ->where('period_type', $this->activePeriod)
            ->groupBy('market_type');
    }

    #[Computed]
    public function availableBalance(): int
    {
        // Assuming user has a wallet relation
        $user = auth()->user();
        if ($user && $user->wallet) {
            return (int) $user->wallet->available_balance;
        }

        return 0;
    }

    #[Computed]
    #[On('betPlaced')]
    public function myBets(): Collection
    {
        $user = auth()->user();
        if (! $user) {
            return collect();
        }

        return Bet::with('market')
            ->where('user_id', $user->id)
            ->where('match_id', $this->match->id)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function selectOutcome(int $outcomeId): void
    {
        $this->dispatch('openPlaceBetModal', $outcomeId);
    }
}
