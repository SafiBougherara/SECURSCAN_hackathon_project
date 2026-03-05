<?php

namespace App\Services;

use Symfony\Component\Process\Process;

class EslintService
{
    public function run(string $path): array
    {
        if (!file_exists("{$path}/package.json"))
            return [];

        $process = new Process(['npx', 'eslint', '--format', 'json', $path]);
        $process->setTimeout(90);
        $process->run();

        $output = json_decode($process->getOutput(), true);
        if (!is_array($output))
            return [];

        $findings = [];
        foreach ($output as $file) {
            $filePath = $this->relativePath($file['filePath'] ?? '', $path);
            foreach ($file['messages'] ?? [] as $msg) {
                $findings[] = [
                    'tool' => 'eslint',
                    'check_id' => $msg['ruleId'] ?? null,
                    'file_path' => $filePath,
                    'line_start' => $msg['line'] ?? null,
                    'severity' => ($msg['severity'] ?? 1) >= 2 ? 'high' : 'medium',
                    'message' => $msg['message'] ?? '',
                    'owasp_raw' => null,
                ];
            }
        }
        return $findings;
    }

    private function relativePath(string $fullPath, string $base): string
    {
        return ltrim(str_replace($base, '', $fullPath), DIRECTORY_SEPARATOR . '/');
    }
}
