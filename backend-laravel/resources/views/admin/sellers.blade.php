@extends('layouts.admin')

@section('content')
<div class="space-y-8" x-data="{
    suspendModal: false,
    suspendSellerId: null,
    suspendSellerName: '',
    suspendReason: '',
    openSuspend(seller) {
        this.suspendSellerId = seller.id;
        this.suspendSellerName = seller.name;
        this.suspendReason = '';
        this.suspendModal = true;
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
            <table class="w-full text-left min-w-137.5">
            <thead>
                <tr class="bg-gray-50/50 border-b border-gray-100">
                    <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-gray-700">Seller</th>
                    <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-gray-700 hidden md:table-cell">Products</th>
                    <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-gray-700 hidden md:table-cell">Orders</th>
                    <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-gray-700">Status</th>
                    <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-gray-700 text-right">Actions</th>
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
                                <div class="text-[10px] text-gray-500 font-medium">{{ $seller->email }}</div>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-sm font-bold text-black hidden md:table-cell">{{ $seller->products_count ?? 0 }}</td>
                    <td class="px-6 py-4 text-sm font-bold text-black hidden md:table-cell">{{ $seller->orders_count ?? 0 }}</td>
                    <td class="px-6 py-4">
                        @php $sc = ['active' => 'bg-green-50 text-green-700 border border-green-200', 'blocked' => 'bg-red-50 text-red-700 border border-red-200', 'frozen' => 'bg-amber-50 text-amber-700 border border-amber-200']; @endphp
                        <span class="px-3 py-1 rounded-full text-[9px] font-black uppercase tracking-widest {{ $sc[$seller->status] ?? 'bg-gray-50 text-gray-600 border border-gray-200' }}">
                            {{ $seller->status }}
                        </span>
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex items-center justify-end gap-2">
                            <form action="/admin/sellers/{{ $seller->id }}/{{ $seller->isVerified ? 'unverify' : 'verify' }}" method="POST">
                                @csrf @method('PATCH')
                                <button type="submit" class="px-4 py-2 {{ $seller->isVerified ? 'bg-gray-50 text-gray-600 hover:bg-gray-200' : 'bg-green-50 text-green-700 hover:bg-green-500 hover:text-white' }} rounded-lg text-[9px] font-black uppercase tracking-widest transition-all cursor-pointer">
                                    {{ $seller->isVerified ? 'Revoke Verified' : 'Verify' }}
                                </button>
                            </form>
                            @if($seller->status === 'active')
                                <button type="button" @click="openSuspend({{ json_encode($seller) }})" class="px-4 py-2 bg-red-50 text-red-700 rounded-lg text-[9px] font-black uppercase tracking-widest hover:bg-red-500 hover:text-white transition-all cursor-pointer">
                                    Suspend
                                </button>
                            @else
                                <form action="/admin/sellers/{{ $seller->id }}/unsuspend" method="POST">
                                    @csrf @method('PATCH')
                                    <button type="submit" class="px-4 py-2 bg-green-50 text-green-700 rounded-lg text-[9px] font-black uppercase tracking-widest hover:bg-green-500 hover:text-white transition-all cursor-pointer">
                                        Restore
                                    </button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="py-16 text-center text-sm text-gray-500 italic">No seller accounts found.</td>
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
</div>
@endsection
