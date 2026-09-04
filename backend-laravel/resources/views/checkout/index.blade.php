@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto px-4 pt-2 pb-0 sm:py-6 lg:py-8" x-data="checkoutApp(
    @js($addresses->first() ?? [
        'recipientName' => '',
        'phone' => '',
        'houseNo' => '',
        'street' => '',
        'barangay' => '',
        'city' => '',
        'province' => '',
        'postalCode' => ''
    ]),
    @js($addresses),
    '{{ ($paymentSource && !($paymentSource->isGcashAvailable ?? true) && ($paymentSource->isMayaAvailable ?? false)) ? 'Maya' : 'GCash' }}'
)">

    {{-- Back Link & Page Title Header --}}
    <div class="mb-6 lg:mb-8">
        <button type="button" @click="if (step === 2) { step = 1; window.scrollTo({ top: 0, behavior: 'smooth' }); } else if (window.history.length > 1) { window.history.back(); } else { window.location.href='/cart'; }" class="inline-flex items-center gap-2 text-[10px] font-bold text-gray-400 uppercase tracking-widest hover:text-[#C0422A] transition-colors mb-3">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
            Back
        </button>
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div>
                <div class="flex items-center gap-2 mb-1">
                    <div class="w-5 h-[1.5px] bg-[#C0422A]"></div>
                    <span class="text-[10px] font-bold uppercase tracking-widest text-[#C0422A]">Secure Checkout</span>
                </div>
                <h1 class="font-serif text-2xl sm:text-3xl font-bold text-black">Order Checkout</h1>
            </div>
            
            <div class="text-xs text-gray-400 font-bold uppercase tracking-widest lg:hidden" x-text="step === 1 ? 'Step 1 of 2: Shipping' : 'Step 2 of 2: Payment'"></div>
        </div>
    </div>



    <form id="checkout-form" action="{{ route('checkout.store') }}" method="POST" enctype="multipart/form-data" @submit="isSubmitting = true">
        @csrf
        <input type="hidden" name="mode" value="{{ $mode }}">
        <input type="hidden" name="shippingAddress" :value="JSON.stringify(address)">

        <div class="space-y-6 pb-24 lg:pb-0 w-full">
            <!-- Main Content Area -->
            <div class="space-y-6 w-full">
                
                <!-- STEP 1: SHIPPING INFORMATION -->
                <div x-show="step === 1" x-transition class="space-y-6">
                    
                    {{-- Delivery Address Card --}}
                    <div class="bg-white rounded-2xl border border-gray-100 p-4 sm:p-6 shadow-sm mb-6 relative overflow-hidden">
                        <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-3.5">
                            <div class="flex items-start gap-3 sm:gap-4 flex-1 min-w-0">
                                <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-[#FDF9F4] border border-[#C0422A]/20 text-[#C0422A] flex items-center justify-center shrink-0 mt-0.5">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    </svg>
                                </div>
                                <div class="space-y-1.5 min-w-0 flex-1">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span class="font-bold text-gray-900 text-sm lg:text-base" x-text="address.recipientName || 'Add Recipient Name'"></span>
                                        <span class="text-xs lg:text-sm text-gray-500 font-medium" x-text="address.phone"></span>
                                    </div>
                                    <div class="flex items-start gap-2 flex-wrap">
                                        <span class="bg-[#C0422A]/10 text-[#C0422A] text-[9px] font-black uppercase px-2 py-0.5 rounded-md tracking-wider border border-[#C0422A]/20 shrink-0 mt-0.5">HOME</span>
                                        <p class="text-xs lg:text-sm text-gray-700 font-medium leading-relaxed flex-1 min-w-0 wrap-break-word" x-text="address.houseNo ? address.houseNo + ' ' + (address.street ? address.street + ', ' : '') + (address.barangay ? address.barangay + ', ' : '') + address.city : 'Please select your delivery address'"></p>
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center gap-2 shrink-0 pt-2 sm:pt-0 border-t sm:border-t-0 border-gray-100 justify-end">
                                <button type="button" @click="openEditAddress()" class="inline-flex items-center gap-1 text-xs font-bold text-gray-700 hover:text-black px-2.5 py-1 bg-gray-50 rounded-lg border border-gray-200 transition-colors">
                                    <svg class="w-3.5 h-3.5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                    Edit
                                </button>
                                <button type="button" @click="showAddressModal = true" class="inline-flex items-center text-xs font-bold text-[#C0422A] hover:text-[#A33622] px-2.5 py-1 bg-[#FDF9F4] rounded-lg border border-[#C0422A]/20 transition-colors whitespace-nowrap">
                                    Change Address
                                </button>
                            </div>
                        </div>
                    </div>

@php
    $resolveQrUrl = function ($qrPath) {
        if (empty($qrPath)) return null;
        if (str_starts_with($qrPath, 'http://') || str_starts_with($qrPath, 'https://')) return $qrPath;
        $clean = ltrim($qrPath, '/');
        while (str_starts_with($clean, 'storage/')) {
            $clean = ltrim(substr($clean, 8), '/');
        }
        if (str_starts_with($clean, 'uploads/')) return asset($clean);
        if (file_exists(public_path($clean))) return asset($clean);
        return asset('storage/' . $clean);
    };
    $gcashQrUrl = $paymentSource && !empty($paymentSource->gcashQrCode) ? $resolveQrUrl($paymentSource->gcashQrCode) : null;
    $mayaQrUrl  = $paymentSource && !empty($paymentSource->mayaQrCode) ? $resolveQrUrl($paymentSource->mayaQrCode) : null;
@endphp
                    {{-- Store Items Preview Card --}}
                    <div class="bg-white rounded-2xl border border-gray-100 p-4 sm:p-6 shadow-sm mb-6 space-y-4">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pb-3 border-b border-gray-100">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="bg-[#C0422A] text-white text-[9px] lg:text-[10px] font-black uppercase px-2.5 py-0.5 rounded-md tracking-wider whitespace-nowrap">{{ $seller->shopName ?? ($seller->name ?? 'LumBarong Store') }}</span>
                                <span class="text-xs sm:text-sm font-bold text-gray-900">{{ $seller ? ($seller->shopDescription ?? 'Official Heritage Artisan') : 'Official Heritage Store' }}</span>
                            </div>
                            <span class="text-[10px] lg:text-xs font-bold text-amber-800 uppercase tracking-wider flex items-center gap-1 bg-amber-50 px-2.5 py-1 rounded-md border border-amber-200/60 w-fit">
                                <svg class="w-3.5 h-3.5 text-amber-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                100% Authentic Handcrafted
                            </span>
                        </div>

                        <div class="divide-y divide-gray-100">
                            @foreach($cart as $item)
                                @php
                                    $itemProduct = !empty($item['id']) ? \App\Models\Product::find($item['id']) : null;
                                    $variationLabel = !empty($item['variation']) 
                                        ? (\App\Support\VariationFormatter::label($item['variation'], $itemProduct?->image) ?? $item['variation'])
                                        : null;
                                    $imgSrc = \App\Support\VariationFormatter::getImageForVariation($item['variation'] ?? null, $itemProduct)
                                        ?: (!empty($item['image']) ? $item['image'] : ($itemProduct ? $itemProduct->getImageUrl() : asset('uploads/products/default.jpg')));
                                @endphp
                                <div class="flex gap-3.5 sm:gap-4 py-3.5 first:pt-0 last:pb-0 items-start">
                                    <img src="{{ $imgSrc }}" onerror="this.src='/uploads/products/default.jpg'" class="w-16 h-16 sm:w-20 sm:h-20 lg:w-24 lg:h-24 object-cover rounded-xl bg-gray-50 border border-gray-100 shrink-0">
                                    <div class="flex-1 min-w-0">
                                        <h4 class="text-xs sm:text-sm lg:text-base font-bold text-gray-900 line-clamp-2 leading-snug">{{ $item['name'] }}</h4>
                                        <div class="text-[10px] sm:text-xs text-gray-500 font-medium mt-1 flex items-center gap-2 flex-wrap">
                                            @if(!empty($item['size'])) 
                                                <span class="inline-flex items-center px-2 py-0.5 rounded bg-stone-100 text-gray-800 text-[10px] font-bold">
                                                    Size: <strong class="ml-1 text-black">{{ $item['size'] }}</strong>
                                                </span> 
                                            @endif
                                            @if(!empty($variationLabel) && strcasecmp($variationLabel, 'Original') !== 0) 
                                                <span class="inline-flex items-center px-2 py-0.5 rounded bg-amber-50 text-amber-900 border border-amber-200/60 text-[10px] font-bold">
                                                    Style: <strong class="ml-1 text-amber-950">{{ $variationLabel }}</strong>
                                                </span> 
                                            @endif
                                        </div>
                                        <div class="inline-flex items-center gap-1 mt-1.5 bg-amber-50 text-amber-900 text-[9px] lg:text-[10px] font-bold px-2 py-0.5 rounded border border-amber-200/50 max-w-full">
                                            <span class="shrink-0">🛡️</span> <span class="truncate">30 Days Heritage Return Guarantee</span>
                                        </div>
                                        <div class="flex items-center justify-between mt-2.5 flex-wrap gap-1">
                                            <div class="flex items-center gap-2">
                                                <span class="text-sm lg:text-base font-black text-[#C0422A]">₱{{ number_format($item['price'], 2) }}</span>
                                                @if(!empty($item['is_on_sale']) && ($item['discount_percentage'] ?? 0) > 0)
                                                    <span class="text-[10px] lg:text-xs text-gray-400 line-through">₱{{ number_format($item['original_price'] ?? $item['price'], 2) }}</span>
                                                @endif
                                            </div>
                                            <span class="text-xs lg:text-sm font-bold text-gray-600 bg-gray-100 px-2.5 py-0.5 rounded-md">Qty: {{ $item['quantity'] }}</span>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- Delivery Guarantee Card --}}
                    @php
                        $shippingFee = 0;
                        foreach ($cart as $item) {
                            $itemShipping = (float) ($item['shippingFee'] ?? 0);
                            if ($itemShipping > $shippingFee) {
                                $shippingFee = $itemShipping;
                            }
                        }
                    @endphp
                    <div class="bg-[#FDF9F4] rounded-2xl border border-[#C0422A]/20 p-4 sm:p-5 lg:p-6 shadow-xs space-y-1">
                        <div class="flex justify-between items-center gap-2">
                            <div class="flex items-center gap-2">
                                <svg class="w-5 h-5 text-[#C0422A] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <span class="text-xs sm:text-sm lg:text-base font-bold text-gray-900">Standard Delivery (3-5 Days)</span>
                            </div>
                            <span class="text-xs sm:text-sm lg:text-base font-black text-black shrink-0">
                                @if($shippingFee > 0) ₱{{ number_format($shippingFee, 2) }} @else <span class="text-emerald-600 uppercase text-xs">FREE</span> @endif
                            </span>
                        </div>
                        <p class="text-[10px] lg:text-xs text-gray-500 font-medium pl-7">Protected by LumBarong nationwide delivery guarantee & secure packaging.</p>
                    </div>

                </div>

                <!-- STEP 2: PAYMENT METHOD & PROOF UPLOAD -->
                <div x-show="step === 2" x-transition class="space-y-6">
                    <div class="mb-6">
                        <h2 class="font-serif text-xl lg:text-2xl font-bold text-black mb-1">Select Payment Channel</h2>
                        <p class="text-xs lg:text-sm text-gray-500 font-medium">Choose your e-wallet payment option below and submit your reference receipt.</p>
                    @php
                        $resolveQrUrl = function ($qrPath) {
                            if (empty($qrPath)) return null;
                            if (str_starts_with($qrPath, 'http://') || str_starts_with($qrPath, 'https://')) return $qrPath;
                            $clean = ltrim($qrPath, '/');
                            while (str_starts_with($clean, 'storage/')) {
                                $clean = ltrim(substr($clean, 8), '/');
                            }
                            if (str_starts_with($clean, 'uploads/')) return asset($clean);
                            if (file_exists(public_path($clean))) return asset($clean);
                            return asset('storage/' . $clean);
                        };

                        $rawGcashQr = $paymentSource->gcashQrCode ?? ($seller->gcashQrCode ?? null);
                        $gcashQrUrl = $rawGcashQr ? $resolveQrUrl($rawGcashQr) : null;

                        $rawMayaQr  = $paymentSource->mayaQrCode ?? ($seller->mayaQrCode ?? null);
                        $mayaQrUrl  = $rawMayaQr ? $resolveQrUrl($rawMayaQr) : null;
                    @endphp

                    <div class="space-y-4">
                        @if(!$paymentSource || ($paymentSource->isGcashAvailable ?? true))
                        <div class="rounded-2xl border-2 p-4 sm:p-5 transition-all duration-200" :class="paymentMethod === 'GCash' ? 'border-[#C0422A] bg-[#FDF9F4]/40 shadow-sm' : 'border-gray-100 bg-white hover:border-gray-200'">
                            <label class="flex items-center justify-between cursor-pointer">
                                <div class="flex items-center gap-3.5">
                                    <div class="w-10 h-10 rounded-xl bg-blue-600 text-white flex items-center justify-center text-xs font-black shadow-sm shrink-0">GC</div>
                                    <div>
                                        <div class="font-bold text-gray-900 text-sm lg:text-base">GCash e-Wallet</div>
                                        <div class="text-[10px] lg:text-xs text-gray-500">Scan QR or Transfer to Mobile Number</div>
                                    </div>
                                </div>
                                <input type="radio" name="paymentMethod" value="GCash" x-model="paymentMethod" class="w-5 h-5 accent-[#C0422A] cursor-pointer">
                            </label>
                            
                            <div x-show="paymentMethod === 'GCash'" class="mt-4 pt-4 border-t border-gray-100 flex flex-col sm:flex-row gap-3 sm:gap-4 items-center" x-transition>
                                <div class="w-full sm:w-1/3 bg-white border border-gray-100 rounded-2xl p-3 flex flex-col items-center justify-center shadow-xs @if($gcashQrUrl) cursor-zoom-in hover:border-[#C0422A]/40 transition-all group/qr @endif"
                                     @if($gcashQrUrl) @click="zoomImage = '{{ $gcashQrUrl }}'; showZoomModal = true" @endif>
                                    @if($gcashQrUrl)
                                        <img src="{{ $gcashQrUrl }}" class="w-24 h-24 sm:w-20 sm:h-20 lg:w-24 lg:h-24 object-contain rounded-xl bg-white border border-gray-100 shadow-xs" alt="GCash QR">
                                    @else
                                        <div class="w-20 h-20 lg:w-24 lg:h-24 bg-gray-50 rounded-xl mb-1 flex items-center justify-center text-gray-300 border border-dashed border-gray-200">
                                            <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"></path></svg>
                                        </div>
                                    @endif
                                    <span class="text-[8px] lg:text-[9px] font-black uppercase text-[#C0422A] tracking-wider mt-1">Tap to Zoom QR</span>
                                </div>
                                <div class="w-full sm:flex-1">
                                    <div class="bg-[#FDF9F4] p-3.5 sm:p-4 rounded-xl border border-[#C0422A]/20">
                                        <div class="text-[9px] lg:text-[10px] font-black text-[#C0422A] uppercase tracking-widest mb-0.5">Send GCash Payment To</div>
                                        <div class="text-base sm:text-lg lg:text-xl font-black text-black tracking-wide">{{ $paymentSource->gcashNumber ?? '0912 345 6789' }}</div>
                                        <div class="text-[10px] lg:text-xs font-bold text-gray-600 mt-1">Account: <span class="text-gray-900">{{ $paymentSource->shopName ?? ($seller->name ?? 'LumBarong Official') }}</span></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif

                        @if(!$paymentSource || ($paymentSource->isMayaAvailable ?? false))
                        <!-- Maya Option -->
                        <div class="rounded-2xl border-2 p-4 sm:p-5 transition-all duration-200" :class="paymentMethod === 'Maya' ? 'border-[#C0422A] bg-[#FDF9F4]/40 shadow-sm' : 'border-gray-100 bg-white hover:border-gray-200'">
                            <label class="flex items-center justify-between cursor-pointer">
                                <div class="flex items-center gap-3.5">
                                    <div class="w-10 h-10 rounded-xl bg-emerald-600 text-white flex items-center justify-center text-xs font-black shadow-sm shrink-0">MY</div>
                                    <div>
                                        <div class="font-bold text-gray-900 text-sm lg:text-base">Maya e-Wallet</div>
                                        <div class="text-[10px] lg:text-xs text-gray-500">Pay via Maya App or Scan QR</div>
                                    </div>
                                </div>
                                <input type="radio" name="paymentMethod" value="Maya" x-model="paymentMethod" class="w-5 h-5 accent-[#C0422A] cursor-pointer">
                            </label>

                            <div x-show="paymentMethod === 'Maya'" class="mt-4 pt-4 border-t border-gray-100 flex flex-col sm:flex-row gap-3 sm:gap-4 items-center" x-transition>
                                <div class="w-full sm:w-1/3 bg-white border border-gray-100 rounded-2xl p-3 flex flex-col items-center justify-center shadow-xs @if($mayaQrUrl) cursor-zoom-in hover:border-[#C0422A]/40 transition-all group/qr @endif"
                                     @if($mayaQrUrl) @click="zoomImage = '{{ $mayaQrUrl }}'; showZoomModal = true" @endif>
                                    @if($mayaQrUrl)
                                        <img src="{{ $mayaQrUrl }}" class="w-24 h-24 sm:w-20 sm:h-20 lg:w-24 lg:h-24 object-contain rounded-xl bg-white border border-gray-100 shadow-xs" alt="Maya QR">
                                    @else
                                        <div class="w-20 h-20 lg:w-24 lg:h-24 bg-gray-50 rounded-xl mb-1 flex items-center justify-center text-gray-300 border border-dashed border-gray-200">
                                            <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"></path></svg>
                                        </div>
                                    @endif
                                    <span class="text-[8px] lg:text-[9px] font-black uppercase text-[#C0422A] tracking-wider mt-1">Tap to Zoom QR</span>
                                </div>
                                <div class="w-full sm:flex-1">
                                    <div class="bg-[#FDF9F4] p-3.5 sm:p-4 rounded-xl border border-[#C0422A]/20">
                                        <div class="text-[9px] lg:text-[10px] font-black text-[#C0422A] uppercase tracking-widest mb-0.5">Send Maya Payment To</div>
                                        <div class="text-base sm:text-lg lg:text-xl font-black text-black tracking-wide">{{ $paymentSource->mayaNumber ?? '0912 345 6789' }}</div>
                                        <div class="text-[10px] lg:text-xs font-bold text-gray-600 mt-1">Account: <span class="text-gray-900">{{ $paymentSource->shopName ?? ($seller->name ?? 'LumBarong Official') }}</span></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>

                    <!-- Payment Proof Upload Inputs -->
                    <div class="bg-white border border-gray-100 rounded-2xl p-5 lg:p-6 mt-6 shadow-sm space-y-4">
                        <div class="flex items-center gap-2 border-b border-gray-100 pb-3">
                            <div class="w-2 h-2 rounded-full bg-[#C0422A]"></div>
                            <h3 class="text-xs lg:text-sm font-bold text-gray-900 uppercase tracking-wider">Upload Proof of Payment</h3>
                        </div>
                        
                        <div class="space-y-4">
                            <div class="space-y-1.5">
                                <div class="flex items-center justify-between flex-wrap gap-1">
                                    <label class="text-[9px] lg:text-[10px] font-bold text-gray-500 uppercase tracking-widest block">Payment Reference Number <span class="text-[#C0422A]">*</span></label>
                                    <span class="text-[9px] font-bold text-gray-400" x-text="paymentMethod === 'GCash' ? 'GCash requirement: 13 digits' : 'Maya requirement: 12 digits'"></span>
                                </div>
                                <input type="text"
                                       id="paymentReferenceInput"
                                       name="paymentReference"
                                       x-model="paymentRef"
                                       @input="handleRefInput()"
                                       @blur="validateRef()"
                                       inputmode="numeric"
                                       :maxlength="paymentMethod === 'GCash' ? 13 : 12"
                                       required
                                       :placeholder="paymentMethod === 'GCash' ? 'e.g. 1002345678901 (13-digit GCash Reference)' : 'e.g. 123456789012 (12-digit Maya Reference)'"
                                       :class="refError ? 'border-red-500 focus:ring-red-200 bg-red-50/20' : (hasReceiptMismatch() ? 'border-rose-400 focus:border-rose-400 focus:ring-rose-200 bg-rose-50/20' : (isRefValid() ? 'border-emerald-500 focus:border-emerald-500 focus:ring-emerald-500/10 bg-emerald-50/10' : 'border-gray-200 focus:border-[#C0422A] focus:ring-[#C0422A]/10 bg-gray-50/50'))"
                                       class="w-full px-4 py-3 border rounded-xl text-sm lg:text-base font-bold outline-none focus:ring-4 transition-all">
                                <div x-show="refError" x-cloak x-text="refError" class="text-xs font-bold text-red-500 px-1 mt-1"></div>
                                <div x-show="!refError && hasReceiptMismatch()" x-cloak class="text-[10px] lg:text-xs text-amber-700 font-bold px-1 mt-0.5 flex items-center gap-1">
                                    <svg class="w-3.5 h-3.5 text-amber-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                    <span>⚠️ Notice: Receipt screenshot requires manual seller verification against reference number.</span>
                                </div>
                                <div x-show="!refError && !hasReceiptMismatch() && isRefValid()" x-cloak class="text-[10px] lg:text-xs text-emerald-600 font-bold px-1 mt-0.5 flex items-center gap-1">
                                    <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                    <span x-text="isReceiptVerified() ? (paymentMethod === 'GCash' ? '✓ Valid GCash Reference & Receipt Verified (13 digits)' : '✓ Valid Maya Reference & Receipt Verified (12 digits)') : (paymentMethod === 'GCash' ? 'Valid GCash Reference Format (13 digits)' : 'Valid Maya Reference Format (12 digits)')"></span>
                                </div>
                            </div>

                            <div class="space-y-1.5">
                                <label class="text-[9px] lg:text-[10px] font-bold text-gray-500 uppercase tracking-widest block">Payment Receipt Screenshot <span class="text-[#C0422A]">*</span></label>
                                
                                <input type="file" 
                                       id="paymentScreenshotInput" 
                                       name="paymentScreenshot" 
                                       accept="image/*" 
                                       required 
                                       @change="handleFileChange($event)" 
                                       class="sr-only">

                                <!-- Upload Card Dropzone -->
                                <div x-show="!fileName" 
                                     @click="document.getElementById('paymentScreenshotInput').click()" 
                                     class="cursor-pointer py-6 px-4 bg-gray-50/70 border-2 border-dashed border-gray-200 rounded-2xl text-center hover:border-[#C0422A] hover:bg-[#FDF9F4]/40 transition-all flex flex-col items-center justify-center gap-2 group">
                                    <div class="w-12 h-12 rounded-2xl bg-white border border-gray-200 text-gray-400 group-hover:text-[#C0422A] group-hover:border-[#C0422A]/30 flex items-center justify-center shadow-xs transition-all">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    </div>
                                    <div>
                                        <div class="inline-flex items-center gap-1.5 text-xs sm:text-sm font-bold text-gray-800 group-hover:text-[#C0422A]">
                                            <span class="px-3 py-1.5 bg-[#C0422A] text-white rounded-xl text-[10px] font-black uppercase tracking-wider shadow-xs">Attach Screenshot</span>
                                            <span class="hidden sm:inline text-gray-500">or tap to select</span>
                                        </div>
                                        <p class="text-[10px] text-gray-400 mt-2">PNG, JPG, or JPEG (Clear receipt showing ref # & amount)</p>
                                    </div>
                                </div>

                                <!-- Active Attached File Display -->
                                <div x-show="fileName" x-cloak class="space-y-2">
                                    <div class="p-3.5 bg-[#FDF9F4] border-2 border-[#C0422A]/30 rounded-2xl flex items-center justify-between gap-3 shadow-xs">
                                        <div class="flex items-center gap-3 min-w-0">
                                            <template x-if="filePreview">
                                                <img :src="filePreview" class="w-12 h-12 object-cover rounded-xl border border-gray-200 shrink-0 bg-white">
                                            </template>
                                            <template x-if="!filePreview">
                                                <div class="w-10 h-10 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center shrink-0">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                                </div>
                                            </template>
                                            <div class="min-w-0">
                                                <div class="flex items-center gap-1.5">
                                                    <span class="text-xs font-bold text-gray-900 truncate" x-text="fileName"></span>
                                                    <span class="text-[9px] font-black uppercase text-emerald-600 bg-emerald-50 px-1.5 py-0.5 rounded border border-emerald-200 shrink-0">Attached</span>
                                                </div>
                                                <p class="text-[10px] text-gray-500 font-medium" x-text="aiChecking ? 'Verifying receipt...' : (aiVerificationResult ? 'Verification Complete' : 'Receipt image attached')"></p>
                                            </div>
                                        </div>
                                        <button type="button" @click="removeFile()" class="p-2 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-xl transition-colors shrink-0 cursor-pointer" title="Remove photo">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                        </button>
                                    </div>

                                    <!-- Verification Status Card -->
                                    <div x-show="aiChecking" x-cloak class="p-3 bg-amber-50/80 border border-amber-200 rounded-xl flex items-center gap-2.5 text-xs text-amber-900 font-bold animate-pulse">
                                        <span class="inline-block w-2.5 h-2.5 rounded-full bg-[#C0422A] animate-ping"></span>
                                        <span>Scanning receipt image & cross-referencing with payment details...</span>
                                    </div>

                                    <template x-if="!aiChecking && aiVerificationResult">
                                        <div class="p-3.5 rounded-xl border flex items-start gap-2.5 text-xs font-bold transition-all"
                                             :class="{
                                                 'bg-emerald-50/80 border-emerald-200 text-emerald-800': aiVerificationResult.status === 'PASS',
                                                 'bg-amber-50/80 border-amber-200 text-amber-900': aiVerificationResult.status === 'REVIEW',
                                                 'bg-rose-50/80 border-rose-200 text-rose-800': aiVerificationResult.status === 'REJECT' || aiVerificationResult.is_receipt === false
                                             }">
                                            <span class="text-sm shrink-0" x-text="aiVerificationResult.status === 'PASS' ? '✓' : (aiVerificationResult.status === 'REVIEW' ? '⚠️' : '❌')"></span>
                                            <div class="space-y-1">
                                                <div class="font-extrabold uppercase text-[10px] tracking-wider"
                                                     x-text="aiVerificationResult.status === 'PASS' 
                                                         ? 'Receipt Verification Passed' 
                                                         : (aiVerificationResult.status === 'REVIEW' ? 'Manual Verification Required' : 'Receipt Verification Rejected')"></div>
                                                <p class="text-[11px] font-medium leading-relaxed" x-text="aiVerificationResult.message"></p>
                                                <template x-if="aiVerificationResult.status === 'REVIEW' && !aiVerificationResult.ref_matched && aiVerificationResult.detected_ref">
                                                    <div class="text-[10px] font-mono bg-amber-100/70 text-amber-900 px-2 py-1 rounded-lg mt-1 inline-block">
                                                        Detected Ref: <span class="font-bold" x-text="aiVerificationResult.detected_ref"></span>
                                                    </div>
                                                </template>
                                            </div>
                                        </div>
                                    </template>
                                </div>

                                <div x-show="screenshotError && !aiVerificationResult" x-cloak x-text="screenshotError" class="text-xs font-bold text-red-500 px-1 mt-1"></div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Order Summary Card (Desktop only, mobile uses the sticky place order bar) -->
            <div class="hidden lg:block w-full bg-white rounded-3xl p-5 sm:p-6 lg:p-8 border border-gray-100 shadow-sm space-y-5 sm:space-y-6">
                <div>
                    <div class="flex items-center gap-2 mb-1">
                        <div class="w-4 h-[1.5px] bg-[#C0422A]"></div>
                        <span class="text-[9px] font-bold uppercase tracking-widest text-[#C0422A]">Order Overview</span>
                    </div>
                    <h2 class="font-serif text-xl sm:text-2xl font-bold text-gray-900">Order Summary</h2>
                    <p class="text-xs text-gray-400 font-bold uppercase tracking-wider mt-1">
                        {{ count($cart) }} item(s) selected
                    </p>
                </div>

                <div class="space-y-3.5 border-t border-gray-100 pt-5 sm:pt-6">
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600 font-medium">Subtotal</span>
                        <span class="text-sm font-bold text-gray-900">₱{{ number_format($subtotal) }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600 font-medium">Estimated Delivery</span>
                        <span class="text-sm font-bold text-gray-900">
                            @if($shippingFee > 0)
                                ₱{{ number_format($shippingFee, 2) }}
                            @else
                                <span class="text-emerald-600 uppercase text-xs">Free</span>
                            @endif
                        </span>
                    </div>
                    <div class="flex justify-between items-center pt-4 border-t border-dashed border-gray-200">
                        <span class="text-base font-bold text-gray-900">Total Payment</span>
                        <span class="text-2xl lg:text-3xl font-black text-[#C0422A]">₱{{ number_format($subtotal + $shippingFee) }}</span>
                    </div>
                </div>

                <template x-if="step === 1">
                    <div class="space-y-2 hidden lg:block">
                        <div x-show="addressStepError" x-cloak
                             class="text-[10px] font-bold text-red-500 bg-red-50 border border-red-100 rounded-xl px-3 py-2 text-center"
                             x-text="addressStepError"></div>
                        <button type="button" @click="validateStep1()" class="w-full bg-[#C0422A] text-white py-4 rounded-xl text-sm font-bold shadow-lg shadow-[#C0422A]/20 hover:bg-[#A33622] transition-all transform active:scale-[0.99] flex items-center justify-center gap-2">
                            <span>Proceed to Payment</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                        </button>
                    </div>
                </template>
                <template x-if="step === 2">
                    <div class="gap-3 hidden lg:flex">
                        <button type="button" @click="step = 1" class="px-4 py-4 border border-gray-200 rounded-xl flex items-center justify-center text-gray-500 hover:text-black hover:border-black transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                        </button>
                        <button type="button" 
                                @click="requestPlaceOrder()" 
                                :disabled="aiChecking"
                                :class="aiChecking ? 'opacity-60 cursor-not-allowed bg-[#C0422A]/80' : 'hover:bg-[#A33622] active:scale-[0.99] cursor-pointer shadow-lg shadow-[#C0422A]/20'"
                                class="flex-1 bg-[#C0422A] text-white py-4 rounded-xl text-sm font-bold transition-all flex items-center justify-center gap-2">
                            <span x-show="!aiChecking" class="inline-flex items-center gap-2">
                                <span>Place Order</span>
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            </span>
                            <span x-show="aiChecking" x-cloak class="inline-flex items-center gap-2">
                                <svg class="w-4 h-4 animate-spin text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span>Scanning Receipt...</span>
                            </span>
                        </button>
                    </div>
                </template>

                <div class="bg-gray-50/80 rounded-2xl p-4 border border-gray-100/80 text-[11px] text-gray-500 leading-relaxed space-y-1">
                    <div class="font-bold text-gray-700 flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5 text-[#C0422A]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                        LumBarong Buyer Protection
                    </div>
                    <p class="text-[10px]">Your order payment is securely processed and verified directly by the seller before fulfillment.</p>
                </div>
            </div>
        </div>
    </form>

    <!-- Mobile Sticky Place Order Bar -->
    <div class="lg:hidden fixed inset-x-0 bottom-0 z-50 bg-white border-t border-gray-200 shadow-[0_-4px_20px_rgba(0,0,0,0.08)] px-4 py-3"
         style="position: fixed; bottom: 0 !important; left: 0 !important; right: 0 !important; width: 100% !important; margin: 0 !important; padding-bottom: max(12px, env(safe-area-inset-bottom, 12px)) !important;"
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
                    @if($shippingFee > 0) ₱{{ number_format($shippingFee, 2) }} @else <span class="text-emerald-600">Free</span> @endif
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
                <div class="flex flex-col items-end gap-1">
                    <button type="button" @click="validateStep1()" class="px-6 py-3 bg-[#C0422A] text-white text-xs font-bold uppercase tracking-widest rounded-xl shadow-lg hover:bg-[#A33622] transition-all">
                        Proceed to Payment
                    </button>
                    <div x-show="addressStepError" x-cloak
                         class="text-[9px] font-bold text-red-500 text-right max-w-45"
                         x-text="addressStepError"></div>
                </div>
            </template>

            {{-- Step 2 Dual Button on Mobile --}}
            <template x-if="step === 2">
                <div class="flex items-center gap-2">
                    <button type="button" @click="step = 1" class="w-10 h-11 border border-gray-200 rounded-xl flex items-center justify-center text-gray-500 hover:bg-gray-50">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    </button>
                    <button type="button" 
                            @click="requestPlaceOrder()" 
                            :disabled="aiChecking"
                            :class="aiChecking ? 'opacity-60 cursor-not-allowed bg-[#C0422A]/80' : 'hover:bg-[#A33622] active:scale-95 cursor-pointer shadow-lg'"
                            class="px-6 py-3 bg-[#C0422A] text-white text-xs font-bold uppercase tracking-widest rounded-xl transition-all flex items-center gap-1.5">
                        <span x-show="!aiChecking">Place Order</span>
                        <span x-show="aiChecking" x-cloak class="flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5 animate-spin text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span>Scanning...</span>
                        </span>
                    </button>
                </div>
            </template>
        </div>
    </div>

    <!-- Step 1: Shop Policy Notice Modal (Shown when clicking Proceed to Payment) -->
    <div x-show="showPolicyModal" class="fixed inset-0 z-110 flex items-center justify-center p-4" style="display: none;" x-cloak>
        <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="showPolicyModal = false"></div>
        <div @click.away="showPolicyModal = false" class="relative bg-white w-full max-w-lg rounded-3xl shadow-2xl p-6 sm:p-8 space-y-5 max-h-[90vh] overflow-y-auto custom-scrollbar" x-transition>
            <div class="text-center">
                <div class="w-14 h-14 rounded-full bg-amber-50 flex items-center justify-center mx-auto mb-3 border border-amber-200/50">
                    <svg class="w-7 h-7 text-[#C0422A]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                </div>
                <h3 class="font-serif text-xl sm:text-2xl font-bold text-black mb-1.5">Shop Policy Notice</h3>
                <p class="text-xs text-gray-500 leading-relaxed max-w-md mx-auto">
                    Please review and accept the shop’s cancellation and refund policies before proceeding to payment.
                </p>
            </div>

            {{-- Policy Notice Box --}}
            <div class="bg-amber-50/80 border border-amber-200/80 rounded-2xl p-4 text-left space-y-1.5">
                <div class="flex items-center gap-1.5 text-xs font-bold text-[#C0422A] uppercase tracking-wider">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span>Important Policy Notice</span>
                </div>
                <p class="text-xs text-amber-950 leading-relaxed">
                    <strong>Important:</strong> Please review the shop’s cancellation and refund policy before placing your order. Once payment is confirmed, cancellation may not be allowed, depending on the shop’s policy. Some shops may not accept cancellations or refunds after payment confirmation. Please make sure your order details are correct and that you wish to proceed before completing your purchase.
                </p>
            </div>

            {{-- Per-Shop Policies Section --}}
            @php
                $orderSellers = (!empty($sellers) && $sellers->count() > 0) ? $sellers : ($seller ? collect([$seller]) : collect());
            @endphp
            @if($orderSellers->count() > 0)
            <div class="space-y-2">
                <div class="text-[10px] font-bold text-gray-400 uppercase tracking-widest px-1">Artisan Shop Policies</div>
                <div class="space-y-2">
                    @foreach($orderSellers as $s)
                    <div class="p-3 bg-stone-50/80 rounded-2xl border border-stone-200 text-left text-xs space-y-2" x-data="{ expanded: false }">
                        <div class="flex items-center justify-between cursor-pointer select-none" @click="expanded = !expanded">
                            <span class="font-bold text-gray-900 flex items-center gap-1.5 truncate">
                                <span class="px-1.5 py-0.5 bg-[#C0422A] text-white text-[8px] font-black rounded uppercase shrink-0">Artisan</span>
                                <span class="truncate">{{ $s->shopName ?: $s->name }}</span>
                            </span>
                            <button type="button" class="text-[10px] font-bold text-[#C0422A] flex items-center gap-1 shrink-0 ml-2 hover:underline cursor-pointer">
                                <span x-text="expanded ? 'Hide Policies' : 'View Policies'"></span>
                                <svg class="w-3 h-3 transition-transform" :class="expanded ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                        </div>
                        <div x-show="expanded" x-collapse class="space-y-2 pt-2 border-t border-stone-200 text-[11px] text-gray-600">
                            <div>
                                <span class="font-bold text-amber-800 block text-[10px] uppercase tracking-wider">Cancellation Policy:</span>
                                <p class="mt-0.5 leading-relaxed bg-white/80 p-2 rounded-xl border border-stone-100">{{ $s->getCancellationPolicy() }}</p>
                            </div>
                            <div>
                                <span class="font-bold text-blue-800 block text-[10px] uppercase tracking-wider">Refund & Return Policy:</span>
                                <p class="mt-0.5 leading-relaxed bg-white/80 p-2 rounded-xl border border-stone-100">{{ $s->getRefundPolicy() }}</p>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Consent Checkbox --}}
            <label class="flex items-start gap-3 cursor-pointer text-left select-none bg-stone-50/80 hover:bg-stone-50 p-3.5 rounded-2xl border border-stone-200 transition-all">
                <input type="checkbox" x-model="policyAccepted" class="mt-0.5 w-4 h-4 rounded text-[#C0422A] accent-[#C0422A] focus:ring-[#C0422A] cursor-pointer shrink-0">
                <span class="text-xs text-gray-700 leading-relaxed font-medium">
                    I understand that once my payment is confirmed, my order may not be cancellable or refundable depending on the shop’s policy. I have reviewed the shop’s cancellation and refund policy and wish to proceed with this purchase.
                </span>
            </label>

            {{-- Modal Buttons --}}
            <div class="flex gap-3 pt-1">
                <button type="button" @click="showPolicyModal = false"
                    class="flex-1 py-3.5 rounded-xl border border-gray-200 text-[10px] font-bold uppercase tracking-widest text-gray-600 hover:bg-gray-50 transition-all cursor-pointer">
                    Go Back
                </button>
                <button type="button" @click="proceedToPaymentStep()"
                    :disabled="!policyAccepted"
                    :class="!policyAccepted ? 'opacity-40 cursor-not-allowed bg-gray-400' : 'bg-[#C0422A] hover:bg-[#A33622] cursor-pointer shadow-md shadow-[#C0422A]/20 active:scale-95'"
                    class="flex-1 py-3.5 rounded-xl text-white text-[10px] font-bold uppercase tracking-widest transition-all">
                    Proceed to Payment
                </button>
            </div>
        </div>
    </div>

    <!-- Step 2: Place Order Confirmation Modal (Final check before submitting order) -->
    <div x-show="showConfirmModal" class="fixed inset-0 z-110 flex items-center justify-center p-4" style="display: none;" x-cloak>
        <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="showConfirmModal = false"></div>
        <div @click.away="showConfirmModal = false" class="relative bg-white w-full max-w-lg rounded-3xl shadow-2xl p-6 sm:p-8 space-y-5 max-h-[90vh] overflow-y-auto custom-scrollbar" x-transition>
            <div class="text-center">
                <div class="w-14 h-14 rounded-full bg-emerald-50 flex items-center justify-center mx-auto mb-3 border border-emerald-200/50">
                    <svg class="w-7 h-7 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <h3 class="font-serif text-xl sm:text-2xl font-bold text-black mb-1.5">Confirm Your Order</h3>
                <p class="text-xs text-gray-500 leading-relaxed max-w-md mx-auto">
                    Please ensure that your delivery address and payment receipt details are accurate before placing your order.
                </p>
            </div>

            {{-- Summary Card --}}
            <div class="bg-gray-50/90 border border-gray-200/80 rounded-2xl p-4 text-left space-y-2 text-xs">
                <div class="flex justify-between items-center text-gray-600">
                    <span>Payment Method:</span>
                    <span class="font-bold text-gray-900" x-text="paymentMethod"></span>
                </div>
                <div class="flex justify-between items-center text-gray-600">
                    <span>Reference Number:</span>
                    <span class="font-mono font-bold text-gray-900" x-text="paymentRef"></span>
                </div>
                <div class="flex justify-between items-center text-gray-600 pt-2 border-t border-gray-200">
                    <span class="font-bold text-gray-900">Total Payment:</span>
                    <span class="font-black text-[#C0422A] text-base">₱{{ number_format($subtotal + $shippingFee) }}</span>
                </div>
            </div>

            {{-- Modal Buttons --}}
            <div class="flex gap-3 pt-1">
                <button type="button" @click="showConfirmModal = false"
                    class="flex-1 py-3.5 rounded-xl border border-gray-200 text-[10px] font-bold uppercase tracking-widest text-gray-600 hover:bg-gray-50 transition-all cursor-pointer">
                    Go Back
                </button>
                <button type="button" @click="confirmPlaceOrder()"
                    class="flex-1 py-3.5 rounded-xl bg-[#C0422A] hover:bg-[#A33622] text-white text-[10px] font-bold uppercase tracking-widest cursor-pointer shadow-md shadow-[#C0422A]/20 active:scale-95 transition-all">
                    Yes, Place Order
                </button>
            </div>
        </div>
    </div>

    <!-- Address Book Modal -->
    <div x-show="showAddressModal" class="fixed inset-0 z-9999 flex items-center justify-center p-4" x-cloak>
        <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="showAddressModal = false"></div>
        <div class="relative bg-white w-full max-w-lg rounded-3xl shadow-2xl overflow-hidden" x-transition>
            <div class="p-6 border-b border-gray-100 flex justify-between items-center">
                <div class="flex items-center gap-2">
                    <div class="w-4 h-[1.5px] bg-[#C0422A]"></div>
                    <h3 class="font-serif text-xl font-bold text-gray-900">Select Delivery Address</h3>
                </div>
                <button @click="showAddressModal = false" class="text-gray-400 hover:text-black">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            <div class="p-6 space-y-3.5 max-h-[60vh] overflow-y-auto custom-scrollbar">
                <template x-for="addr in addresses" :key="addr.id">
                    <div class="border-2 rounded-2xl p-4 cursor-pointer transition-all hover:border-[#C0422A] relative group" :class="address.id === addr.id ? 'border-[#C0422A] bg-[#FDF9F4]/50 shadow-xs' : 'border-gray-100'" @click="selectAddress(addr)">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex gap-3.5 flex-1">
                                <div class="w-9 h-9 rounded-xl bg-[#FDF9F4] text-[#C0422A] border border-[#C0422A]/20 flex items-center justify-center shrink-0 mt-0.5">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                </div>
                                <div>
                                    <div class="text-sm font-bold text-black" x-text="addr.recipientName"></div>
                                    <div class="text-[10px] text-gray-500 font-bold mt-0.5" x-text="addr.phone"></div>
                                    <p class="text-xs text-gray-600 mt-1.5 leading-relaxed" x-text="addr.houseNo + ' ' + (addr.street ? addr.street + ', ' : '') + (addr.barangay ? addr.barangay + ', ' : '') + addr.city"></p>
                                </div>
                            </div>
                            <button type="button" @click.stop="openEditAddress(addr)" class="text-xs font-bold text-[#C0422A] hover:underline px-2 py-1 rounded hover:bg-[#C0422A]/10 transition-colors">
                                Edit
                            </button>
                        </div>
                    </div>
                </template>
                <div class="flex flex-col sm:flex-row gap-3 pt-2">
                    <button type="button" @click="openEditAddress(null)" class="flex-1 flex items-center justify-center gap-2 p-3.5 border-2 border-dashed border-[#C0422A]/40 rounded-2xl text-xs font-bold uppercase tracking-wider text-[#C0422A] hover:bg-[#FDF9F4] transition-all">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                        Add New Address
                    </button>
                    <a href="{{ route('profile.addresses') }}" class="flex-1 flex items-center justify-center gap-2 p-3.5 border border-gray-200 rounded-2xl text-xs font-bold uppercase tracking-wider text-gray-500 hover:text-black hover:border-black transition-all text-center">
                        Manage Saved Addresses
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit / Add Address Modal -->
    <div x-show="showEditAddressModal" class="fixed inset-0 z-10000 flex items-center justify-center p-4" x-cloak>
        <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="showEditAddressModal = false"></div>
        <div class="relative bg-white w-full max-w-lg rounded-3xl shadow-2xl overflow-hidden p-6 sm:p-8 space-y-5 max-h-[90vh] overflow-y-auto" x-transition>
            <div class="flex items-center justify-between border-b border-gray-100 pb-4">
                <div class="flex items-center gap-2">
                    <div class="w-4 h-[1.5px] bg-[#C0422A]"></div>
                    <h3 class="font-serif text-xl font-bold text-gray-900" x-text="editForm.id ? 'Edit Delivery Address' : 'Add New Address'"></h3>
                </div>
                <button type="button" @click="showEditAddressModal = false" class="text-gray-400 hover:text-black">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            <div x-show="addressError" x-cloak x-text="addressError" class="p-3 bg-red-50 border border-red-200 text-red-600 rounded-xl text-xs font-bold"></div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4">
                <div class="col-span-1 sm:col-span-2 space-y-1">
                    <label class="text-[10px] font-bold text-gray-500 uppercase tracking-wider block">Recipient Full Name <span class="text-[#C0422A]">*</span></label>
                    <input type="text" x-model="editForm.recipientName" @input="editForm.recipientName = editForm.recipientName.replace(/[^a-zA-Z\s\.\,\'\-]/g, '')" placeholder="e.g. John Doe (letters only)" class="w-full px-4 py-2.5 bg-gray-50/50 border border-gray-200 rounded-xl text-sm font-semibold outline-none focus:border-[#C0422A] focus:bg-white transition-all">
                </div>

                <div class="col-span-1 sm:col-span-2 space-y-1">
                    <label class="text-[10px] font-bold text-gray-500 uppercase tracking-wider block">Phone Number <span class="text-[#C0422A]">*</span></label>
                    <input type="text" x-model="editForm.phone" @input="editForm.phone = editForm.phone.replace(/[^0-9]/g, '').slice(0, 11)" placeholder="e.g. 09123456789 (11 digits)" inputmode="numeric" maxlength="11" class="w-full px-4 py-2.5 bg-gray-50/50 border border-gray-200 rounded-xl text-sm font-semibold outline-none focus:border-[#C0422A] focus:bg-white transition-all">
                </div>

                <div class="col-span-1 sm:col-span-2 space-y-1">
                    <label class="text-[10px] font-bold text-gray-500 uppercase tracking-wider block">House / Building / Unit No. <span class="text-[#C0422A]">*</span></label>
                    <input type="text" x-model="editForm.houseNo" placeholder="e.g. Block 1 Lot 2 Mango St." class="w-full px-4 py-2.5 bg-gray-50/50 border border-gray-200 rounded-xl text-sm font-semibold outline-none focus:border-[#C0422A] focus:bg-white transition-all">
                </div>

                <!-- Region, Province, City, Barangay Dropdown Selector -->
                <div class="col-span-1 sm:col-span-2 relative space-y-1">
                    <label class="text-[10px] font-bold text-gray-500 uppercase tracking-wider block">Region, Province, City, Barangay <span class="text-[#C0422A]">*</span></label>
                    <div @click="toggleLocationDropdown()"
                         class="w-full px-4 py-2.5 bg-gray-50/50 border border-gray-200 rounded-xl text-xs sm:text-sm font-semibold outline-none focus:border-[#C0422A] focus:bg-white flex items-center justify-between cursor-pointer transition-all">
                        <span class="truncate" :class="getLocationSummary() ? 'text-gray-900 font-bold' : 'text-gray-400'" x-text="getLocationSummary() || 'Select Region, Province, City, Barangay'"></span>
                        <svg class="w-4 h-4 text-gray-400 transition-transform duration-200 shrink-0" :class="locationDropdownOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </div>

                    <!-- Location Dropdown Panel -->
                    <div x-show="locationDropdownOpen"
                         @click.away="locationDropdownOpen = false"
                         class="absolute left-0 right-0 z-50 mt-1 bg-white border border-gray-200 rounded-2xl shadow-2xl overflow-hidden flex flex-col max-h-87.5"
                         x-cloak>

                         <!-- Tabs -->
                         <div class="flex border-b border-gray-150 bg-gray-50 text-[11px] font-bold text-gray-500">
                             <button @click="activeTab = 'region'"
                                     type="button"
                                     :class="activeTab === 'region' ? 'text-[#C0422A] border-b-2 border-[#C0422A] bg-white' : ''"
                                     class="flex-1 py-2.5 text-center border-b border-transparent hover:bg-white transition-colors">
                                 Region
                             </button>
                             <button @click="if(selectedRegion && hasProvinces) activeTab = 'province'"
                                     type="button"
                                     :disabled="!selectedRegion || !hasProvinces"
                                     :class="activeTab === 'province' ? 'text-[#C0422A] border-b-2 border-[#C0422A] bg-white' : ''"
                                     class="flex-1 py-2.5 text-center border-b border-transparent hover:bg-white transition-colors disabled:opacity-40 disabled:cursor-not-allowed flex items-center justify-center gap-1">
                                 Province
                                 <span x-show="!selectedRegion" class="text-[10px] text-red-500">🚫</span>
                             </button>
                             <button @click="if(selectedProvince || (selectedRegion && !hasProvinces)) activeTab = 'city'"
                                     type="button"
                                     :disabled="!selectedProvince && (hasProvinces || !selectedRegion)"
                                     :class="activeTab === 'city' ? 'text-[#C0422A] border-b-2 border-[#C0422A] bg-white' : ''"
                                     class="flex-1 py-2.5 text-center border-b border-transparent hover:bg-white transition-colors disabled:opacity-40 disabled:cursor-not-allowed flex items-center justify-center gap-1">
                                 City
                                 <span x-show="!selectedProvince && hasProvinces" class="text-[10px] text-red-500">🚫</span>
                             </button>
                             <button @click="if(selectedCity) activeTab = 'barangay'"
                                     type="button"
                                     :disabled="!selectedCity"
                                     :class="activeTab === 'barangay' ? 'text-[#C0422A] border-b-2 border-[#C0422A] bg-white' : ''"
                                     class="flex-1 py-2.5 text-center border-b border-transparent hover:bg-white transition-colors disabled:opacity-40 disabled:cursor-not-allowed flex items-center justify-center gap-1">
                                 Barangay
                                 <span x-show="!selectedCity" class="text-[10px] text-red-500">🚫</span>
                             </button>
                         </div>

                         <!-- Inline Search Field -->
                         <div class="p-2 border-b border-gray-100 bg-gray-50/50">
                             <input type="text" x-model="locationSearch" :placeholder="'Search ' + activeTab + '...'"
                                    class="w-full h-8 px-3 bg-white border border-gray-200 rounded-lg text-xs outline-none focus:border-[#C0422A] transition-colors">
                         </div>

                         <!-- Scrollable List -->
                         <div class="flex-1 overflow-y-auto min-h-45 max-h-55 divide-y divide-gray-50 text-xs">
                             <!-- Loading Geo Data Spinner -->
                             <div x-show="loadingGeoData" class="flex items-center justify-center py-10 text-xs text-gray-400 gap-2">
                                 <svg class="animate-spin h-4 w-4 text-[#C0422A]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                     <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                     <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                 </svg>
                                 <span>Loading geographical data...</span>
                             </div>

                             <!-- Region List -->
                             <template x-if="activeTab === 'region' && !loadingGeoData">
                                 <div class="py-1">
                                     <template x-for="reg in filteredGeoList(regionsList)" :key="reg.code">
                                         <button type="button" @click="selectRegion(reg)"
                                              :class="selectedRegion?.code === reg.code ? 'bg-[#C0422A]/5 text-[#C0422A] font-bold' : 'text-gray-700 hover:bg-gray-50'"
                                              class="w-full text-left px-4 py-2.5 text-xs transition-colors flex items-center justify-between">
                                              <span x-text="reg.name + ' (' + reg.regionName + ')'"></span>
                                              <span x-show="selectedRegion?.code === reg.code" class="text-xs">✓</span>
                                         </button>
                                     </template>
                                 </div>
                             </template>

                             <!-- Province List -->
                             <template x-if="activeTab === 'province' && !loadingGeoData">
                                 <div class="py-1">
                                     <template x-for="prov in filteredGeoList(provincesList)" :key="prov.code">
                                         <button type="button" @click="selectProvince(prov)"
                                              :class="selectedProvince?.code === prov.code ? 'bg-[#C0422A]/5 text-[#C0422A] font-bold' : 'text-gray-700 hover:bg-gray-50'"
                                              class="w-full text-left px-4 py-2.5 text-xs transition-colors flex items-center justify-between">
                                              <span x-text="prov.name"></span>
                                              <span x-show="selectedProvince?.code === prov.code" class="text-xs">✓</span>
                                         </button>
                                     </template>
                                 </div>
                             </template>

                             <!-- City List -->
                             <template x-if="activeTab === 'city' && !loadingGeoData">
                                 <div class="py-1">
                                     <template x-for="ct in filteredGeoList(citiesList)" :key="ct.code">
                                         <button type="button" @click="selectCity(ct)"
                                              :class="selectedCity?.code === ct.code ? 'bg-[#C0422A]/5 text-[#C0422A] font-bold' : 'text-gray-700 hover:bg-gray-50'"
                                              class="w-full text-left px-4 py-2.5 text-xs transition-colors flex items-center justify-between">
                                              <span x-text="ct.name"></span>
                                              <span x-show="selectedCity?.code === ct.code" class="text-xs">✓</span>
                                         </button>
                                     </template>
                                 </div>
                             </template>

                             <!-- Barangay List -->
                             <template x-if="activeTab === 'barangay' && !loadingGeoData">
                                 <div class="py-1">
                                     <template x-for="brgy in filteredGeoList(barangaysList)" :key="brgy.code">
                                         <button type="button" @click="selectBarangay(brgy)"
                                              :class="selectedBarangay?.code === brgy.code ? 'bg-[#C0422A]/5 text-[#C0422A] font-bold' : 'text-gray-700 hover:bg-gray-50'"
                                              class="w-full text-left px-4 py-2.5 text-xs transition-colors flex items-center justify-between">
                                              <span x-text="brgy.name"></span>
                                              <span x-show="selectedBarangay?.code === brgy.code" class="text-xs">✓</span>
                                         </button>
                                     </template>
                                 </div>
                             </template>

                             <!-- Empty Geo Results -->
                             <div x-show="!loadingGeoData && filteredGeoList(getCurrentTabList()).length === 0"
                                  class="p-8 text-center text-xs text-gray-400">
                                  No items found.
                             </div>
                         </div>
                    </div>
                </div>

                <div class="col-span-1 sm:col-span-2 space-y-1">
                    <label class="text-[10px] font-bold text-gray-500 uppercase tracking-wider block">Street (Optional)</label>
                    <input type="text" x-model="editForm.street" placeholder="e.g. Mango St." class="w-full px-4 py-2.5 bg-gray-50/50 border border-gray-200 rounded-xl text-sm font-semibold outline-none focus:border-[#C0422A] focus:bg-white transition-all">
                </div>

                <div class="col-span-1 sm:col-span-2 space-y-1">
                    <label class="text-[10px] font-bold text-gray-500 uppercase tracking-wider block">Postal Code</label>
                    <input type="text" x-model="editForm.postalCode" @input="editForm.postalCode = editForm.postalCode.replace(/[^0-9]/g, '').slice(0, 4)" placeholder="e.g. 4103 (4 digits)" inputmode="numeric" maxlength="4" class="w-full px-4 py-2.5 bg-gray-50/50 border border-gray-200 rounded-xl text-sm font-semibold outline-none focus:border-[#C0422A] focus:bg-white transition-all">
                </div>
            </div>

            <div class="flex gap-3 pt-4 border-t border-gray-100">
                <button type="button" @click="showEditAddressModal = false" class="flex-1 py-3 border border-gray-200 text-xs font-bold uppercase tracking-wider text-gray-500 rounded-xl hover:bg-gray-50 transition-colors">
                    Cancel
                </button>
                <button type="button" @click="saveEditAddress()" :disabled="savingAddress" class="flex-1 py-3 bg-[#C0422A] text-white text-xs font-bold uppercase tracking-wider rounded-xl hover:bg-[#A33622] transition-colors shadow-md shadow-[#C0422A]/20 disabled:opacity-50">
                    <span x-text="savingAddress ? 'Saving Address...' : 'Save Address'"></span>
                </button>
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
                class="bg-white rounded-3xl border border-gray-100 shadow-2xl p-6 relative overflow-hidden max-w-sm w-full flex flex-col items-center justify-center"
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
                <div class="text-[9px] font-bold uppercase tracking-[0.2em] text-[#C0422A] mb-1.5 mt-1">Scan QR Code</div>
                <h3 class="font-serif text-lg font-bold text-gray-900 leading-tight mb-4" x-text="paymentMethod === 'GCash' ? 'GCash Payment QR' : 'Maya Payment QR'"></h3>
                
                <div class="w-64 h-64 bg-gray-50 rounded-2xl overflow-hidden border border-gray-100 shadow-xs flex items-center justify-center p-4">
                    <img :src="zoomImage" class="max-w-full max-h-full object-contain rounded-lg" alt="QR Code">
                </div>

                <p class="text-[10px] text-gray-400 font-medium text-center mt-4">Tap outside or press close to return.</p>
            </div>
        </div>
    </template>
