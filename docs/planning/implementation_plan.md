# Kế Hoạch Triển Khai Giai Đoạn 2 (Achievement, Mission, Notification)

Kế hoạch triển khai chi tiết cho 3 tính năng lõi của Giai đoạn 2 nhằm mục đích Game hóa và tăng tương tác.

## Proposed Changes

### 1. v1.5.0 - Achievement / Badge Engine
Hệ thống ghi nhận thành tựu cá nhân, không thưởng lá.

#### [NEW] `database/migrations/xxxx_xx_xx_create_achievements_table.php`
- Bảng `achievements`: `code` (string, unique), `name`, `description`, `icon` (string, Filament icon class), `color` (string, Filament color), `is_repeatable` (boolean), `cooldown_period` (enum).
- Bảng `user_achievements`: `user_id`, `achievement_id`, `awarded_at`.

#### [NEW] `database/seeders/AchievementSeeder.php`
- Viết seeder khởi tạo 10 thành tựu mặc định (FIRST_PREDICTION, FIRST_WIN, EXACT_SCORE_MASTER_I/II, WIN_STREAK_3/5, POSITIVE_ROI_QUALIFIED, MARKET_EXPLORER, COMEBACK_PLAYER, FINAL_PREDICTOR) ngay khi chạy seed DB.

#### [NEW] `app/Domain/Achievement/Services/AchievementService.php`
- Hàm `checkAndAward(int $userId)`: Xét 10 thành tựu và lưu vào DB.

#### [NEW] `app/Jobs/CheckAchievementJob.php`
- Queue job xử lý logic bất đồng bộ, gọi `AchievementService::checkAndAward()`.

#### [MODIFY] `app/Providers/EventServiceProvider.php` (hoặc Event Discovery)
- Listen vào `BetPlaced` và `SettlementCompleted` để dispatch `CheckAchievementJob`.

---

### 2. v1.6.0 - Mission Engine
Hệ thống nhiệm vụ tuần/vòng không thưởng lá.

#### [NEW] `database/migrations/xxxx_xx_xx_create_missions_table.php`
- Bảng `missions`: `code`, `title`, `description`, `type`, `target_value`, `start_at`, `end_at`.
- Bảng `user_missions`: `user_id`, `mission_id`, `current_value`, `is_completed`, `completed_at`.

#### [NEW] `database/seeders/MissionSeeder.php`
- Viết seeder khởi tạo 5 nhiệm vụ mặc định (DAILY_ONE_BET, WEEKLY_ACTIVE_PLAYER, WEEKLY_MARKET_EXPLORER, SMART_STAKE, KNOCKOUT_PARTICIPANT).

#### [NEW] `app/Domain/Mission/Services/MissionService.php`
- Hàm `trackProgress(int $userId, string $action, int $value = 1)`
- Hàm `completeMission(int $userId, int $missionId)`

#### [NEW] `app/Jobs/TrackMissionProgressJob.php`
- Queue job hứng event. Gọi `MissionService::trackProgress()`.

---

### 3. v1.7.0 - Notification System & UI/UX Enhancement
Thông báo realtime in-app thông qua cơ chế database của Laravel kết hợp giao diện Filament, tuân thủ nghiêm ngặt UI/UX Pro Max.

#### [NEW] `database/migrations/xxxx_xx_xx_create_notifications_table.php`
- Sử dụng `php artisan notifications:table` mặc định của Laravel.

#### [NEW] `app/Domain/Notification/Services/NotificationService.php`
- **Sử dụng Filament v3 Notifications** (class `Filament\Notifications\Notification`) để gửi song song Database Notifications và Broadcast/Realtime tới UI, kết hợp Notification default của Laravel.
- `notifyMarketClosing()`, `notifySettlementCompleted()`, `notifyAchievementUnlocked()`.

#### [UI/UX] UI/UX Pro Max - Filament Guidelines
- **Khả năng tiếp cận (Accessibility)**: Đảm bảo focus states rõ ràng, color contrast tối thiểu 4.5:1. Sử dụng màu sắc chuẩn của hệ thống Filament (primary, success, warning, danger).
- **Icons & Visuals**: Sử dụng icon set đồng nhất (Heroicons/Lucide). **KHÔNG dùng emoji**. Badge achievement phải có style riêng biệt với animation mượt mà (duration 150-300ms) để wow user.
- **Layout & Spacing**: Bố cục Grid rõ ràng cho trang danh sách Achievement/Mission. Các card hiển thị nhiệm vụ có progress bar hiển thị trực quan (từ 0-100%).
- **Tương tác**: Click/Hover feedback mượt mà trên card (vd: `cursor-pointer transition-all duration-200 hover:ring-2 hover:ring-primary-500`).
- **Thông báo Real-time**: Toast notification góc màn hình hiển thị ngay khi nhận thành tựu mới, sử dụng màu `success` với icon `heroicon-o-trophy`, tự tắt sau 3 giây.

## Verification Plan

### Automated Tests
- Viết Unit Tests cho `AchievementService` và `MissionService`.
- Kiểm thử logic dispatch queue và Event Listeners.

### Manual Verification
- Chạy lệnh db:seed -> Kiểm tra DB có sẵn default Missions và Achievements.
- Đặt cược -> Kiểm tra giao diện tiến độ nhiệm vụ (Mission Progress Card) có cập nhật mượt mà không.
- Chạy Settlement -> Kiểm tra Toast Notification "Thành tựu mới" xuất hiện đúng chuẩn UI/UX Filament (màu sắc đẹp, hiệu ứng chuyển động, không lỗi giật).
