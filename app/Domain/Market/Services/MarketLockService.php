<?php

namespace App\Domain\Market\Services;

use App\Domain\Audit\Services\AuditLogService;
use App\Domain\Market\Exceptions\InvalidMarketTransitionException;
use App\Enums\MarketStatus;
use App\Models\Market;
use Illuminate\Support\Facades\DB;

class MarketLockService
{
    public function __construct(
        private readonly AuditLogService $auditLog,
    ) {}

    /**
     * Allowed status transitions.
     */
    private const ALLOWED_TRANSITIONS = [
        'DRAFT' => ['OPEN', 'VOIDED', 'CANCELLED'],
        'OPEN' => ['LOCKED', 'VOIDED', 'CANCELLED'],
        'LOCKED' => ['OPEN', 'SETTLING', 'VOIDED'],
        'SETTLING' => ['SETTLED', 'VOIDED'],
        'SETTLED' => [],
        'VOIDED' => [],
        'CANCELLED' => [],
    ];

    /**
     * Transition a single market to a new status.
     */
    public function transition(Market $market, MarketStatus $newStatus, ?string $reason = null): Market
    {
        $current = $market->status;
        $allowed = self::ALLOWED_TRANSITIONS[$current] ?? [];

        if (! in_array($newStatus->value, $allowed, true)) {
            throw new InvalidMarketTransitionException($current, $newStatus->value);
        }

        return DB::transaction(function () use ($market, $newStatus, $reason, $current) {
            $market = Market::lockForUpdate()->findOrFail($market->id);
            $fromStatus = $current; // Capture trước khi refresh

            // Re-check after lock
            $allowed = self::ALLOWED_TRANSITIONS[$market->status] ?? [];
            if (! in_array($newStatus->value, $allowed, true)) {
                throw new InvalidMarketTransitionException($market->status, $newStatus->value);
            }

            $now = now();
            $updates = ['status' => $newStatus->value];

            if ($newStatus === MarketStatus::LOCKED) {
                $updates['locked_at'] = $now;
            } elseif ($newStatus === MarketStatus::SETTLED) {
                $updates['settled_at'] = $now;
            } elseif ($newStatus === MarketStatus::VOIDED) {
                $updates['voided_at'] = $now;
                $updates['void_reason'] = $reason;
            }

            $market->update($updates);
            $updated = $market->fresh();

            // Log khi admin thực hiện thủ công (publish/void)
            $eventMap = [
                'OPEN' => 'MARKET_PUBLISHED',
                'VOIDED' => 'MARKET_VOIDED',
                'LOCKED' => 'MARKET_LOCKED',
            ];
            if (isset($eventMap[$newStatus->value])) {
                $this->auditLog->logMarketTransition(
                    $eventMap[$newStatus->value],
                    $updated,
                    $fromStatus,
                    $newStatus->value,
                    $reason,
                );
            }

            return $updated;
        });
    }

    /**
     * Publish market: DRAFT → OPEN.
     */
    public function publish(Market $market): Market
    {
        return $this->transition($market, MarketStatus::OPEN);
    }

    /**
     * Lock all OPEN markets whose close_at <= now. Idempotent.
     */
    public function lockExpiredMarkets(): int
    {
        $expired = Market::where('status', 'OPEN')
            ->where('close_at', '<=', now())
            ->get();

        $count = 0;
        foreach ($expired as $market) {
            try {
                $this->transition($market, MarketStatus::LOCKED);
                $count++;
            } catch (InvalidMarketTransitionException) {
                // Đã được lock bởi process khác — bỏ qua
            }
        }

        return $count;
    }
}
