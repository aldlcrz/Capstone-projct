@extends('layouts.admin')

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
            return sz.map(item => typeof item === 'object' ? (item.size || item.name || '') : item).filter(Boolean);
        }
        return [];
    },
    formatPrice(price) {
        return parseFloat(price || 0).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
    },

    // Approval State
    approveModal: false,
    isApproving: false,
    approveProductId: null,
    approveProductName: '',
    approveProductSeller: '',
    approveProductPrice: '',
    approveProductImage: '',
    openApprove(product) {
        this.approveProductId = product.id;
        this.approveProductName = product.name;
        this.approveProductSeller = (product.seller ? (product.seller.shopName || product.seller.name) : 'Artisan');
        this.approveProductPrice = parseFloat(product.price || 0).toLocaleString(undefined, {minimumFractionDigits: 2});
        this.approveProductImage = product.image ? (Array.isArray(product.image) ? product.image[0] : product.image) : '/uploads/products/default.jpg';
        this.isApproving = false;
        this.approveModal = true;
    },

    // Rejection State
    rejectModal: false,
    rejectProductId: null,
    rejectProductName: '',
    rejectReason: '',
    openReject(product) {
        this.rejectProductId = product.id;
        this.rejectProductName = product.name;
        this.rejectReason = '';
        this.rejectModal = true;
    },

    // Delete State
    deleteModal: false,
    deleteProductId: null,
    deleteProductName: '',
    deleteReason: '',
    deleteConfirmChecked: false,
    openDelete(product) {
        this.deleteProductId = product.id;
        this.deleteProductName = product.name;
        this.deleteReason = '';
        this.deleteConfirmChecked = false;
        this.deleteModal = true;
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

    {{-- Page Header & Search Bar --}}
    <div class="space-y-3.5">
        <div>
            <div class="text-[10px] font-bold text-[#C0422A] uppercase tracking-[0.2em] mb-1">Product Control</div>
            <h1 class="font-serif text-2xl sm:text-3xl font-bold text-black">
                Product <span class="text-[#C0420A] font-light italic">Moderation &amp; Catalog</span>
            </h1>
        </div>

        {{-- Search Input (Below title) --}}
        <form method="GET" class="flex items-center gap-2 max-w-sm sm:max-w-md">
            @if(request('status'))
                <input type="hidden" name="status" value="{{ request('status') }}">
            @endif
            <div class="relative w-full">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search products, descriptions, artisans..." 
                       class="w-full pl-9 pr-4 py-2.5 bg-white border border-gray-200 rounded-full text-xs text-gray-800 placeholder:text-gray-400 focus:outline-none focus:border-[#C0422A] shadow-xs">
                <svg class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </div>
            @if(request('search'))
                <a href="{{ request()->fullUrlWithQuery(['search' => null, 'page' => 1]) }}" class="px-3 py-2 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-full text-[10px] font-bold">Clear</a>
            @endif
        </form>
    </div>

    @php
        $currentStatus = request('status', 'pending');
    @endphp

    {{-- Filter Pills --}}
    <div class="flex items-center gap-2 overflow-x-auto no-scrollbar pt-1">
        {{-- ALL --}}
        <a href="{{ request()->fullUrlWithQuery(['status' => null, 'page' => 1]) }}"
           class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-[10px] sm:text-[11px] font-black uppercase tracking-wider transition-all {{ empty($currentStatus) || $currentStatus === 'all' ? 'bg-black text-white shadow-sm' : 'bg-white text-gray-700 border border-gray-200 hover:bg-gray-50' }}">
            <span>ALL</span>
            <span class="px-2 py-0.5 rounded-full text-[9px] font-black {{ empty($currentStatus) || $currentStatus === 'all' ? 'bg-white/20 text-white' : 'bg-gray-100 text-gray-600' }}">{{ $counts['all'] }}</span>
        </a>

        {{-- PENDING --}}
        <a href="{{ request()->fullUrlWithQuery(['status' => 'pending', 'page' => 1]) }}"
           class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-[10px] sm:text-[11px] font-black uppercase tracking-wider transition-all {{ $currentStatus === 'pending' ? 'bg-black text-white shadow-sm' : 'bg-white text-gray-700 border border-gray-200 hover:bg-gray-50' }}">
            <span>PENDING</span>
            <span class="px-2 py-0.5 rounded-full text-[9px] font-black {{ $currentStatus === 'pending' ? 'bg-white/20 text-white' : 'bg-gray-100 text-gray-600' }}">{{ $counts['pending'] }}</span>
        </a>

        {{-- APPROVED --}}
        <a href="{{ request()->fullUrlWithQuery(['status' => 'approved', 'page' => 1]) }}"
           class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-[10px] sm:text-[11px] font-black uppercase tracking-wider transition-all {{ $currentStatus === 'approved' ? 'bg-black text-white shadow-sm' : 'bg-white text-gray-700 border border-gray-200 hover:bg-gray-50' }}">
            <span>APPROVED</span>
            <span class="px-2 py-0.5 rounded-full text-[9px] font-black {{ $currentStatus === 'approved' ? 'bg-white/20 text-white' : 'bg-gray-100 text-gray-600' }}">{{ $counts['approved'] }}</span>
        </a>

        {{-- REJECTED --}}
        <a href="{{ request()->fullUrlWithQuery(['status' => 'rejected', 'page' => 1]) }}"
           class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-[10px] sm:text-[11px] font-black uppercase tracking-wider transition-all {{ $currentStatus === 'rejected' ? 'bg-black text-white shadow-sm' : 'bg-white text-gray-700 border border-gray-200 hover:bg-gray-50' }}">
            <span>REJECTED</span>
            <span class="px-2 py-0.5 rounded-full text-[9px] font-black {{ $currentStatus === 'rejected' ? 'bg-white/20 text-white' : 'bg-gray-100 text-gray-600' }}">{{ $counts['rejected'] }}</span>
        </a>
    </div>

    {{-- Product Grid --}}
    @if($products->isEmpty())
        <div class="text-center py-20 bg-white rounded-3xl border border-dashed border-gray-200 shadow-sm p-8">
            <svg class="w-12 h-12 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
            </svg>
            <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wider">No Products Found</h3>
            <p class="text-xs text-gray-400 mt-1">There are currently no products under this status filter.</p>
        </div>
    @else
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-5">
            @foreach($products as $product)
                @php
                    $productStatus = strtolower($product->status ?? 'pending');
                    $statusBadgeStyles = [
                        'pending'  => 'bg-amber-100 text-amber-800 border-amber-200',
                        'approved' => 'bg-emerald-100 text-emerald-800 border-emerald-200',
                        'rejected' => 'bg-rose-100 text-rose-800 border-rose-200',
                    ];
                @endphp
                <div class="bg-white border border-gray-100 rounded-3xl shadow-sm hover:shadow-md transition-all overflow-hidden flex flex-col group">
                    
                    {{-- Compact Scaled Product Image --}}
                    <div class="relative w-full h-44 sm:h-48 bg-stone-100 overflow-hidden shrink-0 border-b border-gray-100 cursor-pointer group"
                         @click="openInspect(@js($product))"
                         title="Click to inspect product details">
                        <img src="{{ $product->getImageUrl() }}" 
                             onerror="this.src='/uploads/products/default.jpg'"
                             class="w-full h-full object-cover object-top group-hover:scale-105 transition-transform duration-300">
                        
                        {{-- Top Badge Overlays --}}
                        <div class="absolute top-2.5 left-2.5 right-2.5 flex items-center justify-between pointer-events-none">
                            <span class="px-2 py-0.5 bg-black/75 backdrop-blur-md rounded-md text-[9px] font-bold text-white uppercase tracking-wider">
                                {{ $product->category->name ?? 'Artisan Piece' }}
                            </span>
                            <span class="px-2 py-0.5 border rounded-md text-[9px] font-black uppercase tracking-wider backdrop-blur-md {{ $statusBadgeStyles[$productStatus] ?? 'bg-gray-100 text-gray-700' }}">
                                {{ $productStatus }}
                            </span>
                        </div>

                        @if($product->is_on_sale && (float)($product->discount_percentage ?? 0) > 0)
                            <div class="absolute bottom-2 left-2.5 px-2 py-0.5 bg-red-600 text-white rounded text-[9px] font-black uppercase tracking-wider shadow-sm">
                                {{ round($product->discount_percentage) }}% OFF
                            </div>
                        @endif

                        {{-- Hover Inspect Overlay Hint --}}
                        <div class="absolute inset-0 bg-black/30 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                            <span class="px-3 py-1.5 bg-white/90 backdrop-blur-xs text-black text-xs font-bold rounded-xl shadow-md flex items-center gap-1.5 transform translate-y-1 group-hover:translate-y-0 transition-transform">
                                <svg class="w-3.5 h-3.5 text-[#C0420A]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                <span>Quick Inspect</span>
                            </span>
                        </div>
                    </div>

                    {{-- Product Info --}}
                    <div class="p-4 sm:p-5 flex-1 flex flex-col justify-between space-y-3">
                        <div class="space-y-1.5">
                            <div class="flex justify-between items-start gap-2">
                                <h3 class="font-serif font-bold text-sm text-gray-900 line-clamp-1 leading-snug cursor-pointer hover:text-[#C0420A] transition-colors" 
                                    @click="openInspect(@js($product))"
                                    title="Inspect: {{ $product->name }}">
                                    {{ $product->name }}
                                </h3>
                                <div class="text-xs font-black text-[#C0420A] shrink-0">
                                    ₱{{ number_format((float) $product->price, 2) }}
                                </div>
                            </div>
                            
                            <p class="text-[11px] text-gray-500 font-medium line-clamp-1">
                                By <strong class="text-gray-800">{{ $product->seller->shopName ?? $product->seller->name ?? 'Artisan' }}</strong>
                            </p>

                            <p class="text-[11px] text-gray-600 line-clamp-2 leading-relaxed font-normal">
                                {{ $product->description ?: 'No description provided.' }}
                            </p>
                        </div>

                        {{-- If Rejected, show reason box --}}
                        @if($productStatus === 'rejected' && $product->rejectionReason)
                            <div class="p-2.5 bg-rose-50 border border-rose-200 rounded-xl text-[10px] text-rose-900 leading-snug">
                                <span class="font-bold uppercase tracking-wider text-[9px] text-rose-700 block mb-0.5">Rejection Reason:</span>
                                <span class="italic font-medium">{{ $product->rejectionReason }}</span>
                            </div>
                        @endif

                        {{-- Action Buttons --}}
                        <div class="pt-2 border-t border-gray-100 flex items-center gap-1.5">
                            @if($productStatus === 'pending')
                                {{-- Inspect Modal Trigger --}}
                                <button type="button" @click="openInspect(@js($product))"
                                    title="Inspect full product details and sizing"
                                    class="px-2.5 py-2 bg-stone-100 hover:bg-stone-200 text-stone-700 text-[10px] font-bold uppercase tracking-wider rounded-xl transition-all cursor-pointer text-center flex items-center gap-1">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    <span>Inspect</span>
                                </button>

                                {{-- Modal-Triggered Approve Button --}}
                                <button type="button" @click="openApprove(@js($product))"
                                    class="flex-1 py-2 bg-stone-900 hover:bg-emerald-600 text-white text-[10px] font-bold uppercase tracking-wider rounded-xl transition-all cursor-pointer text-center">
                                    Approve
                                </button>
                                
                                {{-- Modal-Triggered Reject Button --}}
                                <button type="button" @click="openReject(@js($product))"
                                    class="flex-1 py-2 bg-white border border-gray-200 hover:bg-rose-50 text-rose-600 text-[10px] font-bold uppercase tracking-wider rounded-xl transition-all cursor-pointer text-center">
                                    Reject
                                </button>
                            @elseif($productStatus === 'approved')
                                <button type="button" @click="openInspect(@js($product))"
                                    class="flex-1 py-2 bg-stone-100 hover:bg-stone-200 text-stone-700 text-[10px] font-bold uppercase tracking-wider rounded-xl transition-all cursor-pointer text-center flex items-center justify-center gap-1">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    <span>Inspect</span>
                                </button>
                                <button type="button" @click="openReject(@js($product))"
                                    class="px-3 py-2 bg-white border border-gray-200 hover:bg-amber-50 text-amber-700 text-[10px] font-bold uppercase tracking-wider rounded-xl transition-all cursor-pointer">
                                    Revoke
                                </button>
                            @elseif($productStatus === 'rejected')
                                <button type="button" @click="openInspect(@js($product))"
                                    title="Inspect product details"
                                    class="px-2.5 py-2 bg-stone-100 hover:bg-stone-200 text-stone-700 text-[10px] font-bold uppercase tracking-wider rounded-xl transition-all cursor-pointer text-center flex items-center gap-1">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    <span>Inspect</span>
                                </button>
                                <button type="button" @click="openApprove(@js($product))"
                                    class="flex-1 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-[10px] font-bold uppercase tracking-wider rounded-xl transition-all cursor-pointer text-center">
                                    Re-Approve
                                </button>
                            @endif

                            {{-- Delete Product with Reason Trigger --}}
                            <button type="button" @click="openDelete(@js($product))"
                                title="Permanently Delete Product"
                                class="w-8 h-8 rounded-xl bg-gray-50 hover:bg-red-100 text-gray-400 hover:text-red-600 flex items-center justify-center transition-all cursor-pointer shrink-0">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </button>
                        </div>

                    </div>
                </div>
            @endforeach
        </div>

        {{-- Pagination --}}
        <div class="pt-4">
            {{ $products->withQueryString()->links() }}
        </div>
    @endif

    {{-- ─── Inspect Product Details Modal ─── --}}
    <div x-show="inspectModal" 
         x-cloak 
         style="display: none;"
         class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4 bg-black/60 backdrop-blur-sm"
         @keydown.escape.window="closeInspect()">
        <div class="relative bg-white rounded-3xl shadow-2xl w-full max-w-4xl max-h-[92vh] flex flex-col overflow-hidden border border-gray-100 text-left"
             @click.away="closeInspect()">
            
            {{-- Modal Header --}}
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between gap-3 shrink-0"
                 style="background-color: #FAF7F2;">
                <div class="flex items-center gap-2.5 flex-wrap">
                    <span class="text-xs font-bold uppercase tracking-wider" style="color: #1E1915;">
                        Product Inspection
                    </span>
                    <span class="text-gray-300">•</span>
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider"
                          :class="{
                              'bg-emerald-100 text-emerald-800 border border-emerald-200': (inspectProduct?.status || '').toLowerCase() === 'approved',
                              'bg-rose-100 text-rose-800 border border-rose-200': (inspectProduct?.status || '').toLowerCase() === 'rejected',
                              'bg-amber-100 text-amber-800 border border-amber-200': !['approved', 'rejected'].includes((inspectProduct?.status || '').toLowerCase())
                          }"
                          x-text="(inspectProduct?.status || 'pending').toUpperCase()">
                    </span>
                    <template x-if="inspectProduct?.category?.name">
                        <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-stone-100 text-stone-700 border border-stone-200"
                              x-text="inspectProduct.category.name">
                        </span>
                    </template>
                </div>

                <div class="flex items-center gap-3">
                    <template x-if="inspectProduct?.id">
                        <a :href="'/products/' + inspectProduct.id" target="_blank"
                           class="text-xs font-bold text-amber-900 hover:text-black flex items-center gap-1 transition-colors"
                           title="Open standalone product page in a new tab">
                            <span>Open Page</span>
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                        </a>
                    </template>
                    <button type="button" @click="closeInspect()" 
                            class="w-8 h-8 rounded-full bg-stone-100 hover:bg-stone-200 text-gray-500 hover:text-black flex items-center justify-center transition-colors cursor-pointer">
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
                            <div class="relative aspect-4/5 rounded-2xl overflow-hidden bg-stone-100 border border-stone-200 shadow-sm flex items-center justify-center">
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
                                                :class="inspectActiveImage === idx ? 'border-amber-600 ring-2 ring-amber-500/20 opacity-100 scale-98' : 'border-gray-200 opacity-60 hover:opacity-100'">
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
                            <div class="flex items-baseline gap-3 pb-3 border-b border-gray-100 flex-wrap">
                                <span class="text-2xl font-extrabold text-gray-900" x-text="'₱' + formatPrice(inspectProduct.price)"></span>
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
                                        <span class="px-2.5 py-1 rounded-xl text-xs font-bold bg-stone-100 border border-stone-200 text-stone-800"
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
                                    <div class="p-2.5 bg-stone-50 rounded-xl border border-stone-200/80">
                                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider block">Fabric Type</span>
                                        <span class="font-bold text-gray-800" x-text="inspectProduct.fabric_type"></span>
                                    </div>
                                </template>
                                <template x-if="inspectProduct.collar_type">
                                    <div class="p-2.5 bg-stone-50 rounded-xl border border-stone-200/80">
                                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider block">Collar Style</span>
                                        <span class="font-bold text-gray-800" x-text="inspectProduct.collar_type"></span>
                                    </div>
                                </template>
                                <template x-if="inspectProduct.artisan_region">
                                    <div class="p-2.5 bg-stone-50 rounded-xl border border-stone-200/80">
                                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider block">Artisan Region</span>
                                        <span class="font-bold text-gray-800" x-text="inspectProduct.artisan_region"></span>
                                    </div>
                                </template>
                                <template x-if="inspectProduct.shippingFee !== undefined">
                                    <div class="p-2.5 bg-stone-50 rounded-xl border border-stone-200/80">
                                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider block">Shipping Fee</span>
                                        <span class="font-bold text-gray-800" x-text="'₱' + formatPrice(inspectProduct.shippingFee)"></span>
                                    </div>
                                </template>
                            </div>

                            {{-- Description --}}
                            <div class="space-y-1 pt-2">
                                <span class="text-xs font-bold text-gray-700 block uppercase tracking-wider">Product Description:</span>
                                <div class="p-3.5 bg-stone-50 rounded-2xl border border-stone-200 text-xs text-gray-700 leading-relaxed max-h-40 overflow-y-auto whitespace-pre-line"
                                     x-text="inspectProduct.description || 'No description provided by the artisan.'">
                                </div>
                            </div>

                        </div>
                    </div>
                </template>
            </div>

            {{-- Modal Footer with Moderation Actions --}}
            <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex flex-wrap items-center justify-between gap-3 shrink-0" x-show="inspectProduct">
                <template x-if="inspectProduct">
                    <div class="flex items-center justify-between w-full gap-3 flex-wrap">
                        <div>
                            <button type="button" @click="closeInspect()" class="px-4 py-2 text-xs font-bold text-gray-600 bg-white border border-gray-200 hover:bg-gray-100 rounded-xl transition-all cursor-pointer">
                                Close Preview
                            </button>
                        </div>

                        <div class="flex items-center gap-2 flex-wrap">
                            {{-- Approve Action --}}
                            <template x-if="['pending', 'rejected'].includes((inspectProduct?.status || '').toLowerCase())">
                                <button type="button" 
                                        @click="openApprove(inspectProduct); closeInspect();"
                                        class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold uppercase tracking-wider rounded-xl transition-all shadow-sm flex items-center gap-1.5 cursor-pointer">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                    <span x-text="(inspectProduct?.status || '').toLowerCase() === 'rejected' ? 'Re-Approve' : 'Approve Product'"></span>
                                </button>
                            </template>

                            {{-- Reject / Revoke Action --}}
                            <template x-if="['pending', 'approved'].includes((inspectProduct?.status || '').toLowerCase())">
                                <button type="button" 
                                        @click="openReject(inspectProduct); closeInspect();"
                                        class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold uppercase tracking-wider rounded-xl transition-all shadow-sm flex items-center gap-1.5 cursor-pointer">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                                    <span x-text="(inspectProduct?.status || '').toLowerCase() === 'approved' ? 'Revoke Approval' : 'Reject Product'"></span>
                                </button>
                            </template>

                            {{-- Delete / Archive Action --}}
                            <button type="button" 
                                    @click="openDelete(inspectProduct); closeInspect();"
                                    class="px-3 py-2 bg-white border border-gray-200 hover:bg-red-50 text-gray-400 hover:text-red-600 rounded-xl transition-all cursor-pointer flex items-center gap-1"
                                    title="Delete Product">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                <span class="text-xs font-semibold">Delete</span>
                            </button>
                        </div>
                    </div>
                </template>
            </div>

        </div>
    </div>

    {{-- ─── Approve Product Confirmation Modal ─── --}}
    <div x-show="approveModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" x-cloak>
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="approveModal = false"></div>
        <div class="relative bg-white rounded-3xl shadow-2xl w-full max-w-md p-6 sm:p-7 space-y-5 z-10 border border-gray-100">
            <div class="flex items-start gap-3.5">
                <div class="w-11 h-11 rounded-2xl bg-emerald-100 text-emerald-600 flex items-center justify-center shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
                <div class="min-w-0">
                    <h3 class="text-base sm:text-lg font-bold text-gray-900 leading-tight">Approve Product Listing</h3>
                    <p class="text-xs text-gray-500 mt-1">Publish this artisan masterpiece to the LumBarong live catalog.</p>
                </div>
            </div>

            {{-- Summary Card --}}
            <div class="p-3 bg-stone-50 border border-stone-200 rounded-2xl flex items-center gap-3">
                <img :src="getProductImage(approveProductImage)" class="w-12 h-12 object-cover object-top rounded-xl border border-stone-200 shrink-0" onerror="this.src='/uploads/products/default.jpg'">
                <div class="min-w-0">
                    <h4 class="font-serif font-bold text-xs text-gray-900 line-clamp-1" x-text="approveProductName"></h4>
                    <p class="text-[11px] text-gray-500">By <span class="font-semibold text-gray-700" x-text="approveProductSeller"></span></p>
                    <p class="text-[11px] font-black text-[#C0422A]" x-text="'₱' + approveProductPrice"></p>
                </div>
            </div>

            <p class="text-xs text-gray-600 leading-relaxed font-medium">
                Upon approval, this product will be immediately discoverable in the marketplace and an email notification will be dispatched to the artisan.
            </p>

            <form :action="'/admin/products/' + approveProductId + '/approve'" method="POST" @submit="isApproving = true" class="flex gap-3 pt-2">
                @csrf
                @method('PATCH')
                <button type="button" :disabled="isApproving" @click="approveModal = false" class="flex-1 py-2.5 border border-gray-200 text-xs font-bold text-gray-600 rounded-xl hover:bg-gray-50 transition-all cursor-pointer disabled:opacity-50">
                    Cancel
                </button>
                <button type="submit" :disabled="isApproving" class="flex-1 py-2.5 bg-emerald-600 hover:bg-emerald-700 disabled:opacity-75 text-white text-xs font-bold uppercase tracking-wider rounded-xl transition-all shadow-sm cursor-pointer flex items-center justify-center gap-2">
                    <svg x-show="isApproving" class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                    <span x-text="isApproving ? 'Publishing...' : 'Confirm & Publish'"></span>
                </button>
            </form>
        </div>
    </div>

    {{-- ─── Reject Product Modal ─── --}}
    <div x-show="rejectModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" x-cloak>
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="rejectModal = false"></div>
        <div class="relative bg-white rounded-3xl shadow-2xl w-full max-w-lg p-6 sm:p-7 space-y-5 z-10 border border-gray-100">
            <div class="flex items-start gap-3.5">
                <div class="w-11 h-11 rounded-2xl bg-amber-100 text-amber-700 flex items-center justify-center shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                <div class="min-w-0">
                    <h3 class="text-base sm:text-lg font-bold text-gray-900 leading-tight">Reject Product Submission</h3>
                    <p class="text-xs text-gray-500 mt-1">Provide feedback for <strong x-text="rejectProductName" class="text-black"></strong> so the artisan can revise it.</p>
                </div>
            </div>

            <form :action="'/admin/products/' + rejectProductId + '/reject'" method="POST" class="space-y-4">
                @csrf
                @method('PATCH')
                
                <div>
                    <label class="text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-1.5 block">Quick Reason Presets</label>
                    <div class="flex flex-wrap gap-1.5 mb-2.5">
                        <button type="button" @click="rejectReason = 'Low resolution or blurry product imagery'"
                            class="px-2.5 py-1 rounded-lg text-[10px] font-semibold bg-gray-100 hover:bg-gray-200 text-gray-700 transition-colors">
                            Low Quality Photos
                        </button>
                        <button type="button" @click="rejectReason = 'Missing key fabric specifications, dimensions, or size measurements'"
                            class="px-2.5 py-1 rounded-lg text-[10px] font-semibold bg-gray-100 hover:bg-gray-200 text-gray-700 transition-colors">
                            Incomplete Details
                        </button>
                        <button type="button" @click="rejectReason = 'Listing pricing or discount details appear inaccurate'"
                            class="px-2.5 py-1 rounded-lg text-[10px] font-semibold bg-gray-100 hover:bg-gray-200 text-gray-700 transition-colors">
                            Pricing Issue
                        </button>
                        <button type="button" @click="rejectReason = 'Item does not match LumBarong authentic Filipino heritage and barong categories'"
                            class="px-2.5 py-1 rounded-lg text-[10px] font-semibold bg-gray-100 hover:bg-gray-200 text-gray-700 transition-colors">
                            Category Mismatch
                        </button>
                    </div>

                    <label class="text-[10px] font-bold text-gray-600 uppercase tracking-widest mb-1 block">Detailed Reason for Rejection *</label>
                    <textarea name="reason" x-model="rejectReason" required rows="3" placeholder="Explain the corrections required for the seller to resubmit..." class="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl text-xs outline-none focus:border-black font-medium"></textarea>
                </div>

                <div class="flex gap-3 pt-2">
                    <button type="button" @click="rejectModal = false" class="flex-1 py-2.5 border border-gray-200 text-xs font-bold text-gray-600 rounded-xl hover:bg-gray-50 transition-all cursor-pointer">
                        Cancel
                    </button>
                    <button type="submit" :disabled="!rejectReason.trim()" class="flex-1 py-2.5 bg-rose-600 hover:bg-rose-700 disabled:opacity-40 text-white text-xs font-bold uppercase tracking-wider rounded-xl transition-all shadow-sm cursor-pointer">
                        Confirm Rejection
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ─── Delete Product Confirmation Modal ─── --}}
    <div x-show="deleteModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" x-cloak>
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="deleteModal = false"></div>
        <div class="relative bg-white rounded-3xl shadow-2xl w-full max-w-lg p-6 sm:p-7 space-y-5 z-10 border border-gray-100">
            <div class="flex items-start gap-3.5">
                <div class="w-11 h-11 rounded-2xl bg-red-100 text-red-600 flex items-center justify-center shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                </div>
                <div class="min-w-0">
                    <h3 class="text-base sm:text-lg font-bold text-gray-900 leading-tight">Permanently Delete Product</h3>
                    <p class="text-xs text-gray-500 mt-1">Purge <strong x-text="deleteProductName" class="text-black"></strong> from the platform registry.</p>
                </div>
            </div>

            <div class="p-3 bg-red-50/80 border border-red-200 rounded-2xl text-[11px] text-red-800 leading-relaxed font-medium">
                ⚠️ <strong>Critical Warning:</strong> This action cannot be undone. The product and all associated customer cart entries will be permanently removed.
            </div>

            <form :action="'/admin/products/' + deleteProductId" method="POST" class="space-y-4">
                @csrf
                @method('DELETE')
                
                <div>
                    <label class="text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-1.5 block">Quick Reason Presets</label>
                    <div class="flex flex-wrap gap-1.5 mb-2.5">
                        <button type="button" @click="deleteReason = 'Counterfeit, copyrighted, or non-authentic barong product'"
                            class="px-2.5 py-1 rounded-lg text-[10px] font-semibold bg-gray-100 hover:bg-gray-200 text-gray-700 transition-colors">
                            Counterfeit / Policy
                        </button>
                        <button type="button" @click="deleteReason = 'Artisan requested permanent listing removal'"
                            class="px-2.5 py-1 rounded-lg text-[10px] font-semibold bg-gray-100 hover:bg-gray-200 text-gray-700 transition-colors">
                            Artisan Request
                        </button>
                        <button type="button" @click="deleteReason = 'Inappropriate, offensive, or explicit imagery'"
                            class="px-2.5 py-1 rounded-lg text-[10px] font-semibold bg-gray-100 hover:bg-gray-200 text-gray-700 transition-colors">
                            Inappropriate Media
                        </button>
                        <button type="button" @click="deleteReason = 'Duplicate or abandoned product submission'"
                            class="px-2.5 py-1 rounded-lg text-[10px] font-semibold bg-gray-100 hover:bg-gray-200 text-gray-700 transition-colors">
                            Duplicate / Spam
                        </button>
                    </div>

                    <label class="text-[10px] font-bold text-gray-600 uppercase tracking-widest mb-1 block">Reason for Permanent Deletion *</label>
                    <textarea name="reason" x-model="deleteReason" required rows="2.5" placeholder="Specify reason for deletion for platform audit logs and seller email..." class="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl text-xs outline-none focus:border-red-500 font-medium"></textarea>
                </div>

                <label class="flex items-start gap-2.5 cursor-pointer select-none pt-1">
                    <input type="checkbox" x-model="deleteConfirmChecked" class="mt-0.5 rounded border-gray-300 text-red-600 focus:ring-red-500 w-4 h-4 cursor-pointer">
                    <span class="text-[11px] text-gray-600 font-medium leading-snug">
                        I confirm that I want to permanently delete this product and notify the artisan workshop.
                    </span>
                </label>

                <div class="flex gap-3 pt-2">
                    <button type="button" @click="deleteModal = false" class="flex-1 py-2.5 border border-gray-200 text-xs font-bold text-gray-600 rounded-xl hover:bg-gray-50 transition-all cursor-pointer">
                        Cancel
                    </button>
                    <button type="submit" :disabled="!deleteConfirmChecked || !deleteReason.trim()" class="flex-1 py-2.5 bg-red-600 hover:bg-red-700 disabled:opacity-40 disabled:cursor-not-allowed text-white text-xs font-bold uppercase tracking-wider rounded-xl transition-all shadow-sm cursor-pointer">
                        Confirm &amp; Delete
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection