---
title: "Settlement Engine Specification"
project: "Du Doan La"
version: "1.0"
status: "draft"
last_updated: "2026-06-11"
---

# Settlement Engine Specification

## 1. Mục tiêu

Settlement Engine là lõi nghiệp vụ tính kết quả phiếu dự đoán và cập nhật ví lá. Đây là phần phải được test kỹ nhất của dự án.

Engine phải hỗ trợ:

1. Tỉ số chính xác.
2. Handicap châu Á.
3. Tài/xỉu.
4. Full win, full loss, push, half win, half loss, void.
5. Quarter line: `0.25`, `0.75`, `1.25`, `1.75`, `2.25`, `2.75`...
6. Profit rate snapshot.
7. Wallet ledger.
8. Idempotency.
9. Correction/re-settlement.

---

## 2. Khái niệm tính toán

## 2.1. Quy ước tỷ lệ ăn kiểu Việt Nam

Tài liệu sử dụng cách ghi phổ biến ở Việt Nam: **line + tỷ lệ ăn**.

Ví dụ:

```text
Home -0.5 ăn 0.90
Over 2.5 ăn 0.90
Tỉ số 2-1 ăn 6.00
```

Ý nghĩa:

```text
profit_rate = tỷ lệ ăn / hệ số lãi
decimal_odds = 1 + profit_rate
gross_payout_full_win = stake * (1 + profit_rate)
net_profit_full_win = stake * profit_rate
```

Ví dụ đặt 100 lá ở kèo `Home -0.5 ăn 0.90`:

```text
Nếu thắng đủ: nhận 190 lá, gồm 100 lá vốn + 90 lá lãi.
Nếu thua: nhận 0 lá, mất 100 lá.
Nếu push: nhận 100 lá, không lãi không lỗ.
```

> Lưu ý kỹ thuật: frontend/admin hiển thị và nhập theo `profit_rate`; nếu cần tích hợp/report theo chuẩn quốc tế thì quy đổi sang `decimal_odds = 1 + profit_rate`.

## 2.2. Gross payout

Hệ thống sử dụng **gross payout**.

```text
gross_payout = tổng số lá trả về ví user sau settlement
net_result = gross_payout - stake
```

Ví dụ:

| Stake | Tỷ lệ ăn | Kết quả | Gross payout | Net result |
|---:|---:|---|---:|---:|
| 100 | 0.90 | Win | 190 | +90 |
| 100 | 0.90 | Lose | 0 | -100 |
| 100 | 0.90 | Push | 100 | 0 |
| 100 | 0.90 | Half win | 145 | +45 |
| 100 | 0.90 | Half lose | 50 | -50 |

## 2.3. Wallet movement

Khi đặt bet:

```text
available_balance -= stake
locked_balance += stake
```

Khi settle:

```text
locked_balance -= stake
available_balance += gross_payout
```

Do đó `gross_payout` đã bao gồm phần stake được hoàn nếu thắng hoặc push.

---

## 3. Quy tắc rounding

Vì lá là số nguyên, mọi payout cuối cùng phải là integer.

### Rule mặc định

```text
- Tính toán nội bộ dùng decimal, không dùng float.
- `profit_rate` lưu decimal(10,4), ví dụ `0.90`; nếu cần có thể lưu thêm `decimal_odds = 1 + profit_rate`, ví dụ `1.90`.
- Payout từng component có thể là decimal.
- Sum component payout trước.
- Sau đó round final gross_payout bằng ROUND_HALF_UP về integer.
```

Ví dụ:

```text
stake = 101
tỷ lệ ăn = 0.90
quarter line split: 50.5 + 50.5
component win = 50.5 * (1 + 0.90) = 95.95
component push = 50.5
sum = 146.45
final gross_payout = 146 nếu làm tròn integer thông thường, hoặc 146/147 tùy chính sách
```

Khuyến nghị để tránh tranh cãi:

```text
Chỉ cho stake là bội số của 10 lá trong MVP.
```

Khi stake là bội số của 10, split quarter line ít tạo số lẻ khó hiểu.

---

## 4. Settlement exact score

## 4.1. Input

