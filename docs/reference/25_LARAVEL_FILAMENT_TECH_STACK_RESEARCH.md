---
title: "Tài liệu kỹ thuật Laravel + Filament cho website dự đoán bóng đá nội bộ bằng lá"
version: "1.0"
updated_at: "2026-06-11"
project: "Lá Dự Đoán - Prediction Game nội bộ công ty"
stack_target: "Laravel + Filament + PostgreSQL + Redis"
---


## Quy ước tỷ lệ ăn kiểu Việt Nam

Tài liệu này dùng cách ghi phổ biến ở Việt Nam: **line + ăn + hệ số lãi**. Ví dụ `Home -0.5 ăn 0.90` nghĩa là đặt 100 lá, thắng đủ nhận 190 lá, gồm 100 lá vốn + 90 lá lãi.

Quy ước kỹ thuật:

```text
profit_rate = tỷ lệ ăn / hệ số lãi
decimal_odds = 1 + profit_rate
gross_payout_full_win = stake * (1 + profit_rate)
net_profit_full_win = stake * profit_rate
```

# Tài liệu kỹ thuật Laravel + Filament cho website dự đoán bóng đá nội bộ bằng lá

## 1. Mục tiêu tài liệu

Tài liệu này mô tả stack kỹ thuật phù hợp để xây dựng website nội bộ cho dự án:

> Website dự đoán bóng đá nội bộ bằng đơn vị điểm ảo `lá`, có login, admin cấp lá, user dự đoán tỉ số/kèo châu Á/tài xỉu, hệ thống tự cộng trừ lá sau khi admin cập nhật kết quả.

Phạm vi tài liệu:

- Laravel backend.
- Filament admin/user panel.
- Plugin Laravel/Filament nên dùng.
- Frontend stack.
- Backend package.
- Icon library.
- Chart/analytics library.
- JavaScript library.
- Queue/cache/realtime/scheduler.
- Kỹ thuật bảo mật, audit log, settlement, ledger.
- Cấu trúc thư mục, module, service class.
- Checklist triển khai MVP.

---

## 2. Quyết định stack tổng thể

### 2.1. Stack khuyến nghị

| Thành phần | Đề xuất | Ghi chú |
|---|---|---|
| Framework | Laravel 13 hoặc Laravel 12 | Nếu bắt đầu mới vào 2026, ưu tiên Laravel 13; nếu cần ổn định theo đội dev, Laravel 12 vẫn hợp lý. |
| Admin panel | Filament 5.x | Dùng cho admin, settlement, tỷ lệ ăn, user, wallet, report. |
| UI người chơi | Livewire + Blade + Tailwind hoặc Filament user panel | MVP nên dùng Filament panel riêng cho user để nhanh. |
| Database | PostgreSQL | Phù hợp transaction, locking, enum/check constraint, report. |
| Cache/Queue | Redis | Dùng cho cache leaderboard, queue settlement, lock market. |
| Queue dashboard | Laravel Horizon | Theo dõi job settlement, notification, export. |
| Realtime | Laravel Reverb + Echo | Không bắt buộc MVP; dùng nếu cần cập nhật tỷ lệ ăn/countdown/notification realtime. |
| Build assets | Vite | Laravel mặc định hỗ trợ tốt. |
| CSS | Tailwind CSS | Cùng hệ sinh thái Filament. |
| JS nhẹ | Alpine.js | Dùng cho countdown, modal, stake input nếu custom Blade. |
| Chart | Filament Widgets + ApexCharts | Built-in cho chart đơn giản, ApexCharts cho dashboard đẹp hơn. |
| Auth | Filament Auth + Breezy / Laravel Fortify | Tùy chọn 2FA, profile, session. |
| Permission | Spatie Permission + Filament Shield | Bắt buộc cho admin nhiều role. |
| Audit | Spatie Activitylog | Bắt buộc cho tỷ lệ ăn/result/wallet/settlement. |
| Import/export | Filament ImportAction/ExportAction, Laravel Excel nếu cần XLSX | CSV đủ cho MVP. |
| Backup | Spatie Laravel Backup | Nên có từ đầu. |
| Testing | Pest/PHPUnit | Bắt buộc cho settlement engine. |

---

## 3. Kiến trúc tổng thể

```text
Browser
  |
  |-- /admin       -> Filament Admin Panel
  |-- /app         -> Filament User Panel hoặc Blade/Livewire User Portal
  |-- /api         -> Laravel API, nếu cần mobile/external client
  |
Laravel App
  |
  |-- Domain Services
  |     |-- BetPlacementService
  |     |-- SettlementEngine
  |     |-- WalletLedgerService
  |     |-- MarketLockingService
  |     |-- LeaderboardService
  |
  |-- Queue Jobs
  |     |-- SettleMarketJob
  |     |-- RecalculateLeaderboardJob
  |     |-- SendNotificationJob
  |     |-- ImportFixtureJob
  |
  |-- Scheduler
  |     |-- lock expired markets
  |     |-- snapshot leaderboard
  |     |-- backup database
  |
PostgreSQL + Redis
```

---

## 4. Cách chia panel trong Filament

### 4.1. Đề xuất MVP: 2 panel

```text
/admin  -> dành cho Super Admin, Operator, Settlement Manager, Auditor
/app    -> dành cho User nội bộ
```

Lý do:

- Phân quyền rõ.
- Giao diện người chơi đơn giản, không lộ chức năng admin.
- Dễ giới hạn route, menu, middleware.
- Dễ tách theme và navigation.

### 4.2. Admin Panel

Module nên có:

