@extends('layouts.admin')

@section('content')
<div class="space-y-8">
    <div class="flex items-center justify-between">
        <div>
            <div class="text-[10px] font-bold text-[#C0422A] uppercase tracking-[0.2em] mb-1">User Registry</div>
            <h1 class="font-serif text-3xl font-bold text-black">
                @if(request('role') === 'seller')
                    Seller <span class="text-gray-300 font-light italic">Registry</span>
                @elseif(request('role') === 'admin')
                    Admin <span class="text-gray-300 font-light italic">Accounts</span>
                @elseif(request('role') === 'all')
                    System <span class="text-gray-300 font-light italic">Users</span>
                @else
                    Customer <span class="text-gray-300 font-light italic">Management</span>
                @endif
            </h1>
        </div>
        <div class="flex items-center gap-2 text-xs font-bold text-gray-400">
            Total: <span class="text-black text-lg font-black">{{ $users->total() }}</span>
        </div>
    </div>

    {{-- Filters --}}
    <form method="GET" class="flex flex-wrap gap-3 items-center">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by name or email..."
            class="flex-1 min-w-[220px] px-4 py-3 bg-white border border-gray-100 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-[#C0422A]/10">
        <select name="role" class="px-4 py-3 bg-white border border-gray-100 rounded-xl text-xs font-bold outline-none">
            <option value="all" {{ request('role') === 'all' ? 'selected' : '' }}>All Roles</option>
            <option value="customer" {{ (request('role') === 'customer' || !request('role')) ? 'selected' : '' }}>Customer</option>
            <option value="seller"   {{ request('role') === 'seller'   ? 'selected' : '' }}>Seller</option>
            <option value="admin"    {{ request('role') === 'admin'    ? 'selected' : '' }}>Admin</option>
        </select>
        <select name="status" class="px-4 py-3 bg-white border border-gray-100 rounded-xl text-xs font-bold outline-none">
            <option value="">All Statuses</option>
            <option value="active"  {{ request('status') === 'active'  ? 'selected' : '' }}>Active</option>
            <option value="blocked" {{ request('status') === 'blocked' ? 'selected' : '' }}>Blocked</option>
            <option value="frozen"  {{ request('status') === 'frozen'  ? 'selected' : '' }}>Frozen</option>
        </select>
        <button type="submit" class="px-6 py-3 bg-black text-white rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-[#C0422A] transition-all">
            Filter
        </button>
        @if(request()->hasAny(['search','role','status']))
            <a href="/admin/users" class="px-4 py-3 border border-gray-200 text-gray-400 rounded-xl text-[10px] font-bold hover:bg-gray-50 transition-all">Clear</a>
        @endif
    </form>

    {{-- Table --}}
    <div class="bg-white rounded-3xl border border-gray-100 shadow-sm overflow-hidden">
        <table class="w-full text-left">
            <thead>
                <tr class="bg-gray-50/50 border-b border-gray-100">
                    <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-gray-400">User</th>
                    <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-gray-400 hidden md:table-cell">Role</th>
                    <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-gray-400 hidden lg:table-cell">Joined</th>
                    <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-gray-400">Status</th>
                    <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-gray-400 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($users as $user)
                    @php
                        $roleColors = ['admin' => 'bg-purple-50 text-purple-600', 'seller' => 'bg-blue-50 text-blue-600', 'customer' => 'bg-gray-50 text-gray-600'];
                        $statusColors = ['active' => 'bg-green-50 text-green-600', 'blocked' => 'bg-red-50 text-red-600', 'frozen' => 'bg-amber-50 text-amber-600'];
                    @endphp
                    <tr class="hover:bg-gray-50/50 transition-all">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-xl bg-gray-100 flex items-center justify-center font-black text-sm text-gray-600 shrink-0">
                                    {{ strtoupper(substr($user->name, 0, 1)) }}
                                </div>
                                <div>
                                    <div class="text-sm font-bold text-black">{{ $user->name }}</div>
                                    <div class="text-[10px] text-gray-400">{{ $user->email }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 hidden md:table-cell">
                            <span class="px-3 py-1 rounded-full text-[9px] font-black uppercase tracking-widest {{ $roleColors[$user->role] ?? 'bg-gray-50 text-gray-500' }}">
                                {{ $user->role }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-[11px] text-gray-400 hidden lg:table-cell">
                            {{ $user->createdAt->format('M d, Y') }}
                        </td>
                        <td class="px-6 py-4">
                            <span class="px-3 py-1 rounded-full text-[9px] font-black uppercase tracking-widest {{ $statusColors[$user->status] ?? 'bg-gray-50 text-gray-500' }}">
                                {{ $user->status }}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center justify-end gap-2">
                                @if($user->status === 'active')
                                    <form action="/admin/users/{{ $user->id }}/ban" method="POST" onsubmit="return confirm('Ban this user?')">
                                        @csrf @method('PATCH')
                                        <button type="submit" class="px-4 py-2 bg-red-50 text-red-600 rounded-lg text-[9px] font-black uppercase tracking-widest hover:bg-red-500 hover:text-white transition-all">Ban</button>
                                    </form>
                                @else
                                    <form action="/admin/users/{{ $user->id }}/unban" method="POST">
                                        @csrf @method('PATCH')
                                        <button type="submit" class="px-4 py-2 bg-green-50 text-green-600 rounded-lg text-[9px] font-black uppercase tracking-widest hover:bg-green-500 hover:text-white transition-all">Unban</button>
                                    </form>
                                @endif
                                <form action="/admin/users/{{ $user->id }}" method="POST" onsubmit="return confirm('Delete this user permanently?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="px-4 py-2 bg-gray-50 text-gray-500 rounded-lg text-[9px] font-black uppercase tracking-widest hover:bg-red-500 hover:text-white transition-all">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="py-20 text-center text-sm text-gray-300 italic">No users found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <div class="px-6 py-4 border-t border-gray-50">
            {{ $users->withQueryString()->links() }}
        </div>
    </div>
</div>
@endsection
