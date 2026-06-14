---
title: "User Guide / Thể lệ người chơi"
project: "Du Doan La"
version: "1.0"
status: "draft"
last_updated: "2026-06-11"
---

# User Guide / Thể lệ người chơi

## 1. Giới thiệu

**Dự đoán Lá** là game dự đoán bóng đá nội bộ dành cho nhân sự công ty. Người chơi sử dụng điểm ảo gọi là **lá** để tham gia dự đoán kết quả trận đấu.

Hệ thống chỉ phục vụ mục đích giải trí và gắn kết nội bộ.

---

## 2. Lá là gì?

**Lá** là điểm ảo trong hệ thống.

Lá dùng để:

- Đặt phiếu dự đoán.
- Ghi nhận kết quả đúng/sai.
- Xếp hạng cá nhân.
- Tạo sự vui vẻ trong mùa giải.

Lá không phải tiền. Lá không có giá trị quy đổi.

---

## 3. Quy định bắt buộc về lá

Người chơi cần hiểu rõ:

```text
- Lá là điểm ảo nội bộ.
- Lá không có giá trị quy đổi thành tiền.
- Lá không có giá trị quy đổi thành hiện vật.
- Lá không có giá trị quy đổi thành dịch vụ.
- Không được mua lá.
- Không được bán lá.
- Không được chuyển lá cho người khác.
- Không được dùng lá để thỏa thuận lợi ích bên ngoài hệ thống.
```

Nếu vi phạm, tài khoản có thể bị khóa và phiếu dự đoán có thể bị hủy theo quyết định của ban tổ chức.

---

## 4. Tài khoản người chơi

- Mỗi người chơi sử dụng tài khoản do admin cấp.
- Không tự đăng ký tài khoản.
- Không chia sẻ tài khoản cho người khác.
- Không dùng tài khoản của người khác để đặt dự đoán.
- Nếu quên mật khẩu, liên hệ admin.

---

## 5. Cách nhận lá

Người chơi được admin cấp lá theo mùa giải hoặc theo rule nội bộ.

Ví dụ:

```text
Mỗi người bắt đầu mùa giải với 1.000 lá.
```

Admin có thể cấp thêm hoặc điều chỉnh lá nếu có lỗi hệ thống hoặc chính sách nội bộ.

---

## 6. Các loại số dư

| Loại số dư | Ý nghĩa |
|---|---|
| Lá khả dụng | Số lá có thể dùng để đặt dự đoán |
| Lá đang khóa | Số lá đã dùng cho phiếu đang chờ kết quả |
| Tổng lá | Lá khả dụng + lá đang khóa |

Ví dụ:

```text
Bạn có 1.000 lá khả dụng.
Bạn đặt 100 lá.
Sau khi đặt:
- Lá khả dụng còn 900.
- Lá đang khóa là 100.
```

Khi có kết quả, số lá đang khóa sẽ được xử lý theo kết quả dự đoán.

---

## 7. Cách đặt dự đoán

1. Đăng nhập hệ thống.
2. Chọn trận đấu.
3. Chọn mốc dự đoán:
   - Cả trận.
   - Hiệp 1.
   - Hiệp 2.
   - Hiệp phụ nếu có.
   - Penalty nếu có.
4. Chọn loại dự đoán:
   - Tỉ số chính xác.
   - Handicap.
   - Tài/xỉu.
5. Chọn lựa chọn mong muốn.
6. Nhập số lá.
7. Kiểm tra tỷ lệ ăn và số lá có thể nhận.
8. Xác nhận.

Sau khi xác nhận, phiếu dự đoán sẽ có trạng thái `Đang chờ kết quả`.

---

## 8. Thời gian đóng dự đoán

Mỗi market có thời gian đóng riêng.

Khi đã quá thời gian đóng:

- Không thể đặt phiếu mới.
- Không thể sửa phiếu.
- Không thể hủy phiếu.

Thời gian của hệ thống là thời gian chuẩn. Nếu đồng hồ trên điện thoại/máy tính của bạn khác hệ thống, thời gian hệ thống vẫn là căn cứ cuối cùng.

---

## 9. Dự đoán tỉ số chính xác

## 9.1. Cách chơi

Bạn chọn đúng tỉ số của mốc trận.

Ví dụ:

```text
Bạn chọn: 2-1
Kết quả thật: 2-1
=> Bạn dự đoán đúng.
```

## 9.2. Cách tính lá

