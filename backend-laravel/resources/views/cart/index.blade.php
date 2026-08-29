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
     style="min-height:calc(100vh - 80px);background-color:#FAF8F5;padding:24px 16px 48px 16px;"
     x-data="cartApp()"
     x-init="init()">

    <div style="max-width:1160px;margin:0 auto;">

        {{-- Top Header with Heraldic Laurel Wreath --}}
        <div style="background-color:#FDFBF7;border:1px solid #EAE2D2;border-radius:24px;box-shadow:0 10px 30px rgba(0,0,0,0.04);padding:24px 28px;margin-bottom:24px;">
            <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:16px;">
                <div style="display:flex;align-items:center;gap:14px;">
                    <!-- Heraldic Laurel Wreath + Star Emblem -->
                    <div style="width:48px;height:48px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <svg width="46" height="46" viewBox="0 0 48 48" fill="none">
                            <circle cx="24" cy="23" r="10.5" stroke="#C49520" stroke-width="1" stroke-dasharray="2 1.5"/>
                            <circle cx="24" cy="23" r="8.5" stroke="#C49520" stroke-width="0.8"/>
                            <path d="M24 17.5l1.6 3.4 3.7.5-2.7 2.6.6 3.7-3.2-1.7-3.2 1.7.6-3.7-2.7-2.6 3.7-.5L24 17.5z" fill="#C49520"/>
                            <path d="M15 32.5c-4-3.5-6-8.5-6-14 0-3.5 1-6.5 2.5-9" stroke="#C49520" stroke-width="1.3" stroke-linecap="round"/>
                            <path d="M10 12c1.8 1.2 3.5 2.8 4 4.5M8 17.5c2 .6 3.8 1.8 4.8 3.5M8 23.5c2 0 3.8.6 5 2M9.5 29.5c2-.8 3.8-.8 5.2 0M12.5 34c1.8-1.2 3.6-1.5 5-.8" stroke="#C49520" stroke-width="1.2" stroke-linecap="round"/>
                            <path d="M33 32.5c4-3.5 6-8.5 6-14 0-3.5-1-6.5-2.5-9" stroke="#C49520" stroke-width="1.3" stroke-linecap="round"/>
                            <path d="M38 12c-1.8 1.2-3.5 2.8-4 4.5M40 17.5c-2 .6-3.8 1.8-4.8 3.5M40 23.5c-2 0-3.8.6-5 2M38.5 29.5c-2-.8-3.8-.8-5.2 0M35.5 34c-1.8-1.2-3.6-1.5-5-.8" stroke="#C49520" stroke-width="1.2" stroke-linecap="round"/>
                            <path d="M19 36c3 1.2 7 1.2 10 0" stroke="#C49520" stroke-width="1.3" stroke-linecap="round"/>
                        </svg>
                    </div>
                    <div>
                        <h1 style="font-family:ui-serif,Georgia,Cambria,serif;font-size:22px;font-weight:700;color:#1E1915;letter-spacing:-0.01em;line-height:1.2;margin:0;white-space:nowrap;">
                            My Shopping Cart
                        </h1>
                    </div>
                </div>

                {{-- Center: Cart Item Search Bar --}}
                <div class="flex-1 max-w-md mx-auto w-full sm:w-auto px-0 sm:px-4">
                    <div class="relative w-full">
                        <input type="text"
                               x-model="searchQuery"
                               placeholder="Search added items in cart..."
                               style="background-color:#FFFFFF;border:1px solid #ECE3D2;border-radius:24px;box-shadow:0 2px 6px rgba(0,0,0,0.03);outline:none;font-size:12px;font-weight:600;color:#1E1915;width:100%;height:38px;padding-left:36px;padding-right:32px;"
                               class="transition-all focus:border-[#C49520]">
                        <svg style="position:absolute;left:12px;top:50%;transform:translateY(-50%);width:15px;height:15px;color:#A8A29E;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <button type="button" 
                                x-show="searchQuery.length > 0"
                                @click="searchQuery = ''"
                                style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;color:#A8A29E;cursor:pointer;padding:2px;"
                                class="hover:text-black">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                </div>

                {{-- Luxury Gold Heritage Pill Button (Hidden on mobile screens, visible on sm and up) --}}
                <a href="/" 
                   style="background-color:#FAF5EA;border:1px solid #E6D8BA;border-radius:24px;padding:8px 16px;color:#8C6212;font-size:11px;font-weight:700;letter-spacing:0.06em;text-transform:uppercase;align-items:center;gap:7px;transition:all 0.2s ease-in-out;box-shadow:0 1px 3px rgba(0,0,0,0.02);"
                   onmouseover="this.style.backgroundColor='#1E1915';this.style.borderColor='#1E1915';this.style.color='#DFC97A';this.querySelector('svg').style.transform='translateX(-2px)';"
                   onmouseout="this.style.backgroundColor='#FAF5EA';this.style.borderColor='#E6D8BA';this.style.color='#8C6212';this.querySelector('svg').style.transform='translateX(0)';"
                   class="hidden sm:inline-flex shrink-0 group">
                    <svg style="width:14px;height:14px;transition:transform 0.2s ease;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    <span>Back to Catalogue</span>
                </a>
            </div>

            {{-- Star Divider --}}
            <div style="position:relative;margin:16px 0 0 0;display:flex;align-items:center;justify-content:center;">
                <div style="width:100%;border-top:1px solid #EAE1D0;"></div>
                <span style="position:absolute;background-color:#FDFBF7;padding:0 12px;color:#C49520;font-size:11px;">✦</span>
            </div>
        </div>

        {{-- Mobile Chat Widget Position Elevation Style --}}
        <style>
        @media (max-width: 1023px) {
            .lumbarong-chat-wrapper {
                bottom: calc(144px + env(safe-area-inset-bottom, 0px)) !important;
                right: 16px !important;
            }
            .lumbarong-chat-window {
                bottom: calc(208px + env(safe-area-inset-bottom, 0px)) !important;
            }
        }
        </style>

        {{-- When Cart has items --}}
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 lg:gap-8 items-start" x-show="items.length > 0">

            {{-- ===== Left: Cart Items (8 cols) ===== --}}
            <div class="lg:col-span-7 xl:col-span-8 space-y-4 min-w-0">

                {{-- Minimalist Select All & Actions Bar --}}
                <div style="background-color:#FDFBF7;border:1px solid #EAE2D2;border-radius:18px;box-shadow:0 4px 14px rgba(0,0,0,0.03);" class="px-4 py-3 flex items-center justify-between">
                    <label class="flex items-center gap-2.5 cursor-pointer select-none group">
                        <input type="checkbox"
                               id="select-all"
                               x-model="allSelected"
                               @change="toggleAll()"
                               class="w-4 h-4 rounded border-stone-300 text-[#1E1915] accent-[#1E1915] cursor-pointer shrink-0">
                        <span class="text-xs font-bold text-[#1E1915] uppercase tracking-wider">
                            Select All (<span x-text="selected.length"></span>/<span x-text="items.length"></span>)
                        </span>
                    </label>

                    {{-- Delete Selected Button --}}
                    <button type="button"
                            x-show="selected.length > 0"
                            @click="promptDeleteSelected()"
                            class="inline-flex items-center gap-1.5 text-xs font-bold text-red-600 hover:text-red-700 uppercase tracking-wider px-2.5 py-1 rounded-lg hover:bg-red-50 transition-colors cursor-pointer">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                        <span x-text="selected.length === items.length ? 'Delete All' : 'Delete Selected (' + selected.length + ')'"></span>
                    </button>
                </div>

                {{-- Search No Results View --}}
                <div x-show="searchQuery.trim() && !hasMatchingItems" style="display:none;" class="p-8 text-center bg-[#FDFBF7] border border-[#EAE2D2] rounded-2xl shadow-xs">
                    <p class="text-xs font-bold text-[#78716C]">No items in your cart match "<span x-text="searchQuery"></span>"</p>
                    <button type="button" @click="searchQuery = ''" class="mt-2 text-xs font-bold text-[#8C6212] hover:underline cursor-pointer">Clear Search</button>
                </div>

                {{-- Cart Items Grouped by Shop --}}
                <div class="space-y-4">
                    @forelse($groupedCart as $shopName => $shopItems)
                        @php
                            $firstItem = reset($shopItems);
                            $sellerId = $firstItem['sellerId'] ?? null;
                        @endphp
                        <div style="background-color:#FDFBF7;border:1px solid #EAE2D2;border-radius:24px;box-shadow:0 6px 20px rgba(0,0,0,0.03);overflow:hidden;"
                             class="transition-all duration-200"
                             x-show="shopMatchesSearch('{{ addslashes($shopName) }}')">

                            {{-- Clean Shop Header --}}
                            <div style="background-color:#FAF5EA;border-bottom:1px solid #EAE2D2;" class="px-4 py-3.5 flex items-center justify-between gap-3">
                                <div class="flex items-center gap-2.5 min-w-0">
                                    <label class="flex items-center cursor-pointer shrink-0">
                                        <input type="checkbox"
                                               @change="toggleShop('{{ addslashes($shopName) }}', $event.target.checked)"
                                               :checked="isShopSelected('{{ addslashes($shopName) }}')"
                                               class="w-4 h-4 rounded border-stone-300 text-[#1E1915] accent-[#1E1915] cursor-pointer shrink-0">
                                    </label>

                                    <a href="{{ $sellerId ? '/shops/' . $sellerId : '#' }}" class="flex items-center gap-2 group truncate">
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded bg-[#1E1915] text-[#DFC97A] text-[9px] font-black uppercase tracking-wider shrink-0">
                                            Artisan
                                        </span>
                                        <span style="font-family:ui-serif,Georgia,serif;" class="text-sm font-bold text-[#1E1915] group-hover:text-[#C0422A] transition-colors truncate">
                                            {{ $shopName }}
                                        </span>
                                        <svg class="w-3.5 h-3.5 text-stone-400 group-hover:text-[#C0422A] transition-colors shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                        </svg>
                                    </a>
                                </div>

                                <button type="button"
                                        @click="checkoutShopOnly('{{ addslashes($shopName) }}')"
                                        class="inline-flex items-center gap-1 text-[11px] font-bold text-[#8C6212] hover:text-[#1E1915] bg-[#FAF5EA] hover:bg-[#EAE2D2] border border-[#E6D8BA] px-2.5 py-1 rounded-lg transition-all cursor-pointer shrink-0">
                                    <span>Checkout Shop</span>
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                                </button>
                            </div>

                            {{-- Product Items in Shop --}}
                            <div class="divide-y divide-[#EAE2D2]">
                                @foreach($shopItems as $cartKey => $item)
                                    @php
                                        $itemKey = (string) $cartKey;
                                        $itemProduct = !empty($item['id']) ? \App\Models\Product::find($item['id']) : null;
                                        $imgSrc = \App\Support\VariationFormatter::getImageForVariation($item['variation'] ?? null, $itemProduct)
                                            ?: (!empty($item['image']) ? $item['image'] : ($itemProduct ? $itemProduct->getImageUrl() : asset('uploads/products/default.jpg')));
                                    @endphp
                                    <div class="p-4 sm:p-5 transition-colors duration-150"
                                         x-show="itemMatchesSearch('{{ addslashes($itemKey) }}')"
                                         :style="isSelected('{{ addslashes($itemKey) }}') ? 'background-color:#FAF6EE;' : 'background-color:#FDFBF7;'">

                                        <div class="flex gap-3.5 sm:gap-4 items-center">
                                            {{-- Checkbox --}}
                                            <label class="cursor-pointer shrink-0">
                                                <input type="checkbox"
                                                       value="{{ $itemKey }}"
                                                       x-model="selected"
                                                       @change="syncSelectAll()"
                                                       class="w-4 h-4 rounded border-stone-300 text-[#1E1915] accent-[#1E1915] cursor-pointer shrink-0">
                                            </label>

                                            {{-- Product Thumbnail --}}
                                            <a href="/products/{{ $item['id'] ?? '#' }}" class="shrink-0">
                                                <img src="{{ $imgSrc }}" class="w-16 h-16 sm:w-20 sm:h-20 object-cover object-top rounded-xl bg-[#FAF8F5] border border-[#ECE3D2]">
                                            </a>

                                            {{-- Product Details --}}
                                            <div class="flex-1 min-w-0">
                                                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-1 sm:gap-4">
                                                    <div class="min-w-0">
                                                        <a href="/products/{{ $item['id'] ?? '#' }}"
                                                           class="font-extrabold text-[#1E1915] hover:text-[#C0422A] transition-colors text-xs sm:text-sm block truncate uppercase tracking-tight">
                                                            {{ $item['name'] }}
                                                        </a>

                                                        {{-- Size / Variation Pill --}}
                                                        <div class="mt-1 flex flex-wrap items-center gap-1.5 text-[10px] text-[#78716C]">
                                                            <span class="inline-flex items-center px-2 py-0.5 rounded bg-[#FAF5EA] border border-[#EAE2D2] font-semibold">
                                                                @if(!empty($item['size']))
                                                                    Size: <strong class="ml-1 text-[#1E1915]">{{ $item['size'] }}</strong>
                                                                @else
                                                                    Standard Size
                                                                @endif
                                                                @if(!empty($item['variation']))
                                                                    @php
                                                                        $variationLabel = \App\Support\VariationFormatter::label(
                                                                            $item['variation'],
                                                                            \App\Models\Product::find($item['id'] ?? null)?->image
                                                                        ) ?? $item['variation'];
                                                                    @endphp
                                                                    <span class="mx-1 text-stone-300">•</span>
                                                                    Var: <strong class="ml-1 text-[#1E1915]">{{ $variationLabel }}</strong>
                                                                @endif
                                                            </span>
                                                        </div>
                                                    </div>

                                                    {{-- Price Display --}}
                                                    <div class="text-left sm:text-right shrink-0 mt-1 sm:mt-0">
                                                        @if(!empty($item['is_on_sale']) && ($item['discount_percentage'] ?? 0) > 0)
                                                            <div class="text-[#C0422A] font-black text-sm sm:text-base">₱{{ number_format($item['price']) }}</div>
                                                            <div class="text-[10px] text-stone-400 line-through">₱{{ number_format($item['original_price'] ?? $item['price']) }}</div>
                                                        @else
                                                            <div class="text-[#1E1915] font-black text-sm sm:text-base">₱{{ number_format($item['price']) }}</div>
                                                        @endif
                                                    </div>
                                                </div>

                                                {{-- Bottom Row: Stepper & Remove --}}
                                                <div class="flex items-center justify-between sm:justify-end gap-3 mt-3 pt-2 border-t border-[#EAE2D2]">
                                                    <div class="flex items-center gap-3">
                                                        {{-- Minimalist Stepper --}}
                                                        <div class="flex items-center border border-[#EAE2D2] rounded-lg overflow-hidden h-7 bg-white">
                                                            <button type="button" 
                                                                    @click="updateQty('{{ addslashes($itemKey) }}', (items.find(i => String(i.key) === '{{ addslashes($itemKey) }}')?.quantity || {{ $item['quantity'] }}) - 1)" 
                                                                    class="w-7 h-7 flex items-center justify-center text-stone-500 hover:text-black hover:bg-[#FAF8F5] font-bold text-xs transition-colors cursor-pointer">−</button>
                                                            <span class="w-8 text-center text-xs font-bold text-[#1E1915] border-x border-[#EAE2D2]" 
                                                                  x-text="items.find(i => String(i.key) === '{{ addslashes($itemKey) }}')?.quantity || {{ $item['quantity'] }}"></span>
                                                            <button type="button" 
                                                                    @click="updateQty('{{ addslashes($itemKey) }}', (items.find(i => String(i.key) === '{{ addslashes($itemKey) }}')?.quantity || {{ $item['quantity'] }}) + 1)" 
                                                                    class="w-7 h-7 flex items-center justify-center text-stone-500 hover:text-black hover:bg-[#FAF8F5] font-bold text-xs transition-colors cursor-pointer">+</button>
                                                        </div>

                                                        {{-- Trash Button --}}
                                                        <button type="button" 
                                                                @click="promptRemoveItem('{{ addslashes($itemKey) }}')" 
                                                                class="text-stone-400 hover:text-red-600 transition-colors p-1 cursor-pointer" 
                                                                title="Remove item">
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

            {{-- ===== Right: Minimalist Order Summary Sidebar (4 cols) ===== --}}
            <div class="lg:col-span-5 xl:col-span-4 hidden lg:block">
                <div style="background-color:#FDFBF7;border:1px solid #EAE2D2;border-radius:24px;padding:24px;box-shadow:0 10px 30px rgba(0,0,0,0.04);position:sticky;top:96px;" class="space-y-5">
                    <div>
                        <h2 style="font-family:ui-serif,Georgia,serif;" class="text-xl font-bold text-[#1E1915] tracking-tight">
                            Order Summary
                        </h2>
                        <p class="text-[11px] font-bold uppercase tracking-wider text-[#78716C] mt-1">
                            <span x-text="selected.length"></span> of <span x-text="items.length"></span> item(s) selected
                        </p>
                    </div>

                    {{-- Empty selection notice --}}
                    <div x-show="selected.length === 0" class="p-4 text-center bg-[#FAF5EA] rounded-xl border border-dashed border-[#EAE2D2]">
                        <p class="text-xs font-bold text-[#78716C]">Select items to proceed to checkout</p>
                    </div>

                    <div x-show="selected.length > 0" class="space-y-3">
                        <div class="flex justify-between text-xs text-[#78716C]">
                            <span>Subtotal</span>
                            <span class="font-bold text-[#1E1915]">₱<span x-text="subtotal.toLocaleString('en-PH', {minimumFractionDigits:0,maximumFractionDigits:0})"></span></span>
                        </div>
                        <div class="flex justify-between text-xs text-[#78716C]">
                            <span>Estimated Shipping</span>
                            <span x-show="shipping > 0" class="font-bold text-[#1E1915]">₱<span x-text="shipping.toLocaleString('en-PH', {minimumFractionDigits:2,maximumFractionDigits:2})"></span></span>
                            <span x-show="shipping === 0" class="font-bold text-emerald-700">Free</span>
                        </div>

                        {{-- Total line --}}
                        <div class="pt-3 border-t border-[#EAE2D2] flex justify-between items-baseline">
                            <span class="text-sm font-bold text-[#1E1915] uppercase tracking-wider">Total</span>
                            <span class="text-2xl font-black text-[#C0422A]">₱<span x-text="(subtotal + shipping).toLocaleString('en-PH', {minimumFractionDigits:0,maximumFractionDigits:0})"></span></span>
                        </div>
                    </div>

                    {{-- Checkout Form --}}
                    <form action="/checkout/selected" method="POST" class="pt-1" x-ref="checkoutForm">
                        @csrf
                        <template x-for="key in selected" :key="key">
                            <input type="hidden" name="selected_keys[]" :value="key">
                        </template>
                        <button type="submit"
                                :disabled="selected.length === 0"
                                :style="selected.length === 0 
                                    ? 'width:100%;background-color:#A8A29E;color:#FFFFFF;padding:14px;border-radius:14px;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:0.14em;border:none;display:flex;align-items:center;justify-content:center;gap:6px;cursor:not-allowed;opacity:0.6;' 
                                    : 'width:100%;background-color:#1E1915;color:#FFFFFF;padding:14px;border-radius:14px;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:0.14em;border:none;display:flex;align-items:center;justify-content:center;gap:6px;cursor:pointer;opacity:1;box-shadow:0 4px 14px rgba(0,0,0,0.15);transition:all 0.2s;'"
                                style="width:100%;background-color:#1E1915;color:#FFFFFF;padding:14px;border-radius:14px;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:0.14em;border:none;display:flex;align-items:center;justify-content:center;gap:6px;"
                                onmouseover="if(this.getAttribute('disabled') === null) this.style.backgroundColor='#C0422A';"
                                onmouseout="if(this.getAttribute('disabled') === null) this.style.backgroundColor='#1E1915';">
                            <span style="color:#FFFFFF !important;font-weight:700;letter-spacing:0.12em;text-transform:uppercase;">Proceed to Checkout</span>
                            <span x-show="selected.length > 0" style="color:#DFC97A !important;font-weight:800;" x-text="'(' + selected.length + ')'"></span>
                        </button>
                    </form>
                </div>
            </div>

            {{-- ===== Mobile Sticky Checkout Bar ===== --}}
            <div x-show="items.length > 0"
                 x-cloak
                 class="lg:hidden fixed bottom-16 left-0 right-0 z-40 bg-white border-t border-[#ECE3D2] shadow-lg px-4 py-3"
                 x-data="{ showMobileBreakdown: false }">

                {{-- Price breakdown popup --}}
                <div x-show="showMobileBreakdown" 
                     x-cloak
                     x-transition
                     class="mb-2.5 p-3 bg-[#FAF8F5] rounded-xl border border-[#ECE3D2] text-xs space-y-1.5 shadow-xs">
                    <div class="flex justify-between text-[#78716C]">
                        <span>Subtotal (<span x-text="selected.length"></span> items)</span>
                        <span class="font-bold text-[#1E1915]">₱<span x-text="subtotal.toLocaleString('en-PH', {minimumFractionDigits:0,maximumFractionDigits:0})"></span></span>
                    </div>
                    <div class="flex justify-between text-[#78716C]">
                        <span>Shipping Fee</span>
                        <span x-show="shipping > 0" class="font-bold text-[#1E1915]">₱<span x-text="shipping.toLocaleString('en-PH', {minimumFractionDigits:2,maximumFractionDigits:2})"></span></span>
                        <span x-show="shipping === 0" class="font-bold text-emerald-700">Free</span>
                    </div>
                </div>

                <div class="flex items-center justify-between gap-3">
                    {{-- Left: Select All Checkbox --}}
                    <label class="flex items-center gap-1.5 cursor-pointer select-none shrink-0">
                        <input type="checkbox"
                               x-model="allSelected"
                               @change="toggleAll()"
                               class="w-4 h-4 rounded border-stone-300 text-[#1E1915] accent-[#1E1915] cursor-pointer shrink-0">
                        <span class="text-xs font-bold text-[#1E1915] uppercase tracking-wider">All</span>
                    </label>

                    {{-- Middle: Subtotal & Expand --}}
                    <div class="flex-1 text-right min-w-0 pr-1">
                        <button type="button" @click="showMobileBreakdown = !showMobileBreakdown" class="inline-flex items-center gap-1 text-xs font-bold text-[#1E1915] cursor-pointer">
                            <span>Total:</span>
                            <span class="font-black text-[#C0422A]">₱<span x-text="(subtotal + shipping).toLocaleString('en-PH', {minimumFractionDigits:0,maximumFractionDigits:0})"></span></span>
                            <svg class="w-3.5 h-3.5 transition-transform text-stone-400" :class="showMobileBreakdown ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                    </div>

                    {{-- Right: Checkout Button --}}
                    <button type="button"
                            @click="$refs.checkoutForm.submit()"
                            :disabled="selected.length === 0"
                            :style="selected.length === 0 
                                ? 'background-color:#A8A29E;color:#FFFFFF;padding:10px 18px;border-radius:12px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;border:none;cursor:not-allowed;opacity:0.6;' 
                                : 'background-color:#1E1915;color:#FFFFFF;padding:10px 18px;border-radius:12px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;border:none;cursor:pointer;opacity:1;box-shadow:0 2px 8px rgba(0,0,0,0.12);transition:all 0.2s;'"
                            style="background-color:#1E1915;color:#FFFFFF;padding:10px 18px;border-radius:12px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;border:none;"
                            class="shrink-0">
                        <span style="color:#FFFFFF !important;font-weight:700;">Check Out</span>
                        <span x-show="selected.length > 0" style="color:#DFC97A !important;font-weight:800;" x-text="'(' + selected.length + ')'"></span>
                    </button>
                </div>
            </div>

        </div>

        {{-- Empty Cart view --}}
        <div x-show="items.length === 0" style="display: none;" class="bg-white border border-[#ECE3D2] rounded-2xl p-12 text-center shadow-xs max-w-lg mx-auto my-8">
            <div class="w-14 h-14 rounded-full bg-[#FAF8F5] border border-[#ECE3D2] flex items-center justify-center mx-auto mb-4 text-[#8C6212]">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
            </div>
            <h3 style="font-family:ui-serif,Georgia,serif;" class="text-xl font-bold text-[#1E1915] mb-1.5">Your Shopping Bag is Empty</h3>
            <p class="text-xs text-[#78716C] mb-6 leading-relaxed">Discover handcrafted Barong Tagalog & Filipiniana pieces created by authentic Lumban artisans.</p>
            <a href="/" class="inline-flex items-center justify-center px-6 py-2.5 rounded-xl bg-[#1E1915] hover:bg-[#C0422A] text-white text-xs font-bold uppercase tracking-wider shadow-sm transition-all">
                Explore Collection
            </a>
        </div>

        {{-- Custom Confirmation Modal --}}
        <div x-show="showDeleteModal"
             x-cloak
             class="fixed inset-0 z-50 flex items-center justify-center p-4"
             style="display: none;"
             @keydown.escape.window="showDeleteModal = false">
            
            <!-- Backdrop -->
            <div @click="showDeleteModal = false" class="absolute inset-0 bg-black/60 backdrop-blur-xs"></div>

            <div class="bg-white border border-[#ECE3D2] rounded-2xl shadow-2xl p-6 relative z-10 w-full max-w-sm text-center"
                 @click.stop
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95">
                
                {{-- Warning Trash Icon --}}
                <div class="w-12 h-12 rounded-full bg-red-50 border border-red-200 text-red-600 flex items-center justify-center mx-auto mb-3.5">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                </div>

                <div>
                    <h3 style="font-family:ui-serif,Georgia,serif;" class="text-lg font-bold text-[#1E1915] mb-1.5" x-text="deleteModalTitle"></h3>
                    <p class="text-xs text-[#78716C] m-0 leading-relaxed" x-text="deleteModalMessage"></p>
                </div>

                <div class="flex items-center gap-3 pt-5">
                    <button type="button"
                            @click="showDeleteModal = false"
                            class="flex-1 py-2.5 px-4 rounded-xl border border-[#ECE3D2] bg-[#FAF8F5] hover:bg-stone-100 text-[#78716C] text-xs font-bold uppercase tracking-wider transition-colors cursor-pointer">
                        Cancel
                    </button>
                    <button type="button"
                            @click="confirmDelete()"
                            class="flex-1 py-2.5 px-4 rounded-xl bg-red-600 hover:bg-red-700 text-white text-xs font-bold uppercase tracking-wider shadow-sm transition-colors cursor-pointer">
                        Delete
                    </button>
                </div>
            </div>
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
        searchQuery: '',
        showDeleteModal: false,
        deleteModalTitle: '',
        deleteModalMessage: '',
        deleteAction: null,

        itemMatchesSearch(key) {
            if (!this.searchQuery || !this.searchQuery.trim()) return true;
            const item = this.items.find(i => String(i.key) === String(key));
            if (!item) return false;
            const q = this.searchQuery.toLowerCase().trim();
            const name = (item.name || '').toLowerCase();
            const shop = (item.shop_name || '').toLowerCase();
            const variation = (item.variation || '').toLowerCase();
            const size = (item.size || '').toLowerCase();
            return name.includes(q) || shop.includes(q) || variation.includes(q) || size.includes(q);
        },

        shopMatchesSearch(shopName) {
            if (!this.searchQuery || !this.searchQuery.trim()) {
                return this.items.some(i => (i.shop_name || 'Lumban Heritage Shop') === shopName);
            }
            const shopItems = this.items.filter(i => (i.shop_name || 'Lumban Heritage Shop') === shopName);
            return shopItems.some(i => this.itemMatchesSearch(i.key));
        },

        get hasMatchingItems() {
            if (!this.searchQuery || !this.searchQuery.trim()) return this.items.length > 0;
            return this.items.some(i => this.itemMatchesSearch(i.key));
        },

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
