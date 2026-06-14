---
title: "Testing Master Guide - Dự án Dự Đoán Lá"
version: "1.0.0"
status: "ready-for-repo"
owner: "Product / Engineering / QA"
last_updated: "2026-06-12"
related_docs:
  - docs/technical/05_SETTLEMENT_ENGINE_SPECIFICATION.md
  - docs/technical/04_ERD_DATABASE_DESIGN.md
  - docs/technical/17_DOMAIN_SERVICE_DESIGN.md
  - docs/requirements/03_SRS.md
  - docs/requirements/06_PERMISSION_MATRIX.md
  - docs/operation/08_TEST_PLAN.md
  - AGENTS.md
---

# Testing Master Guide - Dự án Dự Đoán Lá

Tài liệu này là hướng dẫn kiểm thử tổng thể cho hệ thống **dự đoán bóng đá nội bộ bằng điểm ảo “lá”**. Tài liệu dùng cho developer, QA, admin vận hành và AI coding agent khi viết test hoặc review hệ thống.

Hệ thống không phải nền tảng cá cược tiền thật. Mọi test phải giữ đúng nguyên tắc:

```txt
Không nạp tiền.
Không rút tiền.
Không mua bán lá.
Không chuyển lá giữa người chơi.
Không quy đổi lá thành tiền, quà, hiện vật hoặc dịch vụ.
Không public registration.
```

Quy ước odds kiểu Việt Nam:

```txt
Brazil -0.5 ăn 0.90
profit_rate = 0.90
stake = 100 lá
full_win_gross_payout = 100 × (1 + 0.90) = 190 lá
net_profit = 90 lá
```

---

## 1. Mục tiêu kiểm thử

### 1.1. Mục tiêu chính

Đảm bảo hệ thống:

1. Tính lá đúng trong mọi trường hợp.
2. Không làm sai số dư ví.
3. Không cho user đặt khi market đã đóng.
4. Không cho user đặt vượt số lá khả dụng.
5. Không cho admin sửa dữ liệu lịch sử âm thầm.
6. Settlement chạy đúng, chạy lại không nhân đôi tiền.
7. Correction/void tạo dòng điều chỉnh rõ ràng, không xóa lịch sử.
8. Quyền admin/user/auditor được kiểm soát đúng.
9. UI hiển thị đúng odds kiểu Việt Nam: `ăn 0.90`, không nhầm với decimal odds `1.90`.
10. Import/export dữ liệu không làm hỏng hệ thống.

### 1.2. Nguyên tắc test bắt buộc

```txt
- Test nghiệp vụ phải độc lập với UI.
- WalletService phải có unit/integration test riêng.
- SettlementEngine phải có test matrix đầy đủ.
- Filament Action chỉ gọi service, không chứa logic tính lá.
- Mọi thay đổi balance phải sinh wallet_ledger.
- Mọi admin action nhạy cảm phải sinh audit log.
- Mọi test payout phải dùng profit_rate, không dùng decimal odds làm input chính.
```

---

## 2. Phạm vi kiểm thử

## 2.1. In scope

| Nhóm | Nội dung |
|---|---|
| Auth | Login, logout, session, 2FA admin nếu bật |
| Role & permission | Super Admin, Operator, Settlement Manager, Auditor, User |
| User | Đặt lá, xem ví, xem vé, xem leaderboard |
| Admin | User, wallet, season, match, market, odds, settlement, settings |
| Wallet | Grant, deduct, lock stake, release stake, payout, correction |
| Bet | Đặt vé, snapshot odds, trạng thái vé |
| Market | Open, locked, settled, voided, cancelled |
| Settlement | Exact score, Asian handicap, Over/Under, penalty |
| Correction | Sửa kết quả sau settle, tạo adjustment |
| Void | Hủy market và hoàn lá |
| Leaderboard | Net profit, ROI, win rate, exact score wins |
| Import/export | User, fixture, market, odds, wallet grant |
| Security | Permission bypass, CSRF, XSS, rate limit, admin hardening |
| Concurrency | Hai request đặt cùng lúc, đặt sát giờ đóng, settle chạy 2 lần |
| Audit | Log thao tác admin, log wallet, log settlement |
| Deployment | Migration, seed, queue, scheduler, backup, rollback |

## 2.2. Out of scope giai đoạn 1

```txt
- Nạp tiền thật.
- Rút tiền thật.
- Chuyển lá giữa user.
- Quy đổi lá ra quà/tài sản.
- Public registration.
- App mobile native.
- Odds realtime từ nhà cung cấp bên ngoài.
- Department/team leaderboard.
```

---

## 3. Chiến lược kiểm thử

## 3.1. Test pyramid

| Tầng test | Mục đích | Ưu tiên |
|---|---|---|
| Unit test | Calculator, enum, helper, value object | Rất cao |
| Integration test | WalletService, BetPlacementService, SettlementEngine | Rất cao |
| Feature test | Route/API/Livewire/Filament action | Cao |
| Browser/E2E test | Luồng user/admin quan trọng | Trung bình/cao |
| Manual UAT | Admin vận hành thật | Cao |
| Security test | Quyền, auth, input, audit | Cao |
| Load/concurrency test | Race condition, settle hàng loạt | Cao |

## 3.2. Công cụ đề xuất

| Mục đích | Công cụ |
|---|---|
| PHP test | Pest hoặc PHPUnit |
| Static analysis | PHPStan hoặc Larastan |
| Code style | Laravel Pint |
| Browser test | Laravel Dusk hoặc Playwright |
| DB test | PostgreSQL test database |
| Queue test | Laravel queue fake / Redis staging |
| API test | Pest feature test / Postman / Bruno |
| Security checklist | Manual + automated assertions |

Lệnh CI cơ bản:

```bash
composer install --no-interaction --prefer-dist
npm ci
php artisan key:generate --env=testing
php artisan migrate:fresh --seed --env=testing
php artisan test
./vendor/bin/pint --test
./vendor/bin/phpstan analyse
npm run build
```

---

## 4. Test data chuẩn

## 4.1. User mẫu

