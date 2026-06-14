---
title: "PRD - Sản phẩm Dự đoán bóng đá nội bộ bằng Lá"
project: "Du Doan La"
version: "1.0"
status: "draft"
owner: "Product / Internal Operations"
last_updated: "2026-06-11"
---

# PRD - Sản phẩm Dự đoán bóng đá nội bộ bằng Lá

## 1. Tóm tắt sản phẩm

**Dự đoán Lá** là website nội bộ cho nhân sự công ty tham gia dự đoán kết quả bóng đá bằng điểm ảo gọi là **lá**. Hệ thống dùng cho hoạt động giải trí, gắn kết nội bộ và bảng xếp hạng cá nhân. Lá không có giá trị quy đổi thành tiền, hiện vật, dịch vụ hoặc quyền lợi tài chính.

Sản phẩm hỗ trợ các hình thức dự đoán chính:

1. Dự đoán tỉ số chính xác.
2. Dự đoán handicap châu Á.
3. Dự đoán tài/xỉu.
4. Các mốc trận: cả trận 90 phút, hiệp 1, hiệp 2, hiệp phụ, penalty nếu có.

Giai đoạn đầu cố định lịch trận theo **World Cup 2026** để giảm khối lượng nhập liệu và kiểm soát vận hành.

---

## 2. Mục tiêu sản phẩm

### 2.1. Mục tiêu chính

- Tạo một game dự đoán nội bộ, dễ chơi, có tính cạnh tranh lành mạnh.
- Cho phép admin vận hành trận đấu, tỉ lệ, kết quả và bảng xếp hạng.
- Tự động cộng/trừ lá theo rules đã định nghĩa.
- Ghi nhận đầy đủ lịch sử ví, lịch sử dự đoán và audit log.
- Hạn chế tối đa rủi ro pháp lý bằng cách định vị là game điểm ảo nội bộ.

### 2.2. Mục tiêu kỹ thuật

- Xây dựng bằng Laravel + Filament.
- Có mô hình ví dạng ledger, không sửa trực tiếp số dư mà không có log.
- Có settlement engine có unit test đầy đủ.
- Có `profit_rate` snapshot để không ảnh hưởng vé cũ khi admin đổi tỉ lệ.
- Có cơ chế khóa market theo thời gian.
- Có dữ liệu đủ để audit và xử lý correction.

### 2.3. Mục tiêu vận hành

- Admin có thể tự tạo/cập nhật trận, tỷ lệ ăn, kết quả.
- User có thể tự xem lịch, đặt dự đoán, xem vé, xem ví, xem bảng xếp hạng.
- Người vận hành có thể preview settlement trước khi xác nhận.
- Có thể export dữ liệu cần thiết để kiểm tra nội bộ.

---

## 3. Đối tượng sử dụng

| Nhóm người dùng | Mục tiêu sử dụng |
|---|---|
| Player / User | Đăng nhập, xem trận, đặt lá, xem vé, xem lịch sử, xem bảng xếp hạng |
| Operator | Tạo trận, nhập tỷ lệ ăn, mở/khóa market, nhập kết quả sơ bộ |
| Settlement Manager | Kiểm tra preview, xác nhận settlement, xử lý void/correction |
| Auditor | Chỉ xem báo cáo, audit log, lịch sử ví và settlement |
| Super Admin | Toàn quyền cấu hình, phân quyền, khóa hệ thống, xử lý lỗi đặc biệt |

Không sử dụng khái niệm phòng ban/team trong giai đoạn tài liệu tiêu chuẩn này.

---

## 4. Thuật ngữ

| Thuật ngữ | Định nghĩa |
|---|---|
| Lá | Điểm ảo nội bộ, không có giá trị quy đổi |
| Market | Một thị trường dự đoán của một trận/mốc, ví dụ: tỉ số chính xác cả trận |
| Outcome | Một lựa chọn trong market, ví dụ: 2-1, Home -0.5, Over 2.5 |
| Bet / Phiếu dự đoán | Giao dịch user dùng lá để chọn outcome |
| Stake | Số lá user dùng cho một phiếu dự đoán |
| Tỷ lệ ăn | Hệ số lãi theo cách nói Việt Nam, ví dụ `kèo 0.5 ăn 0.9`. Nếu đặt 100 lá và thắng đủ thì lãi 90 lá, gross payout là 190 lá. |
| Settlement | Quá trình tính kết quả thắng/thua/hoàn/nửa thắng/nửa thua |
| Push | Hòa kèo, hoàn lại stake |
| Void | Hủy market/bet và hoàn stake |
| Correction | Điều chỉnh sau settlement, không xóa dữ liệu cũ |
| Full-time | Kết quả 90 phút gồm bù giờ, không gồm hiệp phụ/penalty |

