<?php

namespace Tests\Feature;

use App\Models\Scan;
use App\Models\Vulnerability;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_redirects_to_loading_if_scan_is_pending(): void
    {
        $scan = Scan::create([
            'repo_url' => 'https://github.com/test/repo',
            'status' => 'pending',
        ]);

        $response = $this->get("/scan/{$scan->id}/dashboard");
        $response->assertRedirect("/scan/{$scan->id}/loading");
    }

    public function test_dashboard_shows_results_when_scan_is_done(): void
    {
        $scan = Scan::create([
            'repo_url' => 'https://github.com/test/repo',
            'repo_name' => 'test/repo',
            'status' => 'done',
            'score' => 72,
        ]);

        // Create a sample vulnerability linked to this scan
        Vulnerability::create([
            'scan_id' => $scan->id,
            'tool' => 'semgrep',
            'severity' => 'high',
            'message' => 'Test vulnerability',
            'owasp_category' => 'A03:2025',
            'owasp_label' => 'Injection',
        ]);

        $response = $this->get("/scan/{$scan->id}/dashboard");
        $response->assertStatus(200);
        $response->assertSee('test/repo');
        $response->assertSee('72');
    }

    public function test_dashboard_shows_failed_view_if_scan_failed(): void
    {
        $scan = Scan::create([
            'repo_url' => 'https://github.com/test/repo',
            'status' => 'failed',
        ]);

        $response = $this->get("/scan/{$scan->id}/dashboard");
        $response->assertStatus(200);
        // Should render the scan-failed view, not redirect
        $response->assertViewIs('scan-failed');
    }
}
