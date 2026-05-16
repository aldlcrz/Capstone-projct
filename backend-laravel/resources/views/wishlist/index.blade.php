@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <div class="mb-12">
        <h1 class="text-4xl font-bold text-gray-900 mb-2">My Wishlist</h1>
        <p class="text-gray-500">You have {{ $wishlistItems->count() }} items in your wishlist.</p>
    </div>

    @if($wishlistItems->isEmpty())
        <div class="text-center py-24 bg-gray-50 rounded-2xl border-2 border-dashed border-gray-200">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
            </svg>
            <h3 class="mt-4 text-lg font-medium text-gray-900">Your wishlist is empty</h3>
            <p class="mt-1 text-sm text-gray-500">Explore our collection and save your favorite items.</p>
            <div class="mt-8">
                <a href="/" class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md shadow-sm text-white bg-black hover:bg-gray-800">
                    Go Shopping
                </a>
            </div>
        </div>
    @else
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-8">
            @foreach($wishlistItems as $item)
                @php
                    $product = $item->product;
                    $images = json_decode($product->image);
                    $firstImage = is_array($images) && count($images) > 0 ? $images[0] : 'default.jpg';
                @endphp
                <div class="group bg-white rounded-xl shadow-sm hover:shadow-md transition-shadow duration-300">
                    <div class="relative aspect-[3/4] overflow-hidden rounded-t-xl bg-gray-100">
                        <img src="{{ str_starts_with($firstImage, 'http') ? $firstImage : asset('uploads/products/' . $firstImage) }}" 
                             alt="{{ $product->name }}" 
                             class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                        
                        <form action="{{ route('wishlist.toggle') }}" method="POST" class="absolute top-4 right-4">
                            @csrf
                            <input type="hidden" name="productId" value="{{ $product->id }}">
                            <button type="submit" class="p-2 bg-white rounded-full shadow-sm text-red-500 hover:bg-red-50 transition-colors">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z" clip-rule="evenodd"></path>
                                </svg>
                            </button>
                        </form>
                    </div>
                    <div class="p-5">
                        <h3 class="text-sm font-medium text-gray-900 truncate">{{ $product->name }}</h3>
                        <p class="mt-1 text-lg font-bold text-gray-900">₱{{ number_format($product->price) }}</p>
                        <div class="mt-4">
                            <form action="/cart/add" method="POST">
                                @csrf
                                <input type="hidden" name="productId" value="{{ $product->id }}">
                                <input type="hidden" name="quantity" value="1">
                                <button type="submit" class="w-full flex items-center justify-center px-4 py-2 border border-black rounded-md text-sm font-medium text-black hover:bg-black hover:text-white transition-colors">
                                    Add to Cart
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
