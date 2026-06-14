---
title: "Test Plan / QA Checklist"
project: "Du Doan La"
version: "1.0"
status: "draft"
last_updated: "2026-06-11"
---

# Test Plan / QA Checklist

## 1. Mục tiêu test

Đảm bảo hệ thống dự đoán bằng lá hoạt động chính xác, an toàn và có thể vận hành nội bộ. Trọng tâm test là:

1. Wallet ledger.
2. Bet placement.
3. Market locking.
4. Settlement engine.
5. Permission.
6. Audit log.
7. Correction/void.
8. Concurrency.

---

## 2. Test scope

## 2.1. In scope

- Auth.
- User management.
- Wallet.
- Season/match/market/outcome.
- Bet placement.
- Exact score settlement.
- Asian handicap settlement.
- Over/under settlement.
- Void market.
- Correction.
- Leaderboard.
- Admin workflows.
- Permission matrix.
- Audit log.
- Import/export.
- Scheduler/queue.

## 2.2. Out of scope

- Thanh toán.
- Quy đổi thưởng.
- Team/phòng ban leaderboard.
- Public registration.
- Mobile native app.

---

## 3. Test environments

| Environment | Mục đích |
|---|---|
| Local | Dev tự chạy unit/integration test |
| Staging | QA và admin test luồng thật |
| Production | Chỉ smoke test sau deploy |

## 3.1. Test data cần seed

```text
- 1 season WC2026_TEST
- 5 users player
- 1 super_admin
- 1 operator
- 1 settlement_manager
- 1 auditor
- 3 matches
- Market exact score, handicap, over/under
- Wallet mỗi player: 1.000 lá
```

---

## 4. Unit Test Plan

## 4.1. Settlement exact score

| ID | Scenario | Input | Expected |
|---|---|---|---|
| UT-EX-001 | Đúng tỉ số | Pick 2-1, result 2-1, stake 100, tỷ lệ ăn 6.00 | WON, payout 700 |
| UT-EX-002 | Sai home score | Pick 2-1, result 1-1 | LOST, payout 0 |
| UT-EX-003 | Sai away score | Pick 2-1, result 2-0 | LOST, payout 0 |
| UT-EX-004 | Tỉ số 0-0 | Pick 0-0, result 0-0, tỷ lệ ăn 4.50 | WON, payout 550 nếu stake 100 |

## 4.2. Asian handicap

| ID | Selection | Line | Result | Expected |
|---|---|---:|---|---|
| UT-AH-001 | Home | -0.5 | Home thắng 1 | WON, 190 |
| UT-AH-002 | Home | -0.5 | Hòa | LOST, 0 |
| UT-AH-003 | Home | 0 | Hòa | PUSH, 100 |
| UT-AH-004 | Home | -1.0 | Home thắng 1 | PUSH, 100 |
| UT-AH-005 | Home | -1.0 | Home thắng 2 | WON, 190 |
| UT-AH-006 | Home | -0.75 | Home thắng 1 | HALF_WON, 145 |
| UT-AH-007 | Home | -0.25 | Hòa | HALF_LOST, 50 |
| UT-AH-008 | Away | +0.25 | Hòa | HALF_WON, 145 |
| UT-AH-009 | Away | +0.75 | Away thua 1 | HALF_LOST, 50 |
| UT-AH-010 | Away | +1.0 | Away thua 1 | PUSH, 100 |

Assume stake 100, tỷ lệ ăn 0.90.

## 4.3. Over/Under

| ID | Selection | Line | Total goals | Expected |
|---|---|---:|---:|---|
| UT-OU-001 | Over | 2.5 | 3 | WON, 190 |
| UT-OU-002 | Over | 2.5 | 2 | LOST, 0 |
| UT-OU-003 | Over | 2.0 | 2 | PUSH, 100 |
| UT-OU-004 | Under | 2.0 | 2 | PUSH, 100 |
| UT-OU-005 | Over | 2.25 | 2 | HALF_LOST, 50 |
| UT-OU-006 | Under | 2.25 | 2 | HALF_WON, 145 |
| UT-OU-007 | Over | 2.75 | 3 | HALF_WON, 145 |
| UT-OU-008 | Under | 2.75 | 3 | HALF_LOST, 50 |
| UT-OU-009 | Under | 2.5 | 2 | WON, 190 |
| UT-OU-010 | Under | 2.5 | 3 | LOST, 0 |

