<?php

namespace App\Console\Commands;

use App\Models\Season;
use App\Services\Export\CsvExportService;
use Illuminate\Console\Command;

/**
 * Export dữ liệu bets, ledger, leaderboard ra CSV.
 *
 * Usage:
 *   php artisan export:season WC2026 --type=bets
 *   php artisan export:season WC2026 --type=ledger
 *   php artisan export:season WC2026 --type=leaderboard
 *   php artisan export:season WC2026 --type=all
 */
class ExportSeasonDataCommand extends Command
{
    protected $signature = 'export:season
                            {season_code : Mã mùa giải (VD: WC2026)}
                            {--type=all : bets | ledger | leaderboard | all}
                            {--output= : Thư mục output (mặc định: storage/exports)}';

    protected $description = 'Export dữ liệu season ra CSV (bets, wallet ledger, leaderboard)';

    public function handle(CsvExportService $exporter): int
    {
        $seasonCode = $this->argument('season_code');
        $type = $this->option('type');
        $outputDir = $this->option('output') ?? storage_path('exports');

        $season = Season::where('code', $seasonCode)->first();

        if (! $season) {
            $this->error("Không tìm thấy season code: {$seasonCode}");

            return Command::FAILURE;
        }

        $timestamp = now()->format('Ymd_His');
        $types = $type === 'all' ? ['bets', 'ledger', 'leaderboard'] : [$type];

        foreach ($types as $t) {
            $path = "{$outputDir}/{$seasonCode}_{$t}_{$timestamp}.csv";

            $this->info("Đang export {$t}...");

            $rows = match ($t) {
                'bets' => $exporter->betsRows($season),
                'ledger' => $exporter->ledgerRows($season),
                'leaderboard' => $exporter->leaderboardRows($season),
                default => null,
            };

            if (! $rows) {
                $this->warn("Loại không hợp lệ: {$t}. Bỏ qua.");

                continue;
            }

            $exporter->writeCsv($rows, $path);
            $this->line("  ✓ Đã lưu: {$path}");
        }

        $this->info('Export hoàn tất.');

        return Command::SUCCESS;
    }
}
