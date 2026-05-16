@extends('layouts.app')

@section('content')
<div class="max-w-[900px] mx-auto py-8" x-data="{ cancelModal: false, confirmModal: false, reviewModal: false, reviewProductId: null, reviewProductName: '' }">

    {{-- Back --}}
    <a href="/orders/my-orders" class="inline-flex items-center gap-2 text-[10px] font-bold text-gray-400 uppercase tracking-widest hover:text-[#C0422A] transition-colors mb-8">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
        Back to my orders
    </a>

    {{-- Flash Messages --}}
    @if(session('success'))
        <div class="mb-6 px-6 py-4 bg-green-50 border border-green-100 rounded-2xl text-green-700 text-xs font-bold flex items-center gap-3">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="mb-6 px-6 py-4 bg-red-50 border border-red-100 rounded-2xl text-red-700 text-xs font-bold flex items-center gap-3">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            {{ session('error') }}
        </div>
    @endif

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
                'pending'    => 'bg-yellow-50 text-yellow-700 border-yellow-200',
                'to ship'    => 'bg-blue-50 text-blue-700 border-blue-200',
                'to receive' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
                'shipped'    => 'bg-indigo-50 text-indigo-700 border-indigo-200',
                'completed'  => 'bg-green-50 text-green-700 border-green-200',
                'cancelled'  => 'bg-red-50 text-red-700 border-red-200',
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
        $statusOrder = ['pending' => 0, 'to ship' => 1, 'to receive' => 2, 'shipped' => 2, 'completed' => 3, 'cancelled' => -1];
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
                            $img = is_array($item->product->image) ? ($item->product->image[0] ?? '') : $item->product->image;
                            $imgSrc = $img ? (str_starts_with($img, 'http') ? $img : asset('uploads/products/' . $img)) : '';
                        @endphp
                        <div class="p-6 flex items-center gap-4">
                            <div class="w-16 h-20 bg-gray-50 rounded-xl overflow-hidden border border-gray-100 shrink-0">
                                @if($imgSrc)
                                    <img src="{{ $imgSrc }}" class="w-full h-full object-cover">
                                @endif
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
                                    <button @click="reviewModal = true; reviewProductId = '{{ $item->productId }}'; reviewProductName = '{{ addslashes($item->product->name) }}'"
                                        class="mt-2 text-[9px] font-black uppercase tracking-widest text-[#C0422A] hover:underline">
                                        + Leave a Review
                                    </button>
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
                @if(strtolower($order->status) === 'pending')
                    <button @click="cancelModal = true"
                        class="flex items-center gap-2 px-6 py-3 rounded-xl border-2 border-red-200 text-red-600 text-[10px] font-black uppercase tracking-widest hover:bg-red-50 transition-all">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        Cancel Order
                    </button>
                @endif

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
                            $payStatus = strtolower($order->paymentStatus ?? 'pending');
                            $payColor = $payStatus === 'paid' ? 'text-green-600' : ($payStatus === 'failed' ? 'text-red-500' : 'text-yellow-600');
                        @endphp
                        <span class="text-xs font-black {{ $payColor }} uppercase">{{ $order->paymentStatus ?? 'Pending' }}</span>
                    </div>
                    @if($order->paymentReference)
                    <div class="pt-3 border-t border-gray-50">
                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block mb-1">Reference</span>
                        <span class="text-xs font-mono text-gray-600 break-all">{{ $order->paymentReference }}</span>
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
                    @if($order->shippingAddress)
                        @php $addr = $order->shippingAddress; @endphp
                        <p class="text-sm font-bold text-black mb-1">{{ $addr['fullName'] ?? $addr['name'] ?? 'Customer' }}</p>
                        <p class="text-xs text-gray-500 leading-relaxed">
                            {{ $addr['address'] ?? $addr['street'] ?? '' }}
                            @if(!empty($addr['barangay'])), {{ $addr['barangay'] }}@endif
                            @if(!empty($addr['city'])), {{ $addr['city'] }}@endif
                            @if(!empty($addr['province'])), {{ $addr['province'] }}@endif
                            @if(!empty($addr['postalCode'])), {{ $addr['postalCode'] }}@endif
                        </p>
                        @if(!empty($addr['phone']))
                            <p class="text-xs text-gray-400 mt-2">📞 {{ $addr['phone'] }}</p>
                        @endif
                    @else
                        <p class="text-xs text-gray-400 italic">No address on file.</p>
                    @endif
                </div>
            </div>

            {{-- Seller Info --}}
            @if($order->seller)
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                <div class="px-6 py-4 bg-gray-50/50 border-b border-gray-100">
                    <h3 class="text-[10px] font-black uppercase tracking-widest text-gray-500">Sold By</h3>
                </div>
                <div class="p-6 flex items-center gap-4">
                    <div class="w-10 h-10 rounded-xl bg-[#C0422A] text-white flex items-center justify-center font-black text-sm shrink-0">
                        {{ strtoupper(substr($order->seller->name, 0, 1)) }}
                    </div>
                    <div>
                        <div class="text-sm font-bold text-black">{{ $order->seller->name }}</div>
                        <div class="text-[10px] text-gray-400 font-bold uppercase tracking-widest">Verified Artisan</div>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>

    {{-- Cancel Order Modal --}}
    <div x-show="cancelModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm" x-cloak>
        <div @click.away="cancelModal = false" class="bg-white rounded-3xl shadow-2xl w-full max-w-md p-8 space-y-6">
            <div class="text-center">
                <div class="w-16 h-16 rounded-full bg-red-50 flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                </div>
                <h3 class="font-serif text-xl font-bold text-black mb-2">Cancel This Order?</h3>
                <p class="text-xs text-gray-500">This action cannot be undone. Please provide a reason.</p>
            </div>
            <form action="/orders/{{ $order->id }}/cancel" method="POST" class="space-y-4">
                @csrf
                @method('PATCH')
                <textarea name="cancellationReason" required rows="3" placeholder="Reason for cancellation..."
                    class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm outline-none focus:border-[#C0422A] transition-all resize-none"></textarea>
                <div class="flex gap-3">
                    <button type="button" @click="cancelModal = false"
                        class="flex-1 py-3 rounded-xl border border-gray-200 text-[10px] font-bold uppercase tracking-widest text-gray-500 hover:bg-gray-50 transition-all">
                        Keep Order
                    </button>
                    <button type="submit"
                        class="flex-1 py-3 rounded-xl bg-red-500 text-white text-[10px] font-bold uppercase tracking-widest hover:bg-red-600 transition-all">
                        Yes, Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

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
            <form action="/api/reviews" method="POST" class="space-y-5">
                @csrf
                <input type="hidden" name="productId" :value="reviewProductId">
                <input type="hidden" name="orderId" value="{{ $order->id }}">
                {{-- Star Rating --}}
                <div x-data="{ rating: 0, hover: 0 }" class="space-y-2">
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
