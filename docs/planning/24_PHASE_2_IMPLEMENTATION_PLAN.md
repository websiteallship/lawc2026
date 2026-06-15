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

Hệ thống nhiệm vụ ngắn hạn giúp người chơi tương tác đều đặn, văn minh (không ép all-in).
**Quy tắc tối thượng:** Không thưởng `lá` qua nhiệm vụ, chỉ thưởng tiến độ hoặc badge (tránh lạm phát lá).

#### [NEW] `database/migrations/xxxx_xx_xx_create_missions_table.php`

- Bảng `missions`: `code`, `title`, `description`, `type` (enum: daily, weekly, round, season), `target_value` (int), `start_at`, `end_at`, `reward_achievement_id` (nullable, FK tới achievements).
- Bảng `user_missions`: `user_id`, `mission_id`, `current_value`, `is_completed`, `completed_at`.

#### [NEW] `database/seeders/MissionSeeder.php`

Khởi tạo 5 nhiệm vụ mặc định:

1. `DAILY_ONE_BET`: Đặt 1 bet mỗi ngày.
2. `WEEKLY_ACTIVE_PLAYER`: Có bet ở ít nhất 3 ngày/tuần.
3. `WEEKLY_MARKET_EXPLORER`: Đặt ít nhất 1 bet ở mỗi loại (EXACT_SCORE, ASIAN_HANDICAP, OVER_UNDER)/tuần.
4. `SMART_STAKE`: Ít nhất 5 bet/tuần, không bet nào vượt 90% available_balance.
5. `KNOCKOUT_PARTICIPANT`: Có ít nhất 1 bet ở vòng Knockout.

#### [NEW] `app/Domain/Mission/Services/MissionService.php`

- `trackProgress(int $userId, string $action, int $value = 1, array $context = [])`: Xử lý tăng tiến độ nhiệm vụ dựa vào hành động.
- `evaluateDailyMissions()`, `evaluateWeeklyMissions()`: Đánh giá và reset tiến độ định kỳ.
- `completeMission(int $userId, int $missionId)`: Đánh dấu hoàn thành. Nếu mission có `reward_achievement_id`, gọi trực tiếp `AchievementService::award(...)`. Đồng thời dispatch event `MissionCompleted` để hệ thống Achievement tổng tổng hợp (VD: Huy hiệu "Hoàn thành 10 nhiệm vụ").

#### [NEW] `app/Jobs/EvaluateMissionsJob.php`

- Chạy bất đồng bộ sau khi Settlement hoặc Bet Placed để cập nhật tiến độ (VD: `TrackMissionProgressJob`).
- Có Console Command `app:evaluate-missions` để chạy qua scheduler.

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

#### [UI/UX] Chiến lược Gamification & Tương tác (Tăng Engagement)

1. **Trang Nhiệm Vụ Độc Lập (Missions Page):**
   - Tách riêng Nhiệm Vụ Tuần thành Menu riêng "Nhiệm vụ tuần" để tạo không gian tương tác chuyên biệt.
   - **Hero Banner:** Countdown timer hiển thị thời gian còn lại của tuần và thanh Tiến độ chung (Hoàn tất x/5).
   - **Giao diện (Pro Max):** Các thẻ nhiệm vụ sử dụng `hover:-translate-y-1 hover:shadow-xl transition-all`, phân loại màu sắc và số sao theo độ khó (Difficulty 1 -> 5).

2. **Cơ chế Random Nhiệm Vụ (Global Rotation):**
   - Tạo sẵn 10 nhiệm vụ tuần với độ khó và phần thưởng khác nhau (từ dễ đến khó).
   - Chạy Job/Command `missions:rotate-weekly` vào đầu mỗi tuần để random chọn 5 nhiệm vụ cho toàn Server.
   - Sắp xếp UI từ dễ đến khó để dẫn dắt hành trình của User (Onboarding -> Hardcore).

3. **Tích hợp Nhiệm vụ vào Trang Danh hiệu (Cross-linking):**
   - Vẫn giữ nguyên phần Cross-linking ở `AchievementsPage` để bảo toàn tính tương tác 1-1. Dưới Danh hiệu đang bị khóa, hiển thị trực tiếp tiến độ Nhiệm vụ tương ứng (nếu có).

4. **Hiệu ứng Micro-interactions (WOW factor):**
   - **Unlock Animation:** Khi đạt đủ 100% thanh tiến độ, chuyển thẻ sang màu `success`, bật icon checkmark và bắn hiệu ứng Confetti.
   - **Real-time Toast:** Push Toast notification ngay khi có tương tác tiến độ: *"🔥 2/5 vé tuần đã xong! Cố lên!"*

5. **Floating Mini-Widget (Dự kiến):**
   - Một widget nhỏ góc dưới màn hình hoặc trên bảng điều khiển để hiển thị tiến độ nhiệm vụ đang gần hoàn thành nhất.
8. **Nhận biết Hoàn thành Nhiệm vụ (Completion State):**
   - **Thay đổi Trạng thái Thẻ:** Đổi màu nền sang Xanh nhạt (`bg-success-50`), viền phát sáng nhẹ. Thay icon mục tiêu thành Dấu tick xanh đặc (`heroicon-s-check-circle`). Thanh tiến độ đẩy lên 100%, màu Xanh lá, text ghi *"Hoàn tất"*.
   - **Hiệu ứng "Đóng dấu" (Micro-animation):** Ngay khi thanh tiến độ đầy, kích hoạt hiệu ứng CSS một con dấu "DONE" đập xuống thẻ, hoặc icon phần thưởng nảy lên (Bounce).
   - **Tự động sắp xếp (Auto-sorting):** Các nhiệm vụ đã xong lập tức bị đẩy xuống cuối danh sách (có dải phân cách) để nhường không gian phía trên.
   - **Nổi bật Phần thưởng:** Nếu nhiệm vụ có thưởng Huy hiệu, bóng mờ (Grayscale) của huy hiệu vỡ ra, chuyển thành màu sắc đầy đủ kèm CTA: *"Xem Danh hiệu"*.

## Verification Plan

### Automated Tests

- Viết Unit Tests cho `AchievementService` và `MissionService`.
- Kiểm thử logic dispatch queue và Event Listeners.

### Manual Verification

- Chạy lệnh db:seed -> Kiểm tra DB có sẵn default Missions và Achievements.
- Đặt cược -> Kiểm tra giao diện tiến độ nhiệm vụ (Mission Progress Card) có cập nhật mượt mà không.
- Chạy Settlement -> Kiểm tra Toast Notification "Thành tựu mới" xuất hiện đúng chuẩn UI/UX Filament (màu sắc đẹp, hiệu ứng chuyển động, không lỗi giật).
