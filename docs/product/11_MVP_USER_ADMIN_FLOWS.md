---
title: "MVP User And Admin Flows"
project: "Du Doan La"
version: "1.0"
status: "active"
last_updated: "2026-06-12"
owner: "Product / Engineering"
---

# MVP User And Admin Flows

## 1. Muc tieu

Tai lieu nay chot cac flow can build truoc cho MVP de UI/Filament khong tu dien giai nghiep vu.

## 2. Player flow

## 2.1. First login va dong y the le

```text
Open app
-> Login bang tai khoan admin cap
-> Neu chua accepted_rules_at: hien thi xac nhan the le
-> User tick dong y
-> Dashboard
```

Copy xac nhan phai bam sat legal policy:

```text
Toi xac nhan da hieu rang la la diem ao noi bo, khong co gia tri quy doi, khong duoc mua ban/chuyen nhuong, va he thong chi dung cho hoat dong giai tri noi bo.
```

## 2.2. Dashboard

Hien thi:

- Available la.
- Locked la.
- Tong la.
- Tran sap dien ra.
- Phieu dang pending.
- Link the le.

Khong hien thi:

- Nap/rut.
- Doi thuong.
- Chuyen la.

## 2.3. Dat du doan

```text
Dashboard
-> Chon tran
-> Xem market dang OPEN
-> Chon outcome
-> Nhap stake
-> Modal xac nhan
-> Submit
-> BetPlacementService
-> Thanh cong: hien thi phieu PENDING va wallet moi
```

Modal xac nhan phai hien thi:

- Ten tran.
- Period.
- Lua chon.
- `display_odds`, vi du `Brazil -0.5 an 0.90`.
- Stake.
- Payout toi da neu thang du.
- Close time.

Neu market dong khi modal dang mo, submit phai bi tu choi bang `E_MARKET_CLOSED`.

## 2.4. My bets va wallet history

User chi xem duoc:

- Bet cua minh.
- Wallet ledger cua minh.
- Trang thai: PENDING, WON, LOST, PUSH, HALF_WON, HALF_LOST, VOIDED, CORRECTED.

## 3. Admin flow

## 3.1. Setup season va import lich

```text
Super Admin tao season WC2026
-> Operator upload fixtures_import.csv/xlsx
-> Preview
-> Fix loi neu co
-> Execute import
-> Audit log IMPORT_FIXTURES
```

Lich WC2026 bat buoc nhap tu file trong MVP.

## 3.2. Tao market va odds

```text
Operator tao/import markets
-> Tao/import outcomes
-> Validate profit_rate
-> Publish market
-> Audit log MARKET_PUBLISHED
```

Neu outcome da co bet:

- Khong sua odds am tham.
- Tao outcome/version moi hoac Super Admin xu ly theo policy.

## 3.3. Lock market

```text
Scheduler chay markets:lock-expired moi phut
-> Market OPEN co close_at <= now thanh LOCKED
```

Admin co the lock som neu co ly do va audit log.

## 3.4. Result va settlement

```text
Operator/Settlement Manager nhap result theo period
-> Preview settlement
-> Kiem tra summary va sample tickets
-> Settlement Manager execute
-> SettlementEngine transaction
-> Wallet ledgers
-> Settlement items
-> Leaderboard rebuild/update
-> Audit log SETTLEMENT_EXECUTED
```

Operator khong duoc execute settlement trong permission mac dinh.

## 3.5. Void market

```text
Phat hien market sai truoc settlement
-> Nhap reason
-> Settlement Manager approve/execute void
-> Pending bets thanh VOIDED
-> Locked stake tra ve available
-> Ledger BET_VOIDED
```

## 3.6. Correction

Correction khong nam trong happy path MVP, nhung skeleton/policy phai co. Khi can sua settlement da execute, dung `docs/operation/21_CORRECTION_OPERATION_POLICY.md`.

## 4. Empty/error states bat buoc

| Screen | Empty/error state |
|---|---|
| Dashboard | Chua co tran dang mo |
| Match detail | Market da dong |
| Bet submit | Khong du la |
| Bet submit | Stake khong hop le |
| My bets | Chua co phieu du doan |
| Wallet history | Chua co lich su vi |
| Admin import | File rong/sai format |
| Settlement preview | Chua co pending bets |

## 5. UI copy safe defaults

Dung:

- Du doan.
- La.
- Diem ao.
- Ti le an.
- Phieu du doan.
- Hoan la.
- Bang xep hang.

Tranh:

- Ca cuoc.
- Tien cuoc.
- Nha cai.
- Nap/rut.
- Doi thuong.
- Cashout.
