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

        $payloads = [];
        $now = now();

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
                if ($unbetMatches->count() === 1) {
                    $match = $unbetMatches->first();
                    $title = 'Trận đấu sắp đóng dự đoán';
                    $body = "Trận đấu {$match->home_team} vs {$match->away_team} sắp diễn ra. Đặt cược ngay!";
                    $url = '/player/match/' . $match->id;
                } else {
                    $count = $unbetMatches->count();
                    $title = "Có {$count} trận đấu sắp đóng!";
                    $body = "Có {$count} trận đấu sắp diễn ra. Hãy đưa ra dự đoán của bạn trước khi quá muộn.";
                    $url = '/player/matches';
                }

                $payloads[] = [
                    'id' => (string) \Illuminate\Support\Str::uuid(),
                    'type' => \Filament\Notifications\DatabaseNotification::class,
                    'notifiable_type' => get_class($user),
                    'notifiable_id' => $user->id,
                    'data' => json_encode([
                        'title' => $title,
                        'body' => $body,
                        'icon' => 'heroicon-o-clock',
                        'iconColor' => 'warning',
                        'status' => 'warning',
                        'actions' => [
                            [
                                'name' => 'view',
                                'label' => 'Dự đoán ngay',
                                'url' => $url,
                                'shouldMarkAsRead' => true,
                            ],
                        ],
                    ]),
                    'read_at' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                $notificationsSentCount++;
            }
        }

        if (!empty($payloads)) {
            foreach (array_chunk($payloads, 500) as $chunk) {
                \Illuminate\Support\Facades\DB::table('notifications')->insert($chunk);
            }
        }

        // Cập nhật trạng thái để không bị gửi lại ở lần quét tiếp theo
        FootballMatch::whereIn('id', $upcomingMatches->pluck('id'))->update(['is_closing_notified' => true]);

        $this->info("Hoàn tất! Đã gửi {$notificationsSentCount} thông báo (batch/cá nhân) cho người chơi.");
    }
}
