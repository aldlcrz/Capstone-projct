@extends('layouts.seller')

@section('content')
<div class="max-w-4xl mx-auto pb-36 sm:pb-28 lg:pb-16 px-3 sm:px-6" x-data="addProductManager()">
    {{-- Top Header & Navigation --}}
    <div style="margin-bottom: 20px;">
        {{-- Back Link --}}
        <div style="margin-bottom: 12px;">
            <a href="{{ route('seller.products.index') }}" style="display:inline-flex;align-items:center;gap:6px;font-size:12px;font-weight:600;color:#78716C;text-decoration:none;transition:color 0.2s;" onmouseover="this.style.color='#1E1915'" onmouseout="this.style.color='#78716C'">
                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                <span>Back to Catalogue</span>
            </a>
        </div>

        {{-- Title & Stepper Badge Row --}}
        <div style="display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;">
            <div style="display:flex;align-items:center;gap:14px;">
                {{-- Heraldic Laurel Wreath + Medallion --}}
                <div style="width:48px;height:48px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <svg width="46" height="46" viewBox="0 0 48 48" fill="none">
                        <circle cx="24" cy="23" r="10.5" stroke="#C49520" stroke-width="1" stroke-dasharray="2 1.5"/>
                        <circle cx="24" cy="23" r="8.5" stroke="#C49520" stroke-width="0.8"/>
                        <path d="M24 17.5l1.6 3.4 3.7.5-2.7 2.6.6 3.7-3.2-1.7-3.2 1.7.6-3.7-2.7-2.6 3.7-.5L24 17.5z" fill="#C49520"/>
                        <path d="M15 32.5c-4-3.5-6-8.5-6-14 0-3.5 1-6.5 2.5-9" stroke="#C49520" stroke-width="1.3" stroke-linecap="round"/>
                        <path d="M10 12c1.8 1.2 3.5 2.8 4 4.5M8 17.5c2 .6 3.8 1.8 4.8 3.5M8 23.5c2 0 3.8.6 5 2M9.5 29.5c2-.8 3.8-.8 5.2 0M12.5 34c1.8-1.2 3.6-1.5 5-.8" stroke="#C49520" stroke-width="1.2" stroke-linecap="round"/>
                        <path d="M33 32.5c4-3.5 6-8.5 6-14 0-3.5-1-6.5-2.5-9" stroke="#C49520" stroke-width="1.3" stroke-linecap="round"/>
                        <path d="M38 12c-1.8 1.2-3.5 2.8-4 4.5M40 17.5c-2 .6-3.8 1.8-4.8 3.5M40 23.5c-2 0-3.8.6-5 2M38.5 29.5c-2-.8-3.8-.8-5.2 0M35.5 34c-1.8-1.2-3.6-1.5-5-.8" stroke="#C49520" stroke-width="1.2" stroke-linecap="round"/>
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

            {{-- Floating Stepper Card (Exact from screenshot) --}}
            <div style="background:#FFFFFF;border:1px solid #ECE3D2;border-radius:18px;padding:9px 18px;display:flex;align-items:center;gap:12px;box-shadow:0 2px 8px rgba(0,0,0,0.03);">
                <div style="width:38px;height:38px;border-radius:12px;background:#FDF8EE;border:1px solid #EEDBBA;display:flex;align-items:center;justify-content:center;color:#C49520;flex-shrink:0;">
                    <template x-if="step === 1">
                        <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8">
                            <rect x="3" y="3" width="18" height="18" rx="4" ry="4"/>
                            <circle cx="8.5" cy="8.5" r="1.5" fill="currentColor"/>
                            <path d="M21 15l-5-5L5 21"/>
                            <path d="M16 7l.5 1 1 .5-1 .5-.5 1-.5-1-1-.5 1-.5.5-1z" fill="currentColor"/>
                        </svg>
                    </template>
                    <template x-if="step === 2">
                        <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                        </svg>
                    </template>
                    <template x-if="step === 3">
                        <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                        </svg>
                    </template>
                </div>
                <div>
                    <div style="font-size:11px;font-weight:600;color:#8C7355;line-height:1.2;" x-text="'Step ' + step + ' of 3'"></div>
                    <div style="font-size:13px;font-weight:800;color:#7A5505;margin-top:1px;line-height:1.2;" x-text="step === 1 ? 'Media & Core Info' : (step === 2 ? 'Pricing & Sizing' : 'Story & Payment')"></div>
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
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path></svg>
        </button>
    </div>
    @endif

    {{-- Main Product Form --}}
    <form action="{{ route('seller.products.store') }}" method="POST" id="productForm" enctype="multipart/form-data" onsubmit="return validateProductForm(event, false)" class="space-y-6">
        @csrf
        <input type="hidden" name="action" id="formActionInput" value="publish">

        {{-- ========================================================================= --}}
        {{-- STEP 1: MEDIA & CORE INFO (Exact Screenshot Layout & Aesthetics)           --}}
        {{-- ========================================================================= --}}
        <div x-show="step === 1" style="background-color:#FFFFFF !important;border:1px solid #ECE3D2 !important;border-radius:28px !important;box-shadow:0 8px 30px rgba(0,0,0,0.03) !important;color:#1E1915 !important;" class="p-5 sm:p-8 space-y-6">
            
            {{-- 1. Product Media & Variants --}}
            <div class="space-y-4">
                <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;">
                    <div>
                        <h2 style="font-family:ui-serif,Georgia,Cambria,serif;font-size:18px;font-weight:700;color:#1E1915;margin:0;line-height:1.2;">
                            1. Product Media & Variants <span style="color:#DC2626;">*</span>
                        </h2>
                        <p style="font-size:12px;color:#78716C;margin-top:4px;margin-bottom:0;">
                            Variant 1 is your main style and will be shown as the primary listing.
                        </p>
                    </div>
                    <span style="background-color:#FDF8EE;border:1px solid #EEDBBA;color:#7A5505;font-size:11px;font-weight:700;border-radius:20px;padding:3px 12px;flex-shrink:0;" x-text="variants.length + ' style(s) added'"></span>
                </div>

                {{-- Variant 1: Main Product Style & Cover Photo Card --}}
                <div style="background-color:#FFFFFF !important;border:1px solid #ECE3D2 !important;border-radius:20px !important;padding:22px !important;box-shadow:0 1px 4px rgba(0,0,0,0.02) !important;" class="space-y-4" id="variant_card_0">
                    <div style="display:flex;align-items:center;justify-content:space-between;padding-bottom:12px;border-bottom:1px solid #F2ECE1;">
                        <div style="display:flex;align-items:center;gap:8px;">
                            <span style="width:22px;height:22px;border-radius:50%;background-color:#9E6B15;color:#FFFFFF;display:inline-flex;align-items:center;justify-content:center;font-size:11px;font-weight:800;flex-shrink:0;">1</span>
                            <span style="font-family:ui-serif,Georgia,serif;font-size:15px;font-weight:700;color:#1E1915;">
                                Variant 1 (Main Style / Cover)
                            </span>
                            <span style="background-color:#FDF8EE;border:1px solid #EEDBBA;color:#7A5505;font-size:9.5px;font-weight:800;border-radius:20px;padding:2px 8px;text-transform:uppercase;letter-spacing:0.05em;">Cover Image</span>
                        </div>
                        <div style="display:flex;align-items:center;gap:4px;color:#10B981;font-size:12px;font-weight:700;">
                            <span>✓</span>
                            <span>Primary Listing</span>
                        </div>
                    </div>

                    {{-- Hidden inputs for Variant 1 mapping --}}
                    <input type="hidden" name="variant_indexes[]" value="0">
                    <input type="hidden" name="variant_names[0]" :value="productName || 'Original Style'">

                    {{-- Product Name (English) --}}
                    <div class="space-y-1.5">
                        <label style="font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:0.06em;color:#1E1915;display:block;">
                            PRODUCT NAME (ENGLISH) <span style="color:#DC2626;">*</span>
                            <span style="font-size:10px;color:#A8A096;font-weight:400;margin-left:4px;" x-text="'(' + (productName ? productName.length : 0) + '/100)'"></span>
                        </label>

                        <div class="relative flex items-center">
                            <input type="text" 
                                   name="name" 
                                   id="productNameInput"
                                   required 
                                   maxlength="100"
                                   x-model="productName"
                                   @input="calculateFillRate()"
                                   placeholder="e.g. Hand-Woven Piña Barong Tagalog with Calado Embroidery"
                                   style="width:100%;padding:13px 16px;background-color:#FAF8F5;border:1px solid #E2D9C8;border-radius:14px;font-size:13.5px;font-weight:600;color:#1E1915;outline:none;transition:all 0.2s;"
                                   onfocus="this.style.borderColor='#C49520';this.style.backgroundColor='#FFFFFF';"
                                   onblur="this.style.borderColor='#E2D9C8';this.style.backgroundColor='#FAF8F5';"
                                   class="pr-10 shadow-2xs">
                            
                            {{-- Clear Button (X) --}}
                            <button type="button" 
                                    x-show="productName && productName.length > 0"
                                    @click="productName = ''; calculateFillRate();"
                                    style="position:absolute;right:12px;color:#A8A096;background:none;border:none;cursor:pointer;">
                                <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                            </button>
                        </div>
                    </div>

                    {{-- Variant Images Row (Exact 6 Slots Layout from Screenshot) --}}
                    <div class="space-y-2 pt-1">
                        <div style="display:flex;align-items:center;gap:5px;">
                            <label style="font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:0.06em;color:#1E1915;">
                                VARIANT IMAGES <span style="color:#DC2626;">*</span>
                            </label>
                            <span style="font-size:12px;color:#A8A096;cursor:help;" title="Upload the cover photo and additional angles or details for Variant 1">ⓘ</span>
                        </div>

                        {{-- Hidden inputs to store real files for Laravel form submission --}}
                        <input type="file" 
                               id="variant_file_0"
                               name="variant_image_0" 
                               accept="image/jpeg,image/png,image/webp,image/jpg" 
                               class="hidden" 
                               @change="handleCoverPhotoUpload($event)">

                        <input type="file" 
                               id="gallery_files_input" 
                               name="images[]" 
                               multiple 
                               accept="image/jpeg,image/png,image/webp,image/jpg" 
                               class="hidden" 
                               @change="handleGalleryFilesUpload($event)">

                        {{-- 6-Slot Horizontal Row --}}
                        <div class="flex items-center gap-3 overflow-x-auto pb-2 pt-1">
                            {{-- Slot 0: Upload Cover Photo Big Card --}}
                            <label for="variant_file_0" 
                                   id="variant_upload_box_0"
                                   style="width:130px;height:140px;border-radius:18px;border:1.5px dashed #E2D9C8;background-color:#FAF8F5;display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;cursor:pointer;flex-shrink:0;transition:all 0.2s;padding:10px;"
                                   onmouseover="this.style.borderColor='#C49520';this.style.backgroundColor='#FFFFFF';"
                                   onmouseout="this.style.borderColor='#E2D9C8';this.style.backgroundColor='#FAF8F5';">
                                <div style="color:#C49520;margin-bottom:6px;">
                                    <svg width="26" height="26" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8">
                                        <rect x="3" y="3" width="18" height="18" rx="4" ry="4"/>
                                        <circle cx="8.5" cy="8.5" r="1.5" fill="currentColor"/>
                                        <path d="M21 15l-5-5L5 21"/>
                                        <path d="M16 7l.5 1 1 .5-1 .5-.5 1-.5-1-1-.5 1-.5.5-1z" fill="currentColor"/>
                                    </svg>
                                </div>
                                <span style="font-size:11.5px;font-weight:700;color:#1E1915;line-height:1.2;">Upload Cover Photo</span>
                                <span style="font-size:9.5px;color:#78716C;margin-top:4px;">JPG, PNG, WEBP</span>
                                <span style="font-size:9px;color:#A8A096;margin-top:2px;">Recommended: 1:1</span>
                            </label>

                            {{-- Slot 1: Cover Photo Thumbnail / Watermark Box --}}
                            <div style="width:105px;height:140px;border-radius:18px;border:1px solid #ECE3D2;background-color:#FAF8F5;position:relative;flex-shrink:0;overflow:hidden;display:flex;flex-direction:column;align-items:center;justify-content:space-between;padding:8px 6px;">
                                {{-- Top-left Cover badge --}}
                                <div style="align-self:flex-start;background:#7A5505;color:#FFFFFF;font-size:8.5px;font-weight:800;padding:2px 7px;border-radius:6px;text-transform:uppercase;letter-spacing:0.04em;">
                                    Cover
                                </div>

                                {{-- If image uploaded, show preview --}}
                                <template x-if="variants[0].imagePreview">
                                    <div style="position:absolute;inset:0;z-index:5;">
                                        <img :src="variants[0].imagePreview" style="width:100%;height:100%;object-fit:cover;">
                                        <div class="absolute inset-0 bg-black/40 opacity-0 hover:opacity-100 transition-opacity flex flex-col items-center justify-center text-white text-[10px] font-bold uppercase gap-1">
                                            <label for="variant_file_0" class="cursor-pointer">Change</label>
                                        </div>
                                        <button type="button" 
                                                @click="removeCoverPhoto()" 
                                                style="position:absolute;top:4px;right:4px;width:18px;height:18px;background-color:#DC2626;color:#FFFFFF;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:9px;font-weight:900;border:none;cursor:pointer;z-index:10;">
                                            ✕
                                        </button>
                                    </div>
                                </template>

                                {{-- Faint Laurel Emblem Watermark if empty --}}
                                <template x-if="!variants[0].imagePreview">
                                    <div style="opacity:0.25;margin-top:auto;margin-bottom:auto;">
                                        <svg width="42" height="42" viewBox="0 0 48 48" fill="none">
                                            <circle cx="24" cy="23" r="8.5" stroke="#C49520" stroke-width="1"/>
                                            <path d="M24 17.5l1.6 3.4 3.7.5-2.7 2.6.6 3.7-3.2-1.7-3.2 1.7.6-3.7-2.7-2.6 3.7-.5L24 17.5z" fill="#C49520"/>
                                            <path d="M15 32.5c-4-3.5-6-8.5-6-14 0-3.5 1-6.5 2.5-9" stroke="#C49520" stroke-width="1.3" stroke-linecap="round"/>
                                            <path d="M33 32.5c4-3.5 6-8.5 6-14 0-3.5-1-6.5-2.5-9" stroke="#C49520" stroke-width="1.3" stroke-linecap="round"/>
                                        </svg>
                                    </div>
                                </template>

                                {{-- Gold Star at bottom --}}
                                <div style="color:#C49520;font-size:12px;font-weight:900;z-index:6;">★</div>
                            </div>

                            {{-- Slot 2: Additional Photo 2 --}}
                            <div style="width:105px;height:140px;border-radius:18px;border:1px solid #ECE3D2;background-color:#FAF8F5;position:relative;flex-shrink:0;overflow:hidden;display:flex;flex-direction:column;align-items:center;justify-content:space-between;padding:8px 6px;">
                                <template x-if="galleryImages[0]">
                                    <div style="position:absolute;inset:0;z-index:5;">
                                        <img :src="galleryImages[0].preview" style="width:100%;height:100%;object-fit:cover;">
                                        <button type="button" 
                                                @click="removeGalleryImage(0)" 
                                                style="position:absolute;top:4px;right:4px;width:18px;height:18px;background-color:#DC2626;color:#FFFFFF;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:9px;font-weight:900;border:none;cursor:pointer;z-index:10;">
                                            ✕
                                        </button>
                                    </div>
                                </template>
                                <template x-if="!galleryImages[0]">
                                    <div style="opacity:0.25;margin-top:auto;margin-bottom:auto;">
                                        <svg width="42" height="42" viewBox="0 0 48 48" fill="none">
                                            <circle cx="24" cy="23" r="8.5" stroke="#C49520" stroke-width="1"/>
                                            <path d="M24 17.5l1.6 3.4 3.7.5-2.7 2.6.6 3.7-3.2-1.7-3.2 1.7.6-3.7-2.7-2.6 3.7-.5L24 17.5z" fill="#C49520"/>
                                            <path d="M15 32.5c-4-3.5-6-8.5-6-14 0-3.5 1-6.5 2.5-9" stroke="#C49520" stroke-width="1.3" stroke-linecap="round"/>
                                            <path d="M33 32.5c4-3.5 6-8.5 6-14 0-3.5-1-6.5-2.5-9" stroke="#C49520" stroke-width="1.3" stroke-linecap="round"/>
                                        </svg>
                                    </div>
                                </template>
                                <span style="font-size:11px;font-weight:700;color:#A8A096;z-index:6;">2</span>
                            </div>

                            {{-- Slot 3: Additional Photo 3 --}}
                            <div style="width:105px;height:140px;border-radius:18px;border:1px solid #ECE3D2;background-color:#FAF8F5;position:relative;flex-shrink:0;overflow:hidden;display:flex;flex-direction:column;align-items:center;justify-content:space-between;padding:8px 6px;">
                                <template x-if="galleryImages[1]">
                                    <div style="position:absolute;inset:0;z-index:5;">
                                        <img :src="galleryImages[1].preview" style="width:100%;height:100%;object-fit:cover;">
                                        <button type="button" 
                                                @click="removeGalleryImage(1)" 
                                                style="position:absolute;top:4px;right:4px;width:18px;height:18px;background-color:#DC2626;color:#FFFFFF;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:9px;font-weight:900;border:none;cursor:pointer;z-index:10;">
                                            ✕
                                        </button>
                                    </div>
                                </template>
                                <template x-if="!galleryImages[1]">
                                    <div style="opacity:0.25;margin-top:auto;margin-bottom:auto;">
                                        <svg width="42" height="42" viewBox="0 0 48 48" fill="none">
                                            <circle cx="24" cy="23" r="8.5" stroke="#C49520" stroke-width="1"/>
                                            <path d="M24 17.5l1.6 3.4 3.7.5-2.7 2.6.6 3.7-3.2-1.7-3.2 1.7.6-3.7-2.7-2.6 3.7-.5L24 17.5z" fill="#C49520"/>
                                            <path d="M15 32.5c-4-3.5-6-8.5-6-14 0-3.5 1-6.5 2.5-9" stroke="#C49520" stroke-width="1.3" stroke-linecap="round"/>
                                            <path d="M33 32.5c4-3.5 6-8.5 6-14 0-3.5-1-6.5-2.5-9" stroke="#C49520" stroke-width="1.3" stroke-linecap="round"/>
                                        </svg>
                                    </div>
                                </template>
                                <span style="font-size:11px;font-weight:700;color:#A8A096;z-index:6;">3</span>
                            </div>

                            {{-- Slot 4: Additional Photo 4 --}}
                            <div style="width:105px;height:140px;border-radius:18px;border:1px solid #ECE3D2;background-color:#FAF8F5;position:relative;flex-shrink:0;overflow:hidden;display:flex;flex-direction:column;align-items:center;justify-content:space-between;padding:8px 6px;">
                                <template x-if="galleryImages[2]">
                                    <div style="position:absolute;inset:0;z-index:5;">
                                        <img :src="galleryImages[2].preview" style="width:100%;height:100%;object-fit:cover;">
                                        <button type="button" 
                                                @click="removeGalleryImage(2)" 
                                                style="position:absolute;top:4px;right:4px;width:18px;height:18px;background-color:#DC2626;color:#FFFFFF;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:9px;font-weight:900;border:none;cursor:pointer;z-index:10;">
                                            ✕
                                        </button>
                                    </div>
                                </template>
                                <template x-if="!galleryImages[2]">
                                    <div style="opacity:0.25;margin-top:auto;margin-bottom:auto;">
                                        <svg width="42" height="42" viewBox="0 0 48 48" fill="none">
                                            <circle cx="24" cy="23" r="8.5" stroke="#C49520" stroke-width="1"/>
                                            <path d="M24 17.5l1.6 3.4 3.7.5-2.7 2.6.6 3.7-3.2-1.7-3.2 1.7.6-3.7-2.7-2.6 3.7-.5L24 17.5z" fill="#C49520"/>
                                            <path d="M15 32.5c-4-3.5-6-8.5-6-14 0-3.5 1-6.5 2.5-9" stroke="#C49520" stroke-width="1.3" stroke-linecap="round"/>
                                            <path d="M33 32.5c4-3.5 6-8.5 6-14 0-3.5-1-6.5-2.5-9" stroke="#C49520" stroke-width="1.3" stroke-linecap="round"/>
                                        </svg>
                                    </div>
                                </template>
                                <span style="font-size:11px;font-weight:700;color:#A8A096;z-index:6;">4</span>
                            </div>

                            {{-- Slot 5: Add More Button --}}
                            <label for="gallery_files_input" 
                                   style="width:105px;height:140px;border-radius:18px;border:1.5px dashed #E2D9C8;background-color:#FFFFFF;display:flex;flex-direction:column;align-items:center;justify-content:center;cursor:pointer;flex-shrink:0;transition:all 0.2s;padding:10px;"
                                   onmouseover="this.style.borderColor='#C49520';this.style.backgroundColor='#FAF8F5';"
                                   onmouseout="this.style.borderColor='#E2D9C8';this.style.backgroundColor='#FFFFFF';">
                                <span style="color:#C49520;font-size:20px;font-weight:700;line-height:1;margin-bottom:6px;">+</span>
                                <span style="font-size:11px;font-weight:700;color:#78716C;">Add More</span>
                            </label>
                        </div>

                        {{-- Informational Subtext Notes (Exact from screenshot) --}}
                        <div class="space-y-1 pt-2">
                            <p style="font-size:11.5px;color:#78716C;margin:0;display:flex;align-items:center;gap:6px;">
                                <span style="color:#C49520;font-size:12px;">✨</span>
                                <span>This photo will be showcased as the main thumbnail across the store, search, and catalogue.</span>
                            </p>
                            <p style="font-size:11.5px;color:#7A5505;font-weight:600;margin:0;display:flex;align-items:center;gap:6px;">
                                <span style="color:#C49520;font-size:12px;font-weight:900;">+</span>
                                <span>Add other colors, fabrics, or sleeve styles? Click "Add Another Variant" below.</span>
                            </p>
                        </div>
                    </div>
                </div>

                {{-- Additional Variants (Variant 2, 3, etc. if seller adds) --}}
                <div class="space-y-3">
                    <template x-for="(variant, index) in variants" :key="variant.id">
                        <div x-show="index > 0" style="background-color:#FFFFFF !important;border:1px solid #ECE3D2 !important;border-radius:18px !important;padding:16px !important;box-shadow:0 2px 6px rgba(0,0,0,0.02) !important;" class="space-y-3">
                            <div style="display:flex;align-items:center;justify-content:space-between;padding-bottom:8px;border-bottom:1px solid #F2ECE1;">
                                <div style="display:flex;align-items:center;gap:8px;">
                                    <span style="width:20px;height:20px;border-radius:50%;background-color:#9E6B15;color:#FFFFFF;display:inline-flex;align-items:center;justify-content:center;font-size:10px;font-weight:800;" x-text="index + 1"></span>
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

                            <input type="hidden" name="variant_indexes[]" :value="index">

                            <div style="display:flex;align-items:center;gap:14px;">
                                {{-- Variant Image Box --}}
                                <div style="width:80px;height:80px;position:relative;flex-shrink:0;">
                                    <label :for="'variant_file_' + index"
                                           style="width:80px;height:80px;border-radius:14px;border:1.5px dashed #E2D9C8;background-color:#FAF8F5;display:flex;flex-direction:column;align-items:center;justify-content:center;cursor:pointer;overflow:hidden;position:relative;transition:all 0.2s;"
                                           onmouseover="this.style.borderColor='#C49520';this.style.backgroundColor='#FFFFFF';"
                                           onmouseout="this.style.borderColor='#E2D9C8';this.style.backgroundColor='#FAF8F5';">
                                        <template x-if="variant.imagePreview">
                                            <div style="position:relative;width:100%;height:100%;">
                                                <img :src="variant.imagePreview" style="width:100%;height:100%;object-fit:cover;">
                                                <div class="absolute inset-0 bg-black/30 opacity-0 hover:opacity-100 transition-opacity flex items-center justify-center text-white text-[9px] font-bold uppercase">
                                                    Change
                                                </div>
                                            </div>
                                        </template>
                                        <template x-if="!variant.imagePreview">
                                            <div style="display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;padding:4px;">
                                                <span style="color:#C49520;font-size:16px;line-height:1;">+</span>
                                                <span style="font-size:9px;font-weight:700;color:#7A5505;margin-top:2px;">Photo</span>
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
                                    <label style="font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:0.04em;color:#1E1915;display:block;margin-bottom:4px;">
                                        Variant Name <span style="color:#DC2626;">*</span>
                                    </label>
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

                {{-- Add Another Variant Button (Clean non-breaking layout for mobile & desktop) --}}
                <div>
                    <button type="button" 
                            @click="addVariantRow()" 
                            style="width:100%;padding:11px 16px;border-radius:16px;border:1.5px dashed #C49520;background-color:#FAF8F5;color:#1E1915;display:flex;align-items:center;justify-content:center;gap:8px;cursor:pointer;transition:all 0.2s;text-align:center;"
                            onmouseover="this.style.backgroundColor='#FDFBF7';this.style.borderColor='#7A5505';"
                            onmouseout="this.style.backgroundColor='#FAF8F5';this.style.borderColor='#C49520';">
                        <span style="width:20px;height:20px;border-radius:50%;background-color:#FDF8EE;border:1px solid #EEDBBA;color:#7A5505;display:inline-flex;align-items:center;justify-content:center;font-size:14px;font-weight:800;flex-shrink:0;">+</span>
                        <span style="font-size:13px;font-weight:700;color:#1E1915;white-space:nowrap;">Add Another Variant</span>
                        <span style="font-size:11px;font-weight:500;color:#78716C;white-space:nowrap;" class="hidden sm:inline">(Optional Style / Color)</span>
                        <span style="font-size:11px;font-weight:500;color:#78716C;white-space:nowrap;" class="sm:hidden">(Optional)</span>
                    </button>
                </div>
            </div>

            {{-- Centered Golden Diamond Divider (Exact from Screenshot) --}}
            <div style="position:relative;margin:28px 0;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <div style="width:100%;border-top:1px solid #EAE1D0;"></div>
                <span style="position:absolute;background-color:#FFFFFF;padding:0 12px;color:#C49520;font-size:11px;">◆</span>
            </div>

            {{-- 2. Who is this for? (Target Tag) & Category Selection --}}
            <div class="space-y-4">
                {{-- Who is this for? (Target Tag) --}}
                <div class="space-y-2.5">
                    <div style="display:flex;align-items:center;justify-content:space-between;">
                        <h2 style="font-family:ui-serif,Georgia,Cambria,serif;font-size:18px;font-weight:700;color:#1E1915;margin:0;">
                            2. Who is this for? (Target Tag) <span style="color:#DC2626;">*</span>
                        </h2>
                        <span style="font-size:11px;font-weight:700;border-radius:20px;padding:3px 12px;background-color:#E8F5E9;border:1px solid #A5D6A7;color:#2E7D32;transition:all 0.2s;"
                              x-text="'✓ ' + targetGroup + ' selected'"></span>
                    </div>

                    {{-- Target Tag Segmented Pills --}}
                    <style>
                        .target-pill {
                            min-width: 100px;
                            height: 44px;
                            padding: 0 24px;
                            border-radius: 9999px;
                            font-size: 14px;
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
                            font-weight: 500;
                            user-select: none;
                        }
                        .target-pill:hover:not(.target-pill-selected) {
                            background-color: #F5ECD8;
                            border-color: #C8AC70;
                            color: #221F1C;
                        }
                        .target-pill-selected {
                            background-color: #221F1C !important;
                            color: #FCFAF6 !important;
                            border-color: #C49520 !important;
                            box-shadow: 0 4px 14px rgba(34,31,28,0.18), 0 1px 3px rgba(0,0,0,0.06) !important;
                            font-weight: 600 !important;
                        }
                        .target-checkmark {
                            color: #C49520;
                            font-size: 13px;
                            font-weight: 800;
                        }
                    </style>
                    <div id="target-group-container" style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;padding-top:4px;">
                        @foreach(['Men', 'Women', 'Kids'] as $group)
                            <label class="cursor-pointer select-none" @click="onTargetGroupChange('{{ $group }}')">
                                <input type="radio" 
                                       name="target_group" 
                                       value="{{ $group }}" 
                                       x-model="targetGroup" 
                                       class="hidden">
                                <div class="target-pill" :class="targetGroup === '{{ $group }}' ? 'target-pill-selected' : ''">
                                    <span>{{ $group }}</span>
                                    <span class="target-checkmark" x-show="targetGroup === '{{ $group }}'">✓</span>
                                </div>
                            </label>
                        @endforeach
                    </div>
                </div>

                {{-- Product Category for Selected Tag --}}
                <div class="space-y-2.5 pt-2">
                    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;">
                        <h3 style="font-family:ui-serif,Georgia,serif;font-size:15px;font-weight:700;color:#1E1915;margin:0;">
                            Product Category for <span x-text="targetGroup"></span> <span style="color:#DC2626;">*</span>
                        </h3>
                        <div style="display:flex;align-items:center;gap:8px;">
                            <span style="font-size:11px;font-weight:700;border-radius:20px;padding:3px 12px;background-color:#FDF8EE;border:1px solid #EEDBBA;color:#7A5505;"
                                  x-text="filteredCategories.length + ' Categories Available'"></span>
                            <span style="font-size:11.5px;font-weight:700;color:#C49520;">Choose from options below</span>
                        </div>
                    </div>

                    {{-- Hidden CategoryId input for form submission --}}
                    <input type="hidden" name="CategoryId" id="categorySelect" :value="selectedCategory" required>

                    {{-- Category Cards Grid (Clean layout without icons) --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3 pt-1" id="category-cards-container">
                        <template x-for="cat in filteredCategories" :key="cat.id">
                            <button type="button" 
                                    @click="selectCategory(cat)"
                                    style="padding:14px 18px;border-radius:14px;display:flex;align-items:center;justify-content:center;text-align:center;transition:all 0.2s;cursor:pointer;width:100%;min-height:50px;position:relative;"
                                    :style="selectedCategory === cat.id 
                                        ? 'background-color:#FDF8EE !important;border:1.5px solid #C49520 !important;color:#7A5505 !important;font-weight:800 !important;box-shadow:0 2px 8px rgba(196,149,32,0.12) !important;' 
                                        : 'background-color:#FFFFFF !important;border:1px solid #ECE3D2 !important;color:#1E1915 !important;font-weight:600 !important;box-shadow:0 1px 3px rgba(0,0,0,0.02) !important;'"
                                    onmouseover="if(this.getAttribute('data-active') !== 'true') { this.style.borderColor='#C49520'; this.style.backgroundColor='#FAF8F5'; }"
                                    onmouseout="if(this.getAttribute('data-active') !== 'true') { this.style.borderColor='#ECE3D2'; this.style.backgroundColor='#FFFFFF'; }">
                                
                                {{-- Category Name --}}
                                <span style="font-size:13.5px;line-height:1.3;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" x-text="cat.name"></span>
                                
                                {{-- Checkmark if selected --}}
                                <span x-show="selectedCategory === cat.id" style="color:#C49520;font-size:12px;font-weight:900;position:absolute;right:14px;top:50%;transform:translateY(-50%);">✓</span>
                            </button>
                        </template>

                        <template x-if="filteredCategories.length === 0">
                            <div class="col-span-full py-8 text-center text-xs text-[#78716C] font-medium" style="background:#FAF8F5;border:1px dashed #E2D9C8;border-radius:16px;">
                                No categories currently available for this tag.
                            </div>
                        </template>
                    </div>

                    {{-- Selected Category Confirmation Toast / Badge --}}
                    <div x-show="selectedCategory && selectedCategoryObj" 
                         style="display:flex;align-items:center;justify-content:space-between;padding:10px 16px;border-radius:14px;background-color:#FDF8EE;border:1px solid #EEDBBA;margin-top:10px;"
                         class="transition-all">
                        <div style="display:flex;align-items:center;gap:8px;">
                            <span style="color:#C49520;font-size:13px;font-weight:900;">✓</span>
                            <span style="font-size:11px;color:#7A5505;font-weight:600;text-transform:uppercase;letter-spacing:0.04em;">Selected Category:</span>
                            <strong style="font-size:13px;color:#1E1915;font-weight:800;" x-text="selectedCategoryObj ? selectedCategoryObj.name : ''"></strong>
                        </div>
                        <span style="font-size:11px;color:#A07218;font-weight:600;" class="hidden sm:inline">
                            Lumban Verified ✦
                        </span>
                    </div>
                </div>
            </div>

            {{-- Footer Action Bar (Exact Screenshot Layout) --}}
            <div style="margin-top:28px;padding-top:20px;border-top:1px solid #F2ECE1;display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;">
                <div>
                    <div style="font-size:13px;font-weight:700;color:#1E1915;display:flex;align-items:center;gap:6px;">
                        <span>Next: Complete Product Details</span>
                        <span style="font-weight:700;">→</span>
                    </div>
                    <p style="font-size:11px;color:#78716C;margin:3px 0 0 0;">
                        * Please upload a photo, enter product name, choose a category, and select target tag to proceed.
                    </p>
                </div>

                <button type="button" 
                        @click="goToStep2()"
                        :disabled="!isStep1Complete"
                        style="padding:14px 28px;border-radius:12px;font-size:14px;font-weight:700;display:flex;align-items:center;gap:8px;border:none;transition:all 0.2s;"
                        :style="isStep1Complete 
                            ? 'background-color:#A16D19 !important;color:#FFFFFF !important;cursor:pointer;box-shadow:0 2px 8px rgba(161,109,25,0.25);' 
                            : 'background-color:#E5E0D8 !important;color:#A8A096 !important;cursor:not-allowed;box-shadow:none;'">
                    <span>Save & Continue</span>
                    <span style="font-size:15px;line-height:1;font-weight:700;">→</span>
                </button>
            </div>
        </div>

        {{-- Verified & Trusted Footer Banner (Exact from screenshot, below main card) --}}
        <div x-show="step === 1" style="margin-top:16px;padding:18px 24px;border-radius:20px;background:linear-gradient(90deg,#F6F0E4 0%,#F2EADA 50%,#EAE0CD 100%);border:1px solid #E2D6C0;display:flex;align-items:center;justify-content:space-between;gap:14px;position:relative;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,0.03);">
            <div style="display:flex;align-items:center;gap:14px;position:relative;z-index:10;">
                <div style="width:38px;height:38px;border-radius:50%;border:2px solid #B88728;background-color:#FAF4EA;display:flex;align-items:center;justify-content:center;color:#B88728;flex-shrink:0;box-shadow:0 1px 2px rgba(0,0,0,0.05);">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                </div>
                <div>
                    <h5 style="font-size:13.5px;font-weight:700;color:#1E1915;margin:0;line-height:1.3;">All artisan listings are verified and trusted</h5>
                    <p style="font-size:11.5px;color:#78716C;margin:3px 0 0 0;">Quality craftsmanship. Authentic Lumban, Laguna Filipino heritage.</p>
                </div>
            </div>
            <!-- Background Embroidery Watermark -->
            <svg width="140" height="75" viewBox="0 0 120 80" fill="#C49520" style="position:absolute;right:10px;bottom:-10px;opacity:0.2;pointer-events:none;">
                <path d="M60 10C40 10 30 30 10 35C30 40 40 60 60 60C80 60 90 40 110 35C90 30 80 10 60 10ZM60 25C65 25 70 30 70 35C70 40 65 45 60 45C55 45 50 40 50 35C50 30 55 25 60 25Z"/>
            </svg>
        </div>

        {{-- ========================================================================= --}}
        {{-- STEP 2: PRICING & HERITAGE SIZING MATRIX                                  --}}
        {{-- ========================================================================= --}}
        <div x-show="step === 2" x-collapse class="space-y-6">
            {{-- 1. Fill Rate & Listing Health Bar --}}
            <div style="background-color:#FFFFFF !important;border:1px solid #ECE3D2 !important;border-radius:24px !important;padding:20px 24px !important;box-shadow:0 4px 20px rgba(0,0,0,0.03) !important;" class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <span style="font-family:ui-serif,Georgia,serif;font-size:14px;font-weight:700;color:#1E1915;">Listing Completeness</span>
                    <div style="width:160px;height:10px;background-color:#FAF8F5;border:1px solid #E2D9C8;border-radius:9999px;overflow:hidden;">
                        <div style="height:100%;background:linear-gradient(90deg,#C49520 0%,#1C160E 100%);border-radius:9999px;transition:width 0.5s;" :style="'width: ' + fillRate + '%'"></div>
                    </div>
                    <span style="font-size:12px;font-weight:800;color:#7A5505;" x-text="fillRate + '%'"></span>
                    <span style="color:#C49520;font-size:11px;">✦</span>
                </div>

                <div class="flex items-center gap-2">
                    <span style="font-size:12px;color:#78716C;font-weight:500;">Step 2 of 3: Pricing & Sizing</span>
                </div>
            </div>

            {{-- Hidden Input for Fabric Type --}}
            <input type="hidden" name="fabric_type" :value="fabricType || '100% Piña'">

            {{-- 1. Heritage Sizing & Inventory Matrix --}}
            <div id="sizing-section" style="background-color:#FFFFFF !important;border:1px solid #ECE3D2 !important;border-radius:24px !important;padding:24px !important;box-shadow:0 4px 20px rgba(0,0,0,0.03) !important;" class="space-y-4">
                <div style="display:flex;align-items:center;justify-content:space-between;padding-bottom:12px;border-bottom:1px solid #F2ECE1;">
                    <div style="display:flex;align-items:center;gap:12px;">
                        <div style="width:32px;height:32px;border-radius:50%;background-color:#FDF8EE;border:1px solid #EEDBBA;display:flex;align-items:center;justify-content:center;color:#7A5505;font-family:ui-serif,Georgia,serif;font-weight:700;font-size:13px;flex-shrink:0;">1</div>
                        <div>
                            <h3 style="font-family:ui-serif,Georgia,Cambria,serif;font-size:16px;font-weight:700;color:#1E1915;margin:0;">Heritage Sizing & Stock <span style="color:#DC2626;">*</span></h3>
                            <p style="font-size:12px;color:#78716C;margin-top:2px;margin-bottom:0;">Assign available inventory quantities per size</p>
                        </div>
                    </div>
                    <span style="font-size:10px;font-weight:700;color:#7A5505;background-color:#FDF8EE;border:1px solid #EEDBBA;padding:3px 10px;border-radius:20px;text-transform:uppercase;letter-spacing:0.04em;">At least 1 size required</span>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-2.5 pt-1">
                    @foreach(['S', 'M', 'L', 'XL', 'XXL', 'Custom'] as $size)
                        <div style="background-color:#FAF8F5;border:1px solid #E2D9C8;border-radius:16px;padding:12px;text-align:center;box-shadow:0 1px 3px rgba(0,0,0,0.02);transition:all 0.2s;" class="space-y-2 hover:border-[#C49520]">
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
                                   class="size-stock-input"
                                   oninput="if(parseInt(this.value) > 10000) this.value = 10000; calculateTotalStock(); calculateFillRate();"
                                   style="width:100%;padding:6px 8px;background-color:#FFFFFF;border:1px solid #E2D9C8;border-radius:10px;outline:none;font-size:13px;font-weight:700;text-align:center;color:#1E1915;">
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- 2. Pricing & Logistics Grid --}}
            <div style="background-color:#FFFFFF !important;border:1px solid #ECE3D2 !important;border-radius:24px !important;padding:24px !important;box-shadow:0 4px 20px rgba(0,0,0,0.03) !important;" class="space-y-4">
                <div style="display:flex;align-items:center;gap:12px;padding-bottom:12px;border-bottom:1px solid #F2ECE1;">
                    <div style="width:32px;height:32px;border-radius:50%;background-color:#FDF8EE;border:1px solid #EEDBBA;display:flex;align-items:center;justify-content:center;color:#7A5505;font-family:ui-serif,Georgia,serif;font-weight:700;font-size:13px;flex-shrink:0;">2</div>
                    <div>
                        <h3 style="font-family:ui-serif,Georgia,Cambria,serif;font-size:16px;font-weight:700;color:#1E1915;margin:0;">Price & Shipping Information</h3>
                        <p style="font-size:12px;color:#78716C;margin-top:2px;margin-bottom:0;">Define fair artisan pricing and realistic delivery estimates</p>
                    </div>
                </div>

                <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 pt-1">
                    {{-- Price Input --}}
                    <div id="price-card" style="background-color:#FAF8F5;border:1px solid #E2D9C8;border-radius:16px;padding:14px;display:flex;flex-direction:column;justify-content:space-between;height:100px;box-shadow:0 1px 3px rgba(0,0,0,0.02);">
                        <label style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#78716C;">Price (₱) <span style="color:#DC2626;">*</span></label>
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
                        <label style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#78716C;">Total Stock <span style="color:#DC2626;">*</span></label>
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
                    <div id="shipping-fee-card" style="background-color:#FAF8F5;border:1px solid #E2D9C8;border-radius:16px;padding:14px;display:flex;flex-direction:column;justify-content:space-between;height:100px;box-shadow:0 1px 3px rgba(0,0,0,0.02);">
                        <label style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#78716C;">Shipping Fee (₱) <span style="color:#DC2626;">*</span></label>
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
                    <div id="shipping-days-card" style="background-color:#FAF8F5;border:1px solid #E2D9C8;border-radius:16px;padding:14px;display:flex;flex-direction:column;justify-content:space-between;height:100px;box-shadow:0 1px 3px rgba(0,0,0,0.02);">
                        <label style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#78716C;">Est. Shipping Days <span style="color:#DC2626;">*</span></label>
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

                    <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;">
                        <div style="display:flex;align-items:center;gap:8px;min-width:0;flex:1;">
                            <span style="width:8px;height:8px;border-radius:50%;background-color:#C49520;flex-shrink:0;"></span>
                            <span style="font-size:12px;font-weight:700;color:#7A5505;text-transform:uppercase;letter-spacing:0.06em;white-space:nowrap;">Special Price / Sale Discount</span>
                            <span style="font-size:10px;color:#78716C;font-weight:600;text-transform:uppercase;white-space:nowrap;flex-shrink:0;">(Optional)</span>
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

            {{-- Step 2 Navigation Actions --}}
            <div style="padding-top:20px;display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;">
                <button type="button" 
                        @click="step = 1; window.scrollTo({ top: 0, behavior: 'smooth' })"
                        style="padding:14px 24px;border-radius:12px;border:1px solid #E2D9C8;background-color:#FFFFFF;color:#1E1915;font-size:13px;font-weight:700;cursor:pointer;display:flex;align-items:center;gap:8px;box-shadow:0 1px 3px rgba(0,0,0,0.03);transition:all 0.2s;"
                        onmouseover="this.style.backgroundColor='#FAF8F5';"
                        onmouseout="this.style.backgroundColor='#FFFFFF';">
                    <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    <span>← Back to Step 1</span>
                </button>

                <button type="button" 
                        @click="goToStep3()"
                        style="padding:14px 28px;border-radius:12px;font-size:14px;font-weight:700;background-color:#A16D19;color:#FFFFFF;border:none;cursor:pointer;display:flex;align-items:center;gap:8px;box-shadow:0 2px 8px rgba(161,109,25,0.25);transition:all 0.2s;">
                    <span>Continue to Step 3</span>
                    <span style="font-size:15px;line-height:1;font-weight:700;">→</span>
                </button>
            </div>
        </div>

        {{-- ========================================================================= --}}
        {{-- STEP 3: STORY, PAYMENTS & FINAL PUBLISH                                    --}}
        {{-- ========================================================================= --}}
        <div x-show="step === 3" x-collapse class="space-y-6">
            {{-- 1. Payment Methods Card --}}
            <div id="payment-methods-card" style="background-color:#FFFFFF !important;border:1px solid #ECE3D2 !important;border-radius:24px !important;padding:24px !important;box-shadow:0 4px 20px rgba(0,0,0,0.03) !important;" class="space-y-4">
                <div style="display:flex;align-items:center;justify-content:space-between;padding-bottom:12px;border-bottom:1px solid #F2ECE1;">
                    <div style="display:flex;align-items:center;gap:12px;">
                        <div style="width:32px;height:32px;border-radius:50%;background-color:#FDF8EE;border:1px solid #EEDBBA;display:flex;align-items:center;justify-content:center;color:#7A5505;font-family:ui-serif,Georgia,serif;font-weight:700;font-size:13px;flex-shrink:0;">1</div>
                        <h3 style="font-family:ui-serif,Georgia,Cambria,serif;font-size:16px;font-weight:700;color:#1E1915;margin:0;">Payment Methods <span style="color:#DC2626;">*</span></h3>
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

            {{-- 2. Artisan Description & Storytelling Card --}}
            <div style="background-color:#FFFFFF !important;border:1px solid #ECE3D2 !important;border-radius:24px !important;padding:24px !important;box-shadow:0 4px 20px rgba(0,0,0,0.03) !important;" class="space-y-4">
                <div style="display:flex;align-items:center;justify-content:space-between;padding-bottom:12px;border-bottom:1px solid #F2ECE1;">
                    <div style="display:flex;align-items:center;gap:12px;">
                        <div style="width:32px;height:32px;border-radius:50%;background-color:#FDF8EE;border:1px solid #EEDBBA;display:flex;align-items:center;justify-content:center;color:#7A5505;font-family:ui-serif,Georgia,serif;font-weight:700;font-size:13px;flex-shrink:0;">2</div>
                        <div>
                            <h3 style="font-family:ui-serif,Georgia,Cambria,serif;font-size:16px;font-weight:700;color:#1E1915;margin:0;">Artisan Description & Story <span style="color:#DC2626;">*</span></h3>
                            <p style="font-size:12px;color:#78716C;margin-top:2px;margin-bottom:0;">Highlight the craftsmanship, weaving techniques, and care instructions</p>
                        </div>
                    </div>

                    {{-- AI Auto-Write Story Button --}}
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
                              style="width:100%;padding:14px 16px;background-color:#FAF8F5;border:1px solid #E2D9C8;border-radius:16px;outline:none;font-size:14px;font-weight:500;color:#1E1915;resize:none;transition:all 0.2s;"
                              onfocus="this.style.borderColor='#C49520';"
                              onblur="this.style.borderColor='#E2D9C8';"
                              class="shadow-2xs pb-8"></textarea>
                    
                    <div style="position:absolute;bottom:14px;right:16px;display:flex;align-items:center;gap:4px;background:rgba(255,255,255,0.95);padding:2px 8px;border-radius:9999px;border:1px solid #E2D9C8;font-size:10px;font-weight:700;color:#78716C;pointer-events:none;">
                        <span id="charCounter" x-text="description ? description.length : 0">0</span><span style="color:#A8A096;">/</span><span>500</span>
                    </div>
                </div>
            </div>

            {{-- Step 3 Bottom Submission Actions --}}
            <div style="padding-top:20px;display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;border-top:1px solid #F2ECE1;">
                <button type="button" 
                        @click="step = 2; window.scrollTo({ top: 0, behavior: 'smooth' })"
                        style="padding:14px 24px;border-radius:12px;border:1px solid #E2D9C8;background-color:#FFFFFF;color:#1E1915;font-size:13px;font-weight:700;cursor:pointer;display:flex;align-items:center;gap:8px;box-shadow:0 1px 3px rgba(0,0,0,0.03);transition:all 0.2s;"
                        onmouseover="this.style.backgroundColor='#FAF8F5';"
                        onmouseout="this.style.backgroundColor='#FFFFFF';">
                    <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    <span>← Back to Step 2</span>
                </button>

                <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
                    <button type="button" 
                            @click="submitAsDraft()"
                            style="padding:14px 26px;border-radius:12px;border:1px solid #1C160E;background-color:#FFFFFF;color:#1C160E;font-size:13px;font-weight:700;letter-spacing:0.01em;cursor:pointer;display:flex;align-items:center;gap:8px;box-shadow:0 1px 3px rgba(0,0,0,0.03);transition:all 0.2s;"
                            onmouseover="this.style.backgroundColor='#FAF8F5';"
                            onmouseout="this.style.backgroundColor='#FFFFFF';">
                        <svg width="15" height="15" style="color:#78716C;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
                        <span>Save as Draft</span>
                    </button>

                    <button type="submit" 
                            @click="document.getElementById('formActionInput').value = 'publish'"
                            style="padding:14px 32px;border-radius:12px;border:none;background-color:#A16D19;color:#FFFFFF;font-size:13px;font-weight:700;letter-spacing:0.02em;cursor:pointer;display:flex;align-items:center;gap:10px;box-shadow:0 4px 14px rgba(161,109,25,0.25);transition:all 0.2s;"
                            onmouseover="this.style.backgroundColor='#8B5E14';"
                            onmouseout="this.style.backgroundColor='#A16D19';">
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
        'targetGroup'      => (string) old('target_group', 'Men'),
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

    // Ensure all 12 categories from the screenshot exist in parsedCats for Men
    const referenceMenCategories = [
        'Accessories',
        'Camisa de Chino',
        'Casual',
        'Formal Barong',
        'Heritage Accessories',
        'Jusi Classic Barong',
        'Lumban Specials',
        'Modern',
        'Piña Formal Barong',
        'Semi-Formal',
        'Special Occasion',
        'Traditional'
    ];

    referenceMenCategories.forEach((catName, idx) => {
        const exists = parsedCats.some(c => c.name.toLowerCase() === catName.toLowerCase());
        if (!exists) {
            parsedCats.push({
                id: 'ref_cat_' + (idx + 1),
                name: catName,
                target_group: ['Men', 'Women'],
                image: ''
            });
        } else {
            const item = parsedCats.find(c => c.name.toLowerCase() === catName.toLowerCase());
            if (item && Array.isArray(item.target_group) && !item.target_group.includes('Men')) {
                item.target_group.push('Men');
            }
        }
    });

    const initData = getProductInitData();

    return {
        step: 1,
        productName: initData.name || '',
        selectedCategory: initData.categoryId || '',
        targetGroup: initData.targetGroup || 'Men',
        fabricType: initData.fabricType || '100% Piña',
        price: initData.price || '',
        description: initData.description || '',
        fillRate: 15,
        isAiLoading: false,

        // Media State: Variant 1 (Cover Photo) + Additional Gallery Photos
        variants: [
            { id: 0, name: '', file: null, imagePreview: null }
        ],
        galleryImages: [], // array of { file, preview }

        get imageCount() {
            let count = this.variants.filter(v => v && v.imagePreview !== null).length;
            count += this.galleryImages.length;
            return count;
        },

        handleCoverPhotoUpload(event) {
            const file = event.target.files[0];
            if (file) {
                if (file.size > 5 * 1024 * 1024) {
                    triggerAppModal('Image Exceeds 5MB', 'Selected photo exceeds the 5MB size limit.', 'warning');
                    event.target.value = '';
                    return;
                }
                this.variants[0].file = file;
                const reader = new FileReader();
                reader.onload = (e) => {
                    this.variants[0].imagePreview = e.target.result;
                    const box = document.getElementById('variant_upload_box_0');
                    if (box) box.classList.remove('border-red-500');
                    this.calculateFillRate();
                };
                reader.readAsDataURL(file);
            }
        },

        removeCoverPhoto() {
            this.variants[0].file = null;
            this.variants[0].imagePreview = null;
            const input = document.getElementById('variant_file_0');
            if (input) input.value = '';
            this.calculateFillRate();
        },

        handleGalleryFilesUpload(event) {
            const files = Array.from(event.target.files);
            if (!files.length) return;

            files.forEach(file => {
                if (file.size > 5 * 1024 * 1024) return;
                const reader = new FileReader();
                reader.onload = (e) => {
                    if (this.galleryImages.length < 3) {
                        this.galleryImages.push({
                            file: file,
                            preview: e.target.result
                        });
                        this.calculateFillRate();
                    }
                };
                reader.readAsDataURL(file);
            });
        },

        removeGalleryImage(index) {
            if (index >= 0 && index < this.galleryImages.length) {
                this.galleryImages.splice(index, 1);
                this.calculateFillRate();
            }
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
            }).sort((a, b) => a.name.localeCompare(b.name));
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
            const hasName = Boolean(this.productName && this.productName.trim().length >= 3);
            const hasCategory = Boolean(this.selectedCategory && this.selectedCategory !== '');
            const hasTarget = Boolean(this.targetGroup && ['Men', 'Women', 'Kids'].includes(this.targetGroup));
            const hasMainImage = Boolean(this.variants[0] && this.variants[0].imagePreview !== null);
            return hasName && hasCategory && hasTarget && hasMainImage;
        },

        goToStep2() {
            if (!this.variants[0] || !this.variants[0].imagePreview) {
                triggerAppModal('Cover Photo Required', 'Please upload a Cover Photo for Variant 1.', 'warning');
                const v1Box = document.getElementById('variant_upload_box_0');
                if (v1Box) v1Box.classList.add('border-red-500');
                return;
            }

            if (!this.productName || this.productName.trim().length < 3) {
                triggerAppModal('Product Name Required', 'Please enter a product name with at least 3 characters.', 'warning');
                const nameInput = document.getElementById('productNameInput');
                if (nameInput) {
                    nameInput.classList.add('border-red-500');
                    nameInput.focus();
                }
                return;
            }

            if (!this.targetGroup || !['Men', 'Women', 'Kids'].includes(this.targetGroup)) {
                triggerAppModal('Target Tag Required', 'Please select who this product is for (Men, Women, or Kids).', 'warning');
                return;
            }

            if (!this.selectedCategory || this.selectedCategory === '') {
                triggerAppModal('Category Required', 'Please select a product category from the options below.', 'warning');
                const catContainer = document.getElementById('category-cards-container');
                if (catContainer) catContainer.classList.add('border-red-500');
                return;
            }

            this.step = 2;
            this.calculateFillRate();
            setTimeout(() => {
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }, 100);
        },

        goToStep3() {
            const priceVal = parseFloat(this.price);
            if (isNaN(priceVal) || priceVal < 1 || priceVal > 10000) {
                triggerAppModal('Valid Price Required', 'Please enter a valid base price between ₱1.00 and ₱10,000.00.', 'warning');
                const priceCard = document.getElementById('price-card');
                if (priceCard) priceCard.classList.add('border-red-500');
                return;
            }

            const checkedSizes = document.querySelectorAll('.size-checkbox:checked');
            if (checkedSizes.length === 0) {
                triggerAppModal('Size Required', 'Please check at least one Heritage Size (e.g. S, M, L, XL, XXL, Custom).', 'warning');
                const sizeSec = document.getElementById('sizing-section');
                if (sizeSec) sizeSec.classList.add('border-red-500');
                return;
            }

            const totalStock = parseInt(document.getElementById('total_stock')?.value || 0);
            if (totalStock <= 0) {
                triggerAppModal('Stock Quantity Required', 'Please enter stock quantity greater than 0 for checked sizes.', 'warning');
                return;
            }

            this.step = 3;
            this.calculateFillRate();
            setTimeout(() => {
                window.scrollTo({ top: 0, behavior: 'smooth' });
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

        async generateDescriptionAi() {
            if (this.isAiLoading) return;
            this.isAiLoading = true;
            try {
                const initData = getProductInitData();
                const selectedCatName = this.selectedCategoryObj ? this.selectedCategoryObj.name : '';
                const variantNames = this.variants.map(v => v.name).filter(Boolean);

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
                            textarea.classList.add('ring-2', 'ring-[#A16D19]', 'border-[#A16D19]');
                            setTimeout(() => {
                                textarea.classList.remove('ring-2', 'ring-[#A16D19]', 'border-[#A16D19]');
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
        if (cb.checked && inputs[idx]) {
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

    if (!priceInput || !pctInput || !preview) return;

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
    const targetGroupVal = targetGroupChecked ? targetGroupChecked.value : '';
    if (!targetGroupVal || !['Men', 'Women', 'Kids'].includes(targetGroupVal)) {
        errors.push('Please specify who this product is for (Men, Women, or Kids).');
    }

    const categorySelect = document.getElementById('categorySelect') || document.querySelector('input[name="CategoryId"]');
    const categoryVal = categorySelect ? categorySelect.value : '';
    if (!categoryVal) {
        errors.push('Please select a Product Category.');
        const catContainer = document.getElementById('category-cards-container');
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