```text
Nếu đúng:
    Nhận = số lá đặt × (1 + tỷ lệ ăn)
Nếu sai:
    Nhận = 0
```

Ví dụ:

```text
Bạn đặt 100 lá vào tỉ số 2-1, ăn 6.00.
Nếu đúng: nhận 700 lá, gồm 100 lá vốn + 600 lá lãi.
Nếu sai: nhận 0 lá.
```

---

## 10. Dự đoán handicap châu Á

Handicap là dạng dự đoán có cộng/trừ bàn ảo cho một đội.

Ví dụ:

```text
Team A -0.5
Team B +0.5
```

Nếu bạn chọn `Team A -0.5`, Team A cần thắng trận thì bạn mới thắng dự đoán.

## 10.1. Một số line thường gặp

| Line | Ý nghĩa cơ bản |
|---:|---|
| 0 | Hòa thì hoàn lá |
| -0.5 | Đội được chọn phải thắng |
| +0.5 | Đội được chọn thắng hoặc hòa là thắng |
| -1.0 | Thắng đúng 1 thì hoàn, thắng 2+ thì thắng |
| +1.0 | Thua đúng 1 thì hoàn, hòa/thắng thì thắng |
| -0.25 | Nửa line 0, nửa line -0.5 |
| +0.25 | Nửa line 0, nửa line +0.5 |
| -0.75 | Nửa line -0.5, nửa line -1.0 |
| +0.75 | Nửa line +0.5, nửa line +1.0 |

## 10.2. Các kết quả có thể xảy ra

| Kết quả | Ý nghĩa |
|---|---|
| Thắng đủ | Nhận lại vốn + lãi theo tỷ lệ ăn |
| Thua đủ | Mất số lá đã đặt |
| Hòa kèo | Hoàn lại số lá đã đặt |
| Nửa thắng | Một nửa thắng, một nửa hoàn |
| Nửa thua | Một nửa thua, một nửa hoàn |

## 10.3. Ví dụ Team A -0.75

Bạn đặt:

```text
Team A -0.75
Ăn 0.90
Số lá: 100
```

Line `-0.75` được hiểu là:

```text
50 lá ở line -0.5
50 lá ở line -1.0
```

| Kết quả Team A | Kết quả phiếu | Lá nhận |
|---|---|---:|
| Thắng 2 bàn trở lên | Thắng đủ | 190 |
| Thắng đúng 1 bàn | Nửa thắng | 145 |
| Hòa hoặc thua | Thua đủ | 0 |

---

## 11. Dự đoán tài/xỉu

Tài/xỉu là dự đoán tổng số bàn thắng của mốc trận.

Ví dụ:

```text
Tài 2.5
Xỉu 2.5
```

Nếu bạn chọn `Tài 2.5`, tổng bàn thắng phải từ 3 trở lên thì bạn thắng.

## 11.1. Ví dụ Tài/Xỉu 2.5

| Tổng bàn | Tài 2.5 | Xỉu 2.5 |
|---:|---|---|
| 0 | Thua | Thắng |
| 1 | Thua | Thắng |
| 2 | Thua | Thắng |
| 3 | Thắng | Thua |
| 4+ | Thắng | Thua |

## 11.2. Ví dụ Tài/Xỉu 2.0

| Tổng bàn | Tài 2.0 | Xỉu 2.0 |
|---:|---|---|
| 0-1 | Thua | Thắng |
| 2 | Hoàn lá | Hoàn lá |
| 3+ | Thắng | Thua |

## 11.3. Ví dụ Tài 2.25

Bạn đặt:

```text
Tài 2.25
Ăn 0.90
Số lá: 100
```

Line `2.25` được hiểu là:

```text
50 lá ở Tài 2.0
50 lá ở Tài 2.5
```

| Tổng bàn | Kết quả | Lá nhận |
|---:|---|---:|
| 3+ | Thắng đủ | 190 |
| 2 | Nửa thua | 50 |
| 0-1 | Thua đủ | 0 |

## 11.4. Ví dụ Xỉu 2.25

| Tổng bàn | Kết quả | Lá nhận nếu đặt 100 ăn 0.90 |
|---:|---|---:|
| 0-1 | Thắng đủ | 190 |
| 2 | Nửa thắng | 145 |
| 3+ | Thua đủ | 0 |

---

## 12. Các mốc trận

## 12.1. Cả trận

Tính 90 phút chính thức, bao gồm bù giờ. Không tính hiệp phụ và penalty.

