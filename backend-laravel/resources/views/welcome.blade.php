@extends('layouts.app')

@section('content')
<div class="space-y-8">
    <!-- Category Filter and Search -->
    <div class="flex flex-wrap items-center gap-3 py-2 mb-6">
        <a href="/" class="px-5 py-2.5 rounded-full border {{ !request('category') ? 'bg-black text-white border-black' : 'bg-white text-gray-600 border-gray-200 hover:border-black' }} transition-all text-sm font-bold">All</a>
        <a href="/?category=Men" class="px-5 py-2.5 rounded-full border {{ request('category') == 'Men' ? 'bg-black text-white border-black' : 'bg-white text-gray-600 border-gray-200 hover:border-black' }} transition-all text-sm font-bold">Men</a>
        <a href="/?category=Women" class="px-5 py-2.5 rounded-full border {{ request('category') == 'Women' ? 'bg-black text-white border-black' : 'bg-white text-gray-600 border-gray-200 hover:border-black' }} transition-all text-sm font-bold">Women</a>
        <a href="/?category=Kids" class="px-5 py-2.5 rounded-full border {{ request('category') == 'Kids' ? 'bg-black text-white border-black' : 'bg-white text-gray-600 border-gray-200 hover:border-black' }} transition-all text-sm font-bold">Kids</a>
        
        <div class="h-8 w-px bg-gray-200 mx-2"></div>
        
        <form action="/" method="GET" class="flex-1 min-w-[300px] relative">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search products..." class="w-full bg-white border border-gray-200 rounded-full py-2.5 px-6 focus:outline-none focus:border-black transition-all">
            <button type="submit" class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-black">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
            </button>
        </form>
    </div>

    <!-- Product Grid -->
    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-6">
        @foreach($products as $product)
            @php
                $images = json_decode($product->image);
                $firstImage = is_array($images) && count($images) > 0 ? $images[0] : 'default.jpg';
            @endphp
            <div class="group cursor-pointer">
                <div class="aspect-[4/5] bg-gray-100 rounded-2xl overflow-hidden mb-3 relative">
                    <img src="{{ str_starts_with($firstImage, 'http') ? $firstImage : asset('uploads/products/' . $firstImage) }}" alt="{{ $product->name }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                    <form action="{{ route('wishlist.toggle') }}" method="POST" class="absolute top-4 right-4">
                        @csrf
                        <input type="hidden" name="productId" value="{{ $product->id }}">
                        <button type="submit" class="w-10 h-10 bg-white rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity hover:text-red-500">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path></svg>
                        </button>
                    </form>
                    
                    <div class="absolute inset-x-0 bottom-0 p-4 opacity-0 group-hover:opacity-100 transition-opacity">
                        <form action="/cart/add" method="POST">
                            @csrf
                            <input type="hidden" name="productId" value="{{ $product->id }}">
                            <input type="hidden" name="quantity" value="1">
                            <button type="submit" class="w-full bg-black text-white py-2 rounded-xl text-xs font-bold uppercase tracking-widest hover:bg-gray-800 transition-colors">
                                Add To Cart
                            </button>
                        </form>
                    </div>
                </div>
                <a href="/products/{{ $product->id }}">
                    <h3 class="font-bold text-sm text-gray-900 group-hover:underline">{{ $product->name }}</h3>
                </a>
                <p class="text-sm text-gray-500 mt-1">₱{{ number_format($product->price) }}</p>
            </div>
        @endforeach
    </div>

    @if($products->isEmpty())
        <div class="text-center py-20 bg-gray-50 rounded-2xl border border-dashed border-gray-200">
            <p class="text-gray-500">No products found in this collection.</p>
        </div>
    @endif

    <div class="mt-8">
        {{ $products->links() }}
    </div>
</div>
@endsection
