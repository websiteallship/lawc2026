# Hướng dẫn Kiểm thử (Testing) - Dự Đoán Lá WC2026

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
