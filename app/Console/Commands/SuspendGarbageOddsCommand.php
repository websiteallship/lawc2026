<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\MarketOutcome;

class SuspendGarbageOddsCommand extends Command
{
    protected $signature = 'odds:suspend-garbage';
    protected $description = 'Ẩn các kèo rác (profit < 0.30) khỏi giao diện người chơi (KHÔNG hoàn vé cược)';

    public function handle()
    {
        $this->info("Đang quét các tỷ lệ cược (Outcomes) rác...");

        $outcomes = MarketOutcome::with('market')
            ->where('status', 'ACTIVE')
            ->whereHas('market', function($q) {
                $q->whereIn('market_type', ['ASIAN_HANDICAP', 'OVER_UNDER']);
            })
            ->get()
            ->groupBy(function($o) {
                return $o->market_id . '_' . abs($o->line_value);
            });

        $suspendIds = [];

        foreach ($outcomes as $key => $lineOutcomes) {
            $hasBad = false;
            foreach ($lineOutcomes as $o) {
                if ($o->profit_rate < 0.30) {
                    $hasBad = true;
                    break;
                }
            }
            if ($hasBad) {
                foreach ($lineOutcomes as $o) {
                    $suspendIds[] = $o->id;
                }
            }
        }

        if (!empty($suspendIds)) {
            MarketOutcome::whereIn('id', $suspendIds)->update(['status' => 'SUSPENDED']);
            $this->info("-> Đã ẩn " . count($suspendIds) . " tỷ lệ cược rác.");
            $this->warn("-> Lưu ý: Các vé cược đã đặt vào các kèo này vẫn giữ nguyên (PENDING), không bị hoàn tiền.");
        } else {
            $this->info("-> Không có tỷ lệ cược rác nào cần ẩn.");
        }

        $this->info("\n✅ Hoàn tất dọn dẹp!");
    }
}
