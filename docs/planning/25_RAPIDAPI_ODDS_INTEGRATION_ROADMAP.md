# 25. Roadmap Tích hợp RapidAPI Odds (API-Football)

Tiến trình chi tiết xây dựng tính năng đồng bộ tỷ lệ kèo cược từ RapidAPI vào hệ thống theo kiến trúc Domain-Driven Design (DDD) và tuân thủ chặt chẽ tài liệu kỹ thuật (quy tắc làm tròn, chuyển đổi tỷ lệ Việt Nam).

## Phase 1: Foundation & Cấu hình (Thiết lập Nền tảng)

**1.1. Bổ sung cấu hình Admin (Settings)**
- **Mục tiêu:** Quản lý khóa API của RapidAPI.
- **Thực hiện:** Cập nhật `app/Settings/ApiSettings.php` và trang Filament `ManageApiSettings.php` để thêm các trường: `rapidapi_key`, `rapidapi_host` (mặc định: `v3.football.api-sports.io`), `bookmaker_id` (mặc định: `8` - Bet365).

**1.2. Tạo Data Transfer Objects (DTOs)**
- **Mục tiêu:** Chuẩn hóa dữ liệu trả về từ API trước khi đưa vào Domain.
- **Thực hiện:** Tạo các class DTO (VD: `OddsResponseDto`, `MarketDataDto`, `OutcomeDataDto`) trong thư mục `app/Domain/Market/DTOs/`.

## Phase 2: Core Domain Services (Ánh xạ & Xử lý Nghiệp vụ)

**2.1. Phát triển `OddsIntegrationService`**
- **Vị trí:** `app/Domain/Market/Services/OddsIntegrationService.php`.
- **Nhiệm vụ:** Gọi HTTP Client (có retry logic) tới endpoint `/v3/odds` của RapidAPI.

**2.2. Xây dựng Logic Chuyển đổi & Phân tách (MAPPING & PARSING)**
- **Lọc dữ liệu (Whitelist):** Chỉ xử lý các Bet ID 4, 11 (Chấp), 5, 12 (Tài Xỉu), 10 (Tỉ số). Bỏ qua hoàn toàn các ID không hỗ trợ (VD: 20 - Double Chance).
- **Tỷ lệ cược (Odds):** 
  - Đọc `odd` từ API (dạng thập phân).
  - Áp dụng luật: Nếu `odd > 1.0` -> `profit_rate = odd - 1`. Nếu `odd < 1.0` -> `profit_rate = odd`.
- **Phân tách Chuỗi Kèo (Parsing Values):**
  - **Kèo Chấp (Bet ID 4, 11):** Parse chuỗi `Home -0.5` -> `selection_side = Home`, `$raw_line = -0.5`.
  - **Tài Xỉu (Bet ID 5, 12):** Parse chuỗi `Over 2.5` -> `selection_side = Over`, `$raw_line = 2.5`.
  - **Tỉ số (Bet ID 10):** Parse chuỗi `2:1` hoặc `0-0` bằng regex/explode -> `score_home = 2`, `score_away = 1`.
- **Làm tròn Mốc (Line Normalization):**
  - Chạy hàm làm tròn: `$normalized_line = round($raw_line * 4) / 4`.
  - Nếu kết quả không thuộc danh sách hỗ trợ tại `AGENTS.md` -> loại bỏ.
- **Thời gian (Period):**
  - Đọc `bet_id` (ví dụ ID 1 = Cả trận, ID 13 = Hiệp 1, ID 11 = Hiệp 1 chấp, v.v.) và ánh xạ sang `period_type` (`FULL_TIME`, `FIRST_HALF`...).