---

## 5. Phạm vi sản phẩm

## 5.1. In scope - Giai đoạn 1 MVP

### User

- Đăng nhập tài khoản được admin cấp.
- Xem dashboard cá nhân.
- Xem số lá khả dụng, lá đang khóa, tổng lá.
- Xem lịch trận WC2026 đã seed.
- Xem chi tiết trận và market đang mở.
- Đặt lá cho:
  - Tỉ số chính xác.
  - Handicap châu Á.
  - Tài/xỉu.
- Xem danh sách phiếu dự đoán.
- Xem lịch sử ví.
- Xem bảng xếp hạng cá nhân.
- Xem thể lệ.

### Admin

- Quản lý user.
- Cấp/trừ lá qua ledger.
- Seed/import lịch WC2026.
- Tạo/cập nhật match, market, outcome.
- Nhập tỷ lệ ăn.
- Mở/khóa market.
- Nhập kết quả trận theo từng period.
- Preview settlement.
- Execute settlement.
- Void market.
- Xem audit log.
- Export báo cáo cơ bản.

## 5.2. In scope - Giai đoạn 2

- Leaderboard nâng cao.
- Achievement/huy hiệu cá nhân.
- Notification trong hệ thống.
- Dashboard admin nâng cao.
- Approval workflow cho settlement/correction.
- Correction workflow rõ ràng.
- Snapshot leaderboard theo ngày/tuần/mùa.

## 5.3. Out of scope

- Nạp tiền, rút tiền, mua lá, bán lá.
- Quy đổi lá thành tiền/hiện vật/dịch vụ.
- Chuyển lá giữa user.
- Public registration.
- Public website cho người ngoài công ty.
- Betting exchange giữa người chơi.
- Tỷ lệ ăn tự động lấy từ nhà cái.
- Tích hợp thanh toán.
- Phòng ban/team leaderboard.

---

## 6. User stories chính

### 6.1. Player

| ID | User story | Priority |
|---|---|---|
| US-P01 | Là user, tôi muốn đăng nhập để vào hệ thống nội bộ | Must |
| US-P02 | Là user, tôi muốn xem số lá hiện có để biết khả năng tham gia | Must |
| US-P03 | Là user, tôi muốn xem trận sắp diễn ra và thời gian khóa | Must |
| US-P04 | Là user, tôi muốn đặt lá vào tỉ số chính xác | Must |
| US-P05 | Là user, tôi muốn đặt lá vào handicap châu Á | Must |
| US-P06 | Là user, tôi muốn đặt lá vào tài/xỉu | Must |
| US-P07 | Là user, tôi muốn xem phiếu đã đặt và trạng thái | Must |
| US-P08 | Là user, tôi muốn xem lịch sử cộng/trừ lá | Must |
| US-P09 | Là user, tôi muốn xem bảng xếp hạng | Should |
| US-P10 | Là user, tôi muốn nhận thông báo khi vé đã settle | Should |

### 6.2. Admin

| ID | User story | Priority |
|---|---|---|
| US-A01 | Là admin, tôi muốn tạo/cập nhật user | Must |
| US-A02 | Là admin, tôi muốn cấp lá ban đầu cho user | Must |
| US-A03 | Là admin, tôi muốn seed lịch WC2026 | Must |
| US-A04 | Là admin, tôi muốn tạo market và outcome | Must |
| US-A05 | Là admin, tôi muốn nhập tỷ lệ ăn cho từng outcome | Must |
| US-A06 | Là admin, tôi muốn khóa market theo giờ | Must |
| US-A07 | Là admin, tôi muốn nhập kết quả từng period | Must |
| US-A08 | Là admin, tôi muốn preview settlement trước khi chạy | Must |
| US-A09 | Là admin, tôi muốn execute settlement một cách an toàn | Must |
| US-A10 | Là admin, tôi muốn void market nếu có lỗi vận hành | Must |
| US-A11 | Là auditor, tôi muốn xem audit log | Must |

