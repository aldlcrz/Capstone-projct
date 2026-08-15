@extends('layouts.app')

@section('content')
@php
    $cart = session('cart', []);
    $cartItemsForJs = collect($cart)->map(function ($item, $key) {
        return [
            'key'                 => (string) $key,
            'id'                  => $item['id'] ?? '',
            'name'                => $item['name'] ?? '',
            'price'               => (float) ($item['price'] ?? 0),
            'quantity'            => (int) ($item['quantity'] ?? 1),
            'shippingFee'         => (float) ($item['shippingFee'] ?? 0),
            'is_on_sale'          => ! empty($item['is_on_sale']),
            'discount_percentage' => (float) ($item['discount_percentage'] ?? 0),
            'original_price'      => (float) ($item['original_price'] ?? $item['price'] ?? 0),
            'size'                => $item['size'] ?? '',
            'category_name'       => $item['category_name'] ?? '',
            'variation'           => $item['variation'] ?? '',
            'sellerId'            => $item['sellerId'] ?? '',
            'shop_name'           => $item['shop_name'] ?? 'Lumban Heritage Shop',
        ];
    })->values();

    $groupedCart = [];
    foreach ($cart as $cartKey => $item) {
        $shop = $item['shop_name'] ?? 'Lumban Heritage Shop';
        $groupedCart[$shop][$cartKey] = $item;
    }
@endphp

