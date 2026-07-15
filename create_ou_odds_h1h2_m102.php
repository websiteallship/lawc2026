<?php

use App\Models\FootballMatch;
use App\Models\Market;
use App\Models\MarketOutcome;
use Illuminate\Support\Facades\DB;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$matchCode = 'M102'; // England vs Argentina
$match = FootballMatch::where('match_code', $matchCode)->first();

if (!$match) {
    echo "Không tìm thấy trận đấu $matchCode.\n";
    exit(1);
}

// Data format: [Line, Over Odds, Under Odds]
$h1Data = [
    [0.5, 1.50, 2.50],
    [0.75, 1.75, 2.13],
    [1, 2.20, 1.65],
    [1.25, 2.75, 1.43],
    [1.5, 3.40, 1.30],
    [1.75, 4.50, 1.19],
    [2, 7.00, 1.10],
    [2.5, 10.00, 1.06],
    [3.5, 26.00, 1.01],
];

$h2Data = [
    [0.5, 1.30, 3.40],
    [1.5, 2.50, 1.50],
    [2.5, 6.00, 1.13],
    [3.5, 17.00, 1.02],
    [4.5, 26.00, 1.01],
];

DB::transaction(function () use ($match, $h1Data, $h2Data) {
    
    // --- HIỆP 1 ---
    $h1Market = Market::updateOrCreate(
        [
            'match_id' => $match->id,
            'market_type' => 'OVER_UNDER',
            'period_type' => 'FIRST_HALF',
        ],
        [
            'name' => 'Tài Xỉu (Hiệp 1)',
            'status' => 'OPEN',
            'open_at' => now(),
            'close_at' => $match->kickoff_at->clone()->addMinutes(45),
            'display_order' => 4,
        ]
    );

    foreach ($h1Data as $data) {
        $line = (float)$data[0];
        $overOdds = (float)$data[1];
        $underOdds = (float)$data[2];

        MarketOutcome::updateOrCreate(
            ['market_id' => $h1Market->id, 'selection_side' => 'OVER', 'line_value' => $line],
            ['label' => "Tài $line", 'profit_rate' => round($overOdds - 1, 3), 'decimal_odds' => $overOdds, 'status' => 'ACTIVE', 'display_order' => 1]
        );

        MarketOutcome::updateOrCreate(
            ['market_id' => $h1Market->id, 'selection_side' => 'UNDER', 'line_value' => $line],
            ['label' => "Xỉu $line", 'profit_rate' => round($underOdds - 1, 3), 'decimal_odds' => $underOdds, 'status' => 'ACTIVE', 'display_order' => 2]
        );
    }

    // --- HIỆP 2 ---
    $h2Market = Market::updateOrCreate(
        [
            'match_id' => $match->id,
            'market_type' => 'OVER_UNDER',
            'period_type' => 'SECOND_HALF',
        ],
        [
            'name' => 'Tài Xỉu (Hiệp 2)',
            'status' => 'OPEN',
            'open_at' => now(),
            'close_at' => $match->kickoff_at->clone()->addMinutes(110),
            'display_order' => 5,
        ]
    );

    foreach ($h2Data as $data) {
        $line = (float)$data[0];
        $overOdds = (float)$data[1];
        $underOdds = (float)$data[2];

        MarketOutcome::updateOrCreate(
            ['market_id' => $h2Market->id, 'selection_side' => 'OVER', 'line_value' => $line],
            ['label' => "Tài $line", 'profit_rate' => round($overOdds - 1, 3), 'decimal_odds' => $overOdds, 'status' => 'ACTIVE', 'display_order' => 1]
        );

        MarketOutcome::updateOrCreate(
            ['market_id' => $h2Market->id, 'selection_side' => 'UNDER', 'line_value' => $line],
            ['label' => "Xỉu $line", 'profit_rate' => round($underOdds - 1, 3), 'decimal_odds' => $underOdds, 'status' => 'ACTIVE', 'display_order' => 2]
        );
    }

    echo "Đã tạo toàn bộ kèo Tài Xỉu (Hiệp 1 và Hiệp 2) cho trận {$match->home_team} vs {$match->away_team} thành công!\n";
});
