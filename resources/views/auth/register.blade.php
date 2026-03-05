@extends('layouts.app')
@section('title', 'Register')

@section('content')
    <div class="min-h-[80vh] flex items-center justify-center px-4">
        <div class="w-full max-w-md">
            <div class="text-center mb-8">
                <div class="text-5xl mb-4">🛡️</div>
                <h1 class="text-3xl font-bold gradient-text">{{ __('ui.register_title') }}</h1>
                <p class="text-gray-500 mt-2">{{ __('ui.register_sub') }}</p>
            </div>

            <div class="glass rounded-2xl p-8">
                @if($errors->any())
                    <div class="mb-5 p-3 rounded-lg bg-red-500/10 border border-red-500/20 text-red-400 text-sm">
                        {{ $errors->first() }}
                    </div>
                @endif

                <form method="POST" action="{{ route('register') }}" class="space-y-5">
                    @csrf
                    <div>
                        <label
                            class="block text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">{{ __('ui.register_name') }}</label>
                        <input type="text" name="name" value="{{ old('name') }}" required autofocus
                            class="w-full bg-black/30 border border-white/10 rounded-xl px-4 py-3 text-white placeholder-gray-600 focus:outline-none focus:border-green-500/50 focus:ring-1 focus:ring-green-500/30 transition-all"
                            placeholder="John Doe">
                    </div>
                    <div>
                        <label
                            class="block text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">{{ __('ui.register_email') }}</label>
                        <input type="email" name="email" value="{{ old('email') }}" required
                            class="w-full bg-black/30 border border-white/10 rounded-xl px-4 py-3 text-white placeholder-gray-600 focus:outline-none focus:border-green-500/50 focus:ring-1 focus:ring-green-500/30 transition-all"
                            placeholder="you@example.com">
                    </div>
                    <div>
                        <label
                            class="block text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">{{ __('ui.register_pass') }}</label>
                        <input type="password" name="password" required
                            class="w-full bg-black/30 border border-white/10 rounded-xl px-4 py-3 text-white placeholder-gray-600 focus:outline-none focus:border-green-500/50 focus:ring-1 focus:ring-green-500/30 transition-all"
                            placeholder="Min. 8 characters">
                    </div>
                    <div>
                        <label
                            class="block text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">{{ __('ui.register_confirm') }}</label>
                        <input type="password" name="password_confirmation" required
                            class="w-full bg-black/30 border border-white/10 rounded-xl px-4 py-3 text-white placeholder-gray-600 focus:outline-none focus:border-green-500/50 focus:ring-1 focus:ring-green-500/30 transition-all"
                            placeholder="••••••••">
                    </div>
                    <button type="submit" class="btn-primary w-full rounded-xl py-3 font-semibold text-white">
                        {{ __('ui.register_btn') }}
                    </button>
                </form>

                <p class="text-center text-gray-500 text-sm mt-6">
                    {{ __('ui.register_have') }}
                    <a href="{{ route('login') }}"
                        class="text-green-400 hover:text-green-300 transition-colors font-medium">{{ __('ui.register_signin') }}</a>
                </p>
            </div>
        </div>
    </div>
@endsection