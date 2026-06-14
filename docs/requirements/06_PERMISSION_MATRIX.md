---
title: "Permission Matrix"
project: "Du Doan La"
version: "1.0"
status: "draft"
last_updated: "2026-06-11"
---

# Permission Matrix

## 1. Mục tiêu

Tài liệu này định nghĩa vai trò và quyền trong hệ thống dự đoán nội bộ bằng lá. Permission matrix dùng để cấu hình Filament Shield / Spatie Permission và kiểm thử phân quyền.

---

## 2. Role chuẩn

| Role | Mô tả |
|---|---|
| `super_admin` | Toàn quyền hệ thống |
| `operator` | Vận hành trận, market, tỷ lệ ăn, kết quả sơ bộ |
| `settlement_manager` | Xác nhận settlement, void, correction |
| `auditor` | Xem báo cáo, audit log, dữ liệu vận hành |
| `player` | Người chơi nội bộ |

Không sử dụng role phòng ban/team.

---

## 3. Nguyên tắc phân quyền

- Mặc định deny nếu không có quyền rõ ràng.
- User thường không truy cập admin panel, trừ khi thiết kế chung panel nhưng phải giới hạn page.
- Operator không tự ý execute settlement nếu không có quyền settlement.
- Auditor chỉ xem, không tạo/sửa/xóa.
- Super Admin không nên dùng tài khoản cá nhân để chơi.
- Admin action nhạy cảm phải có audit log.
- Các action sau nên yêu cầu xác nhận password hoặc 2FA:
  - Execute settlement.
  - Void market.
  - Correction.
  - Reset season.
  - Bulk grant/deduct leaves.

---

## 4. Permission naming convention

```text
resource.action
```

Ví dụ:

```text
users.view
users.create
wallets.grant
markets.publish
settlements.execute
```

---

## 5. Matrix tổng quan

| Module / Action | Super Admin | Operator | Settlement Manager | Auditor | Player |
|---|---:|---:|---:|---:|---:|
| Xem dashboard admin | Có | Có | Có | Có | Không |
| Xem dashboard user | Không cần | Không cần | Không cần | Không cần | Có |
| Quản lý user | Có | Không | Không | Xem | Không |
| Cấp/trừ lá | Có | Có giới hạn | Không | Xem | Không |
| Tạo season | Có | Không | Không | Xem | Không |
| Import lịch trận | Có | Có | Không | Xem | Không |
| Tạo/sửa trận | Có | Có | Không | Xem | Không |
| Tạo/sửa market | Có | Có | Không | Xem | Không |
| Nhập tỷ lệ ăn | Có | Có | Không | Xem | Không |
| Publish market | Có | Có | Không | Xem | Không |
| Lock market | Có | Có | Không | Xem | Không |
| Nhập kết quả | Có | Có | Có | Xem | Không |
| Preview settlement | Có | Có | Có | Xem | Không |
| Execute settlement | Có | Không | Có | Xem | Không |
| Void market | Có | Yêu cầu duyệt | Có | Xem | Không |
| Correction | Có | Không | Có | Xem | Không |
| Xem audit log | Có | Không | Có | Có | Không |
| Export report | Có | Có giới hạn | Có | Có | Không |
| Cấu hình hệ thống | Có | Không | Không | Xem | Không |
| Đặt dự đoán | Không khuyến nghị | Không khuyến nghị | Không khuyến nghị | Không | Có |

---

## 6. Permission chi tiết

## 6.1. User permissions

| Permission | Super Admin | Operator | Settlement Manager | Auditor | Player |
|---|---:|---:|---:|---:|---:|
| `users.view` | ✓ |  |  | ✓ |  |
| `users.create` | ✓ |  |  |  |  |
| `users.update` | ✓ |  |  |  |  |
| `users.block` | ✓ |  |  |  |  |
| `users.reset_password` | ✓ |  |  |  |  |
| `users.assign_roles` | ✓ |  |  |  |  |
| `users.export` | ✓ |  |  | ✓ |  |

## 6.2. Wallet permissions

