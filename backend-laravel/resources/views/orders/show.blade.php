@extends('layouts.app')

@section('content')
<div class="max-w-6xl mx-auto px-3 sm:px-6 lg:px-8 py-6 sm:py-10 space-y-6 sm:space-y-8" x-data="{ confirmModal: false, reviewModal: false, reviewProductId: null, reviewProductName: '', reviewOrderItemId: null, copiedToast: false, packingModal: false, packingModalUrl: '' }">

    {{-- Toast for clipboard copy feedback --}}
    <div x-show="copiedToast" x-transition class="fixed top-6 right-6 z-999 bg-black text-white text-xs font-bold px-4 py-2.5 rounded-2xl shadow-xl flex items-center gap-2" style="display: none;">
        <span>📋 Tracking number copied to clipboard!</span>
    </div>

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
            'to ship'              => 'bg-sky-50 text-sky-700 border-sky-200',
            'ready to ship'        => 'bg-sky-50 text-sky-700 border-sky-200',
            'ready_to_ship'        => 'bg-sky-50 text-sky-700 border-sky-200',
            'shipped'              => 'bg-indigo-50 text-indigo-700 border-indigo-200',
            'to receive'           => 'bg-indigo-50 text-indigo-700 border-indigo-200',
            'in transit'           => 'bg-purple-50 text-purple-700 border-purple-200',
            'in_transit'           => 'bg-purple-50 text-purple-700 border-purple-200',
            'out for delivery'     => 'bg-orange-50 text-orange-700 border-orange-200',
            'out_for_delivery'     => 'bg-orange-50 text-orange-700 border-orange-200',
            'delivered'            => 'bg-teal-50 text-teal-700 border-teal-200',
            'completed'            => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            'cancelled'            => 'bg-red-50 text-red-700 border-red-200',
        ];
        $statusClass = $statusColors[strtolower(trim($order->status))] ?? 'bg-gray-50 text-gray-700 border-gray-200';
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

    {{-- Order Progress Timeline --}}
    @php
        $steps = [
            ['label' => 'Order Placed',  'status' => 'pending'],
            ['label' => 'To Ship',       'status' => 'to ship'],
            ['label' => 'Shipped',       'status' => 'shipped'],
            ['label' => 'In Transit',    'status' => 'in transit'],
            ['label' => 'Delivered',     'status' => 'delivered'],
        ];

        $statusRanks = [
            'pending'          => 0,
            'to ship'          => 1,
            'ready to ship'    => 1,
            'ready_to_ship'    => 1,
            'processing'       => 1,
            'shipped'          => 2,
            'to receive'       => 2,
            'in transit'       => 3,
            'in_transit'       => 3,
            'out for delivery' => 3,
            'out_for_delivery' => 3,
            'delivered'        => 4,
            'completed'        => 4,
            'cancelled'        => -1,
        ];

        $currentStep = $statusRanks[strtolower(trim($order->status))] ?? 0;
        $isCancelled = strtolower(trim($order->status)) === 'cancelled';
        $maxSteps = count($steps);

        // Map status history timestamps
        $historyDates = [];
        if ($order->statusHistories) {
            foreach ($order->statusHistories as $h) {
                $historyDates[strtolower(trim($h->newStatus))] = $h->createdAt ? $h->createdAt->format('M d, g:i A') : null;
            }
        }
    @endphp

    @if(!$isCancelled)
    <div class="bg-white rounded-2xl sm:rounded-3xl border border-gray-100 shadow-sm p-5 sm:p-8 space-y-4">
        <div class="flex items-center justify-between">
            <span class="text-[10px] sm:text-xs font-black uppercase tracking-widest text-[#C0420A]">Live Shipment Tracking</span>
            <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Step {{ min($currentStep + 1, $maxSteps) }} of {{ $maxSteps }}</span>
        </div>

        <div class="flex gap-2 overflow-x-auto no-scrollbar py-3 px-1">
            @foreach($steps as $i => $step)
                @php
                    $isDone = $i <= $currentStep;
                    $isCurrent = $i === $currentStep;
                    $stepKey = strtolower($step['status']);
                    $timeLabel = $historyDates[$stepKey] ?? ($i === 0 ? $order->createdAt->format('M d, g:i A') : null);
                @endphp
                <div class="flex-1 min-w-25 flex flex-col items-center gap-2 relative group">
                    <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-full border-2 flex items-center justify-center text-xs font-black transition-all
                        {{ $isCurrent ? 'bg-[#C0420A] border-[#C0420A] text-white shadow-lg shadow-[#C0420A]/30 scale-110' : ($isDone ? 'bg-black border-black text-white' : 'bg-white border-gray-200 text-gray-300') }}">
                        @if($isDone && !$isCurrent)
                            ✓
                        @else
                            {{ $i + 1 }}
                        @endif
                    </div>
                    <div class="text-center">
                        <span class="text-[9px] sm:text-[10px] font-black uppercase tracking-wider block {{ $isDone ? ($isCurrent ? 'text-[#C0420A]' : 'text-black') : 'text-gray-300' }}">
                            {{ $step['label'] }}
                        </span>
                        @if($timeLabel && $isDone)
                            <span class="text-[8px] font-medium text-gray-400 block mt-0.5 whitespace-nowrap">{{ $timeLabel }}</span>
                        @endif
                    </div>
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

        {{-- Left Column: Purchased Items List & Shipment Card --}}
        <div class="lg:col-span-2 space-y-6">

            {{-- Manual Courier Shipping Details Card (Only shown when order has actually been Shipped/Dispatched) --}}
            @php
                $statusNormalized = strtolower(trim($order->status ?? ''));
                $isToShip = in_array($statusNormalized, ['pending', 'processing', 'to ship', 'ready to ship', 'to_ship', 'ready_to_ship']);
            @endphp

            @if($order->packingProof && $isToShip)
            <div class="bg-emerald-950/80 border border-emerald-500/30 text-white rounded-2xl sm:rounded-3xl p-5 shadow-lg flex items-center justify-between flex-wrap gap-3">
                <div>
                    <span class="text-[10px] font-black uppercase tracking-widest text-emerald-400 block mb-0.5">📦 Seller Packing Proof Photo</span>
                    <span class="text-xs text-gray-300 font-medium">Uploaded by seller before handover to courier</span>
                </div>
                <button type="button" 
                    @click="packingModalUrl = '{{ asset('storage/' . $order->packingProof) }}'; packingModal = true;"
                    class="px-4 py-2 bg-emerald-500 hover:bg-emerald-400 text-black text-[10px] font-black uppercase tracking-widest rounded-full transition-all flex items-center gap-1.5 shadow-md active:scale-95">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    <span>View Packing Proof ↗</span>
                </button>
            </div>
            @endif

            @if(!$isToShip && ($order->courierName || $order->trackingNumber || $order->trackingLink || in_array($statusNormalized, ['shipped', 'to receive', 'in transit', 'in_transit', 'out for delivery', 'out_for_delivery', 'delivered', 'completed'])))
            <div class="bg-linear-to-br from-gray-900 to-black text-white rounded-2xl sm:rounded-3xl p-5 sm:p-7 shadow-lg space-y-4">
                <div class="flex items-center justify-between border-b border-white/10 pb-3">
                    <div class="flex items-center gap-2">
                        <span class="text-base">📦</span>
                        <h3 class="text-xs sm:text-sm font-black uppercase tracking-widest">Shipment Information</h3>
                    </div>
                    <span class="px-2.5 py-0.5 bg-white/10 text-white rounded-full text-[9px] font-bold uppercase tracking-wider">
                        {{ $order->status }}
                    </span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                    <div>
                        <span class="text-[9px] font-bold uppercase tracking-widest text-gray-400 block mb-0.5">Courier Company</span>
                        <span class="font-black text-sm text-white">{{ $order->courierName ?? 'Pending Assignment' }}</span>
                    </div>

                    <div>
                        <span class="text-[9px] font-bold uppercase tracking-widest text-gray-400 block mb-0.5">Tracking Number</span>
                        <div class="flex items-center gap-2">
                            <span class="font-mono text-sm font-bold text-amber-400">{{ $order->trackingNumber ?? 'Pending Dispatch' }}</span>
                            @if($order->trackingNumber)
                                <button type="button" 
                                    @click="navigator.clipboard.writeText('{{ $order->trackingNumber }}'); copiedToast = true; setTimeout(() => copiedToast = false, 2500);"
                                    class="px-2 py-0.5 bg-white/10 hover:bg-white/20 text-white rounded-md text-[9px] font-bold uppercase tracking-wider transition-all flex items-center gap-1 active:scale-95">
                                    📋 Copy
                                </button>
                            @endif
                        </div>
                    </div>
                </div>

                @if($order->trackingLink)
                <div class="pt-2">
                    <a href="{{ $order->trackingLink }}" target="_blank" rel="noopener noreferrer"
                        class="inline-flex items-center justify-center gap-2 w-full sm:w-auto px-6 py-3 bg-[#C0420A] hover:bg-[#d94a0d] text-white rounded-full text-xs font-black uppercase tracking-widest transition-all shadow-md active:scale-95">
                        <span>Track Package ↗</span>
                    </a>
                </div>
                @endif

                @if($order->packingProof)
                <div class="pt-3 border-t border-white/10 flex items-center justify-between flex-wrap gap-2">
                    <div>
                        <span class="text-[9px] font-bold uppercase tracking-widest text-emerald-400 block mb-0.5">📦 Seller Packing Proof Photo</span>
                        <span class="text-[10px] text-gray-300 font-medium">Uploaded by seller before handover to courier</span>
                    </div>
                    <button type="button" 
                        @click="packingModalUrl = '{{ asset('storage/' . $order->packingProof) }}'; packingModal = true;"
                        class="px-4 py-2 bg-emerald-500 hover:bg-emerald-400 text-black text-[10px] font-black uppercase tracking-widest rounded-full transition-all flex items-center gap-1.5 shadow-md active:scale-95">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                        <span>View Packing Proof ↗</span>
                    </button>
                </div>
                @endif
            </div>
            @endif

            <div class="bg-white rounded-2xl sm:rounded-3xl border border-gray-100 shadow-sm overflow-hidden">
                <div class="px-5 sm:px-7 py-4 bg-gray-50/60 border-b border-gray-100 flex items-center justify-between">
                    <h3 class="text-[10px] sm:text-xs font-black uppercase tracking-widest text-gray-600">Items Ordered ({{ $order->items->count() }})</h3>
                    <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Heritage Pieces</span>
                </div>
                
                <div class="divide-y divide-gray-50">
                    @foreach($order->items as $item)
                        @php
                            $imgSrc = $item->product ? $item->product->getImageUrl() : asset('uploads/products/default.jpg');
                            $itemStatus = strtolower(trim($order->status));
                            $canRate = in_array($itemStatus, ['delivered', 'completed'], true);
                            $existingReview = $order->reviews ? $order->reviews->where('orderItemId', $item->id)->first() : null;
                            if (!$existingReview && $order->reviews) {
                                $existingReview = $order->reviews->where('productId', $item->productId)->first();
                            }
                        @endphp
                        <div class="p-4 sm:p-6 flex items-center gap-3 sm:gap-5">
                            {{-- Product Image --}}
                            <div class="w-16 h-20 sm:w-20 sm:h-24 bg-gray-50 rounded-2xl overflow-hidden border border-gray-100 shrink-0">
                                <img src="{{ $imgSrc }}" class="w-full h-full object-cover object-top" onerror="this.src='/uploads/products/default.jpg'" alt="{{ $item->product->name ?? 'Product' }}">
                            </div>

                            {{-- Info --}}
                            <div class="flex-1 min-w-0 space-y-1">
                                <h4 class="text-xs sm:text-base font-bold text-black truncate">{{ $item->product->name ?? 'Heritage Product' }}</h4>
                                <div class="flex flex-wrap items-center gap-2 sm:gap-3 text-[9px] sm:text-[10px] font-bold text-gray-400 uppercase tracking-widest">
                                    @if($item->size)<span class="px-2 py-0.5 bg-gray-100 rounded-md text-gray-600">Size: {{ $item->size }}</span>@endif
                                    <span>Qty: {{ $item->quantity }}</span>
                                    <span>₱{{ number_format($item->price) }} each</span>
                                </div>

                                {{-- Rating & Review Controls for Delivered or Completed Orders --}}
                                @if($canRate)
                                    @if($existingReview)
                                        <div class="mt-2.5 p-3 bg-emerald-50/60 border border-emerald-100/80 rounded-2xl space-y-1">
                                            <div class="flex items-center justify-between flex-wrap gap-2">
                                                <div class="flex items-center gap-1 text-amber-400">
                                                    @for($s = 1; $s <= 5; $s++)
                                                        <span class="text-xs">{{ $s <= $existingReview->rating ? '★' : '☆' }}</span>
                                                    @endfor
                                                    <span class="text-[10px] font-black text-emerald-800 ml-1">{{ $existingReview->rating }}/5</span>
                                                </div>
                                                <span class="px-2 py-0.5 bg-emerald-100 text-emerald-800 rounded-full text-[8px] font-black uppercase tracking-widest flex items-center gap-1">
                                                    ✓ Verified Purchase
                                                </span>
                                            </div>
                                            @if($existingReview->comment)
                                                <p class="text-[11px] text-gray-700 italic leading-relaxed">"{{ $existingReview->comment }}"</p>
                                            @endif
                                        </div>
                                    @else
                                        <button type="button"
                                            @click="reviewModal = true; reviewProductId = '{{ $item->productId }}'; reviewOrderItemId = '{{ $item->id }}'; reviewProductName = '{{ addslashes($item->product->name ?? 'Product') }}'"
                                            class="mt-2.5 inline-flex items-center gap-1.5 px-3.5 py-1.5 bg-black hover:bg-[#C0420A] text-white rounded-full text-[10px] font-black uppercase tracking-widest transition-all shadow-xs active:scale-95 cursor-pointer">
                                            <span>⭐ Rate Product</span>
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
            @if(in_array(strtolower(trim($order->status)), ['shipped', 'to receive', 'in transit', 'in_transit', 'out for delivery', 'out_for_delivery', 'delivered'], true))
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
    <div x-show="reviewModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm" x-cloak style="display: none;">
        <div @click.away="reviewModal = false" class="bg-white rounded-3xl shadow-2xl w-full max-w-md p-6 sm:p-8 space-y-5">
            <div>
                <div class="text-[9px] font-black uppercase tracking-widest text-[#C0420A]">Leave a Review</div>
                <h3 class="font-serif text-lg font-bold text-black mt-0.5" x-text="reviewProductName"></h3>
            </div>
            <form action="/api/reviews" method="POST" enctype="multipart/form-data" class="space-y-4" x-data="{ rating: 0, hover: 0, photoFiles: [], videoFile: null }" @submit="if (rating === 0) { $event.preventDefault(); alert('Please select a rating of at least 1 star before submitting.'); }">
                @csrf
                <input type="hidden" name="productId" :value="reviewProductId">
                <input type="hidden" name="orderId" value="{{ $order->id }}">
                <input type="hidden" name="orderItemId" :value="reviewOrderItemId">
                
                {{-- Star Rating --}}
                <div class="space-y-1.5">
                    <label class="text-[10px] font-black uppercase tracking-widest text-gray-500">Star Rating <span class="text-red-500">*</span></label>
                    <div class="flex gap-2 items-center">
                        @for($i = 1; $i <= 5; $i++)
                            <button type="button"
                                @click="rating = {{ $i }}"
                                @mouseenter="hover = {{ $i }}"
                                @mouseleave="hover = 0"
                                class="text-3xl transition-transform active:scale-125 focus:outline-none">
                                <span :class="(hover || rating) >= {{ $i }} ? 'text-amber-400' : 'text-gray-200'">★</span>
                            </button>
                        @endfor
                        <span class="text-xs font-bold text-gray-500 ml-2" x-text="rating > 0 ? rating + ' / 5 Stars' : 'Select Rating'"></span>
                        <input type="hidden" name="rating" :value="rating">
                    </div>
                </div>

                {{-- Comment --}}
                <div class="space-y-1.5">
                    <label class="text-[10px] font-black uppercase tracking-widest text-gray-500">Your Review / Feedback <span class="text-red-500">*</span></label>
                    <textarea name="comment" rows="3" required placeholder="Share your detailed feedback on product quality, fit, and craftsmanship..."
                        class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-2xl text-xs outline-none focus:border-[#C0420A] focus:bg-white transition-all resize-none"></textarea>
                </div>

                {{-- Photo Attachments --}}
                <div class="space-y-1.5">
                    <label class="text-[10px] font-black uppercase tracking-widest text-gray-500 flex items-center justify-between">
                        <span>📷 Add Photos (Optional)</span>
                        <span class="text-[9px] font-bold text-gray-400">JPG, PNG (Max 10MB)</span>
                    </label>
                    <input type="file" name="photos[]" multiple accept="image/*"
                        @change="photoFiles = Array.from($event.target.files)"
                        class="w-full text-xs text-gray-500 file:mr-3 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-[10px] file:font-black file:uppercase file:bg-gray-100 file:text-black hover:file:bg-[#C0420A] hover:file:text-white transition-all">
                    <template x-if="photoFiles.length > 0">
                        <p class="text-[10px] font-bold text-emerald-600 mt-1" x-text="photoFiles.length + ' photo(s) selected'"></p>
                    </template>
                </div>

                {{-- Video Attachment --}}
                <div class="space-y-1.5">
                    <label class="text-[10px] font-black uppercase tracking-widest text-gray-500 flex items-center justify-between">
                        <span>🎥 Add Video (Optional)</span>
                        <span class="text-[9px] font-bold text-gray-400">MP4, MOV, WEBM (Max 50MB)</span>
                    </label>
                    <input type="file" name="video" accept="video/*"
                        @change="videoFile = $event.target.files[0] ? $event.target.files[0].name : null"
                        class="w-full text-xs text-gray-500 file:mr-3 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-[10px] file:font-black file:uppercase file:bg-gray-100 file:text-black hover:file:bg-[#C0420A] hover:file:text-white transition-all">
                    <template x-if="videoFile">
                        <p class="text-[10px] font-bold text-emerald-600 mt-1" x-text="'Video selected: ' + videoFile"></p>
                    </template>
                </div>

                <div class="flex gap-3 pt-2">
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

    {{-- Packing Proof Image Viewer Modal --}}
    <div x-show="packingModal" class="fixed inset-0 z-9999 flex items-center justify-center p-4 bg-black/70 backdrop-blur-md" x-cloak style="display: none;">
        <div @click.away="packingModal = false" class="relative max-w-lg w-full bg-white rounded-3xl overflow-hidden shadow-2xl p-6 flex flex-col items-center">
            <div class="w-full flex items-center justify-between pb-4 border-b border-gray-100 mb-4">
                <div class="flex items-center gap-2">
                    <span class="text-xl">📦</span>
                    <h3 class="font-serif text-lg font-bold text-black">Seller Packing Proof</h3>
                </div>
                <button type="button" @click="packingModal = false"
                    class="w-8 h-8 rounded-full border border-gray-200 flex items-center justify-center text-gray-400 hover:text-black hover:border-gray-400 transition-all shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            
            <div class="w-full bg-gray-50 rounded-2xl overflow-hidden flex items-center justify-center border border-gray-100 max-h-[70vh]">
                <img :src="packingModalUrl" class="max-w-full max-h-[60vh] object-contain" alt="Seller Packing Proof">
            </div>
            
            <div class="w-full mt-4 flex gap-3">
                <a :href="packingModalUrl" download="packing-proof.jpg" class="flex-1 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 text-center rounded-xl text-[10px] font-bold uppercase tracking-widest transition-all">
                    Download Image
                </a>
                <button type="button" @click="packingModal = false" class="flex-1 py-3 bg-black hover:bg-[#C0420A] text-white rounded-xl text-[10px] font-bold uppercase tracking-widest transition-all">
                    Close
                </button>
            </div>
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