**2.3. Xây dựng `MarketSyncService` (Logic Lưu trữ DB)**
- **Vị trí:** `app/Domain/Market/Services/MarketSyncService.php`
- **Nhiệm vụ:** Nhận `OddsResponseDto` và lưu DB an toàn.
- **Quy trình thực thi:**
  1. Mở `DB::transaction`.
  2. Map `bet_id` sang `market_type` và `period_type`.
  3. Dùng `updateOrCreate` để tìm hoặc tạo `Market` dựa trên `[match_id, market_type, period_type]`. Thiết lập `status = OPEN`.
  4. Duyệt các `OutcomeDataDto`:
     - Phân tách (Parse) ra `selection_side`, `line_value` (hoặc `score_home`, `score_away`).
     - Bỏ qua các line không hợp lệ theo chuẩn.
     - Dùng `updateOrCreate` trên bảng `market_outcomes` để chèn hoặc cập nhật `profit_rate`.
  5. **Snapshot Rule:** Cập nhật `profit_rate` tuyệt đối KHÔNG update ngược lại bảng `bets` (để giữ nguyên tỷ lệ cược của các vé đã đặt).

## Phase 3: Tự động hóa (Cronjobs & Event Triggers)

**3.1. Kéo dữ liệu Pre-match (`SyncPreMatchOddsJob`)**
- **Nhiệm vụ:** Kéo tỷ lệ Cả trận & Hiệp 1 cho các trận `SCHEDULED`.
- **Điều kiện:** Thực thi đúng 1 lần vào thời điểm **trước 12 tiếng** so với `kickoff_at`.

**3.2. Bắt sự kiện Live (`MatchStatusChanged`)**
- **Nhiệm vụ:** Kéo tỷ lệ cho Hiệp 2, Hiệp phụ, Penalty.
- **Thực hiện:** Bắt Event từ luồng quét tỷ số khi trận đấu đổi sang các trạng thái:
  - `HALFTIME`: Kéo kèo Hiệp 2.
  - `REGULAR_TIME_FINISHED` (Hết 90p hòa): Kéo kèo Hiệp phụ.
  - Hết 120p hòa: Kéo kèo Penalty.
- **Chặn trùng lặp (Fixed Snapshot):** Đánh cờ `is_odds_fetched = true` cho từng mốc thời gian để khóa vĩnh viễn request lặp (tiết kiệm Quota).

**3.3. Logic Đóng/Mở Kèo Tự Động (Market Lock/Open Strategy)**
- **Mở (`OPEN`):** Ngay sau khi kéo tỷ lệ thành công ở bước 3.1 hoặc 3.2.
- **Đóng (`LOCKED`):** 
  - Kèo Cả trận/Hiệp 1: Đóng khi tới `kickoff_at` hoặc trận đổi sang `IN_PLAY`.
  - Kèo Hiệp 2: Đóng ngay khi còi Hiệp 2 vang lên.
  - Kèo Hiệp phụ: Đóng khi Hiệp phụ bắt đầu.
  - Kèo Pen: Đóng trước loạt sút đầu tiên.
  - **Khẩn cấp:** Lập tức `LOCKED` nếu API báo tỷ lệ `Suspended/Stopped`.

## Phase 4: UI/UX & Fallback (Giao diện Admin & Xử lý lỗi)

**4.1. Action Kéo tay (Manual Sync)**
- Thêm một nút `Action` (VD: "Đồng bộ Kèo API") trên trang chi tiết trận đấu (`FootballMatchResource`) để Admin có thể chủ động kéo lại tỷ lệ cược nếu Auto-sync bị lỗi.

**4.2. Quản lý Lỗi (Error Handling & Activity Log)**
- Cấu hình bắt `HTTP 429 Too Many Requests`. Nếu gặp, log lại cảnh báo qua Activity Log cho Admin và ngắt Job.

## Phase 5: Verification (Kiểm thử)

**5.1. Unit Tests**
- Kiểm thử logic tính `profit_rate` từ `decimal_odds`.
- Kiểm thử thuật toán `round($line * 4) / 4` đối với các giá trị line dị (như `3.1`, `2.2`).

**5.2. Integration Tests**
- Mock HTTP Client của RapidAPI.
- Chạy thử `SyncPreMatchOddsJob` và verify dữ liệu sinh ra đúng cấu trúc bảng `Market` và `MarketOutcome`.
