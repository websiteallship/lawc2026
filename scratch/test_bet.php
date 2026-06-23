<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$bet = \App\Models\Bet::with('outcome')->latest()->first();
echo json_encode($bet ? $bet->toArray() : [], JSON_PRETTY_PRINT);
