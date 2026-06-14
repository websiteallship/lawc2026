---
title: "Changelog & Release Plan"
project: "Dự Đoán Lá"
version: "1.0"
status: "draft"
last_updated: "2026-06-11"
owner: "Product / Engineering"
---

# Changelog & Release Plan

## 1. Mục tiêu

Tài liệu này chuẩn hóa kế hoạch release để dự án triển khai có kiểm soát.

Mục tiêu:

```text
- Biết version nào có tính năng gì.
- Không deploy thay đổi settlement thiếu test.
- Có checklist trước/sau release.
- Có rollback/forward-fix strategy.
```

---

## 2. Versioning

Dùng semantic version đơn giản:

```text
MAJOR.MINOR.PATCH
```

Ý nghĩa:

| Loại | Khi nào tăng |
|---|---|
| MAJOR | Thay đổi lớn, không tương thích dữ liệu hoặc rule |
| MINOR | Thêm module/tính năng mới |
| PATCH | Fix bug nhỏ, không đổi behavior chính |

Ví dụ:

```text
v0.1.0 Project bootstrap
v0.6.0 Settlement engine MVP
v1.0.0 MVP go-live
v1.1.0 Game hóa phase 2
```

---

## 3. Release phases

## 3.1. Phase 0 — Foundation

| Version | Nội dung |
|---|---|
| v0.1.0 | Laravel project, Filament, auth, base layout |
| v0.2.0 | Roles/permissions, admin panel structure |
| v0.3.0 | Database core migrations |
| v0.4.0 | Seed data, dev setup, CI test |

## 3.2. Phase 1 — MVP nghiệp vụ

| Version | Nội dung |
|---|---|
| v0.5.0 | Wallet ledger + grant/deduct |
| v0.6.0 | Match/market/outcome admin |
| v0.7.0 | Bet placement + odds snapshot |
| v0.8.0 | Market auto-lock |
| v0.9.0 | Settlement engine: exact score, handicap, tài/xỉu |
| v0.10.0 | Leaderboard MVP |
| v0.11.0 | Import/export + audit log |
| v1.0.0 | MVP production-ready |

## 3.3. Phase 2 — Game hóa và vận hành nâng cao

| Version | Nội dung |
|---|---|
| v1.1.0 | Achievement/badge cá nhân |
| v1.2.0 | Mission cá nhân |
| v1.3.0 | Notification |
| v1.4.0 | Approval workflow |
| v1.5.0 | Correction workflow nâng cao |
| v1.6.0 | Dashboard analytics |

---

## 4. Release checklist

## 4.1. Trước release

```text
[ ] Tất cả test pass.
[ ] Settlement test matrix pass.
[ ] Migration review xong.
[ ] Có backup nếu migration chạm bảng nhạy cảm.
[ ] Tài liệu cập nhật nếu đổi rule.
[ ] Permission matrix cập nhật nếu thêm action.
[ ] Staging UAT xong.
[ ] Không có failed jobs tồn đọng.
[ ] Có release note.
```

## 4.2. Trong release

```text
[ ] Bật maintenance mode nếu cần.
[ ] Backup database.
[ ] Deploy code.
[ ] Chạy migration.
[ ] Clear/cache Laravel.
[ ] Restart queue worker.
[ ] Kiểm tra scheduler.
[ ] Tắt maintenance mode.
```

## 4.3. Sau release

```text
[ ] Smoke test login admin.
[ ] Smoke test login player.
[ ] Kiểm tra dashboard.
[ ] Kiểm tra queue failed.
[ ] Kiểm tra logs error.
[ ] Kiểm tra health check.
[ ] Nếu có thay đổi settlement, chạy test preview trên staging/demo.
```

---

## 5. Release note template

```markdown
# Release vX.Y.Z - YYYY-MM-DD

## Added
- ...

## Changed
- ...

## Fixed
- ...

## Migration
- Có/Không
- Ảnh hưởng bảng: ...

## Risk
- Low/Medium/High

## Test
- Unit: pass
- Feature: pass
- Settlement matrix: pass

## Rollback
- Code rollback: ...
- DB rollback/forward-fix: ...
```

---

## 6. Changelog template

```markdown
## [v0.7.0] - YYYY-MM-DD

### Added
- Bet placement service.
- Odds snapshot khi đặt lá.
- Wallet lock stake.

### Changed
- UI hiển thị kèo theo kiểu “ăn 0.90”.

### Fixed
- Chặn đặt lá khi market đã đóng.
```

---

## 7. Risk classification

| Risk | Ví dụ | Yêu cầu |
|---|---|---|
| Low | Sửa text UI | Smoke test |
| Medium | Thêm màn hình admin | Permission test |
| High | Sửa wallet/settlement | Backup + full test matrix |
| Critical | Migration bảng wallet/bet | Maintenance + rollback plan |

---

## 8. Migration policy theo release

## 8.1. Bảng nhạy cảm

Nếu release chạm bảng sau, đánh risk ít nhất là High:

```text
wallets
wallet_ledgers
bets
settlements
settlement_items
market_outcomes
```

## 8.2. Không rollback tùy tiện

Với production:

```text
- Nếu migration đã chạy và có dữ liệu mới, ưu tiên forward-fix.
- Không restore database nếu chưa đánh giá mất dữ liệu phát sinh.
- Nếu cần restore, phải khóa hệ thống và thông báo rõ.
```

---

## 9. Definition of ready cho feature

Một feature sẵn sàng code khi có:

```text
[ ] Requirement rõ.
[ ] Permission rõ.
[ ] Database impact rõ.
[ ] Service/logic rõ.
[ ] Test case rõ.
[ ] UI state rõ.
[ ] Error messages rõ.
```

---

## 10. Definition of done cho release

Một release được xem là xong khi:

```text
[ ] Code merged vào main.
[ ] Tag version tạo.
[ ] Deploy staging/prod thành công.
[ ] Smoke test pass.
[ ] Không có failed job mới.
[ ] Changelog cập nhật.
[ ] Docs cập nhật nếu thay đổi nghiệp vụ.
```

---

## 11. Suggested milestones

```text
Milestone 1: Project foundation
Milestone 2: Wallet + user management
Milestone 3: Match/market/odds management
Milestone 4: Bet placement
Milestone 5: Settlement engine
Milestone 6: Leaderboard + reports
Milestone 7: UAT + security hardening
Milestone 8: Go-live nội bộ
Milestone 9: Phase 2 game hóa
```
