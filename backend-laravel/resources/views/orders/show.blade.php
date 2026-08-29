@extends('layouts.app')

@section('content')
<div style="min-height:calc(100vh - 80px);background-color:#FAF8F5;padding:24px 16px 48px 16px;"
     x-data="{ 
        confirmModal: false, 
        cancelModal: false,
        cancellationReason: 'Need to change shipping address / details',
        cancelLoading: false,
        reviewModal: false, 
        reviewProductId: null, 
        reviewProductName: '', 
        reviewOrderItemId: null, 
        reviewProductImage: '', 
        copiedToast: false, 
        copiedMessage: 'Copied to clipboard!',
        packingModal: false, 
        packingModalUrl: '',
        lightboxModal: false,
        lightboxType: 'image',
        lightboxUrl: '',
        openLightbox(type, url) {
            if (!url) return;
            this.lightboxType = type;
            this.lightboxUrl = url;
            this.lightboxModal = true;
        },
        closeLightbox() {
            this.lightboxModal = false;
            if (this.$refs.lightboxVideo) {
                try { this.$refs.lightboxVideo.pause(); } catch(e) {}
            }
            this.lightboxUrl = '';
        },
        copyText(val, msg) {
            if (!val) return;
            navigator.clipboard.writeText(val);
            this.copiedMessage = msg || 'Copied to clipboard!';
            this.copiedToast = true;
            setTimeout(() => this.copiedToast = false, 2500);
        }
     }">

    {{-- Toast for clipboard copy feedback --}}
    <div x-show="copiedToast" x-transition:enter="transition ease-out duration-300 transform"
         x-transition:enter-start="opacity-0 translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-200"
         class="fixed top-6 right-6 z-999 bg-[#1E1E1E] text-white text-xs font-bold px-3.5 py-2.5 rounded-xl shadow-2xl flex items-center gap-2 border border-white/10" 
         style="display: none;">
        <svg class="w-3.5 h-3.5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
        <span x-text="copiedMessage"></span>
    </div>

    <div class="max-w-4xl mx-auto space-y-5 sm:space-y-6">

        {{-- Top Navigation --}}
        <div>
            <a href="/orders/my-orders" class="inline-flex items-center gap-2 text-xs font-bold text-[#78716C] hover:text-[#C0422A] transition-colors group">
                <div style="background-color:#FFFFFF;border:1px solid #ECE3D2;box-shadow:0 1px 3px rgba(0,0,0,0.04);" class="w-7 h-7 rounded-full flex items-center justify-center group-hover:border-[#C49520] group-hover:bg-[#1E1915] group-hover:text-white transition-all">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/></svg>
                </div>
                <span class="tracking-wide">Back to my orders</span>
            </a>
        </div>

        {{-- Order Status Header Card --}}
        @php
            $statusLower = strtolower(trim($order->status ?? ''));
            $customerStatusDisplay = match($statusLower) {
                'completed' => 'Completed',
                'delivered' => 'Delivered',
                'in transit', 'in_transit', 'to receive', 'out for delivery', 'out_for_delivery' => 'To Receive',
                'to ship', 'ready to ship', 'ready_to_ship', 'processing', 'shipped' => 'To Ship',
                'cancelled' => 'Cancelled',
                default => 'Order Placed',
            };
            $statusPillClass = match($customerStatusDisplay) {
                'Completed' => 'bg-emerald-50 text-emerald-700 border border-emerald-200',
                'Delivered' => 'bg-teal-50 text-teal-700 border border-teal-200',
                'To Receive' => 'bg-purple-50 text-purple-700 border border-purple-200',
                'To Ship' => 'bg-sky-50 text-sky-700 border border-sky-200',
                'Cancelled' => 'bg-red-50 text-red-700 border border-red-200',
                default => 'bg-amber-50 text-amber-700 border border-amber-200',
            };
        @endphp

        <div style="background-color:#FDFBF7;border:1px solid #EAE2D2;border-radius:24px;box-shadow:0 10px 30px rgba(0,0,0,0.04);padding:22px 26px;"
             class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="flex items-center gap-3.5">
                <div style="width:44px;height:44px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <svg width="42" height="42" viewBox="0 0 48 48" fill="none">
                        <circle cx="24" cy="23" r="10.5" stroke="#C49520" stroke-width="1" stroke-dasharray="2 1.5"/>
                        <circle cx="24" cy="23" r="8.5" stroke="#C49520" stroke-width="0.8"/>
                        <path d="M24 17.5l1.6 3.4 3.7.5-2.7 2.6.6 3.7-3.2-1.7-3.2 1.7.6-3.7-2.7-2.6 3.7-.5L24 17.5z" fill="#C49520"/>
                        <path d="M15 32.5c-4-3.5-6-8.5-6-14 0-3.5 1-6.5 2.5-9" stroke="#C49520" stroke-width="1.3" stroke-linecap="round"/>
                        <path d="M33 32.5c4-3.5 6-8.5 6-14 0-3.5-1-6.5-2.5-9" stroke="#C49520" stroke-width="1.3" stroke-linecap="round"/>
                    </svg>
                </div>
                <div>
                    <div class="flex items-center gap-2 mb-0.5">
                        <span style="display:inline-flex;align-items:center;gap:4px;padding:2px 8px;background:linear-gradient(135deg,#0F0C08 0%,#1C1609 100%);border:1px solid #A87B10;border-radius:10px;color:#DFC97A;font-weight:800;font-size:11px;">
                            #LB-OR-{{ strtoupper(substr($order->id, -8)) }}
                        </span>
                    </div>
                    <p style="font-size:12px;color:#78716C;margin:2px 0 0 0;">
                        Placed {{ $order->createdAt ? $order->createdAt->format('M d, Y \a\t g:i A') : 'Recently' }}
                    </p>
                </div>
            </div>

            <div class="self-start sm:self-center flex items-center gap-2">
                @if($statusLower === 'pending')
                    <button type="button" @click="cancelModal = true" class="px-3.5 py-1.5 bg-white hover:bg-red-50 text-red-600 border border-red-200 text-[10px] sm:text-[11px] font-bold tracking-wider uppercase rounded-full shadow-2xs transition-all cursor-pointer flex items-center gap-1">
                        <svg class="w-3 h-3 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        <span>Cancel Order</span>
                    </button>
                @endif
                <span class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full text-[10px] sm:text-[11px] font-black tracking-wider uppercase shadow-2xs {{ $statusPillClass }}">
                    <span>{{ $customerStatusDisplay }}</span>
                </span>
            </div>
        </div>

        {{-- Shipment Tracking Card (4 Standard Customer Steps) --}}
        @php
            $steps = [
                ['label' => 'Order Placed',  'status' => 'pending'],
                ['label' => 'To Ship',       'status' => 'to ship'],
                ['label' => 'To Receive',    'status' => 'to receive'],
                ['label' => 'Delivered',     'status' => 'delivered'],
            ];

            $statusRanks = [
                'pending'          => 0,
                'processing'       => 1,
                'to ship'          => 1,
                'ready to ship'    => 1,
                'ready_to_ship'    => 1,
                'shipped'          => 1,
                'in transit'       => 2,
                'in_transit'       => 2,
                'to receive'       => 2,
                'out for delivery' => 2,
                'out_for_delivery' => 2,
                'delivered'        => 3,
                'completed'        => 3,
                'cancelled'        => -1,
            ];

            $currentStep = $statusRanks[$statusLower] ?? 0;
            $isCancelled = $statusLower === 'cancelled';

            // Map status history timestamps
            $historyDates = [];
            if ($order->statusHistories) {
                foreach ($order->statusHistories as $h) {
                    $historyDates[strtolower(trim($h->newStatus))] = $h->createdAt ? $h->createdAt->format('g:i A') : null;
                }
            }
        @endphp

        @if(!$isCancelled)
        <div style="background-color:#FDFBF7;border:1px solid #EAE2D2;border-radius:22px;box-shadow:0 4px 16px rgba(0,0,0,0.03);padding:18px 22px;">
            <div class="flex items-center justify-between mb-4 pb-2.5" style="border-bottom:1px solid #EAE1D0;">
                <div class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-[#C49520]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h2m-6 0a1 1 0 01-1-1m8 1a1 1 0 001-1m-6 0h4"/></svg>
                    <span style="font-family:ui-serif,Georgia,serif;font-size:13px;font-weight:700;color:#1E1915;letter-spacing:0.02em;text-transform:uppercase;">Shipment Tracking</span>
                </div>
            </div>

            <div class="grid grid-cols-4 gap-1 sm:gap-2">
                @foreach($steps as $i => $step)
                    @php
                        $isDone = $i <= $currentStep;
                        $isCurrent = $i === $currentStep;
                        $stepKey = strtolower($step['status']);
                        $timeLabel = $historyDates[$stepKey] ?? ($i === 0 && $order->createdAt ? $order->createdAt->format('g:i A') : null);
                        if (!$timeLabel && $stepKey === 'to receive') {
                            $timeLabel = $historyDates['in transit'] ?? ($historyDates['in_transit'] ?? null);
                        }
                    @endphp

                    <div class="flex flex-col items-center text-center">
                        {{-- Step Circle --}}
                        <div class="w-7 h-7 sm:w-8 sm:h-8 rounded-full flex items-center justify-center text-[11px] font-black transition-all {{ $isCurrent ? 'bg-[#1E1915] text-[#DFC97A] border-2 border-[#A87B10] shadow-md' : ($isDone ? 'bg-[#1E1915] text-white border border-[#1E1915]' : 'bg-white border border-[#ECE3D2] text-[#A8A29E]') }}">
                            @if($isDone && !$isCurrent)
                                <svg class="w-3.5 h-3.5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                            @else
                                {{ $i + 1 }}
                            @endif
                        </div>

                        {{-- Step Label --}}
                        <div class="mt-1.5">
                            <span class="text-[10px] sm:text-[11px] font-black block leading-tight uppercase tracking-wider {{ $isCurrent ? 'text-[#C0422A]' : ($isDone ? 'text-[#1E1915]' : 'text-stone-400') }}">
                                {{ $step['label'] }}
                            </span>
                            @if($timeLabel && $isDone)
                                <span class="text-[9px] font-bold text-[#8C827A] block mt-0.5">{{ $timeLabel }}</span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
        @else
        <div style="background-color:#FEF2F2;border:1px solid #FECACA;border-radius:20px;padding:16px 20px;" class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-xl bg-red-100 text-red-600 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path></svg>
            </div>
            <div>
                <div class="text-xs font-black text-red-700 uppercase tracking-wider">Order Cancelled</div>
                @if($order->cancellationReason)
                    <p class="text-xs text-red-600 mt-0.5">Reason: {{ $order->cancellationReason }}</p>
                @endif
            </div>
        </div>
        @endif

        {{-- ROW 1: Items Ordered & Payment Details --}}
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 sm:gap-5 items-stretch">
            {{-- Left: Items Ordered (7 cols) --}}
            <div class="lg:col-span-7 flex flex-col">
                <div style="background-color:#FFFFFF;border:1px solid #ECE3D2;border-radius:22px;box-shadow:0 4px 16px rgba(0,0,0,0.03);padding:20px;" class="h-full flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between pb-3 mb-3.5" style="border-bottom:1px solid #EAE1D0;">
                            <div style="font-family:ui-serif,Georgia,serif;font-size:13px;font-weight:700;color:#1E1915;letter-spacing:0.02em;text-transform:uppercase;">
                                Items Ordered ({{ $order->items ? $order->items->count() : 0 }})
                            </div>
                            <span style="display:inline-flex;align-items:center;gap:4px;padding:2px 8px;background:#FAF5EA;border:1px solid #E6D8BA;border-radius:8px;color:#A87B10;font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:0.06em;">
                                <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="#C49520" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/></svg>
                                Heritage Piece
                            </span>
                        </div>

                        <div class="divide-y divide-[#FAF4EB]">
                            @if($order->items)
                            @foreach($order->items as $item)
                                @php
                                    $variationLabel = $item->display_variation ?? $item->variation;
                                    $imgSrc = \App\Support\VariationFormatter::getImageForVariation($item->variation, $item->product)
                                        ?: ($item->product ? $item->product->getImageUrl() : asset('uploads/products/default.jpg'));
                                    $itemStatus = strtolower(trim($order->status ?? ''));
                                    $canRate = ($itemStatus === 'completed');
                                    $existingReview = $order->reviews ? $order->reviews->where('orderItemId', $item->id)->first() : null;
                                    if (!$existingReview && $order->reviews) {
                                        $existingReview = $order->reviews->where('productId', $item->productId)->first();
                                    }
                                @endphp
                                <div class="py-3.5 first:pt-0 last:pb-0 flex items-center gap-3.5 group">
                                    {{-- Thumbnail --}}
                                    <div class="w-14 h-16 sm:w-16 sm:h-20 rounded-xl overflow-hidden bg-[#FAF8F5] border border-[#ECE3D2] shrink-0 cursor-pointer"
                                         onclick="window.location.href='/products/{{ $item->productId }}'">
                                        <img src="{{ $imgSrc }}" class="w-full h-full object-cover object-top" onerror="this.src='/uploads/products/default.jpg'" alt="{{ $item->product->name ?? 'Product' }}">
                                    </div>

                                    {{-- Info --}}
                                    <div class="flex-1 min-w-0">
                                        <h4 class="text-xs sm:text-sm font-extrabold text-[#1E1915] truncate uppercase tracking-tight">
                                            <a href="/products/{{ $item->productId }}" class="hover:text-[#C0422A] transition-colors">
                                                {{ $item->product->name ?? 'Heritage Barong Piece' }}
                                            </a>
                                        </h4>
                                        <div class="flex flex-wrap items-center gap-2 mt-1.5">
                                            @if($item->size)
                                                <span class="px-2 py-0.5 bg-[#FAF8F5] text-[#1E1915] text-[10px] font-bold rounded-md border border-[#ECE3D2]">
                                                    Size {{ $item->size }}
                                                </span>
                                            @endif
                                            @if(!empty($variationLabel) && strcasecmp($variationLabel, 'Original') !== 0)
                                                <span class="px-2 py-0.5 bg-[#FAF5EA] text-[#996515] text-[10px] font-bold rounded-md border border-[#E6D8BA]">
                                                    Style: {{ $variationLabel }}
                                                </span>
                                            @endif
                                            <span class="px-2 py-0.5 bg-[#FAF8F5] text-[#78716C] text-[10px] font-bold rounded-md border border-[#ECE3D2]">
                                                Qty {{ $item->quantity }}
                                            </span>
                                        </div>
                                    </div>

                                    {{-- Price --}}
                                    <div class="text-right shrink-0">
                                        <div class="text-xs sm:text-base font-black text-[#1E1915]">
                                            ₱{{ number_format($item->price * $item->quantity) }}
                                        </div>
                                        <div class="text-[9px] font-bold text-[#8C827A] mt-0.5">
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
                                                style="background-color:#1E1915;color:#FFFFFF;border:1px solid #1E1915;"
                                                class="inline-flex items-center gap-1.5 px-4 py-1.5 hover:bg-[#C0422A] rounded-full text-[10px] font-black uppercase tracking-wider transition-all shadow-2xs cursor-pointer active:scale-95">
                                                <span>⭐ Rate Product</span>
                                            </button>
                                        @endif
                                    </div>
                                @endif
                            @endforeach
                            @endif
                        </div>
                    </div>

                    {{-- Total Divider --}}
                    <div>
                        <div class="border-t border-[#ECE3D2] my-3.5"></div>
                        <div class="flex items-center justify-between pt-0.5">
                            <span class="text-xs font-bold uppercase tracking-wider text-[#78716C]">Order Total</span>
                            <span class="text-base sm:text-xl font-black text-[#C0422A]">
                                ₱{{ number_format($order->totalAmount ?? 0, 2) }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Right: Payment Details (5 cols) --}}
            <div class="lg:col-span-5 flex flex-col">
                <div style="background-color:#FFFFFF;border:1px solid #ECE3D2;border-radius:22px;box-shadow:0 4px 16px rgba(0,0,0,0.03);padding:20px;" class="space-y-3 h-full flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between pb-2.5" style="border-bottom:1px solid #EAE1D0;">
                            <div style="font-family:ui-serif,Georgia,serif;font-size:13px;font-weight:700;color:#1E1915;letter-spacing:0.02em;text-transform:uppercase;">Payment Details</div>
                            <svg class="w-4 h-4 text-[#C49520]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                        </div>

                        <div class="space-y-3 pt-2">
                            <div class="flex items-center justify-between text-xs">
                                <span class="font-bold text-[#8C827A] uppercase tracking-wider text-[10px]">Method</span>
                                <span class="font-black text-[#1E1915] text-xs px-2.5 py-1 bg-[#FAF8F5] rounded-lg border border-[#ECE3D2] uppercase">{{ $order->paymentMethod ?? 'GCash' }}</span>
                            </div>

                            <div class="flex items-center justify-between text-xs">
                                <span class="font-bold text-[#8C827A] uppercase tracking-wider text-[10px]">Payment Status</span>
                                @php
                                    $resolvedPayment = $order->resolved_payment_status;
                                    $payClass = ($resolvedPayment === 'Verified' || $resolvedPayment === 'Paid')
                                        ? 'bg-emerald-50 text-emerald-700 border border-emerald-200'
                                        : (($resolvedPayment === 'Payment Rejected' || $resolvedPayment === 'Failed')
                                            ? 'bg-red-50 text-red-700 border border-red-200'
                                            : 'bg-amber-50 text-amber-700 border border-amber-200');
                                @endphp
                                <span class="font-black text-[10px] uppercase tracking-wider px-2.5 py-0.5 rounded-full {{ $payClass }}">
                                    @if($resolvedPayment === 'Payment Submitted')
                                        ⏳ Awaiting Verification
                                    @elseif($resolvedPayment === 'Verified' || $resolvedPayment === 'Paid')
                                        ✓ Verified
                                    @elseif($resolvedPayment === 'Payment Rejected')
                                        ✕ Payment Rejected
                                    @else
                                        {{ $resolvedPayment }}
                                    @endif
                                </span>
                            </div>

                            @if($order->paymentReference)
                            <div class="pt-1">
                                <div class="text-[9px] font-bold uppercase tracking-wider text-[#8C827A] mb-1">Reference Number</div>
                                <div class="bg-[#FAF8F5] border border-[#ECE3D2] rounded-xl px-3 py-2 flex items-center justify-between group">
                                    <span class="font-mono text-xs font-bold text-[#1E1915] tracking-wider truncate mr-2">{{ $order->paymentReference }}</span>
                                    <button type="button" 
                                            @click="navigator.clipboard.writeText('{{ $order->paymentReference }}'); copiedToast = true; setTimeout(() => copiedToast = false, 2500);"
                                            class="p-1 rounded bg-white border border-[#ECE3D2] text-[#78716C] hover:text-[#1E1915] transition-all cursor-pointer"
                                            title="Copy reference number">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>

                    @if($order->resolved_payment_status === 'Verified' || $order->resolved_payment_status === 'Paid')
                    <div class="p-3 bg-emerald-50/80 border border-emerald-200 rounded-xl mt-2">
                        <div class="flex items-start gap-2 text-[11px] text-emerald-900 leading-relaxed font-bold">
                            <svg class="w-4 h-4 text-emerald-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                            <span>Payment verified by artisan. Your order is queued for handcrafting.</span>
                        </div>
                    </div>
                    @elseif($order->resolved_payment_status === 'Payment Rejected')
                    <div class="p-3 bg-red-50 border border-red-200 rounded-xl mt-2 space-y-1.5">
                        <div class="flex items-start gap-2 text-[11px] text-red-900 font-bold">
                            <svg class="w-4 h-4 text-red-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <span>Payment Rejected by Artisan</span>
                        </div>
                        @if($order->paymentRejectionReason)
                            <p class="text-[10px] text-red-700 leading-tight">Reason: {{ $order->paymentRejectionReason }}</p>
                        @endif
                    </div>
                    @else
                    <div class="p-3 bg-amber-50/80 border border-amber-200 rounded-xl mt-2">
                        <div class="flex items-start gap-2 text-[11px] text-amber-900 leading-relaxed font-medium">
                            <span class="text-xs">⏳</span>
                            <span>Payment proof submitted. The artisan will verify the payment against their wallet before starting work.</span>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- ROW 2: Ship To & Sold By --}}
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 sm:gap-5 items-stretch">
            {{-- Left: Ship To (7 cols) --}}
            <div class="{{ $order->seller ? 'lg:col-span-7' : 'lg:col-span-12' }} flex flex-col">
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
                <div style="background-color:#FFFFFF;border:1px solid #ECE3D2;border-radius:22px;box-shadow:0 4px 16px rgba(0,0,0,0.03);padding:20px;" class="space-y-2 h-full flex flex-col justify-between">
                    <div>
                        <div class="flex items-center gap-2 text-[11px] font-bold uppercase tracking-wider text-[#78716C] pb-2" style="border-bottom:1px solid #EAE1D0;">
                            <svg class="w-3.5 h-3.5 text-[#C49520]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            <span>Ship To</span>
                        </div>

                        <div class="space-y-1 pt-2">
                            <h4 class="text-xs sm:text-sm font-extrabold text-[#1E1915]">{{ $recipient }}</h4>
                            @if($streetLine)
                                <p class="text-xs text-[#78716C] leading-relaxed font-medium">{{ $streetLine }}</p>
                            @endif
                            @if($locality || !empty($addr['postalCode']))
                                <p class="text-xs text-[#78716C] leading-relaxed font-medium">
                                    {{ $locality }}@if(!empty($addr['postalCode'])) · {{ $addr['postalCode'] }}@endif
                                </p>
                            @endif
                        </div>
                    </div>

                    @if(!empty($addr['phone']))
                        <div class="pt-2">
                            <div class="inline-flex items-center gap-1.5 px-3 py-1 bg-[#FAF8F5] border border-[#ECE3D2] rounded-lg text-[11px] font-bold text-[#1E1915]">
                                <svg class="w-3 h-3 text-[#78716C]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                <span>{{ $addr['phone'] }}</span>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Right: Sold By (5 cols) --}}
            @if($order->seller)
            <div class="lg:col-span-5 flex flex-col">
                <div style="background-color:#FFFFFF;border:1px solid #ECE3D2;border-radius:22px;box-shadow:0 4px 16px rgba(0,0,0,0.03);padding:20px;" class="space-y-3 h-full flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between pb-2" style="border-bottom:1px solid #EAE1D0;">
                            <div class="text-[11px] font-bold uppercase tracking-wider text-[#78716C]">Sold By</div>
                            <a href="{{ route('shops.show', $order->seller->id) }}" class="text-[11px] font-bold text-[#C0422A] hover:underline flex items-center gap-0.5">
                                <span>Visit shop</span>
                            </a>
                        </div>

                        <div class="flex items-center gap-3 pt-3">
                            <div class="w-11 h-11 rounded-xl bg-linear-to-tr from-[#3D2B1F] to-[#C0422A] text-white flex items-center justify-center font-bold text-xs shrink-0 overflow-hidden shadow-2xs border border-[#ECE3D2]">
                                @if($order->seller->profile_photo_url)
                                    <img src="{{ $order->seller->profile_photo_url }}"
                                         alt="{{ $order->seller->display_name }}"
                                         class="w-full h-full object-cover" onerror="this.src='/uploads/products/default.jpg'">
                                @else
                                    <img src="{{ asset('uploads/products/default.jpg') }}"
                                         alt="{{ $order->seller->display_name }}"
                                         class="w-full h-full object-cover">
                                @endif
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="text-xs sm:text-sm font-extrabold text-[#1E1915] truncate">{{ $order->seller->display_name }}</div>
                                <div class="text-[10px] text-emerald-700 font-bold flex items-center gap-1 mt-0.5">
                                    <svg class="w-3 h-3 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                    <span>{{ $order->seller->isVerified ? 'Verified artisan' : 'Artisan seller' }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endif
        </div>

        {{-- ROW 3: Packing Proof & Courier Tracking --}}
        @php
            $isPendingWithoutProof = in_array($statusLower, ['pending', 'order placed', 'order_placed']) && empty($order->packingProof);
            $hasCourierTracking = in_array(strtolower(str_replace('_', ' ', $order->status)), ['in transit', 'out for delivery', 'delivered', 'completed']) && !empty(trim($order->trackingNumber ?? ''));
        @endphp

        @if(!$isCancelled && !$isPendingWithoutProof)

            @if($hasCourierTracking)
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 sm:gap-5 items-stretch">
                    {{-- Packing Proof --}}
                    <div class="lg:col-span-7 flex flex-col">
                        <div style="background-color:#FFFFFF;border:1px solid #ECE3D2;border-radius:22px;box-shadow:0 4px 16px rgba(0,0,0,0.03);padding:20px;" class="space-y-3 h-full flex flex-col justify-between">
                            <div>
                                <div class="flex items-center justify-between pb-2" style="border-bottom:1px solid #EAE1D0;">
                                    <div class="flex items-center gap-2">
                                        <span class="text-base">📦</span>
                                        <span class="text-[11px] font-bold uppercase tracking-wider text-[#1E1915]">Artisan Packing Proof</span>
                                    </div>
                                    <span class="text-[10px] font-black text-emerald-700 bg-emerald-50 border border-emerald-200 px-2.5 py-0.5 rounded-full flex items-center gap-1">
                                        <svg class="w-3 h-3 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                        Verified by Artisan
                                    </span>
                                </div>

                                @if($order->packingProof)
                                    <div class="mt-3 relative rounded-xl overflow-hidden border border-[#ECE3D2] bg-[#FAF8F5] cursor-pointer group flex items-center justify-center max-h-56"
                                         @click="packingModalUrl = '{{ $order->packing_proof_url }}'; packingModal = true;">
                                        <img src="{{ $order->packing_proof_url }}" class="w-full h-full max-h-56 object-cover group-hover:scale-105 transition-transform duration-300" alt="Packing proof photo">
                                        <div class="absolute inset-0 bg-black/20 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                                            <span class="px-3 py-1.5 bg-black/80 text-white rounded-lg text-xs font-bold backdrop-blur-xs flex items-center gap-1">
                                                🔍 Click to zoom
                                            </span>
                                        </div>
                                    </div>
                                @else
                                    <div class="p-4 bg-amber-50/80 border border-amber-200 rounded-xl mt-3 text-xs text-amber-800">
                                        The artisan is preparing your package. A verified packing photo will appear here once ready.
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- Courier Tracking --}}
                    <div class="lg:col-span-5 flex flex-col">
                        <div style="background-color:#FFFFFF;border:1px solid #ECE3D2;border-radius:22px;box-shadow:0 4px 16px rgba(0,0,0,0.03);padding:20px;" class="space-y-3 text-xs h-full flex flex-col justify-between">
                            <div>
                                <div class="flex items-center justify-between pb-2" style="border-bottom:1px solid #EAE1D0;">
                                    <div class="flex items-center gap-1.5">
                                        <span class="text-base">🚚</span>
                                        <span class="text-[11px] font-bold uppercase tracking-wider text-[#1E1915]">Courier Tracking</span>
                                    </div>
                                    <span class="text-[9px] font-black text-purple-700 bg-purple-50 border border-purple-200 px-2.5 py-0.5 rounded-full uppercase tracking-wider">In Transit</span>
                                </div>

                                <div class="space-y-2.5 pt-2">
                                    @if($order->courierName)
                                        <div class="flex justify-between items-center text-xs">
                                            <span class="text-[#8C827A] font-bold uppercase tracking-wider text-[9px]">Courier</span>
                                            <span class="font-black text-[#1E1915]">{{ $order->courierName }}</span>
                                        </div>
                                    @endif

                                    @if($order->trackingNumber)
                                        <div class="flex justify-between items-center bg-[#FAF8F5] p-2.5 rounded-xl border border-[#ECE3D2]">
                                            <div>
                                                <span class="text-[#8C827A] font-bold text-[9px] uppercase tracking-wider block">Tracking Number</span>
                                                <span class="font-mono text-[#C0422A] font-bold text-xs">{{ $order->trackingNumber }}</span>
                                            </div>
                                            <button type="button" 
                                                    @click="copyText('{{ $order->trackingNumber }}', 'Tracking number copied!')"
                                                    class="px-2.5 py-1.5 bg-white border border-[#ECE3D2] hover:border-[#C49520] hover:text-[#1E1915] rounded-lg text-[10px] font-bold uppercase tracking-wider shadow-2xs flex items-center gap-1 transition-all active:scale-95 cursor-pointer"
                                                    title="Copy tracking number">
                                                <svg class="w-3.5 h-3.5 text-[#78716C]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                                </svg>
                                                <span>Copy</span>
                                            </button>
                                        </div>
                                    @endif
                                </div>
                            </div>

                            @if($order->trackingLink)
                                <div class="space-y-1 pt-2">
                                    <a href="{{ $order->trackingLink }}" target="_blank" rel="noopener noreferrer" 
                                       style="background-color:#1E1915;color:#FFFFFF;border:1px solid #1E1915;"
                                       class="block text-center py-2.5 hover:bg-[#C0422A] rounded-xl text-xs font-bold uppercase tracking-wider transition-all shadow-2xs cursor-pointer">
                                        Track on {{ $order->courierName ?? 'Courier Site' }} ↗
                                    </a>
                                    <p class="text-[9px] text-[#8C827A] text-center">Click to open courier portal and paste your copied tracking number.</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @else
                <div class="w-full">
                    <div style="background-color:#FFFFFF;border:1px solid #ECE3D2;border-radius:22px;box-shadow:0 4px 16px rgba(0,0,0,0.03);padding:20px;" class="space-y-3">
                        <div class="flex items-center justify-between pb-2" style="border-bottom:1px solid #EAE1D0;">
                            <div class="flex items-center gap-2">
                                <span class="text-base">📦</span>
                                <span class="text-[11px] font-bold uppercase tracking-wider text-[#1E1915]">Artisan Packing Verification</span>
                            </div>
                            @if($order->packingProof)
                                <span class="text-[10px] font-black text-emerald-700 bg-emerald-50 border border-emerald-200 px-2.5 py-0.5 rounded-full flex items-center gap-1">
                                    <svg class="w-3 h-3 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                    Verified by Artisan
                                </span>
                            @else
                                <span class="text-[10px] font-black text-amber-700 bg-amber-50 border border-amber-200 px-2.5 py-0.5 rounded-full">
                                    Preparing Package
                                </span>
                            @endif
                        </div>

                        @if($order->packingProof)
                            <div class="grid grid-cols-1 sm:grid-cols-12 gap-4 items-center pt-1">
                                <div class="sm:col-span-5 md:col-span-4 relative rounded-xl overflow-hidden border border-[#ECE3D2] bg-[#FAF8F5] cursor-pointer group aspect-video max-h-48 flex items-center justify-center"
                                     @click="packingModalUrl = '{{ $order->packing_proof_url }}'; packingModal = true;">
                                    <img src="{{ $order->packing_proof_url }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" alt="Packing proof">
                                    <div class="absolute inset-0 bg-black/25 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                                        <span class="px-3 py-1 bg-black/80 text-white rounded-lg text-xs font-bold backdrop-blur-xs flex items-center gap-1">
                                            🔍 Click to zoom
                                        </span>
                                    </div>
                                </div>

                                <div class="sm:col-span-7 md:col-span-8 space-y-2">
                                    <h4 class="text-xs sm:text-sm font-extrabold text-[#1E1915]">Your parcel has been packed & inspected!</h4>
                                    <p class="text-xs text-[#78716C] leading-relaxed font-medium">
                                        The artisan has inspected and packed your heritage item with care. Once dispatched to the courier, your live tracking details will appear automatically.
                                    </p>
                                    <div>
                                        <button type="button" 
                                                @click="packingModalUrl = '{{ $order->packing_proof_url }}'; packingModal = true;"
                                                class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-[#FAF8F5] hover:bg-[#FAF5EA] text-[#1E1915] rounded-lg text-xs font-bold transition-all cursor-pointer border border-[#ECE3D2]">
                                            <svg class="w-3.5 h-3.5 text-[#78716C]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                            <span>View Full-Size Photo Proof</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @else
                            <div class="p-3.5 bg-[#FAF8F5] border border-[#ECE3D2] rounded-xl flex items-start gap-2.5 text-xs text-[#78716C]">
                                <span class="text-base">⏳</span>
                                <div>
                                    <span class="font-extrabold text-[#1E1915] block">Artisan is preparing your package</span>
                                    <span class="text-[11px] text-[#78716C] mt-0.5 block">A verified photograph of your packed piece will be uploaded here by the artisan prior to courier dispatch.</span>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            @endif
        @endif

        {{-- ROW 4: Confirm Received Banner (when delivered) --}}
        @if(in_array($statusLower, ['delivered'], true))
            <div class="w-full">
                <div class="bg-linear-to-r from-emerald-50 to-teal-50 border border-emerald-200 rounded-2xl p-4 flex items-center justify-between gap-3 shadow-2xs">
                    <div>
                        <h4 class="text-xs font-bold text-emerald-900">Has your parcel arrived?</h4>
                        <p class="text-[11px] text-emerald-700 mt-0.5">Please confirm receipt once inspected.</p>
                    </div>
                    <button @click="confirmModal = true"
                        class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold uppercase tracking-wider transition-all shadow-xs active:scale-95 shrink-0 cursor-pointer">
                        Confirm Received
                    </button>
                </div>
            </div>
        @endif

    </div>

    {{-- Confirm Received Modal --}}
    <div x-show="confirmModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm" x-cloak style="display: none;">
        <div @click.away="confirmModal = false" style="background-color:#FDFBF7;border:1px solid #EAE2D2;border-radius:28px;box-shadow:0 25px 60px rgba(0,0,0,0.25);" class="w-full max-w-md p-6 space-y-4 text-gray-900">
            <div class="text-center space-y-1.5">
                <div class="w-12 h-12 rounded-full bg-emerald-50 border border-emerald-200 text-emerald-600 flex items-center justify-center mx-auto text-xl font-bold">
                    ✓
                </div>
                <h3 class="text-base font-bold text-[#1E1915]">Confirm Order Received?</h3>
                <p class="text-xs text-[#78716C] leading-relaxed">Please only confirm once you have physically received and inspected all items.</p>
            </div>
            <form action="/orders/{{ $order->id }}/confirm" method="POST" class="flex gap-2.5 pt-1">
                @csrf
                @method('PATCH')
                <button type="button" @click="confirmModal = false"
                    class="flex-1 py-2.5 rounded-full border border-[#ECE3D2] text-xs font-bold text-[#78716C] hover:bg-[#FAF8F5] transition-all">
                    Not Yet
                </button>
                <button type="submit"
                    class="flex-1 py-2.5 rounded-full bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold transition-all shadow-sm">
                    Confirm Received
                </button>
            </form>
        </div>
    </div>

    {{-- Leave Review Modal --}}
    <div x-show="reviewModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm" x-cloak style="display: none;">
        <div @click.away="reviewModal = false" style="background-color:#FDFBF7;border:1px solid #EAE2D2;border-radius:28px;box-shadow:0 25px 60px rgba(0,0,0,0.25);" class="w-full max-w-md p-6 space-y-3.5 text-gray-900">
            <div class="flex items-center gap-3 pb-2.5" style="border-bottom:1px solid #EAE1D0;">
                <div class="w-12 h-14 bg-[#FAF8F5] rounded-xl overflow-hidden border border-[#ECE3D2] shrink-0">
                    <img :src="reviewProductImage || '/uploads/products/default.jpg'" class="w-full h-full object-cover object-top" onerror="this.src='/uploads/products/default.jpg'" :alt="reviewProductName">
                </div>
                <div class="min-w-0 flex-1">
                    <div class="text-[9px] font-bold uppercase tracking-wider text-[#C0422A]">Leave a Review</div>
                    <h3 class="text-xs sm:text-sm font-extrabold text-[#1E1915] truncate mt-0.5" x-text="reviewProductName"></h3>
                </div>
            </div>
            <form action="/api/reviews" method="POST" enctype="multipart/form-data" class="space-y-3.5" 
                  x-data="{ 
                      rating: 0, 
                      hover: 0, 
                      photoPreviews: [], 
                      videoPreview: null, 
                      videoName: '', 
                      photoError: '', 
                      videoError: '',
                      handlePhotos(e) {
                          this.photoError = '';
                          const files = Array.from(e.target.files);
                          if (this.photoPreviews.length + files.length > 3) {
                              this.photoError = 'Maximum of 3 photos allowed.';
                              e.target.value = '';
                              return;
                          }
                          files.forEach(f => {
                              if (this.photoPreviews.length < 3) {
                                  if (f.size > 10 * 1024 * 1024) {
                                      this.photoError = 'Each image must be under 10MB.';
                                      return;
                                  }
                                  this.photoPreviews.push({
                                      file: f,
                                      url: URL.createObjectURL(f),
                                      name: f.name
                                  });
                              }
                          });
                          this.syncPhotoInput();
                      },
                      removePhoto(idx) {
                          if (this.photoPreviews[idx]) {
                              URL.revokeObjectURL(this.photoPreviews[idx].url);
                              this.photoPreviews.splice(idx, 1);
                              this.photoError = '';
                              this.syncPhotoInput();
                          }
                      },
                      syncPhotoInput() {
                          const dt = new DataTransfer();
                          this.photoPreviews.forEach(p => dt.items.add(p.file));
                          if (this.$refs.photoInput) {
                              this.$refs.photoInput.files = dt.files;
                          }
                      },
                      handleVideo(e) {
                          this.videoError = '';
                          const file = e.target.files[0];
                          if (!file) return;
                          if (file.size > 50 * 1024 * 1024) {
                              this.videoError = 'Video must be less than 50MB.';
                              e.target.value = '';
                              return;
                          }
                          if (this.videoPreview) {
                              URL.revokeObjectURL(this.videoPreview);
                          }
                          this.videoPreview = URL.createObjectURL(file);
                          this.videoName = file.name;
                      },
                      removeVideo() {
                          if (this.videoPreview) {
                              URL.revokeObjectURL(this.videoPreview);
                              this.videoPreview = null;
                              this.videoName = '';
                          }
                          if (this.$refs.videoInput) {
                              this.$refs.videoInput.value = '';
                          }
                          this.videoError = '';
                      }
                  }" 
                  @submit="if (rating === 0) { $event.preventDefault(); alert('Please select a rating of at least 1 star.'); }">
                @csrf
                <input type="hidden" name="productId" :value="reviewProductId">
                <input type="hidden" name="orderId" value="{{ $order->id }}">
                <input type="hidden" name="orderItemId" :value="reviewOrderItemId">
                
                {{-- Star Rating --}}
                <div class="space-y-1">
                    <label class="text-[10px] text-[#78716C] font-bold uppercase tracking-wider">Rating <span class="text-red-500">*</span></label>
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
                        <span class="text-xs font-bold text-[#78716C] ml-2" x-text="rating > 0 ? rating + '/5' : 'Select'"></span>
                        <input type="hidden" name="rating" :value="rating">
                    </div>
                </div>

                {{-- Comment --}}
                <div class="space-y-1">
                    <label class="text-[10px] text-[#78716C] font-bold uppercase tracking-wider">Your Review <span class="text-red-500">*</span></label>
                    <textarea name="comment" rows="3" required placeholder="Share your experience on fit, craftsmanship, and fabric..."
                        class="w-full px-3 py-2 bg-[#FAF8F5] border border-[#ECE3D2] rounded-xl text-xs text-[#1E1915] outline-none focus:border-[#C49520] focus:bg-white transition-all resize-none"></textarea>
                </div>

                {{-- Photo Attachments (Up to 3 images) --}}
                <div class="space-y-1.5 pt-1" style="border-top:1px solid #EAE1D0;">
                    <div class="flex items-center justify-between">
                        <label class="text-[10px] text-[#1E1915] font-bold uppercase tracking-wider flex items-center gap-1">
                            <span>📷 Add Photos</span>
                            <span class="text-stone-400 font-normal" x-text="'(' + photoPreviews.length + '/3)'"></span>
                        </label>
                        <span class="text-[9px] text-[#8C827A]">Max 3 images • 10MB each</span>
                    </div>

                    <input type="file" name="photos[]" multiple accept="image/*" x-ref="photoInput" class="hidden" @change="handlePhotos($event)">

                    <div class="flex flex-wrap items-center gap-2">
                        {{-- Thumbnails --}}
                        <template x-for="(photo, index) in photoPreviews" :key="index">
                            <div class="relative w-14 h-14 rounded-xl border border-[#ECE3D2] overflow-hidden group bg-[#FAF8F5] shrink-0 shadow-2xs">
                                <img :src="photo.url" @click="openLightbox('image', photo.url)" class="w-full h-full object-cover cursor-pointer hover:opacity-90 transition-opacity" title="Click to view full image">
                                <button type="button" @click.stop="removePhoto(index)" class="absolute top-0.5 right-0.5 w-4 h-4 rounded-full bg-red-600 hover:bg-red-700 text-white flex items-center justify-center text-[9px] font-bold shadow-xs cursor-pointer">
                                    ✕
                                </button>
                            </div>
                        </template>

                        {{-- Add Button --}}
                        <template x-if="photoPreviews.length < 3">
                            <button type="button" @click="$refs.photoInput.click()" class="w-14 h-14 rounded-xl border-2 border-dashed border-stone-300 hover:border-[#C49520] bg-[#FAF8F5] flex flex-col items-center justify-center text-[#8C827A] hover:text-[#1E1915] transition-all cursor-pointer shrink-0">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                <span class="text-[8px] font-bold uppercase mt-0.5">Photo</span>
                            </button>
                        </template>
                    </div>
                    <template x-if="photoError">
                        <p class="text-[10px] font-bold text-red-600" x-text="photoError"></p>
                    </template>
                </div>

                {{-- Video Attachment (1 Video) --}}
                <div class="space-y-1.5 pt-1" style="border-top:1px solid #EAE1D0;">
                    <div class="flex items-center justify-between">
                        <label class="text-[10px] text-[#1E1915] font-bold uppercase tracking-wider flex items-center gap-1">
                            <span>🎥 Add Video</span>
                            <span class="text-stone-400 font-normal">(1 video)</span>
                        </label>
                        <span class="text-[9px] text-[#8C827A]">Max 50MB (MP4, MOV, WEBM)</span>
                    </div>

                    <input type="file" name="video" accept="video/*" x-ref="videoInput" class="hidden" @change="handleVideo($event)">

                    <template x-if="!videoPreview">
                        <button type="button" @click="$refs.videoInput.click()" class="w-full py-2 px-3 rounded-xl border border-dashed border-stone-300 hover:border-[#C49520] bg-[#FAF8F5] text-[#78716C] hover:text-[#1E1915] text-xs font-bold transition-all flex items-center justify-center gap-2 cursor-pointer">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                            <span>Attach 1 Video</span>
                        </button>
                    </template>

                    <template x-if="videoPreview">
                        <div class="p-2 bg-gray-900 rounded-xl flex items-center justify-between gap-2 text-white">
                            <div class="flex items-center gap-2 min-w-0 cursor-pointer" @click="openLightbox('video', videoPreview)">
                                <div class="w-8 h-8 rounded-lg bg-black/60 border border-white/20 flex items-center justify-center shrink-0 text-[10px]">
                                    ▶
                                </div>
                                <div class="min-w-0">
                                    <p class="text-[11px] font-bold truncate text-white" x-text="videoName || 'Attached Video'"></p>
                                    <span class="text-[9px] text-orange-400 font-semibold">🔍 Preview Video</span>
                                </div>
                            </div>
                            <button type="button" @click="removeVideo()" class="px-2 py-1 rounded-lg bg-red-600/80 hover:bg-red-600 text-white text-[10px] font-bold transition-colors cursor-pointer shrink-0">
                                ✕ Remove
                            </button>
                        </div>
                    </template>
                    <template x-if="videoError">
                        <p class="text-[10px] font-bold text-red-600" x-text="videoError"></p>
                    </template>
                </div>

                <div class="flex gap-2 pt-2" style="border-top:1px solid #EAE1D0;">
                    <button type="button" @click="reviewModal = false"
                        class="flex-1 py-2.5 rounded-full border border-[#ECE3D2] text-xs font-bold text-[#78716C] hover:bg-[#FAF8F5] transition-all">
                        Cancel
                    </button>
                    <button type="submit"
                        style="background-color:#1E1915;color:#FFFFFF;"
                        class="flex-1 py-2.5 rounded-full hover:bg-[#C0422A] text-xs font-bold uppercase tracking-wider transition-all shadow-2xs">
                        Submit Review
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Packing Proof Viewer Modal --}}
    <div x-show="packingModal" class="fixed inset-0 z-9999 flex items-center justify-center p-4 bg-black/70 backdrop-blur-md" x-cloak style="display: none;">
        <div @click.away="packingModal = false" style="background-color:#FDFBF7;border:1px solid #EAE2D2;border-radius:28px;box-shadow:0 25px 60px rgba(0,0,0,0.25);" class="relative max-w-lg w-full overflow-hidden p-6 flex flex-col items-center">
            <div class="w-full flex items-center justify-between pb-2.5 mb-3" style="border-bottom:1px solid #EAE1D0;">
                <h3 class="text-xs sm:text-sm font-bold text-[#1E1915]">Seller Packing Proof Photo</h3>
                <button type="button" @click="packingModal = false"
                    class="p-1 text-[#8C827A] hover:text-black transition-colors cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            
            <div class="w-full bg-[#FAF8F5] rounded-2xl overflow-hidden flex items-center justify-center border border-[#ECE3D2] max-h-[60vh]">
                <img :src="packingModalUrl" class="max-w-full max-h-[55vh] object-contain" alt="Seller Packing Proof">
            </div>
            
            <div class="w-full mt-4 flex gap-2.5">
                <a :href="packingModalUrl" download="packing-proof.jpg" class="flex-1 py-2.5 bg-[#FAF5EA] hover:bg-[#EAE2D2] text-[#1E1915] text-center rounded-full text-xs font-bold transition-all border border-[#ECE3D2]">
                    Download
                </a>
                <button type="button" @click="packingModal = false" style="background-color:#1E1915;color:#FFFFFF;" class="flex-1 py-2.5 hover:bg-[#C0422A] rounded-full text-xs font-bold uppercase tracking-wider transition-all cursor-pointer">
                    Close
                </button>
            </div>
        </div>
    </div>

    {{-- Media Lightbox Overlay --}}
    <div x-show="lightboxModal" 
         class="fixed inset-0 z-99999 flex items-center justify-center p-3 sm:p-6 bg-black/85 backdrop-blur-md"
         x-cloak 
         @keydown.escape.window="closeLightbox()"
         style="display: none;">
        <div @click.away="closeLightbox()" class="relative max-w-4xl w-full flex flex-col items-center justify-center">
            <button type="button" 
                    @click="closeLightbox()" 
                    class="absolute -top-12 right-0 sm:-right-2 w-10 h-10 rounded-full bg-white/20 hover:bg-white text-white hover:text-black flex items-center justify-center text-lg font-black backdrop-blur-md transition-all cursor-pointer shadow-lg z-20">
                ✕
            </button>

            <div class="w-full flex items-center justify-center rounded-2xl overflow-hidden shadow-2xl bg-black/60 p-1 sm:p-2 border border-white/10">
                <template x-if="lightboxType === 'image'">
                    <img :src="lightboxUrl" class="max-w-full max-h-[82vh] object-contain rounded-xl select-none" alt="Media Preview">
                </template>
                <template x-if="lightboxType === 'video'">
                    <video x-ref="lightboxVideo" :src="lightboxUrl" controls autoplay playsinline class="max-w-full max-h-[78vh] rounded-xl bg-black shadow-2xl"></video>
                </template>
            </div>
        </div>
    </div>

    {{-- Cancel Order Modal --}}
    <div x-show="cancelModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm" x-cloak style="display: none;">
        <div @click.away="cancelModal = false" style="background-color:#FDFBF7;border:1px solid #EAE2D2;border-radius:28px;box-shadow:0 25px 60px rgba(0,0,0,0.25);" class="w-full max-w-md p-6 space-y-4 text-gray-900">
            <div class="flex items-center gap-3 pb-3" style="border-bottom:1px solid #EAE1D0;">
                <div class="w-10 h-10 rounded-full bg-red-100 text-red-600 flex items-center justify-center font-bold text-lg shrink-0">
                    ✕
                </div>
                <div>
                    <h3 class="text-sm font-black text-[#1E1915] uppercase tracking-tight">Cancel Order</h3>
                    <p class="text-[10px] text-[#78716C] font-medium">Please select a reason for cancelling this order.</p>
                </div>
            </div>

            <form action="{{ route('orders.cancel', $order->id) }}" method="POST" class="space-y-3.5" @submit="cancelLoading = true">
                @csrf
                <div class="space-y-1.5">
                    <label class="text-[10px] font-black uppercase tracking-widest text-[#78716C]">Reason for Cancellation <span class="text-red-500">*</span></label>
                    <select name="cancellationReason" x-model="cancellationReason" class="w-full px-3.5 py-2.5 bg-[#FAF8F5] border border-[#ECE3D2] rounded-xl text-xs font-semibold text-[#1E1915] outline-none focus:border-[#C49520] focus:bg-white transition-all">
                        <option value="Need to change shipping address / details">Need to change shipping address / details</option>
                        <option value="Changed mind / ordered by mistake">Changed mind / ordered by mistake</option>
                        <option value="Decided to buy another item">Decided to buy another item</option>
                        <option value="Need to change payment method">Need to change payment method</option>
                        <option value="Other">Other / Custom reason</option>
                    </select>
                </div>

                <template x-if="cancellationReason === 'Other'">
                    <div class="space-y-1">
                        <label class="text-[10px] font-black uppercase tracking-widest text-[#78716C]">Explanation</label>
                        <textarea name="reason" rows="3" placeholder="Provide a brief explanation..." class="w-full px-3.5 py-2.5 bg-[#FAF8F5] border border-[#ECE3D2] rounded-xl text-xs font-medium outline-none focus:border-[#C49520] focus:bg-white resize-none"></textarea>
                    </div>
                </template>

                <div class="p-3 bg-red-50 border border-red-200 rounded-xl text-[10px] text-red-700 leading-relaxed">
                    <strong>Note:</strong> Cancellations and refunds are subject to the shop’s policy. Please contact the seller for assistance.
                </div>

                <div class="flex gap-2.5 pt-2">
                    <button type="button" @click="cancelModal = false" :disabled="cancelLoading" class="flex-1 py-2.5 rounded-full border border-[#ECE3D2] text-xs font-bold text-[#78716C] hover:bg-[#FAF8F5] transition-all cursor-pointer">
                        Keep Order
                    </button>
                    <button type="submit" :disabled="cancelLoading" class="flex-1 py-2.5 rounded-full bg-red-600 hover:bg-red-700 text-white text-xs font-bold uppercase tracking-wider shadow-sm transition-all flex items-center justify-center gap-1.5 cursor-pointer disabled:opacity-50">
                        <template x-if="cancelLoading">
                            <svg class="w-3.5 h-3.5 animate-spin text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/></svg>
                        </template>
                        <span x-text="cancelLoading ? 'Cancelling...' : 'Confirm Cancel'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
