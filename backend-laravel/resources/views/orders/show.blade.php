@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-[#FBF9F5] text-gray-800 py-6 sm:py-8 px-3 sm:px-6 lg:px-8 font-sans -mt-6 sm:-mt-8 -mb-12"
     x-data="{ 
        confirmModal: false, 
        reviewModal: false, 
        reviewProductId: null, 
        reviewProductName: '', 
        reviewOrderItemId: null, 
        reviewProductImage: '', 
        copiedToast: false, 
        packingModal: false, 
        packingModalUrl: '' 
     }">

    {{-- Toast for clipboard copy feedback --}}
    <div x-show="copiedToast" x-transition:enter="transition ease-out duration-300 transform"
         x-transition:enter-start="opacity-0 translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-200"
         class="fixed top-6 right-6 z-999 bg-[#1E1E1E] text-white text-xs font-bold px-3.5 py-2.5 rounded-xl shadow-2xl flex items-center gap-2 border border-white/10" 
         style="display: none;">
        <svg class="w-3.5 h-3.5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
        <span>Reference number copied!</span>
    </div>

    <div class="max-w-4xl mx-auto space-y-4 sm:space-y-5">

        {{-- Top Navigation --}}
        <div>
            <a href="/orders/my-orders" class="inline-flex items-center gap-1.5 text-xs font-bold text-gray-500 hover:text-[#C0420A] transition-colors group">
                <div class="w-6 h-6 rounded-full bg-white border border-gray-200 flex items-center justify-center shadow-2xs group-hover:border-[#C0420A] group-hover:bg-[#C0420A] group-hover:text-white transition-all">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/></svg>
                </div>
                <span>Back to my orders</span>
            </a>
        </div>

        {{-- Header Section --}}
        @php
            $statusLower = strtolower(trim($order->status ?? ''));
            $badgeClasses = match($statusLower) {
                'completed' => 'bg-emerald-50 text-emerald-800 border-emerald-200/80',
                'delivered' => 'bg-teal-50 text-teal-800 border-teal-200/80',
                'shipped', 'to receive' => 'bg-indigo-50 text-indigo-800 border-indigo-200/80',
                'in transit', 'in_transit' => 'bg-purple-50 text-purple-800 border-purple-200/80',
                'to ship', 'ready to ship', 'processing' => 'bg-sky-50 text-sky-800 border-sky-200/80',
                'cancelled' => 'bg-red-50 text-red-800 border-red-200/80',
                default => 'bg-amber-50 text-amber-800 border-amber-200/80',
            };
            $dotClasses = match($statusLower) {
                'completed' => 'bg-emerald-500',
                'delivered' => 'bg-teal-500',
                'shipped', 'to receive' => 'bg-indigo-500',
                'in transit', 'in_transit' => 'bg-purple-500',
                'to ship', 'ready to ship', 'processing' => 'bg-sky-500',
                'cancelled' => 'bg-red-500',
                default => 'bg-amber-500',
            };
        @endphp

        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2.5">
            <div>
                <div class="flex items-center gap-1.5 mb-0.5">
                    <span class="w-1.5 h-1.5 rounded-full bg-[#C0420A]"></span>
                    <span class="text-[9px] font-extrabold uppercase tracking-widest text-[#C0420A]">Order Overview</span>
                </div>
                <h1 class="text-xl sm:text-2xl font-black text-gray-900 tracking-tight">
                    #LB-OR-{{ strtoupper(substr($order->id, -8)) }}
                </h1>
                <p class="text-[11px] text-gray-400 font-medium mt-0.5">
                    Placed {{ $order->createdAt ? $order->createdAt->format('M d, Y \a\t g:i A') : 'Recently' }}
                </p>
            </div>

            <div class="self-start sm:self-center">
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] sm:text-[11px] font-bold tracking-wider uppercase border shadow-2xs {{ $badgeClasses }}">
                    <span class="w-1.5 h-1.5 rounded-full {{ $dotClasses }}"></span>
                    <span>{{ $order->status ?? 'Pending' }}</span>
                </span>
            </div>
        </div>

        {{-- Live Shipment Tracking Card --}}
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

            $currentStep = $statusRanks[$statusLower] ?? 0;
            $isCancelled = $statusLower === 'cancelled';
            $maxSteps = count($steps);

            // Map status history timestamps
            $historyDates = [];
            if ($order->statusHistories) {
                foreach ($order->statusHistories as $h) {
                    $historyDates[strtolower(trim($h->newStatus))] = $h->createdAt ? $h->createdAt->format('g:i A') : null;
                }
            }
        @endphp

        @if(!$isCancelled)
        <div class="bg-white rounded-2xl p-4 sm:p-5 border border-[#EAE6DF] shadow-2xs">
            <div class="flex items-center justify-between mb-4 pb-2.5 border-b border-gray-100">
                <div class="flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5 text-[#C0420A]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h2m-6 0a1 1 0 01-1-1m8 1a1 1 0 001-1m-6 0h4"/></svg>
                    <span class="text-[11px] font-bold uppercase tracking-wider text-gray-700">Live Shipment Tracking</span>
                </div>
            </div>

            <div class="grid grid-cols-5 gap-1 sm:gap-3">
                @foreach($steps as $i => $step)
                    @php
                        $isDone = $i <= $currentStep;
                        $isCurrent = $i === $currentStep;
                        $stepKey = strtolower($step['status']);
                        $timeLabel = $historyDates[$stepKey] ?? ($i === 0 && $order->createdAt ? $order->createdAt->format('g:i A') : null);
                    @endphp

                    <div class="flex flex-col items-center text-center">
                        {{-- Step Circle --}}
                        <div class="w-7 h-7 sm:w-8 sm:h-8 rounded-full flex items-center justify-center text-[11px] font-bold transition-all
                            {{ $isCurrent 
                                ? 'bg-[#C0420A] text-white shadow-sm ring-3 ring-orange-100' 
                                : ($isDone 
                                    ? 'bg-black text-white' 
                                    : 'bg-white border border-gray-200 text-gray-400') }}">
                            @if($isDone && !$isCurrent)
                                <svg class="w-3.5 h-3.5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                            @else
                                {{ $i + 1 }}
                            @endif
                        </div>

                        {{-- Step Label --}}
                        <div class="mt-2">
                            <span class="text-[10px] sm:text-[11px] font-bold block leading-tight {{ $isCurrent ? 'text-[#C0420A]' : ($isDone ? 'text-gray-900' : 'text-gray-400') }}">
                                {{ $step['label'] }}
                            </span>
                            @if($timeLabel && $isDone)
                                <span class="text-[8px] sm:text-[9px] font-semibold text-gray-400 block mt-0.5">{{ $timeLabel }}</span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
        @else
        <div class="bg-red-50 border border-red-200 rounded-2xl p-4 flex items-center gap-3">
            <div class="w-8 h-8 rounded-xl bg-red-100 text-red-600 flex items-center justify-center shrink-0">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path></svg>
            </div>
            <div>
                <div class="text-xs font-bold text-red-700 uppercase tracking-wider">Order Cancelled</div>
                @if($order->cancellationReason)
                    <p class="text-xs text-red-600 mt-0.5">Reason: {{ $order->cancellationReason }}</p>
                @endif
            </div>
        </div>
        @endif

        {{-- Main Two-Column Grid --}}
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 sm:gap-5 items-start">

            {{-- LEFT COLUMN: Items Ordered & Ship To --}}
            <div class="lg:col-span-7 space-y-4 sm:space-y-5">

                {{-- Items Ordered Card --}}
                <div class="bg-white rounded-2xl p-4 sm:p-5 border border-[#EAE6DF] shadow-2xs">
                    <div class="flex items-center justify-between pb-3 mb-3.5 border-b border-gray-100">
                        <div class="text-[11px] font-bold uppercase tracking-wider text-gray-700">
                            Items Ordered ({{ $order->items ? $order->items->count() : 0 }})
                        </div>
                        <span class="inline-flex items-center gap-1 text-[9px] font-bold uppercase tracking-wider text-amber-800 bg-amber-50 px-2 py-0.5 rounded-md border border-amber-200/60">
                            <svg class="w-2.5 h-2.5 text-amber-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/></svg>
                            Heritage Piece
                        </span>
                    </div>

                    <div class="divide-y divide-gray-100">
                        @if($order->items)
                        @foreach($order->items as $item)
                            @php
                                $imgSrc = $item->product ? $item->product->getImageUrl() : asset('uploads/products/default.jpg');
                                $itemStatus = strtolower(trim($order->status ?? ''));
                                $canRate = in_array($itemStatus, ['delivered', 'completed'], true);
                                $existingReview = $order->reviews ? $order->reviews->where('orderItemId', $item->id)->first() : null;
                                if (!$existingReview && $order->reviews) {
                                    $existingReview = $order->reviews->where('productId', $item->productId)->first();
                                }
                            @endphp
                            <div class="py-3 first:pt-0 last:pb-0 flex items-center gap-3 group">
                                {{-- Thumbnail --}}
                                <div class="w-13 h-15 sm:w-14 sm:h-16 rounded-xl overflow-hidden bg-gray-50 border border-gray-200 shrink-0">
                                    <img src="{{ $imgSrc }}" class="w-full h-full object-cover object-top" onerror="this.src='/uploads/products/default.jpg'" alt="{{ $item->product->name ?? 'Product' }}">
                                </div>

                                {{-- Info --}}
                                <div class="flex-1 min-w-0">
                                    <h4 class="text-xs sm:text-sm font-bold text-gray-900 truncate">
                                        {{ $item->product->name ?? 'Heritage Barong Piece' }}
                                    </h4>
                                    <div class="flex flex-wrap items-center gap-1.5 mt-1">
                                        @if($item->size)
                                            <span class="px-2 py-0.5 bg-gray-50 text-gray-700 text-[10px] font-bold rounded-md border border-gray-200">
                                                Size {{ $item->size }}
                                            </span>
                                        @endif
                                        <span class="px-2 py-0.5 bg-gray-50 text-gray-700 text-[10px] font-bold rounded-md border border-gray-200">
                                            Qty {{ $item->quantity }}
                                        </span>
                                    </div>
                                </div>

                                {{-- Price --}}
                                <div class="text-right shrink-0">
                                    <div class="text-xs sm:text-sm font-black text-gray-900">
                                        ₱{{ number_format($item->price * $item->quantity) }}
                                    </div>
                                    <div class="text-[9px] font-bold text-gray-400 mt-0.5">
                                        ₱{{ number_format($item->price) }} each
                                    </div>
                                </div>
                            </div>

                            {{-- Rate / Review control if eligible --}}
                            @if($canRate)
                                <div class="pl-16 pb-2">
                                    @if($existingReview)
                                        <div class="p-2.5 bg-emerald-50/70 rounded-xl border border-emerald-100 text-xs">
                                            <div class="flex items-center justify-between gap-2">
                                                <div class="flex items-center text-amber-400">
                                                    @for($s = 1; $s <= 5; $s++)
                                                        <span class="text-xs">{{ $s <= $existingReview->rating ? '★' : '☆' }}</span>
                                                    @endfor
                                                    <span class="text-[10px] text-emerald-800 ml-1 font-bold">{{ $existingReview->rating }}/5</span>
                                                </div>
                                                <span class="text-[9px] font-extrabold text-emerald-700 uppercase tracking-wider">✓ Verified</span>
                                            </div>
                                            @if($existingReview->comment)
                                                <p class="text-[11px] text-gray-700 italic mt-0.5">&quot;{{ $existingReview->comment }}&quot;</p>
                                            @endif
                                        </div>
                                    @else
                                        <button type="button"
                                            @click="reviewModal = true; reviewProductId = '{{ $item->productId }}'; reviewOrderItemId = '{{ $item->id }}'; reviewProductName = '{{ addslashes($item->product->name ?? 'Product') }}'; reviewProductImage = '{{ $imgSrc }}'"
                                            class="inline-flex items-center gap-1 px-3 py-1 bg-black hover:bg-[#C0420A] text-white rounded-full text-[10px] font-bold uppercase tracking-wider transition-all shadow-2xs cursor-pointer">
                                            <span>⭐ Rate Product</span>
                                        </button>
                                    @endif
                                </div>
                            @endif
                        @endforeach
                        @endif
                    </div>

                    {{-- Total Divider --}}
                    <div class="border-t border-gray-150 my-3.5"></div>

                    <div class="flex items-center justify-between pt-0.5">
                        <span class="text-xs font-bold uppercase tracking-wider text-gray-600">Order Total</span>
                        <span class="text-base sm:text-lg font-black text-[#C0420A]">
                            ₱{{ number_format($order->totalAmount ?? 0, 2) }}
                        </span>
                    </div>
                </div>

                {{-- Ship To Card --}}
                @php
                    $addr = $order->normalized_shipping_address;
                    $recipient = $addr['recipientName'] ?? $addr['fullName'] ?? $addr['name'] ?? 'Customer';
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
                    if (!$locality && !empty($addr['locality'])) {
                        $locality = $addr['locality'];
                    }
                @endphp
                <div class="bg-white rounded-2xl p-4 sm:p-5 border border-[#EAE6DF] shadow-2xs space-y-2">
                    <div class="flex items-center gap-1.5 text-[11px] font-bold uppercase tracking-wider text-gray-500 pb-2 border-b border-gray-100">
                        <svg class="w-3.5 h-3.5 text-[#C0420A]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        <span>Ship To</span>
                    </div>

                    <div class="space-y-1 pt-0.5">
                        <h4 class="text-xs sm:text-sm font-bold text-gray-900">{{ $recipient }}</h4>
                        @if($streetLine)
                            <p class="text-xs text-gray-600 leading-relaxed font-medium">{{ $streetLine }}</p>
                        @endif
                        @if($locality || !empty($addr['postalCode']))
                            <p class="text-xs text-gray-600 leading-relaxed font-medium">
                                {{ $locality }}@if(!empty($addr['postalCode'])) · {{ $addr['postalCode'] }}@endif
                            </p>
                        @endif
                        @if(!empty($addr['phone']))
                            <div class="inline-flex items-center gap-1 px-2.5 py-0.5 bg-gray-50 border border-gray-200 rounded-md text-[11px] font-bold text-gray-700 mt-1.5">
                                <svg class="w-3 h-3 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                <span>{{ $addr['phone'] }}</span>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Confirm Received Button if parcel in transit/delivered --}}
                @if(in_array($statusLower, ['shipped', 'to receive', 'in transit', 'in_transit', 'out for delivery', 'out_for_delivery', 'delivered'], true))
                    <div class="bg-linear-to-r from-emerald-50 to-teal-50 border border-emerald-200 rounded-2xl p-4 flex items-center justify-between gap-3 shadow-2xs">
                        <div>
                            <h4 class="text-xs font-bold text-emerald-900">Has your parcel arrived?</h4>
                            <p class="text-[11px] text-emerald-700 mt-0.5">Please confirm receipt once inspected.</p>
                        </div>
                        <button @click="confirmModal = true"
                            class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold uppercase tracking-wider transition-all shadow-xs active:scale-95 shrink-0">
                            Confirm Received
                        </button>
                    </div>
                @endif
            </div>

            {{-- RIGHT COLUMN: Payment Details & Sold By --}}
            <div class="lg:col-span-5 space-y-4 sm:space-y-5">

                {{-- Payment Details Card --}}
                <div class="bg-white rounded-2xl p-4 sm:p-5 border border-[#EAE6DF] shadow-2xs space-y-3">
                    <div class="flex items-center justify-between pb-2.5 border-b border-gray-100">
                        <div class="text-[11px] font-bold uppercase tracking-wider text-gray-700">Payment Details</div>
                        <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                    </div>

                    <div class="flex items-center justify-between text-xs">
                        <span class="font-bold text-gray-400 uppercase tracking-wider text-[9px]">Method</span>
                        <span class="font-black text-gray-900 text-xs px-2 py-0.5 bg-gray-100 rounded-md">{{ $order->paymentMethod ?? 'GCash' }}</span>
                    </div>

                    <div class="flex items-center justify-between text-xs">
                        <span class="font-bold text-gray-400 uppercase tracking-wider text-[9px]">Status</span>
                        @php
                            $resolvedPayment = $order->resolved_payment_status;
                            $payColor = $resolvedPayment === 'Paid' ? 'text-emerald-700 bg-emerald-50 border-emerald-200' : ($resolvedPayment === 'Failed' ? 'text-red-700 bg-red-50 border-red-200' : 'text-amber-700 bg-amber-50 border-amber-200');
                        @endphp
                        <span class="font-bold text-[10px] uppercase tracking-wider px-2 py-0.5 rounded-full border {{ $payColor }}">{{ $resolvedPayment }}</span>
                    </div>

                    @if($order->paymentReference)
                    <div class="pt-1">
                        <div class="text-[9px] font-bold uppercase tracking-wider text-gray-400 mb-1">Reference Number</div>
                        <div class="bg-gray-50 border border-gray-200 rounded-xl px-3 py-2 flex items-center justify-between group">
                            <span class="font-mono text-xs font-bold text-gray-900 tracking-wider truncate mr-2">{{ $order->paymentReference }}</span>
                            <button type="button" 
                                    @click="navigator.clipboard.writeText('{{ $order->paymentReference }}'); copiedToast = true; setTimeout(() => copiedToast = false, 2500);"
                                    class="p-1 rounded bg-white border border-gray-200 text-gray-400 hover:text-black transition-all cursor-pointer"
                                    title="Copy reference number">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                    @endif

                    @if($order->resolved_payment_status === 'Paid')
                    <div class="p-2.5 bg-emerald-50/70 border border-emerald-100 rounded-xl">
                        <div class="flex items-start gap-2 text-[11px] text-emerald-900 leading-relaxed font-medium">
                            <svg class="w-3.5 h-3.5 text-emerald-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                            <span>Payment confirmed. Orders are final sale. Message the seller for updates.</span>
                        </div>
                    </div>
                    @endif
                </div>

                {{-- Sold By Card --}}
                @if($order->seller)
                <div class="bg-white rounded-2xl p-4 sm:p-5 border border-[#EAE6DF] shadow-2xs space-y-3">
                    <div class="flex items-center justify-between pb-2 border-b border-gray-100">
                        <div class="text-[11px] font-bold uppercase tracking-wider text-gray-500">Sold By</div>
                        <a href="{{ route('shops.show', $order->seller->id) }}" class="text-[11px] font-bold text-[#C0420A] hover:underline flex items-center gap-0.5">
                            <span>Visit shop</span>
                        </a>
                    </div>

                    <div class="flex items-center gap-3 pt-0.5">
                        <div class="w-9 h-9 rounded-xl bg-linear-to-tr from-[#3D2B1F] to-[#C0420A] text-white flex items-center justify-center font-bold text-xs shrink-0 overflow-hidden shadow-2xs">
                            @if($order->seller->profilePhoto)
                                <img src="{{ str_starts_with($order->seller->profilePhoto, 'http') ? $order->seller->profilePhoto : asset('storage/' . $order->seller->profilePhoto) }}"
                                     alt="{{ $order->seller->display_name }}"
                                     class="w-full h-full object-cover" onerror="this.style.display='none'">
                            @else
                                {{ strtoupper(substr($order->seller->display_name ?? 'S', 0, 2)) }}
                            @endif
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="text-xs sm:text-sm font-bold text-gray-900 truncate">{{ $order->seller->display_name }}</div>
                            <div class="text-[10px] text-emerald-700 font-bold flex items-center gap-1 mt-0.5">
                                <svg class="w-3 h-3 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                <span>{{ $order->seller->isVerified ? 'Verified artisan' : 'Artisan seller' }}</span>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                {{-- Packing Proof Photo Card (if uploaded by artisan) --}}
                @if($order->packingProof)
                <div class="bg-white rounded-2xl p-4 border border-[#EAE6DF] shadow-2xs space-y-2.5">
                    <div class="flex items-center justify-between pb-1.5 border-b border-gray-100">
                        <div class="text-[11px] font-bold uppercase tracking-wider text-gray-700">📦 Packing Proof</div>
                        <span class="text-[9px] font-bold text-emerald-700 bg-emerald-50 px-1.5 py-0.5 rounded">By Artisan</span>
                    </div>
                    <div class="aspect-video bg-gray-50 rounded-xl overflow-hidden border border-gray-200 cursor-pointer group relative"
                         @click="packingModalUrl = '{{ asset('storage/' . $order->packingProof) }}'; packingModal = true;">
                        <img src="{{ asset('storage/' . $order->packingProof) }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" alt="Packing proof">
                    </div>
                </div>
                @endif

                {{-- Courier Tracking if dispatched --}}
                @if($order->trackingNumber || $order->trackingLink)
                <div class="bg-white rounded-2xl p-4 border border-[#EAE6DF] shadow-2xs space-y-2.5 text-xs">
                    <div class="text-[11px] font-bold uppercase tracking-wider text-gray-700 pb-1.5 border-b border-gray-100">Courier Tracking</div>
                    @if($order->courierName)
                        <div class="flex justify-between"><span class="text-gray-400 font-bold text-[11px]">Courier:</span><span class="font-bold text-gray-900 text-xs">{{ $order->courierName }}</span></div>
                    @endif
                    @if($order->trackingNumber)
                        <div class="flex justify-between"><span class="text-gray-400 font-bold text-[11px]">Tracking #:</span><span class="font-mono text-[#C0420A] font-bold text-xs">{{ $order->trackingNumber }}</span></div>
                    @endif
                    @if($order->trackingLink)
                        <a href="{{ $order->trackingLink }}" target="_blank" class="block text-center py-2 bg-black hover:bg-[#C0420A] text-white rounded-xl text-xs font-bold uppercase tracking-wider transition-all shadow-2xs">
                            Track Package ↗
                        </a>
                    @endif
                </div>
                @endif

            </div>
        </div>

    </div>

    {{-- Confirm Received Modal --}}
    <div x-show="confirmModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm" x-cloak style="display: none;">
        <div @click.away="confirmModal = false" class="bg-white border border-gray-150 rounded-2xl shadow-2xl w-full max-w-md p-6 space-y-4 text-gray-900">
            <div class="text-center space-y-1.5">
                <div class="w-12 h-12 rounded-full bg-emerald-50 border border-emerald-200 text-emerald-600 flex items-center justify-center mx-auto text-xl font-bold">
                    ✓
                </div>
                <h3 class="text-base font-bold text-gray-900">Confirm Order Received?</h3>
                <p class="text-xs text-gray-500 leading-relaxed">Please only confirm once you have physically received and inspected all items.</p>
            </div>
            <form action="/orders/{{ $order->id }}/confirm" method="POST" class="flex gap-2.5 pt-1">
                @csrf
                @method('PATCH')
                <button type="button" @click="confirmModal = false"
                    class="flex-1 py-2.5 rounded-xl border border-gray-300 text-xs font-bold text-gray-600 hover:bg-gray-50 transition-all">
                    Not Yet
                </button>
                <button type="submit"
                    class="flex-1 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold transition-all shadow-sm">
                    Confirm Received
                </button>
            </form>
        </div>
    </div>

    {{-- Leave Review Modal --}}
    <div x-show="reviewModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm" x-cloak style="display: none;">
        <div @click.away="reviewModal = false" class="bg-white border border-gray-150 rounded-2xl shadow-2xl w-full max-w-md p-6 space-y-3.5 text-gray-900">
            <div class="flex items-center gap-3 pb-2.5 border-b border-gray-100">
                <div class="w-12 h-14 bg-gray-50 rounded-xl overflow-hidden border border-gray-200 shrink-0">
                    <img :src="reviewProductImage || '/uploads/products/default.jpg'" class="w-full h-full object-cover object-top" onerror="this.src='/uploads/products/default.jpg'" :alt="reviewProductName">
                </div>
                <div class="min-w-0 flex-1">
                    <div class="text-[9px] font-bold uppercase tracking-wider text-[#C0420A]">Leave a Review</div>
                    <h3 class="text-xs sm:text-sm font-bold text-gray-900 truncate mt-0.5" x-text="reviewProductName"></h3>
                </div>
            </div>
            <form action="/api/reviews" method="POST" enctype="multipart/form-data" class="space-y-3" x-data="{ rating: 0, hover: 0, photoFiles: [], videoFile: null }" @submit="if (rating === 0) { $event.preventDefault(); alert('Please select a rating of at least 1 star.'); }">
                @csrf
                <input type="hidden" name="productId" :value="reviewProductId">
                <input type="hidden" name="orderId" value="{{ $order->id }}">
                <input type="hidden" name="orderItemId" :value="reviewOrderItemId">
                
                {{-- Star Rating --}}
                <div class="space-y-1">
                    <label class="text-[10px] text-gray-500 font-bold uppercase tracking-wider">Rating <span class="text-red-500">*</span></label>
                    <div class="flex gap-1.5 items-center">
                        @for($i = 1; $i <= 5; $i++)
                            <button type="button"
                                @click="rating = {{ $i }}"
                                @mouseenter="hover = {{ $i }}"
                                @mouseleave="hover = 0"
                                class="text-2xl focus:outline-none transition-transform active:scale-125">
                                <span :class="(hover || rating) >= {{ $i }} ? 'text-amber-400' : 'text-gray-200'">★</span>
                            </button>
                        @endfor
                        <span class="text-xs font-bold text-gray-500 ml-2" x-text="rating > 0 ? rating + '/5' : 'Select'"></span>
                        <input type="hidden" name="rating" :value="rating">
                    </div>
                </div>

                {{-- Comment --}}
                <div class="space-y-1">
                    <label class="text-[10px] text-gray-500 font-bold uppercase tracking-wider">Your Review <span class="text-red-500">*</span></label>
                    <textarea name="comment" rows="3" required placeholder="Share your experience on fit, craftsmanship, and fabric..."
                        class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-xl text-xs text-gray-900 outline-none focus:border-[#C0420A] focus:bg-white transition-all resize-none"></textarea>
                </div>

                {{-- Photo Attachments --}}
                <div class="space-y-1">
                    <label class="text-[10px] text-gray-500 font-bold uppercase tracking-wider flex items-center justify-between">
                        <span>📷 Add Photos</span>
                        <span class="text-[9px] text-gray-400">Max 10MB</span>
                    </label>
                    <input type="file" name="photos[]" multiple accept="image/*"
                        class="w-full text-xs text-gray-500 file:mr-2 file:py-1 file:px-2.5 file:rounded-lg file:border-0 file:text-[10px] file:font-bold file:bg-gray-100 file:text-gray-800 hover:file:bg-[#C0420A] hover:file:text-white transition-all">
                </div>

                <div class="flex gap-2 pt-1">
                    <button type="button" @click="reviewModal = false"
                        class="flex-1 py-2 rounded-xl border border-gray-300 text-xs font-bold text-gray-500 hover:bg-gray-50 transition-all">
                        Cancel
                    </button>
                    <button type="submit"
                        class="flex-1 py-2 rounded-xl bg-black hover:bg-[#C0420A] text-white text-xs font-bold uppercase tracking-wider transition-all shadow-2xs">
                        Submit Review
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Packing Proof Viewer Modal --}}
    <div x-show="packingModal" class="fixed inset-0 z-9999 flex items-center justify-center p-4 bg-black/70 backdrop-blur-md" x-cloak style="display: none;">
        <div @click.away="packingModal = false" class="relative max-w-lg w-full bg-white border border-gray-150 rounded-2xl overflow-hidden shadow-2xl p-5 flex flex-col items-center">
            <div class="w-full flex items-center justify-between pb-2.5 border-b border-gray-100 mb-3">
                <h3 class="text-xs sm:text-sm font-bold text-gray-900">Seller Packing Proof Photo</h3>
                <button type="button" @click="packingModal = false"
                    class="p-1 text-gray-400 hover:text-black transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            
            <div class="w-full bg-gray-50 rounded-xl overflow-hidden flex items-center justify-center border border-gray-200 max-h-[60vh]">
                <img :src="packingModalUrl" class="max-w-full max-h-[55vh] object-contain" alt="Seller Packing Proof">
            </div>
            
            <div class="w-full mt-3.5 flex gap-2">
                <a :href="packingModalUrl" download="packing-proof.jpg" class="flex-1 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 text-center rounded-xl text-xs font-bold transition-all">
                    Download
                </a>
                <button type="button" @click="packingModal = false" class="flex-1 py-2.5 bg-black hover:bg-[#C0420A] text-white rounded-xl text-xs font-bold uppercase tracking-wider transition-all">
                    Close
                </button>
            </div>
        </div>
    </div>

</div>
@endsection