```text
bet.market_type = EXACT_SCORE
bet.score_home_snapshot
bet.score_away_snapshot
result.home_score
result.away_score
stake
profit_rate_snapshot
```

## 4.2. Rule

```text
Nếu predicted_home_score == result_home_score
và predicted_away_score == result_away_score:
    status = WON
    gross_payout = stake * (1 + profit_rate)
Ngược lại:
    status = LOST
    gross_payout = 0
```

## 4.3. Ví dụ

| Prediction | Result | Stake | Tỷ lệ ăn | Status | Gross payout |
|---|---|---:|---:|---|---:|
| 2-1 | 2-1 | 100 | 6.00 | WON | 700 |
| 2-1 | 1-1 | 100 | 6.00 | LOST | 0 |
| 0-0 | 0-0 | 50 | 4.50 | WON | 275 |

## 4.4. Pseudocode

```php
function settleExactScore(Bet $bet, PeriodResult $result): SettlementResult
{
    $isWin = $bet->score_home_snapshot === $result->home_score
        && $bet->score_away_snapshot === $result->away_score;

    if ($isWin) {
        return new SettlementResult(
            status: BetStatus::Won,
            grossPayout: roundHalfUp($bet->stake * (1 + $bet->profit_rate_snapshot)),
            details: ['rule' => 'exact_score_match']
        );
    }

    return new SettlementResult(
        status: BetStatus::Lost,
        grossPayout: 0,
        details: ['rule' => 'exact_score_not_match']
    );
}
```

---

## 5. Settlement Asian Handicap

## 5.1. Input

```text
selection_side_snapshot = HOME hoặc AWAY
line_snapshot = handicap line của đội được chọn
home_score
away_score
stake
profit_rate_snapshot
```

Ví dụ:

```text
HOME -0.75 ăn 0.90
AWAY +0.75 ăn 0.90
```

## 5.2. Công thức chuẩn

Từ góc nhìn đội được chọn:

```text
selected_score = score của đội user chọn
opponent_score = score của đội còn lại
adjusted_margin = selected_score + handicap_line - opponent_score
```

Kết quả:

```text
adjusted_margin > 0  => WIN
adjusted_margin == 0 => PUSH
adjusted_margin < 0  => LOSE
```

## 5.3. Split quarter line

Quarter line phải tách stake thành 2 line component.

| Line | Split thành |
|---:|---|
| -0.25 | 0 và -0.5 |
| -0.75 | -0.5 và -1.0 |
| -1.25 | -1.0 và -1.5 |
| -1.75 | -1.5 và -2.0 |
| +0.25 | 0 và +0.5 |
| +0.75 | +0.5 và +1.0 |
| +1.25 | +1.0 và +1.5 |
| +1.75 | +1.5 và +2.0 |

General rule:

```text
Nếu abs(line % 1) == 0.25:
    split = [floor_toward_zero_line, line_farther_by_0.5]
Nếu abs(line % 1) == 0.75:
    split = [line_closer_by_0.25, line_farther_by_0.25]
```

Implementation đơn giản hơn:

```php
function splitAsianLine(decimal $line): array
{
    $absFraction = abs($line - floor($line));

    // Nên implement bằng multiply 4 để tránh lỗi decimal.
    $q = (int) round($line * 4);

    if ($q % 2 === 0) {
        return [$line]; // integer hoặc .5 line
    }

    return [($q - 1) / 4, ($q + 1) / 4];
}
```

Ví dụ:

```text
-0.75 * 4 = -3
split q = [-4, -2] / 4 = [-1.0, -0.5]
```

Thứ tự không quan trọng.

## 5.4. Payout từng component

```text
component_stake = stake / number_of_components

WIN:  component_payout = component_stake * (1 + profit_rate)
PUSH: component_payout = component_stake
LOSE: component_payout = 0
```

Final:

```text
gross_payout = round(sum(component_payouts))
```

Status tổng hợp:

| Components | Final status |
|---|---|
| WIN + WIN | WON |
| LOSE + LOSE | LOST |
| PUSH | PUSH |
| WIN + PUSH | HALF_WON |
| LOSE + PUSH | HALF_LOST |

## 5.5. Ví dụ handicap