---

## 7. Business rules cấp sản phẩm

## 7.1. Rules về lá

- Lá là điểm ảo, không có giá trị quy đổi.
- User không thể tự nạp lá.
- User không thể rút lá.
- User không thể chuyển lá cho người khác.
- Admin cấp/trừ lá phải đi qua wallet ledger.
- Hệ thống không cho số dư khả dụng âm.
- Khi user đặt lá, stake chuyển từ `available_balance` sang `locked_balance`.
- Khi settle, `locked_balance` giảm và `available_balance` tăng theo payout.

## 7.2. Rules về tỷ lệ ăn

- Tỷ lệ nhập theo cách Việt Nam là **tỷ lệ ăn / hệ số lãi** (`profit_rate`), ví dụ `0.90`, `1.10`, `6.50`.
- Công thức quy đổi kỹ thuật: `decimal_odds = 1 + profit_rate`. Ví dụ `ăn 0.90` tương đương decimal odds `1.90`.
- Khi thắng đủ: `gross_payout = stake × (1 + profit_rate)`. Ví dụ đặt 100 lá, ăn 0.90 thì nhận 190 lá, lãi 90 lá.
- Tỷ lệ ăn tại thời điểm user đặt phải được snapshot vào bet.
- Admin đổi tỷ lệ ăn sau đó không ảnh hưởng bet đã đặt.
- Nếu nhập sai tỷ lệ ăn và chưa có bet, admin có thể sửa.
- Nếu đã có bet, nên tạo version mới hoặc outcome mới tùy chính sách vận hành.

## 7.3. Rules về thời gian

- Market chỉ nhận bet khi status là `OPEN` và `current_time < close_at`.
- Khi `current_time >= close_at`, user không thể đặt/sửa/hủy bet.
- Scheduler tự động chuyển market từ `OPEN` sang `LOCKED` khi hết thời gian.
- Admin có thể khóa sớm market, nhưng phải có audit log.

## 7.4. Rules về settlement

- Settlement phải chạy trong database transaction.
- Một bet chỉ được settle một lần trong settlement chính.
- Nếu cần chỉnh sau settle, dùng correction, không xóa/sửa âm thầm.
- Mọi settlement/correction/void đều phải ghi audit log.
- Payout là gross payout, bao gồm stake gốc nếu thắng/push.

---

## 8. Success metrics

| Metric | Cách đo |
|---|---|
| Active users | Số user có ít nhất 1 bet trong tuần |
| Bet volume | Tổng số phiếu dự đoán |
| Settlement accuracy | Số lỗi settlement / tổng market settled |
| Admin operation error | Số correction/void do nhập sai |
| System stability | Không lỗi khóa market, không double-settlement |
| Engagement | Số phiên đăng nhập, lượt xem leaderboard |

---

## 9. Rủi ro chính

| Rủi ro | Mức độ | Biện pháp |
|---|---:|---|
| Bị hiểu là cá cược tiền thật | Cao | Wording, policy, không quy đổi, chỉ nội bộ |
| Sai settlement kèo 0.25/0.75 | Cao | Settlement Engine Spec + unit test |
| Double settlement | Cao | DB constraint + transaction + idempotency |
| Admin nhập sai tỷ lệ ăn/kết quả | Trung bình | Preview, audit log, correction workflow |
| User đặt sát giờ gây race condition | Trung bình | DB lock + check close_at trong transaction |
| Leaderboard sai do cache | Trung bình | Rebuild snapshot sau settlement |

---

## 10. Điều kiện nghiệm thu PRD

Sản phẩm MVP được xem là đạt khi:

- User đăng nhập được.
- Admin cấp lá được.
- User đặt được 3 loại market chính.
- Hệ thống khóa market đúng giờ.
- Admin nhập kết quả và preview settlement được.
- Settlement cộng/trừ lá đúng với test case chuẩn.
- Leaderboard cập nhật đúng.
- Audit log ghi đủ thao tác quan trọng.
- Có trang thể lệ khẳng định lá là điểm ảo không quy đổi.
