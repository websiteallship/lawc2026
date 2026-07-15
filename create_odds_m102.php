<?php

use App\Models\FootballMatch;
use App\Models\Market;
use App\Models\MarketOutcome;
use Illuminate\Support\Facades\DB;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$matchCode = 'M102';
$match = FootballMatch::where('match_code', $matchCode)->first();

if (!$match) {
    echo "Không tìm thấy trận đấu $matchCode.\n";
    exit(1);
}

DB::transaction(function () use ($match) {
    // 1. Kèo chấp Châu Á (Full Time) - Đồng banh (0)
    $ftMarket = Market::updateOrCreate(
        [
            'match_id' => $match->id,
            'market_type' => 'ASIAN_HANDICAP',
            'period_type' => 'FULL_TIME',
        ],
        [
            'name' => 'Asian Handicap (Full Time)',
            'status' => 'OPEN',
            'open_at' => now(),
            'close_at' => $match->kickoff_at->clone()->addMinutes(60),
            'display_order' => 1,
        ]
    );

    MarketOutcome::updateOrCreate(
        [
            'market_id' => $ftMarket->id,
            'selection_side' => 'HOME',
            'line_value' => 0.0,
        ],
        [
            'label' => 'Home 0.0',
            'profit_rate' => 0.830, // 1.83 - 1
            'decimal_odds' => 1.83,
            'status' => 'ACTIVE',
            'display_order' => 1,
        ]
    );

    MarketOutcome::updateOrCreate(
        [
            'market_id' => $ftMarket->id,
            'selection_side' => 'AWAY',
            'line_value' => 0.0,
        ],
        [
            'label' => 'Away 0.0',
            'profit_rate' => 1.080, // 2.08 - 1
            'decimal_odds' => 2.08,
            'status' => 'ACTIVE',
            'display_order' => 2,
        ]
    );

    // 2. Kèo chấp Châu Á (First Half) - Đồng banh (0)
    $fhMarket = Market::updateOrCreate(
        [
            'match_id' => $match->id,
            'market_type' => 'ASIAN_HANDICAP',
            'period_type' => 'FIRST_HALF',
        ],
        [
            'name' => 'Asian Handicap (First Half)',
            'status' => 'OPEN',
            'open_at' => now(),
            'close_at' => $match->kickoff_at->clone()->addMinutes(45),
            'display_order' => 2,
        ]
    );

    MarketOutcome::updateOrCreate(
        [
            'market_id' => $fhMarket->id,
            'selection_side' => 'HOME',
            'line_value' => 0.0,
        ],
        [
            'label' => 'Home 0.0',
            'profit_rate' => 0.830, // 1.83 - 1
            'decimal_odds' => 1.83,
            'status' => 'ACTIVE',
            'display_order' => 1,
        ]
    );

    MarketOutcome::updateOrCreate(
        [
            'market_id' => $fhMarket->id,
            'selection_side' => 'AWAY',
            'line_value' => 0.0,
        ],
        [
            'label' => 'Away 0.0',
            'profit_rate' => 1.000, // 2.00 - 1
            'decimal_odds' => 2.00,
            'status' => 'ACTIVE',
            'display_order' => 2,
        ]
    );

    echo "Đã tạo kèo Asian Handicap (FT và HT) cho trận {$match->home_team} vs {$match->away_team} thành công!\n";
});
