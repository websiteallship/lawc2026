---
title: "RapidAPI Odds Integration Specification"
project: "Du Doan La"
version: "1.0"
status: "draft"
last_updated: "2026-06-13"
---

# RapidAPI Odds Integration Specification

## 1. Mục tiêu

Tài liệu này xác định phương án kéo tỉ lệ kèo (odds) từ RapidAPI (cụ thể là API-Football) và tích hợp vào hệ thống Dự Đoán Lá, tuân thủ nghiêm ngặt các quy tắc Business Logic và định dạng cá cược kiểu Việt Nam.

---

## 2. Provider Lựa chọn

Sử dụng **API-Football** (by API-SPORTS) trên nền tảng RapidAPI.
- **Endpoint chính:** `GET /v3/odds` (pre-match) và `GET /v3/odds/live` (in-play).
- **Bookmaker khuyên dùng:** Bet365 (ID: 8) hoặc Pinnacle (ID: 104) để đảm bảo chuẩn xác về kèo Châu Á và Tài/Xỉu.

---

## 3. Ánh xạ Dữ liệu (Data Mapping)

### 3.1. Cấu trúc Market & Parsing Rules
- **Lọc Dữ liệu (Whitelist):**
  - Hệ thống **CHỈ** xử lý các Bet ID: `4`, `11` (Kèo Chấp), `5`, `12` (Tài Xỉu), `10` (Tỉ số). 
  - Tất cả các Bet ID khác (ví dụ: `1` - Match Winner, `20` - Double Chance, v.v.) BẮT BUỘC bị loại bỏ hoàn toàn (ignore) ngay trong quá trình xử lý.
- **Asian Handicap (Kèo Chấp - Bet ID 4, 11):** 
  - Giá trị API: `Home -0.5`, `Away +0.5`.
  - Phân tách (Parse): `selection_side` = Home/Away, `$raw_line` = phần số.
- **Over/Under (Tài Xỉu - Bet ID 5, 12):** 
  - Giá trị API: `Over 2.5`, `Under 2.5`.
  - Phân tách: `selection_side` = Over/Under, `$raw_line` = phần số.
- **Exact Score (Tỉ số - Bet ID 10):** 
  - Giá trị API: `2:1` hoặc `0:0`.
  - Phân tách: Cắt chuỗi theo dấu `:` hoặc `-` để lấy `score_home` và `score_away`.

> **Xử lý ngoại lệ (Non-standard Lines):**
> Nếu `$raw_line` lẻ không thuộc bội số của `0.25` (ví dụ: `3.1`), hệ thống phải làm tròn về mốc `0.25` gần nhất (`$normalized = round($raw_line * 4) / 4`). Nếu line làm tròn không thuộc danh sách hỗ trợ tại `AGENTS.md`, bỏ qua (ignore).

### 3.2. Quy tắc Chuyển đổi Odds (Nghiêm ngặt)
API trả về tỷ lệ cược dưới dạng thập phân (Decimal Odds), bắt buộc phải chuẩn hóa về `profit_rate` theo cách tính kiểu Việt Nam.

- **Chuẩn Decimal ($odds > 1.0):** 
  `$profit_rate = round($odds - 1, 3);` 
  *(VD: Odds 1.91 -> profit_rate = 0.910)*
- **Chuẩn HongKong/Malay ($odds < 1.0):** 
  `$profit_rate = $odds;` 
  *(VD: Odds 0.91 -> profit_rate = 0.910)*

### 3.3. Ánh xạ Thời gian thi đấu (Period Type)
API-Football tách các kèo theo hiệp qua `bet_id` riêng biệt:
- **Hiệp 1 / Hiệp 2:** Căn cứ `bet_id` (VD: First Half Asian Handicap) gán `period_type = FIRST_HALF` hoặc `SECOND_HALF`.
- **Hiệp phụ / Luân lưu:** Lấy từ các kèo dạng "Extra time winner", "To Qualify", ánh xạ sang `EXTRA_TIME` / `PENALTY`. Nếu không có, khởi tạo Market ở trạng thái `DRAFT` để Admin cấu hình tay.

### 3.4. Logic Tự động Lưu trữ DB (MarketSyncService)
Khi nhận `OddsResponseDto` từ `OddsIntegrationService`, `MarketSyncService` sẽ thực thi logic lưu trữ tuân thủ chặt chẽ Business Logic:
1. **Tìm/Tạo Kèo (Market):**
   - Dùng `updateOrCreate` trên bảng `markets` với điều kiện `[match_id, market_type, period_type]`.
   - Nếu kèo đang đóng, bỏ qua không update để tránh đè dữ liệu sau khi khóa cược. Nếu mới, set `status = OPEN`.
2. **Upsert Tỷ lệ (Outcome):**
   - Với mỗi `OutcomeDataDto` hợp lệ, tiếp tục dùng `updateOrCreate` trên bảng `market_outcomes` dựa vào `[market_id, selection_side, line_value]`.
   - **Quy tắc Ghi đè (Overwrite):** Chỉ cập nhật cột `profit_rate` mới nhất. Tuyệt đối không xóa các outcome cũ (vì API có thể ẩn tạm thời line đó, hoặc user đã cược).
