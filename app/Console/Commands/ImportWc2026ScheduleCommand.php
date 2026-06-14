<?php

namespace App\Console\Commands;

use App\Models\FootballMatch;
use App\Models\Season;
use Carbon\Carbon;
use Illuminate\Console\Command;

/**
 * Import lịch thi đấu WC2026 từ CSV. Idempotent (upsert bằng match_code).
 *
 * CSV format (có header):
 *   match_code,stage,home_team,away_team,kickoff_at,venue
 *
 * Usage:
 *   php artisan import:wc2026 --file=storage/wc2026.csv
 *   php artisan import:wc2026 --file=storage/wc2026.csv --season=WC2026
 */
class ImportWc2026ScheduleCommand extends Command
{
    protected $signature = 'import:wc2026
                            {--file= : Đường dẫn file CSV}
                            {--season=WC2026 : Mã mùa giải}
                            {--dry-run : Chỉ đọc, không ghi DB}';

    protected $description = 'Import lịch thi đấu WC2026 từ file CSV (idempotent)';

    public function handle(): int
    {
        $filePath = $this->option('file') ?? storage_path('fixtures/wc2026.csv');
        $seasonCode = $this->option('season');
        $dryRun = $this->option('dry-run');

        if (! file_exists($filePath)) {
            $this->error("Không tìm thấy file: {$filePath}");
            $this->line('Tạo file CSV với format:');
            $this->line('  match_code,stage,home_team,away_team,kickoff_at,venue');
            $this->line('  M001,Group A,Brazil,Germany,2026-06-11 02:00,Stadium 1');

            return Command::FAILURE;
        }

        $season = Season::where('code', $seasonCode)->first();
        if (! $season) {
            $this->error("Không tìm thấy season: {$seasonCode}. Chạy seeder trước.");

            return Command::FAILURE;
        }

        $fp = fopen($filePath, 'r');
        $header = fgetcsv($fp); // Bỏ qua dòng header

        $created = 0;
        $updated = 0;
        $errors = [];

        while (($row = fgetcsv($fp)) !== false) {
            if (count($row) < 5) {
                continue;
            }

            [$seasonCodeCsv, $matchCode, $stage, $homeTeam, $awayTeam, $kickoffAt, $venue, $status, $group] = array_pad($row, 9, null);

            $matchCode = trim($matchCode);
            if (empty($matchCode)) {
                continue;
            }

            try {
                $data = [
                    'season_id' => $season->id,
                    'stage' => trim($stage),
                    'group' => $group ? trim($group) : null,
                    'home_team' => trim($homeTeam),
                    'away_team' => trim($awayTeam),
                    // Giờ trong CSV là giờ Việt Nam, nên parse với timezone VN để tránh bị parse sai sang UTC
                    'kickoff_at' => Carbon::parse(trim($kickoffAt), 'Asia/Ho_Chi_Minh'),
                    'timezone' => 'Asia/Ho_Chi_Minh',
                    'venue' => $venue ? trim($venue) : null,
                    'status' => 'SCHEDULED',
                ];

                if ($dryRun) {
                    $this->line("  [DRY] {$matchCode}: {$homeTeam} vs {$awayTeam} @ {$kickoffAt}");

                    continue;
                }

                $existing = FootballMatch::where('match_code', $matchCode)->first();
                if ($existing) {
                    $existing->update($data);
                    $updated++;
                } else {
                    FootballMatch::create(array_merge(['match_code' => $matchCode], $data));
                    $created++;
                }
            } catch (\Throwable $e) {
                $errors[] = "{$matchCode}: {$e->getMessage()}";
            }
        }

        fclose($fp);

        if ($dryRun) {
            $this->info('[DRY RUN] Không ghi DB.');

            return Command::SUCCESS;
        }

        $this->info("Import hoàn tất: {$created} tạo mới, {$updated} cập nhật.");

        if ($errors) {
            $this->warn(count($errors).' lỗi:');
            foreach ($errors as $err) {
                $this->line("  ✗ {$err}");
            }
        }

        return Command::SUCCESS;
    }
}
