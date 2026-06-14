---
title: 24 PROJECT RESEARCH OVERVIEW WC2026
status: integrated
source: earlier-research-document
updated: 2026-06-12
---

# Tài liệu nghiên cứu & đặc tả sản phẩm: Website dự đoán bóng đá nội bộ bằng điểm ảo “lá”


## Quy ước tỷ lệ ăn kiểu Việt Nam

Tài liệu này dùng cách ghi phổ biến ở Việt Nam: **line + ăn + hệ số lãi**. Ví dụ `Home -0.5 ăn 0.90` nghĩa là đặt 100 lá, thắng đủ nhận 190 lá, gồm 100 lá vốn + 90 lá lãi.

Quy ước kỹ thuật:

```text
profit_rate = tỷ lệ ăn / hệ số lãi
decimal_odds = 1 + profit_rate
gross_payout_full_win = stake * (1 + profit_rate)
net_profit_full_win = stake * profit_rate
```


**Phiên bản:** 1.0  
**Ngày:** 2026-06-11  
**Bối cảnh triển khai:** Nội bộ công ty, phục vụ hoạt động giải trí/gắn kết nhân viên trong mùa FIFA World Cup 2026.  
**Tên gợi ý:** Lá Dự Đoán / Football Prediction League nội bộ.

> Tài liệu này mô tả sản phẩm theo hướng **game dự đoán nội bộ bằng điểm ảo**, không phải nền tảng cá cược thương mại. “Lá” chỉ là điểm ảo dùng để tham gia dự đoán và xếp hạng, không được nạp/rút/mua/bán/chuyển nhượng/quy đổi thành tiền, hiện vật hoặc dịch vụ.

---

## Mục lục

