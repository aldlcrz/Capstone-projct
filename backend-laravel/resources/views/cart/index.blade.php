@extends('layouts.app')

@section('content')
<div class="max-w-[1200px] mx-auto px-4 py-12 min-h-screen" x-data="{
    updateQuantity(key, delta) {
        let item = this.items[key];
        if (!item) return;
        let newQty = Math.max(1, item.quantity + delta);
        if (newQty === item.quantity) return;
        
        // Optimistic UI update
        this.items[key].quantity = newQty;
        
        fetch('/cart/update', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ key, quantity: newQty })
        }).then(res => {
            if (!res.ok) location.reload();
        });
    }
}">
    <div class="flex flex-col lg:flex-row gap-8">
        <!-- Cart Items -->
        <div class="flex-1 space-y-6">
            <h1 class="text-3xl font-bold text-black mb-8">Your Cart</h1>
            
            @php $cart = session('cart', []); @endphp
            @forelse($cart as $key => $item)
                <div class="flex gap-6 bg-white p-6 rounded-2xl shadow-sm border border-gray-100 items-center">
                    <img src="{{ str_starts_with($item['image'], 'http') ? $item['image'] : asset('uploads/products/' . $item['image']) }}" class="w-24 h-32 object-cover rounded-xl bg-gray-50">
                    <div class="flex-1">
                        <h3 class="font-bold text-gray-900">{{ $item['name'] }}</h3>
                        <p class="text-red-600 font-bold mt-1">₱{{ number_format($item['price']) }}</p>
                    </div>
                    <div class="flex items-center gap-4 bg-gray-50 rounded-full px-4 py-2">
                        <form action="/cart/update" method="POST" class="inline">
                            @csrf
                            <input type="hidden" name="key" value="{{ $key }}">
                            <input type="hidden" name="quantity" value="{{ $item['quantity'] - 1 }}">
                            <button type="submit" class="text-gray-400 hover:text-black font-black">-</button>
                        </form>
                        <span class="font-bold text-sm w-4 text-center">{{ $item['quantity'] }}</span>
                        <form action="/cart/update" method="POST" class="inline">
                            @csrf
                            <input type="hidden" name="key" value="{{ $key }}">
                            <input type="hidden" name="quantity" value="{{ $item['quantity'] + 1 }}">
                            <button type="submit" class="text-gray-400 hover:text-black font-black">+</button>
                        </form>
                    </div>
                    <form action="/cart/remove" method="POST" class="ml-4">
                        @csrf
                        <input type="hidden" name="key" value="{{ $key }}">
                        <button type="submit" class="text-gray-300 hover:text-red-500 transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                        </button>
                    </form>
                </div>
            @empty
                <div class="text-center py-20 bg-gray-50 rounded-3xl border border-dashed border-gray-200">
                    <p class="text-gray-500 mb-6">Your cart is empty.</p>
                    <a href="/" class="px-8 py-3 bg-black text-white text-xs font-bold uppercase tracking-widest rounded-full hover:bg-gray-800 transition-all">Shop Collection</a>
                </div>
            @endforelse
        </div>

        <!-- Order Summary -->
        @if(!empty($cart))
        <div class="lg:w-96">
            <div class="bg-white p-8 rounded-3xl border border-gray-100 shadow-sm sticky top-8">
                <h2 class="text-xl font-bold text-black mb-6">Summary</h2>
                <div class="space-y-4">
                    <div class="flex justify-between text-gray-500">
                        <span>Subtotal</span>
                        <span class="font-bold text-black">₱{{ number_format(collect($cart)->sum(fn($i) => $i['price'] * $i['quantity'])) }}</span>
                    </div>
                    <div class="flex justify-between text-gray-500">
                        <span>Shipping</span>
                        <span class="text-green-600 font-bold">Free</span>
                    </div>
                    <div class="pt-4 border-t border-gray-100 flex justify-between items-center">
                        <span class="text-lg font-bold text-black">Total</span>
                        <span class="text-2xl font-bold text-red-600">₱{{ number_format(collect($cart)->sum(fn($i) => $i['price'] * $i['quantity'])) }}</span>
                    </div>
                    <a href="/checkout" class="block w-full bg-black text-white text-center py-4 rounded-xl text-xs font-bold uppercase tracking-widest hover:bg-gray-800 transition-all mt-8 shadow-xl">Checkout Now</a>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
