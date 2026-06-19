# Dự Đoán Lá WC2026

Internal football prediction game for a company. Users use virtual points called **lá** to predict football results.

## Legal Positioning & Non-Negotiable Rules
This is an internal prediction game using virtual points. **It is NOT a gambling platform, betting business, or a public betting website.**
- No real-money deposit, withdrawal, or conversion of lá to cash or physical rewards.
- No user-to-user transfer of lá. No payment integration.
- No public registration or access outside the company.
- Admin can grant or deduct lá only through auditable ledger entries.

---

## 1. Hướng dẫn Kiểm thử (Testing)

Dự án này bao gồm cả unit tests (cho backend) và browser tests (cho frontend) để đảm bảo chất lượng hệ thống MVP cho quá trình UAT & Go-live.

## 1. Kiểm thử Backend (Core Engine)
Hệ thống đã có sẵn 60 bài test xác minh cho Core Engine (Wallet, Settlement, Bet Placement).
Chạy toàn bộ backend tests:
```bash
php artisan test
```

## 2. Kiểm thử Frontend / End-to-End (Laravel Dusk)
Môi trường testing frontend tự động đã được thiết lập thông qua **Laravel Dusk**. Dusk cho phép mô phỏng người dùng thật trên trình duyệt (đăng nhập, bấm nút, tương tác Modal) để kiểm tra luồng Player UI và Admin Panel.

**Cài đặt môi trường ban đầu:**
*(Các package đã được cài đặt vào `composer.json`)*
```bash
composer require --dev laravel/dusk
php artisan dusk:install
```

**Cách chạy Frontend / Browser Tests:**
1. Đảm bảo ứng dụng đang chạy ở một terminal khác:
```bash
php artisan serve
```
2. Chạy bộ test Dusk (trình duyệt Chrome sẽ tự động mở lên để chạy test):
```bash
php artisan dusk
```

*Lưu ý: Các test cases của Dusk được lưu tại thư mục `tests/Browser`.*

## 3. UAT Testing (Manual)
Đối với việc vận hành thử nghiệm (UAT) thủ công trên local, làm theo các bước sau:
1. Reset database và tạo seed dữ liệu (105 trận WC2026, admin, 5 user test):
```bash
php artisan migrate:fresh --seed
```
2. Truy cập hệ thống để chạy UAT:
- **Admin Panel:** `http://localhost:8000/admin` (Tài khoản: admin@company.com / password)
- **Player Portal:** `http://localhost:8000/player` (Tài khoản: player1@test.com / password)

## 4. Deploy lên VPS Production

**1. SSH vào máy chủ VPS**
```bash
ssh user@dia_chi_ip_vps
cd /var/www/thu_muc_du_an  # Đường dẫn chứa source code
```

**2. Cập nhật Source Code**
```bash
# Kéo code mới nhất từ nhánh đang làm việc (hiện tại là fixuiux)
git pull origin fixuiux
```

**3. Cập nhật Dependencies (Nếu có)**
```bash
# Cài đặt PHP packages (bỏ qua các package dev)
composer install --no-dev --optimize-autoloader

# Cài đặt Node modules & build (nếu có cập nhật frontend/tailwind)
npm install
npm run build
```

**4. Chạy Migration Database**
```bash
# --force là bắt buộc khi chạy trên môi trường production
php artisan migrate --force
```

**5. Tối ưu hóa & Xóa Cache**
```bash
# Xóa toàn bộ cache rác
php artisan optimize:clear

# Re-cache lại toàn bộ hệ thống để tăng tốc độ load
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# Cache riêng cho Filament Admin UI
php artisan filament:cache-components
php artisan icons:cache
```

**6. Khởi động lại các Process chạy ngầm**
```bash
# Cực kỳ quan trọng: Restart queue worker để code mới có tác dụng trong Jobs
php artisan queue:restart

# (Tùy chọn) Nếu sử dụng Supervisor quản lý queue
sudo supervisorctl restart all
```

**7. Khởi động lại Web Server/PHP (Nếu cần)**
```bash
# Tuỳ theo version PHP đang dùng, ví dụ PHP 8.2 hoặc 8.3
sudo systemctl restart php8.3-fpm
# Nếu dùng Nginx
sudo systemctl reload nginx
```
