<?php

namespace App\Listeners;

use App\Events\MatchStatusChanged;
use App\Domain\Market\Services\MarketLockService;
use App\Enums\MarketStatus;
use App\Models\Market;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class MarketStatusTransitionListener implements ShouldQueue
{
    public function __construct(
        private readonly MarketLockService $marketLockService
    ) {}

    public function handle(MatchStatusChanged $event): void
    {
        $match = $event->match;
        $status = $match->status;

        try {
            $marketsToLock = [];

            // 1. Kèo Cả trận/Hiệp 1: Đóng khi trận đổi sang LIVE (từ SCHEDULED)
            if ($status === 'LIVE' && $event->oldStatus === 'SCHEDULED') {
                $marketsToLock = array_merge($marketsToLock, Market::where('match_id', $match->id)
                    ->whereIn('period_type', ['FULL_TIME', 'FIRST_HALF'])
                    ->where('status', MarketStatus::OPEN->value)
                    ->get()->all());
            }

            // Các logic khóa chi tiết (Hiệp 2, Hiệp phụ, Pen) đã được xử lý tự động trong 
            // MatchSyncService::autoManageMarkets dựa vào detailedStatus từ API.
            // Đoạn này chỉ fallback cho trường hợp Admin đổi tay trạng thái sang LIVE.

            foreach ($marketsToLock as $market) {
                $this->marketLockService->transition($market, MarketStatus::LOCKED, "Auto-locked due to match status: $status");
            }
        } catch (\Exception $e) {
            Log::error("Failed to auto-lock markets for match {$match->id} on status $status: " . $e->getMessage());
        }
    }
}
