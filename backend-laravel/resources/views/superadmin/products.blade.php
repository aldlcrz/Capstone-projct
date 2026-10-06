@extends('layouts.superadmin')

@section('content')
<div class="space-y-6" x-data="{
    // Product Inspection Modal State
    inspectModal: false,
    inspectProduct: null,
    inspectActiveImage: 0,
    inspectImages: [],
    openInspect(product) {
        this.inspectProduct = product;
        this.inspectImages = this.getProductImages(product);
        this.inspectActiveImage = 0;
        this.inspectModal = true;
    },
    closeInspect() {
        this.inspectModal = false;
        this.inspectProduct = null;
        this.inspectImages = [];
    },
    getProductImages(product) {
        if (!product) return ['/uploads/products/default.jpg'];
        let imgs = [];
        let raw = product.image;
        if (typeof raw === 'string') {
            try {
                let parsed = JSON.parse(raw);
                if (Array.isArray(parsed)) raw = parsed;
            } catch(e) {}
        }
        if (Array.isArray(raw)) {
            raw.forEach(item => {
                let u = typeof item === 'object' ? (item.url || '') : item;
                if (u) imgs.push(this.getProductImage(u));
            });
        } else if (raw) {
            imgs.push(this.getProductImage(raw));
        }

        if (product.variations) {
            let vars = product.variations;
            if (typeof vars === 'string') {
                try { vars = JSON.parse(vars); } catch(e) {}
            }
            if (Array.isArray(vars)) {
                vars.forEach(v => {
                    let u = v.url || v.image;
                    if (u) {
                        let full = this.getProductImage(u);
                        if (!imgs.includes(full)) imgs.push(full);
                    }
                });
            }
        }
        return imgs.length > 0 ? imgs : ['/uploads/products/default.jpg'];
    },
    getProductSizes(product) {
        if (!product || !product.sizes) return [];
        let sz = product.sizes;
        if (typeof sz === 'string') {
            try { sz = JSON.parse(sz); } catch(e) { sz = sz.split(',').map(s => s.trim()); }
        }
        if (Array.isArray(sz)) {
            return sz.map(item => typeof item === 'object' ? (item.size || item.name || '') : item).filter(Boolean).filter(s => s.toLowerCase() !== 'custom');
        }
        return [];
    },
    formatPrice(price) {
        return parseFloat(price || 0).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
    },
    getProductImage(img) {
        if (!img) return '/uploads/products/default.jpg';
        let path = '';
        if (Array.isArray(img)) {
            path = img.length > 0 ? (typeof img[0] === 'object' ? (img[0].url || '') : img[0]) : '';
        } else if (typeof img === 'string') {
            path = img;
        }
        if (!path) return '/uploads/products/default.jpg';
        if (path.startsWith('http') || path.startsWith('data:')) return path;
        if (path.startsWith('/storage/')) return path;
        if (path.startsWith('storage/')) return '/' + path;
        if (path.startsWith('/uploads/')) return path;
        if (path.startsWith('uploads/')) return '/' + path;
        return '/storage/' + path.replace(/^\//, '');
    }
}">

    {{-- ═══ PAGE HEADER + SEARCH BAR ═══ --}}
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
        <div class="text-left space-y-0.5 shrink-0">
            <div class="inline-flex items-center gap-2">
                <span class="text-[9px] font-black uppercase tracking-[0.25em] text-[#C0422A]">Catalog Oversight</span>
                <span class="text-gray-300 text-xs">·</span>
                <span class="text-[9px] font-bold uppercase tracking-[0.2em] text-gray-400">Quality Control</span>
                <span class="text-gray-300 text-xs">·</span>
                <span class="text-[9px] font-black uppercase tracking-wider bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full border border-gray-200">View Only</span>
            </div>
            <h1 class="font-serif text-2xl sm:text-3xl font-bold text-gray-900 leading-tight">
                Product <span class="text-[#C0420A] font-light italic">Moderation</span>
            </h1>
            <p class="text-[11px] text-gray-400 font-medium">Browse artisan product submissions, catalog pricing, and quality standards in view-only mode</p>
        </div>

        {{-- Search Bar --}}
        <div class="flex-1 max-w-xl lg:max-w-md w-full">
            <form action="{{ route('superadmin.products') }}" method="GET" class="flex items-center gap-2 w-full">
                <input type="hidden" name="status" value="{{ $status ?? 'pending' }}">
                <div class="relative w-full">
                    <input type="text" name="search" value="{{ $search ?? '' }}"
                           placeholder="Search products, descriptions, shops..."
                           class="w-full pl-11 pr-5 py-2.5 bg-white border border-gray-200 rounded-full text-sm text-gray-800 placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-[#C0422A]/20 focus:border-[#C0422A] shadow-xs transition-all">
                    <svg class="w-5 h-5 text-gray-400 absolute left-4 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>
                @if(!empty($search))
                    <a href="{{ route('superadmin.products', ['status' => $status ?? 'pending']) }}" class="px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-full text-xs font-bold transition-all shrink-0">Clear</a>
                @endif
            </form>
        </div>
    </div>

    <!-- Filter Tabs & Status Pills -->
    @php
        $currentTab = $status ?? 'pending';
        $tabs = [
            'pending'  => ['label' => 'Pending Review', 'count' => $counts['pending'] ?? 0,  'icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z', 'color' => 'text-amber-600', 'bg' => 'bg-amber-500'],
            'approved' => ['label' => 'Approved / Live', 'count' => $counts['approved'] ?? 0, 'icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z', 'color' => 'text-emerald-600', 'bg' => 'bg-emerald-600'],
            'rejected' => ['label' => 'Rejected',         'count' => $counts['rejected'] ?? 0, 'icon' => 'M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z', 'color' => 'text-rose-600', 'bg' => 'bg-rose-600'],
            'all'      => ['label' => 'All Products',     'count' => $counts['all'] ?? 0,      'icon' => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4', 'color' => 'text-gray-900', 'bg' => 'bg-gray-900'],
        ];
    @endphp
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        @foreach($tabs as $tabKey => $tab)
            @php $isSelected = $currentTab === $tabKey; @endphp
            <a href="{{ route('superadmin.products', ['status' => $tabKey, 'search' => $search]) }}"
               class="group relative rounded-2xl px-4 py-3 flex items-center justify-between border transition-all duration-200 cursor-pointer {{ $isSelected ? 'bg-white border-gray-900 ring-2 ring-gray-900/15 shadow-sm -translate-y-0.5' : 'bg-white border-gray-100 hover:border-gray-300 hover:shadow-sm hover:-translate-y-0.5' }}">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-xl flex items-center justify-center shrink-0 transition-colors {{ $isSelected ? $tab['bg'] . ' text-white' : 'bg-gray-100 text-gray-500 group-hover:bg-gray-200' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $tab['icon'] }}"/></svg>
                    </div>
                    <div>
                        <div class="text-sm font-black text-gray-900 leading-none">{{ $tab['count'] }}</div>
                        <div class="text-[9px] font-bold uppercase tracking-wider text-gray-400 mt-1">{{ $tab['label'] }}</div>
                    </div>
                </div>
                @if($isSelected)
                    <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[8px] font-black uppercase tracking-wider bg-gray-900 text-white">Active</span>
                @endif
            </a>
        @endforeach
    </div>

    <!-- Products Table -->
    @if($products->isEmpty())
        <div class="bg-white rounded-2xl border border-gray-100 p-12 text-center shadow-xs">
            <div class="w-16 h-16 bg-gray-50 rounded-2xl flex items-center justify-center mx-auto mb-4 border border-gray-100">
                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
            </div>
            <h3 class="text-sm font-bold text-gray-900">No Products Found</h3>
            <p class="text-xs text-gray-400 mt-1">There are no products matching your selected filter or search term.</p>
        </div>
    @else
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
            <div class="overflow-x-auto no-scrollbar">
                <table class="w-full text-left text-xs min-w-175">
                    <thead>
                        <tr class="bg-[#F8F7F4] border-b border-gray-100 text-gray-400 uppercase tracking-widest font-bold text-[9px]">
                            <th class="px-6 py-3.5">Product</th>
                            <th class="px-6 py-3.5">Artisan / Seller</th>
                            <th class="px-6 py-3.5">Category</th>
                            <th class="px-6 py-3.5">Price &amp; Stock</th>
                            <th class="px-6 py-3.5">Status</th>
                            <th class="px-6 py-3.5 text-right">Details</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($products as $product)
                            <tr class="hover:bg-gray-50/80 transition-colors">
                                <!-- Product Entity -->
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        @php
                                            $imgUrl = $product->getImageUrl();
                                        @endphp
                                        <div class="w-12 h-14 rounded-xl overflow-hidden bg-gray-100 border border-gray-200 shrink-0 cursor-pointer hover:opacity-80 transition-opacity"
                                             @click="openInspect(@js($product))"
                                             title="Inspect: {{ $product->name }}">
                                            <img src="{{ $imgUrl }}" onerror="this.src='/uploads/products/default.jpg'" class="w-full h-full object-cover">
                                        </div>
                                        <div class="min-w-0 max-w-xs">
                                            <div class="text-xs font-bold text-gray-900 truncate cursor-pointer hover:text-[#C0422A] transition-colors"
                                                 @click="openInspect(@js($product))"
                                                 title="Inspect: {{ $product->name }}">{{ $product->name }}</div>
                                            <div class="text-[10px] text-gray-400 truncate">{{ $product->fabric_type ?: 'Barong Tagalog' }}</div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Seller Info -->
                                <td class="px-6 py-4">
                                    <div class="text-xs font-bold text-gray-900">{{ $product->seller?->shopName ?? $product->seller?->name ?? 'Artisan' }}</div>
                                    <div class="text-[10px] text-gray-400">{{ $product->seller?->email ?? 'No email' }}</div>
                                </td>

                                <!-- Category -->
                                <td class="px-6 py-4">
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-gray-100 text-gray-700">
                                        {{ $product->category->name ?? 'Uncategorized' }}
                                    </span>
                                </td>

                                <!-- Price & Stock -->
                                <td class="px-6 py-4">
                                    @if($product->is_on_sale && $product->discount_percentage > 0)
                                        <div class="flex items-baseline gap-1.5 flex-wrap">
                                            <span class="text-xs font-black text-[#C0422A] font-mono">₱{{ number_format((float)$product->sale_price, 2) }}</span>
                                            <span class="text-[10px] text-gray-400 line-through font-mono">₱{{ number_format((float)$product->price, 2) }}</span>
                                            <span class="text-[9px] font-bold text-red-600 bg-red-50 border border-red-100 px-1 py-0.5 rounded">-{{ round($product->discount_percentage) }}%</span>
                                        </div>
                                    @else
                                        <div class="text-xs font-bold text-gray-900 font-mono">₱{{ number_format((float)$product->price, 2) }}</div>
                                    @endif
                                    <div class="text-[10px] text-gray-500 font-medium">Stock: {{ $product->stock }} pcs</div>
                                </td>

                                <!-- Status Badge -->
                                <td class="px-6 py-4">
                                    @php
                                        $statusBadges = [
                                            'approved' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                            'pending'  => 'bg-amber-50 text-amber-700 border-amber-200',
                                            'rejected' => 'bg-rose-50 text-rose-700 border-rose-200',
                                        ];
                                    @endphp
                                    <span class="px-2.5 py-1 rounded-full text-[9px] font-black uppercase tracking-wider border {{ $statusBadges[$product->status] ?? 'bg-gray-50 text-gray-700 border-gray-200' }}">
                                        {{ $product->status }}
                                    </span>
                                </td>

                                <!-- Actions / Inspect Trigger -->
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end">
                                        <!-- Inspect Modal Trigger -->
                                        <button type="button" 
                                                @click="openInspect(@js($product))"
                                                class="px-3 py-1.5 bg-gray-50 hover:bg-gray-200 text-gray-700 border border-gray-200/80 rounded-xl text-[10px] font-bold uppercase tracking-wider transition-all flex items-center gap-1.5 cursor-pointer"
                                                title="Inspect Product Full Details & Sizing">
                                            <svg class="w-3.5 h-3.5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                            <span>Inspect</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if($products->hasPages())
                <div class="p-4 sm:p-6 bg-gray-50/50 border-t border-gray-100">
                    {{ $products->appends(['status' => $status, 'search' => $search])->links() }}
                </div>
            @endif
        </div>
    @endif

    {{-- ==================== INSPECT PRODUCT MODAL ==================== --}}
    <div x-show="inspectModal" 
         x-cloak 
         style="display: none;"
         class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4 bg-black/60 backdrop-blur-xs"
         @keydown.escape.window="closeInspect()">
        <div class="relative bg-white rounded-3xl shadow-2xl w-full max-w-4xl max-h-[92vh] flex flex-col overflow-hidden border border-gray-100 text-left"
             @click.away="closeInspect()">
            
            {{-- Modal Header --}}
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between gap-3 shrink-0 bg-gray-50/80">
                <div class="flex items-center gap-2.5 flex-wrap">
                    <span class="text-xs font-bold uppercase tracking-wider text-gray-900">
                        Product Oversight &amp; Inspection
                    </span>
                    <span class="text-gray-300">•</span>
                    <span class="px-2.5 py-0.5 rounded-full text-[9px] font-black uppercase tracking-wider"
                          :class="{
                              'bg-emerald-100 text-emerald-800 border border-emerald-200': (inspectProduct?.status || '').toLowerCase() === 'approved',
                              'bg-rose-100 text-rose-800 border border-rose-200': (inspectProduct?.status || '').toLowerCase() === 'rejected',
                              'bg-amber-100 text-amber-800 border border-amber-200': !['approved', 'rejected'].includes((inspectProduct?.status || '').toLowerCase())
                          }"
                          x-text="(inspectProduct?.status || 'pending').toUpperCase()">
                    </span>
                    <template x-if="inspectProduct?.category?.name">
                        <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-gray-100 text-gray-700 border border-gray-200"
                              x-text="inspectProduct.category.name">
                        </span>
                    </template>
                </div>

                <div class="flex items-center gap-3">
                    <template x-if="inspectProduct?.id">
                        <a :href="'/products/' + inspectProduct.id" target="_blank"
                           class="text-xs font-bold text-[#C0422A] hover:text-black flex items-center gap-1 transition-colors"
                           title="Open standalone product page in a new tab">
                            <span>Open Live Page</span>
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                        </a>
                    </template>
                    <button type="button" @click="closeInspect()" 
                            class="w-8 h-8 rounded-full bg-gray-100 hover:bg-gray-200 text-gray-500 hover:text-black flex items-center justify-center transition-colors cursor-pointer">
                        ✕
                    </button>
                </div>
            </div>

            {{-- Modal Body (Scrollable) --}}
            <div class="p-6 sm:p-8 overflow-y-auto flex-1 space-y-6" x-show="inspectProduct">
                <template x-if="inspectProduct">
                    <div class="grid grid-cols-1 md:grid-cols-12 gap-6 md:gap-8 items-start">
                        
                        {{-- Left Gallery --}}
                        <div class="md:col-span-5 space-y-3">
                            <div class="relative aspect-4/5 rounded-2xl overflow-hidden bg-gray-100 border border-gray-200 shadow-sm flex items-center justify-center">
                                <img :src="inspectImages[inspectActiveImage] || getProductImage(inspectProduct.image)" 
                                     class="w-full h-full object-cover object-top"
                                     onerror="this.src='/uploads/products/default.jpg'">
                                
                                <template x-if="inspectProduct.is_on_sale && inspectProduct.discount_percentage > 0">
                                    <span class="absolute top-3 left-3 px-2.5 py-1 bg-red-600 text-white rounded-lg text-[10px] font-black uppercase tracking-wider shadow-sm">
                                        <span x-text="Math.round(inspectProduct.discount_percentage) + '% OFF'"></span>
                                    </span>
                                </template>
                            </div>

                            {{-- Thumbnails --}}
                            <template x-if="inspectImages.length > 1">
                                <div class="flex gap-2 overflow-x-auto pb-1 no-scrollbar">
                                    <template x-for="(img, idx) in inspectImages" :key="idx">
                                        <button type="button" @click="inspectActiveImage = idx"
                                                class="w-14 h-16 rounded-xl overflow-hidden border-2 transition-all shrink-0 cursor-pointer shadow-2xs"
                                                :class="inspectActiveImage === idx ? 'border-[#C0422A] ring-2 ring-[#C0422A]/20 opacity-100 scale-98' : 'border-gray-200 opacity-60 hover:opacity-100'">
                                            <img :src="img" class="w-full h-full object-cover object-top" onerror="this.src='/uploads/products/default.jpg'">
                                        </button>
                                    </template>
                                </div>
                            </template>
                        </div>

                        {{-- Right Product Specifications --}}
                        <div class="md:col-span-7 space-y-4">
                            <div>
                                <h2 class="font-serif text-2xl font-bold text-gray-900 leading-tight" x-text="inspectProduct.name"></h2>
                                <p class="text-xs text-gray-500 mt-1">
                                    By <strong class="text-gray-800" x-text="inspectProduct.seller?.shopName || inspectProduct.seller?.name || 'Artisan'"></strong>
                                    <template x-if="inspectProduct.seller?.email">
                                        <span class="text-gray-400" x-text="' (' + inspectProduct.seller.email + ')'"></span>
                                    </template>
                                </p>
                            </div>

                            {{-- Price and Inventory --}}
                            <div class="flex items-center gap-3 pb-3 border-b border-gray-100 flex-wrap">
                                <template x-if="inspectProduct.is_on_sale && inspectProduct.discount_percentage > 0">
                                    <div class="flex items-baseline gap-2 flex-wrap">
                                        <span class="text-2xl font-extrabold text-[#C0422A]" x-text="'₱' + formatPrice(inspectProduct.price * (1 - inspectProduct.discount_percentage / 100))"></span>
                                        <span class="text-sm font-semibold line-through text-gray-400" x-text="'₱' + formatPrice(inspectProduct.price)"></span>
                                        <span class="px-2 py-0.5 bg-red-100 text-red-700 text-xs font-bold rounded-lg" x-text="'-' + Math.round(inspectProduct.discount_percentage) + '% OFF'"></span>
                                    </div>
                                </template>
                                <template x-if="!(inspectProduct.is_on_sale && inspectProduct.discount_percentage > 0)">
                                    <span class="text-2xl font-extrabold text-gray-900" x-text="'₱' + formatPrice(inspectProduct.price)"></span>
                                </template>
                                <span class="px-2.5 py-1 rounded-xl text-xs font-bold"
                                      :class="(inspectProduct.stock > 0) ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : 'bg-red-50 text-red-800 border border-red-200'"
                                      x-text="inspectProduct.stock > 0 ? inspectProduct.stock + ' pieces in stock' : 'Out of Stock'">
                                </span>
                            </div>

                            {{-- If currently rejected, show reason banner --}}
                            <template x-if="(inspectProduct.status || '').toLowerCase() === 'rejected' && inspectProduct.rejectionReason">
                                <div class="p-3.5 rounded-2xl bg-rose-50 border border-rose-200 text-xs text-rose-900 space-y-1">
                                    <strong class="block uppercase text-[10px] tracking-wider text-rose-700 font-black">Current Rejection Reason:</strong>
                                    <p x-text="inspectProduct.rejectionReason" class="leading-relaxed"></p>
                                </div>
                            </template>

                            {{-- Available Sizes --}}
                            <div class="space-y-1.5">
                                <span class="text-xs font-bold text-gray-700 block uppercase tracking-wider">Available Sizes:</span>
                                <div class="flex flex-wrap gap-1.5">
                                    <template x-for="sz in getProductSizes(inspectProduct)" :key="sz">
                                        <span class="px-2.5 py-1 rounded-xl text-xs font-bold bg-gray-100 border border-gray-200 text-gray-800"
                                              x-text="sz">
                                        </span>
                                    </template>
                                    <template x-if="getProductSizes(inspectProduct).length === 0">
                                        <span class="text-xs text-gray-400 italic">Standard sizes</span>
                                    </template>
                                </div>
                            </div>

                            {{-- Specifications Grid --}}
                            <div class="grid grid-cols-2 gap-2 text-xs pt-2">
                                <template x-if="inspectProduct.fabric_type">
                                    <div class="p-2.5 bg-gray-50 rounded-xl border border-gray-100">
                                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider block">Fabric Type</span>
                                        <span class="font-bold text-gray-800" x-text="inspectProduct.fabric_type"></span>
                                    </div>
                                </template>
                                <template x-if="inspectProduct.collar_type">
                                    <div class="p-2.5 bg-gray-50 rounded-xl border border-gray-100">
                                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider block">Collar Style</span>
                                        <span class="font-bold text-gray-800" x-text="inspectProduct.collar_type"></span>
                                    </div>
                                </template>
                                <template x-if="inspectProduct.artisan_region">
                                    <div class="p-2.5 bg-gray-50 rounded-xl border border-gray-100">
                                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider block">Artisan Region</span>
                                        <span class="font-bold text-gray-800" x-text="inspectProduct.artisan_region"></span>
                                    </div>
                                </template>
                                <template x-if="inspectProduct.shippingFee !== undefined">
                                    <div class="p-2.5 bg-gray-50 rounded-xl border border-gray-100">
                                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider block">Shipping Fee</span>
                                        <span class="font-bold text-gray-800" x-text="'₱' + formatPrice(inspectProduct.shippingFee)"></span>
                                    </div>
                                </template>
                            </div>

                            {{-- Description --}}
                            <div class="space-y-1 pt-2">
                                <span class="text-xs font-bold text-gray-700 block uppercase tracking-wider">Product Description:</span>
                                <div class="p-3.5 bg-gray-50 rounded-2xl border border-gray-100 text-xs text-gray-700 leading-relaxed max-h-40 overflow-y-auto whitespace-pre-line"
                                     x-text="inspectProduct.description || 'No description provided by the artisan.'">
                                </div>
                            </div>

                        </div>
                    </div>
                </template>
            </div>

            {{-- Modal Footer with Moderation Actions --}}
            <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex items-center justify-end gap-3 shrink-0" x-show="inspectProduct">
                <button type="button" @click="closeInspect()" class="px-5 py-2 text-xs font-bold text-gray-700 bg-white border border-gray-200 hover:bg-gray-100 rounded-xl transition-all cursor-pointer">
                    Close Preview
                </button>
            </div>

        </div>
    </div>

</div>
@endsection
    </div>

</div>
@endsection
