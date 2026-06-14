---
title: "Admin Operation Manual"
project: "Du Doan La"
version: "1.0"
status: "draft"
last_updated: "2026-06-11"
---

# Admin Operation Manual - Hướng dẫn vận hành admin

## 1. Mục tiêu

Tài liệu này hướng dẫn admin vận hành hệ thống dự đoán bằng lá từ lúc chuẩn bị mùa giải đến khi settlement và xử lý lỗi.

Đối tượng đọc:

- Super Admin.
- Operator.
- Settlement Manager.
- Auditor.

---

## 2. Nguyên tắc vận hành bắt buộc

- Không tạo tài khoản public.
- Không cho người ngoài công ty tham gia.
- Không cấp lá đổi bằng tiền.
- Không sửa trực tiếp số dư ví trong database.
- Không xóa bet/ledger/settlement.
- Không execute settlement nếu chưa preview.
- Không sửa tỷ lệ ăn âm thầm sau khi đã có user đặt.
- Mọi void/correction phải có lý do.
- Luôn kiểm tra period: cả trận, hiệp 1, hiệp 2, hiệp phụ, penalty.

---

## 3. Quy trình setup ban đầu

## 3.1. Tạo season

1. Vào Admin Panel.
2. Chọn `Seasons`.
3. Nhấn `Create`.
4. Nhập:
   - Code: `WC2026`.
   - Name: `World Cup 2026 Internal Prediction`.
   - Start date.
   - End date.
   - Default starting leaves.
5. Lưu season.
6. Chỉ activate khi đã kiểm tra settings.

## 3.2. Cấu hình settings

Vào `Settings` và kiểm tra:

```text
default_starting_leaves = 1000
min_stake = 10
max_stake_per_bet = 200
max_stake_per_match = 500
max_stake_per_day = 1000
allow_negative_balance = false
allow_leaf_transfer = false
default_market_close_minutes_before_kickoff = 5
rounding_mode = ROUND_HALF_UP
timezone = Asia/Ho_Chi_Minh
```

Giá trị có thể thay đổi theo quy mô công ty, nhưng phải thống nhất trước khi season bắt đầu.

## 3.3. Tạo user

Có 2 cách:

### Cách 1: Tạo thủ công

1. Vào `Users`.
2. Nhấn `Create`.
3. Nhập name, email, password tạm.
4. Gán role `player`.
5. Set status `ACTIVE`.
6. Lưu.

### Cách 2: Import CSV

File mẫu:

```csv
name,email,role,status
Nguyen Van A,a@example.com,player,ACTIVE
Tran Thi B,b@example.com,player,ACTIVE
```

Sau import, kiểm tra số lượng user và role.

## 3.4. Cấp lá khởi tạo

1. Vào `Wallets` hoặc action `Seed Wallets` trong season.
2. Chọn season `WC2026`.
3. Chọn user cần cấp hoặc toàn bộ user active.
4. Nhập số lá khởi tạo.
5. Nhập lý do: `Season initial grant`.
6. Confirm.
7. Kiểm tra ledger `ADMIN_GRANT`.

---

## 4. Import lịch WC2026

## 4.1. Chuẩn bị file CSV

```csv
match_code,stage,home_team,away_team,kickoff_at,timezone,venue,status
M001,Group Stage,TBD,TBD,2026-06-12 02:00:00,Asia/Ho_Chi_Minh,Estadio Azteca,SCHEDULED
```

## 4.2. Import

1. Vào `Matches`.
2. Chọn `Import fixtures`.
3. Upload file CSV.
4. Map cột.
5. Preview lỗi nếu có.
6. Confirm import.
7. Lọc theo season để kiểm tra số trận.

## 4.3. Sau khi import

- Kiểm tra timezone.
- Kiểm tra match_code không trùng.
- Kiểm tra stage.
- Với các đội `TBD`, cập nhật sau khi có thông tin chính thức.

---

## 5. Tạo market cho trận

## 5.1. Tạo market thủ công

1. Vào `Matches`.
2. Chọn trận.
3. Chọn tab `Markets`.
4. Nhấn `Create Market`.
5. Chọn:
   - Period type.
   - Market type.
   - Open at.
   - Close at.
6. Lưu dưới status `DRAFT`.

## 5.2. Market khuyến nghị cho MVP

Mỗi trận nên có:

### Full-time

- Exact Score.
- Asian Handicap.
- Over/Under.

### First half

- Exact Score hoặc Over/Under.
- Handicap nếu admin muốn.

### Second half

- Over/Under.
- Handicap nếu admin muốn.

