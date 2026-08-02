@extends('layouts.seller')

@section('content')
<div class="space-y-12">
    <div class="flex items-center justify-between">
        <div>
            <div class="text-[10px] font-bold text-[#C0420A] uppercase tracking-[0.2em] mb-1">Inventory Management</div>
            <h1 class="font-serif text-3xl font-bold text-black uppercase">Product <span class="text-[#C0420A] italic lowercase">catalogue</span></h1>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('seller.products.create') }}" class="flex items-center gap-2 px-8 py-4 bg-black text-white rounded-xl text-[10px] font-bold uppercase tracking-widest hover:bg-[#C0420A] transition-all shadow-xl shadow-black/5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                List New Heritage
            </a>
        </div>
    </div>

    @php
        $discounted = $products->filter(fn($p) => $p->is_on_sale && $p->discount_percentage > 0);
        $regular = $products->reject(fn($p) => $p->is_on_sale && $p->discount_percentage > 0);
    @endphp

    {{-- Lumban Specials & Discounted Section --}}
    @if($discounted->isNotEmpty())
        <div class="space-y-6">
            <div class="flex items-center gap-3 pb-3 border-b border-[#C0420A]/10">
                <div class="w-2.5 h-2.5 rounded-full bg-[#C0420A]"></div>
                <h2 class="text-xs font-black uppercase tracking-widest text-[#C0420A] flex items-center gap-2">
                    Lumban Specials & Discounted Products
                    <span class="px-2 py-0.5 bg-[#C0420A]/10 text-[#C0420A] text-[9px] font-bold rounded-md">{{ $discounted->count() }} items</span>
                </h2>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                @foreach($discounted as $product)
                    <div class="group bg-white rounded-3xl border border-gray-100 shadow-sm hover:shadow-xl transition-all duration-500 overflow-hidden flex flex-col">
                        <!-- Image Section -->
                        <div class="relative aspect-3/4 overflow-hidden bg-gray-50">
                            <img src="{{ $product->getImageUrl() }}" class="w-full h-full object-cover object-top group-hover:scale-110 transition-transform duration-700">
                            
                            <div class="absolute top-4 left-4">
                                <span class="px-3 py-1 rounded-full text-[9px] font-black uppercase tracking-widest {{ $product->status === 'approved' ? 'bg-green-100 text-green-600' : 'bg-amber-100 text-amber-600' }}">
                                    {{ $product->status }}
                                </span>
                            </div>

                            <div class="absolute top-4 right-4 flex flex-col items-end gap-1">
                                <span class="px-2.5 py-0.5 bg-[#C0420A] text-white text-[8px] font-black uppercase tracking-widest rounded-md shadow-md">
                                    Lumban Special
                                </span>
                                <span class="px-1.5 py-0.5 bg-black text-white text-[8px] font-black rounded-md">
                                    -{{ number_format($product->discount_percentage, 0) }}% OFF
                                </span>
                            </div>

                            <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-3 backdrop-blur-sm">
                                <a href="/seller/products/{{ $product->id }}/edit" class="w-12 h-12 bg-white rounded-full flex items-center justify-center text-black hover:bg-[#C0420A] hover:text-white transition-all shadow-xl">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                </a>
                            </div>
                        </div>

                        <div class="p-6 space-y-4 flex-1 flex flex-col">
                            <div class="flex-1">
                                <h3 class="text-sm font-bold text-black line-clamp-1 uppercase tracking-tight">{{ $product->name }}</h3>
                                <p class="text-[10px] text-gray-400 mt-1 line-clamp-2 leading-relaxed">{{ $product->description }}</p>
                            </div>
                            
                            <div class="flex items-center justify-between pt-4 border-t border-gray-50">
                                <div>
                                    <div class="text-[9px] font-bold text-gray-400 uppercase tracking-widest">Price</div>
                                    <div class="flex items-center gap-1.5">
                                        <span class="text-sm font-black text-[#C0420A]">₱{{ number_format($product->salePrice) }}</span>
                                        <span class="text-[10px] text-gray-400 line-through">₱{{ number_format($product->price) }}</span>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div class="text-[9px] font-bold text-gray-400 uppercase tracking-widest">Stock</div>
                                    <div class="text-sm font-black {{ $product->stock < 5 ? 'text-red-500' : 'text-green-600' }}">{{ $product->stock }} Units</div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Standard Catalogue Section --}}
    <div class="space-y-6">
        @if($discounted->isNotEmpty())
            <div class="flex items-center gap-3 pb-3 border-b border-gray-100">
                <div class="w-2.5 h-2.5 rounded-full bg-gray-400"></div>
                <h2 class="text-xs font-black uppercase tracking-widest text-gray-400 flex items-center gap-2">
                    Standard Heritage Products
                    <span class="px-2 py-0.5 bg-gray-100 text-gray-500 text-[9px] font-bold rounded-md">{{ $regular->count() }} items</span>
                </h2>
            </div>
        @endif
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            @forelse($regular as $product)
                <div class="group bg-white rounded-3xl border border-gray-100 shadow-sm hover:shadow-xl transition-all duration-500 overflow-hidden flex flex-col">
                    <!-- Image Section -->
                    <div class="relative aspect-3/4 overflow-hidden bg-gray-50">
                        <img src="{{ $product->getImageUrl() }}" class="w-full h-full object-cover object-top group-hover:scale-110 transition-transform duration-700">
                        
                        <div class="absolute top-4 left-4">
                            <span class="px-3 py-1 rounded-full text-[9px] font-black uppercase tracking-widest {{ $product->status === 'approved' ? 'bg-green-100 text-green-600' : 'bg-amber-100 text-amber-600' }}">
                                {{ $product->status }}
                            </span>
                        </div>

                        <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-3 backdrop-blur-sm">
                            <a href="/seller/products/{{ $product->id }}/edit" class="w-12 h-12 bg-white rounded-full flex items-center justify-center text-black hover:bg-[#C0420A] hover:text-white transition-all shadow-xl">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                            </a>
                        </div>
                    </div>

                    <div class="p-6 space-y-4 flex-1 flex flex-col">
                        <div class="flex-1">
                            <h3 class="text-sm font-bold text-black line-clamp-1 uppercase tracking-tight">{{ $product->name }}</h3>
                            <p class="text-[10px] text-gray-400 mt-1 line-clamp-2 leading-relaxed">{{ $product->description }}</p>
                        </div>
                        
                        <div class="flex items-center justify-between pt-4 border-t border-gray-50">
                            <div>
                                <div class="text-[9px] font-bold text-gray-400 uppercase tracking-widest">Price</div>
                                <div class="text-sm font-black text-black">₱{{ number_format($product->price) }}</div>
                            </div>
                            <div class="text-right">
                                <div class="text-[9px] font-bold text-gray-400 uppercase tracking-widest">Stock</div>
                                <div class="text-sm font-black {{ $product->stock < 5 ? 'text-red-500' : 'text-green-600' }}">{{ $product->stock }} Units</div>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                @if($discounted->isEmpty())
                    <div class="col-span-full py-32 text-center bg-white rounded-3xl border border-dashed border-gray-200">
                        <svg class="w-16 h-16 text-gray-100 mx-auto mb-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                        <h3 class="text-lg font-bold text-black mb-2">No heritage pieces listed</h3>
                        <p class="text-sm text-gray-400 mb-8 max-w-xs mx-auto">Start by listing your first artisan craft to begin your digital workshop journey.</p>
                        <a href="{{ route('seller.products.create') }}" class="inline-flex items-center gap-2 px-8 py-3 bg-[#C0420A] text-white rounded-xl text-[10px] font-bold uppercase tracking-widest hover:bg-black transition-all">
                            Create Listing
                        </a>
                    </div>
                @endif
            @endforelse
        </div>
    </div>
</div>
@endsection
