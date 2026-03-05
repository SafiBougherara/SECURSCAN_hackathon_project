<?php

namespace App\Services;

use App\Models\Vulnerability;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiFixService
{
    private string $apiKey;
    // gemini-1.5-flash is freely available and reliable
    // gemini-2.0-flash has exhausted free quota; gemini-2.5-flash is tested and working
    private string $model = 'gemini-2.5-flash';

    public function __construct()
    {
        $this->apiKey = config('services.gemini.key', '');
    }

    /**
     * Generate an AI fix for the given vulnerability.
     * Caches the result in the vulnerability's `ai_fix` column.
     */
    public function generateFix(Vulnerability $vuln): string
    {
        // Return cached fix if already generated
        if (!empty($vuln->ai_fix)) {
            return $vuln->ai_fix;
        }

        if (empty($this->apiKey)) {
            return '*AI fix unavailable — configure GEMINI_API_KEY in your .env.*';
        }

        $prompt = $this->buildPrompt($vuln);

        try {
            $response = Http::timeout(30)
                ->withoutVerifying()
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post("https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent?key={$this->apiKey}", [
                    'contents' => [
                        ['parts' => [['text' => $prompt]]]
                    ],
                    'generationConfig' => [
                        'temperature' => 0.2,
                        'maxOutputTokens' => 1024,
                    ],
                ]);

            if ($response->successful()) {
                $fix = $response->json('candidates.0.content.parts.0.text', '');
                $fix = trim($fix);

                if (!empty($fix)) {
                    $vuln->update(['ai_fix' => $fix]);
                    return $fix;
                }
            }

            Log::warning('Gemini AI fix failed', [
                'status' => $response->status(),
                'body' => substr($response->body(), 0, 500),
                'vuln' => $vuln->id,
            ]);
        } catch (\Throwable $e) {
            Log::error('AiFixService exception: ' . $e->getMessage(), ['vuln' => $vuln->id]);
        }

        return '*Could not generate AI fix. Please try again later.*';
    }

    /**
     * Generate fixes for a collection of vulnerabilities (batch, with caching).
     */
    public function generateFixesForMany(iterable $vulnerabilities): void
    {
        foreach ($vulnerabilities as $vuln) {
            if (empty($vuln->ai_fix)) {
                $this->generateFix($vuln);
                // Avoid hitting per-minute rate limits
                usleep(600_000); // 0.6s between calls
            }
        }
    }

    private function buildPrompt(Vulnerability $vuln): string
    {
        $file = $vuln->file_path ? "File: `{$vuln->file_path}`" : '';
        $line = $vuln->line_start ? "Line: {$vuln->line_start}" : '';
        $owasp = $vuln->owasp_category ? "OWASP: {$vuln->owasp_category} — {$vuln->owasp_label}" : '';

        $codeBlock = '';
        if (!empty($vuln->code_snippet)) {
            $codeBlock = "\nVulnerable code:\n```\n" . trim($vuln->code_snippet) . "\n```";
        }

        return <<<PROMPT
You are a security expert. Fix the following vulnerability found by a static analysis tool.

Severity: {$vuln->severity}
Tool: {$vuln->tool}
{$owasp}
{$file}
{$line}
Issue: {$vuln->message}
{$codeBlock}

Provide a concise fix:
- Return ONLY a markdown code block containing the corrected code.
- If it's a hardcoded secret, replace it with an environment variable (e.g., env('AWS_KEY')).
- DO NOT include any explanations, introduction, or text outside the code block.
PROMPT;
    }

    /**
     * Replaces the file content by instructing Gemini to fix the specific vulnerability.
     */
    public function applyFixToFile(Vulnerability $vuln, string $fileContent): string
    {
        if (empty($this->apiKey)) {
            return $fileContent;
        }

        $prompt = <<<PROMPT
You are a senior developer fixing a security vulnerability.
Given this file content:
```
{$fileContent}
```

And this vulnerability at line {$vuln->line_start} (or dependency):
Issue: {$vuln->message}
Tool: {$vuln->tool}

Return ONLY the complete fixed file content inside a single markdown code block. Do NOT include any trailing explanations, notes, or intros. Return ONLY the fully updated file content so it can be directly saved.
PROMPT;

        try {
            $response = Http::timeout(60) // Larger timeout for full files
                ->withoutVerifying()
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post("https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent?key={$this->apiKey}", [
                    'contents' => [
                        ['parts' => [['text' => $prompt]]]
                    ],
                    'generationConfig' => [
                        'temperature' => 0.1,
                        'maxOutputTokens' => 8192,
                    ]
                ]);

            if ($response->successful()) {
                $data = $response->json();
                $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';

                // Extract from markdown ```...``` block
                if (preg_match('/```[a-z]*\s*(.*?)\s*```/is', $text, $matches)) {
                    $fixed = trim($matches[1]);
                    // Basic sanity check: ensure it looks like code or at least has similar size
                    if (strlen($fixed) > (strlen($fileContent) * 0.2)) {
                        return $fixed;
                    }
                }

                // If it didn't use a code block or looked suspicious (too small), 
                // but we really want the fix, we check if it looks like code.
                // If it contains "This code is" or similar conversational patterns, reject it.
                if (preg_match('/(This code|As a|I have|Here is)/i', $text)) {
                    Log::warning('Gemini returned conversational text instead of code fix', ['vuln' => $vuln->id]);
                    return $fileContent;
                }
            } else {
                Log::warning('Gemini full fix failed', ['status' => $response->status(), 'vuln' => $vuln->id]);
            }
        } catch (\Throwable $e) {
            Log::error('AiFixService::applyFixToFile failed', ['error' => $e->getMessage()]);
        }

        return $fileContent; // Fallback to original content
    }
}
