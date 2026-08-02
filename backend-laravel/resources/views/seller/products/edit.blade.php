@extends('layouts.seller')

@section('content')
<div class="max-w-[1400px] mx-auto" x-data="{ deleteModal: false, deleteProductId: null, deleteProductName: '' }">
    <div class="mb-10">
        <a href="{{ route('seller.products.index') }}" class="inline-flex items-center gap-2 text-[10px] font-bold text-gray-400 uppercase tracking-widest hover:text-[#C0420A] transition-colors mb-4">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            Back to catalogue
        </a>
        <h1 class="font-serif text-3xl font-bold text-black uppercase">Edit <span class="text-[#C0420A] italic lowercase">heritage piece</span></h1>
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

    <form action="{{ route('seller.products.update', $product->id) }}" method="POST" enctype="multipart/form-data" class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        @csrf
        @method('PUT')

        {{-- Left: Main Details --}}
        <div class="lg:col-span-2 space-y-8">
            <div class="bg-white p-8 rounded-3xl border border-gray-100 shadow-sm space-y-8">
                <div class="space-y-4">
                    <label class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Product Name</label>
                    <input type="text" name="name" required value="{{ old('name', $product->name) }}"
                        placeholder="e.g. Pina-Silk Formal Barong Tagalog"
                        class="w-full px-6 py-4 bg-gray-50/50 border border-gray-100 rounded-2xl outline-none focus:border-[#C0420A] transition-all font-medium text-lg">
                </div>

                <div class="space-y-4">
                    <label class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Artisan Description</label>
                    <textarea name="description" required rows="6"
                        placeholder="Describe the craftsmanship, materials, and the story..."
                        class="w-full px-6 py-4 bg-gray-50/50 border border-gray-100 rounded-2xl outline-none focus:border-[#C0420A] transition-all font-medium resize-none">{{ old('description', $product->description) }}</textarea>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div class="space-y-4">
                        <label class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Price (₱)</label>
                        <input type="number" name="price" required min="1" step="0.01"
                            value="{{ old('price', $product->price) }}"
                            class="w-full px-6 py-4 bg-gray-50/50 border border-gray-100 rounded-2xl outline-none focus:border-[#C0420A] transition-all font-bold text-xl">
                    </div>
                    <div class="space-y-4">
                        <label class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Total Stock</label>
                        <input type="number" name="stock" id="total_stock" min="0"
                            value="{{ old('stock', $product->stock) }}"
                            readonly tabindex="-1"
                            class="w-full px-6 py-4 bg-gray-100 border border-gray-100 rounded-2xl outline-none font-bold text-xl text-gray-400 cursor-not-allowed select-none">
                        <p class="text-[9px] text-gray-400 italic -mt-2">Auto-calculated from sizes below.</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div class="space-y-4">
                        <label class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Shipping Fee (₱)</label>
                        <input type="number" name="shippingFee" min="0" step="0.01" placeholder="0.00"
                            value="{{ old('shippingFee', $product->shippingFee ?? 0) }}"
                            class="w-full px-6 py-4 bg-gray-50/50 border border-gray-100 rounded-2xl outline-none focus:border-[#C0420A] transition-all font-bold text-xl">
                        <p class="text-[9px] text-gray-400 italic -mt-2">Enter 0 for free shipping.</p>
                    </div>
                    <div class="space-y-4">
                        <label class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Est. Shipping Days</label>
                        <input type="number" name="shippingDays" min="1" step="1" placeholder="e.g. 5"
                            value="{{ old('shippingDays', $product->shippingDays ?? 5) }}"
                            class="w-full px-6 py-4 bg-gray-50/50 border border-gray-100 rounded-2xl outline-none focus:border-[#C0420A] transition-all font-bold text-xl">
                    </div>
                </div>

                <div class="space-y-6 pt-6 border-t border-gray-100">
                    <h3 class="text-sm font-bold text-black uppercase tracking-widest">Heritage Sizing & Stock</h3>
                    <p class="text-[10px] text-gray-400">Select sizes and assign stock for each size. The overall stock will update automatically.</p>
                    @php
                        $currentSizes = is_array($product->sizes) ? $product->sizes : (json_decode($product->sizes ?? '[]', true) ?? []);
                        $currentSizeStocks = is_array($product->size_stocks) ? $product->size_stocks : (json_decode($product->size_stocks ?? '[]', true) ?? []);
                    @endphp
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                        @foreach(['S', 'M', 'L', 'XL', 'XXL', 'Custom'] as $size)
                            @php
                                $hasSize = in_array($size, $currentSizes);
                                $sizeStock = $currentSizeStocks[$size] ?? 0;
                            @endphp
                            <div class="p-4 border border-gray-100 bg-gray-50/50 rounded-2xl flex flex-col justify-between gap-3">
                                <label class="flex items-center gap-2 cursor-pointer font-bold text-xs text-gray-600">
                                    <input type="checkbox" name="sizes[]" value="{{ $size }}" 
                                        class="rounded text-[#C0420A] focus:ring-[#C0420A] w-4 h-4 size-checkbox"
                                        {{ $hasSize ? 'checked' : '' }}
                                        onchange="toggleSizeStock(this, '{{ $size }}')">
                                    <span>Size {{ $size }}</span>
                                </label>
                                <input type="number" name="size_stocks[{{ $size }}]" id="stock_{{ $size }}" 
                                    value="{{ old('size_stocks.'.$size, $sizeStock) }}" min="0" 
                                    {{ $hasSize ? '' : 'disabled' }}
                                    oninput="calculateTotalStock()"
                                    class="w-full px-4 py-2.5 bg-white border border-gray-100 rounded-xl outline-none text-xs font-bold text-center size-stock-input">
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="space-y-4 pt-6 border-t border-gray-100">
                    <label class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Product Category</label>
                    <select name="CategoryId" id="categorySelect" required
                        class="w-full px-6 py-4 bg-gray-50/50 border border-gray-100 rounded-2xl outline-none focus:border-[#C0420A] transition-all font-bold text-sm appearance-none">
                        <option value="" disabled>Select a category</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" data-name="{{ strtolower($category->name) }}" {{ $product->CategoryId == $category->id ? 'selected' : '' }}>
                                {{ $category->name }}
                            </option>
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
                        <input type="hidden" name="is_on_sale" id="isOnSaleInput" value="{{ $product->is_on_sale ? '1' : '0' }}">

                        <div class="flex items-center justify-between p-4 bg-white rounded-xl border border-[#C0420A]/20">
                            <div>
                                <div class="text-xs font-black text-gray-700 uppercase tracking-widest">Mark as Lumban Special Sale</div>
                                <div class="text-[9px] text-gray-400 mt-0.5">Products will display a "Lumban Special" badge with discount</div>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" id="discountToggle" class="sr-only peer"
                                    {{ $product->is_on_sale ? 'checked' : '' }}
                                    onchange="toggleDiscount(this)">
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[#C0420A]"></div>
                                <span class="ml-2 text-[10px] font-bold text-gray-500 uppercase tracking-widest peer-checked:text-[#C0420A]">On Sale</span>
                            </label>
                        </div>

                        <div id="discountFields" class="{{ $product->is_on_sale ? '' : 'hidden' }} space-y-4">
                            <div class="space-y-2">
                                <label class="text-[10px] font-black uppercase tracking-widest text-gray-400">Discount Percentage (%)</label>
                                <div class="relative">
                                    <input type="number" name="discount_percentage" id="discountPercentage"
                                        min="1" max="99" step="1" placeholder="e.g. 20"
                                        value="{{ $product->discount_percentage ? (int)$product->discount_percentage : '' }}"
                                        class="w-full px-6 py-4 bg-white border border-[#C0420A]/30 rounded-2xl outline-none focus:border-[#C0420A] transition-all font-black text-xl text-[#C0420A]"
                                        oninput="updateDiscountPreview()">
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
                                <input type="radio" name="target_group" value="{{ $group }}" class="hidden peer"
                                    {{ old('target_group', $product->target_group) == $group ? 'checked' : '' }}>
                                <div class="w-full py-3 rounded-xl border-2 border-gray-100 bg-gray-50/50 text-xs font-black text-gray-400 text-center uppercase tracking-widest peer-checked:border-[#C0420A] peer-checked:bg-[#C0420A]/5 peer-checked:text-[#C0420A] peer-checked:shadow-md peer-checked:shadow-[#C0420A]/10 hover:border-gray-300 transition-all">
                                    {{ $group }}
                                </div>
                            </label>
                        @endforeach
                    </div>
                    <p class="text-[9px] text-gray-400 italic">Selecting a target group makes this product appear when customers filter by Men, Women, or Kids.</p>
                </div>
            </div>

            {{-- Existing Images --}}
            @php
                $images = is_array($product->image) ? $product->image : (json_decode($product->image ?? '[]', true) ?? []);
                if (is_string($product->image) && !str_starts_with($product->image, '[')) {
                    $images = [$product->image];
                }
            @endphp
            @if(count($images) > 0)
            <div class="bg-white p-8 rounded-3xl border border-gray-100 shadow-sm space-y-6">
                <h3 class="text-sm font-bold text-black uppercase tracking-widest">Current Images</h3>
                <div class="grid grid-cols-3 md:grid-cols-4 gap-4">
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
                        <div class="relative group aspect-3/4 rounded-xl overflow-hidden border border-gray-100">
                            <img src="{{ $imgSrc }}" class="w-full h-full object-cover">
                            <label class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 flex items-center justify-center cursor-pointer transition-opacity">
                                <input type="checkbox" name="remove_images[]" value="{{ $img }}" class="hidden peer">
                                <div class="px-3 py-1.5 rounded-lg bg-red-500 text-white text-[9px] font-black uppercase tracking-widest peer-checked:bg-red-700">
                                    Remove
                                </div>
                            </label>
                        </div>
                    @endforeach
                </div>
                <p class="text-[10px] text-gray-400">Hover over an image and tick "Remove" to delete it when you save.</p>
            </div>
            @endif
        </div>

        <div class="space-y-8">
            <div class="bg-white p-8 rounded-3xl border border-gray-100 shadow-sm space-y-5">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-bold text-black uppercase tracking-widest">Add New Images</h3>
                    <span id="img-count-badge" class="hidden text-[9px] font-black uppercase tracking-widest px-2.5 py-1 bg-[#C0420A]/10 text-[#C0420A] rounded-full">0 photos</span>
                </div>

                {{-- Drop Zone --}}
                <label for="imageUploadInput"
                    id="dropZone"
                    class="flex flex-col items-center justify-center gap-3 w-full min-h-[160px] rounded-2xl border-2 border-dashed border-gray-200 bg-gray-50 hover:bg-white hover:border-[#C0420A] transition-all cursor-pointer group px-6 py-8 text-center">
                    <div class="w-12 h-12 rounded-2xl bg-gray-100 group-hover:bg-[#C0420A]/10 flex items-center justify-center transition-colors">
                        <svg class="w-6 h-6 text-gray-300 group-hover:text-[#C0420A] transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <div>
                        <div class="text-xs font-black text-gray-600 group-hover:text-[#C0420A] uppercase tracking-widest transition-colors">Click to Add Photos</div>
                        <p class="text-[9px] text-gray-400 mt-1">Optional &mdash; only if replacing or adding new shots</p>
                    </div>
                    <input type="file" id="imageUploadInput" name="images[]" multiple class="hidden" onchange="previewImages(this)">
                </label>

                {{-- New Upload Preview Grid --}}
                <div id="image-preview-grid" class="hidden grid-cols-3 gap-3">
                    {{-- JS-populated --}}
                </div>
            </div>

            {{-- Payment Method Configuration --}}
            <div class="bg-white p-8 rounded-3xl border border-gray-100 shadow-sm space-y-6">
                <div>
                    <h3 class="text-sm font-bold text-black uppercase tracking-widest flex items-center gap-2">
                        <svg class="w-4 h-4 text-[#C0420A]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>
                        Payment Methods
                    </h3>
                    <p class="text-[10px] text-gray-400 mt-1">Select payment methods accepted for this product. Numbers and QR codes are managed in your profile.</p>
                </div>

                {{-- GCash --}}
                <div class="p-5 bg-gray-50/50 border border-gray-100 rounded-2xl space-y-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                            <span class="text-[11px] font-black uppercase tracking-widest text-[#0060AA]">GCash Method</span>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="product_is_gcash_available" value="1" class="sr-only peer"
                                {{ old('product_is_gcash_available', $product->is_gcash_available) ? 'checked' : '' }}>
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                            <span class="ml-2 text-[10px] font-bold text-gray-500 uppercase tracking-widest peer-checked:text-blue-600">Available</span>
                        </label>
                    </div>

                    <div class="space-y-2">
                        <label class="text-[9px] font-black uppercase tracking-widest text-gray-400">GCash Number</label>
                        <div class="px-4 py-3 bg-gray-100 border border-gray-200 rounded-xl text-xs font-bold text-gray-500">
                            {{ auth()->user()->gcashNumber ?? 'Not set in profile' }}
                        </div>
                    </div>

                    <div class="space-y-2">
                        <label class="text-[9px] font-black uppercase tracking-widest text-gray-400">GCash QR Code</label>
                        <div class="border-2 border-dashed border-gray-200 bg-gray-100 rounded-2xl p-4 flex flex-col items-center justify-center text-center min-h-[100px]">
                            @if(auth()->user()->gcashQrCode)
                                <img src="{{ asset('storage/' . auth()->user()->gcashQrCode) }}" class="w-20 h-20 object-contain rounded-lg">
                            @else
                                <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block">No QR Uploaded in Profile</span>
                            @endif
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
                            <input type="checkbox" name="product_is_maya_available" value="1" class="sr-only peer"
                                {{ old('product_is_maya_available', $product->is_maya_available) ? 'checked' : '' }}>
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-green-600"></div>
                            <span class="ml-2 text-[10px] font-bold text-gray-500 uppercase tracking-widest peer-checked:text-green-600">Available</span>
                        </label>
                    </div>

                    <div class="space-y-2">
                        <label class="text-[9px] font-black uppercase tracking-widest text-gray-400">Maya Number</label>
                        <div class="px-4 py-3 bg-gray-100 border border-gray-200 rounded-xl text-xs font-bold text-gray-500">
                            {{ auth()->user()->mayaNumber ?? 'Not set in profile' }}
                        </div>
                    </div>

                    <div class="space-y-2">
                        <label class="text-[9px] font-black uppercase tracking-widest text-gray-400">Maya QR Code</label>
                        <div class="border-2 border-dashed border-gray-200 bg-gray-100 rounded-2xl p-4 flex flex-col items-center justify-center text-center min-h-[100px]">
                            @if(auth()->user()->mayaQrCode)
                                <img src="{{ asset('storage/' . auth()->user()->mayaQrCode) }}" class="w-20 h-20 object-contain rounded-lg">
                            @else
                                <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block">No QR Uploaded in Profile</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white p-8 rounded-3xl border border-gray-100 shadow-sm space-y-4">
                <h3 class="text-sm font-bold text-black uppercase tracking-widest">Listing Status</h3>
                <div class="flex items-center gap-3 p-4 rounded-2xl bg-gray-50 border border-gray-100">
                    <div class="w-2.5 h-2.5 rounded-full {{ $product->status === 'approved' ? 'bg-green-500' : 'bg-amber-500' }}"></div>
                    <span class="text-xs font-black uppercase tracking-widest {{ $product->status === 'approved' ? 'text-green-600' : 'text-amber-600' }}">
                        {{ ucfirst($product->status) }}
                    </span>
                </div>
                @if($product->status !== 'approved')
                <p class="text-[10px] text-gray-400 italic">Editing this listing will re-submit it for admin review.</p>
                @endif

                <button type="submit"
                    class="w-full py-5 bg-black text-white rounded-2xl font-bold uppercase tracking-[0.2em] shadow-xl hover:bg-[#C0420A] transition-all">
                    Save Changes
                </button>

                <button type="button"
                    @click="deleteModal = true; deleteProductId = '{{ $product->id }}'; deleteProductName = '{{ addslashes($product->name) }}'"
                    class="w-full py-4 border-2 border-red-100 text-red-400 rounded-2xl font-bold text-[10px] uppercase tracking-widest hover:bg-red-50 hover:border-red-200 hover:text-red-600 transition-all">
                    Delete Listing
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
                <p class="text-xs text-gray-500" x-text="'\"' + deleteProductName + '\" will be permanently removed.'"></p>
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
            reader.onload = e => {
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
        if (dropLabel) dropLabel.textContent = 'Click to Add Photos';
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
    } else {
        preview.classList.add('hidden');
    }
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
