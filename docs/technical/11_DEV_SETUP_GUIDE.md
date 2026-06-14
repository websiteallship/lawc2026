---
title: "Developer Setup Guide"
project: "Dự Đoán Lá"
version: "1.0"
status: "draft"
last_updated: "2026-06-11"
owner: "Engineering"
---

# Developer Setup Guide

## 1. Mục tiêu

Tài liệu này giúp developer clone repo và chạy được hệ thống **Dự Đoán Lá** trên máy local hoặc môi trường staging nội bộ.

Hệ thống được định hướng là web dự đoán bóng đá nội bộ bằng điểm ảo `lá`, không có nạp/rút/quy đổi. Stack ưu tiên:

```text
Laravel
Filament
Livewire
Alpine.js
Tailwind CSS
PostgreSQL
Redis
Queue worker
Scheduler
```

Version và stack mặc định đã được chốt trong `docs/technical/21_TECH_DECISIONS.md`.

---

## 2. Yêu cầu môi trường local

## 2.1. Runtime bắt buộc

| Thành phần | Mục đích | Ghi chú |
|---|---|---|
| PHP | Chạy Laravel | Bật extension phổ biến: pdo_pgsql, mbstring, intl, bcmath, redis |
| Composer | Quản lý PHP package | Dùng lockfile để đồng bộ version |
| Node.js | Build asset | Dùng version theo `.nvmrc` nếu có |
| npm/pnpm/yarn | Quản lý JS package | Chọn 1 package manager chính |
| PostgreSQL | Database chính | Free/open-source; không dùng SQLite cho nghiệp vụ ví/settlement |
| Redis | Cache, queue, lock | Cần cho job, scheduler, lock |
| Git | Version control | Theo flow repo |

## 2.2. Extension PHP nên bật

```text
bcmath
ctype
curl
dom
fileinfo
filter
hash
intl
json
mbstring
openssl
pdo
pdo_pgsql
redis
session
tokenizer
xml
zip
```

Lý do cần `bcmath`: nghiệp vụ payout, profit rate, stake split cần tránh sai số float.

---

## 3. Clone repo và cài dependency

```bash
git clone <repo-url> du-doan-la
cd du-doan-la
composer install
npm install
```

Nếu dùng pnpm:

```bash
pnpm install
```

Không commit thư mục:

```text
/vendor
/node_modules
/storage/logs/*.log
.env
```

---

## 4. Cấu hình `.env`

Tạo file môi trường:

```bash
cp .env.example .env
php artisan key:generate
```

Các biến bắt buộc:

```dotenv
APP_NAME="Du Doan La"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=du_doan_la
DB_USERNAME=postgres
DB_PASSWORD=secret

CACHE_STORE=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

APP_TIMEZONE=Asia/Ho_Chi_Minh
```

## 4.1. Quy ước timezone

Toàn hệ thống dùng:

```text
Asia/Ho_Chi_Minh
```

Quy tắc:

- Lưu database business datetime theo Asia/Ho_Chi_Minh.
- UI hiển thị giờ Việt Nam.
- `open_at`, `close_at`, `kickoff_at` phải được parse rõ timezone.
- Test case phải cố định timezone để tránh lỗi khóa market.

---

## 5. Tạo database local

Ví dụ PostgreSQL:

```bash
createdb du_doan_la
php artisan migrate
php artisan db:seed
```

PostgreSQL itself is free/open-source; chi phí thực tế nằm ở VPS, storage, backup, và công vận hành nếu dùng managed service.

Nếu cần reset:

```bash
php artisan migrate:fresh --seed
```

Không dùng `migrate:fresh` trên staging/production.

---

## 6. Seed dữ liệu ban đầu

Seeder tối thiểu:

```text
RoleSeeder
PermissionSeeder
SystemSettingSeeder
SuperAdminSeeder
DemoUserSeeder
SeasonSeeder
FixtureSeeder
MarketSeeder
WalletSeeder
```

Dữ liệu bắt buộc sau seed:

| Dữ liệu | Mục đích |
|---|---|
| Super Admin | Đăng nhập admin đầu tiên |
| Roles/Permissions | Test Shield/permission |
| System settings | Max stake, min stake, max profit rate |
| Season demo | Tạo bối cảnh WC2026 |
| Match demo | Test market |
| Market demo | Test exact score, handicap, tài/xỉu |
| User demo | Test đặt lá |
| Wallet demo | Test ví |

---

## 7. Tạo user admin đầu tiên

Nếu dùng Filament command:

```bash
php artisan make:filament-user
```

Nếu repo có command riêng:

```bash
php artisan ddl:create-super-admin
```

Thông tin admin không được commit vào repo. Dùng `.env`, prompt CLI hoặc secret manager.

---

## 8. Chạy ứng dụng local

Terminal 1:

```bash
php artisan serve
```

Terminal 2:

```bash
npm run dev
```

Terminal 3:

```bash
php artisan queue:work
```

Terminal 4:

```bash
php artisan schedule:work
```

URL mặc định:

```text
User frontend: http://localhost:8000
Admin panel:   http://localhost:8000/admin
```

---

