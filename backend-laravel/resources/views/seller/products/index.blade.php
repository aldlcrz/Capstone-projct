@extends('layouts.seller')

@section('content')
<div class="space-y-8" x-data="sellerProducts()">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="text-[10px] font-bold text-[#C0420A] uppercase tracking-[0.2em] mb-0.5">Inventory Management</div>
            <h1 class="font-serif text-xl sm:text-3xl font-bold text-black uppercase">Product <span class="text-[#C0420A] italic lowercase">catalogue</span></h1>
        </div>
        <div class="flex items-center gap-2.5 sm:gap-3">
            <button @click="showSizeGuideModal = true" type="button" class="flex items-center gap-2 px-4 py-3.5 sm:px-6 sm:py-4 bg-white text-stone-800 border border-stone-200 rounded-xl text-[10px] font-bold uppercase tracking-widest hover:bg-stone-50 hover:border-stone-400 transition-all shadow-sm">
                <svg class="w-4 h-4 text-[#C0420A]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                Size Guide
            </button>
            <a href="{{ route('seller.products.create') }}" class="flex items-center gap-2 px-5 py-3.5 sm:px-8 sm:py-4 bg-black text-white rounded-xl text-[10px] font-bold uppercase tracking-widest hover:bg-[#C0420A] transition-all shadow-xl shadow-black/5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                Add Product
            </a>
        </div>
    </div>

    @php
        $approvedProducts = $products->filter(fn($p) => $p->status === 'approved');
        $pendingProducts  = $products->filter(fn($p) => $p->status === 'pending' || $p->status !== 'approved');
    @endphp

    {{-- Filter Toolbar & Real-Time Search Bar --}}
    <div class="bg-white p-4 sm:p-5 rounded-2xl sm:rounded-3xl border border-gray-100 shadow-sm flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-4">
        {{-- Status Filter Tabs --}}
        <div class="flex items-center gap-2 overflow-x-auto no-scrollbar pb-1 sm:pb-0">
            <button @click="activeTab = 'all'"
                :class="activeTab === 'all' ? 'bg-black text-white shadow-md' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
                class="px-4 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all shrink-0">
                All Products ({{ $products->count() }})
            </button>
            <button @click="activeTab = 'approved'"
                :class="activeTab === 'approved' ? 'bg-green-600 text-white shadow-md' : 'bg-green-50 text-green-700 border border-green-100 hover:bg-green-100'"
                class="px-4 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all shrink-0 flex items-center gap-1.5">
                <span class="w-2 h-2 rounded-full bg-green-400"></span>
                Approved ({{ $approvedProducts->count() }})
            </button>
            <button @click="activeTab = 'pending'"
                :class="activeTab === 'pending' ? 'bg-amber-600 text-white shadow-md' : 'bg-amber-50 text-amber-700 border border-amber-100 hover:bg-amber-100'"
                class="px-4 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all shrink-0 flex items-center gap-1.5">
                <span class="w-2 h-2 rounded-full bg-amber-400"></span>
                Pending Approval ({{ $pendingProducts->count() }})
            </button>
        </div>

        {{-- Live Search Input --}}
        <div class="relative w-full sm:w-72">
            <input type="text"
                x-model="search"
                placeholder="Search products by name..."
                class="w-full pl-10 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-xs font-bold outline-none focus:border-[#C0420A] focus:bg-white transition-all">
            <svg class="w-4 h-4 text-gray-400 absolute left-3.5 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
            </svg>
            <button x-show="search" @click="search = ''" class="absolute right-3 top-2.5 text-xs text-gray-400 hover:text-red-500 font-bold">✕</button>
        </div>
    </div>

    {{-- SECTION 1: PENDING PRODUCTS (shown when activeTab is 'all' or 'pending') --}}
    <div x-show="activeTab === 'all' || activeTab === 'pending'" class="space-y-6">
        @if($pendingProducts->isNotEmpty())
            <div class="flex items-center gap-3 pb-3 border-b border-amber-200">
                <div class="w-2.5 h-2.5 rounded-full bg-amber-500 animate-pulse"></div>
                <h2 class="text-xs font-black uppercase tracking-widest text-amber-700 flex items-center gap-2">
                    Pending Approval Products
                    <span class="px-2 py-0.5 bg-amber-100 text-amber-800 text-[9px] font-bold rounded-md">{{ $pendingProducts->count() }} awaiting review</span>
                </h2>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-3 sm:gap-6">
                @foreach($pendingProducts as $product)
                    <div x-show="matches('{{ addslashes($product->name) }}', '{{ addslashes($product->description ?? '') }}', '{{ $product->status }}')"
                        class="group bg-white rounded-2xl sm:rounded-3xl border border-amber-200 shadow-xs hover:shadow-xl transition-all duration-500 overflow-hidden flex flex-col">
                        
                        <!-- Image Section -->
                        <div class="relative aspect-4/5 sm:aspect-3/4 overflow-hidden bg-gray-50">
                            <img src="{{ $product->getImageUrl() }}" onerror="this.src='/uploads/products/default.jpg'" class="w-full h-full object-cover object-top group-hover:scale-105 transition-transform duration-700">
                            
                            <div class="absolute top-2.5 left-2.5 sm:top-4 sm:left-4">
                                <span class="px-2 py-0.5 sm:px-3 sm:py-1 rounded-full text-[8px] sm:text-[9px] font-black uppercase tracking-widest bg-amber-100 text-amber-700 border border-amber-200">
                                    ⏳ Pending Approval
                                </span>
                            </div>

                            @if($product->is_on_sale && $product->discount_percentage > 0)
                                <div class="absolute top-2.5 right-2.5 sm:top-4 sm:right-4 flex flex-col items-end gap-1">
                                    <span class="px-1.5 py-0.5 sm:px-2.5 sm:py-0.5 bg-[#C0420A] text-white text-[7px] sm:text-[8px] font-black uppercase tracking-widest rounded-md shadow-md">
                                        Special
                                    </span>
                                    <span class="px-1 py-0.5 sm:px-1.5 sm:py-0.5 bg-black text-white text-[7px] sm:text-[8px] font-black rounded-md">
                                        -{{ number_format($product->discount_percentage, 0) }}%
                                    </span>
                                </div>
                            @endif

                            {{-- Desktop Hover Action Buttons (Edit + Delete) --}}
                            <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-3 backdrop-blur-xs">
                                <a href="/seller/products/{{ $product->id }}/edit" title="Edit Product" class="w-10 h-10 sm:w-12 sm:h-12 bg-white rounded-full flex items-center justify-center text-black hover:bg-[#C0420A] hover:text-white transition-all shadow-xl">
                                    <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                </a>
                                <button type="button" @click="openDeleteModal('{{ $product->id }}', '{{ addslashes($product->name) }}')" title="Delete Product" class="w-10 h-10 sm:w-12 sm:h-12 bg-white rounded-full flex items-center justify-center text-red-600 hover:bg-red-600 hover:text-white transition-all shadow-xl cursor-pointer">
                                    <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-4v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                </button>
                            </div>
                        </div>

                        <div class="p-3.5 sm:p-6 space-y-2 sm:space-y-4 flex-1 flex flex-col">
                            <div class="flex-1">
                                <h3 class="text-xs sm:text-sm font-bold text-black line-clamp-1 uppercase tracking-tight">{{ $product->name }}</h3>
                                <p class="text-[9px] sm:text-[10px] text-gray-400 mt-0.5 sm:mt-1 line-clamp-2 leading-relaxed">{{ $product->description }}</p>
                                
                                {{-- Product Rating & Reviews summary --}}
                                <div class="flex items-center justify-between gap-2 mt-2 pt-2 border-t border-gray-100">
                                    <div class="flex items-center gap-1.5">
                                        <span class="text-amber-400 text-xs font-black">★</span>
                                        <span class="text-[11px] font-black text-black">{{ $product->reviews_avg_rating ? number_format($product->reviews_avg_rating, 1) : '0.0' }}</span>
                                        <span class="text-[9px] text-gray-400 font-bold">({{ $product->reviews_count }} {{ Str::plural('review', $product->reviews_count) }})</span>
                                    </div>
                                    @if($product->reviews_count > 0)
                                        <button type="button" @click="openReviewsModal('{{ $product->id }}')" class="text-[9px] font-black uppercase tracking-wider text-[#C0422A] hover:underline cursor-pointer">
                                            View Reviews →
                                        </button>
                                    @endif
                                </div>
                            </div>
                            
                            <div class="flex items-center justify-between pt-2.5 sm:pt-4 border-t border-gray-50">
                                <div>
                                    <div class="text-[8px] sm:text-[9px] font-bold text-gray-400 uppercase tracking-widest">Price</div>
                                    <div class="text-xs sm:text-sm font-black text-black">₱{{ number_format($product->price) }}</div>
                                </div>
                                <div class="text-right">
                                    <div class="text-[8px] sm:text-[9px] font-bold text-gray-400 uppercase tracking-widest">Stock</div>
                                    <div class="text-xs sm:text-sm font-black text-amber-600">{{ $product->stock }}</div>
                                </div>
                            </div>

                            <!-- Mobile Quick Edit & Delete Buttons -->
                            <div class="sm:hidden grid grid-cols-2 gap-2 pt-2 border-t border-gray-100">
                                <a href="/seller/products/{{ $product->id }}/edit" class="py-2 bg-gray-50 text-gray-800 rounded-xl text-[9px] font-black uppercase tracking-wider text-center border border-gray-200 flex items-center justify-center gap-1">
                                    <svg class="w-3 h-3 text-[#C0420A]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                    Edit
                                </a>
                                <button type="button" @click="openDeleteModal('{{ $product->id }}', '{{ addslashes($product->name) }}')" class="w-full py-2 bg-red-50 text-red-600 rounded-xl text-[9px] font-black uppercase tracking-wider text-center border border-red-100 flex items-center justify-center gap-1 cursor-pointer">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-4v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                    Delete
                                </button>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- SECTION 2: APPROVED PRODUCTS (shown when activeTab is 'all' or 'approved') --}}
    <div x-show="activeTab === 'all' || activeTab === 'approved'" class="space-y-6">
        <div class="flex items-center gap-3 pb-3 border-b border-green-200">
            <div class="w-2.5 h-2.5 rounded-full bg-green-500"></div>
            <h2 class="text-xs font-black uppercase tracking-widest text-green-700 flex items-center gap-2">
                Approved & Active Products
                <span class="px-2 py-0.5 bg-green-100 text-green-800 text-[9px] font-bold rounded-md">{{ $approvedProducts->count() }} items live</span>
            </h2>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-3 sm:gap-6">
            @forelse($approvedProducts as $product)
                <div x-show="matches('{{ addslashes($product->name) }}', '{{ addslashes($product->description ?? '') }}', '{{ $product->status }}')"
                    class="group bg-white rounded-2xl sm:rounded-3xl border border-gray-100 shadow-sm hover:shadow-xl transition-all duration-500 overflow-hidden flex flex-col">
                    
                    <!-- Image Section -->
                    <div class="relative aspect-4/5 sm:aspect-3/4 overflow-hidden bg-gray-50">
                        <img src="{{ $product->getImageUrl() }}" onerror="this.src='/uploads/products/default.jpg'" class="w-full h-full object-cover object-top group-hover:scale-105 transition-transform duration-700">
                        
                        <div class="absolute top-2.5 left-2.5 sm:top-4 sm:left-4">
                            <span class="px-2 py-0.5 sm:px-3 sm:py-1 rounded-full text-[8px] sm:text-[9px] font-black uppercase tracking-widest bg-green-100 text-green-700 border border-green-200">
                                ✓ Approved
                            </span>
                        </div>

                        @if($product->is_on_sale && $product->discount_percentage > 0)
                            <div class="absolute top-2.5 right-2.5 sm:top-4 sm:right-4 flex flex-col items-end gap-1">
                                <span class="px-1.5 py-0.5 sm:px-2.5 sm:py-0.5 bg-[#C0420A] text-white text-[7px] sm:text-[8px] font-black uppercase tracking-widest rounded-md shadow-md">
                                    Special
                                </span>
                                <span class="px-1 py-0.5 sm:px-1.5 sm:py-0.5 bg-black text-white text-[7px] sm:text-[8px] font-black rounded-md">
                                    -{{ number_format($product->discount_percentage, 0) }}%
                                </span>
                            </div>
                        @endif

                        {{-- Desktop Hover Action Buttons (Edit + Delete) --}}
                        <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-3 backdrop-blur-xs">
                            <a href="/seller/products/{{ $product->id }}/edit" title="Edit Product" class="w-10 h-10 sm:w-12 sm:h-12 bg-white rounded-full flex items-center justify-center text-black hover:bg-[#C0420A] hover:text-white transition-all shadow-xl">
                                <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                            </a>
                            <button type="button" @click="openDeleteModal('{{ $product->id }}', '{{ addslashes($product->name) }}')" title="Delete Product" class="w-10 h-10 sm:w-12 sm:h-12 bg-white rounded-full flex items-center justify-center text-red-600 hover:bg-red-600 hover:text-white transition-all shadow-xl cursor-pointer">
                                <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-4v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                            </button>
                        </div>
                    </div>

                    <div class="p-3.5 sm:p-6 space-y-2 sm:space-y-4 flex-1 flex flex-col">
                        <div class="flex-1">
                            <h3 class="text-xs sm:text-sm font-bold text-black line-clamp-1 uppercase tracking-tight">{{ $product->name }}</h3>
                            <p class="text-[9px] sm:text-[10px] text-gray-400 mt-0.5 sm:mt-1 line-clamp-2 leading-relaxed">{{ $product->description }}</p>
                            
                            {{-- Product Rating & Reviews summary --}}
                            <div class="flex items-center justify-between gap-2 mt-2 pt-2 border-t border-gray-100">
                                <div class="flex items-center gap-1.5">
                                    <span class="text-amber-400 text-xs font-black">★</span>
                                    <span class="text-[11px] font-black text-black">{{ $product->reviews_avg_rating ? number_format($product->reviews_avg_rating, 1) : '0.0' }}</span>
                                    <span class="text-[9px] text-gray-400 font-bold">({{ $product->reviews_count }} {{ Str::plural('review', $product->reviews_count) }})</span>
                                </div>
                                @if($product->reviews_count > 0)
                                    <button type="button" @click="openReviewsModal('{{ $product->id }}')" class="text-[9px] font-black uppercase tracking-wider text-[#C0422A] hover:underline cursor-pointer">
                                        View Reviews →
                                    </button>
                                @endif
                            </div>
                        </div>
                        
                        <div class="flex items-center justify-between pt-2.5 sm:pt-4 border-t border-gray-50">
                            <div>
                                <div class="text-[8px] sm:text-[9px] font-bold text-gray-400 uppercase tracking-widest">Price</div>
                                <div class="flex items-center gap-1">
                                    <span class="text-xs sm:text-sm font-black text-black">₱{{ number_format($product->salePrice) }}</span>
                                    @if($product->is_on_sale && $product->discount_percentage > 0)
                                        <span class="text-[9px] text-gray-400 line-through">₱{{ number_format($product->price) }}</span>
                                    @endif
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="text-[8px] sm:text-[9px] font-bold text-gray-400 uppercase tracking-widest">Stock</div>
                                <div class="text-xs sm:text-sm font-black {{ $product->stock < 5 ? 'text-red-500' : 'text-green-600' }}">{{ $product->stock }}</div>
                            </div>
                        </div>

                        <!-- Mobile Quick Edit & Delete Buttons -->
                        <div class="sm:hidden grid grid-cols-2 gap-2 pt-2 border-t border-gray-100">
                            <a href="/seller/products/{{ $product->id }}/edit" class="py-2 bg-gray-50 text-gray-800 rounded-xl text-[9px] font-black uppercase tracking-wider text-center border border-gray-200 flex items-center justify-center gap-1">
                                <svg class="w-3 h-3 text-[#C0420A]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                Edit
                            </a>
                            <button type="button" @click="openDeleteModal('{{ $product->id }}', '{{ addslashes($product->name) }}')" class="w-full py-2 bg-red-50 text-red-600 rounded-xl text-[9px] font-black uppercase tracking-wider text-center border border-red-100 flex items-center justify-center gap-1 cursor-pointer">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-4v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                Delete
                            </button>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-span-full py-24 text-center bg-white rounded-3xl border border-dashed border-gray-200">
                    <svg class="w-16 h-16 text-gray-200 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                    <h3 class="text-base font-bold text-black mb-1">No approved products found</h3>
                    <p class="text-xs text-gray-400 mb-6 max-w-xs mx-auto">Products listed will appear here once approved by platform administrators.</p>
                </div>
            @endforelse
        </div>
    </div>

    {{-- Size Guide Management Modal --}}
    <div x-show="showSizeGuideModal" style="display: none;" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center p-4" x-transition>
        <div @click.away="showSizeGuideModal = false" class="w-full max-w-xl bg-white rounded-3xl shadow-2xl overflow-hidden border border-stone-100 flex flex-col max-h-[90vh]">
            {{-- Modal Header --}}
            <div class="px-6 py-5 border-b border-stone-100 flex items-center justify-between bg-stone-50/50 shrink-0">
                <div>
                    <h2 class="font-serif text-lg font-bold text-stone-900 uppercase tracking-wide">Shop Size Guides</h2>
                    <p class="text-[11px] text-stone-500 font-medium">Manage standard size guide charts for Men, Women, and Kids</p>
                </div>
                <button @click="showSizeGuideModal = false" type="button" class="w-8 h-8 rounded-xl bg-stone-200/60 hover:bg-stone-300 text-stone-600 flex items-center justify-center transition-all">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            {{-- Modal Body --}}
            <div class="p-6 overflow-y-auto space-y-6">
                {{-- Category Pill Tabs (Men, Women, Kids) --}}
                <div class="flex gap-2 bg-stone-100 p-1.5 rounded-2xl">
                    <template x-for="tab in ['Men', 'Women', 'Kids']" :key="tab">
                        <button type="button" @click="activeSGTab = tab"
                            :class="activeSGTab === tab ? 'bg-black text-white shadow-md font-black' : 'text-stone-600 hover:text-black font-bold'"
                            class="flex-1 py-2.5 rounded-xl text-xs uppercase tracking-widest transition-all text-center">
                            <span x-text="tab === 'Men' ? '👔 Men' : (tab === 'Women' ? '👗 Women' : '🧒 Kids')"></span>
                        </button>
                    </template>
                </div>

                {{-- Display for activeSGTab --}}
                <div>
                    {{-- IF NO SIZE GUIDE YET --}}
                    <div x-show="!sizeGuides || !sizeGuides[activeSGTab]">
                        <form action="{{ route('seller.sizeguides.update') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <input type="hidden" name="target_group" :value="activeSGTab">
                            <label class="cursor-pointer flex flex-col items-center justify-center border-2 border-dashed border-stone-200 rounded-3xl p-8 hover:border-[#C0420A] hover:bg-orange-50/30 transition-all text-center group">
                                <div class="w-14 h-14 rounded-2xl bg-stone-100 group-hover:bg-orange-100 text-[#C0420A] flex items-center justify-center text-2xl mb-3 transition-colors shadow-xs">
                                    📐
                                </div>
                                <div class="text-sm font-bold text-stone-800 mb-1">
                                    No size guide added yet
                                </div>
                                <div class="text-xs text-[#C0420A] font-extrabold uppercase tracking-wider mb-2 group-hover:underline">
                                    Click to add Size Guide
                                </div>
                                <div class="text-[10px] text-stone-400 font-medium">
                                    Upload size guide image for <span class="font-bold text-stone-600" x-text="activeSGTab"></span> (PNG, JPG, WEBP • Max 5MB)
                                </div>
                                <input type="file" name="size_guide_image" accept="image/*" class="hidden" onchange="this.form.submit()">
                            </label>
                        </form>
                    </div>

                    {{-- IF THERE IS A SIZE GUIDE --}}
                    <div x-show="sizeGuides && sizeGuides[activeSGTab]" class="space-y-4">
                        <div class="rounded-2xl border border-stone-200 bg-stone-50/50 p-4 shadow-sm text-center relative group">
                            <img :src="sizeGuides && sizeGuides[activeSGTab] && (sizeGuides[activeSGTab].startsWith('http') ? sizeGuides[activeSGTab] : ('/' + sizeGuides[activeSGTab].replace(/^\/+/, '')))"
                                 class="w-full max-h-80 object-contain rounded-xl mx-auto shadow-xs">
                        </div>

                        <div class="flex items-center gap-3 pt-2">
                            <form action="{{ route('seller.sizeguides.update') }}" method="POST" enctype="multipart/form-data" class="flex-1">
                                @csrf
                                <input type="hidden" name="target_group" :value="activeSGTab">
                                <label class="w-full py-3 px-4 bg-stone-900 hover:bg-[#C0420A] text-white rounded-xl text-xs font-bold uppercase tracking-widest transition-all cursor-pointer flex items-center justify-center gap-2 shadow-sm">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003-3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                                    Update Size Guide
                                    <input type="file" name="size_guide_image" accept="image/*" class="hidden" onchange="this.form.submit()">
                                </label>
                            </form>

                            <form :action="'/seller/size-guides/' + activeSGTab" method="POST" onsubmit="return confirm('Are you sure you want to remove this size guide?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="py-3 px-4 bg-red-50 hover:bg-red-600 text-red-600 hover:text-white border border-red-100 rounded-xl text-xs font-bold uppercase tracking-widest transition-all">
                                    Delete
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- CUSTOMER REVIEWS MODAL --}}
    <div x-show="showReviewsModal" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 overflow-y-auto bg-black/60 backdrop-blur-xs flex items-center justify-center p-4 sm:p-6"
         style="display: none;"
         @click.self="showReviewsModal = false"
         @keydown.escape.window="if (lightboxImage) { lightboxImage = null; } else if (showReviewsModal) { showReviewsModal = false; }">
        
        <div x-show="showReviewsModal"
             x-transition:enter="transition ease-out duration-300 transform"
             x-transition:enter-start="opacity-0 scale-95 translate-y-4"
             x-transition:enter-end="opacity-100 scale-100 translate-y-0"
             class="bg-white rounded-3xl max-w-xl w-full p-6 sm:p-8 space-y-6 shadow-2xl border border-gray-100 max-h-[85vh] flex flex-col">
            
            {{-- Modal Header --}}
            <div class="flex items-center justify-between pb-4 border-b border-gray-100 shrink-0">
                <div>
                    <div class="text-[9px] font-black uppercase tracking-widest text-[#C0420A]">Customer Feedback</div>
                    <h3 class="font-serif text-lg font-bold text-black uppercase" x-text="selectedProduct ? selectedProduct.name : 'Product Reviews'"></h3>
                    <p class="text-xs text-gray-500 mt-0.5" x-text="selectedProduct && selectedProduct.reviews ? (selectedProduct.reviews.length + ' customer review(s)') : '0 reviews'"></p>
                </div>
                <button @click="showReviewsModal = false" class="w-8 h-8 rounded-full bg-gray-100 hover:bg-gray-200 text-gray-500 hover:text-black flex items-center justify-center transition-colors">
                    ✕
                </button>
            </div>

            {{-- Reviews List --}}
            <div class="flex-1 overflow-y-auto space-y-3 pr-1">
                <template x-if="!selectedProduct || !selectedProduct.reviews || selectedProduct.reviews.length === 0">
                    <div class="py-12 text-center text-gray-400">
                        <div class="text-3xl mb-2">⭐</div>
                        <p class="text-xs italic">No customer reviews yet for this product.</p>
                    </div>
                </template>

                <template x-for="rev in (selectedProduct?.reviews || [])" :key="rev.id">
                    <div class="bg-gray-50/80 p-4 rounded-2xl border border-gray-100 space-y-2">
                        <div class="flex items-center justify-between flex-wrap gap-2">
                            <div class="flex items-center gap-2.5">
                                <div class="w-7 h-7 rounded-full bg-[#C0420A]/10 text-[#C0420A] font-black text-xs flex items-center justify-center uppercase">
                                    <span x-text="rev.customer ? rev.customer.name.charAt(0) : 'C'"></span>
                                </div>
                                <div>
                                    <div class="text-xs font-bold text-black" x-text="rev.customer ? rev.customer.name : 'Verified Buyer'"></div>
                                    <div class="flex items-center text-amber-400 text-xs">
                                        <template x-for="star in 5" :key="star">
                                            <span :class="star <= rev.rating ? 'text-amber-400' : 'text-gray-300'">★</span>
                                        </template>
                                    </div>
                                </div>
                            </div>
                            <span class="text-[9px] font-medium text-gray-400" x-text="rev.createdAt ? new Date(rev.createdAt).toLocaleDateString('en-PH', {month:'short', day:'numeric', year:'numeric'}) : ''"></span>
                        </div>

                        <p class="text-xs text-gray-700 font-medium leading-relaxed" x-text="rev.comment || 'No written comment provided.'"></p>

                        {{-- Images if any --}}
                        <template x-if="rev.images">
                            <div class="flex flex-wrap gap-2 pt-1">
                                <template x-for="(img, idx) in (typeof rev.images === 'string' ? JSON.parse(rev.images || '[]') : (rev.images || []))" :key="idx">
                                    <button type="button" @click="lightboxImage = (img.startsWith('http') || img.startsWith('/') ? img : '/storage/' + img)" class="w-14 h-14 rounded-xl overflow-hidden border border-gray-200 shrink-0 shadow-xs hover:opacity-80 transition-transform hover:scale-105 cursor-pointer">
                                        <img :src="img.startsWith('http') || img.startsWith('/') ? img : '/storage/' + img" class="w-full h-full object-cover">
                                    </button>
                                </template>
                            </div>
                        </template>

                        {{-- Seller Response / Reply Section (Shopee & Lazada Style) --}}
                        <div class="mt-3 pt-2.5 border-t border-gray-200/70">
                            {{-- Existing Seller Response Display --}}
                            <template x-if="rev.seller_reply && replyingToRevId !== rev.id">
                                <div class="bg-white p-3 sm:p-3.5 rounded-xl border-l-4 border-[#C0420A] space-y-1.5 shadow-2xs">
                                    <div class="flex items-center justify-between gap-2">
                                        <span class="text-[10px] font-black uppercase tracking-wider text-[#C0420A] flex items-center gap-1.5">
                                            <span>💬 Seller's Response</span>
                                            <span class="text-gray-400 font-medium" x-text="rev.seller_reply_at ? '• ' + new Date(rev.seller_reply_at).toLocaleDateString('en-PH', {month:'short', day:'numeric', year:'numeric'}) : ''"></span>
                                        </span>
                                        <button type="button" @click="startReply(rev)" class="text-[9px] font-bold text-gray-400 hover:text-[#C0420A] uppercase tracking-wider hover:underline cursor-pointer">
                                            Edit Response
                                        </button>
                                    </div>
                                    <p class="text-xs text-gray-700 leading-relaxed font-normal" x-text="rev.seller_reply"></p>
                                </div>
                            </template>

                            {{-- Reply Button (when no response written yet) --}}
                            <template x-if="!rev.seller_reply && replyingToRevId !== rev.id">
                                <div class="flex justify-end">
                                    <button type="button" @click="startReply(rev)" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white hover:bg-orange-50 text-[#C0420A] border border-orange-200 rounded-lg text-[10px] font-black uppercase tracking-wider transition-all shadow-2xs hover:shadow-xs cursor-pointer">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/></svg>
                                        <span>Reply to Buyer</span>
                                    </button>
                                </div>
                            </template>

                            {{-- Inline Reply Editor Box --}}
                            <template x-if="replyingToRevId === rev.id">
                                <div class="bg-white p-3.5 rounded-2xl border border-orange-200 shadow-sm space-y-2.5">
                                    <div class="flex items-center justify-between">
                                        <span class="text-[10px] font-black uppercase tracking-wider text-[#C0420A] flex items-center gap-1">
                                            <span>💬</span>
                                            <span x-text="rev.seller_reply ? 'Edit Your Response' : 'Reply to ' + (rev.customer ? rev.customer.name : 'Customer')"></span>
                                        </span>
                                        <span class="text-[9px] font-medium text-gray-400" x-text="(1000 - replyText.length) + ' chars left'"></span>
                                    </div>
                                    <textarea 
                                        x-model="replyText" 
                                        rows="3" 
                                        maxlength="1000" 
                                        placeholder="Write a polite, professional response to this buyer (e.g. Thank you for your review! We are delighted that you love the craftsmanship and embroidery!)..."
                                        class="w-full text-xs p-2.5 rounded-xl border border-gray-200 focus:border-[#C0420A] focus:ring-1 focus:ring-[#C0420A] outline-hidden leading-relaxed resize-none"
                                    ></textarea>
                                    <div class="flex items-center justify-end gap-2">
                                        <button type="button" @click="cancelReply()" :disabled="isSubmittingReply" class="px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 text-[10px] font-bold uppercase tracking-wider rounded-lg transition-colors cursor-pointer">
                                            Cancel
                                        </button>
                                        <button type="button" @click="submitReply(rev.id)" :disabled="isSubmittingReply || !replyText.trim()" class="px-4 py-1.5 bg-[#C0420A] hover:bg-[#a63721] disabled:opacity-50 text-white text-[10px] font-black uppercase tracking-wider rounded-lg transition-all shadow-xs flex items-center gap-1.5 cursor-pointer">
                                            <span x-text="isSubmittingReply ? 'Posting...' : 'Submit Response'"></span>
                                        </button>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>
            </div>

            {{-- Close Button --}}
            <div class="pt-2 border-t border-gray-100 shrink-0">
                <button type="button" @click="showReviewsModal = false" class="w-full py-3 bg-black text-white text-xs font-bold uppercase tracking-widest rounded-xl hover:bg-[#C0420A] transition-all cursor-pointer">
                    Close
                </button>
            </div>
        </div>
    </div>

    {{-- LIGHTBOX IMAGE PREVIEW MODAL --}}
    <div x-show="lightboxImage" 
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-9999 bg-black/90 backdrop-blur-md flex flex-col items-center justify-center p-4 sm:p-6"
         style="display: none;"
         @click.self="lightboxImage = null">
        
        <div class="w-full max-w-4xl flex justify-end pb-3">
            <button type="button" @click="lightboxImage = null" class="px-4 py-2 bg-white/20 hover:bg-white/30 text-white rounded-full text-xs font-bold uppercase tracking-wider flex items-center gap-1.5 transition-all shadow-lg cursor-pointer">
                <span>Close</span>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>

        <div class="relative max-h-[85vh] max-w-full flex items-center justify-center pointer-events-none">
            <img :src="lightboxImage" class="max-h-[80vh] max-w-full rounded-2xl shadow-2xl object-contain border border-white/10 pointer-events-auto">
        </div>
    </div>

    {{-- Delete Product Confirmation Modal --}}
    <div x-show="showDeleteModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm" x-cloak>
        <div class="bg-white rounded-3xl w-full max-w-md p-6 sm:p-7 shadow-none border border-gray-200 space-y-5" @click.away="showDeleteModal = false">
            <div class="flex items-start gap-3.5">
                <div class="w-12 h-12 rounded-2xl bg-red-50 text-red-600 flex items-center justify-center shrink-0 border border-red-100">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                </div>
                <div class="min-w-0">
                    <h3 class="text-base sm:text-lg font-bold text-gray-900 leading-tight">Delete Product</h3>
                    <p class="text-xs text-gray-500 mt-1">Are you sure you want to delete <strong x-text="deletingProductName" class="text-black"></strong> from your catalogue?</p>
                </div>
            </div>

            <p class="text-xs text-gray-500 leading-relaxed bg-gray-50 p-3 rounded-2xl border border-gray-100">
                This item will be removed from your active catalogue and archived in the system registry.
            </p>

            <form :action="'/seller/products/' + deletingProductId" method="POST" class="flex gap-3 pt-1">
                @csrf
                @method('DELETE')
                <button type="button" @click="showDeleteModal = false" class="flex-1 py-2.5 border border-gray-200 text-xs font-bold text-gray-700 rounded-xl hover:bg-gray-50 transition-all cursor-pointer">
                    Cancel
                </button>
                <button type="submit" class="flex-1 py-2.5 bg-red-600 hover:bg-red-700 text-white text-xs font-bold uppercase tracking-wider rounded-xl transition-all shadow-none border-0 cursor-pointer">
                    Delete Product
                </button>
            </form>
        </div>
    </div>
</div>

<script id="seller-products-data" type="application/json">
    @json($products)
</script>
<script id="seller-sizeguides-data" type="application/json">
    @json(Auth::user()->size_guides ?? [])
</script>
@endsection

@push('scripts')
<script>
function sellerProducts() {
    return {
        search: '',
        activeTab: 'all',
        showSizeGuideModal: false,
        showReviewsModal: false,
        showDeleteModal: false,
        deletingProductId: null,
        deletingProductName: '',
        selectedProduct: null,
        lightboxImage: null,
        productsData: JSON.parse(document.getElementById('seller-products-data')?.textContent || '[]'),
        replyingToRevId: null,
        replyText: '',
        isSubmittingReply: false,
        activeSGTab: 'Men',
        sizeGuides: JSON.parse(document.getElementById('seller-sizeguides-data')?.textContent || '[]'),
        openDeleteModal(id, name) {
            this.deletingProductId = id;
            this.deletingProductName = name;
            this.showDeleteModal = true;
        },
        matches(productName, productDesc, productStatus) {
            const query = (this.search || '').toLowerCase().trim();
            const matchesSearch = !query || (productName || '').toLowerCase().includes(query) || (productDesc || '').toLowerCase().includes(query);
            const matchesTab = this.activeTab === 'all' || (productStatus || '').toLowerCase() === this.activeTab.toLowerCase();
            return matchesSearch && matchesTab;
        },
        openReviewsModal(productId) {
            this.selectedProduct = (this.productsData || []).find(p => String(p.id) === String(productId)) || null;
            this.replyingToRevId = null;
            this.replyText = '';
            this.showReviewsModal = true;
        },
        startReply(rev) {
            this.replyingToRevId = rev.id;
            this.replyText = rev.seller_reply || '';
        },
        cancelReply() {
            this.replyingToRevId = null;
            this.replyText = '';
        },
        async submitReply(revId) {
            if (!this.replyText.trim() || this.isSubmittingReply) return;
            this.isSubmittingReply = true;
            try {
                const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                const res = await fetch(`/seller/reviews/${revId}/reply`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': token,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ reply: this.replyText })
                });
                const data = await res.json();
                if (res.ok) {
                    if (this.selectedProduct && this.selectedProduct.reviews) {
                        const targetRev = this.selectedProduct.reviews.find(r => String(r.id) === String(revId));
                        if (targetRev) {
                            targetRev.seller_reply = data.seller_reply;
                            targetRev.seller_reply_at = data.seller_reply_at;
                        }
                    }
                    this.cancelReply();
                } else {
                    alert(data.message || 'Failed to submit response.');
                }
            } catch(e) {
                alert('An error occurred while submitting your reply. Please try again.');
            } finally {
                this.isSubmittingReply = false;
            }
        }
    };
}
</script>
@endpush
