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
$oddsData = [
    [0.5, 1.10, 7.00],
    [0.75, 1.11, 6.60],
    [1, 1.13, 5.90],
    [1.25, 1.27, 3.55],
    [1.5, 1.44, 2.75],
    [1.75, 1.53, 2.42],
    [2, 1.73, 2.08],
    [2.25, 2.10, 1.78],
    [2.5, 2.38, 1.57],
    [2.75, 2.75, 1.43],
    [3, 3.55, 1.27],
    [3.25, 3.90, 1.24],
    [3.5, 4.33, 1.22],
    [3.75, 5.75, 1.14],
    [4, 7.00, 1.10],
    [4.5, 10.00, 1.06],
    [5.5, 19.00, 1.02],
];

DB::transaction(function () use ($match, $oddsData) {
    $market = Market::updateOrCreate(
        [
            'match_id' => $match->id,
            'market_type' => 'OVER_UNDER',
            'period_type' => 'FULL_TIME',
        ],
        [
            'name' => 'Tài Xỉu (Cả trận)',
            'status' => 'OPEN',
            'open_at' => now(),
            'close_at' => $match->kickoff_at->clone()->addMinutes(60),
            'display_order' => 3,
        ]
    );

    foreach ($oddsData as $data) {
        $line = (float)$data[0];
        $overOdds = (float)$data[1];
        $underOdds = (float)$data[2];

        // TÀI (OVER)
        MarketOutcome::updateOrCreate(
            [
                'market_id' => $market->id,
                'selection_side' => 'OVER',
                'line_value' => $line,
            ],
            [
                'label' => "Tài $line",
                'profit_rate' => round($overOdds - 1, 3),
                'decimal_odds' => $overOdds,
                'status' => 'ACTIVE',
                'display_order' => 1,
            ]
        );

        // XỈU (UNDER)
        MarketOutcome::updateOrCreate(
            [
                'market_id' => $market->id,
                'selection_side' => 'UNDER',
                'line_value' => $line,
            ],
            [
                'label' => "Xỉu $line",
                'profit_rate' => round($underOdds - 1, 3),
                'decimal_odds' => $underOdds,
                'status' => 'ACTIVE',
                'display_order' => 2,
            ]
        );
    }
    
    // Xử lý riêng line 6.5 và 7.5 vì không có cửa Xỉu
    $singleLines = [
        [6.5, 41.00],
        [7.5, 51.00],
    ];
    
    foreach ($singleLines as $data) {
        $line = (float)$data[0];
        $overOdds = (float)$data[1];
        MarketOutcome::updateOrCreate(
            [
                'market_id' => $market->id,
                'selection_side' => 'OVER',
                'line_value' => $line,
            ],
            [
                'label' => "Tài $line",
                'profit_rate' => round($overOdds - 1, 3),
                'decimal_odds' => $overOdds,
                'status' => 'ACTIVE',
                'display_order' => 1,
            ]
        );
    }

    echo "Đã tạo toàn bộ kèo Tài Xỉu (Cả trận) cho trận {$match->home_team} vs {$match->away_team} thành công!\n";
});
