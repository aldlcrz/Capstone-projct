@extends('layouts.app')

@section('content')
@php
    use App\Support\VariationFormatter;
    $productVariations = VariationFormatter::buildVariations($product->image, $product);
@endphp
{{-- Alpine productDetail component MUST be registered here (before the x-data div below).
     Alpine.js loads with `defer`, which means `alpine:init` fires during DOM parse before
     @stack('scripts') at the bottom of body.blade.php. Registering here ensures the
     component is available when Alpine processes the x-data attribute. --}}
<div id="product-page-data"
    data-logged-in="{{ auth()->check() ? 'true' : 'false' }}"
    data-login-url="{{ route('login') }}"
    data-product-id="{{ $product->id }}"
    data-is-wishlisted="{{ ($isWishlisted ?? false) ? 'true' : 'false' }}"
    data-default-image-url="{{ $product->getImageUrl() }}"
    data-csrf-token="{{ csrf_token() }}"
    style="display:none;" aria-hidden="true">
</div>
<script>
    var _pd = document.getElementById('product-page-data') ? document.getElementById('product-page-data').dataset : {};
    window.isLoggedIn = _pd.loggedIn === 'true';
    window.loginUrl   = _pd.loginUrl;

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('form[action="/cart/add"], form[action="/checkout"]').forEach(function(form) {
            form.addEventListener('submit', function(e) {
                if (!window.isLoggedIn) {
                    e.preventDefault();
                    var pId = form.querySelector('[name="productId"]')?.value || (_pd ? _pd.productId : '');
                    var sz  = form.querySelector('[name="size"]')?.value || '';
                    var qty = parseInt(form.querySelector('[name="quantity"]')?.value || '1', 10);
                    var varLbl = form.querySelector('[name="variation"]')?.value || 'Original';
                    var act = form.getAttribute('action') === '/checkout' ? 'buy_now' : 'add_to_cart';

                    var intent = {
                        action: act,
                        productId: pId,
                        quantity: qty,
                        size: sz,
                        variation: varLbl,
                        redirectUrl: act === 'buy_now' ? '/checkout?mode=buy_now' : window.location.href
                    };
                    try { localStorage.setItem('lumbarong_pending_intent', JSON.stringify(intent)); } catch(err) {}
                    window.location.href = window.loginUrl;
                }
            });
        });
    });

    function productDetail(defaultStock, sizeStocks, variations) {
        var dataEl = document.getElementById('product-page-data');
        var dataset = dataEl ? dataEl.dataset : {};
        var isWishlistedInitial = dataset.isWishlisted === 'true';
        var productId = dataset.productId || '';
        var defaultProductImageUrl = dataset.defaultImageUrl || '';
        var csrfToken = dataset.csrfToken || '';

        return {
            selectedSize: '',
            quantity: 1,
            defaultStock: defaultStock || 1,
            stock: defaultStock || 1,
            sizeStocks: sizeStocks || {},
            activeImage: 0,
            variations: variations || [],
            selectedVariation: 0,
            showSizeGuide: false,
            
            // ─── Shopee-Style Hover Zoom Inspection State ───
            showZoomModal: false,
            zoomOriginX: 50,
            zoomOriginY: 50,
            isZoomed: false,

            openZoomModal(idx) {
                if (idx !== undefined) {
                    this.activeImage = idx;
                    this.selectedVariation = idx;
                }
                this.isZoomed = false;
                this.zoomOriginX = 50;
                this.zoomOriginY = 50;
                this.showZoomModal = true;
                document.body.style.overflow = 'hidden';
            },
            closeZoomModal() {
                this.showZoomModal = false;
                this.isZoomed = false;
                document.body.style.overflow = '';
            },
            handleModalMouseMove(e) {
                const rect = e.currentTarget.getBoundingClientRect();
                const x = ((e.clientX - rect.left) / rect.width) * 100;
                const y = ((e.clientY - rect.top) / rect.height) * 100;
                this.zoomOriginX = Math.min(Math.max(x, 0), 100).toFixed(2);
                this.zoomOriginY = Math.min(Math.max(y, 0), 100).toFixed(2);
                this.isZoomed = true;
            },
            handleModalMouseLeave() {
                this.isZoomed = false;
            },
            handleModalTouch(e) {
                if (e.touches && e.touches.length > 0) {
                    const rect = e.currentTarget.getBoundingClientRect();
                    const touch = e.touches[0];
                    const x = ((touch.clientX - rect.left) / rect.width) * 100;
                    const y = ((touch.clientY - rect.top) / rect.height) * 100;
                    this.zoomOriginX = Math.min(Math.max(x, 0), 100).toFixed(2);
                    this.zoomOriginY = Math.min(Math.max(y, 0), 100).toFixed(2);
                    this.isZoomed = true;
                }
            },
            selectedColorName: 'Off-White',
            isWishlisted: isWishlistedInitial,
            colorSwatches: [
                { name: 'Off-White', hex: '#F9F8F6' },
                { name: 'Ivory', hex: '#EBE4D5' },
                { name: 'Navy Blue', hex: '#1E293B' },
                { name: 'Natural Linen', hex: '#D6C8B4' },
                { name: 'Black', hex: '#18181B' },
                { name: 'Classic Cream', hex: '#F5EAD9' }
            ],
            customMeasurements: {
                neck: '',
                shoulder: '',
                sleeves: '',
                armhole: '',
                fullLength: '',
                chest: '',
                waist: '',
                notes: ''
            },
            effectiveSize: function() {
                if (!this.selectedSize) return '';
                if (this.selectedSize === 'Custom' || this.selectedSize.toLowerCase().includes('custom')) {
                    var parts = [];
                    if (this.customMeasurements.neck) parts.push('Neck: ' + this.customMeasurements.neck);
                    if (this.customMeasurements.shoulder) parts.push('Shoulder: ' + this.customMeasurements.shoulder);
                    if (this.customMeasurements.sleeves) parts.push('Sleeves: ' + this.customMeasurements.sleeves);
                    if (this.customMeasurements.armhole) parts.push('Armhole: ' + this.customMeasurements.armhole);
                    if (this.customMeasurements.fullLength) parts.push('Length: ' + this.customMeasurements.fullLength);
                    if (this.customMeasurements.chest) parts.push('Chest: ' + this.customMeasurements.chest);
                    if (this.customMeasurements.waist) parts.push('Waist: ' + this.customMeasurements.waist);
                    if (this.customMeasurements.notes) parts.push('Notes: ' + this.customMeasurements.notes);
                    return 'Custom (' + (parts.length > 0 ? parts.join(', ') : 'Tailored Sizing') + ')';
                }
                return this.selectedSize;
            },
            toggleWishlist: async function() {
                var hasSizes = Object.keys(this.sizeStocks || {}).length > 0;
                if (hasSizes && !this.selectedSize) {
                    if (window.Alpine && Alpine.store('toast')) {
                        Alpine.store('toast').trigger('Please select your preferred size first before saving to your wishlist.', 'info');
                    } else {
                        alert('Please select your preferred size first before saving to your wishlist.');
                    }
                    return;
                }

                if (!window.isLoggedIn) {
                    const intent = {
                        action: 'wishlist',
                        productId: productId,
                        size: this.selectedSize || null,
                        redirectUrl: window.location.href
                    };
                    try { localStorage.setItem('lumbarong_pending_intent', JSON.stringify(intent)); } catch(err) {}
                    window.location.href = window.loginUrl;
                    return;
                }
                try {
                    var res = await fetch('/wishlist/toggle', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify({ 
                            product_id: productId,
                            size: this.selectedSize || null
                        })
                    });
                    var data = await res.json();
                    this.isWishlisted = data.status === 'added';
                    if (window.Alpine && Alpine.store('toast')) {
                        Alpine.store('toast').trigger(data.message, data.status === 'added' ? 'success' : 'info');
                    }
                } catch(e) {
                    this.isWishlisted = !this.isWishlisted;
                }
            },
            imageUrl: function(url) {
                if (!url) return defaultProductImageUrl;
                if (url.startsWith('http') || url.startsWith('/')) return url;
                if (url.startsWith('products/')) return '/storage/' + url;
                if (url.startsWith('uploads/')) return '/' + url;
                return '/uploads/products/' + url;
            },
            selectedVariationLabel: function() {
                return (this.variations && this.variations[this.selectedVariation]) ? (this.variations[this.selectedVariation].label || 'Original') : 'Original';
            },
            updateStock: function(size) {
                this.selectedSize = size;
                if (this.sizeStocks && this.sizeStocks[size] !== undefined) {
                    this.stock = parseInt(this.sizeStocks[size]) || 0;
                } else {
                    this.stock = this.defaultStock;
                }
                if (this.quantity > this.stock) {
                    this.quantity = Math.max(1, this.stock);
                }
            },
            submitAddToCart: async function(e) {
                if (!window.isLoggedIn) {
                    const intent = {
                        action: 'add_to_cart',
                        productId: '{{ $product->id }}',
                        quantity: this.quantity,
                        size: this.effectiveSize(),
                        variation: this.selectedVariationLabel(),
                        redirectUrl: window.location.href
                    };
                    try { localStorage.setItem('lumbarong_pending_intent', JSON.stringify(intent)); } catch(err) {}
                    window.location.href = window.loginUrl;
                    return;
                }
                try {
                    var formData = new FormData(e.target);
                    var response = await fetch('/cart/add', {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken
                        },
                        body: formData
                    });
                    if (response.ok) {
                        var data = await response.json();
                        if (data.success) {
                            window.dispatchEvent(new CustomEvent('cart-updated', { detail: data }));
                            Alpine.store('toast').trigger('Product successfully added to cart!', 'success');
                        }
                    } else {
                        var errData = await response.json();
                        Alpine.store('toast').trigger(errData.message || 'Failed to add item to cart.', 'error');
                    }
                } catch(err) {
                    Alpine.store('toast').trigger('Something went wrong. Please try again.', 'error');
                }
            },
            chatWithSeller: function(sellerId, sellerName) {
                if (!window.isLoggedIn) {
                    var intent = {
                        action: 'chat',
                        sellerId: sellerId,
                        sellerName: sellerName,
                        redirectUrl: window.location.href
                    };
                    try { localStorage.setItem('lumbarong_pending_intent', JSON.stringify(intent)); } catch(err) {}
                    window.location.href = window.loginUrl;
                    return;
                }
                window.dispatchEvent(new CustomEvent('open-chat', { 
                    detail: { sellerId: sellerId, sellerName: sellerName } 
                }));
            }
        };
    }
