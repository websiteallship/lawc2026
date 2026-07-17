<?php
/**
 * Script tạo kèo Over/Under (Tài/Xỉu Full Time) cho trận Chung kết M104
 * Spain vs Argentina - 20/07/2026 02:00 UTC
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
$matchCode = 'M104'; // Mã trận Chung kết
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
// Lấy từ hình ảnh cung cấp cho M104
$ouLines = [
    '0.5'  => [1.08, 7.50],
    '0.75' => [1.10, 7.00],
    '1'    => [1.12, 6.40],
    '1.25' => [1.25, 3.80],
    '1.5'  => [1.40, 3.00],
    '1.75' => [1.48, 2.60],
    '2'    => [1.65, 2.20],
    '2.25' => [2.00, 1.85],
    '2.5'  => [2.30, 1.62],
    '2.75' => [2.60, 1.48],
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
        'created_by'    => 1, // Admin ID
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
    
    echo "Hoàn tất! Đã tạo 1 kèo chung Tài/Xỉu (O/U) FT cho M104 gồm " . ($createdCount * 2) . " outcomes.\n";
});
