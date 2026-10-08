@extends('layouts.admin')

@section('content')
<div class="space-y-5" x-data="{
    transferModal: false,
    disputeModal: false,
    evidenceModal: false,
    selectedReturn: null,
    refundAmount: '',
    transferRef: '',
    destAccount: '',
    destName: '',
    transferNotes: '',
    disputeDecision: 'approve_return',
    disputeNotes: '',
    activeEvidences: [],
    isSubmitting: false,

    openTransfer(ret) {
        this.selectedReturn = ret;
        this.refundAmount = ret.requested_amount || (ret.order ? ret.order.totalAmount : 0);
        this.transferRef = '';
        this.destAccount = (ret.customer && ret.customer.gcashNumber) ? ret.customer.gcashNumber : ((ret.customer && ret.customer.mayaNumber) ? ret.customer.mayaNumber : '');
        this.destName = ret.customer ? ret.customer.name : '';
        this.transferNotes = '';
        this.transferModal = true;
    },

    openDispute(ret) {
        this.selectedReturn = ret;
        this.disputeDecision = 'approve_return';
        this.disputeNotes = '';
        this.disputeModal = true;
    },

    openEvidences(ret) {
        this.selectedReturn = ret;
        this.activeEvidences = ret.evidences || [];
        this.evidenceModal = true;
    }
}">

    {{-- ═══ PAGE HEADER & SEARCH TOOLBAR ═══ --}}
    <div id="tour-admin-returns-header" class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
        <div class="text-left space-y-0.5 shrink-0">
            <div class="inline-flex items-center gap-2">
                <span class="text-[9px] font-black uppercase tracking-[0.25em] text-[#C0422A]">Return & Refund Center</span>
                <span class="text-gray-300 text-xs">·</span>
                <span class="text-[9px] font-bold uppercase tracking-[0.2em] text-gray-400">Admin Governance</span>
            </div>
            <div class="flex items-center gap-3 flex-wrap">
                <h1 class="font-serif text-2xl sm:text-3xl font-bold text-gray-900 leading-tight">
                    Returns & <span class="text-[#C0420A] font-light italic">Refund Claims</span>
                </h1>
                @if(($counts['disputed'] ?? 0) > 0)
                    <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-rose-50 text-rose-800 border border-rose-200/80 shadow-xs">
                        <span class="w-2 h-2 rounded-full bg-rose-500 animate-pulse"></span>
                        <span>{{ $counts['disputed'] }} Disputed Cases</span>
                    </div>
                @endif
            </div>
            <p class="text-[11px] text-gray-500 font-medium">Manage buyer return requests, arbitrate escalated seller disputes, and record centralized platform refund payouts.</p>
        </div>

        {{-- Search Toolbar --}}
        <div id="tour-admin-returns-search" class="flex-1 max-w-xl lg:max-w-2xl w-full">
            <form method="GET" action="{{ route('admin.returns.index') }}" class="flex items-center gap-2 w-full">
                @if(request('status'))
                    <input type="hidden" name="status" value="{{ request('status') }}">
                @endif
                <div class="relative w-full flex-1">
                    <input type="text" name="search" value="{{ request('search') }}"
                           placeholder="Search by Case ID, Order ID, Customer, Artisan, Reason..."
                           class="w-full pl-10 pr-4 py-2.5 bg-white border border-gray-200 rounded-xl text-xs sm:text-sm text-gray-800 placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-[#C0422A]/20 focus:border-[#C0422A] shadow-xs transition-all">
                    <svg class="w-4 h-4 text-gray-400 absolute left-3.5 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
                @if(request('search'))
                    <a href="{{ route('admin.returns.index', ['status' => request('status')]) }}" 
                       class="px-3.5 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-xl text-xs font-bold transition-all shrink-0">
                        Clear
                    </a>
                @endif
            </form>
        </div>
    </div>

    {{-- ═══ STATUS FILTER CARDS ═══ --}}
    @php
        $currStatus = request('status', 'all');
        $isAll      = empty($currStatus) || $currStatus === 'all';
        $isPending  = $currStatus === 'pending';
        $isDisputed = $currStatus === 'disputed';
        $isApproved = $currStatus === 'approved';
        $isRefunded = $currStatus === 'refunded';
        $isRejected = $currStatus === 'rejected';
    @endphp

    <div id="tour-admin-returns-stats" class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
        {{-- 1. All --}}
        <a href="{{ request()->fullUrlWithQuery(['status' => 'all', 'page' => 1]) }}"
           class="group relative rounded-2xl px-3.5 py-3 flex items-center justify-between border transition-all duration-200 cursor-pointer {{ $isAll ? 'bg-gray-900 text-white border-gray-900 ring-2 ring-gray-900/20 shadow-sm -translate-y-0.5' : 'bg-white border-gray-200 hover:border-gray-400 hover:shadow-sm hover:-translate-y-0.5' }}">
            <div class="flex items-center gap-2">
                <div class="w-7 h-7 rounded-lg flex items-center justify-center shrink-0 transition-colors {{ $isAll ? 'bg-white/20 text-white' : 'bg-gray-100 text-gray-600 group-hover:bg-gray-200' }}">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                </div>
                <div>
                    <div class="text-sm sm:text-base font-black {{ $isAll ? 'text-white' : 'text-gray-900' }} leading-none">{{ $counts['all'] ?? 0 }}</div>
                    <div class="text-[9px] font-bold uppercase tracking-wider {{ $isAll ? 'text-gray-300' : 'text-gray-400' }} mt-1">All Cases</div>
                </div>
            </div>
        </a>

        {{-- 2. Disputed (Highest Priority) --}}
        <a href="{{ request()->fullUrlWithQuery(['status' => 'disputed', 'page' => 1]) }}"
           class="group relative rounded-2xl px-3.5 py-3 flex items-center justify-between border transition-all duration-200 cursor-pointer {{ $isDisputed ? 'bg-rose-50/80 border-rose-600 ring-2 ring-rose-600/20 shadow-sm -translate-y-0.5' : 'bg-white border-rose-200 hover:border-rose-400 hover:shadow-sm hover:-translate-y-0.5' }}">
            <div class="flex items-center gap-2">
                <div class="w-7 h-7 rounded-lg flex items-center justify-center shrink-0 transition-colors {{ $isDisputed ? 'bg-rose-600 text-white' : 'bg-rose-100 text-rose-700 group-hover:bg-rose-200' }}">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                </div>
                <div>
                    <div class="text-sm sm:text-base font-black text-rose-900 leading-none">{{ $counts['disputed'] ?? 0 }}</div>
                    <div class="text-[9px] font-bold uppercase tracking-wider text-rose-700 mt-1">Disputed</div>
                </div>
            </div>
        </a>

        {{-- 3. Pending Review --}}
        <a href="{{ request()->fullUrlWithQuery(['status' => 'pending', 'page' => 1]) }}"
           class="group relative rounded-2xl px-3.5 py-3 flex items-center justify-between border transition-all duration-200 cursor-pointer {{ $isPending ? 'bg-amber-50/80 border-amber-500 ring-2 ring-amber-500/20 shadow-sm -translate-y-0.5' : 'bg-white border-amber-200 hover:border-amber-400 hover:shadow-sm hover:-translate-y-0.5' }}">
            <div class="flex items-center gap-2">
                <div class="w-7 h-7 rounded-lg flex items-center justify-center shrink-0 transition-colors {{ $isPending ? 'bg-amber-500 text-white' : 'bg-amber-100 text-amber-700 group-hover:bg-amber-200' }}">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div>
                    <div class="text-sm sm:text-base font-black text-amber-900 leading-none">{{ $counts['pending'] ?? 0 }}</div>
                    <div class="text-[9px] font-bold uppercase tracking-wider text-amber-700 mt-1">Pending</div>
                </div>
            </div>
        </a>

        {{-- 4. Approved --}}
        <a href="{{ request()->fullUrlWithQuery(['status' => 'approved', 'page' => 1]) }}"
           class="group relative rounded-2xl px-3.5 py-3 flex items-center justify-between border transition-all duration-200 cursor-pointer {{ $isApproved ? 'bg-blue-50/80 border-blue-600 ring-2 ring-blue-600/20 shadow-sm -translate-y-0.5' : 'bg-white border-blue-100 hover:border-blue-300 hover:shadow-sm hover:-translate-y-0.5' }}">
            <div class="flex items-center gap-2">
                <div class="w-7 h-7 rounded-lg flex items-center justify-center shrink-0 transition-colors {{ $isApproved ? 'bg-blue-600 text-white' : 'bg-blue-50 text-blue-600 group-hover:bg-blue-100' }}">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                </div>
                <div>
                    <div class="text-sm sm:text-base font-black text-blue-900 leading-none">{{ $counts['approved'] ?? 0 }}</div>
                    <div class="text-[9px] font-bold uppercase tracking-wider text-blue-700 mt-1">Approved</div>
                </div>
            </div>
        </a>

        {{-- 5. Refunded --}}
        <a href="{{ request()->fullUrlWithQuery(['status' => 'refunded', 'page' => 1]) }}"
           class="group relative rounded-2xl px-3.5 py-3 flex items-center justify-between border transition-all duration-200 cursor-pointer {{ $isRefunded ? 'bg-emerald-50/80 border-emerald-600 ring-2 ring-emerald-600/20 shadow-sm -translate-y-0.5' : 'bg-white border-emerald-100 hover:border-emerald-300 hover:shadow-sm hover:-translate-y-0.5' }}">
            <div class="flex items-center gap-2">
                <div class="w-7 h-7 rounded-lg flex items-center justify-center shrink-0 transition-colors {{ $isRefunded ? 'bg-emerald-600 text-white' : 'bg-emerald-50 text-emerald-600 group-hover:bg-emerald-100' }}">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div>
                    <div class="text-sm sm:text-base font-black text-emerald-900 leading-none">{{ $counts['refunded'] ?? 0 }}</div>
                    <div class="text-[9px] font-bold uppercase tracking-wider text-emerald-700 mt-1">Refunded</div>
                </div>
            </div>
        </a>

        {{-- 6. Rejected --}}
        <a href="{{ request()->fullUrlWithQuery(['status' => 'rejected', 'page' => 1]) }}"
           class="group relative rounded-2xl px-3.5 py-3 flex items-center justify-between border transition-all duration-200 cursor-pointer {{ $isRejected ? 'bg-gray-100 border-gray-600 ring-2 ring-gray-600/20 shadow-sm -translate-y-0.5' : 'bg-white border-gray-200 hover:border-gray-400 hover:shadow-sm hover:-translate-y-0.5' }}">
            <div class="flex items-center gap-2">
                <div class="w-7 h-7 rounded-lg flex items-center justify-center shrink-0 transition-colors {{ $isRejected ? 'bg-gray-700 text-white' : 'bg-gray-100 text-gray-500 group-hover:bg-gray-200' }}">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </div>
                <div>
                    <div class="text-sm sm:text-base font-black text-gray-800 leading-none">{{ $counts['rejected'] ?? 0 }}</div>
                    <div class="text-[9px] font-bold uppercase tracking-wider text-gray-500 mt-1">Rejected</div>
                </div>
            </div>
        </a>
    </div>

    {{-- ═══ RETURNS DOSSIER TABLE ═══ --}}
    <div class="space-y-3">
        <div class="flex items-center justify-between px-1">
            <div class="text-[11px] font-bold text-gray-500">
                Showing <span class="text-gray-900 font-black">{{ $returns->total() }}</span> {{ $returns->total() === 1 ? 'case' : 'cases' }}
                @if(request('status') && request('status') !== 'all')
                    <span class="text-gray-400">· Filter: <span class="capitalize font-bold text-gray-700">{{ str_replace('_', ' ', request('status')) }}</span></span>
                @endif
                @if(request('search'))
                    <span class="text-gray-400">· Query: "<span class="font-bold text-gray-700">{{ request('search') }}</span>"</span>
                @endif
            </div>
        </div>

        <div id="tour-admin-returns-table" class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
            <div class="overflow-x-auto no-scrollbar">
                <table class="w-full text-left min-w-220">
                    <thead>
                        <tr class="border-b border-gray-100 bg-gray-50/75">
                            <th class="px-4 py-3 text-[9px] font-black uppercase tracking-widest text-gray-400">Case ID & Date</th>
                            <th class="px-4 py-3 text-[9px] font-black uppercase tracking-widest text-gray-400">Order & Amount</th>
                            <th class="px-4 py-3 text-[9px] font-black uppercase tracking-widest text-gray-400">Parties Involved</th>
                            <th class="px-4 py-3 text-[9px] font-black uppercase tracking-widest text-gray-400">Reason & Evidences</th>
                            <th class="px-4 py-3 text-[9px] font-black uppercase tracking-widest text-gray-400">Status</th>
                            <th class="px-4 py-3 text-[9px] font-black uppercase tracking-widest text-gray-400 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @forelse($returns as $ret)
                            @php
                                $status = strtolower($ret->return_status ?? $ret->status ?? 'pending');
                                $isDisputedCase = in_array($status, ['disputed', 'escalated']);
                                $isRefundedCase = in_array($status, ['refunded', 'completed', 'resolved']);
                                $isApprovedCase = in_array($status, ['approved', 'item_shipped', 'item_received']);
                                $isRejectedCase = in_array($status, ['rejected', 'declined']);
                            @endphp
                            <tr class="hover:bg-amber-50/20 transition-colors group">
                                {{-- 1. Case ID & Date --}}
                                <td class="px-4 py-3.5">
                                    <div class="space-y-1">
                                        <span class="font-mono text-xs font-black text-gray-900 bg-gray-100 px-2 py-0.5 rounded-md border border-gray-200">
                                            #RR-{{ strtoupper(substr($ret->id, -8)) }}
                                        </span>
                                        <div class="text-[10px] text-gray-400 font-medium">
                                            {{ $ret->createdAt ? $ret->createdAt->format('M d, Y · h:i A') : 'N/A' }}
                                        </div>
                                    </div>
                                </td>

                                {{-- 2. Order & Amount --}}
                                <td class="px-4 py-3.5">
                                    <div class="space-y-1">
                                        <div class="flex items-center gap-1">
                                            <span class="text-[11px] font-mono font-bold text-gray-700">
                                                #LB-{{ strtoupper(substr($ret->orderId, -8)) }}
                                            </span>
                                        </div>
                                        <div class="text-sm font-black text-[#C0420A] font-mono">
                                            ₱{{ number_format($ret->requested_amount ?? ($ret->order->totalAmount ?? 0), 2) }}
                                        </div>
                                        @if($ret->refundTransactions && $ret->refundTransactions->count() > 0)
                                            <div class="text-[9px] font-bold text-emerald-700 bg-emerald-50 px-1.5 py-0.5 rounded w-fit border border-emerald-200">
                                                Ref: {{ $ret->refundTransactions->first()->reference_number }}
                                            </div>
                                        @endif
                                    </div>
                                </td>

                                {{-- 3. Parties Involved --}}
                                <td class="px-4 py-3.5">
                                    <div class="space-y-1">
                                        <div class="flex items-center gap-1.5">
                                            <span class="text-[10px] font-bold uppercase text-gray-400">Buyer:</span>
                                            <span class="text-xs font-bold text-gray-800 truncate">{{ $ret->customer->name ?? 'Buyer' }}</span>
                                        </div>
                                        <div class="flex items-center gap-1.5">
                                            <span class="text-[10px] font-bold uppercase text-gray-400">Seller:</span>
                                            <span class="text-xs font-bold text-gray-800 truncate">{{ $ret->seller->shopName ?? $ret->seller->name ?? 'Artisan' }}</span>
                                        </div>
                                    </div>
                                </td>

                                {{-- 4. Reason & Evidences --}}
                                <td class="px-4 py-3.5">
                                    <div class="space-y-1 max-w-60">
                                        <div class="text-xs font-medium text-gray-800 line-clamp-2 leading-tight">
                                            {{ $ret->reason ?: 'No reason provided' }}
                                        </div>
                                        @if($ret->evidences && $ret->evidences->count() > 0)
                                            <button type="button" 
                                                    @click="openEvidences({{ json_encode($ret) }})"
                                                    class="inline-flex items-center gap-1 text-[10px] font-bold text-blue-600 hover:text-blue-800 transition-colors cursor-pointer">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                                <span>{{ $ret->evidences->count() }} Attached Evidence(s)</span>
                                            </button>
                                        @endif
                                    </div>
                                </td>

                                {{-- 5. Status --}}
                                <td class="px-4 py-3.5">
                                    @if($isDisputedCase)
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-black bg-rose-50 text-rose-800 border border-rose-300 shadow-xs animate-pulse">
                                            <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                                            Dispute Escalated
                                        </span>
                                    @elseif($isRefundedCase)
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            <svg class="w-3 h-3 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                            Refund Disbursed
                                        </span>
                                    @elseif($isApprovedCase)
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-200">
                                            <span class="w-1.5 h-1.5 rounded-full bg-blue-600"></span>
                                            Approved / Return
                                        </span>
                                    @elseif($isRejectedCase)
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold bg-gray-100 text-gray-700 border border-gray-200">
                                            Rejected
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold bg-amber-50 text-amber-800 border border-amber-200">
                                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                            Under Review
                                        </span>
                                    @endif
                                </td>

                                {{-- 6. Actions --}}
                                <td class="px-4 py-3.5 text-right">
                                    <div class="flex items-center justify-end gap-1.5 flex-wrap">
                                        {{-- Dispute Resolution Action --}}
                                        @if($isDisputedCase)
                                            <button type="button" 
                                                    @click="openDispute({{ json_encode($ret) }})"
                                                    class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-bold text-white bg-rose-600 hover:bg-rose-700 shadow-xs hover:shadow transition-all cursor-pointer">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                                <span>Arbitrate Dispute</span>
                                            </button>
                                        @endif

                                        {{-- Refund Transfer Disbursement Action --}}
                                        @if(!$isRefundedCase && ($isApprovedCase || $isDisputedCase))
                                            <button type="button" 
                                                    @click="openTransfer({{ json_encode($ret) }})"
                                                    class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 shadow-xs hover:shadow transition-all cursor-pointer">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                                <span>Record Payout</span>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6">
                                    <div class="py-12 text-center">
                                        <div class="w-14 h-14 bg-gray-50 rounded-2xl flex items-center justify-center mx-auto mb-3 border border-gray-100">
                                            <svg class="w-6 h-6 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 15v-1a4 4 0 00-4-4H4m0 0l3-3m-3 3l3 3m5 4v1a4 4 0 004 4h8m0 0l-3-3m3 3l-3 3"/></svg>
                                        </div>
                                        <h3 class="text-sm font-black text-gray-800 uppercase tracking-wider">No Return / Refund Cases Found</h3>
                                        <p class="text-xs text-gray-400 mt-1 max-w-sm mx-auto">There are currently no return requests matching your selected status filter.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($returns->hasPages())
                <div class="px-5 py-4 border-t border-gray-100 bg-gray-50/50">
                    {{ $returns->links() }}
                </div>
            @endif
        </div>
    </div>

    {{-- ═════════════════════════════════════════════════════════════════ --}}
    {{-- ═══ 1. RECORD PLATFORM REFUND TRANSFER MODAL ═══ --}}
    {{-- ═════════════════════════════════════════════════════════════════ --}}
    <div x-show="transferModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs"
         @keydown.escape.window="transferModal = false">
        <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl border border-gray-100 space-y-4"
             @click.outside="transferModal = false">
            
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0 border border-emerald-200">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div>
                    <h3 class="text-base font-bold text-gray-900">Record Platform Refund Disbursement</h3>
                    <p class="text-xs text-gray-400">Log proof of payout sent to buyer's e-wallet or bank</p>
                </div>
            </div>

            <template x-if="selectedReturn">
                <form :action="'/admin/returns/' + selectedReturn.id + '/record-transfer'" method="POST" enctype="multipart/form-data" @submit="isSubmitting = true" class="space-y-3.5">
                    @csrf
                    <div>
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-gray-700 mb-1">
                            Refund Amount (PHP) <span class="text-rose-500">*</span>
                        </label>
                        <input type="number" step="0.01" min="0.01" name="refund_amount" required x-model="refundAmount"
                               class="w-full px-3.5 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-xs font-mono font-bold text-gray-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500">
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-gray-700 mb-1">
                            GCash / Maya / Bank Reference Number <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" name="transfer_reference" required x-model="transferRef"
                               placeholder="e.g. GCASH-REF-1092837482"
                               class="w-full px-3.5 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-xs font-mono text-gray-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500">
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                        <div>
                            <label class="block text-[11px] font-bold uppercase tracking-wider text-gray-700 mb-1">
                                Destination Mobile / Acc #
                            </label>
                            <input type="text" name="destination_account" x-model="destAccount"
                                   placeholder="09XXXXXXXXX"
                                   class="w-full px-3.5 py-2 bg-gray-50 border border-gray-200 rounded-xl text-xs text-gray-900">
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold uppercase tracking-wider text-gray-700 mb-1">
                                Account Name
                            </label>
                            <input type="text" name="destination_name" x-model="destName"
                                   placeholder="Recipient Name"
                                   class="w-full px-3.5 py-2 bg-gray-50 border border-gray-200 rounded-xl text-xs text-gray-900">
                        </div>
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-gray-700 mb-1">
                            Upload Proof of Transfer Receipt
                        </label>
                        <input type="file" name="transfer_proof" accept="image/*,.pdf"
                               class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-xl text-xs text-gray-600 file:mr-2 file:py-1 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-emerald-50 file:text-emerald-700">
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-gray-700 mb-1">
                            Admin Notes
                        </label>
                        <textarea name="notes" rows="2" x-model="transferNotes"
                                  placeholder="Optional notes regarding this refund transfer..."
                                  class="w-full px-3.5 py-2 bg-gray-50 border border-gray-200 rounded-xl text-xs text-gray-800"></textarea>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-2">
                        <button type="button" @click="transferModal = false" :disabled="isSubmitting"
                                class="px-4 py-2 rounded-xl text-xs font-bold text-gray-600 hover:bg-gray-100 transition-colors cursor-pointer">
                            Cancel
                        </button>
                        <button type="submit" :disabled="isSubmitting || !transferRef.trim()"
                                class="inline-flex items-center gap-1.5 px-5 py-2 rounded-xl text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 shadow-md shadow-emerald-600/20 transition-all cursor-pointer">
                            <span x-text="isSubmitting ? 'Recording...' : 'Record & Complete Refund'"></span>
                        </button>
                    </div>
                </form>
            </template>
        </div>
    </div>

    {{-- ═════════════════════════════════════════════════════════════════ --}}
    {{-- ═══ 2. ARBITRATE DISPUTE MODAL ═══ --}}
    {{-- ═════════════════════════════════════════════════════════════════ --}}
    <div x-show="disputeModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs"
         @keydown.escape.window="disputeModal = false">
        <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl border border-gray-100 space-y-4"
             @click.outside="disputeModal = false">
            
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center shrink-0 border border-rose-200">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                </div>
                <div>
                    <h3 class="text-base font-bold text-gray-900">Arbitrate Return Dispute</h3>
                    <p class="text-xs text-gray-400">Make final administrative ruling on contested return case</p>
                </div>
            </div>

            <template x-if="selectedReturn">
                <form :action="'/admin/returns/' + selectedReturn.id + '/resolve-dispute'" method="POST" @submit="isSubmitting = true" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-gray-700 mb-1.5">
                            Ruling Decision <span class="text-rose-500">*</span>
                        </label>
                        <div class="space-y-2">
                            <label class="flex items-start gap-3 p-3 rounded-xl border border-emerald-200 bg-emerald-50/50 cursor-pointer">
                                <input type="radio" name="decision" value="approve_return" x-model="disputeDecision" class="mt-0.5 text-emerald-600 focus:ring-emerald-500">
                                <div>
                                    <div class="text-xs font-bold text-emerald-900">Overrule Seller & Approve Return</div>
                                    <div class="text-[10px] text-emerald-700 mt-0.5">Customer's claim is valid; customer will be instructed to ship item back or receive refund.</div>
                                </div>
                            </label>
                            <label class="flex items-start gap-3 p-3 rounded-xl border border-rose-200 bg-rose-50/50 cursor-pointer">
                                <input type="radio" name="decision" value="uphold_rejection" x-model="disputeDecision" class="mt-0.5 text-rose-600 focus:ring-rose-500">
                                <div>
                                    <div class="text-xs font-bold text-rose-900">Uphold Seller's Rejection</div>
                                    <div class="text-[10px] text-rose-700 mt-0.5">Seller's contest is valid; the return request is permanently closed without refund.</div>
                                </div>
                            </label>
                        </div>
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-gray-700 mb-1">
                            Ruling Rationale / Explanation Notes
                        </label>
                        <textarea name="notes" rows="3" x-model="disputeNotes"
                                  placeholder="Provide transparent explanation for both buyer and seller..."
                                  class="w-full px-3.5 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-xs text-gray-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#C0422A]/20 focus:border-[#C0422A]"></textarea>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-2">
                        <button type="button" @click="disputeModal = false" :disabled="isSubmitting"
                                class="px-4 py-2 rounded-xl text-xs font-bold text-gray-600 hover:bg-gray-100 transition-colors cursor-pointer">
                            Cancel
                        </button>
                        <button type="submit" :disabled="isSubmitting"
                                class="inline-flex items-center gap-1.5 px-5 py-2 rounded-xl text-xs font-bold text-white bg-[#C0420A] hover:bg-[#A03508] shadow-md shadow-[#C0420A]/20 transition-all cursor-pointer">
                            <span x-text="isSubmitting ? 'Submitting Ruling...' : 'Submit Final Ruling'"></span>
                        </button>
                    </div>
                </form>
            </template>
        </div>
    </div>

    {{-- ═════════════════════════════════════════════════════════════════ --}}
    {{-- ═══ 3. EVIDENCES LIGHTBOX MODAL ═══ --}}
    {{-- ═════════════════════════════════════════════════════════════════ --}}
    <div x-show="evidenceModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm"
         @keydown.escape.window="evidenceModal = false">
        <div class="bg-white rounded-2xl max-w-2xl w-full p-5 shadow-2xl border border-gray-200 space-y-3"
             @click.outside="evidenceModal = false">
            <div class="flex items-center justify-between border-b border-gray-100 pb-2">
                <h3 class="text-sm font-black text-gray-900">Attached Evidence Images</h3>
                <button type="button" @click="evidenceModal = false" class="text-gray-400 hover:text-gray-700 text-sm font-bold">✕</button>
            </div>
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 max-h-[60vh] overflow-y-auto p-1">
                <template x-for="(ev, idx) in activeEvidences" :key="idx">
                    <div class="rounded-xl overflow-hidden border border-gray-200 bg-gray-50">
                        <img :src="ev.file_path ? (ev.file_path.startsWith('http') || ev.file_path.startsWith('/') ? ev.file_path : '/storage/' + ev.file_path) : ''"
                             alt="Evidence" class="w-full h-36 object-cover hover:scale-105 transition-transform">
                        <div class="p-2 text-[10px] text-gray-500 font-medium" x-text="ev.description || 'Evidence photo'"></div>
                    </div>
                </template>
            </div>
        </div>
    </div>
</div>
@endsection
