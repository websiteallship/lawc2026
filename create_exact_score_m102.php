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

// Format: ["score", decimal_odds]
$scoresData = [
    // England win
    ["1:0", 8.00], ["2:0", 13.00], ["2:1", 11.00], ["3:0", 29.00], ["3:1", 26.00], 
    ["3:2", 41.00], ["4:0", 51.00], ["4:1", 51.00], ["4:2", 81.00], ["4:3", 151.00], 
    ["5:0", 151.00], ["5:1", 151.00], ["5:2", 251.00], ["5:3", 501.00], 
    ["6:0", 501.00], ["6:1", 501.00], ["6:2", 451.00],

    // Draw
    ["0:0", 7.00], ["1:1", 5.50], ["2:2", 15.00], ["3:3", 51.00], ["4:4", 401.00],

    // Argentina win
    ["0:1", 8.50], ["0:2", 15.00], ["1:2", 12.00], ["0:3", 34.00], ["1:3", 29.00], 
    ["2:3", 41.00], ["0:4", 67.00], ["1:4", 67.00], ["2:4", 81.00], ["3:4", 151.00], 
    ["0:5", 201.00], ["1:5", 201.00], ["2:5", 301.00], ["3:5", 501.00], ["1:6", 401.00]
];

DB::transaction(function () use ($match, $scoresData) {
    $market = Market::updateOrCreate(
        [
            'match_id' => $match->id,
            'market_type' => 'EXACT_SCORE',
            'period_type' => 'FULL_TIME',
        ],
        [
            'name' => 'Tỉ số chính xác (Cả trận)',
            'status' => 'OPEN',
            'open_at' => now(),
            'close_at' => $match->kickoff_at->clone()->addMinutes(60),
            'display_order' => 6,
        ]
    );

    foreach ($scoresData as $index => $data) {
        $score = $data[0];
        $odds = (float)$data[1];
        
        $parts = explode(':', $score);
        $scoreHome = (int)$parts[0];
        $scoreAway = (int)$parts[1];

        MarketOutcome::updateOrCreate(
            [
                'market_id' => $market->id,
                'score_home' => $scoreHome,
                'score_away' => $scoreAway,
            ],
            [
                'label' => $score,
                'profit_rate' => round($odds - 1, 3),
                'decimal_odds' => $odds,
                'status' => 'ACTIVE',
                'display_order' => $index + 1,
            ]
        );
    }

    echo "Đã tạo toàn bộ kèo Tỉ số chính xác (Cả trận) cho trận {$match->home_team} vs {$match->away_team} thành công!\n";
});
