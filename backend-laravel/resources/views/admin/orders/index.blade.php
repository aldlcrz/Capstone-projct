@extends('layouts.admin')

@section('content')
<div class="space-y-5" x-data="{
    verifyModal: false,
    rejectModal: false,
    receiptModal: false,
    sukliModal: false,
    selectedOrder: null,
    receiptUrl: '',
    receiptRef: '',
    receiptAmount: '',
    rejectReason: '',
    rejectPreset: '',
    sukliAmount: 0,
    sukliDestAccount: '',
    sukliDestName: '',
    sukliTransferRef: '',
    sukliNotes: '',
    isSubmitting: false,

    openVerify(order) {
        this.selectedOrder = order;
        this.verifyModal = true;
    },

    openReject(order) {
        this.selectedOrder = order;
        this.rejectPreset = 'Receipt is blurred or unreadable';
        this.rejectReason = 'Receipt is blurred or unreadable';
        this.rejectModal = true;
    },

    openReceipt(order, url) {
        this.selectedOrder = order;
        this.receiptUrl = url;
        this.receiptRef = order.paymentReference || (order.latest_payment_transaction ? order.latest_payment_transaction.reference_number : 'N/A');
        this.receiptAmount = order.totalAmount;
        this.receiptModal = true;
    },

    openSukliModal(order) {
        this.selectedOrder = order;
        this.sukliAmount = (order.remaining_sukli_refund_amount !== undefined ? order.remaining_sukli_refund_amount : (order.overpayment_amount || 0));
        this.sukliDestAccount = order.refund_mobile_number || (order.customer ? (order.customer.phone || order.customer.contactNumber || '') : '');
        this.sukliDestName = order.customer ? order.customer.name : '';
        this.sukliTransferRef = '';
        this.sukliNotes = '';
        this.sukliModal = true;
    },

    setRejectPreset(text) {
        this.rejectPreset = text;
        this.rejectReason = text;
    },

    getOrderProductSummary(order) {
        if (!order || !order.items || !order.items.length) return 'Heritage Piece';
        const first = order.items[0];
        const name = first.product_name || (first.product ? first.product.name : 'Heritage Piece');
        const variant = (first.display_variation || first.variation || '').trim();
        const size = (first.size || '').trim();
        
        let details = [];
        if (variant && variant.toLowerCase() !== 'original' && variant.toLowerCase() !== 'none' && variant.toLowerCase() !== name.toLowerCase()) {
            details.push(variant);
        }
        if (size && size.toLowerCase() !== 'free size' && size.toLowerCase() !== 'n/a' && size.toLowerCase() !== 'none') {
            if (!details.some(d => d.toLowerCase() === size.toLowerCase())) {
                details.push('Size: ' + size);
            }
        }

        let text = name;
        if (details.length > 0) {
            text += ' (' + details.join(', ') + ')';
        }
        if (order.items.length > 1) {
            text += ' + ' + (order.items.length - 1) + ' more';
        }
        return text;
    },

    copyToClipboard(text) {
        if (!text || text === 'N/A') return;
        navigator.clipboard.writeText(text);
        if (window.showToast) {
            window.showToast('Reference number copied to clipboard', 'success');
        } else {
            alert('Copied: ' + text);
        }
    }
}">

    {{-- ═══ PAGE HEADER & SEARCH TOOLBAR ═══ --}}
    <div id="tour-admin-orders-header" class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
        {{-- Left: Title & Subtitle --}}
        <div class="text-left space-y-0.5 shrink-0">
            <div class="inline-flex items-center gap-2">
                <span class="text-[9px] font-black uppercase tracking-[0.25em] text-[#C0422A]">Orders & Transactions</span>
                <span class="text-gray-300 text-xs">·</span>
                <span class="text-[9px] font-bold uppercase tracking-[0.2em] text-gray-400">Admin Center</span>
            </div>
            <div class="flex items-center gap-3 flex-wrap">
                <h1 class="font-serif text-2xl sm:text-3xl font-bold text-gray-900 leading-tight">
                    Payment <span class="text-[#C0420A] font-light italic">Verification</span>
                </h1>
                <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-amber-50 text-amber-800 border border-amber-200/80 shadow-xs">
                    <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
                    <span>{{ $counts['pending_verification'] ?? 0 }} Pending Review</span>
                </div>
            </div>
            <p class="text-[11px] text-gray-500 font-medium">Verify incoming GCash & Maya customer payments, inspect receipts, and manage marketplace transactions.</p>
        </div>

        {{-- Right: Search & Filter Toolbar --}}
        <div id="tour-admin-orders-search" class="flex-1 max-w-xl lg:max-w-2xl w-full">
            <form method="GET" action="{{ route('admin.orders') }}" class="flex flex-col sm:flex-row items-center gap-2 w-full">
                @if(request('status'))
                    <input type="hidden" name="status" value="{{ request('status') }}">
                @endif
                <div class="relative w-full flex-1">
                    <input type="text" name="search" value="{{ request('search') }}"
                           placeholder="Search by Order ID, Reference #, Customer, Seller..."
                           class="w-full pl-10 pr-4 py-2.5 bg-white border border-gray-200 rounded-xl text-xs sm:text-sm text-gray-800 placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-[#C0422A]/20 focus:border-[#C0422A] shadow-xs transition-all">
                    <svg class="w-4 h-4 text-gray-400 absolute left-3.5 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>

                {{-- Payment Method Selector --}}
                <div class="w-full sm:w-auto shrink-0">
                    <select name="payment_method" onchange="this.form.submit()"
                            class="w-full sm:w-auto px-3 py-2.5 bg-white border border-gray-200 rounded-xl text-xs font-bold text-gray-700 focus:outline-none focus:ring-2 focus:ring-[#C0422A]/20 focus:border-[#C0422A] shadow-xs cursor-pointer">
                        <option value="all" {{ request('payment_method') === 'all' || !request('payment_method') ? 'selected' : '' }}>All Payment Methods</option>
                        <option value="gcash" {{ request('payment_method') === 'gcash' ? 'selected' : '' }}>GCash Only</option>
                        <option value="maya" {{ request('payment_method') === 'maya' ? 'selected' : '' }}>Maya Only</option>
                        <option value="cod" {{ request('payment_method') === 'cod' ? 'selected' : '' }}>COD / Pay in Shop</option>
                    </select>
                </div>

                @if(request('search') || (request('payment_method') && request('payment_method') !== 'all'))
                    <a href="{{ route('admin.orders', ['status' => request('status')]) }}" 
                       class="px-3.5 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-xl text-xs font-bold transition-all shrink-0">
                        Clear
                    </a>
                @endif
            </form>
        </div>
    </div>

    {{-- ═══ STATUS FILTER CARDS ═══ --}}
    @php
        $currStatus = request('status', 'pending_verification');
        $isPending  = $currStatus === 'pending_verification';
        $isAll      = $currStatus === 'all';
        $isVerified = $currStatus === 'verified';
        $isRejected = $currStatus === 'rejected';
        $isCod      = $currStatus === 'cod';
    @endphp

    <div id="tour-admin-orders-stats" class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3">
        {{-- 1. All Orders --}}
        <a href="{{ request()->fullUrlWithQuery(['status' => 'all', 'page' => 1]) }}"
           class="group relative rounded-2xl px-3.5 py-3 flex items-center justify-between border transition-all duration-200 cursor-pointer {{ $isAll ? 'bg-gray-900 text-white border-gray-900 ring-2 ring-gray-900/20 shadow-sm -translate-y-0.5' : 'bg-white border-gray-200 hover:border-gray-400 hover:shadow-sm hover:-translate-y-0.5' }}">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-xl flex items-center justify-center shrink-0 transition-colors {{ $isAll ? 'bg-white/20 text-white' : 'bg-gray-100 text-gray-600 group-hover:bg-gray-200' }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                </div>
                <div>
                    <div class="text-sm sm:text-base font-black {{ $isAll ? 'text-white' : 'text-gray-900' }} leading-none">{{ $counts['all'] ?? 0 }}</div>
                    <div class="text-[9px] font-bold uppercase tracking-wider {{ $isAll ? 'text-gray-300' : 'text-gray-400' }} mt-1">All Orders</div>
                </div>
            </div>
            @if($isAll)
                <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-full text-[8px] font-black uppercase tracking-wider bg-white/20 text-white shadow-xs">
                    Active
                </span>
            @endif
        </a>

        {{-- 2. Pending Verification --}}
        <a href="{{ request()->fullUrlWithQuery(['status' => 'pending_verification', 'page' => 1]) }}"
           class="group relative rounded-2xl px-3.5 py-3 flex items-center justify-between border transition-all duration-200 cursor-pointer {{ $isPending ? 'bg-amber-50/70 border-amber-500 ring-2 ring-amber-500/20 shadow-sm -translate-y-0.5' : 'bg-white border-amber-200/60 hover:border-amber-400 hover:shadow-sm hover:-translate-y-0.5' }}">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-xl flex items-center justify-center shrink-0 transition-colors {{ $isPending ? 'bg-amber-500 text-white' : 'bg-amber-100 text-amber-700 group-hover:bg-amber-200' }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div>
                    <div class="text-sm sm:text-base font-black text-gray-900 leading-none">{{ $counts['pending_verification'] ?? 0 }}</div>
                    <div class="text-[9px] font-bold uppercase tracking-wider text-amber-700 mt-1">Pending</div>
                </div>
            </div>
            @if($isPending)
                <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-full text-[8px] font-black uppercase tracking-wider bg-amber-600 text-white shadow-xs">
                    Active
                </span>
            @endif
        </a>

        {{-- 3. Verified / Paid --}}
        <a href="{{ request()->fullUrlWithQuery(['status' => 'verified', 'page' => 1]) }}"
           class="group relative rounded-2xl px-3.5 py-3 flex items-center justify-between border transition-all duration-200 cursor-pointer {{ $isVerified ? 'bg-emerald-50/70 border-emerald-600 ring-2 ring-emerald-600/20 shadow-sm -translate-y-0.5' : 'bg-white border-emerald-100 hover:border-emerald-300 hover:shadow-sm hover:-translate-y-0.5' }}">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-xl flex items-center justify-center shrink-0 transition-colors {{ $isVerified ? 'bg-emerald-600 text-white' : 'bg-emerald-50 text-emerald-600 group-hover:bg-emerald-100' }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div>
                    <div class="text-sm sm:text-base font-black text-gray-900 leading-none">{{ $counts['verified'] ?? 0 }}</div>
                    <div class="text-[9px] font-bold uppercase tracking-wider text-emerald-700 mt-1">Verified</div>
                </div>
            </div>
            @if($isVerified)
                <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-full text-[8px] font-black uppercase tracking-wider bg-emerald-600 text-white shadow-xs">
                    Active
                </span>
            @endif
        </a>

        {{-- 4. Rejected --}}
        <a href="{{ request()->fullUrlWithQuery(['status' => 'rejected', 'page' => 1]) }}"
           class="group relative rounded-2xl px-3.5 py-3 flex items-center justify-between border transition-all duration-200 cursor-pointer {{ $isRejected ? 'bg-rose-50/70 border-rose-600 ring-2 ring-rose-600/20 shadow-sm -translate-y-0.5' : 'bg-white border-rose-100 hover:border-rose-300 hover:shadow-sm hover:-translate-y-0.5' }}">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-xl flex items-center justify-center shrink-0 transition-colors {{ $isRejected ? 'bg-rose-600 text-white' : 'bg-rose-50 text-rose-600 group-hover:bg-rose-100' }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div>
                    <div class="text-sm sm:text-base font-black text-gray-900 leading-none">{{ $counts['rejected'] ?? 0 }}</div>
                    <div class="text-[9px] font-bold uppercase tracking-wider text-rose-700 mt-1">Rejected</div>
                </div>
            </div>
            @if($isRejected)
                <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-full text-[8px] font-black uppercase tracking-wider bg-rose-600 text-white shadow-xs">
                    Active
                </span>
            @endif
        </a>

        {{-- 5. COD / In-Shop --}}
        <a href="{{ request()->fullUrlWithQuery(['status' => 'cod', 'page' => 1]) }}"
           class="group relative rounded-2xl px-3.5 py-3 flex items-center justify-between border transition-all duration-200 cursor-pointer {{ $isCod ? 'bg-slate-100 border-slate-700 ring-2 ring-slate-700/20 shadow-sm -translate-y-0.5' : 'bg-white border-gray-200 hover:border-gray-400 hover:shadow-sm hover:-translate-y-0.5' }}">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-xl flex items-center justify-center shrink-0 transition-colors {{ $isCod ? 'bg-slate-700 text-white' : 'bg-slate-100 text-slate-600 group-hover:bg-slate-200' }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                </div>
                <div>
                    <div class="text-sm sm:text-base font-black text-gray-900 leading-none">{{ $counts['cod'] ?? 0 }}</div>
                    <div class="text-[9px] font-bold uppercase tracking-wider text-slate-600 mt-1">COD / Store</div>
                </div>
            </div>
            @if($isCod)
                <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-full text-[8px] font-black uppercase tracking-wider bg-slate-700 text-white shadow-xs">
                    Active
                </span>
            @endif
        </a>
    </div>

    {{-- ═══ ORDERS TABLE & VERIFICATION LIST ═══ --}}
    <div class="space-y-3">
        {{-- Results count bar --}}
        <div class="flex items-center justify-between px-1">
            <div class="text-[11px] font-bold text-gray-500">
                Showing <span class="text-gray-900 font-black">{{ $allOrders->total() }}</span> {{ $allOrders->total() === 1 ? 'order' : 'orders' }}
                @if(request('status'))
                    <span class="text-gray-400">· Filter: <span class="capitalize font-bold text-gray-700">{{ str_replace('_', ' ', request('status')) }}</span></span>
                @endif
                @if(request('payment_method') && request('payment_method') !== 'all')
                    <span class="text-gray-400">· Method: <span class="uppercase font-bold text-gray-700">{{ request('payment_method') }}</span></span>
                @endif
                @if(request('search'))
                    <span class="text-gray-400">· Query: "<span class="font-bold text-gray-700">{{ request('search') }}</span>"</span>
                @endif
            </div>
        </div>

        {{-- Table Container --}}
        <div id="tour-admin-orders-table" class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
            <div class="overflow-x-auto no-scrollbar">
                <table class="w-full text-left min-w-220">
                    <thead>
                        <tr class="border-b border-gray-100 bg-gray-50/75">
                            <th class="px-4 py-3 text-[9px] font-black uppercase tracking-widest text-gray-400">Product & Variant</th>
                            <th class="px-4 py-3 text-[9px] font-black uppercase tracking-widest text-gray-400">Customer</th>
                            <th class="px-4 py-3 text-[9px] font-black uppercase tracking-widest text-gray-400">Artisan / Seller</th>
                            <th class="px-4 py-3 text-[9px] font-black uppercase tracking-widest text-gray-400">Payment Details</th>
                            <th class="px-4 py-3 text-[9px] font-black uppercase tracking-widest text-gray-400 text-center">Receipt Proof</th>
                            <th class="px-4 py-3 text-[9px] font-black uppercase tracking-widest text-gray-400">Status</th>
                            <th class="px-4 py-3 text-[9px] font-black uppercase tracking-widest text-gray-400 text-right">Action / Commission</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @forelse($allOrders as $order)
                            @php
                                $pm = strtolower($order->paymentMethod ?? '');
                                $isEwallet = in_array($pm, ['gcash', 'maya', 'paymaya']);
                                $isPaid = in_array($order->paymentStatus, ['Paid', 'Verified']);
                                $isRejectedStatus = in_array($order->paymentStatus, ['Payment Rejected', 'Rejected']);
                                $isPendingReview = $isEwallet && !$isPaid && !$isRejectedStatus && $order->status !== 'Cancelled';
                                
                                $refNumber = $order->paymentReference ?: ($order->latestPaymentTransaction->reference_number ?? null);
                                $receiptUrl = $order->payment_proof_url;

                                $activeRate = (float) ($commissionRate ?? 5.0);
                                $orderCommission = round($order->totalAmount * ($activeRate / 100), 2);
                            @endphp
                            <tr class="hover:bg-amber-50/20 transition-colors group">
                                {{-- 1. Product & Variant / Date --}}
                                <td class="px-4 py-3.5">
                                    <div class="space-y-1 max-w-60">
                                        @php
                                            $firstItem = $order->items->first();
                                            $itemCount = $order->items->count();
                                            $productName = $firstItem ? ($firstItem->product_name ?: ($firstItem->product->name ?? 'Artisan Item')) : 'No Item Details';
                                            $rawVariant = $firstItem ? ($firstItem->display_variation ?? $firstItem->variation) : null;
                                            $rawSize = $firstItem ? $firstItem->size : null;

                                            $hasVariant = !empty($rawVariant) 
                                                && strcasecmp($rawVariant, 'Original') !== 0 
                                                && strcasecmp($rawVariant, 'None') !== 0 
                                                && strcasecmp($rawVariant, 'N/A') !== 0
                                                && strcasecmp($rawVariant, $productName) !== 0;

                                            $hasSize = !empty($rawSize) 
                                                && strcasecmp($rawSize, 'Free Size') !== 0 
                                                && strcasecmp($rawSize, 'N/A') !== 0 
                                                && strcasecmp($rawSize, 'None') !== 0;

                                            $isSameVariantAndSize = $hasVariant && $hasSize && (strcasecmp(trim($rawVariant), trim($rawSize)) === 0);
                                        @endphp

                                        {{-- Primary Product Name --}}
                                        <div class="text-xs font-bold text-gray-900 leading-snug truncate" title="{{ $productName }}">
                                            {{ $productName }}
                                        </div>

                                        {{-- Variant & Size Badges --}}
                                        @if($firstItem)
                                            <div class="flex items-center gap-1.5 flex-wrap">
                                                {{-- Distinct Variant (e.g. Color / Style) --}}
                                                @if($hasVariant && !$isSameVariantAndSize)
                                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold bg-amber-50 text-amber-900 border border-amber-200/80">
                                                        {{ $rawVariant }}
                                                    </span>
                                                @endif

                                                {{-- Size Badge --}}
                                                @if($hasSize)
                                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold bg-gray-100 text-gray-700 border border-gray-200">
                                                        Size: {{ $rawSize }}
                                                    </span>
                                                @elseif($hasVariant && $isSameVariantAndSize)
                                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold bg-gray-100 text-gray-700 border border-gray-200">
                                                        Size: {{ $rawVariant }}
                                                    </span>
                                                @elseif(!$hasVariant)
                                                    <span class="text-[10px] text-gray-400 font-medium">Standard</span>
                                                @endif

                                                {{-- Quantity --}}
                                                @if($firstItem->quantity > 1)
                                                    <span class="text-[10px] font-bold text-gray-500 font-mono">
                                                        ×{{ $firstItem->quantity }}
                                                    </span>
                                                @endif
                                            </div>
                                        @endif

                                        {{-- Additional items indicator if multiple items --}}
                                        @if($itemCount > 1)
                                            <div class="text-[9px] font-bold text-[#C0422A]">
                                                + {{ $itemCount - 1 }} more {{ $itemCount - 1 === 1 ? 'item' : 'items' }}
                                            </div>
                                        @endif

                                        {{-- Secondary Date & Order ID reference --}}
                                        <div class="text-[10px] text-gray-400 font-medium flex items-center gap-1.5 pt-0.5">
                                            <span>{{ $order->createdAt ? $order->createdAt->format('M d, Y · h:i A') : 'N/A' }}</span>
                                            <span class="text-gray-300">·</span>
                                            <span class="font-mono text-[9px] text-gray-400">#LB-{{ strtoupper(substr($order->id, -8)) }}</span>
                                        </div>
                                    </div>
                                </td>

                                {{-- 2. Customer --}}
                                <td class="px-4 py-3.5">
                                    <div class="flex items-center gap-2.5">
                                        <div class="w-8 h-8 rounded-full bg-[#FFF5F2] text-[#C0422A] font-bold text-xs flex items-center justify-center shrink-0 border border-[#C0422A]/20">
                                            {{ strtoupper(substr($order->customer->name ?? 'C', 0, 1)) }}
                                        </div>
                                        <div class="min-w-0">
                                            <div class="text-xs font-bold text-gray-900 truncate leading-snug">
                                                {{ $order->customer->name ?? 'Guest / Deleted' }}
                                            </div>
                                            <div class="text-[10px] text-gray-400 truncate mt-0.5">
                                                {{ $order->customer->email ?? 'No email' }}
                                            </div>
                                            @if(!empty($order->customer->phone ?? $order->customer->contactNumber))
                                                <div class="text-[10px] text-gray-500 font-mono mt-0.5">
                                                    {{ $order->customer->phone ?? $order->customer->contactNumber }}
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </td>

                                {{-- 3. Artisan / Seller --}}
                                <td class="px-4 py-3.5">
                                    <div class="min-w-0">
                                        <div class="text-xs font-bold text-gray-800 truncate">
                                            {{ $order->seller->shopName ?? $order->seller->name ?? 'LumBarong Artisan' }}
                                        </div>
                                        <div class="text-[10px] text-gray-400 truncate mt-0.5">
                                            {{ $order->seller->email ?? 'Artisan Partner' }}
                                        </div>
                                        <div class="inline-flex items-center gap-1 text-[9px] font-semibold text-gray-500 mt-1">
                                            <span class="w-1.5 h-1.5 rounded-full bg-gray-400"></span>
                                            Order: <span class="capitalize">{{ $order->status }}</span>
                                        </div>
                                    </div>
                                </td>

                                {{-- 4. Payment Details --}}
                                <td class="px-4 py-3.5">
                                    <div class="space-y-1">
                                        <div class="flex items-center justify-between gap-1">
                                            <div class="text-sm font-black text-gray-900 font-mono">
                                                ₱{{ number_format($order->totalAmount, 2) }}
                                            </div>
                                            @if($order->isOverpaid())
                                                <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[9px] font-black bg-amber-100 text-amber-900 border border-amber-300">
                                                    <span>💰 Sukli:</span>
                                                    <span class="font-mono">₱{{ number_format($order->authoritativeSukliAmount(), 2) }}</span>
                                                </span>
                                            @endif
                                        </div>

                                        {{-- Payment Method Badge --}}
                                        <div class="flex items-center gap-1.5 flex-wrap">
                                            @if($pm === 'gcash')
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-black bg-blue-50 text-blue-700 border border-blue-200">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-blue-600"></span> GCash
                                                </span>
                                            @elseif(in_array($pm, ['maya', 'paymaya']))
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-black bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-600"></span> Maya
                                                </span>
                                            @else
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-bold bg-gray-100 text-gray-700 border border-gray-200">
                                                    {{ $order->formatted_payment_method ?? $order->paymentMethod ?? 'COD' }}
                                                </span>
                                            @endif
                                        </div>

                                        {{-- Overpayment Detail Line --}}
                                        @if($order->isOverpaid())
                                            <div class="text-[10px] text-gray-600 bg-amber-50/70 p-1.5 rounded-lg border border-amber-200/80 space-y-0.5">
                                                <div class="flex justify-between items-center text-[9px]">
                                                    <span class="text-gray-500">Total Received:</span>
                                                    <span class="font-mono font-bold text-gray-800">₱{{ number_format($order->totalReceivedPayments(), 2) }}</span>
                                                </div>
                                                @if($order->refund_mobile_number)
                                                    <div class="flex justify-between items-center text-[9px]">
                                                        <span class="text-gray-500">Refund Wallet:</span>
                                                        <span class="font-mono font-bold text-amber-900">{{ $order->refund_mobile_number }}</span>
                                                    </div>
                                                @endif
                                                <div class="pt-0.5 flex justify-between items-center text-[9px] border-t border-amber-200/50">
                                                    <span class="text-gray-500">Sukli Status:</span>
                                                    @php $sStatus = $order->sukliRefundStatus(); @endphp
                                                    @if($sStatus === 'REFUNDED')
                                                        <span class="font-bold text-emerald-700">✓ Refunded</span>
                                                    @elseif($sStatus === 'OVERPAYMENT_PENDING_REFUND')
                                                        <span class="font-bold text-indigo-700">Pending Refund</span>
                                                    @elseif($sStatus === 'REFUND_PROCESSING')
                                                        <span class="font-bold text-blue-700">Processing</span>
                                                    @else
                                                        <span class="font-bold text-amber-700">Awaiting Verification</span>
                                                    @endif
                                                </div>
                                            </div>
                                        @endif

                                        {{-- Reference Number with Copy Button --}}
                                        @if($refNumber)
                                            <div class="flex items-center gap-1 text-[10px] font-mono text-gray-600 bg-gray-50 px-1.5 py-0.5 rounded border border-gray-200/60 w-fit">
                                                <span class="text-gray-400 font-bold">Ref:</span>
                                                <span class="font-bold text-gray-800">{{ $refNumber }}</span>
                                                <button type="button" @click="copyToClipboard('{{ $refNumber }}')"
                                                        class="text-gray-400 hover:text-gray-700 ml-0.5 transition-colors" title="Copy Reference Number">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                                </button>
                                            </div>
                                        @endif
                                    </div>
                                </td>

                                {{-- 5. Receipt Proof Thumbnail / Button --}}
                                <td class="px-4 py-3.5 text-center">
                                    @if($receiptUrl)
                                        <button type="button" 
                                                @click="openReceipt({{ json_encode($order) }}, '{{ $receiptUrl }}')"
                                                class="group/receipt relative inline-block rounded-xl overflow-hidden border-2 border-gray-200 hover:border-[#C0422A] shadow-xs transition-all cursor-pointer">
                                            <img src="{{ $receiptUrl }}" 
                                                 alt="Receipt Proof" 
                                                 class="w-12 h-14 object-cover group-hover/receipt:scale-105 transition-transform bg-gray-100"
                                                 loading="lazy">
                                            <div class="absolute inset-0 bg-black/40 opacity-0 group-hover/receipt:opacity-100 transition-opacity flex items-center justify-center">
                                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7"/></svg>
                                            </div>
                                        </button>
                                        <div class="mt-1">
                                            <button type="button" 
                                                    @click="openReceipt({{ json_encode($order) }}, '{{ $receiptUrl }}')"
                                                    class="text-[9px] font-bold text-[#C0422A] hover:underline cursor-pointer">
                                                Inspect
                                            </button>
                                        </div>
                                    @elseif($isEwallet)
                                        <div class="inline-flex flex-col items-center justify-center text-gray-300 py-1">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                            <span class="text-[9px] font-medium text-gray-400 mt-0.5">No receipt</span>
                                        </div>
                                    @else
                                        <div class="inline-flex flex-col items-center justify-center text-slate-400 py-1">
                                            <span class="text-[10px] font-bold text-slate-600 bg-slate-100 px-2 py-0.5 rounded border border-slate-200">In-Person Cash</span>
                                            <span class="text-[9px] font-medium text-slate-400 mt-0.5">Direct to seller</span>
                                        </div>
                                    @endif
                                </td>

                                {{-- 6. Verification Status Badge --}}
                                <td class="px-4 py-3.5">
                                    <div class="space-y-1">
                                        @if($isPaid)
                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                <svg class="w-3 h-3 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                                Verified & Paid
                                            </span>
                                        @elseif($isRejectedStatus)
                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200">
                                                <svg class="w-3 h-3 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                                Payment Rejected
                                            </span>
                                            @if($order->paymentRejectionReason)
                                                <div class="text-[9px] text-rose-600 italic font-medium max-w-40 leading-tight">
                                                    "{{ Str::limit($order->paymentRejectionReason, 35) }}"
                                                </div>
                                            @endif
                                        @elseif($isPendingReview)
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold bg-amber-50 text-amber-800 border border-amber-300 shadow-xs">
                                                <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
                                                Needs Admin Verification
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold bg-slate-100 text-slate-700 border border-slate-200">
                                                <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>
                                                {{ $order->formatted_payment_method ?? $order->paymentMethod ?? 'COD' }} (Seller Verifies)
                                            </span>
                                        @endif
                                    </div>
                                </td>

                                {{-- 7. Verification Actions / Commission --}}
                                <td class="px-4 py-3.5 text-right">
                                    @if($isEwallet)
                                        <div class="flex items-center justify-end gap-1.5 flex-wrap">
                                            @if(!$isPaid)
                                                {{-- Approve / Verify Button (GCash / Maya Only) --}}
                                                <button type="button" 
                                                        @click="openVerify({{ json_encode($order) }})"
                                                        class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 shadow-xs hover:shadow transition-all cursor-pointer">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                                    <span>Verify</span>
                                                </button>

                                                {{-- Reject Button (GCash / Maya Only) --}}
                                                <button type="button" 
                                                        @click="openReject({{ json_encode($order) }})"
                                                        class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-xs font-bold text-rose-700 bg-rose-50 hover:bg-rose-600 hover:text-white border border-rose-200/80 transition-all cursor-pointer">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                                    <span>Reject</span>
                                                </button>
                                            @else
                                                {{-- Already Verified Options --}}
                                                <span class="text-[10px] font-bold text-emerald-700 bg-emerald-50 px-2 py-1 rounded-md border border-emerald-200/60">
                                                    ✓ Confirmed
                                                </span>

                                                {{-- Sukli Disburse Action when Verified & Overpaid --}}
                                                @if($order->isOverpaid())
                                                    @php $sStatus = $order->sukliRefundStatus(); @endphp
                                                    @if($sStatus === 'OVERPAYMENT_PENDING_REFUND')
                                                        <button type="button"
                                                                @click="openSukliModal({{ json_encode($order) }})"
                                                                class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-xs font-bold text-indigo-700 bg-indigo-50 hover:bg-indigo-600 hover:text-white border border-indigo-200 shadow-xs transition-all cursor-pointer">
                                                            <span>💸</span>
                                                            <span>Disburse Sukli</span>
                                                        </button>
                                                    @elseif($sStatus === 'REFUNDED')
                                                        <span class="inline-flex items-center gap-1 px-2 py-1 rounded-md text-[10px] font-bold text-indigo-700 bg-indigo-50 border border-indigo-200/60" title="Sukli already disbursed">
                                                            <span>✓ Sukli Sent</span>
                                                        </span>
                                                    @endif
                                                @endif
                                            @endif
                                        </div>
                                    @else
                                        {{-- COD / Store Pickup: Display Calculated Commission Per Order --}}
                                        <div class="text-right space-y-0.5">
                                            <div class="font-mono text-sm font-black text-[#C0420A]">
                                                ₱{{ number_format($orderCommission, 2) }}
                                            </div>
                                            <div class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[9px] font-bold bg-amber-50 text-amber-800 border border-amber-200">
                                                <span class="w-1 h-1 rounded-full bg-amber-500"></span>
                                                {{ $activeRate }}% Commission Due
                                            </div>
                                            <div class="text-[9px] text-gray-400 font-medium">
                                                In-Store/COD Remittance
                                            </div>
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7">
                                    <div class="py-12 text-center">
                                        <div class="w-14 h-14 bg-amber-50 rounded-2xl flex items-center justify-center mx-auto mb-3 border border-amber-200">
                                            <svg class="w-6 h-6 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        </div>
                                        <h3 class="text-sm font-black text-gray-800 uppercase tracking-wider">No Orders Pending Verification</h3>
                                        <p class="text-xs text-gray-400 mt-1 max-w-sm mx-auto">All GCash and Maya incoming customer payments have been processed, or no orders match your search criteria.</p>
                                        @if(request('status') || request('search') || request('payment_method'))
                                            <div class="mt-4">
                                                <a href="{{ route('admin.orders') }}" class="px-4 py-2 bg-gray-900 text-white rounded-xl text-xs font-bold hover:bg-gray-800 transition-all">
                                                    Reset Filters
                                                </a>
                                            </div>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination footer --}}
            @if($allOrders->hasPages())
                <div class="px-5 py-4 border-t border-gray-100 bg-gray-50/50">
                    {{ $allOrders->links() }}
                </div>
            @endif
        </div>
    </div>

    {{-- ═════════════════════════════════════════════════════════════════ --}}
    {{-- ═══ 1. VERIFY CONFIRMATION MODAL ═══ --}}
    {{-- ═════════════════════════════════════════════════════════════════ --}}
    <div x-show="verifyModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs"
         @keydown.escape.window="verifyModal = false">
        <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-gray-100 space-y-4"
             @click.outside="verifyModal = false">
            
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0 border border-emerald-200">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                </div>
                <div>
                    <h3 class="text-base font-bold text-gray-900">Approve & Verify Payment</h3>
                    <p class="text-xs text-gray-400">Confirm that this customer's payment has been received</p>
                </div>
            </div>

            <template x-if="selectedOrder">
                <div class="bg-gray-50 rounded-xl p-3.5 border border-gray-100 space-y-2 text-xs">
                    <div class="flex justify-between">
                        <span class="text-gray-400 font-medium">Product & Variant:</span>
                        <span class="font-bold text-gray-900 text-right truncate max-w-52.5" x-text="getOrderProductSummary(selectedOrder)"></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-400 font-medium">Order Number:</span>
                        <span class="font-mono font-bold text-gray-700" x-text="'#LB-' + (selectedOrder.id ? selectedOrder.id.slice(-8).toUpperCase() : '')"></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-400 font-medium">Customer:</span>
                        <span class="font-bold text-gray-800" x-text="selectedOrder.customer ? selectedOrder.customer.name : 'Customer'"></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-400 font-medium">Payment Method:</span>
                        <span class="font-bold uppercase" :class="selectedOrder.paymentMethod.toLowerCase() === 'gcash' ? 'text-blue-600' : 'text-emerald-600'" x-text="selectedOrder.paymentMethod"></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-400 font-medium">Reference #:</span>
                        <span class="font-mono font-bold text-gray-900" x-text="selectedOrder.paymentReference || 'N/A'"></span>
                    </div>
                    <div class="flex justify-between border-t border-gray-200/60 pt-2">
                        <span class="text-gray-700 font-bold">Total Amount:</span>
                        <span class="font-mono font-black text-gray-900 text-sm" x-text="'₱' + parseFloat(selectedOrder.totalAmount || 0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})"></span>
                    </div>
                </div>
            </template>

            <div class="text-[11px] text-gray-500 leading-relaxed bg-emerald-50/60 border border-emerald-100 rounded-xl p-3">
                <p class="font-semibold text-emerald-800">Upon verification:</p>
                <ul class="list-disc list-inside mt-1 space-y-0.5 text-emerald-700">
                    <li>Order payment status will update to <strong>Paid</strong></li>
                    <li>Order workflow advances to <strong>To Ship</strong></li>
                    <li>Customer and Seller will receive real-time notifications</li>
                </ul>
            </div>

            <template x-if="selectedOrder">
                <form :action="'/admin/orders/' + selectedOrder.id + '/verify-payment'" method="POST" @submit="isSubmitting = true">
                    @csrf
                    <div class="flex items-center justify-end gap-2 pt-2">
                        <button type="button" @click="verifyModal = false" :disabled="isSubmitting"
                                class="px-4 py-2 rounded-xl text-xs font-bold text-gray-600 hover:bg-gray-100 transition-colors cursor-pointer">
                            Cancel
                        </button>
                        <button type="submit" :disabled="isSubmitting"
                                class="inline-flex items-center gap-1.5 px-5 py-2 rounded-xl text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 shadow-md shadow-emerald-600/20 transition-all cursor-pointer">
                            <svg x-show="isSubmitting" class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                            <span x-text="isSubmitting ? 'Verifying...' : 'Confirm & Verify Payment'"></span>
                        </button>
                    </div>
                </form>
            </template>
        </div>
    </div>

    {{-- ═════════════════════════════════════════════════════════════════ --}}
    {{-- ═══ 2. REJECT PAYMENT MODAL ═══ --}}
    {{-- ═════════════════════════════════════════════════════════════════ --}}
    <div x-show="rejectModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs"
         @keydown.escape.window="rejectModal = false">
        <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl border border-gray-100 space-y-4"
             @click.outside="rejectModal = false">
            
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center shrink-0 border border-rose-200">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </div>
                <div>
                    <h3 class="text-base font-bold text-gray-900">Reject Customer Payment Proof</h3>
                    <p class="text-xs text-gray-400">Specify reason for rejecting this payment transaction</p>
                </div>
            </div>

            {{-- Quick Presets --}}
            <div class="space-y-1.5">
                <label class="block text-[11px] font-bold uppercase tracking-wider text-gray-500">Select Common Rejection Reason</label>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-1.5">
                    <button type="button" @click="setRejectPreset('Receipt is blurred or unreadable')"
                            :class="rejectPreset === 'Receipt is blurred or unreadable' ? 'bg-[#FFF5F2] border-[#C0422A] text-[#C0422A]' : 'bg-gray-50 border-gray-200 text-gray-700'"
                            class="text-left text-xs p-2 rounded-lg border font-medium hover:border-[#C0422A] transition-all cursor-pointer">
                        📷 Receipt is unreadable/blurred
                    </button>
                    <button type="button" @click="setRejectPreset('Reference number does not match receipt')"
                            :class="rejectPreset === 'Reference number does not match receipt' ? 'bg-[#FFF5F2] border-[#C0422A] text-[#C0422A]' : 'bg-gray-50 border-gray-200 text-gray-700'"
                            class="text-left text-xs p-2 rounded-lg border font-medium hover:border-[#C0422A] transition-all cursor-pointer">
                        🔢 Reference # mismatch
                    </button>
                    <button type="button" @click="setRejectPreset('Payment amount sent is insufficient')"
                            :class="rejectPreset === 'Payment amount sent is insufficient' ? 'bg-[#FFF5F2] border-[#C0422A] text-[#C0422A]' : 'bg-gray-50 border-gray-200 text-gray-700'"
                            class="text-left text-xs p-2 rounded-lg border font-medium hover:border-[#C0422A] transition-all cursor-pointer">
                        💰 Amount is incorrect
                    </button>
                    <button type="button" @click="setRejectPreset('Duplicate receipt / already used for another order')"
                            :class="rejectPreset === 'Duplicate receipt / already used for another order' ? 'bg-[#FFF5F2] border-[#C0422A] text-[#C0422A]' : 'bg-gray-50 border-gray-200 text-gray-700'"
                            class="text-left text-xs p-2 rounded-lg border font-medium hover:border-[#C0422A] transition-all cursor-pointer">
                        ⚠️ Duplicate receipt
                    </button>
                </div>
            </div>

            <template x-if="selectedOrder">
                <form :action="'/admin/orders/' + selectedOrder.id + '/reject-payment'" method="POST" @submit="isSubmitting = true" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-gray-700 mb-1">
                            Detailed Reason / Customer Feedback <span class="text-rose-500">*</span>
                        </label>
                        <textarea name="reason" rows="3" required x-model="rejectReason"
                                  placeholder="Explain why the payment was rejected so the customer can upload a valid receipt..."
                                  class="w-full px-3.5 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-xs text-gray-800 placeholder:text-gray-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-rose-500/20 focus:border-rose-500 transition-all"></textarea>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-1">
                        <button type="button" @click="rejectModal = false" :disabled="isSubmitting"
                                class="px-4 py-2 rounded-xl text-xs font-bold text-gray-600 hover:bg-gray-100 transition-colors cursor-pointer">
                            Cancel
                        </button>
                        <button type="submit" :disabled="isSubmitting || !rejectReason.trim()"
                                class="inline-flex items-center gap-1.5 px-5 py-2 rounded-xl text-xs font-bold text-white bg-rose-600 hover:bg-rose-700 shadow-md shadow-rose-600/20 transition-all cursor-pointer">
                            <svg x-show="isSubmitting" class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                            <span x-text="isSubmitting ? 'Rejecting...' : 'Confirm Rejection'"></span>
                        </button>
                    </div>
                </form>
            </template>
        </div>
    </div>

    {{-- ═════════════════════════════════════════════════════════════════ --}}
    {{-- ═══ 3. HIGH-RESOLUTION RECEIPT LIGHTBOX MODAL ═══ --}}
    {{-- ═════════════════════════════════════════════════════════════════ --}}
    <div x-show="receiptModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm"
         @keydown.escape.window="receiptModal = false">
        <div class="bg-white rounded-2xl max-w-2xl w-full p-5 shadow-2xl border border-gray-200 space-y-3.5"
             @click.outside="receiptModal = false">
            
            <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-xs">
                        📷
                    </div>
                    <div>
                        <h3 class="text-sm font-black text-gray-900 truncate max-w-sm" x-text="selectedOrder ? getOrderProductSummary(selectedOrder) : 'Customer Payment Receipt'"></h3>
                        <p class="text-[10px] text-gray-400 font-mono" x-text="selectedOrder ? 'Order #LB-' + selectedOrder.id.slice(-8).toUpperCase() : ''"></p>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <a :href="receiptUrl" target="_blank" download
                       class="px-2.5 py-1 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg text-xs font-bold transition-colors inline-flex items-center gap-1">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                        <span>Download</span>
                    </a>
                    <button type="button" @click="receiptModal = false"
                            class="w-7 h-7 rounded-lg bg-gray-100 hover:bg-gray-200 text-gray-500 flex items-center justify-center font-bold text-xs transition-colors">
                        ✕
                    </button>
                </div>
            </div>

            {{-- Image Preview with Zoom Area --}}
            <div class="bg-gray-950 rounded-xl overflow-hidden flex items-center justify-center min-h-80 max-h-[60vh] p-2">
                <img :src="receiptUrl" alt="Payment Proof Full" 
                     class="max-h-[56vh] w-auto object-contain rounded-lg shadow-md hover:scale-105 transition-transform duration-200 cursor-zoom-in">
            </div>

            {{-- Metadata footer --}}
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 bg-gray-50 p-3 rounded-xl border border-gray-100 text-xs">
                <div>
                    <span class="text-gray-400 block text-[10px] font-bold uppercase">Reference #</span>
                    <span class="font-mono font-bold text-gray-900" x-text="receiptRef"></span>
                </div>
                <div>
                    <span class="text-gray-400 block text-[10px] font-bold uppercase">Expected Amount</span>
                    <span class="font-mono font-black text-gray-900" x-text="'₱' + parseFloat(receiptAmount || 0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})"></span>
                </div>
                <div class="col-span-2 sm:col-span-1 flex items-center justify-end gap-2">
                    <button type="button" @click="receiptModal = false; openVerify(selectedOrder)"
                            class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-lg text-xs transition-colors">
                        ✓ Verify
                    </button>
                    <button type="button" @click="receiptModal = false; openReject(selectedOrder)"
                            class="px-3 py-1.5 bg-rose-50 hover:bg-rose-600 hover:text-white text-rose-700 font-bold rounded-lg text-xs transition-colors border border-rose-200">
                        ✕ Reject
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ═════════════════════════════════════════════════════════════════ --}}
    {{-- ═══ 4. SUKLI / OVERPAYMENT REFUND MODAL ═══ --}}
    {{-- ═════════════════════════════════════════════════════════════════ --}}
    <div x-show="sukliModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-xs"
         @keydown.escape.window="sukliModal = false">
        <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl border border-gray-100 space-y-4"
             @click.outside="sukliModal = false">
            
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center shrink-0 border border-indigo-200 text-lg">
                    💸
                </div>
                <div>
                    <h3 class="text-base font-bold text-gray-900">Disburse Sukli / Overpayment</h3>
                    <p class="text-xs text-gray-500">Record customer overpayment refund transfer</p>
                </div>
            </div>

            <template x-if="selectedOrder">
                <div class="bg-indigo-50/50 rounded-xl p-3.5 border border-indigo-100/80 space-y-2 text-xs">
                    <div class="flex justify-between">
                        <span class="text-gray-500">Order Number:</span>
                        <span class="font-mono font-bold text-gray-800" x-text="'#LB-' + (selectedOrder.id ? selectedOrder.id.slice(-8).toUpperCase() : '')"></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Customer Name:</span>
                        <span class="font-bold text-gray-900" x-text="selectedOrder.customer ? selectedOrder.customer.name : 'Customer'"></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Order Payable:</span>
                        <span class="font-mono font-bold text-gray-700" x-text="'₱' + parseFloat(selectedOrder.totalAmount || 0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})"></span>
                    </div>
                    <div class="flex justify-between border-t border-indigo-100 pt-1.5">
                        <span class="text-indigo-900 font-bold">Eligible Sukli Balance:</span>
                        <span class="font-mono font-black text-indigo-700 text-sm" x-text="'₱' + parseFloat(sukliAmount || 0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})"></span>
                    </div>
                </div>
            </template>

            <template x-if="selectedOrder">
                <form :action="(window.location.pathname.startsWith('/superadmin') ? '/superadmin/orders/' : '/admin/orders/') + selectedOrder.id + '/refund-sukli'" 
                      method="POST" 
                      enctype="multipart/form-data" 
                      @submit="isSubmitting = true" 
                      class="space-y-3.5">
                    @csrf
                    
                    {{-- Refund Amount --}}
                    <div>
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-gray-700 mb-1">
                            Refund Amount (PHP) <span class="text-rose-500">*</span>
                        </label>
                        <input type="number" step="0.01" min="0.01" name="refund_amount" required x-model="sukliAmount"
                               class="w-full px-3.5 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-xs font-mono font-bold text-gray-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all">
                    </div>

                    {{-- Outgoing Transfer Reference Number --}}
                    <div>
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-gray-700 mb-1">
                            Outgoing Reference / Transaction # <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" name="transfer_reference" required x-model="sukliTransferRef"
                               placeholder="e.g. 100999888877 or GCASH-TXN-12345"
                               class="w-full px-3.5 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-xs font-mono text-gray-900 placeholder:text-gray-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all">
                    </div>

                    {{-- Customer Destination Mobile / Account --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                        <div>
                            <label class="block text-[11px] font-bold uppercase tracking-wider text-gray-700 mb-1">
                                Destination Mobile / Wallet <span class="text-rose-500">*</span>
                            </label>
                            <input type="text" name="destination_account" required x-model="sukliDestAccount"
                                   placeholder="09XXXXXXXXX"
                                   class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-xl text-xs font-mono text-gray-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all">
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold uppercase tracking-wider text-gray-700 mb-1">
                                Account Holder Name
                            </label>
                            <input type="text" name="destination_name" x-model="sukliDestName"
                                   placeholder="Customer Full Name"
                                   class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-xl text-xs text-gray-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all">
                        </div>
                    </div>

                    {{-- Transfer Screenshot / Proof --}}
                    <div>
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-gray-700 mb-1">
                            Disbursement Proof / Receipt Screenshot (Optional)
                        </label>
                        <input type="file" name="transfer_proof" accept="image/*,.pdf"
                               class="w-full text-xs text-gray-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 transition-all">
                    </div>

                    {{-- Admin Notes --}}
                    <div>
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-gray-700 mb-1">
                            Audit Notes
                        </label>
                        <textarea name="notes" rows="2" x-model="sukliNotes"
                                  placeholder="Add optional internal disbursement note..."
                                  class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-xl text-xs text-gray-800 placeholder:text-gray-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all"></textarea>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-2">
                        <button type="button" @click="sukliModal = false" :disabled="isSubmitting"
                                class="px-4 py-2 rounded-xl text-xs font-bold text-gray-600 hover:bg-gray-100 transition-colors cursor-pointer">
                            Cancel
                        </button>
                        <button type="submit" :disabled="isSubmitting || !sukliTransferRef.trim()"
                                class="inline-flex items-center gap-1.5 px-5 py-2 rounded-xl text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-700 shadow-md shadow-indigo-600/20 transition-all cursor-pointer">
                            <svg x-show="isSubmitting" class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                            <span x-text="isSubmitting ? 'Recording...' : 'Record Sukli Disbursement'"></span>
                        </button>
                    </div>
                </form>
            </template>
        </div>
    </div>
</div>
@endsection
