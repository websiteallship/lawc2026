<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$bet = \App\Models\Bet::with('outcome', 'market.match')->find(337);
echo json_encode($bet ? $bet->toArray() : [], JSON_PRETTY_PRINT);