<div id="cart-root"
     data-cart-items="{{ json_encode($cartItemsForJs) }}"
     class="max-w-300 mx-auto px-4 pt-0 pb-6 sm:pb-8"
     x-data="cartApp()"
     x-init="init()">

    {{-- Back --}}
    <a href="/" class="hidden sm:inline-flex items-center gap-2 text-[10px] font-bold text-gray-400 uppercase tracking-widest hover:text-black transition-colors mb-2">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
        </svg>
        Back to catalogue
    </a>

    {{-- When Cart has items --}}
    <div class="flex flex-col lg:flex-row gap-8 items-start" x-show="items.length > 0">

        {{-- ===== Left: Cart Items ===== --}}
        <div class="flex-1 space-y-4 min-w-0 w-full">

            {{-- Header + Select All --}}
            <div class="flex items-center justify-between mb-4 sm:mb-6 flex-wrap gap-3">
                <div>
                    <div class="flex items-center gap-2 mb-0.5">
                        <div class="w-5 h-[1.5px] bg-[#C0422A]"></div>
                        <span class="text-[10px] font-bold uppercase tracking-widest text-[#C0422A]">Shopping</span>
                    </div>
                    <h1 class="font-serif text-2xl sm:text-3xl font-bold text-black">Your Cart</h1>
                </div>

                <div class="flex items-center gap-4 flex-wrap">
                    <label class="flex items-center gap-2 cursor-pointer group select-none">
                        <input type="checkbox"
                               id="select-all"
                               x-model="allSelected"
                               @change="toggleAll()"
                               class="w-4 h-4 rounded border border-gray-300 text-[#C0422A] accent-[#C0422A] cursor-pointer shrink-0">
                        <span class="text-xs font-bold text-gray-600 group-hover:text-black transition-colors uppercase tracking-widest">
                            Select All (<span x-text="selected.length"></span>/<span x-text="items.length"></span>)
                        </span>
                    </label>

                    {{-- Delete Selected / Delete All Button --}}
                    <button type="button"
                            x-show="selected.length > 0"
                            @click="promptDeleteSelected()"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-red-200 bg-red-50 text-red-600 hover:bg-red-600 hover:text-white transition-all text-xs font-bold uppercase tracking-wider cursor-pointer shadow-xs">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                        <span x-text="selected.length === items.length ? 'Delete All' : 'Delete Selected (' + selected.length + ')'"></span>
                    </button>
                </div>
            </div>

            {{-- Cart Items Grouped by Shop --}}
            <div class="space-y-4 max-h-[calc(100vh-200px)] lg:max-h-[calc(100vh-220px)] overflow-y-auto pr-1 sm:pr-2 custom-scrollbar">
                @forelse($groupedCart as $shopName => $shopItems)
                    @php
                        $firstItem = reset($shopItems);
                        $sellerId = $firstItem['sellerId'] ?? null;
                    @endphp
                    <div class="bg-white rounded-2xl border border-gray-100 shadow-xs overflow-hidden mb-3 transition-all"
                         x-show="items.some(i => (i.shop_name || 'Lumban Heritage Shop') === '{{ addslashes($shopName) }}')">

                        {{-- Shop Header Bar --}}
                        <div class="bg-white px-3 sm:px-4 py-2.5 border-b border-gray-100 flex items-center justify-between gap-3">
                            <div class="flex items-center gap-2 min-w-0">
                                <label class="flex items-center cursor-pointer shrink-0">
                                    <input type="checkbox"
                                           @change="toggleShop('{{ addslashes($shopName) }}', $event.target.checked)"
                                           :checked="isShopSelected('{{ addslashes($shopName) }}')"
                                           class="w-4 h-4 rounded border border-gray-300 text-[#C0422A] accent-[#C0422A] cursor-pointer shrink-0">
                                </label>

                                <a href="{{ $sellerId ? '/shops/' . $sellerId : '#' }}" class="flex items-center gap-1.5 font-bold text-xs sm:text-sm text-gray-900 hover:text-[#C0422A] transition-colors truncate">
                                    <span class="inline-block px-1.5 py-0.5 bg-[#C0422A] text-white text-[8px] font-black rounded uppercase">Artisan</span>
                                    <span class="truncate">{{ $shopName }}</span>
                                    <svg class="w-3.5 h-3.5 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                    </svg>
                                </a>
                            </div>

                            <div class="flex items-center gap-2 shrink-0">
                                <button type="button"
                                        @click="checkoutShopOnly('{{ addslashes($shopName) }}')"
                                        class="inline-flex items-center gap-1 text-[10px] font-bold text-[#C0422A] bg-[#C0422A]/10 hover:bg-[#C0422A] hover:text-white px-2 py-1 rounded-md transition-all cursor-pointer">
                                    <span>Checkout Shop</span>
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                                </button>
                            </div>
                        </div>

                        {{-- Product Items --}}
                        <div class="divide-y divide-gray-100 p-2 sm:p-3 space-y-2">
                            @foreach($shopItems as $cartKey => $item)
                                @php
                                    $itemKey = (string) $cartKey;
                                    $img = $item['image'] ?? '';
                                    $imgSrc = asset('uploads/products/default.jpg');
                                    if ($img) {
                                        $cleanImg = ltrim($img, '/');
                                        if (str_starts_with($img, 'http') || str_starts_with($img, '/')) {
                                            $imgSrc = $img;
                                        } elseif (file_exists(storage_path('app/public/' . $cleanImg))) {
                                            $imgSrc = asset('storage/' . $cleanImg);
                                        } elseif (file_exists(public_path('uploads/' . $cleanImg))) {
                                            $imgSrc = asset('uploads/' . $cleanImg);
                                        } elseif (file_exists(public_path('uploads/products/' . $cleanImg))) {
                                            $imgSrc = asset('uploads/products/' . $cleanImg);
                                        }
                                    }
                                @endphp
                                <div class="p-2.5 sm:p-3 rounded-xl border transition-all duration-200"
                                     x-show="items.some(i => String(i.key) === '{{ addslashes($itemKey) }}')"
                                     x-transition
                                     :class="isSelected('{{ addslashes($itemKey) }}')
                                         ? 'border-[#C0422A]/40 shadow-[#C0422A]/5 shadow-2xs bg-[#FDF9F4]'
                                         : 'border-gray-100 bg-white'">

                                    {{-- Main Row: Checkbox + Image + Details --}}
                                    <div class="flex gap-3 items-start">
                                        {{-- Checkbox --}}
                                        <div class="pt-1.5 shrink-0">
                                            <label class="block cursor-pointer">
                                                <input type="checkbox"
                                                       value="{{ $itemKey }}"
                                                       x-model="selected"
                                                       @change="syncSelectAll()"
                                                       class="w-4 h-4 rounded border border-gray-300 text-[#C0422A] accent-[#C0422A] cursor-pointer shrink-0">
                                            </label>
                                        </div>

                                        {{-- Product Image (Square 80px) --}}
                                        <a href="/products/{{ $item['id'] ?? '#' }}" class="shrink-0">
                                            <img src="{{ $imgSrc }}" class="w-20 h-20 sm:w-24 sm:h-24 object-cover rounded-lg bg-gray-50 border border-gray-100">
                                        </a>

                                        {{-- Product Info Container --}}
                                        <div class="flex-1 min-w-0 flex flex-col justify-between self-stretch">
                                            <div>
                                                {{-- Title with Category Tag --}}
                                                <div class="flex items-start gap-1">
                                                    @if(!empty($item['category_name']))
                                                        <span class="inline-block text-[8px] sm:text-[9px] font-black text-white bg-[#C0422A] uppercase tracking-wider px-1.5 py-0.5 rounded shrink-0 mt-0.5">{{ $item['category_name'] }}</span>
                                                    @endif
                                                    <a href="/products/{{ $item['id'] ?? '#' }}"
                                                       class="font-bold text-gray-900 hover:text-[#C0422A] transition-colors block text-xs sm:text-sm leading-snug line-clamp-2">
                                                        {{ $item['name'] }}
                                                    </a>
                                                </div>

                                                {{-- Variant / Size Badge --}}
                                                <div class="mt-1">
                                                    <span class="inline-flex items-center gap-1 text-[10px] font-semibold text-gray-500 bg-gray-100 px-1.5 py-0.5 rounded border border-gray-200/60">
                                                        @if(!empty($item['size']))
                                                            <span>Size: <strong class="text-gray-900">{{ $item['size'] }}</strong></span>
                                                        @else
                                                            <span>Standard</span>
                                                        @endif
                                                        @if(!empty($item['variation']))
                                                            @php
                                                                $variationLabel = \App\Support\VariationFormatter::label(
                                                                    $item['variation'],
                                                                    \App\Models\Product::find($item['id'] ?? null)?->image
                                                                ) ?? $item['variation'];
                                                            @endphp
                                                            <span>• Var: <strong class="text-gray-900">{{ $variationLabel }}</strong></span>
                                                        @endif
                                                    </span>
                                                </div>
                                            </div>

                                            {{-- Bottom Price & Stepper Row --}}
                                            <div class="flex items-end justify-between gap-2 mt-2 pt-1">
                                                {{-- Price Column --}}
                                                <div class="flex items-baseline gap-1.5 flex-wrap">
                                                    @if(!empty($item['is_on_sale']) && ($item['discount_percentage'] ?? 0) > 0)
                                                        <span class="text-[#C0422A] font-black text-sm sm:text-base">₱{{ number_format($item['price']) }}</span>
                                                        <span class="text-[10px] text-gray-400 line-through">₱{{ number_format($item['original_price'] ?? $item['price']) }}</span>
                                                    @else
                                                        <span class="text-[#C0422A] font-black text-sm sm:text-base">₱{{ number_format($item['price']) }}</span>
                                                    @endif
                                                </div>

                                                {{-- Stepper & Delete Action --}}
                                                <div class="flex items-center gap-2 shrink-0">
                                                    {{-- Quantity Stepper --}}
                                                    <div class="flex items-center border border-gray-200 rounded-md overflow-hidden h-7 bg-white shadow-2xs">
                                                        <button type="button" @click="updateQty('{{ addslashes($itemKey) }}', (items.find(i => String(i.key) === '{{ addslashes($itemKey) }}')?.quantity || {{ $item['quantity'] }}) - 1)" class="w-6 h-7 flex items-center justify-center text-gray-400 hover:text-black hover:bg-gray-100 font-bold text-xs transition-colors cursor-pointer">−</button>
                                                        <span class="w-6 text-center text-xs font-bold text-gray-900 border-x border-gray-200" x-text="items.find(i => String(i.key) === '{{ addslashes($itemKey) }}')?.quantity || {{ $item['quantity'] }}"></span>
                                                        <button type="button" @click="updateQty('{{ addslashes($itemKey) }}', (items.find(i => String(i.key) === '{{ addslashes($itemKey) }}')?.quantity || {{ $item['quantity'] }}) + 1)" class="w-6 h-7 flex items-center justify-center text-gray-400 hover:text-black hover:bg-gray-100 font-bold text-xs transition-colors cursor-pointer">+</button>
                                                    </div>

                                                    {{-- Trash Icon Button --}}
                                                    <button type="button" @click="promptRemoveItem('{{ addslashes($itemKey) }}')" class="text-gray-300 hover:text-red-500 transition-colors p-1 cursor-pointer" title="Remove item">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @empty
                @endforelse
            </div>
        </div>

        {{-- ===== Right: Dynamic Order Summary (Desktop Sidebar) ===== --}}
        <div class="lg:w-96 w-full hidden lg:block shrink-0 self-stretch">
            <div class="bg-white p-8 rounded-3xl border border-gray-100 shadow-sm sticky top-24">
                <h2 class="text-xl font-bold text-black mb-1">Order Summary</h2>
                <p class="text-[10px] text-gray-400 uppercase tracking-widest font-bold mb-6">
                    <span x-text="selected.length"></span> of <span x-text="items.length"></span> item(s) selected
                </p>

                {{-- Empty selection notice --}}
                <div x-show="selected.length === 0"
                     class="py-6 text-center bg-gray-50 rounded-2xl border border-dashed border-gray-200 mb-6">
                    <svg class="w-8 h-8 text-gray-200 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 012-2h2a2 2 0 012-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                    <p class="text-xs text-gray-400 font-bold">Select items to checkout</p>
                </div>

                <div x-show="selected.length > 0" class="space-y-4">
                    <div class="flex justify-between text-gray-500">
                        <span class="text-sm">Subtotal (<span x-text="selected.length"></span> item<span x-show="selected.length !== 1">s</span>)</span>
                        <span class="font-bold text-black">₱<span x-text="subtotal.toLocaleString('en-PH', {minimumFractionDigits:0,maximumFractionDigits:0})"></span></span>
                    </div>
                    <div class="flex justify-between text-gray-500">
                        <span class="text-sm">Shipping</span>
                        <span x-show="shipping > 0" class="text-gray-900 font-bold text-sm">₱<span x-text="shipping.toLocaleString('en-PH', {minimumFractionDigits:2,maximumFractionDigits:2})"></span></span>
                        <span x-show="shipping === 0" class="text-green-600 font-bold text-sm">Free</span>
                    </div>
                    <div class="pt-4 border-t border-gray-100 flex justify-between items-center">
                        <span class="text-lg font-bold text-black">Total</span>
                        <span class="text-2xl font-black text-[#C0422A]">₱<span x-text="(subtotal + shipping).toLocaleString('en-PH', {minimumFractionDigits:0,maximumFractionDigits:0})"></span></span>
                    </div>
                </div>

                {{-- Checkout form - posts selected keys --}}
                <form action="/checkout/selected" method="POST" class="mt-8" x-ref="checkoutForm">
                    @csrf
                    <template x-for="key in selected" :key="key">
                        <input type="hidden" name="selected_keys[]" :value="key">
                    </template>
                    <button type="submit"
                            :disabled="selected.length === 0"
                            :class="selected.length === 0
                                ? 'opacity-40 cursor-not-allowed bg-gray-400'
                                : 'bg-black hover:bg-[#C0422A] shadow-xl cursor-pointer'"
                            class="block w-full text-white text-center py-4 rounded-xl text-xs font-bold uppercase tracking-widest transition-all">
                        Checkout Now
                        <span x-show="selected.length > 0"
                              class="ml-1 opacity-70"
                              x-text="'(' + selected.length + ' item' + (selected.length !== 1 ? 's' : '') + ')'">
                        </span>
                    </button>
                </form>

                <a href="/" class="block w-full text-center py-3 text-xs font-bold text-gray-400 uppercase tracking-widest hover:text-black transition-colors mt-2">
                    Continue Shopping
                </a>
            </div>
        </div>

        {{-- ===== Mobile Sticky Checkout Bar ===== --}}
        <div x-show="items.length > 0"
             x-cloak
             class="lg:hidden fixed bottom-16 left-0 right-0 z-40 bg-white border-t border-gray-200 shadow-[0_-4px_16px_rgba(0,0,0,0.08)] px-3 py-2.5"
             x-data="{ showMobileBreakdown: false }">

            {{-- Price breakdown popup --}}
            <div x-show="showMobileBreakdown" 
                 x-cloak
                 x-transition
                 class="mb-2 p-3 bg-gray-50 rounded-xl border border-gray-200 text-xs space-y-1.5">
                <div class="flex justify-between text-gray-600">
                    <span>Subtotal (<span x-text="selected.length"></span> items)</span>
                    <span class="font-bold text-gray-900">₱<span x-text="subtotal.toLocaleString('en-PH', {minimumFractionDigits:0,maximumFractionDigits:0})"></span></span>
                </div>
                <div class="flex justify-between text-gray-600">
                    <span>Shipping Fee</span>
                    <span x-show="shipping > 0" class="font-bold text-gray-900">₱<span x-text="shipping.toLocaleString('en-PH', {minimumFractionDigits:2,maximumFractionDigits:2})"></span></span>
                    <span x-show="shipping === 0" class="font-bold text-green-600">Free</span>
                </div>
            </div>

            <div class="flex items-center justify-between gap-2">
                {{-- Left: Select All Checkbox --}}
                <label class="flex items-center gap-1.5 cursor-pointer select-none shrink-0">
                    <input type="checkbox"
                           x-model="allSelected"
                           @change="toggleAll()"
                           class="w-4 h-4 rounded border border-gray-300 text-[#C0422A] accent-[#C0422A] cursor-pointer shrink-0">
                    <span class="text-xs font-bold text-gray-700">All</span>
                </label>

                {{-- Middle: Subtotal & Expand --}}
                <div class="flex-1 text-right min-w-0 pr-1">
                    <button type="button" @click="showMobileBreakdown = !showMobileBreakdown" class="inline-flex items-center gap-1 text-[11px] font-bold text-gray-700 hover:text-black cursor-pointer">
                        <span>Subtotal:</span>
                        <span class="text-sm font-black text-[#C0422A]">₱<span x-text="(subtotal + shipping).toLocaleString('en-PH', {minimumFractionDigits:0,maximumFractionDigits:0})"></span></span>
                        <svg class="w-3.5 h-3.5 transition-transform" :class="showMobileBreakdown ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div class="text-[9px] text-gray-400">
                        Shipping: <span class="font-semibold text-gray-600" x-text="shipping > 0 ? '₱' + shipping.toFixed(2) : 'Free'"></span>
                    </div>
                </div>

                {{-- Right: Checkout Button --}}
                <button type="button"
                        @click="$refs.checkoutForm.submit()"
                        :disabled="selected.length === 0"
                        :class="selected.length === 0
                            ? 'opacity-40 cursor-not-allowed bg-gray-400'
                            : 'bg-[#C0422A] hover:bg-black active:scale-95 shadow-md cursor-pointer'"
                        class="px-5 py-2.5 text-white text-xs font-bold rounded-md transition-all shrink-0">
                    Check Out <span x-show="selected.length > 0" x-text="'(' + selected.length + ')'"></span>
                </button>
            </div>
        </div>

    </div>

    {{-- Empty Cart view --}}
    <div x-show="items.length === 0" style="display: none;" class="text-center py-24 bg-white rounded-3xl border border-dashed border-gray-200">
        <div class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-4 border border-gray-100">
            <svg class="w-8 h-8 text-gray-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
        </div>
        <h3 class="text-sm font-bold text-black uppercase tracking-widest mb-1">Your Cart is Empty</h3>
        <p class="text-xs text-gray-400 mb-6">Discover handcrafted Barong Tagalog pieces from Lumban artisans.</p>
        <a href="/" class="px-8 py-3 bg-black text-white text-xs font-bold uppercase tracking-widest rounded-full hover:bg-gray-800 transition-all">Explore Collection</a>
    </div>

    {{-- Custom Confirmation Modal --}}
    <div x-show="showDeleteModal"
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4"
         style="display: none;"
         @keydown.escape.window="showDeleteModal = false">
        
        <!-- Backdrop -->
        <div @click="showDeleteModal = false" class="absolute inset-0 bg-black/60 backdrop-blur-xs"></div>

        <div class="relative z-10 w-full max-w-sm bg-white rounded-3xl p-6 shadow-2xl border border-gray-100 text-center space-y-4"
             @click.stop
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95">
            
            {{-- Warning Trash Icon --}}
            <div class="w-12 h-12 rounded-full bg-red-50 text-red-500 border border-red-100 flex items-center justify-center mx-auto shadow-xs">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
            </div>

            <div>
                <h3 class="text-base font-black text-gray-900" x-text="deleteModalTitle"></h3>
                <p class="text-xs text-gray-500 font-medium mt-1 leading-relaxed" x-text="deleteModalMessage"></p>
            </div>

            <div class="flex items-center gap-3 pt-2">
                <button type="button"
                        @click="showDeleteModal = false"
                        class="flex-1 py-2.5 px-4 rounded-xl border border-gray-200 text-gray-700 text-xs font-bold uppercase tracking-wider hover:bg-gray-50 transition-all cursor-pointer">
                    Cancel
                </button>
                <button type="button"
                        @click="confirmDelete()"
                        class="flex-1 py-2.5 px-4 rounded-xl bg-red-600 hover:bg-red-700 text-white text-xs font-bold uppercase tracking-wider shadow-lg shadow-red-600/20 active:scale-95 transition-all cursor-pointer">
                    Delete
                </button>
            </div>
        </div>
    </div>

