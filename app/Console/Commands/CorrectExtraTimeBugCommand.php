<?php

namespace App\Console\Commands;

use App\Domain\Settlement\Services\CorrectionService;
use App\Models\FootballMatch;
use App\Models\Market;
use App\Models\MatchPeriodResult;
use App\Models\Settlement;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Command sửa lỗi kèo FULL_TIME và SECOND_HALF bị tính sai do lẫn bàn thắng hiệp phụ.
 *
 * Bug: API trả `fullTime` bao gồm cả Extra Time goals. Settlement dùng tỉ số này
 * thay vì tỉ số 90 phút, dẫn đến payout sai cho kèo cả trận và hiệp 2.
 *
 * Flow:
 *   1. Tìm tất cả trận có hiệp phụ (EXTRA_TIME period result)
 *   2. Tính lại tỉ số 90 phút đúng = FULL_TIME hiện tại - EXTRA_TIME
 *   3. Dùng CorrectionService để re-settle các market bị ảnh hưởng
 *
 * Usage:
 *   php artisan fix:extra-time-bug --dry-run
 *   php artisan fix:extra-time-bug
 */
class CorrectExtraTimeBugCommand extends Command
{
    protected $signature = 'fix:extra-time-bug
        {--dry-run : Chỉ kiểm tra, không thực sự sửa}
        {--match-code= : Chỉ sửa cho một trận cụ thể}';

    protected $description = 'Sửa lỗi settlement kèo cả trận bị tính bao gồm hiệp phụ';

