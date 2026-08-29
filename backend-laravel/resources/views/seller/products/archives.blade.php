@extends('layouts.seller')

@section('content')
<div class="space-y-6 sm:space-y-8" x-data="sellerArchives()">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 pb-2 border-b" style="border-color: #E8DECB;">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="text-[9px] font-extrabold uppercase tracking-[0.25em]" style="color: #C49520;">✦ Shop Catalogue</span>
                <span class="text-xs" style="color: #E8DECB;">•</span>
                <span class="text-[10px] font-semibold tracking-wider uppercase" style="color: #766C60;">Archived Registry</span>
            </div>
            <h1 class="font-serif text-2xl sm:text-3xl lg:text-4xl font-bold tracking-tight" style="color: #1E1915;">
                Product <span class="italic font-normal" style="color: #766C60;">Archives</span>
            </h1>
            <p class="text-xs sm:text-sm font-medium mt-1" style="color: #766C60;">
                Archived products are stored here. You can restore any item back to your active catalogue for admin review.
            </p>
        </div>
        <div class="flex items-center gap-2.5 sm:gap-3">
            <a href="{{ route('seller.products.index') }}"
               class="flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold transition-all shadow-xs"
               style="background: #FDF8EE; border: 1px solid #E8DECB; color: #1E1915;"
               onmouseover="this.style.borderColor='#C49520';"
               onmouseout="this.style.borderColor='#E8DECB';">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                <span>Back to Catalogue</span>
            </a>
        </div>
    </div>

    {{-- Flash Messages --}}
    @if(session('success'))
        <div x-data="{ show: true, init() { setTimeout(() => this.show = false, 5000) } }" x-show="show"
             class="p-3.5 rounded-2xl text-xs font-semibold"
             style="background: #F0FDF4; border: 1px solid #BBF7D0; color: #166534;">
            ✓ {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div x-data="{ show: true, init() { setTimeout(() => this.show = false, 6000) } }" x-show="show"
             class="p-3.5 rounded-2xl text-xs font-semibold"
             style="background: #FEF2F2; border: 1px solid #FECACA; color: #991B1B;">
            ✕ {{ session('error') }}
        </div>
    @endif

    {{-- Stats Bar & Search --}}
    <div class="p-3.5 sm:p-4 rounded-2xl sm:rounded-3xl flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3 shadow-xs"
         style="background: #FFFCF7; border: 1px solid #E8DECB;">
        <div class="flex items-center gap-2">
            <div class="w-8 h-8 rounded-xl flex items-center justify-center shrink-0"
                 style="background: #FDF8EE; color: #C49520; border: 1px solid #E8DECB;">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                </svg>
            </div>
            <div>
                <div class="text-[10px] font-bold uppercase tracking-widest" style="color: #766C60;">Total Archived</div>
                <div class="text-sm font-black font-sans" style="color: #1E1915;">
                    {{ $archivesCount }} {{ $archivesCount === 1 ? 'Product' : 'Products' }}
                </div>
            </div>
        </div>

        <form method="GET" action="{{ route('seller.products.archives') }}" class="flex items-center gap-2">
            <div class="relative w-full sm:w-72">
                <input type="text" name="search" value="{{ $search }}"
                    placeholder="Search archived products..."
                    class="w-full pl-9 pr-4 py-2 rounded-xl text-xs font-semibold outline-none transition-all"
                    style="background: #FDF8EE; border: 1px solid #E8DECB; color: #1E1915;"
                    onfocus="this.style.borderColor='#C49520'; this.style.background='#FFF';"
                    onblur="this.style.borderColor='#E8DECB'; this.style.background='#FDF8EE';">
                <svg class="w-4 h-4 absolute left-3 top-2.5" style="color: #766C60;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </div>
            <button type="submit"
                    class="px-4 py-2 text-white rounded-xl text-xs font-bold transition-all shadow-xs"
                    style="background: #1E1915;"
                    onmouseover="this.style.background='#C49520';"
                    onmouseout="this.style.background='#1E1915';">Search</button>
            @if($search)
                <a href="{{ route('seller.products.archives') }}"
                   class="px-3 py-2 rounded-xl text-xs font-bold transition-all"
                   style="background: #FDF8EE; border: 1px solid #E8DECB; color: #766C60;"
                   onmouseover="this.style.borderColor='#C49520';"
                   onmouseout="this.style.borderColor='#E8DECB';">✕</a>
            @endif
        </form>
    </div>

    {{-- Archives Grid --}}
    @if($archives->isEmpty())
        <div class="py-20 text-center rounded-3xl" style="background: #FFFCF7; border: 2px dashed #E8DECB;">
            <div class="w-12 h-12 rounded-2xl mx-auto mb-3 flex items-center justify-center text-xl"
                 style="background: #FDF8EE; color: #C49520; border: 1px solid #E8DECB;">🗄</div>
            <h3 class="font-serif text-lg font-bold mb-1" style="color: #1E1915;">No Archived Products</h3>
            <p class="text-xs max-w-xs mx-auto" style="color: #766C60;">
                @if($search)
                    No archived products match "{{ $search }}".
                    <a href="{{ route('seller.products.archives') }}" style="color:#C49520;font-weight:700;">Clear search</a>
                @else
                    Products you remove from your active catalogue will appear here for safekeeping.
                @endif
            </p>
        </div>
    @else
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($archives as $record)
                @php
                    $meta  = $record->metadata ?? [];
                    $image = $meta['image'] ?? null;
                    $price = $meta['price'] ?? 0;
                    $sku   = $meta['sku'] ?? $record->identifier ?? null;
                @endphp
                <div class="rounded-2xl sm:rounded-3xl shadow-xs overflow-hidden flex flex-col"
                     style="background: #FFFCF7; border: 1px solid #E8DECB;"
                     onmouseover="this.style.borderColor='#C49520'; this.style.boxShadow='0 8px 24px rgba(30,25,21,0.06)';"
                     onmouseout="this.style.borderColor='#E8DECB'; this.style.boxShadow='0 1px 3px rgba(0,0,0,0.02)';">

                    {{-- Product Image --}}
                    <div class="relative aspect-[4/3] overflow-hidden" style="background: #F5F0E8;">
                        @if($image)
                            <img src="{{ str_starts_with($image, 'http') || str_starts_with($image, '/') ? $image : asset($image) }}"
                                 onerror="this.src='/uploads/products/default.jpg'"
                                 class="w-full h-full object-cover object-top opacity-75">
                        @else
                            <div class="w-full h-full flex items-center justify-center" style="color: #C49520;">
                                <svg class="w-12 h-12 opacity-20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                          d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                            </div>
                        @endif
                        <div class="absolute top-2.5 left-2.5">
                            <span class="px-2 py-0.5 rounded-full text-[8px] font-black uppercase tracking-wider shadow-xs"
                                  style="background: #FDF8EE; color: #766C60; border: 1px solid #E8DECB;">
                                🗄 Archived
                            </span>
                        </div>
                    </div>

                    {{-- Info --}}
                    <div class="p-4 flex-1 flex flex-col gap-2">
                        <div>
                            <h3 class="font-serif text-sm font-bold leading-tight" style="color: #1E1915;">{{ $record->name }}</h3>
                            @if($sku)
                                <div class="text-[10px] font-medium mt-0.5" style="color: #766C60;">SKU: {{ $sku }}</div>
                            @endif
                        </div>

                        <div class="flex items-center justify-between">
                            <span class="text-xs font-black font-sans" style="color: #C49520;">
                                ₱{{ number_format((float)$price, 2) }}
                            </span>
                            <span class="text-[9px] font-medium" style="color: #A09585;">
                                {{ $record->created_at ? \Carbon\Carbon::parse($record->created_at)->diffForHumans() : 'N/A' }}
                            </span>
                        </div>

                        @if($record->reason)
                            <div class="text-[10px] leading-relaxed px-2.5 py-1.5 rounded-xl"
                                 style="background: #FDF8EE; color: #766C60; border: 1px solid #E8DECB;">
                                {{ Str::limit($record->reason, 80) }}
                            </div>
                        @endif

                        {{-- Restore Button --}}
                        <button type="button"
                                @click="openRestoreModal('{{ $record->id }}', '{{ addslashes($record->name) }}')"
                                class="mt-auto w-full py-2 text-xs font-bold uppercase tracking-wider rounded-xl transition-all border cursor-pointer"
                                style="background: #FDF8EE; border-color: #E8DECB; color: #1E1915;"
                                onmouseover="this.style.background='#1E1915'; this.style.color='#FFFCF7'; this.style.borderColor='#1E1915';"
                                onmouseout="this.style.background='#FDF8EE'; this.style.color='#1E1915'; this.style.borderColor='#E8DECB';">
                            ↩ Restore to Catalogue
                        </button>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Pagination --}}
        @if($archives->hasPages())
            <div class="pt-4 border-t flex items-center justify-between flex-wrap gap-4" style="border-color: #E8DECB;">
                <div class="text-xs font-semibold" style="color: #766C60;">
                    Showing <span class="font-bold" style="color: #1E1915;">{{ $archives->firstItem() ?? 1 }}</span>
                    to <span class="font-bold" style="color: #1E1915;">{{ $archives->lastItem() ?? 1 }}</span>
                    of <span class="font-bold" style="color: #1E1915;">{{ $archives->total() }}</span> archived products
                </div>
                {{ $archives->links() }}
            </div>
        @endif
    @endif

    {{-- Restore Confirmation Modal --}}
    <div x-show="showRestoreModal"
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-xs"
         x-cloak>
        <div class="rounded-3xl w-full max-w-md p-6 sm:p-7 shadow-2xl space-y-5"
             style="background: #FFFCF7; border: 1px solid #E8DECB;"
             @click.away="showRestoreModal = false">

            <div class="flex items-start gap-3.5">
                <div class="w-12 h-12 rounded-2xl flex items-center justify-center shrink-0"
                     style="background: #FDF8EE; color: #C49520; border: 1px solid #E8DECB;">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                </div>
                <div class="min-w-0">
                    <h3 class="font-serif text-base sm:text-lg font-bold leading-tight" style="color: #1E1915;">Restore Creation</h3>
                    <p class="text-xs mt-1" style="color: #766C60;">
                        Restore <strong x-text="restoringProductName" class="text-stone-900"></strong> back to your active catalogue?
                    </p>
                </div>
            </div>

            <p class="text-xs leading-relaxed p-3 rounded-2xl" style="background: #FDF8EE; border: 1px solid #E8DECB; color: #766C60;">
                The product will be submitted for admin review before it becomes visible to customers again.
            </p>

            <form :action="'/seller/products/' + restoringProductId + '/restore'" method="POST" class="flex gap-3 pt-1">
                @csrf
                <button type="button" @click="showRestoreModal = false"
                        class="flex-1 py-2.5 text-xs font-bold rounded-xl transition-all cursor-pointer"
                        style="background: #FDF8EE; border: 1px solid #E8DECB; color: #1E1915;">
                    Cancel
                </button>
                <button type="submit"
                        class="flex-1 py-2.5 text-white text-xs font-bold uppercase tracking-wider rounded-xl transition-all border-0 cursor-pointer shadow-xs"
                        style="background: #1E1915;"
                        onmouseover="this.style.background='#C49520';"
                        onmouseout="this.style.background='#1E1915';">
                    ↩ Restore
                </button>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function sellerArchives() {
    return {
        showRestoreModal: false,
        restoringProductId: null,
        restoringProductName: '',
        openRestoreModal(id, name) {
            this.restoringProductId = id;
            this.restoringProductName = name;
            this.showRestoreModal = true;
        },
    };
}
</script>
@endpush
