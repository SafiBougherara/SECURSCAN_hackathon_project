@extends('layouts.app')
@section('title', 'Scan Failed')

@section('content')
    <div class="min-h-[80vh] flex flex-col items-center justify-center px-4 py-20">
        <div class="text-6xl mb-6">❌</div>
        <h1 class="text-3xl font-bold text-white mb-2">{{ __('ui.failed_title') }}</h1>
        <p class="text-gray-400 text-center max-w-md mb-2">
            {{ __('ui.failed_sub') }}
        </p>
        <p class="font-mono text-sm text-gray-600 mb-8 break-all max-w-xl text-center">{{ $scan->repo_url }}</p>

        <div class="glass rounded-xl p-5 max-w-md w-full mb-8 text-sm text-gray-400 space-y-2">
            <p class="font-semibold text-gray-300">{{ __('ui.failed_causes') }}</p>
            <ul class="space-y-1 list-disc list-inside text-gray-500">
                <li>{{ __('ui.failed_c1') }}</li>
                <li>{{ __('ui.failed_c2') }}</li>
                <li>{{ __('ui.failed_c3') }}</li>
                <li>{{ __('ui.failed_c4') }}</li>
            </ul>
        </div>

        <a href="{{ route('home') }}" class="btn-primary rounded-xl px-8 py-4 font-semibold text-white">
            {{ __('ui.failed_try') }}
        </a>
    </div>
@endsection