<?php
/**
 * Script tạo kèo Asian Handicap (Full Time) cho trận tranh hạng 3 M103
 * Pháp vs Anh - 19/07/2026 04:00 UTC
 * Gộp tất cả các mốc (lines) vào 1 kèo chung (Market) duy nhất.
 *
 * Match ID: 104 (match_code M103)
 * Pháp = HOME (kèo trên), Anh = AWAY (kèo dưới)
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

// Xóa các kèo handicap FT cũ (nếu có) để tạo lại
$oldMarkets = Market::where('match_id', $match->id)
    ->where('market_type', 'ASIAN_HANDICAP')
    ->where('period_type', 'FULL_TIME')
    ->get();

if ($oldMarkets->count() > 0) {
    echo "Đang xóa {$oldMarkets->count()} kèo cũ...\n";
    foreach ($oldMarkets as $old) {
        $old->outcomes()->delete();
        $old->delete();
    }
}

// ─── HANDICAP LINES DATA ──────────────────────────────────
$handicapLines = [
    '0'    => [1.40, 2.85],
    '0.25' => [1.63, 2.25],
    '0.5'  => [1.88, 1.98],
    '0.75' => [2.08, 1.73],
    '1'    => [2.42, 1.53],
    '1.25' => [2.75, 1.43],
    '1.5'  => [3.00, 1.38],
    '1.75' => [3.70, 1.26],
    '2'    => [5.25, 1.16],
    '2.25' => [5.50, 1.15],
    '2.5'  => [5.90, 1.13],
];

$openAt = now()->toDateTimeString();
$closeAt = $match->kickoff_at->toDateTimeString();

DB::transaction(function () use ($match, $handicapLines, $openAt, $closeAt) {
    // Tạo 1 Market chung
    $market = Market::create([
        'match_id'      => $match->id,
        'period_type'   => 'FULL_TIME',
        'market_type'   => 'ASIAN_HANDICAP',
        'name'          => 'Kèo chấp (AH) - Cả trận',
        'open_at'       => $openAt,
        'close_at'      => $closeAt,
        'status'        => 'OPEN',
        'display_order' => 1,
        'created_by'    => 1,
    ]);

    $displayOrder = 1;

    foreach ($handicapLines as $lineAbs => $decimalOdds) {
        $lineAbs = (float) $lineAbs;
        $homeDecimal = $decimalOdds[0];
        $awayDecimal = $decimalOdds[1];

        $homeProfitRate = round($homeDecimal - 1, 4);
        $awayProfitRate = round($awayDecimal - 1, 4);

        $lineLabel = $lineAbs == 0 ? '0' : rtrim(rtrim(number_format($lineAbs, 2), '0'), '.');

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
            'display_order'  => $displayOrder++,
        ]);

        $awayLineValue = $lineAbs;
        $awayLabel = $lineAbs == 0 ? "{$match->away_team} 0" : "{$match->away_team} +{$awayLineValue}";

        MarketOutcome::create([
            'market_id'      => $market->id,
            'label'          => $awayLabel,
            'selection_side' => 'AWAY',
            'line_value'     => $awayLineValue,
            'profit_rate'    => $awayProfitRate,
            'decimal_odds'   => $awayDecimal,
            'status'         => 'ACTIVE',
            'display_order'  => $displayOrder++,
        ]);
        
        echo "  ✓ Added Line {$lineLabel}\n";
    }
});

echo "Hoàn tất! Đã tạo 1 kèo chung gồm " . (count($handicapLines) * 2) . " outcomes.\n";
