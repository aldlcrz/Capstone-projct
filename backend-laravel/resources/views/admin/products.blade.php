@extends('layouts.admin')

@section('content')
<div class="space-y-8" x-data="{ rejectModal: false, rejectProductId: null }">
    <div class="flex items-center justify-between">
        <div>
            <div class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">Moderation Queue</div>
            <h1 class="text-3xl font-black text-black">Product Approval</h1>
        </div>
        <div class="flex items-center gap-3">
            <span class="px-3 py-1 bg-orange-100 text-orange-600 rounded-full text-[10px] font-bold uppercase tracking-widest">
                {{ $products->count() }} Pending
            </span>
        </div>
    </div>

    @if($products->isEmpty())
        <div class="text-center py-20 bg-gray-50 rounded-3xl border border-dashed border-gray-200">
            <p class="text-gray-400 italic">No products currently awaiting moderation.</p>
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            @foreach($products as $product)
                <div class="bg-white border border-gray-100 rounded-3xl shadow-sm overflow-hidden flex flex-col">
                    <div class="aspect-[4/3] relative bg-gray-50">
                        @php $images = json_decode($product->image); @endphp
                        <img src="{{ asset('uploads/products/' . ($images[0] ?? 'default.jpg')) }}" class="w-full h-full object-cover">
                        <div class="absolute top-4 left-4">
                            <span class="px-2 py-1 bg-white/90 backdrop-blur rounded text-[9px] font-bold text-black uppercase">{{ $product->category }}</span>
                        </div>
                    </div>
                    <div class="p-6 flex-1 flex flex-col">
                        <div class="flex justify-between items-start mb-4">
                            <div>
                                <h3 class="font-bold text-black">{{ $product->name }}</h3>
                                <p class="text-xs text-gray-500">By {{ $product->seller->name ?? 'Unknown Seller' }}</p>
                            </div>
                            <div class="text-sm font-black">₱{{ number_format($product->price) }}</div>
                        </div>
                        <p class="text-xs text-gray-400 line-clamp-2 mb-6">{{ $product->description }}</p>
                        
                        <div class="mt-auto grid grid-cols-2 gap-3 pt-4 border-t border-gray-50">
                            <form action="{{ route('admin.products.approve', $product->id) }}" method="POST">
                                @csrf
                                <button type="submit" class="w-full py-2.5 bg-black text-white text-[10px] font-bold uppercase tracking-widest rounded-xl hover:bg-green-600 transition-all">Approve</button>
                            </form>
                            <button @click="rejectModal = true; rejectProductId = {{ $product->id }}" class="w-full py-2.5 bg-white border border-gray-100 text-red-500 text-[10px] font-bold uppercase tracking-widest rounded-xl hover:bg-red-50 transition-all">Reject</button>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    <!-- Rejection Modal -->
    <div x-show="rejectModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" x-cloak>
        <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="rejectModal = false"></div>
        <div class="relative bg-white rounded-3xl w-full max-w-md p-8 shadow-2xl">
            <h2 class="text-xl font-bold mb-4">Reject Product</h2>
            <form :action="'/admin/products/reject/' + rejectProductId" method="POST">
                @csrf
                <div class="space-y-4">
                    <div>
                        <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2 block">Reason for Rejection</label>
                        <textarea name="reason" rows="4" class="w-full bg-gray-50 border border-gray-100 rounded-2xl p-4 text-sm focus:outline-none focus:border-black" placeholder="Explain why the product was rejected..." required></textarea>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <button type="button" @click="rejectModal = false" class="py-3 bg-gray-50 text-gray-400 text-[10px] font-bold uppercase tracking-widest rounded-xl">Cancel</button>
                        <button type="submit" class="py-3 bg-red-500 text-white text-[10px] font-bold uppercase tracking-widest rounded-xl shadow-lg shadow-red-500/20">Confirm Rejection</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
