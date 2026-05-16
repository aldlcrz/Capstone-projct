@extends('layouts.seller')

@section('content')
<div class="space-y-8">
    <div class="flex items-center justify-between">
        <div>
            <div class="text-[10px] font-bold text-[#C0422A] uppercase tracking-[0.2em] mb-1">Seller Performance</div>
            <h1 class="font-serif text-3xl font-bold text-black uppercase">Seller <span class="text-[#C0422A] italic lowercase">dashboard</span></h1>
        </div>
        <div class="flex items-center gap-3">
            <a href="/seller/export-report" class="flex items-center gap-2 px-6 py-3 bg-[#3D2B1F] text-white rounded-xl text-[10px] font-bold uppercase tracking-widest hover:bg-[#C0422A] transition-all shadow-lg shadow-black/5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                Export CSV
            </a>
        </div>
    </div>

    <!-- KPI Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="bg-[#C0422A] p-6 rounded-2xl shadow-xl shadow-[#C0422A]/10 text-white">
            <div class="flex items-center justify-between mb-4">
                <div class="text-[10px] font-bold uppercase tracking-widest text-white/60">Total Revenue</div>
                <svg class="w-5 h-5 text-white/40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
            <div class="text-2xl font-black">₱{{ number_format($summary['revenue']) }}</div>
        </div>

        <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm">
            <div class="flex items-center justify-between mb-4">
                <div class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Total Orders</div>
                <svg class="w-5 h-5 text-gray-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg>
            </div>
            <div class="text-2xl font-black text-black">{{ $summary['orders'] }}</div>
        </div>

        <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm">
            <div class="flex items-center justify-between mb-4">
                <div class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Total Customers</div>
                <svg class="w-5 h-5 text-gray-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
            </div>
            <div class="text-2xl font-black text-black">{{ $summary['customers'] }}</div>
        </div>

        <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm">
            <div class="flex items-center justify-between mb-4">
                <div class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Conv. Rate</div>
                <svg class="w-5 h-5 text-gray-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
            </div>
            <div class="text-2xl font-black text-black">{{ $summary['conversionRate'] }}</div>
        </div>
    </div>

    <!-- Inventory & Prescriptions -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <div class="lg:col-span-4 bg-white p-6 rounded-3xl border border-gray-100 shadow-sm">
            <h3 class="text-sm font-bold text-black mb-6">Inventory Health</h3>
            <div class="space-y-6">
                <div>
                    <div class="flex justify-between text-[10px] font-bold uppercase tracking-widest text-gray-400 mb-2">
                        <span>Healthy Stock</span>
                        <span class="text-green-600">{{ $inventoryHealth['healthy'] }}</span>
                    </div>
                    <div class="h-2 w-full bg-gray-50 rounded-full overflow-hidden">
                        <div class="h-full bg-green-500 rounded-full" style="width: {{ $inventoryHealth['total'] > 0 ? ($inventoryHealth['healthy'] / $inventoryHealth['total'] * 100) : 0 }}%"></div>
                    </div>
                </div>
                <div>
                    <div class="flex justify-between text-[10px] font-bold uppercase tracking-widest text-gray-400 mb-2">
                        <span>Low Stock</span>
                        <span class="text-amber-600">{{ $inventoryHealth['lowStock'] }}</span>
                    </div>
                    <div class="h-2 w-full bg-gray-50 rounded-full overflow-hidden">
                        <div class="h-full bg-amber-500 rounded-full" style="width: {{ $inventoryHealth['total'] > 0 ? ($inventoryHealth['lowStock'] / $inventoryHealth['total'] * 100) : 0 }}%"></div>
                    </div>
                </div>
                <div>
                    <div class="flex justify-between text-[10px] font-bold uppercase tracking-widest text-gray-400 mb-2">
                        <span>Out of Stock</span>
                        <span class="text-red-600">{{ $inventoryHealth['outOfStock'] }}</span>
                    </div>
                    <div class="h-2 w-full bg-gray-50 rounded-full overflow-hidden">
                        <div class="h-full bg-red-500 rounded-full" style="width: {{ $inventoryHealth['total'] > 0 ? ($inventoryHealth['outOfStock'] / $inventoryHealth['total'] * 100) : 0 }}%"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="lg:col-span-8 bg-white p-6 rounded-3xl border border-gray-100 shadow-sm">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h3 class="text-sm font-bold text-black">Artisan Prescriptions</h3>
                    <p class="text-[10px] text-gray-400 uppercase tracking-widest">Heritage Growth Tips</p>
                </div>
                <span class="px-2 py-0.5 bg-red-50 text-[#C0422A] text-[9px] font-black uppercase tracking-widest rounded border border-red-100">Smart Advice</span>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @forelse($prescriptions as $p)
                    <div class="p-5 rounded-2xl bg-[#F9F7F4] border border-[#E5DDD5]">
                        <div class="flex items-center gap-2 mb-2">
                            <div class="w-1.5 h-1.5 rounded-full {{ $p['priority'] === 'urgent' ? 'bg-red-500 animate-pulse' : 'bg-amber-500' }}"></div>
                            <span class="text-[10px] font-bold uppercase tracking-widest text-black">{{ $p['title'] }}</span>
                        </div>
                        <p class="text-xs text-gray-500 mb-3">{{ $p['message'] }}</p>
                        <a href="#" class="text-[10px] font-bold text-[#C0422A] uppercase tracking-widest hover:underline">Take Action →</a>
                    </div>
                @empty
                    <div class="col-span-2 py-8 text-center text-xs text-gray-400 italic">No recommendations at this time. Keep up the great work!</div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="bg-white p-6 rounded-3xl border border-gray-100 shadow-sm">
        <h3 class="text-sm font-bold text-black mb-6">Recent Workshop Activity</h3>
        <div class="space-y-4">
            @forelse($recentActivity as $activity)
                <div class="flex items-center justify-between p-4 rounded-2xl hover:bg-gray-50 transition-all border border-transparent hover:border-gray-100">
                    <div class="flex items-center gap-4">
                        <div class="w-10 h-10 rounded-full bg-[#F8F7F4] flex items-center justify-center text-[#C0422A]">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg>
                        </div>
                        <div>
                            <div class="text-xs font-bold text-black">Order #{{ strtoupper(substr($activity['id'], -6)) }}</div>
                            <div class="text-[10px] text-gray-400">Status: <span class="capitalize text-amber-600 font-bold">{{ $activity['status'] }}</span></div>
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="text-xs font-bold text-black">₱{{ number_format($activity['amount']) }}</div>
                        <div class="text-[9px] text-gray-400">{{ \Carbon\Carbon::parse($activity['date'])->format('M d, Y') }}</div>
                    </div>
                </div>
            @empty
                <div class="py-12 text-center text-xs text-gray-400 italic">Your workshop hasn't had any recent activity. Time to list new products!</div>
            @endforelse
        </div>
    </div>
</div>
@endsection
