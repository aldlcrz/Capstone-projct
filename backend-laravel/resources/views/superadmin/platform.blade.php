@extends('layouts.superadmin')

@section('content')
<div class="space-y-8">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <div class="text-[10px] font-bold text-[#C0422A] uppercase tracking-widest mb-1">Infrastructure &amp; Hosting Architecture</div>
            <h1 class="font-serif text-3xl font-bold text-[#3D2B1F]">Server &amp; <span class="text-[#C0422A] italic">Platform Info</span></h1>
            <p class="text-xs text-gray-500 mt-1">Technical specifications of the hosting server, runtime extensions, and database architecture.</p>
        </div>
    </div>

    <!-- Specifications Grid -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Application Architecture -->
        <div class="bg-white border border-[#E5DDD5] rounded-3xl p-6 shadow-sm space-y-4">
            <h3 class="text-xs font-bold text-[#3D2B1F] uppercase tracking-wider flex items-center gap-2 border-b border-[#F0EAE1] pb-3">
                <span>⚡ Application Stack</span>
            </h3>
            <dl class="divide-y divide-[#F0EAE1] text-xs">
                <div class="py-2.5 flex justify-between">
                    <dt class="text-gray-500 font-medium">Framework</dt>
                    <dd class="font-bold text-[#3D2B1F] font-mono">Laravel v{{ $info['app']['version'] }}</dd>
                </div>
                <div class="py-2.5 flex justify-between">
                    <dt class="text-gray-500 font-medium">Environment</dt>
                    <dd class="font-bold font-mono {{ $info['app']['environment'] === 'production' ? 'text-green-600' : 'text-amber-600' }}">
                        {{ strtoupper($info['app']['environment']) }}
                    </dd>
                </div>
                <div class="py-2.5 flex justify-between">
                    <dt class="text-gray-500 font-medium">Debug Mode</dt>
                    <dd class="font-bold font-mono {{ $info['app']['debug'] === 'Enabled' ? 'text-amber-600' : 'text-green-600' }}">
                        {{ $info['app']['debug'] }}
                    </dd>
                </div>
                <div class="py-2.5 flex justify-between">
                    <dt class="text-gray-500 font-medium">Timezone</dt>
                    <dd class="font-bold text-[#3D2B1F] font-mono">{{ $info['app']['timezone'] }}</dd>
                </div>
                <div class="py-2.5 flex justify-between">
                    <dt class="text-gray-500 font-medium">Locale</dt>
                    <dd class="font-bold text-[#3D2B1F] font-mono">{{ $info['app']['locale'] }}</dd>
                </div>
            </dl>
        </div>

        <!-- PHP Runtime Environment -->
        <div class="bg-white border border-[#E5DDD5] rounded-3xl p-6 shadow-sm space-y-4">
            <h3 class="text-xs font-bold text-[#3D2B1F] uppercase tracking-wider flex items-center gap-2 border-b border-[#F0EAE1] pb-3">
                <span>🐘 PHP Runtime</span>
            </h3>
            <dl class="divide-y divide-[#F0EAE1] text-xs">
                <div class="py-2.5 flex justify-between">
                    <dt class="text-gray-500 font-medium">PHP Version</dt>
                    <dd class="font-bold text-[#3D2B1F] font-mono">{{ $info['php']['version'] }}</dd>
                </div>
                <div class="py-2.5 flex justify-between">
                    <dt class="text-gray-500 font-medium">Host Operating System</dt>
                    <dd class="font-bold text-[#3D2B1F] font-mono">{{ $info['php']['os'] }}</dd>
                </div>
                <div class="py-2.5 flex justify-between">
                    <dt class="text-gray-500 font-medium">Memory Allocation</dt>
                    <dd class="font-bold text-[#3D2B1F] font-mono">{{ $info['php']['memory_limit'] }}</dd>
                </div>
                <div class="py-2.5 flex justify-between">
                    <dt class="text-gray-500 font-medium">Max Execution Time</dt>
                    <dd class="font-bold text-[#3D2B1F] font-mono">{{ $info['php']['max_execution_time'] }}</dd>
                </div>
                <div class="py-2.5 flex justify-between">
                    <dt class="text-gray-500 font-medium">Upload Limit</dt>
                    <dd class="font-bold text-[#3D2B1F] font-mono">{{ $info['php']['upload_max_filesize'] }}</dd>
                </div>
            </dl>
        </div>

        <!-- Database Engine -->
        <div class="bg-white border border-[#E5DDD5] rounded-3xl p-6 shadow-sm space-y-4">
            <h3 class="text-xs font-bold text-[#3D2B1F] uppercase tracking-wider flex items-center gap-2 border-b border-[#F0EAE1] pb-3">
                <span>🗄️ Database Architecture</span>
            </h3>
            <dl class="divide-y divide-[#F0EAE1] text-xs">
                <div class="py-2.5 flex justify-between">
                    <dt class="text-gray-500 font-medium">Driver</dt>
                    <dd class="font-bold text-[#3D2B1F] font-mono uppercase">{{ $info['database']['driver'] }}</dd>
                </div>
                <div class="py-2.5 flex justify-between">
                    <dt class="text-gray-500 font-medium">Engine Version</dt>
                    <dd class="font-bold text-[#3D2B1F] font-mono">{{ Str::limit($info['database']['version'], 20) }}</dd>
                </div>
                <div class="py-2.5 flex justify-between">
                    <dt class="text-gray-500 font-medium">Database Name</dt>
                    <dd class="font-bold text-[#3D2B1F] font-mono">{{ $info['database']['database'] }}</dd>
                </div>
                <div class="py-2.5 flex justify-between">
                    <dt class="text-gray-500 font-medium">Total Size on Disk</dt>
                    <dd class="font-bold text-[#C0422A] font-mono">{{ $info['stats']['db_size'] }}</dd>
                </div>
                <div class="py-2.5 flex justify-between">
                    <dt class="text-gray-500 font-medium">Total Revenue Processed</dt>
                    <dd class="font-bold text-green-700 font-mono">{{ $info['stats']['total_revenue'] }}</dd>
                </div>
            </dl>
        </div>
    </div>

    <!-- MySQL Tables Breakdown -->
    @if(count($tables) > 0)
    <div class="bg-white border border-[#E5DDD5] rounded-3xl overflow-hidden shadow-sm">
        <div class="px-6 py-5 border-b border-[#E5DDD5] bg-[#FAF7F2]">
            <h3 class="text-sm font-bold text-[#3D2B1F] uppercase tracking-wider">MySQL Database Table Metrics</h3>
            <p class="text-[11px] text-gray-500 mt-0.5">Live row count and physical disk consumption per database table.</p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="bg-[#F7F3EE] border-b border-[#E5DDD5] text-gray-400 uppercase tracking-widest font-bold text-[9px]">
                        <th class="px-6 py-3.5">Table Name</th>
                        <th class="px-6 py-3.5 text-center">Row Count (Approx)</th>
                        <th class="px-6 py-3.5 text-right">Physical Size</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#F0EAE1]">
                    @foreach($tables as $t)
                    <tr class="hover:bg-[#FAF7F2] transition-colors">
                        <td class="px-6 py-3.5 font-bold text-[#3D2B1F] font-mono">
                            {{ $t->name }}
                        </td>
                        <td class="px-6 py-3.5 text-center text-gray-600 font-mono">
                            {{ number_format($t->rows) }}
                        </td>
                        <td class="px-6 py-3.5 text-right font-bold text-gray-800 font-mono">
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