| Permission | Super Admin | Operator | Settlement Manager | Auditor | Player |
|---|---:|---:|---:|---:|---:|
| `wallets.view` | ✓ | ✓ | ✓ | ✓ | Own only |
| `wallets.ledger.view` | ✓ | ✓ | ✓ | ✓ | Own only |
| `wallets.grant` | ✓ | ✓ limited |  |  |  |
| `wallets.deduct` | ✓ | ✓ limited |  |  |  |
| `wallets.bulk_grant` | ✓ |  |  |  |  |
| `wallets.reset_season` | ✓ |  |  |  |  |
| `wallets.export` | ✓ | ✓ | ✓ | ✓ |  |

### Giới hạn operator

```text
operator có thể cấp/trừ lá nhưng:
- Không vượt quá max_admin_adjustment_per_action
- Bắt buộc nhập lý do
- Bắt buộc ghi audit log
- Không được reset season
```

## 6.3. Season & Match permissions

| Permission | Super Admin | Operator | Settlement Manager | Auditor | Player |
|---|---:|---:|---:|---:|---:|
| `seasons.view` | ✓ | ✓ | ✓ | ✓ | ✓ active only |
| `seasons.create` | ✓ |  |  |  |  |
| `seasons.update` | ✓ |  |  |  |  |
| `seasons.close` | ✓ |  |  |  |  |
| `matches.view` | ✓ | ✓ | ✓ | ✓ | ✓ |
| `matches.create` | ✓ | ✓ |  |  |  |
| `matches.update` | ✓ | ✓ |  |  |  |
| `matches.import` | ✓ | ✓ |  |  |  |
| `matches.cancel` | ✓ | ✓ |  |  |  |
| `matches.export` | ✓ | ✓ | ✓ | ✓ |  |

## 6.4. Market & Tỷ lệ ăn permissions

| Permission | Super Admin | Operator | Settlement Manager | Auditor | Player |
|---|---:|---:|---:|---:|---:|
| `markets.view` | ✓ | ✓ | ✓ | ✓ | ✓ open/locked |
| `markets.create` | ✓ | ✓ |  |  |  |
| `markets.update` | ✓ | ✓ |  |  |  |
| `markets.publish` | ✓ | ✓ |  |  |  |
| `markets.lock` | ✓ | ✓ |  |  |  |
| `markets.void.request` | ✓ | ✓ |  |  |  |
| `markets.void.approve` | ✓ |  | ✓ |  |  |
| `outcomes.create` | ✓ | ✓ |  |  |  |
| `outcomes.update` | ✓ | ✓ |  |  |  |
| `outcomes.disable` | ✓ | ✓ |  |  |  |
| `tỷ lệ ăn.update` | ✓ | ✓ |  |  |  |
| `tỷ lệ ăn.import` | ✓ | ✓ |  |  |  |

### Rule sửa tỷ lệ ăn

| Tình huống | Quyền |
|---|---|
| Outcome chưa có bet | Operator được sửa |
| Outcome đã có bet | Chỉ tạo phiên bản tỷ lệ ăn/outcome mới hoặc Super Admin xử lý |
| Market đã locked | Không sửa tỷ lệ ăn, trừ correction có log |
| Market đã settled | Không sửa tỷ lệ ăn |

## 6.5. Bet permissions

| Permission | Super Admin | Operator | Settlement Manager | Auditor | Player |
|---|---:|---:|---:|---:|---:|
| `bets.view_any` | ✓ | ✓ | ✓ | ✓ |  |
| `bets.view_own` |  |  |  |  | ✓ |
| `bets.place` |  |  |  |  | ✓ |
| `bets.cancel_own` |  |  |  |  | Optional trước khi lock |
| `bets.cancel_admin` | ✓ |  | ✓ |  |  |
| `bets.export` | ✓ | ✓ | ✓ | ✓ |  |

Khuyến nghị MVP: không cho user hủy bet sau khi đã đặt, để giảm tranh cãi.

## 6.6. Result & Settlement permissions

| Permission | Super Admin | Operator | Settlement Manager | Auditor | Player |
|---|---:|---:|---:|---:|---:|
| `results.view` | ✓ | ✓ | ✓ | ✓ | ✓ settled |
| `results.enter` | ✓ | ✓ | ✓ |  |  |
| `results.update_before_settlement` | ✓ | ✓ | ✓ |  |  |
| `settlements.preview` | ✓ | ✓ | ✓ | ✓ |  |
| `settlements.execute` | ✓ |  | ✓ |  |  |
| `settlements.rollback` | ✓ |  |  |  |  |
| `settlements.correction.create` | ✓ |  | ✓ |  |  |
| `settlements.correction.approve` | ✓ |  | ✓ |  |  |
| `settlements.export` | ✓ | ✓ | ✓ | ✓ |  |

