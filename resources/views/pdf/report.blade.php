<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>SecureScan Security Report — {{ $scan->repo_name ?? $scan->id }}</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            color: #1a1a2e;
            background: #fff;
        }

        .header {
            background: #0f3d1a;
            color: #fff;
            padding: 24px 32px;
            margin-bottom: 24px;
        }

        .header h1 {
            font-size: 20px;
            font-weight: bold;
            letter-spacing: 0.5px;
        }

        .header .meta {
            font-size: 9px;
            color: #a3e6b0;
            margin-top: 4px;
        }

        .section {
            margin: 0 24px 18px 24px;
        }

        .section-title {
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: #16a34a;
            border-bottom: 1px solid #d1fae5;
            padding-bottom: 4px;
            margin-bottom: 10px;
        }

        /* Score badge */
        .score-box {
            display: inline-block;
            border: 2px solid;
            border-radius: 8px;
            padding: 6px 16px;
            text-align: center;
        }

        .score-num {
            font-size: 28px;
            font-weight: bold;
        }

        .score-label {
            font-size: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Stats table */
        .stats-table {
            width: 100%;
            border-collapse: collapse;
        }

        .stats-table th,
        .stats-table td {
            border: 1px solid #e5e7eb;
            padding: 5px 8px;
            text-align: left;
            font-size: 9px;
        }

        .stats-table th {
            background: #f0fdf4;
            font-weight: bold;
            color: #166534;
        }

        .stats-table tr:nth-child(even) td {
            background: #f9fafb;
        }

        /* Severity colors */
        .sev-critical {
            color: #dc2626;
            font-weight: bold;
        }

        .sev-high {
            color: #ea580c;
            font-weight: bold;
        }

        .sev-medium {
            color: #ca8a04;
            font-weight: bold;
        }

        .sev-low {
            color: #2563eb;
            font-weight: bold;
        }

        .sev-info {
            color: #6b7280;
        }

        /* Vuln card */
        .vuln-card {
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            margin-bottom: 10px;
            page-break-inside: avoid;
        }

        .vuln-card-header {
            padding: 6px 10px;
            background: #f9fafb;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .vuln-card-body {
            padding: 8px 10px;
        }

        .badge {
            display: inline-block;
            border-radius: 4px;
            padding: 1px 6px;
            font-size: 8px;
            font-weight: bold;
            margin-right: 4px;
        }

        .badge-owasp {
            background: #ede9fe;
            color: #7c3aed;
        }

        .badge-tool {
            background: #f3f4f6;
            color: #374151;
            border: 1px solid #e5e7eb;
        }

        .fix-box {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-radius: 4px;
            padding: 6px 8px;
            margin-top: 6px;
            font-size: 8.5px;
            white-space: pre-wrap;
            word-break: break-word;
            line-height: 1.5;
        }

        .footer {
            text-align: center;
            font-size: 8px;
            color: #9ca3af;
            margin: 24px;
            padding-top: 12px;
            border-top: 1px solid #e5e7eb;
        }

        .pill-row {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            margin-bottom: 12px;
        }

        .pill {
            border-radius: 20px;
            padding: 4px 12px;
            font-size: 9px;
            font-weight: bold;
            text-align: center;
        }
    </style>
</head>

<body>

    {{-- ===== HEADER ===== --}}
    <div class="header">
        <h1>🔒 SecureScan Security Report</h1>
        <div class="meta">
            Repository: {{ $scan->repo_url }}<br>
            Generated: {{ now()->format('Y-m-d H:i') }} &nbsp;|&nbsp; Scan ID: #{{ $scan->id }}
        </div>
    </div>

    {{-- ===== SCORE + SUMMARY ===== --}}
    <div class="section">
        <div class="section-title">Summary</div>
        <table style="width:100%">
            <tr>
                <td style="width:120px; vertical-align:top;">
                    @php
                        $scoreColor = $scan->score >= 70 ? '#16a34a' : ($scan->score >= 40 ? '#ca8a04' : '#dc2626');
                    @endphp
                    <div class="score-box" style="border-color:{{ $scoreColor }}; color:{{ $scoreColor }}">
                        <div class="score-num">{{ $scan->score ?? 'N/A' }}</div>
                        <div class="score-label" style="color: {{ $scoreColor }}">/ 100 Security Score</div>
                    </div>
                </td>
                <td style="padding-left:20px; vertical-align:top;">
                    <table class="stats-table">
                        <thead>
                            <tr>
                                <th>Severity</th>
                                <th>Count</th>
                                <th>% of Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $total = $vulnerabilities->count(); @endphp
                            @foreach(['critical' => ['🔴', '#dc2626'], 'high' => ['🟠', '#ea580c'], 'medium' => ['🟡', '#ca8a04'], 'low' => ['🔵', '#2563eb'], 'info' => ['⚪', '#6b7280']] as $sev => [$emoji, $color])
                                @php $cnt = $bySeverity[$sev] ?? 0; @endphp
                                @if($cnt > 0)
                                    <tr>
                                        <td style="color:{{ $color }}; font-weight:bold;">{{ $emoji }} {{ ucfirst($sev) }}</td>
                                        <td style="font-weight:bold;">{{ $cnt }}</td>
                                        <td>{{ $total > 0 ? round($cnt / $total * 100) : 0 }}%</td>
                                    </tr>
                                @endif
                            @endforeach
                            <tr>
                                <td style="font-weight:bold;background:#f0fdf4">Total</td>
                                <td style="font-weight:bold;background:#f0fdf4">{{ $total }}</td>
                                <td style="background:#f0fdf4">100%</td>
                            </tr>
                        </tbody>
                    </table>
                </td>
            </tr>
        </table>
    </div>

    {{-- ===== OWASP BREAKDOWN ===== --}}
    @if($byOwasp->count() > 0)
        <div class="section">
            <div class="section-title">OWASP Top 10 Breakdown</div>
            <table class="stats-table">
                <thead>
                    <tr>
                        <th>OWASP Category</th>
                        <th>Count</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($byOwasp as $cat => $cnt)
                        <tr>
                            <td>{{ $cat }}</td>
                            <td>{{ $cnt }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    {{-- ===== VULNERABILITY DETAILS ===== --}}
    <div class="section">
        <div class="section-title">Vulnerability Details ({{ $total }})</div>

        @forelse($vulnerabilities as $vuln)
            @php
                $sevColors = ['critical' => '#dc2626', 'high' => '#ea580c', 'medium' => '#ca8a04', 'low' => '#2563eb', 'info' => '#6b7280'];
                $sevEmojis = ['critical' => '🔴', 'high' => '🟠', 'medium' => '🟡', 'low' => '🔵', 'info' => '⚪'];
                $color = $sevColors[$vuln->severity] ?? '#6b7280';
                $emoji = $sevEmojis[$vuln->severity] ?? '⚪';
            @endphp
            <div class="vuln-card" style="border-left: 4px solid {{ $color }};">
                <div class="vuln-card-header">
                    <span style="color:{{ $color }}; font-size:9px; font-weight:bold;">{{ $emoji }}
                        {{ strtoupper($vuln->severity) }}</span>
                    @if($vuln->owasp_category)
                        <span class="badge badge-owasp">{{ $vuln->owasp_category }}</span>
                    @endif
                    <span class="badge badge-tool">{{ $vuln->tool }}</span>
                    @if($vuln->file_path)
                        <span style="font-size:8px; color:#6b7280; font-family:monospace;">
                            📄 {{ $vuln->file_path }}{{ $vuln->line_start ? ':' . $vuln->line_start : '' }}
                        </span>
                    @endif
                </div>
                <div class="vuln-card-body">
                    <p style="font-size:9px; color:#374151; margin-bottom:4px;">{{ $vuln->message }}</p>
                    @if($vuln->owasp_label)
                        <p style="font-size:8px; color:#7c3aed; margin-bottom:4px;">{{ $vuln->owasp_label }}</p>
                    @endif

                    {{-- Code snippet --}}
                    @if($vuln->code_snippet)
                        <p style="font-size:8px; color:#92400e; font-weight:bold; margin-top:6px; margin-bottom:2px;">🔍
                            Vulnerable code:</p>
                        <div class="fix-box"
                            style="background:#fffbeb; border-color:#fde68a; color:#78350f; font-family:monospace;">
                            {{ $vuln->code_snippet }}</div>
                    @endif

                    {{-- AI Fix --}}
                    @if($vuln->ai_fix)
                        <p style="font-size:8px; color:#166534; font-weight:bold; margin-top:6px; margin-bottom:2px;">✨ AI Fix
                            Suggestion:</p>
                        <div class="fix-box">{{ $vuln->ai_fix }}</div>
                    @elseif($vuln->fix_suggestion)
                        <p style="font-size:8px; color:#166534; font-weight:bold; margin-top:6px; margin-bottom:2px;">💡 Fix
                            Suggestion:</p>
                        <div class="fix-box">{{ $vuln->fix_suggestion }}</div>
                    @endif
                </div>
            </div>
        @empty
            <p style="color:#6b7280; font-style:italic;">No vulnerabilities detected. 🎉</p>
        @endforelse
    </div>

    {{-- ===== FOOTER ===== --}}
    <div class="footer">
        SecureScan — Powered by Semgrep · ESLint · TruffleHog · Bandit · OWASP Top 10 2025<br>
        Report generated on {{ now()->format('Y-m-d H:i:s') }}
    </div>

</body>

</html>