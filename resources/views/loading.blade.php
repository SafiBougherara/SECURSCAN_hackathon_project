@extends('layouts.app')
@section('title', 'Scanning…')

@push('styles')
<style>
    @keyframes run-across {
        0% { transform: translateX(-150px); }
        100% { transform: translateX(100vw); }
    }
    .animate-run-across {
        animation: run-across 15s linear infinite;
    }
</style>
@endpush


@section('content')
<div class="min-h-screen flex flex-col items-center justify-center px-4 py-20 relative overflow-hidden"
     x-data="poller('{{ route('scan.status', $scan) }}')"
     x-init="startPolling()">

    <!-- Matrix Rain Background -->
    <canvas id="matrix-canvas" class="absolute top-0 left-0 w-full h-full z-0 opacity-[0.15] pointer-events-none"></canvas>

    <!-- Main content container (above canvas) -->
    <div class="relative z-10 w-full max-w-7xl mx-auto flex flex-col items-center">

    <!-- Animated scanner visual -->
    <div class="relative mb-12">
        <div class="w-32 h-32 rounded-full bg-gradient-to-br from-green-500/20 to-green-900/20 border-2 border-green-500/30 flex items-center justify-center animate-glow">
            <div class="w-20 h-20 rounded-full bg-gradient-to-br from-green-500/30 to-green-900/30 flex items-center justify-center overflow-hidden animate-pulse">
                <img src="{{ asset('asset/robot_LD.png') }}" alt="Robot" class="w-16 h-16 object-contain">
            </div>
        </div>
        <!-- Orbiting dot -->
        <div class="absolute inset-0 animate-spin" style="animation-duration:3s">
            <div class="w-4 h-4 rounded-full bg-green-400 absolute -top-2 left-1/2 -translate-x-1/2 shadow-lg shadow-green-400/50"></div>
        </div>
    </div>

    <h1 class="text-3xl font-bold text-white mb-2">{{ __('ui.loading_title') }}</h1>
    <p class="text-gray-400 mb-2 font-mono text-sm">{{ $scan->repo_url }}</p>
    <p class="text-gray-500 text-sm mb-10">{{ __('ui.loading_sub') }}</p>

    <!-- Progress steps -->
    <div class="glass rounded-2xl p-6 max-w-md w-full space-y-4">
        @foreach([
            ['🔀', __('ui.loading_step1')],
            ['🔍', __('ui.loading_step2')],
            ['📦', __('ui.loading_step3')],
            ['🔑', __('ui.loading_step4')],
            ['🗂️', __('ui.loading_step5')],
            ['📊', __('ui.loading_step6')],
        ] as $i => [$icon, $label])
        <div class="flex items-center gap-3 text-sm"
             x-bind:class="step > {{ $i }} ? 'text-green-400' : step === {{ $i }} ? 'text-green-400' : 'text-gray-600'">
            <span class="text-base">
                <template x-if="step > {{ $i }}">✅</template>
                <template x-if="step === {{ $i }}">
                    <svg class="animate-spin w-4 h-4 inline" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                </template>
                <template x-if="step < {{ $i }}">⋯</template>
            </span>
            <span>{{ $label }}</span>
        </div>
        @endforeach
    </div>

    <!-- Status badge -->
    <div class="mt-8 flex items-center gap-2">
        <div class="w-2 h-2 rounded-full bg-green-400 animate-pulse"></div>
        <span class="text-green-400 text-sm font-medium" x-text="statusText"></span>
    </div>
    </div>

    <!-- Running Robot Knight -->
    <div class="fixed bottom-0 left-0 w-full h-32 pointer-events-none z-50 overflow-hidden"
         x-show="step < 6"
         x-transition:enter="transition ease-out duration-500"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-500"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">
        <img src="{{ asset('asset/walking robot knight.gif') }}" 
             alt="Running Robot" 
             class="absolute bottom-0 h-24 animate-run-across filter drop-shadow-[0_0_15px_rgba(34,197,94,0.5)]">
    </div>
</div>
@endsection

@push('scripts')
<script>
function poller(statusUrl) {
    // ... (rest of poller function)
    return {
        step: 0,
        statusText: 'Initializing scan…',
        intervalId: null,

        startPolling() {
            this.simulateSteps();
            this.intervalId = setInterval(() => this.checkStatus(statusUrl), 3000);
        },

        simulateSteps() {
            const steps = [0, 1, 2, 3, 4, 5];
            steps.forEach((s, i) => {
                setTimeout(() => { if (this.step < s + 1) this.step = s; }, i * 12000);
            });
        },

        async checkStatus(url) {
            try {
                const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                const data = await res.json();

                if (data.status === 'done') {
                    this.statusText = 'Scan complete! Redirecting…';
                    this.step = 6;
                    clearInterval(this.intervalId);
                    setTimeout(() => window.location.href = data.redirect, 800);
                } else if (data.status === 'failed') {
                    this.statusText = '❌ Scan failed. Redirecting…';
                    clearInterval(this.intervalId);
                    setTimeout(() => window.location.href = data.redirect, 1500);
                } else if (data.status === 'running') {
                    this.statusText = 'Scan in progress…';
                }
            } catch (e) {
                console.error('Polling error:', e);
            }
        }
    }
}

document.addEventListener('DOMContentLoaded', () => {
    const canvas = document.getElementById('matrix-canvas');
    if (!canvas) return;
    
    const ctx = canvas.getContext('2d');

    const resizeCanvas = () => {
        canvas.width = window.innerWidth;
        canvas.height = window.innerHeight;
    };
    resizeCanvas();

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
        ctx.fillStyle = 'rgba(10, 15, 30, 0.05)';
        ctx.fillRect(0, 0, canvas.width, canvas.height);

        ctx.font = `${fontSize}px monospace`;

        drops.forEach((y, i) => {
            const char = chars[Math.floor(Math.random() * chars.length)];
            ctx.fillStyle = y === 1 ? '#ffffff' : '#22c55e';
            ctx.fillText(char, i * fontSize, y * fontSize);

            if (y * fontSize > canvas.height && Math.random() > 0.975) {
                drops[i] = 0;
            }
            drops[i]++;
        });
    }

    setInterval(draw, 40);
});
</script>
@endpush