Assume stake 100, tỷ lệ ăn 0.90.

## 4.4. Line splitter

| ID | Line | Expected components |
|---|---:|---|
| UT-LINE-001 | 0 | [0] |
| UT-LINE-002 | -0.5 | [-0.5] |
| UT-LINE-003 | +0.5 | [+0.5] |
| UT-LINE-004 | -0.25 | [-0.5, 0] |
| UT-LINE-005 | +0.25 | [0, +0.5] |
| UT-LINE-006 | -0.75 | [-1.0, -0.5] |
| UT-LINE-007 | +0.75 | [+0.5, +1.0] |
| UT-LINE-008 | 2.25 | [2.0, 2.5] |
| UT-LINE-009 | 2.75 | [2.5, 3.0] |

---

## 5. Integration Test Plan

## 5.1. Bet placement

| ID | Scenario | Steps | Expected |
|---|---|---|---|
| IT-BET-001 | Đặt thành công | User có 1000 lá, đặt 100 lá market open | Bet PENDING, available 900, locked 100, ledger BET_PLACED |
| IT-BET-002 | Không đủ lá | User có 50 lá, đặt 100 lá | Reject, ví không đổi |
| IT-BET-003 | Market locked | Đặt vào market LOCKED | Reject |
| IT-BET-004 | Quá close_at | now >= close_at | Reject |
| IT-BET-005 | Outcome inactive | Đặt outcome INACTIVE | Reject |
| IT-BET-006 | Stake dưới min | Stake 1, min 10 | Reject |
| IT-BET-007 | Stake vượt max per bet | Stake 1000, max 500 | Reject |
| IT-BET-008 | Snapshot tỷ lệ ăn | Admin đổi tỷ lệ ăn sau khi đặt | Bet giữ tỷ lệ ăn cũ |

## 5.2. Wallet ledger

| ID | Scenario | Expected |
|---|---|---|
| IT-WAL-001 | Admin grant 1000 | available +1000, ledger ADMIN_GRANT |
| IT-WAL-002 | Admin deduct 200 | available -200, ledger ADMIN_DEDUCT |
| IT-WAL-003 | Bet placed | available -stake, locked +stake |
| IT-WAL-004 | Bet won | locked -stake, available +payout |
| IT-WAL-005 | Bet lost | locked -stake, available +0 |
| IT-WAL-006 | Bet push | locked -stake, available +stake |
| IT-WAL-007 | Void | locked -stake, available +stake |

## 5.3. Settlement execution

| ID | Scenario | Expected |
|---|---|---|
| IT-SET-001 | Preview không đổi ví | Chạy preview, wallet unchanged |
| IT-SET-002 | Execute đổi ví | Wallet cập nhật theo payout |
| IT-SET-003 | Settlement tạo items | Mỗi bet có settlement_item |
| IT-SET-004 | Settlement tạo ledger | Mỗi bet có ledger tương ứng |
| IT-SET-005 | Settlement cập nhật market | Market status SETTLED |
| IT-SET-006 | Double settlement | Lần 2 bị chặn |
| IT-SET-007 | Không có bet | Settlement thành công với 0 bet hoặc cảnh báo tùy rule |

---

## 6. Permission Test Plan

| ID | Actor | Action | Expected |
|---|---|---|---|
| PT-001 | Player | Truy cập admin user list | 403 |
| PT-002 | Player | Đặt bet own wallet | 200 |
| PT-003 | Player | Xem bet người khác | 403 |
| PT-004 | Operator | Nhập tỷ lệ ăn | Allowed |
| PT-005 | Operator | Execute settlement | 403 |
| PT-006 | Settlement Manager | Execute settlement | Allowed |
| PT-007 | Auditor | Cấp lá | 403 |
| PT-008 | Auditor | Export audit log | Allowed |
| PT-009 | Super Admin | Settings update | Allowed |

---

## 7. E2E Workflow Test

## 7.1. Full happy path

