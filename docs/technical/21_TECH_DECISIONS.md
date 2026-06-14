---
title: "Technical Decisions"
project: "Du Doan La"
version: "1.0"
status: "active"
last_updated: "2026-06-12"
owner: "Engineering"
---

# Technical Decisions

## 1. Muc tieu

Tai lieu nay khoa cac lua chon ky thuat mac dinh truoc khi bootstrap code. Neu can doi, tao ADR moi trong `docs/technical/22_ARCHITECTURE_DECISION_RECORDS.md` va cap nhat task/test lien quan.

## 2. Stack chinh thuc cho MVP

| Thanh phan | Quyet dinh | Ly do |
|---|---|---|
| Backend | Laravel 12 | On dinh, ho tro bao mat den 2027, ecosystem tot hon Laravel 13 moi ra |
| PHP | 8.3 | Du moi, on dinh voi Laravel 12 va de cai tren VPS |
| Admin UI | Filament 4 | Du on dinh cho panel noi bo, tuong thich Laravel 12 |
| Frontend | Livewire + Blade + Alpine.js + Tailwind | Giam do phuc tap, phu hop app noi bo |
| Database | PostgreSQL 16+ | Free/open-source, transaction, row lock, constraint manh cho wallet/settlement |
| Cache/queue/session | Redis 7+ | Queue, session, lock, cache |
| Web server | Nginx + PHP-FPM | Don gian, pho bien tren VPS |
| Queue supervisor | Supervisor | De quan ly queue worker tren VPS |
| Scheduler | Cron goi `php artisan schedule:run` moi phut | Chuan Laravel, de quan sat |
| Test runner | PHPUnit mac dinh Laravel | It phu thuoc, co the them Pest sau neu can |
| Static/format | Laravel Pint, Larastan PHPStan | Bat loi som cho domain logic |
| Package manager JS | npm | Mac dinh Laravel/Vite, it tranh cai |

## 3. Cac package du kien

| Package | Muc dich | Bat buoc MVP |
|---|---|---:|
| `filament/filament` | Admin panel | Co |
| `spatie/laravel-permission` | Role/permission | Co |
| `bezhansalleh/filament-shield` | Permission UI/Filament policy | Co |
| `jeffgreco13/filament-breezy` | Admin 2FA/profile | Nen |
| `spatie/laravel-activitylog` | Audit log | Co |
| `pxlrbt/filament-activity-log` | Xem audit trong Filament | Nen |
| `spatie/laravel-settings` | System settings | Co |
| `filament/spatie-laravel-settings-plugin` | Settings UI | Nen |
| `spatie/laravel-backup` | Backup database | Co truoc staging |
| `leandrocfe/filament-apex-charts` | Dashboard chart | Defer neu chua can |

Khong them package thanh toan, betting, reward redemption, user transfer, public registration.

## 4. Timezone

Quyet dinh cho du an nay:

```text
APP_TIMEZONE=Asia/Ho_Chi_Minh
Database datetime duoc luu theo Asia/Ho_Chi_Minh.
File import dung Asia/Ho_Chi_Minh.
UI hien thi Asia/Ho_Chi_Minh.
```

Ly do: day la game noi bo van hanh o Viet Nam, lich import va thoi diem dong market can doc duoc truc tiep boi admin. Neu sau nay can tich hop nguon quoc te, service import phai parse source timezone roi convert sang Asia/Ho_Chi_Minh truoc khi ghi DB.

## 5. Database naming da chot

| Khai niem | Ten chot |
|---|---|
| Match model | `FootballMatch` trong PHP, table `matches` |
| Period result table | `match_period_results` |
| Odds source field | `profit_rate` |
| Ledger delta | `amount_available`, `amount_locked` |
| Ledger balance snapshot | `balance_available_after`, `balance_locked_after` |
| Bet label snapshot | `label_snapshot` |
| Bet display odds snapshot | `display_odds_snapshot` |
| Bet close time snapshot | `close_at_snapshot` |

## 6. Deployment target

MVP mac dinh chay tren mot VPS noi bo:

```text
Nginx
PHP-FPM
Laravel app
PostgreSQL
Redis
Supervisor queue workers
Cron scheduler
```

Chi tach database/Redis/worker sang server rieng khi co dau hieu tai cao, deadlock, backup cham, hoac nhieu user dat sat gio.

## 7. Gioi han giai doan MVP

- Lich WC2026 nhap tu file CSV/XLSX, khong scrape live.
- Odds nhap tu file hoac admin UI, khong lay tu nha cai.
- Khong co mobile app native.
- Khong co public API cho nguoi ngoai.
- Khong co thanh toan, nap/rut, doi thuong, chuyen la.

## 8. Dieu kien doi quyet dinh

Moi thay doi trong cac muc sau phai co ADR va test:

- Settlement formula.
- Vietnamese odds convention.
- Wallet ledger schema.
- Timezone storage.
- Correction policy.
- Deployment topology.
- Public access model.
