<?php

namespace App\Console\Commands;

use App\Domain\Leaderboard\Services\LeaderboardService;
use App\Domain\Notification\Services\NotificationService;
use App\Models\Season;
use App\Models\User;
use Illuminate\Console\Command;

class NotifyLeaderboard extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notify:leaderboard {--type=daily : Loại thông báo (daily|weekly)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Gửi thông báo cập nhật bảng xếp hạng cho toàn bộ người chơi (Hằng ngày / Hằng tuần)';

    /**
     * Execute the console command.
     */
    public function handle(LeaderboardService $leaderboardService, NotificationService $notificationService)
    {
        $type = $this->option('type') ?? 'daily';
        
        $season = Season::where('status', 'ACTIVE')->first();
        
        if (!$season) {
            $this->warn('Không có Season nào đang ACTIVE để cập nhật bảng xếp hạng.');
            return;
        }

        $this->info("Đang tính toán lại bảng xếp hạng cho {$type}...");

        // Lấy snapshot mới nhất từ LeaderboardService (đã có rank của từng user)
        $entries = $leaderboardService->computeSeason($season);

        if ($entries->isEmpty()) {
            $this->info('Chưa có dữ liệu bảng xếp hạng để thông báo.');
            return;
        }

        // Lấy tất cả người chơi đang hoạt động
        $users = User::role('player')->where('status', 'ACTIVE')->get();
        $count = 0;

        foreach ($users as $user) {
            // Tìm rank của user này
            $userEntry = $entries->firstWhere('userId', $user->id);
            if ($userEntry) {
                $rank = $userEntry->rank;
                
                if ($type === 'weekly') {
                    if ($rank <= 3) {
                        $msg = "🏆 Chốt hạng Tuần! Xin chúc mừng, bạn đang xuất sắc nằm trong TOP {$rank} của giải đấu!";
                    } elseif ($rank <= 10) {
                        $msg = "Bảng xếp hạng Tuần đã chốt! Bạn đang ở vị trí thứ {$rank}. Cố lên để vào Top 3 nhé!";
                    } else {
                        $msg = "Bảng xếp hạng Tuần đã chốt. Vị trí hiện tại của bạn là {$rank}. Cơ hội vẫn còn ở phía trước!";
                    }
                } else {
                    $msg = "Cập nhật Bảng xếp hạng Ngày. Vị trí hiện tại của bạn là {$rank}. Tiếp tục giữ vững phong độ nhé!";
                }

                $notificationService->notifyLeaderboardUpdated($user, $msg);
                $count++;
            }
        }

        $this->info("Hoàn tất! Đã gửi thông báo {$type} cho {$count} người chơi.");
    }
}
