<?php

namespace App\Services;

use Symfony\Component\Process\Process;

class NpmAuditService
{
    public function run(string $path): array
    {
        if (!file_exists("{$path}/package.json"))
            return [];

        $process = new Process(['npm', 'audit', '--json']);
        $process->setWorkingDirectory($path);
        $process->setTimeout(90);
        $process->run();

        $output = json_decode($process->getOutput(), true);
        if (!isset($output['vulnerabilities']))
            return [];

        // Read package.json once to extract installed versions
        $pkg = json_decode(file_get_contents("{$path}/package.json") ?: '{}', true);
        $allDeps = array_merge(
            $pkg['dependencies'] ?? [],
            $pkg['devDependencies'] ?? [],
            $pkg['peerDependencies'] ?? [],
            $pkg['optionalDependencies'] ?? [],
        );

        $findings = [];
        foreach ($output['vulnerabilities'] as $name => $vuln) {
            // Build a representative code snippet from package.json
            $version = $allDeps[$name] ?? ($vuln['range'] ?? 'unknown');
            $depType = isset($pkg['dependencies'][$name]) ? 'dependencies' : (isset($pkg['devDependencies'][$name]) ? 'devDependencies' : 'dependencies');
            $snippet = "// package.json\n\"{$depType}\": {\n  \"{$name}\": \"{$version}\"\n}";

            // Add CVE references if available
            if (!empty($vuln['via'])) {
                $cves = [];
                foreach ((array) $vuln['via'] as $via) {
                    if (is_string($via))
                        $cves[] = $via;
                    elseif (isset($via['url']))
                        $cves[] = $via['url'];
                }
                if (!empty($cves)) {
                    $snippet .= "\n\n// CVE references:\n// " . implode("\n// ", array_slice($cves, 0, 3));
                }
            }

            $findings[] = [
                'tool' => 'npm_audit',
                'check_id' => $name,
                'file_path' => 'package.json',
                'line_start' => null,
                'severity' => $this->mapSeverity($vuln['severity'] ?? 'low'),
                'message' => "Vulnerable dependency: {$name} — " . ($vuln['title'] ?? 'no title'),
                'owasp_raw' => null,
                'code_snippet' => $snippet,
            ];
        }
        return $findings;
    }

    private function mapSeverity(string $s): string
    {
        return match ($s) {
            'critical' => 'critical',
            'high' => 'high',
            'moderate' => 'medium',
            default => 'low',
        };
    }
}