## 9. Các command nên có trong repo

## 9.1. Command setup nhanh

```bash
php artisan app:install-local
```

Command này nên làm:

```text
- Kiểm tra .env
- Kiểm tra database connection
- Chạy migrate
- Chạy seeder
- Tạo storage link
- Clear cache
- Hiển thị tài khoản demo
```

## 9.2. Command khóa market

```bash
php artisan markets:lock-expired
```

Chạy qua scheduler mỗi phút hoặc 30 giây tùy thiết kế.

## 9.3. Command preview settlement

```bash
php artisan settlements:preview {match_code}
```

Dùng để debug nghiệp vụ.

## 9.4. Command rebuild leaderboard

```bash
php artisan leaderboard:rebuild --season=wc2026
```

Dùng khi correction/re-settlement hoặc deploy thay đổi công thức leaderboard.

---

## 10. Coding convention

## 10.1. Nguyên tắc chung

```text
- Không viết nghiệp vụ ví trong Controller/Filament Action.
- Không tự update balance trực tiếp ngoài WalletService.
- Không tự settle trong Resource callback.
- Không dùng float cho tiền/lá/payout.
- Không xóa bet, settlement, ledger đã phát sinh.
- Mọi thao tác thay đổi lá phải có wallet_ledger.
- Mọi thao tác admin nhạy cảm phải có audit log.
```

## 10.2. Namespace đề xuất

```text
app/
  Domain/
    Wallet/
    Betting/
    Settlement/
    Market/
    Leaderboard/
    Audit/
  Filament/
    Resources/
    Pages/
    Widgets/
  Actions/
  Console/Commands/
```

## 10.3. Service naming

```text
BetPlacementService
WalletService
MarketLockService
SettlementEngine
ExactScoreSettlementCalculator
AsianHandicapSettlementCalculator
OverUnderSettlementCalculator
LeaderboardService
CorrectionService
```

---

## 11. Quy ước odds kiểu Việt Nam

UI/admin nhập và hiển thị:

```text
Home -0.5 ăn 0.90
Tài 2.5 ăn 0.90
Tỉ số 2-1 ăn 6.00
```

Field kỹ thuật:

```text
profit_rate = 0.90
```

Công thức:

```text
gross_payout_full_win = stake * (1 + profit_rate)
net_profit_full_win = stake * profit_rate
```

Ví dụ:

```text
Stake = 100 lá
Ăn = 0.90
Thắng đủ => nhận 190 lá
Thua => nhận 0 lá
Push => nhận 100 lá
Half win => nhận 145 lá
Half lose => nhận 50 lá
```

Không nhập `1.90` vào ô “ăn” nếu ý nghĩa là `ăn 0.90`.

---

## 12. Chạy test

```bash
php artisan test
```

Nhóm test bắt buộc:

```text
Feature/Auth
Feature/Permission
Feature/BetPlacement
Feature/Wallet
Feature/MarketLock
Feature/Settlement
Feature/Leaderboard
Unit/SettlementCalculators
Unit/WalletLedger
```

Test nghiệp vụ phải dùng dataset rõ:

```text
Home -0.5 ăn 0.90, stake 100, Home thắng 1 => payout 190
Home -0.75 ăn 0.90, stake 100, Home thắng 1 => payout 145
Home -1 ăn 0.90, stake 100, Home thắng 1 => payout 100
Tài 2.25 ăn 0.90, stake 100, tổng 3 bàn => payout 190
Xỉu 2.25 ăn 0.90, stake 100, tổng 2 bàn => payout 145
```

---

## 13. Local debugging checklist

Khi lỗi đặt lá:

```text
1. Market có status OPEN không?
2. current_time có nhỏ hơn close_at không?
3. User có đủ available_balance không?
4. Outcome có status ACTIVE không?
5. profit_rate_snapshot có được lưu không?
6. Wallet ledger BET_PLACED có tạo không?
7. Transaction có rollback không?
```

Khi lỗi settlement:

```text
1. Market có bets pending không?
2. Result period đã nhập đủ chưa?
3. Calculator đúng market_type không?
4. gross_payout đúng không?
5. locked_balance có giảm không?
6. available_balance có tăng payout không?
7. settlement_items có đủ số lượng bet không?
8. Job có chạy lại gây double payout không?
```

---

## 14. Pull request checklist

Mỗi PR cần kiểm tra:

```text
- Có migration/test liên quan không?
- Có ảnh hưởng wallet/settlement không?
- Có test cho happy path và edge case không?
- Có dùng transaction khi thay đổi nhiều bảng không?
- Có audit log cho action admin không?
- Có cập nhật tài liệu docs nếu thay đổi rule không?
- Có tránh dùng float không?
- Có tránh direct balance update không?
```

---

## 15. Definition of Done cho developer

Một task chỉ được xem là xong khi:

```text
- Code pass test.
- Không có lỗi static analysis nghiêm trọng.
- Migration rollback được hoặc có hướng rollback dữ liệu.
- Có audit log nếu là admin action.
- Có permission check nếu liên quan admin/user role.
- Có validation message rõ.
- Có cập nhật docs nếu thay đổi behavior.
```