## 12.2. Hiệp 1

Tính từ đầu trận đến hết hiệp 1, bao gồm bù giờ hiệp 1.

## 12.3. Hiệp 2

Tính riêng số bàn trong hiệp 2, bao gồm bù giờ hiệp 2.

Ví dụ:

```text
Hiệp 1: 1-1
Cả trận: 2-1
Hiệp 2 được tính là: 1-0
```

## 12.4. Hiệp phụ

Chỉ có ở một số trận loại trực tiếp. Tính riêng 30 phút hiệp phụ, không tính penalty.

## 12.5. Penalty

Tính riêng loạt sút luân lưu nếu trận có penalty.

---

## 13. Khi nào được hoàn lá?

Bạn được hoàn lá trong các trường hợp:

- Hòa kèo/push.
- Market bị hủy/void.
- Trận bị hủy và ban tổ chức quyết định hoàn lá.
- Một phần của quarter line được hoàn.

Ví dụ:

```text
Bạn chọn Team A -1.0.
Team A thắng đúng 1 bàn.
=> Hòa kèo, hoàn 100 lá nếu đặt 100.
```

---

## 14. Bảng xếp hạng

Bảng xếp hạng cá nhân có thể dựa trên:

- Tổng lá hiện có.
- Lãi/lỗ mùa giải.
- ROI.
- Tỉ lệ thắng.
- Số lần đúng tỉ số.
- Số phiếu đã đặt.

Không có bảng xếp hạng phòng ban/team.

Một số bảng phụ như ROI có thể yêu cầu số phiếu tối thiểu để tránh trường hợp chỉ đặt 1 phiếu rồi đứng top.

---

## 15. Trạng thái phiếu dự đoán

| Trạng thái | Ý nghĩa |
|---|---|
| Pending | Đang chờ kết quả |
| Won | Thắng đủ |
| Lost | Thua đủ |
| Push | Hoàn lá |
| Half won | Nửa thắng |
| Half lost | Nửa thua |
| Voided | Phiếu bị hủy và hoàn lá |
| Corrected | Phiếu đã được điều chỉnh do correction |

---

## 16. Lịch sử ví

Mọi thay đổi lá đều có trong lịch sử ví, ví dụ:

- Admin cấp lá.
- Đặt phiếu.
- Thắng/thua/hoàn lá.
- Market bị hủy.
- Điều chỉnh do correction.

Nếu thấy số lá không đúng, hãy kiểm tra lịch sử ví trước khi báo admin.

---

## 17. Các hành vi không được phép

Người chơi không được:

- Mua bán lá.
- Chuyển lá cho người khác.
- Thỏa thuận đổi lá lấy tiền/quà/dịch vụ.
- Dùng tài khoản người khác.
- Tạo nhiều tài khoản.
- Lợi dụng lỗi hệ thống.
- Rủ rê dùng hệ thống cho mục đích cá cược tiền thật.
- Cố tình gây tranh cãi hoặc làm sai lệch tinh thần vui vẻ nội bộ.

---

## 18. Khi có lỗi hoặc tranh chấp

Nếu có vấn đề, người chơi nên cung cấp:

- Mã phiếu.
- Tên trận.
- Thời điểm đặt.
- Ảnh chụp màn hình nếu có.
- Mô tả lỗi.

Ban tổ chức sẽ kiểm tra:

- Phiếu dự đoán.
- Profit rate snapshot.
- Market close time.
- Kết quả period.
- Settlement detail.
- Wallet ledger.

Quyết định cuối cùng dựa trên dữ liệu hệ thống và thể lệ đã công bố.

---

## 19. Câu xác nhận tham gia

Khi tham gia, người chơi xác nhận:

```text
Tôi hiểu rằng Dự đoán Lá là game điểm ảo nội bộ. Lá không phải tiền, không có giá trị quy đổi thành tiền, hiện vật hoặc dịch vụ. Tôi không mua bán, chuyển nhượng hoặc dùng lá cho bất kỳ thỏa thuận lợi ích nào ngoài hệ thống.
```

---

## 20. Tóm tắt nhanh

```text
- Chơi vui nội bộ.
- Lá là điểm ảo.
- Không tiền thật.
- Không đổi thưởng.
- Không mua bán/chuyển lá.
- Đặt trước khi market đóng.
- Kết quả được hệ thống tính tự động theo thể lệ.
- Có thắc mắc thì kiểm tra mã phiếu và lịch sử ví.
```
