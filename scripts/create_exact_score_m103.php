<?php
/**
 * Script tạo kèo Tỉ số chính xác (Exact Score Full Time) cho trận M103
 * Pháp vs Anh - 19/07/2026 04:00 UTC
 * Dữ liệu tỉ lệ ăn dựa trên bảng tỉ số được cung cấp.
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

// Xóa các kèo Tỉ số cũ (nếu có)
$oldMarkets = Market::where('match_id', $match->id)
    ->where('market_type', 'EXACT_SCORE')
    ->where('period_type', 'FULL_TIME')
    ->get();

if ($oldMarkets->count() > 0) {
    echo "Đang xóa {$oldMarkets->count()} kèo cũ...\n";
    foreach ($oldMarkets as $old) {
        $old->outcomes()->delete();
        $old->delete();
    }
}

// ─── EXACT SCORE DATA ─────────────────────────────────────
// array of ['home_score', 'away_score', 'profit_rate']
$exactScores = [
    [1, 0, 8.50],  [2, 0, 12.00], [2, 1, 9.00],  [3, 0, 22.00], [3, 1, 18.00],
    [4, 1, 40.00], [1, 1, 5.50],  [2, 2, 12.00], [0, 1, 11.00], [0, 2, 16.00],
    [0, 3, 33.00], [1, 2, 11.00], [1, 3, 25.00], [2, 3, 33.00], [3, 3, 40.00],
    [0, 4, 66.00], [1, 4, 50.00], [2, 4, 66.00], [4, 2, 50.00], [1, 6, 500.00],
    [2, 5, 150.0], [3, 4, 100.0], [5, 0, 100.0], [5, 1, 80.00], [5, 2, 125.0],
    [6, 1, 250.0], [3, 2, 25.00], [0, 5, 150.0], [4, 3, 80.00], [3, 5, 300.0],
    [5, 3, 250.0], [4, 4, 150.0], [4, 0, 50.00], [1, 5, 125.0], [6, 0, 350.0],
    [6, 2, 400.0], [0, 0, 10.00]
];

$openAt = now()->toDateTimeString();
$closeAt = $match->kickoff_at->toDateTimeString();

DB::transaction(function () use ($match, $exactScores, $openAt, $closeAt) {
    $market = Market::create([
        'match_id'      => $match->id,
        'period_type'   => 'FULL_TIME',
        'market_type'   => 'EXACT_SCORE',
        'name'          => 'Tỉ số chính xác (Cả trận)',
        'open_at'       => $openAt,
        'close_at'      => $closeAt,
        'status'        => 'OPEN',
        'display_order' => 1,
        'created_by'    => 1,
    ]);

    $displayOrder = 1;

    foreach ($exactScores as $scoreData) {
        $homeScore = $scoreData[0];
        $awayScore = $scoreData[1];
        $profitRate = $scoreData[2];
        $decimalOdds = $profitRate + 1;

        MarketOutcome::create([
            'market_id'      => $market->id,
            'label'          => "{$homeScore}-{$awayScore}",
            'selection_side' => null, // Exactly as in BulkCreateMarkets
            'score_home'     => $homeScore,
            'score_away'     => $awayScore,
            'profit_rate'    => $profitRate,
            'decimal_odds'   => $decimalOdds,
            'status'         => 'ACTIVE',
            'display_order'  => $displayOrder++,
        ]);

        echo "  ✓ Đã tạo Tỉ số {$homeScore}-{$awayScore} (ăn {$profitRate})\n";
    }

    // Luôn tạo thêm lựa chọn "Tỉ số khác" để bọc hậu
    MarketOutcome::create([
        'market_id'      => $market->id,
        'label'          => "Tỉ số khác",
        'selection_side' => 'OTHER',
        'score_home'     => null,
        'score_away'     => null,
        'profit_rate'    => 50.00, // Đặt một tỉ lệ mặc định cho "Tỉ số khác"
        'decimal_odds'   => 51.00,
        'status'         => 'ACTIVE',
        'display_order'  => $displayOrder++,
    ]);
    
    echo "  ✓ Đã tạo Tỉ số khác (ăn 50.00)\n";
    echo "\nHoàn tất! Đã tạo kèo Tỉ số chính xác với " . (count($exactScores) + 1) . " outcomes.\n";
});
