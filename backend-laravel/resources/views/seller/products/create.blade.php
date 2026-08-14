@extends('layouts.seller')

@section('content')
<div class="max-w-350 mx-auto pb-36 sm:pb-28 lg:pb-12 px-2.5 sm:px-6">
    <div class="mb-3 sm:mb-10">
        <a href="{{ route('seller.products.index') }}" class="inline-flex items-center gap-2 text-[10px] font-bold text-gray-400 uppercase tracking-widest hover:text-[#C0420A] transition-colors mb-1.5 sm:mb-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            Back to catalogue
        </a>
        <h1 class="font-serif text-xl sm:text-3xl font-bold text-black uppercase">New <span class="text-[#C0420A] italic lowercase">heritage piece</span></h1>
    </div>
    @if($errors->any() || session('error'))
    <div 
        x-data="{ show: true, init() { setTimeout(() => this.show = false, 8000) } }"
        x-show="show"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 translate-y-2 sm:translate-y-0 sm:translate-x-2"
        x-transition:enter-end="opacity-100 translate-y-0 sm:translate-x-0"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed top-6 right-4 sm:right-6 z-50 w-[calc(100%-2rem)] sm:w-full max-w-sm bg-white/95 backdrop-blur-md rounded-2xl shadow-2xl border border-red-200 p-4 flex items-start gap-3.5"
        x-cloak
    >
        <div class="w-8 h-8 rounded-full bg-red-50 flex items-center justify-center text-red-600 shrink-0 shadow-sm border border-red-100">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path></svg>
        </div>
        <div class="grow pt-0.5">
            <h4 class="text-xs font-black text-black uppercase tracking-wider">Please fix the following</h4>
            @if(session('error'))
                <div class="text-xs text-red-600 font-bold mt-1">{{ session('error') }}</div>
            @endif
            @if($errors->any())
                <ul class="text-xs text-gray-500 font-medium mt-1 leading-relaxed space-y-0.5 list-disc list-inside">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            @endif
        </div>
        <button @click="show = false" class="text-gray-300 hover:text-gray-500 transition-colors shrink-0">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
        </button>
    </div>
    @endif

    <form action="{{ route('seller.products.store') }}" method="POST" enctype="multipart/form-data" onsubmit="return validateProductForm(event, false)" class="grid grid-cols-1 lg:grid-cols-3 gap-4 sm:gap-6">
        @csrf

        {{-- Left Column: Core Product Data (2 cols) --}}
        <div class="lg:col-span-2 space-y-4">

            {{-- 1. Basic Product Information --}}
            <div class="bg-white p-4 sm:p-6 rounded-2xl border border-gray-100 shadow-xs space-y-4">
                <div class="flex items-center gap-2.5 pb-2 border-b border-gray-100/80">
                    <div class="w-7 h-7 rounded-lg bg-[#C0420A]/10 flex items-center justify-center text-[#C0420A] shrink-0">
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
                                Product Name <span class="text-[#C0420A]">*</span>
                            </label>
                            <span class="text-[9px] text-gray-400 font-medium hidden sm:inline-block">Concise & descriptive</span>
                        </div>
                        <input type="text" name="name" required value="{{ old('name') }}"
                            placeholder="e.g. Hand-Woven Piña Barong Tagalog"
                            class="w-full px-3.5 py-2.5 sm:px-4 sm:py-3 bg-gray-50/70 border border-gray-200/90 rounded-xl outline-none focus:border-[#C0420A] focus:bg-white focus:ring-2 focus:ring-[#C0420A]/10 transition-all font-semibold text-sm text-gray-800 placeholder:text-gray-400 placeholder:font-normal">
                    </div>

                    {{-- Artisan Description --}}
                    <div class="space-y-1.5" x-data="{
                        showAiGen: false,
                        aiFabric: 'Piña-Seda Silk',
                        aiEmbroidery: 'Lumban Calado Hand Embroidery',
                        aiCollar: 'Mandarin / Chinese Collar',
                        aiTheme: 'Wedding & Formal Gala',
                        aiGenLoading: false,
                        generateAiStory() {
                            this.aiGenLoading = true;
                            fetch('/ai/seller/generate-description', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('input[name=_token]').value
                                },
                                body: JSON.stringify({
                                    fabric: this.aiFabric,
                                    embroidery: this.aiEmbroidery,
                                    collar: this.aiCollar,
                                    theme: this.aiTheme,
                                    category: 'Barong Tagalog'
                                })
                            })
                            .then(res => res.json())
                            .then(data => {
                                if (data.title) {
                                    const nameInput = document.querySelector('input[name=name]');
                                    if (nameInput && !nameInput.value) nameInput.value = data.title;
                                }
                                const descInput = document.getElementById('artisanDescription');
                                if (descInput) {
                                    descInput.value = (data.description || '').substring(0, 500);
                                    updateCharCount(descInput);
                                }
                                this.showAiGen = false;
                                if (window.Alpine && Alpine.store('toast')) {
                                    Alpine.store('toast').trigger('AI artisan description generated!', 'success');
                                }
                            })
                            .catch(() => {})
                            .finally(() => { this.aiGenLoading = false; });
                        }
                    }">
                        <div class="flex items-center justify-between">
                            <label class="text-[10px] sm:text-[11px] font-bold uppercase tracking-wider text-gray-700">
                                Artisan Description <span class="text-[#C0420A]">*</span>
                            </label>
                            <div class="flex items-center gap-2">
                                <button type="button" @click="showAiGen = !showAiGen" class="text-[10px] font-extrabold text-[#C0420A] bg-red-50 hover:bg-red-100 px-2 py-0.5 rounded-md border border-red-100 flex items-center gap-1 transition-colors cursor-pointer">
                                    <span>✨</span>
                                    <span>AI Story Generator</span>
                                </button>
                                <span class="text-[9px] text-gray-400 font-medium hidden sm:inline-block">Max 500 characters</span>
                            </div>
                        </div>

                        <!-- AI Generator Quick Dropdown -->
                        <div x-show="showAiGen" x-transition x-cloak class="p-3.5 bg-amber-50/70 border border-amber-200/80 rounded-xl space-y-2.5 mb-2">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-black text-amber-900 flex items-center gap-1">
                                    <span>✨</span>
                                    <span>Lumban Artisan Storycraft AI</span>
                                </span>
                                <button type="button" @click="showAiGen = false" class="text-xs text-amber-700 hover:text-amber-950">✕</button>
                            </div>
                            <div class="grid grid-cols-2 gap-2 text-xs">
                                <div>
                                    <label class="block text-[9px] font-bold uppercase text-amber-800 mb-0.5">Fabric</label>
                                    <select x-model="aiFabric" class="w-full bg-white border border-amber-200 rounded-lg p-1.5 text-xs font-semibold outline-none">
                                        <option value="Piña-Seda Silk">Piña-Seda Silk</option>
                                        <option value="Cocoon Silk">Cocoon Silk</option>
                                        <option value="Jusi Silk Blend">Jusi Silk Blend</option>
                                        <option value="High-Grade Organza">Organza</option>
                                        <option value="Natural Linen">Natural Linen</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-[9px] font-bold uppercase text-amber-800 mb-0.5">Embroidery Style</label>
                                    <select x-model="aiEmbroidery" class="w-full bg-white border border-amber-200 rounded-lg p-1.5 text-xs font-semibold outline-none">
                                        <option value="Lumban Calado Hand Embroidery">Lumban Calado Hand Embroidery</option>
                                        <option value="Full Pechera Hand-Needlework">Full Pechera Needlework</option>
                                        <option value="Geometric Contemporary Burda">Geometric Burda</option>
                                        <option value="Floral Vine Monochromatic">Floral Vine Monochromatic</option>
                                    </select>
                                </div>
                            </div>
                            <button type="button" @click="generateAiStory()" :disabled="aiGenLoading" class="w-full py-2 bg-[#C0420A] hover:bg-[#a33708] disabled:opacity-50 text-white font-black text-xs uppercase tracking-wider rounded-lg shadow-sm transition-all flex items-center justify-center gap-1.5 cursor-pointer">
                                <span x-show="!aiGenLoading">✨ Auto-Generate Title & Story</span>
                                <span x-show="aiGenLoading" class="flex items-center gap-1"><span>Weaving story...</span></span>
                            </button>
                        </div>

                        <div class="relative group">
                            <textarea name="description" id="artisanDescription" required rows="4" maxlength="500"
                                oninput="updateCharCount(this)"
                                placeholder="Describe the craftsmanship, cultural heritage, weaving techniques, and unique story behind this piece..."
                                class="w-full px-3.5 py-2.5 sm:px-4 sm:py-3 bg-gray-50/70 border border-gray-200/90 rounded-xl outline-none focus:border-[#C0420A] focus:bg-white focus:ring-2 focus:ring-[#C0420A]/10 transition-all font-normal text-sm text-gray-800 placeholder:text-gray-400 resize-none pb-7 sm:pb-8">{{ old('description') }}</textarea>
                            <div class="absolute bottom-2 right-2.5 sm:bottom-2.5 sm:right-3.5 flex items-center gap-1 bg-white/95 backdrop-blur-xs px-2 py-0.5 rounded-md border border-gray-100 text-[9px] sm:text-[10px] font-bold text-gray-400 pointer-events-none shadow-2xs">
                                <span id="charCounter">{{ strlen(old('description', '')) }}</span><span class="text-gray-300">/</span><span>500</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- 2. Pricing & Shipping Stat Card --}}
            <div class="bg-white p-4 sm:p-6 rounded-2xl border border-gray-100 shadow-xs">
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
                    <div id="price-card" class="p-3.5 bg-[#F9F8F6] border border-stone-200/60 rounded-xl flex flex-col justify-between h-24 sm:h-26 transition-all">
                        <label class="text-[9px] font-bold uppercase tracking-widest text-stone-500">Price (₱) <span class="text-[#C0420A]">*</span></label>
                        <input type="number" name="price" id="priceInput" required min="1" max="10000" step="0.01" placeholder="0.00"
                            oninput="if(parseFloat(this.value) > 10000) this.value = 10000; updateDiscountPreview();"
                            class="w-full bg-transparent font-sans text-lg font-bold text-gray-900 outline-none border-b border-transparent focus:border-[#C0420A] transition-all">
                        <p class="text-[8px] text-stone-400 font-medium">Item base price</p>
                    </div>

                    <div id="stock-card" class="p-3.5 bg-[#F9F8F6] border border-stone-200/60 rounded-xl flex flex-col justify-between h-24 sm:h-26 transition-all">
                        <label class="text-[9px] font-bold uppercase tracking-widest text-stone-500">Total Stock <span class="text-[#C0420A]">*</span></label>
                        <input type="number" name="stock" id="total_stock" min="0" placeholder="0"
                            readonly tabindex="-1"
                            class="w-full bg-transparent font-sans text-lg font-bold text-gray-900 outline-none select-none cursor-not-allowed">
                        <p class="text-[8px] text-stone-400 font-medium">Auto-calculated</p>
                    </div>

                    <div id="shipping-fee-card" class="p-3.5 bg-[#F9F8F6] border border-stone-200/60 rounded-xl flex flex-col justify-between h-24 sm:h-26 transition-all">
                        <label class="text-[9px] font-bold uppercase tracking-widest text-stone-500">Shipping Fee (₱) <span class="text-[#C0420A]">*</span></label>
                        <input type="number" name="shippingFee" id="shippingFeeInput" required min="0" max="500" step="0.01" placeholder="0.00"
                            value="{{ old('shippingFee', 0) }}"
                            oninput="if(parseFloat(this.value) > 500) this.value = 500;"
                            class="w-full bg-transparent font-sans text-lg font-bold text-gray-900 outline-none border-b border-transparent focus:border-[#C0420A] transition-all">
                        <p class="text-[8px] text-stone-400 font-medium">Enter 0 for free</p>
                    </div>

                    <div id="shipping-days-card" class="p-3.5 bg-[#F9F8F6] border border-stone-200/60 rounded-xl flex flex-col justify-between h-24 sm:h-26 transition-all">
                        <label class="text-[9px] font-bold uppercase tracking-widest text-stone-500">Est. Shipping Days <span class="text-[#C0420A]">*</span></label>
                        <input type="number" name="shippingDays" id="shippingDaysInput" required min="1" max="30" step="1" placeholder="5"
                            value="{{ old('shippingDays', 5) }}"
                            oninput="if(parseInt(this.value) > 30) this.value = 30;"
                            class="w-full bg-transparent font-sans text-lg font-bold text-gray-900 outline-none border-b border-transparent focus:border-[#C0420A] transition-all">
                        <p class="text-[8px] text-stone-400 font-medium">To deliver</p>
                    </div>
                </div>
            </div>

            {{-- 3. Heritage Sizing & Inventory Card --}}
            <div id="sizing-section" class="bg-white p-4 sm:p-6 rounded-2xl border border-gray-100 shadow-xs space-y-3 transition-all">
                <div class="flex items-center justify-between">
                    <h3 class="text-xs sm:text-sm font-bold text-black uppercase tracking-widest">Heritage Sizing & Stock <span class="text-[#C0420A]">*</span></h3>
                    <span class="text-[10px] text-gray-400 font-medium">Assign stock per size</span>
                </div>
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-2.5">
                    @foreach(['S', 'M', 'L', 'XL', 'XXL', 'Custom'] as $size)
                        <div class="p-2.5 bg-gray-50 border border-gray-200 rounded-xl space-y-2 text-center transition-all hover:bg-white hover:border-gray-300">
                            <label class="flex items-center justify-center gap-1.5 text-xs font-black uppercase text-gray-700 cursor-pointer select-none">
                                <input type="checkbox" name="sizes[]" value="{{ $size }}" id="size_cb_{{ $size }}"
                                    class="rounded text-[#C0420A] focus:ring-[#C0420A] w-3.5 h-3.5 size-checkbox"
                                    onchange="toggleSizeStock(this, '{{ $size }}')">
                                <span>Size {{ $size }}</span>
                            </label>
                            <input type="number" name="size_stocks[{{ $size }}]" id="stock_{{ $size }}" 
                                value="0" min="0" max="10000" disabled
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
                        <label class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Product Category <span class="text-[#C0420A]">*</span></label>
                        <select name="CategoryId" id="categorySelect" required
                            class="w-full px-4 py-2.5 bg-gray-50/50 border border-gray-200/80 rounded-xl outline-none focus:border-[#C0420A] transition-all font-bold text-xs appearance-none">
                            <option value="" disabled selected>Select a category</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" data-name="{{ strtolower($category->name) }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div id="target-group-container" class="space-y-1.5 p-1 rounded-xl transition-all">
                        <label class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Who is this for? <span class="text-[#C0420A]">*</span></label>
                        <div class="flex gap-2">
                            @foreach(['Men', 'Women', 'Kids'] as $group)
                                <label class="flex-1 cursor-pointer">
                                    <input type="radio" name="target_group" value="{{ $group }}" class="hidden peer target-group-radio" {{ old('target_group') == $group ? 'checked' : '' }}>
                                    <div class="w-full py-2 rounded-xl border border-gray-200 bg-gray-50/50 text-xs font-bold text-gray-500 text-center uppercase tracking-wider peer-checked:border-[#C0420A] peer-checked:bg-[#C0420A]/5 peer-checked:text-[#C0420A] transition-all">
                                        {{ $group }}
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- Lumban Special Discount Panel --}}
                <div class="p-4 rounded-xl border border-[#C0420A]/15 bg-orange-50/20 space-y-3">
                    <input type="hidden" name="is_on_sale" id="isOnSaleInput" value="0">

                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-[#C0420A]"></span>
                            <span class="text-xs font-black text-[#C0420A] uppercase tracking-widest">Lumban Special Sale</span>
                            <span class="text-[9px] text-gray-400 font-bold uppercase tracking-wider">(Optional)</span>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer shrink-0">
                            <input type="checkbox" id="discountToggle" class="sr-only peer"
                                onchange="toggleDiscount(this)">
                            <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-0.5 after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-[#C0420A]"></div>
                        </label>
                    </div>

                    <div id="discountFields" class="hidden space-y-2.5 pt-2 border-t border-[#C0420A]/10">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 items-end">
                            <div>
                                <label class="text-[9px] font-bold uppercase tracking-widest text-gray-400 mb-1 block">Discount (%)</label>
                                <input type="number" name="discount_percentage" id="discountPercentage"
                                    min="1" max="99" step="1" placeholder="e.g. 20"
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

            {{-- Product Media --}}
            <div class="bg-white p-4 sm:p-6 rounded-2xl border border-gray-100 shadow-xs space-y-4">
                <div class="flex items-center justify-between">
                    <h3 class="text-xs sm:text-sm font-bold text-black uppercase tracking-widest">Product Images <span class="text-[#C0420A]">*</span></h3>
                    <span id="img-count-badge" class="hidden text-[9px] font-black uppercase tracking-widest px-2.5 py-0.5 bg-[#C0420A]/10 text-[#C0420A] rounded-full">0 photos</span>
                </div>

                <div class="space-y-2">
                    <label for="imageUploadInput"
                        id="dropZone"
                        class="flex flex-col items-center justify-center gap-2 w-full min-h-32 rounded-xl border-2 border-dashed border-gray-200 bg-gray-50/50 hover:bg-white hover:border-[#C0420A] transition-all cursor-pointer p-4 text-center relative overflow-hidden">
                        <svg class="w-6 h-6 text-gray-400 group-hover:text-[#C0420A] transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        <div id="dropZoneTitle" class="text-xs font-bold text-gray-700 uppercase tracking-widest">Click to Upload Photos</div>
                        <p id="dropZoneSubtitle" class="text-[9px] text-gray-400">PNG, JPG, WEBP &mdash; portrait shots</p>
                        <input type="file" id="imageUploadInput" name="images[]" multiple class="hidden" onchange="previewImages(this)">
                    </label>

                    <div id="image-preview-grid" class="hidden grid-cols-3 gap-2">
                        {{-- JS populated --}}
                    </div>
                </div>
            </div>

            {{-- Payment Method Configuration --}}
            <div id="payment-methods-card" class="bg-white p-4 sm:p-6 rounded-2xl border border-gray-100 shadow-xs space-y-3 transition-all">
                <div class="flex items-center justify-between mb-1">
                    <h3 class="text-xs sm:text-sm font-black text-black uppercase tracking-widest">Payment Methods <span class="text-[#C0420A]">*</span></h3>
                    <a href="{{ route('seller.profile') }}?open_payment=1#payment-methods" class="text-[11px] font-bold text-[#C0420A] hover:underline flex items-center gap-1">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><circle cx="12" cy="12" r="3"/></svg>
                        Settings
                    </a>
                </div>

                {{-- GCash --}}
                @php 
                    $user = auth()->user(); 
                    $hasGcashNumber = !empty($user->gcashNumber);
                    $hasGcashQr = !empty($user->gcashQrCode);
                    $isGcashComplete = $hasGcashNumber && $hasGcashQr;
                @endphp
                <div class="rounded-xl border border-blue-100 overflow-hidden">
                    {{-- Header --}}
                    <div class="flex items-center justify-between px-3 py-2.5 bg-linear-to-r from-blue-600 to-blue-500">
                        <div class="flex items-center gap-2">
                            <div class="w-6 h-6 rounded-md bg-white/20 flex items-center justify-center">
                                <svg class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                            </div>
                            <span class="text-[10px] font-black uppercase tracking-widest text-white">GCash</span>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="product_is_gcash_available" value="1" id="gcash_toggle_create" class="sr-only peer" {{ old('product_is_gcash_available', true) ? 'checked' : '' }} onchange="document.getElementById('gcash_fields_create').style.display = this.checked ? '' : 'none'">
                            <div class="w-9 h-5 bg-white/30 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-0.5 after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-white/40 border border-white/40"></div>
                        </label>
                    </div>
                    {{-- Body --}}
                    <div id="gcash_fields_create" {{ old('product_is_gcash_available', true) ? '' : 'style=display:none' }} class="p-3 bg-white flex items-center gap-3">
                        @if($hasGcashQr)
                            @php
                                $gcashQr = $user->gcashQrCode;
                                $gcashQrUrl = str_starts_with($gcashQr, 'http') ? $gcashQr : (str_starts_with(ltrim($gcashQr,'/'), 'uploads/') ? asset(ltrim($gcashQr,'/')) : asset('storage/' . ltrim($gcashQr,'/')));
                            @endphp
                            <img src="{{ $gcashQrUrl }}" class="w-12 h-12 object-contain rounded-lg border-2 border-blue-100 bg-blue-50/40 shrink-0" onerror="this.style.display='none'">
                        @else
                            <div class="w-12 h-12 rounded-lg border-2 border-dashed border-blue-100 bg-blue-50/30 flex items-center justify-center shrink-0">
                                <svg class="w-5 h-5 text-blue-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
                            </div>
                        @endif
                        <div class="flex-1 min-w-0">
                            @if($isGcashComplete)
                                <div class="text-sm font-black text-gray-900 tracking-wide">{{ $user->gcashNumber }}</div>
                                <div class="text-[9px] text-blue-500 font-bold uppercase tracking-widest mt-0.5 flex items-center gap-1">
                                    <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                    Ready (Number & QR Set)
                                </div>
                            @elseif($hasGcashNumber && !$hasGcashQr)
                                <div class="text-xs font-black text-gray-900">{{ $user->gcashNumber }}</div>
                                <div class="text-[9px] text-amber-600 font-bold mt-0.5">Missing QR Code (Required)</div>
                                <a href="{{ route('seller.profile') }}?open_payment=1#payment-methods" class="text-[9px] text-blue-600 font-bold underline">Upload QR in Settings →</a>
                            @elseif(!$hasGcashNumber && $hasGcashQr)
                                <div class="text-[10px] text-amber-600 font-bold">Missing Mobile Number (Required)</div>
                                <a href="{{ route('seller.profile') }}?open_payment=1#payment-methods" class="text-[9px] text-blue-600 font-bold underline">Add Number in Settings →</a>
                            @else
                                <div class="text-[10px] text-gray-400 italic">Not configured</div>
                                <div class="text-[8px] text-gray-400 font-medium">Both Number & QR required</div>
                                <a href="{{ route('seller.profile') }}?open_payment=1#payment-methods" class="text-[9px] text-blue-600 font-bold underline">Add in Settings →</a>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Maya --}}
                @php 
                    $hasMayaNumber = !empty($user->mayaNumber);
                    $hasMayaQr = !empty($user->mayaQrCode);
                    $isMayaComplete = $hasMayaNumber && $hasMayaQr;
                @endphp
                <div class="rounded-xl border border-green-100 overflow-hidden">
                    {{-- Header --}}
                    <div class="flex items-center justify-between px-3 py-2.5 bg-linear-to-r from-green-600 to-green-500">
                        <div class="flex items-center gap-2">
                            <div class="w-6 h-6 rounded-md bg-white/20 flex items-center justify-center">
                                <svg class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                            </div>
                            <span class="text-[10px] font-black uppercase tracking-widest text-white">Maya</span>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="product_is_maya_available" value="1" id="maya_toggle_create" class="sr-only peer" {{ old('product_is_maya_available', false) ? 'checked' : '' }} onchange="document.getElementById('maya_fields_create').style.display = this.checked ? '' : 'none'">
                            <div class="w-9 h-5 bg-white/30 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-0.5 after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-white/40 border border-white/40"></div>
                        </label>
                    </div>
                    {{-- Body --}}
                    <div id="maya_fields_create" {{ old('product_is_maya_available', false) ? '' : 'style=display:none' }} class="p-3 bg-white flex items-center gap-3">
                        @if($hasMayaQr)
                            @php
                                $mayaQr = $user->mayaQrCode;
                                $mayaQrUrl = str_starts_with($mayaQr, 'http') ? $mayaQr : (str_starts_with(ltrim($mayaQr,'/'), 'uploads/') ? asset(ltrim($mayaQr,'/')) : asset('storage/' . ltrim($mayaQr,'/')));
                            @endphp
                            <img src="{{ $mayaQrUrl }}" class="w-12 h-12 object-contain rounded-lg border-2 border-green-100 bg-green-50/40 shrink-0" onerror="this.style.display='none'">
                        @else
                            <div class="w-12 h-12 rounded-lg border-2 border-dashed border-green-100 bg-green-50/30 flex items-center justify-center shrink-0">
                                <svg class="w-5 h-5 text-green-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
                            </div>
                        @endif
                        <div class="flex-1 min-w-0">
                            @if($isMayaComplete)
                                <div class="text-sm font-black text-gray-900 tracking-wide">{{ $user->mayaNumber }}</div>
                                <div class="text-[9px] text-green-600 font-bold uppercase tracking-widest mt-0.5 flex items-center gap-1">
                                    <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                    Ready (Number & QR Set)
                                </div>
                            @elseif($hasMayaNumber && !$hasMayaQr)
                                <div class="text-xs font-black text-gray-900">{{ $user->mayaNumber }}</div>
                                <div class="text-[9px] text-amber-600 font-bold mt-0.5">Missing QR Code (Required)</div>
                                <a href="{{ route('seller.profile') }}?open_payment=1#payment-methods" class="text-[9px] text-green-600 font-bold underline">Upload QR in Settings →</a>
                            @elseif(!$hasMayaNumber && $hasMayaQr)
                                <div class="text-[10px] text-amber-600 font-bold">Missing Mobile Number (Required)</div>
                                <a href="{{ route('seller.profile') }}?open_payment=1#payment-methods" class="text-[9px] text-green-600 font-bold underline">Add Number in Settings →</a>
                            @else
                                <div class="text-[10px] text-gray-400 italic">Not configured</div>
                                <div class="text-[8px] text-gray-400 font-medium">Both Number & QR required</div>
                                <a href="{{ route('seller.profile') }}?open_payment=1#payment-methods" class="text-[9px] text-green-600 font-bold underline">Add in Settings →</a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- Submission Action Card --}}
            <div class="bg-white p-4 sm:p-6 rounded-2xl border border-gray-100 shadow-xs space-y-3">
                <div class="p-3 rounded-xl bg-amber-50/60 border border-amber-100">
                    <p class="text-[9px] text-amber-800 font-bold uppercase tracking-wider">
                        New listings are reviewed by admin before appearing in shop.
                    </p>
                </div>
                <button type="submit" class="w-full py-3.5 bg-black text-white rounded-xl font-bold uppercase tracking-[0.15em] shadow-md hover:bg-[#C0420A] transition-all text-xs">
                    Submit Listing
                </button>
            </div>
        </div>

        {{-- Mobile Sticky Action Bar --}}
        <div class="lg:hidden fixed bottom-16 inset-x-0 bg-white/95 backdrop-blur-md border-t border-gray-200 px-3.5 py-2.5 z-30 shadow-2xl flex items-center justify-between gap-2.5">
            <div class="min-w-0">
                <div class="text-[10px] font-black text-black uppercase tracking-wider truncate">New Heritage Piece</div>
                <div class="text-[9px] text-gray-400 font-bold uppercase tracking-widest truncate">Ready to submit</div>
            </div>
            <button type="submit" class="px-4 py-2 bg-[#C0420A] text-white rounded-xl text-[11px] font-black uppercase tracking-widest shadow-md hover:bg-black transition-all shrink-0">
                Submit Listing
            </button>
        </div>
    </form>