| Resource/Page | Mục đích |
|---|---|
| UserResource | Tạo tài khoản, phân quyền, khóa/mở user. |
| DepartmentResource | Quản lý phòng ban. |
| SeasonResource | Mùa giải, kỳ thi đấu. |
| CompetitionResource | World Cup 2026, Champions League, v.v. |
| MatchResource | Trận đấu, giờ đá, sân, trạng thái. |
| MarketResource | Cả trận, hiệp 1, hiệp 2, hiệp phụ, penalty. |
| MarketOutcomeResource | Tỉ số, handicap, tài xỉu, tỷ lệ ăn. |
| Tỷ lệ ănVersionResource | Lưu version tỷ lệ ăn. |
| BetResource | Vé dự đoán của user. |
| SettlementPage | Nhập kết quả, preview, xác nhận settlement. |
| WalletResource | Ví lá. |
| WalletLedgerResource | Lịch sử biến động lá. |
| LeaderboardPage | Bảng xếp hạng. |
| AuditLogPage | Lịch sử thao tác. |
| GameSettingsPage | Cấu hình hệ thống. |
| ReportPage | Export báo cáo. |

### 4.3. User Panel

Module nên có:

| Page | Nội dung |
|---|---|
| Dashboard | Số lá, trận đang mở, vé pending, xếp hạng cá nhân. |
| Fixtures | Lịch trận World Cup 2026. |
| Match Detail | Tỉ lệ tỉ số, kèo châu Á, tài xỉu, giờ đóng. |
| Place Bet | Chọn market, nhập lá, xác nhận. |
| My Bets | Vé pending/thắng/thua/hoàn/hủy. |
| Wallet History | Lịch sử lá. |
| Leaderboard | Cá nhân/phòng ban/tuần/mùa. |
| Rules | Luật chơi, pháp lý, công thức tính. |

---

## 5. Plugin Filament nên dùng

## 5.1. Nhóm bắt buộc cho MVP

### 5.1.1. Filament core

```bash
composer require filament/filament:"^5.0" -W
php artisan filament:install --panels
```

Dùng cho:

- Panel.
- Resource.
- Form.
- Table.
- Action.
- Infolist.
- Widget.
- Notification.
- Import/Export Action.

Ghi chú:

- Không cần cài thêm form/table/action riêng nếu dùng Panel Builder.
- Dùng `php artisan filament:optimize` khi deploy production.

---

### 5.1.2. Filament Shield + Spatie Permission

```bash
composer require spatie/laravel-permission
composer require bezhansalleh/filament-shield
php artisan shield:setup
```

Dùng cho:

- Phân quyền theo role.
- Phân quyền theo Resource/Page/Widget/Action.
- Super admin toàn quyền.
- Operator chỉ nhập trận/kèo.
- Settlement manager duyệt settlement.
- Auditor chỉ xem log/báo cáo.
- User chỉ xem và đặt dự đoán.

Role đề xuất:

```text
super_admin
operator
settlement_manager
auditor
user
```

Permission gợi ý:

```text
users.view
users.create
users.update
users.lock

wallets.view
wallets.grant
wallets.deduct
wallets.adjust

matches.view
matches.create
matches.update
matches.publish
matches.cancel

markets.view
markets.create
markets.update
markets.lock
markets.void

tỷ lệ ăn.view
tỷ lệ ăn.create
tỷ lệ ăn.update
tỷ lệ ăn.version

bets.view
bets.cancel_admin

settlements.view
settlements.preview
settlements.approve
settlements.execute
settlements.rollback

leaderboards.view
reports.export
audit_logs.view
settings.update
```

Khuyến nghị:

- Không cho `operator` quyền trực tiếp cộng/trừ ví.
- Không cho `operator` vừa nhập kết quả vừa tự duyệt settlement nếu hệ thống có nhiều người chơi.
- Dùng policy riêng cho các action nhạy cảm: `settle`, `void`, `rollback`, `grantLeaves`.

---

### 5.1.3. Filament Breezy

```bash
composer require jeffgreco13/filament-breezy
```

Dùng cho:

- Profile page.
- Đổi mật khẩu.
- Avatar.
- Two-factor authentication.
- Session management.
- Password confirmation cho action nhạy cảm.
- Sanctum token management nếu cần API token.

Áp dụng trong dự án:

| Nhóm user | 2FA |
|---|---|
| super_admin | Bắt buộc |
| operator | Nên bắt buộc |
| settlement_manager | Bắt buộc |
| auditor | Nên bắt buộc |
| user thường | Không bắt buộc trong MVP |

---

### 5.1.4. Spatie Activitylog

```bash
composer require spatie/laravel-activitylog
php artisan vendor:publish --provider="Spatie\Activitylog\ActivitylogServiceProvider" --tag="activitylog-migrations"
php artisan migrate
```

Dùng cho:

- Log user đặt lá.
- Log admin cấp/trừ lá.
- Log admin đổi tỷ lệ ăn.
- Log admin mở/khóa market.
- Log admin nhập kết quả.
- Log settlement.
- Log void/correction/rollback.

Model nên log:

```text
User
Wallet
WalletLedger
Competition
Season
MatchModel
Market
MarketOutcome
Tỷ lệ ănVersion
Bet
Settlement
SettlementLine
GameSetting
```

Lưu ý:

- Có thể tự tạo Filament Resource để xem bảng `activity_log` thay vì phụ thuộc plugin xem log bên thứ ba.
- Nếu dùng plugin xem log cộng đồng, phải kiểm tra compatibility với Filament 5 trước khi cài.

---

### 5.1.5. Spatie Laravel Settings + Filament Settings Plugin

```bash
composer require spatie/laravel-settings
composer require filament/spatie-laravel-settings-plugin:"^5.0" -W
```

Dùng cho cấu hình động:

```text
default_starting_leaves
min_stake
max_stake_per_bet
max_stake_per_match
max_stake_per_day
allow_negative_balance
allow_user_transfer
market_auto_lock_seconds
settlement_requires_approval
leaderboard_snapshot_frequency
legal_disclaimer_text
season_reset_policy
```

