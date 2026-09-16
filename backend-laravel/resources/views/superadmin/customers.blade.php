@extends('layouts.superadmin')

@section('content')
<div class="space-y-6" x-data="{
    banModal: false,
    banCustomerId: '',
    banCustomerName: '',
    banReason: '',
    setPreset(r) { this.banReason = r; },
    openBan(id, name) {
        this.banCustomerId = id;
        this.banCustomerName = name;
        this.banReason = 'Violation of community terms and policies';
        this.banModal = true;
    }
}">
    {{-- ═══ PAGE HEADER + SEARCH BAR ═══ --}}
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
        <div class="text-left space-y-0.5 shrink-0">
            <div class="inline-flex items-center gap-2">
                <span class="text-[9px] font-black uppercase tracking-[0.25em] text-[#C0422A]">Buyer Registry</span>
                <span class="text-gray-300 text-xs">·</span>
                <span class="text-[9px] font-bold uppercase tracking-[0.2em] text-gray-400">Account Governance</span>
            </div>
            <h1 class="font-serif text-2xl sm:text-3xl font-bold text-gray-900 leading-tight">
                Customer <span class="text-[#C0420A] font-light italic">Directory</span>
            </h1>
            <p class="text-[11px] text-gray-400 font-medium">Supreme oversight of customer accounts, purchase frequency, lifetime spend, and access privileges</p>
        </div>

        {{-- Search Bar --}}
        <div class="flex-1 max-w-xl lg:max-w-md w-full">
            <form action="{{ route('superadmin.customers') }}" method="GET" class="flex items-center gap-2 w-full">
                <input type="hidden" name="status" value="{{ $status }}">
                <div class="relative w-full">
                    <input type="text" name="search" value="{{ $search }}"
                           placeholder="Search customer name, email..."
                           class="w-full pl-11 pr-5 py-2.5 bg-white border border-gray-200 rounded-full text-sm text-gray-800 placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-[#C0422A]/20 focus:border-[#C0422A] shadow-xs transition-all">
                    <svg class="w-5 h-5 text-gray-400 absolute left-4 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>
                @if($search)
                    <a href="{{ route('superadmin.customers', ['status' => $status]) }}" class="px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-full text-xs font-bold transition-all shrink-0">Clear</a>
                @endif
            </form>
        </div>
    </div>

    {{-- ═══ STATS BAR / STATUS FILTERS ═══ --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
        {{-- All Customers --}}
        <a href="{{ route('superadmin.customers', ['status' => 'all', 'search' => $search]) }}"
           class="group relative rounded-2xl px-4 py-3 flex items-center justify-between border transition-all duration-200 cursor-pointer {{ $status === 'all' ? 'bg-white border-gray-900 ring-2 ring-gray-900/15 shadow-sm -translate-y-0.5' : 'bg-white border-gray-100 hover:border-gray-300 hover:shadow-sm hover:-translate-y-0.5' }}">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-xl flex items-center justify-center shrink-0 transition-colors {{ $status === 'all' ? 'bg-gray-900 text-white' : 'bg-gray-100 text-gray-500 group-hover:bg-gray-200' }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                </div>
                <div>
                    <div class="text-xs font-black text-gray-900 leading-none">All Customers</div>
                    <div class="text-[9px] font-bold uppercase tracking-wider text-gray-400 mt-1">Full Directory</div>
                </div>
            </div>
            @if($status === 'all')
                <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[8px] font-black uppercase tracking-wider bg-gray-900 text-white">Active</span>
            @endif
        </a>

        {{-- Active Customers --}}
        <a href="{{ route('superadmin.customers', ['status' => 'active', 'search' => $search]) }}"
           class="group relative rounded-2xl px-4 py-3 flex items-center justify-between border transition-all duration-200 cursor-pointer {{ $status === 'active' ? 'bg-emerald-50/50 border-emerald-600 ring-2 ring-emerald-600/20 shadow-sm -translate-y-0.5' : 'bg-white border-green-100 hover:border-emerald-300 hover:shadow-sm hover:-translate-y-0.5' }}">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-xl flex items-center justify-center shrink-0 transition-colors {{ $status === 'active' ? 'bg-emerald-600 text-white' : 'bg-green-50 text-green-600 group-hover:bg-green-100' }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div>
                    <div class="text-xs font-black text-gray-900 leading-none">Active Buyers</div>
                    <div class="text-[9px] font-bold uppercase tracking-wider text-emerald-600 mt-1">Operational</div>
                </div>
            </div>
            @if($status === 'active')
                <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[8px] font-black uppercase tracking-wider bg-emerald-600 text-white">Active</span>
            @endif
        </a>

        {{-- Banned Customers --}}
        <a href="{{ route('superadmin.customers', ['status' => 'banned', 'search' => $search]) }}"
           class="group relative rounded-2xl px-4 py-3 flex items-center justify-between border transition-all duration-200 cursor-pointer {{ $status === 'banned' ? 'bg-rose-50/50 border-rose-600 ring-2 ring-rose-600/20 shadow-sm -translate-y-0.5' : 'bg-white border-rose-100 hover:border-rose-300 hover:shadow-sm hover:-translate-y-0.5' }}">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-xl flex items-center justify-center shrink-0 transition-colors {{ $status === 'banned' ? 'bg-rose-600 text-white' : 'bg-rose-50 text-rose-600 group-hover:bg-rose-100' }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                </div>
                <div>
                    <div class="text-xs font-black text-gray-900 leading-none">Banned</div>
                    <div class="text-[9px] font-bold uppercase tracking-wider text-rose-600 mt-1">Suspended</div>
                </div>
            </div>
            @if($status === 'banned')
                <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[8px] font-black uppercase tracking-wider bg-rose-600 text-white">Active</span>
            @endif
        </a>
    </div>

    <!-- Customers Table -->
    <div class="bg-white border border-gray-100 rounded-2xl overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="bg-[#F8F7F4] border-b border-gray-100 text-gray-400 uppercase tracking-widest font-bold text-[9px]">
                        <th class="px-6 py-3.5">Customer Details</th>
                        <th class="px-6 py-3.5">Contact Information</th>
                        <th class="px-6 py-3.5 text-center">Orders Placed</th>
                        <th class="px-6 py-3.5">Lifetime Spend</th>
                        <th class="px-6 py-3.5">Registered Date</th>
                        <th class="px-6 py-3.5">Account Status</th>
                        <th class="px-6 py-3.5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($customers as $customer)
                    <tr class="hover:bg-gray-50/80 transition-colors">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-xl bg-gray-900 text-white flex items-center justify-center font-bold text-xs shrink-0">
                                    {{ strtoupper(substr($customer['name'], 0, 1)) }}
                                </div>
                                <div>
                                    <div class="font-bold text-gray-900 text-sm">{{ $customer['name'] }}</div>
                                    <div class="text-[10px] text-gray-400 font-mono">ID: #{{ $customer['id'] }}</div>
                                </div>
                            </div>
                        </td>

                        <td class="px-6 py-4 text-gray-600">
                            <div class="font-medium text-gray-800">{{ $customer['email'] }}</div>
                            <div class="text-[10px] text-gray-400 font-mono">{{ $customer['phone'] ?? 'No phone' }}</div>
                        </td>

                        <td class="px-6 py-4 text-center">
                            <span class="px-2.5 py-1 bg-gray-100 text-gray-700 font-bold rounded-lg text-xs">
                                {{ $customer['orders_count'] }} orders
                            </span>
                        </td>

                        <td class="px-6 py-4 font-bold text-gray-900 font-mono text-xs">
                            ₱{{ number_format($customer['total_spent'], 2) }}
                        </td>

                        <td class="px-6 py-4 text-gray-500 font-mono">
                            {{ $customer['created_at'] ? \Carbon\Carbon::parse($customer['created_at'])->format('M d, Y') : '—' }}
                        </td>

                        <td class="px-6 py-4">
                            @if($customer['status'] === 'banned')
                                <span class="px-2.5 py-1 bg-rose-50 text-rose-700 border border-rose-200 rounded-full font-bold text-[9px] uppercase tracking-wider">🚫 Banned</span>
                            @else
                                <span class="px-2.5 py-1 bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-full font-bold text-[9px] uppercase tracking-wider">Active</span>
                            @endif
                        </td>

                        <td class="px-6 py-4 text-right">
                            @if($customer['status'] === 'banned')
                                <form action="{{ route('superadmin.customers.unban', $customer['id']) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="px-3.5 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-[10px] font-bold uppercase tracking-wider transition-all cursor-pointer shadow-xs">
                                        Unban
                                    </button>
                                </form>
                            @else
                                <button type="button" 
                                    @click="openBan('{{ $customer['id'] }}', '{{ addslashes($customer['name']) }}')"
                                    class="px-3.5 py-1.5 bg-rose-50 text-rose-700 hover:bg-rose-600 hover:text-white border border-rose-200 rounded-xl text-[10px] font-bold uppercase tracking-wider transition-all cursor-pointer shadow-xs">
                                    Ban Account
                                </button>
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
        <div class="px-6 py-4 border-t border-gray-100 bg-gray-50/50">
            {{ $customers->links() }}
        </div>
        @endif
    </div>

    <!-- ── Interactive Ban Account Modal ── -->
    <div x-show="banModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" x-cloak>
        <div class="fixed inset-0 bg-black/50 backdrop-blur-xs transition-opacity" @click="banModal = false"></div>
        
        <div class="relative bg-white rounded-3xl border border-gray-100 shadow-2xl w-full max-w-lg p-6 sm:p-8 space-y-6 z-10">
            <div class="flex items-start justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-rose-50 text-rose-600 border border-rose-200 flex items-center justify-center font-bold text-lg shrink-0">
                        🚫
                    </div>
                    <div>
                        <h3 class="font-serif text-lg font-bold text-gray-900">Suspend Customer Account</h3>
                        <p class="text-xs text-gray-500 mt-0.5">Account: <strong class="text-gray-900 font-bold" x-text="banCustomerName"></strong></p>
                    </div>
                </div>
                <button type="button" @click="banModal = false" class="text-gray-400 hover:text-gray-600 text-xl font-bold p-1 cursor-pointer">&times;</button>
            </div>

            <p class="text-xs text-gray-600 leading-relaxed bg-gray-50/80 p-3.5 rounded-2xl border border-gray-100">
                ⚠️ Banning this customer will prevent them from logging in, accessing their orders, or making purchases. <strong>The reason you provide below will be shown directly to them when they attempt to log in.</strong>
            </p>

            <form :action="'/superadmin/customers/' + banCustomerId + '/ban'" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2">Quick Preset Reasons</label>
                    <div class="flex flex-wrap gap-1.5 mb-3">
                        <button type="button" @click="setPreset('Violation of Terms of Service')"
                            class="px-2.5 py-1 rounded-lg text-[10px] font-semibold bg-gray-100 hover:bg-gray-200 text-gray-700 transition-colors cursor-pointer">
                            Violation of Terms
                        </button>
                        <button type="button" @click="setPreset('Fraudulent transaction / payment dispute')"
                            class="px-2.5 py-1 rounded-lg text-[10px] font-semibold bg-gray-100 hover:bg-gray-200 text-gray-700 transition-colors cursor-pointer">
                            Fraud / Dispute
                        </button>
                        <button type="button" @click="setPreset('Abusive behavior towards sellers or staff')"
                            class="px-2.5 py-1 rounded-lg text-[10px] font-semibold bg-gray-100 hover:bg-gray-200 text-gray-700 transition-colors cursor-pointer">
                            Abusive Behavior
                        </button>
                        <button type="button" @click="setPreset('Suspicious or automated bot activity')"
                            class="px-2.5 py-1 rounded-lg text-[10px] font-semibold bg-gray-100 hover:bg-gray-200 text-gray-700 transition-colors cursor-pointer">
                            Bot Activity
                        </button>
                    </div>

                    <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1.5">Detailed Suspension Reason (Shown upon login) *</label>
                    <textarea name="reason" x-model="banReason" required rows="3"
                        placeholder="Explain why this account is being suspended..."
                        class="w-full p-3.5 bg-gray-50 border border-gray-200 text-gray-900 text-xs rounded-2xl focus:outline-none focus:border-[#C0422A] transition-colors leading-relaxed"></textarea>
                </div>

                <div class="flex items-center gap-3 pt-2">
                    <button type="button" @click="banModal = false"
                        class="flex-1 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl text-xs font-bold uppercase tracking-wider transition-all cursor-pointer">
                        Cancel
                    </button>
                    <button type="submit"
                        class="flex-1 py-3 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-xs font-bold uppercase tracking-wider transition-all cursor-pointer shadow-sm">
                        Confirm Ban Account
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