</div>

<script>
function checkoutApp(initialAddress, initialAddresses, defaultPaymentMethod) {
    return {
        step: 1,
        paymentMethod: defaultPaymentMethod || 'GCash',
        address: initialAddress || {},
        addresses: initialAddresses || [],
        showAddressModal: false,
        showPolicyModal: false,
        showConfirmModal: false,
        policyAccepted: false,
        showEditAddressModal: false,
        savingAddress: false,
        addressError: '',
        addressStepError: '',
        editForm: {
            id: null,
            recipientName: '',
            phone: '',
            houseNo: '',
            street: '',
            barangay: '',
            city: '',
            province: '',
            postalCode: ''
        },
        fileName: '',
        filePreview: '',
        screenshotError: '',
        zoomImage: '',
        showZoomModal: false,
        paymentRef: '',
        refError: '',
        isRefDuplicate: false,
        refCheckTimer: null,
        aiChecking: false,
        aiVerificationResult: null,

        init() {
            this.$watch('paymentMethod', () => {
                if (this.paymentRef) {
                    this.validateRef();
                } else {
                    this.refError = '';
                }
                if (this.fileName) {
                    this.runAiReceiptVerification();
                }
            });
        },

        handleRefInput() {
            if (this.paymentRef) {
                const isValidFormat = this.validateRef();
                if (isValidFormat) {
                    // Debounce duplicate check to server
                    clearTimeout(this.refCheckTimer);
                    this.refCheckTimer = setTimeout(() => {
                        this.checkServerReference();
                    }, 400);
                }
            } else {
                this.refError = '';
                this.isRefDuplicate = false;
            }
        },

        checkServerReference() {
            const val = (this.paymentRef || '').trim();
            if (!val) return;
            const csrfToken = document.querySelector('input[name=_token]')?.value || '';

            fetch('/ai/payment-reference/check', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({ reference: val, method: this.paymentMethod })
            })
            .then(res => res.json())
            .then(data => {
                if (data.is_duplicate) {
                    this.refError = '❌ Security Alert: This payment reference number has already been used in another order.';
                    this.isRefDuplicate = true;
                } else if (!data.is_valid && data.message) {
                    this.refError = data.message;
                    this.isRefDuplicate = false;
                } else {
                    this.isRefDuplicate = false;
                    if (this.refError.includes('Security Alert') || this.refError.includes('already been used')) {
                        this.refError = '';
                    }
                }
                // Also trigger AI receipt check if image is already attached
                if (this.fileName && !this.isRefDuplicate) {
                    this.runAiReceiptVerification();
                }
            })
            .catch(() => {});
        },

        isRefValid() {
            const val = (this.paymentRef || '').trim();
            if (!val || this.isRefDuplicate || this.refError) return false;
            if (/^(\d)\1+$/.test(val)) return false;
            const isGcash = this.paymentMethod === 'GCash';
            if (isGcash) {
                return /^\d{13}$/.test(val);
            } else {
                return /^\d{12}$/.test(val);
            }
        },

        hasReceiptMismatch() {
            if (!this.fileName || !this.aiVerificationResult) return false;
            return this.aiVerificationResult.is_receipt === false 
                || this.aiVerificationResult.status === 'REJECT' 
                || (this.aiVerificationResult.status === 'REVIEW' && this.aiVerificationResult.ref_matched === false);
        },

        isReceiptVerified() {
            if (!this.fileName || !this.aiVerificationResult) return false;
            return this.aiVerificationResult.is_receipt === true 
                && this.aiVerificationResult.status === 'PASS' 
                && this.aiVerificationResult.ref_matched === true;
        },

        locationDropdownOpen: false,
        activeTab: 'region',
        regionsList: [],
        provincesList: [],
        citiesList: [],
        barangaysList: [],
        selectedRegion: null,
        selectedProvince: null,
        selectedCity: null,
        selectedBarangay: null,
        loadingGeoData: false,
        locationSearch: '',
        hasProvinces: true,

        handleFileChange(e) {
            const file = e.target.files && e.target.files[0];
            if (file) {
                if (file.size > 10 * 1024 * 1024) {
                    this.screenshotError = 'File size exceeds 10MB limit.';
                    e.target.value = '';
                    this.fileName = '';
                    this.filePreview = '';
                    this.aiVerificationResult = null;
                    return;
                }
                this.screenshotError = '';
                this.fileName = file.name;
                if (file.type.startsWith('image/')) {
                    this.filePreview = URL.createObjectURL(file);
                } else {
                    this.filePreview = '';
                }
                // Run AI receipt analysis
                this.runAiReceiptVerification();
            }
        },

        runAiReceiptVerification() {
            const fileInput = document.getElementById('paymentScreenshotInput');
            const file = fileInput && fileInput.files && fileInput.files[0];
            if (!file) return;

            const csrfToken = document.querySelector('input[name=_token]')?.value || '';
            const formData = new FormData();
            formData.append('receipt', file);
            formData.append('reference', (this.paymentRef || '').trim());
            formData.append('method', this.paymentMethod);
            formData.append('amount', '{{ (float)($grandTotal ?? 0) }}');

            this.aiChecking = true;
            this.aiVerificationResult = null;

            fetch('/ai/receipt/verify', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken
                },
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                this.aiVerificationResult = data;
                if (data.is_receipt === false) {
                    this.screenshotError = data.message || 'Attached file is not a valid receipt.';
                } else {
                    this.screenshotError = '';
                }
            })
            .catch(() => {
                this.aiVerificationResult = {
                    is_receipt: true,
                    message: '✓ Receipt screenshot attached.'
                };
            })
            .finally(() => {
                this.aiChecking = false;
            });
        },

        removeFile() {
            const input = document.getElementById('paymentScreenshotInput');
            if (input) input.value = '';
            this.fileName = '';
            this.filePreview = '';
            this.screenshotError = '';
            this.aiVerificationResult = null;
            this.aiChecking = false;
        },

        selectAddress(addr) {
            this.address = addr;
            this.showAddressModal = false;
        },
        openEditAddress(addr) {
            const target = addr || this.address || {};
            this.editForm = {
                id: target.id || null,
                recipientName: target.recipientName || '',
                phone: target.phone || '',
                houseNo: target.houseNo || '',
                street: target.street || '',
                barangay: target.barangay || '',
                city: target.city || '',
                province: target.province || '',
                postalCode: target.postalCode || ''
            };

            this.selectedRegion = target.province || target.city ? { name: target.province || 'Default Region' } : null;
            this.selectedProvince = target.province ? { name: target.province } : null;
            this.selectedCity = target.city ? { name: target.city } : null;
            this.selectedBarangay = target.barangay ? { name: target.barangay } : null;
            this.activeTab = 'region';
            this.locationSearch = '';
            this.addressError = '';
            this.showEditAddressModal = true;
        },

        async toggleLocationDropdown() {
            this.locationDropdownOpen = !this.locationDropdownOpen;
            if (this.locationDropdownOpen && this.regionsList.length === 0) {
                await this.loadRegions();
            }
        },

        async loadRegions() {
            this.loadingGeoData = true;
            try {
                const res = await fetch('https://psgc.gitlab.io/api/regions/');
                if (res.ok) {
                    this.regionsList = await res.json();
                }
            } catch(e) {
                console.error("Failed to load regions", e);
            }
            this.loadingGeoData = false;
        },

        async selectRegion(region) {
            this.selectedRegion = region;
            this.editForm.region = region.name;

            this.selectedProvince = null;
            this.editForm.province = '';
            this.selectedCity = null;
            this.editForm.city = '';
            this.selectedBarangay = null;
            this.editForm.barangay = '';
            this.locationSearch = '';

            if (region.code === '130000000') {
                this.hasProvinces = false;
                this.provincesList = [];
                this.editForm.province = 'Metro Manila';
                this.selectedProvince = { code: '130000000', name: 'Metro Manila' };
                this.activeTab = 'city';
                await this.loadNCRCities();
            } else {
                this.hasProvinces = true;
                this.activeTab = 'province';
                await this.loadProvinces(region.code);
            }
        },

        async loadNCRCities() {
            this.loadingGeoData = true;
            try {
                const res = await fetch('https://psgc.gitlab.io/api/regions/130000000/cities-municipalities/');
                if (res.ok) {
                    this.citiesList = await res.json();
                }
            } catch(e) {
                console.error("Failed to load NCR cities", e);
            }
            this.loadingGeoData = false;
        },

        async loadProvinces(regionCode) {
            this.loadingGeoData = true;
            try {
                const res = await fetch(`https://psgc.gitlab.io/api/regions/${regionCode}/provinces/`);
                if (res.ok) {
                    this.provincesList = await res.json();
                }
            } catch(e) {
                console.error("Failed to load provinces", e);
            }
            this.loadingGeoData = false;
        },

        async selectProvince(province) {
            this.selectedProvince = province;
            this.editForm.province = province.name;

            this.selectedCity = null;
            this.editForm.city = '';
            this.selectedBarangay = null;
            this.editForm.barangay = '';
            this.locationSearch = '';

            this.activeTab = 'city';
            await this.loadCities(province.code);
        },

        async loadCities(provinceCode) {
            this.loadingGeoData = true;
            try {
                const res = await fetch(`https://psgc.gitlab.io/api/provinces/${provinceCode}/cities-municipalities/`);
                if (res.ok) {
                    this.citiesList = await res.json();
                }
            } catch(e) {
                console.error("Failed to load cities", e);
            }
            this.loadingGeoData = false;
        },

        async selectCity(city) {
            this.selectedCity = city;
            this.editForm.city = city.name;

            this.selectedBarangay = null;
            this.editForm.barangay = '';
            this.locationSearch = '';

            if (!this.editForm.postalCode) {
                this.editForm.postalCode = this.autoPostalCode(city.name, this.editForm.province);
            }

            this.activeTab = 'barangay';
            await this.loadBarangays(city.code);
        },

        async loadBarangays(cityCode) {
            this.loadingGeoData = true;
            try {
                const res = await fetch(`https://psgc.gitlab.io/api/cities-municipalities/${cityCode}/barangays/`);
                if (res.ok) {
                    this.barangaysList = await res.json();
                }
            } catch(e) {
                console.error("Failed to load barangays", e);
            }
            this.loadingGeoData = false;
        },

        selectBarangay(barangay) {
            this.selectedBarangay = barangay;
            this.editForm.barangay = barangay.name;
            this.locationDropdownOpen = false;
            this.locationSearch = '';

            if (!this.editForm.postalCode) {
                this.editForm.postalCode = this.autoPostalCode(this.editForm.city, this.editForm.province);
            }
        },

        autoPostalCode(cityName, provinceName) {
            if (!cityName) return '';
            const c = cityName.toLowerCase().trim();
            const p = (provinceName || '').toLowerCase().trim();

            const postalMap = {
                'lumban': '4014',
                'santa cruz': p.includes('laguna') ? '4009' : '1003',
                'calamba': '4027',
                'los baños': '4030',
                'los banos': '4030',
                'biñan': '4024',
                'binan': '4024',
                'san pedro': '4023',
                'santa rosa': '4026',
                'san pablo': '4000',
                'pagsanjan': '4008',
                'manila': '1000',
                'quezon city': '1100',
                'makati': '1200',
                'pasig': '1600',
                'taguig': '1630',
                'imus': '4103',
                'dasariñas': '4114',
                'dasmarinas': '4114',
                'bacoor': '4102'
            };

            for (const key in postalMap) {
                if (c.includes(key)) return postalMap[key];
            }
            return '4000';
        },

        filteredGeoList(list) {
            if (!list) return [];
            if (!this.locationSearch) return list;
            const q = this.locationSearch.toLowerCase();
            return list.filter(item =>
                (item.name && item.name.toLowerCase().includes(q)) ||
                (item.regionName && item.regionName.toLowerCase().includes(q))
            );
        },

        getCurrentTabList() {
            if (this.activeTab === 'region') return this.regionsList;
            if (this.activeTab === 'province') return this.provincesList;
            if (this.activeTab === 'city') return this.citiesList;
            if (this.activeTab === 'barangay') return this.barangaysList;
            return [];
        },

        getLocationSummary() {
            if (this.selectedRegion || this.selectedProvince || this.selectedCity || this.selectedBarangay) {
                return [
                    this.selectedRegion?.name,
                    this.selectedProvince?.name,
                    this.selectedCity?.name,
                    this.selectedBarangay?.name
                ].filter(Boolean).join(', ');
            }
            if (this.editForm.city || this.editForm.province) {
                return [this.editForm.province, this.editForm.city, this.editForm.barangay].filter(Boolean).join(', ');
            }
            return '';
        },
        async saveEditAddress() {
            this.addressError = '';

            const name = (this.editForm.recipientName || '').trim();
            const phone = (this.editForm.phone || '').trim();
            const houseNo = (this.editForm.houseNo || '').trim();
            const city = (this.editForm.city || '').trim();
            const province = (this.editForm.province || '').trim();
            const postalCode = (this.editForm.postalCode || '').trim();

            if (!name || !phone || !houseNo || !city || !province) {
                this.addressError = 'Please fill in all required fields (*).';
                return;
            }

            if (/[0-9]/.test(name)) {
                this.addressError = 'Recipient full name cannot contain numbers.';
                return;
            }

            if (!/^[a-zA-Z\s\.\,\'\-]+$/.test(name)) {
                this.addressError = 'Recipient full name can only contain letters, spaces, hyphens, and periods.';
                return;
            }

            if (!/^(09|\+639)\d{9}$/.test(phone)) {
                this.addressError = 'Phone number must be a valid 11-digit mobile number starting with 09 (e.g. 09123456789).';
                return;
            }

            if (postalCode && !/^\d{4}$/.test(postalCode)) {
                this.addressError = 'Postal code must contain exactly 4 numeric digits (e.g. 4103).';
                return;
            }

            this.savingAddress = true;
            try {
                const isUpdate = !!this.editForm.id;
                const url = isUpdate ? `/api/addresses/${this.editForm.id}` : '/api/addresses';
                const method = isUpdate ? 'PUT' : 'POST';
                
                const res = await fetch(url, {
                    method: method,
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    },
                    body: JSON.stringify({
                        ...this.editForm,
                        recipientName: name,
                        phone: phone,
                        houseNo: houseNo,
                        city: city,
                        province: province,
                        postalCode: postalCode
                    })
                });

                if (!res.ok) {
                    const errData = await res.json().catch(() => ({}));
                    let msg = errData.message || 'Failed to save address.';
                    if (errData.errors) {
                        msg = Object.values(errData.errors).flat().join(' ');
                    }
                    throw new Error(msg);
                }

                const saved = await res.json();
                this.address = saved;
                if (isUpdate) {
                    const idx = this.addresses.findIndex(a => a.id === saved.id);
                    if (idx !== -1) {
                        this.addresses[idx] = saved;
                    } else {
                        this.addresses.unshift(saved);
                    }
                } else {
                    this.addresses.unshift(saved);
                }
                this.showEditAddressModal = false;
            } catch (e) {
                this.addressError = e.message || 'An error occurred while saving address.';
            } finally {
                this.savingAddress = false;
            }
        },
        validateRef() {
            const val = (this.paymentRef || '').trim();
            const isGcash = this.paymentMethod === 'GCash';
            const requiredDigits = isGcash ? 13 : 12;

            if (!val) {
                this.refError = isGcash 
                    ? 'Reference number must be exactly 13 digits.' 
                    : 'Reference number must be exactly 12 digits.';
                return false;
            }

            if (/^(\d)\1+$/.test(val)) {
                this.refError = 'Invalid reference number. Repeated digit sequences are not allowed.';
                return false;
            }

            if (!/^\d+$/.test(val) || val.length !== requiredDigits) {
                this.refError = `Reference number must be exactly ${requiredDigits} digits.`;
                return false;
            }

            this.refError = '';
            return true;
        },
        validateStep1() {
            this.addressStepError = '';
            const addr = this.address || {};
            if (!addr.recipientName || !addr.city || !addr.province) {
                this.addressStepError = !addr.recipientName
                    ? 'Please add a delivery address with a recipient name before proceeding.'
                    : 'Your saved address is incomplete. Please add a full delivery address.';
                // scroll into address section
                document.querySelector('[data-address-section]')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
                return;
            }
            // Show the Shop Policy Notice Modal before proceeding to payment
            this.showPolicyModal = true;
        },
        proceedToPaymentStep() {
            if (!this.policyAccepted) {
                return;
            }
            this.showPolicyModal = false;
            this.step = 2;
            window.scrollTo({ top: 0, behavior: 'smooth' });
        },
        requestPlaceOrder() {
            if (this.aiChecking) {
                this.screenshotError = 'Please wait while receipt scanning is in progress.';
                return;
            }
            if (!this.validateRef() || this.isRefDuplicate) {
                if (this.isRefDuplicate) {
                    this.refError = '❌ Security Alert: This payment reference number has already been used in another order.';
                }
                document.getElementById('paymentReferenceInput')?.focus();
                return;
            }
            const screenshotInput = document.getElementById('paymentScreenshotInput');
            if (!screenshotInput || !screenshotInput.files || screenshotInput.files.length === 0) {
                this.screenshotError = 'Payment receipt screenshot is required.';
                document.getElementById('paymentScreenshotInput')?.focus();
                return;
            }
            if (this.aiVerificationResult && this.aiVerificationResult.is_receipt === false) {
                this.screenshotError = this.aiVerificationResult.message || 'Attached file is not a valid receipt.';
                document.getElementById('paymentScreenshotInput')?.focus();
                return;
            }
            this.screenshotError = '';
            const form = document.getElementById('checkout-form');
            if (!form || !form.reportValidity()) return;
            this.showConfirmModal = true;
        },
        confirmPlaceOrder() {
            if (this.aiChecking) {
                this.showConfirmModal = false;
                this.screenshotError = 'Please wait while receipt scanning is in progress.';
                return;
            }
            if (!this.validateRef() || this.isRefDuplicate) {
                this.showConfirmModal = false;
                document.getElementById('paymentReferenceInput')?.focus();
                return;
            }
            if (this.aiVerificationResult && this.aiVerificationResult.is_receipt === false) {
                this.showConfirmModal = false;
                document.getElementById('paymentScreenshotInput')?.focus();
                return;
            }
            this.showConfirmModal = false;
            document.getElementById('checkout-form')?.submit();
        }
    };
}
</script>
@endsection

