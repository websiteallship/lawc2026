<?php

namespace Database\Seeders;

use App\Models\Mission;
use Illuminate\Database\Seeder;

class MissionSeeder extends Seeder
{
    public function run(): void
    {
        $achievements = \App\Models\Achievement::pluck('id', 'code');

        $missions = [
            // --- DAILY MISSIONS ---
            [
                'code' => 'DAILY_ONE_BET',
                'title' => 'Dự đoán mỗi ngày',
                'description' => 'Hoàn thành 1 vé dự đoán hợp lệ trong ngày hôm nay.',
                'type' => 'daily',
                'target_value' => 1,
                'difficulty' => 1,
            ],
            // --- WEEKLY MISSIONS (10 cố định) ---
            [
                'code' => 'WEEKLY_W1',
                'title' => 'Tân binh năng nổ',
                'description' => 'Tham gia đặt dự đoán ít nhất 2 vé trong tuần.',
                'type' => 'weekly',
                'target_value' => 2,
                'difficulty' => 1,
            ],
            [
                'code' => 'WEEKLY_W2',
                'title' => 'Cú đêm chăm chỉ',
                'description' => 'Đặt cược trong khung giờ từ 0h đến 5h sáng ở 2 đêm.',
                'type' => 'weekly',
                'target_value' => 2,
                'difficulty' => 1,
                'reward_achievement_id' => $achievements['NIGHT_OWL'] ?? null,
            ],
            [
                'code' => 'WEEKLY_W3',
                'title' => 'Chuyên gia đa dạng',
                'description' => 'Tham gia ít nhất 1 vé ở mỗi loại kèo (Tỉ số, Handicap, Tài Xỉu) trong tuần.',
                'type' => 'weekly',
                'target_value' => 3,
                'difficulty' => 2,
                'reward_achievement_id' => $achievements['MULTI_MARKET'] ?? null,
            ],
            [
                'code' => 'WEEKLY_W4',
                'title' => 'Người chơi bền bỉ',
                'description' => 'Tham gia đặt dự đoán ít nhất 4 ngày khác nhau trong tuần.',
                'type' => 'weekly',
                'target_value' => 4,
                'difficulty' => 2,
            ],
            [
                'code' => 'WEEKLY_W5',
                'title' => 'Tay bắn tỉa',
                'description' => 'Thắng ít nhất 3 vé dự đoán trong tuần.',
                'type' => 'weekly',
                'target_value' => 3,
                'difficulty' => 3,
            ],
            [
                'code' => 'WEEKLY_W6',
                'title' => 'Kẻ chinh phục chuỗi thắng',
                'description' => 'Đoán trúng 3 lần liên tiếp.',
                'type' => 'weekly',
                'target_value' => 3,
                'difficulty' => 3,
                'reward_achievement_id' => $achievements['WIN_STREAK_3'] ?? null,
            ],
            [
                'code' => 'WEEKLY_W7',
                'title' => 'Chuyên gia soi kèo',
                'description' => 'Thắng ít nhất 5 vé dự đoán trong tuần.',
                'type' => 'weekly',
                'target_value' => 5,
                'difficulty' => 4,
            ],
            [
                'code' => 'WEEKLY_W8',
                'title' => 'Tay chơi lớn',
                'description' => 'Tổng mức đặt cược trong tuần đạt 1,000,000 lá.',
                'type' => 'weekly',
                'target_value' => 1000000,
                'difficulty' => 4,
            ],
            [
                'code' => 'WEEKLY_W9',
                'title' => 'Bậc thầy tỉ số',
                'description' => 'Đoán chính xác tỉ số của 1 trận đấu trong tuần.',
                'type' => 'weekly',
                'target_value' => 1,
                'difficulty' => 5,
            ],
            [
                'code' => 'WEEKLY_W10',
                'title' => 'Huyền thoại tuần',
                'description' => 'Thắng liên tiếp 5 dự đoán.',
                'type' => 'weekly',
                'target_value' => 5,
                'difficulty' => 5,
                'reward_achievement_id' => $achievements['WIN_STREAK_5'] ?? null,
            ],
            // --- SEASON MISSIONS ---
            [
                'code' => 'KNOCKOUT_PARTICIPANT',
                'title' => 'Tiến vào Vòng trong',
                'description' => 'Tham gia đặt dự đoán ít nhất 1 vé ở trận đấu thuộc vòng Knockout.',
                'type' => 'season',
                'target_value' => 1,
                'difficulty' => 3,
            ]
        ];

        foreach ($missions as $mission) {
            Mission::updateOrCreate(['code' => $mission['code']], $mission);
        }
    }
}
