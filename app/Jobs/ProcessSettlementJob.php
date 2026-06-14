<?php

namespace App\Jobs;

use App\Domain\Settlement\Data\MatchResult;
use App\Domain\Settlement\Services\SettlementEngine;
use App\Models\Market;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessSettlementJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public Market $market,
        public MatchResult $matchResult,
        public ?User $executedBy = null,
        public string $reason = ''
    ) {}

    public function handle(SettlementEngine $settlementEngine): void
    {
        try {
            $settlementEngine->execute(
                $this->market,
                $this->matchResult,
                $this->executedBy,
                $this->reason
            );
        } catch (\Exception $e) {
            Log::error("Lỗi khi chạy ProcessSettlementJob cho market {$this->market->id}: " . $e->getMessage());
            throw $e;
        }
    }
}
