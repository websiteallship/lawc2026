# TASKS.md

> Execution backlog for AI coding agents working in Google Antigravity or similar agentic IDEs.
>
> Use this file as the task router. Do not ask an agent to “build the whole app”. Execute one task or one small task group at a time.

---

## 0. How To Use This File

Each task includes:

- **Goal**: what must be produced
- **Model**: recommended model type
- **Read first**: docs the agent must inspect
- **Files expected**: likely file areas
- **Acceptance criteria**: what must be true before done
- **Tests**: required verification

### Model Guidance

| Task type | Recommended model |
|---|---|
| Architecture, DB, settlement, wallet, security | Reasoning/Pro model |
| CRUD, UI scaffolding, seeder, factory, simple refactor | Fast model |
| Review of settlement or ledger | Reasoning/Pro model |

### Strict Rules

1. Do not skip docs.
2. Do not code business logic in UI resources.
3. Do not update wallet balances outside `WalletService`.
4. Do not settle outside `SettlementEngine`.
5. Do not change Vietnamese odds convention.
6. Do not delete historical betting/ledger/settlement data.
7. Always add or update tests for domain logic.
8. Use small commits/tasks.

---

## 1. Milestone Overview

```txt
M0 - Repository and project bootstrap
M1 - Domain foundation and migrations
M2 - Wallet ledger and bet placement
M3 - Settlement engine
M4 - Admin Filament panel
M5 - User-facing prediction flow
M6 - Import/export and seed data
M7 - Leaderboard, notification, reports
M8 - Security, QA, staging readiness
```

---

# M0 — Repository And Project Bootstrap

## T001 — Initialize Laravel Project

**Goal**: Create the base Laravel application structure.

**Model**: Fast model

**Read first**:

- `AGENTS.md`
- `docs/technical/11_DEV_SETUP_GUIDE.md`
- `docs/technical/21_TECH_DECISIONS.md`
- `docs/reference/25_LARAVEL_FILAMENT_TECH_STACK_RESEARCH.md`

**Files expected**:

```txt
composer.json
.env.example
app/
routes/
database/
tests/
```

**Acceptance criteria**:

- Laravel app boots locally.
- `.env.example` contains required variables for PostgreSQL, Redis, queue, mail, app URL.
- No secrets are committed.
- Basic test command works.

**Tests**:

```bash
php artisan test
```

---

## T002 — Install And Configure Filament

**Goal**: Install Filament and create admin panel baseline.

**Model**: Fast model

**Read first**:

- `docs/technical/11_DEV_SETUP_GUIDE.md`
- `docs/reference/25_LARAVEL_FILAMENT_TECH_STACK_RESEARCH.md`

**Files expected**:

```txt
app/Providers/Filament/
app/Filament/
config/filament.php
```

**Acceptance criteria**:

- Admin panel route works.
- Filament login works.
- Admin user can be created.
- No betting business logic is implemented in Filament yet.

**Tests**:

```bash
php artisan test
```

---

## T003 — Install Core Packages

**Goal**: Install and configure required packages.

**Model**: Fast model

**Read first**:

- `AGENTS.md`
- `docs/technical/13_SECURITY_CHECKLIST.md`
- `docs/reference/25_LARAVEL_FILAMENT_TECH_STACK_RESEARCH.md`

**Required packages**:

```txt
Filament Shield
Spatie Permission
Filament Breezy
Spatie Activitylog
Filament Activity Log
Spatie Settings
Filament Settings Plugin
```

**Acceptance criteria**:

- Packages installed and published as needed.
- Config files committed.
- Migrations generated where needed.
- No package introduces payment, public registration, or point transfer features.

**Tests**:

```bash
php artisan test
```

---

## T004 — Configure Code Quality Tooling

**Goal**: Add formatting/static analysis/test tooling.

**Model**: Fast model

**Read first**:

- `docs/technical/11_DEV_SETUP_GUIDE.md`

**Recommended tools**:

```txt
Laravel Pint
PHPUnit or Pest
PHPStan or Larastan, if selected
```

**Acceptance criteria**:

- Formatting command documented.
- Test command documented.
- CI-ready scripts added to README or composer scripts.

**Tests**:

```bash
php artisan test
./vendor/bin/pint --test
```

---

# M1 — Domain Foundation And Migrations

## T005 — Create Domain Enums

**Goal**: Add enum classes for all domain statuses and types.

**Model**: Reasoning/Pro model

