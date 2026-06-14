# Hướng dẫn Deploy lên Vultr VPS

## 1. Đăng nhập và Chuẩn bị Server
```bash
# Đăng nhập vào VPS qua SSH
ssh root@<vps_ip>

# Cập nhật hệ thống
apt update && apt upgrade -y

# Cài đặt các gói cơ bản
apt install -y nginx postgresql redis-server supervisor unzip git curl
```

## 2. Cài đặt PHP & Node.js (Ubuntu 26.04 LTS đã có sẵn PHP 8.5)
```bash
# Cập nhật và cài đặt PHP 8.5 cùng các extension
apt update
apt install -y php8.5-fpm php8.5-cli php8.5-pgsql php-redis php8.5-mbstring php8.5-xml php8.5-curl php8.5-zip php8.5-bcmath php8.5-intl

# Cài đặt Node.js 20
curl -fsSL https://deb.nodesource.com/setup_20.x | bash -
apt install -y nodejs
```

## 3. Cài đặt Composer
```bash
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php composer-setup.php
php -r "unlink('composer-setup.php');"
mv composer.phar /usr/local/bin/composer
```

## 4. Setup Database
```bash
# Chuyển sang user postgres và tạo database, user
sudo -u postgres psql -c "CREATE DATABASE du_doan_la;"
sudo -u postgres psql -c "CREATE USER du_doan_la WITH PASSWORD 'mat_khau_database';"
sudo -u postgres psql -c "GRANT ALL PRIVILEGES ON DATABASE du_doan_la TO du_doan_la;"
```

## 5. Phân quyền và Thư mục chứa Source Code
```bash
# Tạo thư mục
mkdir -p /var/www/du-doan-la/current

# Phân quyền cho www-data
chown -R www-data:www-data /var/www/du-doan-la
usermod -aG www-data root
```

## 6. Deploy Code
```bash
cd /var/www/du-doan-la/current

# Clone code (thay <repo_url> bằng link git thật)
git clone <repo_url> .

# Setup biến môi trường
cp .env.example .env
nano .env # (Cấu hình DB, REDIS, APP_URL, sửa APP_ENV=production)

# Cài đặt dependencies
composer install --no-dev --optimize-autoloader
npm ci
npm run build

# Khởi tạo Laravel
php artisan key:generate --force
php artisan storage:link
php artisan migrate --force
php artisan db:seed --force # Tuỳ chọn
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# Phân quyền lại thư mục storage & cache
chown -R www-data:www-data storage bootstrap/cache
```

## 7. Cấu hình Nginx
```bash
# Xoá cấu hình mặc định (tuỳ chọn)
rm /etc/nginx/sites-enabled/default

# Tạo file cấu hình Nginx
nano /etc/nginx/sites-available/du-doan-la
```
Nội dung file:
```nginx
server {
    listen 80;
    server_name <vps_ip_hoac_domain>;
    root /var/www/du-doan-la/current/public;
    index index.php;
    
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.5-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```
```bash
# Kích hoạt Nginx site
ln -s /etc/nginx/sites-available/du-doan-la /etc/nginx/sites-enabled/
nginx -t
systemctl reload nginx
```

## 8. Cấu hình Supervisor & Cron
```bash
# Tạo cấu hình Supervisor cho Queue Worker
nano /etc/supervisor/conf.d/du-doan-la.conf
```
Nội dung file:
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
```
```bash
# Khởi động Supervisor
supervisorctl reread
supervisorctl update
supervisorctl start du-doan-la-worker:*

# Cài đặt Cronjob
crontab -e
```
Thêm dòng sau vào cuối:
```cron
* * * * * cd /var/www/du-doan-la/current && php artisan schedule:run >> /dev/null 2>&1
```

## 9. Cập nhật Code (Deploy bản cập nhật)
Có 2 cách để cập nhật code lên server, khuyên dùng Cách 1 cho team nhỏ/sửa nhanh.

### Cách 1: Sửa trực tiếp bằng VS Code Remote - SSH (Khuyên dùng)
Không cần qua Git, sửa file trên máy tính và lưu thẳng vào server:
1. Mở VS Code, cài Extension **Remote - SSH**.
2. Bấm F1 -> `Remote-SSH: Connect to Host...` -> Nhập `root@<vps_ip>`.
3. Trong VS Code, chọn **Open Folder** -> Nhập đường dẫn `/var/www/du-doan-la/current`.
4. Khi có thay đổi, sửa code và bấm `Ctrl + S`, file sẽ được tự động lưu thẳng lên VPS.
5. Mở Terminal trong VS Code (Terminal chạy thẳng trên VPS), chạy các lệnh cần thiết (ví dụ):
```bash
# Nếu sửa database / cache:
php artisan migrate --force
php artisan config:cache

# Nếu cập nhật thư viện PHP/NodeJS:
composer install
npm run build
```

### Cách 2: Dùng Git (Quy trình chuẩn)
```bash
# 1. Tại máy tính Local (Push code)
git add .
git commit -m "Update feature"
git push origin main

# 2. Tại VPS Terminal (Pull code)
cd /var/www/du-doan-la/current
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
systemctl reload php8.5-fpm
php artisan up
```
