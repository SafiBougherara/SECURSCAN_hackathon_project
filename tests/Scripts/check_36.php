<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$scan = App\Models\Scan::find(36);
if ($scan) {
    foreach ($scan->vulnerabilities as $v) {
        echo "TOOL: " . $v->tool . " | FILE: " . $v->file_path . "\n";
    }
} else {
    echo "Scan 36 not found.\n";
}
