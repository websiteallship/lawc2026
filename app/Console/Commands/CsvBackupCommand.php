<?php

namespace App\Console\Commands;

use App\Models\Bet;
use App\Models\Market;
use App\Models\Settlement;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Export Bets, Markets, Settlements ra CSV → zip → lưu vào backup disk.
 *
 * Usage:
 *   php artisan csv:backup
 *   php artisan csv:backup --type=bets
 *   php artisan csv:backup --type=markets
 *   php artisan csv:backup --type=settlements
 */
class CsvBackupCommand extends Command
{
    protected $signature = 'csv:backup
                            {--type=all : bets | markets | settlements | all}
                            {--label= : Nhãn phân biệt (auto|manual). Mặc định: auto}';

    protected $description = 'Export CSV (Bets/Markets/Settlements) → zip → lưu vào backup disk';

    /** Các cột cho từng loại */
    private const COLUMNS = [
        'bets' => [
            'id', 'public_code', 'user_id', 'season_id', 'match_id',
            'market_id', 'outcome_id', 'stake',
            'market_type_snapshot', 'period_type_snapshot',
            'label_snapshot', 'display_odds_snapshot', 'profit_rate_snapshot',
            'line_snapshot', 'close_at_snapshot',
            'status', 'gross_payout', 'net_result',
            'placed_at', 'settled_at', 'voided_at', 'created_at',
        ],
        'markets' => [
            'id', 'match_id', 'period_type', 'market_type',
            'name', 'open_at', 'close_at', 'status', 'display_order',
            'created_by', 'locked_at', 'settled_at', 'voided_at',
            'void_reason', 'created_at', 'updated_at',
        ],
        'settlements' => [
            'id', 'market_id', 'match_id', 'period_type', 'status',
            'result_home_score', 'result_away_score',
            'total_bets', 'total_stake', 'total_payout',
            'executed_by', 'executed_at', 'reason', 'created_at', 'updated_at',
        ],
    ];

    public function handle(): int
    {
        $type  = $this->option('type');
        $label = $this->option('label') ?? 'auto';
        $types = $type === 'all' ? ['bets', 'markets', 'settlements'] : [$type];

        foreach ($types as $t) {
            if (! array_key_exists($t, self::COLUMNS)) {
                $this->warn("Loại không hợp lệ: {$t}. Bỏ qua.");
                continue;
            }
            $this->exportType($t, $label);
        }

        return Command::SUCCESS;
    }

    private function exportType(string $type, string $label): void
    {
        $timestamp = now()->format('Y-m-d-H-i-s');
        $fileName  = "{$timestamp}-csv-{$type}.zip";

        $diskName  = config('backup.backup.destination.disks')[0] ?? 'local';
        $disk      = Storage::disk($diskName);
        $backupDir = config('backup.backup.name', 'laravel-backup');

        $disk->makeDirectory($backupDir);

        // Temp dir
        $tempDir = storage_path('app/backup-temp');
        if (! is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $csvPath = "{$tempDir}/{$timestamp}-{$type}.csv";
        $zipPath = "{$tempDir}/{$fileName}";

        try {
            // 1. Write CSV
            $this->writeCsv($type, $csvPath);

            // 2. Zip
            $zip = new \ZipArchive();
            if ($zip->open($zipPath, \ZipArchive::CREATE) !== true) {
                throw new \RuntimeException("Không thể tạo file zip: {$zipPath}");
            }
            $zip->addFile($csvPath, basename($csvPath));
            $zip->close();

            // 3. Upload to backup disk
            $disk->putFileAs($backupDir, new \Illuminate\Http\File($zipPath), $fileName);

            // 4. Log to manual_backups.json (để phân biệt type)
            $this->logBackup($fileName, $label, $type);

            $this->info("✓ CSV backup [{$type}] → {$backupDir}/{$fileName}");
        } finally {
            // Cleanup temps
            if (file_exists($csvPath)) unlink($csvPath);
            if (file_exists($zipPath)) unlink($zipPath);
        }
    }

    private function writeCsv(string $type, string $csvPath): void
    {
        $fp = fopen($csvPath, 'w');
        // BOM UTF-8 cho Excel
        fwrite($fp, "\xEF\xBB\xBF");

        $columns = self::COLUMNS[$type];
        fputcsv($fp, $columns);

        $model = match ($type) {
            'bets'        => Bet::class,
            'markets'     => Market::class,
            'settlements' => Settlement::class,
        };

        $model::orderBy('id')->chunk(500, function ($rows) use ($fp, $columns) {
            foreach ($rows as $row) {
                fputcsv($fp, array_map(function ($col) use ($row) {
                    $val = $row->{$col};
                    if ($val instanceof \BackedEnum) return $val->value;
                    if ($val instanceof \Carbon\Carbon) return $val->toDateTimeString();
                    return $val;
                }, $columns));
            }
        });

        fclose($fp);
    }

    private function logBackup(string $filename, string $label, string $type): void
    {
        $disk    = Storage::disk('local');
        $logFile = 'manual_backups.json';
        $existing = $disk->exists($logFile)
            ? (json_decode($disk->get($logFile), true) ?? [])
            : [];

        // Nếu label là manual, ghi vào manual_backups.json để DatabaseBackup nhận dạng
        if ($label === 'manual') {
            $existing[] = $filename;
            $disk->put($logFile, json_encode(array_values($existing)));
        }

        // Luôn ghi vào csv_backups.json để phân biệt type
        $csvLog  = 'csv_backups.json';
        $csvData = $disk->exists($csvLog)
            ? (json_decode($disk->get($csvLog), true) ?? [])
            : [];
        $csvData[$filename] = ['type' => $type, 'label' => $label];
        $disk->put($csvLog, json_encode($csvData));
    }
}
