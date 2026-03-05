@extends('layouts.app')

@section('title', __('ui.settings_title'))

@section('content')
    <div class="max-w-2xl mx-auto px-4 py-12">

        {{-- Header --}}
        <div class="mb-8">
            <h1 class="text-2xl font-bold gradient-text">{{ __('ui.settings_title') }}</h1>
            <p class="text-gray-400 text-sm mt-1">{{ __('ui.settings_sub') }}</p>
        </div>

        {{-- Flash messages --}}
        @if(session('success'))
            <div class="mb-6 px-4 py-3 rounded-lg bg-green-500/10 border border-green-500/30 text-green-400 text-sm">
                {{ session('success') }}
            </div>
        @endif
        @if($errors->any())
            <div class="mb-6 px-4 py-3 rounded-lg bg-red-500/10 border border-red-500/30 text-red-400 text-sm">
                @foreach($errors->all() as $error) {{ $error }}<br> @endforeach
            </div>
        @endif

        {{-- GitHub Token Card --}}
        <div class="glass-card rounded-2xl p-6">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-9 h-9 rounded-lg bg-gray-800 flex items-center justify-center text-lg">🔑</div>
                <div>
                    <h2 class="font-semibold text-white text-sm">{{ __('ui.settings_token_title') }}</h2>
                    <p class="text-gray-500 text-xs">{{ __('ui.settings_token_sub') }}</p>
                </div>
            </div>

            <form method="POST" action="{{ route('settings.update') }}"
                x-data="{ show: false, hasToken: {{ $user->github_token ? 'true' : 'false' }} }">
                @csrf

                <div class="space-y-4">
                    {{-- Current status --}}
                    @if($user->github_token)
                        <div
                            class="flex items-center gap-2 text-xs text-green-400 bg-green-500/10 px-3 py-2 rounded-lg border border-green-500/20">
                            <span>✅</span>
                            <span>{{ __('ui.settings_token_saved') }}</span>
                        </div>
                    @else
                        <div
                            class="flex items-center gap-2 text-xs text-yellow-400 bg-yellow-500/10 px-3 py-2 rounded-lg border border-yellow-500/20">
                            <span>⚠️</span>
                            <span>{{ __('ui.settings_token_missing') }}</span>
                        </div>
                    @endif

                    {{-- Token input --}}
                    <div>
                        <label class="block text-xs text-gray-400 mb-1.5" for="github_token">
                            {{ __('ui.settings_token_label') }}
                            <a href="https://github.com/settings/tokens/new?scopes=repo&description=SecureScan"
                                target="_blank"
                                class="ml-2 text-green-400 hover:underline">{{ __('ui.settings_token_github') }}</a>
                        </label>
                        <div class="relative">
                            <input :type="show ? 'text' : 'password'" id="github_token" name="github_token"
                                placeholder="{{ $user->github_token ? '••••••••••••••••••••••••' : 'ghp_xxxxxxxxxxxxxxxxxxxx' }}"
                                autocomplete="off"
                                class="w-full bg-surface-800 border border-white/10 rounded-lg px-3 py-2.5 text-sm text-white placeholder-gray-600 focus:outline-none focus:border-green-500/40 pr-10 font-mono">
                            <button type="button" @click="show = !show"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-300 text-xs">
                                <span x-text="show ? '🙈' : '👁️'"></span>
                            </button>
                        </div>
                        <p class="text-xs text-gray-600 mt-1">{{ __('ui.settings_token_scopes') }} <code
                                class="text-green-400">repo</code></p>
                    </div>

                    {{-- Actions --}}
                    <div class="flex items-center gap-3 pt-2">
                        <button type="submit" class="btn-primary px-5 py-2 rounded-lg text-sm font-semibold text-white">
                            {{ __('ui.settings_save') }}
                        </button>
                        @if($user->github_token)
                            <button type="submit" name="clear_token" value="1"
                                onclick="return confirm('{{ __('ui.settings_clear_confirm') }}')"
                                class="px-4 py-2 rounded-lg border border-red-500/30 text-red-400 hover:bg-red-500/10 text-sm transition-all">
                                {{ __('ui.settings_clear') }}
                            </button>
                        @endif
                    </div>
                </div>
            </form>
        </div>

        {{-- Gemini AI Status Card --}}
        <div class="glass-card rounded-2xl p-6 mt-4" x-data="{
                    status: 'loading',
                    label: '...',
                    model: '',
                    message: '',
                    async fetchStatus() {
                        try {
                            const r = await fetch('{{ route('settings.gemini-status') }}', { headers: { 'Accept': 'application/json' } });
                            const d = await r.json();
                            this.status  = d.status;
                            this.label   = d.label;
                            this.model   = d.model;
                            this.message = d.message;
                        } catch(e) {
                            this.status = 'error';
                            this.label  = 'Unreachable';
                        }
                    }
                 }" x-init="fetchStatus()">

            <div class="flex items-center gap-3 mb-4">
                <div class="w-9 h-9 rounded-lg bg-gray-800 flex items-center justify-center text-lg">🤖</div>
                <div>
                    <h2 class="font-semibold text-white text-sm">{{ __('ui.settings_ai_title') }}</h2>
                    <p class="text-gray-500 text-xs">{{ __('ui.settings_ai_sub') }}</p>
                </div>
            </div>

            <div class="flex items-center gap-4">
                {{-- Status badge --}}
                <div class="flex items-center gap-2">
                    {{-- Spinner while loading --}}
                    <template x-if="status === 'loading'">
                        <span class="flex items-center gap-2 text-xs text-gray-500">
                            <svg class="animate-spin w-3.5 h-3.5" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                                </circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                            </svg>
                            {{ __('ui.settings_ai_checking') }}
                        </span>
                    </template>

                    <template x-if="status === 'ok'">
                        <span
                            class="flex items-center gap-2 text-xs px-3 py-1.5 rounded-full bg-green-500/10 border border-green-500/20 text-green-400 font-medium">
                            <span>✅</span> <span x-text="label"></span>
                        </span>
                    </template>

                    <template x-if="status === 'quota'">
                        <span
                            class="flex items-center gap-2 text-xs px-3 py-1.5 rounded-full bg-yellow-500/10 border border-yellow-500/20 text-yellow-400 font-medium">
                            <span>⏳</span> <span x-text="label"></span>
                        </span>
                    </template>

                    <template x-if="status === 'invalid'">
                        <span
                            class="flex items-center gap-2 text-xs px-3 py-1.5 rounded-full bg-red-500/10 border border-red-500/20 text-red-400 font-medium">
                            <span>❌</span> <span x-text="label"></span>
                        </span>
                    </template>

                    <template x-if="status === 'unconfigured'">
                        <span
                            class="flex items-center gap-2 text-xs px-3 py-1.5 rounded-full bg-gray-500/10 border border-gray-500/20 text-gray-500 font-medium">
                            <span>⚙️</span> <span x-text="label"></span>
                        </span>
                    </template>

                    <template x-if="status === 'error'">
                        <span
                            class="flex items-center gap-2 text-xs px-3 py-1.5 rounded-full bg-red-500/10 border border-red-500/20 text-red-400 font-medium">
                            <span>🔌</span> <span x-text="label"></span>
                        </span>
                    </template>
                </div>

                {{-- Model + info --}}
                <div x-show="status !== 'loading'" class="flex-1">
                    <p class="text-xs text-gray-600 font-mono" x-text="model"></p>
                    <p class="text-xs text-gray-500 mt-0.5" x-text="message"></p>
                </div>

                {{-- Refresh button --}}
                <button @click="status='loading'; fetchStatus()" x-show="status !== 'loading'"
                    class="text-xs text-gray-600 hover:text-gray-400 transition-colors px-2 py-1 rounded border border-white/5 hover:border-white/10"
                    title="Refresh">
                    ↺
                </button>
            </div>
        </div>

        {{-- Account info (read-only) --}}
        <div class="glass-card rounded-2xl p-6 mt-4">
            <h2 class="font-semibold text-white text-sm mb-3">{{ __('ui.settings_account') }}</h2>
            <div class="space-y-2 text-sm">
                <div class="flex justify-between">
                    <span class="text-gray-500">{{ __('ui.settings_name') }}</span>
                    <span class="text-white">{{ auth()->user()->name }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">{{ __('ui.settings_email') }}</span>
                    <span class="text-white">{{ auth()->user()->email }}</span>
                </div>
                @if(auth()->user()->is_admin)
                    <div class="flex justify-between">
                        <span class="text-gray-500">{{ __('ui.settings_role') }}</span>
                        <span
                            class="px-2 py-0.5 rounded-full bg-green-500/20 border border-green-500/30 text-green-400 text-xs font-mono">Admin</span>
                    </div>
                @endif
            </div>
        </div>

    </div>
@endsection