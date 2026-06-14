---
title: "Correction Operation Policy"
project: "Du Doan La"
version: "1.0"
status: "active"
last_updated: "2026-06-12"
owner: "Operations / Settlement"
---

# Correction Operation Policy

## 1. Muc tieu

Correction dung khi market da settled nhung ket qua hoac cau hinh settlement bi sai. Correction khong dung de sua odds tuy tien, khong xoa settlement cu, khong sua ledger cu.

## 2. Khi nao duoc correction

Cho phep:

- Nhap sai result period.
- Nhap nham period.
- Settlement calculator bug duoc xac nhan.
- Market bi settle bang result chua confirm.

Khong dung correction de:

- Doi odds sau khi user da dat.
- Uu ai user/admin.
- Che giau loi van hanh.
- Xoa lich su.

## 3. Role

| Action | Role |
|---|---|
| Request correction | operator, settlement_manager, super_admin |
| Preview correction | settlement_manager, super_admin |
| Execute correction | settlement_manager, super_admin |
| View correction/audit | auditor, settlement_manager, super_admin |

Neu quy mo lon, bat buoc nguoi execute khac nguoi nhap result ban dau.

## 4. Required data

Correction request phai co:

- Market.
- Old settlement.
- New result.
- Reason.
- Evidence/source note.
- Actor.
- Timestamp.

## 5. Calculation rule

```text
old_gross_payout = payout da tra trong settlement cu
new_gross_payout = payout dung theo result moi
delta = new_gross_payout - old_gross_payout
```

Wallet movement:

```text
delta > 0: available_balance += delta
delta = 0: khong doi vi, van log correction item neu can
delta < 0: available_balance -= abs(delta) neu du la
```

Neu `delta < 0` va available khong du:

```text
Khong cho am available_balance.
Tao trang thai MANUAL_REVIEW hoac PENDING_RECOVERY.
Super Admin xu ly bang admin deduct co ledger rieng neu policy cho phep.
```

## 6. Data integrity

Bat buoc:

- Tao correction settlement moi.
- Tao settlement item moi cho bet bi anh huong.
- Tao wallet ledger `SETTLEMENT_CORRECTION` cho delta khac 0.
- Update bet correction metadata hoac status corrected theo design.
- Rebuild leaderboard cho season.
- Ghi audit log.

Cam:

- Xoa settlement cu.
- Sua wallet ledger cu.
- Sua bet payout cu khong co correction trace.
- Cho balance am.

## 7. UX yeu cau

Preview correction phai hien thi:

- Old status/payout.
- New status/payout.
- Delta.
- User affected.
- Wallet available hien tai.
- Can manual review hay khong.

Execute phai co:

- Confirm modal.
- Reason required.
- Permission check.
- 2FA/password confirmation neu da cau hinh.

## 8. Tests

Bat buoc co test:

- Correction tang payout.
- Correction giam payout khi user du available.
- Correction giam payout khi user khong du available thi manual review.
- Khong xoa settlement/ledger cu.
- Leaderboard rebuild sau correction.
- Permission deny operator execute correction.