```text
1. Super Admin tạo season.
2. Super Admin seed users và cấp 1000 lá.
3. Operator import lịch trận.
4. Operator tạo market exact score, handicap, over/under.
5. Operator publish market.
6. Player đặt 3 phiếu.
7. Scheduler khóa market khi hết giờ.
8. Operator nhập kết quả.
9. Settlement Manager preview settlement.
10. Settlement Manager execute settlement.
11. Player xem phiếu đã settle.
12. Player xem wallet history.
13. Leaderboard cập nhật.
14. Auditor xem audit log.
```

Expected:

- Không lỗi permission.
- Không âm ví.
- Ledger đủ.
- Settlement đúng.
- Leaderboard đúng.

## 7.2. Void path

```text
1. Player đặt bet vào market open.
2. Admin phát hiện market tạo sai.
3. Settlement Manager void market.
4. Hệ thống hoàn stake.
5. Bet status VOIDED.
6. Ledger BET_VOIDED.
```

## 7.3. Correction path

```text
1. Market đã settled.
2. Admin phát hiện nhập sai kết quả.
3. Settlement Manager tạo correction.
4. Hệ thống preview delta.
5. Settlement Manager confirm.
6. Hệ thống tạo correction settlement và ledger delta.
```

---

## 8. Concurrency Test

| ID | Scenario | Expected |
|---|---|---|
| CT-001 | User gửi 2 request đặt bet cùng lúc, ví chỉ đủ 1 request | Chỉ 1 thành công |
| CT-002 | User đặt đúng lúc market đang khóa bởi scheduler | Chỉ thành công nếu transaction check trước close_at và status OPEN |
| CT-003 | 2 admin execute settlement cùng lúc | Chỉ 1 thành công |
| CT-004 | Admin đổi tỷ lệ ăn khi user đang đặt | Bet snapshot nhất quán hoặc request bị retry |
| CT-005 | Settlement 500 bets cùng market | Không deadlock, ví đúng |

---

## 9. Security Test

| ID | Scenario | Expected |
|---|---|---|
| ST-001 | CSRF form bet | Token invalid bị reject |
| ST-002 | Direct URL admin | Player bị 403 |
| ST-003 | SQL injection search | Không lỗi, không leak data |
| ST-004 | XSS trong tên user/team | Escape đúng |
| ST-005 | Rate limit login | Nhiều failed attempts bị limit |
| ST-006 | Session timeout | Session hết hạn đúng |
| ST-007 | Force update hidden fields | Server validate lại, không tin client |

---

## 10. Performance Test

| ID | Scenario | Target |
|---|---|---|
| PERF-001 | Match list 104 trận | < 1s server response ở staging |
| PERF-002 | Leaderboard 500 users | < 1s nếu dùng snapshot/cache |
| PERF-003 | Settlement 1.000 bets | Hoàn thành trong thời gian chấp nhận được, không timeout web request nếu queue |
| PERF-004 | Concurrent 100 users đặt sát giờ | Không sai số dư, không double bet |

---

## 11. Import/Export Test

| ID | Scenario | Expected |
|---|---|---|
| IMP-001 | Import fixtures hợp lệ | Tạo match đúng |
| IMP-002 | Import trùng match_code | Reject hoặc update theo rule |
| IMP-003 | Import tỷ lệ ăn thiếu field | Báo lỗi row |
| IMP-004 | Import tỷ lệ ăn line sai format | Báo lỗi row |
| EXP-001 | Export bets | File có đủ cột |
| EXP-002 | Export ledger | File có đủ ledger và balance after |
| EXP-003 | Export settlement | Có summary và item |

---

## 12. Regression checklist trước release

- Auth login/logout.
- Player đặt bet.
- Market close.
- Exact score settlement.
- Handicap settlement.
- Over/Under settlement.
- Wallet ledger movement.
- Leaderboard update.
- Audit log.
- Permission.
- Void.
- Correction.
- Import fixtures.
- Export reports.
- Footer/legal disclaimer.

---

## 13. Definition of Done

Một feature chỉ được xem là done khi:

- Code hoàn thành.
- Unit test pass.
- Integration test liên quan pass.
- Permission được kiểm tra.
- Audit log có nếu là admin action.
- Không có dữ liệu bị xóa cứng trái rule.
- UI có empty/error state.
- Có ghi chú vận hành nếu admin cần dùng.
