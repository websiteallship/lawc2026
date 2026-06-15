<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\MarketOutcome;
use App\Models\Bet;
use App\Domain\Wallet\Services\WalletService;
use Illuminate\Support\Facades\DB;
use App\Enums\BetStatus;

class CleanGarbageOddsCommand extends Command
{
    protected $signature = 'odds:clean-garbage';
    protected $description = 'Ẩn các kèo rác (profit < 0.50) và Hủy (Void) hoàn tiền các vé đã cược vào kèo đó';

    public function handle(WalletService $walletService)
    {
        $this->info("1. Đang quét các tỷ lệ cược (Outcomes) rác...");

        $outcomes = MarketOutcome::with('market')
            ->where('status', 'ACTIVE')
            ->whereHas('market', function($q) {
                $q->whereIn('market_type', ['ASIAN_HANDICAP', 'OVER_UNDER']);
            })
            ->get()
            ->groupBy(function($o) {
                return $o->market_id . '_' . $o->line_value;
            });

        $suspendIds = [];

        foreach ($outcomes as $key => $lineOutcomes) {
            $hasBad = false;
            foreach ($lineOutcomes as $o) {
                // Sửa lỗi: dùng decimal_odds thay vì profit_rate vì profit_rate ở code cũ bị lưu sai thành 1.00
                if ($o->decimal_odds < 1.50) {
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
        } else {
            $this->info("-> Không có tỷ lệ cược rác nào cần ẩn.");
        }

        $this->info("\n2. Đang quét các vé cược (Bets) đã đặt vào kèo rác...");

        $bets = Bet::where('status', 'PENDING')
            ->whereIn('outcome_id', $suspendIds)
            ->with(['wallet', 'user'])
            ->get();

        if ($bets->isEmpty()) {
            $this->info("-> Không có vé cược rác nào cần hủy.");
            return;
        }

        $this->info("-> Tìm thấy " . $bets->count() . " vé cược. Đang tiến hành hủy và hoàn tiền...");

        DB::transaction(function () use ($bets, $walletService) {
            foreach ($bets as $bet) {
                $wallet = \App\Models\Wallet::lockForUpdate()->findOrFail($bet->wallet_id);
                
                $bet->status = BetStatus::VOIDED;
                $bet->voided_at = now();
                $bet->metadata = array_merge($bet->metadata ?? [], ['void_reason' => 'Hệ thống hủy kèo lỗi (Tỷ lệ < 0.50)']);
                $bet->save();

                $walletService->voidBet($wallet, $bet);
                
                $this->line("   + Đã hủy vé #{$bet->public_code} (User: {$bet->user->email}). Hoàn lại {$bet->stake} lá.");
            }
        });

        $this->info("\n✅ Hoàn tất dọn dẹp!");
    }
}
