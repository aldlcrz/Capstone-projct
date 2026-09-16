@extends('layouts.superadmin')

@section('content')
<div class="space-y-8">
    <!-- Header with Quick Developer Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="inline-flex items-center gap-2">
                <span class="text-[9px] font-black uppercase tracking-[0.25em] text-[#C0422A]">System &amp; Governance</span>
                <span class="text-gray-300 text-xs">·</span>
                <span class="text-[9px] font-bold uppercase tracking-[0.2em] text-gray-400">Command Center</span>
            </div>
            <h1 class="font-serif text-2xl sm:text-3xl font-bold text-gray-900 leading-tight mt-0.5">
                Super Admin <span class="text-[#C0420A] font-light italic">Dashboard</span>
            </h1>
            <p class="text-[11px] text-gray-400 font-medium">{{ now()->format('l, F j, Y') }} · Supreme governance &amp; enterprise controls</p>
        </div>

        <div class="flex items-center gap-2 flex-wrap">
            {{-- 1-Click Clear Cache Button --}}
            <form action="{{ route('superadmin.maintenance.clear-cache') }}" method="POST">
                @csrf
                <button type="submit" class="px-4 py-2.5 bg-white hover:bg-[#F8F7F4] text-gray-700 border border-gray-200 rounded-xl text-[10px] font-bold uppercase tracking-widest transition-all shadow-xs flex items-center gap-2 cursor-pointer">
                    <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    <span>Clear Cache</span>
                </button>
            </form>

            <a href="{{ route('superadmin.commissions') }}" class="px-5 py-2.5 bg-[#3D2B1F] hover:bg-[#C0422A] text-white font-bold rounded-xl text-[10px] uppercase tracking-widest transition-all shadow-sm flex items-center gap-2">
                <span>Profit Breakdown →</span>
            </a>
        </div>
    </div>

    <!-- ── 1. SYSTEM & DEVELOPER VITALS (TOP STRIP) ── -->
    <div class="bg-white border border-gray-100 rounded-2xl p-6 shadow-sm">
        <div class="flex items-center justify-between border-b border-gray-100 pb-3 mb-4">
            <div class="text-[10px] font-bold text-gray-400 uppercase tracking-widest flex items-center gap-2">
                <svg class="w-4 h-4 text-[#C0422A]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"/></svg>
                <span>Server &amp; Platform Health</span>
            </div>
            <a href="{{ route('superadmin.platform') }}" class="text-[10px] font-bold uppercase tracking-widest text-[#C0422A] hover:underline">
                Detailed Platform Specs →
            </a>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 text-xs">
            <div class="p-3.5 bg-gray-50/80 rounded-xl border border-gray-100">
                <div class="text-[9px] font-bold text-gray-400 uppercase tracking-wider mb-1">Environment</div>
                <div class="font-bold text-gray-800 flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full {{ $systemHealth['environment'] === 'production' ? 'bg-emerald-500' : 'bg-amber-500' }}"></span>
                    <span class="capitalize font-mono">{{ $systemHealth['environment'] }}</span>
                </div>
            </div>

            <div class="p-3.5 bg-gray-50/80 rounded-xl border border-gray-100">
                <div class="text-[9px] font-bold text-gray-400 uppercase tracking-wider mb-1">PHP / Laravel</div>
                <div class="font-bold text-gray-800 font-mono">
                    {{ $systemHealth['php_version'] }} / v{{ $systemHealth['laravel_version'] }}
                </div>
            </div>

            <div class="p-3.5 bg-gray-50/80 rounded-xl border border-gray-100">
                <div class="text-[9px] font-bold text-gray-400 uppercase tracking-wider mb-1">Database Size</div>
                <div class="font-bold text-gray-800 font-mono">
                    {{ $systemHealth['db_size'] }}
                </div>
            </div>

            <div class="p-3.5 bg-gray-50/80 rounded-xl border border-gray-100">
                <div class="text-[9px] font-bold text-gray-400 uppercase tracking-wider mb-1">Memory Usage</div>
                <div class="font-bold text-gray-800 font-mono">
                    {{ $systemHealth['memory_usage'] }}
                </div>
            </div>

            <div class="p-3.5 bg-gray-50/80 rounded-xl border border-gray-100">
                <div class="text-[9px] font-bold text-gray-400 uppercase tracking-wider mb-1">Disk Free</div>
                <div class="font-bold text-gray-800 font-mono">
                    {{ $systemHealth['disk_free'] }}
                </div>
            </div>

            <div class="p-3.5 bg-gray-50/80 rounded-xl border border-gray-100">
                <div class="text-[9px] font-bold text-gray-400 uppercase tracking-wider mb-1">System Mode</div>
                <div>
                    @if($systemHealth['is_maintenance'])
                        <span class="px-2 py-0.5 bg-amber-100 text-amber-800 rounded font-bold text-[9px] uppercase tracking-wider">Maintenance</span>
                    @else
                        <span class="px-2 py-0.5 bg-emerald-100 text-emerald-800 rounded font-bold text-[9px] uppercase tracking-wider">Live Normal</span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- ── 2. FINANCIAL & GOVERNANCE KPIS ── -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Total Platform Sales -->
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 flex flex-col gap-3 hover:shadow-md transition-all">
            <div class="flex items-center justify-between">
                <div class="w-9 h-9 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                </div>
                <span class="text-[9px] font-bold text-gray-400 uppercase tracking-wider">GMV</span>
            </div>
            <div>
                <div class="text-xs font-bold text-gray-400 uppercase tracking-wider">Total Gross Sales</div>
                <div class="text-2xl font-black text-gray-900 mt-0.5">₱{{ number_format($totalSalesAllTime, 2) }}</div>
                <div class="text-[11px] text-gray-400 font-medium mt-1">This Month: <span class="text-gray-700 font-bold">₱{{ number_format($totalSalesThisMonth, 2) }}</span></div>
            </div>
        </div>

        <!-- Total Commission Expected -->
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 flex flex-col gap-3 hover:shadow-md transition-all">
            <div class="flex items-center justify-between">
                <div class="w-9 h-9 rounded-xl bg-red-50 text-[#C0422A] flex items-center justify-center shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <span class="text-[9px] font-bold text-[#C0422A] uppercase tracking-wider">{{ $rate }}% Fee</span>
            </div>
            <div>
                <div class="text-xs font-bold text-gray-400 uppercase tracking-wider">Platform Revenue</div>
                <div class="text-2xl font-black text-[#C0422A] mt-0.5">₱{{ number_format($totalCommissionAllTime, 2) }}</div>
                <div class="text-[11px] text-gray-400 font-medium mt-1">This Month: <span class="text-[#C0422A] font-bold">₱{{ number_format($totalCommissionThisMonth, 2) }}</span></div>
            </div>
        </div>

        <!-- Collected vs Outstanding -->
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 flex flex-col gap-3 hover:shadow-md transition-all">
            <div class="flex items-center justify-between">
                <div class="w-9 h-9 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <span class="text-[9px] font-bold text-emerald-600 uppercase tracking-wider">Realized</span>
            </div>
            <div>
                <div class="text-xs font-bold text-gray-400 uppercase tracking-wider">Commission Collected</div>
                <div class="text-2xl font-black text-emerald-600 mt-0.5">₱{{ number_format($totalCollected, 2) }}</div>
                <div class="text-[11px] text-gray-400 font-medium mt-1">Outstanding: <span class="text-rose-600 font-bold">₱{{ number_format($totalOutstanding, 2) }}</span></div>
            </div>
        </div>

        <!-- User Accounts Overview -->
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 flex flex-col gap-3 hover:shadow-md transition-all">
            <div class="flex items-center justify-between">
                <div class="w-9 h-9 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                </div>
                <span class="text-[9px] font-bold text-gray-400 uppercase tracking-wider">Population</span>
            </div>
            <div>
                <div class="text-xs font-bold text-gray-400 uppercase tracking-wider">Platform Registry</div>
                <div class="text-2xl font-black text-gray-900 mt-0.5">{{ $sellerCount }} <span class="text-xs text-gray-400 font-normal">Shops</span> · {{ $customerCount }} <span class="text-xs text-gray-400 font-normal">Buyers</span></div>
                <div class="text-[11px] mt-1 flex items-center gap-2.5 font-medium">
                    <span class="text-emerald-600 font-bold">✓ {{ $verifiedSellers }} Verified</span>
                    <span class="text-blue-500 font-bold">❄️ {{ $frozenCount }} Frozen</span>
                    <span class="text-gray-400">{{ $productCount }} Items</span>
                </div>
            </div>
        </div>
    </div>

    <!-- ── 3. PROFIT PER SHOP LEADERBOARD & RECENT ACTIVITY ── -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Top 5 Profit Shops Leaderboard (2 cols) -->
        <div class="lg:col-span-2 bg-white border border-gray-100 rounded-2xl overflow-hidden shadow-sm flex flex-col">
            <div class="px-6 py-4.5 border-b border-gray-100 flex items-center justify-between bg-gray-50/50">
                <div>
                    <h3 class="text-xs font-bold text-gray-900 uppercase tracking-wider">Top Artisan Shops Profit Leaderboard</h3>
                    <p class="text-[11px] text-gray-400 mt-0.5 font-medium">Highest grossing artisan shops and generated platform revenue.</p>
                </div>
                <a href="{{ route('superadmin.sellers') }}" class="px-3 py-1.5 bg-white hover:bg-gray-50 text-gray-700 border border-gray-200 rounded-xl text-[10px] font-bold uppercase tracking-wider transition-all">
                    All Shops →
                </a>
            </div>
            
            <div class="overflow-x-auto flex-1">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="bg-[#F8F7F4] border-b border-gray-100 text-gray-400 uppercase tracking-widest font-bold text-[9px]">
                            <th class="px-6 py-3.5">Rank &amp; Shop</th>
                            <th class="px-6 py-3.5">Orders</th>
                            <th class="px-6 py-3.5">Gross Sales</th>
                            <th class="px-6 py-3.5">Platform Commission</th>
                            <th class="px-6 py-3.5 text-right">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($topShops as $index => $shop)
                        <tr class="hover:bg-gray-50/80 transition-colors">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <span class="w-6 h-6 rounded-full flex items-center justify-center font-bold text-xs {{ $index === 0 ? 'bg-amber-100 text-amber-700' : ($index === 1 ? 'bg-gray-200 text-gray-700' : ($index === 2 ? 'bg-orange-100 text-orange-700' : 'bg-transparent text-gray-400')) }}">
                                        {{ $index + 1 }}
                                    </span>
                                    <div>
                                        <div class="font-bold text-gray-900 flex items-center gap-1.5">
                                            <span>{{ $shop['shop_name'] }}</span>
                                            @if($shop['is_verified'])
                                                <span class="text-blue-500 font-bold" title="Verified Artisan">✓</span>
                                            @endif
                                        </div>
                                        <div class="text-[10px] text-gray-400">{{ $shop['name'] }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-gray-600 font-mono">{{ $shop['orders'] }}</td>
                            <td class="px-6 py-4 font-bold text-gray-900 font-mono">₱{{ number_format($shop['sales'], 2) }}</td>
                            <td class="px-6 py-4 font-bold text-[#C0422A] font-mono">₱{{ number_format($shop['commission'], 2) }}</td>
                            <td class="px-6 py-4 text-right">
                                @if($shop['status'] === 'frozen')
                                    <span class="px-2.5 py-0.5 bg-blue-50 text-blue-600 border border-blue-200 rounded-full font-bold text-[9px] uppercase">Frozen</span>
                                @else
                                    <span class="px-2.5 py-0.5 bg-emerald-50 text-emerald-600 border border-emerald-200 rounded-full font-bold text-[9px] uppercase">Active</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="px-6 py-8 text-center text-gray-400 italic">No shop sales recorded yet.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Quick Developer Actions & Error Logs (1 col) -->
        <div class="bg-white border border-gray-100 rounded-2xl p-6 shadow-sm flex flex-col justify-between space-y-6">
            <div>
                <div class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">Developer Fast Track</div>
                <h3 class="text-sm font-bold text-gray-900 mb-4">Quick Developer Actions</h3>

                <div class="space-y-2.5">
                    <a href="{{ route('superadmin.error-logs') }}" class="w-full flex items-center justify-between p-3.5 rounded-xl bg-gray-50/80 hover:bg-gray-100/80 border border-gray-100 transition-all group">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-rose-50 text-rose-600 flex items-center justify-center font-bold">
                                📜
                            </div>
                            <div>
                                <div class="text-xs font-bold text-gray-900">System Error Logs</div>
                                <div class="text-[10px] text-gray-400">{{ $recentErrorCount }} recorded error entries</div>
                            </div>
                        </div>
                        <span class="text-xs text-gray-400 group-hover:text-gray-700">→</span>
                    </a>

                    <a href="{{ route('superadmin.maintenance') }}" class="w-full flex items-center justify-between p-3.5 rounded-xl bg-gray-50/80 hover:bg-gray-100/80 border border-gray-100 transition-all group">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-amber-50 text-amber-700 flex items-center justify-center font-bold">
                                🧪
                            </div>
                            <div>
                                <div class="text-xs font-bold text-gray-900">Maintenance Mode</div>
                                <div class="text-[10px] text-gray-400">System maintenance &amp; bypass keys</div>
                            </div>
                        </div>
                        <span class="text-xs text-gray-400 group-hover:text-gray-700">→</span>
                    </a>

                    <a href="{{ route('superadmin.audit-logs') }}" class="w-full flex items-center justify-between p-3.5 rounded-xl bg-gray-50/80 hover:bg-gray-100/80 border border-gray-100 transition-all group">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center font-bold">
                                📋
                            </div>
                            <div>
                                <div class="text-xs font-bold text-gray-900">Audit &amp; Security Logs</div>
                                <div class="text-[10px] text-gray-400">Track operations and admin activities</div>
                            </div>
                        </div>
                        <span class="text-xs text-gray-400 group-hover:text-gray-700">→</span>
                    </a>
                </div>
            </div>

            <div class="pt-4 border-t border-gray-100">
                <div class="text-[9px] text-gray-400 font-bold uppercase tracking-wider mb-2">Commission Rate Policy</div>
                <div class="flex items-center justify-between text-xs">
                    <span class="text-gray-600">Global Rate: <strong class="text-[#C0422A]">{{ $rate }}%</strong></span>
                    <a href="{{ route('superadmin.commissions') }}" class="text-[10px] font-bold uppercase tracking-widest text-[#C0422A] hover:underline">Adjust Rate</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
