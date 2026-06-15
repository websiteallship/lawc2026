<?php

namespace App\Domain\Wallet\Services;

use App\Domain\Audit\Services\AuditLogService;
use App\Domain\Wallet\Exceptions\InsufficientBalanceException;
use App\Domain\Wallet\Exceptions\NegativeBalanceException;
use App\Enums\BetStatus;
use App\Enums\LedgerType;
use App\Models\Bet;
use App\Models\Season;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletLedger;
use Illuminate\Support\Facades\DB;

class WalletService
{
    public function __construct(
        private readonly AuditLogService $auditLog,
    ) {}

    /**
     * Tạo ví cho user/season. Idempotent.
     */
    public function createWallet(User $user, Season $season, ?int $startingLeaves = null): Wallet
    {
        $leaves = $startingLeaves ?? $season->default_starting_leaves;

        return DB::transaction(function () use ($user, $season, $leaves) {
            $wallet = Wallet::firstOrCreate(
                ['user_id' => $user->id, 'season_id' => $season->id],
                ['available_balance' => 0, 'locked_balance' => 0, 'status' => 'ACTIVE']
            );

            if ($wallet->wasRecentlyCreated && $leaves > 0) {
                $this->grant($wallet, $leaves, null, 'Lá khởi đầu mùa giải');
            }

            return $wallet->fresh();
        });
    }

