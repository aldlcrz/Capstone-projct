@extends('layouts.superadmin')

@section('content')
<div class="space-y-6" x-data="promotionManager({
    categories: {{ Js::from($categories) }},
    sellers: {{ Js::from($sellers) }},
    allProducts: {{ Js::from($allProducts) }},
    banners: {{ Js::from($banners) }}
})">

    {{-- Page Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="text-left space-y-0.5">
            <div class="inline-flex items-center gap-2">
                <span class="text-[9px] font-black uppercase tracking-[0.25em] text-[#C0422A]">Content &amp; Marketing</span>
                <span class="text-gray-300 text-xs">·</span>
                <span class="text-[9px] font-bold uppercase tracking-[0.2em] text-gray-400">Hero Promotions</span>
            </div>
            <h1 class="font-serif text-2xl sm:text-3xl font-bold text-gray-900 leading-tight">
                Homepage <span class="text-[#C0420A] font-light italic">Promotions</span>
            </h1>
            <p class="text-[11px] text-gray-400 font-medium">Feature artisan products, shops, and seasonal hero campaigns on the marketplace homepage</p>
        </div>
        <div class="flex items-center gap-2.5">
            <button @click="openAddModal()"
                class="flex items-center gap-2 px-5 py-2.5 bg-[#3D2B1F] text-white rounded-xl text-[10px] font-bold uppercase tracking-widest hover:bg-[#C0422A] shadow-sm transition-all cursor-pointer">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                <span>Add Promotion</span>
            </button>
        </div>
    </div>

    {{-- ── ALL PROMOTIONS ── --}}
    <div class="space-y-4">
        @if($banners->isEmpty())
            <div class="bg-white rounded-2xl border border-gray-100 p-16 text-center shadow-xs">
                <div class="w-16 h-16 bg-gray-50 rounded-2xl flex items-center justify-center mx-auto mb-4 border border-gray-100">
                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </div>
                <h3 class="text-sm font-bold text-gray-900 uppercase tracking-widest mb-1">No Promotions Created</h3>
                <p class="text-xs text-gray-400 max-w-sm mx-auto mb-6">Quickly feature products from shops or upload custom banner campaigns.</p>
                <button @click="openAddModal()" class="px-6 py-2.5 bg-[#3D2B1F] text-white rounded-xl text-xs font-bold uppercase tracking-widest hover:bg-[#C0422A] transition-all cursor-pointer shadow-sm">
                    Feature First Product / Promotion
                </button>
            </div>
        @else
            <div class="bg-white rounded-2xl border border-gray-100 overflow-hidden shadow-sm">
                <div class="p-4 bg-amber-50/60 border-b border-amber-100/80 flex items-center justify-between text-xs text-amber-900">
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-[#C0422A]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span><strong>Hero Carousel Order:</strong> Order <strong>#1</strong> appears first on the homepage. Use arrow buttons to reorder.</span>
                    </div>
                </div>

                <div class="overflow-x-auto no-scrollbar">
                    <table class="w-full text-left border-collapse min-w-175 text-xs">
                        <thead>
                            <tr class="bg-[#F8F7F4] border-b border-gray-100 text-gray-400 uppercase tracking-widest font-bold text-[9px]">
                                <th class="px-5 py-3.5 text-center w-24">Order</th>
                                <th class="px-5 py-3.5 w-40">Preview</th>
                                <th class="px-5 py-3.5">Promotion Details</th>
                                <th class="px-5 py-3.5 w-44">Schedule</th>
                                <th class="px-5 py-3.5 text-center w-28">Status</th>
                                <th class="px-5 py-3.5 text-right w-32">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($banners as $index => $banner)
                                @php
                                    $isLive = $banner->isCurrentlyLive();
                                    $now = now();
                                    $isScheduled = $banner->is_active && $banner->start_date && $banner->start_date > $now;
                                    $isExpired = $banner->is_active && $banner->end_date && $banner->end_date < $now;
                                @endphp
                                <tr class="hover:bg-gray-50/80 transition-colors group">
                                    {{-- Order Controls --}}
                                    <td class="px-5 py-4 text-center">
                                        <div class="flex items-center justify-center gap-1.5">
                                            <span class="w-6 h-6 rounded-lg bg-gray-100 text-gray-800 font-mono text-xs font-bold flex items-center justify-center">
                                                {{ $banner->order_index }}
                                            </span>
                                            <div class="flex flex-col gap-0.5">
                                                @if($index > 0)
                                                    <button type="button" @click="moveBanner({{ $index }}, 'up')" class="p-0.5 text-gray-400 hover:text-black transition-colors cursor-pointer" title="Move Up">
                                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 15l7-7 7 7"/></svg>
                                                    </button>
                                                @endif
                                                @if($index < count($banners) - 1)
                                                    <button type="button" @click="moveBanner({{ $index }}, 'down')" class="p-0.5 text-gray-400 hover:text-black transition-colors cursor-pointer" title="Move Down">
                                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
                                                    </button>
                                                @endif
                                            </div>
                                        </div>
                                    </td>

                                    {{-- Preview Image --}}
                                    <td class="px-5 py-4">
                                        <div class="w-36 aspect-video rounded-xl overflow-hidden bg-gray-900 border border-gray-100 shadow-xs relative">
                                            <img src="{{ $banner->getImageUrl() }}" class="w-full h-full object-cover" alt="Banner">
                                            <div class="absolute inset-0 bg-linear-to-r from-black/60 to-transparent"></div>
                                        </div>
                                    </td>

                                    {{-- Details --}}
                                    <td class="px-5 py-4">
                                        <div class="space-y-1">
                                            @if($banner->subtitle)
                                                <div class="text-[9px] font-bold text-[#C0422A] uppercase tracking-widest">{{ $banner->subtitle }}</div>
                                            @endif
                                            <div class="text-sm font-bold text-gray-900">{{ $banner->title ?: 'Untitled Promotion' }}</div>
                                            
                                            <div class="flex items-center gap-2 flex-wrap pt-0.5">
                                                @if($banner->button_text_1 && $banner->button_url_1)
                                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-gray-100 text-gray-700 rounded text-[9px] font-bold">
                                                        <span class="w-1.5 h-1.5 rounded-full bg-[#C0422A]"></span>
                                                        {{ $banner->button_text_1 }} → <span class="text-gray-500 font-normal font-mono">{{ Str::limit($banner->button_url_1, 28) }}</span>
                                                    </span>
                                                @endif
                                                @if($banner->button_text_2 && $banner->button_url_2)
                                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-gray-100 text-gray-700 rounded text-[9px] font-bold">
                                                        {{ $banner->button_text_2 }} → <span class="text-gray-500 font-normal font-mono">{{ Str::limit($banner->button_url_2, 28) }}</span>
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                    </td>

                                    {{-- Schedule info --}}
                                    <td class="px-5 py-4 text-xs text-gray-600">
                                        @if($banner->start_date || $banner->end_date)
                                            <div class="space-y-0.5 text-[10px]">
                                                @if($banner->start_date)
                                                    <div><span class="text-gray-400 font-medium">Start:</span> {{ $banner->start_date->format('M d, Y h:i A') }}</div>
                                                @endif
                                                @if($banner->end_date)
                                                    <div><span class="text-gray-400 font-medium">End:</span> {{ $banner->end_date->format('M d, Y h:i A') }}</div>
                                                @endif
                                            </div>
                                        @else
                                            <span class="text-[10px] text-gray-400 italic">Always active</span>
                                        @endif
                                    </td>

                                    {{-- Status Badge --}}
                                    <td class="px-5 py-4 text-center">
                                        @if(!$banner->is_active)
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[9px] font-black uppercase tracking-wider bg-gray-100 text-gray-600 border border-gray-200">
                                                Hidden
                                            </span>
                                        @elseif($isLive)
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[9px] font-black uppercase tracking-wider bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                                Live Now
                                            </span>
                                        @elseif($isScheduled)
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[9px] font-black uppercase tracking-wider bg-blue-50 text-blue-700 border border-blue-200">
                                                Scheduled
                                            </span>
                                        @elseif($isExpired)
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[9px] font-black uppercase tracking-wider bg-amber-50 text-amber-700 border border-amber-200">
                                                Expired
                                            </span>
                                        @endif
                                    </td>

                                    {{-- Actions --}}
                                    <td class="px-5 py-4 text-right">
                                        <div class="flex items-center justify-end gap-1">
                                            <form action="/superadmin/banners/{{ $banner->id }}/toggle" method="POST" class="inline">
                                                @csrf @method('PATCH')
                                                <button type="submit" class="p-2 text-gray-400 hover:text-black transition-colors cursor-pointer" title="{{ $banner->is_active ? 'Hide Promotion' : 'Show Promotion' }}">
                                                    @if($banner->is_active)
                                                        <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                                    @else
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"/></svg>
                                                    @endif
                                                </button>
                                            </form>

                                            <button @click="openEditModal({{ Js::from($banner) }})"
                                                class="p-2 text-gray-400 hover:text-black transition-colors cursor-pointer" title="Edit Promotion">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                                                </svg>
                                            </button>

                                            <form action="/superadmin/banners/{{ $banner->id }}" method="POST" class="inline"
                                                onsubmit="return confirm('Are you sure you want to delete this promotion?')">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="p-2 text-gray-400 hover:text-rose-600 transition-colors cursor-pointer" title="Delete Promotion">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                    </svg>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>

    {{-- ── PROMOTION ADD/EDIT MODAL ── --}}
    <div x-show="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4 bg-black/50 backdrop-blur-xs overflow-y-auto" x-cloak style="display: none;">
        <div class="bg-white text-gray-900 rounded-3xl w-full max-w-lg shadow-2xl border border-gray-100 overflow-hidden flex flex-col my-auto"
            @click.away="showModal = false">
            
            {{-- Modal Header --}}
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-gray-50/80">
                <h2 class="font-serif text-lg font-bold text-gray-900" x-text="isEditing ? 'Edit Hero Promotion' : 'Add Hero Promotion'"></h2>
                <button type="button" @click="showModal = false" class="text-gray-400 hover:text-black transition-colors p-1 cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            {{-- Modal Form --}}
            <form :action="isEditing ? '/superadmin/banners/' + form.id : '{{ route('superadmin.banners.store') }}'"
                  method="POST"
                  enctype="multipart/form-data"
                  id="bannerForm"
                  class="p-6 space-y-4">
                @csrf
                <template x-if="isEditing">
                    <input type="hidden" name="_method" value="PUT">
                </template>
                <input type="hidden" name="preset_image_url" :value="form.preset_image_url">
                <input type="hidden" name="button_text_1" :value="form.button_text_1">
                <input type="hidden" name="button_url_1" :value="form.button_url_1">
                <input type="hidden" name="button_text_2" :value="form.button_text_2">
                <input type="hidden" name="button_url_2" :value="form.button_url_2">
                <input type="hidden" name="subtitle" :value="form.subtitle">

                {{-- Mode Switcher (Pills) --}}
                <div class="grid grid-cols-2 gap-2">
                    <button type="button" @click="setMode('product')"
                        :class="mode === 'product' ? 'bg-gray-900 text-white font-bold' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
                        class="py-2.5 px-4 rounded-xl text-xs flex items-center justify-center gap-2 transition-all cursor-pointer">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                        <span>Existing Product</span>
                    </button>
                    <button type="button" @click="setMode('upload')"
                        :class="mode === 'upload' ? 'bg-gray-900 text-white font-bold' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
                        class="py-2.5 px-4 rounded-xl text-xs flex items-center justify-center gap-2 transition-all cursor-pointer">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                        <span>Upload Banner</span>
                    </button>
                </div>

                {{-- Mode 1: Product Selector Mode --}}
                <div x-show="mode === 'product'" class="space-y-3">
                    <div>
                        <label class="block text-[10px] font-bold uppercase tracking-widest text-gray-400 mb-1.5">Select Artisan Shop</label>
                        <select x-model="selectedShopId" @change="onShopChange()"
                            class="w-full bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2.5 text-xs text-gray-900 focus:bg-white focus:outline-none focus:border-[#C0422A] transition-colors">
                            <option value="">-- Select an Artisan Shop --</option>
                            <template x-for="seller in sellers" :key="seller.id">
                                <option :value="seller.id" x-text="seller.shop_name + ' (' + seller.products.length + ' products)'"></option>
                            </template>
                        </select>
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold uppercase tracking-widest text-gray-400 mb-1.5">Select Product to Feature</label>
                        <select x-model="selectedProductId" @change="onProductChange()" :disabled="!selectedShopId"
                            class="w-full bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2.5 text-xs text-gray-900 focus:bg-white focus:outline-none focus:border-[#C0422A] transition-colors disabled:opacity-40 disabled:cursor-not-allowed">
                            <option value="">-- Choose a Product --</option>
                            <template x-for="prod in filteredProducts" :key="prod.id">
                                <option :value="prod.id" x-text="prod.name + ' - ₱' + Number(prod.price).toLocaleString()"></option>
                            </template>
                        </select>
                    </div>
                </div>

                {{-- Mode 2: Custom Upload Mode --}}
                <div x-show="mode === 'upload'" class="space-y-3">
                    <div>
                        <label class="block text-[10px] font-bold uppercase tracking-widest text-gray-400 mb-1.5">Hero Image File</label>
                        <div class="relative border-2 border-dashed border-gray-200 hover:border-gray-400 rounded-2xl p-4 text-center cursor-pointer transition-colors bg-gray-50"
                            @click="$refs.fileInput.click()">
                            <input type="file" x-ref="fileInput" name="image" accept="image/*" class="hidden" @change="handleFileSelect($event)">
                            <div class="flex flex-col items-center justify-center gap-1">
                                <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                <span class="text-xs text-gray-700 font-bold">Click to browse image</span>
                                <span class="text-[10px] text-gray-400">16:9 banner aspect ratio recommended</span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Live Image Preview --}}
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-widest text-gray-400 mb-1.5">Banner Preview</label>
                    <div class="w-full aspect-video rounded-2xl overflow-hidden bg-gray-100 border border-gray-200 relative flex items-center justify-center">
                        <template x-if="imagePreviewUrl">
                            <img :src="imagePreviewUrl" class="w-full h-full object-cover" alt="Preview">
                        </template>
                        <template x-if="!imagePreviewUrl">
                            <div class="flex flex-col items-center gap-1 text-gray-400 text-xs">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                <span>No image selected</span>
                            </div>
                        </template>
                    </div>
                </div>

                {{-- Promotion Title --}}
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-widest text-gray-400 mb-1.5">Promotion Headline Title <span class="text-rose-500">*</span></label>
                    <input type="text" name="title" x-model="form.title" required placeholder="e.g. Authentic Lumban Piña Silk Barong"
                        class="w-full bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2.5 text-xs text-gray-900 focus:bg-white focus:outline-none focus:border-[#C0422A] transition-colors">
                </div>

                {{-- Modal Actions --}}
                <div class="pt-2 flex items-center justify-end gap-2.5">
                    <button type="button" @click="showModal = false" class="px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl text-xs font-bold uppercase tracking-wider transition-colors cursor-pointer">
                        Cancel
                    </button>
                    <button type="submit" class="px-5 py-2.5 bg-[#3D2B1F] hover:bg-[#C0422A] text-white rounded-xl text-xs font-bold uppercase tracking-wider transition-colors cursor-pointer shadow-sm">
                        <span x-text="isEditing ? 'Save Changes' : 'Create Promotion'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>

<script>
function promotionManager(initialData) {
    return {
        categories: initialData.categories || [],
        sellers: initialData.sellers || [],
        allProducts: initialData.allProducts || [],
        bannersList: initialData.banners || [],
        showModal: false,
        isEditing: false,
        mode: 'product',
        selectedShopId: '',
        selectedProductId: '',
        imagePreviewUrl: '',
        fileInfo: { name: '', dimensions: '' },
        form: {
            id: null,
            title: '',
            subtitle: '',
            button_text_1: 'Shop now',
            button_url_1: '',
            button_text_2: 'Visit shop',
            button_url_2: '',
            order_index: 1,
            is_active: true,
            start_date: '',
            end_date: '',
            preset_image_url: ''
        },

        get filteredProducts() {
            if (!this.selectedShopId) return [];
            var shop = this.sellers.find(s => String(s.id) === String(this.selectedShopId));
            return shop ? shop.products : [];
        },

        setMode(m) {
            this.mode = m;
        },

        openAddModal() {
            this.isEditing = false;
            this.mode = 'product';
            this.selectedShopId = '';
            this.selectedProductId = '';
            this.imagePreviewUrl = '';
            this.fileInfo = { name: '', dimensions: '' };
            this.form = {
                id: null,
                title: '',
                subtitle: '',
                button_text_1: 'Shop now',
                button_url_1: '',
                button_text_2: 'Visit shop',
                button_url_2: '',
                order_index: this.bannersList.length + 1,
                is_active: true,
                start_date: '',
                end_date: '',
                preset_image_url: ''
            };
            this.showModal = true;
        },

        openEditModal(banner) {
            this.isEditing = true;
            var initialImg = banner.image_path ? (banner.image_path.startsWith('http') || banner.image_path.startsWith('/') ? banner.image_path : '/storage/' + banner.image_path) : '';
            this.imagePreviewUrl = initialImg;
            this.fileInfo = { name: '', dimensions: '' };
            this.form = {
                id: banner.id,
                title: banner.title || '',
                subtitle: banner.subtitle || '',
                button_text_1: banner.button_text_1 || 'Shop now',
                button_url_1: banner.button_url_1 || '',
                button_text_2: banner.button_text_2 || 'Visit shop',
                button_url_2: banner.button_url_2 || '',
                order_index: banner.order_index || 1,
                is_active: Boolean(banner.is_active),
                start_date: banner.start_date || '',
                end_date: banner.end_date || '',
                preset_image_url: initialImg
            };
            this.mode = banner.image_path ? 'upload' : 'product';
            this.showModal = true;
        },

        onShopChange() {
            if (!this.selectedShopId) {
                this.form.subtitle = '';
                this.form.button_url_2 = '';
                return;
            }
            var shop = this.sellers.find(s => String(s.id) === String(this.selectedShopId));
            if (shop) {
                this.form.subtitle = shop.shop_name;
                this.form.button_url_2 = '/shops/' + shop.id;
            }
            this.selectedProductId = '';
        },

        onProductChange() {
            if (!this.selectedProductId) return;
            var prod = this.allProducts.find(p => String(p.id) === String(this.selectedProductId));
            if (prod) {
                this.form.title = prod.name;
                this.form.button_url_1 = '/products/' + prod.id;
                this.form.preset_image_url = prod.image_url;
                this.imagePreviewUrl = prod.image_url;
            }
        },

        handleFileSelect(event) {
            var file = event.target.files[0];
            if (!file) return;
            var self = this;
            var reader = new FileReader();
            reader.onload = function(e) {
                self.imagePreviewUrl = e.target.result;
                self.form.preset_image_url = '';
            };
            reader.readAsDataURL(file);
        },

        moveBanner(index, direction) {
            var newIndex = direction === 'up' ? index - 1 : index + 1;
            if (newIndex < 0 || newIndex >= this.bannersList.length) return;

            var item = this.bannersList.splice(index, 1)[0];
            this.bannersList.splice(newIndex, 0, item);

            var orderedIds = this.bannersList.map(function(b) { return b.id; });
            var token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            fetch('{{ route("superadmin.banners.reorder") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': token
                },
                body: JSON.stringify({ ordered_ids: orderedIds })
            })
            .then(function(res) { return res.json(); })
            .then(function() {
                window.location.reload();
            })
            .catch(function(err) {
                console.error('Reorder error', err);
            });
        }
    };
}
</script>
@endsection
