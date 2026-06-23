<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Mission;

$missions = Mission::where('type', 'weekly')->get();
foreach ($missions as $m) {
    echo "ID: {$m->id} | {$m->code} | Target: {$m->target_value} | Active: {$m->is_active}\n";
}
