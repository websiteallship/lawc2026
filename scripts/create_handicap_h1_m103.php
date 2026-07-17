<?php
/**
 * Script tạo kèo Asian Handicap (First Half - Hiệp 1) cho trận M103
 * Pháp vs Anh - 19/07/2026 04:00 UTC
 * Gộp tất cả các mốc (lines) vào 1 kèo chung (Market) duy nhất.
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

// Xóa các kèo handicap H1 cũ (nếu có)
$oldMarkets = Market::where('match_id', $match->id)
    ->where('market_type', 'ASIAN_HANDICAP')
    ->where('period_type', 'FIRST_HALF')
    ->get();

if ($oldMarkets->count() > 0) {
    echo "Đang xóa {$oldMarkets->count()} kèo cũ...\n";
    foreach ($oldMarkets as $old) {
        $old->outcomes()->delete();
        $old->delete();
    }
}

// ─── HANDICAP LINES DATA (FIRST HALF) ─────────────────────
// home_line => [home_decimal_odds, away_decimal_odds]
$handicapLines = [
    '-1.5'  => [5.90, 1.13],
    '-1.25' => [5.25, 1.16],
    '-1'    => [4.40, 1.20],
    '-0.75' => [2.85, 1.40],
    '-0.5'  => [2.35, 1.58],
    '-0.25' => [1.98, 1.88],
    '0'     => [1.53, 2.42],
    '0.25'  => [1.35, 3.10],
    '0.5'   => [1.26, 3.70],
    '0.75'  => [1.16, 5.25],
];

$openAt = now()->toDateTimeString();
$closeAt = $match->kickoff_at->toDateTimeString();

DB::transaction(function () use ($match, $handicapLines, $openAt, $closeAt) {
    $market = Market::create([
        'match_id'      => $match->id,
        'period_type'   => 'FIRST_HALF',
        'market_type'   => 'ASIAN_HANDICAP',
        'name'          => 'Kèo chấp (AH) - Hiệp 1',
        'open_at'       => $openAt,
        'close_at'      => $closeAt,
        'status'        => 'OPEN',
        'display_order' => 1,
        'created_by'    => 1,
    ]);

    $displayOrder = 1;

    foreach ($handicapLines as $homeLine => $decimalOdds) {
        $homeLine = (float) $homeLine;
        $awayLine = -$homeLine;
        
        $homeDecimal = $decimalOdds[0];
        $awayDecimal = $decimalOdds[1];

        $homeProfitRate = round($homeDecimal - 1, 4);
        $awayProfitRate = round($awayDecimal - 1, 4);

        $lineAbs = abs($homeLine);
        $lineLabelStr = $lineAbs == 0 ? '0' : rtrim(rtrim(number_format($lineAbs, 2), '0'), '.');

        // HOME outcome
        if ($homeLine > 0) {
            $homeLabel = "{$match->home_team} +{$lineLabelStr}";
        } elseif ($homeLine < 0) {
            $homeLabel = "{$match->home_team} -{$lineLabelStr}";
        } else {
            $homeLabel = "{$match->home_team} 0";
        }

        MarketOutcome::create([
            'market_id'      => $market->id,
            'label'          => $homeLabel,
            'selection_side' => 'HOME',
            'line_value'     => $homeLine,
            'profit_rate'    => $homeProfitRate,
            'decimal_odds'   => $homeDecimal,
            'status'         => 'ACTIVE',
            'display_order'  => $displayOrder++,
        ]);

        // AWAY outcome
        if ($awayLine > 0) {
            $awayLabel = "{$match->away_team} +{$lineLabelStr}";
        } elseif ($awayLine < 0) {
            $awayLabel = "{$match->away_team} -{$lineLabelStr}";
        } else {
            $awayLabel = "{$match->away_team} 0";
        }

        MarketOutcome::create([
            'market_id'      => $market->id,
            'label'          => $awayLabel,
            'selection_side' => 'AWAY',
            'line_value'     => $awayLine,
            'profit_rate'    => $awayProfitRate,
            'decimal_odds'   => $awayDecimal,
            'status'         => 'ACTIVE',
            'display_order'  => $displayOrder++,
        ]);
        
        echo "  ✓ Added Line {$homeLine} (Home: {$homeLabel}, Away: {$awayLabel})\n";
    }
});

echo "Hoàn tất! Đã tạo 1 kèo chung H1 gồm " . (count($handicapLines) * 2) . " outcomes.\n";
