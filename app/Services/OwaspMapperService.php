<?php

namespace App\Services;

class OwaspMapperService
{
    // Map raw OWASP strings to normalized category codes
    private const OWASP_NORMALIZATION = [
        'A01' => ['label' => 'Broken Access Control', 'year' => '2025'],
        'A02' => ['label' => 'Cryptographic Failures', 'year' => '2025'],
        'A03' => ['label' => 'Injection', 'year' => '2025'],
        'A04' => ['label' => 'Insecure Design', 'year' => '2025'],
        'A05' => ['label' => 'Security Misconfiguration', 'year' => '2025'],
        'A06' => ['label' => 'Vulnerable Components', 'year' => '2025'],
        'A07' => ['label' => 'Auth Failures', 'year' => '2025'],
        'A08' => ['label' => 'Software Integrity Failures', 'year' => '2025'],
        'A09' => ['label' => 'Security Logging Failures', 'year' => '2025'],
        'A10' => ['label' => 'SSRF', 'year' => '2025'],
    ];

    // Tool-based default OWASP categories when no metadata is present
    private const TOOL_DEFAULTS = [
        'npm_audit' => 'A06',
        'trufflehog' => 'A08',
    ];

    public function map(array $finding): array
    {
        $owaspRaw = $finding['owasp_raw'] ?? null;
        $tool = $finding['tool'] ?? '';

        // Try to extract category code from raw string (e.g. "A05:2021 - XSS" → "A05")
        $category = null;
        if ($owaspRaw) {
            $rawStr = is_array($owaspRaw) ? implode(' ', $owaspRaw) : (string) $owaspRaw;
            if (preg_match('/A(\d{2})/i', $rawStr, $m)) {
                $category = strtoupper('A' . $m[1]);
            }
        }

        // Fall back to tool default
        if (!$category) {
            $category = self::TOOL_DEFAULTS[$tool] ?? $this->guessFromMessage($finding['message'] ?? '', $tool);
        }

        $meta = self::OWASP_NORMALIZATION[$category] ?? ['label' => 'Unknown', 'year' => '2025'];

        return [
            'owasp_category' => $category . ':' . $meta['year'],
            'owasp_label' => $meta['label'],
        ];
    }

    private function guessFromMessage(string $message, string $tool): string
    {
        $msg = strtolower($message);

        if (str_contains($msg, 'sql') || str_contains($msg, 'injection') || str_contains($msg, 'eval(')) {
            return 'A03';
        }
        if (str_contains($msg, 'xss') || str_contains($msg, 'cross-site')) {
            return 'A03';
        }
        if (str_contains($msg, 'secret') || str_contains($msg, 'token') || str_contains($msg, 'password') || str_contains($msg, 'api_key')) {
            return 'A08';
        }
        if (str_contains($msg, 'ssl') || str_contains($msg, 'tls') || str_contains($msg, 'http://') || str_contains($msg, 'crypto')) {
            return 'A02';
        }
        if (str_contains($msg, 'command') || str_contains($msg, 'exec') || str_contains($msg, 'shell')) {
            return 'A03';
        }
        if (str_contains($msg, 'access') || str_contains($msg, 'auth') || str_contains($msg, 'permission')) {
            return 'A01';
        }
        if ($tool === 'eslint')
            return 'A03';
        if ($tool === 'bandit')
            return 'A03';

        return 'A05'; // default: Security Misconfiguration
    }
}
