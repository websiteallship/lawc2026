<?php

namespace App\Console\Commands;

use App\Domain\Market\Services\MarketLockService;
use Illuminate\Console\Command;

class LockExpiredMarketsCommand extends Command
{
    protected $signature = 'markets:lock-expired';

    protected $description = 'Khóa các market OPEN đã quá giờ đóng (close_at <= now)';

    public function handle(MarketLockService $service): int
    {
        $count = $service->lockExpiredMarkets();
        $this->info("Đã khóa {$count} market.");

        return Command::SUCCESS;
    }
}
