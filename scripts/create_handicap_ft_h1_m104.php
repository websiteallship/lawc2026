<?php
/**
 * Script tạo kèo Chấp (Asian Handicap Full Time & First Half) cho trận M104
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

// Xóa các kèo Handicap cũ (nếu có)
$oldMarkets = Market::where('match_id', $match->id)
    ->where('market_type', 'ASIAN_HANDICAP')
    ->whereIn('period_type', ['FULL_TIME', 'FIRST_HALF'])
    ->get();

if ($oldMarkets->count() > 0) {
    echo "Đang xóa {$oldMarkets->count()} kèo cũ (FT & H1)...\n";
    foreach ($oldMarkets as $old) {
        $old->outcomes()->delete();
        $old->delete();
    }
}

// ─── HANDICAP DATA ────────────────────────────────────────
$periodsData = [
    'FULL_TIME' => [
        'name' => 'Kèo chấp (AH) - Cả trận',
        'display_order' => 1,
        'lines' => [
            '-1.75' => [5.90, 1.13],
            '-1.5'  => [4.40, 1.20],
            '-1.25' => [4.00, 1.23],
            '-1'    => [3.55, 1.27],
            '-0.75' => [2.68, 1.45],
            '-0.5'  => [2.20, 1.65],
            '-0.25' => [1.93, 1.93],
            '0'     => [1.55, 2.38],
            '0.25'  => [1.40, 2.85],
            '0.5'   => [1.30, 3.45],
            '0.75'  => [1.20, 4.40],
            '1'     => [1.11, 6.80],
        ]
    ],
    'FIRST_HALF' => [
        'name' => 'Kèo chấp (AH) - Hiệp 1',
        'display_order' => 2,
        'lines' => [
            '-0.75' => [3.90, 1.24],
            '-0.5'  => [2.85, 1.40],
            '-0.25' => [2.30, 1.60],
            '0'     => [1.65, 2.30],
            '0.25'  => [1.35, 3.10],
            '0.5'   => [1.24, 3.90],
            '0.75'  => [1.14, 5.75],
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
            'market_type'   => 'ASIAN_HANDICAP',
            'name'          => $data['name'],
            'open_at'       => $openAt,
            'close_at'      => $closeAt,
            'status'        => 'OPEN',
            'display_order' => $data['display_order'],
            'created_by'    => 1,
        ]);

        $displayOrder = 1;
        $createdCount = 0;
        
        echo "\nTạo kèo {$data['name']}...\n";

        foreach ($data['lines'] as $line => $decimalOdds) {
            $homeDecimal = $decimalOdds[0];
            $awayDecimal = $decimalOdds[1];

            if ($homeDecimal < 1.5 || $awayDecimal < 1.5) {
                echo "  - Bỏ qua Line {$line} (Home: {$homeDecimal}, Away: {$awayDecimal} < 1.5)\n";
                continue;
            }

            $homeProfitRate = round($homeDecimal - 1, 4);
            $awayProfitRate = round($awayDecimal - 1, 4);

            $lineVal = (float) $line;
            $lineLabelStr = $lineVal > 0 ? '+' . rtrim(rtrim(number_format($lineVal, 2), '0'), '.') : rtrim(rtrim(number_format($lineVal, 2), '0'), '.');
            if ($lineVal == 0) $lineLabelStr = '0';

            // HOME outcome
            MarketOutcome::create([
                'market_id'      => $market->id,
                'label'          => "TBN {$lineLabelStr}", // Spain
                'selection_side' => 'HOME',
                'line_value'     => $lineVal,
                'profit_rate'    => $homeProfitRate,
                'decimal_odds'   => $homeDecimal,
                'status'         => 'ACTIVE',
                'display_order'  => $displayOrder++,
            ]);

            // AWAY outcome
            MarketOutcome::create([
                'market_id'      => $market->id,
                'label'          => "ARG " . ($lineVal == 0 ? '0' : ($lineVal > 0 ? '-' . abs($lineVal) : '+' . abs($lineVal))), // Argentina (ngược line)
                'selection_side' => 'AWAY',
                'line_value'     => -$lineVal, // Đảo dấu line cho đội khách
                'profit_rate'    => $awayProfitRate,
                'decimal_odds'   => $awayDecimal,
                'status'         => 'ACTIVE',
                'display_order'  => $displayOrder++,
            ]);
            
            $createdCount++;
            echo "  ✓ Đã tạo Line {$lineLabelStr} (Home ăn {$homeProfitRate}, Away ăn {$awayProfitRate})\n";
        }
        
        echo "=> Hoàn tất tạo " . ($createdCount * 2) . " outcomes cho {$data['name']}.\n";
    }
});