| User | Role | Lá ban đầu |
|---|---|---:|
| super_admin@example.test | Super Admin | 0 |
| operator@example.test | Operator | 0 |
| settlement@example.test | Settlement Manager | 0 |
| auditor@example.test | Auditor | 0 |
| user_a@example.test | User | 1,000 |
| user_b@example.test | User | 1,000 |
| user_c@example.test | User | 50 |
| inactive_user@example.test | User inactive | 1,000 |

## 4.2. Trận mẫu

| Match code | Home | Away | Kickoff |
|---|---|---|---|
| M001 | Brazil | Germany | Future time + 2 hours |
| M002 | Argentina | France | Future time + 1 day |
| M003 | Spain | Portugal | Past time - 1 day |

## 4.3. Market mẫu

| Market | Period | Type | Close at |
|---|---|---|---|
| M001_FULL_AH | FULL_TIME | ASIAN_HANDICAP | kickoff - 5 minutes |
| M001_FULL_OU | FULL_TIME | OVER_UNDER | kickoff - 5 minutes |
| M001_FULL_SCORE | FULL_TIME | EXACT_SCORE | kickoff - 5 minutes |
| M001_H1_AH | FIRST_HALF | ASIAN_HANDICAP | kickoff - 5 minutes |
| M001_H2_AH | SECOND_HALF | ASIAN_HANDICAP | halftime + 5 minutes |

## 4.4. Odds mẫu kiểu Việt Nam

| Market | Selection | Line | Ăn | Meaning |
|---|---|---:|---:|---|
| Asian handicap | Brazil | -0.5 | 0.90 | Brazil thắng là thắng vé |
| Asian handicap | Germany | +0.5 | 0.90 | Germany hòa/thắng là thắng vé |
| Asian handicap | Brazil | -0.75 | 0.90 | Brazil thắng 1 là nửa thắng |
| Asian handicap | Brazil | -1.00 | 0.90 | Brazil thắng 1 là push |
| Over/Under | Tài | 2.5 | 0.90 | Tổng bàn > 2.5 là thắng |
| Over/Under | Xỉu | 2.5 | 0.90 | Tổng bàn < 2.5 là thắng |
| Exact score | 2-1 | null | 7.00 | Đúng tỉ số 2-1 là thắng |

---

## 5. Unit test - Odds và payout

## 5.1. Vietnamese odds convention

| Case ID | Input | Expected |
|---|---|---|
| ODDS-001 | `profit_rate = 0.90`, stake `100` | gross payout `190` |
| ODDS-002 | `profit_rate = 0.85`, stake `200` | gross payout `370` |
| ODDS-003 | `profit_rate = 7.00`, stake `10` | gross payout `80` |
| ODDS-004 | `profit_rate = 0` | reject hoặc allow theo setting; nếu allow payout = stake |
| ODDS-005 | `profit_rate < 0` | reject |
| ODDS-006 | `profit_rate = 1.90` ở kèo handicap thông thường | cảnh báo nếu vượt ngưỡng config |

## 5.2. Công thức payout chuẩn

```txt
full_win_gross_payout = stake × (1 + profit_rate)
full_win_net_profit = stake × profit_rate
push_payout = stake
full_loss_payout = 0
half_win_payout = stake/2 × (1 + profit_rate) + stake/2
half_loss_payout = stake/2
```

Test case:

| Case ID | Result | Stake | Ăn | Expected payout |
|---|---|---:|---:|---:|
| PAY-001 | Full win | 100 | 0.90 | 190 |
| PAY-002 | Full lose | 100 | 0.90 | 0 |
| PAY-003 | Push | 100 | 0.90 | 100 |
| PAY-004 | Half win | 100 | 0.90 | 145 |
| PAY-005 | Half lose | 100 | 0.90 | 50 |
| PAY-006 | Full win | 101 | 0.90 | theo rounding rule |

## 5.3. Rounding rule

Phải chọn một rule và test cố định. Khuyến nghị cho hệ thống lá:

```txt
- Stake là integer.
- Balance là integer.
- Payout được round down/floor về integer.
- Quarter split với stake lẻ phải xử lý bằng hai phần: floor(stake/2) và phần còn lại.
```

Ví dụ stake 101, split quarter line:

```txt
part_1 = 50
part_2 = 51
```

Test:

| Case ID | Stake | Split expected |
|---|---:|---|
| ROUND-001 | 100 | 50 + 50 |
| ROUND-002 | 101 | 50 + 51 |
| ROUND-003 | 1 | reject nếu min stake > 1 |

---

## 6. Unit test - Exact score settlement

## 6.1. Quy tắc

Exact score thắng khi tỉ số thực tế đúng hoàn toàn với selection.

```txt
User chọn Brazil 2-1 Germany ăn 7.00
Stake 100
Nếu kết quả full-time 2-1 => payout 800
Nếu kết quả khác => payout 0
```

## 6.2. Test matrix

| Case ID | Selection | Actual | Stake | Ăn | Expected status | Payout |
|---|---|---|---:|---:|---|---:|
| ES-001 | 2-1 | 2-1 | 100 | 7.00 | WON | 800 |
| ES-002 | 2-1 | 1-2 | 100 | 7.00 | LOST | 0 |
| ES-003 | 0-0 | 0-0 | 100 | 5.00 | WON | 600 |
| ES-004 | 3-3 | 3-2 | 100 | 12.00 | LOST | 0 |
| ES-005 | H1 1-0 | H1 result 1-0 | 100 | 4.00 | WON | 500 |
| ES-006 | H2 1-0 | H2 independent result 1-0 | 100 | 4.00 | WON | 500 |

## 6.3. Edge cases

| Case ID | Case | Expected |
|---|---|---|
| ES-E01 | Result not entered | Cannot settle |
| ES-E02 | Score negative | Validation error |
| ES-E03 | Score null | Validation error |
| ES-E04 | Market voided | Refund stake |
| ES-E05 | Bet already settled | Idempotent, no duplicate payout |

---

## 7. Unit test - Asian handicap settlement

## 7.1. Quy tắc tổng quát

Tính điểm sau handicap:

```txt
home_adjusted_score = home_score + home_handicap
away_adjusted_score = away_score + away_handicap
```

Với lựa chọn Home -0.5:

```txt
Nếu home_score - away_score > 0.5 => win
Ngược lại => lose
```

Nên implement theo hướng chia quarter line thành hai line đơn.

## 7.2. Full line và half line

### Home -0.5 ăn 0.90

