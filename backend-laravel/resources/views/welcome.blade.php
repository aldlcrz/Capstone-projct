@extends('layouts.app')

@section('content')
<div id="page-data"
    data-logged-in="{{ auth()->check() ? 'true' : 'false' }}"
    data-login-url="{{ route('login') }}"
    style="display:none;" aria-hidden="true">
</div>
<script>
    var _pd = document.getElementById('page-data').dataset;
    window.isLoggedIn = _pd.loggedIn === 'true';
    window.loginUrl   = _pd.loginUrl;

    // Guard: redirect guests to login instead of opening the Quick-Add modal
    window.openQuickAdd = function(detail) {
        if (!window.isLoggedIn) {
            window.location.href = window.loginUrl + '?next=cart';
            return;
        }
        window.dispatchEvent(new CustomEvent('open-quick-add', { detail: detail }));
    };
</script>
<div class="space-y-10">

    {{-- ====== Hero Banner ====== --}}
    @if(!request('search') && !request('category'))
        @if(isset($banners) && $banners->isNotEmpty())
            <!-- Dynamic Hero Banner Slider -->
            <div 
                x-data="{
                    activeSlide: 0,
                    slidesCount: {{ $banners->count() }},
                    autoplayInterval: null,
                    initAutoplay() {
                        this.autoplayInterval = setInterval(() => {
                            this.activeSlide = (this.activeSlide + 1) % this.slidesCount;
                        }, 6000);
                    },
                    stopAutoplay() {
                        if (this.autoplayInterval) {
                            clearInterval(this.autoplayInterval);
                        }
                    }
                }"
                x-init="initAutoplay()"
                @mouseenter="stopAutoplay()"
                @mouseleave="initAutoplay()"
                class="relative rounded-3xl overflow-hidden min-h-[260px] lg:min-h-[340px] flex items-center bg-gray-950 shadow-md group"
            >
                <!-- Slides container -->
                <div class="relative w-full h-full min-h-[260px] lg:min-h-[340px]">
                    @foreach($banners as $index => $banner)
                        <div 
                            x-show="activeSlide === {{ $index }}"
                            x-transition:enter="transition ease-out duration-1000 transform"
                            x-transition:enter-start="opacity-0 translate-x-4"
                            x-transition:enter-end="opacity-100 translate-x-0"
                            x-transition:leave="transition ease-in duration-1000 transform absolute inset-0"
                            x-transition:leave-start="opacity-100 translate-x-0"
                            x-transition:leave-end="opacity-0 -translate-x-4"
                            class="w-full h-full min-h-[260px] lg:min-h-[340px] flex items-center"
                            :class="{ 'hidden': activeSlide !== {{ $index }} }"
                        >
                            <!-- Slide Background Image with dark overlay -->
                            <div class="absolute inset-0">
                                <img src="{{ $banner->getImageUrl() }}" class="w-full h-full object-cover" alt="{{ $banner->title }}">
                                <div class="absolute inset-0" style="background: linear-gradient(to right, rgba(0, 0, 0, 0.85) 0%, rgba(0, 0, 0, 0.4) 50%, transparent 100%);"></div>
                            </div>

                            <!-- Slide Content -->
                            <div class="relative px-8 lg:px-16 py-12 flex flex-col lg:flex-row items-center gap-8 w-full z-10">
                                <div class="grow">
                                    @if($banner->title)
                                        <h2 class="font-serif text-3xl lg:text-5xl font-bold text-white leading-tight mb-4">
                                            {!! nl2br(e($banner->title)) !!}
                                        </h2>
                                    @endif
                                    @if($banner->subtitle)
                                        <p class="text-[#E3D9C9] text-sm leading-relaxed max-w-lg mb-8">
                                            {{ $banner->subtitle }}
                                        </p>
                                    @endif
                                    <div class="flex flex-wrap gap-3">
                                        @if($banner->button_text_1 && $banner->button_url_1)
                                            <a href="{{ $banner->button_url_1 }}" class="px-6 py-3 bg-[#C0422A] text-[#ffffff] text-xs font-black uppercase tracking-widest rounded-full hover:bg-white hover:text-black transition-all">
                                                {{ $banner->button_text_1 }}
                                            </a>
                                        @endif
                                        @if($banner->button_text_2 && $banner->button_url_2)
                                            <a href="{{ $banner->button_url_2 }}" class="px-6 py-3 border border-white/30 text-white text-xs font-black uppercase tracking-widest rounded-full hover:bg-white/10 transition-all">
                                                {{ $banner->button_text_2 }}
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Slide Indicators / Pagination Dots -->
                @if($banners->count() > 1)
                    <div class="absolute bottom-4 left-1/2 transform -translate-x-1/2 z-20 flex gap-2">
                        <template x-for="i in slidesCount" :key="i">
                            <button 
                                @click="activeSlide = i - 1"
                                class="w-2 h-2 rounded-full transition-all duration-300"
                                :class="activeSlide === (i - 1) ? 'bg-white w-6' : 'bg-white/40 hover:bg-white/60'"
                            ></button>
                        </template>
                    </div>

                    <!-- Navigation Arrows (Hidden on mobile, hover visible on desktop) -->
                    <button 
                        @click="activeSlide = (activeSlide - 1 + slidesCount) % slidesCount"
                        class="absolute left-4 w-10 h-10 rounded-full bg-black/20 hover:bg-black/60 text-white items-center justify-center transition-all z-20 focus:outline-none backdrop-blur-sm opacity-0 group-hover:opacity-100 hidden md:flex"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    </button>
                    <button 
                        @click="activeSlide = (activeSlide + 1) % slidesCount"
                        class="absolute right-4 w-10 h-10 rounded-full bg-black/20 hover:bg-black/60 text-white items-center justify-center transition-all z-20 focus:outline-none backdrop-blur-sm opacity-0 group-hover:opacity-100 hidden md:flex"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </button>
                @endif
            </div>
        @else
            <!-- Default Static Hero Banner Fallback -->
            <div class="relative bg-[#2A2A28] rounded-3xl overflow-hidden min-h-[260px] lg:min-h-[320px] flex items-center">
                {{-- Decorative pattern --}}
                <div class="absolute inset-0 opacity-[0.04]" style="background-image: url('data:image/svg+xml,%3Csvg width=\'60\' height=\'60\' viewBox=\'0 0 60 60\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cg fill=\'none\' fill-rule=\'evenodd\'%3E%3Cg fill=\'%23ffffff\' fill-opacity=\'1\'%3E%3Cpath d=\'M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z\'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E');"></div>

                {{-- Accent gradient --}}
                <div class="absolute right-0 top-0 w-1/2 h-full pointer-events-none" style="background: linear-gradient(to left, rgba(192, 66, 42, 0.2), transparent);"></div>

                <div class="relative px-8 lg:px-16 py-12 flex flex-col lg:flex-row items-center gap-8 w-full">
                    <div class="flex-1">
                        <div class="flex items-center gap-2 mb-4">
                            <div class="w-8 h-[1.5px] bg-[#C0422A]"></div>
                            <span class="text-[10px] font-black uppercase tracking-[0.3em] text-[#C0422A]">Lumban, Laguna · Est. Heritage</span>
                        </div>
                        <h1 class="font-serif text-3xl lg:text-5xl font-bold text-white leading-tight mb-4">
                            Handcrafted <em class="text-[#E3D9C9] not-italic">Barong</em><br>
                            <span class="text-white">Tagalog Artistry</span>
                        </h1>
                        <p class="text-[#A89880] text-sm leading-relaxed max-w-sm mb-8">
                            Discover premium hand-embroidered pieces crafted by master artisans from the weaving capital of the Philippines.
                        </p>
                        <div class="flex flex-wrap gap-3">
                            <a href="/?category=Men" class="px-6 py-3 bg-white text-black text-xs font-black uppercase tracking-widest rounded-full hover:bg-[#C0422A] hover:text-white transition-all">Shop Men</a>
                            <a href="/?category=Women" class="px-6 py-3 border border-white/30 text-white text-xs font-black uppercase tracking-widest rounded-full hover:bg-white/10 transition-all">Shop Women</a>
                        </div>
                    </div>
                    <div class="shrink-0 hidden lg:flex items-center justify-end gap-3">
                        <div class="text-right">
                            <div class="text-[#A89880] text-[10px] uppercase tracking-widest font-bold mb-1">Trusted By</div>
                            <div class="text-white text-3xl font-black">500+</div>
                            <div class="text-[#A89880] text-xs">Happy Customers</div>
                        </div>
                        <div class="w-px h-12 bg-white/10"></div>
                        <div class="text-right">
                            <div class="text-[#A89880] text-[10px] uppercase tracking-widest font-bold mb-1">Artisans</div>
                            <div class="text-white text-3xl font-black">50+</div>
                            <div class="text-[#A89880] text-xs">Master Weavers</div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    @endif

    {{-- ====== Filters & Search ====== --}}
    <div class="space-y-3">
        <!-- Demographics & Search Bar -->
        <div class="flex flex-wrap items-center gap-3">
            <div class="flex items-center gap-2 overflow-x-auto no-scrollbar py-1">
                <a href="/" class="px-5 py-2.5 rounded-full border text-sm font-bold transition-all {{ !request('category') ? 'bg-black text-white border-black' : 'bg-white text-gray-600 border-gray-200 hover:border-black hover:text-black' }}">All</a>
                <a href="/?category=Men" class="px-5 py-2.5 rounded-full border text-sm font-bold transition-all {{ in_array(request('category'), ['Male', 'Men']) ? 'bg-black text-white border-black' : 'bg-white text-gray-600 border-gray-200 hover:border-black hover:text-black' }}">Men</a>
                <a href="/?category=Women" class="px-5 py-2.5 rounded-full border text-sm font-bold transition-all {{ in_array(request('category'), ['Female', 'Women']) ? 'bg-black text-white border-black' : 'bg-white text-gray-600 border-gray-200 hover:border-black hover:text-black' }}">Women</a>
                <a href="/?category=Kids" class="px-5 py-2.5 rounded-full border text-sm font-bold transition-all {{ request('category') == 'Kids' ? 'bg-black text-white border-black' : 'bg-white text-gray-600 border-gray-200 hover:border-black hover:text-black' }}">Kids</a>
            </div>

            <div class="h-8 w-px bg-gray-200 mx-1 hidden md:block"></div>

            <form action="/" method="GET" class="flex-1 min-w-[240px] relative">
                @if(request('category'))
                    <input type="hidden" name="category" value="{{ request('category') }}">
                @endif
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="Search artisan pieces..."
                       class="w-full bg-white border border-gray-200 rounded-full py-2.5 pl-10 pr-5 text-sm focus:outline-none focus:border-black transition-all shadow-sm">
                <svg class="w-4 h-4 absolute left-4 top-1/2 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </form>

            @if(request('search'))
                <div class="text-xs text-gray-400 font-medium">
                    Results for <span class="font-bold text-black">"{{ request('search') }}"</span>
                    <a href="{{ request('category') ? '/?category='.request('category') : '/' }}" class="ml-2 text-[#C0422A] hover:underline">Clear</a>
                </div>
            @endif
        </div>

        <!-- Product Categories Row -->
        @if(isset($categories) && $categories->isNotEmpty())
            <div class="flex items-center gap-2 overflow-x-auto no-scrollbar pb-2 pt-1 border-t border-gray-100">
                <span class="text-[10px] font-black uppercase tracking-widest text-gray-400 shrink-0 mr-1">Categories:</span>
                @foreach($categories as $cat)
                    @php
                        $isActiveCategory = request('category') == $cat->id || strtolower(request('category')) == strtolower($cat->name);
                    @endphp
                    <a href="/?category={{ urlencode($cat->id) }}"
                       class="px-4 py-1.5 rounded-full border text-xs font-semibold transition-all shrink-0 {{ $isActiveCategory ? 'bg-[#C0422A] text-white border-[#C0422A] shadow-sm font-bold' : 'bg-white text-gray-700 border-gray-200 hover:border-[#C0422A] hover:text-[#C0422A]' }}">
                        {{ $cat->name }}
                    </a>
                @endforeach
            </div>
        @endif
    </div>

    @php
        $discounted = $products->filter(fn($p) => $p->is_on_sale && $p->discount_percentage > 0);
        $regular = $products->reject(fn($p) => $p->is_on_sale && $p->discount_percentage > 0);
        $catReq = request('category');
        $activeCatObj = isset($categories) && $catReq ? $categories->first(fn($c) => $c->id == $catReq || strtolower($c->name) == strtolower($catReq)) : null;
        $displayCatName = $activeCatObj ? $activeCatObj->name : $catReq;
    @endphp

    {{-- ====== Lumban Specials & Discounted Section ====== --}}
    @if($discounted->isNotEmpty())
        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-6 h-[1.5px] bg-[#C0422A]"></div>
                    <span class="text-[10px] font-black uppercase tracking-widest text-[#C0422A] flex items-center gap-2">
                        Lumban Specials & Discounted Products
                        <span class="px-2 py-0.5 bg-[#C0422A]/10 text-[#C0422A] text-[9px] font-bold rounded-md">{{ $discounted->count() }} specials</span>
                    </span>
                </div>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-5">
                @foreach($discounted as $product)
                @php
                    $pSizes = is_string($product->sizes) ? json_decode($product->sizes, true) : $product->sizes;
                    if (empty($pSizes)) {
                        $pSizes = ['S', 'M', 'L', 'XL'];
                    }
                @endphp
                <div class="group cursor-pointer"
                     onclick="if(!event.target.closest('button') && !event.target.closest('form') && !event.target.closest('a')) window.location.href='/products/{{ $product->id }}'">
                    <div class="aspect-4/5 bg-gray-100 rounded-2xl overflow-hidden mb-3 relative shadow-sm">
                        <img src="{{ $product->getImageUrl() }}"
                             alt="{{ $product->name }}"
                             class="w-full h-full object-cover object-top group-hover:scale-105 transition-transform duration-500 ease-out">

                        {{-- Hover overlay --}}
                        <div class="absolute inset-x-0 bottom-0 p-3 translate-y-1 opacity-0 group-hover:opacity-100 group-hover:translate-y-0 transition-all duration-300">
                            <button type="button" 
                                    data-id="{{ $product->id }}"
                                    data-name="{{ $product->name }}"
                                    data-price="{{ $product->sale_price }}"
                                    data-image="{{ $product->getImageUrl() }}"
                                    data-sizes='{!! json_encode($pSizes) !!}'
                                    data-size-stocks='{!! json_encode($product->size_stocks ?? (object)[]) !!}'
                                    data-default-stock="{{ $product->stock ?? 1 }}"
                                    onclick="event.stopPropagation(); window.openQuickAdd({ id: this.dataset.id, name: this.dataset.name, price: this.dataset.price, image: this.dataset.image, sizes: JSON.parse(this.dataset.sizes), sizeStocks: JSON.parse(this.dataset.sizeStocks), defaultStock: parseInt(this.dataset.defaultStock) })"
                                    class="w-full bg-black/90 backdrop-blur-sm text-white py-2 rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-[#C0422A] transition-colors shadow-lg">
                                + Add to Cart
                            </button>
                        </div>

                        {{-- Lumban Special sale badge --}}
                        <div class="absolute top-2.5 left-2.5 flex flex-col gap-1">
                            <div class="flex items-center gap-1 bg-[#C0422A] text-white px-2 py-0.5 rounded-full shadow-lg">
                                <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-5 5a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 10V5a2 2 0 012-2z"/>
                                </svg>
                                <span class="text-[8px] font-black uppercase tracking-widest">Lumban Special</span>
                            </div>
                            <div class="bg-black/80 text-white text-[8px] font-black px-2 py-0.5 rounded-full w-fit">
                                -{{ number_format($product->discount_percentage, 0) }}% OFF
                            </div>
                        </div>
                    </div>
                    <a href="/products/{{ $product->id }}" class="block">
                        <h3 class="font-bold text-sm text-gray-900 group-hover:text-[#C0422A] transition-colors leading-tight line-clamp-2">{{ $product->name }}</h3>
                    </a>
                    @if($product->avgRating)
                        <div class="flex items-center gap-1 text-[10px] font-bold text-yellow-500 mt-1">
                            <span>★</span>
                            <span>{{ number_format($product->avgRating, 1) }}</span>
                            <span class="text-gray-400">({{ $product->reviewCount }})</span>
                        </div>
                    @endif
                    <div class="flex items-center gap-2 mt-1">
                        <p class="text-sm font-black text-[#C0422A]">₱{{ number_format($product->salePrice) }}</p>
                        <p class="text-xs font-bold text-gray-400 line-through">₱{{ number_format($product->price) }}</p>
                    </div>
                    @if($product->artisan)
                        <p class="text-[10px] text-gray-400 mt-0.5 font-medium flex items-center gap-1">
                            by {{ $product->artisan }}
                            @if($product->seller && $product->seller->isPremiumActive())
                                <span class="text-yellow-500 font-extrabold text-[9px]" title="Premium Seller">👑</span>
                            @endif
                        </p>
                    @endif
                </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- ====== Standard Catalogue & Recommendations ====== --}}
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-6 h-[1.5px] bg-[#2A2A28]"></div>
                <span class="text-[10px] font-black uppercase tracking-widest text-[#2A2A28]">
                    {{ $displayCatName ? $displayCatName.' Collection' : (request('search') ? 'Search Results' : 'Recommended Products') }}
                </span>
            </div>
            <span class="text-[10px] text-gray-400 font-bold">{{ $regular->count() }} {{ Str::plural('piece', $regular->count()) }}</span>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-5">
            @foreach($regular as $product)
            @php
                $pSizes = is_string($product->sizes) ? json_decode($product->sizes, true) : $product->sizes;
                if (empty($pSizes)) {
                    $pSizes = ['S', 'M', 'L', 'XL'];
                }
            @endphp
            <div class="group cursor-pointer"
                 onclick="if(!event.target.closest('button') && !event.target.closest('form') && !event.target.closest('a')) window.location.href='/products/{{ $product->id }}'">
                <div class="aspect-4/5 bg-gray-100 rounded-2xl overflow-hidden mb-3 relative shadow-sm">
                    <img src="{{ $product->getImageUrl() }}"
                         alt="{{ $product->name }}"
                         class="w-full h-full object-cover object-top group-hover:scale-105 transition-transform duration-500 ease-out">

                    {{-- Hover overlay --}}
                    <div class="absolute inset-x-0 bottom-0 p-3 translate-y-1 opacity-0 group-hover:opacity-100 group-hover:translate-y-0 transition-all duration-300">
                        <button type="button" 
                                data-id="{{ $product->id }}"
                                data-name="{{ $product->name }}"
                                data-price="{{ $product->sale_price }}"
                                data-image="{{ $product->getImageUrl() }}"
                                data-sizes='{!! json_encode($pSizes) !!}'
                                data-size-stocks='{!! json_encode($product->size_stocks ?? (object)[]) !!}'
                                data-default-stock="{{ $product->stock ?? 1 }}"
                                onclick="event.stopPropagation(); window.openQuickAdd({ id: this.dataset.id, name: this.dataset.name, price: this.dataset.price, image: this.dataset.image, sizes: JSON.parse(this.dataset.sizes), sizeStocks: JSON.parse(this.dataset.sizeStocks), defaultStock: parseInt(this.dataset.defaultStock) })"
                                class="w-full bg-black/90 backdrop-blur-sm text-white py-2 rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-[#C0422A] transition-colors shadow-lg">
                            + Add to Cart
                        </button>
                    </div>

                    @if($product->target_group)
                        <div class="absolute top-2.5 left-2.5 bg-white/90 backdrop-blur-sm text-[8px] font-black uppercase tracking-widest text-gray-500 px-2 py-0.5 rounded-full">
                            {{ $product->target_group }}
                        </div>
                    @endif
                </div>
                <a href="/products/{{ $product->id }}" class="block">
                    <h3 class="font-bold text-sm text-gray-900 group-hover:text-[#C0422A] transition-colors leading-tight line-clamp-2">{{ $product->name }}</h3>
                </a>
                @if($product->avgRating)
                    <div class="flex items-center gap-1 text-[10px] font-bold text-yellow-500 mt-1">
                        <span>★</span>
                        <span>{{ number_format($product->avgRating, 1) }}</span>
                        <span class="text-gray-400">({{ $product->reviewCount }})</span>
                    </div>
                @endif
                <p class="text-sm font-black text-gray-800 mt-1">₱{{ number_format($product->price) }}</p>
                @if($product->artisan)
                    <p class="text-[10px] text-gray-400 mt-0.5 font-medium flex items-center gap-1">
                        by {{ $product->artisan }}
                        @if($product->seller && $product->seller->isPremiumActive())
                            <span class="text-yellow-500 font-extrabold text-[9px]" title="Premium Seller">👑</span>
                        @endif
                    </p>
                @endif
            </div>
            @endforeach
        </div>
    </div>


    @if($products->isEmpty())
        <div class="text-center py-24 bg-white rounded-3xl border border-dashed border-gray-200">
            <div class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-4 border border-gray-100">
                <svg class="w-8 h-8 text-gray-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <h3 class="text-sm font-bold text-black uppercase tracking-widest mb-1">No Products Found</h3>
            <p class="text-xs text-gray-400 mb-6">Try a different search term or browse all collections.</p>
            <a href="/" class="px-6 py-2.5 bg-black text-white text-xs font-bold uppercase tracking-widest rounded-full hover:bg-gray-800 transition-all">View All Products</a>
        </div>
    @endif

    <div class="mt-4">
        {{ $products->links() }}
    </div>

    {{-- ====== Quick Add to Cart Modal ====== --}}
    <div 
        x-data="quickAddModal"
        @keydown.escape.window="open = false"
    >
        {{-- 
            x-if completely removes the modal from the DOM when closed.
            This prevents any invisible overlay from blocking page clicks.
        --}}
        <template x-if="open">
            {{-- Backdrop --}}
            <div 
                class="fixed inset-0 z-[9999] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm"
                @click="open = false"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
            >
                {{-- Modal Panel — click.stop prevents backdrop from closing when clicking inside --}}
                <div 
                    @click.stop
                    class="bg-white w-full max-w-md rounded-[28px] border border-gray-100 shadow-2xl p-6 relative overflow-hidden"
                    x-transition:enter="transition ease-out duration-300 transform"
                    x-transition:enter-start="opacity-0 scale-95 translate-y-4"
                    x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                    x-transition:leave="transition ease-in duration-200 transform"
                    x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                    x-transition:leave-end="opacity-0 scale-95 translate-y-4"
                >
                    {{-- Close Button --}}
                    <button 
                        @click="open = false"
                        class="absolute top-4 right-4 w-8 h-8 flex items-center justify-center rounded-full text-gray-400 hover:text-black hover:bg-gray-100 transition-colors"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>

                    {{-- Product Summary Header --}}
                    <div class="flex gap-4 items-start mb-6">
                        <div class="w-20 h-24 rounded-2xl bg-gray-50 overflow-hidden border border-gray-100 shrink-0">
                            <img :src="product.image" class="w-full h-full object-cover object-top" alt="">
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="text-[9px] font-bold uppercase tracking-[0.2em] text-[#C0422A] mb-1">Quick Add</div>
                            <h3 class="font-serif text-lg font-bold text-gray-900 leading-tight mb-1 truncate" x-text="product.name"></h3>
                            <div class="text-sm font-black text-[#C0422A] mb-2" x-text="'₱' + Number(product.price).toLocaleString()"></div>
                        </div>
                    </div>

                    <div class="space-y-6">
                        {{-- Size Selection --}}
                        <div x-show="product.sizes && product.sizes.length > 0">
                            <div class="flex items-center justify-between mb-3">
                                <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Select Size</span>
                                <span x-show="selectedSize" class="text-[10px] text-gray-500 font-semibold">
                                    <span x-text="stock"></span> pieces available
                                </span>
                            </div>
                            
                            <div class="flex flex-wrap gap-2">
                                <template x-for="size in product.sizes" :key="size">
                                    <button 
                                        @click="selectSize(size)"
                                        type="button"
                                        class="relative w-12 h-12 rounded-xl flex items-center justify-center text-xs font-bold border transition-all shadow-sm"
                                        :class="selectedSize === size ? 'border-black bg-black text-white' : 'border-gray-200 text-gray-700 bg-white hover:border-black'"
                                        :disabled="product.sizeStocks && product.sizeStocks[size] !== undefined && parseInt(product.sizeStocks[size]) <= 0"
                                    >
                                        <span :class="product.sizeStocks && product.sizeStocks[size] !== undefined && parseInt(product.sizeStocks[size]) <= 0 ? 'text-gray-300 line-through font-normal' : ''" x-text="size"></span>
                                        <span x-show="product.sizeStocks && product.sizeStocks[size] !== undefined && parseInt(product.sizeStocks[size]) <= 0" class="absolute bottom-1 text-[7px] text-red-500 font-extrabold uppercase scale-90">Out</span>
                                    </button>
                                </template>
                            </div>
                        </div>

                        {{-- Quantity Selection --}}
                        <div>
                            <div class="flex items-center justify-between mb-3">
                                <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Quantity</span>
                                <span x-show="!product.sizes || product.sizes.length === 0" class="text-[10px] text-gray-500 font-semibold">
                                    <span x-text="stock"></span> pieces available
                                </span>
                            </div>
                            <div class="flex items-center gap-4">
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
                                        @input="if(quantity > stock) quantity = stock; if(quantity < 1 || !quantity) quantity = 1;"
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
                            </div>
                        </div>
                    </div>

                    {{-- Add to Cart Form --}}
                    <div class="mt-8">
                        <form action="/cart/add" method="POST" @submit.prevent="submitAddToCart($event)">
                            @csrf
                            <input type="hidden" name="productId" :value="product.id">
                            <input type="hidden" name="size" :value="selectedSize">
                            <input type="hidden" name="quantity" :value="quantity">
                            
                            <button 
                                type="submit"
                                :disabled="(product.sizes && product.sizes.length > 0 && !selectedSize) || stock <= 0"
                                class="w-full h-12 bg-[#2A2A28] text-white rounded-xl font-bold uppercase tracking-widest hover:bg-[#C0422A] transition-all shadow-lg shadow-black/10 disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                                </svg>
                                <span x-text="(product.sizes && product.sizes.length > 0 && !selectedSize) ? 'Select Size' : (stock <= 0 ? 'Out of Stock' : 'Add to Cart')"></span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </template>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('quickAddModal', () => ({
            open: false,
            product: {
                id: '',
                name: '',
                price: 0,
                image: '',
                sizes: [],
                sizeStocks: {},
                defaultStock: 0
            },
            selectedSize: '',
            quantity: 1,
            stock: 1,
            
            init() {
                window.addEventListener('open-quick-add', (e) => {
                    this.product = e.detail;
                    this.selectedSize = '';
                    this.quantity = 1;
                    this.stock = this.product.defaultStock || 1;
                    
                    if (!this.product.sizes || this.product.sizes.length === 0) {
                        this.stock = this.product.defaultStock || 1;
                    }
                    this.open = true;
                });
            },
            
            selectSize(size) {
                this.selectedSize = size;
                if (this.product.sizeStocks && this.product.sizeStocks[size] !== undefined) {
                    this.stock = parseInt(this.product.sizeStocks[size]) || 0;
                } else {
                    this.stock = this.product.defaultStock || 1;
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
                            this.open = false;
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
