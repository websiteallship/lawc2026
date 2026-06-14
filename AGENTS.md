# AGENTS.md

> Repository-level instructions for AI coding agents working on this Laravel + Filament project.
>
> This file is designed for Google Antigravity-style agent workflows and other AI coding agents.
> It defines project boundaries, non-negotiable business rules, coding rules, safety rules, and task execution protocol.

---

## 0. Mandatory First Step For Every Agent

Before editing code, the agent MUST read these files in this order:

1. `docs/00_README.md`
2. `docs/MANIFEST.md`
3. `docs/technical/21_TECH_DECISIONS.md`
4. `docs/technical/22_ARCHITECTURE_DECISION_RECORDS.md`
5. `docs/product/01_PRD.md`
6. `docs/product/02_LEGAL_COMPLIANCE_POLICY.md`
7. `docs/requirements/03_SRS.md`
8. `docs/technical/04_ERD_DATABASE_DESIGN.md`
9. `docs/technical/05_SETTLEMENT_ENGINE_SPECIFICATION.md`
10. `docs/technical/17_DOMAIN_SERVICE_DESIGN.md`
11. `TASKS.md`
12. The specific task section requested by the user

If any task conflicts with the documentation, the agent MUST stop and report the conflict. Do not guess.

---

## 1. Project Identity

### Project Name

`du-doan-la`

### Product Description

Internal football prediction game for a company. Users use virtual points called **lá** to predict football results.

The product supports:

- Exact score prediction
- Asian handicap prediction
- Over/under prediction
- Match periods:
  - Full time
  - First half
  - Second half
  - Extra time
  - Penalty, when applicable
- Admin-managed users
- Admin-managed virtual leaves
- Market opening/closing times
- Automated settlement
- Individual leaderboard
- Audit logs

### Legal Positioning

This is an internal prediction game using virtual points.

It is NOT:

- A gambling platform
- A betting business
- A bookmaker system
- A money wagering system
- A public betting website

The word **cá cược** should be avoided in UI copy unless discussing legal/compliance warnings. Prefer:

- `dự đoán`
- `lá`
- `điểm ảo`
- `mốc dự đoán`
- `bảng xếp hạng`
- `tỉ lệ ăn`
- `kèo dự đoán`

---

## 2. Non-Negotiable Legal Rules

The agent MUST enforce these rules in copy, code, validation, seed data, and admin UI:

1. No real-money deposit.
2. No real-money withdrawal.
3. No conversion of lá to cash.
4. No conversion of lá to physical rewards, vouchers, services, or anything with monetary value.
5. No public registration.
6. No public access outside the company.
7. No user-to-user transfer of lá.
8. No payment integration.
9. No wallet top-up by user.
10. Admin can grant or deduct lá only through auditable ledger entries.
11. Any leaderboard or achievement is for entertainment and internal engagement only.
12. Legal disclaimer must be visible in the rules/user guide page.

If asked to add money, deposits, withdrawals, transfers, public betting, odds selling, or redeemable rewards, the agent MUST refuse the implementation and point to `docs/product/02_LEGAL_COMPLIANCE_POLICY.md`.

---

## 3. Vietnamese Odds Convention

This project uses Vietnamese-style odds wording:

```txt
Brazil -0.5 ăn 0.90
Tài 2.5 ăn 0.90
Tỉ số 2-1 ăn 7.00
```

### Internal Meaning

`ăn 0.90` means:

```txt
profit_rate = 0.90
full_win_gross_payout = stake × (1 + profit_rate)
net_profit = stake × profit_rate
```

Example:

```txt
Selection: Brazil -0.5 ăn 0.90
Stake: 100 lá
If full win: payout = 100 × (1 + 0.90) = 190 lá
If push: payout = 100 lá
If lose: payout = 0 lá
```

### Critical Rule

The system MUST NOT treat `0.90` as decimal odds.

Wrong:

```txt
payout = stake × 0.90
```

Correct:

```txt
payout = stake × (1 + 0.90)
```

### Storage Rule

Use `profit_rate` as the canonical stored value.

Recommended:

```txt
profit_rate decimal(8,3)
display_odds_snapshot string, e.g. "Brazil -0.5 ăn 0.90"
```