| Case ID | Actual | Stake | Expected | Payout |
|---|---|---:|---|---:|
| AH-HM05-001 | Home thắng 1 | 100 | WON | 190 |
| AH-HM05-002 | Hòa | 100 | LOST | 0 |
| AH-HM05-003 | Home thua 1 | 100 | LOST | 0 |

### Away +0.5 ăn 0.90

| Case ID | Actual | Stake | Expected | Payout |
|---|---|---:|---|---:|
| AH-AP05-001 | Home thắng 1 | 100 | LOST | 0 |
| AH-AP05-002 | Hòa | 100 | WON | 190 |
| AH-AP05-003 | Away thắng 1 | 100 | WON | 190 |

### Home 0 ăn 0.90

| Case ID | Actual | Stake | Expected | Payout |
|---|---|---:|---|---:|
| AH-H0-001 | Home thắng 1 | 100 | WON | 190 |
| AH-H0-002 | Hòa | 100 | PUSH | 100 |
| AH-H0-003 | Home thua 1 | 100 | LOST | 0 |

### Home -1 ăn 0.90

| Case ID | Actual | Stake | Expected | Payout |
|---|---|---:|---|---:|
| AH-HM1-001 | Home thắng 2 | 100 | WON | 190 |
| AH-HM1-002 | Home thắng 1 | 100 | PUSH | 100 |
| AH-HM1-003 | Hòa | 100 | LOST | 0 |

## 7.3. Quarter line

### Home -0.25 ăn 0.90

Split:

```txt
-0.25 = 0 + -0.5
stake 100 = 50 ở kèo 0, 50 ở kèo -0.5
```

| Case ID | Actual | Expected | Payout |
|---|---|---|---:|
| AH-HM025-001 | Home thắng 1 | WON | 190 |
| AH-HM025-002 | Hòa | HALF_LOST | 50 |
| AH-HM025-003 | Home thua 1 | LOST | 0 |

### Home -0.75 ăn 0.90

Split:

```txt
-0.75 = -0.5 + -1.0
```

| Case ID | Actual | Expected | Payout |
|---|---|---|---:|
| AH-HM075-001 | Home thắng 2 | WON | 190 |
| AH-HM075-002 | Home thắng 1 | HALF_WON | 145 |
| AH-HM075-003 | Hòa | LOST | 0 |

### Home -1.25 ăn 0.90

Split:

```txt
-1.25 = -1.0 + -1.5
```

| Case ID | Actual | Expected | Payout |
|---|---|---|---:|
| AH-HM125-001 | Home thắng 2 | WON | 190 |
| AH-HM125-002 | Home thắng 1 | HALF_LOST | 50 |
| AH-HM125-003 | Hòa | LOST | 0 |

### Home -1.75 ăn 0.90

Split:

```txt
-1.75 = -1.5 + -2.0
```

| Case ID | Actual | Expected | Payout |
|---|---|---|---:|
| AH-HM175-001 | Home thắng 3 | WON | 190 |
| AH-HM175-002 | Home thắng 2 | HALF_WON | 145 |
| AH-HM175-003 | Home thắng 1 | LOST | 0 |

## 7.4. Away quarter line tương ứng

Cần mirror test với Away:

| Case ID | Selection | Actual | Expected | Payout |
|---|---|---|---|---:|
| AH-A025-001 | Away +0.25 | Hòa | HALF_WON | 145 |
| AH-A025-002 | Away +0.25 | Away thua 1 | LOST | 0 |
| AH-A075-001 | Away +0.75 | Away thua 1 | HALF_LOST | 50 |
| AH-A075-002 | Away +0.75 | Hòa | WON | 190 |
| AH-A125-001 | Away +1.25 | Away thua 1 | HALF_WON | 145 |
| AH-A125-002 | Away +1.25 | Away thua 2 | LOST | 0 |

---

## 8. Unit test - Over/Under settlement

## 8.1. Quy tắc

```txt
total_goals = home_score + away_score
```

Over thắng nếu tổng bàn lớn hơn line. Under thắng nếu tổng bàn nhỏ hơn line. Line nguyên có thể push. Quarter line split thành hai line.

## 8.2. Half line

### Tài 2.5 ăn 0.90

| Case ID | Actual total | Stake | Expected | Payout |
|---|---:|---:|---|---:|
| OU-O25-001 | 3 | 100 | WON | 190 |
| OU-O25-002 | 2 | 100 | LOST | 0 |
| OU-O25-003 | 0 | 100 | LOST | 0 |

### Xỉu 2.5 ăn 0.90

| Case ID | Actual total | Stake | Expected | Payout |
|---|---:|---:|---|---:|
| OU-U25-001 | 2 | 100 | WON | 190 |
| OU-U25-002 | 3 | 100 | LOST | 0 |

## 8.3. Integer line

### Tài 2.0 ăn 0.90

| Case ID | Actual total | Expected | Payout |
|---|---:|---|---:|
| OU-O20-001 | 3 | WON | 190 |
| OU-O20-002 | 2 | PUSH | 100 |
| OU-O20-003 | 1 | LOST | 0 |

### Xỉu 2.0 ăn 0.90

| Case ID | Actual total | Expected | Payout |
|---|---:|---|---:|
| OU-U20-001 | 1 | WON | 190 |
| OU-U20-002 | 2 | PUSH | 100 |
| OU-U20-003 | 3 | LOST | 0 |

## 8.4. Quarter line

### Tài 2.25 ăn 0.90

Split:

```txt
Tài 2.25 = Tài 2.0 + Tài 2.5
```

| Case ID | Actual total | Expected | Payout |
|---|---:|---|---:|
| OU-O225-001 | 3 | WON | 190 |
| OU-O225-002 | 2 | HALF_LOST | 50 |
| OU-O225-003 | 1 | LOST | 0 |

### Xỉu 2.25 ăn 0.90

Split:

```txt
Xỉu 2.25 = Xỉu 2.0 + Xỉu 2.5
```

| Case ID | Actual total | Expected | Payout |
|---|---:|---|---:|
| OU-U225-001 | 1 | WON | 190 |
| OU-U225-002 | 2 | HALF_WON | 145 |
| OU-U225-003 | 3 | LOST | 0 |

### Tài 2.75 ăn 0.90

Split:

