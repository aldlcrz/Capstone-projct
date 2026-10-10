@extends('layouts.app')

@section('content')
<div style="min-height:calc(100vh - 80px);background-color:#FAF8F5;padding:24px 16px 48px 16px;"
     x-data="{ 
        confirmModal: false, 
        cancelModal: false,
        cancellationReason: 'Need to change shipping address / details',
        cancelLoading: false,
        returnModal: false,
        returnReason: 'Damaged / Defective item',
        returnMessage: '',
        returnLoading: false,
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

    <div class="max-w-5xl mx-auto space-y-5 sm:space-y-6">

        {{-- Top Navigation --}}
        <div class="flex items-center justify-between gap-3">
            <a href="/orders/my-orders" class="inline-flex items-center gap-2 text-xs font-bold text-[#78716C] hover:text-[#C0422A] transition-colors group">
                <div style="background-color:#FFFFFF;border:1px solid #ECE3D2;box-shadow:0 1px 3px rgba(0,0,0,0.04);" class="w-7 h-7 rounded-full flex items-center justify-center group-hover:border-[#C49520] group-hover:bg-[#1E1915] group-hover:text-white transition-all">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/></svg>
                </div>
                <span class="tracking-wide">Back to my orders</span>
            </a>

            {{-- Home Button --}}
            <a href="{{ route('home') }}"
               class="hidden sm:inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl bg-[#FAF6EE] border border-[#E2D9C8] text-[#78716C] hover:bg-[#1E1915] hover:text-[#DFC97A] hover:border-[#1E1915] font-bold text-[11px] uppercase tracking-wider transition-all shadow-2xs no-underline">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                </svg>
                <span>Home</span>
            </a>
        </div>

        @php
            $statusLower = strtolower(trim($order->status ?? ''));
            $isStorePickup = $order->isStorePickup();
            $isSpecialDelivery = $order->isSpecialDelivery();
            $customerStatusDisplay = match(true) {
                $statusLower === 'completed' => 'Completed',
                $statusLower === 'delivered' => ($isStorePickup ? 'Picked Up / Claimed' : 'Delivered'),
                $isStorePickup && $statusLower === 'shipped' => 'Ready for Pickup',
                $isSpecialDelivery && $statusLower === 'shipped' => 'Special Delivery Processing',
                $isSpecialDelivery && in_array($statusLower, ['in transit', 'in_transit', 'to receive', 'out for delivery', 'out_for_delivery'], true) => 'Out for Special Delivery',
                in_array($statusLower, ['in transit', 'in_transit', 'to receive', 'out for delivery', 'out_for_delivery'], true) => 'To Receive',
                in_array($statusLower, ['to ship', 'ready to ship', 'ready_to_ship', 'processing', 'shipped'], true) => 'To Ship',
                in_array($statusLower, ['cancellation pending', 'cancellation requested'], true) => 'Cancellation Pending',
                $statusLower === 'cancelled' => 'Cancelled',
                default => 'Order Placed',
            };
            $statusPillClass = match($customerStatusDisplay) {
                'Completed' => 'bg-emerald-50 text-emerald-700 border border-emerald-200',
                'Delivered', 'Picked Up / Claimed' => 'bg-teal-50 text-teal-700 border border-teal-200',
                'Ready for Pickup' => 'bg-amber-50 text-amber-800 border border-amber-200',
                'Out for Special Delivery' => 'bg-blue-50 text-blue-700 border border-blue-200',
                'Special Delivery Processing' => 'bg-sky-50 text-sky-700 border border-sky-200',
                'To Receive' => 'bg-purple-50 text-purple-700 border border-purple-200',
                'To Ship' => 'bg-sky-50 text-sky-700 border border-sky-200',
                'Cancellation Pending' => 'bg-orange-50 text-orange-700 border border-orange-200',
                'Cancelled' => 'bg-red-50 text-red-700 border border-red-200',
                default => 'bg-amber-50 text-amber-700 border border-amber-200',
            };

            $steps = $isStorePickup ? [
                ['label' => 'Order Placed',  'status' => 'pending'],
                ['label' => 'Preparing',     'status' => 'to ship'],
                ['label' => 'Ready for Pickup', 'status' => 'shipped'],
                ['label' => 'Picked Up',    'status' => 'delivered'],
            ] : ($isSpecialDelivery ? [
                ['label' => 'Order Placed',  'status' => 'pending'],
                ['label' => 'Preparing',     'status' => 'to ship'],
                ['label' => 'Out for Delivery', 'status' => 'in transit'],
                ['label' => 'Delivered',     'status' => 'delivered'],
            ] : [
                ['label' => 'Order Placed',  'status' => 'pending'],
                ['label' => 'To Ship',       'status' => 'to ship'],
                ['label' => 'To Receive',    'status' => 'to receive'],
                ['label' => 'Delivered',     'status' => 'delivered'],
            ]);

            $statusRanks = ($isStorePickup || $isSpecialDelivery) ? [
                'pending'          => 0,
                'processing'       => 1,
                'to ship'          => 1,
                'ready to ship'    => 1,
                'ready_to_ship'    => 1,
                'shipped'          => $isSpecialDelivery ? 1 : 2,
                'in transit'       => 2,
                'in_transit'       => 2,
                'to receive'       => 2,
                'out for delivery' => 2,
                'out_for_delivery' => 2,
                'delivered'        => 3,
                'completed'        => 3,
                'cancelled'        => -1,
                'cancellation pending'   => 0,
                'cancellation requested' => 0,
            ] : [
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
                'cancellation pending'   => 0,
                'cancellation requested' => 0,
            ];

            $currentStep = $statusRanks[$statusLower] ?? 0;
            $isCancelled = $statusLower === 'cancelled';
            $isCancellationPending = in_array($statusLower, ['cancellation pending', 'cancellation requested']);

            // Map status history timestamps
            $historyDates = [];
            if ($order->statusHistories) {
                foreach ($order->statusHistories as $h) {
                    $historyDates[strtolower(trim($h->newStatus))] = $h->createdAt ? $h->createdAt->format('g:i A') : null;
                }
            }

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

            $sellerWorkshopLat = $order->seller?->shopLatitude ? (float) $order->seller->shopLatitude : 14.2988;
            $sellerWorkshopLng = $order->seller?->shopLongitude ? (float) $order->seller->shopLongitude : 121.4606;
            $sellerWorkshopAddress = $order->seller?->shopAddress ?: trim(implode(', ', array_filter([
                $order->seller?->shopHouseNo,
                $order->seller?->shopStreet,
                $order->seller?->shopBarangay,
                $order->seller?->shopCity ?: 'Lumban',
                $order->seller?->shopProvince ?: 'Laguna',
                $order->seller?->shopPostalCode ?: '4014'
            ])));
            if (empty($sellerWorkshopAddress)) {
                $sellerWorkshopAddress = 'Lumban, Laguna, Philippines (4014)';
            }
            $sellerShopName = $order->seller?->shopName ?: ($order->seller?->name ?: 'Lumban Artisan Workshop');
            $directionsUrl = "https://www.google.com/maps/dir/?api=1&destination=" . urlencode("{$sellerWorkshopLat},{$sellerWorkshopLng}");

            $displayCourier = $order->shipping?->fulfillment_provider_name 
                ?? ($order->courierName ?? ($order->shipping?->pricing_provider_name ?? ($order->shipping?->provider_name ?? null)));
            $hasCourierTracking = !$isStorePickup && !$isSpecialDelivery && in_array(strtolower(str_replace('_', ' ', $order->status)), ['in transit', 'out for delivery', 'delivered', 'completed']) && !empty(trim($order->trackingNumber ?? ''));
            $hasSpecialDeliveryActive = $isSpecialDelivery && in_array(strtolower(str_replace('_', ' ', $order->status)), ['shipped', 'in transit', 'out for delivery', 'delivered', 'completed']);
        @endphp

        {{-- UNIFIED MASTER ORDER DETAILS CARD CONTAINER --}}
        <div style="background-color:#FFFFFF;border:1px solid #ECE3D2;border-radius:20px;box-shadow:0 4px 20px rgba(0,0,0,0.02);overflow:hidden;" class="space-y-0">
            
            {{-- 1. ORDER HEADER & STEPPER TRACKER --}}
            <div class="p-4 sm:p-5 space-y-4">
                {{-- Header Row --}}
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 pb-2.5" style="border-bottom:1px solid #FAF4EB;">
                    <div class="space-y-0.5">
                        <div class="text-sm font-extrabold text-[#1E1915] font-mono tracking-wide">
                            #LB-OR-{{ strtoupper(substr($order->id, -8)) }}
                        </div>
                        <div class="text-xs text-[#78716C]">
                            Placed {{ $order->createdAt ? $order->createdAt->format('M d, Y \a\t g:i A') : 'Recently' }}
                        </div>
                    </div>

                    {{-- Badges on Right --}}
                    <div class="flex flex-wrap items-center gap-1.5">
                        @if($isStorePickup)
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-extrabold uppercase tracking-wider bg-amber-50 text-amber-900 border border-amber-200">
                                Store Pickup
                            </span>
                        @elseif($isSpecialDelivery)
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-extrabold uppercase tracking-wider bg-blue-50 text-blue-900 border border-blue-200">
                                Special delivery
                            </span>
                        @else
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-extrabold uppercase tracking-wider bg-stone-100 text-stone-700 border border-stone-200">
                                Standard Shipping
                            </span>
                        @endif

                        {{-- Payment Method Badge --}}
                        @if(!($isStorePickup && in_array(strtolower($order->paymentMethod ?? ''), ['cod', 'cash', 'cash_on_delivery', 'cash on delivery'])))
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-extrabold uppercase tracking-wider bg-[#FAF6EE] text-[#996515] border border-[#E2D9C8] flex items-center gap-1">
                                <span>💳</span>
                                <span>{{ $order->formatted_payment_method }}</span>
                            </span>
                        @endif

                        <span class="px-2.5 py-0.5 rounded-full text-xs font-black uppercase tracking-wider {{ $statusPillClass }}">
                            {{ $customerStatusDisplay }}
                        </span>

                        @if($statusLower === 'pending')
                            <button type="button" @click="cancelModal = true" class="px-2.5 py-0.5 bg-white hover:bg-red-50 text-red-600 border border-red-200 text-xs font-bold uppercase rounded-full transition-all cursor-pointer flex items-center gap-1 shadow-2xs">
                                <svg class="w-3 h-3 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                <span>Cancel</span>
                            </button>
                        @endif
                    </div>
                </div>

                {{-- 4-Step Stepper --}}
                @if($isCancellationPending)
                    <div style="background-color:#FFF7ED;border:1px solid #FED7AA;border-radius:14px;padding:12px 16px;" class="flex items-center gap-3">
                        <div class="w-7 h-7 rounded-lg bg-orange-100 text-orange-600 flex items-center justify-center shrink-0 text-sm">⏳</div>
                        <div class="text-xs">
                            <span class="font-bold text-orange-900 block">Cancellation Pending Artisan Approval</span>
                            <span class="text-orange-700 text-[11px]">Your request has been submitted and is currently being reviewed.</span>
                        </div>
                    </div>
                @elseif($isCancelled)
                    <div style="background-color:#FEF2F2;border:1px solid #FECACA;border-radius:14px;padding:12px 16px;" class="flex items-center gap-3">
                        <div class="w-7 h-7 rounded-lg bg-red-100 text-red-600 flex items-center justify-center shrink-0 text-sm">✕</div>
                        <div class="text-xs">
                            <span class="font-bold text-red-900 block">Order Cancelled</span>
                            @if($order->cancellationReason)
                                <span class="text-red-600 text-[11px]">Reason: {{ $order->cancellationReason }}</span>
                            @endif
                        </div>
                    </div>
                @else
                    <div class="relative py-1">
                        {{-- Connecting Line Background --}}
                        <div class="absolute top-5 sm:top-5.5 left-[12%] right-[12%] h-0.5 bg-[#EAE1D0] -translate-y-1/2 z-0"></div>
                        {{-- Connecting Line Active fill --}}
                        @php
                            $progressWidthPct = match($currentStep) {
                                0 => 0,
                                1 => 33.33,
                                2 => 66.66,
                                3 => 100,
                                default => 0,
                            };
                            $fillWidth = number_format($progressWidthPct * 0.76, 2) . '%';
                        @endphp
                        <div class="absolute top-5 sm:top-5.5 left-[12%] h-0.5 bg-emerald-500 -translate-y-1/2 z-0 transition-all duration-500" :style="'width: {{ $fillWidth }}'"></div>

                        <div class="grid grid-cols-4 gap-1.5 relative z-10">
                            @foreach($steps as $i => $step)
                                @php
                                    $isDone = $i < $currentStep;
                                    $isCurrent = $i === $currentStep;
                                    $stepKey = strtolower($step['status']);
                                    $timeLabel = $historyDates[$stepKey] ?? ($i === 0 && $order->createdAt ? $order->createdAt->format('g:i A') : null);
                                    if (!$timeLabel && $stepKey === 'to receive') {
                                        $timeLabel = $historyDates['in transit'] ?? ($historyDates['in_transit'] ?? null);
                                    }
                                @endphp
                                <div class="flex flex-col items-center text-center">
                                    <div class="w-6 h-6 sm:w-7 sm:h-7 rounded-full flex items-center justify-center text-[11px] font-black transition-all shadow-xs {{ ($isDone || ($isCurrent && $currentStep === 3)) ? 'bg-emerald-600 text-white' : ($isCurrent ? 'bg-[#1E1915] text-[#DFC97A] border-2 border-[#A87B10] ring-3 ring-amber-100' : 'bg-[#FAF8F5] text-stone-400 border border-[#ECE3D2]') }}">
                                        @if($isDone || ($isCurrent && $currentStep === 3))
                                            <svg class="w-3 h-3 sm:w-3.5 sm:h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                        @else
                                            <span>{{ $i + 1 }}</span>
                                        @endif
                                    </div>
                                    <div class="mt-1.5 space-y-0.5">
                                        <span class="text-[10px] sm:text-[11px] font-black block uppercase tracking-wider {{ ($isDone || ($isCurrent && $currentStep === 3)) ? 'text-[#1E1915]' : ($isCurrent ? 'text-[#C0422A]' : 'text-stone-400') }}">
                                            {{ $step['label'] }}
                                        </span>
                                        @if($timeLabel && ($isDone || $isCurrent))
                                            <span class="text-[9px] sm:text-[10px] font-medium text-[#78716C] block leading-none">{{ $timeLabel }}</span>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            {{-- 1.5 APPOINTMENT BANNER (STORE PICKUP & SPECIAL DELIVERY) --}}
            @if($order->appointment_date && !$isCancelled)
                <div class="px-4 sm:px-5 pb-3 pt-0">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 shadow-2xs rounded-[14px] p-3 sm:px-4 border {{ $isStorePickup ? 'bg-gradient-to-r from-amber-100 via-amber-100 to-amber-200 border-amber-400' : 'bg-gradient-to-r from-blue-50 via-blue-50 to-blue-100 border-blue-300' }}">
                        <div class="flex items-start sm:items-center gap-3">
                            <div class="w-9 h-9 rounded-xl {{ $isStorePickup ? 'bg-amber-100 border border-amber-300 text-amber-900' : 'bg-blue-100 border border-blue-300 text-blue-900' }} flex items-center justify-center shrink-0 text-base">
                                {{ $isStorePickup ? '🏬' : '🏍️' }}
                            </div>
                            <div>
                                <div class="text-[10px] font-black uppercase tracking-wider {{ $isStorePickup ? 'text-amber-900' : 'text-blue-900' }}">
                                    {{ $isStorePickup ? '📅 In-Shop Store Visit Appointment' : '📅 Special Delivery Scheduled Date' }}
                                </div>
                                <div class="text-xs sm:text-sm font-extrabold {{ $isStorePickup ? 'text-amber-950' : 'text-blue-950' }} mt-0.5">
                                    {{ \Carbon\Carbon::parse($order->appointment_date)->format('l, F j, Y') }}
                                    @if($order->appointment_time)
                                        &bull; <span class="font-bold {{ $isStorePickup ? 'text-amber-900' : 'text-blue-900' }}">{{ $order->appointment_time }}</span>
                                    @endif
                                </div>
                                @if($order->appointment_notes)
                                    <div class="text-[11px] {{ $isStorePickup ? 'text-amber-800' : 'text-blue-800' }} mt-0.5 italic">
                                        &ldquo;{{ $order->appointment_notes }}&rdquo;
                                    </div>
                                @endif
                            </div>
                        </div>
                        <div class="text-right shrink-0">
                            <span class="inline-flex items-center gap-1 px-3 py-1 {{ $isStorePickup ? 'bg-amber-900 text-amber-50' : 'bg-blue-900 text-blue-50' }} text-[10px] font-black uppercase tracking-wider rounded-full shadow-xs">
                                Confirmed by Artisan
                            </span>
                        </div>
                    </div>
                </div>
            @endif

            {{-- 2. STATUS ACTION BANNERS --}}
            @if(in_array($statusLower, ['delivered'], true))
                <div class="px-4 sm:px-5 pb-4 pt-0">
                    <div style="background: linear-gradient(135deg, #ECFDF5 0%, #D1FAE5 100%); border: 1px solid #A7F3D0; border-radius: 14px; padding: 10px 16px;" class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 shadow-2xs">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-emerald-100/90 border border-emerald-300 text-emerald-800 flex items-center justify-center shrink-0">
                                @if($isStorePickup)
                                    <span class="text-sm">🏬</span>
                                @elseif($isSpecialDelivery)
                                    <span class="text-sm">🏍️</span>
                                @else
                                    <svg class="w-4 h-4 text-emerald-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                                @endif
                            </div>
                            <div>
                                <h3 class="text-xs sm:text-sm font-extrabold text-emerald-950 m-0">
                                    {{ $isStorePickup ? 'Order picked up at workshop' : ($isSpecialDelivery ? 'Special delivery completed' : 'Parcel delivered') }}
                                </h3>
                                <p class="text-[11px] text-emerald-800/90 mt-0.5 m-0 font-medium">Please inspect your heritage piece and confirm receipt.</p>
                            </div>
                        </div>
                        <button @click="confirmModal = true"
                            class="px-4 py-1.5 rounded-full bg-emerald-600 hover:bg-emerald-700 active:scale-95 text-white text-[11px] font-black uppercase tracking-wider transition-all shadow-xs shrink-0 cursor-pointer text-center">
                            Confirm received
                        </button>
                    </div>
                </div>
            @elseif($isSpecialDelivery && in_array($statusLower, ['in transit', 'in_transit', 'out for delivery', 'out_for_delivery'], true))
                <div class="px-4 sm:px-5 pb-4 pt-0">
                    <div style="background: linear-gradient(135deg, #EFF6FF 0%, #DBEAFE 100%); border: 1px solid #BFDBFE; border-radius: 14px; padding: 10px 16px;" class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 shadow-2xs">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-blue-100/90 border border-blue-300 text-blue-800 flex items-center justify-center shrink-0 text-sm">
                                🏍️
                            </div>
                            <div>
                                <h3 class="text-xs sm:text-sm font-extrabold text-blue-950 m-0">Special Delivery Fulfillment Progress &bull; Out for Special Delivery</h3>
                                <p class="text-[11px] text-blue-800/90 mt-0.5 m-0 font-medium">Your heritage piece is in transit via dedicated Local Artisan Rider.</p>
                            </div>
                        </div>
                        <button type="button"
                                @click="window.dispatchEvent(new CustomEvent('open-chat', { detail: { sellerId: '{{ $order->sellerId }}', sellerName: '{{ addslashes($order->seller->shopName ?? $order->seller->name ?? 'Artisan') }}' } }))"
                                style="background-color:#1E1915;color:#FFFFFF;"
                                class="px-3.5 py-1.5 rounded-full hover:bg-[#C0422A] text-[11px] font-black uppercase tracking-wider transition-all shadow-xs shrink-0 cursor-pointer text-center">
                            💬 Message Artisan
                        </button>
                    </div>
                </div>
            @elseif($statusLower === 'completed')
                @php
                    $firstItem = $order->items ? $order->items->first() : null;
                    $unreviewedItem = null;
                    $hasReviews = $order->reviews && $order->reviews->count() > 0;
                    if ($firstItem && $order->items) {
                        $unreviewedItem = $order->items->first(function($itm) use ($order) {
                            return !$order->reviews || !$order->reviews->where('orderItemId', $itm->id)->first();
                        });
                    }
                    $activeReturn = $order->returnRequests ? $order->returnRequests->first() : null;
                @endphp
                <div class="px-4 sm:px-5 pb-4 pt-0">
                    <div style="background-color:#FAF8F5;border:1px solid #ECE3D2;border-radius:14px;padding:10px 16px;" class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 shadow-2xs">
                        <div class="flex items-center gap-2.5">
                            <div class="w-7 h-7 rounded-full bg-emerald-50 border border-emerald-200 text-emerald-700 flex items-center justify-center font-black text-xs">✓</div>
                            <div>
                                <h3 class="text-xs sm:text-sm font-extrabold text-[#1E1915] m-0">Order Completed</h3>
                                <p class="text-[11px] text-[#78716C] mt-0.5 m-0">Thank you for supporting Lumban artisanal craftsmanship.</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            @if($activeReturn)
                                <span class="px-3 py-1 rounded-lg text-xs font-bold uppercase tracking-wider bg-blue-50 text-blue-900 border border-blue-200">
                                    Return: {{ ucfirst($activeReturn->status ?? 'Pending') }}
                                </span>
                            @elseif($hasReviews)
                                <span class="px-3 py-1 rounded-lg text-xs font-bold uppercase tracking-wider bg-emerald-50 text-emerald-800 border border-emerald-200">
                                    ★ Reviewed by Buyer
                                </span>
                            @else
                                @if($unreviewedItem)
                                    @php
                                        $imgForReview = \App\Support\VariationFormatter::getImageForVariation($unreviewedItem->variation, $unreviewedItem->product)
                                            ?: ($unreviewedItem->product ? $unreviewedItem->product->getImageUrl() : asset('uploads/products/default.jpg'));
                                    @endphp
                                    <button type="button"
                                        @click="reviewModal = true; reviewProductId = '{{ $unreviewedItem->productId }}'; reviewOrderItemId = '{{ $unreviewedItem->id }}'; reviewProductName = '{{ addslashes($unreviewedItem->product->name ?? 'Product') }}'; reviewProductImage = '{{ $imgForReview }}'"
                                        style="background-color:#1E1915;color:#FFFFFF;"
                                        class="px-3.5 py-1.5 rounded-xl hover:bg-[#C0422A] text-xs font-black uppercase tracking-wider transition-all shadow-xs flex items-center gap-1 cursor-pointer">
                                        <span>⭐ Rate</span>
                                    </button>
                                @endif
                                <button type="button"
                                    @click="returnModal = true;"
                                    class="px-3 py-1.5 rounded-xl bg-white hover:bg-stone-50 text-stone-700 border border-stone-300 text-xs font-bold uppercase tracking-wider transition-all shadow-2xs flex items-center gap-1 cursor-pointer">
                                    <span>Return</span>
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            @endif

            {{-- Divider --}}
            <div style="border-top:1px solid #FAF4EB;"></div>

            {{-- 3. ITEMS ORDERED --}}
            <div class="p-4 sm:p-5 space-y-3">
                <div class="flex items-center justify-between pb-2" style="border-bottom:1px solid #FAF4EB;">
                    <div style="font-family:ui-serif,Georgia,serif;font-size:12px;font-weight:700;color:#1E1915;letter-spacing:0.02em;text-transform:uppercase;">
                        Items ordered ({{ $order->items ? $order->items->count() : 0 }})
                    </div>
                    <span style="display:inline-flex;align-items:center;gap:3px;padding:2px 7px;background:#FAF5EA;border:1px solid #E6D8BA;border-radius:6px;color:#A87B10;font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:0.06em;">
                        Heritage pieces
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
                            $hasReturn = $order->returnRequests && $order->returnRequests->count() > 0;
                            $canRate = ($itemStatus === 'completed') && !$hasReturn;
                            $existingReview = $order->reviews ? $order->reviews->where('orderItemId', $item->id)->first() : null;
                            if (!$existingReview && $order->reviews) {
                                $existingReview = $order->reviews->where('productId', $item->productId)->first();
                            }
                            $itemTitle = (!empty($variationLabel) && strcasecmp($variationLabel, 'Original') !== 0) ? $variationLabel : ($item->product->name ?? 'Heritage Barong Piece');
                        @endphp
                        <div class="py-2.5 first:pt-0 last:pb-0">
                            <div class="flex items-center gap-3">
                                {{-- Thumbnail --}}
                                <div class="w-12 h-12 sm:w-14 sm:h-14 rounded-xl overflow-hidden bg-[#FAF8F5] border border-[#ECE3D2] shrink-0 cursor-pointer group"
                                     onclick="window.location.href='/products/{{ $item->productId }}'">
                                    <img src="{{ $imgSrc }}" class="w-full h-full object-cover object-top group-hover:scale-105 transition-transform duration-300" onerror="this.src='/uploads/products/default.jpg'" alt="{{ $item->product->name ?? 'Product' }}">
                                </div>

                                {{-- Info --}}
                                <div class="flex-1 min-w-0">
                                    <div class="flex flex-wrap items-baseline justify-between gap-1">
                                        <h4 class="text-xs sm:text-sm font-extrabold text-[#1E1915] truncate uppercase tracking-tight m-0">
                                            <a href="/products/{{ $item->productId }}" class="hover:text-[#C0422A] transition-colors">
                                                {{ $itemTitle }}
                                            </a>
                                        </h4>
                                    </div>
                                    <div class="flex flex-wrap items-center gap-1.5 mt-1">
                                        @if($item->size)
                                            <span class="px-2 py-0.5 bg-[#FAF8F5] text-[#1E1915] text-[11px] font-bold rounded-md border border-[#ECE3D2]">
                                                Size {{ $item->size }}
                                            </span>
                                        @endif
                                        @if($item->isPreorder())
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-amber-500/15 border border-amber-600/30 text-amber-800 text-[10px] font-black uppercase tracking-wider">
                                                <svg class="w-2.5 h-2.5 text-amber-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                                Preorder
                                            </span>
                                        @endif
                                        <span class="px-2 py-0.5 bg-[#FAF8F5] text-[#78716C] text-[11px] font-bold rounded-md border border-[#ECE3D2]">
                                            Qty {{ $item->quantity }}
                                        </span>
                                        @if($canRate && $existingReview)
                                            <span class="px-2 py-0.5 bg-emerald-50 rounded-md border border-emerald-200 text-[10px] font-extrabold text-emerald-800">
                                                ✓ {{ $existingReview->rating }}/5 Stars
                                            </span>
                                        @endif
                                        <button type="button"
                                                @click="window.dispatchEvent(new CustomEvent('open-report', { detail: { sellerId: '{{ $order->sellerId }}', productId: '{{ $item->productId }}' } }))"
                                                class="text-[10px] font-bold text-stone-400 hover:text-red-600 transition-colors flex items-center gap-1 cursor-pointer">
                                            <span>🚩 Report Product</span>
                                        </button>
                                    </div>
                                </div>

                                {{-- Price --}}
                                <div class="text-right shrink-0">
                                    <div class="text-sm font-black text-[#1E1915]">
                                        ₱{{ number_format($item->price * $item->quantity) }}
                                    </div>
                                    <div class="text-[10px] font-bold text-[#8C827A]">
                                        ₱{{ number_format($item->price) }} each
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                    @endif
                </div>
            </div>

            {{-- Divider --}}
            <div style="border-top:1px solid #FAF4EB;"></div>

            {{-- 4. UPPER SECTION: Delivery Address (Left) & Artisan Inspection Proof (Right) --}}
            <div class="grid grid-cols-1 md:grid-cols-2 divide-y md:divide-y-0 md:divide-x divide-[#FAF4EB]">
                
                {{-- Left Column: Delivery Address --}}
                <div class="p-4 sm:p-5 space-y-2.5">
                    @if($isStorePickup)
                        {{-- Workshop Pickup Address --}}
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-black uppercase tracking-wider text-[#8C827A] flex items-center gap-1.5">
                                <span>📍</span>
                                <span>Workshop Pickup</span>
                            </span>
                            <a href="{{ $directionsUrl }}" target="_blank" rel="noopener noreferrer"
                               class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider bg-[#FAF6EE] hover:bg-[#FAF0E6] text-[#996515] border border-[#E2D9C8] hover:border-[#D4AF37] transition-all shadow-2xs">
                                <span>🗺️</span>
                                <span>Directions</span>
                                <span>↗</span>
                            </a>
                        </div>
                        <div>
                            <h4 class="text-xs sm:text-sm font-extrabold text-[#1E1915] font-serif m-0">{{ $sellerShopName }}</h4>
                            <p class="text-xs text-[#78716C] leading-snug mt-0.5 m-0 font-medium">{{ $sellerWorkshopAddress }}</p>
                        </div>
                        <div class="rounded-xl overflow-hidden border border-[#E2D9C8] bg-white relative">
                            <div id="order-workshop-leaflet-map" class="w-full h-28 relative z-0"></div>
                        </div>
                        <div class="pt-1.5 border-t border-[#FAF4EB] flex flex-wrap items-center justify-between gap-1.5 text-xs">
                            <span class="text-[#78716C]">Claim: <strong class="text-[#1E1915] font-mono">#LB-OR-{{ strtoupper(substr($order->id, -8)) }}</strong></span>
                            <div class="flex items-center gap-1.5">
                                <a href="{{ route('orders.pickup-receipt', $order->id) }}" target="_blank"
                                   class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider bg-[#FAF6EE] hover:bg-[#FAF0E6] text-[#996515] border border-[#E2D9C8] transition-all shadow-2xs">
                                    View Receipt ↗
                                </a>
                                <a href="{{ route('orders.pickup-receipt.download', $order->id) }}" target="_blank"
                                   style="background-color:#1E1915;color:#FFFFFF;"
                                   class="px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider hover:bg-[#C0422A] transition-all shadow-xs">
                                    Download Pickup Receipt
                                </a>
                            </div>
                        </div>
                    @else
                        {{-- Standard/Special Delivery Address --}}
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-black uppercase tracking-wider text-[#8C827A] flex items-center gap-1.5">
                                <span>📍</span>
                                <span>Delivery address</span>
                            </span>
                            @if(!empty($addr['phone']))
                                <span class="text-[11px] font-mono text-[#1E1915] font-bold flex items-center gap-1 px-2 py-0.5 bg-[#FAF8F5] border border-[#ECE3D2] rounded-full">
                                    <span>📞</span>
                                    <span>{{ $addr['phone'] }}</span>
                                </span>
                            @endif
                        </div>
                        <div class="space-y-0.5">
                            <h4 class="text-xs sm:text-sm font-extrabold text-[#1E1915] m-0">{{ $recipient }}</h4>
                            @if($streetLine)
                                <p class="text-xs text-[#57534E] leading-snug m-0 font-medium">{{ $streetLine }}</p>
                            @endif
                            @if($locality || !empty($addr['postalCode']))
                                <p class="text-xs text-[#78716C] leading-snug m-0 font-medium">
                                    {{ $locality }}@if(!empty($addr['postalCode'])) · <span class="font-bold text-[#1E1915]">{{ $addr['postalCode'] }}</span>@endif
                                </p>
                            @endif
                        </div>

                        @if($hasCourierTracking && $order->trackingNumber)
                            <div class="pt-2.5 border-t border-[#FAF4EB] flex items-center justify-between gap-2">
                                <div class="min-w-0 flex-1">
                                    <span class="text-[#8C827A] text-[10px] font-black uppercase tracking-wider block leading-none">Tracking #</span>
                                    <div class="flex items-center gap-1.5 mt-0.5">
                                        <span class="font-mono text-[#C0422A] font-extrabold text-xs sm:text-sm tracking-tight truncate">{{ $order->trackingNumber }}</span>
                                        <button type="button" 
                                                @click="copyText('{{ $order->trackingNumber }}', 'Tracking number copied!')"
                                                class="px-2 py-0.5 bg-[#FAF8F5] hover:bg-stone-100 text-[#1E1915] border border-[#ECE3D2] rounded-md text-[10px] font-extrabold uppercase transition-all cursor-pointer shadow-2xs shrink-0">
                                            Copy
                                        </button>
                                    </div>
                                </div>
                                @if($order->trackingLink)
                                    <a href="{{ $order->trackingLink }}" target="_blank" rel="noopener noreferrer" 
                                       style="background-color:#1E1915;color:#FFFFFF;"
                                       class="inline-flex items-center gap-1 px-3 py-1.5 hover:bg-[#C0422A] rounded-xl text-[11px] font-black uppercase tracking-wider transition-all shadow-xs shrink-0 cursor-pointer text-center">
                                        <span>Track Courier Live</span>
                                        <span>↗</span>
                                    </a>
                                @endif
                            </div>
                        @endif
                    @endif
                </div>

                {{-- Right Column: Artisan Inspection Proof --}}
                <div class="p-4 sm:p-5 space-y-2.5 bg-[#FDFBF7]/30 flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xs font-black uppercase tracking-wider text-[#8C827A] flex items-center gap-1.5">
                                <span>📸</span>
                                <span>Artisan inspection proof</span>
                            </span>
                            @if($order->packingProof)
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-black text-emerald-800 bg-emerald-50 border border-emerald-200 shadow-2xs">
                                    ✓ Verified
                                </span>
                            @endif
                        </div>

                        @if($order->packingProof)
                            <div class="relative rounded-xl overflow-hidden border border-[#ECE3D2] bg-[#FAF8F5] cursor-pointer group flex items-center justify-center"
                                 style="height: 78px; width: 100%;"
                                 @click="packingModalUrl = '{{ $order->packing_proof_url }}'; packingModal = true;">
                                <img src="{{ $order->packing_proof_url }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" alt="Packing inspection proof">
                                <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                                    <span class="px-2.5 py-0.5 bg-black/80 text-white rounded-md text-[11px] font-bold backdrop-blur-xs flex items-center gap-1">
                                        🔍 Zoom
                                    </span>
                                </div>
                            </div>
                        @else
                            <div class="p-2.5 bg-[#FAF8F5] border border-[#ECE3D2] rounded-xl flex items-center gap-2 text-xs text-[#78716C]">
                                <span class="text-sm">⏳</span>
                                <div class="text-[11px] leading-tight">
                                    <strong class="text-[#1E1915] block">Photo pending</strong>
                                    <span>Artisan will upload quality inspection photo before dispatch.</span>
                                </div>
                            </div>
                        @endif
                    </div>

                    {{-- Dispatch pill for Special Delivery --}}
                    @if($isSpecialDelivery)
                        <div class="p-2 bg-blue-50/90 rounded-lg border border-blue-100 text-xs text-blue-900 leading-none font-bold flex items-center gap-2 shadow-2xs">
                            <span class="text-xs">🏍️</span>
                            <span class="text-[11px]">Dispatched via local artisan rider</span>
                        </div>
                    @endif
                </div>

            </div>

            {{-- Divider --}}
            <div style="border-top:1px solid #FAF4EB;"></div>

            {{-- 5. LOWER SECTION: Sold by Artisan (Left) & Payment and Receipt (Right) --}}
            <div class="grid grid-cols-1 md:grid-cols-2 divide-y md:divide-y-0 md:divide-x divide-[#FAF4EB]">
                
                {{-- Left Column: Sold by artisan --}}
                <div class="p-4 sm:p-5 space-y-2.5">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-black uppercase tracking-wider text-[#8C827A]">Sold by artisan</span>
                        @if($order->seller)
                            <a href="{{ route('shops.show', $order->seller->id) }}" class="text-xs font-bold text-[#C0422A] hover:underline transition-colors">
                                Visit shop ↗
                            </a>
                        @endif
                    </div>

                    @if($order->seller)
                        <div class="flex items-center gap-2.5">
                            <div class="w-9 h-9 rounded-full bg-linear-to-tr from-[#3D2B1F] to-[#C0422A] border border-[#ECE3D2] text-white flex items-center justify-center font-bold text-xs shrink-0 overflow-hidden shadow-2xs">
                                @if($order->seller?->profile_photo_url)
                                    <img src="{{ $order->seller->profile_photo_url }}" alt="{{ $order->seller->display_name ?? 'Artisan' }}" class="w-full h-full object-cover" onerror="this.src='/uploads/products/default.jpg'">
                                @else
                                    {{ strtoupper(substr($order->seller->shopName ?: ($order->seller->name ?: 'AR'), 0, 2)) }}
                                @endif
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="text-xs sm:text-sm font-extrabold text-[#1E1915] truncate">{{ $order->seller?->display_name ?? 'Artisan Shop' }}</div>
                                <div class="text-[11px] text-emerald-700 font-bold flex items-center gap-1">
                                    <svg class="w-2.5 h-2.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                    <span>{{ $order->seller?->isVerified ? 'Verified Lumban Artisan' : 'Artisan Seller' }}</span>
                                </div>
                            </div>
                        </div>

                        <button type="button"
                                @click="window.dispatchEvent(new CustomEvent('open-chat', { detail: { sellerId: '{{ $order->sellerId }}', sellerName: '{{ addslashes($order->seller->shopName ?? $order->seller->name ?? 'Artisan') }}' } }))"
                                style="background-color:#FAF5EA;border:1px solid #E6D8BA;color:#1E1915;"
                                class="w-full py-2 rounded-xl hover:bg-[#1E1915] hover:text-white text-xs font-bold uppercase tracking-wider transition-all flex items-center justify-center gap-1.5 cursor-pointer shadow-2xs">
                            <span>💬</span>
                            <span>Chat with artisan</span>
                        </button>
                    @endif
                </div>

                {{-- Right Column: Payment and receipt --}}
                <div class="p-4 sm:p-5 space-y-2.5 bg-[#FDFBF7]/30">
                    <div class="flex items-center justify-between pb-1.5" style="border-bottom:1px solid #FAF4EB;">
                        <span style="font-family:ui-serif,Georgia,serif;font-size:12px;font-weight:700;color:#1E1915;letter-spacing:0.02em;text-transform:uppercase;">
                            Payment and receipt
                        </span>
                        <span class="text-[11px] font-bold text-[#78716C] px-2 py-0.5 bg-[#FAF8F5] rounded-md border border-[#ECE3D2]">
                            {{ $order->formatted_payment_method }}
                        </span>
                    </div>

                    <div class="space-y-1.5 text-xs">
                        <div class="flex items-center justify-between text-[#78716C]">
                            <span>Subtotal</span>
                            <span class="font-bold text-[#1E1915]">
                                ₱{{ number_format(($order->totalAmount ?? 0) - ($order->shippingFee ?? ($order->shipping?->shipping_fee ?? 0)), 2) }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between text-[#78716C]">
                            <span>{{ $isStorePickup ? 'Fulfillment' : 'Shipping fee' }}</span>
                            <span class="font-bold text-[#1E1915]">
                                ₱{{ number_format($isStorePickup ? 0 : ($order->shipping?->shipping_fee ?? ($order->shippingFee ?? 0)), 2) }}
                            </span>
                        </div>

                        @if(in_array(strtoupper($order->paymentMethod ?? ''), ['GCASH', 'MAYA']) && !empty($order->paymentReference) && !str_starts_with($order->paymentReference, 'COD-'))
                            <div class="pt-0.5 flex items-center justify-between text-[#78716C]">
                                <span class="text-[10px] uppercase tracking-wider">Ref:</span>
                                <div class="flex items-center gap-1 font-mono text-[11px] text-[#1E1915]">
                                    <span>{{ $order->paymentReference }}</span>
                                    <button type="button" @click="copyText('{{ $order->paymentReference }}', 'Reference copied!')" class="text-[10px] text-[#78716C] hover:text-[#1E1915] uppercase font-bold">Copy</button>
                                </div>
                            </div>
                        @endif
                    </div>

                    {{-- Grand Total --}}
                    <div class="pt-2 border-t border-dashed border-[#ECE3D2] flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-[#78716C]">Grand total</span>
                        <span class="text-lg sm:text-xl font-black text-[#C0422A]">
                            ₱{{ number_format($order->totalAmount ?? 0, 2) }}
                        </span>
                    </div>

                    {{-- Customer-Facing Sukli / Overpayment Status Card --}}
                    @php
                        $cAuthoritativeSukli = $order->authoritativeSukliAmount();
                        $cSukliStatus = $order->sukliRefundStatus();
                        $cLatestSukliTx = $order->latestSukliRefundTransaction();
                        
                        $sukliCardClass = match($cSukliStatus) {
                            'REFUNDED' => 'bg-emerald-50/80 border-emerald-200 text-emerald-950',
                            'OVERPAYMENT_PENDING_REFUND' => 'bg-blue-50/80 border-blue-200 text-blue-950',
                            'REFUND_PROCESSING' => 'bg-purple-50/80 border-purple-200 text-purple-950',
                            default => 'bg-amber-50/80 border-amber-200 text-amber-950',
                        };
                        
                        $sukliBadgeClass = match($cSukliStatus) {
                            'REFUNDED' => 'bg-emerald-200 text-emerald-900',
                            'OVERPAYMENT_PENDING_REFUND' => 'bg-blue-200 text-blue-900',
                            'REFUND_PROCESSING' => 'bg-purple-200 text-purple-900',
                            default => 'bg-amber-200 text-amber-900',
                        };
                    @endphp
                    @if($cAuthoritativeSukli > 0)
                        <div class="mt-3 p-3 rounded-xl border text-xs space-y-1.5 transition-all {{ $sukliCardClass }}">
                            <div class="flex items-center justify-between font-bold">
                                <span class="flex items-center gap-1.5">
                                    <span>💸</span>
                                    <span>Sukli / Excess Payment</span>
                                </span>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider {{ $sukliBadgeClass }}">
                                    ₱{{ number_format($cAuthoritativeSukli, 2) }}
                                </span>
                            </div>

                            <p class="text-[11px] leading-relaxed m-0 font-medium">
                                @if($cSukliStatus === 'REFUNDED')
                                    Your <strong>₱{{ number_format($cAuthoritativeSukli, 2) }}</strong> sukli refund has been processed.
                                @elseif($cSukliStatus === 'OVERPAYMENT_PENDING_REFUND')
                                    Your payment has been verified. Your <strong>₱{{ number_format($cAuthoritativeSukli, 2) }}</strong> sukli is awaiting refund processing{{ $order->masked_refund_phone ? ' to ' . $order->masked_refund_phone : '' }}.
                                @elseif($cSukliStatus === 'REFUND_PROCESSING')
                                    Your <strong>₱{{ number_format($cAuthoritativeSukli, 2) }}</strong> sukli refund is currently being processed.
                                @else
                                    Your payment has an excess amount of <strong>₱{{ number_format($cAuthoritativeSukli, 2) }}</strong>. When the Admin verifies your order, your sukli will be processed using the refund details you provided{{ $order->masked_refund_phone ? ' (' . $order->masked_refund_phone . ')' : '' }}.
                                @endif
                            </p>

                            @if($cSukliStatus === 'REFUNDED' && $cLatestSukliTx)
                                <div class="pt-1 border-t border-emerald-200 flex flex-wrap items-center justify-between gap-1 text-[10px] text-emerald-800 font-mono">
                                    <span>Ref: <strong>{{ $cLatestSukliTx->transfer_reference ?? 'Manual Transfer' }}</strong></span>
                                    @if($cLatestSukliTx->created_at)
                                        <span>{{ $cLatestSukliTx->created_at->format('M d, Y g:i A') }}</span>
                                    @endif
                                </div>
                            @endif
                        </div>
                    @endif
                </div>

            </div>

            {{-- 6. AUTHENTIC HERITAGE GUARANTEE FOOTER --}}
            <div class="border-t border-[#FAF4EB] bg-[#FAF8F5]/50 py-2.5 px-4 text-center text-xs text-[#78716C] flex items-center justify-center gap-2">
                <span>🛡️</span>
                <span><strong>Lumban Heritage Guarantee:</strong> Verified genuine artisanal embroidery craft.</span>
            </div>

        </div>

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
                    <h3 class="text-sm font-black text-[#1E1915] uppercase tracking-tight">Request Order Cancellation</h3>
                    <p class="text-[10px] text-[#78716C] font-medium">Please select a reason for requesting cancellation. Your request will be sent to the artisan for review.</p>
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

                <div class="p-3 bg-amber-50 border border-amber-200 rounded-xl text-[10px] text-amber-800 leading-relaxed">
                    <strong>Note:</strong> Cancellation requests require artisan confirmation. If approved, the order will be cancelled and items restocked.
                </div>

                <div class="flex gap-2.5 pt-2">
                    <button type="button" @click="cancelModal = false" :disabled="cancelLoading" class="flex-1 py-2.5 rounded-full border border-[#ECE3D2] text-xs font-bold text-[#78716C] hover:bg-[#FAF8F5] transition-all cursor-pointer">
                        Keep Order
                    </button>
                    <button type="submit" :disabled="cancelLoading" class="flex-1 py-2.5 rounded-full bg-red-600 hover:bg-red-700 text-white text-xs font-bold uppercase tracking-wider shadow-sm transition-all flex items-center justify-center gap-1.5 cursor-pointer disabled:opacity-50">
                        <template x-if="cancelLoading">
                            <svg class="w-3.5 h-3.5 animate-spin text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/></svg>
                        </template>
                        <span x-text="cancelLoading ? 'Submitting...' : 'Request Cancellation'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Request Return Modal --}}
    <div x-show="returnModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm" x-cloak style="display: none;">
        <div @click.away="returnModal = false" style="background-color:#FDFBF7;border:1px solid #EAE2D2;border-radius:28px;box-shadow:0 25px 60px rgba(0,0,0,0.25);" class="w-full max-w-md p-6 space-y-4 text-gray-900">
            <div class="flex items-center gap-3 pb-3 border-b border-[#EAE1D0]">
                <div class="w-10 h-10 rounded-full bg-[#FAF5EA] border border-[#E6D8BA] text-[#A87B10] flex items-center justify-center font-bold text-base shrink-0">
                    ↩
                </div>
                <div>
                    <h3 class="text-sm font-black text-[#1E1915] uppercase tracking-tight">Request Item Return</h3>
                    <p class="text-[10px] text-[#78716C] font-medium">Submit a return request to the artisan. Please specify the reason and attach photo evidence of the item.</p>
                </div>
            </div>

            <form action="{{ route('orders.return', $order->id) }}" method="POST" enctype="multipart/form-data" class="space-y-3.5" @submit="returnLoading = true">
                @csrf
                <input type="hidden" name="orderId" value="{{ $order->id }}">
                <div class="space-y-1.5">
                    <label class="text-[10px] font-black uppercase tracking-widest text-[#78716C]">Reason for Return <span class="text-red-500">*</span></label>
                    <select name="reason" x-model="returnReason" class="w-full px-3.5 py-2.5 bg-[#FAF8F5] border border-[#ECE3D2] rounded-xl text-xs font-semibold text-[#1E1915] outline-none focus:border-[#C49520] focus:bg-white transition-all">
                        <option value="Damaged / Defective item">Damaged / Defective item</option>
                        <option value="Item does not match description / pictures">Item does not match description / pictures</option>
                        <option value="Wrong size or incorrect fitting">Wrong size or incorrect fitting</option>
                        <option value="Incomplete item or missing parts">Incomplete item or missing parts</option>
                        <option value="Received wrong item">Received wrong item</option>
                        <option value="Other">Other reason</option>
                    </select>
                </div>

                <div class="space-y-1">
                    <label class="text-[10px] font-black uppercase tracking-widest text-[#78716C]">Details / Explanation</label>
                    <textarea name="message" x-model="returnMessage" rows="3" placeholder="Explain the issue in detail to help the artisan assess your return request..." class="w-full px-3.5 py-2.5 bg-[#FAF8F5] border border-[#ECE3D2] rounded-xl text-xs font-medium outline-none focus:border-[#C49520] focus:bg-white resize-none"></textarea>
                </div>

                <div class="space-y-1">
                    <label class="text-[10px] font-black uppercase tracking-widest text-[#78716C] flex items-center justify-between">
                        <span>Photo / Evidence <span class="text-red-500">*</span></span>
                        <span class="text-stone-400 font-normal">Max 10MB</span>
                    </label>
                    <input type="file" name="proof_files[]" multiple required accept="image/*" class="w-full px-3 py-2 bg-[#FAF8F5] border border-[#ECE3D2] rounded-xl text-xs text-stone-600 file:mr-3 file:py-1 file:px-2.5 file:rounded-md file:border-0 file:text-[10px] file:font-bold file:bg-[#1E1915] file:text-white hover:file:bg-[#C0420A] cursor-pointer">
                    <p class="text-[9px] text-[#78716C] font-medium">Please attach at least one clear photo of the item, defect, or tag as proof for the artisan.</p>
                </div>

                <div class="p-3 bg-amber-50 border border-amber-200 rounded-xl text-[10px] text-amber-800 leading-relaxed">
                    <strong>Artisan Return Policy:</strong> Returned items must be unworn and in original condition with tags intact. The artisan will inspect your request and respond promptly.
                </div>

                <div class="flex gap-2.5 pt-2">
                    <button type="button" @click="returnModal = false" :disabled="returnLoading" class="flex-1 py-2.5 rounded-full border border-[#ECE3D2] text-xs font-bold text-[#78716C] hover:bg-[#FAF8F5] transition-all cursor-pointer">
                        Cancel
                    </button>
                    <button type="submit" :disabled="returnLoading" class="flex-1 py-2.5 rounded-full bg-[#1E1915] hover:bg-[#C0420A] text-white text-xs font-bold uppercase tracking-wider shadow-sm transition-all flex items-center justify-center gap-1.5 cursor-pointer disabled:opacity-50">
                        <template x-if="returnLoading">
                            <svg class="w-3.5 h-3.5 animate-spin text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/></svg>
                        </template>
                        <span x-text="returnLoading ? 'Submitting...' : 'Submit Request'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>

@if($isStorePickup)
    {{-- Leaflet Assets for Workshop Map --}}
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <script id="order-workshop-map-config" type="application/json">
    {!! json_encode([
        'lat' => $sellerWorkshopLat,
        'lng' => $sellerWorkshopLng,
        'shopName' => $sellerShopName,
        'shopAddress' => $sellerWorkshopAddress,
        'directionsUrl' => $directionsUrl,
    ]) !!}
    </script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const mapContainer = document.getElementById('order-workshop-leaflet-map');
        if (!mapContainer || typeof L === 'undefined') return;

        const configEl = document.getElementById('order-workshop-map-config');
        const config = configEl ? JSON.parse(configEl.textContent || '{}') : {};

        const lat = Number(config.lat || 14.2988);
        const lng = Number(config.lng || 121.4606);
        const shopName = config.shopName || 'Artisan Workshop';
        const shopAddress = config.shopAddress || 'Lumban, Laguna';
        const directionsUrl = config.directionsUrl || '#';

        const map = L.map('order-workshop-leaflet-map', {
            zoomControl: true,
            attributionControl: false
        }).setView([lat, lng], 16);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19
        }).addTo(map);

        const shopPinIcon = L.divIcon({
            className: 'lumbarong-workshop-pin-icon',
            html: '<div style="position:relative;width:38px;height:38px;display:flex;align-items:center;justify-content:center;"><div style="width:34px;height:34px;background:#1E1915;border:2.5px solid #DFC97A;border-radius:50% 50% 50% 0;transform:rotate(-45deg);display:flex;align-items:center;justify-content:center;box-shadow:0 4px 14px rgba(0,0,0,0.4);"><span style="transform:rotate(45deg);color:#DFC97A;font-size:14px;font-weight:900;">🏛️</span></div><div style="position:absolute;bottom:-6px;width:12px;height:4px;background:rgba(0,0,0,0.3);border-radius:50%;filter:blur(1px);"></div></div>',
            iconSize: [38, 38],
            iconAnchor: [19, 38],
            popupAnchor: [0, -38]
        });

        const marker = L.marker([lat, lng], { icon: shopPinIcon }).addTo(map);
        marker.bindPopup('<div style="font-family:sans-serif;padding:2px;"><div style="font-weight:800;color:#1E1915;font-size:12px;">' + shopName + '</div><div style="font-size:11px;color:#666;margin-top:2px;">' + shopAddress + '</div><a href="' + directionsUrl + '" target="_blank" style="display:inline-block;margin-top:6px;font-size:10px;font-weight:800;color:#C49520;text-transform:uppercase;text-decoration:none;">Get Directions ↗</a></div>').openPopup();

        setTimeout(function() { map.invalidateSize(); }, 300);
    });
    </script>
@endif
@endsection
