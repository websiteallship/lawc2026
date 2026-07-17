<?php
/**
 * Script tạo kèo Tỉ số chính xác (Exact Score Full Time) cho trận M104
 * Spain vs Argentina - 20/07/2026 02:00 UTC
 * Tỉ lệ được tạo tự động dựa trên dự đoán (Spain cửa trên nhẹ, trận đấu ít bàn thắng).
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
// Tây Ban Nha (Home) chấp nhẹ Argentina (Away). Kèo Tài xỉu khoảng 2-2.25.
$exactScores = [
    // Tây Ban Nha thắng
    [1, 0, 6.00],
    [2, 0, 9.00],
    [2, 1, 8.50],
    [3, 0, 20.00],
    [3, 1, 18.00],
    [3, 2, 35.00],
    [4, 0, 50.00],
    [4, 1, 45.00],
    [4, 2, 60.00],

    // Hòa
    [0, 0, 7.50],
    [1, 1, 5.00],
    [2, 2, 15.00],
    [3, 3, 60.00],
    [4, 4, 200.00],

    // Argentina thắng
    [0, 1, 7.50],
    [0, 2, 13.00],
    [1, 2, 11.00],
    [0, 3, 30.00],
    [1, 3, 28.00],
    [2, 3, 40.00],
    [0, 4, 70.00],
    [1, 4, 65.00],
    [2, 4, 80.00],
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
            'selection_side' => null,
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
        'profit_rate'    => 50.00,
        'decimal_odds'   => 51.00,
        'status'         => 'ACTIVE',
        'display_order'  => $displayOrder++,
    ]);
    
    echo "  ✓ Đã tạo Tỉ số khác (ăn 50.00)\n";
    echo "\nHoàn tất! Đã tạo kèo Tỉ số chính xác với " . (count($exactScores) + 1) . " outcomes.\n";
});
