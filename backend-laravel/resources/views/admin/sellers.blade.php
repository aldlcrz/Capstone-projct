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
    openDelete(id, name) {
        this.deleteSellerId = id;
        this.deleteSellerName = name;
        this.deleteModal = true;
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
            <a href="{{ request()->fullUrlWithQuery(['filter' => null, 'page' => 1]) }}"
               class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-[10px] sm:text-[11px] font-black uppercase tracking-wider transition-all {{ empty($currentFilter) ? 'bg-black text-white shadow-sm' : 'bg-white text-gray-700 border border-gray-200 hover:bg-gray-50' }}">
                <span>ALL</span>
                <span class="px-2 py-0.5 rounded-full text-[9px] font-black {{ empty($currentFilter) ? 'bg-white/20 text-white' : 'bg-gray-100 text-gray-600' }}">{{ $counts['all'] }}</span>
            </a>

            {{-- VERIFIED --}}
            <a href="{{ request()->fullUrlWithQuery(['filter' => 'verified', 'page' => 1]) }}"
               class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-[10px] sm:text-[11px] font-black uppercase tracking-wider transition-all {{ $currentFilter === 'verified' ? 'bg-black text-white shadow-sm' : 'bg-white text-gray-700 border border-gray-200 hover:bg-gray-50' }}">
                <span>VERIFIED</span>
                <span class="px-2 py-0.5 rounded-full text-[9px] font-black {{ $currentFilter === 'verified' ? 'bg-white/20 text-white' : 'bg-gray-100 text-gray-600' }}">{{ $counts['verified'] }}</span>
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
                        <div class="text-[10px] text-gray-500 font-medium truncate">{{ $seller->email }} • <span class="text-[#C0422A] font-semibold">{{ $seller->shopName ?? 'Workshop' }}</span></div>
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

    {{-- All Sellers Table --}}
    <div class="bg-white rounded-3xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-50 flex items-center justify-between">
            <h3 class="text-sm font-black uppercase tracking-widest text-black">All Sellers</h3>
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
                            <a href="/shop/{{ urlencode($seller->shopName) }}" target="_blank" class="text-[#3D2B1F] hover:text-[#C0422A] hover:underline flex items-center gap-1 font-bold">
                                <span>{{ $seller->shopName }}</span>
                                <svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                            </a>
                        @else
                            <span class="text-gray-400">—</span>
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
                            <span class="font-bold text-[#C0422A]" x-text="selectedSeller.shopName"></span>
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
    <div x-show="previewModal" class="fixed inset-0 z-[70] flex items-center justify-center p-4 sm:p-6" style="z-index: 70;" x-cloak>
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
        <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="deleteModal = false"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md p-6 space-y-4 z-10">
            <div class="w-12 h-12 rounded-2xl bg-red-100 flex items-center justify-center mx-auto">
                <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
            </div>
            <div class="text-center">
                <h3 class="text-lg font-bold text-gray-900">Delete Seller Account</h3>
                <p class="text-xs text-gray-500 leading-relaxed mt-2">
                    Are you sure you want to permanently delete <strong x-text="deleteSellerName" class="text-red-600"></strong>'s account? This action <span class="font-bold text-red-600">cannot be undone</span>.
                </p>
            </div>
            <form :action="'/admin/sellers/' + deleteSellerId" method="POST" class="space-y-4">
                @csrf @method('DELETE')
                <div class="flex gap-3 pt-2">
                    <button type="button" @click="deleteModal = false" class="flex-1 py-2.5 border border-gray-200 text-xs font-semibold text-gray-500 rounded-xl hover:bg-gray-50 cursor-pointer">Cancel</button>
                    <button type="submit" class="flex-1 py-2.5 bg-red-600 text-white text-xs font-bold uppercase tracking-wider rounded-xl hover:bg-red-700 cursor-pointer shadow-sm">Delete Permanently</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