```txt
Tài 2.75 = Tài 2.5 + Tài 3.0
```

| Case ID | Actual total | Expected | Payout |
|---|---:|---|---:|
| OU-O275-001 | 4 | WON | 190 |
| OU-O275-002 | 3 | HALF_WON | 145 |
| OU-O275-003 | 2 | LOST | 0 |

### Xỉu 2.75 ăn 0.90

Split:

```txt
Xỉu 2.75 = Xỉu 2.5 + Xỉu 3.0
```

| Case ID | Actual total | Expected | Payout |
|---|---:|---|---:|
| OU-U275-001 | 2 | WON | 190 |
| OU-U275-002 | 3 | HALF_LOST | 50 |
| OU-U275-003 | 4 | LOST | 0 |

---

## 9. Unit test - Period result

## 9.1. Period definitions

| Period | Rule |
|---|---|
| FULL_TIME | 90 phút + bù giờ, không gồm hiệp phụ/penalty |
| FIRST_HALF | Hiệp 1 + bù giờ hiệp 1 |
| SECOND_HALF | Chỉ bàn trong hiệp 2, tính độc lập |
| EXTRA_TIME | 30 phút hiệp phụ nếu có |
| PENALTY | Loạt luân lưu nếu có |

## 9.2. Test period score calculation

Trận thực tế:

```txt
H1: Brazil 1-0 Germany
FT: Brazil 2-1 Germany
ET: Brazil 3-2 Germany
PEN: Brazil 4-3 Germany
```

Expected:

| Case ID | Period | Expected score |
|---|---|---|
| PERIOD-001 | FIRST_HALF | 1-0 |
| PERIOD-002 | SECOND_HALF | 1-1 |
| PERIOD-003 | FULL_TIME | 2-1 |
| PERIOD-004 | EXTRA_TIME | 1-1 nếu tính riêng ET |
| PERIOD-005 | PENALTY | 4-3 |

---

## 10. Integration test - WalletService

## 10.1. Grant leaves

| Case ID | Action | Initial | Amount | Expected available | Ledger |
|---|---|---:|---:|---:|---|
| WAL-GRANT-001 | Admin grant | 0 | 1000 | 1000 | ADMIN_GRANT |
| WAL-GRANT-002 | Admin grant | 1000 | 500 | 1500 | ADMIN_GRANT |
| WAL-GRANT-003 | Grant negative | 1000 | -100 | reject | none |

## 10.2. Deduct leaves

| Case ID | Initial | Amount | Expected |
|---|---:|---:|---|
| WAL-DED-001 | 1000 | 100 | available = 900 |
| WAL-DED-002 | 50 | 100 | reject nếu không cho âm |
| WAL-DED-003 | 0 | 1 | reject |

## 10.3. Lock stake

| Case ID | Available | Locked | Stake | Expected available | Expected locked |
|---|---:|---:|---:|---:|---:|
| WAL-LOCK-001 | 1000 | 0 | 100 | 900 | 100 |
| WAL-LOCK-002 | 50 | 0 | 100 | reject | reject |
| WAL-LOCK-003 | 1000 | 100 | 200 | 800 | 300 |

## 10.4. Settle payout

| Case ID | Before available | Before locked | Stake | Payout | Expected available | Expected locked |
|---|---:|---:|---:|---:|---:|---:|
| WAL-SET-001 | 900 | 100 | 100 | 190 | 1090 | 0 |
| WAL-SET-002 | 900 | 100 | 100 | 0 | 900 | 0 |
| WAL-SET-003 | 900 | 100 | 100 | 100 | 1000 | 0 |
| WAL-SET-004 | 900 | 100 | 100 | 145 | 1045 | 0 |

## 10.5. Ledger consistency

Mỗi thay đổi ví phải tạo ledger:

| Case ID | Action | Required ledger |
|---|---|---|
| WAL-LEDGER-001 | Grant | ADMIN_GRANT |
| WAL-LEDGER-002 | Place bet | BET_PLACED |
| WAL-LEDGER-003 | Win | BET_SETTLED_WIN |
| WAL-LEDGER-004 | Lose | BET_SETTLED_LOSS |
| WAL-LEDGER-005 | Push | BET_SETTLED_PUSH |
| WAL-LEDGER-006 | Void | MARKET_VOID_REFUND |
| WAL-LEDGER-007 | Correction | CORRECTION_ADJUSTMENT |

---

## 11. Integration test - BetPlacementService

## 11.1. Happy path

| Case ID | Scenario | Expected |
|---|---|---|
| BET-001 | User đặt Brazil -0.5 ăn 0.90, stake 100 | Bet PENDING, available -100, locked +100 |
| BET-002 | User đặt Tài 2.5 ăn 0.90, stake 50 | Bet PENDING, snapshot correct |
| BET-003 | User đặt tỉ số 2-1 ăn 7.00 | Bet PENDING, potential payout 800 |

## 11.2. Validation

| Case ID | Scenario | Expected error |
|---|---|---|
| BET-V001 | Market draft | MARKET_NOT_OPEN |
| BET-V002 | Market locked | MARKET_CLOSED |
| BET-V003 | Market settled | MARKET_NOT_BETTABLE |
| BET-V004 | User inactive | USER_INACTIVE |
| BET-V005 | User không đủ lá | INSUFFICIENT_BALANCE |
| BET-V006 | Stake nhỏ hơn min | STAKE_BELOW_MINIMUM |
| BET-V007 | Stake lớn hơn max per bet | STAKE_EXCEEDS_MAX_PER_BET |
| BET-V008 | Stake vượt max per match | STAKE_EXCEEDS_MAX_PER_MATCH |
| BET-V009 | Stake không phải số nguyên | INVALID_STAKE |
| BET-V010 | Outcome inactive | OUTCOME_NOT_AVAILABLE |

## 11.3. Snapshot

Khi đặt vé, cần snapshot:

```txt
market_id
outcome_id
selection_type_snapshot
line_snapshot
profit_rate_snapshot
display_odds_snapshot
period_type_snapshot
market_type_snapshot
```

Test:

| Case ID | Scenario | Expected |
|---|---|---|
| BET-SNAP-001 | Admin đổi odds sau khi user đặt | Bet cũ giữ profit_rate cũ |
| BET-SNAP-002 | Admin đổi label outcome | Bet cũ giữ display_odds_snapshot cũ |
| BET-SNAP-003 | Settlement sau khi odds đổi | Tính theo snapshot của bet |

