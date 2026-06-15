<?php

namespace App\Console\Commands;

use App\Domain\Notification\Services\NotificationService;
use App\Models\Bet;
use App\Models\FootballMatch;
use App\Models\User;
use Illuminate\Console\Command;

class NotifyClosingSoonMatches extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notify:closing-soon';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Gửi thông báo nhắc nhở người chơi dự đoán các trận đấu sắp bắt đầu (còn < 30 phút)';

    /**
     * Execute the console command.
     */
    public function handle(NotificationService $notificationService)
    {
        $this->info('Đang kiểm tra các trận đấu sắp diễn ra trong vòng 30 phút tới...');

        // Lấy các trận sắp đá trong 30 phút, chưa bị đánh dấu gửi thông báo
        $upcomingMatches = FootballMatch::where('kickoff_at', '>=', now())
            ->where('kickoff_at', '<=', now()->addMinutes(30))
            ->where('is_closing_notified', false)
            ->whereIn('status', ['NS', 'not_started', 'TIMED']) // Có thể điều chỉnh tuỳ theo API provider
            ->get();

        if ($upcomingMatches->isEmpty()) {
            $this->info('Không có trận nào sắp diễn ra cần gửi thông báo.');
            return;
        }

        $this->info("Tìm thấy {$upcomingMatches->count()} trận sắp đóng. Đang xử lý gửi thông báo...");

        // Lấy tất cả người chơi đang hoạt động
        // Chú ý: Cần import namespace User và role nếu cần, ở đây giả định role name là 'player'
        $users = User::role('player')->where('is_active', true)->get();

        $notificationsSentCount = 0;

        foreach ($users as $user) {
            $unbetMatches = collect();
            
            foreach ($upcomingMatches as $match) {
                // Kiểm tra xem người dùng đã đặt cược vào trận này chưa
                $hasBet = Bet::where('user_id', $user->id)
                    ->where('match_id', $match->id)
                    ->exists();

                if (!$hasBet) {
                    $unbetMatches->push($match);
                }
            }

            // Chỉ bắn thông báo nếu người dùng còn trận chưa bet
            if ($unbetMatches->isNotEmpty()) {
                $notificationService->notifyMatchesClosingSoon($user, $unbetMatches);
                $notificationsSentCount++;
            }
        }

        // Cập nhật trạng thái để không bị gửi lại ở lần quét tiếp theo
        FootballMatch::whereIn('id', $upcomingMatches->pluck('id'))->update(['is_closing_notified' => true]);

        $this->info("Hoàn tất! Đã gửi {$notificationsSentCount} thông báo (batch/cá nhân) cho người chơi.");
    }
}
