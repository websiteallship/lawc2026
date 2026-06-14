---
title: "Domain Service Design"
project: "Dự Đoán Lá"
version: "1.0"
status: "draft"
last_updated: "2026-06-11"
owner: "Engineering"
---

# Domain Service Design

## 1. Mục tiêu

Tài liệu này mô tả cách tổ chức nghiệp vụ lõi trong Laravel để tránh code rải rác trong Controller, Filament Resource hoặc Livewire component.

Nguyên tắc chính:

```text
- Controller/Filament Action chỉ nhận input, gọi service, trả response.
- Nghiệp vụ ví, đặt lá, settlement nằm trong Domain Services.
- Calculator settlement tách riêng theo market type.
- Mọi thao tác lá có transaction + ledger.
- Logic phải test được bằng unit test.
```

---

## 2. Cấu trúc thư mục đề xuất

```text
app/
  Domain/
    Wallet/
      Actions/
      Data/
      Enums/
      Services/
    Betting/
      Actions/
      Data/
      Enums/
      Services/
    Settlement/
      Calculators/
      Data/
      Enums/
      Services/
    Market/
      Services/
    Leaderboard/
      Services/
    Audit/
      Services/
  Filament/
    Resources/
    Pages/
    Widgets/
  Models/
  Policies/
```

---

## 3. Domain enums

## 3.1. MarketType

```php
enum MarketType: string
{
    case EXACT_SCORE = 'EXACT_SCORE';
    case ASIAN_HANDICAP = 'ASIAN_HANDICAP';
    case OVER_UNDER = 'OVER_UNDER';
    case PENALTY_WINNER = 'PENALTY_WINNER';
}
```

## 3.2. PeriodType

```php
enum PeriodType: string
{
    case FULL_TIME = 'FULL_TIME';
    case FIRST_HALF = 'FIRST_HALF';
    case SECOND_HALF = 'SECOND_HALF';
    case EXTRA_TIME = 'EXTRA_TIME';
    case PENALTY = 'PENALTY';
}
```

## 3.3. BetStatus

```php
enum BetStatus: string
{
    case PENDING = 'PENDING';
    case WON = 'WON';
    case LOST = 'LOST';
    case PUSH = 'PUSH';
    case HALF_WON = 'HALF_WON';
    case HALF_LOST = 'HALF_LOST';
    case VOIDED = 'VOIDED';
}
```

---

## 4. WalletService

## 4.1. Trách nhiệm

```text
- Tạo ví cho user/season.
- Cấp lá.
- Trừ lá.
- Lock stake khi đặt dự đoán.
- Unlock/settle stake.
- Tạo wallet ledger.
- Đảm bảo không âm balance.
```

## 4.2. Public methods

```php
class WalletService
{
    public function createWallet(User $user, Season $season): Wallet;

    public function grant(
        Wallet $wallet,
        int $amount,
        User $admin,
        string $reason,
        ?Model $reference = null
    ): WalletLedger;

    public function lockStake(
        Wallet $wallet,
        int $stake,
        Bet $bet
    ): WalletLedger;

    public function settleBet(
        Wallet $wallet,
        Bet $bet,
        int $grossPayout,
        string $resultStatus
    ): array;

    public function voidBet(
        Wallet $wallet,
        Bet $bet,
        string $reason
    ): array;
}
```

## 4.3. Invariant

```text
available_balance >= 0
locked_balance >= 0
total_balance = available_balance + locked_balance
wallet ledger phải khớp balance sau mỗi transaction
```

---

## 5. BetPlacementService

## 5.1. Trách nhiệm

```text
- Nhận yêu cầu đặt lá.
- Validate market/outcome/stake.
- Snapshot odds, line, label.
- Lock ví user.
- Tạo bet.
- Tạo wallet ledger.
```

## 5.2. Method đề xuất

```php
class BetPlacementService
{
    public function placeBet(
        User $user,
        MarketOutcome $outcome,
        int $stake,
        ?string $clientRequestId = null
    ): Bet;
}
```

## 5.3. Flow

```text
1. Load outcome + market + match.
2. Check user active.
3. Check market.status = OPEN.
4. Check now < market.close_at.
5. Check outcome.status = ACTIVE.
6. Validate stake min/max.
7. Check stake limit per match/day.
8. DB transaction.
9. Lock wallet row FOR UPDATE.
10. Check available_balance >= stake.
11. Create bet with snapshot:
    - profit_rate_snapshot
    - line_snapshot
    - selection_side_snapshot
    - label_snapshot
    - display_odds_snapshot
    - close_at_snapshot
12. WalletService::lockStake.
13. Commit.
14. Dispatch BetPlaced event.
```

---

## 6. MarketLockService

## 6.1. Trách nhiệm

```text
- Tự khóa market quá giờ.
- Không ảnh hưởng market đã settled/voided.
- Chạy idempotent qua scheduler.
```

## 6.2. Method

```php
class MarketLockService
{
    public function lockExpiredMarkets(CarbonInterface $now): int;
    public function lockMarket(Market $market, ?User $admin = null): void;
}
```

## 6.3. Rule

```text
Nếu now >= close_at và status = OPEN => LOCKED
Nếu status != OPEN => không làm gì
```

---

## 7. SettlementEngine

## 7.1. Trách nhiệm

```text
- Nhận market + result.
- Chọn calculator đúng market_type.
- Tính preview settlement.
- Execute settlement bằng transaction.
- Đảm bảo idempotency.
```