---

## 12. Integration test - Market lifecycle

## 12.1. State transition

| From | To | Allowed? |
|---|---|---|
| DRAFT | OPEN | Yes |
| OPEN | LOCKED | Yes |
| LOCKED | SETTLING | Yes |
| SETTLING | SETTLED | Yes |
| OPEN | VOIDED | Yes, refund pending bets |
| LOCKED | VOIDED | Yes, refund pending bets |
| SETTLED | VOIDED | No, dùng correction |
| SETTLED | SETTLING | No, dùng re-settlement/correction flow |

## 12.2. Test cases

| Case ID | Scenario | Expected |
|---|---|---|
| MKT-001 | Publish market draft | status OPEN |
| MKT-002 | Lock market manually | status LOCKED |
| MKT-003 | Scheduler lock market khi quá close_at | status LOCKED |
| MKT-004 | User đặt sau close_at nhưng status còn OPEN | reject MARKET_CLOSED |
| MKT-005 | Admin sửa odds khi chưa có bet | allowed |
| MKT-006 | Admin sửa odds khi đã có bet | tạo odds version mới hoặc yêu cầu confirmation |
| MKT-007 | Void market có pending bet | hoàn locked stake |

---

## 13. Integration test - Settlement workflow

## 13.1. Settlement preview

Preview không được thay đổi balance.

| Case ID | Scenario | Expected |
|---|---|---|
| SET-PREV-001 | Preview 10 bet | No wallet change |
| SET-PREV-002 | Preview hiển thị total payout | correct |
| SET-PREV-003 | Preview có bet win/loss/push/half | correct count |
| SET-PREV-004 | Preview khi thiếu result | reject RESULT_REQUIRED |

## 13.2. Execute settlement

| Case ID | Scenario | Expected |
|---|---|---|
| SET-EXE-001 | Execute settlement lần đầu | settlement_items created, wallet updated |
| SET-EXE-002 | Execute settlement lần hai | no duplicate payout, error or idempotent no-op |
| SET-EXE-003 | Có 0 bet | market SETTLED, no wallet changes |
| SET-EXE-004 | Một bet lỗi payout | transaction rollback toàn bộ |
| SET-EXE-005 | Queue bị fail giữa chừng | rollback hoặc retry an toàn |

## 13.3. Bet status after settlement

| Outcome | Bet status |
|---|---|
| Full win | WON |
| Full lose | LOST |
| Push | PUSH |
| Half win | HALF_WON |
| Half lose | HALF_LOST |
| Void | VOIDED |

---

## 14. Correction và re-settlement test

## 14.1. Correction nguyên tắc

Không sửa/xóa settlement cũ. Tạo correction record và ledger adjustment.

```txt
Original settlement: Brazil 2-1 Germany
Corrected result: Brazil 1-1 Germany
System computes delta between old payout and new payout.
Apply delta to wallet via CORRECTION_ADJUSTMENT ledger.
```

## 14.2. Test cases

| Case ID | Scenario | Expected |
|---|---|---|
| COR-001 | Bet cũ WON payout 190, kết quả mới LOST payout 0 | wallet delta -190 |
| COR-002 | Bet cũ LOST payout 0, kết quả mới WON payout 190 | wallet delta +190 |
| COR-003 | Bet cũ PUSH 100, kết quả mới WON 190 | wallet delta +90 |
| COR-004 | Bet cũ HALF_WON 145, kết quả mới LOST 0 | wallet delta -145 |
| COR-005 | Correction làm available âm | reject hoặc tạo negative_adjustment_pending theo rule |
| COR-006 | Correction không có reason | reject |
| COR-007 | Correction bởi operator không đủ quyền | reject |
| COR-008 | Correction được duyệt bởi settlement manager | apply delta |

---

## 15. Void market test

## 15.1. Void trước settlement

| Case ID | Scenario | Expected |
|---|---|---|
| VOID-001 | Market OPEN có pending bets | Refund all stakes, bets VOIDED |
| VOID-002 | Market LOCKED có pending bets | Refund all stakes, bets VOIDED |
| VOID-003 | Market DRAFT không có bet | status VOIDED |
| VOID-004 | Void không reason | reject |
| VOID-005 | User đặt sau void | reject MARKET_NOT_BETTABLE |

## 15.2. Void sau settlement

Khuyến nghị không void trực tiếp sau settlement. Dùng correction.

| Case ID | Scenario | Expected |
|---|---|---|
| VOID-S-001 | Void settled market | reject USE_CORRECTION_FLOW |
| VOID-S-002 | Super admin force void settled | nếu cho phép: tạo correction delta rõ ràng |

---

## 16. Permission test

## 16.1. Role matrix cơ bản

| Action | Super Admin | Operator | Settlement Manager | Auditor | User |
|---|---:|---:|---:|---:|---:|
| View admin panel | Yes | Yes | Yes | Yes | No |
| Create user | Yes | No | No | No | No |
| Grant leaves | Yes | Optional limited | No | No | No |
| Create match | Yes | Yes | No | No | No |
| Create market | Yes | Yes | No | No | No |
| Enter result | Yes | Yes | Yes | No | No |
| Preview settlement | Yes | Yes | Yes | View only | No |
| Execute settlement | Yes | No | Yes | No | No |
| View audit log | Yes | No | No | Yes | No |
| Place bet | No | No | No | No | Yes |

## 16.2. Test cases

| Case ID | Scenario | Expected |
|---|---|---|
| PERM-001 | User truy cập admin panel | 403 hoặc redirect |
| PERM-002 | Operator execute settlement | 403 |
| PERM-003 | Auditor tạo market | 403 |
| PERM-004 | Settlement Manager tạo user | 403 |
| PERM-005 | User gọi endpoint admin bằng URL trực tiếp | 403 |
| PERM-006 | Admin mất role trong session hiện tại | permission updated on next request |

---

## 17. Auth và account test

