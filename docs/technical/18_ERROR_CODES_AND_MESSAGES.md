---
title: "Error Codes and Messages"
project: "Dự Đoán Lá"
version: "1.0"
status: "draft"
last_updated: "2026-06-11"
owner: "Engineering / Product"
---

# Error Codes and Messages

## 1. Mục tiêu

Chuẩn hóa mã lỗi và thông báo để:

```text
- User hiểu vì sao không đặt được lá.
- Admin hiểu lỗi import/settlement.
- Dev debug nhanh.
- API/Livewire/Filament dùng chung message.
```

---

## 2. Format lỗi chuẩn

## 2.1. API format

```json
{
  "success": false,
  "message": "Không đủ lá khả dụng",
  "errors": [
    {
      "code": "INSUFFICIENT_BALANCE",
      "field": "stake",
      "detail": "Bạn có 50 lá khả dụng, không thể đặt 100 lá."
    }
  ]
}
```

## 2.2. UI toast format

```text
Không đủ lá khả dụng. Bạn còn 50 lá, số lá đặt là 100.
```

## 2.3. Admin detail format

```text
[INSUFFICIENT_BALANCE] User #15 available_balance=50, stake=100, market_id=20.
```

---

## 3. Auth errors

| Code | HTTP | User message | Admin/dev detail |
|---|---:|---|---|
| UNAUTHENTICATED | 401 | Vui lòng đăng nhập lại. | Session/token invalid |
| LOGIN_FAILED | 422 | Email hoặc mật khẩu không đúng. | Do not reveal which field failed |
| ACCOUNT_INACTIVE | 403 | Tài khoản đã bị khóa. | user.status != ACTIVE |
| PASSWORD_CHANGE_REQUIRED | 403 | Vui lòng đổi mật khẩu trước khi tiếp tục. | password_temporary=true |
| TWO_FACTOR_REQUIRED | 403 | Vui lòng xác thực 2 lớp. | Admin role requires 2FA |

---

## 4. Permission errors

| Code | HTTP | Message |
|---|---:|---|
| PERMISSION_DENIED | 403 | Bạn không có quyền thực hiện thao tác này. |
| ADMIN_ONLY | 403 | Chức năng này chỉ dành cho admin. |
| PLAYER_ONLY | 403 | Chức năng này chỉ dành cho người chơi. |
| SETTLEMENT_APPROVAL_REQUIRED | 403 | Settlement cần được duyệt trước khi thực thi. |
| MAKER_CHECKER_VIOLATION | 403 | Người nhập kết quả không được tự duyệt settlement này. |

---

## 5. Market errors

| Code | HTTP | Message | Khi nào xảy ra |
|---|---:|---|---|
| MARKET_NOT_FOUND | 404 | Không tìm thấy mốc dự đoán. | market_id sai |
| MARKET_NOT_OPEN | 409 | Mốc dự đoán chưa mở hoặc không còn mở. | status != OPEN |
| MARKET_CLOSED | 409 | Mốc dự đoán đã đóng. | now >= close_at |
| MARKET_LOCKED | 409 | Mốc dự đoán đã khóa. | status LOCKED/SETTLING |
| MARKET_ALREADY_SETTLED | 409 | Mốc dự đoán đã được xử lý kết quả. | status SETTLED |
| MARKET_VOIDED | 409 | Mốc dự đoán đã bị hủy và hoàn lá. | status VOIDED |
| OUTCOME_NOT_ACTIVE | 409 | Lựa chọn này hiện không khả dụng. | outcome suspended/inactive |
| ODDS_CHANGED | 409 | Tỷ lệ đã thay đổi, vui lòng tải lại. | stale outcome version |

---

## 6. Stake/wallet errors

| Code | HTTP | Message |
|---|---:|---|
| INVALID_STAKE | 422 | Số lá đặt không hợp lệ. |
| STAKE_BELOW_MIN | 422 | Số lá đặt thấp hơn mức tối thiểu. |
| STAKE_ABOVE_MAX | 422 | Số lá đặt vượt mức tối đa mỗi vé. |
| MATCH_STAKE_LIMIT_EXCEEDED | 422 | Bạn đã vượt giới hạn lá cho trận này. |
| DAILY_STAKE_LIMIT_EXCEEDED | 422 | Bạn đã vượt giới hạn lá trong ngày. |
| INSUFFICIENT_BALANCE | 422 | Không đủ lá khả dụng. |
| WALLET_NOT_FOUND | 404 | Không tìm thấy ví lá. |
| WALLET_LOCK_FAILED | 409 | Ví đang được xử lý, vui lòng thử lại. |

Message có biến:

```text
Bạn có {available_balance} lá khả dụng, không thể đặt {stake} lá.
```

---

## 7. Odds/profit rate errors