**Read first**:

- `docs/technical/04_ERD_DATABASE_DESIGN.md`
- `docs/technical/05_SETTLEMENT_ENGINE_SPECIFICATION.md`
- `docs/technical/17_DOMAIN_SERVICE_DESIGN.md`

**Enums required**:

```txt
MatchStatus
MarketStatus
MarketType
PeriodType
BetStatus
SettlementStatus
WalletLedgerType
UserRole
PredictionResultType
```

**Acceptance criteria**:

- Enum values match docs.
- No duplicate naming between bet status and settlement result.
- Enum usage is suitable for Eloquent casts.

**Tests**:

```bash
php artisan test
```

---

## T006 — Create Database Migrations

**Goal**: Implement migrations based on ERD.

**Model**: Reasoning/Pro model

**Read first**:

- `docs/technical/04_ERD_DATABASE_DESIGN.md`
- `docs/technical/21_TECH_DECISIONS.md`
- `docs/product/02_LEGAL_COMPLIANCE_POLICY.md`

**Tables required for MVP**:

```txt
users
seasons
matches
match_period_results
markets
market_outcomes
wallets
wallet_ledgers
bets
settlements
settlement_items
leaderboard_snapshots
system_settings
```

**Critical fields**:

```txt
market_outcomes.profit_rate
bets.profit_rate_snapshot
bets.label_snapshot
bets.display_odds_snapshot
bets.close_at_snapshot
wallets.available_balance
wallets.locked_balance
wallet_ledgers.amount_available
wallet_ledgers.amount_locked
wallet_ledgers.balance_available_after
wallet_ledgers.balance_locked_after
```

**Acceptance criteria**:

- Migrations run from empty database.
- Foreign keys are defined.
- Useful indexes exist for user_id, match_id, market_id, status, kickoff_at, close_at.
- `profit_rate`, not decimal odds, is canonical.
- Settled data is not designed for hard deletion.

**Tests**:

```bash
php artisan migrate:fresh
php artisan test
```

---

## T007 — Create Models And Relationships

**Goal**: Create Eloquent models and relationships.

**Model**: Fast model, then Pro review

**Read first**:

- `docs/technical/04_ERD_DATABASE_DESIGN.md`

**Models expected**:

```txt
Season
MatchGame or FootballMatch
MatchPeriod
Market
MarketOutcome
Wallet
WalletLedger
Bet
Settlement
SettlementItem
LeaderboardSnapshot
```

**Acceptance criteria**:

- Relationships match ERD.
- Enum casts are configured.
- Fillable/guarded fields are safe.
- No hidden mass assignment for balances without service.

**Tests**:

```bash
php artisan test
```

---

## T008 — Seed Roles, Permissions, And Initial Settings

**Goal**: Configure baseline roles/permissions/settings.

**Model**: Fast model

**Read first**:

- `docs/requirements/06_PERMISSION_MATRIX.md`
- `docs/technical/13_SECURITY_CHECKLIST.md`

**Roles**:

```txt
super_admin
operator
settlement_manager
auditor
user
```

**Settings**:

```txt
default_starting_leaves
min_stake
max_stake_per_bet
max_stake_per_match
max_stake_per_day
allow_negative_balance = false
allow_user_transfer = false
```

**Acceptance criteria**:

- Permissions match matrix.
- Super admin has all permissions.
- User does not have admin permissions.
- Settings can be read from code.

**Tests**:

```bash
php artisan migrate:fresh --seed
php artisan test
```

---

# M2 — Wallet Ledger And Bet Placement

## T009 — Implement WalletService

**Goal**: Implement canonical wallet operations.

**Model**: Reasoning/Pro model

**Read first**:

- `AGENTS.md`
- `docs/technical/17_DOMAIN_SERVICE_DESIGN.md`
- `docs/technical/04_ERD_DATABASE_DESIGN.md`

**Methods expected**:

```php
WalletService::grant(User $user, int $amount, string $reason, ?User $actor = null): WalletLedger
WalletService::deduct(User $user, int $amount, string $reason, ?User $actor = null): WalletLedger
WalletService::lockStake(User $user, int $amount, Bet $bet): WalletLedger
WalletService::releaseStake(User $user, int $amount, Bet $bet, string $reason): WalletLedger
WalletService::settlePayout(User $user, int $grossPayout, Bet $bet, string $resultType): WalletLedger
```

**Acceptance criteria**:

- Uses DB transaction where needed.
- Uses wallet row lock.
- Prevents negative balances.
- Always writes ledger.
- Does not allow user-to-user transfer.

**Tests**:

```txt
- grant creates ledger and increases available
- deduct creates ledger and decreases available
- lockStake decreases available and increases locked
- releaseStake decreases locked and increases available
- settlePayout decreases locked and increases available by gross payout
- cannot lock more than available
- cannot produce negative locked balance
```

**Command**:

```bash
php artisan test --filter=WalletServiceTest
```

---

## T010 — Implement BetPlacementService

**Goal**: Implement prediction placement with odds snapshot.

**Model**: Reasoning/Pro model

**Read first**:

- `AGENTS.md`
- `docs/requirements/03_SRS.md`
- `docs/technical/17_DOMAIN_SERVICE_DESIGN.md`
- `docs/technical/18_ERROR_CODES_AND_MESSAGES.md`

**Acceptance criteria**:

- Checks user active.
- Checks market open and not past close time.
- Checks outcome active.
- Checks stake limits.
- Checks available balance.
- Creates bet with snapshots.
- Calls `WalletService::lockStake()`.
- Returns clear error codes.

**Tests**:

```txt
- user can place valid bet
- market closed rejects bet
- insufficient balance rejects bet
- odds snapshot persists after outcome odds changed
- stake below min rejected
- stake above max rejected
- two concurrent placements cannot overspend wallet
```

**Command**:

```bash
php artisan test --filter=BetPlacementServiceTest
```

---

## T011 — Implement Domain Exceptions And Error Codes

**Goal**: Standardize errors.

**Model**: Fast model

**Read first**:

- `docs/technical/18_ERROR_CODES_AND_MESSAGES.md`

**Acceptance criteria**:

- Domain exceptions map to documented error codes.
- UI/API can display Vietnamese messages.
- No raw SQL/database exception should leak to user.

**Tests**:

```bash
php artisan test
```

---

# M3 — Settlement Engine

## T012 — Implement ExactScoreSettlementCalculator

**Goal**: Calculate exact score results.

**Model**: Reasoning/Pro model

**Read first**:

- `docs/technical/05_SETTLEMENT_ENGINE_SPECIFICATION.md`
- `docs/planning/22_PHASE_1_MVP_DETAILED_SPEC.md`

**Acceptance criteria**:

- Exact match wins.
- Non-match loses.
- Uses `profit_rate`.
- Returns result DTO, not DB changes.

**Tests**:

```txt
Tỉ số 2-1 ăn 7.00, stake 100, result 2-1 => payout 800
Tỉ số 2-1 ăn 7.00, stake 100, result 1-1 => payout 0
```

---

## T013 — Implement AsianHandicapSettlementCalculator

**Goal**: Calculate Asian handicap payouts.

**Model**: Reasoning/Pro model only

**Read first**:

- `AGENTS.md`
- `docs/technical/05_SETTLEMENT_ENGINE_SPECIFICATION.md`
- `docs/planning/22_PHASE_1_MVP_DETAILED_SPEC.md`

**Acceptance criteria**:

- Supports whole, half, and quarter lines.
- Quarter lines split stake into halves.
- Handles win, lose, push, half win, half lose.
- Uses Vietnamese `profit_rate`.
- Calculator has no DB mutation.

**Required test cases**:

```txt
Home -0.5 ăn 0.90, stake 100, Home wins by 1 => payout 190
Home -0.5 ăn 0.90, stake 100, draw => payout 0
Home 0 ăn 0.90, stake 100, draw => payout 100
Home -1 ăn 0.90, stake 100, Home wins by 1 => payout 100
Home -0.75 ăn 0.90, stake 100, Home wins by 1 => payout 145
Home -0.25 ăn 0.90, stake 100, draw => payout 50
Away +0.75 ăn 0.90, stake 100, Away loses by 1 => payout 50
Away +1 ăn 0.90, stake 100, Away loses by 1 => payout 100
```

---

## T014 — Implement OverUnderSettlementCalculator

**Goal**: Calculate Asian total over/under payouts.

**Model**: Reasoning/Pro model only

**Read first**:

- `docs/technical/05_SETTLEMENT_ENGINE_SPECIFICATION.md`
- `docs/planning/22_PHASE_1_MVP_DETAILED_SPEC.md`

**Acceptance criteria**:

- Supports whole, half, and quarter totals.
- Quarter totals split stake into halves.
- Handles win, lose, push, half win, half lose.
- Uses `profit_rate`.
- Calculator has no DB mutation.