### HOME -0.75, tỷ lệ ăn 0.90, stake 100

Split: `-0.5` và `-1.0`.

| Result | Component -0.5 | Component -1.0 | Gross payout | Status |
|---|---|---|---:|---|
| Home thắng 2 bàn | Win | Win | 190 | WON |
| Home thắng 1 bàn | Win | Push | 145 | HALF_WON |
| Hòa | Lose | Lose | 0 | LOST |
| Home thua | Lose | Lose | 0 | LOST |

### HOME -1.0, tỷ lệ ăn 0.90, stake 100

| Result | Gross payout | Status |
|---|---:|---|
| Home thắng 2 bàn | 190 | WON |
| Home thắng 1 bàn | 100 | PUSH |
| Hòa/thua | 0 | LOST |

### AWAY +0.25, tỷ lệ ăn 0.90, stake 100

Split: `0` và `+0.5`.

| Result theo AWAY | Component 0 | Component +0.5 | Gross payout | Status |
|---|---|---|---:|---|
| Away thắng | Win | Win | 190 | WON |
| Hòa | Push | Win | 145 | HALF_WON |
| Away thua | Lose | Lose | 0 | LOST |

## 5.6. Pseudocode

```php
function settleAsianHandicap(Bet $bet, PeriodResult $result): SettlementResult
{
    [$selectedScore, $opponentScore] = match ($bet->selection_side_snapshot) {
        'HOME' => [$result->home_score, $result->away_score],
        'AWAY' => [$result->away_score, $result->home_score],
    };

    $lines = splitAsianLine($bet->line_snapshot);
    $componentStake = decimalDiv($bet->stake, count($lines));
    $components = [];
    $gross = decimal(0);

    foreach ($lines as $line) {
        $adjustedMargin = $selectedScore + $line - $opponentScore;

        if ($adjustedMargin > 0) {
            $status = 'WIN';
            $payout = $componentStake * (1 + $bet->profit_rate_snapshot);
        } elseif ($adjustedMargin == 0) {
            $status = 'PUSH';
            $payout = $componentStake;
        } else {
            $status = 'LOSE';
            $payout = 0;
        }

        $gross += $payout;
        $components[] = compact('line', 'status', 'payout');
    }

    return new SettlementResult(
        status: aggregateComponentStatus($components),
        grossPayout: roundHalfUp($gross),
        details: ['components' => $components]
    );
}
```

---

## 6. Settlement Over/Under

## 6.1. Input

```text
selection_side_snapshot = OVER hoặc UNDER
line_snapshot = total goal line
home_score
away_score
stake
profit_rate_snapshot
```

```text
total_goals = home_score + away_score
```

## 6.2. Rule cho line đơn

Với `OVER`:

```text
margin = total_goals - line
margin > 0  => WIN
margin == 0 => PUSH
margin < 0  => LOSE
```

Với `UNDER`:

```text
margin = line - total_goals
margin > 0  => WIN
margin == 0 => PUSH
margin < 0  => LOSE
```

## 6.3. Split quarter total line

| Total line | Split thành |
|---:|---|
| 2.25 | 2.0 và 2.5 |
| 2.75 | 2.5 và 3.0 |
| 3.25 | 3.0 và 3.5 |
| 3.75 | 3.5 và 4.0 |

Dùng cùng hàm split line như handicap.

## 6.4. Ví dụ tài/xỉu

### Over 2.25, tỷ lệ ăn 0.90, stake 100

Split: Over 2.0 và Over 2.5.

| Total goals | Component 2.0 | Component 2.5 | Gross payout | Status |
|---:|---|---|---:|---|
| 3 | Win | Win | 190 | WON |
| 2 | Push | Lose | 50 | HALF_LOST |
| 1 | Lose | Lose | 0 | LOST |

### Under 2.25, tỷ lệ ăn 0.90, stake 100

| Total goals | Component 2.0 | Component 2.5 | Gross payout | Status |
|---:|---|---|---:|---|
| 0 hoặc 1 | Win | Win | 190 | WON |
| 2 | Push | Win | 145 | HALF_WON |
| 3+ | Lose | Lose | 0 | LOST |

