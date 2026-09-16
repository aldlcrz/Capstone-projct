@extends('layouts.superadmin')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="text-[10px] font-bold text-[#C0422A] uppercase tracking-[0.2em] mb-1">Developer Operations &amp; DevOps</div>
            <h1 class="font-serif text-2xl sm:text-3xl font-bold text-[#3D2B1F]">
                System <span class="text-[#C0422A] italic">Maintenance &amp; Cache</span>
            </h1>
            <p class="text-xs text-gray-500 mt-1">Control marketplace availability during schema upgrades and purge server cache engines without SSH.</p>
        </div>
    </div>

    <!-- Alert Notifications -->
    @if(session('success'))
    <div class="p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-bold rounded-2xl flex items-center gap-3 shadow-xs">
        <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
        </svg>
        <span>{{ session('success') }}</span>
    </div>
    @endif

    <!-- Status Cards Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Maintenance Mode Toggle Card -->
        <div class="bg-white border border-[#E5DDD5] rounded-3xl p-6 sm:p-8 shadow-xs space-y-6 flex flex-col justify-between">
            <div class="space-y-4">
                <div class="flex items-center justify-between border-b border-gray-100 pb-4">
                    <span class="text-[10px] font-black uppercase tracking-widest text-[#C0422A]">Marketplace Availability</span>
                    @if($isMaintenanceMode)
                        <span class="px-3 py-1 bg-amber-50 text-amber-700 border border-amber-200 rounded-full text-[10px] font-black uppercase tracking-wider flex items-center gap-1.5 animate-pulse">
                            <span class="w-2 h-2 rounded-full bg-amber-500"></span> Maintenance Mode Active
                        </span>
                    @else
                        <span class="px-3 py-1 bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-full text-[10px] font-black uppercase tracking-wider flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-emerald-500"></span> Platform Live (Normal)
                        </span>
                    @endif
                </div>

                <div class="space-y-1">
                    <h3 class="font-serif text-lg font-bold text-[#3D2B1F]">
                        {{ $isMaintenanceMode ? 'Maintenance Mode is currently ENABLED' : 'Platform is fully online and accessible' }}
                    </h3>
                    <p class="text-xs text-gray-500 leading-relaxed">
                        When enabled, non-superadmin visitors attempting to access the marketplace will see a warm maintenance screen (HTTP 503). Superadmins bypass restrictions automatically.
                    </p>
                </div>

                <form action="{{ route('superadmin.maintenance.toggle') }}" method="POST" class="space-y-4 pt-2">
                    @csrf
                    <div>
                        <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-2">Notice Message Displayed to Visitors</label>
                        <textarea name="message" rows="3" class="w-full p-3.5 bg-gray-50 border border-gray-200 text-gray-900 text-xs rounded-2xl focus:bg-white focus:outline-none focus:border-[#C0422A] transition-all leading-relaxed">{{ $maintenanceMessage }}</textarea>
                    </div>

                    @if($isMaintenanceMode)
                        <input type="hidden" name="enable" value="0">
                        <button type="submit" class="w-full py-3 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs uppercase tracking-widest rounded-xl transition-all cursor-pointer shadow-xs">
                            ✓ Bring Platform Back Online
                        </button>
                    @else
                        <input type="hidden" name="enable" value="1">
                        <button type="submit" class="w-full py-3 bg-amber-600 hover:bg-amber-700 text-white font-bold text-xs uppercase tracking-widest rounded-xl transition-all cursor-pointer shadow-xs">
                            ⚠️ Enable Maintenance Mode
                        </button>
                    @endif
                </form>
            </div>
        </div>

        <!-- 1-Click Cache Purge Card -->
        <div class="bg-white border border-[#E5DDD5] rounded-3xl p-6 sm:p-8 shadow-xs space-y-6 flex flex-col justify-between">
            <div class="space-y-4">
                <div class="flex items-center justify-between border-b border-gray-100 pb-4">
                    <span class="text-[10px] font-black uppercase tracking-widest text-[#C0422A]">Framework Acceleration</span>
                    <span class="px-3 py-1 bg-blue-50 text-blue-700 border border-blue-200 rounded-full text-[10px] font-black uppercase tracking-wider">
                        ⚡ Instant Flush
                    </span>
                </div>

                <div class="space-y-1">
                    <h3 class="font-serif text-lg font-bold text-[#3D2B1F]">1-Click Cache Purge &amp; Rebuild</h3>
                    <p class="text-xs text-gray-500 leading-relaxed">
                        Instantly flushes compiled Blade views, route caches, application config, and session stores. Use this immediately after deploying changes or when debugging cached templates.
                    </p>
                </div>

                <div class="p-4 rounded-2xl bg-[#F7F3EE] border border-[#E5DDD5] space-y-2 text-xs text-gray-700 font-mono">
                    <div class="flex items-center gap-2">
                        <span class="text-emerald-600 font-bold">✓</span> <span>php artisan optimize:clear</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-emerald-600 font-bold">✓</span> <span>php artisan view:clear</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-emerald-600 font-bold">✓</span> <span>php artisan config:clear</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-emerald-600 font-bold">✓</span> <span>php artisan route:clear</span>
                    </div>
                </div>

                <form action="{{ route('superadmin.maintenance.clear-cache') }}" method="POST">
                    @csrf
                    <button type="submit" class="w-full py-3 bg-[#3D2B1F] hover:bg-[#C0422A] text-white font-bold text-xs uppercase tracking-widest rounded-xl transition-all cursor-pointer shadow-xs flex items-center justify-center gap-2">
                        <svg class="w-4 h-4 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        <span>Purge All System Caches</span>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