### Extra time / Penalty

Chỉ tạo cho vòng knock-out nếu cần.

## 5.3. Tạo outcome exact score

Ví dụ:

Tỷ lệ ăn là hệ số lãi, không bao gồm vốn. Ví dụ `ăn 0.90`: đặt 100 lá, thắng đủ nhận 190 lá.

| Label | Home score | Away score | Tỷ lệ ăn |
|---|---:|---:|---:|
| 0-0 | 0 | 0 | 6.00 |
| 1-0 | 1 | 0 | 6.50 |
| 1-1 | 1 | 1 | 4.50 |
| 2-1 | 2 | 1 | 8.00 |

## 5.4. Tạo outcome handicap

Ví dụ:

| Label | Selection side | Line | Tỷ lệ ăn |
|---|---|---:|---:|
| Home -0.75 | HOME | -0.75 | 0.90 |
| Away +0.75 | AWAY | +0.75 | 0.90 |

Luôn tạo đủ 2 phía của một line nếu market là handicap.

## 5.5. Tạo outcome tài/xỉu

Ví dụ:

| Label | Selection side | Line | Tỷ lệ ăn |
|---|---|---:|---:|
| Over 2.5 | OVER | 2.50 | 0.90 |
| Under 2.5 | UNDER | 2.50 | 0.90 |

Luôn tạo cặp Over/Under cùng line.

---

## 6. Publish market

Trước khi publish, kiểm tra:

- Market có ít nhất một outcome active.
- Tỷ lệ ăn hợp lệ.
- Open at < close at.
- Close at không quá sát thời điểm hiện tại.
- Period type đúng.
- Không dùng từ cấm trong label.

Sau đó:

1. Nhấn `Publish`.
2. Confirm.
3. Market chuyển sang `OPEN` nếu đang trong thời gian mở.
4. User bắt đầu nhìn thấy market.

---

## 7. Theo dõi market trước trận

Admin cần kiểm tra:

- Countdown đóng market.
- Số lượng bet.
- Tổng lá đang locked.
- Tỷ lệ ăn có bị nhập sai không.
- Market có cần khóa sớm không.

Nếu phát hiện sai tỷ lệ ăn:

| Tình huống | Cách xử lý |
|---|---|
| Chưa có bet | Sửa tỷ lệ ăn trực tiếp, có log |
| Đã có bet | Disable outcome cũ, tạo outcome/market mới hoặc void nếu lỗi nghiêm trọng |
| Market đã locked | Không sửa tỷ lệ ăn, xử lý sau bằng void/correction nếu cần |

---

## 8. Khóa market

## 8.1. Tự động

Scheduler tự chuyển `OPEN` sang `LOCKED` khi:

```text
now >= close_at
```

## 8.2. Thủ công

Operator có thể lock sớm nếu:

- Trận bắt đầu sớm.
- Có lỗi cần ngừng nhận dự đoán.
- Có thông tin bất thường.

Khi lock thủ công:

- Bắt buộc nhập lý do.
- Ghi audit log.

---

## 9. Nhập kết quả

## 9.1. Sau trận

1. Vào `Matches`.
2. Chọn trận.
3. Chọn `Enter Results`.
4. Nhập kết quả từng period.
5. Save draft.
6. Kiểm tra lại.
7. Confirm result.

## 9.2. Quy tắc nhập period

| Period | Cách nhập |
|---|---|
| FIRST_HALF | Tỉ số riêng hiệp 1 |
| FULL_TIME | Tỉ số 90 phút, gồm bù giờ, không gồm hiệp phụ |
| SECOND_HALF | Tỉ số riêng hiệp 2 |
| EXTRA_TIME | Tỉ số riêng 30 phút hiệp phụ |
| PENALTY | Tỉ số loạt penalty |

Ví dụ:

```text
Hiệp 1: 1-1
Full-time: 2-1
Second half phải là: 1-0
```

Không nhập second half là 2-1.

---

## 10. Preview settlement

1. Vào market đã locked.
2. Chọn `Preview Settlement`.
3. Hệ thống hiển thị:
   - Số bet pending.
   - Total stake.
   - Total payout.
   - Breakdown won/lost/push/half.
   - Danh sách bet và kết quả dự kiến.
4. Export preview nếu cần.
5. Kiểm tra bất thường.

Checklist trước execute:

- Period đúng.
- Kết quả đúng.
- Total stake hợp lý.
- Payout không bất thường.
- Có bet nào lỗi không.
- Market chưa từng settled.

---

## 11. Execute settlement

Chỉ Settlement Manager hoặc Super Admin thực hiện.

