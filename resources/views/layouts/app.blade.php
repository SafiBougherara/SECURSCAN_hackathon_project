<!DOCTYPE html>
<html lang="fr" class="dark">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'SecureScan') — Security Scanner</title>
    <meta name="description"
        content="SecureScan: scan your GitHub repositories for security vulnerabilities in seconds.">
    <link rel="icon" type="image/png" href="{{ asset('asset/robot_LD.png') }}">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap"
        rel="stylesheet">

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'sans-serif'], mono: ['JetBrains Mono', 'monospace'] },
                    colors: {
                        brand: {
                            50: '#f0fdf4',
                            100: '#dcfce7',
                            400: '#4ade80',
                            500: '#22c55e',
                            600: '#16a34a',
                            900: '#14532d',
                        },
                        surface: {
                            900: '#0a0f1e',
                            800: '#0d1428',
                            700: '#111827',
                            600: '#1a2234',
                            500: '#243047',
                        }
                    },
                    animation: {
                        'pulse-slow': 'pulse 3s ease-in-out infinite',
                        'float': 'float 6s ease-in-out infinite',
                        'glow': 'glow 2s ease-in-out infinite',
                    },
                    keyframes: {
                        float: { '0%,100%': { transform: 'translateY(0px)' }, '50%': { transform: 'translateY(-10px)' } },
                        glow: { '0%,100%': { boxShadow: '0 0 20px rgba(34,197,94,0.3)' }, '50%': { boxShadow: '0 0 40px rgba(34,197,94,0.6)' } },
                    }
                }
            }
        }
    </script>

    <!-- Alpine.js -->
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

    <style>
        body {
            background: #0a0f1e;
            font-family: 'Inter', sans-serif;
        }

        .gradient-text {
            background: linear-gradient(135deg, #4ade80, #22c55e, #16a34a);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .glass {
            background: rgba(17, 24, 39, 0.6);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(74, 222, 128, 0.1);
        }

        .glass-card {
            background: rgba(17, 24, 39, 0.8);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.06);
            transition: all 0.3s ease;
        }

        .glass-card:hover {
            border-color: rgba(74, 222, 128, 0.25);
            transform: translateY(-2px);
            box-shadow: 0 8px 32px rgba(34, 197, 94, 0.15);
        }

        .btn-primary {
            background: linear-gradient(135deg, #22c55e, #16a34a);
            transition: all 0.3s;
            box-shadow: 0 4px 20px rgba(34, 197, 94, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 8px 30px rgba(34, 197, 94, 0.5);
        }

        .severity-critical {
            border-left: 3px solid #ef4444;
        }

        .severity-high {
            border-left: 3px solid #f97316;
        }

        .severity-medium {
            border-left: 3px solid #eab308;
        }

        .severity-low {
            border-left: 3px solid #3b82f6;
        }

        .severity-info {
            border-left: 3px solid #6b7280;
        }

        ::-webkit-scrollbar {
            width: 6px;
        }

        ::-webkit-scrollbar-track {
            background: #0a0f1e;
        }

        ::-webkit-scrollbar-thumb {
            background: #14532d;
            border-radius: 3px;
        }
    </style>

    @stack('styles')
</head>

<body class="min-h-screen text-gray-100">

    <nav class="glass sticky top-0 z-50 px-6 py-4">
        <div class="max-w-7xl mx-auto flex items-center justify-between">
            <a href="{{ route('home') }}" class="flex items-center gap-3 group">
                <div class="w-10 h-10 flex items-center justify-center group-hover:scale-110 transition-transform">
                    <img src="{{ asset('asset/robot_LD.png') }}" alt="SecureScan Robot"
                        class="w-full h-full object-contain">
                </div>
                <span class="text-xl font-bold gradient-text">SecureScan</span>
            </a>
            <div class="flex items-center gap-3 text-sm">
                <span class="hidden sm:block text-gray-400">OWASP Top 10 · 2025</span>

                {{-- Language Switcher Dropdown --}}
                <div class="h-5 w-px bg-white/10 mx-1"></div>
                <div class="relative" x-data="{ open: false }">
                    <button @click="open = !open" @click.away="open = false"
                        class="flex items-center gap-1 px-2 py-1 text-xs text-gray-400 hover:text-white transition-colors focus:outline-none">
                        @if(app()->getLocale() === 'fr')
                            🇫🇷 FR
                        @else
                            🇬🇧 EN
                        @endif
                        <svg class="w-3 h-3 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7">
                            </path>
                        </svg>
                    </button>

                    <div x-show="open" x-cloak x-transition:enter="transition ease-out duration-100"
                        x-transition:enter-start="transform opacity-0 scale-95"
                        x-transition:enter-end="transform opacity-100 scale-100"
                        x-transition:leave="transition ease-in duration-75"
                        x-transition:leave-start="transform opacity-100 scale-100"
                        x-transition:leave-end="transform opacity-0 scale-95"
                        class="absolute right-0 mt-3 w-24 rounded-lg bg-[#0d1428] border border-white/10 shadow-xl overflow-hidden z-50 py-1"
                        style="display: none;">
                        <a href="{{ route('lang.switch', 'en') }}"
                            class="flex items-center gap-2 px-3 py-2 text-xs hover:bg-white/5 transition-colors {{ app()->getLocale() === 'en' ? 'text-white bg-white/5' : 'text-gray-400' }}">
                            🇬🇧 EN
                        </a>
                        <a href="{{ route('lang.switch', 'fr') }}"
                            class="flex items-center gap-2 px-3 py-2 text-xs hover:bg-white/5 transition-colors {{ app()->getLocale() === 'fr' ? 'text-white bg-white/5' : 'text-gray-400' }}">
                            🇫🇷 FR
                        </a>
                    </div>
                </div>
                <div class="h-5 w-px bg-white/10 mx-1"></div>

                @auth
                    <a href="{{ route('scans.index') }}"
                        class="px-3 py-1.5 rounded-lg border border-white/10 text-gray-400 hover:text-white hover:border-white/20 transition-all text-xs">
                        📋 {{ __('ui.nav_my_scans') }}
                    </a>
                    <a href="{{ route('settings') }}"
                        class="px-3 py-1.5 rounded-lg border border-white/10 text-gray-400 hover:text-white hover:border-white/20 transition-all text-xs">
                        ⚙️ Settings
                    </a>
                    @if(auth()->user()->is_admin)
                        <span
                            class="px-2 py-0.5 rounded-full bg-green-500/20 border border-green-500/30 text-green-400 text-xs font-mono">Admin</span>
                    @endif
                    <div class="flex items-center gap-2">
                        <span class="text-gray-400 text-xs hidden md:block">{{ auth()->user()->name }}</span>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit"
                                class="px-3 py-1.5 rounded-lg border border-white/10 text-gray-500 hover:text-red-400 hover:border-red-500/30 transition-all text-xs">
                                {{ __('ui.nav_sign_out') }}
                            </button>
                        </form>
                    </div>
                @else
                    <a href="{{ route('login') }}"
                        class="px-3 py-1.5 rounded-lg border border-white/10 text-gray-400 hover:text-white transition-all text-xs">
                        {{ __('ui.nav_sign_in') }}
                    </a>
                    <a href="{{ route('register') }}"
                        class="px-3 py-1.5 rounded-lg bg-green-500/10 border border-green-500/20 text-green-400 hover:bg-green-500/20 transition-all text-xs font-medium">
                        {{ __('ui.nav_register') }}
                    </a>
                @endauth
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main>
        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="mt-20 border-t border-white/5 py-8 text-center text-gray-600 text-sm">
        <p>SecureScan — Powered by Semgrep · ESLint · TruffleHog · Bandit</p>
    </footer>

    @stack('scripts')
</body>

</html>