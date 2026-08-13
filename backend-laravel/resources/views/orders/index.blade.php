@extends('layouts.app')

@section('content')
<div class="max-w-5xl mx-auto px-3 sm:px-6 py-6 sm:py-10 space-y-6 sm:space-y-8"
     x-data="{
        detailsModal: false,
        selectedOrder: null,
        getStepIndex(status) {
            const s = (status || '').toLowerCase();
            if (s === 'completed' || s === 'delivered') return 3;
            if (s === 'to receive' || s === 'shipped') return 2;
            if (s === 'to ship' || s === 'ready to ship' || s === 'ready_to_ship') return 1;
            return 0;
        },
        getStatusColor(status) {
            const s = (status || '').toLowerCase().trim();
            if (s === 'completed') return 'bg-emerald-50 text-emerald-700 border-emerald-200';
            if (s === 'delivered') return 'bg-teal-50 text-teal-700 border-teal-200';
            if (s === 'out for delivery' || s === 'out_for_delivery') return 'bg-orange-50 text-orange-700 border-orange-200';
            if (s === 'in transit' || s === 'in_transit') return 'bg-purple-50 text-purple-700 border-purple-200';
            if (s === 'shipped' || s === 'to receive') return 'bg-indigo-50 text-indigo-700 border-indigo-200';
            if (s === 'ready to ship' || s === 'ready_to_ship') return 'bg-sky-50 text-sky-700 border-sky-200';
            if (s === 'cancelled') return 'bg-red-50 text-red-700 border-red-200';
            return 'bg-amber-50 text-amber-700 border-amber-200';
        }
     }">

    {{-- Page Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="w-2 h-2 rounded-full bg-[#C0420A]"></span>
                <span class="text-[9px] sm:text-[10px] font-black uppercase tracking-widest text-[#C0420A]">Customer Account</span>
            </div>
            <h1 class="font-serif text-xl sm:text-3xl font-bold text-black uppercase tracking-tight">My <span class="text-[#C0420A] italic lowercase">Orders</span></h1>
            <p class="text-xs text-gray-500 mt-0.5">Track your barong purchases, view receipts, and manage deliveries.</p>
        </div>

        {{-- Search Input --}}
        <form action="/orders/my-orders" method="GET" class="relative w-full sm:w-72">
            <input type="text" name="search" value="{{ request('search') }}"
                   placeholder="Search order ID or item..."
                   class="w-full h-10 sm:h-11 pl-9 sm:pl-10 pr-4 bg-white border border-gray-200 rounded-full text-xs font-semibold shadow-xs outline-none focus:border-[#C0420A] transition-all">
            <svg class="w-4 h-4 text-gray-400 absolute left-3.5 top-3 sm:top-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
        </form>
    </div>

    {{-- Filter Capsule Tabs --}}
    <div class="flex gap-2 overflow-x-auto no-scrollbar pb-1">
        @foreach(['ALL' => 'All', 'PENDING' => 'Pending', 'TO SHIP' => 'To Ship', 'TO RECEIVE' => 'To Receive', 'DELIVERED' => 'Delivered', 'COMPLETED' => 'Completed', 'CANCELLED' => 'Cancelled'] as $key => $label)
            @php
                $isActive = request('tab', 'ALL') == $key;
            @endphp
            <a href="/orders/my-orders?tab={{ $key }}"
               class="shrink-0 whitespace-nowrap px-4 sm:px-5 py-2 sm:py-2.5 rounded-full text-[9px] sm:text-[10px] font-black uppercase tracking-widest transition-all flex items-center gap-1.5 active:scale-95
                      {{ $isActive ? 'bg-black text-white shadow-md shadow-black/10' : 'bg-white text-gray-500 border border-gray-200 hover:border-gray-300' }}">
                <span>{{ $label }}</span>
            </a>
        @endforeach
    </div>

    {{-- Orders List --}}
    <div class="space-y-4 sm:space-y-6">
        @forelse($orders as $order)
            @php
                $addr = $order->normalized_shipping_address;
                $recipient = $addr['recipientName'] ?? $addr['fullName'] ?? $addr['name'] ?? 'Buyer';
                $streetLine = trim(implode(' ', array_filter([
                    $addr['houseNo'] ?? '',
                    $addr['street'] ?? '',
                    $addr['address'] ?? '',
                ])));
                $locality = collect([
                    $addr['barangay'] ?? null,
                    $addr['city'] ?? null,
                    $addr['province'] ?? null,
                ])->filter()->implode(', ');

                $orderData = [
                    'id' => $order->id,
                    'shortId' => strtoupper(substr($order->id, -8)),
                    'status' => $order->status,
                    'createdAt' => $order->createdAt ? $order->createdAt->format('F d, Y \a\t h:i A') : '',
                    'totalAmount' => number_format($order->totalAmount, 2),
                    'paymentMethod' => $order->paymentMethod ?? 'COD',
                    'paymentStatus' => $order->resolved_payment_status,
                    'paymentReference' => $order->paymentReference ?? null,
                    'recipient' => $recipient,
                    'streetLine' => $streetLine,
                    'locality' => $locality,
                    'postalCode' => $addr['postalCode'] ?? '',
                    'phone' => $addr['phone'] ?? '',
                    'seller' => $order->seller ? [
                        'id' => $order->seller->id,
                        'name' => $order->seller->display_name ?? $order->seller->shopName ?? $order->seller->name ?? 'Artisan Shop',
                        'isVerified' => (bool) $order->seller->isVerified,
                        'photo' => $order->seller->profilePhoto ? (str_starts_with($order->seller->profilePhoto, 'http') ? $order->seller->profilePhoto : asset(ltrim($order->seller->profilePhoto, '/'))) : null,
                    ] : null,
                    'items' => $order->items->map(fn($item) => [
                        'id' => $item->id,
                        'name' => $item->product ? $item->product->name : 'Heritage Product',
                        'image' => $item->product ? $item->product->getImageUrl() : asset('uploads/products/default.jpg'),
                        'size' => $item->size,
                        'quantity' => $item->quantity,
                        'price' => number_format($item->price),
                        'subtotal' => number_format($item->price * $item->quantity),
                    ])->values()
                ];
            @endphp

            <div class="bg-white rounded-2xl sm:rounded-3xl border border-gray-100 shadow-sm hover:shadow-md transition-all overflow-hidden group">

                {{-- Card Header --}}
                <div class="px-4 sm:px-6 py-3.5 sm:py-4 bg-gray-50/60 border-b border-gray-100 flex flex-wrap items-center justify-between gap-2">
                    <div class="flex flex-wrap items-center gap-2 sm:gap-3 text-[9px] sm:text-[10px] font-bold uppercase tracking-wider text-gray-500 min-w-0">
                        <span class="font-black text-black">#LB-OR-{{ $orderData['shortId'] }}</span>
                        <span class="w-1 h-1 bg-gray-300 rounded-full shrink-0"></span>
                        <span class="text-gray-400">{{ $order->createdAt->format('M d, Y • g:i A') }}</span>
                    </div>

                    @php
                        $statusColors = [
                            'pending'              => 'bg-amber-50 text-amber-700 border-amber-200',
                            'to ship'              => 'bg-sky-50 text-sky-700 border-sky-200',
                            'to receive'           => 'bg-indigo-50 text-indigo-700 border-indigo-200',
                            'shipped'              => 'bg-purple-50 text-purple-700 border-purple-200',
                            'delivered'            => 'bg-teal-50 text-teal-700 border-teal-200',
                            'completed'            => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                            'cancelled'            => 'bg-red-50 text-red-700 border-red-200',
                            'cancellation pending' => 'bg-orange-50 text-orange-700 border-orange-200',
                        ];
                        $statusClass = $statusColors[strtolower($order->status)] ?? 'bg-gray-50 text-gray-600 border-gray-200';
                    @endphp
                    <span class="shrink-0 px-3 py-1 rounded-full border text-[9px] font-black uppercase tracking-widest {{ $statusClass }}">
                        {{ $order->status }}
                    </span>
                </div>

                {{-- Product Items --}}
                <div class="px-4 sm:px-6 py-4 space-y-3.5 cursor-pointer" @click="selectedOrder = {{ json_encode($orderData) }}; detailsModal = true;">
                    @foreach($order->items as $item)
                        <div class="flex items-center gap-3 sm:gap-4">
                            {{-- Thumbnail --}}
                            <div class="w-14 h-16 sm:w-16 sm:h-20 bg-gray-50 rounded-xl overflow-hidden border border-gray-100 shrink-0">
                                @php
                                    $imgSrc = $item->product ? $item->product->getImageUrl() : asset('uploads/products/default.jpg');
                                @endphp
                                <img src="{{ $imgSrc }}" class="w-full h-full object-cover object-top"
                                     onerror="this.src='/uploads/products/default.jpg'"
                                     alt="{{ $item->product->name ?? 'Product' }}">
                            </div>

                            {{-- Title & Meta --}}
                            <div class="flex-1 min-w-0 space-y-1">
                                <h4 class="text-xs sm:text-base font-bold text-black truncate group-hover:text-[#C0420A] transition-colors">{{ $item->product->name ?? 'Heritage Product' }}</h4>
                                <div class="flex flex-wrap items-center gap-2 text-[9px] sm:text-[10px] font-bold text-gray-400 uppercase tracking-widest">
                                    @if($item->size)<span class="px-2 py-0.5 bg-gray-100 rounded-md text-gray-600">Size: {{ $item->size }}</span>@endif
                                    <span>Qty: {{ $item->quantity }}</span>
                                </div>
                            </div>

                            {{-- Item Price --}}
                            <div class="text-right shrink-0">
                                <div class="text-xs sm:text-base font-black text-black">₱{{ number_format($item->price) }}</div>
                                <div class="text-[9px] text-gray-400 font-bold uppercase tracking-wider">each</div>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Card Footer --}}
                <div class="px-4 sm:px-6 py-3.5 sm:py-4 border-t border-gray-100 bg-gray-50/40">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <div class="text-[9px] font-bold text-gray-400 uppercase tracking-widest">Total Amount</div>
                            <div class="text-base sm:text-xl font-black text-[#C0420A]">₱{{ number_format($order->totalAmount, 2) }}</div>
                        </div>

                        <button type="button"
                                @click="selectedOrder = {{ json_encode($orderData) }}; detailsModal = true;"
                                class="px-5 sm:px-6 py-2.5 rounded-full bg-black text-white text-[9px] sm:text-[10px] font-black uppercase tracking-widest hover:bg-[#C0420A] transition-all whitespace-nowrap shadow-sm active:scale-95 flex items-center gap-1.5 cursor-pointer">
                            <span>View Details</span>
                            <span class="text-xs">→</span>
                        </button>
                    </div>
                </div>

            </div>
        @empty
            <div class="bg-white rounded-3xl p-10 sm:p-16 text-center border border-gray-100 shadow-sm space-y-3">
                <div class="w-14 h-14 sm:w-16 sm:h-16 bg-gray-50 text-gray-400 rounded-full flex items-center justify-center mx-auto text-2xl border border-gray-100">
                    🛍️
                </div>
                <h3 class="text-xs sm:text-sm font-black text-black uppercase tracking-widest">No Orders Found</h3>
                <p class="text-xs text-gray-400 max-w-sm mx-auto">You have not placed any orders matching this filter yet.</p>
                <div class="pt-2">
                    <a href="/" class="inline-flex items-center gap-2 px-6 py-3 rounded-full bg-[#C0420A] text-white text-xs font-black uppercase tracking-widest hover:bg-black transition-all shadow-md">
                        Explore Collection
                    </a>
                </div>
            </div>
        @endforelse
    </div>

    {{-- Interactive Order Details Modal / Bottom Sheet --}}
    <div x-show="detailsModal"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         style="display: none;"
         class="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-end sm:items-center justify-center p-0 sm:p-4">

        <div @click.away="detailsModal = false"
             class="w-full sm:max-w-2xl bg-white rounded-t-3xl sm:rounded-3xl shadow-2xl overflow-hidden flex flex-col max-h-[90vh]">

            {{-- Modal Header Banner --}}
            <div class="relative bg-linear-to-br from-[#2A2A28] to-black p-5 sm:p-6 text-white shrink-0 flex items-center justify-between">
                <div>
                    <div class="flex items-center gap-2 mb-1">
                        <span class="w-2 h-2 rounded-full bg-[#C0420A]"></span>
                        <span class="text-[9px] font-black uppercase tracking-widest text-[#C0420A]">Order Details</span>
                    </div>
                    <h2 class="text-lg sm:text-2xl font-black uppercase tracking-tight" x-text="selectedOrder ? '#LB-OR-' + selectedOrder.shortId : ''"></h2>
                    <p class="text-[11px] sm:text-xs text-gray-300 mt-0.5" x-text="selectedOrder ? selectedOrder.createdAt : ''"></p>
                </div>

                <div class="flex items-center gap-3">
                    <template x-if="selectedOrder">
                        <span class="px-3.5 py-1.5 rounded-full border text-[9px] font-black uppercase tracking-wider"
                              :class="getStatusColor(selectedOrder.status)"
                              x-text="selectedOrder.status"></span>
                    </template>

                    <button @click="detailsModal = false"
                            class="w-8 h-8 rounded-full bg-white/10 hover:bg-white/20 text-white flex items-center justify-center transition-all cursor-pointer">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
            </div>

            {{-- Modal Scrollable Body --}}
            <div class="p-5 sm:p-6 overflow-y-auto flex-1 space-y-6" x-show="selectedOrder">
                <template x-if="selectedOrder">
                    <div class="space-y-6">

                        {{-- Order Timeline Progress --}}
                        <div class="bg-gray-50/80 p-4 sm:p-6 rounded-2xl border border-gray-100" x-show="selectedOrder.status.toLowerCase() !== 'cancelled'">
                            <div class="text-[9px] font-black uppercase tracking-widest text-[#C0420A] mb-4">Delivery Progress</div>
                            <div class="flex items-center justify-between relative px-2">
                                <div class="absolute left-4 right-4 top-4 h-1 bg-gray-200 z-0 rounded-full"></div>
                                <div class="absolute left-4 top-4 h-1 bg-[#C0420A] z-0 transition-all duration-500 rounded-full"
                                     :style="'width: calc(' + (getStepIndex(selectedOrder.status) * 33.33) + '% - 8px);'"></div>

                                <template x-for="(stLabel, idx) in ['Order Placed', 'Ready to Ship', 'Shipped', 'Delivered']">
                                    <div class="flex flex-col items-center gap-1.5 z-10">
                                        <div class="w-8 h-8 rounded-full border-2 flex items-center justify-center text-[10px] font-black transition-all"
                                             :class="idx <= getStepIndex(selectedOrder.status) ? 'bg-[#C0420A] border-[#C0420A] text-white shadow-md' : 'bg-white border-gray-200 text-gray-400'">
                                            <span x-text="idx < getStepIndex(selectedOrder.status) ? '✓' : (idx + 1)"></span>
                                        </div>
                                        <span class="text-[8px] sm:text-[9px] font-bold uppercase tracking-wider text-center"
                                              :class="idx <= getStepIndex(selectedOrder.status) ? 'text-[#C0420A]' : 'text-gray-400'"
                                              x-text="stLabel"></span>
                                    </div>
                                </template>
                            </div>
                        </div>

                        {{-- Cancelled Alert --}}
                        <div class="p-4 bg-red-50 border border-red-100 rounded-2xl flex items-center gap-3 text-xs" x-show="selectedOrder.status.toLowerCase() === 'cancelled'">
                            <span class="text-xl">⚠️</span>
                            <div>
                                <div class="font-black uppercase tracking-wider text-red-700">Order Cancelled</div>
                                <p class="text-[11px] text-red-500 mt-0.5">This order has been cancelled and cannot be fulfilled.</p>
                            </div>
                        </div>

                        {{-- Purchased Items --}}
                        <div class="space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="text-[9px] font-black uppercase tracking-widest text-[#C0420A]">Items Ordered</span>
                                <span class="text-[9px] font-bold text-gray-400 uppercase tracking-widest" x-text="selectedOrder.items.length + ' item(s)'"></span>
                            </div>

                            <div class="space-y-2">
                                <template x-for="item in selectedOrder.items" :key="item.id">
                                    <div class="flex items-center gap-3 bg-white border border-gray-100 rounded-2xl p-3 shadow-xs">
                                        <div class="w-14 h-16 bg-gray-50 rounded-xl overflow-hidden shrink-0 border border-gray-100">
                                            <img :src="item.image" class="w-full h-full object-cover object-top" x-on:error="$event.target.src='/uploads/products/default.jpg'">
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <h4 class="text-xs sm:text-sm font-bold text-black truncate" x-text="item.name"></h4>
                                            <div class="flex flex-wrap gap-2 text-[9px] font-bold text-gray-400 uppercase tracking-widest mt-1">
                                                <span x-show="item.size" class="px-2 py-0.5 bg-gray-100 rounded text-gray-600" x-text="'Size: ' + item.size"></span>
                                                <span x-text="'Qty: ' + item.quantity"></span>
                                            </div>
                                        </div>
                                        <div class="text-right shrink-0">
                                            <div class="text-xs sm:text-sm font-black text-black" x-text="'₱' + item.subtotal"></div>
                                            <div class="text-[9px] text-gray-400 font-bold uppercase tracking-wider" x-text="'₱' + item.price + ' ea'"></div>
                                        </div>
                                    </div>
                                </template>
                            </div>

                            <div class="flex items-center justify-between p-4 bg-gray-50 rounded-2xl border border-gray-100">
                                <span class="text-xs font-bold text-gray-500 uppercase tracking-wider">Grand Total Amount</span>
                                <span class="text-lg font-black text-[#C0420A]" x-text="'₱' + selectedOrder.totalAmount"></span>
                            </div>
                        </div>

                        {{-- Payment & Shipping Cards Grid --}}
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            {{-- Payment Card --}}
                            <div class="bg-gray-50/80 p-4 rounded-2xl border border-gray-100 space-y-2.5 text-xs">
                                <div class="flex items-center justify-between">
                                    <span class="text-[9px] font-black uppercase tracking-widest text-[#C0420A]">Payment Info</span>
                                    <span class="px-2 py-0.5 rounded-full text-[9px] font-black uppercase tracking-wider bg-emerald-50 text-emerald-700 border border-emerald-200"
                                          x-text="selectedOrder.paymentStatus"></span>
                                </div>
                                <div class="flex items-center justify-between pt-1">
                                    <span class="text-gray-400 font-bold text-[9px] uppercase tracking-wider">Method</span>
                                    <span class="font-black text-black uppercase" x-text="selectedOrder.paymentMethod"></span>
                                </div>
                                <div class="pt-2 border-t border-gray-200/60" x-show="selectedOrder.paymentReference">
                                    <span class="text-gray-400 font-bold text-[9px] uppercase tracking-wider block mb-0.5">Reference No.</span>
                                    <span class="font-mono text-xs font-bold text-gray-700 bg-white px-2 py-1 rounded border border-gray-100 block truncate" x-text="selectedOrder.paymentReference"></span>
                                </div>
                            </div>

                            {{-- Shipping Card --}}
                            <div class="bg-gray-50/80 p-4 rounded-2xl border border-gray-100 space-y-2 text-xs">
                                <div class="text-[9px] font-black uppercase tracking-widest text-[#C0420A]">Ship To</div>
                                <div>
                                    <div class="font-black text-black" x-text="selectedOrder.recipient"></div>
                                    <p class="text-gray-600 font-medium mt-0.5 leading-relaxed text-[11px]">
                                        <span x-text="selectedOrder.streetLine"></span><br>
                                        <span x-text="selectedOrder.locality"></span>
                                        <template x-if="selectedOrder.postalCode">
                                            <span x-text="' ' + selectedOrder.postalCode"></span>
                                        </template>
                                    </p>
                                    <div class="text-[#C0420A] font-bold mt-1 text-[11px]" x-show="selectedOrder.phone" x-text="'📞 ' + selectedOrder.phone"></div>
                                </div>
                            </div>
                        </div>

                        {{-- Sold By Seller Card --}}
                        <div class="bg-gray-50/80 p-4 rounded-2xl border border-gray-100 flex items-center justify-between gap-3" x-show="selectedOrder.seller">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-linear-to-tr from-[#3D2B1F] to-[#C0420A] text-white flex items-center justify-center font-black text-sm shrink-0 overflow-hidden">
                                    <template x-if="selectedOrder.seller && selectedOrder.seller.photo">
                                        <img :src="selectedOrder.seller.photo" class="w-full h-full object-cover">
                                    </template>
                                    <template x-if="!selectedOrder.seller || !selectedOrder.seller.photo">
                                        <span x-text="selectedOrder.seller ? selectedOrder.seller.name[0].toUpperCase() : 'S'"></span>
                                    </template>
                                </div>
                                <div>
                                    <div class="text-xs font-black text-black" x-text="selectedOrder.seller ? selectedOrder.seller.name : ''"></div>
                                    <div class="text-[9px] font-bold text-gray-400 uppercase tracking-widest" x-text="selectedOrder.seller && selectedOrder.seller.isVerified ? 'Verified Artisan ✓' : 'Artisan Seller'"></div>
                                </div>
                            </div>
                            <template x-if="selectedOrder.seller">
                                <a :href="'/shops/' + selectedOrder.seller.id" class="px-3.5 py-1.5 bg-white border border-gray-200 hover:border-[#C0420A] text-[9px] font-black uppercase tracking-widest text-[#C0420A] rounded-full transition-all">
                                    Shop →
                                </a>
                            </template>
                        </div>

                    </div>
                </template>
            </div>

            {{-- Modal Footer --}}
            <div class="p-4 sm:p-5 bg-gray-50 border-t border-gray-100 flex gap-3 shrink-0">
                <button @click="detailsModal = false"
                    class="w-full py-3 sm:py-3.5 rounded-full bg-black text-white hover:bg-[#C0420A] text-[10px] font-black uppercase tracking-widest transition-all cursor-pointer shadow-sm active:scale-95">
                    Close Details
                </button>
            </div>
        </div>
    </div>

</div>
@endsection