</div>

<script type="application/json" id="seller-payment-config">
{!! json_encode([
    'hasGcashNumber' => !empty($user->gcashNumber),
    'hasGcashQr' => !empty($user->gcashQrCode),
    'hasMayaNumber' => !empty($user->mayaNumber),
    'hasMayaQr' => !empty($user->mayaQrCode),
]) !!}
</script>

<script>
let productImagesDT = new DataTransfer();

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
            const exists = Array.from(productImagesDT.files).some(f => f.name === file.name && f.size === file.size);
            if (exists) {
                duplicateCount++;
            } else {
                productImagesDT.items.add(file);
            }
        });

        if (oversizedCount > 0) {
            triggerAppModal('Image Exceeds 5MB', `${oversizedCount} photo(s) exceeded the 5MB size limit and were skipped.`, 'warning');
        } else if (duplicateCount > 0) {
            triggerAppModal('Duplicate Image Skipped', `${duplicateCount} duplicate image(s) already added and were skipped.`, 'warning');
        }

        input.files = productImagesDT.files;
    }
    renderImagePreviews();
}

function removeImageAt(index) {
    const input = document.getElementById('imageUploadInput');
    const newDT = new DataTransfer();
    Array.from(productImagesDT.files).forEach((file, i) => {
        if (i !== index) newDT.items.add(file);
    });
    productImagesDT = newDT;
    if (input) input.files = productImagesDT.files;
    renderImagePreviews();
}

