@extends('layouts.admin')

@section('content')
<div class="space-y-5" x-data="{
    receiptModal: false,
    selectedOrder: null,
    receiptUrl: '',
    receiptRef: '',
    receiptAmount: 0,

    openReceipt(order, url) {
        this.selectedOrder = order;
        this.receiptUrl = url;
        this.receiptRef = order.paymentReference || (order.latest_payment_transaction ? order.latest_payment_transaction.reference_number : 'N/A');
        this.receiptAmount = order.totalAmount;
        this.receiptModal = true;
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
                    Order <span class="text-[#C0420A] font-light italic">Management</span>
                </h1>
                <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-800 border border-emerald-200/80 shadow-xs">
                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                    <span>Direct-to-Seller · {{ $commissionRate ?? 5.0 }}% Commission Due</span>
                </div>
            </div>
            <p class="text-[11px] text-gray-500 font-medium">Track customer orders, direct-to-seller settlements, and marketplace commission governance.</p>
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
                        <option value="gcash" {{ request('payment_method') === 'gcash' ? 'selected' : '' }}>Direct GCash</option>
                        <option value="maya" {{ request('payment_method') === 'maya' ? 'selected' : '' }}>Direct Maya</option>
                        <option value="cod" {{ request('payment_method') === 'cod' ? 'selected' : '' }}>COD / In-Store Cash</option>
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
        $currStatus  = request('status', 'all');
        $isAll       = $currStatus === 'all';
        $isPending   = $currStatus === 'pending';
        $isDelivered = $currStatus === 'delivered';
        $isEwallet   = $currStatus === 'direct_ewallet';
        $isCod       = $currStatus === 'cod_cash';
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

        {{-- 2. Pending / In Progress --}}
        <a href="{{ request()->fullUrlWithQuery(['status' => 'pending', 'page' => 1]) }}"
           class="group relative rounded-2xl px-3.5 py-3 flex items-center justify-between border transition-all duration-200 cursor-pointer {{ $isPending ? 'bg-amber-50/70 border-amber-500 ring-2 ring-amber-500/20 shadow-sm -translate-y-0.5' : 'bg-white border-amber-200/60 hover:border-amber-400 hover:shadow-sm hover:-translate-y-0.5' }}">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-xl flex items-center justify-center shrink-0 transition-colors {{ $isPending ? 'bg-amber-500 text-white' : 'bg-amber-100 text-amber-700 group-hover:bg-amber-200' }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div>
                    <div class="text-sm sm:text-base font-black text-gray-900 leading-none">{{ $counts['pending'] ?? 0 }}</div>
                    <div class="text-[9px] font-bold uppercase tracking-wider text-amber-700 mt-1">Pending</div>
                </div>
            </div>
            @if($isPending)
                <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-full text-[8px] font-black uppercase tracking-wider bg-amber-600 text-white shadow-xs">
                    Active
                </span>
            @endif
        </a>

        {{-- 3. Delivered / Completed --}}
        <a href="{{ request()->fullUrlWithQuery(['status' => 'delivered', 'page' => 1]) }}"
           class="group relative rounded-2xl px-3.5 py-3 flex items-center justify-between border transition-all duration-200 cursor-pointer {{ $isDelivered ? 'bg-emerald-50/70 border-emerald-600 ring-2 ring-emerald-600/20 shadow-sm -translate-y-0.5' : 'bg-white border-emerald-100 hover:border-emerald-300 hover:shadow-sm hover:-translate-y-0.5' }}">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-xl flex items-center justify-center shrink-0 transition-colors {{ $isDelivered ? 'bg-emerald-600 text-white' : 'bg-emerald-50 text-emerald-600 group-hover:bg-emerald-100' }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div>
                    <div class="text-sm sm:text-base font-black text-gray-900 leading-none">{{ $counts['delivered'] ?? 0 }}</div>
                    <div class="text-[9px] font-bold uppercase tracking-wider text-emerald-700 mt-1">Delivered</div>
                </div>
            </div>
            @if($isDelivered)
                <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-full text-[8px] font-black uppercase tracking-wider bg-emerald-600 text-white shadow-xs">
                    Active
                </span>
            @endif
        </a>

        {{-- 4. Direct GCash / Maya --}}
        <a href="{{ request()->fullUrlWithQuery(['status' => 'direct_ewallet', 'page' => 1]) }}"
           class="group relative rounded-2xl px-3.5 py-3 flex items-center justify-between border transition-all duration-200 cursor-pointer {{ $isEwallet ? 'bg-blue-50/70 border-blue-600 ring-2 ring-blue-600/20 shadow-sm -translate-y-0.5' : 'bg-white border-blue-100 hover:border-blue-300 hover:shadow-sm hover:-translate-y-0.5' }}">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-xl flex items-center justify-center shrink-0 transition-colors {{ $isEwallet ? 'bg-blue-600 text-white' : 'bg-blue-50 text-blue-600 group-hover:bg-blue-100' }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                </div>
                <div>
                    <div class="text-sm sm:text-base font-black text-gray-900 leading-none">{{ $counts['direct_ewallet'] ?? 0 }}</div>
                    <div class="text-[9px] font-bold uppercase tracking-wider text-blue-700 mt-1">Direct E-Wallet</div>
                </div>
            </div>
            @if($isEwallet)
                <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-full text-[8px] font-black uppercase tracking-wider bg-blue-600 text-white shadow-xs">
                    Active
                </span>
            @endif
        </a>

        {{-- 5. COD / Store Cash --}}
        <a href="{{ request()->fullUrlWithQuery(['status' => 'cod_cash', 'page' => 1]) }}"
           class="group relative rounded-2xl px-3.5 py-3 flex items-center justify-between border transition-all duration-200 cursor-pointer {{ $isCod ? 'bg-slate-100 border-slate-700 ring-2 ring-slate-700/20 shadow-sm -translate-y-0.5' : 'bg-white border-gray-200 hover:border-gray-400 hover:shadow-sm hover:-translate-y-0.5' }}">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-xl flex items-center justify-center shrink-0 transition-colors {{ $isCod ? 'bg-slate-700 text-white' : 'bg-slate-100 text-slate-600 group-hover:bg-slate-200' }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                </div>
                <div>
                    <div class="text-sm sm:text-base font-black text-gray-900 leading-none">{{ $counts['cod_cash'] ?? 0 }}</div>
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

    {{-- ═══ ORDERS TABLE & LIST ═══ --}}
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
                            <th class="px-4 py-3 text-[9px] font-black uppercase tracking-widest text-gray-400">Fulfillment & Payment</th>
                            <th class="px-4 py-3 text-[9px] font-black uppercase tracking-widest text-gray-400 text-right">Platform Commission</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @forelse($allOrders as $order)
                            @php
                                $pm = strtolower($order->paymentMethod ?? '');
                                $isEwalletOrder = in_array($pm, ['gcash', 'maya', 'paymaya']);
                                
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
                                                && strcasecmp($rawVariant, $productName) !== 0;
                                            $hasSize = !empty($rawSize) 
                                                && strcasecmp($rawSize, 'Free Size') !== 0 
                                                && strcasecmp($rawSize, 'N/A') !== 0 
                                                && strcasecmp($rawSize, 'None') !== 0;
                                            $isSameVariantAndSize = $hasVariant && $hasSize && strcasecmp(trim($rawVariant), trim($rawSize)) === 0;
                                        @endphp

                                        {{-- Product Title --}}
                                        <div class="text-xs font-black text-gray-900 group-hover:text-[#C0422A] transition-colors line-clamp-1" title="{{ $productName }}">
                                            {{ $productName }}
                                        </div>

                                        {{-- Variant & Size Badges --}}
                                        @if($firstItem)
                                            <div class="flex items-center gap-1.5 flex-wrap">
                                                @if($hasVariant && !$isSameVariantAndSize)
                                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold bg-amber-50 text-amber-800 border border-amber-200">
                                                        {{ $rawVariant }}
                                                    </span>
                                                @endif

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
                                            {{ strtoupper(substr($order->customer?->name ?? 'C', 0, 1)) }}
                                        </div>
                                        <div class="min-w-0">
                                            <div class="text-xs font-bold text-gray-900 truncate leading-snug">
                                                {{ $order->customer?->name ?? 'Guest / Deleted' }}
                                            </div>
                                            <div class="text-[10px] text-gray-400 truncate mt-0.5">
                                                {{ $order->customer?->email ?? 'No email' }}
                                            </div>
                                            @php
                                                $custPhone = $order->customer?->phone ?? $order->customer?->contactNumber ?? $order->customer?->mobileNumber;
                                            @endphp
                                            @if(!empty($custPhone))
                                                <div class="text-[10px] text-gray-500 font-mono mt-0.5">
                                                    {{ $custPhone }}
                                                </div>
                                            @else
                                                <div class="text-[9px] text-gray-400 italic">No phone</div>
                                            @endif
                                        </div>
                                    </div>
                                </td>

                                {{-- 3. Artisan / Seller --}}
                                <td class="px-4 py-3.5">
                                    <div class="min-w-0">
                                        <div class="text-xs font-bold text-gray-800 truncate">
                                            {{ $order->seller?->shopName ?? $order->seller?->name ?? 'LumBarong Artisan' }}
                                        </div>
                                        <div class="text-[10px] text-gray-400 truncate mt-0.5">
                                            {{ $order->seller?->email ?? 'Artisan Partner' }}
                                        </div>
                                        <div class="inline-flex items-center gap-1 text-[9px] font-semibold text-gray-500 mt-1">
                                            <span class="w-1.5 h-1.5 rounded-full bg-gray-400"></span>
                                            <span>Shop Order</span>
                                        </div>
                                    </div>
                                </td>

                                {{-- 4. Payment Details --}}
                                <td class="px-4 py-3.5">
                                    <div class="space-y-1">
                                        <div class="text-sm font-black text-gray-900 font-mono">
                                            ₱{{ number_format($order->totalAmount, 2) }}
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
                                    @elseif($isEwalletOrder)
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

                                {{-- 6. Fulfillment & Payment Status --}}
                                <td class="px-4 py-3.5">
                                    <div class="space-y-1.5">
                                        {{-- Order Fulfillment Status Badge --}}
                                        @php
                                            $ordStatus = strtolower($order->status ?? 'pending');
                                            $badgeColor = match($ordStatus) {
                                                'delivered', 'completed' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                                'shipped', 'in transit'  => 'bg-blue-50 text-blue-700 border-blue-200',
                                                'to ship', 'processing'  => 'bg-indigo-50 text-indigo-700 border-indigo-200',
                                                'cancelled', 'declined'  => 'bg-rose-50 text-rose-700 border-rose-200',
                                                default                  => 'bg-amber-50 text-amber-800 border-amber-200',
                                            };
                                        @endphp
                                        <div>
                                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold border {{ $badgeColor }}">
                                                <span class="w-1.5 h-1.5 rounded-full bg-current"></span>
                                                Order: {{ ucfirst($order->status ?? 'Pending') }}
                                            </span>
                                        </div>

                                        {{-- Direct Payment Settlement Details --}}
                                        <div>
                                            @if($isEwalletOrder)
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[9px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200/80">
                                                    ✓ Paid to Seller (Direct)
                                                </span>
                                            @elseif(str_contains(strtolower($order->paymentMethod ?? ''), 'pickup') || str_contains(strtolower($order->courierName ?? ''), 'pickup'))
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[9px] font-bold bg-slate-100 text-slate-700 border border-slate-200">
                                                    Store Pickup (Pay at Shop)
                                                </span>
                                            @elseif(str_contains(strtolower($order->paymentMethod ?? ''), 'special') || str_contains(strtolower($order->courierName ?? ''), 'special'))
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[9px] font-bold bg-slate-100 text-slate-700 border border-slate-200">
                                                    Special Delivery (Pay to Rider)
                                                </span>
                                            @else
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[9px] font-bold bg-slate-100 text-slate-700 border border-slate-200">
                                                    COD (Pay on Delivery)
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </td>

                                {{-- 7. Platform Commission --}}
                                <td class="px-4 py-3.5 text-right">
                                    <div class="text-right space-y-0.5">
                                        <div class="font-mono text-sm font-black text-[#C0420A]">
                                            ₱{{ number_format($orderCommission, 2) }}
                                        </div>
                                        <div class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[9px] font-bold bg-amber-50 text-amber-800 border border-amber-200">
                                            <span class="w-1 h-1 rounded-full bg-amber-500"></span>
                                            {{ $activeRate }}% Commission Due
                                        </div>
                                        <div class="text-[9px] text-gray-400 font-medium">
                                            Direct to Seller
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center">
                                    <div class="max-w-xs mx-auto space-y-3">
                                        <div class="w-12 h-12 rounded-2xl bg-gray-100 text-gray-400 mx-auto flex items-center justify-center">
                                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                        </div>
                                        <h3 class="text-sm font-black text-gray-800 uppercase tracking-wider">No Orders Found</h3>
                                        <p class="text-xs text-gray-400">No orders match your selected filters or search terms.</p>
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
    {{-- ═══ HIGH-RESOLUTION RECEIPT LIGHTBOX MODAL ═══ --}}
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
            <div class="grid grid-cols-2 gap-2 bg-gray-50 p-3 rounded-xl border border-gray-100 text-xs">
                <div>
                    <span class="text-gray-400 block text-[10px] font-bold uppercase">Reference #</span>
                    <span class="font-mono font-bold text-gray-900" x-text="receiptRef"></span>
                </div>
                <div>
                    <span class="text-gray-400 block text-[10px] font-bold uppercase">Total Amount (Direct to Seller)</span>
                    <span class="font-mono font-black text-gray-900" x-text="'₱' + parseFloat(receiptAmount || 0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})"></span>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
