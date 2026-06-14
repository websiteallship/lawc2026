<?php

namespace App\Domain\Audit\Services;

use Illuminate\Database\Eloquent\Model;

/**
 * AuditLogService — wrapper mỏng quanh spatie/laravel-activitylog.
 *
 * Tất cả admin-sensitive actions PHẢI đi qua service này.
 * Không log trực tiếp bằng activity() helper ở tầng Filament/Controller.
 */
class AuditLogService
{
    /**
     * Log một sự kiện nghiệp vụ.
     *
     * @param  string  $event  Tên sự kiện (VD: WALLET_GRANTED, MARKET_PUBLISHED)
     * @param  array  $properties  Thông tin kèm theo
     * @param  Model|null  $subject  Đối tượng bị tác động (Market, Bet, Wallet...)
     * @param  Model|null  $causer  Người thực hiện (User)
     */
    public function log(
        string $event,
        array $properties = [],
        ?Model $subject = null,
        ?Model $causer = null,
        ?string $description = null
    ): void {
        $builder = activity('du-doan-la')
            ->withProperties($properties)
            ->event($event);

        if ($subject) {
            $builder = $builder->performedOn($subject);
        }

        if ($causer) {
            $builder = $builder->causedBy($causer);
        }

        $builder->log($description ?? $event);
    }

    /**
     * Log wallet grant/deduct.
     */
    public function logWalletChange(
        string $event,    // WALLET_GRANTED | WALLET_DEDUCTED
        Model $wallet,
        int $amount,
        string $reason,
        ?Model $actor = null,
    ): void {
        $action = $event === 'WALLET_GRANTED' ? 'Cộng' : 'Trừ';
        $desc = "{$action} {$amount} lá. Lý do: {$reason}";

        $this->log($event, [
            'amount' => $amount,
            'reason' => $reason,
            'balance_after' => $wallet->available_balance,
        ], $wallet, $actor, $desc);
    }

    /**
     * Log market status change.
     */
    public function logMarketTransition(
        string $event,    // MARKET_PUBLISHED | MARKET_LOCKED | MARKET_VOIDED
        Model $market,
        string $fromStatus,
        string $toStatus,
        ?string $reason = null,
        ?Model $actor = null,
    ): void {
        $desc = "Trạng thái: {$fromStatus} -> {$toStatus}".($reason ? " ({$reason})" : '');

        $this->log($event, [
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'reason' => $reason,
        ], $market, $actor, $desc);
    }

    /**
     * Log settlement executed.
     */
    public function logSettlement(
        Model $settlement,
        int $totalBets,
        int $totalPayout,
        ?Model $actor = null,
    ): void {
        $desc = "Trả thưởng {$totalBets} cược. Tổng xuất: {$totalPayout} lá";

        $this->log('SETTLEMENT_EXECUTED', [
            'total_bets' => $totalBets,
            'total_payout' => $totalPayout,
        ], $settlement, $actor, $desc);
    }
}
