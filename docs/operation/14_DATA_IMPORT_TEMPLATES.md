---
title: "Data Import Templates"
project: "Dự Đoán Lá"
version: "1.0"
status: "draft"
last_updated: "2026-06-11"
owner: "Operations / Engineering"
---

# Data Import Templates

## 1. Mục tiêu

Tài liệu này chuẩn hóa các file CSV/XLSX dùng để import dữ liệu vào hệ thống:

```text
- User
- Lịch trận
- Market
- Odds tỉ số chính xác
- Odds kèo châu Á
- Odds tài/xỉu
- Cấp lá ban đầu
```

Nguyên tắc: import phải **preview trước**, validate từng dòng, ghi batch id và có thể export lỗi.

Riêng lịch WC2026 dùng policy chi tiết tại `docs/operation/23_WC2026_FILE_IMPORT_POLICY.md`.

---

## 2. Quy tắc chung cho file import

## 2.1. Encoding

```text
UTF-8
```

Nếu mở bằng Excel tiếng Việt, nên dùng CSV UTF-8 with BOM hoặc XLSX.

## 2.2. Date/time

```text
YYYY-MM-DD HH:mm:ss
Timezone: Asia/Ho_Chi_Minh
```

Ví dụ:

```text
2026-06-12 02:00:00
```

## 2.3. Decimal

Dùng dấu chấm:

```text
0.90
2.25
-0.75
```

Không dùng:

```text
0,90
2,25
```

## 2.4. Odds kiểu Việt Nam

Cột `profit_rate` là **tỷ lệ ăn**, không phải decimal odds.

```text
Đúng: 0.90
Sai nếu muốn ăn 0.90: 1.90
```

Công thức hệ thống:

```text
Thắng đủ => gross payout = stake * (1 + profit_rate)
```

---

## 3. `users_import.csv`

## 3.1. Mục đích

Import user nội bộ.

## 3.2. Columns

| Column | Required | Type | Ví dụ | Ghi chú |
|---|---:|---|---|---|
| name | Yes | string | Nguyen Van A | Tên hiển thị |
| email | Yes | email | a@example.com | Unique |
| username | No | string | nguyenvana | Optional |
| role | Yes | enum | player | player/operator/auditor |
| status | Yes | enum | ACTIVE | ACTIVE/INACTIVE |
| initial_leaves | No | integer | 1000 | Nếu muốn cấp lá ngay |

## 3.3. Template

```csv
name,email,username,role,status,initial_leaves
Nguyen Van A,a@example.com,nguyenvana,player,ACTIVE,1000
Tran Thi B,b@example.com,tranthib,player,ACTIVE,1000
```

## 3.4. Validation

```text
- email unique.
- role tồn tại.
- initial_leaves >= 0.
- Nếu initial_leaves > 0 phải tạo wallet ledger ADMIN_GRANT.
```

---

## 4. `fixtures_import.csv`

Tham khao:

- File mau ngan: `docs/operation/import_samples/fixtures_import.csv`
- File full toan giai WC2026: `docs/operation/import_samples/fixtures_import_full_wc2026.csv`

## 4.1. Mục đích

Import lịch trận cố định theo WC2026 hoặc giải nội bộ.

MVP mặc định nhập lịch WC2026 từ file, không scrape live và không gọi external API.

## 4.2. Columns

| Column | Required | Type | Ví dụ | Ghi chú |
|---|---:|---|---|---|
| season_code | Yes | string | WC2026 | Mùa giải |
| match_code | Yes | string | M001 | Unique trong season |
| round | Yes | enum/string | GROUP_STAGE | GROUP_STAGE, R32, R16... |
| home_team | Yes | string | TBD | Có thể TBD |
| away_team | Yes | string | TBD | Có thể TBD |
| kickoff_at | Yes | datetime | 2026-06-12 02:00:00 | Giờ VN |
| venue | No | string | Estadio Azteca | Sân |
| status | Yes | enum | DRAFT | DRAFT/SCHEDULED |

## 4.3. Template

