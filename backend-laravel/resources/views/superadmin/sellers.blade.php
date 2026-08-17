@extends('layouts.superadmin')

@section('content')
<div class="space-y-8">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="text-[10px] font-bold text-[#C0422A] uppercase tracking-widest mb-1">Artisan Community Governance</div>
            <h1 class="font-serif text-3xl font-bold text-[#3D2B1F]">Sellers &amp; <span class="text-[#C0422A] italic">Shops Directory</span></h1>
            <p class="text-xs text-gray-500 mt-1">Super Admin oversight of all registered artisan shops, sales volume, commission balances, and account statuses.</p>
        </div>
    </div>

    <!-- Search & Filter Controls -->
    <div class="bg-white border border-[#E5DDD5] rounded-3xl p-6 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
        {{-- Filter Tabs --}}
        <div class="flex items-center gap-2 flex-wrap">
            <a href="{{ route('superadmin.sellers', ['status' => 'all', 'search' => $search]) }}"
               class="px-4 py-2 rounded-xl text-xs font-bold uppercase tracking-wider transition-all {{ $status === 'all' ? 'bg-[#3D2B1F] text-white' : 'bg-[#F7F3EE] text-gray-600 hover:bg-[#E5DDD5]' }}">
                All Shops
            </a>
            <a href="{{ route('superadmin.sellers', ['status' => 'active', 'search' => $search]) }}"
               class="px-4 py-2 rounded-xl text-xs font-bold uppercase tracking-wider transition-all {{ $status === 'active' ? 'bg-[#3D2B1F] text-white' : 'bg-[#F7F3EE] text-gray-600 hover:bg-[#E5DDD5]' }}">
                Active
            </a>
            <a href="{{ route('superadmin.sellers', ['status' => 'frozen', 'search' => $search]) }}"
               class="px-4 py-2 rounded-xl text-xs font-bold uppercase tracking-wider transition-all {{ $status === 'frozen' ? 'bg-blue-600 text-white' : 'bg-blue-50 text-blue-700 hover:bg-blue-100' }}">
                ❄️ Frozen
            </a>
            <a href="{{ route('superadmin.sellers', ['status' => 'unverified', 'search' => $search]) }}"
               class="px-4 py-2 rounded-xl text-xs font-bold uppercase tracking-wider transition-all {{ $status === 'unverified' ? 'bg-amber-600 text-white' : 'bg-amber-50 text-amber-700 hover:bg-amber-100' }}">
                ⚠️ Unverified
            </a>
        </div>

        {{-- Search Form --}}
        <form action="{{ route('superadmin.sellers') }}" method="GET" class="flex items-center gap-2">
            <input type="hidden" name="status" value="{{ $status }}">
            <div class="relative">
                <input type="text" name="search" value="{{ $search }}" placeholder="Search shop or artisan..."
                    class="w-64 pl-9 pr-4 py-2 bg-[#FAF7F2] border border-[#EBE3D9] text-[#3D2B1F] text-xs rounded-xl focus:outline-none focus:border-[#C0422A] transition-colors">
                <svg class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </div>
            <button type="submit" class="px-4 py-2 bg-[#3D2B1F] hover:bg-[#C0422A] text-white text-xs font-bold rounded-xl uppercase tracking-wider transition-all cursor-pointer">
                Search
            </button>
            @if($search)
                <a href="{{ route('superadmin.sellers', ['status' => $status]) }}" class="px-3 py-2 bg-gray-100 text-gray-600 text-xs font-bold rounded-xl">Clear</a>
            @endif
        </form>
    </div>

    <!-- Shops Table -->
    <div class="bg-white border border-[#E5DDD5] rounded-3xl overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="bg-[#F7F3EE] border-b border-[#E5DDD5] text-gray-400 uppercase tracking-widest font-bold text-[9px]">
                        <th class="px-6 py-4">Shop &amp; Artisan</th>
                        <th class="px-6 py-4">Contact</th>
                        <th class="px-6 py-4 text-center">Catalog</th>
                        <th class="px-6 py-4">Total Gross Sales</th>
                        <th class="px-6 py-4">Platform Fee ({{ $rate }}%)</th>
                        <th class="px-6 py-4">Unpaid Debt</th>
                        <th class="px-6 py-4">Status</th>
                        <th class="px-6 py-4 text-right">Governance Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#F0EAE1]">
                    @forelse($sellers as $seller)
                    <tr class="hover:bg-[#FAF7F2] transition-colors">
                        <td class="px-6 py-4">
                            <div class="font-bold text-[#3D2B1F] text-sm flex items-center gap-1.5">
                                <span>{{ $seller['shop_name'] }}</span>
                                @if($seller['is_verified'])
                                    <span class="inline-flex items-center px-1.5 py-0.2 bg-blue-50 border border-blue-200 text-blue-700 text-[8px] font-bold rounded-full">✓ Verified</span>
                                @endif
                            </div>
                            <div class="text-[10px] text-gray-400">Owner: {{ $seller['name'] }}</div>
                        </td>

                        <td class="px-6 py-4 text-gray-600">
                            <div>{{ $seller['email'] }}</div>
                            <div class="text-[10px] text-gray-400 font-mono">{{ $seller['phone'] }}</div>
                        </td>

                        <td class="px-6 py-4 text-center">
                            <span class="px-2.5 py-1 bg-gray-100 text-gray-700 font-bold rounded-lg text-xs">
                                {{ $seller['products_count'] }} items
                            </span>
                        </td>

                        <td class="px-6 py-4 font-bold text-[#3D2B1F] font-mono text-xs">
                            ₱{{ number_format($seller['total_sales'], 2) }}
                        </td>

                        <td class="px-6 py-4 font-bold text-[#C0422A] font-mono text-xs">
                            ₱{{ number_format($seller['total_profit'], 2) }}
                        </td>

                        <td class="px-6 py-4 font-mono text-xs">
                            @if($seller['unpaid_debt'] > 0)
                                <span class="font-bold text-red-500">₱{{ number_format($seller['unpaid_debt'], 2) }}</span>
                            @else
                                <span class="text-gray-400">₱0.00</span>
                            @endif
                        </td>

                        <td class="px-6 py-4">
                            @if($seller['status'] === 'frozen')
                                <span class="px-2.5 py-1 bg-blue-50 text-blue-600 border border-blue-200 rounded-full font-bold text-[9px] uppercase tracking-wider">❄️ Frozen</span>
                            @else
                                <span class="px-2.5 py-1 bg-green-50 text-green-700 border border-green-200 rounded-full font-bold text-[9px] uppercase tracking-wider">Active</span>
                            @endif
                        </td>

                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end gap-2">
                                {{-- Freeze / Unfreeze --}}
                                @if($seller['status'] === 'frozen')
                                    <form action="{{ route('superadmin.shops.unfreeze', $seller['id']) }}" method="POST">
                                        @csrf @method('PATCH')
                                        <button type="submit" class="px-3 py-1.5 bg-green-600 hover:bg-green-700 text-white rounded-lg text-[10px] font-bold uppercase tracking-wider transition-all cursor-pointer">
                                            Unfreeze
                                        </button>
                                    </form>
                                @else
                                    <form action="{{ route('superadmin.shops.freeze', $seller['id']) }}" method="POST">
                                        @csrf @method('PATCH')
                                        <input type="hidden" name="reason" value="Administrative review by Super Admin">
                                        <button type="submit" class="px-3 py-1.5 bg-blue-50 text-blue-700 hover:bg-blue-100 border border-blue-200 rounded-lg text-[10px] font-bold uppercase tracking-wider transition-all cursor-pointer">
                                            Freeze
                                        </button>
                                    </form>
                                @endif

                                {{-- Verification Toggle --}}
                                @if($seller['is_verified'])
                                    <form action="{{ route('superadmin.sellers.unverify', $seller['id']) }}" method="POST">
                                        @csrf @method('PATCH')
                                        <button type="submit" class="px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg text-[10px] font-bold uppercase tracking-wider transition-all cursor-pointer" title="Remove Verified Badge">
                                            Unverify
                                        </button>
                                    </form>
                                @else
                                    <form action="{{ route('superadmin.sellers.verify', $seller['id']) }}" method="POST">
                                        @csrf @method('PATCH')
                                        <button type="submit" class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-[10px] font-bold uppercase tracking-wider transition-all cursor-pointer" title="Grant Verified Badge">
                                            Verify
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-6 py-12 text-center text-gray-400 italic">No artisan shops match your current filter criteria.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($sellers->hasPages())
        <div class="px-6 py-4 border-t border-[#E5DDD5] bg-[#FAF7F2]">
            {{ $sellers->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
