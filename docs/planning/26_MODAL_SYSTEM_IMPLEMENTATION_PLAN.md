---
title: "26 – Modal System Implementation Plan"
status: draft
phase: 2
created: 2026-06-17
refs:
  - "docs/product/30_DAILY_LOGIN_WELCOME_MODAL.md"
  - "docs/product/31_MODAL_TRIGGER_STRATEGY.md"
---

# Kế Hoạch Triển Khai Modal Engagement System

## Tổng quan

Triển khai hệ thống 6 modal tương tác cho Player Panel:

| ID | Tên | Trigger |
|---|---|---|
| M1 | Daily Briefing | Lần đầu trong ngày |
| M2 | Celebration | Achievement/Mission mới |
| M3 | Match Reminder | Trận sắp đóng kèo chưa cược |
| M4 | Settlement Summary | Có vé vừa settle |
| M5 | Re-engagement | Vắng >= 3 ngày |
| M6 | Daily Ranking | Lần đầu trong ngày (sau M1) |

---

## Phase A – Nền tảng (Foundation)

**Mục tiêu:** Database, settings, contract, orchestrator skeleton.

**Ước tính:** 1–2 ngày

---

### A1. Migration – Thêm cột tracking vào `users`

**File:** `database/migrations/xxxx_add_modal_tracking_to_users.php`

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

**Sửa `app/Models/User.php`:** Thêm 6 cột vào `$fillable` và `$casts` (cast sang `datetime`).

---

### A2. Migration – Bảng `match_reminders`

**File:** `database/migrations/xxxx_create_match_reminders_table.php`

```php
Schema::create('match_reminders', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->foreignId('match_id')->constrained('matches')->cascadeOnDelete();
    $table->timestamp('reminded_at');
    $table->timestamp('snoozed_until')->nullable();
    $table->timestamp('dismissed_at')->nullable();
    $table->timestamps();
    $table->unique(['user_id', 'match_id']);
    $table->index(['user_id', 'reminded_at']);
});
```

---

### A3. Thêm settings vào `AppSettings`

**File:** `app/Settings/AppSettings.php`

```php
public bool $welcome_modal_enabled = true;
public int $welcome_modal_cooldown_hours = 12;
public int $match_reminder_window_hours = 5;
public int $match_reminder_cooldown_hours = 4;
public bool $match_reminder_enabled = true;
public int $reengagement_absent_days = 3;
```

Chạy migration cột settings:

```bash
php artisan settings:migrate
```

---

### A4. Interface & Contract

**File:** `app/Domain/Modal/Contracts/ModalCheckerInterface.php`

```php
<?php
namespace App\Domain\Modal\Contracts;

use App\Models\User;

interface ModalCheckerInterface
{
    public function shouldShow(User $user): bool;
    public function getData(User $user): array;
    public function markAsSeen(User $user): void;
}
```

---

### A5. ModalOrchestrator Livewire – skeleton

**File:** `app/Livewire/Player/ModalOrchestrator.php`

```php
<?php
namespace App\Livewire\Player;

use Livewire\Component;

class ModalOrchestrator extends Component
{
    public ?string $currentModal = null;
    public array $queue = [];
    public array $modalData = [];

    public function mount(): void
    {
        if (!auth()->check()) return;
        $this->buildQueue();
        $this->showNext();
    }

    public function dismiss(): void
    {
        $this->markCurrentAsSeen();
        $this->currentModal = null;
        $this->dispatch('modal-queue-next');
    }

    #[\Livewire\Attributes\On('modal-queue-next')]
    public function showNext(): void
    {
        if (empty($this->queue)) return;
        $this->currentModal = array_shift($this->queue);
    }

    private function buildQueue(): void { /* Phase B */ }
    private function markCurrentAsSeen(): void { /* Phase B */ }

    public function render()
    {
        return view('livewire.player.modal-orchestrator');
    }
}
```

