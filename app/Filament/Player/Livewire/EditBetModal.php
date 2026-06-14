<?php

namespace App\Filament\Player\Livewire;

use App\Domain\Betting\Exceptions\BetPlacementException;
use App\Domain\Betting\Services\EditBetService;
use App\Models\Bet;
use App\Models\Market;
use App\Models\Season;
use App\Models\Wallet;
use App\Settings\AppSettings;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;

class EditBetModal extends Component
{
    public ?int $betId = null;

    public bool $isOpen = false;

    public bool $isMarketLocked = false;

    public int $stake = 10;

    public int $minStake = 10;

    public int $maxStake = 200;

    public ?Bet $oldBet = null;

    public ?Market $market = null;

    public $outcomes = [];

    public ?int $selectedOutcomeId = null;

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

    #[On('openEditBetModal')]
    public function openModal(int $betId)
    {
        $this->betId = $betId;
        $this->oldBet = Bet::with(['market.outcomes', 'market.match', 'outcome'])->find($betId);

        if (! $this->oldBet || $this->oldBet->status->value !== 'PENDING') {
            Notification::make()->title('Lỗi')->body('Phiếu không hợp lệ hoặc đã chốt.')->danger()->send();

            return;
        }

        $this->market = $this->oldBet->market;
        $this->outcomes = $this->market->outcomes->where('status', 'ACTIVE');
        $this->selectedOutcomeId = $this->oldBet->outcome_id;
        $this->stake = $this->oldBet->stake;

        $settings = app(AppSettings::class);
        $this->minStake = $settings->min_stake ?? 10;
        $this->maxStake = $settings->max_stake_per_bet ?? 200;

        $this->refreshWallet();
        $this->checkMarketStatus();

        if (! $this->isMarketLocked) {
            $this->isOpen = true;
        }
    }

    public function closeModal()
    {
        $this->isOpen = false;
        $this->betId = null;
        $this->oldBet = null;
        $this->market = null;
        $this->outcomes = [];
        $this->isMarketLocked = false;
    }

    public function checkMarketStatus()
    {
        if ($this->market) {
            $this->market->refresh();
            if ($this->market->status !== 'OPEN' || now()->gte($this->market->close_at)) {
                $this->isMarketLocked = true;
                if ($this->isOpen) {
                    Notification::make()
                        ->title('Kèo đã đóng!')
                        ->body('Bạn không thể chỉnh sửa vé cược lúc này vì trận đấu đã bắt đầu hoặc kèo đã bị khóa.')
                        ->warning()
                        ->send();
                    $this->closeModal();
                    $this->dispatch('betPlaced'); // Trigger reload of bets list to hide edit button
                }
            }
        }
    }

    public function selectOutcome(int $outcomeId)
    {
        $this->selectedOutcomeId = $outcomeId;
    }

    public function updateBet(EditBetService $editService)
    {
        $this->validate([
            'stake' => "required|integer|min:{$this->minStake}|max:{$this->maxStake}",
            'selectedOutcomeId' => 'required|exists:market_outcomes,id',
        ]);

        $this->checkMarketStatus();
        if ($this->isMarketLocked) {
            return;
        }

        if (! $this->wallet) {
            Notification::make()->title('Lỗi hệ thống')->body('Không tìm thấy ví lá của bạn.')->danger()->send();

            return;
        }

        // Available balance logic when editing: We get our old stake back!
        // So effective balance = available_balance + old_stake
        $effectiveBalance = $this->availableBalance + $this->oldBet->stake;
        if ($this->stake > $effectiveBalance) {
            Notification::make()->title('Lỗi')->body('Số dư không đủ!')->danger()->send();

            return;
        }

        try {
            $newBet = $editService->editBet($this->oldBet, $this->selectedOutcomeId, $this->stake);

            Notification::make()
                ->title('Sửa dự đoán thành công!')
                ->body("Mã phiếu mới: {$newBet->public_code}")
                ->success()
                ->send();

            $this->closeModal();
            $this->dispatch('betPlaced');

        } catch (BetPlacementException $e) {
            Notification::make()->title('Lỗi sửa dự đoán')->body($e->getMessage())->danger()->send();
            $this->checkMarketStatus(); // Might be locked
        } catch (\Exception $e) {
            Notification::make()->title('Lỗi không xác định')->body($e->getMessage())->danger()->send();
        }
    }

    public function render()
    {
        // Calculate max allowed stake for the UI
        $effectiveBalance = 0;
        if ($this->wallet && $this->oldBet) {
            $effectiveBalance = $this->availableBalance + $this->oldBet->stake;
        }

        return view('filament.player.livewire.edit-bet-modal', [
            'effectiveBalance' => $effectiveBalance,
        ]);
    }
}
