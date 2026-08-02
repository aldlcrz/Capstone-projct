@extends('layouts.admin')

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
            <div class="text-[10px] font-bold text-[#C0422A] uppercase tracking-[0.2em] mb-1">Seller Verifications</div>
            <h1 class="font-serif text-3xl font-bold text-black uppercase">
                Premium <span class="text-[#C0422A] italic lowercase">subscriptions</span>
            </h1>
        </div>
    </div>

    <!-- Tab navigation -->
    <div class="flex gap-2 border-b border-gray-100 pb-1">
        <button @click="tab = 'pending'"
            :class="tab === 'pending' ? 'border-b-2 border-black text-black font-black' : 'text-gray-400 font-bold hover:text-gray-600'"
            class="px-4 py-2 text-[10px] uppercase tracking-widest transition-all">
            Pending Approval ({{ $pending->count() }})
        </button>
        <button @click="tab = 'history'"
            :class="tab === 'history' ? 'border-b-2 border-black text-black font-black' : 'text-gray-400 font-bold hover:text-gray-600'"
            class="px-4 py-2 text-[10px] uppercase tracking-widest transition-all">
            Verification History
        </button>
        <button @click="tab = 'settings'"
            :class="tab === 'settings' ? 'border-b-2 border-black text-black font-black' : 'text-gray-400 font-bold hover:text-gray-600'"
            class="px-4 py-2 text-[10px] uppercase tracking-widest transition-all">
            Payment Account Settings
        </button>
    </div>

    <!-- Pending Requests List -->
    <div x-show="tab === 'pending'" class="space-y-4">
        @if($pending->isEmpty())
            <div class="bg-white rounded-3xl border border-gray-100 p-12 text-center shadow-sm">
                <svg class="w-12 h-12 text-gray-200 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"></path></svg>
                <p class="text-sm font-bold text-gray-300 uppercase tracking-widest">No pending subscription payments</p>
            </div>
        @else
            <div class="bg-white rounded-3xl border border-gray-100 shadow-sm overflow-hidden divide-y divide-gray-50">
                @foreach($pending as $sub)
                    <div class="p-6 hover:bg-gray-50/50 transition-all flex flex-col md:flex-row md:items-center justify-between gap-6">
                        <!-- Left details -->
                        <div class="flex items-start gap-4 flex-1">
                            <div class="w-10 h-10 rounded-xl bg-yellow-500/10 text-yellow-700 flex items-center justify-center font-black text-sm shrink-0 border border-yellow-500/20">
                                {{ strtoupper(substr($sub->user->name ?? 'A', 0, 1)) }}
                            </div>
                            <div class="space-y-1">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="text-xs font-black text-black">{{ $sub->user->shopName ?: $sub->user->name }}</span>
                                    <span class="px-2 py-0.5 rounded-full border border-blue-200 bg-blue-50 text-blue-700 text-[8px] font-black uppercase tracking-wider">{{ $sub->paymentMethod }}</span>
                                </div>
                                <p class="text-[10px] text-gray-400">Reference: <span class="font-mono text-gray-600 font-bold select-all">{{ $sub->paymentReference }}</span></p>
                                <p class="text-[10px] text-gray-300">{{ $sub->createdAt ? $sub->createdAt->format('M d, Y · h:i A') : '' }}</p>
                            </div>
                        </div>

                        <!-- Center: Amount & Receipt Link -->
                        <div class="flex items-center gap-6 min-w-[150px]">
                            <div class="text-right">
                                <div class="text-sm font-black text-black">₱{{ number_format($sub->amount, 2) }}</div>
                                @if($sub->paymentProof)
                                    <button type="button" @click="openZoom('{{ asset('storage/' . $sub->paymentProof) }}')" class="text-[9px] font-black uppercase tracking-widest text-[#C0422A] hover:underline cursor-pointer">
                                        View Receipt
                                    </button>
                                @endif
                            </div>
                        </div>

                        <!-- Right: Actions -->
                        <div class="flex items-center gap-2 shrink-0">
                            <!-- Approve button with POST/PATCH form -->
                            <form action="{{ route('admin.subscriptions.approve', $sub->id) }}" method="POST">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="px-5 py-2.5 bg-black text-white hover:bg-green-600 rounded-xl text-[9px] font-black uppercase tracking-widest transition-all">
                                    Approve
                                </button>
                            </form>
                            
                            <button type="button" @click="openReject('{{ route('admin.subscriptions.reject', $sub->id) }}', '{{ $sub->user->name }}')" class="px-4 py-2.5 bg-white border border-gray-200 text-red-600 hover:bg-red-50 hover:border-red-200 rounded-xl text-[9px] font-black uppercase tracking-widest transition-all">
                                Reject
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <!-- Verification History List -->
    <div x-show="tab === 'history'" class="space-y-4" style="display: none;">
        @if($history->isEmpty())
            <div class="bg-white rounded-3xl border border-gray-100 p-12 text-center shadow-sm">
                <p class="text-xs text-gray-400 italic">No processed subscriptions in history yet.</p>
            </div>
        @else
            <div class="bg-white rounded-3xl border border-gray-100 shadow-sm overflow-hidden divide-y divide-gray-50">
                @foreach($history as $sub)
                    <div class="p-6 flex flex-col md:flex-row md:items-center justify-between gap-6">
                        <div class="flex items-start gap-4">
                            <div class="w-10 h-10 rounded-xl bg-gray-50 text-gray-500 flex items-center justify-center font-bold text-sm shrink-0 border border-gray-100">
                                {{ strtoupper(substr($sub->user->name ?? 'A', 0, 1)) }}
                            </div>
                            <div class="space-y-1">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="text-xs font-bold text-black">{{ $sub->user->shopName ?: $sub->user->name }}</span>
                                    @if($sub->status === 'active')
                                        <span class="px-2 py-0.5 rounded-full border border-green-200 bg-green-50 text-green-700 text-[8px] font-black uppercase tracking-wider">Active</span>
                                    @elseif($sub->status === 'expired')
                                        <span class="px-2 py-0.5 rounded-full border border-gray-200 bg-gray-50 text-gray-500 text-[8px] font-black uppercase tracking-wider">Expired</span>
                                    @else
                                        <span class="px-2 py-0.5 rounded-full border border-red-200 bg-red-50 text-red-700 text-[8px] font-black uppercase tracking-wider">Rejected</span>
                                    @endif
                                </div>
                                <p class="text-[10px] text-gray-400">Reference: <span class="font-mono">{{ $sub->paymentReference }}</span> · Paid: {{ $sub->paymentMethod }}</p>
                                @if($sub->status === 'rejected' && $sub->rejectionReason)
                                    <p class="text-[10px] text-red-500 font-bold">Reason: {{ $sub->rejectionReason }}</p>
                                @endif
                                @if($sub->startsAt && $sub->endsAt)
                                    <p class="text-[9px] text-gray-400 uppercase tracking-wider font-bold">Duration: {{ $sub->startsAt->format('M d, Y') }} - {{ $sub->endsAt->format('M d, Y') }}</p>
                                @endif
                            </div>
                        </div>

                        <div class="text-right">
                            <div class="text-sm font-black text-black">₱{{ number_format($sub->amount, 2) }}</div>
                            @if($sub->paymentProof)
                                <button type="button" @click="openZoom('{{ asset('storage/' . $sub->paymentProof) }}')" class="text-[9px] font-black uppercase tracking-widest text-[#C0422A] hover:underline cursor-pointer">
                                    View Receipt
                                </button>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
            
            <div class="pt-4">
                {{ $history->links() }}
            </div>
        @endif
    </div>

    <!-- Payment Account Settings -->
    <div x-show="tab === 'settings'" class="space-y-6" style="display: none;" x-cloak>
        <div class="bg-white rounded-3xl border border-gray-100 p-8 shadow-sm max-w-2xl">
            <h2 class="font-serif text-2xl font-bold tracking-tight text-black mb-1">Configure Payment Accounts</h2>
            <p class="text-xs text-gray-500 leading-relaxed mb-6">
                Update the GCash and Maya accounts that sellers will pay to when upgrading to Premium. The numbers and QR codes configured here will be displayed to sellers on their checkout page.
            </p>

            <form action="{{ route('admin.subscriptions.settings.update') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
                @csrf

                <!-- GCash Settings Section -->
                <div class="p-6 bg-blue-50/30 border border-blue-100 rounded-2xl space-y-4">
                    <div class="flex items-center justify-between">
                        <h3 class="text-xs font-black uppercase text-blue-600 tracking-wider">GCash Account Settings</h3>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="isGcashAvailable" value="1" class="sr-only peer" {{ $admin && $admin->isGcashAvailable ? 'checked' : '' }}>
                            <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-blue-600"></div>
                            <span class="ml-2 text-[10px] font-bold text-gray-500 uppercase tracking-wider">Available</span>
                        </label>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="gcashNumber" class="text-[9px] font-bold text-gray-400 uppercase tracking-widest block mb-1.5">GCash Phone Number</label>
                            <input type="text" name="gcashNumber" id="gcashNumber" value="{{ $admin->gcashNumber ?? '' }}" placeholder="e.g. 09171234567"
                                class="w-full px-4 py-3 border border-gray-100 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-blue-500/10 bg-white">
                        </div>

                        <div>
                            <label for="gcashQrCode" class="text-[9px] font-bold text-gray-400 uppercase tracking-widest block mb-1.5">Upload GCash QR Code</label>
                            <input type="file" name="gcashQrCode" id="gcashQrCode" accept="image/*"
                                class="w-full text-xs text-gray-500 file:mr-4 file:py-2 file:px-3 file:rounded-xl file:border-0 file:text-[9px] file:font-black file:uppercase file:tracking-widest file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                        </div>
                    </div>

                    @if($admin && $admin->gcashQrCode)
                        <div class="flex items-center gap-3 pt-2">
                            <div class="w-12 h-12 bg-white border border-gray-200 rounded-xl overflow-hidden shadow-sm flex items-center justify-center">
                                <img src="{{ asset('storage/' . $admin->gcashQrCode) }}" class="w-full h-full object-contain">
                            </div>
                            <div>
                                <div class="text-[9px] font-bold text-gray-400 uppercase tracking-widest">Current QR Code</div>
                                <button type="button" @click="openZoom('{{ asset('storage/' . $admin->gcashQrCode) }}')" class="text-[9px] font-black uppercase tracking-widest text-[#C0422A] hover:underline">
                                    Preview / Zoom
                                </button>
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Maya Settings Section -->
                <div class="p-6 bg-teal-50/30 border border-teal-100 rounded-2xl space-y-4">
                    <div class="flex items-center justify-between">
                        <h3 class="text-xs font-black uppercase text-teal-600 tracking-wider">Maya Account Settings</h3>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="isMayaAvailable" value="1" class="sr-only peer" {{ $admin && $admin->isMayaAvailable ? 'checked' : '' }}>
                            <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-teal-600"></div>
                            <span class="ml-2 text-[10px] font-bold text-gray-500 uppercase tracking-wider">Available</span>
                        </label>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="mayaNumber" class="text-[9px] font-bold text-gray-400 uppercase tracking-widest block mb-1.5">Maya Phone Number</label>
                            <input type="text" name="mayaNumber" id="mayaNumber" value="{{ $admin->mayaNumber ?? '' }}" placeholder="e.g. 09171234567"
                                class="w-full px-4 py-3 border border-gray-100 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-teal-500/10 bg-white">
                        </div>

                        <div>
                            <label for="mayaQrCode" class="text-[9px] font-bold text-gray-400 uppercase tracking-widest block mb-1.5">Upload Maya QR Code</label>
                            <input type="file" name="mayaQrCode" id="mayaQrCode" accept="image/*"
                                class="w-full text-xs text-gray-500 file:mr-4 file:py-2 file:px-3 file:rounded-xl file:border-0 file:text-[9px] file:font-black file:uppercase file:tracking-widest file:bg-teal-50 file:text-teal-700 hover:file:bg-teal-100">
                        </div>
                    </div>

                    @if($admin && $admin->mayaQrCode)
                        <div class="flex items-center gap-3 pt-2">
                            <div class="w-12 h-12 bg-white border border-gray-200 rounded-xl overflow-hidden shadow-sm flex items-center justify-center">
                                <img src="{{ asset('storage/' . $admin->mayaQrCode) }}" class="w-full h-full object-contain">
                            </div>
                            <div>
                                <div class="text-[9px] font-bold text-gray-400 uppercase tracking-widest">Current QR Code</div>
                                <button type="button" @click="openZoom('{{ asset('storage/' . $admin->mayaQrCode) }}')" class="text-[9px] font-black uppercase tracking-widest text-[#C0422A] hover:underline">
                                    Preview / Zoom
                                </button>
                            </div>
                        </div>
                    @endif
                </div>

                <div class="pt-4 flex justify-end">
                    <button type="submit" class="px-6 py-3 bg-black hover:bg-[#C0422A] text-white rounded-xl text-[10px] font-black uppercase tracking-widest transition-all">
                        Save Payment Settings
                    </button>
                </div>
            </form>
        </div>
    </div>