| Case ID | Scenario | Expected |
|---|---|---|
| AUTH-001 | Login đúng | success |
| AUTH-002 | Login sai password | error |
| AUTH-003 | User inactive login | reject |
| AUTH-004 | Admin bật 2FA | yêu cầu OTP |
| AUTH-005 | Logout | session invalidated |
| AUTH-006 | Session timeout | yêu cầu login lại |
| AUTH-007 | Brute force login | rate limited |
| AUTH-008 | Public registration route | disabled/not found |

---

## 18. Filament admin UI test

## 18.1. User resource

| Case ID | Scenario | Expected |
|---|---|---|
| FIL-USR-001 | Tạo user mới | user created, wallet created |
| FIL-USR-002 | Tạo user trùng email | validation error |
| FIL-USR-003 | Khóa user | inactive, không đặt được |
| FIL-USR-004 | Cấp lá từ user page | ledger ADMIN_GRANT |

## 18.2. Match resource

| Case ID | Scenario | Expected |
|---|---|---|
| FIL-MAT-001 | Tạo trận | match DRAFT |
| FIL-MAT-002 | Publish trận | market có thể mở |
| FIL-MAT-003 | Cancel trận có market pending | require void/refund flow |
| FIL-MAT-004 | Kickoff_at không hợp lệ | validation error |

## 18.3. Market/outcome resource

| Case ID | Scenario | Expected |
|---|---|---|
| FIL-MKT-001 | Tạo handicap market | outcomes valid |
| FIL-MKT-002 | Nhập ăn 0.90 | lưu profit_rate = 0.90 |
| FIL-MKT-003 | Nhập ăn 1.90 ở line thường | warning nếu vượt config |
| FIL-MKT-004 | Nhập line không thuộc step 0.25 | validation error |
| FIL-MKT-005 | Close_at sau kickoff bất thường | warning hoặc reject theo period |

## 18.4. Settlement page

| Case ID | Scenario | Expected |
|---|---|---|
| FIL-SET-001 | Nhập kết quả | preview hiện đúng |
| FIL-SET-002 | Confirm settle | settlement executed |
| FIL-SET-003 | Không đủ quyền | action hidden/403 |
| FIL-SET-004 | Settle market không locked | reject hoặc auto lock theo rule |

---

## 19. User-facing UI test

## 19.1. Dashboard

| Case ID | Scenario | Expected |
|---|---|---|
| UI-DASH-001 | User login | thấy số lá khả dụng |
| UI-DASH-002 | Có vé pending | thấy locked balance |
| UI-DASH-003 | Không có trận mở | empty state rõ ràng |

## 19.2. Match detail và đặt lá

| Case ID | Scenario | Expected |
|---|---|---|
| UI-BET-001 | Xem odds | hiển thị `Brazil -0.5 ăn 0.90` |
| UI-BET-002 | Nhập stake 100 | modal hiển thị payout 190 |
| UI-BET-003 | Confirm đặt | tạo vé thành công |
| UI-BET-004 | Đặt sát giờ đóng | nếu server time quá close_at thì reject |
| UI-BET-005 | Odds đổi sau khi mở modal | cảnh báo reload hoặc snapshot server-side |
| UI-BET-006 | User không đủ lá | thông báo rõ |

## 19.3. My bets

| Case ID | Scenario | Expected |
|---|---|---|
| UI-MYB-001 | Vé pending | status pending |
| UI-MYB-002 | Vé won | hiện payout và net profit |
| UI-MYB-003 | Vé lost | hiện mất stake |
| UI-MYB-004 | Vé half won | hiện nửa thắng |
| UI-MYB-005 | Vé voided | hiện hoàn lá |

## 19.4. Wallet history

| Case ID | Scenario | Expected |
|---|---|---|
| UI-WAL-001 | Xem lịch sử lá | thấy grant/place/settle |
| UI-WAL-002 | Ledger nhiều dòng | pagination/filter hoạt động |
| UI-WAL-003 | Correction | hiện reason |

---

## 20. Leaderboard test

## 20.1. Metrics

| Metric | Formula |
|---|---|
| current_balance | available + locked |
| net_profit | current_balance - total_admin_granted + total_admin_deducted |
| total_staked | tổng stake đã đặt |
| win_rate | won_count / settled_bet_count |
| ROI | net_profit_from_bets / total_staked |
| exact_score_wins | số vé exact score WON |

## 20.2. Test cases

| Case ID | Scenario | Expected |
|---|---|---|
| LB-001 | User A lãi nhiều hơn User B | A rank cao hơn ở net_profit |
| LB-002 | User ít vé nhưng ROI cao | không vào bảng ROI nếu chưa đủ min bets |
| LB-003 | Pending bet | không tính win_rate |
| LB-004 | Voided bet | không tính vào total_staked hoặc tùy rule, phải cố định |
| LB-005 | Correction | leaderboard cập nhật delta |
| LB-006 | Tiebreak | theo rule: net_profit, ROI, total_staked, created_at |

---

## 21. Import/export test

## 21.1. Users import

| Case ID | Scenario | Expected |
|---|---|---|
| IMP-USR-001 | CSV hợp lệ | users created |
| IMP-USR-002 | Email trùng | row error |
| IMP-USR-003 | Role không tồn tại | row error |
| IMP-USR-004 | Thiếu email | row error |
| IMP-USR-005 | Unicode tên Việt Nam | import đúng UTF-8 |

## 21.2. Fixtures import

| Case ID | Scenario | Expected |
|---|---|---|
| IMP-FIX-001 | Lịch hợp lệ | matches created |
| IMP-FIX-002 | match_code trùng | update hoặc reject theo rule |
| IMP-FIX-003 | kickoff_at sai timezone | validation error |
| IMP-FIX-004 | team TBD | allowed nếu WC2026 chưa xác định |

## 21.3. Odds import

| Case ID | Scenario | Expected |
|---|---|---|
| IMP-ODD-001 | handicap `-0.5`, ăn `0.90` | outcome created |
| IMP-ODD-002 | ăn nhập `1.90` | warning/reject theo max config |
| IMP-ODD-003 | line `-0.33` | reject |
| IMP-ODD-004 | market không tồn tại | row error |
| IMP-ODD-005 | duplicate outcome | update/create version theo rule |

## 21.4. Export

| Case ID | Scenario | Expected |
|---|---|---|
| EXP-001 | Export bets | đầy đủ snapshot odds |
| EXP-002 | Export wallet ledger | balance after mỗi dòng |
| EXP-003 | Export settlement report | có payout và status |
| EXP-004 | Export UTF-8 | mở Excel không lỗi tiếng Việt |

