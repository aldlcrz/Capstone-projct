@extends('layouts.seller')

@section('content')
<div class="max-w-4xl mx-auto pb-36 sm:pb-28 lg:pb-16 px-3 sm:px-6" x-data="addProductManager()">
    {{-- Top Header & Navigation --}}
    <div class="mb-6 sm:mb-8 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <a href="{{ route('seller.products.index') }}" class="inline-flex items-center gap-2 text-[11px] font-bold text-[#78716C] uppercase tracking-widest hover:text-[#18181B] transition-colors mb-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                Back to catalogue
            </a>
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-[#FEF9EC] border border-[#F3E8CE] flex items-center justify-center text-[#9A6B1F] shrink-0 shadow-2xs">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                    </svg>
                </div>
                <div>
                    <h1 class="font-serif text-2xl sm:text-3xl font-bold text-[#18181B] tracking-tight">
                        New Heritage Piece
                    </h1>
                    <p class="text-xs text-[#78716C] font-medium mt-0.5">List a new handcrafted Lumban creation for discerning buyers</p>
                </div>
            </div>
        </div>

        {{-- Step Indicator Badge (Artisan Pill Style) --}}
        <div class="flex items-center gap-2">
            <span class="text-xs font-bold tracking-wide px-4 py-1.5 rounded-full border transition-all flex items-center gap-2 shadow-2xs"
                  :class="step === 1 ? 'bg-[#FEF9EC] text-[#9A6B1F] border-[#F3E8CE]' : 'bg-[#18181B] text-white border-[#18181B]'">
                <span class="w-2 h-2 rounded-full" :class="step === 1 ? 'bg-[#C5A059]' : 'bg-emerald-400'"></span>
                <span x-text="step === 1 ? 'Step 1: Media & Core Info' : 'Step 2: Specifications'"></span>
            </span>
        </div>
    </div>

    {{-- Error Flash Notification --}}
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

    {{-- Main Product Form --}}
    <form action="{{ route('seller.products.store') }}" method="POST" id="productForm" enctype="multipart/form-data" onsubmit="return validateProductForm(event, false)" class="space-y-6">
        @csrf
        <input type="hidden" name="action" id="formActionInput" value="publish">

        {{-- ========================================================================= --}}
        {{-- PHASE 1: IMAGE FIRST & CORE IDENTIFICATION (Heritage Artisan Theme)       --}}
        {{-- ========================================================================= --}}
        <div class="bg-white p-6 sm:p-8 rounded-3xl border border-[#EFE8DA] shadow-[0_12px_40px_-8px_rgba(0,0,0,0.06),0_4px_12px_-2px_rgba(0,0,0,0.03)] space-y-6">
            
            {{-- 1. Product Media & Variants (Unified: Variant 1 is Cover Photo & Product Name) --}}
            <div class="space-y-4">
                <div class="flex items-center justify-between pb-1">
                    <div class="space-y-0.5">
                        <h2 class="font-serif text-lg font-bold text-[#18181B] flex items-center gap-2">
                            <span>1. Product Media & Variants</span>
                            <span class="text-[#C5A059]">*</span>
                        </h2>
                        <p class="text-xs text-[#78716C]">
                            Variant 1 represents your main product style, cover photo, and product name.
                        </p>
                    </div>
                    <span class="text-[11px] font-bold text-[#9A6B1F] bg-[#FEF9EC] border border-[#F3E8CE] px-3 py-1 rounded-full shadow-2xs" x-text="variants.length + ' style(s)'"></span>
                </div>

                {{-- Variant 1: Main Product Style & Cover Photo (Unified with Product Name) --}}
                <div class="p-5 sm:p-6 bg-[#FFFDF9] border border-[#EFE8DA] rounded-2xl space-y-4 transition-all shadow-2xs hover:border-[#C5A059]" id="variant_card_0">
                    <div class="flex items-center justify-between pb-2 border-b border-[#EFE8DA]">
                        <div class="flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-[#C5A059]"></span>
                            <span class="font-serif text-sm font-bold text-[#18181B] tracking-tight">
                                Variant 1 (Main Style / Cover)
                            </span>
                            <span class="px-2.5 py-0.5 rounded-full bg-[#FEF9EC] border border-[#F3E8CE] text-[#9A6B1F] text-[10px] font-bold uppercase tracking-wider">Cover Image</span>
                        </div>
                        <span class="text-xs text-[#78716C] font-medium hidden sm:inline">Primary Product Listing</span>
                    </div>

                    {{-- Hidden inputs for Variant 1 mapping --}}
                    <input type="hidden" name="variant_indexes[]" value="0">
                    <input type="hidden" name="variant_names[0]" :value="productName || 'Original Style'">

                    {{-- 1. Product Name (English) --}}
                    <div class="space-y-1.5">
                        <div class="flex items-center justify-between">
                            <label class="text-xs font-bold uppercase tracking-wider text-[#18181B]">
                                Product Name (English) <span class="text-[#C5A059]">*</span>
                                <span class="text-[10px] text-[#A8A29E] font-normal" x-text="'(' + (productName ? productName.length : 0) + '/100)'"></span>
                            </label>
                        </div>

                        <div class="relative flex items-center">
                            <input type="text" 
                                   name="name" 
                                   id="productNameInput"
                                   required 
                                   maxlength="100"
                                   x-model="productName"
                                   @input="calculateFillRate()"
                                   placeholder="e.g. Hand-Woven Piña Barong Tagalog with Calado Embroidery"
                                   class="w-full px-4 py-3.5 bg-white border border-[#E5DECE] rounded-xl outline-none focus:border-[#C5A059] focus:ring-2 focus:ring-[#C5A059]/15 transition-all font-semibold text-sm text-[#18181B] placeholder:text-[#A8A29E] placeholder:font-normal pr-10 shadow-2xs">
                            
                            {{-- Clear Button (X) --}}
                            <button type="button" 
                                    x-show="productName && productName.length > 0"
                                    @click="productName = ''; calculateFillRate();"
                                    class="absolute right-3 text-[#A8A29E] hover:text-[#18181B] transition-colors cursor-pointer">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                            </button>
                        </div>
                    </div>

                    {{-- 2. Cover Photo Upload --}}
                    <div class="space-y-1.5 pt-1">
                        <label class="text-xs font-bold uppercase tracking-wider text-[#18181B] block">
                            Variant 1 Photo (Cover Image) <span class="text-[#C5A059]">*</span>
                        </label>
                        <div class="flex flex-col sm:flex-row items-start sm:items-center gap-4 sm:gap-5">
                            <div class="relative shrink-0" style="width: 140px; height: 140px;">
                                <label for="variant_file_0"
                                       id="variant_upload_box_0"
                                       style="width: 140px; height: 140px;"
                                       class="rounded-2xl border-2 border-dashed border-[#E5DECE] hover:border-[#C5A059] bg-[#FAF8F5] hover:bg-white flex flex-col items-center justify-center cursor-pointer overflow-hidden transition-all relative group/img shadow-2xs select-none">
                                    
                                    <template x-if="variants[0].imagePreview">
                                        <div class="relative w-full h-full">
                                            <img :src="variants[0].imagePreview" class="w-full h-full object-cover rounded-xl">
                                            <div class="absolute inset-0 bg-black/40 opacity-0 group-hover/img:opacity-100 transition-opacity flex flex-col items-center justify-center text-white text-[10px] font-bold uppercase tracking-wider gap-1">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                                <span>Change</span>
                                            </div>
                                            <div class="absolute bottom-1.5 inset-x-1.5 bg-black/60 backdrop-blur-xs py-0.5 rounded text-center text-[9px] font-bold text-white uppercase tracking-wider">
                                                Cover Image
                                            </div>
                                        </div>
                                    </template>

                                    <template x-if="!variants[0].imagePreview">
                                        <div class="flex flex-col items-center justify-center text-center p-3">
                                            <div class="w-10 h-10 rounded-full bg-[#FEF9EC] border border-[#F3E8CE] flex items-center justify-center text-[#9A6B1F] mb-1.5 group-hover/img:scale-110 transition-transform">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                            </div>
                                            <span class="text-xs font-bold text-[#9A6B1F]">+ Upload Photo</span>
                                            <span class="text-[10px] text-[#A8A29E] font-medium mt-0.5">JPEG, PNG, WEBP</span>
                                        </div>
                                    </template>

                                    <input type="file" 
                                           id="variant_file_0"
                                           name="variant_image_0" 
                                           accept="image/jpeg,image/png,image/webp,image/jpg" 
                                           class="hidden" 
                                           @change="handleVariantFile($event, 0)">
                                </label>

                                <button type="button" 
                                        x-show="variants[0].imagePreview"
                                        @click="removeVariantImage(0)" 
                                        class="absolute -top-2 -right-2 w-6 h-6 bg-red-600 hover:bg-red-700 text-white rounded-full flex items-center justify-center text-[10px] font-black shadow-md transition-all cursor-pointer"
                                        title="Remove photo">
                                    ✕
                                </button>
                            </div>

                            <div class="flex-1 space-y-1.5 text-xs text-[#78716C]">
                                <h4 class="font-serif font-bold text-[#18181B] text-sm">Primary Product Appearance</h4>
                                <p class="text-[11px] text-[#78716C] leading-relaxed">
                                    This photo will be showcased as the main thumbnail across the store, search, and catalogue.
                                </p>
                                <p class="text-[11px] text-[#9A6B1F] font-semibold flex items-center gap-1">
                                    <span>✦</span> Have other colors, fabrics, or sleeve styles? Click "+ Add Another Variant" below.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Additional Variants List (Variant 2, 3, etc.) --}}
                <div class="space-y-3">
                    <template x-for="(variant, index) in variants" :key="variant.id">
                        <div x-show="index > 0" class="p-4 bg-white border border-[#EFE8DA] hover:border-[#C5A059] rounded-2xl space-y-3 transition-all shadow-2xs">
                            <div class="flex items-center justify-between pb-2 border-b border-[#EFE8DA]/60">
                                <div class="flex items-center gap-2">
                                    <span class="w-1.5 h-1.5 rounded-full bg-[#C5A059]"></span>
                                    <span class="font-serif text-sm font-bold text-[#18181B]" x-text="'Variant ' + (index + 1)"></span>
                                    <span class="px-2.5 py-0.5 rounded-full bg-[#FAF8F5] border border-[#E5DECE] text-[#78716C] text-[10px] font-bold uppercase tracking-wider">Style Option</span>
                                </div>
                                <button type="button" 
                                        @click="removeVariantRow(index)" 
                                        class="text-xs font-semibold text-red-600 hover:text-red-700 flex items-center gap-1 cursor-pointer">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    <span>Remove</span>
                                </button>
                            </div>

                            {{-- Hidden input mapping for PHP --}}
                            <input type="hidden" name="variant_indexes[]" :value="index">

                            <div class="flex items-center gap-3.5">
                                {{-- Variant Image Box --}}
                                <div class="relative shrink-0" style="width: 80px; height: 80px;">
                                    <label :for="'variant_file_' + index"
                                           style="width: 80px; height: 80px;"
                                           class="rounded-2xl border-2 border-dashed border-[#E5DECE] hover:border-[#C5A059] bg-[#FAF8F5] hover:bg-white flex flex-col items-center justify-center cursor-pointer overflow-hidden transition-all relative group/img shadow-2xs select-none">
                                        <template x-if="variant.imagePreview">
                                            <div class="relative w-full h-full">
                                                <img :src="variant.imagePreview" class="w-full h-full object-cover rounded-xl">
                                                <div class="absolute inset-0 bg-black/30 opacity-0 group-hover/img:opacity-100 transition-opacity flex items-center justify-center text-white text-[9px] font-bold uppercase tracking-wider">
                                                    Change
                                                </div>
                                            </div>
                                        </template>
                                        <template x-if="!variant.imagePreview">
                                            <div class="flex flex-col items-center justify-center text-center p-1">
                                                <div class="w-6 h-6 rounded-full bg-[#FEF9EC] border border-[#F3E8CE] flex items-center justify-center text-[#9A6B1F] mb-0.5 group-hover/img:scale-110 transition-transform">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                                </div>
                                                <span class="text-[9px] font-extrabold text-[#9A6B1F] uppercase tracking-wider">+ Photo</span>
                                            </div>
                                        </template>
                                        <input type="file" 
                                               :id="'variant_file_' + index" 
                                               :name="'variant_image_' + index" 
                                               accept="image/jpeg,image/png,image/webp,image/jpg" 
                                               class="hidden" 
                                               @change="handleVariantFile($event, index)">
                                    </label>
                                    <button type="button" 
                                            x-show="variant.imagePreview"
                                            @click="removeVariantImage(index)" 
                                            class="absolute -top-1.5 -right-1.5 w-5 h-5 bg-red-600 hover:bg-red-700 text-white rounded-full flex items-center justify-center text-[9px] font-black shadow-md transition-all cursor-pointer">
                                        ✕
                                    </button>
                                </div>

                                {{-- Variant Name Input --}}
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center justify-between mb-1">
                                        <label class="text-[10px] font-bold uppercase tracking-wider text-[#18181B]">
                                            Variant Name <span class="text-[#C5A059]">*</span>
                                        </label>
                                        <span class="text-[9px] text-[#A8A29E] font-medium hidden sm:inline">e.g. Color, embroidery, or style</span>
                                    </div>
                                    <input type="text" 
                                           :name="'variant_names[' + index + ']'" 
                                           x-model="variant.name" 
                                           placeholder="e.g. Emerald Green, Ivory Piña, Short Sleeve..." 
                                           class="w-full px-3.5 py-2.5 bg-[#FAF8F5] border border-[#E5DECE] rounded-xl text-xs font-bold text-[#18181B] outline-none focus:border-[#C5A059] focus:bg-white transition-all shadow-2xs">
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

                {{-- Add Another Variant Button --}}
                <div>
                    <button type="button" 
                            @click="addVariantRow()" 
                            class="w-full py-3.5 px-4 rounded-2xl border border-dashed border-[#E5DECE] hover:border-[#C5A059] bg-[#FAF8F5] hover:bg-[#FFFDF9] text-[#18181B] font-bold text-xs flex items-center justify-center gap-2 transition-all cursor-pointer shadow-2xs">
                        <span class="text-[#C5A059] text-base font-bold">+</span>
                        <span>Add Another Variant (Optional Style / Color)</span>
                    </button>
                </div>
            </div>

            {{-- Ornamental Heritage Divider (From Artisan Modal) --}}
            <div class="flex items-center justify-center my-6">
                <div class="h-px bg-[#EFE8DA] flex-1"></div>
                <span class="px-3.5 text-[#C5A059] text-xs">✦</span>
                <div class="h-px bg-[#EFE8DA] flex-1"></div>
            </div>

            {{-- 2. Target Tag & Dynamic Matching Category Selection --}}
            <div class="space-y-4">
                {{-- Step A: Who is this for? (Target Tag) --}}
                <div class="space-y-1.5">
                    <div class="flex items-center justify-between">
                        <h2 class="font-serif text-lg font-bold text-[#18181B] flex items-center gap-2">
                            <span>2. Who is this for? (Target Tag)</span>
                            <span class="text-[#C5A059]">*</span>
                        </h2>
                        <span class="text-xs font-bold transition-colors"
                              :class="targetGroup ? 'text-emerald-700' : 'text-[#C5A059]'"
                              x-text="targetGroup ? ('✓ ' + targetGroup + ' selected') : 'Select a tag'"></span>
                    </div>

                    <div id="target-group-container" class="grid grid-cols-3 gap-3">
                        @foreach(['Men', 'Women', 'Kids'] as $group)
                            @php
                                $emoji = match($group) {
                                    'Men' => '👔',
                                    'Women' => '👗',
                                    'Kids' => '🧸',
                                    default => '🏷️'
                                };
                            @endphp
                            <label class="cursor-pointer">
                                <input type="radio" 
                                       name="target_group" 
                                       value="{{ $group }}" 
                                       x-model="targetGroup" 
                                       @change="onTargetGroupChange('{{ $group }}')" 
                                       class="hidden peer target-group-radio">
                                <div class="w-full py-3.5 px-3 rounded-2xl border border-[#E5DECE] bg-[#FAF8F5] hover:bg-[#FFFDF9] text-xs font-bold text-[#44403C] text-center uppercase tracking-wider flex items-center justify-center gap-2 transition-all peer-checked:border-[#18181B] peer-checked:bg-[#18181B] peer-checked:text-white peer-checked:font-bold peer-checked:shadow-sm shadow-2xs">
                                    <span class="text-base">{{ $emoji }}</span>
                                    <span>{{ $group }}</span>
                                    <span x-show="targetGroup === '{{ $group }}'" class="w-4 h-4 rounded-full bg-[#C5A059] text-white flex items-center justify-center text-[9px] font-bold ml-1">✓</span>
                                </div>
                            </label>
                        @endforeach
                    </div>
                </div>

                {{-- Step B: Product Category matching selected tag --}}
                <div class="space-y-1.5 pt-2">
                    <div class="flex items-center justify-between">
                        <h3 class="font-serif text-sm font-bold text-[#18181B]">
                            Product Category <span x-show="targetGroup" x-text="'for ' + targetGroup"></span> <span class="text-[#C5A059]">*</span>
                        </h3>
                        <span class="text-xs font-bold transition-colors"
                              :class="selectedCategory ? 'text-emerald-700' : 'text-[#C5A059]'"
                              x-text="selectedCategory && selectedCategoryObj ? ('✓ ' + selectedCategoryObj.name) : (targetGroup ? 'Choose from options below' : 'Select tag above first')"></span>
                    </div>

                    {{-- Hidden CategoryId input for form submission --}}
                    <input type="hidden" name="CategoryId" id="categorySelect" :value="selectedCategory" required>

                    {{-- Prompt when NO tag is selected (Artisan Sand Style) --}}
                    <div x-show="!targetGroup" class="p-4 rounded-2xl bg-[#FAF3E0]/70 border border-[#ECDDC0] text-[#7C6A4F] text-xs font-medium flex items-center gap-2.5">
                        <span class="text-base shrink-0">👆</span>
                        <span>Please select who this product is for (<strong>Men</strong>, <strong>Women</strong>, or <strong>Kids</strong>) above to display matching categories.</span>
                    </div>

                    {{-- Category grid displayed when a tag is picked --}}
                    <div x-show="targetGroup" class="space-y-2" x-cloak>
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-2.5 max-h-60 overflow-y-auto p-3 bg-[#FAF8F5] rounded-2xl border border-[#EFE8DA] shadow-2xs" id="category-cards-container">
                            <template x-for="cat in filteredCategories" :key="cat.id">
                                <button type="button" 
                                        @click="selectCategory(cat)"
                                        :class="selectedCategory === cat.id 
                                            ? 'bg-[#FEF9EC] border-2 border-[#C5A059] text-[#9A6B1F] font-bold shadow-xs ring-2 ring-[#C5A059]/10' 
                                            : 'bg-white border border-[#EFE8DA] hover:border-[#C5A059] text-[#18181B] hover:bg-[#FFFDF9] font-semibold'"
                                        class="p-3.5 rounded-xl flex items-center justify-between text-left transition-all cursor-pointer group hover:scale-[1.01] active:scale-[0.99] text-xs">
                                    <span class="truncate" x-text="cat.name"></span>
                                    <span x-show="selectedCategory === cat.id" class="w-4 h-4 rounded-full bg-[#C5A059] text-white flex items-center justify-center text-[9px] shrink-0 font-bold ml-1.5">✓</span>
                                </button>
                            </template>

                            <template x-if="filteredCategories.length === 0">
                                <div class="col-span-full py-6 text-center text-xs text-[#78716C] font-medium">
                                    No categories found for this tag.
                                </div>
                            </template>
                        </div>

                        <p x-show="selectedCategory && selectedCategoryObj" class="text-xs text-[#78716C] font-medium px-1">
                            Selected Category: <strong class="text-[#18181B]" x-text="selectedCategoryObj.name"></strong>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Step 1 Primary CTA Button: Sleek Onyx Pill Button matching "View Shop →" --}}
        <div x-show="step === 1" class="pt-2 space-y-3">
            <button type="button" 
                    @click="goToStep2()"
                    :disabled="!isStep1Complete"
                    :class="isStep1Complete 
                        ? 'bg-[#18181B] hover:bg-[#27272A] active:scale-[0.99] text-white shadow-md hover:shadow-xl cursor-pointer' 
                        : 'bg-gray-200 text-gray-400 cursor-not-allowed opacity-75 shadow-none select-none'"
                    class="w-full py-4 px-8 rounded-full font-bold text-sm tracking-wide transition-all flex items-center justify-center gap-3 group">
                <span>Next: Complete Product Details</span>
                <span class="text-base font-bold transition-transform group-hover:translate-x-1">→</span>
            </button>

            {{-- Status prompt when fields are missing --}}
            <template x-if="!isStep1Complete">
                <p class="text-center text-xs text-[#78716C] font-medium flex items-center justify-center gap-1.5 pt-0.5">
                    <span class="text-[#C5A059]">✦</span>
                    <span>Please upload a photo, enter product name, choose a category, and select target tag to proceed.</span>
                </p>
            </template>

            {{-- Authentic Heritage Verification Banner (Direct from Artisan Modal) --}}
            <div class="bg-[#FAF3E0]/80 border border-[#ECDDC0] rounded-2xl p-4 sm:p-5 flex items-center justify-between relative overflow-hidden shadow-2xs mt-4">
                <div class="flex items-center gap-3.5 relative z-10">
                    <div class="w-9 h-9 rounded-full bg-[#EBD8B3]/70 text-[#8C6226] flex items-center justify-center font-bold text-sm shrink-0">
                        ✓
                    </div>
                    <div>
                        <h4 class="font-serif font-bold text-sm text-[#423118]">Handcrafted Heritage Guarantee</h4>
                        <p class="text-xs text-[#7C6A4F] mt-0.5">Every Lumban artisan listing is verified for genuine Filipino craftsmanship and authentic materials.</p>
                    </div>
                </div>
                <div class="hidden sm:block text-[#C5A059]/25 text-3xl font-serif select-none pointer-events-none pr-2">
                    ❖
                     {{-- ========================================================================= --}}
        {{-- PHASE 2: COMPLETE SPECIFICATIONS (Progressively Revealed after Step 1)     --}}
        {{-- ========================================================================= --}}
        <div x-show="step >= 2" x-collapse class="space-y-6">

            {{-- 1. Fill Rate & Listing Health Bar --}}
            <div class="bg-white p-5 sm:p-6 rounded-3xl border border-[#EFE8DA] shadow-[0_12px_40px_-8px_rgba(0,0,0,0.06),0_4px_12px_-2px_rgba(0,0,0,0.03)] flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <span class="font-serif text-sm font-bold text-[#18181B]">Listing Completeness</span>
                    <div class="w-36 sm:w-48 h-2.5 bg-[#FAF8F5] border border-[#E5DECE] rounded-full overflow-hidden">
                        <div class="h-full bg-linear-to-r from-[#C5A059] to-[#18181B] rounded-full transition-all duration-500" :style="'width: ' + fillRate + '%'"></div>
                    </div>
                    <span class="text-xs font-black text-[#9A6B1F]" x-text="fillRate + '%'"></span>
                    <span class="text-xs text-[#C5A059]">✦</span>
                </div>

                <div class="flex items-center gap-2">
                    <span class="text-xs text-[#78716C] font-medium">All essential artisan specifications</span>
                </div>
            </div>

            {{-- Hidden Input for Fabric Type --}}
            <input type="hidden" name="fabric_type" :value="fabricType || '100% Piña'">

            {{-- 1. Heritage Sizing & Inventory Matrix --}}
            <div id="sizing-section" class="bg-white p-6 sm:p-7 rounded-3xl border border-[#EFE8DA] shadow-2xs space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-[#EFE8DA]">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-full bg-[#FEF9EC] border border-[#F3E8CE] flex items-center justify-center text-[#9A6B1F] shrink-0 font-serif font-bold text-xs shadow-2xs">1</div>
                        <div>
                            <h3 class="font-serif text-sm sm:text-base font-bold text-[#18181B] tracking-tight">Heritage Sizing & Stock <span class="text-[#C5A059]">*</span></h3>
                            <p class="text-xs text-[#78716C] font-medium mt-0.5">Assign available inventory quantities per size</p>
                        </div>
                    </div>
                    <span class="text-[10px] font-bold text-[#9A6B1F] uppercase bg-[#FEF9EC] border border-[#F3E8CE] px-3 py-1 rounded-full">At least 1 size required</span>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-2.5 pt-1">
                    @foreach(['S', 'M', 'L', 'XL', 'XXL', 'Custom'] as $size)
                        <div class="p-3 bg-[#FAF8F5] border border-[#E5DECE] rounded-2xl space-y-2 text-center transition-all hover:bg-white hover:border-[#C5A059] shadow-2xs">
                            <label class="flex items-center justify-center gap-1.5 text-xs font-bold uppercase text-[#18181B] cursor-pointer select-none">
                                <input type="checkbox" 
                                       name="sizes[]" 
                                       value="{{ $size }}" 
                                       id="size_cb_{{ $size }}"
                                       class="rounded text-[#18181B] focus:ring-[#C5A059] w-3.5 h-3.5 size-checkbox"
                                       onchange="toggleSizeStock(this, '{{ $size }}'); calculateFillRate();">
                                <span>Size {{ $size }}</span>
                            </label>
                            <input type="number" 
                                   name="size_stocks[{{ $size }}]" 
                                   id="stock_{{ $size }}" 
                                   value="0" 
                                   min="0" 
                                   max="10000" 
                                   disabled
                                   oninput="if(parseInt(this.value) > 10000) this.value = 10000; calculateTotalStock(); calculateFillRate();"
                                   class="w-full px-2 py-1.5 bg-white border border-[#E5DECE] rounded-xl outline-none text-xs font-bold text-center size-stock-input text-[#18181B]">
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- 2. Pricing & Logistics Grid --}}
            <div class="bg-white p-6 sm:p-7 rounded-3xl border border-[#EFE8DA] shadow-2xs space-y-4">
                <div class="flex items-center gap-3 pb-3 border-b border-[#EFE8DA]">
                    <div class="w-8 h-8 rounded-full bg-[#FEF9EC] border border-[#F3E8CE] flex items-center justify-center text-[#9A6B1F] shrink-0 font-serif font-bold text-xs shadow-2xs">2</div>
                    <div>
                        <h3 class="font-serif text-sm sm:text-base font-bold text-[#18181B] tracking-tight">Price & Shipping Information</h3>
                        <p class="text-xs text-[#78716C] font-medium mt-0.5">Define fair artisan pricing and realistic delivery estimates</p>
                    </div>
                </div>

                <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 pt-1">
                    {{-- Price Input --}}
                    <div id="price-card" class="p-4 bg-[#FAF8F5] border border-[#E5DECE] rounded-2xl flex flex-col justify-between h-26 sm:h-28 transition-all hover:border-[#C5A059] shadow-2xs">
                        <label class="text-[10px] font-bold uppercase tracking-wider text-[#78716C]">Price (₱) <span class="text-[#C5A059]">*</span></label>
                        <input type="number" 
                               name="price" 
                               id="priceInput" 
                               required 
                               min="1" 
                               max="10000" 
                               step="0.01" 
                               placeholder="0.00"
                               x-model="price"
                               oninput="if(parseFloat(this.value) > 10000) this.value = 10000; updateDiscountPreview(); calculateFillRate();"
                               class="w-full bg-transparent font-sans text-xl font-bold text-[#18181B] outline-none border-b border-transparent focus:border-[#C5A059] transition-all">
                        <p class="text-[9px] text-[#A8A29E] font-medium">Item base price</p>
                    </div>

                    {{-- Total Stock (Auto) --}}
                    <div id="stock-card" class="p-4 bg-[#FAF8F5] border border-[#E5DECE] rounded-2xl flex flex-col justify-between h-26 sm:h-28 transition-all shadow-2xs">
                        <label class="text-[10px] font-bold uppercase tracking-wider text-[#78716C]">Total Stock <span class="text-[#C5A059]">*</span></label>
                        <input type="number" 
                               name="stock" 
                               id="total_stock" 
                               min="0" 
                               placeholder="0"
                               readonly 
                               tabindex="-1"
                               class="w-full bg-transparent font-sans text-xl font-bold text-[#18181B] outline-none select-none cursor-not-allowed">
                        <p class="text-[9px] text-[#A8A29E] font-medium">Auto-summed from sizes</p>
                    </div>

                    {{-- Shipping Fee --}}
                    <div id="shipping-fee-card" class="p-4 bg-[#FAF8F5] border border-[#E5DECE] rounded-2xl flex flex-col justify-between h-26 sm:h-28 transition-all hover:border-[#C5A059] shadow-2xs">
                        <label class="text-[10px] font-bold uppercase tracking-wider text-[#78716C]">Shipping Fee (₱) <span class="text-[#C5A059]">*</span></label>
                        <input type="number" 
                               name="shippingFee" 
                               id="shippingFeeInput" 
                               required 
                               min="0" 
                               max="500" 
                               step="0.01" 
                               placeholder="0.00"
                               value="{{ old('shippingFee', 0) }}"
                               oninput="if(parseFloat(this.value) > 500) this.value = 500; calculateFillRate();"
                               class="w-full bg-transparent font-sans text-xl font-bold text-[#18181B] outline-none border-b border-transparent focus:border-[#C5A059] transition-all">
                        <p class="text-[9px] text-[#A8A29E] font-medium">Enter 0 for free delivery</p>
                    </div>

                    {{-- Shipping Days --}}
                    <div id="shipping-days-card" class="p-4 bg-[#FAF8F5] border border-[#E5DECE] rounded-2xl flex flex-col justify-between h-26 sm:h-28 transition-all hover:border-[#C5A059] shadow-2xs">
                        <label class="text-[10px] font-bold uppercase tracking-wider text-[#78716C]">Est. Shipping Days <span class="text-[#C5A059]">*</span></label>
                        <input type="number" 
                               name="shippingDays" 
                               id="shippingDaysInput" 
                               required 
                               min="1" 
                               max="30" 
                               step="1" 
                               placeholder="5"
                               value="{{ old('shippingDays', 5) }}"
                               oninput="if(parseInt(this.value) > 30) this.value = 30; calculateFillRate();"
                               class="w-full bg-transparent font-sans text-xl font-bold text-[#18181B] outline-none border-b border-transparent focus:border-[#C5A059] transition-all">
                        <p class="text-[9px] text-[#A8A29E] font-medium">Delivery lead time</p>
                    </div>
                </div>

                {{-- Lumban Special Discount Panel --}}
                <div class="p-4 rounded-2xl border border-[#F3E8CE] bg-[#FEF9EC]/50 space-y-3">
                    <input type="hidden" name="is_on_sale" id="isOnSaleInput" value="0">

                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-[#C5A059]"></span>
                            <span class="text-xs font-bold text-[#9A6B1F] uppercase tracking-wider">Special Price / Sale Discount</span>
                            <span class="text-[9px] text-[#78716C] font-bold uppercase tracking-wider">(Optional)</span>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer shrink-0">
                            <input type="checkbox" id="discountToggle" class="sr-only peer" onchange="toggleDiscount(this)">
                            <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-0.5 after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-[#18181B]"></div>
                        </label>
                    </div>

                    <div id="discountFields" class="hidden space-y-2.5 pt-2 border-t border-[#F3E8CE]">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 items-end">
                            <div>
                                <label class="text-[10px] font-bold uppercase tracking-wider text-[#78716C] mb-1 block">Discount (%)</label>
                                <input type="number" 
                                       name="discount_percentage" 
                                       id="discountPercentage" 
                                       min="1" 
                                       max="99" 
                                       step="1" 
                                       placeholder="e.g. 20"
                                       class="w-full px-4 py-2.5 bg-white border border-[#E5DECE] rounded-xl outline-none font-bold text-sm text-[#18181B] focus:border-[#C5A059]"
                                       oninput="if(parseInt(this.value) > 99) this.value = 99; updateDiscountPreview();">
                            </div>
                            <div>
                                <label class="text-[10px] font-bold uppercase tracking-wider text-[#78716C] mb-1 block">Price Preview</label>
                                <div id="discountPreview" class="hidden w-full px-4 py-2.5 bg-white rounded-xl border border-[#F3E8CE] items-center justify-center gap-2 h-10.5 shadow-2xs">
                                    <span id="previewOriginal" class="text-xs text-[#78716C] line-through font-bold"></span>
                                    <span id="previewSale" class="text-sm font-black text-[#9A6B1F]"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- 3. Payment Methods Card --}}
            <div id="payment-methods-card" class="bg-white p-6 sm:p-7 rounded-3xl border border-[#EFE8DA] shadow-2xs space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-[#EFE8DA]">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-full bg-[#FEF9EC] border border-[#F3E8CE] flex items-center justify-center text-[#9A6B1F] shrink-0 font-serif font-bold text-xs shadow-2xs">3</div>
                        <h3 class="font-serif text-sm sm:text-base font-bold text-[#18181B] tracking-tight">Payment Methods <span class="text-[#C5A059]">*</span></h3>
                    </div>
                    <a href="{{ route('seller.profile') }}?open_payment=1#payment-methods" target="_blank" class="text-xs font-bold text-[#9A6B1F] hover:text-[#18181B] flex items-center gap-1">
                        Settings ↗
                    </a>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-1">
                    {{-- GCash --}}
                    @php 
                        $user = auth()->user(); 
                        $hasGcashNumber = !empty($user->gcashNumber);
                        $hasGcashQr = !empty($user->gcashQrCode);
                        $isGcashComplete = $hasGcashNumber && $hasGcashQr;
                    @endphp
                    <div class="rounded-2xl border border-blue-100 overflow-hidden shadow-2xs">
                        <div class="flex items-center justify-between px-4 py-2.5 bg-linear-to-r from-blue-600 to-blue-500">
                            <span class="text-[10px] font-black uppercase tracking-widest text-white">GCash</span>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="product_is_gcash_available" value="1" id="gcash_toggle_create" class="sr-only peer" {{ old('product_is_gcash_available', true) ? 'checked' : '' }} onchange="document.getElementById('gcash_fields_create').style.display = this.checked ? '' : 'none'; calculateFillRate();">
                                <div class="w-8 h-4.5 bg-white/30 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-0.5 after:bg-white after:border-gray-300 after:border after:rounded-full after:h-3.5 after:w-3.5 after:transition-all peer-checked:bg-white/40 border border-white/40"></div>
                            </label>
                        </div>
                        <div id="gcash_fields_create" {{ old('product_is_gcash_available', true) ? '' : 'style=display:none' }} class="p-3.5 bg-white flex items-center gap-3">
                            <div class="flex-1 min-w-0">
                                @if($isGcashComplete)
                                    <div class="text-xs font-black text-gray-900">{{ $user->gcashNumber }}</div>
                                    <div class="text-[9px] text-blue-600 font-bold uppercase tracking-widest mt-0.5">✓ Ready (Number & QR Set)</div>
                                @else
                                    <div class="text-[10px] text-amber-600 font-bold">Incomplete setup</div>
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
                    <div class="rounded-2xl border border-emerald-100 overflow-hidden shadow-2xs">
                        <div class="flex items-center justify-between px-4 py-2.5 bg-linear-to-r from-emerald-600 to-emerald-500">
                            <span class="text-[10px] font-black uppercase tracking-widest text-white">Maya</span>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="product_is_maya_available" value="1" id="maya_toggle_create" class="sr-only peer" {{ old('product_is_maya_available', false) ? 'checked' : '' }} onchange="document.getElementById('maya_fields_create').style.display = this.checked ? '' : 'none'; calculateFillRate();">
                                <div class="w-8 h-4.5 bg-white/30 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-0.5 after:bg-white after:border-gray-300 after:border after:rounded-full after:h-3.5 after:w-3.5 after:transition-all peer-checked:bg-white/40 border border-white/40"></div>
                            </label>
                        </div>
                        <div id="maya_fields_create" {{ old('product_is_maya_available', false) ? '' : 'style=display:none' }} class="p-3.5 bg-white flex items-center gap-3">
                            <div class="flex-1 min-w-0">
                                @if($isMayaComplete)
                                    <div class="text-xs font-black text-gray-900">{{ $user->mayaNumber }}</div>
                                    <div class="text-[9px] text-emerald-600 font-bold uppercase tracking-widest mt-0.5">✓ Ready (Number & QR Set)</div>
                                @else
                                    <div class="text-[10px] text-amber-600 font-bold">Incomplete setup</div>
                                    <a href="{{ route('seller.profile') }}?open_payment=1#payment-methods" class="text-[9px] text-emerald-600 font-bold underline">Add in Settings →</a>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- 4. Artisan Description & Storytelling Card --}}
            <div class="bg-white p-6 sm:p-7 rounded-3xl border border-[#EFE8DA] shadow-2xs space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-[#EFE8DA]">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-full bg-[#FEF9EC] border border-[#F3E8CE] flex items-center justify-center text-[#9A6B1F] shrink-0 font-serif font-bold text-xs shadow-2xs">4</div>
                        <div>
                            <h3 class="font-serif text-sm sm:text-base font-bold text-[#18181B] tracking-tight">Artisan Description & Story <span class="text-[#C5A059]">*</span></h3>
                            <p class="text-xs text-[#78716C] font-medium mt-0.5">Highlight the craftsmanship, weaving techniques, and care instructions</p>
                        </div>
                    </div>

                    {{-- AI Auto-Write Story Button (Sleek Onyx Pill) --}}
                    <button type="button" 
                            @click="generateDescriptionAi()"
                            :disabled="isAiLoading"
                            class="px-4 py-2 rounded-full bg-[#18181B] hover:bg-[#27272A] active:scale-95 text-white text-xs font-bold shadow-xs flex items-center gap-1.5 cursor-pointer transition-all disabled:opacity-50">
                        <span x-show="!isAiLoading" class="flex items-center gap-1.5">
                            <span class="text-xs text-[#C5A059]">✦</span>
                            <span>AI Auto-Write</span>
                        </span>
                        <span x-show="isAiLoading" class="flex items-center gap-1.5" x-cloak>
                            <svg class="animate-spin h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                            </svg>
                            <span>Writing...</span>
                        </span>
                    </button>
                </div>

                <div class="relative group pt-1">
                    <textarea name="description" 
                              id="artisanDescription" 
                              required 
                              rows="5" 
                              maxlength="500"
                              x-model="description"
                              @input="updateCharCount($el); calculateFillRate();"
                              placeholder="Describe the craftsmanship, cultural heritage, weaving techniques, and unique story behind this piece..."
                              class="w-full px-4 py-3.5 bg-[#FAF8F5] border border-[#E5DECE] rounded-2xl outline-none focus:border-[#C5A059] focus:bg-white focus:ring-2 focus:ring-[#C5A059]/15 transition-all font-normal text-sm text-[#18181B] placeholder:text-[#A8A29E] resize-none pb-8 shadow-2xs"></textarea>
                    
                    <div class="absolute bottom-3 right-4 flex items-center gap-1 bg-white/95 backdrop-blur-xs px-2.5 py-0.5 rounded-full border border-[#E5DECE] text-[10px] font-bold text-[#78716C] pointer-events-none shadow-2xs">
                        <span id="charCounter" x-text="description ? description.length : 0">0</span><span class="text-[#A8A29E]">/</span><span>500</span>
                    </div>
                </div>
            </div>

            {{-- 7. Bottom Submission Actions (Back, Draft & Publish) --}}
            <div class="pt-6 pb-2 border-t border-[#EFE8DA] flex flex-col sm:flex-row items-center justify-between gap-4">
                <button type="button" 
                        @click="step = 1; window.scrollTo({ top: 0, behavior: 'smooth' })"
                        class="w-full sm:w-auto px-6 py-3.5 rounded-full border border-[#E5DECE] hover:bg-[#FAF8F5] text-[#18181B] font-bold text-xs transition-all flex items-center justify-center gap-2 cursor-pointer shadow-2xs">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    <span>Back to Step 1</span>
                </button>

                <div class="flex flex-col sm:flex-row items-center gap-3 w-full sm:w-auto">
                    <button type="button" 
                            @click="submitAsDraft()"
                            class="w-full sm:w-auto px-7 py-3.5 rounded-full border border-[#18181B] bg-white hover:bg-[#FAF8F5] text-[#18181B] font-bold text-xs tracking-wide transition-all shadow-xs flex items-center justify-center gap-2 cursor-pointer active:scale-[0.99]">
                        <svg class="w-4 h-4 text-[#78716C]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/>
                        </svg>
                        <span>Save as Draft</span>
                    </button>

                    <button type="submit" 
                            @click="document.getElementById('formActionInput').value = 'publish'"
                            class="w-full sm:w-auto px-8 py-3.5 rounded-full bg-[#18181B] hover:bg-[#27272A] active:scale-[0.99] text-white font-bold text-xs tracking-wide shadow-md hover:shadow-xl transition-all flex items-center justify-center gap-2.5 cursor-pointer">
                        <span>Publish Heritage Piece</span>
                        <span class="text-sm font-bold">→</span>
                    </button>
                </div>
            </div>
        </div>

    </form>
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
            'image' => $c->getImageUrl(),
        ];
    })->values();

    $currentUser = auth()->user();
    $productInitData = [
        'name'             => (string) old('name', ''),
        'categoryId'       => (string) old('CategoryId', ''),
        'targetGroup'      => (string) old('target_group', ''),
        'fabricType'       => (string) old('fabric_type', '100% Piña'),
        'price'            => (string) old('price', ''),
        'description'      => (string) old('description', ''),
        'csrfToken'        => (string) csrf_token(),
        'aiSuggestUrl'     => (string) route('ai.seller.suggest'),
        'aiDescriptionUrl' => (string) route('ai.seller.description'),
        'hasGcashNumber'   => !empty($currentUser?->gcashNumber),
        'hasGcashQr'       => !empty($currentUser?->gcashQrCode),
        'hasMayaNumber'    => !empty($currentUser?->mayaNumber),
        'hasMayaQr'        => !empty($currentUser?->mayaQrCode),
    ];
