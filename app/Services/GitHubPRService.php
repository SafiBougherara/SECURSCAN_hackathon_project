<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;

class GitHubPRService
{
    private string $token;
    private string $baseUrl = 'https://api.github.com';

    public function __construct(string $token = '')
    {
        $this->token = $token ?: config('services.github.token', '');
    }

    /**
     * Create an instance using the authenticated user's token,
     * falling back to the global .env GITHUB_TOKEN.
     */
    public static function forUser(User $user): static
    {
        $token = $user->github_token ?: config('services.github.token', '');
        return new static($token);
    }

    /**
     * Full PR flow using only the GitHub REST API (no git clone).
     * Creates a branch, commits a SECURITY_REPORT.md, then opens a PR.
     */
    public function createPullRequestViaApi(
        string $repoUrl,
        array $vulnerabilities
    ): string {
        ['owner' => $owner, 'repo' => $repo] = $this->parseRepoUrl($repoUrl);

        // 1 — Get SHA of default branch
        $repoInfo = $this->get("/repos/{$owner}/{$repo}");
        $defaultBranch = $repoInfo['default_branch'] ?? 'main';

        $refInfo = $this->get("/repos/{$owner}/{$repo}/git/refs/heads/{$defaultBranch}");
        $baseSha = $refInfo['object']['sha'];

        // 2 — Get base tree SHA
        $commitInfo = $this->get("/repos/{$owner}/{$repo}/git/commits/{$baseSha}");
        $baseTreeSha = $commitInfo['tree']['sha'];

        $tree = [];

        // 3 — Add SECURITY_REPORT.md blob
        $reportContent = $this->buildReport($vulnerabilities, $repoUrl);
        $reportBlob = $this->post("/repos/{$owner}/{$repo}/git/blobs", [
            'content' => base64_encode($reportContent),
            'encoding' => 'base64'
        ]);
        $tree[] = [
            'path' => 'SECURITY_REPORT.md',
            'mode' => '100644',
            'type' => 'blob',
            'sha' => $reportBlob['sha']
        ];

        // 4 — Process source files for actual fixes
        $aiService = app(\App\Services\AiFixService::class);
        $filesToFix = [];

        foreach ($vulnerabilities as $v) {
            // Group vulnerabilities by file (exclude pure repo-level findings)
            if (!empty($v->file_path)) {
                $filesToFix[$v->file_path][] = $v;
            }
        }
        foreach ($filesToFix as $filePath => $vulns) {
            try {
                // Normalize path for GitHub API (must use forward slashes)
                $normalizedPath = str_replace('\\', '/', $filePath);

                // Fetch current file content from GitHub
                $fileRes = $this->get("/repos/{$owner}/{$repo}/contents/{$normalizedPath}?ref={$defaultBranch}");
                if (!isset($fileRes['content']))
                    continue;

                $content = base64_decode($fileRes['content']);
                $patchedContent = $content;

                // Detect line endings to preserve them (Windows vs Linux)
                $isCrlf = str_contains($content, "\r\n");

                foreach ($vulns as $v) {
                    if ($v->tool === 'npm_audit' && $filePath === 'package.json') {
                        // ... (keep existing npm_audit logic)
                        $pkgName = $v->check_id;
                        $targetVer = 'latest';
                        if ($v->ai_fix && preg_match('/(?:\"|\')' . preg_quote($pkgName, '/') . '(?:\"|\')\s*:\s*(?:\"|\')([^\'\"]+)(?:\"|\')/', $v->ai_fix, $m)) {
                            $targetVer = $m[1];
                        }
                        $patchedContent = preg_replace(
                            '/(?:\"|\')' . preg_quote($pkgName, '/') . '(?:\"|\')\s*:\s*(?:\"|\')[^\'\"]+(?:\"|\')/',
                            "\"{$pkgName}\": \"{$targetVer}\"",
                            $patchedContent
                        );
                    } else {
                        // Use Gemini for code fixes
                        $newContent = $aiService->applyFixToFile($v, $patchedContent);

                        // If we were CRLF and AI returned LF, convert back to prevent messy diffs
                        if ($isCrlf && !str_contains($newContent, "\r\n")) {
                            $newContent = str_replace("\n", "\r\n", $newContent);
                        }

                        $patchedContent = $newContent;
                        usleep(500_000);
                    }
                }

                // Append modified file to tree
                if ($patchedContent !== $content) {
                    $blob = $this->post("/repos/{$owner}/{$repo}/git/blobs", [
                        'content' => base64_encode($patchedContent),
                        'encoding' => 'base64'
                    ]);
                    $tree[] = [
                        'path' => $normalizedPath,
                        'mode' => '100644',
                        'type' => 'blob',
                        'sha' => $blob['sha']
                    ];
                }
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning("Failed to fix file {$filePath} for PR", ['error' => $e->getMessage()]);
                // Silently continue (the SECURITY_REPORT will still be there)
            }
        }

        // 5 — Create new Git Tree
        $newTree = $this->post("/repos/{$owner}/{$repo}/git/trees", [
            'base_tree' => $baseTreeSha,
            'tree' => $tree
        ]);

        // 6 — Create Commit
        $newCommit = $this->post("/repos/{$owner}/{$repo}/git/commits", [
            'message' => '🔒 [SecureScan] Apply security fixes and add vulnerability report',
            'tree' => $newTree['sha'],
            'parents' => [$baseSha]
        ]);

        // 7 — Create Branch Ref
        $branchName = 'securescan/report-' . now()->format('Ymd-His');
        $this->post("/repos/{$owner}/{$repo}/git/refs", [
            'ref' => "refs/heads/{$branchName}",
            'sha' => $newCommit['sha']
        ]);

        // 8 — Open the Pull Request
        $critical = collect($vulnerabilities)->where('severity', 'critical')->count();
        $high = collect($vulnerabilities)->where('severity', 'high')->count();

        $pr = $this->post("/repos/{$owner}/{$repo}/pulls", [
            'title' => "🔒 [SecureScan] Security Report — {$critical} Critical, {$high} High",
            'head' => $branchName,
            'base' => $defaultBranch,
            'body' => $this->buildPRBody($vulnerabilities),
        ]);

        return $pr['html_url'];
    }