### Over 2.75, tỷ lệ ăn 0.90, stake 100

Split: Over 2.5 và Over 3.0.

| Total goals | Component 2.5 | Component 3.0 | Gross payout | Status |
|---:|---|---|---:|---|
| 4+ | Win | Win | 190 | WON |
| 3 | Win | Push | 145 | HALF_WON |
| 2 trở xuống | Lose | Lose | 0 | LOST |

### Under 2.75, tỷ lệ ăn 0.90, stake 100

| Total goals | Component 2.5 | Component 3.0 | Gross payout | Status |
|---:|---|---|---:|---|
| 0,1,2 | Win | Win | 190 | WON |
| 3 | Lose | Push | 50 | HALF_LOST |
| 4+ | Lose | Lose | 0 | LOST |

## 6.5. Pseudocode

```php
function settleOverUnder(Bet $bet, PeriodResult $result): SettlementResult
{
    $totalGoals = $result->home_score + $result->away_score;
    $lines = splitAsianLine($bet->line_snapshot);
    $componentStake = decimalDiv($bet->stake, count($lines));
    $components = [];
    $gross = decimal(0);

    foreach ($lines as $line) {
        $margin = match ($bet->selection_side_snapshot) {
            'OVER' => $totalGoals - $line,
            'UNDER' => $line - $totalGoals,
        };

        if ($margin > 0) {
            $status = 'WIN';
            $payout = $componentStake * (1 + $bet->profit_rate_snapshot);
        } elseif ($margin == 0) {
            $status = 'PUSH';
            $payout = $componentStake;
        } else {
            $status = 'LOSE';
            $payout = 0;
        }

        $gross += $payout;
        $components[] = compact('line', 'status', 'payout');
    }

    return new SettlementResult(
        status: aggregateComponentStatus($components),
        grossPayout: roundHalfUp($gross),
        details: [
            'total_goals' => $totalGoals,
            'components' => $components,
        ]
    );
}
```

---

## 7. Settlement theo period

## 7.1. FULL_TIME

- Tính 90 phút chính thức + bù giờ.
- Không tính hiệp phụ.
- Không tính penalty.

## 7.2. FIRST_HALF

- Tính hiệp 1 + bù giờ hiệp 1.

## 7.3. SECOND_HALF

- Tính riêng bàn trong hiệp 2 + bù giờ hiệp 2.
- Không dùng tỉ số chung cuộc.

Ví dụ:

```text
Hiệp 1: Home 1-1 Away
Full-time: Home 2-1 Away
SECOND_HALF result = Home 1-0 Away
```

## 7.4. EXTRA_TIME

- Tính riêng 30 phút hiệp phụ.
- Không tính penalty.

## 7.5. PENALTY

- Tính riêng loạt sút luân lưu.
- MVP có thể chỉ hỗ trợ `PENALTY_WINNER` hoặc exact penalty score nếu admin cấu hình.

---

## 8. Void logic

Market/bet được void khi:

- Trận bị hủy.
- Market nhập sai nghiêm trọng.
- Market bị tạo nhầm period.
- Kết quả không thể xác định.
- Lỗi kỹ thuật ảnh hưởng công bằng.

Void xử lý:

```text
For each pending bet:
    gross_payout = stake
    status = VOIDED
    wallet.locked -= stake
    wallet.available += stake
    ledger type = BET_VOIDED
```

Không dùng void để sửa kết quả đã settle. Nếu đã settle, dùng correction.

---

## 9. Correction logic

Correction dùng khi market đã settled nhưng kết quả hoặc rule bị nhập sai.

Nguyên tắc:

- Không xóa settlement cũ.
- Không sửa ledger cũ.
- Tạo correction settlement mới.
- Tính chênh lệch giữa payout cũ và payout đúng.
- Tạo ledger `SETTLEMENT_CORRECTION` với delta.
- Cập nhật bet status sang kết quả corrected mới hoặc lưu status hiện tại + correction metadata.

Ví dụ:

```text
Bet stake 100, payout cũ 190.
Payout đúng sau correction 0.
Delta = -190.
User available_balance -= 190 nếu đủ.
Nếu không đủ, ghi negative_adjustment_pending hoặc cho phép admin xử lý thủ công theo policy.
```

