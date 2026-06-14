<?php

namespace App\Filament\Player\Livewire;

use App\Domain\Betting\Data\PlaceBetInput;
use App\Domain\Betting\Exceptions\BetPlacementException;
use App\Domain\Betting\Services\BetPlacementService;
use App\Models\MarketOutcome;
use App\Models\Season;
use App\Models\Wallet;
use App\Settings\AppSettings;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\On;
use Livewire\Component;

class PlaceBetModal extends Component
{
    public ?int $outcomeId = null;

    public bool $isOpen = false;

    public int $stake = 10;

    public int $minStake = 10;

    public int $maxStake = 200;

    public ?MarketOutcome $outcome = null;

    public ?Wallet $wallet = null;

    public int $availableBalance = 0;

    public function mount()
    {
        $this->refreshWallet();
    }

    public function refreshWallet()
    {
        $user = Auth::user();
        if ($user) {
            $activeSeason = Season::where('status', 'active')->first();
            if ($activeSeason) {
                $this->wallet = Wallet::where('user_id', $user->id)
                    ->where('season_id', $activeSeason->id)
                    ->first();
                $this->availableBalance = $this->wallet ? (int) $this->wallet->available_balance : 0;
            }
        }
    }

    #[On('openPlaceBetModal')]
    public function openModal(int $outcomeId)
    {
        $this->outcomeId = $outcomeId;
        $this->outcome = MarketOutcome::with('market.match')->find($outcomeId);

        $settings = app(AppSettings::class);
        $this->minStake = $settings->min_stake ?? 10;
        $this->maxStake = $settings->max_stake_per_bet ?? 200;

        $this->stake = $this->minStake;

        $this->refreshWallet();
        $this->isOpen = true;
    }

    public function closeModal()
    {
        $this->isOpen = false;
        $this->outcomeId = null;
        $this->outcome = null;
    }

    public function placeBet(BetPlacementService $betService)
    {
        $this->validate([
            'stake' => "required|integer|min:{$this->minStake}|max:{$this->maxStake}",
        ]);

        if (RateLimiter::tooManyAttempts('place-bet:' . Auth::id(), 30)) {
            Notification::make()
                ->title('Thao tác quá nhanh')
                ->body('Vui lòng thử lại sau.')
                ->danger()
                ->send();
            return;
        }
        RateLimiter::hit('place-bet:' . Auth::id());

        if (! $this->wallet) {
            Notification::make()
                ->title('Lỗi hệ thống')
                ->body('Không tìm thấy ví lá của bạn.')
                ->danger()
                ->send();

            return;
        }

        if (! $this->outcome || ! $this->outcome->market) {
            return;
        }

        try {
            $input = new PlaceBetInput(
                user: Auth::user(),
                wallet: $this->wallet,
                market: $this->outcome->market,
                outcome: $this->outcome,
                stake: $this->stake
            );

            $bet = $betService->placeBet($input);

            Notification::make()
                ->title('Đặt dự đoán thành công!')
                ->body("Mã phiếu: {$bet->public_code}")
                ->success()
                ->actions([
                    Action::make('view')
                        ->label('Xem phiếu của tôi')
                        ->url(route('filament.player.pages.my-bets-page')),
                    Action::make('close')
                        ->label('Quay lại trận')
                        ->close()
                        ->color('gray'),
                ])
                ->send();

            $this->closeModal();

            // Refresh parent page if needed
            $this->dispatch('betPlaced');

        } catch (BetPlacementException $e) {
            Notification::make()
                ->title('Lỗi đặt dự đoán')
                ->body($e->getMessage())
                ->danger()
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Lỗi không xác định')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function render()
    {
        return view('filament.player.livewire.place-bet-modal');
    }
}
