@extends('layouts.app')

@section('content')
<div class="max-w-5xl mx-auto px-4 sm:px-6 py-6 sm:py-10">

    {{-- Page Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6 sm:mb-10">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <div class="w-5 h-0.5 bg-[#C0422A]"></div>
                <span class="text-[10px] font-bold uppercase tracking-widest text-[#C0422A]">Account</span>
            </div>
            <h1 class="font-serif text-2xl sm:text-3xl font-bold text-black">My <span class="text-[#C0422A] italic">Orders</span></h1>
        </div>

        {{-- Search --}}
        <form action="/orders/my-orders" method="GET" class="relative w-full sm:w-64">
            <input type="text" name="search" value="{{ request('search') }}"
                   placeholder="Search orders..."
                   class="w-full bg-white border border-gray-200 rounded-xl py-2.5 sm:py-3 px-10 text-xs focus:outline-none focus:ring-2 focus:ring-[#C0422A]/10">
            <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
        </form>
    </div>

    {{-- Tabs — horizontally scrollable, no wrap --}}
    <div class="flex border-b border-gray-100 mb-6 overflow-x-auto no-scrollbar">
        @foreach(['ALL', 'PENDING', 'TO SHIP', 'TO RECEIVE', 'COMPLETED'] as $tab)
            <a href="/orders/my-orders?tab={{ $tab }}"
               class="shrink-0 whitespace-nowrap px-4 sm:px-6 py-3 text-[10px] font-bold uppercase tracking-widest transition-all border-b-2
                      {{ (request('tab', 'ALL') == $tab) ? 'border-[#C0422A] text-[#C0422A]' : 'border-transparent text-gray-400 hover:text-black' }}">
                {{ $tab }}
            </a>
        @endforeach
    </div>

    {{-- Orders List --}}
    <div class="space-y-4 sm:space-y-6">
        @forelse($orders as $order)
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">

                {{-- Card Header --}}
                <div class="px-4 sm:px-6 py-3 sm:py-4 bg-gray-50/50 border-b border-gray-100 flex flex-wrap items-center justify-between gap-2">
                    {{-- Order ID + Date --}}
                    <div class="flex flex-wrap items-center gap-1.5 sm:gap-3 text-[9px] sm:text-[10px] font-bold uppercase tracking-wider text-gray-400 min-w-0">
                        <span class="truncate max-w-35 sm:max-w-none">Order #LB-OR-{{ strtoupper(substr($order->id, -8)) }}</span>
                        <span class="w-1 h-1 bg-gray-300 rounded-full shrink-0"></span>
                        <span class="shrink-0">{{ $order->createdAt->format('M d, Y') }}</span>
                    </div>
                    {{-- Status Badge --}}
                    @php
                        $statusColors = [
                            'pending'              => 'bg-yellow-50 text-yellow-600 border-yellow-100',
                            'processing'           => 'bg-blue-50 text-blue-600 border-blue-100',
                            'to ship'              => 'bg-blue-50 text-blue-600 border-blue-100',
                            'to receive'           => 'bg-indigo-50 text-indigo-600 border-indigo-100',
                            'shipped'              => 'bg-indigo-50 text-indigo-600 border-indigo-100',
                            'delivered'            => 'bg-teal-50 text-teal-600 border-teal-100',
                            'completed'            => 'bg-green-50 text-green-600 border-green-100',
                            'cancelled'            => 'bg-red-50 text-red-600 border-red-100',
                            'cancellation pending' => 'bg-orange-50 text-orange-600 border-orange-100',
                        ];
                        $statusClass = $statusColors[strtolower($order->status)] ?? 'bg-gray-50 text-gray-600 border-gray-100';
                    @endphp
                    <span class="shrink-0 px-2.5 py-0.5 rounded-full border text-[9px] font-black uppercase tracking-widest {{ $statusClass }}">
                        {{ $order->status }}
                    </span>
                </div>

                {{-- Items --}}
                <div class="px-4 sm:px-6 py-4 space-y-4">
                    @foreach($order->items as $item)
                        <div class="flex items-center gap-3 sm:gap-4">
                            {{-- Thumbnail --}}
                            <div class="w-14 h-16 sm:w-16 sm:h-20 bg-gray-50 rounded-lg overflow-hidden border border-gray-100 shrink-0">
                                @php
                                    $imgSrc = $item->product ? $item->product->getImageUrl() : asset('uploads/products/default.jpg');
                                @endphp
                                <img src="{{ $imgSrc }}" class="w-full h-full object-cover"
                                     onerror="this.src='/uploads/products/default.jpg'"
                                     alt="{{ $item->product->name ?? 'Product' }}">
                            </div>
                            {{-- Name + Meta --}}
                            <div class="flex-1 min-w-0">
                                <h4 class="text-xs sm:text-sm font-bold text-black mb-1 truncate">{{ $item->product->name }}</h4>
                                <div class="flex items-center gap-2 text-[9px] sm:text-[10px] font-bold text-gray-400 uppercase tracking-widest">
                                    <span>Size: {{ $item->size }}</span>
                                    <span>·</span>
                                    <span>Qty: {{ $item->quantity }}</span>
                                </div>
                            </div>
                            {{-- Price --}}
                            <div class="text-right shrink-0">
                                <div class="text-sm sm:text-base font-bold text-black">₱{{ number_format($item->price) }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Card Footer --}}
                <div class="px-4 sm:px-6 py-3 sm:py-4 border-t border-gray-50 bg-white">
                    <div class="flex items-center justify-between gap-3">
                        <div class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Total Amount</div>
                        <div class="flex items-center gap-3">
                            <div class="text-base sm:text-lg font-black text-[#C0422A]">₱{{ number_format($order->totalAmount) }}</div>
                            <a href="/orders/{{ $order->id }}"
                               class="px-4 sm:px-5 py-2 rounded-full bg-black text-white text-[9px] font-black uppercase tracking-widest hover:bg-[#C0422A] transition-all whitespace-nowrap">
                                View Details
                            </a>
                        </div>
                    </div>
                </div>

            </div>
        @empty
            <div class="py-16 sm:py-20 text-center">
                <div class="w-14 h-14 sm:w-16 sm:h-16 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-4 border border-gray-100">
                    <svg class="w-7 h-7 sm:w-8 sm:h-8 text-gray-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                    </svg>
                </div>
                <h3 class="text-xs sm:text-sm font-bold text-black uppercase tracking-widest">No Orders Yet</h3>
                <p class="text-xs text-gray-400 mt-1">Start your heritage collection today.</p>
            </div>
        @endforelse
    </div>

</div>
@endsection