---

## 22. Notification test

| Case ID | Scenario | Expected |
|---|---|---|
| NOTI-001 | Admin cấp lá | user nhận notification |
| NOTI-002 | Vé settled | user nhận kết quả |
| NOTI-003 | Market voided | user nhận thông báo hoàn lá |
| NOTI-004 | Notification read | marked read |
| NOTI-005 | Queue notification fail | retry an toàn |

---

## 23. Audit log test

| Case ID | Action | Audit required fields |
|---|---|---|
| AUD-001 | Admin grant leaves | actor, user, amount, reason, before/after |
| AUD-002 | Admin change odds | old value, new value, reason |
| AUD-003 | Admin execute settlement | market, result, total payout |
| AUD-004 | Admin void market | reason, affected bets |
| AUD-005 | Admin correction | old result, new result, delta |
| AUD-006 | Permission denied action | optional security log |

Audit log không được chứa:

```txt
password
remember token
2FA secret
raw session id
sensitive env value
```

---

## 24. Concurrency và race condition test

## 24.1. Hai request đặt cùng lúc

Scenario:

```txt
User có 100 lá.
Gửi đồng thời 2 request đặt 100 lá.
```

Expected:

```txt
Chỉ 1 request thành công.
1 request fail INSUFFICIENT_BALANCE.
available_balance không âm.
locked_balance = 100.
chỉ có 1 bet PENDING.
```

## 24.2. Đặt sát giờ đóng

Scenario:

```txt
Market close_at = 20:00:00.
Request gửi lúc client 19:59:59 nhưng server nhận 20:00:01.
```

Expected:

```txt
Reject MARKET_CLOSED theo server time.
```

## 24.3. Settlement chạy đồng thời

Scenario:

```txt
Hai admin/queue cùng execute settlement một market.
```

Expected:

```txt
Chỉ một settlement được execute.
Không nhân đôi payout.
Có lock hoặc unique constraint chống duplicate.
```

## 24.4. Void và bet placement đồng thời

Scenario:

```txt
Admin void market cùng lúc user đặt vé.
```

Expected:

```txt
Dùng DB transaction/lock.
Kết quả chỉ có một trạng thái hợp lệ:
- Bet không được tạo; hoặc
- Bet được tạo rồi ngay lập tức void/refund theo transaction order.
Không mất lá.
```

---

## 25. Security test

## 25.1. Input security

| Case ID | Scenario | Expected |
|---|---|---|
| SEC-IN-001 | Team name chứa `<script>` | escape trên UI |
| SEC-IN-002 | Reason chứa HTML | escape/sanitize |
| SEC-IN-003 | CSV chứa formula `=cmd()` | export/import sanitize nếu mở Excel |
| SEC-IN-004 | SQL-like input | không ảnh hưởng query |

## 25.2. Auth/security

| Case ID | Scenario | Expected |
|---|---|---|
| SEC-AUTH-001 | CSRF missing | reject |
| SEC-AUTH-002 | User đổi user_id trong request | reject/ignore, dùng auth user |
| SEC-AUTH-003 | User gọi settle endpoint | 403 |
| SEC-AUTH-004 | Admin panel không auth | redirect login |
| SEC-AUTH-005 | Debug mode production | false |

## 25.3. Business abuse

| Case ID | Scenario | Expected |
|---|---|---|
| SEC-BIZ-001 | User cố đặt stake âm | reject |
| SEC-BIZ-002 | User cố đặt stake 0 | reject nếu min stake > 0 |
| SEC-BIZ-003 | User cố sửa bet pending | không cho nếu rule không hỗ trợ |
| SEC-BIZ-004 | User cố hủy bet sau đặt | không cho nếu rule không hỗ trợ |
| SEC-BIZ-005 | User cố chuyển lá | không có feature/endpoint |

---

## 26. Performance test

## 26.1. Mục tiêu hiệu năng MVP

| Scenario | Target |
|---|---:|
| Load match list | < 500ms với 104 trận |
| Load market detail | < 700ms với 100 outcomes |
| Place bet | < 500ms p95 |
| Preview settlement 1,000 bets | < 5s |
| Execute settlement 1,000 bets | < 15s hoặc chạy queue |
| Leaderboard 1,000 users | < 1s nếu dùng snapshot/cache |

## 26.2. Test cases

| Case ID | Scenario | Expected |
|---|---|---|
| PERF-001 | 100 user đặt cùng lúc | không âm ví, response ổn định |
| PERF-002 | 1,000 bets trong một market | settlement đúng |
| PERF-003 | 10,000 wallet ledger rows | wallet history paginated nhanh |
| PERF-004 | Leaderboard nhiều user | dùng index/cache/snapshot |

---

## 27. Deployment và migration test

| Case ID | Scenario | Expected |
|---|---|---|
| DEP-001 | Fresh install | migrate/seed success |
| DEP-002 | Rollback migration dev | rollback success |
| DEP-003 | Deploy production với maintenance mode | không nhận bet trong lúc deploy |
| DEP-004 | Queue worker restart | không mất job |
| DEP-005 | Scheduler chạy market lock | market quá giờ bị lock |
| DEP-006 | Backup trước settlement | backup tạo thành công |
| DEP-007 | Restore backup staging | dữ liệu khôi phục được |

---

## 28. Manual UAT checklist

## 28.1. UAT cho User

Checklist:

```txt
[ ] Login được.
[ ] Thấy số lá khả dụng.
[ ] Thấy trận đang mở.
[ ] Thấy kèo kiểu Việt Nam: Brazil -0.5 ăn 0.90.
[ ] Đặt 100 lá thành công.
[ ] Modal xác nhận hiển thị nếu thắng nhận 190 lá.
[ ] Sau khi đặt, available giảm, locked tăng.
[ ] Market đóng thì không đặt được.
[ ] Sau settlement, thấy vé thắng/thua/hoàn chính xác.
[ ] Leaderboard cập nhật đúng.
[ ] Lịch sử lá có đủ dòng.
```

## 28.2. UAT cho Admin vận hành

Checklist:

