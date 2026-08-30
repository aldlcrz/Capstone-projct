@extends('layouts.seller')

@section('content')
<div class="space-y-6 sm:space-y-8" x-data="sellerProducts()">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 pb-2 border-b" style="border-color: #E8DECB;">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="text-[9px] font-extrabold uppercase tracking-[0.25em]" style="color: #C49520;">✦ Shop Catalogue</span>
                <span class="text-xs" style="color: #E8DECB;">•</span>
                <span class="text-[10px] font-semibold tracking-wider uppercase" style="color: #766C60;">Lumban Artisan Registry</span>
            </div>
            <h1 class="font-serif text-2xl sm:text-3xl lg:text-4xl font-bold tracking-tight" style="color: #1E1915;">
                Your <span class="italic font-normal" style="color: #766C60;">Creations</span>
            </h1>
            <p class="text-xs sm:text-sm font-medium mt-1" style="color: #766C60;">
                Manage your handcrafted creations, variants, and inventory catalogue.
            </p>
        </div>
        <div class="flex items-center gap-2.5 sm:gap-3 flex-wrap">
            <button @click="showSizeGuideModal = true" type="button" class="flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold transition-all shadow-xs" style="background: #FDF8EE; border: 1px solid #E8DECB; color: #1E1915;" onmouseover="this.style.borderColor='#C49520';" onmouseout="this.style.borderColor='#E8DECB';">
                <svg class="w-4 h-4" style="color: #C49520;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                <span>Size Guides</span>
            </button>
            <a href="{{ route('seller.products.archives') }}" class="flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold transition-all shadow-xs" style="background: #FDF8EE; border: 1px solid #E8DECB; color: #1E1915;" onmouseover="this.style.borderColor='#C49520';" onmouseout="this.style.borderColor='#E8DECB';">
                <svg class="w-4 h-4" style="color: #766C60;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path></svg>
                <span>Archives</span>
            </a>
            <a href="{{ route('seller.products.create') }}" class="flex items-center gap-2 px-5 py-2.5 text-white rounded-xl text-xs font-bold transition-all shadow-xs" style="background: #1E1915;" onmouseover="this.style.background='#C49520';" onmouseout="this.style.background='#1E1915';">
                <svg class="w-4 h-4" style="color: #C49520;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                <span>Add Product</span>
            </a>
        </div>
    </div>

    @php
        $approvedProducts = $products->filter(fn($p) => $p->status === 'approved');
        $pendingProducts  = $products->filter(fn($p) => $p->status === 'pending');
        $draftProducts    = $products->filter(fn($p) => $p->status === 'draft');
    @endphp

    {{-- Filter Toolbar & Real-Time Search Bar --}}
    <div class="p-3.5 sm:p-4 rounded-2xl sm:rounded-3xl flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3 shadow-xs" style="background: #FFFCF7; border: 1px solid #E8DECB;">
        {{-- Status Filter Tabs (Pill System) --}}
        <div class="flex items-center gap-2 overflow-x-auto no-scrollbar pb-1 sm:pb-0">
            <button @click="activeTab = 'all'"
                :style="activeTab === 'all' ? 'background:#1E1915; color:#FFFCF7; border:1px solid #C49520;' : 'background:#FDF8EE; color:#1E1915; border:1px solid #E8DECB;'"
                class="px-4 py-2 rounded-xl text-[10px] font-extrabold uppercase tracking-widest transition-all shrink-0 flex items-center gap-1.5 cursor-pointer shadow-2xs">
                <span x-show="activeTab === 'all'" style="color:#C49520;">✓</span>
                <span>All Creations ({{ $totalCount ?? $products->total() }})</span>
            </button>
            <button @click="activeTab = 'approved'"
                :style="activeTab === 'approved' ? 'background:#1E1915; color:#FFFCF7; border:1px solid #C49520;' : 'background:#FDF8EE; color:#1E1915; border:1px solid #E8DECB;'"
                class="px-4 py-2 rounded-xl text-[10px] font-extrabold uppercase tracking-widest transition-all shrink-0 flex items-center gap-1.5 cursor-pointer shadow-2xs">
                <span x-show="activeTab === 'approved'" style="color:#C49520;">✓</span>
                <span class="w-1.5 h-1.5 rounded-full" style="background:#4A6741;"></span>
                <span>Approved ({{ $approvedCount ?? $approvedProducts->count() }})</span>
            </button>
            <button @click="activeTab = 'pending'"
                :style="activeTab === 'pending' ? 'background:#1E1915; color:#FFFCF7; border:1px solid #C49520;' : 'background:#FDF8EE; color:#1E1915; border:1px solid #E8DECB;'"
                class="px-4 py-2 rounded-xl text-[10px] font-extrabold uppercase tracking-widest transition-all shrink-0 flex items-center gap-1.5 cursor-pointer shadow-2xs">
                <span x-show="activeTab === 'pending'" style="color:#C49520;">✓</span>
                <span class="w-1.5 h-1.5 rounded-full" style="background:#C49520;"></span>
                <span>Pending Review ({{ $pendingCount ?? $pendingProducts->count() }})</span>
            </button>
            <button @click="activeTab = 'drafts'"
                :style="activeTab === 'drafts' ? 'background:#1E1915; color:#FFFCF7; border:1px solid #C49520;' : 'background:#FDF8EE; color:#1E1915; border:1px solid #E8DECB;'"
                class="px-4 py-2 rounded-xl text-[10px] font-extrabold uppercase tracking-widest transition-all shrink-0 flex items-center gap-1.5 cursor-pointer shadow-2xs">
                <span x-show="activeTab === 'drafts'" style="color:#C49520;">✓</span>
                <span class="w-1.5 h-1.5 rounded-full" style="background:#64748B;"></span>
                <span>Drafts ({{ $draftCount ?? $draftProducts->count() }})</span>
            </button>
        </div>

        {{-- Live Search Input --}}
        <div class="relative w-full sm:w-72">
            <input type="text"
                x-model="search"
                placeholder="Search creations..."
                class="w-full pl-9 pr-8 py-2 rounded-xl text-xs font-semibold outline-none transition-all"
                style="background: #FDF8EE; border: 1px solid #E8DECB; color: #1E1915;"
                onfocus="this.style.borderColor='#C49520'; this.style.background='#FFF';"
                onblur="this.style.borderColor='#E8DECB'; this.style.background='#FDF8EE';">
            <svg class="w-4 h-4 absolute left-3 top-2.5" style="color: #766C60;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
            </svg>
            <button x-show="search" @click="search = ''" class="absolute right-2.5 top-2 text-xs font-bold transition-colors" style="color: #766C60;" onmouseover="this.style.color='#C49520';" onmouseout="this.style.color='#766C60';">✕</button>
        </div>
    </div>

    {{-- SECTION 1: DRAFTS --}}
    <div x-show="activeTab === 'all' || activeTab === 'drafts'" class="space-y-5">
        @if($draftProducts->isNotEmpty())
            <div class="flex items-center gap-2 pb-2.5 border-b" style="border-color: #E8DECB;">
                <span class="w-2 h-2 rounded-full" style="background: #64748B;"></span>
                <h2 class="text-xs font-black uppercase tracking-widest flex items-center gap-2" style="color: #475569;">
                    <span>Unpublished Drafts</span>
                    <span class="px-2 py-0.5 text-[9px] font-bold rounded-md" style="background: #F1F5F9; border: 1px solid #CBD5E1; color: #475569;">{{ $draftProducts->count() }} drafts saved</span>
                </h2>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-3 sm:gap-5">
                @foreach($draftProducts as $product)
                    <div x-show="matches('{{ addslashes($product->name) }}', '{{ addslashes($product->description ?? '') }}', '{{ $product->status }}')"
                        class="group rounded-2xl sm:rounded-3xl shadow-xs transition-all duration-300 overflow-hidden flex flex-col"
                        style="background: #FFFCF7; border: 1px dashed #CBD5E1;"
                        onmouseover="this.style.borderColor='#C49520'; this.style.boxShadow='0 8px 24px rgba(30,25,21,0.06)';"
                        onmouseout="this.style.borderColor='#CBD5E1'; this.style.boxShadow='0 1px 3px rgba(0,0,0,0.02)';">
                        
                        <!-- Image Section -->
                        <div class="relative aspect-4/5 sm:aspect-3/4 overflow-hidden" style="background: #FDF8EE;">
                            <img src="{{ $product->getImageUrl() }}" onerror="this.src='/uploads/products/default.jpg'" class="w-full h-full object-cover object-top group-hover:scale-105 transition-transform duration-700">
                            
                            <div class="absolute top-2.5 left-2.5 sm:top-3.5 sm:left-3.5">
                                <span class="px-2 py-0.5 sm:px-2.5 sm:py-1 rounded-full text-[8px] sm:text-[9px] font-black uppercase tracking-wider shadow-xs" style="background: #F1F5F9; color: #475569; border: 1px solid #CBD5E1;">
                                    📝 Draft
                                </span>
                            </div>

                            {{-- Desktop Hover Action Buttons (Edit + Delete) --}}
                            <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-3 backdrop-blur-xs">
                                <a href="/seller/products/{{ $product->id }}/edit" title="Resume Editing Draft" class="w-10 h-10 bg-white rounded-full flex items-center justify-center text-stone-900 transition-all shadow-xl" onmouseover="this.style.background='#C49520'; this.style.color='#FFF';" onmouseout="this.style.background='#FFF'; this.style.color='#1E1915';">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                </a>
                                <button type="button" @click="openDeleteModal('{{ $product->id }}', '{{ addslashes($product->name) }}')" title="Discard Draft" class="w-10 h-10 bg-white rounded-full flex items-center justify-center text-red-600 transition-all shadow-xl cursor-pointer" onmouseover="this.style.background='#DC2626'; this.style.color='#FFF';" onmouseout="this.style.background='#FFF'; this.style.color='#DC2626';">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-4v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                </button>
                            </div>
                        </div>

                        <div class="p-3.5 sm:p-5 space-y-2 sm:space-y-3 flex-1 flex flex-col">
                            <div class="flex-1">
                                <h3 class="font-serif text-xs sm:text-sm font-bold line-clamp-1 tracking-tight" style="color: #1E1915;">{{ $product->name }}</h3>
                                <p class="text-[9px] sm:text-[10px] mt-0.5 line-clamp-2 leading-relaxed" style="color: #766C60;">{{ $product->description ?: 'No description provided yet.' }}</p>
                            </div>
                            
                            <div class="flex items-center justify-between pt-2 border-t" style="border-color: #E8DECB;">
                                <div>
                                    <div class="text-[8px] sm:text-[9px] font-bold uppercase tracking-widest" style="color: #766C60;">Price</div>
                                    <div class="text-xs sm:text-sm font-black font-sans" style="color: #1E1915;">₱{{ number_format($product->price) }}</div>
                                </div>
                                <div class="text-right">
                                    <div class="text-[8px] sm:text-[9px] font-bold uppercase tracking-widest" style="color: #766C60;">Stock</div>
                                    <div class="text-xs sm:text-sm font-black font-sans" style="color: #475569;">{{ $product->stock }}</div>
                                </div>
                            </div>

                            <!-- Action Button to Resume Draft -->
                            <div class="pt-2 border-t" style="border-color: #E8DECB;">
                                <a href="/seller/products/{{ $product->id }}/edit" class="w-full py-2 rounded-xl text-[10px] font-bold uppercase tracking-wider text-center flex items-center justify-center gap-1.5 transition-all shadow-2xs" style="background: #1E1915; color: #FFFCF7;" onmouseover="this.style.background='#C49520';" onmouseout="this.style.background='#1E1915';">
                                    <svg class="w-3.5 h-3.5" style="color: #C49520;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                    <span>Resume Editing</span>
                                </a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @elseif($activeTab === 'drafts')
            <div class="py-16 text-center rounded-3xl" style="background: #FFFCF7; border: 2px dashed #E8DECB;">
                <div class="w-12 h-12 rounded-2xl mx-auto mb-3 flex items-center justify-center text-lg" style="background: #FDF8EE; color: #766C60; border: 1px solid #E8DECB;">
                    📝
                </div>
                <h3 class="font-serif text-base font-bold mb-1" style="color: #1E1915;">No saved drafts found</h3>
                <p class="text-xs max-w-xs mx-auto mb-4" style="color: #766C60;">When you choose "Save as Draft" during product creation, it will appear here.</p>
                <a href="{{ route('seller.products.create') }}" class="inline-flex items-center gap-2 px-5 py-2.5 text-white rounded-xl text-xs font-bold transition-all shadow-xs" style="background: #1E1915;" onmouseover="this.style.background='#C49520';" onmouseout="this.style.background='#1E1915';">
                    <span>Create New Product</span>
                </a>
            </div>
        @endif
    </div>

    {{-- SECTION 2: PENDING PRODUCTS --}}
    <div x-show="activeTab === 'all' || activeTab === 'pending'" class="space-y-5">
        @if($pendingProducts->isNotEmpty())
            <div class="flex items-center gap-2 pb-2.5 border-b" style="border-color: #E8DECB;">
                <span class="w-2 h-2 rounded-full animate-pulse" style="background: #C49520;"></span>
                <h2 class="text-xs font-black uppercase tracking-widest flex items-center gap-2" style="color: #A16D19;">
                    <span>Pending Administrative Review</span>
                    <span class="px-2 py-0.5 text-[9px] font-bold rounded-md" style="background: #FDF8EE; border: 1px solid #E8DECB; color: #A16D19;">{{ $pendingProducts->count() }} awaiting review</span>
                </h2>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-3 sm:gap-5">
                @foreach($pendingProducts as $product)
                    <div x-show="matches('{{ addslashes($product->name) }}', '{{ addslashes($product->description ?? '') }}', '{{ $product->status }}')"
                        class="group rounded-2xl sm:rounded-3xl shadow-xs transition-all duration-300 overflow-hidden flex flex-col"
                        style="background: #FFFCF7; border: 1px solid #E8DECB;"
                        onmouseover="this.style.borderColor='#C49520'; this.style.boxShadow='0 8px 24px rgba(30,25,21,0.06)';"
                        onmouseout="this.style.borderColor='#E8DECB'; this.style.boxShadow='0 1px 3px rgba(0,0,0,0.02)';">
                        
                        <!-- Image Section -->
                        <div class="relative aspect-4/5 sm:aspect-3/4 overflow-hidden" style="background: #FDF8EE;">
                            <img src="{{ $product->getImageUrl() }}" onerror="this.src='/uploads/products/default.jpg'" class="w-full h-full object-cover object-top group-hover:scale-105 transition-transform duration-700">
                            
                            <div class="absolute top-2.5 left-2.5 sm:top-3.5 sm:left-3.5">
                                <span class="px-2 py-0.5 sm:px-2.5 sm:py-1 rounded-full text-[8px] sm:text-[9px] font-black uppercase tracking-wider shadow-xs" style="background: #FDF8EE; color: #A16D19; border: 1px solid #E8DECB;">
                                    ⏳ Pending
                                </span>
                            </div>

                            @if($product->is_on_sale && $product->discount_percentage > 0)
                                <div class="absolute top-2.5 right-2.5 sm:top-3.5 sm:right-3.5 flex flex-col items-end gap-1">
                                    <span class="px-1.5 py-0.5 text-white text-[8px] font-black uppercase tracking-widest rounded-md shadow-xs" style="background: #1E1915; border: 1px solid #C49520;">
                                        -{{ number_format($product->discount_percentage, 0) }}%
                                    </span>
                                </div>
                            @endif

                            {{-- Desktop Hover Action Buttons (Edit + Delete) --}}
                            <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-3 backdrop-blur-xs">
                                <a href="/seller/products/{{ $product->id }}/edit" title="Edit Product" class="w-10 h-10 bg-white rounded-full flex items-center justify-center text-stone-900 transition-all shadow-xl" onmouseover="this.style.background='#C49520'; this.style.color='#FFF';" onmouseout="this.style.background='#FFF'; this.style.color='#1E1915';">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                </a>
                                <button type="button" @click="openDeleteModal('{{ $product->id }}', '{{ addslashes($product->name) }}')" title="Delete Product" class="w-10 h-10 bg-white rounded-full flex items-center justify-center text-red-600 transition-all shadow-xl cursor-pointer" onmouseover="this.style.background='#DC2626'; this.style.color='#FFF';" onmouseout="this.style.background='#FFF'; this.style.color='#DC2626';">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-4v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                </button>
                            </div>
                        </div>

                        <div class="p-3.5 sm:p-5 space-y-2 sm:space-y-3 flex-1 flex flex-col">
                            <div class="flex-1">
                                <h3 class="font-serif text-xs sm:text-sm font-bold line-clamp-1 tracking-tight" style="color: #1E1915;">{{ $product->name }}</h3>
                                <p class="text-[9px] sm:text-[10px] mt-0.5 line-clamp-2 leading-relaxed" style="color: #766C60;">{{ $product->description }}</p>
                                
                                {{-- Product Rating summary --}}
                                <div class="flex items-center justify-between gap-2 mt-2 pt-2 border-t" style="border-color: #E8DECB;">
                                    <div class="flex items-center gap-1.5">
                                        <span class="text-xs" style="color: #C49520;">★</span>
                                        <span class="text-[11px] font-black font-sans" style="color: #1E1915;">{{ $product->reviews_avg_rating ? number_format($product->reviews_avg_rating, 1) : '0.0' }}</span>
                                        <span class="text-[9px] font-medium font-sans" style="color: #766C60;">({{ $product->reviews_count }})</span>
                                    </div>
                                    @if($product->reviews_count > 0)
                                        <button type="button" @click="openReviewsModal('{{ $product->id }}')" class="text-[9px] font-extrabold uppercase tracking-wider hover:underline cursor-pointer" style="color: #C49520;">
                                            Reviews →
                                        </button>
                                    @endif
                                </div>
                            </div>
                            
                            <div class="flex items-center justify-between pt-2 border-t" style="border-color: #E8DECB;">
                                <div>
                                    <div class="text-[8px] sm:text-[9px] font-bold uppercase tracking-widest" style="color: #766C60;">Price</div>
                                    <div class="text-xs sm:text-sm font-black font-sans" style="color: #1E1915;">₱{{ number_format($product->price) }}</div>
                                </div>
                                <div class="text-right">
                                    <div class="text-[8px] sm:text-[9px] font-bold uppercase tracking-widest" style="color: #766C60;">Stock</div>
                                    <div class="text-xs sm:text-sm font-black font-sans" style="color: #A16D19;">{{ $product->stock }}</div>
                                </div>
                            </div>

                            <!-- Mobile Quick Edit & Delete Buttons -->
                            <div class="sm:hidden grid grid-cols-2 gap-2 pt-2 border-t" style="border-color: #E8DECB;">
                                <a href="/seller/products/{{ $product->id }}/edit" class="py-2 rounded-xl text-[9px] font-bold uppercase tracking-wider text-center flex items-center justify-center gap-1" style="background: #FDF8EE; border: 1px solid #E8DECB; color: #1E1915;">
                                    <svg class="w-3 h-3" style="color: #C49520;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                    Edit
                                </a>
                                <button type="button" @click="openDeleteModal('{{ $product->id }}', '{{ addslashes($product->name) }}')" class="w-full py-2 rounded-xl text-[9px] font-bold uppercase tracking-wider text-center flex items-center justify-center gap-1 cursor-pointer" style="background: #FEF2F2; border: 1px solid #FECACA; color: #DC2626;">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-4v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                    Delete
                                </button>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @elseif($activeTab === 'pending')
            <div class="py-16 text-center rounded-3xl" style="background: #FFFCF7; border: 2px dashed #E8DECB;">
                <div class="w-12 h-12 rounded-2xl mx-auto mb-3 flex items-center justify-center text-lg" style="background: #FDF8EE; color: #C49520; border: 1px solid #E8DECB;">
                    ⏳
                </div>
                <h3 class="font-serif text-base font-bold mb-1" style="color: #1E1915;">No creations awaiting review</h3>
                <p class="text-xs max-w-xs mx-auto mb-4" style="color: #766C60;">All your published creations have been reviewed or are active in your catalogue.</p>
                <a href="{{ route('seller.products.create') }}" class="inline-flex items-center gap-2 px-5 py-2.5 text-white rounded-xl text-xs font-bold transition-all shadow-xs" style="background: #1E1915;" onmouseover="this.style.background='#C49520';" onmouseout="this.style.background='#1E1915';">
                    <span>Add New Product</span>
                </a>
            </div>
        @endif
    </div>

    {{-- SECTION 3: APPROVED PRODUCTS --}}
    <div x-show="activeTab === 'all' || activeTab === 'approved'" class="space-y-5">
        @if($approvedProducts->isNotEmpty())
            <div class="flex items-center gap-2 pb-2.5 border-b" style="border-color: #E8DECB;">
                <span class="w-2 h-2 rounded-full" style="background: #4A6741;"></span>
                <h2 class="text-xs font-black uppercase tracking-widest flex items-center gap-2" style="color: #4A6741;">
                    <span>Active & Live Catalogue</span>
                    <span class="px-2 py-0.5 text-[9px] font-bold rounded-md" style="background: #F0F4EF; border: 1px solid #C5D9B8; color: #4A6741;">{{ $approvedProducts->count() }} items online</span>
                </h2>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-3 sm:gap-5">
                @foreach($approvedProducts as $product)
                    <div x-show="matches('{{ addslashes($product->name) }}', '{{ addslashes($product->description ?? '') }}', '{{ $product->status }}')"
                        class="group rounded-2xl sm:rounded-3xl shadow-xs transition-all duration-300 overflow-hidden flex flex-col"
                        style="background: #FFFCF7; border: 1px solid #E8DECB;"
                        onmouseover="this.style.borderColor='#C49520'; this.style.boxShadow='0 8px 24px rgba(30,25,21,0.06)';"
                        onmouseout="this.style.borderColor='#E8DECB'; this.style.boxShadow='0 1px 3px rgba(0,0,0,0.02)';">
                        
                        <!-- Image Section -->
                        <div class="relative aspect-4/5 sm:aspect-3/4 overflow-hidden" style="background: #FDF8EE;">
                            <img src="{{ $product->getImageUrl() }}" onerror="this.src='/uploads/products/default.jpg'" class="w-full h-full object-cover object-top group-hover:scale-105 transition-transform duration-700">
                            
                            <div class="absolute top-2.5 left-2.5 sm:top-3.5 sm:left-3.5">
                                <span class="px-2 py-0.5 sm:px-2.5 sm:py-1 rounded-full text-[8px] sm:text-[9px] font-black uppercase tracking-wider shadow-xs" style="background: #F0F4EF; color: #4A6741; border: 1px solid #C5D9B8;">
                                    ✓ Approved
                                </span>
                            </div>

                            @if($product->is_on_sale && $product->discount_percentage > 0)
                                <div class="absolute top-2.5 right-2.5 sm:top-3.5 sm:right-3.5 flex flex-col items-end gap-1">
                                    <span class="px-1.5 py-0.5 text-white text-[8px] font-black uppercase tracking-widest rounded-md shadow-xs" style="background: #1E1915; border: 1px solid #C49520;">
                                        -{{ number_format($product->discount_percentage, 0) }}%
                                    </span>
                                </div>
                            @endif

                            {{-- Desktop Hover Action Buttons (Edit + Delete) --}}
                            <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-3 backdrop-blur-xs">
                                <a href="/seller/products/{{ $product->id }}/edit" title="Edit Product" class="w-10 h-10 bg-white rounded-full flex items-center justify-center text-stone-900 transition-all shadow-xl" onmouseover="this.style.background='#C49520'; this.style.color='#FFF';" onmouseout="this.style.background='#FFF'; this.style.color='#1E1915';">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                </a>
                                <button type="button" @click="openDeleteModal('{{ $product->id }}', '{{ addslashes($product->name) }}')" title="Delete Product" class="w-10 h-10 bg-white rounded-full flex items-center justify-center text-red-600 transition-all shadow-xl cursor-pointer" onmouseover="this.style.background='#DC2626'; this.style.color='#FFF';" onmouseout="this.style.background='#FFF'; this.style.color='#DC2626';">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-4v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                </button>
                            </div>
                        </div>

                        <div class="p-3.5 sm:p-5 space-y-2 sm:space-y-3 flex-1 flex flex-col">
                            <div class="flex-1">
                                <h3 class="font-serif text-xs sm:text-sm font-bold line-clamp-1 tracking-tight" style="color: #1E1915;">{{ $product->name }}</h3>
                                <p class="text-[9px] sm:text-[10px] mt-0.5 line-clamp-2 leading-relaxed" style="color: #766C60;">{{ $product->description }}</p>
                                
                                {{-- Product Rating summary --}}
                                <div class="flex items-center justify-between gap-2 mt-2 pt-2 border-t" style="border-color: #E8DECB;">
                                    <div class="flex items-center gap-1.5">
                                        <span class="text-xs" style="color: #C49520;">★</span>
                                        <span class="text-[11px] font-black font-sans" style="color: #1E1915;">{{ $product->reviews_avg_rating ? number_format($product->reviews_avg_rating, 1) : '0.0' }}</span>
                                        <span class="text-[9px] font-medium font-sans" style="color: #766C60;">({{ $product->reviews_count }})</span>
                                    </div>
                                    @if($product->reviews_count > 0)
                                        <button type="button" @click="openReviewsModal('{{ $product->id }}')" class="text-[9px] font-extrabold uppercase tracking-wider hover:underline cursor-pointer" style="color: #C49520;">
                                            Reviews →
                                        </button>
                                    @endif
                                </div>
                            </div>
                            
                            <div class="flex items-center justify-between pt-2 border-t" style="border-color: #E8DECB;">
                                <div>
                                    <div class="text-[8px] sm:text-[9px] font-bold uppercase tracking-widest" style="color: #766C60;">Price</div>
                                    <div class="flex items-center gap-1">
                                        <span class="text-xs sm:text-sm font-black font-sans" style="color: #1E1915;">₱{{ number_format($product->salePrice) }}</span>
                                        @if($product->is_on_sale && $product->discount_percentage > 0)
                                            <span class="text-[9px] line-through font-sans" style="color: #A09585;">₱{{ number_format($product->price) }}</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div class="text-[8px] sm:text-[9px] font-bold uppercase tracking-widest" style="color: #766C60;">Stock</div>
                                    <div class="text-xs sm:text-sm font-black font-sans {{ $product->stock < 5 ? 'text-red-500' : 'text-stone-800' }}">{{ $product->stock }}</div>
                                </div>
                            </div>

                            <!-- Mobile Quick Edit & Delete Buttons -->
                            <div class="sm:hidden grid grid-cols-2 gap-2 pt-2 border-t" style="border-color: #E8DECB;">
                                <a href="/seller/products/{{ $product->id }}/edit" class="py-2 rounded-xl text-[9px] font-bold uppercase tracking-wider text-center flex items-center justify-center gap-1" style="background: #FDF8EE; border: 1px solid #E8DECB; color: #1E1915;">
                                    <svg class="w-3 h-3" style="color: #C49520;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                    Edit
                                </a>
                                <button type="button" @click="openDeleteModal('{{ $product->id }}', '{{ addslashes($product->name) }}')" class="w-full py-2 rounded-xl text-[9px] font-bold uppercase tracking-wider text-center flex items-center justify-center gap-1 cursor-pointer" style="background: #FEF2F2; border: 1px solid #FECACA; color: #DC2626;">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-4v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                    Delete
                                </button>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @elseif($activeTab === 'approved')
            <div class="py-16 text-center rounded-3xl" style="background: #FFFCF7; border: 2px dashed #E8DECB;">
                <div class="w-12 h-12 rounded-2xl mx-auto mb-3 flex items-center justify-center text-lg" style="background: #FDF8EE; color: #4A6741; border: 1px solid #E8DECB;">
                    ✓
                </div>
                <h3 class="font-serif text-base font-bold mb-1" style="color: #1E1915;">No approved creations yet</h3>
                <p class="text-xs max-w-xs mx-auto mb-4" style="color: #766C60;">Creations approved by administrators will appear here and in the marketplace.</p>
                <a href="{{ route('seller.products.create') }}" class="inline-flex items-center gap-2 px-5 py-2.5 text-white rounded-xl text-xs font-bold transition-all shadow-xs" style="background: #1E1915;" onmouseover="this.style.background='#C49520';" onmouseout="this.style.background='#1E1915';">
                    <span>Add New Product</span>
                </a>
            </div>
        @endif
    </div>

    {{-- GLOBAL EMPTY STATE IF NO PRODUCTS AT ALL --}}
    @if($products->isEmpty())
        <div class="py-20 text-center rounded-3xl" style="background: #FFFCF7; border: 2px dashed #E8DECB;">
            <div class="w-12 h-12 rounded-2xl mx-auto mb-3 flex items-center justify-center" style="background: #FDF8EE; color: #C49520; border: 1px solid #E8DECB;">
                ✦
            </div>
            <h3 class="font-serif text-lg font-bold mb-1" style="color: #1E1915;">Your catalogue is ready for its first creation</h3>
            <p class="text-xs max-w-xs mx-auto mb-5" style="color: #766C60;">Add your first handcrafted barong or embroidery piece to begin showcase.</p>
            <a href="{{ route('seller.products.create') }}" class="inline-flex items-center gap-2 px-5 py-2.5 text-white rounded-xl text-xs font-bold transition-all shadow-xs" style="background: #1E1915;" onmouseover="this.style.background='#C49520';" onmouseout="this.style.background='#1E1915';">
                <span>Add Your First Product</span>
            </a>
        </div>
    @endif

        {{-- Pagination (15 products per page) --}}
        @if($products->hasPages())
            <div class="pt-8 flex items-center justify-between flex-wrap gap-4 border-t mt-6" style="border-color: #E8DECB;">
                <div class="text-xs font-semibold" style="color: #766C60;">
                    Showing <span class="font-bold" style="color: #1E1915;">{{ $products->firstItem() }}</span> to <span class="font-bold" style="color: #1E1915;">{{ $products->lastItem() }}</span> of <span class="font-bold" style="color: #1E1915;">{{ $products->total() }}</span> handcrafted creations
                </div>
                <div class="flex items-center gap-1.5">
                    {{-- Previous Page Link --}}
                    @if ($products->onFirstPage())
                        <span class="px-3.5 py-2 rounded-xl text-xs font-bold opacity-40 cursor-not-allowed" style="background: #FDF8EE; border: 1px solid #E8DECB; color: #766C60;">
                            ← Previous
                        </span>
                    @else
                        <a href="{{ $products->previousPageUrl() }}" class="px-3.5 py-2 rounded-xl text-xs font-bold transition-all" style="background: #FDF8EE; border: 1px solid #E8DECB; color: #1E1915;" onmouseover="this.style.borderColor='#C49520'; this.style.color='#C49520';" onmouseout="this.style.borderColor='#E8DECB'; this.style.color='#1E1915';">
                            ← Previous
                        </a>
                    @endif

                    {{-- Pagination Elements --}}
                    @foreach ($products->getUrlRange(1, $products->lastPage()) as $page => $url)
                        @if ($page == $products->currentPage())
                            <span class="w-9 h-9 rounded-xl text-xs font-black flex items-center justify-center shadow-xs" style="background: #1E1915; color: #FFFCF7; border: 1px solid #C49520;">
                                {{ $page }}
                            </span>
                        @else
                            <a href="{{ $url }}" class="w-9 h-9 rounded-xl text-xs font-bold flex items-center justify-center transition-all" style="background: #FDF8EE; border: 1px solid #E8DECB; color: #1E1915;" onmouseover="this.style.borderColor='#C49520'; this.style.color='#C49520';" onmouseout="this.style.borderColor='#E8DECB'; this.style.color='#1E1915';">
                                {{ $page }}
                            </a>
                        @endif
                    @endforeach

                    {{-- Next Page Link --}}
                    @if ($products->hasMorePages())
                        <a href="{{ $products->nextPageUrl() }}" class="px-3.5 py-2 rounded-xl text-xs font-bold transition-all" style="background: #FDF8EE; border: 1px solid #E8DECB; color: #1E1915;" onmouseover="this.style.borderColor='#C49520'; this.style.color='#C49520';" onmouseout="this.style.borderColor='#E8DECB'; this.style.color='#1E1915';">
                            Next →
                        </a>
                    @else
                        <span class="px-3.5 py-2 rounded-xl text-xs font-bold opacity-40 cursor-not-allowed" style="background: #FDF8EE; border: 1px solid #E8DECB; color: #766C60;">
                            Next →
                        </span>
                    @endif
                </div>
            </div>
        @endif
    </div>

    {{-- Size Guide Management Modal (Artisan Luxury Theme) --}}
    <div x-show="showSizeGuideModal" style="display: none;" class="fixed inset-0 bg-black/60 backdrop-blur-xs z-50 flex items-center justify-center p-4" x-transition>
        <div @click.away="showSizeGuideModal = false" class="w-full max-w-xl rounded-3xl shadow-2xl overflow-hidden flex flex-col max-h-[90vh]" style="background: #FFFCF7; border: 1px solid #E8DECB;">
            {{-- Modal Header --}}
            <div class="px-6 py-5 border-b flex items-center justify-between shrink-0" style="background: #FDF8EE; border-color: #E8DECB;">
                <div>
                    <h2 class="font-serif text-lg font-bold tracking-wide" style="color: #1E1915;">Artisan Size Guides</h2>
                    <p class="text-[11px] font-medium" style="color: #766C60;">Standard size specifications for Men, Women, and Kids collections</p>
                </div>
                <button @click="showSizeGuideModal = false" type="button" class="w-8 h-8 rounded-xl flex items-center justify-center transition-all cursor-pointer" style="background: #FFFCF7; border: 1px solid #E8DECB; color: #766C60;">
                    ✕
                </button>
            </div>

            {{-- Modal Body --}}
            <div class="p-6 overflow-y-auto space-y-6">
                {{-- Category Pill Tabs (Men, Women, Kids) --}}
                <div class="flex gap-2 p-1.5 rounded-2xl" style="background: #FDF8EE; border: 1px solid #E8DECB;">
                    <template x-for="tab in ['Men', 'Women', 'Kids']" :key="tab">
                        <button type="button" @click="activeSGTab = tab"
                            :style="activeSGTab === tab ? 'background:#1E1915; color:#FFFCF7; border:1px solid #C49520;' : 'color:#766C60;'"
                            class="flex-1 py-2.5 rounded-xl text-xs uppercase tracking-widest transition-all text-center font-bold cursor-pointer">
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
                            <label class="cursor-pointer flex flex-col items-center justify-center border-2 border-dashed rounded-3xl p-8 transition-all text-center group" style="border-color: #E8DECB; background: #FDF8EE;" onmouseover="this.style.borderColor='#C49520';" onmouseout="this.style.borderColor='#E8DECB';">
                                <div class="w-12 h-12 rounded-2xl flex items-center justify-center text-xl mb-3 shadow-xs" style="background: #FFFCF7; border: 1px solid #E8DECB; color: #C49520;">
                                    📐
                                </div>
                                <div class="text-sm font-serif font-bold mb-1" style="color: #1E1915;">
                                    No size guide chart uploaded yet
                                </div>
                                <div class="text-xs font-extrabold uppercase tracking-wider mb-2" style="color: #C49520;">
                                    Click to upload Size Guide
                                </div>
                                <div class="text-[10px] font-medium" style="color: #766C60;">
                                    Upload size measurement image for <span class="font-bold text-stone-900" x-text="activeSGTab"></span> (PNG, JPG, WEBP • Max 5MB)
                                </div>
                                <input type="file" name="size_guide_image" accept="image/*" class="hidden" onchange="this.form.submit()">
                            </label>
                        </form>
                    </div>

                    {{-- IF THERE IS A SIZE GUIDE --}}
                    <div x-show="sizeGuides && sizeGuides[activeSGTab]" class="space-y-4">
                        <div class="rounded-2xl p-4 shadow-xs text-center relative group" style="background: #FDF8EE; border: 1px solid #E8DECB;">
                            <img :src="sizeGuides && sizeGuides[activeSGTab] && (sizeGuides[activeSGTab].startsWith('http') ? sizeGuides[activeSGTab] : ('/' + sizeGuides[activeSGTab].replace(/^\/+/, '')))"
                                 class="w-full max-h-80 object-contain rounded-xl mx-auto shadow-xs">
                        </div>

                        <div class="flex items-center gap-3 pt-2">
                            <form action="{{ route('seller.sizeguides.update') }}" method="POST" enctype="multipart/form-data" class="flex-1">
                                @csrf
                                <input type="hidden" name="target_group" :value="activeSGTab">
                                <label class="w-full py-2.5 px-4 text-white rounded-xl text-xs font-bold uppercase tracking-widest transition-all cursor-pointer flex items-center justify-center gap-2 shadow-xs" style="background: #1E1915;" onmouseover="this.style.background='#C49520';" onmouseout="this.style.background='#1E1915';">
                                    <svg class="w-4 h-4" style="color: #C49520;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003-3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                                    Update Size Guide
                                    <input type="file" name="size_guide_image" accept="image/*" class="hidden" onchange="this.form.submit()">
                                </label>
                            </form>

                            <form :action="'/seller/size-guides/' + activeSGTab" method="POST" onsubmit="return confirm('Are you sure you want to remove this size guide?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="py-2.5 px-4 text-red-600 rounded-xl text-xs font-bold uppercase tracking-widest transition-all" style="background: #FEF2F2; border: 1px solid #FECACA;">
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
             class="rounded-3xl max-w-xl w-full p-6 sm:p-8 space-y-6 shadow-2xl max-h-[85vh] flex flex-col"
             style="background: #FFFCF7; border: 1px solid #E8DECB;">
            
            {{-- Modal Header --}}
            <div class="flex items-center justify-between pb-4 border-b shrink-0" style="border-color: #E8DECB;">
                <div>
                    <div class="text-[9px] font-extrabold uppercase tracking-widest" style="color: #C49520;">Client Feedback</div>
                    <h3 class="font-serif text-lg font-bold" style="color: #1E1915;" x-text="selectedProduct ? selectedProduct.name : 'Product Reviews'"></h3>
                    <p class="text-xs mt-0.5" style="color: #766C60;" x-text="selectedProduct && selectedProduct.reviews ? (selectedProduct.reviews.length + ' client feedback entries') : '0 reviews'"></p>
                </div>
                <button @click="showReviewsModal = false" class="w-8 h-8 rounded-xl flex items-center justify-center transition-colors cursor-pointer" style="background: #FDF8EE; border: 1px solid #E8DECB; color: #766C60;">
                    ✕
                </button>
            </div>

            {{-- Reviews List --}}
            <div class="flex-1 overflow-y-auto space-y-3 pr-1">
                <template x-if="!selectedProduct || !selectedProduct.reviews || selectedProduct.reviews.length === 0">
                    <div class="py-12 text-center" style="color: #A09585;">
                        <div class="text-2xl mb-2" style="color: #C49520;">✦</div>
                        <p class="text-xs italic">No client reviews registered for this creation yet.</p>
                    </div>
                </template>

                <template x-for="rev in (selectedProduct?.reviews || [])" :key="rev.id">
                    <div class="p-4 rounded-2xl space-y-2" style="background: #FDF8EE; border: 1px solid #E8DECB;">
                        <div class="flex items-center justify-between flex-wrap gap-2">
                            <div class="flex items-center gap-2.5">
                                <div class="w-7 h-7 rounded-full font-black text-xs flex items-center justify-center uppercase" style="background: #1E1915; color: #C49520;">
                                    <span x-text="rev.customer ? rev.customer.name.charAt(0) : 'C'"></span>
                                </div>
                                <div>
                                    <div class="text-xs font-bold" style="color: #1E1915;" x-text="rev.customer ? rev.customer.name : 'Verified Customer'"></div>
                                    <div class="flex items-center text-xs" style="color: #C49520;">
                                        <template x-for="star in 5" :key="star">
                                            <span :style="star <= rev.rating ? 'color:#C49520;' : 'color:#E8DECB;'">★</span>
                                        </template>
                                    </div>
                                </div>
                            </div>
                            <span class="text-[9px] font-medium" style="color: #766C60;" x-text="rev.createdAt ? new Date(rev.createdAt).toLocaleDateString('en-PH', {month:'short', day:'numeric', year:'numeric'}) : ''"></span>
                        </div>

                        <p class="text-xs font-medium leading-relaxed" style="color: #1E1915;" x-text="rev.comment || 'No written comment provided.'"></p>

                        {{-- Images if any --}}
                        <template x-if="rev.images">
                            <div class="flex flex-wrap gap-2 pt-1">
                                <template x-for="(img, idx) in (typeof rev.images === 'string' ? JSON.parse(rev.images || '[]') : (rev.images || []))" :key="idx">
                                    <button type="button" @click="lightboxImage = (img.startsWith('http') || img.startsWith('/') ? img : '/storage/' + img)" class="w-14 h-14 rounded-xl overflow-hidden shrink-0 shadow-xs hover:opacity-80 transition-transform hover:scale-105 cursor-pointer" style="border: 1px solid #E8DECB;">
                                        <img :src="img.startsWith('http') || img.startsWith('/') ? img : '/storage/' + img" class="w-full h-full object-cover">
                                    </button>
                                </template>
                            </div>
                        </template>

                        {{-- Seller Response Section --}}
                        <div class="mt-3 pt-2.5 border-t" style="border-color: #E8DECB;">
                            {{-- Existing Seller Response --}}
                            <template x-if="rev.seller_reply && replyingToRevId !== rev.id">
                                <div class="p-3 rounded-xl border-l-3 space-y-1" style="background: #FFFCF7; border-color: #C49520;">
                                    <div class="flex items-center justify-between gap-2">
                                        <span class="text-[10px] font-black uppercase tracking-wider flex items-center gap-1.5" style="color: #C49520;">
                                            <span>💬 Artisan's Response</span>
                                            <span class="font-medium" style="color: #766C60;" x-text="rev.seller_reply_at ? '• ' + new Date(rev.seller_reply_at).toLocaleDateString('en-PH', {month:'short', day:'numeric', year:'numeric'}) : ''"></span>
                                        </span>
                                        <button type="button" @click="startReply(rev)" class="text-[9px] font-bold uppercase tracking-wider hover:underline cursor-pointer" style="color: #766C60;">
                                            Edit
                                        </button>
                                    </div>
                                    <p class="text-xs leading-relaxed" style="color: #1E1915;" x-text="rev.seller_reply"></p>
                                </div>
                            </template>

                            {{-- Reply Button --}}
                            <template x-if="!rev.seller_reply && replyingToRevId !== rev.id">
                                <div class="flex justify-end">
                                    <button type="button" @click="startReply(rev)" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-[10px] font-extrabold uppercase tracking-wider transition-all shadow-2xs cursor-pointer" style="background: #FFFCF7; border: 1px solid #E8DECB; color: #1E1915;" onmouseover="this.style.borderColor='#C49520'; this.style.color='#C49520';" onmouseout="this.style.borderColor='#E8DECB'; this.style.color='#1E1915';">
                                        <svg class="w-3.5 h-3.5" style="color: #C49520;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/></svg>
                                        <span>Respond to Customer</span>
                                    </button>
                                </div>
                            </template>

                            {{-- Inline Reply Editor --}}
                            <template x-if="replyingToRevId === rev.id">
                                <div class="p-3.5 rounded-2xl space-y-2.5" style="background: #FFFCF7; border: 1px solid #C49520;">
                                    <div class="flex items-center justify-between">
                                        <span class="text-[10px] font-black uppercase tracking-wider flex items-center gap-1" style="color: #C49520;">
                                            <span>💬</span>
                                            <span x-text="rev.seller_reply ? 'Edit Your Response' : 'Reply to ' + (rev.customer ? rev.customer.name : 'Client')"></span>
                                        </span>
                                        <span class="text-[9px] font-medium" style="color: #766C60;" x-text="(1000 - replyText.length) + ' chars left'"></span>
                                    </div>
                                    <textarea 
                                        x-model="replyText" 
                                        rows="3" 
                                        maxlength="1000" 
                                        placeholder="Write a courteous, professional reply to the customer..."
                                        class="w-full text-xs p-2.5 rounded-xl outline-hidden leading-relaxed resize-none"
                                        style="background: #FDF8EE; border: 1px solid #E8DECB; color: #1E1915;"
                                    ></textarea>
                                    <div class="flex items-center justify-end gap-2">
                                        <button type="button" @click="cancelReply()" :disabled="isSubmittingReply" class="px-3 py-1.5 text-[10px] font-bold uppercase tracking-wider rounded-lg transition-colors cursor-pointer" style="background: #FDF8EE; color: #766C60; border: 1px solid #E8DECB;">
                                            Cancel
                                        </button>
                                        <button type="button" @click="submitReply(rev.id)" :disabled="isSubmittingReply || !replyText.trim()" class="px-4 py-1.5 text-white text-[10px] font-black uppercase tracking-wider rounded-lg transition-all shadow-xs flex items-center gap-1.5 cursor-pointer disabled:opacity-50" style="background: #1E1915;" onmouseover="this.style.background='#C49520';" onmouseout="this.style.background='#1E1915';">
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
            <div class="pt-2 border-t shrink-0" style="border-color: #E8DECB;">
                <button type="button" @click="showReviewsModal = false" class="w-full py-2.5 text-white text-xs font-bold uppercase tracking-widest rounded-xl transition-all cursor-pointer" style="background: #1E1915;" onmouseover="this.style.background='#C49520';" onmouseout="this.style.background='#1E1915';">
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
    <div x-show="showDeleteModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-xs" x-cloak>
        <div class="rounded-3xl w-full max-w-md p-6 sm:p-7 shadow-2xl space-y-5" style="background: #FFFCF7; border: 1px solid #E8DECB;" @click.away="showDeleteModal = false">
            <div class="flex items-start gap-3.5">
                <div class="w-12 h-12 rounded-2xl flex items-center justify-center shrink-0" style="background: #FEF2F2; color: #DC2626; border: 1px solid #FECACA;">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                </div>
                <div class="min-w-0">
                    <h3 class="font-serif text-base sm:text-lg font-bold leading-tight" style="color: #1E1915;">Archive Creation</h3>
                    <p class="text-xs mt-1" style="color: #766C60;">Are you sure you want to remove <strong x-text="deletingProductName" class="text-stone-900"></strong> from your active catalogue?</p>
                </div>
            </div>

            <p class="text-xs leading-relaxed p-3 rounded-2xl" style="background: #FDF8EE; border: 1px solid #E8DECB; color: #766C60;">
                This product will be removed from customer display and archived in your historical ledger.
            </p>

            <form :action="'/seller/products/' + deletingProductId" method="POST" class="flex gap-3 pt-1">
                @csrf
                @method('DELETE')
                <button type="button" @click="showDeleteModal = false" class="flex-1 py-2.5 text-xs font-bold rounded-xl transition-all cursor-pointer" style="background: #FDF8EE; border: 1px solid #E8DECB; color: #1E1915;">
                    Cancel
                </button>
                <button type="submit" class="flex-1 py-2.5 bg-red-600 hover:bg-red-700 text-white text-xs font-bold uppercase tracking-wider rounded-xl transition-all border-0 cursor-pointer shadow-xs">
                    Archive Product
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
            const statusLower = (productStatus || '').toLowerCase();
            const tabLower = this.activeTab.toLowerCase();
            const matchesTab = this.activeTab === 'all' || statusLower === tabLower || (this.activeTab === 'drafts' && statusLower === 'draft');
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