1. Từ màn hình preview, nhấn `Execute Settlement`.
2. Tick xác nhận.
3. Nhập password/2FA nếu bật.
4. Confirm.
5. Hệ thống chạy settlement.
6. Kiểm tra status market `SETTLED`.
7. Kiểm tra sample vài bet và wallet ledger.
8. Kiểm tra leaderboard.

Không refresh/liên tục bấm execute nếu hệ thống đang chạy. Nếu bị lỗi, kiểm tra log trước khi chạy lại.

---

## 12. Void market

## 12.1. Khi nào void

- Trận bị hủy.
- Market tạo sai nghiêm trọng.
- Period sai.
- Tỷ lệ ăn sai ảnh hưởng nhiều user.
- Không có kết quả chính xác để settle.

## 12.2. Cách void

1. Chọn market.
2. Nhấn `Void`.
3. Nhập lý do.
4. Preview số bet và số lá sẽ hoàn.
5. Settlement Manager/Super Admin confirm.
6. Hệ thống hoàn locked stake về available.
7. Bet status `VOIDED`.
8. Ledger `BET_VOIDED`.

---

## 13. Correction

## 13.1. Khi nào correction

- Nhập sai tỉ số.
- Settle nhầm period.
- Rule tính sai do lỗi phần mềm.
- Kết quả chính thức thay đổi.

## 13.2. Quy trình

1. Vào settlement cũ.
2. Chọn `Create Correction`.
3. Nhập lý do.
4. Nhập kết quả đúng hoặc chọn rule đúng.
5. Preview delta từng user.
6. Kiểm tra user có bị trừ quá available không.
7. Confirm correction.
8. Hệ thống tạo correction settlement.
9. Hệ thống tạo ledger delta.
10. Audit log ghi đầy đủ.

## 13.3. Lưu ý

Không xóa settlement cũ. Không sửa ledger cũ. Không sửa database thủ công nếu không có biên bản kỹ thuật.

---

## 14. Export báo cáo

Báo cáo cần xuất:

- User list.
- Wallet ledger.
- Bet list.
- Settlement report.
- Leaderboard.
- Audit log.

Khuyến nghị export sau:

- Mỗi ngày thi đấu.
- Sau vòng bảng.
- Sau mỗi vòng knock-out.
- Trước khi reset season.

---

## 15. Xử lý sự cố thường gặp

## 15.1. User báo bị trừ lá nhưng không thấy phiếu

Kiểm tra:

- Bet list theo user.
- Wallet ledger `BET_PLACED`.
- Request log.

Nếu có ledger nhưng không có bet, đây là lỗi nghiêm trọng cần dev kiểm tra transaction.

## 15.2. User báo đặt đúng giờ nhưng bị từ chối

Kiểm tra:

- `close_at` theo timezone.
- Market status.
- Server time.
- Audit log scheduler lock.

Server là nguồn thời gian chuẩn, không theo đồng hồ máy user.

## 15.3. Settlement sai payout

Kiểm tra:

- Bet profit_rate snapshot.
- Bet line snapshot.
- Period result.
- Calculation detail trong settlement item.
- Test lại bằng Settlement Engine Spec.

Nếu sai thật, tạo correction.

## 15.4. Leaderboard sai

- Rebuild leaderboard snapshot.
- Kiểm tra wallet totals.
- Kiểm tra bet status.
- Không sửa leaderboard thủ công.

---

## 16. Checklist trước mỗi ngày thi đấu

- Lịch trận đúng.
- Đội bóng đã cập nhật nếu trước đó TBD.
- Market đã publish.
- Tỷ lệ ăn đã kiểm tra.
- Close time đúng.
- Scheduler chạy.
- Queue worker chạy.
- Backup gần nhất thành công.
- Admin trực settlement đã phân công.

---

## 17. Checklist sau mỗi ngày thi đấu

- Tất cả market cần settle đã settled.
- Không còn market `SETTLING` treo.
- Wallet ledger không lỗi.
- Leaderboard cập nhật.
- Export báo cáo nếu cần.
- Ghi chú lỗi/correction nếu có.

---

## 18. Quy tắc không làm

Admin không được:

- Tạo user ngoài công ty.
- Bật chức năng chuyển lá.
- Cấp lá để đổi lấy tiền.
- Xóa bet/ledger/settlement.
- Sửa trực tiếp database khi chưa có approval kỹ thuật.
- Execute settlement khi chưa kiểm tra preview.
- Dùng wording “cược tiền”, “nhà cái”, “rút thưởng” trong nội dung hệ thống.
