<?php

namespace App\Domain\Betting\Services;

use App\Domain\Betting\Data\PlaceBetInput;
use App\Domain\Betting\Exceptions\BetPlacementException;
use App\Domain\Wallet\Services\WalletService;
use App\Enums\BetStatus;
use App\Models\Bet;
use App\Models\MarketOutcome;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;

class EditBetService
{
    public function __construct(
        private readonly WalletService $walletService,
        private readonly BetPlacementService $betPlacementService
    ) {}

    /**
     * Sửa cược bằng cách Hủy vé cũ và Tạo vé mới.
     *
     * @throws BetPlacementException
     */
    public function editBet(Bet $oldBet, int $newOutcomeId, int $newStake): Bet
    {
        return DB::transaction(function () use ($oldBet, $newOutcomeId, $newStake) {
            // Lock vé cũ
            $oldBet = Bet::lockForUpdate()->findOrFail($oldBet->id);

            if ($oldBet->status->value !== BetStatus::PENDING->value) {
                throw new BetPlacementException(
                    'Chỉ có thể sửa phiếu đang chờ mở thưởng.',
                    'BET_NOT_PENDING'
                );
            }

            $market = $oldBet->market;
            if ($market->status !== 'OPEN' || now()->gte($market->close_at)) {
                throw new BetPlacementException(
                    "Kèo [{$market->name}] đã đóng, không thể sửa cược.",
                    'MARKET_CLOSED'
                );
            }

            $user = $oldBet->user;
            $wallet = $oldBet->wallet;
            $wallet = Wallet::lockForUpdate()->findOrFail($wallet->id);

            // 1. Hoàn tiền vé cũ (Void)
            $this->walletService->voidBet($wallet, $oldBet);

            $oldBet->status = BetStatus::VOIDED;
            $oldBet->voided_at = now();
            // Lưu log là bị hủy do sửa vé
            $metadata = $oldBet->metadata ?? [];
            $metadata['void_reason'] = 'EDITED_BY_USER';
            $oldBet->metadata = $metadata;
            $oldBet->save();

            // 2. Tạo vé mới
            $newOutcome = MarketOutcome::findOrFail($newOutcomeId);

            $input = new PlaceBetInput(
                user: $user,
                wallet: $wallet,
                market: $market,
                outcome: $newOutcome,
                stake: $newStake
            );

            // Tận dụng lại validation & logic của BetPlacementService
            $newBet = $this->betPlacementService->placeBet($input);

            // Lưu lại ID vé cũ vào metadata vé mới để tiện truy vết
            $newMetadata = $newBet->metadata ?? [];
            $newMetadata['edited_from_bet_id'] = $oldBet->id;
            $newBet->metadata = $newMetadata;
            $newBet->save();

            return $newBet;
        });
    }
}
