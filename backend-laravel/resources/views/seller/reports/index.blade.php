@extends('layouts.seller')

@section('content')
<div class="space-y-5 sm:space-y-6 max-w-7xl pb-28 lg:pb-12" x-data="sellerReportsHub()">

    {{-- ══ HEADER ══════════════════════════════════════════════════════════ --}}
    <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 pb-4 border-b" style="border-color: #E8DECB;">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="text-[9px] font-extrabold uppercase tracking-[0.25em]" style="color: #C49520;">✦ Trust &amp; Safety</span>
                <span class="text-xs" style="color: #E8DECB;">•</span>
                <span class="text-[10px] font-semibold uppercase tracking-wider" style="color: #766C60;">Compliance &amp; Case Ledger</span>
            </div>
            <h1 class="font-serif text-2xl sm:text-3xl font-bold tracking-tight" style="color: #1E1915;">
                Reports &amp; <span class="italic font-normal" style="color: #766C60;">Concerns</span>
            </h1>
            <p class="text-xs font-medium mt-1" style="color: #766C60;">
                Review concerns filed regarding your shop or listings. Transparent case investigation and response hub.
            </p>
        </div>

        {{-- Search Bar --}}
        <div class="relative w-full sm:w-72 shrink-0">
            <input type="text" x-model="searchQuery" placeholder="Search case ID, reason, or topic..."
                class="w-full h-10 sm:h-11 pl-9 pr-4 rounded-xl text-xs font-semibold shadow-xs outline-none transition-all"
                style="background: #FDF8EE; border: 1px solid #E8DECB; color: #1E1915;"
                onfocus="this.style.borderColor='#C49520'; this.style.background='#FFF';"
                onblur="this.style.borderColor='#E8DECB'; this.style.background='#FDF8EE';">
            <svg class="w-4 h-4 absolute left-3 top-3 sm:top-3.5" style="color: #766C60;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
        </div>
    </div>

    {{-- ══ METRIC OVERVIEW CAPSULES (4-Pill Luxury Row) ════════════════════ --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5 sm:gap-4">
        {{-- Total Reports --}}
        <div class="p-3.5 sm:p-4 rounded-2xl transition-all" 
             style="background: #FFFFFF; border: 1px solid #ECE3D2; box-shadow: 0 1px 4px rgba(30,25,21,0.03);">
            <div class="flex items-center justify-between gap-1 mb-1">
                <span class="text-[9px] font-black uppercase tracking-widest" style="color: #766C60;">Total Reports</span>
                <span class="text-xs">📋</span>
            </div>
            <div class="text-xl sm:text-2xl font-black font-sans" style="color: #1E1915;">{{ $counts['total'] }}</div>
            <div class="text-[10px] font-medium mt-0.5" style="color: #9E9182;">All lifetime cases</div>
        </div>

        {{-- In Review --}}
        <div class="p-3.5 sm:p-4 rounded-2xl transition-all" 
             style="background: #FFFFFF; border: 1px solid #ECE3D2; box-shadow: 0 1px 4px rgba(30,25,21,0.03);">
            <div class="flex items-center justify-between gap-1 mb-1">
                <span class="text-[9px] font-black uppercase tracking-widest" style="color: #1D4ED8;">In Review</span>
                <span class="text-xs">🔍</span>
            </div>
            <div class="text-xl sm:text-2xl font-black font-sans" style="color: #1D4ED8;">{{ $counts['under_review'] }}</div>
            <div class="text-[10px] font-medium mt-0.5" style="color: #9E9182;">Active investigation</div>
        </div>

        {{-- Resolved --}}
        <div class="p-3.5 sm:p-4 rounded-2xl transition-all" 
             style="background: #FFFFFF; border: 1px solid #ECE3D2; box-shadow: 0 1px 4px rgba(30,25,21,0.03);">
            <div class="flex items-center justify-between gap-1 mb-1">
                <span class="text-[9px] font-black uppercase tracking-widest" style="color: #4A6741;">Resolved</span>
                <span class="text-xs">✅</span>
            </div>
            <div class="text-xl sm:text-2xl font-black font-sans" style="color: #4A6741;">{{ $counts['resolved'] }}</div>
            <div class="text-[10px] font-medium mt-0.5" style="color: #9E9182;">Closed cases</div>
        </div>

        {{-- Confirmed Violations --}}
        <div class="p-3.5 sm:p-4 rounded-2xl transition-all" 
             style="background: #FFFFFF; border: 1px solid #ECE3D2; box-shadow: 0 1px 4px rgba(30,25,21,0.03);">
            <div class="flex items-center justify-between gap-1 mb-1">
                <span class="text-[9px] font-black uppercase tracking-widest" style="color: {{ $counts['confirmed_violations'] > 0 ? '#DC2626' : '#766C60' }};">Violations</span>
                <span class="text-xs">🛡️</span>
            </div>
            <div class="text-xl sm:text-2xl font-black font-sans" style="color: {{ $counts['confirmed_violations'] > 0 ? '#DC2626' : '#4A6741' }};">
                {{ $counts['confirmed_violations'] }}
            </div>
            <div class="text-[10px] font-medium mt-0.5" style="color: #9E9182;">Confirmed strikes</div>
        </div>
    </div>

    {{-- ══ TRUST & SAFETY PRINCIPLE NOTICE (Sleek Collapsible Banner) ═════ --}}
    <div class="rounded-2xl p-4 sm:p-4.5 transition-all"
         style="background: #FDF8EE; border: 1px solid #E8DECB;">
        <div class="flex items-start sm:items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-xl flex items-center justify-center shrink-0 text-sm font-bold shadow-2xs"
                     style="background: #FFFFFF; border: 1px solid #E8DECB; color: #C49520;">
                    🛡️
                </div>
                <div>
                    <span class="text-xs font-bold text-[#1E1915]">Trust &amp; Safety Standard:</span>
                    <span class="text-xs text-[#766C60] ml-1">Submitted reports undergo neutral review before any policy assessment. Total reports ≠ violations.</span>
                </div>
            </div>
            <button type="button" @click="showNoticeDetails = !showNoticeDetails"
                    class="text-[11px] font-bold shrink-0 transition-colors cursor-pointer flex items-center gap-1"
                    style="color: #C49520;"
                    onmouseover="this.style.color='#A16D19';" onmouseout="this.style.color='#C49520';">
                <span x-text="showNoticeDetails ? 'Hide details ▴' : 'Learn more ▾'"></span>
            </button>
        </div>
        
        <div x-show="showNoticeDetails" x-collapse class="mt-3 pt-3 border-t text-xs leading-relaxed" style="border-color: #E8DECB; color: #766C60;">
            A submitted report only opens a case for review and investigation. Reports do not automatically restrict or penalize your shop. You may review the concern, inspect evidence, and submit an official seller response to assist the Trust &amp; Safety team. Only confirmed violations determined after fair investigation reflect on your account record.
        </div>
    </div>

    {{-- ══ STATUS TABS & TYPE FILTER (System Module Bar) ════════════════════ --}}
    <div class="space-y-3">
        {{-- Status Filter Tabs (Horizontal Scrollable Module Tabs) --}}
        <div class="overflow-x-auto no-scrollbar -mx-4 px-4 sm:mx-0 sm:px-0">
            <div class="flex items-center gap-2 border-b pb-3 min-w-max" style="border-color: #E8DECB;">
                @php
                    $statusTabs = [
                        'all'          => ['label' => 'All Reports', 'icon' => '📋', 'count' => $counts['total']],
                        'Under Review' => ['label' => 'Under Review', 'icon' => '🔍', 'count' => $counts['under_review']],
                        'Pending'      => ['label' => 'Pending', 'icon' => '⏳', 'count' => $counts['pending']],
                        'Resolved'     => ['label' => 'Resolved', 'icon' => '✅', 'count' => $counts['resolved']],
                        'Dismissed'    => ['label' => 'Dismissed', 'icon' => '—', 'count' => $counts['dismissed']],
                    ];
                @endphp
                @foreach($statusTabs as $val => $tab)
                    <button type="button" @click="statusFilter = '{{ $val }}'"
                            class="px-4 py-2.5 rounded-xl text-xs font-bold transition-all flex items-center gap-2 cursor-pointer font-sans shrink-0 active:scale-95 hover:border-[#C49520]"
                            :style="statusFilter === '{{ $val }}' 
                                ? 'background: #1E1915; color: #FFFCF7; box-shadow: 0 2px 8px rgba(30,25,21,0.12); border: 1px solid #1E1915;' 
                                : 'background: #FFFFFF; color: #6C6256; border: 1px solid #ECE3D2;'">
                        <span>{{ $tab['icon'] }} {{ $tab['label'] }}</span>
                        <span class="px-2 py-0.5 text-[10px] rounded-full font-bold transition-colors ml-0.5" 
                              :style="statusFilter === '{{ $val }}' ? 'background: rgba(196,149,32,0.28); color: #DFC97A;' : 'background: #F4EFE6; color: #766C60;'">
                            {{ $tab['count'] }}
                        </span>
                    </button>
                @endforeach
            </div>
        </div>

        {{-- Type Toggle Selector --}}
        <div class="flex items-center gap-1.5 overflow-x-auto no-scrollbar pt-0.5">
            <span class="text-[10px] font-black uppercase tracking-widest mr-1" style="color: #766C60;">Filter Type:</span>
            <button type="button" @click="typeFilter = 'all'"
                    class="px-3 py-1 rounded-lg text-[10px] font-extrabold uppercase tracking-wider transition-all shrink-0 cursor-pointer border"
                    :style="typeFilter === 'all' 
                        ? 'background: #1E1915; color: #FFFCF7; border-color: #1E1915;' 
                        : 'background: #FFFFFF; color: #766C60; border-color: #ECE3D2;'">
                All Types
            </button>
            <button type="button" @click="typeFilter = 'account'"
                    class="px-3 py-1 rounded-lg text-[10px] font-extrabold uppercase tracking-wider transition-all shrink-0 cursor-pointer border"
                    :style="typeFilter === 'account' 
                        ? 'background: #1E1915; color: #FFFCF7; border-color: #1E1915;' 
                        : 'background: #FFFFFF; color: #766C60; border-color: #ECE3D2;'">
                👤 Account Reports ({{ $counts['account_reports'] }})
            </button>
            <button type="button" @click="typeFilter = 'product'"
                    class="px-3 py-1 rounded-lg text-[10px] font-extrabold uppercase tracking-wider transition-all shrink-0 cursor-pointer border"
                    :style="typeFilter === 'product' 
                        ? 'background: #1E1915; color: #FFFCF7; border-color: #1E1915;' 
                        : 'background: #FFFFFF; color: #766C60; border-color: #ECE3D2;'">
                📦 Product Reports ({{ $counts['product_reports'] }})
            </button>
        </div>
    </div>

    {{-- ══ REPORTS CAPSULE LIST ═════════════════════════════════════════════ --}}
    @if($reports->isEmpty())
        <div class="py-16 sm:py-20 text-center rounded-3xl p-10 space-y-2 shadow-xs" style="background: #FFFCF7; border: 1px solid #E8DECB;">
            <div class="w-12 h-12 rounded-2xl flex items-center justify-center mx-auto text-xl" style="background: #FDF8EE; color: #C49520; border: 1px solid #E8DECB;">🛡️</div>
            <h3 class="font-serif text-sm font-bold uppercase tracking-wider" style="color: #1E1915;">No Reports Found</h3>
            <p class="text-[11px]" style="color: #766C60;">Your shop has a clean Trust &amp; Safety record. Keep delivering authentic creations to maintain it.</p>
        </div>
    @else
        <div class="space-y-3">
            @foreach($reports as $report)
                @php
                    $evidenceList = $report->getEvidenceList();
                    $evidenceCount = count($evidenceList);
                    $reportType = $report->reportType ?? (!empty($report->productId) ? 'product' : 'account');

                    $statusBadgeStyle = match($report->status) {
                        'Pending'      => 'background: #FFFBEB; color: #B45309; border: 1px solid #FDE68A;',
                        'Under Review' => 'background: #EFF6FF; color: #1D4ED8; border: 1px solid #BFDBFE;',
                        'Resolved'     => 'background: #F0F4EF; color: #4A6741; border: 1px solid #C5D9B8;',
                        'Dismissed'    => 'background: #F4EFE6; color: #766C60; border: 1px solid #E8DECB;',
                        default        => 'background: #FDF8EE; color: #766C60; border: 1px solid #E8DECB;',
                    };

                    $statusIcon = match($report->status) {
                        'Pending'      => '⏳',
                        'Under Review' => '🔍',
                        'Resolved'     => '✅',
                        'Dismissed'    => '—',
                        default        => '•',
                    };

                    $searchCorpus = strtolower($report->getReportCode() . ' ' . $report->reason . ' ' . $report->description . ' ' . ($report->product->name ?? ''));
                @endphp

                <div
                    x-show="(typeFilter === 'all' || typeFilter === '{{ $reportType }}') && (statusFilter === 'all' || statusFilter === '{{ addslashes($report->status) }}') && (!searchQuery || '{{ addslashes($searchCorpus) }}'.includes(searchQuery.toLowerCase().trim()))"
                    x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 translate-y-1"
                    x-transition:enter-end="opacity-100 translate-y-0"
                    class="rounded-3xl p-5 sm:p-6 transition-all hover:border-[#C49520]"
                    style="background: #FFFFFF; border: 1px solid #ECE3D2; box-shadow: 0 1px 4px rgba(30,25,21,0.04);">

                    {{-- Top Row: Code, Type Pill, Status Pill --}}
                    <div class="flex items-center justify-between gap-2 flex-wrap mb-2.5">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="px-2.5 py-1 rounded-xl text-xs font-mono font-black"
                                  style="background: #FDF8EE; border: 1px solid #E8DECB; color: #1E1915;">
                                #{{ $report->getReportCode() }}
                            </span>
                            <span class="px-2.5 py-0.5 rounded-full text-[9px] font-black uppercase tracking-wider"
                                  style="background: #F8F5F0; border: 1px solid #E8DECB; color: #766C60;">
                                {{ $reportType === 'product' ? '📦 Product' : '👤 Account' }}
                            </span>
                        </div>

                        <div class="flex items-center gap-2">
                            @if($report->sellerResponse)
                                <span class="px-2 py-0.5 rounded-full text-[9px] font-bold uppercase tracking-wider"
                                      style="background: #FAF5EA; color: #A16D19; border: 1px solid #E8DECB;">
                                    ✓ Responded
                                </span>
                            @endif
                            <span class="px-3 py-1 rounded-full text-[9px] font-black uppercase tracking-wider flex items-center gap-1 shrink-0"
                                  style="{{ $statusBadgeStyle }}">
                                <span>{{ $statusIcon }}</span>
                                <span>{{ $report->status }}</span>
                            </span>
                        </div>
                    </div>

                    {{-- Title / Reason --}}
                    <h3 class="font-sans text-sm sm:text-base font-extrabold tracking-tight" style="color: #1E1915;">
                        {{ $report->reason }}
                    </h3>

                    {{-- Description snippet --}}
                    @if($report->description)
                        <p class="text-xs font-medium leading-relaxed line-clamp-2 mt-1" style="color: #766C60;">
                            {{ $report->description }}
                        </p>
                    @endif

                    {{-- Product Preview (if product report) --}}
                    @if($report->product)
                        <div class="flex items-center gap-3 p-3 rounded-2xl mt-3 max-w-md"
                             style="background: #FDF8EE; border: 1px solid #E8DECB;">
                            <img src="{{ $report->product->primary_image ?? '/uploads/products/default.jpg' }}" 
                                 class="w-12 h-12 rounded-xl object-cover shrink-0"
                                 style="border: 1px solid #E8DECB;">
                            <div class="min-w-0 flex-1">
                                <div class="text-xs font-bold truncate" style="color: #1E1915;">{{ $report->product->name }}</div>
                                <div class="text-xs font-black mt-0.5" style="color: #C49520;">₱{{ number_format($report->product->price, 2) }}</div>
                            </div>
                        </div>
                    @endif

                    {{-- Bottom Row: Meta (Reporter, Evidence, Date) & View Case Button --}}
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mt-4 pt-3.5 border-t" style="border-color: #F4EFE6;">
                        <div class="flex items-center gap-2.5 flex-wrap text-xs font-medium" style="color: #766C60;">
                            <div class="flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5" style="color: #9E9182;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                <span>Anonymous Customer</span>
                            </div>
                            <span style="color: #E8DECB;">•</span>
                            <div class="flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5 {{ $evidenceCount > 0 ? 'text-[#C49520]' : 'text-gray-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                                <span class="{{ $evidenceCount > 0 ? 'font-bold' : '' }}" style="{{ $evidenceCount > 0 ? 'color: #A16D19;' : '' }}">
                                    {{ $evidenceCount > 0 ? $evidenceCount . ' Evidence File' . ($evidenceCount > 1 ? 's' : '') : 'No Evidence' }}
                                </span>
                            </div>
                            <span style="color: #E8DECB;">•</span>
                            <div class="text-[11px]" style="color: #766C60;">
                                {{ $report->createdAt ? $report->createdAt->format('M d, Y') : '' }}
                            </div>
                        </div>

                        <button type="button" @click="openCaseModal('{{ $report->id }}')"
                                class="px-5 py-2 rounded-xl text-xs font-black uppercase tracking-wider text-white shadow-xs transition-all cursor-pointer flex items-center justify-center gap-1.5 shrink-0 self-end sm:self-auto"
                                style="background: #1E1915;"
                                onmouseover="this.style.background='#C49520';" onmouseout="this.style.background='#1E1915';">
                            <span>View Case</span>
                            <span class="text-xs" style="color: #DFC97A;">➔</span>
                        </button>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    {{-- ══ DETAILED CASE MODAL ══════════════════════════════════════════════ --}}
    <div x-show="showCaseModal"
         x-cloak
         class="fixed inset-0 z-1000 flex items-center justify-center p-3 sm:p-6"
         style="display: none;"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         @keydown.escape.window="showCaseModal = false">

        <!-- Backdrop with Solid Blur -->
        <div @click="showCaseModal = false" class="fixed inset-0 bg-black/70 backdrop-blur-sm"></div>

        <!-- Case Modal Dialog Box -->
        <div class="relative w-full max-w-3xl rounded-3xl sm:rounded-4xl shadow-2xl overflow-hidden max-h-[90vh] flex flex-col z-10"
             style="background-color: #FFFFFF !important; color: #1E1915 !important; border: 1px solid #ECE3D2;"
             @click.stop>
            
            <!-- Modal Header -->
            <div class="px-6 py-5 sm:px-8 sm:py-6 border-b flex items-center justify-between shrink-0"
                 style="background-color: #FAF7F2 !important; border-color: #E8DECB;">
                <div class="flex items-center gap-3.5">
                    <div class="w-11 h-11 rounded-2xl flex items-center justify-center shrink-0 shadow-xs" style="background: #1E1915; color: #C49520;">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-mono font-black px-2 py-0.5 rounded-lg" 
                                  style="background: #FFFFFF; border: 1px solid #E8DECB; color: #1E1915;"
                                  x-text="activeCase.reportCode"></span>
                            <span class="text-[9px] font-extrabold uppercase tracking-wider px-2.5 py-0.5 rounded-full border"
                                  :class="activeCase.status === 'Resolved' ? 'bg-emerald-50 text-emerald-800 border-emerald-200' : (activeCase.status === 'Under Review' ? 'bg-blue-50 text-blue-800 border-blue-200' : 'bg-amber-50 text-amber-800 border-amber-200')"
                                  x-text="activeCase.status"></span>
                        </div>
                        <h3 class="font-serif text-lg sm:text-xl font-bold tracking-tight mt-0.5" style="color: #1E1915;" x-text="activeCase.reason"></h3>
                    </div>
                </div>

                <button type="button" @click="showCaseModal = false" class="w-9 h-9 rounded-xl flex items-center justify-center transition-colors cursor-pointer" style="color: #766C60; background: #F4EFE6;" onmouseover="this.style.background='#E8DECB';" onmouseout="this.style.background='#F4EFE6';" title="Close">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <!-- Modal Body Scrollable -->
            <div class="p-6 sm:p-8 overflow-y-auto flex-1 space-y-6" style="background-color: #FFFFFF !important;">

                <!-- 1. Report Information Card -->
                <div class="p-4 sm:p-5 rounded-2xl space-y-3" style="background-color: #FAF7F2 !important; border: 1px solid #E8DECB;">
                    <div class="text-[10px] font-black uppercase tracking-widest" style="color: #766C60;">Report Information</div>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs">
                        <div>
                            <span class="text-[10px] font-bold block uppercase" style="color: #9E9182;">Report Type</span>
                            <span class="font-black capitalize text-xs" style="color: #1E1915;" x-text="activeCase.reportType + ' Report'"></span>
                        </div>
                        <div>
                            <span class="text-[10px] font-bold block uppercase" style="color: #9E9182;">Severity</span>
                            <span class="font-black uppercase text-xs" :class="activeCase.severity === 'CRITICAL' ? 'text-red-600' : (activeCase.severity === 'HIGH' ? 'text-orange-600' : 'text-gray-900')" x-text="activeCase.severity"></span>
                        </div>
                        <div>
                            <span class="text-[10px] font-bold block uppercase" style="color: #9E9182;">Date Submitted</span>
                            <span class="font-bold text-xs" style="color: #1E1915;" x-text="activeCase.formattedDate"></span>
                        </div>
                        <div>
                            <span class="text-[10px] font-bold block uppercase" style="color: #9E9182;">Reporter</span>
                            <span class="font-bold text-xs" style="color: #766C60;">Anonymous Customer</span>
                        </div>
                    </div>
                </div>

                <!-- 2. Customer Concern Statement -->
                <div class="space-y-2">
                    <label class="text-[10px] font-black uppercase tracking-widest block" style="color: #766C60;">Customer Concern Description</label>
                    <div class="p-4 sm:p-5 rounded-2xl text-xs sm:text-sm leading-relaxed shadow-2xs" 
                         style="background: #FAF8F5; border: 1px solid #ECE3D2; color: #1E1915;" 
                         x-text="activeCase.description"></div>
                </div>

                <!-- 3. Submitted Evidence Gallery (In-Modal Lightbox) -->
                <template x-if="activeCase.evidence && activeCase.evidence.length > 0">
                    <div class="space-y-2">
                        <label class="text-[10px] font-black uppercase tracking-widest block" style="color: #766C60;">Submitted Evidence Gallery</label>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                            <template x-for="(imgUrl, idx) in activeCase.evidence" :key="idx">
                                <div class="relative group rounded-2xl overflow-hidden aspect-square bg-gray-50 shadow-2xs cursor-pointer"
                                     style="border: 1px solid #ECE3D2;"
                                     @click="lightboxUrl = imgUrl; showLightbox = true">
                                    <img :src="imgUrl" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                                    <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center text-white text-[10px] font-bold uppercase tracking-wider">
                                        🔍 Zoom Photo
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>

                <!-- 4. Case Investigation Timeline (Real Database Events) -->
                <div class="space-y-3 p-4 sm:p-5 rounded-2xl" style="background-color: #FAF7F2 !important; border: 1px solid #E8DECB;">
                    <label class="text-[10px] font-black uppercase tracking-widest block" style="color: #766C60;">Case Investigation Timeline</label>
                    <div class="space-y-3 relative pl-4 ml-2" style="border-left: 2px solid rgba(196,149,32,0.4);">
                        <template x-for="event in activeCase.timeline" :key="event.id">
                            <div class="relative space-y-0.5">
                                <div class="absolute top-1.5 w-2.5 h-2.5 rounded-full" style="background: #C49520; border: 2px solid #FFFFFF; left: -21px;"></div>
                                <div class="flex items-center justify-between gap-2">
                                    <span class="text-xs font-bold" style="color: #1E1915;" x-text="event.title"></span>
                                    <span class="text-[10px] font-semibold" style="color: #9E9182;" x-text="event.date"></span>
                                </div>
                                <p class="text-xs leading-relaxed" style="color: #766C60;" x-text="event.description"></p>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- 5. Seller Response Section -->
                <div class="p-4 sm:p-5 rounded-2xl space-y-3 shadow-2xs" style="background: #FFFFFF; border: 1px solid #ECE3D2;">
                    <div class="flex items-center justify-between">
                        <label class="text-[10px] font-black uppercase tracking-widest block" style="color: #766C60;">Your Official Response</label>
                        <template x-if="activeCase.sellerResponse">
                            <span class="text-[9px] font-bold uppercase tracking-wider px-2.5 py-0.5 rounded-full"
                                  style="background: #FAF5EA; color: #A16D19; border: 1px solid #E8DECB;">
                                Submitted on <span x-text="activeCase.sellerRespondedAt"></span>
                            </span>
                        </template>
                    </div>

                    <!-- Already Submitted Response -->
                    <template x-if="activeCase.sellerResponse">
                        <div class="space-y-3">
                            <p class="text-xs leading-relaxed p-4 rounded-xl" 
                               style="background: #FAF8F5; border: 1px solid #ECE3D2; color: #1E1915;" 
                               x-text="activeCase.sellerResponse"></p>
                            
                            <!-- Seller Attached Evidence -->
                            <template x-if="activeCase.sellerResponseEvidence && activeCase.sellerResponseEvidence.length > 0">
                                <div class="grid grid-cols-3 sm:grid-cols-4 gap-2.5">
                                    <template x-for="(sImg, sIdx) in activeCase.sellerResponseEvidence" :key="sIdx">
                                        <div class="rounded-xl overflow-hidden aspect-square bg-gray-50 cursor-pointer"
                                             style="border: 1px solid #ECE3D2;"
                                             @click="lightboxUrl = sImg; showLightbox = true">
                                            <img :src="sImg" class="w-full h-full object-cover">
                                        </div>
                                    </template>
                                </div>
                            </template>
                        </div>
                    </template>

                    <!-- Response Form (If not responded and case is active) -->
                    <template x-if="!activeCase.sellerResponse && activeCase.status !== 'Resolved' && activeCase.status !== 'Dismissed'">
                        <form @submit.prevent="submitResponse(activeCase.id)" class="space-y-3">
                            <p class="text-xs leading-relaxed" style="color: #766C60;">
                                You may provide additional information, context, or evidence regarding this concern. Your response will become an official part of the case record for Trust &amp; Safety review.
                            </p>
                            <textarea 
                                x-model="sellerResponseText" 
                                rows="4" 
                                required
                                class="w-full p-3.5 rounded-xl outline-none text-xs font-medium transition-all resize-none shadow-2xs"
                                style="background: #FAF8F5; border: 1px solid #ECE3D2; color: #1E1915;"
                                onfocus="this.style.borderColor='#C49520'; this.style.background='#FFF';"
                                onblur="this.style.borderColor='#ECE3D2'; this.style.background='#FAF8F5';"
                                placeholder="Enter your response, clarification, or resolution proposal (minimum 5 characters)..."></textarea>

                            <div class="flex items-center justify-between gap-3 pt-1">
                                <label class="cursor-pointer text-xs font-bold hover:underline flex items-center gap-1.5" style="color: #C49520;">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                                    <span>Attach Evidence Photo</span>
                                    <input type="file" accept="image/*" @change="uploadSellerEvidence($event)" class="hidden">
                                </label>

                                <button type="submit"
                                        :disabled="isSubmittingResponse || sellerResponseText.trim().length < 5"
                                        class="px-5 py-2.5 rounded-xl text-xs font-bold uppercase tracking-widest text-white shadow-md transition-all cursor-pointer disabled:opacity-50"
                                        style="background: #1E1915;"
                                        onmouseover="this.style.background='#C49520';" onmouseout="this.style.background='#1E1915';">
                                    <span x-show="!isSubmittingResponse">Submit Response</span>
                                    <span x-show="isSubmittingResponse">Submitting...</span>
                                </button>
                            </div>

                            <!-- Attached Seller Evidence Previews -->
                            <template x-if="sellerEvidenceFiles.length > 0">
                                <div class="flex items-center gap-2 pt-2">
                                    <template x-for="(sFile, sIdx) in sellerEvidenceFiles" :key="sIdx">
                                        <div class="relative w-12 h-12 rounded-lg overflow-hidden border border-gray-200">
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
                    <div class="p-4 sm:p-5 rounded-2xl space-y-2" style="background: #F0F4EF; border: 1px solid #C5D9B8;">
                        <div class="text-[10px] font-black uppercase tracking-widest" style="color: #4A6741;">Trust &amp; Safety Determination</div>
                        <div class="text-xs font-bold" style="color: #1E1915;" x-text="'Investigation Result: ' + (activeCase.investigationResult || 'No Violation Found')"></div>
                        <div class="text-xs" style="color: #766C60;" x-text="'Action Taken: ' + (activeCase.actionTaken || 'None')"></div>
                        <template x-if="activeCase.disciplinaryReason">
                            <p class="text-xs mt-1 p-3 rounded-xl" style="background: #FFFFFF; border: 1px solid #C5D9B8; color: #1E1915;" x-text="activeCase.disciplinaryReason"></p>
                        </template>
                    </div>
                </template>

            </div>

            <!-- Modal Footer -->
            <div class="px-6 py-4 sm:px-8 sm:py-5 border-t flex items-center justify-end shrink-0"
                 style="background-color: #FAF7F2 !important; border-color: #E8DECB;">
                <button type="button" @click="showCaseModal = false" 
                        class="px-6 py-2.5 rounded-full text-xs font-bold uppercase tracking-widest transition-colors cursor-pointer shadow-2xs"
                        style="background: #FFFFFF; border: 1px solid #E8DECB; color: #766C60;"
                        onmouseover="this.style.background='#FDF8EE'; this.style.color='#1E1915';"
                        onmouseout="this.style.background='#FFFFFF'; this.style.color='#766C60';">
                    Close Case Window
                </button>
            </div>

        </div>
    </div>

    {{-- ══ LIGHTBOX IMAGE MODAL ═════════════════════════════════════════════ --}}
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
        searchQuery: '',
        showNoticeDetails: false,
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
