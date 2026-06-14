<?php

namespace App\Console\Commands;

use App\Domain\Match\Services\MatchSyncService;
use Illuminate\Console\Command;

class SyncApiMatchCommand extends Command
{
    protected $signature = 'matches:sync-api {--live : Chỉ đồng bộ tỉ số trực tiếp} {--schedule : Chỉ đồng bộ lịch thi đấu} {--historic : Kéo lại toàn bộ lịch sử (sẽ ghi đè dữ liệu cũ)}';

    protected $description = 'Kéo dữ liệu API thủ công (Lịch thi đấu & Tỉ số & Lịch sử)';

    public function handle(MatchSyncService $syncService)
    {
        if ($this->option('historic')) {
            $this->info("Đang đồng bộ lại toàn bộ dữ liệu lịch sử từ API...");
            try {
                $syncService->syncAllHistoricMatches();
                $this->info("Đồng bộ dữ liệu lịch sử thành công.");
            } catch (\Exception $e) {
                $this->error('Lỗi đồng bộ quá khứ: '.$e->getMessage());
                return 1;
            }
            return 0;
        }

        $this->info('Đang đồng bộ API Live Score...');

        try {
            if ($this->option('schedule')) {
                $syncService->syncSchedules();
                $this->info('Đồng bộ lịch thi đấu thành công.');
            } elseif ($this->option('live')) {
                $syncService->syncLiveScores();
                $this->info('Đồng bộ tỉ số trực tiếp thành công.');
            } else {
                $syncService->syncSchedules();
                $syncService->syncLiveScores();
                $this->info('Đồng bộ lịch và tỉ số thành công.');
            }
        } catch (\Exception $e) {
            $this->error('Lỗi đồng bộ: '.$e->getMessage());
            return 1;
        }

        return 0;
    }
}