    private function buildReport(array $vulnerabilities, string $repoUrl): string
    {
        $date = now()->format('Y-m-d H:i');
        $lines = ["# 🔒 SecureScan Security Report", "", "**Scanned:** `{$repoUrl}`  ", "**Date:** {$date}", ""];

        $bySeverity = collect($vulnerabilities)->groupBy('severity');
        foreach (['critical', 'high', 'medium', 'low', 'info'] as $sev) {
            $group = $bySeverity->get($sev, collect());
            if ($group->isEmpty())
                continue;
            $lines[] = "## " . strtoupper($sev) . " ({$group->count()})";
            foreach ($group as $v) {
                $file = $v->file_path ? " — `{$v->file_path}`" . ($v->line_start ? ":{$v->line_start}" : '') : '';
                $lines[] = "- **[{$v->owasp_category}]** {$v->message}{$file}";

                // Include AI fix if available
                $fix = $v->ai_fix ?? $v->fix_suggestion ?? null;
                if ($fix) {
                    $lines[] = "";
                    $lines[] = "  **AI Fix:**";

                    // Clean fix: remove starting/ending code block markers if AI included them (prevents nesting)
                    $cleanFix = preg_replace('/^```[a-z]*\s*|\s*```$/is', '', trim($fix));

                    $lines[] = "  ```";
                    foreach (explode("\n", trim($cleanFix)) as $fixLine) {
                        $lines[] = "  " . $fixLine;
                    }
                    $lines[] = "  ```";
                }
            }
            $lines[] = '';
        }

        $lines[] = "---";
        $lines[] = "*Generated by [SecureScan](https://github.com) — OWASP Top 10 2025*";

        return implode("\n", $lines);
    }

    private function buildPRBody(array $vulnerabilities): string
    {
        $total = count($vulnerabilities);
        $vulns = collect($vulnerabilities);
        $critical = $vulns->where('severity', 'critical')->count();
        $high = $vulns->where('severity', 'high')->count();
        $medium = $vulns->where('severity', 'medium')->count();
        $low = $vulns->where('severity', 'low')->count();
        $info = $vulns->where('severity', 'info')->count();

        // Collect top 3 AI fixes from critical/high
        $topFixes = $vulns
            ->whereIn('severity', ['critical', 'high'])
            ->filter(fn($v) => !empty($v->ai_fix) || !empty($v->fix_suggestion))
            ->take(3);

        $fixSection = '';
        if ($topFixes->isNotEmpty()) {
            $fixSection = "\n### 🔧 Top AI Fix Suggestions\n\n";
            foreach ($topFixes as $v) {
                $fix = $v->ai_fix ?? $v->fix_suggestion ?? '';
                $file = $v->file_path ? "`{$v->file_path}`" . ($v->line_start ? ":{$v->line_start}" : '') : 'N/A';
                $fixSection .= "**[" . strtoupper($v->severity) . "] {$v->owasp_category}** — {$file}\n";
                $fixSection .= "```\n" . trim($fix) . "\n```\n\n";
            }
        }

        return <<<MD
## 🔒 SecureScan — Automated Security Report

This PR was automatically generated by **SecureScan** after scanning this repository.

### Summary

| Severity | Count |
|----------|-------|
| 🔴 Critical | {$critical} |
| 🟠 High | {$high} |
| 🟡 Medium | {$medium} |
| 🔵 Low | {$low} |
| ⚪ Info | {$info} |
| **Total** | **{$total}** |

### What's included

A `SECURITY_REPORT.md` file has been added with the full list of detected vulnerabilities (classified by OWASP Top 10 2025) including AI-generated fix suggestions.
{$fixSection}
> ⚠️ Please review and address the Critical and High severity issues before merging.

*Generated by SecureScan*
MD;
    }

    private function parseRepoUrl(string $url): array
    {
        // https://github.com/owner/repo  or  https://github.com/owner/repo.git
        preg_match('#github\.com[/:]([\w.-]+)/([\w.-]+?)(?:\.git)?$#', $url, $m);
        return ['owner' => $m[1] ?? '', 'repo' => $m[2] ?? ''];
    }

    private function http()
    {
        return Http::withToken($this->token)
            ->withoutVerifying()  // cacert.pem path in php.ini may be misconfigured on Windows
            ->withHeaders(['Accept' => 'application/vnd.github+json']);
    }

    private function get(string $path): array
    {
        $response = $this->http()->get($this->baseUrl . $path);

        if ($response->failed()) {
            throw new \RuntimeException("GitHub API error [{$response->status()}] on GET {$path}: " . $response->body());
        }

        return $response->json();
    }

    private function post(string $path, array $data): array
    {
        $response = $this->http()->post($this->baseUrl . $path, $data);

        if ($response->failed()) {
            throw new \RuntimeException("GitHub API error [{$response->status()}] on POST {$path}: " . $response->body());
        }

        return $response->json();
    }

    private function put(string $path, array $data): array
    {
        $response = $this->http()->put($this->baseUrl . $path, $data);

        if ($response->failed()) {
            throw new \RuntimeException("GitHub API error [{$response->status()}] on PUT {$path}: " . $response->body());
        }

        return $response->json();
    }
}
