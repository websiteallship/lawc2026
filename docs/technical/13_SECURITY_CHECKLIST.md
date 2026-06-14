---
title: "Security Checklist"
project: "Dự Đoán Lá"
version: "1.0"
status: "draft"
last_updated: "2026-06-11"
owner: "Security / Engineering"
---

# Security Checklist

## 1. Mục tiêu

Tài liệu này đặt ra checklist bảo mật cho hệ thống **Dự Đoán Lá**. Hệ thống tuy chỉ dùng điểm ảo, nhưng vẫn có rủi ro:

```text
- Lộ tài khoản nội bộ.
- User đặt lá thay người khác.
- Admin sửa số dư không kiểm soát.
- Settlement sai hoặc bị chạy lại.
- Lộ thông tin lịch sử user.
- Bị khai thác qua form import/export.
```

---

## 2. Nguyên tắc bảo mật lõi

```text
1. Mọi request thay đổi dữ liệu phải kiểm tra permission.
2. Mọi thay đổi lá phải đi qua wallet ledger.
3. Không có direct balance update trong controller/resource.
4. Không xóa bet/settlement/ledger đã phát sinh.
5. Admin action nhạy cảm phải có audit log.
6. Settlement phải idempotent.
7. User không được thấy dữ liệu private của user khác, trừ leaderboard công khai.
8. Production không bật debug.
```

---

## 3. Authentication

## 3.1. Password policy

Đề xuất:

```text
- Tối thiểu 10 ký tự.
- Có chữ hoa, chữ thường, số hoặc ký tự đặc biệt.
- Không cho dùng password phổ biến.
- Không gửi password qua chat/email plain text.
- Khi admin tạo user, dùng link đặt mật khẩu hoặc password tạm bắt đổi lần đầu.
```

## 3.2. 2FA

Bắt buộc 2FA cho:

```text
super_admin
operator
settlement_manager
auditor nếu có quyền xem dữ liệu nhạy cảm
```

Không bắt buộc cho player nhưng khuyến khích nếu dùng tài khoản cá nhân.

## 3.3. Session

```text
- Session timeout hợp lý.
- Logout các session cũ khi đổi password.
- Cookie secure + httpOnly trên production.
- CSRF protection bật.
```

---

## 4. Authorization

## 4.1. Permission matrix

Mọi Resource/Action/Page/Widget trong Filament phải gắn permission.

Ví dụ:

```text
wallets.grant
wallets.deduct
markets.publish
markets.void
settlements.preview
settlements.execute
settings.update
audit_logs.view
```

## 4.2. Không tin frontend

Không dựa vào việc ẩn nút UI. Backend/service phải check:

```text
- User có được đặt market này không?
- Market còn mở không?
- Admin có quyền settle không?
- Admin có quyền correction không?
```

---

## 5. Wallet security

## 5.1. Không update ví trực tiếp

Cấm:

```php
$user->wallet->available_balance += 100;
$user->wallet->save();
```

Bắt buộc dùng:

```php
WalletService::credit(...);
WalletService::debit(...);
WalletService::lockStake(...);
WalletService::settleBet(...);
```

## 5.2. Ledger bắt buộc

Mọi biến động phải có:

```text
wallet_id
user_id
type
amount
balance_before
balance_after
reference_type
reference_id
created_by
reason
metadata
```

## 5.3. Transaction + row lock

Khi đặt lá:

```php
DB::transaction(function () use ($user, $outcome, $stake) {
    $wallet = Wallet::where('user_id', $user->id)->lockForUpdate()->first();
    // check balance, create bet, create ledger, update wallet
});
```

---

## 6. Settlement security

## 6.1. Idempotency

Settlement không được trả thưởng 2 lần.

Yêu cầu:

```text
- settlement có unique key theo market_id + result_version.
- bet status phải chuyển từ PENDING sang settled status đúng 1 lần.
- Nếu job chạy lại, phải nhận biết đã execute.
```

## 6.2. Approval

Nếu phase 2 đã bật approval:

```text
- Operator nhập kết quả.
- Settlement Manager duyệt.
- Hệ thống execute.
- Không cho cùng 1 người vừa nhập vừa duyệt nếu cấu hình yêu cầu maker-checker.
```

## 6.3. Correction

Correction không sửa/xóa ledger cũ. Phải tạo transaction đảo chiều hoặc ledger điều chỉnh.

---

## 7. Input validation

## 7.1. Stake

```text
stake >= min_stake
stake <= max_stake_per_bet
stake là số nguyên dương
user có đủ available_balance
```

## 7.2. Profit rate

```text
profit_rate >= min_profit_rate
profit_rate <= max_profit_rate
precision <= 4 decimal places
```

Cảnh báo nếu admin nhập:

```text
1.90
```

vì có thể admin đang nhầm `decimal odds 1.90` với `ăn 0.90`.

## 7.3. File import

```text
- Giới hạn dung lượng file.
- Chỉ cho CSV/XLSX nếu cần.
- Validate từng dòng.
- Không execute import nếu có lỗi nghiêm trọng.
- Preview trước khi import.
- Ghi import batch id.
```

---

## 8. Web security

```text
[ ] CSRF bật cho form.
[ ] Escape output mặc định.
[ ] Không render HTML user nhập nếu không sanitize.
[ ] Rate limit login.
[ ] Rate limit đặt lá nếu cần.
[ ] Không expose stack trace.
[ ] Không commit .env.
[ ] Không log token/password.
[ ] Không để storage private public.
```

---

## 9. Admin panel protection

```text
[ ] Admin panel yêu cầu login.
[ ] Admin panel có 2FA cho role nhạy cảm.
[ ] IP allowlist nếu chạy nội bộ.
[ ] Audit log mọi action nhạy cảm.
[ ] Không cho player vào panel admin.
[ ] Không để route dev/debug public.
```

---

## 10. Data privacy

User thường chỉ được xem:

```text
- Hồ sơ cá nhân của mình.
- Ví lá của mình.
- Vé của mình.
- Lịch sử lá của mình.
- Leaderboard công khai.
```

User không được xem:

```text
- Email user khác nếu không cần.
- Wallet ledger chi tiết user khác.
- Vé pending của user khác.
- Admin audit log.
```

---

## 11. Backup security

```text
[ ] Backup không nằm trong public web root.
[ ] Backup có mã hóa nếu chứa dữ liệu thật.
[ ] Quyền đọc backup giới hạn.
[ ] Có retention policy.
[ ] Có restore drill.
[ ] Không gửi backup qua kênh chat không an toàn.
```

---

## 12. Security test cases

```text
[ ] Player truy cập /admin bị chặn.
[ ] Player gọi endpoint đặt thay user khác bị chặn.
[ ] Player sửa stake bằng devtool vượt max bị chặn.
[ ] Player đặt market đã đóng bị chặn.
[ ] Admin không có quyền settle bị chặn.
[ ] Operator không có quyền settings.update bị chặn.
[ ] Import odds profit_rate 1.90 bị cảnh báo.
[ ] Settlement job chạy 2 lần không double payout.
[ ] Correction tạo ledger mới, không sửa ledger cũ.
```

---

## 13. Production security checklist

```text
[ ] APP_DEBUG=false
[ ] APP_ENV=production
[ ] HTTPS bật
[ ] Secure cookie bật
[ ] 2FA admin bật
[ ] Rate limit login bật
[ ] Permission matrix đã test
[ ] Audit log hoạt động
[ ] Backup hoạt động
[ ] Queue failed monitor bật
[ ] Admin route không expose nếu không cần
[ ] Legal disclaimer hiển thị
```
