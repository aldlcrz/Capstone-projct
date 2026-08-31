@extends('layouts.seller')

@section('content')
@php
    $seller = auth()->user();
    $initPaymentState = [
        'hasGcashNumber' => !empty($product->gcash_number) || !empty($seller->gcashNumber),
        'hasGcashQr'     => !empty($product->gcash_qr_code) || !empty($seller->gcashQrCode),
        'gcashNumber'    => $product->gcash_number ?: ($seller->gcashNumber ?? ''),
        'gcashQrUrl'     => $product->gcash_qr_code ? (str_starts_with($product->gcash_qr_code, 'http') ? $product->gcash_qr_code : asset($product->gcash_qr_code)) : ($seller->gcashQrCode ? (str_starts_with($seller->gcashQrCode, 'http') ? $seller->gcashQrCode : asset($seller->gcashQrCode)) : null),
        'hasMayaNumber'  => !empty($product->maya_number) || !empty($seller->mayaNumber),
        'hasMayaQr'      => !empty($product->maya_qr_code) || !empty($seller->mayaQrCode),
        'mayaNumber'     => $product->maya_number ?: ($seller->mayaNumber ?? ''),
        'mayaQrUrl'      => $product->maya_qr_code ? (str_starts_with($product->maya_qr_code, 'http') ? $product->maya_qr_code : asset($product->maya_qr_code)) : ($seller->mayaQrCode ? (str_starts_with($seller->mayaQrCode, 'http') ? $seller->mayaQrCode : asset($seller->mayaQrCode)) : null),
    ];
