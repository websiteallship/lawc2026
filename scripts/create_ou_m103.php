<?php
/**
 * Script tạo kèo Over/Under (Tài/Xỉu Full Time) cho trận M103
 * Pháp vs Anh - 19/07/2026 04:00 UTC
 * Gộp tất cả các mốc (lines) vào 1 kèo chung (Market) duy nhất.
 * Tự động loại bỏ các cặp kèo có tỉ lệ ăn < 0.5 (decimal odds < 1.5)
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

// Xóa các kèo Tài/Xỉu FT cũ (nếu có)
$oldMarkets = Market::where('match_id', $match->id)
    ->where('market_type', 'OVER_UNDER')
    ->where('period_type', 'FULL_TIME')
    ->get();

if ($oldMarkets->count() > 0) {
    echo "Đang xóa {$oldMarkets->count()} kèo cũ...\n";
    foreach ($oldMarkets as $old) {
        $old->outcomes()->delete();
        $old->delete();
    }
}

// ─── OVER/UNDER LINES DATA (FULL TIME) ────────────────────
// line => [over_decimal_odds, under_decimal_odds]
$ouLines = [
    '0.5'  => [1.02, 17.00],
    '1.25' => [1.10, 7.00],
    '1.5'  => [1.13, 6.00],
    '1.75' => [1.15, 5.50],
    '2'    => [1.17, 5.00],
    '2.25' => [1.30, 3.45],
    '2.5'  => [1.44, 2.75],
    '2.75' => [1.53, 2.42],
    '3'    => [1.65, 2.20],
    '3.25' => [1.90, 1.95],
    '3.5'  => [2.10, 1.73],
    '3.75' => [2.35, 1.58],
    '4'    => [2.85, 1.40],
    '4.25' => [3.10, 1.35],
    '4.5'  => [3.50, 1.30],
    '4.75' => [4.15, 1.22],
    '5'    => [5.75, 1.14],
    '5.25' => [5.90, 1.13],
];

$openAt = now()->toDateTimeString();
$closeAt = $match->kickoff_at->toDateTimeString();

DB::transaction(function () use ($match, $ouLines, $openAt, $closeAt) {
    $market = Market::create([
        'match_id'      => $match->id,
        'period_type'   => 'FULL_TIME',
        'market_type'   => 'OVER_UNDER',
        'name'          => 'Tài/Xỉu (O/U) - Cả trận',
        'open_at'       => $openAt,
        'close_at'      => $closeAt,
        'status'        => 'OPEN',
        'display_order' => 1,
        'created_by'    => 1,
    ]);

    $displayOrder = 1;
    $createdCount = 0;

    foreach ($ouLines as $line => $decimalOdds) {
        $overDecimal = $decimalOdds[0];
        $underDecimal = $decimalOdds[1];

        // Loại bỏ cặp kèo nếu một trong 2 bên có decimal odds < 1.5 (profit_rate < 0.5)
        if ($overDecimal < 1.5 || $underDecimal < 1.5) {
            echo "  - Bỏ qua Line {$line} (Over: {$overDecimal}, Under: {$underDecimal} < 1.5)\n";
            continue;
        }

        $overProfitRate = round($overDecimal - 1, 4);
        $underProfitRate = round($underDecimal - 1, 4);

        $lineAbs = (float) $line;
        $lineLabelStr = rtrim(rtrim(number_format($lineAbs, 2), '0'), '.');

        // OVER outcome
        MarketOutcome::create([
            'market_id'      => $market->id,
            'label'          => "Tài {$lineLabelStr}",
            'selection_side' => 'OVER',
            'line_value'     => $lineAbs,
            'profit_rate'    => $overProfitRate,
            'decimal_odds'   => $overDecimal,
            'status'         => 'ACTIVE',
            'display_order'  => $displayOrder++,
        ]);

        // UNDER outcome
        MarketOutcome::create([
            'market_id'      => $market->id,
            'label'          => "Xỉu {$lineLabelStr}",
            'selection_side' => 'UNDER',
            'line_value'     => $lineAbs,
            'profit_rate'    => $underProfitRate,
            'decimal_odds'   => $underDecimal,
            'status'         => 'ACTIVE',
            'display_order'  => $displayOrder++,
        ]);
        
        $createdCount++;
        echo "  ✓ Đã tạo Line {$lineLabelStr} (Tài ăn {$overProfitRate}, Xỉu ăn {$underProfitRate})\n";
    }
    
    echo "Hoàn tất! Đã tạo 1 kèo chung Tài/Xỉu (O/U) FT gồm " . ($createdCount * 2) . " outcomes.\n";
});
