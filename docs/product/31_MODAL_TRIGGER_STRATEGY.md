---
title: "31 – Modal Trigger Strategy (6 Modal Types)"
status: draft
phase: 2
created: 2026-06-17
depends_on:
  - "30_DAILY_LOGIN_WELCOME_MODAL.md"
  - "23_PHASE_2_ENHANCEMENT_SPEC.md"
---

# Modal Trigger Strategy – Phân tách 6 Loại Modal

## 1. Nguyên tắc thiết kế

**Mỗi modal chỉ phục vụ MỘT mục tiêu hành vi cụ thể.**

| Nguyên tắc | Giải thích |
|---|---|
| Right time | Hiện đúng lúc user cần, không hiện lúc bận |
| Single intent | Mỗi modal chỉ nói về 1 chủ đề |
| Non-blocking | Không chặn tác vụ user đang làm (trừ celebration) |
| Dismissable | Luôn có nút đóng nhanh |
| Anti-spam | Mỗi loại có cooldown riêng, tránh chồng nhau |

---

## 2. Bản đồ 6 Modal Theo Trigger

```
TRIGGER                        MODAL                         MỤC TIÊU
─────────────────────────────────────────────────────────────────────
Lần đầu mở trong ngày ───────► [M1] Daily Briefing           Định hướng ngày
                                                              
Lần đầu mở trong ngày ───────► [M6] Daily Ranking            Thứ hạng hôm nay
(hiện sau M1)                                                 
                                                              
Có achievement / mission ─────► [M2] Celebration             Khen thưởng
mới trong vòng 24h                                            
                                                              
Trận OPEN mà user ────────────► [M3] Match Reminder          Nhắc cược
chưa cược + sắp đóng kèo                                     
                                                              
Sau khi settlement ───────────► [M4] Settlement Summary      Tổng kết vé
kết thúc (vé có kết quả)                                     
                                                              
Sau N ngày không vào ─────────► [M5] Re-engagement          Kéo user quay lại
```

---

## 3. Chi tiết từng Modal

---

### M1 – Daily Briefing (Tóm tắt ngày mới)

**Trigger:**
- Lần đầu tiên navigate vào Player panel trong ngày (theo giờ HCM UTC+7).
- Điều kiện: `last_daily_briefing_at < today_start_hcm`.

**Không trigger khi:**
- User vào trang login nhưng chưa vào dashboard.
- User đã xem hôm nay.

**Cooldown:** Reset mỗi ngày lúc `00:00 Asia/Ho_Chi_Minh`.

**Nội dung:**
```
┌──────────────────────────────────────────┐
│  ☀️  Chào [Tên], hôm nay [Thứ D/M]!     │
│  ─────────────────────────────────────── │
│  📊  Lá: 12,500  |  Hạng: #5  |  +2,300│
│  ─────────────────────────────────────── │
│  📋  NHIỆM VỤ HÔM NAY                   │
│  · Dự đoán trong ngày     [0/1] chưa    │
│  · Khám phá đủ kèo        [2/3] 67%     │
│  ─────────────────────────────────────── │
│  ⚽  TRẬN SẮP ĐÓ KÈO                   │
│  · Brazil vs Pháp  — đóng sau 2h15p     │
│  · Argentina vs Đức — đóng sau 5h30p    │
│  ─────────────────────────────────────── │
│         [Vào chơi ngay]   [Đóng]         │
└──────────────────────────────────────────┘
```

**UX Rules:**
- Hiện sau 800ms delay (sau khi page render xong).
- Kích thước: `max-w-lg`, scrollable nếu nhiều trận.
- Mobile: bottom sheet slide-up.
- Không có confetti (không có gì để ăn mừng).

**DB tracking:**
```sql
users.last_daily_briefing_at TIMESTAMP NULL
```

---

### M2 – Celebration Modal (Ăn mừng thành tựu / nhiệm vụ)

**Trigger (theo thứ tự ưu tiên):**

