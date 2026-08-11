@extends('layouts.seller')

@section('content')
<div class="max-w-350 mx-auto pb-36 sm:pb-28 lg:pb-12 px-2.5 sm:px-6" x-data="{ deleteModal: false, deleteProductId: null, deleteProductName: '' }">
    <div class="mb-3 sm:mb-10">
        <a href="{{ route('seller.products.index') }}" class="inline-flex items-center gap-2 text-[10px] font-bold text-gray-400 uppercase tracking-widest hover:text-[#C0420A] transition-colors mb-1.5 sm:mb-3">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            Back to catalogue
        </a>
        <h1 class="font-serif text-xl sm:text-3xl font-bold text-black uppercase">Edit <span class="text-[#C0420A] italic lowercase">heritage piece</span></h1>
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

        {{-- Left Column: Core Product Data (2 cols) --}}
        <div class="lg:col-span-2 space-y-4">

            {{-- 1. Basic Product Information --}}
            <div class="bg-white p-4 sm:p-6 rounded-2xl border border-gray-100 shadow-xs space-y-4">
                <h3 class="text-xs sm:text-sm font-bold text-black uppercase tracking-widest flex items-center gap-2">
                    <svg class="w-4 h-4 text-[#C0420A]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    Basic Information
                </h3>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="space-y-1.5">
                        <label class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Product Name</label>
                        <input type="text" name="name" required value="{{ old('name', $product->name) }}"
                            placeholder="e.g. Pina-Silk Formal Barong Tagalog"
                            class="w-full px-4 py-2.5 bg-gray-50/50 border border-gray-200/80 rounded-xl outline-none focus:border-[#C0420A] focus:bg-white transition-all font-medium text-sm text-gray-800">
                    </div>

                    <div class="space-y-1.5">
                        <label class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Materials Used (Fabric)</label>
                        <input type="text" name="fabric_type" value="{{ old('fabric_type', $product->fabric_type ?? '100% Piña') }}"
                            placeholder="e.g. 100% Piña, Piña Organza, Jusi"
                            class="w-full px-4 py-2.5 bg-gray-50/50 border border-gray-200/80 rounded-xl outline-none focus:border-[#C0420A] focus:bg-white transition-all font-medium text-sm text-gray-800">
                    </div>
                </div>

                <div class="space-y-1.5">
                    <label class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Artisan Description</label>
                    <div class="relative">
                        <textarea name="description" id="artisanDescription" required rows="3" maxlength="500"
                            oninput="updateCharCount(this)"
                            placeholder="Describe the craftsmanship, materials used, and the story behind this piece..."
                            class="w-full px-4 py-2.5 bg-gray-50/50 border border-gray-200/80 rounded-xl outline-none focus:border-[#C0420A] focus:bg-white transition-all font-medium resize-none text-sm text-gray-800 pb-7">{{ old('description', $product->description) }}</textarea>
                        <div id="charCounter" class="absolute bottom-2.5 right-3.5 text-[10px] font-bold text-gray-400 pointer-events-none">
                            {{ strlen(old('description', $product->description ?? '')) }} / 500
                        </div>
                    </div>
                </div>
            </div>

            {{-- 2. Pricing & Shipping Stat Card --}}
            <div class="bg-white p-4 sm:p-6 rounded-2xl border border-gray-100 shadow-xs">
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
                    {{-- Price --}}
                    <div class="p-3.5 bg-[#F9F8F6] border border-stone-200/60 rounded-xl flex flex-col justify-between h-24 sm:h-26">
                        <label class="text-[9px] font-bold uppercase tracking-widest text-stone-500">Price (₱)</label>
                        <input type="number" name="price" required min="1" max="10000" step="0.01"
                            value="{{ old('price', $product->price) }}"
                            oninput="if(parseFloat(this.value) > 10000) this.value = 10000; updateDiscountPreview();"
                            class="w-full bg-transparent font-sans text-lg font-bold text-gray-900 outline-none border-b border-transparent focus:border-[#C0420A] transition-all">
                        <p class="text-[8px] text-stone-400 font-medium">Item base price</p>
                    </div>

                    {{-- Total Stock --}}
                    <div class="p-3.5 bg-[#F9F8F6] border border-stone-200/60 rounded-xl flex flex-col justify-between h-24 sm:h-26">
                        <label class="text-[9px] font-bold uppercase tracking-widest text-stone-500">Total Stock</label>
                        <input type="number" name="stock" id="total_stock" min="0"
                            value="{{ old('stock', $product->stock) }}"
                            readonly tabindex="-1"
                            class="w-full bg-transparent font-sans text-lg font-bold text-gray-900 outline-none select-none cursor-not-allowed">
                        <p class="text-[8px] text-stone-400 font-medium">Auto-calculated</p>
                    </div>

                    {{-- Shipping Fee --}}
                    <div class="p-3.5 bg-[#F9F8F6] border border-stone-200/60 rounded-xl flex flex-col justify-between h-24 sm:h-26">
                        <label class="text-[9px] font-bold uppercase tracking-widest text-stone-500">Shipping Fee (₱)</label>
                        <input type="number" name="shippingFee" min="0" max="500" step="0.01" placeholder="0.00"
                            value="{{ old('shippingFee', $product->shippingFee ?? 0) }}"
                            oninput="if(parseFloat(this.value) > 500) this.value = 500;"
                            class="w-full bg-transparent font-sans text-lg font-bold text-gray-900 outline-none border-b border-transparent focus:border-[#C0420A] transition-all">
                        <p class="text-[8px] text-stone-400 font-medium">Enter 0 for free</p>
                    </div>

                    {{-- Est. Shipping Days --}}
                    <div class="p-3.5 bg-[#F9F8F6] border border-stone-200/60 rounded-xl flex flex-col justify-between h-24 sm:h-26">
                        <label class="text-[9px] font-bold uppercase tracking-widest text-stone-500">Est. Shipping Days</label>
                        <input type="number" name="shippingDays" min="1" max="30" step="1" placeholder="5"
                            value="{{ old('shippingDays', $product->shippingDays ?? 5) }}"
                            oninput="if(parseInt(this.value) > 30) this.value = 30;"
                            class="w-full bg-transparent font-sans text-lg font-bold text-gray-900 outline-none border-b border-transparent focus:border-[#C0420A] transition-all">
                        <p class="text-[8px] text-stone-400 font-medium">To deliver</p>
                    </div>
                </div>
            </div>

            {{-- 3. Heritage Sizing & Inventory Card --}}
            <div id="sizing-section" class="bg-white p-4 sm:p-6 rounded-2xl border border-gray-100 shadow-xs space-y-3">
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
                                    class="rounded text-[#C0420A] focus:ring-[#C0420A] w-3.5 h-3.5 size-checkbox"
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
            <div class="bg-white p-4 sm:p-6 rounded-2xl border border-gray-100 shadow-xs space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="space-y-1.5">
                        <label class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Product Category</label>
                        <select name="CategoryId" id="categorySelect" required
                            class="w-full px-4 py-2.5 bg-gray-50/50 border border-gray-200/80 rounded-xl outline-none focus:border-[#C0420A] transition-all font-bold text-xs appearance-none">
                            <option value="" disabled>Select a category</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" data-name="{{ strtolower($category->name) }}" {{ $product->CategoryId == $category->id ? 'selected' : '' }}>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="space-y-1.5">
                        <label class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Who is this for?</label>
                        <div class="flex gap-2">
                            @foreach(['Men', 'Women', 'Kids'] as $group)
                                <label class="flex-1 cursor-pointer">
                                    <input type="radio" name="target_group" value="{{ $group }}" class="hidden peer"
                                        {{ old('target_group', $product->target_group) == $group ? 'checked' : '' }}>
                                    <div class="w-full py-2 rounded-xl border border-gray-200 bg-gray-50/50 text-xs font-bold text-gray-500 text-center uppercase tracking-wider peer-checked:border-[#C0420A] peer-checked:bg-[#C0420A]/5 peer-checked:text-[#C0420A] transition-all">
                                        {{ $group }}
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- Lumban Special Discount Panel --}}
                @php
                    $isOnSale = old('is_on_sale', $product->is_on_sale ?? false);
                    $discountPct = old('discount_percentage', $product->discount_percentage ?? '');
                @endphp
                <div class="p-4 rounded-xl border border-[#C0420A]/15 bg-orange-50/20 space-y-3">
                    <input type="hidden" name="is_on_sale" id="isOnSaleInput" value="{{ $isOnSale ? '1' : '0' }}">

                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-[#C0420A]"></span>
                            <span class="text-xs font-black text-[#C0420A] uppercase tracking-widest">Lumban Special Sale</span>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer shrink-0">
                            <input type="checkbox" id="discountToggle" class="sr-only peer"
                                {{ $isOnSale ? 'checked' : '' }}
                                onchange="toggleDiscount(this)">
                            <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-0.5 after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-[#C0420A]"></div>
                        </label>
                    </div>

                    <div id="discountFields" class="{{ $isOnSale ? '' : 'hidden' }} space-y-2.5 pt-2 border-t border-[#C0420A]/10">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 items-end">
                            <div>
                                <label class="text-[9px] font-bold uppercase tracking-widest text-gray-400 mb-1 block">Discount (%)</label>
                                <input type="number" name="discount_percentage" id="discountPercentage"
                                    min="1" max="99" step="1" placeholder="e.g. 20"
                                    value="{{ $discountPct }}"
                                    class="w-full px-3.5 py-2.5 bg-white border border-[#C0420A]/30 rounded-xl outline-none font-bold text-sm text-[#C0420A]"
                                    oninput="if(parseInt(this.value) > 99) this.value = 99; updateDiscountPreview();">
                            </div>
                            <div>
                                <label class="text-[9px] font-bold uppercase tracking-widest text-gray-400 mb-1 block">Price Preview</label>
                                <div id="discountPreview" class="hidden w-full px-3.5 py-2.5 bg-white rounded-xl border border-[#C0420A]/20 items-center justify-center gap-2 h-10.5">
                                    <span id="previewOriginal" class="text-xs text-gray-400 line-through font-bold"></span>
                                    <span id="previewSale" class="text-sm font-black text-[#C0420A]"></span>
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
            <div class="bg-white p-4 sm:p-6 rounded-2xl border border-gray-100 shadow-xs space-y-4">
                <div class="flex items-center justify-between">
                    <h3 class="text-xs sm:text-sm font-bold text-black uppercase tracking-widest">Product Images</h3>
                    <span id="img-count-badge" class="hidden text-[9px] font-black uppercase tracking-widest px-2.5 py-0.5 bg-[#C0420A]/10 text-[#C0420A] rounded-full">0 photos</span>
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
                            @php
                                if (str_starts_with($img, 'http')) {
                                    $imgSrc = $img;
                                } elseif (str_starts_with($img, 'products/')) {
                                    $imgSrc = asset('storage/' . $img);
                                } elseif (str_starts_with($img, 'uploads/')) {
                                    $imgSrc = asset($img);
                                } else {
                                    $imgSrc = asset('uploads/products/' . $img);
                                }
                            @endphp
                            <div class="relative group aspect-3/4 rounded-xl overflow-hidden border transition-all shadow-xs"
                                 x-data="{ marked: false }"
                                 :class="marked ? 'border-red-500 opacity-60 scale-95' : 'border-gray-200 hover:border-gray-300'">
                                <img src="{{ $imgSrc }}" class="w-full h-full object-cover">
                                <input type="checkbox" name="remove_images[]" value="{{ $img }}" :checked="marked" class="hidden">
                                
                                <button type="button" @click="marked = !marked"
                                    class="absolute top-1 right-1 w-6 h-6 rounded-full text-[10px] font-black flex items-center justify-center shadow-md transition-all z-10"
                                    :class="marked ? 'bg-red-600 text-white' : 'bg-black/70 text-white hover:bg-red-600'">
                                    ✕
                                </button>
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
                        class="flex flex-col items-center justify-center gap-2 w-full min-h-28 rounded-xl border-2 border-dashed border-gray-200 bg-gray-50/50 hover:bg-white hover:border-[#C0420A] transition-all cursor-pointer p-4 text-center relative overflow-hidden">
                        <svg class="w-6 h-6 text-gray-400 group-hover:text-[#C0420A] transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
            <div class="bg-white p-4 sm:p-6 rounded-2xl border border-gray-100 shadow-xs space-y-3">
                <div class="flex items-center justify-between">
                    <h3 class="text-xs sm:text-sm font-bold text-black uppercase tracking-widest">Payment Methods</h3>
                    <a href="{{ route('seller.profile') }}?open_payment=1#payment-methods" class="text-[9px] font-bold text-[#C0420A] hover:underline">Settings &rarr;</a>
                </div>

                {{-- GCash --}}
                <div class="p-3 bg-gray-50/50 border border-gray-100 rounded-xl space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-[10px] font-black uppercase tracking-widest text-[#0060AA]">GCash</span>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="product_is_gcash_available" value="1" class="sr-only peer"
                                {{ old('product_is_gcash_available', $product->is_gcash_available) ? 'checked' : '' }}>
                            <div class="w-8 h-4.5 bg-gray-200 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-0.5 after:bg-white after:border-gray-300 after:border after:rounded-full after:h-3.5 after:w-3.5 after:transition-all peer-checked:bg-blue-600"></div>
                        </label>
                    </div>
                    <input type="text" name="gcashNumber" value="{{ old('gcashNumber', $product->gcash_number ?? auth()->user()->gcashNumber) }}" placeholder="GCash Number" class="w-full px-3 py-2 bg-white border border-gray-200 rounded-lg text-xs font-bold text-gray-800 outline-none">
                </div>

                {{-- Maya --}}
                <div class="p-3 bg-gray-50/50 border border-gray-100 rounded-xl space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-[10px] font-black uppercase tracking-widest text-[#00B050]">Maya</span>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="product_is_maya_available" value="1" class="sr-only peer"
                                {{ old('product_is_maya_available', $product->is_maya_available) ? 'checked' : '' }}>
                            <div class="w-8 h-4.5 bg-gray-200 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-0.5 after:bg-white after:border-gray-300 after:border after:rounded-full after:h-3.5 after:w-3.5 after:transition-all peer-checked:bg-green-600"></div>
                        </label>
                    </div>
                    <input type="text" name="mayaNumber" value="{{ old('mayaNumber', $product->maya_number ?? auth()->user()->mayaNumber) }}" placeholder="Maya Number" class="w-full px-3 py-2 bg-white border border-gray-200 rounded-lg text-xs font-bold text-gray-800 outline-none">
                </div>
            </div>

            {{-- Actions & Status Card --}}
            <div class="bg-white p-4 sm:p-6 rounded-2xl border border-gray-100 shadow-xs space-y-3">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold uppercase tracking-widest text-gray-400">Status</span>
                    <span class="text-xs font-black uppercase tracking-widest {{ $product->status === 'approved' ? 'text-green-600' : 'text-amber-600' }}">
                        {{ ucfirst($product->status) }}
                    </span>
                </div>

                <button type="submit"
                    class="w-full py-3.5 bg-black text-white rounded-xl font-bold uppercase tracking-[0.15em] shadow-md hover:bg-[#C0420A] transition-all text-xs">
                    Save Changes
                </button>

                <button type="button"
                    @click="deleteModal = true; deleteProductId = '{{ $product->id }}'; deleteProductName = '{{ addslashes($product->name) }}'"
                    class="w-full py-2.5 border border-red-200 text-red-600 rounded-xl font-bold text-[10px] uppercase tracking-widest hover:bg-red-50 transition-all">
                    Delete Listing
                </button>
            </div>
        </div>

        {{-- Mobile Sticky Action Bar --}}
        <div class="lg:hidden fixed bottom-16 inset-x-0 bg-white/95 backdrop-blur-md border-t border-gray-200 px-3.5 py-2.5 z-30 shadow-2xl flex items-center justify-between gap-2.5">
            <div class="min-w-0">
                <div class="text-[10px] font-black text-black uppercase tracking-wider truncate" title="{{ $product->name }}">{{ $product->name }}</div>
                <div class="text-[9px] text-[#C0420A] font-bold uppercase tracking-widest truncate">Editing Listing</div>
            </div>
            <div class="flex items-center gap-2 shrink-0">
                <button type="button"
                    @click="deleteModal = true; deleteProductId = '{{ $product->id }}'; deleteProductName = '{{ addslashes($product->name) }}'"
                    class="p-2.5 bg-red-50 text-red-600 rounded-xl text-xs font-bold transition-all hover:bg-red-100"
                    title="Delete Listing">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                </button>
                <button type="submit" class="px-4 py-2 bg-[#C0420A] text-white rounded-xl text-[11px] font-black uppercase tracking-widest shadow-md hover:bg-black transition-all">
                    Save Changes
                </button>
            </div>
        </div>
    </form>

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
                    class="flex-1 py-3 rounded-xl border border-gray-200 text-[10px] font-bold uppercase tracking-widest text-gray-500 hover:bg-gray-50 transition-all">
                    Keep Listing
                </button>
                <button type="submit"
                    class="flex-1 py-3 rounded-xl bg-red-500 text-white text-[10px] font-bold uppercase tracking-widest hover:bg-red-600 transition-all">
                    Yes, Delete
                </button>
            </form>
        </div>
    </div>
</div>

<script>
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
                    <button type="button" onclick="removeNewEditImageAt(${idx})" class="absolute top-2 right-2 w-7 h-7 bg-red-600/95 hover:bg-red-600 text-white rounded-full flex items-center justify-center text-xs font-black shadow-lg hover:scale-110 active:scale-95 transition-all z-10" title="Remove photo">✕</button>
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
                dropZone.classList.add('border-[#C0420A]', 'bg-orange-50/40');
            });
        });

        ['dragleave', 'drop'].forEach(evt => {
            dropZone.addEventListener(evt, (e) => {
                e.preventDefault();
                e.stopPropagation();
                dropZone.classList.remove('border-[#C0420A]', 'bg-orange-50/40');
            });
        });

        dropZone.addEventListener('drop', (e) => {
            const files = e.dataTransfer?.files;
            if (files && files.length > 0) {
                let duplicateCount = 0;
                let oversizedCount = 0;

                Array.from(files).forEach(file => {
                    if (file.type.startsWith('image/')) {
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

    document.getElementById('total_stock').value = total;
}

function handleCategoryChange(select) {
    const selectedOption = select.options[select.selectedIndex];
    const categoryName = (selectedOption.dataset.name || '').toLowerCase();
    const panel = document.getElementById('lumbanSpecialPanel');

    if (categoryName.includes('lumban special')) {
        panel.classList.remove('hidden');
    } else {
        panel.classList.add('hidden');
        // Reset discount state when switching away
        const toggle = document.getElementById('discountToggle');
        if (toggle) toggle.checked = false;
        toggleDiscount(toggle);
    }
}

function toggleDiscount(checkbox) {
    const fields = document.getElementById('discountFields');
    const hiddenInput = document.getElementById('isOnSaleInput');
    if (checkbox && checkbox.checked) {
        fields.classList.remove('hidden');
        hiddenInput.value = '1';
        updateDiscountPreview();
    } else {
        fields.classList.add('hidden');
        hiddenInput.value = '0';
        document.getElementById('discountPreview').classList.add('hidden');
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
        previewOriginal.textContent = '₱' + price.toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        previewSale.textContent = '₱' + salePrice.toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        preview.classList.remove('hidden');
        preview.classList.add('flex');
    } else {
        preview.classList.add('hidden');
        preview.classList.remove('flex');
    }
}

function validateProductForm(e, isEdit = true) {
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

    // 4. Product Category
    const categorySelect = document.getElementById('categorySelect');
    if (!categorySelect || !categorySelect.value) {
        errors.push('Please select a Product Category.');
        if (categorySelect) categorySelect.classList.add('border-red-500');
    }

    // 5. Lumban Special Discount
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

        // Create floating error banner
        const banner = document.createElement('div');
        banner.id = 'js-error-banner';
        banner.className = 'fixed top-6 right-6 z-50 w-full max-w-md bg-white rounded-2xl shadow-2xl border border-red-200 p-4 flex items-start gap-3.5';
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

        // Auto remove banner after 8 seconds
        setTimeout(() => {
            const b = document.getElementById('js-error-banner');
            if (b) b.remove();
        }, 8000);

        // Smooth scroll to first invalid field
        const firstError = document.querySelector('.border-red-500');
        if (firstError) {
            firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }

        return false;
    }

    return true;
}

// Update preview on page load and register event listeners
document.addEventListener('DOMContentLoaded', function() {
    const priceInput = document.querySelector('input[name="price"]');
    if (priceInput) {
        priceInput.addEventListener('input', updateDiscountPreview);
    }
    updateDiscountPreview();
});
</script>
@endsection
