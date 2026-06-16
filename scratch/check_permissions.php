<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$operator = \Spatie\Permission\Models\Role::findByName('operator');
echo "Operator permissions:\n";
print_r($operator->permissions->pluck('name')->toArray());

echo "\nAll permissions:\n";
print_r(\Spatie\Permission\Models\Permission::pluck('name')->toArray());
