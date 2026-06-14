---
title: "Observability, Logging & Monitoring"
project: "Dự Đoán Lá"
version: "1.0"
status: "draft"
last_updated: "2026-06-11"
owner: "Engineering / Operations"
---

# Observability, Logging & Monitoring

## 1. Mục tiêu

Hệ thống có nghiệp vụ ví lá và settlement, nên log/monitor phải đủ để trả lời các câu hỏi:

```text
- Vì sao user bị trừ lá?
- Vé nào thắng/thua/push?
- Settlement chạy lúc nào, bởi ai?
- Có job nào fail không?
- Có market nào không tự khóa không?
- Balance hiện tại có khớp ledger không?
```

---

## 2. Các loại log

| Loại log | Mục đích | Ví dụ |
|---|---|---|
| Application log | Lỗi app | Exception, validation unusual |
| Audit log | Hành động admin | Sửa odds, settle, void |
| Wallet ledger | Biến động lá | BET_PLACED, BET_WON |
| Import log | Import dữ liệu | File, batch, dòng lỗi |
| Queue log | Job nền | Settlement job failed |
| Security log | Login/permission | Login failed, blocked access |

---

## 3. Audit log bắt buộc

Các event phải log:

```text
USER_CREATED
USER_UPDATED
USER_LOCKED
WALLET_GRANTED
WALLET_DEDUCTED
MATCH_CREATED
MATCH_UPDATED
MARKET_CREATED
MARKET_PUBLISHED
MARKET_LOCKED
MARKET_VOIDED
OUTCOME_CREATED
ODDS_UPDATED
RESULT_UPDATED
SETTLEMENT_PREVIEWED
SETTLEMENT_APPROVED
SETTLEMENT_EXECUTED
CORRECTION_CREATED
CORRECTION_EXECUTED
IMPORT_STARTED
IMPORT_COMPLETED
EXPORT_CREATED
SETTINGS_UPDATED
```

Mỗi audit record cần có:

```text
actor_id
action
subject_type
subject_id
before
after
ip_address
user_agent
created_at
reason
metadata
```

---

## 4. Wallet ledger không thay thế audit log

`wallet_ledger` ghi biến động tài chính/điểm.

`audit_log` ghi ai thao tác và dữ liệu trước/sau.

Ví dụ admin cấp 1000 lá:

```text
wallet_ledger: ADMIN_GRANT amount=1000 balance_before=0 balance_after=1000
audit_log: actor=admin action=WALLET_GRANTED subject=user#15 reason="Cấp lá đầu mùa"
```

---

## 5. Metrics cần theo dõi

## 5.1. Business metrics

```text
users_active_count
bets_placed_count
bets_pending_count
bets_settled_count
bets_voided_count
total_staked_leaves
total_payout_leaves
total_locked_leaves
markets_open_count
markets_locked_count
settlements_executed_count
```

## 5.2. System metrics

```text
queue_pending_jobs
queue_failed_jobs
queue_oldest_job_age
scheduler_last_run_at
database_connections
database_size
redis_memory
http_5xx_count
http_4xx_count
login_failed_count
```

---

## 6. Health checks

## 6.1. Basic health

```text
GET /health
```

Kiểm tra:

```text
- App boot được.
- Database query được.
- Redis ping được.
```

## 6.2. Deep health nội bộ

```text
GET /health/deep
```

Chỉ admin/internal access.

Kiểm tra:

```text
- Queue worker heartbeat.
- Scheduler heartbeat.
- Failed jobs count.
- Last backup time.
- Last market lock job.
```

---

## 7. Scheduler heartbeat

Tạo bảng hoặc cache key:

```text
scheduler_heartbeat:last_run_at
markets_lock_expired:last_run_at
leaderboard_rebuild:last_run_at
backup:last_success_at
```

Alert nếu:

```text
markets_lock_expired quá 2 phút không chạy.
scheduler quá 2 phút không heartbeat.
backup quá 24h không thành công.
```

---

## 8. Queue monitoring

Nếu dùng Horizon:

