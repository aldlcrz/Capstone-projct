@extends('layouts.app')

@section('content')
<div class="max-w-6xl mx-auto px-3 sm:px-6 lg:px-8 py-6 sm:py-10 space-y-6 sm:space-y-8" x-data="{ confirmModal: false, reviewModal: false, reviewProductId: null, reviewProductName: '' }">

    {{-- Back Button --}}
    <div>
        <a href="/orders/my-orders" class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-gray-200 rounded-full text-xs font-bold text-gray-600 shadow-xs hover:border-[#C0420A] hover:text-[#C0420A] transition-all">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            <span>Back to my orders</span>
        </a>
    </div>

    {{-- Order Header Banner Card --}}
    @php
        $statusColors = [
            'pending'              => 'bg-amber-50 text-amber-700 border-amber-200',
            'processing'           => 'bg-blue-50 text-blue-700 border-blue-200',
            'to ship'              => 'bg-blue-50 text-blue-700 border-blue-200',
            'to receive'           => 'bg-indigo-50 text-indigo-700 border-indigo-200',
            'shipped'              => 'bg-purple-50 text-purple-700 border-purple-200',
            'completed'            => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            'cancelled'            => 'bg-red-50 text-red-700 border-red-200',
            'cancellation pending' => 'bg-orange-50 text-orange-700 border-orange-200',
        ];
        $statusClass = $statusColors[strtolower($order->status)] ?? 'bg-gray-50 text-gray-700 border-gray-200';
    @endphp

    <div class="bg-white rounded-2xl sm:rounded-3xl p-5 sm:p-7 border border-gray-100 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="space-y-1">
            <div class="flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-[#C0420A]"></span>
                <span class="text-[9px] sm:text-[10px] font-black uppercase tracking-widest text-[#C0420A]">Order Overview</span>
            </div>
            <h1 class="font-serif text-xl sm:text-3xl font-bold text-black uppercase tracking-tight">
                #LB-OR-{{ strtoupper(substr($order->id, -8)) }}
            </h1>
            <p class="text-xs sm:text-sm text-gray-400 font-medium">Placed on {{ $order->createdAt->format('F d, Y \a\t h:i A') }}</p>
        </div>

        <div>
            <span class="inline-block px-4 sm:px-6 py-2 sm:py-2.5 rounded-full border text-[10px] sm:text-xs font-black uppercase tracking-widest {{ $statusClass }} shadow-xs">
                {{ $order->status }}
            </span>
        </div>
    </div>

    {{-- Order Progress Timeline (Mobile & Desktop Optimized) --}}
    @php
        $steps = [
            ['label' => 'Order Placed',  'status' => 'pending'],
            ['label' => 'Processing',    'status' => 'to ship'],
            ['label' => 'Shipped',       'status' => 'to receive'],
            ['label' => 'Delivered',     'status' => 'completed'],
        ];
        $statusOrder = [
            'pending'              => 0,
            'processing'           => 1,
            'to ship'              => 1,
            'to receive'           => 2,
            'shipped'              => 2,
            'delivered'            => 3,
            'completed'            => 3,
            'cancelled'            => -1,
            'cancellation pending' => -1,
        ];
        $currentStep = $statusOrder[strtolower($order->status)] ?? 0;
        $isCancelled = strtolower($order->status) === 'cancelled';
        $progressPct = $currentStep >= 3 ? 100 : round($currentStep * 33.33, 2);
    @endphp

    @if(!$isCancelled)
    <div class="bg-white rounded-2xl sm:rounded-3xl border border-gray-100 shadow-sm p-4 sm:p-8">
        <div class="flex items-center justify-between relative px-2 sm:px-6">
            {{-- Background Progress Line --}}
            <div class="absolute left-6 right-6 top-4 sm:top-5 h-1 bg-gray-100 z-0 rounded-full"></div>
            {{-- Active Progress Line --}}
            <div class="absolute left-6 top-4 sm:top-5 h-1 bg-[#C0420A] z-0 transition-all duration-700 rounded-full js-progress-bar"
                 data-progress="{{ $progressPct }}"></div>

            @foreach($steps as $i => $step)
                <div class="flex flex-col items-center gap-1.5 sm:gap-2 z-10">
                    <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-full border-2 flex items-center justify-center text-xs font-black transition-all
                        {{ $i <= $currentStep ? 'bg-[#C0420A] border-[#C0420A] text-white shadow-md shadow-[#C0420A]/20 scale-105' : 'bg-white border-gray-200 text-gray-400' }}">
                        @if($i < $currentStep)
                            <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                        @else
                            {{ $i + 1 }}
                        @endif
                    </div>
                    <span class="text-[8px] sm:text-[10px] font-black uppercase tracking-wider text-center max-w-16 sm:max-w-none
                        {{ $i <= $currentStep ? 'text-[#C0420A]' : 'text-gray-400' }}">
                        {{ $step['label'] }}
                    </span>
                </div>
            @endforeach
        </div>
    </div>
    @else
    <div class="bg-red-50/80 border border-red-100 rounded-2xl sm:rounded-3xl p-5 sm:p-6 flex items-center gap-4">
        <div class="w-10 h-10 rounded-2xl bg-red-100 flex items-center justify-center shrink-0">
            <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
        </div>
        <div>
            <div class="text-xs sm:text-sm font-black uppercase tracking-widest text-red-700">Order Cancelled</div>
            @if($order->cancellationReason)
                <p class="text-xs text-red-600 mt-0.5">Reason: {{ $order->cancellationReason }}</p>
            @endif
        </div>
    </div>
    @endif

    {{-- Main Content Grid: Left (Items & Actions) | Right (Summary Cards) --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 sm:gap-8">

        {{-- Left Column: Purchased Items List --}}
        <div class="lg:col-span-2 space-y-6">

            <div class="bg-white rounded-2xl sm:rounded-3xl border border-gray-100 shadow-sm overflow-hidden">
                <div class="px-5 sm:px-7 py-4 bg-gray-50/60 border-b border-gray-100 flex items-center justify-between">
                    <h3 class="text-[10px] sm:text-xs font-black uppercase tracking-widest text-gray-600">Items Ordered ({{ $order->items->count() }})</h3>
                    <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Heritage Pieces</span>
                </div>
                
                <div class="divide-y divide-gray-50">
                    @foreach($order->items as $item)
                        @php
                            $imgSrc = $item->product ? $item->product->getImageUrl() : asset('uploads/products/default.jpg');
                        @endphp
                        <div class="p-4 sm:p-6 flex items-center gap-3 sm:gap-5">
                            {{-- Product Image --}}
                            <div class="w-16 h-20 sm:w-20 sm:h-24 bg-gray-50 rounded-2xl overflow-hidden border border-gray-100 shrink-0">
                                <img src="{{ $imgSrc }}" class="w-full h-full object-cover object-top" onerror="this.src='/uploads/products/default.jpg'" alt="{{ $item->product->name ?? 'Product' }}">
                            </div>

                            {{-- Info --}}
                            <div class="flex-1 min-w-0 space-y-1">
                                <h4 class="text-xs sm:text-base font-bold text-black truncate">{{ $item->product->name }}</h4>
                                <div class="flex flex-wrap items-center gap-2 sm:gap-3 text-[9px] sm:text-[10px] font-bold text-gray-400 uppercase tracking-widest">
                                    @if($item->size)<span class="px-2 py-0.5 bg-gray-100 rounded-md text-gray-600">Size: {{ $item->size }}</span>@endif
                                    <span>Qty: {{ $item->quantity }}</span>
                                    <span>₱{{ number_format($item->price) }} each</span>
                                </div>

                                {{-- Leave a Review Button for Completed Orders --}}
                                @if(strtolower($order->status) === 'completed')
                                    @php
                                        $hasReview = $order->reviews->where('productId', $item->productId)->first();
                                    @endphp
                                    @if($hasReview)
                                        <div class="mt-2 flex flex-col gap-0.5">
                                            <span class="text-[9px] font-black uppercase tracking-widest text-emerald-600 flex items-center gap-1">
                                                <svg class="w-3 h-3 text-emerald-500 fill-current" viewBox="0 0 20 20">
                                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                                </svg>
                                                Reviewed ({{ $hasReview->rating }} ★)
                                            </span>
                                            <p class="text-[10px] text-gray-400 italic truncate">"{{ $hasReview->comment }}"</p>
                                        </div>
                                    @else
                                        <button @click="reviewModal = true; reviewProductId = '{{ $item->productId }}'; reviewProductName = '{{ addslashes($item->product->name) }}'"
                                            class="mt-2 inline-flex items-center gap-1 text-[10px] font-black uppercase tracking-widest text-[#C0420A] hover:underline cursor-pointer">
                                            <span>+ Leave a Review</span>
                                        </button>
                                    @endif
                                @endif
                            </div>

                            {{-- Price Subtotal --}}
                            <div class="text-right shrink-0">
                                <div class="text-xs sm:text-lg font-black text-black">₱{{ number_format($item->price * $item->quantity) }}</div>
                                <div class="text-[9px] text-gray-400 font-bold uppercase tracking-wider">subtotal</div>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Total Amount Row --}}
                <div class="px-5 sm:px-7 py-4 sm:py-5 bg-gray-50/80 border-t border-gray-100 flex items-center justify-between">
                    <span class="text-xs font-bold uppercase tracking-widest text-gray-500">Order Total Amount</span>
                    <span class="text-lg sm:text-2xl font-black text-[#C0420A]">₱{{ number_format($order->totalAmount, 2) }}</span>
                </div>
            </div>

            {{-- Confirm Received Action Button --}}
            @if(strtolower($order->status) === 'to receive' || strtolower($order->status) === 'shipped')
                <div class="bg-emerald-50/60 border border-emerald-100 rounded-2xl sm:rounded-3xl p-5 flex flex-col sm:flex-row items-center justify-between gap-4">
                    <div>
                        <h4 class="text-xs font-black uppercase tracking-wider text-emerald-800">Has your parcel arrived?</h4>
                        <p class="text-[11px] text-emerald-600 mt-0.5">Please confirm receipt once you inspect your items.</p>
                    </div>
                    <button @click="confirmModal = true"
                        class="w-full sm:w-auto px-6 py-3 rounded-full bg-emerald-600 text-white text-xs font-black uppercase tracking-widest hover:bg-emerald-700 transition-all shadow-md shadow-emerald-600/20 active:scale-95 flex items-center justify-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                        <span>Confirm Received</span>
                    </button>
                </div>
            @endif
        </div>

        {{-- Right Column: Payment, Address & Seller Summary Cards --}}
        <div class="space-y-5 sm:space-y-6">

            {{-- Payment Information Card --}}
            <div class="bg-white rounded-2xl sm:rounded-3xl border border-gray-100 shadow-sm overflow-hidden">
                <div class="px-5 sm:px-6 py-4 bg-gray-50/60 border-b border-gray-100 flex items-center justify-between">
                    <h3 class="text-[10px] sm:text-xs font-black uppercase tracking-widest text-gray-600">Payment Details</h3>
                    <span class="text-xs">💳</span>
                </div>
                <div class="p-5 sm:p-6 space-y-3.5 text-xs">
                    <div class="flex items-center justify-between">
                        <span class="font-bold text-gray-400 uppercase tracking-widest text-[9px]">Method</span>
                        <span class="font-black text-black uppercase bg-gray-100 px-2.5 py-1 rounded-md text-[11px]">{{ $order->paymentMethod ?? 'N/A' }}</span>
                    </div>

                    <div class="flex items-center justify-between">
                        <span class="font-bold text-gray-400 uppercase tracking-widest text-[9px]">Status</span>
                        @php
                            $resolvedPayment = $order->resolved_payment_status;
                            $payBadge = $resolvedPayment === 'Paid' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : ($resolvedPayment === 'Failed' ? 'bg-red-50 text-red-600 border-red-200' : 'bg-amber-50 text-amber-700 border-amber-200');
                        @endphp
                        <span class="px-2.5 py-0.5 rounded-full border text-[9px] font-black uppercase tracking-wider {{ $payBadge }}">{{ $resolvedPayment }}</span>
                    </div>

                    @if($order->paymentReference)
                    <div class="pt-3 border-t border-gray-100">
                        <span class="font-bold text-gray-400 uppercase tracking-widest text-[9px] block mb-1">Reference Number</span>
                        <span class="font-mono text-xs font-bold text-gray-700 bg-gray-50 px-2.5 py-1 rounded-md block truncate border border-gray-100">{{ $order->paymentReference }}</span>
                    </div>
                    @endif

                    @if($order->resolved_payment_status === 'Paid')
                    <div class="pt-3 border-t border-gray-100">
                        <p class="text-[10px] text-gray-500 leading-relaxed font-medium">
                            Payment confirmed. Orders on LumBarong are final sale. For updates, message the seller using
                            <span class="font-bold text-[#C0420A]">Messages</span> (bottom-right chat).
                        </p>
                    </div>
                    @endif
                </div>
            </div>

            {{-- Shipping Address Card --}}
            <div class="bg-white rounded-2xl sm:rounded-3xl border border-gray-100 shadow-sm overflow-hidden">
                <div class="px-5 sm:px-6 py-4 bg-gray-50/60 border-b border-gray-100 flex items-center justify-between">
                    <h3 class="text-[10px] sm:text-xs font-black uppercase tracking-widest text-gray-600">Ship To</h3>
                    <span class="text-xs">📍</span>
                </div>
                <div class="p-5 sm:p-6">
                    @php
                        $addr = $order->normalized_shipping_address;
                        $recipient = $addr['recipientName'] ?? $addr['fullName'] ?? $addr['name'] ?? null;
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
                    @endphp
                    @if($recipient || $streetLine || $locality)
                        @if($recipient)
                            <p class="text-sm font-black text-black mb-1">{{ $recipient }}</p>
                        @endif
                        @if($streetLine || $locality)
                            <p class="text-xs text-gray-600 leading-relaxed font-medium">
                                {{ $streetLine }}
                                @if($streetLine && $locality)<br>@endif
                                {{ $locality }}
                                @if(!empty($addr['postalCode']))
                                    <br><span class="font-mono text-gray-400 text-[10px]">ZIP {{ $addr['postalCode'] }}</span>
                                @endif
                            </p>
                        @endif
                        @if(!empty($addr['phone']))
                            <p class="text-xs font-bold text-[#C0420A] mt-2 flex items-center gap-1">
                                <span>📞</span> {{ $addr['phone'] }}
                            </p>
                        @endif
                    @else
                        <p class="text-xs text-gray-400 italic">No delivery address on file.</p>
                    @endif
                </div>
            </div>

            {{-- Sold By Seller Card --}}
            @if($order->seller)
            <a href="{{ route('shops.show', $order->seller->id) }}"
               class="block bg-white rounded-2xl sm:rounded-3xl border border-gray-100 shadow-sm overflow-hidden hover:border-[#C0420A]/40 hover:shadow-md transition-all group">
                <div class="px-5 sm:px-6 py-4 bg-gray-50/60 border-b border-gray-100 flex items-center justify-between">
                    <h3 class="text-[10px] sm:text-xs font-black uppercase tracking-widest text-gray-600">Sold By</h3>
                    <span class="text-[9px] font-black uppercase tracking-widest text-[#C0420A] group-hover:underline">
                        Visit Shop →
                    </span>
                </div>
                <div class="p-5 sm:p-6 flex items-center gap-4">
                    <div class="w-12 h-12 rounded-2xl bg-linear-to-tr from-[#3D2B1F] to-[#C0420A] text-white flex items-center justify-center font-black text-base shadow-sm shrink-0 overflow-hidden group-hover:scale-105 transition-transform">
                        @if($order->seller->profilePhoto)
                            <img src="{{ str_starts_with($order->seller->profilePhoto, 'http') ? $order->seller->profilePhoto : asset('storage/' . $order->seller->profilePhoto) }}"
                                 alt="{{ $order->seller->display_name }}"
                                 class="w-full h-full object-cover" onerror="this.style.display='none'">
                        @else
                            {{ strtoupper(substr($order->seller->display_name ?? 'S', 0, 1)) }}
                        @endif
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="text-sm font-black text-black group-hover:text-[#C0420A] transition-colors truncate">{{ $order->seller->display_name }}</div>
                        <div class="text-[10px] text-gray-400 font-bold uppercase tracking-widest mt-0.5">
                            {{ $order->seller->isVerified ? 'Verified Artisan ✓' : 'Artisan Seller' }}
                        </div>
                    </div>
                </div>
            </a>
            @endif
        </div>
    </div>

    {{-- Recommended Products Section --}}
    @if(isset($recommended) && $recommended->isNotEmpty())
    <div class="mt-12 sm:mt-16 pt-8 sm:pt-12 border-t border-gray-100">
        <div class="flex items-center justify-between mb-6">
            <div>
                <div class="flex items-center gap-2 mb-1">
                    <div class="w-4 h-0.5 bg-[#C0420A]"></div>
                    <span class="text-[10px] font-black uppercase tracking-widest text-[#C0420A]">Keep Exploring</span>
                </div>
                <h2 class="font-serif text-xl sm:text-2xl font-bold text-black">You Might Also Like</h2>
            </div>
            <a href="/" class="text-[10px] font-black text-gray-400 uppercase tracking-widest hover:text-[#C0420A] transition-colors">
                View all →
            </a>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 sm:gap-5">
            @foreach($recommended as $rec)
            <a href="/products/{{ $rec->id }}" class="group block bg-white rounded-2xl p-2.5 sm:p-3 border border-gray-100 hover:border-gray-200 shadow-xs hover:shadow-md transition-all">
                <div class="aspect-4/5 bg-gray-100 rounded-xl overflow-hidden mb-2.5 relative">
                    <img src="{{ $rec->getImageUrl() }}"
                         alt="{{ $rec->name }}"
                         class="w-full h-full object-cover object-top group-hover:scale-105 transition-transform duration-500 ease-out"
                         x-on:error="$event.target.src='/uploads/products/default.jpg'">

                    @if($rec->is_on_sale && $rec->discount_percentage > 0)
                        <div class="absolute top-2 left-2 bg-[#C0420A] text-white text-[8px] font-black uppercase tracking-widest px-2 py-0.5 rounded-full shadow-xs">
                            -{{ number_format($rec->discount_percentage, 0) }}% OFF
                        </div>
                    @endif
                </div>

                <h3 class="font-bold text-xs sm:text-sm text-black group-hover:text-[#C0420A] transition-colors leading-tight line-clamp-1 mb-1">{{ $rec->name }}</h3>

                @if($rec->avgRating)
                    <div class="flex items-center gap-1 text-[10px] font-bold text-yellow-500 mb-1">
                        <span>★</span>
                        <span>{{ number_format($rec->avgRating, 1) }}</span>
                        <span class="text-gray-400">({{ $rec->reviewCount }})</span>
                    </div>
                @endif

                <div class="flex items-center gap-2">
                    @if($rec->is_on_sale && $rec->discount_percentage > 0)
                        <p class="text-xs sm:text-sm font-black text-[#C0420A]">₱{{ number_format($rec->salePrice) }}</p>
                        <p class="text-[10px] font-bold text-gray-400 line-through">₱{{ number_format($rec->price) }}</p>
                    @else
                        <p class="text-xs sm:text-sm font-black text-black">₱{{ number_format($rec->price) }}</p>
                    @endif
                </div>
            </a>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Confirm Received Modal --}}
    <div x-show="confirmModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm" x-cloak>
        <div @click.away="confirmModal = false" class="bg-white rounded-3xl shadow-2xl w-full max-w-md p-6 sm:p-8 space-y-6">
            <div class="text-center space-y-2">
                <div class="w-14 h-14 rounded-full bg-emerald-50 flex items-center justify-center mx-auto text-2xl text-emerald-600">
                    ✓
                </div>
                <h3 class="font-serif text-lg sm:text-xl font-bold text-black">Confirm Order Received?</h3>
                <p class="text-xs text-gray-500 leading-relaxed">Please only confirm once you have physically received and inspected all items in your package.</p>
            </div>
            <form action="/orders/{{ $order->id }}/confirm" method="POST" class="flex gap-3">
                @csrf
                @method('PATCH')
                <button type="button" @click="confirmModal = false"
                    class="flex-1 py-3 rounded-full border border-gray-200 text-[10px] font-black uppercase tracking-widest text-gray-600 hover:bg-gray-50 transition-all">
                    Not Yet
                </button>
                <button type="submit"
                    class="flex-1 py-3 rounded-full bg-emerald-600 text-white text-[10px] font-black uppercase tracking-widest hover:bg-emerald-700 transition-all shadow-md shadow-emerald-600/20">
                    Confirm Received
                </button>
            </form>
        </div>
    </div>

    {{-- Leave Review Modal --}}
    <div x-show="reviewModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm" x-cloak>
        <div @click.away="reviewModal = false" class="bg-white rounded-3xl shadow-2xl w-full max-w-md p-6 sm:p-8 space-y-5">
            <div>
                <div class="text-[9px] font-black uppercase tracking-widest text-[#C0420A]">Leave a Review</div>
                <h3 class="font-serif text-lg font-bold text-black mt-0.5" x-text="reviewProductName"></h3>
            </div>
            <form action="/api/reviews" method="POST" class="space-y-4" x-data="{ rating: 0, hover: 0 }" @submit="if (rating === 0) { $event.preventDefault(); alert('Please select a rating of at least 1 star before submitting.'); }">
                @csrf
                <input type="hidden" name="productId" :value="reviewProductId">
                <input type="hidden" name="orderId" value="{{ $order->id }}">
                
                {{-- Star Rating --}}
                <div class="space-y-1.5">
                    <label class="text-[10px] font-black uppercase tracking-widest text-gray-400">Rating</label>
                    <div class="flex gap-1.5">
                        @for($i = 1; $i <= 5; $i++)
                            <button type="button"
                                @click="rating = {{ $i }}"
                                @mouseenter="hover = {{ $i }}"
                                @mouseleave="hover = 0"
                                class="text-3xl transition-transform active:scale-125">
                                <span :class="(hover || rating) >= {{ $i }} ? 'text-amber-400' : 'text-gray-200'">★</span>
                            </button>
                        @endfor
                        <input type="hidden" name="rating" :value="rating">
                    </div>
                </div>

                <div class="space-y-1.5">
                    <label class="text-[10px] font-black uppercase tracking-widest text-gray-400">Your Review</label>
                    <textarea name="comment" rows="4" required placeholder="Share your experience with this heritage piece..."
                        class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-2xl text-xs outline-none focus:border-[#C0420A] focus:bg-white transition-all resize-none"></textarea>
                </div>

                <div class="flex gap-3 pt-1">
                    <button type="button" @click="reviewModal = false"
                        class="flex-1 py-3 rounded-full border border-gray-200 text-[10px] font-black uppercase tracking-widest text-gray-500 hover:bg-gray-50 transition-all">
                        Cancel
                    </button>
                    <button type="submit"
                        class="flex-1 py-3 rounded-full bg-black text-white text-[10px] font-black uppercase tracking-widest hover:bg-[#C0420A] transition-all shadow-sm">
                        Submit Review
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.js-progress-bar').forEach(function(el) {
        const p = el.getAttribute('data-progress');
        if (p) el.style.width = p + '%';
    });
});
</script>
@endsection