**Required test cases**:

```txt
Tài 2.5 ăn 0.90, stake 100, total 3 => payout 190
Tài 2.5 ăn 0.90, stake 100, total 2 => payout 0
Xỉu 2.5 ăn 0.90, stake 100, total 2 => payout 190
Tài 2.25 ăn 0.90, stake 100, total 2 => payout 50
Xỉu 2.25 ăn 0.90, stake 100, total 2 => payout 145
Tài 2.75 ăn 0.90, stake 100, total 3 => payout 145
Xỉu 2.75 ăn 0.90, stake 100, total 3 => payout 50
```

---

## T015 — Implement SettlementEngine Orchestrator

**Goal**: Execute settlement for a market.

**Model**: Reasoning/Pro model only

**Read first**:

- `docs/technical/05_SETTLEMENT_ENGINE_SPECIFICATION.md`
- `docs/technical/17_DOMAIN_SERVICE_DESIGN.md`
- `docs/technical/22_ARCHITECTURE_DECISION_RECORDS.md`
- `docs/operation/08_TEST_PLAN.md`

**Acceptance criteria**:

- Settlement is idempotent.
- Locks market or settlement row to prevent duplicate execution.
- Loads pending bets.
- Calls correct calculator.
- Calls WalletService to settle payout.
- Creates settlement and settlement_items.
- Updates bet statuses.
- Writes audit/activity log.
- Does not delete old data.

**Tests**:

```txt
- settle exact score market
- settle handicap market
- settle over/under market
- executing settlement twice does not double-pay
- settlement failure rolls back all wallet changes
```

---

## T016 — Implement VoidMarketService

**Goal**: Void a market and return locked stakes.

**Model**: Reasoning/Pro model

**Read first**:

- `docs/technical/05_SETTLEMENT_ENGINE_SPECIFICATION.md`
- `docs/operation/09_ADMIN_OPERATION_MANUAL.md`

**Acceptance criteria**:

- Only pending bets are voided.
- Locked stakes return to available balance.
- Ledger entries are created.
- Market status becomes `VOIDED`.
- Reason is required.
- Audit log is written.

---

## T017 — Implement CorrectionService Skeleton

**Goal**: Provide safe structure for future correction/re-settlement.

**Model**: Reasoning/Pro model

**Read first**:

- `docs/planning/23_PHASE_2_ENHANCEMENT_SPEC.md`
- `docs/technical/17_DOMAIN_SERVICE_DESIGN.md`
- `docs/operation/21_CORRECTION_OPERATION_POLICY.md`

**Acceptance criteria**:

- Correction does not hard-delete settlement history.
- Correction is separate from MVP settlement.
- If full implementation is deferred, create interfaces/notes/tests marking expected behavior.

---

# M4 — Admin Filament Panel

## T018 — Build UserResource And Wallet Actions

**Goal**: Manage users and grant/deduct lá.

**Model**: Fast model, Pro review for wallet actions

**Read first**:

- `docs/requirements/06_PERMISSION_MATRIX.md`
- `docs/operation/09_ADMIN_OPERATION_MANUAL.md`

**Acceptance criteria**:

- Super admin can create/edit users.
- Admin can grant/deduct only through `WalletService`.
- No direct balance editing field is exposed.
- Activity log records action.

---

## T019 — Build Season And Match Resources

**Goal**: Manage seasons and WC2026 fixtures.

**Model**: Fast model

**Read first**:

- `docs/requirements/07_UI_UX_WIREFRAME.md`
- `docs/operation/14_DATA_IMPORT_TEMPLATES.md`

**Acceptance criteria**:

- Admin can create/update matches.
- Can filter by status/date.
- Can publish/lock/cancel through actions.
- No settlement logic included here.

---

## T020 — Build Market And Outcome Resources

**Goal**: Manage prediction markets and odds.

**Model**: Fast model, Pro review for odds fields

**Read first**:

- `docs/technical/05_SETTLEMENT_ENGINE_SPECIFICATION.md`
- `docs/operation/09_ADMIN_OPERATION_MANUAL.md`

**Acceptance criteria**:

- Admin enters `profit_rate` as `ăn`, e.g. `0.90`.
- UI warns not to enter `1.90` unless intentionally meaning `ăn 1.90`.
- Display label shows `Brazil -0.5 ăn 0.90`.
- If existing bets exist, odds changes do not affect snapshots.

