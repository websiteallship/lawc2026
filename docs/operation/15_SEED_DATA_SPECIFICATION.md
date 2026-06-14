---
title: "Seed Data Specification"
project: "Dự Đoán Lá"
version: "1.0"
status: "draft"
last_updated: "2026-06-11"
owner: "Engineering / QA"
---

# Seed Data Specification

## 1. Mục tiêu

Tài liệu này mô tả dữ liệu seed cần có để developer/tester chạy hệ thống nhanh và kiểm thử nghiệp vụ.

Seed data phải phục vụ 3 mục tiêu:

```text
1. Local development chạy được ngay.
2. QA kiểm thử đặt lá/settlement/leaderboard.
3. Staging có dữ liệu gần production nhưng không dùng dữ liệu nhạy cảm.
```

---

## 2. Nguyên tắc seed

```text
- Không seed dữ liệu thật của nhân viên vào repo.
- Không seed password thật.
- Không seed email production nếu không cần.
- Dữ liệu settlement phải có expected payout rõ.
- Seed phải idempotent hoặc dùng migrate:fresh ở local.
- Seed không được phá dữ liệu staging/production nếu chạy nhầm.
```

Production chỉ chạy seed an toàn:

```text
RoleSeeder
PermissionSeeder
SystemSettingSeeder
```

Không chạy demo seed trên production.

---

## 3. Seed roles

## 3.1. Roles

| Role | Mục đích |
|---|---|
| super_admin | Toàn quyền |
| operator | Tạo trận, nhập odds, nhập kết quả |
| settlement_manager | Duyệt/chạy settlement |
| auditor | Chỉ xem log/báo cáo |
| player | Người chơi |

## 3.2. Permissions

Seed toàn bộ permission trong `06_PERMISSION_MATRIX.md`.

Nhóm permission:

```text
users.*
wallets.*
seasons.*
matches.*
markets.*
odds.*
bets.*
settlements.*
leaderboards.*
reports.*
audit_logs.*
settings.*
imports.*
exports.*
```

---

## 4. Seed system settings

| Key | Value demo | Ghi chú |
|---|---:|---|
| default_starting_leaves | 1000 | Lá đầu mùa |
| min_stake | 10 | Tối thiểu mỗi vé |
| max_stake_per_bet | 200 | Tối đa mỗi vé |
| max_stake_per_match | 500 | Tối đa mỗi trận |
| max_stake_per_day | 1000 | Tối đa mỗi ngày |
| allow_negative_balance | false | Không cho âm |
| allow_user_transfer | false | Không cho chuyển lá |
| min_profit_rate | 0.01 | Tỷ lệ ăn tối thiểu |
| max_profit_rate | 20.00 | Tỷ lệ ăn tối đa, tùy loại market có thể thấp hơn |
| suspicious_profit_rate_threshold | 1.50 | Cảnh báo nếu nhập quá cao cho handicap/tài xỉu |

---

## 5. Seed users

## 5.1. Admin demo local

| Name | Email | Role |
|---|---|---|
| Super Admin | admin@example.test | super_admin |
| Operator | operator@example.test | operator |
| Settlement Manager | settlement@example.test | settlement_manager |
| Auditor | auditor@example.test | auditor |

## 5.2. Player demo

| Name | Email | Starting leaves |
|---|---|---:|
| Player One | player1@example.test | 1000 |
| Player Two | player2@example.test | 1000 |
| Player Three | player3@example.test | 1000 |
| Player Low Balance | low@example.test | 20 |

Password demo chỉ dùng local/staging và phải ghi trong README nội bộ nếu cần.

---

## 6. Seed season

```text
season_code: WC2026_DEMO
name: World Cup 2026 Demo
status: ACTIVE
starts_at: 2026-06-11 00:00:00
ends_at: 2026-07-20 23:59:59
```

---

## 7. Seed fixtures demo

## 7.1. Match M001

```text
match_code: M001
home_team: Brazil
away_team: Germany
kickoff_at: 2026-06-12 02:00:00
status: SCHEDULED
```

## 7.2. Match M002

