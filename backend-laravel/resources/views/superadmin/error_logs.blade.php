@extends('layouts.superadmin')

@section('content')
<div class="space-y-8" x-data="{ expanded: null, filterLevel: 'all', search: '' }">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="text-[10px] font-bold text-[#C0422A] uppercase tracking-widest mb-1">Developer Diagnostic Tools</div>
            <h1 class="font-serif text-3xl font-bold text-[#3D2B1F]">System <span class="text-[#C0422A] italic">Error Logs</span></h1>
            <p class="text-xs text-gray-500 mt-1">Real-time inspection of <code class="font-mono text-[11px] bg-gray-100 px-1 py-0.5 rounded">storage/logs/laravel.log</code> with stack traces.</p>
        </div>

        <div class="flex items-center gap-3">
            <span class="text-xs text-gray-400 font-mono">Log File Size: <strong>{{ $logSize }}</strong></span>
            <form action="{{ route('superadmin.error-logs.clear') }}" method="POST" onsubmit="return confirm('Are you sure you want to clear the system log file?');">
                @csrf
                <button type="submit" class="px-4 py-2.5 bg-red-50 hover:bg-red-100 text-red-600 border border-red-200 rounded-xl text-xs font-bold uppercase tracking-wider transition-all cursor-pointer shadow-xs">
                    Clear Log File
                </button>
            </form>
        </div>
    </div>

    <!-- Filter & Search Controls -->
    <div class="bg-white border border-[#E5DDD5] rounded-3xl p-6 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div class="flex items-center gap-2 flex-wrap">
            <button type="button" @click="filterLevel = 'all'"
                :class="filterLevel === 'all' ? 'bg-[#3D2B1F] text-white' : 'bg-[#F7F3EE] text-gray-600 hover:bg-[#E5DDD5]'"
                class="px-4 py-2 rounded-xl text-xs font-bold uppercase tracking-wider transition-all cursor-pointer">
                All Logs ({{ count($entries) }})
            </button>
            <button type="button" @click="filterLevel = 'ERROR'"
                :class="filterLevel === 'ERROR' ? 'bg-red-600 text-white' : 'bg-red-50 text-red-700 hover:bg-red-100'"
                class="px-4 py-2 rounded-xl text-xs font-bold uppercase tracking-wider transition-all cursor-pointer">
                Errors &amp; Exceptions
            </button>
            <button type="button" @click="filterLevel = 'WARNING'"
                :class="filterLevel === 'WARNING' ? 'bg-amber-600 text-white' : 'bg-amber-50 text-amber-700 hover:bg-amber-100'"
                class="px-4 py-2 rounded-xl text-xs font-bold uppercase tracking-wider transition-all cursor-pointer">
                Warnings
            </button>
            <button type="button" @click="filterLevel = 'INFO'"
                :class="filterLevel === 'INFO' ? 'bg-blue-600 text-white' : 'bg-blue-50 text-blue-700 hover:bg-blue-100'"
                class="px-4 py-2 rounded-xl text-xs font-bold uppercase tracking-wider transition-all cursor-pointer">
                Info &amp; Debug
            </button>
        </div>

        <div class="relative">
            <input type="text" x-model="search" placeholder="Filter log text..."
                class="w-64 pl-9 pr-4 py-2 bg-[#FAF7F2] border border-[#EBE3D9] text-[#3D2B1F] text-xs rounded-xl focus:outline-none focus:border-[#C0422A] transition-colors">
            <svg class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
        </div>
    </div>

    <!-- Logs List -->
    <div class="space-y-3">
        @forelse($entries as $index => $entry)
        <div x-show="(filterLevel === 'all' || '{{ $entry['level'] }}'.includes(filterLevel)) && (!search || '{{ strtolower(addslashes($entry['full_text'])) }}'.includes(search.toLowerCase()))"
             class="bg-white border border-[#E5DDD5] rounded-2xl overflow-hidden shadow-xs transition-all">
            
            <div class="p-4 flex items-start justify-between gap-4 cursor-pointer hover:bg-[#FAF7F2]"
                 @click="expanded === {{ $index }} ? expanded = null : expanded = {{ $index }}">
                <div class="space-y-1 flex-1">
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="font-mono text-gray-400 text-[11px]">{{ $entry['timestamp'] }}</span>
                        
                        @if(in_array($entry['level'], ['ERROR', 'CRITICAL', 'ALERT', 'EMERGENCY']))
                            <span class="px-2 py-0.5 bg-red-50 text-red-600 border border-red-200 rounded font-bold text-[9px] uppercase tracking-wider font-mono">
                                {{ $entry['level'] }}
                            </span>
                        @elseif($entry['level'] === 'WARNING')
                            <span class="px-2 py-0.5 bg-amber-50 text-amber-700 border border-amber-200 rounded font-bold text-[9px] uppercase tracking-wider font-mono">
                                {{ $entry['level'] }}
                            </span>
                        @else
                            <span class="px-2 py-0.5 bg-blue-50 text-blue-700 border border-blue-200 rounded font-bold text-[9px] uppercase tracking-wider font-mono">
                                {{ $entry['level'] }}
                            </span>
                        @endif

                        <span class="text-[10px] text-gray-400 font-mono">env: {{ $entry['environment'] }}</span>
                    </div>

                    <div class="text-xs font-bold text-[#3D2B1F] font-mono leading-relaxed break-all">
                        {{ $entry['message'] }}
                    </div>
                </div>

                <button type="button" class="p-1 text-gray-400 hover:text-black">
                    <svg class="w-4 h-4 transform transition-transform" :class="expanded === {{ $index }} ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
            </div>

            {{-- Full Stack Trace Accordion --}}
            <div x-show="expanded === {{ $index }}" x-collapse x-cloak class="p-4 bg-[#141414] text-gray-300 font-mono text-[11px] border-t border-gray-800 overflow-x-auto leading-relaxed max-h-96">
                <pre class="whitespace-pre-wrap">{{ $entry['full_text'] }}</pre>
            </div>
        </div>
        @empty
        <div class="bg-white border border-[#E5DDD5] rounded-3xl p-12 text-center shadow-sm">
            <div class="w-12 h-12 bg-green-50 rounded-full flex items-center justify-center mx-auto mb-3 border border-green-100">
                <span class="text-green-600 text-lg">✓</span>
            </div>
            <h3 class="text-sm font-bold text-[#3D2B1F] uppercase tracking-widest mb-1">No System Errors Recorded</h3>
            <p class="text-xs text-gray-500">The Laravel error log is currently clean and operating smoothly.</p>
        </div>
        @endforelse
    </div>
</div>
@endsection