</div>

<script>
function cartApp() {
    return {
        items: JSON.parse(document.getElementById('cart-root')?.dataset?.cartItems || '[]'),
        selected: [],
        allSelected: false,
        showDeleteModal: false,
        deleteModalTitle: '',
        deleteModalMessage: '',
        deleteAction: null,

        init() {
            // Pre-select all items on load
            this.selected = this.items.map(i => String(i.key));
            this.syncSelectAll();
        },

        isSelected(key) {
            return this.selected.map(String).includes(String(key));
        },

        isShopSelected(shopName) {
            const shopItems = this.items.filter(i => (i.shop_name || 'Lumban Heritage Shop') === shopName);
            return shopItems.length > 0 && shopItems.every(i => this.selected.map(String).includes(String(i.key)));
        },

        isShopIndeterminate(shopName) {
            const shopItems = this.items.filter(i => (i.shop_name || 'Lumban Heritage Shop') === shopName);
            const selectedCount = shopItems.filter(i => this.selected.map(String).includes(String(i.key))).length;
            return selectedCount > 0 && selectedCount < shopItems.length;
        },

        toggleShop(shopName, isChecked) {
            const shopKeys = this.items
                .filter(i => (i.shop_name || 'Lumban Heritage Shop') === shopName)
                .map(i => String(i.key));

            if (isChecked) {
                this.selected = Array.from(new Set([...this.selected, ...shopKeys]));
            } else {
                this.selected = this.selected.filter(k => !shopKeys.includes(String(k)));
            }
            this.syncSelectAll();
        },

        checkoutShopOnly(shopName) {
            const shopKeys = this.items
                .filter(i => (i.shop_name || 'Lumban Heritage Shop') === shopName)
                .map(i => String(i.key));
            if (shopKeys.length === 0) return;
            this.selected = shopKeys;
            this.syncSelectAll();
            this.$nextTick(() => {
                if (this.$refs.checkoutForm) {
                    this.$refs.checkoutForm.submit();
                }
            });
        },

        toggleAll() {
            if (this.allSelected) {
                this.selected = this.items.map(i => String(i.key));
            } else {
                this.selected = [];
            }
        },

        syncSelectAll() {
            this.allSelected = this.selected.length === this.items.length && this.items.length > 0;
        },

        get subtotal() {
            return this.items
                .filter(i => this.selected.map(String).includes(String(i.key)))
                .reduce((sum, i) => sum + i.price * i.quantity, 0);
        },

        get shipping() {
            const fees = this.items
                .filter(i => this.selected.map(String).includes(String(i.key)))
                .map(i => i.shippingFee);
            return fees.length > 0 ? Math.max(...fees) : 0;
        },

        async updateQty(key, newQty) {
            if (newQty < 1) return;
            const item = this.items.find(i => String(i.key) === String(key));
            if (!item) return;
            const originalQty = item.quantity;
            item.quantity = newQty;

            try {
                const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                const response = await fetch('/cart/update', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrf
                    },
                    body: JSON.stringify({ key: key, quantity: newQty })
                });
                if (response.ok) {
                    const data = await response.json();
                    if (data.success && data.cart && data.cart[key]) {
                        item.quantity = data.cart[key].quantity;
                    }
                    window.dispatchEvent(new CustomEvent('cart-updated', { detail: data }));
                } else {
                    item.quantity = originalQty;
                }
            } catch(e) {
                item.quantity = originalQty;
            }
        },

        promptDeleteSelected() {
            if (this.selected.length === 0) return;
            const isAll = this.selected.length === this.items.length;
            this.deleteModalTitle = isAll ? 'Delete All Items' : 'Delete Selected Items';
            this.deleteModalMessage = isAll 
                ? 'Are you sure you want to delete all items from your cart?' 
                : 'Are you sure you want to delete the selected (' + this.selected.length + ') item(s) from your cart?';
            this.deleteAction = () => this.executeRemoveSelected();
            this.showDeleteModal = true;
        },

        promptRemoveItem(key) {
            const item = this.items.find(i => String(i.key) === String(key));
            const itemName = item ? item.name : 'this item';
            this.deleteModalTitle = 'Remove Item';
            this.deleteModalMessage = 'Are you sure you want to remove "' + itemName + '" from your cart?';
            this.deleteAction = () => this.executeRemoveItem(key);
            this.showDeleteModal = true;
        },

        async confirmDelete() {
            this.showDeleteModal = false;
            if (typeof this.deleteAction === 'function') {
                await this.deleteAction();
            }
        },

        async executeRemoveItem(key) {
            try {
                const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                const response = await fetch('/cart/remove/' + encodeURIComponent(key), {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrf
                    }
                });
                if (response.ok) {
                    const data = await response.json();
                    if (data.success) {
                        this.items = this.items.filter(i => String(i.key) !== String(key));
                        this.selected = this.selected.filter(k => String(k) !== String(key));
                        this.syncSelectAll();
                        window.dispatchEvent(new CustomEvent('cart-updated', { detail: data }));
                        if (window.Alpine && Alpine.store('toast')) {
                            Alpine.store('toast').trigger('Item removed from cart.', 'info');
                        }
                    }
                }
            } catch(e) {
                console.error(e);
            }
        },

        async executeRemoveSelected() {
            if (this.selected.length === 0) return;
            try {
                const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                const response = await fetch('/cart/remove-selected', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrf
                    },
                    body: JSON.stringify({ keys: this.selected })
                });
                if (response.ok) {
                    const data = await response.json();
                    if (data.success) {
                        const removedKeys = [...this.selected].map(String);
                        this.items = this.items.filter(i => !removedKeys.includes(String(i.key)));
                        this.selected = [];
                        this.syncSelectAll();
                        window.dispatchEvent(new CustomEvent('cart-updated', { detail: data }));
                        if (window.Alpine && Alpine.store('toast')) {
                            Alpine.store('toast').trigger('Selected items removed from cart.', 'info');
                        }
                    }
                }
            } catch(e) {
                console.error(e);
            }
        },
    };
}
</script>
@endsection