@endphp

<script id="product-init-data" type="application/json">
{!! json_encode($productInitData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) !!}
</script>

<script id="categories-data-json" type="application/json">
{!! json_encode($categoriesJson, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) !!}
</script>

<script>
let productImagesDT = new DataTransfer();

function getProductInitData() {
    try {
        const el = document.getElementById('product-init-data');
        if (el && el.textContent) {
            return JSON.parse(el.textContent);
        }
    } catch (e) {}
    return {};
}

function addProductManager() {
    let parsedCats = [];
    try {
        const jsonEl = document.getElementById('categories-data-json');
        if (jsonEl && jsonEl.textContent) {
            parsedCats = JSON.parse(jsonEl.textContent);
        }
    } catch (e) {
        parsedCats = [];
    }

    const initData = getProductInitData();

    return {
        step: 1,
        imageCount: 0,
        imagePreviews: [],
        productName: initData.name || '',
        selectedCategory: initData.categoryId || '',
        targetGroup: initData.targetGroup || 'Men',
        fabricType: initData.fabricType || '100% Piña',
        price: initData.price || '',
        description: initData.description || '',
        fillRate: 15,
        isAiLoading: false,

        // Product Variations / Unified Media & Variants State
        variants: [
            { id: 0, name: '', file: null, imagePreview: null }
        ],

        get imageCount() {
            return this.variants.filter(v => v && v.imagePreview !== null).length;
        },

        addVariantRow() {
            const nextId = this.variants.length;
            this.variants.push({ id: nextId, name: '', file: null, imagePreview: null });
            this.calculateFillRate();
        },

        removeVariantRow(index) {
            if (index === 0) return;
            this.variants.splice(index, 1);
            this.calculateFillRate();
        },

        handleVariantFile(event, index) {
            const file = event.target.files[0];
            if (file) {
                if (file.size > 5 * 1024 * 1024) {
                    triggerAppModal('Image Exceeds 5MB', 'Selected photo exceeds the 5MB size limit.', 'warning');
                    event.target.value = '';
                    return;
                }
                this.variants[index].file = file;
                const reader = new FileReader();
                reader.onload = (e) => {
                    this.variants[index].imagePreview = e.target.result;
                    const box = document.getElementById('variant_upload_box_' + index);
                    if (box) box.classList.remove('border-red-500');
                    this.calculateFillRate();
                };
                reader.readAsDataURL(file);
            }
        },

        removeVariantImage(index) {
            this.variants[index].file = null;
            this.variants[index].imagePreview = null;
            const fileInput = document.getElementById('variant_file_' + index);
            if (fileInput) fileInput.value = '';
            this.calculateFillRate();
        },

        // Real-time categories state
        categoriesList: parsedCats,

        init() {
            this.calculateFillRate();
        },

        get selectedCategoryObj() {
            if (!Array.isArray(this.categoriesList)) return null;
            return this.categoriesList.find(c => c && String(c.id) === String(this.selectedCategory)) || null;
        },

        get filteredCategories() {
            if (!Array.isArray(this.categoriesList)) return [];
            if (!this.targetGroup) return [];
            return this.categoriesList.filter(c => {
                if (!c) return false;
                let tg = c.target_group;
                if (Array.isArray(tg)) {
                    return tg.includes(this.targetGroup);
                }
                if (typeof tg === 'string') {
                    return tg === this.targetGroup;
                }
                return false;
            });
        },

        selectCategory(cat) {
            if (!cat) return;
            this.selectedCategory = cat.id;
            const catContainer = document.getElementById('category-cards-container');
            if (catContainer) catContainer.classList.remove('border-red-500');
            this.calculateFillRate();
        },

        onTargetGroupChange(group) {
            this.targetGroup = group;
            const tgContainer = document.getElementById('target-group-container');
            if (tgContainer) tgContainer.classList.remove('border-red-500', 'p-1', 'border', 'rounded-xl');

            // If current category does not belong to the selected tag, unselect it
            if (this.selectedCategory) {
                const currentCat = Array.isArray(this.categoriesList) ? this.categoriesList.find(c => String(c.id) === String(this.selectedCategory)) : null;
                if (currentCat) {
                    let tg = currentCat.target_group;
                    let hasTag = Array.isArray(tg) ? tg.includes(group) : (tg === group);
                    if (!hasTag) {
                        this.selectedCategory = '';
                    }
                }
            }

            const catContainer = document.getElementById('category-cards-container');
            if (catContainer) catContainer.classList.remove('border-red-500');

            this.calculateFillRate();
        },

        get isStep1Complete() {
            const hasName = Boolean(this.productName && this.productName.trim().length > 0);
            const hasCategory = Boolean(this.selectedCategory && this.selectedCategory !== '');
            const hasTarget = Boolean(this.targetGroup && ['Men', 'Women', 'Kids'].includes(this.targetGroup));
            const hasMainImage = Boolean(this.variants[0] && this.variants[0].imagePreview !== null);
            let extraVariantsValid = true;
            for (let i = 1; i < this.variants.length; i++) {
                if (!this.variants[i].name || this.variants[i].name.trim().length === 0 || !this.variants[i].imagePreview) {
                    extraVariantsValid = false;
                    break;
                }
            }
            return hasName && hasCategory && hasTarget && hasMainImage && extraVariantsValid;
        },

        goToStep2() {
            if (!this.productName || this.productName.trim().length === 0) {
                triggerAppModal('Product Name Required', 'Please enter a product name to proceed.', 'warning');
                const nameInput = document.getElementById('productNameInput');
                if (nameInput) {
                    nameInput.classList.add('border-red-500');
                    nameInput.focus();
                }
                return;
            }

            if (!this.targetGroup || !['Men', 'Women', 'Kids'].includes(this.targetGroup)) {
                triggerAppModal('Target Tag Required', 'Please select who this product is for (Men, Women, or Kids).', 'warning');
                const tgContainer = document.getElementById('target-group-container');
                if (tgContainer) tgContainer.classList.add('border-red-500', 'p-1', 'border', 'rounded-xl');
                return;
            }

            if (!this.selectedCategory || this.selectedCategory === '') {
                triggerAppModal('Category Required', 'Please select a product category for ' + this.targetGroup + '.', 'warning');
                const catContainer = document.getElementById('category-cards-container');
                if (catContainer) {
                    catContainer.classList.add('border-red-500');
                }
                return;
            }

            if (!this.variants[0] || !this.variants[0].imagePreview) {
                triggerAppModal('Product Photo Required', 'Please upload the main product photo for Variant 1.', 'warning');
                const v1Box = document.getElementById('variant_upload_box_0');
                if (v1Box) v1Box.classList.add('border-red-500');
                return;
            }

            for (let i = 1; i < this.variants.length; i++) {
                const v = this.variants[i];
                if (!v.name || v.name.trim().length === 0) {
                    triggerAppModal('Variant Name Missing', 'Please enter a name for Variant ' + (i + 1) + '.', 'warning');
                    return;
                }
                if (!v.imagePreview) {
                    triggerAppModal('Variant Photo Missing', 'Please upload a photo for Variant ' + (i + 1) + ' ("' + v.name + '").', 'warning');
                    return;
                }
            }

            this.step = 2;
            this.calculateFillRate();
            setTimeout(() => {
                window.scrollTo({ top: 350, behavior: 'smooth' });
            }, 100);
        },

        calculateFillRate() {
            let score = 0;
            if (this.imageCount > 0) score += 20;
            if (this.productName && this.productName.trim().length >= 3) score += 20;
            if (this.selectedCategory) score += 15;
            if (parseFloat(this.price) > 0) score += 15;
            if (this.description && this.description.trim().length >= 10) score += 15;
            if (document.querySelectorAll('.size-checkbox:checked').length > 0) score += 10;
            if (this.targetGroup) score += 5;
            this.fillRate = Math.min(100, score);
        },

        async triggerAiGenerate() {
            if (this.imageCount === 0 && !this.productName) {
                triggerAppModal('Upload Cover Photo', 'Please upload a product photo first for AI auto-recommendations.', 'info');
                return;
            }

            this.isAiLoading = true;
            try {
                const formData = new FormData();
                if (this.variants[0]?.file) {
                    formData.append('image', this.variants[0].file);
                }
                formData.append('current_name', this.productName || '');
                formData.append('current_category', this.selectedCategory || '');
                const initData = getProductInitData();
                formData.append('_token', initData.csrfToken || '');

                const response = await fetch(initData.aiSuggestUrl || '', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                if (response.ok) {
                    const data = await response.json();
                    if (data.title) this.productName = data.title;
                    if (data.category_id) {
                        this.selectedCategory = data.category_id;
                        const matchCat = this.categoriesList.find(c => c.id === data.category_id);
                        if (matchCat && matchCat.target_group && matchCat.target_group.length > 0) {
                            this.targetGroup = matchCat.target_group[0];
                        }
                    }
                    if (data.target_group) this.targetGroup = data.target_group;
                    if (data.fabric_type) this.fabricType = data.fabric_type;
                    if (data.description) this.description = data.description;

                    this.calculateFillRate();
                }
            } catch (err) {
                console.error('AI suggestion failed', err);
            } finally {
                this.isAiLoading = false;
            }
        },

        async generateDescriptionAi() {
            if (this.isAiLoading) return;
            this.isAiLoading = true;
            try {
                const initData = getProductInitData();
                const selectedCatName = this.selectedCategoryObj ? this.selectedCategoryObj.name : '';
                const variantNames = this.hasVariants ? this.variants.map(v => v.name).filter(Boolean) : [];

                const response = await fetch(initData.aiDescriptionUrl || '/seller/generate-description', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': initData.csrfToken || '',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        name: this.productName || '',
                        category: selectedCatName,
                        category_id: this.selectedCategory || '',
                        target_group: this.targetGroup || 'Men',
                        fabric: this.fabricType || '100% Piña',
                        variants: variantNames,
                        theme: 'Wedding & Cultural Heritage'
                    })
                });
                if (response.ok) {
                    const data = await response.json();
                    if (data && data.description) {
                        this.description = data.description;
                        const textarea = document.getElementById('artisanDescription');
                        if (textarea) {
                            textarea.classList.add('ring-2', 'ring-[#C0420A]', 'border-[#C0420A]');
                            setTimeout(() => {
                                textarea.classList.remove('ring-2', 'ring-[#C0420A]', 'border-[#C0420A]');
                            }, 1500);
                        }
                        this.calculateFillRate();
                    }
                }
            } catch (e) {
                console.error('AI Description error:', e);
            } finally {
                this.isAiLoading = false;
            }
        },

        submitAsDraft() {
            document.getElementById('formActionInput').value = 'draft';
            document.getElementById('productForm').submit();
        }
    };
}

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