Do not store `decimal_odds` as the source of truth unless explicitly required. If needed, derive it:

```txt
decimal_odds = 1 + profit_rate
```

---

## 4. Business Logic Boundaries

The following logic MUST NOT be implemented inside Filament Resources, Blade components, Livewire components, Controllers, or route closures.

It must live in domain services:

| Logic | Required service/class |
|---|---|
| Grant/deduct lá | `WalletService` |
| Lock stake | `WalletService` |
| Release locked stake | `WalletService` |
| Place prediction | `BetPlacementService` |
| Exact score settlement | `ExactScoreSettlementCalculator` |
| Asian handicap settlement | `AsianHandicapSettlementCalculator` |
| Over/under settlement | `OverUnderSettlementCalculator` |
| Settlement orchestration | `SettlementEngine` |
| Market locking | `MarketLockService` |
| Leaderboard calculation | `LeaderboardService` |
| Correction/re-settlement | `CorrectionService` |
| Audit trail | `AuditLogService` or Spatie Activitylog wrapper |

UI layers may only call services.

Wrong:

```php
// Do not calculate payout directly in a Filament action.
$payout = $stake * (1 + $profitRate);
$user->wallet->available_balance += $payout;
```

Correct:

```php
app(SettlementEngine::class)->settleMarket($market, $resultDto);
```

---

## 5. Wallet And Ledger Rules

### Canonical Rule

Every lá movement MUST be represented by a `wallet_ledgers` record.

Never update wallet balances without a ledger row.

### Wallet Fields

The wallet should contain at least:

```txt
available_balance
locked_balance
total_balance = available_balance + locked_balance
```

### Ledger Types

The project should support these ledger types:

```txt
ADMIN_GRANT
ADMIN_DEDUCT
BET_PLACED
BET_WON
BET_LOST
BET_PUSH
BET_HALF_WON
BET_HALF_LOST
BET_VOIDED
SETTLEMENT_CORRECTION
SEASON_RESET
```

These names follow the canonical ERD. Add future ledger types such as cancellation or system adjustment only after updating `docs/technical/04_ERD_DATABASE_DESIGN.md`, settlement/wallet tests, and operation docs.

### Transaction Rules

Any wallet-changing operation MUST use:

- DB transaction
- `lockForUpdate()` on wallet row
- validation before mutation
- ledger insertion in the same transaction
- idempotency protection where applicable

### Negative Balance

Negative `available_balance` is forbidden.

`locked_balance` cannot be negative.

---

## 6. Bet Placement Rules

`BetPlacementService::placeBet()` MUST perform all checks in this order:

1. Start DB transaction.
2. Lock the user wallet row.
3. Verify user is active.
4. Verify market exists.
5. Verify market status is `OPEN`.
6. Verify current time is before `close_at`.
7. Verify outcome is active.
8. Verify stake is integer and positive.
9. Verify stake >= configured minimum.
10. Verify stake <= configured maximum per bet.
11. Verify stake does not exceed maximum per match/user if configured.
12. Verify available balance >= stake.
13. Snapshot `profit_rate`, line, market type, period type, label, display odds, and market close time.
14. Create bet with status `PENDING`.
15. Move stake from available to locked via `WalletService::lockStake()`.
16. Create `BET_PLACED` ledger.
17. Commit transaction.

If any check fails, rollback transaction and return a typed domain exception/error code.

---

## 7. Market Status Rules

Use explicit state transitions.

Recommended statuses:

```txt
DRAFT
OPEN
LOCKED
SETTLING
SETTLED
VOIDED
CANCELLED
```

Allowed transitions:

```txt
DRAFT -> OPEN
OPEN -> LOCKED
OPEN -> VOIDED
LOCKED -> SETTLING
SETTLING -> SETTLED
SETTLING -> VOIDED
SETTLED -> CORRECTION_PENDING, if correction module exists
```

Disallowed:

```txt
SETTLED -> OPEN
VOIDED -> OPEN
CANCELLED -> OPEN
```

The agent MUST NOT add shortcuts unless docs are updated.

---

## 8. Settlement Rules

Settlement MUST be:

- deterministic
- idempotent
- auditable
- tested
- reversible only through correction entries, not deletion

### Full Win

```txt
gross_payout = stake × (1 + profit_rate)
```

### Lose

```txt
gross_payout = 0
```

### Push

```txt
gross_payout = stake
```

### Half Win

```txt
gross_payout = (stake / 2) × (1 + profit_rate) + (stake / 2)
```

### Half Lose

```txt
gross_payout = stake / 2
```

### Rounding

Use integer leaves.

Recommended default:

```txt
Round each final gross payout to nearest integer using PHP round(..., 0, PHP_ROUND_HALF_UP), then cast to int.
```

If this rule changes, update:

- `docs/technical/05_SETTLEMENT_ENGINE_SPECIFICATION.md`
- settlement tests
- user guide examples

---

## 9. Asian Handicap Rules

Supported lines:

```txt
0
±0.25
±0.5
±0.75
±1
±1.25
±1.5
±1.75
±2
±2.25
±2.5
±2.75
±3
```

Quarter-line splitting:

```txt
-0.25 = split into 0 and -0.5
-0.75 = split into -0.5 and -1
+0.25 = split into 0 and +0.5
+0.75 = split into +0.5 and +1
```

For handicap settlement, calculate adjusted score/difference and evaluate each split line independently.

The stake must be split into equal halves for quarter lines.

---

## 10. Over/Under Rules

Supported total lines:

```txt
0.5
0.75
1
1.25
1.5
1.75
2
2.25
2.5
2.75
3
3.25
3.5
3.75
4+
```

Quarter-line splitting:

```txt
2.25 = split into 2.0 and 2.5
2.75 = split into 2.5 and 3.0
```

Example:

```txt
Tài 2.25 ăn 0.90
Stake 100
Total goals = 2
Over 2.0 = push for 50
Over 2.5 = lose for 50
Gross payout = 50
```

```txt
Xỉu 2.25 ăn 0.90
Stake 100
Total goals = 2
Under 2.0 = push for 50
Under 2.5 = win for 50
Gross payout = 50 + 95 = 145
```

---

## 11. Exact Score Rules

Exact score uses direct score match.

```txt
Tỉ số 2-1 ăn 7.00
Stake 100
If result is 2-1: payout = 100 × (1 + 7.00) = 800
If not: payout = 0
```

For exact score, `profit_rate` may be larger than common handicap odds.

---

## 12. Data Integrity Rules

### Never Delete Historical Data

The agent MUST NOT hard-delete:

- settled bets
- settlement rows
- settlement items
- wallet ledgers
- audit logs
- correction logs

Use status fields, void entries, correction entries, or soft deletes where explicitly documented.

### Odds Snapshot Rule

When a user places a bet, store snapshot values on the bet:

```txt
market_type_snapshot
period_type_snapshot
label_snapshot
line_snapshot
profit_rate_snapshot
display_odds_snapshot
close_at_snapshot
```

Changing odds after a bet is placed MUST NOT affect existing bets.

---

## 13. Security Rules

Mandatory:

1. Admin 2FA should be supported or planned.
2. Admin routes must require authentication.
3. Authorization must use roles/permissions, preferably Filament Shield + Spatie Permission.
4. No debug mode in production.
5. No `.env` content in logs or commits.
6. No command that deletes user data without explicit confirmation.
7. No destructive migration without documented backup/rollback.
8. No raw SQL using untrusted input.
9. No user-supplied HTML unless sanitized.
10. All admin-sensitive actions must be logged.

Agents MUST NOT read or print secrets from `.env`, deployment files, private keys, or production dumps.

---

## 14. Framework And Stack Rules

Preferred stack:

```txt
Laravel
Filament
Livewire
Alpine.js
Tailwind CSS
PostgreSQL
Redis
Queue workers
Scheduler
```

Recommended packages:

```txt
filament/filament
bezhansalleh/filament-shield
spatie/laravel-permission
jeffgreco13/filament-breezy
spatie/laravel-activitylog
pxlrbt/filament-activity-log
spatie/laravel-settings
filament/spatie-laravel-settings-plugin
leandrocfe/filament-apex-charts
spatie/laravel-backup
```

