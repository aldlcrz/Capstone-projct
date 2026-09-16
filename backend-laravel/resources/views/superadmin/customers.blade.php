@extends('layouts.superadmin')

@section('content')
<div class="space-y-4" x-data="{
    banModal: false,
    banUserId: null,
    banUserName: '',
    banReason: '',
    deleteModal: false,
    deleteUserId: null,
    deleteUserName: '',
    deleteReason: '',
    deleteConfirmChecked: false,
    openBan(user) {
        this.banUserId = user.id;
        this.banUserName = user.name;
        this.banReason = 'Violation of platform customer terms';
        this.banModal = true;
    },
    openDelete(user) {
        this.deleteUserId = user.id;
        this.deleteUserName = user.name;
        this.deleteReason = '';
        this.deleteConfirmChecked = false;
        this.deleteModal = true;
    }
}">

    {{-- ═══ PAGE HEADER + SEARCH BAR ═══ --}}
    <div id="tour-superadmin-users-header" class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
        {{-- Left: Title & Subtitle --}}
        <div class="text-left space-y-0.5 shrink-0">
            <div class="inline-flex items-center gap-2">
                <span class="text-[9px] font-black uppercase tracking-[0.25em] text-[#C0422A]">User Registry</span>
                <span class="text-gray-300 text-xs">·</span>
                <span class="text-[9px] font-bold uppercase tracking-[0.2em] text-gray-400">Super Admin Center</span>
            </div>
            <div class="flex items-center gap-3 flex-wrap">
                <h1 class="font-serif text-2xl sm:text-3xl font-bold text-gray-900 leading-tight">
                    Customer <span class="text-[#C0420A] font-light italic">Management</span>
                </h1>
                {{-- Guide Button --}}
                <button type="button" 
                        onclick="window.startSpotlightTour ? window.startSpotlightTour('admin-customers-guide') : null"
                        class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full text-xs font-black uppercase tracking-wider transition-all shadow-xs cursor-pointer group shrink-0"
                        style="background-color: #FFF5F2; color: #C0422A; border: 1.5px solid #C0422A;"
                        onmouseover="this.style.backgroundColor='#C0422A'; this.style.color='#FFFFFF';"
                        onmouseout="this.style.backgroundColor='#FFF5F2'; this.style.color='#C0422A';"
                        title="Interactive Customer Management Guide">
                    <svg class="w-4 h-4 transition-transform group-hover:rotate-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span>Customer Guide</span>
                </button>
            </div>
            <p class="text-[11px] text-gray-400 font-medium">Manage registered marketplace buyers and their account access status</p>
        </div>

        {{-- Larger Search Bar --}}
        <div id="tour-superadmin-users-search" class="flex-1 max-w-xl lg:max-w-2xl w-full">
            <form method="GET" action="{{ route('superadmin.customers') }}" class="flex items-center gap-2 w-full">
                @if(request('status'))
                    <input type="hidden" name="status" value="{{ request('status') }}">
                @endif
                <div class="relative w-full">
                    <input type="text" name="search" value="{{ request('search', $search ?? '') }}"
                           placeholder="Search customers by name, email..."
                           class="w-full pl-11 pr-5 py-2.5 bg-white border border-gray-200 rounded-full text-sm text-gray-800 placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-[#C0422A]/20 focus:border-[#C0422A] shadow-xs transition-all">
                    <svg class="w-5 h-5 text-gray-400 absolute left-4 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>
                @if(request('search') || !empty($search))
                    <a href="{{ route('superadmin.customers', ['status' => request('status')]) }}" class="px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-full text-xs font-bold transition-all shrink-0">Clear</a>
                @endif
            </form>
        </div>
    </div>

    {{-- ═══ STATS BAR / STATUS FILTERS ═══ --}}
    @php
        $currentStatus = request('status', $status ?? '');
        $isTotal   = empty($currentStatus) || $currentStatus === 'all';
        $isActive  = $currentStatus === 'active';
        $isBlocked = $currentStatus === 'blocked' || $currentStatus === 'banned';
    @endphp
    <div id="tour-superadmin-users-stats" class="grid grid-cols-1 sm:grid-cols-3 gap-3">
        {{-- Total --}}
        <a href="{{ route('superadmin.customers', ['search' => request('search')]) }}"
           class="group relative rounded-2xl px-4 py-3 flex items-center justify-between border transition-all duration-200 cursor-pointer {{ $isTotal ? 'bg-white border-gray-900 ring-2 ring-gray-900/15 shadow-sm -translate-y-0.5' : 'bg-white border-gray-100 hover:border-gray-300 hover:shadow-sm hover:-translate-y-0.5' }}">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-xl flex items-center justify-center shrink-0 transition-colors {{ $isTotal ? 'bg-gray-900 text-white' : 'bg-gray-100 text-gray-500 group-hover:bg-gray-200' }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                </div>
                <div>
                    <div class="text-base sm:text-lg font-black text-gray-900 leading-none">{{ $counts['all'] ?? $customers->total() }}</div>
                    <div class="text-[9px] font-bold uppercase tracking-wider text-gray-400 mt-0.5">Total</div>
                </div>
            </div>
            @if($isTotal)
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[8px] font-black uppercase tracking-wider bg-gray-900 text-white shadow-xs">
                    <span class="w-1 h-1 rounded-full bg-emerald-400"></span> Active
                </span>
            @endif
        </a>

        {{-- Active --}}
        <a href="{{ route('superadmin.customers', ['status' => 'active', 'search' => request('search')]) }}"
           class="group relative rounded-2xl px-4 py-3 flex items-center justify-between border transition-all duration-200 cursor-pointer {{ $isActive ? 'bg-emerald-50/50 border-emerald-600 ring-2 ring-emerald-600/20 shadow-sm -translate-y-0.5' : 'bg-white border-green-100 hover:border-emerald-300 hover:shadow-sm hover:-translate-y-0.5' }}">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-xl flex items-center justify-center shrink-0 transition-colors {{ $isActive ? 'bg-emerald-600 text-white' : 'bg-green-50 text-green-600 group-hover:bg-green-100' }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div>
                    <div class="text-base sm:text-lg font-black text-gray-900 leading-none">{{ $counts['active'] ?? 0 }}</div>
                    <div class="text-[9px] font-bold uppercase tracking-wider text-green-600 mt-0.5">Active</div>
                </div>
            </div>
            @if($isActive)
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[8px] font-black uppercase tracking-wider bg-emerald-600 text-white shadow-xs">
                    <span class="w-1 h-1 rounded-full bg-white"></span> Active
                </span>
            @endif
        </a>

        {{-- Banned / Blocked --}}
        <a href="{{ route('superadmin.customers', ['status' => 'blocked', 'search' => request('search')]) }}"
           class="group relative rounded-2xl px-4 py-3 flex items-center justify-between border transition-all duration-200 cursor-pointer {{ $isBlocked ? 'bg-red-50/50 border-red-600 ring-2 ring-red-600/20 shadow-sm -translate-y-0.5' : 'bg-white border-red-100 hover:border-red-300 hover:shadow-sm hover:-translate-y-0.5' }}">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-xl flex items-center justify-center shrink-0 transition-colors {{ $isBlocked ? 'bg-red-600 text-white' : 'bg-red-50 text-red-500 group-hover:bg-red-100' }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                </div>
                <div>
                    <div class="text-base sm:text-lg font-black text-gray-900 leading-none">{{ $counts['blocked'] ?? 0 }}</div>
                    <div class="text-[9px] font-bold uppercase tracking-wider text-red-600 mt-0.5">Banned</div>
                </div>
            </div>
            @if($isBlocked)
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[8px] font-black uppercase tracking-wider bg-red-600 text-white shadow-xs">
                    <span class="w-1 h-1 rounded-full bg-white"></span> Active
                </span>
            @endif
        </a>
    </div>

    {{-- ═══ RESULTS & USERS TABLE ═══ --}}
    <div class="space-y-2">
        {{-- Results count bar --}}
        <div class="flex items-center justify-between px-1">
            <div class="text-[11px] font-bold text-gray-400">
                Showing <span class="text-gray-900 font-black">{{ $customers->total() }}</span> {{ $customers->total() === 1 ? 'customer' : 'customers' }}
                @if($currentStatus)
                    <span class="text-gray-400">· Filtered by <span class="capitalize font-black text-gray-700">{{ $currentStatus }}</span></span>
                @endif
                @if(request('search'))
                    <span class="text-gray-400">· Matching "<span class="font-bold text-gray-700">{{ request('search') }}</span>"</span>
                @endif
            </div>
        </div>

        {{-- Table Card --}}
        <div id="tour-superadmin-users-table" class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
            <div class="overflow-x-auto no-scrollbar">
                <table class="w-full text-left min-w-160">
                    <thead>
                        <tr class="border-b border-gray-100 bg-gray-50/60">
                            <th class="px-5 py-3 text-[9px] font-black uppercase tracking-widest text-gray-400 w-[38%]">Customer Details</th>
                            <th class="px-4 py-3 text-[9px] font-black uppercase tracking-widest text-gray-400 w-[18%] hidden sm:table-cell">Orders Placed</th>
                            <th class="px-4 py-3 text-[9px] font-black uppercase tracking-widest text-gray-400 w-[18%] hidden md:table-cell">Registered</th>
                            <th class="px-4 py-3 text-[9px] font-black uppercase tracking-widest text-gray-400 w-[12%]">Status</th>
                            <th id="tour-superadmin-users-actions" class="px-5 py-3 text-[9px] font-black uppercase tracking-widest text-gray-400 w-[14%] text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @forelse($customers as $user)
                            @php
                                $isBanned = in_array($user->status, ['blocked', 'banned']);
                            @endphp
                            <tr class="hover:bg-gray-50/50 transition-colors group">
                                {{-- User identity --}}
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-3.5">
                                        <div class="w-9 h-9 rounded-full bg-gray-100 ring-2 ring-gray-200 flex items-center justify-center font-bold text-xs text-gray-700 shrink-0 overflow-hidden shadow-xs"
                                             style="width: 36px; height: 36px; min-width: 36px; min-height: 36px; max-width: 36px; max-height: 36px;">
                                            @if($user->profilePhoto)
                                                <img src="{{ str_starts_with($user->profilePhoto, 'http') || str_starts_with($user->profilePhoto, '/') ? $user->profilePhoto : asset('storage/' . $user->profilePhoto) }}"
                                                     class="w-full h-full object-cover"
                                                     style="width: 36px; height: 36px; min-width: 36px; min-height: 36px; max-width: 36px; max-height: 36px; object-fit: cover;">
                                            @else
                                                <span class="text-xs font-bold text-gray-600">{{ strtoupper(substr($user->name, 0, 1)) }}</span>
                                            @endif
                                        </div>
                                        <div class="min-w-0">
                                            <div class="text-xs font-bold text-gray-900 truncate leading-snug">{{ $user->name }}</div>
                                            <div class="text-[10px] text-gray-400 font-medium truncate leading-tight mt-1">{{ $user->email }}</div>
                                            @if($isBanned && $user->violationReason)
                                                <div class="text-[9px] text-red-600 font-semibold truncate max-w-xs mt-1" title="{{ $user->violationReason }}">
                                                    Reason: {{ $user->violationReason }}
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </td>

                                {{-- Orders Placed --}}
                                <td class="px-4 py-4 hidden sm:table-cell">
                                    <div class="text-[11px] text-gray-600 font-medium">
                                        <strong class="text-gray-900 font-bold">{{ $user->orders_count ?? 0 }}</strong> {{ ($user->orders_count ?? 0) === 1 ? 'order' : 'orders' }}
                                    </div>
                                </td>

                                {{-- Registered Date --}}
                                <td class="px-4 py-4 hidden md:table-cell">
                                    <span class="text-[11px] text-gray-500 font-medium whitespace-nowrap">{{ $user->createdAt ? $user->createdAt->format('M d, Y') : '—' }}</span>
                                </td>

                                {{-- Status badge --}}
                                <td class="px-4 py-4">
                                    @if($isBanned)
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold bg-red-50 text-red-700 border border-red-200/80">
                                            <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>
                                            Banned
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200/80">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                            Active
                                        </span>
                                    @endif
                                </td>

                                {{-- Actions --}}
                                <td class="px-5 py-4">
                                    <div class="flex items-center justify-end gap-2">
                                        @if($isBanned)
                                            <form action="{{ route('superadmin.customers.unban', $user->id) }}" method="POST" class="inline">
                                                @csrf
                                                <button type="submit"
                                                    class="px-2.5 py-1 bg-emerald-50 text-emerald-700 border border-emerald-200/80 rounded-lg text-[10px] font-bold uppercase tracking-wider hover:bg-emerald-600 hover:text-white transition-all cursor-pointer">
                                                    Unban
                                                </button>
                                            </form>
                                        @else
                                            <button type="button" @click="openBan({{ json_encode($user) }})"
                                                class="px-2.5 py-1 bg-red-50 text-red-600 border border-red-200/80 rounded-lg text-[10px] font-bold uppercase tracking-wider hover:bg-red-600 hover:text-white transition-all cursor-pointer">
                                                Ban
                                            </button>
                                        @endif
                                        <button type="button" @click="openDelete({{ json_encode($user) }})"
                                            class="px-2.5 py-1 bg-gray-50 text-gray-400 border border-gray-200/80 rounded-lg text-[10px] font-bold uppercase tracking-wider hover:bg-red-50 hover:text-red-600 hover:border-red-200 transition-all cursor-pointer">
                                            Delete
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-5 py-12 text-center text-gray-400">
                                    <div class="w-12 h-12 bg-gray-50 rounded-2xl flex items-center justify-center mx-auto mb-3 border border-gray-100">
                                        <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                                    </div>
                                    <div class="text-sm font-bold text-gray-700">No Customers Found</div>
                                    <p class="text-xs text-gray-400 mt-0.5">Try refining your search or filter.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            @if($customers->hasPages())
                <div class="p-4 bg-gray-50/50 border-t border-gray-100">
                    {{ $customers->appends(['status' => request('status'), 'search' => request('search')])->links() }}
                </div>
            @endif
        </div>
    </div>

    {{-- ═══ BAN CUSTOMER MODAL ═══ --}}
    <div x-show="banModal" 
         x-cloak 
         style="display: none;"
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95">
        <div class="bg-white rounded-3xl p-6 sm:p-8 max-w-md w-full shadow-2xl border border-gray-100" @click.away="banModal = false">
            <div class="w-14 h-14 rounded-2xl bg-red-50 text-red-600 flex items-center justify-center mx-auto mb-4 border border-red-100">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
            </div>
            <h3 class="font-serif text-xl font-bold text-center text-[#3D2B1F] mb-1">Ban Customer Account</h3>
            <p class="text-xs text-gray-500 text-center mb-5">Prevent <span class="font-bold text-gray-900" x-text="banUserName"></span> from placing orders or logging into LumBarong.</p>

            <form :action="'/superadmin/customers/' + banUserId + '/ban'" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-1.5">Reason for Ban</label>
                    <textarea name="reason" rows="3" x-model="banReason" required
                              class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-xs font-medium focus:bg-white focus:outline-none focus:border-[#C0422A] transition-all"></textarea>
                </div>
                <div class="flex items-center gap-3 pt-2">
                    <button type="button" @click="banModal = false" class="flex-1 py-2.5 bg-gray-100 text-gray-700 text-xs font-bold rounded-xl hover:bg-gray-200 transition-all cursor-pointer">Cancel</button>
                    <button type="submit" class="flex-1 py-2.5 bg-red-600 text-white text-xs font-bold rounded-xl hover:bg-red-700 transition-all cursor-pointer shadow-xs">Confirm Ban</button>
                </div>
            </form>
        </div>
    </div>

    {{-- ═══ PERMANENT DELETE CUSTOMER MODAL ═══ --}}
    <div x-show="deleteModal" 
         x-cloak 
         style="display: none;"
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95">
        <div class="bg-white rounded-3xl p-6 sm:p-8 max-w-md w-full shadow-2xl border border-gray-100" @click.away="deleteModal = false">
            <div class="w-14 h-14 rounded-2xl bg-red-50 text-red-600 flex items-center justify-center mx-auto mb-4 border border-red-100">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
            </div>
            <h3 class="font-serif text-xl font-bold text-center text-[#3D2B1F] mb-1">Delete Customer Account</h3>
            <p class="text-xs text-gray-500 text-center mb-5">Permanently delete and archive the customer record for <span class="font-bold text-gray-900" x-text="deleteUserName"></span>.</p>

            <form :action="'/superadmin/customers/' + deleteUserId" method="POST" class="space-y-4">
                @csrf
                @method('DELETE')
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-1.5">Deletion Reason</label>
                    <input type="text" name="reason" x-model="deleteReason" placeholder="e.g. Account deletion requested by customer"
                           class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-xs font-medium focus:bg-white focus:outline-none focus:border-[#C0422A] transition-all">
                </div>
                <div class="flex items-center gap-2 pt-1">
                    <input type="checkbox" id="super_delete_confirm" x-model="deleteConfirmChecked" class="rounded border-gray-300 text-red-600 focus:ring-red-500">
                    <label for="super_delete_confirm" class="text-xs text-gray-600 cursor-pointer">I confirm that this action will permanently archive and delete this account.</label>
                </div>
                <div class="flex items-center gap-3 pt-2">
                    <button type="button" @click="deleteModal = false" class="flex-1 py-2.5 bg-gray-100 text-gray-700 text-xs font-bold rounded-xl hover:bg-gray-200 transition-all cursor-pointer">Cancel</button>
                    <button type="submit" :disabled="!deleteConfirmChecked" :class="deleteConfirmChecked ? 'bg-red-600 hover:bg-red-700 cursor-pointer' : 'bg-red-300 cursor-not-allowed'" class="flex-1 py-2.5 text-white text-xs font-bold rounded-xl transition-all shadow-xs">Delete Account</button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