3. **Bảo vệ toàn vẹn (Transactions):** Toàn bộ tiến trình tạo/cập nhật kèo cho 1 trận bắt buộc nằm gọn trong 1 `DB::transaction()`.
4. **Snapshot Rule:** Mọi cập nhật `profit_rate` chỉ thay đổi trên `market_outcomes`. Bảng `bets` giữ nguyên `profit_rate_snapshot` để tránh thay đổi tỷ lệ vé đã cược.
---

## 4. Chiến lược Gọi API (Fixed Snapshot Strategy) & Tối ưu Quota

**Câu hỏi:** Bản Free API-Football (100 requests/ngày) có đủ không và có cho kéo tỷ lệ không?
- **Trả lời:** CÓ. Bản Free hỗ trợ endpoint `/odds`. 100 requests/ngày là **thừa đủ** cho World Cup nhờ kiến trúc Tách biệt Trách nhiệm (Separation of Concerns).

### 4.1. Kiến trúc Tiết kiệm Request (Event-Driven)
Hệ thống **KHÔNG** dùng RapidAPI để quét trạng thái trận đấu liên tục, mà dựa vào `football-data.org` (Gói Free 14.400 req/ngày - Đã setup ở File `20_FOOTBALL_DATA_API_INTEGRATION.md`).
- Luồng quét tỷ số/trạng thái (1 phút/lần) chạy trên `football-data.org`.
- RapidAPI chỉ đóng vai trò "Kéo tỷ lệ", bị gọi thụ động khi có Event bắn ra từ luồng quét trên.

### 4.2. Thời điểm chốt kèo (Snapshot Timing) & Tiêu hao Quota
World Cup có tối đa 4 trận/ngày. Thay vì gọi từng `fixture_id` (do bất đồng bộ ID với `football-data.org`), hệ thống gọi theo ngày:
`/odds?league=1&season=2026&date={YYYY-MM-DD}&bookmaker=8`
- **Pre-match:** Kéo 1 lần vào buổi sáng cho toàn bộ các trận trong ngày. *(Tốn 1 request)*
- **Live Event:** Khi bất kỳ trận nào báo sự kiện (HALFTIME, REGULAR_TIME_FINISHED), kéo lại endpoint trên để lấy kèo Live. *(Chỉ tốn 1 request cập nhật cho tất cả)*
**=> Tổng tiêu hao:** Chỉ tốn tối đa **2-5 requests / NGÀY** cho toàn bộ giải đấu, siêu an toàn cho gói Free 100 req/ngày. Việc ánh xạ dữ liệu sẽ dùng tên đội bóng (`home_team`, `away_team`) thay vì `id`.

### 4.3. Luồng xử lý tự động (Automated Workflow)
1. Job đồng bộ tỷ số (`football-data.org`) phát hiện trạng thái trận đấu thay đổi.
2. Bắn Event kích hoạt kéo Odds.
3. Listener gọi endpoint `/odds?league=1&season=2026&date={date}&bookmaker=8` của RapidAPI.
4. Ánh xạ dữ liệu trả về với bảng `matches` qua tên đội. Lưu DB, đánh cờ `is_odds_fetched = true`. Tự động mở cược (`OPEN`).

---

## 5. Logic Đóng/Mở Kèo Tự Động (Market Lock/Open Strategy)

Việc đóng/mở kèo (`OPEN` / `LOCKED`) phải phản hồi tự động theo dòng thời gian trận đấu:

| Loại kèo | Thời điểm Mở (OPEN) | Thời điểm Đóng (LOCKED) |
|---|---|---|
| **Cả trận & Hiệp 1** | Ngay khi có tỷ lệ từ API. | Khi trận đấu báo `IN_PLAY` hoặc chạm mốc `kickoff_at`. |
| **Hiệp 2** | Khi trạng thái là `HALFTIME`. | Ngay khi còi bắt đầu Hiệp 2 vang lên. |
| **Hiệp phụ (Extra Time)** | Sau 90 phút hòa (`REGULAR_TIME_FINISHED`). | Khi Hiệp phụ chính thức bắt đầu. |
| **Luân lưu (Penalty)** | Sau 120 phút hòa. | Trước khi loạt sút luân lưu đầu tiên diễn ra. |

> **Khẩn cấp:** Bất cứ khi nào API báo cờ `Suspended/Stopped` (kèo bị tạm ngưng từ nhà cái), toàn bộ Market đang diễn ra phải lập tức chuyển sang `LOCKED`.

---

## 6. Business Logic Rules

1. **Transaction & Locking:**
   Mọi thao tác upsert `profit_rate` hoặc `line` trên bảng `markets`/`outcomes` phải đặt trong DB Transaction.
2. **Snapshot Rule:**
   Việc thay đổi `profit_rate` tuyệt đối **KHÔNG** làm thay đổi tỷ lệ của những vé cược (`bets`) đã đặt trước đó. Vé cược luôn dùng `profit_rate_snapshot`.
3. **Quản lý Cấu hình:**
   Lưu khóa API RapidAPI và các thông số cài đặt trong `app/Settings/ApiSettings.php` để Admin có thể cấu hình từ giao diện Filament.
4. **Domain Driven Design:**
   Mọi logic xử lý, ánh xạ, lưu trữ từ API bắt buộc đặt tại thư mục Domain, ví dụ: `app/Domain/Market/Services/OddsIntegrationService.php`. Tuyệt đối không viết logic trong Job hay Controller.