**File:** `resources/views/livewire/player/modal-orchestrator.blade.php`

```blade
<div>
    @if($currentModal)
        @include("livewire.player.modals.{$currentModal}", $modalData[$currentModal] ?? [])
    @endif
</div>
```

**Register trong `PlayerPanelProvider`:**

```php
->renderHook(
    PanelsRenderHook::BODY_END,
    fn(): string => Blade::render("
        @livewire('player.modal-orchestrator')
        {{-- ... existing AFK code ... --}}
    ")
)
```

**Chạy:**
```bash
php artisan migrate
php artisan serve
```
**Verify:** Không có lỗi khi load Player Panel.

---

## Phase B – M1 Daily Briefing + M6 Daily Ranking

**Mục tiêu:** 2 modal quan trọng nhất trigger hàng ngày.

**Ước tính:** 2–3 ngày

---

### B1. DailyBriefingChecker

**File:** `app/Domain/Modal/Checkers/DailyBriefingChecker.php`

```php
public function shouldShow(User $user): bool
{
    if (!app(AppSettings::class)->welcome_modal_enabled) return false;
    $last = $user->last_daily_briefing_at;
    return $last === null
        || !Carbon::parse($last)->timezone('Asia/Ho_Chi_Minh')->isToday();
}

public function getData(User $user): array
{
    return Cache::remember("modal_briefing_{$user->id}", 600, function () use ($user) {
        $wallet  = $this->getWallet($user);
        $missions = $this->getActiveMissions($user);
        $matches  = Market::with('match')
            ->where('status', 'OPEN')
            ->where('close_at', '>', now())
            ->orderBy('close_at')
            ->limit(5)
            ->get();
        return compact('wallet', 'missions', 'matches');
    });
}

public function markAsSeen(User $user): void
{
    $user->update(['last_daily_briefing_at' => now()]);
    Cache::forget("modal_briefing_{$user->id}");
}
```

### B2. Blade – daily-briefing.blade.php

**File:** `resources/views/livewire/player/modals/daily-briefing.blade.php`

Nội dung: modal Tailwind, hiện sau 800ms (Alpine `x-init`), `max-w-lg`, scrollable body, mobile = bottom-sheet.

Sections:
- Header: chào tên + ngày giờ + số lá + hạng hiện tại.
- Nhiệm vụ active: progress bar cho từng mission (tối đa 3).
- Trận sắp đóng: badge đỏ pulse nếu < 60 phút.
- Footer: [Vào chơi ngay] + [Đóng].

---

### B3. DailyRankingChecker

**File:** `app/Domain/Modal/Checkers/DailyRankingChecker.php`

```php
public function shouldShow(User $user): bool
{
    $last = $user->last_daily_ranking_shown_at;
    return $last === null
        || !Carbon::parse($last)->timezone('Asia/Ho_Chi_Minh')->isToday();
}

public function getData(User $user): array
{
    // Lấy từ LeaderboardSnapshot gần nhất, tránh query nặng
    $rankings = Cache::remember('leaderboard_season_top50', 900, fn() => ...);
    $rank     = collect($rankings)->search(fn($r) => $r['user_id'] === $user->id);
    $userRank = $rank !== false ? $rank + 1 : null;
    $top3     = array_slice($rankings, 0, 3);
    $gapToAbove = $this->calcGap($rankings, $rank);
    $messageKey = $this->resolveMessage($userRank, count($rankings));

    return compact('userRank', 'top3', 'gapToAbove', 'messageKey', 'rankings');
}
```

**Bảng thông điệp theo hạng:**

```php
private function resolveMessage(?int $rank, int $total): string
{
    if ($rank === null) return 'new';
    return match(true) {
        $rank === 1 => 'top1',
        $rank === 2 => 'top2',
        $rank === 3 => 'top3',
        $rank <= 10 => 'chasing',
        $rank <= 20 => 'climbing',
        default     => 'learning',
    };
}
```