```text
- Monitor queue settlement.
- Monitor failed jobs.
- Retry failed jobs có kiểm soát.
- Không retry settlement job nếu không idempotent.
```

Queue quan trọng:

```text
settlement
imports
exports
notifications
```

Alert nếu:

```text
- settlement job failed.
- failed_jobs > 0.
- queue oldest job age > 5 phút.
```

---

## 9. Log context chuẩn

Khi log nghiệp vụ, luôn kèm context:

```php
Log::info('Bet placed', [
    'user_id' => $user->id,
    'bet_id' => $bet->id,
    'market_id' => $market->id,
    'outcome_id' => $outcome->id,
    'stake' => $stake,
    'profit_rate' => $outcome->profit_rate,
]);
```

Settlement log:

```php
Log::info('Settlement executed', [
    'settlement_id' => $settlement->id,
    'market_id' => $market->id,
    'bets_count' => $settlement->items_count,
    'total_stake' => $settlement->total_stake,
    'total_payout' => $settlement->total_payout,
    'actor_id' => $actor->id,
]);
```

---

## 10. Data integrity checks

Nên có command định kỳ:

```bash
php artisan integrity:check-wallets
php artisan integrity:check-settlements
php artisan integrity:check-leaderboard
```

## 10.1. Wallet integrity

Check:

```text
available_balance >= 0
locked_balance >= 0
total_balance = available_balance + locked_balance
sum pending bets stake = locked_balance theo user/season
ledger balance_after khớp wallet hiện tại
```

## 10.2. Settlement integrity

Check:

```text
settled bet có settlement_item
settlement_item gross_payout khớp bet payout
market SETTLED không còn pending bet
voided market không còn locked stake
```

## 10.3. Leaderboard integrity

Check:

```text
net_profit = total_payout - total_staked
ROI không chia cho 0
rank không duplicate nếu không tie policy
```

---

## 11. Alert policy

| Sự kiện | Mức độ | Hành động |
|---|---|---|
| Settlement job failed | Critical | Notify admin/dev ngay |
| Scheduler không chạy | Critical | Notify dev |
| Market không khóa đúng giờ | Critical | Notify operator |
| Database backup fail | High | Notify dev/ops |
| Login failed tăng bất thường | Medium | Check security |
| Queue backlog cao | Medium | Tăng worker/check lỗi |

---

## 12. Dashboard admin đề xuất

Widget nên có:

```text
- Market đang mở
- Market sắp đóng trong 30 phút
- Pending bets count
- Total locked leaves
- Settlement chờ duyệt
- Failed jobs
- Last backup time
- Last scheduler heartbeat
```

---

## 13. Incident response

Khi phát hiện settlement sai:

```text
1. Khóa market liên quan nếu chưa khóa.
2. Không sửa dữ liệu trực tiếp.
3. Export settlement_items và wallet_ledger liên quan.
4. Xác định nguyên nhân: result sai, line sai, calculator bug, odds snapshot sai.
5. Nếu cần, tạo correction theo quy trình.
6. Rebuild leaderboard.
7. Ghi incident note.
```

Khi market không tự đóng:

```text
1. Chạy markets:lock-expired thủ công.
2. Kiểm tra bets đặt sau close_at.
3. Nếu có bet không hợp lệ, void/correction theo rule.
4. Kiểm tra scheduler heartbeat.
```

---

## 14. Log retention

| Log | Retention đề xuất |
|---|---:|
| Application log | 30-90 ngày |
| Audit log | Ít nhất 1 mùa giải + 6 tháng |
| Wallet ledger | Không xóa |
| Settlement items | Không xóa |
| Import log | Ít nhất 1 mùa giải |
| Failed jobs | 30 ngày hoặc sau khi xử lý |

---

## 15. Monitoring checklist

```text
[ ] /health hoạt động.
[ ] Scheduler heartbeat hoạt động.
[ ] Queue worker heartbeat hoạt động.
[ ] Failed jobs có alert.
[ ] Backup status có alert.
[ ] Wallet integrity command chạy được.
[ ] Settlement integrity command chạy được.
[ ] Audit log đủ context.
[ ] Không log password/token.
```
