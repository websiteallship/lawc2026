<?php
echo "=== DEDICATION_7_DAYS STREAK CHECK FOR ALL USERS ===\n";

$users = \App\Models\User::all();
foreach ($users as $user) {
    $userId = $user->id;
    
    // localDate helper logic
    $driver = config('database.default');
    $conn   = config("database.connections.{$driver}.driver");
    $localDate = match ($conn) {
        'pgsql'  => "DATE(placed_at AT TIME ZONE 'Asia/Ho_Chi_Minh')",
        'sqlite' => "DATE(datetime(placed_at, '+7 hours'))",
        default  => "DATE(placed_at)",
    };

    $allBetDays = \App\Models\Bet::where('user_id', $userId)
        ->selectRaw("{$localDate} as date")
        ->groupBy('date')
        ->orderBy('date', 'desc')
        ->pluck('date')
        ->toArray();

    if (count($allBetDays) === 0) continue;

    $dedicationStreak = 1;
    for ($i = 0; $i < count($allBetDays) - 1; $i++) {
        $d1 = \Carbon\Carbon::parse($allBetDays[$i])->startOfDay();
        $d2 = \Carbon\Carbon::parse($allBetDays[$i + 1])->startOfDay();
        if ((int) $d1->diffInDays($d2) === 1) {
            $dedicationStreak++;
        } else {
            break; // Gap → dừng
        }
    }
    
    echo "User ID: {$userId} | Name: {$user->name}\n";
    echo "All bet dates: " . implode(', ', $allBetDays) . "\n";
    echo "Dedication Streak: {$dedicationStreak}\n";
    echo "--------------------------\n";
}
