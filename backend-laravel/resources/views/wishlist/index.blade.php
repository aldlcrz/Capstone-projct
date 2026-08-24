@extends('layouts.app')

@section('content')
<div class="max-w-6xl mx-auto px-4 py-6 sm:py-8 lg:py-10">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <div class="w-5 h-[1.5px] bg-[#C0422A]"></div>
                <span class="text-[10px] font-bold uppercase tracking-widest text-[#C0422A]">Saved Items</span>
            </div>
            <h1 class="font-serif text-2xl sm:text-3xl font-bold text-black">My Wishlist</h1>
        </div>
        <div class="text-xs text-gray-500 font-bold">
            <span class="text-black font-extrabold">{{ $products->count() }}</span> {{ Str::plural('item', $products->count()) }} saved
        </div>
    </div>

    @if($wishlists->isNotEmpty())
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 sm:gap-6">
            @foreach($wishlists as $wishlist)
                @php $product = $wishlist->product; @endphp
                @if(!$product) @continue @endif
                <div class="bg-white rounded-2xl border border-gray-100 p-4 shadow-xs flex flex-col justify-between group transition-all hover:border-gray-200 hover:shadow-md relative" id="wishlist-card-{{ $product->id }}">
                    
                    <!-- Remove Button -->
                    <button type="button" 
                            onclick="removeWishlist('{{ $product->id }}')" 
                            class="absolute top-6 right-6 z-10 w-8 h-8 rounded-full bg-white/90 backdrop-blur-sm border border-gray-200 flex items-center justify-center text-gray-400 hover:text-red-600 hover:border-red-200 transition-all shadow-xs"
                            title="Remove from wishlist">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>

                    <div>
                        <!-- Product Image -->
                        <a href="/products/{{ $product->id }}" class="block aspect-4/5 bg-gray-50 rounded-xl overflow-hidden mb-3 relative">
                            <img src="{{ $product->getImageUrl() }}" onerror="this.src='/uploads/products/default.jpg'" class="w-full h-full object-cover object-top group-hover:scale-105 transition-transform duration-300">
                            @if($product->is_on_sale && $product->discount_percentage > 0)
                                <div class="absolute top-2.5 left-2.5 flex flex-col gap-1.5 z-10 pointer-events-none items-start">
                                    <div class="flex items-center gap-1.5 px-2.5 py-0.5 shadow-lg" style="background: linear-gradient(180deg, rgba(26, 20, 15, 0.94) 0%, rgba(12, 10, 8, 0.96) 100%); border: 1px solid rgba(229, 184, 105, 0.75); border-radius: 9px; box-shadow: 0 4px 10px rgba(0,0,0,0.6), inset 0 1px 1px rgba(255, 235, 180, 0.2);">
                                        <svg class="w-3 h-3 shrink-0" viewBox="0 0 24 24" fill="none" style="filter: drop-shadow(0 0 2px rgba(229,184,105,0.4));">
                                            <circle cx="12" cy="12" r="10" stroke="#E5B869" stroke-width="1.2" stroke-dasharray="2 1.5"/>
                                            <circle cx="12" cy="12" r="6" stroke="#E5B869" stroke-width="1"/>
                                            <path d="M12 2v4M12 18v4M2 12h4M18 12h4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83" stroke="#E5B869" stroke-width="1.2" stroke-linecap="round"/>
                                            <circle cx="12" cy="12" r="2.5" fill="#E5B869"/>
                                        </svg>
                                        <span class="font-serif text-[7.5px] font-bold uppercase tracking-[0.18em]" style="color: #EAD8B1 !important; text-shadow: 0 1px 2px rgba(0,0,0,0.9);">Lumban Special</span>
                                    </div>
                                    <div class="flex items-baseline px-2.5 py-0.5 shadow-lg w-fit" style="background: linear-gradient(180deg, rgba(26, 20, 15, 0.94) 0%, rgba(12, 10, 8, 0.96) 100%); border: 1px solid rgba(229, 184, 105, 0.75); border-radius: 9px; box-shadow: 0 4px 10px rgba(0,0,0,0.6), inset 0 1px 1px rgba(255, 235, 180, 0.2);">
                                        <span class="text-[9px] font-black tracking-tight" style="color: #F7E7C4 !important; font-family: ui-sans-serif, system-ui, -apple-system, sans-serif; text-shadow: 0 1px 3px rgba(0,0,0,0.9);">-{{ number_format($product->discount_percentage, 0) }}%</span>
                                        <span class="text-[7.5px] font-extrabold uppercase tracking-[0.12em] ml-1" style="color: #C5A059 !important; text-shadow: 0 1px 2px rgba(0,0,0,0.9);">OFF</span>
                                    </div>
                                </div>
                            @endif
                            @if($wishlist->size)
                                <div class="absolute bottom-2.5 left-2.5 bg-black/75 backdrop-blur-xs text-white text-[9px] font-bold uppercase tracking-wider px-2.5 py-0.5 rounded-lg border border-white/20">
                                    Size: {{ $wishlist->size }}
                                </div>
                            @endif
                        </a>

                        <!-- Category & Artisan -->
                        <div class="flex items-center justify-between text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">
                            <span>{{ $product->category->name ?? 'Barong Tagalog' }}</span>
                            <span class="text-amber-800 font-extrabold truncate max-w-28">by {{ $product->artisan ?? $product->seller->shopName ?? 'Artisan' }}</span>
                        </div>

                        <!-- Title -->
                        <a href="/products/{{ $product->id }}" class="font-bold text-sm text-gray-900 hover:text-[#C0422A] transition-colors leading-snug line-clamp-2 mb-2 block">
                            {{ $product->name }}
                        </a>

                        <!-- Price -->
                        <div class="flex items-baseline gap-2 mb-3">
                            <span class="text-base font-extrabold text-gray-900">₱{{ number_format($product->salePrice) }}</span>
                            @if($product->is_on_sale && $product->discount_percentage > 0)
                                <span class="text-xs font-bold text-gray-400 line-through">₱{{ number_format($product->price) }}</span>
                            @endif
                        </div>
                    </div>

                    <!-- Bottom Actions -->
                    <div class="pt-3 border-t border-gray-100 flex items-center gap-2">
                        <a href="/products/{{ $product->id }}" class="flex-1 h-9 rounded-xl bg-black hover:bg-gray-900 text-white font-bold text-xs flex items-center justify-center gap-1.5 transition-colors shadow-2xs">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            View Details
                        </a>
                    </div>

                </div>
            @endforeach
        </div>
    @else
        <!-- Empty State -->
        <div class="text-center py-16 bg-white rounded-3xl border border-gray-100 p-8 shadow-xs max-w-lg mx-auto">
            <div class="w-16 h-16 rounded-full bg-red-50 text-red-500 flex items-center justify-center mx-auto mb-4 border border-red-100">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
            </div>
            <h3 class="font-serif text-xl font-bold text-gray-900 mb-2">Your Wishlist is Empty</h3>
            <p class="text-xs text-gray-500 mb-6 leading-relaxed">Save handcrafted Barong Tagalog pieces you love while browsing to easily view or purchase them later.</p>
            <a href="/" class="inline-flex items-center justify-center px-6 py-3 rounded-xl bg-black hover:bg-gray-900 text-white font-bold text-xs uppercase tracking-wider transition-colors shadow-md">
                Explore Collection
            </a>
        </div>
    @endif
</div>

@push('scripts')
<script>
    async function removeWishlist(productId) {
        try {
            const res = await fetch('/wishlist/toggle', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ product_id: productId })
            });
            const data = await res.json();
            if (data.success) {
                const card = document.getElementById('wishlist-card-' + productId);
                if (card) {
                    card.style.opacity = '0';
                    card.style.transform = 'scale(0.95)';
                    setTimeout(() => card.remove(), 200);
                }
                if (window.Alpine && Alpine.store('toast')) {
                    Alpine.store('toast').trigger('Item removed from wishlist', 'info');
                }
            }
        } catch(e) {}
    }
</script>
@endpush
@endsection