</script>
<div class="max-w-6xl mx-auto py-4 lg:py-6" x-data="productDetail({{ (int)($product->stock ?? 1) }}, @js($product->size_stocks ?? (object)[]), @js($productVariations))">
    <!-- Breadcrumb Navigation -->
    <nav class="flex items-center gap-2 text-xs font-semibold text-gray-500 mb-5">
        <a href="/" class="hover:text-black transition-colors">Home</a>
        <span>&gt;</span>
        <a href="/?category={{ urlencode($product->category->name ?? 'Barong Tagalog') }}" class="hover:text-black transition-colors">{{ $product->category->name ?? 'Barong Tagalog' }}</a>
        <span>&gt;</span>
        <span class="text-gray-900 font-bold truncate max-w-62.5 sm:max-w-none">{{ $product->name }}</span>
    </nav>

    <!-- Product Detail Main Container Card -->
    <div class="bg-white rounded-3xl border border-gray-100 shadow-sm overflow-hidden p-6 sm:p-8 lg:p-10">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12 items-start">
            
            <!-- Left Side: Product Images Gallery (Vertical Thumbnails + Main Image) -->
            <div class="lg:col-span-5 flex flex-col-reverse sm:flex-row gap-3 sm:gap-4 items-start">
                
                <!-- Vertical Gallery Thumbnails (Left side) -->
                <div class="flex sm:flex-col gap-3 overflow-x-auto sm:overflow-y-auto max-h-115 no-scrollbar shrink-0 w-full sm:w-20">
                    <template x-for="(variation, index) in variations" :key="index">
                        <button 
                            @click="activeImage = index; selectedVariation = index"
                            class="relative w-14 h-18 sm:w-16 sm:h-20 rounded-xl overflow-hidden shrink-0 border-2 transition-all shadow-2xs"
                            :class="activeImage === index ? 'border-amber-600 ring-2 ring-amber-500/20 opacity-100 scale-98' : 'border-gray-200 opacity-60 hover:opacity-100'"
                        >
                            <img :src="imageUrl(variation.url)" onerror="this.src='/uploads/products/default.jpg'" class="w-full h-full object-cover">
                        </button>
                    </template>
                </div>

                <!-- Main Image Display Box with Direct Click-to-Zoom Inspection -->
                <div 
                    class="flex-1 min-w-0 w-full relative aspect-4/5 bg-gray-50 rounded-2xl overflow-hidden border border-gray-100 shadow-xs group select-none cursor-zoom-in"
                    @click="openZoomModal(activeImage)"
                    title="Click to inspect and zoom"
                >
                    <!-- Main Image Display -->
                    <template x-for="(variation, index) in variations" :key="index">
                        <img 
                            x-show="activeImage === index"
                            :src="imageUrl(variation.url)"
                            onerror="this.src='/uploads/products/default.jpg'"
                            class="w-full h-full object-contain group-hover:scale-105 transition-transform duration-300 ease-out p-1"
                            alt="{{ $product->name }}"
                        >
                    </template>

                    @if($product->is_on_sale && $product->discount_percentage > 0)
                        <div style="position:absolute;top:8px;left:8px;display:flex;flex-direction:column;gap:5px;z-index:10;pointer-events:none;">
                            <div style="display:inline-flex;align-items:center;gap:5px;padding:5px 12px 5px 8px;background:linear-gradient(135deg,#0F0C08 0%,#1C1609 100%);border:1px solid #A87B10;border-radius:20px;box-shadow:0 0 8px rgba(180,130,15,0.45),inset 0 1px 0 rgba(230,185,60,0.12);white-space:nowrap;">
                                <img src="/images/logo-icon.png" alt="LumBarong" style="width:16px;height:16px;border-radius:50%;flex-shrink:0;object-fit:cover;">
                                <span style="color:#DFC97A;font-family:ui-sans-serif,system-ui,sans-serif;font-size:8.5px;font-weight:700;letter-spacing:0.18em;text-transform:uppercase;">Lumban Special</span>
                            </div>
                            <div style="display:inline-flex;align-items:baseline;padding:5px 12px;background:linear-gradient(90deg,#7A5505 0%,#C8890A 25%,#E8AD12 50%,#C8890A 75%,#7A5505 100%);border:1px solid #5C3E04;border-radius:20px;box-shadow:0 2px 10px rgba(200,137,10,0.5),inset 0 1px 0 rgba(255,220,80,0.25);white-space:nowrap;">
                                <span style="color:#FFF8E0;font-family:ui-sans-serif,system-ui,sans-serif;font-size:16px;font-weight:900;line-height:1;letter-spacing:-0.02em;">-{{ number_format($product->discount_percentage, 0) }}%</span>
                                <span style="color:#FFE8A0;font-family:ui-sans-serif,system-ui,sans-serif;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;margin-left:3px;">OFF</span>
                            </div>
                        </div>
                    @endif

                    <!-- Zoom Helper Hint Badge (Bottom Right) -->
                    <div class="absolute bottom-3.5 right-3.5 z-10 px-3 py-1.5 rounded-full bg-white/90 backdrop-blur-md border border-gray-200 shadow-sm flex items-center gap-1.5 text-gray-700 pointer-events-none opacity-85 group-hover:opacity-100 transition-opacity">
                        <svg class="w-3.5 h-3.5 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7"/>
                        </svg>
                        <span class="text-[10px] font-bold tracking-tight">Click to Zoom</span>
                    </div>
                </div>
            </div>

            <!-- Right Side: Details & Selectors -->
            <div class="lg:col-span-7 flex flex-col justify-between space-y-6">
                <div>
                    <!-- Product Title -->
                    <h1 class="font-sans text-2xl sm:text-3xl font-extrabold text-gray-900 mb-1 leading-tight tracking-tight">
                        {{ $product->name }}
                    </h1>

                    <!-- Seller Info & Rating Row -->
                    <div class="flex items-center gap-3 text-xs mb-4">
                        <div class="flex items-center gap-1.5">
                            <span class="text-gray-500 font-medium">by</span>
                            <a href="/shops/{{ $product->sellerId }}" class="font-extrabold text-amber-800 hover:underline flex items-center gap-1.5">
                                <img src="{{ $product->seller->profile_photo_url ?? '/uploads/products/default.jpg' }}" onerror="this.src='/uploads/products/default.jpg'" class="w-5 h-5 rounded-full object-cover border border-gray-200" alt="Artisan">
                                <span>{{ $product->artisan ?? $product->seller->shopName ?? 'BarongniJuan' }}</span>
                            </a>
                        </div>
                        <span class="text-gray-300">•</span>
                        <div class="flex items-center gap-1 font-bold text-gray-700">
                            @if(($product->reviewCount ?? 0) > 0)
                                <span class="text-amber-600 font-extrabold">{{ number_format($product->avgRating, 1) }}</span>
                                <svg class="w-3.5 h-3.5 fill-amber-400 text-amber-400" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                </svg>
                                <span class="text-gray-400 font-normal">({{ $product->reviewCount }} {{ \Illuminate\Support\Str::plural('review', $product->reviewCount) }})</span>
                            @else
                                <span class="text-xs text-gray-400 font-medium">New • No ratings yet</span>
                            @endif
                        </div>
                    </div>

                    <!-- Price Row -->
                    <div class="flex items-baseline gap-3 mb-4">
                        <span class="text-2xl sm:text-3xl font-extrabold text-gray-900">₱{{ number_format($product->salePrice) }}</span>
                        @if($product->is_on_sale && $product->discount_percentage > 0)
                            <span class="text-base font-bold text-gray-400 line-through">₱{{ number_format($product->price) }}</span>
                            <span class="text-xs font-extrabold text-orange-600 uppercase">{{ number_format($product->discount_percentage, 0) }}% OFF</span>
                        @endif
                    </div>

                    <!-- Category Specification -->
                    <div class="space-y-1 text-xs text-gray-600 mb-6">
                        <div><span class="font-bold text-gray-800">Category:</span> {{ $product->category->name ?? 'Wedding Barong' }}</div>
                    </div>

                    <!-- Available Sizes Row -->
                    <div class="mb-6">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xs font-bold text-gray-900">Available Sizes:</span>
                            <button type="button" @click="showSizeGuide = true" onclick="openSizeGuideModal()" class="text-[11px] font-bold text-amber-800 hover:underline cursor-pointer">Size Guide</button>
                        </div>
                        <div class="flex flex-wrap gap-2.5">
                            @php
                                $sizes = is_string($product->sizes) ? json_decode($product->sizes, true) : $product->sizes;
                                if (empty($sizes)) {
                                    $sizes = ['XS', 'S', 'M', 'L', 'XL', 'XXL', 'Custom'];
                                } else {
                                    $hasCustom = false;
                                    foreach($sizes as $sz) {
                                        $name = is_array($sz) ? ($sz['size'] ?? $sz['name'] ?? '') : $sz;
                                        if (strtolower($name) === 'custom') { $hasCustom = true; break; }
                                    }
                                    if (!$hasCustom) { $sizes[] = 'Custom'; }
                                }
                            @endphp
                            @foreach($sizes as $size)
                                @php 
                                    $sizeName = is_array($size) ? ($size['size'] ?? $size['name'] ?? 'N/A') : $size;
                                    $hasSizeStock = true;
                                    if (is_array($product->size_stocks) && isset($product->size_stocks[$sizeName]) && (int)$product->size_stocks[$sizeName] === 0) {
                                        $hasSizeStock = false;
                                    }
                                @endphp
                                <button 
                                    @click="updateStock('{{ $sizeName }}')"
                                    type="button"
                                    class="min-w-11 h-11 px-3 rounded-xl flex items-center justify-center text-xs font-bold border transition-all shadow-2xs"
                                    :class="selectedSize === '{{ $sizeName }}' ? 'border-amber-600 bg-amber-50/80 text-amber-900 ring-2 ring-amber-500/20' : 'border-gray-200 text-gray-700 bg-white hover:border-gray-400'"
                                >
                                    <span class="{{ !$hasSizeStock ? 'text-gray-300 line-through font-normal' : '' }}">{{ $sizeName }}</span>
                                </button>
                            @endforeach
                        </div>

                        {{-- Custom Measurements Input Card --}}
                        <div x-show="selectedSize && (selectedSize === 'Custom' || selectedSize.toLowerCase().includes('custom'))"
                             x-cloak
                             x-transition
                             class="mt-3.5 p-4 bg-[#FDF9F4] border border-[#C0422A]/20 rounded-2xl space-y-3 relative">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-extrabold text-[#C0422A] uppercase tracking-wider">✂️ Tailored Custom Sizing</span>
                                <button type="button" 
                                        @click="selectedSize = null" 
                                        class="w-6 h-6 rounded-full bg-white border border-gray-200 hover:border-gray-400 hover:text-black text-gray-400 flex items-center justify-center transition-all cursor-pointer shadow-2xs active:scale-95"
                                        title="Close custom sizing">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            </div>
                            <p class="text-[11px] text-gray-500 font-medium">Input your body measurements in inches (in) or centimetres (cm):</p>
                            
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5 text-xs">
                                <div>
                                    <label class="font-bold text-gray-700 block mb-1">Neck</label>
                                    <input type="text" x-model="customMeasurements.neck" placeholder="e.g. 15.5 in / 39 cm" class="w-full h-8.5 px-3 bg-white border border-gray-200 rounded-xl text-xs outline-none focus:border-[#C0422A] transition-colors">
                                </div>
                                <div>
                                    <label class="font-bold text-gray-700 block mb-1">Shoulder</label>
                                    <input type="text" x-model="customMeasurements.shoulder" placeholder="e.g. 17.5 in / 44 cm" class="w-full h-8.5 px-3 bg-white border border-gray-200 rounded-xl text-xs outline-none focus:border-[#C0422A] transition-colors">
                                </div>
                                <div>
                                    <label class="font-bold text-gray-700 block mb-1">Sleeves</label>
                                    <input type="text" x-model="customMeasurements.sleeves" placeholder="e.g. 24 in / 60 cm" class="w-full h-8.5 px-3 bg-white border border-gray-200 rounded-xl text-xs outline-none focus:border-[#C0422A] transition-colors">
                                </div>
                                <div>
                                    <label class="font-bold text-gray-700 block mb-1">Armhole</label>
                                    <input type="text" x-model="customMeasurements.armhole" placeholder="e.g. 19 in / 48 cm" class="w-full h-8.5 px-3 bg-white border border-gray-200 rounded-xl text-xs outline-none focus:border-[#C0422A] transition-colors">
                                </div>
                                <div>
                                    <label class="font-bold text-gray-700 block mb-1">Length</label>
                                    <input type="text" x-model="customMeasurements.fullLength" placeholder="e.g. 29 in / 74 cm" class="w-full h-8.5 px-3 bg-white border border-gray-200 rounded-xl text-xs outline-none focus:border-[#C0422A] transition-colors">
                                </div>
                                <div>
                                    <label class="font-bold text-gray-700 block mb-1">Chest</label>
                                    <input type="text" x-model="customMeasurements.chest" placeholder="e.g. 38 in / 96 cm" class="w-full h-8.5 px-3 bg-white border border-gray-200 rounded-xl text-xs outline-none focus:border-[#C0422A] transition-colors">
                                </div>
                                <div>
                                    <label class="font-bold text-gray-700 block mb-1">Waist</label>
                                    <input type="text" x-model="customMeasurements.waist" placeholder="e.g. 32 in / 81 cm" class="w-full h-8.5 px-3 bg-white border border-gray-200 rounded-xl text-xs outline-none focus:border-[#C0422A] transition-colors">
                                </div>
                            </div>
                            <div>
                                <label class="font-bold text-gray-700 block mb-1 text-xs">Special Sizing Notes (Optional)</label>
                                <input type="text" x-model="customMeasurements.notes" placeholder="e.g. Loose fit for wedding ceremony" class="w-full h-8.5 px-3 bg-white border border-gray-200 rounded-xl text-xs outline-none focus:border-[#C0422A] transition-colors">
                            </div>
                        </div>
                    </div>

                    <!-- Quantity Stepper -->
                    <div class="flex items-center gap-4 mb-6">
                        <span class="text-xs font-bold text-gray-900">Quantity:</span>
                        <div class="flex items-center border border-gray-200 rounded-xl bg-gray-50 overflow-hidden shadow-2xs h-10">
                            <button 
                                @click="if(quantity > 1) quantity--" 
                                type="button" 
                                class="w-9 h-full flex items-center justify-center text-gray-600 hover:text-black font-bold text-base hover:bg-gray-100 transition-colors"
                            >
                                −
                            </button>
                            <input 
                                type="number" 
                                x-model.number="quantity" 
                                min="1" 
                                :max="stock"
                                class="w-10 text-center bg-transparent border-0 outline-none text-xs font-bold text-gray-900"
                            >
                            <button 
                                @click="if(quantity < stock) quantity++" 
                                type="button" 
                                class="w-9 h-full flex items-center justify-center text-gray-600 hover:text-black font-bold text-base hover:bg-gray-100 transition-colors"
                            >
                                +
                            </button>
                        </div>
                        <span class="text-xs font-semibold" :class="stock > 0 ? 'text-gray-400' : 'text-red-500 font-bold'">
                            (<span x-text="stock > 0 ? stock + ' pieces available' : 'Out of Stock'"></span>)
                        </span>
                    </div>

                    <!-- Action Buttons -->
                    <form action="/cart/add" method="POST" @submit.prevent="submitAddToCart($event)" class="space-y-3">
                        @csrf
                        <input type="hidden" name="productId" value="{{ $product->id }}">
                        <input type="hidden" name="size" :value="effectiveSize()">
                        <input type="hidden" name="quantity" :value="quantity">
                        <input type="hidden" name="variation" :value="selectedVariationLabel()">

                        {{-- WHEN IN STOCK (stock > 0) --}}
                        <div x-show="stock > 0" class="space-y-3">
                            <div class="flex items-center gap-3">
                                <button 
                                    type="submit"
                                    :disabled="!selectedSize"
                                    class="flex-1 h-12 rounded-xl bg-black hover:bg-gray-900 text-white font-bold text-xs flex items-center justify-center gap-2 transition-colors shadow-md disabled:opacity-50 cursor-pointer"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                                    </svg>
                                    <span x-text="!selectedSize ? 'Select Size' : 'Add to Cart'">Add to Cart</span>
                                </button>

                                <button 
                                    type="button" 
                                    @click="
                                        if (selectedSize && stock > 0) {
                                            if (!window.isLoggedIn) {
                                                const intent = {
                                                    action: 'buy_now',
                                                    productId: '{{ $product->id }}',
                                                    quantity: quantity,
                                                    size: effectiveSize(),
                                                    variation: selectedVariationLabel(),
                                                    redirectUrl: '/checkout?mode=buy_now'
                                                };
                                                try { localStorage.setItem('lumbarong_pending_intent', JSON.stringify(intent)); } catch(err) {}
                                                window.location.href = window.loginUrl;
                                                return;
                                            }
                                            window.location.href = '/checkout?productId={{ $product->id }}&size=' + effectiveSize() + '&quantity=' + quantity + '&direct=1';
                                        }
                                    "
                                    :disabled="!selectedSize"
                                    class="flex-1 h-12 rounded-xl bg-[#C89B55] hover:bg-[#B88B45] text-white font-extrabold text-xs tracking-wide shadow-md transition-colors disabled:opacity-50 cursor-pointer flex items-center justify-center gap-1.5"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                    <span x-text="!selectedSize ? 'Select Size' : 'Buy Now'">Buy Now</span>
                                </button>
                            </div>
                        </div>

                        {{-- WHEN OUT OF STOCK (stock <= 0) - Wishlist Button appears here like Lazada --}}
                        <div x-show="stock <= 0" class="space-y-3" x-cloak style="display: none;">
                            <div class="p-3 bg-red-50/80 border border-red-200 rounded-xl flex items-center justify-between text-xs">
                                <div class="flex items-center gap-2 text-red-700 font-bold">
                                    <svg class="w-4 h-4 text-red-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                    <span>This item is currently out of stock.</span>
                                </div>
                                <span class="text-[10px] uppercase tracking-wider font-extrabold text-red-800 bg-red-100 px-2 py-0.5 rounded">Sold Out</span>
                            </div>

                            <button 
                                type="button" 
                                @click="toggleWishlist()" 
                                class="w-full h-12 rounded-xl border font-bold text-xs flex items-center justify-center gap-2 transition-all shadow-sm cursor-pointer"
                                :class="isWishlisted ? 'text-red-600 border-red-200 bg-red-50 hover:bg-red-100/70' : 'text-gray-900 border-gray-300 bg-white hover:bg-gray-50'"
                            >
                                <svg class="w-4 h-4" :class="isWishlisted ? 'fill-red-500 text-red-500' : 'fill-none stroke-current'" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                                </svg>
                                <span x-text="isWishlisted ? '❤️ Saved in Your Wishlist' : '♡ Add to Wishlist (Save for later)'"></span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Bottom Delivery Feature Bar (Realtime Seller Info) -->
        @php
            $minDays = (int)($product->shippingDays ?? 3);
            $maxDays = $minDays + 2;
            $locationParts = array_filter([$product->seller->shopCity ?? null, $product->seller->shopProvince ?? null]);
            $shipsFrom = !empty($locationParts) ? implode(', ', $locationParts) : ($product->seller->shopAddress ?? $product->artisan_region ?? 'Lumban, Laguna');
        @endphp
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-8 mt-8 border-t border-gray-100 text-xs">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-gray-50 flex items-center justify-center text-gray-700 shrink-0 border border-gray-100">
                    🚚
                </div>
                <div>
                    <div class="font-medium text-gray-500">Shipping Fee</div>
                    <div class="font-extrabold text-gray-900">{{ ($product->shippingFee ?? 0) > 0 ? '₱' . number_format($product->shippingFee, 2) : 'Free Shipping' }}</div>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-gray-50 flex items-center justify-center text-gray-700 shrink-0 border border-gray-100">
                    📦
                </div>
                <div>
                    <div class="font-medium text-gray-500">Estimated Delivery</div>
                    <div class="font-extrabold text-gray-900">{{ $minDays }} - {{ $maxDays }} days</div>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-gray-50 flex items-center justify-center text-gray-700 shrink-0 border border-gray-100">
                    📍
                </div>
                <div>
                    <div class="font-medium text-gray-500">Ships from</div>
                    <div class="font-extrabold text-gray-900">{{ $shipsFrom }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- ─── Simple Standalone Hover-Zoom Modal ─── --}}
    <div 
        x-show="showZoomModal" 
        x-cloak
        style="display: none; z-index: 99999;"
        class="fixed inset-0 flex items-center justify-center p-3 sm:p-6 bg-black/65 backdrop-blur-xs select-none"
        @keydown.window.escape="closeZoomModal()"
        @click.self="closeZoomModal()"
    >
        <div class="relative w-full max-w-2xl bg-black rounded-2xl shadow-2xl overflow-hidden border border-neutral-800 flex flex-col" style="background-color: #000000;">
            
            <!-- Close Button (Top Right of Modal) -->
            <button 
                type="button" 
                @click="closeZoomModal()"
                class="absolute top-3 right-3 z-30 w-8 h-8 rounded-full bg-black/70 hover:bg-black text-white shadow-lg flex items-center justify-center transition-all cursor-pointer border border-white/20"
                title="Close (Esc)"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>

            <!-- Large Zoom Viewer -->
            <div 
                class="relative w-full aspect-square sm:aspect-4/5 bg-black overflow-hidden cursor-crosshair min-h-87.5 sm:min-h-115"
                style="background-color: #000000;"
                @mousemove="handleModalMouseMove($event)"
                @mouseleave="handleModalMouseLeave()"
                @touchstart="handleModalTouch($event)"
                @touchmove.prevent="handleModalTouch($event)"
                @touchend="handleModalMouseLeave()"
            >
                <template x-for="(variation, index) in variations" :key="index">
                    <img 
                        x-show="activeImage === index"
                        :src="imageUrl(variation.url)"
                        onerror="this.src='/uploads/products/default.jpg'"
                        class="w-full h-full object-contain pointer-events-none transition-transform duration-75 ease-out"
                        :class="isZoomed ? 'scale-[2.4]' : 'scale-100'"
                        :style="isZoomed ? { transformOrigin: `${zoomOriginX}% ${zoomOriginY}%` } : {}"
                        alt="{{ $product->name }}"
                    >
                </template>

                <!-- Subtle Hover Zoom Helper -->
                <div 
                    x-show="!isZoomed" 
                    class="absolute bottom-3 left-3 px-2.5 py-1 rounded-md bg-black/75 text-white text-[11px] font-medium pointer-events-none flex items-center gap-1.5 border border-white/10"
                >
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7"/></svg>
                    <span>Move mouse to zoom</span>
                </div>
            </div>

            <!-- Bottom Thumbnails Strip (Solid Black Footer) -->
            <div 
                class="p-3.5 bg-black border-t border-neutral-800 flex items-center justify-center gap-2 overflow-x-auto no-scrollbar" 
                style="background-color: #000000 !important; background: #000000 !important;" 
                x-show="variations && variations.length > 1"
            >
                <template x-for="(variation, index) in variations" :key="index">
                    <button 
                        type="button"
                        @click="activeImage = index; selectedVariation = index"
                        class="w-12 h-14 rounded-lg overflow-hidden border-2 transition-all cursor-pointer shrink-0 bg-neutral-900 shadow-md"
                        :class="activeImage === index ? 'border-[#C0420A] ring-2 ring-[#C0420A] scale-105' : 'border-neutral-700 opacity-60 hover:opacity-100'"
                    >
                        <img :src="imageUrl(variation.url)" class="w-full h-full object-cover">
                    </button>
                </template>
            </div>
        </div>
    </div>

        <!-- Lower Section: Description & Info -->
        <div class="mt-16 pt-10 border-t border-gray-100 grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-16">
            <div class="lg:col-span-5">
                <h3 class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-4">Artisan's Story</h3>
                <div class="flex items-center gap-4">
                    <div class="w-14 h-14 bg-gray-50 rounded-2xl flex items-center justify-center text-xl font-bold text-gray-300 border border-gray-100 shadow-sm shrink-0 relative overflow-hidden">
                        @if($product->seller && $product->seller->profile_photo_url)
                            <img src="{{ $product->seller->profile_photo_url }}" class="w-full h-full object-cover" onerror="this.src='/uploads/products/default.jpg'">
                        @else
                            <img src="{{ asset('uploads/products/default.jpg') }}" class="w-full h-full object-cover" alt="Artisan">
                        @endif
                        @if($product->seller && $product->seller->isPremiumActive())
                            <span class="absolute -top-1 -right-1 text-sm bg-yellow-400 border border-white rounded-full w-5 h-5 flex items-center justify-center shadow-xs z-10">👑</span>
                        @endif
                    </div>
                    <div>
                        <div class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-0.5">Artisan</div>
                        <div class="text-sm font-bold text-black flex items-center gap-1.5">
                            {{ $product->artisan ?? 'Lumban Master Craft' }}
                            @if($product->seller && $product->seller->isPremiumActive())
                                <span class="px-2 py-0.5 bg-yellow-100 border border-yellow-200 text-yellow-700 text-[8px] font-black uppercase tracking-wider rounded-full">👑 Premium</span>
                            @endif
                        </div>
                        
                        <div class="mt-2 flex items-center gap-2">
                            <a href="/shops/{{ $product->sellerId }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-stone-100 hover:bg-[#C0420A] text-[9px] font-black uppercase tracking-widest text-stone-700 hover:text-white rounded-lg border border-stone-200/60 transition-all shadow-sm">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg>
                                View Shop
                            </a>
                            <button 
                                type="button" 
                                @click="chatWithSeller('{{ $product->sellerId }}', '{{ e($product->seller->shopName ?? $product->seller->name ?? 'Artisan') }}')"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-amber-50 hover:bg-[#C0422A] text-[9px] font-black uppercase tracking-widest text-amber-900 hover:text-white rounded-lg border border-amber-200/60 transition-all shadow-sm"
                            >
                                💬 Chat with Seller
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="lg:col-span-7">
                <h3 class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-3">Product Details</h3>
                <p class="text-gray-600 text-sm leading-relaxed whitespace-pre-line">
                    {{ $product->description }}
                </p>
            </div>
        </div>

        <!-- Reviews Section -->
        @php
            $reviewsList = ($product->reviews ?? collect())->map(function($rev) {
                $customerName = $rev->customer->name ?? 'customer';
                $initial = strtoupper(substr($customerName, 0, 1));
                $photo = $rev->customer ? $rev->customer->profile_photo_url : null;
                $images = is_string($rev->images) ? json_decode($rev->images, true) : $rev->images;
                $images = is_array($images) ? array_values(array_filter($images)) : [];
                $images = array_map(function($img) {
                    if (!$img) return null;
                    if (str_starts_with($img, 'http://') || str_starts_with($img, 'https://')) return $img;
                    if (str_starts_with($img, '/')) return asset(ltrim($img, '/'));
                    if (str_starts_with($img, 'uploads/')) return asset($img);
                    if (str_starts_with($img, 'storage/')) return asset($img);
                    if (str_starts_with($img, 'reviews/')) return asset('storage/' . $img);
                    return asset('uploads/reviews/' . $img);
                }, $images);
                $images = array_values(array_filter($images));

                $videoUrl = null;
                if ($rev->video) {
                    if (str_starts_with($rev->video, 'http://') || str_starts_with($rev->video, 'https://')) {
                        $videoUrl = $rev->video;
                    } elseif (str_starts_with($rev->video, '/')) {
                        $videoUrl = asset(ltrim($rev->video, '/'));
                    } elseif (str_starts_with($rev->video, 'uploads/') || str_starts_with($rev->video, 'storage/')) {
                        $videoUrl = asset($rev->video);
                    } else {
                        $videoUrl = asset('storage/' . $rev->video);
                    }
                }

                return [
                    'id' => $rev->id,
                    'rating' => (int)$rev->rating,
                    'comment' => $rev->comment,
                    'seller_reply' => $rev->seller_reply,
                    'seller_reply_date' => $rev->seller_reply_at ? ($rev->seller_reply_at instanceof \Carbon\Carbon ? $rev->seller_reply_at->format('M d, Y') : \Carbon\Carbon::parse($rev->seller_reply_at)->format('M d, Y')) : null,
                    'seller_name' => $product->seller->name ?? 'Artisan Store',
                    'date' => $rev->createdAt ? ($rev->createdAt instanceof \Carbon\Carbon ? $rev->createdAt->format('F d, Y') : \Carbon\Carbon::parse($rev->createdAt)->format('F d, Y')) : '',
                    'customerName' => $customerName,
                    'initial' => $initial,
                    'customerPhoto' => $photo,
                    'images' => $images,
                    'video' => $videoUrl,
                    'verified' => (bool)($rev->orderId || $rev->orderItemId),
                ];
            })->values();
        @endphp

        <div class="mt-16 pt-10 border-t border-gray-100"
             x-data="{
                 allReviews: {{ json_encode($reviewsList) }},
                 reviewsModal: false,
                 activeFilter: 'all',
                 currentPage: 1,
                 perPage: 5,
                 lightboxModal: false,
                 lightboxType: 'image',
                 lightboxUrl: '',
                 openLightbox(type, url) {
                     if (!url) return;
                     this.lightboxType = type;
                     this.lightboxUrl = url;
                     this.lightboxModal = true;
                 },
                 closeLightbox() {
                     this.lightboxModal = false;
                     if (this.$refs.lightboxVideo) {
                         try { this.$refs.lightboxVideo.pause(); } catch(e) {}
                     }
                     this.lightboxUrl = '';
                 },
                 get filteredReviews() {
                     if (this.activeFilter === 'all') return this.allReviews;
                     if (this.activeFilter === 'media') {
                         return this.allReviews.filter(r => (r.images && r.images.length > 0) || r.video);
                     }
                     const star = parseInt(this.activeFilter, 10);
                     return this.allReviews.filter(r => r.rating === star);
                 },
                 get totalPages() {
                     return Math.max(1, Math.ceil(this.filteredReviews.length / this.perPage));
                 },
                 get paginatedReviews() {
                     const start = (this.currentPage - 1) * this.perPage;
                     return this.filteredReviews.slice(start, start + this.perPage);
                 },
                 setFilter(f) {
                     this.activeFilter = f;
                     this.currentPage = 1;
                 },
                 countFilter(f) {
                     if (f === 'all') return this.allReviews.length;
                     if (f === 'media') return this.allReviews.filter(r => (r.images && r.images.length > 0) || r.video).length;
                     const star = parseInt(f, 10);
                     return this.allReviews.filter(r => r.rating === star).length;
                 }
             }"
             @keydown.escape.window="closeLightbox()">

            @php
                $totalRevCount = $product->reviews->count();
                $starBreakdown = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
                foreach($product->reviews as $r) {
                    $s = (int)$r->rating;
                    if(isset($starBreakdown[$s])) $starBreakdown[$s]++;
                }
            @endphp

            {{-- Reviews Section Header & Summary Badge (Matching Mockup) --}}
            <div class="flex items-center gap-6 sm:gap-10 mb-8 flex-wrap">
                <div>
                    <div class="flex items-center gap-1.5 mb-1">
                        <span class="text-[#C0420A] font-bold text-xs">—</span>
                        <span class="text-[10px] font-black text-[#C0420A] uppercase tracking-widest">Reviews & Feedback</span>
                    </div>
                    <h2 class="font-serif text-2xl sm:text-3xl font-bold text-black">Customer Reviews</h2>
                </div>

                @if($product->reviews->isNotEmpty())
                    <div class="rounded-2xl px-6 py-3.5 flex items-center gap-4 shadow-xs" style="background-color: #FAF9F6; border: 1px solid #EFEAE2;">
                        <div class="text-center pr-4 border-r" style="border-color: #E5DEC3;">
                            <span class="text-3xl font-black text-[#C0420A] leading-none">{{ number_format($product->avgRating, 1) }}</span>
                            <span class="text-[9px] text-gray-400 font-bold block mt-0.5">out of 5</span>
                        </div>
                        <div>
                            <div class="flex items-center gap-0.5 text-amber-400 text-sm">
                                @for($i = 1; $i <= 5; $i++)
                                    <span>{{ $i <= round($product->avgRating) ? '★' : '☆' }}</span>
                                @endfor
                            </div>
                            <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mt-0.5 block">
                                {{ $totalRevCount }} {{ $totalRevCount === 1 ? 'REVIEW' : 'REVIEWS' }}
                            </span>
                        </div>
                    </div>
                @endif
            </div>

            @if($product->reviews->isNotEmpty())
                {{-- Rating Distribution Breakdown (Matching Mockup) --}}
                <div class="rounded-3xl p-6 sm:p-7 mb-8 max-w-xl shadow-xs space-y-3" style="background-color: #FAF9F6; border: 1px solid #EFEAE2;">
                    <span class="text-[10px] font-black uppercase tracking-widest text-gray-400 block mb-3">Rating Distribution</span>
                    @for($star = 5; $star >= 1; $star--)
                        @php
                            $starCount = $starBreakdown[$star] ?? 0;
                            $starPct = $totalRevCount > 0 ? round(($starCount / $totalRevCount) * 100) : 0;
                        @endphp
                        <div class="flex items-center gap-3 text-xs">
                            <span class="w-8 font-bold text-gray-600 text-right">{{ $star }} ★</span>
                            <div class="flex-1 h-3 rounded-full overflow-hidden bg-[#ECE7DE] min-h-2.5">
                                <div class="h-full rounded-full bg-amber-500 min-h-2.5" :style="'width: ' + {{ (int)$starPct }} + '%'"></div>
                            </div>
                            <span class="w-8 text-[11px] font-bold text-gray-400 text-right">{{ $starCount }}</span>
                        </div>
                    @endfor
                </div>

                {{-- Stacked Single-Column Customer Reviews List (Matching Mockup) --}}
                <div class="space-y-4">
                    @foreach($product->reviews->take(3) as $review)
                        <div class="rounded-2xl p-5 sm:p-6 space-y-3 shadow-xs" style="background-color: #FAF9F6; border: 1px solid #EFEAE2;">
                            <div class="flex items-center justify-between flex-wrap gap-2">
                                <div class="flex items-center gap-3">
                                    <div class="w-11 h-11 rounded-full flex items-center justify-center font-bold text-gray-700 text-sm overflow-hidden shrink-0" style="background-color: #EAE8E4;">
                                        @if($review->customer && $review->customer->profile_photo_url)
                                            <img src="{{ $review->customer->profile_photo_url }}" class="w-full h-full object-cover" onerror="this.style.display='none'">
                                        @else
                                            {{ strtoupper(substr($review->customer->name ?? 'C', 0, 1)) }}
                                        @endif
                                    </div>
                                    <div>
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <span class="text-sm font-bold text-black">{{ $review->customer->name ?? 'Customer' }}</span>
                                            <div class="flex items-center gap-0.5 text-amber-400 text-xs">
                                                @for($i = 1; $i <= 5; $i++)
                                                    <span>{{ $i <= $review->rating ? '★' : '☆' }}</span>
                                                @endfor
                                            </div>
                                        </div>
                                        <div class="text-[10px] text-gray-400 font-bold uppercase tracking-widest mt-0.5">
                                            {{ $review->createdAt ? $review->createdAt->format('F d, Y') : '' }}
                                        </div>
                                    </div>
                                </div>

                                @if($review->orderId || $review->orderItemId)
                                    <span class="px-3 py-1 rounded-full text-[9px] font-black uppercase tracking-widest flex items-center gap-1" style="background-color: #E8F8F0; color: #1E9E65; border: 1px solid #D0F0E0;">
                                        ✓ Verified Purchase
                                    </span>
                                @endif
                            </div>

                            @if($review->comment)
                                <p class="text-xs sm:text-sm text-gray-700 italic leading-relaxed">
                                    "{{ $review->comment }}"
                                </p>
                            @endif

                            {{-- Review Media (Photos up to 3 & Video) --}}
                            @php
                                $revImages = $review->images_list;
                                $revVideo = $review->video_url;
                            @endphp
                            @if(!empty($revImages) || !empty($revVideo))
                                <div class="flex flex-wrap items-center gap-2.5 pt-1">
                                    {{-- Photos --}}
                                    @foreach($revImages as $rImgUrl)
                                        @if($rImgUrl)
                                            <button type="button" 
                                                    @click="openLightbox('image', '{{ $rImgUrl }}')" 
                                                    class="w-16 h-16 sm:w-20 sm:h-20 rounded-2xl overflow-hidden bg-white shrink-0 hover:scale-105 transition-all shadow-2xs border border-[#EAE6DF] relative group cursor-pointer">
                                                <img src="{{ $rImgUrl }}" class="w-full h-full object-cover" onerror="this.style.display='none'" alt="Customer Review Photo">
                                                <div class="absolute inset-0 bg-black/20 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center text-white text-xs">
                                                    🔍
                                                </div>
                                            </button>
                                        @endif
                                    @endforeach

                                    {{-- Video --}}
                                    @if($revVideo)
                                        <button type="button" 
                                                @click="openLightbox('video', '{{ $revVideo }}')" 
                                                class="w-16 h-16 sm:w-20 sm:h-20 rounded-2xl overflow-hidden bg-black shrink-0 hover:scale-105 transition-all shadow-2xs border border-gray-800 relative group cursor-pointer flex items-center justify-center">
                                            <video src="{{ $revVideo }}" class="w-full h-full object-cover opacity-70" preload="metadata"></video>
                                            <div class="absolute inset-0 bg-black/25 flex items-center justify-center">
                                                <div class="w-7 h-7 rounded-full bg-white/90 text-black flex items-center justify-center text-[10px] font-black pl-0.5 shadow-sm group-hover:scale-110 transition-transform">
                                                    ▶
                                                </div>
                                            </div>
                                            <span class="absolute bottom-1 right-1 bg-black/80 text-[8px] font-bold text-white px-1.5 py-0.2 rounded">VIDEO</span>
                                        </button>
                                    @endif
                                </div>
                            @endif

                            {{-- Seller Response (Shopee/Lazada Style) --}}
                            @if(!empty($review->seller_reply))
                                <div class="mt-3 p-3.5 sm:p-4 bg-[#FAF9F5] rounded-2xl border-l-4 border-[#C0420A] space-y-1.5 shadow-2xs">
                                    <div class="flex items-center justify-between gap-2">
                                        <span class="text-[10px] font-black uppercase tracking-wider text-[#C0420A] flex items-center gap-1.5">
                                            <span>💬 Seller's Response</span>
                                            <span class="text-gray-400 font-medium">• {{ $product->seller->name ?? 'Artisan Store' }}</span>
                                        </span>
                                        @if($review->seller_reply_at)
                                            <span class="text-[9px] text-gray-400 font-medium">{{ \Carbon\Carbon::parse($review->seller_reply_at)->diffForHumans() }}</span>
                                        @endif
                                    </div>
                                    <p class="text-xs text-gray-700 leading-relaxed font-normal">{{ $review->seller_reply }}</p>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>

                {{-- View All Reviews Button (Matching Mockup) --}}
                <div class="mt-6">
                    <button type="button" 
                            @click="reviewsModal = true"
                            class="w-full py-4 px-6 rounded-2xl text-xs font-black uppercase tracking-widest transition-all shadow-xs flex items-center justify-center gap-2 cursor-pointer group active:scale-[0.99] hover:bg-[#C0420A] hover:text-white"
                            style="background-color: #FAF9F6; border: 1px solid #E88058; color: #C0420A;">
                        <span>VIEW ALL REVIEWS ({{ $totalRevCount }})</span>
                        <span class="group-hover:translate-y-0.5 transition-transform text-sm font-bold">⌄</span>
                    </button>
                </div>

            @else
                <div class="text-center py-12 bg-gray-50/50 rounded-2xl border border-gray-100">
                    <p class="text-xs text-gray-400 font-bold uppercase tracking-widest">No reviews yet for this heritage piece.</p>
                    <p class="text-[10px] text-gray-400 mt-1">Purchased items can be rated once they are received.</p>
                </div>
            @endif

            {{-- Shopee/Lazada Style All Reviews Modal with Filter Tabs & Pagination --}}
            <div x-show="reviewsModal" class="fixed inset-0 z-9999 flex items-center justify-center p-3 sm:p-6 bg-black/60 backdrop-blur-sm" x-cloak style="display: none;">
                <div @click.away="reviewsModal = false" class="bg-white rounded-3xl shadow-2xl w-full max-w-3xl max-h-[85vh] flex flex-col overflow-hidden animate-in fade-in zoom-in-95 duration-200 border border-gray-100">
                    
                    {{-- Modal Header --}}
                    <div class="px-5 sm:px-6 py-4 sm:py-5 border-b border-gray-100 flex items-center justify-between bg-white shrink-0">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-2xl bg-amber-50 border border-amber-100 flex items-center justify-center text-amber-500 font-black text-sm shadow-xs shrink-0">
                                ★
                            </div>
                            <div>
                                <h3 class="font-serif text-base sm:text-lg font-bold text-gray-900">Customer Reviews</h3>
                                <p class="text-xs text-gray-500 font-medium mt-0.5">
                                    <span class="font-bold text-amber-500">★ {{ number_format($product->avgRating, 1) }}</span> out of 5 • <span class="font-semibold text-gray-700" x-text="allReviews.length + ' Total ' + (allReviews.length === 1 ? 'Review' : 'Reviews')"></span>
                                </p>
                            </div>
                        </div>
                        <button type="button" @click="reviewsModal = false" class="w-8 h-8 rounded-full border border-gray-200 bg-gray-50 hover:bg-gray-100 flex items-center justify-center text-gray-400 hover:text-gray-900 transition-all cursor-pointer">
                            ✕
                        </button>
                    </div>

                    {{-- Shopee/Lazada Style Filter Tabs --}}
                    <div class="px-4 sm:px-6 py-3 bg-gray-50/70 border-b border-gray-100 flex items-center gap-1.5 sm:gap-2 overflow-x-auto no-scrollbar shrink-0">
                        <button type="button" 
                            @click="setFilter('all')"
                            :class="activeFilter === 'all' ? 'bg-[#C0420A] text-white shadow-xs font-bold' : 'bg-white text-gray-700 border border-gray-200 hover:border-gray-300 hover:bg-gray-50 font-medium'"
                            class="px-3 py-1.5 sm:px-3.5 sm:py-1.5 rounded-full text-[11px] sm:text-xs transition-all whitespace-nowrap cursor-pointer flex items-center gap-1 shrink-0">
                            <span>All</span>
                            <span class="opacity-80" x-text="'(' + countFilter('all') + ')'"></span>
                        </button>
                        <button type="button" 
                            @click="setFilter('5')"
                            :class="activeFilter === '5' ? 'bg-[#C0420A] text-white shadow-xs font-bold' : 'bg-white text-gray-700 border border-gray-200 hover:border-gray-300 hover:bg-gray-50 font-medium'"
                            class="px-3 py-1.5 sm:px-3.5 sm:py-1.5 rounded-full text-[11px] sm:text-xs transition-all whitespace-nowrap cursor-pointer flex items-center gap-1 shrink-0">
                            <span>5 Star</span>
                            <span class="opacity-80" x-text="'(' + countFilter('5') + ')'"></span>
                        </button>
                        <button type="button" 
                            @click="setFilter('4')"
                            :class="activeFilter === '4' ? 'bg-[#C0420A] text-white shadow-xs font-bold' : 'bg-white text-gray-700 border border-gray-200 hover:border-gray-300 hover:bg-gray-50 font-medium'"
                            class="px-3 py-1.5 sm:px-3.5 sm:py-1.5 rounded-full text-[11px] sm:text-xs transition-all whitespace-nowrap cursor-pointer flex items-center gap-1 shrink-0">
                            <span>4 Star</span>
                            <span class="opacity-80" x-text="'(' + countFilter('4') + ')'"></span>
                        </button>
                        <button type="button" 
                            @click="setFilter('3')"
                            :class="activeFilter === '3' ? 'bg-[#C0420A] text-white shadow-xs font-bold' : 'bg-white text-gray-700 border border-gray-200 hover:border-gray-300 hover:bg-gray-50 font-medium'"
                            class="px-3 py-1.5 sm:px-3.5 sm:py-1.5 rounded-full text-[11px] sm:text-xs transition-all whitespace-nowrap cursor-pointer flex items-center gap-1 shrink-0">
                            <span>3 Star</span>
                            <span class="opacity-80" x-text="'(' + countFilter('3') + ')'"></span>
                        </button>
                        <button type="button" 
                            @click="setFilter('2')"
                            :class="activeFilter === '2' ? 'bg-[#C0420A] text-white shadow-xs font-bold' : 'bg-white text-gray-700 border border-gray-200 hover:border-gray-300 hover:bg-gray-50 font-medium'"
                            class="px-3 py-1.5 sm:px-3.5 sm:py-1.5 rounded-full text-[11px] sm:text-xs transition-all whitespace-nowrap cursor-pointer flex items-center gap-1 shrink-0">
                            <span>2 Star</span>
                            <span class="opacity-80" x-text="'(' + countFilter('2') + ')'"></span>
                        </button>
                        <button type="button" 
                            @click="setFilter('1')"
                            :class="activeFilter === '1' ? 'bg-[#C0420A] text-white shadow-xs font-bold' : 'bg-white text-gray-700 border border-gray-200 hover:border-gray-300 hover:bg-gray-50 font-medium'"
                            class="px-3 py-1.5 sm:px-3.5 sm:py-1.5 rounded-full text-[11px] sm:text-xs transition-all whitespace-nowrap cursor-pointer flex items-center gap-1 shrink-0">
                            <span>1 Star</span>
                            <span class="opacity-80" x-text="'(' + countFilter('1') + ')'"></span>
                        </button>
                        <button type="button" 
                            @click="setFilter('media')"
                            :class="activeFilter === 'media' ? 'bg-[#C0420A] text-white shadow-xs font-bold' : 'bg-white text-gray-700 border border-gray-200 hover:border-gray-300 hover:bg-gray-50 font-medium'"
                            class="px-3.5 py-1.5 sm:px-4 sm:py-1.5 rounded-full text-[11px] sm:text-xs transition-all whitespace-nowrap cursor-pointer flex items-center gap-1 shrink-0">
                            <span>📷 With Media</span>
                            <span class="opacity-80" x-text="'(' + countFilter('media') + ')'"></span>
                        </button>
                    </div>

                    {{-- Reviews Content (Scrollable) --}}
                    <div x-ref="reviewsContainer" class="flex-1 overflow-y-auto p-4 sm:p-6 space-y-3.5 bg-gray-50/40">
                        <template x-if="filteredReviews.length === 0">
                            <div class="text-center py-12 text-gray-400 space-y-2">
                                <div class="text-3xl">💬</div>
                                <p class="text-xs font-bold uppercase tracking-widest">No reviews found under this filter.</p>
                            </div>
                        </template>

                        <template x-for="review in paginatedReviews" :key="review.id">
                            <div class="bg-white border border-gray-100 rounded-2xl p-4 sm:p-5 space-y-2.5 shadow-xs hover:border-gray-200 transition-all">
                                <div class="flex items-center justify-between flex-wrap gap-2">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-full bg-gray-100 border border-gray-200/60 flex items-center justify-center font-bold text-gray-700 text-sm overflow-hidden shrink-0 shadow-xs">
                                            <template x-if="review.customerPhoto">
                                                <img :src="review.customerPhoto" class="w-full h-full object-cover">
                                            </template>
                                            <template x-if="!review.customerPhoto">
                                                <span x-text="review.initial"></span>
                                            </template>
                                        </div>
                                        <div>
                                            <div class="flex items-center gap-2 flex-wrap">
                                                <span class="text-xs sm:text-sm font-bold text-gray-900" x-text="review.customerName"></span>
                                                <div class="flex items-center gap-0.5 text-amber-400 text-xs">
                                                    <template x-for="s in 5" :key="s">
                                                        <span x-text="s <= review.rating ? '★' : '☆'"></span>
                                                    </template>
                                                </div>
                                            </div>
                                            <div class="text-[10px] text-gray-400 font-semibold uppercase tracking-wider mt-0.5" x-text="review.date"></div>
                                        </div>
                                    </div>

                                    <template x-if="review.verified">
                                        <span class="px-2.5 py-0.5 bg-emerald-50 text-emerald-700 border border-emerald-100/80 rounded-full text-[9px] font-black uppercase tracking-wider flex items-center gap-1">
                                            ✓ Verified Purchase
                                        </span>
                                    </template>
                                </div>

                                <template x-if="review.comment">
                                    <p class="text-xs sm:text-sm text-gray-800 leading-relaxed italic" x-text="'“' + review.comment + '”'"></p>
                                </template>

                                {{-- Review Media (Images up to 3 & Video) in Modal --}}
                                <div class="flex flex-wrap items-center gap-2 pt-1">
                                    {{-- Photos --}}
                                    <template x-if="review.images && review.images.length > 0">
                                        <template x-for="(img, idx) in review.images" :key="idx">
                                            <button type="button" 
                                                    @click="openLightbox('image', img)" 
                                                    class="w-16 h-16 sm:w-20 sm:h-20 rounded-xl overflow-hidden border border-gray-200 bg-gray-50 shrink-0 hover:scale-105 transition-all shadow-xs block cursor-pointer relative group">
                                                <img :src="img" class="w-full h-full object-cover" alt="Review Photo">
                                                <div class="absolute inset-0 bg-black/20 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center text-white text-xs">
                                                    🔍
                                                </div>
                                            </button>
                                        </template>
                                    </template>

                                    {{-- Video --}}
                                    <template x-if="review.video">
                                        <button type="button" 
                                                @click="openLightbox('video', review.video)" 
                                                class="w-16 h-16 sm:w-20 sm:h-20 rounded-xl overflow-hidden border border-gray-800 bg-black shrink-0 hover:scale-105 transition-all shadow-xs cursor-pointer relative group flex items-center justify-center">
                                            <video :src="review.video" class="w-full h-full object-cover opacity-70" preload="metadata"></video>
                                            <div class="absolute inset-0 bg-black/25 flex items-center justify-center">
                                                <div class="w-7 h-7 rounded-full bg-white/90 text-black flex items-center justify-center text-[10px] font-black pl-0.5 shadow-sm group-hover:scale-110 transition-transform">
                                                    ▶
                                                </div>
                                            </div>
                                            <span class="absolute bottom-1 right-1 bg-black/80 text-[8px] font-bold text-white px-1.5 py-0.2 rounded">VIDEO</span>
                                        </button>
                                    </template>
                                </div>

                                {{-- Seller Response (Shopee/Lazada Style) --}}
                                <template x-if="review.seller_reply">
                                    <div class="mt-3 p-3.5 bg-[#FAF9F5] rounded-2xl border-l-4 border-[#C0420A] space-y-1 shadow-2xs">
                                        <div class="flex items-center justify-between gap-2">
                                            <span class="text-[10px] font-black uppercase tracking-wider text-[#C0420A] flex items-center gap-1.5">
                                                <span>💬 Seller's Response</span>
                                                <span class="text-gray-400 font-normal" x-text="'• ' + (review.seller_name || 'Artisan Store')"></span>
                                            </span>
                                            <span class="text-[9px] text-gray-400 font-medium" x-text="review.seller_reply_date || ''"></span>
                                        </div>
                                        <p class="text-xs text-gray-700 leading-relaxed font-normal" x-text="review.seller_reply"></p>
                                    </div>
                                </template>
                            </div>
                        </template>
                    </div>

                    {{-- Modal Pagination Footer (Matching Screenshot) --}}
                    <div class="p-5 sm:p-6 bg-white border-t border-gray-100 flex flex-col items-center gap-3 shrink-0 relative">
                        <div class="text-xs text-gray-500 font-medium text-center">
                            Showing page <span class="font-bold text-gray-900" x-text="currentPage"></span> of <span class="font-bold text-gray-900" x-text="totalPages"></span> (<span x-text="filteredReviews.length"></span> reviews)
                        </div>

                        {{-- Pagination Buttons (‹ 1 2 3 ›) --}}
                        <div class="flex items-center gap-2">
                            <button type="button" 
                                @click="if(currentPage > 1) { currentPage--; if($refs.reviewsContainer) $refs.reviewsContainer.scrollTop = 0; }"
                                :disabled="currentPage === 1"
                                :class="currentPage === 1 ? 'opacity-30 cursor-not-allowed text-gray-400 bg-gray-50' : 'hover:border-[#C0420A] hover:text-[#C0420A] text-gray-700 bg-white cursor-pointer'"
                                class="w-10 h-10 rounded-xl border border-gray-200 flex items-center justify-center text-sm font-bold transition-all shadow-xs">
                                ‹
                            </button>

                            <template x-for="p in totalPages" :key="p">
                                <button type="button" 
                                    @click="currentPage = p; if($refs.reviewsContainer) $refs.reviewsContainer.scrollTop = 0;"
                                    :class="currentPage === p ? 'border-[#C0420A] text-[#C0420A] bg-white font-black shadow-xs' : 'border-gray-200 text-gray-700 hover:border-gray-300 hover:bg-gray-50 font-bold'"
                                    class="w-10 h-10 rounded-xl border text-sm transition-all cursor-pointer flex items-center justify-center"
                                    x-text="p">
                                </button>
                            </template>

                            <button type="button" 
                                @click="if(currentPage < totalPages) { currentPage++; if($refs.reviewsContainer) $refs.reviewsContainer.scrollTop = 0; }"
                                :disabled="currentPage === totalPages"
                                :class="currentPage === totalPages ? 'opacity-30 cursor-not-allowed text-gray-400 bg-gray-50' : 'hover:border-[#C0420A] hover:text-[#C0420A] text-gray-700 bg-white cursor-pointer'"
                                class="w-10 h-10 rounded-xl border border-gray-200 flex items-center justify-center text-sm font-bold transition-all shadow-xs">
                                ›
                            </button>
                        </div>

                        {{-- Close Button on Bottom Right --}}
                        <div class="w-full sm:w-auto sm:absolute sm:right-6 sm:bottom-5 flex justify-end mt-2 sm:mt-0">
                            <button type="button" @click="reviewsModal = false"
                                class="w-full sm:w-auto px-7 py-3 bg-[#111] text-white text-xs font-black uppercase tracking-widest rounded-xl hover:bg-[#C0420A] transition-all shadow-md active:scale-95 cursor-pointer">
                                CLOSE
                            </button>
                        </div>
                    </div>

                </div>
            </div>

            {{-- Integrated Media Lightbox Overlay (Images & Video Modal) --}}
            <div x-show="lightboxModal" 
                 class="fixed inset-0 z-99999 flex items-center justify-center p-3 sm:p-6 bg-black/85 backdrop-blur-md"
                 x-cloak 
                 style="display: none;">
                <div @click.away="closeLightbox()" class="relative max-w-4xl w-full flex flex-col items-center justify-center">
                    {{-- Obvious Close Button --}}
                    <button type="button" 
                            @click="closeLightbox()" 
                            class="absolute -top-12 right-0 sm:-right-2 w-10 h-10 rounded-full bg-white/20 hover:bg-white text-white hover:text-black flex items-center justify-center text-lg font-black backdrop-blur-md transition-all cursor-pointer shadow-lg z-20">
                        ✕
                    </button>

                    {{-- Lightbox Display Container --}}
                    <div class="w-full flex items-center justify-center rounded-2xl overflow-hidden shadow-2xl bg-black/60 p-1 sm:p-2 border border-white/10">
                        <template x-if="lightboxType === 'image'">
                            <img :src="lightboxUrl" class="max-w-full max-h-[82vh] object-contain rounded-xl select-none" alt="Review Media Preview">
                        </template>
                        <template x-if="lightboxType === 'video'">
                            <video x-ref="lightboxVideo" :src="lightboxUrl" controls autoplay playsinline class="max-w-full max-h-[78vh] rounded-xl bg-black shadow-2xl"></video>
                        </template>
                    </div>
                </div>
            </div>

        </div>

    </div>

    {{-- Recommended Products --}}
    @if($recommended->isNotEmpty())
    <div class="mt-16">
        <div class="flex items-center justify-between mb-6">
            <div>
                <div class="flex items-center gap-2 mb-1">
                    <div class="w-5 h-[1.5px] bg-[#C0422A]"></div>
                    <span class="text-[10px] font-bold uppercase tracking-widest text-[#C0422A]">Keep Exploring</span>
                </div>
                <h2 class="font-serif text-2xl font-bold text-black">Recommended Products</h2>
            </div>
            <a href="/#catalogue-section" class="text-[10px] font-bold text-gray-400 uppercase tracking-widest hover:text-[#C0422A] transition-colors">
                View all →
            </a>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 lg:grid-cols-6 gap-3.5 sm:gap-4">
            @foreach($recommended as $rec)
            <a href="/products/{{ $rec->id }}" class="group block">
                <div class="aspect-4/5 bg-gray-100 rounded-2xl overflow-hidden mb-3 relative shadow-sm">
                    <img src="{{ $rec->getImageUrl() }}"
                         alt="{{ $rec->name }}"
                         class="w-full h-full object-cover object-top group-hover:scale-105 transition-transform duration-500 ease-out">

                    @if($rec->is_on_sale && $rec->discount_percentage > 0)
                        <div style="position:absolute;top:8px;left:8px;display:flex;flex-direction:column;gap:4px;z-index:10;pointer-events:none;">
                            <div style="display:inline-flex;align-items:center;gap:4px;padding:3px 9px 3px 6px;background:linear-gradient(135deg,#0F0C08 0%,#1C1609 100%);border:1px solid #A87B10;border-radius:20px;box-shadow:0 0 8px rgba(180,130,15,0.45),inset 0 1px 0 rgba(230,185,60,0.12);white-space:nowrap;">
                                <img src="/images/logo-icon.png" alt="LumBarong" style="width:13px;height:13px;border-radius:50%;flex-shrink:0;object-fit:cover;">
                                <span style="color:#DFC97A;font-family:ui-sans-serif,system-ui,sans-serif;font-size:7px;font-weight:700;letter-spacing:0.18em;text-transform:uppercase;">Lumban Special</span>
                            </div>
                            <div style="display:inline-flex;align-items:baseline;padding:3px 9px;background:linear-gradient(90deg,#7A5505 0%,#C8890A 25%,#E8AD12 50%,#C8890A 75%,#7A5505 100%);border:1px solid #5C3E04;border-radius:20px;box-shadow:0 2px 10px rgba(200,137,10,0.5),inset 0 1px 0 rgba(255,220,80,0.25);white-space:nowrap;">
                                <span style="color:#FFF8E0;font-family:ui-sans-serif,system-ui,sans-serif;font-size:13px;font-weight:900;line-height:1;letter-spacing:-0.02em;">-{{ number_format($rec->discount_percentage, 0) }}%</span>
                                <span style="color:#FFE8A0;font-family:ui-sans-serif,system-ui,sans-serif;font-size:8px;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;margin-left:2px;">OFF</span>
                            </div>
                        </div>
                    @elseif($rec->target_group)
                        <div class="absolute top-2.5 left-2.5 bg-white/90 backdrop-blur-sm text-[8px] font-black uppercase tracking-widest text-gray-500 px-2 py-0.5 rounded-full">
                            {{ $rec->target_group }}
                        </div>
                    @endif
                </div>

                <h3 class="font-extrabold text-sm text-gray-900 group-hover:text-[#C0422A] transition-colors leading-tight line-clamp-2 uppercase tracking-tight">{{ $rec->name }}</h3>

                @if($rec->avgRating)
                    <div class="flex items-center gap-1 text-[10px] font-bold text-yellow-500 mt-1">
                        <span>★</span>
                        <span>{{ number_format($rec->avgRating, 1) }}</span>
                        <span class="text-gray-400">({{ $rec->reviewCount }})</span>
                    </div>
                @endif

                <div class="flex items-center gap-2 mt-1">
                    @if($rec->is_on_sale && $rec->discount_percentage > 0)
                        <p class="text-base font-extrabold text-[#E02424]">₱{{ number_format($rec->salePrice) }}</p>
                        <p class="text-xs font-bold text-gray-400 line-through">₱{{ number_format($rec->price) }}</p>
                    @else
                        <p class="text-sm font-black text-gray-800">₱{{ number_format($rec->price) }}</p>
                    @endif
                </div>

                @if($rec->artisan)
                    <p class="text-[10px] text-gray-400 mt-0.5 font-medium">by {{ $rec->artisan }}</p>
                @endif
            </a>
            @endforeach
        </div>
    </div>
    @endif

    <!-- ========== SIZE GUIDE MODAL ========== -->
    <div id="size-guide-modal"
         x-show="showSizeGuide"
         x-cloak
         @keydown.escape.window="showSizeGuide = false; closeSizeGuideModal();"
         class="fixed inset-0 flex items-center justify-center p-4"
         style="display: none; z-index: 999999 !important;">
        <!-- Backdrop -->
        <div class="absolute inset-0 bg-black/70 backdrop-blur-sm" @click="showSizeGuide = false" onclick="closeSizeGuideModal()"></div>

        <!-- Modal Panel -->
        <div class="relative z-10 bg-white rounded-3xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto border border-gray-100">

            <!-- Header -->
            <div class="sticky top-0 bg-white px-8 pt-8 pb-6 border-b border-gray-100 flex items-start justify-between z-20">
                <div>
                    <div class="flex items-center gap-2 mb-1">
                        <div class="w-5 h-[1.5px] bg-[#C0422A]"></div>
                        <span class="text-[10px] font-bold uppercase tracking-widest text-[#C0422A]">Barong Tagalog</span>
                    </div>
                    <h2 class="font-serif text-2xl font-bold text-black">Size Guide</h2>
                    <p class="text-xs text-gray-400 mt-0.5">All measurements are in centimetres (cm)</p>
                </div>
                <button type="button" @click="showSizeGuide = false" onclick="closeSizeGuideModal()"
                        class="w-9 h-9 rounded-full border border-gray-200 flex items-center justify-center text-gray-400 hover:text-black hover:border-gray-400 transition-all shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <!-- Content -->
            <div class="px-8 py-6 space-y-6" x-data="{ sizeTab: 'men' }">

                <!-- Seller's Custom Size Guide Image (if uploaded) -->
                @if($product->getSizeGuideUrl())
                    <div class="bg-[#FDF9F4] border border-[#F5EAD9] rounded-2xl p-5 space-y-3">
                        <div class="flex items-center justify-between gap-2">
                            <span class="text-xs font-extrabold uppercase tracking-wider text-[#C0422A]">📐 Seller's Custom Size Chart</span>
                            <span class="text-[9px] bg-[#C0422A]/10 text-[#C0422A] px-2 py-0.5 rounded-full font-bold uppercase tracking-wider">Artisan Size Guide</span>
                        </div>
                        <div class="rounded-xl overflow-hidden bg-white border border-gray-200 p-2 shadow-xs">
                            <img src="{{ $product->getSizeGuideUrl() }}" class="w-full max-h-96 object-contain mx-auto rounded-lg" alt="{{ $product->name }} Size Guide">
                        </div>
                    </div>
                @endif

                <!-- Seller Custom Measurements Table (if specified by artisan) -->
                @if(!empty($product->size_guide_measurements) && is_array($product->size_guide_measurements))
                    <div class="bg-amber-50/60 border border-amber-200/80 rounded-2xl p-5 space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-black uppercase tracking-wider text-[#C0422A]">👕 Seller Specific Product Measurements</span>
                            <span class="text-[9px] bg-amber-200/60 text-amber-900 px-2 py-0.5 rounded-full font-bold uppercase">Exact Garment Specs</span>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-xs">
                                <thead>
                                    <tr class="border-b-2 border-amber-300">
                                        <th class="py-2.5 pr-4 text-left font-black uppercase text-[10px] text-black">Size</th>
                                        <th class="py-2.5 px-3 text-center font-black uppercase text-[10px] text-gray-700">Chest</th>
                                        <th class="py-2.5 px-3 text-center font-black uppercase text-[10px] text-gray-700">Shoulder</th>
                                        <th class="py-2.5 px-3 text-center font-black uppercase text-[10px] text-gray-700">Length</th>
                                        <th class="py-2.5 px-3 text-center font-black uppercase text-[10px] text-gray-700">Sleeves</th>
                                        <th class="py-2.5 px-3 text-center font-black uppercase text-[10px] text-gray-700">Width</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-amber-100">
                                    @foreach($product->size_guide_measurements as $mRow)
                                        <tr>
                                            <td class="py-2.5 pr-4 font-black text-black">{{ $mRow['size'] ?? '—' }}</td>
                                            <td class="py-2.5 px-3 text-center font-bold text-gray-800">{{ $mRow['chest'] ?? '—' }}</td>
                                            <td class="py-2.5 px-3 text-center font-bold text-gray-800">{{ $mRow['shoulder'] ?? '—' }}</td>
                                            <td class="py-2.5 px-3 text-center font-bold text-gray-800">{{ $mRow['length'] ?? '—' }}</td>
                                            <td class="py-2.5 px-3 text-center font-bold text-gray-800">{{ $mRow['sleeves'] ?? '—' }}</td>
                                            <td class="py-2.5 px-3 text-center font-bold text-gray-800">{{ $mRow['width'] ?? '—' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif

                <!-- Size Guide Reference with Men / Women / Kids Tabs -->
                @php
                    $sellerSizeGuides = $product->seller->size_guides ?? [];
                    $resolveSgUrl = function($targetGroup, $defaultPath) use ($sellerSizeGuides) {
                        if (!empty($sellerSizeGuides[$targetGroup])) {
                            $path = $sellerSizeGuides[$targetGroup];
                            return str_starts_with($path, 'http') ? $path : asset(ltrim($path, '/'));
                        }
                        return asset($defaultPath);
                    };
                    $menGuideUrl   = $resolveSgUrl('Men', 'uploads/size-guides/size_guide_men.png');
                    $womenGuideUrl = $resolveSgUrl('Women', 'uploads/size-guides/size_guide_women.png');
                    $kidsGuideUrl  = $resolveSgUrl('Kids', 'uploads/size-guides/size_guide_kids.png');

                    $isCustomMen   = !empty($sellerSizeGuides['Men']);
                    $isCustomWomen = !empty($sellerSizeGuides['Women']);
                    $isCustomKids  = !empty($sellerSizeGuides['Kids']);
                @endphp

                <div class="space-y-4">
                    <div class="flex items-center gap-2 py-1">
                        <div class="w-5 h-[1.5px] bg-gray-300"></div>
                        <span class="text-[10px] font-bold uppercase tracking-widest text-gray-400">
                            Barong & Filipiniana Size Guide Reference
                        </span>
                        <div class="flex-1 h-px bg-gray-100"></div>
                    </div>

                    <!-- Tab Buttons: Men / Women / Kids -->
                    <div class="flex gap-2 bg-gray-100 p-1 rounded-2xl w-full">
                        <button type="button" id="size-tab-btn-men" @click="sizeTab = 'men'" onclick="switchSizeGuideTab('men')"
                            class="flex-1 py-2.5 rounded-xl text-xs font-black uppercase tracking-widest bg-white shadow-md text-black border border-gray-200 transition-all">
                            👔 Men
                        </button>
                        <button type="button" id="size-tab-btn-women" @click="sizeTab = 'women'" onclick="switchSizeGuideTab('women')"
                            class="flex-1 py-2.5 rounded-xl text-xs font-semibold uppercase tracking-widest text-gray-400 hover:text-black transition-all">
                            👗 Women
                        </button>
                        <button type="button" id="size-tab-btn-kids" @click="sizeTab = 'kids'" onclick="switchSizeGuideTab('kids')"
                            class="flex-1 py-2.5 rounded-xl text-xs font-semibold uppercase tracking-widest text-gray-400 hover:text-black transition-all">
                            🧒 Kids
                        </button>
                    </div>

                    <!-- MEN's SIZE DIAGRAM PICTURE -->
                    <div id="size-tab-content-men" x-show="sizeTab === 'men'" class="space-y-4 text-center" style="display: block;">
                        <div class="rounded-3xl border border-[#E5DDD5] overflow-hidden bg-[#FDF9F4] p-4 shadow-sm">
                            <a href="{{ $menGuideUrl }}" target="_blank" title="Click to view full size image">
                                <img src="{{ $menGuideUrl }}" alt="Men's Size Guide Chart" class="w-full max-h-[70vh] object-contain rounded-2xl mx-auto shadow-xs hover:scale-[1.02] transition-transform">
                            </a>
                        </div>
                        <p class="text-xs text-gray-500 font-semibold">
                            👔 {{ $isCustomMen ? "Artisan's Shop Men's Size Guide Chart" : "Men's Barong Tagalog Standard Size Guide Chart" }} • Click image to open high-resolution view
                        </p>
                    </div>

                    <!-- WOMEN's SIZE DIAGRAM PICTURE -->
                    <div id="size-tab-content-women" x-show="sizeTab === 'women'" class="space-y-4 text-center" style="display: none;">
                        <div class="rounded-3xl border border-[#F5EAD9] overflow-hidden bg-[#FDF9F4] p-4 shadow-sm">
                            <a href="{{ $womenGuideUrl }}" target="_blank" title="Click to view full size image">
                                <img src="{{ $womenGuideUrl }}" alt="Women's Size Guide Chart" class="w-full max-h-[70vh] object-contain rounded-2xl mx-auto shadow-xs hover:scale-[1.02] transition-transform">
                            </a>
                        </div>
                        <p class="text-xs text-gray-500 font-semibold">
                            👗 {{ $isCustomWomen ? "Artisan's Shop Women's Size Guide Chart" : "Women's Baro't Saya / Filipiniana Standard Size Guide Chart" }} • Click image to open high-resolution view
                        </p>
                    </div>

                    <!-- KIDS' SIZE DIAGRAM PICTURE -->
                    <div id="size-tab-content-kids" x-show="sizeTab === 'kids'" class="space-y-4 text-center" style="display: none;">
                        <div class="rounded-3xl border border-gray-200 overflow-hidden bg-[#F4F8F3] p-4 shadow-sm">
                            <a href="{{ $kidsGuideUrl }}" target="_blank" title="Click to view full size image">
                                <img src="{{ $kidsGuideUrl }}" alt="Kids' Size Guide Chart" class="w-full max-h-[70vh] object-contain rounded-2xl mx-auto shadow-xs hover:scale-[1.02] transition-transform">
                            </a>
                        </div>
                        <p class="text-xs text-gray-500 font-semibold">
                            🧒 {{ $isCustomKids ? "Artisan's Shop Kids' Size Guide Chart" : "Kids' Barong Tagalog Standard Size Guide Chart" }} • Click image to open high-resolution view
                        </p>
                    </div>
                </div>

                <!-- How to Measure tip -->
                <div class="bg-[#FDF9F4] border border-[#F5EAD9] rounded-2xl p-4 flex gap-4">
                    <div class="w-8 h-8 rounded-full bg-[#C0422A]/10 flex items-center justify-center shrink-0 mt-0.5">
                        <svg class="w-4 h-4 text-[#C0422A]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <div>
                        <div class="text-[10px] font-black uppercase tracking-widest text-[#C0422A] mb-1">How to Measure</div>
                        <p class="text-xs text-gray-600 leading-relaxed">Use a soft measuring tape. Keep the tape close to your body but not tight. Measure over your undergarment for the most accurate results.</p>
                    </div>
                </div>

                <!-- Measurement Guides Grid -->
                <div class="grid grid-cols-2 gap-3">
                    <div class="bg-gray-50 rounded-2xl p-4">
                        <div class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-1.5">Chest / Bust</div>
                        <p class="text-xs text-gray-600 leading-relaxed">Measure around the fullest part of your chest, keeping the tape horizontal under the armpits.</p>
                    </div>
                    <div class="bg-gray-50 rounded-2xl p-4">
                        <div class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-1.5">Shoulders</div>
                        <p class="text-xs text-gray-600 leading-relaxed">Measure from the edge of one shoulder across the back to the edge of the other shoulder.</p>
                    </div>
                    <div class="bg-gray-50 rounded-2xl p-4">
                        <div class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-1.5">Length</div>
                        <p class="text-xs text-gray-600 leading-relaxed">Measure from the highest point of the shoulder, straight down to the desired hemline.</p>
                    </div>
                    <div class="bg-gray-50 rounded-2xl p-4">
                        <div class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-1.5">Sleeve</div>
                        <p class="text-xs text-gray-600 leading-relaxed">Measure from the shoulder seam to the end of the cuff with your arm slightly bent.</p>
                    </div>
                </div>

                <!-- Note -->
                <div class="text-center pb-2">
                    <p class="text-[10px] text-gray-400 font-medium">Sizes may vary slightly between artisans. When in doubt, size up. Contact the artisan for custom fitting.</p>
                </div>

            </div>
    </div>
    <!-- ========================================= -->

</div>

<script>
    function openSizeGuideModal() {
        var modal = document.getElementById('size-guide-modal');
        if (modal) {
            modal.style.setProperty('display', 'flex', 'important');
            document.body.style.overflow = 'hidden';
        }
    }

    function closeSizeGuideModal() {
        var modal = document.getElementById('size-guide-modal');
        if (modal) {
            modal.style.setProperty('display', 'none', 'important');
            document.body.style.overflow = '';
        }
    }

    function switchSizeGuideTab(tabName) {
        var tabs = ['men', 'women', 'kids'];
        tabs.forEach(function(t) {
            var content = document.getElementById('size-tab-content-' + t);
            var btn = document.getElementById('size-tab-btn-' + t);
            if (content) {
                content.style.display = (t === tabName) ? 'block' : 'none';
            }
            if (btn) {
                if (t === tabName) {
                    btn.className = 'flex-1 py-2.5 rounded-xl text-xs font-black uppercase tracking-widest bg-white shadow-md text-black border border-gray-200 transition-all';
                } else {
                    btn.className = 'flex-1 py-2.5 rounded-xl text-xs font-semibold uppercase tracking-widest text-gray-400 hover:text-black transition-all';
                }
            }
        });
    }

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeSizeGuideModal();
        }
    });
</script>

@endsection
