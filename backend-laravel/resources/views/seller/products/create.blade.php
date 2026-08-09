@extends('layouts.seller')

@section('content')
<div class="max-w-350 mx-auto pb-28 lg:pb-12 px-2 sm:px-6">
    <div class="mb-4 sm:mb-10">
        <a href="{{ route('seller.products.index') }}" class="inline-flex items-center gap-2 text-[10px] font-bold text-gray-400 uppercase tracking-widest hover:text-[#C0420A] transition-colors mb-2">
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

    <form action="{{ route('seller.products.store') }}" method="POST" enctype="multipart/form-data" onsubmit="return validateProductForm(event, false)" class="grid grid-cols-1 lg:grid-cols-3 gap-5 sm:gap-8">
        @csrf
        <div class="lg:col-span-2 space-y-5 sm:space-y-8">
            <div class="bg-white p-4 sm:p-8 rounded-3xl border border-gray-100 shadow-sm space-y-5 sm:space-y-8">
                <div class="space-y-4">
                    <label class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Product Name</label>
                    <input type="text" name="name" required placeholder="e.g. Pina-Silk Formal Barong Tagalog" class="w-full px-6 py-4 bg-gray-50/50 border border-gray-100 rounded-2xl outline-none focus:border-[#C0420A] transition-all font-medium text-lg">
                </div>

                <div class="space-y-4">
                    <label class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Artisan Description</label>
                    <textarea name="description" required rows="6" placeholder="Describe the craftsmanship, materials used, and the story behind this piece..." class="w-full px-6 py-4 bg-gray-50/50 border border-gray-100 rounded-2xl outline-none focus:border-[#C0420A] transition-all font-medium resize-none"></textarea>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div class="space-y-4">
                        <label class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Price (₱)</label>
                        <input type="number" name="price" required min="1" max="10000" step="0.01" placeholder="0.00"
                            oninput="if(parseFloat(this.value) > 10000) this.value = 10000; updateDiscountPreview();"
                            class="w-full px-6 py-4 bg-gray-50/50 border border-gray-100 rounded-2xl outline-none focus:border-[#C0420A] transition-all font-bold text-xl">
                    </div>
                    <div class="space-y-4">
                        <label class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Total Stock</label>
                        <input type="number" name="stock" id="total_stock" min="0" placeholder="0"
                            readonly tabindex="-1"
                            class="w-full px-6 py-4 bg-gray-100 border border-gray-100 rounded-2xl outline-none font-bold text-xl text-gray-400 cursor-not-allowed select-none">
                        <p class="text-[9px] text-gray-400 italic -mt-2">Auto-calculated from sizes below.</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div class="space-y-4">
                        <label class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Shipping Fee (₱)</label>
                        <input type="number" name="shippingFee" min="0" max="500" step="0.01" placeholder="0.00"
                            value="{{ old('shippingFee', 0) }}"
                            oninput="if(parseFloat(this.value) > 500) this.value = 500;"
                            class="w-full px-6 py-4 bg-gray-50/50 border border-gray-100 rounded-2xl outline-none focus:border-[#C0420A] transition-all font-bold text-xl">
                        <p class="text-[9px] text-gray-400 italic -mt-2">Enter 0 for free shipping.</p>
                    </div>
                    <div class="space-y-4">
                        <label class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Est. Shipping Days</label>
                        <input type="number" name="shippingDays" min="1" max="30" step="1" placeholder="e.g. 5"
                            value="{{ old('shippingDays', 5) }}"
                            oninput="if(parseInt(this.value) > 30) this.value = 30;"
                            class="w-full px-6 py-4 bg-gray-50/50 border border-gray-100 rounded-2xl outline-none focus:border-[#C0420A] transition-all font-bold text-xl">
                    </div>
                </div>

                <div id="sizing-section" class="space-y-6 pt-6 border-t border-gray-100 rounded-2xl p-4 transition-all">
                    <h3 class="text-sm font-bold text-black uppercase tracking-widest">Heritage Sizing & Stock</h3>
                    <p class="text-[10px] text-gray-400">Select sizes and assign stock for each size. The overall stock will update automatically.</p>
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                        @foreach(['S', 'M', 'L', 'XL', 'XXL', 'Custom'] as $size)
                            <div class="p-4 border border-gray-100 bg-gray-50/50 rounded-2xl flex flex-col justify-between gap-3">
                                <label class="flex items-center gap-2 cursor-pointer font-bold text-xs text-gray-600">
                                    <input type="checkbox" name="sizes[]" value="{{ $size }}" 
                                        class="rounded text-[#C0420A] focus:ring-[#C0420A] w-4 h-4 size-checkbox"
                                        onchange="toggleSizeStock(this, '{{ $size }}')">
                                    <span>Size {{ $size }}</span>
                                </label>
                                <input type="number" name="size_stocks[{{ $size }}]" id="stock_{{ $size }}" 
                                    value="0" min="0" max="10000" disabled
                                    oninput="if(parseInt(this.value) > 10000) this.value = 10000; calculateTotalStock();"
                                    class="w-full px-4 py-2.5 bg-white border border-gray-100 rounded-xl outline-none text-xs font-bold text-center size-stock-input">
                            </div>
                        @endforeach
                    </div>

                    <!-- Size Guide Image Upload -->
                    <div class="mt-6 pt-6 border-t border-gray-100 space-y-4">
                        <label class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Product Size Guide Chart / Image (Optional)</label>
                        <p class="text-[10px] text-gray-400 -mt-2">Upload a custom size guide chart for this product so customers can see exact measurements on the size guide modal.</p>
                        <div class="relative group" x-data="{ guidePreview: '' }">
                            <input type="file" name="size_guide_image" accept="image/*"
                                class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10"
                                @change="
                                    const file = $event.target.files[0];
                                    if (file) {
                                        const reader = new FileReader();
                                        reader.onload = (e) => { guidePreview = e.target.result; };
                                        reader.readAsDataURL(file);
                                    }
                                ">
                            <div class="border-2 border-dashed border-gray-200 bg-white rounded-2xl p-6 flex flex-col items-center justify-center text-center hover:border-[#C0420A] hover:bg-[#C0420A]/5 transition-all">
                                <template x-if="guidePreview">
                                    <div class="relative w-full max-h-48 overflow-hidden rounded-xl border border-gray-200">
                                        <img :src="guidePreview" class="w-full h-48 object-contain">
                                    </div>
                                </template>
                                <template x-if="!guidePreview">
                                    <div class="space-y-2">
                                        <svg class="w-8 h-8 text-gray-300 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path>
                                        </svg>
                                        <span class="text-xs font-bold text-gray-600 block">Upload Size Guide Chart/Image</span>
                                        <span class="text-[10px] text-gray-400 font-medium block">PNG, JPG, WEBP up to 5MB</span>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="space-y-4 pt-6 border-t border-gray-100">
                    <label class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Product Category</label>
                    <select name="CategoryId" id="categorySelect" required
                        class="w-full px-6 py-4 bg-gray-50/50 border border-gray-100 rounded-2xl outline-none focus:border-[#C0420A] transition-all font-bold text-sm appearance-none">
                        <option value="" disabled selected>Select a category</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" data-name="{{ strtolower($category->name) }}">{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Lumban Special Discount Panel (always visible, independent of category) --}}
                <div class="space-y-5 pt-6 border-t border-[#C0420A]/15">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 bg-[#C0420A] rounded-xl flex items-center justify-center shrink-0">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-5 5a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 10V5a2 2 0 012-2z"/>
                            </svg>
                        </div>
                        <div>
                            <h4 class="text-sm font-black text-[#C0420A] uppercase tracking-widest">Lumban Special</h4>
                            <p class="text-[9px] text-gray-400 uppercase tracking-widest">Sale / Discount Configuration — Independent of category</p>
                        </div>
                    </div>

                    <div class="rounded-2xl p-5 border border-[#C0420A]/15 space-y-4" style="background: linear-gradient(to bottom right, #FFF5F0, #fff);">
                        <input type="hidden" name="is_on_sale" id="isOnSaleInput" value="0">

                        <div class="flex items-center justify-between p-4 bg-white rounded-xl border border-[#C0420A]/20">
                            <div>
                                <div class="text-xs font-black text-gray-700 uppercase tracking-widest">Mark as Lumban Special Sale</div>
                                <div class="text-[9px] text-gray-400 mt-0.5">Products will display a "Lumban Special" badge with discount</div>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" id="discountToggle" class="sr-only peer"
                                    onchange="toggleDiscount(this)">
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-0.5 after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[#C0420A]"></div>
                                <span class="ml-2 text-[10px] font-bold text-gray-500 uppercase tracking-widest peer-checked:text-[#C0420A]">On Sale</span>
                            </label>
                        </div>

                        <div id="discountFields" class="hidden space-y-4">
                            <div class="space-y-2">
                                <label class="text-[10px] font-black uppercase tracking-widest text-gray-400">Discount Percentage (%)</label>
                                <div class="relative">
                                    <input type="number" name="discount_percentage" id="discountPercentage"
                                        min="1" max="99" step="1" placeholder="e.g. 20"
                                        class="w-full px-6 py-4 bg-white border border-[#C0420A]/30 rounded-2xl outline-none focus:border-[#C0420A] transition-all font-black text-xl text-[#C0420A]"
                                        oninput="if(parseInt(this.value) > 99) this.value = 99; updateDiscountPreview();">
                                    <span class="absolute right-5 top-1/2 -translate-y-1/2 text-lg font-black text-[#C0420A]">%</span>
                                </div>
                            </div>
                            <div id="discountPreview" class="hidden p-4 bg-[#C0420A]/5 rounded-xl border border-[#C0420A]/20">
                                <div class="text-[9px] font-black uppercase tracking-widest text-gray-400 mb-1">Preview</div>
                                <div class="flex items-center gap-3">
                                    <span id="previewOriginal" class="text-sm text-gray-400 line-through font-bold"></span>
                                    <span id="previewSale" class="text-xl font-black text-[#C0420A]"></span>
                                    <span class="px-2 py-0.5 bg-[#C0420A] text-white text-[9px] font-black uppercase tracking-widest rounded-full">Lumban Special</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="space-y-4 pt-6 border-t border-gray-100">
                    <label class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Who is this for?</label>
                    <div class="flex gap-3">
                        @foreach(['Men', 'Women', 'Kids'] as $group)
                            <label class="flex-1 cursor-pointer group">
                                <input type="radio" name="target_group" value="{{ $group }}" class="hidden peer" {{ old('target_group') == $group ? 'checked' : '' }}>
                                <div class="w-full py-3 rounded-xl border-2 border-gray-100 bg-gray-50/50 text-xs font-black text-gray-400 text-center uppercase tracking-widest peer-checked:border-[#C0420A] peer-checked:bg-[#C0420A]/5 peer-checked:text-[#C0420A] peer-checked:shadow-md peer-checked:shadow-[#C0420A]/10 hover:border-gray-300 transition-all">
                                    {{ $group }}
                                </div>
                            </label>
                        @endforeach
                    </div>
                    <p class="text-[9px] text-gray-400 italic">Selecting a target group makes this product appear when customers filter by Men, Women, or Kids.</p>
                </div>
            </div>
        </div>

        <div class="space-y-5 sm:space-y-8">
            <div class="bg-white p-4 sm:p-8 rounded-3xl border border-gray-100 shadow-sm space-y-5">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-bold text-black uppercase tracking-widest">Product Imagery</h3>
                    <span id="img-count-badge" class="hidden text-[9px] font-black uppercase tracking-widest px-2.5 py-1 bg-[#C0420A]/10 text-[#C0420A] rounded-full">0 photos</span>
                </div>

                {{-- Drop Zone --}}
                <label for="imageUploadInput"
                    id="dropZone"
                    class="flex flex-col items-center justify-center gap-3 w-full min-h-40 rounded-2xl border-2 border-dashed border-gray-200 bg-gray-50 hover:bg-white hover:border-[#C0420A] transition-all cursor-pointer group px-6 py-8 text-center">
                    <div class="w-12 h-12 rounded-2xl bg-gray-100 group-hover:bg-[#C0420A]/10 flex items-center justify-center transition-colors">
                        <svg class="w-6 h-6 text-gray-300 group-hover:text-[#C0420A] transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <div>
                        <div class="text-xs font-black text-gray-600 group-hover:text-[#C0420A] uppercase tracking-widest transition-colors">Click to Upload Photos</div>
                        <p class="text-[9px] text-gray-400 mt-1">PNG, JPG, WEBP &mdash; portrait shots recommended</p>
                    </div>
                    <input type="file" id="imageUploadInput" name="images[]" multiple required class="hidden" onchange="previewImages(this)">
                </label>

                {{-- Preview Grid --}}
                <div id="image-preview-grid" class="hidden grid-cols-3 gap-3">
                    {{-- JS-populated --}}
                </div>
            </div>

            {{-- Payment Method Configuration --}}
            <div class="bg-white p-4 sm:p-8 rounded-3xl border border-gray-100 shadow-sm space-y-5 sm:space-y-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-bold text-black uppercase tracking-widest flex items-center gap-2">
                            <svg class="w-4 h-4 text-[#C0420A]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>
                            Payment Methods
                        </h3>
                        <p class="text-[10px] text-gray-400 mt-1">Select accepted payment methods for this product and upload your QR codes.</p>
                    </div>
                    <a href="{{ route('seller.profile') }}" target="_blank" class="text-[10px] font-bold text-[#C0420A] hover:underline flex items-center gap-1 shrink-0">
                        Profile Payment Settings
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                    </a>
                </div>

                {{-- GCash --}}
                <div class="p-5 bg-gray-50/50 border border-gray-100 rounded-2xl space-y-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                            <span class="text-[11px] font-black uppercase tracking-widest text-[#0060AA]">GCash Method</span>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="product_is_gcash_available" value="1" class="sr-only peer" {{ old('product_is_gcash_available', true) ? 'checked' : '' }}>
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-0.5 after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                            <span class="ml-2 text-[10px] font-bold text-gray-500 uppercase tracking-widest peer-checked:text-blue-600">Available</span>
                        </label>
                    </div>

                    <div class="space-y-2">
                        <label class="text-[9px] font-black uppercase tracking-widest text-gray-400">GCash Number</label>
                        <input type="text" name="gcashNumber" value="{{ old('gcashNumber', auth()->user()->gcashNumber) }}" placeholder="e.g. 0917 123 4567" class="w-full px-4 py-3 bg-white border border-gray-200 rounded-xl text-xs font-bold text-gray-800 outline-none focus:border-blue-500 transition-all">
                    </div>

                    <div class="space-y-2">
                        <label class="text-[9px] font-black uppercase tracking-widest text-gray-400">GCash QR Code</label>
                        <div class="relative group" x-data="{ qrPreview: '{{ auth()->user()->gcashQrCode ? asset('storage/' . auth()->user()->gcashQrCode) : '' }}' }">
                            <input type="file" name="gcashQrCode" accept="image/*" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10" @change="
                                const file = $event.target.files[0];
                                if (file) {
                                    const reader = new FileReader();
                                    reader.onload = (e) => { qrPreview = e.target.result; };
                                    reader.readAsDataURL(file);
                                }
                            ">
                            <div class="border-2 border-dashed border-gray-200 bg-white rounded-2xl p-4 flex flex-col items-center justify-center text-center hover:border-blue-500 hover:bg-blue-50/10 transition-all min-h-25">
                                <template x-if="qrPreview">
                                    <div class="relative w-24 h-24">
                                        <img :src="qrPreview" class="w-full h-full object-contain rounded-lg">
                                        <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 flex items-center justify-center rounded-lg transition-opacity">
                                            <span class="text-[8px] text-white font-bold uppercase tracking-widest">Change QR</span>
                                        </div>
                                    </div>
                                </template>
                                <template x-if="!qrPreview">
                                    <div class="space-y-1">
                                        <svg class="w-6 h-6 text-gray-300 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block">Upload GCash QR Code</span>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Maya --}}
                <div class="p-5 bg-gray-50/50 border border-gray-100 rounded-2xl space-y-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-green-500"></span>
                            <span class="text-[11px] font-black uppercase tracking-widest text-[#00B050]">Maya Method</span>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="product_is_maya_available" value="1" class="sr-only peer" {{ old('product_is_maya_available', false) ? 'checked' : '' }}>
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-0.5 after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-green-600"></div>
                            <span class="ml-2 text-[10px] font-bold text-gray-500 uppercase tracking-widest peer-checked:text-green-600">Available</span>
                        </label>
                    </div>

                    <div class="space-y-2">
                        <label class="text-[9px] font-black uppercase tracking-widest text-gray-400">Maya Number</label>
                        <input type="text" name="mayaNumber" value="{{ old('mayaNumber', auth()->user()->mayaNumber) }}" placeholder="e.g. 0917 123 4567" class="w-full px-4 py-3 bg-white border border-gray-200 rounded-xl text-xs font-bold text-gray-800 outline-none focus:border-green-500 transition-all">
                    </div>

                    <div class="space-y-2">
                        <label class="text-[9px] font-black uppercase tracking-widest text-gray-400">Maya QR Code</label>
                        <div class="relative group" x-data="{ qrPreview: '{{ auth()->user()->mayaQrCode ? asset('storage/' . auth()->user()->mayaQrCode) : '' }}' }">
                            <input type="file" name="mayaQrCode" accept="image/*" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10" @change="
                                const file = $event.target.files[0];
                                if (file) {
                                    const reader = new FileReader();
                                    reader.onload = (e) => { qrPreview = e.target.result; };
                                    reader.readAsDataURL(file);
                                }
                            ">
                            <div class="border-2 border-dashed border-gray-200 bg-white rounded-2xl p-4 flex flex-col items-center justify-center text-center hover:border-green-500 hover:bg-green-50/10 transition-all min-h-25">
                                <template x-if="qrPreview">
                                    <div class="relative w-24 h-24">
                                        <img :src="qrPreview" class="w-full h-full object-contain rounded-lg">
                                        <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 flex items-center justify-center rounded-lg transition-opacity">
                                            <span class="text-[8px] text-white font-bold uppercase tracking-widest">Change QR</span>
                                        </div>
                                    </div>
                                </template>
                                <template x-if="!qrPreview">
                                    <div class="space-y-1">
                                        <svg class="w-6 h-6 text-gray-300 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block">Upload Maya QR Code</span>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white p-8 rounded-3xl border border-gray-100 shadow-sm space-y-6">
                <h3 class="text-sm font-bold text-black uppercase tracking-widest">Listing Status</h3>
                <div class="p-4 rounded-2xl bg-amber-50 border border-amber-100">
                    <p class="text-[10px] text-amber-800 leading-relaxed font-bold italic uppercase tracking-wider">
                        All new listings are reviewed by administrators to ensure heritage quality standards before appearing in the shop.
                    </p>
                </div>
                <button type="submit" class="w-full py-4 sm:py-5 bg-black text-white rounded-2xl font-bold uppercase tracking-[0.2em] shadow-xl hover:bg-[#C0420A] transition-all">
                    Submit Listing
                </button>
            </div>
        </div>

        {{-- Mobile Sticky Action Bar --}}
        <div class="lg:hidden fixed bottom-16 inset-x-0 bg-white/95 backdrop-blur-md border-t border-gray-200 p-3 z-30 shadow-2xl flex items-center justify-between gap-3">
            <div class="text-[10px] font-bold text-gray-500 uppercase tracking-widest pl-2">
                <span>New Heritage Listing</span>
            </div>
            <button type="submit" class="px-6 py-3 bg-[#C0420A] text-white rounded-xl text-xs font-black uppercase tracking-widest shadow-md hover:bg-black transition-all">
                Submit Listing
            </button>
        </div>
    </form>
</div>

<script>
function previewImages(input) {
    const grid = document.getElementById('image-preview-grid');
    const badge = document.getElementById('img-count-badge');
    const dropLabel = document.getElementById('dropZone').querySelector('div:last-of-type div:first-child');
    grid.innerHTML = '';

    if (input.files && input.files.length > 0) {
        grid.classList.remove('hidden');
        grid.classList.add('grid');
        badge.classList.remove('hidden');
        badge.textContent = input.files.length + ' photo' + (input.files.length !== 1 ? 's' : '');
        if (dropLabel) dropLabel.textContent = 'Change Photos';

        Array.from(input.files).forEach((file, idx) => {
            const reader = new FileReader();
            reader.onload = function(e) {
                const card = document.createElement('div');
                card.className = 'relative group rounded-2xl overflow-hidden border border-gray-100 bg-gray-50 shadow-sm';
                card.style.aspectRatio = '3/4';
                card.innerHTML = `
                    <img src="${e.target.result}" class="w-full h-full object-cover object-top transition-transform duration-300 group-hover:scale-105">
                    <div class="absolute inset-0 bg-gradient-to-t from-black/40 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity flex items-end p-2">
                        <span class="text-[8px] font-black text-white uppercase tracking-widest">${file.name.length > 16 ? file.name.substring(0, 14) + '…' : file.name}</span>
                    </div>
                    <div class="absolute top-2 left-2 w-5 h-5 bg-black/60 rounded-full flex items-center justify-center text-[8px] font-black text-white">${idx + 1}</div>
                `;
                grid.appendChild(card);
            };
            reader.readAsDataURL(file);
        });
    } else {
        grid.classList.add('hidden');
        grid.classList.remove('grid');
        badge.classList.add('hidden');
        if (dropLabel) dropLabel.textContent = 'Click to Upload Photos';
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
    } else {
        preview.classList.add('hidden');
    }
}

function validateProductForm(e, isEdit = false) {
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

    // 6. Product Imagery
    if (!isEdit) {
        const imgInput = document.getElementById('imageUploadInput');
        if (!imgInput || !imgInput.files || imgInput.files.length === 0) {
            errors.push('Please upload at least one product image.');
            const dropZone = document.getElementById('dropZone');
            if (dropZone) dropZone.classList.add('border-red-500');
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

// Also update preview when price changes
document.addEventListener('DOMContentLoaded', function() {
    const priceInput = document.querySelector('input[name="price"]');
    if (priceInput) {
        priceInput.addEventListener('input', updateDiscountPreview);
    }
});
</script>
@endsection