```csv
season_code,match_code,round,home_team,away_team,kickoff_at,venue,status
WC2026,M001,GROUP_STAGE,Mexico,South Africa,2026-06-12 02:00:00,Estadio Azteca,DRAFT
WC2026,M002,GROUP_STAGE,South Korea,Czechia,2026-06-12 05:00:00,Guadalajara,DRAFT
```

## 4.4. Validation

```text
- season_code tồn tại hoặc được tạo trước.
- match_code unique theo season.
- kickoff_at parse được theo timezone VN.
- Không overwrite trận đã có bet nếu không có quyền đặc biệt.
```

---

## 5. `markets_import.csv`

## 5.1. Mục đích

Tạo market/mốc dự đoán cho trận.

## 5.2. Columns

| Column | Required | Type | Ví dụ |
|---|---:|---|---|
| match_code | Yes | string | M001 |
| period_type | Yes | enum | FULL_TIME |
| market_type | Yes | enum | ASIAN_HANDICAP |
| open_at | Yes | datetime | 2026-06-11 08:00:00 |
| close_at | Yes | datetime | 2026-06-12 01:55:00 |
| status | Yes | enum | DRAFT |

## 5.3. Allowed `period_type`

```text
FULL_TIME
FIRST_HALF
SECOND_HALF
EXTRA_TIME
PENALTY
```

## 5.4. Allowed `market_type`

```text
EXACT_SCORE
ASIAN_HANDICAP
OVER_UNDER
PENALTY_WINNER
```

## 5.5. Template

```csv
match_code,period_type,market_type,open_at,close_at,status
M001,FULL_TIME,ASIAN_HANDICAP,2026-06-11 08:00:00,2026-06-12 01:55:00,DRAFT
M001,FULL_TIME,OVER_UNDER,2026-06-11 08:00:00,2026-06-12 01:55:00,DRAFT
M001,FULL_TIME,EXACT_SCORE,2026-06-11 08:00:00,2026-06-12 01:55:00,DRAFT
```

---

## 6. `asian_handicap_odds_import.csv`

## 6.1. Columns

| Column | Required | Type | Ví dụ | Ghi chú |
|---|---:|---|---|---|
| match_code | Yes | string | M001 |  |
| period_type | Yes | enum | FULL_TIME |  |
| selection_side | Yes | enum | HOME | HOME/AWAY |
| line_value | Yes | decimal | -0.5 | Handicap |
| profit_rate | Yes | decimal | 0.90 | Tỷ lệ ăn kiểu VN |
| label | No | string | Home -0.5 ăn 0.90 | Có thể auto generate |
| status | Yes | enum | ACTIVE | ACTIVE/SUSPENDED |

## 6.2. Template

```csv
match_code,period_type,selection_side,line_value,profit_rate,label,status
M001,FULL_TIME,HOME,-0.5,0.90,Home -0.5 ăn 0.90,ACTIVE
M001,FULL_TIME,AWAY,0.5,0.90,Away +0.5 ăn 0.90,ACTIVE
M001,FIRST_HALF,HOME,-0.25,0.85,Home -0.25 ăn 0.85,ACTIVE
M001,FIRST_HALF,AWAY,0.25,0.95,Away +0.25 ăn 0.95,ACTIVE
```

## 6.3. Validation

```text
- line_value thuộc các mốc hợp lệ: 0, 0.25, 0.5, 0.75, 1, 1.25...
- HOME và AWAY nên có line đối ứng.
- profit_rate trong min/max cấu hình.
- Nếu nhập 1.90, cảnh báo có thể nhầm với decimal odds.
```

---

## 7. `over_under_odds_import.csv`

## 7.1. Columns

| Column | Required | Type | Ví dụ |
|---|---:|---|---|
| match_code | Yes | string | M001 |
| period_type | Yes | enum | FULL_TIME |
| selection_side | Yes | enum | OVER |
| line_value | Yes | decimal | 2.5 |
| profit_rate | Yes | decimal | 0.90 |
| label | No | string | Tài 2.5 ăn 0.90 |
| status | Yes | enum | ACTIVE |

## 7.2. Template

