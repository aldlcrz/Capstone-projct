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
            <table class="w-full text-left min-w-[640px]">
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
    <div x-show="snapshotModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm" x-cloak>
        <div class="bg-white rounded-3xl w-full max-w-xl p-6 sm:p-7 shadow-2xl space-y-5 border border-gray-100 max-h-[85vh] flex flex-col" @click.away="snapshotModal = false">
            <div class="flex items-start justify-between gap-3 border-b border-gray-100 pb-4 shrink-0">
                <div class="min-w-0">
                    <span class="text-[9px] font-black uppercase tracking-wider px-2 py-0.5 rounded bg-black text-white" x-text="inspectRecord?.item_type"></span>
                    <h3 class="text-lg font-bold text-gray-900 mt-1 truncate" x-text="inspectRecord?.name"></h3>
                    <p class="text-xs text-gray-400" x-text="'Archived ' + (inspectRecord?.created_at ? new Date(inspectRecord.created_at).toLocaleString() : '') + ' by ' + (inspectRecord?.archived_by || 'Admin')"></p>
                </div>
                <button type="button" @click="snapshotModal = false" class="text-gray-400 hover:text-black">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <div class="overflow-y-auto space-y-4 pr-1 text-xs">
                <div>
                    <span class="font-bold text-gray-500 uppercase tracking-widest text-[9px] block mb-1">Recorded Deletion Reason</span>
                    <div class="p-3.5 bg-red-50 text-red-900 rounded-2xl font-medium leading-relaxed border border-red-100" x-text="inspectRecord?.reason || 'None specified'"></div>
                </div>
            </div>

            <div class="pt-2 border-t border-gray-100 flex justify-end shrink-0">
                <button type="button" @click="snapshotModal = false" class="px-5 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl text-xs font-bold uppercase tracking-wider">
                    Close
                </button>
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