Ví dụ class:

```php
namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class GameSettings extends Settings
{
    public int $default_starting_leaves;
    public int $min_stake;
    public int $max_stake_per_bet;
    public int $max_stake_per_match;
    public int $max_stake_per_day;
    public bool $allow_negative_balance;
    public bool $allow_user_transfer;

    public static function group(): string
    {
        return 'game';
    }
}
```

---

### 5.1.6. Backup

```bash
composer require spatie/laravel-backup
```

Plugin Filament backup có thể cân nhắc:

```bash
composer require shuvroroy/filament-spatie-laravel-backup
```

Lưu ý:

- Kiểm tra compatibility với Filament version đang dùng.
- Nếu plugin chưa hỗ trợ tốt, vẫn dùng command backup của Spatie và tự tạo page hiển thị file backup.

Lịch backup đề xuất:

```text
02:00 hằng ngày
Trước khi reset mùa giải
Trước khi settlement hàng loạt vòng knockout
Trước khi import tỷ lệ ăn số lượng lớn
```

---

## 5.2. Nhóm nên dùng ở phase 2

### 5.2.1. Apex Charts cho Filament

```bash
composer require leandrocfe/filament-apex-charts
```

Dùng cho:

- Dashboard admin.
- Tổng lá đặt theo ngày.
- Doanh số lá ảo theo trận.
- Tổng vé theo market type.
- Tỉ lệ win/lose/push.
- ROI theo user/phòng ban.
- Top user tăng trưởng mạnh.

Chart đề xuất:

| Dashboard | Chart |
|---|---|
| Admin Overview | Stats cards + line chart số vé theo ngày. |
| Wallet | Bar chart tổng lá theo phòng ban. |
| Leaderboard | Horizontal bar top 10 user. |
| Market | Donut chart phân bổ loại kèo. |
| Settlement | Stacked bar win/lose/push. |

---

### 5.2.2. Laravel Excel

```bash
composer require maatwebsite/excel
```

Dùng khi:

- Admin muốn import `.xlsx` nhiều sheet.
- Lịch WC2026 có nhiều sheet.
- Tỷ lệ ăn nhập theo file Excel.
- Export báo cáo có định dạng đẹp hơn CSV.

MVP có thể chỉ dùng:

```text
Filament ImportAction / ExportAction cho CSV
```

Chỉ cài Laravel Excel khi thật sự cần `.xlsx`.

---

### 5.2.3. Approval flow

Có 2 hướng:

#### Hướng A: tự code

Tự tạo các bảng:

```text
approval_requests
approval_steps
approval_logs
```

Dùng cho:

```text
settlement approval
void market approval
wallet correction approval
season reset approval
```

#### Hướng B: plugin cộng đồng

Có thể dùng plugin approvals của cộng đồng, nhưng cần kiểm tra:

- Có hỗ trợ Filament 5 không.
- Có còn maintain không.
- Có test không.
- Có phù hợp flow maker-checker không.

Khuyến nghị:

> Với dự án này, approval là nghiệp vụ nhạy cảm. Nên tự code đơn giản để kiểm soát tốt hơn.

---

### 5.2.4. Docs / Rules page

Có thể dùng:

- Custom Filament Page.
- Markdown file render bằng Laravel Markdown.
- Plugin docs nếu đội dev muốn quản trị nội dung trong panel.

Nội dung nên có:

```text
Luật điểm lá
Luật tỉ số chính xác
Luật kèo châu Á
Luật tài xỉu
Quy định không quy đổi lá
Quy định không chuyển nhượng lá
Quy định hủy/void/correction
```

---

## 5.3. Plugin/package không nên cài ở MVP

| Plugin/package | Lý do chưa cần |
|---|---|
| DataTables JS | Filament Table đã đủ mạnh. |
| FullCalendar | Lịch trận dạng list/card/table là đủ; có thể thêm sau. |
| Heavy UI kit như DaisyUI/Flowbite | Dễ xung đột style với Filament/Tailwind. |
| Nhiều icon set cùng lúc | Tăng asset/cache, thiếu nhất quán UI. |
| Chart library phức tạp như D3 | Không cần cho dashboard MVP. |
| SPA React/Vue đầy đủ | Làm chậm MVP nếu team đang chọn Filament/Livewire. |
| CMS plugin lớn | Luật chơi/thể lệ có thể làm bằng Markdown page. |

---

# 6. Backend package Laravel nên dùng

## 6.1. Package chính

```bash
composer require filament/filament:"^5.0" -W
composer require spatie/laravel-permission
composer require bezhansalleh/filament-shield
composer require jeffgreco13/filament-breezy
composer require spatie/laravel-activitylog
composer require spatie/laravel-settings
composer require filament/spatie-laravel-settings-plugin:"^5.0" -W
composer require spatie/laravel-backup
composer require laravel/horizon
composer require laravel/sanctum
```

## 6.2. Package optional

```bash
composer require laravel/reverb
composer require laravel/socialite
composer require maatwebsite/excel
composer require spatie/laravel-data
composer require spatie/laravel-query-builder
composer require spatie/laravel-health
composer require sentry/sentry-laravel
```

## 6.3. Dev package

```bash
composer require pestphp/pest --dev --with-all-dependencies
composer require pestphp/pest-plugin-laravel --dev
composer require larastan/larastan --dev
composer require laravel/pint --dev
composer require barryvdh/laravel-debugbar --dev
composer require laravel/telescope --dev
```

Khuyến nghị:

