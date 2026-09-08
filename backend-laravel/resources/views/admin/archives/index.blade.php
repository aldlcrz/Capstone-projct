@extends('layouts.admin')

@section('content')
<div class="space-y-6" x-data="{
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

    {{-- ═══ PAGE HEADER ═══ --}}
    <div class="text-center space-y-1.5 pb-2">
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

    {{-- ═══ STATS BAR ═══ --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <div class="bg-white rounded-2xl border border-gray-100 shadow-xs px-4 py-3.5 flex items-center gap-3 hover:-translate-y-0.5 transition-all">
            <div class="w-9 h-9 rounded-xl bg-gray-100 flex items-center justify-center shrink-0">
                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/></svg>
            </div>
            <div>
                <div class="text-lg font-black text-gray-900 leading-none">{{ $counts['all'] ?? 0 }}</div>
                <div class="text-[9px] font-bold uppercase tracking-wider text-gray-400 mt-0.5">Total</div>
            </div>
        </div>
        <div class="bg-white rounded-2xl border border-purple-100 shadow-xs px-4 py-3.5 flex items-center gap-3 hover:-translate-y-0.5 transition-all">
            <div class="w-9 h-9 rounded-xl bg-purple-50 flex items-center justify-center shrink-0">
                <svg class="w-4 h-4 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
            </div>
            <div>
                <div class="text-lg font-black text-gray-900 leading-none">{{ $counts['product'] ?? 0 }}</div>
                <div class="text-[9px] font-bold uppercase tracking-wider text-purple-400 mt-0.5">Products</div>
            </div>
        </div>
        <div class="bg-white rounded-2xl border border-emerald-100 shadow-xs px-4 py-3.5 flex items-center gap-3 hover:-translate-y-0.5 transition-all">
            <div class="w-9 h-9 rounded-xl bg-emerald-50 flex items-center justify-center shrink-0">
                <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            </div>
            <div>
                <div class="text-lg font-black text-gray-900 leading-none">{{ $counts['customer'] ?? 0 }}</div>
                <div class="text-[9px] font-bold uppercase tracking-wider text-emerald-500 mt-0.5">Customers</div>
            </div>
        </div>
        <div class="bg-white rounded-2xl border border-amber-100 shadow-xs px-4 py-3.5 flex items-center gap-3 hover:-translate-y-0.5 transition-all">
            <div class="w-9 h-9 rounded-xl bg-amber-50 flex items-center justify-center shrink-0">
                <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
            </div>
            <div>
                <div class="text-lg font-black text-gray-900 leading-none">{{ $counts['seller'] ?? 0 }}</div>
                <div class="text-[9px] font-bold uppercase tracking-wider text-amber-500 mt-0.5">Sellers</div>
            </div>
        </div>
    </div>

    {{-- ═══ SEARCH + FILTERS ═══ --}}
    <div class="space-y-3">
        <form method="GET" class="flex items-center justify-center gap-2 max-w-sm sm:max-w-md mx-auto">
            @if(request('type'))
                <input type="hidden" name="type" value="{{ request('type') }}">
            @endif
            <div class="relative w-full">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by name, email, reason, or admin..."
                       class="w-full pl-9 pr-4 py-2.5 bg-white border border-gray-200 rounded-full text-xs text-gray-800 placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-[#C0422A]/20 focus:border-[#C0422A] shadow-xs transition-all">
                <svg class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </div>
            @if(request('search'))
                <a href="{{ request()->fullUrlWithQuery(['search' => null, 'page' => 1]) }}" class="px-3 py-2 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-full text-[10px] font-bold transition-all">Clear</a>
            @endif
        </form>

        @php $currentType = request('type', 'all'); @endphp
        <div class="flex items-center justify-center gap-2 overflow-x-auto no-scrollbar">
            <a href="{{ request()->fullUrlWithQuery(['type' => 'all', 'page' => 1]) }}"
               class="inline-flex items-center gap-1.5 px-4 py-1.5 rounded-full text-[10px] font-black uppercase tracking-wider transition-all whitespace-nowrap {{ $currentType === 'all' || empty($currentType) ? 'bg-gray-900 text-white shadow-sm' : 'bg-white text-gray-600 border border-gray-200 hover:bg-gray-50' }}">
                All <span class="px-1.5 py-0.5 rounded-full text-[9px] font-black {{ $currentType === 'all' || empty($currentType) ? 'bg-white/20' : 'bg-gray-100 text-gray-500' }}">{{ $counts['all'] }}</span>
            </a>
            <a href="{{ request()->fullUrlWithQuery(['type' => 'product', 'page' => 1]) }}"
               class="inline-flex items-center gap-1.5 px-4 py-1.5 rounded-full text-[10px] font-black uppercase tracking-wider transition-all whitespace-nowrap {{ $currentType === 'product' ? 'bg-purple-700 text-white shadow-sm' : 'bg-white text-gray-600 border border-gray-200 hover:border-purple-200 hover:bg-purple-50' }}">
                <span class="w-1.5 h-1.5 rounded-full {{ $currentType === 'product' ? 'bg-purple-200' : 'bg-purple-400' }}"></span>
                Products <span class="px-1.5 py-0.5 rounded-full text-[9px] font-black {{ $currentType === 'product' ? 'bg-white/20' : 'bg-gray-100 text-gray-500' }}">{{ $counts['product'] }}</span>
            </a>
            <a href="{{ request()->fullUrlWithQuery(['type' => 'category', 'page' => 1]) }}"
               class="inline-flex items-center gap-1.5 px-4 py-1.5 rounded-full text-[10px] font-black uppercase tracking-wider transition-all whitespace-nowrap {{ $currentType === 'category' ? 'bg-blue-700 text-white shadow-sm' : 'bg-white text-gray-600 border border-gray-200 hover:border-blue-200 hover:bg-blue-50' }}">
                <span class="w-1.5 h-1.5 rounded-full {{ $currentType === 'category' ? 'bg-blue-200' : 'bg-blue-400' }}"></span>
                Categories <span class="px-1.5 py-0.5 rounded-full text-[9px] font-black {{ $currentType === 'category' ? 'bg-white/20' : 'bg-gray-100 text-gray-500' }}">{{ $counts['category'] }}</span>
            </a>
            <a href="{{ request()->fullUrlWithQuery(['type' => 'customer', 'page' => 1]) }}"
               class="inline-flex items-center gap-1.5 px-4 py-1.5 rounded-full text-[10px] font-black uppercase tracking-wider transition-all whitespace-nowrap {{ $currentType === 'customer' ? 'bg-emerald-700 text-white shadow-sm' : 'bg-white text-gray-600 border border-gray-200 hover:border-emerald-200 hover:bg-emerald-50' }}">
                <span class="w-1.5 h-1.5 rounded-full {{ $currentType === 'customer' ? 'bg-emerald-200' : 'bg-emerald-400' }}"></span>
                Customers <span class="px-1.5 py-0.5 rounded-full text-[9px] font-black {{ $currentType === 'customer' ? 'bg-white/20' : 'bg-gray-100 text-gray-500' }}">{{ $counts['customer'] }}</span>
            </a>
            <a href="{{ request()->fullUrlWithQuery(['type' => 'seller', 'page' => 1]) }}"
               class="inline-flex items-center gap-1.5 px-4 py-1.5 rounded-full text-[10px] font-black uppercase tracking-wider transition-all whitespace-nowrap {{ $currentType === 'seller' ? 'bg-amber-600 text-white shadow-sm' : 'bg-white text-gray-600 border border-gray-200 hover:border-amber-200 hover:bg-amber-50' }}">
                <span class="w-1.5 h-1.5 rounded-full {{ $currentType === 'seller' ? 'bg-amber-200' : 'bg-amber-400' }}"></span>
                Sellers <span class="px-1.5 py-0.5 rounded-full text-[9px] font-black {{ $currentType === 'seller' ? 'bg-white/20' : 'bg-gray-100 text-gray-500' }}">{{ $counts['seller'] }}</span>
            </a>
        </div>
    </div>

    {{-- ═══ ARCHIVE RECORDS ═══ --}}
    @if($archives->isEmpty())
        <div class="bg-white rounded-3xl border border-gray-100 shadow-sm p-16 text-center">
            <div class="w-16 h-16 bg-gray-50 rounded-2xl flex items-center justify-center mx-auto mb-4 border border-gray-100">
                <svg class="w-7 h-7 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/></svg>
            </div>
            <p class="text-sm font-bold text-gray-400 uppercase tracking-widest">Archive Registry is Empty</p>
            <p class="text-xs text-gray-300 mt-1">No archived records found under the current filter</p>
        </div>
    @else
        <div class="space-y-3">
            @foreach($archives as $record)
                @php
                    $typeConfig = [
                        'product'  => ['border' => 'border-l-purple-400',  'badge' => 'bg-purple-100 text-purple-800 border-purple-200',  'icon' => 'bg-purple-50',  'emoji' => '📦'],
                        'category' => ['border' => 'border-l-blue-400',    'badge' => 'bg-blue-100 text-blue-800 border-blue-200',        'icon' => 'bg-blue-50',    'emoji' => '🏷️'],
                        'customer' => ['border' => 'border-l-emerald-400', 'badge' => 'bg-emerald-100 text-emerald-800 border-emerald-200','icon' => 'bg-emerald-50', 'emoji' => '🧑'],
                        'seller'   => ['border' => 'border-l-amber-400',   'badge' => 'bg-amber-100 text-amber-800 border-amber-200',     'icon' => 'bg-amber-50',   'emoji' => '🏪'],
                    ];
                    $tc = $typeConfig[$record->item_type] ?? ['border' => 'border-l-gray-300', 'badge' => 'bg-gray-100 text-gray-700 border-gray-200', 'icon' => 'bg-gray-50', 'emoji' => '📄'];
                    $daysOld = $record->created_at->diffInDays(now());
                    $daysLeft = max(0, 30 - $daysOld);
                    $expiryClass = $daysLeft <= 7 ? 'bg-red-50 text-red-600 border-red-200' : 'bg-gray-50 text-gray-500 border-gray-200';
                @endphp
                <div class="bg-white rounded-2xl border border-gray-100 border-l-4 {{ $tc['border'] }} shadow-sm hover:shadow-md transition-all">
                    <div class="p-4 sm:p-5">
                        <div class="flex items-start gap-4">
                            {{-- Type Icon / Thumbnail --}}
                            <div class="w-12 h-12 rounded-xl {{ $tc['icon'] }} border border-gray-100 flex items-center justify-center font-bold text-sm text-gray-600 shrink-0 overflow-hidden">
                                @if($record->item_type === 'product' && !empty($record->metadata['image']))
                                    @php
                                        $rawImg = $record->metadata['image'];
                                        $imgUrl = is_array($rawImg) ? ($rawImg[0]['url'] ?? $rawImg[0] ?? '') : $rawImg;
                                        if ($imgUrl && !str_starts_with($imgUrl, 'http') && !str_starts_with($imgUrl, '/')) {
                                            $imgUrl = '/storage/' . ltrim($imgUrl, '/');
                                        }
                                    @endphp
                                    <img src="{{ $imgUrl }}" class="w-full h-full object-cover" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex'">
                                    <span class="hidden w-full h-full items-center justify-center text-lg">{{ $tc['emoji'] }}</span>
                                @elseif($record->item_type === 'category' && !empty($record->metadata['image']))
                                    <img src="{{ $record->metadata['image'] }}" class="w-full h-full object-cover" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex'">
                                    <span class="hidden w-full h-full items-center justify-center text-lg">{{ $tc['emoji'] }}</span>
                                @else
                                    <span class="text-lg">{{ $tc['emoji'] }}</span>
                                @endif
                            </div>

                            {{-- Main Info --}}
                            <div class="flex-1 min-w-0">
                                <div class="flex items-start justify-between gap-3 flex-wrap">
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-2 flex-wrap mb-1">
                                            <span class="px-2 py-0.5 rounded-md text-[8px] font-black uppercase tracking-widest border {{ $tc['badge'] }}">{{ $record->item_type }}</span>
                                            <span class="px-2 py-0.5 rounded-md text-[8px] font-black uppercase tracking-widest border {{ $expiryClass }}">⏱ {{ $daysLeft }} days left</span>
                                        </div>
                                        <div class="text-sm font-bold text-gray-900 truncate">{{ $record->name }}</div>
                                        @if($record->identifier)
                                            <div class="text-[10px] text-gray-400 truncate">{{ $record->identifier }}</div>
                                        @endif
                                        @if($record->item_type === 'product' && isset($record->metadata['price']))
                                            <div class="text-[11px] font-bold text-[#C0422A] mt-0.5">₱{{ number_format((float)$record->metadata['price'], 2) }}</div>
                                        @endif
                                    </div>
                                    {{-- Actions --}}
                                    <div class="flex items-center gap-1.5 shrink-0">
                                        <button type="button" @click="openSnapshot({{ json_encode($record) }})"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-gray-50 border border-gray-200 text-gray-600 rounded-xl text-[9px] font-black uppercase tracking-widest hover:bg-gray-100 transition-all cursor-pointer">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                            Inspect
                                        </button>
                                        <button type="button" @click="openRestore({{ json_encode($record) }})"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-50 border border-emerald-100 text-emerald-700 rounded-xl text-[9px] font-black uppercase tracking-widest hover:bg-emerald-600 hover:text-white hover:border-emerald-600 transition-all cursor-pointer">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                            Restore
                                        </button>
                                        <button type="button" @click="openPurge({{ json_encode($record) }})"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-red-50 border border-red-100 text-red-600 rounded-xl text-[9px] font-black uppercase tracking-widest hover:bg-red-600 hover:text-white hover:border-red-600 transition-all cursor-pointer">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            Purge
                                        </button>
                                    </div>
                                </div>

                                {{-- Meta Row --}}
                                <div class="flex items-center gap-4 mt-2 pt-2 border-t border-gray-50">
                                    <span class="text-[10px] text-gray-400">Deleted <strong class="text-gray-600">{{ $daysOld }}d ago</strong></span>
                                    <span class="text-[10px] text-gray-400">By <strong class="text-gray-600">{{ $record->archived_by ?: 'Admin' }}</strong></span>
                                    @if($record->reason)
                                        <span class="text-[10px] text-gray-400 truncate hidden sm:block">"{{ Str::limit($record->reason, 60) }}"</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Pagination --}}
        <div class="pt-2">
            {{ $archives->withQueryString()->links() }}
        </div>
    @endif

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