### B4. Blade – daily-ranking.blade.php

**File:** `resources/views/livewire/player/modals/daily-ranking.blade.php`

Sections:
- Header dynamic theo `$messageKey` (icon + tiêu đề + màu nền khác nhau).
- Stats card: hạng + lãi ròng + win rate + ROI.
- Delta: "Kém #X: -{gap} lá" (nếu không phải hạng 1).
- Mini leaderboard top 3, highlight hàng user.
- Footer: [Xem bảng xếp hạng] + [Đóng].

**Alpine animation:**
```javascript
// Top 1/2/3: confetti khi mounted
init() {
    if (['top1','top2','top3'].includes(this.messageKey)) {
        this.$nextTick(() => this.fireRankConfetti(this.messageKey));
    }
}
```

### B5. Wiring vào Orchestrator

**`buildQueue()` trong ModalOrchestrator:**

```php
private function buildQueue(): void
{
    $user = auth()->user();
    $checkers = [
        'celebration'  => DailyCelebrationChecker::class,
        'settlement'   => SettlementSummaryChecker::class,
        'daily'        => DailyBriefingChecker::class,
        'ranking'      => DailyRankingChecker::class,
        'reminder'     => MatchReminderChecker::class,
        'reengagement' => ReengagementChecker::class,
    ];
    foreach ($checkers as $type => $class) {
        $checker = app($class);
        if ($checker->shouldShow($user)) {
            $this->queue[]           = $type;
            $this->modalData[$type]  = $checker->getData($user);
        }
    }
}

private function markCurrentAsSeen(): void
{
    $map = [
        'daily'        => DailyBriefingChecker::class,
        'ranking'      => DailyRankingChecker::class,
        'celebration'  => CelebrationChecker::class,
        'settlement'   => SettlementSummaryChecker::class,
        'reminder'     => MatchReminderChecker::class,
        'reengagement' => ReengagementChecker::class,
    ];
    if ($this->currentModal && isset($map[$this->currentModal])) {
        app($map[$this->currentModal])->markAsSeen(auth()->user());
    }
}
```

**Verify:**
- Login lần đầu trong ngày → M1 hiện.
- Đóng M1 → 1.5s → M6 hiện với đúng thông điệp hạng.
- Login lần 2 trong ngày → không hiện.

---

## Phase C – M2 Celebration (Achievement + Mission)

**Mục tiêu:** Modal pháo hoa khi đạt thành tựu / hoàn thành nhiệm vụ.

**Ước tính:** 1–2 ngày

---

### C1. CelebrationChecker

```php
public function shouldShow(User $user): bool
{
    $since = $user->last_celebration_shown_at ?? now()->subYear();

    $hasNewAchievement = UserAchievement::where('user_id', $user->id)
        ->where('awarded_at', '>', $since)->exists();

    $hasCompletedMission = UserMission::where('user_id', $user->id)
        ->where('is_completed', true)
        ->where('completed_at', '>', $since)->exists();

    return $hasNewAchievement || $hasCompletedMission;
}
```

### C2. Canvas Confetti

**Install:**
```bash
npm install canvas-confetti
```

**`resources/js/app.js`:**
```js
import confetti from 'canvas-confetti';
window.fireConfetti = (type = 'achievement') => {
    if (type === 'fireworks') {
        // burst 3 lần cách nhau 500ms
    } else {
        confetti({ particleCount: 120, spread: 80, origin: { y: 0.6 } });
    }
};
```

### C3. Blade – celebration.blade.php

- Section achievement: badge glow pulse + `scale-in bounce` animation (200ms stagger).
- Section mission completed: checkmark draw SVG animation + confetti type `fireworks`.
- Nếu có cả hai: achievement trước, kẻ ngang phân cách, mission sau.
- Footer: [Xem thành tựu] + [Tuyệt vời! Đóng].

