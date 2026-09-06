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
            return sz.map(item => typeof item === 'object' ? (item.size || item.name || '') : item).filter(Boolean);
        }
        return [];
    },
    formatPrice(price) {
        return parseFloat(price || 0).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
    },

    // Approval State
    approveModal: false,
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

    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="text-[10px] font-bold text-[#C0422A] uppercase tracking-widest mb-1">Catalog Oversight</div>
            <h1 class="font-serif text-3xl font-bold text-[#3D2B1F]">Product <span class="text-[#C0422A] italic">Moderation</span></h1>
            <p class="text-xs text-gray-500 mt-1">Review artisan product submissions, manage approvals, monitor catalog pricing, and maintain marketplace quality.</p>
        </div>
    </div>

    <!-- Filter Tabs & Status Pills -->
    <div class="flex items-center gap-2 overflow-x-auto no-scrollbar pb-1">
        @php
            $tabs = [
                'pending'  => ['label' => 'Pending Review', 'count' => $counts['pending'] ?? 0],
                'approved' => ['label' => 'Approved / Live', 'count' => $counts['approved'] ?? 0],
                'rejected' => ['label' => 'Rejected',         'count' => $counts['rejected'] ?? 0],
                'all'      => ['label' => 'All Products',     'count' => $counts['all'] ?? 0],
            ];
        @endphp

        @foreach($tabs as $tabKey => $tab)
            <a href="{{ route('superadmin.products', ['status' => $tabKey, 'search' => $search]) }}"
               class="px-4 py-2 rounded-xl text-xs font-bold transition-all shrink-0 flex items-center gap-2 {{ ($status ?? 'pending') === $tabKey ? 'bg-[#3D2B1F] text-white shadow-sm' : 'bg-white border border-[#E5DDD5] text-gray-600 hover:border-gray-400' }}">
                <span>{{ $tab['label'] }}</span>
                <span class="px-1.5 py-0.5 rounded-full text-[10px] {{ ($status ?? 'pending') === $tabKey ? 'bg-white/20 text-white' : 'bg-gray-100 text-gray-600' }}">{{ $tab['count'] }}</span>
            </a>
        @endforeach
    </div>

    <!-- Search Form -->
    <div class="bg-white border border-[#E5DDD5] rounded-2xl p-4 shadow-xs">
        <form action="{{ route('superadmin.products') }}" method="GET" class="flex flex-col sm:flex-row items-center gap-3">
            <input type="hidden" name="status" value="{{ $status ?? 'pending' }}">
            <div class="relative flex-1 w-full">
                <svg class="w-4 h-4 text-gray-400 absolute left-3.5 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input type="text" name="search" value="{{ $search ?? '' }}" placeholder="Search products, descriptions, artisan shops..." 
                       class="w-full pl-10 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-xs font-medium focus:bg-white focus:outline-none focus:border-[#C0422A] transition-all">
            </div>
            <div class="flex items-center gap-2 w-full sm:w-auto">
                <button type="submit" class="flex-1 sm:flex-none px-5 py-2.5 bg-[#C0422A] hover:bg-[#a53808] text-white text-xs font-bold rounded-xl transition-all shadow-xs cursor-pointer">
                    Search
                </button>
                @if(!empty($search))
                    <a href="{{ route('superadmin.products', ['status' => $status ?? 'pending']) }}" class="px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-600 text-xs font-bold rounded-xl transition-all cursor-pointer">
                        Clear
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Products Table -->
    @if($products->isEmpty())
        <div class="bg-white rounded-3xl border border-[#E5DDD5] p-12 text-center shadow-xs">
            <div class="w-16 h-16 bg-gray-50 rounded-2xl flex items-center justify-center mx-auto mb-4 border border-gray-100">
                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
            </div>
            <h3 class="text-sm font-bold text-gray-900">No Products Found</h3>
            <p class="text-xs text-gray-400 mt-1">There are no products matching your selected filter or search term.</p>
        </div>
    @else
        <div class="bg-white rounded-3xl border border-[#E5DDD5] shadow-xs overflow-hidden">
            <div class="overflow-x-auto no-scrollbar">
                <table class="w-full text-left border-collapse min-w-175">
                    <thead>
                        <tr class="bg-gray-50/70 border-b border-[#E5DDD5]">
                            <th class="px-6 py-4 text-[10px] font-black text-gray-600 uppercase tracking-widest">Product</th>
                            <th class="px-6 py-4 text-[10px] font-black text-gray-600 uppercase tracking-widest">Artisan / Seller</th>
                            <th class="px-6 py-4 text-[10px] font-black text-gray-600 uppercase tracking-widest">Category</th>
                            <th class="px-6 py-4 text-[10px] font-black text-gray-600 uppercase tracking-widest">Price &amp; Stock</th>
                            <th class="px-6 py-4 text-[10px] font-black text-gray-600 uppercase tracking-widest">Status</th>
                            <th class="px-6 py-4 text-[10px] font-black text-gray-600 uppercase tracking-widest text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($products as $product)
                            <tr class="hover:bg-amber-50/20 transition-colors">
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
                                    <div class="text-xs font-bold text-gray-900">{{ $product->seller->shopName ?? $product->seller->name ?? 'Artisan' }}</div>
                                    <div class="text-[10px] text-gray-400">{{ $product->seller->email ?? 'No email' }}</div>
                                </td>

                                <!-- Category -->
                                <td class="px-6 py-4">
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-gray-100 text-gray-700">
                                        {{ $product->category->name ?? 'Uncategorized' }}
                                    </span>
                                </td>

                                <!-- Price & Stock -->
                                <td class="px-6 py-4">
                                    <div class="text-xs font-extrabold text-gray-900">₱{{ number_format((float)$product->price, 2) }}</div>
                                    <div class="text-[10px] text-gray-500 font-medium">Stock: {{ $product->stock }} pcs</div>
                                </td>

                                <!-- Status Badge -->
                                <td class="px-6 py-4">
                                    @php
                                        $statusBadges = [
                                            'approved' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                            'pending'  => 'bg-amber-50 text-amber-700 border-amber-200',
                                            'rejected' => 'bg-red-50 text-red-700 border-red-200',
                                        ];
                                    @endphp
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider border {{ $statusBadges[$product->status] ?? 'bg-gray-50 text-gray-700 border-gray-200' }}">
                                        {{ $product->status }}
                                    </span>
                                </td>

                                <!-- Actions -->
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        @if($product->status === 'pending')
                                            <!-- Quick Approve Button -->
                                            <button type="button" 
                                                    @click="openApprove(@js($product))"
                                                    class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl transition-all flex items-center gap-1 cursor-pointer shadow-xs">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                                <span>Approve</span>
                                            </button>

                                            <!-- Quick Reject Button -->
                                            <button type="button" 
                                                    @click="openReject(@js($product))"
                                                    class="px-3 py-1.5 bg-red-50 hover:bg-red-100 text-red-600 text-xs font-bold rounded-xl transition-all flex items-center gap-1 cursor-pointer border border-red-200 shadow-none">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                                <span>Reject</span>
                                            </button>
                                        @endif

                                        <!-- Inspect Modal Trigger -->
                                        <button type="button" 
                                                @click="openInspect(@js($product))"
                                                class="p-2 text-gray-400 hover:text-stone-900 hover:bg-gray-100 rounded-xl transition-all cursor-pointer"
                                                title="Inspect Product Full Details & Sizing">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                        </button>

                                        <!-- Delete / Archive Product Button -->
                                        <button type="button" 
                                                @click="openDelete(@js($product))"
                                                class="p-2 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-xl transition-all cursor-pointer"
                                                title="Delete & Archive Product">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
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
                <div class="p-6 bg-gray-50/50 border-t border-[#E5DDD5]">
                    {{ $products->appends(['status' => $status, 'search' => $search])->links() }}
                </div>
            @endif
        </div>
    @endif

    {{-- ==================== INSPECT PRODUCT MODAL ==================== --}}
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
                    <span class="text-xs font-bold uppercase tracking-wider text-stone-900">
                        Product Oversight &amp; Inspection
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
                                        class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-xs font-bold uppercase tracking-wider rounded-xl transition-all shadow-sm flex items-center gap-1.5 cursor-pointer">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                                    <span x-text="(inspectProduct?.status || '').toLowerCase() === 'approved' ? 'Revoke Approval' : 'Reject Product'"></span>
                                </button>
                            </template>

                            {{-- Delete / Archive Action --}}
                            <button type="button" 
                                    @click="openDelete(inspectProduct); closeInspect();"
                                    class="px-3 py-2 bg-white border border-gray-200 hover:bg-red-50 text-gray-400 hover:text-red-600 rounded-xl transition-all cursor-pointer flex items-center gap-1"
                                    title="Delete & Archive Product">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                <span class="text-xs font-semibold">Archive</span>
                            </button>
                        </div>
                    </div>
                </template>
            </div>

        </div>
    </div>

    {{-- ==================== APPROVE MODAL ==================== --}}
    <div x-show="approveModal" 
         x-cloak 
         style="display: none;"
         class="fixed inset-0 z-50 flex items-center justify-center p-4"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95">
        <div @click="approveModal = false" class="absolute inset-0 bg-black/60 backdrop-blur-sm"></div>
        <div class="bg-white w-full max-w-md rounded-3xl shadow-2xl relative z-10 overflow-hidden border border-gray-100 p-6 sm:p-8 text-center flex flex-col items-center">
            <div class="w-16 h-16 rounded-2xl bg-emerald-50 border border-emerald-100 flex items-center justify-center text-emerald-600 mb-5">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            </div>
            <h3 class="text-xl font-bold text-gray-900 mb-1">Approve Product</h3>
            <p class="text-xs text-gray-500 mb-6">Are you sure you want to approve <span class="font-bold text-gray-900" x-text="approveProductName"></span>? It will become visible in the live customer shop immediately.</p>
            
            <form :action="'/superadmin/products/' + approveProductId + '/approve'" method="POST" class="w-full flex items-center gap-3">
                @csrf
                <button type="button" @click="approveModal = false" class="flex-1 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-bold rounded-xl transition-all cursor-pointer">Cancel</button>
                <button type="submit" class="flex-1 py-3 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl transition-all cursor-pointer shadow-sm">Approve &amp; Publish</button>
            </form>
        </div>
    </div>

    {{-- ==================== REJECT MODAL ==================== --}}
    <div x-show="rejectModal" 
         x-cloak 
         style="display: none;"
         class="fixed inset-0 z-50 flex items-center justify-center p-4"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95">
        <div @click="rejectModal = false" class="absolute inset-0 bg-black/60 backdrop-blur-sm"></div>
        <div class="bg-white w-full max-w-md rounded-3xl shadow-2xl relative z-10 overflow-hidden border border-gray-100 p-6 sm:p-8">
            <div class="w-14 h-14 rounded-2xl bg-red-50 border border-red-100 flex items-center justify-center text-red-600 mb-4 mx-auto">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </div>
            <h3 class="text-lg font-bold text-gray-900 text-center mb-1">Reject Product Submission</h3>
            <p class="text-xs text-gray-500 text-center mb-4">Provide a clear rejection reason. The seller will be notified via email and in-app message.</p>
            
            <form :action="'/superadmin/products/' + rejectProductId + '/reject'" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-1.5">Rejection Reason <span class="text-red-500">*</span></label>
                    <textarea name="reason" rows="3" required placeholder="e.g. Unclear product embroidery photo, incomplete size specifications..."
                              class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-xs focus:bg-white focus:outline-none focus:border-black"></textarea>
                </div>
                <div class="flex items-center gap-3 pt-2">
                    <button type="button" @click="rejectModal = false" class="flex-1 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-bold rounded-xl transition-all cursor-pointer">Cancel</button>
                    <button type="submit" class="flex-1 py-3 bg-red-600 hover:bg-red-700 text-white text-xs font-bold rounded-xl transition-all cursor-pointer shadow-sm">Reject Product</button>
                </div>
            </form>
        </div>
    </div>

    {{-- ==================== DELETE MODAL ==================== --}}
    <div x-show="deleteModal" 
         x-cloak 
         style="display: none;"
         class="fixed inset-0 z-50 flex items-center justify-center p-4"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95">
        <div @click="deleteModal = false" class="absolute inset-0 bg-black/60 backdrop-blur-sm"></div>
        <div class="bg-white w-full max-w-md rounded-3xl shadow-2xl relative z-10 overflow-hidden border border-gray-100 p-6 sm:p-8">
            <div class="w-14 h-14 rounded-2xl bg-red-50 border border-red-100 flex items-center justify-center text-red-600 mb-4 mx-auto">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
            </div>
            <h3 class="text-lg font-bold text-gray-900 text-center mb-1">Delete &amp; Archive Product</h3>
            <p class="text-xs text-gray-500 text-center mb-4">This product will be archived in the Archive Vault and removed from the catalog.</p>
            
            <form :action="'/superadmin/products/' + deleteProductId" method="POST" class="space-y-4">
                @csrf
                @method('DELETE')
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-1.5">Reason for Deletion <span class="text-red-500">*</span></label>
                    <input type="text" name="reason" required placeholder="e.g. Counterfeit design, seller request..."
                           class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-xs focus:bg-white focus:outline-none focus:border-black">
                </div>
                <div class="flex items-center gap-3 pt-2">
                    <button type="button" @click="deleteModal = false" class="flex-1 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-bold rounded-xl transition-all cursor-pointer">Cancel</button>
                    <button type="submit" class="flex-1 py-3 bg-red-600 hover:bg-red-700 text-white text-xs font-bold rounded-xl transition-all cursor-pointer shadow-sm">Delete &amp; Archive</button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
