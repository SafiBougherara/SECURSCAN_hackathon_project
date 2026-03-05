<?php

namespace App\Http\Controllers;

use App\Models\Scan;
use App\Models\Vulnerability;
use App\Services\AiFixService;
use App\Services\GitHubPRService;
use Illuminate\Http\Request;
use Throwable;

class PullRequestController extends Controller
{
    public function create(Request $request, Scan $scan)
    {
        $request->validate([
            'vulnerability_ids' => 'required|array|min:1',
            'vulnerability_ids.*' => 'integer|exists:vulnerabilities,id',
        ]);

        // Get selected vulnerabilities for this scan
        $vulnerabilities = Vulnerability::whereIn('id', $request->input('vulnerability_ids'))
            ->where('scan_id', $scan->id)
            ->get();

        if ($vulnerabilities->isEmpty()) {
            return response()->json(['success' => false, 'error' => 'No vulnerabilities found for the selected IDs.'], 422);
        }

        // Check a GitHub token is available (user token or .env fallback)
        $user = auth()->user();
        $hasToken = ($user && $user->github_token) || !empty(config('services.github.token'));
        if (!$hasToken) {
            return response()->json([
                'success' => false,
                'error' => 'No GitHub token configured. Go to ⚙️ Settings to add your Personal Access Token.',
            ], 422);
        }

        try {
            // Pre-generate AI fixes so the PR report includes them
            $aiService = app(AiFixService::class);
            $aiService->generateFixesForMany($vulnerabilities);
            $vulnerabilities = $vulnerabilities->fresh();

            $prService = GitHubPRService::forUser(auth()->user());

            // Pure API — no git clone, no threading issues
            $prUrl = $prService->createPullRequestViaApi(
                $scan->repo_url,
                $vulnerabilities->all()
            );

            return response()->json(['success' => true, 'pr_url' => $prUrl]);

        } catch (Throwable $e) {
            $token = config('services.github.token', '');
            $message = $token
                ? str_replace("https://{$token}@", 'https://[TOKEN]@', $e->getMessage())
                : $e->getMessage();

            return response()->json(['success' => false, 'error' => $message], 500);
        }
    }
}
