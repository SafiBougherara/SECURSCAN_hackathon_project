@extends('layouts.app')
@section('title', 'Dashboard — ' . ($scan->repo_name ?? 'Scan'))
@php use Illuminate\Support\Str; @endphp

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
<div class="max-w-7xl mx-auto px-4 py-8"
     x-data="dashboard({{ $vulnerabilities->count() }})"
     x-init="initCharts({{ $bySeverity->toJson() }}, {{ $byOwasp->toJson() }})">

    {{-- ====== HEADER ====== --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8">
        <div>
            <div class="flex items-center gap-3 mb-1">
                <a href="{{ route('home') }}" class="text-gray-500 hover:text-sky-400 transition-colors text-sm">← {{ __('ui.dash_breadcrumb') }}</a>
                <span class="text-gray-700">/</span>
                <span class="font-mono text-sm text-gray-400">{{ $scan->repo_name }}</span>
            </div>
            <h1 class="text-2xl font-bold text-white">{{ __('ui.dash_title') }}</h1>
            <p class="text-gray-500 text-sm font-mono mt-1 break-all">{{ $scan->repo_url }}</p>
        </div>

        <!-- Score + Buttons -->
        <div class="flex items-center gap-3 flex-wrap justify-end">
            <!-- Global Score -->
            <div class="glass rounded-2xl px-6 py-4 text-center min-w-[120px]">
                <div class="text-3xl font-extrabold
                    @if($scan->score >= 70) text-green-400
                    @elseif($scan->score >= 40) text-yellow-400
                    @else text-red-400
                    @endif">
                    {{ $scan->score }}<span class="text-lg text-gray-500">/100</span>
                </div>
                <div class="text-xs text-gray-500 mt-1 uppercase tracking-wider">{{ __('ui.dash_score') }}</div>
            </div>

            <!-- Rescan Button -->
            @auth
            <button @click="rescanModalOpen = true"
                class="px-5 py-3 rounded-xl border border-green-500/30 text-green-400 hover:bg-green-500/10 font-semibold text-sm transition-all flex items-center gap-2">
                {{ __('ui.dash_rescan') }}
            </button>
            @endauth

            <!-- Export PDF Button -->
            <button @click="exportPdf('{{ route('scan.pdf', $scan) }}')"
               :disabled="pdfLoading"
               class="px-5 py-3 rounded-xl border border-violet-500/30 text-violet-400 hover:bg-violet-500/10 font-semibold text-sm transition-all flex items-center gap-2 disabled:opacity-40 disabled:cursor-not-allowed">
                {{ __('ui.dash_export_pdf') }}
            </button>

            <!-- PR Button -->
            <button
                id="pr-btn"
                @click="createPR('{{ route('scan.pull-request', $scan) }}')"
                :disabled="selected.length === 0 || prLoading"
                class="btn-primary rounded-xl px-6 py-4 font-semibold text-white flex items-center gap-2 disabled:opacity-40 disabled:cursor-not-allowed disabled:transform-none"
                :title="selected.length === 0 ? '{{ __('ui.dash_select_warn') }}' : '{{ __('ui.dash_create_pr') }}'"
            >
                <span x-show="!prLoading">🔀 {{ __('ui.dash_create_pr') }}</span>
                <span x-show="prLoading" class="flex items-center gap-2">
                    <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                    {{ __('ui.dash_creating_pr') }}
                </span>
                <span x-show="selected.length > 0 && !prLoading" class="bg-white/20 rounded-full px-2 py-0.5 text-xs" x-text="selected.length"></span>
            </button>
        </div>
    </div>

    {{-- ====== PARENT SCAN COMPARISON ====== --}}
    @if($parentScan && $parentScan->status === 'done')
    @php
        $scoreDelta = $scan->score !== null && $parentScan->score !== null ? $scan->score - $parentScan->score : null;
        $vulnDelta  = $vulnerabilities->count() - $parentScan->vulnerabilities->count();
    @endphp
    <div class="glass rounded-2xl p-5 mb-6 border border-green-500/10">
        <div class="flex flex-wrap items-center gap-6">
            <div class="text-sm font-semibold text-gray-400 flex items-center gap-2">{{ __('ui.dash_comparison') }}
                <a href="{{ route('scan.dashboard', $parentScan) }}" class="text-xs text-green-400 hover:underline ml-1">#{{ $parentScan->id }} →</a>
            </div>
            <div class="flex items-center gap-6">
                <!-- Score delta -->
                <div class="text-center">
                    <div class="text-xs text-gray-500 uppercase tracking-wider mb-1">{{ __('ui.dash_comparison_score') }}</div>
                    <div class="flex items-center gap-1.5">
                        <span class="text-lg font-bold text-gray-400">{{ $parentScan->score ?? '?' }}</span>
                        <span class="text-gray-600">→</span>
                        <span class="text-lg font-bold {{ $scan->score >= $parentScan->score ? 'text-green-400' : 'text-red-400' }}">{{ $scan->score ?? '?' }}</span>
                        @if($scoreDelta !== null)
                        <span class="text-sm font-bold ml-1 {{ $scoreDelta >= 0 ? 'text-green-400' : 'text-red-400' }}">
                            {{ $scoreDelta >= 0 ? '↑ +' . $scoreDelta : '↓ ' . $scoreDelta }}
                        </span>
                        @endif
                    </div>
                </div>
                <div class="w-px h-8 bg-white/10"></div>
                <!-- Vuln delta -->
                <div class="text-center">
                    <div class="text-xs text-gray-500 uppercase tracking-wider mb-1">{{ __('ui.dash_comparison_vulns') }}</div>
                    <div class="flex items-center gap-1.5">
                        <span class="text-lg font-bold text-gray-400">{{ $parentScan->vulnerabilities->count() }}</span>
                        <span class="text-gray-600">→</span>
                        <span class="text-lg font-bold {{ $vulnDelta <= 0 ? 'text-green-400' : 'text-red-400' }}">{{ $vulnerabilities->count() }}</span>
                        <span class="text-sm font-bold ml-1 {{ $vulnDelta <= 0 ? 'text-green-400' : 'text-red-400' }}">
                            {{ $vulnDelta > 0 ? '↑ +' . $vulnDelta : ($vulnDelta < 0 ? '↓ ' . $vulnDelta : '→ 0') }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- ====== STAT CARDS ====== --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-8">
        @foreach([
            ['critical', '🔴', '#ef4444', $bySeverity['critical'] ?? 0],
            ['high',     '🟠', '#f97316', $bySeverity['high'] ?? 0],
            ['medium',   '🟡', '#eab308', $bySeverity['medium'] ?? 0],
            ['low',      '🔵', '#3b82f6', $bySeverity['low'] ?? 0],
        ] as [$label, $emoji, $color, $count])
        <div class="glass-card rounded-xl p-5">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs uppercase tracking-widest text-gray-500">{{ __('ui.sev_' . $label) }}</span>
                <span class="text-lg">{{ $emoji }}</span>
            </div>
            <div class="text-3xl font-extrabold" style="color: {{ $color }}">{{ $count }}</div>
        </div>
        @endforeach
    </div>

    {{-- ====== CHARTS ROW ====== --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <div class="glass rounded-2xl p-6">
            <h2 class="text-sm font-semibold text-gray-400 uppercase tracking-wider mb-4">{{ __('ui.dash_severity_dist') }}</h2>
            <div class="h-64"><canvas id="severityChart"></canvas></div>
        </div>
        <div class="glass rounded-2xl p-6">
            <h2 class="text-sm font-semibold text-gray-400 uppercase tracking-wider mb-4">{{ __('ui.dash_owasp_dist') }}</h2>
            <div class="h-64"><canvas id="owaspChart"></canvas></div>
        </div>
    </div>

    {{-- ====== FILTERS ====== --}}
    <div class="glass rounded-2xl p-4 mb-6 flex flex-wrap gap-3 items-center">
        <span class="text-xs text-gray-500 uppercase tracking-wider mr-2">{{ __('ui.dash_filter') }}</span>

        <!-- Severity filter -->
        @foreach(['all', 'critical', 'high', 'medium', 'low', 'info'] as $sev)
        <button
            @click="filterSeverity = '{{ $sev }}'"
            :class="filterSeverity === '{{ $sev }}' ? 'bg-green-500/20 border-green-500/40 text-green-300' : 'border-white/10 text-gray-500 hover:text-gray-300'"
            class="px-3 py-1.5 rounded-lg border text-xs font-medium transition-all capitalize">
            {{ $sev === 'all' ? __('ui.dash_all_sev') : __('ui.sev_' . $sev) }}
        </button>
        @endforeach

        <div class="h-5 w-px bg-white/10 mx-1"></div>

        <!-- Tool filter -->
        @php $tools = $vulnerabilities->pluck('tool')->unique()->sort()->values(); @endphp
        @foreach(['all', ...$tools] as $tool)
        <button
            @click="filterTool = '{{ $tool }}'"
            :class="filterTool === '{{ $tool }}' ? 'bg-green-500/20 border-green-500/40 text-green-300' : 'border-white/10 text-gray-500 hover:text-gray-300'"
            class="px-3 py-1.5 rounded-lg border text-xs font-medium transition-all">
            {{ $tool === 'all' ? __('ui.dash_all_tools') : $tool }}
        </button>
        @endforeach

        <div class="h-5 w-px bg-white/10 mx-1"></div>

        {{-- Explained filter (toggle) --}}
        <button
            @click="filterExplained = !filterExplained"
            :class="filterExplained ? 'bg-green-500/20 border-green-500/40 text-green-300' : 'border-white/10 text-gray-500 hover:text-gray-300'"
            class="px-3 py-1.5 rounded-lg border text-xs font-medium transition-all flex items-center gap-1">
            ✅ {{ __('ui.dash_explained_short') }}
        </button>

        <div class="h-5 w-px bg-white/10 mx-1"></div>

        {{-- OWASP Category filter --}}
        <span class="text-xs text-gray-500 uppercase tracking-wider">OWASP</span>
        <button
            @click="filterOwasp = 'all'"
            :class="filterOwasp === 'all' ? 'bg-violet-500/20 border-violet-500/40 text-violet-300' : 'border-white/10 text-gray-500 hover:text-gray-300'"
            class="px-3 py-1.5 rounded-lg border text-xs font-medium transition-all">
            Toutes
        </button>
        @foreach($byOwasp->keys()->sort()->values() as $owaspCat)
        <button
            @click="filterOwasp = '{{ $owaspCat }}'"
            :class="filterOwasp === '{{ $owaspCat }}' ? 'bg-violet-500/20 border-violet-500/40 text-violet-300' : 'border-white/10 text-gray-500 hover:text-gray-300'"
            class="px-3 py-1.5 rounded-lg border text-xs font-medium transition-all font-mono">
            {{ explode(':', $owaspCat)[0] }}
        </button>
        @endforeach

        <div class="ml-auto flex items-center gap-4">
            {{-- Select all visible checkbox --}}
            <label class="flex items-center gap-2 cursor-pointer select-none group">
                <div class="w-4 h-4 rounded border-2 flex-shrink-0 flex items-center justify-center transition-all"
                     :class="isAllVisibleSelected() ? 'bg-sky-500 border-sky-500' : 'border-gray-600 group-hover:border-gray-400'">
                    <svg x-show="isAllVisibleSelected()" class="w-2.5 h-2.5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
                <input type="checkbox" class="hidden"
                    :checked="isAllVisibleSelected()"
                    @change="toggleSelectAllVisible()">
                <span class="text-xs text-gray-500 group-hover:text-gray-300 transition-colors">{{ __('ui.dash_select_all') }}</span>
            </label>

            <!-- Generate Fixes Button -->
            <button
                x-show="selected.length > 0"
                x-transition
                @click="generateFixes('{{ route('vulnerabilities.fix-batch') }}')"
                :disabled="fixBatchLoading"
                class="px-3 py-1.5 rounded-lg border border-sky-500/30 bg-sky-500/10 text-sky-400 hover:bg-sky-500/20 text-xs font-medium transition-all flex items-center gap-1 disabled:opacity-40 disabled:cursor-not-allowed">
                <span x-show="!fixBatchLoading">{{ __('ui.dash_generate_fix_btn') }}</span>
                <span x-show="fixBatchLoading" class="flex items-center gap-1">
                    <svg class="animate-spin w-3 h-3" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                    </svg>
                    {{ __('ui.dash_generating_fix') }}
                </span>
            </button>

            <div class="text-xs text-gray-500">
                <span x-show="selected.length > 0" class="text-sky-400 font-semibold" x-text="selected.length + ' {{ __('ui.dash_selected') }} · '"></span><span x-text="filteredCount"></span> {{ __('ui.dash_shown') }}
            </div>
        </div>
    </div>

    {{-- ====== VULNERABILITY CARDS ====== --}}
    <div id="vuln-list" class="grid grid-cols-1 md:grid-cols-2 gap-4">
        @forelse($vulnerabilities as $vuln)
        <div
            class="glass-card rounded-xl p-5 severity-{{ $vuln->severity }} cursor-pointer select-none"
            data-severity="{{ $vuln->severity }}"
            data-tool="{{ $vuln->tool }}"
            data-id="{{ $vuln->id }}"
            data-owasp="{{ $vuln->owasp_category }}"
            data-explained="{{ $vuln->chat_explanation ? '1' : '0' }}"
            @explanation-saved.window="if ($event.detail.id === {{ $vuln->id }}) $el.dataset.explained = '1'"
            @explanation-cleared.window="if ($event.detail.id === {{ $vuln->id }}) $el.dataset.explained = '0'"
            @click="toggleSelect({{ $vuln->id }})"
            :class="selected.includes({{ $vuln->id }}) ? 'ring-2 ring-sky-500/50 bg-sky-500/5' : ''"
            x-show="matchesFilter('{{ $vuln->severity }}', '{{ $vuln->tool }}', {{ $vuln->chat_explanation ? 'true' : 'false' }}, {{ $vuln->id }})"
        >
            <div class="flex items-start justify-between gap-3">
                <div class="flex items-center gap-3 flex-1 min-w-0">
                    <!-- Checkbox -->
                    <div class="w-5 h-5 rounded border-2 flex-shrink-0 flex items-center justify-center transition-all"
                         :class="selected.includes({{ $vuln->id }}) ? 'bg-sky-500 border-sky-500' : 'border-gray-600'">
                        <svg x-show="selected.includes({{ $vuln->id }})" class="w-3 h-3 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                        </svg>
                    </div>

                    <div class="flex-1 min-w-0">
                        <!-- Severity + OWASP badges -->
                        <div class="flex flex-wrap items-center gap-2 mb-2">
                            <span class="text-xs font-bold px-2 py-0.5 rounded-full"
                                style="background: {{ $vuln->severity_color }}20; color: {{ $vuln->severity_color }}; border: 1px solid {{ $vuln->severity_color }}40">
                                {{ $vuln->severity_emoji }} {{ strtoupper($vuln->severity) }}
                            </span>
                            @if($vuln->owasp_category)
                            <span class="text-xs px-2 py-0.5 rounded-full bg-violet-500/10 border border-violet-500/20 text-violet-400">
                                {{ $vuln->owasp_category }}
                            </span>
                            @endif
                            <span class="text-xs px-2 py-0.5 rounded-full bg-white/5 border border-white/10 text-gray-400 font-mono">
                                {{ $vuln->tool }}
                            </span>
                        </div>

                        <!-- File + line -->
                        @if($vuln->file_path)
                        <p class="text-xs text-gray-500 font-mono mb-2 truncate">
                            📄 {{ $vuln->file_path }}@if($vuln->line_start):{{ $vuln->line_start }}@endif
                        </p>
                        @endif

                        <!-- Message -->
                        <p class="text-sm text-gray-300 leading-relaxed line-clamp-2">{{ $vuln->message }}</p>

                        <!-- OWASP label -->
                        @if($vuln->owasp_label)
                        <p class="text-xs text-violet-400/70 mt-1">{{ $vuln->owasp_label }}</p>
                        @endif
                    </div>
                </div>

                {{-- Smart Explain button: shows '✅ Expliqué' if cached, '🧠 Expliquer' otherwise --}}
                <div class="flex items-center gap-2"
                     x-data="{ explained: {{ $vuln->chat_explanation ? 'true' : 'false' }} }"
                     @explanation-saved.window="if ($event.detail.id === {{ $vuln->id }}) explained = true"
                     @explanation-cleared.window="if ($event.detail.id === {{ $vuln->id }}) explained = false">
                    <button
                        @click.stop="$dispatch('open-chat', { id: {{ $vuln->id }}, message: '{{ addslashes($vuln->message) }}', tool: '{{ $vuln->tool }}', file: '{{ $vuln->file_path ?? '' }}', explanation: '{{ addslashes($vuln->chat_explanation) }}' })"
                        :class="explained
                            ? 'bg-green-500/10 border-green-500/30 text-green-400 hover:bg-green-500/20'
                            : 'bg-emerald-500/10 border-emerald-500/20 text-emerald-400 hover:bg-emerald-500/20'"
                        class="flex-shrink-0 text-xs px-2 py-1 rounded-lg border transition-all flex items-center gap-1">
                        <span x-show="!explained">🧠 <span class="hidden sm:inline">{{ __('ui.dash_explain') }}</span></span>
                        <span x-show="explained">✅ <span class="hidden sm:inline">{{ __('ui.dash_explained_short') }}</span></span>
                    </button>
                    <button
                        @click.stop="openDetail({{ $vuln->id }}, '{{ route('vulnerability.fix', $vuln) }}')"
                        class="flex-shrink-0 text-xs px-2 py-1 rounded-lg bg-blue-500/10 border border-blue-500/20 text-blue-400 hover:bg-blue-500/20 transition-all">
                        🔍 Detail
                    </button>
                </div>

                <!-- Fix suggestion indicator -->
                @if($vuln->fix_suggestion)
                <span class="flex-shrink-0 text-xs px-2 py-1 rounded-lg bg-green-500/10 border border-green-500/20 text-green-400">✨ Fix</span>
                @endif
            </div>

            <!-- Fix suggestion expandable -->
            @if($vuln->fix_suggestion && !str_contains($vuln->fix_suggestion, '|||'))
            <div class="mt-3 pt-3 border-t border-white/5">
                <p class="text-xs text-gray-500 mb-1 uppercase tracking-wider">{{ __('ui.dash_ai_suggestion') }}</p>
                <p class="text-xs text-gray-400 font-mono bg-black/20 rounded-lg p-3 leading-relaxed">{{ Str::limit($vuln->fix_suggestion, 300) }}</p>
            </div>
            @endif
        </div>
        @empty
        <div class="col-span-2 text-center py-20 text-gray-500">
            <div class="text-5xl mb-4">🎉</div>
            <p class="text-xl font-semibold text-green-400">{{ __('ui.dash_no_vulns') }}</p>
            <p class="text-sm mt-2">{{ __('ui.dash_no_vulns_sub') }}</p>
        </div>
        @endforelse
    </div>

    {{-- ====== VULNERABILITY DETAIL MODAL ====== --}}
    <div x-show="vulnDetail.open" x-cloak
         class="fixed inset-0 bg-black/80 backdrop-blur-sm flex items-center justify-center z-50 p-4"
         @click="vulnDetail.open = false"
         @keydown.escape.window="vulnDetail.open = false">
        <div class="glass rounded-2xl max-w-4xl w-full shadow-2xl max-h-[90vh] overflow-y-auto" @click.stop>

            <!-- Modal header -->
            <div class="flex items-center justify-between px-6 py-4 border-b border-white/10">
                <div class="flex items-center gap-3">
                    <span x-text="vulnDetail.severityEmoji" class="text-xl"></span>
                    <span x-text="vulnDetail.severity?.toUpperCase()" class="text-sm font-bold" :style="'color:' + vulnDetail.severityColor"></span>
                    <span class="text-xs px-2 py-0.5 rounded-full bg-violet-500/10 border border-violet-500/20 text-violet-400" x-text="vulnDetail.owaspCategory" x-show="vulnDetail.owaspCategory"></span>
                    <span class="text-xs px-2 py-0.5 rounded-full bg-white/5 border border-white/10 text-gray-400 font-mono" x-text="vulnDetail.tool"></span>
                </div>
                <div class="flex items-center gap-2">
                    <button @click="$dispatch('open-chat', { id: vulnDetail.id, message: vulnDetail.message, tool: vulnDetail.tool, file: vulnDetail.filePath, explanation: vulnDetail.explanation })" class="text-xs px-3 py-1.5 rounded-lg bg-emerald-500/20 border border-emerald-500/30 text-emerald-300 hover:bg-emerald-500/30 transition-colors flex items-center gap-2 font-medium">
                        🧠 {{ __('ui.dash_explain') }}
                    </button>
                    <button @click="vulnDetail.open = false" class="text-gray-500 hover:text-white text-xl transition-colors ml-2">✕</button>
                </div>
            </div>

            <!-- Modal body -->
            <div class="px-6 py-5 space-y-4">
                <!-- File + line -->
                <div x-show="vulnDetail.filePath" class="flex items-center gap-2 text-xs font-mono text-gray-400 bg-black/20 rounded-lg px-3 py-2">
                    <span>📄</span>
                    <span x-text="vulnDetail.filePath"></span>
                    <span x-show="vulnDetail.lineStart" class="text-green-400" x-text="':' + vulnDetail.lineStart"></span>
                </div>

                <!-- Issue message -->
                <div>
                    <p class="text-xs text-gray-500 uppercase tracking-wider mb-1">{{ __('ui.dash_detail_issue') }}</p>
                    <p class="text-sm text-gray-200 leading-relaxed" x-text="vulnDetail.message"></p>
                </div>

                <!-- OWASP label -->
                <div x-show="vulnDetail.owaspLabel">
                    <p class="text-xs text-violet-400" x-text="vulnDetail.owaspLabel"></p>
                </div>

                <!-- Code snippet -->
                <div x-show="vulnDetail.codeSnippet">
                    <p class="text-xs text-gray-500 uppercase tracking-wider mb-2">{{ __('ui.dash_detail_code') }}</p>
                    <div class="bg-black/50 rounded-xl border border-white/5 overflow-hidden">
                        <pre class="text-xs text-amber-300 font-mono p-4 whitespace-pre-wrap leading-relaxed overflow-x-auto" x-text="vulnDetail.codeSnippet"></pre>
                    </div>
                </div>

                <!-- AI Fix — Diff view + editable -->
                <div x-data="{ fixEdited: false, fixSaving: false }">
                    <div class="flex items-center justify-between mb-2">
                        <p class="text-xs text-gray-500 uppercase tracking-wider">{{ __('ui.dash_detail_ai_fix') }}</p>
                        <span x-show="fixEdited" class="text-[10px] text-amber-400 flex items-center gap-1">✏️ Modifié</span>
                    </div>
                    <!-- Loading -->
                    <div x-show="vulnDetail.fixLoading" class="flex items-center gap-2 text-sm text-gray-400 py-4">
                        <svg class="animate-spin w-4 h-4 text-green-400" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                        </svg>
                        {{ __('ui.dash_detail_loading') }}
                    </div>

                    <!-- Diff view: original | fixed (only shown when code_snippet exists) -->
                    <div x-show="!vulnDetail.fixLoading && vulnDetail.codeSnippet" class="grid grid-cols-2 gap-2 mb-3">
                        <div class="bg-red-950/30 rounded-xl border border-red-500/20 overflow-hidden">
                            <div class="px-3 py-1.5 border-b border-red-500/20 flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-red-500"></span>
                                <span class="text-[10px] text-red-400 font-semibold uppercase tracking-wider">Original</span>
                            </div>
                            <pre class="text-xs text-red-300 font-mono whitespace-pre-wrap leading-relaxed p-3 overflow-x-auto max-h-48" x-text="vulnDetail.codeSnippet"></pre>
                        </div>
                        <div class="bg-green-950/30 rounded-xl border border-green-500/20 overflow-hidden">
                            <div class="px-3 py-1.5 border-b border-green-500/20 flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-green-500"></span>
                                <span class="text-[10px] text-green-400 font-semibold uppercase tracking-wider">Corrigé</span>
                            </div>
                            <pre class="text-xs text-green-300 font-mono whitespace-pre-wrap leading-relaxed p-3 overflow-x-auto max-h-48" x-text="vulnDetail.aiFix"></pre>
                        </div>
                    </div>

                    <!-- Editable fix textarea -->
                    <div x-show="!vulnDetail.fixLoading">
                        <p class="text-[10px] text-gray-500 uppercase tracking-wider mb-1">✏️ Modifier le correctif IA</p>
                        <textarea
                            x-model="vulnDetail.aiFix"
                            @input="fixEdited = true"
                            rows="6"
                            class="w-full bg-black/40 border border-white/10 rounded-xl p-3 text-xs text-green-300 font-mono focus:outline-none focus:border-green-500/50 focus:ring-1 focus:ring-green-500/30 transition-all resize-y leading-relaxed"
                            placeholder="Le correctif IA apparaîtra ici…"
                        ></textarea>
                        <div class="flex items-center gap-2 mt-2">
                            <button
                                x-show="fixEdited"
                                x-transition
                                @click="$dispatch('save-fix', { id: vulnDetail.id, fix: vulnDetail.aiFix }); fixEdited = false; fixSaving = true"
                                :disabled="fixSaving"
                                class="px-3 py-1.5 rounded-lg bg-green-500/20 border border-green-500/30 text-green-400 text-xs font-semibold hover:bg-green-500/30 transition-all flex items-center gap-1 disabled:opacity-50">
                                <span x-show="!fixSaving">💾 Enregistrer</span>
                                <span x-show="fixSaving">⏳ Sauvegarde…</span>
                            </button>
                            <button
                                x-show="fixEdited"
                                x-transition
                                @click="openDetail(vulnDetail.id, '{{ route('vulnerability.fix', ['vulnerability' => '__ID__']) }}'.replace('__ID__', vulnDetail.id)); fixEdited = false"
                                class="px-3 py-1.5 rounded-lg bg-white/5 border border-white/10 text-gray-400 text-xs hover:bg-white/10 transition-all">
                                ↩ Réinitialiser
                            </button>
                        </div>
                    </div>
                </div>

                <!-- AI Explanation (Cache) -->
                <div x-show="vulnDetail.explanation" class="pt-4 border-t border-white/5" x-data="{ confirmClear: false }">
                    <div class="flex items-center justify-between mb-2">
                        <p class="text-xs text-gray-500 uppercase tracking-wider">{{ __('ui.dash_ai_explanation') }}</p>
                        <!-- Normal delete button -->
                        <button x-show="!confirmClear"
                                @click.stop="confirmClear = true"
                                class="text-[10px] text-red-400/70 hover:text-red-400 transition-colors flex items-center gap-1">
                            {{ __('ui.dash_clear_cache') }}
                        </button>
                        <!-- Inline confirm row -->
                        <div x-show="confirmClear" class="flex items-center gap-2">
                            <span class="text-[10px] text-gray-400">{{ __('ui.confirm_cancel') }}?</span>
                            <button @click.stop="confirmClear = false"
                                    class="text-[10px] px-2 py-0.5 rounded bg-white/10 text-gray-300 hover:bg-white/20 transition-colors">
                                {{ __('ui.confirm_cancel') }}
                            </button>
                            <button @click.stop="clearExplanation(vulnDetail.id); confirmClear = false"
                                    class="text-[10px] px-2 py-0.5 rounded bg-red-500/20 border border-red-500/30 text-red-400 hover:bg-red-500/30 transition-colors font-semibold">
                                {{ __('ui.confirm_ok') }}
                            </button>
                        </div>
                    </div>
                    <div class="bg-emerald-950/20 rounded-xl p-4 border border-emerald-500/10">
                        <div class="text-xs text-gray-200 leading-relaxed" x-html="formatMessage(vulnDetail.explanation)"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- PR Success Modal --}}
    <div x-show="prUrl" x-cloak class="fixed inset-0 bg-black/70 backdrop-blur-sm flex items-center justify-center z-50 p-4">
        <div class="glass rounded-2xl p-8 max-w-md w-full text-center shadow-2xl">
            <div class="text-5xl mb-4">🎉</div>
            <h2 class="text-xl font-bold text-white mb-2">{{ __('ui.dash_pr_success') }}</h2>
            <p class="text-gray-400 text-sm mb-6">{{ __('ui.dash_pr_sub') }}</p>
            <a :href="prUrl" target="_blank"
               class="btn-primary rounded-xl px-6 py-3 font-semibold text-white inline-flex items-center gap-2">
                🔗 {{ __('ui.dash_pr_view') }}
            </a>
            <button @click="prUrl = null" class="block w-full mt-3 text-sm text-gray-500 hover:text-gray-300 transition-colors">
                {{ __('ui.dash_pr_close') }}
            </button>
        </div>
    </div>

    {{-- PR Confirmation Modal --}}
    <div x-show="prConfirmOpen" x-cloak class="fixed inset-0 bg-black/70 backdrop-blur-sm flex items-center justify-center z-[9995] p-4">
        <div class="glass rounded-2xl p-8 max-w-md w-full text-center shadow-2xl border border-sky-500/30">
            <div class="text-5xl mb-4">🔀</div>
            <h2 class="text-xl font-bold text-white mb-2">{{ __('ui.dash_create_pr') }}</h2>
            <div class="bg-sky-500/10 border border-sky-500/20 rounded-xl p-4 mb-6">
                <p class="text-sky-300 text-sm leading-relaxed">
                    <strong>⚠️ Important :</strong> La génération des correctifs par l'IA et la création de la PR sur GitHub peuvent prendre <strong>plusieurs minutes</strong>. 
                </p>
                <p class="text-sky-400/80 text-xs mt-2 italic">
                    Veuillez ne pas quitter cette page ou rafraîchir le navigateur pendant l'opération.
                </p>
            </div>
            
            <div class="flex flex-col gap-3">
                <button @click="confirmCreatePR()" 
                        class="btn-primary rounded-xl px-6 py-3 font-semibold text-white w-full shadow-lg shadow-sky-500/20">
                    🚀 Lancer la création
                </button>
                <button @click="prConfirmOpen = false" 
                        class="px-6 py-3 rounded-xl border border-white/10 text-gray-400 hover:text-white hover:bg-white/5 transition-all text-sm">
                    {{ __('ui.confirm_cancel') }}
                </button>
            </div>
        </div>
    </div>

    {{-- PR Error Modal --}}
    <div x-show="prError" x-cloak class="fixed inset-0 bg-black/70 backdrop-blur-sm flex items-center justify-center z-50 p-4">
        <div class="glass rounded-2xl p-8 max-w-md w-full text-center shadow-2xl border border-red-500/30">
            <div class="text-5xl mb-4">⚠️</div>
            <h2 class="text-xl font-bold text-white mb-2">{{ __('ui.dash_pr_error') }}</h2>
            <p class="text-gray-400 text-sm mb-6 leading-relaxed whitespace-pre-line">{{ __('ui.dash_pr_error_sub') }}</p>

            <button @click="prError = null" 
                    class="btn-primary bg-gray-800 hover:bg-gray-700 rounded-xl px-6 py-3 font-semibold text-white w-full transition-colors">
                {{ __('ui.dash_pr_close') }}
            </button>
        </div>
    </div>

    {{-- ======== FULLSCREEN PDF LOADING OVERLAY ======== --}}
    <div x-show="pdfLoading" x-cloak
         class="fixed inset-0 bg-black/80 backdrop-blur-sm z-[9999] flex flex-col items-center justify-center transition-opacity"
         x-transition:enter="ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">
         
         <div class="relative w-24 h-24 mb-6">
             {{-- Animated circles --}}
             <div class="absolute inset-0 rounded-full border-t-4 border-violet-500 opacity-20 animate-[spin_3s_linear_infinite]"></div>
             <div class="absolute inset-2 rounded-full border-r-4 border-violet-400 opacity-40 animate-[spin_2s_linear_infinite_reverse]"></div>
             <div class="absolute inset-4 rounded-full border-b-4 border-violet-300 opacity-60 animate-[spin_1s_linear_infinite]"></div>
             <div class="absolute inset-0 flex items-center justify-center text-3xl">📄</div>
         </div>
         <h3 class="text-xl font-bold text-white mb-2">Génération du PDF...</h3>
         <p class="text-gray-400 text-sm max-w-sm text-center">
             Analyse des vulnérabilités et compilation du rapport. Veuillez patienter...
         </p>
    </div>

    {{-- ======== TOAST NOTIFICATION ======== --}}
    <div x-show="toastMessage" x-cloak
         class="fixed bottom-6 right-6 z-[9999] max-w-sm"
         x-transition:enter="transition ease-out duration-300 transform"
         x-transition:enter-start="translate-y-4 opacity-0"
         x-transition:enter-end="translate-y-0 opacity-100"
         x-transition:leave="transition ease-in duration-200 transform"
         x-transition:leave-start="translate-y-0 opacity-100"
         x-transition:leave-end="translate-y-4 opacity-0">
        
        <div class="glass-card rounded-xl p-4 flex items-start gap-4 shadow-2xl border-l-[6px]"
             :class="toastType === 'success' ? 'border-l-emerald-500' : 'border-l-red-500'">
            
            <div class="flex-shrink-0 mt-0.5">
                <span x-show="toastType === 'success'" class="text-emerald-400 text-xl">✅</span>
                <span x-show="toastType === 'error'" class="text-red-400 text-xl">❌</span>
            </div>
            
            <div class="flex-1">
                <h4 class="text-sm font-bold text-white mb-1" x-text="toastTitle"></h4>
                <p class="text-xs text-gray-300" x-text="toastMessage"></p>
            </div>
            
            <button @click="toastMessage = null" class="text-gray-500 hover:text-white transition-colors">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>
        </div>
    </div>

    {{-- ======== RESCAN MODAL ======== --}}
    <div x-show="rescanModalOpen" x-cloak
         class="fixed inset-0 bg-black/80 backdrop-blur-sm z-[9990] flex items-center justify-center p-4 transition-opacity"
         x-transition:enter="ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">

        <div class="glass rounded-2xl max-w-md w-full shadow-2xl overflow-hidden border border-white/10" @click.away="rescanModalOpen = false">
            <div class="px-6 py-5 border-b border-white/5 bg-white/5">
                <div class="flex items-center gap-3">
                    <span class="text-xl">🔄</span>
                    <h3 class="text-lg font-bold text-white">{{ __('ui.dash_rescan_title') }}</h3>
                </div>
            </div>
            
            <div class="px-6 py-5">
                <p class="text-sm text-gray-300">{{ __('ui.dash_rescan_confirm') }}</p>
            </div>
            
            <div class="px-6 py-4 bg-black/20 flex items-center justify-end gap-3 border-t border-white/5">
                <button @click.prevent="rescanModalOpen = false" type="button" class="px-4 py-2 rounded-xl text-sm font-medium text-gray-400 hover:text-white hover:bg-white/10 transition-colors">
                    {{ __('ui.dash_pr_close') }}
                </button>
                <form method="POST" action="{{ route('scan.rescan', $scan) }}">
                    @csrf
                    <button type="submit" class="px-5 py-2 rounded-xl text-sm font-semibold bg-green-500/20 text-green-400 border border-green-500/30 hover:bg-green-500/30 hover:border-green-500/50 transition-all">
                        {{ __('ui.dash_rescan_btn') }}
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Running Robot Knight during PR Generation -->
    <div class="fixed bottom-0 left-0 w-full h-32 pointer-events-none z-[9999] overflow-hidden"
         x-show="prLoading"
         x-transition:enter="transition ease-out duration-500"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-500"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         x-cloak>
        <img src="{{ asset('asset/walking robot knight.gif') }}" 
             alt="Running Robot" 
             class="absolute bottom-0 h-24 animate-run-across filter drop-shadow-[0_0_15px_rgba(34,197,94,0.5)]">
    </div>

</div>

    {{-- ======== FLOATING AI CHATBOT ======== --}}
    <div x-data="chatbot()" 
         @open-chat.window="openChat($event.detail)"
         class="fixed bottom-6 right-6 z-[9990] flex flex-col items-end pointer-events-none">
        
        <!-- Chat Window -->
        <div x-show="isOpen" 
             x-transition:enter="transition ease-out duration-300 transform origin-bottom-right"
             x-transition:enter-start="scale-95 opacity-0 translate-y-4"
             x-transition:enter-end="scale-100 opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-200 transform origin-bottom-right"
             x-transition:leave-start="scale-100 opacity-100 translate-y-0"
             x-transition:leave-end="scale-95 opacity-0 translate-y-4"
             class="pointer-events-auto bg-gray-900 border border-white/10 shadow-2xl rounded-2xl w-80 sm:w-96 mb-4 flex flex-col overflow-hidden"
             style="height: 650px; max-height: calc(100vh - 120px);"
             x-cloak>
             
             <!-- Header -->
             <div class="bg-gradient-to-r from-emerald-600/30 to-sky-600/30 border-b border-white/10 px-4 py-3 flex items-center justify-between">
                 <div class="flex items-center gap-2">
                     <span class="text-xl">🧠</span>
                     <h3 class="font-bold text-white text-sm">{{ __('ui.chat_title') ?? 'SecureScan Assistant' }}</h3>
                 </div>
                 <button @click="isOpen = false" class="text-gray-400 hover:text-white">
                     <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                 </button>
             </div>
             
             <!-- Messages Area -->
             <div class="flex-1 overflow-y-auto p-4 space-y-4 custom-scrollbar" id="chat-messages-container">
                 <template x-for="(msg, index) in messages" :key="index">
                     <div class="flex flex-col" :class="msg.role === 'user' ? 'items-end' : 'items-start'">
                         <div class="max-w-[85%] rounded-2xl px-4 py-2 text-sm"
                              :class="msg.role === 'user' ? 'bg-sky-600/50 text-white rounded-br-sm' : 'bg-white/10 text-gray-200 rounded-bl-sm border border-white/5'">
                             <!-- Use x-html to render simple markdown if it's the model -->
                             <span x-html="msg.role === 'model' ? formatMessage(msg.text) : msg.text"></span>
                         </div>
                     </div>
                 </template>
                 <div x-show="isLoading" class="flex items-center gap-2 text-gray-400 p-2 text-sm">
                     <svg class="animate-spin w-4 h-4 text-emerald-400" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                     <span>Gemini écrit...</span>
                 </div>
             </div>
             
             <!-- Input Area -->
             <div class="p-3 border-t border-white/10 bg-black/20">
                 <form @submit.prevent="sendMessage" class="flex items-center gap-2">
                     <input type="text" x-model="input" placeholder="{{ __('ui.chat_placeholder') ?? 'Poser une question...' }}"
                            class="flex-1 bg-white/5 border border-white/10 rounded-xl px-4 py-2 text-sm text-white focus:outline-none focus:border-emerald-500/50 focus:ring-1 focus:ring-emerald-500/50 transition-all placeholder-gray-500"
                            :disabled="isLoading" />
                     <button type="submit" :disabled="isLoading || !input.trim()"
                             class="p-2 rounded-xl bg-emerald-500 text-white hover:bg-emerald-600 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                         <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" /></svg>
                     </button>
                 </form>
             </div>
        </div>
        
        <!-- Toggle Button -->
        <button @click="isOpen = !isOpen" 
                class="pointer-events-auto bg-emerald-600 hover:bg-emerald-500 text-white rounded-full p-4 shadow-xl border border-white/10 transition-transform hover:scale-105 active:scale-95 flex items-center justify-center">
            <svg x-show="!isOpen" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" /></svg>
            <svg x-show="isOpen" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
        </button>
    </div>

@endsection

@push('scripts')
<script>
function dashboard(total) {
    return {
        selected: [],
        filterSeverity: 'all',
        filterTool: 'all',
        filterExplained: false,
        filterOwasp: 'all',
        filterRevision: 0,  // bumped on real-time explanation save/clear to force x-show re-eval
        prLoading: false,
        pdfLoading: false,
        fixBatchLoading: false,
        rescanModalOpen: false,
        prConfirmOpen: false,
        prApiUrl: null,
        prUrl: null,
        prError: null,
        toastMessage: null,
        toastTitle: null,
        toastType: 'success',
        filteredCount: total,
        vulnDetail: {
            open: false,
            fixLoading: false,
            id: null,
            severity: null,
            severityColor: null,
            severityEmoji: null,
            owaspCategory: null,
            owaspLabel: null,
            tool: null,
            filePath: null,
            lineStart: null,
            message: null,
            aiFix: null,
            codeSnippet: null,
            explanation: null,
        },
        init() {
            this.$watch('filterSeverity', () => this.updateCount());
            this.$watch('filterTool',     () => this.updateCount());
            this.$watch('filterExplained', () => this.updateCount());
            this.$watch('filterOwasp',    () => this.updateCount());
            // Listen for real-time explanation changes to keep the filter accurate and update active detail
            window.addEventListener('explanation-saved', (e) => { 
                this.filterRevision++; 
                if (this.vulnDetail.id === e.detail.id) {
                    this.vulnDetail.explanation = e.detail.text;
                }
            });
            window.addEventListener('explanation-cleared', () => { this.filterRevision++; });
            // Listen for save-fix events dispatched by the editable fix textarea
            window.addEventListener('save-fix', (e) => this.saveFix(e.detail.id, e.detail.fix));
        },

        toggleSelect(id) {
            const idx = this.selected.indexOf(id);
            if (idx === -1) this.selected.push(id);
            else this.selected.splice(idx, 1);
        },

        async openDetail(id, url) {
            this.vulnDetail.open = true;
            this.vulnDetail.fixLoading = true;
            this.vulnDetail.aiFix = null;
            this.vulnDetail.codeSnippet = null;
            this.vulnDetail.explanation = null; // Fix: Reset previous explanation
            this.vulnDetail.id = id;
            this.vulnDetail.message = null;
            try {
                const res = await fetch(url, {
                    headers: { 'Accept': 'application/json' }
                });
                const data = await res.json();
                this.vulnDetail.severity      = data.severity;
                this.vulnDetail.severityColor  = data.severity_color;
                this.vulnDetail.severityEmoji  = data.severity_emoji;
                this.vulnDetail.owaspCategory  = data.owasp_category;
                this.vulnDetail.owaspLabel     = data.owasp_label;
                this.vulnDetail.tool           = data.tool;
                this.vulnDetail.filePath       = data.file_path;
                this.vulnDetail.lineStart      = data.line_start;
                this.vulnDetail.message        = data.message;
                this.vulnDetail.codeSnippet    = data.code_snippet || null;
                this.vulnDetail.aiFix          = data.ai_fix || data.fix_suggestion || 'No fix available.';
                this.vulnDetail.explanation    = data.chat_explanation || null;
            } catch(e) {
                this.vulnDetail.aiFix = 'Failed to load fix: ' + e.message;
            } finally {
                this.vulnDetail.fixLoading = false;
            }
        },

        matchesFilter(severity, tool, explainedAtBoot, id) {
            // filterRevision is referenced so Alpine re-evaluates this when it changes
            void this.filterRevision;
            const sevOk  = this.filterSeverity === 'all' || this.filterSeverity === severity;
            const toolOk = this.filterTool === 'all' || this.filterTool === tool;
            // Read real-time state from DOM data attribute when filterExplained is active
            let explained = explainedAtBoot;
            let owasp = '';
            if (id !== undefined) {
                const el = document.querySelector(`#vuln-list > div[data-id="${id}"]`);
                if (el) {
                    if (this.filterExplained) explained = el.dataset.explained === '1';
                    owasp = el.dataset.owasp || '';
                }
            }
            const expOk   = !this.filterExplained || explained === true;
            const owaspOk = this.filterOwasp === 'all' || this.filterOwasp === owasp;
            return sevOk && toolOk && expOk && owaspOk;
        },

        updateCount() {
            this.$nextTick(() => {
                this.filteredCount = document.querySelectorAll('#vuln-list > div[x-show]')
                    .length;
            });
        },

        visibleIds() {
            return [...document.querySelectorAll('#vuln-list > div[data-id]')]
                .filter(el => el.style.display !== 'none')
                .map(el => parseInt(el.dataset.id))
                .filter(id => !isNaN(id));
        },

        isAllVisibleSelected() {
            const visible = this.visibleIds();
            return visible.length > 0 && visible.every(id => this.selected.includes(id));
        },

        toggleSelectAllVisible() {
            const visible = this.visibleIds();
            if (this.isAllVisibleSelected()) {
                // Deselect all visible
                this.selected = this.selected.filter(id => !visible.includes(id));
            } else {
                // Select all visible (keep already selected ones from other filters)
                visible.forEach(id => {
                    if (!this.selected.includes(id)) this.selected.push(id);
                });
            }
        },

        createPR(url) {
            if (this.selected.length === 0) return;
            this.prApiUrl = url;
            this.prConfirmOpen = true;
        },

        async confirmCreatePR() {
            this.prConfirmOpen = false;
            this.prLoading = true;
            this.prError = null;
            try {
                const res = await fetch(this.prApiUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({ vulnerability_ids: this.selected }),
                });
                const data = await res.json();
                if (data.success) {
                    this.prUrl = data.pr_url;
                } else if (res.status === 401 || res.status === 403) {
                    this.prError = 'Authentification requise. Veuillez vous reconnecter.';
                } else {
                    this.prError = data.error || data.message || 'Une erreur inconnue est survenue.';
                }
            } catch (e) {
                this.prError = 'Échec de la connexion au serveur : ' + e.message;
            } finally {
                this.prLoading = false;
            }
        },

        async generateFixes(url) {
            if (this.selected.length === 0) return;
            this.fixBatchLoading = true;
            try {
                const res = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({ vulnerability_ids: this.selected }),
                });
                const data = await res.json();
                if (data.success) {
                    this.showToast('{{ __('ui.dash_fix_success_title') }}', data.message || '{{ __('ui.dash_fix_success_msg') }}', 'success');
                } else {
                    this.showToast('{{ __('ui.dash_fix_error_title') }}', data.error || data.message || 'Une erreur est survenue.', 'error');
                }
            } catch (e) {
                this.showToast('{{ __('ui.dash_fix_error_conn') }}', e.message, 'error');
            } finally {
                this.fixBatchLoading = false;
            }
        },

        showToast(title, message, type = 'success') {
            this.toastTitle = title;
            this.toastMessage = message;
            this.toastType = type;
            setTimeout(() => { this.toastMessage = null; }, 5000);
        },

        async exportPdf(url) {
            this.pdfLoading = true;
            try {
                // To display an overlay while the browser downloads the PDF,
                // we fetch the blob manually and create an object URL.
                const res = await fetch(url, { headers: { 'Accept': 'application/pdf' } });
                if (!res.ok) throw new Error('Download failed');
                
                const blob = await res.blob();
                const downloadUrl = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = downloadUrl;
                a.download = `securescan-report-{{ $scan->id }}.pdf`;
                document.body.appendChild(a);
                a.click();
                a.remove();
                window.URL.revokeObjectURL(downloadUrl);
            } catch (e) {
                console.error('PDF Export Error:', e);
                alert('An error occurred while generating the PDF.');
            } finally {
                this.pdfLoading = false;
            }
        },

        async saveFix(id, fixText) {
            try {
                const url = '{{ route("vulnerability.save-fix", ["vulnerability" => "__ID__"]) }}'.replace('__ID__', id);
                const res = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({ ai_fix: fixText }),
                });
                const data = await res.json();
                if (data.success) {
                    this.showToast('✅ Fix enregistré', 'Le correctif a été sauvegardé avec succès.', 'success');
                } else {
                    this.showToast('Erreur', data.error || 'Impossible de sauvegarder le fix.', 'error');
                }
            } catch (e) {
                this.showToast('Erreur réseau', e.message, 'error');
            } finally {
                // Reset fixSaving in the nested x-data — dispatch a custom event
                window.dispatchEvent(new CustomEvent('fix-saved'));
            }
        },

        async clearExplanation(id) {
            try {
                const res = await fetch(`/vulnerability/${id}/explanation`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    }
                });
                if (!res.ok) {
                    const err = await res.json().catch(() => ({}));
                    throw new Error(err.message || `HTTP ${res.status}`);
                }
                const data = await res.json();
                if (data.success) {
                    this.vulnDetail.explanation = null;
                    window.dispatchEvent(new CustomEvent('explanation-cleared', { detail: { id: id } }));
                    this.showToast('{{ __('ui.dash_fix_success_title') }}', '{{ __('ui.dash_explanation_cleared') }}', 'success');
                }
            } catch (e) {
                console.error('clearExplanation error:', e);
                this.showToast('{{ __('ui.dash_fix_error_title') }}', e.message || '{{ __('ui.dash_explanation_clear_error') }}', 'error');
            }
        },

        formatMessage(text) {
            if (!text) return '';
            // Basic markdown parsing
            let formatted = text
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;").replace(/>/g, "&gt;") // Escape HTML
                .replace(/```([\s\S]*?)```/g, '<pre class="bg-black/50 p-2 rounded-lg my-2 font-mono text-xs overflow-x-auto text-amber-300 border border-white/5">$1</pre>') // Code blocks
                .replace(/`([^`]+)`/g, '<code class="bg-white/10 px-1 rounded text-emerald-300 font-mono text-xs">$1</code>') // Inline code
                .replace(/\*\*([^*]+)\*\*/g, '<strong class="text-white">$1</strong>') // Bold
                .replace(/\*([^*]+)\*/g, '<em class="italic text-gray-300">$1</em>') // Italic
                .replace(/\n/g, '<br>'); // Newlines
            
            return formatted;
        },

        initCharts(bySeverity, byOwasp) {
            const i18nSev = {
                critical: "{{ __('ui.sev_critical') }}",
                high: "{{ __('ui.sev_high') }}",
                medium: "{{ __('ui.sev_medium') }}",
                low: "{{ __('ui.sev_low') }}",
                info: "{{ __('ui.sev_info') }}"
            };

            // Severity donut chart
            const severityCtx = document.getElementById('severityChart').getContext('2d');
            const severityColors = {
                critical: '#ef4444', high: '#f97316', medium: '#eab308',
                low: '#3b82f6', info: '#6b7280'
            };
            const sevLabels = Object.keys(bySeverity);
            const sevData   = Object.values(bySeverity);
            new Chart(severityCtx, {
                type: 'doughnut',
                data: {
                    labels: sevLabels.map(l => i18nSev[l] || (l.charAt(0).toUpperCase() + l.slice(1))),
                    datasets: [{ data: sevData, backgroundColor: sevLabels.map(l => severityColors[l] || '#6b7280'), borderWidth: 0, hoverOffset: 6 }]
                },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    plugins: { legend: { position: 'right', labels: { color: '#9ca3af', padding: 16, font: { size: 12 } } } },
                    cutout: '70%',
                }
            });

            // OWASP horizontal bar chart
            const owaspCtx = document.getElementById('owaspChart').getContext('2d');
            const owaspLabels = Object.keys(byOwasp);
            const owaspData   = Object.values(byOwasp);
            new Chart(owaspCtx, {
                type: 'bar',
                data: {
                    labels: owaspLabels,
                    datasets: [{
                        label: 'Vulnerabilities',
                        data: owaspData,
                        backgroundColor: owaspLabels.map((_, i) => `hsla(${120 + i * 10}, 80%, 60%, 0.7)`),
                        borderRadius: 6,
                    }]
                },
                options: {
                    responsive: true, maintainAspectRatio: false, indexAxis: 'y',
                    plugins: { legend: { display: false } },
                    scales: {
                        x: { grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { color: '#6b7280' } },
                        y: { grid: { display: false }, ticks: { color: '#9ca3af', font: { family: 'JetBrains Mono', size: 11 } } }
                    }
                }
            });
        }
    }
}

function chatbot() {
    return {
        isOpen: false,
        isLoading: false,
        input: '',
        messages: [],
        vulnCtx: null,
        
        openChat(detail) {
            this.isOpen = true;

            // Reset conversation when switching to a DIFFERENT vulnerability
            if (this.vulnCtx !== detail.id) {
                this.messages = [];
                this.input = '';
            }

            this.vulnCtx = detail.id;
            
            // If already has cached explanation, push it to local messages (no API call)
            if (this.messages.length === 0 && detail.explanation) {
                this.messages.push({ role: 'user', text: `Peux-tu m'expliquer cette faille ${detail.tool} ?` });
                this.messages.push({ role: 'model', text: detail.explanation });
                this.$nextTick(() => this.scrollToBottom());
                return;
            }

            // Initiate context message if chat is empty and no cache
            if (this.messages.length === 0) {
                const promptMsg = `Peux-tu m'expliquer cette faille ${detail.tool} trouvée dans mon code ?\nMessage: ${detail.message}`;
                this.input = promptMsg;
                this.sendMessage();
            }
        },
        
        async sendMessage() {
            if (!this.input.trim() || this.isLoading) return;
            
            const userText = this.input.trim();
            this.input = '';
            
            // Append user message
            this.messages.push({ role: 'user', text: userText });
            this.scrollToBottom();
            
            this.isLoading = true;
            
            try {
                const historyToSend = this.messages.slice(0, -1); // send previous history
                
                const res = await fetch('{{ route("chat.message") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({
                        message: userText,
                        history: historyToSend,
                        vulnerability_id: this.vulnCtx
                    })
                });
                
                const data = await res.json();
                if (data.success && data.reply) {
                    this.messages.push({ role: 'model', text: data.reply });
                    // If this was the first message (history had 1 entry = the initial question),
                    // dispatch event so the card button updates to '✅ Expliqué' in real-time
                    if (historyToSend.length === 0 && this.vulnCtx) {
                        window.dispatchEvent(new CustomEvent('explanation-saved', { 
                            detail: { id: this.vulnCtx, text: data.reply } 
                        }));
                    }
                } else {
                    this.messages.push({ role: 'model', text: "⚠️ Une erreur est survenue lors de la réponse de l'IA." });
                }
            } catch (e) {
                this.messages.push({ role: 'model', text: "❌ Erreur de réseau : impossible de joindre le serveur." });
            } finally {
                this.isLoading = false;
                this.scrollToBottom();
            }
        },
        
        scrollToBottom() {
            setTimeout(() => {
                const container = document.getElementById('chat-messages-container');
                if (container) {
                    container.scrollTop = container.scrollHeight;
                }
            }, 50);
        },
        
        formatMessage(text) {
            if (!text) return '';
            // Basic markdown parsing for the chatbot (code blocks, bold, italics)
            let formatted = text
                .replace(/</g, "&lt;").replace(/>/g, "&gt;") // Escape HTML
                .replace(/```([\s\S]*?)```/g, '<pre class="bg-black/50 p-2 rounded-lg my-2 font-mono text-xs overflow-x-auto text-amber-300 border border-white/5">$1</pre>') // Code blocks
                .replace(/`([^`]+)`/g, '<code class="bg-white/10 px-1 rounded text-emerald-300 font-mono text-xs">$1</code>') // Inline code
                .replace(/\*\*([^*]+)\*\*/g, '<strong class="text-white">$1</strong>') // Bold
                .replace(/\*([^*]+)\*/g, '<em class="italic text-gray-300">$1</em>') // Italic
                .replace(/\n/g, '<br>'); // Newlines
            
            return formatted;
        }
    }
}
</script>
@endpush
