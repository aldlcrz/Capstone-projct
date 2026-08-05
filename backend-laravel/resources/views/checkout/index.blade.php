@extends('layouts.app')

@section('content')
<div class="max-w-[1200px] mx-auto px-4 py-12 min-h-screen bg-white" x-data="{
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
    <!-- Stepper -->
    <div class="flex items-center gap-12 mb-12 border-b border-gray-100 pb-2 overflow-x-auto no-scrollbar">
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

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 items-start">
            <!-- Main Content Area -->
            <div class="lg:col-span-7 space-y-12">
                
                <!-- STEP 1: SHIPPING -->
                <div x-show="step === 1" x-transition>
                    <div class="mb-8">
                        <h1 class="text-2xl font-bold text-black mb-2">Check Out Your Items</h1>
                        <p class="text-sm text-gray-400 font-medium">Verify your shipping details before proceeding.</p>
                    </div>

                    <div class="space-y-6">
                        <div class="relative border-2 rounded-xl p-4 transition-all border-gray-100 focus-within:border-black">
                            <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1 block">Full Name</label>
                            <input type="text" x-model="address.recipientName" class="w-full font-bold text-black outline-none bg-transparent" placeholder="Full Name">
                        </div>

                        <button type="button" @click="showAddressModal = true" class="w-full relative border-2 border-gray-100 rounded-xl p-6 text-left hover:border-black transition-colors group cursor-pointer">
                            <div class="flex items-start justify-between">
                                <div class="flex gap-4">
                                    <svg class="w-5 h-5 text-gray-400 mt-1 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                                    <div>
                                        <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block">Delivery Address</label>
                                        <p class="font-medium text-gray-500 mt-1" x-text="address.houseNo ? address.houseNo + ' ' + address.street + ', ' + address.barangay + ', ' + address.city : 'Select an address'"></p>
                                    </div>
                                </div>
                                <svg class="w-4 h-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                            </div>
                        </button>

                        <div class="relative border-2 rounded-xl p-4 transition-all border-gray-100 focus-within:border-black">
                            <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1 block">Phone Number</label>
                            <input type="text" x-model="address.phone" class="w-full font-bold text-black outline-none bg-transparent" placeholder="Phone Number">
                        </div>
                    </div>
                </div>

                <!-- STEP 2: PAYMENT -->
                <div x-show="step === 2" x-transition>
                    <div class="mb-8">
                        <h2 class="text-xl font-bold text-black mb-1">Payment Method</h2>
                        <p class="text-sm text-gray-400 font-medium">Select your preferred payment option.</p>
                    </div>

                    <div class="space-y-4">
                        @if(!$paymentSource || ($paymentSource->isGcashAvailable ?? true))
                        <div class="rounded-2xl border-2 p-6 transition-all" :class="paymentMethod === 'GCash' ? 'border-black bg-white' : 'border-gray-50'">
                            <label class="flex items-center justify-between cursor-pointer">
                                <div class="flex items-center gap-4">
                                    <div class="bg-blue-50 text-blue-600 px-3 py-1 rounded-lg text-xs font-black">GCASH</div>
                                    <span class="font-bold text-gray-700">GCash</span>
                                </div>
                                <input type="radio" name="paymentMethod" value="GCash" x-model="paymentMethod" class="w-5 h-5 accent-black">
                            </label>
                            
                            <div x-show="paymentMethod === 'GCash'" class="mt-6 pt-6 border-t border-gray-50 flex gap-6">
                                <div class="w-1/3 bg-gray-50 rounded-2xl p-4 flex flex-col items-center justify-center @if($paymentSource && $paymentSource->gcashQrCode) cursor-zoom-in hover:bg-gray-100 transition-colors group/qr @endif"
                                     @if($paymentSource && $paymentSource->gcashQrCode) @click="zoomImage = '{{ asset('storage/' . $paymentSource->gcashQrCode) }}'; showZoomModal = true" @endif>
                                    @if($paymentSource && $paymentSource->gcashQrCode)
                                        <img src="{{ asset('storage/' . $paymentSource->gcashQrCode) }}" class="w-24 h-24 object-contain rounded-xl bg-white border border-gray-100 group-hover/qr:scale-105 transition-transform duration-300 shadow-sm">
                                    @else
                                        <div class="w-24 h-24 bg-white rounded-xl mb-2 flex items-center justify-center text-gray-200">
                                            <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"></path></svg>
                                        </div>
                                    @endif
                                    <span class="text-[9px] font-black uppercase text-gray-400 @if($paymentSource && $paymentSource->gcashQrCode) group-hover/qr:text-[#C0422A] @endif transition-colors">Scan QR</span>
                                </div>
                                <div class="flex-1">
                                    <div class="bg-blue-50/50 p-4 rounded-xl border border-blue-100">
                                        <div class="text-[9px] font-black text-blue-400 uppercase tracking-widest mb-1">Send Payment To</div>
                                        <div class="text-xl font-black text-black">{{ $paymentSource->gcashNumber ?? '0912 345 6789' }}</div>
                                        <div class="text-[10px] font-bold text-gray-500 mt-1">Name: {{ $paymentSource->shopName ?? ($seller->name ?? 'LumBarong Store') }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif

                        @if(!$paymentSource || ($paymentSource->isMayaAvailable ?? false))
                        <!-- Maya Option -->
                        <div class="rounded-2xl border-2 p-6 transition-all" :class="paymentMethod === 'Maya' ? 'border-black bg-white' : 'border-gray-50'">
                            <label class="flex items-center justify-between cursor-pointer">
                                <div class="flex items-center gap-4">
                                    <div class="bg-green-50 text-green-600 px-3 py-1 rounded-lg text-xs font-black">MAYA</div>
                                    <span class="font-bold text-gray-700">Maya</span>
                                </div>
                                <input type="radio" name="paymentMethod" value="Maya" x-model="paymentMethod" class="w-5 h-5 accent-black">
                            </label>

                            <div x-show="paymentMethod === 'Maya'" class="mt-6 pt-6 border-t border-gray-50 flex gap-6">
                                <div class="w-1/3 bg-gray-50 rounded-2xl p-4 flex flex-col items-center justify-center @if($paymentSource && $paymentSource->mayaQrCode) cursor-zoom-in hover:bg-gray-100 transition-colors group/qr @endif"
                                     @if($paymentSource && $paymentSource->mayaQrCode) @click="zoomImage = '{{ asset('storage/' . $paymentSource->mayaQrCode) }}'; showZoomModal = true" @endif>
                                    @if($paymentSource && $paymentSource->mayaQrCode)
                                        <img src="{{ asset('storage/' . $paymentSource->mayaQrCode) }}" class="w-24 h-24 object-contain rounded-xl bg-white border border-gray-100 group-hover/qr:scale-105 transition-transform duration-300 shadow-sm">
                                    @else
                                        <div class="w-24 h-24 bg-white rounded-xl mb-2 flex items-center justify-center text-gray-200">
                                            <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"></path></svg>
                                        </div>
                                    @endif
                                    <span class="text-[9px] font-black uppercase text-gray-400 @if($paymentSource && $paymentSource->mayaQrCode) group-hover/qr:text-[#C0422A] @endif transition-colors">Scan QR</span>
                                </div>
                                <div class="flex-1">
                                    <div class="bg-green-50/50 p-4 rounded-xl border border-green-100">
                                        <div class="text-[9px] font-black text-green-400 uppercase tracking-widest mb-1">Send Payment To</div>
                                        <div class="text-xl font-black text-black">{{ $paymentSource->mayaNumber ?? '0912 345 6789' }}</div>
                                        <div class="text-[10px] font-bold text-gray-500 mt-1">Name: {{ $paymentSource->shopName ?? ($seller->name ?? 'LumBarong Store') }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>

                    <!-- Common Reference and Screenshot Proof Inputs -->
                    <div class="bg-[#F9F7F4]/50 border border-gray-100 rounded-2xl p-6 mt-6 space-y-4">
                        <div class="text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Upload Proof of Payment</div>
                        <div class="space-y-3">
                            <input type="text" name="paymentReference" required placeholder="Reference Number" class="w-full px-4 py-3 bg-white border-gray-100 border rounded-xl text-sm font-bold outline-none focus:border-black">
                            <input type="file" name="paymentScreenshot" required class="w-full text-xs text-gray-400 file:bg-black file:text-white file:rounded-lg file:border-0 file:px-4 file:py-2 file:mr-4 file:font-black">
                        </div>
                    </div>
                </div>

            </div>

            <!-- Sidebar -->
            <div class="lg:col-span-5">
                <div class="bg-[#F9FAFB] rounded-[40px] p-8 border border-gray-100 sticky top-10">
                    <h2 class="text-xl font-bold mb-6">Order Summary</h2>
                    <div class="space-y-4 mb-8 max-h-[300px] overflow-y-auto pr-2 custom-scrollbar">
                        @foreach($cart as $item)
                            @php
                                $img = $item['image'] ?? '';
                                $imgSrc = str_starts_with($img, 'http') ? $img : (str_starts_with($img, 'products/') ? asset('storage/' . $img) : asset('uploads/products/' . $img));
                            @endphp
                            <div class="bg-white rounded-2xl p-4 flex gap-4 border border-gray-50 shadow-sm">
                                <div class="w-16 h-16 bg-gray-50 rounded-xl overflow-hidden shrink-0">
                                    <img src="{{ $imgSrc }}" class="w-full h-full object-cover">
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

                    @php
                        // Calculate maximum shipping fee among all items in this checkout
                        $shippingFee = 0;
                        foreach ($cart as $item) {
                            $itemShipping = (float) ($item['shippingFee'] ?? 0);
                            if ($itemShipping > $shippingFee) {
                                $shippingFee = $itemShipping;
                            }
                        }
                    @endphp
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
    <div x-show="showAddressModal" class="fixed inset-0 z-[9999] flex items-center justify-center p-4" x-cloak>
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
            class="fixed inset-0 z-[9999] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm"
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
                class="bg-white rounded-[32px] border border-gray-100 shadow-2xl p-6 relative overflow-hidden max-w-sm w-full flex flex-col items-center justify-center"
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
