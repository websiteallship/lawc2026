# Hướng dẫn chống DDoS - Tầng Ứng dụng (Laravel)

Tài liệu này hướng dẫn cấu hình chống DDoS, spam request ở tầng Application bằng các cơ chế có sẵn của Laravel.

## 1. Rate Limiting Middleware (ThrottleRequests)

Laravel cung cấp middleware `throttle` để giới hạn số lượng request.

### Cấu hình trong `app/Providers/RouteServiceProvider.php` (hoặc `bootstrap/app.php` ở Laravel 11):

Định nghĩa các Rate Limiter logic:

```php
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Http\Request;

protected function configureRateLimiting(): void
{
    // Global limit (áp dụng chung)
    RateLimiter::for('global', function (Request $request) {
        return Limit::perMinute(100)->by($request->ip());
    });

    // API limit (áp dụng cho các route API)
    RateLimiter::for('api', function (Request $request) {
        // Nếu user đã đăng nhập, giới hạn theo user ID (chống spam từ nhiều IP bởi 1 acc)
        // Nếu chưa đăng nhập, giới hạn theo IP
        return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
    });

    // Login limit (Chống Brute-force & DDoS vào cổng auth)
    RateLimiter::for('login', function (Request $request) {
        return Limit::perMinute(5)->by($request->ip());
    });
}
```

### Áp dụng Middleware vào Routes

Trong `routes/api.php` hoặc `routes/web.php`:

```php
// Áp dụng middleware throttle
Route::middleware(['throttle:api'])->group(function () {
    Route::post('/bet', [BetController::class, 'placeBet']);
});
```

Hoặc cấu hình Global Middleware trong `app/Http/Kernel.php` (với Laravel < 11):

```php
protected $middleware = [
    // ...
    'throttle:global',
];
```

## 2. Bảo vệ cổng Admin (Filament)

Filament Admin panel cần được bảo vệ chặt chẽ để tránh spam login và ddos.

### Auth Throttling:
Filament mặc định sử dụng rate limiting trên trang đăng nhập. Có thể kiểm tra trong file config `config/filament.php` (thường là plugin/auth) để đảm bảo throttle middleware đang được áp dụng. Đổi URL đăng nhập từ `/admin` sang một đường dẫn ẩn khác nếu cần thiết (security through obscurity).

## 3. Quản lý Cache (Redis)

Việc rate limiting trong Laravel sẽ tạo gánh nặng lớn lên hệ thống lưu trữ (mặc định là file). Để tối ưu hiệu suất dưới áp lực DDoS:

1. Đảm bảo config `CACHE_DRIVER=redis` trong `.env`.
2. Redis giúp Laravel xử lý tốc độ đọc/ghi rate limiter nhanh hơn file hệ thống rất nhiều.