    public function handle(CorrectionService $correctionService): int
    {
        $isDryRun = (bool) $this->option('dry-run');
        $matchCode = $this->option('match-code');

        if ($isDryRun) {
            $this->warn('[DRY-RUN] Chỉ kiểm tra — không thay đổi DB.');
        }

        // Tìm trận có hiệp phụ
        $query = FootballMatch::whereHas('periodResults', function ($q) {
            $q->where('period_type', 'EXTRA_TIME');
        });

        if ($matchCode) {
            $query->where('match_code', $matchCode);
        }

        $matches = $query->get();

        if ($matches->isEmpty()) {
            $this->info('Không tìm thấy trận nào có hiệp phụ. Không cần correction.');

            return self::SUCCESS;
        }

        $totalCorrected = 0;
        $totalSkipped = 0;
        $totalErrors = 0;

        foreach ($matches as $match) {
            $this->newLine();
            $this->info("=== {$match->home_team} vs {$match->away_team} (match_code: {$match->match_code}) ===");

            $ftPeriod = $match->periodResults()->where('period_type', 'FULL_TIME')->first();
            $htPeriod = $match->periodResults()->where('period_type', 'FIRST_HALF')->first();
            $etPeriod = $match->periodResults()->where('period_type', 'EXTRA_TIME')->first();

            if (! $ftPeriod || ! $etPeriod) {
                $this->comment('  Thiếu FULL_TIME hoặc EXTRA_TIME period result. Bỏ qua.');
                $totalSkipped++;

                continue;
            }

            // Tính tỉ số 90 phút đúng
            // Nếu FULL_TIME hiện tại đã bao gồm ET: regulationScore = FULL_TIME - EXTRA_TIME
            // Nếu FULL_TIME hiện tại chưa bao gồm ET: không cần sửa
            $currentFtHome = $ftPeriod->home_score;
            $currentFtAway = $ftPeriod->away_score;
            $etHome = $etPeriod->home_score;
            $etAway = $etPeriod->away_score;

            // Heuristic: Nếu trận đá hiệp phụ thì 90 phút phải hòa.
            // Nếu FULL_TIME hiện tại - ET = hòa → FULL_TIME đang sai (bao gồm ET)
            // Nếu FULL_TIME hiện tại đã là hòa → FULL_TIME đúng rồi (chỉ 90 phút)
            $possibleRegulationHome = $currentFtHome - $etHome;
            $possibleRegulationAway = $currentFtAway - $etAway;

            $this->line("  FULL_TIME hiện tại: {$currentFtHome}-{$currentFtAway}");
            $this->line("  EXTRA_TIME: {$etHome}-{$etAway}");
            $this->line("  FIRST_HALF: " . ($htPeriod ? "{$htPeriod->home_score}-{$htPeriod->away_score}" : 'N/A'));

            // Kiểm tra: nếu FULL_TIME đang hòa → đã đúng, không cần sửa
            if ($currentFtHome === $currentFtAway) {
                $this->info("  FULL_TIME đã là hòa ({$currentFtHome}-{$currentFtAway}) → ĐÚNG. Không cần sửa.");
                $totalSkipped++;

                continue;
            }

            // Kiểm tra: FT - ET phải là hòa (vì trận đã đá ET tức 90p phải hòa)
            if ($possibleRegulationHome !== $possibleRegulationAway) {
                $this->warn("  ⚠️  FT - ET = {$possibleRegulationHome}-{$possibleRegulationAway} (KHÔNG hòa). Dữ liệu bất thường, cần kiểm tra thủ công.");
                $totalErrors++;

                continue;
            }

            $regulationHome = $possibleRegulationHome;
            $regulationAway = $possibleRegulationAway;
            $this->line("  Tỉ số 90 phút đúng: {$regulationHome}-{$regulationAway}");

            // --- 1. Sửa Period Result FULL_TIME ---
            if (! $isDryRun) {
                $ftPeriod->update([
                    'home_score' => $regulationHome,
                    'away_score' => $regulationAway,
                    'source_note' => "Corrected: was {$currentFtHome}-{$currentFtAway} (included ET), fixed to 90min only",
                ]);
                $this->line("  ✓ Cập nhật FULL_TIME period: {$regulationHome}-{$regulationAway}");
            } else {
                $this->line("  [DRY] Sẽ cập nhật FULL_TIME period: {$currentFtHome}-{$currentFtAway} → {$regulationHome}-{$regulationAway}");
            }

            // --- 2. Sửa Period Result SECOND_HALF ---
            $shPeriod = $match->periodResults()->where('period_type', 'SECOND_HALF')->first();
            if ($shPeriod && $htPeriod) {
                $correctShHome = max(0, $regulationHome - $htPeriod->home_score);
                $correctShAway = max(0, $regulationAway - $htPeriod->away_score);

                if ($shPeriod->home_score !== $correctShHome || $shPeriod->away_score !== $correctShAway) {
                    if (! $isDryRun) {
                        $shPeriod->update([
                            'home_score' => $correctShHome,
                            'away_score' => $correctShAway,
                            'source_note' => "Corrected: was {$shPeriod->home_score}-{$shPeriod->away_score}, fixed using regulation FT - HT",
                        ]);
                        $this->line("  ✓ Cập nhật SECOND_HALF period: {$correctShHome}-{$correctShAway}");
                    } else {
                        $this->line("  [DRY] Sẽ cập nhật SECOND_HALF period: {$shPeriod->home_score}-{$shPeriod->away_score} → {$correctShHome}-{$correctShAway}");
                    }
                }
            }

            // --- 3. Correction cho các market FULL_TIME đã settle ---
            $this->correctMarketsForPeriod(
                $match, 'FULL_TIME', $regulationHome, $regulationAway,
                $correctionService, $isDryRun, $totalCorrected, $totalSkipped
            );

            // --- 4. Correction cho các market SECOND_HALF đã settle ---
            if ($shPeriod && $htPeriod) {
                $this->correctMarketsForPeriod(
                    $match, 'SECOND_HALF', $correctShHome, $correctShAway,
                    $correctionService, $isDryRun, $totalCorrected, $totalSkipped
                );
            }
        }

        $this->newLine();
        $this->info("=== KẾT QUẢ ===");
        $this->line("Corrected: {$totalCorrected}");
        $this->line("Skipped: {$totalSkipped}");
        $this->line("Errors: {$totalErrors}");

        if ($totalErrors > 0) {
            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    private function correctMarketsForPeriod(
        FootballMatch $match,
        string $periodType,
        int $correctHome,
        int $correctAway,
        CorrectionService $correctionService,
        bool $isDryRun,
        int &$totalCorrected,
        int &$totalSkipped
    ): void {
        $settledMarkets = Market::where('match_id', $match->id)
            ->where('period_type', $periodType)
            ->where('status', 'SETTLED')
            ->get();

        foreach ($settledMarkets as $market) {
            $settlement = Settlement::where('market_id', $market->id)
                ->where('status', 'EXECUTED')
                ->first();

            if (! $settlement) {
                $totalSkipped++;

                continue;
            }

            // Kiểm tra settlement dùng đúng tỉ số chưa
            if ($settlement->result_home_score === $correctHome && $settlement->result_away_score === $correctAway) {
                $this->comment("  Market#{$market->id} [{$market->market_type}|{$periodType}]: Settlement đã đúng ({$correctHome}-{$correctAway}). Bỏ qua.");
                $totalSkipped++;

                continue;
            }

            // Kiểm tra đã có correction chưa (tránh chạy lại)
            $existingCorrection = $settlement->corrections()
                ->where('status', 'EXECUTED')
                ->where('new_results->home_score', $correctHome)
                ->where('new_results->away_score', $correctAway)
                ->first();

            if ($existingCorrection) {
                $this->comment("  Market#{$market->id}: Đã có correction #{$existingCorrection->id}. Bỏ qua.");
                $totalSkipped++;

                continue;
            }

            $this->warn("  🔴 Market#{$market->id} [{$market->market_type}|{$periodType}]: Settlement sai ({$settlement->result_home_score}-{$settlement->result_away_score}), đúng: {$correctHome}-{$correctAway}");

            if ($isDryRun) {
                $this->line("  [DRY] Sẽ tạo correction: {$settlement->result_home_score}-{$settlement->result_away_score} → {$correctHome}-{$correctAway}");
                $totalCorrected++;

                continue;
            }

            try {
                $reason = "Auto-correction: Bug FULL_TIME bao gồm hiệp phụ. Sửa từ {$settlement->result_home_score}-{$settlement->result_away_score} thành {$correctHome}-{$correctAway} (90 phút)";

                $correction = $correctionService->createCorrection(
                    $settlement->id,
                    ['home_score' => $correctHome, 'away_score' => $correctAway],
                    $reason
                );

                $correctionService->executeCorrection($correction);

                $this->info("  ✓ Correction #{$correction->id} executed. Market#{$market->id}");
                Log::info("ET bug correction executed", [
                    'match' => $match->match_code,
                    'market_id' => $market->id,
                    'old_score' => "{$settlement->result_home_score}-{$settlement->result_away_score}",
                    'new_score' => "{$correctHome}-{$correctAway}",
                    'correction_id' => $correction->id,
                ]);

                $totalCorrected++;
            } catch (\Throwable $e) {
                $this->error("  ✗ Correction failed Market#{$market->id}: {$e->getMessage()}");
                Log::error("ET bug correction failed", [
                    'market_id' => $market->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
