@extends('layouts.seller')

@section('content')
<div class="max-w-4xl mx-auto pb-36 sm:pb-28 lg:pb-16 px-3 sm:px-6" x-data="addProductManager()">
    {{-- Top Header & Navigation --}}
    <div class="mb-4 sm:mb-8 flex items-center justify-between">
        <div>
            <a href="{{ route('seller.products.index') }}" class="inline-flex items-center gap-2 text-[10px] font-bold text-gray-400 uppercase tracking-widest hover:text-[#C0420A] transition-colors mb-1.5 sm:mb-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                Back to catalogue
            </a>
            <h1 class="font-serif text-xl sm:text-3xl font-bold text-black uppercase tracking-tight">
                New <span class="text-[#C0420A] italic lowercase">heritage piece</span>
            </h1>
        </div>

        {{-- Step Indicator Badge --}}
        <div class="flex items-center gap-2">
            <span class="text-[10px] font-black uppercase tracking-widest px-3 py-1 rounded-full border transition-all"
                  :class="step === 1 ? 'bg-amber-50 text-amber-700 border-amber-200' : 'bg-emerald-50 text-emerald-700 border-emerald-200'">
                <span x-text="step === 1 ? 'Step 1: Image & Core Info' : 'Step 2: Specifications'"></span>
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
        {{-- PHASE 1: IMAGE FIRST & CORE IDENTIFICATION (Always at Top)               --}}
        {{-- ========================================================================= --}}
        <div class="bg-white p-5 sm:p-7 rounded-2xl border border-gray-100 shadow-sm space-y-6">
            
            {{-- 1. Product Image Section (1/8) --}}
            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <label class="text-xs sm:text-sm font-bold text-gray-900 uppercase tracking-wider">
                            Product Image <span class="text-gray-400 font-normal" x-text="'(' + imageCount + '/8)'"></span> <span class="text-[#C0420A]">*</span>
                        </label>
                    </div>
                    <span class="text-[10px] text-gray-400 font-medium hidden sm:inline-block">First photo will be the Cover Image</span>
                </div>

                {{-- Image Slots & Upload Trigger --}}
                <div class="flex flex-wrap gap-3 items-center">
                    {{-- Rendered Previews (Cover Image & Additional Photos) --}}
                    <template x-for="(img, index) in imagePreviews" :key="index">
                        <div class="relative w-24 h-24 sm:w-28 sm:h-28 rounded-xl overflow-hidden border border-gray-200 bg-gray-50 shadow-xs group shrink-0">
                            <img :src="img.url" class="w-full h-full object-cover object-top transition-transform group-hover:scale-105">
                            
                            {{-- Cover Image Ribbon on First Image --}}
                            <template x-if="index === 0">
                                <div class="absolute bottom-0 inset-x-0 bg-black/75 backdrop-blur-xs py-0.5 text-center text-[9px] font-bold text-white uppercase tracking-wider">
                                    Cover Image
                                </div>
                            </template>

                            {{-- Image Number Badge (for secondary images) --}}
                            <template x-if="index > 0">
                                <div class="absolute top-1.5 left-1.5 w-5 h-5 rounded-full bg-black/60 backdrop-blur-xs flex items-center justify-center text-[9px] font-bold text-white" x-text="index + 1"></div>
                            </template>

                            {{-- Delete Button (X) --}}
                            <button type="button" @click="removeImage(index)" class="absolute top-1.5 right-1.5 w-5 h-5 bg-red-600 hover:bg-red-700 text-white rounded-full flex items-center justify-center text-[10px] font-black shadow-md hover:scale-110 active:scale-95 transition-all">
                                ✕
                            </button>
                        </div>
                    </template>

                    {{-- Add Image [+] Slot (Only shown if less than 8 images) --}}
                    <template x-if="imageCount < 8">
                        <label for="imageUploadInput"
                               id="dropZone"
                               class="w-24 h-24 sm:w-28 sm:h-28 rounded-xl border-2 border-dashed border-gray-300 hover:border-[#C0420A] bg-gray-50/70 hover:bg-orange-50/20 flex flex-col items-center justify-center gap-1 cursor-pointer transition-all shrink-0 text-center p-2 group">
                            <div class="w-8 h-8 rounded-lg bg-gray-100 group-hover:bg-[#C0420A]/10 text-gray-400 group-hover:text-[#C0420A] flex items-center justify-center transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                            </div>
                            <span class="text-[10px] font-bold text-gray-500 group-hover:text-[#C0420A]" x-text="imageCount === 0 ? 'Upload Photo' : 'Add Photo'"></span>
                        </label>
                    </template>

                    {{-- Hidden Multi-File Input --}}
                    <input type="file" id="imageUploadInput" name="images[]" multiple accept="image/jpeg,image/png,image/webp" class="hidden" @change="handleFileChange($event)">
                </div>

                {{-- Empty Image Helper Warning --}}
                <div x-show="imageCount === 0" class="text-[11px] text-gray-400 flex items-center gap-1.5 pt-1">
                    <svg class="w-3.5 h-3.5 text-[#C0420A]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <span>Upload your main cover photo first to unlock auto-suggestions and full specifications.</span>
                </div>
            </div>

            <div class="border-t border-gray-100 pt-5 space-y-4">
                {{-- 2. Product Name (English) --}}
                <div class="space-y-1.5">
                    <div class="flex items-center justify-between">
                        <label class="text-[11px] font-bold uppercase tracking-wider text-gray-700">
                            Product Name(English) <span class="text-[#C0420A]">*</span>
                            <span class="text-[10px] text-gray-400 font-normal" x-text="'(' + (productName ? productName.length : 0) + '/100)'"></span>
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
                               class="w-full px-4 py-3 bg-gray-50/70 border border-gray-200 rounded-xl outline-none focus:border-[#C0420A] focus:bg-white focus:ring-2 focus:ring-[#C0420A]/10 transition-all font-semibold text-sm text-gray-800 placeholder:text-gray-400 placeholder:font-normal pr-10">
                        
                        {{-- Clear Button (X) --}}
                        <button type="button" 
                                x-show="productName && productName.length > 0"
                                @click="productName = ''; calculateFillRate();"
                                class="absolute right-3 text-gray-400 hover:text-gray-600 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>
                    </div>
                </div>

                {{-- 3. Product Category & Tag Selection --}}
                <div class="space-y-2.5">
                    <div class="flex items-center justify-between">
                        <label class="text-[11px] font-bold uppercase tracking-wider text-gray-700">
                            Category <span class="text-[#C0420A]">*</span>
                        </label>
                        <span class="text-[9px] text-[#C0420A] font-semibold">Select category</span>
                    </div>

                    {{-- Category Demographic Quick Filter Chips --}}
                    <div class="space-y-1.5">
                        <div class="flex items-center justify-between">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-gray-500">
                                Target Tag <span class="text-[#C0420A]">*</span>
                            </span>
                            <span class="text-[9px] text-gray-400 font-medium">Select tag</span>
                        </div>

                        <style>
                            .tag-chip {
                                display: inline-flex;
                                align-items: center;
                                gap: 6px;
                                padding: 6px 13px;
                                border-radius: 12px;
                                font-size: 11px;
                                font-weight: 700;
                                text-transform: uppercase;
                                letter-spacing: 0.05em;
                                cursor: pointer;
                                transition: all 0.15s ease;
                                border: 1.5px solid transparent;
                                user-select: none;
                            }
                            .tag-chip-all {
                                background-color: #f3f4f6;
                                color: #374151;
                                border-color: #e5e7eb;
                            }
                            .tag-chip-all.active {
                                background-color: #111827 !important;
                                color: #ffffff !important;
                                border-color: #111827 !important;
                                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                            }
                            .tag-chip-men {
                                background-color: #eff6ff;
                                color: #1d4ed8;
                                border-color: #bfdbfe;
                            }
                            .tag-chip-men.active {
                                background-color: #2563eb !important;
                                color: #ffffff !important;
                                border-color: #2563eb !important;
                                box-shadow: 0 2px 4px rgba(37,99,235,0.25);
                            }
                            .tag-chip-women {
                                background-color: #fdf2f8;
                                color: #be185d;
                                border-color: #fbcfe8;
                            }
                            .tag-chip-women.active {
                                background-color: #db2777 !important;
                                color: #ffffff !important;
                                border-color: #db2777 !important;
                                box-shadow: 0 2px 4px rgba(219,39,119,0.25);
                            }
                            .tag-chip-kids {
                                background-color: #fffbeb;
                                color: #b45309;
                                border-color: #fde68a;
                            }
                            .tag-chip-kids.active {
                                background-color: #d97706 !important;
                                color: #ffffff !important;
                                border-color: #d97706 !important;
                                box-shadow: 0 2px 4px rgba(217,119,6,0.25);
                            }
                        </style>

                        <div class="flex items-center gap-2 overflow-x-auto pb-1 no-scrollbar">
                            <button type="button" 
                                    @click="filterByTag('All')"
                                    :class="categoryFilter === 'All' ? 'active' : ''"
                                    class="tag-chip tag-chip-all">
                                <span>All</span>
                                <span style="font-size: 10px; opacity: 0.85;" x-text="'(' + (categoriesList ? categoriesList.length : 0) + ')'"></span>
                            </button>

                            <button type="button" 
                                    @click="filterByTag('Men')"
                                    :class="categoryFilter === 'Men' ? 'active' : ''"
                                    class="tag-chip tag-chip-men">
                                <span>👔 Men</span>
                                <span style="font-size: 10px; opacity: 0.85;" x-text="'(' + getTagCount('Men') + ')'"></span>
                            </button>

                            <button type="button" 
                                    @click="filterByTag('Women')"
                                    :class="categoryFilter === 'Women' ? 'active' : ''"
                                    class="tag-chip tag-chip-women">
                                <span>👗 Women</span>
                                <span style="font-size: 10px; opacity: 0.85;" x-text="'(' + getTagCount('Women') + ')'"></span>
                            </button>

                            <button type="button" 
                                    @click="filterByTag('Kids')"
                                    :class="categoryFilter === 'Kids' ? 'active' : ''"
                                    class="tag-chip tag-chip-kids">
                                <span>🧸 Kids</span>
                                <span style="font-size: 10px; opacity: 0.85;" x-text="'(' + getTagCount('Kids') + ')'"></span>
                            </button>
                        </div>
                    </div>

                    {{-- Interactive Category Selector Trigger --}}
                    <div class="relative">
                        <button type="button" 
                                @click="isCategoryDropdownOpen = !isCategoryDropdownOpen"
                                class="w-full px-4 py-3 bg-gray-50/80 border border-gray-200 rounded-xl outline-none focus:border-[#C0420A] focus:bg-white text-left flex items-center justify-between transition-all group shadow-2xs">
                            
                            <div class="flex items-center gap-2.5 min-w-0">
                                <template x-if="selectedCategoryObj">
                                    <div class="flex items-center gap-2 truncate">
                                        <span class="text-sm font-bold text-gray-900 truncate" x-text="selectedCategoryObj.name"></span>
                                        
                                        {{-- Demographic Badges for Selected Category --}}
                                        <div class="flex items-center gap-1 shrink-0">
                                            <template x-for="tag in (selectedCategoryObj.target_group || [])" :key="tag">
                                                <span class="px-2 py-0.5 rounded-md text-[9px] font-black uppercase tracking-wider"
                                                      :class="{
                                                          'bg-blue-100 text-blue-700 border border-blue-200': tag === 'Men',
                                                          'bg-rose-100 text-rose-700 border border-rose-200': tag === 'Women',
                                                          'bg-amber-100 text-amber-700 border border-amber-200': tag === 'Kids'
                                                      }"
                                                      x-text="tag"></span>
                                            </template>
                                            <template x-if="!selectedCategoryObj.target_group || selectedCategoryObj.target_group.length === 0">
                                                <span class="px-2 py-0.5 rounded-md bg-gray-100 text-gray-600 border border-gray-200 text-[9px] font-bold uppercase">Unisex / All</span>
                                            </template>
                                        </div>
                                    </div>
                                </template>
                                <template x-if="!selectedCategoryObj">
                                    <span class="text-sm text-gray-400 font-normal">Select a category (e.g. Formal Barong > Men)</span>
                                </template>
                            </div>

                            <div class="flex items-center gap-1.5 text-gray-400 group-hover:text-gray-700 transition-colors shrink-0">
                                <span class="text-[11px] font-bold uppercase tracking-wider hidden sm:inline" x-text="isCategoryDropdownOpen ? 'Close' : 'Select'"></span>
                                <svg class="w-4 h-4 transition-transform duration-200 text-gray-400" :class="isCategoryDropdownOpen ? 'rotate-180 text-[#C0420A]' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </div>
                        </button>

                        {{-- Category Dropdown Picker Modal / Menu --}}
                        <div x-show="isCategoryDropdownOpen" 
                             @click.away="isCategoryDropdownOpen = false"
                             x-transition:enter="transition ease-out duration-200"
                             x-transition:enter-start="opacity-0 translate-y-1 scale-98"
                             x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                             x-transition:leave="transition ease-in duration-150"
                             x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                             x-transition:leave-end="opacity-0 translate-y-1 scale-98"
                             class="absolute top-full left-0 right-0 mt-1.5 z-40 bg-white rounded-2xl shadow-2xl border border-gray-200 overflow-hidden max-h-76 flex flex-col p-2 space-y-2"
                             x-cloak>
                            
                            {{-- Search input inside dropdown --}}
                            <div class="relative px-1 pt-1">
                                <input type="text" 
                                       x-model="categorySearch" 
                                       placeholder="Type to search category (e.g. Barong, Gown, Polo)..." 
                                       class="w-full px-3.5 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-xs font-semibold outline-none focus:border-[#C0420A] focus:bg-white transition-all pl-9">
                                <svg class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                            </div>

                            {{-- Categories List --}}
                            <div class="overflow-y-auto space-y-1 pr-1 max-h-56 divide-y divide-gray-50">
                                <template x-for="cat in filteredCategories" :key="cat.id">
                                    <button type="button" 
                                            @click="selectCategory(cat)"
                                            class="w-full px-3 py-2.5 rounded-xl hover:bg-orange-50/70 flex items-center justify-between text-left transition-colors group cursor-pointer"
                                            :class="selectedCategory === cat.id ? 'bg-orange-50/90 border border-[#C0420A]/30' : ''">
                                        
                                        <div class="flex items-center gap-2 min-w-0">
                                            <span class="text-xs font-bold text-gray-800 group-hover:text-[#C0420A]" x-text="cat.name"></span>
                                        </div>

                                        {{-- Demographic Tag Badges --}}
                                        <div class="flex items-center gap-1 shrink-0">
                                            <template x-for="tag in cat.target_group" :key="tag">
                                                <span class="px-2 py-0.5 rounded-md text-[9px] font-black uppercase tracking-wider"
                                                      :class="{
                                                          'bg-blue-100 text-blue-700 border border-blue-200': tag === 'Men',
                                                          'bg-pink-100 text-pink-700 border border-pink-200': tag === 'Women',
                                                          'bg-amber-100 text-amber-700 border border-amber-200': tag === 'Kids'
                                                      }"
                                                      x-text="tag"></span>
                                            </template>
                                            <template x-if="!cat.target_group || cat.target_group.length === 0">
                                                <span class="px-2 py-0.5 rounded-md bg-gray-100 text-gray-600 border border-gray-200 text-[8px] font-bold uppercase">All</span>
                                            </template>
                                        </div>
                                    </button>
                                </template>

                                <template x-if="filteredCategories.length === 0">
                                    <div class="py-8 text-center text-xs text-gray-400">
                                        No categories found matching "<span class="font-bold text-gray-700" x-text="categorySearch"></span>"
                                    </div>
                                </template>
                            </div>
                        </div>

                        {{-- Fallback Synchronized Hidden Native Select with Complete Tag Names --}}
                        <select name="CategoryId" 
                                id="categorySelect" 
                                required
                                x-model="selectedCategory"
                                class="sr-only">
                            <option value="" disabled selected>Select category</option>
                            @foreach($categories as $category)
                                @php
                                    $tags = is_array($category->target_group) ? $category->target_group : (is_string($category->target_group) ? json_decode($category->target_group, true) ?? [] : []);
                                    $tagStr = !empty($tags) ? implode(', ', $tags) : 'All';
                                @endphp
                                <option value="{{ $category->id }}" data-tags="{{ implode(',', $tags) }}" data-name="{{ strtolower($category->name) }}">
                                    {{ $category->name }} ({{ $tagStr }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            {{-- Step 1 Primary CTA Button: "Next: Complete Product Info" --}}
            <div x-show="step === 1" class="pt-2">
                <button type="button" 
                        @click="goToStep2()"
                        class="w-full py-4 bg-blue-600 hover:bg-blue-700 active:scale-[0.99] text-white rounded-full font-bold text-sm tracking-wide shadow-lg shadow-blue-500/20 transition-all flex items-center justify-center gap-2">
                    <span>Next: Complete Product Info</span>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                </button>
            </div>
        </div>

        {{-- ========================================================================= --}}
        {{-- PHASE 2: COMPLETE SPECIFICATIONS (Progressively Revealed after Step 1)     --}}
        {{-- ========================================================================= --}}
        <div x-show="step >= 2" x-collapse class="space-y-6">

            {{-- 1. Fill Rate & Listing Health Bar --}}
            <div class="bg-white p-4 sm:p-5 rounded-2xl border border-gray-100 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <span class="text-xs font-black uppercase tracking-wider text-gray-700">Fill Rate</span>
                    <div class="w-36 sm:w-48 h-2.5 bg-gray-100 rounded-full overflow-hidden">
                        <div class="h-full bg-linear-to-r from-blue-500 to-indigo-600 rounded-full transition-all duration-500" :style="'width: ' + fillRate + '%'"></div>
                    </div>
                    <span class="text-xs font-black text-blue-600" x-text="fillRate + '%'"></span>
                    <span class="text-base">🚀</span>
                </div>

                <div class="flex items-center gap-2">
                    <span class="text-[10px] text-gray-400 font-medium">All essential specifications</span>
                </div>
            </div>

            {{-- 2. Heritage Specifications (Fabric Type & Target Group) --}}
            <div class="bg-white p-5 sm:p-6 rounded-2xl border border-gray-100 shadow-xs space-y-4">
                <div class="flex items-center gap-2.5 pb-2 border-b border-gray-100">
                    <div class="w-7 h-7 rounded-lg bg-[#C0420A]/10 flex items-center justify-center text-[#C0420A] shrink-0 font-bold text-xs">1</div>
                    <h3 class="text-xs sm:text-sm font-black text-gray-900 uppercase tracking-widest leading-none">Heritage Classification</h3>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    {{-- Target Group (Who is this for?) --}}
                    <div id="target-group-container" class="space-y-1.5">
                        <label class="text-[10px] font-bold uppercase tracking-widest text-gray-500">Who is this for? <span class="text-[#C0420A]">*</span></label>
                        <div class="flex gap-2">
                            @foreach(['Men', 'Women', 'Kids'] as $group)
                                <label class="flex-1 cursor-pointer">
                                    <input type="radio" 
                                           name="target_group" 
                                           value="{{ $group }}" 
                                           x-model="targetGroup"
                                           @change="onTargetGroupChange('{{ $group }}')"
                                           class="hidden peer target-group-radio" 
                                           {{ old('target_group', 'Men') == $group ? 'checked' : '' }}>
                                    <div class="w-full py-2.5 rounded-xl border border-gray-200 bg-gray-50 text-xs font-bold text-gray-600 text-center uppercase tracking-wider peer-checked:border-[#C0420A] peer-checked:bg-[#C0420A]/10 peer-checked:text-[#C0420A] transition-all">
                                        {{ $group }}
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    {{-- Fabric Type --}}
                    <div class="space-y-1.5">
                        <label class="text-[10px] font-bold uppercase tracking-widest text-gray-500">Fabric Type <span class="text-[#C0420A]">*</span></label>
                        <select name="fabric_type" 
                                id="fabricTypeSelect"
                                x-model="fabricType"
                                @change="calculateFillRate()"
                                class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl outline-none focus:border-[#C0420A] transition-all font-bold text-xs">
                            <option value="100% Piña">100% Authentic Piña (Pineapple Fiber)</option>
                            <option value="Piña-Seda">Piña-Seda (Pineapple Silk Blend)</option>
                            <option value="Jusi Silk">Jusi Silk (Classic Traditional)</option>
                            <option value="Cocoon Silk">Cocoon Silk</option>
                            <option value="Organza">Organza</option>
                            <option value="Cotton-Linen">Premium Cotton-Linen</option>
                        </select>
                    </div>
                </div>
            </div>

            {{-- 3. Heritage Sizing & Inventory Matrix --}}
            <div id="sizing-section" class="bg-white p-5 sm:p-6 rounded-2xl border border-gray-100 shadow-xs space-y-4">
                <div class="flex items-center justify-between pb-2 border-b border-gray-100">
                    <div class="flex items-center gap-2.5">
                        <div class="w-7 h-7 rounded-lg bg-[#C0420A]/10 flex items-center justify-center text-[#C0420A] shrink-0 font-bold text-xs">2</div>
                        <div>
                            <h3 class="text-xs sm:text-sm font-black text-gray-900 uppercase tracking-widest leading-none">Heritage Sizing & Stock <span class="text-[#C0420A]">*</span></h3>
                            <p class="text-[10px] text-gray-400 font-medium mt-0.5">Assign stock quantities per size</p>
                        </div>
                    </div>
                    <span class="text-[10px] text-gray-400 font-bold uppercase">At least 1 size required</span>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-2.5">
                    @foreach(['S', 'M', 'L', 'XL', 'XXL', 'Custom'] as $size)
                        <div class="p-2.5 bg-gray-50 border border-gray-200 rounded-xl space-y-2 text-center transition-all hover:bg-white hover:border-gray-300">
                            <label class="flex items-center justify-center gap-1.5 text-xs font-black uppercase text-gray-700 cursor-pointer select-none">
                                <input type="checkbox" 
                                       name="sizes[]" 
                                       value="{{ $size }}" 
                                       id="size_cb_{{ $size }}"
                                       class="rounded text-[#C0420A] focus:ring-[#C0420A] w-3.5 h-3.5 size-checkbox"
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
                                   class="w-full px-2 py-1.5 bg-white border border-gray-200 rounded-lg outline-none text-xs font-bold text-center size-stock-input">
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- 4. Pricing & Logistics Grid --}}
            <div class="bg-white p-5 sm:p-6 rounded-2xl border border-gray-100 shadow-xs space-y-4">
                <div class="flex items-center gap-2.5 pb-2 border-b border-gray-100">
                    <div class="w-7 h-7 rounded-lg bg-[#C0420A]/10 flex items-center justify-center text-[#C0420A] shrink-0 font-bold text-xs">3</div>
                    <h3 class="text-xs sm:text-sm font-black text-gray-900 uppercase tracking-widest leading-none">Price & Shipping Information</h3>
                </div>

                <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
                    {{-- Price Input --}}
                    <div id="price-card" class="p-3.5 bg-[#F9F8F6] border border-stone-200/80 rounded-xl flex flex-col justify-between h-24 sm:h-26 transition-all">
                        <label class="text-[9px] font-bold uppercase tracking-widest text-stone-500">Price (₱) <span class="text-[#C0420A]">*</span></label>
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
                               class="w-full bg-transparent font-sans text-lg font-bold text-gray-900 outline-none border-b border-transparent focus:border-[#C0420A] transition-all">
                        <p class="text-[8px] text-stone-400 font-medium">Item base price</p>
                    </div>

                    {{-- Total Stock (Auto) --}}
                    <div id="stock-card" class="p-3.5 bg-[#F9F8F6] border border-stone-200/80 rounded-xl flex flex-col justify-between h-24 sm:h-26 transition-all">
                        <label class="text-[9px] font-bold uppercase tracking-widest text-stone-500">Total Stock <span class="text-[#C0420A]">*</span></label>
                        <input type="number" 
                               name="stock" 
                               id="total_stock" 
                               min="0" 
                               placeholder="0"
                               readonly 
                               tabindex="-1"
                               class="w-full bg-transparent font-sans text-lg font-bold text-gray-900 outline-none select-none cursor-not-allowed">
                        <p class="text-[8px] text-stone-400 font-medium">Auto-summed from sizes</p>
                    </div>

                    {{-- Shipping Fee --}}
                    <div id="shipping-fee-card" class="p-3.5 bg-[#F9F8F6] border border-stone-200/80 rounded-xl flex flex-col justify-between h-24 sm:h-26 transition-all">
                        <label class="text-[9px] font-bold uppercase tracking-widest text-stone-500">Shipping Fee (₱) <span class="text-[#C0420A]">*</span></label>
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
                               class="w-full bg-transparent font-sans text-lg font-bold text-gray-900 outline-none border-b border-transparent focus:border-[#C0420A] transition-all">
                        <p class="text-[8px] text-stone-400 font-medium">Enter 0 for free delivery</p>
                    </div>

                    {{-- Shipping Days --}}
                    <div id="shipping-days-card" class="p-3.5 bg-[#F9F8F6] border border-stone-200/80 rounded-xl flex flex-col justify-between h-24 sm:h-26 transition-all">
                        <label class="text-[9px] font-bold uppercase tracking-widest text-stone-500">Est. Shipping Days <span class="text-[#C0420A]">*</span></label>
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
                               class="w-full bg-transparent font-sans text-lg font-bold text-gray-900 outline-none border-b border-transparent focus:border-[#C0420A] transition-all">
                        <p class="text-[8px] text-stone-400 font-medium">To deliver</p>
                    </div>
                </div>

                {{-- Lumban Special Discount Panel --}}
                <div class="p-4 rounded-xl border border-[#C0420A]/15 bg-orange-50/20 space-y-3">
                    <input type="hidden" name="is_on_sale" id="isOnSaleInput" value="0">

                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-[#C0420A]"></span>
                            <span class="text-xs font-black text-[#C0420A] uppercase tracking-widest">Special Price / Sale Discount</span>
                            <span class="text-[9px] text-gray-400 font-bold uppercase tracking-wider">(Optional)</span>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer shrink-0">
                            <input type="checkbox" id="discountToggle" class="sr-only peer" onchange="toggleDiscount(this)">
                            <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-0.5 after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-[#C0420A]"></div>
                        </label>
                    </div>

                    <div id="discountFields" class="hidden space-y-2.5 pt-2 border-t border-[#C0420A]/10">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 items-end">
                            <div>
                                <label class="text-[9px] font-bold uppercase tracking-widest text-gray-400 mb-1 block">Discount (%)</label>
                                <input type="number" 
                                       name="discount_percentage" 
                                       id="discountPercentage"
                                       min="1" 
                                       max="99" 
                                       step="1" 
                                       placeholder="e.g. 20"
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

            {{-- 5. Payment Methods Card --}}
            <div id="payment-methods-card" class="bg-white p-5 sm:p-6 rounded-2xl border border-gray-100 shadow-xs space-y-4">
                <div class="flex items-center justify-between pb-2 border-b border-gray-100">
                    <div class="flex items-center gap-2.5">
                        <div class="w-7 h-7 rounded-lg bg-[#C0420A]/10 flex items-center justify-center text-[#C0420A] shrink-0 font-bold text-xs">4</div>
                        <h3 class="text-xs sm:text-sm font-black text-gray-900 uppercase tracking-widest leading-none">Payment Methods <span class="text-[#C0420A]">*</span></h3>
                    </div>
                    <a href="{{ route('seller.profile') }}?open_payment=1#payment-methods" target="_blank" class="text-[11px] font-bold text-[#C0420A] hover:underline flex items-center gap-1">
                        Settings ↗
                    </a>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    {{-- GCash --}}
                    @php 
                        $user = auth()->user(); 
                        $hasGcashNumber = !empty($user->gcashNumber);
                        $hasGcashQr = !empty($user->gcashQrCode);
                        $isGcashComplete = $hasGcashNumber && $hasGcashQr;
                    @endphp
                    <div class="rounded-xl border border-blue-100 overflow-hidden">
                        <div class="flex items-center justify-between px-3 py-2 bg-linear-to-r from-blue-600 to-blue-500">
                            <span class="text-[10px] font-black uppercase tracking-widest text-white">GCash</span>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="product_is_gcash_available" value="1" id="gcash_toggle_create" class="sr-only peer" {{ old('product_is_gcash_available', true) ? 'checked' : '' }} onchange="document.getElementById('gcash_fields_create').style.display = this.checked ? '' : 'none'; calculateFillRate();">
                                <div class="w-8 h-4.5 bg-white/30 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-0.5 after:bg-white after:rounded-full after:h-3.5 after:w-3.5 after:transition-all peer-checked:bg-white/40 border border-white/40"></div>
                            </label>
                        </div>
                        <div id="gcash_fields_create" {{ old('product_is_gcash_available', true) ? '' : 'style=display:none' }} class="p-3 bg-white flex items-center gap-3">
                            <div class="flex-1 min-w-0">
                                @if($isGcashComplete)
                                    <div class="text-xs font-black text-gray-900">{{ $user->gcashNumber }}</div>
                                    <div class="text-[9px] text-blue-500 font-bold uppercase tracking-widest mt-0.5">✓ Ready (Number & QR Set)</div>
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
                    <div class="rounded-xl border border-green-100 overflow-hidden">
                        <div class="flex items-center justify-between px-3 py-2 bg-linear-to-r from-green-600 to-green-500">
                            <span class="text-[10px] font-black uppercase tracking-widest text-white">Maya</span>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="product_is_maya_available" value="1" id="maya_toggle_create" class="sr-only peer" {{ old('product_is_maya_available', false) ? 'checked' : '' }} onchange="document.getElementById('maya_fields_create').style.display = this.checked ? '' : 'none'; calculateFillRate();">
                                <div class="w-8 h-4.5 bg-white/30 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-0.5 after:bg-white after:rounded-full after:h-3.5 after:w-3.5 after:transition-all peer-checked:bg-white/40 border border-white/40"></div>
                            </label>
                        </div>
                        <div id="maya_fields_create" {{ old('product_is_maya_available', false) ? '' : 'style=display:none' }} class="p-3 bg-white flex items-center gap-3">
                            <div class="flex-1 min-w-0">
                                @if($isMayaComplete)
                                    <div class="text-xs font-black text-gray-900">{{ $user->mayaNumber }}</div>
                                    <div class="text-[9px] text-green-600 font-bold uppercase tracking-widest mt-0.5">✓ Ready (Number & QR Set)</div>
                                @else
                                    <div class="text-[10px] text-amber-600 font-bold">Incomplete setup</div>
                                    <a href="{{ route('seller.profile') }}?open_payment=1#payment-methods" class="text-[9px] text-green-600 font-bold underline">Add in Settings →</a>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- 6. Artisan Description & Storytelling Card --}}
            <div class="bg-white p-5 sm:p-6 rounded-2xl border border-gray-100 shadow-xs space-y-4">
                <div class="flex items-center justify-between pb-2 border-b border-gray-100">
                    <div class="flex items-center gap-2.5">
                        <div class="w-7 h-7 rounded-lg bg-[#C0420A]/10 flex items-center justify-center text-[#C0420A] shrink-0 font-bold text-xs">5</div>
                        <div>
                            <h3 class="text-xs sm:text-sm font-black text-gray-900 uppercase tracking-widest leading-none">Artisan Description & Story <span class="text-[#C0420A]">*</span></h3>
                            <p class="text-[10px] text-gray-400 font-medium mt-0.5">Highlight the craftsmanship, weaving techniques, and care instructions</p>
                        </div>
                    </div>

                    {{-- AI Copywriter Button --}}
                    <button type="button" 
                            @click="generateDescriptionAi()"
                            :disabled="isAiLoading"
                            class="text-xs font-bold text-blue-600 hover:text-blue-700 flex items-center gap-1">
                        <svg class="w-3.5 h-3.5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                        <span>AI Copywriter</span>
                    </button>
                </div>

                <div class="relative group">
                    <textarea name="description" 
                              id="artisanDescription" 
                              required 
                              rows="5" 
                              maxlength="500"
                              x-model="description"
                              @input="updateCharCount($el); calculateFillRate();"
                              placeholder="Describe the craftsmanship, cultural heritage, weaving techniques, and unique story behind this piece..."
                              class="w-full px-4 py-3 bg-gray-50/70 border border-gray-200 rounded-xl outline-none focus:border-[#C0420A] focus:bg-white focus:ring-2 focus:ring-[#C0420A]/10 transition-all font-normal text-sm text-gray-800 placeholder:text-gray-400 resize-none pb-8"></textarea>
                    
                    <div class="absolute bottom-2.5 right-3.5 flex items-center gap-1 bg-white/95 backdrop-blur-xs px-2 py-0.5 rounded-md border border-gray-100 text-[10px] font-bold text-gray-400 pointer-events-none shadow-2xs">
                        <span id="charCounter" x-text="description ? description.length : 0">0</span><span class="text-gray-300">/</span><span>500</span>
                    </div>
                </div>
            </div>

            {{-- 7. Bottom Submission Actions (Draft & Publish) --}}
            <div class="pt-4 flex flex-col-reverse sm:flex-row items-center justify-end gap-3.5">
                <button type="button" 
                        @click="submitAsDraft()"
                        class="w-full sm:w-auto px-8 py-3.5 rounded-full border-2 border-gray-300 hover:border-gray-800 bg-white text-gray-700 hover:text-black font-bold text-xs uppercase tracking-widest transition-all">
                    Save as Draft
                </button>

                <button type="submit" 
                        @click="document.getElementById('formActionInput').value = 'publish'"
                        class="w-full sm:w-auto px-10 py-3.5 rounded-full bg-blue-600 hover:bg-blue-700 active:scale-[0.99] text-white font-bold text-xs uppercase tracking-widest shadow-lg shadow-blue-500/25 transition-all">
                    Publish Product
                </button>
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
@endphp

<script id="categories-data-json" type="application/json">
{!! json_encode($categoriesJson, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) !!}
</script>

<script>
let productImagesDT = new DataTransfer();

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

    return {
        step: 1,
        imageCount: 0,
        imagePreviews: [],
        productName: @json(old('name', '')),
        selectedCategory: @json(old('CategoryId', '')),
        targetGroup: @json(old('target_group', 'Men')),
        fabricType: @json(old('fabric_type', '100% Piña')),
        price: @json(old('price', '')),
        description: @json(old('description', '')),
        fillRate: 15,
        isAiLoading: false,

        // Real-time categories state
        categoriesList: parsedCats,
        categoryFilter: 'All',
        categorySearch: '',
        isCategoryDropdownOpen: false,

        init() {
            this.calculateFillRate();
        },

        getTagCount(tag) {
            if (!Array.isArray(this.categoriesList)) return 0;
            return this.categoriesList.filter(c => {
                if (!c || !Array.isArray(c.target_group)) return false;
                return c.target_group.includes(tag);
            }).length;
        },

        filterByTag(tag) {
            this.categoryFilter = tag;
            if (tag !== 'All') {
                this.targetGroup = tag;
            }
            this.calculateFillRate();
        },

        get filteredCategories() {
            if (!Array.isArray(this.categoriesList)) return [];
            let list = this.categoriesList;
            if (this.categoryFilter !== 'All') {
                list = list.filter(c => {
                    if (!c) return false;
                    if (Array.isArray(c.target_group)) return c.target_group.includes(this.categoryFilter);
                    if (typeof c.target_group === 'string') return c.target_group === this.categoryFilter;
                    return false;
                });
            }
            if (this.categorySearch && this.categorySearch.trim() !== '') {
                const q = this.categorySearch.toLowerCase().trim();
                list = list.filter(c => c && c.name && c.name.toLowerCase().includes(q));
            }
            return list;
        },

        get selectedCategoryObj() {
            if (!Array.isArray(this.categoriesList)) return null;
            return this.categoriesList.find(c => c && c.id === this.selectedCategory) || null;
        },

        selectCategory(cat) {
            if (!cat) return;
            this.selectedCategory = cat.id;
            let tags = Array.isArray(cat.target_group) ? cat.target_group : [];
            if (tags.length > 0) {
                // Auto-sync demographic radio if category has assigned target_group in Admin
                this.targetGroup = tags[0];
            }
            this.isCategoryDropdownOpen = false;
            this.calculateFillRate();
        },

        onTargetGroupChange(group) {
            this.targetGroup = group;
            this.calculateFillRate();
        },

        goToStep2() {
            if (this.imageCount === 0) {
                triggerAppModal('Cover Image Required', 'Please upload at least one product photo to proceed.', 'warning');
                const dropZone = document.getElementById('dropZone');
                if (dropZone) dropZone.classList.add('border-red-500');
                return;
            }
            this.step = 2;
            this.calculateFillRate();
            setTimeout(() => {
                window.scrollTo({ top: 320, behavior: 'smooth' });
            }, 100);
        },

        handleFileChange(event) {
            const input = event.target;
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
                        if (productImagesDT.items.length < 8) {
                            productImagesDT.items.add(file);
                        }
                    }
                });

                if (oversizedCount > 0) {
                    triggerAppModal('Image Exceeds 5MB', `${oversizedCount} photo(s) exceeded the 5MB size limit and were skipped.`, 'warning');
                } else if (duplicateCount > 0) {
                    triggerAppModal('Duplicate Image Skipped', `${duplicateCount} duplicate image(s) already added.`, 'warning');
                }

                input.files = productImagesDT.files;
                this.syncPreviews();
            }
        },

        removeImage(index) {
            const input = document.getElementById('imageUploadInput');
            const newDT = new DataTransfer();
            Array.from(productImagesDT.files).forEach((file, i) => {
                if (i !== index) newDT.items.add(file);
            });
            productImagesDT = newDT;
            if (input) input.files = productImagesDT.files;
            this.syncPreviews();
        },

        syncPreviews() {
            const files = Array.from(productImagesDT.files);
            this.imageCount = files.length;
            this.imagePreviews = [];

            files.forEach((file, idx) => {
                const reader = new FileReader();
                reader.onload = (e) => {
                    this.imagePreviews.push({ url: e.target.result, name: file.name });
                };
                reader.readAsDataURL(file);
            });

            this.calculateFillRate();
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
                if (productImagesDT.files.length > 0) {
                    formData.append('image', productImagesDT.files[0]);
                }
                formData.append('current_name', this.productName || '');
                formData.append('current_category', this.selectedCategory || '');
                formData.append('_token', @json(csrf_token()));

                const response = await fetch(@json(route('ai.seller.suggest')), {
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
            this.isAiLoading = true;
            try {
                const response = await fetch(@json(route('ai.seller.description')), {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': @json(csrf_token())
                    },
                    body: JSON.stringify({
                        fabric: this.fabricType || '100% Piña',
                        category: this.selectedCategory || 'Barong Tagalog',
                        theme: 'Wedding & Formal'
                    })
                });
                if (response.ok) {
                    const data = await response.json();
                    if (data.description) {
                        this.description = data.description;
                        this.calculateFillRate();
                    }
                }
            } catch (e) {
                console.error(e);
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

    // 6. Payment Methods
    const gcashToggle = document.getElementById('gcash_toggle_create');
    const mayaToggle = document.getElementById('maya_toggle_create');
    const paymentCard = document.getElementById('payment-methods-card');
    const isGcashChecked = gcashToggle ? gcashToggle.checked : false;
    const isMayaChecked = mayaToggle ? mayaToggle.checked : false;

    const paymentConfig = {
        hasGcashNumber: @json(!empty($user->gcashNumber)),
        hasGcashQr: @json(!empty($user->gcashQrCode)),
        hasMayaNumber: @json(!empty($user->mayaNumber)),
        hasMayaQr: @json(!empty($user->mayaQrCode)),
    };
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