| Priority | Event | Nguồn trigger |
|---|---|---|
| 1 | Achievement mới được trao | `AchievementService` dispatch event |
| 2 | Mission mới completed | `MissionService` dispatch event |

**Thời điểm hiện:**
- Ngay khi user đang online và event được dispatch (real-time qua Livewire event hoặc polling).
- Hoặc: lần đầu navigate vào bất kỳ trang nào sau khi có achievement/mission mới (nếu offline lúc đó).

**Cooldown:** Không có cooldown — mỗi achievement/mission là một lần celebration riêng. Nhưng nếu có nhiều achievement cùng lúc → gộp vào 1 modal.

**Nội dung (Achievement):**
```
┌──────────────────────────────────────────┐
│          🎆  THÀNH TỰU MỚI!             │
│                                          │
│  [🏅 GLOW] Chuỗi thắng 3               │
│              Bậc thầy phong độ           │
│                                          │
│  [🏅 GLOW] Bắt đúng tỉ số              │
│              Siêu dự đoán               │
│                                          │
│      [Xem tất cả thành tựu]  [Đóng]     │
└──────────────────────────────────────────┘
[CONFETTI FULL SCREEN 3 giây]
```

**Nội dung (Mission completed):**
```
┌──────────────────────────────────────────┐
│          🎯  NHIỆM VỤ HOÀN THÀNH!       │
│                                          │
│  ✅ "Khám phá đủ kèo"                   │
│     Phần thưởng: 🎖️ Badge Đa Dạng      │
│                                          │
│  ✅ "5 vé thắng trong tuần"             │
│     Phần thưởng: 🎖️ Tay Săn Lá Vàng    │
│                                          │
│            [Tuyệt vời!]                  │
└──────────────────────────────────────────┘
[FIREWORKS + CHECKMARK ANIMATION]
```

**UX Rules:**
- Animation: confetti (achievement) hoặc fireworks (mission completed).
- Delay xuất hiện: 500ms sau navigate.
- Không auto-dismiss — user phải bấm đóng.
- Nếu có cả achievement + mission cùng lúc: hiện theo thứ tự achievement trước, mission sau (2 modal liên tiếp, cách nhau 1 giây).

**DB tracking:**
```sql
-- Dùng lại UserAchievement.awarded_at và UserMission.completed_at
-- So sánh với users.last_celebration_shown_at
users.last_celebration_shown_at TIMESTAMP NULL
```

**Implement:** Livewire event `$dispatch('open-celebration-modal', [...])` từ server event / polling.

---

### M3 – Match Reminder (Nhắc trận sắp đóng kèo)

**Trigger:** Tất cả đồng thời phải đúng:
1. Có market OPEN mà user **chưa có bet** trong trận đó.
2. `market.close_at` trong khoảng **[now + 30 phút, now + X giờ]** (X configurable, mặc định 5h).
3. Lần cuối nhắc trận này (`match_reminder_sent_at` cho user+match) đã qua **4 giờ** (cooldown).

**Khi nào hiện:**
- Không hiện chủ động (không interrupt user đang làm).
- Hiện khi user navigate vào Dashboard hoặc Match List.
- Hoặc: nút "Chuông" trong header có badge đỏ, user click vào thì thấy.

**Nội dung:**
```
┌──────────────────────────────────────────┐
│  ⏰  ĐỪNG LỠ TRẬN NÀY!                  │
│                                          │
│  🇧🇷 Brazil  vs  🇫🇷 Pháp  🇫🇷           │
│  Đóng kèo: còn 45 phút                  │
│  Bạn chưa dự đoán trận này!             │
│                                          │
│  🇦🇷 Argentina vs 🇩🇪 Đức                │
│  Đóng kèo: còn 1 giờ 20 phút           │
│  Bạn chưa dự đoán trận này!             │
│                                          │
│  [Dự đoán ngay]  [Nhắc lại sau]  [Bỏ]  │
└──────────────────────────────────────────┘
```

