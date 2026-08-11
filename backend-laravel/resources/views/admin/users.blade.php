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
    openBan(user) {
        this.banUserId = user.id;
        this.banUserName = user.name;
        this.banReason = '';
        this.banModal = true;
    },
    openDelete(user) {
        this.deleteUserId = user.id;
        this.deleteUserName = user.name;
        this.deleteModal = true;
    }
}">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div>
            <div class="text-[10px] font-bold text-[#C0422A] uppercase tracking-[0.2em] mb-1">User Registry</div>
            <h1 class="font-serif text-2xl sm:text-3xl font-bold text-black">
                Customer <span class="text-gray-300 font-light italic">Management</span>
            </h1>
        </div>
        <div class="flex items-center gap-2 text-xs font-bold text-gray-400">
            Total Customers: <span class="text-black text-base sm:text-lg font-black">{{ $users->total() }}</span>
        </div>
    </div>

    {{-- Filters --}}
    <form method="GET" class="flex flex-col sm:flex-row gap-3 items-stretch sm:items-center">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search customers by name, username, or email..."
            class="flex-1 min-w-0 px-4 py-3 bg-white border border-gray-100 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-[#C0422A]/10">
        <select name="status" class="px-4 py-3 bg-white border border-gray-100 rounded-xl text-xs font-bold outline-none cursor-pointer">
            <option value="">All Statuses</option>
            <option value="active"  {{ request('status') === 'active'  ? 'selected' : '' }}>Active</option>
            <option value="blocked" {{ request('status') === 'blocked' ? 'selected' : '' }}>Blocked</option>
            <option value="frozen"  {{ request('status') === 'frozen'  ? 'selected' : '' }}>Frozen</option>
        </select>
        <button type="submit" class="px-6 py-3 bg-black text-white rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-[#C0422A] transition-all">
            Filter
        </button>
        @if(request()->hasAny(['search','status']))
            <a href="/admin/users" class="px-4 py-3 border border-gray-200 text-gray-400 rounded-xl text-[10px] font-bold text-center hover:bg-gray-50 transition-all">Clear</a>
        @endif
    </form>

    {{-- Table --}}
    <div class="bg-white rounded-3xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="overflow-x-auto no-scrollbar">
            <table class="w-full text-left min-w-[550px]">
            <thead>
                <tr class="bg-gray-50/50 border-b border-gray-100">
                    <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-gray-400">Customer</th>
                    <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-gray-400 hidden lg:table-cell">Joined</th>
                    <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-gray-400">Status</th>
                    <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-gray-400 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($users as $user)
                    @php
                        $statusColors = ['active' => 'bg-green-50 text-green-600', 'blocked' => 'bg-red-50 text-red-600', 'frozen' => 'bg-amber-50 text-amber-600'];
                    @endphp
                    <tr class="hover:bg-gray-50/50 transition-all">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-xl bg-gray-100 flex items-center justify-center font-black text-sm text-gray-600 shrink-0 overflow-hidden">
                                    @if($user->profilePhoto)
                                        <img src="{{ str_starts_with($user->profilePhoto, 'http') || str_starts_with($user->profilePhoto, '/') ? $user->profilePhoto : asset('storage/' . $user->profilePhoto) }}" class="w-full h-full object-cover">
                                    @else
                                        {{ strtoupper(substr($user->name, 0, 1)) }}
                                    @endif
                                </div>
                                <div>
                                    <div class="text-sm font-bold text-black">{{ $user->name }}</div>
                                    <div class="text-[10px] text-gray-400">{{ $user->email }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-[11px] text-gray-400 hidden lg:table-cell">
                            {{ $user->createdAt ? $user->createdAt->format('M d, Y') : 'N/A' }}
                        </td>
                        <td class="px-6 py-4">
                            <span class="px-3 py-1 rounded-full text-[9px] font-black uppercase tracking-widest {{ $statusColors[$user->status] ?? 'bg-gray-50 text-gray-500' }}">
                                {{ $user->status }}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center justify-end gap-2">
                                @if($user->status === 'active')
                                    <button type="button" @click="openBan({{ json_encode($user) }})" class="px-4 py-2 bg-red-50 text-red-600 rounded-lg text-[9px] font-black uppercase tracking-widest hover:bg-red-500 hover:text-white transition-all">
                                        Ban Customer
                                    </button>
                                @else
                                    <form action="/admin/users/{{ $user->id }}/unban" method="POST">
                                        @csrf @method('PATCH')
                                        <button type="submit" class="px-4 py-2 bg-green-50 text-green-600 rounded-lg text-[9px] font-black uppercase tracking-widest hover:bg-green-500 hover:text-white transition-all">
                                            Restore Account
                                        </button>
                                    </form>
                                @endif
                                <button type="button" @click="openDelete({{ json_encode($user) }})" class="px-4 py-2 bg-gray-50 text-gray-500 rounded-lg text-[9px] font-black uppercase tracking-widest hover:bg-red-500 hover:text-white transition-all">
                                    Delete
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="py-20 text-center text-sm text-gray-300 italic">No customer accounts found.</td>
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
                    <label class="text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-1 block">Explanation / Reason *</label>
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
        <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="deleteModal = false"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md p-6 space-y-4">
            <h3 class="text-lg font-bold text-gray-900">Confirm Account Deletion</h3>
            <p class="text-xs text-gray-500 leading-relaxed">
                Are you sure you want to permanently delete <strong x-text="deleteUserName" class="text-black"></strong>? This action cannot be undone.
            </p>
            <form :action="'/admin/users/' + deleteUserId" method="POST" class="flex gap-3 pt-2">
                @csrf @method('DELETE')
                <button type="button" @click="deleteModal = false" class="flex-1 py-2.5 border border-gray-200 text-xs font-semibold text-gray-500 rounded-xl hover:bg-gray-50">Cancel</button>
                <button type="submit" class="flex-1 py-2.5 bg-red-600 text-white text-xs font-bold uppercase tracking-wider rounded-xl hover:bg-red-700">Permanently Delete</button>
            </form>
        </div>
    </div>
</div>
@endsection
