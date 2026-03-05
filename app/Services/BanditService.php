<?php

namespace App\Services;

use Symfony\Component\Process\Process;

class BanditService
{
    public function run(string $path): array
    {
        $pyFiles = glob("{$path}/**/*.py", GLOB_BRACE) ?: glob("{$path}/*.py") ?: [];
        if (empty($pyFiles)) {
            // try recursive check
            $iter = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path));
            $found = false;
            foreach ($iter as $file) {
                if ($file->getExtension() === 'py') {
                    $found = true;
                    break;
                }
            }
            if (!$found)
                return [];
        }

        $binary = env('BANDIT_PATH', 'bandit');
        $scriptsDir = dirname($binary);

        $process = new Process([$binary, '-r', $path, '-f', 'json']);

        $env = [
            'PYTHONUTF8' => '1',
            'PATH' => $scriptsDir . ';' . getenv('PATH')
        ];
        $process->setEnv($env);

        $process->setTimeout(300);
        $process->run();

        $output = json_decode($process->getOutput(), true);
        if (!isset($output['results']))
            return [];

        $findings = [];
        foreach ($output['results'] as $result) {
            // Bandit provides `code` with the actual vulnerable code snippet
            $snippet = isset($result['code']) ? trim($result['code']) : null;

            $findings[] = [
                'tool' => 'bandit',
                'check_id' => $result['test_id'] ?? null,
                'file_path' => $result['filename'] ?? null,
                'line_start' => $result['line_number'] ?? null,
                'severity' => strtolower($result['issue_severity'] ?? 'low'),
                'message' => $result['issue_text'] ?? '',
                'owasp_raw' => null,
                'code_snippet' => $snippet,
            ];
        }
        return $findings;
    }
}
