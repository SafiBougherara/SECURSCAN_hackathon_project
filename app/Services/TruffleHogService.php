<?php

namespace App\Services;

use Symfony\Component\Process\Process;

class TruffleHogService
{
    public function run(string $path): array
    {
        $binary = env('TRUFFLEHOG_PATH', file_exists(base_path('trufflehog.exe')) ? base_path('trufflehog.exe') : 'trufflehog');
        $process = new Process([$binary, 'filesystem', $path, '--json', '--no-update']);
        $process->setTimeout(120);
        $process->run();

        $findings = [];
        foreach (explode("\n", $process->getOutput()) as $line) {
            $line = trim($line);
            if (empty($line))
                continue;
            $result = json_decode($line, true);
            if (!$result || !isset($result['DetectorName']))
                continue;

            $filePath = $result['SourceMetadata']['Data']['Filesystem']['file'] ?? null;
            if ($filePath) {
                $filePath = $this->relativePath($filePath, $path);
            }

            $findings[] = [
                'tool' => 'trufflehog',
                'check_id' => $result['DetectorName'] ?? 'secret-leak',
                'file_path' => $filePath,
                'line_start' => $result['SourceMetadata']['Data']['Filesystem']['line'] ?? null,
                'severity' => 'critical',
                'message' => 'Secret detected: ' . ($result['DetectorName'] ?? 'unknown secret'),
                'owasp_raw' => 'A08:2021',
            ];
        }
        return $findings;
    }

    private function relativePath(string $fullPath, string $base): string
    {
        return ltrim(str_replace(str_replace('/', DIRECTORY_SEPARATOR, $base), '', $fullPath), DIRECTORY_SEPARATOR . '/');
    }
}
