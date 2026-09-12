@extends('layouts.admin')

@section('content')
<div class="space-y-6" x-data="adminReportsManagement()">
    
    {{-- ═══ PAGE HEADER + SEARCH BAR ═══ --}}
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
        {{-- Left: Title & Subtitle --}}
        <div class="text-left space-y-0.5 shrink-0">
            <div class="inline-flex items-center gap-2">
                <span class="text-[9px] font-black uppercase tracking-[0.25em] text-[#C0422A]">Compliance &amp; Reports</span>
                <span class="text-gray-300 text-xs">·</span>
                <span class="text-[9px] font-bold uppercase tracking-[0.2em] text-gray-400">System Governance</span>
            </div>
            <h1 class="font-serif text-2xl sm:text-3xl font-bold text-gray-900 leading-tight">
                Compliance &amp; <span class="text-[#C0420A] font-light italic">Reports</span>
            </h1>
            <p class="text-[11px] text-gray-400 font-medium">Review consumer inquiries, investigate reported violations, and enforce marketplace trust &amp; safety standards</p>
        </div>

        {{-- Larger Search Bar matching Archive Hub --}}
        <div class="flex-1 max-w-xl lg:max-w-2xl w-full">
            <form method="GET" action="{{ route('admin.reports') }}" class="flex items-center gap-2 w-full">
                @if(request('status') && request('status') !== 'Pending')
                    <input type="hidden" name="status" value="{{ request('status') }}">
                @endif

                <div class="relative w-full">
                    <input type="text" name="search" value="{{ request('search') }}"
                           placeholder="Search case ID, reason, seller, customer, or product..."
                           class="w-full pl-11 pr-5 py-2.5 bg-white border border-gray-200 rounded-full text-sm text-gray-800 placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-[#C0422A]/20 focus:border-[#C0422A] shadow-xs transition-all">
                    <svg class="w-5 h-5 text-gray-400 absolute left-4 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
                @if(request('search'))
                    <a href="{{ request()->fullUrlWithQuery(['search' => null, 'page' => 1]) }}" 
                       class="px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-full text-xs font-bold transition-all shrink-0">
                        Clear
                    </a>
                @endif
            </form>
        </div>
    </div>

    {{-- ═══ STATS BAR / STATUS FILTERS (Unified Interactive Cards matching Archive Hub) ═══ --}}
    @php
        $currentStatus = request('status', 'Pending');
        $isAll         = $currentStatus === 'all';
        $isPending     = $currentStatus === 'Pending';
        $isUnderReview = $currentStatus === 'Under Review';
        $isResolved    = $currentStatus === 'Resolved';
        $isDismissed   = $currentStatus === 'Dismissed';
    @endphp
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3">
        {{-- Total / All --}}
        <a href="{{ request()->fullUrlWithQuery(['status' => 'all', 'page' => 1]) }}"
           class="group relative rounded-2xl px-3.5 py-3 flex items-center justify-between border transition-all duration-200 cursor-pointer {{ $isAll ? 'bg-white border-gray-900 ring-2 ring-gray-900/15 shadow-sm -translate-y-0.5' : 'bg-white border-gray-100 hover:border-gray-300 hover:shadow-sm hover:-translate-y-0.5' }}">
            <div class="flex items-center gap-2.5 min-w-0">
                <div class="w-8 h-8 rounded-xl flex items-center justify-center shrink-0 transition-colors {{ $isAll ? 'bg-gray-900 text-white' : 'bg-gray-100 text-gray-500 group-hover:bg-gray-200' }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
                <div class="min-w-0">
                    <div class="text-base sm:text-lg font-black text-gray-900 leading-none">{{ $counts['all'] ?? 0 }}</div>
                    <div class="text-[9px] font-bold uppercase tracking-wider text-gray-400 mt-0.5 truncate">Total Logged</div>
                </div>
            </div>
            @if($isAll)
                <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-full text-[8px] font-black uppercase tracking-wider bg-gray-900 text-white shadow-xs shrink-0">
                    <span class="w-1 h-1 rounded-full bg-emerald-400"></span> Active
                </span>
            @endif
        </a>

        {{-- Pending Review --}}
        <a href="{{ request()->fullUrlWithQuery(['status' => 'Pending', 'page' => 1]) }}"
           class="group relative rounded-2xl px-3.5 py-3 flex items-center justify-between border transition-all duration-200 cursor-pointer {{ $isPending ? 'bg-amber-50/50 border-amber-500 ring-2 ring-amber-500/20 shadow-sm -translate-y-0.5' : 'bg-white border-amber-100 hover:border-amber-300 hover:shadow-sm hover:-translate-y-0.5' }}">
            <div class="flex items-center gap-2.5 min-w-0">
                <div class="w-8 h-8 rounded-xl flex items-center justify-center shrink-0 transition-colors {{ $isPending ? 'bg-amber-500 text-white' : 'bg-amber-50 text-amber-600 group-hover:bg-amber-100' }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div class="min-w-0">
                    <div class="text-base sm:text-lg font-black text-gray-900 leading-none">{{ $counts['pending'] ?? 0 }}</div>
                    <div class="text-[9px] font-bold uppercase tracking-wider text-amber-500 mt-0.5 truncate">Pending Review</div>
                </div>
            </div>
            @if($isPending)
                <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-full text-[8px] font-black uppercase tracking-wider bg-amber-500 text-white shadow-xs shrink-0">
                    <span class="w-1 h-1 rounded-full bg-amber-200"></span> Active
                </span>
            @endif
        </a>

        {{-- In Investigation (Under Review) --}}
        <a href="{{ request()->fullUrlWithQuery(['status' => 'Under Review', 'page' => 1]) }}"
           class="group relative rounded-2xl px-3.5 py-3 flex items-center justify-between border transition-all duration-200 cursor-pointer {{ $isUnderReview ? 'bg-sky-50/50 border-sky-600 ring-2 ring-sky-600/20 shadow-sm -translate-y-0.5' : 'bg-white border-sky-100 hover:border-sky-300 hover:shadow-sm hover:-translate-y-0.5' }}">
            <div class="flex items-center gap-2.5 min-w-0">
                <div class="w-8 h-8 rounded-xl flex items-center justify-center shrink-0 transition-colors {{ $isUnderReview ? 'bg-sky-600 text-white' : 'bg-sky-50 text-sky-600 group-hover:bg-sky-100' }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
                <div class="min-w-0">
                    <div class="text-base sm:text-lg font-black text-gray-900 leading-none">{{ $counts['under_review'] ?? 0 }}</div>
                    <div class="text-[9px] font-bold uppercase tracking-wider text-sky-500 mt-0.5 truncate">In Investigation</div>
                </div>
            </div>
            @if($isUnderReview)
                <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-full text-[8px] font-black uppercase tracking-wider bg-sky-600 text-white shadow-xs shrink-0">
                    <span class="w-1 h-1 rounded-full bg-sky-200"></span> Active
                </span>
            @endif
        </a>

        {{-- Resolved Cases --}}
        <a href="{{ request()->fullUrlWithQuery(['status' => 'Resolved', 'page' => 1]) }}"
           class="group relative rounded-2xl px-3.5 py-3 flex items-center justify-between border transition-all duration-200 cursor-pointer {{ $isResolved ? 'bg-emerald-50/50 border-emerald-600 ring-2 ring-emerald-600/20 shadow-sm -translate-y-0.5' : 'bg-white border-emerald-100 hover:border-emerald-300 hover:shadow-sm hover:-translate-y-0.5' }}">
            <div class="flex items-center gap-2.5 min-w-0">
                <div class="w-8 h-8 rounded-xl flex items-center justify-center shrink-0 transition-colors {{ $isResolved ? 'bg-emerald-600 text-white' : 'bg-emerald-50 text-emerald-600 group-hover:bg-emerald-100' }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div class="min-w-0">
                    <div class="text-base sm:text-lg font-black text-gray-900 leading-none">{{ $counts['resolved'] ?? 0 }}</div>
                    <div class="text-[9px] font-bold uppercase tracking-wider text-emerald-500 mt-0.5 truncate">Resolved Cases</div>
                </div>
            </div>
            @if($isResolved)
                <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-full text-[8px] font-black uppercase tracking-wider bg-emerald-600 text-white shadow-xs shrink-0">
                    <span class="w-1 h-1 rounded-full bg-emerald-200"></span> Active
                </span>
            @endif
        </a>

        {{-- Dismissed --}}
        <a href="{{ request()->fullUrlWithQuery(['status' => 'Dismissed', 'page' => 1]) }}"
           class="group relative rounded-2xl px-3.5 py-3 flex items-center justify-between border transition-all duration-200 cursor-pointer {{ $isDismissed ? 'bg-purple-50/50 border-purple-600 ring-2 ring-purple-600/20 shadow-sm -translate-y-0.5' : 'bg-white border-purple-100 hover:border-purple-300 hover:shadow-sm hover:-translate-y-0.5' }}">
            <div class="flex items-center gap-2.5 min-w-0">
                <div class="w-8 h-8 rounded-xl flex items-center justify-center shrink-0 transition-colors {{ $isDismissed ? 'bg-purple-600 text-white' : 'bg-purple-50 text-purple-600 group-hover:bg-purple-100' }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                    </svg>
                </div>
                <div class="min-w-0">
                    <div class="text-base sm:text-lg font-black text-gray-900 leading-none">{{ $counts['dismissed'] ?? 0 }}</div>
                    <div class="text-[9px] font-bold uppercase tracking-wider text-purple-500 mt-0.5 truncate">Dismissed</div>
                </div>
            </div>
            @if($isDismissed)
                <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-full text-[8px] font-black uppercase tracking-wider bg-purple-600 text-white shadow-xs shrink-0">
                    <span class="w-1 h-1 rounded-full bg-purple-200"></span> Active
                </span>
            @endif
        </a>
    </div>

    {{-- ═══ RESULTS COUNT BAR (Matching Archive Hub) ═══ --}}
    <div class="flex items-center justify-between px-1">
        <div class="text-[11px] font-bold text-gray-400">
            Showing <span class="text-gray-900 font-black">{{ $reports->total() }}</span> {{ $reports->total() === 1 ? 'report record' : 'report records' }}
            <span class="text-gray-400">· Filtered by <span class="capitalize font-black text-gray-700">{{ $currentStatus === 'all' ? 'All Status' : $currentStatus }}</span></span>
            @if(request('search'))
                <span class="text-gray-400">· Matching "<span class="font-bold text-gray-700">{{ request('search') }}</span>"</span>
            @endif
        </div>
    </div>

    {{-- ══ SELLER RISK PATTERN OVERVIEW (DECISION-SUPPORT ANALYTICS) ══ --}}
    @if(isset($topReportedSellers) && count($topReportedSellers) > 0)
    <div class="bg-white border border-gray-100 rounded-2xl p-5 sm:p-6 shadow-sm space-y-4">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-xl bg-orange-50 border border-orange-100 flex items-center justify-center text-[#C0422A]">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-xs font-black uppercase tracking-widest text-gray-900">Seller Risk Pattern Overview</h3>
                    <p class="text-[10px] text-gray-400 font-medium">Algorithmic risk evaluation for admin decision support</p>
                </div>
            </div>
            <span class="text-[9px] font-bold uppercase tracking-wider text-gray-500 bg-gray-50 px-3 py-1 rounded-xl border border-gray-200/80">
                Last 30 Days Activity
            </span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
            @foreach($topReportedSellers as $item)
                @php
                    $riskBadge = match($item['risk_level']) {
                        'CRITICAL' => 'bg-red-50 text-red-700 border-red-200/80',
                        'HIGH'     => 'bg-orange-50 text-orange-700 border-orange-200/80',
                        'MEDIUM'   => 'bg-amber-50 text-amber-800 border-amber-200/80',
                        default    => 'bg-emerald-50 text-emerald-700 border-emerald-200/80',
                    };
                    $riskBorder = match($item['risk_level']) {
                        'CRITICAL' => 'border-l-4 border-l-red-500',
                        'HIGH'     => 'border-l-4 border-l-orange-500',
                        'MEDIUM'   => 'border-l-4 border-l-amber-500',
                        default    => 'border-l-4 border-l-emerald-500',
                    };
                @endphp
                <div class="p-4 rounded-2xl bg-gray-50/70 border border-gray-200/70 {{ $riskBorder }} space-y-2.5 shadow-2xs hover:shadow-xs transition-all">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0 flex-1">
                            <div class="text-xs font-bold text-gray-900 truncate">{{ $item['seller']->shopName ?: $item['seller']->name }}</div>
                            <div class="text-[10px] text-gray-500 truncate">{{ $item['seller']->email }}</div>
                        </div>
                        <span class="text-[8px] font-black uppercase tracking-wider px-2 py-0.5 rounded-full border {{ $riskBadge }}">
                            {{ $item['risk_level'] }} RISK
                        </span>
                    </div>

                    <div class="grid grid-cols-3 gap-1.5 text-center text-[10px] py-1">
                        <div class="bg-white p-1.5 rounded-xl border border-gray-200/80 shadow-2xs">
                            <div class="font-black text-gray-900">{{ $item['recent_reports'] }}</div>
                            <div class="text-gray-400 text-[8px] font-bold uppercase tracking-wider">Recent</div>
                        </div>
                        <div class="bg-red-50/80 p-1.5 rounded-xl border border-red-100 shadow-2xs">
                            <div class="font-black text-red-600">{{ $item['violations'] }}</div>
                            <div class="text-red-700 text-[8px] font-bold uppercase tracking-wider">Violations</div>
                        </div>
                        <div class="bg-amber-50/80 p-1.5 rounded-xl border border-amber-100 shadow-2xs">
                            <div class="font-black text-amber-700">{{ $item['pending'] }}</div>
                            <div class="text-amber-800 text-[8px] font-bold uppercase tracking-wider">Pending</div>
                        </div>
                    </div>

                    <div class="text-[11px] text-gray-600 bg-white/80 p-2 rounded-xl border border-gray-100 leading-snug">
                        <strong class="text-gray-900 font-bold">Rec:</strong> {{ $item['recommendation'] }}
                    </div>
                </div>
            @endforeach
        </div>

        <p class="text-[11px] text-gray-400 italic">
            ⚖️ <strong>Safety Principle:</strong> Risk scores are decision-support indicators. Administrators always make final enforcement determinations following evidence review.
        </p>
    </div>
    @endif

    {{-- ═══ REPORTS TABLE (Matching Archive Hub Table Design) ═══ --}}
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="overflow-x-auto no-scrollbar">
            <table class="w-full text-left min-w-180">
                <thead>
                    <tr class="border-b border-gray-100 bg-gray-50/60">
                        <th class="px-5 py-2.5 text-[9px] font-black uppercase tracking-widest text-gray-400 w-[20%]">Case &amp; Identifier</th>
                        <th class="px-4 py-2.5 text-[9px] font-black uppercase tracking-widest text-gray-400 w-[18%]">Reporter</th>
                        <th class="px-4 py-2.5 text-[9px] font-black uppercase tracking-widest text-gray-400 w-[20%]">Reported Party / Product</th>
                        <th class="px-4 py-2.5 text-[9px] font-black uppercase tracking-widest text-gray-400 w-[20%]">Concern Reason</th>
                        <th class="px-3 py-2.5 text-[9px] font-black uppercase tracking-widest text-gray-400 w-[8%] text-center">Severity</th>
                        <th class="px-3 py-2.5 text-[9px] font-black uppercase tracking-widest text-gray-400 w-[8%] text-center">Status</th>
                        <th class="px-5 py-2.5 text-[9px] font-black uppercase tracking-widest text-gray-400 w-[6%] text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($reports as $report)
                        @php
                            $rType = $report->reportType ?? (!empty($report->productId) ? 'product' : 'account');
                            $typeIcon = $rType === 'product' ? 'bg-sky-50 text-sky-600' : 'bg-amber-50 text-amber-600';
                            $typeEmoji = $rType === 'product' ? '📦' : '👤';

                            $sevBadge = match(strtoupper($report->severity ?? 'MEDIUM')) {
                                'CRITICAL' => 'bg-red-50 text-red-700 border-red-200/80',
                                'HIGH'     => 'bg-orange-50 text-orange-700 border-orange-200/80',
                                'MEDIUM'   => 'bg-amber-50 text-amber-800 border-amber-200/80',
                                default    => 'bg-emerald-50 text-emerald-700 border-emerald-200/80',
                            };

                            $statusBadge = match($report->status) {
                                'Pending'      => ['badge' => 'bg-amber-50 text-amber-800 border-amber-200/80', 'dot' => 'bg-amber-500'],
                                'Under Review' => ['badge' => 'bg-sky-50 text-sky-800 border-sky-200/80',       'dot' => 'bg-sky-500'],
                                'Resolved'     => ['badge' => 'bg-emerald-50 text-emerald-800 border-emerald-200/80', 'dot' => 'bg-emerald-500'],
                                'Dismissed'    => ['badge' => 'bg-gray-100 text-gray-700 border-gray-200/80',    'dot' => 'bg-gray-400'],
                                default        => ['badge' => 'bg-gray-100 text-gray-700 border-gray-200/80',    'dot' => 'bg-gray-400'],
                            };
                        @endphp
                        <tr class="hover:bg-gray-50/50 transition-colors group">
                            {{-- Case & Identifier --}}
                            <td class="px-5 py-2.5">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-8 h-8 rounded-xl {{ $typeIcon }} border border-gray-100 flex items-center justify-center font-bold text-xs shrink-0 shadow-2xs"
                                         style="width: 32px; height: 32px; min-width: 32px; min-height: 32px;">
                                        <span>{{ $typeEmoji }}</span>
                                    </div>
                                    <div class="min-w-0">
                                        <div class="text-xs font-black font-mono text-gray-900 truncate leading-tight">{{ $report->getReportCode() }}</div>
                                        <div class="mt-0.5">
                                            <span class="inline-flex items-center gap-1 text-[8px] font-black uppercase tracking-wider px-1.5 py-0.5 rounded-full border {{ $rType === 'product' ? 'bg-sky-50 text-sky-700 border-sky-200/60' : 'bg-amber-50 text-amber-700 border-amber-200/60' }}">
                                                <span class="w-1 h-1 rounded-full {{ $rType === 'product' ? 'bg-sky-500' : 'bg-amber-500' }}"></span>
                                                {{ $rType === 'product' ? 'PRODUCT' : 'ACCOUNT' }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </td>

                            {{-- Reporter --}}
                            <td class="px-4 py-2.5">
                                <div class="flex items-center gap-2">
                                    <div class="w-7 h-7 rounded-xl bg-gray-100 border border-gray-200/80 flex items-center justify-center text-[10px] font-black text-gray-600 shrink-0">
                                        {{ strtoupper(substr($report->reporter->name ?? 'A', 0, 1)) }}
                                    </div>
                                    <div class="min-w-0">
                                        <div class="text-xs font-bold text-gray-900 truncate leading-tight">{{ $report->reporter->name ?? 'Anonymous' }}</div>
                                        <div class="text-[10px] text-gray-400 font-medium truncate leading-tight mt-0.5">{{ $report->reporter->email ?? '—' }}</div>
                                    </div>
                                </div>
                            </td>

                            {{-- Reported Party / Product --}}
                            <td class="px-4 py-2.5">
                                <div class="min-w-0">
                                    <div class="text-xs font-bold text-gray-900 truncate leading-tight">
                                        {{ $report->reported->shopName ?: ($report->reported->name ?? 'Deleted Account') }}
                                    </div>
                                    @if($report->product)
                                        <div class="inline-flex items-center gap-1 text-[10px] text-[#C0422A] font-bold truncate max-w-xs mt-0.5">
                                            <span class="w-1 h-1 rounded-full bg-[#C0422A] shrink-0"></span>
                                            <span class="truncate">Product: {{ $report->product->name }}</span>
                                        </div>
                                    @else
                                        <div class="text-[10px] text-gray-400 font-mono mt-0.5">ID: {{ substr($report->reportedId, 0, 8) }}...</div>
                                    @endif
                                </div>
                            </td>

                            {{-- Concern Reason --}}
                            <td class="px-4 py-2.5 max-w-xs">
                                <div class="text-xs font-bold text-gray-900 truncate leading-tight">{{ $report->reason }}</div>
                                <div class="text-[10px] text-gray-400 truncate leading-tight mt-0.5 italic">"{{ $report->description }}"</div>
                                @if($report->sellerResponse)
                                    <span class="inline-flex items-center gap-1 text-[8px] font-bold text-purple-700 bg-purple-50 px-1.5 py-0.2 rounded border border-purple-200 mt-1">
                                        ✓ Responded
                                    </span>
                                @endif
                            </td>

                            {{-- Severity --}}
                            <td class="px-3 py-2.5 text-center">
                                <span class="inline-flex items-center gap-1 text-[8px] font-black uppercase tracking-wider px-2 py-0.5 rounded-full border {{ $sevBadge }} shadow-2xs">
                                    @if(in_array(strtoupper($report->severity ?? ''), ['CRITICAL', 'HIGH']))
                                        <span class="w-1 h-1 rounded-full bg-red-500 animate-pulse"></span>
                                    @endif
                                    {{ $report->severity ?? 'MEDIUM' }}
                                </span>
                            </td>

                            {{-- Status --}}
                            <td class="px-3 py-2.5 text-center">
                                <span class="inline-flex items-center gap-1 text-[8px] font-black uppercase tracking-wider px-2 py-0.5 rounded-full border {{ $statusBadge['badge'] }} shadow-2xs">
                                    <span class="w-1 h-1 rounded-full {{ $statusBadge['dot'] }}"></span>
                                    {{ $report->status }}
                                </span>
                            </td>

                            {{-- Actions --}}
                            <td class="px-5 py-2.5 text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    <button type="button" @click="openModerationModal('{{ $report->id }}')" 
                                            class="inline-flex items-center gap-1 px-2.5 py-1 bg-white hover:bg-gray-100 text-gray-700 border border-gray-200 rounded-lg text-[10px] font-bold uppercase tracking-wider transition-all cursor-pointer shadow-2xs">
                                        <span>Moderate</span>
                                    </button>

                                    <form action="{{ route('admin.reports.delete', $report->id) }}" method="POST" onsubmit="return confirm('Permanently delete this report record?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" 
                                            class="w-7 h-7 rounded-lg border border-red-100 bg-white hover:bg-red-50 flex items-center justify-center text-red-500 hover:text-red-700 transition-all cursor-pointer shadow-2xs" 
                                            title="Delete">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-16 text-center text-gray-400">
                                <div class="flex flex-col items-center gap-2">
                                    <div class="w-12 h-12 rounded-2xl bg-gray-50 border border-gray-100 flex items-center justify-center text-gray-400">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    </div>
                                    <p class="text-xs font-bold uppercase tracking-widest text-gray-700 mt-1">No Reports Found</p>
                                    <p class="text-[11px] text-gray-400">All consumer inquiries and violation flags have been addressed.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Pagination --}}
    <div class="mt-4">
        {{ $reports->appends(request()->query())->links() }}
    </div>

    {{-- ══ DETAILED MODERATION & INVESTIGATION MODAL ══ --}}
    <div x-show="showModModal"
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-6"
         style="display: none;"
         @keydown.escape.window="showModModal = false">
        
        <!-- Backdrop -->
        <div @click="showModModal = false" class="fixed inset-0 bg-black/70 backdrop-blur-sm"></div>

        <!-- Modal Dialog (Crisp Solid White) -->
        <div class="relative bg-white w-full max-w-4xl rounded-3xl p-6 sm:p-8 shadow-2xl overflow-y-auto max-h-[92vh] border border-gray-200 flex flex-col z-10"
             style="background-color: #FFFFFF !important; color: #1E1915 !important;"
             @click.stop>
            
            <!-- Modal Header -->
            <div class="flex items-center justify-between pb-4 border-b border-gray-200 shrink-0">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 rounded-2xl bg-[#1E1915] text-[#C49520] flex items-center justify-center font-bold text-lg shadow-xs">
                        ⚖️
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-mono font-black text-gray-900 bg-gray-100 px-2 py-0.5 rounded border border-gray-200" x-text="activeReport.reportCode"></span>
                            <span class="text-[9px] font-extrabold uppercase tracking-wider px-2.5 py-0.5 rounded-full border bg-gray-50 text-gray-700 border-gray-200" x-text="activeReport.status"></span>
                        </div>
                        <h3 class="text-base sm:text-lg font-black text-gray-900 tracking-tight mt-0.5" x-text="activeReport.reason"></h3>
                    </div>
                </div>

                <button type="button" @click="showModModal = false" class="w-9 h-9 rounded-xl flex items-center justify-center text-gray-400 hover:text-black hover:bg-gray-100 transition-colors cursor-pointer" title="Close">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <!-- Modal Content Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 py-6 overflow-y-auto flex-1">
                
                <!-- Left: Case Details & History (7 cols) -->
                <div class="lg:col-span-7 space-y-5">
                    
                    <!-- Report Info Cards -->
                    <div class="p-4 sm:p-5 rounded-2xl bg-gray-50 border border-gray-200 space-y-2.5 text-xs">
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <span class="text-[9px] font-black uppercase tracking-widest text-gray-400 block">Report Type</span>
                                <span class="font-bold text-gray-900 capitalize text-xs" x-text="activeReport.reportType + ' Report'"></span>
                            </div>
                            <div>
                                <span class="text-[9px] font-black uppercase tracking-widest text-gray-400 block">Date Submitted</span>
                                <span class="font-bold text-gray-900 text-xs" x-text="activeReport.formattedDate"></span>
                            </div>
                        </div>
                    </div>

                    <!-- Customer Concern Full Text -->
                    <div class="space-y-1.5">
                        <label class="text-[10px] font-black uppercase tracking-widest text-gray-500 block">Customer Concern Statement</label>
                        <p class="text-xs sm:text-sm leading-relaxed p-4 rounded-2xl bg-white border border-gray-200 text-gray-900 shadow-2xs" x-text="activeReport.description"></p>
                    </div>

                    <!-- Evidence Previews -->
                    <template x-if="activeReport.evidence && activeReport.evidence.length > 0">
                        <div class="space-y-1.5">
                            <label class="text-[10px] font-black uppercase tracking-widest text-gray-500 block">Submitted Evidence</label>
                            <div class="grid grid-cols-3 gap-2">
                                <template x-for="(img, idx) in activeReport.evidence" :key="idx">
                                    <div class="rounded-xl overflow-hidden border border-gray-200 aspect-square bg-gray-50 cursor-pointer shadow-2xs"
                                         @click="lightboxUrl = img; showLightbox = true">
                                        <img :src="img" class="w-full h-full object-cover">
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>

                    <!-- Seller Response -->
                    <div class="space-y-1.5">
                        <div class="flex items-center justify-between">
                            <label class="text-[10px] font-black uppercase tracking-widest text-gray-500 block">Seller Official Response</label>
                            <span x-show="activeReport.sellerRespondedAt" class="text-[10px] font-bold text-purple-700" x-text="'Responded ' + activeReport.sellerRespondedAt"></span>
                        </div>
                        <template x-if="activeReport.sellerResponse">
                            <div class="space-y-2">
                                <p class="text-xs sm:text-sm leading-relaxed p-4 rounded-2xl bg-purple-50/50 border border-purple-100 text-gray-900 shadow-2xs" x-text="activeReport.sellerResponse"></p>
                                <template x-if="activeReport.sellerResponseEvidence && activeReport.sellerResponseEvidence.length > 0">
                                    <div class="grid grid-cols-3 gap-2">
                                        <template x-for="(sImg, sIdx) in activeReport.sellerResponseEvidence" :key="sIdx">
                                            <div class="rounded-xl overflow-hidden border border-purple-200 aspect-square bg-gray-50 cursor-pointer"
                                                 @click="lightboxUrl = sImg; showLightbox = true">
                                                <img :src="sImg" class="w-full h-full object-cover">
                                            </div>
                                        </template>
                                    </div>
                                </template>
                            </div>
                        </template>
                        <template x-if="!activeReport.sellerResponse">
                            <div class="p-3.5 rounded-xl bg-gray-50 border border-gray-200 text-xs text-gray-400 italic">
                                No response submitted by seller yet.
                            </div>
                        </template>
                    </div>

                    <!-- Case Timeline -->
                    <div class="space-y-2 p-4 sm:p-5 rounded-2xl bg-gray-50 border border-gray-200">
                        <label class="text-[10px] font-black uppercase tracking-widest text-gray-500 block">Case History Timeline</label>
                        <div class="space-y-2.5 relative pl-3 border-l-2 border-[#C49520]/50 ml-1">
                            <template x-for="event in activeReport.timeline" :key="event.id">
                                <div class="text-xs space-y-0.5">
                                    <div class="flex items-center justify-between">
                                        <strong class="text-gray-900" x-text="event.title"></strong>
                                        <span class="text-[10px] text-gray-400" x-text="event.date"></span>
                                    </div>
                                    <p class="text-gray-600 text-xs" x-text="event.description"></p>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                <!-- Right: Moderation Form & Action Dispatch (5 cols) -->
                <div class="lg:col-span-5 bg-gray-50 border border-gray-200 p-5 rounded-3xl space-y-4">
                    <h4 class="text-xs font-black uppercase tracking-widest text-gray-900">Moderation Determination</h4>

                    <form :action="'/admin/reports/' + activeReport.id + '/resolve'" method="POST" class="space-y-3.5">
                        @csrf

                        {{-- Status Selection --}}
                        <div class="space-y-1">
                            <label class="text-[9px] font-black uppercase tracking-widest text-gray-500">Case Status</label>
                            <select name="status" x-model="modStatus" required class="w-full px-3.5 py-2.5 bg-white border border-gray-300 rounded-xl text-xs font-bold text-gray-900 outline-none">
                                <option value="Pending">⏳ Pending</option>
                                <option value="Under Review">🔍 Under Review</option>
                                <option value="Resolved">✓ Resolved</option>
                                <option value="Dismissed">— Dismissed</option>
                            </select>
                        </div>

                        {{-- Severity Selection --}}
                        <div class="space-y-1">
                            <label class="text-[9px] font-black uppercase tracking-widest text-gray-500">Assigned Severity</label>
                            <select name="severity" x-model="modSeverity" class="w-full px-3.5 py-2.5 bg-white border border-gray-300 rounded-xl text-xs font-bold text-gray-900 outline-none">
                                <option value="LOW">🟢 Low (Minor concern)</option>
                                <option value="MEDIUM">🟡 Medium (Potential policy violation)</option>
                                <option value="HIGH">🟠 High (Serious concern requiring investigation)</option>
                                <option value="CRITICAL">🔴 Critical (Severe abuse / fraud / illegal)</option>
                            </select>
                        </div>

                        {{-- Investigation Result --}}
                        <div class="space-y-1">
                            <label class="text-[9px] font-black uppercase tracking-widest text-gray-500">Investigation Result</label>
                            <select name="investigationResult" x-model="modInvestigationResult" class="w-full px-3.5 py-2.5 bg-white border border-gray-300 rounded-xl text-xs font-bold text-gray-900 outline-none">
                                <option value="No Violation Found">No Violation Found</option>
                                <option value="Policy Violation Confirmed">Policy Violation Confirmed</option>
                                <option value="Insufficient Evidence">Insufficient Evidence</option>
                                <option value="Escalated for Further Investigation">Escalated for Further Investigation</option>
                            </select>
                        </div>

                        {{-- Action Taken --}}
                        <div class="space-y-1">
                            <label class="text-[9px] font-black uppercase tracking-widest text-gray-500">Disciplinary Action</label>
                            <select name="action" x-model="modAction" class="w-full px-3.5 py-2.5 bg-white border border-gray-300 rounded-xl text-xs font-bold text-gray-900 outline-none">
                                <option value="None">None (No Action)</option>
                                <option value="Warning">Warning Issued</option>
                                <option value="Request Additional Information">Request Additional Information</option>
                                <option value="Temporary Restriction">Temporary Account Restriction</option>
                                <option value="Suspend Account">Suspend Account (Immediate Logout)</option>
                                <option value="Ban Account">Permanent Account Ban</option>
                                <option value="Escalate to Super Admin">Escalate to Super Admin</option>
                            </select>
                        </div>

                        {{-- Disciplinary Reason (Required if taking action) --}}
                        <div class="space-y-1" x-show="modAction !== 'None'">
                            <label class="text-[9px] font-black uppercase tracking-widest text-red-600">Disciplinary Justification / Notice</label>
                            <textarea name="disciplinaryReason" x-model="modDisciplinaryReason" rows="2" placeholder="Official reason shown to user / recorded in enforcement log..." class="w-full px-3.5 py-2 bg-white border border-red-200 rounded-xl text-xs outline-none resize-none"></textarea>
                        </div>

                        {{-- Internal Moderator Notes --}}
                        <div class="space-y-1">
                            <label class="text-[9px] font-black uppercase tracking-widest text-gray-500">Internal Admin Notes (Private)</label>
                            <textarea name="notes" x-model="modNotes" rows="2" placeholder="Private internal investigation notes..." class="w-full px-3.5 py-2 bg-white border border-gray-300 rounded-xl text-xs outline-none resize-none"></textarea>
                        </div>

                        <div class="pt-2">
                            <button type="submit" class="w-full py-3 bg-[#1E1915] text-white rounded-xl text-xs font-black uppercase tracking-widest hover:bg-black transition-all shadow-md cursor-pointer">
                                Apply Determination &amp; Update
                            </button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>

    {{-- Lightbox Viewer --}}
    <div x-show="showLightbox"
         x-cloak
         class="fixed inset-0 z-2000 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm"
         style="display: none;"
         @click="showLightbox = false"
         @keydown.escape.window="showLightbox = false">
        <div class="relative max-w-4xl max-h-[90vh] flex flex-col items-center" @click.stop>
            <img :src="lightboxUrl" class="max-w-full max-h-[80vh] rounded-2xl shadow-2xl object-contain border-2 border-white/20">
            <button type="button" @click="showLightbox = false" class="mt-4 px-5 py-2 rounded-xl text-xs font-bold uppercase tracking-widest bg-white/20 text-white hover:bg-white/40 backdrop-blur-md cursor-pointer">
                Close (ESC)
            </button>
        </div>
    </div>

</div>

<script>
function adminReportsManagement() {
    return {
        showModModal: false,
        showLightbox: false,
        lightboxUrl: '',
        activeReport: {},
        modStatus: 'Resolved',
        modSeverity: 'MEDIUM',
        modInvestigationResult: 'No Violation Found',
        modAction: 'None',
        modDisciplinaryReason: '',
        modNotes: '',

        async openModerationModal(reportId) {
            try {
                const res = await fetch(`/api/v1/reports/${reportId}`);
                if (res.ok) {
                    this.activeReport = await res.json();
                    this.modStatus = this.activeReport.status || 'Resolved';
                    this.modSeverity = this.activeReport.severity || 'MEDIUM';
                    this.modInvestigationResult = this.activeReport.investigationResult || 'No Violation Found';
                    this.modAction = this.activeReport.actionTaken || 'None';
                    this.modDisciplinaryReason = this.activeReport.disciplinaryReason || '';
                    this.modNotes = this.activeReport.adminNotes || '';
                    this.showModModal = true;
                } else {
                    alert('Could not load report details.');
                }
            } catch (e) {
                alert('Network error while loading report details.');
            }
        }
    };
}
</script>
@endsection
