<?php

namespace App\Filament\Player\Livewire;

use App\Domain\Betting\Exceptions\BetPlacementException;
use App\Domain\Betting\Services\CancelBetService;
use App\Models\Bet;
use App\Models\Market;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;

class CancelBetModal extends Component
{
    public ?int $betId = null;
    public bool $isOpen = false;
    public bool $isMarketLocked = false;

    public ?Bet $bet = null;
    public ?Market $market = null;

    // Số lần huỷ còn lại cho trận này (load khi mở modal)
    public int $remainingCancels = 20;
    public bool $showRemainingWarning = false;

    #[On('openCancelBetModal')]
    public function openModal(int $betId): void
    {
        $this->betId = $betId;
        $this->bet = Bet::with(['market.match', 'outcome'])->find($betId);

        if (! $this->bet || $this->bet->status->value !== 'PENDING') {
            Notification::make()
                ->title('Phiếu không hợp lệ hoặc đã chốt.')
                ->danger()
                ->send();
            return;
        }

        // Kiểm tra ownership
        if ($this->bet->user_id !== Auth::id()) {
            Notification::make()
                ->title('Bạn không có quyền huỷ phiếu này.')
                ->danger()
                ->send();
            return;
        }

        $this->market = $this->bet->market;
        $this->checkMarketStatus();

        if ($this->isMarketLocked) {
            return;
        }

        // Tính số lần huỷ còn lại
        $this->loadRemainingCancels();

        $this->isOpen = true;
    }

    public function closeModal(): void
    {
        $this->isOpen = false;
        $this->betId = null;
        $this->bet = null;
        $this->market = null;
        $this->isMarketLocked = false;
    }

    public function checkMarketStatus(): void
    {
        if ($this->market) {
            $this->market->refresh();
            if ($this->market->status !== 'OPEN' || now()->gte($this->market->close_at)) {
                $this->isMarketLocked = true;
                if ($this->isOpen) {
                    Notification::make()
                        ->title('Kèo đã đóng!')
                        ->body('Bạn không thể huỷ vé vì kèo đã bị khoá.')
                        ->warning()
                        ->send();
                    $this->closeModal();
                    $this->dispatch('betPlaced');
                }
            }
        }
    }

    public function confirmCancel(CancelBetService $cancelService): void
    {
        if (! $this->bet || ! $this->market) {
            return;
        }

        $this->checkMarketStatus();
        if ($this->isMarketLocked) {
            return;
        }

        try {
            $result = $cancelService->cancelBet($this->bet, Auth::user());

            if ($result['show_warning']) {
                Notification::make()
                    ->title('Huỷ thành công!')
                    ->body("Bạn còn {$result['remaining']} lần huỷ cho trận này.")
                    ->warning()
                    ->send();
            } else {
                Notification::make()
                    ->title('Đã huỷ phiếu thành công.')
                    ->body("Số lá đã được hoàn lại vào ví.")
                    ->success()
                    ->send();
            }

            $this->closeModal();
            $this->dispatch('betPlaced');

        } catch (BetPlacementException $e) {
            Notification::make()
                ->title('Không thể huỷ phiếu')
                ->body($e->getMessage())
                ->danger()
                ->send();

            // Nếu cooldown → không đóng modal, user có thể đợi
            if ($e->getCode() === 0) {
                // Reload remaining
                $this->loadRemainingCancels();
            }
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Lỗi hệ thống')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    private function loadRemainingCancels(): void
    {
        $cancelCount = Bet::where('user_id', Auth::id())
            ->where('match_id', $this->bet->match_id)
            ->where('status', \App\Enums\BetStatus::VOIDED)
            ->whereRaw("metadata->>'void_reason' = 'CANCELLED_BY_USER'")
            ->count();

        $this->remainingCancels = max(0, 20 - $cancelCount);
        $this->showRemainingWarning = $this->remainingCancels <= 5;
    }

    public function render(): \Illuminate\View\View
    {
        return view('filament.player.livewire.cancel-bet-modal');
    }
}