### Separation of duties khuyến nghị

Nếu quy mô lớn:

```text
Người nhập tỷ lệ ăn/kết quả không nên là người execute settlement cuối cùng.
```

MVP có thể chưa bắt buộc, nhưng audit log phải đầy đủ.

## 6.7. Leaderboard permissions

| Permission | Super Admin | Operator | Settlement Manager | Auditor | Player |
|---|---:|---:|---:|---:|---:|
| `leaderboards.view` | ✓ | ✓ | ✓ | ✓ | ✓ |
| `leaderboards.rebuild` | ✓ |  | ✓ |  |  |
| `leaderboards.export` | ✓ | ✓ | ✓ | ✓ |  |

## 6.8. Audit & Settings permissions

| Permission | Super Admin | Operator | Settlement Manager | Auditor | Player |
|---|---:|---:|---:|---:|---:|
| `audit_logs.view` | ✓ |  | ✓ | ✓ |  |
| `audit_logs.export` | ✓ |  | ✓ | ✓ |  |
| `settings.view` | ✓ |  |  | ✓ |  |
| `settings.update` | ✓ |  |  |  |  |
| `system.maintenance` | ✓ |  |  |  |  |

---

## 7. Filament Shield seed đề xuất

## 7.1. Permissions cần seed

```text
users.view
users.create
users.update
users.block
users.reset_password
users.assign_roles
users.export

wallets.view
wallets.ledger.view
wallets.grant
wallets.deduct
wallets.bulk_grant
wallets.reset_season
wallets.export

seasons.view
seasons.create
seasons.update
seasons.close

matches.view
matches.create
matches.update
matches.import
matches.cancel
matches.export

markets.view
markets.create
markets.update
markets.publish
markets.lock
markets.void.request
markets.void.approve

outcomes.create
outcomes.update
outcomes.disable
tỷ lệ ăn.update
tỷ lệ ăn.import

bets.view_any
bets.view_own
bets.place
bets.cancel_own
bets.cancel_admin
bets.export

results.view
results.enter
results.update_before_settlement

settlements.preview
settlements.execute
settlements.rollback
settlements.correction.create
settlements.correction.approve
settlements.export

leaderboards.view
leaderboards.rebuild
leaderboards.export

audit_logs.view
audit_logs.export

settings.view
settings.update
system.maintenance
```

## 7.2. Role assignment mặc định

| Role | Permissions |
|---|---|
| super_admin | All |
| operator | matches, markets, tỷ lệ ăn, result enter, limited wallet, reports |
| settlement_manager | settlement, correction, void approve, leaderboard rebuild, audit view |
| auditor | view/export only |
| player | own wallet, place bet, view leaderboard, view rules |

---

## 8. Test phân quyền bắt buộc

| Test ID | Actor | Action | Expected |
|---|---|---|---|
| PERM-001 | Player | Truy cập admin users | Denied |
| PERM-002 | Player | Đặt bet khi đủ lá | Allowed |
| PERM-003 | Player | Xem ledger của người khác | Denied |
| PERM-004 | Operator | Execute settlement | Denied |
| PERM-005 | Operator | Nhập tỷ lệ ăn | Allowed |
| PERM-006 | Settlement Manager | Sửa tỷ lệ ăn | Denied |
| PERM-007 | Auditor | Xem audit log | Allowed |
| PERM-008 | Auditor | Cấp lá | Denied |
| PERM-009 | Super Admin | Reset season | Allowed |
| PERM-010 | Player | Truy cập bet của người khác qua URL | Denied |

---

## 9. Acceptance criteria

- Toàn bộ permission được seed tự động.
- Role mặc định được tạo qua seeder.
- Không có page/action admin nào thiếu permission check.
- Player không vào được admin operation pages.
- Auditor không thực hiện được write action.
- Các action nhạy cảm có audit log.
- Test PERM-001 đến PERM-010 pass.
