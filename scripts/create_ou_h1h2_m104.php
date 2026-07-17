<?php
/**
 * Script tạo kèo Over/Under (Tài/Xỉu Hiệp 1 và Hiệp 2) cho trận Chung kết M104
 * Spain vs Argentina - 20/07/2026 02:00 UTC
 * Gộp tất cả các mốc (lines) vào 1 kèo chung cho mỗi Hiệp.
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
$matchCode = 'M104';
$match = FootballMatch::where('match_code', $matchCode)->first();

if (!$match) {
    echo "ERROR: Không tìm thấy trận {$matchCode}\n";
    exit(1);
}

// Xóa các kèo Tài/Xỉu H1 & H2 cũ (nếu có)
$oldMarkets = Market::where('match_id', $match->id)
    ->where('market_type', 'OVER_UNDER')
    ->whereIn('period_type', ['FIRST_HALF', 'SECOND_HALF'])
    ->get();

if ($oldMarkets->count() > 0) {
    echo "Đang xóa {$oldMarkets->count()} kèo cũ (H1 & H2)...\n";
    foreach ($oldMarkets as $old) {
        $old->outcomes()->delete();
        $old->delete();
    }
}

// ─── OVER/UNDER DATA ──────────────────────────────────────
$periodsData = [
    'FIRST_HALF' => [
        'name' => 'Tài/Xỉu (O/U) - Hiệp 1',
        'lines' => [
            '0.5'  => [1.44, 2.63],
            '0.75' => [1.68, 2.15],
            '1'    => [2.15, 1.73],
            '1.25' => [2.68, 1.45],
            '1.5'  => [3.25, 1.33],
        ]
    ],
    'SECOND_HALF' => [
        'name' => 'Tài/Xỉu (O/U) - Hiệp 2',
        'lines' => [
            '0.5'  => [1.29, 3.50],
            '1.5'  => [2.38, 1.53],
            '2.5'  => [5.50, 1.14],
            '3.5'  => [15.00, 1.03],
        ]
    ]
];

$openAt = now()->toDateTimeString();
$closeAt = $match->kickoff_at->toDateTimeString();

DB::transaction(function () use ($match, $periodsData, $openAt, $closeAt) {
    foreach ($periodsData as $periodType => $data) {
        $market = Market::create([
            'match_id'      => $match->id,
            'period_type'   => $periodType,
            'market_type'   => 'OVER_UNDER',
            'name'          => $data['name'],
            'open_at'       => $openAt,
            'close_at'      => $closeAt,
            'status'        => 'OPEN',
            'display_order' => $periodType === 'FIRST_HALF' ? 2 : 3,
            'created_by'    => 1,
        ]);

        $displayOrder = 1;
        $createdCount = 0;
        
        echo "\nTạo kèo {$data['name']}...\n";

        foreach ($data['lines'] as $line => $decimalOdds) {
            $overDecimal = $decimalOdds[0];
            $underDecimal = $decimalOdds[1];

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
        
        echo "=> Hoàn tất tạo " . ($createdCount * 2) . " outcomes cho {$data['name']}.\n";
    }
});
