@extends('layouts.app')

@section('content')
<div style="min-height:calc(100vh - 80px);background-color:#FAF8F5;" class="py-4 px-3 sm:py-8 sm:px-6"
     x-data="{ activeReportId: null }">

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
                    Click any report pill to inspect its full statement and investigation timeline.
                </p>
            </div>
        </div>

        {{-- Star Divider --}}
        <div class="relative my-4 sm:my-5 flex items-center justify-center">
            <div class="w-full border-t border-[#EAE1D0]"></div>
            <span class="absolute bg-[#FDFBF7] px-3 text-[#C49520] text-xs">✦</span>
        </div>

        {{-- Reports Pill List --}}
        <div class="space-y-2.5 sm:space-y-3">
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

                {{-- Compact Report Pill --}}
                <div @click="activeReportId = '{{ $report->id }}'"
                     class="p-3 sm:p-4 rounded-2xl bg-white border border-[#E8DECB] shadow-2xs hover:border-[#C49520] hover:shadow-md hover:bg-[#FAF8F5] transition-all cursor-pointer flex items-center justify-between gap-3 group">
                    
                    {{-- Left Details --}}
                    <div class="flex items-center gap-3 min-w-0">
                        {{-- Shield Medallion Icon --}}
                        <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl flex items-center justify-center shrink-0 border border-[#E6D8BA] bg-[#FAF5EA] text-[#B88728] group-hover:scale-105 group-hover:bg-[#1E1915] group-hover:text-[#DFC97A] group-hover:border-[#1E1915] transition-all">
                            <svg class="w-4.5 h-4.5 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                            </svg>
                        </div>

                        {{-- Code, Reason & Target --}}
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-1.5 flex-wrap">
                                <span class="font-mono text-[11px] sm:text-xs font-black text-[#1E1915] bg-[#FAF6EE] border border-[#E8DECB] px-2 py-0.5 rounded-md">
                                    {{ $report->getReportCode() }}
                                </span>
                                <span class="text-[9px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-md {{ $rType === 'product' ? 'bg-blue-50 text-blue-700 border border-blue-200' : 'bg-amber-50 text-amber-800 border border-amber-200' }}">
                                    {{ $rType === 'product' ? '📦 Product' : '👤 Account' }}
                                </span>
                            </div>

                            <div class="text-xs sm:text-sm font-bold text-[#1E1915] truncate mt-1 group-hover:text-[#996515] transition-colors">
                                {{ $report->reason }}
                            </div>

                            <div class="text-[11px] text-[#766C60] truncate mt-0.5">
                                @if($report->product)
                                    <span>Product: <strong>{{ $report->product->name }}</strong></span>
                                @elseif($report->reported)
                                    <span>Shop: <strong>{{ $report->reported->shopName ?: $report->reported->name }}</strong></span>
                                @endif
                                <span class="mx-1 text-[#D8CEBE]">&bull;</span>
                                <span>{{ $report->createdAt->format('M d, Y') }}</span>
                            </div>
                        </div>
                    </div>

                    {{-- Right Status Pill & Navigation Chevron --}}
                    <div class="flex items-center gap-2 shrink-0">
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-extrabold uppercase tracking-wider border {{ $statusConfig['class'] }}">
                            {{ $statusConfig['label'] }}
                        </span>
                        <svg class="w-4 h-4 text-[#8C827A] group-hover:text-[#1E1915] group-hover:translate-x-0.5 transition-all hidden sm:block" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                        </svg>
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

    {{-- Report Details Modal Popup --}}
    <div x-show="activeReportId !== null"
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4 bg-black/60 backdrop-blur-xs"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         @keydown.escape.window="activeReportId = null">

        <div class="relative w-full max-w-lg bg-[#FDFBF7] border border-[#EAE2D2] rounded-2xl sm:rounded-3xl p-5 sm:p-6 shadow-2xl max-h-[90vh] overflow-y-auto text-[#1E1915]"
             @click.away="activeReportId = null">

            @foreach($reports as $report)
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

                <div x-show="activeReportId === '{{ $report->id }}'" class="space-y-4">
                    {{-- Modal Header --}}
                    <div class="flex items-center justify-between pb-3 border-b border-[#EAE1D0]">
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 rounded-lg bg-[#1E1915] text-[#DFC97A] flex items-center justify-center shrink-0">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                </svg>
                            </div>
                            <div>
                                <span class="text-[9px] font-bold uppercase tracking-widest text-[#996515] block">Report Details</span>
                                <h3 class="font-mono text-xs sm:text-sm font-black text-[#1E1915] m-0">
                                    {{ $report->getReportCode() }}
                                </h3>
                            </div>
                        </div>
                        <button type="button"
                                @click="activeReportId = null"
                                class="w-8 h-8 rounded-lg bg-[#FAF6EE] border border-[#E2D9C8] text-[#78716C] hover:bg-[#1E1915] hover:text-[#DFC97A] flex items-center justify-center transition-all cursor-pointer">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    {{-- Badges & Status Row --}}
                    <div class="flex items-center justify-between gap-2 bg-white border border-[#E8DECB] p-3 rounded-xl shadow-2xs flex-wrap">
                        <div class="flex items-center gap-1.5">
                            <span class="text-[10px] font-bold uppercase tracking-wider px-2.5 py-0.5 rounded-md {{ $rType === 'product' ? 'bg-blue-50 text-blue-700 border border-blue-200' : 'bg-amber-50 text-amber-800 border border-amber-200' }}">
                                {{ $rType === 'product' ? '📦 Product Report' : '👤 Account Report' }}
                            </span>
                        </div>
                        <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-[10px] font-extrabold uppercase tracking-wider border {{ $statusConfig['class'] }}">
                            {{ $statusConfig['label'] }}
                        </span>
                    </div>

                    {{-- Reason & Customer Statement --}}
                    <div class="bg-white border border-[#E8DECB] p-4 rounded-xl shadow-2xs space-y-2">
                        <div class="text-xs font-bold uppercase tracking-wider text-[#A09585]">Reported Concern</div>
                        <div class="text-sm sm:text-base font-bold text-[#1E1915]">
                            {{ $report->reason }}
                        </div>

                        @if($report->description)
                            <div class="mt-3 p-3 rounded-xl bg-[#FAF8F5] border border-[#ECE3D2] text-xs text-[#59514A] leading-relaxed">
                                <span class="text-[9.5px] font-bold uppercase tracking-wider text-[#996515] block mb-1">Your Submitted Statement:</span>
                                <p class="m-0 wrap-break-word whitespace-pre-line">{{ $report->description }}</p>
                            </div>
                        @endif
                    </div>

                    {{-- Reported Subject --}}
                    <div class="bg-white border border-[#E8DECB] p-4 rounded-xl shadow-2xs space-y-1.5">
                        <div class="text-xs font-bold uppercase tracking-wider text-[#A09585]">Reported Target</div>
                        @if($report->product)
                            <div class="flex items-center gap-3 pt-1">
                                <img src="{{ $report->product->primary_image ?? '/uploads/products/default.jpg' }}"
                                     class="w-12 h-12 rounded-lg object-cover border border-[#E2D6C0] shrink-0"
                                     alt="{{ $report->product->name }}">
                                <div class="min-w-0 flex-1">
                                    <span class="text-xs font-bold text-[#1E1915] block truncate">{{ $report->product->name }}</span>
                                    <span class="text-[11px] text-[#766C60] block mt-0.5">Product ID: {{ $report->productId }}</span>
                                </div>
                            </div>
                        @elseif($report->reported)
                            <div class="flex items-center gap-2 pt-1 text-xs">
                                <div class="w-7 h-7 rounded-lg bg-[#FAF5EA] border border-[#E6D8BA] flex items-center justify-center text-[#B88728] shrink-0">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                                    </svg>
                                </div>
                                <div>
                                    <span class="text-[#766C60] block text-[10.5px]">Seller / Shop Name</span>
                                    <strong class="font-bold text-[#1E1915] text-xs">{{ $report->reported->shopName ?: $report->reported->name }}</strong>
                                </div>
                            </div>
                        @endif
                    </div>

                    {{-- Investigation Timeline --}}
                    <div class="bg-white border border-[#E8DECB] p-4 rounded-xl shadow-2xs space-y-3">
                        <div class="text-xs font-bold uppercase tracking-wider text-[#996515] flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5 text-[#C49520]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                <circle cx="12" cy="12" r="9"/>
                                <polyline points="12 6 12 12 16 14"/>
                            </svg>
                            <span>Investigation Timeline</span>
                        </div>

                        @if($report->timelineEvents->isNotEmpty())
                            <div class="space-y-3 relative pl-4 border-l-2 border-[#E6D8BA] ml-1.5">
                                @foreach($report->timelineEvents as $tEvent)
                                    <div class="relative text-xs">
                                        <div class="absolute -left-[21px] top-1 w-2.5 h-2.5 rounded-full bg-[#C49520] border-2 border-white shadow-2xs"></div>
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
                        @else
                            <div class="p-3 rounded-xl bg-[#FAF8F5] border border-[#ECE3D2] text-[11px] text-[#766C60] leading-relaxed">
                                Our Trust &amp; Safety moderators are currently reviewing this report. Any updates, seller responses, and resolutions will be recorded on this timeline.
                            </div>
                        @endif
                    </div>

                    {{-- Submission Date & Modal Action --}}
                    <div class="flex items-center justify-between pt-1 text-[10.5px] text-[#A09585]">
                        <span class="flex items-center gap-1">
                            <svg class="w-3 h-3 text-[#B88728] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/>
                            </svg>
                            Submitted: {{ $report->createdAt->format('M d, Y • g:i A') }}
                        </span>
                        <button type="button"
                                @click="activeReportId = null"
                                class="px-4 py-1.5 rounded-xl bg-[#1E1915] text-[#DFC97A] font-bold text-xs uppercase tracking-wider hover:bg-black transition-all cursor-pointer">
                            Close
                        </button>
                    </div>
                </div>
            @endforeach

        </div>
    </div>

</div>
@endsection
