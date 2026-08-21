@extends('layouts.superadmin')

@section('content')
<div class="space-y-8" x-data="{
    tab: 'pending',
    zoomImage: '',
    showZoomModal: false,
    rejectModal: false,
    rejectRoute: '',
    rejectSellerName: '',
    openZoom(url) {
        this.zoomImage = url;
        this.showZoomModal = true;
    },
    openReject(routeUrl, name) {
        this.rejectRoute = routeUrl;
        this.rejectSellerName = name;
        this.rejectModal = true;
    }
}">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="text-[10px] font-bold text-[#C0422A] uppercase tracking-[0.2em] mb-1">Artisan Verification &amp; Tiers</div>
            <h1 class="font-serif text-3xl font-bold text-[#3D2B1F]">
                Premium <span class="text-[#C0422A] italic">Subscriptions</span>
            </h1>
            <p class="text-xs text-gray-500 mt-1">Review seller subscription upgrades, approve proof of payment receipts, and manage seller limits.</p>
        </div>
    </div>

    <!-- Tab navigation -->
    <div class="flex gap-2 border-b border-[#E5DDD5] pb-1">
        <button @click="tab = 'pending'"
            :class="tab === 'pending' ? 'border-b-2 border-[#C0422A] text-[#C0422A] font-black' : 'text-gray-500 font-bold hover:text-gray-700'"
            class="px-4 py-2 text-[10px] uppercase tracking-widest transition-all cursor-pointer">
            Pending Approval ({{ $pending->count() }})
        </button>
        <button @click="tab = 'history'"
            :class="tab === 'history' ? 'border-b-2 border-[#C0422A] text-[#C0422A] font-black' : 'text-gray-500 font-bold hover:text-gray-700'"
            class="px-4 py-2 text-[10px] uppercase tracking-widest transition-all cursor-pointer">
            Verification History
        </button>
        <button @click="tab = 'settings'"
            :class="tab === 'settings' ? 'border-b-2 border-[#C0422A] text-[#C0422A] font-black' : 'text-gray-500 font-bold hover:text-gray-700'"
            class="px-4 py-2 text-[10px] uppercase tracking-widest transition-all cursor-pointer">
            Receiving Account Settings
        </button>
    </div>

    <!-- Pending Requests List -->
    <div x-show="tab === 'pending'" class="space-y-4">
        @if($pending->isEmpty())
            <div class="bg-white rounded-3xl border border-[#E5DDD5] p-12 text-center shadow-xs">
                <div class="w-16 h-16 bg-gray-50 rounded-2xl flex items-center justify-center mx-auto mb-4 border border-gray-100">
                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <h3 class="text-sm font-bold text-[#3D2B1F]">No Pending Requests</h3>
                <p class="text-xs text-gray-400 mt-1">All premium subscription requests have been reviewed.</p>
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($pending as $sub)
                    <div class="bg-white rounded-3xl border border-[#E5DDD5] p-6 shadow-xs flex flex-col justify-between space-y-4">
                        <div class="space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="px-2.5 py-1 bg-amber-50 text-amber-700 border border-amber-200 rounded-full text-[10px] font-black uppercase tracking-wider">
                                    {{ $sub->planName ?: 'Premium' }}
                                </span>
                                <span class="text-[10px] text-gray-400">{{ $sub->createdAt ? $sub->createdAt->format('M d, Y') : 'N/A' }}</span>
                            </div>

                            <div>
                                <h3 class="text-sm font-bold text-[#3D2B1F]">{{ $sub->user->name ?? 'Unknown Seller' }}</h3>
                                <p class="text-xs text-gray-500">{{ $sub->user->shopName ?? 'Artisan Shop' }}</p>
                                <p class="text-xs text-gray-400 font-mono">{{ $sub->user->email ?? '' }}</p>
                            </div>

                            <div class="p-3 bg-gray-50 rounded-2xl border border-gray-100 flex items-center justify-between">
                                <span class="text-xs text-gray-500 font-medium">Amount Paid:</span>
                                <span class="text-sm font-extrabold text-[#3D2B1F]">₱{{ number_format((float)($sub->amount ?: 149), 2) }}</span>
                            </div>

                            @if($sub->paymentProof)
                                <div>
                                    <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1.5">Proof of Payment</label>
                                    <div @click="openZoom('{{ str_starts_with($sub->paymentProof, 'http') || str_starts_with($sub->paymentProof, '/') ? $sub->paymentProof : asset('storage/' . $sub->paymentProof) }}')"
                                         class="w-full h-32 rounded-2xl overflow-hidden bg-gray-100 border border-gray-200 cursor-pointer group relative">
                                        <img src="{{ str_starts_with($sub->paymentProof, 'http') || str_starts_with($sub->paymentProof, '/') ? $sub->paymentProof : asset('storage/' . $sub->paymentProof) }}" 
                                             class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                                        <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center text-white text-xs font-bold gap-1">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7"/></svg>
                                            <span>Inspect Receipt</span>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>

                        <div class="pt-3 border-t border-gray-100 flex items-center gap-2">
                            <form action="{{ route('superadmin.subscriptions.approve', $sub->id) }}" method="POST" class="flex-1">
                                @csrf
                                <button type="submit" class="w-full py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl transition-all shadow-xs cursor-pointer">
                                    Approve
                                </button>
                            </form>

                            <button type="button" 
                                    @click="openReject('{{ route('superadmin.subscriptions.reject', $sub->id) }}', '{{ addslashes($sub->user->name ?? 'Seller') }}')"
                                    class="flex-1 py-2.5 bg-red-50 hover:bg-red-100 text-red-600 border border-red-200 text-xs font-bold rounded-xl transition-all cursor-pointer">
                                Reject
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <!-- Verification History Tab -->
    <div x-show="tab === 'history'" class="space-y-4" style="display: none;">
        @if($history->isEmpty())
            <div class="bg-white rounded-3xl border border-[#E5DDD5] p-12 text-center shadow-xs">
                <div class="w-16 h-16 bg-gray-50 rounded-2xl flex items-center justify-center mx-auto mb-4 border border-gray-100">
                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <h3 class="text-sm font-bold text-[#3D2B1F]">No History Found</h3>
                <p class="text-xs text-gray-400 mt-1">Processed subscription records will appear here.</p>
            </div>
        @else
            <div class="bg-white rounded-3xl border border-[#E5DDD5] shadow-xs overflow-hidden">
                <div class="overflow-x-auto no-scrollbar">
                    <table class="w-full text-left border-collapse min-w-175">
                        <thead>
                            <tr class="bg-gray-50/70 border-b border-[#E5DDD5]">
                                <th class="px-6 py-4 text-[10px] font-black text-gray-600 uppercase tracking-widest">Seller</th>
                                <th class="px-6 py-4 text-[10px] font-black text-gray-600 uppercase tracking-widest">Plan &amp; Amount</th>
                                <th class="px-6 py-4 text-[10px] font-black text-gray-600 uppercase tracking-widest">Status</th>
                                <th class="px-6 py-4 text-[10px] font-black text-gray-600 uppercase tracking-widest">Active Period</th>
                                <th class="px-6 py-4 text-[10px] font-black text-gray-600 uppercase tracking-widest">Date Processed</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($history as $record)
                                <tr class="hover:bg-amber-50/20 transition-colors">
                                    <td class="px-6 py-4">
                                        <div class="text-xs font-bold text-gray-900">{{ $record->user->name ?? 'Unknown Seller' }}</div>
                                        <div class="text-[10px] text-gray-400">{{ $record->user->shopName ?? 'Artisan Shop' }}</div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-xs font-bold text-gray-900">{{ $record->planName ?: 'Premium' }}</div>
                                        <div class="text-[10px] text-gray-500 font-mono">₱{{ number_format((float)($record->amount ?: 149), 2) }}</div>
                                    </td>
                                    <td class="px-6 py-4">
                                        @if($record->status === 'active')
                                            <span class="px-2.5 py-1 bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-full text-[10px] font-black uppercase tracking-wider">Active</span>
                                        @elseif($record->status === 'rejected')
                                            <span class="px-2.5 py-1 bg-red-50 text-red-700 border border-red-200 rounded-full text-[10px] font-black uppercase tracking-wider">Rejected</span>
                                        @else
                                            <span class="px-2.5 py-1 bg-gray-100 text-gray-700 rounded-full text-[10px] font-black uppercase tracking-wider">{{ $record->status }}</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-xs text-gray-600">
                                        @if($record->startsAt && $record->endsAt)
                                            <div>{{ $record->startsAt->format('M d, Y') }} — {{ $record->endsAt->format('M d, Y') }}</div>
                                        @else
                                            <span class="text-gray-400">N/A</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-xs text-gray-500">
                                        {{ $record->updatedAt ? $record->updatedAt->format('M d, Y h:i A') : 'N/A' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if($history->hasPages())
                    <div class="p-6 bg-gray-50/50 border-t border-[#E5DDD5]">
                        {{ $history->links() }}
                    </div>
                @endif
            </div>
        @endif
    </div>

    <!-- Receiving Settings Tab -->
    <div x-show="tab === 'settings'" class="space-y-4" style="display: none;">
        <div class="bg-white rounded-3xl border border-[#E5DDD5] p-6 sm:p-8 max-w-xl shadow-xs">
            <h3 class="font-serif text-lg font-bold text-[#3D2B1F] mb-1">Subscription Receiving Accounts</h3>
            <p class="text-xs text-gray-500 mb-6">These payment account details are shown to artisan sellers when submitting subscription upgrades.</p>

            <form action="{{ route('superadmin.subscriptions.settings') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-[10px] font-black uppercase tracking-widest text-gray-500 mb-1.5">GCash Account Name</label>
                    <input type="text" name="gcashName" value="{{ old('gcashName', $admin->gcashName ?? '') }}" placeholder="e.g. LumBarong Admin"
                           class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-xs focus:bg-white focus:outline-none focus:border-black">
                </div>

                <div>
                    <label class="block text-[10px] font-black uppercase tracking-widest text-gray-500 mb-1.5">GCash Account Number</label>
                    <input type="text" name="gcashNumber" value="{{ old('gcashNumber', $admin->gcashNumber ?? '') }}" placeholder="e.g. 0917XXXXXXX"
                           class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-xs focus:bg-white focus:outline-none focus:border-black">
                </div>

                <div>
                    <label class="block text-[10px] font-black uppercase tracking-widest text-gray-500 mb-1.5">GCash QR Code Image</label>
                    @if(!empty($admin->gcashQr))
                        <div class="w-32 h-32 rounded-2xl overflow-hidden bg-gray-100 border border-gray-200 mb-3">
                            <img src="{{ str_starts_with($admin->gcashQr, 'http') || str_starts_with($admin->gcashQr, '/') ? $admin->gcashQr : asset('storage/' . $admin->gcashQr) }}" class="w-full h-full object-cover">
                        </div>
                    @endif
                    <input type="file" name="gcashQr" accept="image/*" class="text-xs text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-[#3D2B1F] file:text-white hover:file:bg-[#C0422A] file:cursor-pointer">
                </div>

                <div class="pt-4 border-t border-gray-100 flex justify-end">
                    <button type="submit" class="px-6 py-2.5 bg-[#3D2B1F] hover:bg-[#C0422A] text-white text-xs font-bold uppercase tracking-widest rounded-xl transition-all shadow-xs cursor-pointer">
                        Save Receiving Settings
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Zoom Receipt Modal --}}
    <div x-show="showZoomModal" 
         x-cloak 
         style="display: none;"
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm"
         @click="showZoomModal = false">
        <div class="relative max-w-2xl max-h-[90vh] p-2 bg-white rounded-3xl shadow-2xl overflow-hidden" @click.stop>
            <button @click="showZoomModal = false" class="absolute top-4 right-4 z-10 w-8 h-8 rounded-full bg-black/60 text-white flex items-center justify-center hover:bg-black transition-colors cursor-pointer">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
            <img :src="zoomImage" class="max-h-[85vh] max-w-full rounded-2xl object-contain">
        </div>
    </div>

    {{-- Reject Modal --}}
    <div x-show="rejectModal" 
         x-cloak 
         style="display: none;"
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
        <div class="bg-white rounded-3xl p-6 sm:p-8 max-w-md w-full shadow-2xl border border-gray-100" @click.away="rejectModal = false">
            <div class="w-14 h-14 rounded-2xl bg-red-50 text-red-600 flex items-center justify-center mx-auto mb-4 border border-red-100">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </div>
            <h3 class="font-serif text-xl font-bold text-center text-[#3D2B1F] mb-1">Reject Subscription Request</h3>
            <p class="text-xs text-gray-500 text-center mb-4">Provide a reason for rejecting <span class="font-bold text-gray-900" x-text="rejectSellerName"></span>'s subscription.</p>

            <form :action="rejectRoute" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-1.5">Rejection Reason <span class="text-red-500">*</span></label>
                    <textarea name="rejectionReason" rows="3" required placeholder="e.g. Unreadable receipt screenshot, incorrect payment reference..."
                              class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-xs focus:bg-white focus:outline-none focus:border-black"></textarea>
                </div>
                <div class="flex items-center gap-3 pt-2">
                    <button type="button" @click="rejectModal = false" class="flex-1 py-3 bg-gray-100 text-gray-700 text-xs font-bold rounded-xl hover:bg-gray-200 transition-all cursor-pointer">Cancel</button>
                    <button type="submit" class="flex-1 py-3 bg-red-600 text-white text-xs font-bold rounded-xl hover:bg-red-700 transition-all cursor-pointer shadow-sm">Reject Request</button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