**UX Rules:**
- Hiện dạng toast notification nhỏ ở góc màn hình (không phải full modal) nếu chỉ 1 trận.
- Hiện modal đầy đủ nếu >= 2 trận sắp đóng.
- Badge "Sắp đóng" màu đỏ pulse nếu < 30 phút.
- Nút "Nhắc lại sau" → snooze 30 phút.

**DB tracking:**
```sql
-- Bảng mới nhẹ
match_reminders (
  id, user_id, match_id, reminded_at, snoozed_until, dismissed_at
)
```

**Config (AppSettings):**
```php
public int $match_reminder_window_hours = 5;   // Nhắc khi còn X giờ
public int $match_reminder_cooldown_hours = 4;  // Không nhắc lại trong Y giờ
public bool $match_reminder_enabled = true;
```

---

### M4 – Settlement Summary (Tổng kết kết quả vé)

**Trigger:** Có bet(s) của user vừa được settled mà user chưa xem kết quả.

**Thời điểm hiện:**
- Lần đầu navigate vào bất kỳ trang nào sau settlement (so sánh `last_settlement_summary_shown_at` với `bets.settled_at` mới nhất).
- Không hiện real-time trong khi settlement đang chạy.

**Nội dung:**
```
┌──────────────────────────────────────────┐
│  📬  KẾT QUẢ VÉ DỰ ĐOÁN CỦA BẠN       │
│                                          │
│  Brazil vs Pháp — FT 2-1                │
│  🟢 WON   Brazil -0.5  →  +90 lá       │
│  🟢 WON   Tài 2.5      →  +90 lá       │
│  🔴 LOST  Tỉ số 2-1    →  -100 lá      │
│  ──────────────────────────────────      │
│  Kết quả phiên này:  +80 lá 🎉          │
│                                          │
│         [Xem lịch sử]   [Đóng]           │
└──────────────────────────────────────────┘
```

**UX Rules:**
- Nếu tổng kết quả > 0: background xanh lá + confetti nhỏ (không to bằng M2).
- Nếu tổng kết quả < 0: background đỏ nhẹ, không có confetti.
- Nếu push/void tất cả: background xám trung tính.
- Gộp tất cả bet settled từ cùng 1 settlement vào 1 modal.
- Nếu nhiều settlement chưa xem: hiện từng cái một, next/prev.

**DB tracking:**
```sql
users.last_settlement_summary_shown_at TIMESTAMP NULL
```

---

### M5 – Re-engagement (Kéo user quay lại)

**Trigger:** Tất cả đồng thời phải đúng:
1. User không navigate vào player panel trong **N ngày** (mặc định 3 ngày).
2. Đang có market OPEN (có trận để chơi).
3. User chưa nhận re-engagement modal trong **7 ngày**.

**Thời điểm hiện:** Ngay khi user login trở lại.

**Nội dung:**
```
┌──────────────────────────────────────────┐
│  👋  Lâu rồi không gặp!                │
│                                          │
│  Bạn đã vắng mặt 5 ngày...             │
│                                          │
│  📊 Bảng XH đã thay đổi:               │
│  Hạng của bạn: #5 → #8 (giảm 3 hạng)  │
│                                          │
│  ⚽ Đang có 12 trận chờ dự đoán!       │
│  🎯 Nhiệm vụ tuần chỉ còn 2 ngày!     │
│                                          │
│    [Vào xem ngay]   [Nhắc sau 1 ngày]  │
└──────────────────────────────────────────┘
```

**UX Rules:**
- Friendly, không tạo cảm giác áp lực.
- Chỉ hiện 1 lần mỗi 7 ngày dù user vẫn không vào.
- Không hiện nếu không có trận nào đang mở.

**DB tracking:**
```sql
users.last_reengagement_shown_at TIMESTAMP NULL
users.last_active_at TIMESTAMP NULL  -- cập nhật mỗi lần navigate
```