1. [Tuyên bố phạm vi & nguyên tắc pháp lý](#1-tuyên-bố-phạm-vi--nguyên-tắc-pháp-lý)
2. [Mục tiêu sản phẩm](#2-mục-tiêu-sản-phẩm)
3. [Nguyên tắc vận hành điểm ảo “lá”](#3-nguyên-tắc-vận-hành-điểm-ảo-lá)
4. [Vai trò người dùng](#4-vai-trò-người-dùng)
5. [Loại dự đoán/market cần hỗ trợ](#5-loại-dự-đoánmarket-cần-hỗ-trợ)
6. [Luật tính kết quả từng market](#6-luật-tính-kết-quả-từng-market)
7. [Các mốc trận đấu](#7-các-mốc-trận-đấu)
8. [Luồng nghiệp vụ](#8-luồng-nghiệp-vụ)
9. [Cơ chế ví lá & ledger](#9-cơ-chế-ví-lá--ledger)
10. [Leaderboard & game hóa](#10-leaderboard--game-hóa)
11. [Quản trị dữ liệu World Cup 2026 cố định](#11-quản-trị-dữ-liệu-world-cup-2026-cố-định)
12. [Lịch World Cup 2026 seed theo giờ Việt Nam](#12-lịch-world-cup-2026-seed-theo-giờ-việt-nam)
13. [Mô hình dữ liệu đề xuất](#13-mô-hình-dữ-liệu-đề-xuất)
14. [API/phân hệ kỹ thuật](#14-apiphân-hệ-kỹ-thuật)
15. [Bảo mật, chống gian lận, audit log](#15-bảo-mật-chống-gian-lận-audit-log)
16. [Màn hình chức năng](#16-màn-hình-chức-năng)
17. [Roadmap triển khai](#17-roadmap-triển-khai)
18. [Thể lệ mẫu đưa lên website](#18-thể-lệ-mẫu-đưa-lên-website)
19. [Nguồn tham khảo](#19-nguồn-tham-khảo)

---

## 1. Tuyên bố phạm vi & nguyên tắc pháp lý

### 1.1. Định vị sản phẩm

Sản phẩm nên được định vị là:

> **Website dự đoán kết quả bóng đá nội bộ bằng điểm ảo, phục vụ hoạt động giải trí/gắn kết nhân viên.**

Không nên định vị là:

> Website cá cược, nhà cái, ăn kèo, đặt cược có thưởng, đổi thưởng.

### 1.2. Nguyên tắc bắt buộc để giảm rủi ro

- Không nạp tiền.
- Không rút tiền.
- Không bán “lá”.
- Không cho user chuyển “lá” cho nhau.
- Không quy đổi “lá” ra tiền, hiện vật hoặc dịch vụ.
- Không thu phí tham gia.
- Không mở cho người ngoài công ty.
- Không quảng bá công khai.
- Không dùng từ “nhà cái”, “ăn tiền”, “rút thưởng”, “đổi thưởng”.
- Có trang thể lệ rõ ràng: “lá là điểm ảo, không có giá trị tài sản”.
- Mọi thay đổi điểm phải có log.

### 1.3. Lưu ý pháp lý

Theo Nghị định 06/2017/NĐ-CP, hoạt động đặt cược bóng đá quốc tế là hoạt động được quản lý theo khuôn khổ riêng về kinh doanh đặt cược. Bộ luật Hình sự Việt Nam cũng có quy định về hành vi đánh bạc trái phép được-thua bằng tiền hoặc hiện vật. Vì vậy, sản phẩm này cần tránh mọi cơ chế có thể bị hiểu là được-thua bằng tài sản.

**Khuyến nghị:** trước khi vận hành chính thức, đặc biệt nếu có quà cuối mùa, nên có người phụ trách pháp chế/HR duyệt thể lệ.

---

## 2. Mục tiêu sản phẩm

### 2.1. Mục tiêu chính

- Tạo sân chơi nội bộ mùa World Cup 2026.
- Cho nhân viên dự đoán kết quả bằng điểm ảo “lá”.
- Tăng tương tác giữa các phòng ban.
- Có bảng xếp hạng cá nhân/phòng ban.
- Admin kiểm soát toàn bộ lịch trận, tỉ lệ, số lá và settlement.

### 2.2. Không thuộc phạm vi

- Không làm cổng cá cược thương mại.
- Không liên kết thanh toán.
- Không tích hợp ví tiền thật.
- Không public ngoài công ty.
- Không tạo cơ chế đổi thưởng tự động.

---

## 3. Nguyên tắc vận hành điểm ảo “lá”

### 3.1. Định nghĩa “lá”

“Lá” là điểm ảo dùng để tham gia dự đoán nội bộ. Lá không có giá trị tiền tệ, không phải tài sản, không được quy đổi thành tiền/quà/dịch vụ.

### 3.2. Cấu hình khuyến nghị

| Tham số | Giá trị gợi ý |
|---|---:|
| Lá khởi đầu mỗi user | 1.000 lá |
| Dự đoán tối thiểu | 10 lá |
| Dự đoán tối đa mỗi vé | 200 lá |
| Dự đoán tối đa mỗi trận | 500 lá |
| Dự đoán tối đa mỗi ngày | 1.000 lá |
| Cho phép âm lá | Không |
| Cho phép chuyển lá | Không |
| Reset theo mùa | Có |
| Cấp thêm lá | Chỉ admin |

### 3.3. Trạng thái số dư

| Trường | Ý nghĩa |
|---|---|
| `available_balance` | Lá khả dụng |
| `locked_balance` | Lá đang nằm trong vé chưa settle |
| `total_balance` | Tổng lá = available + locked |
| `season_profit` | Lãi/lỗ mùa hiện tại |
| `total_staked` | Tổng lá đã dùng để dự đoán |
| `total_returned` | Tổng lá đã được trả về |

---

## 4. Vai trò người dùng

### 4.1. Super Admin

- Quản lý mùa giải.
- Quản lý toàn bộ admin.
- Cấu hình luật chơi.
- Cấp/trừ lá.
- Reset mùa.
- Xem audit log.
- Export báo cáo.
- Khóa/mở hệ thống.

### 4.2. Admin vận hành

- Cập nhật lịch trận.
- Nhập tỉ lệ dự đoán.
- Mở/đóng market.
- Nhập kết quả.
- Preview settlement.
- Xác nhận settlement.
- Void market khi có lỗi.

### 4.3. User

- Đăng nhập.
- Xem lịch trận.
- Xem các lựa chọn dự đoán.
- Dùng lá để dự đoán.
- Xem vé của mình.
- Xem lịch sử lá.
- Xem leaderboard.

### 4.4. Auditor/HR

- Chỉ xem dữ liệu.
- Xem lịch sử phân bổ lá.
- Xem settlement.
- Xem leaderboard.
- Không được sửa điểm/kết quả.

---

## 5. Loại dự đoán/market cần hỗ trợ

### 5.1. Dự đoán tỉ số chính xác

User chọn tỉ số cụ thể, ví dụ:

| Market | Lựa chọn | Tỉ lệ |
|---|---:|---:|
| Cả trận 90 phút | 1-0 | 6.50 |
| Cả trận 90 phút | 1-1 | 5.20 |
| Cả trận 90 phút | 2-1 | 7.00 |
| Hiệp 1 | 0-0 | 2.40 |
| Hiệp 1 | 1-0 | 3.80 |

### 5.2. Dự đoán handicap châu Á

Ví dụ:

| Lựa chọn | Handicap | Tỉ lệ |
|---|---:|---:|
| Đội A | -0.5 | 0.90 |
| Đội B | +0.5 | 0.90 |

Các handicap nên hỗ trợ:

```text
0
±0.25
±0.5
±0.75
±1
±1.25
±1.5
±1.75
±2
±2.25
±2.5
±2.75
±3
```

### 5.3. Dự đoán tài/xỉu

Tài/xỉu dựa trên tổng số bàn thắng của một mốc trận.

Ví dụ:

| Market | Line | Lựa chọn | Tỉ lệ |
|---|---:|---|---:|
| Cả trận 90 phút | 2.5 | Tài | 0.90 |
| Cả trận 90 phút | 2.5 | Xỉu | 1.95 |
| Hiệp 1 | 0.75 | Tài | 1.85 |
| Hiệp 1 | 0.75 | Xỉu | 2.00 |
| Hiệp phụ | 0.5 | Tài | 2.10 |
| Hiệp phụ | 0.5 | Xỉu | 1.75 |

Các line nên hỗ trợ:

```text
0.5
0.75
1
1.25
1.5
1.75
2
2.25
2.5
2.75
3
3.25
3.5
3.75
4
4.25
4.5
```

---

## 6. Luật tính kết quả từng market

## 6.1. Công thức chung

Với tỷ lệ ăn dạng decimal:

```text
Tổng nhận khi thắng đủ = stake × tỷ lệ ăn
Lãi ròng khi thắng đủ = stake × (tỷ lệ ăn - 1)
Thua đủ = mất stake
Hòa kèo/push = hoàn stake
```

Ví dụ:

```text
Đặt 100 lá ăn 0.90
Thắng đủ: nhận 190 lá, lãi ròng 90 lá
Thua đủ: nhận 0 lá, lãi ròng -100 lá
Push: nhận 100 lá, lãi ròng 0 lá
```

---

## 6.2. Tỉ số chính xác

### Luật

- Đúng tỉ số: thắng đủ.
- Sai tỉ số: thua đủ.
- Market bị hủy: hoàn lá.

### Ví dụ

```text
User đặt 100 lá vào tỉ số 2-1 @ 7.00.
Kết quả đúng 2-1: nhận 700 lá, lãi ròng 600 lá.
Kết quả khác: mất 100 lá.
```

---

## 6.3. Handicap châu Á

### Cách tính

Với lựa chọn đội A handicap `h`, lấy:

```text
adjusted_score = goals_A + h - goals_B
```

| adjusted_score | Kết quả |
|---:|---|
| > 0 | Thắng |
| = 0 | Push |
| < 0 | Thua |

Với kèo phần tư như `±0.25`, `±0.75`, `±1.25`, hệ thống tách stake thành 2 nửa.

### Bảng quy đổi kèo phần tư

| Handicap | Tách thành |
|---:|---|
| -0.25 | 0 và -0.5 |
| +0.25 | 0 và +0.5 |
| -0.75 | -0.5 và -1 |
| +0.75 | +0.5 và +1 |
| -1.25 | -1 và -1.5 |
| +1.25 | +1 và +1.5 |
| -1.75 | -1.5 và -2 |
| +1.75 | +1.5 và +2 |

### Ví dụ kèo -0.75

```text
Đội A -0.75 ăn 0.90
Stake: 100 lá
Tách thành:
- 50 lá vào A -0.5
- 50 lá vào A -1
```

| Kết quả trận | Kết quả vé | Tổng nhận |
|---|---|---:|
| A thắng 2 bàn trở lên | Thắng đủ | 190 |
| A thắng đúng 1 bàn | Nửa thắng, nửa hoàn | 145 |
| A hòa/thua | Thua đủ | 0 |

---

## 6.4. Tài/xỉu

### Luật cơ bản

Tính tổng bàn thắng của mốc trận:

```text
total_goals = goals_home + goals_away
```

Với lựa chọn **Tài**:

| Điều kiện | Kết quả |
|---|---|
| total_goals > line | Thắng |
| total_goals = line | Push |
| total_goals < line | Thua |

Với lựa chọn **Xỉu**:

| Điều kiện | Kết quả |
|---|---|
| total_goals < line | Thắng |
| total_goals = line | Push |
| total_goals > line | Thua |

Với line phần tư như `2.25`, `2.75`, hệ thống tách stake thành 2 nửa.

### Bảng quy đổi tài/xỉu phần tư

| Line | Tách thành |
|---:|---|
| 0.75 | 0.5 và 1.0 |
| 1.25 | 1.0 và 1.5 |
| 1.75 | 1.5 và 2.0 |
| 2.25 | 2.0 và 2.5 |
| 2.75 | 2.5 và 3.0 |
| 3.25 | 3.0 và 3.5 |
| 3.75 | 3.5 và 4.0 |

### Ví dụ Tài 2.25

```text
User đặt 100 lá vào Tài 2.25 ăn 0.90.
Hệ thống tách thành:
- 50 lá vào Tài 2.0
- 50 lá vào Tài 2.5
```

| Tổng bàn | Kết quả | Tổng nhận |
|---:|---|---:|
| 3+ | Thắng đủ | 190 |
| 2 | Nửa hòa, nửa thua | 50 |
| 0 hoặc 1 | Thua đủ | 0 |

Giải thích trường hợp tổng bàn = 2:

```text
50 lá Tài 2.0 = push, hoàn 50
50 lá Tài 2.5 = thua, nhận 0
Tổng nhận = 50
Lãi ròng = -50
```

### Ví dụ Xỉu 2.75

```text
User đặt 100 lá vào Xỉu 2.75 ăn 0.90.
Tách thành:
- 50 lá vào Xỉu 2.5
- 50 lá vào Xỉu 3.0
```

| Tổng bàn | Kết quả | Tổng nhận |
|---:|---|---:|
| 0, 1, 2 | Thắng đủ | 190 |
| 3 | Nửa thua, nửa hòa | 50 |
| 4+ | Thua đủ | 0 |

---

## 7. Các mốc trận đấu

Hệ thống cần hỗ trợ nhiều mốc độc lập. Mỗi mốc có thời gian mở/đóng riêng.

| Mốc | Mã | Cách tính |
|---|---|---|
| Cả trận 90 phút | `FULL_TIME` | Hiệp 1 + hiệp 2 + bù giờ, không gồm hiệp phụ/penalty |
| Hiệp 1 | `FIRST_HALF` | Tính đến hết bù giờ hiệp 1 |
| Hiệp 2 độc lập | `SECOND_HALF` | Chỉ số bàn trong hiệp 2 |
| Hiệp phụ | `EXTRA_TIME` | 30 phút hiệp phụ, nếu có |
| Hiệp phụ hiệp 1 | `EXTRA_FIRST_HALF` | 15 phút đầu hiệp phụ |
| Hiệp phụ hiệp 2 | `EXTRA_SECOND_HALF` | 15 phút sau hiệp phụ |
| Penalty | `PENALTY` | Loạt sút luân lưu, nếu có |

### 7.1. Quy tắc đóng/mở

Mỗi mốc có:

```text
open_at
close_at
settle_at
status
```

Trạng thái:

| Status | Ý nghĩa |
|---|---|
| `DRAFT` | Admin đang tạo |
| `OPEN` | User được dự đoán |
| `LOCKED` | Quá giờ, không được dự đoán |
| `SETTLING` | Admin đang nhập kết quả |
| `SETTLED` | Đã tính lá |
| `VOIDED` | Hủy market, hoàn lá |
| `CANCELLED` | Trận hủy |

Quy tắc:

```text
Nếu current_time >= close_at:
- Không cho đặt mới
- Không cho sửa vé
- Không cho hủy vé
```

---

## 8. Luồng nghiệp vụ

### 8.1. Luồng admin chuẩn bị trận

```text
1. Import lịch World Cup 2026 cố định.
2. Admin chọn trận cần mở dự đoán.
3. Admin tạo market: tỉ số, handicap, tài/xỉu.
4. Admin nhập tỷ lệ ăn cho từng lựa chọn.
5. Admin đặt open_at và close_at.
6. Admin publish market.
7. Hệ thống hiển thị trận cho user.
```

### 8.2. Luồng user dự đoán

```text
1. User login.
2. Xem dashboard.
3. Chọn trận.
4. Chọn mốc: cả trận, hiệp 1, hiệp 2, hiệp phụ, penalty.
5. Chọn market: tỉ số, handicap, tài/xỉu.
6. Nhập số lá.
7. Xác nhận.
8. Hệ thống khóa stake vào vé.
```

### 8.3. Luồng settlement

```text
1. Trận/mốc kết thúc.
2. Admin nhập tỉ số thực tế.
3. Hệ thống tính thử settlement.
4. Admin xem preview.
5. Admin xác nhận.
6. Hệ thống cập nhật ledger.
7. Leaderboard cập nhật.
```

---

## 9. Cơ chế ví lá & ledger

### 9.1. Vì sao cần ledger

Không nên chỉ lưu `balance` vì dễ sai và khó truy vết. Mọi thay đổi lá cần ghi thành giao dịch.

### 9.2. Loại giao dịch ledger

| Loại | Ý nghĩa |
|---|---|
| `ADMIN_GRANT` | Admin cấp lá |
| `ADMIN_DEDUCT` | Admin trừ lá |
| `BET_PLACED` | User đặt lá, chuyển từ available sang locked |
| `BET_WON` | Vé thắng, cộng lá nhận về |
| `BET_LOST` | Vé thua, giải phóng locked về 0 |
| `BET_PUSH` | Hòa kèo, hoàn lá |
| `BET_HALF_WON` | Nửa thắng nửa hoàn |
| `BET_HALF_LOST` | Nửa thua nửa hoàn |
| `MARKET_VOID` | Hủy market, hoàn lá |
| `CORRECTION` | Điều chỉnh do lỗi nhập liệu |
| `SEASON_RESET` | Reset mùa giải |

### 9.3. Nguyên tắc cập nhật ví

```text
Không sửa trực tiếp balance.
Mọi thay đổi phải đi qua ledger.
Mỗi ledger entry phải có actor, reason, timestamp.
```

---

## 10. Leaderboard & game hóa

### 10.1. Leaderboard chính

| Hạng | User | Phòng ban | Lá hiện có | Lãi/lỗ | ROI |
|---:|---|---|---:|---:|---:|
| 1 | A | Sales | 3.200 | +2.200 | 42% |
| 2 | B | Marketing | 2.750 | +1.750 | 35% |

### 10.2. Bảng phụ

| Bảng | Ý nghĩa |
|---|---|
| Cao thủ tỉ số | Đúng tỉ số nhiều nhất |
| Cao thủ handicap | ROI handicap cao nhất |
| Cao thủ tài/xỉu | ROI tài/xỉu cao nhất |
| Người chơi ổn định | Tỉ lệ thắng cao, số vé đủ lớn |
| Risk taker | Tổng lá đặt cao nhất |
| Comeback | Tăng hạng mạnh nhất tuần |
| Phòng ban mạnh nhất | Tổng điểm theo phòng ban |

### 10.3. Công thức ROI

```text
ROI = lãi_ròng / tổng_lá_đã_đặt × 100%
```

### 10.4. Huy hiệu gợi ý

| Badge | Điều kiện |
|---|---|
| Oracle | Đúng tỉ số 3 lần |
| Ice Cold | Chuỗi thắng 5 vé |
| Comeback King | Tăng 20 hạng trong tuần |
| Safe Hands | 10 vé liên tiếp không âm ROI |
| Over Master | Thắng tài/xỉu 5 lần |
| Handicap Brain | Thắng handicap 5 lần |

---

## 11. Quản trị dữ liệu World Cup 2026 cố định

### 11.1. Nguyên tắc

Vì sản phẩm phục vụ World Cup 2026, hệ thống nên seed sẵn lịch thi đấu chính thức/đã công bố. Admin không nên tạo trận tùy ý trong mùa WC nếu không cần.

Cấu hình:

```text
competition = FIFA World Cup 2026
season = WC2026
match_source = OFFICIAL_SEED
allow_manual_match_creation = false by default
```

### 11.2. Group stage

Các trận vòng bảng có đội cụ thể, ngày giờ và địa điểm cụ thể. Admin chỉ cần mở/đóng market và nhập tỷ lệ ăn.

### 11.3. Knockout stage

Các trận knockout có nhánh cố định theo mã trận, ví dụ:

```text
Match 73: Group A runners-up vs Group B runners-up
Match 89: Match 74 winners vs Match 77 winners
Match 104: Match 101 winners vs Match 102 winners
```

Sau khi có kết quả vòng bảng/knockout, admin cập nhật đội thực tế vào các placeholder.

### 11.4. Cấu hình auto-create market theo lịch

Gợi ý:

```text
Tự tạo market trước kickoff 48 giờ.
Tự đóng market cả trận trước kickoff 5 phút.
Tự đóng market hiệp 1 trước kickoff 5 phút.
Tự mở market hiệp 2 sau khi admin xác nhận tỉ số hiệp 1.
Tự mở market hiệp phụ nếu trận knockout hòa sau 90 phút.
Tự mở market penalty nếu trận vẫn hòa sau hiệp phụ.
```

---

## 12. Lịch World Cup 2026 seed theo giờ Việt Nam

**Nguồn lịch:** FIFA/Sky Sports, thời gian nguồn là giờ UK; bảng dưới đã quy đổi sang giờ Việt Nam `Asia/Ho_Chi_Minh` bằng cách cộng 6 giờ trong giai đoạn UK đang dùng BST. Cần đối soát lại với nguồn FIFA trước khi import production.

| Mã trận | Giờ VN | Vòng/Bảng | Trận | Địa điểm | Giờ nguồn |
|---|---:|---|---|---|---|
| WC26-M001 | 2026-06-12 02:00 | Group A | Mexico vs South Africa | Mexico City, Mexico | 2026-06-11 20:00 UK |
| WC26-M002 | 2026-06-12 09:00 | Group A | South Korea vs Czech Republic | Zapopan, Mexico | 2026-06-12 03:00 UK |
| WC26-M003 | 2026-06-13 02:00 | Group B | Canada vs Bosnia & Herzegovina | Toronto, Canada | 2026-06-12 20:00 UK |
| WC26-M004 | 2026-06-13 08:00 | Group D | USA vs Paraguay | Los Angeles, USA | 2026-06-13 02:00 UK |
| WC26-M005 | 2026-06-14 02:00 | Group B | Qatar vs Switzerland | Santa Clara, USA | 2026-06-13 20:00 UK |
| WC26-M006 | 2026-06-14 05:00 | Group C | Brazil vs Morocco | New Jersey, USA | 2026-06-13 23:00 UK |
| WC26-M007 | 2026-06-14 08:00 | Group C | Haiti vs Scotland | Foxborough, USA | 2026-06-14 02:00 UK |
| WC26-M008 | 2026-06-14 11:00 | Group D | Australia vs Turkey | Vancouver, Canada | 2026-06-14 05:00 UK |
| WC26-M009 | 2026-06-15 00:00 | Group E | Germany vs Curacao | Houston, USA | 2026-06-14 18:00 UK |
| WC26-M010 | 2026-06-15 03:00 | Group F | Netherlands vs Japan | Arlington, USA | 2026-06-14 21:00 UK |
| WC26-M011 | 2026-06-15 06:00 | Group E | Ivory Coast vs Ecuador | Philadelphia, USA | 2026-06-15 00:00 UK |
| WC26-M012 | 2026-06-15 09:00 | Group F | Sweden vs Tunisia | Guadalupe, Mexico | 2026-06-15 03:00 UK |
| WC26-M013 | 2026-06-15 23:00 | Group H | Spain vs Cape Verde | Atlanta, USA | 2026-06-15 17:00 UK |
| WC26-M014 | 2026-06-16 02:00 | Group G | Belgium vs Egypt | Seattle, USA | 2026-06-15 20:00 UK |
| WC26-M015 | 2026-06-16 05:00 | Group H | Saudi Arabia vs Uruguay | Miami, USA | 2026-06-15 23:00 UK |
| WC26-M016 | 2026-06-16 08:00 | Group G | Iran vs New Zealand | Los Angeles, USA | 2026-06-16 02:00 UK |
| WC26-M017 | 2026-06-17 02:00 | Group I | France vs Senegal | New Jersey, USA | 2026-06-16 20:00 UK |
| WC26-M018 | 2026-06-17 05:00 | Group I | Iraq vs Norway | Foxborough, USA | 2026-06-16 23:00 UK |
| WC26-M019 | 2026-06-17 08:00 | Group J | Argentina vs Algeria | Kansas City, USA | 2026-06-17 02:00 UK |
| WC26-M020 | 2026-06-17 11:00 | Group J | Austria vs Jordan | Santa Clara, USA | 2026-06-17 05:00 UK |
| WC26-M021 | 2026-06-18 00:00 | Group K | Portugal vs DR Congo | Houston, USA | 2026-06-17 18:00 UK |
| WC26-M022 | 2026-06-18 03:00 | Group L | England vs Croatia | Arlington, USA | 2026-06-17 21:00 UK |
| WC26-M023 | 2026-06-18 06:00 | Group L | Ghana vs Panama | Toronto, Canada | 2026-06-18 00:00 UK |
| WC26-M024 | 2026-06-18 09:00 | Group K | Uzbekistan vs Colombia | Mexico City, Mexico | 2026-06-18 03:00 UK |
| WC26-M025 | 2026-06-18 23:00 | Group A | Czech Republic vs South Africa | Atlanta, USA | 2026-06-18 17:00 UK |
| WC26-M026 | 2026-06-19 02:00 | Group B | Switzerland vs Bosnia & Herzegovina | Los Angeles, USA | 2026-06-18 20:00 UK |
| WC26-M027 | 2026-06-19 05:00 | Group B | Canada vs Qatar | Vancouver, Canada | 2026-06-18 23:00 UK |
| WC26-M028 | 2026-06-19 08:00 | Group A | Mexico vs South Korea | Zapopan, Mexico | 2026-06-19 02:00 UK |
| WC26-M029 | 2026-06-20 02:00 | Group D | USA vs Australia | Seattle, USA | 2026-06-19 20:00 UK |
| WC26-M030 | 2026-06-20 05:00 | Group C | Scotland vs Morocco | Foxborough, USA | 2026-06-19 23:00 UK |
| WC26-M031 | 2026-06-20 07:30 | Group C | Brazil vs Haiti | Philadelphia, USA | 2026-06-20 01:30 UK |
| WC26-M032 | 2026-06-20 10:00 | Group D | Turkey vs Paraguay | Santa Clara, USA | 2026-06-20 04:00 UK |
| WC26-M033 | 2026-06-21 00:00 | Group F | Netherlands vs Sweden | Houston, USA | 2026-06-20 18:00 UK |
| WC26-M034 | 2026-06-21 03:00 | Group E | Germany vs Ivory Coast | Toronto, Canada | 2026-06-20 21:00 UK |
| WC26-M035 | 2026-06-21 07:00 | Group E | Ecuador vs Curacao | Kansas City, USA | 2026-06-21 01:00 UK |
| WC26-M036 | 2026-06-21 11:00 | Group F | Tunisia vs Japan | Guadalupe, Mexico | 2026-06-21 05:00 UK |
| WC26-M037 | 2026-06-21 23:00 | Group H | Spain vs Saudi Arabia | Atlanta, USA | 2026-06-21 17:00 UK |
| WC26-M038 | 2026-06-22 02:00 | Group G | Belgium vs Iran | Los Angeles, USA | 2026-06-21 20:00 UK |
| WC26-M039 | 2026-06-22 05:00 | Group H | Uruguay vs Cape Verde | Miami, USA | 2026-06-21 23:00 UK |
| WC26-M040 | 2026-06-22 08:00 | Group G | New Zealand vs Egypt | Vancouver, Canada | 2026-06-22 02:00 UK |
| WC26-M041 | 2026-06-23 00:00 | Group J | Argentina vs Austria | Arlington, USA | 2026-06-22 18:00 UK |
| WC26-M042 | 2026-06-23 04:00 | Group I | France vs Iraq | Philadelphia, USA | 2026-06-22 22:00 UK |
| WC26-M043 | 2026-06-23 07:00 | Group I | Norway vs Senegal | Toronto, Canada | 2026-06-23 01:00 UK |
| WC26-M044 | 2026-06-23 10:00 | Group J | Jordan vs Algeria | Santa Clara, USA | 2026-06-23 04:00 UK |
| WC26-M045 | 2026-06-24 00:00 | Group K | Portugal vs Uzbekistan | Houston, USA | 2026-06-23 18:00 UK |
| WC26-M046 | 2026-06-24 03:00 | Group L | England vs Ghana | Foxborough, USA | 2026-06-23 21:00 UK |
| WC26-M047 | 2026-06-24 06:00 | Group L | Panama vs Croatia | Foxborough, USA | 2026-06-24 00:00 UK |
| WC26-M048 | 2026-06-24 09:00 | Group K | Colombia vs DR Congo | Zapopan, Mexico | 2026-06-24 03:00 UK |
| WC26-M049 | 2026-06-25 02:00 | Group B | Switzerland vs Canada | Vancouver, Canada | 2026-06-24 20:00 UK |
| WC26-M050 | 2026-06-25 02:00 | Group B | Bosnia & Herzegovina vs Qatar | Seattle, USA | 2026-06-24 20:00 UK |
| WC26-M051 | 2026-06-25 05:00 | Group C | Morocco vs Haiti | Atlanta, USA | 2026-06-24 23:00 UK |
| WC26-M052 | 2026-06-25 05:00 | Group C | Scotland vs Brazil | Miami, USA | 2026-06-24 23:00 UK |
| WC26-M053 | 2026-06-25 08:00 | Group A | South Africa vs South Korea | Guadalupe, Mexico | 2026-06-25 02:00 UK |
| WC26-M054 | 2026-06-25 08:00 | Group A | Czech Republic vs Mexico | Mexico City, Mexico | 2026-06-25 02:00 UK |
| WC26-M055 | 2026-06-26 03:00 | Group E | Curacao vs Ivory Coast | Philadelphia, USA | 2026-06-25 21:00 UK |
| WC26-M056 | 2026-06-26 03:00 | Group E | Ecuador vs Germany | New Jersey, USA | 2026-06-25 21:00 UK |
| WC26-M057 | 2026-06-26 06:00 | Group F | Tunisia vs Netherlands | Kansas City, USA | 2026-06-26 00:00 UK |
| WC26-M058 | 2026-06-26 06:00 | Group F | Japan vs Sweden | Arlington, USA | 2026-06-26 00:00 UK |
| WC26-M059 | 2026-06-26 09:00 | Group D | Turkey vs USA | Los Angeles, USA | 2026-06-26 03:00 UK |
| WC26-M060 | 2026-06-26 09:00 | Group D | Paraguay vs Australia | Santa Clara, USA | 2026-06-26 03:00 UK |
| WC26-M061 | 2026-06-27 02:00 | Group I | Norway vs France | Foxborough, USA | 2026-06-26 20:00 UK |
| WC26-M062 | 2026-06-27 02:00 | Group I | Senegal vs Iraq | Toronto, Canada | 2026-06-26 20:00 UK |
| WC26-M063 | 2026-06-27 07:00 | Group H | Cape Verde vs Saudi Arabia | Houston, USA | 2026-06-27 01:00 UK |
| WC26-M064 | 2026-06-27 07:00 | Group H | Uruguay vs Spain | Zapopan, Mexico | 2026-06-27 01:00 UK |
| WC26-M065 | 2026-06-27 10:00 | Group G | New Zealand vs Belgium | Vancouver, Canada | 2026-06-27 04:00 UK |
| WC26-M066 | 2026-06-27 10:00 | Group G | Egypt vs Iran | Seattle, USA | 2026-06-27 04:00 UK |
| WC26-M067 | 2026-06-28 04:00 | Group L | Panama vs England | New Jersey, USA | 2026-06-27 22:00 UK |
| WC26-M068 | 2026-06-28 04:00 | Group L | Croatia vs Ghana | Philadelphia, USA | 2026-06-27 22:00 UK |
| WC26-M069 | 2026-06-28 06:30 | Group K | Colombia vs Portugal | Miami, USA | 2026-06-28 00:30 UK |
| WC26-M070 | 2026-06-28 06:30 | Group K | DR Congo vs Uzbekistan | Atlanta, USA | 2026-06-28 00:30 UK |
| WC26-M071 | 2026-06-28 09:00 | Group J | Algeria vs Austria | Kansas City, USA | 2026-06-28 03:00 UK |
| WC26-M072 | 2026-06-28 09:00 | Group J | Jordan vs Argentina | Arlington, USA | 2026-06-28 03:00 UK |
| WC26-M073 | 2026-06-29 02:00 | Round of 32 - Match 73 | Group A runners-up vs Group B runners-up | Los Angeles, USA | 2026-06-28 20:00 UK |
| WC26-M074 | 2026-06-30 00:00 | Round of 32 - Match 76 | Group C winners vs Group F runners-up | Houston, USA | 2026-06-29 18:00 UK |
| WC26-M075 | 2026-06-30 03:30 | Round of 32 - Match 74 | Group E winners vs Group A/B/C/D/F third place | Foxborough, USA | 2026-06-29 21:30 UK |
| WC26-M076 | 2026-06-30 08:00 | Round of 32 - Match 75 | Group F winners vs Group C runners-up | Guadalupe, Mexico | 2026-06-30 02:00 UK |
| WC26-M077 | 2026-07-01 00:00 | Round of 32 - Match 78 | Group E runners-up vs Group I runners-up | Arlington, USA | 2026-06-30 18:00 UK |
| WC26-M078 | 2026-07-01 04:00 | Round of 32 - Match 77 | Group I winners vs Group C/D/F/G/H third place | New Jersey, USA | 2026-06-30 22:00 UK |
| WC26-M079 | 2026-07-01 08:00 | Round of 32 - Match 79 | Group A winners vs Group C/E/F/H/I third place | Mexico City, Mexico | 2026-07-01 02:00 UK |
| WC26-M080 | 2026-07-01 23:00 | Round of 32 - Match 80 | Group L winners vs Group E/H/I/J/K third place | Atlanta, USA | 2026-07-01 17:00 UK |
| WC26-M081 | 2026-07-02 03:00 | Round of 32 - Match 82 | Group G winners vs Group A/E/H/I/J third place | Seattle, USA | 2026-07-01 21:00 UK |
| WC26-M082 | 2026-07-02 07:00 | Round of 32 - Match 81 | Group D winners vs Group B/E/F/I/J third place | Santa Clara, USA | 2026-07-02 01:00 UK |
| WC26-M083 | 2026-07-03 02:00 | Round of 32 - Match 84 | Group H winners vs Group J runners-up | Los Angeles, USA | 2026-07-02 20:00 UK |
| WC26-M084 | 2026-07-03 06:00 | Round of 32 - Match 83 | Group K runners-up vs Group L runners-up | Toronto, Canada | 2026-07-03 00:00 UK |
| WC26-M085 | 2026-07-03 10:00 | Round of 32 - Match 85 | Group B winners vs Group E/F/G/I/J third place | Vancouver, Canada | 2026-07-03 04:00 UK |
| WC26-M086 | 2026-07-04 01:00 | Round of 32 - Match 88 | Group D runners-up vs Group G runners-up | Arlington, USA | 2026-07-03 19:00 UK |
| WC26-M087 | 2026-07-04 05:00 | Round of 32 - Match 86 | Group J winners vs Group H runners-up | Miami, USA | 2026-07-03 23:00 UK |
| WC26-M088 | 2026-07-04 08:30 | Round of 32 - Match 87 | Group K winners vs Group D/E/I/J/L third place | Kansas City, USA | 2026-07-04 02:30 UK |
| WC26-M089 | 2026-07-05 00:00 | Round of 16 - Match 90 | Match 73 winners vs Match 75 winners | Houston, USA | 2026-07-04 18:00 UK |
| WC26-M090 | 2026-07-05 04:00 | Round of 16 - Match 89 | Match 74 winners vs Match 77 winners | Philadelphia, USA | 2026-07-04 22:00 UK |
| WC26-M091 | 2026-07-06 03:00 | Round of 16 - Match 91 | Match 76 winners vs Match 78 winners | New Jersey, USA | 2026-07-05 21:00 UK |
| WC26-M092 | 2026-07-06 07:00 | Round of 16 - Match 92 | Match 79 winners vs Match 80 winners | Mexico City, Mexico | 2026-07-06 01:00 UK |
| WC26-M093 | 2026-07-07 02:00 | Round of 16 - Match 93 | Match 83 winners vs Match 84 winners | Arlington, USA | 2026-07-06 20:00 UK |
| WC26-M094 | 2026-07-07 07:00 | Round of 16 - Match 94 | Match 81 winners vs Match 82 winners | Seattle, USA | 2026-07-07 01:00 UK |
| WC26-M095 | 2026-07-07 23:00 | Round of 16 - Match 95 | Match 86 winners vs Match 88 winners | Atlanta, USA | 2026-07-07 17:00 UK |
| WC26-M096 | 2026-07-08 03:00 | Round of 16 - Match 96 | Match 85 winners vs Match 87 winners | Vancouver, Canada | 2026-07-07 21:00 UK |
| WC26-M097 | 2026-07-10 03:00 | Quarter-final - Match 97 | Match 89 winners vs Match 90 winners | Foxborough, USA | 2026-07-09 21:00 UK |
| WC26-M098 | 2026-07-11 02:00 | Quarter-final - Match 98 | Match 93 winners vs Match 94 winners | Los Angeles, USA | 2026-07-10 20:00 UK |
| WC26-M099 | 2026-07-12 04:00 | Quarter-final - Match 99 | Match 91 winners vs Match 92 winners | Miami, USA | 2026-07-11 22:00 UK |
| WC26-M100 | 2026-07-12 08:00 | Quarter-final - Match 100 | Match 95 winners vs Match 96 winners | Kansas City, USA | 2026-07-12 02:00 UK |
| WC26-M101 | 2026-07-15 02:00 | Semi-final - Match 101 | Match 97 winners vs Match 98 winners | Arlington, USA | 2026-07-14 20:00 UK |
| WC26-M102 | 2026-07-16 02:00 | Semi-final - Match 102 | Match 99 winners vs Match 100 winners | Atlanta, USA | 2026-07-15 20:00 UK |
| WC26-M103 | 2026-07-19 04:00 | Third Place Playoff - Match 103 | Match 101 losers vs Match 102 losers | Miami, USA | 2026-07-18 22:00 UK |
| WC26-M104 | 2026-07-20 02:00 | Final - Match 104 | Match 101 winners vs Match 102 winners | New Jersey, USA | 2026-07-19 20:00 UK |


---

## 13. Mô hình dữ liệu đề xuất

### 13.1. Nhóm user & phân quyền

```sql
users (
  id,
  employee_code,
  name,
  email,
  password_hash,
  department_id,
  status,
  created_at,
  updated_at
)

roles (
  id,
  code,
  name
)

user_roles (
  user_id,
  role_id
)

departments (
  id,
  name
)
```

### 13.2. Nhóm ví lá

```sql
wallets (
  id,
  user_id,
  season_id,
  available_balance,
  locked_balance,
  total_staked,
  total_returned,
  season_profit,
  updated_at
)

wallet_ledger (
  id,
  wallet_id,
  user_id,
  type,
  amount,
  balance_before,
  balance_after,
  related_bet_id,
  related_market_id,
  actor_user_id,
  reason,
  created_at
)
```

### 13.3. Nhóm giải/trận

```sql
competitions (
  id,
  name,
  code
)

seasons (
  id,
  competition_id,
  name,
  code,
  start_at,
  end_at,
  status
)

matches (
  id,
  season_id,
  source_code,
  match_number,
  stage,
  group_code,
  home_team,
  away_team,
  home_placeholder,
  away_placeholder,
  venue,
  kickoff_at,
  timezone,
  status,
  is_seeded,
  created_at,
  updated_at
)
```

### 13.4. Nhóm mốc trận & kết quả

```sql
match_periods (
  id,
  match_id,
  period_type,
  open_at,
  close_at,
  status,
  home_score,
  away_score,
  settled_at
)
```

### 13.5. Nhóm market/tỷ lệ ăn

```sql
markets (
  id,
  match_id,
  period_id,
  market_type,
  name,
  status,
  open_at,
  close_at,
  created_by,
  created_at,
  updated_at
)

market_outcomes (
  id,
  market_id,
  label,
  selection_type,
  tỷ lệ ăn,
  handicap_value,
  total_line,
  score_home,
  score_away,
  status,
  created_at,
  updated_at
)

tỷ lệ ăn_versions (
  id,
  market_outcome_id,
  old_tỷ lệ ăn,
  new_tỷ lệ ăn,
  changed_by,
  reason,
  created_at
)
```

### 13.6. Nhóm vé dự đoán

```sql
bets (
  id,
  user_id,
  wallet_id,
  match_id,
  period_id,
  market_id,
  outcome_id,
  stake,
  profit_rate_snapshot,
  handicap_snapshot,
  total_line_snapshot,
  status,
  potential_return,
  actual_return,
  profit,
  placed_at,
  settled_at
)
```

### 13.7. Audit log

```sql
audit_logs (
  id,
  actor_user_id,
  action,
  entity_type,
  entity_id,
  before_json,
  after_json,
  ip_address,
  user_agent,
  created_at
)
```

---

## 14. API/phân hệ kỹ thuật

### 14.1. Auth

```text
POST /auth/login
POST /auth/logout
GET  /me
```

### 14.2. User

```text
GET  /users
POST /admin/users
PATCH /admin/users/:id
PATCH /admin/users/:id/status
```

### 14.3. Wallet

```text
GET  /me/wallet
GET  /me/wallet/ledger
POST /admin/wallets/grant
POST /admin/wallets/deduct
```

### 14.4. Matches

```text
GET  /matches
GET  /matches/:id
POST /admin/matches/import-wc2026-seed
PATCH /admin/matches/:id
```

### 14.5. Markets

```text
GET  /matches/:id/markets
POST /admin/markets
PATCH /admin/markets/:id
POST /admin/markets/:id/publish
POST /admin/markets/:id/void
```

### 14.6. Bets

```text
POST /bets
GET  /me/bets
GET  /me/bets/:id
```

### 14.7. Settlement

```text
POST /admin/periods/:id/result
POST /admin/markets/:id/settlement-preview
POST /admin/markets/:id/settle
POST /admin/markets/:id/resettle
```

### 14.8. Leaderboard

```text
GET /leaderboards/season
GET /leaderboards/week
GET /leaderboards/department
GET /leaderboards/market-type
```

---

## 15. Bảo mật, chống gian lận, audit log

### 15.1. Khóa tỷ lệ ăn theo vé

Khi user đặt, vé phải lưu snapshot:

```text
profit_rate_snapshot
handicap_snapshot
total_line_snapshot
market_type
period_type
placed_at
close_at_snapshot
```

Nếu admin đổi tỷ lệ ăn sau đó, vé cũ không đổi.

### 15.2. Không sửa âm thầm

Mọi thao tác nhạy cảm phải có log:

- Đổi tỷ lệ ăn.
- Void market.
- Nhập kết quả.
- Sửa kết quả.
- Cấp/trừ lá.
- Reset mùa.
- Khóa/mở user.

### 15.3. Settlement 2 bước

```text
1. Admin nhập kết quả.
2. Hệ thống preview settlement.
3. Admin xác nhận settle.
4. Hệ thống ghi ledger.
```

Với bán kết/chung kết hoặc nếu có phần thưởng nội bộ, nên yêu cầu 2 admin duyệt.

### 15.4. Giới hạn thao tác user

- Không cho đặt quá số lá khả dụng.
- Không cho đặt sau `close_at`.
- Không cho sửa vé sau khi xác nhận.
- Không cho hủy vé nếu market đã locked.
- Không cho tạo nhiều tài khoản nếu không qua admin.

### 15.5. Chống lỗi race condition

Khi đặt vé:

```text
BEGIN TRANSACTION
SELECT wallet FOR UPDATE
Kiểm tra available_balance >= stake
Trừ available_balance
Cộng locked_balance
Tạo bet
Tạo ledger
COMMIT
```

---

## 16. Màn hình chức năng

### 16.1. User

| Màn hình | Nội dung |
|---|---|
| Dashboard | Số lá, trận sắp diễn ra, vé đang mở |
| Lịch WC2026 | Danh sách trận theo ngày/bảng/vòng |
| Chi tiết trận | Market tỉ số, handicap, tài/xỉu |
| Đặt lá | Nhập stake, xác nhận |
| Vé của tôi | Pending, won, lost, push, void |
| Lịch sử lá | Ledger cá nhân |
| Leaderboard | Cá nhân, phòng ban, tuần, mùa |
| Thể lệ | Quy định điểm ảo và luật chơi |

### 16.2. Admin

| Màn hình | Nội dung |
|---|---|
| Quản lý user | Tạo user, cấp lá, khóa user |
| Quản lý lịch WC2026 | Xem lịch seed, cập nhật đội knockout |
| Quản lý market | Tạo/sửa/publish tỷ lệ ăn |
| Settlement | Nhập kết quả, preview, settle |
| Ledger | Lịch sử biến động lá |
| Audit log | Toàn bộ thao tác nhạy cảm |
| Báo cáo | Export CSV/Excel |
| Cấu hình | Stake limit, mùa giải, luật đóng/mở |

---

## 17. Roadmap triển khai

### 17.1. Giai đoạn 1 — MVP

Mục tiêu: chạy được mùa World Cup 2026 nội bộ.

Có:

- Login.
- Admin tạo/cấp user.
- Admin cấp lá.
- Seed lịch WC2026.
- Tạo market tỉ số.
- Tạo market handicap.
- Tạo market tài/xỉu.
- User đặt lá.
- Tự khóa theo giờ.
- Admin nhập kết quả.
- Settlement tự động.
- Leaderboard.
- Audit log cơ bản.

### 17.2. Giai đoạn 2 — Game hóa

- Badge.
- Chuỗi thắng/thua.
- Leaderboard phòng ban.
- Weekly challenge.
- Dự đoán miễn phí không mất lá.
- Notification qua email/Slack/Zalo OA nội bộ.

### 17.3. Giai đoạn 3 — Vận hành nâng cao

- Tỷ lệ ăn versioning đầy đủ.
- Dual approval khi settle.
- Re-settlement có kiểm soát.
- Dashboard analytics.
- Export báo cáo nâng cao.
- Backup tự động.
- WebSocket realtime.

---

## 18. Thể lệ mẫu đưa lên website

```text
1. Lá là điểm ảo dùng trong trò chơi dự đoán bóng đá nội bộ.
2. Lá không có giá trị quy đổi thành tiền, hiện vật hoặc dịch vụ.
3. Người chơi không được mua, bán hoặc chuyển nhượng lá.
4. Mỗi user được admin cấp số lá theo từng mùa giải.
5. Mỗi dự đoán sẽ bị khóa sau thời gian đóng market.
6. Sau khi market đóng, user không thể sửa hoặc hủy dự đoán.
7. Tỉ lệ tại thời điểm đặt sẽ được lưu cố định cho vé đó.
8. Nếu trận bị hủy hoặc market bị void, hệ thống hoàn lá.
9. Kết quả do ban tổ chức nhập dựa trên nguồn kết quả công khai.
10. Ban tổ chức có quyền điều chỉnh lỗi kỹ thuật nhưng mọi điều chỉnh đều được ghi log.
11. Bảng xếp hạng chỉ phục vụ mục đích giải trí và gắn kết nội bộ.
12. Website chỉ dành cho nhân viên nội bộ, không mở cho bên ngoài.
```

---

## 19. Nguồn tham khảo

- FIFA — World Cup 2026 match schedule/fixtures: https://www.fifa.com/en/tournaments/mens/worldcup/canadamexicousa2026/articles/match-schedule-fixtures-results-teams-stadiums
- FIFA — Scores & Fixtures: https://www.fifa.com/en/tournaments/mens/worldcup/canadamexicousa2026/scores-fixtures
- Sky Sports — Full World Cup 2026 fixture schedule and UK kick-off times: https://www.skysports.com/football/news/12098/13481245/world-cup-2026-fixture-schedule-and-uk-kick-off-times-day-by-day-breakdown-of-all-104-matches-including-england-scotland
- Nghị định 06/2017/NĐ-CP về kinh doanh đặt cược đua ngựa, đua chó và bóng đá quốc tế: https://luatvietnam.vn/thuong-mai/nghi-dinh-06-2017-nd-cp-chinh-phu-112061-d1.html
- Điều 321 Bộ luật Hình sự — tội đánh bạc: https://xaydungchinhsach.chinhphu.vn/khi-nao-danh-bac-se-bi-xu-ly-hinh-su-che-giau-viec-danh-bac-se-bi-phat-bao-nhieu-tien-119240207153645943.htm

---

## Phụ lục A — Stack kỹ thuật khuyến nghị

### Option nhanh cho MVP

```text
Laravel + Filament + PostgreSQL + Redis
```

Lý do:

- Làm admin panel nhanh.
- Quản lý user, bảng dữ liệu, form nhập tỷ lệ ăn thuận tiện.
- Dễ export Excel/CSV.
- Phù hợp hệ thống nội bộ.

### Option hiện đại, realtime tốt hơn

```text
Next.js + NestJS + PostgreSQL + Redis + WebSocket
```

Lý do:

- Frontend linh hoạt.
- Backend rõ module.
- Tốt nếu có nhiều user đặt sát giờ.

---

## Phụ lục B — Pseudocode settlement tài/xỉu

```pseudo
function settle_total_bet(selection, line, total_goals, stake, tỷ lệ ăn):
    split_lines = split_quarter_line(line)
    split_stake = stake / len(split_lines)
    total_return = 0

    for each sub_line in split_lines:
        if selection == "OVER":
            if total_goals > sub_line:
                total_return += split_stake * tỷ lệ ăn
            else if total_goals == sub_line:
                total_return += split_stake
            else:
                total_return += 0

        if selection == "UNDER":
            if total_goals < sub_line:
                total_return += split_stake * tỷ lệ ăn
            else if total_goals == sub_line:
                total_return += split_stake
            else:
                total_return += 0

    profit = total_return - stake
    return total_return, profit
```

---

## Phụ lục C — Pseudocode settlement handicap

```pseudo
function settle_handicap_bet(team_goals, opponent_goals, handicap, stake, tỷ lệ ăn):
    split_handicaps = split_quarter_handicap(handicap)
    split_stake = stake / len(split_handicaps)
    total_return = 0

    for each h in split_handicaps:
        adjusted_score = team_goals + h - opponent_goals

        if adjusted_score > 0:
            total_return += split_stake * tỷ lệ ăn
        else if adjusted_score == 0:
            total_return += split_stake
        else:
            total_return += 0

    profit = total_return - stake
    return total_return, profit
```