---

## T021 — Build Settlement Admin Flow

**Goal**: Admin can enter result, preview settlement, execute settlement.

**Model**: Reasoning/Pro model

**Read first**:

- `docs/technical/05_SETTLEMENT_ENGINE_SPECIFICATION.md`
- `docs/operation/09_ADMIN_OPERATION_MANUAL.md`
- `docs/requirements/07_UI_UX_WIREFRAME.md`

**Acceptance criteria**:

- Preview shows all bets and expected payout.
- Execute calls `SettlementEngine` only.
- Cannot execute twice.
- Requires permission.
- Audit log records actor/time/result.

---

## T022 — Build Audit Log And Settings Pages

**Goal**: Provide admin visibility and configuration.

**Model**: Fast model

**Read first**:

- `docs/technical/13_SECURITY_CHECKLIST.md`
- `docs/technical/19_OBSERVABILITY_LOGGING_MONITORING.md`

**Acceptance criteria**:

- Audit log is read-only.
- Settings are permission-protected.
- Legal settings cannot enable prohibited behavior.

---

# M5 — User-Facing Prediction Flow

## T023 — Build User Dashboard

**Goal**: Show balance, upcoming matches, active bets.

**Model**: Fast model

**Read first**:

- `docs/requirements/07_UI_UX_WIREFRAME.md`
- `docs/product/10_USER_GUIDE_THE_LE.md`

**Acceptance criteria**:

- User sees available and locked lá.
- User sees upcoming open markets.
- Legal disclaimer or link to rules is present.

---

## T024 — Build Match Detail And Prediction Modal

**Goal**: Allow user to place prediction safely.

**Model**: Fast model, Pro review for placement flow

**Read first**:

- `docs/requirements/07_UI_UX_WIREFRAME.md`
- `docs/technical/18_ERROR_CODES_AND_MESSAGES.md`

**Acceptance criteria**:

- User sees display odds in Vietnamese format.
- Confirmation modal shows potential payout.
- Placement calls `BetPlacementService` only.
- If market closes while modal is open, placement is rejected with clear error.

---

## T025 — Build My Bets And Wallet History Pages

**Goal**: User can inspect predictions and ledger history.

**Model**: Fast model

**Read first**:

- `docs/requirements/07_UI_UX_WIREFRAME.md`
- `docs/product/10_USER_GUIDE_THE_LE.md`

**Acceptance criteria**:

- User sees only own bets.
- User sees pending/won/lost/push/voided states.
- User sees ledger entries for own wallet.
- No user can view another user's private details.

---

## T026 — Build Leaderboard Page

**Goal**: Show individual leaderboard.

**Model**: Fast model

**Read first**:

- `docs/planning/23_PHASE_2_ENHANCEMENT_SPEC.md`
- `docs/requirements/07_UI_UX_WIREFRAME.md`

**Acceptance criteria**:

- No department/team leaderboard.
- Ranking supports total balance, net profit, ROI, win rate where available.
- ROI has minimum stake/bet threshold if enabled.

---

# M6 — Import, Export, Seed Data

## T027 — Implement CSV Importers

**Goal**: Import users, fixtures, markets, odds, wallet grants.

**Model**: Fast model

**Read first**:

- `docs/operation/14_DATA_IMPORT_TEMPLATES.md`
- `docs/operation/23_WC2026_FILE_IMPORT_POLICY.md`
- `docs/operation/import_samples/`

**Acceptance criteria**:

- Valid CSV imports succeed.
- Invalid rows produce clear errors.
- Odds use `profit_rate`.
- Wallet grants call `WalletService`.

---

## T028 — Implement Seed Data

**Goal**: Create dev/staging sample data.

**Model**: Fast model

**Read first**:

- `docs/operation/15_SEED_DATA_SPECIFICATION.md`

**Acceptance criteria**:

- `php artisan migrate:fresh --seed` creates usable local app.
- Demo user has wallet.
- Demo matches have markets and outcomes.
- Sample odds use Vietnamese format.

---

## T029 — Implement Export Reports

**Goal**: Export bets, wallet ledger, settlements, leaderboard.

**Model**: Fast model

**Read first**:

- `docs/operation/09_ADMIN_OPERATION_MANUAL.md`
- `docs/technical/19_OBSERVABILITY_LOGGING_MONITORING.md`

**Acceptance criteria**:

- Export permission protected.
- Export does not include secrets.
- Export fields match admin operations needs.

