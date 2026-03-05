# bonus.md — Chatbot IA contextuel (gratuit)

## LLM recommandé : Google Gemini Flash (via Google AI Studio)

**Pourquoi Gemini Flash ?**
- Gratuit sans carte bancaire, clé API en 2 minutes sur https://aistudio.google.com
- Quota free tier : ~15 req/min, 1 000 req/jour (Gemini 1.5 Flash)
- Contexte 1 million de tokens → tu peux envoyer TOUS les findings d'un scan en une seule requête
- Compatible format OpenAI (requêtes JSON similaires)

**Alternative si quota atteint :** OpenRouter (https://openrouter.ai) — accès à 70+ modèles gratuits
(DeepSeek, Llama, Mistral) avec le même format d'API, sans CB.

---

## Objectif

Un chatbot flottant sur le dashboard, **contextualisé avec les vulnérabilités du scan en cours**.
L'utilisateur peut demander :
- "Explique-moi la faille A05 détectée dans routes/api.js"
- "Comment corriger cette injection SQL ?"
- "Quel est le niveau de risque global de ce projet ?"

---

## Intégration Laravel (backend)

### 1. Ajouter la clé dans `.env`
```env
GEMINI_API_KEY=AIza...
GEMINI_MODEL=gemini-1.5-flash
```

### 2. Créer `app/Http/Controllers/ChatController.php`
```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\Scan;

class ChatController extends Controller
{
    public function ask(Request $request, Scan $scan)
    {
        $request->validate(['message' => 'required|string|max:1000']);

        // Construire le contexte à partir des vulnérabilités du scan
        $vulns = $scan->vulnerabilities()
            ->select('severity', 'owasp_category', 'owasp_label', 'file_path', 'line_start', 'message')
            ->orderByRaw("FIELD(severity, 'critical','high','medium','low')")
            ->limit(50) // limiter pour ne pas exploser le prompt
            ->get();

        $context = "Tu es un expert en cybersécurité. Voici les résultats d'analyse de sécurité du repo \"{$scan->repo_name}\" :\n\n";
        foreach ($vulns as $v) {
            $context .= "- [{$v->severity}] {$v->owasp_category} {$v->owasp_label} → {$v->file_path}:{$v->line_start} : {$v->message}\n";
        }
        $context .= "\nScore global : {$scan->score}/100\n";
        $context .= "\nRéponds en français, de manière concise et pratique.";

        // Appel Gemini API
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->post(
            "https://generativelanguage.googleapis.com/v1beta/models/" . env('GEMINI_MODEL') . ":generateContent?key=" . env('GEMINI_API_KEY'),
            [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $context . "\n\nQuestion de l'utilisateur : " . $request->message]
                        ]
                    ]
                ],
                'generationConfig' => [
                    'maxOutputTokens' => 512,
                    'temperature' => 0.3
                ]
            ]
        );

        $reply = $response->json('candidates.0.content.parts.0.text') ?? "Erreur : pas de réponse.";

        return response()->json(['reply' => $reply]);
    }
}
```

### 3. Ajouter la route dans `routes/web.php`
```php
Route::post('/scan/{scan}/chat', [ChatController::class, 'ask']);
```

---

## Frontend — Widget chatbot (Blade + Alpine.js)

Ajouter ce bloc en bas de `dashboard.blade.php` :

```html
<!-- Chatbot flottant -->
<div x-data="chatbot()" class="fixed bottom-6 right-6 z-50">

    <!-- Bouton toggle -->
    <button @click="open = !open"
        class="bg-indigo-600 hover:bg-indigo-700 text-white rounded-full w-14 h-14 flex items-center justify-center shadow-lg text-2xl">
        🤖
    </button>

    <!-- Fenêtre chat -->
    <div x-show="open" x-transition
        class="absolute bottom-16 right-0 w-96 bg-white rounded-xl shadow-2xl border border-gray-200 flex flex-col"
        style="height: 480px;">

        <!-- Header -->
        <div class="bg-indigo-600 text-white px-4 py-3 rounded-t-xl font-semibold text-sm">
            🛡️ Assistant Sécurité — {{ $scan->repo_name }}
        </div>

        <!-- Messages -->
        <div class="flex-1 overflow-y-auto p-4 space-y-3 text-sm" x-ref="messages">
            <template x-for="msg in messages" :key="msg.id">
                <div :class="msg.role === 'user' ? 'text-right' : 'text-left'">
                    <span :class="msg.role === 'user'
                        ? 'bg-indigo-100 text-indigo-900'
                        : 'bg-gray-100 text-gray-800'"
                        class="inline-block px-3 py-2 rounded-lg max-w-xs"
                        x-text="msg.text">
                    </span>
                </div>
            </template>
            <div x-show="loading" class="text-gray-400 text-xs text-center">⏳ Analyse en cours...</div>
        </div>

        <!-- Input -->
        <div class="border-t p-3 flex gap-2">
            <input x-model="input"
                @keydown.enter="send()"
                type="text"
                placeholder="Ex: Comment corriger l'injection SQL ?"
                class="flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400" />
            <button @click="send()"
                :disabled="loading"
                class="bg-indigo-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-indigo-700 disabled:opacity-50">
                →
            </button>
        </div>
    </div>
</div>

<script>
function chatbot() {
    return {
        open: false,
        input: '',
        loading: false,
        messages: [
            { id: 0, role: 'bot', text: 'Bonjour ! Je connais toutes les failles détectées dans ce scan. Que voulez-vous savoir ?' }
        ],
        async send() {
            if (!this.input.trim() || this.loading) return;
            const userMsg = this.input.trim();
            this.messages.push({ id: Date.now(), role: 'user', text: userMsg });
            this.input = '';
            this.loading = true;
            this.$nextTick(() => {
                this.$refs.messages.scrollTop = this.$refs.messages.scrollHeight;
            });
            try {
                const res = await fetch(`/scan/{{ $scan->id }}/chat`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ message: userMsg })
                });
                const data = await res.json();
                this.messages.push({ id: Date.now() + 1, role: 'bot', text: data.reply });
            } catch (e) {
                this.messages.push({ id: Date.now() + 1, role: 'bot', text: 'Erreur de connexion.' });
            }
            this.loading = false;
            this.$nextTick(() => {
                this.$refs.messages.scrollTop = this.$refs.messages.scrollHeight;
            });
        }
    }
}
</script>
```

---

## Pour obtenir la clé Gemini (2 min)

1. Aller sur https://aistudio.google.com
2. Se connecter avec un compte Google
3. Cliquer **"Get API key"** → **"Create API key"**
4. Copier la clé dans `.env` → `GEMINI_API_KEY=AIza...`
5. ✅ Aucune CB requise

---

## Priorité d'implémentation

Ne commencer ce bonus qu'une fois ces éléments validés :
- ✅ Dashboard avec cartes fonctionnel
- ✅ Scan Semgrep qui tourne
- ✅ Bouton Pull Request opérationnel

Estimation : **2-3h** pour intégrer le chatbot complet si tout le reste tourne.
