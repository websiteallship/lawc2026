<?php
/**
 * Script tạo kèo Asian Handicap (Full Time) cho trận tranh hạng 3 M103
 * Pháp vs Anh - 19/07/2026 04:00 UTC
 *
 * Match ID: 104 (match_code M103)
 * Pháp = HOME (kèo trên), Anh = AWAY (kèo dưới)
 *
 * Chạy: php scripts/create_handicap_m103.php
 */

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\FootballMatch;
use App\Models\Market;
use App\Models\MarketOutcome;
use Illuminate\Support\Facades\DB;

// ─── CONFIG ───────────────────────────────────────────────
$matchCode = 'M103';
$match = FootballMatch::where('match_code', $matchCode)->first();

if (!$match) {
    echo "ERROR: Không tìm thấy trận {$matchCode}\n";
    exit(1);
}

echo "Trận: {$match->home_team} vs {$match->away_team} (ID: {$match->id})\n";
echo "Kickoff: {$match->kickoff_at}\n\n";

// Kiểm tra đã có kèo handicap fulltime chưa
$existing = Market::where('match_id', $match->id)
    ->where('market_type', 'ASIAN_HANDICAP')
    ->where('period_type', 'FULL_TIME')
    ->count();

if ($existing > 0) {
    echo "WARNING: Đã có {$existing} kèo Handicap Full Time cho trận này!\n";
    echo "Bỏ qua tạo mới. Nếu muốn tạo lại, xóa kèo cũ trước.\n";
    exit(0);
}

// ─── HANDICAP LINES DATA ──────────────────────────────────
// Pháp = HOME (kèo trên, line âm = chấp)
// Anh = AWAY (kèo dưới, line dương = được chấp)
// profit_rate = decimal_odds - 1
// Decimal odds lấy từ hình (bookmaker data 17/07/2026)
//
// Format: [line_abs, home_profit_rate, away_profit_rate]
// line_abs: giá trị tuyệt đối của handicap
// HOME line = -line_abs (Pháp chấp)
// AWAY line = +line_abs (Anh được chấp)

$handicapLines = [
    // line_abs => [home_decimal_odds, away_decimal_odds]
    '0'    => [1.40, 2.85],
    '0.25' => [1.63, 2.25],   // 0, -0.5
    '0.5'  => [1.88, 1.98],
    '0.75' => [2.08, 1.73],   // -0.5, -1
    '1'    => [2.42, 1.53],
    '1.25' => [2.75, 1.43],   // -1, -1.5
    '1.5'  => [3.00, 1.38],
    '1.75' => [3.70, 1.26],   // -1.5, -2
    '2'    => [5.25, 1.16],
    '2.25' => [5.50, 1.15],   // -2, -2.5
    '2.5'  => [5.90, 1.13],
];

// Kickoff time for open/close
$openAt = now()->toDateTimeString();
$closeAt = $match->kickoff_at->toDateTimeString();

echo "Tạo kèo Handicap Full Time...\n";
echo "Open: {$openAt}\n";
echo "Close: {$closeAt}\n\n";

DB::transaction(function () use ($match, $handicapLines, $openAt, $closeAt) {
    $displayOrder = 1;

    foreach ($handicapLines as $lineAbs => $decimalOdds) {
        $lineAbs = (float) $lineAbs;
        $homeDecimal = $decimalOdds[0];
        $awayDecimal = $decimalOdds[1];

        // profit_rate = decimal_odds - 1
        $homeProfitRate = round($homeDecimal - 1, 4);
        $awayProfitRate = round($awayDecimal - 1, 4);

        // Build label
        if ($lineAbs == 0) {
            $lineLabel = '0';
        } else {
            $lineLabel = number_format($lineAbs, 2);
            // Trim trailing zeros: 0.50 -> 0.5, 1.00 -> 1, 1.25 -> 1.25
            $lineLabel = rtrim(rtrim($lineLabel, '0'), '.');
        }

        $marketName = "Kèo chấp (AH) - Cả trận · Line {$lineLabel}";

        $market = Market::create([
            'match_id'      => $match->id,
            'period_type'   => 'FULL_TIME',
            'market_type'   => 'ASIAN_HANDICAP',
            'name'          => $marketName,
            'open_at'       => $openAt,
            'close_at'      => $closeAt,
            'status'        => 'OPEN',
            'display_order' => $displayOrder++,
            'created_by'    => 1, // admin
        ]);

        // HOME outcome (Pháp - kèo trên, line âm)
        $homeLineValue = -$lineAbs;
        $homeLabel = "{$match->home_team} {$homeLineValue}";

        MarketOutcome::create([
            'market_id'      => $market->id,
            'label'          => $homeLabel,
            'selection_side' => 'HOME',
            'line_value'     => $homeLineValue,
            'profit_rate'    => $homeProfitRate,
            'decimal_odds'   => $homeDecimal,
            'status'         => 'ACTIVE',
            'display_order'  => 1,
        ]);

        // AWAY outcome (Anh - kèo dưới, line dương)
        $awayLineValue = $lineAbs;
        $awayLabel = "{$match->away_team} +{$awayLineValue}";

        if ($lineAbs == 0) {
            $awayLabel = "{$match->away_team} 0";
        }

        MarketOutcome::create([
            'market_id'      => $market->id,
            'label'          => $awayLabel,
            'selection_side' => 'AWAY',
            'line_value'     => $awayLineValue,
            'profit_rate'    => $awayProfitRate,
            'decimal_odds'   => $awayDecimal,
            'status'         => 'ACTIVE',
            'display_order'  => 2,
        ]);

        echo "  ✓ Line {$lineLabel}: {$homeLabel} ăn {$homeProfitRate} | {$awayLabel} ăn {$awayProfitRate}\n";
    }
});

echo "\nHoàn tất! Đã tạo " . count($handicapLines) . " kèo Handicap Full Time cho trận {$matchCode}.\n";