```csv
match_code,period_type,selection_side,line_value,profit_rate,label,status
M001,FULL_TIME,OVER,2.5,0.90,Tài 2.5 ăn 0.90,ACTIVE
M001,FULL_TIME,UNDER,2.5,0.90,Xỉu 2.5 ăn 0.90,ACTIVE
M001,FIRST_HALF,OVER,1.0,0.85,Tài 1 ăn 0.85,ACTIVE
M001,FIRST_HALF,UNDER,1.0,0.95,Xỉu 1 ăn 0.95,ACTIVE
```

## 7.3. Validation

```text
- line_value > 0.
- line_value có thể là .0, .25, .5, .75.
- OVER và UNDER nên có cùng line.
- profit_rate trong min/max cấu hình.
```

---

## 8. `exact_score_odds_import.csv`

## 8.1. Columns

| Column | Required | Type | Ví dụ |
|---|---:|---|---|
| match_code | Yes | string | M001 |
| period_type | Yes | enum | FULL_TIME |
| score_home | Yes | integer | 2 |
| score_away | Yes | integer | 1 |
| profit_rate | Yes | decimal | 6.00 |
| label | No | string | 2-1 ăn 6.00 |
| status | Yes | enum | ACTIVE |

## 8.2. Template

```csv
match_code,period_type,score_home,score_away,profit_rate,label,status
M001,FULL_TIME,0,0,6.00,0-0 ăn 6.00,ACTIVE
M001,FULL_TIME,1,0,5.50,1-0 ăn 5.50,ACTIVE
M001,FULL_TIME,1,1,5.00,1-1 ăn 5.00,ACTIVE
M001,FULL_TIME,2,1,7.00,2-1 ăn 7.00,ACTIVE
```

## 8.3. Validation

```text
- score_home >= 0.
- score_away >= 0.
- Không duplicate cùng match/period/score.
- profit_rate cho tỉ số có thể cao hơn handicap, nhưng vẫn nên có max riêng.
```

---

## 9. `wallet_grants_import.csv`

## 9.1. Columns

| Column | Required | Type | Ví dụ |
|---|---:|---|---|
| email | Yes | email | a@example.com |
| season_code | Yes | string | WC2026 |
| amount | Yes | integer | 1000 |
| reason | Yes | string | Cấp lá đầu mùa |

## 9.2. Template

```csv
email,season_code,amount,reason
a@example.com,WC2026,1000,Cấp lá đầu mùa
b@example.com,WC2026,1000,Cấp lá đầu mùa
```

## 9.3. Validation

```text
- User tồn tại.
- Wallet tồn tại hoặc được tạo.
- amount > 0.
- Mỗi dòng phải tạo wallet_ledger type ADMIN_GRANT.
- Không import trùng nếu batch đã chạy, trừ khi admin xác nhận.
```

---

## 10. Import workflow chuẩn

```text
1. Admin upload file.
2. Hệ thống parse file.
3. Validate từng dòng.
4. Hiển thị preview:
   - Tổng dòng
   - Dòng hợp lệ
   - Dòng lỗi
   - Cảnh báo
5. Admin xác nhận import.
6. Hệ thống chạy transaction hoặc import batch.
7. Ghi import log.
8. Cho phép tải file lỗi.
```

---

## 11. Import error format

File lỗi nên có thêm cột:

```text
row_number
error_code
error_message
```

Ví dụ:

```csv
row_number,match_code,period_type,selection_side,line_value,profit_rate,error_code,error_message
5,M001,FULL_TIME,HOME,-0.5,1.90,ODDS_SUSPICIOUS,Profit rate 1.90 có thể là nhầm decimal odds. Nếu muốn ăn 0.90 hãy nhập 0.90.
```

---

## 12. Checklist trước khi cho admin import thật

```text
[ ] Template đúng encoding UTF-8.
[ ] Date/time đúng giờ Việt Nam.
[ ] profit_rate nhập theo kiểu ăn 0.90.
[ ] Có preview trước khi execute.
[ ] Có rollback khi import fail.
[ ] Có audit log import.
[ ] Có file lỗi để tải về.
[ ] Không update odds cũ đã có bet nếu không tạo version mới.
```
