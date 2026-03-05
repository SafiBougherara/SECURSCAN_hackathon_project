@extends('layouts.app')
@section('title', 'Login')

@section('content')
    <div class="min-h-[80vh] flex items-center justify-center px-4">
        <div class="w-full max-w-md">
            <div class="text-center mb-8">
                <div class="flex justify-center mb-6">
                    <img src="{{ asset('asset/robot_LD.png') }}" alt="Robot" class="w-24 h-24 object-contain animate-float">
                </div>
                <h1 class="text-3xl font-bold gradient-text">{{ __('ui.login_title') }}</h1>
                <p class="text-gray-500 mt-2">{{ __('ui.login_sub') }}</p>
            </div>

            <div class="glass rounded-2xl p-8">
                @if($errors->any())
                    <div class="mb-5 p-3 rounded-lg bg-red-500/10 border border-red-500/20 text-red-400 text-sm">
                        {{ $errors->first() }}
                    </div>
                @endif

                <form method="POST" action="{{ route('login') }}" class="space-y-5">
                    @csrf
                    <div>
                        <label
                            class="block text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">{{ __('ui.login_email') }}</label>
                        <input type="email" name="email" value="{{ old('email') }}" required autofocus
                            class="w-full bg-black/30 border border-white/10 rounded-xl px-4 py-3 text-white placeholder-gray-600 focus:outline-none focus:border-green-500/50 focus:ring-1 focus:ring-green-500/30 transition-all"
                            placeholder="you@example.com">
                    </div>
                    <div>
                        <label
                            class="block text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">{{ __('ui.login_password') }}</label>
                        <input type="password" name="password" required
                            class="w-full bg-black/30 border border-white/10 rounded-xl px-4 py-3 text-white placeholder-gray-600 focus:outline-none focus:border-green-500/50 focus:ring-1 focus:ring-green-500/30 transition-all"
                            placeholder="••••••••">
                    </div>
                    <div class="flex items-center gap-3">
                        <input type="checkbox" name="remember" id="remember" class="rounded">
                        <label for="remember" class="text-sm text-gray-400">{{ __('ui.login_remember') }}</label>
                    </div>
                    <button type="submit" class="btn-primary w-full rounded-xl py-3 font-semibold text-white">
                        {{ __('ui.login_btn') }}
                    </button>
                </form>

                <p class="text-center text-gray-500 text-sm mt-6">
                    {{ __('ui.login_no_acc') }}
                    <a href="{{ route('register') }}"
                        class="text-green-400 hover:text-green-300 transition-colors font-medium">{{ __('ui.login_create') }}</a>
                </p>
            </div>
        </div>
    </div>
@endsection