---

### M6 – Daily Ranking (Thứ hạng ngày mới)

**Trigger:**
- Lần đầu tiên navigate vào Player panel trong ngày (cùng điều kiện M1).
- Điều kiện: `last_daily_ranking_shown_at < today_start_hcm`.
- Hiện **sau M1** (M1 dismiss xong → delay 1.5s → M6 hiện).

**Cooldown:** Reset mỗi ngày lúc `00:00 Asia/Ho_Chi_Minh`.

**DB tracking:**
```sql
users.last_daily_ranking_shown_at TIMESTAMP NULL
```

---

#### Thông điệp theo hạng

| Hạng | Biểu tượng | Tiêu đề | Thông điệp chính | Thông điệp phụ |
|---|---|---|---|---|
| #1 | 🥇👑 | **VÔ ĐỊCH!** | Bạn đang dẫn đầu bảng xếp hạng! | "Giữ vững ngôi vương nhé, cả team đang nhìn bạn đó!" |
| #2 | 🥈⚡ | **Á QUÂN!** | Chỉ cách ngôi vương một bước! | "Hôm nay là ngày lật ngược thế cờ không? Cơ hội đang ở đây!" |
| #3 | 🥉🔥 | **TOP 3!** | Bạn đang trong nhóm tinh hoa! | "Podium trong tầm tay — thêm vài vé thắng là soán ngôi ngay!" |
| #4–#10 | 🎯 | **NHÓM ĐUỔI** | Bạn đang ở hạng #{rank}! | "Chỉ cách top 3 {gap} lá — một phiên hay là đủ!" |
| #11–#20 | 💪 | **ĐANG TIẾN** | Hạng #{rank}, chưa vào top 10! | "Mỗi vé thắng là một bước leo hạng. Hôm nay thử sức nhé!" |
| Bottom 50% | 🌱 | **ĐANG HỌC** | Hạng #{rank} — vẫn còn cả một hành trình! | "Đừng lo, mọi cao thủ đều bắt đầu từ dưới. Chiến tiếp!" |
| Chưa có vé | 🆕 | **CHÀO NGƯỜI MỚI!** | Bạn chưa dự đoán trận nào! | "Một vé đầu tiên — bước đầu của huyền thoại. Thử ngay nhé!" |

---

#### Wireframe theo từng hạng

**Top 1:**
```
┌──────────────────────────────────────────┐
│  👑🥇  VÔ ĐỊCH!                          │
│  ════════════════════════════════════    │
│  Bạn đang DẪN ĐẦU bảng xếp hạng!       │
│                                          │
│  ┌────────────────────────────────────┐  │
│  │  Hạng  #1    Lãi ròng  +5,200 lá  │  │
│  │  Win rate 68%    ROI  +24%         │  │
│  └────────────────────────────────────┘  │
│                                          │
│  "Giữ vững ngôi vương nhé,              │
│   cả team đang nhìn bạn đó!" 💪         │
│                                          │
│  🏆 Top 3 mùa giải:                     │
│  1. [Bạn]        +5,200 lá  ← YOU      │
│  2. Voi nhanh    +4,800 lá              │
│  3. Hổ dũng      +3,900 lá              │
│                                          │
│  [Xem bảng xếp hạng đầy đủ]  [Đóng]   │
└──────────────────────────────────────────┘
[CONFETTI 🎆 + CROWN GLOW ANIMATION]
```

