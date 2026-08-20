@extends('layouts.admin')

@section('content')
<div class="space-y-8" x-data="{
    suspendModal: false,
    suspendSellerId: null,
    suspendSellerName: '',
    suspendReason: '',
    deleteModal: false,
    deleteSellerId: null,
    deleteSellerName: '',
    reviewModal: false,
    previewModal: false,
    previewUrl: '',
    previewTitle: '',
    selectedSeller: {
        id: '',
        name: '',
        email: '',
        mobileNumber: '',
        gcashNumber: '',
        shopName: '',
        shopAddress: '',
        residencyCertificate: null,
        businessPermit: null,
        birDocument: null,
        createdAt: '',
        isVerified: false,
        status: '',
        products_count: 0,
        orders_count: 0
    },
    shopPreviewModal: false,
    shopLoading: false,
    shopSeller: null,
    shopProducts: [],
    shopActiveTab: 'all',
    shopSearchQuery: '',
    selectedProductPreview: null,
    productPreviewModal: false,
    productActiveImage: 0,
    openReview(seller) {
        this.selectedSeller = seller;
        this.reviewModal = true;
    },
    openPreview(url, title) {
        this.previewUrl = url;
        this.previewTitle = title;
        this.previewModal = true;
    },
    openSuspend(id, name) {
        this.suspendSellerId = id;
        this.suspendSellerName = name;
        this.suspendReason = 'Violation of platform seller policies';
        this.suspendModal = true;
    },
    deleteModal: false,
    deleteSellerId: null,
    deleteSellerName: '',
    deleteReason: '',
    deleteConfirmChecked: false,
    openDelete(id, name) {
        this.deleteSellerId = id;
        this.deleteSellerName = name;
        this.deleteReason = '';
        this.deleteConfirmChecked = false;
        this.deleteModal = true;
    },
    async openShopPreview(sellerId, fallbackShopName) {
        this.shopPreviewModal = true;
        this.shopLoading = true;
        this.shopSeller = {
            id: sellerId,
            shopName: fallbackShopName || 'Artisan Workshop',
            name: '',
            location: 'Lumban, Laguna',
            isVerified: false,
            isPremium: false,
            rating: '0.0',
            productCount: 0,
            joined: '—',
            cancellation_policy: '',
            refund_policy: ''
        };
        this.shopProducts = [];
        this.shopActiveTab = 'all';
        this.shopSearchQuery = '';
        this.selectedProductPreview = null;
        
        try {
            const ts = Date.now();
            const [sRes, pRes] = await Promise.all([
                fetch(`/api/v1/user/seller/${sellerId}?t=${ts}`, { cache: 'no-store' }),
                fetch(`/api/v1/products?seller=${sellerId}&t=${ts}`, { cache: 'no-store' })
            ]);
            if (sRes.ok) {
                this.shopSeller = await sRes.json();
            }
            if (pRes.ok) {
                this.shopProducts = await pRes.json();
            }
        } catch (e) {
            console.error('Error loading shop preview:', e);
        } finally {
            this.shopLoading = false;
        }
    },
    get displayedShopProducts() {
        let p = [...this.shopProducts];
        if (this.shopActiveTab === 'rated') {
            p.sort((a, b) => (Number(b.rating) || 0) - (Number(a.rating) || 0));
        } else if (this.shopActiveTab === 'sale') {
            p = p.filter(item => item.is_on_sale);
        }
        if (this.shopSearchQuery && this.shopSearchQuery.trim()) {
            const q = this.shopSearchQuery.toLowerCase();
            p = p.filter(item => (item.name || '').toLowerCase().includes(q) || (item.description || '').toLowerCase().includes(q));
        }
        return p;
    },
    openProductPreview(product) {
        this.selectedProductPreview = product;
        this.productActiveImage = 0;
        this.productPreviewModal = true;
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
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="text-[10px] font-bold text-[#C0422A] uppercase tracking-[0.2em] mb-1">Artisan Registry</div>
            <h1 class="font-serif text-3xl font-bold text-black">Seller <span class="text-[#C0420A] font-light italic">Management</span></h1>
        </div>
    </div>

    @php
        $currentFilter = request('filter');
    @endphp

    {{-- Filter Pills & Search Bar --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 pt-1">
        {{-- Pill Filters --}}
        <div class="flex items-center gap-2 overflow-x-auto no-scrollbar pb-1">
            {{-- ALL --}}
            <a href="{{ request()->fullUrlWithQuery(['filter' => 'all', 'page' => 1]) }}"
               class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-[10px] sm:text-[11px] font-black uppercase tracking-wider transition-all {{ $currentFilter === 'all' ? 'bg-black text-white shadow-sm' : 'bg-white text-gray-700 border border-gray-200 hover:bg-gray-50' }}">
                <span>ALL</span>
                <span class="px-2 py-0.5 rounded-full text-[9px] font-black {{ $currentFilter === 'all' ? 'bg-white/20 text-white' : 'bg-gray-100 text-gray-600' }}">{{ $counts['all'] }}</span>
            </a>

            {{-- APPROVED / VERIFIED --}}
            <a href="{{ request()->fullUrlWithQuery(['filter' => null, 'page' => 1]) }}"
               class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-[10px] sm:text-[11px] font-black uppercase tracking-wider transition-all {{ (empty($currentFilter) || $currentFilter === 'verified') ? 'bg-black text-white shadow-sm' : 'bg-white text-gray-700 border border-gray-200 hover:bg-gray-50' }}">
                <span>APPROVED</span>
                <span class="px-2 py-0.5 rounded-full text-[9px] font-black {{ (empty($currentFilter) || $currentFilter === 'verified') ? 'bg-white/20 text-white' : 'bg-gray-100 text-gray-600' }}">{{ $counts['verified'] }}</span>
            </a>

            {{-- PENDING --}}
            <a href="{{ request()->fullUrlWithQuery(['filter' => 'pending', 'page' => 1]) }}"
               class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-[10px] sm:text-[11px] font-black uppercase tracking-wider transition-all {{ $currentFilter === 'pending' ? 'bg-black text-white shadow-sm' : 'bg-white text-gray-700 border border-gray-200 hover:bg-gray-50' }}">
                <span>PENDING</span>
                <span class="px-2 py-0.5 rounded-full text-[9px] font-black {{ $currentFilter === 'pending' ? 'bg-white/20 text-white' : 'bg-gray-100 text-gray-600' }}">{{ $counts['pending'] }}</span>
            </a>

            {{-- SUSPENDED --}}
            <a href="{{ request()->fullUrlWithQuery(['filter' => 'suspended', 'page' => 1]) }}"
               class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-[10px] sm:text-[11px] font-black uppercase tracking-wider transition-all {{ $currentFilter === 'suspended' ? 'bg-black text-white shadow-sm' : 'bg-white text-gray-700 border border-gray-200 hover:bg-gray-50' }}">
                <span>SUSPENDED</span>
                <span class="px-2 py-0.5 rounded-full text-[9px] font-black {{ $currentFilter === 'suspended' ? 'bg-white/20 text-white' : 'bg-gray-100 text-gray-600' }}">{{ $counts['suspended'] }}</span>
            </a>
        </div>

        {{-- Search Input --}}
        <form method="GET" class="flex items-center gap-2">
            @if(request('filter'))
                <input type="hidden" name="filter" value="{{ request('filter') }}">
            @endif
            <div class="relative w-full sm:w-64">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search sellers, shops..." 
                       class="w-full pl-9 pr-4 py-2 bg-white border border-gray-200 rounded-full text-xs text-gray-800 placeholder:text-gray-400 focus:outline-none focus:border-[#C0422A]">
                <svg class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </div>
            @if(request('search'))
                <a href="{{ request()->fullUrlWithQuery(['search' => null, 'page' => 1]) }}" class="px-3 py-2 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-full text-[10px] font-bold">Clear</a>
            @endif
        </form>
    </div>

    {{-- Pending Verification (Compact Pill Row Style) --}}
    @if($pendingSellers->count() > 0)
    <div class="bg-amber-50/70 border border-amber-200/80 rounded-2xl p-3.5 sm:p-4 space-y-2.5">
        <h3 class="text-[11px] font-black uppercase tracking-widest text-amber-900 flex items-center gap-2">
            <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
            Awaiting Verification ({{ $pendingSellers->count() }})
        </h3>
        <div class="space-y-2">
            @foreach($pendingSellers as $seller)
            @php
                $pData = [
                    'id' => $seller->id,
                    'name' => $seller->name,
                    'email' => $seller->email,
                    'mobileNumber' => $seller->mobileNumber ?? 'Not Provided',
                    'gcashNumber' => $seller->gcashNumber ?? 'Not Provided',
                    'shopName' => $seller->shopName ?? ($seller->name . "'s Workshop"),
                    'shopAddress' => $seller->shopAddress ?? 'Not Provided',
                    'residencyCertificate' => $seller->residencyCertificate ? asset($seller->residencyCertificate) : null,
                    'businessPermit' => $seller->businessPermit ? asset($seller->businessPermit) : null,
                    'birDocument' => $seller->birDocument ? asset($seller->birDocument) : null,
                    'createdAt' => $seller->createdAt ? $seller->createdAt->format('M d, Y h:i A') : '—',
                    'isVerified' => (bool)$seller->isVerified,
                    'status' => $seller->status,
                    'products_count' => $seller->products_count ?? 0,
                    'orders_count' => $seller->orders_count ?? 0,
                ];
            @endphp
            <div class="bg-white rounded-xl p-2.5 sm:p-3 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-2.5 shadow-xs hover:shadow-sm transition-all border border-amber-100">
                <div class="flex items-center gap-2.5 min-w-0">
                    <div class="w-8 h-8 rounded-lg bg-amber-100 text-amber-800 flex items-center justify-center font-black text-xs shrink-0">
                        {{ strtoupper(substr($seller->name, 0, 1)) }}
                    </div>
                    <div class="min-w-0">
                        <div class="text-xs font-bold text-black truncate">{{ $seller->name }}</div>
                        <div class="text-[10px] text-gray-500 font-medium truncate">{{ $seller->email }} • <button type="button" @click="openShopPreview('{{ $seller->id }}', '{{ addslashes($seller->shopName ?? 'Workshop') }}')" class="text-[#C0422A] font-semibold hover:underline cursor-pointer inline-flex items-center gap-0.5" title="Preview Artisan Shop">{{ $seller->shopName ?? 'Workshop' }} <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg></button></div>
                    </div>
                </div>
                <div class="flex items-center gap-1.5 shrink-0">
                    <button type="button" @click="openReview({{ json_encode($pData) }})" class="px-3 py-1.5 bg-green-600 text-white rounded-lg text-[9px] font-black uppercase tracking-widest hover:bg-green-700 transition-all cursor-pointer shadow-xs flex items-center gap-1">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        Approve &amp; Verify
                    </button>
                    <button type="button" @click="openSuspend('{{ $seller->id }}', '{{ addslashes($seller->name) }}')" class="px-2.5 py-1.5 bg-red-50 text-red-700 rounded-lg text-[9px] font-black uppercase tracking-widest hover:bg-red-500 hover:text-white transition-all cursor-pointer">
                        Reject / Suspend
                    </button>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Sellers Table --}}
    <div class="bg-white rounded-3xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-50 flex items-center justify-between">
            <h3 class="text-sm font-black uppercase tracking-widest text-black">
                @if($currentFilter === 'pending')
                    Pending Sellers (Awaiting Verification)
                @elseif($currentFilter === 'suspended')
                    Suspended Sellers
                @elseif($currentFilter === 'all')
                    All Sellers Registry
                @else
                    Approved Sellers
                @endif
            </h3>
        </div>
        <div class="overflow-x-auto no-scrollbar">
            <table class="w-full text-left min-w-160">
            <thead>
                <tr class="bg-gray-50/50 border-b border-gray-100">
                    <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-gray-700">Artisan / Seller</th>
                    <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-gray-700 hidden lg:table-cell">Shop Name</th>
                    <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-gray-700 text-center hidden md:table-cell">Products</th>
                    <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-gray-700 text-center hidden md:table-cell">Orders</th>
                    <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-gray-700 hidden sm:table-cell">Joined</th>
                    <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-gray-700 text-center">Status</th>
                    <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-gray-700 text-center">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($sellers as $seller)
                @php
                    $sData = [
                        'id' => $seller->id,
                        'name' => $seller->name,
                        'email' => $seller->email,
                        'mobileNumber' => $seller->mobileNumber ?? 'Not Provided',
                        'gcashNumber' => $seller->gcashNumber ?? 'Not Provided',
                        'shopName' => $seller->shopName ?? ($seller->name . "'s Workshop"),
                        'shopAddress' => $seller->shopAddress ?? 'Not Provided',
                        'residencyCertificate' => $seller->residencyCertificate ? asset($seller->residencyCertificate) : null,
                        'businessPermit' => $seller->businessPermit ? asset($seller->businessPermit) : null,
                        'birDocument' => $seller->birDocument ? asset($seller->birDocument) : null,
                        'createdAt' => $seller->createdAt ? $seller->createdAt->format('M d, Y h:i A') : '—',
                        'isVerified' => (bool)$seller->isVerified,
                        'status' => $seller->status,
                        'products_count' => $seller->products_count ?? 0,
                        'orders_count' => $seller->orders_count ?? 0,
                    ];
                @endphp
                <tr class="hover:bg-gray-50/50 transition-all">
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-xl bg-[#C0422A] text-white flex items-center justify-center font-black text-sm shrink-0">
                                {{ strtoupper(substr($seller->name, 0, 1)) }}
                            </div>
                            <div class="min-w-0">
                                <div class="text-sm font-bold text-black flex items-center gap-2 truncate">
                                    {{ $seller->name }}
                                    @if($seller->isVerified)
                                        <span class="text-green-500 text-[10px] font-black" title="Verified">✓</span>
                                    @endif
                                </div>
                                <div class="text-[10px] text-gray-500 font-medium truncate">{{ $seller->email }}</div>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-xs font-semibold text-gray-700 hidden lg:table-cell">
                        @if($seller->shopName)
                            <button type="button" @click="openShopPreview('{{ $seller->id }}', '{{ addslashes($seller->shopName) }}')" class="text-[#3D2B1F] hover:text-[#C0422A] hover:underline flex items-center gap-1.5 font-bold cursor-pointer text-left group" title="Preview Seller Shop">
                                <span class="group-hover:text-[#C0422A]">{{ $seller->shopName }}</span>
                                <svg class="w-3.5 h-3.5 text-gray-400 group-hover:text-[#C0422A] transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            </button>
                        @else
                            <button type="button" @click="openShopPreview('{{ $seller->id }}', '{{ addslashes($seller->name . '\'s Workshop') }}')" class="text-gray-400 hover:text-[#C0422A] hover:underline flex items-center gap-1 text-[11px] cursor-pointer" title="Preview Workshop">
                                <span>{{ $seller->name }}'s Workshop</span>
                                <svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            </button>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-sm font-bold text-black text-center hidden md:table-cell">{{ $seller->products_count ?? 0 }}</td>
                    <td class="px-6 py-4 text-sm font-bold text-black text-center hidden md:table-cell">{{ $seller->orders_count ?? 0 }}</td>
                    <td class="px-6 py-4 text-[11px] text-gray-500 font-medium hidden sm:table-cell">
                        {{ $seller->createdAt ? $seller->createdAt->format('M d, Y') : '—' }}
                    </td>
                    <td class="px-6 py-4 text-center">
                        @if($seller->status === 'blocked')
                            <span class="px-3 py-1 rounded-full text-[9px] font-black uppercase tracking-widest inline-block bg-red-50 text-red-700 border border-red-200">Suspended</span>
                        @elseif($seller->status === 'frozen')
                            <span class="px-3 py-1 rounded-full text-[9px] font-black uppercase tracking-widest inline-block bg-amber-50 text-amber-700 border border-amber-200">Frozen</span>
                        @elseif(!$seller->isVerified)
                            <span class="px-3 py-1 rounded-full text-[9px] font-black uppercase tracking-widest inline-block bg-blue-50 text-blue-700 border border-blue-200">Pending</span>
                        @else
                            <span class="px-3 py-1 rounded-full text-[9px] font-black uppercase tracking-widest inline-block bg-green-50 text-green-700 border border-green-200">Active</span>
                        @endif
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex items-center justify-center gap-2">
                            {{-- Verify button opens Document Inspection Modal --}}
                            @if(!$seller->isVerified && $seller->status !== 'blocked')
                                <button type="button" @click="openReview({{ json_encode($sData) }})" class="px-4 py-2 bg-green-50 text-green-700 hover:bg-green-500 hover:text-white rounded-lg text-[9px] font-black uppercase tracking-widest transition-all cursor-pointer shadow-xs">
                                    Verify
                                </button>
                            @else
                                <button type="button" @click="openReview({{ json_encode($sData) }})" class="px-3 py-2 bg-gray-50 text-gray-600 hover:bg-gray-200 rounded-lg text-[9px] font-black uppercase tracking-widest transition-all cursor-pointer" title="View Application & Documents">
                                    Docs
                                </button>
                            @endif

                            @if($seller->status !== 'blocked')
                                <button type="button" @click="openSuspend('{{ $seller->id }}', '{{ addslashes($seller->name) }}')" class="px-4 py-2 bg-red-50 text-red-700 rounded-lg text-[9px] font-black uppercase tracking-widest hover:bg-red-500 hover:text-white transition-all cursor-pointer">
                                    Suspend
                                </button>
                            @else
                                <form action="{{ route('admin.sellers.unsuspend', $seller->id) }}" method="POST">
                                    @csrf @method('PATCH')
                                    <button type="submit" class="px-4 py-2 bg-green-50 text-green-700 rounded-lg text-[9px] font-black uppercase tracking-widest hover:bg-green-500 hover:text-white transition-all cursor-pointer">
                                        Restore
                                    </button>
                                </form>
                            @endif
                            <button type="button" @click="openDelete('{{ $seller->id }}', '{{ addslashes($seller->name) }}')" class="px-2 py-2 text-gray-400 hover:text-red-500 transition-all cursor-pointer" title="Delete Seller">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </button>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="py-16 text-center text-sm text-gray-500 italic">No seller accounts found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
        </div>
        <div class="px-6 py-4 border-t border-gray-50">
            {{ $sellers->links() }}
        </div>
    </div>

    {{-- Review Application & Documents Modal (Shown upon clicking Verify / Approve & Verify) --}}
    <div x-show="reviewModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6" x-cloak>
        <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="reviewModal = false"></div>
        <div class="relative bg-white rounded-3xl shadow-2xl w-full max-w-4xl max-h-[92vh] overflow-y-auto no-scrollbar z-10 border border-gray-100 flex flex-col">
            
            {{-- Modal Header --}}
            <div class="px-6 sm:px-8 py-5 border-b border-gray-100 flex items-center justify-between sticky top-0 bg-white/95 backdrop-blur-md z-20">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-[#C0422A] text-white flex items-center justify-center font-black text-base shadow-sm">
                        <span x-text="selectedSeller.name ? selectedSeller.name.charAt(0).toUpperCase() : 'S'"></span>
                    </div>
                    <div>
                        <h3 class="text-base sm:text-lg font-black text-gray-900 leading-tight flex items-center gap-2">
                            <span x-text="selectedSeller.name"></span>
                            <span x-show="selectedSeller.isVerified" class="text-green-600 text-xs font-bold bg-green-50 px-2 py-0.5 rounded-md border border-green-200">Verified</span>
                            <span x-show="!selectedSeller.isVerified" class="text-blue-600 text-xs font-bold bg-blue-50 px-2 py-0.5 rounded-md border border-blue-200">Pending Review</span>
                        </h3>
                        <p class="text-xs text-gray-500">Inspect credentials &amp; requirements before verification</p>
                    </div>
                </div>
                <button type="button" @click="reviewModal = false" class="w-9 h-9 rounded-full bg-gray-100 hover:bg-gray-200 text-gray-500 hover:text-black flex items-center justify-center transition-all cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            {{-- Modal Content --}}
            <div class="p-6 sm:p-8 space-y-6 flex-1">
                
                {{-- Applicant Details Grid --}}
                <div class="bg-[#F9F6F2] rounded-2xl p-5 border border-[#E5DDD5] space-y-4">
                    <div class="text-[10px] font-black uppercase tracking-widest text-[#8C7B70]">Applicant Information</div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4 text-xs">
                        <div>
                            <span class="text-gray-400 block text-[10px] font-bold uppercase">Full Registry Name</span>
                            <span class="font-bold text-gray-900" x-text="selectedSeller.name"></span>
                        </div>
                        <div>
                            <span class="text-gray-400 block text-[10px] font-bold uppercase">Secure Email</span>
                            <span class="font-bold text-gray-900 truncate block" x-text="selectedSeller.email"></span>
                        </div>
                        <div>
                            <span class="text-gray-400 block text-[10px] font-bold uppercase">Mobile Number</span>
                            <span class="font-bold text-gray-900" x-text="selectedSeller.mobileNumber"></span>
                        </div>
                        <div>
                            <span class="text-gray-400 block text-[10px] font-bold uppercase">GCash Account</span>
                            <span class="font-bold text-gray-900" x-text="selectedSeller.gcashNumber"></span>
                        </div>
                        <div>
                            <span class="text-gray-400 block text-[10px] font-bold uppercase">Workshop / Shop Name</span>
                            <button type="button" @click="openShopPreview(selectedSeller.id, selectedSeller.shopName)" class="font-bold text-[#C0422A] hover:underline cursor-pointer inline-flex items-center gap-1 text-left" title="Preview Seller Shopfront">
                                <span x-text="selectedSeller.shopName"></span>
                                <svg class="w-3 h-3 text-[#C0422A]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            </button>
                        </div>
                        <div>
                            <span class="text-gray-400 block text-[10px] font-bold uppercase">Application Date</span>
                            <span class="font-bold text-gray-900" x-text="selectedSeller.createdAt"></span>
                        </div>
                        <div class="sm:col-span-2 md:col-span-3">
                            <span class="text-gray-400 block text-[10px] font-bold uppercase">Workshop Address</span>
                            <span class="font-semibold text-gray-800" x-text="selectedSeller.shopAddress"></span>
                        </div>
                    </div>
                </div>

                {{-- Requirements Documents Gallery --}}
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <div class="text-[10px] font-black uppercase tracking-widest text-gray-500">Submitted Verification Documents</div>
                        <span class="text-[10px] font-bold text-gray-400">Click any document to inspect full preview</span>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        
                        {{-- 1. Residency Certificate --}}
                        <div class="bg-white rounded-2xl border border-gray-200 p-4 flex flex-col justify-between space-y-3 shadow-sm hover:border-[#C0422A] transition-colors">
                            <div>
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-[10px] font-black uppercase tracking-wider text-gray-500">1. Residency Cert</span>
                                    <template x-if="selectedSeller.residencyCertificate">
                                        <span class="text-[9px] font-bold text-green-700 bg-green-50 px-2 py-0.5 rounded-full border border-green-200">Attached</span>
                                    </template>
                                    <template x-if="!selectedSeller.residencyCertificate">
                                        <span class="text-[9px] font-bold text-red-600 bg-red-50 px-2 py-0.5 rounded-full border border-red-200">Missing</span>
                                    </template>
                                </div>
                                <div class="h-36 rounded-xl bg-gray-50 border border-gray-100 flex items-center justify-center overflow-hidden relative group">
                                    <template x-if="selectedSeller.residencyCertificate">
                                        <img :src="selectedSeller.residencyCertificate" class="w-full h-full object-cover group-hover:scale-105 transition-transform cursor-pointer" @click="openPreview(selectedSeller.residencyCertificate, 'Residency Certificate')">
                                    </template>
                                    <template x-if="!selectedSeller.residencyCertificate">
                                        <div class="text-center p-3 text-gray-400">
                                            <svg class="w-8 h-8 mx-auto mb-1 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                            <span class="text-[10px] font-bold">No file uploaded</span>
                                        </div>
                                    </template>
                                </div>
                            </div>
                            <template x-if="selectedSeller.residencyCertificate">
                                <div class="flex gap-2">
                                    <button type="button" @click="openPreview(selectedSeller.residencyCertificate, 'Residency Certificate')" class="flex-1 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-800 rounded-lg text-[10px] font-bold transition-all cursor-pointer text-center">
                                        Preview
                                    </button>
                                    <a :href="selectedSeller.residencyCertificate" target="_blank" download class="px-3 py-1.5 bg-[#3D2B1F] text-white hover:bg-[#C0422A] rounded-lg text-[10px] font-bold transition-all text-center">
                                        Download
                                    </a>
                                </div>
                            </template>
                        </div>

                        {{-- 2. Business Permit --}}
                        <div class="bg-white rounded-2xl border border-gray-200 p-4 flex flex-col justify-between space-y-3 shadow-sm hover:border-[#C0422A] transition-colors">
                            <div>
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-[10px] font-black uppercase tracking-wider text-gray-500">2. Business Permit</span>
                                    <template x-if="selectedSeller.businessPermit">
                                        <span class="text-[9px] font-bold text-green-700 bg-green-50 px-2 py-0.5 rounded-full border border-green-200">Attached</span>
                                    </template>
                                    <template x-if="!selectedSeller.businessPermit">
                                        <span class="text-[9px] font-bold text-red-600 bg-red-50 px-2 py-0.5 rounded-full border border-red-200">Missing</span>
                                    </template>
                                </div>
                                <div class="h-36 rounded-xl bg-gray-50 border border-gray-100 flex items-center justify-center overflow-hidden relative group">
                                    <template x-if="selectedSeller.businessPermit">
                                        <img :src="selectedSeller.businessPermit" class="w-full h-full object-cover group-hover:scale-105 transition-transform cursor-pointer" @click="openPreview(selectedSeller.businessPermit, 'Business Permit')">
                                    </template>
                                    <template x-if="!selectedSeller.businessPermit">
                                        <div class="text-center p-3 text-gray-400">
                                            <svg class="w-8 h-8 mx-auto mb-1 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                            <span class="text-[10px] font-bold">No file uploaded</span>
                                        </div>
                                    </template>
                                </div>
                            </div>
                            <template x-if="selectedSeller.businessPermit">
                                <div class="flex gap-2">
                                    <button type="button" @click="openPreview(selectedSeller.businessPermit, 'Business Permit')" class="flex-1 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-800 rounded-lg text-[10px] font-bold transition-all cursor-pointer text-center">
                                        Preview
                                    </button>
                                    <a :href="selectedSeller.businessPermit" target="_blank" download class="px-3 py-1.5 bg-[#3D2B1F] text-white hover:bg-[#C0422A] rounded-lg text-[10px] font-bold transition-all text-center">
                                        Download
                                    </a>
                                </div>
                            </template>
                        </div>

                        {{-- 3. BIR Document --}}
                        <div class="bg-white rounded-2xl border border-gray-200 p-4 flex flex-col justify-between space-y-3 shadow-sm hover:border-[#C0422A] transition-colors">
                            <div>
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-[10px] font-black uppercase tracking-wider text-gray-500">3. BIR / Tax Reg</span>
                                    <template x-if="selectedSeller.birDocument">
                                        <span class="text-[9px] font-bold text-green-700 bg-green-50 px-2 py-0.5 rounded-full border border-green-200">Attached</span>
                                    </template>
                                    <template x-if="!selectedSeller.birDocument">
                                        <span class="text-[9px] font-bold text-red-600 bg-red-50 px-2 py-0.5 rounded-full border border-red-200">Missing</span>
                                    </template>
                                </div>
                                <div class="h-36 rounded-xl bg-gray-50 border border-gray-100 flex items-center justify-center overflow-hidden relative group">
                                    <template x-if="selectedSeller.birDocument">
                                        <img :src="selectedSeller.birDocument" class="w-full h-full object-cover group-hover:scale-105 transition-transform cursor-pointer" @click="openPreview(selectedSeller.birDocument, 'BIR Document')">
                                    </template>
                                    <template x-if="!selectedSeller.birDocument">
                                        <div class="text-center p-3 text-gray-400">
                                            <svg class="w-8 h-8 mx-auto mb-1 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/></svg>
                                            <span class="text-[10px] font-bold">No file uploaded</span>
                                        </div>
                                    </template>
                                </div>
                            </div>
                            <template x-if="selectedSeller.birDocument">
                                <div class="flex gap-2">
                                    <button type="button" @click="openPreview(selectedSeller.birDocument, 'BIR Document')" class="flex-1 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-800 rounded-lg text-[10px] font-bold transition-all cursor-pointer text-center">
                                        Preview
                                    </button>
                                    <a :href="selectedSeller.birDocument" target="_blank" download class="px-3 py-1.5 bg-[#3D2B1F] text-white hover:bg-[#C0422A] rounded-lg text-[10px] font-bold transition-all text-center">
                                        Download
                                    </a>
                                </div>
                            </template>
                        </div>

                    </div>
                </div>

            </div>

            {{-- Modal Action Bar --}}
            <div class="px-6 sm:px-8 py-4 bg-gray-50 border-t border-gray-100 flex flex-col sm:flex-row items-center justify-between gap-3 sticky bottom-0 z-20">
                <button type="button" @click="reviewModal = false" class="w-full sm:w-auto px-6 py-2.5 bg-white border border-gray-200 text-gray-600 rounded-xl text-xs font-bold hover:bg-gray-100 transition-all cursor-pointer">
                    Close
                </button>
                <div class="flex items-center gap-2 w-full sm:w-auto">
                    <template x-if="!selectedSeller.isVerified && selectedSeller.status !== 'blocked'">
                        <div class="flex items-center gap-2 w-full sm:w-auto">
                            <button type="button" @click="reviewModal = false; openSuspend(selectedSeller.id, selectedSeller.name)" class="flex-1 sm:flex-initial px-5 py-2.5 bg-red-50 text-red-700 hover:bg-red-500 hover:text-white rounded-xl text-xs font-black uppercase tracking-wider transition-all cursor-pointer">
                                Reject / Suspend
                            </button>
                            <form :action="'/admin/sellers/' + selectedSeller.id + '/verify'" method="POST" class="flex-1 sm:flex-initial">
                                @csrf @method('PATCH')
                                <button type="submit" class="w-full px-6 py-2.5 bg-green-600 hover:bg-green-700 text-white rounded-xl text-xs font-black uppercase tracking-wider transition-all cursor-pointer shadow-md flex items-center justify-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                    Approve &amp; Verify Seller
                                </button>
                            </form>
                        </div>
                    </template>
                    <template x-if="selectedSeller.isVerified && selectedSeller.status !== 'blocked'">
                        <div class="flex items-center gap-2 w-full sm:w-auto">
                            <form :action="'/admin/sellers/' + selectedSeller.id + '/unverify'" method="POST" class="flex-1 sm:flex-initial">
                                @csrf @method('PATCH')
                                <button type="submit" onclick="return confirm('Revoke verification for this artisan?')" class="w-full px-5 py-2.5 bg-amber-50 text-amber-800 hover:bg-amber-500 hover:text-white rounded-xl text-xs font-black uppercase tracking-wider transition-all cursor-pointer">
                                    Revoke Verification
                                </button>
                            </form>
                            <button type="button" @click="reviewModal = false; openSuspend(selectedSeller.id, selectedSeller.name)" class="flex-1 sm:flex-initial px-5 py-2.5 bg-red-50 text-red-700 hover:bg-red-500 hover:text-white rounded-xl text-xs font-black uppercase tracking-wider transition-all cursor-pointer">
                                Suspend Account
                            </button>
                        </div>
                    </template>
                </div>
            </div>

        </div>
    </div>

    {{-- Document Lightbox Preview Modal --}}
    <div x-show="previewModal" class="fixed inset-0 z-70 flex items-center justify-center p-4 sm:p-6" style="z-index: 70;" x-cloak>
        <div class="absolute inset-0 bg-black/85 backdrop-blur-md" @click="previewModal = false"></div>
        <div class="relative bg-white rounded-3xl shadow-2xl w-full max-w-4xl overflow-hidden z-10 border border-gray-200 flex flex-col max-h-[92vh]">
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-white">
                <div class="font-bold text-sm text-gray-900 truncate" x-text="previewTitle"></div>
                <div class="flex items-center gap-2">
                    <a :href="previewUrl" target="_blank" download class="px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-bold rounded-lg transition-all">
                        Download Original
                    </a>
                    <a :href="previewUrl" target="_blank" class="px-3 py-1.5 bg-[#3D2B1F] hover:bg-[#C0422A] text-white text-xs font-bold rounded-lg transition-all">
                        Open in New Tab ↗
                    </a>
                    <button type="button" @click="previewModal = false" class="w-8 h-8 rounded-full bg-gray-100 hover:bg-gray-200 text-gray-500 flex items-center justify-center cursor-pointer">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
            </div>
            <div class="p-4 bg-gray-900 flex items-center justify-center flex-1 overflow-auto min-h-[50vh]">
                <template x-if="previewUrl && previewUrl.toLowerCase().endsWith('.pdf')">
                    <iframe :src="previewUrl" class="w-full h-[75vh] rounded-lg bg-white"></iframe>
                </template>
                <template x-if="!previewUrl || !previewUrl.toLowerCase().endsWith('.pdf')">
                    <img :src="previewUrl" class="max-w-full max-h-[75vh] object-contain rounded-lg shadow-2xl">
                </template>
            </div>
        </div>
    </div>

    {{-- Suspend Confirmation Modal --}}
    <div x-show="suspendModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" x-cloak>
        <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="suspendModal = false"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md p-6 space-y-4 z-10">
            <h3 class="text-lg font-bold text-gray-900">Suspend / Reject Seller Account</h3>
            <p class="text-xs text-gray-500 leading-relaxed">
                Are you sure you want to suspend or reject seller <strong x-text="suspendSellerName" class="text-black"></strong>? Please enter an explanation or reason.
            </p>
            <form :action="'/admin/sellers/' + suspendSellerId + '/suspend'" method="POST" class="space-y-4">
                @csrf @method('PATCH')
                <div>
                    <label class="text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-1.5 block">Quick Presets</label>
                    <div class="flex flex-wrap gap-1.5 mb-3">
                        <button type="button" @click="suspendReason = 'Submitted application documents are incomplete or illegible'"
                            class="px-2.5 py-1 rounded-lg text-[10px] font-semibold bg-gray-100 hover:bg-gray-200 text-gray-700 transition-colors">
                            Incomplete Docs
                        </button>
                        <button type="button" @click="suspendReason = 'Invalid or expired Business Permit / DTI Registration'"
                            class="px-2.5 py-1 rounded-lg text-[10px] font-semibold bg-gray-100 hover:bg-gray-200 text-gray-700 transition-colors">
                            Invalid Permit
                        </button>
                        <button type="button" @click="suspendReason = 'Policy violation / counterfeit or prohibited listings'"
                            class="px-2.5 py-1 rounded-lg text-[10px] font-semibold bg-gray-100 hover:bg-gray-200 text-gray-700 transition-colors">
                            Policy Violation
                        </button>
                        <button type="button" @click="suspendReason = 'Administrative review in progress'"
                            class="px-2.5 py-1 rounded-lg text-[10px] font-semibold bg-gray-100 hover:bg-gray-200 text-gray-700 transition-colors">
                            Admin Review
                        </button>
                    </div>

                    <label class="text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-1 block">Explanation / Reason (Shown to seller upon login) *</label>
                    <textarea name="reason" x-model="suspendReason" required rows="3" placeholder="Provide the reason for account suspension/rejection..." class="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl text-xs outline-none focus:border-red-500"></textarea>
                </div>
                <div class="flex gap-3 pt-2">
                    <button type="button" @click="suspendModal = false" class="flex-1 py-2.5 border border-gray-200 text-xs font-semibold text-gray-500 rounded-xl hover:bg-gray-50 cursor-pointer">Cancel</button>
                    <button type="submit" class="flex-1 py-2.5 bg-red-600 text-white text-xs font-bold uppercase tracking-wider rounded-xl hover:bg-red-700 cursor-pointer shadow-sm">Confirm Action</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Delete Confirmation Modal --}}
    <div x-show="deleteModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" x-cloak>
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="deleteModal = false"></div>
        <div class="relative bg-white rounded-3xl shadow-2xl w-full max-w-lg p-6 sm:p-7 space-y-5 z-10 border border-gray-100">
            <div class="flex items-start gap-3.5">
                <div class="w-11 h-11 rounded-2xl bg-red-100 flex items-center justify-center shrink-0">
                    <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                </div>
                <div class="min-w-0">
                    <h3 class="text-base sm:text-lg font-bold text-gray-900 leading-tight">Delete Seller Account</h3>
                    <p class="text-xs text-gray-500 mt-1">
                        Permanently purge <strong x-text="deleteSellerName" class="text-black"></strong> and all associated artisan workshop records.
                    </p>
                </div>
            </div>

            <div class="p-3 bg-red-50/80 border border-red-200 rounded-2xl text-[11px] text-red-800 leading-relaxed font-medium">
                ⚠️ <strong>Critical Warning:</strong> This action is permanent and irreversible. Active sessions, seller listings, and profile records will be completely removed from the registry.
            </div>

            <form :action="'/admin/sellers/' + deleteSellerId" method="POST" class="space-y-4">
                @csrf @method('DELETE')
                
                <div>
                    <label class="text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-1.5 block">Quick Reason Presets</label>
                    <div class="flex flex-wrap gap-1.5 mb-2.5">
                        <button type="button" @click="deleteReason = 'Severe violation of platform terms and fraudulent activity'"
                            class="px-2.5 py-1 rounded-lg text-[10px] font-semibold bg-gray-100 hover:bg-gray-200 text-gray-700 transition-colors">
                            Policy Violation
                        </button>
                        <button type="button" @click="deleteReason = 'Requested by artisan / shop owner account deletion'"
                            class="px-2.5 py-1 rounded-lg text-[10px] font-semibold bg-gray-100 hover:bg-gray-200 text-gray-700 transition-colors">
                            Artisan Request
                        </button>
                        <button type="button" @click="deleteReason = 'Inactive or abandoned registration account cleanup'"
                            class="px-2.5 py-1 rounded-lg text-[10px] font-semibold bg-gray-100 hover:bg-gray-200 text-gray-700 transition-colors">
                            Abandoned Account
                        </button>
                        <button type="button" @click="deleteReason = 'Unresponsive or counterfeit application submissions'"
                            class="px-2.5 py-1 rounded-lg text-[10px] font-semibold bg-gray-100 hover:bg-gray-200 text-gray-700 transition-colors">
                            Counterfeit / Spam
                        </button>
                    </div>

                    <label class="text-[10px] font-bold text-gray-600 uppercase tracking-widest mb-1 block">Reason for Permanent Deletion *</label>
                    <textarea name="reason" x-model="deleteReason" required rows="2.5" placeholder="Specify reason for deletion for platform audit logs..." class="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl text-xs outline-none focus:border-red-500 font-medium"></textarea>
                </div>

                <label class="flex items-start gap-2.5 cursor-pointer select-none pt-1">
                    <input type="checkbox" x-model="deleteConfirmChecked" class="mt-0.5 rounded border-gray-300 text-red-600 focus:ring-red-500 w-4 h-4 cursor-pointer">
                    <span class="text-[11px] text-gray-600 font-medium leading-snug">
                        I confirm that I want to permanently delete this seller account and understand that this action cannot be undone.
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

    {{-- ─── Admin Seller Shopfront Preview Modal (In-Page Preview Only, No Ordering) ─── --}}
    <div x-show="shopPreviewModal" class="fixed inset-0 z-60 flex items-center justify-center p-2 sm:p-4 md:p-6" style="z-index: 60;" x-cloak>
        <div class="absolute inset-0 bg-black/75 backdrop-blur-sm" @click="shopPreviewModal = false"></div>
        <div class="relative bg-stone-50 rounded-3xl shadow-2xl w-full max-w-5xl max-h-[92vh] overflow-hidden z-10 border border-gray-200 flex flex-col">
            
            {{-- Admin Preview Bar --}}
            <div class="bg-[#2E2A24] text-amber-200 px-5 sm:px-8 py-2.5 flex items-center justify-between border-b border-amber-900/40 shrink-0">
                <div class="flex items-center gap-2 text-[11px] font-bold">
                    <span class="w-2 h-2 rounded-full bg-amber-400 animate-pulse"></span>
                    <span class="uppercase tracking-widest text-white">Admin Storefront Preview</span>
                    <span class="text-amber-400/80 font-normal hidden sm:inline">• Read-Only Mode (Ordering &amp; Checkout Disabled)</span>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" @click="shopPreviewModal = false" class="text-xs text-stone-400 hover:text-white flex items-center gap-1 font-bold transition-colors cursor-pointer">
                        <span>Close Preview</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
            </div>

            {{-- Modal Body (Scrollable) --}}
            <div class="flex-1 overflow-y-auto no-scrollbar p-4 sm:p-6 space-y-6">
                
                {{-- Shop Header Profile Card --}}
                <div class="bg-white rounded-2xl shadow-sm border border-stone-200 flex flex-col md:flex-row overflow-hidden">
                    
                    {{-- Left Side: Branding / Banner --}}
                    <div class="w-full md:w-96 p-5 sm:p-6 flex flex-col justify-between shrink-0 relative overflow-hidden"
                         :class="shopSeller?.isPremium ? 'border-r border-yellow-500/20' : 'bg-[#1A1A1A]'"
                         :style="shopSeller?.isPremium ? 'background: linear-gradient(to bottom, #2E2A24, #1A1A1A);' : ''">
                        <div class="absolute inset-0 opacity-[0.04] bg-white mix-blend-overlay"></div>
                        <div class="relative z-10 flex gap-4 items-center">
                            <div class="w-16 h-16 sm:w-18 sm:h-18 rounded-full border-2 border-white/20 bg-stone-100 overflow-hidden shrink-0 flex items-center justify-center font-serif text-2xl sm:text-3xl text-stone-400 shadow-md">
                                <template x-if="shopSeller && shopSeller.profilePhoto">
                                    <img :src="getProductImage(shopSeller.profilePhoto)" class="w-full h-full object-cover" onerror="this.src='/uploads/products/default.jpg'" />
                                </template>
                                <template x-if="!shopSeller || !shopSeller.profilePhoto">
                                    <span x-text="shopSeller?.shopName ? shopSeller.shopName.charAt(0).toUpperCase() : 'A'"></span>
                                </template>
                            </div>
                            <div class="text-left text-white min-w-0">
                                <h2 class="font-serif text-base sm:text-lg font-bold leading-tight flex items-center gap-1.5 tracking-wide flex-wrap">
                                    <span x-text="shopSeller?.shopName || 'Artisan Workshop'" class="truncate"></span>
                                    <template x-if="shopSeller?.isVerified">
                                        <span class="inline-flex items-center gap-0.5 text-[#A1D4B1] text-xs font-bold" title="Verified Store">
                                            <svg class="w-3.5 h-3.5 fill-current" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                                        </span>
                                    </template>
                                    <template x-if="shopSeller?.isPremium">
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-yellow-500/20 border border-yellow-500/30 text-yellow-400 text-[8px] font-black uppercase tracking-wider rounded-full">
                                            👑 Premium
                                        </span>
                                    </template>
                                </h2>
                                <div class="text-white/60 text-[11px] mt-1 flex items-center gap-1 font-medium tracking-wide">
                                    <svg class="w-3 h-3 opacity-80 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                    <span x-text="shopSeller?.location || 'Lumban, Laguna'" class="truncate"></span>
                                </div>
                                <div class="text-white/40 text-[10px] mt-0.5 truncate" x-text="'Owner: ' + (shopSeller?.name || 'Artisan')"></div>
                            </div>
                        </div>

                        <div class="relative z-10 flex gap-2 mt-4 pt-3 border-t border-white/10 w-full">
                            <button type="button" @click="shopActiveTab = 'policies'" class="flex-1 flex items-center justify-center gap-1.5 border border-white/30 px-3 py-1.5 text-[10px] font-bold uppercase tracking-widest text-white transition-all hover:bg-white/10 rounded-lg cursor-pointer">
                                <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                <span>Shop Policies</span>
                            </button>
                            <button type="button" @click="shopActiveTab = 'all'" class="flex-1 flex items-center justify-center gap-1.5 bg-[#C0422A] px-3 py-1.5 text-[10px] font-bold uppercase tracking-widest text-white transition-all hover:bg-[#a83720] rounded-lg cursor-pointer">
                                <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                                <span>View Pieces</span>
                            </button>
                        </div>
                    </div>

                    {{-- Right Side: Stats --}}
                    <div class="flex-1 p-5 sm:p-6 flex items-center bg-white">
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 w-full text-center sm:text-left">
                            <div class="p-3 bg-stone-50 rounded-xl border border-stone-100">
                                <span class="text-stone-400 font-bold text-[10px] uppercase tracking-wider block">Masterpieces</span>
                                <span class="text-[#C0420A] font-extrabold text-lg" x-text="shopProducts.length"></span>
                            </div>
                            <div class="p-3 bg-stone-50 rounded-xl border border-stone-100">
                                <span class="text-stone-400 font-bold text-[10px] uppercase tracking-wider block">Artisan Rating</span>
                                <span class="text-gray-900 font-extrabold text-lg flex items-center justify-center sm:justify-start gap-1">
                                    <svg class="w-4 h-4 text-amber-400 fill-current" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                    <span x-text="Number(shopSeller?.rating || 0).toFixed(1)"></span>
                                </span>
                            </div>
                            <div class="p-3 bg-stone-50 rounded-xl border border-stone-100">
                                <span class="text-stone-400 font-bold text-[10px] uppercase tracking-wider block">Response Rate</span>
                                <span class="text-green-700 font-extrabold text-lg" x-text="shopSeller?.responseRate || '100%'"></span>
                            </div>
                            <div class="p-3 bg-stone-50 rounded-xl border border-stone-100">
                                <span class="text-stone-400 font-bold text-[10px] uppercase tracking-wider block">Joined</span>
                                <span class="text-gray-800 font-bold text-xs truncate block mt-1" x-text="shopSeller?.joined || 'April 2026'"></span>
                            </div>
                        </div>
                    </div>

                </div>

                {{-- Collection Header, Filters & Search --}}
                <div class="space-y-4">
                    <div class="flex flex-col sm:flex-row items-center justify-between gap-3">
                        <div class="flex items-center gap-2 overflow-x-auto no-scrollbar w-full sm:w-auto">
                            <button type="button"
                                @click="shopActiveTab = 'all'"
                                :class="shopActiveTab === 'all' ? 'bg-[#C0420A] text-white shadow-sm' : 'bg-white text-stone-600 border border-stone-200 hover:bg-stone-50'"
                                class="px-4 py-2 rounded-full text-[10px] font-black uppercase tracking-wider transition-all cursor-pointer">
                                All Pieces (<span x-text="shopProducts.length"></span>)
                            </button>
                            <button type="button"
                                @click="shopActiveTab = 'sale'"
                                :class="shopActiveTab === 'sale' ? 'bg-[#C0420A] text-white shadow-sm' : 'bg-white text-stone-600 border border-stone-200 hover:bg-stone-50'"
                                class="px-4 py-2 rounded-full text-[10px] font-black uppercase tracking-wider transition-all cursor-pointer">
                                On Sale
                            </button>
                            <button type="button"
                                @click="shopActiveTab = 'rated'"
                                :class="shopActiveTab === 'rated' ? 'bg-[#C0420A] text-white shadow-sm' : 'bg-white text-stone-600 border border-stone-200 hover:bg-stone-50'"
                                class="px-4 py-2 rounded-full text-[10px] font-black uppercase tracking-wider transition-all cursor-pointer">
                                Highest Rated
                            </button>
                            <button type="button"
                                @click="shopActiveTab = 'policies'"
                                :class="shopActiveTab === 'policies' ? 'bg-[#C0420A] text-white shadow-sm' : 'bg-white text-stone-600 border border-stone-200 hover:bg-stone-50'"
                                class="px-4 py-2 rounded-full text-[10px] font-black uppercase tracking-wider transition-all cursor-pointer flex items-center gap-1">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                <span>Shop Policies</span>
                            </button>
                        </div>

                        {{-- Search Input in Shop --}}
                        <div class="relative w-full sm:w-64" x-show="shopActiveTab !== 'policies'">
                            <input type="text" placeholder="Filter shop pieces..." x-model="shopSearchQuery"
                                class="w-full pl-9 pr-4 py-2 bg-white border border-stone-200 rounded-full text-xs text-stone-800 placeholder:text-stone-400 focus:outline-none focus:border-[#C0422A] shadow-xs">
                            <svg class="w-4 h-4 text-stone-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        </div>
                    </div>

                    {{-- Loading Indicator --}}
                    <div x-show="shopLoading" class="py-16 text-center space-y-3">
                        <svg class="w-8 h-8 animate-spin text-[#C0422A] mx-auto" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        <p class="text-xs text-stone-500 font-medium">Loading artisan storefront...</p>
                    </div>

                    {{-- Dedicated Policies Tab --}}
                    <div x-show="shopActiveTab === 'policies' && !shopLoading" class="space-y-4" x-cloak>
                        <div class="bg-white rounded-2xl p-6 border border-stone-200 shadow-sm space-y-4">
                            <div class="border-b border-stone-100 pb-3">
                                <div class="flex items-center gap-2 text-[#C0422A] text-xs font-black uppercase tracking-widest mb-1">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                                    Artisan Trust &amp; Storefront Policies
                                </div>
                                <h3 class="font-serif text-xl font-bold text-black" x-text="`${shopSeller?.shopName || 'Shop'} Terms & Guarantees`"></h3>
                                <p class="text-xs text-stone-500 mt-1">Review the customer terms set by this artisan workshop.</p>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                {{-- Cancellation Policy --}}
                                <div class="p-4 bg-amber-50/70 border border-amber-200/80 rounded-2xl space-y-2">
                                    <div class="flex items-center gap-2 text-xs font-bold text-amber-900 uppercase tracking-wider">
                                        <div class="w-6 h-6 rounded-lg bg-amber-200 flex items-center justify-center text-amber-800 shrink-0">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        </div>
                                        <span>Cancellation Policy</span>
                                    </div>
                                    <p class="text-xs text-stone-700 leading-relaxed font-medium" x-text="shopSeller?.cancellation_policy || 'Cancellation requests must be submitted prior to order processing and payment verification. Once payment is confirmed and artisan crafting begins, cancellations may not be accepted.'"></p>
                                </div>

                                {{-- Refund & Return Policy --}}
                                <div class="p-4 bg-blue-50/70 border border-blue-200/80 rounded-2xl space-y-2">
                                    <div class="flex items-center gap-2 text-xs font-bold text-blue-900 uppercase tracking-wider">
                                        <div class="w-6 h-6 rounded-lg bg-blue-200 flex items-center justify-center text-blue-800 shrink-0">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        </div>
                                        <span>Refund &amp; Return Policy</span>
                                    </div>
                                    <p class="text-xs text-stone-700 leading-relaxed font-medium" x-text="shopSeller?.refund_policy || 'Refund requests are subject to shop evaluation. Custom tailored garments are crafted to provided measurements. Damaged or defective items upon arrival may be submitted for review through our return system.'"></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Products Grid --}}
                    <div x-show="shopActiveTab !== 'policies' && !shopLoading" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3 sm:gap-4" x-cloak>
                        <template x-for="product in displayedShopProducts" :key="product.id">
                            <div @click="openProductPreview(product)" class="group relative flex flex-col bg-white rounded-2xl shadow-xs hover:shadow-md border border-stone-200 hover:border-[#C0422A] transition-all cursor-pointer overflow-hidden">
                                <div class="relative aspect-square overflow-hidden bg-stone-50">
                                    <img :src="getProductImage(product.image)" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105" onerror="this.src='/uploads/products/default.jpg'" />
                                    <template x-if="product.is_on_sale">
                                        <div class="absolute top-2 right-2 bg-[#C0420A] text-white px-2 py-0.5 rounded text-[8px] font-black uppercase tracking-wider shadow-sm z-10">
                                            Sale <span x-show="parseFloat(product.discount_percentage || 0) > 0" x-text="'-' + Math.round(product.discount_percentage) + '%'"></span>
                                        </div>
                                    </template>
                                </div>
                                <div class="p-3 flex flex-1 flex-col justify-between space-y-2">
                                    <div>
                                        <h4 class="text-xs font-bold text-gray-900 group-hover:text-[#C0422A] line-clamp-2 transition-colors leading-tight" x-text="product.name"></h4>
                                        <div class="flex items-center gap-1 mt-1 text-[10px] font-bold text-gray-500">
                                            <svg class="w-3 h-3 text-amber-400 fill-current" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                            <span x-text="Number(product.rating || 0).toFixed(1)"></span>
                                            <span class="text-gray-300">•</span>
                                            <span class="text-stone-400 font-normal" x-text="'Sold ' + (product.soldCount || 0)"></span>
                                        </div>
                                    </div>
                                    <div class="flex items-center justify-between pt-1 border-t border-stone-100">
                                        <div>
                                            <template x-if="product.is_on_sale && parseFloat(product.discount_percentage || 0) > 0">
                                                <div class="flex items-center gap-1 flex-wrap">
                                                    <span class="text-xs font-black text-[#C0420A]" x-text="'₱' + parseFloat(product.price * (1 - product.discount_percentage / 100)).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})"></span>
                                                    <span class="text-[9px] text-gray-400 line-through" x-text="'₱' + parseFloat(product.price).toLocaleString()"></span>
                                                </div>
                                            </template>
                                            <template x-if="!(product.is_on_sale && parseFloat(product.discount_percentage || 0) > 0)">
                                                <span class="text-xs font-black text-[#C0420A]" x-text="'₱' + parseFloat(product.price || 0).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})"></span>
                                            </template>
                                        </div>
                                        <span class="text-[9px] text-stone-500 font-bold bg-stone-100 px-1.5 py-0.5 rounded">Inspect ↗</span>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>

                    {{-- Empty State --}}
                    <div x-show="shopActiveTab !== 'policies' && displayedShopProducts.length === 0 && !shopLoading" class="rounded-2xl border-2 border-dashed border-stone-200 p-12 text-center" x-cloak>
                        <svg class="w-10 h-10 mx-auto mb-2 text-stone-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/></svg>
                        <p class="font-serif italic text-sm text-stone-400">No pieces found in this artisan catalog.</p>
                    </div>

                </div>

            </div>

            {{-- Modal Footer --}}
            <div class="px-6 py-3.5 bg-white border-t border-stone-200 flex items-center justify-between shrink-0">
                <div class="text-[11px] text-stone-500 font-medium flex items-center gap-1.5">
                    <span class="w-1.5 h-1.5 rounded-full bg-stone-400"></span>
                    <span>Admin Preview Session</span>
                </div>
                <button type="button" @click="shopPreviewModal = false" class="px-5 py-2 bg-stone-900 hover:bg-[#C0420A] text-white rounded-xl text-xs font-bold uppercase tracking-wider transition-all cursor-pointer">
                    Close Preview
                </button>
            </div>

        </div>
    </div>

    {{-- ─── Admin Product Detail Inspection Lightbox (Read-Only) ─── --}}
    <div x-show="productPreviewModal" class="fixed inset-0 z-70 flex items-center justify-center p-3 sm:p-6" style="z-index: 70;" x-cloak>
        <div class="absolute inset-0 bg-black/80 backdrop-blur-md" @click="productPreviewModal = false"></div>
        <div class="relative bg-white rounded-3xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto no-scrollbar z-10 border border-gray-200 p-6 space-y-5">
            
            {{-- Modal Header --}}
            <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                <div class="flex items-center gap-2">
                    <span class="px-2 py-0.5 bg-amber-100 text-amber-800 text-[9px] font-black uppercase tracking-wider rounded">Admin Inspection</span>
                    <span class="text-xs text-gray-500 font-bold">Product ID: <span x-text="selectedProductPreview?.id"></span></span>
                </div>
                <button type="button" @click="productPreviewModal = false" class="w-7 h-7 rounded-full bg-gray-100 hover:bg-gray-200 text-gray-500 flex items-center justify-center cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            {{-- Product Info Grid --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 items-start">
                <div class="aspect-4/5 rounded-2xl overflow-hidden bg-stone-100 border border-stone-200 relative">
                    <img :src="getProductImage(selectedProductPreview?.image)" class="w-full h-full object-cover object-top" onerror="this.src='/uploads/products/default.jpg'">
                </div>
                <div class="space-y-3.5 text-xs">
                    <div>
                        <h3 class="font-serif text-lg font-bold text-gray-900 leading-tight" x-text="selectedProductPreview?.name"></h3>
                        <p class="text-[11px] text-gray-500 mt-0.5">By <strong class="text-black" x-text="shopSeller?.shopName || 'Artisan'"></strong></p>
                    </div>

                    <div class="p-3 bg-stone-50 rounded-xl space-y-1.5 border border-stone-200">
                        <div class="flex justify-between items-center">
                            <span class="text-gray-500 font-medium">Listing Price:</span>
                            <span class="text-sm font-black text-[#C0422A]" x-text="'₱' + parseFloat(selectedProductPreview?.price || 0).toLocaleString(undefined, {minimumFractionDigits: 2})"></span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-500 font-medium">Available Stock:</span>
                            <span class="font-bold text-gray-800" x-text="(selectedProductPreview?.stock || 0) + ' units'"></span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-500 font-medium">Rating:</span>
                            <span class="font-bold text-gray-800" x-text="Number(selectedProductPreview?.rating || 0).toFixed(1) + ' ★ (' + (selectedProductPreview?.reviewCount || 0) + ' reviews)'"></span>
                        </div>
                    </div>

                    <div>
                        <span class="text-[10px] font-bold uppercase tracking-wider text-gray-400 block mb-1">Description</span>
                        <p class="text-gray-700 leading-relaxed max-h-36 overflow-y-auto no-scrollbar whitespace-pre-line" x-text="selectedProductPreview?.description || 'No description provided.'"></p>
                    </div>

                    {{-- Admin Read-Only Notice --}}
                    <div class="p-3 bg-amber-50 border border-amber-200 rounded-xl text-[11px] text-amber-900 leading-snug">
                        <strong>🛡️ Ordering Inactive:</strong> Administrators cannot place customer orders or add items to cart from the admin control panel.
                    </div>
                </div>
            </div>

            <div class="pt-2 flex justify-end">
                <button type="button" @click="productPreviewModal = false" class="px-5 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl text-xs font-bold transition-all cursor-pointer">
                    Back to Shop Preview
                </button>
            </div>
        </div>
    </div>
</div>
@endsection
