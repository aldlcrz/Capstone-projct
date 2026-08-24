@extends('layouts.app')

@section('content')
<div style="min-height:calc(100vh - 80px);background-color:#FAF8F5;padding:24px 16px 48px 16px;"
     x-data="{
        detailsModal: false,
        selectedOrder: null,
        confirmModal: false,
        confirmOrderId: '',
        packingModal: false,
        packingModalUrl: '',
        reviewModal: false,
        reviewProductId: '',
        reviewOrderId: '',
        reviewOrderItemId: '',
        reviewProductName: '',
        reviewProductImage: '',
        cancelModal: false,
        cancelOrderId: null,
        cancellationReason: 'Need to change shipping address / details',
        cancelLoading: false,
        getStepIndex(status) {
            const s = (status || '').toLowerCase().trim();
            if (s === 'completed' || s === 'delivered') return 3;
            if (s === 'in transit' || s === 'in_transit' || s === 'out for delivery' || s === 'to receive') return 2;
            if (s === 'shipped' || s === 'ready to ship' || s === 'ready_to_ship' || s === 'to ship' || s === 'processing') return 1;
            return 0;
        },
        getStatusColor(status) {
            const s = (status || '').toLowerCase().trim();
            if (s === 'completed') return 'bg-emerald-50 text-emerald-700 border-emerald-200';
            if (s === 'delivered') return 'bg-teal-50 text-teal-700 border-teal-200';
            if (s === 'out for delivery' || s === 'out_for_delivery') return 'bg-orange-50 text-orange-700 border-orange-200';
            if (s === 'in transit' || s === 'in_transit') return 'bg-purple-50 text-purple-700 border-purple-200';
            if (s === 'shipped' || s === 'to receive') return 'bg-indigo-50 text-indigo-700 border-indigo-200';
            if (s === 'to ship' || s === 'ready to ship' || s === 'ready_to_ship' || s === 'processing') return 'bg-sky-50 text-sky-700 border-sky-200';
            if (s === 'cancelled') return 'bg-red-50 text-red-700 border-red-200';
            return 'bg-amber-50 text-amber-700 border-amber-200';
        },
        copiedTracking: false,
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
        }
     }">

    <div style="max-width:1040px;margin:0 auto;" class="space-y-6 sm:space-y-7">

        {{-- Top Header with Heraldic Laurel Wreath --}}
        <div style="background-color:#FDFBF7;border:1px solid #EAE2D2;border-radius:24px;box-shadow:0 10px 30px rgba(0,0,0,0.04);padding:24px 28px;">
            <div style="display:flex;align-items:center;gap:14px;">
                <!-- Heraldic Laurel Wreath + Star Emblem -->
                <div style="width:48px;height:48px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <svg width="46" height="46" viewBox="0 0 48 48" fill="none">
                        <circle cx="24" cy="23" r="10.5" stroke="#C49520" stroke-width="1" stroke-dasharray="2 1.5"/>
                        <circle cx="24" cy="23" r="8.5" stroke="#C49520" stroke-width="0.8"/>
                        <path d="M24 17.5l1.6 3.4 3.7.5-2.7 2.6.6 3.7-3.2-1.7-3.2 1.7.6-3.7-2.7-2.6 3.7-.5L24 17.5z" fill="#C49520"/>
                        <path d="M15 32.5c-4-3.5-6-8.5-6-14 0-3.5 1-6.5 2.5-9" stroke="#C49520" stroke-width="1.3" stroke-linecap="round"/>
                        <path d="M10 12c1.8 1.2 3.5 2.8 4 4.5M8 17.5c2 .6 3.8 1.8 4.8 3.5M8 23.5c2 0 3.8.6 5 2M9.5 29.5c2-.8 3.8-.8 5.2 0M12.5 34c1.8-1.2 3.6-1.5 5-.8" stroke="#C49520" stroke-width="1.2" stroke-linecap="round"/>
                        <path d="M33 32.5c4-3.5 6-8.5 6-14 0-3.5-1-6.5-2.5-9" stroke="#C49520" stroke-width="1.3" stroke-linecap="round"/>
                        <path d="M38 12c-1.8 1.2-3.5 2.8-4 4.5M40 17.5c-2 .6-3.8 1.8-4.8 3.5M40 23.5c-2 0-3.8.6-5 2M38.5 29.5c-2-.8-3.8-.8-5.2 0M35.5 34c-1.8-1.2-3.6-1.5-5-.8" stroke="#C49520" stroke-width="1.2" stroke-linecap="round"/>
                        <path d="M19 36c3 1.2 7 1.2 10 0" stroke="#C49520" stroke-width="1.3" stroke-linecap="round"/>
                    </svg>
                </div>
                <div>
                    <h1 style="font-family:ui-serif,Georgia,Cambria,serif;font-size:22px;font-weight:700;color:#1E1915;letter-spacing:-0.01em;line-height:1.2;margin:0;">
                        My Orders & Purchases
                    </h1>
                </div>
            </div>

            {{-- Star Divider --}}
            <div style="position:relative;margin:18px 0 0 0;display:flex;align-items:center;justify-content:center;">
                <div style="width:100%;border-top:1px solid #EAE1D0;"></div>
                <span style="position:absolute;background-color:#FDFBF7;padding:0 12px;color:#C49520;font-size:11px;">✦</span>
            </div>
        </div>

        {{-- Filter Capsule Tabs (Horizontally scrollable on mobile, single row) --}}
        <div class="flex items-center overflow-x-auto no-scrollbar gap-2 pb-1 -mx-4 px-4 sm:mx-0 sm:px-0 scroll-smooth">
            @foreach(['ALL' => 'All', 'PENDING' => 'Pending', 'TO SHIP' => 'To Ship', 'TO RECEIVE' => 'To Receive', 'DELIVERED' => 'Delivered', 'COMPLETED' => 'Completed', 'CANCELLED' => 'Cancelled'] as $key => $label)
                @php
                    $isActive = strtoupper(request('tab', 'ALL')) === $key;
                    $count = $counts[$key] ?? 0;
                @endphp
                @if($isActive)
                    <a href="/orders/my-orders?tab={{ $key }}"
                       style="background-color:#1E1915;color:#FFFFFF;border:1px solid #1E1915;box-shadow:0 4px 14px rgba(0,0,0,0.18);"
                       class="shrink-0 whitespace-nowrap px-4 py-2 rounded-full text-[10px] font-black uppercase tracking-wider transition-all flex items-center gap-2 active:scale-95 hover:opacity-90">
                        <span style="color:#FFFFFF !important;font-weight:800;letter-spacing:0.08em;">{{ $label }}</span>
                        <span style="background-color:rgba(223,201,122,0.25);color:#DFC97A !important;border:1px solid rgba(223,201,122,0.4);"
                              class="px-2 py-0.5 text-[9px] rounded-full font-extrabold">
                            {{ $count }}
                        </span>
                    </a>
                @else
                    <a href="/orders/my-orders?tab={{ $key }}"
                       style="background-color:#FDFBF7;color:#78716C;border:1px solid #EAE2D2;box-shadow:0 2px 6px rgba(0,0,0,0.02);"
                       class="shrink-0 whitespace-nowrap px-4 py-2 rounded-full text-[10px] font-black uppercase tracking-wider transition-all flex items-center gap-2 active:scale-95 hover:opacity-90">
                        <span style="color:#78716C !important;font-weight:800;letter-spacing:0.08em;">{{ $label }}</span>
                        <span style="background-color:#FAF5EA;color:#8C6212 !important;border:1px solid #E6D8BA;"
                              class="px-2 py-0.5 text-[9px] rounded-full font-extrabold">
                            {{ $count }}
                        </span>
                    </a>
                @endif
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
                        'packingProof' => $order->packing_proof_url,
                        'courierName' => $order->courierName ?? null,
                        'trackingNumber' => $order->trackingNumber ?? null,
                        'trackingLink' => $order->trackingLink ?? null,
                        'recipient' => $recipient,
                        'streetLine' => $streetLine,
                        'locality' => $locality,
                        'postalCode' => $addr['postalCode'] ?? '',
                        'phone' => $addr['phone'] ?? '',
                        'seller' => $order->seller ? [
                            'id' => $order->seller->id,
                            'name' => $order->seller->display_name ?? $order->seller->shopName ?? $order->seller->name ?? 'Artisan Shop',
                            'isVerified' => (bool) $order->seller->isVerified,
                            'photo' => $order->seller->profile_photo_url,
                        ] : null,
                        'items' => $order->items->map(function($item) use ($order) {
                            $existingReview = $order->reviews ? $order->reviews->where('orderItemId', $item->id)->first() : null;
                            if (!$existingReview && $order->reviews) {
                                $existingReview = $order->reviews->where('productId', $item->productId)->first();
                            }
                            return [
                                'id' => $item->id,
                                'productId' => $item->productId,
                                'name' => $item->product ? $item->product->name : 'Heritage Product',
                                'image' => $item->product ? $item->product->getImageUrl() : asset('uploads/products/default.jpg'),
                                'size' => $item->size,
                                'quantity' => $item->quantity,
                                'price' => number_format($item->price),
                                'subtotal' => number_format($item->price * $item->quantity),
                                'review' => $existingReview ? [
                                    'rating' => $existingReview->rating,
                                    'comment' => $existingReview->comment,
                                ] : null,
                            ];
                        })->values()
                    ];
                @endphp

                <div style="background-color:#FFFFFF;border:1px solid #ECE3D2;border-radius:22px;box-shadow:0 4px 16px rgba(0,0,0,0.03);overflow:hidden;transition:all 0.25s;"
                     class="group cursor-pointer hover:border-[#DFC97A] hover:shadow-md"
                     onclick="window.location.href='/orders/{{ $order->id }}'">

                    {{-- Card Header --}}
                    <div style="background-color:#FAF6EE;border-bottom:1px solid #EAE1D0;padding:14px 20px;" class="flex flex-wrap items-center justify-between gap-2">
                        <div class="flex flex-wrap items-center gap-2 sm:gap-3 text-[10px] sm:text-[11px] font-bold uppercase tracking-wider text-[#78716C] min-w-0">
                            <span style="display:inline-flex;align-items:center;gap:4px;padding:2px 8px;background:linear-gradient(135deg,#0F0C08 0%,#1C1609 100%);border:1px solid #A87B10;border-radius:10px;color:#DFC97A;font-weight:800;">
                                #LB-OR-{{ $orderData['shortId'] }}
                            </span>
                            <span class="w-1 h-1 bg-[#D6CEBE] rounded-full shrink-0"></span>
                            <span class="text-[#8C827A]">{{ $order->createdAt->format('M d, Y • g:i A') }}</span>
                        </div>

                        @php
                            $statusLower = strtolower(trim($order->status ?? ''));
                            $customerStatus = match($statusLower) {
                                'completed' => 'Completed',
                                'delivered' => 'Delivered',
                                'in transit', 'in_transit', 'to receive', 'out for delivery', 'out_for_delivery' => 'To Receive',
                                'to ship', 'ready to ship', 'ready_to_ship', 'processing', 'shipped' => 'To Ship',
                                'cancelled' => 'Cancelled',
                                'cancellation pending' => 'Cancellation Pending',
                                default => 'Pending',
                            };
                            $statusPillClass = match($customerStatus) {
                                'Completed' => 'bg-emerald-50 text-emerald-700 border border-emerald-200',
                                'Delivered' => 'bg-teal-50 text-teal-700 border border-teal-200',
                                'To Receive' => 'bg-purple-50 text-purple-700 border border-purple-200',
                                'To Ship' => 'bg-sky-50 text-sky-700 border border-sky-200',
                                'Cancelled' => 'bg-red-50 text-red-700 border border-red-200',
                                'Cancellation Pending' => 'bg-orange-50 text-orange-700 border border-orange-200',
                                default => 'bg-amber-50 text-amber-700 border border-amber-200',
                            };
                        @endphp
                        <span class="shrink-0 px-3.5 py-1 rounded-full text-[9px] font-black uppercase tracking-widest {{ $statusPillClass }}">
                            {{ $customerStatus }}
                        </span>
                    </div>

                    {{-- Product Items --}}
                    <div class="px-4 sm:px-6 py-4 space-y-3.5">
                        @foreach($order->items as $item)
                            <div class="flex items-center gap-3.5 sm:gap-4">
                                {{-- Thumbnail --}}
                                <div class="w-14 h-16 sm:w-16 sm:h-20 bg-[#FAF8F5] rounded-xl overflow-hidden border border-[#ECE3D2] shrink-0"
                                     onclick="event.stopPropagation(); window.location.href='/products/{{ $item->productId }}';">
                                    @php
                                        $imgSrc = $item->product ? $item->product->getImageUrl() : asset('uploads/products/default.jpg');
                                    @endphp
                                    <img src="{{ $imgSrc }}" class="w-full h-full object-cover object-top"
                                         onerror="this.src='/uploads/products/default.jpg'"
                                         alt="{{ $item->product->name ?? 'Product' }}">
                                </div>

                                {{-- Title & Meta --}}
                                <div class="flex-1 min-w-0 space-y-1">
                                    <h4 class="text-xs sm:text-base font-extrabold text-[#1E1915] truncate uppercase tracking-tight">
                                        <a href="/products/{{ $item->productId }}"
                                           onclick="event.stopPropagation();"
                                           @click.stop
                                           class="hover:text-[#C0422A] transition-colors">
                                            {{ $item->product->name ?? 'Heritage Product' }}
                                        </a>
                                    </h4>
                                    <div class="flex flex-wrap items-center gap-2 text-[9px] sm:text-[10px] font-bold text-[#8C827A] uppercase tracking-wider">
                                        @if($item->size)<span class="px-2 py-0.5 bg-[#FAF8F5] rounded-md text-[#1E1915] border border-[#EAE2D2]">Size: {{ $item->size }}</span>@endif
                                        <span>Qty: {{ $item->quantity }}</span>
                                    </div>
                                </div>

                                {{-- Item Price --}}
                                <div class="text-right shrink-0">
                                    <div class="text-xs sm:text-base font-black text-[#1E1915]">₱{{ number_format($item->price) }}</div>
                                    <div class="text-[9px] text-[#8C827A] font-bold uppercase tracking-wider">each</div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    {{-- Card Footer --}}
                    <div style="background-color:#FAF8F5;border-top:1px solid #ECE3D2;padding:14px 20px;">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <div class="text-[9px] font-bold text-[#8C827A] uppercase tracking-widest">Total Amount</div>
                                <div class="text-base sm:text-xl font-black text-[#C0422A]">₱{{ number_format($order->totalAmount, 2) }}</div>
                            </div>

                            @php
                                $statusLower = strtolower(trim($order->status));
                                $isDelivered = ($statusLower === 'delivered');
                                $isCompleted = ($statusLower === 'completed');
                                
                                $firstItem = $order->items->first();
                                $unreviewedItem = null;
                                $firstReview = null;
                                if ($firstItem) {
                                    $unreviewedItem = $order->items->first(function($itm) use ($order) {
                                        return !$order->reviews || !$order->reviews->where('orderItemId', $itm->id)->first();
                                    });
                                    $firstReview = $order->reviews ? $order->reviews->first() : null;
                                }
                            @endphp

                            <div class="flex items-center gap-2">
                                @if($isDelivered)
                                    <button type="button"
                                            onclick="event.stopPropagation();"
                                            @click.stop="confirmOrderId = '{{ $order->id }}'; confirmModal = true;"
                                            class="px-5 sm:px-6 py-2.5 rounded-full bg-emerald-600 hover:bg-emerald-700 text-white text-[9px] sm:text-[10px] font-black uppercase tracking-widest transition-all whitespace-nowrap shadow-sm active:scale-95 flex items-center gap-1.5 cursor-pointer">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                                        <span>Confirm Received</span>
                                    </button>
                                @elseif($isCompleted)
                                    @if($unreviewedItem)
                                        <button type="button"
                                                onclick="event.stopPropagation();"
                                                @click.stop="reviewModal = true; reviewProductId = '{{ $unreviewedItem->productId }}'; reviewOrderId = '{{ $order->id }}'; reviewOrderItemId = '{{ $unreviewedItem->id }}'; reviewProductName = '{{ addslashes($unreviewedItem->product->name ?? 'Product') }}'; reviewProductImage = '{{ $unreviewedItem->product ? $unreviewedItem->product->getImageUrl() : asset('uploads/products/default.jpg') }}'"
                                                style="background-color:#1E1915;color:#FFFFFF;border:1px solid #1E1915;"
                                                class="px-5 sm:px-6 py-2.5 rounded-full hover:bg-[#C0422A] text-[9px] sm:text-[10px] font-black uppercase tracking-widest transition-all whitespace-nowrap shadow-sm active:scale-95 flex items-center gap-1.5 cursor-pointer">
                                            <span>⭐ Rate Product</span>
                                        </button>
                                    @else
                                        <span onclick="event.stopPropagation();" style="background:#ECFDF5;color:#047857;border:1px solid #A7F3D0;" class="inline-flex items-center gap-1 px-4 py-2 rounded-full text-[9px] sm:text-[10px] font-black uppercase tracking-wider">
                                            ★ {{ $firstReview ? $firstReview->rating . '/5 ' : '' }}Reviewed
                                        </span>
                                    @endif
                                @elseif($statusLower === 'pending')
                                    <button type="button"
                                            onclick="event.stopPropagation();"
                                            @click.stop="cancelOrderId = '{{ $order->id }}'; cancelModal = true;"
                                            class="px-3.5 py-1.5 rounded-full bg-white hover:bg-red-50 text-red-600 border border-red-200 text-[9px] sm:text-[10px] font-black uppercase tracking-widest transition-all whitespace-nowrap shadow-2xs active:scale-95 flex items-center gap-1 cursor-pointer">
                                        <span>✕ Cancel</span>
                                    </button>
                                    <span class="text-[9px] sm:text-[10px] font-bold text-[#8C827A] uppercase tracking-wider flex items-center gap-1">
                                        <span>View Details</span>
                                        <svg class="w-3 h-3 text-stone-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                                    </span>
                                @elseif($statusLower === 'cancelled')
                                    <span style="background:#FEF2F2;color:#B91C1C;border:1px solid #FECACA;" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-full text-[9px] sm:text-[10px] font-black uppercase tracking-wider">
                                        ✕ Cancelled
                                    </span>
                                @else
                                    <span class="text-[9px] sm:text-[10px] font-bold text-[#8C827A] uppercase tracking-wider flex items-center gap-1">
                                        <span>View Details</span>
                                        <svg class="w-3 h-3 text-stone-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>

                </div>
            @empty
                <div style="background-color:#FDFBF7;border:1px solid #EAE2D2;border-radius:28px;padding:48px 24px;text-align:center;box-shadow:0 10px 30px rgba(0,0,0,0.04);max-width:520px;margin:32px auto;">
                    <div style="width:64px;height:64px;border-radius:50%;background-color:#FAF5EA;border:1px solid #E6D8BA;display:flex;align-items:center;justify-content:center;margin:0 auto 16px auto;color:#C49520;">
                        <svg width="28" height="28" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                    </div>
                    <h3 style="font-family:ui-serif,Georgia,serif;font-size:20px;font-weight:700;color:#1E1915;margin-bottom:6px;">No Orders Found</h3>
                    <p style="font-size:12.5px;color:#78716C;margin-bottom:24px;line-height:1.5;">You do not have any orders matching this category filter.</p>
                    <a href="/" style="display:inline-flex;align-items:center;justify-content:center;padding:10px 24px;border-radius:14px;background-color:#1E1915;color:#FFFFFF;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:0.12em;text-decoration:none;box-shadow:0 4px 12px rgba(0,0,0,0.15);transition:all 0.2s;"
                       onmouseover="this.style.backgroundColor='#C0422A';"
                       onmouseout="this.style.backgroundColor='#1E1915';">
                        Explore Collections
                    </a>
                </div>
            @endforelse
        </div>

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
             style="background-color:#FDFBF7;border:1px solid #EAE2D2;border-radius:28px;box-shadow:0 25px 60px rgba(0,0,0,0.25);"
             class="w-full sm:max-w-2xl overflow-hidden flex flex-col max-h-[90vh]">

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
                                     :style="'width: calc(' + (getStepIndex(selectedOrder.status) * 25) + '% - 8px);'"></div>

                                <template x-for="(stLabel, idx) in ['Order Placed', 'To Ship', 'Shipped', 'In Transit', 'Delivered']">
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

                        {{-- Seller Packing Proof Card (Shown whenever seller has uploaded a proof photo) --}}
                        <div class="bg-emerald-50/90 border border-emerald-200/80 rounded-2xl p-4 space-y-3" x-show="selectedOrder && selectedOrder.packingProof">
                            <div class="flex items-center justify-between gap-3">
                                <div class="flex items-center gap-2.5">
                                    <span class="text-base">📦</span>
                                    <div>
                                        <div class="font-black text-emerald-950 uppercase tracking-wider text-[10px]">Seller Packing Proof</div>
                                        <p class="text-[10px] text-emerald-700 font-medium mt-0.5">Photo uploaded by seller before handing order to courier</p>
                                    </div>
                                </div>
                                <button type="button" @click="packingModalUrl = selectedOrder.packingProof; packingModal = true;"
                                    class="px-3.5 py-1.5 bg-emerald-700 hover:bg-emerald-800 text-white rounded-full text-[9px] font-black uppercase tracking-widest transition-all shrink-0 shadow-xs flex items-center gap-1 active:scale-95">
                                    <span>View Full Photo ↗</span>
                                </button>
                            </div>

                            {{-- Thumbnail Image Preview --}}
                            <div @click="packingModalUrl = selectedOrder.packingProof; packingModal = true;" 
                                 class="w-full max-h-48 rounded-xl overflow-hidden bg-white border border-emerald-200 cursor-pointer group relative flex items-center justify-center">
                                <img :src="selectedOrder.packingProof" class="w-full max-h-48 object-cover group-hover:scale-105 transition-transform duration-300" alt="Packing Proof Preview" x-on:error="$event.target.style.display='none'">
                                <div class="absolute inset-0 bg-black/20 group-hover:bg-black/10 transition-colors flex items-center justify-center">
                                    <span class="px-3 py-1 bg-black/60 text-white text-[9px] font-bold rounded-full backdrop-blur-xs group-hover:scale-110 transition-transform">🔍 Click to Enlarge</span>
                                </div>
                            </div>
                        </div>

                        {{-- Packing Photo notice when seller has not yet uploaded (only when To Ship / Processing) --}}
                        <div class="bg-amber-50/80 border border-amber-200/80 rounded-2xl p-3.5 flex items-center justify-between gap-3"
                             x-show="selectedOrder && !selectedOrder.packingProof && ['processing', 'to ship', 'ready to ship', 'to_ship', 'ready_to_ship'].includes((selectedOrder.status || '').toLowerCase())">
                            <div class="flex items-center gap-2.5">
                                <span class="text-base">📦</span>
                                <div>
                                    <div class="font-bold text-amber-950 text-[10px] uppercase tracking-wider">Packaging Status</div>
                                    <p class="text-[10px] text-amber-800 font-medium mt-0.5">The artisan is currently preparing and packing your order. Verified photo will be displayed here once packed.</p>
                                </div>
                            </div>
                            <span class="px-2.5 py-1 bg-amber-100/90 text-amber-900 border border-amber-300 rounded-full text-[8px] font-black uppercase tracking-wider shrink-0">
                                In Preparation
                            </span>
                        </div>

                        {{-- Courier Tracking Details (Shown ONLY when In Transit or later with active tracking number) --}}
                        <div class="bg-linear-to-br from-gray-900 to-black text-white p-4 rounded-2xl space-y-3 shadow-md"
                             x-show="selectedOrder && ['in transit', 'out for delivery', 'delivered', 'completed'].includes((selectedOrder.status || '').toLowerCase().replace(/_/g, ' ')) && selectedOrder.trackingNumber">
                            <div class="flex items-center justify-between border-b border-white/10 pb-2">
                                <div class="flex items-center gap-1.5">
                                    <span class="text-base">🚚</span>
                                    <span class="text-[9px] font-black uppercase tracking-widest text-gray-300">Courier Shipping Details</span>
                                </div>
                                <span class="px-2 py-0.5 bg-white/10 rounded-full text-[8px] font-black uppercase tracking-widest text-amber-400" x-text="selectedOrder.status"></span>
                            </div>

                            <div class="grid grid-cols-2 gap-3 text-xs">
                                <div>
                                    <span class="text-[8px] font-bold uppercase tracking-widest text-gray-400 block">Courier</span>
                                    <span class="font-black text-white text-xs" x-text="selectedOrder.courierName || 'Pending Assignment'"></span>
                                </div>
                                <div>
                                    <span class="text-[8px] font-bold uppercase tracking-widest text-gray-400 block">Tracking Number</span>
                                    <div class="flex items-center gap-1.5 mt-0.5">
                                        <span class="font-mono font-black text-amber-400 text-xs" x-text="selectedOrder.trackingNumber || 'Pending Dispatch'"></span>
                                        <template x-if="selectedOrder.trackingNumber">
                                            <button type="button" 
                                                    @click="navigator.clipboard.writeText(selectedOrder.trackingNumber); copiedTracking = true; setTimeout(() => copiedTracking = false, 2000)"
                                                    class="p-1 rounded bg-white/10 hover:bg-white/20 text-white transition-colors relative cursor-pointer"
                                                    title="Copy tracking number">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                                </svg>
                                                <span x-show="copiedTracking" x-cloak class="absolute -top-7 right-0 bg-emerald-600 text-white text-[9px] font-bold px-1.5 py-0.5 rounded shadow whitespace-nowrap">Copied!</span>
                                            </button>
                                        </template>
                                    </div>
                                </div>
                            </div>

                            <template x-if="selectedOrder && selectedOrder.trackingLink">
                                <div class="pt-1 space-y-1">
                                    <a :href="selectedOrder.trackingLink" target="_blank" rel="noopener noreferrer"
                                       class="inline-flex items-center justify-center gap-1.5 w-full py-2.5 bg-[#C0420A] hover:bg-[#d94a0d] text-white rounded-xl text-[9px] font-black uppercase tracking-widest transition-all shadow-xs">
                                        <span x-text="'Track Package on ' + (selectedOrder.courierName || 'Courier Portal') + ' ↗'"></span>
                                    </a>
                                    <p class="text-[9px] text-gray-400 text-center">Copy your tracking number and paste it on the courier portal to track.</p>
                                </div>
                            </template>
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
                <template x-if="selectedOrder && ['delivered'].includes((selectedOrder.status || '').toLowerCase())">
                    <button type="button"
                        @click="confirmOrderId = selectedOrder.id; confirmModal = true;"
                        class="flex-1 py-3 bg-emerald-600 hover:bg-emerald-700 text-white rounded-full text-[10px] font-black uppercase tracking-widest transition-all shadow-sm active:scale-95 flex items-center justify-center gap-1.5 cursor-pointer">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                        <span>Confirm Received</span>
                    </button>
                </template>
                <button @click="detailsModal = false"
                    class="flex-1 py-3 sm:py-3.5 rounded-full bg-black text-white hover:bg-[#C0420A] text-[10px] font-black uppercase tracking-widest transition-all cursor-pointer shadow-sm active:scale-95">
                    Close Details
                </button>
            </div>
        </div>
    </div>

    {{-- Floating Packing Proof Photo Viewer Modal --}}
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
                <img :src="packingModalUrl" class="max-w-full max-h-[60vh] object-contain" alt="Packing Proof">
            </div>
            
            <div class="w-full mt-4 flex gap-3">
                <a :href="packingModalUrl" download="packing-proof.jpg" target="_blank" class="flex-1 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 text-center rounded-xl text-[10px] font-bold uppercase tracking-widest transition-all">
                    Download Image
                </a>
                <button type="button" @click="packingModal = false" class="flex-1 py-3 bg-black hover:bg-[#C0420A] text-white rounded-xl text-[10px] font-bold uppercase tracking-widest transition-all">
                    Close
                </button>
            </div>
        </div>
    </div>

    {{-- Confirm Received Modal --}}
    <div x-show="confirmModal" class="fixed inset-0 z-9999 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm" x-cloak style="display: none;">
        <div @click.away="confirmModal = false" class="bg-white rounded-3xl shadow-2xl w-full max-w-md p-6 sm:p-8 space-y-6">
            <div class="text-center space-y-2">
                <div class="w-14 h-14 rounded-full bg-emerald-50 border border-emerald-100 flex items-center justify-center mx-auto text-2xl text-emerald-600 shadow-sm">
                    ✓
                </div>
                <h3 class="font-serif text-lg sm:text-xl font-bold text-black">Confirm Order Received?</h3>
                <p class="text-xs text-gray-500 leading-relaxed">Please only confirm once you have physically received and inspected all items in your package.</p>
            </div>
            <form :action="'/orders/' + confirmOrderId + '/confirm'" method="POST" class="flex gap-3">
                @csrf
                @method('PATCH')
                <button type="button" @click="confirmModal = false"
                    class="flex-1 py-3 rounded-full border border-gray-200 text-[10px] font-black uppercase tracking-widest text-gray-600 hover:bg-gray-50 transition-all cursor-pointer">
                    Not Yet
                </button>
                <button type="submit"
                    class="flex-1 py-3 rounded-full bg-emerald-600 text-white text-[10px] font-black uppercase tracking-widest hover:bg-emerald-700 transition-all shadow-md shadow-emerald-600/20 cursor-pointer">
                    Confirm Received
                </button>
            </form>
        </div>
    </div>

    {{-- Leave Review Modal --}}
    <div x-show="reviewModal" class="fixed inset-0 z-9999 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm" x-cloak style="display: none;">
        <div @click.away="reviewModal = false" class="bg-white rounded-3xl shadow-2xl w-full max-w-md p-6 sm:p-8 space-y-5">
            <div class="flex items-center gap-3.5 pb-3 border-b border-gray-100">
                <div class="w-14 h-16 bg-gray-50 rounded-xl overflow-hidden border border-gray-100 shrink-0">
                    <img :src="reviewProductImage || '/uploads/products/default.jpg'" class="w-full h-full object-cover object-top" onerror="this.src='/uploads/products/default.jpg'" :alt="reviewProductName">
                </div>
                <div class="min-w-0 flex-1">
                    <div class="text-[9px] font-black uppercase tracking-widest text-[#C0420A]">Leave a Review</div>
                    <h3 class="font-serif text-base font-bold text-black truncate mt-0.5" x-text="reviewProductName"></h3>
                </div>
            </div>
            <form action="/api/reviews" method="POST" enctype="multipart/form-data" class="space-y-4" 
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
                              this.photoError = 'Maximum of 3 images allowed.';
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
                  @submit="if (rating === 0) { $event.preventDefault(); alert('Please select a rating of at least 1 star before submitting.'); }">
                @csrf
                <input type="hidden" name="productId" :value="reviewProductId">
                <input type="hidden" name="orderId" :value="reviewOrderId || (selectedOrder ? selectedOrder.id : '')">
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
                                class="text-3xl transition-transform active:scale-125 focus:outline-none cursor-pointer">
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

                {{-- Photo Attachments (0-3 images) --}}
                <div class="space-y-1.5 pt-1 border-t border-gray-100">
                    <div class="flex items-center justify-between">
                        <label class="text-[10px] font-black uppercase tracking-widest text-gray-700 flex items-center gap-1">
                            <span>📷 Upload Photos</span>
                            <span class="text-gray-400 font-normal" x-text="'(' + photoPreviews.length + '/3)'"></span>
                        </label>
                        <span class="text-[9px] font-bold text-gray-400">Max 3 images • 10MB</span>
                    </div>

                    <input type="file" name="photos[]" multiple accept="image/*" x-ref="photoInput" class="hidden" @change="handlePhotos($event)">

                    <div class="flex flex-wrap items-center gap-2">
                        {{-- Previews --}}
                        <template x-for="(photo, index) in photoPreviews" :key="index">
                            <div class="relative w-14 h-14 rounded-xl border border-gray-200 overflow-hidden group bg-gray-50 shrink-0 shadow-2xs">
                                <img :src="photo.url" @click="openLightbox('image', photo.url)" class="w-full h-full object-cover cursor-pointer hover:opacity-90 transition-opacity" title="Click to preview">
                                <button type="button" @click.stop="removePhoto(index)" class="absolute top-0.5 right-0.5 w-4 h-4 rounded-full bg-red-600 hover:bg-red-700 text-white flex items-center justify-center text-[9px] font-bold shadow-xs cursor-pointer">
                                    ✕
                                </button>
                            </div>
                        </template>

                        {{-- Add Button --}}
                        <template x-if="photoPreviews.length < 3">
                            <button type="button" @click="$refs.photoInput.click()" class="w-14 h-14 rounded-xl border-2 border-dashed border-gray-300 hover:border-[#C0420A] bg-gray-50/60 hover:bg-orange-50/30 flex flex-col items-center justify-center text-gray-400 hover:text-[#C0420A] transition-all cursor-pointer shrink-0">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                <span class="text-[8px] font-black uppercase mt-0.5">Photo</span>
                            </button>
                        </template>
                    </div>
                    <template x-if="photoError">
                        <p class="text-[10px] font-bold text-red-600" x-text="photoError"></p>
                    </template>
                </div>

                {{-- Video Attachment (1 video) --}}
                <div class="space-y-1.5 pt-1 border-t border-gray-100">
                    <div class="flex items-center justify-between">
                        <label class="text-[10px] font-black uppercase tracking-widest text-gray-700 flex items-center gap-1">
                            <span>🎥 Upload Video</span>
                            <span class="text-gray-400 font-normal">(1 video)</span>
                        </label>
                        <span class="text-[9px] font-bold text-gray-400">Max 50MB (MP4, MOV, WEBM)</span>
                    </div>

                    <input type="file" name="video" accept="video/*" x-ref="videoInput" class="hidden" @change="handleVideo($event)">

                    <template x-if="!videoPreview">
                        <button type="button" @click="$refs.videoInput.click()" class="w-full py-2.5 px-3 rounded-2xl border border-dashed border-gray-300 hover:border-[#C0420A] bg-gray-50/60 hover:bg-orange-50/20 text-gray-600 hover:text-[#C0420A] text-xs font-bold transition-all flex items-center justify-center gap-2 cursor-pointer">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                            <span>Attach 1 Video</span>
                        </button>
                    </template>

                    <template x-if="videoPreview">
                        <div class="p-2.5 bg-gray-900 rounded-2xl flex items-center justify-between gap-2 text-white">
                            <div class="flex items-center gap-2 min-w-0 cursor-pointer" @click="openLightbox('video', videoPreview)">
                                <div class="w-8 h-8 rounded-lg bg-black/60 border border-white/20 flex items-center justify-center shrink-0 text-[10px]">
                                    ▶
                                </div>
                                <div class="min-w-0">
                                    <p class="text-[11px] font-bold truncate text-white" x-text="videoName || 'Attached Video'"></p>
                                    <span class="text-[9px] text-orange-400 font-semibold">🔍 Preview Video</span>
                                </div>
                            </div>
                            <button type="button" @click="removeVideo()" class="px-2.5 py-1 rounded-full bg-red-600/80 hover:bg-red-600 text-white text-[10px] font-bold transition-colors cursor-pointer shrink-0">
                                ✕ Remove
                            </button>
                        </div>
                    </template>
                    <template x-if="videoError">
                        <p class="text-[10px] font-bold text-red-600" x-text="videoError"></p>
                    </template>
                </div>

                <div class="flex gap-3 pt-3 border-t border-gray-100">
                    <button type="button" @click="reviewModal = false"
                        class="flex-1 py-3 rounded-full border border-gray-200 text-[10px] font-black uppercase tracking-widest text-gray-500 hover:bg-gray-50 transition-all cursor-pointer">
                        Cancel
                    </button>
                    <button type="submit"
                        class="flex-1 py-3 rounded-full bg-black text-white text-[10px] font-black uppercase tracking-widest hover:bg-[#C0420A] transition-all shadow-sm cursor-pointer">
                        Submit Review
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Media Lightbox Overlay (for reviewing image & video previews without page redirects) --}}
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
        <div @click.away="cancelModal = false" class="bg-white border border-gray-150 rounded-2xl shadow-2xl w-full max-w-md p-6 space-y-4 text-gray-900">
            <div class="flex items-center gap-3 pb-3 border-b border-gray-100">
                <div class="w-10 h-10 rounded-full bg-red-100 text-red-600 flex items-center justify-center font-bold text-lg shrink-0">
                    ✕
                </div>
                <div>
                    <h3 class="text-sm font-black text-black uppercase tracking-tight">Cancel Order</h3>
                    <p class="text-[10px] text-gray-500 font-medium">Please select a reason for cancelling this order.</p>
                </div>
            </div>

            <form :action="'/orders/' + cancelOrderId + '/cancel'" method="POST" class="space-y-3.5" @submit="cancelLoading = true">
                @csrf
                <div class="space-y-1.5">
                    <label class="text-[10px] font-black uppercase tracking-widest text-gray-500">Reason for Cancellation <span class="text-red-500">*</span></label>
                    <select name="cancellationReason" x-model="cancellationReason" class="w-full px-3.5 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-xs font-semibold text-gray-800 outline-none focus:border-red-500 focus:bg-white transition-all">
                        <option value="Need to change shipping address / details">Need to change shipping address / details</option>
                        <option value="Changed mind / ordered by mistake">Changed mind / ordered by mistake</option>
                        <option value="Decided to buy another item">Decided to buy another item</option>
                        <option value="Need to change payment method">Need to change payment method</option>
                        <option value="Other">Other / Custom reason</option>
                    </select>
                </div>

                <template x-if="cancellationReason === 'Other'">
                    <div class="space-y-1">
                        <label class="text-[10px] font-black uppercase tracking-widest text-gray-500">Explanation</label>
                        <textarea name="reason" rows="3" placeholder="Provide a brief explanation..." class="w-full px-3.5 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-xs font-medium outline-none focus:border-red-500 focus:bg-white resize-none"></textarea>
                    </div>
                </template>

                <div class="p-3 bg-red-50 border border-red-200 rounded-xl text-[10px] text-red-700 leading-relaxed">
                    <strong>Note:</strong> Cancellations and refunds are subject to the shop’s policy. Please contact the seller for assistance.
                </div>

                <div class="flex gap-2.5 pt-2">
                    <button type="button" @click="cancelModal = false" :disabled="cancelLoading" class="flex-1 py-2.5 rounded-full border border-gray-200 text-xs font-bold text-gray-600 hover:bg-gray-50 transition-all cursor-pointer">
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
