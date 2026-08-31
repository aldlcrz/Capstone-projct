@extends('layouts.seller')

@section('content')
<div class="space-y-6 sm:space-y-8 pb-28 lg:pb-12" x-data="sellerReportsHub()">

    {{-- ══ HEADER ══════════════════════════════════════════════════════════ --}}
    <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 pb-2 border-b" style="border-color: #E8DECB;">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="text-[9px] font-extrabold uppercase tracking-[0.25em]" style="color: #C49520;">🛡️ Trust &amp; Safety</span>
                <span class="text-xs" style="color: #E8DECB;">•</span>
                <span class="text-[10px] font-semibold tracking-wider uppercase" style="color: #766C60;">Account &amp; Product Concerns</span>
            </div>
            <h1 class="font-serif text-2xl sm:text-3xl lg:text-4xl font-bold tracking-tight" style="color: #1E1915;">
                Reports &amp; <span class="italic font-normal" style="color: #766C60;">Concerns</span>
            </h1>
            <p class="text-xs sm:text-sm font-medium mt-1" style="color: #766C60;">
                Review concerns filed regarding your shop or listings. Transparent case investigation and response hub.
            </p>
        </div>

        {{-- Summary Metric Badges --}}
        <div class="flex items-center gap-2 flex-wrap shrink-0">
            <div class="px-3.5 py-2 rounded-xl text-center shadow-xs" style="background: #FDF8EE; border: 1px solid #E8DECB;">
                <div class="text-[8px] font-bold uppercase tracking-widest" style="color: #766C60;">Total Reports</div>
                <div class="text-lg font-black font-sans" style="color: #1E1915;">{{ $counts['total'] }}</div>
            </div>
            <div class="px-3.5 py-2 rounded-xl text-center shadow-xs" style="background: #FEF9EE; border: 1px solid #F6DFA0;">
                <div class="text-[8px] font-bold uppercase tracking-widest" style="color: #A16D19;">Pending</div>
                <div class="text-lg font-black font-sans" style="color: #C49520;">{{ $counts['pending'] }}</div>
            </div>
            <div class="px-3.5 py-2 rounded-xl text-center shadow-xs" style="background: #EEF3FE; border: 1px solid #B8CEFA;">
                <div class="text-[8px] font-bold uppercase tracking-widest" style="color: #2E5FCA;">Under Review</div>
                <div class="text-lg font-black font-sans" style="color: #2E5FCA;">{{ $counts['under_review'] }}</div>
            </div>
            <div class="px-3.5 py-2 rounded-xl text-center shadow-xs" style="background: #F0F4EF; border: 1px solid #C5D9B8;">
                <div class="text-[8px] font-bold uppercase tracking-widest" style="color: #4A6741;">Resolved</div>
                <div class="text-lg font-black font-sans" style="color: #4A6741;">{{ $counts['resolved'] }}</div>
            </div>
            <div class="px-3.5 py-2 rounded-xl text-center shadow-xs" style="background: #FFFDF9; border: 1px solid #E8DECB;">
                <div class="text-[8px] font-bold uppercase tracking-widest" style="color: #A09585;">Confirmed Violations</div>
                <div class="text-lg font-black font-sans {{ $counts['confirmed_violations'] > 0 ? 'text-red-600' : 'text-emerald-700' }}">
                    {{ $counts['confirmed_violations'] }}
                </div>
            </div>
        </div>
    </div>

    {{-- ══ PRINCIPLE NOTICE BANNER ══════════════════════════════════════════ --}}
    <div class="flex items-start gap-3.5 p-4 sm:p-5 rounded-2xl sm:rounded-3xl shadow-2xs" style="background: #FEF9EE; border: 1px solid #F6DFA0;">
        <div class="w-9 h-9 rounded-2xl flex items-center justify-center shrink-0 shadow-xs" style="background: #FDF8EE; border: 1px solid #E8DECB; color: #C49520;">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
        <div class="flex-1 min-w-0">
            <div class="text-xs font-black uppercase tracking-wider" style="color: #A16D19;">
                Important Business Principle: Total Reports ≠ Policy Violations
            </div>
            <p class="text-[11px] sm:text-xs mt-0.5 leading-relaxed" style="color: #766C60;">
                A submitted report only opens a case for investigation. Reports do not automatically restrict or penalize your shop. You may review the concern, inspect evidence, and submit an official seller response to assist the Trust &amp; Safety team. Only confirmed violations determined after investigation reflect on your account record.
            </p>
        </div>
    </div>

    {{-- ══ FILTERS & SEARCH BAR ════════════════════════════════════════════ --}}
    <div class="space-y-3 p-4 sm:p-5 rounded-2xl sm:rounded-3xl shadow-xs" style="background: #FFFCF7; border: 1px solid #E8DECB;">
        
        <!-- Report Type Tabs -->
        <div class="flex items-center gap-2 overflow-x-auto no-scrollbar pb-1 border-b" style="border-color: #F0EAD8;">
            <span class="text-[9px] font-black uppercase tracking-widest text-[#A09585] shrink-0 mr-1">Type:</span>
            <button @click="typeFilter = 'all'"
                :style="typeFilter === 'all' ? 'background:#1E1915; color:#FFFCF7; border:1px solid #C49520;' : 'background:#FDF8EE; color:#1E1915; border:1px solid #E8DECB;'"
                class="px-3.5 py-1.5 rounded-xl text-[10px] font-extrabold uppercase tracking-wider transition-all shrink-0 cursor-pointer shadow-2xs">
                All Reports ({{ $counts['total'] }})
            </button>
            <button @click="typeFilter = 'account'"
                :style="typeFilter === 'account' ? 'background:#1E1915; color:#FFFCF7; border:1px solid #C49520;' : 'background:#FDF8EE; color:#1E1915; border:1px solid #E8DECB;'"
                class="px-3.5 py-1.5 rounded-xl text-[10px] font-extrabold uppercase tracking-wider transition-all shrink-0 cursor-pointer shadow-2xs">
                👤 Account Reports ({{ $counts['account_reports'] }})
            </button>
            <button @click="typeFilter = 'product'"
                :style="typeFilter === 'product' ? 'background:#1E1915; color:#FFFCF7; border:1px solid #C49520;' : 'background:#FDF8EE; color:#1E1915; border:1px solid #E8DECB;'"
                class="px-3.5 py-1.5 rounded-xl text-[10px] font-extrabold uppercase tracking-wider transition-all shrink-0 cursor-pointer shadow-2xs">
                📦 Product Reports ({{ $counts['product_reports'] }})
            </button>
        </div>

        <!-- Status Filter Pills -->
        <div class="flex items-center gap-2 overflow-x-auto no-scrollbar pt-1">
            <span class="text-[9px] font-black uppercase tracking-widest text-[#A09585] shrink-0 mr-1">Status:</span>
            @php
                $statusFilters = [
                    'all'          => 'All Statuses (' . $counts['total'] . ')',
                    'Pending'      => '⏳ Pending (' . $counts['pending'] . ')',
                    'Under Review' => '🔍 Under Review (' . $counts['under_review'] . ')',
                    'Resolved'     => '✓ Resolved (' . $counts['resolved'] . ')',
                    'Dismissed'    => '— Dismissed (' . $counts['dismissed'] . ')',
                ];
            @endphp
            @foreach($statusFilters as $val => $label)
                <button @click="statusFilter = '{{ $val }}'"
                    :style="statusFilter === '{{ $val }}' ? 'background:#C49520; color:#FFF; border:1px solid #C49520;' : 'background:#FDF8EE; color:#766C60; border:1px solid #E8DECB;'"
                    class="px-3 py-1 rounded-lg text-[9px] font-extrabold uppercase tracking-wider transition-all shrink-0 cursor-pointer">
                    {{ $label }}
                </button>
            @endforeach
        </div>
    </div>

    {{-- ══ REPORTS LIST ═════════════════════════════════════════════════════ --}}
    @if($reports->isEmpty())
        <div class="py-20 text-center rounded-3xl" style="background: #FFFCF7; border: 2px dashed #E8DECB;">
            <div class="w-14 h-14 rounded-2xl mx-auto mb-4 flex items-center justify-center text-2xl" style="background: #FDF8EE; border: 1px solid #E8DECB;">
                🛡️
            </div>
            <h3 class="font-serif text-lg font-bold mb-1" style="color: #1E1915;">No reports filed against your shop</h3>
            <p class="text-xs max-w-xs mx-auto" style="color: #766C60;">
                Your shop has a clean Trust &amp; Safety record. Keep delivering authentic, high-quality Lumban artisan creations to maintain it.
            </p>
        </div>
    @else
        <div class="space-y-3.5 sm:space-y-4">
            @foreach($reports as $report)
                @php
                    $statusConfig = match($report->status) {
                        'Pending'      => ['bg' => '#FEF9EE', 'border' => '#F6DFA0', 'text' => '#A16D19', 'dot' => '#C49520', 'label' => 'PENDING'],
                        'Under Review' => ['bg' => '#EEF3FE', 'border' => '#B8CEFA', 'text' => '#2E5FCA', 'dot' => '#4A7BFF', 'label' => 'UNDER REVIEW'],
                        'Resolved'     => ['bg' => '#F0F4EF', 'border' => '#C5D9B8', 'text' => '#4A6741', 'dot' => '#4A6741', 'label' => 'RESOLVED'],
                        'Dismissed'    => ['bg' => '#F7F4EC', 'border' => '#E0D9CC', 'text' => '#766C60', 'dot' => '#A09585', 'label' => 'DISMISSED'],
                        default        => ['bg' => '#FDF8EE', 'border' => '#E8DECB', 'text' => '#766C60', 'dot' => '#A09585', 'label' => strtoupper($report->status)],
                    };

                    $sevConfig = match(strtoupper($report->severity ?? 'MEDIUM')) {
                        'CRITICAL' => ['bg' => '#FDF2F2', 'border' => '#F8B4B4', 'text' => '#9B1C1C', 'label' => 'CRITICAL'],
                        'HIGH'     => ['bg' => '#FDF4E8', 'border' => '#FBD5A5', 'text' => '#B45309', 'label' => 'HIGH'],
                        'MEDIUM'   => ['bg' => '#FEF9EE', 'border' => '#F6DFA0', 'text' => '#A16D19', 'label' => 'MEDIUM'],
                        default    => ['bg' => '#F0F4EF', 'border' => '#C5D9B8', 'text' => '#4A6741', 'label' => 'LOW'],
                    };

                    $evidenceList = $report->getEvidenceList();
                    $evidenceCount = count($evidenceList);
                    $reportType = $report->reportType ?? (!empty($report->productId) ? 'product' : 'account');
                @endphp

                <div
                    x-show="(typeFilter === 'all' || typeFilter === '{{ $reportType }}') && (statusFilter === 'all' || statusFilter === '{{ addslashes($report->status) }}')"
                    x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 translate-y-1"
                    x-transition:enter-end="opacity-100 translate-y-0"
                    class="rounded-2xl sm:rounded-3xl shadow-xs overflow-hidden transition-all hover:border-[#C49520]"
                    style="background: #FFFCF7; border: 1px solid #E8DECB;">

                    {{-- Card Header & Body --}}
                    <div class="p-4 sm:p-6">
                        <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4">
                            
                            <div class="flex-1 min-w-0 space-y-2">
                                {{-- Badges Row --}}
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="text-[10px] font-black uppercase tracking-wider font-mono text-[#1E1915]">
                                        {{ $report->getReportCode() }}
                                    </span>
                                    
                                    {{-- Report Type Badge --}}
                                    <span class="text-[8px] font-black uppercase tracking-wider px-2 py-0.5 rounded-full"
                                          style="{{ $reportType === 'product' ? 'background: #EEF3FE; color: #2E5FCA; border: 1px solid #B8CEFA;' : 'background: #FAF5EA; color: #766C60; border: 1px solid #E8DECB;' }}">
                                        {{ $reportType === 'product' ? '📦 PRODUCT REPORT' : '👤 ACCOUNT REPORT' }}
                                    </span>

                                    {{-- Status Badge --}}
                                    <span class="text-[8px] font-black uppercase tracking-wider px-2.5 py-0.5 rounded-full"
                                          style="background: {{ $statusConfig['bg'] }}; color: {{ $statusConfig['text'] }}; border: 1px solid {{ $statusConfig['border'] }};">
                                        {{ $statusConfig['label'] }}
                                    </span>

                                    {{-- Severity Badge --}}
                                    <span class="text-[8px] font-extrabold uppercase tracking-wider px-2 py-0.5 rounded-md"
                                          style="background: {{ $sevConfig['bg'] }}; color: {{ $sevConfig['text'] }}; border: 1px solid {{ $sevConfig['border'] }};">
                                        Severity: {{ $sevConfig['label'] }}
                                    </span>

                                    @if($report->sellerResponse)
                                        <span class="text-[8px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-full bg-purple-50 text-purple-700 border border-purple-200">
                                            ✓ Responded
                                        </span>
                                    @endif
                                </div>

                                {{-- Reason --}}
                                <h3 class="text-base font-bold tracking-tight text-[#1E1915]">
                                    {{ $report->reason }}
                                </h3>

                                {{-- Short Description --}}
                                <p class="text-xs leading-relaxed line-clamp-2 text-[#766C60]">
                                    {{ $report->description }}
                                </p>

                                {{-- Product Preview (if product report) --}}
                                @if($report->product)
                                    <div class="flex items-center gap-2.5 p-2 rounded-xl bg-[#FAF5EA] border border-[#E8DECB] max-w-md">
                                        <img src="{{ $report->product->primary_image ?? '/uploads/products/default.jpg' }}" class="w-8 h-8 rounded-lg object-cover border border-[#E8DECB]">
                                        <div class="min-w-0 flex-1">
                                            <div class="text-[10px] font-bold text-[#1E1915] truncate">{{ $report->product->name }}</div>
                                            <div class="text-[9px] text-[#C49520] font-bold">₱{{ number_format($report->product->price, 2) }}</div>
                                        </div>
                                    </div>
                                @endif
                            </div>

                            {{-- Right Meta & Action Button --}}
                            <div class="flex sm:flex-col items-center sm:items-end justify-between sm:justify-start gap-2 shrink-0 border-t sm:border-t-0 pt-3 sm:pt-0 border-[#F0EAD8]">
                                <div class="text-left sm:text-right">
                                    <div class="text-[10px] font-semibold text-[#A09585]">
                                        {{ $report->createdAt ? $report->createdAt->format('M d, Y') : '—' }}
                                    </div>
                                    <div class="text-[9px] text-[#C0B090]">
                                        {{ $report->createdAt ? $report->createdAt->diffForHumans() : '' }}
                                    </div>
                                </div>

                                <button @click="openCaseModal('{{ $report->id }}')"
                                        class="px-4 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest text-white shadow-xs hover:bg-black transition-all cursor-pointer flex items-center gap-1.5"
                                        style="background: #1E1915;">
                                    <span>View Case</span>
                                    <svg class="w-3 h-3 text-[#C49520]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
                                </button>
                            </div>
                        </div>

                        {{-- Card Footer Details --}}
                        <div class="flex items-center gap-3 mt-4 pt-3 border-t flex-wrap text-[10px] font-medium text-[#766C60]" style="border-color: #F0EAD8;">
                            <div class="flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5 text-[#A09585]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                <span>Anonymous Customer</span>
                            </div>
                            <span class="text-[#E8DECB]">•</span>
                            <div class="flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5 {{ $evidenceCount > 0 ? 'text-[#C49520]' : 'text-[#A09585]' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                                <span class="{{ $evidenceCount > 0 ? 'font-bold text-[#C49520]' : '' }}">
                                    {{ $evidenceCount > 0 ? $evidenceCount . ' Evidence File' . ($evidenceCount > 1 ? 's' : '') : 'No Evidence' }}
                                </span>
                            </div>
                            <span class="text-[#E8DECB]">•</span>
                            <div>
                                Last updated: {{ $report->updatedAt ? $report->updatedAt->format('M d, Y') : '—' }}
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    {{-- ══ DETAILED CASE MODAL ══════════════════════════════════════════════ --}}
    <div x-show="showCaseModal"
         x-cloak
         class="fixed inset-0 z-1000 flex items-center justify-center p-3 sm:p-4"
         style="display: none;"
         x-transition:enter="transition ease-out duration-250"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         @keydown.escape.window="showCaseModal = false">

        <!-- Backdrop -->
        <div @click="showCaseModal = false" class="absolute inset-0 bg-black/60 backdrop-blur-xs"></div>

        <!-- Case Modal Window -->
        <div class="relative w-full max-w-3xl bg-[#FFFCF7] rounded-3xl sm:rounded-4xl shadow-2xl overflow-hidden border border-[#E8DECB] max-h-[90vh] flex flex-col"
             @click.stop>
            
            <!-- Modal Header -->
            <div class="px-6 py-5 sm:px-8 sm:py-6 border-b border-[#E8DECB] bg-[#FDF8EE] flex items-center justify-between shrink-0">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl flex items-center justify-center shrink-0 shadow-xs" style="background: #1E1915; color: #C49520;">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="text-[10px] font-black uppercase tracking-wider font-mono text-[#1E1915]" x-text="activeCase.reportCode"></span>
                            <span class="text-[8px] font-extrabold uppercase tracking-wider px-2 py-0.5 rounded-full"
                                  :class="activeCase.status === 'Resolved' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-amber-50 text-amber-700 border border-amber-200'"
                                  x-text="activeCase.status"></span>
                        </div>
                        <h3 class="font-serif text-lg sm:text-xl font-bold tracking-tight text-[#1E1915]" x-text="activeCase.reason"></h3>
                    </div>
                </div>

                <button type="button" @click="showCaseModal = false" class="w-8 h-8 rounded-xl flex items-center justify-center text-[#766C60] hover:text-[#1E1915] hover:bg-[#E8DECB]/40 transition-colors cursor-pointer">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <!-- Modal Body Scrollable -->
            <div class="p-6 sm:p-8 overflow-y-auto flex-1 space-y-6">

                <!-- 1. Report Information -->
                <div class="p-4 sm:p-5 rounded-2xl bg-[#FAF5EA] border border-[#E8DECB] space-y-3">
                    <div class="text-[9px] font-black uppercase tracking-widest text-[#A09585]">Report Information</div>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs">
                        <div>
                            <span class="text-[9px] font-semibold text-[#766C60] block">Report Type</span>
                            <span class="font-bold text-[#1E1915] capitalize" x-text="activeCase.reportType + ' Report'"></span>
                        </div>
                        <div>
                            <span class="text-[9px] font-semibold text-[#766C60] block">Severity</span>
                            <span class="font-bold uppercase" :class="activeCase.severity === 'CRITICAL' ? 'text-red-600' : (activeCase.severity === 'HIGH' ? 'text-amber-600' : 'text-[#1E1915]')" x-text="activeCase.severity"></span>
                        </div>
                        <div>
                            <span class="text-[9px] font-semibold text-[#766C60] block">Date Submitted</span>
                            <span class="font-bold text-[#1E1915]" x-text="activeCase.formattedDate"></span>
                        </div>
                        <div>
                            <span class="text-[9px] font-semibold text-[#766C60] block">Reporter</span>
                            <span class="font-bold text-[#766C60]">Anonymous Customer</span>
                        </div>
                    </div>
                </div>

                <!-- 2. Customer Concern -->
                <div class="space-y-2">
                    <div class="text-[9px] font-black uppercase tracking-widest text-[#A09585]">Customer Concern Description</div>
                    <div class="p-4 rounded-2xl bg-white border border-[#E8DECB] text-xs leading-relaxed text-[#1E1915]" x-text="activeCase.description"></div>
                </div>

                <!-- 3. Uploaded Evidence Gallery (In-Modal Lightbox) -->
                <template x-if="activeCase.evidence && activeCase.evidence.length > 0">
                    <div class="space-y-2">
                        <div class="text-[9px] font-black uppercase tracking-widest text-[#A09585]">Submitted Evidence Gallery</div>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                            <template x-for="(imgUrl, idx) in activeCase.evidence" :key="idx">
                                <div class="relative group rounded-2xl overflow-hidden border border-[#E8DECB] aspect-square bg-white shadow-2xs cursor-pointer"
                                     @click="lightboxUrl = imgUrl; showLightbox = true">
                                    <img :src="imgUrl" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                                    <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center text-white text-[9px] font-bold uppercase tracking-wider">
                                        🔍 Preview
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>

                <!-- 4. Case Timeline (Real Database Events) -->
                <div class="space-y-3 p-4 sm:p-5 rounded-2xl bg-[#FAF5EA] border border-[#E8DECB]">
                    <div class="text-[9px] font-black uppercase tracking-widest text-[#A09585]">Case Investigation Timeline</div>
                    <div class="space-y-3 relative pl-4 border-l-2 border-[#C49520]/40 ml-2">
                        <template x-for="event in activeCase.timeline" :key="event.id">
                            <div class="relative space-y-0.5">
                                <div class="absolute -left-[21px] top-1.5 w-2.5 h-2.5 rounded-full bg-[#C49520] border-2 border-white"></div>
                                <div class="flex items-center justify-between gap-2">
                                    <span class="text-xs font-bold text-[#1E1915]" x-text="event.title"></span>
                                    <span class="text-[9px] font-semibold text-[#A09585]" x-text="event.date"></span>
                                </div>
                                <p class="text-[11px] text-[#766C60] leading-relaxed" x-text="event.description"></p>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- 5. Seller Response Section -->
                <div class="p-4 sm:p-5 rounded-2xl bg-white border border-[#E8DECB] space-y-3">
                    <div class="flex items-center justify-between">
                        <div class="text-[9px] font-black uppercase tracking-widest text-[#A09585]">Your Official Response</div>
                        <template x-if="activeCase.sellerResponse">
                            <span class="text-[8px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200">
                                Submitted on <span x-text="activeCase.sellerRespondedAt"></span>
                            </span>
                        </template>
                    </div>

                    <!-- Already Submitted Response -->
                    <template x-if="activeCase.sellerResponse">
                        <div class="space-y-3">
                            <p class="text-xs leading-relaxed p-3.5 rounded-xl bg-[#FAF5EA] border border-[#E8DECB] text-[#1E1915]" x-text="activeCase.sellerResponse"></p>
                            
                            <!-- Seller Attached Evidence -->
                            <template x-if="activeCase.sellerResponseEvidence && activeCase.sellerResponseEvidence.length > 0">
                                <div class="grid grid-cols-3 sm:grid-cols-4 gap-2.5">
                                    <template x-for="(sImg, sIdx) in activeCase.sellerResponseEvidence" :key="sIdx">
                                        <div class="rounded-xl overflow-hidden border border-[#E8DECB] aspect-square bg-white cursor-pointer"
                                             @click="lightboxUrl = sImg; showLightbox = true">
                                            <img :src="sImg" class="w-full h-full object-cover">
                                        </div>
                                    </template>
                                </div>
                            </template>
                        </div>
                    </template>

                    <!-- Response Form (If not responded or open) -->
                    <template x-if="!activeCase.sellerResponse && activeCase.status !== 'Resolved' && activeCase.status !== 'Dismissed'">
                        <form @submit.prevent="submitResponse(activeCase.id)" class="space-y-3">
                            <p class="text-[11px] text-[#766C60] leading-relaxed">
                                You may provide additional information, context, or evidence regarding this concern. Your response will become an official part of the case record for Trust &amp; Safety review.
                            </p>
                            <textarea 
                                x-model="sellerResponseText" 
                                rows="4" 
                                required
                                class="w-full p-3.5 bg-[#FAF5EA] border border-[#E8DECB] focus:border-[#C49520] rounded-xl outline-none text-xs font-medium text-[#1E1915] transition-all resize-none shadow-2xs"
                                placeholder="Enter your response, clarification, or resolution proposal (minimum 5 characters)..."></textarea>

                            <div class="flex items-center justify-between gap-3 pt-1">
                                <label class="cursor-pointer text-[10px] font-bold text-[#C49520] hover:underline flex items-center gap-1">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                                    <span>Attach Evidence Photo</span>
                                    <input type="file" accept="image/*" @change="uploadSellerEvidence($event)" class="hidden">
                                </label>

                                <button type="submit"
                                        :disabled="isSubmittingResponse || sellerResponseText.trim().length < 5"
                                        class="px-5 py-2.5 rounded-xl text-xs font-bold uppercase tracking-widest text-white shadow-md transition-all cursor-pointer disabled:opacity-50"
                                        style="background: #1E1915;">
                                    <span x-show="!isSubmittingResponse">Submit Response</span>
                                    <span x-show="isSubmittingResponse">Submitting...</span>
                                </button>
                            </div>

                            <!-- Attached Seller Evidence Previews -->
                            <template x-if="sellerEvidenceFiles.length > 0">
                                <div class="flex items-center gap-2 pt-2">
                                    <template x-for="(sFile, sIdx) in sellerEvidenceFiles" :key="sIdx">
                                        <div class="relative w-12 h-12 rounded-lg overflow-hidden border border-[#E8DECB]">
                                            <img :src="sFile" class="w-full h-full object-cover">
                                            <button type="button" @click="sellerEvidenceFiles.splice(sIdx, 1)" class="absolute top-0.5 right-0.5 w-4 h-4 rounded-full bg-black/70 text-white text-[8px] flex items-center justify-center">✕</button>
                                        </div>
                                    </template>
                                </div>
                            </template>
                        </form>
                    </template>
                </div>

                <!-- 6. Admin Determination & Resolution Notes (if available) -->
                <template x-if="activeCase.investigationResult || activeCase.actionTaken">
                    <div class="p-4 sm:p-5 rounded-2xl bg-[#F0F4EF] border border-[#C5D9B8] space-y-2">
                        <div class="text-[9px] font-black uppercase tracking-widest text-[#4A6741]">Trust &amp; Safety Determination</div>
                        <div class="text-xs font-bold text-[#1E1915]" x-text="'Investigation Result: ' + (activeCase.investigationResult || 'No Violation Found')"></div>
                        <div class="text-xs text-[#766C60]" x-text="'Action Taken: ' + (activeCase.actionTaken || 'None')"></div>
                        <template x-if="activeCase.disciplinaryReason">
                            <p class="text-xs mt-1 text-[#1E1915] p-2.5 bg-white rounded-xl border border-[#C5D9B8]" x-text="activeCase.disciplinaryReason"></p>
                        </template>
                    </div>
                </template>

            </div>

            <!-- Modal Footer -->
            <div class="px-6 py-4 sm:px-8 sm:py-5 bg-[#FDF8EE] border-t border-[#E8DECB] flex items-center justify-end shrink-0">
                <button type="button" @click="showCaseModal = false" class="px-6 py-2.5 rounded-xl text-xs font-bold uppercase tracking-widest text-[#766C60] bg-white border border-[#E8DECB] hover:bg-[#FAF5EA] transition-colors cursor-pointer">
                    Close Case View
                </button>
            </div>
        </div>
    </div>

    {{-- ══ LIGHTBOX MODAL (For evidence viewer without new browser tab) ══ --}}
    <div x-show="showLightbox"
         x-cloak
         class="fixed inset-0 z-2000 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm"
         style="display: none;"
         @click="showLightbox = false"
         @keydown.escape.window="showLightbox = false">
        <div class="relative max-w-4xl max-h-[90vh] bg-transparent flex flex-col items-center" @click.stop>
            <img :src="lightboxUrl" class="max-w-full max-h-[80vh] rounded-2xl shadow-2xl object-contain border-2 border-white/20">
            <button type="button" @click="showLightbox = false" class="mt-4 px-5 py-2 rounded-xl text-xs font-bold uppercase tracking-widest bg-white/20 text-white hover:bg-white/40 backdrop-blur-md cursor-pointer">
                Close Preview (ESC)
            </button>
        </div>
    </div>

