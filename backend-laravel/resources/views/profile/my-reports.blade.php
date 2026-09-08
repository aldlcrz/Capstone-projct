@extends('layouts.app')

@section('content')
<div style="min-height:calc(100vh - 80px);background-color:#FAF8F5;" class="py-4 px-3 sm:py-8 sm:px-6">
    <div class="w-full max-w-3xl mx-auto bg-[#FDFBF7] border border-[#EAE2D2] rounded-2xl sm:rounded-[28px] shadow-[0_12px_40px_rgba(0,0,0,0.05)] p-4 sm:p-7 text-[#1E1915]">

        {{-- Top Navigation Bar --}}
        <div class="flex items-center justify-between gap-3 pb-3 sm:pb-4 border-b border-[#EAE1D0]">
            <a href="{{ route('profile') }}"
               class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-[#FAF6EE] border border-[#E2D9C8] text-[#78716C] hover:bg-[#1E1915] hover:text-[#DFC97A] hover:border-[#1E1915] font-bold text-[11px] uppercase tracking-wider transition-all shadow-2xs no-underline">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                <span>Back to Profile</span>
            </a>
            <span class="text-[9.5px] font-black uppercase tracking-widest text-[#996515]">Trust &amp; Safety</span>
        </div>

        {{-- Header Section --}}
        <div class="flex items-center gap-3.5 pt-3 sm:pt-4">
            <div class="w-10 h-10 sm:w-11 sm:h-11 rounded-2xl flex items-center justify-center shrink-0 shadow-xs bg-[#1E1915] text-[#C49520]">
                <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                </svg>
            </div>
            <div class="min-w-0">
                <h1 class="font-serif text-lg sm:text-2xl font-bold tracking-tight text-[#1E1915] m-0 leading-tight">
                    My Submitted Reports
                </h1>
                <p class="text-xs text-[#78716C] mt-1 mb-0 leading-normal">
                    Track the live review status and resolution timeline of your reports.
                </p>
            </div>
        </div>

        {{-- Star Divider --}}
        <div class="relative my-4 sm:my-5 flex items-center justify-center">
            <div class="w-full border-t border-[#EAE1D0]"></div>
            <span class="absolute bg-[#FDFBF7] px-3 text-[#C49520] text-xs">✦</span>
        </div>

        {{-- Reports List --}}
        <div class="space-y-3.5 sm:space-y-4">
            @forelse($reports as $report)
                @php
                    $statusConfig = match($report->status) {
                        'Pending'      => ['class' => 'bg-[#FEF9EE] border-[#F6DFA0] text-[#A16D19]', 'label' => '⏳ Pending Review'],
                        'Under Review' => ['class' => 'bg-[#EEF3FE] border-[#B8CEFA] text-[#2E5FCA]', 'label' => '🔍 Under Investigation'],
                        'Resolved'     => ['class' => 'bg-[#F0F4EF] border-[#C5D9B8] text-[#4A6741]', 'label' => '✓ Resolved'],
                        'Dismissed'    => ['class' => 'bg-[#F7F4EC] border-[#E0D9CC] text-[#766C60]', 'label' => '— Dismissed'],
                        default        => ['class' => 'bg-[#FDF8EE] border-[#E8DECB] text-[#766C60]', 'label' => $report->status],
                    };
                    $rType = $report->reportType ?? (!empty($report->productId) ? 'product' : 'account');
                @endphp

                <div class="p-3.5 sm:p-5 rounded-2xl bg-white border border-[#E8DECB] shadow-xs hover:border-[#C49520] transition-all space-y-3">
                    {{-- Card Header: Code & Type on left, Compact Pill on right (Never stretched full width!) --}}
                    <div class="flex items-center justify-between gap-2 flex-wrap sm:flex-nowrap">
                        <div class="flex items-center gap-1.5">
                            <span class="font-mono text-xs font-black text-[#1E1915] bg-[#FAF6EE] border border-[#E8DECB] px-2 py-0.5 rounded-md">
                                {{ $report->getReportCode() }}
                            </span>
                            <span class="text-[9px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-md {{ $rType === 'product' ? 'bg-blue-50 text-blue-700 border border-blue-200' : 'bg-amber-50 text-amber-800 border border-amber-200' }}">
                                {{ $rType === 'product' ? '📦 Product' : '👤 Account' }}
                            </span>
                        </div>
                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase tracking-wider shrink-0 border {{ $statusConfig['class'] }}">
                            {{ $statusConfig['label'] }}
                        </span>
                    </div>

                    {{-- Reason & Customer Description Box --}}
                    <div>
                        <div class="text-sm sm:text-base font-bold text-[#1E1915]">
                            {{ $report->reason }}
                        </div>
                        @if($report->description)
                            <div class="mt-2 p-2.5 sm:p-3 rounded-xl bg-[#FAF8F5] border border-[#ECE3D2] text-xs text-[#59514A] leading-relaxed">
                                <span class="text-[9px] font-bold uppercase tracking-wider text-[#A09585] block mb-0.5">Your Statement:</span>
                                <p class="m-0 wrap-break-word whitespace-pre-line">{{ $report->description }}</p>
                            </div>
                        @endif
                    </div>

                    {{-- Target Preview (Shop or Product) --}}
                    @if($report->product)
                        <div class="flex items-center gap-3 p-2.5 rounded-xl bg-[#FAF5EA] border border-[#E8DECB]">
                            <img src="{{ $report->product->primary_image ?? '/uploads/products/default.jpg' }}"
                                 class="w-10 h-10 rounded-lg object-cover border border-[#E2D6C0] shrink-0"
                                 alt="{{ $report->product->name }}">
                            <div class="min-w-0 flex-1">
                                <span class="text-[9px] font-bold uppercase tracking-wider text-[#A09585] block leading-tight">Reported Product</span>
                                <span class="text-xs font-bold text-[#1E1915] truncate block mt-0.5">{{ $report->product->name }}</span>
                            </div>
                        </div>
                    @elseif($report->reported)
                        <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-xl bg-[#FAF5EA] border border-[#E8DECB] text-xs max-w-full">
                            <svg class="w-3.5 h-3.5 text-[#B88728] shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                            </svg>
                            <span class="text-[#766C60] font-medium shrink-0">Reported Shop:</span>
                            <strong class="font-bold text-[#1E1915] truncate">{{ $report->reported->shopName ?: $report->reported->name }}</strong>
                        </div>
                    @endif

                    {{-- Investigation Timeline --}}
                    @if($report->timelineEvents->isNotEmpty())
                        <div class="pt-2.5 border-t border-[#F0EAD8]">
                            <div class="text-[10px] font-black uppercase tracking-wider text-[#996515] mb-2 flex items-center gap-1.5">
                                <svg class="w-3 h-3 text-[#C49520]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                    <circle cx="12" cy="12" r="9"/>
                                    <polyline points="12 6 12 12 16 14"/>
                                </svg>
                                <span>Investigation Timeline</span>
                            </div>
                            <div class="space-y-2 relative pl-3.5 border-l-2 border-[#E6D8BA] ml-1.5">
                                @foreach($report->timelineEvents as $tEvent)
                                    <div class="relative text-xs">
                                        <div class="absolute -left-4.5 top-1 w-2 h-2 rounded-full bg-[#C49520] border-2 border-white shadow-2xs"></div>
                                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-0.5 sm:gap-2">
                                            <span class="font-bold text-[#1E1915] text-xs leading-snug">{{ $tEvent->title }}</span>
                                            <span class="text-[10px] text-[#8C827A] shrink-0">{{ $tEvent->created_at->format('M d, Y • g:i A') }}</span>
                                        </div>
                                        @if($tEvent->description)
                                            <p class="text-[11px] text-[#766C60] mt-0.5 mb-0 leading-relaxed">{{ $tEvent->description }}</p>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- Submitted Date Footer --}}
                    <div class="flex items-center gap-1.5 text-[10px] text-[#A09585] pt-1 border-t border-[#FAF5EA]">
                        <svg class="w-3 h-3 text-[#B88728] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <circle cx="12" cy="12" r="10"/>
                            <path d="M12 6v6l4 2"/>
                        </svg>
                        <span>Submitted on {{ $report->createdAt->format('F j, Y \a\t g:i A') }}</span>
                    </div>
                </div>
            @empty
                <div class="py-12 sm:py-16 text-center rounded-2xl border-2 border-dashed border-[#E8DECB] bg-white p-6">
                    <div class="text-3xl mb-2">🛡️</div>
                    <h3 class="font-serif text-base sm:text-lg font-bold text-[#1E1915] m-0">No Reports Filed</h3>
                    <p class="text-xs text-[#766C60] mt-1.5 max-w-sm mx-auto leading-relaxed">
                        You have not submitted any trust &amp; safety reports. When you report concerns regarding shops or products, they will appear here with live tracking.
                    </p>
                </div>
            @endforelse
        </div>

        {{-- Pagination --}}
        @if($reports->hasPages())
            <div class="mt-6">
                {{ $reports->links() }}
            </div>
        @endif

    </div>
</div>
@endsection