**Alpine:**
```javascript
init() {
    this.$nextTick(() => {
        if (this.hasAchievements) window.fireConfetti('achievement');
        if (this.hasMissions) setTimeout(() => window.fireConfetti('fireworks'), 800);
    });
}
```

**Verify:**
- Trao achievement thủ công qua Tinker → mở Player Panel → M2 hiện với confetti.

---

## Phase D – M4 Settlement Summary

**Mục tiêu:** Tổng kết vé sau mỗi settlement.

**Ước tính:** 1 ngày

---

### D1. SettlementSummaryChecker

```php
public function shouldShow(User $user): bool
{
    $since = $user->last_settlement_summary_shown_at;
    return Bet::where('user_id', $user->id)
        ->whereNotNull('settled_at')
        ->when($since, fn($q) => $q->where('settled_at', '>', $since))
        ->exists();
}

public function getData(User $user): array
{
    $since = $user->last_settlement_summary_shown_at;
    $bets  = Bet::where('user_id', $user->id)
        ->whereNotNull('settled_at')
        ->when($since, fn($q) => $q->where('settled_at', '>', $since))
        ->with('market.match')
        ->orderBy('settled_at', 'desc')
        ->limit(10)
        ->get();

    $total = $bets->sum('net_result');
    return compact('bets', 'total');
}
```

### D2. Blade – settlement-summary.blade.php

- Header: màu xanh nếu `$total > 0`, đỏ nếu âm, xám nếu = 0.
- Danh sách vé: icon WON/LOST/PUSH/HALF + số lá.
- Confetti nhỏ chỉ khi `$total > 0`.
- Nếu nhiều settlement: `<` `>` navigation.

---

## Phase E – M3 Match Reminder

**Mục tiêu:** Nhắc trận sắp đóng kèo mà user chưa cược.

**Ước tính:** 1 ngày

---

### E1. MatchReminderChecker

```php
public function shouldShow(User $user): bool
{
    $settings = app(AppSettings::class);
    if (!$settings->match_reminder_enabled) return false;

    $windowHours = $settings->match_reminder_window_hours; // 5h

    return Market::where('status', 'OPEN')
        ->whereBetween('close_at', [now()->addMinutes(30), now()->addHours($windowHours)])
        ->whereDoesntHave('bets', fn($q) => $q->where('user_id', $user->id))
        ->whereDoesntHave('match.reminders', function ($q) use ($user) {
            $q->where('user_id', $user->id)
              ->where(function ($q) {
                  $q->where('dismissed_at', '>', now()->subHours(4))
                    ->orWhere('snoozed_until', '>', now());
              });
        })
        ->exists();
}
```

### E2. Blade – match-reminder.blade.php

- 1 trận → toast nhỏ góc phải.
- >= 2 trận → modal đầy đủ.
- Badge đỏ pulse: `< 60 phút`.
- Nút: [Dự đoán ngay] → redirect match detail | [Nhắc lại sau 30p] → `snooze()` | [Bỏ qua] → `dismiss()`.

### E3. Livewire actions

```php
public function snooze(int $matchId): void
{
    MatchReminder::updateOrCreate(
        ['user_id' => auth()->id(), 'match_id' => $matchId],
        ['reminded_at' => now(), 'snoozed_until' => now()->addMinutes(30)]
    );
}
```

---

## Phase F – M5 Re-engagement

**Mục tiêu:** Kéo user vắng mặt quay lại.

**Ước tính:** 0.5 ngày

---

### F1. ReengagementChecker

```php
public function shouldShow(User $user): bool
{
    $absentDays = app(AppSettings::class)->reengagement_absent_days;
    $lastActive = $user->last_active_at ?? $user->created_at;
    $absentEnough = Carbon::parse($lastActive)->diffInDays(now()) >= $absentDays;

    $lastShown = $user->last_reengagement_shown_at;
    $cooldownOk = $lastShown === null
        || Carbon::parse($lastShown)->diffInDays(now()) >= 7;

    $hasOpenMatches = Market::where('status', 'OPEN')
        ->where('close_at', '>', now())->exists();

    return $absentEnough && $cooldownOk && $hasOpenMatches;
}
```

