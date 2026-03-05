@extends('layouts.app')
@section('title', 'Scan Your Repository')

@section('content')
    <div class="min-h-screen flex flex-col items-center justify-center px-4 py-20 relative overflow-hidden"
        x-data="{ loading: false }">

        <!-- Matrix Rain Background -->
        <canvas id="matrix-canvas" class="absolute top-0 left-0 w-full h-full z-0 opacity-[0.18] pointer-events-none"></canvas>

        <!-- Main content container (above canvas) -->
        <div class="relative z-10 w-full max-w-7xl mx-auto flex flex-col items-center">

        <!-- Background glow effects -->
        <div class="absolute top-1/4 left-1/3 w-96 h-96 bg-green-500/10 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute bottom-1/4 right-1/3 w-80 h-80 bg-green-900/10 rounded-full blur-3xl pointer-events-none">
        </div>

        <!-- Hero Badge -->
        <div
            class="mb-6 flex items-center gap-2 px-4 py-2 rounded-full glass text-sm text-green-400 border border-green-500/20">
            <span class="w-2 h-2 rounded-full bg-green-400 animate-pulse"></span>
            {{ __('ui.home_badge') }}
        </div>

        <!-- Hero Title -->
        <h1 class="text-5xl sm:text-6xl font-extrabold text-center mb-4 animate-float">
            <span class="gradient-text">{{ __('ui.home_headline') }}</span><br>
            <span class="text-white">{{ __('ui.home_headline2') }}</span>
        </h1>
        <p class="text-gray-400 text-center text-lg max-w-xl mb-12">
            {{ __('ui.home_sub') }}
        </p>

        <!-- Form Card -->
        <div class="glass rounded-2xl p-8 w-full max-w-2xl shadow-2xl">
            <form action="{{ route('scan.store') }}" method="POST" @submit="loading = true" class="flex flex-col gap-4">
                @csrf
                <label class="text-sm text-gray-400 font-medium tracking-wide uppercase">{{ __('ui.home_label') }}</label>
                <div class="flex flex-col sm:flex-row gap-3">
                    <input id="repo_url" type="url" name="repo_url" value="{{ old('repo_url') }}"
                        placeholder="https://github.com/owner/repo" required
                        class="flex-1 bg-white/5 border border-white/10 rounded-xl px-5 py-4 text-white placeholder-gray-600 font-mono text-sm focus:outline-none focus:border-green-500/50 focus:ring-2 focus:ring-green-500/20 transition-all">
                    <button type="submit" id="submit-btn"
                        class="btn-primary rounded-xl px-8 py-4 font-semibold text-white whitespace-nowrap flex items-center gap-2 justify-center"
                        :disabled="loading">
                        <template x-if="!loading">
                            <span>🔍 {{ __('ui.home_scan_btn') }}</span>
                        </template>
                        <template x-if="loading">
                            <span class="flex items-center gap-2">
                                <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                        stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                </svg>
                                {{ __('ui.home_scanning') }}
                            </span>
                        </template>
                    </button>
                </div>

                @error('repo_url')
                    <p class="text-red-400 text-sm mt-1">⚠ {{ $message }}</p>
                @enderror
            </form>
        </div>

        <!-- Feature cards -->
        <div class="mt-16 grid grid-cols-2 sm:grid-cols-4 gap-4 max-w-3xl w-full">
            @foreach([
                        ['🔍', 'Semgrep', __('ui.feat_semgrep_desc')],
                        ['📦', 'npm audit', __('ui.feat_npm_desc')],
                        ['🔑', 'TruffleHog', __('ui.feat_trufflehog_desc')],
                        ['🐍', 'Bandit', __('ui.feat_bandit_desc')],
                    ] as [$icon, $name, $desc])
                                    <div class="glass-card rounded-xl p-4 text-center">
                <div class="text-2xl mb-2">{{ $icon }}</div>
                <div class="font-semibold text-sm text-white">{{ $name }}</div>
                                                        <div class="text-xs text-gray-500 mt-1">{{ $desc }}</div>
                                                    </div>
            @endforeach
                        </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const canvas = document.getElementById('matrix-canvas');
        if (!canvas) return;
        
        const ctx = canvas.getContext('2d');

        // Set canvas to full window
        const resizeCanvas = () => {
            canvas.width = window.innerWidth;
            canvas.height = window.innerHeight;
        };
        resizeCanvas();

        // Characters
        const chars = '01アイウエオカキクケコサシスセソタチツテトナニヌネノ';
        const fontSize = 14;
        let columns = Math.floor(canvas.width / fontSize);
        let drops = Array(columns).fill(1);

        window.addEventListener('resize', () => {
            resizeCanvas();
            columns = Math.floor(canvas.width / fontSize);
            drops = Array(columns).fill(1);
        });

        function draw() {
            // Semi-transparent black to create trailing effect
            ctx.fillStyle = 'rgba(10, 15, 30, 0.05)';
            ctx.fillRect(0, 0, canvas.width, canvas.height);

            ctx.font = `${fontSize}px monospace`;

            drops.forEach((y, i) => {
                const char = chars[Math.floor(Math.random() * chars.length)];
                
                // Head of the column is white, body is matrix green
                ctx.fillStyle = y === 1 ? '#ffffff' : '#22c55e'; // match text-green-500
                ctx.fillText(char, i * fontSize, y * fontSize);

                // Reset drop randomly to create stagger
                if (y * fontSize > canvas.height && Math.random() > 0.975) {
                    drops[i] = 0;
                }
                drops[i]++;
            });
        }

        setInterval(draw, 40); // ~25 FPS
    });
</script>
@endpush
