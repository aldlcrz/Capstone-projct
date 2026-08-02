@extends('layouts.seller')

@section('content')
<div class="max-w-4xl mx-auto space-y-8" x-data="{
    paymentMethod: 'GCash',
    zoomImage: '',
    showZoomModal: false,
    openZoom(url) {
        this.zoomImage = url;
        this.showZoomModal = true;
    }
}">
    <!-- Header -->
    <div>
        <div class="text-[10px] font-bold text-[#C0422A] uppercase tracking-[0.2em] mb-1">Membership Plan</div>
        <h1 class="font-serif text-3xl font-bold text-black uppercase">
            Premium <span class="text-[#C0422A] italic lowercase">Upgrade</span>
        </h1>
    </div>

    @if($user->isPremiumActive())
        <!-- Active Subscription View -->
        <div class="rounded-3xl p-8 text-white border border-yellow-500/20 shadow-2xl relative overflow-hidden" style="background: linear-gradient(to bottom right, #1A1A1A, #2E2A24);">
            <div class="absolute -right-16 -top-16 w-48 h-48 bg-yellow-500/5 rounded-full blur-3xl"></div>
            <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
                <div class="space-y-4">
                    <div class="inline-flex items-center gap-2 px-3 py-1 bg-yellow-500/10 border border-yellow-500/30 text-yellow-400 rounded-full text-[10px] font-bold uppercase tracking-wider">
                        <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path></svg>
                        Premium Active
                    </div>
                    <h2 class="font-serif text-3xl font-bold tracking-tight">You are a Premium Seller</h2>
                    <p class="text-sm text-gray-400 max-w-lg leading-relaxed">
                        Enjoy the full benefits of unlimited product listings, verified featured badges, custom promotional hero banners, and priority placement on the marketplace search.
                    </p>
                    <div class="flex flex-wrap gap-x-6 gap-y-2 text-xs font-bold uppercase tracking-widest text-gray-300 pt-2 border-t border-white/5">
                        <div>Active Since: <span class="text-white font-black">{{ $latestSubscription && $latestSubscription->startsAt ? $latestSubscription->startsAt->format('M d, Y') : 'N/A' }}</span></div>
                        <div>Valid Until: <span class="text-yellow-400 font-black">{{ $user->premiumEndsAt ? $user->premiumEndsAt->format('M d, Y') : 'N/A' }}</span></div>
                    </div>
                </div>
                <div class="shrink-0 flex items-center justify-center w-24 h-24 bg-yellow-500/10 rounded-2xl border border-yellow-500/20 text-yellow-400">
                    <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"></path></svg>
                </div>
            </div>
        </div>

    @elseif($latestSubscription && $latestSubscription->status === 'pending')
        <!-- Pending Subscription View -->
        <div class="bg-white rounded-3xl p-8 border border-amber-100 shadow-lg flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div class="space-y-4 flex-1">
                <div class="inline-flex items-center gap-2 px-3 py-1 bg-amber-50 border border-amber-200 text-amber-700 rounded-full text-[10px] font-bold uppercase tracking-wider">
                    <svg class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                    Awaiting Verification
                </div>
                <h2 class="font-serif text-2xl font-bold tracking-tight text-black">Your Upgrade Request is Under Review</h2>
                <p class="text-xs text-gray-500 leading-relaxed max-w-xl">
                    You've submitted your payment of <strong>₱299.00</strong> via <strong>{{ $latestSubscription->paymentMethod }}</strong> (Reference ID: <span class="font-mono text-xs font-bold">{{ $latestSubscription->paymentReference }}</span>). The admin is checking the payment details. You will receive a notification immediately once it is approved.
                </p>
                @if($latestSubscription->paymentProof)
                    <button type="button" @click="openZoom('{{ asset('storage/' . $latestSubscription->paymentProof) }}')" class="inline-flex items-center gap-1.5 text-[10px] font-black uppercase tracking-widest text-[#C0422A] hover:underline">
                        View Uploaded Receipt Proof
                    </button>
                @endif
            </div>
            <div class="shrink-0 flex items-center justify-center w-20 h-20 bg-amber-50 rounded-2xl border border-amber-100 text-amber-500">
                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
        </div>

    @else
        <!-- Subscription Benefits & Upgrade Form -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
            
            <!-- Benefits Landing (Left) -->
            <div class="lg:col-span-5 bg-[#3D2B1F] text-white rounded-3xl p-6 md:p-8 space-y-6 shadow-xl relative overflow-hidden">
                <div class="absolute -right-20 -bottom-20 w-44 h-44 bg-white/5 rounded-full blur-2xl"></div>
                <div>
                    <div class="text-[9px] font-bold text-yellow-400 uppercase tracking-[0.2em] mb-1">LumBarong Premium</div>
                    <h2 class="font-serif text-2xl font-bold">Artisan Upgrade</h2>
                </div>
                <p class="text-xs text-stone-300 leading-relaxed">
                    Scale your digital workshop and connect with heritage craft enthusiasts with advanced tools.
                </p>
                <div class="space-y-4 pt-4 border-t border-white/10">
                    <div class="flex items-start gap-3.5">
                        <div class="w-5 h-5 rounded-full bg-yellow-400/10 flex items-center justify-center shrink-0 text-yellow-400 mt-0.5">
                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>
                        </div>
                        <div>
                            <h4 class="text-xs font-bold text-white uppercase tracking-wider">Unlimited Product Listings</h4>
                            <p class="text-[10px] text-stone-400 mt-0.5">Bypass the standard 10 product listing limit for standard accounts.</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-3.5">
                        <div class="w-5 h-5 rounded-full bg-yellow-400/10 flex items-center justify-center shrink-0 text-yellow-400 mt-0.5">
                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>
                        </div>
                        <div>
                            <h4 class="text-xs font-bold text-white uppercase tracking-wider">Premium Badge & Verification</h4>
                            <p class="text-[10px] text-stone-400 mt-0.5">Displays a distinctive gold premium tag on your profile and product listings.</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-3.5">
                        <div class="w-5 h-5 rounded-full bg-yellow-400/10 flex items-center justify-center shrink-0 text-yellow-400 mt-0.5">
                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>
                        </div>
                        <div>
                            <h4 class="text-xs font-bold text-white uppercase tracking-wider">Hero Banner Request</h4>
                            <p class="text-[10px] text-stone-400 mt-0.5">Request a custom promotional hero banner to be displayed on the platform homepage (subject to admin approval).</p>
                        </div>
                    </div>
                </div>

                <div class="pt-6 border-t border-white/10 flex items-center justify-between">
                    <div class="text-[10px] font-bold text-stone-400 uppercase tracking-widest">Subscription Price</div>
                    <div class="text-right">
                        <div class="text-2xl font-black text-yellow-400">₱299.00</div>
                        <div class="text-[9px] text-stone-400 font-bold uppercase tracking-widest">Per month (30 days)</div>
                    </div>
                </div>
            </div>

            <!-- Payment Portal Form (Right) -->
            <div class="lg:col-span-7 bg-white rounded-3xl p-6 md:p-8 border border-gray-100 shadow-sm space-y-6">
                @if($latestSubscription && $latestSubscription->status === 'rejected')
                    <!-- Rejected Notice -->
                    <div class="p-4 bg-red-50 border border-red-100 rounded-2xl flex items-start gap-3">
                        <div class="w-6 h-6 rounded-full bg-red-100 flex items-center justify-center text-red-600 shrink-0">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </div>
                        <div class="grow text-xs leading-relaxed">
                            <h4 class="font-bold text-red-700 uppercase tracking-wide">Previous Request Rejected</h4>
                            <p class="text-red-500 mt-0.5">Reason: {{ $latestSubscription->rejectionReason }}</p>
                            <p class="text-red-400/90 mt-1">Please verify your payment credentials and re-submit your upgrade request below.</p>
                        </div>
                    </div>
                @endif

                <h3 class="font-serif text-lg font-bold text-black border-b border-gray-50 pb-3">Payment details</h3>

                <!-- Admin GCash/Maya Info -->
                @if($admin)
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <!-- GCash Detail card -->
                        @if($admin->isGcashAvailable && $admin->gcashNumber)
                            <div class="p-4 bg-blue-50/50 border border-blue-100 rounded-2xl flex flex-col items-center text-center">
                                <span class="text-[9px] font-black uppercase text-blue-600 tracking-wider mb-2 bg-blue-50 px-2.5 py-0.5 rounded-full">Pay with GCash</span>
                                <div class="text-xs font-bold text-gray-500">Number</div>
                                <div class="text-sm font-black text-black select-all tracking-wider mt-0.5">{{ $admin->gcashNumber }}</div>
                                @if($admin->gcashQrCode)
                                    <button type="button" @click="openZoom('{{ asset('storage/' . $admin->gcashQrCode) }}')" class="mt-3 group relative w-20 h-20 bg-white border border-gray-200 rounded-lg overflow-hidden shrink-0 transition-transform active:scale-95 cursor-zoom-in">
                                        <img src="{{ asset('storage/' . $admin->gcashQrCode) }}" class="w-full h-full object-contain">
                                        <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 flex items-center justify-center text-[8px] text-white font-bold uppercase tracking-wider transition-opacity">Zoom</div>
                                    </button>
                                @endif
                            </div>
                        @endif

                        <!-- Maya Detail card -->
                        @if($admin->isMayaAvailable && $admin->mayaNumber)
                            <div class="p-4 bg-teal-50/50 border border-teal-100 rounded-2xl flex flex-col items-center text-center">
                                <span class="text-[9px] font-black uppercase text-teal-600 tracking-wider mb-2 bg-teal-50 px-2.5 py-0.5 rounded-full">Pay with Maya</span>
                                <div class="text-xs font-bold text-gray-500">Number</div>
                                <div class="text-sm font-black text-black select-all tracking-wider mt-0.5">{{ $admin->mayaNumber }}</div>
                                @if($admin->mayaQrCode)
                                    <button type="button" @click="openZoom('{{ asset('storage/' . $admin->mayaQrCode) }}')" class="mt-3 group relative w-20 h-20 bg-white border border-gray-200 rounded-lg overflow-hidden shrink-0 transition-transform active:scale-95 cursor-zoom-in">
                                        <img src="{{ asset('storage/' . $admin->mayaQrCode) }}" class="w-full h-full object-contain">
                                        <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 flex items-center justify-center text-[8px] text-white font-bold uppercase tracking-wider transition-opacity">Zoom</div>
                                    </button>
                                @endif
                            </div>
                        @endif
                    </div>
                @else
                    <div class="p-4 bg-gray-50 border border-gray-100 rounded-xl text-center text-xs text-gray-400 italic">
                        Admin payment methods have not been configured yet. Please contact support.
                    </div>
                @endif

                <!-- Upload Form -->
                <form action="{{ route('seller.subscription.subscribe') }}" method="POST" enctype="multipart/form-data" class="space-y-4 pt-2">
                    @csrf
                    
                    <!-- Method Selector -->
                    <div>
                        <label class="text-[9px] font-bold text-gray-400 uppercase tracking-widest block mb-2">Select Payment Method</label>
                        <div class="grid grid-cols-2 gap-3">
                            <label class="p-4 rounded-xl border flex items-center gap-3 cursor-pointer transition-all" :class="paymentMethod === 'GCash' ? 'border-blue-500 bg-blue-50/20' : 'border-gray-100 hover:border-gray-200'">
                                <input type="radio" name="paymentMethod" value="GCash" x-model="paymentMethod" class="accent-blue-500">
                                <span class="text-xs font-bold text-black">GCash</span>
                            </label>
                            <label class="p-4 rounded-xl border flex items-center gap-3 cursor-pointer transition-all" :class="paymentMethod === 'Maya' ? 'border-teal-500 bg-teal-50/20' : 'border-gray-100 hover:border-gray-200'">
                                <input type="radio" name="paymentMethod" value="Maya" x-model="paymentMethod" class="accent-teal-500">
                                <span class="text-xs font-bold text-black">Maya</span>
                            </label>
                        </div>
                    </div>

                    <!-- Reference Number -->
                    <div>
                        <label for="paymentReference" class="text-[9px] font-bold text-gray-400 uppercase tracking-widest block mb-1.5">Payment Reference Number</label>
                        <input type="text" name="paymentReference" id="paymentReference" required placeholder="Enter the transaction Reference ID"
                            class="w-full px-4 py-3 border border-gray-100 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-[#C0422A]/10 bg-white">
                    </div>

                    <!-- Receipt Upload -->
                    <div>
                        <label for="paymentProof" class="text-[9px] font-bold text-gray-400 uppercase tracking-widest block mb-1.5">Upload Receipt Screenshot</label>
                        <input type="file" name="paymentProof" id="paymentProof" required accept="image/*"
                            class="w-full text-xs text-gray-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-[10px] file:font-black file:uppercase file:tracking-widest file:bg-gray-100 file:text-gray-700 hover:file:bg-gray-200">
                    </div>

                    <button type="submit" class="w-full py-3.5 bg-black hover:bg-[#C0422A] text-white rounded-xl text-[10px] font-black uppercase tracking-widest transition-all mt-4">
                        Submit Payment Verification
                    </button>
                </form>
            </div>
        </div>
    @endif

