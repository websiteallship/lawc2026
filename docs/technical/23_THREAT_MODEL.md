---
title: "Threat Model"
project: "Du Doan La"
version: "1.0"
status: "active"
last_updated: "2026-06-12"
owner: "Security / Engineering"
---

# Threat Model

## 1. Scope

Threat model nay ap dung cho MVP Laravel + Filament noi bo:

- Admin panel.
- Player UI.
- Wallet ledger.
- Bet placement.
- Market locking.
- Settlement/void/correction.
- CSV/XLSX import.
- VPS deployment.

Ngoai scope MVP:

- Payment.
- Public registration.
- External odds API.
- Reward redemption.

## 2. Assets

| Asset | Muc do | Ghi chu |
|---|---:|---|
| Wallet balances | Critical | Sai so du lam hong niem tin va leaderboard |
| Wallet ledgers | Critical | Bang audit bat buoc |
| Bets | Critical | Phieu du doan khong duoc mat/sua am tham |
| Settlements | Critical | Ket qua tinh la |
| Admin credentials | Critical | Co the thao tung market/settlement |
| Import files | High | Co the lam sai lich/odds |
| Audit logs | High | Can cho truy vet |
| Leaderboard | Medium | Anh huong ranking, co the rebuild |

## 3. Trust boundaries

```text
Player browser
  -> Laravel web/session
  -> Domain services
  -> PostgreSQL/Redis

Admin browser
  -> Filament admin panel
  -> Domain services
  -> PostgreSQL/Redis

Import file
  -> Import preview
  -> Validation
  -> Import execution
  -> Audit/import logs
```

## 4. Entry points

- Login/logout.
- Admin CRUD resources.
- Bet placement form/modal.
- Settlement preview/execute.
- Void/correction action.
- CSV/XLSX upload.
- Export reports.
- Queue worker jobs.
- Scheduler commands.

## 5. STRIDE summary

| Threat | Scenario | Risk | Mitigation |
|---|---|---:|---|
| Spoofing | Player truy cap admin route | High | Auth, role permission, Filament Shield, tests PERM |
| Tampering | User sua stake/outcome hidden field | High | Server-side validation, load outcome from DB, snapshot in transaction |
| Repudiation | Admin cap/tru la khong co ly do | High | Wallet ledger + activity log + required reason |
| Information disclosure | Player xem ledger/bet nguoi khac | High | Policies, scoped query, feature tests |
| Denial of service | Import file lon hoac queue settlement bi treo | Medium | File size limit, batch import, queue timeout, failed job alert |
| Elevation of privilege | Operator execute settlement | High | Permission deny, separation of duties, audit |

## 6. Critical attack paths

### 6.1. Overspend wallet khi dat sat gio

Path:

```text
User gui 2 request cung luc -> ca 2 doc available_balance cu -> ca 2 thanh cong
```

Mitigation:

- `DB::transaction`.
- `lockForUpdate()` wallet row.
- Check `available_balance >= stake` sau khi lock.
- Concurrency test `CT-001`.

### 6.2. Double settlement

Path:

```text
2 admin/job execute cung market -> wallet duoc cong payout 2 lan
```

Mitigation:

- Lock market row.
- Market status `LOCKED -> SETTLING -> SETTLED`.
- One executed settlement per market.
- Settle only `PENDING` bets.
- Test double execution.

### 6.3. Odds tampering sau khi co bet

Path:

```text
Admin sua profit_rate cua outcome -> bet cu bi tinh theo odds moi
```

Mitigation:

- Bet snapshot `profit_rate_snapshot`, `line_snapshot`, `label_snapshot`, `display_odds_snapshot`.
- Settlement dung snapshot tren bet.
- Neu outcome da co bet, chi tao version/outcome moi hoac yeu cau Super Admin.

### 6.4. Import sai lich WC2026

Path:

```text
File import sai timezone hoac match_code trung -> market dong sai gio
```

Mitigation:

- Import preview.
- Validate Asia/Ho_Chi_Minh.
- Unique `season_code + match_code`.
- Khong overwrite tran da co bet.
- Import log va file loi.

### 6.5. Insider admin abuse

Path:

```text
Admin cap la/void/correction de thao tung leaderboard
```

Mitigation:

- Permission theo role.
- Required reason.
- Activity log.
- Export audit cho auditor.
- 2FA cho admin.
- Separation of duties cho settlement/correction neu quy mo lon.

## 7. Security requirements extracted

- All admin routes require authentication.
- Player cannot access admin pages.
- Player can only view own bets and ledgers.
- Wallet mutation only through `WalletService`.
- Settlement only through `SettlementEngine`.
- Import action must preview and validate before execute.
- All sensitive admin actions require audit log.
- Production `APP_DEBUG=false`.
- HTTPS required on VPS.
- Database backup required before migration touching wallet/bet/settlement tables.

## 8. Residual risks

| Risk | Residual level | Follow-up |
|---|---:|---|
| Admin co quyen cao lam sai thao tac | Medium | Approval workflow phase 2 |
| VPS single point of failure | Medium | Backup/restore drill, later split DB |
| Manual WC2026 file sai | Medium | Dual-review import file before production |
| Correction am khi user het la | Medium | Manual review policy |

## 9. Review cadence

- Review truoc khi go-live.
- Review lai sau khi them correction full workflow.
- Review lai neu mo public access, external API, hoac reward.
