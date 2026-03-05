<?php

namespace Tests\Feature;

use App\Jobs\RunSecurityScanJob;
use App\Models\Scan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ScanControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_returns_200(): void
    {
        $response = $this->get('/');
        $response->assertStatus(200);
    }

    public function test_scan_store_rejects_invalid_url(): void
    {
        $response = $this->post('/scan', ['repo_url' => 'not-a-url']);
        $response->assertSessionHasErrors('repo_url');
    }

    public function test_scan_store_rejects_non_github_url(): void
    {
        $response = $this->post('/scan', ['repo_url' => 'https://gitlab.com/user/repo']);
        $response->assertSessionHasErrors('repo_url');
    }

    public function test_scan_store_accepts_github_url(): void
    {
        Queue::fake();

        $response = $this->post('/scan', [
            'repo_url' => 'https://github.com/laravel/laravel',
        ]);

        // Should redirect to the loading page (not error)
        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        // A scan record should have been created
        $this->assertDatabaseHas('scans', [
            'repo_url' => 'https://github.com/laravel/laravel',
            'status' => 'pending',
        ]);

        // The job should have been dispatched
        Queue::assertPushed(RunSecurityScanJob::class);
    }

    public function test_scan_loading_page_returns_200(): void
    {
        $scan = Scan::create([
            'repo_url' => 'https://github.com/test/repo',
            'status' => 'pending',
        ]);

        $response = $this->get("/scan/{$scan->id}/loading");
        $response->assertStatus(200);
    }

    public function test_scan_status_returns_json(): void
    {
        $scan = Scan::create([
            'repo_url' => 'https://github.com/test/repo',
            'status' => 'pending',
        ]);

        $response = $this->get("/scan/{$scan->id}/status");
        $response->assertStatus(200);
        $response->assertJson(['status' => 'pending']);
    }
}
