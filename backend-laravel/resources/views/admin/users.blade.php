@extends('layouts.admin')

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
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
        {{-- Left: Title & Subtitle --}}
        <div class="text-left space-y-0.5 shrink-0">
            <div class="inline-flex items-center gap-2">
                <span class="text-[9px] font-black uppercase tracking-[0.25em] text-[#C0422A]">User Registry</span>
                <span class="text-gray-300 text-xs">·</span>
                <span class="text-[9px] font-bold uppercase tracking-[0.2em] text-gray-400">Admin Center</span>
            </div>
            <h1 class="font-serif text-2xl sm:text-3xl font-bold text-gray-900 leading-tight">
                Customer <span class="text-[#C0420A] font-light italic">Management</span>
            </h1>
            <p class="text-[11px] text-gray-400 font-medium">Manage registered marketplace buyers and their account status</p>
        </div>

        {{-- Larger Search Bar in front of Customer Management --}}
        <div class="flex-1 max-w-xl lg:max-w-2xl w-full">
            <form method="GET" class="flex items-center gap-2 w-full">
                @if(request('status'))
                    <input type="hidden" name="status" value="{{ request('status') }}">
                @endif
                <div class="relative w-full">
                    <input type="text" name="search" value="{{ request('search') }}"
                           placeholder="Search customers by name, email..."
                           class="w-full pl-11 pr-5 py-2.5 bg-white border border-gray-200 rounded-full text-sm text-gray-800 placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-[#C0422A]/20 focus:border-[#C0422A] shadow-xs transition-all">
                    <svg class="w-5 h-5 text-gray-400 absolute left-4 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>
                @if(request('search'))
                    <a href="{{ request()->fullUrlWithQuery(['search' => null, 'page' => 1]) }}" class="px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-full text-xs font-bold transition-all shrink-0">Clear</a>
                @endif
            </form>
        </div>
    </div>

    {{-- ═══ STATS BAR / STATUS FILTERS ═══ --}}
    @php
        $currentStatus = request('status');
        $isTotal   = empty($currentStatus) || $currentStatus === 'all';
        $isActive  = $currentStatus === 'active';
        $isBlocked = $currentStatus === 'blocked';
        $isFrozen  = $currentStatus === 'frozen';
    @endphp
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        {{-- Total --}}
        <a href="{{ request()->fullUrlWithQuery(['status' => null, 'page' => 1]) }}"
           class="group relative rounded-2xl px-4 py-3 flex items-center justify-between border transition-all duration-200 cursor-pointer {{ $isTotal ? 'bg-white border-gray-900 ring-2 ring-gray-900/15 shadow-sm -translate-y-0.5' : 'bg-white border-gray-100 hover:border-gray-300 hover:shadow-sm hover:-translate-y-0.5' }}">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-xl flex items-center justify-center shrink-0 transition-colors {{ $isTotal ? 'bg-gray-900 text-white' : 'bg-gray-100 text-gray-500 group-hover:bg-gray-200' }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                </div>
                <div>
                    <div class="text-base sm:text-lg font-black text-gray-900 leading-none">{{ $counts['all'] ?? $users->total() }}</div>
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
        <a href="{{ request()->fullUrlWithQuery(['status' => 'active', 'page' => 1]) }}"
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
                    <span class="w-1 h-1 rounded-full bg-emerald-200"></span> Active
                </span>
            @endif
        </a>

        {{-- Blocked --}}
        <a href="{{ request()->fullUrlWithQuery(['status' => 'blocked', 'page' => 1]) }}"
           class="group relative rounded-2xl px-4 py-3 flex items-center justify-between border transition-all duration-200 cursor-pointer {{ $isBlocked ? 'bg-red-50/50 border-red-600 ring-2 ring-red-600/20 shadow-sm -translate-y-0.5' : 'bg-white border-red-100 hover:border-red-300 hover:shadow-sm hover:-translate-y-0.5' }}">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-xl flex items-center justify-center shrink-0 transition-colors {{ $isBlocked ? 'bg-red-600 text-white' : 'bg-red-50 text-red-500 group-hover:bg-red-100' }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                </div>
                <div>
                    <div class="text-base sm:text-lg font-black text-gray-900 leading-none">{{ $counts['blocked'] ?? 0 }}</div>
                    <div class="text-[9px] font-bold uppercase tracking-wider text-red-500 mt-0.5">Blocked</div>
                </div>
            </div>
            @if($isBlocked)
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[8px] font-black uppercase tracking-wider bg-red-600 text-white shadow-xs">
                    <span class="w-1 h-1 rounded-full bg-red-200"></span> Active
                </span>
            @endif
        </a>

        {{-- Frozen --}}
        <a href="{{ request()->fullUrlWithQuery(['status' => 'frozen', 'page' => 1]) }}"
           class="group relative rounded-2xl px-4 py-3 flex items-center justify-between border transition-all duration-200 cursor-pointer {{ $isFrozen ? 'bg-amber-50/50 border-amber-500 ring-2 ring-amber-500/20 shadow-sm -translate-y-0.5' : 'bg-white border-amber-100 hover:border-amber-300 hover:shadow-sm hover:-translate-y-0.5' }}">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-xl flex items-center justify-center shrink-0 transition-colors {{ $isFrozen ? 'bg-amber-500 text-white' : 'bg-amber-50 text-amber-500 group-hover:bg-amber-100' }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
                </div>
                <div>
                    <div class="text-base sm:text-lg font-black text-gray-900 leading-none">{{ $counts['frozen'] ?? 0 }}</div>
                    <div class="text-[9px] font-bold uppercase tracking-wider text-amber-500 mt-0.5">Frozen</div>
                </div>
            </div>
            @if($isFrozen)
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[8px] font-black uppercase tracking-wider bg-amber-500 text-white shadow-xs">
                    <span class="w-1 h-1 rounded-full bg-amber-200"></span> Active
                </span>
            @endif
        </a>
    </div>

    {{-- ═══ RESULTS & USER TABLE ═══ --}}
    <div class="space-y-2">
        {{-- Results count bar --}}
        <div class="flex items-center justify-between px-1">
            <div class="text-[11px] font-bold text-gray-400">
                Showing <span class="text-gray-900 font-black">{{ $users->total() }}</span> {{ $users->total() === 1 ? 'customer' : 'customers' }}
                @if(request('status'))
                    <span class="text-gray-400">· Filtered by <span class="capitalize font-black text-gray-700">{{ request('status') }}</span></span>
                @endif
                @if(request('search'))
                    <span class="text-gray-400">· Matching "<span class="font-bold text-gray-700">{{ request('search') }}</span>"</span>
                @endif
            </div>
        </div>

        {{-- Table card --}}
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
            <div class="overflow-x-auto no-scrollbar">
                <table class="w-full text-left min-w-145">
                    <thead>
                        <tr class="border-b border-gray-100 bg-gray-50/60">
                            <th class="px-5 py-2.5 text-[9px] font-black uppercase tracking-widest text-gray-400 w-[38%]">Customer</th>
                            <th class="px-4 py-2.5 text-[9px] font-black uppercase tracking-widest text-gray-400 w-[22%] hidden lg:table-cell">Joined</th>
                            <th class="px-4 py-2.5 text-[9px] font-black uppercase tracking-widest text-gray-400 w-[18%]">Status</th>
                            <th class="px-5 py-2.5 text-[9px] font-black uppercase tracking-widest text-gray-400 w-[22%] text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @forelse($users as $user)
                            @php
                                $statusConfig = [
                                    'active'  => ['dot' => 'bg-emerald-500', 'pill' => 'bg-emerald-50 text-emerald-700 border border-emerald-200/60',  'ring' => 'ring-emerald-200/70'],
                                    'blocked' => ['dot' => 'bg-rose-500',   'pill' => 'bg-rose-50 text-rose-700 border border-rose-200/60',        'ring' => 'ring-rose-200/70'],
                                    'frozen'  => ['dot' => 'bg-amber-500', 'pill' => 'bg-amber-50 text-amber-700 border border-amber-200/60',  'ring' => 'ring-amber-200/70'],
                                ];
                                $sc = $statusConfig[$user->status] ?? ['dot' => 'bg-gray-400', 'pill' => 'bg-gray-50 text-gray-600 border border-gray-200/60', 'ring' => 'ring-gray-200'];
                            @endphp
                            <tr class="hover:bg-gray-50/50 transition-colors group">
                                {{-- Customer Identity --}}
                                <td class="px-5 py-2">
                                    <div class="flex items-center gap-2.5">
                                        <div class="w-8 h-8 rounded-full bg-gray-100 ring-2 {{ $sc['ring'] }} flex items-center justify-center font-bold text-xs text-gray-700 shrink-0 overflow-hidden transition-all shadow-xs"
                                             style="width: 32px; height: 32px; min-width: 32px; min-height: 32px; max-width: 32px; max-height: 32px;">
                                            @if($user->profilePhoto)
                                                <img src="{{ str_starts_with($user->profilePhoto, 'http') || str_starts_with($user->profilePhoto, '/') ? $user->profilePhoto : asset('storage/' . $user->profilePhoto) }}"
                                                     class="w-full h-full object-cover"
                                                     style="width: 32px; height: 32px; min-width: 32px; min-height: 32px; max-width: 32px; max-height: 32px; object-fit: cover;">
                                            @else
                                                <span class="text-xs font-bold text-gray-600">{{ strtoupper(substr($user->name, 0, 1)) }}</span>
                                            @endif
                                        </div>
                                        <div class="min-w-0">
                                            <div class="text-xs font-bold text-gray-900 truncate leading-tight">{{ $user->name }}</div>
                                            <div class="text-[10px] text-gray-400 font-medium truncate leading-tight mt-0.5">{{ $user->email }}</div>
                                        </div>
                                    </div>
                                </td>
                                {{-- Joined Date --}}
                                <td class="px-4 py-2 hidden lg:table-cell">
                                    <span class="text-[11px] text-gray-500 font-medium whitespace-nowrap">{{ $user->createdAt ? $user->createdAt->format('M d, Y') : 'N/A' }}</span>
                                </td>
                                {{-- Status --}}
                                <td class="px-4 py-2">
                                    <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[10px] font-bold capitalize {{ $sc['pill'] }}">
                                        <span class="w-1.5 h-1.5 rounded-full {{ $sc['dot'] }}"></span>
                                        {{ $user->status }}
                                    </span>
                                </td>
                                {{-- Actions --}}
                                <td class="px-5 py-2">
                                    <div class="flex items-center justify-end gap-1.5">
                                        @if($user->status === 'active')
                                            <button type="button" @click="openBan({{ json_encode($user) }})"
                                                class="px-2.5 py-1 bg-rose-50 text-rose-600 border border-rose-200/80 rounded-lg text-[10px] font-bold uppercase tracking-wider hover:bg-rose-600 hover:text-white transition-all cursor-pointer">
                                                Ban
                                            </button>
                                        @else
                                            <form action="/admin/users/{{ $user->id }}/unban" method="POST" class="inline">
                                                @csrf @method('PATCH')
                                                <button type="submit"
                                                    class="px-2.5 py-1 bg-emerald-50 text-emerald-700 border border-emerald-200/80 rounded-lg text-[10px] font-bold uppercase tracking-wider hover:bg-emerald-600 hover:text-white transition-all cursor-pointer">
                                                    Restore
                                                </button>
                                            </form>
                                        @endif
                                        <button type="button" @click="openDelete({{ json_encode($user) }})"
                                            class="px-2.5 py-1 bg-gray-50 text-gray-500 border border-gray-200/80 rounded-lg text-[10px] font-bold uppercase tracking-wider hover:bg-rose-600 hover:text-white hover:border-rose-600 transition-all cursor-pointer">
                                            Delete
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4">
                                    <div class="py-10 text-center">
                                        <div class="w-12 h-12 bg-gray-50 rounded-2xl flex items-center justify-center mx-auto mb-2.5 border border-gray-100">
                                            <svg class="w-5 h-5 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                        </div>
                                        <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">No customers found</p>
                                        <p class="text-[10px] text-gray-300 mt-0.5">Try adjusting your search or filter criteria</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{-- Pagination --}}
            <div class="px-5 py-2.5 border-t border-gray-50 bg-gray-50/30">
                {{ $users->withQueryString()->links() }}
            </div>
        </div>
    </div>


    {{-- Ban Confirmation Modal --}}
    <div x-show="banModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" x-cloak>
        <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="banModal = false"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md p-6 space-y-4">
            <h3 class="text-lg font-bold text-gray-900">Ban Customer Account</h3>
            <p class="text-xs text-gray-500 leading-relaxed">
                Are you sure you want to ban customer <strong x-text="banUserName" class="text-black"></strong>? Please provide a reason below for record keeping and user notification.
            </p>
            <form :action="'/admin/users/' + banUserId + '/ban'" method="POST" class="space-y-4">
                @csrf @method('PATCH')
                <div>
                    <label class="text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-1.5 block">Quick Presets</label>
                    <div class="flex flex-wrap gap-1.5 mb-3">
                        <button type="button" @click="banReason = 'Violation of Terms of Service'"
                            class="px-2.5 py-1 rounded-lg text-[10px] font-semibold bg-gray-100 hover:bg-gray-200 text-gray-700 transition-colors">
                            Terms Violation
                        </button>
                        <button type="button" @click="banReason = 'Fraudulent transaction / payment dispute'"
                            class="px-2.5 py-1 rounded-lg text-[10px] font-semibold bg-gray-100 hover:bg-gray-200 text-gray-700 transition-colors">
                            Payment Fraud
                        </button>
                        <button type="button" @click="banReason = 'Abusive behavior towards sellers or staff'"
                            class="px-2.5 py-1 rounded-lg text-[10px] font-semibold bg-gray-100 hover:bg-gray-200 text-gray-700 transition-colors">
                            Abusive Behavior
                        </button>
                        <button type="button" @click="banReason = 'Suspicious or automated spam activity'"
                            class="px-2.5 py-1 rounded-lg text-[10px] font-semibold bg-gray-100 hover:bg-gray-200 text-gray-700 transition-colors">
                            Spam Activity
                        </button>
                    </div>

                    <label class="text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-1 block">Explanation / Reason (Shown to customer) *</label>
                    <textarea name="reason" x-model="banReason" required rows="3" placeholder="Specify why this account is being suspended..." class="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl text-xs outline-none focus:border-red-500"></textarea>
                </div>
                <div class="flex gap-3 pt-2">
                    <button type="button" @click="banModal = false" class="flex-1 py-2.5 border border-gray-200 text-xs font-semibold text-gray-500 rounded-xl hover:bg-gray-50">Cancel</button>
                    <button type="submit" class="flex-1 py-2.5 bg-red-600 text-white text-xs font-bold uppercase tracking-wider rounded-xl hover:bg-red-700">Confirm Ban</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Delete Confirmation Modal --}}
    <div x-show="deleteModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" x-cloak>
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="deleteModal = false"></div>
        <div class="relative bg-white rounded-3xl shadow-2xl w-full max-w-lg p-6 sm:p-7 space-y-5 z-10 border border-gray-100">
            <div class="flex items-start gap-3.5">
                <div class="w-11 h-11 rounded-2xl bg-red-100 flex items-center justify-center shrink-0">
                    <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                </div>
                <div class="min-w-0">
                    <h3 class="text-base sm:text-lg font-bold text-gray-900 leading-tight">Delete Customer Account</h3>
                    <p class="text-xs text-gray-500 mt-1">
                        Permanently purge <strong x-text="deleteUserName" class="text-black"></strong> and all associated customer account records.
                    </p>
                </div>
            </div>

            <div class="p-3 bg-red-50/80 border border-red-200 rounded-2xl text-[11px] text-red-800 leading-relaxed font-medium">
                ⚠️ <strong>Critical Warning:</strong> This action cannot be undone. All active sessions, authentication tokens, and customer records will be permanently deleted from the system.
            </div>

            <form :action="'/admin/users/' + deleteUserId" method="POST" class="space-y-4">
                @csrf @method('DELETE')
                
                <div>
                    <label class="text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-1.5 block">Quick Reason Presets</label>
                    <div class="flex flex-wrap gap-1.5 mb-2.5">
                        <button type="button" @click="deleteReason = 'Violation of platform customer terms and conditions'"
                            class="px-2.5 py-1 rounded-lg text-[10px] font-semibold bg-gray-100 hover:bg-gray-200 text-gray-700 transition-colors">
                            Terms Violation
                        </button>
                        <button type="button" @click="deleteReason = 'Requested by customer account closure'"
                            class="px-2.5 py-1 rounded-lg text-[10px] font-semibold bg-gray-100 hover:bg-gray-200 text-gray-700 transition-colors">
                            User Request
                        </button>
                        <button type="button" @click="deleteReason = 'Inactive or duplicate customer account cleanup'"
                            class="px-2.5 py-1 rounded-lg text-[10px] font-semibold bg-gray-100 hover:bg-gray-200 text-gray-700 transition-colors">
                            Inactive Account
                        </button>
                        <button type="button" @click="deleteReason = 'Fraudulent chargeback activity or abusive behavior'"
                            class="px-2.5 py-1 rounded-lg text-[10px] font-semibold bg-gray-100 hover:bg-gray-200 text-gray-700 transition-colors">
                            Fraud / Abuse
                        </button>
                    </div>

                    <label class="text-[10px] font-bold text-gray-600 uppercase tracking-widest mb-1 block">Reason for Permanent Deletion *</label>
                    <textarea name="reason" x-model="deleteReason" required rows="2.5" placeholder="Specify reason for deletion for platform audit logs..." class="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl text-xs outline-none focus:border-red-500 font-medium"></textarea>
                </div>

                <label class="flex items-start gap-2.5 cursor-pointer select-none pt-1">
                    <input type="checkbox" x-model="deleteConfirmChecked" class="mt-0.5 rounded border-gray-300 text-red-600 focus:ring-red-500 w-4 h-4 cursor-pointer">
                    <span class="text-[11px] text-gray-600 font-medium leading-snug">
                        I confirm that I want to permanently delete this customer account and understand that this action cannot be undone.
                    </span>
                </label>

                <div class="flex gap-3 pt-2">
                    <button type="button" @click="deleteModal = false" class="flex-1 py-2.5 border border-gray-200 text-xs font-bold text-gray-600 rounded-xl hover:bg-gray-50 transition-all cursor-pointer">
                        Cancel
                    </button>
                    <button type="submit" :disabled="!deleteConfirmChecked || !deleteReason.trim()" class="flex-1 py-2.5 bg-red-600 hover:bg-red-700 disabled:opacity-40 disabled:cursor-not-allowed text-white text-xs font-bold uppercase tracking-wider rounded-xl transition-all shadow-sm cursor-pointer">
                        Confirm &amp; Delete
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
