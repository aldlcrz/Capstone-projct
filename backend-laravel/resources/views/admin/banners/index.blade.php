@extends('layouts.admin')

@section('content')
<div class="space-y-8" x-data="promotionManager({
    categories: {{ Js::from($categories) }},
    sellers: {{ Js::from($sellers) }},
    allProducts: {{ Js::from($allProducts) }},
    banners: {{ Js::from($banners) }}
})">

    {{-- Page Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="text-[10px] font-bold text-[#C0422A] uppercase tracking-[0.2em] mb-1">Content Management</div>
            <h1 class="font-serif text-2xl sm:text-3xl font-bold text-black">Homepage <span class="text-[#C0420A] font-light italic">Promotions</span></h1>
            <p class="text-xs text-gray-500 mt-1">Feature artisan products, shops, and seasonal hero campaigns on the marketplace homepage.</p>
        </div>
        <div class="flex items-center gap-2.5">
            <button @click="openAddModal()"
                class="flex items-center gap-2 px-5 sm:px-6 py-2.5 sm:py-3 bg-[#3D2B1F] text-white rounded-xl text-[9px] sm:text-[10px] font-bold uppercase tracking-widest hover:bg-[#C0422A] shadow-sm transition-all cursor-pointer">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Add Promotion
            </button>
        </div>
    </div>

    {{-- ── ALL PROMOTIONS ── --}}
    <div class="space-y-4">
        @if($banners->isEmpty())
            <div class="bg-white rounded-3xl border border-gray-100 p-16 text-center shadow-sm">
                <div class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-4 border border-gray-100">
                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </div>
                <h3 class="text-sm font-bold text-black uppercase tracking-widest mb-1">No Promotions Created</h3>
                <p class="text-xs text-gray-500 max-w-sm mx-auto mb-6">Quickly feature products from shops or upload custom banner campaigns.</p>
                <button @click="openAddModal()" class="px-6 py-2.5 bg-[#3D2B1F] text-white rounded-xl text-xs font-bold uppercase tracking-widest hover:bg-[#C0422A] transition-all cursor-pointer">
                    Feature First Product / Promotion
                </button>
            </div>
        @else
            <div class="bg-white rounded-3xl border border-gray-100 overflow-hidden shadow-sm">
                <div class="p-4 bg-amber-50/40 border-b border-amber-100/60 flex items-center justify-between text-xs text-amber-900">
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-[#C0422A]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span><strong>Hero Carousel Order:</strong> Order <strong>#1</strong> appears first on the homepage. Use arrow buttons to reorder.</span>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-50/50">
                                <th class="px-5 py-4 text-[10px] font-black text-gray-700 uppercase tracking-widest text-center w-24">Order</th>
                                <th class="px-5 py-4 text-[10px] font-black text-gray-700 uppercase tracking-widest w-40">Preview</th>
                                <th class="px-5 py-4 text-[10px] font-black text-gray-700 uppercase tracking-widest">Promotion Details</th>
                                <th class="px-5 py-4 text-[10px] font-black text-gray-700 uppercase tracking-widest w-44">Schedule</th>
                                <th class="px-5 py-4 text-[10px] font-black text-gray-700 uppercase tracking-widest text-center w-28">Status</th>
                                <th class="px-5 py-4 text-[10px] font-black text-gray-700 uppercase tracking-widest text-right w-32">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @foreach($banners as $index => $banner)
                                @php
                                    $isLive = $banner->isCurrentlyLive();
                                    $now = now();
                                    $isScheduled = $banner->is_active && $banner->start_date && $banner->start_date > $now;
                                    $isExpired = $banner->is_active && $banner->end_date && $banner->end_date < $now;
                                @endphp
                                <tr class="hover:bg-gray-50/60 transition-colors group">
                                    {{-- Order Controls --}}
                                    <td class="px-5 py-4 text-center">
                                        <div class="flex items-center justify-center gap-1.5">
                                            <span class="w-6 h-6 rounded-lg bg-gray-100 text-gray-800 font-mono text-xs font-bold flex items-center justify-center">
                                                {{ $banner->order_index }}
                                            </span>
                                            <div class="flex flex-col gap-0.5">
                                                @if($index > 0)
                                                    <button type="button" @click="moveBanner({{ $index }}, 'up')" class="p-0.5 text-gray-400 hover:text-black transition-colors" title="Move Up">
                                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 15l7-7 7 7"/></svg>
                                                    </button>
                                                @endif
                                                @if($index < count($banners) - 1)
                                                    <button type="button" @click="moveBanner({{ $index }}, 'down')" class="p-0.5 text-gray-400 hover:text-black transition-colors" title="Move Down">
                                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
                                                    </button>
                                                @endif
                                            </div>
                                        </div>
                                    </td>

                                    {{-- Preview Image --}}
                                    <td class="px-5 py-4">
                                        <div class="w-36 aspect-16/9 rounded-xl overflow-hidden bg-gray-900 border border-gray-100 shadow-xs relative">
                                            <img src="{{ $banner->getImageUrl() }}" class="w-full h-full object-cover" alt="Banner">
                                            <div class="absolute inset-0 bg-linear-to-r from-black/60 to-transparent"></div>
                                        </div>
                                    </td>

                                    {{-- Details --}}
                                    <td class="px-5 py-4">
                                        <div class="space-y-1">
                                            @if($banner->subtitle)
                                                <div class="text-[9px] font-bold text-amber-600 uppercase tracking-widest">{{ $banner->subtitle }}</div>
                                            @endif
                                            <div class="text-sm font-extrabold text-gray-900">{{ $banner->title ?: 'Untitled Promotion' }}</div>
                                            
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
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[9px] font-black uppercase tracking-wider bg-green-50 text-green-700 border border-green-200">
                                                <span class="w-1.5 h-1.5 rounded-full bg-green-500 animate-pulse"></span>
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
                                            <form action="{{ route('admin.banners.toggle', $banner->id) }}" method="POST" class="inline">
                                                @csrf @method('PATCH')
                                                <button type="submit" class="p-2 text-gray-400 hover:text-black transition-colors" title="{{ $banner->is_active ? 'Hide Promotion' : 'Show Promotion' }}">
                                                    @if($banner->is_active)
                                                        <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                                    @else
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"/></svg>
                                                    @endif
                                                </button>
                                            </form>

                                            <button @click="openEditModal({{ Js::from($banner) }})"
                                                class="p-2 text-gray-500 hover:text-black transition-colors cursor-pointer" title="Edit Promotion">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                                                </svg>
                                            </button>

                                            <form action="{{ route('admin.banners.destroy', $banner->id) }}" method="POST" class="inline"
                                                onsubmit="return confirm('Are you sure you want to delete this promotion?')">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="p-2 text-gray-400 hover:text-red-600 transition-colors cursor-pointer" title="Delete Promotion">
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

    {{-- ── EXACT MODAL REDESIGN (MATCHING USER MOCKUP) ── --}}
    <div x-show="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4 bg-black/80 backdrop-blur-md overflow-y-auto" x-cloak>
        <div class="bg-[#141414] text-white rounded-3xl w-full max-w-[520px] shadow-2xl border border-[#272727] overflow-hidden flex flex-col my-auto"
            @click.away="showModal = false">
            
            {{-- Modal Header --}}
            <div class="px-6 pt-5 pb-3 flex items-center justify-between">
                <h2 class="text-base font-semibold text-white" x-text="isEditing ? 'Edit hero promotion' : 'Add hero promotion'"></h2>
                <button type="button" @click="showModal = false" class="text-gray-400 hover:text-white transition-colors p-1 cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            {{-- Modal Form --}}
            <form :action="isEditing ? '/admin/banners/' + form.id : '{{ route('admin.banners.store') }}'"
                  method="POST"
                  enctype="multipart/form-data"
                  id="bannerForm"
                  class="px-6 pb-6 space-y-4">
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
                        :class="mode === 'product' ? 'bg-[#262626] text-white font-medium border border-[#383838]' : 'bg-[#181818] text-gray-400 hover:text-gray-200 border border-transparent'"
                        class="py-2 px-4 rounded-xl text-xs flex items-center justify-center gap-2 transition-all cursor-pointer">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                        <span>Existing product</span>
                    </button>
                    <button type="button" @click="setMode('upload')"
                        :class="mode === 'upload' ? 'bg-[#262626] text-white font-medium border border-[#383838]' : 'bg-[#181818] text-gray-400 hover:text-gray-200 border border-transparent'"
                        class="py-2 px-4 rounded-xl text-xs flex items-center justify-center gap-2 transition-all cursor-pointer">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                        <span>Upload image</span>
                    </button>
                </div>

                {{-- ── 1. EXISTING PRODUCT MODE ── --}}
                <div x-show="mode === 'product'" class="space-y-3">
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="text-[11px] text-gray-400 block mb-1.5">Shop</label>
                            <div class="relative">
                                <select x-model="selectedShopId" @change="onShopSelected()"
                                    class="w-full appearance-none bg-[#1A1A1A] border border-[#2B2B2B] text-white text-xs rounded-xl px-3.5 py-2.5 pr-8 focus:outline-none focus:border-gray-500 transition-colors cursor-pointer">
                                    <option value="">Select Shop</option>
                                    <template x-for="s in sellers" :key="s.id">
                                        <option :value="s.id" x-text="s.shop_name"></option>
                                    </template>
                                </select>
                                <div class="absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none text-gray-400">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                </div>
                            </div>
                        </div>

                        <div>
                            <label class="text-[11px] text-gray-400 block mb-1.5">Product</label>
                            <div class="relative">
                                <select x-model="selectedProductId" @change="onProductSelected()"
                                    class="w-full appearance-none bg-[#1A1A1A] border border-[#2B2B2B] text-white text-xs rounded-xl px-3.5 py-2.5 pr-8 focus:outline-none focus:border-gray-500 transition-colors cursor-pointer">
                                    <option value="">Select Product</option>
                                    <template x-for="p in selectableProducts" :key="p.id">
                                        <option :value="p.id" x-text="p.name"></option>
                                    </template>
                                </select>
                                <div class="absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none text-gray-400">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Headline Input Bar (Auto-fills with product name, editable by admin) --}}
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label class="text-[11px] text-gray-400 block">Headline</label>
                            <span class="text-[9px] text-gray-500 font-mono" x-show="form.title" x-text="form.title.length + ' / 60'"></span>
                        </div>
                        <input type="text" name="title" x-model="form.title" maxlength="60"
                            placeholder="Handcrafted piña silk barong"
                            class="w-full bg-[#1A1A1A] border border-[#2B2B2B] text-white text-xs rounded-xl px-3.5 py-2.5 focus:outline-none focus:border-gray-500 transition-colors">
                    </div>

                    <div class="flex items-center gap-1.5 text-[11px] text-gray-400 pt-0.5">
                        <svg class="w-3.5 h-3.5 text-amber-400 shrink-0" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2l2.4 7.4H22l-6 4.6 2.3 7.4-6.3-4.6-6.3 4.6 2.3-7.4-6-4.6h7.6z"/></svg>
                        <span>Headline, image, and both links fill in automatically.</span>
                    </div>
                </div>

                {{-- ── 2. UPLOAD IMAGE MODE ── --}}
                <div x-show="mode === 'upload'" class="space-y-3" style="display:none;" x-cloak>
                    <div>
                        <label class="text-[11px] text-gray-400 block mb-1.5">Banner image</label>
                        <label class="border border-dashed border-[#333] hover:border-gray-500 bg-[#171717] rounded-xl p-5 flex flex-col items-center justify-center gap-1.5 cursor-pointer transition-colors block text-center">
                            <svg class="w-5 h-5 text-gray-400 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            <div class="text-xs text-gray-400">
                                1200 x 400px recommended · <span class="text-blue-400 hover:underline">Choose file</span>
                            </div>
                            <input type="file" name="image" @change="handleFileSelect($event)" accept="image/*" class="hidden">
                        </label>
                        <div x-show="fileInfo.name" class="text-[10px] text-gray-400 mt-1 flex items-center gap-1.5">
                            <span class="text-green-400">✓</span>
                            <span x-text="fileInfo.name"></span>
                            <span x-show="fileInfo.dimensions" x-text="'(' + fileInfo.dimensions + ')'"></span>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div>
                            <label class="text-[11px] text-gray-400 block mb-1.5">Headline</label>
                            <input type="text" name="title" x-model="form.title" placeholder="Handcrafted piña silk barong"
                                class="w-full bg-[#1A1A1A] border border-[#2B2B2B] text-white text-xs rounded-xl px-3.5 py-2.5 focus:outline-none focus:border-gray-500 transition-colors">
                        </div>
                        <div>
                            <label class="text-[11px] text-gray-400 block mb-1.5">Shop / Subtitle</label>
                            <input type="text" x-model="form.subtitle" placeholder="LumBarong Shop"
                                class="w-full bg-[#1A1A1A] border border-[#2B2B2B] text-white text-xs rounded-xl px-3.5 py-2.5 focus:outline-none focus:border-gray-500 transition-colors">
                        </div>
                        <div>
                            <label class="text-[11px] text-gray-400 block mb-1.5">Shop link</label>
                            <input type="text" x-model="form.button_url_2" placeholder="/shops"
                                class="w-full bg-[#1A1A1A] border border-[#2B2B2B] text-white text-xs rounded-xl px-3.5 py-2.5 focus:outline-none focus:border-gray-500 transition-colors font-mono text-[11px]">
                        </div>
                    </div>
                </div>

                {{-- ── 3. CLEAN LIVE PREVIEW CARD (MATCHING USER MOCKUP) ── --}}
                <div class="relative w-full rounded-2xl overflow-hidden bg-[#0A0A0A] border border-[#222] min-h-[140px] p-5 flex flex-col justify-between shadow-inner">
                    {{-- Background simulation --}}
                    <template x-if="imagePreviewUrl">
                        <img :src="imagePreviewUrl" class="absolute inset-0 w-full h-full object-cover opacity-60">
                    </template>
                    <div class="absolute inset-0 bg-gradient-to-r from-black/90 via-black/60 to-transparent"></div>

                    {{-- Foreground content --}}
                    <div class="relative z-10 space-y-0.5">
                        <h4 class="text-sm font-bold text-white tracking-tight" x-text="form.title || 'Handcrafted piña silk barong'"></h4>
                        <p class="text-[11px] text-gray-400 font-medium" x-text="form.subtitle || 'LumBarong Shop'"></p>
                    </div>

                    {{-- Action buttons simulation --}}
                    <div class="relative z-10 flex items-center gap-2 pt-3">
                        <div class="flex items-center gap-1.5 px-3 py-1.5 bg-[#1F1F1F] border border-[#333] text-white text-[10px] font-medium rounded-lg shadow-sm">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                            <span>Shop now</span>
                        </div>
                        <div class="px-3 py-1.5 bg-transparent border border-[#333] text-gray-300 text-[10px] font-medium rounded-lg">
                            <span>Visit shop</span>
                        </div>
                    </div>
                </div>

                {{-- ── 4. SCHEDULING EXPANDABLE ── --}}
                <div x-show="showSchedule" class="p-3.5 bg-[#1A1A1A] border border-[#2B2B2B] rounded-xl space-y-2.5" style="display:none;" x-cloak>
                    <div class="text-[10px] font-bold uppercase tracking-wider text-amber-400">Schedule Visibility</div>
                    <div class="grid grid-cols-2 gap-2.5">
                        <div>
                            <label class="text-[10px] text-gray-400 block mb-1">Start Date</label>
                            <input type="datetime-local" name="start_date" x-model="form.start_date"
                                class="w-full bg-[#111] border border-[#333] text-white text-[11px] rounded-lg px-2.5 py-1.5">
                        </div>
                        <div>
                            <label class="text-[10px] text-gray-400 block mb-1">End Date</label>
                            <input type="datetime-local" name="end_date" x-model="form.end_date"
                                class="w-full bg-[#111] border border-[#333] text-white text-[11px] rounded-lg px-2.5 py-1.5">
                        </div>
                    </div>
                </div>

                {{-- ── 5. BOTTOM CONTROLS & PUBLISH ── --}}
                <div class="flex items-center justify-between pt-1">
                    <label class="flex items-center gap-2 cursor-pointer text-xs text-gray-300 select-none">
                        <input type="checkbox" name="is_active" value="1" x-model="form.is_active"
                            class="w-4 h-4 rounded bg-[#1A1A1A] border-[#333] text-white focus:ring-0 cursor-pointer">
                        <span>Active now</span>
                    </label>

                    <button type="button" @click="showSchedule = !showSchedule"
                        class="flex items-center gap-1.5 px-3 py-1.5 bg-[#1A1A1A] hover:bg-[#242424] border border-[#2B2B2B] text-gray-300 text-xs rounded-xl transition-colors cursor-pointer">
                        <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        <span x-text="showSchedule ? 'Hide schedule' : 'Schedule instead'"></span>
                    </button>
                </div>

                {{-- Action Buttons --}}
                <div class="grid grid-cols-2 gap-3 pt-2">
                    <button type="button" @click="showModal = false"
                        class="py-2.5 px-4 bg-[#1F1F1F] hover:bg-[#292929] border border-[#333] text-white text-xs font-semibold rounded-xl transition-colors cursor-pointer">
                        Cancel
                    </button>
                    <button type="submit"
                        class="py-2.5 px-4 bg-white hover:bg-gray-100 text-black text-xs font-bold rounded-xl transition-colors cursor-pointer shadow-md">
                        <span x-text="isEditing ? 'Save Changes' : 'Publish'"></span>
                    </button>
                </div>

            </form>
    </div>

</div>

<script>
function promotionManager(initialData) {
    return {
        tab: '{{ $pendingCount > 0 ? "requests" : "all" }}',
        showModal: false,
        showRejectModal: false,
        showSchedule: false,
        isEditing: false,
        mode: 'product', // 'product' or 'upload'
        rejectRoute: '',
        rejectBannerTitle: '',
        imagePreviewUrl: '',
        fileInfo: { name: '', dimensions: '' },
        
        categories: initialData.categories || [],
        sellers: initialData.sellers || [],
        allProducts: initialData.allProducts || [],
        bannersList: initialData.banners || [],

        selectedShopId: '',
        selectedProductId: '',

        get selectableProducts() {
            if (!this.selectedShopId) {
                return this.allProducts;
            }
            var self = this;
            return this.allProducts.filter(function(p) {
                return String(p.seller_id) === String(self.selectedShopId);
            });
        },

        form: {
            id: '',
            title: '',
            subtitle: 'LumBarong Shop',
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

        setMode(newMode) {
            this.mode = newMode;
            if (newMode === 'upload' && !this.form.title) {
                this.form.title = 'Handcrafted piña silk barong';
                this.form.subtitle = 'LumBarong Shop';
                this.form.button_text_1 = 'Shop now';
                this.form.button_url_1 = '/#catalogue-section';
                this.form.button_text_2 = 'Visit shop';
                this.form.button_url_2 = '/shops';
            }
        },

        onShopSelected() {
            this.selectedProductId = '';
            if (this.selectedShopId) {
                var shop = this.sellers.find(s => String(s.id) === String(this.selectedShopId));
                if (shop) {
                    this.form.subtitle = shop.shop_name || shop.name || 'LumBarong Shop';
                    this.form.button_url_2 = '/shops/' + shop.id;
                    if (shop.products && shop.products.length > 0) {
                        this.selectedProductId = shop.products[0].id;
                        this.onProductSelected();
                    }
                }
            } else {
                this.form.subtitle = 'LumBarong Shop';
            }
        },

        onProductSelected() {
            if (!this.selectedProductId) return;
            var product = this.allProducts.find(p => String(p.id) === String(this.selectedProductId));
            if (!product) return;

            this.selectedShopId = product.seller_id;
            this.form.title = product.name;
            this.form.subtitle = product.shop_name || 'LumBarong Shop';
            this.form.button_text_1 = 'Shop now';
            this.form.button_url_1 = '/products/' + product.id;
            this.form.button_text_2 = 'Visit shop';
            this.form.button_url_2 = '/shops/' + product.seller_id;
            
            if (product.image_url) {
                this.imagePreviewUrl = product.image_url;
                this.form.preset_image_url = product.image_url;
            }
        },

        openAddModal() {
            this.isEditing = false;
            this.mode = 'product';
            this.showSchedule = false;
            this.imagePreviewUrl = '';
            this.fileInfo = { name: '', dimensions: '' };

            this.form = {
                id: '',
                title: '',
                subtitle: 'LumBarong Shop',
                button_text_1: 'Shop now',
                button_url_1: '',
                button_text_2: 'Visit shop',
                button_url_2: '',
                order_index: (this.bannersList.length + 1),
                is_active: true,
                start_date: '',
                end_date: '',
                preset_image_url: ''
            };

            // Pre-select first shop if available
            if (this.sellers.length > 0) {
                this.selectedShopId = this.sellers[0].id;
                this.onShopSelected();
            } else if (this.allProducts.length > 0) {
                this.selectedProductId = this.allProducts[0].id;
                this.onProductSelected();
            }

            this.showModal = true;
        },

        openEditModal(banner) {
            this.isEditing = true;
            this.showSchedule = Boolean(banner.start_date || banner.end_date);
            this.imagePreviewUrl = banner.image_path ? (banner.image_path.startsWith('http') || banner.image_path.startsWith('/') ? banner.image_path : '/' + banner.image_path) : '';
            this.fileInfo = { name: 'Current Image', dimensions: '' };
            
            var formatDt = function(dtStr) {
                if (!dtStr) return '';
                var d = new Date(dtStr);
                if (isNaN(d.getTime())) return '';
                return d.toISOString().slice(0, 16);
            };

            var cleanSubtitle = banner.subtitle || '';
            if (!cleanSubtitle || cleanSubtitle.toLowerCase().includes('macapagal')) {
                cleanSubtitle = 'LumBarong Shop';
            }

            this.form = {
                id: banner.id,
                title: banner.title || '',
                subtitle: cleanSubtitle,
                button_text_1: banner.button_text_1 || 'Shop now',
                button_url_1: banner.button_url_1 || '',
                button_text_2: banner.button_text_2 || 'Visit shop',
                button_url_2: banner.button_url_2 || '',
                order_index: banner.order_index || 1,
                is_active: Boolean(banner.is_active),
                start_date: formatDt(banner.start_date),
                end_date: formatDt(banner.end_date),
                preset_image_url: ''
            };

            // Match shop & product if linked
            this.selectedProductId = '';
            this.selectedShopId = '';

            if (banner.button_url_1 && banner.button_url_1.includes('/products/')) {
                var prodId = banner.button_url_1.replace('/products/', '').split('?')[0];
                var prod = this.allProducts.find(p => String(p.id) === String(prodId));
                if (prod) {
                    this.selectedProductId = prod.id;
                    this.selectedShopId = prod.seller_id;
                    this.mode = 'product';
                    this.form.subtitle = prod.shop_name || cleanSubtitle;
                } else {
                    this.mode = 'upload';
                }
            } else if (banner.button_url_2 && banner.button_url_2.includes('/shops/')) {
                var shopId = banner.button_url_2.replace('/shops/', '').split('?')[0];
                var shop = this.sellers.find(s => String(s.id) === String(shopId));
                if (shop) {
                    this.selectedShopId = shop.id;
                    this.form.subtitle = shop.shop_name || cleanSubtitle;
                }
                this.mode = 'upload';
            } else {
                this.mode = 'upload';
            }

            this.showModal = true;
        },

        handleFileSelect(event) {
            var file = event.target.files[0];
            if (!file) return;

            var self = this;
            self.fileInfo.name = file.name;

            var reader = new FileReader();
            reader.onload = function(e) {
                self.imagePreviewUrl = e.target.result;
                self.form.preset_image_url = '';
                var img = new Image();
                img.onload = function() {
                    self.fileInfo.dimensions = img.width + 'x' + img.height + 'px';
                };
                img.src = e.target.result;
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

            fetch('{{ route("admin.banners.reorder") }}', {
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