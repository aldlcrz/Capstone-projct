@extends('layouts.superadmin')

@section('content')
<div class="space-y-8">
    <!-- Header with Quick Developer Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="text-[10px] font-bold text-[#C0422A] uppercase tracking-widest mb-1 flex items-center gap-1.5">
                <span class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></span>
                System &amp; Governance Command Center
            </div>
            <h1 class="font-serif text-3xl font-bold text-[#3D2B1F]">Super Admin <span class="text-[#C0422A] italic">Dashboard</span></h1>
        </div>

        <div class="flex items-center gap-2 flex-wrap">
            {{-- 1-Click Clear Cache Button --}}
            <form action="{{ route('superadmin.maintenance.clear-cache') }}" method="POST">
                @csrf
                <button type="submit" class="px-4 py-2.5 bg-white hover:bg-[#F7F3EE] text-[#3D2B1F] border border-[#E5DDD5] rounded-xl text-xs font-bold uppercase tracking-wider transition-all shadow-xs flex items-center gap-2 cursor-pointer">
                    <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    <span>Clear System Cache</span>
                </button>
            </form>

            <a href="{{ route('superadmin.commissions') }}" class="px-5 py-2.5 bg-[#3D2B1F] hover:bg-[#C0422A] text-white font-black rounded-xl text-xs uppercase tracking-widest transition-all shadow-sm flex items-center gap-2">
                <span>Profit Breakdown →</span>
            </a>
        </div>
    </div>

    <!-- ── 1. SYSTEM & DEVELOPER VITALS (TOP STRIP) ── -->
    <div class="bg-white border border-[#E5DDD5] rounded-3xl p-6 shadow-sm">
        <div class="flex items-center justify-between border-b border-[#F0EAE1] pb-3 mb-4">
            <div class="text-[10px] font-bold text-gray-400 uppercase tracking-widest flex items-center gap-2">
                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"/></svg>
                <span>Server &amp; Platform Health</span>
            </div>
            <a href="{{ route('superadmin.platform') }}" class="text-[11px] text-[#C0422A] hover:underline font-bold">
                Detailed Platform Specs →
            </a>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4 text-xs">
            <div class="p-3 bg-[#FAF7F2] rounded-2xl border border-[#EBE3D9]">
                <div class="text-[9px] font-bold text-gray-400 uppercase tracking-wider mb-1">Environment</div>
                <div class="font-bold text-[#3D2B1F] flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full {{ $systemHealth['environment'] === 'production' ? 'bg-green-500' : 'bg-amber-500' }}"></span>
                    <span class="capitalize font-mono">{{ $systemHealth['environment'] }}</span>
                </div>
            </div>

            <div class="p-3 bg-[#FAF7F2] rounded-2xl border border-[#EBE3D9]">
                <div class="text-[9px] font-bold text-gray-400 uppercase tracking-wider mb-1">PHP / Laravel</div>
                <div class="font-bold text-[#3D2B1F] font-mono">
                    {{ $systemHealth['php_version'] }} / v{{ $systemHealth['laravel_version'] }}
                </div>
            </div>

            <div class="p-3 bg-[#FAF7F2] rounded-2xl border border-[#EBE3D9]">
                <div class="text-[9px] font-bold text-gray-400 uppercase tracking-wider mb-1">Database Size</div>
                <div class="font-bold text-[#3D2B1F] font-mono">
                    {{ $systemHealth['db_size'] }}
                </div>
            </div>

            <div class="p-3 bg-[#FAF7F2] rounded-2xl border border-[#EBE3D9]">
                <div class="text-[9px] font-bold text-gray-400 uppercase tracking-wider mb-1">Memory Usage</div>
                <div class="font-bold text-[#3D2B1F] font-mono">
                    {{ $systemHealth['memory_usage'] }}
                </div>
            </div>

            <div class="p-3 bg-[#FAF7F2] rounded-2xl border border-[#EBE3D9]">
                <div class="text-[9px] font-bold text-gray-400 uppercase tracking-wider mb-1">Disk Free</div>
                <div class="font-bold text-[#3D2B1F] font-mono">
                    {{ $systemHealth['disk_free'] }}
                </div>
            </div>

            <div class="p-3 bg-[#FAF7F2] rounded-2xl border border-[#EBE3D9]">
                <div class="text-[9px] font-bold text-gray-400 uppercase tracking-wider mb-1">System Mode</div>
                <div>
                    @if($systemHealth['is_maintenance'])
                        <span class="px-2 py-0.5 bg-amber-100 text-amber-800 rounded font-bold text-[9px] uppercase tracking-wider">Maintenance</span>
                    @else
                        <span class="px-2 py-0.5 bg-green-100 text-green-800 rounded font-bold text-[9px] uppercase tracking-wider">Live Normal</span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- ── 2. FINANCIAL & GOVERNANCE KPIS ── -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Total Platform Sales -->
        <div class="bg-white border border-[#E5DDD5] rounded-3xl p-6 shadow-sm">
            <div class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2">Total Gross Sales (GMV)</div>
            <div class="text-2xl font-black text-[#3D2B1F]">₱{{ number_format($totalSalesAllTime, 2) }}</div>
            <div class="text-[11px] text-gray-400 mt-2">This Month: <span class="text-gray-700 font-bold">₱{{ number_format($totalSalesThisMonth, 2) }}</span></div>
        </div>

        <!-- Total Commission Expected -->
        <div class="bg-white border border-[#E5DDD5] rounded-3xl p-6 shadow-sm">
            <div class="text-[10px] font-bold text-[#C0422A] uppercase tracking-widest mb-2">Platform Revenue ({{ $rate }}%)</div>
            <div class="text-2xl font-black text-[#C0422A]">₱{{ number_format($totalCommissionAllTime, 2) }}</div>
            <div class="text-[11px] text-gray-400 mt-2">This Month: <span class="text-[#C0422A] font-bold">₱{{ number_format($totalCommissionThisMonth, 2) }}</span></div>
        </div>

        <!-- Collected vs Outstanding -->
        <div class="bg-white border border-[#E5DDD5] rounded-3xl p-6 shadow-sm">
            <div class="text-[10px] font-bold text-green-600 uppercase tracking-widest mb-2">Commission Collected</div>
            <div class="text-2xl font-black text-green-600">₱{{ number_format($totalCollected, 2) }}</div>
            <div class="text-[11px] text-gray-400 mt-2">Outstanding: <span class="text-red-500 font-bold">₱{{ number_format($totalOutstanding, 2) }}</span></div>
        </div>

        <!-- User Accounts Overview -->
        <div class="bg-white border border-[#E5DDD5] rounded-3xl p-6 shadow-sm">
            <div class="text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-2">Platform Population</div>
            <div class="text-2xl font-black text-[#3D2B1F]">{{ $sellerCount }} <span class="text-xs text-gray-400 font-normal">Shops</span> · {{ $customerCount }} <span class="text-xs text-gray-400 font-normal">Buyers</span></div>
            <div class="text-[11px] mt-2 flex items-center gap-3">
                <span class="text-green-600 font-bold">✓ {{ $verifiedSellers }} Verified</span>
                <span class="text-blue-500 font-bold">❄️ {{ $frozenCount }} Frozen</span>
                <span class="text-gray-500">{{ $productCount }} Products</span>
            </div>
        </div>
    </div>

    <!-- ── 3. PROFIT PER SHOP LEADERBOARD & RECENT ACTIVITY ── -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Top 5 Profit Shops Leaderboard (2 cols) -->
        <div class="lg:col-span-2 bg-white border border-[#E5DDD5] rounded-3xl overflow-hidden shadow-sm flex flex-col">
            <div class="px-6 py-5 border-b border-[#E5DDD5] flex items-center justify-between bg-[#FAF7F2]">
                <div>
                    <h3 class="text-sm font-bold text-[#3D2B1F] uppercase tracking-wider">Top Artisan Shops Profit Leaderboard</h3>
                    <p class="text-[11px] text-gray-500 mt-0.5">Highest grossing artisan shops and generated platform revenue.</p>
                </div>
                <a href="{{ route('superadmin.sellers') }}" class="px-3 py-1.5 bg-white hover:bg-[#F7F3EE] text-[#3D2B1F] border border-[#E5DDD5] rounded-xl text-[10px] font-bold uppercase tracking-wider transition-all">
                    All Shops Directory →
                </a>
            </div>
            
            <div class="overflow-x-auto flex-1">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="bg-[#F7F3EE] border-b border-[#E5DDD5] text-gray-400 uppercase tracking-widest font-bold text-[9px]">
                            <th class="px-6 py-3.5">Rank &amp; Shop</th>
                            <th class="px-6 py-3.5">Orders</th>
                            <th class="px-6 py-3.5">Gross Sales</th>
                            <th class="px-6 py-3.5">Platform Commission</th>
                            <th class="px-6 py-3.5 text-right">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#F0EAE1]">
                        @forelse($topShops as $index => $shop)
                        <tr class="hover:bg-[#FAF7F2] transition-colors">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <span class="w-6 h-6 rounded-full flex items-center justify-center font-bold text-xs {{ $index === 0 ? 'bg-amber-100 text-amber-700' : ($index === 1 ? 'bg-gray-100 text-gray-700' : ($index === 2 ? 'bg-orange-100 text-orange-700' : 'bg-transparent text-gray-400')) }}">
                                        {{ $index + 1 }}
                                    </span>
                                    <div>
                                        <div class="font-bold text-[#3D2B1F] flex items-center gap-1.5">
                                            <span>{{ $shop['shop_name'] }}</span>
                                            @if($shop['is_verified'])
                                                <span class="text-blue-500" title="Verified Artisan">✓</span>
                                            @endif
                                        </div>
                                        <div class="text-[10px] text-gray-400">{{ $shop['name'] }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-gray-600 font-mono">{{ $shop['orders'] }}</td>
                            <td class="px-6 py-4 font-bold text-gray-800 font-mono">₱{{ number_format($shop['sales'], 2) }}</td>
                            <td class="px-6 py-4 font-bold text-[#C0422A] font-mono">₱{{ number_format($shop['commission'], 2) }}</td>
                            <td class="px-6 py-4 text-right">
                                @if($shop['status'] === 'frozen')
                                    <span class="px-2.5 py-0.5 bg-blue-50 text-blue-600 border border-blue-200 rounded-full font-bold text-[9px] uppercase">Frozen</span>
                                @else
                                    <span class="px-2.5 py-0.5 bg-green-50 text-green-600 border border-green-200 rounded-full font-bold text-[9px] uppercase">Active</span>
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
        <div class="bg-white border border-[#E5DDD5] rounded-3xl p-6 shadow-sm flex flex-col justify-between space-y-6">
            <div>
                <div class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">Developer Fast Track</div>
                <h3 class="text-base font-bold text-[#3D2B1F] mb-4">Quick Developer Actions</h3>

                <div class="space-y-2.5">
                    <a href="{{ route('superadmin.error-logs') }}" class="w-full flex items-center justify-between p-3.5 rounded-2xl bg-[#FAF7F2] hover:bg-[#F0EAE1] border border-[#EBE3D9] transition-all group">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-xl bg-red-50 text-red-600 flex items-center justify-center font-bold">
                                📜
                            </div>
                            <div>
                                <div class="text-xs font-bold text-[#3D2B1F]">System Error Logs</div>
                                <div class="text-[10px] text-gray-500">{{ $recentErrorCount }} recorded error entries</div>
                            </div>
                        </div>
                        <span class="text-xs text-gray-400 group-hover:text-[#3D2B1F]">→</span>
                    </a>

                    <a href="{{ route('superadmin.maintenance') }}" class="w-full flex items-center justify-between p-3.5 rounded-2xl bg-[#FAF7F2] hover:bg-[#F0EAE1] border border-[#EBE3D9] transition-all group">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-xl bg-amber-50 text-amber-700 flex items-center justify-center font-bold">
                                🧪
                            </div>
                            <div>
                                <div class="text-xs font-bold text-[#3D2B1F]">Maintenance Mode</div>
                                <div class="text-[10px] text-gray-500">System maintenance &amp; secret bypass</div>
                            </div>
                        </div>
                        <span class="text-xs text-gray-400 group-hover:text-[#3D2B1F]">→</span>
                    </a>

                    <a href="{{ route('superadmin.audit-logs') }}" class="w-full flex items-center justify-between p-3.5 rounded-2xl bg-[#FAF7F2] hover:bg-[#F0EAE1] border border-[#EBE3D9] transition-all group">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center font-bold">
                                📋
                            </div>
                            <div>
                                <div class="text-xs font-bold text-[#3D2B1F]">Audit &amp; Security Logs</div>
                                <div class="text-[10px] text-gray-500">Track operations and admin activities</div>
                            </div>
                        </div>
                        <span class="text-xs text-gray-400 group-hover:text-[#3D2B1F]">→</span>
                    </a>
                </div>
            </div>

            <div class="pt-4 border-t border-[#F0EAE1]">
                <div class="text-[10px] text-gray-400 font-bold uppercase tracking-wider mb-2">Commission Rate Policy</div>
                <div class="flex items-center justify-between text-xs">
                    <span class="text-gray-600">Global Rate: <strong class="text-[#C0422A]">{{ $rate }}%</strong></span>
                    <a href="{{ route('superadmin.commissions') }}" class="text-[11px] text-[#C0422A] font-bold hover:underline">Adjust Rate</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