| Package | Dùng cho |
|---|---|
| Pest | Test settlement engine, wallet ledger. |
| Larastan | Static analysis, giảm bug type. |
| Pint | Format code chuẩn Laravel. |
| Debugbar | Dev local. Không bật production. |
| Telescope | Debug queue/request/job local hoặc staging. Không public production. |

---

# 7. Frontend stack

## 7.1. Lựa chọn khuyến nghị cho MVP

```text
Blade + Livewire + Alpine.js + Tailwind CSS + Filament Components
```

Lý do:

- Cùng hệ sinh thái Laravel/Filament.
- Ít tách biệt frontend/backend.
- Không cần build SPA phức tạp.
- Dễ cho admin panel và user portal dùng chung logic.
- Phù hợp hệ thống nội bộ.

## 7.2. Khi nào dùng React/Vue/Inertia?

Chỉ nên cân nhắc nếu:

- User portal cần trải nghiệm như app realtime rất mượt.
- Có mobile web phức tạp.
- Có team frontend riêng.
- Muốn tách UI người chơi khỏi Filament hoàn toàn.

Nếu dùng Inertia:

```text
Laravel API/controller
Inertia React hoặc Vue
Tailwind CSS
Heroicons/Lucide
ApexCharts hoặc ECharts
```

Nhưng với MVP nội bộ, cách này không cần thiết.

---

# 8. JavaScript library đề xuất

## 8.1. Cài đặt cơ bản

```bash
npm install dayjs
npm install apexcharts
```

Nếu dùng realtime:

```bash
npm install laravel-echo pusher-js
```

Nếu tự dùng Alpine bên ngoài Filament:

```bash
npm install alpinejs @alpinejs/focus @alpinejs/persist
```

## 8.2. Mapping JS theo tính năng

| Tính năng | Thư viện | Ghi chú |
|---|---|---|
| Countdown đóng market | Alpine.js + Day.js | Nhẹ, đủ dùng. |
| Format giờ theo timezone | Day.js | Có thể dùng plugin timezone nếu cần. |
| Biểu đồ dashboard | ApexCharts | Dùng trực tiếp hoặc qua Filament Apex Charts. |
| Realtime notification | Laravel Echo + Reverb | Phase 2. |
| Modal/Dropdown nhỏ | Alpine.js | Filament đã có component sẵn trong panel. |
| Upload avatar | Breezy/Filament FileUpload | Không cần JS riêng. |
| Date/time picker | Filament DateTimePicker | Không cần Flatpickr riêng. |
| Select/search | Filament Select | Không cần TomSelect riêng. |

## 8.3. Lưu ý tránh xung đột JS

- Không load Alpine.js hai lần trong cùng panel.
- Không tự nhúng Tailwind CDN trong Filament panel.
- Không trộn nhiều UI framework như Bootstrap + Tailwind + DaisyUI.
- Không dùng jQuery/DataTables nếu đã dùng Filament Table.
- Nếu thêm ApexCharts riêng, chỉ mount ở component cần chart.
- Nếu dùng Reverb/Echo, kiểm soát channel authorization kỹ.

---

# 9. Icon library

## 9.1. Khuyến nghị chính: Heroicons

Dùng Heroicons vì:

- Cùng hệ sinh thái Tailwind.
- Filament dùng icon theo style gần Heroicons.
- Đủ cho dashboard/admin/user portal.
- Miễn phí, MIT license.

Các nhóm icon hay dùng:

```text
heroicon-o-trophy
heroicon-o-calendar-days
heroicon-o-wallet
heroicon-o-chart-bar
heroicon-o-clock
heroicon-o-lock-closed
heroicon-o-user-group
heroicon-o-shield-check
heroicon-o-document-chart-bar
heroicon-o-arrow-path
heroicon-o-exclamation-triangle
```

## 9.2. Nếu cần nhiều icon hơn

Có thể thêm một trong các bộ sau, không nên thêm tất cả:

```bash
composer require blade-ui-kit/blade-icons
composer require blade-ui-kit/blade-heroicons
```

Hoặc:

```bash
composer require codeat3/blade-lucide-icons
```

Khuyến nghị:

> MVP chỉ dùng Heroicons. Nếu thiếu icon thể thao/bóng đá thì thêm Lucide Icons, nhưng phải thống nhất style outline/solid.

## 9.3. Quy tắc icon UI

| Loại chức năng | Icon gợi ý |
|---|---|
| Trận đấu | calendar-days |
| Ví lá | wallet |
| Leaderboard | trophy/chart-bar |
| Settlement | check-circle |
| Audit | document-magnifying-glass |
| Cài đặt | cog-6-tooth |
| Khóa market | lock-closed |
| Cảnh báo lỗi tỷ lệ ăn | exclamation-triangle |
| Người dùng | user-group |

---

# 10. Chart và dashboard

## 10.1. Built-in Filament Widgets

Dùng cho:

- Stats overview.
- Card số liệu.
- Chart đơn giản.
- Table widget.

Widget gợi ý:

```text
TotalUsersWidget
TotalLeavesInSystemWidget
PendingBetsWidget
OpenMarketsWidget
TodayMatchesWidget
SettlementQueueWidget
```

## 10.2. ApexCharts

Dùng khi cần chart đẹp hơn:

```bash
composer require leandrocfe/filament-apex-charts
npm install apexcharts
```

Chart đề xuất:

| Chart | Mục đích |
|---|---|
| Line chart | Số vé đặt theo thời gian. |
| Bar chart | Top user theo lá. |
| Horizontal bar | Top phòng ban. |
| Donut chart | Phân bổ loại kèo: tỉ số, handicap, tài xỉu. |
| Stacked bar | Win/lose/push theo trận. |
| Area chart | Tổng lá lưu chuyển theo ngày. |

## 10.3. Metric cần có

