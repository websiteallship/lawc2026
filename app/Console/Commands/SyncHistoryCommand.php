<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Bet;
use App\Events\BetPlaced;

class SyncHistoryCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:sync-history';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Chạy lại logic cập nhật Nhiệm vụ và Danh hiệu cho toàn bộ vé dự đoán cũ';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $bets = Bet::all();
        $this->info("Bắt đầu đồng bộ " . $bets->count() . " vé dự đoán...");

        $bar = $this->output->createProgressBar($bets->count());

        foreach ($bets as $bet) {
            // Kích hoạt sự kiện BetPlaced cho từng vé để chạy listener Nhiệm vụ và Danh hiệu
            event(new BetPlaced($bet));
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('Đã đồng bộ xong toàn bộ lịch sử vé!');
    }
}
