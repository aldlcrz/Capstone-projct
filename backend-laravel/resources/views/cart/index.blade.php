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
                        <h1 style="font-family:ui-serif,Georgia,Cambria,serif;font-size:22px;font-weight:700;color:#1E1915;letter-spacing:-0.01em;line-height:1.2;margin:0;">
                            Shopping Bag & Atelier Cart
                        </h1>
                        <p style="font-size:12.5px;color:#78716C;margin-top:3px;margin-bottom:0;">
                            Select handcrafted pieces from authentic Lumban artisans to proceed to checkout
                        </p>
                    </div>
                </div>

                <a href="/" class="hidden sm:inline-flex items-center gap-2 text-xs font-bold text-gray-500 hover:text-[#C0422A] transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
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

        {{-- When Cart has items --}}
        <div class="flex flex-col lg:flex-row gap-6 sm:gap-8 items-start" x-show="items.length > 0">

            {{-- ===== Left: Cart Items ===== --}}
            <div class="flex-1 space-y-4 min-w-0 w-full">

                {{-- Sub-header with Select All & Actions --}}
                <div style="background-color:#FFFFFF;border:1px solid #ECE3D2;border-radius:18px;padding:12px 18px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;box-shadow:0 2px 6px rgba(0,0,0,0.02);">
                    <label class="flex items-center gap-2.5 cursor-pointer group select-none">
                        <input type="checkbox"
                               id="select-all"
                               x-model="allSelected"
                               @change="toggleAll()"
                               class="w-4 h-4 rounded border border-gray-300 text-[#C0422A] accent-[#C0422A] cursor-pointer shrink-0">
                        <span style="font-size:12px;font-weight:700;color:#1E1915;text-transform:uppercase;letter-spacing:0.08em;">
                            Select All (<span x-text="selected.length"></span>/<span x-text="items.length"></span>)
                        </span>
                    </label>

                    {{-- Delete Selected / Delete All Button --}}
                    <button type="button"
                            x-show="selected.length > 0"
                            @click="promptDeleteSelected()"
                            style="display:inline-flex;align-items:center;gap:6px;padding:6px 12px;border-radius:10px;background:#FEF2F2;border:1px solid #FECACA;color:#DC2626;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;cursor:pointer;transition:all 0.2s;"
                            onmouseover="this.style.background='#DC2626';this.style.color='#FFFFFF';"
                            onmouseout="this.style.background='#FEF2F2';this.style.color='#DC2626';">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                        <span x-text="selected.length === items.length ? 'Delete All' : 'Delete Selected (' + selected.length + ')'"></span>
                    </button>
                </div>

                {{-- Cart Items Grouped by Shop --}}
                <div class="space-y-4">
                    @forelse($groupedCart as $shopName => $shopItems)
                        @php
                            $firstItem = reset($shopItems);
                            $sellerId = $firstItem['sellerId'] ?? null;
                        @endphp
                        <div style="background-color:#FFFFFF;border:1px solid #ECE3D2;border-radius:20px;box-shadow:0 4px 14px rgba(0,0,0,0.03);overflow:hidden;transition:all 0.2s;"
                             x-show="items.some(i => (i.shop_name || 'Lumban Heritage Shop') === '{{ addslashes($shopName) }}')">

                            {{-- Shop Header Bar --}}
                            <div style="background-color:#FAF6EE;border-bottom:1px solid #EAE1D0;padding:12px 18px;display:flex;align-items:center;justify-content:space-between;gap:12px;">
                                <div class="flex items-center gap-2.5 min-w-0">
                                    <label class="flex items-center cursor-pointer shrink-0">
                                        <input type="checkbox"
                                               @change="toggleShop('{{ addslashes($shopName) }}', $event.target.checked)"
                                               :checked="isShopSelected('{{ addslashes($shopName) }}')"
                                               class="w-4 h-4 rounded border border-gray-300 text-[#C0422A] accent-[#C0422A] cursor-pointer shrink-0">
                                    </label>

                                    <a href="{{ $sellerId ? '/shops/' . $sellerId : '#' }}" class="flex items-center gap-2 font-bold text-xs sm:text-sm text-[#1E1915] hover:text-[#C0422A] transition-colors truncate">
                                        <div style="display:inline-flex;align-items:center;gap:4px;padding:2px 8px;background:linear-gradient(135deg,#0F0C08 0%,#1C1609 100%);border:1px solid #A87B10;border-radius:12px;box-shadow:0 0 6px rgba(180,130,15,0.35);">
                                            <span style="color:#DFC97A;font-size:7.5px;font-weight:800;letter-spacing:0.08em;text-transform:uppercase;">Artisan</span>
                                        </div>
                                        <span class="font-serif font-bold truncate text-[#1E1915]">{{ $shopName }}</span>
                                        <svg class="w-3.5 h-3.5 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                        </svg>
                                    </a>
                                </div>

                                <div class="flex items-center gap-2 shrink-0">
                                    <button type="button"
                                            @click="checkoutShopOnly('{{ addslashes($shopName) }}')"
                                            style="display:inline-flex;align-items:center;gap:5px;padding:4px 10px;border-radius:10px;background:#FAF5EA;border:1px solid #E6D8BA;color:#8C6212;font-size:11px;font-weight:700;cursor:pointer;transition:all 0.2s;"
                                            onmouseover="this.style.background='#8C6212';this.style.color='#FFFFFF';"
                                            onmouseout="this.style.background='#FAF5EA';this.style.color='#8C6212';">
                                        <span>Checkout Shop</span>
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                                    </button>
                                </div>
                            </div>

                            {{-- Product Items --}}
                            <div class="divide-y divide-[#F0EAE0] p-3 space-y-3">
                                @foreach($shopItems as $cartKey => $item)
                                    @php
                                        $itemKey = (string) $cartKey;
                                        $itemProduct = !empty($item['id']) ? \App\Models\Product::find($item['id']) : null;
                                        $imgSrc = $itemProduct ? $itemProduct->getImageUrl() : asset('uploads/products/default.jpg');
                                        if ($imgSrc === asset('uploads/products/default.jpg') && !empty($item['image'])) {
                                            $img = $item['image'];
                                            if (str_starts_with($img, 'http') || str_starts_with($img, '/')) {
                                                $imgSrc = $img;
                                            } else {
                                                $cleanImg = ltrim($img, '/');
                                                if (file_exists(public_path('uploads/' . $cleanImg))) {
                                                    $imgSrc = asset('uploads/' . $cleanImg);
                                                } elseif (file_exists(public_path('uploads/products/' . $cleanImg))) {
                                                    $imgSrc = asset('uploads/products/' . $cleanImg);
                                                } elseif (file_exists(storage_path('app/public/' . $cleanImg))) {
                                                    $imgSrc = asset('storage/' . $cleanImg);
                                                }
                                            }
                                        }
                                    @endphp
                                    <div class="p-3 rounded-xl border transition-all duration-200"
                                         x-show="items.some(i => String(i.key) === '{{ addslashes($itemKey) }}')"
                                         x-transition
                                         :class="isSelected('{{ addslashes($itemKey) }}')
                                             ? 'border-[#DFC97A] shadow-xs bg-[#FDFBF7]'
                                             : 'border-[#F0EAE0] bg-white'">

                                        {{-- Main Row: Checkbox + Image + Details --}}
                                        <div class="flex gap-3.5 items-start">
                                            {{-- Checkbox --}}
                                            <div class="pt-2 shrink-0">
                                                <label class="block cursor-pointer">
                                                    <input type="checkbox"
                                                           value="{{ $itemKey }}"
                                                           x-model="selected"
                                                           @change="syncSelectAll()"
                                                           class="w-4 h-4 rounded border border-gray-300 text-[#C0422A] accent-[#C0422A] cursor-pointer shrink-0">
                                                </label>
                                            </div>

                                            {{-- Product Image (Square 84px) --}}
                                            <a href="/products/{{ $item['id'] ?? '#' }}" class="shrink-0">
                                                <img src="{{ $imgSrc }}" class="w-20 h-20 sm:w-22 sm:h-22 object-cover object-top rounded-xl bg-[#FAF8F5] border border-[#ECE3D2] shadow-2xs">
                                            </a>

                                            {{-- Product Info Container --}}
                                            <div class="flex-1 min-w-0 flex flex-col justify-between self-stretch">
                                                <div>
                                                    {{-- Title with Category Tag --}}
                                                    <div class="flex items-start gap-1.5">
                                                        @if(!empty($item['category_name']))
                                                            <span class="inline-block text-[8px] sm:text-[9px] font-black text-white bg-[#1E1915] uppercase tracking-wider px-1.5 py-0.5 rounded shrink-0 mt-0.5">{{ $item['category_name'] }}</span>
                                                        @endif
                                                        <a href="/products/{{ $item['id'] ?? '#' }}"
                                                           class="font-extrabold text-[#1E1915] hover:text-[#C0422A] transition-colors block text-xs sm:text-sm leading-tight line-clamp-2 uppercase tracking-tight">
                                                            {{ $item['name'] }}
                                                        </a>
                                                    </div>

                                                    {{-- Variant / Size Badge --}}
                                                    <div class="mt-1.5">
                                                        <span class="inline-flex items-center gap-1.5 text-[10px] font-semibold text-[#78716C] bg-[#FAF8F5] px-2 py-0.5 rounded-md border border-[#EAE2D2]">
                                                            @if(!empty($item['size']))
                                                                <span>Size: <strong class="text-[#1E1915]">{{ $item['size'] }}</strong></span>
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
                                                                <span>• Var: <strong class="text-[#1E1915]">{{ $variationLabel }}</strong></span>
                                                            @endif
                                                        </span>
                                                    </div>
                                                </div>

                                                {{-- Bottom Price & Stepper Row --}}
                                                <div class="flex items-end justify-between gap-2 mt-2 pt-1">
                                                    {{-- Price Column --}}
                                                    <div class="flex items-baseline gap-1.5 flex-wrap">
                                                        @if(!empty($item['is_on_sale']) && ($item['discount_percentage'] ?? 0) > 0)
                                                            <span class="text-[#E02424] font-black text-sm sm:text-base">₱{{ number_format($item['price']) }}</span>
                                                            <span class="text-[10px] text-gray-400 line-through">₱{{ number_format($item['original_price'] ?? $item['price']) }}</span>
                                                        @else
                                                            <span class="text-[#1E1915] font-black text-sm sm:text-base">₱{{ number_format($item['price']) }}</span>
                                                        @endif
                                                    </div>

                                                    {{-- Stepper & Delete Action --}}
                                                    <div class="flex items-center gap-2 shrink-0">
                                                        {{-- Quantity Stepper --}}
                                                        <div class="flex items-center border border-[#ECE3D2] rounded-lg overflow-hidden h-7 bg-white shadow-2xs">
                                                            <button type="button" @click="updateQty('{{ addslashes($itemKey) }}', (items.find(i => String(i.key) === '{{ addslashes($itemKey) }}')?.quantity || {{ $item['quantity'] }}) - 1)" class="w-7 h-7 flex items-center justify-center text-gray-400 hover:text-black hover:bg-[#FAF8F5] font-bold text-xs transition-colors cursor-pointer">−</button>
                                                            <span class="w-7 text-center text-xs font-bold text-[#1E1915] border-x border-[#ECE3D2]" x-text="items.find(i => String(i.key) === '{{ addslashes($itemKey) }}')?.quantity || {{ $item['quantity'] }}"></span>
                                                            <button type="button" @click="updateQty('{{ addslashes($itemKey) }}', (items.find(i => String(i.key) === '{{ addslashes($itemKey) }}')?.quantity || {{ $item['quantity'] }}) + 1)" class="w-7 h-7 flex items-center justify-center text-gray-400 hover:text-black hover:bg-[#FAF8F5] font-bold text-xs transition-colors cursor-pointer">+</button>
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
                <div style="background-color:#FDFBF7;border:1px solid #EAE2D2;border-radius:24px;box-shadow:0 10px 30px rgba(0,0,0,0.04);padding:28px 24px;position:sticky;top:96px;">
                    <div style="display:flex;align-items:center;gap:10px;margin-bottom:4px;">
                        <span style="color:#C49520;font-size:14px;">✦</span>
                        <h2 style="font-family:ui-serif,Georgia,serif;font-size:20px;font-weight:700;color:#1E1915;margin:0;">Order Summary</h2>
                    </div>
                    <p style="font-size:11px;color:#78716C;text-transform:uppercase;letter-spacing:0.1em;font-weight:700;margin-bottom:20px;">
                        <span x-text="selected.length"></span> of <span x-text="items.length"></span> item(s) selected
                    </p>

                    {{-- Empty selection notice --}}
                    <div x-show="selected.length === 0"
                         style="padding:20px;text-align:center;background-color:#FAF8F5;border-radius:16px;border:1px dashed #E2D9C8;margin-bottom:20px;">
                        <svg class="w-8 h-8 text-gray-300 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 012-2h2a2 2 0 012-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                        <p style="font-size:12px;color:#78716C;font-weight:700;margin:0;">Select items to checkout</p>
                    </div>

                    <div x-show="selected.length > 0" class="space-y-3.5">
                        <div class="flex justify-between text-[#78716C] text-sm">
                            <span>Subtotal (<span x-text="selected.length"></span> item<span x-show="selected.length !== 1">s</span>)</span>
                            <span class="font-bold text-[#1E1915]">₱<span x-text="subtotal.toLocaleString('en-PH', {minimumFractionDigits:0,maximumFractionDigits:0})"></span></span>
                        </div>
                        <div class="flex justify-between text-[#78716C] text-sm">
                            <span>Shipping</span>
                            <span x-show="shipping > 0" class="text-[#1E1915] font-bold">₱<span x-text="shipping.toLocaleString('en-PH', {minimumFractionDigits:2,maximumFractionDigits:2})"></span></span>
                            <span x-show="shipping === 0" class="text-emerald-700 font-bold">Free</span>
                        </div>

                        {{-- Divider --}}
                        <div style="border-top:1px solid #EAE1D0;margin-top:16px;padding-top:16px;" class="flex justify-between items-center">
                            <span style="font-family:ui-serif,Georgia,serif;font-size:18px;font-weight:700;color:#1E1915;">Total</span>
                            <span style="font-size:24px;font-weight:900;color:#C0422A;">₱<span x-text="(subtotal + shipping).toLocaleString('en-PH', {minimumFractionDigits:0,maximumFractionDigits:0})"></span></span>
                        </div>
                    </div>

                    {{-- Checkout form - posts selected keys --}}
                    <form action="/checkout/selected" method="POST" class="mt-6" x-ref="checkoutForm">
                        @csrf
                        <template x-for="key in selected" :key="key">
                            <input type="hidden" name="selected_keys[]" :value="key">
                        </template>
                        <button type="submit"
                                :disabled="selected.length === 0"
                                :class="selected.length === 0
                                    ? 'opacity-40 cursor-not-allowed bg-gray-400'
                                    : 'bg-[#1E1915] hover:bg-[#C0422A] shadow-lg cursor-pointer'"
                                style="width:100%;color:#FFFFFF;padding:14px;border-radius:14px;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:0.14em;transition:all 0.2s;">
                            <span>Proceed to Checkout</span>
                            <span x-show="selected.length > 0"
                                  style="color:#DFC97A;margin-left:4px;"
                                  x-text="'(' + selected.length + ')'">
                            </span>
                        </button>
                    </form>

                    <a href="/" style="display:block;width:100%;text-align:center;padding:10px 0;font-size:11px;font-weight:700;color:#78716C;text-transform:uppercase;letter-spacing:0.1em;text-decoration:none;margin-top:8px;transition:color 0.2s;"
                       onmouseover="this.style.color='#1E1915';"
                       onmouseout="this.style.color='#78716C';">
                        Continue Browsing Collections
                    </a>
                </div>
            </div>

            {{-- ===== Mobile Sticky Checkout Bar ===== --}}
            <div x-show="items.length > 0"
                 x-cloak
                 class="lg:hidden fixed bottom-16 left-0 right-0 z-40 bg-[#FDFBF7] border-t border-[#EAE2D2] shadow-[0_-4px_20px_rgba(0,0,0,0.08)] px-3.5 py-3"
                 x-data="{ showMobileBreakdown: false }">

                {{-- Price breakdown popup --}}
                <div x-show="showMobileBreakdown" 
                     x-cloak
                     x-transition
                     class="mb-2.5 p-3.5 bg-white rounded-2xl border border-[#ECE3D2] text-xs space-y-1.5 shadow-sm">
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

                <div class="flex items-center justify-between gap-2.5">
                    {{-- Left: Select All Checkbox --}}
                    <label class="flex items-center gap-1.5 cursor-pointer select-none shrink-0">
                        <input type="checkbox"
                               x-model="allSelected"
                               @change="toggleAll()"
                               class="w-4 h-4 rounded border border-gray-300 text-[#C0422A] accent-[#C0422A] cursor-pointer shrink-0">
                        <span class="text-xs font-bold text-[#1E1915] uppercase tracking-wider">All</span>
                    </label>

                    {{-- Middle: Subtotal & Expand --}}
                    <div class="flex-1 text-right min-w-0 pr-1">
                        <button type="button" @click="showMobileBreakdown = !showMobileBreakdown" class="inline-flex items-center gap-1 text-[11px] font-bold text-[#1E1915] cursor-pointer">
                            <span>Total:</span>
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
                                : 'bg-[#1E1915] hover:bg-[#C0422A] active:scale-95 shadow-md cursor-pointer'"
                            class="px-5 py-2.5 text-white text-xs font-bold uppercase tracking-wider rounded-xl transition-all shrink-0">
                        Check Out <span x-show="selected.length > 0" x-text="'(' + selected.length + ')'"></span>
                    </button>
                </div>
            </div>

        </div>

        {{-- Empty Cart view --}}
        <div x-show="items.length === 0" style="display: none;background-color:#FDFBF7;border:1px solid #EAE2D2;border-radius:28px;padding:48px 24px;text-align:center;box-shadow:0 10px 30px rgba(0,0,0,0.04);max-width:520px;margin:32px auto;">
            <div style="width:64px;height:64px;border-radius:50%;background-color:#FAF5EA;border:1px solid #E6D8BA;display:flex;align-items:center;justify-content:center;margin:0 auto 16px auto;color:#C49520;">
                <svg width="28" height="28" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
            </div>
            <h3 style="font-family:ui-serif,Georgia,serif;font-size:20px;font-weight:700;color:#1E1915;margin-bottom:6px;">Your Shopping Bag is Empty</h3>
            <p style="font-size:12.5px;color:#78716C;margin-bottom:24px;line-height:1.5;">Discover handcrafted Barong Tagalog & Filipiniana pieces created by master embroiderers in Lumban.</p>
            <a href="/" style="display:inline-flex;align-items:center;justify-content:center;padding:10px 24px;border-radius:14px;background-color:#1E1915;color:#FFFFFF;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:0.12em;text-decoration:none;box-shadow:0 4px 12px rgba(0,0,0,0.15);transition:all 0.2s;"
               onmouseover="this.style.backgroundColor='#C0422A';"
               onmouseout="this.style.backgroundColor='#1E1915';">
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

            <div style="background-color:#FDFBF7;border:1px solid #EAE2D2;border-radius:28px;box-shadow:0 25px 60px rgba(0,0,0,0.25);padding:26px;position:relative;z-index:10;width:100%;max-width:380px;text-align:center;"
                 @click.stop
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95">
                
                {{-- Warning Trash Icon --}}
                <div style="width:48px;height:48px;border-radius:50%;background:#FEF2F2;border:1px solid #FECACA;color:#DC2626;display:flex;align-items:center;justify-content:center;margin:0 auto 14px auto;">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                </div>

                <div>
                    <h3 style="font-family:ui-serif,Georgia,serif;font-size:18px;font-weight:700;color:#1E1915;margin-bottom:6px;" x-text="deleteModalTitle"></h3>
                    <p style="font-size:12px;color:#78716C;margin:0;line-height:1.5;" x-text="deleteModalMessage"></p>
                </div>

                <div class="flex items-center gap-3 pt-5">
                    <button type="button"
                            @click="showDeleteModal = false"
                            style="flex:1;padding:10px 16px;border-radius:12px;border:1px solid #E2D9C8;background:#FFFFFF;color:#78716C;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;cursor:pointer;transition:all 0.2s;">
                        Cancel
                    </button>
                    <button type="button"
                            @click="confirmDelete()"
                            style="flex:1;padding:10px 16px;border-radius:12px;background:#DC2626;color:#FFFFFF;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;cursor:pointer;box-shadow:0 4px 12px rgba(220,38,38,0.25);transition:all 0.2s;">
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