@endphp
<style>
    /* Target Group Pills */
    .target-pill {
        height: 38px;
        padding: 0 20px;
        border-radius: 9999px;
        font-size: 13px;
        letter-spacing: 0.01em;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        transition: all 180ms ease;
        cursor: pointer;
        box-sizing: border-box;
        border: 1px solid #E2D9C8;
        background-color: #FCFAF6;
        color: #221F1C;
        font-weight: 600;
        user-select: none;
    }
    .target-pill:hover:not(.target-pill-selected) {
        background-color: #F5ECD8;
        border-color: #C8AC70;
    }
    .target-pill-selected {
        background-color: #1E1915 !important;
        color: #FCFAF6 !important;
        border-color: #C49520 !important;
        box-shadow: 0 4px 14px rgba(34,31,28,0.18), 0 1px 3px rgba(0,0,0,0.06) !important;
        font-weight: 700 !important;
    }
    .target-checkmark { color: #C49520; font-size: 13px; font-weight: 800; }

    /* Category Pills */
    .cat-pill {
        width: 100%;
        min-height: 42px;
        padding: 8px 12px;
        border-radius: 12px;
        font-size: 12.5px;
        font-weight: 600;
        letter-spacing: 0.01em;
        display: flex;
        align-items: center;
        justify-content: center;
        text-align: center;
        gap: 6px;
        position: relative;
        transition: all 180ms ease;
        cursor: pointer;
        box-sizing: border-box;
        border: 1px solid #E2D9C8;
        background-color: #FFFFFF;
        color: #221F1C;
        user-select: none;
    }
    .cat-pill:hover:not(.cat-pill-selected) {
        background-color: #F5ECD8;
        border-color: #C8AC70;
    }
    .cat-pill-selected {
        background-color: #1E1915 !important;
        color: #FCFAF6 !important;
        border-color: #C49520 !important;
        box-shadow: 0 3px 10px rgba(34,31,28,0.15) !important;
        font-weight: 700 !important;
    }
    .cat-checkmark {
        position: absolute;
        right: 10px;
        top: 50%;
        transform: translateY(-50%);
        color: #C49520;
        font-size: 12px;
        font-weight: 800;
    }
</style>
<div class="max-w-350 mx-auto pb-36 sm:pb-28 lg:pb-12 px-2.5 sm:px-6" x-data="editProductManager()">
    <div class="mb-3 sm:mb-10 pb-4 border-b" style="border-color: #E8DECB;">
        <div class="flex items-center gap-2 mb-1">
            <span class="text-[9px] font-extrabold uppercase tracking-[0.25em]" style="color: #C49520;">✦ Shop Catalogue</span>
            <span style="color: #E8DECB;">•</span>
            <span class="text-[10px] font-semibold tracking-wider uppercase" style="color: #766C60;">Edit Listing</span>
        </div>
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <h1 class="font-serif text-2xl sm:text-3xl font-bold tracking-tight" style="color: #1E1915;">Edit <span class="italic font-normal" style="color: #766C60;">Heritage Piece</span></h1>
            <a href="{{ route('seller.products.index') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold transition-all shadow-xs self-start sm:self-auto" style="background: #FDF8EE; border: 1px solid #E8DECB; color: #1E1915;" onmouseover="this.style.borderColor='#C49520';" onmouseout="this.style.borderColor='#E8DECB';">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Back to Catalogue
            </a>
        </div>
    </div>

    @if($errors->any())
    <div 
        x-data="{ show: true, init() { setTimeout(() => this.show = false, 7000) } }"
        x-show="show"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 translate-y-2 sm:translate-y-0 sm:translate-x-2"
        x-transition:enter-end="opacity-100 translate-y-0 sm:translate-x-0"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed top-6 right-6 z-9999 w-full max-w-sm bg-white/95 backdrop-blur-md rounded-2xl shadow-2xl border border-gray-100 p-4 flex items-start gap-3.5"
        style="display: none;"
        x-cloak
    >
        <div class="w-8 h-8 rounded-full bg-red-50 flex items-center justify-center text-red-600 shrink-0 shadow-sm border border-red-100">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path></svg>
        </div>
        <div class="grow pt-0.5">
            <h4 class="text-xs font-black text-black uppercase tracking-wider">Please fix the following</h4>
            <ul class="text-xs text-gray-500 font-medium mt-1 leading-relaxed space-y-0.5 list-disc list-inside">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        <button @click="show = false" class="text-gray-300 hover:text-gray-500 transition-colors shrink-0">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
        </button>
    </div>
    @endif

    <form action="{{ route('seller.products.update', $product->id) }}" method="POST" enctype="multipart/form-data" onsubmit="return validateProductForm(event, true)" class="grid grid-cols-1 lg:grid-cols-3 gap-4 sm:gap-6">
        @csrf
        @method('PUT')
        <input type="hidden" name="action" id="formActionInput" value="publish">

        {{-- Left Column: Core Product Data (2 cols) --}}
        <div class="lg:col-span-2 space-y-4">

            {{-- 1. Basic Product Information --}}
            <div class="space-y-4 sm:space-y-6 rounded-2xl border p-4 sm:p-6 shadow-xs" style="background: #FFFCF7; border-color: #E8DECB;">
                <div class="flex items-center gap-2.5 pb-2 border-b border-gray-100/80">
                    <div class="w-7 h-7 rounded-lg bg-[#C49520]/10 flex items-center justify-center text-[#C49520] shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                    <div>
                        <h3 class="text-xs sm:text-sm font-black text-gray-900 uppercase tracking-widest leading-none">Basic Information</h3>
                        <p class="text-[10px] text-gray-400 font-medium mt-0.5">Core title and artisan craftsmanship story</p>
                    </div>
                </div>

                <div class="space-y-3.5 sm:space-y-4">
                    {{-- Product Name --}}
                    <div class="space-y-1.5">
                        <div class="flex items-center justify-between">
                            <label class="text-[10px] sm:text-[11px] font-bold uppercase tracking-wider text-gray-700">
                                Product Name <span class="text-[#C49520]">*</span>
                            </label>
                            <span class="text-[9px] text-gray-400 font-medium hidden sm:inline-block">Concise & descriptive</span>
                        </div>
                        <input type="text" name="name" required value="{{ old('name', $product->name) }}"
                            placeholder="e.g. Hand-Woven Piña Barong Tagalog"
                            class="w-full px-3.5 py-2.5 sm:px-4 sm:py-3 bg-gray-50/70 border border-gray-200/90 rounded-xl outline-none focus:border-[#C49520] focus:bg-white focus:ring-2 focus:ring-[#C49520]/10 transition-all font-semibold text-sm text-gray-800 placeholder:text-gray-400 placeholder:font-normal">
                    </div>

                    {{-- Artisan Description --}}
                    <div class="space-y-1.5">
                        <div class="flex items-center justify-between">
                            <label class="text-[10px] sm:text-[11px] font-bold uppercase tracking-wider text-gray-700">
                                Artisan Description <span class="text-[#C49520]">*</span>
                            </label>
                            <span class="text-[9px] text-gray-400 font-medium hidden sm:inline-block">Max 500 characters</span>
                        </div>

                        <div class="relative group">
                            <textarea name="description" id="artisanDescription" required rows="4" maxlength="500"
                                oninput="updateCharCount(this)"
                                placeholder="Describe the craftsmanship, cultural heritage, weaving techniques, and unique story behind this piece..."
                                class="w-full px-3.5 py-2.5 sm:px-4 sm:py-3 bg-gray-50/70 border border-gray-200/90 rounded-xl outline-none focus:border-[#C49520] focus:bg-white focus:ring-2 focus:ring-[#C49520]/10 transition-all font-normal text-sm text-gray-800 placeholder:text-gray-400 resize-none pb-7 sm:pb-8">{{ old('description', $product->description) }}</textarea>
                            <div class="absolute bottom-2 right-2.5 sm:bottom-2.5 sm:right-3.5 flex items-center gap-1 bg-white/95 backdrop-blur-xs px-2 py-0.5 rounded-md border border-gray-100 text-[9px] sm:text-[10px] font-bold text-gray-400 pointer-events-none shadow-2xs">
                                <span id="charCounter">{{ strlen(old('description', $product->description ?? '')) }}</span><span class="text-gray-300">/</span><span>500</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- 2. Pricing & Shipping Stat Card --}}
            <div class="rounded-2xl border p-4 sm:p-6 shadow-xs" style="background: #FFFCF7; border-color: #E8DECB;" ">
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
                    {{-- Price --}}
                    <div class="p-3.5 bg-[#FDF8EE] border border-[#E8DECB] rounded-xl flex flex-col justify-between h-24 sm:h-26">
                        <label class="text-[9px] font-bold uppercase tracking-widest text-stone-500">Price (₱)</label>
                        <input type="number" name="price" required min="1" max="10000" step="0.01"
                            value="{{ old('price', $product->price) }}"
                            oninput="if(parseFloat(this.value) > 10000) this.value = 10000; updateDiscountPreview();"
                            class="w-full bg-transparent font-sans text-lg font-bold text-gray-900 outline-none border-b border-transparent focus:border-[#C49520] transition-all">
                        <p class="text-[8px] text-stone-400 font-medium">Item base price</p>
                    </div>

                    {{-- Total Stock --}}
                    <div class="p-3.5 bg-[#FDF8EE] border border-[#E8DECB] rounded-xl flex flex-col justify-between h-24 sm:h-26">
                        <label class="text-[9px] font-bold uppercase tracking-widest text-stone-500">Total Stock</label>
                        <input type="number" name="stock" id="total_stock" min="0"
                            value="{{ old('stock', $product->stock) }}"
                            readonly tabindex="-1"
                            class="w-full bg-transparent font-sans text-lg font-bold text-gray-900 outline-none select-none cursor-not-allowed">
                        <p class="text-[8px] text-stone-400 font-medium">Auto-calculated</p>
                    </div>

                    {{-- Shipping Fee --}}
                    <div class="p-3.5 bg-[#FDF8EE] border border-[#E8DECB] rounded-xl flex flex-col justify-between h-24 sm:h-26">
                        <label class="text-[9px] font-bold uppercase tracking-widest text-stone-500">Shipping Fee (₱)</label>
                        <input type="number" name="shippingFee" min="0" max="500" step="0.01" placeholder="0.00"
                            value="{{ old('shippingFee', $product->shippingFee ?? 0) }}"
                            oninput="if(parseFloat(this.value) > 500) this.value = 500;"
                            class="w-full bg-transparent font-sans text-lg font-bold text-gray-900 outline-none border-b border-transparent focus:border-[#C49520] transition-all">
                        <p class="text-[8px] text-stone-400 font-medium">Enter 0 for free</p>
                    </div>

                    {{-- Est. Shipping Days --}}
                    <div class="p-3.5 bg-[#FDF8EE] border border-[#E8DECB] rounded-xl flex flex-col justify-between h-24 sm:h-26">
                        <label class="text-[9px] font-bold uppercase tracking-widest text-stone-500">Est. Shipping Days</label>
                        <input type="number" name="shippingDays" min="1" max="30" step="1" placeholder="5"
                            value="{{ old('shippingDays', $product->shippingDays ?? 5) }}"
                            oninput="if(parseInt(this.value) > 30) this.value = 30;"
                            class="w-full bg-transparent font-sans text-lg font-bold text-gray-900 outline-none border-b border-transparent focus:border-[#C49520] transition-all">
                        <p class="text-[8px] text-stone-400 font-medium">To deliver</p>
                    </div>
                </div>
            </div>

            {{-- 3. Heritage Sizing & Inventory Card --}}
            <div id="sizing-section" class="rounded-2xl border p-4 sm:p-6 shadow-xs" style="background: #FFFCF7; border-color: #E8DECB;"  space-y-3">
                <div class="flex items-center justify-between">
                    <h3 class="text-xs sm:text-sm font-bold text-black uppercase tracking-widest">Heritage Sizing & Stock</h3>
                    <span class="text-[10px] text-gray-400 font-medium">Assign stock per size</span>
                </div>
                @php
                    $currentSizes = is_array($product->sizes) ? $product->sizes : (json_decode($product->sizes ?? '[]', true) ?? []);
                    $currentSizeStocks = is_array($product->size_stocks) ? $product->size_stocks : (json_decode($product->size_stocks ?? '[]', true) ?? []);
                @endphp
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-2.5">
                    @foreach(['S', 'M', 'L', 'XL', 'XXL', 'Custom'] as $size)
                        @php
                            $hasSize = in_array($size, $currentSizes);
                            $sizeStock = $currentSizeStocks[$size] ?? 0;
                        @endphp
                        <div class="p-2.5 border border-gray-100 bg-gray-50/50 rounded-xl flex flex-col justify-between gap-2">
                            <label class="flex items-center gap-1.5 cursor-pointer font-bold text-xs text-gray-700">
                                <input type="checkbox" name="sizes[]" value="{{ $size }}" 
                                    class="rounded text-[#C49520] focus:ring-[#C49520] w-3.5 h-3.5 size-checkbox"
                                    {{ $hasSize ? 'checked' : '' }}
                                    onchange="toggleSizeStock(this, '{{ $size }}')">
                                <span>Size {{ $size }}</span>
                            </label>
                            <input type="number" name="size_stocks[{{ $size }}]" id="stock_{{ $size }}" 
                                value="{{ old('size_stocks.'.$size, $sizeStock) }}" min="0" max="10000"
                                {{ $hasSize ? '' : 'disabled' }}
                                oninput="if(parseInt(this.value) > 10000) this.value = 10000; calculateTotalStock();"
                                class="w-full px-2 py-1.5 bg-white border border-gray-200 rounded-lg outline-none text-xs font-bold text-center size-stock-input">
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- 4. Category & Sale Configuration --}}
            <div class="rounded-2xl border p-4 sm:p-6 shadow-xs space-y-4" style="background: #FFFCF7; border-color: #E8DECB;">
                {{-- Target Group / Tag --}}
                <div id="target-group-container" class="space-y-2">
                    <div class="flex items-center justify-between">
                        <label class="text-[10px] font-bold uppercase tracking-widest text-gray-500">
                            Who is this for? (Tag) <span class="text-[#C49520]">*</span>
                        </label>
                        <span class="text-[11px] text-stone-500 font-semibold" x-text="'Tag: ' + targetGroup"></span>
                    </div>
                    <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                        @foreach(['Men', 'Women', 'Kids'] as $group)
                            <label class="cursor-pointer select-none" @click="onTargetGroupChange('{{ $group }}')">
                                <input type="radio" 
                                       name="target_group" 
                                       value="{{ $group }}" 
                                       x-model="targetGroup" 
                                       class="hidden target-group-radio">
                                <div class="target-pill" :class="targetGroup === '{{ $group }}' ? 'target-pill-selected' : ''">
                                    <span>{{ $group }}</span>
                                    <span class="target-checkmark" x-show="targetGroup === '{{ $group }}'">✓</span>
                                </div>
                            </label>
                        @endforeach
                    </div>
                </div>

                {{-- Product Category for Selected Tag --}}
                <div class="space-y-2.5 pt-3 border-t border-stone-200/70">
                    <div class="flex items-center justify-between flex-wrap gap-2">
                        <label class="text-[10px] font-bold uppercase tracking-widest text-gray-500">
                            Product Category for <span class="text-[#1E1915] font-black" x-text="targetGroup"></span> <span class="text-[#C49520]">*</span>
                        </label>
                        <div class="flex items-center gap-2">
                            <span class="text-[10px] font-bold rounded-full px-2.5 py-0.5 bg-[#FDF8EE] border border-[#EEDBBA] text-[#7A5505]"
                                  x-text="filteredCategories.length + ' Available'"></span>
                            <span class="text-[10px] font-bold rounded-full px-2.5 py-0.5 bg-emerald-50 border border-emerald-200 text-emerald-700"
                                  x-show="selectedCategories.length > 0"
                                  x-text="selectedCategories.length + ' Selected'"></span>
                        </div>
                    </div>

                    {{-- Hidden inputs for form submission --}}
                    <template x-for="catId in selectedCategories" :key="catId">
                        <input type="hidden" name="category_ids[]" :value="catId">
                    </template>
                    <input type="hidden" name="CategoryId" id="categorySelect" :value="selectedCategories[0] || ''">

                    {{-- Category Pills Grid --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2.5 pt-1" id="category-cards-container">
                        <template x-for="cat in filteredCategories" :key="cat.id">
                            <button type="button" 
                                    @click="toggleCategory(cat)"
                                    class="cat-pill"
                                    :class="selectedCategories.includes(String(cat.id)) ? 'cat-pill-selected' : ''">
                                <span x-text="cat.name" style="line-height:1.3;"></span>
                                <span class="cat-checkmark" x-show="selectedCategories.includes(String(cat.id))">✓</span>
                            </button>
                        </template>

                        <template x-if="filteredCategories.length === 0">
                            <div class="col-span-full py-6 text-center text-xs text-stone-500 font-medium bg-stone-50 rounded-xl border border-dashed border-stone-300">
                                No categories available for this tag.
                            </div>
                        </template>
                    </div>

                    {{-- Selected Categories Confirmation Badge --}}
                    <div x-show="selectedCategories.length > 0"
                         class="p-3 rounded-xl bg-[#FDF8EE] border border-[#EEDBBA] flex items-center justify-between gap-2 flex-wrap">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="text-[#C49520] font-black text-xs">✓</span>
                            <span class="text-[10px] text-[#7A5505] font-bold uppercase tracking-wider">Active Category:</span>
                            <template x-for="catId in selectedCategories" :key="catId">
                                <span class="text-xs bg-[#221F1C] text-[#FCFAF6] rounded-full px-3 py-1 font-semibold inline-flex items-center gap-1.5 shadow-2xs">
                                    <span x-text="categoriesList.find(c => String(c.id) === String(catId))?.name || catId"></span>
                                    <button type="button" @click.stop="toggleCategory({id: catId})" class="text-[#C49520] hover:text-white font-bold ml-1">×</button>
                                </span>
                            </template>
                        </div>
                        <span class="text-[10px] text-[#A07218] font-bold">Lumban Verified ✦</span>
                    </div>
                </div>

                {{-- Lumban Special Discount Panel --}}
                @php
                    $isOnSale = old('is_on_sale', $product->is_on_sale ?? false);
                    $discountPct = old('discount_percentage', $product->discount_percentage ?? '');
                @endphp
                <div class="p-4 rounded-xl border border-[#C49520]/15 bg-orange-50/20 space-y-3">
                    <input type="hidden" name="is_on_sale" id="isOnSaleInput" value="{{ $isOnSale ? '1' : '0' }}">

                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-[#C49520]"></span>
                            <span class="text-xs font-black text-[#C49520] uppercase tracking-widest">Lumban Special Sale</span>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer shrink-0">
                            <input type="checkbox" id="discountToggle" class="sr-only peer"
                                {{ $isOnSale ? 'checked' : '' }}
                                onchange="toggleDiscount(this)">
                            <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-0.5 after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-[#C49520]"></div>
                        </label>
                    </div>

                    <div id="discountFields" class="{{ $isOnSale ? '' : 'hidden' }} space-y-2.5 pt-2 border-t border-[#C49520]/10">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 items-end">
                            <div>
                                <label class="text-[9px] font-bold uppercase tracking-widest text-gray-400 mb-1 block">Discount (%)</label>
                                <input type="number" name="discount_percentage" id="discountPercentage"
                                    min="1" max="99" step="1" placeholder="e.g. 20"
                                    value="{{ $discountPct }}"
                                    class="w-full px-3.5 py-2.5 bg-white border border-[#C49520]/30 rounded-xl outline-none font-bold text-sm text-[#C49520]"
                                    oninput="if(parseInt(this.value) > 99) this.value = 99; updateDiscountPreview();">
                            </div>
                            <div>
                                <label class="text-[9px] font-bold uppercase tracking-widest text-gray-400 mb-1 block">Price Preview</label>
                                <div id="discountPreview" class="hidden w-full px-3.5 py-2.5 bg-white rounded-xl border border-[#C49520]/20 items-center justify-center gap-2 h-10.5">
                                    <span id="previewOriginal" class="text-xs text-gray-400 line-through font-bold"></span>
                                    <span id="previewSale" class="text-sm font-black text-[#C49520]"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        {{-- Right Column: Media & Submission Controls (1 col) --}}
        <div class="space-y-4">

            {{-- Product Media (Current & Add New) --}}
            <div class="rounded-2xl border p-4 sm:p-6 shadow-xs" style="background: #FFFCF7; border-color: #E8DECB;"  space-y-4">
                <div class="flex items-center justify-between">
                    <h3 class="text-xs sm:text-sm font-bold text-black uppercase tracking-widest">Product Images</h3>
                    <span id="img-count-badge" class="hidden text-[9px] font-black uppercase tracking-widest px-2.5 py-0.5 bg-[#C49520]/10 text-[#C49520] rounded-full">0 photos</span>
                </div>

                @php
                    $images = is_array($product->image) ? $product->image : (json_decode($product->image ?? '[]', true) ?? []);
                    if (is_string($product->image) && !str_starts_with($product->image, '[')) {
                        $images = [$product->image];
                    }
                @endphp
                @if(count($images) > 0)
                <div class="space-y-2">
                    <label class="text-[9px] font-bold uppercase tracking-widest text-gray-400">Current Photos</label>
                    <div class="grid grid-cols-3 gap-2">
                        @foreach($images as $i => $img)
                            {{-- Outer wrapper: always in DOM so checkbox submits correctly --}}
                            <div x-data="{ removed: false }">
                                {{-- Hidden checkbox: lives outside x-show so it's always submitted --}}
                                <input type="checkbox" name="remove_images[]" value="{{ $img }}" :checked="removed" class="hidden">

                                {{-- Visual card: disappears instantly on click --}}
                                <div class="relative group aspect-3/4 rounded-xl overflow-hidden border border-gray-200 shadow-xs transition-all"
                                     x-show="!removed"
                                     x-transition:leave="transition ease-in duration-200"
                                     x-transition:leave-start="opacity-100 scale-100"
                                     x-transition:leave-end="opacity-0 scale-75">
                                    <img src="{{ $product->getImageUrl($img) }}" onerror="this.src='/uploads/products/default.jpg'" class="w-full h-full object-cover">
                                    <button type="button"
                                            @click="removed = true"
                                            title="Remove photo"
                                            class="absolute top-1.5 right-1.5 w-6 h-6 rounded-full bg-black/60 hover:bg-red-600 text-white text-[10px] font-black flex items-center justify-center shadow-md transition-all z-10 cursor-pointer">
                                        ✕
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
                @endif

                {{-- Add New Images Dropzone --}}
                <div class="space-y-2">
                    <label class="text-[9px] font-bold uppercase tracking-widest text-gray-400">Upload New Photos</label>
                    <label for="imageUploadInput"
                        id="dropZone"
                        class="flex flex-col items-center justify-center gap-2 w-full min-h-28 rounded-xl border-2 border-dashed border-gray-200 bg-gray-50/50 hover:bg-white hover:border-[#C49520] transition-all cursor-pointer p-4 text-center relative overflow-hidden">
                        <svg class="w-6 h-6 text-gray-400 group-hover:text-[#C49520] transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        <div id="dropZoneTitle" class="text-xs font-bold text-gray-700 uppercase tracking-widest">Click to Add Photos</div>
                        <p id="dropZoneSubtitle" class="text-[9px] text-gray-400">PNG, JPG, WEBP &mdash; portrait shots</p>
                        <input type="file" id="imageUploadInput" name="images[]" multiple class="hidden" onchange="previewImages(this)">
                    </label>

                    <div id="image-preview-grid" class="hidden grid-cols-3 gap-2">
                        {{-- JS populated --}}
                    </div>
                </div>
            </div>

            {{-- Payment Method Configuration --}}
            <div id="payment-methods-card" class="rounded-2xl border p-4 sm:p-6 shadow-xs space-y-3 transition-all" style="background: #FFFCF7; border-color: #E8DECB;">
                <div class="flex items-center justify-between mb-1">
                    <div>
                        <h3 class="text-xs sm:text-sm font-black text-black uppercase tracking-widest">Payment Methods <span class="text-[#C49520]">*</span></h3>
                        <p class="text-[10px] text-stone-500 font-medium mt-0.5">Configure direct buyer payout methods</p>
                    </div>
                </div>

                {{-- GCash --}}
                <div class="rounded-2xl border overflow-hidden transition-all shadow-xs" style="background: #FFFFFF; border-color: #E2E8F0;">
                    <div class="flex items-center justify-between px-3.5 py-3" style="background: #2563EB;">
                        <div class="flex items-center gap-2">
                            <div class="w-7 h-7 rounded-xl bg-white/20 flex items-center justify-center font-black text-xs text-white">
                                G
                            </div>
                            <span class="text-xs font-black uppercase tracking-widest text-white">GCash</span>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="product_is_gcash_available" value="1" id="gcash_toggle_edit" class="sr-only peer"
                                x-model="isGcashOn"
                                {{ old('product_is_gcash_available', $product->is_gcash_available) ? 'checked' : '' }}>
                            <div class="w-9 h-5 bg-white/30 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-0.5 after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-white/50 border border-white/40"></div>
                        </label>
                    </div>

                    <div x-show="isGcashOn" class="p-3.5 space-y-2.5">
                        <template x-if="paymentState.isGcashComplete">
                            <div class="flex items-center gap-3 p-2.5 rounded-xl bg-blue-50/60 border border-blue-100">
                                <div class="w-12 h-12 rounded-lg border border-blue-200 overflow-hidden shrink-0 bg-white flex items-center justify-center cursor-zoom-in group relative"
                                     @click="openLightbox(paymentState.gcashQrUrl)">
                                    <img :src="paymentState.gcashQrUrl" class="w-full h-full object-contain" onerror="this.style.display='none'">
                                    <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center text-white">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7"/></svg>
                                    </div>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center justify-between gap-1">
                                        <span class="text-xs font-black text-gray-900 tracking-wide" x-text="paymentState.gcashNumber"></span>
                                        <button type="button" @click="openPaymentModal('gcash')" 
                                                class="px-2.5 py-1 rounded-lg text-[10px] font-black uppercase tracking-wider text-blue-700 bg-white border border-blue-200 hover:bg-blue-50 transition-all cursor-pointer shadow-2xs">
                                            GCash Setting
                                        </button>
                                    </div>
                                    <div class="text-[9px] text-blue-600 font-bold uppercase tracking-widest mt-0.5 flex items-center gap-1">
                                        <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                        Ready (Number & QR Set)
                                    </div>
                                </div>
                            </div>
                        </template>

                        <template x-if="!paymentState.isGcashComplete">
                            <div class="p-3 rounded-xl bg-amber-50 border border-amber-200 flex items-start justify-between gap-2">
                                <div>
                                    <div class="text-xs font-bold text-amber-900">Incomplete GCash Setup</div>
                                    <p class="text-[10px] text-amber-700 mt-0.5">Both mobile number and QR code are required.</p>
                                </div>
                                <button type="button" @click="openPaymentModal('gcash')" 
                                        class="px-2.5 py-1 rounded-lg text-[10px] font-black uppercase tracking-wider text-blue-700 bg-white border border-blue-200 hover:bg-blue-50 transition-all cursor-pointer shadow-2xs shrink-0">
                                    GCash Setting
                                </button>
                            </div>
                        </template>
                    </div>
                </div>

                {{-- Maya --}}
                <div class="rounded-2xl border overflow-hidden transition-all shadow-xs" style="background: #FFFFFF; border-color: #E2E8F0;">
                    <div class="flex items-center justify-between px-3.5 py-3" style="background: #059669;">
                        <div class="flex items-center gap-2">
                            <div class="w-7 h-7 rounded-xl bg-white/20 flex items-center justify-center font-black text-xs text-white">
                                M
                            </div>
                            <span class="text-xs font-black uppercase tracking-widest text-white">Maya</span>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="product_is_maya_available" value="1" id="maya_toggle_edit" class="sr-only peer"
                                x-model="isMayaOn"
                                {{ old('product_is_maya_available', $product->is_maya_available) ? 'checked' : '' }}>
                            <div class="w-9 h-5 bg-white/30 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-0.5 after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-white/50 border border-white/40"></div>
                        </label>
                    </div>

                    <div x-show="isMayaOn" class="p-3.5 space-y-2.5">
                        <template x-if="paymentState.isMayaComplete">
                            <div class="flex items-center gap-3 p-2.5 rounded-xl bg-emerald-50/60 border border-emerald-100">
                                <div class="w-12 h-12 rounded-lg border border-emerald-200 overflow-hidden shrink-0 bg-white flex items-center justify-center cursor-zoom-in group relative"
                                     @click="openLightbox(paymentState.mayaQrUrl)">
                                    <img :src="paymentState.mayaQrUrl" class="w-full h-full object-contain" onerror="this.style.display='none'">
                                    <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center text-white">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7"/></svg>
                                    </div>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center justify-between gap-1">
                                        <span class="text-xs font-black text-gray-900 tracking-wide" x-text="paymentState.mayaNumber"></span>
                                        <button type="button" @click="openPaymentModal('maya')" 
                                                class="px-2.5 py-1 rounded-lg text-[10px] font-black uppercase tracking-wider text-emerald-700 bg-white border border-emerald-200 hover:bg-emerald-50 transition-all cursor-pointer shadow-2xs">
                                            Maya Setting
                                        </button>
                                    </div>
                                    <div class="text-[9px] text-emerald-600 font-bold uppercase tracking-widest mt-0.5 flex items-center gap-1">
                                        <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                        Ready (Number & QR Set)
                                    </div>
                                </div>
                            </div>
                        </template>

                        <template x-if="!paymentState.isMayaComplete">
                            <div class="p-3 rounded-xl bg-amber-50 border border-amber-200 flex items-start justify-between gap-2">
                                <div>
                                    <div class="text-xs font-bold text-amber-900">Incomplete Maya Setup</div>
                                    <p class="text-[10px] text-amber-700 mt-0.5">Both account number and QR code are required.</p>
                                </div>
                                <button type="button" @click="openPaymentModal('maya')" 
                                        class="px-2.5 py-1 rounded-lg text-[10px] font-black uppercase tracking-wider text-emerald-700 bg-white border border-emerald-200 hover:bg-emerald-50 transition-all cursor-pointer shadow-2xs shrink-0">
                                    Maya Setting
                                </button>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            {{-- Actions & Status Card --}}
            <div class="rounded-2xl border p-4 sm:p-6 shadow-xs space-y-3.5" style="background: #FFFCF7; border-color: #E8DECB;">
                <div class="flex items-center justify-between pb-2 border-b" style="border-color: #E8DECB;">
                    <span class="text-[10px] font-extrabold uppercase tracking-widest text-stone-500">Listing Status</span>
                    @if($product->status === 'approved')
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider bg-green-100 text-green-800 border border-green-200">
                            ✓ Approved
                        </span>
                    @elseif($product->status === 'pending')
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider bg-amber-100 text-amber-800 border border-amber-200">
                            ⏳ Pending Review
                        </span>
                    @elseif($product->status === 'draft')
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider bg-stone-100 text-stone-700 border border-stone-300">
                            📝 Draft
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider bg-red-100 text-red-800 border border-red-200">
                            {{ ucfirst($product->status) }}
                        </span>
                    @endif
                </div>

                @if($product->status === 'draft')
                    {{-- For drafts: Publish vs Save as Draft --}}
                    <button type="submit"
                        onclick="document.getElementById('formActionInput').value = 'publish'"
                        class="w-full py-3.5 text-white rounded-xl font-black uppercase tracking-[0.15em] shadow-md transition-all text-xs flex items-center justify-center gap-2 cursor-pointer"
                        style="background: #1E1915;"
                        onmouseover="this.style.background='#C49520';"
                        onmouseout="this.style.background='#1E1915';">
                        <span>Publish Listing</span>
                        <span class="text-sm">→</span>
                    </button>

                    <button type="button"
                        @click="submitAsDraft()"
                        class="w-full py-3 rounded-xl font-extrabold text-xs uppercase tracking-wider transition-all flex items-center justify-center gap-2 cursor-pointer"
                        style="background: #FDF8EE; border: 1.5px solid #C49520; color: #7A5505;"
                        onmouseover="this.style.background='#FEF3C7';"
                        onmouseout="this.style.background='#FDF8EE';">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
                        <span>Save as Draft</span>
                    </button>

                    <button type="button"
                        @click="deleteModal = true; deleteProductId = '{{ $product->id }}'; deleteProductName = '{{ addslashes($product->name) }}'"
                        class="w-full py-2.5 rounded-xl font-bold text-[10px] uppercase tracking-widest transition-all cursor-pointer"
                        style="border: 1px solid #FECACA; color: #B91C1C; background: transparent;"
                        onmouseover="this.style.background='#FEF2F2';"
                        onmouseout="this.style.background='transparent';">
                        Discard / Delete Draft
                    </button>
                @else
                    {{-- For active/pending: Save Changes --}}
                    <button type="submit"
                        onclick="document.getElementById('formActionInput').value = 'publish'"
                        class="w-full py-3.5 text-white rounded-xl font-black uppercase tracking-[0.15em] shadow-md transition-all text-xs flex items-center justify-center gap-2 cursor-pointer"
                        style="background: #1E1915;"
                        onmouseover="this.style.background='#C49520';"
                        onmouseout="this.style.background='#1E1915';">
                        Save Changes
                    </button>

                    <button type="button"
                        @click="deleteModal = true; deleteProductId = '{{ $product->id }}'; deleteProductName = '{{ addslashes($product->name) }}'"
                        class="w-full py-2.5 rounded-xl font-bold text-[10px] uppercase tracking-widest transition-all cursor-pointer"
                        style="border: 1px solid #FECACA; color: #B91C1C; background: transparent;"
                        onmouseover="this.style.background='#FEF2F2';"
                        onmouseout="this.style.background='transparent';">
                        Archive / Delete Listing
                    </button>
                @endif
            </div>
        </div>

        {{-- Mobile Sticky Action Bar --}}
        <div class="lg:hidden fixed bottom-16 inset-x-0 backdrop-blur-md border-t px-3.5 py-2.5 z-30 shadow-2xl flex items-center justify-between gap-2.5" style="background: rgba(253,248,238,0.97); border-color: #E8DECB;">
            <div class="min-w-0">
                <div class="text-[10px] font-black uppercase tracking-wider truncate" style="color: #1E1915;" title="{{ $product->name }}">{{ $product->name }}</div>
                <div class="text-[9px] font-bold uppercase tracking-widest truncate" style="color: #C49520;">
                    {{ $product->status === 'draft' ? 'Editing Draft' : 'Editing Listing' }}
                </div>
            </div>
            <div class="flex items-center gap-2 shrink-0">
                <button type="button"
                    @click="deleteModal = true; deleteProductId = '{{ $product->id }}'; deleteProductName = '{{ addslashes($product->name) }}'"
                    class="p-2.5 rounded-xl text-xs font-bold transition-all cursor-pointer"
                    style="background: #FEF2F2; color: #B91C1C;"
                    onmouseover="this.style.background='#FECACA';"
                    onmouseout="this.style.background='#FEF2F2';"
                    title="Delete Listing">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                </button>
                @if($product->status === 'draft')
                    <button type="button" @click="submitAsDraft()" class="px-3 py-2 rounded-xl text-[11px] font-extrabold uppercase tracking-wider transition-all cursor-pointer" style="background: #FDF8EE; border: 1px solid #C49520; color: #7A5505;">
                        Save Draft
                    </button>
                    <button type="submit" onclick="document.getElementById('formActionInput').value = 'publish'" class="px-4 py-2 text-white rounded-xl text-[11px] font-black uppercase tracking-widest shadow-md transition-all cursor-pointer" style="background: #1E1915;" onmouseover="this.style.background='#C49520';" onmouseout="this.style.background='#1E1915';">
                        Publish
                    </button>
                @else
                    <button type="submit" onclick="document.getElementById('formActionInput').value = 'publish'" class="px-4 py-2 text-white rounded-xl text-[11px] font-black uppercase tracking-widest shadow-md transition-all cursor-pointer" style="background: #1E1915;" onmouseover="this.style.background='#C49520';" onmouseout="this.style.background='#1E1915';">
                        Save Changes
                    </button>
                @endif
            </div>
        </div>
    </form>

    {{-- ================================================================ --}}
    {{-- PAYMENT METHODS CONFIGURATION MODAL (IN-PAGE POPUP)               --}}
    {{-- ================================================================ --}}
    <div x-show="showPaymentModal" 
         x-cloak 
         style="display:none;" 
         class="fixed inset-0 z-50 bg-black/60 backdrop-blur-xs flex items-center justify-center p-3 sm:p-5"
         @keydown.escape.window="closePaymentModal()">
        
        {{-- Modal Card --}}
        <div @click.away="closePaymentModal()" 
             class="w-full max-w-lg rounded-3xl shadow-2xl overflow-hidden flex flex-col max-h-[90vh] transition-all" 
             style="background: #FFFCF7; border: 1px solid #E8DECB;">
            
            {{-- Header --}}
            <div class="px-6 pt-5 pb-4 border-b shrink-0" style="border-color: #E8DECB; background: #FAF7F0;">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <div class="inline-flex items-center gap-2 mb-1">
                            <template x-if="activePaymentTab === 'gcash'">
                                <span style="background-color:#2563EB;color:#FFFFFF;padding:4px 12px;border-radius:9999px;font-size:11px;font-weight:800;letter-spacing:0.04em;display:inline-flex;align-items:center;gap:6px;box-shadow:0 2px 6px rgba(37,99,235,0.25);">
                                    <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                                    <span>GCash Payout</span>
                                </span>
                            </template>
                            <template x-if="activePaymentTab === 'maya'">
                                <span style="background-color:#059669;color:#FFFFFF;padding:4px 12px;border-radius:9999px;font-size:11px;font-weight:800;letter-spacing:0.04em;display:inline-flex;align-items:center;gap:6px;box-shadow:0 2px 6px rgba(5,150,105,0.25);">
                                    <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                                    <span>Maya Payout</span>
                                </span>
                            </template>
                        </div>
                        <h2 style="font-family:ui-serif,Georgia,Cambria,serif;font-size:22px;font-weight:700;color:#1E1915;margin:6px 0 2px 0;">
                            <span x-text="activePaymentTab === 'gcash' ? 'GCash Setting' : 'Maya Setting'"></span>
                        </h2>
                        <p style="font-size:12.5px;color:#78716C;margin:0;">
                            <span x-text="activePaymentTab === 'gcash' ? 'Configure your GCash mobile number & payment QR code' : 'Configure your Maya account number & payment QR code'"></span>
                        </p>
                    </div>
                    <button type="button" @click="closePaymentModal()" class="w-8 h-8 rounded-xl flex items-center justify-center transition-all cursor-pointer hover:bg-black/5 shrink-0" style="background: #FDF8EE; color: #766C60; border: 1px solid #E8DECB;">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
            </div>

            {{-- Body --}}
            <div class="overflow-y-auto flex-1 p-6 space-y-5">
                {{-- Error banner if any --}}
                <div x-show="paymentModalError" x-cloak class="p-3.5 rounded-2xl bg-red-50 border border-red-200 text-xs text-red-700 flex items-start gap-2.5 shadow-xs">
                    <svg class="w-4 h-4 text-red-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span x-text="paymentModalError" class="font-semibold leading-relaxed"></span>
                </div>

                {{-- Success banner if any --}}
                <div x-show="paymentModalSuccess" x-cloak class="p-3.5 rounded-2xl bg-green-50 border border-green-200 text-xs text-green-700 flex items-start gap-2.5 shadow-xs">
                    <svg class="w-4 h-4 text-green-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    <span x-text="paymentModalSuccess" class="font-bold leading-relaxed"></span>
                </div>

                {{-- GCASH FORM ONLY --}}
                <div x-show="activePaymentTab === 'gcash'" class="space-y-4">
                    {{-- Status Banner --}}
                    <div class="flex items-center justify-between p-3.5 rounded-2xl bg-blue-50/80 border border-blue-200">
                        <div class="flex items-center gap-2.5">
                            <div class="w-8 h-8 rounded-xl bg-blue-600 text-white flex items-center justify-center font-black text-xs shadow-xs">
                                G
                            </div>
                            <div>
                                <h4 class="text-xs font-bold text-blue-950">GCash Direct Payment</h4>
                                <p class="text-[11px] text-blue-700">Receive instant customer payments</p>
                            </div>
                        </div>
                        <template x-if="modalGcashNumber && (modalGcashQrPreview || paymentState.hasGcashQr)">
                            <span class="inline-flex items-center gap-1 text-[10px] font-bold text-green-700 bg-green-100/90 px-2.5 py-1 rounded-full border border-green-300">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                Ready
                            </span>
                        </template>
                        <template x-if="!modalGcashNumber || (!modalGcashQrPreview && !paymentState.hasGcashQr)">
                            <span class="inline-flex items-center gap-1 text-[10px] font-bold text-amber-800 bg-amber-100/90 px-2.5 py-1 rounded-full border border-amber-300">
                                ⚠ Incomplete
                            </span>
                        </template>
                    </div>

                    {{-- GCash Mobile Number Input --}}
                    <div class="space-y-1.5">
                        <label class="text-[11px] font-bold text-stone-700 uppercase tracking-wider block">
                            GCash Mobile Number <span class="text-red-500">*</span>
                        </label>
                        <div class="relative flex items-center rounded-2xl border bg-white shadow-xs focus-within:ring-2 focus-within:ring-blue-500 focus-within:border-blue-500 transition-all overflow-hidden" style="border-color: #CBD5E1;">
                            <div class="px-3.5 py-3 border-r bg-[#F1F5F9] text-xs font-extrabold text-[#1E1915] select-none flex items-center gap-1.5" style="border-color: #CBD5E1;">
                                <span class="text-sm">🇵🇭</span>
                                <span>+63</span>
                            </div>
                            <input type="text" 
                                   x-model="modalGcashNumber" 
                                   placeholder="0917 123 4567" 
                                   class="w-full px-3.5 py-3 text-xs sm:text-sm font-bold text-[#1E1915] outline-none bg-transparent" 
                                   maxlength="15">
                        </div>
                        <p class="text-[11px] text-stone-500">Enter your 11-digit GCash mobile number (e.g. 09171234567).</p>
                    </div>

                    {{-- GCash QR Code Upload Section --}}
                    <div class="space-y-1.5">
                        <label class="text-[11px] font-bold text-stone-700 uppercase tracking-wider block">
                            GCash QR Code Image <span class="text-red-500">*</span>
                        </label>

                        <input type="file" 
                               id="modal_gcash_qr_input_edit" 
                               accept="image/*" 
                               @change="previewModalQr('gcash', $event)" 
                               class="hidden">

                        {{-- Existing / Selected QR Code View --}}
                        <template x-if="modalGcashQrPreview || paymentState.gcashQrUrl">
                            <div class="p-3.5 rounded-2xl border bg-white flex items-center justify-between gap-3 shadow-xs" style="border-color: #CBD5E1;">
                                <div class="flex items-center gap-3 min-w-0">
                                    <div class="relative w-16 h-16 rounded-xl border overflow-hidden shrink-0 bg-[#F8FAFC] flex items-center justify-center group cursor-zoom-in shadow-xs" 
                                         style="border-color: #E2E8F0;"
                                         @click="openLightbox(modalGcashQrPreview || paymentState.gcashQrUrl)">
                                        <img :src="modalGcashQrPreview || paymentState.gcashQrUrl" class="w-full h-full object-contain">
                                        <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center text-white">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7"/></svg>
                                        </div>
                                    </div>
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-1.5">
                                            <span class="text-xs font-bold text-stone-800 truncate">GCash QR Code</span>
                                            <span class="text-[9px] font-black text-green-600 bg-green-50 px-1.5 py-0.5 rounded-md border border-green-200">Uploaded</span>
                                        </div>
                                        <p class="text-[11px] text-stone-500 mt-0.5">Click thumbnail to inspect full size</p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-1.5 shrink-0">
                                    <button type="button" 
                                            @click="document.getElementById('modal_gcash_qr_input_edit').click()" 
                                            class="px-3.5 py-2 rounded-xl text-xs font-bold text-blue-700 bg-blue-50 border border-blue-200 hover:bg-blue-100 transition-all cursor-pointer">
                                        Replace
                                    </button>
                                </div>
                            </div>
                        </template>

                        {{-- Empty State Dropzone --}}
                        <template x-if="!modalGcashQrPreview && !paymentState.gcashQrUrl">
                            <div @click="document.getElementById('modal_gcash_qr_input_edit').click()" 
                                 class="p-6 rounded-2xl border-2 border-dashed flex flex-col items-center justify-center text-center cursor-pointer transition-all hover:bg-blue-50/50 hover:border-blue-400 bg-white" 
                                 style="border-color: #CBD5E1;">
                                <div class="w-11 h-11 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center mb-2.5 shadow-xs">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                </div>
                                <p class="text-xs font-bold text-stone-800">Upload GCash QR Code Image</p>
                                <p class="text-[11px] text-stone-500 mt-0.5">PNG, JPG, or WEBP up to 5MB</p>
                                <span class="mt-3 px-3.5 py-1.5 rounded-xl bg-[#1E1915] text-white text-[11px] font-bold shadow-xs hover:bg-blue-600 transition-all">
                                    Choose Image File
                                </span>
                            </div>
                        </template>
                    </div>

                    {{-- Informational Notice --}}
                    <div class="p-3.5 rounded-2xl bg-[#FFFBEB] border border-[#FDE68A] flex items-start gap-2.5">
                        <svg class="w-4 h-4 text-amber-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <p class="text-[11px] text-amber-900 leading-relaxed font-medium">
                            Both your <strong>GCash Mobile Number</strong> and <strong>QR Code Image</strong> are required so buyers can easily scan and complete transactions.
                        </p>
                    </div>
                </div>

                {{-- MAYA FORM ONLY --}}
                <div x-show="activePaymentTab === 'maya'" class="space-y-4">
                    {{-- Status Banner --}}
                    <div class="flex items-center justify-between p-3.5 rounded-2xl bg-emerald-50/80 border border-emerald-200">
                        <div class="flex items-center gap-2.5">
                            <div class="w-8 h-8 rounded-xl bg-emerald-600 text-white flex items-center justify-center font-black text-xs shadow-xs">
                                M
                            </div>
                            <div>
                                <h4 class="text-xs font-bold text-emerald-950">Maya Direct Payment</h4>
                                <p class="text-[11px] text-emerald-700">Receive instant customer payments</p>
                            </div>
                        </div>
                        <template x-if="modalMayaNumber && (modalMayaQrPreview || paymentState.hasMayaQr)">
                            <span class="inline-flex items-center gap-1 text-[10px] font-bold text-green-700 bg-green-100/90 px-2.5 py-1 rounded-full border border-green-300">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                Ready
                            </span>
                        </template>
                        <template x-if="!modalMayaNumber || (!modalMayaQrPreview && !paymentState.hasMayaQr)">
                            <span class="inline-flex items-center gap-1 text-[10px] font-bold text-amber-800 bg-amber-100/90 px-2.5 py-1 rounded-full border border-amber-300">
                                ⚠ Incomplete
                            </span>
                        </template>
                    </div>

                    {{-- Maya Account Number Input --}}
                    <div class="space-y-1.5">
                        <label class="text-[11px] font-bold text-stone-700 uppercase tracking-wider block">
                            Maya Account Number <span class="text-red-500">*</span>
                        </label>
                        <div class="relative flex items-center rounded-2xl border bg-white shadow-xs focus-within:ring-2 focus-within:ring-emerald-500 focus-within:border-emerald-500 transition-all overflow-hidden" style="border-color: #CBD5E1;">
                            <div class="px-3.5 py-3 border-r bg-[#F1F5F9] text-xs font-extrabold text-[#1E1915] select-none flex items-center gap-1.5" style="border-color: #CBD5E1;">
                                <span class="text-sm">🇵🇭</span>
                                <span>+63</span>
                            </div>
                            <input type="text" 
                                   x-model="modalMayaNumber" 
                                   placeholder="0918 123 4567" 
                                   class="w-full px-3.5 py-3 text-xs sm:text-sm font-bold text-[#1E1915] outline-none bg-transparent" 
                                   maxlength="15">
                        </div>
                        <p class="text-[11px] text-stone-500">Enter your Maya mobile or account number.</p>
                    </div>

                    {{-- Maya QR Code Upload Section --}}
                    <div class="space-y-1.5">
                        <label class="text-[11px] font-bold text-stone-700 uppercase tracking-wider block">
                            Maya QR Code Image <span class="text-red-500">*</span>
                        </label>

                        <input type="file" 
                               id="modal_maya_qr_input_edit" 
                               accept="image/*" 
                               @change="previewModalQr('maya', $event)" 
                               class="hidden">

                        {{-- Existing / Selected QR Code View --}}
                        <template x-if="modalMayaQrPreview || paymentState.mayaQrUrl">
                            <div class="p-3.5 rounded-2xl border bg-white flex items-center justify-between gap-3 shadow-xs" style="border-color: #CBD5E1;">
                                <div class="flex items-center gap-3 min-w-0">
                                    <div class="relative w-16 h-16 rounded-xl border overflow-hidden shrink-0 bg-[#F8FAFC] flex items-center justify-center group cursor-zoom-in shadow-xs" 
                                         style="border-color: #E2E8F0;"
                                         @click="openLightbox(modalMayaQrPreview || paymentState.mayaQrUrl)">
                                        <img :src="modalMayaQrPreview || paymentState.mayaQrUrl" class="w-full h-full object-contain">
                                        <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center text-white">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7"/></svg>
                                        </div>
                                    </div>
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-1.5">
                                            <span class="text-xs font-bold text-stone-800 truncate">Maya QR Code</span>
                                            <span class="text-[9px] font-black text-green-600 bg-green-50 px-1.5 py-0.5 rounded-md border border-green-200">Uploaded</span>
                                        </div>
                                        <p class="text-[11px] text-stone-500 mt-0.5">Click thumbnail to inspect full size</p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-1.5 shrink-0">
                                    <button type="button" 
                                            @click="document.getElementById('modal_maya_qr_input_edit').click()" 
                                            class="px-3.5 py-2 rounded-xl text-xs font-bold text-emerald-700 bg-emerald-50 border border-emerald-200 hover:bg-emerald-100 transition-all cursor-pointer">
                                        Replace
                                    </button>
                                </div>
                            </div>
                        </template>

                        {{-- Empty State Dropzone --}}
                        <template x-if="!modalMayaQrPreview && !paymentState.mayaQrUrl">
                            <div @click="document.getElementById('modal_maya_qr_input_edit').click()" 
                                 class="p-6 rounded-2xl border-2 border-dashed flex flex-col items-center justify-center text-center cursor-pointer transition-all hover:bg-emerald-50/50 hover:border-emerald-400 bg-white" 
                                 style="border-color: #CBD5E1;">
                                <div class="w-11 h-11 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center mb-2.5 shadow-xs">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                </div>
                                <p class="text-xs font-bold text-stone-800">Upload Maya QR Code Image</p>
                                <p class="text-[11px] text-stone-500 mt-0.5">PNG, JPG, or WEBP up to 5MB</p>
                                <span class="mt-3 px-3.5 py-1.5 rounded-xl bg-[#1E1915] text-white text-[11px] font-bold shadow-xs hover:bg-emerald-600 transition-all">
                                    Choose Image File
                                </span>
                            </div>
                        </template>
                    </div>

                    {{-- Informational Notice --}}
                    <div class="p-3.5 rounded-2xl bg-[#FFFBEB] border border-[#FDE68A] flex items-start gap-2.5">
                        <svg class="w-4 h-4 text-amber-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <p class="text-[11px] text-amber-900 leading-relaxed font-medium">
                            Both your <strong>Maya Account Number</strong> and <strong>QR Code Image</strong> are required so buyers can easily scan and complete transactions.
                        </p>
                    </div>
                </div>
            </div>

            {{-- Footer Actions --}}
            <div class="px-6 py-4 border-t flex items-center justify-end gap-3 shrink-0" style="border-color: #E8DECB; background: #FAF7F0;">
                <button type="button" 
                        @click="closePaymentModal()" 
                        style="padding:10px 20px;border-radius:12px;border:1px solid #D6D3D1;background-color:#FFFFFF;color:#44403C;font-size:13px;font-weight:700;cursor:pointer;transition:all 0.15s;"
                        onmouseover="this.style.backgroundColor='#F5F5F4';"
                        onmouseout="this.style.backgroundColor='#FFFFFF';">
                    Cancel
                </button>
                <button type="button" 
                        @click="savePaymentSettings()" 
                        :disabled="isSavingPayment" 
                        style="padding:10px 24px;border-radius:12px;border:none;background-color:#1E1915;color:#FFFFFF;font-size:13px;font-weight:700;cursor:pointer;display:inline-flex;align-items:center;gap:8px;box-shadow:0 2px 8px rgba(0,0,0,0.18);transition:all 0.15s;"
                        onmouseover="this.style.backgroundColor='#C49520';"
                        onmouseout="this.style.backgroundColor='#1E1915';"
                        class="disabled:opacity-50">
                    <span x-show="!isSavingPayment">Save Payment Configuration</span>
                    <span x-show="isSavingPayment" class="inline-flex items-center gap-2" x-cloak>
                        <svg class="animate-spin h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                        </svg>
                        <span>Saving...</span>
                    </span>
                </button>
            </div>
        </div>
    </div>

    {{-- QR Lightbox Modal --}}
    <div x-show="showQrLightbox" 
         x-cloak 
         style="display:none;" 
         class="fixed inset-0 z-60 bg-black/80 backdrop-blur-md flex items-center justify-center p-4" 
         @click="closeLightbox()" 
         @keydown.escape.window="closeLightbox()">
        <div class="relative max-w-sm w-full bg-white rounded-3xl p-6 shadow-2xl flex flex-col items-center gap-4" @click.stop>
            <button type="button" @click="closeLightbox()" class="absolute top-4 right-4 w-8 h-8 rounded-full bg-stone-100 hover:bg-stone-200 text-stone-600 flex items-center justify-center font-bold text-sm cursor-pointer">✕</button>
            <h3 class="text-sm font-extrabold text-stone-900 uppercase tracking-widest">Payment QR Code</h3>
            <div class="w-64 h-64 rounded-2xl border border-stone-200 overflow-hidden bg-stone-50 flex items-center justify-center">
                <img :src="lightboxImgUrl" class="w-full h-full object-contain">
            </div>
            <p class="text-[11px] text-stone-500 text-center">Scan to pay with banking or digital wallet app</p>
        </div>
    </div>

    {{-- Delete Confirmation Modal --}}
    <div x-show="deleteModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm" x-cloak>
        <div @click.away="deleteModal = false" class="bg-white rounded-3xl shadow-2xl w-full max-w-md p-8 space-y-6">
            <div class="text-center">
                <div class="w-16 h-16 rounded-full bg-red-50 flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                </div>
                <h3 class="font-serif text-xl font-bold text-black mb-1">Delete This Listing?</h3>
                <p class="text-xs text-gray-500">&quot;<span x-text="deleteProductName"></span>&quot; will be permanently removed.</p>
            </div>
            <form :action="'/seller/products/' + deleteProductId" method="POST" class="flex gap-3">
                @csrf
                @method('DELETE')
                <button type="button" @click="deleteModal = false"
                    class="flex-1 py-3 rounded-xl border border-gray-200 text-[10px] font-bold uppercase tracking-widest text-gray-500 hover:bg-gray-50 transition-all cursor-pointer">
                    Keep Listing
                </button>
                <button type="submit"
                    class="flex-1 py-3 rounded-xl bg-red-500 text-white text-[10px] font-bold uppercase tracking-widest hover:bg-red-600 transition-all cursor-pointer">
                    Yes, Delete
                </button>
            </form>
        </div>
    </div>
</div>

@php
    $categoriesJson = $categories->map(function($c) {
        $tags = $c->target_group;
        if (is_string($tags)) {
            $decoded = json_decode($tags, true);
            $tags = is_array($decoded) ? $decoded : [$tags];
        }
        if (!is_array($tags)) {
            $tags = [];
        }
        $tags = array_values(array_filter(array_map('trim', $tags)));
        return [
            'id' => (string) $c->id,
            'name' => (string) $c->name,
            'target_group' => $tags,
            'image' => method_exists($c, 'getImageUrl') ? $c->getImageUrl() : '',
        ];
    })->values();

    $initialCategoryIds = [];
    if (!empty($product->category_ids) && is_array($product->category_ids)) {
        $initialCategoryIds = array_map('strval', $product->category_ids);
    } elseif (!empty($product->CategoryId)) {
        $initialCategoryIds = [(string) $product->CategoryId];
    }

    $editInitData = [
        'productId' => (string) $product->id,
        'csrfToken' => csrf_token(),
        'profileUpdateUrl' => route('seller.profile.update'),
        'targetGroup' => (string) old('target_group', $product->target_group ?: 'Men'),
        'categoryId' => (string) old('CategoryId', $product->CategoryId ?: ''),
        'categoryIds' => $initialCategoryIds,
        'hasGcashNumber' => !empty($product->gcash_number) || !empty($seller->gcashNumber),
        'hasGcashQr' => !empty($product->gcash_qr_code) || !empty($seller->gcashQrCode),
        'gcashNumber' => (string) ($product->gcash_number ?: ($seller->gcashNumber ?? '')),
        'gcashQrUrl' => $product->gcash_qr_code ? asset($product->gcash_qr_code) : ($seller->gcashQrCode ? asset($seller->gcashQrCode) : null),
        'isGcashOn' => (bool) old('product_is_gcash_available', $product->is_gcash_available),
        
        'hasMayaNumber' => !empty($product->maya_number) || !empty($seller->mayaNumber),
        'hasMayaQr' => !empty($product->maya_qr_code) || !empty($seller->mayaQrCode),
        'mayaNumber' => (string) ($product->maya_number ?: ($seller->mayaNumber ?? '')),
        'mayaQrUrl' => $product->maya_qr_code ? asset($product->maya_qr_code) : ($seller->mayaQrCode ? asset($seller->mayaQrCode) : null),
        'isMayaOn' => (bool) old('product_is_maya_available', $product->is_maya_available),
    ];
@endphp

<script id="product-edit-init-data" type="application/json">
{!! json_encode($editInitData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) !!}
</script>

<script id="categories-data-json" type="application/json">
{!! json_encode($categoriesJson, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) !!}
</script>

<script id="seller-payment-config" type="application/json">
{!! json_encode([
    'hasGcashNumber' => !empty($product->gcash_number) || !empty($seller->gcashNumber),
    'hasGcashQr' => !empty($product->gcash_qr_code) || !empty($seller->gcashQrCode),
    'hasMayaNumber' => !empty($product->maya_number) || !empty($seller->mayaNumber),
    'hasMayaQr' => !empty($product->maya_qr_code) || !empty($seller->mayaQrCode),
], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) !!}
</script>

<script>
function getEditInitData() {
    try {
        const el = document.getElementById('product-edit-init-data');
        if (el && el.textContent) {
            return JSON.parse(el.textContent);
        }
    } catch (e) {}
    return {};
}

let editProductImagesDT = new DataTransfer();

function triggerAppModal(title, message, type = 'warning') {
    window.dispatchEvent(new CustomEvent('open-confirmation', {
        detail: {
            title: title,
            message: message,
            type: type,
            confirmText: 'Got It',
            cancelText: 'Dismiss',
            onConfirm: null
        }
    }));
}

function editProductManager() {
    let parsedCats = [];
    try {
        const jsonEl = document.getElementById('categories-data-json');
        if (jsonEl && jsonEl.textContent) {
            parsedCats = JSON.parse(jsonEl.textContent);
        }
    } catch (e) {
        parsedCats = [];
    }

    const initData = getEditInitData();

    // Ensure all 12 reference categories exist in parsedCats
    const referenceMenCategories = [
        'Accessories', 'Camisa de Chino', 'Casual', 'Formal Barong',
        'Heritage Accessories', 'Jusi Classic Barong', 'Lumban Specials',
        'Modern', 'Piña Formal Barong', 'Semi-Formal', 'Special Occasion', 'Traditional'
    ];
    referenceMenCategories.forEach((catName) => {
        const item = parsedCats.find(c => c.name && c.name.toLowerCase() === catName.toLowerCase());
        if (item && Array.isArray(item.target_group) && !item.target_group.includes('Men')) {
            item.target_group.push('Men');
        }
    });

    const initialCategoryIds = Array.isArray(initData.categoryIds) && initData.categoryIds.length > 0 
        ? initData.categoryIds.map(String) 
        : (initData.categoryId ? [String(initData.categoryId)] : []);

    return {
        deleteModal: false,
        deleteProductId: null,
        deleteProductName: '',

        targetGroup: initData.targetGroup || 'Men',
        selectedCategories: initialCategoryIds,
        categoriesList: parsedCats,

        get filteredCategories() {
            if (!Array.isArray(this.categoriesList)) return [];
            if (!this.targetGroup) return [];
            return this.categoriesList.filter(c => {
                if (!c) return false;
                let tg = c.target_group;
                if (Array.isArray(tg)) return tg.includes(this.targetGroup);
                if (typeof tg === 'string') return tg === this.targetGroup;
                return false;
            }).sort((a, b) => (a.name || '').localeCompare(b.name || ''));
        },

        toggleCategory(cat) {
            if (!cat) return;
            const strId = String(cat.id);
            const idx = this.selectedCategories.indexOf(strId);
            if (idx === -1) {
                this.selectedCategories = [strId]; // Single category selection mode for clean mapping
            } else {
                this.selectedCategories.splice(idx, 1);
            }
            const catContainer = document.getElementById('category-cards-container');
            if (catContainer) catContainer.classList.remove('border-red-500');
        },

        onTargetGroupChange(group) {
            this.targetGroup = group;
            const tgContainer = document.getElementById('target-group-container');
            if (tgContainer) tgContainer.classList.remove('border-red-500', 'p-1', 'border', 'rounded-xl');

            // If selected category does not belong to new target group, remove it
            if (this.selectedCategories.length > 0) {
                this.selectedCategories = this.selectedCategories.filter(catId => {
                    const cat = Array.isArray(this.categoriesList) ? this.categoriesList.find(c => String(c.id) === String(catId)) : null;
                    if (!cat) return false;
                    let tg = cat.target_group;
                    return Array.isArray(tg) ? tg.includes(group) : (tg === group);
                });
            }
        },

        showPaymentModal: false,
        activePaymentTab: 'gcash',
        isSavingPayment: false,
        paymentModalError: '',
        paymentModalSuccess: '',
        modalGcashNumber: initData.gcashNumber || '',
        modalGcashQrFile: null,
        modalGcashQrPreview: null,
        modalMayaNumber: initData.mayaNumber || '',
        modalMayaQrFile: null,
        modalMayaQrPreview: null,
        showQrLightbox: false,
        lightboxImgUrl: '',

        isGcashOn: Boolean(initData.isGcashOn),
        isMayaOn: Boolean(initData.isMayaOn),

        paymentState: {
            hasGcashNumber: Boolean(initData.hasGcashNumber),
            hasGcashQr: Boolean(initData.hasGcashQr),
            gcashNumber: initData.gcashNumber || '',
            gcashQrUrl: initData.gcashQrUrl || null,
            get isGcashComplete() { return Boolean(this.hasGcashNumber && this.hasGcashQr); },

            hasMayaNumber: Boolean(initData.hasMayaNumber),
            hasMayaQr: Boolean(initData.hasMayaQr),
            mayaNumber: initData.mayaNumber || '',
            mayaQrUrl: initData.mayaQrUrl || null,
            get isMayaComplete() { return Boolean(this.hasMayaNumber && this.hasMayaQr); }
        },

        openPaymentModal(tab = 'gcash') {
            this.activePaymentTab = (tab === 'maya') ? 'maya' : 'gcash';
            this.paymentModalError = '';
            this.paymentModalSuccess = '';
            this.modalGcashNumber = this.paymentState.gcashNumber || '';
            this.modalMayaNumber = this.paymentState.mayaNumber || '';
            this.modalGcashQrFile = null;
            this.modalMayaQrFile = null;
            this.modalGcashQrPreview = null;
            this.modalMayaQrPreview = null;
            const gInput = document.getElementById('modal_gcash_qr_input_edit');
            if (gInput) gInput.value = '';
            const mInput = document.getElementById('modal_maya_qr_input_edit');
            if (mInput) mInput.value = '';
            this.showPaymentModal = true;
        },

        closePaymentModal() {
            this.showPaymentModal = false;
            this.paymentModalError = '';
            this.paymentModalSuccess = '';
        },

        previewModalQr(type, event) {
            const file = event.target.files && event.target.files[0];
            if (!file) return;
            if (file.size > 5 * 1024 * 1024) {
                this.paymentModalError = 'QR Code image must not exceed 5MB.';
                event.target.value = '';
                return;
            }
            const reader = new FileReader();
            if (type === 'gcash') {
                this.modalGcashQrFile = file;
                reader.onload = (e) => { this.modalGcashQrPreview = e.target.result; };
                reader.readAsDataURL(file);
            } else {
                this.modalMayaQrFile = file;
                reader.onload = (e) => { this.modalMayaQrPreview = e.target.result; };
                reader.readAsDataURL(file);
            }
        },

        openLightbox(url) {
            if (!url) return;
            this.lightboxImgUrl = url;
            this.showQrLightbox = true;
        },

        closeLightbox() {
            this.showQrLightbox = false;
            this.lightboxImgUrl = '';
        },

        async savePaymentSettings() {
            this.paymentModalError = '';
            this.paymentModalSuccess = '';

            const isGcash = this.activePaymentTab === 'gcash';
            const isMaya = this.activePaymentTab === 'maya';

            const gcashNum = (this.modalGcashNumber || '').trim();
            const mayaNum = (this.modalMayaNumber || '').trim();
            const gcashFile = this.modalGcashQrFile;
            const mayaFile = this.modalMayaQrFile;
            const hasExistingGcashQr = Boolean(this.paymentState.hasGcashQr);
            const hasExistingMayaQr = Boolean(this.paymentState.hasMayaQr);

            const errors = [];
            if (isGcash) {
                const hasQr = Boolean(gcashFile || hasExistingGcashQr);
                if (!gcashNum || !hasQr) {
                    if (!gcashNum && !hasQr) errors.push('Both GCash mobile number and QR code image are required.');
                    else if (!gcashNum) errors.push('Please enter a GCash mobile number.');
                    else if (!hasQr) errors.push('Please upload a GCash QR code image.');
                }
            } else if (isMaya) {
                const hasQr = Boolean(mayaFile || hasExistingMayaQr);
                if (!mayaNum || !hasQr) {
                    if (!mayaNum && !hasQr) errors.push('Both Maya account number and QR code image are required.');
                    else if (!mayaNum) errors.push('Please enter a Maya account number.');
                    else if (!hasQr) errors.push('Please upload a Maya QR code image.');
                }
            }

            if (errors.length > 0) {
                this.paymentModalError = errors.join(' ');
                return;
            }

            this.isSavingPayment = true;

            try {
                const formData = new FormData();
                formData.append('_token', initData.csrfToken || document.querySelector('input[name="_token"]')?.value || '');
                formData.append('_method', 'PUT');

                if (isGcash) {
                    formData.append('gcashNumber', gcashNum);
                    if (gcashFile) formData.append('gcashQrCode', gcashFile);
                } else if (isMaya) {
                    formData.append('mayaNumber', mayaNum);
                    if (mayaFile) formData.append('mayaQrCode', mayaFile);
                }

                const url = initData.profileUpdateUrl || '/seller/profile';
                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    },
                    body: formData
                });

                const data = await response.json();

                if (response.ok && data.success) {
                    const providerName = isGcash ? 'GCash' : 'Maya';
                    this.paymentModalSuccess = `${providerName} settings saved successfully!`;

                    if (data.user) {
                        if (data.user.gcashNumber !== undefined) {
                            this.paymentState.gcashNumber = data.user.gcashNumber || '';
                            this.paymentState.gcashQrUrl = data.user.gcashQrUrl || null;
                            this.paymentState.hasGcashNumber = Boolean(data.user.gcashNumber);
                            this.paymentState.hasGcashQr = Boolean(data.user.gcashQrCode);
                        }
                        if (data.user.mayaNumber !== undefined) {
                            this.paymentState.mayaNumber = data.user.mayaNumber || '';
                            this.paymentState.mayaQrUrl = data.user.mayaQrUrl || null;
                            this.paymentState.hasMayaNumber = Boolean(data.user.mayaNumber);
                            this.paymentState.hasMayaQr = Boolean(data.user.mayaQrCode);
                        }
                    }

                    window._currentPaymentState = {
                        hasGcashNumber: this.paymentState.hasGcashNumber,
                        hasGcashQr: this.paymentState.hasGcashQr,
                        hasMayaNumber: this.paymentState.hasMayaNumber,
                        hasMayaQr: this.paymentState.hasMayaQr
                    };

                    const cfgEl = document.getElementById('seller-payment-config');
                    if (cfgEl) {
                        cfgEl.textContent = JSON.stringify(window._currentPaymentState);
                    }

                    const paymentCard = document.getElementById('payment-methods-card');
                    if (paymentCard) paymentCard.classList.remove('border-red-500');

                    setTimeout(() => {
                        this.showPaymentModal = false;
                        this.paymentModalSuccess = '';
                    }, 800);
                } else {
                    this.paymentModalError = data.message || 'Failed to save payment settings. Please try again.';
                }
            } catch (err) {
                console.error(err);
                this.paymentModalError = 'An error occurred while saving. Please check your connection and try again.';
            } finally {
                this.isSavingPayment = false;
            }
        },

        submitAsDraft() {
            const form = document.querySelector('form[action*="products/"]');
            const actionInput = document.getElementById('formActionInput');
            if (actionInput) actionInput.value = 'draft';
            const nameInput = document.querySelector('input[name="name"]');
            if (!nameInput || !nameInput.value.trim()) {
                alert('Please provide a Product Name to save your draft.');
                if (nameInput) {
                    nameInput.classList.add('border-red-500');
                    nameInput.focus();
                }
                return;
            }
            if (form) form.submit();
        }
    };
}

function previewImages(input) {
    if (input.files && input.files.length > 0) {
        let duplicateCount = 0;
        let oversizedCount = 0;

        Array.from(input.files).forEach(file => {
            if (file.size > 5 * 1024 * 1024) {
                oversizedCount++;
                return;
            }
            const exists = Array.from(editProductImagesDT.files).some(f => f.name === file.name && f.size === file.size);
            if (exists) {
                duplicateCount++;
            } else {
                editProductImagesDT.items.add(file);
            }
        });

        if (oversizedCount > 0) {
            triggerAppModal('Image Exceeds 5MB', `${oversizedCount} photo(s) exceeded the 5MB size limit and were skipped.`, 'warning');
        } else if (duplicateCount > 0) {
            triggerAppModal('Duplicate Image Skipped', `${duplicateCount} duplicate image(s) already added and were skipped.`, 'warning');
        }

        input.files = editProductImagesDT.files;
    }
    renderEditImagePreviews();
}

function removeNewEditImageAt(index) {
    const input = document.getElementById('imageUploadInput');
    const newDT = new DataTransfer();
    Array.from(editProductImagesDT.files).forEach((file, i) => {
        if (i !== index) newDT.items.add(file);
    });
    editProductImagesDT = newDT;
    if (input) input.files = editProductImagesDT.files;
    renderEditImagePreviews();
}

function renderEditImagePreviews() {
    const grid = document.getElementById('image-preview-grid');
    const badge = document.getElementById('img-count-badge');
    const titleEl = document.getElementById('dropZoneTitle');
    const subEl = document.getElementById('dropZoneSubtitle');

    if (!grid) return;
    grid.innerHTML = '';

    const files = editProductImagesDT.files;
    if (files && files.length > 0) {
        grid.classList.remove('hidden');
        grid.classList.add('grid');
        if (badge) {
            badge.classList.remove('hidden');
            badge.textContent = files.length + ' new photo' + (files.length !== 1 ? 's' : '');
        }
        if (titleEl) titleEl.textContent = '+ Add More Photos';
        if (subEl) subEl.textContent = `${files.length} new photo(s) selected — click or drop to add more`;

        Array.from(files).forEach((file, idx) => {
            const reader = new FileReader();
            reader.onload = e => {
                const card = document.createElement('div');
                card.className = 'relative group rounded-2xl overflow-hidden border border-gray-200/80 bg-gray-50 shadow-sm hover:shadow-md transition-all duration-300';
                card.style.aspectRatio = '3/4';
                card.innerHTML = `
                    <img src="${e.target.result}" class="w-full h-full object-cover object-top group-hover:scale-105 transition-transform duration-500">
                    <div class="absolute inset-0 bg-linear-to-t from-black/60 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex items-end p-2 pointer-events-none">
                        <span class="text-[9px] font-bold text-white truncate">${file.name}</span>
                    </div>
                    <div class="absolute top-2 left-2 px-2 py-0.5 bg-black/75 backdrop-blur-md rounded-full text-[9px] font-black text-white shadow-sm border border-white/10">
                        ${idx + 1}
                    </div>
                    <button type="button" onclick="removeNewEditImageAt(${idx})" class="absolute top-2 right-2 w-7 h-7 bg-red-600/95 hover:bg-red-600 text-white rounded-full flex items-center justify-center text-xs font-black shadow-lg hover:scale-110 active:scale-95 transition-all z-10 cursor-pointer" title="Remove photo">✕</button>
                `;
                grid.appendChild(card);
            };
            reader.readAsDataURL(file);
        });
    } else {
        grid.classList.add('hidden');
        grid.classList.remove('grid');
        if (badge) badge.classList.add('hidden');
        if (titleEl) titleEl.textContent = 'Click to Add Photos';
        if (subEl) subEl.textContent = 'Optional — only if replacing or adding new shots';
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const dropZone = document.getElementById('dropZone');
    const input = document.getElementById('imageUploadInput');
    
    if (dropZone && input) {
        ['dragenter', 'dragover'].forEach(evt => {
            dropZone.addEventListener(evt, (e) => {
                e.preventDefault();
                e.stopPropagation();
                dropZone.classList.add('border-[#C49520]', 'bg-orange-50/40');
            });
        });

        ['dragleave', 'drop'].forEach(evt => {
            dropZone.addEventListener(evt, (e) => {
                e.preventDefault();
                e.stopPropagation();
                dropZone.classList.remove('border-[#C49520]', 'bg-orange-50/40');
            });
        });

        dropZone.addEventListener('drop', (e) => {
            const files = e.dataTransfer?.files;
            if (files && files.length > 0) {
                let duplicateCount = 0;
                let oversizedCount = 0;

                Array.from(files).forEach(file => {
                    if (file.type && file.type.startsWith('image/')) {
                        if (file.size > 5 * 1024 * 1024) {
                            oversizedCount++;
                            return;
                        }
                        const exists = Array.from(editProductImagesDT.files).some(f => f.name === file.name && f.size === file.size);
                        if (exists) {
                            duplicateCount++;
                        } else {
                            editProductImagesDT.items.add(file);
                        }
                    }
                });

                if (oversizedCount > 0) {
                    triggerAppModal('Image Exceeds 5MB', `${oversizedCount} photo(s) exceeded the 5MB size limit and were skipped.`, 'warning');
                } else if (duplicateCount > 0) {
                    triggerAppModal('Duplicate Image Skipped', `${duplicateCount} duplicate image(s) already added and were skipped.`, 'warning');
                }

                input.files = editProductImagesDT.files;
                renderEditImagePreviews();
            }
        });
    }
});

function updateCharCount(el) {
    const counter = document.getElementById('charCounter');
    if (counter) {
        counter.textContent = el.value.length;
    }
}

function previewQr(input, previewId, placeholderId) {
    const preview = document.getElementById(previewId);
    const placeholder = document.getElementById(placeholderId);
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => {
            preview.querySelector('img').src = e.target.result;
            preview.classList.remove('hidden');
            placeholder.classList.add('hidden');
        };
        reader.readAsDataURL(input.files[0]);
    }
}

function toggleSizeStock(checkbox, size) {
    const stockInput = document.getElementById('stock_' + size);
    if (checkbox.checked) {
        stockInput.removeAttribute('disabled');
        if (stockInput.value === '' || stockInput.value === '0') {
            stockInput.value = '5';
        }
    } else {
        stockInput.value = '0';
        stockInput.setAttribute('disabled', 'true');
    }
    calculateTotalStock();
}

function calculateTotalStock() {
    let total = 0;
    const inputs = document.querySelectorAll('.size-stock-input');
    const checkboxes = document.querySelectorAll('.size-checkbox');

    checkboxes.forEach((cb, idx) => {
        if (cb.checked) {
            const val = parseInt(inputs[idx].value) || 0;
            total += val;
        }
    });

    const totalStockEl = document.getElementById('total_stock');
    if (totalStockEl) totalStockEl.value = total;
}

function toggleDiscount(checkbox) {
    const fields = document.getElementById('discountFields');
    const hiddenInput = document.getElementById('isOnSaleInput');
    if (checkbox && checkbox.checked) {
        fields.classList.remove('hidden');
        if (hiddenInput) hiddenInput.value = '1';
        updateDiscountPreview();
    } else {
        fields.classList.add('hidden');
        if (hiddenInput) hiddenInput.value = '0';
        const preview = document.getElementById('discountPreview');
        if (preview) preview.classList.add('hidden');
        const pct = document.getElementById('discountPercentage');
        if (pct) pct.value = '';
    }
}

function updateDiscountPreview() {
    const priceInput = document.querySelector('input[name="price"]');
    const pctInput = document.getElementById('discountPercentage');
    const preview = document.getElementById('discountPreview');
    const previewOriginal = document.getElementById('previewOriginal');
    const previewSale = document.getElementById('previewSale');

    if (!priceInput || !pctInput) return;

    const price = parseFloat(priceInput.value) || 0;
    const pct = parseFloat(pctInput.value) || 0;

    if (price > 0 && pct > 0 && pct < 100) {
        const salePrice = price * (1 - pct / 100);
        if (previewOriginal) previewOriginal.textContent = '₱' + price.toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        if (previewSale) previewSale.textContent = '₱' + salePrice.toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        if (preview) {
            preview.classList.remove('hidden');
            preview.classList.add('flex');
        }
    } else if (preview) {
        preview.classList.add('hidden');
        preview.classList.remove('flex');
    }
}

function validateProductForm(e, isEdit = true) {
    const action = document.getElementById('formActionInput')?.value || 'publish';
    if (action === 'draft') {
        const nameInput = document.querySelector('input[name="name"]');
        if (!nameInput || !nameInput.value.trim()) {
            e.preventDefault();
            alert('Please provide a Product Name to save your draft.');
            if (nameInput) {
                nameInput.classList.add('border-red-500');
                nameInput.focus();
            }
            return false;
        }
        return true;
    }

    const errors = [];
    
    // Clear previous error styles
    document.querySelectorAll('.border-red-500').forEach(el => el.classList.remove('border-red-500'));
    const oldJsBanner = document.getElementById('js-error-banner');
    if (oldJsBanner) oldJsBanner.remove();

    // 1. Name & Description
    const nameInput = document.querySelector('input[name="name"]');
    if (!nameInput || !nameInput.value.trim()) {
        errors.push('Product Name is required.');
        if (nameInput) nameInput.classList.add('border-red-500');
    }

    const descInput = document.querySelector('textarea[name="description"]');
    if (!descInput || !descInput.value.trim()) {
        errors.push('Artisan Description is required.');
        if (descInput) descInput.classList.add('border-red-500');
    }

    // 2. Price
    const priceInput = document.querySelector('input[name="price"]');
    const priceVal = parseFloat(priceInput ? priceInput.value : 0);
    if (!priceInput || isNaN(priceVal) || priceVal < 1) {
        errors.push('Price must be at least ₱1.00.');
        if (priceInput) priceInput.classList.add('border-red-500');
    } else if (priceVal > 10000) {
        errors.push('Price cannot exceed ₱10,000.00.');
        if (priceInput) priceInput.classList.add('border-red-500');
    }

    // Shipping Fee
    const shipFeeInput = document.querySelector('input[name="shippingFee"]');
    if (shipFeeInput) {
        const shipFeeVal = parseFloat(shipFeeInput.value) || 0;
        if (shipFeeVal < 0 || shipFeeVal > 500) {
            errors.push('Shipping Fee must be between ₱0.00 and ₱500.00.');
            shipFeeInput.classList.add('border-red-500');
        }
    }

    // Shipping Days
    const shipDaysInput = document.querySelector('input[name="shippingDays"]');
    if (shipDaysInput) {
        const shipDaysVal = parseInt(shipDaysInput.value) || 0;
        if (shipDaysVal < 1 || shipDaysVal > 30) {
            errors.push('Estimated Shipping Days must be between 1 and 30 days.');
            shipDaysInput.classList.add('border-red-500');
        }
    }

    // 3. Heritage Sizing & Stock
    const checkedSizes = document.querySelectorAll('.size-checkbox:checked');
    const sizingSection = document.getElementById('sizing-section');
    
    if (checkedSizes.length === 0) {
        errors.push('Please select at least one Heritage Size (e.g. S, M, L, XL, XXL, Custom).');
        if (sizingSection) sizingSection.classList.add('border-red-500');
    } else {
        let invalidStockCount = 0;
        checkedSizes.forEach(cb => {
            const sizeVal = cb.value;
            const stockInput = document.getElementById('stock_' + sizeVal);
            const qty = parseInt(stockInput ? stockInput.value : 0) || 0;
            if (qty <= 0) {
                invalidStockCount++;
                if (stockInput) stockInput.classList.add('border-red-500');
            } else if (qty > 10000) {
                invalidStockCount++;
                errors.push(`Stock for Size ${sizeVal} cannot exceed 10,000 units.`);
                if (stockInput) stockInput.classList.add('border-red-500');
            }
        });
        if (invalidStockCount > 0 && !errors.some(e => e.includes('10,000'))) {
            errors.push('Each checked Heritage size must have a stock quantity greater than 0.');
            if (sizingSection) sizingSection.classList.add('border-red-500');
        }
    }

    const totalStock = parseInt(document.getElementById('total_stock')?.value || 0);
    if (totalStock <= 0) {
        errors.push('Total product stock must be greater than 0.');
    }

    // 4. Product Category & Target Group
    const selectedCats = Array.from(document.querySelectorAll('input[name="category_ids[]"]')).map(i => i.value).filter(Boolean);
    const legacyCat = document.querySelector('input[name="CategoryId"]')?.value;
    const hasCategory = Boolean(selectedCats.length > 0 || legacyCat);
    const catContainer = document.getElementById('category-cards-container');

    if (!hasCategory) {
        errors.push('Please select a Product Category.');
        if (catContainer) catContainer.classList.add('border-red-500');
    }

    const targetGroupChecked = document.querySelector('input[name="target_group"]:checked');
    const targetGroupContainer = document.getElementById('target-group-container');
    if (!targetGroupChecked) {
        errors.push('Please specify who this product is for (Men, Women, or Kids).');
        if (targetGroupContainer) targetGroupContainer.classList.add('border-red-500', 'border');
    }

    // 5. Payment Methods (Both Number and QR Code strictly required)
    const gcashToggle = document.getElementById('gcash_toggle_edit');
    const mayaToggle = document.getElementById('maya_toggle_edit');
    const paymentCard = document.getElementById('payment-methods-card');
    const isGcashChecked = gcashToggle ? gcashToggle.checked : false;
    const isMayaChecked = mayaToggle ? mayaToggle.checked : false;

    let paymentConfig = {};
    try {
        paymentConfig = JSON.parse(document.getElementById('seller-payment-config')?.textContent || '{}');
    } catch (e) {}

    const hasGcashNumber = Boolean(paymentConfig.hasGcashNumber);
    const hasGcashQr = Boolean(paymentConfig.hasGcashQr);
    const hasMayaNumber = Boolean(paymentConfig.hasMayaNumber);
    const hasMayaQr = Boolean(paymentConfig.hasMayaQr);

    let hasAnyCompleteEnabled = false;

    if (isGcashChecked) {
        if (!hasGcashNumber || !hasGcashQr) {
            if (!hasGcashNumber && !hasGcashQr) {
                errors.push('GCash is enabled but not configured. Both Mobile Number and QR Code are required.');
            } else if (!hasGcashQr) {
                errors.push('GCash is enabled but missing a QR Code. Both Mobile Number and QR Code are required.');
            } else {
                errors.push('GCash is enabled but missing a Mobile Number. Both Mobile Number and QR Code are required.');
            }
            if (paymentCard) paymentCard.classList.add('border-red-500');
        } else {
            hasAnyCompleteEnabled = true;
        }
    }

    if (isMayaChecked) {
        if (!hasMayaNumber || !hasMayaQr) {
            if (!hasMayaNumber && !hasMayaQr) {
                errors.push('Maya is enabled but not configured. Both Account Number and QR Code are required.');
            } else if (!hasMayaQr) {
                errors.push('Maya is enabled but missing a QR Code. Both Account Number and QR Code are required.');
            } else {
                errors.push('Maya is enabled but missing an Account Number. Both Account Number and QR Code are required.');
            }
            if (paymentCard) paymentCard.classList.add('border-red-500');
        } else {
            hasAnyCompleteEnabled = true;
        }
    }

    if (!isGcashChecked && !isMayaChecked) {
        errors.push('Please enable at least one payment method (GCash or Maya).');
        if (paymentCard) paymentCard.classList.add('border-red-500');
    } else if (!hasAnyCompleteEnabled && !errors.some(e => e.includes('GCash') || e.includes('Maya'))) {
        errors.push('Please enable at least one complete payment method with both a mobile number and a QR code.');
        if (paymentCard) paymentCard.classList.add('border-red-500');
    }

    // 6. Lumban Special Discount (Optional)
    const isOnSale = document.getElementById('discountToggle')?.checked;
    if (isOnSale) {
        const pctInput = document.getElementById('discountPercentage');
        const pctVal = parseFloat(pctInput ? pctInput.value : 0);
        if (isNaN(pctVal) || pctVal < 1 || pctVal > 99) {
            errors.push('Discount percentage must be between 1% and 99%.');
            if (pctInput) pctInput.classList.add('border-red-500');
        }
    }

    if (errors.length > 0) {
        e.preventDefault();

        const banner = document.createElement('div');
        banner.id = 'js-error-banner';
        banner.className = 'fixed top-8 left-1/2 -translate-x-1/2 z-50 w-[92%] max-w-md bg-white rounded-2xl shadow-2xl border border-red-200 p-4.5 flex items-start gap-3.5 transition-all';
        banner.innerHTML = `
            <div class="w-8 h-8 rounded-full bg-red-50 flex items-center justify-center text-red-600 shrink-0 border border-red-100 font-bold">✕</div>
            <div class="grow pt-0.5">
                <h4 class="text-xs font-black text-black uppercase tracking-wider">Please fix the following form errors</h4>
                <ul class="text-xs text-red-600 font-semibold mt-1 leading-relaxed space-y-1 list-disc list-inside">
                    ${errors.map(err => `<li>${err}</li>`).join('')}
                </ul>
            </div>
            <button onclick="this.parentElement.remove()" class="text-gray-300 hover:text-gray-500 shrink-0 font-bold">✕</button>
        `;
        document.body.appendChild(banner);

        setTimeout(() => {
            const b = document.getElementById('js-error-banner');
            if (b) b.remove();
        }, 8000);

        const firstError = document.querySelector('.border-red-500');
        if (firstError) {
            firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }

        return false;
    }

    return true;
}

// ================================================================
// TEMPORARY FORM DATA PERSISTENCE (Reload = Keep, Leave = Delete)
// ================================================================
function getEditTempKey() {
    const initData = getEditInitData();
    return 'lumbarong_seller_edit_product_temp_' + (initData.productId || 'default');
}

let _editFormSubmitted = false;
let _editSaveTimer = null;

function isEditPageReload() {
    try {
        const navEntries = performance.getEntriesByType('navigation');
        if (navEntries && navEntries.length > 0) {
            return navEntries[0].type === 'reload';
        }
        if (window.performance && window.performance.navigation) {
            return window.performance.navigation.type === 1;
        }
    } catch (e) {}
    return false;
}

function clearEditTemporaryFormData() {
    try {
        sessionStorage.removeItem(getEditTempKey());
    } catch (e) {}
}

function saveEditTemporaryFormData() {
    if (_editFormSubmitted) return;
    clearTimeout(_editSaveTimer);
    _editSaveTimer = setTimeout(() => {
        try {
            const data = {
                name: document.querySelector('input[name="name"]')?.value || '',
                description: document.querySelector('textarea[name="description"]')?.value || '',
                price: document.querySelector('input[name="price"]')?.value || '',
                fabric_type: document.querySelector('input[name="fabric_type"]')?.value || '',
                shippingFee: document.querySelector('input[name="shippingFee"]')?.value || '',
                shippingDays: document.querySelector('input[name="shippingDays"]')?.value || '',
                isOnSale: document.getElementById('discountToggle')?.checked || false,
                discountPercentage: document.getElementById('discountPercentage')?.value || ''
            };
            sessionStorage.setItem(getEditTempKey(), JSON.stringify(data));
        } catch (e) {}
    }, 300);
}

function restoreEditTemporaryFormData() {
    try {
        const raw = sessionStorage.getItem(getEditTempKey());
        if (!raw) return;
        const data = JSON.parse(raw);
        if (!data) return;

        if (data.name) {
            const el = document.querySelector('input[name="name"]');
            if (el) el.value = data.name;
        }
        if (data.description) {
            const el = document.querySelector('textarea[name="description"]');
            if (el) el.value = data.description;
        }
        if (data.price) {
            const el = document.querySelector('input[name="price"]');
            if (el) el.value = data.price;
        }
        if (data.fabric_type) {
            const el = document.querySelector('input[name="fabric_type"]');
            if (el) el.value = data.fabric_type;
        }
        if (data.shippingFee) {
            const el = document.querySelector('input[name="shippingFee"]');
            if (el) el.value = data.shippingFee;
        }
        if (data.shippingDays) {
            const el = document.querySelector('input[name="shippingDays"]');
            if (el) el.value = data.shippingDays;
        }
        if (data.isOnSale) {
            const toggle = document.getElementById('discountToggle');
            if (toggle) {
                toggle.checked = true;
                if (typeof toggleDiscount === 'function') toggleDiscount(toggle);
            }
            if (data.discountPercentage) {
                const pct = document.getElementById('discountPercentage');
                if (pct) pct.value = data.discountPercentage;
            }
        }
        updateDiscountPreview();
    } catch (e) {}
}

document.addEventListener('DOMContentLoaded', function() {
    if (isEditPageReload()) {
        setTimeout(restoreEditTemporaryFormData, 200);
    } else {
        clearEditTemporaryFormData();
    }

    const priceInput = document.querySelector('input[name="price"]');
    if (priceInput) {
        priceInput.addEventListener('input', updateDiscountPreview);
    }
    updateDiscountPreview();

    document.querySelectorAll('.target-group-radio').forEach(radio => {
        radio.addEventListener('change', function() {
            const container = document.getElementById('target-group-container');
            if (container) {
                container.classList.remove('border-red-500', 'border');
            }
        });
    });

    document.addEventListener('input', saveEditTemporaryFormData);
    document.addEventListener('change', saveEditTemporaryFormData);

    const form = document.querySelector('form[action*="products/"]');
    if (form) {
        form.addEventListener('submit', () => {
            _editFormSubmitted = true;
            clearEditTemporaryFormData();
        });
    }

    document.addEventListener('click', (e) => {
        const anchor = e.target.closest('a[href]');
        if (!anchor) return;
        const href = anchor.getAttribute('href');
        if (!href || href.startsWith('#') || href.startsWith('javascript')) return;
        try {
            const destUrl = new URL(href, window.location.origin);
            if (destUrl.pathname !== window.location.pathname) {
                clearEditTemporaryFormData();
            }
        } catch (err) {}
    });
});
</script>
@endsection
