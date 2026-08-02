@extends('layouts.app')

@section('content')
@php
    $cart = session('cart', []);
    $cartItemsForJs = collect($cart)->map(function ($item, $key) {
        return [
            'key'                 => $key,
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
        ];
    })->values();
@endphp

{{-- Build JS-safe items array --}}
<script>
    window.__cartItems = @json($cartItemsForJs);
</script>

<div class="max-w-[1200px] mx-auto px-4 py-12 min-h-screen"
     x-data="cartApp()"
     x-init="init()">

    {{-- Back --}}
    <a href="/" class="inline-flex items-center gap-2 text-[10px] font-bold text-gray-400 uppercase tracking-widest hover:text-black transition-colors mb-6">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
        </svg>
        Back to catalogue
    </a>

    <div class="flex flex-col lg:flex-row gap-8" x-show="items.length > 0">

        {{-- ===== Left: Cart Items ===== --}}
        <div class="flex-1 space-y-4">

            {{-- Header + Select All --}}
            <div class="flex items-center justify-between mb-6 flex-wrap gap-3">
                <div>
                    <div class="flex items-center gap-2 mb-0.5">
                        <div class="w-5 h-[1.5px] bg-[#C0422A]"></div>
                        <span class="text-[10px] font-bold uppercase tracking-widest text-[#C0422A]">Shopping</span>
                    </div>
                    <h1 class="font-serif text-3xl font-bold text-black">Your Cart</h1>
                </div>

                @if(!empty($cart))
                <label class="flex items-center gap-2.5 cursor-pointer group select-none">
                    <div class="relative w-5 h-5">
                        <input type="checkbox"
                               id="select-all"
                               x-model="allSelected"
                               @change="toggleAll()"
                               class="sr-only peer">
                        <div class="w-5 h-5 rounded-md border-2 border-gray-300 peer-checked:bg-[#C0422A] peer-checked:border-[#C0422A] transition-all flex items-center justify-center">
                            <svg x-show="allSelected" class="w-3 h-3 text-white fill-current" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            <svg x-show="!allSelected && selected.length > 0" class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M20 12H4"/>
                            </svg>
                        </div>
                    </div>
                    <span class="text-xs font-bold text-gray-600 group-hover:text-black transition-colors uppercase tracking-widest">
                        Select All (<span x-text="selected.length"></span>/<span>{{ count($cart) }}</span>)
                    </span>
                </label>
                @endif
            </div>

            {{-- Cart Items List --}}
            @forelse($cart as $key => $item)
                @php
                    $img    = $item['image'] ?? '';
                    $imgSrc = str_starts_with($img, 'http') ? $img
                            : (str_starts_with($img, 'products/') ? asset('storage/'.$img) : asset('uploads/products/'.$img));
                @endphp
                <div class="flex gap-4 bg-white p-5 rounded-2xl shadow-sm border transition-all duration-200 items-start"
                     x-show="items.some(i => i.key === '{{ $key }}')"
                     x-transition
                     :class="isSelected('{{ $key }}')
                         ? 'border-[#C0422A]/40 shadow-[#C0422A]/5 shadow-md bg-[#FDF9F4]'
                         : 'border-gray-100'">

                    {{-- Checkbox --}}
                    <div class="pt-1 shrink-0">
                        <label class="relative w-5 h-5 block cursor-pointer">
                            <input type="checkbox"
                                   value="{{ $key }}"
                                   x-model="selected"
                                   @change="syncSelectAll()"
                                   class="sr-only peer">
                            <div class="w-5 h-5 rounded-md border-2 border-gray-300 peer-checked:bg-[#C0422A] peer-checked:border-[#C0422A] transition-all flex items-center justify-center">
                                <svg class="w-3 h-3 text-white fill-current hidden peer-checked:block" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                        </label>
                    </div>

                    {{-- Product Image --}}
                    <a href="/products/{{ $item['id'] ?? '#' }}" class="shrink-0">
                        <img src="{{ $imgSrc }}" class="w-20 h-24 object-cover rounded-xl bg-gray-50 border border-gray-100">
                    </a>

                    {{-- Product Info --}}
                    <div class="flex-1 min-w-0">
                        <div class="flex flex-wrap items-center gap-2 mb-1.5">
                            @if(!empty($item['category_name']))
                                <span class="inline-block text-[9px] font-black text-[#C0422A] uppercase tracking-wider bg-[#C0422A]/5 px-2 py-0.5 rounded">{{ $item['category_name'] }}</span>
                            @endif
                            @if(!empty($item['is_on_sale']))
                                <span class="inline-flex items-center gap-1 text-[9px] font-black text-white bg-[#C0422A] uppercase tracking-wider px-2 py-0.5 rounded shadow-sm">
                                    <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-5 5a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 10V5a2 2 0 012-2z"/>
                                    </svg>
                                    Lumban Special
                                </span>
                            @endif
                        </div>

                        <a href="/products/{{ $item['id'] ?? '#' }}"
                           class="font-bold text-gray-900 hover:text-[#C0422A] transition-colors block truncate text-base">
                            {{ $item['name'] }}
                        </a>

                        <div class="flex flex-wrap items-center gap-3 mt-1.5 text-[10px] font-bold text-gray-400 uppercase tracking-widest">
                            @if(!empty($item['size']))
                                <span class="bg-gray-100 text-gray-700 px-2 py-0.5 rounded">Size: <span class="text-black font-black">{{ $item['size'] }}</span></span>
                            @else
                                <span class="bg-gray-50 text-gray-400 px-2 py-0.5 rounded">Size: Standard</span>
                            @endif
                            @if(!empty($item['variation']))
                                @php
                                    $variationLabel = \App\Support\VariationFormatter::label(
                                        $item['variation'],
                                        \App\Models\Product::find($item['id'] ?? null)?->image
                                    ) ?? $item['variation'];
                                @endphp
                                <span class="bg-gray-100 text-gray-700 px-2 py-0.5 rounded">Variation: <span class="text-black font-black">{{ $variationLabel }}</span></span>
                            @endif
                        </div>

                        {{-- Price --}}
                        <div class="flex items-center gap-2 mt-3">
                            @if(!empty($item['is_on_sale']) && ($item['discount_percentage'] ?? 0) > 0)
                                <span class="text-[#C0422A] font-black text-base">₱{{ number_format($item['price']) }}</span>
                                <span class="text-xs text-gray-400 line-through">₱{{ number_format($item['original_price'] ?? $item['price']) }}</span>
                                <span class="text-[8px] font-black bg-[#C0422A] text-white px-1.5 py-0.5 rounded-md uppercase tracking-wider">-{{ number_format($item['discount_percentage'], 0) }}%</span>
                            @else
                                <span class="text-gray-900 font-black text-base">₱{{ number_format($item['price']) }}</span>
                            @endif
                        </div>
                    </div>

                    {{-- Right side: qty + subtotal + remove --}}
                    <div class="flex flex-col items-end gap-3 shrink-0">
                        {{-- Quantity Stepper --}}
                        <div class="flex items-center border border-gray-200 rounded-xl overflow-hidden h-10 bg-white">
                            <button type="button" @click="updateQty('{{ $key }}', (items.find(i => i.key === '{{ $key }}')?.quantity || {{ $item['quantity'] }}) - 1)" class="w-10 h-10 flex items-center justify-center text-gray-400 hover:text-black hover:bg-gray-50 font-bold text-lg transition-colors">−</button>
                            <span class="w-10 text-center text-sm font-bold text-gray-900 border-x border-gray-200" x-text="items.find(i => i.key === '{{ $key }}')?.quantity || {{ $item['quantity'] }}"></span>
                            <button type="button" @click="updateQty('{{ $key }}', (items.find(i => i.key === '{{ $key }}')?.quantity || {{ $item['quantity'] }}) + 1)" class="w-10 h-10 flex items-center justify-center text-gray-400 hover:text-black hover:bg-gray-50 font-bold text-lg transition-colors">+</button>
                        </div>

                        {{-- Subtotal --}}
                        <div class="text-right">
                            <div class="text-sm font-black text-black" x-text="'₱' + Number((items.find(i => i.key === '{{ $key }}')?.price || 0) * (items.find(i => i.key === '{{ $key }}')?.quantity || 0)).toLocaleString()"></div>
                            <div class="text-[9px] text-gray-400 uppercase tracking-widest">subtotal</div>
                        </div>

                        {{-- Remove --}}
                        <button type="button" @click="removeItem('{{ $key }}')" class="w-9 h-9 rounded-full border border-gray-100 flex items-center justify-center text-gray-300 hover:text-red-500 hover:border-red-200 transition-all cursor-pointer">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        </button>
                    </div>
                </div>

            @empty
                <div class="text-center py-24 bg-white rounded-3xl border border-dashed border-gray-200">
                    <div class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-4 border border-gray-100">
                        <svg class="w-8 h-8 text-gray-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                    </div>
                    <h3 class="text-sm font-bold text-black uppercase tracking-widest mb-1">Your Cart is Empty</h3>
                    <p class="text-xs text-gray-400 mb-6">Discover handcrafted Barong Tagalog pieces from Lumban artisans.</p>
                    <a href="/" class="px-8 py-3 bg-black text-white text-xs font-bold uppercase tracking-widest rounded-full hover:bg-gray-800 transition-all">Explore Collection</a>
                </div>
            @endforelse
        </div>

        {{-- ===== Right: Dynamic Order Summary ===== --}}
        @if(!empty($cart))
        <div class="lg:w-96">
            <div class="bg-white p-8 rounded-3xl border border-gray-100 shadow-sm sticky top-8">
                <h2 class="text-xl font-bold text-black mb-1">Order Summary</h2>
                <p class="text-[10px] text-gray-400 uppercase tracking-widest font-bold mb-6">
                    <span x-text="selected.length"></span> of {{ count($cart) }} item(s) selected
                </p>

                {{-- Empty selection notice --}}
                <div x-show="selected.length === 0"
                     class="py-6 text-center bg-gray-50 rounded-2xl border border-dashed border-gray-200 mb-6">
                    <svg class="w-8 h-8 text-gray-200 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
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
        @endif
    </div>

    {{-- Client-side Empty Cart view --}}
    <div x-show="items.length === 0" style="display: none;" class="text-center py-24 bg-white rounded-3xl border border-dashed border-gray-200">
        <div class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-4 border border-gray-100">
            <svg class="w-8 h-8 text-gray-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
        </div>
        <h3 class="text-sm font-bold text-black uppercase tracking-widest mb-1">Your Cart is Empty</h3>
        <p class="text-xs text-gray-400 mb-6">Discover handcrafted Barong Tagalog pieces from Lumban artisans.</p>
        <a href="/" class="px-8 py-3 bg-black text-white text-xs font-bold uppercase tracking-widest rounded-full hover:bg-gray-800 transition-all">Explore Collection</a>
    </div>
