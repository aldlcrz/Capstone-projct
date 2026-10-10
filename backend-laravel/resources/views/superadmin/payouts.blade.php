@extends('layouts.superadmin')

@section('content')
<div x-data="sellerPayoutsPage()" class="space-y-6 sm:space-y-8 pb-12">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-2 border-b border-gray-100">
        <div>
            <div class="inline-flex items-center gap-2">
                <span class="text-[9px] font-black uppercase tracking-[0.25em] text-[#C0422A]">Super Admin Financial Governance</span>
                <span class="text-gray-300 text-xs">·</span>
                <span class="text-[9px] font-bold uppercase tracking-[0.2em] text-gray-400">Online E-Wallet Disbursals</span>
            </div>
            <h1 class="font-serif text-2xl sm:text-3xl font-bold text-gray-900 leading-tight mt-0.5">
                Seller <span class="text-[#C0422A] font-light italic">Payouts &amp; Settlements</span>
            </h1>
            <p class="text-xs text-gray-500 mt-1 font-medium">Review eligible artisan earnings from verified GCash &amp; Maya orders and record transfer payouts.</p>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('superadmin.commissions') }}" class="px-4 py-2.5 bg-white border border-gray-200 hover:border-[#C0422A] text-gray-700 hover:text-[#C0422A] rounded-xl text-xs font-bold uppercase tracking-wider transition-all shadow-xs flex items-center gap-1.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/></svg>
                <span>Commission Hub</span>
            </a>
        </div>
    </div>

    <!-- Summary KPI Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 flex flex-col justify-between hover:shadow-md transition-all">
            <div class="flex items-center justify-between">
                <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold text-lg">
                    💰
                </div>
                <span class="text-[9px] font-bold text-emerald-600 uppercase tracking-wider">Ready to Pay</span>
            </div>
            <div class="mt-3">
                <div class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Available for Payout</div>
                <div class="text-2xl font-black text-emerald-600 font-mono mt-0.5">₱{{ number_format($kpis['available_amount'], 2) }}</div>
                <div class="text-[11px] text-gray-400 mt-1">{{ $kpis['available_count'] }} settlements eligible</div>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 flex flex-col justify-between hover:shadow-md transition-all">
            <div class="flex items-center justify-between">
                <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-lg">
                    ⏳
                </div>
                <span class="text-[9px] font-bold text-blue-600 uppercase tracking-wider">In Fulfillment</span>
            </div>
            <div class="mt-3">
                <div class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Pending Eligibility</div>
                <div class="text-2xl font-black text-blue-600 font-mono mt-0.5">₱{{ number_format($kpis['pending_amount'], 2) }}</div>
                <div class="text-[11px] text-gray-400 mt-1">{{ $kpis['pending_count'] }} orders unfulfilled</div>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 flex flex-col justify-between hover:shadow-md transition-all">
            <div class="flex items-center justify-between">
                <div class="w-10 h-10 rounded-xl bg-gray-50 text-gray-700 flex items-center justify-center font-bold text-lg">
                    ✓
                </div>
                <span class="text-[9px] font-bold text-gray-500 uppercase tracking-wider">Completed</span>
            </div>
            <div class="mt-3">
                <div class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Total Disbursed</div>
                <div class="text-2xl font-black text-gray-900 font-mono mt-0.5">₱{{ number_format($kpis['paid_amount'], 2) }}</div>
                <div class="text-[11px] text-gray-400 mt-1">{{ $kpis['paid_count'] }} payouts settled</div>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 flex flex-col justify-between hover:shadow-md transition-all">
            <div class="flex items-center justify-between">
                <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center font-bold text-lg">
                    🔒
                </div>
                <span class="text-[9px] font-bold text-amber-600 uppercase tracking-wider">Disputes &amp; Holds</span>
            </div>
            <div class="mt-3">
                <div class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Settlements on Hold</div>
                <div class="text-2xl font-black text-amber-600 font-mono mt-0.5">₱{{ number_format($kpis['on_hold_amount'], 2) }}</div>
                <div class="text-[11px] text-gray-400 mt-1">{{ $kpis['on_hold_count'] }} active holds</div>
            </div>
        </div>
    </div>

    <!-- Filter & Search Bar -->
    <div class="bg-white rounded-2xl border border-gray-100 shadow-xs p-4 flex flex-col sm:flex-row items-center justify-between gap-3">
        <div class="flex items-center gap-2 overflow-x-auto w-full sm:w-auto pb-1 sm:pb-0">
            <a href="{{ request()->fullUrlWithQuery(['status' => '']) }}"
               class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition-all shrink-0 {{ empty(request('status')) ? 'bg-[#3D2B1F] text-white' : 'bg-gray-50 text-gray-600 hover:bg-gray-100' }}">
                All ({{ $payouts->total() }})
            </a>
            <a href="{{ request()->fullUrlWithQuery(['status' => 'AVAILABLE_FOR_PAYOUT']) }}"
               class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition-all shrink-0 {{ request('status') === 'AVAILABLE_FOR_PAYOUT' ? 'bg-emerald-600 text-white' : 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100' }}">
                Ready to Pay ({{ $kpis['available_count'] }})
            </a>
            <a href="{{ request()->fullUrlWithQuery(['status' => 'PENDING_ELIGIBILITY']) }}"
               class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition-all shrink-0 {{ request('status') === 'PENDING_ELIGIBILITY' ? 'bg-blue-600 text-white' : 'bg-blue-50 text-blue-700 hover:bg-blue-100' }}">
                In Progress ({{ $kpis['pending_count'] }})
            </a>
            <a href="{{ request()->fullUrlWithQuery(['status' => 'PAID']) }}"
               class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition-all shrink-0 {{ request('status') === 'PAID' ? 'bg-gray-800 text-white' : 'bg-gray-50 text-gray-700 hover:bg-gray-100' }}">
                Paid Out ({{ $kpis['paid_count'] }})
            </a>
            <a href="{{ request()->fullUrlWithQuery(['status' => 'ON_HOLD']) }}"
               class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition-all shrink-0 {{ request('status') === 'ON_HOLD' ? 'bg-amber-600 text-white' : 'bg-amber-50 text-amber-700 hover:bg-amber-100' }}">
                On Hold ({{ $kpis['on_hold_count'] }})
            </a>
        </div>

        <form method="GET" action="{{ route(Route::currentRouteName()) }}" class="w-full sm:w-72 flex items-center relative">
            <input type="hidden" name="status" value="{{ request('status') }}">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search seller, order ID, ref..."
                   class="w-full pl-9 pr-4 py-2 bg-gray-50 border border-gray-200 rounded-xl text-xs font-medium focus:outline-none focus:border-[#C0422A] transition-all">
            <svg class="w-4 h-4 text-gray-400 absolute left-3 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
        </form>
    </div>

    <!-- Settlement Table -->
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs min-w-160">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-100 text-[10px] font-bold text-gray-400 uppercase tracking-wider">
                        <th class="px-5 py-3.5">Order / Date</th>
                        <th class="px-5 py-3.5">Artisan Shop</th>
                        <th class="px-5 py-3.5">E-Wallet &amp; Dest</th>
                        <th class="px-5 py-3.5">Settlement Amount</th>
                        <th class="px-5 py-3.5">Settlement Status</th>
                        <th class="px-5 py-3.5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-gray-700 font-medium">
                    @forelse($payouts as $payout)
                    @php
                        $order = $payout->order;
                        $seller = $payout->seller;
                        $shopName = $seller?->shopName ?: ($seller?->name ?: 'Artisan');
                        $destAcc = $payout->payout_destination_account ?: ($payout->payout_method === 'Maya' ? $seller?->mayaNumber : $seller?->gcashNumber);
                        $sellerQrRaw = strtoupper($payout->payout_method) === 'MAYA' ? $seller?->mayaQrCode : $seller?->gcashQrCode;
                        $sellerQrUrl = $sellerQrRaw ? (str_starts_with($sellerQrRaw, 'http') ? $sellerQrRaw : asset('storage/' . ltrim($sellerQrRaw, '/'))) : '';
                        $isReady = ($payout->status === 'AVAILABLE_FOR_PAYOUT');
                        $isPaid = ($payout->status === 'PAID');
                        $isHold = ($payout->status === 'ON_HOLD');
                    @endphp
                    <tr class="hover:bg-amber-50/20 transition-colors">
                        <td class="px-5 py-4">
                            @if($order)
                                <div class="font-bold text-gray-900 font-mono">#LB-OR-{{ strtoupper(substr($order->id, -8)) }}</div>
                                <div class="text-[10px] text-gray-400 mt-0.5">{{ $order->createdAt ? $order->createdAt->format('M d, Y h:i A') : 'N/A' }}</div>
                                <div class="text-[9px] text-gray-500 font-medium">Status: <span class="font-bold">{{ $order->status }}</span></div>
                            @else
                                <span class="font-mono text-gray-400">Manual / Consolidated</span>
                            @endif
                        </td>

                        <td class="px-5 py-4">
                            <div class="font-bold text-gray-900">{{ $shopName }}</div>
                            <div class="text-[10px] text-gray-400 truncate max-w-xs">{{ $seller?->email }}</div>
                        </td>

                        <td class="px-5 py-4">
                            <div class="flex items-center gap-1.5">
                                <span class="px-2 py-0.5 rounded-md text-[9px] font-black uppercase tracking-wider {{ strtoupper($payout->payout_method) === 'MAYA' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-blue-50 text-blue-700 border border-blue-200' }}">
                                    {{ $payout->payout_method ?: 'GCash' }}
                                </span>
                            </div>
                            <div class="font-mono font-bold text-gray-800 text-[11px] mt-1 select-all">
                                {{ $destAcc ?: 'No phone configured' }}
                            </div>
                        </td>

                        <td class="px-5 py-4 font-mono">
                            <div class="text-sm font-black text-gray-900">₱{{ number_format($payout->net_settlement_amount, 2) }}</div>
                            <div class="text-[9px] text-gray-400">Gross: ₱{{ number_format($payout->gross_sales, 2) }}</div>
                        </td>

                        <td class="px-5 py-4">
                            @if($isPaid)
                                <span class="px-2.5 py-1 bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-full font-bold uppercase text-[9px] inline-flex items-center gap-1">
                                    <span>✓ Paid Out</span>
                                </span>
                                @if($payout->transfer_reference)
                                    <div class="text-[10px] font-mono text-gray-500 mt-1">Ref: {{ $payout->transfer_reference }}</div>
                                @endif
                            @elseif($isReady)
                                <span class="px-2.5 py-1 bg-emerald-100 text-emerald-800 border border-emerald-300 rounded-full font-bold uppercase text-[9px] inline-flex items-center gap-1 animate-pulse">
                                    <span>● Ready for Payout</span>
                                </span>
                            @elseif($isHold)
                                <span class="px-2.5 py-1 bg-amber-50 text-amber-800 border border-amber-200 rounded-full font-bold uppercase text-[9px]">
                                    🔒 On Hold
                                </span>
                                @if($payout->hold_reason)
                                    <div class="text-[9px] text-amber-700 mt-1 truncate max-w-xs">{{ $payout->hold_reason }}</div>
                                @endif
                            @else
                                <span class="px-2.5 py-1 bg-blue-50 text-blue-700 border border-blue-200 rounded-full font-bold uppercase text-[9px]">
                                    ⏳ Pending Completion
                                </span>
                            @endif
                        </td>

                        <td class="px-5 py-4 text-right">
                            <div class="flex items-center justify-end gap-2">
                                @if($isReady)
                                    <button type="button"
                                            @click="openDisburseModal('{{ $payout->id }}', '{{ addslashes($shopName) }}', '{{ number_format($payout->net_settlement_amount, 2) }}', '{{ $payout->payout_method ?: 'GCash' }}', '{{ $destAcc }}', '{{ $order ? strtoupper(substr($order->id, -8)) : '' }}', '{{ $sellerQrUrl }}')"
                                            class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-[10px] font-bold uppercase tracking-wider transition-all shadow-xs cursor-pointer">
                                        💳 Disburse Payout
                                    </button>
                                    <button type="button"
                                            @click="openHoldModal('{{ $payout->id }}', '{{ addslashes($shopName) }}')"
                                            class="px-2.5 py-1.5 bg-amber-50 hover:bg-amber-100 text-amber-800 border border-amber-200 rounded-xl text-[10px] font-bold uppercase tracking-wider transition-all cursor-pointer">
                                        🔒 Hold
                                    </button>
                                @elseif($isHold)
                                    <form action="{{ route('superadmin.payouts.release', $payout->id) }}" method="POST" class="inline">
                                        @csrf @method('PATCH')
                                        <button type="submit" class="px-3 py-1.5 bg-blue-50 hover:bg-blue-600 hover:text-white text-blue-700 border border-blue-200 rounded-xl text-[10px] font-bold uppercase tracking-wider transition-all cursor-pointer">
                                            🔓 Release Hold
                                        </button>
                                    </form>
                                @elseif($isPaid)
                                    <button type="button"
                                            @click="openProofViewModal('{{ $payout->transfer_proof_url }}', '{{ $payout->transfer_reference }}', '{{ addslashes($shopName) }}', '{{ number_format($payout->net_settlement_amount, 2) }}', '{{ $payout->processed_at ? $payout->processed_at->format('M d, Y h:i A') : '' }}', '{{ $payout->processor?->name ?? 'Super Admin' }}')"
                                            class="px-3 py-1.5 bg-gray-50 hover:bg-gray-100 text-gray-700 border border-gray-200 rounded-xl text-[10px] font-bold uppercase tracking-wider transition-all cursor-pointer">
                                        🔍 View Proof
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center text-gray-400 italic">
                            No seller settlement records found matching your filters.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($payouts->hasPages())
        <div class="px-5 py-4 border-t border-gray-100">
            {{ $payouts->links() }}
        </div>
        @endif
    </div>

    <!-- 1. Disburse Manual Payout Modal -->
    <template x-teleport="body">
        <div x-show="showDisburseModal"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             @keydown.escape.window="showDisburseModal = false"
             @click.self="showDisburseModal = false"
             class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm"
             style="display: none;"
             x-cloak>

            <div class="bg-white rounded-3xl p-6 sm:p-8 max-w-lg w-full shadow-2xl border border-gray-100 space-y-5" @click.stop>
                <div class="flex items-center gap-3 text-emerald-600">
                    <div class="w-11 h-11 rounded-2xl bg-emerald-50 border border-emerald-200 flex items-center justify-center font-bold text-xl shrink-0">
                        💳
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-gray-900">Record Manual Payout Transfer</h3>
                        <p class="text-xs text-gray-400">Log verified outgoing funds sent to artisan e-wallet</p>
                    </div>
                </div>

                <!-- Payout Details Summary Card -->
                <div class="bg-gray-50 p-4 rounded-2xl border border-gray-100 space-y-2.5 text-xs">
                    <div class="flex justify-between items-center">
                        <span class="text-gray-400 font-bold uppercase tracking-wider text-[10px]">Artisan Shop:</span>
                        <span class="font-bold text-gray-900" x-text="targetShopName"></span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-400 font-bold uppercase tracking-wider text-[10px]">Settlement Amount:</span>
                        <span class="font-black text-emerald-600 text-sm font-mono">₱<span x-text="targetAmount"></span></span>
                    </div>
                    <div class="flex justify-between items-center border-t border-gray-200/80 pt-2">
                        <span class="text-gray-400 font-bold uppercase tracking-wider text-[10px]">Destination Channel:</span>
                        <span class="font-bold text-gray-900 select-all" x-text="targetMethod + ' · ' + targetDestination"></span>
                    </div>

                    <!-- Seller QR Code Display for Scanning -->
                    <template x-if="targetQrUrl">
                        <div class="pt-2 border-t border-gray-200/80 flex items-center gap-3">
                            <a :href="targetQrUrl" target="_blank" class="block shrink-0 group">
                                <img :src="targetQrUrl" class="w-16 h-16 sm:w-20 sm:h-20 object-contain rounded-xl border border-gray-200 bg-white p-1 shadow-2xs group-hover:scale-105 transition-transform" alt="Seller Payout QR">
                            </a>
                            <div class="space-y-0.5">
                                <span class="text-[10px] font-bold text-gray-700 uppercase tracking-wider block">Seller Payout QR Code</span>
                                <p class="text-[11px] text-gray-500 leading-snug">Scan with your e-wallet app or click to view full size.</p>
                            </div>
                        </div>
                    </template>
                </div>

                <form x-bind:action="disburseActionUrl" method="POST" enctype="multipart/form-data" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-1">
                            Outgoing Transfer Reference Number *
                        </label>
                        <input type="text" name="transfer_reference" required placeholder="e.g. 90283741829"
                                class="w-full px-3.5 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-gray-900 text-xs font-mono font-bold focus:outline-none focus:border-emerald-600 transition-all">
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-1">
                            Transfer Receipt Screenshot (Optional)
                        </label>
                        <input type="file" name="transfer_proof" accept="image/*"
                                class="w-full text-xs text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-gray-100 file:text-gray-700 hover:file:bg-gray-200 cursor-pointer">
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-1">
                            Administrative Notes (Optional)
                        </label>
                        <textarea name="admin_notes" rows="2" placeholder="Transfer notes or confirmation details..."
                                  class="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl text-xs text-gray-900 focus:outline-none focus:border-emerald-600 resize-none"></textarea>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-2">
                        <button type="button" @click="showDisburseModal = false" class="px-4 py-2.5 bg-gray-100 text-gray-700 hover:bg-gray-200 rounded-xl text-xs font-bold uppercase tracking-wider transition-all cursor-pointer">
                            Cancel
                        </button>
                        <button type="submit" class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl text-xs uppercase tracking-wider transition-all shadow-md cursor-pointer">
                            ✓ Confirm &amp; Mark Paid
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </template>

    <!-- 2. Hold Settlement Modal -->
    <template x-teleport="body">
        <div x-show="showHoldModal"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             @keydown.escape.window="showHoldModal = false"
             @click.self="showHoldModal = false"
             class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm"
             style="display: none;"
             x-cloak>

            <div class="bg-white rounded-3xl p-6 max-w-md w-full shadow-2xl border border-gray-100 space-y-5" @click.stop>
                <div class="flex items-center gap-3 text-amber-600">
                    <div class="w-10 h-10 rounded-2xl bg-amber-50 border border-amber-200 flex items-center justify-center font-bold text-lg shrink-0">
                        🔒
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-gray-900">Hold Settlement Payout</h3>
                        <p class="text-xs text-gray-400">Place financial settlement on administrative hold</p>
                    </div>
                </div>

                <form x-bind:action="holdActionUrl" method="POST" class="space-y-4">
                    @csrf @method('PATCH')
                    <div>
                        <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-1.5">Reason for Hold *</label>
                        <input type="text" name="hold_reason" required placeholder="e.g. Return request submitted / customer dispute pending"
                               class="w-full px-3.5 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-gray-900 text-xs focus:outline-none focus:border-amber-600 transition-all">
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-2">
                        <button type="button" @click="showHoldModal = false" class="px-4 py-2.5 bg-gray-100 text-gray-700 hover:bg-gray-200 rounded-xl text-xs font-bold uppercase tracking-wider transition-all cursor-pointer">
                            Cancel
                        </button>
                        <button type="submit" class="px-5 py-2.5 bg-amber-600 hover:bg-amber-700 text-white font-bold rounded-xl text-xs uppercase tracking-wider transition-all shadow-xs cursor-pointer">
                            Confirm Hold
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </template>

    <!-- 3. View Proof Modal -->
    <template x-teleport="body">
        <div x-show="showProofViewModal"
             x-cloak
             style="display: none;"
             class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/75 backdrop-blur-sm"
             @keydown.escape.window="showProofViewModal = false"
             @click.self="showProofViewModal = false">
            
            <div class="bg-white rounded-3xl p-6 max-w-lg w-full shadow-2xl border border-gray-100 space-y-4" @click.stop>
                <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                    <h3 class="text-sm font-bold text-gray-900">Payout Transfer Details</h3>
                    <button type="button" @click="showProofViewModal = false" class="text-gray-400 hover:text-gray-700">✕</button>
                </div>

                <div class="bg-gray-50 p-4 rounded-2xl space-y-1.5 text-xs">
                    <div class="flex justify-between"><span class="text-gray-400 font-bold uppercase text-[10px]">Artisan:</span><span class="font-bold text-gray-900" x-text="proofShopName"></span></div>
                    <div class="flex justify-between"><span class="text-gray-400 font-bold uppercase text-[10px]">Amount:</span><span class="font-bold text-emerald-600 font-mono">₱<span x-text="proofAmount"></span></span></div>
                    <div class="flex justify-between"><span class="text-gray-400 font-bold uppercase text-[10px]">Reference:</span><span class="font-mono font-bold text-gray-900 select-all" x-text="proofReference"></span></div>
                    <div class="flex justify-between"><span class="text-gray-400 font-bold uppercase text-[10px]">Disbursed By:</span><span class="text-gray-700" x-text="proofProcessor + ' on ' + proofDate"></span></div>
                </div>

                <template x-if="proofUrl">
                    <div class="rounded-xl overflow-hidden border border-gray-200 bg-black/5">
                        <img x-bind:src="proofUrl" class="max-h-72 w-full object-contain mx-auto p-2" onerror="this.parentElement.innerHTML='<div class=\'p-8 text-center text-xs text-gray-400 italic\'>Proof screenshot not available.</div>'">
                    </div>
                </template>
                <template x-if="!proofUrl">
                    <div class="p-6 bg-gray-50 rounded-2xl text-center text-xs text-gray-400 italic">
                        No transfer proof image was attached for this payout.
                    </div>
                </template>
            </div>
        </div>
    </template>
</div>

<script>
function sellerPayoutsPage() {
    return {
        showDisburseModal: false,
        showHoldModal: false,
        showProofViewModal: false,
        targetPayoutId: '',
        targetShopName: '',
        targetAmount: '0.00',
        targetMethod: 'GCash',
        targetDestination: '',
        targetOrderId: '',
        targetQrUrl: '',
        disburseActionUrl: '',
        holdActionUrl: '',
        proofUrl: null,
        proofReference: '',
        proofShopName: '',
        proofAmount: '0.00',
        proofDate: '',
        proofProcessor: 'Super Admin',

        openDisburseModal(id, shopName, amount, method, destination, orderId, qrUrl = '') {
            this.targetPayoutId = id;
            this.targetShopName = shopName;
            this.targetAmount = amount;
            this.targetMethod = method;
            this.targetDestination = destination;
            this.targetOrderId = orderId;
            this.targetQrUrl = qrUrl;
            this.disburseActionUrl = `/superadmin/payouts/${id}/process`;
            this.showDisburseModal = true;
        },

        openHoldModal(id, shopName) {
            this.targetPayoutId = id;
            this.targetShopName = shopName;
            this.holdActionUrl = `/superadmin/payouts/${id}/hold`;
            this.showHoldModal = true;
        },

        openProofViewModal(url, ref, shopName, amount, date, processor) {
            this.proofUrl = url;
            this.proofReference = ref;
            this.proofShopName = shopName;
            this.proofAmount = amount;
            this.proofDate = date;
            this.proofProcessor = processor;
            this.showProofViewModal = true;
        }
    };
}
</script>
@endsection
