@extends('layouts.superadmin')

@section('content')
<div class="space-y-6" x-data="{
    freezeModal: false,
    freezeShopId: '',
    freezeShopName: '',
    freezeReason: '',
    setPreset(r) { this.freezeReason = r; },
    openFreeze(id, name) {
        this.freezeShopId = id;
        this.freezeShopName = name;
        this.freezeReason = 'Unpaid platform commission fee past grace period';
        this.freezeModal = true;
    }
}">
    {{-- ═══ PAGE HEADER + SEARCH BAR ═══ --}}
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
        <div class="text-left space-y-0.5 shrink-0">
            <div class="inline-flex items-center gap-2">
                <span class="text-[9px] font-black uppercase tracking-[0.25em] text-[#C0422A]">Artisan Governance</span>
                <span class="text-gray-300 text-xs">·</span>
                <span class="text-[9px] font-bold uppercase tracking-[0.2em] text-gray-400">Shops &amp; Accounts</span>
            </div>
            <h1 class="font-serif text-2xl sm:text-3xl font-bold text-gray-900 leading-tight">
                Sellers &amp; <span class="text-[#C0420A] font-light italic">Shops Directory</span>
            </h1>
            <p class="text-[11px] text-gray-400 font-medium">Supreme oversight of registered artisan shops, sales volume, commission dues, and shop statuses</p>
        </div>

        {{-- Search Bar --}}
        <div class="flex-1 max-w-xl lg:max-w-md w-full">
            <form action="{{ route('superadmin.sellers') }}" method="GET" class="flex items-center gap-2 w-full">
                <input type="hidden" name="status" value="{{ $status }}">
                <div class="relative w-full">
                    <input type="text" name="search" value="{{ $search }}"
                           placeholder="Search shop or artisan name..."
                           class="w-full pl-11 pr-5 py-2.5 bg-white border border-gray-200 rounded-full text-sm text-gray-800 placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-[#C0422A]/20 focus:border-[#C0422A] shadow-xs transition-all">
                    <svg class="w-5 h-5 text-gray-400 absolute left-4 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>
                @if($search)
                    <a href="{{ route('superadmin.sellers', ['status' => $status]) }}" class="px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-full text-xs font-bold transition-all shrink-0">Clear</a>
                @endif
            </form>
        </div>
    </div>

    {{-- ═══ STATS BAR / STATUS FILTERS ═══ --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        {{-- All Shops --}}
        <a href="{{ route('superadmin.sellers', ['status' => 'all', 'search' => $search]) }}"
           class="group relative rounded-2xl px-4 py-3 flex items-center justify-between border transition-all duration-200 cursor-pointer {{ $status === 'all' ? 'bg-white border-gray-900 ring-2 ring-gray-900/15 shadow-sm -translate-y-0.5' : 'bg-white border-gray-100 hover:border-gray-300 hover:shadow-sm hover:-translate-y-0.5' }}">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-xl flex items-center justify-center shrink-0 transition-colors {{ $status === 'all' ? 'bg-gray-900 text-white' : 'bg-gray-100 text-gray-500 group-hover:bg-gray-200' }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                </div>
                <div>
                    <div class="text-xs font-black text-gray-900 leading-none">All Shops</div>
                    <div class="text-[9px] font-bold uppercase tracking-wider text-gray-400 mt-1">Directory</div>
                </div>
            </div>
            @if($status === 'all')
                <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[8px] font-black uppercase tracking-wider bg-gray-900 text-white">Active</span>
            @endif
        </a>

        {{-- Active Shops --}}
        <a href="{{ route('superadmin.sellers', ['status' => 'active', 'search' => $search]) }}"
           class="group relative rounded-2xl px-4 py-3 flex items-center justify-between border transition-all duration-200 cursor-pointer {{ $status === 'active' ? 'bg-emerald-50/50 border-emerald-600 ring-2 ring-emerald-600/20 shadow-sm -translate-y-0.5' : 'bg-white border-green-100 hover:border-emerald-300 hover:shadow-sm hover:-translate-y-0.5' }}">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-xl flex items-center justify-center shrink-0 transition-colors {{ $status === 'active' ? 'bg-emerald-600 text-white' : 'bg-green-50 text-green-600 group-hover:bg-green-100' }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div>
                    <div class="text-xs font-black text-gray-900 leading-none">Active</div>
                    <div class="text-[9px] font-bold uppercase tracking-wider text-emerald-600 mt-1">Operational</div>
                </div>
            </div>
            @if($status === 'active')
                <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[8px] font-black uppercase tracking-wider bg-emerald-600 text-white">Active</span>
            @endif
        </a>

        {{-- Frozen Shops --}}
        <a href="{{ route('superadmin.sellers', ['status' => 'frozen', 'search' => $search]) }}"
           class="group relative rounded-2xl px-4 py-3 flex items-center justify-between border transition-all duration-200 cursor-pointer {{ $status === 'frozen' ? 'bg-blue-50/50 border-blue-600 ring-2 ring-blue-600/20 shadow-sm -translate-y-0.5' : 'bg-white border-blue-100 hover:border-blue-300 hover:shadow-sm hover:-translate-y-0.5' }}">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-xl flex items-center justify-center shrink-0 transition-colors {{ $status === 'frozen' ? 'bg-blue-600 text-white' : 'bg-blue-50 text-blue-600 group-hover:bg-blue-100' }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                </div>
                <div>
                    <div class="text-xs font-black text-gray-900 leading-none">Frozen</div>
                    <div class="text-[9px] font-bold uppercase tracking-wider text-blue-600 mt-1">Locked</div>
                </div>
            </div>
            @if($status === 'frozen')
                <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[8px] font-black uppercase tracking-wider bg-blue-600 text-white">Active</span>
            @endif
        </a>

        {{-- Unverified Shops --}}
        <a href="{{ route('superadmin.sellers', ['status' => 'unverified', 'search' => $search]) }}"
           class="group relative rounded-2xl px-4 py-3 flex items-center justify-between border transition-all duration-200 cursor-pointer {{ $status === 'unverified' ? 'bg-amber-50/50 border-amber-500 ring-2 ring-amber-500/20 shadow-sm -translate-y-0.5' : 'bg-white border-amber-100 hover:border-amber-300 hover:shadow-sm hover:-translate-y-0.5' }}">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-xl flex items-center justify-center shrink-0 transition-colors {{ $status === 'unverified' ? 'bg-amber-500 text-white' : 'bg-amber-50 text-amber-600 group-hover:bg-amber-100' }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                </div>
                <div>
                    <div class="text-xs font-black text-gray-900 leading-none">Unverified</div>
                    <div class="text-[9px] font-bold uppercase tracking-wider text-amber-600 mt-1">Pending</div>
                </div>
            </div>
            @if($status === 'unverified')
                <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[8px] font-black uppercase tracking-wider bg-amber-500 text-white">Active</span>
            @endif
        </a>
    </div>

    <!-- Shops Table -->
    <div class="bg-white border border-gray-100 rounded-2xl overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="bg-[#F8F7F4] border-b border-gray-100 text-gray-400 uppercase tracking-widest font-bold text-[9px]">
                        <th class="px-6 py-3.5">Shop &amp; Artisan</th>
                        <th class="px-6 py-3.5">Contact</th>
                        <th class="px-6 py-3.5 text-center">Catalog</th>
                        <th class="px-6 py-3.5">Gross Sales</th>
                        <th class="px-6 py-3.5">Fee ({{ $rate }}%)</th>
                        <th class="px-6 py-3.5">Unpaid Debt</th>
                        <th class="px-6 py-3.5">Status</th>
                        <th class="px-6 py-3.5 text-right">Governance Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($sellers as $seller)
                    <tr class="hover:bg-gray-50/80 transition-colors">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-xl bg-black text-white flex items-center justify-center font-bold text-xs shrink-0">
                                    {{ strtoupper(substr($seller['shop_name'], 0, 1)) }}
                                </div>
                                <div>
                                    <div class="font-bold text-gray-900 flex items-center gap-1.5">
                                        <span>{{ $seller['shop_name'] }}</span>
                                        @if($seller['is_verified'])
                                            <span class="inline-flex items-center px-1.5 py-0.2 bg-blue-50 border border-blue-200 text-blue-700 text-[8px] font-bold rounded-full">✓ Verified</span>
                                        @endif
                                    </div>
                                    <div class="text-[10px] text-gray-400">Owner: {{ $seller['name'] }}</div>
                                </div>
                            </div>
                        </td>

                        <td class="px-6 py-4 text-gray-600">
                            <div class="font-medium text-gray-800">{{ $seller['email'] }}</div>
                            <div class="text-[10px] text-gray-400 font-mono">{{ $seller['phone'] }}</div>
                        </td>

                        <td class="px-6 py-4 text-center">
                            <span class="px-2.5 py-1 bg-gray-100 text-gray-700 font-bold rounded-lg text-xs">
                                {{ $seller['products_count'] }} items
                            </span>
                        </td>

                        <td class="px-6 py-4 font-bold text-gray-900 font-mono text-xs">
                            ₱{{ number_format($seller['total_sales'], 2) }}
                        </td>

                        <td class="px-6 py-4 font-bold text-[#C0422A] font-mono text-xs">
                            ₱{{ number_format($seller['total_profit'], 2) }}
                        </td>

                        <td class="px-6 py-4 font-mono text-xs">
                            @if($seller['unpaid_debt'] > 0)
                                <span class="font-bold text-rose-600">₱{{ number_format($seller['unpaid_debt'], 2) }}</span>
                            @else
                                <span class="text-gray-400">₱0.00</span>
                            @endif
                        </td>

                        <td class="px-6 py-4">
                            @if($seller['status'] === 'frozen')
                                <span class="px-2.5 py-1 bg-blue-50 text-blue-700 border border-blue-200 rounded-full font-bold text-[9px] uppercase tracking-wider">❄️ Frozen</span>
                            @else
                                <span class="px-2.5 py-1 bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-full font-bold text-[9px] uppercase tracking-wider">Active</span>
                            @endif
                        </td>

                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end gap-2">
                                {{-- Freeze / Unfreeze --}}
                                @if($seller['status'] === 'frozen')
                                    <form action="{{ route('superadmin.shops.unfreeze', $seller['id']) }}" method="POST">
                                        @csrf @method('PATCH')
                                        <button type="submit" class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-[10px] font-bold uppercase tracking-wider transition-all cursor-pointer shadow-xs">
                                            Unfreeze
                                        </button>
                                    </form>
                                @else
                                    <button type="button" 
                                        @click="openFreeze('{{ $seller['id'] }}', '{{ addslashes($seller['shop_name']) }}')"
                                        class="px-3 py-1.5 bg-blue-50 text-blue-700 hover:bg-blue-600 hover:text-white border border-blue-200 rounded-xl text-[10px] font-bold uppercase tracking-wider transition-all cursor-pointer shadow-xs">
                                        Freeze
                                    </button>
                                @endif

                                {{-- Verification Toggle --}}
                                @if($seller['is_verified'])
                                    <form action="{{ route('superadmin.sellers.unverify', $seller['id']) }}" method="POST">
                                        @csrf @method('PATCH')
                                        <button type="submit" class="px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl text-[10px] font-bold uppercase tracking-wider transition-all cursor-pointer" title="Remove Verified Badge">
                                            Unverify
                                        </button>
                                    </form>
                                @else
                                    <form action="{{ route('superadmin.sellers.verify', $seller['id']) }}" method="POST">
                                        @csrf @method('PATCH')
                                        <button type="submit" class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-[10px] font-bold uppercase tracking-wider transition-all cursor-pointer" title="Grant Verified Badge">
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
        <div class="px-6 py-4 border-t border-gray-100 bg-gray-50/50">
            {{ $sellers->links() }}
        </div>
        @endif
    </div>

    <!-- ── Interactive Freeze Shop Modal ── -->
    <div x-show="freezeModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" x-cloak>
        <div class="fixed inset-0 bg-black/50 backdrop-blur-xs transition-opacity" @click="freezeModal = false"></div>
        
        <div class="relative bg-white rounded-3xl border border-gray-100 shadow-2xl w-full max-w-lg p-6 sm:p-8 space-y-6 z-10">
            <div class="flex items-start justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-blue-50 text-blue-600 border border-blue-200 flex items-center justify-center font-bold text-lg shrink-0">
                        ❄️
                    </div>
                    <div>
                        <h3 class="font-serif text-lg font-bold text-gray-900">Freeze Artisan Shop</h3>
                        <p class="text-xs text-gray-500 mt-0.5">Shop: <strong class="text-gray-900 font-bold" x-text="freezeShopName"></strong></p>
                    </div>
                </div>
                <button type="button" @click="freezeModal = false" class="text-gray-400 hover:text-gray-600 text-xl font-bold p-1 cursor-pointer">&times;</button>
            </div>

            <p class="text-xs text-gray-600 leading-relaxed bg-gray-50/80 p-3.5 rounded-2xl border border-gray-100">
                Freezing this shop will prevent the seller from receiving new orders and hide checkout from the marketplace until unresolved commission fees or disputes are settled. <strong>The reason below will be displayed when they attempt to log in.</strong>
            </p>

            <form :action="'/superadmin/shops/' + freezeShopId + '/freeze'" method="POST" class="space-y-4">
                @csrf @method('PATCH')
                <div>
                    <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2">Quick Presets</label>
                    <div class="flex flex-wrap gap-1.5 mb-3">
                        <button type="button" @click="setPreset('Unpaid platform commission fee past due date')"
                            class="px-2.5 py-1 rounded-lg text-[10px] font-semibold bg-gray-100 hover:bg-gray-200 text-gray-700 transition-colors cursor-pointer">
                            Unpaid Commission
                        </button>
                        <button type="button" @click="setPreset('Policy violation / counterfeit or prohibited listings')"
                            class="px-2.5 py-1 rounded-lg text-[10px] font-semibold bg-gray-100 hover:bg-gray-200 text-gray-700 transition-colors cursor-pointer">
                            Policy Violation
                        </button>
                        <button type="button" @click="setPreset('High dispute rate / unfulfilled customer orders')"
                            class="px-2.5 py-1 rounded-lg text-[10px] font-semibold bg-gray-100 hover:bg-gray-200 text-gray-700 transition-colors cursor-pointer">
                            Unfulfilled Orders
                        </button>
                        <button type="button" @click="setPreset('Administrative investigation in progress')"
                            class="px-2.5 py-1 rounded-lg text-[10px] font-semibold bg-gray-100 hover:bg-gray-200 text-gray-700 transition-colors cursor-pointer">
                            Admin Investigation
                        </button>
                    </div>

                    <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1.5">Reason for Freeze *</label>
                    <textarea name="reason" x-model="freezeReason" required rows="3"
                        placeholder="Explain why this artisan shop is being frozen..."
                        class="w-full p-3.5 bg-gray-50 border border-gray-200 text-gray-900 text-xs rounded-2xl focus:outline-none focus:border-[#C0422A] transition-colors leading-relaxed"></textarea>
                </div>

                <div class="flex items-center gap-3 pt-2">
                    <button type="button" @click="freezeModal = false"
                        class="flex-1 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl text-xs font-bold uppercase tracking-wider transition-all cursor-pointer">
                        Cancel
                    </button>
                    <button type="submit"
                        class="flex-1 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold uppercase tracking-wider transition-all cursor-pointer shadow-sm">
                        Confirm Freeze
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
