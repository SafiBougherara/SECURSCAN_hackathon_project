<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Scan;

$scan = Scan::find(26);
if ($scan) {
    echo "Scan ID: " . $scan->id . " Status: " . $scan->status . "\n";
    $vByTool = $scan->vulnerabilities->groupBy('tool')->map->count();
    print_r($vByTool->toArray());
} else {
    echo "Scan 26 not found.\n";
}