```text
total_users
total_active_users
total_matches
total_open_markets
total_bets
total_pending_bets
total_staked_leaves
total_locked_leaves
total_settled_leaves
win_rate
push_rate
average_stake
largest_win
largest_loss
roi_by_user
roi_by_department
```

---

# 11. Database và kỹ thuật lưu tiền điểm ảo `lá`

## 11.1. Nguyên tắc lưu lá

- `lá` là điểm ảo, lưu bằng integer.
- Không dùng float cho số lá.
- Tỷ lệ ăn có thể lưu dạng decimal.
- Kết quả payout cuối cùng nên làm tròn theo rule rõ ràng.

Gợi ý:

```text
stake_leaves: integer
tỷ lệ ăn_decimal: numeric(8,3)
payout_leaves: integer
profit_leaves: integer
```

## 11.2. Wallet không được update trực tiếp

Không nên chỉ có:

```text
users.balance
```

Nên có:

```text
wallets
wallet_ledgers
```

Bảng `wallets`:

```text
id
user_id
available_balance
locked_balance
total_balance
season_profit
created_at
updated_at
```

Bảng `wallet_ledgers`:

```text
id
wallet_id
user_id
type
amount
balance_before
balance_after
reference_type
reference_id
metadata
created_by
created_at
```

Loại ledger:

```text
ADMIN_GRANT
ADMIN_DEDUCT
BET_PLACED
BET_WON
BET_LOST
BET_PUSH
BET_HALF_WON
BET_HALF_LOST
MARKET_VOID
SETTLEMENT_CORRECTION
SEASON_RESET
```

## 11.3. Transaction bắt buộc

Khi user đặt lá:

```php
DB::transaction(function () use ($user, $marketOutcome, $stake) {
    $wallet = Wallet::where('user_id', $user->id)
        ->lockForUpdate()
        ->firstOrFail();

    if ($wallet->available_balance < $stake) {
        throw new InsufficientLeavesException();
    }

    // kiểm tra market còn mở
    // snapshot tỷ lệ ăn
    // tạo bet
    // trừ available, cộng locked
    // ghi ledger
});
```

Khi settle:

```php
DB::transaction(function () use ($market) {
    // lock market
    // lấy danh sách bet pending
    // tính payout từng bet
    // update wallet bằng lockForUpdate
    // ghi settlement lines
    // ghi ledger
    // đánh dấu market settled
});
```

---

# 12. Backend domain services nên viết riêng

Không nên đặt toàn bộ logic vào Filament Resource hoặc Controller.

## 12.1. Service class đề xuất

```text
app/Domain/Betting/Services/BetPlacementService.php
app/Domain/Betting/Services/SettlementEngine.php
app/Domain/Wallet/Services/WalletLedgerService.php
app/Domain/Market/Services/MarketLockingService.php
app/Domain/Leaderboard/Services/LeaderboardService.php
app/Domain/Tỷ lệ ăn/Services/Tỷ lệ ănVersioningService.php
```

## 12.2. DTO / Value Object

Có thể dùng class thường hoặc `spatie/laravel-data`.

```text
BetPlacementData
SettlementResult
PayoutResult
ScoreResult
AsianHandicapLine
OverUnderLine
Tỷ lệ ănSnapshot
```

## 12.3. Enum nên có

```php
enum MarketType: string
{
    case ExactScore = 'EXACT_SCORE';
    case AsianHandicap = 'ASIAN_HANDICAP';
    case OverUnder = 'OVER_UNDER';
}

enum PeriodType: string
{
    case FullTime = 'FULL_TIME';
    case FirstHalf = 'FIRST_HALF';
    case SecondHalf = 'SECOND_HALF';
    case ExtraTime = 'EXTRA_TIME';
    case Penalty = 'PENALTY';
}

enum BetStatus: string
{
    case Pending = 'PENDING';
    case Won = 'WON';
    case Lost = 'LOST';
    case Push = 'PUSH';
    case HalfWon = 'HALF_WON';
    case HalfLost = 'HALF_LOST';
    case Voided = 'VOIDED';
}
```

---

# 13. Settlement engine

## 13.1. Không phụ thuộc plugin

Các logic sau phải tự code:

```text
Exact score settlement
Asian handicap settlement
Over/under settlement
Wallet payout
Half win / half lose / push
Market void
Correction / rollback
```

## 13.2. Exact score

Input:

```text
prediction_home_score
prediction_away_score
actual_home_score
actual_away_score
stake
tỷ lệ ăn
```

Rule:

```text
Nếu prediction == actual:
    payout = stake * tỷ lệ ăn
    profit = payout - stake
Ngược lại:
    payout = 0
    profit = -stake
```

## 13.3. Asian handicap

Input:

```text
selected_team
handicap_value
actual_home_score
actual_away_score
stake
tỷ lệ ăn
```

Các trạng thái:

```text
WIN
LOSE
PUSH
HALF_WIN
HALF_LOSE
```

Công thức payout:

```text
WIN       -> stake * tỷ lệ ăn
LOSE      -> 0
PUSH      -> stake
HALF_WIN  -> stake/2 * tỷ lệ ăn + stake/2
HALF_LOSE -> stake/2
```

## 13.4. Over/Under - tài xỉu

Input:

```text
side: OVER | UNDER
line: 2.5, 2.25, 2.75, 3.0...
total_goals
stake
tỷ lệ ăn
```

Các trạng thái:

```text
WIN
LOSE
PUSH
HALF_WIN
HALF_LOSE
```

Ví dụ tài 2.25, tổng bàn = 3:

```text
Tài 2.0 thắng
Tài 2.5 thắng
=> WIN
```

Ví dụ tài 2.25, tổng bàn = 2:

```text
Tài 2.0 hòa/push
Tài 2.5 thua
=> HALF_LOSE
```

Ví dụ xỉu 2.75, tổng bàn = 2:

