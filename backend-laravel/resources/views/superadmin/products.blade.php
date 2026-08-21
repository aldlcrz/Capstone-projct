@extends('layouts.superadmin')

@section('content')
<div class="space-y-6" x-data="{
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
                                        <div class="w-12 h-14 rounded-xl overflow-hidden bg-gray-100 border border-gray-200 shrink-0">
                                            <img src="{{ $imgUrl }}" onerror="this.src='/uploads/products/default.jpg'" class="w-full h-full object-cover">
                                        </div>
                                        <div class="min-w-0 max-w-xs">
                                            <div class="text-xs font-bold text-gray-900 truncate">{{ $product->name }}</div>
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
                                                    @click="openApprove({{ json_encode($product) }})"
                                                    class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl transition-all flex items-center gap-1 cursor-pointer shadow-xs">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                                <span>Approve</span>
                                            </button>

                                            <!-- Quick Reject Button -->
                                            <button type="button" 
                                                    @click="openReject({{ json_encode($product) }})"
                                                    class="px-3 py-1.5 bg-red-50 hover:bg-red-100 text-red-600 text-xs font-bold rounded-xl transition-all flex items-center gap-1 cursor-pointer border border-red-200 shadow-none">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                                <span>Reject</span>
                                            </button>
                                        @endif

                                        <!-- Delete / Archive Product Button -->
                                        <button type="button" 
                                                @click="openDelete({{ json_encode($product) }})"
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