**Top 2:**
```
┌──────────────────────────────────────────┐
│  🥈⚡  Á QUÂN — Sát nút ngôi vương!    │
│  ════════════════════════════════════    │
│  Hạng #2 — Chỉ cách #1 một bước!       │
│                                          │
│  ┌────────────────────────────────────┐  │
│  │  Hạng  #2    Lãi ròng  +4,800 lá  │  │
│  │  Kém #1: -400 lá                  │  │
│  └────────────────────────────────────┘  │
│                                          │
│  "Hôm nay là ngày lật ngược thế cờ     │
│   không? Cơ hội đang ở đây!" ⚡        │
│                                          │
│  🏆 Top 3:                              │
│  1. Sư tử mạnh   +5,200 lá             │
│  2. [Bạn]        +4,800 lá  ← YOU      │
│  3. Hổ dũng      +3,900 lá              │
│                                          │
│  [Dự đoán ngay]          [Đóng]         │
└──────────────────────────────────────────┘
[SILVER SHIMMER + CONFETTI NHỎ]
```

**Top 3:**
```
┌──────────────────────────────────────────┐
│  🥉🔥  TOP 3 — Bạn đang trong           │
│          nhóm tinh hoa!                  │
│  ════════════════════════════════════    │
│  Hạng #3 — Podium trong tầm tay!        │
│                                          │
│  ┌────────────────────────────────────┐  │
│  │  Hạng  #3    Lãi ròng  +3,900 lá  │  │
│  │  Kém #2: -900 lá                  │  │
│  └────────────────────────────────────┘  │
│                                          │
│  "Thêm vài vé thắng là soán ngôi ngay!"│
│                                          │
│  🏆 Top 3:                              │
│  1. Sư tử mạnh   +5,200 lá             │
│  2. Voi nhanh    +4,800 lá              │
│  3. [Bạn]        +3,900 lá  ← YOU      │
│                                          │
│  [Dự đoán ngay]          [Đóng]         │
└──────────────────────────────────────────┘
[BRONZE GLOW + CONFETTI NHỎ]
```

