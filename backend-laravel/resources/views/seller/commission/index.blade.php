@extends('layouts.seller')

@section('content')
<div class="space-y-6 sm:space-y-8 max-w-6xl pb-28 lg:pb-12 px-2 sm:px-6 font-sans"
     x-data="sellerCommissionApp()"
     x-cloak>

    {{-- Flash Notifications --}}
    @if(session('success'))
        <div class="p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-900 text-xs sm:text-sm font-bold flex items-center justify-between gap-3 shadow-xs">
            <div class="flex items-center gap-2.5">
                <x-lucide name="check-circle" class="w-5 h-5 text-emerald-600 shrink-0" />
                <span>{{ session('success') }}</span>
            </div>
            <button type="button" @click="$el.parentElement.remove()" class="text-emerald-700 hover:text-emerald-900 text-xs font-black uppercase">Dismiss</button>
        </div>
    @endif

    @if(session('error') || $errors->any())
        <div class="p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-900 text-xs sm:text-sm font-bold space-y-1 shadow-xs">
            <div class="flex items-center gap-2.5">
                <x-lucide name="alert-triangle" class="w-5 h-5 text-rose-600 shrink-0" />
                <span>{{ session('error') ?: 'Please check the form for errors and try again.' }}</span>
            </div>
            @if($errors->any())
                <ul class="list-disc list-inside text-xs text-rose-700 pl-7 space-y-0.5">
                    @foreach($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            @endif
        </div>
    @endif

    {{-- Header with Guide Button --}}
    <div id="tour-commission-header" class="flex flex-col sm:flex-row sm:items-end justify-between gap-3 sm:gap-4 pb-2 border-b" style="border-color: #E8DECB;">
        <div>
            <div class="text-[10px] font-bold text-[#C49520] uppercase tracking-[0.2em] mb-1 inline-flex items-center gap-1">
                <x-lucide name="sparkles" class="w-3 h-3 text-[#C49520]" /> Monthly Commission Remittance
            </div>
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

            <div class="text-white rounded-2xl p-4 sm:p-6 shadow-xl space-y-2 relative overflow-hidden bg-[#1E1915] border border-[#C49520] flex flex-col justify-between">
                <div>
                    <div class="text-[10px] font-bold uppercase tracking-widest text-amber-200/70">Platform Commission Due</div>
                    <div class="text-2xl sm:text-3xl font-black font-sans text-[#E6CA65]">₱{{ number_format($commissionDue, 2) }}</div>
                    <div class="text-xs text-amber-100/80 font-sans mt-0.5">
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

                {{-- Action / Pay Commission Button --}}
                <div class="pt-2">
                    @if($currentRecord && $currentRecord->status === 'paid')
                        <div class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-emerald-500/20 border border-emerald-400/40 text-emerald-300 text-xs font-bold uppercase tracking-wider">
                            <x-lucide name="check-circle" class="w-3.5 h-3.5 text-emerald-400" /> Settled
                        </div>
                    @elseif($currentRecord && $currentRecord->status === 'verification_pending')
                        <button type="button" 
                                @click="openPayModal()"
                                class="w-full sm:w-auto inline-flex items-center justify-center gap-1.5 px-3.5 py-2 rounded-xl bg-[#C49520] hover:bg-[#DFC97A] text-[#1E1915] text-xs font-black uppercase tracking-wider transition-all shadow-md cursor-pointer active:scale-95">
                            <x-lucide name="receipt" class="w-3.5 h-3.5 text-[#1E1915]" /> View / Update Remittance
                        </button>
                    @else
                        <button type="button" 
                                @click="openPayModal()"
                                class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-[#C49520] hover:bg-[#DFC97A] text-[#1E1915] text-xs font-black uppercase tracking-wider transition-all shadow-md cursor-pointer active:scale-95">
                            <x-lucide name="credit-card" class="w-4 h-4 text-[#1E1915]" /> Pay Commission
                        </button>
                    @endif
                </div>
            </div>
        </div>

        {{-- Payment Accounts & Submit Form --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 sm:gap-8">
            {{-- Super Admin Payment Accounts --}}
            <div id="tour-commission-accounts" class="rounded-2xl p-5 sm:p-8 shadow-xs space-y-4 bg-[#FFFCF7] border border-[#E8DECB]">
                <div class="flex items-center justify-between">
                    <h3 class="text-xs font-black uppercase tracking-widest text-[#1E1915] flex items-center gap-2">
                        <svg class="w-4 h-4 text-[#C49520]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                        Platform Remittance Accounts
                    </h3>
                    <span class="text-[10px] text-[#766C60] font-semibold">Official Superadmin Wallet</span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    {{-- GCash --}}
                    <div class="p-4 rounded-2xl space-y-3 text-center bg-[#FDF8EE] border border-[#E8DECB]">
                        <div class="text-xs font-bold text-[#1E1915] uppercase tracking-widest flex items-center justify-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                            <span>GCash Account</span>
                        </div>
                        <div class="flex items-center justify-center gap-2">
                            <span class="text-sm font-black text-[#1E1915] select-all font-mono">{{ $paymentSettings['gcash_number'] ?: 'Not provided' }}</span>
                            @if($paymentSettings['gcash_number'])
                                <button type="button" 
                                        @click="copyToClipboard('{{ $paymentSettings['gcash_number'] }}', 'GCash Number copied!')"
                                        class="px-2 py-0.5 rounded-md text-[9.5px] font-bold uppercase tracking-wider bg-white border border-[#E8DECB] hover:bg-gray-50 text-[#766C60] cursor-pointer"
                                        title="Copy number">
                                    Copy
                                </button>
                            @endif
                        </div>
                        @if($paymentSettings['gcash_qr'])
                            @php
                                $gcashQrUrl = str_starts_with($paymentSettings['gcash_qr'], 'http') || str_starts_with($paymentSettings['gcash_qr'], '/') ? $paymentSettings['gcash_qr'] : asset('storage/' . $paymentSettings['gcash_qr']);
                            @endphp
                            <div class="relative group cursor-pointer" @click="openProofModal('{{ $gcashQrUrl }}', 'Official Superadmin GCash QR Code', '{{ $paymentSettings['gcash_number'] }}')">
                                <img src="{{ $gcashQrUrl }}" class="w-32 h-32 object-contain mx-auto rounded-xl border border-white shadow-xs group-hover:scale-105 transition-transform" onerror="this.style.display='none'">
                                <div class="text-[9.5px] text-[#C49520] font-bold text-center mt-1 group-hover:underline">Tap to zoom QR</div>
                            </div>
                        @else
                            <div class="h-32 bg-white/60 rounded-xl flex items-center justify-center text-[10px] text-gray-400 italic">No QR uploaded</div>
                        @endif
                    </div>

                    {{-- Maya --}}
                    <div class="p-4 rounded-2xl space-y-3 text-center bg-[#FDF8EE] border border-[#E8DECB]">
                        <div class="text-xs font-bold text-[#1E1915] uppercase tracking-widest flex items-center justify-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                            <span>Maya Account</span>
                        </div>
                        <div class="flex items-center justify-center gap-2">
                            <span class="text-sm font-black text-[#1E1915] select-all font-mono">{{ $paymentSettings['maya_number'] ?: 'Not provided' }}</span>
                            @if($paymentSettings['maya_number'])
                                <button type="button" 
                                        @click="copyToClipboard('{{ $paymentSettings['maya_number'] }}', 'Maya Number copied!')"
                                        class="px-2 py-0.5 rounded-md text-[9.5px] font-bold uppercase tracking-wider bg-white border border-[#E8DECB] hover:bg-gray-50 text-[#766C60] cursor-pointer"
                                        title="Copy number">
                                    Copy
                                </button>
                            @endif
                        </div>
                        @if($paymentSettings['maya_qr'])
                            @php
                                $mayaQrUrl = str_starts_with($paymentSettings['maya_qr'], 'http') || str_starts_with($paymentSettings['maya_qr'], '/') ? $paymentSettings['maya_qr'] : asset('storage/' . $paymentSettings['maya_qr']);
                            @endphp
                            <div class="relative group cursor-pointer" @click="openProofModal('{{ $mayaQrUrl }}', 'Official Superadmin Maya QR Code', '{{ $paymentSettings['maya_number'] }}')">
                                <img src="{{ $mayaQrUrl }}" class="w-32 h-32 object-contain mx-auto rounded-xl border border-white shadow-xs group-hover:scale-105 transition-transform" onerror="this.style.display='none'">
                                <div class="text-[9.5px] text-[#C49520] font-bold text-center mt-1 group-hover:underline">Tap to zoom QR</div>
                            </div>
                        @else
                            <div class="h-32 bg-white/60 rounded-xl flex items-center justify-center text-[10px] text-gray-400 italic">No QR uploaded</div>
                        @endif
                    </div>
                </div>

                {{-- Copy toast feedback --}}
                <div x-show="copiedText" x-transition.opacity class="text-center text-xs font-bold text-emerald-700 bg-emerald-50 py-1.5 px-3 rounded-lg border border-emerald-200">
                    <span x-text="copiedText"></span>
                </div>
            </div>

            {{-- Commission Status & Governance Card --}}
            <div id="tour-commission-form" class="rounded-2xl p-5 sm:p-8 shadow-xs space-y-4 bg-[#FFFCF7] border border-[#E8DECB] flex flex-col justify-between">
                <div class="space-y-4">
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
                            @if($currentRecord->referenceNumber)
                                <div class="text-xs text-emerald-800 font-mono font-bold mt-1">Ref: {{ $currentRecord->referenceNumber }} ({{ $currentRecord->paymentMethod }})</div>
                            @endif
                        </div>
                    @elseif($currentRecord && $currentRecord->status === 'verification_pending')
                        <div class="p-5 sm:p-6 bg-amber-50/70 border border-amber-200 rounded-2xl space-y-3">
                            <div class="flex items-center justify-between gap-2">
                                <div class="flex items-center gap-3">
                                    <span class="w-10 h-10 rounded-full bg-amber-500 text-white flex items-center justify-center shrink-0"><x-lucide name="clock" class="w-5 h-5 text-white" /></span>
                                    <div>
                                        <div class="text-sm font-bold text-amber-900">Verification Pending</div>
                                        <div class="text-xs text-amber-700 font-sans">Remittance of ₱{{ number_format($currentRecord->commissionAmount, 2) }} submitted</div>
                                    </div>
                                </div>
                                <span class="px-2.5 py-1 bg-amber-200/80 text-amber-900 rounded-full text-[9px] font-black uppercase tracking-wider shrink-0">Under Review</span>
                            </div>
                            <div class="text-xs text-gray-700 border-t border-amber-200/60 pt-3 space-y-1.5 leading-relaxed font-sans">
                                <p><strong>Method:</strong> {{ $currentRecord->paymentMethod }}</p>
                                <p><strong>Reference Number:</strong> <span class="font-mono font-bold text-[#1E1915]">{{ $currentRecord->referenceNumber }}</span></p>
                                @if($currentRecord->paymentProof)
                                    @php
                                        $currentProofUrl = str_starts_with($currentRecord->paymentProof, 'http') || str_starts_with($currentRecord->paymentProof, '/') ? $currentRecord->paymentProof : asset('storage/' . ltrim($currentRecord->paymentProof, '/'));
                                    @endphp
                                    <div class="pt-1">
                                        <button type="button" 
                                                @click="openProofModal('{{ $currentProofUrl }}', 'Commission Remittance Proof ({{ \Carbon\Carbon::parse($period . '-01')->format('M Y') }})', '{{ $currentRecord->referenceNumber }}')"
                                                class="inline-flex items-center gap-1.5 text-xs font-bold text-[#C49520] hover:underline cursor-pointer">
                                            <x-lucide name="image" class="w-3.5 h-3.5 text-[#C49520]" />
                                            <span>View Submitted Receipt</span>
                                        </button>
                                    </div>
                                @endif
                                <p class="text-[11px] text-gray-500 pt-0.5">Your remittance proof is currently undergoing financial verification by the platform Super Administrator.</p>
                            </div>
                            <div class="pt-2">
                                <button type="button" 
                                        @click="openPayModal()"
                                        class="w-full py-2.5 px-4 rounded-xl bg-white border border-[#C49520] text-[#1E1915] hover:bg-[#FDF8EE] text-xs font-bold uppercase tracking-wider transition-all flex items-center justify-center gap-2 cursor-pointer shadow-2xs">
                                    <x-lucide name="pencil" class="w-3.5 h-3.5 text-[#C49520]" />
                                    <span>Update Remittance Details</span>
                                </button>
                            </div>
                        </div>
                    @else
                        <div class="p-5 sm:p-6 bg-amber-50/70 border border-amber-200 rounded-2xl space-y-3">
                            <div class="flex items-center gap-3">
                                <span class="w-10 h-10 rounded-full bg-amber-500 text-white flex items-center justify-center shrink-0"><x-lucide name="clock" class="w-5 h-5 text-white" /></span>
                                <div>
                                    <div class="text-sm font-bold text-amber-900">Payment Due: ₱{{ number_format($commissionDue, 2) }}</div>
                                    <div class="text-xs text-amber-700 font-sans">Billing Period: {{ \Carbon\Carbon::parse($period . '-01')->format('F Y') }} (Rate: {{ $rate }}%)</div>
                                </div>
                            </div>
                            <div class="text-xs text-gray-600 border-t border-amber-200/60 pt-3 space-y-1.5 leading-relaxed font-sans">
                                <p><strong>Remittance Instructions:</strong> Please remit your platform fee to the official GCash or Maya accounts on the left, then click <strong>Pay Commission</strong> to attach your transaction reference number and proof receipt.</p>
                            </div>
                            <div class="pt-2">
                                <button type="button" 
                                        @click="openPayModal()"
                                        class="w-full py-3 px-4 rounded-xl bg-[#C49520] hover:bg-[#A87B10] text-white text-xs font-black uppercase tracking-wider transition-all flex items-center justify-center gap-2 cursor-pointer shadow-md active:scale-95">
                                    <x-lucide name="credit-card" class="w-4 h-4 text-white" />
                                    <span>Pay Commission Now</span>
                                </button>
                            </div>
                        </div>
                    @endif
                </div>

                <div class="p-4 rounded-xl bg-gray-50 border border-gray-200 text-[11px] text-gray-500 space-y-1 mt-4">
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
                                    @if($rec->paymentProof)
                                        @php
                                            $recProofUrl = str_starts_with($rec->paymentProof, 'http') || str_starts_with($rec->paymentProof, '/') ? $rec->paymentProof : asset('storage/' . ltrim($rec->paymentProof, '/'));
                                        @endphp
                                        <button type="button" 
                                                @click="openProofModal('{{ $recProofUrl }}', 'Commission Remittance Proof ({{ \Carbon\Carbon::parse($rec->period . '-01')->format('M Y') }})', '{{ $rec->referenceNumber }}')"
                                                class="text-[10px] text-[#C49520] font-bold hover:underline inline-flex items-center gap-1 mt-0.5 cursor-pointer">
                                            <x-lucide name="image" class="w-3 h-3 text-[#C49520]" />
                                            <span>View Receipt</span>
                                        </button>
                                    @endif
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

    {{-- PAY COMMISSION MODAL --}}
    <div x-show="payModal"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 bg-black/70 backdrop-blur-xs z-50 flex items-center justify-center p-3 sm:p-4 overflow-y-auto"
         @click.self="closePayModal()"
         style="display: none;"
         x-cloak>

        <div class="bg-[#FFFCF7] border border-[#E8DECB] rounded-3xl max-w-lg w-full p-5 sm:p-6 space-y-4 shadow-2xl relative my-auto overflow-hidden font-sans"
             @click.stop>
            
            {{-- Header decorative bar --}}
            <div class="h-1.5 w-full bg-linear-to-r from-[#C49520] via-amber-500 to-[#1E1915] absolute top-0 left-0"></div>

            {{-- Modal Top Row --}}
            <div class="flex items-center justify-between border-b border-[#E8DECB] pb-3">
                <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-xl flex items-center justify-center bg-[#FDF8EE] border border-[#E8DECB] text-[#C49520]">
                        <x-lucide name="credit-card" class="w-5 h-5 text-[#C49520]" />
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-[#1E1915]">Remit Platform Commission</h3>
                        <p class="text-[10px] text-[#766C60] font-medium">Billing Period: {{ \Carbon\Carbon::parse($period . '-01')->format('F Y') }}</p>
                    </div>
                </div>
                <button type="button" 
                        @click="closePayModal()" 
                        class="w-8 h-8 rounded-full bg-gray-100 hover:bg-gray-200 text-gray-500 hover:text-gray-800 flex items-center justify-center transition-all cursor-pointer">
                    <x-lucide name="x" class="w-4 h-4" />
                </button>
            </div>

            {{-- Dues Summary Badge --}}
            <div class="p-3.5 rounded-2xl bg-[#FDF8EE] border border-[#E8DECB] flex items-center justify-between">
                <div>
                    <span class="text-[10px] font-bold text-[#766C60] uppercase tracking-wider block">Total Commission Due</span>
                    <span class="text-xs text-[#766C60]">From ₱{{ number_format($cashSales, 2) }} Cash Sales ({{ $rate }}%)</span>
                </div>
                <div class="text-right">
                    <span class="text-xl sm:text-2xl font-black text-[#C49520] font-sans">₱{{ number_format($commissionDue, 2) }}</span>
                </div>
            </div>

            {{-- Commission Payment Form --}}
            <form action="{{ route('seller.commission.submit') }}" 
                  method="POST" 
                  enctype="multipart/form-data" 
                  @submit="submitting = true" 
                  class="space-y-4">
                @csrf
                <input type="hidden" name="period" value="{{ $period }}">

                {{-- Step 1: Remittance Account Channel Selection --}}
                <div class="space-y-1.5">
                    <label class="block text-[10px] font-black uppercase tracking-wider text-[#766C60]">
                        1. Select Remittance Channel <span class="text-red-500">*</span>
                    </label>
                    <div class="grid grid-cols-2 gap-2.5">
                        {{-- GCash Option --}}
                        <label class="p-3 rounded-2xl border cursor-pointer transition-all flex flex-col justify-between space-y-2"
                               :class="selectedMethod === 'GCash' ? 'bg-blue-50/60 border-blue-400 ring-2 ring-blue-400/20 shadow-xs' : 'bg-white border-[#E8DECB] hover:bg-[#FAF7F2]'">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-1.5">
                                    <input type="radio" name="paymentMethod" value="GCash" x-model="selectedMethod" class="text-blue-600 focus:ring-blue-500">
                                    <span class="text-xs font-bold text-[#1E1915]">GCash</span>
                                </div>
                                <span class="text-[9px] font-bold px-1.5 py-0.5 rounded-md bg-blue-100 text-blue-800">e-Wallet</span>
                            </div>
                            <div class="text-left font-mono font-bold text-xs text-[#1E1915] select-all truncate">
                                {{ $paymentSettings['gcash_number'] ?: 'Not provided' }}
                            </div>
                            @if($paymentSettings['gcash_number'])
                                <button type="button" 
                                        @click.stop="copyToClipboard('{{ $paymentSettings['gcash_number'] }}', 'GCash Number copied!')"
                                        class="w-full py-1 text-[9px] font-black uppercase tracking-wider rounded-lg bg-white border border-blue-200 text-blue-700 hover:bg-blue-50 cursor-pointer">
                                    Copy Number
                                </button>
                            @endif
                        </label>

                        {{-- Maya Option --}}
                        <label class="p-3 rounded-2xl border cursor-pointer transition-all flex flex-col justify-between space-y-2"
                               :class="selectedMethod === 'Maya' ? 'bg-emerald-50/60 border-emerald-400 ring-2 ring-emerald-400/20 shadow-xs' : 'bg-white border-[#E8DECB] hover:bg-[#FAF7F2]'">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-1.5">
                                    <input type="radio" name="paymentMethod" value="Maya" x-model="selectedMethod" class="text-emerald-600 focus:ring-emerald-500">
                                    <span class="text-xs font-bold text-[#1E1915]">Maya</span>
                                </div>
                                <span class="text-[9px] font-bold px-1.5 py-0.5 rounded-md bg-emerald-100 text-emerald-800">e-Wallet</span>
                            </div>
                            <div class="text-left font-mono font-bold text-xs text-[#1E1915] select-all truncate">
                                {{ $paymentSettings['maya_number'] ?: 'Not provided' }}
                            </div>
                            @if($paymentSettings['maya_number'])
                                <button type="button" 
                                        @click.stop="copyToClipboard('{{ $paymentSettings['maya_number'] }}', 'Maya Number copied!')"
                                        class="w-full py-1 text-[9px] font-black uppercase tracking-wider rounded-lg bg-white border border-emerald-200 text-emerald-700 hover:bg-emerald-50 cursor-pointer">
                                    Copy Number
                                </button>
                            @endif
                        </label>
                    </div>
                </div>

                {{-- QR Code Hint if available --}}
                <div x-show="selectedMethod === 'GCash' && gcashQr" class="p-2.5 rounded-xl bg-blue-50/40 border border-blue-200/60 flex items-center justify-between gap-2">
                    <span class="text-[11px] text-blue-900 font-medium">Scan Superadmin GCash QR Code</span>
                    <button type="button" 
                            @click="openProofModal(gcashQr, 'Official Superadmin GCash QR Code', gcashNumber)"
                            class="px-2 py-1 rounded-lg bg-white text-blue-700 font-bold text-[10px] border border-blue-200 hover:bg-blue-50 cursor-pointer">
                        View QR
                    </button>
                </div>
                <div x-show="selectedMethod === 'Maya' && mayaQr" class="p-2.5 rounded-xl bg-emerald-50/40 border border-emerald-200/60 flex items-center justify-between gap-2">
                    <span class="text-[11px] text-emerald-900 font-medium">Scan Superadmin Maya QR Code</span>
                    <button type="button" 
                            @click="openProofModal(mayaQr, 'Official Superadmin Maya QR Code', mayaNumber)"
                            class="px-2 py-1 rounded-lg bg-white text-emerald-700 font-bold text-[10px] border border-emerald-200 hover:bg-emerald-50 cursor-pointer">
                        View QR
                    </button>
                </div>

                {{-- Step 2: Transaction Reference Number --}}
                <div class="space-y-1">
                    <label for="commission-reference-input" class="block text-[10px] font-black uppercase tracking-wider text-[#766C60]">
                        2. Transaction Reference Number <span class="text-red-500">*</span>
                    </label>
                    <input type="text"
                           id="commission-reference-input"
                           name="referenceNumber"
                           x-model="referenceNumber"
                           required
                           placeholder="e.g. 13-digit GCash Ref or 12-digit Maya Ref"
                           style="border-color: #E8DECB; background-color: #FFFFFF;"
                           class="w-full rounded-xl border px-3.5 py-2.5 font-mono text-xs font-bold text-[#1E1915] uppercase placeholder:font-sans placeholder:normal-case placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-[#C49520]/40 focus:border-[#C49520]">
                    <div class="text-[10px] text-[#766C60]">Please enter the official transaction reference number shown on your GCash/Maya transfer receipt.</div>
                </div>

                {{-- Step 3: Payment Proof Screenshot --}}
                <div class="space-y-1.5">
                    <label class="block text-[10px] font-black uppercase tracking-wider text-[#766C60]">
                        3. Upload Payment Receipt Screenshot <span class="text-red-500">*</span>
                    </label>

                    <input type="file" 
                           id="commission-proof-input"
                           name="paymentProof" 
                           accept="image/*" 
                           @change="onFileChange($event)" 
                           class="hidden">

                    {{-- Image Preview Container --}}
                    <template x-if="photoPreview || existingProof">
                        <div class="p-3 bg-white border border-[#E8DECB] rounded-2xl space-y-2">
                            <div class="w-full rounded-xl overflow-hidden border border-gray-200 bg-gray-50 max-h-48 flex items-center justify-center p-1 relative">
                                <img :src="photoPreview || existingProof" class="max-h-44 w-full object-contain rounded-lg" alt="Receipt Preview">
                            </div>
                            <div class="flex items-center justify-between text-[11px] font-bold px-1">
                                <span class="text-emerald-700 inline-flex items-center gap-1">
                                    <x-lucide name="check" class="w-3.5 h-3.5 text-emerald-600" />
                                    <span x-text="photoPreview ? 'New receipt photo selected' : 'Previously submitted proof attached'"></span>
                                </span>
                                <div class="flex items-center gap-2">
                                    <button type="button" 
                                            @click="document.getElementById('commission-proof-input').click()" 
                                            class="text-[#C49520] hover:underline font-black uppercase text-[10px] cursor-pointer">
                                        Replace
                                    </button>
                                    <template x-if="photoPreview">
                                        <button type="button" 
                                                @click="removePhoto()" 
                                                class="text-red-600 hover:underline font-black uppercase text-[10px] cursor-pointer">
                                            Clear
                                        </button>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </template>

                    {{-- File Dropzone when no photo --}}
                    <template x-if="!photoPreview && !existingProof">
                        <div @click="document.getElementById('commission-proof-input').click()"
                             class="p-4 sm:p-5 rounded-2xl border-2 border-dashed border-[#E8DECB] hover:border-[#C49520] bg-white hover:bg-[#FDF8EE] transition-all cursor-pointer text-center space-y-1.5 group">
                            <div class="w-10 h-10 rounded-full bg-[#FDF8EE] border border-[#E8DECB] text-[#C49520] flex items-center justify-center mx-auto group-hover:scale-105 transition-transform">
                                <x-lucide name="upload-cloud" class="w-5 h-5 text-[#C49520]" />
                            </div>
                            <div class="text-xs font-bold text-[#1E1915]">Click or tap to choose receipt screenshot</div>
                            <div class="text-[10px] text-[#766C60]">PNG, JPG, JPEG, or WebP up to 10MB</div>
                        </div>
                    </template>
                </div>

                {{-- Step 4: Optional Notes --}}
                <div class="space-y-1">
                    <label for="commission-notes-input" class="block text-[10px] font-black uppercase tracking-wider text-[#766C60]">
                        4. Artisan Remittance Notes (Optional)
                    </label>
                    <textarea id="commission-notes-input"
                              name="notes"
                              x-model="notes"
                              rows="2"
                              placeholder="Any additional details or transaction notes for Superadmin auditing..."
                              style="border-color: #E8DECB; background-color: #FFFFFF;"
                              class="w-full rounded-xl border px-3 py-2 text-xs text-[#1E1915] placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-[#C49520]/40 focus:border-[#C49520]"></textarea>
                </div>

                {{-- Modal Action Buttons --}}
                <div class="flex items-center justify-end gap-2.5 pt-2 border-t border-[#E8DECB]">
                    <button type="button" 
                            @click="closePayModal()" 
                            :disabled="submitting"
                            class="px-4 py-2.5 rounded-xl border border-[#E8DECB] bg-white text-xs font-bold text-[#1E1915] hover:bg-gray-50 transition-all cursor-pointer">
                        Cancel
                    </button>
                    <button type="submit" 
                            :disabled="submitting"
                            style="background-color: #C49520; color: #FFFFFF;"
                            class="px-5 py-2.5 rounded-xl text-xs font-black uppercase tracking-wider hover:bg-[#A87B10] disabled:opacity-50 transition-all shadow-md flex items-center gap-2 cursor-pointer">
                        <template x-if="submitting">
                            <svg class="w-4 h-4 animate-spin text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/></svg>
                        </template>
                        <x-lucide name="check" class="w-4 h-4 text-white" x-show="!submitting" />
                        <span x-text="submitting ? 'Submitting Remittance...' : 'Submit Remittance Payment'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- PROOF / QR CODE ZOOM LIGHTBOX MODAL --}}
    <div x-show="proofModal"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 bg-black/80 backdrop-blur-sm z-50 flex items-center justify-center p-3 sm:p-6"
         @click.self="closeProofModal()"
         style="display: none;"
         x-cloak>

        <div class="bg-white rounded-3xl max-w-md w-full p-5 space-y-3 shadow-2xl relative border border-gray-100 text-center"
             @click.stop>
            <div class="flex items-center justify-between border-b pb-2">
                <span class="text-xs font-bold text-[#1E1915] truncate" x-text="activeProofTitle || 'Image Viewer'"></span>
                <button type="button" @click="closeProofModal()" class="text-gray-400 hover:text-black">
                    <x-lucide name="x" class="w-4 h-4" />
                </button>
            </div>
            <div class="max-h-96 overflow-auto flex items-center justify-center bg-gray-50 rounded-2xl p-2 border">
                <img :src="activeProofUrl" class="max-h-90 w-full object-contain rounded-xl" alt="Zoomed Receipt">
            </div>
            <div class="flex items-center justify-between text-xs text-gray-500 font-mono">
                <span x-text="activeProofRef ? 'Ref: ' + activeProofRef : ''"></span>
                <a :href="activeProofUrl" target="_blank" download class="text-[#C49520] hover:underline font-bold text-xs">Open in New Tab ↗</a>
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
                'title' => 'Commission Governance & Remittance Status',
                'text' => 'Review the payment status of your monthly dues and click Pay Commission to submit your transaction reference number and proof receipt.',
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

@once
<script>
function sellerCommissionApp() {
    return {
        payModal: false,
        proofModal: false,
        activeProofUrl: '',
        activeProofTitle: '',
        activeProofRef: '',
        selectedMethod: '{{ $currentRecord?->paymentMethod ?: 'GCash' }}',
        referenceNumber: '{{ addslashes($currentRecord?->referenceNumber ?: '') }}',
        notes: '{{ addslashes($currentRecord?->notes ?: '') }}',
        photoPreview: null,
        photoFile: null,
        existingProof: '{{ $currentRecord?->paymentProof ? (str_starts_with($currentRecord->paymentProof, 'http') || str_starts_with($currentRecord->paymentProof, '/') ? $currentRecord->paymentProof : asset('storage/' . ltrim($currentRecord->paymentProof, '/'))) : '' }}',
        gcashNumber: '{{ addslashes($paymentSettings['gcash_number'] ?? '') }}',
        mayaNumber: '{{ addslashes($paymentSettings['maya_number'] ?? '') }}',
        gcashQr: '{{ $paymentSettings['gcash_qr'] ? (str_starts_with($paymentSettings['gcash_qr'], 'http') || str_starts_with($paymentSettings['gcash_qr'], '/') ? $paymentSettings['gcash_qr'] : asset('storage/' . $paymentSettings['gcash_qr'])) : '' }}',
        mayaQr: '{{ $paymentSettings['maya_qr'] ? (str_starts_with($paymentSettings['maya_qr'], 'http') || str_starts_with($paymentSettings['maya_qr'], '/') ? $paymentSettings['maya_qr'] : asset('storage/' . $paymentSettings['maya_qr'])) : '' }}',
        submitting: false,
        copiedText: '',

        copyToClipboard(text, label) {
            if (!text) return;
            navigator.clipboard.writeText(text);
            this.copiedText = label;
            setTimeout(() => { this.copiedText = ''; }, 2500);
        },

        openPayModal() {
            this.payModal = true;
        },

        closePayModal() {
            this.payModal = false;
        },

        openProofModal(url, title, ref) {
            this.activeProofUrl = url;
            this.activeProofTitle = title || 'Receipt Proof';
            this.activeProofRef = ref || '';
            this.proofModal = true;
        },

        closeProofModal() {
            this.proofModal = false;
            this.activeProofUrl = '';
            this.activeProofTitle = '';
            this.activeProofRef = '';
        },

        onFileChange(e) {
            const file = e.target.files ? e.target.files[0] : null;
            if (!file) return;
            this.photoFile = file;
            const reader = new FileReader();
            reader.onload = (evt) => {
                this.photoPreview = evt.target.result;
            };
            reader.readAsDataURL(file);
        },

        removePhoto() {
            this.photoFile = null;
            this.photoPreview = null;
            const input = document.getElementById('commission-proof-input');
            if (input) input.value = '';
        }
    };
}
</script>
@endonce
@endsection
