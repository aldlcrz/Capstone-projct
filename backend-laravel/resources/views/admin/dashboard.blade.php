@extends('layouts.admin')

@section('content')
<div class="space-y-8">
    <div class="flex items-center justify-between">
        <div>
            <div class="text-[10px] font-bold text-[#C0422A] uppercase tracking-[0.2em] mb-1">Enterprise Overview</div>
            <h1 class="font-serif text-3xl font-bold text-black">Dashboard <span class="text-gray-300 font-light italic">Insights</span></h1>
        </div>
        <div class="flex items-center gap-3">
            <a href="/admin/export-global-report" class="flex items-center gap-2 px-6 py-3 bg-[#3D2B1F] text-white rounded-xl text-[10px] font-bold uppercase tracking-widest hover:bg-[#C0422A] transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                Export Report
            </a>
        </div>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm">
            <div class="w-10 h-10 rounded-xl bg-red-50 text-[#C0422A] flex items-center justify-center mb-4">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
            <div class="text-2xl font-black text-black mb-1">{{ $stats['totalSales'] }}</div>
            <div class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Total Sales</div>
        </div>

        <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm">
            <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center mb-4">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg>
            </div>
            <div class="text-2xl font-black text-black mb-1">{{ $stats['totalOrders'] }}</div>
            <div class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Total Orders</div>
        </div>

        <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm">
            <div class="w-10 h-10 rounded-xl bg-green-50 text-green-600 flex items-center justify-center mb-4">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
            </div>
            <div class="text-2xl font-black text-black mb-1">{{ $stats['activeCustomers'] }}</div>
            <div class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Active Customers</div>
        </div>

        <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm">
            <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center mb-4">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 11m8 4V4"></path></svg>
            </div>
            <div class="text-2xl font-black text-black mb-1">{{ $stats['liveProducts'] }}</div>
            <div class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Live Products</div>
        </div>
    </div>

    <!-- Financial Summary -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="md:col-span-2 bg-white rounded-3xl border border-gray-100 p-8 shadow-sm">
            <h3 class="text-lg font-bold text-black mb-6">Recent Activity</h3>
            <div class="space-y-4">
                @foreach($recentActivity as $activity)
                    <div class="flex items-center justify-between py-3 border-b border-gray-50 last:border-0">
                        <div class="flex items-center gap-4">
                            <div class="w-8 h-8 rounded-full bg-gray-50 flex items-center justify-center text-gray-400">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            </div>
                            <div>
                                <div class="text-xs font-bold text-black">{{ $activity->title }}</div>
                                <div class="text-[10px] text-gray-400">{{ $activity->createdAt->diffForHumans() }}</div>
                            </div>
                        </div>
                        <div class="text-[10px] font-bold text-gray-500 uppercase tracking-widest">{{ $activity->type }}</div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="bg-black text-white rounded-3xl p-8 shadow-xl flex flex-col justify-between">
            <div>
                <h3 class="text-lg font-bold mb-1">Financial Health</h3>
                <p class="text-[10px] text-gray-400 uppercase tracking-widest">Platform Net Profit</p>
            </div>
            <div class="py-8">
                <div class="text-4xl font-black text-[#C0422A]">{{ $stats['totalProfit'] }}</div>
                <div class="flex items-center gap-2 mt-2">
                    <span class="text-[10px] font-bold text-green-400">↑ Healthy</span>
                    <span class="text-[10px] text-gray-500">vs platform cost</span>
                </div>
            </div>
            <div class="space-y-3">
                <div class="flex justify-between text-[10px] font-bold uppercase tracking-widest text-gray-500">
                    <span>Revenue</span>
                    <span class="text-white">{{ $stats['totalRevenue'] }}</span>
                </div>
                <div class="flex justify-between text-[10px] font-bold uppercase tracking-widest text-gray-500">
                    <span>Capital</span>
                    <span class="text-white">{{ $stats['totalCapital'] }}</span>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
