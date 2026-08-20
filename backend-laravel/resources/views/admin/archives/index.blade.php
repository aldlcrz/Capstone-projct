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

    {{-- Page Header & Search Bar --}}
    <div class="space-y-3.5">
        <div>
            <div class="text-[10px] font-bold text-[#C0422A] uppercase tracking-[0.2em] mb-1">System Governance</div>
            <h1 class="font-serif text-2xl sm:text-3xl font-bold text-black">
                Archive <span class="text-[#C0420A] font-light italic">Hub &amp; Deleted Registry</span>
            </h1>
        </div>

        {{-- Search Input (Below title) --}}
        <form method="GET" class="flex items-center gap-2 max-w-sm sm:max-w-md">
            @if(request('type'))
                <input type="hidden" name="type" value="{{ request('type') }}">
            @endif
            <div class="relative w-full">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by name, email, reason, or admin..." 
                       class="w-full pl-9 pr-4 py-2.5 bg-white border border-gray-200 rounded-full text-xs text-gray-800 placeholder:text-gray-400 focus:outline-none focus:border-[#C0422A] shadow-xs">
                <svg class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </div>
            @if(request('search'))
                <a href="{{ request()->fullUrlWithQuery(['search' => null, 'page' => 1]) }}" class="px-3 py-2 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-full text-[10px] font-bold">Clear</a>
            @endif
        </form>
    </div>

    @php
        $currentType = request('type', 'all');
    @endphp

    {{-- Filter Pills Sorted by Category --}}
    <div class="flex items-center gap-2 overflow-x-auto no-scrollbar pt-1">
        {{-- ALL --}}
        <a href="{{ request()->fullUrlWithQuery(['type' => 'all', 'page' => 1]) }}"
           class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-[10px] sm:text-[11px] font-black uppercase tracking-wider transition-all {{ empty($currentType) || $currentType === 'all' ? 'bg-black text-white shadow-sm' : 'bg-white text-gray-700 border border-gray-200 hover:bg-gray-50' }}">
            <span>ALL</span>
            <span class="px-2 py-0.5 rounded-full text-[9px] font-black {{ empty($currentType) || $currentType === 'all' ? 'bg-white/20 text-white' : 'bg-gray-100 text-gray-600' }}">{{ $counts['all'] }}</span>
        </a>

        {{-- PRODUCTS --}}
        <a href="{{ request()->fullUrlWithQuery(['type' => 'product', 'page' => 1]) }}"
           class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-[10px] sm:text-[11px] font-black uppercase tracking-wider transition-all {{ $currentType === 'product' ? 'bg-black text-white shadow-sm' : 'bg-white text-gray-700 border border-gray-200 hover:bg-gray-50' }}">
            <span>PRODUCTS</span>
            <span class="px-2 py-0.5 rounded-full text-[9px] font-black {{ $currentType === 'product' ? 'bg-white/20 text-white' : 'bg-gray-100 text-gray-600' }}">{{ $counts['product'] }}</span>
        </a>

        {{-- CATEGORIES --}}
        <a href="{{ request()->fullUrlWithQuery(['type' => 'category', 'page' => 1]) }}"
           class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-[10px] sm:text-[11px] font-black uppercase tracking-wider transition-all {{ $currentType === 'category' ? 'bg-black text-white shadow-sm' : 'bg-white text-gray-700 border border-gray-200 hover:bg-gray-50' }}">
            <span>CATEGORIES</span>
            <span class="px-2 py-0.5 rounded-full text-[9px] font-black {{ $currentType === 'category' ? 'bg-white/20 text-white' : 'bg-gray-100 text-gray-600' }}">{{ $counts['category'] }}</span>
        </a>

        {{-- CUSTOMERS --}}
        <a href="{{ request()->fullUrlWithQuery(['type' => 'customer', 'page' => 1]) }}"
           class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-[10px] sm:text-[11px] font-black uppercase tracking-wider transition-all {{ $currentType === 'customer' ? 'bg-black text-white shadow-sm' : 'bg-white text-gray-700 border border-gray-200 hover:bg-gray-50' }}">
            <span>CUSTOMERS</span>
            <span class="px-2 py-0.5 rounded-full text-[9px] font-black {{ $currentType === 'customer' ? 'bg-white/20 text-white' : 'bg-gray-100 text-gray-600' }}">{{ $counts['customer'] }}</span>
        </a>

        {{-- SELLERS --}}
        <a href="{{ request()->fullUrlWithQuery(['type' => 'seller', 'page' => 1]) }}"
           class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-[10px] sm:text-[11px] font-black uppercase tracking-wider transition-all {{ $currentType === 'seller' ? 'bg-black text-white shadow-sm' : 'bg-white text-gray-700 border border-gray-200 hover:bg-gray-50' }}">
            <span>SELLERS</span>
            <span class="px-2 py-0.5 rounded-full text-[9px] font-black {{ $currentType === 'seller' ? 'bg-white/20 text-white' : 'bg-gray-100 text-gray-600' }}">{{ $counts['seller'] }}</span>
        </a>
    </div>

    {{-- Main Archive Table --}}
    @if($archives->isEmpty())
        <div class="text-center py-20 bg-white rounded-3xl border border-dashed border-gray-200 shadow-sm p-8">
            <svg class="w-12 h-12 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
            </svg>
            <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wider">Archive Registry is Empty</h3>
            <p class="text-xs text-gray-400 mt-1">There are no archived items found under this filter criteria.</p>
        </div>
    @else
        <div class="bg-white rounded-3xl border border-gray-100 shadow-sm overflow-hidden">
            <div class="overflow-x-auto no-scrollbar">
                <table class="w-full text-left border-collapse min-w-[700px]">
                    <thead>
                        <tr class="bg-gray-50/50 border-b border-gray-100">
                            <th class="px-6 py-4 text-[10px] font-black text-gray-700 uppercase tracking-widest">Type</th>
                            <th class="px-6 py-4 text-[10px] font-black text-gray-700 uppercase tracking-widest">Archived Entity</th>
                            <th class="px-6 py-4 text-[10px] font-black text-gray-700 uppercase tracking-widest">Deletion Reason</th>
                            <th class="px-6 py-4 text-[10px] font-black text-gray-700 uppercase tracking-widest">Archived Info</th>
                            <th class="px-6 py-4 text-[10px] font-black text-gray-700 uppercase tracking-widest text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach($archives as $record)
                            @php
                                $typeStyles = [
                                    'product'  => 'bg-purple-100 text-purple-800 border-purple-200',
                                    'category' => 'bg-blue-100 text-blue-800 border-blue-200',
                                    'customer' => 'bg-emerald-100 text-emerald-800 border-emerald-200',
                                    'seller'   => 'bg-amber-100 text-amber-800 border-amber-200',
                                ];
                            @endphp
                            <tr class="hover:bg-gray-50/60 transition-colors">
                                {{-- Item Type --}}
                                <td class="px-6 py-4">
                                    <span class="px-2.5 py-1 rounded-md text-[9px] font-black uppercase tracking-wider border {{ $typeStyles[$record->item_type] ?? 'bg-gray-100 text-gray-700 border-gray-200' }}">
                                        {{ $record->item_type }}
                                    </span>
                                </td>

                                {{-- Entity Details --}}
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-xl bg-stone-100 border border-gray-200 flex items-center justify-center font-bold text-xs text-gray-600 shrink-0 overflow-hidden">
                                            @if($record->item_type === 'product' && !empty($record->metadata['image']))
                                                @php
                                                    $rawImg = $record->metadata['image'];
                                                    $imgUrl = is_array($rawImg) ? ($rawImg[0]['url'] ?? $rawImg[0] ?? '') : $rawImg;
                                                    if ($imgUrl && !str_starts_with($imgUrl, 'http') && !str_starts_with($imgUrl, '/')) {
                                                        $imgUrl = '/storage/' . ltrim($imgUrl, '/');
                                                    }
                                                @endphp
                                                <img src="{{ $imgUrl }}" class="w-full h-full object-cover" onerror="this.style.display='none'">
                                            @elseif($record->item_type === 'category' && !empty($record->metadata['image']))
                                                <img src="{{ $record->metadata['image'] }}" class="w-full h-full object-cover" onerror="this.style.display='none'">
                                            @else
                                                <span>{{ strtoupper(substr($record->name, 0, 1)) }}</span>
                                            @endif
                                        </div>
                                        <div class="min-w-0">
                                            <div class="text-xs font-bold text-gray-900 truncate">{{ $record->name }}</div>
                                            @if($record->identifier)
                                                <div class="text-[10px] text-gray-500 truncate">{{ $record->identifier }}</div>
                                            @endif
                                            @if($record->item_type === 'product' && isset($record->metadata['price']))
                                                <div class="text-[10px] font-bold text-[#C0422A]">₱{{ number_format((float)$record->metadata['price'], 2) }}</div>
                                            @endif
                                        </div>
                                    </div>
                                </td>

                                {{-- Reason --}}
                                <td class="px-6 py-4">
                                    <div class="max-w-xs">
                                        <span class="text-xs text-gray-700 font-medium line-clamp-2 leading-relaxed bg-gray-50 p-2 rounded-xl border border-gray-100 block">
                                            {{ $record->reason ?: 'Administrative deletion' }}
                                        </span>
                                    </div>
                                </td>

                                {{-- Archive Meta --}}
                                <td class="px-6 py-4">
                                    <div class="space-y-0.5">
                                        <div class="text-[11px] font-bold text-gray-800">{{ $record->created_at->format('M d, Y • h:i A') }}</div>
                                        <div class="text-[10px] text-gray-400">By <strong class="text-gray-600">{{ $record->archived_by ?: 'Admin' }}</strong></div>
                                    </div>
                                </td>

                                {{-- Actions --}}
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        {{-- Inspect Snapshot --}}
                                        <button type="button" @click="openSnapshot({{ json_encode($record) }})"
                                            class="px-2.5 py-1.5 bg-stone-100 hover:bg-stone-200 text-stone-700 rounded-xl text-[10px] font-bold uppercase tracking-wider transition-all cursor-pointer" title="View Snapshot Data">
                                            Inspect
                                        </button>

                                        {{-- Restore --}}
                                        <button type="button" @click="openRestore({{ json_encode($record) }})"
                                            class="px-2.5 py-1.5 bg-emerald-50 hover:bg-emerald-600 text-emerald-700 hover:text-white rounded-xl text-[10px] font-bold uppercase tracking-wider transition-all cursor-pointer" title="Restore Entity">
                                            Restore
                                        </button>

                                        {{-- Purge --}}
                                        <button type="button" @click="openPurge({{ json_encode($record) }})"
                                            class="w-8 h-8 rounded-xl bg-red-50 hover:bg-red-600 text-red-600 hover:text-white flex items-center justify-center transition-all cursor-pointer shrink-0" title="Permanently Purge">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Pagination --}}
        <div class="pt-4">
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
                    <div class="p-3 bg-red-50 text-red-900 rounded-xl font-medium" x-text="inspectRecord?.reason || 'None specified'"></div>
                </div>

                {{-- Clean Formatted Snapshot Details (No raw JSON block) --}}
                <div>
                    <span class="font-bold text-gray-500 uppercase tracking-widest text-[9px] block mb-2">Original Snapshot Details</span>
                    <div class="bg-stone-50 border border-stone-200 rounded-2xl p-4 space-y-2">
                        <template x-for="(value, key) in (inspectRecord?.metadata || {})" :key="key">
                            <template x-if="value !== null && value !== '' && key !== 'password' && key !== 'remember_token' && typeof value !== 'object'">
                                <div class="flex items-center justify-between py-1.5 border-b border-stone-200/60 last:border-0 text-xs">
                                    <span class="font-bold text-gray-500 capitalize" x-text="key.replace(/([A-Z])/g, ' $1').replace(/_/g, ' ')"></span>
                                    <span class="font-semibold text-gray-900 text-right max-w-xs truncate" x-text="value"></span>
                                </div>
                            </template>
                        </template>
                    </div>
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