```text
Xỉu 2.5 thắng
Xỉu 3.0 thắng
=> WIN
```

Ví dụ xỉu 2.75, tổng bàn = 3:

```text
Xỉu 2.5 thua
Xỉu 3.0 push
=> HALF_LOSE
```

---

# 14. Queue, Scheduler và Realtime

## 14.1. Redis

Dùng Redis cho:

```text
cache leaderboard
queue settlement jobs
rate limit
short lock
broadcast presence nếu cần
```

## 14.2. Horizon

Cài đặt:

```bash
composer require laravel/horizon
php artisan horizon:install
```

Dùng để theo dõi:

```text
SettleMarketJob
RecalculateLeaderboardJob
SendBetSettledNotificationJob
ExportReportJob
ImportFixtureJob
```

Queue gợi ý:

```text
high      -> settlement, wallet correction
normal    -> notifications, leaderboard
low       -> export/import/report
```

## 14.3. Scheduler

Command nên có:

```text
markets:lock-expired
leaderboard:snapshot
wallets:reconcile
matches:remind-upcoming
backups:run
```

Ví dụ:

```php
use Illuminate\Support\Facades\Schedule;

Schedule::command('markets:lock-expired')->everyMinute();
Schedule::command('leaderboard:snapshot')->hourly();
Schedule::command('wallets:reconcile')->dailyAt('03:00');
```

## 14.4. Realtime với Laravel Reverb

Không bắt buộc MVP.

Dùng khi cần:

- User đang mở trận, tỷ lệ ăn/market status tự cập nhật.
- Countdown đóng market realtime.
- Vé vừa settle thì user nhận notification ngay.
- Admin settlement dashboard cập nhật queue status.

Package:

```bash
composer require laravel/reverb
npm install laravel-echo pusher-js
```

Channel cần bảo vệ:

```text
private-user.{id}
private-admin.settlements
private-match.{id}
```

Không broadcast dữ liệu nhạy cảm như toàn bộ ví user khác.

---

# 15. Import lịch World Cup 2026 và tỷ lệ ăn

## 15.1. Seeder cố định lịch trận

Tạo:

```text
database/seeders/Wc2026FixtureSeeder.php
```

Các field nên có:

```text
match_no
competition_code
stage
group_code
home_team_placeholder
away_team_placeholder
home_team_id
away_team_id
stadium
city
country
kickoff_at_utc
kickoff_at_vn
status
```

Do vòng bảng có đội cụ thể sau bốc thăm/lịch chính thức, hệ thống cần hỗ trợ:

```text
placeholder trước
team chính thức sau
```

## 15.2. Import tỷ lệ ăn

Mẫu CSV tỷ lệ ăn:

```csv
match_no,period_type,market_type,selection_label,side,line,home_score,away_score,tỷ lệ ăn,close_at
1,FULL_TIME,EXACT_SCORE,1-0,,,,1,0,6.50,2026-06-12 01:45:00
1,FULL_TIME,ASIAN_HANDICAP,Home -0.5,HOME,-0.5,,,0.90,2026-06-12 01:45:00
1,FULL_TIME,OVER_UNDER,Over 2.5,OVER,2.5,,,1.95,2026-06-12 01:45:00
1,FULL_TIME,OVER_UNDER,Under 2.5,UNDER,2.5,,,1.85,2026-06-12 01:45:00
```

## 15.3. ImportAction hay Laravel Excel?

| Nhu cầu | Công cụ |
|---|---|
| CSV đơn giản | Filament ImportAction |
| CSV export báo lỗi | Filament ImportAction |
| XLSX nhiều sheet | Laravel Excel |
| Import cực lớn | Queue job + chunking |

---

# 16. API nội bộ

MVP có thể không cần API riêng nếu dùng Filament/Livewire.

Nếu cần API:

```bash
composer require laravel/sanctum
```

Endpoint gợi ý:

```text
GET    /api/me
GET    /api/matches
GET    /api/matches/{match}
GET    /api/matches/{match}/markets
POST   /api/bets
GET    /api/my-bets
GET    /api/wallet
GET    /api/leaderboard
```

Bảo mật:

```text
Sanctum token hoặc SPA cookie auth
rate limit theo user/IP
không expose tỷ lệ ăn admin draft
không cho đặt nếu market locked
server-side validate toàn bộ stake/tỷ lệ ăn/status
```

---

# 17. Kỹ thuật bảo mật cần áp dụng

## 17.1. Auth và session

- Bắt buộc HTTPS.
- Admin nên bật 2FA.
- Session timeout cho admin.
- Password confirmation cho action nhạy cảm.
- Không dùng tài khoản chung.
- Không gửi password qua chat/email plain text.

## 17.2. Authorization

- Dùng policy cho từng model/action.
- Không chỉ ẩn nút trên UI; backend phải check quyền.
- Các action nhạy cảm phải check permission cụ thể.

## 17.3. Rate limit

Cần rate limit:

```text
login
place bet
export report
preview settlement
api leaderboard
```

## 17.4. Audit log

Bắt buộc log:

```text
login admin
failed login admin
admin grant/deduct leaves
tỷ lệ ăn create/update
market open/lock/void
result input
settlement preview
settlement execute
settlement rollback
settings update
```

## 17.5. Idempotency

Settlement phải idempotent:

```text
Cùng một market không được settle 2 lần nếu không có correction flow.
Mỗi settlement có settlement_id duy nhất.
Mỗi wallet ledger có reference_type/reference_id rõ.
```

## 17.6. Concurrency

Các vị trí phải lock DB:

```text
user đặt bet
admin settle market
admin grant/deduct leaves
rollback/correction
season reset
```

Sử dụng:

```php
lockForUpdate()
DB::transaction()
unique index
status machine
```

