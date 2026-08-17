@extends('layouts.superadmin')

@section('content')
<div class="space-y-8">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="text-[10px] font-bold text-[#C0422A] uppercase tracking-widest mb-1">Customer Account Oversight</div>
            <h1 class="font-serif text-3xl font-bold text-[#3D2B1F]">Customer <span class="text-[#C0422A] italic">Directory</span></h1>
            <p class="text-xs text-gray-500 mt-1">Super Admin oversight of customer accounts, order history, lifetime spend, and account access controls.</p>
        </div>
    </div>

    <!-- Search & Filter Controls -->
    <div class="bg-white border border-[#E5DDD5] rounded-3xl p-6 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
        {{-- Filter Tabs --}}
        <div class="flex items-center gap-2 flex-wrap">
            <a href="{{ route('superadmin.customers', ['status' => 'all', 'search' => $search]) }}"
               class="px-4 py-2 rounded-xl text-xs font-bold uppercase tracking-wider transition-all {{ $status === 'all' ? 'bg-[#3D2B1F] text-white' : 'bg-[#F7F3EE] text-gray-600 hover:bg-[#E5DDD5]' }}">
                All Customers
            </a>
            <a href="{{ route('superadmin.customers', ['status' => 'active', 'search' => $search]) }}"
               class="px-4 py-2 rounded-xl text-xs font-bold uppercase tracking-wider transition-all {{ $status === 'active' ? 'bg-[#3D2B1F] text-white' : 'bg-[#F7F3EE] text-gray-600 hover:bg-[#E5DDD5]' }}">
                Active
            </a>
            <a href="{{ route('superadmin.customers', ['status' => 'banned', 'search' => $search]) }}"
               class="px-4 py-2 rounded-xl text-xs font-bold uppercase tracking-wider transition-all {{ $status === 'banned' ? 'bg-red-600 text-white' : 'bg-red-50 text-red-700 hover:bg-red-100' }}">
                🚫 Banned
            </a>
        </div>

        {{-- Search Form --}}
        <form action="{{ route('superadmin.customers') }}" method="GET" class="flex items-center gap-2">
            <input type="hidden" name="status" value="{{ $status }}">
            <div class="relative">
                <input type="text" name="search" value="{{ $search }}" placeholder="Search customer name, email..."
                    class="w-64 pl-9 pr-4 py-2 bg-[#FAF7F2] border border-[#EBE3D9] text-[#3D2B1F] text-xs rounded-xl focus:outline-none focus:border-[#C0422A] transition-colors">
                <svg class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </div>
            <button type="submit" class="px-4 py-2 bg-[#3D2B1F] hover:bg-[#C0422A] text-white text-xs font-bold rounded-xl uppercase tracking-wider transition-all cursor-pointer">
                Search
            </button>
            @if($search)
                <a href="{{ route('superadmin.customers', ['status' => $status]) }}" class="px-3 py-2 bg-gray-100 text-gray-600 text-xs font-bold rounded-xl">Clear</a>
            @endif
        </form>
    </div>

    <!-- Customers Table -->
    <div class="bg-white border border-[#E5DDD5] rounded-3xl overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="bg-[#F7F3EE] border-b border-[#E5DDD5] text-gray-400 uppercase tracking-widest font-bold text-[9px]">
                        <th class="px-6 py-4">Customer</th>
                        <th class="px-6 py-4">Contact</th>
                        <th class="px-6 py-4 text-center">Orders Placed</th>
                        <th class="px-6 py-4">Lifetime Spend</th>
                        <th class="px-6 py-4">Registered Date</th>
                        <th class="px-6 py-4">Account Status</th>
                        <th class="px-6 py-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#F0EAE1]">
                    @forelse($customers as $customer)
                    <tr class="hover:bg-[#FAF7F2] transition-colors">
                        <td class="px-6 py-4 font-bold text-[#3D2B1F]">
                            <div class="text-sm">{{ $customer['name'] }}</div>
                            <div class="text-[10px] text-gray-400 font-mono">ID: {{ $customer['id'] }}</div>
                        </td>

                        <td class="px-6 py-4 text-gray-600">
                            <div>{{ $customer['email'] }}</div>
                            <div class="text-[10px] text-gray-400 font-mono">{{ $customer['phone'] }}</div>
                        </td>

                        <td class="px-6 py-4 text-center">
                            <span class="px-2.5 py-1 bg-gray-100 text-gray-700 font-bold rounded-lg text-xs">
                                {{ $customer['orders_count'] }} orders
                            </span>
                        </td>

                        <td class="px-6 py-4 font-bold text-[#3D2B1F] font-mono text-xs">
                            ₱{{ number_format($customer['total_spent'], 2) }}
                        </td>

                        <td class="px-6 py-4 text-gray-500 font-mono">
                            {{ $customer['created_at'] ? \Carbon\Carbon::parse($customer['created_at'])->format('M d, Y') : '—' }}
                        </td>

                        <td class="px-6 py-4">
                            @if($customer['status'] === 'banned')
                                <span class="px-2.5 py-1 bg-red-50 text-red-600 border border-red-200 rounded-full font-bold text-[9px] uppercase tracking-wider">🚫 Banned</span>
                            @else
                                <span class="px-2.5 py-1 bg-green-50 text-green-700 border border-green-200 rounded-full font-bold text-[9px] uppercase tracking-wider">Active</span>
                            @endif
                        </td>

                        <td class="px-6 py-4 text-right">
                            @if($customer['status'] === 'banned')
                                <form action="{{ route('superadmin.customers.unban', $customer['id']) }}" method="POST" class="inline">
                                    @csrf @method('PATCH')
                                    <button type="submit" class="px-3 py-1.5 bg-green-600 hover:bg-green-700 text-white rounded-lg text-[10px] font-bold uppercase tracking-wider transition-all cursor-pointer">
                                        Unban
                                    </button>
                                </form>
                            @else
                                <form action="{{ route('superadmin.customers.ban', $customer['id']) }}" method="POST" class="inline">
                                    @csrf @method('PATCH')
                                    <button type="submit" class="px-3 py-1.5 bg-red-50 text-red-600 hover:bg-red-100 border border-red-200 rounded-lg text-[10px] font-bold uppercase tracking-wider transition-all cursor-pointer">
                                        Ban
                                    </button>
                                </form>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center text-gray-400 italic">No customer records match your filter criteria.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($customers->hasPages())
        <div class="px-6 py-4 border-t border-[#E5DDD5] bg-[#FAF7F2]">
            {{ $customers->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
