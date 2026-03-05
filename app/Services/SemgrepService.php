<?php

namespace App\Services;

use Symfony\Component\Process\Process;

class SemgrepService
{
    public function run(string $path): array
    {
        $binary = env('SEMGREP_PATH', 'semgrep');
        $scriptsDir = dirname($binary);

        $process = new Process([
            $binary,
            'scan',
            '--config',
            'auto',
            $path,
            '--json',
            '--timeout',
            '60',
            '--no-git-ignore'
        ]);

        // Fix for Windows: Semgrep needs its Scripts dir in PATH to find pysemgrep
        // and PYTHONUTF8=1 to handle unicode output correctly.
        $env = [
            'PYTHONUTF8' => '1',
            'PATH' => $scriptsDir . ';' . getenv('PATH')
        ];
        $process->setEnv($env);

        $process->setTimeout(120);
        $process->run();

        $output = json_decode($process->getOutput(), true);
        if (!isset($output['results']))
            return [];

        $findings = [];
        foreach ($output['results'] as $result) {
            $findings[] = [
                'tool' => 'semgrep',
                'check_id' => $result['check_id'] ?? null,
                'file_path' => $this->relativePath($result['path'] ?? '', $path),
                'line_start' => $result['start']['line'] ?? null,
                'severity' => $this->mapSeverity($result['extra']['severity'] ?? 'INFO'),
                'message' => $result['extra']['message'] ?? '',
                'owasp_raw' => $result['extra']['metadata']['owasp'] ?? null,
                // Semgrep provides the vulnerable lines directly
                'code_snippet' => isset($result['extra']['lines']) ? trim($result['extra']['lines']) : null,
            ];
        }
        return $findings;
    }

    private function mapSeverity(string $s): string
    {
        return match (strtoupper($s)) {
            'CRITICAL' => 'critical',
            'ERROR', 'HIGH' => 'high',
            'WARNING', 'MEDIUM' => 'medium',
            'INFO', 'LOW' => 'low',
            default => 'info',
        };
    }

    private function relativePath(string $fullPath, string $base): string
    {
        return ltrim(str_replace(str_replace('/', DIRECTORY_SEPARATOR, $base), '', $fullPath), DIRECTORY_SEPARATOR . '/');
    }
}
