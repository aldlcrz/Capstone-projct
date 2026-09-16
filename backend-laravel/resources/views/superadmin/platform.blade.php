@extends('layouts.superadmin')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="text-[10px] font-bold text-[#C0422A] uppercase tracking-[0.2em] mb-1">Infrastructure &amp; Server Diagnostics</div>
            <h1 class="font-serif text-2xl sm:text-3xl font-bold text-[#3D2B1F]">
                Server &amp; <span class="text-[#C0422A] italic">Platform Info</span>
            </h1>
            <p class="text-xs text-gray-500 mt-1">Technical specifications of the hosting server environment, runtime extensions, and database architecture.</p>
        </div>
    </div>

    <!-- Specifications Grid -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Application Architecture -->
        <div class="bg-white border border-[#E5DDD5] rounded-3xl p-6 shadow-xs space-y-4">
            <div class="border-b border-gray-100 pb-3">
                <span class="text-[10px] font-black uppercase tracking-widest text-[#C0422A]">App Environment</span>
                <h3 class="font-serif text-base font-bold text-[#3D2B1F]">Laravel Framework</h3>
            </div>
            <dl class="divide-y divide-gray-100 text-xs">
                <div class="py-2.5 flex justify-between">
                    <dt class="text-gray-500 font-medium">Framework</dt>
                    <dd class="font-bold text-gray-900 font-mono">Laravel v{{ $info['app']['version'] }}</dd>
                </div>
                <div class="py-2.5 flex justify-between">
                    <dt class="text-gray-500 font-medium">Environment</dt>
                    <dd class="font-bold font-mono {{ $info['app']['environment'] === 'production' ? 'text-emerald-600' : 'text-amber-600' }}">
                        {{ strtoupper($info['app']['environment']) }}
                    </dd>
                </div>
                <div class="py-2.5 flex justify-between">
                    <dt class="text-gray-500 font-medium">Debug Mode</dt>
                    <dd class="font-bold font-mono {{ $info['app']['debug'] === 'Enabled' ? 'text-amber-600' : 'text-emerald-600' }}">
                        {{ $info['app']['debug'] }}
                    </dd>
                </div>
                <div class="py-2.5 flex justify-between">
                    <dt class="text-gray-500 font-medium">Timezone</dt>
                    <dd class="font-bold text-gray-900 font-mono">{{ $info['app']['timezone'] }}</dd>
                </div>
                <div class="py-2.5 flex justify-between">
                    <dt class="text-gray-500 font-medium">Locale</dt>
                    <dd class="font-bold text-gray-900 font-mono">{{ $info['app']['locale'] }}</dd>
                </div>
            </dl>
        </div>

        <!-- PHP Runtime Environment -->
        <div class="bg-white border border-[#E5DDD5] rounded-3xl p-6 shadow-xs space-y-4">
            <div class="border-b border-gray-100 pb-3">
                <span class="text-[10px] font-black uppercase tracking-widest text-[#C0422A]">Engine Runtime</span>
                <h3 class="font-serif text-base font-bold text-[#3D2B1F]">PHP Runtime</h3>
            </div>
            <dl class="divide-y divide-gray-100 text-xs">
                <div class="py-2.5 flex justify-between">
                    <dt class="text-gray-500 font-medium">PHP Version</dt>
                    <dd class="font-bold text-gray-900 font-mono">{{ $info['php']['version'] }}</dd>
                </div>
                <div class="py-2.5 flex justify-between">
                    <dt class="text-gray-500 font-medium">Host Operating System</dt>
                    <dd class="font-bold text-gray-900 font-mono truncate max-w-35 text-right">{{ $info['php']['os'] }}</dd>
                </div>
                <div class="py-2.5 flex justify-between">
                    <dt class="text-gray-500 font-medium">Memory Allocation</dt>
                    <dd class="font-bold text-gray-900 font-mono">{{ $info['php']['memory_limit'] }}</dd>
                </div>
                <div class="py-2.5 flex justify-between">
                    <dt class="text-gray-500 font-medium">Max Execution Time</dt>
                    <dd class="font-bold text-gray-900 font-mono">{{ $info['php']['max_execution_time'] }}</dd>
                </div>
                <div class="py-2.5 flex justify-between">
                    <dt class="text-gray-500 font-medium">Upload Limit</dt>
                    <dd class="font-bold text-gray-900 font-mono">{{ $info['php']['upload_max_filesize'] }}</dd>
                </div>
            </dl>
        </div>

        <!-- Database Engine -->
        <div class="bg-white border border-[#E5DDD5] rounded-3xl p-6 shadow-xs space-y-4">
            <div class="border-b border-gray-100 pb-3">
                <span class="text-[10px] font-black uppercase tracking-widest text-[#C0422A]">Data Persistence</span>
                <h3 class="font-serif text-base font-bold text-[#3D2B1F]">Database Engine</h3>
            </div>
            <dl class="divide-y divide-gray-100 text-xs">
                <div class="py-2.5 flex justify-between">
                    <dt class="text-gray-500 font-medium">Driver</dt>
                    <dd class="font-bold text-gray-900 font-mono uppercase">{{ $info['database']['driver'] }}</dd>
                </div>
                <div class="py-2.5 flex justify-between">
                    <dt class="text-gray-500 font-medium">Engine Version</dt>
                    <dd class="font-bold text-gray-900 font-mono">{{ Str::limit($info['database']['version'], 18) }}</dd>
                </div>
                <div class="py-2.5 flex justify-between">
                    <dt class="text-gray-500 font-medium">Database Name</dt>
                    <dd class="font-bold text-gray-900 font-mono">{{ $info['database']['database'] }}</dd>
                </div>
                <div class="py-2.5 flex justify-between">
                    <dt class="text-gray-500 font-medium">Total Size on Disk</dt>
                    <dd class="font-bold text-[#C0422A] font-mono">{{ $info['stats']['db_size'] }}</dd>
                </div>
                <div class="py-2.5 flex justify-between">
                    <dt class="text-gray-500 font-medium">Gross Platform Volume</dt>
                    <dd class="font-bold text-emerald-700 font-mono">{{ $info['stats']['total_revenue'] }}</dd>
                </div>
            </dl>
        </div>
    </div>

    <!-- MySQL Tables Breakdown -->
    @if(count($tables) > 0)
    <div class="bg-white border border-[#E5DDD5] rounded-3xl overflow-hidden shadow-xs">
        <div class="px-6 py-5 border-b border-[#E5DDD5] bg-[#F7F3EE]">
            <span class="text-[10px] font-black uppercase tracking-widest text-[#C0422A]">Physical Storage Metrics</span>
            <h3 class="font-serif text-base font-bold text-[#3D2B1F]">MySQL Database Table Telemetry</h3>
            <p class="text-xs text-gray-500 mt-0.5">Live row count and physical storage consumption per database table.</p>
        </div>

        <div class="overflow-x-auto no-scrollbar">
            <table class="w-full text-left border-collapse min-w-150">
                <thead>
                    <tr class="bg-gray-50/70 border-b border-[#E5DDD5]">
                        <th class="px-6 py-3.5 text-[10px] font-black text-gray-600 uppercase tracking-widest">Table Name</th>
                        <th class="px-6 py-3.5 text-[10px] font-black text-gray-600 uppercase tracking-widest text-center">Row Count (Approx)</th>
                        <th class="px-6 py-3.5 text-[10px] font-black text-gray-600 uppercase tracking-widest text-right">Physical Size</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($tables as $t)
                    <tr class="hover:bg-amber-50/20 transition-colors">
                        <td class="px-6 py-3.5 font-bold text-gray-900 font-mono text-xs">
                            {{ $t->name }}
                        </td>
                        <td class="px-6 py-3.5 text-center text-gray-600 font-mono text-xs">
                            {{ number_format($t->rows) }}
                        </td>
                        <td class="px-6 py-3.5 text-right font-bold text-gray-900 font-mono text-xs">
                            {{ $t->size_mb }} MB
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif
</div>
@endsection
