# 20. Football-Data.org API Integration Guide

> Tài liệu này mô tả thiết kế và luồng tích hợp dữ liệu bóng đá từ API `football-data.org` vào dự án Dự Đoán Lá, tập trung vào giải đấu World Cup 2026. 
> Mục tiêu là tự động hóa việc cập nhật lịch thi đấu và tỉ số mà không vi phạm rate limit của gói Free (10 requests/minute).

---

## 1. Tổng quan API & Gói cước (Free Tier)

- **Provider:** [football-data.org](https://www.football-data.org)
- **Rate Limit:** 10 requests / phút (~14.400 requests / ngày).
- **Độ trễ (Latency):** Tỉ số live có thể bị delay từ 1-3 phút so với thực tế (hoàn toàn chấp nhận được cho game dự đoán nội bộ).
- **Authentication:** Gửi API token qua Header `X-Auth-Token`.

---

## 2. Các chỉ số (Metrics) Bắt buộc phải cập nhật Real-time / Near Real-time

Dựa trên cấu trúc cá cược (Cả trận, Hiệp 1, Hiệp 2, Hiệp phụ, Luân lưu) của dự án, hệ thống cần bám sát các chỉ số sau từ API:

### 2.1. Trạng thái trận đấu (Match Status)
Từ API, trường `status` thường có các giá trị:
- `SCHEDULED` / `TIMED`: Trận đấu chưa diễn ra.
- `IN_PLAY` / `PAUSED`: Trận đấu đang diễn ra (Hiệp 1, Nghỉ giữa hiệp, Hiệp 2,...). -> Map về `LIVE` trong database của chúng ta.
- `FINISHED`: Trận đấu đã kết thúc chính thức. -> Map về `FINISHED`.

### 2.2. Điểm số chi tiết (Scores)
football-data.org trả về object `score` chia làm các mốc thời gian. Chúng ta cần map trực tiếp vào model `MatchPeriodResult` hoặc `FootballMatch`:
- `score.fullTime.home` / `away`: Tỉ số cuối cùng của trận đấu (bao gồm 90 phút + bù giờ, không tính hiệp phụ).
- `score.halfTime.home` / `away`: Tỉ số kết thúc Hiệp 1.
- `score.extraTime.home` / `away`: Tỉ số sau Hiệp phụ (nếu có).
- `score.penalties.home` / `away`: Tỉ số luân lưu (nếu có).

### 2.3. Lịch thi đấu & Giờ bóng lăn (Kickoff Time)
- `utcDate`: Dùng để cập nhật `kickoff_at` trong trường hợp BTC dời giờ thi đấu.

---

## 3. Chiến lược Gọi API (Cronjob Strategy)

Để tiết kiệm request và tránh vượt quá giới hạn 10 calls/min, hệ thống sẽ chia làm 2 luồng quét chính:

### Luồng 1: Sync Lịch Thi Đấu (Daily / Hourly)
- **Tên Job:** `SyncScheduledMatchesJob`
- **Tần suất:** Mỗi 6 tiếng một lần (hoặc 1 lần/ngày lúc 00:00).
- **Nhiệm vụ:** Gọi API lấy lịch thi đấu của World Cup (dùng `competition_id` của WC2026). Thêm mới các trận đấu vào DB hoặc cập nhật lại `kickoff_at` nếu có thay đổi.
- **API Endpoint:** `GET /v4/competitions/{id}/matches?status=SCHEDULED`

### Luồng 2: Sync Tỉ số Trực tiếp (Live Polling)
- **Tên Job:** `SyncLiveMatchScoresJob`
- **Tần suất:** Chạy mỗi phút `->everyMinute()`.
- **Điều kiện chạy (RẤT QUAN TRỌNG):** Cronjob CHỈ thực hiện gọi API nếu trong Database đang có ít nhất 1 trận đấu thỏa mãn điều kiện:
  `kickoff_at <= now()` VÀ `status` != `FINISHED`.
- **Nhiệm vụ:**
  - Nếu có trận đang đá, gọi API để lấy tỉ số live: `GET /v4/matches/{match_id}` hoặc lấy danh sách live toàn giải.
  - Cập nhật `home_score`, `away_score`, `status` vào bảng `football_matches`.
  - Nếu API trả về `FINISHED`, tự động tạo/cập nhật các record vào bảng `match_period_results` (Hiệp 1, Cả trận...).

---

## 4. Kiến trúc Code đề xuất

Hệ thống nên tổ chức mã nguồn trong `app/Domain/Match/Services/FootballDataApiService.php`.

### Mapping Trạng thái (Status Mapping)
```php
public function mapApiStatusToDomain(string $apiStatus): string
{
    return match($apiStatus) {
        'IN_PLAY', 'PAUSED' => 'LIVE',
        'FINISHED', 'AWARDED' => 'FINISHED',
        'POSTPONED', 'CANCELLED', 'SUSPENDED' => 'CANCELLED',
        default => 'SCHEDULED',
    };
}
```

### Xử lý kết thúc trận (Auto-Populate Results)
Khi trận đấu chuyển sang `FINISHED` từ API:
1. Hệ thống tự động điền (populate) kết quả vào `match_period_results`.
2. Trạng thái của `match_period_results` để là `CONFIRMED` (hoặc `DRAFT` nếu cần Admin duyệt lại).
3. **Quy tắc an toàn (Safety Rule):** Quá trình Trả thưởng (Settlement) KHÔNG NÊN tự động chạy ngay khi API báo kết thúc. Để đảm bảo tính an toàn dòng tiền (lá), hệ thống chỉ nên "Điền sẵn kết quả", sau đó Admin sẽ vào màn hình xác nhận kết quả lần cuối và bấm nút **"Execute Settlement"** thủ công.

---

## 5. Xử lý Lỗi & Giới hạn (Rate Limit Handling)

- Quản lý `X-Auth-Token` thông qua **Admin Settings** thay vì hardcode trong `.env` (Xem mục 6).
- Khi gọi HTTP Client, cấu hình Retry:
  ```php
  Http::withToken(app(\App\Settings\ApiSettings::class)->football_data_api_token)
      ->retry(3, 1000) // Retry 3 lần, cách nhau 1s nếu timeout
      ->get('...');
  ```
- Nếu API trả về HTTP 429 (Too Many Requests), Job cần bắt exception, ghi log (ActivityLog) cảnh báo Admin và bỏ qua lượt quét hiện tại, đợi cronjob phút tiếp theo chạy lại. Không được crash queue worker.

---

## 6. Tích hợp Quản lý API qua Admin Settings

Dự án sử dụng package `spatie/laravel-settings` và `filament/spatie-laravel-settings-plugin`. Việc cấu hình API sẽ được đưa ra giao diện Admin thay vì fix cứng trong code để tiện điều chỉnh luồng quét khi go-live.

### 6.1. Khởi tạo ApiSettings Class
Tạo file `app/Settings/ApiSettings.php`:
```php
namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class ApiSettings extends Settings
{
    public string $football_data_api_token;
    public bool $is_auto_sync_enabled;
    public int $auto_sync_interval_minutes;
    public string $competition_id; // Dùng cho World Cup (Ví dụ: 2000 là WC)

    public static function group(): string
    {
        return 'api';
    }
}
```

### 6.2. Migration Settings
Tạo file migration `database/settings/2026_06_12_xxxxxx_create_api_settings.php`:
```php
use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('api.football_data_api_token', '');
        $this->migrator->add('api.is_auto_sync_enabled', false);
        $this->migrator->add('api.auto_sync_interval_minutes', 3);
        $this->migrator->add('api.competition_id', '2000'); // 2000 là FIFA World Cup
    }
};
```

### 6.3. Filament Settings Page
Tạo file `app/Filament/Pages/ManageApiSettings.php`:
```php
namespace App\Filament\Pages;

use App\Settings\ApiSettings;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\SettingsPage;

class ManageApiSettings extends SettingsPage
{
    protected static ?string $navigationIcon = 'heroicon-o-cog-8-tooth';
    protected static ?string $navigationGroup = 'Hệ thống';
    protected static string $settings = ApiSettings::class;
    protected static ?string $navigationLabel = 'Cấu hình API';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Football-Data.org API')
                    ->description('Cấu hình kết nối lấy dữ liệu trận đấu và tỉ số tự động.')
                    ->schema([
                        Forms\Components\TextInput::make('football_data_api_token')
                            ->label('API Token')
                            ->password()
                            ->required(),
                        Forms\Components\TextInput::make('competition_id')
                            ->label('Competition ID')
                            ->default('2000')
                            ->required(),
                        Forms\Components\Toggle::make('is_auto_sync_enabled')
                            ->label('Bật tự động đồng bộ (Cronjob)')
                            ->helperText('Nếu tắt, hệ thống sẽ ngừng quét API tự động.'),
                        Forms\Components\Select::make('auto_sync_interval_minutes')
                            ->label('Tần suất quét Live Score')
                            ->options([
                                1 => 'Mỗi phút (Khuyên dùng khi có gói API trả phí)',
                                3 => 'Mỗi 3 phút',
                                5 => 'Mỗi 5 phút (Tiết kiệm quota)',
                            ])
                            ->required(),
                    ])->columns(2),
            ]);
    }
}
```

### 6.4. Chặn gọi API ở Cronjob nếu tắt Sync
Bên trong các Cronjob `SyncScheduledMatchesJob` và `SyncLiveMatchScoresJob`, phải check cờ `is_auto_sync_enabled`:
```php
$settings = app(\App\Settings\ApiSettings::class);

if (! $settings->is_auto_sync_enabled || empty($settings->football_data_api_token)) {
    return Command::SUCCESS; // Bỏ qua quét
}
```
