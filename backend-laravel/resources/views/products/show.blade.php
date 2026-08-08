@extends('layouts.app')

@section('content')
@php
    use App\Support\VariationFormatter;
    $productVariations = VariationFormatter::buildVariations($product->image);
@endphp
<div id="product-page-data"
    data-logged-in="{{ auth()->check() ? 'true' : 'false' }}"
    data-login-url="{{ route('login') }}"
    style="display:none;" aria-hidden="true">
</div>
<script>
    var _pd = document.getElementById('product-page-data').dataset;
    window.isLoggedIn = _pd.loggedIn === 'true';
    window.loginUrl   = _pd.loginUrl;

    document.addEventListener('DOMContentLoaded', function () {
        // Intercept cart/checkout form submissions for guests
        document.querySelectorAll('form[action="/cart/add"], form[action="/checkout"]').forEach(function(form) {
            form.addEventListener('submit', function(e) {
                if (!window.isLoggedIn) {
                    e.preventDefault();
                    window.location.href = window.loginUrl + '?next=cart';
                }
            });
        });
    });
</script>
<div class="max-w-6xl mx-auto py-4 lg:py-6" x-data="productDetail({{ $product->stock ?? 1 }}, {{ json_encode($product->size_stocks ?? (object)[]) }}, {{ json_encode($productVariations) }})">
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

                <!-- Main Image Display Box with 360 Badge -->
                <div 
                    class="flex-1 min-w-0 w-full relative aspect-4/5 bg-gray-50 rounded-2xl overflow-hidden border border-gray-100 shadow-xs group select-none"
                    :class="isZoomed ? 'cursor-zoom-out' : 'cursor-zoom-in'"
                    x-data="{
                        isZoomed: false,
                        originX: 50,
                        originY: 50,
                        toggleZoom(e) {
                            const rect = e.currentTarget.getBoundingClientRect();
                            const x = ((e.clientX - rect.left) / rect.width) * 100;
                            const y = ((e.clientY - rect.top) / rect.height) * 100;
                            this.originX = x.toFixed(2);
                            this.originY = y.toFixed(2);
                            this.isZoomed = !this.isZoomed;
                        },
                        handleMouseMove(e) {
                            if (!this.isZoomed) return;
                            const rect = e.currentTarget.getBoundingClientRect();
                            const x = ((e.clientX - rect.left) / rect.width) * 100;
                            const y = ((e.clientY - rect.top) / rect.height) * 100;
                            this.originX = x.toFixed(2);
                            this.originY = y.toFixed(2);
                        },
                        handleMouseLeave() {
                            this.isZoomed = false;
                        }
                    }"
                    @click="toggleZoom($event)"
                    @mousemove="handleMouseMove($event)"
                    @mouseleave="handleMouseLeave()"
                >
                    <!-- Main Image Display -->
                    <template x-for="(variation, index) in variations" :key="index">
                        <img 
                            x-show="activeImage === index"
                            :src="imageUrl(variation.url)"
                            onerror="this.src='/uploads/products/default.jpg'"
                            class="w-full h-full object-cover object-top"
                            :class="isZoomed ? 'scale-[2.2] transition-transform duration-100 ease-out' : 'scale-100 transition-transform duration-300 ease-out'"
                            :style="isZoomed ? { transformOrigin: `${originX}% ${originY}%` } : {}"
                            alt="{{ $product->name }}"
                        >
                    </template>

                    <!-- 360 Degree Interactive Badge (Bottom Right) -->
                    <div class="absolute bottom-4 right-4 z-10 w-11 h-11 rounded-full bg-white/90 backdrop-blur-md border border-gray-200 shadow-md flex items-center justify-center cursor-pointer hover:scale-110 transition-transform" title="360° Interactive View">
                        <div class="flex flex-col items-center justify-center leading-none text-gray-900">
                            <span class="text-[9px] font-black tracking-tighter">360°</span>
                            <svg class="w-3.5 h-3.5 text-gray-800 -mt-0.5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                                <path d="M4 12a8 8 0 0114.93-3M20 12a8 8 0 01-14.93 3"/><path d="M4 12l3-3M4 12l3 3M20 12l-3-3M20 12l-3 3"/>
                            </svg>
                        </div>
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
                                <img src="{{ $product->seller->profile_photo_url ?? '/uploads/categories/wedding_groom.png' }}" class="w-5 h-5 rounded-full object-cover border border-gray-200" alt="Artisan">
                                <span>{{ $product->artisan ?? $product->seller->shopName ?? 'BarongniJuan' }}</span>
                            </a>
                        </div>
                        <span class="text-gray-300">•</span>
                        <div class="flex items-center gap-1 font-bold text-gray-700">
                            <span class="text-amber-600 font-extrabold">{{ number_format($product->avgRating ?? 4.8, 1) }}</span>
                            <svg class="w-3.5 h-3.5 fill-amber-400 text-amber-400" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                            </svg>
                            <span class="text-gray-400 font-normal">({{ $product->reviewCount ?? 125 }} reviews)</span>
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

                    <!-- Material & Category Specifications -->
                    <div class="space-y-1 text-xs text-gray-600 mb-6">
                        <div><span class="font-bold text-gray-800">Material:</span> {{ $product->fabric ?? '100% Piña' }}</div>
                        <div><span class="font-bold text-gray-800">Category:</span> {{ $product->category->name ?? 'Wedding Barong' }}</div>
                    </div>

                    <!-- Available Sizes Row -->
                    <div class="mb-6">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xs font-bold text-gray-900">Available Sizes:</span>
                            <button type="button" @click="showSizeGuide = true" class="text-[11px] font-bold text-amber-800 hover:underline">Size Guide</button>
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
                             class="mt-3.5 p-4 bg-[#FDF9F4] border border-[#C0422A]/20 rounded-2xl space-y-3">
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-extrabold text-[#C0422A] uppercase tracking-wider">✂️ Tailored Custom Sizing</span>
                            </div>
                            <p class="text-[11px] text-gray-500 font-medium">Input your body measurements in inches (in) or centimetres (cm):</p>
                            
                            <div class="grid grid-cols-2 sm:grid-cols-3 gap-2.5 text-xs">
                                <div>
                                    <label class="font-bold text-gray-700 block mb-1">Neck</label>
                                    <input type="text" x-model="customMeasurements.neck" placeholder="e.g. 15.5 in / 39 cm" class="w-full h-8.5 px-3 bg-white border border-gray-200 rounded-xl text-xs outline-none focus:border-[#C0422A] transition-colors">
                                </div>
                                <div>
                                    <label class="font-bold text-gray-700 block mb-1">Chest</label>
                                    <input type="text" x-model="customMeasurements.chest" placeholder="e.g. 38 in / 96 cm" class="w-full h-8.5 px-3 bg-white border border-gray-200 rounded-xl text-xs outline-none focus:border-[#C0422A] transition-colors">
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
                                    <label class="font-bold text-gray-700 block mb-1">Waist</label>
                                    <input type="text" x-model="customMeasurements.waist" placeholder="e.g. 32 in / 81 cm" class="w-full h-8.5 px-3 bg-white border border-gray-200 rounded-xl text-xs outline-none focus:border-[#C0422A] transition-colors">
                                </div>
                                <div>
                                    <label class="font-bold text-gray-700 block mb-1">Full Length</label>
                                    <input type="text" x-model="customMeasurements.fullLength" placeholder="e.g. 29 in / 74 cm" class="w-full h-8.5 px-3 bg-white border border-gray-200 rounded-xl text-xs outline-none focus:border-[#C0422A] transition-colors">
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
                        <span class="text-xs font-semibold text-gray-400">
                            (<span x-text="stock"></span> pieces available)
                        </span>
                    </div>

                    <!-- Action Buttons -->
                    <form action="/cart/add" method="POST" @submit.prevent="submitAddToCart($event)" class="space-y-3">
                        @csrf
                        <input type="hidden" name="productId" value="{{ $product->id }}">
                        <input type="hidden" name="size" :value="effectiveSize()">
                        <input type="hidden" name="quantity" :value="quantity">
                        <input type="hidden" name="variation" :value="selectedVariationLabel()">

                        <div class="flex items-center gap-3">
                            <button 
                                type="button" 
                                @click="toggleWishlist()" 
                                class="flex-1 h-12 rounded-xl border border-gray-300 font-bold text-xs flex items-center justify-center gap-2 hover:bg-gray-50 transition-colors shadow-2xs"
                                :class="isWishlisted ? 'text-red-500 border-red-200 bg-red-50/50' : 'text-gray-900'"
                            >
                                <svg class="w-4 h-4" :class="isWishlisted ? 'fill-red-500 text-red-500' : 'fill-none stroke-current'" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                                </svg>
                                <span x-text="isWishlisted ? 'Saved in Wishlist' : 'Add to Wishlist'"></span>
                            </button>

                            <button 
                                type="submit"
                                :disabled="!selectedSize || stock <= 0"
                                class="flex-1 h-12 rounded-xl bg-black hover:bg-gray-900 text-white font-bold text-xs flex items-center justify-center gap-2 transition-colors shadow-md disabled:opacity-50"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                                </svg>
                                <span x-text="!selectedSize ? 'Select Size' : (stock <= 0 ? 'Out of Stock' : 'Add to Cart')"></span>
                            </button>
                        </div>

                        <!-- Full Width Gold Buy Now Button -->
                        <button 
                            type="button" 
                            @click="if(selectedSize && stock > 0) window.location.href = '/checkout?productId={{ $product->id }}&size=' + effectiveSize() + '&quantity=' + quantity + '&direct=1'"
                            :disabled="!selectedSize || stock <= 0"
                            class="w-full h-12 rounded-xl bg-[#C89B55] hover:bg-[#B88B45] text-white font-extrabold text-sm tracking-wide shadow-md transition-colors disabled:opacity-50"
                        >
                            <span x-text="!selectedSize ? 'Select Size' : 'Buy Now'"></span>
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Bottom Delivery & Policy Feature Bar -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 pt-8 mt-8 border-t border-gray-100 text-xs">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-gray-50 flex items-center justify-center text-gray-700 shrink-0 border border-gray-100">
                    🚚
                </div>
                <div>
                    <div class="font-medium text-gray-500">Shipping Fee</div>
                    <div class="font-extrabold text-gray-900">₱80.00</div>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-gray-50 flex items-center justify-center text-gray-700 shrink-0 border border-gray-100">
                    📦
                </div>
                <div>
                    <div class="font-medium text-gray-500">Estimated Delivery</div>
                    <div class="font-extrabold text-gray-900">2 - 4 days</div>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-gray-50 flex items-center justify-center text-gray-700 shrink-0 border border-gray-100">
                    ⏱️
                </div>
                <div>
                    <div class="font-medium text-gray-500">Return Policy</div>
                    <div class="font-extrabold text-gray-900">7 days return</div>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-gray-50 flex items-center justify-center text-gray-700 shrink-0 border border-gray-100">
                    📍
                </div>
                <div>
                    <div class="font-medium text-gray-500">Ships from</div>
                    <div class="font-extrabold text-gray-900">Manila, PH</div>
                </div>
            </div>
        </div>
    </div>

        <!-- Lower Section: Description & Info -->
        <div class="mt-16 pt-10 border-t border-gray-100 grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-16">
            <div class="lg:col-span-5">
                <h3 class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-4">Artisan's Story</h3>
                <div class="flex items-center gap-4">
                    <div class="w-14 h-14 bg-gray-50 rounded-2xl flex items-center justify-center text-xl font-bold text-gray-300 border border-gray-100 shadow-sm shrink-0 relative">
                        {{ strtoupper(substr($product->artisan ?? 'A', 0, 1)) }}
                        @if($product->seller && $product->seller->isPremiumActive())
                            <span class="absolute -top-1 -right-1 text-sm bg-yellow-400 border border-white rounded-full w-5 h-5 flex items-center justify-center shadow-xs">👑</span>
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
                        
                        <div class="mt-2">
                            <a href="/shops/{{ $product->sellerId }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-stone-100 hover:bg-[#C0420A] text-[9px] font-black uppercase tracking-widest text-stone-700 hover:text-white rounded-lg border border-stone-200/60 transition-all shadow-sm">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg>
                                View Shop
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="lg:col-span-7">
                <h3 class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-4">Product Details</h3>
                <p class="text-gray-600 text-sm leading-relaxed whitespace-pre-line">
                    {{ $product->description }}
                </p>
            </div>
        </div>

        <!-- Reviews Section -->
        <div class="mt-16 pt-10 border-t border-gray-100">
            <div class="flex flex-wrap items-center gap-6 mb-8">
                <div>
                    <div class="flex items-center gap-2 mb-1">
                        <div class="w-5 h-[1.5px] bg-[#C0422A]"></div>
                        <span class="text-[10px] font-bold uppercase tracking-widest text-[#C0422A]">Reviews & Feedback</span>
                    </div>
                    <h2 class="font-serif text-2xl font-bold text-black">Customer Reviews</h2>
                </div>
                
                {{-- Average rating summary badge --}}
                @if(($product->reviewCount ?? 0) > 0)
                    <div class="flex items-center gap-4 bg-[#FDF9F4] border border-[#F5EAD9] px-5 py-2.5 rounded-2xl">
                        <div class="text-center">
                            <span class="text-2xl font-extrabold text-[#C0422A]">{{ number_format($product->avgRating, 1) }}</span>
                            <span class="text-[10px] text-gray-400 font-bold block">out of 5</span>
                        </div>
                        <div class="w-px h-8 bg-gray-200"></div>
                        <div>
                            <div class="flex items-center gap-0.5 text-yellow-400">
                                @for($i = 1; $i <= 5; $i++)
                                    <svg class="w-3.5 h-3.5 fill-current {{ $i <= round($product->avgRating) ? 'text-yellow-400' : 'text-gray-200' }}" viewBox="0 0 20 20">
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                    </svg>
                                @endfor
                            </div>
                            <span class="text-[9px] font-bold text-gray-400 uppercase tracking-widest block mt-0.5">{{ $product->reviewCount }} review{{ ($product->reviewCount ?? 0) != 1 ? 's' : '' }}</span>
                        </div>
                    </div>
                @endif
            </div>

            @if($product->reviews && $product->reviews->count() > 0)
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    @foreach($product->reviews as $review)
                        <div class="bg-gray-50/50 border border-gray-100 rounded-2xl p-6 space-y-3">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-xl bg-gray-200 flex items-center justify-center font-bold text-gray-600 text-sm overflow-hidden shrink-0">
                                    @if($review->customer && $review->customer->profilePhoto)
                                        <img src="{{ str_starts_with($review->customer->profilePhoto, 'http') ? $review->customer->profilePhoto : asset($review->customer->profilePhoto) }}" class="w-full h-full object-cover" onerror="this.style.display='none'">
                                    @else
                                        {{ strtoupper(substr($review->customer->name ?? 'A', 0, 1)) }}
                                    @endif
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span class="text-xs font-bold text-black">{{ $review->customer->name ?? 'Anonymous Customer' }}</span>
                                        <div class="flex items-center gap-0.5">
                                            @for($i = 1; $i <= 5; $i++)
                                                <svg class="w-3.5 h-3.5 fill-current {{ $i <= $review->rating ? 'text-yellow-400' : 'text-gray-200' }}" viewBox="0 0 20 20">
                                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                                </svg>
                                            @endfor
                                        </div>
                                    </div>
                                    <div class="text-[9px] text-gray-400 font-bold uppercase tracking-widest mt-0.5">{{ $review->createdAt->format('F d, Y') }}</div>
                                </div>
                            </div>
                            <p class="text-xs text-gray-600 leading-relaxed italic">
                                "{{ $review->comment }}"
                            </p>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-12 bg-gray-50/50 rounded-2xl border border-gray-100">
                    <p class="text-xs text-gray-400 font-bold uppercase tracking-widest">No reviews yet for this heritage piece.</p>
                    <p class="text-[10px] text-gray-400 mt-1">Purchased items can be rated once they are received.</p>
                </div>
            @endif
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
            <a href="/" class="text-[10px] font-bold text-gray-400 uppercase tracking-widest hover:text-[#C0422A] transition-colors">
                View all
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
                        <div class="absolute top-2.5 left-2.5 bg-[#C0422A] text-white text-[8px] font-black uppercase tracking-widest px-2 py-0.5 rounded-full">
                            -{{ number_format($rec->discount_percentage, 0) }}% OFF
                        </div>
                    @elseif($rec->target_group)
                        <div class="absolute top-2.5 left-2.5 bg-white/90 backdrop-blur-sm text-[8px] font-black uppercase tracking-widest text-gray-500 px-2 py-0.5 rounded-full">
                            {{ $rec->target_group }}
                        </div>
                    @endif
                </div>

                <h3 class="font-bold text-sm text-gray-900 group-hover:text-[#C0422A] transition-colors leading-tight line-clamp-2">{{ $rec->name }}</h3>

                @if($rec->avgRating)
                    <div class="flex items-center gap-1 text-[10px] font-bold text-yellow-500 mt-1">
                        <span>★</span>
                        <span>{{ number_format($rec->avgRating, 1) }}</span>
                        <span class="text-gray-400">({{ $rec->reviewCount }})</span>
                    </div>
                @endif

                <div class="flex items-center gap-2 mt-1">
                    @if($rec->is_on_sale && $rec->discount_percentage > 0)
                        <p class="text-sm font-black text-[#C0422A]">₱{{ number_format($rec->salePrice) }}</p>
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
    <div x-show="showSizeGuide"
         style="display: none; z-index: 9999;"
         x-cloak
         @keydown.escape.window="showSizeGuide = false"
         class="fixed inset-0 z-[9999] flex items-center justify-center p-4">
        <!-- Backdrop -->
        <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="showSizeGuide = false"></div>

        <!-- Modal Panel -->
        <div class="relative bg-white rounded-3xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95">

            <!-- Header -->
            <div class="sticky top-0 bg-white px-8 pt-8 pb-6 border-b border-gray-100 flex items-start justify-between">
                <div>
                    <div class="flex items-center gap-2 mb-1">
                        <div class="w-5 h-[1.5px] bg-[#C0422A]"></div>
                        <span class="text-[10px] font-bold uppercase tracking-widest text-[#C0422A]">Barong Tagalog</span>
                    </div>
                    <h2 class="font-serif text-2xl font-bold text-black">Size Guide</h2>
                    <p class="text-xs text-gray-400 mt-0.5">All measurements are in centimetres (cm)</p>
                </div>
                <button type="button" @click="showSizeGuide = false"
                        class="w-9 h-9 rounded-full border border-gray-200 flex items-center justify-center text-gray-400 hover:text-black hover:border-gray-400 transition-all shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <!-- Content -->
            <div class="px-8 py-6 space-y-8">

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

                <!-- Size Table -->
                <div class="overflow-x-auto">
                    <table class="w-full text-xs">
                        <thead>
                            <tr class="border-b-2 border-black">
                                <th class="py-3 pr-6 text-left font-black uppercase tracking-widest text-[10px] text-black">Size</th>
                                <th class="py-3 px-4 text-center font-black uppercase tracking-widest text-[10px] text-gray-500">Chest</th>
                                <th class="py-3 px-4 text-center font-black uppercase tracking-widest text-[10px] text-gray-500">Shoulders</th>
                                <th class="py-3 px-4 text-center font-black uppercase tracking-widest text-[10px] text-gray-500">Length</th>
                                <th class="py-3 px-4 text-center font-black uppercase tracking-widest text-[10px] text-gray-500">Sleeve</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @php
                            $sizeChart = [
                                ['size' => 'XS',  'chest' => '84–88',  'shoulder' => '42',   'length' => '70',  'sleeve' => '57'],
                                ['size' => 'S',   'chest' => '88–92',  'shoulder' => '43',   'length' => '72',  'sleeve' => '58'],
                                ['size' => 'M',   'chest' => '92–96',  'shoulder' => '44',   'length' => '74',  'sleeve' => '59'],
                                ['size' => 'L',   'chest' => '96–100', 'shoulder' => '45.5', 'length' => '76',  'sleeve' => '60'],
                                ['size' => 'XL',  'chest' => '100–106','shoulder' => '47',   'length' => '78',  'sleeve' => '61'],
                                ['size' => '2XL', 'chest' => '106–114','shoulder' => '48.5', 'length' => '80',  'sleeve' => '62'],
                                ['size' => '3XL', 'chest' => '114–122','shoulder' => '50',   'length' => '82',  'sleeve' => '63'],
                            ];
                            @endphp
                            @foreach($sizeChart as $row)
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="py-3 pr-6">
                                        <span @class([
                                            'inline-flex items-center justify-center w-10 h-10 rounded-xl font-black text-sm',
                                            'bg-black text-white' => is_array($sizes ?? []) && in_array($row['size'], array_column($sizes ?? [], 'size')),
                                            'bg-gray-100 text-gray-700' => !(is_array($sizes ?? []) && in_array($row['size'], array_column($sizes ?? [], 'size'))),
                                        ])>
                                            {{ $row['size'] }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 text-center font-semibold text-gray-700">{{ $row['chest'] }}</td>
                                    <td class="py-3 px-4 text-center font-semibold text-gray-700">{{ $row['shoulder'] }}</td>
                                    <td class="py-3 px-4 text-center font-semibold text-gray-700">{{ $row['length'] }}</td>
                                    <td class="py-3 px-4 text-center font-semibold text-gray-700">{{ $row['sleeve'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Measurement Guides -->
                <div class="grid grid-cols-2 gap-4">
                    <div class="bg-gray-50 rounded-2xl p-4">
                        <div class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-2">Chest</div>
                        <p class="text-xs text-gray-600 leading-relaxed">Measure around the fullest part of your chest, keeping the tape horizontal under the armpits.</p>
                    </div>
                    <div class="bg-gray-50 rounded-2xl p-4">
                        <div class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-2">Shoulders</div>
                        <p class="text-xs text-gray-600 leading-relaxed">Measure from the edge of one shoulder across the back to the edge of the other shoulder.</p>
                    </div>
                    <div class="bg-gray-50 rounded-2xl p-4">
                        <div class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-2">Length</div>
                        <p class="text-xs text-gray-600 leading-relaxed">Measure from the highest point of the shoulder, straight down to the desired hemline.</p>
                    </div>
                    <div class="bg-gray-50 rounded-2xl p-4">
                        <div class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-2">Sleeve</div>
                        <p class="text-xs text-gray-600 leading-relaxed">Measure from the shoulder seam to the end of the cuff with your arm slightly bent.</p>
                    </div>
                </div>

                <!-- Note -->
                <div class="text-center pb-2">
                    <p class="text-[10px] text-gray-400 font-medium">Sizes may vary slightly between artisans. When in doubt, size up. Contact the artisan for custom sizing.</p>
                </div>

            </div>
        </div>
    </div>
    <!-- ========================================= -->

</div>

@push('scripts')
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('productDetail', (defaultStock, sizeStocks, variations) => ({
            selectedSize: '', 
            quantity: 1, 
            defaultStock: defaultStock,
            stock: defaultStock, 
            sizeStocks: sizeStocks,
            activeImage: 0,
            variations: variations,
            selectedVariation: 0,
            showSizeGuide: false,
            selectedColorName: 'Off-White',
            isWishlisted: @json(auth()->check() && \App\Models\Wishlist::where('user_id', auth()->id())->where('product_id', $product->id)->exists()),
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
                chest: '',
                shoulder: '',
                sleeves: '',
                waist: '',
                fullLength: '',
                notes: ''
            },
            effectiveSize() {
                if (!this.selectedSize) return '';
                if (this.selectedSize === 'Custom' || this.selectedSize.toLowerCase().includes('custom')) {
                    const parts = [];
                    if (this.customMeasurements.neck) parts.push('Neck: ' + this.customMeasurements.neck);
                    if (this.customMeasurements.chest) parts.push('Chest: ' + this.customMeasurements.chest);
                    if (this.customMeasurements.shoulder) parts.push('Shoulder: ' + this.customMeasurements.shoulder);
                    if (this.customMeasurements.sleeves) parts.push('Sleeves: ' + this.customMeasurements.sleeves);
                    if (this.customMeasurements.waist) parts.push('Waist: ' + this.customMeasurements.waist);
                    if (this.customMeasurements.fullLength) parts.push('Full Length: ' + this.customMeasurements.fullLength);
                    if (this.customMeasurements.notes) parts.push('Notes: ' + this.customMeasurements.notes);
                    return 'Custom (' + (parts.length > 0 ? parts.join(', ') : 'Tailored Sizing') + ')';
                }
                return this.selectedSize;
            },
            async toggleWishlist() {
                if (!window.isLoggedIn) {
                    window.location.href = window.loginUrl + '?next=wishlist';
                    return;
                }
                try {
                    const res = await fetch('/wishlist/toggle', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify({ product_id: {{ $product->id }} })
                    });
                    const data = await res.json();
                    this.isWishlisted = data.status === 'added';
                    if (window.Alpine && Alpine.store('toast')) {
                        Alpine.store('toast').trigger(data.message, data.status === 'added' ? 'success' : 'info');
                    }
                } catch(e) {
                    this.isWishlisted = !this.isWishlisted;
                }
            },
            imageUrl(url) {
                if (!url) return '/uploads/products/default.jpg';
                if (url.startsWith('http')) return url;
                if (url.startsWith('products/')) return '/storage/' + url;
                if (url.startsWith('uploads/')) return '/' + url;
                if (url.startsWith('/uploads/') || url.startsWith('/storage/')) return url;
                return '/uploads/products/' + url;
            },
            selectedVariationLabel() {
                return this.variations[this.selectedVariation]?.label || 'Original';
            },
            updateStock(size) {
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
            async submitAddToCart(e) {
                if (!window.isLoggedIn) {
                    window.location.href = window.loginUrl + '?next=cart';
                    return;
                }
                try {
                    const formData = new FormData(e.target);
                    const response = await fetch('/cart/add', {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: formData
                    });
                    if (response.ok) {
                        const data = await response.json();
                        if (data.success) {
                            window.dispatchEvent(new CustomEvent('cart-updated', { detail: data }));
                            Alpine.store('toast').trigger('Product successfully added to cart!', 'success');
                        }
                    } else {
                        const errData = await response.json();
                        Alpine.store('toast').trigger(errData.message || 'Failed to add item to cart.', 'error');
                    }
                } catch(err) {
                    Alpine.store('toast').trigger('Something went wrong. Please try again.', 'error');
                }
            }
        }));
    });
</script>
@endpush
@endsection