```text
match_code: M002
home_team: Argentina
away_team: France
kickoff_at: 2026-06-13 02:00:00
status: SCHEDULED
```

## 7.3. Match M003 settled demo

```text
match_code: M003
home_team: Spain
away_team: Japan
kickoff_at: 2026-06-10 02:00:00
status: FINISHED
result_full_time: 2-1
result_first_half: 1-0
result_second_half: 1-1
```

---

## 8. Seed markets

Mỗi match demo nên có:

```text
FULL_TIME / ASIAN_HANDICAP
FULL_TIME / OVER_UNDER
FULL_TIME / EXACT_SCORE
FIRST_HALF / ASIAN_HANDICAP
FIRST_HALF / OVER_UNDER
```

Không cần seed hiệp phụ/penalty trong MVP nếu chưa test knockout.

---

## 9. Seed odds kiểu Việt Nam

## 9.1. Asian handicap

```text
Brazil -0.5 ăn 0.90
Germany +0.5 ăn 0.90
Brazil -0.75 ăn 0.90
Germany +0.75 ăn 0.90
Brazil -1 ăn 0.90
Germany +1 ăn 0.90
```

## 9.2. Over/under

```text
Tài 2.5 ăn 0.90
Xỉu 2.5 ăn 0.90
Tài 2.25 ăn 0.90
Xỉu 2.25 ăn 0.90
Tài 2.75 ăn 0.90
Xỉu 2.75 ăn 0.90
```

## 9.3. Exact score

```text
0-0 ăn 6.00
1-0 ăn 5.50
1-1 ăn 5.00
2-1 ăn 7.00
2-0 ăn 7.50
```

---

## 10. Seed bets for settlement test

Dùng match M003 kết quả full-time `Spain 2-1 Japan`.

| User | Market | Selection | Stake | Expected |
|---|---|---|---:|---:|
| player1 | Handicap | Spain -0.5 ăn 0.90 | 100 | 190 |
| player2 | Handicap | Spain -0.75 ăn 0.90 | 100 | 145 |
| player3 | Handicap | Spain -1 ăn 0.90 | 100 | 100 |
| player1 | Over/Under | Tài 2.5 ăn 0.90 | 100 | 190 |
| player2 | Over/Under | Xỉu 2.5 ăn 0.90 | 100 | 0 |
| player3 | Exact Score | 2-1 ăn 7.00 | 100 | 800 |

Lưu ý exact score `ăn 7.00` nghĩa là:

```text
Stake 100, thắng đủ nhận 800 = 100 vốn + 700 lãi.
```

---

## 11. Seeder classes đề xuất

```text
database/seeders/
  RoleSeeder.php
  PermissionSeeder.php
  SystemSettingSeeder.php
  AdminUserSeeder.php
  DemoUserSeeder.php
  SeasonSeeder.php
  FixtureSeeder.php
  MarketSeeder.php
  MarketOutcomeSeeder.php
  WalletSeeder.php
  DemoBetSeeder.php
```

`DatabaseSeeder` local:

```php
public function run(): void
{
    $this->call([
        RoleSeeder::class,
        PermissionSeeder::class,
        SystemSettingSeeder::class,
        AdminUserSeeder::class,
    ]);

    if (! app()->isProduction()) {
        $this->call([
            DemoUserSeeder::class,
            SeasonSeeder::class,
            FixtureSeeder::class,
            MarketSeeder::class,
            MarketOutcomeSeeder::class,
            WalletSeeder::class,
            DemoBetSeeder::class,
        ]);
    }
}
```

---

## 12. Seed verification checklist

Sau seed local:

```text
[ ] Login admin được.
[ ] Login player demo được.
[ ] Player có 1000 lá.
[ ] Có ít nhất 3 trận demo.
[ ] Có đủ market handicap/tài xỉu/tỉ số.
[ ] UI hiển thị “ăn 0.90”, không hiển thị @1.90 là chính.
[ ] Đặt lá được ở market open.
[ ] Market closed không đặt được.
[ ] Preview settlement M003 đúng expected payout.
[ ] Leaderboard rebuild được.
```
