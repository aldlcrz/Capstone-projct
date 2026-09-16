@extends('layouts.superadmin')

@section('content')
<div class="space-y-6" x-data="errorLogManager()">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="text-[10px] font-bold text-[#C0422A] uppercase tracking-[0.2em] mb-1">Developer Diagnostic Tools</div>
            <h1 class="font-serif text-2xl sm:text-3xl font-bold text-[#3D2B1F]">
                System <span class="text-[#C0422A] italic">Error Logs</span>
            </h1>
            <p class="text-xs text-gray-500 mt-1">Real-time inspection of <code class="font-mono text-[11px] bg-gray-100 text-gray-700 px-1.5 py-0.5 rounded-md border border-gray-200">storage/logs/laravel.log</code> with interactive stack traces.</p>
        </div>

        <div class="flex items-center gap-3">
            <span class="text-xs text-gray-500 font-mono bg-white px-3 py-2 rounded-xl border border-[#E5DDD5]">Log Size: <strong class="text-gray-900">{{ $logSize }}</strong></span>
            <form action="{{ route('superadmin.error-logs.clear') }}" method="POST" onsubmit="return confirm('Are you sure you want to clear the system log file?');">
                @csrf
                <button type="submit" class="px-4 py-2 bg-red-50 hover:bg-red-100 text-red-600 border border-red-200 rounded-xl text-xs font-bold uppercase tracking-wider transition-all cursor-pointer shadow-xs">
                    Clear Log File
                </button>
            </form>
        </div>
    </div>

    <!-- Filter & Search Controls -->
    <div class="flex items-center gap-2 overflow-x-auto no-scrollbar pb-1">
        <button type="button" @click="filterLevel = 'all'"
            :class="filterLevel === 'all' ? 'bg-[#3D2B1F] text-white shadow-sm' : 'bg-white border border-[#E5DDD5] text-gray-600 hover:border-gray-400'"
            class="px-4 py-2 rounded-xl text-xs font-bold transition-all shrink-0 flex items-center gap-2 cursor-pointer">
            <span>All Logs</span>
            <span class="px-1.5 py-0.5 rounded-full text-[10px]" :class="filterLevel === 'all' ? 'bg-[#C0422A] text-white' : 'bg-gray-100 text-gray-600 font-black'">{{ count($entries) }}</span>
        </button>
        <button type="button" @click="filterLevel = 'ERROR'"
            :class="filterLevel === 'ERROR' ? 'bg-[#3D2B1F] text-white shadow-sm' : 'bg-white border border-[#E5DDD5] text-gray-600 hover:border-gray-400'"
            class="px-4 py-2 rounded-xl text-xs font-bold transition-all shrink-0 flex items-center gap-2 cursor-pointer">
            <span class="text-red-500 font-black">●</span>
            <span>Errors &amp; Exceptions</span>
            <span class="px-1.5 py-0.5 rounded-full text-[10px]" :class="filterLevel === 'ERROR' ? 'bg-red-600 text-white' : 'bg-red-50 text-red-700 font-black'">{{ $errorCount }}</span>
        </button>
        <button type="button" @click="filterLevel = 'WARNING'"
            :class="filterLevel === 'WARNING' ? 'bg-[#3D2B1F] text-white shadow-sm' : 'bg-white border border-[#E5DDD5] text-gray-600 hover:border-gray-400'"
            class="px-4 py-2 rounded-xl text-xs font-bold transition-all shrink-0 flex items-center gap-2 cursor-pointer">
            <span class="text-amber-500 font-black">●</span>
            <span>Warnings</span>
            <span class="px-1.5 py-0.5 rounded-full text-[10px]" :class="filterLevel === 'WARNING' ? 'bg-amber-600 text-white' : 'bg-amber-50 text-amber-700 font-black'">{{ $warningCount }}</span>
        </button>
        <button type="button" @click="filterLevel = 'INFO'"
            :class="filterLevel === 'INFO' ? 'bg-[#3D2B1F] text-white shadow-sm' : 'bg-white border border-[#E5DDD5] text-gray-600 hover:border-gray-400'"
            class="px-4 py-2 rounded-xl text-xs font-bold transition-all shrink-0 flex items-center gap-2 cursor-pointer">
            <span class="text-blue-500 font-black">●</span>
            <span>Info &amp; Debug</span>
            <span class="px-1.5 py-0.5 rounded-full text-[10px]" :class="filterLevel === 'INFO' ? 'bg-blue-600 text-white' : 'bg-blue-50 text-blue-700 font-black'">{{ $infoCount }}</span>
        </button>
    </div>

    <!-- Live Search Input -->
    <div class="bg-white border border-[#E5DDD5] rounded-2xl p-3 sm:p-4 shadow-xs">
        <div class="relative w-full">
            <svg class="w-4 h-4 text-gray-400 absolute left-3.5 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            <input type="text" x-model="search" placeholder="Search exception message, class name, or timestamp..."
                class="w-full pl-10 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-xs font-medium focus:bg-white focus:outline-none focus:border-[#C0422A] transition-all">
        </div>
    </div>

    <!-- Logs List Rendered via Alpine Component -->
    <div class="space-y-3">
        <template x-for="(entry, index) in filteredEntries" :key="index">
            <div class="bg-white border border-[#E5DDD5] rounded-2xl overflow-hidden shadow-xs transition-all">
                <div class="p-4 sm:p-5 flex items-start justify-between gap-4 cursor-pointer hover:bg-[#F7F3EE]/50 transition-colors"
                     @click="expanded === index ? expanded = null : expanded = index">
                    <div class="space-y-1.5 flex-1 min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="font-mono text-gray-500 text-[11px]" x-text="entry.timestamp"></span>
                            
                            <template x-if="['ERROR', 'CRITICAL', 'ALERT', 'EMERGENCY'].includes(entry.level)">
                                <span class="px-2 py-0.5 bg-red-50 text-red-700 border border-red-200 rounded-full font-black text-[9px] uppercase tracking-wider font-mono" x-text="entry.level"></span>
                            </template>

                            <template x-if="entry.level === 'WARNING'">
                                <span class="px-2 py-0.5 bg-amber-50 text-amber-700 border border-amber-200 rounded-full font-black text-[9px] uppercase tracking-wider font-mono" x-text="entry.level"></span>
                            </template>

                            <template x-if="!['ERROR', 'CRITICAL', 'ALERT', 'EMERGENCY', 'WARNING'].includes(entry.level)">
                                <span class="px-2 py-0.5 bg-blue-50 text-blue-700 border border-blue-200 rounded-full font-black text-[9px] uppercase tracking-wider font-mono" x-text="entry.level"></span>
                            </template>

                            <span class="text-[10px] text-gray-400 font-mono" x-text="'env: ' + entry.environment"></span>
                        </div>

                        <div class="text-xs font-bold text-gray-900 font-mono leading-relaxed break-all" x-text="entry.message"></div>
                    </div>

                    <div class="shrink-0 flex items-center gap-1.5 text-xs text-gray-400 font-medium pt-1">
                        <span class="hidden sm:inline" x-text="expanded === index ? 'Collapse' : 'Inspect Trace'"></span>
                        <svg class="w-4 h-4 transform transition-transform text-gray-500" :class="expanded === index ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </div>
                </div>

                {{-- Full Stack Trace --}}
                <div x-show="expanded === index" x-collapse x-cloak class="p-5 bg-[#1C1A17] text-[#E5DDD5] font-mono text-[11px] border-t border-gray-800 overflow-x-auto leading-relaxed max-h-96">
                    <pre class="whitespace-pre-wrap select-all font-mono" x-text="entry.full_text"></pre>
                </div>
            </div>
        </template>

        <div x-show="filteredEntries.length === 0" class="bg-white border border-[#E5DDD5] rounded-3xl p-12 text-center shadow-xs" x-cloak>
            <div class="w-14 h-14 bg-emerald-50 rounded-2xl flex items-center justify-center mx-auto mb-3 border border-emerald-100">
                <svg class="w-7 h-7 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            </div>
            <h3 class="text-sm font-bold text-gray-900 mb-1">No Entries Match Filter</h3>
            <p class="text-xs text-gray-400">No error log records match your selected log level or search query.</p>
    <script id="error-logs-json" type="application/json">
        {!! json_encode($entries) !!}
    </script>
</div>

<script>
function errorLogManager() {
    return {
        expanded: null,
        filterLevel: 'all',
        search: '',
        entries: JSON.parse(document.getElementById('error-logs-json').textContent || '[]'),
        get filteredEntries() {
            var lvl = this.filterLevel;
            var q = this.search.toLowerCase().trim();
            return this.entries.filter(function(e) {
                var levelMatch = false;
                if (lvl === 'all') {
                    levelMatch = true;
                } else if (lvl === 'ERROR') {
                    levelMatch = ['ERROR', 'CRITICAL', 'ALERT', 'EMERGENCY'].includes(e.level);
                } else if (lvl === 'WARNING') {
                    levelMatch = (e.level === 'WARNING');
                } else if (lvl === 'INFO') {
                    levelMatch = !['ERROR', 'CRITICAL', 'ALERT', 'EMERGENCY', 'WARNING'].includes(e.level);
                }

                var textMatch = true;
                if (q) {
                    textMatch = (e.message && e.message.toLowerCase().includes(q)) ||
                                (e.full_text && e.full_text.toLowerCase().includes(q)) ||
                                (e.timestamp && e.timestamp.includes(q));
                }

                return levelMatch && textMatch;
            });
        }
    };
}
</script>
@endsection
