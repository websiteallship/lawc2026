---
title: "API Specification"
project: "Dự Đoán Lá"
version: "1.0"
status: "draft"
last_updated: "2026-06-11"
owner: "Engineering"
---

# API Specification

## 1. Mục tiêu

Tài liệu này chuẩn hóa API nếu hệ thống tách frontend user, mobile web hoặc cần tích hợp realtime. Nếu phase đầu dùng Filament/Livewire toàn bộ, API có thể triển khai sau nhưng nên thiết kế trước để tránh lệch domain model.

---

## 2. Nguyên tắc API

```text
- JSON only.
- Auth bằng session hoặc token tùy frontend.
- Mọi endpoint thay đổi dữ liệu phải check permission.
- Không expose dữ liệu private user khác.
- Không cho API update balance trực tiếp.
- Mọi odds hiển thị theo profit_rate kiểu Việt Nam.
```

---

## 3. Response format chuẩn

## 3.1. Success

```json
{
  "success": true,
  "data": {},
  "message": null,
  "errors": null
}
```

## 3.2. Error

```json
{
  "success": false,
  "data": null,
  "message": "Mốc dự đoán đã đóng",
  "errors": [
    {
      "code": "MARKET_CLOSED",
      "field": null,
      "detail": "Market FULL_TIME/ASIAN_HANDICAP đã đóng lúc 2026-06-12 01:55:00"
    }
  ]
}
```

---

## 4. Auth endpoints

## 4.1. Login

```text
POST /api/auth/login
```

Request:

```json
{
  "email": "player1@example.test",
  "password": "secret"
}
```

Response:

```json
{
  "success": true,
  "data": {
    "user": {
      "id": 1,
      "name": "Player One",
      "role": "player"
    }
  },
  "message": null,
  "errors": null
}
```

## 4.2. Logout

```text
POST /api/auth/logout
```

---

## 5. Match endpoints

## 5.1. List matches

```text
GET /api/matches?season=WC2026&status=open&date=2026-06-12
```

Response data item:

```json
{
  "id": 1,
  "match_code": "M001",
  "home_team": "Brazil",
  "away_team": "Germany",
  "kickoff_at": "2026-06-12T02:00:00+07:00",
  "status": "SCHEDULED",
  "markets_summary": {
    "open": 3,
    "locked": 0,
    "settled": 0
  }
}
```

## 5.2. Match detail

```text
GET /api/matches/{match_id}
```

Response includes match, result if available, market list.

---

## 6. Market endpoints

## 6.1. List markets for match

```text
GET /api/matches/{match_id}/markets
```

Response item:

```json
{
  "id": 10,
  "period_type": "FULL_TIME",
  "market_type": "ASIAN_HANDICAP",
  "status": "OPEN",
  "open_at": "2026-06-11T08:00:00+07:00",
  "close_at": "2026-06-12T01:55:00+07:00",
  "outcomes": [
    {
      "id": 101,
      "label": "Brazil -0.5 ăn 0.90",
      "selection_side": "HOME",
      "line_value": "-0.50",
      "profit_rate": "0.90",
      "potential_return_for_100": 190
    }
  ]
}
```

## 6.2. Market detail

```text
GET /api/markets/{market_id}
```

---

## 7. Bet endpoints

## 7.1. Place bet

```text
POST /api/bets
```

Request:

```json
{
  "market_outcome_id": 101,
  "stake": 100
}
```

Backend xử lý:

```text
- Check auth.
- Check player role.
- Check market OPEN.
- Check current_time < close_at.
- Check outcome ACTIVE.
- Check stake min/max.
- Check user available_balance.
- Snapshot line/profit_rate/label.
- Lock stake bằng wallet transaction.
- Create bet PENDING.
```

Response:

```json
{
  "success": true,
  "data": {
    "bet_id": 1001,
    "status": "PENDING",
    "selection_label": "Brazil -0.5 ăn 0.90",
    "stake": 100,
    "profit_rate_snapshot": "0.90",
    "potential_return": 190,
    "wallet": {
      "available_balance": 900,
      "locked_balance": 100
    }
  },
  "message": "Đã ghi nhận dự đoán",
  "errors": null
}
```

## 7.2. My bets

```text
GET /api/my-bets?status=PENDING&match_id=1
```

Response item:

```json
{
  "id": 1001,
  "match": "Brazil vs Germany",
  "period_type": "FULL_TIME",
  "market_type": "ASIAN_HANDICAP",
  "selection_label": "Brazil -0.5 ăn 0.90",
  "stake": 100,
  "potential_return": 190,
  "status": "PENDING",
  "placed_at": "2026-06-11T20:00:00+07:00"
}
```

---

## 8. Wallet endpoints

## 8.1. Wallet summary

```text
GET /api/wallet
```

Response:

```json
{
  "success": true,
  "data": {
    "available_balance": 900,
    "locked_balance": 100,
    "total_balance": 1000,
    "season_profit": 0,
    "total_staked": 100,
    "total_won": 0
  },
  "message": null,
  "errors": null
}
```

## 8.2. Wallet ledger

```text
GET /api/wallet/ledger?page=1
```

---

## 9. Leaderboard endpoints

```text
GET /api/leaderboard?season=WC2026&metric=net_profit
```

Response item:

```json
{
  "rank": 1,
  "user_name": "Player One",
  "total_balance": 1800,
  "net_profit": 800,
  "roi": 42.5,
  "win_rate": 55.0,
  "bets_count": 20
}
```

Không expose email nếu không cần.

---

## 10. Notification endpoints

```text
GET /api/notifications
POST /api/notifications/{id}/read
```

Notification types:

```text
BET_PLACED
MARKET_CLOSING_SOON
BET_SETTLED
MARKET_VOIDED
ADMIN_GRANT
```

---

## 11. Admin endpoints

Admin endpoints chỉ dùng nếu không thao tác qua Filament.

```text
POST /api/admin/matches
POST /api/admin/markets
POST /api/admin/outcomes
POST /api/admin/results
POST /api/admin/settlements/preview
POST /api/admin/settlements/execute
POST /api/admin/markets/{id}/void
```

Bắt buộc:

```text
- Permission check.
- Audit log.
- Idempotency key với execute settlement.
```

---

## 12. Error codes thường gặp

| HTTP | Code | Ý nghĩa |
|---:|---|---|
| 401 | UNAUTHENTICATED | Chưa đăng nhập |
| 403 | PERMISSION_DENIED | Không có quyền |
| 422 | INVALID_STAKE | Stake không hợp lệ |
| 422 | INSUFFICIENT_BALANCE | Không đủ lá |
| 409 | MARKET_CLOSED | Market đã đóng |
| 409 | ODDS_CHANGED | Tỷ lệ đã đổi, cần tải lại |
| 409 | BET_ALREADY_SETTLED | Vé đã settle |
| 409 | SETTLEMENT_ALREADY_EXECUTED | Settlement đã chạy |

---

## 13. Rate limit đề xuất

| Endpoint | Limit |
|---|---:|
| Login | 5 request/phút/IP |
| Place bet | 30 request/phút/user |
| List matches | 120 request/phút/user |
| Leaderboard | 60 request/phút/user |
| Admin import | Theo permission, không public |

---

## 14. API test checklist

```text
[ ] User chưa login không gọi được wallet.
[ ] Player không gọi được admin endpoints.
[ ] Stake âm bị chặn.
[ ] Stake vượt max bị chặn.
[ ] Market đóng bị chặn.
[ ] User không đủ lá bị chặn.
[ ] Response potential_return đúng theo profit_rate.
[ ] Endpoint không expose email user khác trong leaderboard.
```
