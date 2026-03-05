<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

class UserSettingsController extends Controller
{
    /** GET /settings */
    public function show()
    {
        return view('settings', ['user' => Auth::user()]);
    }

    /** POST /settings */
    public function update(Request $request)
    {
        $request->validate([
            'github_token' => 'nullable|string|max:200',
        ]);

        $user = Auth::user();
        $token = $request->input('github_token');

        // Only update if non-empty or explicitly cleared
        if ($request->has('clear_token')) {
            $user->github_token = null;
        } elseif (!empty($token)) {
            $user->github_token = $token;
        }

        $user->save();

        return redirect()->route('settings')->with('success', '✅ Settings saved.');
    }

    /** GET /settings/gemini-status  → JSON */
    public function geminiStatus(): JsonResponse
    {
        $key = config('services.gemini.key', '');
        $model = 'gemini-2.5-flash';

        if (empty($key)) {
            return response()->json([
                'status' => 'unconfigured',
                'label' => 'Not configured',
                'model' => $model,
                'message' => 'GEMINI_API_KEY is not set in .env',
            ]);
        }

        try {
            $response = Http::timeout(10)
                ->withoutVerifying()
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post("https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$key}", [
                    'contents' => [['parts' => [['text' => 'OK']]]],
                    'generationConfig' => ['maxOutputTokens' => 2],
                ]);

            $http = $response->status();

            if ($http === 200) {
                return response()->json([
                    'status' => 'ok',
                    'label' => 'Active',
                    'model' => $model,
                    'message' => "Model {$model} is responding normally.",
                ]);
            }

            if ($http === 429) {
                return response()->json([
                    'status' => 'quota',
                    'label' => 'Quota exceeded',
                    'model' => $model,
                    'message' => 'Daily free-tier quota exhausted. Resets daily at midnight Pacific.',
                ]);
            }

            if (in_array($http, [400, 401, 403])) {
                return response()->json([
                    'status' => 'invalid',
                    'label' => 'Invalid key',
                    'model' => $model,
                    'message' => 'Check your GEMINI_API_KEY in .env (HTTP ' . $http . ').',
                ]);
            }

            return response()->json([
                'status' => 'error',
                'label' => 'Error ' . $http,
                'model' => $model,
                'message' => substr($response->body(), 0, 200),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'label' => 'Unreachable',
                'model' => $model,
                'message' => 'Could not connect to Gemini API: ' . $e->getMessage(),
            ]);
        }
    }
}
