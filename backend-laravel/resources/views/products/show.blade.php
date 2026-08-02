@extends('layouts.app')

@section('content')
@php
    use App\Support\VariationFormatter;
    $productVariations = VariationFormatter::buildVariations($product->image);
@endphp
<script>
    window.isLoggedIn = {{ auth()->check() ? 'true' : 'false' }};
    window.loginUrl   = '{{ route("login") }}';

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
<div class="w-full py-4 lg:py-8" x-data="productDetail({{ $product->stock ?? 1 }}, {{ json_encode($product->size_stocks ?? (object)[]) }}, {{ json_encode($productVariations) }})">
    <!-- Back Button -->
    <div class="mb-6">
        <a href="/" class="inline-flex items-center gap-2 text-[10px] font-bold text-gray-400 uppercase tracking-widest hover:text-black transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
            Back to catalogue
        </a>
    </div>

    <!-- Product Detail Container Card -->
    <div class="bg-white rounded-[32px] border border-gray-100 shadow-sm overflow-hidden p-6 lg:p-12">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-16">
            
            <!-- Left Side: Product Images Gallery -->
            <div class="lg:col-span-5 flex flex-col space-y-6">
                <!-- Main Image Box with Tag -->
                <div 
                    class="relative aspect-4/5 bg-gray-50 rounded-2xl overflow-hidden border border-gray-100 shadow-sm group cursor-zoom-in"
                    x-data="{
                        isZoomed: false,
                        originX: 50,
                        originY: 50,
                        handleMouseMove(e) {
                            const rect = e.currentTarget.getBoundingClientRect();
                            const x = ((e.clientX - rect.left) / rect.width) * 100;
                            const y = ((e.clientY - rect.top) / rect.height) * 100;
                            this.originX = x.toFixed(2);
                            this.originY = y.toFixed(2);
                            this.isZoomed = true;
                        },
                        handleMouseLeave() {
                            this.isZoomed = false;
                        }
                    }"
                    @mousemove="handleMouseMove($event)"
                    @mouseleave="handleMouseLeave()"
                >
                    <!-- Brand / Artisan Badge -->
                    <div class="absolute top-4 left-4 z-10 flex items-center gap-1.5 px-3 py-1.5 bg-[#2E2E2C]/85 backdrop-blur-sm text-[#E3D9C9] font-black uppercase tracking-widest text-[9px] rounded-xl shadow-lg shadow-black/10">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        <span>{{ strtoupper($product->artisan ?? 'Lumban Artisan Craft') }}</span>
                        @if($product->seller && $product->seller->isPremiumActive())
                            <span class="text-yellow-400 font-extrabold ml-1 animate-pulse" title="Premium Artisan">👑</span>
                        @endif
                    </div>

                    <!-- Lumban Special sale badge -->
                    @if($product->is_on_sale && $product->discount_percentage > 0)
                        <div class="absolute top-4 right-4 z-10 flex flex-col items-end gap-1.5">
                            <div class="flex items-center gap-1 bg-[#C0420A] text-white px-2.5 py-1 rounded-xl shadow-lg">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-5 5a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 10V5a2 2 0 012-2z"/>
                                </svg>
                                <span class="text-[9px] font-black uppercase tracking-widest">Lumban Special</span>
                            </div>
                            <div class="bg-black/80 text-white text-[9px] font-black px-2 py-0.5 rounded-md shadow-md">
                                -{{ number_format($product->discount_percentage, 0) }}% OFF
                            </div>
                        </div>
                    @endif

                    <!-- Main Image Display -->
                    <template x-for="(variation, index) in variations" :key="index">
                        <img 
                            x-show="activeImage === index"
                            :src="imageUrl(variation.url)"
                            class="w-full h-full object-cover object-top"
                            :class="isZoomed ? 'scale-[2.2] transition-transform duration-100 ease-out' : 'scale-100 transition-transform duration-300 ease-out'"
                            :style="isZoomed ? { transformOrigin: `${originX}% ${originY}%` } : {}"
                            alt="{{ $product->name }}"
                        >
                    </template>
                </div>
                
                <!-- Gallery Thumbnails -->
                <div class="flex gap-3 overflow-x-auto pb-1 no-scrollbar">
                    <template x-for="(variation, index) in variations" :key="index">
                        <button 
                            @click="activeImage = index; selectedVariation = index"
                            class="relative w-20 h-24 rounded-xl overflow-hidden shrink-0 border-2 transition-all shadow-sm"
                            :class="activeImage === index ? 'border-black opacity-100 scale-[0.98]' : 'border-transparent opacity-60 hover:opacity-100'"
                        >
                            <img :src="imageUrl(variation.url)" class="w-full h-full object-cover">
                        </button>
                    </template>
                </div>
            </div>

            <!-- Right Side: Details & Selectors -->
            <div class="lg:col-span-7 flex flex-col justify-between">
                <div>
                    <!-- Product Category Badge -->
                    <div class="text-[10px] font-bold uppercase tracking-[0.25em] text-[#C0422A] mb-3 flex items-center gap-1.5">
                        <span class="w-4 h-[1.5px] bg-[#C0422A]"></span>
                        {{ $product->category->name ?? 'TRADITIONAL' }}
                    </div>

                    <!-- Product Title -->
                    <h1 class="font-serif text-3xl lg:text-4xl font-bold text-gray-900 mb-3 leading-tight tracking-tight">
                        {{ $product->name }}
                    </h1>

                    <!-- Ratings and Sold stats -->
                    <div class="flex items-center gap-4 text-xs font-bold text-gray-400 mb-6 py-2 border-b border-gray-50">
                        <div class="flex items-center gap-1">
                            <span class="text-sm font-black text-[#C0422A]">{{ number_format($product->avgRating ?? 0, 1) }}</span>
                            <div class="flex items-center gap-0.5 text-yellow-400">
                                @php $ratingVal = $product->avgRating ?? 0; @endphp
                                @for($i = 1; $i <= 5; $i++)
                                    <svg class="w-3.5 h-3.5 fill-current {{ $i <= round($ratingVal) ? 'text-yellow-400' : 'text-gray-200' }}" viewBox="0 0 20 20">
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                    </svg>
                                @endfor
                            </div>
                        </div>
                        <span class="w-px h-3 bg-gray-200"></span>
                        <div><span class="text-gray-900">{{ $product->reviewCount ?? 0 }}</span> rating{{ ($product->reviewCount ?? 0) != 1 ? 's' : '' }}</div>
                        <span class="w-px h-3 bg-gray-200"></span>
                        <div><span class="text-gray-900">{{ $soldCount ?? 0 }}</span> sold</div>
                    </div>

                    <!-- Price Box Container -->
                    <div class="bg-[#FDF9F4] border border-[#F5EAD9] p-5 lg:p-6 rounded-2xl mb-8 flex items-center gap-4 flex-wrap">
                        @if($product->is_on_sale && $product->discount_percentage > 0)
                            <span class="text-3xl lg:text-4xl font-extrabold text-[#C0422A] tracking-tight">₱ {{ number_format($product->salePrice) }}</span>
                            <span class="text-lg lg:text-xl font-bold text-gray-400 line-through">₱ {{ number_format($product->price) }}</span>
                            <span class="px-2.5 py-1 bg-[#C0420A] text-white text-[9px] font-black uppercase tracking-widest rounded-lg shadow-sm shadow-[#C0420A]/10">SAVE {{ number_format($product->discount_percentage, 0) }}%</span>
                        @else
                            <span class="text-3xl lg:text-4xl font-extrabold text-[#C0422A] tracking-tight">₱ {{ number_format($product->price) }}</span>
                        @endif
                    </div>

                    <!-- Detail Spec Rows -->
                    <div class="space-y-6 lg:space-y-8 mb-8">
                        
                        <!-- Row 1: Shipping -->
                        <div class="flex items-start gap-4">
                            <div class="w-24 text-[10px] font-bold text-gray-400 uppercase tracking-widest pt-1">Shipping</div>
                            <div class="flex-1 flex flex-col gap-1">
                                <div class="flex items-center gap-2 flex-wrap">
                                    @if(($product->shippingFee ?? 0) > 0)
                                        <span class="text-sm font-black text-gray-800">
                                            ₱{{ number_format($product->shippingFee, 2) }}
                                        </span>
                                        <span class="px-2 py-0.5 bg-gray-100 text-gray-500 text-[9px] font-black uppercase tracking-widest rounded-full">Shipping Fee</span>
                                    @else
                                        <span class="text-sm font-black text-green-600">FREE</span>
                                        <span class="px-2 py-0.5 bg-green-50 text-green-600 text-[9px] font-black uppercase tracking-widest rounded-full">Free Shipping</span>
                                    @endif
                                </div>
                                <div class="text-xs text-gray-400 font-medium">
                                    Est. arrival in <span class="font-bold text-gray-600">{{ $product->shippingDays ?? 5 }} day{{ ($product->shippingDays ?? 5) != 1 ? 's' : '' }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Row 2: Variations -->
                        <div class="flex items-start gap-4">
                            <div class="w-24 text-[10px] font-bold text-gray-400 uppercase tracking-widest pt-2">Variation</div>
                            <div class="grow flex flex-wrap gap-3">
                                <template x-for="(variation, idx) in variations" :key="idx">
                                    <button 
                                        @click="activeImage = idx; selectedVariation = idx"
                                        type="button"
                                        class="flex items-center gap-2 pl-1.5 pr-4 py-1.5 border rounded-xl transition-all text-xs font-bold shadow-sm"
                                        :class="selectedVariation === idx ? 'border-[#C0422A] bg-[#C0422A]/5 text-[#C0422A]' : 'border-gray-200 bg-white text-gray-700 hover:border-gray-400'"
                                    >
                                        <div class="w-6 h-6 rounded bg-gray-50 overflow-hidden shrink-0 border border-gray-100">
                                            <img :src="imageUrl(variation.url)" class="w-full h-full object-cover">
                                        </div>
                                        <span x-text="variation.label"></span>
                                    </button>
                                </template>
                            </div>
                        </div>

                        <!-- Row 3: Sizing with Size Guide -->
                        <div class="flex flex-col space-y-3">
                            <div class="flex items-center justify-between pl-28">
                                <button type="button" @click="showSizeGuide = true" class="inline-flex items-center gap-1 text-[10px] font-bold text-[#C0422A] uppercase tracking-widest hover:underline">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12.248 10.457L19 4m0 0h-5m5 0v5M6 20l6.752-6.457m0 0L19 20m-6.248-6.457L6 4"/>
                                    </svg>
                                    Size Guide
                                </button>
                            </div>

                            <div class="flex items-start gap-4">
                                <div class="w-24 text-[10px] font-bold text-gray-400 uppercase tracking-widest pt-3">Size</div>
                                <div class="grow flex flex-wrap gap-2.5">
                                    @php
                                        $sizes = is_string($product->sizes) ? json_decode($product->sizes, true) : $product->sizes;
                                        if (empty($sizes)) {
                                            $sizes = ['S', 'M', 'L', 'XL'];
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
                                            class="relative w-12 h-12 rounded-xl flex items-center justify-center text-xs font-bold border transition-all shadow-sm"
                                            :class="selectedSize === '{{ $sizeName }}' ? 'border-black bg-black text-white' : 'border-gray-200 text-gray-700 bg-white hover:border-black'"
                                        >
                                            <span class="{{ !$hasSizeStock ? 'text-gray-300 line-through font-normal' : '' }}">{{ $sizeName }}</span>
                                            @if(!$hasSizeStock)
                                                <span class="absolute bottom-1 text-[7px] text-red-500 font-extrabold uppercase scale-90">Out</span>
                                            @endif
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <!-- Row 4: Quantity -->
                        <div class="flex items-center gap-4">
                            <div class="w-24 text-[10px] font-bold text-gray-400 uppercase tracking-widest">Quantity</div>
                            <div class="flex-1 flex items-center gap-4">
                                <!-- Custom Stepper -->
                                <div class="flex items-center border border-gray-200 rounded-xl bg-gray-50 overflow-hidden shadow-sm h-11">
                                    <button 
                                        @click="if(quantity > 1) quantity--" 
                                        type="button" 
                                        class="w-11 h-full flex items-center justify-center text-gray-500 hover:text-black font-bold text-lg hover:bg-gray-100 transition-colors"
                                    >
                                        −
                                    </button>
                                    <input 
                                        type="number" 
                                        x-model.number="quantity" 
                                        min="1" 
                                        :max="stock"
                                        class="w-12 text-center bg-transparent border-0 outline-none text-xs font-bold text-gray-800"
                                    >
                                    <button 
                                        @click="if(quantity < stock) quantity++" 
                                        type="button" 
                                        class="w-11 h-full flex items-center justify-center text-gray-500 hover:text-black font-bold text-lg hover:bg-gray-100 transition-colors"
                                    >
                                        +
                                    </button>
                                </div>
                                <span class="text-xs font-semibold text-gray-400">
                                    <span x-text="stock"></span> pieces available
                                </span>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- Bottom Stacked Action Buttons -->
                <div class="flex flex-col gap-3 mt-8">
                    <!-- Form 1: Add to Cart -->
                    <form action="/cart/add" method="POST" @submit.prevent="submitAddToCart($event)">
                        @csrf
                        <input type="hidden" name="productId" value="{{ $product->id }}">
                        <input type="hidden" name="size" :value="selectedSize">
                        <input type="hidden" name="quantity" :value="quantity">
                        <input type="hidden" name="variation" :value="selectedVariationLabel()">
                        <button 
                            type="submit"
                            :disabled="!selectedSize || stock <= 0"
                            class="w-full h-14 bg-white border border-gray-300 text-gray-800 rounded-xl font-bold uppercase tracking-widest hover:bg-gray-50 hover:text-black transition-all shadow-sm disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                            </svg>
                            <span x-text="!selectedSize ? 'Select Size' : (stock <= 0 ? 'Out of Stock' : 'Add to Cart')"></span>
                        </button>
                    </form>

                    <!-- Form 2: Buy Now -->
                    <form action="/checkout" method="GET">
                        <input type="hidden" name="productId" value="{{ $product->id }}">
                        <input type="hidden" name="size" :value="selectedSize">
                        <input type="hidden" name="quantity" :value="quantity">
                        <input type="hidden" name="variation" :value="selectedVariationLabel()">
                        <input type="hidden" name="direct" value="1">
                        <button 
                            type="submit"
                            :disabled="!selectedSize || stock <= 0"
                            class="w-full h-14 bg-[#2A2A28] text-white rounded-xl font-bold uppercase tracking-widest hover:bg-[#3E3E3C] transition-all shadow-lg shadow-black/10 disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
                        >
                            <span x-text="!selectedSize ? 'Select Size' : (stock <= 0 ? 'Out of Stock' : 'Buy Now')"></span>
                        </button>
                    </form>
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
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-8">
                <div>
                    <div class="flex items-center gap-2 mb-1">
                        <div class="w-5 h-[1.5px] bg-[#C0422A]"></div>
                        <span class="text-[10px] font-bold uppercase tracking-widest text-[#C0422A]">Reviews & Feedback</span>
                    </div>
                    <h2 class="font-serif text-2xl font-bold text-black">Customer Reviews</h2>
                </div>
                
                {{-- Average rating summary badge --}}
                @if(($product->reviewCount ?? 0) > 0)
                    <div class="flex items-center gap-4 bg-[#FDF9F4] border border-[#F5EAD9] px-6 py-3 rounded-2xl self-start md:self-auto">
                        <div class="text-center">
                            <span class="text-3xl font-extrabold text-[#C0422A]">{{ number_format($product->avgRating, 1) }}</span>
                            <span class="text-xs text-gray-400 font-bold block">out of 5</span>
                        </div>
                        <div class="w-px h-10 bg-gray-200"></div>
                        <div>
                            <div class="flex items-center gap-0.5 text-yellow-400">
                                @for($i = 1; $i <= 5; $i++)
                                    <svg class="w-4 h-4 fill-current {{ $i <= round($product->avgRating) ? 'text-yellow-400' : 'text-gray-200' }}" viewBox="0 0 20 20">
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                    </svg>
                                @endfor
                            </div>
                            <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block mt-0.5">{{ $product->reviewCount }} review{{ ($product->reviewCount ?? 0) != 1 ? 's' : '' }}</span>
                        </div>
                    </div>
                @endif
            </div>

            @if($product->reviews && $product->reviews->count() > 0)
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    @foreach($product->reviews as $review)
                        <div class="bg-gray-50/50 border border-gray-100 rounded-2xl p-6 space-y-3">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-xl bg-gray-200 flex items-center justify-center font-bold text-gray-600 text-sm overflow-hidden shrink-0">
                                        @if($review->customer && $review->customer->profilePhoto)
                                            <img src="{{ str_starts_with($review->customer->profilePhoto, 'http') ? $review->customer->profilePhoto : asset($review->customer->profilePhoto) }}" class="w-full h-full object-cover" onerror="this.style.display='none'">
                                        @else
                                            {{ strtoupper(substr($review->customer->name ?? 'A', 0, 1)) }}
                                        @endif
                                    </div>
                                    <div>
                                        <div class="text-xs font-bold text-black">{{ $review->customer->name ?? 'Anonymous Customer' }}</div>
                                        <div class="text-[9px] text-gray-400 font-bold uppercase tracking-widest mt-0.5">{{ $review->createdAt->format('F d, Y') }}</div>
                                    </div>
                                </div>
                                <div class="flex gap-0.5">
                                    @for($i = 1; $i <= 5; $i++)
                                        <svg class="w-3 h-3 fill-current {{ $i <= $review->rating ? 'text-yellow-400' : 'text-gray-200' }}" viewBox="0 0 20 20">
                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                        </svg>
                                    @endfor
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

        <div class="grid grid-cols-2 md:grid-cols-4 gap-5">
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
         class="fixed inset-0 z-200 flex items-center justify-center p-4"
         x-cloak>
        <!-- Backdrop -->
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="showSizeGuide = false"></div>

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
            imageUrl(url) {
                if (!url) return '/uploads/products/default.jpg';
                if (url.startsWith('http')) return url;
                if (url.startsWith('products/')) return '/storage/' + url;
                if (url.startsWith('uploads/')) return '/' + url;
                if (url.startsWith('/uploads/')) return url;
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
