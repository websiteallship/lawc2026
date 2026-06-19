<?php
// Debug DEDICATION_7_DAYS trên VPS
// Chạy: php artisan tinker debug_dedication.php

// Lấy 1 user để test (thay $userId nếu cần)
$userId = 1;

echo "=== Timezone info ===\n";
echo "PHP timezone: " . date_default_timezone_get() . "\n";
echo "DB connection: " . config('database.default') . "\n";

$rawDate = DB::selectOne("SELECT NOW() as now, NOW() AT TIME ZONE 'Asia/Ho_Chi_Minh' as vntime");
echo "DB NOW(): " . $rawDate->now . "\n";
echo "DB NOW() VN: " . $rawDate->vntime . "\n";

echo "\n=== Placed_at raw (3 bets gần nhất) ===\n";
$rawBets = DB::select("SELECT id, placed_at, placed_at AT TIME ZONE 'Asia/Ho_Chi_Minh' as placed_vn, DATE(placed_at AT TIME ZONE 'Asia/Ho_Chi_Minh') as date_vn FROM bets WHERE user_id = $userId ORDER BY placed_at DESC LIMIT 10");
foreach ($rawBets as $b) {
    echo "Bet#{$b->id}: UTC={$b->placed_at} | VN={$b->placed_vn} | DATE_VN={$b->date_vn}\n";
}

echo "\n=== Group by date (top 10 days) ===\n";
$days = DB::select("SELECT DATE(placed_at AT TIME ZONE 'Asia/Ho_Chi_Minh') as date, COUNT(*) as cnt FROM bets WHERE user_id = $userId GROUP BY date ORDER BY date DESC LIMIT 10");
foreach ($days as $d) {
    echo "Date: {$d->date} | Bets: {$d->cnt}\n";
}

echo "\n=== Consecutive streak calculation ===\n";
$allDates = array_column(DB::select("SELECT DATE(placed_at AT TIME ZONE 'Asia/Ho_Chi_Minh') as date FROM bets WHERE user_id = $userId GROUP BY date ORDER BY date DESC"), 'date');
echo "All bet dates: " . implode(', ', $allDates) . "\n";

$streak = count($allDates) > 0 ? 1 : 0;
for ($i = 0; $i < count($allDates) - 1; $i++) {
    $d1 = Carbon\Carbon::parse($allDates[$i])->startOfDay();
    $d2 = Carbon\Carbon::parse($allDates[$i + 1])->startOfDay();
    $diff = (int) $d1->diffInDays($d2);
    echo "  {$allDates[$i]} -> {$allDates[$i+1]}: diff={$diff} days " . ($diff === 1 ? "✓" : "✗ BREAK") . "\n";
    if ($diff === 1) {
        $streak++;
    } else {
        break;
    }
}
echo "Current streak: $streak days\n";
echo "Achievement requires: 7 days\n";
echo "Should award: " . ($streak >= 7 ? 'YES' : 'NO') . "\n";
