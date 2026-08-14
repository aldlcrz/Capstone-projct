@extends('layouts.admin')

@section('content')
    <div class="space-y-8" x-data="{ rejectModal: false, rejectProductId: null }">
        <div class="flex items-center justify-between">
            <div>
                <div class="text-[10px] font-bold text-[#C0422A] uppercase tracking-[0.2em] mb-1">Moderation Queue</div>
                <h1 class="text-3xl font-black text-black">Product <span class="text-[#C0420A] font-light italic">Approval</span></h1>
            </div>
            <div class="flex items-center gap-3">
                <span
                    class="px-3 py-1 bg-orange-100 text-orange-700 border border-orange-200 rounded-full text-[10px] font-black uppercase tracking-widest">
                    {{ $counts['pending'] }} Pending
                </span>
            </div>
        </div>

        @if($products->isEmpty())
            <div class="text-center py-20 bg-gray-50 rounded-3xl border border-dashed border-gray-200">
                <p class="text-gray-500 italic">No products currently awaiting moderation.</p>
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                @foreach($products as $product)
                    <div class="bg-white border border-gray-100 rounded-3xl shadow-sm overflow-hidden flex flex-col">
                        <div class="relative w-full" style="padding-top: 120%;">
                            <img src="{{ $product->getImageUrl() }}" class="absolute inset-0 w-full h-full object-cover object-top">
                            <div class="absolute top-4 left-4">
                                <span
                                    class="px-2 py-1 bg-white/90 backdrop-blur rounded text-[9px] font-bold text-black uppercase">{{ $product->category->name ?? 'N/A' }}</span>
                            </div>
                        </div>
                        <div class="p-6 flex-1 flex flex-col">
                            <div class="flex justify-between items-start mb-4">
                                <div>
                                    <h3 class="font-bold text-black">{{ $product->name }}</h3>
                                    <p class="text-xs text-gray-600 font-medium">By {{ $product->seller->name ?? 'Unknown Seller' }}</p>
                                </div>
                                <div class="text-sm font-black text-[#C0422A]">₱{{ number_format($product->price) }}</div>
                            </div>
                            <p class="text-xs text-gray-600 line-clamp-2 mb-6 font-medium">{{ $product->description }}</p>

                            <div class="mt-auto grid grid-cols-2 gap-3 pt-4 border-t border-gray-50">
                                <form action="{{ route('admin.products.approve', $product->id) }}" method="POST"
                                    onsubmit="return confirm('Are you sure you want to approve this product?')">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit"
                                        class="w-full py-2.5 bg-black text-white text-[10px] font-bold uppercase tracking-widest rounded-xl hover:bg-green-600 transition-all cursor-pointer">Approve</button>
                                </form>
                                <button @click="rejectModal = true; rejectProductId = '{{ $product->id }}'"
                                    class="w-full py-2.5 bg-white border border-gray-200 text-red-600 text-[10px] font-bold uppercase tracking-widest rounded-xl hover:bg-red-50 transition-all cursor-pointer">Reject</button>
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
                <h2 class="text-xl font-bold mb-4 text-black">Reject Product</h2>
                <form :action="'/admin/products/reject/' + rejectProductId" method="POST">
                    @csrf
                    @method('PATCH')
                    <div class="space-y-4">
                        <div>
                            <label class="text-[10px] font-bold text-gray-700 uppercase tracking-widest mb-2 block">Reason
                                for Rejection</label>
                            <textarea name="reason" rows="4"
                                class="w-full bg-gray-50 border border-gray-200 rounded-2xl p-4 text-sm text-gray-800 focus:outline-none focus:border-black"
                                placeholder="Explain why the product was rejected..." required></textarea>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <button type="button" @click="rejectModal = false"
                                class="py-3 bg-gray-100 text-gray-700 text-[10px] font-bold uppercase tracking-widest rounded-xl hover:bg-gray-200 transition-all cursor-pointer">Cancel</button>
                            <button type="submit"
                                class="py-3 bg-red-600 text-white text-[10px] font-bold uppercase tracking-widest rounded-xl shadow-lg shadow-red-500/20 hover:bg-red-700 transition-all cursor-pointer">Confirm
                                Rejection</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection