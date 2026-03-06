<?php
require 'vendor/autoload.php';
use Symfony\Component\Process\Process;

// Function to generate a fake AWS Access Key ID (AKIA...)
function generateFakeAwsAccessKeyId() {
    $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $key = 'AKIA'; // AWS Access Key IDs start with AKIA
    for ($i = 0; $i < 16; $i++) {
        $key .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $key;
}

// Function to generate a fake AWS Secret Access Key (40 base64 characters)
function generateFakeAwsSecretAccessKey() {
    $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/';
    $key = '';
    for ($i = 0; $i < 40; $i++) {
        $key .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $key;
}

$awsKey = generateFakeAwsAccessKeyId();
$awsSecret = generateFakeAwsSecretAccessKey();

file_put_contents('test_secrets.txt', "AWS_KEY=$awsKey\nAWS_SECRET=$awsSecret\n");

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