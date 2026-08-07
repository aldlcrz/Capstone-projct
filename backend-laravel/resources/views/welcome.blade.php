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
<div class="space-y-8" x-data="{ categoriesModalOpen: false, topShopsModalOpen: false }">

        {{-- ====== Shop by Category ====== --}}
        <div>
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base sm:text-lg font-extrabold text-gray-900">Shop by Category</h3>
                <button type="button" @click="categoriesModalOpen = true" class="text-xs font-semibold text-amber-700 hover:text-amber-900 hover:underline cursor-pointer">
                    View all
                </button>
            </div>

            @php
                $catItems = [
                    ['name' => 'All Barongs', 'cat' => '__all__', 'img' => '/uploads/categories/featured_best_sellers.png'],
                    ['name' => 'Wedding Barong', 'cat' => 'Wedding Barong', 'img' => '/uploads/categories/wedding_groom.png'],
                    ['name' => 'Piña Formal Barong', 'cat' => 'Piña Formal Barong', 'img' => '/uploads/categories/pina_formal.png'],
                    ['name' => 'Jusi Classic Barong', 'cat' => 'Jusi Classic Barong', 'img' => '/uploads/categories/jusi_classic.png'],
                    ['name' => 'Polo Barong', 'cat' => 'Polo Barong', 'img' => '/uploads/categories/polo_casual.png'],
                    ['name' => 'Filipiniana Gown', 'cat' => 'Filipiniana Gown', 'img' => '/uploads/categories/women_filipiniana.png'],
                    ['name' => 'Modern Terno Top', 'cat' => 'Modern Terno Top', 'img' => '/uploads/categories/women_terno.png'],
                    ['name' => 'Boys\' Barong', 'cat' => 'Boys\' Barong', 'img' => '/uploads/categories/kids_boys.png'],
                    ['name' => 'Accessories', 'cat' => 'Accessories', 'img' => '/uploads/categories/accessories.png'],
                ];

                $allCatItems = [
                    ['name' => 'Wedding Barong', 'cat' => 'Wedding Barong', 'group' => 'Men', 'img' => '/uploads/categories/wedding_groom.png'],
                    ['name' => 'Piña Formal Barong', 'cat' => 'Piña Formal Barong', 'group' => 'Men', 'img' => '/uploads/categories/pina_formal.png'],
                    ['name' => 'Jusi Classic Barong', 'cat' => 'Jusi Classic Barong', 'group' => 'Men', 'img' => '/uploads/categories/jusi_classic.png'],
                    ['name' => 'Polo Barong', 'cat' => 'Polo Barong', 'group' => 'Men', 'img' => '/uploads/categories/polo_casual.png'],
                    ['name' => 'Camisa de Chino Undershirt', 'cat' => 'Camisa de Chino', 'group' => 'Men', 'img' => '/uploads/categories/camisa_undershirt.png'],
                    ['name' => 'Filipiniana Gown', 'cat' => 'Filipiniana Gown', 'group' => 'Women', 'img' => '/uploads/categories/women_filipiniana.png'],
                    ['name' => 'Modern Terno Top', 'cat' => 'Modern Terno Top', 'group' => 'Women', 'img' => '/uploads/categories/women_terno.png'],
                    ['name' => 'Lady Barong Blouse', 'cat' => 'Lady Barong', 'group' => 'Women', 'img' => '/uploads/categories/women_lady_barong.png'],
                    ['name' => 'Boys\' Barong Tagalog', 'cat' => 'Boys\' Barong', 'group' => 'Kids', 'img' => '/uploads/categories/kids_boys.png'],
                    ['name' => 'Girls\' Filipiniana Dress', 'cat' => 'Girls\' Filipiniana', 'group' => 'Kids', 'img' => '/uploads/categories/kids_girls.png'],
                    ['name' => 'Cufflinks & Heritage Accessories', 'cat' => 'Accessories', 'group' => 'Accessories', 'img' => '/uploads/categories/accessories.png'],
                ];

                if (isset($categories) && $categories->isNotEmpty()) {
                    foreach ($categories as $dbCat) {
                        $exists = false;
                        foreach ($allCatItems as $item) {
                            if (strtolower($item['name']) == strtolower($dbCat->name)) {
                                $exists = true;
                                break;
                            }
                        }
                        if (!$exists) {
                            $allCatItems[] = [
                                'name' => $dbCat->name,
                                'cat' => $dbCat->name,
                                'group' => 'Other',
                                'img' => '/uploads/categories/pina_formal.png'
                            ];
                        }
                    }
                }

                // Put chosen category in FRONT if selected
                $selectedCatParam = request('category');
                if ($selectedCatParam) {
                    $foundItem = null;
                    $foundIndex = -1;

                    foreach ($catItems as $idx => $ci) {
                        if (strtolower($ci['cat']) === strtolower($selectedCatParam) || strtolower($ci['name']) === strtolower($selectedCatParam)) {
                            $foundIndex = $idx;
                            $foundItem = $ci;
                            break;
                        }
                    }

                    if ($foundItem) {
                        array_splice($catItems, $foundIndex, 1);
                        array_unshift($catItems, $foundItem);
                    } else {
                        foreach ($allCatItems as $aci) {
                            if (strtolower($aci['cat']) === strtolower($selectedCatParam) || strtolower($aci['name']) === strtolower($selectedCatParam)) {
                                array_pop($catItems);
                                array_unshift($catItems, $aci);
                                break;
                            }
                        }
                    }
                }
            @endphp

            <div class="grid grid-cols-4 sm:grid-cols-9 gap-3 sm:gap-4 text-center">
                @foreach($catItems as $index => $item)
                    @php
                        $isAll = $item['cat'] === '__all__';
                        $isCurrentSelected = $isAll
                            ? !$selectedCatParam && !request('search') && !request('sort')
                            : ($selectedCatParam && (strtolower($item['cat']) === strtolower($selectedCatParam) || strtolower($item['name']) === strtolower($selectedCatParam)));
                        $itemHref = $isAll ? '/' : '/?category=' . urlencode($item['cat']);
                    @endphp
                    <a href="{{ $itemHref }}" class="group flex flex-col items-center gap-2">
                        <div class="w-16 h-16 sm:w-20 sm:h-20 rounded-full overflow-hidden bg-gray-100 border-2 {{ $isCurrentSelected ? 'border-amber-600 ring-4 ring-amber-500/25 scale-105 shadow-md' : 'border-transparent group-hover:border-amber-600 shadow-xs group-hover:scale-105' }} transition-all">
                            <img src="{{ $item['img'] }}" class="w-full h-full object-cover" alt="{{ $item['name'] }}">
                        </div>
                        <span class="text-[11px] {{ $isCurrentSelected ? 'font-black text-amber-700' : 'font-medium text-gray-700 group-hover:text-black' }} leading-tight line-clamp-2">{{ $item['name'] }}</span>
                    </a>
                @endforeach
            </div>

            {{-- All Categories Modal --}}
            <template x-if="categoriesModalOpen">
                <div 
                    class="fixed inset-0 z-9999 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm"
                    @click="categoriesModalOpen = false"
                    @keydown.escape.window="categoriesModalOpen = false"
                    x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0"
                    x-transition:enter-end="opacity-100"
                    x-transition:leave="transition ease-in duration-150"
                    x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                >
                    <div 
                        @click.stop
                        class="bg-white w-full max-w-4xl rounded-3xl border border-gray-100 shadow-2xl p-6 sm:p-8 relative max-h-[85vh] flex flex-col overflow-hidden"
                        x-transition:enter="transition ease-out duration-300 transform"
                        x-transition:enter-start="opacity-0 scale-95 translate-y-4"
                        x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                    >
                        <!-- Modal Header -->
                        <div class="flex items-center justify-between pb-4 border-b border-gray-100 mb-6 shrink-0">
                            <div>
                                <h3 class="text-lg sm:text-xl font-extrabold text-gray-900">All Categories</h3>
                                <p class="text-xs text-gray-500 mt-0.5">Explore our complete collection by category</p>
                            </div>
                            <button 
                                type="button"
                                @click="categoriesModalOpen = false"
                                class="w-9 h-9 flex items-center justify-center rounded-full text-gray-400 hover:text-black hover:bg-gray-100 transition-colors"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>

                        <!-- Modal Body (Scrollable Grid of Circular Mini Pictures + Labels) -->
                        <div class="overflow-y-auto no-scrollbar pr-1 grow">
                            <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-6 gap-6 sm:gap-8 text-center py-2">
                                @foreach($allCatItems as $item)
                                    <a href="/?category={{ urlencode($item['cat']) }}" 
                                       @click="categoriesModalOpen = false"
                                       class="group flex flex-col items-center gap-2.5 hover:scale-105 transition-transform duration-200">
                                        <div class="w-18 h-18 sm:w-20 sm:h-20 rounded-full overflow-hidden bg-gray-100 border-2 border-gray-200 group-hover:border-amber-600 shadow-sm transition-all shrink-0">
                                            <img src="{{ $item['img'] }}" class="w-full h-full object-cover" alt="{{ $item['name'] }}">
                                        </div>
                                        <span class="text-xs font-semibold text-gray-800 group-hover:text-amber-700 leading-tight line-clamp-2">{{ $item['name'] }}</span>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        {{-- ====== Featured Sections ====== --}}
        <div>
            <div class="mb-4">
                <h3 class="text-base sm:text-lg font-extrabold text-gray-900">Featured Sections</h3>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-5">
                <!-- Best Sellers -->
                <a href="/?sort=best_sellers#catalogue-section" class="group relative rounded-2xl overflow-hidden bg-gray-900 aspect-4/5 shadow-sm flex flex-col justify-end p-4">
                    <img src="/uploads/categories/featured_best_sellers.png" class="absolute inset-0 w-full h-full object-cover opacity-85 group-hover:scale-105 transition-transform duration-500" alt="Best Sellers">
                    <div class="absolute inset-0 bg-linear-to-t from-black/85 via-black/20 to-transparent"></div>
                    <div class="relative z-10 text-white">
                        <h4 class="font-bold text-xs sm:text-sm">Best Sellers</h4>
                        <p class="text-[10px] text-gray-300">Highest selling barongs</p>
                    </div>
                </a>

                <!-- Top Rated Shops -->
                <button type="button" @click="topShopsModalOpen = true" class="group relative rounded-2xl overflow-hidden bg-gray-900 aspect-4/5 shadow-sm flex flex-col justify-end p-4 text-left cursor-pointer">
                    <img src="/uploads/categories/wedding_groom.png" class="absolute inset-0 w-full h-full object-cover opacity-85 group-hover:scale-105 transition-transform duration-500" alt="Top Rated Shops">
                    <div class="absolute inset-0 bg-linear-to-t from-black/85 via-black/20 to-transparent"></div>
                    <div class="relative z-10 text-white">
                        <h4 class="font-bold text-xs sm:text-sm">Top Rated Shops</h4>
                        <p class="text-[10px] text-gray-300">Ratings, sales & products</p>
                    </div>
                </button>

                <!-- New Arrivals -->
                <a href="/?sort=newest#catalogue-section" class="group relative rounded-2xl overflow-hidden bg-gray-900 aspect-4/5 shadow-sm flex flex-col justify-end p-4">
                    <img src="/uploads/categories/jusi_classic.png" class="absolute inset-0 w-full h-full object-cover opacity-85 group-hover:scale-105 transition-transform duration-500" alt="New Arrivals">
                    <div class="absolute inset-0 bg-linear-to-t from-black/85 via-black/20 to-transparent"></div>
                    <div class="relative z-10 text-white">
                        <h4 class="font-bold text-xs sm:text-sm">New Arrivals</h4>
                        <p class="text-[10px] text-gray-300">Fresh hand-embroidered designs</p>
                    </div>
                </a>
            </div>

            {{-- Top Rated Shops Modal --}}
            <template x-if="topShopsModalOpen">
                <div 
                    class="fixed inset-0 z-9999 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm"
                    @click="topShopsModalOpen = false"
                    @keydown.escape.window="topShopsModalOpen = false"
                    x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0"
                    x-transition:enter-end="opacity-100"
                    x-transition:leave="transition ease-in duration-150"
                    x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                >
                    <div 
                        @click.stop
                        class="bg-white w-full max-w-4xl rounded-3xl border border-gray-100 shadow-2xl p-6 sm:p-8 relative max-h-[85vh] flex flex-col overflow-hidden"
                        x-transition:enter="transition ease-out duration-300 transform"
                        x-transition:enter-start="opacity-0 scale-95 translate-y-4"
                        x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                    >
                        <!-- Modal Header -->
                        <div class="flex items-center justify-between pb-4 border-b border-gray-100 mb-6 shrink-0">
                            <div>
                                <h3 class="text-lg sm:text-xl font-extrabold text-gray-900 flex items-center gap-2">
                                    ⭐ Top Rated Shops & Artisans
                                </h3>
                                <p class="text-xs text-gray-500 mt-0.5">Verified master embroiderers & top-performing Lumban ateliers</p>
                            </div>
                            <button 
                                type="button"
                                @click="topShopsModalOpen = false"
                                class="w-9 h-9 flex items-center justify-center rounded-full text-gray-400 hover:text-black hover:bg-gray-100 transition-colors"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>

                        <!-- Modal Body (Grid of Top Rated Shops) -->
                        <div class="overflow-y-auto no-scrollbar pr-1 grow">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 py-2">
                                @foreach($topShops as $shop)
                                    <div class="bg-gray-50/80 hover:bg-amber-50/40 rounded-2xl p-4 border border-gray-100 hover:border-amber-200 transition-all flex flex-col justify-between group">
                                        <div class="flex items-start gap-3.5">
                                            <div class="w-14 h-14 rounded-2xl overflow-hidden bg-white border border-gray-200 shrink-0 shadow-xs">
                                                <img src="{{ $shop->avatar }}" class="w-full h-full object-cover" alt="{{ $shop->name }}">
                                            </div>
                                            <div class="grow min-w-0">
                                                <div class="flex items-center justify-between gap-2">
                                                    <h4 class="font-extrabold text-sm text-gray-900 truncate group-hover:text-amber-900 transition-colors">{{ $shop->name }}</h4>
                                                    <div class="flex items-center gap-1 bg-amber-100 text-amber-800 text-[11px] font-black px-2 py-0.5 rounded-full shrink-0">
                                                        <svg class="w-3 h-3 fill-amber-500 text-amber-500" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                                        <span>{{ $shop->rating }}</span>
                                                    </div>
                                                </div>
                                                <p class="text-[11px] text-gray-500 line-clamp-2 mt-1 leading-snug">{{ $shop->description }}</p>
                                                <span class="text-[10px] text-gray-400 font-medium block mt-1">📍 {{ $shop->location }}</span>
                                            </div>
                                        </div>

                                        <div class="mt-4 pt-3 border-t border-gray-200/60 flex items-center justify-between">
                                            <div class="flex items-center gap-4 text-xs">
                                                <div>
                                                    <span class="text-[10px] text-gray-400 block uppercase font-bold tracking-wider">Total Sold</span>
                                                    <strong class="text-gray-900 font-extrabold text-xs">{{ $shop->total_sold }} barongs</strong>
                                                </div>
                                                <div class="w-px h-6 bg-gray-200"></div>
                                                <div>
                                                    <span class="text-[10px] text-gray-400 block uppercase font-bold tracking-wider">Products</span>
                                                    <strong class="text-gray-900 font-extrabold text-xs">{{ $shop->products_count }} items</strong>
                                                </div>
                                            </div>

                                            <a href="/?search={{ urlencode($shop->name) }}" 
                                               @click="topShopsModalOpen = false"
                                               class="px-3.5 py-1.5 bg-black text-white text-[11px] font-bold rounded-xl hover:bg-amber-700 transition-colors shadow-xs">
                                                Browse Shop →
                                            </a>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </div>

    {{-- ====== Product Catalogue Section ====== --}}
    <div id="catalogue-section" class="space-y-8 transition-opacity duration-300">
        @if(request('sort') === 'best_sellers' || request('sort') === 'trending')
            <div class="text-xs text-amber-950 font-medium flex items-center justify-between bg-amber-50/90 p-3.5 sm:p-4 rounded-2xl border border-amber-200 shadow-xs">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center font-bold text-base shrink-0">🔥</div>
                    <div>
                        <strong class="text-sm font-extrabold text-amber-950 block leading-tight">Best Sellers Collection</strong>
                        <span class="text-xs text-amber-800/90">Displaying highest selling authentic Barong Tagalog & Filipiniana pieces</span>
                    </div>
                </div>
                <a href="/" class="text-xs font-bold text-amber-900 hover:underline shrink-0">Show All</a>
            </div>
        @endif

        @if(request('search'))
            <div class="text-xs text-gray-500 font-medium flex items-center gap-2 bg-gray-50 p-3 rounded-xl border border-gray-100">
                <span>Results for <strong class="text-black">"{{ request('search') }}"</strong></span>
                <a href="{{ request('category') ? '/?category='.request('category') : '/' }}" class="text-[#C0422A] font-bold hover:underline ml-auto">Clear Search</a>
            </div>
        @endif

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
                             onerror="this.src='/uploads/products/default.jpg'"
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
                         onerror="this.src='/uploads/products/default.jpg'"
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
    </div>{{-- End #catalogue-section --}}

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
                class="fixed inset-0 z-9999 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm"
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

    // Instant AJAX filtering for categories, demographics, and search without full page refresh
    document.addEventListener('DOMContentLoaded', function() {
        function loadCatalogue(url, push = true) {
            const section = document.getElementById('catalogue-section');
            if (!section) {
                window.location.href = url;
                return;
            }

            section.style.opacity = '0.4';
            section.style.pointerEvents = 'none';

            fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(res => res.text())
                .then(html => {
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');
                    const newSection = doc.getElementById('catalogue-section');
                    if (newSection && section) {
                        section.innerHTML = newSection.innerHTML;
                        if (push) history.pushState(null, '', url);
                    }
                })
                .catch(() => {
                    window.location.href = url;
                })
                .finally(() => {
                    section.style.opacity = '1';
                    section.style.pointerEvents = 'auto';
                });
        }

        document.addEventListener('click', function(e) {
            const link = e.target.closest('.ajax-filter-link, .pagination a');
            if (link && link.href) {
                e.preventDefault();
                loadCatalogue(link.href);
            }
        });

        document.addEventListener('submit', function(e) {
            const form = e.target.closest('.ajax-search-form');
            if (form) {
                e.preventDefault();
                const formData = new FormData(form);
                const params = new URLSearchParams(formData).toString();
                const url = form.action + (params ? '?' + params : '');
                loadCatalogue(url);
            }
        });

        window.addEventListener('popstate', function() {
            loadCatalogue(window.location.href, false);
        });
    });
</script>
@endpush
@endsection