<!-- Receipt Zoom Modal -->
<div x-show="showZoomModal" class="fixed inset-0 z-9999 flex items-center justify-center p-4 bg-black/60 backdrop-blur-md" x-cloak>
    <div @click.away="showZoomModal = false" class="relative max-w-lg w-full bg-white rounded-3xl overflow-hidden shadow-2xl p-6 flex flex-col items-center">
        <div class="w-full flex items-center justify-between pb-4 border-b border-gray-100 mb-4">
            <h3 class="font-serif text-lg font-bold text-black">Proof of Payment</h3>
            <button type="button" @click="showZoomModal = false"
                class="w-8 h-8 rounded-full border border-gray-200 flex items-center justify-center text-gray-400 hover:text-black hover:border-gray-400 transition-all shrink-0">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        
        <div class="w-full bg-gray-50 rounded-2xl overflow-hidden flex items-center justify-center border border-gray-100 max-h-[70vh]">
            <img :src="zoomImage" class="max-w-full max-h-[60vh] object-contain" alt="Payment Proof">
        </div>
        
        <div class="w-full mt-4 flex gap-3">
            <a :href="zoomImage" download="receipt" class="flex-1 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 text-center rounded-xl text-[10px] font-bold uppercase tracking-widest transition-all">
                Download Image
            </a>
            <button type="button" @click="showZoomModal = false" class="flex-1 py-3 bg-black hover:bg-[#C0422A] text-white rounded-xl text-[10px] font-bold uppercase tracking-widest transition-all">
                Close
            </button>
        </div>
    </div>