Khuyến nghị MVP:

```text
Correction chỉ cho Super Admin/Settlement Manager thực hiện.
Nếu delta âm lớn hơn available_balance, đưa vào danh sách manual review.
```

---

## 10. Idempotency

Settlement phải chống chạy lặp.

Các biện pháp:

- Market status phải là `LOCKED` hoặc `SETTLING`, không phải `SETTLED`.
- Lock market row `FOR UPDATE`.
- Constraint mỗi market chỉ có một settlement `EXECUTED` active.
- Bet status phải là `PENDING` khi settle chính.
- Mỗi bet chỉ có một settlement item chính.

Pseudocode:

```php
DB::transaction(function () use ($market) {
    $market = Market::whereKey($market->id)->lockForUpdate()->first();

    if ($market->status === 'SETTLED') {
        throw new AlreadySettledException();
    }

    $market->update(['status' => 'SETTLING']);

    // settle pending bets only

    $market->update(['status' => 'SETTLED']);
});
```

---

## 11. Settlement service structure

```text
app/Domain/Settlement/
├── SettlementEngine.php
├── Contracts/
│   ├── SettlementCalculator.php
│   └── SettlementResult.php
├── Calculators/
│   ├── ExactScoreCalculator.php
│   ├── AsianHandicapCalculator.php
│   ├── OverUnderCalculator.php
│   └── PenaltyWinnerCalculator.php
├── Support/
│   ├── LineSplitter.php
│   ├── PayoutRounding.php
│   └── ComponentStatusAggregator.php
└── Actions/
    ├── PreviewSettlementAction.php
    ├── ExecuteSettlementAction.php
    ├── VoidMarketAction.php
    └── CorrectSettlementAction.php
```

---

## 12. Test matrix bắt buộc

| ID | Market | Selection | Line | Tỷ lệ ăn | Stake | Result | Expected status | Expected payout |
|---|---|---|---:|---:|---:|---|---|---:|
| T001 | Exact score | 2-1 | - | 6.00 | 100 | 2-1 | WON | 700 |
| T002 | Exact score | 2-1 | - | 6.00 | 100 | 1-1 | LOST | 0 |
| T003 | AH | Home | -0.5 | 0.90 | 100 | Home wins 1 | WON | 190 |
| T004 | AH | Home | -0.5 | 0.90 | 100 | Draw | LOST | 0 |
| T005 | AH | Home | 0 | 0.90 | 100 | Draw | PUSH | 100 |
| T006 | AH | Home | -1.0 | 0.90 | 100 | Home wins 1 | PUSH | 100 |
| T007 | AH | Home | -0.75 | 0.90 | 100 | Home wins 1 | HALF_WON | 145 |
| T008 | AH | Home | -0.25 | 0.90 | 100 | Draw | HALF_LOST | 50 |
| T009 | AH | Away | +0.25 | 0.90 | 100 | Draw | HALF_WON | 145 |
| T010 | OU | Over | 2.5 | 0.90 | 100 | 3 goals | WON | 190 |
| T011 | OU | Over | 2.5 | 0.90 | 100 | 2 goals | LOST | 0 |
| T012 | OU | Over | 2.0 | 0.90 | 100 | 2 goals | PUSH | 100 |
| T013 | OU | Over | 2.25 | 0.90 | 100 | 2 goals | HALF_LOST | 50 |
| T014 | OU | Under | 2.25 | 0.90 | 100 | 2 goals | HALF_WON | 145 |
| T015 | OU | Over | 2.75 | 0.90 | 100 | 3 goals | HALF_WON | 145 |
| T016 | OU | Under | 2.75 | 0.90 | 100 | 3 goals | HALF_LOST | 50 |

---

## 13. Acceptance criteria

Settlement Engine đạt yêu cầu khi:

- Pass toàn bộ test matrix.
- Không dùng float.
- Có test cho all period types.
- Có test cho void.
- Có test cho double settlement.
- Có test cho profit_rate snapshot.
- Có test cho wallet ledger movement.
- Có test concurrency cơ bản.