### F2. Blade – reengagement.blade.php

Hiển thị: số ngày vắng + thay đổi hạng (từ snapshot) + số trận đang mở + thời gian còn lại nhiệm vụ tuần.

---

## Phase G – Middleware `UpdateLastActive`

**Mục tiêu:** Cập nhật `last_active_at` mỗi request vào Player panel.

**File:** `app/Http/Middleware/UpdateLastActiveMiddleware.php`

```php
public function handle(Request $request, Closure $next): Response
{
    if (auth()->check()) {
        auth()->user()->updateQuietly(['last_active_at' => now()]);
    }
    return $next($request);
}
```

**Register** vào `authMiddleware` của `PlayerPanelProvider`.

---

## Phase H – npm build + CSS animations

**`resources/css/components/modal-animations.css`:**

```css
@keyframes badgeBounceIn {
    0%   { transform: scale(0) rotate(-10deg); opacity: 0; }
    60%  { transform: scale(1.15) rotate(3deg); }
    100% { transform: scale(1) rotate(0); opacity: 1; }
}
@keyframes achievementGlow {
    0%, 100% { box-shadow: 0 0 6px rgba(251,191,36,.4); }
    50%       { box-shadow: 0 0 24px rgba(251,191,36,.9); }
}
@keyframes checkDraw {
    from { stroke-dashoffset: 100; }
    to   { stroke-dashoffset: 0; }
}
@keyframes crownPulse {
    0%, 100% { filter: drop-shadow(0 0 4px gold); }
    50%       { filter: drop-shadow(0 0 16px gold); }
}
```

**Import** vào `resources/css/filament/player/theme.css`.

**Chạy:**
```bash
npm run build
```

---

## Checklist Verification toàn hệ thống

| # | Test case | Kết quả mong đợi |
|---|---|---|
| 1 | Login lần đầu trong ngày | M1 → M6 xuất hiện theo thứ tự |
| 2 | Login lần 2 trong ngày | Không có modal |
| 3 | Có achievement mới chưa xem | M2 với confetti |
| 4 | Mission completed chưa xem | M2 với fireworks |
| 5 | Trận OPEN trong 5h chưa cược | M3 hiện (toast hoặc modal) |
| 6 | Snooze M3 → sau 30p vào lại | M3 hiện lại |
| 7 | Settle xong → mở panel | M4 với màu đúng theo kết quả |
| 8 | Vắng 3 ngày → mở panel | M5 hiện |
| 9 | Nhiều modal đủ điều kiện | Theo thứ tự priority, cách 1.5s |
| 10 | `welcome_modal_enabled = false` | Không hiện bất kỳ modal nào |
| 11 | Hạng #1 M6 | Crown glow + confetti đầy đủ |
| 12 | Hạng > 10 M6 | Fade-in, không confetti |
| 13 | `prefers-reduced-motion` | Không animation, modal vẫn hiện |

---

## Thứ tự chạy lệnh sau khi hoàn thành

```bash
php artisan migrate
php artisan db:seed --class=SettingsSeeder   # nếu có welcome modal settings
npm install canvas-confetti
npm run build
php artisan config:clear
php artisan cache:clear
php artisan test --filter=Modal
```

---

## Dependencies

| Package | Mục đích | Ghi chú |
|---|---|---|
| `canvas-confetti` | Pháo hoa / confetti | npm, 6KB gzipped |
| `spatie/laravel-settings` | `AppSettings` | Đã có sẵn |
| Livewire 3 | `ModalOrchestrator` | Đã có sẵn |
| Alpine.js | Animation controller | Đã có sẵn |
