---
title: "VPS Deployment Runbook"
project: "Du Doan La"
version: "1.0"
status: "active"
last_updated: "2026-06-12"
owner: "DevOps / Engineering"
---

# VPS Deployment Runbook

## 1. Scope

Runbook nay la chuan deploy MVP len mot VPS Linux. Khong chay deploy production neu chua co:

- VPS host/IP.
- SSH user va access method.
- Domain/subdomain noi bo.
- Database password.
- Backup target.
- Xac nhan staging hay production.

## 2. Topology mac dinh

```text
VPS
  Nginx
  PHP 8.3 FPM
  Laravel 12 app
  PostgreSQL 16+
  Redis 7+
  Supervisor queue workers
  Cron scheduler
```

## 3. Server packages

Ubuntu LTS recommended:

```bash
sudo apt update
sudo apt install nginx postgresql redis-server supervisor unzip git curl
sudo apt install php8.3-fpm php8.3-cli php8.3-pgsql php8.3-redis php8.3-mbstring php8.3-xml php8.3-curl php8.3-zip php8.3-bcmath php8.3-intl
```

Install Composer and Node.js theo policy infra cua cong ty.

## 4. Directory layout

```text
/var/www/du-doan-la/current
/var/www/du-doan-la/shared/.env
/var/www/du-doan-la/shared/storage
/var/backups/du-doan-la
```

MVP co the dung `current` truc tiep. Neu can zero-downtime hon, them release folders sau.

## 5. Environment

`.env` production/staging can co:

```dotenv
APP_NAME="Du Doan La"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://du-doan-la.internal
APP_TIMEZONE=Asia/Ho_Chi_Minh

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=du_doan_la
DB_USERNAME=du_doan_la
DB_PASSWORD=<secret>

CACHE_STORE=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

SESSION_SECURE_COOKIE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax
```

Khong commit `.env`.

## 6. First deploy

```bash
cd /var/www/du-doan-la/current
composer install --no-dev --optimize-autoloader
npm ci
npm run build
php artisan key:generate --force
php artisan storage:link
php artisan migrate --force
php artisan db:seed --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

Neu production, seed demo user chi duoc chay neu duoc chap thuan.

## 7. Nginx site

```nginx
server {
    listen 80;
    server_name du-doan-la.internal;
    root /var/www/du-doan-la/current/public;

    index index.php;
    charset utf-8;
    client_max_body_size 20M;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ ^/index\.php(/|$) {
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

Production phai dung HTTPS.

## 8. Supervisor worker

```ini
[program:du-doan-la-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/du-doan-la/current/artisan queue:work redis --queue=settlement,imports,exports,notifications,default --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/du-doan-la/current/storage/logs/worker.log
stopwaitsecs=3600
```

Kich hoat Supervisor:
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start du-doan-la-worker:*
```

```cron
* * * * * cd /var/www/du-doan-la/current && php artisan schedule:run >> /dev/null 2>&1
```

Them vao Crontab:
```bash
# Mo giao dien sua crontab
crontab -e

# Dan dong lenh tren vao cuoi file va luu lai
```

Scheduler phai chay:

- `markets:lock-expired`.
- `SyncLiveMatchScoresJob` (dua vao interval setting).
- `SyncPreMatchOddsJob` (dua vao interval setting).
- Backup theo lich.
- Queue pruning neu co.
- Leaderboard snapshot neu cau hinh.

## 10. Safe deploy checklist

Truoc deploy:

```text
[ ] Khong co market sap dong trong 10 phut.
[ ] Khong co settlement dang chay.
[ ] Backup DB neu migration cham vao wallet/bet/settlement.
[ ] `php artisan test` pass o CI/local.
[ ] `.env` production khong bat debug.
```

Deploy:

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
sudo systemctl reload php8.3-fpm
sudo systemctl reload nginx
php artisan up
```

Sau deploy:

```text
[ ] Login admin.
[ ] Login player staging/test.
[ ] Xem danh sach match.
[ ] Dat thu phieu tren staging.
[ ] Kiem tra wallet ledger.
[ ] Kiem tra queue failed.
[ ] Kiem tra scheduler heartbeat.
[ ] Kiem tra log error.
```

## 11. Backup

Daily DB backup:

```bash
pg_dump -Fc du_doan_la > /var/backups/du-doan-la/du_doan_la_$(date +%Y%m%d_%H%M%S).dump
```

Restore drill hang thang tren staging:

```text
Restore DB -> run integrity checks -> compare wallet ledger -> smoke test.
```

## 12. Khong duoc deploy khi

- Chua co backup.
- Chua ro staging hay production.
- Chua co domain/SSL.
- Dang settlement.
- Dang import file lon.
- Dang co incident wallet/ledger.