| Code | HTTP | Message |
|---|---:|---|
| INVALID_PROFIT_RATE | 422 | Tỷ lệ ăn không hợp lệ. |
| PROFIT_RATE_TOO_LOW | 422 | Tỷ lệ ăn thấp hơn mức cho phép. |
| PROFIT_RATE_TOO_HIGH | 422 | Tỷ lệ ăn vượt mức cho phép. |
| PROFIT_RATE_SUSPICIOUS | 422/Warning | Tỷ lệ ăn có vẻ bất thường. Nếu muốn “ăn 0.90”, hãy nhập 0.90, không nhập 1.90. |
| INVALID_LINE_VALUE | 422 | Line kèo không hợp lệ. |
| INVALID_SELECTION_SIDE | 422 | Lựa chọn không hợp lệ. |

---

## 8. Settlement errors

| Code | HTTP | Message |
|---|---:|---|
| RESULT_REQUIRED | 422 | Cần nhập kết quả trước khi settlement. |
| RESULT_PERIOD_MISSING | 422 | Chưa có kết quả cho mốc này. |
| SETTLEMENT_ALREADY_EXECUTED | 409 | Settlement đã được thực thi. |
| SETTLEMENT_PROCESSING | 409 | Settlement đang được xử lý. |
| BET_ALREADY_SETTLED | 409 | Vé đã được xử lý. |
| SETTLEMENT_IDEMPOTENCY_CONFLICT | 409 | Yêu cầu settlement bị trùng hoặc khác dữ liệu. |
| SETTLEMENT_CALCULATION_FAILED | 500 | Không thể tính settlement cho một số vé. |
| SETTLEMENT_APPROVAL_MISSING | 403 | Chưa có phê duyệt settlement. |

---

## 9. Correction/void errors

| Code | HTTP | Message |
|---|---:|---|
| VOID_REASON_REQUIRED | 422 | Cần nhập lý do hủy mốc dự đoán. |
| MARKET_CANNOT_BE_VOIDED | 409 | Không thể hủy mốc ở trạng thái hiện tại. |
| CORRECTION_REASON_REQUIRED | 422 | Cần nhập lý do điều chỉnh. |
| CORRECTION_ALREADY_EXECUTED | 409 | Điều chỉnh đã được thực thi. |
| ORIGINAL_SETTLEMENT_NOT_FOUND | 404 | Không tìm thấy settlement gốc. |

---

## 10. Import/export errors

| Code | HTTP | Message |
|---|---:|---|
| IMPORT_FILE_REQUIRED | 422 | Vui lòng chọn file import. |
| IMPORT_FILE_INVALID | 422 | File import không hợp lệ. |
| IMPORT_COLUMN_MISSING | 422 | File thiếu cột bắt buộc. |
| IMPORT_ROW_INVALID | 422 | Một số dòng không hợp lệ. |
| IMPORT_DUPLICATE_ROW | 422 | File có dòng bị trùng dữ liệu. |
| IMPORT_BATCH_NOT_FOUND | 404 | Không tìm thấy batch import. |
| EXPORT_FAILED | 500 | Không thể xuất báo cáo. |

---

## 11. System errors

| Code | HTTP | Message |
|---|---:|---|
| SYSTEM_MAINTENANCE | 503 | Hệ thống đang bảo trì. |
| QUEUE_JOB_FAILED | 500 | Tác vụ nền xử lý thất bại. |
| DATABASE_ERROR | 500 | Lỗi dữ liệu, vui lòng liên hệ admin. |
| RATE_LIMITED | 429 | Bạn thao tác quá nhanh, vui lòng thử lại sau. |
| UNKNOWN_ERROR | 500 | Có lỗi xảy ra, vui lòng thử lại. |

---

## 12. Message style guideline

## 12.1. User-facing

Nên:

```text
Mốc dự đoán đã đóng lúc 01:55. Bạn không thể đặt thêm lá cho mốc này.
```

Không nên:

```text
Market status invalid.
```

## 12.2. Admin-facing

Nên chi tiết hơn:

```text
Không thể execute settlement vì market #12 đã có settlement #88 ở trạng thái EXECUTED.
```

## 12.3. Dev log

Có thể ghi đủ context:

```text
SETTLEMENT_ALREADY_EXECUTED market_id=12 settlement_id=88 actor_id=3 idempotency_key=abc
```

---

## 13. Error test checklist

```text
[ ] Sai login trả LOGIN_FAILED.
[ ] Player vào admin trả PERMISSION_DENIED.
[ ] Đặt stake âm trả INVALID_STAKE.
[ ] Đặt vượt balance trả INSUFFICIENT_BALANCE.
[ ] Market đóng trả MARKET_CLOSED.
[ ] Outcome suspended trả OUTCOME_NOT_ACTIVE.
[ ] Admin nhập profit_rate 1.90 cho handicap có cảnh báo PROFIT_RATE_SUSPICIOUS.
[ ] Chạy settlement lần 2 trả SETTLEMENT_ALREADY_EXECUTED.
[ ] Void không có reason trả VOID_REASON_REQUIRED.
```