---

# 18. Cấu trúc thư mục đề xuất

```text
app/
  Domain/
    Betting/
      Enums/
      Data/
      Services/
      Actions/
      Exceptions/
    Wallet/
      Enums/
      Services/
      Actions/
      Exceptions/
    Market/
      Enums/
      Services/
    Settlement/
      Services/
      Actions/
    Leaderboard/
      Services/
  Filament/
    Admin/
      Resources/
      Pages/
      Widgets/
    User/
      Pages/
      Widgets/
  Models/
  Policies/
  Jobs/
  Console/Commands/
  Settings/
  Observers/
  Notifications/

database/
  migrations/
  seeders/
    Wc2026FixtureSeeder.php
    RolePermissionSeeder.php
    GameSettingsSeeder.php

tests/
  Unit/
    SettlementEngineTest.php
    AsianHandicapSettlementTest.php
    OverUnderSettlementTest.php
    WalletLedgerServiceTest.php
  Feature/
    PlaceBetTest.php
    SettleMarketTest.php
    AdminPermissionTest.php
```

---

# 19. Database tables chính

```text
users
roles
permissions
model_has_roles
model_has_permissions
role_has_permissions

departments
wallets
wallet_ledgers

competitions
seasons
teams
stadiums
matches
match_periods
markets
market_outcomes
tỷ lệ ăn_versions

bets
bet_selections
settlements
settlement_lines
approval_requests
approval_logs

leaderboard_snapshots
activity_log
settings
notifications
failed_jobs
jobs
```

---

# 20. Filament Resource mapping

| Model | Resource | Panel |
|---|---|---|
| User | UserResource | Admin |
| Department | DepartmentResource | Admin |
| Wallet | WalletResource | Admin |
| WalletLedger | WalletLedgerResource | Admin/User read-only |
| Competition | CompetitionResource | Admin |
| Season | SeasonResource | Admin |
| Team | TeamResource | Admin |
| Match | MatchResource | Admin/User read-only |
| Market | MarketResource | Admin/User read-only |
| MarketOutcome | MarketOutcomeResource | Admin |
| Bet | BetResource | Admin/User own bets |
| Settlement | SettlementResource/Page | Admin |
| LeaderboardSnapshot | LeaderboardPage | Admin/User |
| ActivityLog | ActivityLogResource | Admin/Auditor |
| GameSettings | SettingsPage | Admin |

---

# 21. UI/UX kỹ thuật cho người chơi

## 21.1. Match card

Hiển thị:

```text
Đội nhà vs đội khách
Giờ đá theo giờ Việt Nam
Trạng thái: sắp mở / đang mở / đã khóa / đã settle
Các tab: Cả trận, H1, H2, Hiệp phụ, Penalty
Countdown đóng kèo
```

## 21.2. Tỷ lệ ăn button

Khi user chọn tỷ lệ ăn:

```text
Lưu outcome_id
Snapshot tỷ lệ ăn
Mở stake input
Tính potential return
Yêu cầu confirm
```

## 21.3. Bet confirmation modal

Modal phải hiển thị:

```text
Trận
Market
Lựa chọn
Tỉ lệ
Số lá đặt
Số lá có thể nhận
Giờ đóng
Cảnh báo: sau khi xác nhận không thể hủy/sửa nếu market đã đóng
```

## 21.4. My bets

Filter:

```text
Pending
Won
Lost
Push
Half won
Half lost
Voided
```

---

# 22. Admin UX kỹ thuật

## 22.1. Settlement preview

Trước khi execute:

```text
Tổng vé pending
Tổng lá locked
Số vé win/lose/push/half
Tổng payout
Tổng profit/loss của user
Danh sách top payout
Cảnh báo bất thường
```

## 22.2. Tỷ lệ ăn versioning

Nếu admin sửa tỷ lệ ăn:

```text
Vé đã đặt giữ tỷ lệ ăn cũ
MarketOutcome tạo version mới hoặc tỷ lệ ăn_version mới
Audit log ghi rõ thay đổi
```

## 22.3. Void market

Void phải:

```text
Yêu cầu lý do
Preview danh sách vé bị ảnh hưởng
Hoàn locked stake về available
Ghi ledger MARKET_VOID
Ghi audit log
```

---

# 23. Testing bắt buộc

## 23.1. Unit test settlement

Các case phải test:

```text
Exact score đúng
Exact score sai
Asian handicap 0 push
Asian handicap -0.25 half lose/win
Asian handicap -0.5 win/lose
Asian handicap -0.75 half win/full win/lose
Asian handicap -1 push/win/lose
Over 2.25 full win/half lose/lose
Under 2.75 full win/half lose/lose
Void hoàn lá
Rollback settlement
```

## 23.2. Feature test

```text
User không đặt vượt số lá
User không đặt sau close_at
User không đặt market locked
User không thấy bet người khác
Operator không được cấp lá
Auditor không được sửa tỷ lệ ăn
Settlement không chạy 2 lần
Wallet ledger cân bằng sau settlement
```

## 23.3. Reconciliation test

Command kiểm tra:

```text
wallet.available + wallet.locked = wallet.total
sum ledger theo user khớp balance hiện tại
pending bets khớp locked_balance
settled bets không còn locked stake
```

---

# 24. Deployment

## 24.1. Server stack

```text
Nginx
PHP-FPM 8.4/8.5
PostgreSQL
Redis
Supervisor
Cron
SSL
```

## 24.2. Deploy commands

```bash
composer install --no-dev --optimize-autoloader
npm ci
npm run build
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
php artisan filament:optimize
php artisan horizon:terminate
```

## 24.3. Cron

```cron
* * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1
```

## 24.4. Supervisor workers

```text
php artisan horizon
php artisan reverb:start nếu dùng Reverb
```

