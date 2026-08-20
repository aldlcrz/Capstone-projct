@extends('layouts.admin')

@section('content')
<div class="space-y-8" x-data="{
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
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div>
            <div class="text-[10px] font-bold text-[#C0422A] uppercase tracking-[0.2em] mb-1">User Registry</div>
            <h1 class="font-serif text-2xl sm:text-3xl font-bold text-black">
                Customer <span class="text-[#C0420A] font-light italic">Management</span>
            </h1>
        </div>
        <div class="flex items-center gap-2 text-xs font-bold text-gray-600">
            Total Customers: <span class="text-black text-base sm:text-lg font-black">{{ $users->total() }}</span>
        </div>
    </div>

    @php
        $currentStatus = request('status');
    @endphp

    {{-- Filter Pills & Search Bar --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 pt-1">
        {{-- Pill Filters --}}
        <div class="flex items-center gap-2 overflow-x-auto no-scrollbar pb-1">
            {{-- ALL --}}
            <a href="{{ request()->fullUrlWithQuery(['status' => null, 'page' => 1]) }}"
               class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-[10px] sm:text-[11px] font-black uppercase tracking-wider transition-all {{ empty($currentStatus) ? 'bg-black text-white shadow-sm' : 'bg-white text-gray-700 border border-gray-200 hover:bg-gray-50' }}">
                <span>ALL</span>
                <span class="px-2 py-0.5 rounded-full text-[9px] font-black {{ empty($currentStatus) ? 'bg-white/20 text-white' : 'bg-gray-100 text-gray-600' }}">{{ $counts['all'] ?? $users->total() }}</span>
            </a>

            {{-- ACTIVE --}}
            <a href="{{ request()->fullUrlWithQuery(['status' => 'active', 'page' => 1]) }}"
               class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-[10px] sm:text-[11px] font-black uppercase tracking-wider transition-all {{ $currentStatus === 'active' ? 'bg-black text-white shadow-sm' : 'bg-white text-gray-700 border border-gray-200 hover:bg-gray-50' }}">
                <span>ACTIVE</span>
                <span class="px-2 py-0.5 rounded-full text-[9px] font-black {{ $currentStatus === 'active' ? 'bg-white/20 text-white' : 'bg-gray-100 text-gray-600' }}">{{ $counts['active'] ?? 0 }}</span>
            </a>

            {{-- BLOCKED / BANNED --}}
            <a href="{{ request()->fullUrlWithQuery(['status' => 'blocked', 'page' => 1]) }}"
               class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-[10px] sm:text-[11px] font-black uppercase tracking-wider transition-all {{ $currentStatus === 'blocked' ? 'bg-black text-white shadow-sm' : 'bg-white text-gray-700 border border-gray-200 hover:bg-gray-50' }}">
                <span>BLOCKED</span>
                <span class="px-2 py-0.5 rounded-full text-[9px] font-black {{ $currentStatus === 'blocked' ? 'bg-white/20 text-white' : 'bg-gray-100 text-gray-600' }}">{{ $counts['blocked'] ?? 0 }}</span>
            </a>

            {{-- FROZEN --}}
            <a href="{{ request()->fullUrlWithQuery(['status' => 'frozen', 'page' => 1]) }}"
               class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-[10px] sm:text-[11px] font-black uppercase tracking-wider transition-all {{ $currentStatus === 'frozen' ? 'bg-black text-white shadow-sm' : 'bg-white text-gray-700 border border-gray-200 hover:bg-gray-50' }}">
                <span>FROZEN</span>
                <span class="px-2 py-0.5 rounded-full text-[9px] font-black {{ $currentStatus === 'frozen' ? 'bg-white/20 text-white' : 'bg-gray-100 text-gray-600' }}">{{ $counts['frozen'] ?? 0 }}</span>
            </a>
        </div>

        {{-- Search Input --}}
        <form method="GET" class="flex items-center gap-2">
            @if(request('status'))
                <input type="hidden" name="status" value="{{ request('status') }}">
            @endif
            <div class="relative w-full sm:w-64">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search customers..." 
                       class="w-full pl-9 pr-4 py-2 bg-white border border-gray-200 rounded-full text-xs text-gray-800 placeholder:text-gray-400 focus:outline-none focus:border-[#C0422A]">
                <svg class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </div>
            @if(request('search'))
                <a href="{{ request()->fullUrlWithQuery(['search' => null, 'page' => 1]) }}" class="px-3 py-2 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-full text-[10px] font-bold">Clear</a>
            @endif
        </form>
    </div>

    {{-- Table --}}
    <div class="bg-white rounded-3xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="overflow-x-auto no-scrollbar">
            <table class="w-full text-left min-w-137.5">
            <thead>
                <tr class="bg-gray-50/50 border-b border-gray-100">
                    <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-gray-700">Customer</th>
                    <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-gray-700 hidden lg:table-cell">Joined</th>
                    <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-gray-700">Status</th>
                    <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-gray-700 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($users as $user)
                    @php
                        $statusColors = ['active' => 'bg-green-50 text-green-700 border border-green-200', 'blocked' => 'bg-red-50 text-red-700 border border-red-200', 'frozen' => 'bg-amber-50 text-amber-700 border border-amber-200'];
                    @endphp
                    <tr class="hover:bg-gray-50/50 transition-all">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-xl bg-gray-100 flex items-center justify-center font-black text-sm text-gray-700 shrink-0 overflow-hidden">
                                    @if($user->profilePhoto)
                                        <img src="{{ str_starts_with($user->profilePhoto, 'http') || str_starts_with($user->profilePhoto, '/') ? $user->profilePhoto : asset('storage/' . $user->profilePhoto) }}" class="w-full h-full object-cover">
                                    @else
                                        {{ strtoupper(substr($user->name, 0, 1)) }}
                                    @endif
                                </div>
                                <div>
                                    <div class="text-sm font-bold text-black">{{ $user->name }}</div>
                                    <div class="text-[10px] text-gray-500 font-medium">{{ $user->email }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-[11px] text-gray-600 font-medium hidden lg:table-cell">
                            {{ $user->createdAt ? $user->createdAt->format('M d, Y') : 'N/A' }}
                        </td>
                        <td class="px-6 py-4">
                            <span class="px-3 py-1 rounded-full text-[9px] font-black uppercase tracking-widest {{ $statusColors[$user->status] ?? 'bg-gray-50 text-gray-600 border border-gray-200' }}">
                                {{ $user->status }}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center justify-end gap-2">
                                @if($user->status === 'active')
                                    <button type="button" @click="openBan({{ json_encode($user) }})" class="px-4 py-2 bg-red-50 text-red-600 rounded-lg text-[9px] font-black uppercase tracking-widest hover:bg-red-500 hover:text-white transition-all cursor-pointer">
                                        Ban Customer
                                    </button>
                                @else
                                    <form action="/admin/users/{{ $user->id }}/unban" method="POST">
                                        @csrf @method('PATCH')
                                        <button type="submit" class="px-4 py-2 bg-green-50 text-green-600 rounded-lg text-[9px] font-black uppercase tracking-widest hover:bg-green-500 hover:text-white transition-all cursor-pointer">
                                            Restore Account
                                        </button>
                                    </form>
                                @endif
                                <button type="button" @click="openDelete({{ json_encode($user) }})" class="px-4 py-2 bg-gray-50 text-gray-600 rounded-lg text-[9px] font-black uppercase tracking-widest hover:bg-red-500 hover:text-white transition-all cursor-pointer">
                                    Delete
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="py-20 text-center text-sm text-gray-500 italic">No customer accounts found.</td>
                    </tr>
                @endforelse
            </tbody>
            </table>
        </div>
        <div class="px-6 py-4 border-t border-gray-50">
            {{ $users->withQueryString()->links() }}
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