<!-- Receipt Zoom Modal -->
<div x-show="showZoomModal" class="fixed inset-0 z-9999 flex items-center justify-center p-4 bg-black/60 backdrop-blur-md" x-cloak>
    <div @click.away="showZoomModal = false" class="relative max-w-lg w-full bg-white rounded-3xl overflow-hidden shadow-2xl p-6 flex flex-col items-center">
        <div class="w-full flex items-center justify-between pb-4 border-b border-gray-100 mb-4">
            <h3 class="font-serif text-lg font-bold text-black">Scan Payment</h3>
            <button type="button" @click="showZoomModal = false"
                class="w-8 h-8 rounded-full border border-gray-200 flex items-center justify-center text-gray-400 hover:text-black hover:border-gray-400 transition-all shrink-0">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        
        <div class="w-full bg-gray-50 rounded-2xl overflow-hidden flex items-center justify-center border border-gray-100 max-h-[70vh]">
            <img :src="zoomImage" class="max-w-full max-h-[60vh] object-contain" alt="QR Code">
        </div>
        
        <div class="w-full mt-4 flex gap-3">
            <a :href="zoomImage" download="qr_code" class="flex-1 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 text-center rounded-xl text-[10px] font-bold uppercase tracking-widest transition-all">
                Download Image
            </a>
            <button type="button" @click="showZoomModal = false" class="flex-1 py-3 bg-black hover:bg-[#C0422A] text-white rounded-xl text-[10px] font-bold uppercase tracking-widest transition-all">
                Close
            </button>
        </div>
    </div>
</div>
</div>
@endsection
