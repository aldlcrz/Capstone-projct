@extends('layouts.app')

@section('content')
<div class="max-w-[1000px] mx-auto py-8">
    <div class="flex flex-col md:flex-row items-center justify-between gap-6 mb-12">
        <div>
            <div class="flex items-center gap-2 mb-2">
                <div class="w-6 h-[2px] bg-[#C0422A]"></div>
                <span class="text-[10px] font-bold uppercase tracking-widest text-[#C0422A]">Account</span>
            </div>
            <h1 class="font-serif text-3xl font-bold text-black">My <span class="text-[#C0422A] italic">Orders</span></h1>
        </div>
        
        <form action="/orders" method="GET" class="relative w-full md:w-64">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search orders..." class="w-full bg-white border border-gray-200 rounded-xl py-3 px-10 text-xs focus:outline-none focus:ring-2 focus:ring-[#C0422A]/5">
            <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
        </form>
    </div>

    <!-- Tabs -->
    <div class="flex border-b border-gray-100 mb-8 overflow-x-auto no-scrollbar gap-2">
        @foreach(['ALL', 'PENDING', 'TO SHIP', 'TO RECEIVE', 'COMPLETED', 'CANCELLED'] as $tab)
            <a href="/orders?tab={{ $tab }}" class="px-6 py-4 text-[10px] font-bold uppercase tracking-widest transition-all border-b-2 {{ (request('tab', 'ALL') == $tab) ? 'border-[#C0422A] text-[#C0422A]' : 'border-transparent text-gray-400 hover:text-black' }}">
                {{ $tab }}
            </a>
        @endforeach
    </div>

    <!-- Orders List -->
    <div class="space-y-6">
        @forelse($orders as $order)
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                <!-- Header -->
                <div class="px-6 py-4 bg-gray-50/50 border-b border-gray-100 flex items-center justify-between">
                    <div class="flex items-center gap-4 text-[10px] font-bold uppercase tracking-wider text-gray-400">
                        <span>Order #LB-OR-{{ strtoupper(substr($order->id, -8)) }}</span>
                        <span class="w-1 h-1 bg-gray-300 rounded-full"></span>
                        <span>{{ $order->createdAt->format('M d, Y') }}</span>
                    </div>
                    @php
                        $statusColors = [
                            'pending' => 'bg-yellow-50 text-yellow-600 border-yellow-100',
                            'to ship' => 'bg-blue-50 text-blue-600 border-blue-100',
                            'completed' => 'bg-green-50 text-green-600 border-green-100',
                            'cancelled' => 'bg-red-50 text-red-600 border-red-100',
                        ];
                        $statusClass = $statusColors[strtolower($order->status)] ?? 'bg-gray-50 text-gray-600 border-gray-100';
                    @endphp
                    <span class="px-3 py-1 rounded-full border text-[9px] font-black uppercase tracking-widest {{ $statusClass }}">
                        {{ $order->status }}
                    </span>
                </div>

                <!-- Items -->
                <div class="p-6 space-y-4">
                    @foreach($order->items as $item)
                        <div class="flex items-center gap-4">
                            <div class="w-16 h-20 bg-gray-50 rounded-lg overflow-hidden border border-gray-100 shrink-0">
                                @php
                                    $img = is_array($item->product->image) ? ($item->product->image[0] ?? '') : $item->product->image;
                                    $imgSrc = str_starts_with($img, 'http') ? $img : asset('uploads/products/' . $img);
                                @endphp
                                <img src="{{ $imgSrc }}" class="w-full h-full object-cover">
                            </div>
                            <div class="flex-1">
                                <h4 class="text-sm font-bold text-black mb-1">{{ $item->product->name }}</h4>
                                <div class="flex items-center gap-3 text-[10px] font-bold text-gray-400 uppercase tracking-widest">
                                    <span>Size: {{ $item->size }}</span>
                                    <span>Qty: {{ $item->quantity }}</span>
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="text-base font-bold text-black">₱{{ number_format($item->price) }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Footer -->
                <div class="px-6 py-4 border-t border-gray-50 flex items-center justify-between bg-white">
                    <div class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Total Amount</div>
                    <div class="flex items-center gap-4">
                        <div class="text-lg font-black text-[#C0422A]">₱{{ number_format($order->totalAmount) }}</div>
                        <a href="/orders/{{ $order->id }}" class="px-5 py-2 rounded-full bg-black text-white text-[9px] font-black uppercase tracking-widest hover:bg-[#C0422A] transition-all">
                            View Details
                        </a>
                    </div>
                </div>
            </div>
        @empty
            <div class="py-20 text-center">
                <div class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-4 border border-gray-100">
                    <svg class="w-8 h-8 text-gray-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg>
                </div>
                <h3 class="text-sm font-bold text-black uppercase tracking-widest">No Orders Yet</h3>
                <p class="text-xs text-gray-400 mt-1">Start your heritage collection today.</p>
            </div>
        @endforelse
    </div>
</div>
@endsection
