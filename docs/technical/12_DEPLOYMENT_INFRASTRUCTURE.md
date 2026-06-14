---
title: "Deployment & Infrastructure"
project: "Dự Đoán Lá"
version: "1.0"
status: "draft"
last_updated: "2026-06-11"
owner: "DevOps / Engineering"
---

# Deployment & Infrastructure

## 1. Mục tiêu

Tài liệu này mô tả cách triển khai hệ thống **Dự Đoán Lá** lên staging/production nội bộ, đảm bảo:

```text
- Không mất dữ liệu ví lá.
- Không double settlement.
- Không cho user đặt khi hệ thống đang deploy nguy hiểm.
- Có backup/restore.
- Có queue/scheduler ổn định.
- Có log/monitoring đủ để truy lỗi.
```

---

## 2. Môi trường triển khai

## 2.1. Các môi trường nên có

| Môi trường | Mục đích | Dữ liệu |
|---|---|---|
| local | Dev cá nhân | Demo/fake |
| staging | Test nghiệp vụ, UAT | Gần production nhưng không dùng dữ liệu thật nếu chưa được phép |
| production | Chạy nội bộ chính thức | Dữ liệu thật |

## 2.2. Nguyên tắc tách môi trường

```text
- Mỗi môi trường có database riêng.
- Redis riêng hoặc prefix riêng.
- Queue riêng.
- APP_KEY riêng.
- Storage riêng.
- Không dùng production database cho local.
```

---

## 3. Kiến trúc production đề xuất

MVP đã chốt triển khai mặc định trên một VPS nội bộ. Xem runbook thao tác cụ thể tại `docs/operation/22_VPS_DEPLOYMENT_RUNBOOK.md`.

## 3.1. Mô hình đơn giản cho nội bộ

```text
Nginx
  -> PHP-FPM / Laravel app
  -> PostgreSQL
  -> Redis
  -> Queue worker
  -> Scheduler
```

Chạy trên 1 VPS/server nội bộ nếu lượng user nhỏ. Nếu nhiều user đặt sát giờ, nên tách:

```text
Web server
Database server
Redis server
Worker server
Backup storage
```

## 3.2. Thành phần bắt buộc

| Thành phần | Bắt buộc | Lý do |
|---|---:|---|
| Nginx/Apache | Có | Reverse proxy/static files |
| PHP-FPM | Có | Chạy Laravel |
| PostgreSQL | Có | Transaction/lock mạnh; phần mềm free/open-source, chi phí là server/managed hosting |
| Redis | Có | Queue/cache/lock/session |
| Supervisor/Systemd | Có | Giữ queue worker chạy |
| Cron | Có | Laravel scheduler |
| SSL | Có | Bảo vệ login/session |
| Backup | Có | Khôi phục ví/ledger |

---

## 4. Cấu hình deploy Laravel

Stack/version mặc định xem `docs/technical/21_TECH_DECISIONS.md`.

## 4.1. Quy trình deploy an toàn

```text
1. Pull code mới.
2. Cài composer dependency.
3. Cài/build asset.
4. Bật maintenance mode nếu migration có rủi ro.
5. Backup database nếu thay đổi schema quan trọng.
6. Chạy migration.
7. Clear/cache config, route, view.
8. Restart PHP-FPM.
9. Restart queue worker.
10. Tắt maintenance mode.
11. Smoke test.
```

Ví dụ command:

```bash
php artisan down --render="errors::503"
git pull origin main
composer install --no-dev --optimize-autoloader
npm ci
npm run build
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
php artisan queue:restart
php artisan up
```

## 4.2. Khi không được deploy

Không deploy khi:

```text
- Đang có market sắp đóng trong vài phút.
- Admin đang preview/execute settlement.
- Queue đang xử lý settlement lớn.
- Chưa backup trước migration có thay đổi bảng wallet/bet/settlement.
```

---

## 5. Nginx config mẫu

```nginx
server {
    listen 80;
    server_name du-doan-la.internal;
    root /var/www/du-doan-la/public;

    index index.php index.html;

    client_max_body_size 20M;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.ht {
        deny all;
    }
}
```

Production nên dùng HTTPS.
Với VPS production, HTTPS là bắt buộc.

---

## 6. Hướng dẫn chạy Queue và Schedule (Auto-run)

Để hệ thống tự động cập nhật Live Score, Kèo RapidAPI và xử lý trả thưởng (Settlement), bắt buộc phải có 2 tiến trình chạy ngầm:
1. **Schedule**: Gửi lệnh định kỳ.
2. **Queue Worker**: Xử lý các lệnh/jobs.

### 6.1. Trên Local (Môi trường phát triển - Windows)

Trên Windows không có service ngầm sẵn như Linux, cách tiện nhất là tạo file `.bat` để khởi động cùng lúc.

**Tạo file `start-jobs.bat` tại thư mục gốc dự án:**
```bat
@echo off
start "Laravel Scheduler" cmd /k "php artisan schedule:work"
start "Laravel Queue Worker" cmd /k "php artisan queue:work"
```
Mỗi khi code hoặc test tự động hóa, chỉ cần **click đúp vào file `start-jobs.bat`** để mở 2 tiến trình chạy ngầm.

