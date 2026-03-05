<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\GitCloneService;
use App\Services\TruffleHogService;
use App\Models\Scan;

$git = new GitCloneService();
$trufflehog = new TruffleHogService();

$repo = 'https://github.com/trufflesecurity/test_keys';
$path = $git->clone($repo, 'test_truffle_debug');

echo "Cloned to $path\n";

$findings = $trufflehog->run($path);

echo "Found " . count($findings) . " findings with TruffleHog.\n";
foreach ($findings as $f) {
    echo "- " . $f['check_id'] . " in " . $f['file_path'] . "\n";
}

$git->cleanup('test_truffle_debug');
