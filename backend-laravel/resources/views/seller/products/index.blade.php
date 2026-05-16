@extends('layouts.seller')

@section('content')
<div class="space-y-8">
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

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
        @forelse($products as $product)
            <div class="group bg-white rounded-3xl border border-gray-100 shadow-sm hover:shadow-xl transition-all duration-500 overflow-hidden flex flex-col">
                <!-- Image Section -->
                <div class="relative aspect-[3/4] overflow-hidden bg-gray-50">
                    @php
                        $images = is_string($product->image) ? json_decode($product->image, true) : $product->image;
                        $firstImage = is_array($images) && count($images) > 0 ? $images[0] : $product->image;
                        $imagePath = $firstImage ? (str_starts_with($firstImage, 'http') ? $firstImage : asset('uploads/products/' . $firstImage)) : asset('images/placeholder.png');
                    @endphp
                    <img src="{{ $imagePath }}" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700">
                    
                    <div class="absolute top-4 left-4">
                        <span class="px-3 py-1 rounded-full text-[9px] font-black uppercase tracking-widest {{ $product->status === 'approved' ? 'bg-green-100 text-green-600' : 'bg-amber-100 text-amber-600' }}">
                            {{ $product->status }}
                        </span>
                    </div>

                    <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-3 backdrop-blur-sm">
                        <a href="/seller/products/{{ $product->id }}/edit" class="w-12 h-12 bg-white rounded-full flex items-center justify-center text-black hover:bg-[#C0420A] hover:text-white transition-all shadow-xl">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                        </a>
                        <button class="w-12 h-12 bg-white rounded-full flex items-center justify-center text-red-500 hover:bg-red-500 hover:text-white transition-all shadow-xl">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                        </button>
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
            <div class="col-span-full py-32 text-center bg-white rounded-3xl border border-dashed border-gray-200">
                <svg class="w-16 h-16 text-gray-100 mx-auto mb-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                <h3 class="text-lg font-bold text-black mb-2">No heritage pieces listed</h3>
                <p class="text-sm text-gray-400 mb-8 max-w-xs mx-auto">Start by listing your first artisan craft to begin your digital workshop journey.</p>
                <a href="{{ route('seller.products.create') }}" class="inline-flex items-center gap-2 px-8 py-3 bg-[#C0420A] text-white rounded-xl text-[10px] font-bold uppercase tracking-widest hover:bg-black transition-all">
                    Create Listing
                </a>
            </div>
        @endforelse
    </div>
</div>
@endsection
