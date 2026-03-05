<?php

namespace App\Http\Controllers;

use App\Models\Vulnerability;
use App\Services\AiChatService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ChatController extends Controller
{
    /**
     * Handle incoming chat messages and return AI responses
     */
    public function message(Request $request, AiChatService $chatService): JsonResponse
    {
        $request->validate([
            'message' => 'required|string',
            'history' => 'array',
            'history.*.role' => 'required|in:user,model',
            'history.*.text' => 'required|string',
            'vulnerability_id' => 'nullable|exists:vulnerabilities,id'
        ]);

        $incomingMsg = $request->input('message');
        $history = $request->input('history', []);

        // Append new user message to history payload
        $history[] = [
            'role' => 'user',
            'text' => $incomingMsg
        ];

        // Fetch context if provided
        $vuln = null;
        if ($request->filled('vulnerability_id')) {
            $vuln = Vulnerability::find($request->input('vulnerability_id'));
        }

        // If it's the first message and we have a cached explanation, return it directly
        if ($vuln && count($history) === 1 && !empty($vuln->chat_explanation)) {
            return response()->json([
                'success' => true,
                'reply' => $vuln->chat_explanation
            ]);
        }

        // Call Gemini
        $reply = $chatService->sendMessage($history, $vuln);

        // Cache the explanation if it's the first message and not an API error
        if ($vuln && count($history) === 1 && !str_contains($reply, '⚠️') && !str_contains($reply, 'Erreur')) {
            $vuln->update(['chat_explanation' => $reply]);
        }

        return response()->json([
            'success' => true,
            'reply' => $reply
        ]);
    }
}