---

# M7 — Scheduler, Notifications, Leaderboard

## T030 — Implement Market Auto-Lock Scheduler

**Goal**: Automatically lock markets after close time.

**Model**: Reasoning/Pro model

**Read first**:

- `docs/requirements/03_SRS.md`
- `docs/technical/17_DOMAIN_SERVICE_DESIGN.md`

**Acceptance criteria**:

- Scheduler locks markets with `close_at <= now`.
- Placement service still checks close time even if scheduler is delayed.
- Lock operation is idempotent.

---

## T031 — Implement LeaderboardService

**Goal**: Calculate individual rankings.

**Model**: Reasoning/Pro model

**Read first**:

- `docs/planning/23_PHASE_2_ENHANCEMENT_SPEC.md`
- `docs/technical/17_DOMAIN_SERVICE_DESIGN.md`

**Acceptance criteria**:

- No department/team calculation.
- Rankings are deterministic.
- Supports season scope.
- Can be recalculated safely.

---

## T032 — Implement Basic Notifications

**Goal**: Notify users/admin about key events.

**Model**: Fast model

**Read first**:

- `docs/planning/23_PHASE_2_ENHANCEMENT_SPEC.md`

**Acceptance criteria**:

- Bet settled notification.
- Market voided notification.
- Admin grant notification.
- No spammy or gambling-like wording.

---

# M8 — Security, QA, Staging

## T033 — Security Review Pass

**Goal**: Review auth, permissions, sensitive flows.

**Model**: Reasoning/Pro model

**Read first**:

- `AGENTS.md`
- `docs/technical/13_SECURITY_CHECKLIST.md`
- `docs/requirements/06_PERMISSION_MATRIX.md`

**Acceptance criteria**:

- Permission checks verified.
- Admin-only actions protected.
- Wallet balance cannot be changed through mass assignment.
- User cannot access other user data.
- No secrets in repo.

---

## T034 — Settlement Regression Test Matrix

**Goal**: Ensure settlement matches docs.

**Model**: Reasoning/Pro model

**Read first**:

- `docs/technical/05_SETTLEMENT_ENGINE_SPECIFICATION.md`
- `docs/operation/08_TEST_PLAN.md`

**Acceptance criteria**:

- Exact score matrix exists.
- Asian handicap matrix exists.
- Over/under matrix exists.
- Quarter-line examples are included.
- Tests pass.

---

## T035 — Deployment Readiness

**Goal**: Prepare staging/production deployment.

**Model**: Reasoning/Pro model

**Read first**:

- `docs/technical/12_DEPLOYMENT_INFRASTRUCTURE.md`
- `docs/operation/22_VPS_DEPLOYMENT_RUNBOOK.md`
- `docs/technical/19_OBSERVABILITY_LOGGING_MONITORING.md`

**Acceptance criteria**:

- Queue worker instructions documented.
- Scheduler instructions documented.
- Backup/restore documented.
- Rollback documented.
- `.env.example` complete.

---

## T036 — Final MVP Acceptance Pass

**Goal**: Verify MVP end-to-end.

**Model**: Reasoning/Pro model

**Read first**:

- `docs/planning/22_PHASE_1_MVP_DETAILED_SPEC.md`
- `docs/operation/08_TEST_PLAN.md`
- `docs/operation/09_ADMIN_OPERATION_MANUAL.md`

**Acceptance criteria**:

End-to-end scenario passes:

```txt
1. Admin creates user
2. Admin grants lá
3. Admin creates match
4. Admin creates market/outcomes
5. User places prediction
6. Market locks
7. Admin enters result
8. Admin previews settlement
9. Admin executes settlement
10. Wallet and leaderboard update correctly
11. Audit log is complete
```

---

## 2. Backlog Parking Lot

Do not implement before MVP unless explicitly approved:

```txt
- Mobile app
- Public registration
- Payment integration
- User-to-user lá transfer
- Reward redemption
- Social sharing
- Chat/comments
- External odds scraping
- Real-money features
- Department/team leaderboard
```

---

## 3. Agent Completion Report Format

At the end of every task, respond with:

```txt
Task: Txxx - <title>
Status: completed / partial / blocked
Changed files:
- path/to/file
- path/to/file

Verification:
- command run: ...
- result: passed / failed / not run

Notes:
- ...

Risks / follow-up:
- ...
```

Do not claim completion if tests were not run or if there are known failures.