    /**
     * Admin cấp lá. Tạo ledger ADMIN_GRANT.
     */
    public function grant(Wallet $wallet, int $amount, ?User $actor, string $reason = ''): WalletLedger
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException("Số lá cấp phải > 0. Nhận được: {$amount}.");
        }

        return DB::transaction(function () use ($wallet, $amount, $actor, $reason) {
            $wallet = Wallet::lockForUpdate()->findOrFail($wallet->id);

            $wallet->available_balance += $amount;
            $wallet->save();

            $ledger = $this->createLedger($wallet, LedgerType::ADMIN_GRANT, $amount, 0, $actor, $reason);

            $this->auditLog->logWalletChange('WALLET_GRANTED', $wallet, $amount, $reason, $actor);

            app(\App\Domain\Notification\Services\NotificationService::class)->notifyWalletGranted($wallet->user, $ledger);

            return $ledger;
        });
    }

    /**
     * Admin trừ lá. Tạo ledger ADMIN_DEDUCT.
     */
    public function deduct(Wallet $wallet, int $amount, ?User $actor, string $reason = ''): WalletLedger
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException("Số lá trừ phải > 0. Nhận được: {$amount}.");
        }

        return DB::transaction(function () use ($wallet, $amount, $actor, $reason) {
            $wallet = Wallet::lockForUpdate()->findOrFail($wallet->id);

            if ($wallet->available_balance < $amount) {
                throw new InsufficientBalanceException($wallet->available_balance, $amount);
            }

            $wallet->available_balance -= $amount;
            $wallet->save();

            $ledger = $this->createLedger($wallet, LedgerType::ADMIN_DEDUCT, -$amount, 0, $actor, $reason);

            $this->auditLog->logWalletChange('WALLET_DEDUCTED', $wallet, $amount, $reason, $actor);

            return $ledger;
        });
    }

    /**
     * Khóa stake khi đặt dự đoán. available -= stake, locked += stake.
     * Tạo ledger BET_PLACED.
     */
    public function lockStake(Wallet $wallet, int $stake, Bet $bet): WalletLedger
    {
        if ($stake <= 0) {
            throw new \InvalidArgumentException("Stake phải > 0. Nhận được: {$stake}.");
        }

        // Gọi trong transaction đã mở từ BetPlacementService, wallet đã lockForUpdate
        if ($wallet->available_balance < $stake) {
            throw new InsufficientBalanceException($wallet->available_balance, $stake);
        }

        $wallet->available_balance -= $stake;
        $wallet->locked_balance += $stake;
        $wallet->total_staked += $stake;
        $wallet->save();

        $ledger = $this->createLedger($wallet, LedgerType::BET_PLACED, -$stake, $stake, null, "Đặt dự đoán #{$bet->public_code}");
        $ledger->bet_id = $bet->id;
        $ledger->save();

        return $ledger;
    }

    /**
     * Settle bet: giải phóng locked và cộng payout vào available.
     */
    public function settleBet(Wallet $wallet, Bet $bet, int $grossPayout, BetStatus $status): WalletLedger
    {
        $stake = $bet->stake;

        // Map status → LedgerType
        $ledgerType = match ($status) {
            BetStatus::WON => LedgerType::BET_WON,
            BetStatus::LOST => LedgerType::BET_LOST,
            BetStatus::PUSH => LedgerType::BET_PUSH,
            BetStatus::HALF_WON => LedgerType::BET_HALF_WON,
            BetStatus::HALF_LOST => LedgerType::BET_HALF_LOST,
            default => throw new \InvalidArgumentException("Trạng thái không hỗ trợ settle: {$status->value}"),
        };

        // Gọi trong transaction của SettlementEngine, wallet đã lockForUpdate
        if ($wallet->locked_balance < $stake) {
            throw new NegativeBalanceException('locked_balance', $wallet->locked_balance - $stake);
        }

        $wallet->locked_balance -= $stake;
        $wallet->available_balance += $grossPayout;
        $wallet->total_payout += $grossPayout;
        $wallet->net_profit = $wallet->total_payout - $wallet->total_staked;
        $wallet->save();

        $amountAvailable = $grossPayout;
        $ledger = $this->createLedger($wallet, $ledgerType, $amountAvailable, -$stake, null, "Settle bet #{$bet->public_code}");
        $ledger->bet_id = $bet->id;
        $ledger->save();

        return $ledger;
    }

    /**
     * Void bet: hoàn trả stake từ locked về available.
     */
    public function voidBet(Wallet $wallet, Bet $bet): WalletLedger
    {
        $stake = $bet->stake;

        // Gọi trong transaction đã mở, wallet đã lockForUpdate
        if ($wallet->locked_balance < $stake) {
            throw new NegativeBalanceException('locked_balance', $wallet->locked_balance - $stake);
        }

        $wallet->locked_balance -= $stake;
        $wallet->available_balance += $stake;
        $wallet->total_staked = max(0, $wallet->total_staked - $stake);
        $wallet->net_profit = $wallet->total_payout - $wallet->total_staked;
        $wallet->save();

        $ledger = $this->createLedger($wallet, LedgerType::BET_VOIDED, $stake, -$stake, null, "Void bet #{$bet->public_code}");
        $ledger->bet_id = $bet->id;
        $ledger->save();

        return $ledger;
    }

    /**
     * Sửa kết quả: Cộng/trừ số chênh lệch do settlement correction.
     */
    public function correctSettlement(Wallet $wallet, Bet $bet, int $adjustment, string $reason): WalletLedger
    {
        // Gọi trong transaction đã mở, wallet đã lockForUpdate
        if ($adjustment < 0 && $wallet->available_balance < abs($adjustment)) {
            // Cho phép âm available balance nếu sửa kết quả làm mất tiền? 
            // Phase 2 yêu cầu available_balance không được âm, nên ta có thể throw hoặc cứ trừ (rồi user nợ, nhưng rule cấm âm).
            // Tạm thời nếu âm, allow nợ hoặc set về 0? Trong cá cược nội bộ có thể allow tạm hoặc ném exception
            // We will throw InsufficientBalanceException for now if it drops below 0, or let it go negative? 
            // Rule: "Negative `available_balance` is forbidden." - wait, if we settled wrong and user spent it? 
            // Let's just deduct it and if it goes negative, we might need a manual grant, but for now we throw.
            throw new InsufficientBalanceException($wallet->available_balance, abs($adjustment));
        }

        $wallet->available_balance += $adjustment;
        $wallet->total_payout += $adjustment;
        $wallet->net_profit = $wallet->total_payout - $wallet->total_staked;
        $wallet->save();

        $ledger = $this->createLedger($wallet, LedgerType::SETTLEMENT_CORRECTION, $adjustment, 0, null, $reason);
        $ledger->bet_id = $bet->id;
        $ledger->save();

        return $ledger;
    }

    /**
     * Tạo ledger entry. Gọi sau khi wallet đã được update.
     */
    private function createLedger(
        Wallet $wallet,
        LedgerType $type,
        int $amountAvailable,
        int $amountLocked,
        ?User $actor,
        string $reason = ''
    ): WalletLedger {
        return WalletLedger::create([
            'wallet_id' => $wallet->id,
            'user_id' => $wallet->user_id,
            'season_id' => $wallet->season_id,
            'type' => $type,
            'amount_available' => $amountAvailable,
            'amount_locked' => $amountLocked,
            'balance_available_after' => $wallet->available_balance,
            'balance_locked_after' => $wallet->locked_balance,
            'actor_id' => $actor?->id,
            'reason' => $reason,
            'created_at' => now(),
        ]);
    }
}
