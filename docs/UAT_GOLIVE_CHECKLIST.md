# UAT Test Cases & Go-live Checklist — v1.0.0

## Staging UAT — 10–30 người dùng, 5 trận giả

### Setup Staging

```bash
# 1. Seed dữ liệu staging
php artisan db:seed --class=StagingSeeder   # hoặc dùng import:wc2026 với file CSV test

# 2. Tạo 5 trận giả (qua Filament admin)
# 3. Tạo markets cho mỗi trận: 1 EXACT_SCORE + 1 ASIAN_HANDICAP + 1 OVER_UNDER
# 4. Publish markets → OPEN
# 5. Phân quyền 10-30 users: role = player
# 6. Cấp mỗi user 1000 lá
```

---

## UAT Test Cases

### TC-1: Luồng cơ bản (Happy Path)

| # | Action | Expected |
|---|--------|----------|
| 1 | User đặt lá vào kèo đang OPEN | Bet PENDING, wallet locked tăng |
| 2 | Chờ close_at hết hạn | Market tự chuyển LOCKED (scheduler) |
| 3 | Admin nhập kết quả | MatchPeriodResult tạo được |
| 4 | Admin preview settlement | Hiển thị WON/LOST/PUSH đúng |
| 5 | Admin confirm settle | Bets cập nhật, wallet cộng payout |
| 6 | Leaderboard cập nhật | Thứ hạng thay đổi đúng |

### TC-2: Void Market

| # | Action | Expected |
|---|--------|----------|
| 1 | Admin void market OPEN | Market VOIDED, bets trả về PENDING (chờ void) |
| 2 | Admin void bets PENDING | Stake hoàn về available_balance |
| 3 | Ledger có BET_VOIDED | Không âm balance |

### TC-3: Đồng thời sát giờ đóng

| # | Action | Expected |
|---|--------|----------|
| 1 | 5 users đặt cùng lúc 1 phút trước close_at | Tất cả BET thành công |
| 2 | close_at = now() | Scheduler lock market |
| 3 | User thử đặt sau close_at | Lỗi "Market đã đóng" |

### TC-4: Stake limit

| # | Action | Expected |
|---|--------|----------|
| 1 | Đặt < 10 lá | Lỗi min_stake |
| 2 | Đặt > 200 lá | Lỗi max_stake_per_bet |
| 3 | Tổng 1 trận > 500 lá | Lỗi max_stake_per_match |
| 4 | Số dư không đủ | Lỗi insufficient_balance |

### TC-5: Settlement Correctness (Staging)

| Kèo | Kết quả | Dự đoán | Expected |
|-----|---------|---------|---------|
| EXACT_SCORE 2-1 | 2-1 | 2-1 | WON, payout = stake × 8.00 |
| EXACT_SCORE 2-1 | 1-0 | 2-1 | LOST, payout = 0 |
| AH Home -0.5 | 1-0 | Home -0.5 | WON, payout = stake × 1.90 |
| AH Home -0.5 | 1-1 | Home -0.5 | LOST, payout = 0 |
| AH Home -0.25 | 1-1 | Home -0.25 | HALF_LOST, payout = stake/2 |
| O/U Tài 2.25 | 2 goals | OVER | HALF_LOST, payout = stake/2 |
| O/U Xỉu 2.25 | 2 goals | UNDER | HALF_WON, payout = stake/2×1.90 + stake/2 |

### TC-6: Correction (Sau settle sai)

> Áp dụng khi có lỗi nhập kết quả.

| # | Action | Expected |
|---|--------|----------|
| 1 | Settle với kết quả sai | Bets SETTLED, ledger ghi |
| 2 | Phát hiện sai → Correction | Ledger SETTLEMENT_CORRECTION tạo, balance điều chỉnh |
| 3 | Ledger cũ vẫn còn | Không xóa settled data |

---

## Go-live Checklist

### Infrastructure

- [ ] Server PostgreSQL 16 đang chạy, DB tạo sẵn
- [ ] Redis đang chạy
- [ ] PHP 8.3+, PHP extensions: pgsql, zip, gd, redis
- [ ] Queue worker: `supervisord` hoặc systemd service
- [ ] Scheduler: `crontab -e` → `* * * * * cd /path && php artisan schedule:run >> /dev/null 2>&1`
- [ ] HTTPS certificate (Let's Encrypt hoặc wildcard)
- [ ] Firewall: chỉ mở port 80/443 và SSH

### Application Deploy

```bash
# Production deploy sequence
git pull origin main
composer install --optimize-autoloader --no-dev
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan migrate --force
php artisan db:seed --class=ProductionSeeder   # chỉ chạy 1 lần
php artisan queue:restart
```

### Pre-launch Verify

- [ ] `php artisan test` → **60/60 PASS**
- [ ] `php artisan backup:run --only-db` → backup file tạo được
- [ ] Đăng nhập admin Filament → hoạt động
- [ ] Tạo 1 trận test + market + outcome → publish → đặt 1 bet → settle → ledger đúng
- [ ] Leaderboard hiển thị đúng thứ hạng
- [ ] Scheduler `markets:lock-expired` chạy được (kiểm tra log)

### Communication

- [ ] Gửi email hướng dẫn user: URL nội bộ, tài khoản, số lá
- [ ] Kèm link trang `/rules` với disclaimer
- [ ] Đặt lịch: WC2026 kick-off 11/06/2026 (VN timezone)

---

## Rollback Plan

```bash
# Nếu có lỗi nghiêm trọng sau go-live
php artisan down                          # Maintenance mode
php artisan migrate:rollback --step=1    # Rollback migration cuối (nếu cần)
# Restore từ backup:
php artisan backup:restore               # (cần config)
php artisan up
```

> [!CAUTION]
> KHÔNG rollback migration nếu đã có dữ liệu ledger/bets. Dùng correction entries thay thế.
