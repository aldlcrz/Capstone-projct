@extends('layouts.seller')

@section('content')
<div class="space-y-6 sm:space-y-8 max-w-6xl pb-28 lg:pb-12 px-2 sm:px-6">
    {{-- Header with Guide Button --}}
    <div id="tour-commission-header" class="flex flex-col sm:flex-row sm:items-end justify-between gap-3 sm:gap-4 pb-2 border-b" style="border-color: #E8DECB;">
        <div>
            <div class="text-[10px] font-bold text-[#C49520] uppercase tracking-[0.2em] mb-1">✦ Financial Ledger &amp; Earnings</div>
            <div class="flex items-center gap-3 flex-wrap">
                <h1 class="font-serif text-2xl sm:text-3xl font-bold text-[#1E1915]">Seller <span class="text-[#766C60] font-light italic">Earnings &amp; Payouts</span></h1>
                {{-- Guide Button --}}
                <button type="button" 
                        onclick="window.startSpotlightTour('seller-commission-guide')"
                        class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full text-xs font-black uppercase tracking-wider transition-all shadow-xs cursor-pointer group shrink-0"
                        style="background-color: #FDF8EE; color: #C49520; border: 1.5px solid #C49520;"
                        onmouseover="this.style.backgroundColor='#C49520'; this.style.color='#FFFFFF';"
                        onmouseout="this.style.backgroundColor='#FDF8EE'; this.style.color='#C49520';"
                        title="Start Interactive Commission Guide">
                    <svg class="w-4 h-4 transition-transform group-hover:rotate-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span>Financial Guide</span>
                </button>
            </div>
            <p class="text-xs text-[#766C60] mt-1 font-medium">Track your online GCash/Maya payouts, cash sales commissions, and verified platform settlements.</p>
        </div>
    </div>

    {{-- SECTION 1: ONLINE GCASH/MAYA SETTLEMENTS & PAYOUTS --}}
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <h2 class="text-sm font-black uppercase tracking-widest text-[#1E1915] flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                Online Payment Settlements (GCash / Maya)
            </h2>
            <span class="text-[10px] font-bold text-emerald-700 bg-emerald-50 border border-emerald-200 px-2.5 py-1 rounded-full uppercase tracking-wider">
                0% Platform Commission Policy
            </span>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-4 gap-3 sm:gap-4">
            <div class="rounded-2xl p-4 sm:p-5 shadow-xs space-y-1 bg-[#FFFCF7] border border-[#E8DECB]">
                <div class="text-[10px] font-bold uppercase tracking-widest text-[#766C60]">Available for Payout</div>
                <div class="text-xl sm:text-2xl font-black font-sans text-emerald-600">₱{{ number_format($financialSummary['online_available_for_payout'] ?? 0, 2) }}</div>
                <div class="text-[10px] text-[#766C60]">Orders delivered &amp; verified</div>
            </div>

            <div class="rounded-2xl p-4 sm:p-5 shadow-xs space-y-1 bg-[#FFFCF7] border border-[#E8DECB]">
                <div class="text-[10px] font-bold uppercase tracking-widest text-[#766C60]">Pending Eligibility</div>
                <div class="text-xl sm:text-2xl font-black font-sans text-amber-600">₱{{ number_format($financialSummary['online_pending_settlement'] ?? 0, 2) }}</div>
                <div class="text-[10px] text-[#766C60]">Fulfillment / delivery in-transit</div>
            </div>

            <div class="rounded-2xl p-4 sm:p-5 shadow-xs space-y-1 bg-[#FFFCF7] border border-[#E8DECB]">
                <div class="text-[10px] font-bold uppercase tracking-widest text-[#766C60]">Processing Payouts</div>
                <div class="text-xl sm:text-2xl font-black font-sans text-blue-600">₱{{ number_format($financialSummary['online_payout_processing'] ?? 0, 2) }}</div>
                <div class="text-[10px] text-[#766C60]">In admin queue</div>
            </div>

            <div class="rounded-2xl p-4 sm:p-5 shadow-xs space-y-1 bg-[#1E1915] text-white border border-[#C49520]">
                <div class="text-[10px] font-bold uppercase tracking-widest text-amber-200/70">Total Disbursed</div>
                <div class="text-xl sm:text-2xl font-black font-sans text-[#E6CA65]">₱{{ number_format($financialSummary['online_paid_payouts'] ?? 0, 2) }}</div>
                <div class="text-[10px] text-amber-100/70">Completed manual &amp; auto payouts</div>
            </div>
        </div>

        {{-- Online Settlements & Payouts Table --}}
        <div class="rounded-2xl shadow-xs overflow-hidden bg-[#FFFCF7] border border-[#E8DECB]">
            <div class="px-4 sm:px-6 py-3.5 border-b border-[#E8DECB] flex items-center justify-between">
                <h3 class="text-xs font-black uppercase tracking-widest text-[#1E1915]">Online Order Disbursements &amp; Settlements</h3>
                <span class="text-[10px] text-[#766C60] font-semibold">{{ $payouts->count() }} Record(s)</span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left min-w-162.5">
                    <thead>
                        <tr class="border-b bg-[#FDF8EE] border-[#E8DECB]">
                            <th class="px-4 sm:px-6 py-3 text-[9px] font-black uppercase tracking-widest text-[#766C60]">Order ID</th>
                            <th class="px-4 sm:px-6 py-3 text-[9px] font-black uppercase tracking-widest text-[#766C60]">Product Sales</th>
                            <th class="px-4 sm:px-6 py-3 text-[9px] font-black uppercase tracking-widest text-[#766C60]">Shipping</th>
                            <th class="px-4 sm:px-6 py-3 text-[9px] font-black uppercase tracking-widest text-[#766C60]">Net Settlement</th>
                            <th class="px-4 sm:px-6 py-3 text-[9px] font-black uppercase tracking-widest text-[#766C60]">Disbursement Ref</th>
                            <th class="px-4 sm:px-6 py-3 text-[9px] font-black uppercase tracking-widest text-[#766C60]">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#F0EAE1] text-xs font-sans text-[#1E1915]">
                        @forelse($payouts as $payout)
                        <tr class="hover:bg-[#FAF7F2] transition-all">
                            <td class="px-4 sm:px-6 py-3.5">
                                <span class="font-bold font-mono text-[#C0420A]">#{{ substr($payout->order_id, 0, 8) }}</span>
                                <div class="text-[10px] text-gray-500">{{ $payout->created_at ? $payout->created_at->format('M d, Y') : '—' }}</div>
                            </td>
                            <td class="px-4 sm:px-6 py-3.5 font-semibold">₱{{ number_format($payout->gross_sales, 2) }}</td>
                            <td class="px-4 sm:px-6 py-3.5 font-semibold text-gray-600">₱{{ number_format($payout->shipping_amount, 2) }}</td>
                            <td class="px-4 sm:px-6 py-3.5 font-black text-emerald-700">₱{{ number_format($payout->net_settlement_amount, 2) }}</td>
                            <td class="px-4 sm:px-6 py-3.5">
                                @if($payout->transaction_reference)
                                    <div class="font-bold text-[11px]">{{ $payout->payout_method }}</div>
                                    <div class="text-[10px] font-mono text-[#766C60]">Ref: {{ $payout->transaction_reference }}</div>
                                @else
                                    <span class="text-gray-400 italic text-[11px]">Awaiting Transfer</span>
                                @endif
                            </td>
                            <td class="px-4 sm:px-6 py-3.5">
                                @if($payout->status === 'PAID')
                                    <span class="px-2.5 py-0.5 bg-emerald-100 text-emerald-800 rounded-full text-[9px] font-black uppercase tracking-widest">Disbursed ✓</span>
                                @elseif($payout->status === 'AVAILABLE_FOR_PAYOUT')
                                    <span class="px-2.5 py-0.5 bg-blue-100 text-blue-800 rounded-full text-[9px] font-black uppercase tracking-widest">Available</span>
                                @elseif($payout->status === 'PAYOUT_PROCESSING')
                                    <span class="px-2.5 py-0.5 bg-amber-100 text-amber-800 rounded-full text-[9px] font-black uppercase tracking-widest">Processing</span>
                                @elseif($payout->status === 'ON_HOLD')
                                    <span class="px-2.5 py-0.5 bg-rose-100 text-rose-800 rounded-full text-[9px] font-black uppercase tracking-widest">On Hold</span>
                                @else
                                    <span class="px-2.5 py-0.5 bg-gray-100 text-gray-700 rounded-full text-[9px] font-black uppercase tracking-widest">Pending</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="py-8 text-center text-xs text-gray-400 italic">No online payment settlements recorded yet.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- SECTION 2: CASH SALES PLATFORM COMMISSION --}}
    <div class="space-y-4 pt-4 border-t border-[#E8DECB]">
        <div class="flex items-center justify-between">
            <h2 class="text-sm font-black uppercase tracking-widest text-[#1E1915] flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-[#C49520]"></span>
                Cash Sales Platform Commission (Store Pickup / COD)
            </h2>
            <span class="text-[10px] font-bold text-[#766C60] bg-[#FDF8EE] border border-[#E8DECB] px-2.5 py-1 rounded-full uppercase tracking-wider">
                Configured Rate: {{ $rate }}%
            </span>
        </div>

        {{-- Monthly Summary Card --}}
        <div id="tour-commission-summary" class="grid grid-cols-1 sm:grid-cols-3 gap-3 sm:gap-6">
            <div class="rounded-2xl p-4 sm:p-6 shadow-xs space-y-1 bg-[#FFFCF7] border border-[#E8DECB]">
                <div class="text-[10px] font-bold uppercase tracking-widest text-[#766C60]">Billing Period</div>
                <div class="text-xl sm:text-2xl font-bold font-sans text-[#1E1915]">{{ \Carbon\Carbon::parse($period . '-01')->format('F Y') }}</div>
                <div class="text-xs text-[#766C60]">Due by the 10th of next month</div>
            </div>

            <div class="rounded-2xl p-4 sm:p-6 shadow-xs space-y-1 bg-[#FFFCF7] border border-[#E8DECB]">
                <div class="text-[10px] font-bold uppercase tracking-widest text-[#766C60]">Eligible Cash Sales ({{ $rate }}%)</div>
                <div class="text-xl sm:text-2xl font-black font-sans text-[#C49520]">₱{{ number_format($cashSales, 2) }}</div>
                <div class="text-xs text-[#766C60]">COD &amp; Store Pickup cash base</div>
            </div>

            <div class="text-white rounded-2xl p-4 sm:p-6 shadow-xl space-y-1 relative overflow-hidden bg-[#1E1915] border border-[#C49520]">
                <div class="text-[10px] font-bold uppercase tracking-widest text-amber-200/70">Platform Commission Due</div>
                <div class="text-2xl sm:text-3xl font-black font-sans text-[#E6CA65]">₱{{ number_format($commissionDue, 2) }}</div>
                <div class="text-xs text-amber-100/80 font-sans">
                    Status: 
                    @if($currentRecord && $currentRecord->status === 'paid')
                        <span class="font-bold text-emerald-400 uppercase">✓ Paid</span>
                    @elseif($currentRecord && $currentRecord->status === 'verification_pending')
                        <span class="font-bold text-amber-300 uppercase">⏳ Verification Pending</span>
                    @else
                        <span class="font-bold text-rose-400 uppercase">Payment Due</span>
                    @endif
                </div>
            </div>
        </div>

        {{-- Payment Accounts & Submit Form --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 sm:gap-8">
            {{-- Super Admin Payment Accounts --}}
            <div id="tour-commission-accounts" class="rounded-2xl p-5 sm:p-8 shadow-xs space-y-4 bg-[#FFFCF7] border border-[#E8DECB]">
                <h3 class="text-xs font-black uppercase tracking-widest text-[#1E1915] flex items-center gap-2">
                    <svg class="w-4 h-4 text-[#C49520]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                    Platform Remittance Accounts
                </h3>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    {{-- GCash --}}
                    <div class="p-4 rounded-2xl space-y-3 text-center bg-[#FDF8EE] border border-[#E8DECB]">
                        <div class="text-xs font-bold text-[#1E1915] uppercase tracking-widest">GCash Account</div>
                        <div class="text-sm font-black text-[#1E1915] select-all font-sans">{{ $paymentSettings['gcash_number'] ?: 'Not provided' }}</div>
                        @if($paymentSettings['gcash_qr'])
                            <img src="{{ str_starts_with($paymentSettings['gcash_qr'], 'http') || str_starts_with($paymentSettings['gcash_qr'], '/') ? $paymentSettings['gcash_qr'] : asset('storage/' . $paymentSettings['gcash_qr']) }}" class="w-32 h-32 object-contain mx-auto rounded-xl border border-white shadow-xs" onerror="this.style.display='none'">
                        @else
                            <div class="h-32 bg-white/60 rounded-xl flex items-center justify-center text-[10px] text-gray-400 italic">No QR uploaded</div>
                        @endif
                    </div>

                    {{-- Maya --}}
                    <div class="p-4 rounded-2xl space-y-3 text-center bg-[#FDF8EE] border border-[#E8DECB]">
                        <div class="text-xs font-bold text-[#1E1915] uppercase tracking-widest">Maya Account</div>
                        <div class="text-sm font-black text-[#1E1915] select-all font-sans">{{ $paymentSettings['maya_number'] ?: 'Not provided' }}</div>
                        @if($paymentSettings['maya_qr'])
                            <img src="{{ str_starts_with($paymentSettings['maya_qr'], 'http') || str_starts_with($paymentSettings['maya_qr'], '/') ? $paymentSettings['maya_qr'] : asset('storage/' . $paymentSettings['maya_qr']) }}" class="w-32 h-32 object-contain mx-auto rounded-xl border border-white shadow-xs" onerror="this.style.display='none'">
                        @else
                            <div class="h-32 bg-white/60 rounded-xl flex items-center justify-center text-[10px] text-gray-400 italic">No QR uploaded</div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Submit Payment Proof Form --}}
            <div id="tour-commission-form" class="rounded-2xl p-5 sm:p-8 shadow-xs space-y-4 bg-[#FFFCF7] border border-[#E8DECB]">
                <h3 class="text-xs font-black uppercase tracking-widest text-[#1E1915] flex items-center gap-2">
                    <svg class="w-4 h-4 text-[#C49520]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                    Submit Remittance Proof
                </h3>

                @if($currentRecord && $currentRecord->status === 'paid')
                    <div class="p-6 bg-emerald-50 border border-emerald-200 rounded-2xl text-center space-y-2">
                        <div class="w-12 h-12 rounded-full bg-emerald-600 text-white flex items-center justify-center text-xl font-bold mx-auto">✓</div>
                        <div class="text-sm font-bold text-emerald-900">Commission Settled for {{ $period }}</div>
                        <div class="text-xs text-emerald-700 font-sans">Thank you! Your payment of ₱{{ number_format($currentRecord->commissionAmount, 2) }} was verified on {{ $currentRecord->paidAt ? $currentRecord->paidAt->format('M d, Y') : 'N/A' }}.</div>
                    </div>
                @else
                    <form action="{{ route('seller.commission.submit') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                        @csrf
                        <input type="hidden" name="period" value="{{ $period }}">

                        <div>
                            <label class="text-[10px] font-bold text-[#766C60] uppercase tracking-widest block mb-1">Payment Method *</label>
                            <select name="paymentMethod" required class="w-full h-11 px-4 rounded-xl text-xs font-bold outline-none font-sans bg-[#FDF8EE] border border-[#E8DECB] text-[#1E1915]">
                                <option value="GCash">GCash</option>
                                <option value="Maya">Maya</option>
                            </select>
                        </div>

                        <div>
                            <label class="text-[10px] font-bold text-[#766C60] uppercase tracking-widest block mb-1">Reference / Transaction Number *</label>
                            <input type="text" name="referenceNumber" required placeholder="e.g. 10029384812" class="w-full h-11 px-4 rounded-xl text-xs font-semibold outline-none font-sans bg-[#FDF8EE] border border-[#E8DECB] text-[#1E1915]">
                        </div>

                        <div>
                            <label class="text-[10px] font-bold text-[#766C60] uppercase tracking-widest block mb-1">Proof of Payment Screenshot *</label>
                            <input type="file" name="paymentProof" required accept="image/*" class="w-full text-xs text-[#766C60] file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-[#FDF8EE] file:text-[#1E1915] hover:file:bg-[#F3EAD8] cursor-pointer">
                        </div>

                        <div>
                            <label class="text-[10px] font-bold text-[#766C60] uppercase tracking-widest block mb-1">Notes (Optional)</label>
                            <textarea name="notes" rows="2" placeholder="Additional details..." class="w-full p-3 rounded-xl text-xs outline-none resize-none bg-[#FDF8EE] border border-[#E8DECB] text-[#1E1915]"></textarea>
                        </div>

                        <button type="submit" class="w-full h-12 text-white rounded-xl text-xs font-bold uppercase tracking-widest transition-all shadow-xs cursor-pointer bg-[#1E1915] hover:bg-[#C49520]">
                            Submit Remittance Proof
                        </button>
                    </form>
                @endif
            </div>
        </div>

        {{-- Payment History Table --}}
        <div id="tour-commission-history" class="rounded-2xl shadow-xs overflow-hidden bg-[#FFFCF7] border border-[#E8DECB]">
            <div class="px-4 sm:px-6 py-3.5 border-b border-[#E8DECB] flex items-center justify-between">
                <h3 class="text-xs sm:text-sm font-black uppercase tracking-widest text-[#1E1915]">Commission Settlement History</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left min-w-125">
                    <thead>
                        <tr class="border-b bg-[#FDF8EE] border-[#E8DECB]">
                            <th class="px-4 sm:px-6 py-3.5 text-[9px] sm:text-[10px] font-black uppercase tracking-widest text-[#766C60]">Period</th>
                            <th class="px-4 sm:px-6 py-3.5 text-[9px] sm:text-[10px] font-black uppercase tracking-widest text-[#766C60]">Cash Sales</th>
                            <th class="px-4 sm:px-6 py-3.5 text-[9px] sm:text-[10px] font-black uppercase tracking-widest text-[#766C60]">Commission</th>
                            <th class="px-4 sm:px-6 py-3.5 text-[9px] sm:text-[10px] font-black uppercase tracking-widest text-[#766C60]">Method / Ref</th>
                            <th class="px-4 sm:px-6 py-3.5 text-[9px] sm:text-[10px] font-black uppercase tracking-widest text-[#766C60]">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#F0EAE1] text-xs font-sans text-[#1E1915]">
                        @forelse($pastRecords as $rec)
                        <tr class="hover:bg-[#FAF7F2] transition-all">
                            <td class="px-4 sm:px-6 py-3.5 font-bold">{{ \Carbon\Carbon::parse($rec->period . '-01')->format('M Y') }}</td>
                            <td class="px-4 sm:px-6 py-3.5 font-semibold">₱{{ number_format($rec->totalSales, 2) }}</td>
                            <td class="px-4 sm:px-6 py-3.5 font-bold text-[#C49520]">₱{{ number_format($rec->commissionAmount, 2) }}</td>
                            <td class="px-4 sm:px-6 py-3.5">
                                @if($rec->referenceNumber)
                                    <div class="font-bold">{{ $rec->paymentMethod }}</div>
                                    <div class="text-[10px] text-[#766C60]">Ref: {{ $rec->referenceNumber }}</div>
                                @else
                                    <span class="text-gray-400 italic">None</span>
                                @endif
                            </td>
                            <td class="px-4 sm:px-6 py-3.5">
                                @if($rec->status === 'paid')
                                    <span class="px-2.5 py-0.5 sm:px-3 sm:py-1 bg-emerald-100 text-emerald-800 rounded-full text-[8px] sm:text-[9px] font-black uppercase tracking-widest">Paid</span>
                                @elseif($rec->status === 'verification_pending')
                                    <span class="px-2.5 py-0.5 sm:px-3 sm:py-1 bg-amber-100 text-amber-800 rounded-full text-[8px] sm:text-[9px] font-black uppercase tracking-widest">Verification Pending</span>
                                @else
                                    <span class="px-2.5 py-0.5 sm:px-3 sm:py-1 bg-rose-100 text-rose-800 rounded-full text-[8px] sm:text-[9px] font-black uppercase tracking-widest">Unpaid</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="py-12 text-center text-xs text-gray-400 italic">No past commission payment records.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Spotlight Tour Guide --}}
    @php
        $commissionTourSteps = [
            [
                'selector' => '#tour-commission-header',
                'title' => '💵 Financial Settlement & Payouts',
                'text' => 'Welcome to your Earnings & Settlement hub! Here you track online GCash/Maya settlements and monthly platform cash sales commission dues.',
                'position' => 'bottom'
            ],
            [
                'selector' => '#tour-commission-summary',
                'title' => '📈 Monthly Cash Sales & Net Due Summary',
                'text' => 'Track your eligible cash sales, commission tier percentage, and the net commission amount due by the 10th of each month.',
                'position' => 'bottom'
            ],
            [
                'selector' => '#tour-commission-accounts',
                'title' => '💳 Platform Remittance Accounts',
                'text' => 'Official LumBarong platform GCash and Maya account numbers and scannable QR codes for seamless commission remittances.',
                'position' => 'top'
            ],
            [
                'selector' => '#tour-commission-form',
                'title' => '📤 Submit Remittance Proof',
                'text' => 'After transferring your dues, select the payment method, enter your transaction reference number, and upload the receipt screenshot.',
                'position' => 'top'
            ],
            [
                'selector' => '#tour-commission-history',
                'title' => '📜 Settlement History & Status Audit',
                'text' => 'Audit past monthly billing cycles, payment reference codes, and real-time verification statuses.',
                'position' => 'top'
            ],
        ];
    @endphp

    <x-spotlight-tour tourId="seller-commission-guide" :steps="$commissionTourSteps" :autoStart="false" />
</div>
@endsection