</div>

<script>
function cartApp() {
    return {
        items: window.__cartItems || [],
        selected: [],
        allSelected: false,

        init() {
            // Pre-select all items on load
            this.selected = this.items.map(i => i.key);
            this.syncSelectAll();
        },

        isSelected(key) {
            return this.selected.includes(key);
        },

        toggleAll() {
            if (this.allSelected) {
                this.selected = this.items.map(i => i.key);
            } else {
                this.selected = [];
            }
        },

        syncSelectAll() {
            this.allSelected = this.selected.length === this.items.length && this.items.length > 0;
        },

        get subtotal() {
            return this.items
                .filter(i => this.selected.includes(i.key))
                .reduce((sum, i) => sum + i.price * i.quantity, 0);
        },

        get shipping() {
            const fees = this.items
                .filter(i => this.selected.includes(i.key))
                .map(i => i.shippingFee);
            return fees.length > 0 ? Math.max(...fees) : 0;
        },

        async updateQty(key, newQty) {
            if (newQty < 1) return;
            const item = this.items.find(i => i.key === key);
            if (!item) return;
            const originalQty = item.quantity;
            item.quantity = newQty;

            try {
                const response = await fetch('/cart/update', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
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

        async removeItem(key) {
            try {
                const response = await fetch('/cart/remove/' + encodeURIComponent(key), {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                });
                if (response.ok) {
                    const data = await response.json();
                    if (data.success) {
                        this.items = this.items.filter(i => i.key !== key);
                        this.selected = this.selected.filter(k => k !== key);
                        this.syncSelectAll();
                        window.dispatchEvent(new CustomEvent('cart-updated', { detail: data }));
                    }
                }
            } catch(e) {}
        },
    };
}
</script>
@endsection
