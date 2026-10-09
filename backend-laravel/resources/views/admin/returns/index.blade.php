@extends('layouts.admin')

@section('content')
<div class="space-y-6" x-data="{
    // Claims Modals
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

    // Sukli Modals
    sukliDetailModal: false,
    sukliRefundModal: false,
    selectedSukliOrder: null,
    sukliRefundAmount: '',
    sukliTransferRef: '',
    sukliDestAccount: '',
    sukliDestName: '',
    sukliNotes: '',

    // Cancellation Modals
    cancelDetailModal: false,
    cancelRefundModal: false,
    selectedCancelOrder: null,
    cancelRefundAmount: '',
    cancelTransferRef: '',
    cancelDestAccount: '',
    cancelDestName: '',
    cancelNotes: '',

    isSubmitting: false,

    // Claims Handlers
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
    },

    // Sukli Handlers
    openSukliDetail(ord) {
        this.selectedSukliOrder = ord;
        this.sukliDetailModal = true;
    },

    openSukliRefund(ord) {
        this.selectedSukliOrder = ord;
        this.sukliRefundAmount = ord.remaining_sukli || ord.authoritative_sukli || 0;
        this.sukliTransferRef = '';
        this.sukliDestAccount = ord.customer_wallet || '';
        this.sukliDestName = ord.customer ? ord.customer.name : '';
        this.sukliNotes = '';
        this.sukliRefundModal = true;
    },

    // Cancellation Handlers
    openCancelDetail(ord) {
        this.selectedCancelOrder = ord;
        this.cancelDetailModal = true;
    },

    openCancelRefund(ord) {
        this.selectedCancelOrder = ord;
        this.cancelRefundAmount = ord.remaining_cancellation_refund || ord.total_paid || 0;
        this.cancelTransferRef = '';
        this.cancelDestAccount = ord.customer_wallet || '';
        this.cancelDestName = ord.customer ? ord.customer.name : '';
        this.cancelNotes = '';
        this.cancelRefundModal = true;
    },

    copyToClipboard(text) {
        if (!text) return;
        navigator.clipboard.writeText(text).then(() => {
            alert('Copied to clipboard: ' + text);
        }).catch(err => console.error('Copy failed', err));
    }
}">

    {{-- ═════════════════════════════════════════════════════════════════ --}}
    {{-- ═══ PAGE HEADER & 3-WORKFLOW SEGMENTED NAVIGATION ═══ --}}
    {{-- ═════════════════════════════════════════════════════════════════ --}}
    <div class="space-y-4">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
            <div class="text-left space-y-0.5 shrink-0">
                <div class="inline-flex items-center gap-2">
                    <span class="text-[9px] font-black uppercase tracking-[0.25em] text-[#C0422A]">Return & Refund Center</span>
                    <span class="text-gray-300 text-xs">·</span>
                    <span class="text-[9px] font-bold uppercase tracking-[0.2em] text-gray-500">Admin Governance · Centralized Financial Resolution</span>
                </div>
                <div class="flex items-center gap-3 flex-wrap">
                    <h1 class="font-serif text-2xl sm:text-3xl font-bold text-gray-900 leading-tight">
                        @if($tab === 'claims')
                            Return & <span class="text-[#C0420A] font-light italic">Refund Claims</span>
                        @elseif($tab === 'sukli')
                            Sukli / <span class="text-amber-600 font-light italic">Overpayment Refunds</span>
                        @elseif($tab === 'cancellations')
                            Order <span class="text-indigo-600 font-light italic">Cancellations</span>
                        @endif
                    </h1>
                </div>
                <p class="text-[11px] text-gray-500 font-medium">
                    @if($tab === 'claims')
                        Manage buyer return requests, arbitrate escalated seller disputes, and record centralized platform refund payouts.
                    @elseif($tab === 'sukli')
                        Verify overpaid customer transactions, calculate exact sukli, and disburse official e-wallet refunds.
                    @elseif($tab === 'cancellations')
                        Manage cancellation requests, track state changes, and process full refunds for paid cancelled orders.
                    @endif
                </p>
            </div>

            {{-- Search Toolbar --}}
            <div class="flex-1 max-w-xl lg:max-w-md w-full">
                <form method="GET" action="{{ url()->current() }}" class="flex items-center gap-2 w-full">
                    <input type="hidden" name="tab" value="{{ $tab }}">
                    @if(request('status') && request('status') !== 'all')
                        <input type="hidden" name="status" value="{{ request('status') }}">
                    @endif
                    <div class="relative w-full flex-1">
                        <input type="text" name="search" value="{{ request('search') }}"
                               placeholder="Search {{ $tab === 'claims' ? 'cases, orders, parties...' : ($tab === 'sukli' ? 'orders, customers, references...' : 'cancellations, orders, customers...') }}"
                               class="w-full pl-9 pr-4 py-2 bg-white border border-gray-200 rounded-xl text-xs text-gray-800 placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-[#C0422A]/20 focus:border-[#C0422A] shadow-xs transition-all">
                        <svg class="w-3.5 h-3.5 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                    @if(request('search'))
                        <a href="{{ url()->current() . '?tab=' . $tab . (request('status') ? '&status=' . request('status') : '') }}" 
                           class="px-3 py-2 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-xl text-xs font-bold transition-all shrink-0">
                            Clear
                        </a>
                    @endif
                </form>
            </div>
        </div>

        {{-- ═══ 3-TAB WORKFLOW PILLS ═══ --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 p-1.5 bg-white/95 backdrop-blur-md rounded-2xl border border-amber-950/10 shadow-xs">
            {{-- Tab 1: Return & Refund Claims --}}
            <a href="{{ url()->current() }}?tab=claims"
               class="group relative flex items-center justify-center gap-2.5 px-4 py-3 rounded-xl font-bold text-xs transition-all duration-200 cursor-pointer {{ $tab === 'claims' ? 'bg-[#3D2B1F] text-amber-200 border border-[#2D1F16] shadow-sm' : 'text-gray-600 hover:text-gray-900 hover:bg-[#FAF8F5]' }}">
                <div class="w-6 h-6 rounded-lg flex items-center justify-center transition-colors {{ $tab === 'claims' ? 'bg-amber-400/20 text-amber-300' : 'bg-gray-100 text-gray-500 group-hover:bg-gray-200' }}">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                    </svg>
                </div>
                <span>Return & Refund Claims</span>
                @if($tab === 'claims')
                    <span class="w-1.5 h-1.5 rounded-full bg-amber-400 animate-pulse"></span>
                @endif
            </a>

            {{-- Tab 2: Sukli / Overpayments --}}
            <a href="{{ url()->current() }}?tab=sukli"
               class="group relative flex items-center justify-center gap-2.5 px-4 py-3 rounded-xl font-bold text-xs transition-all duration-200 cursor-pointer {{ $tab === 'sukli' ? 'bg-[#3D2B1F] text-amber-200 border border-[#2D1F16] shadow-sm' : 'text-gray-600 hover:text-gray-900 hover:bg-[#FAF8F5]' }}">
                <div class="w-6 h-6 rounded-lg flex items-center justify-center transition-colors {{ $tab === 'sukli' ? 'bg-amber-400/20 text-amber-300' : 'bg-gray-100 text-gray-500 group-hover:bg-gray-200' }}">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                </div>
                <span>Sukli / Overpayments</span>
                @if($tab === 'sukli')
                    <span class="w-1.5 h-1.5 rounded-full bg-amber-400 animate-pulse"></span>
                @endif
            </a>

            {{-- Tab 3: Order Cancellations --}}
            <a href="{{ url()->current() }}?tab=cancellations"
               class="group relative flex items-center justify-center gap-2.5 px-4 py-3 rounded-xl font-bold text-xs transition-all duration-200 cursor-pointer {{ $tab === 'cancellations' ? 'bg-[#3D2B1F] text-amber-200 border border-[#2D1F16] shadow-sm' : 'text-gray-600 hover:text-gray-900 hover:bg-[#FAF8F5]' }}">
                <div class="w-6 h-6 rounded-lg flex items-center justify-center transition-colors {{ $tab === 'cancellations' ? 'bg-amber-400/20 text-amber-300' : 'bg-gray-100 text-gray-500 group-hover:bg-gray-200' }}">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                </div>
                <span>Order Cancellations</span>
                @if($tab === 'cancellations')
                    <span class="w-1.5 h-1.5 rounded-full bg-amber-400 animate-pulse"></span>
                @endif
            </a>
        </div>
    </div>

    {{-- ═════════════════════════════════════════════════════════════════ --}}
    {{-- ═══ STATUS STAT CARDS (SPECIFIC TO ACTIVE TAB) ═══ --}}
    {{-- ═════════════════════════════════════════════════════════════════ --}}
    @php
        $currStatus = request('status', 'all');
        $isAll      = empty($currStatus) || $currStatus === 'all';
    @endphp

    @if($tab === 'claims')
        {{-- ── Tab 1 Stat Cards ── --}}
        @php
            $isDisputed = $currStatus === 'disputed';
            $isPending  = $currStatus === 'pending';
            $isApproved = $currStatus === 'approved';
            $isRefunded = $currStatus === 'refunded';
            $isRejected = $currStatus === 'rejected';
        @endphp
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
            <a href="{{ request()->fullUrlWithQuery(['status' => 'all', 'claims_page' => 1]) }}"
               class="group relative rounded-2xl px-3.5 py-3 flex items-center justify-between border transition-all duration-200 cursor-pointer {{ $isAll ? 'bg-white border-gray-900 ring-2 ring-gray-900/15 shadow-sm -translate-y-0.5' : 'bg-white border-gray-100 hover:border-gray-300 hover:shadow-sm hover:-translate-y-0.5' }}">
                <div class="flex items-center gap-2.5">
                    <div class="w-7 h-7 rounded-xl flex items-center justify-center shrink-0 transition-colors {{ $isAll ? 'bg-gray-900 text-white' : 'bg-gray-100 text-gray-500 group-hover:bg-gray-200' }}">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                    </div>
                    <div>
                        <div class="text-sm sm:text-base font-black text-gray-900 leading-none">{{ $counts['all'] ?? 0 }}</div>
                        <div class="text-[9px] font-bold uppercase tracking-wider text-gray-400 mt-0.5">All Cases</div>
                    </div>
                </div>
                @if($isAll)
                    <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-full text-[8px] font-black uppercase tracking-wider bg-gray-900 text-white shadow-xs">Active</span>
                @endif
            </a>

            <a href="{{ request()->fullUrlWithQuery(['status' => 'disputed', 'claims_page' => 1]) }}"
               class="group relative rounded-2xl px-3.5 py-3 flex items-center justify-between border transition-all duration-200 cursor-pointer {{ $isDisputed ? 'bg-rose-50/70 border-rose-600 ring-2 ring-rose-600/20 shadow-sm -translate-y-0.5' : 'bg-white border-rose-100/80 hover:border-rose-300 hover:shadow-sm hover:-translate-y-0.5' }}">
                <div class="flex items-center gap-2.5">
                    <div class="w-7 h-7 rounded-xl flex items-center justify-center shrink-0 transition-colors {{ $isDisputed ? 'bg-rose-600 text-white' : 'bg-rose-50 text-rose-600 group-hover:bg-rose-100' }}">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    </div>
                    <div>
                        <div class="text-sm sm:text-base font-black text-gray-900 leading-none">{{ $counts['disputed'] ?? 0 }}</div>
                        <div class="text-[9px] font-bold uppercase tracking-wider text-rose-600 mt-0.5">Disputed</div>
                    </div>
                </div>
                @if($isDisputed)
                    <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-full text-[8px] font-black uppercase tracking-wider bg-rose-600 text-white shadow-xs">Active</span>
                @endif
            </a>

            <a href="{{ request()->fullUrlWithQuery(['status' => 'pending', 'claims_page' => 1]) }}"
               class="group relative rounded-2xl px-3.5 py-3 flex items-center justify-between border transition-all duration-200 cursor-pointer {{ $isPending ? 'bg-amber-50/70 border-amber-500 ring-2 ring-amber-500/20 shadow-sm -translate-y-0.5' : 'bg-white border-amber-100/80 hover:border-amber-300 hover:shadow-sm hover:-translate-y-0.5' }}">
                <div class="flex items-center gap-2.5">
                    <div class="w-7 h-7 rounded-xl flex items-center justify-center shrink-0 transition-colors {{ $isPending ? 'bg-amber-500 text-white' : 'bg-amber-50 text-amber-600 group-hover:bg-amber-100' }}">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <div>
                        <div class="text-sm sm:text-base font-black text-gray-900 leading-none">{{ $counts['pending'] ?? 0 }}</div>
                        <div class="text-[9px] font-bold uppercase tracking-wider text-amber-600 mt-0.5">Pending</div>
                    </div>
                </div>
                @if($isPending)
                    <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-full text-[8px] font-black uppercase tracking-wider bg-amber-500 text-white shadow-xs">Active</span>
                @endif
            </a>

            <a href="{{ request()->fullUrlWithQuery(['status' => 'approved', 'claims_page' => 1]) }}"
               class="group relative rounded-2xl px-3.5 py-3 flex items-center justify-between border transition-all duration-200 cursor-pointer {{ $isApproved ? 'bg-sky-50/70 border-sky-600 ring-2 ring-sky-600/20 shadow-sm -translate-y-0.5' : 'bg-white border-sky-100/80 hover:border-sky-300 hover:shadow-sm hover:-translate-y-0.5' }}">
                <div class="flex items-center gap-2.5">
                    <div class="w-7 h-7 rounded-xl flex items-center justify-center shrink-0 transition-colors {{ $isApproved ? 'bg-sky-600 text-white' : 'bg-sky-50 text-sky-600 group-hover:bg-sky-100' }}">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    </div>
                    <div>
                        <div class="text-sm sm:text-base font-black text-gray-900 leading-none">{{ $counts['approved'] ?? 0 }}</div>
                        <div class="text-[9px] font-bold uppercase tracking-wider text-sky-600 mt-0.5">Approved</div>
                    </div>
                </div>
                @if($isApproved)
                    <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-full text-[8px] font-black uppercase tracking-wider bg-sky-600 text-white shadow-xs">Active</span>
                @endif
            </a>

            <a href="{{ request()->fullUrlWithQuery(['status' => 'refunded', 'claims_page' => 1]) }}"
               class="group relative rounded-2xl px-3.5 py-3 flex items-center justify-between border transition-all duration-200 cursor-pointer {{ $isRefunded ? 'bg-emerald-50/70 border-emerald-600 ring-2 ring-emerald-600/20 shadow-sm -translate-y-0.5' : 'bg-white border-emerald-100/80 hover:border-emerald-300 hover:shadow-sm hover:-translate-y-0.5' }}">
                <div class="flex items-center gap-2.5">
                    <div class="w-7 h-7 rounded-xl flex items-center justify-center shrink-0 transition-colors {{ $isRefunded ? 'bg-emerald-600 text-white' : 'bg-emerald-50 text-emerald-600 group-hover:bg-emerald-100' }}">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <div>
                        <div class="text-sm sm:text-base font-black text-gray-900 leading-none">{{ $counts['refunded'] ?? 0 }}</div>
                        <div class="text-[9px] font-bold uppercase tracking-wider text-emerald-600 mt-0.5">Refunded</div>
                    </div>
                </div>
                @if($isRefunded)
                    <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-full text-[8px] font-black uppercase tracking-wider bg-emerald-600 text-white shadow-xs">Active</span>
                @endif
            </a>

            <a href="{{ request()->fullUrlWithQuery(['status' => 'rejected', 'claims_page' => 1]) }}"
               class="group relative rounded-2xl px-3.5 py-3 flex items-center justify-between border transition-all duration-200 cursor-pointer {{ $isRejected ? 'bg-slate-50 border-slate-600 ring-2 ring-slate-600/20 shadow-sm -translate-y-0.5' : 'bg-white border-slate-100/80 hover:border-slate-300 hover:shadow-sm hover:-translate-y-0.5' }}">
                <div class="flex items-center gap-2.5">
                    <div class="w-7 h-7 rounded-xl flex items-center justify-center shrink-0 transition-colors {{ $isRejected ? 'bg-slate-700 text-white' : 'bg-slate-50 text-slate-500 group-hover:bg-slate-100' }}">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </div>
                    <div>
                        <div class="text-sm sm:text-base font-black text-gray-900 leading-none">{{ $counts['rejected'] ?? 0 }}</div>
                        <div class="text-[9px] font-bold uppercase tracking-wider text-slate-500 mt-0.5">Rejected</div>
                    </div>
                </div>
                @if($isRejected)
                    <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-full text-[8px] font-black uppercase tracking-wider bg-slate-700 text-white shadow-xs">Active</span>
                @endif
            </a>
        </div>
    @elseif($tab === 'sukli')
        {{-- ── Tab 2 Stat Cards ── --}}
        @php
            $isPendingRefund = $currStatus === 'pending_refund';
            $isRefunded      = $currStatus === 'refunded';
            $isPendingVerif  = $currStatus === 'pending_verification';
        @endphp
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
            <a href="{{ request()->fullUrlWithQuery(['status' => 'all', 'sukli_page' => 1]) }}"
               class="group relative rounded-2xl px-4 py-3.5 flex items-center justify-between border transition-all duration-200 cursor-pointer {{ $isAll ? 'bg-white border-gray-900 ring-2 ring-gray-900/15 shadow-sm -translate-y-0.5' : 'bg-white border-gray-100 hover:border-gray-300 hover:shadow-sm hover:-translate-y-0.5' }}">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-xl flex items-center justify-center shrink-0 transition-colors {{ $isAll ? 'bg-gray-900 text-white' : 'bg-gray-100 text-gray-500 group-hover:bg-gray-200' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                    </div>
                    <div>
                        <div class="text-base font-black text-gray-900 leading-none">{{ $counts['all'] ?? 0 }}</div>
                        <div class="text-[10px] font-bold uppercase tracking-wider text-gray-400 mt-0.5">All Sukli Cases</div>
                    </div>
                </div>
                @if($isAll)
                    <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-full text-[8px] font-black uppercase tracking-wider bg-gray-900 text-white shadow-xs">Active</span>
                @endif
            </a>

            <a href="{{ request()->fullUrlWithQuery(['status' => 'pending_refund', 'sukli_page' => 1]) }}"
               class="group relative rounded-2xl px-4 py-3.5 flex items-center justify-between border transition-all duration-200 cursor-pointer {{ $isPendingRefund ? 'bg-amber-50/70 border-amber-600 ring-2 ring-amber-600/20 shadow-sm -translate-y-0.5' : 'bg-white border-amber-100/80 hover:border-amber-300 hover:shadow-sm hover:-translate-y-0.5' }}">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-xl flex items-center justify-center shrink-0 transition-colors {{ $isPendingRefund ? 'bg-amber-600 text-white' : 'bg-amber-50 text-amber-600 group-hover:bg-amber-100' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <div>
                        <div class="text-base font-black text-gray-900 leading-none">{{ $counts['pending_refund'] ?? 0 }}</div>
                        <div class="text-[10px] font-bold uppercase tracking-wider text-amber-600 mt-0.5">Pending Refund</div>
                    </div>
                </div>
                @if($isPendingRefund)
                    <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-full text-[8px] font-black uppercase tracking-wider bg-amber-600 text-white shadow-xs">Active</span>
                @endif
            </a>

            <a href="{{ request()->fullUrlWithQuery(['status' => 'refunded', 'sukli_page' => 1]) }}"
               class="group relative rounded-2xl px-4 py-3.5 flex items-center justify-between border transition-all duration-200 cursor-pointer {{ $isRefunded ? 'bg-emerald-50/70 border-emerald-600 ring-2 ring-emerald-600/20 shadow-sm -translate-y-0.5' : 'bg-white border-emerald-100/80 hover:border-emerald-300 hover:shadow-sm hover:-translate-y-0.5' }}">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-xl flex items-center justify-center shrink-0 transition-colors {{ $isRefunded ? 'bg-emerald-600 text-white' : 'bg-emerald-50 text-emerald-600 group-hover:bg-emerald-100' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    </div>
                    <div>
                        <div class="text-base font-black text-gray-900 leading-none">{{ $counts['refunded'] ?? 0 }}</div>
                        <div class="text-[10px] font-bold uppercase tracking-wider text-emerald-600 mt-0.5">Sukli Refunded</div>
                    </div>
                </div>
                @if($isRefunded)
                    <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-full text-[8px] font-black uppercase tracking-wider bg-emerald-600 text-white shadow-xs">Active</span>
                @endif
            </a>

            <a href="{{ request()->fullUrlWithQuery(['status' => 'pending_verification', 'sukli_page' => 1]) }}"
               class="group relative rounded-2xl px-4 py-3.5 flex items-center justify-between border transition-all duration-200 cursor-pointer {{ $isPendingVerif ? 'bg-sky-50/70 border-sky-600 ring-2 ring-sky-600/20 shadow-sm -translate-y-0.5' : 'bg-white border-sky-100/80 hover:border-sky-300 hover:shadow-sm hover:-translate-y-0.5' }}">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-xl flex items-center justify-center shrink-0 transition-colors {{ $isPendingVerif ? 'bg-sky-600 text-white' : 'bg-sky-50 text-sky-600 group-hover:bg-sky-100' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <div>
                        <div class="text-base font-black text-gray-900 leading-none">{{ $counts['pending_verification'] ?? 0 }}</div>
                        <div class="text-[10px] font-bold uppercase tracking-wider text-sky-600 mt-0.5">Pending Verification</div>
                    </div>
                </div>
                @if($isPendingVerif)
                    <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-full text-[8px] font-black uppercase tracking-wider bg-sky-600 text-white shadow-xs">Active</span>
                @endif
            </a>
        </div>
    @elseif($tab === 'cancellations')
        {{-- ── Tab 3 Stat Cards ── --}}
        @php
            $isPendingRefund = $currStatus === 'pending_refund';
            $isRefunded      = $currStatus === 'refunded';
            $isUnpaid        = $currStatus === 'unpaid';
            $isPendingAppr   = $currStatus === 'pending_approval';
        @endphp
        <div class="grid grid-cols-2 sm:grid-cols-5 gap-3">
            <a href="{{ request()->fullUrlWithQuery(['status' => 'all', 'cancel_page' => 1]) }}"
               class="group relative rounded-2xl px-3.5 py-3 flex items-center justify-between border transition-all duration-200 cursor-pointer {{ $isAll ? 'bg-white border-gray-900 ring-2 ring-gray-900/15 shadow-sm -translate-y-0.5' : 'bg-white border-gray-100 hover:border-gray-300 hover:shadow-sm hover:-translate-y-0.5' }}">
                <div class="flex items-center gap-2.5">
                    <div class="w-7 h-7 rounded-xl flex items-center justify-center shrink-0 transition-colors {{ $isAll ? 'bg-gray-900 text-white' : 'bg-gray-100 text-gray-500 group-hover:bg-gray-200' }}">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                    </div>
                    <div>
                        <div class="text-sm sm:text-base font-black text-gray-900 leading-none">{{ $counts['all'] ?? 0 }}</div>
                        <div class="text-[9px] font-bold uppercase tracking-wider text-gray-400 mt-0.5">All Cancellations</div>
                    </div>
                </div>
                @if($isAll)
                    <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-full text-[8px] font-black uppercase tracking-wider bg-gray-900 text-white shadow-xs">Active</span>
                @endif
            </a>

            <a href="{{ request()->fullUrlWithQuery(['status' => 'pending_refund', 'cancel_page' => 1]) }}"
               class="group relative rounded-2xl px-3.5 py-3 flex items-center justify-between border transition-all duration-200 cursor-pointer {{ $isPendingRefund ? 'bg-amber-50/70 border-amber-600 ring-2 ring-amber-600/20 shadow-sm -translate-y-0.5' : 'bg-white border-amber-100/80 hover:border-amber-300 hover:shadow-sm hover:-translate-y-0.5' }}">
                <div class="flex items-center gap-2.5">
                    <div class="w-7 h-7 rounded-xl flex items-center justify-center shrink-0 transition-colors {{ $isPendingRefund ? 'bg-amber-600 text-white' : 'bg-amber-50 text-amber-600 group-hover:bg-amber-100' }}">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <div>
                        <div class="text-sm sm:text-base font-black text-gray-900 leading-none">{{ $counts['pending_refund'] ?? 0 }}</div>
                        <div class="text-[9px] font-bold uppercase tracking-wider text-amber-600 mt-0.5">Pending Refund</div>
                    </div>
                </div>
                @if($isPendingRefund)
                    <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-full text-[8px] font-black uppercase tracking-wider bg-amber-600 text-white shadow-xs">Active</span>
                @endif
            </a>

            <a href="{{ request()->fullUrlWithQuery(['status' => 'refunded', 'cancel_page' => 1]) }}"
               class="group relative rounded-2xl px-3.5 py-3 flex items-center justify-between border transition-all duration-200 cursor-pointer {{ $isRefunded ? 'bg-emerald-50/70 border-emerald-600 ring-2 ring-emerald-600/20 shadow-sm -translate-y-0.5' : 'bg-white border-emerald-100/80 hover:border-emerald-300 hover:shadow-sm hover:-translate-y-0.5' }}">
                <div class="flex items-center gap-2.5">
                    <div class="w-7 h-7 rounded-xl flex items-center justify-center shrink-0 transition-colors {{ $isRefunded ? 'bg-emerald-600 text-white' : 'bg-emerald-50 text-emerald-600 group-hover:bg-emerald-100' }}">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    </div>
                    <div>
                        <div class="text-sm sm:text-base font-black text-gray-900 leading-none">{{ $counts['refunded'] ?? 0 }}</div>
                        <div class="text-[9px] font-bold uppercase tracking-wider text-emerald-600 mt-0.5">Fully Refunded</div>
                    </div>
                </div>
                @if($isRefunded)
                    <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-full text-[8px] font-black uppercase tracking-wider bg-emerald-600 text-white shadow-xs">Active</span>
                @endif
            </a>

            <a href="{{ request()->fullUrlWithQuery(['status' => 'unpaid', 'cancel_page' => 1]) }}"
               class="group relative rounded-2xl px-3.5 py-3 flex items-center justify-between border transition-all duration-200 cursor-pointer {{ $isUnpaid ? 'bg-slate-50 border-slate-600 ring-2 ring-slate-600/20 shadow-sm -translate-y-0.5' : 'bg-white border-slate-100/80 hover:border-slate-300 hover:shadow-sm hover:-translate-y-0.5' }}">
                <div class="flex items-center gap-2.5">
                    <div class="w-7 h-7 rounded-xl flex items-center justify-center shrink-0 transition-colors {{ $isUnpaid ? 'bg-slate-700 text-white' : 'bg-slate-50 text-slate-500 group-hover:bg-slate-100' }}">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                    </div>
                    <div>
                        <div class="text-sm sm:text-base font-black text-gray-900 leading-none">{{ $counts['unpaid'] ?? 0 }}</div>
                        <div class="text-[9px] font-bold uppercase tracking-wider text-slate-500 mt-0.5">Unpaid / No Refund</div>
                    </div>
                </div>
                @if($isUnpaid)
                    <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-full text-[8px] font-black uppercase tracking-wider bg-slate-700 text-white shadow-xs">Active</span>
                @endif
            </a>

            <a href="{{ request()->fullUrlWithQuery(['status' => 'pending_approval', 'cancel_page' => 1]) }}"
               class="group relative rounded-2xl px-3.5 py-3 flex items-center justify-between border transition-all duration-200 cursor-pointer {{ $isPendingAppr ? 'bg-indigo-50/70 border-indigo-600 ring-2 ring-indigo-600/20 shadow-sm -translate-y-0.5' : 'bg-white border-indigo-100/80 hover:border-indigo-300 hover:shadow-sm hover:-translate-y-0.5' }}">
                <div class="flex items-center gap-2.5">
                    <div class="w-7 h-7 rounded-xl flex items-center justify-center shrink-0 transition-colors {{ $isPendingAppr ? 'bg-indigo-600 text-white' : 'bg-indigo-50 text-indigo-600 group-hover:bg-indigo-100' }}">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <div>
                        <div class="text-sm sm:text-base font-black text-gray-900 leading-none">{{ $counts['pending_approval'] ?? 0 }}</div>
                        <div class="text-[9px] font-bold uppercase tracking-wider text-indigo-600 mt-0.5">Pending Approval</div>
                    </div>
                </div>
                @if($isPendingAppr)
                    <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-full text-[8px] font-black uppercase tracking-wider bg-indigo-600 text-white shadow-xs">Active</span>
                @endif
            </a>
        </div>
    @endif

    {{-- ═════════════════════════════════════════════════════════════════ --}}
    {{-- ═══ TAB 1 VIEW: RETURN & REFUND CLAIMS ═══ --}}
    {{-- ═════════════════════════════════════════════════════════════════ --}}
    @if($tab === 'claims')
        <div class="space-y-3">
            <div class="flex items-center justify-between px-1">
                <div class="text-[11px] font-bold text-gray-500">
                    Showing <span class="text-gray-900 font-black">{{ $returns ? $returns->total() : 0 }}</span> {{ ($returns && $returns->total() === 1) ? 'claim case' : 'claim cases' }}
                    @if(request('status') && request('status') !== 'all')
                        <span class="text-gray-400">· Filter: <span class="capitalize font-bold text-gray-700">{{ str_replace('_', ' ', request('status')) }}</span></span>
                    @endif
                    @if(request('search'))
                        <span class="text-gray-400">· Query: "<span class="font-bold text-gray-700">{{ request('search') }}</span>"</span>
                    @endif
                </div>
            </div>

            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
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
                                                    Ref: {{ $ret->refundTransactions->first()->transfer_reference ?: $ret->refundTransactions->first()->reference_number }}
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
                                            @if($isDisputedCase)
                                                <button type="button" 
                                                        @click="openDispute({{ json_encode($ret) }})"
                                                        class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-bold text-white bg-rose-600 hover:bg-rose-700 shadow-xs hover:shadow transition-all cursor-pointer">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                                    <span>Arbitrate Dispute</span>
                                                </button>
                                            @endif

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

                @if($returns && $returns->hasPages())
                    <div class="px-5 py-4 border-t border-gray-100 bg-gray-50/50">
                        {{ $returns->links() }}
                    </div>
                @endif
            </div>
        </div>
    @endif

    {{-- ═════════════════════════════════════════════════════════════════ --}}
    {{-- ═══ TAB 2 VIEW: SUKLI / OVERPAYMENTS ═══ --}}
    {{-- ═════════════════════════════════════════════════════════════════ --}}
    @if($tab === 'sukli')
        <div class="space-y-3">
            <div class="flex items-center justify-between px-1">
                <div class="text-[11px] font-bold text-gray-500">
                    Showing <span class="text-gray-900 font-black">{{ $sukliOrders ? $sukliOrders->total() : 0 }}</span> {{ ($sukliOrders && $sukliOrders->total() === 1) ? 'overpayment record' : 'overpayment records' }}
                    @if(request('status') && request('status') !== 'all')
                        <span class="text-gray-400">· Filter: <span class="capitalize font-bold text-gray-700">{{ str_replace('_', ' ', request('status')) }}</span></span>
                    @endif
                    @if(request('search'))
                        <span class="text-gray-400">· Query: "<span class="font-bold text-gray-700">{{ request('search') }}</span>"</span>
                    @endif
                </div>
            </div>

            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                <div class="overflow-x-auto no-scrollbar">
                    <table class="w-full text-left min-w-240">
                        <thead>
                            <tr class="border-b border-gray-100 bg-gray-50/75">
                                <th class="px-4 py-3 text-[9px] font-black uppercase tracking-widest text-gray-400">Order & Customer</th>
                                <th class="px-4 py-3 text-[9px] font-black uppercase tracking-widest text-gray-400">Method & Reference</th>
                                <th class="px-4 py-3 text-[9px] font-black uppercase tracking-widest text-gray-400">Payable Amount</th>
                                <th class="px-4 py-3 text-[9px] font-black uppercase tracking-widest text-gray-400">Verified Received</th>
                                <th class="px-4 py-3 text-[9px] font-black uppercase tracking-widest text-gray-400">Sukli (Overpayment)</th>
                                <th class="px-4 py-3 text-[9px] font-black uppercase tracking-widest text-gray-400">Refund Status</th>
                                <th class="px-4 py-3 text-[9px] font-black uppercase tracking-widest text-gray-400 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @forelse($sukliOrders as $ord)
                                @php
                                    $orderTotal = (float) ($ord->totalAmount ?? 0);
                                    $receivedTotal = (float) $ord->totalReceivedPayments();
                                    $sukliAmount = (float) $ord->authoritativeSukliAmount();
                                    $remainingSukli = (float) $ord->remainingSukliRefundAmount();
                                    $sukliStatus = $ord->sukliRefundStatus();
                                    $isPaymentVerified = in_array(strtolower($ord->paymentStatus ?? ''), ['paid', 'verified'], true)
                                        || ($ord->latestPaymentTransaction && $ord->latestPaymentTransaction->status === 'VERIFIED');
                                    $latestSukliTx = $ord->refundTransactions->whereNull('return_request_id')->sortByDesc('created_at')->first();

                                    $cleanDest = $ord->decrypted_refund_mobile_number ?? ($ord->refund_mobile_number ?? ($ord->refundMobileNumber ?? ($ord->customer?->mobileNumber ?? ($ord->customer?->phone ?? ''))));
                                    $maskedWallet = '';
                                    if (!empty($cleanDest)) {
                                        $len = strlen($cleanDest);
                                        $maskedWallet = $len > 4 ? substr($cleanDest, 0, 4) . str_repeat('*', max(2, $len - 7)) . substr($cleanDest, -3) : $cleanDest;
                                    }

                                    $jsOrderData = [
                                        'id'                   => $ord->id,
                                        'order_number'         => '#LB-' . strtoupper(substr($ord->id, -8)),
                                        'customer_name'        => $ord->customer?->name ?? 'Customer',
                                        'customer_email'       => $ord->customer?->email ?? '',
                                        'customer_wallet'      => $cleanDest,
                                        'masked_wallet'        => $maskedWallet ?: '09*********',
                                        'payment_method'       => strtoupper($ord->paymentMethod ?? 'GCASH'),
                                        'payment_status'       => $ord->paymentStatus ?? 'Pending',
                                        'total_amount'         => number_format($orderTotal, 2),
                                        'total_received'       => number_format($receivedTotal, 2),
                                        'authoritative_sukli'  => $sukliAmount,
                                        'remaining_sukli'      => $remainingSukli,
                                        'sukli_amount_fmt'     => number_format($sukliAmount, 2),
                                        'sukli_status'         => $sukliStatus,
                                        'is_payment_verified'  => $isPaymentVerified,
                                        'original_ref'         => $ord->latestPaymentTransaction?->reference_number ?? 'N/A',
                                        'refund_tx_ref'        => $latestSukliTx?->transfer_reference ?? '',
                                        'refund_proof_path'    => $latestSukliTx?->transfer_proof_path ?? '',
                                        'processed_at'         => $latestSukliTx?->processed_at ? $latestSukliTx->processed_at->format('M d, Y h:i A') : '',
                                        'processed_by'         => $latestSukliTx?->processor?->name ?? '',
                                        'customer'             => [
                                            'name'  => $ord->customer?->name ?? 'Customer',
                                            'phone' => $cleanDest,
                                        ]
                                    ];
                                @endphp
                                <tr class="hover:bg-amber-50/20 transition-colors group">
                                    {{-- 1. Order & Customer --}}
                                    <td class="px-4 py-3.5">
                                        <div class="space-y-1">
                                            <span class="font-mono text-xs font-black text-gray-900 bg-gray-100 px-2 py-0.5 rounded-md border border-gray-200">
                                                #LB-{{ strtoupper(substr($ord->id, -8)) }}
                                            </span>
                                            <div class="text-xs font-bold text-gray-800">
                                                {{ $ord->customer->name ?? 'Customer' }}
                                            </div>
                                            <div class="text-[10px] text-gray-400">
                                                {{ $ord->createdAt ? $ord->createdAt->format('M d, Y · h:i A') : 'N/A' }}
                                            </div>
                                        </div>
                                    </td>

                                    {{-- 2. Method & Reference --}}
                                    <td class="px-4 py-3.5">
                                        <div class="space-y-1">
                                            <span class="inline-flex items-center gap-1 font-bold text-xs uppercase {{ strtoupper($ord->paymentMethod ?? '') === 'MAYA' ? 'text-emerald-700' : 'text-blue-700' }}">
                                                {{ strtoupper($ord->paymentMethod ?? 'GCASH') }}
                                            </span>
                                            <div class="font-mono text-[11px] font-bold text-gray-700">
                                                {{ $ord->latestPaymentTransaction?->reference_number ?: 'Ref: Pending' }}
                                            </div>
                                            <div class="text-[10px] text-gray-400">
                                                Wallet: {{ $maskedWallet ?: '09*********' }}
                                            </div>
                                        </div>
                                    </td>

                                    {{-- 3. Payable Amount --}}
                                    <td class="px-4 py-3.5">
                                        <div class="text-xs font-mono font-bold text-gray-700">
                                            ₱{{ number_format($orderTotal, 2) }}
                                        </div>
                                    </td>

                                    {{-- 4. Verified Received --}}
                                    <td class="px-4 py-3.5">
                                        <div class="space-y-1">
                                            <div class="text-xs font-mono font-bold text-gray-900">
                                                ₱{{ number_format($receivedTotal, 2) }}
                                            </div>
                                            <div>
                                                @if($isPaymentVerified)
                                                    <span class="text-[9px] font-bold text-emerald-700 bg-emerald-50 px-1.5 py-0.5 rounded border border-emerald-200">
                                                        ✓ Payment Verified
                                                    </span>
                                                @else
                                                    <span class="text-[9px] font-bold text-amber-700 bg-amber-50 px-1.5 py-0.5 rounded border border-amber-200">
                                                        ⏳ Unverified Receipt
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                    </td>

                                    {{-- 5. Sukli (Overpayment) --}}
                                    <td class="px-4 py-3.5">
                                        <div class="space-y-1">
                                            <div class="text-sm font-black text-amber-700 font-mono">
                                                ₱{{ number_format($sukliAmount, 2) }}
                                            </div>
                                            @if($remainingSukli < $sukliAmount && $remainingSukli > 0)
                                                <div class="text-[9px] font-bold text-gray-500 font-mono">
                                                    Rem: ₱{{ number_format($remainingSukli, 2) }}
                                                </div>
                                            @endif
                                        </div>
                                    </td>

                                    {{-- 6. Refund Status --}}
                                    <td class="px-4 py-3.5">
                                        @if($sukliStatus === 'refunded')
                                            <div class="space-y-1">
                                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                    <svg class="w-3 h-3 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                                    Sukli Refunded
                                                </span>
                                                @if($latestSukliTx)
                                                    <div class="text-[9px] font-mono text-gray-500">
                                                        Tx: {{ $latestSukliTx->transfer_reference }}
                                                    </div>
                                                @endif
                                            </div>
                                        @elseif($sukliStatus === 'pending_refund' || ($isPaymentVerified && $remainingSukli > 0))
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-black bg-amber-50 text-amber-800 border border-amber-300 shadow-xs animate-pulse">
                                                <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                                                Pending Refund
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold bg-gray-100 text-gray-600 border border-gray-200">
                                                Awaiting Verification
                                            </span>
                                        @endif
                                    </td>

                                    {{-- 7. Actions --}}
                                    <td class="px-4 py-3.5 text-right">
                                        <div class="flex items-center justify-end gap-1.5 flex-wrap">
                                            {{-- View Details --}}
                                            <button type="button" 
                                                    @click="openSukliDetail({{ json_encode($jsOrderData) }})"
                                                    class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-xs font-bold text-gray-700 bg-gray-100 hover:bg-gray-200 border border-gray-200 transition-all cursor-pointer">
                                                <svg class="w-3.5 h-3.5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                                <span>View</span>
                                            </button>

                                            {{-- Disburse Sukli Refund --}}
                                            @if($isPaymentVerified && $remainingSukli > 0)
                                                <button type="button" 
                                                        @click="openSukliRefund({{ json_encode($jsOrderData) }})"
                                                        class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-bold text-white bg-amber-600 hover:bg-amber-700 shadow-xs hover:shadow transition-all cursor-pointer">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                                    <span>Disburse Sukli</span>
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7">
                                        <div class="py-12 text-center">
                                            <div class="w-14 h-14 bg-gray-50 rounded-2xl flex items-center justify-center mx-auto mb-3 border border-gray-100">
                                                <svg class="w-6 h-6 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                                            </div>
                                            <h3 class="text-sm font-black text-gray-800 uppercase tracking-wider">No Sukli / Overpayment Cases</h3>
                                            <p class="text-xs text-gray-400 mt-1 max-w-sm mx-auto">There are currently no customer overpayment records matching your selected filter.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($sukliOrders && $sukliOrders->hasPages())
                    <div class="px-5 py-4 border-t border-gray-100 bg-gray-50/50">
                        {{ $sukliOrders->links() }}
                    </div>
                @endif
            </div>
        </div>
    @endif

    {{-- ═════════════════════════════════════════════════════════════════ --}}
    {{-- ═══ TAB 3 VIEW: ORDER CANCELLATIONS ═══ --}}
    {{-- ═════════════════════════════════════════════════════════════════ --}}
    @if($tab === 'cancellations')
        <div class="space-y-3">
            <div class="flex items-center justify-between px-1">
                <div class="text-[11px] font-bold text-gray-500">
                    Showing <span class="text-gray-900 font-black">{{ $cancellations ? $cancellations->total() : 0 }}</span> {{ ($cancellations && $cancellations->total() === 1) ? 'cancellation record' : 'cancellation records' }}
                    @if(request('status') && request('status') !== 'all')
                        <span class="text-gray-400">· Filter: <span class="capitalize font-bold text-gray-700">{{ str_replace('_', ' ', request('status')) }}</span></span>
                    @endif
                    @if(request('search'))
                        <span class="text-gray-400">· Query: "<span class="font-bold text-gray-700">{{ request('search') }}</span>"</span>
                    @endif
                </div>
            </div>

            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                <div class="overflow-x-auto no-scrollbar">
                    <table class="w-full text-left min-w-240">
                        <thead>
                            <tr class="border-b border-gray-100 bg-gray-50/75">
                                <th class="px-4 py-3 text-[9px] font-black uppercase tracking-widest text-gray-400">Order & Date</th>
                                <th class="px-4 py-3 text-[9px] font-black uppercase tracking-widest text-gray-400">Customer & Seller</th>
                                <th class="px-4 py-3 text-[9px] font-black uppercase tracking-widest text-gray-400">Cancellation Info</th>
                                <th class="px-4 py-3 text-[9px] font-black uppercase tracking-widest text-gray-400">Order & Payment</th>
                                <th class="px-4 py-3 text-[9px] font-black uppercase tracking-widest text-gray-400">Eligible Refund Due</th>
                                <th class="px-4 py-3 text-[9px] font-black uppercase tracking-widest text-gray-400">Refund Status</th>
                                <th class="px-4 py-3 text-[9px] font-black uppercase tracking-widest text-gray-400 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @forelse($cancellations as $ord)
                                @php
                                    $orderTotal = (float) ($ord->totalAmount ?? 0);
                                    $totalPaid = (float) $ord->totalPaidAmount();
                                    $remainingRefund = (float) $ord->remainingCancellationRefundAmount();
                                    $cancelRefundStatus = $ord->cancellationRefundStatus();
                                    $latestCancelTx = $ord->refundTransactions->sortByDesc('created_at')->first();

                                    $cleanDest = $ord->decrypted_refund_mobile_number ?? ($ord->refund_mobile_number ?? ($ord->refundMobileNumber ?? ($ord->customer?->mobileNumber ?? ($ord->customer?->phone ?? ''))));
                                    $maskedWallet = '';
                                    if (!empty($cleanDest)) {
                                        $len = strlen($cleanDest);
                                        $maskedWallet = $len > 4 ? substr($cleanDest, 0, 4) . str_repeat('*', max(2, $len - 7)) . substr($cleanDest, -3) : $cleanDest;
                                    }

                                    $jsCancelData = [
                                        'id'                            => $ord->id,
                                        'order_number'                  => '#LB-' . strtoupper(substr($ord->id, -8)),
                                        'customer_name'                 => $ord->customer?->name ?? 'Customer',
                                        'customer_email'                => $ord->customer?->email ?? '',
                                        'customer_wallet'               => $cleanDest,
                                        'masked_wallet'                 => $maskedWallet ?: '09*********',
                                        'seller_name'                   => $ord->seller?->shopName ?? ($ord->seller?->name ?? 'Artisan'),
                                        'cancellation_reason'           => $ord->cancellationReason ?: 'Cancelled upon request',
                                        'cancellation_initiator'        => $ord->cancelledBy ?? 'Customer',
                                        'cancellation_date'             => $ord->updatedAt ? $ord->updatedAt->format('M d, Y h:i A') : '',
                                        'order_status'                  => strtoupper($ord->status),
                                        'payment_status'                => strtoupper($ord->paymentStatus ?? 'UNPAID'),
                                        'payment_method'                => strtoupper($ord->paymentMethod ?? 'COD'),
                                        'total_amount'                  => number_format($orderTotal, 2),
                                        'total_paid'                    => $totalPaid,
                                        'remaining_cancellation_refund' => $remainingRefund,
                                        'refund_status'                 => $cancelRefundStatus,
                                        'refund_tx_ref'                 => $latestCancelTx?->transfer_reference ?? '',
                                        'refund_proof_path'             => $latestCancelTx?->transfer_proof_path ?? '',
                                        'processed_at'                  => $latestCancelTx?->processed_at ? $latestCancelTx->processed_at->format('M d, Y h:i A') : '',
                                        'customer'                      => [
                                            'name'  => $ord->customer?->name ?? 'Customer',
                                            'phone' => $cleanDest,
                                        ]
                                    ];
                                @endphp
                                <tr class="hover:bg-indigo-50/20 transition-colors group">
                                    {{-- 1. Order & Date --}}
                                    <td class="px-4 py-3.5">
                                        <div class="space-y-1">
                                            <span class="font-mono text-xs font-black text-gray-900 bg-gray-100 px-2 py-0.5 rounded-md border border-gray-200">
                                                #LB-{{ strtoupper(substr($ord->id, -8)) }}
                                            </span>
                                            <div class="text-[10px] text-gray-400">
                                                {{ $ord->createdAt ? $ord->createdAt->format('M d, Y · h:i A') : 'N/A' }}
                                            </div>
                                        </div>
                                    </td>

                                    {{-- 2. Customer & Seller --}}
                                    <td class="px-4 py-3.5">
                                        <div class="space-y-1">
                                            <div class="flex items-center gap-1.5">
                                                <span class="text-[10px] font-bold uppercase text-gray-400">Customer:</span>
                                                <span class="text-xs font-bold text-gray-800">{{ $ord->customer->name ?? 'Customer' }}</span>
                                            </div>
                                            <div class="flex items-center gap-1.5">
                                                <span class="text-[10px] font-bold uppercase text-gray-400">Seller:</span>
                                                <span class="text-xs font-bold text-gray-800">{{ $ord->seller->shopName ?? $ord->seller->name ?? 'Artisan' }}</span>
                                            </div>
                                        </div>
                                    </td>

                                    {{-- 3. Cancellation Info --}}
                                    <td class="px-4 py-3.5">
                                        <div class="space-y-1 max-w-56">
                                            <div class="text-xs font-medium text-gray-800 line-clamp-2">
                                                {{ $ord->cancellationReason ?: 'Cancelled upon request' }}
                                            </div>
                                            <div class="text-[10px] text-gray-400">
                                                By: <span class="font-bold text-gray-600 capitalize">{{ $ord->cancelledBy ?: 'Customer' }}</span>
                                            </div>
                                        </div>
                                    </td>

                                    {{-- 4. Order & Payment --}}
                                    <td class="px-4 py-3.5">
                                        <div class="space-y-1">
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200 uppercase">
                                                {{ $ord->status }}
                                            </span>
                                            <div class="text-[11px] font-bold text-gray-700 uppercase">
                                                {{ $ord->paymentMethod ?: 'COD' }} · <span class="text-[10px] font-medium text-gray-500">{{ $ord->paymentStatus ?: 'Unpaid' }}</span>
                                            </div>
                                        </div>
                                    </td>

                                    {{-- 5. Eligible Refund Due --}}
                                    <td class="px-4 py-3.5">
                                        <div class="space-y-1">
                                            @if($totalPaid > 0)
                                                <div class="text-sm font-black text-indigo-700 font-mono">
                                                    ₱{{ number_format($totalPaid, 2) }}
                                                </div>
                                                <div class="text-[10px] text-gray-500">
                                                    Order Total: ₱{{ number_format($orderTotal, 2) }}
                                                </div>
                                            @else
                                                <div class="text-xs font-bold text-gray-400 font-mono">
                                                    ₱0.00
                                                </div>
                                                <div class="text-[10px] text-gray-400">
                                                    No payment collected
                                                </div>
                                            @endif
                                        </div>
                                    </td>

                                    {{-- 6. Refund Status --}}
                                    <td class="px-4 py-3.5">
                                        @if($cancelRefundStatus === 'refunded')
                                            <div class="space-y-1">
                                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                    <svg class="w-3 h-3 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                                    Fully Refunded
                                                </span>
                                                @if($latestCancelTx)
                                                    <div class="text-[9px] font-mono text-gray-500">
                                                        Tx: {{ $latestCancelTx->transfer_reference }}
                                                    </div>
                                                @endif
                                            </div>
                                        @elseif($cancelRefundStatus === 'pending_refund')
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-black bg-amber-50 text-amber-800 border border-amber-300 shadow-xs animate-pulse">
                                                <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                                                Pending Refund
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold bg-gray-100 text-gray-600 border border-gray-200">
                                                No Refund Due
                                            </span>
                                        @endif
                                    </td>

                                    {{-- 7. Actions --}}
                                    <td class="px-4 py-3.5 text-right">
                                        <div class="flex items-center justify-end gap-1.5 flex-wrap">
                                            <button type="button" 
                                                    @click="openCancelDetail({{ json_encode($jsCancelData) }})"
                                                    class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-xs font-bold text-gray-700 bg-gray-100 hover:bg-gray-200 border border-gray-200 transition-all cursor-pointer">
                                                <svg class="w-3.5 h-3.5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                                <span>View</span>
                                            </button>

                                            @if($remainingRefund > 0)
                                                <button type="button" 
                                                        @click="openCancelRefund({{ json_encode($jsCancelData) }})"
                                                        class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-700 shadow-xs hover:shadow transition-all cursor-pointer">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                                    <span>Disburse Refund</span>
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7">
                                        <div class="py-12 text-center">
                                            <div class="w-14 h-14 bg-gray-50 rounded-2xl flex items-center justify-center mx-auto mb-3 border border-gray-100">
                                                <svg class="w-6 h-6 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                                            </div>
                                            <h3 class="text-sm font-black text-gray-800 uppercase tracking-wider">No Order Cancellations Found</h3>
                                            <p class="text-xs text-gray-400 mt-1 max-w-sm mx-auto">There are currently no cancelled orders matching your selected status filter.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($cancellations && $cancellations->hasPages())
                    <div class="px-5 py-4 border-t border-gray-100 bg-gray-50/50">
                        {{ $cancellations->links() }}
                    </div>
                @endif
            </div>
        </div>
    @endif

    {{-- ═════════════════════════════════════════════════════════════════ --}}
    {{-- ═══ MODAL 1: CLAIMS - RECORD PLATFORM REFUND TRANSFER ═══ --}}
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
                <form :action="(window.location.pathname.startsWith('/superadmin') ? '/superadmin/returns/' : '/admin/returns/') + selectedReturn.id + '/record-transfer'" method="POST" enctype="multipart/form-data" @submit="isSubmitting = true" class="space-y-3.5">
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
    {{-- ═══ MODAL 2: CLAIMS - ARBITRATE DISPUTE MODAL ═══ --}}
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
                <form :action="(window.location.pathname.startsWith('/superadmin') ? '/superadmin/returns/' : '/admin/returns/') + selectedReturn.id + '/resolve-dispute'" method="POST" @submit="isSubmitting = true" class="space-y-4">
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
    {{-- ═══ MODAL 3: CLAIMS - EVIDENCES LIGHTBOX MODAL ═══ --}}
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

    {{-- ═════════════════════════════════════════════════════════════════ --}}
    {{-- ═══ MODAL 4: SUKLI - DETAIL VIEW MODAL ═══ --}}
    {{-- ═════════════════════════════════════════════════════════════════ --}}
    <div x-show="sukliDetailModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-xs"
         @keydown.escape.window="sukliDetailModal = false">
        <div class="bg-gray-950 text-white rounded-3xl max-w-md w-full p-6 shadow-2xl border border-gray-800 space-y-5"
             @click.outside="sukliDetailModal = false">
            
            <div class="flex items-center justify-between pb-3 border-b border-gray-800">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-amber-500/20 text-amber-400 flex items-center justify-center shrink-0 border border-amber-500/30 text-lg">
                        💰
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-white">Sukli / Overpayments</h3>
                        <p class="text-xs text-gray-400" x-text="selectedSukliOrder ? selectedSukliOrder.order_number + ' · ' + selectedSukliOrder.customer_name : ''"></p>
                    </div>
                </div>
                <button type="button" @click="sukliDetailModal = false" class="text-gray-400 hover:text-white p-1.5 rounded-lg hover:bg-white/10 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <template x-if="selectedSukliOrder">
                <div class="space-y-4 text-xs">
                    {{-- Financial Breakdown (Matches Screenshot Layout) --}}
                    <div class="bg-black/60 rounded-2xl p-4 border border-gray-800/80 space-y-3">
                        <div class="flex justify-between items-center">
                            <span class="text-gray-400 font-medium">Order total</span>
                            <span class="font-mono font-bold text-white text-sm" x-text="'₱' + selectedSukliOrder.total_amount"></span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-400 font-medium">Payment received</span>
                            <span class="font-mono font-bold text-white text-sm" x-text="'₱' + selectedSukliOrder.total_received"></span>
                        </div>
                        <div class="pt-2 border-t border-gray-800 flex justify-between items-center">
                            <span class="text-xs font-black text-amber-400 uppercase tracking-wider">Sukli owed to customer</span>
                            <span class="font-mono font-black text-amber-400 text-base" x-text="'₱' + selectedSukliOrder.sukli_amount_fmt"></span>
                        </div>
                        <div class="flex justify-between items-center pt-1">
                            <span class="text-gray-400 font-medium">Refund status</span>
                            <span class="font-bold px-2.5 py-0.5 rounded-full text-[11px] border"
                                  :class="selectedSukliOrder.sukli_status === 'refunded' ? 'bg-emerald-500/20 text-emerald-400 border-emerald-500/30' : 'bg-amber-500/20 text-amber-400 border-amber-500/30'"
                                  x-text="selectedSukliOrder.sukli_status === 'refunded' ? 'Refunded' : 'Pending refund'"></span>
                        </div>
                    </div>

                    {{-- Technical & Verification Details --}}
                    <div class="bg-gray-900/60 rounded-2xl p-4 border border-gray-800/80 space-y-2.5 text-[11px]">
                        <div class="flex justify-between items-center">
                            <span class="text-gray-400">Payment Method:</span>
                            <span class="font-bold text-white" x-text="selectedSukliOrder.payment_method"></span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-400">Payment Verification:</span>
                            <span class="font-bold" :class="selectedSukliOrder.is_payment_verified ? 'text-emerald-400' : 'text-amber-400'" x-text="selectedSukliOrder.is_payment_verified ? 'Verified' : 'Unverified'"></span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-400">Incoming Payment Reference:</span>
                            <span class="font-mono font-bold text-white" x-text="selectedSukliOrder.original_ref"></span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-400">Customer Wallet / Mobile:</span>
                            <span class="font-mono font-bold text-amber-400" x-text="selectedSukliOrder.masked_wallet"></span>
                        </div>
                        <template x-if="selectedSukliOrder.refund_tx_ref">
                            <div class="pt-2 border-t border-gray-800 space-y-1.5">
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-400">Outgoing Refund Ref:</span>
                                    <span class="font-mono font-bold text-emerald-400" x-text="selectedSukliOrder.refund_tx_ref"></span>
                                </div>
                                <div class="flex justify-between items-center" x-show="selectedSukliOrder.processed_at">
                                    <span class="text-gray-400">Refund Processed At:</span>
                                    <span class="text-gray-300" x-text="selectedSukliOrder.processed_at"></span>
                                </div>
                            </div>
                        </template>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-2">
                        <button type="button" @click="sukliDetailModal = false"
                                class="px-4 py-2 rounded-xl text-xs font-bold text-gray-400 hover:text-white hover:bg-white/10 transition-colors cursor-pointer">
                            Close
                        </button>
                        <template x-if="selectedSukliOrder.is_payment_verified && selectedSukliOrder.remaining_sukli > 0">
                            <button type="button" @click="sukliDetailModal = false; openSukliRefund(selectedSukliOrder)"
                                    class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-bold text-white bg-amber-600 hover:bg-amber-700 shadow-md shadow-amber-600/30 transition-all cursor-pointer">
                                <span>💸</span>
                                <span>Disburse Sukli</span>
                            </button>
                        </template>
                    </div>
                </div>
            </template>
        </div>
    </div>

    {{-- ═════════════════════════════════════════════════════════════════ --}}
    {{-- ═══ MODAL 5: SUKLI - DISBURSE REFUND MODAL ═══ --}}
    {{-- ═════════════════════════════════════════════════════════════════ --}}
    <div x-show="sukliRefundModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/75 backdrop-blur-xs"
         @keydown.escape.window="sukliRefundModal = false">
        <div class="bg-white rounded-3xl max-w-lg w-full p-6 shadow-2xl border border-gray-100 space-y-4"
             @click.outside="sukliRefundModal = false">
            
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-700 flex items-center justify-center shrink-0 border border-amber-200 text-lg">
                    💸
                </div>
                <div>
                    <h3 class="text-base font-bold text-gray-900">Disburse Sukli / Overpayment</h3>
                    <p class="text-xs text-gray-500">Record customer overpayment refund transfer</p>
                </div>
            </div>

            <template x-if="selectedSukliOrder">
                <div class="bg-amber-50/50 rounded-2xl p-3.5 border border-amber-200/60 space-y-2 text-xs">
                    <div class="flex justify-between">
                        <span class="text-gray-500">Order Number:</span>
                        <span class="font-mono font-bold text-gray-800" x-text="selectedSukliOrder.order_number"></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Customer Name:</span>
                        <span class="font-bold text-gray-900" x-text="selectedSukliOrder.customer_name"></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Order Payable:</span>
                        <span class="font-mono font-bold text-gray-700" x-text="'₱' + selectedSukliOrder.total_amount"></span>
                    </div>
                    <div class="flex justify-between border-t border-amber-200/70 pt-1.5">
                        <span class="text-amber-950 font-bold">Eligible Sukli Balance:</span>
                        <span class="font-mono font-black text-amber-800 text-sm" x-text="'₱' + parseFloat(selectedSukliOrder.remaining_sukli || selectedSukliOrder.authoritative_sukli || 0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})"></span>
                    </div>
                </div>
            </template>

            <template x-if="selectedSukliOrder">
                <form :action="(window.location.pathname.startsWith('/superadmin') ? '/superadmin/returns/orders/' : '/admin/returns/orders/') + selectedSukliOrder.id + '/refund-sukli'" 
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
                        <input type="number" step="0.01" min="0.01" name="refund_amount" required x-model="sukliRefundAmount"
                               class="w-full px-3.5 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-xs font-mono font-bold text-gray-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 transition-all">
                    </div>

                    {{-- Outgoing Transfer Reference Number --}}
                    <div>
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-gray-700 mb-1">
                            Outgoing Reference / Transaction # <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" name="transfer_reference" required x-model="sukliTransferRef"
                               placeholder="e.g. 100999888877 or GCASH-TXN-12345"
                               class="w-full px-3.5 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-xs font-mono text-gray-900 placeholder:text-gray-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 transition-all">
                    </div>

                    {{-- Customer Destination Mobile / Account --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                        <div>
                            <label class="block text-[11px] font-bold uppercase tracking-wider text-gray-700 mb-1">
                                Destination Mobile / Wallet <span class="text-rose-500">*</span>
                            </label>
                            <input type="text" name="destination_account" required x-model="sukliDestAccount"
                                   placeholder="09XXXXXXXXX"
                                   class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-xl text-xs font-mono text-gray-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 transition-all">
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold uppercase tracking-wider text-gray-700 mb-1">
                                Account Holder Name
                            </label>
                            <input type="text" name="destination_name" x-model="sukliDestName"
                                   placeholder="Customer Full Name"
                                   class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-xl text-xs text-gray-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 transition-all">
                        </div>
                    </div>

                    {{-- Transfer Screenshot / Proof --}}
                    <div>
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-gray-700 mb-1">
                            Distribution Proof / Receipt Screenshot (Optional)
                        </label>
                        <input type="file" name="transfer_proof" accept="image/*,.pdf"
                               class="w-full text-xs text-gray-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-amber-50 file:text-amber-800 hover:file:bg-amber-100 transition-all">
                    </div>

                    {{-- Admin Notes --}}
                    <div>
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-gray-700 mb-1">
                            Audit Notes
                        </label>
                        <textarea name="notes" rows="2" x-model="sukliNotes"
                                  placeholder="Add optional internal distribution note..."
                                  class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-xl text-xs text-gray-800 placeholder:text-gray-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 transition-all"></textarea>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-2">
                        <button type="button" @click="sukliRefundModal = false" :disabled="isSubmitting"
                                class="px-4 py-2 rounded-xl text-xs font-bold text-gray-600 hover:bg-gray-100 transition-colors cursor-pointer">
                            Cancel
                        </button>
                        <button type="submit" :disabled="isSubmitting || !sukliTransferRef.trim()"
                                class="inline-flex items-center gap-1.5 px-5 py-2 rounded-xl text-xs font-bold text-white bg-amber-600 hover:bg-amber-700 shadow-md shadow-amber-600/20 transition-all cursor-pointer">
                            <svg x-show="isSubmitting" class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                            <span x-text="isSubmitting ? 'Recording...' : 'Record Sukli Distribution'"></span>
                        </button>
                    </div>
                </form>
            </template>
        </div>
    </div>

    {{-- ═════════════════════════════════════════════════════════════════ --}}
    {{-- ═══ MODAL 6: CANCELLATIONS - DETAIL VIEW MODAL ═══ --}}
    {{-- ═════════════════════════════════════════════════════════════════ --}}
    <div x-show="cancelDetailModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-xs"
         @keydown.escape.window="cancelDetailModal = false">
        <div class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl border border-gray-100 space-y-4"
             @click.outside="cancelDetailModal = false">
            
            <div class="flex items-center justify-between pb-3 border-b border-gray-100">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-700 flex items-center justify-center shrink-0 border border-indigo-200 text-lg">
                        📦
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-gray-900">Cancellation Dossier</h3>
                        <p class="text-xs text-gray-500" x-text="selectedCancelOrder ? selectedCancelOrder.order_number : ''"></p>
                    </div>
                </div>
                <button type="button" @click="cancelDetailModal = false" class="text-gray-400 hover:text-gray-600 p-1.5 rounded-lg hover:bg-gray-100 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <template x-if="selectedCancelOrder">
                <div class="space-y-3.5 text-xs">
                    {{-- Financial Breakdown --}}
                    <div class="bg-indigo-50/60 rounded-2xl p-4 border border-indigo-100 space-y-2.5">
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600 font-medium">Order Total:</span>
                            <span class="font-mono font-bold text-gray-900" x-text="'₱' + selectedCancelOrder.total_amount"></span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600 font-medium">Total Paid by Customer:</span>
                            <span class="font-mono font-bold text-gray-900" x-text="'₱' + parseFloat(selectedCancelOrder.total_paid || 0).toFixed(2)"></span>
                        </div>
                        <div class="pt-2 border-t border-indigo-200/70 flex justify-between items-center">
                            <span class="text-xs font-black text-indigo-950 uppercase tracking-wider">Eligible Refund Due:</span>
                            <span class="font-mono font-black text-indigo-800 text-base" x-text="'₱' + parseFloat(selectedCancelOrder.remaining_cancellation_refund || 0).toFixed(2)"></span>
                        </div>
                    </div>

                    {{-- Cancellation Reason & Audit Trail --}}
                    <div class="bg-gray-50 rounded-2xl p-4 border border-gray-200/70 space-y-2.5 text-[11px]">
                        <div class="flex justify-between items-center">
                            <span class="text-gray-500">Cancellation Initiator:</span>
                            <span class="font-bold text-gray-800 capitalize" x-text="selectedCancelOrder.cancellation_initiator"></span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-500">Cancellation Date:</span>
                            <span class="text-gray-700" x-text="selectedCancelOrder.cancellation_date"></span>
                        </div>
                        <div>
                            <span class="text-gray-500 block mb-1">Reason for Cancellation:</span>
                            <div class="p-2.5 bg-white rounded-xl border border-gray-200 text-gray-800 font-medium" x-text="selectedCancelOrder.cancellation_reason"></div>
                        </div>
                        <div class="flex justify-between items-center pt-2 border-t border-gray-200">
                            <span class="text-gray-500">Refund Destination:</span>
                            <span class="font-mono font-bold text-indigo-700" x-text="selectedCancelOrder.masked_wallet"></span>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-2">
                        <button type="button" @click="cancelDetailModal = false"
                                class="px-4 py-2 rounded-xl text-xs font-bold text-gray-600 hover:bg-gray-100 transition-colors cursor-pointer">
                            Close
                        </button>
                        <template x-if="selectedCancelOrder.remaining_cancellation_refund > 0">
                            <button type="button" @click="cancelDetailModal = false; openCancelRefund(selectedCancelOrder)"
                                    class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-700 shadow-md shadow-indigo-600/20 transition-all cursor-pointer">
                                <span>💸</span>
                                <span>Disburse Refund</span>
                            </button>
                        </template>
                    </div>
                </div>
            </template>
        </div>
    </div>

    {{-- ═════════════════════════════════════════════════════════════════ --}}
    {{-- ═══ MODAL 7: CANCELLATIONS - DISBURSE FULL REFUND MODAL ═══ --}}
    {{-- ═════════════════════════════════════════════════════════════════ --}}
    <div x-show="cancelRefundModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/75 backdrop-blur-xs"
         @keydown.escape.window="cancelRefundModal = false">
        <div class="bg-white rounded-3xl max-w-lg w-full p-6 shadow-2xl border border-gray-100 space-y-4"
             @click.outside="cancelRefundModal = false">
            
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-700 flex items-center justify-center shrink-0 border border-indigo-200 text-lg">
                    💸
                </div>
                <div>
                    <h3 class="text-base font-bold text-gray-900">Disburse Cancellation Refund</h3>
                    <p class="text-xs text-gray-500">Record full payout transfer for paid cancelled order</p>
                </div>
            </div>

            <template x-if="selectedCancelOrder">
                <div class="bg-indigo-50/50 rounded-2xl p-3.5 border border-indigo-100 space-y-2 text-xs">
                    <div class="flex justify-between">
                        <span class="text-gray-500">Order Number:</span>
                        <span class="font-mono font-bold text-gray-800" x-text="selectedCancelOrder.order_number"></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Customer Name:</span>
                        <span class="font-bold text-gray-900" x-text="selectedCancelOrder.customer_name"></span>
                    </div>
                    <div class="flex justify-between border-t border-indigo-100 pt-1.5">
                        <span class="text-indigo-950 font-bold">Total Refund Due:</span>
                        <span class="font-mono font-black text-indigo-800 text-sm" x-text="'₱' + parseFloat(selectedCancelOrder.remaining_cancellation_refund || 0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})"></span>
                    </div>
                </div>
            </template>

            <template x-if="selectedCancelOrder">
                <form :action="(window.location.pathname.startsWith('/superadmin') ? '/superadmin/returns/orders/' : '/admin/returns/orders/') + selectedCancelOrder.id + '/refund-cancellation'" 
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
                        <input type="number" step="0.01" min="0.01" name="refund_amount" required x-model="cancelRefundAmount"
                               class="w-full px-3.5 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-xs font-mono font-bold text-gray-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all">
                    </div>

                    {{-- Outgoing Transfer Reference Number --}}
                    <div>
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-gray-700 mb-1">
                            Outgoing Reference / Transaction # <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" name="transfer_reference" required x-model="cancelTransferRef"
                               placeholder="e.g. 100999888877 or GCASH-TXN-12345"
                               class="w-full px-3.5 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-xs font-mono text-gray-900 placeholder:text-gray-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all">
                    </div>

                    {{-- Customer Destination Mobile / Account --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                        <div>
                            <label class="block text-[11px] font-bold uppercase tracking-wider text-gray-700 mb-1">
                                Destination Mobile / Wallet <span class="text-rose-500">*</span>
                            </label>
                            <input type="text" name="destination_account" required x-model="cancelDestAccount"
                                   placeholder="09XXXXXXXXX"
                                   class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-xl text-xs font-mono text-gray-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all">
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold uppercase tracking-wider text-gray-700 mb-1">
                                Account Holder Name
                            </label>
                            <input type="text" name="destination_name" x-model="cancelDestName"
                                   placeholder="Customer Full Name"
                                   class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-xl text-xs text-gray-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all">
                        </div>
                    </div>

                    {{-- Transfer Screenshot / Proof --}}
                    <div>
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-gray-700 mb-1">
                            Distribution Proof / Receipt Screenshot (Optional)
                        </label>
                        <input type="file" name="transfer_proof" accept="image/*,.pdf"
                               class="w-full text-xs text-gray-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 transition-all">
                    </div>

                    {{-- Admin Notes --}}
                    <div>
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-gray-700 mb-1">
                            Audit Notes
                        </label>
                        <textarea name="notes" rows="2" x-model="cancelNotes"
                                  placeholder="Add optional internal distribution note..."
                                  class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-xl text-xs text-gray-800 placeholder:text-gray-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all"></textarea>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-2">
                        <button type="button" @click="cancelRefundModal = false" :disabled="isSubmitting"
                                class="px-4 py-2 rounded-xl text-xs font-bold text-gray-600 hover:bg-gray-100 transition-colors cursor-pointer">
                            Cancel
                        </button>
                        <button type="submit" :disabled="isSubmitting || !cancelTransferRef.trim()"
                                class="inline-flex items-center gap-1.5 px-5 py-2 rounded-xl text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-700 shadow-md shadow-indigo-600/20 transition-all cursor-pointer">
                            <svg x-show="isSubmitting" class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                            <span x-text="isSubmitting ? 'Recording...' : 'Record Cancellation Refund'"></span>
                        </button>
                    </div>
                </form>
            </template>
        </div>
    </div>
</div>
@endsection
