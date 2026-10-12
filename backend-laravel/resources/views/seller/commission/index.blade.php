@extends('layouts.seller')

@section('content')
<div class="space-y-6 sm:space-y-8 max-w-6xl pb-28 lg:pb-12 px-2 sm:px-6">
    {{-- Header with Guide Button --}}
    <div id="tour-commission-header" class="flex flex-col sm:flex-row sm:items-end justify-between gap-3 sm:gap-4 pb-2 border-b" style="border-color: #E8DECB;">
        <div>
            <div class="text-[10px] font-bold text-[#C49520] uppercase tracking-[0.2em] mb-1 inline-flex items-center gap-1"><x-lucide name="sparkles" class="w-3 h-3 text-[#C49520]" /> Monthly Commission Remittance</div>
            <div class="flex items-center gap-3 flex-wrap">
                <h1 class="font-serif text-2xl sm:text-3xl font-bold text-[#1E1915]">Seller <span class="text-[#766C60] font-light italic">Platform Commission</span></h1>
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
                    <span>Commission Guide</span>
                </button>
            </div>
            <p class="text-xs text-[#766C60] mt-1 font-medium">Review your monthly platform commission dues on cash sales (Store Pickup &amp; COD) and submit your remittance payment to Superadmin.</p>
        </div>
    </div>

    {{-- CASH SALES PLATFORM COMMISSION --}}
    <div class="space-y-4">
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
                        <span class="font-bold text-emerald-400 uppercase"><x-lucide name="check-circle" class="w-3 h-3 inline mr-1" /> Paid</span>
                    @elseif($currentRecord && $currentRecord->status === 'verification_pending')
                        <span class="font-bold text-amber-300 uppercase"><x-lucide name="clock" class="w-3 h-3 inline mr-1" /> Verification Pending</span>
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

            {{-- Commission Status & Governance Card (Strictly Read-Only) --}}
            <div id="tour-commission-form" class="rounded-2xl p-5 sm:p-8 shadow-xs space-y-4 bg-[#FFFCF7] border border-[#E8DECB]">
                <h3 class="text-xs font-black uppercase tracking-widest text-[#1E1915] flex items-center gap-2">
                    <svg class="w-4 h-4 text-[#C49520]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                    Commission Status &amp; Governance
                </h3>

                @if($currentRecord && $currentRecord->status === 'paid')
                    <div class="p-6 bg-emerald-50 border border-emerald-200 rounded-2xl text-center space-y-2">
                        <div class="w-12 h-12 rounded-full bg-emerald-600 text-white flex items-center justify-center mx-auto"><x-lucide name="check-circle" class="w-6 h-6 text-white" /></div>
                        <div class="text-sm font-bold text-emerald-900">Commission Settled for {{ \Carbon\Carbon::parse($period . '-01')->format('F Y') }}</div>
                        <div class="text-xs text-emerald-700 font-sans">
                            Your platform commission of ₱{{ number_format($currentRecord->commissionAmount, 2) }} was verified as Paid by Super Administrator on {{ $currentRecord->paidAt ? $currentRecord->paidAt->format('M d, Y h:i A') : 'N/A' }}.
                        </div>
                    </div>
                @else
                    <div class="p-6 bg-amber-50/70 border border-amber-200 rounded-2xl space-y-3">
                        <div class="flex items-center gap-3">
                            <span class="w-10 h-10 rounded-full bg-amber-500 text-white flex items-center justify-center shrink-0"><x-lucide name="clock" class="w-5 h-5 text-white" /></span>
                            <div>
                                <div class="text-sm font-bold text-amber-900">Payment Due: ₱{{ number_format($commissionDue, 2) }}</div>
                                <div class="text-xs text-amber-700 font-sans">Billing Period: {{ \Carbon\Carbon::parse($period . '-01')->format('F Y') }} (Rate: {{ $rate }}%)</div>
                            </div>
                        </div>
                        <div class="text-xs text-gray-600 border-t border-amber-200/60 pt-3 space-y-1.5 leading-relaxed font-sans">
                            <p><strong>Remittance Instructions:</strong> Please remit your platform fee to one of the LumBarong official remittance accounts displayed to the left.</p>
                            <p><strong>Direct Governance:</strong> Remittance records and Paid/Unpaid status are verified and updated exclusively by the platform Super Administrator. Proof submissions directly through this page are discontinued to ensure financial auditing integrity.</p>
                        </div>
                    </div>
                @endif

                <div class="p-4 rounded-xl bg-gray-50 border border-gray-200 text-[11px] text-gray-500 space-y-1">
                    <div class="font-bold uppercase tracking-wider text-gray-700">Audit Policy:</div>
                    <p>Commission calculations are based on eligible completed seller-received revenue. Cancelled, refunded, or unfulfilled transactions are excluded.</p>
                </div>
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
                'title' => 'Platform Commission Remittance',
                'text' => 'Welcome to your Commission Remittance hub! Here you track monthly platform commission dues for cash sales (Store Pickup & COD) and submit remittances to Superadmin.',
                'position' => 'bottom'
            ],
            [
                'selector' => '#tour-commission-summary',
                'title' => 'Monthly Cash Sales & Net Due Summary',
                'text' => 'Track your eligible cash sales, commission tier percentage, and the net commission amount due by the 10th of each month.',
                'position' => 'bottom'
            ],
            [
                'selector' => '#tour-commission-accounts',
                'title' => 'Platform Remittance Accounts',
                'text' => 'Official LumBarong platform GCash and Maya account numbers and scannable QR codes for seamless commission remittances.',
                'position' => 'top'
            ],
            [
                'selector' => '#tour-commission-form',
                'title' => 'Submit Remittance Proof',
                'text' => 'After transferring your dues, select the payment method, enter your transaction reference number, and upload the receipt screenshot.',
                'position' => 'top'
            ],
            [
                'selector' => '#tour-commission-history',
                'title' => 'Settlement History & Status Audit',
                'text' => 'Audit past monthly billing cycles, payment reference codes, and real-time verification statuses.',
                'position' => 'top'
            ],
        ];
    @endphp

    <x-spotlight-tour tourId="seller-commission-guide" :steps="$commissionTourSteps" :autoStart="false" />
</div>
@endsection