### 6.2. Trên Production (Linux / Ubuntu)

#### A. Cấu hình Crontab (Cho Schedule)
Gõ lệnh `crontab -e` và thêm dòng sau:
```cron
* * * * * cd /var/www/du-doan-la && php artisan schedule:run >> /dev/null 2>&1
```

#### B. Cấu hình Supervisor (Cho Queue Worker)
Cài đặt Supervisor (`sudo apt-get install supervisor`).
Tạo file cấu hình `/etc/supervisor/conf.d/du-doan-la-worker.conf`:

```ini
[program:du-doan-la-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/du-doan-la/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/du-doan-la/storage/logs/worker.log
stopwaitsecs=3600
```
Khởi động Worker:
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start du-doan-la-worker:*
```

---

## 7. Các Task Scheduler quan trọng (Tham khảo)

Bên dưới là danh sách các task ngầm quan trọng mà Cronjob trên sẽ kích hoạt:
- Cập nhật tỷ số (`SyncLiveMatchScoresJob`)
- Cập nhật Kèo API (`SyncPreMatchOddsJob`)
- Khóa market hết giờ (`markets:lock-expired`)
- Backup DB (`backup:run`)
- Leaderboard snapshot (`snapshot:leaderboard`)

## 8. Database migration policy

## 8.1. Bảng nhạy cảm

Các bảng sau cần backup trước migration:

```text
wallets
wallet_ledgers
bets
settlements
settlement_items
markets
market_outcomes
```

## 8.2. Nguyên tắc migration

```text
- Không drop column nhạy cảm nếu chưa có migration chuyển dữ liệu.
- Không đổi kiểu dữ liệu stake/payout/profit_rate tùy tiện.
- Không sửa enum mà không có backfill.
- Không update balance hàng loạt nếu không có ledger đối ứng.
- Migration production phải chạy được bằng --force và có log.
```

---

## 9. Backup & restore

## 9.1. Backup tối thiểu

| Loại | Tần suất | Giữ lại |
|---|---:|---:|
| Database full backup | Hàng ngày | 30 ngày |
| Pre-settlement backup | Trước settlement lớn | 7 ngày |
| Pre-migration backup | Trước deploy có migration | 14 ngày |
| Storage backup | Hàng ngày/tuần | 30 ngày |

## 9.2. Command backup

```bash
php artisan backup:run --only-db
```

## 9.3. Restore drill

Mỗi tháng nên test restore trên staging:

```text
1. Tải backup production gần nhất.
2. Restore vào staging.
3. Chạy migration nếu cần.
4. Kiểm tra số dư wallet.
5. Kiểm tra ledger sum.
6. Kiểm tra settlement gần nhất.
7. Kiểm tra leaderboard.
```

---

## 10. Health check

Endpoint đề xuất:

```text
GET /health
```

Response:

```json
{
  "status": "ok",
  "app": true,
  "database": true,
  "redis": true,
  "queue": true,
  "scheduler": true
}
```

Không expose thông tin nhạy cảm trong health check public.

---

## 11. Smoke test sau deploy

Sau mỗi deploy:

```text
1. Login admin.
2. Mở danh sách match.
3. Mở chi tiết 1 market.
4. Login user demo staging.
5. Đặt thử 10 lá ở market demo.
6. Kiểm tra wallet ledger.
7. Preview settlement ở match demo.
8. Không chạy settlement thật trên production nếu không phải quy trình vận hành.
9. Kiểm tra queue failed.
10. Kiểm tra logs lỗi.
```

---

## 12. Rollback deploy

## 12.1. Rollback code

```bash
git checkout <previous-release-tag>
composer install --no-dev --optimize-autoloader
npm ci
npm run build
php artisan config:cache
php artisan route:cache
php artisan queue:restart
```

## 12.2. Rollback database

Không rollback database tùy tiện nếu đã có dữ liệu mới phát sinh. Với ví/settlement:

```text
- Ưu tiên forward-fix.
- Nếu bắt buộc restore, phải khóa hệ thống.
- Phải thông báo mất dữ liệu phát sinh sau backup nếu có.
- Phải đối chiếu wallet ledger sau restore.
```

---

## 13. Production hardening

```text
APP_ENV=production
APP_DEBUG=false
LOG_LEVEL=warning
SESSION_SECURE_COOKIE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax
```

Nếu hệ thống chỉ nội bộ:

```text
- Dùng VPN hoặc IP allowlist.
- Tách admin panel bằng subdomain/path và middleware.
- Bắt 2FA cho admin.
```

---

## 14. Checklist go-live

```text
[ ] APP_DEBUG=false
[ ] SSL hoạt động
[ ] Database backup chạy thử thành công
[ ] Restore drill thành công trên staging
[ ] Queue worker chạy
[ ] Scheduler chạy
[ ] markets:lock-expired hoạt động
[ ] Admin 2FA bật
[ ] Permission đúng
[ ] Không có user demo trên production nếu không cần
[ ] Legal disclaimer hiển thị
[ ] Terms/thể lệ hiển thị
[ ] Test đặt lá production với tài khoản nội bộ kiểm thử
[ ] Test void/correction trên staging
```