---

# 25. Environment variables gợi ý

```env
APP_NAME="La Du Doan"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://prediction.company.local

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=la_du_doan
DB_USERNAME=app_user
DB_PASSWORD=secret

CACHE_STORE=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

FILESYSTEM_DISK=local

MAIL_MAILER=smtp
MAIL_HOST=
MAIL_PORT=587
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=no-reply@company.local
MAIL_FROM_NAME="La Du Doan"

FILAMENT_FILESYSTEM_DISK=public

REVERB_APP_ID=
REVERB_APP_KEY=
REVERB_APP_SECRET=
REVERB_HOST=
REVERB_PORT=443
REVERB_SCHEME=https
```

---

# 26. Composer + NPM install mẫu cho MVP

## 26.1. Composer

```bash
composer require filament/filament:"^5.0" -W
composer require spatie/laravel-permission
composer require bezhansalleh/filament-shield
composer require jeffgreco13/filament-breezy
composer require spatie/laravel-activitylog
composer require spatie/laravel-settings
composer require filament/spatie-laravel-settings-plugin:"^5.0" -W
composer require spatie/laravel-backup
composer require laravel/horizon
composer require laravel/sanctum
```

## 26.2. Composer dev

```bash
composer require pestphp/pest --dev --with-all-dependencies
composer require pestphp/pest-plugin-laravel --dev
composer require larastan/larastan --dev
composer require laravel/pint --dev
composer require barryvdh/laravel-debugbar --dev
composer require laravel/telescope --dev
```

## 26.3. NPM

```bash
npm install dayjs apexcharts
```

Nếu dùng realtime:

```bash
npm install laravel-echo pusher-js
```

Nếu custom Blade ngoài Filament cần Alpine riêng:

```bash
npm install alpinejs @alpinejs/focus @alpinejs/persist
```

---

# 27. MVP checklist kỹ thuật

## 27.1. Foundation

- [ ] Cài Laravel.
- [ ] Cài Filament 5.
- [ ] Tạo Admin Panel.
- [ ] Tạo User Panel.
- [ ] Cài Shield.
- [ ] Cài Breezy.
- [ ] Cài Activitylog.
- [ ] Cài Settings.
- [ ] Cài Horizon.
- [ ] Cấu hình PostgreSQL.
- [ ] Cấu hình Redis.

## 27.2. Domain

- [ ] Tạo User/Department.
- [ ] Tạo Wallet/WalletLedger.
- [ ] Tạo Competition/Season/Match.
- [ ] Tạo Market/Outcome/Tỷ lệ ănVersion.
- [ ] Tạo Bet.
- [ ] Tạo Settlement/SettlementLine.
- [ ] Viết BetPlacementService.
- [ ] Viết SettlementEngine.
- [ ] Viết WalletLedgerService.
- [ ] Viết LeaderboardService.

## 27.3. Admin

- [ ] UserResource.
- [ ] WalletResource.
- [ ] MatchResource.
- [ ] MarketResource.
- [ ] OutcomeResource.
- [ ] SettlementPage.
- [ ] LeaderboardPage.
- [ ] ActivityLogResource.
- [ ] GameSettingsPage.

## 27.4. User

- [ ] Dashboard.
- [ ] Fixture list.
- [ ] Match detail.
- [ ] Place bet.
- [ ] My bets.
- [ ] Wallet history.
- [ ] Leaderboard.
- [ ] Rules page.

## 27.5. Safety

- [ ] Không có nạp tiền.
- [ ] Không có rút tiền.
- [ ] Không có chuyển lá user-user.
- [ ] Không có đổi lá ra quà/tiền.
- [ ] Có disclaimer điểm ảo.
- [ ] Có audit log.
- [ ] Có role/permission.
- [ ] Có backup.
- [ ] Có test settlement.

---

# 28. Khuyến nghị cuối

Stack tốt nhất cho dự án này:

```text
Laravel 13
Filament 5
PostgreSQL
Redis
Horizon
Blade + Livewire + Alpine.js
Tailwind CSS
Heroicons
ApexCharts
Spatie Permission + Shield
Breezy
Spatie Activitylog
Spatie Settings
Spatie Backup
Pest + Larastan + Pint
```

Không nên phụ thuộc plugin cho phần cốt lõi:

```text
Ví lá
Đặt lá
Settlement
Kèo châu Á
Tài xỉu
Tỉ số chính xác
Audit nghiệp vụ
Correction/rollback
```

Các phần đó nên tự viết service riêng, có test đầy đủ, vì đây là nơi dễ phát sinh sai số, tranh chấp và lỗi bảo mật nhất.

---

# 29. Nguồn tham khảo kỹ thuật

- Filament 5 Documentation: https://filamentphp.com/docs/5.x/introduction/overview
- Filament 3 Installation / Optimize: https://filamentphp.com/docs/3.x/panels/installation
- Filament Shield: https://filamentphp.com/plugins/bezhansalleh-shield
- Filament Breezy: https://filamentphp.com/plugins/jeffgreco-breezy
- Filament Spatie Settings Plugin: https://filamentphp.com/plugins/filament-spatie-settings
- Spatie Laravel Activitylog: https://spatie.be/docs/laravel-activitylog/v5/introduction
- Spatie Laravel Settings: https://github.com/spatie/laravel-settings
- Laravel 13 Installation: https://laravel.com/docs/13.x/installation
- Laravel Horizon: https://laravel.com/docs/13.x/horizon
- Laravel Scheduling: https://laravel.com/docs/13.x/scheduling
- Laravel Redis: https://laravel.com/docs/13.x/redis
- Tailwind CSS: https://tailwindcss.com/
- Heroicons: https://heroicons.com/
- ApexCharts: https://apexcharts.com/
