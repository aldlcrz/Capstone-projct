@extends('layouts.superadmin')

@section('content')
<div class="space-y-4" x-data="superSellerManager()">
    {{-- ═══ PAGE HEADER + SEARCH BAR ═══ --}}
    <div id="tour-superadmin-sellers-header" class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
        {{-- Left: Title & Subtitle --}}
        <div class="text-left space-y-0.5 shrink-0">
            <div class="inline-flex items-center gap-2">
                <span class="text-[9px] font-black uppercase tracking-[0.25em] text-[#C0422A]">Artisan Registry</span>
                <span class="text-gray-300 text-xs">·</span>
                <span class="text-[9px] font-bold uppercase tracking-[0.2em] text-gray-400">Super Admin Center</span>
            </div>
            <div class="flex items-center gap-3 flex-wrap">
                <h1 class="font-serif text-2xl sm:text-3xl font-bold text-gray-900 leading-tight">
                    Seller <span class="text-[#C0420A] font-light italic">Management</span>
                </h1>
                {{-- Guide Button --}}
                <button type="button" 
                        onclick="window.startSpotlightTour ? window.startSpotlightTour('admin-sellers-guide') : null"
                        class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full text-xs font-black uppercase tracking-wider transition-all shadow-xs cursor-pointer group shrink-0"
                        style="background-color: #FFF5F2; color: #C0422A; border: 1.5px solid #C0422A;"
                        onmouseover="this.style.backgroundColor='#C0422A'; this.style.color='#FFFFFF';"
                        onmouseout="this.style.backgroundColor='#FFF5F2'; this.style.color='#C0422A';"
                        title="Interactive Seller Management Guide">
                    <svg class="w-4 h-4 transition-transform group-hover:rotate-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span>Seller Guide</span>
                </button>
            </div>
            <p class="text-[11px] text-gray-400 font-medium">Review applications, monitor shops, manage debt, and enforce marketplace governance</p>
        </div>

        {{-- Larger Search Bar --}}
        <div id="tour-superadmin-sellers-search" class="flex-1 max-w-xl lg:max-w-2xl w-full">
            <form method="GET" action="{{ route('superadmin.sellers') }}" class="flex items-center gap-2 w-full">
                @if(request('filter'))
                    <input type="hidden" name="filter" value="{{ request('filter') }}">
                @endif
                <div class="relative w-full">
                    <input type="text" name="search" value="{{ request('search', $search ?? '') }}"
                           placeholder="Search sellers by name, email, shop..."
                           class="w-full pl-11 pr-5 py-2.5 bg-white border border-gray-200 rounded-full text-sm text-gray-800 placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-[#C0422A]/20 focus:border-[#C0422A] shadow-xs transition-all">
                    <svg class="w-5 h-5 text-gray-400 absolute left-4 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>
                @if(request('search') || !empty($search))
                    <a href="{{ route('superadmin.sellers', ['filter' => request('filter')]) }}" class="px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-full text-xs font-bold transition-all shrink-0">Clear</a>
                @endif
            </form>
        </div>
    </div>

    {{-- ═══ STATS BAR / STATUS FILTERS ═══ --}}
    @php
        $currentFilter = request('filter', $filter ?? '');
        $isAll       = $currentFilter === 'all';
        $isApproved  = empty($currentFilter) || $currentFilter === 'verified';
        $isPending   = $currentFilter === 'pending' || $currentFilter === 'unverified';
        $isFrozen    = $currentFilter === 'frozen';
        $isSuspended = $currentFilter === 'suspended';
        $isRejected  = $currentFilter === 'rejected';
    @endphp
    <div id="tour-superadmin-sellers-filters" class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3">
        {{-- Total / All --}}
        <a href="{{ route('superadmin.sellers', ['filter' => 'all', 'search' => request('search')]) }}"
           class="group relative rounded-2xl px-3.5 py-3 flex items-center justify-between border transition-all duration-200 cursor-pointer {{ $isAll ? 'bg-white border-gray-900 ring-2 ring-gray-900/15 shadow-sm -translate-y-0.5' : 'bg-white border-gray-100 hover:border-gray-300 hover:shadow-sm hover:-translate-y-0.5' }}">
            <div class="flex items-center gap-2.5">
                <div class="w-7 h-7 rounded-xl flex items-center justify-center shrink-0 transition-colors {{ $isAll ? 'bg-gray-900 text-white' : 'bg-gray-100 text-gray-500 group-hover:bg-gray-200' }}">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                </div>
                <div>
                    <div class="text-sm sm:text-base font-black text-gray-900 leading-none">{{ $counts['all'] ?? 0 }}</div>
                    <div class="text-[9px] font-bold uppercase tracking-wider text-gray-400 mt-0.5">Total</div>
                </div>
            </div>
            @if($isAll)
                <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-full text-[8px] font-black uppercase tracking-wider bg-gray-900 text-white shadow-xs">Active</span>
            @endif
        </a>

        {{-- Approved (Default) --}}
        <a href="{{ route('superadmin.sellers', ['search' => request('search')]) }}"
           class="group relative rounded-2xl px-3.5 py-3 flex items-center justify-between border transition-all duration-200 cursor-pointer {{ $isApproved ? 'bg-emerald-50/50 border-emerald-600 ring-2 ring-emerald-600/20 shadow-sm -translate-y-0.5' : 'bg-white border-green-100 hover:border-emerald-300 hover:shadow-sm hover:-translate-y-0.5' }}">
            <div class="flex items-center gap-2.5">
                <div class="w-7 h-7 rounded-xl flex items-center justify-center shrink-0 transition-colors {{ $isApproved ? 'bg-emerald-600 text-white' : 'bg-green-50 text-green-600 group-hover:bg-green-100' }}">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div>
                    <div class="text-sm sm:text-base font-black text-gray-900 leading-none">{{ $counts['verified'] ?? 0 }}</div>
                    <div class="text-[9px] font-bold uppercase tracking-wider text-green-600 mt-0.5">Active</div>
                </div>
            </div>
            @if($isApproved)
                <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-full text-[8px] font-black uppercase tracking-wider bg-emerald-600 text-white shadow-xs">Active</span>
            @endif
        </a>

        {{-- Pending --}}
        <a href="{{ route('superadmin.sellers', ['filter' => 'pending', 'search' => request('search')]) }}"
           class="group relative rounded-2xl px-3.5 py-3 flex items-center justify-between border transition-all duration-200 cursor-pointer {{ $isPending ? 'bg-amber-50/50 border-amber-500 ring-2 ring-amber-500/20 shadow-sm -translate-y-0.5' : 'bg-white border-amber-100 hover:border-amber-300 hover:shadow-sm hover:-translate-y-0.5' }}">
            <div class="flex items-center gap-2.5">
                <div class="w-7 h-7 rounded-xl flex items-center justify-center shrink-0 transition-colors {{ $isPending ? 'bg-amber-500 text-white' : 'bg-amber-50 text-amber-500 group-hover:bg-amber-100' }}">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div>
                    <div class="text-sm sm:text-base font-black text-gray-900 leading-none">{{ $counts['pending'] ?? 0 }}</div>
                    <div class="text-[9px] font-bold uppercase tracking-wider text-amber-500 mt-0.5">Pending</div>
                </div>
            </div>
            @if($isPending)
                <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-full text-[8px] font-black uppercase tracking-wider bg-amber-500 text-white shadow-xs">Active</span>
            @endif
        </a>

        {{-- Frozen (Commission Debt) --}}
        <a href="{{ route('superadmin.sellers', ['filter' => 'frozen', 'search' => request('search')]) }}"
           class="group relative rounded-2xl px-3.5 py-3 flex items-center justify-between border transition-all duration-200 cursor-pointer {{ $isFrozen ? 'bg-orange-50/50 border-orange-500 ring-2 ring-orange-500/20 shadow-sm -translate-y-0.5' : 'bg-white border-orange-100 hover:border-orange-300 hover:shadow-sm hover:-translate-y-0.5' }}">
            <div class="flex items-center gap-2.5">
                <div class="w-7 h-7 rounded-xl flex items-center justify-center shrink-0 transition-colors {{ $isFrozen ? 'bg-orange-500 text-white' : 'bg-orange-50 text-orange-500 group-hover:bg-orange-100' }}">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                </div>
                <div>
                    <div class="text-sm sm:text-base font-black text-gray-900 leading-none">{{ $counts['frozen'] ?? 0 }}</div>
                    <div class="text-[9px] font-bold uppercase tracking-wider text-orange-600 mt-0.5">Frozen</div>
                </div>
            </div>
            @if($isFrozen)
                <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-full text-[8px] font-black uppercase tracking-wider bg-orange-500 text-white shadow-xs">Active</span>
            @endif
        </a>

        {{-- Suspended / Policy Violation --}}
        <a href="{{ route('superadmin.sellers', ['filter' => 'suspended', 'search' => request('search')]) }}"
           class="group relative rounded-2xl px-3.5 py-3 flex items-center justify-between border transition-all duration-200 cursor-pointer {{ $isSuspended ? 'bg-red-50/50 border-red-600 ring-2 ring-red-600/20 shadow-sm -translate-y-0.5' : 'bg-white border-red-100 hover:border-red-300 hover:shadow-sm hover:-translate-y-0.5' }}">
            <div class="flex items-center gap-2.5">
                <div class="w-7 h-7 rounded-xl flex items-center justify-center shrink-0 transition-colors {{ $isSuspended ? 'bg-red-600 text-white' : 'bg-red-50 text-red-500 group-hover:bg-red-100' }}">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                </div>
                <div>
                    <div class="text-sm sm:text-base font-black text-gray-900 leading-none">{{ $counts['suspended'] ?? 0 }}</div>
                    <div class="text-[9px] font-bold uppercase tracking-wider text-red-600 mt-0.5">Suspended</div>
                </div>
            </div>
            @if($isSuspended)
                <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-full text-[8px] font-black uppercase tracking-wider bg-red-600 text-white shadow-xs">Active</span>
            @endif
        </a>
    </div>

    {{-- ═══ PENDING VERIFICATION ALERT STRIP ═══ --}}
    @if(isset($pendingSellers) && $pendingSellers->count() > 0 && !$isPending)
    <div class="bg-amber-50/70 border border-amber-200/80 rounded-2xl p-3 sm:p-3.5 space-y-2">
        <div class="flex items-center justify-between">
            <h3 class="text-[10px] font-black uppercase tracking-widest text-amber-900 flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
                Awaiting Verification ({{ $pendingSellers->count() }})
            </h3>
            <a href="{{ route('superadmin.sellers', ['filter' => 'pending']) }}" class="text-[10px] font-bold text-amber-800 hover:text-amber-950 underline">View all pending</a>
        </div>
        <div class="space-y-1.5">
            @foreach($pendingSellers->take(3) as $pendingSeller)
            @php
                $pData = [
                    'id' => $pendingSeller->id,
                    'name' => $pendingSeller->name,
                    'email' => $pendingSeller->email,
                    'mobileNumber' => $pendingSeller->mobileNumber ?? 'Not Provided',
                    'gcashNumber' => $pendingSeller->gcashNumber ?? 'Not Provided',
                    'shopName' => $pendingSeller->shopName ?? ($pendingSeller->name . "'s Workshop"),
                    'shopAddress' => $pendingSeller->shopAddress ?? 'Not Provided',
                    'residencyCertificate' => $pendingSeller->residencyCertificate ? (str_starts_with($pendingSeller->residencyCertificate, 'http') || str_starts_with($pendingSeller->residencyCertificate, '/') ? $pendingSeller->residencyCertificate : asset($pendingSeller->residencyCertificate)) : null,
                    'businessPermit' => $pendingSeller->businessPermit ? (str_starts_with($pendingSeller->businessPermit, 'http') || str_starts_with($pendingSeller->businessPermit, '/') ? $pendingSeller->businessPermit : asset($pendingSeller->businessPermit)) : null,
                    'birDocument' => $pendingSeller->birDocument ? (str_starts_with($pendingSeller->birDocument, 'http') || str_starts_with($pendingSeller->birDocument, '/') ? $pendingSeller->birDocument : asset($pendingSeller->birDocument)) : null,
                    'createdAt' => $pendingSeller->createdAt ? $pendingSeller->createdAt->format('M d, Y h:i A') : '—',
                    'isVerified' => (bool)$pendingSeller->isVerified,
                    'status' => $pendingSeller->status ?? 'pending',
                    'products_count' => $pendingSeller->products_count ?? 0,
                    'orders_count' => $pendingSeller->orders_count ?? 0,
                ];
            @endphp
            <div class="bg-white rounded-xl p-2 sm:p-2.5 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-2 shadow-xs border border-amber-100/80">
                <div class="flex items-center gap-2.5 min-w-0">
                    <div class="w-7 h-7 rounded-full bg-amber-100 text-amber-800 flex items-center justify-center font-bold text-xs shrink-0">
                        {{ strtoupper(substr($pendingSeller->name, 0, 1)) }}
                    </div>
                    <div class="min-w-0">
                        <div class="text-xs font-bold text-gray-900 truncate leading-tight">{{ $pendingSeller->name }}</div>
                        <div class="text-[10px] text-gray-400 font-medium truncate leading-tight mt-0.5">
                            {{ $pendingSeller->email }}
                            @if($pendingSeller->shopName)
                                <span class="text-gray-300">·</span>
                                <span class="text-[#C0422A] font-semibold">{{ $pendingSeller->shopName }}</span>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="flex items-center gap-1.5 shrink-0">
                    <button type="button" @click="openReview({{ json_encode($pData) }})" class="px-2.5 py-1 bg-emerald-600 text-white rounded-lg text-[9px] font-black uppercase tracking-wider hover:bg-emerald-700 transition-all cursor-pointer shadow-xs">
                        Approve &amp; Verify
                    </button>
                    <button type="button" @click="openReject('{{ $pendingSeller->id }}', '{{ addslashes($pendingSeller->name) }}')" class="px-2.5 py-1 bg-rose-50 text-rose-700 rounded-lg text-[9px] font-black uppercase tracking-wider hover:bg-rose-500 hover:text-white transition-all cursor-pointer">
                        Reject
                    </button>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- ═══ RESULTS & SELLER TABLE ═══ --}}
    <div class="space-y-2">
        {{-- Results count bar --}}
        <div class="flex items-center justify-between px-1">
            <div class="text-[11px] font-bold text-gray-400">
                Showing <span class="text-gray-900 font-black">{{ $sellers->total() }}</span> {{ $sellers->total() === 1 ? 'artisan seller' : 'artisan sellers' }}
                @if($currentFilter)
                    <span class="text-gray-400">· Filtered by <span class="capitalize font-black text-gray-700">{{ $currentFilter }}</span></span>
                @else
                    <span class="text-gray-400">· Filtered by <span class="font-black text-gray-700">Approved</span></span>
                @endif
                @if(request('search'))
                    <span class="text-gray-400">· Matching "<span class="font-bold text-gray-700">{{ request('search') }}</span>"</span>
                @endif
            </div>
        </div>

        {{-- Table Card --}}
        <div id="tour-superadmin-sellers-table" class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
            <div class="overflow-x-auto no-scrollbar">
                <table class="w-full text-left min-w-180">
                    <thead>
                        <tr class="border-b border-gray-100 bg-gray-50/60">
                            <th class="px-5 py-3 text-[9px] font-black uppercase tracking-widest text-gray-400 w-[32%]">Seller &amp; Shop</th>
                            <th class="px-4 py-3 text-[9px] font-black uppercase tracking-widest text-gray-400 w-[16%] hidden md:table-cell">Inventory</th>
                            <th class="px-4 py-3 text-[9px] font-black uppercase tracking-widest text-gray-400 w-[16%] hidden lg:table-cell">Joined</th>
                            <th class="px-4 py-3 text-[9px] font-black uppercase tracking-widest text-gray-400 w-[14%]">Status</th>
                            <th id="tour-superadmin-sellers-actions" class="px-5 py-3 text-[9px] font-black uppercase tracking-widest text-gray-400 w-[22%] text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @forelse($sellers as $seller)
                            @php
                                $sData = [
                                    'id' => $seller->id,
                                    'name' => $seller->name,
                                    'email' => $seller->email,
                                    'mobileNumber' => $seller->mobileNumber ?? 'Not Provided',
                                    'gcashNumber' => $seller->gcashNumber ?? 'Not Provided',
                                    'shopName' => $seller->shopName ?? ($seller->name . "'s Workshop"),
                                    'shopAddress' => $seller->shopAddress ?? 'Not Provided',
                                    'residencyCertificate' => $seller->residencyCertificate ? (str_starts_with($seller->residencyCertificate, 'http') || str_starts_with($seller->residencyCertificate, '/') ? $seller->residencyCertificate : asset($seller->residencyCertificate)) : null,
                                    'businessPermit' => $seller->businessPermit ? (str_starts_with($seller->businessPermit, 'http') || str_starts_with($seller->businessPermit, '/') ? $seller->businessPermit : asset($seller->businessPermit)) : null,
                                    'birDocument' => $seller->birDocument ? (str_starts_with($seller->birDocument, 'http') || str_starts_with($seller->birDocument, '/') ? $seller->birDocument : asset($seller->birDocument)) : null,
                                    'createdAt' => $seller->createdAt ? $seller->createdAt->format('M d, Y h:i A') : '—',
                                    'isVerified' => (bool)$seller->isVerified,
                                    'status' => $seller->status ?? 'pending',
                                    'rejection_type' => $seller->rejection_type ?? 'document_correction',
                                    'rejection_reason' => $seller->rejection_reason ?: ($seller->rejectionReason ?: ''),
                                    'products_count' => $seller->products_count ?? 0,
                                    'orders_count' => $seller->orders_count ?? 0,
                                ];
                                
                                $normStatus = match(true) {
                                    $seller->status === 'blocked' || $seller->status === 'suspended' => 'suspended',
                                    $seller->status === 'rejected' => 'rejected',
                                    $seller->status === 'frozen'   => 'frozen',
                                    $seller->isVerified && ($seller->status === 'active' || empty($seller->status)) => 'active',
                                    default => 'pending',
                                };

                                $statusClass = match($normStatus) {
                                    'suspended' => 'bg-red-50 text-red-700 border border-red-200/80',
                                    'rejected'  => 'bg-rose-50 text-rose-700 border border-rose-200/80',
                                    'frozen'    => 'bg-orange-50 text-orange-700 border border-orange-200/80',
                                    'pending'   => 'bg-amber-50 text-amber-800 border border-amber-200/80',
                                    default     => 'bg-emerald-50 text-emerald-700 border border-emerald-200/80',
                                };
                                $statusDot = match($normStatus) {
                                    'suspended' => 'bg-red-500',
                                    'rejected'  => 'bg-rose-500',
                                    'frozen'    => 'bg-orange-500',
                                    'pending'   => 'bg-amber-500',
                                    default     => 'bg-emerald-500',
                                };
                                $statusLabel = match($normStatus) {
                                    'suspended' => 'Suspended',
                                    'rejected'  => 'Rejected',
                                    'frozen'    => 'Frozen',
                                    'pending'   => 'Pending',
                                    default     => 'Active',
                                };
                            @endphp
                            <tr class="hover:bg-gray-50/50 transition-colors group">
                                {{-- Seller & Shop --}}
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-3.5">
                                        <div class="w-9 h-9 rounded-full bg-gray-100 ring-2 ring-gray-200 flex items-center justify-center font-bold text-xs text-gray-700 shrink-0 overflow-hidden transition-all shadow-xs"
                                             style="width: 36px; height: 36px; min-width: 36px; min-height: 36px; max-width: 36px; max-height: 36px;">
                                            @if($seller->profilePhoto)
                                                <img src="{{ str_starts_with($seller->profilePhoto, 'http') || str_starts_with($seller->profilePhoto, '/') ? $seller->profilePhoto : asset('storage/' . $seller->profilePhoto) }}"
                                                     class="w-full h-full object-cover"
                                                     style="width: 36px; height: 36px; min-width: 36px; min-height: 36px; max-width: 36px; max-height: 36px; object-fit: cover;">
                                            @else
                                                <span class="text-xs font-bold text-gray-600">{{ strtoupper(substr($seller->name, 0, 1)) }}</span>
                                            @endif
                                        </div>
                                        <div class="min-w-0">
                                            <div class="flex items-center gap-2 flex-wrap">
                                                <span class="text-xs font-bold text-gray-900 truncate leading-snug">{{ $seller->name }}</span>
                                                @if($seller->isVerified)
                                                    <span class="text-[8px] font-black text-emerald-600 bg-emerald-50 border border-emerald-200/80 px-1.5 py-0.5 rounded uppercase leading-none">✓</span>
                                                @endif
                                            </div>
                                            <div class="text-[10px] text-gray-400 font-medium truncate leading-tight mt-1">
                                                <span>{{ $seller->email }}</span>
                                                @if($seller->shopName)
                                                    <span class="text-gray-300 mx-1">·</span>
                                                    <span class="text-[#C0422A] font-semibold">{{ $seller->shopName }}</span>
                                                @endif
                                            </div>
                                            @if($normStatus === 'suspended' && $seller->violationReason)
                                                <div class="text-[9px] text-red-600 font-semibold truncate max-w-xs mt-1" title="{{ $seller->violationReason }}">
                                                    Violation: {{ $seller->violationReason }}
                                                </div>
                                            @elseif($normStatus === 'frozen')
                                                @php
                                                    $unpaidRec = $seller->commissionRecords ? $seller->commissionRecords->first() : null;
                                                @endphp
                                                @if($unpaidRec)
                                                    <div class="text-[9px] text-orange-600 font-semibold truncate max-w-xs mt-1">
                                                        Overdue Commission: ₱{{ number_format($unpaidRec->commissionAmount, 2) }} ({{ $unpaidRec->period }})
                                                    </div>
                                                @endif
                                            @endif
                                        </div>
                                    </div>
                                </td>

                                {{-- Inventory --}}
                                <td class="px-4 py-4 hidden md:table-cell">
                                    <div class="text-[11px] text-gray-600 font-medium">
                                        <strong class="text-gray-900 font-bold">{{ $seller->products_count ?? 0 }}</strong> prods
                                        <span class="text-gray-300 mx-1">·</span>
                                        <strong class="text-gray-900 font-bold">{{ $seller->orders_count ?? 0 }}</strong> orders
                                    </div>
                                </td>

                                {{-- Joined Date --}}
                                <td class="px-4 py-4 hidden lg:table-cell">
                                    <span class="text-[11px] text-gray-500 font-medium whitespace-nowrap">{{ $seller->createdAt ? $seller->createdAt->format('M d, Y') : '—' }}</span>
                                </td>

                                {{-- Status --}}
                                <td class="px-4 py-4">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold {{ $statusClass }}">
                                        <span class="w-1.5 h-1.5 rounded-full {{ $statusDot }}"></span>
                                        {{ $statusLabel }}
                                    </span>
                                </td>

                                {{-- Actions --}}
                                <td class="px-5 py-4">
                                    <div class="flex items-center justify-end gap-2">
                                        {{-- Docs Button --}}
                                        <button type="button" @click="openReview({{ json_encode($sData) }})"
                                            class="px-2.5 py-1 bg-gray-50 text-gray-600 border border-gray-200/80 rounded-lg text-[10px] font-bold uppercase tracking-wider hover:bg-gray-200 transition-all cursor-pointer">
                                            Docs
                                        </button>

                                        @if($normStatus === 'frozen')
                                            {{-- Unfreeze Button (Super Admin Exclusive) --}}
                                            <form action="{{ route('superadmin.sellers.unfreeze', $seller->id) }}" method="POST" class="inline">
                                                @csrf @method('PATCH')
                                                <button type="submit"
                                                    class="px-2.5 py-1 bg-emerald-50 text-emerald-700 border border-emerald-200/80 rounded-lg text-[10px] font-bold uppercase tracking-wider hover:bg-emerald-600 hover:text-white transition-all cursor-pointer">
                                                    Unfreeze
                                                </button>
                                            </form>
                                        @else
                                            {{-- Freeze Button (Super Admin Exclusive) --}}
                                            <button type="button" @click="openFreeze('{{ $seller->id }}', '{{ addslashes($seller->name) }}')"
                                                class="px-2.5 py-1 bg-orange-50 text-orange-700 border border-orange-200/80 rounded-lg text-[10px] font-bold uppercase tracking-wider hover:bg-orange-600 hover:text-white transition-all cursor-pointer" title="Freeze Shop Account">
                                                Freeze
                                            </button>
                                        @endif

                                        @if($normStatus === 'suspended')
                                            <form action="{{ route('superadmin.sellers.unsuspend', $seller->id) }}" method="POST" class="inline">
                                                @csrf @method('PATCH')
                                                <button type="submit"
                                                    class="px-2.5 py-1 bg-emerald-50 text-emerald-700 border border-emerald-200/80 rounded-lg text-[10px] font-bold uppercase tracking-wider hover:bg-emerald-600 hover:text-white transition-all cursor-pointer">
                                                    Unsuspend
                                                </button>
                                            </form>
                                        @else
                                            <button type="button" @click="openSuspend('{{ $seller->id }}', '{{ addslashes($seller->name) }}')"
                                                class="px-2.5 py-1 bg-red-50 text-red-600 border border-red-200/80 rounded-lg text-[10px] font-bold uppercase tracking-wider hover:bg-red-600 hover:text-white transition-all cursor-pointer" title="Suspend Account (Policy Violation)">
                                                Suspend
                                            </button>
                                        @endif

                                        {{-- Delete Button --}}
                                        <button type="button" @click="openDelete('{{ $seller->id }}', '{{ addslashes($seller->name) }}')"
                                            class="px-2.5 py-1 bg-gray-50 text-gray-400 border border-gray-200/80 rounded-lg text-[10px] font-bold uppercase tracking-wider hover:bg-red-50 hover:text-red-600 hover:border-red-200 transition-all cursor-pointer">
                                            Delete
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-5 py-12 text-center text-gray-400">
                                    <div class="w-12 h-12 bg-gray-50 rounded-2xl flex items-center justify-center mx-auto mb-3 border border-gray-100">
                                        <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                    </div>
                                    <div class="text-sm font-bold text-gray-700">No Artisan Sellers Found</div>
                                    <p class="text-xs text-gray-400 mt-0.5">Try refining your search or changing the filter pill.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            @if($sellers->hasPages())
                <div class="p-4 bg-gray-50/50 border-t border-gray-100">
                    {{ $sellers->appends(['filter' => request('filter'), 'search' => request('search')])->links() }}
                </div>
            @endif
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════════ --}}
    {{-- ═══ 1. APPLICANT DOCUMENT REVIEW MODAL ═════════════════════ --}}
    {{-- ══════════════════════════════════════════════════════════════ --}}
    <div x-show="reviewModal" 
         x-cloak 
         style="display: none;"
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95">
        <div class="bg-white w-full max-w-4xl max-h-[90vh] rounded-3xl shadow-2xl relative z-10 overflow-hidden border border-gray-100 flex flex-col" @click.away="reviewModal = false">
            
            {{-- Modal Header --}}
            <div class="px-6 sm:px-8 py-5 border-b border-gray-100 flex items-center justify-between sticky top-0 bg-white/95 backdrop-blur-md z-20">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-[#C0422A] text-white flex items-center justify-center font-black text-base shadow-sm">
                        <span x-text="selectedSeller.name ? selectedSeller.name.charAt(0).toUpperCase() : 'S'"></span>
                    </div>
                    <div>
                        <h3 class="text-base sm:text-lg font-black text-gray-900 leading-tight flex items-center gap-2">
                            <span x-text="selectedSeller.name"></span>
                            <span x-show="selectedSeller.isVerified && selectedSeller.status !== 'blocked' && selectedSeller.status !== 'suspended' && selectedSeller.status !== 'frozen'" class="text-green-600 text-xs font-bold bg-green-50 px-2 py-0.5 rounded-md border border-green-200">Active</span>
                            <span x-show="selectedSeller.status === 'frozen'" class="text-orange-600 text-xs font-bold bg-orange-50 px-2 py-0.5 rounded-md border border-orange-200">Frozen</span>
                            <span x-show="!selectedSeller.isVerified && selectedSeller.status !== 'rejected'" class="text-blue-600 text-xs font-bold bg-blue-50 px-2 py-0.5 rounded-md border border-blue-200">Pending Review</span>
                            <span x-show="selectedSeller.status === 'rejected'" class="text-rose-600 text-xs font-bold bg-rose-50 px-2 py-0.5 rounded-md border border-rose-200">Rejected</span>
                            <span x-show="selectedSeller.status === 'blocked' || selectedSeller.status === 'suspended'" class="text-red-600 text-xs font-bold bg-red-50 px-2 py-0.5 rounded-md border border-red-200">Suspended</span>
                        </h3>
                        <p class="text-xs text-gray-500">Inspect credentials &amp; requirements before verification</p>
                    </div>
                </div>
                <button type="button" @click="reviewModal = false" class="w-9 h-9 rounded-full bg-gray-100 hover:bg-gray-200 text-gray-500 hover:text-black flex items-center justify-center transition-all cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            {{-- Modal Content --}}
            <div class="p-6 sm:p-8 space-y-6 flex-1 overflow-y-auto">
                
                {{-- Applicant Details Grid --}}
                <div class="bg-[#F9F6F2] rounded-2xl p-5 border border-[#E5DDD5] space-y-4">
                    <div class="text-[10px] font-black uppercase tracking-widest text-[#8C7B70]">Applicant Information</div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 text-xs">
                        <div>
                            <span class="text-gray-400 block text-[10px] font-bold uppercase">Full Registry Name</span>
                            <span class="font-bold text-gray-900" x-text="selectedSeller.name"></span>
                        </div>
                        <div>
                            <span class="text-gray-400 block text-[10px] font-bold uppercase">Secure Email</span>
                            <span class="font-bold text-gray-900 truncate block" x-text="selectedSeller.email"></span>
                        </div>
                        <div>
                            <span class="text-gray-400 block text-[10px] font-bold uppercase">Workshop / Shop Name</span>
                            <span class="font-bold text-[#C0422A] block" x-text="selectedSeller.shopName"></span>
                        </div>
                        <div>
                            <span class="text-gray-400 block text-[10px] font-bold uppercase">Application Date</span>
                            <span class="font-bold text-gray-900" x-text="selectedSeller.createdAt"></span>
                        </div>
                    </div>
                </div>

                {{-- Requirements Documents Gallery --}}
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <div class="text-[10px] font-black uppercase tracking-widest text-gray-500">Submitted Verification Documents</div>
                        <span class="text-[10px] font-bold text-gray-400">Click any document to inspect full preview</span>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        
                        {{-- 1. Proof of Lumban Residency --}}
                        <div class="bg-white rounded-2xl border border-gray-200 p-4 flex flex-col justify-between space-y-3 shadow-sm hover:border-[#C0422A] transition-colors">
                            <div>
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-[10px] font-black uppercase tracking-wider text-gray-500">1. Proof of Lumban Residency</span>
                                    <template x-if="selectedSeller.residencyCertificate">
                                        <span class="text-[9px] font-bold text-green-700 bg-green-50 px-2 py-0.5 rounded-full border border-green-200">Attached</span>
                                    </template>
                                    <template x-if="!selectedSeller.residencyCertificate">
                                        <span class="text-[9px] font-bold text-red-600 bg-red-50 px-2 py-0.5 rounded-full border border-red-200">Missing</span>
                                    </template>
                                </div>
                                <div class="h-36 rounded-xl bg-gray-50 border border-gray-100 flex items-center justify-center overflow-hidden relative group">
                                    <template x-if="selectedSeller.residencyCertificate">
                                        <img :src="selectedSeller.residencyCertificate" class="w-full h-full object-cover group-hover:scale-105 transition-transform cursor-pointer" @click="openPreview(selectedSeller.residencyCertificate, 'Proof of Lumban Residency')">
                                    </template>
                                    <template x-if="!selectedSeller.residencyCertificate">
                                        <div class="text-center p-3 text-gray-400">
                                            <svg class="w-8 h-8 mx-auto mb-1 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                            <span class="text-[10px] font-bold">No file uploaded</span>
                                        </div>
                                    </template>
                                </div>
                            </div>
                            <template x-if="selectedSeller.residencyCertificate">
                                <div class="flex gap-2">
                                    <button type="button" @click="openPreview(selectedSeller.residencyCertificate, 'Proof of Lumban Residency')" class="flex-1 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-800 rounded-lg text-[10px] font-bold transition-all cursor-pointer text-center">
                                        Preview
                                    </button>
                                    <a :href="selectedSeller.residencyCertificate" target="_blank" download class="px-3 py-1.5 bg-[#3D2B1F] text-white hover:bg-[#C0422A] rounded-lg text-[10px] font-bold transition-all text-center">
                                        Download
                                    </a>
                                </div>
                            </template>
                        </div>

                        {{-- 2. Business Permit --}}
                        <div class="bg-white rounded-2xl border border-gray-200 p-4 flex flex-col justify-between space-y-3 shadow-sm hover:border-[#C0422A] transition-colors">
                            <div>
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-[10px] font-black uppercase tracking-wider text-gray-500">2. Business Permit</span>
                                    <template x-if="selectedSeller.businessPermit">
                                        <span class="text-[9px] font-bold text-green-700 bg-green-50 px-2 py-0.5 rounded-full border border-green-200">Attached</span>
                                    </template>
                                    <template x-if="!selectedSeller.businessPermit">
                                        <span class="text-[9px] font-bold text-red-600 bg-red-50 px-2 py-0.5 rounded-full border border-red-200">Missing</span>
                                    </template>
                                </div>
                                <div class="h-36 rounded-xl bg-gray-50 border border-gray-100 flex items-center justify-center overflow-hidden relative group">
                                    <template x-if="selectedSeller.businessPermit">
                                        <img :src="selectedSeller.businessPermit" class="w-full h-full object-cover group-hover:scale-105 transition-transform cursor-pointer" @click="openPreview(selectedSeller.businessPermit, 'Business Permit')">
                                    </template>
                                    <template x-if="!selectedSeller.businessPermit">
                                        <div class="text-center p-3 text-gray-400">
                                            <svg class="w-8 h-8 mx-auto mb-1 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                            <span class="text-[10px] font-bold">No file uploaded</span>
                                        </div>
                                    </template>
                                </div>
                            </div>
                            <template x-if="selectedSeller.businessPermit">
                                <div class="flex gap-2">
                                    <button type="button" @click="openPreview(selectedSeller.businessPermit, 'Business Permit')" class="flex-1 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-800 rounded-lg text-[10px] font-bold transition-all cursor-pointer text-center">
                                        Preview
                                    </button>
                                    <a :href="selectedSeller.businessPermit" target="_blank" download class="px-3 py-1.5 bg-[#3D2B1F] text-white hover:bg-[#C0422A] rounded-lg text-[10px] font-bold transition-all text-center">
                                        Download
                                    </a>
                                </div>
                            </template>
                        </div>

                        {{-- 3. BIR Document --}}
                        <div class="bg-white rounded-2xl border border-gray-200 p-4 flex flex-col justify-between space-y-3 shadow-sm hover:border-[#C0422A] transition-colors">
                            <div>
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-[10px] font-black uppercase tracking-wider text-gray-500">3. BIR / Tax Reg</span>
                                    <template x-if="selectedSeller.birDocument">
                                        <span class="text-[9px] font-bold text-green-700 bg-green-50 px-2 py-0.5 rounded-full border border-green-200">Attached</span>
                                    </template>
                                    <template x-if="!selectedSeller.birDocument">
                                        <span class="text-[9px] font-bold text-red-600 bg-red-50 px-2 py-0.5 rounded-full border border-red-200">Missing</span>
                                    </template>
                                </div>
                                <div class="h-36 rounded-xl bg-gray-50 border border-gray-100 flex items-center justify-center overflow-hidden relative group">
                                    <template x-if="selectedSeller.birDocument">
                                        <img :src="selectedSeller.birDocument" class="w-full h-full object-cover group-hover:scale-105 transition-transform cursor-pointer" @click="openPreview(selectedSeller.birDocument, 'BIR Document')">
                                    </template>
                                    <template x-if="!selectedSeller.birDocument">
                                        <div class="text-center p-3 text-gray-400">
                                            <svg class="w-8 h-8 mx-auto mb-1 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                            <span class="text-[10px] font-bold">No file uploaded</span>
                                        </div>
                                    </template>
                                </div>
                            </div>
                            <template x-if="selectedSeller.birDocument">
                                <div class="flex gap-2">
                                    <button type="button" @click="openPreview(selectedSeller.birDocument, 'BIR Document')" class="flex-1 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-800 rounded-lg text-[10px] font-bold transition-all cursor-pointer text-center">
                                        Preview
                                    </button>
                                    <a :href="selectedSeller.birDocument" target="_blank" download class="px-3 py-1.5 bg-[#3D2B1F] text-white hover:bg-[#C0422A] rounded-lg text-[10px] font-bold transition-all text-center">
                                        Download
                                    </a>
                                </div>
                            </template>
                        </div>

                    </div>
                </div>

            </div>

            {{-- Modal Footer --}}
            <div class="p-6 bg-gray-50 border-t border-gray-100 flex flex-wrap items-center justify-between gap-3 sticky bottom-0 z-20">
                <div class="flex items-center gap-2">
                    <template x-if="!selectedSeller.isVerified">
                        <form :action="'/superadmin/sellers/' + selectedSeller.id + '/verify'" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-black uppercase tracking-wider transition-all cursor-pointer shadow-sm flex items-center gap-1.5">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                <span>Verify &amp; Approve</span>
                            </button>
                        </form>
                    </template>
                    <template x-if="!selectedSeller.isVerified">
                        <button type="button" @click="reviewModal = false; openReject(selectedSeller.id, selectedSeller.name)" class="px-5 py-2.5 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 rounded-xl text-xs font-black uppercase tracking-wider transition-all cursor-pointer">
                            Reject Application
                        </button>
                    </template>
                </div>
                <button type="button" @click="reviewModal = false" class="px-5 py-2.5 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-xl text-xs font-bold transition-all cursor-pointer">
                    Close
                </button>
            </div>
        </div>
    </div>

    {{-- ═══ 2. FULL DOCUMENT ZOOM PREVIEW MODAL ═══ --}}
    <div x-show="previewModal" 
         x-cloak 
         style="display: none;"
         class="fixed inset-0 z-60 flex items-center justify-center p-4 bg-black/85 backdrop-blur-md"
         @click="previewModal = false">
        <div class="relative max-w-4xl max-h-[92vh] p-2 bg-white rounded-3xl shadow-2xl overflow-hidden" @click.stop>
            <div class="p-4 bg-gray-50 border-b border-gray-100 flex items-center justify-between">
                <span class="text-xs font-bold text-gray-900" x-text="previewTitle"></span>
                <button @click="previewModal = false" class="w-8 h-8 rounded-full bg-gray-200 text-gray-700 flex items-center justify-center hover:bg-gray-300 transition-colors cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="p-4 overflow-auto max-h-[80vh] flex items-center justify-center bg-gray-100">
                <img :src="previewSrc" class="max-h-[75vh] max-w-full rounded-xl object-contain shadow-md">
            </div>
        </div>
    </div>

    {{-- ═══ 3. FREEZE SHOP MODAL (SUPER ADMIN EXCLUSIVE) ═══ --}}
    <div x-show="freezeModal" 
         x-cloak 
         style="display: none;"
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95">
        <div class="bg-white rounded-3xl p-6 sm:p-8 max-w-md w-full shadow-2xl border border-gray-100" @click.away="freezeModal = false">
            <div class="w-14 h-14 rounded-2xl bg-orange-50 text-orange-600 flex items-center justify-center mx-auto mb-4 border border-orange-100">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
            </div>
            <h3 class="font-serif text-xl font-bold text-center text-[#3D2B1F] mb-1">Freeze Artisan Shop</h3>
            <p class="text-xs text-gray-500 text-center mb-5">Temporarily restrict <span class="font-bold text-gray-900" x-text="freezeShopName"></span>'s store and listings due to overdue commission balances.</p>

            <form :action="'/superadmin/sellers/' + freezeShopId + '/freeze'" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-1.5">Reason for Freezing</label>
                    <textarea name="reason" rows="3" x-model="freezeReason" required
                              class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-xs font-medium focus:bg-white focus:outline-none focus:border-[#C0422A] transition-all"></textarea>
                </div>
                <div class="flex items-center gap-3 pt-2">
                    <button type="button" @click="freezeModal = false" class="flex-1 py-2.5 bg-gray-100 text-gray-700 text-xs font-bold rounded-xl hover:bg-gray-200 transition-all cursor-pointer">Cancel</button>
                    <button type="submit" class="flex-1 py-2.5 bg-orange-600 text-white text-xs font-bold rounded-xl hover:bg-orange-700 transition-all cursor-pointer shadow-xs">Freeze Shop</button>
                </div>
            </form>
        </div>
    </div>

    {{-- ═══ 4. SUSPEND SELLER MODAL ═══ --}}
    <div x-show="suspendModal" 
         x-cloak 
         style="display: none;"
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95">
        <div class="bg-white rounded-3xl p-6 sm:p-8 max-w-md w-full shadow-2xl border border-gray-100" @click.away="suspendModal = false">
            <div class="w-14 h-14 rounded-2xl bg-red-50 text-red-600 flex items-center justify-center mx-auto mb-4 border border-red-100">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
            </div>
            <h3 class="font-serif text-xl font-bold text-center text-[#3D2B1F] mb-1">Suspend Seller Account</h3>
            <p class="text-xs text-gray-500 text-center mb-5">Suspend <span class="font-bold text-gray-900" x-text="suspendSellerName"></span>'s account due to terms of service or policy violations.</p>

            <form :action="'/superadmin/sellers/' + suspendSellerId + '/suspend'" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-1.5">Violation Reason <span class="text-red-500">*</span></label>
                    <textarea name="reason" rows="3" required placeholder="Describe the policy violation or reason for suspension..."
                              class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-xs font-medium focus:bg-white focus:outline-none focus:border-[#C0422A] transition-all"></textarea>
                </div>
                <div class="flex items-center gap-3 pt-2">
                    <button type="button" @click="suspendModal = false" class="flex-1 py-2.5 bg-gray-100 text-gray-700 text-xs font-bold rounded-xl hover:bg-gray-200 transition-all cursor-pointer">Cancel</button>
                    <button type="submit" class="flex-1 py-2.5 bg-red-600 text-white text-xs font-bold rounded-xl hover:bg-red-700 transition-all cursor-pointer shadow-xs">Suspend Seller</button>
                </div>
            </form>
        </div>
    </div>

    {{-- ═══ 5. REJECT APPLICATION MODAL ═══ --}}
    <div x-show="rejectModal" 
         x-cloak 
         style="display: none;"
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95">
        <div class="bg-white rounded-3xl p-6 sm:p-8 max-w-md w-full shadow-2xl border border-gray-100" @click.away="rejectModal = false">
            <div class="w-14 h-14 rounded-2xl bg-rose-50 text-rose-600 flex items-center justify-center mx-auto mb-4 border border-rose-100">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </div>
            <h3 class="font-serif text-xl font-bold text-center text-[#3D2B1F] mb-1">Reject Application</h3>
            <p class="text-xs text-gray-500 text-center mb-5">Provide a detailed reason for rejecting <span class="font-bold text-gray-900" x-text="rejectSellerName"></span>'s seller registration.</p>

            <form :action="'/superadmin/sellers/' + rejectSellerId + '/reject'" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-1.5">Rejection Reason <span class="text-red-500">*</span></label>
                    <textarea name="reason" rows="3" required placeholder="e.g. Incomplete business permit, illegible proof of residency..."
                              class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-xs font-medium focus:bg-white focus:outline-none focus:border-[#C0422A] transition-all"></textarea>
                </div>
                <div class="flex items-center gap-3 pt-2">
                    <button type="button" @click="rejectModal = false" class="flex-1 py-2.5 bg-gray-100 text-gray-700 text-xs font-bold rounded-xl hover:bg-gray-200 transition-all cursor-pointer">Cancel</button>
                    <button type="submit" class="flex-1 py-2.5 bg-rose-600 text-white text-xs font-bold rounded-xl hover:bg-rose-700 transition-all cursor-pointer shadow-xs">Reject Application</button>
                </div>
            </form>
        </div>
    </div>

    {{-- ═══ 6. DELETE / ARCHIVE SELLER MODAL ═══ --}}
    <div x-show="deleteModal" 
         x-cloak 
         style="display: none;"
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95">
        <div class="bg-white rounded-3xl p-6 sm:p-8 max-w-md w-full shadow-2xl border border-gray-100" @click.away="deleteModal = false">
            <div class="w-14 h-14 rounded-2xl bg-red-50 text-red-600 flex items-center justify-center mx-auto mb-4 border border-red-100">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
            </div>
            <h3 class="font-serif text-xl font-bold text-center text-[#3D2B1F] mb-1">Delete &amp; Archive Seller</h3>
            <p class="text-xs text-gray-500 text-center mb-5">This will archive <span class="font-bold text-gray-900" x-text="deleteSellerName"></span> and deactivate all products associated with their shop.</p>

            <form :action="'/superadmin/sellers/' + deleteSellerId" method="POST" class="space-y-4">
                @csrf
                @method('DELETE')
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-1.5">Deletion Reason</label>
                    <input type="text" name="reason" placeholder="Administrative deletion"
                           class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-xs font-medium focus:bg-white focus:outline-none focus:border-[#C0422A] transition-all">
                </div>
                <div class="flex items-center gap-3 pt-2">
                    <button type="button" @click="deleteModal = false" class="flex-1 py-2.5 bg-gray-100 text-gray-700 text-xs font-bold rounded-xl hover:bg-gray-200 transition-all cursor-pointer">Cancel</button>
                    <button type="submit" class="flex-1 py-2.5 bg-red-600 text-white text-xs font-bold rounded-xl hover:bg-red-700 transition-all cursor-pointer shadow-xs">Confirm Delete</button>
                </div>
            </form>
        </div>
    </div>

</div>

<script>
function superSellerManager() {
    return {
        reviewModal: false,
        selectedSeller: {},
        previewModal: false,
        previewSrc: '',
        previewTitle: '',
        freezeModal: false,
        freezeShopId: '',
        freezeShopName: '',
        freezeReason: '',
        suspendModal: false,
        suspendSellerId: '',
        suspendSellerName: '',
        rejectModal: false,
        rejectSellerId: '',
        rejectSellerName: '',
        deleteModal: false,
        deleteSellerId: '',
        deleteSellerName: '',
        openReview(seller) {
            this.selectedSeller = seller;
            this.reviewModal = true;
        },
        openPreview(src, title) {
            this.previewSrc = src;
            this.previewTitle = title;
            this.previewModal = true;
        },
        openFreeze(id, name) {
            this.freezeShopId = id;
            this.freezeShopName = name;
            this.freezeReason = 'Unpaid platform commission fee past grace period';
            this.freezeModal = true;
        },
        openSuspend(id, name) {
            this.suspendSellerId = id;
            this.suspendSellerName = name;
            this.suspendModal = true;
        },
        openReject(id, name) {
            this.rejectSellerId = id;
            this.rejectSellerName = name;
            this.rejectModal = true;
        },
        openDelete(id, name) {
            this.deleteSellerId = id;
            this.deleteSellerName = name;
            this.deleteModal = true;
        }
    };
}
</script>
@endsection
