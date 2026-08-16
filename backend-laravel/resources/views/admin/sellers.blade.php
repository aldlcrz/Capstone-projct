@extends('layouts.admin')

@section('content')
<div class="space-y-8" x-data="{
    suspendModal: false,
    suspendSellerId: null,
    suspendSellerName: '',
    suspendReason: '',
    deleteModal: false,
    deleteSellerId: null,
    deleteSellerName: '',
    openSuspend(seller) {
        this.suspendSellerId = seller.id;
        this.suspendSellerName = seller.name;
        this.suspendReason = '';
        this.suspendModal = true;
    },
    openDelete(seller) {
        this.deleteSellerId = seller.id;
        this.deleteSellerName = seller.name;
        this.deleteModal = true;
    }
}">
    <div>
        <div class="text-[10px] font-bold text-[#C0422A] uppercase tracking-[0.2em] mb-1">Artisan Registry</div>
        <h1 class="font-serif text-3xl font-bold text-black">Seller <span class="text-[#C0420A] font-light italic">Management</span></h1>
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-sm flex items-center gap-4">
            <div class="w-10 h-10 rounded-xl bg-green-50 text-green-600 flex items-center justify-center font-black text-lg">✓</div>
            <div><div class="text-xl font-black text-black">{{ $counts['verified'] }}</div><div class="text-[10px] font-bold text-gray-500 uppercase tracking-widest">Verified</div></div>
        </div>
        <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-sm flex items-center gap-4">
            <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center font-black text-lg">⏳</div>
            <div><div class="text-xl font-black text-black">{{ $counts['pending'] }}</div><div class="text-[10px] font-bold text-gray-500 uppercase tracking-widest">Pending</div></div>
        </div>
        <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-sm flex items-center gap-4">
            <div class="w-10 h-10 rounded-xl bg-red-50 text-red-600 flex items-center justify-center font-black text-lg">✕</div>
            <div><div class="text-xl font-black text-black">{{ $counts['suspended'] }}</div><div class="text-[10px] font-bold text-gray-500 uppercase tracking-widest">Suspended</div></div>
        </div>
    </div>

    {{-- Pending Verification --}}
    @if($pendingSellers->count() > 0)
    <div class="bg-amber-50 border border-amber-100 rounded-3xl p-4 sm:p-6 space-y-4">
        <h3 class="text-xs sm:text-sm font-black uppercase tracking-widest text-amber-800 flex items-center gap-2">
            <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
            Awaiting Verification ({{ $pendingSellers->count() }})
        </h3>
        <div class="space-y-3">
            @foreach($pendingSellers as $seller)
            <div class="bg-white rounded-2xl p-4 sm:p-5 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 shadow-sm">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-amber-100 text-amber-800 flex items-center justify-center font-black shrink-0">
                        {{ strtoupper(substr($seller->name, 0, 1)) }}
                    </div>
                    <div class="min-w-0">
                        <div class="text-sm font-bold text-black truncate">{{ $seller->name }}</div>
                        <div class="text-[10px] text-gray-500 font-medium truncate">{{ $seller->email }}</div>
                    </div>
                </div>
                <div class="flex gap-2 w-full sm:w-auto">
                    <form action="/admin/sellers/{{ $seller->id }}/verify" method="POST" class="flex-1 sm:flex-initial">
                        @csrf @method('PATCH')
                        <button type="submit" class="w-full sm:w-auto px-5 py-2 bg-green-600 text-white rounded-xl text-[9px] font-black uppercase tracking-widest hover:bg-green-700 transition-all cursor-pointer">Verify</button>
                    </form>
                    <button type="button" @click="openSuspend({{ json_encode($seller) }})" class="flex-1 sm:flex-initial px-5 py-2 bg-red-50 text-red-600 rounded-xl text-[9px] font-black uppercase tracking-widest hover:bg-red-500 hover:text-white transition-all cursor-pointer">Reject / Suspend</button>
                    <button type="button" @click="openDelete({{ json_encode($seller) }})" class="flex-1 sm:flex-initial px-5 py-2 bg-gray-100 text-gray-600 rounded-xl text-[9px] font-black uppercase tracking-widest hover:bg-gray-800 hover:text-white transition-all cursor-pointer">Delete</button>
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
        <div class="overflow-x-auto no-scrollbar">
            <table class="w-full text-left min-w-160">
            <thead>
                <tr class="bg-gray-50/50 border-b border-gray-100">
                    <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-gray-700">Artisan / Seller</th>
                    <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-gray-700 hidden lg:table-cell">Shop Name</th>
                    <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-gray-700 text-center hidden md:table-cell">Products</th>
                    <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-gray-700 text-center hidden md:table-cell">Orders</th>
                    <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-gray-700 hidden sm:table-cell">Joined</th>
                    <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-gray-700 text-center">Status</th>
                    <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-gray-700 text-center">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($sellers as $seller)
                <tr class="hover:bg-gray-50/50 transition-all">
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-xl bg-[#C0422A] text-white flex items-center justify-center font-black text-sm shrink-0">
                                {{ strtoupper(substr($seller->name, 0, 1)) }}
                            </div>
                            <div class="min-w-0">
                                <div class="text-sm font-bold text-black flex items-center gap-2 truncate">
                                    {{ $seller->name }}
                                    @if($seller->isVerified)
                                        <span class="text-green-500 text-[10px] font-black" title="Verified">✓</span>
                                    @endif
                                </div>
                                <div class="text-[10px] text-gray-500 font-medium truncate">{{ $seller->email }}</div>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-xs font-semibold text-gray-700 hidden lg:table-cell">
                        {{ $seller->shopName ?? '—' }}
                    </td>
                    <td class="px-6 py-4 text-sm font-bold text-black text-center hidden md:table-cell">{{ $seller->products_count ?? 0 }}</td>
                    <td class="px-6 py-4 text-sm font-bold text-black text-center hidden md:table-cell">{{ $seller->orders_count ?? 0 }}</td>
                    <td class="px-6 py-4 text-[11px] text-gray-500 font-medium hidden sm:table-cell">
                        {{ $seller->createdAt ? $seller->createdAt->format('M d, Y') : '—' }}
                    </td>
                    <td class="px-6 py-4 text-center">
                        @php $sc = ['active' => 'bg-green-50 text-green-700 border border-green-200', 'blocked' => 'bg-red-50 text-red-700 border border-red-200', 'frozen' => 'bg-amber-50 text-amber-700 border border-amber-200', 'pending_approval' => 'bg-blue-50 text-blue-700 border border-blue-200']; @endphp
                        <span class="px-3 py-1 rounded-full text-[9px] font-black uppercase tracking-widest inline-block {{ $sc[$seller->status] ?? 'bg-gray-50 text-gray-600 border border-gray-200' }}">
                            {{ $seller->status === 'pending_approval' ? 'Pending' : $seller->status }}
                        </span>
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex items-center justify-center gap-2">
                            @if(!$seller->isVerified)
                                <form action="/admin/sellers/{{ $seller->id }}/verify" method="POST">
                                    @csrf @method('PATCH')
                                    <button type="submit" class="px-4 py-2 bg-green-50 text-green-700 hover:bg-green-500 hover:text-white rounded-lg text-[9px] font-black uppercase tracking-widest transition-all cursor-pointer">
                                        Verify
                                    </button>
                                </form>
                            @endif
                            @if($seller->status === 'active' || $seller->status === 'pending_approval')
                                <button type="button" @click="openSuspend({{ json_encode($seller) }})" class="px-4 py-2 bg-red-50 text-red-700 rounded-lg text-[9px] font-black uppercase tracking-widest hover:bg-red-500 hover:text-white transition-all cursor-pointer">
                                    Suspend
                                </button>
                            @else
                                <form action="{{ route('admin.sellers.unsuspend', $seller->id) }}" method="POST">
                                    @csrf @method('PATCH')
                                    <button type="submit" class="px-4 py-2 bg-green-50 text-green-700 rounded-lg text-[9px] font-black uppercase tracking-widest hover:bg-green-500 hover:text-white transition-all cursor-pointer">
                                        Restore
                                    </button>
                                </form>
                            @endif
                            <button type="button" @click="openDelete({{ json_encode($seller) }})" class="px-4 py-2 bg-gray-100 text-gray-600 rounded-lg text-[9px] font-black uppercase tracking-widest hover:bg-gray-800 hover:text-white transition-all cursor-pointer">
                                Delete
                            </button>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="py-16 text-center text-sm text-gray-500 italic">No seller accounts found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
        </div>
        <div class="px-6 py-4 border-t border-gray-50">
            {{ $sellers->links() }}
        </div>
    </div>

    {{-- Suspend Confirmation Modal --}}
    <div x-show="suspendModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" x-cloak>
        <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="suspendModal = false"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md p-6 space-y-4">
            <h3 class="text-lg font-bold text-gray-900">Suspend Seller Account</h3>
            <p class="text-xs text-gray-500 leading-relaxed">
                Are you sure you want to suspend seller <strong x-text="suspendSellerName" class="text-black"></strong>? Please enter an explanation or reason for suspending this seller.
            </p>
            <form :action="'/admin/sellers/' + suspendSellerId + '/suspend'" method="POST" class="space-y-4">
                @csrf @method('PATCH')
                <div>
                    <label class="text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-1 block">Explanation / Reason *</label>
                    <textarea name="reason" x-model="suspendReason" required rows="3" placeholder="Provide the reason for account suspension..." class="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl text-xs outline-none focus:border-red-500"></textarea>
                </div>
                <div class="flex gap-3 pt-2">
                    <button type="button" @click="suspendModal = false" class="flex-1 py-2.5 border border-gray-200 text-xs font-semibold text-gray-500 rounded-xl hover:bg-gray-50">Cancel</button>
                    <button type="submit" class="flex-1 py-2.5 bg-red-600 text-white text-xs font-bold uppercase tracking-wider rounded-xl hover:bg-red-700">Confirm Suspension</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Delete Confirmation Modal --}}
    <div x-show="deleteModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" x-cloak>
        <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="deleteModal = false"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md p-6 space-y-4">
            <div class="w-12 h-12 rounded-2xl bg-red-100 flex items-center justify-center mx-auto">
                <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
            </div>
            <div class="text-center">
                <h3 class="text-lg font-bold text-gray-900">Delete Seller Account</h3>
                <p class="text-xs text-gray-500 leading-relaxed mt-2">
                    You are about to permanently delete <strong x-text="deleteSellerName" class="text-red-600"></strong>'s account. This action <span class="font-bold text-red-600">cannot be undone</span>.
                </p>
            </div>
            <form :action="'/admin/sellers/' + deleteSellerId" method="POST" class="space-y-4">
                @csrf @method('DELETE')
                <div class="flex gap-3 pt-2">
                    <button type="button" @click="deleteModal = false" class="flex-1 py-2.5 border border-gray-200 text-xs font-semibold text-gray-500 rounded-xl hover:bg-gray-50">Cancel</button>
                    <button type="submit" class="flex-1 py-2.5 bg-red-600 text-white text-xs font-bold uppercase tracking-wider rounded-xl hover:bg-red-700">Delete Permanently</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