### Icon Rule

The agent MUST NOT use character-based icons in UI, including:

```txt
✓ ✕ ✖ + - → ← ↑ ↓ ★ ☆ • …
```

Do not use Unicode symbols, emoji, or punctuation as a substitute for UI icons in:

- Filament actions
- navigation items
- buttons
- badges
- alerts
- status indicators
- empty states

The agent MUST use the appropriate icon library supported by the stack instead.

Preferred choices:

- Filament UI: Heroicons via the Filament icon system
- Frontend packages if added later: the framework-standard icon library compatible with that package

If an icon is needed, use the icon component/API, not hardcoded display characters in labels.

Do not add packages unless:

1. The task requires it.
2. The package is actively maintained.
3. It is compatible with the Laravel/Filament version in this repo.
4. The package does not bypass security, auth, wallet, or settlement rules.

---

## 15. Code Organization Rules

Preferred structure:

```txt
app/
  Domain/
    Wallet/
    Betting/
    Settlement/
    Market/
    Leaderboard/
  Enums/
  Exceptions/
  Filament/
    Admin/
    User/
  Models/
  Policies/
  Services/
```

Keep domain code framework-light where practical.

Controllers, Filament Resources, Livewire components, and console commands should orchestrate services, not implement core math.

---

## 16. Testing Rules

The agent MUST write or update tests for any change affecting:

- wallet balance
- ledger entries
- bet placement
- market lock timing
- exact score payout
- Asian handicap payout
- over/under payout
- settlement idempotency
- void/correction
- leaderboard ranking
- permission checks

Minimum test commands before declaring done:

```bash
php artisan test
```

If formatting/static analysis is configured:

```bash
./vendor/bin/pint
./vendor/bin/phpstan analyse
```

Do not claim tests passed unless actually run.

---

## 17. Agent Execution Protocol

For every task, follow this sequence:

1. Read relevant docs.
2. Identify files to edit.
3. State a short implementation plan.
4. Make minimal scoped changes.
5. Add/update tests.
6. Run relevant tests.
7. Summarize changed files.
8. List any remaining risks or follow-up tasks.

Do not edit unrelated files.

Do not reformat the whole repo unless the task explicitly asks.

Do not change public APIs, database columns, statuses, or enum names without updating docs and tests.

---

## 18. Model Usage Guidance

Use a reasoning/Pro model for:

- database design
- settlement logic
- wallet/ledger logic
- race condition review
- security review
- architecture decisions

Use a fast model for:

- Filament CRUD scaffolding
- forms/tables/widgets
- factories/seeders
- simple tests
- UI copy
- repetitive refactors

If uncertain, choose the reasoning/Pro model for anything involving money-like point accounting, settlement, or security.

---

## 19. Done Definition

A task is done only if:

1. The implementation matches docs.
2. Relevant tests exist.
3. Relevant tests pass.
4. Wallet changes go through ledger.
5. Settlement behavior is covered by test cases.
6. No legal boundary is violated.
7. No unrelated files are changed.
8. The response includes changed files and verification steps.

---

## 20. Red Flags Requiring Human Review

Stop and ask for human review if a task requires:

- Adding real-money features
- Adding deposit/withdrawal/payment
- Allowing user-to-user lá transfer
- Changing settlement math
- Changing Vietnamese odds convention
- Deleting settled data
- Editing ledger balances manually
- Running destructive database commands
- Adding external APIs that transmit private user data
- Deploying to production
- Handling credentials/secrets

---

## 21. Known Issues & Quirks

### Filament v3 Action Namespaces

In Filament v3, all Action classes (e.g., `Action`, `BulkAction`, `EditAction`, `DeleteBulkAction`, `BulkActionGroup`) have been moved to the standalone `Filament\Actions` namespace. 
They no longer reside in `Filament\Tables\Actions` or `Filament\Forms\Actions`.
The agent MUST NOT use namespaces like `Filament\Tables\Actions\BulkActionGroup` or `Filament\Tables\Actions\DeleteBulkAction`.
Instead, use:
```php
use Filament\Actions\BulkActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\DeleteBulkAction;
```
Do not hallucinate v2 namespaces for v3 projects.
