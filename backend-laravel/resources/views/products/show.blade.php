@extends('layouts.app')

@section('content')
<div class="max-w-[1200px] mx-auto py-8 lg:py-16">
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 lg:gap-20">
        
        <!-- Left: Image Gallery -->
        <div x-data="{ activeImage: 0, images: {{ json_encode(is_string($product->image) ? json_decode($product->image, true) : (is_array($product->image) ? $product->image : [$product->image])) }} }">
            <div class="relative aspect-4/5 bg-gray-50 rounded-2xl overflow-hidden border border-gray-100 mb-6">
                <template x-for="(img, index) in images" :key="index">
                    <img 
                        x-show="activeImage === index"
                        :src="img.startsWith('http') ? img : '/uploads/products/' + img" 
                        class="w-full h-full object-cover object-top transition-all duration-500"
                        alt="{{ $product->name }}"
                    >
                </template>
            </div>
            
            <div class="flex gap-4 overflow-x-auto pb-2 no-scrollbar">
                <template x-for="(img, index) in images" :key="index">
                    <button 
                        @click="activeImage = index"
                        class="relative w-20 h-24 rounded-xl overflow-hidden shrink-0 border-2 transition-all"
                        :class="activeImage === index ? 'border-black opacity-100' : 'border-transparent opacity-60 hover:opacity-100'"
                    >
                        <img :src="img.startsWith('http') ? img : '/uploads/products/' + img" class="w-full h-full object-cover">
                    </button>
                </template>
            </div>
        </div>

        <!-- Right: Product Details -->
        <div class="flex flex-col" x-data="{ selectedSize: '', quantity: 1, stock: {{ $product->stock ?? 10 }} }">
            <nav class="flex items-center gap-2 text-[10px] font-bold uppercase tracking-[0.2em] text-gray-400 mb-4">
                <a href="/" class="hover:text-black">Home</a>
                <span>/</span>
                <span>{{ $product->category ?? 'Heritage' }}</span>
            </nav>

            <h1 class="text-3xl md:text-5xl font-extrabold text-black mb-4 leading-tight tracking-tight">
                {{ $product->name }}
            </h1>

            <div class="flex items-center gap-4 mb-8">
                <span class="text-3xl font-black text-black">₱{{ number_format($product->price) }}</span>
                <div class="flex items-center gap-1 px-3 py-1 bg-yellow-50 rounded-full border border-yellow-100">
                    <svg class="w-3 h-3 fill-yellow-400 text-yellow-400" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                    <span class="text-xs font-bold text-yellow-700">{{ number_format($product->rating, 1) }}</span>
                </div>
            </div>

            <p class="text-gray-600 leading-relaxed mb-10 text-sm md:text-base">
                {{ $product->description }}
            </p>

            <!-- Size Selector -->
            <div class="mb-10">
                <div class="flex items-center justify-between mb-4">
                    <span class="text-xs font-bold uppercase tracking-widest text-black">Select Size</span>
                </div>
                <div class="flex flex-wrap gap-3">
                    @php
                        $sizes = is_string($product->sizes) ? json_decode($product->sizes, true) : (is_array($product->sizes) ? $product->sizes : ['S', 'M', 'L', 'XL']);
                    @endphp
                    @foreach($sizes as $size)
                        @php $sizeName = is_array($size) ? ($size['size'] ?? $size['name'] ?? 'N/A') : $size; @endphp
                        <button 
                            @click="selectedSize = '{{ $sizeName }}'"
                            class="w-14 h-14 rounded-full flex items-center justify-center text-sm font-bold border-2 transition-all"
                            :class="selectedSize === '{{ $sizeName }}' ? 'border-black bg-black text-white' : 'border-gray-200 text-gray-600 hover:border-black'"
                        >
                            {{ $sizeName }}
                        </button>
                    @endforeach
                </div>
            </div>

            <!-- Actions -->
            <div class="flex flex-col gap-4 mt-auto">
                <div class="flex gap-4">
                    <form action="/cart/add" method="POST" class="flex-1">
                        @csrf
                        <input type="hidden" name="productId" value="{{ $product->id }}">
                        <input type="hidden" name="size" :value="selectedSize">
                        <input type="hidden" name="quantity" :value="quantity">
                        <button 
                            type="submit"
                            :disabled="!selectedSize"
                            class="w-full h-16 bg-white border-2 border-black text-black rounded-2xl font-bold uppercase tracking-widest hover:bg-gray-50 transition-all disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            Add to Cart
                        </button>
                    </form>
                    
                    <form action="{{ route('wishlist.toggle') }}" method="POST">
                        @csrf
                        <input type="hidden" name="productId" value="{{ $product->id }}">
                        <button 
                            type="submit"
                            class="w-16 h-16 rounded-2xl border-2 flex items-center justify-center transition-all duration-300 {{ $isWishlisted ? 'bg-red-50 border-red-500 text-red-500' : 'bg-white border-gray-100 text-gray-400 hover:border-red-500 hover:text-red-500' }}"
                        >
                            <svg class="w-6 h-6 {{ $isWishlisted ? 'fill-current' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path></svg>
                        </button>
                    </form>
                </div>
                
                <form action="/checkout" method="GET">
                    <input type="hidden" name="productId" value="{{ $product->id }}">
                    <input type="hidden" name="size" :value="selectedSize">
                    <input type="hidden" name="quantity" :value="quantity">
                    <input type="hidden" name="direct" value="1">
                    <button 
                        type="submit"
                        :disabled="!selectedSize"
                        class="w-full h-16 bg-black text-white rounded-2xl font-bold uppercase tracking-widest hover:bg-gray-900 transition-all shadow-xl shadow-black/10 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        Buy Now
                    </button>
                </form>
            </div>

            <!-- Seller Info -->
            <div class="mt-12 p-6 bg-white rounded-2xl border border-gray-100 flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 bg-gray-50 rounded-xl flex items-center justify-center text-xl font-bold text-gray-300">
                        {{ strtoupper(substr($product->artisan ?? 'A', 0, 1)) }}
                    </div>
                    <div>
                        <div class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">Artisan</div>
                        <div class="text-sm font-bold text-black">{{ $product->artisan ?? 'Lumban Master Craft' }}</div>
                    </div>
                </div>
                <a href="/artisan/{{ $product->sellerId }}" class="text-[10px] font-bold uppercase tracking-[0.2em] text-gray-400 hover:text-black">View Shop →</a>
            </div>
        </div>
    </div>
</div>
@endsection
