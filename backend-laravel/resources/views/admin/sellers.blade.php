@extends('layouts.admin')

@section('content')
<div class="space-y-8">
    <div>
        <div class="text-[10px] font-bold text-[#C0422A] uppercase tracking-[0.2em] mb-1">Artisan Registry</div>
        <h1 class="font-serif text-3xl font-bold text-black">Seller <span class="text-gray-300 font-light italic">Management</span></h1>
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-sm flex items-center gap-4">
            <div class="w-10 h-10 rounded-xl bg-green-50 text-green-600 flex items-center justify-center font-black text-lg">✓</div>
            <div><div class="text-xl font-black text-black">{{ $counts['verified'] }}</div><div class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Verified</div></div>
        </div>
        <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-sm flex items-center gap-4">
            <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center font-black text-lg">⏳</div>
            <div><div class="text-xl font-black text-black">{{ $counts['pending'] }}</div><div class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Pending</div></div>
        </div>
        <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-sm flex items-center gap-4">
            <div class="w-10 h-10 rounded-xl bg-red-50 text-red-600 flex items-center justify-center font-black text-lg">✕</div>
            <div><div class="text-xl font-black text-black">{{ $counts['suspended'] }}</div><div class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Suspended</div></div>
        </div>
    </div>

    {{-- Pending Verification --}}
    @if($pendingSellers->count() > 0)
    <div class="bg-amber-50 border border-amber-100 rounded-3xl p-6 space-y-4">
        <h3 class="text-sm font-black uppercase tracking-widest text-amber-800 flex items-center gap-2">
            <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
            Awaiting Verification ({{ $pendingSellers->count() }})
        </h3>
        <div class="space-y-3">
            @foreach($pendingSellers as $seller)
            <div class="bg-white rounded-2xl p-5 flex items-center justify-between shadow-sm">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-amber-100 text-amber-800 flex items-center justify-center font-black">
                        {{ strtoupper(substr($seller->name, 0, 1)) }}
                    </div>
                    <div>
                        <div class="text-sm font-bold text-black">{{ $seller->name }}</div>
                        <div class="text-[10px] text-gray-400">{{ $seller->email }}</div>
                    </div>
                </div>
                <div class="flex gap-2">
                    <form action="/admin/sellers/{{ $seller->id }}/verify" method="POST">
                        @csrf @method('PATCH')
                        <button type="submit" class="px-5 py-2 bg-green-600 text-white rounded-xl text-[9px] font-black uppercase tracking-widest hover:bg-green-700 transition-all">Verify</button>
                    </form>
                    <form action="/admin/sellers/{{ $seller->id }}/suspend" method="POST">
                        @csrf @method('PATCH')
                        <input type="hidden" name="reason" value="Application rejected by admin.">
                        <button type="submit" class="px-5 py-2 bg-red-50 text-red-600 rounded-xl text-[9px] font-black uppercase tracking-widest hover:bg-red-500 hover:text-white transition-all">Reject</button>
                    </form>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- All Sellers Table --}}
    <div class="bg-white rounded-3xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-50 flex items-center justify-between">
            <h3 class="text-sm font-black uppercase tracking-widest text-black">All Sellers</h3>
        </div>
        <table class="w-full text-left">
            <thead>
                <tr class="bg-gray-50/50 border-b border-gray-100">
                    <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-gray-400">Seller</th>
                    <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-gray-400 hidden md:table-cell">Products</th>
                    <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-gray-400 hidden md:table-cell">Orders</th>
                    <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-gray-400">Status</th>
                    <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-gray-400 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($sellers as $seller)
                <tr class="hover:bg-gray-50/50 transition-all">
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-xl bg-[#C0422A] text-white flex items-center justify-center font-black text-sm">
                                {{ strtoupper(substr($seller->name, 0, 1)) }}
                            </div>
                            <div>
                                <div class="text-sm font-bold text-black flex items-center gap-2">
                                    {{ $seller->name }}
                                    @if($seller->isVerified)
                                        <span class="text-green-500 text-[9px]">✓</span>
                                    @endif
                                </div>
                                <div class="text-[10px] text-gray-400">{{ $seller->email }}</div>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-sm font-bold text-black hidden md:table-cell">{{ $seller->products_count ?? 0 }}</td>
                    <td class="px-6 py-4 text-sm font-bold text-black hidden md:table-cell">{{ $seller->orders_count ?? 0 }}</td>
                    <td class="px-6 py-4">
                        @php $sc = ['active' => 'bg-green-50 text-green-600', 'blocked' => 'bg-red-50 text-red-600', 'frozen' => 'bg-amber-50 text-amber-600']; @endphp
                        <span class="px-3 py-1 rounded-full text-[9px] font-black uppercase tracking-widest {{ $sc[$seller->status] ?? 'bg-gray-50 text-gray-500' }}">
                            {{ $seller->status }}
                        </span>
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex items-center justify-end gap-2">
                            @if($seller->status === 'active')
                                <form action="/admin/sellers/{{ $seller->id }}/suspend" method="POST" onsubmit="return confirm('Suspend this seller?')">
                                    @csrf @method('PATCH')
                                    <input type="hidden" name="reason" value="Suspended by admin.">
                                    <button type="submit" class="px-4 py-2 bg-red-50 text-red-600 rounded-lg text-[9px] font-black uppercase tracking-widest hover:bg-red-500 hover:text-white transition-all">Suspend</button>
                                </form>
                            @else
                                <form action="/admin/sellers/{{ $seller->id }}/verify" method="POST">
                                    @csrf @method('PATCH')
                                    <button type="submit" class="px-4 py-2 bg-green-50 text-green-600 rounded-lg text-[9px] font-black uppercase tracking-widest hover:bg-green-500 hover:text-white transition-all">Restore</button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" class="py-16 text-center text-sm text-gray-300 italic">No sellers yet.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="px-6 py-4 border-t border-gray-50">{{ $sellers->links() }}</div>
    </div>
</div>
@endsection
