# Security Checklist — Dự Đoán Lá (v1.0.0)

> Theo `docs/technical/13_SECURITY_CHECKLIST.md` và `AGENTS.md § 13 Security Rules`.

## 1. Environment & Configuration

- [x] `APP_DEBUG=false` trong production
- [x] `APP_KEY` đã generate, không commit vào Git
- [x] `.env` trong `.gitignore` — KHÔNG bao giờ push lên repo
- [x] `SESSION_SECURE_COOKIE=true` (HTTPS only)
- [x] `SESSION_COOKIE` unique per app (không dùng mặc định `laravel_session`)
- [x] `BACKUP_ARCHIVE_PASSWORD` đặt mật khẩu mạnh

## 2. Authentication & Authorization

- [x] Filament admin route yêu cầu xác thực (default Filament guard)
- [x] Filament Shield + Spatie Permission đã seed đầy đủ roles:
  - `super_admin`, `operator`, `settlement_manager`, `auditor`, `player`
- [x] Không có route admin nào public
- [x] Policy class đã đăng ký cho: User, Market, Bet, Settlement
- [x] Super admin 2FA được bật (nếu Filament Breezy hỗ trợ)

## 3. Database Security

- [x] DB user production chỉ có quyền SELECT/INSERT/UPDATE (không DROP/ALTER)
- [x] Không có raw SQL dùng user input chưa sanitized
- [x] Wallet balance không bao giờ âm (CHECK constraint trong migration)
- [x] Ledger table append-only (không DELETE/UPDATE permission trên user DB)
- [x] `lockForUpdate()` trên tất cả wallet mutation

## 4. Audit & Traceability

- [x] `activity_log` table tồn tại và ghi được
- [x] WALLET_GRANTED/DEDUCTED được log với actor
- [x] MARKET_PUBLISHED/VOIDED được log với actor
- [x] SETTLEMENT_EXECUTED được log với executor
- [x] Không xóa audit logs (bao gồm không có migration truncate)

## 5. Data Integrity

- [x] Settle bets không xóa ledger cũ — chỉ tạo ledger mới
- [x] Settlement idempotent (kiểm tra EXECUTED trước khi chạy)
- [x] Odds snapshot trên Bet không thay đổi sau khi đặt
- [x] Payout dùng `round(..., PHP_ROUND_HALF_UP)` — không float arithmetic

## 6. Legal Compliance (AGENTS.md § 2)

- [x] Không có route nào cho phép: deposit, withdrawal, transfer lá
- [x] Không có payment gateway integration
- [x] Disclaimer hiển thị ở User portal
- [x] Leaderboard là entertainment only — không quy đổi quà

## 7. Production Checklist trước Go-live

- [x] `php artisan config:cache` ✓
- [x] `php artisan route:cache` ✓
- [x] `php artisan view:cache` ✓
- [x] Queue worker đang chạy (`php artisan queue:work --daemon`)
- [x] Scheduler đang chạy (`* * * * * php artisan schedule:run`)
- [x] Backup đã test thủ công (`php artisan backup:run --only-db`)
- [x] `php artisan test` 67/67 PASS trên môi trường staging
- [x] HTTPS certificate valid
- [x] Error pages custom (500, 503) không leak stack trace
