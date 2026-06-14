---
title: "Architecture Decision Records"
project: "Du Doan La"
version: "1.0"
status: "active"
last_updated: "2026-06-12"
owner: "Engineering"
---

# Architecture Decision Records

## ADR-001: Dung `profit_rate` lam source of truth cho ti le an

Status: Accepted

Decision:

```text
Luu `profit_rate` la he so lai theo cach Viet Nam.
Khong luu decimal odds lam source of truth.
Neu can decimal odds, derive bang `1 + profit_rate`.
```

Consequences:

- UI/admin nhap `an 0.90` thi DB luu `0.90`.
- Full win payout = `stake * (1 + profit_rate)`.
- Test settlement phai cover truong hop exact score `an 7.00` thanh payout `800` voi stake `100`.

## ADR-002: Wallet dung available/locked balance va ledger bat buoc

Status: Accepted

Decision:

```text
wallets co available_balance va locked_balance.
Moi bien dong la phai tao wallet_ledgers trong cung transaction.
```

Consequences:

- Dat du doan: available giam, locked tang.
- Settlement/void: locked giam, available tang theo gross payout.
- Khong co update balance truc tiep trong Filament, controller, command import.

## ADR-003: Settlement chi nam trong calculator va SettlementEngine

Status: Accepted

Decision:

```text
Calculator tinh ket qua, khong mutate DB.
SettlementEngine orchestrate transaction, wallet ledger, settlement_items.
```

Consequences:

- Filament action chi goi service.
- Unit test calculator khong can database.
- Integration test SettlementEngine phai cover idempotency va rollback.

## ADR-004: Correction khong sua/xoa settlement cu

Status: Accepted

Decision:

```text
Correction tao ban ghi correction rieng, tinh delta, tao ledger SETTLEMENT_CORRECTION.
Khong sua ledger/settlement cu.
```

Consequences:

- Audit duoc lich su sai/dung.
- Neu delta am lon hon available balance, dua vao manual review.
- Leaderboard phai rebuild sau correction.

## ADR-005: Lich WC2026 nhap tu file

Status: Accepted

Decision:

```text
Lich WC2026 duoc import tu CSV/XLSX do admin/operation upload.
Khong scrape live hoac goi external API trong MVP.
```

Consequences:

- Can preview/import log/file loi.
- `kickoff_at`, `open_at`, `close_at` trong file la Asia/Ho_Chi_Minh.
- Khong overwrite tran da co bet neu khong qua workflow correction/admin override.

## ADR-006: Timezone luu Asia/Ho_Chi_Minh

Status: Accepted

Decision:

```text
Database datetime cho business time luu theo Asia/Ho_Chi_Minh.
APP_TIMEZONE=Asia/Ho_Chi_Minh.
Import file cung dung Asia/Ho_Chi_Minh.
```

Consequences:

- Test market locking phai set timezone co dinh.
- Source quoc te phai convert sang Asia/Ho_Chi_Minh truoc khi ghi DB.
- Admin doc DB/export se thay gio Viet Nam truc tiep.

## ADR-007: Deploy MVP tren mot VPS

Status: Accepted

Decision:

```text
MVP chay single VPS voi Nginx, PHP-FPM, PostgreSQL, Redis, Supervisor, Cron.
```

Consequences:

- Don gian van hanh va backup.
- Phai co backup daily va pre-migration.
- Khi tai tang moi tach DB/Redis/worker.

## ADR-008: Khong co tinh nang real-money hoac doi thuong

Status: Accepted

Decision:

```text
Khong nap tien, rut tien, chuyen la, doi thuong, public registration, payment integration.
```

Consequences:

- Settings khong duoc co flag bat cac tinh nang cam.
- UI copy tranh "ca cuoc", "nap", "rut", "doi thuong".
- Neu user yeu cau tinh nang nay, agent/refinement phai tu choi implementation.
