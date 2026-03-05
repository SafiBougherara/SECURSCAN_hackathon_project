<?php
require 'vendor/autoload.php';
use Symfony\Component\Process\Process;

file_put_contents('test_secrets.txt', "AWS_KEY=AKIAQYLPMN5HHHFPZAM2\nAWS_SECRET=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\n");

$binary = 'c:\laragon\www\Projet_hackathon\trufflehog.exe';
$path = __DIR__ . '/test_secrets.txt';

$process = new Process([$binary, 'filesystem', $path, '--json', '--no-update']);
$process->run();

$output = $process->getOutput();
echo "Output length: " . strlen($output) . "\n";
echo "Raw output start: " . substr($output, 0, 200) . "...\n";

foreach (explode("\n", $output) as $line) {
    if (empty(trim($line)))
        continue;
    $result = json_decode($line, true);
    if (!$result) {
        echo "Failed to decode: " . $line . "\n";
        continue;
    }
    if (!isset($result['DetectorName'])) {
        echo "No DetectorName in: " . $line . "\n";
        continue;
    }
    echo "Found result: " . $result['DetectorName'] . " in " . ($result['SourceMetadata']['Data']['Filesystem']['file'] ?? 'unknown') . "\n";
}

unlink('test_secrets.txt');
