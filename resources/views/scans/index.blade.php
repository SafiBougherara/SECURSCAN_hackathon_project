@extends('layouts.app')
@section('title', __('ui.scans_title'))

@section('content')
    {{-- Alpine.js wrapper with custom confirm modal state --}}
    <div class="max-w-5xl mx-auto px-4 py-10" x-data="{
                confirm: {
                    open: false,
                    title: '',
                    message: '',
                    action: null,
                    formId: null,
                },
                openConfirm(title, message, formId) {
                    this.confirm.title   = title;
                    this.confirm.message = message;
                    this.confirm.formId  = formId;
                    this.confirm.open    = true;
                },
                submitConfirmed() {
                    if (this.confirm.formId) {
                        document.getElementById(this.confirm.formId).submit();
                    }
                    this.confirm.open = false;
                }
             }">

        {{-- Header --}}
        <div class="flex items-center justify-between mb-8">
            <div>
                <h1 class="text-2xl font-bold text-white">
                    @if(auth()->user()->is_admin)
                        🛡️ {{ __('ui.scans_admin') }} <span
                            class="text-sm font-normal text-green-400 ml-2">{{ __('ui.scans_admin_view') }}</span>
                    @else
                        📋 {{ __('ui.scans_title') }}
                    @endif
                </h1>
                <p class="text-gray-500 text-sm mt-1">{{ $scans->total() }} {{ __('ui.scans_total') }}</p>
            </div>
            <a href="{{ route('home') }}" class="btn-primary rounded-xl px-5 py-3 text-sm font-semibold text-white">
                {{ __('ui.scans_new') }}
            </a>
        </div>

        {{-- Flash messages --}}
        @if(session('success'))
            <div class="mb-5 p-3 rounded-lg bg-green-500/10 border border-green-500/20 text-green-400 text-sm">
                ✅ {{ __('ui.scans_deleted') }}
            </div>
        @endif

        {{-- Table --}}
        <div class="glass rounded-2xl overflow-hidden">
            @forelse($scans as $scan)
                <div class="flex items-center gap-4 px-6 py-4 border-b border-white/5 hover:bg-white/2 transition-colors group">
                    {{-- Status indicator --}}
                    <div class="flex-shrink-0">
                        @if($scan->status === 'done')
                            <span class="w-2.5 h-2.5 rounded-full bg-green-400 inline-block"></span>
                        @elseif($scan->status === 'running')
                            <span class="w-2.5 h-2.5 rounded-full bg-green-400 animate-pulse inline-block"></span>
                        @elseif($scan->status === 'failed')
                            <span class="w-2.5 h-2.5 rounded-full bg-red-400 inline-block"></span>
                        @else
                            <span class="w-2.5 h-2.5 rounded-full bg-gray-600 inline-block"></span>
                        @endif
                    </div>

                    {{-- Repo info --}}
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-3">
                            <span
                                class="font-mono font-semibold text-white text-sm truncate">{{ $scan->repo_name ?? 'unknown' }}</span>
                            @if($scan->status === 'done')
                                <span
                                    class="text-xs px-2 py-0.5 rounded-full
                                                                            @if($scan->score >= 70) bg-green-500/10 text-green-400 border border-green-500/20
                                                                            @elseif($scan->score >= 40) bg-yellow-500/10 text-yellow-400 border border-yellow-500/20
                                                                            @else bg-red-500/10 text-red-400 border border-red-500/20 @endif">
                                    {{ $scan->score }}/100
                                </span>
                            @else
                                <span
                                    class="text-xs px-2 py-0.5 rounded-full bg-white/5 border border-white/10 text-gray-500 capitalize">
                                    {{ $scan->status }}
                                </span>
                            @endif
                            @if(auth()->user()->is_admin && $scan->user)
                                <span class="text-xs text-gray-600">{{ __('ui.scans_by') }} {{ $scan->user->name }}</span>
                            @endif
                        </div>
                        <p class="text-gray-600 text-xs font-mono truncate mt-0.5">{{ $scan->repo_url }}</p>
                    </div>

                    {{-- Date --}}
                    <div class="text-xs text-gray-600 flex-shrink-0 hidden sm:block">
                        {{ $scan->created_at->diffForHumans() }}
                    </div>

                    {{-- Actions --}}
                    <div class="flex items-center gap-2 flex-shrink-0 opacity-0 group-hover:opacity-100 transition-opacity">
                        @if($scan->status === 'done')
                            <a href="{{ route('scan.dashboard', $scan) }}"
                                class="text-xs px-3 py-1.5 rounded-lg bg-green-500/10 border border-green-500/20 text-green-400 hover:bg-green-500/20 transition-colors">
                                {{ __('ui.scans_view') }}
                            </a>
                        @elseif(in_array($scan->status, ['pending', 'running']))
                            <a href="{{ route('scan.loading', $scan) }}"
                                class="text-xs px-3 py-1.5 rounded-lg bg-green-500/10 border border-green-500/20 text-green-400 hover:bg-green-500/20 transition-colors">
                                {{ __('ui.scans_watch') }}
                            </a>
                        @endif

                        {{-- Rescan (custom confirm) --}}
                        <form id="rescan-{{ $scan->id }}" method="POST" action="{{ route('scan.rescan', $scan) }}">
                            @csrf
                            <button type="button"
                                @click="openConfirm('{{ __('ui.dash_rescan') }}', '{{ __('ui.dash_rescan_confirm') }}', 'rescan-{{ $scan->id }}')"
                                class="text-xs px-3 py-1.5 rounded-lg bg-green-500/10 border border-green-500/20 text-green-400 hover:bg-green-500/20 transition-colors">
                                {{ __('ui.dash_rescan') }}
                            </button>
                        </form>

                        {{-- Delete (custom confirm) --}}
                        <form id="delete-{{ $scan->id }}" method="POST" action="{{ route('scan.destroy', $scan) }}">
                            @csrf
                            @method('DELETE')
                            <button type="button"
                                @click="openConfirm('{{ __('ui.scans_delete_title') }}', '{{ __('ui.scans_confirm_delete') }}', 'delete-{{ $scan->id }}')"
                                class="text-xs px-3 py-1.5 rounded-lg bg-red-500/10 border border-red-500/20 text-red-400 hover:bg-red-500/20 transition-colors">
                                {{ __('ui.scans_delete') }}
                            </button>
                        </form>
                    </div>
                </div>
            @empty
                <div class="text-center py-20 text-gray-600">
                    <div class="text-4xl mb-4">📭</div>
                    <p class="font-medium">{{ __('ui.scans_empty') }}</p>
                    <a href="{{ route('home') }}"
                        class="text-green-400 hover:text-green-300 text-sm mt-2 inline-block">{{ __('ui.scans_empty_cta') }}</a>
                </div>
            @endforelse
        </div>

        {{-- Pagination --}}
        @if($scans->hasPages())
            <div class="mt-6">
                {{ $scans->links() }}
            </div>
        @endif

        {{-- ====== CUSTOM CONFIRM MODAL ====== --}}
        <div x-show="confirm.open" x-cloak
            class="fixed inset-0 bg-black/70 backdrop-blur-sm flex items-center justify-center z-50 p-4"
            x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95">
            <div class="glass rounded-2xl p-7 max-w-sm w-full shadow-2xl border border-white/10"
                @click.outside="confirm.open = false">
                {{-- Icon --}}
                <div
                    class="w-12 h-12 rounded-xl bg-green-500/10 border border-green-500/20 flex items-center justify-center text-2xl mx-auto mb-4">
                    🔄
                </div>
                {{-- Title --}}
                <h3 class="text-lg font-bold text-white text-center mb-2" x-text="confirm.title"></h3>
                {{-- Message --}}
                <p class="text-sm text-gray-400 text-center leading-relaxed mb-6" x-text="confirm.message"></p>
                {{-- Buttons --}}
                <div class="flex gap-3">
                    <button @click="confirm.open = false"
                        class="flex-1 px-4 py-2.5 rounded-xl border border-white/10 text-gray-400 hover:bg-white/5 text-sm font-medium transition-colors">
                        {{ __('ui.confirm_cancel') }}
                    </button>
                    <button @click="submitConfirmed()"
                        class="flex-1 btn-primary px-4 py-2.5 rounded-xl text-white text-sm font-semibold transition-all">
                        {{ __('ui.confirm_ok') }}
                    </button>
                </div>
            </div>
        </div>

    </div>
@endsection