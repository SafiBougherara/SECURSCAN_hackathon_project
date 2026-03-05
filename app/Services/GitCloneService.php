<?php

namespace App\Services;

use Symfony\Component\Process\Process;
use Exception;

class GitCloneService
{
    public function clone(string $repoUrl, string|int $scanId): string
    {
        // Simple validation to ensure it's a repository and not just an organization
        if (!preg_match('/github\.com\/[^\/]+\/[^\/]+/', $repoUrl)) {
            throw new Exception("Invalid GitHub repository URL. Please provide a full repository URL (ex: https://github.com/user/repo).");
        }

        $path = storage_path("app/repos/{$scanId}");

        $process = new Process([
            'git',
            '-c',
            'http.sslBackend=schannel',
            '-c',
            'http.sslVerify=false',
            'clone',
            '--depth',
            '1',
            $repoUrl,
            $path
        ]);
        $process->setTimeout(120);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new Exception("Git clone failed: " . $process->getErrorOutput());
        }

        return $path;
    }

    public function cleanup(string|int $scanId): void
    {
        $path = storage_path("app/repos/{$scanId}");
        if (is_dir($path)) {
            $this->deleteDirectory($path);
        }
    }

    private function deleteDirectory(string $dir): void
    {
        if (!is_dir($dir))
            return;
        $process = new Process(['cmd', '/c', 'rmdir', '/s', '/q', $dir]);
        $process->run();
    }
}