```txt
[ ] Tạo user.
[ ] Cấp lá cho user.
[ ] Tạo season.
[ ] Import fixture.
[ ] Tạo match.
[ ] Tạo market full-time.
[ ] Nhập kèo handicap.
[ ] Nhập tài/xỉu.
[ ] Nhập tỉ số chính xác.
[ ] Publish market.
[ ] Lock market.
[ ] Nhập kết quả.
[ ] Preview settlement.
[ ] Execute settlement.
[ ] Xem settlement report.
[ ] Xem audit log.
[ ] Void market test.
[ ] Correction test.
[ ] Export report.
```

## 28.3. UAT cho Auditor

Checklist:

```txt
[ ] Auditor login admin panel được.
[ ] Auditor không tạo/sửa dữ liệu được.
[ ] Auditor xem audit log được.
[ ] Auditor xem wallet ledger được.
[ ] Auditor xem settlement report được.
[ ] Auditor không execute settlement được.
```

---

## 29. Regression test suite bắt buộc trước release

Trước mỗi release, phải chạy tối thiểu:

```txt
[ ] Auth tests
[ ] Permission tests
[ ] WalletService tests
[ ] BetPlacementService tests
[ ] ExactScoreSettlement tests
[ ] AsianHandicapSettlement tests
[ ] OverUnderSettlement tests
[ ] MarketLock tests
[ ] SettlementPreview tests
[ ] SettlementExecute tests
[ ] Void tests
[ ] Correction tests
[ ] Leaderboard tests
[ ] Import tests
[ ] Export tests
[ ] Audit log tests
[ ] Concurrency tests
```

Lệnh:

```bash
php artisan test
./vendor/bin/pint --test
./vendor/bin/phpstan analyse
npm run build
```

---

## 30. Test case naming convention

Format:

```txt
<AREA>-<SUBAREA>-<NUMBER>
```

Ví dụ:

```txt
AH-HM075-002
OU-O225-001
WAL-LOCK-001
BET-SNAP-003
SET-EXE-002
```

Tên test code:

```php
it('settles home minus zero point seventy five as half win when home wins by one goal', function () {
    // ...
});
```

---

## 31. Factory và seeder phục vụ test

Nên có factory:

```txt
UserFactory
WalletFactory
SeasonFactory
MatchFactory
MarketFactory
MarketOutcomeFactory
BetFactory
SettlementFactory
```

Nên có test helper:

```php
createUserWithLeaves(int $amount): User
createOpenMarket(string $type, string $period): Market
createAsianHandicapOutcome(Market $market, string $side, float $line, float $profitRate): MarketOutcome
placeBet(User $user, MarketOutcome $outcome, int $stake): Bet
settleMarket(Market $market, array $result): Settlement
```

---

## 32. Những lỗi nghiêm trọng phải chặn release

Release bị chặn nếu có một trong các lỗi:

```txt
- Balance âm không kiểm soát.
- Settlement nhân đôi payout.
- Bet dùng odds mới thay vì odds snapshot.
- User đặt được sau close_at.
- User đặt vượt available_balance.
- Admin thường execute settlement dù không có quyền.
- Wallet thay đổi mà không có ledger.
- Admin action nhạy cảm không có audit log.
- Correction sửa/xóa settlement cũ thay vì tạo adjustment.
- Hiển thị ăn 0.90 nhưng tính như 0.90 gross thay vì profit_rate.
- Import odds 1.90 bị hiểu nhầm mà không warning.
```

---

## 33. Prompt mẫu cho AI agent viết test

## 33.1. WalletService tests

```txt
Read AGENTS.md and docs/technical/17_DOMAIN_SERVICE_DESIGN.md first.
Implement tests for WalletService only.
Do not implement UI.
Do not modify settlement logic.
Required cases:
- grant leaves
- deduct leaves
- lock stake
- settle win payout
- settle loss payout
- settle push payout
- reject insufficient balance
- ensure wallet_ledger is created for every balance change
Use database transactions and assert final balances.
```

## 33.2. Asian handicap tests

```txt
Read docs/technical/05_SETTLEMENT_ENGINE_SPECIFICATION.md first.
Implement unit tests for AsianHandicapSettlementCalculator.
Use Vietnamese odds convention:
- ăn 0.90 means profit_rate = 0.90
- full win payout = stake × 1.90
Required lines:
0, -0.25, +0.25, -0.5, +0.5, -0.75, +0.75, -1, +1, -1.25, +1.25.
Do not change production code unless tests reveal a mismatch.
```

## 33.3. Over/Under tests

```txt
Read docs/technical/05_SETTLEMENT_ENGINE_SPECIFICATION.md first.
Implement unit tests for OverUnderSettlementCalculator.
Required lines:
2.0, 2.25, 2.5, 2.75, 3.0.
Cover Over and Under.
Cover win, lose, push, half win, half loss.
```

## 33.4. Concurrency tests

```txt
Implement integration tests for concurrent bet placement.
Scenario:
- user has 100 leaves
- send two simultaneous requests placing 100 leaves each
Expected:
- exactly one bet succeeds
- wallet available is 0
- wallet locked is 100
- no negative balance
- one failed request returns INSUFFICIENT_BALANCE
Use lockForUpdate or equivalent transaction-safe implementation.
```

---

## 34. Final acceptance checklist

Dự án đạt chuẩn kiểm thử MVP khi:

```txt
[ ] 100% settlement calculator tests pass.
[ ] WalletService tests pass.
[ ] BetPlacementService tests pass.
[ ] Permission matrix tests pass.
[ ] Market locking tests pass.
[ ] Void/correction tests pass.
[ ] Import/export tests pass.
[ ] Manual UAT user/admin/auditor pass.
[ ] Không có lỗi release blocker.
[ ] Docs và tests thống nhất cách ghi odds Việt Nam.
```

---

## 35. Kết luận

Tài liệu này là chuẩn kiểm thử tổng thể. Trong dự án này, phần cần test sâu nhất không phải UI mà là:

```txt
1. Wallet ledger
2. Bet placement
3. Odds snapshot
4. Asian handicap settlement
5. Over/under settlement
6. Correction/re-settlement
7. Permission
8. Concurrency
```

Nếu các phần này đúng, UI có thể hoàn thiện dần. Nếu các phần này sai, toàn bộ bảng xếp hạng và niềm tin người chơi sẽ sai theo.
