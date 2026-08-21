@extends('layouts.superadmin')

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

    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="text-[10px] font-bold text-[#C0422A] uppercase tracking-widest mb-1">System Governance &amp; Recovery</div>
            <h1 class="font-serif text-3xl font-bold text-[#3D2B1F]">Archive <span class="text-[#C0422A] italic">Vault</span></h1>
            <p class="text-xs text-gray-500 mt-1">Audit, inspect snapshots, restore accidentally deleted records, or permanently purge archived records.</p>
        </div>
    </div>

    <!-- Filter Pills Bar -->
    <div class="flex items-center gap-2 overflow-x-auto no-scrollbar pb-1">
        @php
            $tabs = [
                'all'      => ['label' => 'All Archives', 'count' => $counts['all'] ?? 0],
                'product'  => ['label' => 'Products',     'count' => $counts['product'] ?? 0],
                'category' => ['label' => 'Categories',   'count' => $counts['category'] ?? 0],
                'customer' => ['label' => 'Customers',    'count' => $counts['customer'] ?? 0],
                'seller'   => ['label' => 'Sellers',      'count' => $counts['seller'] ?? 0],
            ];
        @endphp

        @foreach($tabs as $tabKey => $tab)
            <a href="{{ route('superadmin.archives', ['type' => $tabKey, 'search' => $search]) }}"
               class="px-4 py-2 rounded-xl text-xs font-bold transition-all shrink-0 flex items-center gap-2 {{ ($type ?? 'all') === $tabKey ? 'bg-[#3D2B1F] text-white shadow-sm' : 'bg-white border border-[#E5DDD5] text-gray-600 hover:border-gray-400' }}">
                <span>{{ $tab['label'] }}</span>
                <span class="px-1.5 py-0.5 rounded-full text-[10px] {{ ($type ?? 'all') === $tabKey ? 'bg-white/20 text-white' : 'bg-gray-100 text-gray-600' }}">{{ $tab['count'] }}</span>
            </a>
        @endforeach
    </div>

    <!-- Search Bar -->
    <div class="bg-white border border-[#E5DDD5] rounded-2xl p-4 shadow-xs">
        <form action="{{ route('superadmin.archives') }}" method="GET" class="flex flex-col sm:flex-row items-center gap-3">
            <input type="hidden" name="type" value="{{ $type ?? 'all' }}">
            <div class="relative flex-1 w-full">
                <svg class="w-4 h-4 text-gray-400 absolute left-3.5 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input type="text" name="search" value="{{ $search ?? '' }}" placeholder="Search by name, email, identifier, reason..." 
                       class="w-full pl-10 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-xs font-medium focus:bg-white focus:outline-none focus:border-[#C0422A] transition-all">
            </div>
            <div class="flex items-center gap-2 w-full sm:w-auto">
                <button type="submit" class="flex-1 sm:flex-none px-5 py-2.5 bg-[#C0422A] hover:bg-[#a53808] text-white text-xs font-bold rounded-xl transition-all shadow-xs cursor-pointer">
                    Search
                </button>
                @if(!empty($search))
                    <a href="{{ route('superadmin.archives', ['type' => $type ?? 'all']) }}" class="px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-600 text-xs font-bold rounded-xl transition-all cursor-pointer">
                        Clear
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Archives Table -->
    @if($archives->isEmpty())
        <div class="bg-white rounded-3xl border border-[#E5DDD5] p-12 text-center shadow-xs">
            <div class="w-16 h-16 bg-gray-50 rounded-2xl flex items-center justify-center mx-auto mb-4 border border-gray-100">
                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/></svg>
            </div>
            <h3 class="text-sm font-bold text-gray-900">No Archived Records Found</h3>
            <p class="text-xs text-gray-400 mt-1">There are no archived items found under this filter criteria.</p>
        </div>
    @else
        <div class="bg-white rounded-3xl border border-[#E5DDD5] shadow-xs overflow-hidden">
            <div class="overflow-x-auto no-scrollbar">
                <table class="w-full text-left border-collapse min-w-175">
                    <thead>
                        <tr class="bg-gray-50/70 border-b border-[#E5DDD5]">
                            <th class="px-6 py-4 text-[10px] font-black text-gray-600 uppercase tracking-widest">Type</th>
                            <th class="px-6 py-4 text-[10px] font-black text-gray-600 uppercase tracking-widest">Archived Entity</th>
                            <th class="px-6 py-4 text-[10px] font-black text-gray-600 uppercase tracking-widest">Deletion Reason</th>
                            <th class="px-6 py-4 text-[10px] font-black text-gray-600 uppercase tracking-widest">Archived Info</th>
                            <th class="px-6 py-4 text-[10px] font-black text-gray-600 uppercase tracking-widest text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($archives as $record)
                            <tr class="hover:bg-amber-50/30 transition-colors">
                                <!-- Type Badge -->
                                <td class="px-6 py-4">
                                    @php
                                        $typeColors = [
                                            'product'  => 'bg-amber-50 text-amber-700 border-amber-200',
                                            'category' => 'bg-blue-50 text-blue-700 border-blue-200',
                                            'customer' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                            'seller'   => 'bg-purple-50 text-purple-700 border-purple-200',
                                        ];
                                    @endphp
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider border {{ $typeColors[$record->item_type] ?? 'bg-gray-50 text-gray-700 border-gray-200' }}">
                                        {{ $record->item_type }}
                                    </span>
                                </td>

                                <!-- Entity Info with Thumbnail -->
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        @php
                                            $thumb = null;
                                            if (!empty($record->metadata)) {
                                                if ($record->item_type === 'product' && !empty($record->metadata['image'])) {
                                                    $rawImg = $record->metadata['image'];
                                                    $thumb = is_array($rawImg) ? ($rawImg[0]['url'] ?? ($rawImg[0] ?? null)) : $rawImg;
                                                } elseif ($record->item_type === 'category' && !empty($record->metadata['image'])) {
                                                    $thumb = $record->metadata['image'];
                                                } elseif (in_array($record->item_type, ['customer', 'seller']) && !empty($record->metadata['profilePhoto'])) {
                                                    $thumb = $record->metadata['profilePhoto'];
                                                }
                                            }
                                        @endphp
                                        @if($thumb)
                                            <div class="w-10 h-10 rounded-xl overflow-hidden bg-gray-100 border border-gray-200 shrink-0">
                                                <img src="{{ str_starts_with($thumb, 'http') || str_starts_with($thumb, '/') ? $thumb : asset('storage/' . $thumb) }}" 
                                                     onerror="this.src='/uploads/products/default.jpg'" 
                                                     class="w-full h-full object-cover">
                                            </div>
                                        @else
                                            <div class="w-10 h-10 rounded-xl bg-gray-100 border border-gray-200 flex items-center justify-center text-gray-400 font-bold text-xs shrink-0">
                                                {{ strtoupper(substr($record->name, 0, 1)) }}
                                            </div>
                                        @endif
                                        <div>
                                            <div class="text-xs font-bold text-gray-900">{{ $record->name }}</div>
                                            @if($record->identifier)
                                                <div class="text-[10px] text-gray-400 font-mono">{{ $record->identifier }}</div>
                                            @endif
                                        </div>
                                    </div>
                                </td>

                                <!-- Deletion Reason -->
                                <td class="px-6 py-4">
                                    <span class="text-xs text-gray-600 line-clamp-2 max-w-xs">{{ $record->reason ?: 'No reason provided' }}</span>
                                </td>

                                <!-- Archived Date & Actor -->
                                <td class="px-6 py-4">
                                    <div class="text-xs font-medium text-gray-900">{{ $record->created_at->format('M d, Y h:i A') }}</div>
                                    <div class="text-[10px] text-gray-400">By: {{ $record->archived_by ?: 'System' }}</div>
                                </td>

                                <!-- Actions -->
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <!-- Inspect Snapshot Button -->
                                        <button type="button" 
                                                @click="openSnapshot({{ json_encode($record) }})"
                                                class="px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-bold rounded-xl transition-all flex items-center gap-1.5 cursor-pointer shadow-none">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                            <span>Inspect</span>
                                        </button>

                                        <!-- Restore Button -->
                                        <button type="button" 
                                                @click="openRestore({{ json_encode($record) }})"
                                                class="px-3 py-1.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 text-xs font-bold rounded-xl transition-all flex items-center gap-1.5 cursor-pointer shadow-none border border-emerald-200">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                            <span>Restore</span>
                                        </button>

                                        <!-- Purge Button -->
                                        <button type="button" 
                                                @click="openPurge({{ json_encode($record) }})"
                                                class="px-3 py-1.5 bg-red-50 hover:bg-red-100 text-red-600 text-xs font-bold rounded-xl transition-all flex items-center gap-1.5 cursor-pointer shadow-none border border-red-200">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            <span>Purge</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if($archives->hasPages())
                <div class="p-6 bg-gray-50/50 border-t border-[#E5DDD5]">
                    {{ $archives->appends(['type' => $type, 'search' => $search])->links() }}
                </div>
            @endif
        </div>
    @endif

    {{-- ==================== SNAPSHOT INSPECTION MODAL ==================== --}}
    <div x-show="snapshotModal" 
         x-cloak 
         style="display: none;"
         class="fixed inset-0 z-50 flex items-center justify-center p-4"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95">
        <div @click="snapshotModal = false" class="absolute inset-0 bg-black/60 backdrop-blur-sm"></div>
        <div class="bg-white w-full max-w-2xl rounded-3xl shadow-2xl relative z-10 overflow-hidden border border-gray-100 max-h-[90vh] flex flex-col">
            <!-- Modal Header -->
            <div class="p-6 bg-gray-50/80 border-b border-gray-100 flex items-center justify-between">
                <div>
                    <span class="text-[10px] font-black uppercase tracking-widest text-[#C0422A]" x-text="'Archived ' + (inspectRecord?.item_type || 'Record')"></span>
                    <h3 class="text-base font-bold text-gray-900" x-text="inspectRecord?.name"></h3>
                </div>
                <button type="button" @click="snapshotModal = false" class="w-8 h-8 rounded-full bg-gray-200/60 hover:bg-gray-200 flex items-center justify-center text-gray-500 transition-all cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <!-- Modal Body -->
            <div class="p-6 space-y-6 overflow-y-auto flex-1">
                <!-- Summary Card -->
                <div class="bg-[#F7F3EE] rounded-2xl p-4 border border-[#E5DDD5] space-y-2">
                    <div class="grid grid-cols-2 gap-2 text-xs">
                        <div><span class="text-gray-400">Type:</span> <span class="font-bold uppercase text-gray-900" x-text="inspectRecord?.item_type"></span></div>
                        <div><span class="text-gray-400">Archived Date:</span> <span class="font-bold text-gray-900" x-text="inspectRecord?.created_at ? new Date(inspectRecord.created_at).toLocaleString() : ''"></span></div>
                        <div><span class="text-gray-400">Archived By:</span> <span class="font-bold text-gray-900" x-text="inspectRecord?.archived_by || 'System'"></span></div>
                        <div><span class="text-gray-400">Identifier:</span> <span class="font-mono text-gray-900" x-text="inspectRecord?.identifier || 'N/A'"></span></div>
                    </div>
                    <div class="text-xs pt-2 border-t border-[#E5DDD5]">
                        <span class="text-gray-400">Deletion Reason:</span>
                        <span class="font-semibold text-gray-900" x-text="inspectRecord?.reason || 'None provided'"></span>
                    </div>
                </div>

                <!-- Structured Key-Value Details -->
                <div>
                    <h4 class="text-xs font-bold uppercase tracking-wider text-gray-700 mb-3">Snapshot Details</h4>
                    <div class="bg-white border border-gray-200 rounded-2xl divide-y divide-gray-100 overflow-hidden text-xs">
                        <template x-for="(val, key) in (inspectRecord?.metadata || {})" :key="key">
                            <div class="flex items-start justify-between p-3 hover:bg-gray-50/50">
                                <span class="font-bold text-gray-500 capitalize" x-text="key.replace(/_/g, ' ')"></span>
                                <span class="text-gray-900 font-medium text-right max-w-xs break-words" x-text="typeof val === 'object' ? JSON.stringify(val) : val"></span>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="p-4 bg-gray-50/80 border-t border-gray-100 flex items-center justify-end gap-3">
                <button type="button" @click="snapshotModal = false" class="px-5 py-2.5 bg-gray-200 hover:bg-gray-300 text-gray-700 text-xs font-bold rounded-xl transition-all cursor-pointer">Close</button>
            </div>
        </div>
    </div>

    {{-- ==================== RESTORE MODAL ==================== --}}
    <div x-show="restoreModal" 
         x-cloak 
         style="display: none;"
         class="fixed inset-0 z-50 flex items-center justify-center p-4"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95">
        <div @click="restoreModal = false" class="absolute inset-0 bg-black/60 backdrop-blur-sm"></div>
        <div class="bg-white w-full max-w-sm rounded-3xl shadow-2xl relative z-10 overflow-hidden border border-gray-100 p-6 sm:p-8 text-center flex flex-col items-center">
            <div class="w-16 h-16 rounded-2xl bg-emerald-50 border border-emerald-100 flex items-center justify-center text-emerald-600 mb-5">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
            </div>
            <h3 class="text-lg font-bold text-gray-900 mb-1">Restore Archived Record</h3>
            <p class="text-xs text-gray-500 mb-6">Are you sure you want to restore <span class="font-bold text-gray-800" x-text="restoreName"></span> back to active status in the system?</p>
            
            <form :action="'/superadmin/archives/' + restoreId + '/restore'" method="POST" class="w-full flex items-center gap-3">
                @csrf
                <button type="button" @click="restoreModal = false" class="flex-1 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-bold rounded-xl transition-all cursor-pointer">Cancel</button>
                <button type="submit" class="flex-1 py-3 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl transition-all cursor-pointer shadow-sm">Restore</button>
            </form>
        </div>
    </div>

    {{-- ==================== PURGE MODAL ==================== --}}
    <div x-show="purgeModal" 
         x-cloak 
         style="display: none;"
         class="fixed inset-0 z-50 flex items-center justify-center p-4"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95">
        <div @click="purgeModal = false" class="absolute inset-0 bg-black/60 backdrop-blur-sm"></div>
        <div class="bg-white w-full max-w-sm rounded-3xl shadow-2xl relative z-10 overflow-hidden border border-gray-100 p-6 sm:p-8 text-center flex flex-col items-center">
            <div class="w-16 h-16 rounded-2xl bg-red-50 border border-red-100 flex items-center justify-center text-red-600 mb-5">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
            </div>
            <h3 class="text-lg font-bold text-gray-900 mb-1">Permanently Purge Record</h3>
            <p class="text-xs text-red-600 font-medium mb-6">This will permanently destroy the archived record of <span class="font-bold text-gray-900" x-text="purgeName"></span>. This action CANNOT be undone.</p>
            
            <form :action="'/superadmin/archives/' + purgeId" method="POST" class="w-full flex items-center gap-3">
                @csrf
                @method('DELETE')
                <button type="button" @click="purgeModal = false" class="flex-1 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-bold rounded-xl transition-all cursor-pointer">Cancel</button>
                <button type="submit" class="flex-1 py-3 bg-red-600 hover:bg-red-700 text-white text-xs font-bold rounded-xl transition-all cursor-pointer shadow-sm">Purge Forever</button>
            </form>
        </div>
    </div>

</div>
@endsection
