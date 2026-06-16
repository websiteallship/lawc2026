<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$userId = \App\Models\User::first()->id;

$ledgers = \App\Models\WalletLedger::where('user_id', $userId)
    ->get(['id', 'type', 'user_id', 'season_id', 'deleted_at']);

echo "Total ledgers: " . $ledgers->count() . "\n";
foreach ($ledgers as $l) {
    echo "ID: {$l->id}, Type: " . (is_object($l->type) ? $l->type->value : $l->type) . ", User: {$l->user_id}, Season: {$l->season_id}\n";
}

$count = \App\Models\WalletLedger::where('user_id', $userId)
    ->whereIn('type', [
        \App\Enums\LedgerType::BET_WON, 
        \App\Enums\LedgerType::BET_LOST, 
    ])->count();
echo "Count with Enum: $count\n";

$countString = \App\Models\WalletLedger::where('user_id', $userId)
    ->whereIn('type', [
        \App\Enums\LedgerType::BET_WON->value, 
        \App\Enums\LedgerType::BET_LOST->value, 
    ])->count();
echo "Count with String: $countString\n";
