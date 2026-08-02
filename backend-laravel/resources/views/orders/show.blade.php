@extends('layouts.app')

@section('content')
<div class="max-w-[900px] mx-auto py-8" x-data="{ confirmModal: false, reviewModal: false, reviewProductId: null, reviewProductName: '' }">

    {{-- Back --}}
    <a href="/orders/my-orders" class="inline-flex items-center gap-2 text-[10px] font-bold text-gray-400 uppercase tracking-widest hover:text-[#C0422A] transition-colors mb-8">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
        Back to my orders
    </a>

    {{-- Flash Messages are handled by the global floating toast in layouts/app.blade.php --}}

    {{-- Page Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <div class="w-6 h-[2px] bg-[#C0422A]"></div>
                <span class="text-[10px] font-bold uppercase tracking-widest text-[#C0422A]">Order Details</span>
            </div>
            <h1 class="font-serif text-2xl font-bold text-black">
                #LB-OR-{{ strtoupper(substr($order->id, -8)) }}
            </h1>
            <p class="text-xs text-gray-400 mt-1">Placed on {{ $order->createdAt->format('F d, Y \a\t h:i A') }}</p>
        </div>

        @php
            $statusColors = [
                'pending'              => 'bg-yellow-50 text-yellow-700 border-yellow-200',
                'processing'           => 'bg-blue-50 text-blue-700 border-blue-200',
                'to ship'              => 'bg-blue-50 text-blue-700 border-blue-200',
                'to receive'           => 'bg-indigo-50 text-indigo-700 border-indigo-200',
                'shipped'              => 'bg-indigo-50 text-indigo-700 border-indigo-200',
                'completed'            => 'bg-green-50 text-green-700 border-green-200',
                'cancelled'            => 'bg-red-50 text-red-700 border-red-200',
                'cancellation pending' => 'bg-orange-50 text-orange-700 border-orange-200',
            ];
            $statusClass = $statusColors[strtolower($order->status)] ?? 'bg-gray-50 text-gray-700 border-gray-200';
        @endphp
        <span class="self-start sm:self-auto px-5 py-2 rounded-full border text-[10px] font-black uppercase tracking-widest {{ $statusClass }}">
            {{ $order->status }}
        </span>
    </div>

    {{-- Status Timeline --}}
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
    @endphp

    @if(!$isCancelled)
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 mb-6">
        <div class="flex items-center justify-between relative">
            <div class="absolute left-0 right-0 top-4 h-[2px] bg-gray-100 z-0"></div>
            <div class="absolute left-0 top-4 h-[2px] bg-[#C0422A] z-0 transition-all duration-700"
                 style="width: {{ $currentStep >= 3 ? '100' : ($currentStep * 33.33) }}%"></div>
            @foreach($steps as $i => $step)
                <div class="flex flex-col items-center gap-2 z-10 flex-1">
                    <div class="w-8 h-8 rounded-full border-2 flex items-center justify-center text-[10px] font-black transition-all
                        {{ $i <= $currentStep ? 'bg-[#C0422A] border-[#C0422A] text-white shadow-md shadow-[#C0422A]/30' : 'bg-white border-gray-200 text-gray-300' }}">
                        @if($i < $currentStep)
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                        @else
                            {{ $i + 1 }}
                        @endif
                    </div>
                    <span class="text-[9px] font-bold uppercase tracking-widest text-center hidden sm:block
                        {{ $i <= $currentStep ? 'text-[#C0422A]' : 'text-gray-300' }}">
                        {{ $step['label'] }}
                    </span>
                </div>
            @endforeach
        </div>
    </div>
    @else
    <div class="bg-red-50 border border-red-100 rounded-2xl p-6 mb-6 flex items-center gap-4">
        <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center shrink-0">
            <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
        </div>
        <div>
            <div class="text-xs font-black uppercase tracking-widest text-red-700 mb-0.5">Order Cancelled</div>
            @if($order->cancellationReason)
                <p class="text-xs text-red-500">Reason: {{ $order->cancellationReason }}</p>
            @endif
        </div>
    </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Left: Items & Actions --}}
        <div class="lg:col-span-2 space-y-6">

            {{-- Order Items --}}
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                <div class="px-6 py-4 bg-gray-50/50 border-b border-gray-100">
                    <h3 class="text-[10px] font-black uppercase tracking-widest text-gray-500">Items Ordered ({{ $order->items->count() }})</h3>
                </div>
                <div class="divide-y divide-gray-50">
                    @foreach($order->items as $item)
                        @php
                            $rawImg = is_array($item->product->image) ? ($item->product->image[0] ?? '') : $item->product->image;
                            if (!$rawImg) {
                                $imgSrc = asset('uploads/products/default.jpg');
                            } elseif (str_starts_with($rawImg, 'http')) {
                                $imgSrc = $rawImg;
                            } elseif (str_starts_with($rawImg, 'products/')) {
                                $imgSrc = asset('storage/' . $rawImg);
                            } elseif (str_starts_with($rawImg, 'uploads/')) {
                                $imgSrc = asset($rawImg);
                            } else {
                                $imgSrc = asset('uploads/products/' . $rawImg);
                            }
                        @endphp
                        <div class="p-6 flex items-center gap-4">
                            <div class="w-16 h-20 bg-gray-50 rounded-xl overflow-hidden border border-gray-100 shrink-0">
                                <img src="{{ $imgSrc }}" class="w-full h-full object-cover" onerror="this.src='{{ asset('uploads/products/default.jpg') }}'" alt="{{ $item->product->name }}">
                            </div>
                            <div class="flex-1 min-w-0">
                                <h4 class="text-sm font-bold text-black truncate mb-1">{{ $item->product->name }}</h4>
                                <div class="flex flex-wrap gap-3 text-[10px] font-bold text-gray-400 uppercase tracking-widest">
                                    @if($item->size)<span>Size: {{ $item->size }}</span>@endif
                                    <span>Qty: {{ $item->quantity }}</span>
                                    <span>₱{{ number_format($item->price) }} each</span>
                                </div>
                                {{-- Review button for completed orders --}}
                                @if(strtolower($order->status) === 'completed')
                                    @php
                                        $hasReview = $order->reviews->where('productId', $item->productId)->first();
                                    @endphp
                                    @if($hasReview)
                                        <div class="mt-2 flex flex-col gap-1">
                                            <span class="text-[9px] font-black uppercase tracking-widest text-green-600 flex items-center gap-1">
                                                <svg class="w-3 h-3 text-green-500 fill-current" viewBox="0 0 20 20">
                                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                                </svg>
                                                Reviewed ({{ $hasReview->rating }} ★)
                                            </span>
                                            <p class="text-[10px] text-gray-400 italic">"{{ $hasReview->comment }}"</p>
                                        </div>
                                    @else
                                        <button @click="reviewModal = true; reviewProductId = '{{ $item->productId }}'; reviewProductName = '{{ addslashes($item->product->name) }}'"
                                            class="mt-2 text-[9px] font-black uppercase tracking-widest text-[#C0422A] hover:underline">
                                            + Leave a Review
                                        </button>
                                    @endif
                                @endif
                            </div>
                            <div class="text-right shrink-0">
                                <div class="text-base font-black text-black">₱{{ number_format($item->price * $item->quantity) }}</div>
                                <div class="text-[9px] text-gray-400">subtotal</div>
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="px-6 py-4 bg-gray-50/50 border-t border-gray-100 flex items-center justify-between">
                    <span class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Order Total</span>
                    <span class="text-xl font-black text-[#C0422A]">₱{{ number_format($order->totalAmount) }}</span>
                </div>
            </div>

            {{-- Action Buttons --}}
            <div class="flex flex-wrap gap-3">
                @if(strtolower($order->status) === 'to receive' || strtolower($order->status) === 'shipped')
                    <button @click="confirmModal = true"
                        class="flex items-center gap-2 px-6 py-3 rounded-xl bg-green-600 text-white text-[10px] font-black uppercase tracking-widest hover:bg-green-700 transition-all shadow-md shadow-green-600/20">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        Confirm Received
                    </button>
                @endif
            </div>
        </div>

        {{-- Right: Summary Cards --}}
        <div class="space-y-6">

            {{-- Payment Info --}}
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                <div class="px-6 py-4 bg-gray-50/50 border-b border-gray-100">
                    <h3 class="text-[10px] font-black uppercase tracking-widest text-gray-500">Payment</h3>
                </div>
                <div class="p-6 space-y-4">
                    <div class="flex items-center justify-between">
                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Method</span>
                        <span class="text-xs font-black text-black uppercase">{{ $order->paymentMethod ?? 'N/A' }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Status</span>
                        @php
                            $resolvedPayment = $order->resolved_payment_status;
                            $payColor = $resolvedPayment === 'Paid' ? 'text-green-600' : ($resolvedPayment === 'Failed' ? 'text-red-500' : 'text-yellow-600');
                        @endphp
                        <span class="text-xs font-black {{ $payColor }} uppercase">{{ $resolvedPayment }}</span>
                    </div>
                    @if($order->paymentReference)
                    <div class="pt-3 border-t border-gray-50">
                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block mb-1">Reference</span>
                        <span class="text-xs font-mono text-gray-600 break-all">{{ $order->paymentReference }}</span>
                    </div>
                    @endif
                    @if($order->resolved_payment_status === 'Paid')
                    <div class="pt-3 border-t border-gray-50">
                        <p class="text-[10px] text-gray-500 leading-relaxed">
                            Payment was confirmed at checkout. Paid orders cannot be cancelled.
                            Please note that products purchased on LumBarong are final sale and do not include a refund feature.
                            For order updates, message the seller using
                            <span class="font-bold text-[#C0422A]">Messages</span> (bottom-right chat).
                        </p>
                    </div>
                    @endif
                </div>
            </div>

            {{-- Shipping Address --}}
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                <div class="px-6 py-4 bg-gray-50/50 border-b border-gray-100">
                    <h3 class="text-[10px] font-black uppercase tracking-widest text-gray-500">Ship To</h3>
                </div>
                <div class="p-6">
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
                            <p class="text-sm font-bold text-black mb-1">{{ $recipient }}</p>
                        @endif
                        @if($streetLine || $locality)
                            <p class="text-xs text-gray-500 leading-relaxed">
                                {{ $streetLine }}
                                @if($streetLine && $locality)<br>@endif
                                {{ $locality }}
                                @if(!empty($addr['postalCode']))
                                    <br>{{ $addr['postalCode'] }}
                                @endif
                            </p>
                        @endif
                        @if(!empty($addr['phone']))
                            <p class="text-xs text-gray-400 mt-2">{{ $addr['phone'] }}</p>
                        @endif
                    @else
                        <p class="text-xs text-gray-400 italic">No address on file.</p>
                    @endif
                </div>
            </div>

            {{-- Seller Info --}}
            @if($order->seller)
            <a href="{{ route('shops.show', $order->seller->id) }}"
               class="block bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden hover:border-[#C0422A]/30 hover:shadow-md transition-all group">
                <div class="px-6 py-4 bg-gray-50/50 border-b border-gray-100 flex items-center justify-between">
                    <h3 class="text-[10px] font-black uppercase tracking-widest text-gray-500">Sold By</h3>
                    <span class="text-[9px] font-black uppercase tracking-widest text-[#C0422A] opacity-0 group-hover:opacity-100 transition-opacity">
                        Visit Shop →
                    </span>
                </div>
                <div class="p-6 flex items-center gap-4">
                    @if($order->seller->profile_photo_url)
                        <img src="{{ $order->seller->profile_photo_url }}"
                             alt="{{ $order->seller->display_name }}"
                             class="w-10 h-10 rounded-xl object-cover border border-gray-100 shrink-0 group-hover:ring-2 group-hover:ring-[#C0422A]/20 transition-all">
                    @else
                        <div class="w-10 h-10 rounded-xl bg-[#C0422A] text-white flex items-center justify-center font-black text-sm shrink-0">
                            {{ strtoupper(substr($order->seller->display_name, 0, 1)) }}
                        </div>
                    @endif
                    <div class="min-w-0 flex-1">
                        <div class="text-sm font-bold text-black group-hover:text-[#C0422A] transition-colors">{{ $order->seller->display_name }}</div>
                        <div class="text-[10px] text-gray-400 font-bold uppercase tracking-widest">
                            {{ $order->seller->isVerified ? 'Verified Artisan' : 'Artisan Seller' }}
                        </div>
                        @if($order->seller->shopDescription)
                            <p class="text-[11px] text-gray-500 mt-1 line-clamp-2">{{ $order->seller->shopDescription }}</p>
                        @endif
                    </div>
                </div>
            </a>
            @endif
        </div>
    </div>

    {{-- Recommended Products --}}
    @if(isset($recommended) && $recommended->isNotEmpty())
    <div class="mt-16 pt-12 border-t border-gray-100">
        <div class="flex items-center justify-between mb-6">
            <div>
                <div class="flex items-center gap-2 mb-1">
                    <div class="w-5 h-[1.5px] bg-[#C0422A]"></div>
                    <span class="text-[10px] font-bold uppercase tracking-widest text-[#C0422A]">Keep Exploring</span>
                </div>
                <h2 class="font-serif text-2xl font-bold text-black">You Might Also Like</h2>
            </div>
            <a href="/" class="text-[10px] font-bold text-gray-400 uppercase tracking-widest hover:text-[#C0422A] transition-colors">
                View all
            </a>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-5">
            @foreach($recommended as $rec)
            <a href="/products/{{ $rec->id }}" class="group block">
                <div class="aspect-4/5 bg-gray-100 rounded-2xl overflow-hidden mb-3 relative shadow-sm">
                    <img src="{{ $rec->getImageUrl() }}"
                         alt="{{ $rec->name }}"
                         class="w-full h-full object-cover object-top group-hover:scale-105 transition-transform duration-500 ease-out">

                    @if($rec->is_on_sale && $rec->discount_percentage > 0)
                        <div class="absolute top-2.5 left-2.5 bg-[#C0422A] text-white text-[8px] font-black uppercase tracking-widest px-2 py-0.5 rounded-full">
                            -{{ number_format($rec->discount_percentage, 0) }}% OFF
                        </div>
                    @elseif($rec->target_group)
                        <div class="absolute top-2.5 left-2.5 bg-white/90 backdrop-blur-sm text-[8px] font-black uppercase tracking-widest text-gray-500 px-2 py-0.5 rounded-full">
                            {{ $rec->target_group }}
                        </div>
                    @endif
                </div>

                <h3 class="font-bold text-sm text-gray-900 group-hover:text-[#C0422A] transition-colors leading-tight line-clamp-2">{{ $rec->name }}</h3>

                @if($rec->avgRating)
                    <div class="flex items-center gap-1 text-[10px] font-bold text-yellow-500 mt-1">
                        <span>★</span>
                        <span>{{ number_format($rec->avgRating, 1) }}</span>
                        <span class="text-gray-400">({{ $rec->reviewCount }})</span>
                    </div>
                @endif

                <div class="flex items-center gap-2 mt-1">
                    @if($rec->is_on_sale && $rec->discount_percentage > 0)
                        <p class="text-sm font-black text-[#C0422A]">₱{{ number_format($rec->salePrice) }}</p>
                        <p class="text-xs font-bold text-gray-400 line-through">₱{{ number_format($rec->price) }}</p>
                    @else
                        <p class="text-sm font-black text-gray-800">₱{{ number_format($rec->price) }}</p>
                    @endif
                </div>
            </a>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Confirm Received Modal --}}
    <div x-show="confirmModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm" x-cloak>
        <div @click.away="confirmModal = false" class="bg-white rounded-3xl shadow-2xl w-full max-w-md p-8 space-y-6">
            <div class="text-center">
                <div class="w-16 h-16 rounded-full bg-green-50 flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                </div>
                <h3 class="font-serif text-xl font-bold text-black mb-2">Confirm Order Received?</h3>
                <p class="text-xs text-gray-500">Please only confirm once you have physically received all items in good condition.</p>
            </div>
            <form action="/orders/{{ $order->id }}/confirm" method="POST" class="flex gap-3">
                @csrf
                @method('PATCH')
                <button type="button" @click="confirmModal = false"
                    class="flex-1 py-3 rounded-xl border border-gray-200 text-[10px] font-bold uppercase tracking-widest text-gray-500 hover:bg-gray-50 transition-all">
                    Not Yet
                </button>
                <button type="submit"
                    class="flex-1 py-3 rounded-xl bg-green-600 text-white text-[10px] font-bold uppercase tracking-widest hover:bg-green-700 transition-all">
                    Confirm Received
                </button>
            </form>
        </div>
    </div>

    {{-- Leave Review Modal --}}
    <div x-show="reviewModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm" x-cloak>
        <div @click.away="reviewModal = false" class="bg-white rounded-3xl shadow-2xl w-full max-w-md p-8 space-y-6">
            <div>
                <h3 class="font-serif text-xl font-bold text-black mb-1">Leave a Review</h3>
                <p class="text-xs text-gray-400" x-text="reviewProductName"></p>
            </div>
            <form action="/api/reviews" method="POST" class="space-y-5" x-data="{ rating: 0, hover: 0 }" @submit="if (rating === 0) { $event.preventDefault(); alert('Please select a rating of at least 1 star before submitting.'); }">
                @csrf
                <input type="hidden" name="productId" :value="reviewProductId">
                <input type="hidden" name="orderId" value="{{ $order->id }}">
                {{-- Star Rating --}}
                <div class="space-y-2">
                    <label class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Rating</label>
                    <div class="flex gap-1">
                        @for($i = 1; $i <= 5; $i++)
                            <button type="button"
                                @click="rating = {{ $i }}"
                                @mouseenter="hover = {{ $i }}"
                                @mouseleave="hover = 0"
                                class="text-3xl transition-transform hover:scale-110">
                                <span :class="(hover || rating) >= {{ $i }} ? 'text-yellow-400' : 'text-gray-200'">★</span>
                            </button>
                        @endfor
                        <input type="hidden" name="rating" :value="rating">
                    </div>
                </div>
                <div class="space-y-2">
                    <label class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Your Review</label>
                    <textarea name="comment" rows="4" required placeholder="Share your experience with this heritage piece..."
                        class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm outline-none focus:border-[#C0422A] transition-all resize-none"></textarea>
                </div>
                <div class="flex gap-3">
                    <button type="button" @click="reviewModal = false"
                        class="flex-1 py-3 rounded-xl border border-gray-200 text-[10px] font-bold uppercase tracking-widest text-gray-500 hover:bg-gray-50 transition-all">
                        Cancel
                    </button>
                    <button type="submit"
                        class="flex-1 py-3 rounded-xl bg-black text-white text-[10px] font-bold uppercase tracking-widest hover:bg-[#C0422A] transition-all">
                        Submit Review
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