## 7.2. Public methods

```php
class SettlementEngine
{
    public function preview(Market $market, MatchResult $result): SettlementPreview;

    public function execute(
        Market $market,
        MatchResult $result,
        User $actor,
        ?string $idempotencyKey = null
    ): Settlement;
}
```

## 7.3. Execute flow

```text
1. Check market LOCKED/CLOSED.
2. Check result đủ cho period_type.
3. Check chưa execute settlement cùng result_version.
4. Create settlement status PROCESSING.
5. Load pending bets.
6. For each bet:
   - calculator->calculate(bet, result)
   - lock wallet
   - WalletService::settleBet
   - update bet status
   - create settlement_item
7. Mark settlement EXECUTED.
8. Mark market SETTLED.
9. Dispatch SettlementExecuted event.
10. Rebuild/update leaderboard.
```

---

## 8. Settlement calculators

## 8.1. Interface

```php
interface SettlementCalculator
{
    public function supports(MarketType $marketType): bool;
    public function calculate(Bet $bet, MatchResult $result): SettlementResult;
}
```

## 8.2. SettlementResult DTO

```php
final readonly class SettlementResult
{
    public function __construct(
        public BetStatus $status,
        public int $grossPayout,
        public int $netResult,
        public array $components = [],
        public string $explanation = '',
    ) {}
}
```

## 8.3. ExactScoreSettlementCalculator

Logic:

```text
Nếu result_home == score_home_snapshot và result_away == score_away_snapshot:
  status = WON
  gross_payout = stake * (1 + profit_rate)
Ngược lại:
  status = LOST
  gross_payout = 0
```

## 8.4. AsianHandicapSettlementCalculator

Logic:

```text
adjusted_score = selected_team_score + line_value
compare adjusted_score với opponent_score
```

Quarter line split:

```text
-0.25 => split 0 và -0.5
-0.75 => split -0.5 và -1
+0.25 => split 0 và +0.5
+0.75 => split +0.5 và +1
```

## 8.5. OverUnderSettlementCalculator

Logic:

```text
total_goals = home_score + away_score
OVER: compare total_goals với line
UNDER: compare line với total_goals
```

Quarter total split:

```text
2.25 => split 2.0 và 2.5
2.75 => split 2.5 và 3.0
```

---

## 9. CorrectionService

## 9.1. Trách nhiệm

```text
- Tạo correction khi nhập sai kết quả hoặc settlement sai.
- Không xóa ledger cũ.
- Tạo adjustment ledger.
- Cập nhật leaderboard.
```

## 9.2. Method

```php
class CorrectionService
{
    public function createCorrection(
        Settlement $oldSettlement,
        MatchResult $newResult,
        User $actor,
        string $reason
    ): Correction;

    public function executeCorrection(Correction $correction): Settlement;
}
```

## 9.3. Rule

```text
new_net_result - old_net_result = adjustment_amount
```

Ví dụ:

```text
Old payout: 0
New payout: 190
Adjustment: +190 vào available_balance
```

---

## 10. LeaderboardService

## 10.1. Trách nhiệm

```text
- Tính rank cá nhân.
- Tính net_profit, ROI, win_rate.
- Áp điều kiện tối thiểu để tránh méo ROI.
- Snapshot leaderboard.
```

## 10.2. Methods

```php
class LeaderboardService
{
    public function rebuildSeason(Season $season): void;
    public function updateUser(User $user, Season $season): void;
    public function snapshot(Season $season): LeaderboardSnapshot;
}
```

## 10.3. Formula

```text
net_profit = total_payout - total_staked
ROI = net_profit / total_staked * 100
win_rate = won_bets / settled_bets * 100
```

Điều kiện ROI hợp lệ:

```text
settled_bets >= min_bets_for_roi_rank
total_staked >= min_stake_for_roi_rank
```

---

## 11. AuditLogService

## 11.1. Trách nhiệm

```text
- Log admin action.
- Log import/export.
- Log odds change.
- Log settlement/correction.
```

## 11.2. Events cần log

```text
USER_CREATED
WALLET_GRANTED
MATCH_CREATED
MARKET_PUBLISHED
ODDS_CHANGED
MARKET_LOCKED
RESULT_UPDATED
SETTLEMENT_PREVIEWED
SETTLEMENT_EXECUTED
MARKET_VOIDED
CORRECTION_EXECUTED
SETTINGS_UPDATED
```

---

## 12. Service test matrix

| Service | Test chính |
|---|---|
| WalletService | grant, lock, settle, void, không âm |
| BetPlacementService | đặt thành công, market đóng, thiếu lá, stake vượt max |
| MarketLockService | khóa đúng giờ, idempotent |
| SettlementEngine | preview, execute, không double payout |
| AH Calculator | 0, 0.25, 0.5, 0.75, 1 |
| OU Calculator | 2, 2.25, 2.5, 2.75, 3 |
| CorrectionService | adjustment đúng, không xóa ledger cũ |
| LeaderboardService | net_profit, ROI, rank, min condition |

---

## 13. Anti-patterns cấm dùng

```text
- Tính payout trực tiếp trong Filament Resource.
- Update wallet balance bằng query builder rời rạc.
- Dùng float cho profit_rate/stake/payout.
- Xóa bet đã settle.
- Sửa ledger cũ.
- Chạy settlement không có idempotency.
- Để admin action bỏ qua service.
```
