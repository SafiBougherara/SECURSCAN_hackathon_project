<?php

namespace Database\Seeders;

use App\Models\Scan;
use App\Models\Vulnerability;
use Illuminate\Database\Seeder;

class DemoScanSeeder extends Seeder
{
    public function run(): void
    {
        // Create a realistic demo scan (juice-shop - famous vulnerable app)
        $scan = Scan::create([
            'repo_url' => 'https://github.com/juice-shop/juice-shop',
            'repo_name' => 'juice-shop',
            'status' => 'done',
            'score' => 23,
        ]);

        $vulnerabilities = [
            // === CRITICAL ===
            [
                'tool' => 'semgrep',
                'check_id' => 'javascript.express.security.audit.xss.direct-response-write',
                'file_path' => 'routes/basket.ts',
                'line_start' => 42,
                'severity' => 'critical',
                'message' => 'User-controlled data flows directly into res.send(). This is a potential Cross-Site Scripting (XSS) vulnerability.',
                'owasp_category' => 'A03:2025',
                'owasp_label' => 'Injection',
                'fix_suggestion' => null,
            ],
            [
                'tool' => 'trufflehog',
                'check_id' => 'JWT',
                'file_path' => 'config/default.yml',
                'line_start' => 11,
                'severity' => 'critical',
                'message' => 'Secret detected: JWT signing secret hardcoded in configuration file.',
                'owasp_category' => 'A08:2025',
                'owasp_label' => 'Software Integrity Failures',
                'fix_suggestion' => null,
            ],
            [
                'tool' => 'semgrep',
                'check_id' => 'javascript.sequelize.security.audit.sequelize-injection',
                'file_path' => 'routes/search.ts',
                'line_start' => 17,
                'severity' => 'critical',
                'message' => 'SQL Injection via unsanitized user input passed to Sequelize query. Attacker can read/modify all database data.',
                'owasp_category' => 'A03:2025',
                'owasp_label' => 'Injection',
                'fix_suggestion' => null,
            ],
            [
                'tool' => 'trufflehog',
                'check_id' => 'AWSAccessKey',
                'file_path' => '.env.bak',
                'line_start' => 3,
                'severity' => 'critical',
                'message' => 'AWS Access Key exposed in backup .env file. Rotate credentials immediately.',
                'owasp_category' => 'A08:2025',
                'owasp_label' => 'Software Integrity Failures',
                'fix_suggestion' => null,
            ],

            // === HIGH ===
            [
                'tool' => 'semgrep',
                'check_id' => 'javascript.express.security.audit.express-path-traversal',
                'file_path' => 'routes/fileServer.ts',
                'line_start' => 88,
                'severity' => 'high',
                'message' => 'Path traversal vulnerability: user input used in file path without sanitization. Attacker can read arbitrary files.',
                'owasp_category' => 'A01:2025',
                'owasp_label' => 'Broken Access Control',
                'fix_suggestion' => null,
            ],
            [
                'tool' => 'npm_audit',
                'check_id' => 'jsonwebtoken',
                'file_path' => 'package.json',
                'line_start' => null,
                'severity' => 'high',
                'message' => 'Vulnerable dependency: jsonwebtoken — Improper type validation allows attackers to bypass verification.',
                'owasp_category' => 'A06:2025',
                'owasp_label' => 'Vulnerable Components',
                'fix_suggestion' => null,
            ],
            [
                'tool' => 'semgrep',
                'check_id' => 'javascript.express.security.audit.insecure-cookie',
                'file_path' => 'server.ts',
                'line_start' => 56,
                'severity' => 'high',
                'message' => 'Cookie set without HttpOnly and Secure flags. Session tokens are exposed to JavaScript and transmitted over HTTP.',
                'owasp_category' => 'A05:2025',
                'owasp_label' => 'Security Misconfiguration',
                'fix_suggestion' => null,
            ],
            [
                'tool' => 'npm_audit',
                'check_id' => 'express',
                'file_path' => 'package.json',
                'line_start' => null,
                'severity' => 'high',
                'message' => 'Vulnerable dependency: express — Open redirect vulnerability in Express.js < 4.19.2.',
                'owasp_category' => 'A06:2025',
                'owasp_label' => 'Vulnerable Components',
                'fix_suggestion' => null,
            ],
            [
                'tool' => 'semgrep',
                'check_id' => 'javascript.lang.security.audit.prototype-pollution',
                'file_path' => 'lib/utils.ts',
                'line_start' => 134,
                'severity' => 'high',
                'message' => 'Prototype pollution via Object.assign with user-controlled keys. Attacker can modify Object prototype.',
                'owasp_category' => 'A03:2025',
                'owasp_label' => 'Injection',
                'fix_suggestion' => null,
            ],

            // === MEDIUM ===
            [
                'tool' => 'eslint',
                'check_id' => 'no-eval',
                'file_path' => 'frontend/src/app/score-board/score-board.component.ts',
                'line_start' => 23,
                'severity' => 'medium',
                'message' => 'eval() with user input detected. This enables arbitrary code execution.',
                'owasp_category' => 'A03:2025',
                'owasp_label' => 'Injection',
                'fix_suggestion' => null,
            ],
            [
                'tool' => 'semgrep',
                'check_id' => 'javascript.express.security.audit.helmet-disable-xss-protection',
                'file_path' => 'server.ts',
                'line_start' => 32,
                'severity' => 'medium',
                'message' => 'X-XSS-Protection header is disabled via Helmet configuration. Re-enable or configure a Content Security Policy.',
                'owasp_category' => 'A05:2025',
                'owasp_label' => 'Security Misconfiguration',
                'fix_suggestion' => null,
            ],
            [
                'tool' => 'npm_audit',
                'check_id' => 'lodash',
                'file_path' => 'package.json',
                'line_start' => null,
                'severity' => 'medium',
                'message' => 'Vulnerable dependency: lodash — Prototype Pollution in lodash < 4.17.21.',
                'owasp_category' => 'A06:2025',
                'owasp_label' => 'Vulnerable Components',
                'fix_suggestion' => null,
            ],

            // === LOW ===
            [
                'tool' => 'semgrep',
                'check_id' => 'javascript.express.security.audit.express-cors-permissive',
                'file_path' => 'server.ts',
                'line_start' => 41,
                'severity' => 'low',
                'message' => 'CORS configured with wildcard origin (*). Restrict to specific trusted domains.',
                'owasp_category' => 'A05:2025',
                'owasp_label' => 'Security Misconfiguration',
                'fix_suggestion' => null,
            ],
            [
                'tool' => 'semgrep',
                'check_id' => 'javascript.lang.security.audit.weak-crypto',
                'file_path' => 'lib/insecurity.ts',
                'line_start' => 67,
                'severity' => 'low',
                'message' => 'MD5 used for password hashing. Use bcrypt, argon2 or scrypt instead.',
                'owasp_category' => 'A02:2025',
                'owasp_label' => 'Cryptographic Failures',
                'fix_suggestion' => null,
            ],
            [
                'tool' => 'eslint',
                'check_id' => 'no-console',
                'file_path' => 'routes/order.ts',
                'line_start' => 12,
                'severity' => 'low',
                'message' => 'console.log() with potentially sensitive data (order details) found in production code.',
                'owasp_category' => 'A09:2025',
                'owasp_label' => 'Security Logging Failures',
                'fix_suggestion' => null,
            ],
        ];

        foreach ($vulnerabilities as $v) {
            Vulnerability::create(array_merge($v, ['scan_id' => $scan->id]));
        }

        $this->command->info("✅ Demo scan créé ! ID: {$scan->id} — Accès: /scan/{$scan->id}/dashboard");
    }
}
