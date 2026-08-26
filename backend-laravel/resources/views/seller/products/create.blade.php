@extends('layouts.seller')

@section('content')
<div class="max-w-4xl mx-auto pb-36 sm:pb-28 lg:pb-16 px-3 sm:px-6" x-data="addProductManager()">
    {{-- Top Header & Navigation --}}
    <div style="margin-bottom:24px;display:flex;flex-direction:column;gap:16px;">
        <div>
            <a href="{{ route('seller.products.index') }}" style="display:inline-flex;align-items:center;gap:8px;font-size:11px;font-weight:700;color:#78716C;text-transform:uppercase;letter-spacing:0.08em;text-decoration:none;margin-bottom:10px;transition:color 0.2s;">
                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                Back to catalogue
            </a>
            <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;flex-wrap:wrap;">
                <div style="display:flex;align-items:center;gap:14px;">
                    <!-- Heraldic Laurel Wreath + Star Emblem (From Top Rated Shops Modal) -->
                    <div style="width:48px;height:48px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <svg width="46" height="46" viewBox="0 0 48 48" fill="none">
                            <!-- Central Medallion -->
                            <circle cx="24" cy="23" r="10.5" stroke="#C49520" stroke-width="1" stroke-dasharray="2 1.5"/>
                            <circle cx="24" cy="23" r="8.5" stroke="#C49520" stroke-width="0.8"/>
                            <path d="M24 17.5l1.6 3.4 3.7.5-2.7 2.6.6 3.7-3.2-1.7-3.2 1.7.6-3.7-2.7-2.6 3.7-.5L24 17.5z" fill="#C49520"/>
                            <!-- Laurel Wreath Left -->
                            <path d="M15 32.5c-4-3.5-6-8.5-6-14 0-3.5 1-6.5 2.5-9" stroke="#C49520" stroke-width="1.3" stroke-linecap="round"/>
                            <path d="M10 12c1.8 1.2 3.5 2.8 4 4.5M8 17.5c2 .6 3.8 1.8 4.8 3.5M8 23.5c2 0 3.8.6 5 2M9.5 29.5c2-.8 3.8-.8 5.2 0M12.5 34c1.8-1.2 3.6-1.5 5-.8" stroke="#C49520" stroke-width="1.2" stroke-linecap="round"/>
                            <!-- Laurel Wreath Right -->
                            <path d="M33 32.5c4-3.5 6-8.5 6-14 0-3.5-1-6.5-2.5-9" stroke="#C49520" stroke-width="1.3" stroke-linecap="round"/>
                            <path d="M38 12c-1.8 1.2-3.5 2.8-4 4.5M40 17.5c-2 .6-3.8 1.8-4.8 3.5M40 23.5c-2 0-3.8.6-5 2M38.5 29.5c-2-.8-3.8-.8-5.2 0M35.5 34c-1.8-1.2-3.6-1.5-5-.8" stroke="#C49520" stroke-width="1.2" stroke-linecap="round"/>
                            <!-- Base Ribbon -->
                            <path d="M19 36c3 1.2 7 1.2 10 0" stroke="#C49520" stroke-width="1.3" stroke-linecap="round"/>
                        </svg>
                    </div>
                    <div>
                        <h1 style="font-family:ui-serif,Georgia,Cambria,serif;font-size:24px;font-weight:700;color:#1E1915;letter-spacing:-0.01em;line-height:1.2;margin:0;">
                            New Heritage Piece
                        </h1>
                        <p style="font-size:13px;color:#78716C;margin-top:3px;margin-bottom:0;">
                            List a new handcrafted Lumban creation for discerning buyers
                        </p>
                    </div>
                </div>

                {{-- Step Indicator Badge (Artisan Pill Style) --}}
                <div style="display:flex;align-items:center;gap:8px;">
                    <span style="font-size:12px;font-weight:700;border-radius:20px;padding:5px 14px;display:flex;align-items:center;gap:6px;box-shadow:0 1px 3px rgba(0,0,0,0.03);transition:all 0.2s;"
                          :style="step === 1 ? 'background-color:#FDF8EE;border:1px solid #EEDBBA;color:#7A5505;' : 'background-color:#1C160E;border:1px solid #1C160E;color:#FAF6F0;'">
                        <span style="width:7px;height:7px;border-radius:50%;" :style="step === 1 ? 'background-color:#C49520;' : 'background-color:#10B981;'"></span>
                        <span x-text="step === 1 ? 'Step 1: Media & Core Info' : 'Step 2: Specifications'"></span>
                    </span>
                </div>
            </div>
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
        {{-- PHASE 1: IMAGE FIRST & CORE IDENTIFICATION (Exact Top Rated Shops Theme) --}}
        {{-- ========================================================================= --}}
        <div style="background-color:#FDFBF7 !important;border:1px solid #EAE2D2 !important;border-radius:28px !important;box-shadow:0 10px 40px rgba(0,0,0,0.06) !important;padding:24px sm:padding:30px;color:#1E1915 !important;" class="p-5 sm:p-8 space-y-6">
            
            {{-- 1. Product Media & Variants (Unified: Variant 1 is Cover Photo & Product Name) --}}
            <div class="space-y-4">
                <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;">
                    <div>
                        <h2 style="font-family:ui-serif,Georgia,Cambria,serif;font-size:18px;font-weight:700;color:#1E1915;margin:0;line-height:1.2;">
                            1. Product Media & Variants <span style="color:#C49520;">*</span>
                        </h2>
                        <p style="font-size:12px;color:#78716C;margin-top:4px;margin-bottom:0;">
                            Variant 1 represents your main product style, cover photo, and product name.
                        </p>
                    </div>
                    <span style="background-color:#FDF8EE;border:1px solid #EEDBBA;color:#7A5505;font-size:11px;font-weight:700;border-radius:20px;padding:3px 10px;flex-shrink:0;" x-text="variants.length + ' style(s)'"></span>
                </div>

                {{-- Variant 1: Main Product Style & Cover Photo (Unified with Product Name) --}}
                <div style="background-color:#FFFFFF !important;border:1px solid #ECE3D2 !important;border-radius:20px !important;padding:20px !important;box-shadow:0 2px 6px rgba(0,0,0,0.03) !important;" class="space-y-4" id="variant_card_0">
                    <div style="display:flex;align-items:center;justify-content:space-between;padding-bottom:10px;border-bottom:1px solid #F2ECE1;">
                        <div style="display:flex;align-items:center;gap:8px;">
                            <span style="width:8px;height:8px;border-radius:50%;background-color:#C49520;"></span>
                            <span style="font-family:ui-serif,Georgia,serif;font-size:15px;font-weight:700;color:#1E1915;">
                                Variant 1 (Main Style / Cover)
                            </span>
                            <span style="background-color:#FDF8EE;border:1px solid #EEDBBA;color:#7A5505;font-size:10px;font-weight:700;border-radius:20px;padding:2px 8px;text-transform:uppercase;letter-spacing:0.04em;">Cover Image</span>
                        </div>
                        <span style="font-size:11px;color:#8C827A;font-weight:500;" class="hidden sm:inline">Primary Product Listing</span>
                    </div>

                    {{-- Hidden inputs for Variant 1 mapping --}}
                    <input type="hidden" name="variant_indexes[]" value="0">
                    <input type="hidden" name="variant_names[0]" :value="productName || 'Original Style'">

                    {{-- 1. Product Name (English) --}}
                    <div class="space-y-1.5">
                        <div style="display:flex;align-items:center;justify-content:space-between;">
                            <label style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#1E1915;">
                                Product Name (English) <span style="color:#C49520;">*</span>
                                <span style="font-size:10px;color:#A8A096;font-weight:400;" x-text="'(' + (productName ? productName.length : 0) + '/100)'"></span>
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
                                   style="width:100%;padding:13px 16px;background-color:#FAF8F5;border:1px solid #E2D9C8;border-radius:14px;font-size:14px;font-weight:600;color:#1E1915;outline:none;transition:all 0.2s;"
                                   onfocus="this.style.borderColor='#C49520';this.style.backgroundColor='#FFFFFF';"
                                   onblur="this.style.borderColor='#E2D9C8';this.style.backgroundColor='#FAF8F5';"
                                   class="pr-10 shadow-2xs">
                            
                            {{-- Clear Button (X) --}}
                            <button type="button" 
                                    x-show="productName && productName.length > 0"
                                    @click="productName = ''; calculateFillRate();"
                                    style="position:absolute;right:12px;color:#A8A096;background:none;border:none;cursor:pointer;">
                                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                            </button>
                        </div>
                    </div>

                    {{-- 2. Cover Photo Upload --}}
                    <div class="space-y-1.5 pt-1">
                        <label style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#1E1915;display:block;">
                            Variant 1 Photo (Cover Image) <span style="color:#C49520;">*</span>
                        </label>
                        <div class="flex flex-col sm:flex-row items-start sm:items-center gap-4 sm:gap-5">
                            <div style="width:140px;height:140px;position:relative;flex-shrink:0;">
                                <label for="variant_file_0"
                                       id="variant_upload_box_0"
                                       style="width:140px;height:140px;border-radius:18px;border:2px dashed #E2D9C8;background-color:#FAF8F5;display:flex;flex-direction:column;align-items:center;justify-content:center;cursor:pointer;overflow:hidden;position:relative;transition:all 0.2s;"
                                       onmouseover="this.style.borderColor='#C49520';this.style.backgroundColor='#FFFFFF';"
                                       onmouseout="this.style.borderColor='#E2D9C8';this.style.backgroundColor='#FAF8F5';"
                                       class="group/img shadow-2xs select-none">
                                    
                                    <template x-if="variants[0].imagePreview">
                                        <div style="position:relative;width:100%;height:100%;">
                                            <img :src="variants[0].imagePreview" style="width:100%;height:100%;object-fit:cover;">
                                            <div class="absolute inset-0 bg-black/40 opacity-0 group-hover/img:opacity-100 transition-opacity flex flex-col items-center justify-center text-white text-[10px] font-bold uppercase tracking-wider gap-1">
                                                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                                <span>Change</span>
                                            </div>
                                            <div style="position:absolute;bottom:6px;left:6px;right:6px;background:rgba(0,0,0,0.65);backdrop-filter:blur(4px);padding:3px 0;border-radius:6px;text-align:center;font-size:9px;font-weight:700;color:#FFFFFF;text-transform:uppercase;letter-spacing:0.04em;">
                                                Cover Image
                                            </div>
                                        </div>
                                    </template>

                                    <template x-if="!variants[0].imagePreview">
                                        <div style="display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;padding:12px;">
                                            <div style="width:40px;height:40px;border-radius:50%;background-color:#FDF8EE;border:1px solid #EEDBBA;display:flex;align-items:center;justify-content:center;color:#C49520;margin-bottom:6px;" class="group-hover/img:scale-105 transition-transform">
                                                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                            </div>
                                            <span style="font-size:12px;font-weight:700;color:#7A5505;">+ Upload Photo</span>
                                            <span style="font-size:10px;color:#A8A096;margin-top:2px;">JPEG, PNG, WEBP</span>
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
                                        style="position:absolute;top:-6px;right:-6px;width:24px;height:24px;background-color:#DC2626;color:#FFFFFF;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:10px;font-weight:900;border:none;cursor:pointer;box-shadow:0 2px 6px rgba(0,0,0,0.2);"
                                        title="Remove photo">
                                    ✕
                                </button>
                            </div>

                            <div class="flex-1 space-y-1.5 text-xs text-[#78716C]">
                                <h4 style="font-family:ui-serif,Georgia,serif;font-size:14px;font-weight:700;color:#1E1915;margin:0;">
                                    Primary Product Appearance
                                </h4>
                                <p style="font-size:12px;color:#78716C;line-height:1.5;margin-top:4px;margin-bottom:0;">
                                    This photo will be showcased as the main thumbnail across the store, search, and catalogue.
                                </p>
                                <p style="font-size:11px;color:#7A5505;font-weight:600;margin-top:6px;margin-bottom:0;">
                                    <span style="color:#C49520;">✦</span> Have other colors, fabrics, or sleeve styles? Click "+ Add Another Variant" below.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Additional Variants List (Variant 2, 3, etc.) --}}
                <div class="space-y-3">
                    <template x-for="(variant, index) in variants" :key="variant.id">
                        <div x-show="index > 0" style="background-color:#FFFFFF !important;border:1px solid #ECE3D2 !important;border-radius:18px !important;padding:16px !important;box-shadow:0 2px 6px rgba(0,0,0,0.02) !important;" class="space-y-3">
                            <div style="display:flex;align-items:center;justify-content:space-between;padding-bottom:8px;border-bottom:1px solid #F2ECE1;">
                                <div style="display:flex;align-items:center;gap:8px;">
                                    <span style="width:6px;height:6px;border-radius:50%;background-color:#C49520;"></span>
                                    <span style="font-family:ui-serif,Georgia,serif;font-size:14px;font-weight:700;color:#1E1915;" x-text="'Variant ' + (index + 1)"></span>
                                    <span style="background-color:#FAF8F5;border:1px solid #E2D9C8;color:#78716C;font-size:9px;font-weight:700;border-radius:20px;padding:2px 8px;text-transform:uppercase;letter-spacing:0.04em;">Style Option</span>
                                </div>
                                <button type="button" 
                                        @click="removeVariantRow(index)" 
                                        style="font-size:12px;font-weight:600;color:#DC2626;background:none;border:none;cursor:pointer;display:flex;align-items:center;gap:4px;">
                                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    <span>Remove</span>
                                </button>
                            </div>

                            {{-- Hidden input mapping for PHP --}}
                            <input type="hidden" name="variant_indexes[]" :value="index">

                            <div style="display:flex;align-items:center;gap:14px;">
                                {{-- Variant Image Box --}}
                                <div style="width:80px;height:80px;position:relative;flex-shrink:0;">
                                    <label :for="'variant_file_' + index"
                                           style="width:80px;height:80px;border-radius:14px;border:2px dashed #E2D9C8;background-color:#FAF8F5;display:flex;flex-direction:column;align-items:center;justify-content:center;cursor:pointer;overflow:hidden;position:relative;transition:all 0.2s;"
                                           onmouseover="this.style.borderColor='#C49520';this.style.backgroundColor='#FFFFFF';"
                                           onmouseout="this.style.borderColor='#E2D9C8';this.style.backgroundColor='#FAF8F5';"
                                           class="group/img shadow-2xs select-none">
                                        <template x-if="variant.imagePreview">
                                            <div style="position:relative;width:100%;height:100%;">
                                                <img :src="variant.imagePreview" style="width:100%;height:100%;object-fit:cover;">
                                                <div class="absolute inset-0 bg-black/30 opacity-0 group-hover/img:opacity-100 transition-opacity flex items-center justify-center text-white text-[9px] font-bold uppercase tracking-wider">
                                                    Change
                                                </div>
                                            </div>
                                        </template>
                                        <template x-if="!variant.imagePreview">
                                            <div style="display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;padding:4px;">
                                                <div style="width:24px;height:24px;border-radius:50%;background-color:#FDF8EE;border:1px solid #EEDBBA;display:flex;align-items:center;justify-content:center;color:#C49520;margin-bottom:2px;">
                                                    <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                                </div>
                                                <span style="font-size:9px;font-weight:700;color:#7A5505;">+ Photo</span>
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
                                            style="position:absolute;top:-5px;right:-5px;width:20px;height:20px;background-color:#DC2626;color:#FFFFFF;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:9px;font-weight:900;border:none;cursor:pointer;"
                                            title="Remove photo">
                                        ✕
                                    </button>
                                </div>

                                {{-- Variant Name Input --}}
                                <div style="flex:1;min-width:0;">
                                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:4px;">
                                        <label style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.04em;color:#1E1915;">
                                            Variant Name <span style="color:#C49520;">*</span>
                                        </label>
                                        <span style="font-size:9px;color:#A8A096;" class="hidden sm:inline">e.g. Color, embroidery, or style</span>
                                    </div>
                                    <input type="text" 
                                           :name="'variant_names[' + index + ']'" 
                                           x-model="variant.name" 
                                           placeholder="e.g. Emerald Green, Ivory Piña, Short Sleeve..." 
                                           style="width:100%;padding:10px 14px;background-color:#FAF8F5;border:1px solid #E2D9C8;border-radius:12px;font-size:13px;font-weight:600;color:#1E1915;outline:none;transition:all 0.2s;"
                                           onfocus="this.style.borderColor='#C49520';this.style.backgroundColor='#FFFFFF';"
                                           onblur="this.style.borderColor='#E2D9C8';this.style.backgroundColor='#FAF8F5';">
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

                {{-- Add Another Variant Button --}}
                <div>
                    <button type="button" 
                            @click="addVariantRow()" 
                            style="width:100%;padding:14px 20px;border-radius:18px;border:1px dashed #C49520;background-color:#FAF8F5;color:#1E1915;font-size:13px;font-weight:700;display:flex;align-items:center;justify-content:center;gap:8px;cursor:pointer;transition:all 0.2s;"
                            onmouseover="this.style.backgroundColor='#FDFBF7';this.style.borderColor='#7A5505';"
                            onmouseout="this.style.backgroundColor='#FAF8F5';this.style.borderColor='#C49520';">
                        <span style="color:#C49520;font-size:16px;font-weight:700;line-height:1;">+</span>
                        <span>Add Another Variant (Optional Style / Color)</span>
                    </button>
                </div>
            </div>

            {{-- Star Divider (Exact from Top Rated Shops Modal) --}}
            <div style="position:relative;margin:24px 0;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <div style="width:100%;border-top:1px solid #EAE1D0;"></div>
                <span style="position:absolute;background-color:#FDFBF7;padding:0 14px;color:#C49520;font-size:13px;">✦</span>
            </div>

            {{-- 2. Target Tag & Dynamic Matching Category Selection --}}
            <div class="space-y-4">
                {{-- Step A: Who is this for? (Target Tag) --}}
                <div class="space-y-1.5">
                    <div style="display:flex;align-items:center;justify-content:space-between;">
                        <h2 style="font-family:ui-serif,Georgia,Cambria,serif;font-size:18px;font-weight:700;color:#1E1915;margin:0;">
                            2. Who is this for? (Target Tag) <span style="color:#C49520;">*</span>
                        </h2>
                        <span style="font-size:12px;font-weight:700;transition:color 0.2s;"
                              :style="targetGroup ? 'color:#10B981;' : 'color:#C49520;'"
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
                            <label class="cursor-pointer" @click="onTargetGroupChange('{{ $group }}')">
                                <input type="radio" 
                                       name="target_group" 
                                       value="{{ $group }}" 
                                       x-model="targetGroup" 
                                       class="hidden">
                                <div style="width:100%;padding:14px 12px;border-radius:18px;border:1px solid #E2D9C8;font-size:13px;font-weight:700;text-align:center;text-transform:uppercase;letter-spacing:0.04em;display:flex;align-items:center;justify-content:center;gap:8px;transition:all 0.2s;box-shadow:0 1px 3px rgba(0,0,0,0.03);"
                                     :style="targetGroup === '{{ $group }}' 
                                        ? 'background-color:#1C160E !important;color:#FAF6F0 !important;border-color:#1C160E !important;box-shadow:0 4px 12px rgba(28,22,14,0.18) !important;' 
                                        : 'background-color:#FAF8F5 !important;color:#44403C !important;border-color:#E2D9C8 !important;'">
                                    <span style="font-size:16px;">{{ $emoji }}</span>
                                    <span>{{ $group }}</span>
                                    <span x-show="targetGroup === '{{ $group }}'" style="width:16px;height:16px;border-radius:50%;background-color:#C49520;color:#FFFFFF;display:inline-flex;align-items:center;justify-content:center;font-size:9px;font-weight:800;margin-left:4px;">✓</span>
                                </div>
                            </label>
                        @endforeach
                    </div>
                </div>

                {{-- Step B: Product Category matching selected tag --}}
                <div class="space-y-1.5 pt-2">
                    <div style="display:flex;align-items:center;justify-content:space-between;">
                        <h3 style="font-family:ui-serif,Georgia,serif;font-size:15px;font-weight:700;color:#1E1915;margin:0;">
                            Product Category <span x-show="targetGroup" x-text="'for ' + targetGroup"></span> <span style="color:#C49520;">*</span>
                        </h3>
                        <span style="font-size:12px;font-weight:700;transition:color 0.2s;"
                              :style="selectedCategory ? 'color:#10B981;' : 'color:#C49520;'"
                              x-text="selectedCategory && selectedCategoryObj ? ('✓ ' + selectedCategoryObj.name) : (targetGroup ? 'Choose from options below' : 'Select tag above first')"></span>
                    </div>

                    {{-- Hidden CategoryId input for form submission --}}
                    <input type="hidden" name="CategoryId" id="categorySelect" :value="selectedCategory" required>

                    {{-- Prompt when NO tag is selected (Exact Sand Banner Style) --}}
                    <div x-show="!targetGroup" style="padding:14px 18px;border-radius:18px;background:linear-gradient(90deg,#F6F0E4 0%,#F2EADA 50%,#EAE0CD 100%);border:1px solid #E2D6C0;color:#78716C;font-size:13px;font-weight:500;display:flex;align-items:center;gap:10px;">
                        <span style="font-size:16px;flex-shrink:0;">👆</span>
                        <span>Please select who this product is for (<strong>Men</strong>, <strong>Women</strong>, or <strong>Kids</strong>) above to display matching categories.</span>
                    </div>

                    {{-- Category grid displayed when a tag is picked --}}
                    <div x-show="targetGroup" class="space-y-2" x-cloak>
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-2.5 max-h-60 overflow-y-auto p-3" style="background-color:#FAF8F5;border:1px solid #EAE1D0;border-radius:20px;box-shadow:0 1px 4px rgba(0,0,0,0.02);" id="category-cards-container">
                            <template x-for="cat in filteredCategories" :key="cat.id">
                                <button type="button" 
                                        @click="selectCategory(cat)"
                                        style="padding:13px 15px;border-radius:14px;display:flex;align-items:center;justify-content:space-between;text-align:left;transition:all 0.2s;cursor:pointer;font-size:13px;"
                                        :style="selectedCategory === cat.id 
                                            ? 'background-color:#FDF8EE !important;border:2px solid #C49520 !important;color:#7A5505 !important;font-weight:700 !important;box-shadow:0 2px 8px rgba(196,149,32,0.15) !important;' 
                                            : 'background-color:#FFFFFF !important;border:1px solid #ECE3D2 !important;color:#1E1915 !important;font-weight:600 !important;'">
                                    <span class="truncate" x-text="cat.name"></span>
                                    <span x-show="selectedCategory === cat.id" style="width:16px;height:16px;border-radius:50%;background-color:#C49520;color:#FFFFFF;display:inline-flex;align-items:center;justify-content:center;font-size:9px;font-weight:800;flex-shrink:0;margin-left:6px;">✓</span>
                                </button>
                            </template>

                            <template x-if="filteredCategories.length === 0">
                                <div class="col-span-full py-6 text-center text-xs text-[#78716C] font-medium">
                                    No categories found for this tag.
                                </div>
                            </template>
                        </div>

                        <p x-show="selectedCategory && selectedCategoryObj" style="font-size:12px;color:#78716C;font-weight:500;padding:0 4px;margin:0;">
                            Selected Category: <strong style="color:#1E1915;font-weight:700;" x-text="selectedCategoryObj.name"></strong>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Step 1 Primary CTA Button: Sleek Onyx Pill Button matching "View Shop →" --}}
        <div x-show="step === 1" style="margin-top:20px;display:flex;flex-direction:column;gap:12px;">
            <button type="button" 
                    @click="goToStep2()"
                    :disabled="!isStep1Complete"
                    style="width:100%;padding:16px 28px;border-radius:9999px;font-size:14px;font-weight:700;letter-spacing:0.02em;display:flex;align-items:center;justify-content:center;gap:10px;border:none;box-shadow:0 4px 14px rgba(28,22,14,0.12);transition:all 0.2s;"
                    :style="isStep1Complete 
                        ? 'background-color:#1C160E !important;color:#FAF6F0 !important;cursor:pointer;' 
                        : 'background-color:#E5E0D8 !important;color:#A8A096 !important;cursor:not-allowed;box-shadow:none;'">
                <span>Next: Complete Product Details</span>
                <span style="font-size:15px;line-height:1;font-weight:700;">→</span>
            </button>

            {{-- Status prompt when fields are missing --}}
            <template x-if="!isStep1Complete">
                <p style="text-align:center;font-size:12px;color:#78716C;margin:0;display:flex;align-items:center;justify-content:center;gap:6px;">
                    <span style="color:#C49520;">✦</span>
                    <span>Please upload a photo, enter product name, choose a category, and select target tag to proceed.</span>
                </p>
            </template>

            {{-- Verified & Trusted Footer Banner (Exact from welcome.blade.php) --}}
            <div style="margin-top:10px;padding:16px 20px;border-radius:18px;background:linear-gradient(90deg,#F6F0E4 0%,#F2EADA 50%,#EAE0CD 100%);border:1px solid #E2D6C0;display:flex;align-items:center;justify-content:space-between;gap:12px;position:relative;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,0.03);">
                <div style="display:flex;align-items:center;gap:12px;position:relative;z-index:10;">
                    <div style="width:34px;height:34px;border-radius:50%;border:2px solid #B88728;background-color:#FAF4EA;display:flex;align-items:center;justify-content:center;color:#B88728;flex-shrink:0;box-shadow:0 1px 2px rgba(0,0,0,0.05);">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                        </svg>
                    </div>
                    <div>
                        <h5 style="font-size:13px;font-weight:700;color:#1E1915;margin:0;line-height:1.2;">All artisan listings are verified and trusted</h5>
                        <p style="font-size:11px;color:#78716C;margin:2px 0 0 0;">Quality craftsmanship. Authentic Lumban, Laguna Filipino heritage.</p>
                    </div>
                </div>
                <!-- Background Embroidery Flourish Watermark -->
                <svg width="120" height="70" viewBox="0 0 120 80" fill="#C49520" style="position:absolute;right:8px;bottom:-10px;opacity:0.18;pointer-events:none;">
                    <path d="M60 10C40 10 30 30 10 35C30 40 40 60 60 60C80 60 90 40 110 35C90 30 80 10 60 10ZM60 25C65 25 70 30 70 35C70 40 65 45 60 45C55 45 50 40 50 35C50 30 55 25 60 25Z"/>
                </svg>
            </div>
        </div>
        
        {{-- ========================================================================= --}}
        {{-- PHASE 2: COMPLETE SPECIFICATIONS (Progressively Revealed after Step 1)     --}}
        {{-- ========================================================================= --}}
        <div x-show="step >= 2" x-collapse class="space-y-6">

            {{-- 1. Fill Rate & Listing Health Bar --}}
            <div style="background-color:#FFFFFF !important;border:1px solid #EAE2D2 !important;border-radius:24px !important;padding:20px 24px !important;box-shadow:0 4px 20px rgba(0,0,0,0.04) !important;" class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <span style="font-family:ui-serif,Georgia,serif;font-size:14px;font-weight:700;color:#1E1915;">Listing Completeness</span>
                    <div style="width:160px;height:10px;background-color:#FAF8F5;border:1px solid #E2D9C8;border-radius:9999px;overflow:hidden;">
                        <div style="height:100%;background:linear-gradient(90deg,#C49520 0%,#1C160E 100%);border-radius:9999px;transition:width 0.5s;" :style="'width: ' + fillRate + '%'"></div>
                    </div>
                    <span style="font-size:12px;font-weight:800;color:#7A5505;" x-text="fillRate + '%'"></span>
                    <span style="color:#C49520;font-size:11px;">✦</span>
                </div>

                <div class="flex items-center gap-2">
                    <span style="font-size:12px;color:#78716C;font-weight:500;">All essential artisan specifications</span>
                </div>
            </div>

            {{-- Hidden Input for Fabric Type --}}
            <input type="hidden" name="fabric_type" :value="fabricType || '100% Piña'">

            {{-- 1. Heritage Sizing & Inventory Matrix --}}
            <div id="sizing-section" style="background-color:#FDFBF7 !important;border:1px solid #EAE2D2 !important;border-radius:24px !important;padding:24px !important;box-shadow:0 4px 20px rgba(0,0,0,0.03) !important;" class="space-y-4">
                <div style="display:flex;align-items:center;justify-content:space-between;padding-bottom:12px;border-bottom:1px solid #EAE1D0;">
                    <div style="display:flex;align-items:center;gap:12px;">
                        <div style="width:32px;height:32px;border-radius:50%;background-color:#FDF8EE;border:1px solid #EEDBBA;display:flex;align-items:center;justify-content:center;color:#7A5505;font-family:ui-serif,Georgia,serif;font-weight:700;font-size:13px;flex-shrink:0;">1</div>
                        <div>
                            <h3 style="font-family:ui-serif,Georgia,Cambria,serif;font-size:16px;font-weight:700;color:#1E1915;margin:0;">Heritage Sizing & Stock <span style="color:#C49520;">*</span></h3>
                            <p style="font-size:12px;color:#78716C;margin-top:2px;margin-bottom:0;">Assign available inventory quantities per size</p>
                        </div>
                    </div>
                    <span style="font-size:10px;font-weight:700;color:#7A5505;background-color:#FDF8EE;border:1px solid #EEDBBA;padding:3px 10px;border-radius:20px;text-transform:uppercase;letter-spacing:0.04em;">At least 1 size required</span>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-2.5 pt-1">
                    @foreach(['S', 'M', 'L', 'XL', 'XXL', 'Custom'] as $size)
                        <div style="background-color:#FFFFFF;border:1px solid #E2D9C8;border-radius:16px;padding:12px;text-align:center;box-shadow:0 1px 3px rgba(0,0,0,0.02);transition:all 0.2s;" class="space-y-2 hover:border-[#C49520]">
                            <label style="display:flex;align-items:center;justify-content:center;gap:6px;font-size:12px;font-weight:700;text-transform:uppercase;color:#1E1915;cursor:pointer;">
                                <input type="checkbox" 
                                       name="sizes[]" 
                                       value="{{ $size }}" 
                                       id="size_cb_{{ $size }}"
                                       class="rounded text-[#1C160E] focus:ring-[#C49520] w-3.5 h-3.5 size-checkbox"
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
                                   style="width:100%;padding:6px 8px;background-color:#FAF8F5;border:1px solid #E2D9C8;border-radius:10px;outline:none;font-size:13px;font-weight:700;text-align:center;color:#1E1915;">
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- 2. Pricing & Logistics Grid --}}
            <div style="background-color:#FDFBF7 !important;border:1px solid #EAE2D2 !important;border-radius:24px !important;padding:24px !important;box-shadow:0 4px 20px rgba(0,0,0,0.03) !important;" class="space-y-4">
                <div style="display:flex;align-items:center;gap:12px;padding-bottom:12px;border-bottom:1px solid #EAE1D0;">
                    <div style="width:32px;height:32px;border-radius:50%;background-color:#FDF8EE;border:1px solid #EEDBBA;display:flex;align-items:center;justify-content:center;color:#7A5505;font-family:ui-serif,Georgia,serif;font-weight:700;font-size:13px;flex-shrink:0;">2</div>
                    <div>
                        <h3 style="font-family:ui-serif,Georgia,Cambria,serif;font-size:16px;font-weight:700;color:#1E1915;margin:0;">Price & Shipping Information</h3>
                        <p style="font-size:12px;color:#78716C;margin-top:2px;margin-bottom:0;">Define fair artisan pricing and realistic delivery estimates</p>
                    </div>
                </div>

                <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 pt-1">
                    {{-- Price Input --}}
                    <div id="price-card" style="background-color:#FFFFFF;border:1px solid #E2D9C8;border-radius:16px;padding:14px;display:flex;flex-direction:column;justify-content:space-between;height:100px;box-shadow:0 1px 3px rgba(0,0,0,0.02);">
                        <label style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#78716C;">Price (₱) <span style="color:#C49520;">*</span></label>
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
                               style="width:100%;background:transparent;font-size:18px;font-weight:700;color:#1E1915;outline:none;border:none;">
                        <p style="font-size:9px;color:#A8A096;margin:0;">Item base price</p>
                    </div>

                    {{-- Total Stock (Auto) --}}
                    <div id="stock-card" style="background-color:#FAF8F5;border:1px solid #E2D9C8;border-radius:16px;padding:14px;display:flex;flex-direction:column;justify-content:space-between;height:100px;box-shadow:0 1px 3px rgba(0,0,0,0.02);">
                        <label style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#78716C;">Total Stock <span style="color:#C49520;">*</span></label>
                        <input type="number" 
                               name="stock" 
                               id="total_stock" 
                               min="0" 
                               placeholder="0"
                               readonly 
                               tabindex="-1"
                               style="width:100%;background:transparent;font-size:18px;font-weight:700;color:#1E1915;outline:none;border:none;cursor:not-allowed;">
                        <p style="font-size:9px;color:#A8A096;margin:0;">Auto-summed from sizes</p>
                    </div>

                    {{-- Shipping Fee --}}
                    <div id="shipping-fee-card" style="background-color:#FFFFFF;border:1px solid #E2D9C8;border-radius:16px;padding:14px;display:flex;flex-direction:column;justify-content:space-between;height:100px;box-shadow:0 1px 3px rgba(0,0,0,0.02);">
                        <label style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#78716C;">Shipping Fee (₱) <span style="color:#C49520;">*</span></label>
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
                               style="width:100%;background:transparent;font-size:18px;font-weight:700;color:#1E1915;outline:none;border:none;">
                        <p style="font-size:9px;color:#A8A096;margin:0;">Enter 0 for free delivery</p>
                    </div>

                    {{-- Shipping Days --}}
                    <div id="shipping-days-card" style="background-color:#FFFFFF;border:1px solid #E2D9C8;border-radius:16px;padding:14px;display:flex;flex-direction:column;justify-content:space-between;height:100px;box-shadow:0 1px 3px rgba(0,0,0,0.02);">
                        <label style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#78716C;">Est. Shipping Days <span style="color:#C49520;">*</span></label>
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
                               style="width:100%;background:transparent;font-size:18px;font-weight:700;color:#1E1915;outline:none;border:none;">
                        <p style="font-size:9px;color:#A8A096;margin:0;">Delivery lead time</p>
                    </div>
                </div>

                {{-- Lumban Special Discount Panel --}}
                <div style="padding:14px 18px;border-radius:18px;background-color:#FDF8EE;border:1px solid #EEDBBA;" class="space-y-3">
                    <input type="hidden" name="is_on_sale" id="isOnSaleInput" value="0">

                    <div style="display:flex;align-items:center;justify-content:space-between;">
                        <div style="display:flex;align-items:center;gap:8px;">
                            <span style="width:8px;height:8px;border-radius:50%;background-color:#C49520;"></span>
                            <span style="font-size:12px;font-weight:700;color:#7A5505;text-transform:uppercase;letter-spacing:0.06em;">Special Price / Sale Discount</span>
                            <span style="font-size:10px;color:#78716C;font-weight:700;text-transform:uppercase;">(Optional)</span>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer shrink-0">
                            <input type="checkbox" id="discountToggle" class="sr-only peer" onchange="toggleDiscount(this)">
                            <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-0.5 after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-[#1C160E]"></div>
                        </label>
                    </div>

                    <div id="discountFields" class="hidden space-y-2.5 pt-2 border-t border-[#EEDBBA]">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 items-end">
                            <div>
                                <label style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#78716C;display:block;margin-bottom:4px;">Discount (%)</label>
                                <input type="number" 
                                       name="discount_percentage" 
                                       id="discountPercentage" 
                                       min="1" 
                                       max="99" 
                                       step="1" 
                                       placeholder="e.g. 20"
                                       style="width:100%;padding:10px 14px;background-color:#FFFFFF;border:1px solid #E2D9C8;border-radius:12px;font-size:14px;font-weight:700;color:#1E1915;outline:none;"
                                       oninput="if(parseInt(this.value) > 99) this.value = 99; updateDiscountPreview();">
                            </div>
                            <div>
                                <label style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#78716C;display:block;margin-bottom:4px;">Price Preview</label>
                                <div id="discountPreview" class="hidden w-full px-4 py-2 bg-white rounded-xl border border-[#EEDBBA] items-center justify-center gap-2 h-10.5 shadow-2xs">
                                    <span id="previewOriginal" class="text-xs text-[#78716C] line-through font-bold"></span>
                                    <span id="previewSale" class="text-sm font-black text-[#7A5505]"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- 3. Payment Methods Card --}}
            <div id="payment-methods-card" style="background-color:#FDFBF7 !important;border:1px solid #EAE2D2 !important;border-radius:24px !important;padding:24px !important;box-shadow:0 4px 20px rgba(0,0,0,0.03) !important;" class="space-y-4">
                <div style="display:flex;align-items:center;justify-content:space-between;padding-bottom:12px;border-bottom:1px solid #EAE1D0;">
                    <div style="display:flex;align-items:center;gap:12px;">
                        <div style="width:32px;height:32px;border-radius:50%;background-color:#FDF8EE;border:1px solid #EEDBBA;display:flex;align-items:center;justify-content:center;color:#7A5505;font-family:ui-serif,Georgia,serif;font-weight:700;font-size:13px;flex-shrink:0;">3</div>
                        <h3 style="font-family:ui-serif,Georgia,Cambria,serif;font-size:16px;font-weight:700;color:#1E1915;margin:0;">Payment Methods <span style="color:#C49520;">*</span></h3>
                    </div>
                    <a href="{{ route('seller.profile') }}?open_payment=1#payment-methods" target="_blank" style="font-size:12px;font-weight:700;color:#7A5505;text-decoration:none;display:flex;align-items:center;gap:4px;">
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
                    <div style="border-radius:18px;border:1px solid #DBEAFE;overflow:hidden;background:#FFFFFF;box-shadow:0 1px 3px rgba(0,0,0,0.02);">
                        <div style="display:flex;align-items:center;justify-content:space-between;padding:10px 14px;background:linear-gradient(90deg,#2563EB,#3B82F6);">
                            <span style="font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:0.06em;color:#FFFFFF;">GCash</span>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="product_is_gcash_available" value="1" id="gcash_toggle_create" class="sr-only peer" {{ old('product_is_gcash_available', true) ? 'checked' : '' }} onchange="document.getElementById('gcash_fields_create').style.display = this.checked ? '' : 'none'; calculateFillRate();">
                                <div class="w-8 h-4.5 bg-white/30 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-0.5 after:bg-white after:border-gray-300 after:border after:rounded-full after:h-3.5 after:w-3.5 after:transition-all peer-checked:bg-white/40 border border-white/40"></div>
                            </label>
                        </div>
                        <div id="gcash_fields_create" {{ old('product_is_gcash_available', true) ? '' : 'style=display:none' }} style="padding:14px;background:#FFFFFF;display:flex;align-items:center;gap:12px;">
                            <div class="flex-1 min-w-0">
                                @if($isGcashComplete)
                                    <div style="font-size:13px;font-weight:700;color:#1E1915;">{{ $user->gcashNumber }}</div>
                                    <div style="font-size:10px;color:#2563EB;font-weight:700;text-transform:uppercase;letter-spacing:0.04em;margin-top:2px;">✓ Ready (Number & QR Set)</div>
                                @else
                                    <div style="font-size:11px;color:#D97706;font-weight:700;">Incomplete setup</div>
                                    <a href="{{ route('seller.profile') }}?open_payment=1#payment-methods" style="font-size:10px;color:#2563EB;font-weight:700;text-decoration:underline;">Add in Settings →</a>
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
                    <div style="border-radius:18px;border:1px solid #D1FAE5;overflow:hidden;background:#FFFFFF;box-shadow:0 1px 3px rgba(0,0,0,0.02);">
                        <div style="display:flex;align-items:center;justify-content:space-between;padding:10px 14px;background:linear-gradient(90deg,#059669,#10B981);">
                            <span style="font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:0.06em;color:#FFFFFF;">Maya</span>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="product_is_maya_available" value="1" id="maya_toggle_create" class="sr-only peer" {{ old('product_is_maya_available', false) ? 'checked' : '' }} onchange="document.getElementById('maya_fields_create').style.display = this.checked ? '' : 'none'; calculateFillRate();">
                                <div class="w-8 h-4.5 bg-white/30 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-0.5 after:bg-white after:border-gray-300 after:border after:rounded-full after:h-3.5 after:w-3.5 after:transition-all peer-checked:bg-white/40 border border-white/40"></div>
                            </label>
                        </div>
                        <div id="maya_fields_create" {{ old('product_is_maya_available', false) ? '' : 'style=display:none' }} style="padding:14px;background:#FFFFFF;display:flex;align-items:center;gap:12px;">
                            <div class="flex-1 min-w-0">
                                @if($isMayaComplete)
                                    <div style="font-size:13px;font-weight:700;color:#1E1915;">{{ $user->mayaNumber }}</div>
                                    <div style="font-size:10px;color:#059669;font-weight:700;text-transform:uppercase;letter-spacing:0.04em;margin-top:2px;">✓ Ready (Number & QR Set)</div>
                                @else
                                    <div style="font-size:11px;color:#D97706;font-weight:700;">Incomplete setup</div>
                                    <a href="{{ route('seller.profile') }}?open_payment=1#payment-methods" style="font-size:10px;color:#059669;font-weight:700;text-decoration:underline;">Add in Settings →</a>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- 4. Artisan Description & Storytelling Card --}}
            <div style="background-color:#FDFBF7 !important;border:1px solid #EAE2D2 !important;border-radius:24px !important;padding:24px !important;box-shadow:0 4px 20px rgba(0,0,0,0.03) !important;" class="space-y-4">
                <div style="display:flex;align-items:center;justify-content:space-between;padding-bottom:12px;border-bottom:1px solid #EAE1D0;">
                    <div style="display:flex;align-items:center;gap:12px;">
                        <div style="width:32px;height:32px;border-radius:50%;background-color:#FDF8EE;border:1px solid #EEDBBA;display:flex;align-items:center;justify-content:center;color:#7A5505;font-family:ui-serif,Georgia,serif;font-weight:700;font-size:13px;flex-shrink:0;">4</div>
                        <div>
                            <h3 style="font-family:ui-serif,Georgia,Cambria,serif;font-size:16px;font-weight:700;color:#1E1915;margin:0;">Artisan Description & Story <span style="color:#C49520;">*</span></h3>
                            <p style="font-size:12px;color:#78716C;margin-top:2px;margin-bottom:0;">Highlight the craftsmanship, weaving techniques, and care instructions</p>
                        </div>
                    </div>

                    {{-- AI Auto-Write Story Button (Sleek Onyx Pill) --}}
                    <button type="button" 
                            @click="generateDescriptionAi()"
                            :disabled="isAiLoading"
                            style="padding:8px 18px;border-radius:9999px;background-color:#1C160E;color:#FAF6F0;font-size:12px;font-weight:700;display:flex;align-items:center;gap:6px;border:none;cursor:pointer;box-shadow:0 2px 6px rgba(28,22,14,0.15);transition:all 0.2s;"
                            class="hover:opacity-90 active:scale-95 disabled:opacity-50">
                        <span x-show="!isAiLoading" style="display:flex;align-items:center;gap:6px;">
                            <span style="color:#C49520;font-size:12px;">✦</span>
                            <span>AI Auto-Write</span>
                        </span>
                        <span x-show="isAiLoading" style="display:flex;align-items:center;gap:6px;" x-cloak>
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
                              style="width:100%;padding:14px 16px;background-color:#FFFFFF;border:1px solid #E2D9C8;border-radius:16px;outline:none;font-size:14px;font-weight:500;color:#1E1915;resize:none;transition:all 0.2s;"
                              onfocus="this.style.borderColor='#C49520';"
                              onblur="this.style.borderColor='#E2D9C8';"
                              class="shadow-2xs pb-8"></textarea>
                    
                    <div style="position:absolute;bottom:14px;right:16px;display:flex;align-items:center;gap:4px;background:rgba(255,255,255,0.95);padding:2px 8px;border-radius:9999px;border:1px solid #E2D9C8;font-size:10px;font-weight:700;color:#78716C;pointer-events:none;">
                        <span id="charCounter" x-text="description ? description.length : 0">0</span><span style="color:#A8A096;">/</span><span>500</span>
                    </div>
                </div>
            </div>

            {{-- 7. Bottom Submission Actions (Back, Draft & Publish) --}}
            <div style="padding-top:20px;display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;border-top:1px solid #EAE1D0;">
                <button type="button" 
                        @click="step = 1; window.scrollTo({ top: 0, behavior: 'smooth' })"
                        style="padding:14px 24px;border-radius:9999px;border:1px solid #E2D9C8;background-color:#FFFFFF;color:#1E1915;font-size:13px;font-weight:700;cursor:pointer;display:flex;align-items:center;gap:8px;box-shadow:0 1px 3px rgba(0,0,0,0.03);transition:all 0.2s;"
                        onmouseover="this.style.backgroundColor='#FAF8F5';"
                        onmouseout="this.style.backgroundColor='#FFFFFF';">
                    <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    <span>Back to Step 1</span>
                </button>

                <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
                    <button type="button" 
                            @click="submitAsDraft()"
                            style="padding:14px 26px;border-radius:9999px;border:1px solid #1C160E;background-color:#FFFFFF;color:#1C160E;font-size:13px;font-weight:700;letter-spacing:0.02em;cursor:pointer;display:flex;align-items:center;gap:8px;box-shadow:0 1px 3px rgba(0,0,0,0.03);transition:all 0.2s;"
                            onmouseover="this.style.backgroundColor='#FAF8F5';"
                            onmouseout="this.style.backgroundColor='#FFFFFF';">
                        <svg width="15" height="15" style="color:#78716C;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/>
                        </svg>
                        <span>Save as Draft</span>
                    </button>

                    <button type="submit" 
                            @click="document.getElementById('formActionInput').value = 'publish'"
                            style="padding:14px 32px;border-radius:9999px;border:none;background-color:#1C160E;color:#FAF6F0;font-size:13px;font-weight:700;letter-spacing:0.02em;cursor:pointer;display:flex;align-items:center;gap:10px;box-shadow:0 4px 14px rgba(28,22,14,0.15);transition:all 0.2s;"
                            onmouseover="this.style.backgroundColor='#2C2419';"
                            onmouseout="this.style.backgroundColor='#1C160E';">
                        <span>Publish Heritage Piece</span>
                        <span style="font-size:15px;line-height:1;font-weight:700;">→</span>
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
