<?php

namespace App\Console\Commands;

use App\Domain\Market\Services\MarketLockService;
use App\Domain\Settlement\Data\MatchResult;
use App\Domain\Settlement\Services\SettlementEngine;
use App\Enums\MarketStatus;
use App\Models\FootballMatch;
use App\Models\Market;
use App\Models\MatchPeriodResult;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class MockResultCommand extends Command
{
    protected $signature = 'mock:match-results
        {match_code=M003 : Mã trận đấu}
        {--ft-home=1 : Bàn thắng đội nhà Full Time}
        {--ft-away=0 : Bàn thắng đội khách Full Time}
        {--h1-home=1 : Bàn thắng đội nhà Hiệp 1}
        {--h1-away=0 : Bàn thắng đội khách Hiệp 1}
        {--pen-home=4 : Số penalty đội nhà}
        {--pen-away=3 : Số penalty đội khách}
        {--settle : Tự động lock và execute settlement sau khi mock}';

    protected $description = 'Tạo kết quả mockup ảo cho trận đấu để test settlement. Dùng --settle để auto-lock và settle ngay.';

    public function handle(MarketLockService $lockService, SettlementEngine $settlementEngine): int
    {
        $matchCode = $this->argument('match_code');
        $match = FootballMatch::where('match_code', $matchCode)->first();

        if (! $match) {
            $this->error("Không tìm thấy trận đấu với mã: {$matchCode}");

            return self::FAILURE;
        }

        $ftHome = (int) $this->option('ft-home');
        $ftAway = (int) $this->option('ft-away');
        $h1Home = (int) $this->option('h1-home');
        $h1Away = (int) $this->option('h1-away');
        $penHome = (int) $this->option('pen-home');
        $penAway = (int) $this->option('pen-away');
        $h2Home = max(0, $ftHome - $h1Home);
        $h2Away = max(0, $ftAway - $h1Away);

        $this->info("Trận: {$match->home_team} vs {$match->away_team}");
        $this->line("  Hiệp 1:   {$h1Home} - {$h1Away}");
        $this->line("  Hiệp 2:   {$h2Home} - {$h2Away}");
        $this->line("  Cả trận:  {$ftHome} - {$ftAway}");
        $this->line("  Penalty:  {$penHome} - {$penAway}");
        $this->newLine();

        // --- 1. Ghi kết quả từng hiệp ---
        $periods = [
            'FIRST_HALF' => ['home' => $h1Home,  'away' => $h1Away],
            'SECOND_HALF' => ['home' => $h2Home,  'away' => $h2Away],
            'FULL_TIME' => ['home' => $ftHome,  'away' => $ftAway],
            'PENALTY' => ['home' => $penHome, 'away' => $penAway],
        ];

        foreach ($periods as $period => $score) {
            MatchPeriodResult::updateOrCreate(
                ['match_id' => $match->id, 'period_type' => $period],
                [
                    'home_score' => $score['home'],
                    'away_score' => $score['away'],
                    'status' => 'CONFIRMED',
                    'source_note' => 'Mock Data (testing)',
                    'entered_by' => 1,
                ]
            );
        }

        $match->update([
            'home_score' => $ftHome,
            'away_score' => $ftAway,
            'status' => 'FINISHED',
            'finished_at' => now(),
        ]);

        $this->info('✓ Đã ghi kết quả mock vào DB.');

        if (! $this->option('settle')) {
            $this->comment('Bỏ qua bước settle. Dùng --settle để tự động lock và execute settlement.');

            return self::SUCCESS;
        }

        // --- 2. Lock tất cả OPEN markets ---
        $this->newLine();
        $this->info('--- Đang lock markets OPEN → LOCKED ---');

        $openMarkets = Market::where('match_id', $match->id)
            ->where('status', MarketStatus::OPEN->value)
            ->get();

        foreach ($openMarkets as $market) {
            try {
                $lockService->transition($market, MarketStatus::LOCKED);
                $this->line("  ✓ Locked Market#{$market->id} [{$market->market_type}|{$market->period_type}]");
            } catch (\Throwable $e) {
                $this->warn("  ✗ Lock Market#{$market->id} failed: {$e->getMessage()}");
            }
        }

        // --- 3. Execute Settlement cho từng market ---
        $this->newLine();
        $this->info('--- Đang execute settlement ---');

        $lockedMarkets = Market::where('match_id', $match->id)
            ->whereIn('status', [MarketStatus::LOCKED->value, 'SETTLING'])
            ->get();

        foreach ($lockedMarkets as $market) {
            $periodResult = MatchPeriodResult::where('match_id', $match->id)
                ->where('period_type', $market->period_type)
                ->first();

            if (! $periodResult) {
                $this->warn("  ✗ Market#{$market->id} [{$market->market_type}|{$market->period_type}]: Không có period_result. Bỏ qua.");

                continue;
            }

            try {
                $matchResult = new MatchResult($periodResult->home_score, $periodResult->away_score);
                $settlement = $settlementEngine->execute($market, $matchResult, null, 'Mock settlement for testing');

                $this->line(sprintf(
                    '  ✓ Settled Market#%d [%s|%s] result=%d-%d | bets=%d | payout=%s lá',
                    $market->id,
                    $market->market_type,
                    $market->period_type,
                    $periodResult->home_score,
                    $periodResult->away_score,
                    $settlement->total_bets,
                    number_format($settlement->total_payout)
                ));
            } catch (\Throwable $e) {
                $this->warn("  ✗ Market#{$market->id} settlement failed: {$e->getMessage()}");
                Log::error("MockResultCommand settlement failed for Market#{$market->id}: ".$e->getMessage());
            }
        }

        $this->newLine();
        $this->info('✓ Hoàn tất. Kiểm tra Admin Panel → Kết Quả Mở Thưởng để xem kết quả.');

        return self::SUCCESS;
    }
}
