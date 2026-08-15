@extends('layouts.seller')

@section('content')
<div class="space-y-8" x-data="{
    search: '',
    activeTab: 'all',
    showSizeGuideModal: false,
    activeSGTab: 'Men',
    sizeGuides: {{ Js::from(Auth::user()->size_guides ?? []) }},
    matches(productName, productDesc, productStatus) {
        const query = this.search.toLowerCase().trim();
        const matchesSearch = !query || productName.toLowerCase().includes(query) || productDesc.toLowerCase().includes(query);
        const matchesTab = this.activeTab === 'all' || productStatus.toLowerCase() === this.activeTab.toLowerCase();
        return matchesSearch && matchesTab;
    }
}">
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
                                <form action="{{ route('seller.products.destroy', $product->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this product?');" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" title="Delete Product" class="w-10 h-10 sm:w-12 sm:h-12 bg-white rounded-full flex items-center justify-center text-red-600 hover:bg-red-600 hover:text-white transition-all shadow-xl">
                                        <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-4v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                    </button>
                                </form>
                            </div>
                        </div>

                        <div class="p-3.5 sm:p-6 space-y-2 sm:space-y-4 flex-1 flex flex-col">
                            <div class="flex-1">
                                <h3 class="text-xs sm:text-sm font-bold text-black line-clamp-1 uppercase tracking-tight">{{ $product->name }}</h3>
                                <p class="text-[9px] sm:text-[10px] text-gray-400 mt-0.5 sm:mt-1 line-clamp-2 leading-relaxed">{{ $product->description }}</p>
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
                                <form action="{{ route('seller.products.destroy', $product->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this product?');" class="w-full">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="w-full py-2 bg-red-50 text-red-600 rounded-xl text-[9px] font-black uppercase tracking-wider text-center border border-red-100 flex items-center justify-center gap-1">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-4v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                        Delete
                                    </button>
                                </form>
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
                            <form action="{{ route('seller.products.destroy', $product->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this product?');" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" title="Delete Product" class="w-10 h-10 sm:w-12 sm:h-12 bg-white rounded-full flex items-center justify-center text-red-600 hover:bg-red-600 hover:text-white transition-all shadow-xl">
                                    <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-4v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                </button>
                            </form>
                        </div>
                    </div>

                    <div class="p-3.5 sm:p-6 space-y-2 sm:space-y-4 flex-1 flex flex-col">
                        <div class="flex-1">
                            <h3 class="text-xs sm:text-sm font-bold text-black line-clamp-1 uppercase tracking-tight">{{ $product->name }}</h3>
                            <p class="text-[9px] sm:text-[10px] text-gray-400 mt-0.5 sm:mt-1 line-clamp-2 leading-relaxed">{{ $product->description }}</p>
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
                            <form action="{{ route('seller.products.destroy', $product->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this product?');" class="w-full">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="w-full py-2 bg-red-50 text-red-600 rounded-xl text-[9px] font-black uppercase tracking-wider text-center border border-red-100 flex items-center justify-center gap-1">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-4v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                    Delete
                                </button>
                            </form>
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
</div>
@endsection
