<?php

namespace Tests\Unit;

use App\Services\OwaspMapperService;
use PHPUnit\Framework\TestCase;

class OwaspMapperServiceTest extends TestCase
{
    private OwaspMapperService $mapper;

    protected function setUp(): void
    {
        $this->mapper = new OwaspMapperService();
    }

    public function test_maps_semgrep_owasp_tag(): void
    {
        $result = $this->mapper->map([
            'owasp_raw' => 'A03:2021 - Injection',
            'tool' => 'semgrep',
            'message' => 'SQL injection vulnerability',
        ]);

        $this->assertSame('A03:2025', $result['owasp_category']);
        $this->assertSame('Injection', $result['owasp_label']);
    }

    public function test_falls_back_to_npm_audit_default(): void
    {
        $result = $this->mapper->map([
            'owasp_raw' => null,
            'tool' => 'npm_audit',
            'message' => 'Vulnerable dependency: lodash',
        ]);

        $this->assertSame('A06:2025', $result['owasp_category']);
    }

    public function test_falls_back_to_trufflehog_default(): void
    {
        $result = $this->mapper->map([
            'owasp_raw' => null,
            'tool' => 'trufflehog',
            'message' => 'Hardcoded secret detected',
        ]);

        $this->assertSame('A08:2025', $result['owasp_category']);
    }

    public function test_guesses_injection_from_sql_message(): void
    {
        $result = $this->mapper->map([
            'owasp_raw' => null,
            'tool' => 'eslint',
            'message' => 'Possible SQL injection via user input',
        ]);

        $this->assertSame('A03:2025', $result['owasp_category']);
    }

    public function test_guesses_secrets_from_api_key_message(): void
    {
        $result = $this->mapper->map([
            'owasp_raw' => null,
            'tool' => 'semgrep',
            'message' => 'Hardcoded api_key found in source',
        ]);

        $this->assertSame('A08:2025', $result['owasp_category']);
    }

    public function test_guesses_crypto_failure_from_ssl_message(): void
    {
        $result = $this->mapper->map([
            'owasp_raw' => null,
            'tool' => 'semgrep',
            'message' => 'Insecure SSL/TLS configuration detected',
        ]);

        $this->assertSame('A02:2025', $result['owasp_category']);
    }

    public function test_unknown_message_falls_back_to_a05(): void
    {
        $result = $this->mapper->map([
            'owasp_raw' => null,
            'tool' => 'unknown_tool',
            'message' => 'Some unrecognized issue in the codebase',
        ]);

        $this->assertSame('A05:2025', $result['owasp_category']);
    }

    public function test_extracts_a01_from_raw_tag(): void
    {
        $result = $this->mapper->map([
            'owasp_raw' => 'A01:2021 - Broken Access Control',
            'tool' => 'semgrep',
            'message' => 'Insecure direct object reference',
        ]);

        $this->assertSame('A01:2025', $result['owasp_category']);
        $this->assertSame('Broken Access Control', $result['owasp_label']);
    }
}