</div>

<!-- Rejection Modal -->
<div x-show="rejectModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm" x-cloak>
    <div @click.away="rejectModal = false" class="bg-white rounded-3xl shadow-2xl w-full max-w-md p-8 space-y-6">
        <div>
            <h3 class="font-serif text-xl font-bold text-black mb-1">Reject Subscription Upgrade</h3>
            <p class="text-[10px] text-gray-400 uppercase tracking-widest font-bold">Seller: <span x-text="rejectSellerName" class="text-black"></span></p>
        </div>

        <form :action="rejectRoute" method="POST" class="space-y-4">
            @csrf
            @method('PATCH')
            
            <div>
                <label for="rejectionReason" class="text-[9px] font-bold text-gray-400 uppercase tracking-widest block mb-1.5">Reason for Rejection</label>
                <textarea name="rejectionReason" id="rejectionReason" required rows="4" placeholder="Enter rejection reason to notify the seller..."
                    class="w-full px-4 py-3 border border-gray-100 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-red-500/10 bg-white resize-none"></textarea>
            </div>

            <div class="flex gap-3 pt-2">
                <button type="button" @click="rejectModal = false"
                    class="flex-1 py-3 rounded-xl border border-gray-200 text-[10px] font-bold uppercase tracking-widest text-gray-500 hover:bg-gray-50 transition-all">
                    Cancel
                </button>
                <button type="submit" class="flex-1 py-3 bg-red-600 hover:bg-red-700 text-white text-[10px] font-bold uppercase tracking-widest transition-all">
                    Confirm Reject
                </button>
            </div>
        </form>
    </div>
</div>
</div>
@endsection
