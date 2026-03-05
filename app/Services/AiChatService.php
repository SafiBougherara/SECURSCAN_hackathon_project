<?php

namespace App\Services;

use App\Models\Vulnerability;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiChatService
{
    private string $apiKey;
    private string $model;

    public function __construct()
    {
        $this->apiKey = config('services.gemini.key');
        // Using flash for faster interactive responses
        $this->model = 'gemini-2.5-flash';
    }

    /**
     * Send a conversation history to Gemini and get a response
     */
    public function sendMessage(array $history, ?Vulnerability $vuln = null): string
    {
        if (empty($this->apiKey)) {
            return "⚠️ Erreur : La clé API Gemini n'est pas configurée dans le fichier `.env` (`GEMINI_API_KEY`).";
        }

        // Prepare the internal history format for Gemini API
        $contents = [];

        // System instruction proxy: Since some models don't support root system_instructions well,
        $systemPrompt = "Tu es un expert en cybersécurité intégré à l'outil SecureScan.
Ton rôle est d'expliquer les failles de sécurité de manière très détaillée, structurée et pédagogique à un développeur.
Tes réponses DOIVENT être formatées en Markdown et suivre **strictement** cette structure (ne génère pas le titre principal de la faille, va direct au but) :
1. **Description détaillée** : Qu'est-ce que cette vulnérabilité exactement ? Comment fonctionne-t-elle ?
2. **Risque concret** : Que risque réellement l'application si un attaquant exploite ça ?
3. **Le correctif (Comment réparer)** : Explique l'approche générale pour corriger ce type de faille (exemples génériques bienvenus).

RÈGLE ABSOLUE : Dès ton tout premier message contenant le contexte d'une faille, tu DOIS INVARIABLEMENT et DIRECTEMENT fournir l'explication complète avec les 3 points ci-dessus. NE demande JAMAIS l'avis de l'utilisateur ou ne dis pas \"Analysons ensemble\" en attendant qu'il réponde. Donne la réponse détaillée IMMÉDIATEMENT. Ne sois pas laconique. Sois un vrai professeur de hacking éthique.";

        if ($vuln) {
            $systemPrompt .= "\n\nContexte Actuel: "
                . "Un développeur te demande d'analyser cette vulnérabilité précise trouvée dans le code :\n"
                . "- Outil d'analyse : {$vuln->tool}\n"
                . "- Sévérité : {$vuln->severity}\n"
                . "- Fichier ciblé : {$vuln->file_path}" . ($vuln->line_start ? ":{$vuln->line_start}" : "") . "\n"
                . "- Message original de l'outil : {$vuln->message}\n";

            if ($vuln->code_snippet) {
                $systemPrompt .= "- Snippet de code vulnérable :\n```\n{$vuln->code_snippet}\n```\n";
            }
        }

        // Reconstruct the history
        foreach ($history as $index => $msg) {
            $role = $msg['role'] === 'user' ? 'user' : 'model';
            $text = $msg['text'];

            // Inject the system prompt transparently into the very first user message array
            if ($index === 0 && $role === 'user') {
                $text = $systemPrompt . "\n\nQuestion de l'utilisateur : " . $text;
            }

            $contents[] = [
                'role' => $role,
                'parts' => [['text' => $text]]
            ];
        }

        try {
            $response = Http::timeout(20)
                ->withoutVerifying()
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post("https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent?key={$this->apiKey}", [
                    'contents' => $contents,
                    'generationConfig' => [
                        'temperature' => 0.4,
                        'maxOutputTokens' => 4096,
                    ]
                ]);

            if ($response->successful()) {
                $data = $response->json();
                $reply = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';

                return trim($reply) ?: "Désolé, je n'ai pas pu générer une réponse valide.";
            }

            if ($response->status() === 429) {
                return "⚠️ Erreur 429: Le quota gratuit de l'API Gemini est atteint. Merci de patienter une minute avant de poser une autre question.";
            }

            Log::error('AiChatService API Error', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return "Une erreur de communication avec l'IA est survenue (Code: {$response->status()}).";

        } catch (\Throwable $e) {
            Log::error('AiChatService Exception', ['error' => $e->getMessage()]);
            return "Erreur d'exécution: Impossible de contacter le serveur d'intelligence artificielle.";
        }
    }
}
