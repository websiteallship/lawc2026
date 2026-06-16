<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$totalBets = \App\Models\Bet::count();
$nullSeason = \App\Models\Bet::whereNull('season_id')->count();
echo "Total bets: $totalBets\n";
echo "Null season bets: $nullSeason\n";
$firstBet = \App\Models\Bet::first();
if ($firstBet) {
    echo "First bet season_id: " . $firstBet->season_id . "\n";
}

$user = \App\Models\User::where('name', 'like', '%Sâu nhút nhát%')->first();
if ($user) {
    echo "User ID: " . $user->id . "\n";
    $bets = \App\Models\Bet::where('user_id', $user->id)->count();
    echo "Bets for Sâu nhút nhát: " . $bets . "\n";
    $wallet = \App\Models\Wallet::where('user_id', $user->id)->first();
    echo "Wallet season_id: " . $wallet->season_id . "\n";
} else {
    echo "User Sâu nhút nhát not found\n";
}
