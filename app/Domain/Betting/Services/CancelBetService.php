<?php

namespace App\Domain\Betting\Services;

use App\Domain\Betting\Exceptions\BetPlacementException;
use App\Domain\Wallet\Services\WalletService;
use App\Enums\BetStatus;
use App\Models\Bet;
use App\Models\User;
use App\Models\Wallet;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CancelBetService
{
    private const MAX_CANCELS_PER_MATCH = 20;
    private const WARN_REMAINING_THRESHOLD = 5;
    private const CANCEL_COOLDOWN_SECONDS = 30;

    public function __construct(
        private readonly WalletService $walletService,
    ) {}

    /**
     * Huỷ vé dự đoán do user chủ động.
     *
     * Trả về array:
     *   'bet'          => Bet (đã VOIDED)
     *   'remaining'    => int (số lần huỷ còn lại cho trận này)
     *   'show_warning' => bool (true khi remaining <= WARN_REMAINING_THRESHOLD)
     *
     * @throws BetPlacementException
     */
    public function cancelBet(Bet $bet, User $user): array
    {
        return DB::transaction(function () use ($bet, $user) {
            // 1. Lock bet FOR UPDATE
            $bet = Bet::lockForUpdate()->findOrFail($bet->id);

            // 2. Verify ownership
            if ($bet->user_id !== $user->id) {
                throw new BetPlacementException(
                    'Bạn không có quyền huỷ phiếu này.',
                    'BET_NOT_OWNED'
                );
            }

            // 3. Verify status PENDING
            if ($bet->status->value !== BetStatus::PENDING->value) {
                throw new BetPlacementException(
                    'Chỉ có thể huỷ phiếu đang chờ mở thưởng.',
                    'BET_NOT_PENDING'
                );
            }

            // 4. Verify market OPEN + chưa hết giờ
            $market = $bet->market;
            if ($market->status !== 'OPEN' || now()->gte($market->close_at)) {
                throw new BetPlacementException(
                    "Kèo [{$market->name}] đã đóng, không thể huỷ.",
                    'MARKET_CLOSED'
                );
            }

            // 5. Cooldown 30s
            $lastCancelAt = Bet::where('user_id', $user->id)
                ->where('match_id', $bet->match_id)
                ->where('status', BetStatus::VOIDED)
                ->whereNotNull('voided_at')
                ->whereRaw("metadata->>'void_reason' = 'CANCELLED_BY_USER'")
                ->orderByDesc('voided_at')
                ->value('voided_at');

            if ($lastCancelAt) {
                $diffSeconds = (int) abs(Carbon::parse($lastCancelAt)->diffInSeconds(now()));
                if ($diffSeconds < self::CANCEL_COOLDOWN_SECONDS) {
                    $remaining = self::CANCEL_COOLDOWN_SECONDS - $diffSeconds;
                    throw new BetPlacementException(
                        "Thao tác quá nhanh, vui lòng thử lại sau {$remaining} giây.",
                        'CANCEL_COOLDOWN'
                    );
                }
            }

            // 6. Max cancels per match
            $cancelCount = Bet::where('user_id', $user->id)
                ->where('match_id', $bet->match_id)
                ->where('status', BetStatus::VOIDED)
                ->whereRaw("metadata->>'void_reason' = 'CANCELLED_BY_USER'")
                ->count();

            if ($cancelCount >= self::MAX_CANCELS_PER_MATCH) {
                throw new BetPlacementException(
                    'Bạn đã huỷ tối đa ' . self::MAX_CANCELS_PER_MATCH . ' phiếu cho trận này.',
                    'MAX_CANCELS_EXCEEDED'
                );
            }

            // 7. Lock wallet + void stake
            $wallet = Wallet::lockForUpdate()->findOrFail($bet->wallet_id);
            $this->walletService->voidBet($wallet, $bet);

            // 8. Update bet
            $bet->status = BetStatus::VOIDED;
            $bet->voided_at = now();
            $metadata = $bet->metadata ?? [];
            $metadata['void_reason'] = 'CANCELLED_BY_USER';
            $bet->metadata = $metadata;
            $bet->save();

            // 9. Tính remaining
            $remaining = self::MAX_CANCELS_PER_MATCH - ($cancelCount + 1);

            return [
                'bet'          => $bet,
                'remaining'    => $remaining,
                'show_warning' => $remaining <= self::WARN_REMAINING_THRESHOLD,
            ];
        });
    }
}
