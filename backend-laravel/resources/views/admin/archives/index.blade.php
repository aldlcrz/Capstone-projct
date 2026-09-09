@extends('layouts.admin')

@section('content')
<div class="space-y-4" x-data="{
    // Snapshot Inspection Modal State
    snapshotModal: false,
    inspectRecord: null,
    openSnapshot(record) {
        this.inspectRecord = record;
        this.snapshotModal = true;
    },

    // Restore Modal State
    restoreModal: false,
    restoreId: null,
    restoreName: '',
    restoreType: '',
    openRestore(record) {
        this.restoreId = record.id;
        this.restoreName = record.name;
        this.restoreType = record.item_type;
        this.restoreModal = true;
    },

    // Purge Modal State
    purgeModal: false,
    purgeId: null,
    purgeName: '',
    purgeType: '',
    openPurge(record) {
        this.purgeId = record.id;
        this.purgeName = record.name;
        this.purgeType = record.item_type;
        this.purgeModal = true;
    },

    getRecordImage(record) {
        if (!record || !record.metadata) return null;
        const meta = record.metadata;
        if (record.item_type === 'product' && meta.image) {
            let img = meta.image;
            let path = Array.isArray(img) ? (img[0] ? (img[0].url || img[0]) : '') : img;
            if (!path) return '/uploads/products/default.jpg';
            if (path.startsWith('http') || path.startsWith('data:')) return path;
            if (path.startsWith('/storage/') || path.startsWith('/uploads/')) return path;
            if (path.startsWith('storage/') || path.startsWith('uploads/')) return '/' + path;
            return '/storage/' + path.replace(/^\//, '');
        }
        if (record.item_type === 'category' && meta.image) {
            let path = meta.image;
            if (path.startsWith('http') || path.startsWith('data:')) return path;
            if (path.startsWith('/')) return path;
            return '/' + path;
        }
        if ((record.item_type === 'customer' || record.item_type === 'seller') && meta.profilePhoto) {
            let path = meta.profilePhoto;
            if (path.startsWith('http') || path.startsWith('data:')) return path;
            if (path.startsWith('/storage/')) return path;
            if (path.startsWith('storage/')) return '/' + path;
            return '/storage/' + path.replace(/^\//, '');
        }
        return null;
    },

    showRawMetadata: false,
    formatDate(str) {
        if (!str) return '—';
        try {
            const d = new Date(str);
            if (isNaN(d.getTime())) return str;
            return d.toLocaleString('en-US', {
                month: 'short',
                day: 'numeric',
                year: 'numeric',
                hour: 'numeric',
                minute: '2-digit',
                hour12: true
            });
        } catch (e) {
            return str;
        }
    },
    formatPrice(val) {
        if (val === undefined || val === null || val === '' || isNaN(val)) return '—';
        return '₱' + Number(val).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    },
    getMetadataEntries(meta) {
        if (!meta || typeof meta !== 'object') return [];
        const ignored = ['password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes'];
        return Object.entries(meta).filter(([k]) => !ignored.includes(k));
    },
    formatMetaVal(val) {
        if (val === null || val === undefined) return 'null';
        if (typeof val === 'boolean') return val ? 'true' : 'false';
        if (typeof val === 'object') return JSON.stringify(val);
        return String(val);
    }
}">

    {{-- ═══ PAGE HEADER + SEARCH BAR ═══ --}}
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
        {{-- Left: Title & Subtitle --}}
        <div class="text-left space-y-0.5 shrink-0">
            <div class="inline-flex items-center gap-2">
                <span class="text-[9px] font-black uppercase tracking-[0.25em] text-[#C0422A]">Archive Hub</span>
                <span class="text-gray-300 text-xs">·</span>
                <span class="text-[9px] font-bold uppercase tracking-[0.2em] text-gray-400">System Governance</span>
            </div>
            <h1 class="font-serif text-2xl sm:text-3xl font-bold text-gray-900 leading-tight">
                Deleted <span class="text-[#C0420A] font-light italic">Registry</span>
            </h1>
            <p class="text-[11px] text-gray-400 font-medium">Soft-deleted records — eligible for restoration or permanent purge</p>
        </div>

        {{-- Larger Search Bar in front of Header --}}
        <div class="flex-1 max-w-xl lg:max-w-2xl w-full">
            <form method="GET" class="flex items-center gap-2 w-full">
                @if(request('type'))
                    <input type="hidden" name="type" value="{{ request('type') }}">
                @endif
                <div class="relative w-full">
                    <input type="text" name="search" value="{{ request('search') }}"
                           placeholder="Search by name, identifier, reason, or admin..."
                           class="w-full pl-11 pr-5 py-2.5 bg-white border border-gray-200 rounded-full text-sm text-gray-800 placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-[#C0422A]/20 focus:border-[#C0422A] shadow-xs transition-all">
                    <svg class="w-5 h-5 text-gray-400 absolute left-4 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>
                @if(request('search'))
                    <a href="{{ request()->fullUrlWithQuery(['search' => null, 'page' => 1]) }}" class="px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-full text-xs font-bold transition-all shrink-0">Clear</a>
                @endif
            </form>
        </div>
    </div>

    {{-- ═══ STATS BAR / RECORD TYPE FILTERS ═══ --}}
    @php
        $currentType = request('type', 'all');
        $isAll      = $currentType === 'all' || empty($currentType);
        $isProduct  = $currentType === 'product';
        $isCategory = $currentType === 'category';
        $isCustomer = $currentType === 'customer';
        $isSeller   = $currentType === 'seller';
    @endphp
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3">
        {{-- Total / All --}}
        <a href="{{ request()->fullUrlWithQuery(['type' => 'all', 'page' => 1]) }}"
           class="group relative rounded-2xl px-3.5 py-3 flex items-center justify-between border transition-all duration-200 cursor-pointer {{ $isAll ? 'bg-white border-gray-900 ring-2 ring-gray-900/15 shadow-sm -translate-y-0.5' : 'bg-white border-gray-100 hover:border-gray-300 hover:shadow-sm hover:-translate-y-0.5' }}">
            <div class="flex items-center gap-2.5 min-w-0">
                <div class="w-8 h-8 rounded-xl flex items-center justify-center shrink-0 transition-colors {{ $isAll ? 'bg-gray-900 text-white' : 'bg-gray-100 text-gray-500 group-hover:bg-gray-200' }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/></svg>
                </div>
                <div class="min-w-0">
                    <div class="text-base sm:text-lg font-black text-gray-900 leading-none">{{ $counts['all'] ?? 0 }}</div>
                    <div class="text-[9px] font-bold uppercase tracking-wider text-gray-400 mt-0.5 truncate">Total</div>
                </div>
            </div>
            @if($isAll)
                <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-full text-[8px] font-black uppercase tracking-wider bg-gray-900 text-white shadow-xs shrink-0">
                    <span class="w-1 h-1 rounded-full bg-emerald-400"></span> Active
                </span>
            @endif
        </a>

        {{-- Products --}}
        <a href="{{ request()->fullUrlWithQuery(['type' => 'product', 'page' => 1]) }}"
           class="group relative rounded-2xl px-3.5 py-3 flex items-center justify-between border transition-all duration-200 cursor-pointer {{ $isProduct ? 'bg-purple-50/50 border-purple-600 ring-2 ring-purple-600/20 shadow-sm -translate-y-0.5' : 'bg-white border-purple-100 hover:border-purple-300 hover:shadow-sm hover:-translate-y-0.5' }}">
            <div class="flex items-center gap-2.5 min-w-0">
                <div class="w-8 h-8 rounded-xl flex items-center justify-center shrink-0 transition-colors {{ $isProduct ? 'bg-purple-600 text-white' : 'bg-purple-50 text-purple-600 group-hover:bg-purple-100' }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                </div>
                <div class="min-w-0">
                    <div class="text-base sm:text-lg font-black text-gray-900 leading-none">{{ $counts['product'] ?? 0 }}</div>
                    <div class="text-[9px] font-bold uppercase tracking-wider text-purple-500 mt-0.5 truncate">Products</div>
                </div>
            </div>
            @if($isProduct)
                <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-full text-[8px] font-black uppercase tracking-wider bg-purple-600 text-white shadow-xs shrink-0">
                    <span class="w-1 h-1 rounded-full bg-purple-200"></span> Active
                </span>
            @endif
        </a>

        {{-- Categories --}}
        <a href="{{ request()->fullUrlWithQuery(['type' => 'category', 'page' => 1]) }}"
           class="group relative rounded-2xl px-3.5 py-3 flex items-center justify-between border transition-all duration-200 cursor-pointer {{ $isCategory ? 'bg-blue-50/50 border-blue-600 ring-2 ring-blue-600/20 shadow-sm -translate-y-0.5' : 'bg-white border-blue-100 hover:border-blue-300 hover:shadow-sm hover:-translate-y-0.5' }}">
            <div class="flex items-center gap-2.5 min-w-0">
                <div class="w-8 h-8 rounded-xl flex items-center justify-center shrink-0 transition-colors {{ $isCategory ? 'bg-blue-600 text-white' : 'bg-blue-50 text-blue-600 group-hover:bg-blue-100' }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                </div>
                <div class="min-w-0">
                    <div class="text-base sm:text-lg font-black text-gray-900 leading-none">{{ $counts['category'] ?? 0 }}</div>
                    <div class="text-[9px] font-bold uppercase tracking-wider text-blue-500 mt-0.5 truncate">Categories</div>
                </div>
            </div>
            @if($isCategory)
                <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-full text-[8px] font-black uppercase tracking-wider bg-blue-600 text-white shadow-xs shrink-0">
                    <span class="w-1 h-1 rounded-full bg-blue-200"></span> Active
                </span>
            @endif
        </a>

        {{-- Customers --}}
        <a href="{{ request()->fullUrlWithQuery(['type' => 'customer', 'page' => 1]) }}"
           class="group relative rounded-2xl px-3.5 py-3 flex items-center justify-between border transition-all duration-200 cursor-pointer {{ $isCustomer ? 'bg-emerald-50/50 border-emerald-600 ring-2 ring-emerald-600/20 shadow-sm -translate-y-0.5' : 'bg-white border-emerald-100 hover:border-emerald-300 hover:shadow-sm hover:-translate-y-0.5' }}">
            <div class="flex items-center gap-2.5 min-w-0">
                <div class="w-8 h-8 rounded-xl flex items-center justify-center shrink-0 transition-colors {{ $isCustomer ? 'bg-emerald-600 text-white' : 'bg-emerald-50 text-emerald-600 group-hover:bg-emerald-100' }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                </div>
                <div class="min-w-0">
                    <div class="text-base sm:text-lg font-black text-gray-900 leading-none">{{ $counts['customer'] ?? 0 }}</div>
                    <div class="text-[9px] font-bold uppercase tracking-wider text-emerald-500 mt-0.5 truncate">Customers</div>
                </div>
            </div>
            @if($isCustomer)
                <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-full text-[8px] font-black uppercase tracking-wider bg-emerald-600 text-white shadow-xs shrink-0">
                    <span class="w-1 h-1 rounded-full bg-emerald-200"></span> Active
                </span>
            @endif
        </a>

        {{-- Sellers --}}
        <a href="{{ request()->fullUrlWithQuery(['type' => 'seller', 'page' => 1]) }}"
           class="group relative rounded-2xl px-3.5 py-3 flex items-center justify-between border transition-all duration-200 cursor-pointer {{ $isSeller ? 'bg-amber-50/50 border-amber-500 ring-2 ring-amber-500/20 shadow-sm -translate-y-0.5' : 'bg-white border-amber-100 hover:border-amber-300 hover:shadow-sm hover:-translate-y-0.5' }}">
            <div class="flex items-center gap-2.5 min-w-0">
                <div class="w-8 h-8 rounded-xl flex items-center justify-center shrink-0 transition-colors {{ $isSeller ? 'bg-amber-500 text-white' : 'bg-amber-50 text-amber-500 group-hover:bg-amber-100' }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                </div>
                <div class="min-w-0">
                    <div class="text-base sm:text-lg font-black text-gray-900 leading-none">{{ $counts['seller'] ?? 0 }}</div>
                    <div class="text-[9px] font-bold uppercase tracking-wider text-amber-500 mt-0.5 truncate">Sellers</div>
                </div>
            </div>
            @if($isSeller)
                <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-full text-[8px] font-black uppercase tracking-wider bg-amber-500 text-white shadow-xs shrink-0">
                    <span class="w-1 h-1 rounded-full bg-amber-200"></span> Active
                </span>
            @endif
        </a>
    </div>

    {{-- ═══ RESULTS COUNT BAR ═══ --}}
    <div class="flex items-center justify-between px-1">
        <div class="text-[11px] font-bold text-gray-400">
            Showing <span class="text-gray-900 font-black">{{ $archives->total() }}</span> {{ $archives->total() === 1 ? 'archived record' : 'archived records' }}
            <span class="text-gray-400">· Filtered by <span class="capitalize font-black text-gray-700">{{ $currentType === 'all' || empty($currentType) ? 'All Types' : $currentType }}</span></span>
            @if(request('search'))
                <span class="text-gray-400">· Matching "<span class="font-bold text-gray-700">{{ request('search') }}</span>"</span>
            @endif
        </div>
    </div>

    {{-- ═══ ARCHIVE TABLE ═══ --}}
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="overflow-x-auto no-scrollbar">
            <table class="w-full text-left min-w-160">
                <thead>
                    <tr class="border-b border-gray-100 bg-gray-50/60">
                        <th class="px-5 py-2.5 text-[9px] font-black uppercase tracking-widest text-gray-400 w-[36%]">Record &amp; Identifier</th>
                        <th class="px-4 py-2.5 text-[9px] font-black uppercase tracking-widest text-gray-400 w-[14%]">Type</th>
                        <th class="px-4 py-2.5 text-[9px] font-black uppercase tracking-widest text-gray-400 w-[18%] hidden md:table-cell">Archived Info</th>
                        <th class="px-4 py-2.5 text-[9px] font-black uppercase tracking-widest text-gray-400 w-[16%] hidden sm:table-cell">Retention</th>
                        <th class="px-5 py-2.5 text-[9px] font-black uppercase tracking-widest text-gray-400 w-[16%] text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($archives as $record)
                        @php
                            $typeConfig = [
                                'product'  => ['dot' => 'bg-purple-500',  'badge' => 'bg-purple-50 text-purple-700 border-purple-200/60',  'icon' => 'bg-purple-50 text-purple-600',  'emoji' => '📦'],
                                'category' => ['dot' => 'bg-blue-500',    'badge' => 'bg-blue-50 text-blue-700 border-blue-200/60',        'icon' => 'bg-blue-50 text-blue-600',      'emoji' => '🏷️'],
                                'customer' => ['dot' => 'bg-emerald-500', 'badge' => 'bg-emerald-50 text-emerald-700 border-emerald-200/60','icon' => 'bg-emerald-50 text-emerald-600', 'emoji' => '🧑'],
                                'seller'   => ['dot' => 'bg-amber-500',   'badge' => 'bg-amber-50 text-amber-700 border-amber-200/60',     'icon' => 'bg-amber-50 text-amber-600',    'emoji' => '🏪'],
                            ];
                            $tc = $typeConfig[$record->item_type] ?? ['dot' => 'bg-gray-400', 'badge' => 'bg-gray-50 text-gray-700 border-gray-200', 'icon' => 'bg-gray-50 text-gray-600', 'emoji' => '📄'];
                            $daysOld = (int) floor(abs($record->created_at->diffInDays(now())));
                            $daysLeft = max(0, 30 - $daysOld);
                            $expiryClass = $daysLeft <= 7 ? 'bg-red-50 text-red-700 border-red-200/80 font-black' : 'bg-gray-50 text-gray-600 border-gray-200/80 font-bold';
                        @endphp
                        <tr class="hover:bg-gray-50/50 transition-colors group">
                            {{-- Record & Identifier --}}
                            <td class="px-5 py-2">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-8 h-8 rounded-xl {{ $tc['icon'] }} border border-gray-100 flex items-center justify-center font-bold text-xs shrink-0 overflow-hidden shadow-2xs"
                                         style="width: 32px; height: 32px; min-width: 32px; min-height: 32px; max-width: 32px; max-height: 32px;">
                                        @if($record->item_type === 'product' && !empty($record->metadata['image']))
                                            @php
                                                $rawImg = $record->metadata['image'];
                                                $imgUrl = is_array($rawImg) ? ($rawImg[0]['url'] ?? $rawImg[0] ?? '') : $rawImg;
                                                if ($imgUrl && !str_starts_with($imgUrl, 'http') && !str_starts_with($imgUrl, '/')) {
                                                    $imgUrl = '/storage/' . ltrim($imgUrl, '/');
                                                }
                                            @endphp
                                            <img src="{{ $imgUrl }}" class="w-full h-full object-cover" style="width: 32px; height: 32px; min-width: 32px; min-height: 32px; max-width: 32px; max-height: 32px; object-fit: cover;" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex'">
                                            <span class="hidden w-full h-full items-center justify-center text-xs">{{ $tc['emoji'] }}</span>
                                        @elseif($record->item_type === 'category' && !empty($record->metadata['image']))
                                            @php
                                                $catImg = $record->metadata['image'];
                                                if ($catImg && !str_starts_with($catImg, 'http') && !str_starts_with($catImg, '/')) {
                                                    $catImg = '/' . ltrim($catImg, '/');
                                                }
                                            @endphp
                                            <img src="{{ $catImg }}" class="w-full h-full object-cover" style="width: 32px; height: 32px; min-width: 32px; min-height: 32px; max-width: 32px; max-height: 32px; object-fit: cover;" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex'">
                                            <span class="hidden w-full h-full items-center justify-center text-xs">{{ $tc['emoji'] }}</span>
                                        @elseif(($record->item_type === 'customer' || $record->item_type === 'seller') && !empty($record->metadata['profilePhoto']))
                                            @php
                                                $profImg = $record->metadata['profilePhoto'];
                                                if ($profImg && !str_starts_with($profImg, 'http') && !str_starts_with($profImg, '/')) {
                                                    $profImg = '/storage/' . ltrim($profImg, '/');
                                                }
                                            @endphp
                                            <img src="{{ $profImg }}" class="w-full h-full object-cover" style="width: 32px; height: 32px; min-width: 32px; min-height: 32px; max-width: 32px; max-height: 32px; object-fit: cover;" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex'">
                                            <span class="hidden w-full h-full items-center justify-center text-xs">{{ $tc['emoji'] }}</span>
                                        @else
                                            <span class="text-xs">{{ $tc['emoji'] }}</span>
                                        @endif
                                    </div>
                                    <div class="min-w-0">
                                        <div class="text-xs font-bold text-gray-900 truncate leading-tight">{{ $record->name }}</div>
                                        <div class="text-[10px] text-gray-400 font-medium truncate leading-tight mt-0.5 flex items-center gap-1">
                                            @if($record->identifier)
                                                <span class="truncate">{{ $record->identifier }}</span>
                                            @endif
                                            @if($record->item_type === 'product' && isset($record->metadata['price']))
                                                <span class="text-gray-300">·</span>
                                                <span class="text-[#C0422A] font-bold">₱{{ number_format((float)$record->metadata['price'], 2) }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </td>

                            {{-- Type --}}
                            <td class="px-4 py-2">
                                <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider border {{ $tc['badge'] }}">
                                    <span class="w-1.5 h-1.5 rounded-full {{ $tc['dot'] }}"></span>
                                    {{ $record->item_type }}
                                </span>
                            </td>

                            {{-- Archived Info --}}
                            <td class="px-4 py-2 hidden md:table-cell">
                                <div class="text-[11px] text-gray-600 font-medium leading-tight">
                                    <span>Deleted <strong class="text-gray-900 font-bold">{{ $daysOld === 0 ? 'today' : $daysOld . 'd ago' }}</strong></span>
                                    <span class="text-gray-300">·</span>
                                    <span class="text-gray-500">{{ $record->archived_by ?: 'Admin' }}</span>
                                </div>
                                @if($record->reason)
                                    <div class="text-[10px] text-gray-400 truncate max-w-xs mt-0.5">"{{ Str::limit($record->reason, 45) }}"</div>
                                @endif
                            </td>

                            {{-- Retention Expiry --}}
                            <td class="px-4 py-2 hidden sm:table-cell">
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] border {{ $expiryClass }}">
                                    <span>⏱</span>
                                    <span>{{ $daysLeft }}d left</span>
                                </span>
                            </td>

                            {{-- Actions --}}
                            <td class="px-5 py-2">
                                <div class="flex items-center justify-end gap-1.5">
                                    <button type="button" @click="openSnapshot({{ json_encode($record) }})"
                                        class="px-2.5 py-1 bg-stone-50 text-stone-700 border border-stone-200/80 rounded-lg text-[10px] font-bold uppercase tracking-wider hover:bg-stone-200 transition-all cursor-pointer"
                                        title="Inspect Record Details">
                                        Inspect
                                    </button>
                                    <button type="button" @click="openRestore({{ json_encode($record) }})"
                                        class="px-2.5 py-1 bg-emerald-50 text-emerald-700 border border-emerald-200/80 rounded-lg text-[10px] font-bold uppercase tracking-wider hover:bg-emerald-600 hover:text-white transition-all cursor-pointer"
                                        title="Restore Record">
                                        Restore
                                    </button>
                                    <button type="button" @click="openPurge({{ json_encode($record) }})"
                                        class="px-2.5 py-1 bg-rose-50 text-rose-600 border border-rose-200/80 rounded-lg text-[10px] font-bold uppercase tracking-wider hover:bg-rose-600 hover:text-white transition-all cursor-pointer"
                                        title="Permanently Purge">
                                        Purge
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">
                                <div class="py-10 text-center">
                                    <div class="w-12 h-12 bg-gray-50 rounded-2xl flex items-center justify-center mx-auto mb-2.5 border border-gray-100">
                                        <svg class="w-5 h-5 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/></svg>
                                    </div>
                                    <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">No archived records found</p>
                                    <p class="text-[10px] text-gray-300 mt-0.5">Try adjusting your search or filter criteria</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{-- Pagination --}}
        <div class="px-5 py-2.5 border-t border-gray-50 bg-gray-50/30">
            {{ $archives->withQueryString()->links() }}
        </div>
    </div>

    {{-- ─── Snapshot Inspection Modal ─── --}}
    <div x-show="snapshotModal" 
         x-cloak 
         class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4 bg-black/60 backdrop-blur-sm"
         @keydown.escape.window="snapshotModal = false">
        <div class="relative bg-white rounded-3xl shadow-2xl w-full max-w-2xl max-h-[90vh] flex flex-col overflow-hidden border border-gray-100 text-left"
             @click.away="snapshotModal = false">
            
            {{-- Modal Header --}}
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between gap-3 shrink-0"
                 style="background-color: #FAF7F2;">
                <div class="flex items-center gap-2.5 flex-wrap min-w-0">
                    <span class="text-xs font-bold uppercase tracking-wider text-gray-900">
                        Archive Snapshot
                    </span>
                    <span class="text-gray-300">•</span>
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider shadow-2xs"
                          :class="{
                              'bg-purple-100 text-purple-800 border border-purple-200': inspectRecord?.item_type === 'product',
                              'bg-blue-100 text-blue-800 border border-blue-200': inspectRecord?.item_type === 'category',
                              'bg-emerald-100 text-emerald-800 border border-emerald-200': inspectRecord?.item_type === 'customer',
                              'bg-amber-100 text-amber-800 border border-amber-200': inspectRecord?.item_type === 'seller',
                              'bg-gray-100 text-gray-800 border border-gray-200': !['product', 'category', 'customer', 'seller'].includes(inspectRecord?.item_type)
                          }"
                          x-text="(inspectRecord?.item_type || 'record').toUpperCase()">
                    </span>
                    <template x-if="inspectRecord?.identifier">
                        <span class="text-[11px] text-gray-500 font-medium truncate" x-text="inspectRecord.identifier"></span>
                    </template>
                </div>

                <button type="button" @click="snapshotModal = false" 
                        class="w-8 h-8 rounded-full bg-stone-100 hover:bg-stone-200 text-gray-500 hover:text-black flex items-center justify-center transition-colors cursor-pointer shrink-0">
                    ✕
                </button>
            </div>

            {{-- Modal Body (Scrollable) --}}
            <div class="p-5 sm:p-6 overflow-y-auto flex-1 space-y-5" x-show="inspectRecord">
                
                {{-- Title & Meta Header Card --}}
                <div class="flex items-start gap-4">
                    {{-- Media preview if available --}}
                    <template x-if="getRecordImage(inspectRecord)">
                        <div class="w-16 h-16 sm:w-20 sm:h-20 rounded-2xl bg-stone-100 border border-gray-200/80 overflow-hidden shrink-0 shadow-xs"
                             style="width: 72px; height: 72px; min-width: 72px; min-height: 72px; max-width: 72px; max-height: 72px;">
                            <img :src="getRecordImage(inspectRecord)" 
                                 class="w-full h-full object-cover" 
                                 style="width: 72px; height: 72px; min-width: 72px; min-height: 72px; max-width: 72px; max-height: 72px; object-fit: cover;"
                                 onerror="this.style.display='none'">
                        </div>
                    </template>
                    <div class="min-w-0 flex-1">
                        <h2 class="font-serif text-lg sm:text-xl font-bold text-gray-900 leading-snug wrap-break-word" x-text="inspectRecord?.name"></h2>
                        <div class="flex flex-wrap items-center gap-x-2 gap-y-1 text-[11px] text-gray-500 mt-1">
                            <span>Archived <strong class="text-gray-700" x-text="formatDate(inspectRecord?.created_at)"></strong></span>
                            <span class="text-gray-300">·</span>
                            <span>By <strong class="text-gray-700" x-text="inspectRecord?.archived_by || 'Admin'"></strong></span>
                            <template x-if="inspectRecord?.item_id">
                                <span class="text-gray-400 text-[10px]" x-text="'(Orig ID: #' + inspectRecord.item_id + ')'"></span>
                            </template>
                        </div>
                    </div>
                </div>

                {{-- Recorded Deletion Reason --}}
                <div class="p-3.5 bg-rose-50/70 text-rose-950 rounded-2xl border border-rose-200/80 space-y-1">
                    <div class="flex items-center gap-1.5 text-rose-700 text-[10px] font-black uppercase tracking-wider">
                        <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        <span>Recorded Deletion Reason</span>
                    </div>
                    <p class="text-xs font-medium leading-relaxed" x-text="inspectRecord?.reason || 'None specified'"></p>
                </div>

                {{-- TYPE 1: PRODUCT SNAPSHOT DETAILS --}}
                <template x-if="inspectRecord?.item_type === 'product'">
                    <div class="space-y-4">
                        <h3 class="text-xs font-black uppercase tracking-wider text-gray-400 flex items-center gap-2">
                            <span>Product Snapshot Data</span>
                            <span class="flex-1 h-px bg-gray-100"></span>
                        </h3>

                        {{-- Metric Tiles --}}
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5">
                            <div class="p-3 rounded-xl bg-stone-50 border border-stone-200/60">
                                <div class="text-[10px] font-bold uppercase text-stone-500">Price</div>
                                <div class="text-base font-black text-[#C0422A] mt-0.5" x-text="formatPrice(inspectRecord?.metadata?.price)"></div>
                            </div>
                            <div class="p-3 rounded-xl bg-stone-50 border border-stone-200/60">
                                <div class="text-[10px] font-bold uppercase text-stone-500">Stock Qty</div>
                                <div class="text-base font-black text-gray-900 mt-0.5" x-text="inspectRecord?.metadata?.stock ?? '0'"></div>
                            </div>
                            <div class="p-3 rounded-xl bg-stone-50 border border-stone-200/60">
                                <div class="text-[10px] font-bold uppercase text-stone-500">SKU</div>
                                <div class="text-xs font-bold text-gray-800 mt-1 truncate" x-text="inspectRecord?.metadata?.sku || inspectRecord?.identifier || '—'"></div>
                            </div>
                            <div class="p-3 rounded-xl bg-stone-50 border border-stone-200/60">
                                <div class="text-[10px] font-bold uppercase text-stone-500">Status</div>
                                <div class="text-xs font-bold capitalize mt-1" 
                                     :class="(inspectRecord?.metadata?.status || '') === 'approved' ? 'text-emerald-700' : 'text-gray-700'"
                                     x-text="inspectRecord?.metadata?.status || 'Active'"></div>
                            </div>
                        </div>

                        {{-- Description --}}
                        <div class="p-3.5 rounded-xl bg-gray-50 border border-gray-100 space-y-1">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-gray-400 block">Product Description</span>
                            <p class="text-xs text-gray-700 leading-relaxed whitespace-pre-line" 
                               x-text="inspectRecord?.metadata?.description || 'No description was provided for this product.'"></p>
                        </div>

                        {{-- Additional Relationships / Attributes --}}
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 text-xs">
                            <div class="p-3 rounded-xl bg-white border border-gray-200/80 flex items-center justify-between">
                                <span class="text-gray-500 font-medium">Category ID</span>
                                <span class="font-bold text-gray-900" x-text="inspectRecord?.metadata?.category_id || '—'"></span>
                            </div>
                            <div class="p-3 rounded-xl bg-white border border-gray-200/80 flex items-center justify-between">
                                <span class="text-gray-500 font-medium">Seller ID</span>
                                <span class="font-bold text-gray-900" x-text="inspectRecord?.metadata?.seller_id || '—'"></span>
                            </div>
                            <template x-if="inspectRecord?.metadata?.is_on_sale">
                                <div class="p-3 rounded-xl bg-rose-50 border border-rose-200/70 flex items-center justify-between col-span-full">
                                    <span class="text-rose-700 font-bold">On Sale Promotion</span>
                                    <span class="font-black text-rose-800" x-text="(inspectRecord?.metadata?.discount_percentage || 0) + '% Discount'"></span>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>

                {{-- TYPE 2: CATEGORY SNAPSHOT DETAILS --}}
                <template x-if="inspectRecord?.item_type === 'category'">
                    <div class="space-y-4">
                        <h3 class="text-xs font-black uppercase tracking-wider text-gray-400 flex items-center gap-2">
                            <span>Category Snapshot Data</span>
                            <span class="flex-1 h-px bg-gray-100"></span>
                        </h3>

                        {{-- Attributes --}}
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                            <div class="p-3 rounded-xl bg-stone-50 border border-stone-200/60">
                                <span class="text-[10px] font-bold uppercase text-stone-500 block">Category Name</span>
                                <span class="text-sm font-bold text-gray-900 mt-0.5 block" x-text="inspectRecord?.metadata?.name || inspectRecord?.name"></span>
                            </div>
                            <div class="p-3 rounded-xl bg-stone-50 border border-stone-200/60">
                                <span class="text-[10px] font-bold uppercase text-stone-500 block">Slug / Target Group</span>
                                <span class="text-xs font-bold text-gray-800 mt-1 block" x-text="Array.isArray(inspectRecord?.metadata?.target_group) ? inspectRecord.metadata.target_group.join(', ') : (inspectRecord?.identifier || 'General')"></span>
                            </div>
                        </div>

                        {{-- Description --}}
                        <div class="p-3.5 rounded-xl bg-gray-50 border border-gray-100 space-y-1">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-gray-400 block">Description</span>
                            <p class="text-xs text-gray-700 leading-relaxed" 
                               x-text="inspectRecord?.metadata?.description || 'No description was recorded for this category.'"></p>
                        </div>
                    </div>
                </template>

                {{-- TYPE 3: CUSTOMER SNAPSHOT DETAILS --}}
                <template x-if="inspectRecord?.item_type === 'customer'">
                    <div class="space-y-4">
                        <h3 class="text-xs font-black uppercase tracking-wider text-gray-400 flex items-center gap-2">
                            <span>Customer Snapshot Data</span>
                            <span class="flex-1 h-px bg-gray-100"></span>
                        </h3>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 text-xs">
                            <div class="p-3 rounded-xl bg-white border border-gray-200/80">
                                <span class="text-[10px] font-bold uppercase text-gray-400 block">Full Name</span>
                                <span class="font-bold text-gray-900 text-sm mt-0.5 block" x-text="inspectRecord?.metadata?.name || inspectRecord?.name"></span>
                            </div>
                            <div class="p-3 rounded-xl bg-white border border-gray-200/80">
                                <span class="text-[10px] font-bold uppercase text-gray-400 block">Email Address</span>
                                <span class="font-bold text-gray-900 mt-0.5 block truncate" x-text="inspectRecord?.metadata?.email || inspectRecord?.identifier"></span>
                            </div>
                            <div class="p-3 rounded-xl bg-white border border-gray-200/80">
                                <span class="text-[10px] font-bold uppercase text-gray-400 block">Phone Number</span>
                                <span class="font-bold text-gray-900 mt-0.5 block" x-text="inspectRecord?.metadata?.mobileNumber || 'Not provided'"></span>
                            </div>
                            <div class="p-3 rounded-xl bg-white border border-gray-200/80">
                                <span class="text-[10px] font-bold uppercase text-gray-400 block">Account Status at Deletion</span>
                                <span class="font-bold capitalize mt-0.5 block" 
                                      :class="(inspectRecord?.metadata?.status || '') === 'active' ? 'text-emerald-600' : 'text-rose-600'"
                                      x-text="inspectRecord?.metadata?.status || 'Active'"></span>
                            </div>
                            <template x-if="inspectRecord?.metadata?.created_at">
                                <div class="p-3 rounded-xl bg-white border border-gray-200/80 col-span-full">
                                    <span class="text-[10px] font-bold uppercase text-gray-400 block">Account Registration Date</span>
                                    <span class="font-bold text-gray-700 mt-0.5 block" x-text="formatDate(inspectRecord.metadata.created_at)"></span>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>

                {{-- TYPE 4: SELLER SNAPSHOT DETAILS --}}
                <template x-if="inspectRecord?.item_type === 'seller'">
                    <div class="space-y-4">
                        <h3 class="text-xs font-black uppercase tracking-wider text-gray-400 flex items-center gap-2">
                            <span>Artisan / Seller Snapshot Data</span>
                            <span class="flex-1 h-px bg-gray-100"></span>
                        </h3>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 text-xs">
                            <div class="p-3 rounded-xl bg-white border border-gray-200/80">
                                <span class="text-[10px] font-bold uppercase text-gray-400 block">Shop / Business Name</span>
                                <span class="font-bold text-gray-900 text-sm mt-0.5 block" x-text="inspectRecord?.metadata?.shopName || inspectRecord?.name"></span>
                            </div>
                            <div class="p-3 rounded-xl bg-white border border-gray-200/80">
                                <span class="text-[10px] font-bold uppercase text-gray-400 block">Owner / Contact Name</span>
                                <span class="font-bold text-gray-900 mt-0.5 block" x-text="inspectRecord?.metadata?.name || '—'"></span>
                            </div>
                            <div class="p-3 rounded-xl bg-white border border-gray-200/80">
                                <span class="text-[10px] font-bold uppercase text-gray-400 block">Email Address</span>
                                <span class="font-bold text-gray-900 mt-0.5 block truncate" x-text="inspectRecord?.metadata?.email || inspectRecord?.identifier"></span>
                            </div>
                            <div class="p-3 rounded-xl bg-white border border-gray-200/80">
                                <span class="text-[10px] font-bold uppercase text-gray-400 block">Phone / Mobile</span>
                                <span class="font-bold text-gray-900 mt-0.5 block" x-text="inspectRecord?.metadata?.mobileNumber || 'Not provided'"></span>
                            </div>
                            <div class="p-3 rounded-xl bg-white border border-gray-200/80">
                                <span class="text-[10px] font-bold uppercase text-gray-400 block">GCash Account</span>
                                <span class="font-bold text-gray-900 mt-0.5 block" x-text="inspectRecord?.metadata?.gcashNumber || 'Not provided'"></span>
                            </div>
                            <div class="p-3 rounded-xl bg-white border border-gray-200/80">
                                <span class="text-[10px] font-bold uppercase text-gray-400 block">Verification Status</span>
                                <span class="font-bold mt-0.5 block" 
                                      :class="inspectRecord?.metadata?.isVerified ? 'text-emerald-600' : 'text-amber-600'"
                                      x-text="inspectRecord?.metadata?.isVerified ? 'Verified Artisan' : 'Pending Verification'"></span>
                            </div>
                            <template x-if="inspectRecord?.metadata?.shopAddress">
                                <div class="p-3 rounded-xl bg-white border border-gray-200/80 col-span-full">
                                    <span class="text-[10px] font-bold uppercase text-gray-400 block">Workshop / Shop Address</span>
                                    <span class="font-medium text-gray-800 mt-0.5 block" x-text="inspectRecord.metadata.shopAddress"></span>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>

                {{-- ALL METADATA KEY-VALUE TABLE (EXPANDABLE) --}}
                <div class="pt-2 border-t border-gray-100">
                    <button type="button" 
                            @click="showRawMetadata = !showRawMetadata"
                            class="flex items-center justify-between w-full p-2.5 rounded-xl bg-stone-50 hover:bg-stone-100 transition-colors text-xs font-bold text-stone-700 cursor-pointer">
                        <span class="flex items-center gap-1.5">
                            <span>🔍 Complete Raw Snapshot Attributes</span>
                            <span class="text-[10px] font-normal text-stone-500" x-text="'(' + getMetadataEntries(inspectRecord?.metadata).length + ' fields)'"></span>
                        </span>
                        <svg class="w-4 h-4 transition-transform text-stone-500" :class="{ 'rotate-180': showRawMetadata }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>

                    <div x-show="showRawMetadata" x-transition class="mt-2.5 rounded-2xl border border-gray-200/80 overflow-hidden text-xs">
                        <div class="max-h-60 overflow-y-auto divide-y divide-gray-100 bg-white">
                            <template x-for="[k, v] in getMetadataEntries(inspectRecord?.metadata)" :key="k">
                                <div class="px-3.5 py-2 flex items-start justify-between gap-3 hover:bg-stone-50/50">
                                    <span class="font-mono text-[11px] font-bold text-gray-600 shrink-0 select-all" x-text="k"></span>
                                    <span class="font-mono text-[11px] text-gray-900 text-right break-all max-w-[65%] select-all" x-text="formatMetaVal(v)"></span>
                                </div>
                            </template>
                            <template x-if="getMetadataEntries(inspectRecord?.metadata).length === 0">
                                <div class="p-4 text-center text-gray-400 text-xs">No extra metadata attributes recorded.</div>
                            </template>
                        </div>
                    </div>
                </div>

            </div>

            {{-- Modal Footer --}}
            <div class="px-6 py-3.5 border-t border-gray-100 flex flex-wrap items-center justify-between gap-2.5 shrink-0"
                 style="background-color: #FAF7F2;">
                <div class="text-[11px] text-gray-400 font-medium">
                    Eligible for 30-day restore cycle
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" 
                            @click="snapshotModal = false; openRestore(inspectRecord)" 
                            class="px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold uppercase tracking-wider transition-all shadow-xs cursor-pointer flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        <span>Restore Record</span>
                    </button>
                    <button type="button" 
                            @click="snapshotModal = false; openPurge(inspectRecord)" 
                            class="px-3.5 py-2 bg-rose-50 hover:bg-rose-600 text-rose-600 hover:text-white border border-rose-200/80 rounded-xl text-xs font-bold uppercase tracking-wider transition-all cursor-pointer flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        <span>Purge</span>
                    </button>
                    <button type="button" 
                            @click="snapshotModal = false" 
                            class="px-4 py-2 bg-white hover:bg-gray-100 text-gray-700 border border-gray-200 rounded-xl text-xs font-bold uppercase tracking-wider transition-all cursor-pointer">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ─── Restore Record Modal ─── --}}
    <div x-show="restoreModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm" x-cloak>
        <div class="bg-white rounded-3xl w-full max-w-md p-6 sm:p-7 shadow-2xl space-y-5 border border-gray-100" @click.away="restoreModal = false">
            <div class="flex items-start gap-3.5">
                <div class="w-11 h-11 rounded-2xl bg-emerald-100 text-emerald-600 flex items-center justify-center shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                </div>
                <div class="min-w-0">
                    <h3 class="text-base sm:text-lg font-bold text-gray-900 leading-tight">Restore Archived Item</h3>
                    <p class="text-xs text-gray-500 mt-1">Reactivate <strong x-text="restoreName" class="text-black"></strong> back into the platform registry.</p>
                </div>
            </div>

            <p class="text-xs text-gray-600 leading-relaxed font-medium">
                This will reconstruct the original database record using the archived metadata snapshot.
            </p>

            <form :action="'/admin/archives/' + restoreId + '/restore'" method="POST" class="flex gap-3 pt-2">
                @csrf
                <button type="button" @click="restoreModal = false" class="flex-1 py-2.5 border border-gray-200 text-xs font-bold text-gray-600 rounded-xl hover:bg-gray-50 transition-all cursor-pointer">
                    Cancel
                </button>
                <button type="submit" class="flex-1 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold uppercase tracking-wider rounded-xl transition-all shadow-sm cursor-pointer">
                    Confirm Restore
                </button>
            </form>
        </div>
    </div>

    {{-- ─── Purge Record Modal ─── --}}
    <div x-show="purgeModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm" x-cloak>
        <div class="bg-white rounded-3xl w-full max-w-md p-6 sm:p-7 shadow-2xl space-y-5 border border-gray-100" @click.away="purgeModal = false">
            <div class="flex items-start gap-3.5">
                <div class="w-11 h-11 rounded-2xl bg-red-100 text-red-600 flex items-center justify-center shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                </div>
                <div class="min-w-0">
                    <h3 class="text-base sm:text-lg font-bold text-gray-900 leading-tight">Permanently Purge Archive</h3>
                    <p class="text-xs text-gray-500 mt-1">Erase <strong x-text="purgeName" class="text-black"></strong> forever from the archives.</p>
                </div>
            </div>

            <div class="p-3 bg-red-50 border border-red-200 rounded-2xl text-[11px] text-red-800 leading-relaxed font-medium">
                ⚠️ <strong>Warning:</strong> This permanently deletes this snapshot from the archive database. It cannot be recovered after this.
            </div>

            <form :action="'/admin/archives/' + purgeId" method="POST" class="flex gap-3 pt-2">
                @csrf
                @method('DELETE')
                <button type="button" @click="purgeModal = false" class="flex-1 py-2.5 border border-gray-200 text-xs font-bold text-gray-600 rounded-xl hover:bg-gray-50 transition-all cursor-pointer">
                    Cancel
                </button>
                <button type="submit" class="flex-1 py-2.5 bg-red-600 hover:bg-red-700 text-white text-xs font-bold uppercase tracking-wider rounded-xl transition-all shadow-sm cursor-pointer">
                    Purge Forever
                </button>
            </form>
        </div>
    </div>

</div>
@endsection
