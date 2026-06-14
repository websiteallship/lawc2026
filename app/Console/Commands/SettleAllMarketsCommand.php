<?php

namespace App\Console\Commands;

use App\Domain\Market\Services\MarketLockService;
use App\Domain\Settlement\Data\MatchResult;
use App\Domain\Settlement\Services\SettlementEngine;
use App\Domain\Wallet\Services\WalletService;
use App\Enums\BetStatus;
use App\Enums\MarketStatus;
use App\Models\FootballMatch;
use App\Models\Market;
use App\Models\MatchPeriodResult;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Command độc lập để lock + execute settlement toàn bộ market của một trận đấu.
 * Dùng khi kết quả đã được nhập thủ công hoặc từ API.
 *
 * Usage:
 *   php artisan settle:markets M003
 *   php artisan settle:markets M003 --dry-run
 */
class SettleAllMarketsCommand extends Command
{
    protected $signature = 'settle:markets
        {match_code : Mã trận đấu (ví dụ: M003)}
        {--dry-run : Chỉ preview, không thực sự execute}';

    protected $description = 'Lock và execute settlement cho tất cả markets của một trận đấu';

    public function handle(MarketLockService $lockService, SettlementEngine $settlementEngine): int
    {
        $matchCode = $this->argument('match_code');
        $match = FootballMatch::where('match_code', $matchCode)->first();

        if (! $match) {
            $this->error("Không tìm thấy trận: {$matchCode}");

            return self::FAILURE;
        }

        $isDryRun = (bool) $this->option('dry-run');

        $this->info("Trận: {$match->home_team} vs {$match->away_team} | Status: {$match->status}");

        if ($isDryRun) {
            $this->warn('[DRY-RUN] Chỉ preview — không thực sự thay đổi DB.');
        }

        // --- Kiểm tra kết quả từng hiệp đã có chưa ---
        $periodResults = MatchPeriodResult::where('match_id', $match->id)
            ->pluck('period_type')
            ->toArray();

        $this->newLine();
        $this->line('Period results có trong DB: '.(empty($periodResults) ? 'KHÔNG CÓ' : implode(', ', $periodResults)));
        $this->newLine();

        // --- Bước 1: Lock OPEN markets ---
        $this->info('--- Bước 1: Lock các market OPEN ---');
        $openMarkets = Market::where('match_id', $match->id)
            ->where('status', MarketStatus::OPEN->value)
            ->get();

        if ($openMarkets->isEmpty()) {
            $this->comment('  Không có market nào đang OPEN.');
        }

        foreach ($openMarkets as $market) {
            if ($isDryRun) {
                $this->line("  [DRY] Sẽ lock Market#{$market->id} [{$market->market_type}|{$market->period_type}]");

                continue;
            }

            try {
                $lockService->transition($market, MarketStatus::LOCKED);
                $this->line("  ✓ Locked Market#{$market->id} [{$market->market_type}|{$market->period_type}]");
            } catch (\Throwable $e) {
                $this->warn("  ✗ Lock Market#{$market->id} failed: {$e->getMessage()}");
            }
        }

        // --- Bước 2: Execute Settlement ---
        $this->newLine();
        $this->info('--- Bước 2: Execute Settlement ---');

        $settleableMarkets = Market::where('match_id', $match->id)
            ->whereIn('status', [MarketStatus::LOCKED->value, 'SETTLING'])
            ->get();

        if ($settleableMarkets->isEmpty()) {
            $this->comment('  Không có market nào ở trạng thái LOCKED/SETTLING.');
            $this->warn('  Tip: Hãy chắc chắn đã lock market trước, hoặc chạy lại sau khi lock thủ công trên Admin Panel.');

            return self::SUCCESS;
        }

        $successCount = 0;
        $failCount = 0;

        foreach ($settleableMarkets as $market) {
            $periodResult = MatchPeriodResult::where('match_id', $match->id)
                ->where('period_type', $market->period_type)
                ->first();

            if (! $periodResult) {
                if ($match->status === 'FINISHED') {
                    try {
                        DB::transaction(function () use ($market, $lockService) {
                            $lockService->transition($market, MarketStatus::VOIDED, "Match finished without {$market->period_type} period.");

                            $pendingBets = $market->bets()->where('status', BetStatus::PENDING->value)->get();
                            $walletService = app(WalletService::class);
                            foreach ($pendingBets as $bet) {
                                $bet->update([
                                    'status' => BetStatus::VOIDED->value,
                                    'voided_at' => now(),
                                ]);
                                $walletService->voidBet($bet->wallet, $bet);
                            }
                        });
                        $this->line("  ✓ Market#{$market->id} [{$market->market_type}|{$market->period_type}]: VOIDED (không diễn ra hiệp này).");
                        $successCount++;
                    } catch (\Throwable $e) {
                        $this->warn("  ✗ Lỗi khi VOID Market#{$market->id}: {$e->getMessage()}");
                        $failCount++;
                    }
                } else {
                    $this->warn("  ✗ Market#{$market->id} [{$market->market_type}|{$market->period_type}]: Không có period_result. Cần nhập kết quả hiệp này trước.");
                    $failCount++;
                }

                continue;
            }

            if ($isDryRun) {
                $betCount = $market->bets()->where('status', 'PENDING')->count();
                $this->line(sprintf(
                    '  [DRY] Market#%d [%s|%s] → result %d-%d | %d vé PENDING',
                    $market->id,
                    $market->market_type,
                    $market->period_type,
                    $periodResult->home_score,
                    $periodResult->away_score,
                    $betCount
                ));

                // Preview từng bet
                try {
                    $matchResult = new MatchResult($periodResult->home_score, $periodResult->away_score);
                    $previews = $settlementEngine->preview($market, $matchResult);
                    foreach ($previews as $p) {
                        $this->line(sprintf(
                            '       → Bet %s stake=%d → %s payout=%d',
                            $p['bet']->public_code,
                            $p['bet']->stake,
                            $p['result']->status->value,
                            $p['result']->grossPayout
                        ));
                    }
                } catch (\Throwable $e) {
                    $this->warn("       Preview error: {$e->getMessage()}");
                }

                continue;
            }

            try {
                $matchResult = new MatchResult($periodResult->home_score, $periodResult->away_score);
                $settlement = $settlementEngine->execute($market, $matchResult, null, 'Auto-settle via CLI');

                $this->line(sprintf(
                    '  ✓ Market#%d [%s|%s] result=%d-%d | bets=%d | payout=%s lá',
                    $market->id,
                    $market->market_type,
                    $market->period_type,
                    $periodResult->home_score,
                    $periodResult->away_score,
                    $settlement->total_bets,
                    number_format($settlement->total_payout)
                ));
                $successCount++;
            } catch (\Throwable $e) {
                $this->warn("  ✗ Market#{$market->id} settlement failed: {$e->getMessage()}");
                Log::error("SettleAllMarketsCommand failed Market#{$market->id}: ".$e->getMessage());
                $failCount++;
            }
        }

        if (! $isDryRun) {
            $this->newLine();
            $this->info("✓ Hoàn tất: {$successCount} settled, {$failCount} failed.");
        }

        return $failCount > 0 ? self::FAILURE : self::SUCCESS;
    }
}
