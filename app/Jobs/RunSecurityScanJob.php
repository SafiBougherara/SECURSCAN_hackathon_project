<?php

namespace App\Jobs;

use App\Models\Scan;
use App\Models\Vulnerability;
use App\Services\GitCloneService;
use App\Services\SemgrepService;
use App\Services\EslintService;
use App\Services\NpmAuditService;
use App\Services\TruffleHogService;
use App\Services\BanditService;
use App\Services\OwaspMapperService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class RunSecurityScanJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600;
    public int $tries = 1;

    public function __construct(public Scan $scan)
    {
    }

    public function handle(
        GitCloneService $git,
        SemgrepService $semgrep,
        EslintService $eslint,
        NpmAuditService $npmAudit,
        TruffleHogService $trufflehog,
        BanditService $bandit,
        OwaspMapperService $owasp,
    ): void {
        $scan = $this->scan;
        $scan->update(['status' => 'running']);
        $localPath = null;

        try {
            // 1. Clone the repo
            Log::info("Scan #{$scan->id}: Starting clone for {$scan->repo_url}");
            $localPath = $git->clone($scan->repo_url, $scan->id);
            Log::info("Scan #{$scan->id}: Clone successful at $localPath");

            // Extract repo name from URL
            $repoName = basename(rtrim(preg_replace('/\.git$/', '', $scan->repo_url), '/'));
            $scan->update(['repo_name' => $repoName]);

            // 2. Run all scanners
            Log::info("Scan #{$scan->id}: Running Semgrep...");
            $semgrepFindings = $semgrep->run($localPath);
            Log::info("Scan #{$scan->id}: Semgrep found " . count($semgrepFindings) . " vulnerabilities.");

            Log::info("Scan #{$scan->id}: Running ESLint...");
            $eslintFindings = $eslint->run($localPath);
            Log::info("Scan #{$scan->id}: ESLint found " . count($eslintFindings) . " vulnerabilities.");

            Log::info("Scan #{$scan->id}: Running NPM Audit...");
            $npmFindings = $npmAudit->run($localPath);
            Log::info("Scan #{$scan->id}: NPM Audit found " . count($npmFindings) . " vulnerabilities.");

            Log::info("Scan #{$scan->id}: Running TruffleHog...");
            $truffleFindings = $trufflehog->run($localPath);
            Log::info("Scan #{$scan->id}: TruffleHog found " . count($truffleFindings) . " vulnerabilities.");

            Log::info("Scan #{$scan->id}: Running Bandit...");
            $banditFindings = $bandit->run($localPath);
            Log::info("Scan #{$scan->id}: Bandit found " . count($banditFindings) . " vulnerabilities.");

            $allFindings = array_merge(
                $semgrepFindings,
                $eslintFindings,
                $npmFindings,
                $truffleFindings,
                $banditFindings
            );

            // 3. Map OWASP + extract code snippets + bulk insert
            Log::info("Scan #{$scan->id}: Mapping findings to OWASP and saving to database...");
            $toInsert = [];
            foreach ($allFindings as $finding) {
                $owaspData = $owasp->map($finding);

                // Get code snippet: use what the tool already provided, otherwise
                // extract a few lines directly from the cloned file
                $snippet = $finding['code_snippet'] ?? null;
                if (empty($snippet) && !empty($finding['file_path']) && !empty($finding['line_start'])) {
                    $snippet = $this->extractCodeSnippet(
                        $localPath . DIRECTORY_SEPARATOR . $finding['file_path'],
                        (int) $finding['line_start'],
                        5  // lines of context (2 before + target + 2 after)
                    );
                }

                $toInsert[] = [
                    'scan_id' => $scan->id,
                    'tool' => $finding['tool'],
                    'check_id' => $finding['check_id'] ?? null,
                    'file_path' => $finding['file_path'] ?? null,
                    'line_start' => $finding['line_start'] ?? null,
                    'severity' => $finding['severity'],
                    'message' => mb_substr($finding['message'], 0, 65535),
                    'owasp_category' => $owaspData['owasp_category'],
                    'owasp_label' => $owaspData['owasp_label'],
                    'fix_suggestion' => null,
                    'selected' => false,
                    'code_snippet' => $snippet ? mb_substr($snippet, 0, 2000) : null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            // Bulk insert in chunks for performance
            foreach (array_chunk($toInsert, 200) as $chunk) {
                Vulnerability::insert($chunk);
            }

            // 4. Calculate score
            $counts = Vulnerability::where('scan_id', $scan->id)
                ->selectRaw('severity, COUNT(*) as cnt')
                ->groupBy('severity')
                ->pluck('cnt', 'severity');

            $penalty = ($counts['critical'] ?? 0) * 15
                + ($counts['high'] ?? 0) * 8
                + ($counts['medium'] ?? 0) * 3
                + ($counts['low'] ?? 0) * 1;

            $score = max(0, 100 - $penalty);

            $scan->update(['status' => 'done', 'score' => $score]);

        } catch (Throwable $e) {
            Log::error("SecureScan job failed for scan #{$scan->id}: " . $e->getMessage());
            $scan->update(['status' => 'failed']);
        } finally {
            // 5. Cleanup cloned repo
            if ($localPath && is_dir($localPath)) {
                $git->cleanup($scan->id);
            }
        }
    }

    /**
     * Extract a code snippet from a file around the given line number.
     * Returns the target line + a few lines of context for readability.
     */
    private function extractCodeSnippet(string $filePath, int $lineNumber, int $contextLines = 5): ?string
    {
        if (!file_exists($filePath) || !is_readable($filePath)) {
            return null;
        }

        // Avoid reading huge binary files
        if (filesize($filePath) > 500 * 1024) {
            return null;
        }

        $lines = file($filePath, FILE_IGNORE_NEW_LINES);
        if ($lines === false) {
            return null;
        }

        $totalLines = count($lines);
        $start = max(0, $lineNumber - 1 - intdiv($contextLines, 2));
        $end = min($totalLines - 1, $lineNumber - 1 + intdiv($contextLines, 2));

        $snippet = [];
        for ($i = $start; $i <= $end; $i++) {
            $lineNum = $i + 1;
            $marker = ($lineNum === $lineNumber) ? '→ ' : '  ';
            $snippet[] = $marker . $lineNum . ': ' . $lines[$i];
        }

        return implode("\n", $snippet);
    }
}