function updateCharCount(el) {
    const counter = document.getElementById('charCounter');
    if (counter) counter.textContent = el.value.length;
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
    const action = document.getElementById('formActionInput')?.value;
    if (action === 'draft') {
        const nameInput = document.querySelector('input[name="name"]');
        if (!nameInput || !nameInput.value.trim()) {
            e.preventDefault();
            triggerAppModal('Draft Name Required', 'Please enter at least a product name to save a draft.', 'warning');
            return false;
        }
        return true;
    }

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
    const targetGroupChecked = document.querySelector('input[name="target_group"]:checked') || document.getElementById('targetGroupInput');
    const targetGroupContainer = document.getElementById('target-group-container');
    const targetGroupVal = targetGroupChecked ? targetGroupChecked.value : '';
    if (!targetGroupVal || !['Men', 'Women', 'Kids'].includes(targetGroupVal)) {
        errors.push('Please specify who this product is for (Men, Women, or Kids).');
        if (targetGroupContainer) targetGroupContainer.classList.add('border-red-500', 'p-1', 'border', 'rounded-xl');
    }

    const categorySelect = document.getElementById('categorySelect') || document.querySelector('input[name="CategoryId"], select[name="CategoryId"]');
    const categoryVal = categorySelect ? categorySelect.value : '';
    const catContainer = document.getElementById('category-cards-container');
    if (!categoryVal) {
        errors.push('Please select a Product Category.');
        if (catContainer) catContainer.classList.add('border-red-500');
    }

    // 5. Product Imagery (Variant 1 is required)
    if (!isEdit) {
        const v1FileInput = document.getElementById('variant_file_0');
        const hasV1File = Boolean(v1FileInput && v1FileInput.files && v1FileInput.files.length > 0);
        if (!hasV1File) {
            errors.push('Please upload the main product photo for Variant 1 (Cover Photo).');
            const v1Box = document.getElementById('variant_upload_box_0');
            if (v1Box) v1Box.classList.add('border-red-500');
        }
    }

    // 6. Payment Methods
    const gcashToggle = document.getElementById('gcash_toggle_create');
    const mayaToggle = document.getElementById('maya_toggle_create');
    const paymentCard = document.getElementById('payment-methods-card');
    const isGcashChecked = gcashToggle ? gcashToggle.checked : false;
    const isMayaChecked = mayaToggle ? mayaToggle.checked : false;

    const initData = getProductInitData();
    const hasGcashNumber = Boolean(initData.hasGcashNumber);
    const hasGcashQr = Boolean(initData.hasGcashQr);
    const hasMayaNumber = Boolean(initData.hasMayaNumber);
    const hasMayaQr = Boolean(initData.hasMayaQr);

    let hasAnyCompleteEnabled = false;

    if (isGcashChecked) {
        if (!hasGcashNumber || !hasGcashQr) {
            if (!hasGcashNumber && !hasGcashQr) {
                errors.push('GCash is enabled but not configured. Both Mobile Number and QR Code are required.');
            } else if (!hasGcashQr) {
                errors.push('GCash is enabled but missing a QR Code.');
            } else {
                errors.push('GCash is enabled but missing a Mobile Number.');
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
                errors.push('Maya is enabled but missing a QR Code.');
            } else {
                errors.push('Maya is enabled but missing an Account Number.');
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

    // 7. Lumban Special Discount
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
            if (typeof firstError.focus === 'function') firstError.focus();
        }

        return false;
    }

    return true;
}
</script>
@endsection