</div>

<script>
function sellerReportsHub() {
    return {
        typeFilter: 'all',
        statusFilter: 'all',
        showCaseModal: false,
        showLightbox: false,
        lightboxUrl: '',
        activeCase: {},
        sellerResponseText: '',
        sellerEvidenceFiles: [],
        isSubmittingResponse: false,

        async openCaseModal(reportId) {
            try {
                const res = await fetch(`/api/v1/seller/reports/${reportId}`);
                if (res.ok) {
                    this.activeCase = await res.json();
                    this.sellerResponseText = '';
                    this.sellerEvidenceFiles = [];
                    this.showCaseModal = true;
                } else {
                    alert('Could not load case details. Please try again.');
                }
            } catch (e) {
                alert('Network error while loading case details.');
            }
        },

        async uploadSellerEvidence(event) {
            const file = event.target.files[0];
            if (!file) return;

            if (file.size > 10 * 1024 * 1024) {
                alert('File size exceeds 10MB limit.');
                return;
            }

            const formData = new FormData();
            formData.append('image', file);

            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') 
                || document.querySelector('input[name="_token"]')?.value 
                || '';

            try {
                const res = await fetch('/api/v1/upload', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                    body: formData
                });

                if (res.ok) {
                    const data = await res.json();
                    if (data.url) this.sellerEvidenceFiles.push(data.url);
                } else {
                    alert('Failed to upload evidence image.');
                }
            } catch (e) {
                alert('Network error during upload.');
            } finally {
                event.target.value = '';
            }
        },

        async submitResponse(reportId) {
            if (this.sellerResponseText.trim().length < 5) return;
            this.isSubmittingResponse = true;

            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') 
                || document.querySelector('input[name="_token"]')?.value 
                || '';

            try {
                const res = await fetch(`/api/v1/reports/${reportId}/response`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({
                        response: this.sellerResponseText,
                        evidence: this.sellerEvidenceFiles
                    })
                });

                const data = await res.json();
                if (res.ok && data.status === 'success') {
                    // Reload active case details
                    await this.openCaseModal(reportId);
                    alert('Your response has been successfully recorded in the case file.');
                } else {
                    alert(data.message || 'Failed to submit response.');
                }
            } catch (e) {
                alert('Network error while submitting response.');
            } finally {
                this.isSubmittingResponse = false;
            }
        }
    };
}
</script>
@endsection