**Ngoài top 3 (ví dụ hạng #7):**
```
┌──────────────────────────────────────────┐
│  🎯  Hạng #7 — Nhóm bám đuổi!          │
│  ════════════════════════════════════    │
│                                          │
│  ┌────────────────────────────────────┐  │
│  │  Hạng  #7    Lãi ròng  +1,200 lá  │  │
│  │  Kém #3: -2,700 lá                │  │
│  └────────────────────────────────────┘  │
│                                          │
│  "Chỉ cách top 3 2,700 lá —            │
│   một phiên hay là đủ!" 💡             │
│                                          │
│  📊 Top 3 hiện tại:                     │
│  1. Sư tử mạnh   +5,200 lá  (+4,000)  │
│  2. Voi nhanh    +4,800 lá  (+3,600)  │
│  3. Hổ dũng      +3,900 lá  (+2,700)  │
│  ...                                     │
│  7. [Bạn]        +1,200 lá  ← YOU      │
│                                          │
│  [Xem bảng đầy đủ]       [Đóng]         │
└──────────────────────────────────────────┘
```

---

#### UX Rules

- **Animation theo hạng:**
  - Hạng #1: Confetti đầy đủ + crown glow pulse.
  - Hạng #2: Silver shimmer + confetti nhỏ.
  - Hạng #3: Bronze glow + confetti nhỏ.
  - Hạng #4–#10: Slide-in animation, không confetti.
  - Hạng #11+: Fade-in đơn giản.
- Mini leaderboard top 3 luôn hiển thị để tạo tham khảo.
- Highlight hàng của user bằng nền màu accent.
- Hiển thị delta (khoảng cách) so với hạng trên để tạo động lực cụ thể.
- Chỉ tính leaderboard mùa giải (season) cho modal này — không tính tuần.
- Dữ liệu lấy từ `LeaderboardSnapshot` mới nhất (tránh query nặng).

---

#### Data cần cho M6

```php
// Dữ liệu cần lấy
[
    'user_rank'       => int,          // Hạng hiện tại của user
    'user_net_profit' => int,          // Lãi ròng hiện tại
    'total_players'   => int,          // Tổng số người chơi
    'top3'            => [             // Top 3 để hiển thị mini leaderboard
        ['rank'=>1, 'name'=>..., 'net_profit'=>..., 'avatar'=>...],
        ['rank'=>2, ...],
        ['rank'=>3, ...],
    ],
    'gap_to_above'    => int|null,     // Khoảng cách lá so với hạng trên (null nếu hạng 1)
    'message_key'     => string,       // 'top1'|'top2'|'top3'|'chasing'|'climbing'|'learning'|'new'
]
```

---

## 4. Ma Trận Ưu tiên Khi Nhiều Modal Cùng Trigger

Nếu tại cùng 1 thời điểm nhiều modal đều đủ điều kiện, ưu tiên theo thứ tự:

```
Priority 1: M2 Celebration (achievement/mission) — quan trọng nhất, user mong đợi
Priority 2: M4 Settlement Summary — user muốn biết kết quả ngay
Priority 3: M1 Daily Briefing — thông tin định hướng ngày (luôn trước M6)
Priority 4: M6 Daily Ranking — thứ hạng (hiện ngay sau M1)
Priority 5: M3 Match Reminder — chỉ nhắc, không khẩn cấp
Priority 6: M5 Re-engagement — thấp nhất
```

**Rule xử lý:**
- Chỉ hiện 1 modal tại 1 thời điểm.
- Modal kế tiếp (nếu còn) hiện sau khi modal hiện tại bị dismiss.
- Giữa 2 modal liên tiếp: delay 1.5 giây.
- M1 và M6 luôn đi cặp (M6 hiện ngay sau M1 dismiss).

---

## 5. Tracking State – Các Cột Cần Thêm Vào `users`

```sql
ALTER TABLE users ADD COLUMN last_daily_briefing_at       TIMESTAMP NULL;
ALTER TABLE users ADD COLUMN last_daily_ranking_shown_at  TIMESTAMP NULL;
ALTER TABLE users ADD COLUMN last_celebration_shown_at    TIMESTAMP NULL;
ALTER TABLE users ADD COLUMN last_settlement_summary_shown_at TIMESTAMP NULL;
ALTER TABLE users ADD COLUMN last_reengagement_shown_at   TIMESTAMP NULL;
ALTER TABLE users ADD COLUMN last_active_at               TIMESTAMP NULL;
```

Migration duy nhất:

```php
Schema::table('users', function (Blueprint $table) {
    $table->timestamp('last_daily_briefing_at')->nullable()->after('last_login_at');
    $table->timestamp('last_daily_ranking_shown_at')->nullable()->after('last_daily_briefing_at');
    $table->timestamp('last_celebration_shown_at')->nullable()->after('last_daily_ranking_shown_at');
    $table->timestamp('last_settlement_summary_shown_at')->nullable()->after('last_celebration_shown_at');
    $table->timestamp('last_reengagement_shown_at')->nullable()->after('last_settlement_summary_shown_at');
    $table->timestamp('last_active_at')->nullable()->after('last_reengagement_shown_at');
});
```

---

## 6. Cooldown Matrix

| Modal | Cooldown | Reset tại |
|---|---|---|
| M1 Daily Briefing | 1 lần/ngày | 00:00 Asia/Ho_Chi_Minh |
| M6 Daily Ranking | 1 lần/ngày (cặp với M1) | 00:00 Asia/Ho_Chi_Minh |
| M2 Celebration | Không có cooldown | Mỗi event mới = 1 lần |
| M3 Match Reminder | 4 giờ / trận | Rolling |
| M4 Settlement Summary | Mỗi settlement mới | Sau khi xem |
| M5 Re-engagement | 7 ngày | Rolling |

---

## 7. Livewire Architecture

### Component duy nhất quản lý queue

```
app/Livewire/Player/ModalOrchestrator.php
```

Mounted 1 lần trong `BODY_END` renderHook. Tự kiểm tra thứ tự ưu tiên, emit từng modal theo queue.

```php
class ModalOrchestrator extends Component
{
    public ?string $currentModal = null; // 'daily', 'ranking', 'celebration', 'settlement', 'reminder', 'reengagement'
    public array $queue = [];
    public array $modalData = [];

    public function mount(): void
    {
        $this->buildQueue();
        $this->showNext();
    }

    public function dismiss(): void
    {
        $this->markCurrentAsSeen();
        $this->currentModal = null;
        // Delay rồi show next
        $this->dispatch('modal-dismissed');
    }

    private function buildQueue(): void
    {
        $user = auth()->user();
        $checkers = [
            'celebration'   => app(CelebrationModalChecker::class),
            'settlement'    => app(SettlementSummaryChecker::class),
            'daily'         => app(DailyBriefingChecker::class),
            'ranking'       => app(DailyRankingChecker::class),
            'reminder'      => app(MatchReminderChecker::class),
            'reengagement'  => app(ReengagementChecker::class),
        ];

        foreach ($checkers as $type => $checker) {
            if ($checker->shouldShow($user)) {
                $this->queue[] = $type;
                $this->modalData[$type] = $checker->getData($user);
            }
        }
    }
}
```

### 6 Checker classes (thin, testable)

```
app/Domain/Modal/Checkers/
  DailyBriefingChecker.php
  DailyRankingChecker.php
  CelebrationChecker.php
  MatchReminderChecker.php
  SettlementSummaryChecker.php
  ReengagementChecker.php
```

Mỗi class implement:
```php
interface ModalCheckerInterface
{
    public function shouldShow(User $user): bool;
    public function getData(User $user): array;
    public function markAsSeen(User $user): void;
}
```

---

## 8. Flow Diagram

```
User navigate vào Player Panel
        │
        ▼
ModalOrchestrator::mount()
        │
        ▼
Build queue (check 6 conditions theo priority)
        │
        ├─ Có Celebration? → queue[0] = 'celebration'
        ├─ Có Settlement?  → queue[1] = 'settlement'
        ├─ Có Daily?       → queue[2] = 'daily'
        ├─ Có Ranking?     → queue[3] = 'ranking'   ← cặp với daily
        ├─ Có Reminder?    → queue[4] = 'reminder'
        └─ Có Reengagement?→ queue[5] = 'reengagement'
                │
                ▼
        showNext() → render modal đầu tiên trong queue
                │
        User dismiss()
                │
                ▼
        markAsSeen() + delay 1.5s
                │
                ▼
        showNext() → modal tiếp theo (nếu còn)
                │
        Queue rỗng → done
```

---

## 9. Files Cần Tạo

| File | Loại |
|---|---|
| `app/Livewire/Player/ModalOrchestrator.php` | Livewire |
| `resources/views/livewire/player/modal-orchestrator.blade.php` | Blade |
| `resources/views/livewire/player/modals/daily-briefing.blade.php` | Blade partial |
| `resources/views/livewire/player/modals/daily-ranking.blade.php` | Blade partial |
| `resources/views/livewire/player/modals/celebration.blade.php` | Blade partial |
| `resources/views/livewire/player/modals/match-reminder.blade.php` | Blade partial |
| `resources/views/livewire/player/modals/settlement-summary.blade.php` | Blade partial |
| `resources/views/livewire/player/modals/reengagement.blade.php` | Blade partial |
| `app/Domain/Modal/Checkers/DailyBriefingChecker.php` | Checker |
| `app/Domain/Modal/Checkers/DailyRankingChecker.php` | Checker |
| `app/Domain/Modal/Checkers/CelebrationChecker.php` | Checker |
| `app/Domain/Modal/Checkers/MatchReminderChecker.php` | Checker |
| `app/Domain/Modal/Checkers/SettlementSummaryChecker.php` | Checker |
| `app/Domain/Modal/Checkers/ReengagementChecker.php` | Checker |
| `app/Domain/Modal/Contracts/ModalCheckerInterface.php` | Interface |
| `database/migrations/xxxx_add_modal_tracking_to_users.php` | Migration |
| `database/migrations/xxxx_create_match_reminders_table.php` | Migration |
