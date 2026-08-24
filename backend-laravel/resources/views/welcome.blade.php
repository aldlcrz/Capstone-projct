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

    // Guard: save pending intent and redirect guests to login instead of opening the Quick-Add modal
    window.openQuickAdd = function(detail) {
        if (!window.isLoggedIn) {
            var intent = {
                action: 'add_to_cart',
                productId: detail.id,
                quantity: 1,
                size: null,
                variation: 'Original',
                redirectUrl: window.location.href
            };
            try { localStorage.setItem('lumbarong_pending_intent', JSON.stringify(intent)); } catch(e) {}
            window.location.href = window.loginUrl;
            return;
        }
        window.dispatchEvent(new CustomEvent('open-quick-add', { detail: detail }));
    };
</script>
<div class="space-y-8" x-data="{ categoriesModalOpen: false, topShopsModalOpen: false }">

    {{-- ====== Hero Banner Coverflow Product Carousel ====== --}}
    @if(isset($banners) && $banners->isNotEmpty())
    @php
        $bannerData = $banners->map(function($b) {
            return [
                'id'            => (string)$b->id,
                'title'         => $b->title ?: 'Artisan Barong',
                'subtitle'      => $b->subtitle ?: 'LumBarong Shop',
                'image_url'     => $b->getImageUrl(),
                'button_text_1' => $b->button_text_1 ?: 'Shop now',
                'button_url_1'  => $b->getResolvedButtonUrl1(),
                'button_text_2' => $b->button_text_2 ?: 'Visit shop',
                'button_url_2'  => $b->getResolvedButtonUrl2(),
            ];
        })->values();
    @endphp
    <div
        x-data="{
            items: {{ Js::from($bannerData) }},
            active: 0,
            get total() { return this.items.length; },
            get current() { return this.items[this.active] || this.items[0]; },
            getItemPos(idx) {
                if (this.total === 1) return 'center';
                var diff = (idx - this.active + this.total) % this.total;
                if (diff === 0) return 'center';
                if (diff === 1 || (this.total === 2 && diff === 1)) return 'right';
                if (diff === this.total - 1) return 'left';
                return 'hidden';
            },
            prev() {
                this.active = (this.active - 1 + this.total) % this.total;
            },
            next() {
                this.active = (this.active + 1) % this.total;
            },
            goTo(i) {
                this.active = i;
            },
            timer: null,
            startTimer() {
                if (this.total <= 1) return;
                this.timer = setInterval(() => { this.next(); }, 3000);
            },
            stopTimer() {
                if (this.timer) clearInterval(this.timer);
            }
        }"
        x-init="startTimer()"
        @mouseenter="stopTimer()"
        @mouseleave="startTimer()"
        style="background: #0d0c0a; min-height: 380px;"
        class="relative rounded-2xl sm:rounded-3xl overflow-hidden shadow-2xl flex flex-col justify-between select-none py-3 sm:py-5 px-3"
    >
        {{-- Luxury Studio Atmosphere Backdrop --}}
        <div class="absolute inset-0 pointer-events-none" style="background: radial-gradient(ellipse at center, #261f17 0%, #110f0d 55%, #050505 100%);"></div>
        <div class="absolute inset-0 pointer-events-none bg-black/20"></div>

        {{-- 3D Product Carousel Stage --}}
        <div class="relative w-full flex items-center justify-center overflow-hidden" style="height: 220px; min-height: 220px;">
            {{-- Center Spotlight Glow --}}
            <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-64 h-64 bg-amber-500/10 rounded-full blur-3xl pointer-events-none z-0"></div>

            <template x-for="(item, idx) in items" :key="item.id">
                <div
                    @click="if(getItemPos(idx) !== 'center') goTo(idx)"
                    :style="getItemPos(idx) === 'center' ? 'left: 50%; transform: translateX(-50%) scale(1); z-index: 20; opacity: 1;' : (getItemPos(idx) === 'left' ? 'left: 20%; transform: translateX(-50%) scale(0.75); z-index: 10; opacity: 0.38; cursor: pointer;' : (getItemPos(idx) === 'right' ? 'left: 80%; transform: translateX(-50%) scale(0.75); z-index: 10; opacity: 0.38; cursor: pointer;' : 'left: 50%; transform: translateX(-50%) scale(0.5); z-index: 0; opacity: 0; pointer-events: none;'))"
                    class="absolute top-0 bottom-0 flex items-center justify-center transition-all duration-500 ease-out"
                    style="height: 220px;"
                >
                    <img :src="item.image_url" 
                         :alt="item.title"
                         style="max-height: 220px; height: 100%; width: auto; max-width: 320px; object-fit: contain;"
                         class="rounded-2xl drop-shadow-[0_12px_28px_rgba(0,0,0,0.9)] filter contrast-[1.03]">
                </div>
            </template>
        </div>

        {{-- Centered Dynamic Product Information & Action Buttons --}}
        <div class="relative z-30 flex flex-col items-center justify-center text-center px-4 pt-2 space-y-1.5 sm:space-y-2">
            <p class="text-[10px] sm:text-xs font-black uppercase tracking-[0.25em] text-amber-300 drop-shadow-md" x-text="current.subtitle"></p>
            <h2 class="text-lg sm:text-2xl lg:text-3xl font-black text-white leading-tight tracking-tight drop-shadow-[0_2px_10px_rgba(0,0,0,0.95)] max-w-xl mx-auto truncate px-2" x-text="current.title"></h2>

            <div class="flex items-center justify-center gap-2.5 sm:gap-3.5 pt-1">
                {{-- Dynamic Shop Now button matching centered product --}}
                <a :href="current.button_url_1"
                   @click="if(current.button_url_1 === '#catalogue-section') { $event.preventDefault(); document.getElementById('catalogue-section')?.scrollIntoView({ behavior: 'smooth' }); }"
                   class="inline-flex items-center justify-center px-5 py-2 sm:px-7 sm:py-2.5 bg-[#C0422A] hover:bg-[#a6351f] text-white text-[11px] sm:text-xs font-black uppercase tracking-wider rounded-xl transition-all shadow-lg hover:shadow-xl hover:scale-105 active:scale-95 cursor-pointer">
                    <span x-text="current.button_text_1 || 'Shop now'"></span>
                </a>

                {{-- Dynamic Visit Shop button matching centered product --}}
                <a :href="current.button_url_2"
                   @click="if(current.button_url_2 === '#catalogue-section') { $event.preventDefault(); document.getElementById('catalogue-section')?.scrollIntoView({ behavior: 'smooth' }); }"
                   class="inline-flex items-center justify-center px-5 py-2 sm:px-7 sm:py-2.5 bg-black/70 hover:bg-black/90 text-white text-[11px] sm:text-xs font-black uppercase tracking-wider rounded-xl border border-white/30 backdrop-blur-md transition-all hover:scale-105 active:scale-95 cursor-pointer shadow-md">
                    <span x-text="current.button_text_2 || 'Visit shop'"></span>
                </a>
            </div>

            {{-- Dot Indicators --}}
            <div class="flex items-center gap-1.5 pt-2" x-show="total > 1">
                <template x-for="(dot, dIdx) in items" :key="'dot-' + dot.id">
                    <button type="button"
                            @click="goTo(dIdx)"
                            :class="active === dIdx ? 'w-6 bg-amber-400' : 'w-2 bg-white/40 hover:bg-white/70'"
                            class="h-1.5 rounded-full transition-all duration-300 cursor-pointer"></button>
                </template>
            </div>
        </div>

        {{-- Left & Right Navigation Chevrons --}}
        <template x-if="total > 1">
            <div>
                <button type="button" @click="prev()" aria-label="Previous product" class="absolute left-3 sm:left-5 top-1/2 -translate-y-1/2 z-30 w-8 h-8 sm:w-10 sm:h-10 rounded-full bg-black/70 hover:bg-black/95 text-white border border-white/20 flex items-center justify-center transition-all hover:scale-110 active:scale-95 cursor-pointer shadow-xl backdrop-blur-xs">
                    <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/></svg>
                </button>
                <button type="button" @click="next()" aria-label="Next product" class="absolute right-3 sm:right-5 top-1/2 -translate-y-1/2 z-30 w-8 h-8 sm:w-10 sm:h-10 rounded-full bg-black/70 hover:bg-black/95 text-white border border-white/20 flex items-center justify-center transition-all hover:scale-110 active:scale-95 cursor-pointer shadow-xl backdrop-blur-xs">
                    <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
                </button>
            </div>
        </template>
    </div>
    @endif

        {{-- ====== Shop by Category ====== --}}
        <div id="shop-by-category-section" x-on:open-all-categories.window="categoriesModalOpen = true">
            <div class="flex items-center gap-3 mb-4">
                <h3 class="text-base sm:text-lg font-extrabold text-gray-900">Shop by Category</h3>
                <button type="button" @click="categoriesModalOpen = true" onclick="window.dispatchEvent(new CustomEvent('open-all-categories'))" class="text-xs font-semibold text-[#C0422A] hover:text-amber-900 hover:underline cursor-pointer">
                    View all
                </button>
            </div>

            @php
                $getCatImage = function($name) {
                    $nameLower = strtolower($name);
                    if (str_contains($nameLower, 'wedding')) return '/uploads/categories/wedding_groom.png';
                    if (str_contains($nameLower, 'pina') || str_contains($nameLower, 'piña') || str_contains($nameLower, 'formal barong')) return '/uploads/categories/pina_formal.png';
                    if (str_contains($nameLower, 'jusi') || str_contains($nameLower, 'lumban')) return '/uploads/categories/jusi_classic.png';
                    if (str_contains($nameLower, 'polo') || str_contains($nameLower, 'semi-formal') || str_contains($nameLower, 'casual')) return '/uploads/categories/polo_casual.png';
                    if (str_contains($nameLower, 'camisa') || str_contains($nameLower, 'undershirt')) return '/uploads/categories/camisa_undershirt.png';
                    if (str_contains($nameLower, 'terno') || str_contains($nameLower, 'modern filipiniana')) return '/uploads/categories/women_terno.png';
                    if (str_contains($nameLower, 'traditional gown') || str_contains($nameLower, 'filipiniana') || str_contains($nameLower, 'gown')) return '/uploads/categories/women_filipiniana.png';
                    if (str_contains($nameLower, 'lady') || str_contains($nameLower, 'blouse')) return '/uploads/categories/women_lady_barong.png';
                    if (str_contains($nameLower, 'girl')) return '/uploads/categories/kids_girls.png';
                    if (str_contains($nameLower, 'boy') || str_contains($nameLower, 'kid')) return '/uploads/categories/kids_boys.png';
                    if (str_contains($nameLower, 'accessor') || str_contains($nameLower, 'cufflink') || str_contains($nameLower, 'heritage')) return '/uploads/categories/accessories.png';
                    return '/uploads/categories/pina_formal.png';
                };

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
                        $dbName = trim($dbCat->name ?? '');
                        if (empty($dbName)) continue;

                        $exists = false;
                        foreach ($allCatItems as $item) {
                            if (strtolower($item['name']) === strtolower($dbName) || strtolower($item['cat']) === strtolower($dbName)) {
                                $exists = true;
                                break;
                            }
                        }

                        $pCount = $dbCat->products_count ?? (method_exists($dbCat, 'products') ? $dbCat->products()->count() : 0);

                        if (!$exists && $pCount > 0) {
                            $targetGroupStr = 'Other';
                            if (is_array($dbCat->target_group) && count($dbCat->target_group) > 0) {
                                $targetGroupStr = implode(', ', $dbCat->target_group);
                            } elseif (is_string($dbCat->target_group) && !empty($dbCat->target_group)) {
                                $targetGroupStr = $dbCat->target_group;
                            }

                            $allCatItems[] = [
                                'name' => $dbCat->name,
                                'cat' => $dbCat->name,
                                'group' => $targetGroupStr,
                                'img' => $dbCat->getImageUrl()
                            ];
                        }
                    }
                }

                // Saved categories logic applies ONLY for logged-in accounts
                $selectedCatParam = request('category');
                $activeCatObj = isset($categories) && $selectedCatParam ? $categories->first(fn($c) => (string)$c->id === (string)$selectedCatParam || strtolower(trim($c->name)) === strtolower(trim($selectedCatParam))) : null;
                $selectedCatName = $activeCatObj ? $activeCatObj->name : $selectedCatParam;
                $savedCategories = auth()->check() ? session('saved_categories', []) : [];

                if (auth()->check()) {
                    if ($selectedCatParam && $selectedCatParam !== '__all__' && !in_array($selectedCatParam, $savedCategories)) {
                        array_unshift($savedCategories, $selectedCatParam);
                    }

                    if (!empty($savedCategories)) {
                        $savedItems = [];
                        foreach ($savedCategories as $savedCatName) {
                            foreach ($allCatItems as $aci) {
                                if (strtolower($aci['cat']) === strtolower($savedCatName) || strtolower($aci['name']) === strtolower($savedCatName)) {
                                    $alreadyAdded = false;
                                    foreach ($savedItems as $si) {
                                        if (strtolower($si['cat']) === strtolower($aci['cat'])) {
                                            $alreadyAdded = true;
                                            break;
                                        }
                                    }
                                    if (!$alreadyAdded) {
                                        $savedItems[] = $aci;
                                    }
                                    break;
                                }
                            }
                        }

                        // Remove saved items from default $catItems to avoid duplicates
                        foreach ($savedItems as $sItem) {
                            foreach ($catItems as $idx => $ci) {
                                if (strtolower($ci['cat']) === strtolower($sItem['cat'])) {
                                    array_splice($catItems, $idx, 1);
                                    break;
                                }
                            }
                        }

                        // Insert saved items right after 'All Barongs' at position 1
                        array_splice($catItems, 1, 0, $savedItems);
                    }
                } elseif ($selectedCatParam && $selectedCatParam !== '__all__') {
                    // For non-logged-in guest users, temporarily move current category to front without saving
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
                        array_splice($catItems, 1, 0, [$foundItem]);
                    }
                }
            @endphp

            <div class="flex gap-3 sm:gap-5 overflow-x-auto no-scrollbar pb-1 items-start">
                @foreach($catItems as $index => $item)
                    @php
                        $isAll = $item['cat'] === '__all__';
                        $isCurrentSelected = $isAll
                            ? (!$selectedCatParam || $selectedCatParam === '__all__') && !request('search') && !request('sort')
                            : ($selectedCatParam && (
                                strtolower(trim($item['cat'])) === strtolower(trim($selectedCatParam)) ||
                                strtolower(trim($item['name'])) === strtolower(trim($selectedCatParam)) ||
                                ($selectedCatName && (
                                    strtolower(trim($item['cat'])) === strtolower(trim($selectedCatName)) ||
                                    strtolower(trim($item['name'])) === strtolower(trim($selectedCatName))
                                ))
                            ));
                        $itemHref = $isAll ? '/#catalogue-section' : '/?category=' . urlencode($item['cat']) . '#catalogue-section';
                    @endphp
                    <a href="{{ $itemHref }}" data-category="{{ $item['cat'] }}" class="category-pill-btn ajax-filter-link group flex flex-col items-center gap-2 shrink-0 w-16 sm:w-20 cursor-pointer">
                        <div class="category-img-box relative w-16 h-16 sm:w-20 sm:h-20 rounded-full overflow-hidden bg-gray-100 border-2 {{ $isCurrentSelected ? 'border-[#C0422A] ring-4 ring-[#C0422A]/25 scale-105 shadow-md' : 'border-gray-200/80 group-hover:border-[#C0422A] shadow-xs group-hover:scale-105' }} transition-all">
                            <img src="{{ $item['img'] }}" loading="lazy" decoding="async" class="w-full h-full object-cover" alt="{{ $item['name'] }}">
                            <div class="category-active-badge {{ $isCurrentSelected ? '' : 'hidden' }}">
                                <div class="absolute inset-0 bg-[#C0422A]/10 pointer-events-none"></div>
                                <span class="absolute top-1 right-1 w-4 h-4 sm:w-4.5 sm:h-4.5 bg-[#C0422A] text-white rounded-full flex items-center justify-center shadow-md ring-2 ring-white">
                                    <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                </span>
                            </div>
                        </div>
                        <span class="category-name-label text-[11px] {{ $isCurrentSelected ? 'font-black text-[#C0422A]' : 'font-medium text-gray-700 group-hover:text-black' }} leading-tight text-center line-clamp-2">{{ $item['name'] }}</span>
                    </a>
                @endforeach
                <button type="button" @click="categoriesModalOpen = true" onclick="window.dispatchEvent(new CustomEvent('open-all-categories'))" class="group flex flex-col items-center gap-2 shrink-0 w-16 sm:w-20 cursor-pointer">
                    <div class="w-16 h-16 sm:w-20 sm:h-20 rounded-full bg-gray-100 border-2 border-dashed border-gray-300 group-hover:border-[#C0422A] flex items-center justify-center text-gray-500 group-hover:text-[#C0422A] transition-all shadow-2xs group-hover:scale-105">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h.01M12 12h.01M19 12h.01M6 12a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0z"/></svg>
                    </div>
                    <span class="text-[11px] font-bold text-gray-600 group-hover:text-[#C0422A] leading-tight text-center">More...</span>
                </button>
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
                        <div class="overflow-y-auto no-scrollbar pr-1 grow" id="all-categories-modal-grid">
                            <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-6 gap-6 sm:gap-8 text-center py-2">
                                @foreach($allCatItems as $item)
                                    @php
                                        $isModalSelected = $selectedCatParam && (
                                            strtolower(trim($item['cat'])) === strtolower(trim($selectedCatParam)) ||
                                            strtolower(trim($item['name'])) === strtolower(trim($selectedCatParam)) ||
                                            ($selectedCatName && (
                                                strtolower(trim($item['cat'])) === strtolower(trim($selectedCatName)) ||
                                                strtolower(trim($item['name'])) === strtolower(trim($selectedCatName))
                                            ))
                                        );
                                    @endphp
                                    <a href="/?category={{ urlencode($item['cat']) }}#catalogue-section" 
                                       data-category="{{ $item['cat'] }}"
                                       @click="categoriesModalOpen = false"
                                       class="category-pill-btn ajax-filter-link group flex flex-col items-center gap-2.5 hover:scale-105 transition-transform duration-200 cursor-pointer">
                                        <div class="category-img-box relative w-18 h-18 sm:w-20 sm:h-20 rounded-full overflow-hidden bg-gray-100 border-2 {{ $isModalSelected ? 'border-[#C0422A] ring-4 ring-[#C0422A]/25 scale-105 shadow-md' : 'border-gray-200 group-hover:border-[#C0422A] shadow-sm' }} transition-all shrink-0">
                                            <img src="{{ $item['img'] }}" loading="lazy" decoding="async" class="w-full h-full object-cover" alt="{{ $item['name'] }}">
                                            <div class="category-active-badge {{ $isModalSelected ? '' : 'hidden' }}">
                                                <div class="absolute inset-0 bg-[#C0422A]/10 pointer-events-none"></div>
                                                <span class="absolute top-1 right-1 w-4.5 h-4.5 bg-[#C0422A] text-white rounded-full flex items-center justify-center shadow-md ring-2 ring-white">
                                                    <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                                </span>
                                            </div>
                                        </div>
                                        <span class="category-name-label text-xs {{ $isModalSelected ? 'font-black text-[#C0422A]' : 'font-semibold text-gray-800 group-hover:text-[#C0422A]' }} leading-tight line-clamp-2">{{ $item['name'] }}</span>
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
                <a href="/?sort=best_sellers#catalogue-section" class="ajax-filter-link group relative rounded-2xl overflow-hidden bg-gray-900 aspect-4/5 shadow-sm flex flex-col justify-end p-4">
                    <img src="/uploads/categories/featured_best_sellers.png" loading="lazy" decoding="async" class="absolute inset-0 w-full h-full object-cover opacity-85 group-hover:scale-105 transition-transform duration-500" alt="Best Sellers">
                    <div class="absolute inset-0 bg-linear-to-t from-black/85 via-black/20 to-transparent"></div>
                    <div class="relative z-10 text-white">
                        <h4 class="font-bold text-xs sm:text-sm">Best Sellers</h4>
                        <p class="text-[10px] text-gray-300">Highest selling barongs</p>
                    </div>
                </a>

                <!-- Top Rated Shops -->
                <button type="button" @click="topShopsModalOpen = true" class="group relative rounded-2xl overflow-hidden bg-gray-900 aspect-4/5 shadow-sm flex flex-col justify-end p-4 text-left cursor-pointer">
                    <img src="/uploads/categories/wedding_groom.png" loading="lazy" decoding="async" class="absolute inset-0 w-full h-full object-cover opacity-85 group-hover:scale-105 transition-transform duration-500" alt="Top Rated Shops">
                    <div class="absolute inset-0 bg-linear-to-t from-black/85 via-black/20 to-transparent"></div>
                    <div class="relative z-10 text-white">
                        <h4 class="font-bold text-xs sm:text-sm">Top Rated Shops</h4>
                        <p class="text-[10px] text-gray-300">Ratings, sales & products</p>
                    </div>
                </button>

                <!-- New Arrivals -->
                <a href="/?sort=newest#catalogue-section" class="ajax-filter-link group relative rounded-2xl overflow-hidden bg-gray-900 aspect-4/5 shadow-sm flex flex-col justify-end p-4">
                    <img src="/uploads/categories/jusi_classic.png" loading="lazy" decoding="async" class="absolute inset-0 w-full h-full object-cover opacity-85 group-hover:scale-105 transition-transform duration-500" alt="New Arrivals">
                    <div class="absolute inset-0 bg-linear-to-t from-black/85 via-black/20 to-transparent"></div>
                    <div class="relative z-10 text-white">
                        <h4 class="font-bold text-xs sm:text-sm">New Arrivals</h4>
                        <p class="text-[10px] text-gray-300">Fresh hand-embroidered designs</p>
                    </div>
                </a>

                <!-- Lumban Special -->
                <a href="/?sort=lumban_special#catalogue-section" class="ajax-filter-link group relative rounded-2xl overflow-hidden bg-gray-900 aspect-4/5 shadow-sm flex flex-col justify-end p-4">
                    <img src="/uploads/categories/pina_formal.png" loading="lazy" decoding="async" class="absolute inset-0 w-full h-full object-cover opacity-85 group-hover:scale-105 transition-transform duration-500" alt="Lumban Special">
                    <div class="absolute inset-0 bg-linear-to-t from-black/85 via-black/20 to-transparent"></div>
                    <div class="relative z-10 text-white">
                        <div style="display:inline-flex;align-items:center;gap:5px;padding:4px 11px 4px 7px;background:linear-gradient(90deg,#7A5505 0%,#C8890A 25%,#E8AD12 50%,#C8890A 75%,#7A5505 100%);border:1px solid #5C3E04;border-radius:20px;box-shadow:0 2px 10px rgba(200,137,10,0.5),inset 0 1px 0 rgba(255,220,80,0.25);white-space:nowrap;margin-bottom:5px;">
                            <img src="/images/logo-icon.png" alt="LumBarong" style="width:13px;height:13px;border-radius:50%;flex-shrink:0;object-fit:cover;">
                            <span style="color:#FFF8E0;font-family:ui-sans-serif,system-ui,sans-serif;font-size:8px;font-weight:800;letter-spacing:0.18em;text-transform:uppercase;">Special Sale</span>
                        </div>
                        <h4 class="font-bold text-xs sm:text-sm">Lumban Special</h4>
                        <p class="text-[10px] text-gray-300">Exclusive discounted barongs</p>
                    </div>
                </a>
            </div>

            {{-- Top Rated Shops Modal --}}
            <template x-if="topShopsModalOpen">
                <div 
                    class="fixed inset-0 z-9999 flex items-center justify-center p-3 sm:p-5 bg-black/75 backdrop-blur-sm"
                    style="position:fixed;inset:0;z-index:9999;display:flex;align-items:center;justify-content:center;background:rgba(0,0,0,0.75);backdrop-filter:blur(6px);"
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
                        class="w-full max-w-4xl max-h-[90vh] flex flex-col overflow-hidden relative"
                        style="background-color:#FDFBF7 !important;border:1px solid #EAE2D2 !important;border-radius:28px !important;box-shadow:0 25px 60px rgba(0,0,0,0.25) !important;padding:24px !important;color:#1E1915 !important;"
                        x-transition:enter="transition ease-out duration-300 transform"
                        x-transition:enter-start="opacity-0 scale-95 translate-y-4"
                        x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                    >
                        <!-- Modal Header -->
                        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;flex-shrink:0;">
                            <div style="display:flex;align-items:center;gap:14px;">
                                <!-- Heraldic Laurel Wreath + Star Emblem -->
                                <div style="width:48px;height:48px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                    <svg width="46" height="46" viewBox="0 0 48 48" fill="none">
                                        <!-- Central Medallion -->
                                        <circle cx="24" cy="23" r="10.5" stroke="#C49520" stroke-width="1" stroke-dasharray="2 1.5"/>
                                        <circle cx="24" cy="23" r="8.5" stroke="#C49520" stroke-width="0.8"/>
                                        <path d="M24 17.5l1.6 3.4 3.7.5-2.7 2.6.6 3.7-3.2-1.7-3.2 1.7.6-3.7-2.7-2.6 3.7-.5L24 17.5z" fill="#C49520"/>
                                        <!-- Laurel Wreath Left -->
                                        <path d="M15 32.5c-4-3.5-6-8.5-6-14 0-3.5 1-6.5 2.5-9" stroke="#C49520" stroke-width="1.3" stroke-linecap="round"/>
                                        <path d="M10 12c1.8 1.2 3.5 2.8 4 4.5M8 17.5c2 .6 3.8 1.8 4.8 3.5M8 23.5c2 0 3.8.6 5 2M9.5 29.5c2-.8 3.8-.8 5.2 0M12.5 34c1.8-1.2 3.6-1.5 5-.8" stroke="#C49520" stroke-width="1.2" stroke-linecap="round"/>
                                        <!-- Laurel Wreath Right -->
                                        <path d="M33 32.5c4-3.5 6-8.5 6-14 0-3.5-1-6.5-2.5-9" stroke="#C49520" stroke-width="1.3" stroke-linecap="round"/>
                                        <path d="M38 12c-1.8 1.2-3.5 2.8-4 4.5M40 17.5c-2 .6-3.8 1.8-4.8 3.5M40 23.5c-2 0-3.8.6-5 2M38.5 29.5c-2-.8-3.8-.8-5.2 0M35.5 34c-1.8-1.2-3.6-1.5-5-.8" stroke="#C49520" stroke-width="1.2" stroke-linecap="round"/>
                                        <!-- Base Ribbon -->
                                        <path d="M19 36c3 1.2 7 1.2 10 0" stroke="#C49520" stroke-width="1.3" stroke-linecap="round"/>
                                    </svg>
                                </div>
                                <div>
                                    <h3 style="font-family:ui-serif,Georgia,Cambria,serif;font-size:22px;font-weight:700;color:#1E1915;letter-spacing:-0.01em;line-height:1.2;margin:0;">
                                        Top Rated Shops & Artisans
                                    </h3>
                                    <p style="font-size:13px;color:#78716C;margin-top:3px;margin-bottom:0;">
                                        Verified master embroiderers & top-performing Lumban ateliers
                                    </p>
                                </div>
                            </div>
                            <button 
                                type="button"
                                @click="topShopsModalOpen = false"
                                style="width:40px;height:40px;border-radius:50%;border:1px solid #E2D9C8;background-color:#FFFFFF;display:flex;align-items:center;justify-content:center;color:#8C827A;cursor:pointer;flex-shrink:0;box-shadow:0 1px 3px rgba(0,0,0,0.05);transition:all 0.2s;"
                            >
                                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>

                        <!-- Star Divider -->
                        <div style="position:relative;margin:16px 0;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <div style="width:100%;border-top:1px solid #EAE1D0;"></div>
                            <span style="position:absolute;background-color:#FDFBF7;padding:0 12px;color:#C49520;font-size:12px;">✦</span>
                        </div>

                        <!-- Modal Body (Grid of Top Rated Shops) -->
                        <div class="overflow-y-auto no-scrollbar pr-1 grow space-y-4" style="overflow-y:auto;">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:16px;">
                                @foreach($topShops as $shop)
                                    <div style="background-color:#FFFFFF !important;border:1px solid #ECE3D2 !important;border-radius:18px !important;padding:18px !important;box-shadow:0 2px 6px rgba(0,0,0,0.03) !important;display:flex;flex-direction:column;justify-content:space-between;transition:all 0.2s;">
                                        <div>
                                            <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;">
                                                <div style="display:flex;align-items:flex-start;gap:14px;min-width:0;">
                                                    <!-- Gold Ringed Circular Avatar -->
                                                    <div style="width:58px;height:58px;min-width:58px;max-width:58px;min-height:58px;max-height:58px;border-radius:50%;padding:2px;background:linear-gradient(135deg,#996515,#E6CA65,#996515);box-shadow:0 2px 6px rgba(0,0,0,0.08);flex-shrink:0;">
                                                        <img src="{{ $shop->avatar }}" 
                                                             onerror="this.src='/uploads/products/default.jpg'" 
                                                             style="width:100%;height:100%;border-radius:50%;object-fit:cover;background:#FAF8F5;display:block;"
                                                             alt="{{ $shop->name }}">
                                                    </div>
                                                    <div style="min-width:0;">
                                                        <h4 style="font-family:ui-serif,Georgia,serif;font-size:15px;font-weight:700;color:#1E1915;line-height:1.2;margin:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                                                            {{ $shop->name }}
                                                        </h4>
                                                        <p class="line-clamp-2" style="font-size:11px;color:#78716C;margin-top:4px;margin-bottom:0;line-height:1.4;">
                                                            {{ $shop->description }}
                                                        </p>
                                                        <div style="display:flex;align-items:center;gap:4px;font-size:11px;color:#8C827A;font-weight:500;margin-top:6px;">
                                                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#B88728" stroke-width="2" style="flex-shrink:0;">
                                                                <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                            </svg>
                                                            <span style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $shop->location }}</span>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Rating Pill Badge -->
                                                <div style="background-color:#FDF8EE;border:1px solid #EEDBBA;color:#7A5505;font-size:12px;font-weight:700;padding:3px 9px;border-radius:20px;display:flex;align-items:center;gap:4px;flex-shrink:0;">
                                                    <svg width="13" height="13" viewBox="0 0 20 20" fill="#C49520">
                                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                                    </svg>
                                                    <span>{{ $shop->rating }}</span>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Card Bottom Stats & Action -->
                                        <div style="margin-top:14px;padding-top:12px;border-top:1px solid #F2ECE1;display:flex;align-items:center;justify-content:space-between;gap:8px;">
                                            <div style="display:flex;align-items:center;gap:18px;">
                                                <!-- Total Sold -->
                                                <div style="display:flex;align-items:center;gap:6px;">
                                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#8C827A" stroke-width="1.8" style="flex-shrink:0;">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                                                    </svg>
                                                    <div>
                                                        <span style="font-size:9px;color:#8C827A;text-transform:uppercase;font-weight:700;letter-spacing:0.06em;display:block;line-height:1;margin-bottom:2px;">Total Sold</span>
                                                        <strong style="font-size:12px;font-weight:800;color:#1E1915;display:block;line-height:1;">
                                                            {{ $shop->total_sold }} {{ $shop->total_sold == 1 ? 'barong' : 'barongs' }}
                                                        </strong>
                                                    </div>
                                                </div>
                                                <!-- Products Count -->
                                                <div style="display:flex;align-items:center;gap:6px;">
                                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#8C827A" stroke-width="1.8" style="flex-shrink:0;">
                                                        <rect x="3" y="8" width="18" height="13" rx="2" stroke-linecap="round" stroke-linejoin="round"/>
                                                        <path d="M3 13h18M10 13v2a2 2 0 004 0v-2M8 8V5a2 2 0 012-2h4a2 2 0 012 2v3" stroke-linecap="round" stroke-linejoin="round"/>
                                                    </svg>
                                                    <div>
                                                        <span style="font-size:9px;color:#8C827A;text-transform:uppercase;font-weight:700;letter-spacing:0.06em;display:block;line-height:1;margin-bottom:2px;">Products</span>
                                                        <strong style="font-size:12px;font-weight:800;color:#1E1915;display:block;line-height:1;">
                                                            {{ $shop->products_count }} {{ $shop->products_count == 1 ? 'item' : 'items' }}
                                                        </strong>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- View Shop Button -->
                                            <a href="/shops/{{ $shop->id }}#shop-catalogue" 
                                               @click="topShopsModalOpen = false"
                                               style="padding:7px 14px;background-color:#1C160E;color:#FAF6F0;font-size:12px;font-weight:600;border-radius:12px;text-decoration:none;display:flex;align-items:center;gap:4px;flex-shrink:0;box-shadow:0 1px 3px rgba(0,0,0,0.1);transition:background-color 0.2s;">
                                                <span>View Shop</span>
                                                <span style="font-size:13px;line-height:1;">→</span>
                                            </a>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            <!-- Verified & Trusted Footer Banner -->
                            <div style="margin-top:16px;padding:14px 18px;border-radius:18px;background:linear-gradient(90deg,#F6F0E4 0%,#F2EADA 50%,#EAE0CD 100%);border:1px solid #E2D6C0;display:flex;align-items:center;justify-content:space-between;gap:12px;position:relative;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,0.03);">
                                <div style="display:flex;align-items:center;gap:12px;position:relative;z-index:10;">
                                    <div style="width:32px;height:32px;border-radius:50%;border:2px solid #B88728;background-color:#FAF4EA;display:flex;align-items:center;justify-content:center;color:#B88728;flex-shrink:0;box-shadow:0 1px 2px rgba(0,0,0,0.05);">
                                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <h5 style="font-size:13px;font-weight:700;color:#1E1915;margin:0;line-height:1.2;">All shops are verified and trusted</h5>
                                        <p style="font-size:11px;color:#78716C;margin:2px 0 0 0;">Quality craftsmanship. Authentic Filipino heritage.</p>
                                    </div>
                                </div>
                                <!-- Background Embroidery Flourish Watermark -->
                                <svg width="120" height="70" viewBox="0 0 120 80" fill="#C49520" style="position:absolute;right:8px;bottom:-10px;opacity:0.18;pointer-events:none;">
                                    <path d="M60 10C40 10 30 30 10 35C30 40 40 60 60 60C80 60 90 40 110 35C90 30 80 10 60 10ZM60 25C65 25 70 30 70 35C70 40 65 45 60 45C55 45 50 40 50 35C50 30 55 25 60 25Z"/>
                                </svg>
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
                <a href="/#catalogue-section" class="ajax-filter-link text-xs font-bold text-amber-900 hover:underline shrink-0">Show All</a>
            </div>
        @endif

        @if(request('search'))
            <div class="text-xs text-gray-500 font-medium flex items-center gap-2 bg-gray-50 p-3 rounded-xl border border-gray-100">
                <span>Results for <strong class="text-black">"{{ request('search') }}"</strong></span>
                <a href="{{ request('category') ? '/?category='.request('category').'#catalogue-section' : '/#catalogue-section' }}" class="ajax-filter-link text-[#C0422A] font-bold hover:underline ml-auto">Clear Search</a>
            </div>
        @endif

    @php
        $catReq = request('category');
        $activeCatObj = isset($categories) && $catReq ? $categories->first(fn($c) => $c->id == $catReq || strtolower($c->name) == strtolower($catReq)) : null;
        $displayCatName = $activeCatObj ? $activeCatObj->name : $catReq;
        $isBestSellers = in_array(request('sort'), ['best_sellers', 'trending', 'most_sold']);
        $headerTitle = request('sort') === 'lumban_special' || request('lumban_special') 
            ? 'Lumban Special Collection' 
            : ($isBestSellers 
                ? 'Best Sellers Collection' 
                : ($displayCatName ? $displayCatName.' Collection' : (request('search') ? 'Search Results' : 'Recommended Products')));
    @endphp

    {{-- ====== Unified Product Catalogue ====== --}}
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-6 h-[1.5px] bg-[#C0422A]"></div>
                <span class="text-[10px] font-black uppercase tracking-widest text-[#2A2A28]">
                    {{ $headerTitle }}
                </span>
            </div>
            <span class="text-[10px] text-gray-400 font-bold">{{ $products->count() }} {{ Str::plural('piece', $products->count()) }}</span>
        </div>

        <div class="grid grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3 sm:gap-5">
            @foreach($products as $product)
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

                    @if($product->is_on_sale && $product->discount_percentage > 0)
                        <div style="position:absolute;top:6px;left:6px;display:flex;flex-direction:column;gap:4px;z-index:10;pointer-events:none;">
                            {{-- Top badge: LUMBAN SPECIAL --}}
                            <div style="display:inline-flex;align-items:center;gap:4px;padding:3px 8px 3px 5px;background:linear-gradient(135deg,#0F0C08 0%,#1C1609 100%);border:1px solid #A87B10;border-radius:20px;box-shadow:0 0 8px rgba(180,130,15,0.45),inset 0 1px 0 rgba(230,185,60,0.12);white-space:nowrap;">
                                <img src="/images/logo-icon.png" alt="LumBarong" style="width:13px;height:13px;border-radius:50%;flex-shrink:0;object-fit:cover;">
                                <span style="color:#DFC97A;font-family:ui-sans-serif,system-ui,sans-serif;font-size:7px;font-weight:700;letter-spacing:0.04em;text-transform:uppercase;">Lumban Special</span>
                            </div>
                            {{-- Bottom badge: -10% OFF (golden gradient) --}}
                            <div style="display:inline-flex;align-items:baseline;padding:3px 8px;background:linear-gradient(90deg,#7A5505 0%,#C8890A 25%,#E8AD12 50%,#C8890A 75%,#7A5505 100%);border:1px solid #5C3E04;border-radius:20px;box-shadow:0 2px 10px rgba(200,137,10,0.5),inset 0 1px 0 rgba(255,220,80,0.25);white-space:nowrap;width:fit-content;">
                                <span style="color:#FFF8E0;font-family:ui-sans-serif,system-ui,sans-serif;font-size:13px;font-weight:900;line-height:1;letter-spacing:-0.02em;">-{{ number_format($product->discount_percentage, 0) }}%</span>
                                <span style="color:#FFE8A0;font-family:ui-sans-serif,system-ui,sans-serif;font-size:8px;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;margin-left:2px;">OFF</span>
                            </div>
                        </div>
                    @elseif($product->target_group)
                        @php
                            $tgVal = strtolower(trim($product->target_group));
                        @endphp
                        <div style="position:absolute;top:6px;left:6px;display:inline-flex;align-items:center;gap:5px;padding:3px 7px 3px 5px;background:linear-gradient(135deg,#131E2E 0%,#0B111A 100%);border:1px solid #A87B10;border-radius:4px;box-shadow:0 2px 8px rgba(0,0,0,0.45),inset 0 1px 0 rgba(230,185,60,0.2);white-space:nowrap;z-index:10;pointer-events:none;">
                            @if($tgVal === 'men')
                                <svg style="width:11px;height:11px;flex-shrink:0;" viewBox="0 0 24 24">
                                    <path fill="#DFC97A" d="M12 2l-2.5 5 2.5 1.5 2.5-1.5L12 2zm-4.5 5.5L3 9v13h7v-9l-2.5-2.5zm9 0l-2.5 2.5v9h7V9l-4.5-1.5zM11 9.5v8l1 3.5 1-3.5v-8l-1 1-1-1z"/>
                                </svg>
                            @elseif($tgVal === 'women')
                                <svg style="width:11px;height:11px;flex-shrink:0;" viewBox="0 0 24 24">
                                    <path fill="#DFC97A" d="M12 2a2.2 2.2 0 1 0 0 4.4 2.2 2.2 0 0 0 0-4.4zm-2.5 5.5L7 11.5l2 1.5-2 9h10l-2-9 2-1.5-2.5-4h-5zM11 9h2l1 3.5-2 1.5-2-1.5L11 9z"/>
                                </svg>
                            @elseif($tgVal === 'kids')
                                <svg style="width:11px;height:11px;flex-shrink:0;" viewBox="0 0 24 24">
                                    <path fill="#DFC97A" d="M12 2a3 3 0 1 0 0 6 3 3 0 0 0 0-6zm-4 7.5c-1.4 0-2.5 1.1-2.5 2.5v3.5c0 .8.7 1.5 1.5 1.5H8V21h8v-4h1c.8 0 1.5-.7 1.5-1.5V12c0-1.4-1.1-2.5-2.5-2.5h-8z"/>
                                </svg>
                            @else
                                <svg style="width:11px;height:11px;flex-shrink:0;" viewBox="0 0 24 24">
                                    <path fill="#DFC97A" d="M12 2l2.4 7.2h7.6l-6.1 4.5 2.3 7.3L12 16.5 5.8 21l2.3-7.3L2 9.2h7.6z"/>
                                </svg>
                            @endif
                            <div style="width:1px;height:9px;background:rgba(223,201,122,0.35);flex-shrink:0;"></div>
                            <span style="color:#DFC97A;font-family:ui-serif,Georgia,Cambria,'Times New Roman',serif;font-size:7.5px;font-weight:700;letter-spacing:0.16em;text-transform:uppercase;line-height:1;">{{ $product->target_group }}</span>
                        </div>
                    @endif
                </div>
                <a href="/products/{{ $product->id }}" class="block">
                    <h3 class="font-extrabold text-sm text-gray-900 group-hover:text-[#C0422A] transition-colors leading-tight line-clamp-2 uppercase tracking-tight">{{ $product->name }}</h3>
                </a>
                <div class="flex items-center gap-1.5 text-[10px] mt-1">
                    @if($product->avgRating)
                        <div class="flex items-center gap-1 font-bold text-yellow-500">
                            <span>★</span>
                            <span>{{ number_format($product->avgRating, 1) }}</span>
                            <span class="text-gray-400 font-normal">({{ $product->reviewCount }})</span>
                        </div>
                        <span class="text-gray-300">•</span>
                    @endif
                    <span class="text-gray-500 font-semibold text-[10px]">
                        {{ (int)($product->sold_count ?? 0) }} sold
                    </span>
                </div>
                <div class="flex items-center gap-2 mt-1">
                    <p class="text-base font-extrabold {{ $product->is_on_sale && $product->discount_percentage > 0 ? 'text-[#E02424]' : 'text-gray-900' }}">
                        ₱{{ number_format($product->salePrice) }}
                    </p>
                    @if($product->is_on_sale && $product->discount_percentage > 0)
                        <p class="text-xs font-bold text-gray-400 line-through">₱{{ number_format($product->price) }}</p>
                    @endif
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


    @if($products->isEmpty())
        <div class="text-center py-24 bg-white rounded-3xl border border-dashed border-gray-200">
            <div class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-4 border border-gray-100">
                <svg class="w-8 h-8 text-gray-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <h3 class="text-sm font-bold text-black uppercase tracking-widest mb-1">
                {{ in_array(request('sort'), ['best_sellers', 'trending', 'most_sold']) ? 'No Best Sellers Yet' : 'No Products Found' }}
            </h3>
            <p class="text-xs text-gray-400 mb-6">
                {{ in_array(request('sort'), ['best_sellers', 'trending', 'most_sold']) ? 'Products will appear here in real-time once sales are completed.' : 'Try a different search term or browse all collections.' }}
            </p>
            <a href="/#catalogue-section" class="ajax-filter-link px-6 py-2.5 bg-black hover:bg-[#C0422A] text-white text-xs font-bold uppercase tracking-widest rounded-full transition-all shadow-md inline-block">View All Products</a>
        </div>
    @endif
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
                            <img :src="window.getAppProductImage ? window.getAppProductImage(product.image) : '/uploads/products/default.jpg'" class="w-full h-full object-cover object-top" x-on:error="$event.target.src='/uploads/products/default.jpg'" alt="">
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
                    const intent = {
                        action: 'add_to_cart',
                        productId: this.product ? this.product.id : null,
                        quantity: this.quantity || 1,
                        size: this.selectedSize || null,
                        variation: 'Original',
                        redirectUrl: window.location.href
                    };
                    try { localStorage.setItem('lumbarong_pending_intent', JSON.stringify(intent)); } catch(err) {}
                    window.location.href = window.loginUrl;
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
        function updateCategoryHighlight(targetCat) {
            const normalizedTarget = (targetCat || '__all__').toLowerCase().trim();

            document.querySelectorAll('.category-pill-btn').forEach(btn => {
                const cat = (btn.getAttribute('data-category') || '__all__').toLowerCase().trim();
                const imgBox = btn.querySelector('.category-img-box');
                const badge = btn.querySelector('.category-active-badge');
                const label = btn.querySelector('.category-name-label');

                const isMatch = (normalizedTarget === '__all__' || normalizedTarget === '')
                    ? (cat === '__all__' || cat === '')
                    : (cat === normalizedTarget);

                if (imgBox) {
                    if (isMatch) {
                        imgBox.classList.remove('border-gray-200/80', 'border-gray-200', 'shadow-xs', 'shadow-sm');
                        imgBox.classList.add('border-[#C0422A]', 'ring-4', 'ring-[#C0422A]/25', 'scale-105', 'shadow-md');
                    } else {
                        imgBox.classList.remove('border-[#C0422A]', 'ring-4', 'ring-[#C0422A]/25', 'scale-105', 'shadow-md');
                        imgBox.classList.add('border-gray-200/80', 'shadow-xs');
                    }
                }

                if (badge) {
                    if (isMatch) {
                        badge.classList.remove('hidden');
                    } else {
                        badge.classList.add('hidden');
                    }
                }

                if (label) {
                    if (isMatch) {
                        label.classList.remove('font-medium', 'font-semibold', 'text-gray-700', 'text-gray-800');
                        label.classList.add('font-black', 'text-[#C0422A]');
                    } else {
                        label.classList.remove('font-black', 'text-[#C0422A]');
                        label.classList.add('font-medium', 'text-gray-700');
                    }
                }
            });
        }

        function scrollToCatalogue() {
            const section = document.getElementById('catalogue-section');
            if (section) {
                const topOffset = section.getBoundingClientRect().top + window.pageYOffset - 80;
                window.scrollTo({ top: topOffset, behavior: 'smooth' });
            }
        }

        function loadCatalogue(url, push = true) {
            const section = document.getElementById('catalogue-section');
            if (!section) {
                window.location.href = url;
                return;
            }

            // Sync category highlight immediately from requested URL
            try {
                const parsedUrl = new URL(url, window.location.origin);
                const urlCat = parsedUrl.searchParams.get('category');
                updateCategoryHighlight(urlCat);
            } catch(e) {}

            section.style.opacity = '0.4';
            section.style.pointerEvents = 'none';

            const fetchUrl = url.split('#')[0] || '/';

            fetch(fetchUrl, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(res => res.text())
                .then(html => {
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');
                    const newSection = doc.getElementById('catalogue-section');
                    if (newSection && section) {
                        section.innerHTML = newSection.innerHTML;

                        // Update Shop by Category row highlighting
                        const newCatBar = doc.getElementById('shop-by-category-section');
                        const currentCatBar = document.getElementById('shop-by-category-section');
                        if (newCatBar && currentCatBar) {
                            currentCatBar.innerHTML = newCatBar.innerHTML;
                        }

                        // Update All Categories Modal grid highlighting
                        const newModalGrid = doc.getElementById('all-categories-modal-grid');
                        const currentModalGrid = document.getElementById('all-categories-modal-grid');
                        if (newModalGrid && currentModalGrid) {
                            currentModalGrid.innerHTML = newModalGrid.innerHTML;
                        }

                        // Re-sync highlight classes after DOM update
                        try {
                            const parsedUrl = new URL(url, window.location.origin);
                            const urlCat = parsedUrl.searchParams.get('category');
                            updateCategoryHighlight(urlCat);
                        } catch(e) {}

                        if (push) history.pushState(null, '', url);
                        scrollToCatalogue();
                    } else {
                        window.location.href = url;
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

                // If clicking a category button, instantly highlight it on the UI immediately
                const catBtn = link.closest('.category-pill-btn') || (link.classList.contains('category-pill-btn') ? link : null);
                if (catBtn) {
                    const catVal = catBtn.getAttribute('data-category');
                    updateCategoryHighlight(catVal);
                } else if (link.href.includes('#catalogue-section') && !link.href.includes('category=')) {
                    updateCategoryHighlight('__all__');
                }

                loadCatalogue(link.href);
            }
        });

        // Ensure category highlight is synchronized on page load and popstate
        try {
            const initialCat = new URL(window.location.href).searchParams.get('category');
            updateCategoryHighlight(initialCat);
        } catch(e) {}

        // Auto scroll if page loaded with category filter or hash
        if (window.location.search.includes('category=') || window.location.search.includes('sort=') || window.location.search.includes('search=') || window.location.hash === '#catalogue-section') {
            setTimeout(scrollToCatalogue, 150);
        }

        let searchDebounceTimer = null;
        document.addEventListener('input', function(e) {
            const input = e.target.closest('.ajax-search-form input[name="search"], input[name="search"]');
            if (input) {
                clearTimeout(searchDebounceTimer);
                searchDebounceTimer = setTimeout(function() {
                    const form = input.closest('form');
                    if (form) {
                        const formData = new FormData(form);
                        const params = new URLSearchParams(formData).toString();
                        const url = (form.action || '/') + (params ? '?' + params : '');
                        loadCatalogue(url);
                    }
                }, 200);
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
