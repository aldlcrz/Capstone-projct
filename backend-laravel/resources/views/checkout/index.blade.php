@extends('layouts.app')

@section('content')
<div class="max-w-300 mx-auto px-4 py-12 min-h-screen bg-white" x-data="{
    step: 1,
    paymentMethod: '{{ ($paymentSource && !($paymentSource->isGcashAvailable ?? true) && ($paymentSource->isMayaAvailable ?? false)) ? 'Maya' : 'GCash' }}',
    address: @js($addresses->first() ?? [
        'recipientName' => '',
        'phone' => '',
        'houseNo' => '',
        'street' => '',
        'barangay' => '',
        'city' => '',
        'province' => '',
        'postalCode' => ''
    ]),
    showAddressModal: false,
    showConfirmModal: false,
    addresses: @js($addresses),
    zoomImage: '',
    showZoomModal: false,

    selectAddress(addr) {
        this.address = addr;
        this.showAddressModal = false;
    },
    requestPlaceOrder() {
        const form = document.getElementById('checkout-form');
        if (!form || !form.reportValidity()) return;
        this.showConfirmModal = true;
    },
    confirmPlaceOrder() {
        this.showConfirmModal = false;
        document.getElementById('checkout-form')?.submit();
    }
}">
    <!-- Mobile Header -->
    <div class="lg:hidden flex items-center justify-between mb-6 pb-3 border-b border-gray-100">
        <a href="/cart" class="flex items-center gap-2 text-gray-700 font-bold text-base hover:text-black">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
            </svg>
            Checkout
        </a>
        <div class="text-xs text-gray-400 font-bold uppercase tracking-widest" x-text="step === 1 ? 'Step 1 of 2' : 'Step 2 of 2'"></div>
    </div>

    <!-- Stepper (Desktop) -->
    <div class="hidden lg:flex items-center gap-12 mb-12 border-b border-gray-100 pb-2 overflow-x-auto no-scrollbar">
        <div class="flex items-center gap-3 border-b-2 pb-2 transition-colors" :class="step >= 1 ? 'border-black' : 'border-transparent text-gray-300'">
            <div class="w-6 h-6 rounded-full flex items-center justify-center" :class="step > 1 ? 'bg-gray-400' : 'bg-black'">
                <template x-if="step > 1">
                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                </template>
                <template x-if="step === 1">
                    <span class="text-[11px] font-bold text-white">1</span>
                </template>
            </div>
            <span class="text-sm font-bold whitespace-nowrap" :class="step >= 1 ? 'text-black' : 'text-gray-300'">Shipping Information</span>
        </div>
        <div class="flex items-center gap-3 border-b-2 pb-2 transition-colors" :class="step >= 2 ? 'border-black' : 'border-transparent text-gray-300'">
            <div class="w-6 h-6 rounded-full flex items-center justify-center" :class="step >= 2 ? 'bg-black' : 'border border-gray-300'">
                <span class="text-[11px] font-bold" :class="step >= 2 ? 'text-white' : 'text-gray-300'">2</span>
            </div>
            <span class="text-sm font-bold whitespace-nowrap" :class="step >= 2 ? 'text-black' : 'text-gray-300'">Payment Details</span>
        </div>
    </div>

    <form id="checkout-form" action="{{ route('checkout.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="mode" value="{{ $mode }}">
        <input type="hidden" name="shippingAddress" :value="JSON.stringify(address)">

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12 items-start pb-24 lg:pb-0">
            <!-- Main Content Area -->
            <div class="lg:col-span-7 space-y-8 lg:space-y-12">
                
                <!-- STEP 1: SHIPPING (Lazada Style) -->
                <div x-show="step === 1" x-transition>
                    
                    {{-- Lazada-Style Address Card --}}
                    <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-xs mb-6 relative overflow-hidden">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex items-start gap-3 flex-1">
                                <div class="w-9 h-9 rounded-full bg-blue-50 text-blue-600 flex items-center justify-center shrink-0 mt-0.5">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    </svg>
                                </div>
                                <div class="space-y-1">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span class="font-bold text-black text-sm" x-text="address.recipientName || 'Add Recipient Name'"></span>
                                        <span class="text-xs text-gray-500 font-medium" x-text="address.phone"></span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span class="bg-blue-100 text-blue-700 text-[9px] font-black uppercase px-1.5 py-0.5 rounded tracking-wider">HOME</span>
                                        <p class="text-xs text-gray-600 font-medium leading-snug" x-text="address.houseNo ? address.houseNo + ' ' + address.street + ', ' + address.barangay + ', ' + address.city : 'Please select your delivery address'"></p>
                                    </div>
                                </div>
                            </div>
                            <button type="button" @click="showAddressModal = true" class="text-xs font-bold text-blue-600 hover:text-blue-800 shrink-0">
                                Edit
                            </button>
                        </div>
                    </div>

                    {{-- Form Fields for Contact/Address Name --}}
                    <div class="space-y-4 bg-gray-50/50 p-5 rounded-2xl border border-gray-100 mb-6">
                        <div class="text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Recipient Details</div>
                        <div class="relative border rounded-xl p-3 bg-white border-gray-200 focus-within:border-black">
                            <label class="text-[9px] font-bold text-gray-400 uppercase tracking-widest block mb-0.5">Full Name</label>
                            <input type="text" x-model="address.recipientName" class="w-full font-bold text-black text-sm outline-none bg-transparent" placeholder="Recipient Name">
                        </div>

                        <div class="relative border rounded-xl p-3 bg-white border-gray-200 focus-within:border-black">
                            <label class="text-[9px] font-bold text-gray-400 uppercase tracking-widest block mb-0.5">Mobile Phone Number</label>
                            <input type="text" x-model="address.phone" class="w-full font-bold text-black text-sm outline-none bg-transparent" placeholder="Mobile Phone Number">
                        </div>
                    </div>

                    {{-- Lazada-Style Store Items Preview --}}
                    <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-xs mb-6 space-y-4">
                        <div class="flex items-center justify-between pb-3 border-b border-gray-100">
                            <div class="flex items-center gap-2">
                                <span class="bg-[#C0422A] text-white text-[9px] font-black uppercase px-2 py-0.5 rounded tracking-wider">LumBarong Store</span>
                                <span class="text-xs font-bold text-black">Official Store</span>
                            </div>
                            <span class="text-[10px] font-bold text-green-600 uppercase tracking-wider flex items-center gap-1">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                100% Authentic
                            </span>
                        </div>

                        <div class="space-y-4">
                            @foreach($cart as $item)
                                @php
                                    $img = $item['image'] ?? '';
                                    $imgSrc = (str_starts_with($img, 'http') || str_starts_with($img, '/')) ? $img : (str_starts_with($img, 'products/') ? asset('storage/' . $img) : asset('uploads/products/' . $img));
                                @endphp
                                <div class="flex gap-3 items-start">
                                    <img src="{{ $imgSrc }}" onerror="this.src='/uploads/products/default.jpg'" class="w-20 h-20 object-cover rounded-xl bg-gray-50 border border-gray-100 shrink-0">
                                    <div class="flex-1 min-w-0">
                                        <h4 class="text-xs font-bold text-gray-900 line-clamp-2 leading-snug">{{ $item['name'] }}</h4>
                                        <div class="text-[10px] text-gray-400 font-medium mt-1">
                                            @if(!empty($item['size'])) Size: {{ $item['size'] }} @endif
                                            @if(!empty($item['variation'])) | {{ $item['variation'] }} @endif
                                        </div>
                                        <div class="inline-block mt-1 bg-blue-50 text-blue-600 text-[9px] font-bold px-1.5 py-0.5 rounded">
                                            30 Days Free Returns
                                        </div>
                                        <div class="flex items-center justify-between mt-2">
                                            <div class="flex items-center gap-1.5">
                                                <span class="text-xs font-black text-[#C0422A]">₱{{ number_format($item['price']) }}</span>
                                                @if(!empty($item['is_on_sale']) && ($item['discount_percentage'] ?? 0) > 0)
                                                    <span class="text-[10px] text-gray-400 line-through">₱{{ number_format($item['original_price'] ?? $item['price']) }}</span>
                                                @endif
                                            </div>
                                            <span class="text-xs font-bold text-gray-500">Qty: {{ $item['quantity'] }}</span>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- Lazada-Style Delivery Guarantee Card --}}
                    @php
                        $shippingFee = 0;
                        foreach ($cart as $item) {
                            $itemShipping = (float) ($item['shippingFee'] ?? 0);
                            if ($itemShipping > $shippingFee) {
                                $shippingFee = $itemShipping;
                            }
                        }
                    @endphp
                    <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-xs space-y-1">
                        <div class="flex justify-between items-center">
                            <span class="text-xs font-bold text-gray-800">Guaranteed Delivery (3-5 Days)</span>
                            <span class="text-xs font-black text-black">
                                @if($shippingFee > 0) ₱{{ number_format($shippingFee, 2) }} @else Free @endif
                            </span>
                        </div>
                        <p class="text-[10px] text-gray-400 font-medium">Eligible for LumBarong delivery guarantee and buyer protection.</p>
                    </div>

                </div>

                <!-- STEP 2: PAYMENT (Lazada Style) -->
                <div x-show="step === 2" x-transition>
                    <div class="mb-6">
                        <h2 class="text-xl font-bold text-black mb-1">Select Payment Method</h2>
                        <p class="text-xs text-gray-400 font-medium">Choose your preferred payment channel.</p>
                    </div>

                    <div class="space-y-4">
                        @if(!$paymentSource || ($paymentSource->isGcashAvailable ?? true))
                        <div class="rounded-2xl border-2 p-5 transition-all" :class="paymentMethod === 'GCash' ? 'border-[#C0422A] bg-white shadow-sm' : 'border-gray-100'">
                            <label class="flex items-center justify-between cursor-pointer">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-xs font-black">GC</div>
                                    <div>
                                        <div class="font-bold text-gray-900 text-sm">GCash e-Wallet</div>
                                        <div class="text-[10px] text-gray-400">Direct QR or Mobile Transfer</div>
                                    </div>
                                </div>
                                <input type="radio" name="paymentMethod" value="GCash" x-model="paymentMethod" class="w-5 h-5 accent-[#C0422A]">
                            </label>
                            
                            <div x-show="paymentMethod === 'GCash'" class="mt-4 pt-4 border-t border-gray-100 flex gap-4 items-center">
                                <div class="w-1/3 bg-gray-50 rounded-2xl p-3 flex flex-col items-center justify-center @if($paymentSource && $paymentSource->gcashQrCode) cursor-zoom-in hover:bg-gray-100 transition-colors group/qr @endif"
                                     @if($paymentSource && $paymentSource->gcashQrCode) @click="zoomImage = '{{ asset('storage/' . $paymentSource->gcashQrCode) }}'; showZoomModal = true" @endif>
                                    @if($paymentSource && $paymentSource->gcashQrCode)
                                        <img src="{{ asset('storage/' . $paymentSource->gcashQrCode) }}" class="w-20 h-20 object-contain rounded-xl bg-white border border-gray-100 shadow-xs">
                                    @else
                                        <div class="w-20 h-20 bg-white rounded-xl mb-1 flex items-center justify-center text-gray-200">
                                            <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"></path></svg>
                                        </div>
                                    @endif
                                    <span class="text-[8px] font-black uppercase text-gray-400">Tap to Zoom QR</span>
                                </div>
                                <div class="flex-1">
                                    <div class="bg-blue-50/50 p-3.5 rounded-xl border border-blue-100">
                                        <div class="text-[9px] font-black text-blue-500 uppercase tracking-widest mb-0.5">Send Payment To</div>
                                        <div class="text-lg font-black text-black">{{ $paymentSource->gcashNumber ?? '0912 345 6789' }}</div>
                                        <div class="text-[10px] font-bold text-gray-500 mt-0.5">Name: {{ $paymentSource->shopName ?? ($seller->name ?? 'LumBarong Store') }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif

                        @if(!$paymentSource || ($paymentSource->isMayaAvailable ?? false))
                        <!-- Maya Option -->
                        <div class="rounded-2xl border-2 p-5 transition-all" :class="paymentMethod === 'Maya' ? 'border-[#C0422A] bg-white shadow-sm' : 'border-gray-100'">
                            <label class="flex items-center justify-between cursor-pointer">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-xl bg-green-50 text-green-600 flex items-center justify-center text-xs font-black">MY</div>
                                    <div>
                                        <div class="font-bold text-gray-900 text-sm">Maya e-Wallet</div>
                                        <div class="text-[10px] text-gray-400">Pay via Maya app or QR</div>
                                    </div>
                                </div>
                                <input type="radio" name="paymentMethod" value="Maya" x-model="paymentMethod" class="w-5 h-5 accent-[#C0422A]">
                            </label>

                            <div x-show="paymentMethod === 'Maya'" class="mt-4 pt-4 border-t border-gray-100 flex gap-4 items-center">
                                <div class="w-1/3 bg-gray-50 rounded-2xl p-3 flex flex-col items-center justify-center @if($paymentSource && $paymentSource->mayaQrCode) cursor-zoom-in hover:bg-gray-100 transition-colors group/qr @endif"
                                     @if($paymentSource && $paymentSource->mayaQrCode) @click="zoomImage = '{{ asset('storage/' . $paymentSource->mayaQrCode) }}'; showZoomModal = true" @endif>
                                    @if($paymentSource && $paymentSource->mayaQrCode)
                                        <img src="{{ asset('storage/' . $paymentSource->mayaQrCode) }}" class="w-20 h-20 object-contain rounded-xl bg-white border border-gray-100 shadow-xs">
                                    @else
                                        <div class="w-20 h-20 bg-white rounded-xl mb-1 flex items-center justify-center text-gray-200">
                                            <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"></path></svg>
                                        </div>
                                    @endif
                                    <span class="text-[8px] font-black uppercase text-gray-400">Tap to Zoom QR</span>
                                </div>
                                <div class="flex-1">
                                    <div class="bg-green-50/50 p-3.5 rounded-xl border border-green-100">
                                        <div class="text-[9px] font-black text-green-500 uppercase tracking-widest mb-0.5">Send Payment To</div>
                                        <div class="text-lg font-black text-black">{{ $paymentSource->mayaNumber ?? '0912 345 6789' }}</div>
                                        <div class="text-[10px] font-bold text-gray-500 mt-0.5">Name: {{ $paymentSource->shopName ?? ($seller->name ?? 'LumBarong Store') }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>

                    <!-- Payment Proof Upload Inputs -->
                    <div class="bg-gray-50 border border-gray-100 rounded-2xl p-5 mt-6 space-y-4">
                        <div class="text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Upload Proof of Payment</div>
                        <div class="space-y-3">
                            <input type="text" name="paymentReference" required placeholder="Payment Reference Number" class="w-full px-4 py-3 bg-white border-gray-200 border rounded-xl text-sm font-bold outline-none focus:border-black">
                            <input type="file" name="paymentScreenshot" required class="w-full text-xs text-gray-400 file:bg-black file:text-white file:rounded-lg file:border-0 file:px-4 file:py-2 file:mr-4 file:font-black">
                        </div>
                    </div>
                </div>

            </div>

            <!-- Desktop Sidebar -->
            <div class="hidden lg:block lg:col-span-5">
                <div class="bg-[#F9FAFB] rounded-[40px] p-8 border border-gray-100 sticky top-10">
                    <h2 class="text-xl font-bold mb-6">Order Summary</h2>
                    <div class="space-y-4 mb-8 max-h-75 overflow-y-auto pr-2 custom-scrollbar">
                        @foreach($cart as $item)
                            @php
                                $img = $item['image'] ?? '';
                                $imgSrc = (str_starts_with($img, 'http') || str_starts_with($img, '/')) ? $img : (str_starts_with($img, 'products/') ? asset('storage/' . $img) : asset('uploads/products/' . $img));
                            @endphp
                            <div class="bg-white rounded-2xl p-4 flex gap-4 border border-gray-50 shadow-sm">
                                <div class="w-16 h-16 bg-gray-50 rounded-xl overflow-hidden shrink-0">
                                    <img src="{{ $imgSrc }}" onerror="this.src='/uploads/products/default.jpg'" class="w-full h-full object-cover">
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center justify-between">
                                        <h3 class="text-xs font-bold text-black line-clamp-1">{{ $item['name'] }}</h3>
                                        @if(!empty($item['category_name']))
                                            <span class="text-[8px] font-bold text-gray-400 uppercase tracking-wider bg-gray-100 px-1.5 py-0.5 rounded ml-2 shrink-0">{{ $item['category_name'] }}</span>
                                        @endif
                                    </div>
                                    <div class="text-[9px] text-gray-400 font-bold uppercase mt-1">
                                        @if(!empty($item['size']))Size: {{ $item['size'] }}@else Size: Standard @endif
                                    </div>
                                    <div class="flex justify-between items-center mt-2">
                                        <span class="text-[10px] text-gray-400 font-bold">{{ $item['quantity'] }}x</span>
                                        <div class="flex items-center gap-1.5">
                                            @if(!empty($item['is_on_sale']) && ($item['discount_percentage'] ?? 0) > 0)
                                                <span class="text-xs font-bold text-[#C0422A]">₱{{ number_format($item['price'] * $item['quantity']) }}</span>
                                                <span class="text-[9px] text-gray-400 line-through">₱{{ number_format(($item['original_price'] ?? $item['price']) * $item['quantity']) }}</span>
                                            @else
                                                <span class="text-xs font-bold text-black">₱{{ number_format($item['price'] * $item['quantity']) }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="space-y-3 border-t border-gray-100 pt-6 mb-8">
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-400 font-medium">Subtotal</span>
                            <span class="text-sm font-bold text-black">₱{{ number_format($subtotal) }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-400 font-medium">Delivery</span>
                            <span class="text-sm font-bold text-black">
                                @if($shippingFee > 0)
                                    ₱{{ number_format($shippingFee, 2) }}
                                @else
                                    Free
                                @endif
                            </span>
                        </div>
                        <div class="flex justify-between items-center pt-3 border-t border-dashed border-gray-200">
                            <span class="text-lg font-bold text-black">Total</span>
                            <span class="text-2xl font-black text-[#C0422A]">₱{{ number_format($subtotal + $shippingFee) }}</span>
                        </div>
                    </div>

                    <template x-if="step === 1">
                        <button type="button" @click="step = 2" class="w-full bg-black text-white py-5 rounded-2xl text-sm font-bold shadow-xl shadow-black/10 hover:bg-[#C0422A] transition-all">
                            Proceed to Payment
                        </button>
                    </template>
                    <template x-if="step === 2">
                        <div class="flex gap-4">
                            <button type="button" @click="step = 1" class="w-1/4 border-2 border-gray-100 py-5 rounded-2xl flex items-center justify-center text-gray-400 hover:text-black">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                            </button>
                            <button type="button" @click="requestPlaceOrder()" class="flex-1 bg-[#C0422A] text-white py-5 rounded-2xl text-sm font-bold shadow-xl shadow-[#C0422A]/10 hover:bg-[#A33622] transition-all">
                                Place Order
                            </button>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </form>

    <!-- ===== Mobile Sticky Place Order Bar (Lazada Style) ===== -->
    <div class="lg:hidden fixed bottom-0 left-0 right-0 z-50 bg-white border-t border-gray-200 shadow-[0_-6px_24px_rgba(0,0,0,0.12)] p-3"
         x-data="{ showCheckoutBreakdown: false }">

        {{-- Expandable Price Breakdown on Mobile --}}
        <div x-show="showCheckoutBreakdown" 
             x-cloak
             x-transition
             class="mb-3 p-4 bg-gray-50 rounded-2xl border border-gray-100 text-xs space-y-2">
            <div class="flex justify-between items-center text-gray-500">
                <span>Subtotal ({{ count($cart) }} item{{ count($cart) > 1 ? 's' : '' }})</span>
                <span class="font-bold text-black">₱{{ number_format($subtotal) }}</span>
            </div>
            <div class="flex justify-between items-center text-gray-500">
                <span>Estimated Shipping</span>
                <span class="font-bold text-black">
                    @if($shippingFee > 0) ₱{{ number_format($shippingFee, 2) }} @else Free @endif
                </span>
            </div>
        </div>

        <div class="flex items-center justify-between gap-3">
            {{-- Savings & Total Breakdown Toggle --}}
            <div class="flex-1 min-w-0 pl-1">
                <button type="button" @click="showCheckoutBreakdown = !showCheckoutBreakdown" class="inline-flex items-center gap-1 text-[10px] font-bold uppercase tracking-widest text-gray-400 hover:text-black">
                    <span>Total Payment</span>
                    <svg class="w-3 h-3 transition-transform" :class="showCheckoutBreakdown ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div class="text-lg font-black text-[#C0422A] leading-tight">
                    ₱{{ number_format($subtotal + $shippingFee) }}
                </div>
            </div>

            {{-- Step 1 Button on Mobile --}}
            <template x-if="step === 1">
                <button type="button" @click="step = 2" class="px-6 py-3.5 bg-black text-white text-xs font-bold uppercase tracking-widest rounded-xl shadow-lg hover:bg-[#C0422A] transition-all">
                    Proceed to Payment
                </button>
            </template>

            {{-- Step 2 Dual Button on Mobile (Lazada Style Place Order) --}}
            <template x-if="step === 2">
                <div class="flex items-center gap-2">
                    <button type="button" @click="step = 1" class="w-10 h-11 border border-gray-200 rounded-xl flex items-center justify-center text-gray-500 hover:bg-gray-50">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    </button>
                    <button type="button" @click="requestPlaceOrder()" class="px-6 py-3.5 bg-[#C0422A] text-white text-xs font-bold uppercase tracking-widest rounded-xl shadow-lg hover:bg-[#A33622] transition-all">
                        Place Order
                    </button>
                </div>
            </template>
        </div>
    </div>

    <!-- Place Order Confirmation Modal -->
    <div x-show="showConfirmModal" class="fixed inset-0 z-110 flex items-center justify-center p-4" x-cloak>
        <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="showConfirmModal = false"></div>
        <div @click.away="showConfirmModal = false" class="relative bg-white w-full max-w-md rounded-3xl shadow-2xl p-8 space-y-6" x-transition>
            <div class="text-center">
                <div class="w-16 h-16 rounded-full bg-amber-50 flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                </div>
                <h3 class="font-serif text-xl font-bold text-black mb-2">Confirm Your Purchase?</h3>
                <p class="text-xs text-gray-500 leading-relaxed">
                    Please make sure your shipping details and payment proof are correct before placing this order.
                </p>
            </div>
            <div class="bg-red-50 border border-red-100 rounded-2xl p-4">
                <p class="text-[11px] font-bold text-red-700 uppercase tracking-widest mb-1">No refund policy</p>
                <p class="text-xs text-red-600 leading-relaxed">
                    All purchases on LumBarong are final sale. Once payment is confirmed, this order
                    <span class="font-bold">strictly cannot be cancelled or refunded</span>.
                </p>
            </div>
            <div class="flex gap-3">
                <button type="button" @click="showConfirmModal = false"
                    class="flex-1 py-3 rounded-xl border border-gray-200 text-[10px] font-bold uppercase tracking-widest text-gray-500 hover:bg-gray-50 transition-all">
                    Go Back
                </button>
                <button type="button" @click="confirmPlaceOrder()"
                    class="flex-1 py-3 rounded-xl bg-[#C0422A] text-white text-[10px] font-bold uppercase tracking-widest hover:bg-[#A33622] transition-all">
                    Yes, Place Order
                </button>
            </div>
        </div>
    </div>

    <!-- Address Book Modal -->
    <div x-show="showAddressModal" class="fixed inset-0 z-9999 flex items-center justify-center p-4" x-cloak>
        <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="showAddressModal = false"></div>
        <div class="relative bg-white w-full max-w-lg rounded-[40px] shadow-2xl overflow-hidden" x-transition>
            <div class="p-8 border-b border-gray-50 flex justify-between items-center">
                <h3 class="text-xl font-bold">Select Address</h3>
                <button @click="showAddressModal = false" class="text-gray-400 hover:text-black">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            <div class="p-8 space-y-4 max-h-[60vh] overflow-y-auto custom-scrollbar">
                <template x-for="addr in addresses" :key="addr.id">
                    <div class="border-2 rounded-2xl p-4 cursor-pointer transition-all hover:border-black" :class="address.id === addr.id ? 'border-black bg-gray-50' : 'border-gray-50'" @click="selectAddress(addr)">
                        <div class="flex gap-4">
                            <div class="w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center text-gray-400 shrink-0">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                            </div>
                            <div>
                                <div class="text-sm font-bold text-black" x-text="addr.recipientName"></div>
                                <div class="text-[10px] text-gray-400 font-bold mt-1" x-text="addr.phone"></div>
                                <p class="text-xs text-gray-500 mt-2" x-text="addr.houseNo + ' ' + addr.street + ', ' + addr.barangay + ', ' + addr.city"></p>
                            </div>
                        </div>
                    </div>
                </template>
                <a href="{{ route('profile.addresses') }}" class="flex items-center justify-center gap-2 p-4 border-2 border-dashed border-gray-200 rounded-2xl text-sm font-bold text-gray-400 hover:text-black hover:border-black transition-all">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    Manage Addresses
                </a>
            </div>
        </div>
    </div>

    <!-- QR Code Zoom Modal -->
    <template x-if="showZoomModal">
        <div 
            class="fixed inset-0 z-9999 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm"
            @click="showZoomModal = false"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
        >
            <div 
                @click.stop
                class="bg-white rounded-4xl border border-gray-100 shadow-2xl p-6 relative overflow-hidden max-w-sm w-full flex flex-col items-center justify-center"
                x-transition:enter="transition ease-out duration-300 transform"
                x-transition:enter-start="opacity-0 scale-95 translate-y-4"
                x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                x-transition:leave="transition ease-in duration-200 transform"
                x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                x-transition:leave-end="opacity-0 scale-95 translate-y-4"
            >
                <!-- Close Button -->
                <button 
                    @click="showZoomModal = false"
                    class="absolute top-4 right-4 w-8 h-8 flex items-center justify-center rounded-full text-gray-400 hover:text-black hover:bg-gray-100 transition-colors"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>

                <!-- Modal Content -->
                <div class="text-[9px] font-bold uppercase tracking-[0.2em] text-[#C0422A] mb-2 mt-2">Scan QR Code</div>
                <h3 class="font-serif text-base font-bold text-gray-900 leading-tight mb-4" x-text="paymentMethod === 'GCash' ? 'GCash Payment QR' : 'Maya Payment QR'"></h3>
                
                <div class="w-64 h-64 bg-gray-50 rounded-2xl overflow-hidden border border-gray-100 shadow-sm flex items-center justify-center p-4">
                    <img :src="zoomImage" class="max-w-full max-h-full object-contain" alt="QR Code">
                </div>

                <p class="text-[10px] text-gray-400 font-medium text-center mt-4">Click anywhere outside or close to return.</p>
            </div>
        </div>
    </template>
</div>
@endsection
