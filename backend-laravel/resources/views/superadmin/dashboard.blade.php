@extends('layouts.superadmin')

@section('content')
<div class="space-y-8">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <div class="text-xs font-bold text-[#C0422A] uppercase tracking-widest mb-1">Super Admin Governance</div>
            <h1 class="font-serif text-3xl font-bold text-[#3D2B1F]">Platform Sales &amp; <span class="text-[#C0422A] italic">Commission Overview</span></h1>
        </div>
        <a href="{{ route('superadmin.commissions') }}" class="px-5 py-3 bg-[#3D2B1F] hover:bg-[#C0422A] text-white font-black rounded-xl text-xs uppercase tracking-widest transition-all shadow-sm">
            Manage Commissions →
        </a>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Total Platform Sales -->
        <div class="bg-white border border-[#E5DDD5] rounded-3xl p-6 shadow-sm">
            <div class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2">Total Sales (All Time)</div>
            <div class="text-2xl font-black text-[#3D2B1F]">₱{{ number_format($totalSalesAllTime, 2) }}</div>
            <div class="text-[11px] text-gray-400 mt-2">This Month: <span class="text-gray-700 font-bold">₱{{ number_format($totalSalesThisMonth, 2) }}</span></div>
        </div>

        <!-- Total Commission Expected -->
        <div class="bg-white border border-[#E5DDD5] rounded-3xl p-6 shadow-sm">
            <div class="text-[10px] font-bold text-[#C0422A] uppercase tracking-widest mb-2">Total Commission ({{ $rate }}%)</div>
            <div class="text-2xl font-black text-[#C0422A]">₱{{ number_format($totalCommissionAllTime, 2) }}</div>
            <div class="text-[11px] text-gray-400 mt-2">This Month: <span class="text-[#C0422A] font-bold">₱{{ number_format($totalCommissionThisMonth, 2) }}</span></div>
        </div>

        <!-- Collected vs Outstanding -->
        <div class="bg-white border border-[#E5DDD5] rounded-3xl p-6 shadow-sm">
            <div class="text-[10px] font-bold text-green-600 uppercase tracking-widest mb-2">Collected Commission</div>
            <div class="text-2xl font-black text-green-600">₱{{ number_format($totalCollected, 2) }}</div>
            <div class="text-[11px] text-gray-400 mt-2">Outstanding: <span class="text-red-500 font-bold">₱{{ number_format($totalOutstanding, 2) }}</span></div>
        </div>

        <!-- Shop Account Status -->
        <div class="bg-white border border-[#E5DDD5] rounded-3xl p-6 shadow-sm">
            <div class="text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-2">Shop Account Status</div>
            <div class="text-2xl font-black text-[#3D2B1F]">{{ $sellerCount }} <span class="text-xs text-gray-400 font-normal">Active Shops</span></div>
            <div class="text-[11px] mt-2 flex items-center gap-3">
                <span class="text-blue-500 font-bold">❄️ {{ $frozenCount }} Frozen</span>
                <span class="text-red-500 font-bold">⚠️ {{ $unpaidCount }} Unpaid</span>
            </div>
        </div>
    </div>

    <!-- Active Commission Rules Banner -->
    <div class="bg-white border border-[#E5DDD5] rounded-3xl p-6 flex flex-col md:flex-row md:items-center justify-between gap-4 shadow-sm">
        <div class="space-y-1">
            <h3 class="text-sm font-bold text-[#3D2B1F] uppercase tracking-wider flex items-center gap-2">
                <span>⚙️ Current Commission Settings</span>
            </h3>
            <p class="text-xs text-gray-500">
                Rate: <strong class="text-[#C0422A]">{{ $rate }}% per order price</strong> • Standard Due Date: <strong class="text-gray-700">7th of each month</strong> • Auto-Freeze Enabled
            </p>
        </div>
        <a href="{{ route('superadmin.commissions') }}" class="px-4 py-2 bg-[#F7F3EE] hover:bg-[#E5DDD5] text-[#3D2B1F] border border-[#E5DDD5] rounded-xl text-xs font-bold uppercase tracking-wider w-fit transition-all">
            Configure Settings
        </a>
    </div>

    <!-- Recent Commission Activity Table -->
    <div class="bg-white border border-[#E5DDD5] rounded-3xl overflow-hidden shadow-sm">
        <div class="px-6 py-5 border-b border-[#E5DDD5] flex items-center justify-between">
            <h3 class="text-sm font-bold text-[#3D2B1F] uppercase tracking-wider">Recent Commission Records</h3>
            <span class="text-xs text-gray-400">Period: {{ $currentPeriod }}</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="bg-[#F7F3EE] border-b border-[#E5DDD5] text-gray-400 uppercase tracking-widest font-bold text-[10px]">
                        <th class="px-6 py-4">Shop / Seller</th>
                        <th class="px-6 py-4">Period</th>
                        <th class="px-6 py-4">Total Sales</th>
                        <th class="px-6 py-4">Commission ({{ $rate }}%)</th>
                        <th class="px-6 py-4">Status</th>
                        <th class="px-6 py-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#E5DDD5]">
                    @forelse($recentRecords as $record)
                    <tr class="hover:bg-[#F7F3EE] transition-all">
                        <td class="px-6 py-4 font-bold text-[#3D2B1F]">
                            {{ $record->seller->shopName ?? $record->seller->name ?? 'Unknown Seller' }}
                            <div class="text-[10px] text-gray-400 font-normal">{{ $record->seller->email ?? '' }}</div>
                        </td>
                        <td class="px-6 py-4 text-gray-500 font-mono">{{ $record->period }}</td>
                        <td class="px-6 py-4 font-bold text-gray-700">₱{{ number_format($record->totalSales, 2) }}</td>
                        <td class="px-6 py-4 font-bold text-[#C0422A]">₱{{ number_format($record->commissionAmount, 2) }}</td>
                        <td class="px-6 py-4">
                            @if($record->status === 'paid')
                                <span class="px-3 py-1 bg-green-50 text-green-600 border border-green-200 rounded-full font-bold uppercase text-[9px]">Paid</span>
                            @else
                                <span class="px-3 py-1 bg-red-50 text-red-500 border border-red-200 rounded-full font-bold uppercase text-[9px]">Unpaid</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right">
                            <a href="{{ route('superadmin.commissions') }}" class="text-[#C0422A] hover:underline font-semibold">View All</a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-8 text-center text-gray-400 italic">No commission records generated yet for recent periods.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