function renderImagePreviews() {
    const grid = document.getElementById('image-preview-grid');
    const badge = document.getElementById('img-count-badge');
    const titleEl = document.getElementById('dropZoneTitle');
    const subEl = document.getElementById('dropZoneSubtitle');

    if (!grid) return;
    grid.innerHTML = '';

    const files = productImagesDT.files;
    if (files && files.length > 0) {
        grid.classList.remove('hidden');
        grid.classList.add('grid');
        if (badge) {
            badge.classList.remove('hidden');
            badge.textContent = files.length + ' photo' + (files.length !== 1 ? 's' : '');
        }
        if (titleEl) titleEl.textContent = '+ Add More Photos';
        if (subEl) subEl.textContent = `${files.length} photo(s) selected — click or drop to add more`;

        Array.from(files).forEach((file, idx) => {
            const reader = new FileReader();
            reader.onload = function(e) {
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
                    <button type="button" onclick="removeImageAt(${idx})" class="absolute top-2 right-2 w-7 h-7 bg-red-600/95 hover:bg-red-600 text-white rounded-full flex items-center justify-center text-xs font-black shadow-lg hover:scale-110 active:scale-95 transition-all z-10" title="Remove photo">✕</button>
                `;
                grid.appendChild(card);
            };
            reader.readAsDataURL(file);
        });
    } else {
        grid.classList.add('hidden');
        grid.classList.remove('grid');
        if (badge) badge.classList.add('hidden');
        if (titleEl) titleEl.textContent = 'Click to Upload Photos';
        if (subEl) subEl.textContent = 'PNG, JPG, WEBP — portrait shots recommended';
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
                        const exists = Array.from(productImagesDT.files).some(f => f.name === file.name && f.size === file.size);
                        if (exists) {
                            duplicateCount++;
                        } else {
                            productImagesDT.items.add(file);
                        }
                    }
                });

                if (oversizedCount > 0) {
                    triggerAppModal('Image Exceeds 5MB', `${oversizedCount} photo(s) exceeded the 5MB size limit and were skipped.`, 'warning');
                } else if (duplicateCount > 0) {
                    triggerAppModal('Duplicate Image Skipped', `${duplicateCount} duplicate image(s) already added and were skipped.`, 'warning');
                }

                input.files = productImagesDT.files;
                renderImagePreviews();
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

function validateProductForm(e, isEdit = false) {
    const errors = [];
    
    // Clear previous error styles
    document.querySelectorAll('.border-red-500, .ring-2.ring-red-500').forEach(el => {
        el.classList.remove('border-red-500', 'ring-2', 'ring-red-500');
    });
    const oldJsBanner = document.getElementById('js-error-banner');
    if (oldJsBanner) oldJsBanner.remove();

    // 1. Basic Information (Name & Description)
    const nameInput = document.querySelector('input[name="name"]');
    if (!nameInput || !nameInput.value.trim()) {
        errors.push('Product Name is required.');
        if (nameInput) nameInput.classList.add('border-red-500');
    } else if (nameInput.value.trim().length < 3) {
        errors.push('Product Name must be at least 3 characters.');
        if (nameInput) nameInput.classList.add('border-red-500');
    }

    const descInput = document.querySelector('textarea[name="description"]');
    if (!descInput || !descInput.value.trim()) {
        errors.push('Artisan Description is required.');
        if (descInput) descInput.classList.add('border-red-500');
    } else if (descInput.value.trim().length < 10) {
        errors.push('Artisan Description must be at least 10 characters.');
        if (descInput) descInput.classList.add('border-red-500');
    }

    // 2. Pricing & Shipping
    const priceInput = document.querySelector('input[name="price"]');
    const priceCard = document.getElementById('price-card');
    const priceVal = parseFloat(priceInput ? priceInput.value : 0);
    if (!priceInput || isNaN(priceVal) || priceVal < 1) {
        errors.push('Product Price is required (must be at least ₱1.00).');
        if (priceCard) priceCard.classList.add('border-red-500');
        if (priceInput) priceInput.classList.add('border-red-500');
    } else if (priceVal > 10000) {
        errors.push('Product Price cannot exceed ₱10,000.00.');
        if (priceCard) priceCard.classList.add('border-red-500');
        if (priceInput) priceInput.classList.add('border-red-500');
    }

    const shipFeeInput = document.querySelector('input[name="shippingFee"]');
    const shipFeeCard = document.getElementById('shipping-fee-card');
    if (!shipFeeInput || shipFeeInput.value === '' || isNaN(parseFloat(shipFeeInput.value))) {
        errors.push('Shipping Fee is required (enter 0 for free delivery).');
        if (shipFeeCard) shipFeeCard.classList.add('border-red-500');
    } else {
        const shipFeeVal = parseFloat(shipFeeInput.value);
        if (shipFeeVal < 0 || shipFeeVal > 500) {
            errors.push('Shipping Fee must be between ₱0.00 and ₱500.00.');
            if (shipFeeCard) shipFeeCard.classList.add('border-red-500');
        }
    }

    const shipDaysInput = document.querySelector('input[name="shippingDays"]');
    const shipDaysCard = document.getElementById('shipping-days-card');
    if (!shipDaysInput || !shipDaysInput.value || isNaN(parseInt(shipDaysInput.value))) {
        errors.push('Estimated Shipping Days is required.');
        if (shipDaysCard) shipDaysCard.classList.add('border-red-500');
    } else {
        const shipDaysVal = parseInt(shipDaysInput.value);
        if (shipDaysVal < 1 || shipDaysVal > 30) {
            errors.push('Estimated Shipping Days must be between 1 and 30 days.');
            if (shipDaysCard) shipDaysCard.classList.add('border-red-500');
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
        const stockCard = document.getElementById('stock-card');
        if (stockCard) stockCard.classList.add('border-red-500');
    }

    // 4. Product Category & Target Group
    const categorySelect = document.getElementById('categorySelect');
    if (!categorySelect || !categorySelect.value) {
        errors.push('Please select a Product Category.');
        if (categorySelect) categorySelect.classList.add('border-red-500');
    }

    const targetGroupChecked = document.querySelector('input[name="target_group"]:checked');
    const targetGroupContainer = document.getElementById('target-group-container');
    if (!targetGroupChecked) {
        errors.push('Please specify who this product is for (Men, Women, or Kids).');
        if (targetGroupContainer) targetGroupContainer.classList.add('border-red-500', 'border');
    }

    // 5. Product Imagery
    if (!isEdit) {
        const hasFiles = (productImagesDT && productImagesDT.files && productImagesDT.files.length > 0) ||
                         (document.getElementById('imageUploadInput')?.files?.length > 0);
        if (!hasFiles) {
            errors.push('Please upload at least one product image.');
            const dropZone = document.getElementById('dropZone');
            if (dropZone) dropZone.classList.add('border-red-500');
        }
    }

    // 6. Payment Methods (Both Number and QR Code strictly required)
    const gcashToggle = document.getElementById('gcash_toggle_create');
    const mayaToggle = document.getElementById('maya_toggle_create');
    const paymentCard = document.getElementById('payment-methods-card');
    const isGcashChecked = gcashToggle ? gcashToggle.checked : false;
    const isMayaChecked = mayaToggle ? mayaToggle.checked : false;

    const paymentConfig = JSON.parse(document.getElementById('seller-payment-config')?.textContent || '{}');
    const hasGcashNumber = Boolean(paymentConfig.hasGcashNumber);
    const hasGcashQr = Boolean(paymentConfig.hasGcashQr);
    const hasMayaNumber = Boolean(paymentConfig.hasMayaNumber);
    const hasMayaQr = Boolean(paymentConfig.hasMayaQr);

    let hasAnyCompleteEnabled = false;

    if (isGcashChecked) {
        if (!hasGcashNumber || !hasGcashQr) {
            if (!hasGcashNumber && !hasGcashQr) {
                errors.push('GCash is enabled but not configured. Both Mobile Number and QR Code are required (Add in Settings).');
            } else if (!hasGcashQr) {
                errors.push('GCash is enabled but missing a QR Code. Both Mobile Number and QR Code are required (Upload in Settings).');
            } else {
                errors.push('GCash is enabled but missing a Mobile Number. Both Mobile Number and QR Code are required (Add in Settings).');
            }
            if (paymentCard) paymentCard.classList.add('border-red-500');
        } else {
            hasAnyCompleteEnabled = true;
        }
    }

    if (isMayaChecked) {
        if (!hasMayaNumber || !hasMayaQr) {
            if (!hasMayaNumber && !hasMayaQr) {
                errors.push('Maya is enabled but not configured. Both Account Number and QR Code are required (Add in Settings).');
            } else if (!hasMayaQr) {
                errors.push('Maya is enabled but missing a QR Code. Both Account Number and QR Code are required (Upload in Settings).');
            } else {
                errors.push('Maya is enabled but missing an Account Number. Both Account Number and QR Code are required (Add in Settings).');
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

    // 7. Lumban Special Discount (Optional)
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

        // Create floating error banner (Centered at top)
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

        // Auto remove banner after 8 seconds
        setTimeout(() => {
            const b = document.getElementById('js-error-banner');
            if (b) b.remove();
        }, 8000);

        // Smooth scroll & focus to first invalid field
        const firstError = document.querySelector('.border-red-500');
        if (firstError) {
            firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
            if (typeof firstError.focus === 'function') {
                firstError.focus();
            }
        }

        return false;
    }

    return true;
}

// Also update preview when price changes & clear radio validation errors
document.addEventListener('DOMContentLoaded', function() {
    const priceInput = document.querySelector('input[name="price"]');
    if (priceInput) {
        priceInput.addEventListener('input', updateDiscountPreview);
    }

    document.querySelectorAll('.target-group-radio').forEach(radio => {
        radio.addEventListener('change', function() {
            const container = document.getElementById('target-group-container');
            if (container) {
                container.classList.remove('border-red-500', 'border');
            }
        });
    });
});
</script>
@endsection
