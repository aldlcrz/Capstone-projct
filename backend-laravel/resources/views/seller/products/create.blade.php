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

    {{-- Restored Draft Notice Banner --}}
    <div x-show="hasRestoredDraft"
         x-transition
         x-cloak
         style="margin-bottom:18px;padding:14px 20px;border-radius:20px;background:#FDF8EE;border:1px solid #EEDBBA;display:flex;align-items:center;justify-content:space-between;gap:12px;box-shadow:0 2px 6px rgba(0,0,0,0.02);">
        <div style="display:flex;align-items:center;gap:10px;">
            <span style="font-size:18px;">✨</span>
            <div>
                <span style="font-size:13px;font-weight:700;color:#7A5505;">Unsaved draft recovered!</span>
                <span style="font-size:12px;color:#8C7355;margin-left:4px;">We restored your creation inputs from your previous session.</span>
            </div>
        </div>
        <button type="button" 
                @click="clearDraftAndReset()"
                style="font-size:11.5px;font-weight:700;color:#DC2626;background:#FFF5F5;border:1px solid #FECACA;border-radius:10px;padding:5px 12px;cursor:pointer;white-space:nowrap;transition:all 0.2s;"
                onmouseover="this.style.background='#DC2626'; this.style.color='#FFFFFF';"
                onmouseout="this.style.background='#FFF5F5'; this.style.color='#DC2626';">
            Discard & Start Fresh
        </button>
    </div>

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
                        /* Target Audience Pills */
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
                        }
                        .target-pill-selected {
                            background-color: #221F1C !important;
                            color: #FCFAF6 !important;
                            border-color: #C49520 !important;
                            box-shadow: 0 4px 14px rgba(34,31,28,0.18), 0 1px 3px rgba(0,0,0,0.06) !important;
                            font-weight: 600 !important;
                        }
                        .target-checkmark { color: #C49520; font-size: 13px; font-weight: 800; }

                        /* Category Pills */
                        .cat-pill {
                            width: 100%;
                            min-height: 46px;
                            padding: 10px 16px;
                            border-radius: 12px;
                            font-size: 13px;
                            font-weight: 500;
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
                            background-color: #FCFAF6;
                            color: #221F1C;
                            user-select: none;
                        }
                        .cat-pill:hover:not(.cat-pill-selected) {
                            background-color: #F5ECD8;
                            border-color: #C8AC70;
                        }
                        .cat-pill-selected {
                            background-color: #221F1C !important;
                            color: #FCFAF6 !important;
                            border-color: #C49520 !important;
                            box-shadow: 0 4px 14px rgba(34,31,28,0.18), 0 1px 3px rgba(0,0,0,0.06) !important;
                            font-weight: 600 !important;
                        }
                        .cat-checkmark {
                            position: absolute;
                            right: 12px;
                            top: 50%;
                            transform: translateY(-50%);
                            color: #C49520;
                            font-size: 12px;
                            font-weight: 800;
                        }
                        .cat-pill-selected .cat-checkmark { color: #C49520; }
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
                                  x-text="filteredCategories.length + ' Available'"></span>
                            <span style="font-size:11px;font-weight:600;border-radius:20px;padding:3px 12px;background-color:#E8F5E9;border:1px solid #A5D6A7;color:#2E7D32;"
                                  x-show="selectedCategories.length > 0"
                                  x-text="selectedCategories.length + ' Selected'"></span>
                        </div>
                    </div>

                    {{-- Hidden inputs for multi-category form submission --}}
                    <template x-for="catId in selectedCategories" :key="catId">
                        <input type="hidden" name="category_ids[]" :value="catId">
                    </template>
                    {{-- Keep legacy CategoryId for backward compat (first selected) --}}
                    <input type="hidden" name="CategoryId" id="categorySelect" :value="selectedCategories[0] || ''">

                    {{-- Category Cards Grid (Pill style, multi-select) --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3 pt-1" id="category-cards-container">
                        <template x-for="cat in filteredCategories" :key="cat.id">
                            <button type="button" 
                                    @click="toggleCategory(cat)"
                                    class="cat-pill"
                                    :class="selectedCategories.includes(cat.id) ? 'cat-pill-selected' : ''">
                                <span x-text="cat.name" style="line-height:1.3;"></span>
                                <span class="cat-checkmark" x-show="selectedCategories.includes(cat.id)">✓</span>
                            </button>
                        </template>

                        <template x-if="filteredCategories.length === 0">
                            <div class="col-span-full py-8 text-center text-xs text-[#78716C] font-medium" style="background:#FAF8F5;border:1px dashed #E2D9C8;border-radius:16px;">
                                No categories currently available for this tag.
                            </div>
                        </template>
                    </div>

                    {{-- Selected Categories Confirmation Badge --}}
                    <div x-show="selectedCategories.length > 0"
                         style="padding:10px 16px;border-radius:14px;background-color:#FDF8EE;border:1px solid #EEDBBA;margin-top:10px;display:flex;align-items:flex-start;justify-content:space-between;gap:8px;flex-wrap:wrap;">
                        <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                            <span style="color:#C49520;font-size:13px;font-weight:900;">✓</span>
                            <span style="font-size:11px;color:#7A5505;font-weight:600;text-transform:uppercase;letter-spacing:0.04em;">Categories:</span>
                            <template x-for="catId in selectedCategories" :key="catId">
                                <span style="font-size:11px;background:#221F1C;color:#FCFAF6;border-radius:9999px;padding:2px 10px;font-weight:600;display:inline-flex;align-items:center;gap:4px;">
                                    <span x-text="categoriesList.find(c => c.id === catId)?.name || catId"></span>
                                    <button type="button" @click.stop="toggleCategory({id: catId})" style="margin-left:2px;font-size:11px;color:#C49520;background:none;border:none;cursor:pointer;padding:0;line-height:1;">×</button>
                                </span>
                            </template>
                        </div>
                        <span style="font-size:11px;color:#A07218;font-weight:600;" class="hidden sm:inline">Lumban Verified ✦</span>
                    </div>
                </div>
            </div>

            {{-- Footer Action Bar --}}
            <div style="margin-top:28px;padding-top:20px;border-top:1px solid #F2ECE1;display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;">
                <div>
                    <div style="font-size:13px;font-weight:700;color:#1E1915;display:flex;align-items:center;gap:6px;">
                        <span>Next: Complete Product Details</span>
                        <span style="font-weight:700;">→</span>
                    </div>
                    <div style="display:flex;align-items:center;gap:8px;margin-top:6px;flex-wrap:wrap;">
                        {{-- Photo Status --}}
                        <span class="rounded-full"
                              style="font-size:10.5px;font-weight:700;border-radius:9999px !important;padding:4px 12px !important;display:inline-flex;align-items:center;gap:4px;"
                              :style="variants[0] && variants[0].imagePreview ? 'border-radius:9999px !important;padding:4px 12px !important;font-size:10.5px;font-weight:700;background:#E8F5E9;color:#2E7D32;border:1px solid #A5D6A7;display:inline-flex;align-items:center;' : 'border-radius:9999px !important;padding:4px 12px !important;font-size:10.5px;font-weight:700;background:#FEF2F2;color:#DC2626;border:1px solid #FECACA;display:inline-flex;align-items:center;'">
                            <span x-text="variants[0] && variants[0].imagePreview ? '✓ Photo added' : '✕ Photo missing'"></span>
                        </span>

                        {{-- Name Status --}}
                        <span class="rounded-full"
                              style="font-size:10.5px;font-weight:700;border-radius:9999px !important;padding:4px 12px !important;display:inline-flex;align-items:center;gap:4px;"
                              :style="productName && productName.trim().length >= 3 ? 'border-radius:9999px !important;padding:4px 12px !important;font-size:10.5px;font-weight:700;background:#E8F5E9;color:#2E7D32;border:1px solid #A5D6A7;display:inline-flex;align-items:center;' : 'border-radius:9999px !important;padding:4px 12px !important;font-size:10.5px;font-weight:700;background:#FEF2F2;color:#DC2626;border:1px solid #FECACA;display:inline-flex;align-items:center;'">
                            <span x-text="productName && productName.trim().length >= 3 ? '✓ Name set' : '✕ Name missing'"></span>
                        </span>

                        {{-- Target Status --}}
                        <span class="rounded-full"
                              style="font-size:10.5px;font-weight:700;border-radius:9999px !important;padding:4px 12px !important;display:inline-flex;align-items:center;gap:4px;"
                              :style="targetGroup ? 'border-radius:9999px !important;padding:4px 12px !important;font-size:10.5px;font-weight:700;background:#E8F5E9;color:#2E7D32;border:1px solid #A5D6A7;display:inline-flex;align-items:center;' : 'border-radius:9999px !important;padding:4px 12px !important;font-size:10.5px;font-weight:700;background:#FEF2F2;color:#DC2626;border:1px solid #FECACA;display:inline-flex;align-items:center;'">
                            <span x-text="targetGroup ? '✓ ' + targetGroup : '✕ Target missing'"></span>
                        </span>

                        {{-- Category Status --}}
                        <span class="rounded-full"
                              style="font-size:10.5px;font-weight:700;border-radius:9999px !important;padding:4px 12px !important;display:inline-flex;align-items:center;gap:4px;"
                              :style="selectedCategories.length > 0 ? 'border-radius:9999px !important;padding:4px 12px !important;font-size:10.5px;font-weight:700;background:#E8F5E9;color:#2E7D32;border:1px solid #A5D6A7;display:inline-flex;align-items:center;' : 'border-radius:9999px !important;padding:4px 12px !important;font-size:10.5px;font-weight:700;background:#FEF2F2;color:#DC2626;border:1px solid #FECACA;display:inline-flex;align-items:center;'">
                            <span x-text="selectedCategories.length > 0 ? '✓ Category selected' : '✕ Category missing'"></span>
                        </span>

                        <span id="draft-save-indicator" style="display:none;font-size:10px;font-weight:600;color:#2E7D32;white-space:nowrap;background:#E8F5E9;border:1px solid #A5D6A7;border-radius:9999px;padding:3px 10px;">✓ Draft saved</span>
                    </div>
                </div>

                <button type="button" 
                        @click="goToStep2()"
                        class="inline-flex items-center gap-2 font-bold transition-all border-0 shadow-sm cursor-pointer"
                        style="padding:12px 28px !important;border-radius:9999px !important;font-size:14px;display:inline-flex;align-items:center;gap:8px;border:none;transition:all 0.2s;"
                        :style="isStep1Complete 
                            ? 'background-color:#A16D19 !important;color:#FFFFFF !important;cursor:pointer;box-shadow:0 3px 10px rgba(161,109,25,0.3);border-radius:9999px !important;padding:12px 28px !important;font-size:14px;font-weight:700;display:inline-flex;align-items:center;gap:8px;border:none;' 
                            : 'background-color:#1E1915 !important;color:#FFFFFF !important;cursor:pointer;box-shadow:0 2px 8px rgba(0,0,0,0.15);border-radius:9999px !important;padding:12px 28px !important;font-size:14px;font-weight:700;display:inline-flex;align-items:center;gap:8px;border:none;'"
                        onmouseover="this.style.backgroundColor='#8A5C14';"
                        onmouseout="this.style.backgroundColor=isStep1Complete ? '#A16D19' : '#1E1915';">
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
                    <span class="rounded-full"
                          style="font-size:10.5px;font-weight:700;border-radius:9999px !important;padding:4px 12px !important;text-transform:uppercase;letter-spacing:0.04em;display:inline-flex;align-items:center;gap:4px;"
                          :style="hasValidSizing 
                              ? 'border-radius:9999px !important;padding:4px 12px !important;font-size:10.5px;font-weight:700;background:#E8F5E9;color:#2E7D32;border:1px solid #A5D6A7;display:inline-flex;align-items:center;' 
                              : 'border-radius:9999px !important;padding:4px 12px !important;font-size:10.5px;font-weight:700;background:#FEF2F2;color:#DC2626;border:1px solid #FECACA;display:inline-flex;align-items:center;'">
                        <span x-text="hasValidSizing ? '✓ Sizing configured' : 'At least 1 size required'"></span>
                    </span>
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
                <div style="display:flex;align-items:center;justify-content:space-between;padding-bottom:12px;border-bottom:1px solid #F2ECE1;flex-wrap:wrap;gap:10px;">
                    <div style="display:flex;align-items:center;gap:12px;">
                        <div style="width:32px;height:32px;border-radius:50%;background-color:#FDF8EE;border:1px solid #EEDBBA;display:flex;align-items:center;justify-content:center;color:#7A5505;font-family:ui-serif,Georgia,serif;font-weight:700;font-size:13px;flex-shrink:0;">2</div>
                        <div>
                            <h3 style="font-family:ui-serif,Georgia,Cambria,serif;font-size:16px;font-weight:700;color:#1E1915;margin:0;">Price & Shipping Information <span style="color:#DC2626;">*</span></h3>
                            <p style="font-size:12px;color:#78716C;margin-top:2px;margin-bottom:0;">Define fair artisan pricing and realistic delivery estimates</p>
                        </div>
                    </div>
                    <span class="rounded-full"
                          style="font-size:10.5px;font-weight:700;border-radius:9999px !important;padding:4px 12px !important;text-transform:uppercase;letter-spacing:0.04em;display:inline-flex;align-items:center;gap:4px;"
                          :style="isPricingComplete 
                              ? 'border-radius:9999px !important;padding:4px 12px !important;font-size:10.5px;font-weight:700;background:#E8F5E9;color:#2E7D32;border:1px solid #A5D6A7;display:inline-flex;align-items:center;' 
                              : 'border-radius:9999px !important;padding:4px 12px !important;font-size:10.5px;font-weight:700;background:#FEF2F2;color:#DC2626;border:1px solid #FECACA;display:inline-flex;align-items:center;'">
                        <span x-text="pricingStatusText"></span>
                    </span>
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
                               oninput="if(parseFloat(this.value) > 10000) this.value = 10000; updateDiscountPreview(); calculateFillRate(); document.getElementById('price-card')?.classList.remove('border-red-500', 'ring-2', 'ring-red-400'); this.classList.remove('border-red-500');"
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
                               min="1" 
                               max="500" 
                               step="0.01" 
                               placeholder="0.00"
                               x-model="shippingFee"
                               oninput="if(parseFloat(this.value) > 500) this.value = 500; calculateFillRate(); document.getElementById('shipping-fee-card')?.classList.remove('border-red-500', 'ring-2', 'ring-red-400'); this.classList.remove('border-red-500');"
                               style="width:100%;background:transparent;font-size:18px;font-weight:700;color:#1E1915;outline:none;border:none;">
                        <p style="font-size:9px;color:#A8A096;margin:0;">Min. ₱1.00 (Max ₱500.00)</p>
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
                               x-model="shippingDays"
                               oninput="if(parseInt(this.value) > 30) this.value = 30; calculateFillRate(); document.getElementById('shipping-days-card')?.classList.remove('border-red-500', 'ring-2', 'ring-red-400'); this.classList.remove('border-red-500');"
                               style="width:100%;background:transparent;font-size:18px;font-weight:700;color:#1E1915;outline:none;border:none;">
                        <p style="font-size:9px;color:#A8A096;margin:0;">Delivery lead time</p>
                    </div>
                </div>

                {{-- Lumban Special Discount Panel --}}
                <div style="padding:14px 16px;border-radius:18px;background-color:#FDF8EE;border:1px solid #EEDBBA;">
                    <input type="hidden" name="is_on_sale" id="isOnSaleInput" value="0">

                    <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;">
                        <div style="display:flex;align-items:center;gap:8px;min-width:0;flex:1;">
                            <span style="width:8px;height:8px;border-radius:50%;background-color:#C49520;flex-shrink:0;display:inline-block;"></span>
                            <div style="display:flex;align-items:baseline;gap:4px 6px;flex-wrap:wrap;min-width:0;">
                                <span style="font-size:12px;font-weight:700;color:#7A5505;text-transform:uppercase;letter-spacing:0.04em;line-height:1.2;">Special Price / Sale</span>
                                <span style="font-size:10px;color:#78716C;font-weight:600;text-transform:uppercase;line-height:1.2;">(Optional)</span>
                            </div>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer shrink-0" style="margin:0;line-height:1;">
                            <input type="checkbox" id="discountToggle" class="sr-only peer" onchange="toggleDiscount(this)">
                            <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-0.5 after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-[#1C160E]"></div>
                        </label>
                    </div>

                    <div id="discountFields" class="hidden space-y-2.5 pt-3.5 mt-3.5 border-t border-[#EEDBBA]">
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
            <div class="pt-5 flex flex-col-reverse sm:flex-row items-stretch sm:items-center justify-between gap-3 sm:gap-4">
                <button type="button" 
                        @click="step = 1; window.scrollTo({ top: 0, behavior: 'smooth' })"
                        style="padding:13px 24px;border-radius:9999px;border:1px solid #E2D9C8;background-color:#FFFFFF;color:#1E1915;font-size:13.5px;font-weight:700;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;gap:8px;box-shadow:0 1px 3px rgba(0,0,0,0.03);transition:all 0.2s;"
                        onmouseover="this.style.backgroundColor='#FAF8F5';"
                        onmouseout="this.style.backgroundColor='#FFFFFF';">
                    <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    <span>Back to Step 1</span>
                </button>

                <button type="button" 
                        @click="goToStep3()"
                        style="padding:13px 28px;border-radius:9999px;font-size:14px;font-weight:700;background-color:#A16D19;color:#FFFFFF;border:none;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;gap:8px;box-shadow:0 3px 10px rgba(161,109,25,0.3);transition:all 0.2s;"
                        onmouseover="this.style.backgroundColor='#8A5C14';"
                        onmouseout="this.style.backgroundColor='#A16D19';">
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
                    <button type="button" @click="openPaymentModal('gcash')" style="font-size:12px;font-weight:700;color:#7A5505;text-decoration:none;display:flex;align-items:center;gap:4px;background:none;border:none;cursor:pointer;" onmouseover="this.style.color='#C49520'" onmouseout="this.style.color='#7A5505'">
                        Settings ↗
                    </button>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-1">
                    {{-- GCash Card --}}
                    <div x-data="{ isGcashOn: {{ old('product_is_gcash_available', true) ? 'true' : 'false' }} }" 
                         style="border-radius:20px;border:1px solid #E2D9C8;background:#FFFFFF;box-shadow:0 2px 8px rgba(0,0,0,0.02);overflow:hidden;transition:all 0.2s;"
                         :style="isGcashOn ? 'border-color:#BFDBFE;box-shadow:0 4px 14px rgba(37,99,235,0.06);' : 'border-color:#E8DECB;opacity:0.85;'">
                        
                        {{-- Card Header --}}
                        <div style="padding:14px 16px;display:flex;align-items:center;justify-content:space-between;gap:10px;background:#FAF8F5;border-bottom:1px solid #F0E8D9;">
                            <div style="display:flex;align-items:center;gap:8px;min-width:0;">
                                <div style="display:inline-flex;align-items:center;gap:6px;padding:4px 10px;border-radius:9999px;background:#2563EB;color:#FFFFFF;font-size:11px;font-weight:800;letter-spacing:0.04em;box-shadow:0 1px 3px rgba(37,99,235,0.25);">
                                    <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                                    <span>GCash</span>
                                </div>
                                <template x-if="isGcashOn">
                                    <span>
                                        <template x-if="paymentState.isGcashComplete">
                                            <span style="font-size:10px;font-weight:700;padding:3px 8px;border-radius:9999px;background:#ECFDF5;color:#16A34A;border:1px solid #BBF7D0;">✓ Configured</span>
                                        </template>
                                        <template x-if="!paymentState.isGcashComplete">
                                            <span style="font-size:10px;font-weight:700;padding:3px 8px;border-radius:9999px;background:#FFFBEB;color:#D97706;border:1px solid #FDE68A;">⚠ Setup Needed</span>
                                        </template>
                                    </span>
                                </template>
                                <template x-if="!isGcashOn">
                                    <span style="font-size:10px;font-weight:600;padding:3px 8px;border-radius:9999px;background:#F3F4F6;color:#6B7280;border:1px solid #E5E7EB;">○ Disabled</span>
                                </template>
                            </div>

                            <label class="relative inline-flex items-center cursor-pointer shrink-0" style="margin:0;line-height:1;">
                                <input type="checkbox" 
                                       name="product_is_gcash_available" 
                                       value="1" 
                                       id="gcash_toggle_create" 
                                       class="sr-only peer" 
                                       x-model="isGcashOn"
                                       @change="calculateFillRate()">
                                <div class="w-9 h-5 bg-gray-300 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-0.5 after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-[#2563EB]"></div>
                            </label>
                        </div>

                        {{-- Card Body --}}
                        <div style="padding:16px;">
                            <template x-if="isGcashOn">
                                <div>
                                    <template x-if="paymentState.isGcashComplete">
                                        <div style="display:flex;align-items:center;gap:14px;">
                                            <template x-if="paymentState.gcashQrUrl">
                                                <div @click="openLightbox(paymentState.gcashQrUrl)"
                                                     title="Click to view full size"
                                                     style="position:relative;width:56px;height:56px;border-radius:12px;border:1px solid #BFDBFE;background:#EFF6FF;padding:3px;flex-shrink:0;overflow:hidden;cursor:pointer;">
                                                    <img :src="paymentState.gcashQrUrl" alt="GCash QR" style="width:100%;height:100%;object-fit:contain;border-radius:8px;">
                                                </div>
                                            </template>
                                            <div style="min-width:0;flex:1;">
                                                <div style="display:flex;align-items:center;justify-content:space-between;gap:6px;">
                                                    <label style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.04em;color:#64748B;display:block;">GCash Mobile Number</label>
                                                    <button type="button" @click="openPaymentModal('gcash')" style="font-size:11px;font-weight:700;color:#2563EB;background:none;border:none;cursor:pointer;padding:0;" onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'">
                                                        GCash Setting
                                                    </button>
                                                </div>
                                                <div style="font-size:14.5px;font-weight:800;color:#1E1915;letter-spacing:0.02em;margin-top:2px;" x-text="paymentState.gcashNumber"></div>
                                                <p style="font-size:10px;color:#16A34A;font-weight:700;margin:3px 0 0 0;">✓ Ready to receive direct payments</p>
                                            </div>
                                        </div>
                                    </template>

                                    <template x-if="!paymentState.isGcashComplete">
                                        <div style="padding:12px;border-radius:14px;background:#FFFBEB;border:1px solid #FDE68A;">
                                            <div style="display:flex;align-items:flex-start;gap:8px;">
                                                <svg style="width:16px;height:16px;color:#D97706;flex-shrink:0;margin-top:1px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                                <div style="flex:1;min-width:0;">
                                                    <div style="font-size:12px;font-weight:800;color:#92400E;">Incomplete GCash Setup</div>
                                                    <p style="font-size:11px;color:#B45309;margin:2px 0 8px 0;line-height:1.4;">
                                                        <template x-if="!paymentState.hasGcashNumber && !paymentState.hasGcashQr">
                                                            <span>Both your GCash mobile number and QR code must be configured.</span>
                                                        </template>
                                                        <template x-if="paymentState.hasGcashNumber && !paymentState.hasGcashQr">
                                                            <span>Your GCash QR code image has not been uploaded yet.</span>
                                                        </template>
                                                        <template x-if="!paymentState.hasGcashNumber && paymentState.hasGcashQr">
                                                            <span>Your GCash mobile number is missing.</span>
                                                        </template>
                                                    </p>
                                                    <button type="button" @click="openPaymentModal('gcash')" style="display:inline-flex;align-items:center;gap:4px;font-size:11.5px;font-weight:700;color:#2563EB;text-decoration:none;background:none;border:none;cursor:pointer;padding:0;" onmouseover="this.style.textDecoration='underline';" onmouseout="this.style.textDecoration='none';">
                                                        <span>GCash Setting</span>
                                                        <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </template>

                            <template x-if="!isGcashOn">
                                <div style="padding:10px 12px;border-radius:12px;background:#F9FAFB;border:1px dashed #E5E7EB;display:flex;align-items:center;gap:8px;">
                                    <span style="font-size:11px;color:#6B7280;">GCash is turned off for this item. Switch toggle on to accept GCash.</span>
                                </div>
                            </template>
                        </div>
                    </div>

                    {{-- Maya Card --}}
                    <div x-data="{ isMayaOn: {{ old('product_is_maya_available', false) ? 'true' : 'false' }} }" 
                         style="border-radius:20px;border:1px solid #E2D9C8;background:#FFFFFF;box-shadow:0 2px 8px rgba(0,0,0,0.02);overflow:hidden;transition:all 0.2s;"
                         :style="isMayaOn ? 'border-color:#A7F3D0;box-shadow:0 4px 14px rgba(5,150,105,0.06);' : 'border-color:#E8DECB;opacity:0.85;'">
                        
                        {{-- Card Header --}}
                        <div style="padding:14px 16px;display:flex;align-items:center;justify-content:space-between;gap:10px;background:#FAF8F5;border-bottom:1px solid #F0E8D9;">
                            <div style="display:flex;align-items:center;gap:8px;min-width:0;">
                                <div style="display:inline-flex;align-items:center;gap:6px;padding:4px 10px;border-radius:9999px;background:#059669;color:#FFFFFF;font-size:11px;font-weight:800;letter-spacing:0.04em;box-shadow:0 1px 3px rgba(5,150,105,0.25);">
                                    <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                                    <span>Maya</span>
                                </div>
                                <template x-if="isMayaOn">
                                    <span>
                                        <template x-if="paymentState.isMayaComplete">
                                            <span style="font-size:10px;font-weight:700;padding:3px 8px;border-radius:9999px;background:#ECFDF5;color:#16A34A;border:1px solid #BBF7D0;">✓ Configured</span>
                                        </template>
                                        <template x-if="!paymentState.isMayaComplete">
                                            <span style="font-size:10px;font-weight:700;padding:3px 8px;border-radius:9999px;background:#FFFBEB;color:#D97706;border:1px solid #FDE68A;">⚠ Setup Needed</span>
                                        </template>
                                    </span>
                                </template>
                                <template x-if="!isMayaOn">
                                    <span style="font-size:10px;font-weight:600;padding:3px 8px;border-radius:9999px;background:#F3F4F6;color:#6B7280;border:1px solid #E5E7EB;">○ Disabled</span>
                                </template>
                            </div>

                            <label class="relative inline-flex items-center cursor-pointer shrink-0" style="margin:0;line-height:1;">
                                <input type="checkbox" 
                                       name="product_is_maya_available" 
                                       value="1" 
                                       id="maya_toggle_create" 
                                       class="sr-only peer" 
                                       x-model="isMayaOn"
                                       @change="calculateFillRate()">
                                <div class="w-9 h-5 bg-gray-300 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-0.5 after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-[#059669]"></div>
                            </label>
                        </div>

                        {{-- Card Body --}}
                        <div style="padding:16px;">
                            <template x-if="isMayaOn">
                                <div>
                                    <template x-if="paymentState.isMayaComplete">
                                        <div style="display:flex;align-items:center;gap:14px;">
                                            <template x-if="paymentState.mayaQrUrl">
                                                <div @click="openLightbox(paymentState.mayaQrUrl)"
                                                     title="Click to view full size"
                                                     style="position:relative;width:56px;height:56px;border-radius:12px;border:1px solid #A7F3D0;background:#ECFDF5;padding:3px;flex-shrink:0;overflow:hidden;cursor:pointer;">
                                                    <img :src="paymentState.mayaQrUrl" alt="Maya QR" style="width:100%;height:100%;object-fit:contain;border-radius:8px;">
                                                </div>
                                            </template>
                                            <div style="min-width:0;flex:1;">
                                                <div style="display:flex;align-items:center;justify-content:space-between;gap:6px;">
                                                    <label style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.04em;color:#64748B;display:block;">Maya Account Number</label>
                                                    <button type="button" @click="openPaymentModal('maya')" style="font-size:11px;font-weight:700;color:#059669;background:none;border:none;cursor:pointer;padding:0;" onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'">
                                                        Maya Setting
                                                    </button>
                                                </div>
                                                <div style="font-size:14.5px;font-weight:800;color:#1E1915;letter-spacing:0.02em;margin-top:2px;" x-text="paymentState.mayaNumber"></div>
                                                <p style="font-size:10px;color:#16A34A;font-weight:700;margin:3px 0 0 0;">✓ Ready to receive direct payments</p>
                                            </div>
                                        </div>
                                    </template>

                                    <template x-if="!paymentState.isMayaComplete">
                                        <div style="padding:12px;border-radius:14px;background:#FFFBEB;border:1px solid #FDE68A;">
                                            <div style="display:flex;align-items:flex-start;gap:8px;">
                                                <svg style="width:16px;height:16px;color:#D97706;flex-shrink:0;margin-top:1px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                                <div style="flex:1;min-width:0;">
                                                    <div style="font-size:12px;font-weight:800;color:#92400E;">Incomplete Maya Setup</div>
                                                    <p style="font-size:11px;color:#B45309;margin:2px 0 8px 0;line-height:1.4;">
                                                        <template x-if="!paymentState.hasMayaNumber && !paymentState.hasMayaQr">
                                                            <span>Both your Maya account number and QR code must be configured.</span>
                                                        </template>
                                                        <template x-if="paymentState.hasMayaNumber && !paymentState.hasMayaQr">
                                                            <span>Your Maya QR code image has not been uploaded yet.</span>
                                                        </template>
                                                        <template x-if="!paymentState.hasMayaNumber && paymentState.hasMayaQr">
                                                            <span>Your Maya account number is missing.</span>
                                                        </template>
                                                    </p>
                                                    <button type="button" @click="openPaymentModal('maya')" style="display:inline-flex;align-items:center;gap:4px;font-size:11.5px;font-weight:700;color:#059669;text-decoration:none;background:none;border:none;cursor:pointer;padding:0;" onmouseover="this.style.textDecoration='underline';" onmouseout="this.style.textDecoration='none';">
                                                        <span>Maya Setting</span>
                                                        <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </template>

                            <template x-if="!isMayaOn">
                                <div style="padding:10px 12px;border-radius:12px;background:#F9FAFB;border:1px dashed #E5E7EB;display:flex;align-items:center;gap:8px;">
                                    <span style="font-size:11px;color:#6B7280;">Maya is turned off for this item. Switch toggle on to accept Maya.</span>
                                </div>
                            </template>
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
            <div class="pt-5 flex flex-col-reverse sm:flex-row items-stretch sm:items-center justify-between gap-3 sm:gap-4 border-t border-[#F2ECE1]">
                <button type="button" 
                        @click="step = 2; window.scrollTo({ top: 0, behavior: 'smooth' })"
                        style="padding:13px 24px;border-radius:9999px;border:1px solid #E2D9C8;background-color:#FFFFFF;color:#1E1915;font-size:13.5px;font-weight:700;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;gap:8px;box-shadow:0 1px 3px rgba(0,0,0,0.03);transition:all 0.2s;"
                        onmouseover="this.style.backgroundColor='#FAF8F5';"
                        onmouseout="this.style.backgroundColor='#FFFFFF';">
                    <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    <span>Back to Step 2</span>
                </button>

                <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
                    <button type="button" 
                            @click="submitAsDraft()"
                            style="padding:13px 26px;border-radius:9999px;border:1px solid #1C160E;background-color:#FFFFFF;color:#1C160E;font-size:13.5px;font-weight:700;letter-spacing:0.01em;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;gap:8px;box-shadow:0 1px 3px rgba(0,0,0,0.03);transition:all 0.2s;"
                            onmouseover="this.style.backgroundColor='#FAF8F5';"
                            onmouseout="this.style.backgroundColor='#FFFFFF';">
                        <svg width="15" height="15" style="color:#78716C;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
                        <span>Save as Draft</span>
                    </button>

                    <button type="submit" 
                            @click="document.getElementById('formActionInput').value = 'publish'"
                            style="padding:13px 32px;border-radius:9999px;border:none;background-color:#A16D19;color:#FFFFFF;font-size:13.5px;font-weight:700;letter-spacing:0.02em;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;gap:10px;box-shadow:0 4px 14px rgba(161,109,25,0.25);transition:all 0.2s;"
                            onmouseover="this.style.backgroundColor='#8B5E14';"
                            onmouseout="this.style.backgroundColor='#A16D19';">
                        <span>Publish Heritage Piece</span>
                        <span style="font-size:15px;line-height:1;font-weight:700;">→</span>
                    </button>
                </div>
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

                {{-- ======================================================== --}}
                {{-- GCASH FORM ONLY                                          --}}
                {{-- ======================================================== --}}
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

                        {{-- Hidden native file input --}}
                        <input type="file" 
                               id="modal_gcash_qr_input" 
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
                                            @click="document.getElementById('modal_gcash_qr_input').click()" 
                                            class="px-3.5 py-2 rounded-xl text-xs font-bold text-blue-700 bg-blue-50 border border-blue-200 hover:bg-blue-100 transition-all cursor-pointer">
                                        Replace
                                    </button>
                                </div>
                            </div>
                        </template>

                        {{-- Empty State Dropzone --}}
                        <template x-if="!modalGcashQrPreview && !paymentState.gcashQrUrl">
                            <div @click="document.getElementById('modal_gcash_qr_input').click()" 
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

                {{-- ======================================================== --}}
                {{-- MAYA FORM ONLY                                           --}}
                {{-- ======================================================== --}}
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

                    {{-- Maya Mobile / Account Number Input --}}
                    <div class="space-y-1.5">
                        <label class="text-[11px] font-bold text-stone-700 uppercase tracking-wider block">
                            Maya Mobile / Account Number <span class="text-red-500">*</span>
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
                        <p class="text-[11px] text-stone-500">Enter your Maya registered mobile or account number.</p>
                    </div>

                    {{-- Maya QR Code Upload Section --}}
                    <div class="space-y-1.5">
                        <label class="text-[11px] font-bold text-stone-700 uppercase tracking-wider block">
                            Maya QR Code Image <span class="text-red-500">*</span>
                        </label>

                        {{-- Hidden native file input --}}
                        <input type="file" 
                               id="modal_maya_qr_input" 
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
                                            @click="document.getElementById('modal_maya_qr_input').click()" 
                                            class="px-3.5 py-2 rounded-xl text-xs font-bold text-emerald-700 bg-emerald-50 border border-emerald-200 hover:bg-emerald-100 transition-all cursor-pointer">
                                        Replace
                                    </button>
                                </div>
                            </div>
                        </template>

                        {{-- Empty State Dropzone --}}
                        <template x-if="!modalMayaQrPreview && !paymentState.mayaQrUrl">
                            <div @click="document.getElementById('modal_maya_qr_input').click()" 
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
                        <svg class="w-4 h-4 text-emerald-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <p class="text-[11px] text-stone-800 leading-relaxed font-medium">
                            Both your <strong>Maya Mobile/Account Number</strong> and <strong>QR Code Image</strong> are required so buyers can easily scan and complete transactions.
                        </p>
                    </div>
                </div>
            </div>

            {{-- Footer --}}
            <div class="px-6 py-4 border-t shrink-0 flex items-center gap-3" style="border-color: #E8DECB; background: #FAF7F0;">
                <button type="button" 
                        @click="savePaymentSettings()" 
                        :disabled="isSavingPayment"
                        :style="activePaymentTab === 'gcash' ? 'background-color:#2563EB !important;color:#FFFFFF !important;box-shadow:0 4px 14px rgba(37,99,235,0.35);' : 'background-color:#059669 !important;color:#FFFFFF !important;box-shadow:0 4px 14px rgba(5,150,105,0.35);'"
                        class="flex-1 py-3.5 px-6 rounded-2xl text-xs sm:text-sm font-extrabold uppercase tracking-wider cursor-pointer flex items-center justify-center gap-2 active:scale-98 disabled:opacity-50 transition-all">
                    <span x-show="!isSavingPayment" x-text="activePaymentTab === 'gcash' ? 'Save GCash Setting' : 'Save Maya Setting'"></span>
                    <span x-show="isSavingPayment" style="display:none;" class="flex items-center gap-2">
                        <svg class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                        </svg>
                        Saving...
                    </span>
                </button>
                <button type="button" 
                        @click="closePaymentModal()" 
                        :disabled="isSavingPayment"
                        class="px-6 py-3.5 rounded-2xl text-xs sm:text-sm font-bold uppercase tracking-wider cursor-pointer hover:bg-stone-200/60 transition-all shrink-0" 
                        style="background: #FFFFFF; border: 1px solid #D6D3D1; color: #44403C;">
                    Cancel
                </button>
            </div>
        </div>
    </div>

    {{-- QR Code Lightbox --}}
    <div x-show="showQrLightbox" 
         x-cloak 
         style="display:none;" 
         class="fixed inset-0 z-200 bg-black/80 backdrop-blur-xs flex items-center justify-center p-6"
         @click="closeLightbox()"
         @keydown.escape.window="closeLightbox()">
        <div class="relative rounded-3xl p-4 shadow-2xl max-w-xs w-full flex flex-col items-center gap-4" style="background: #FFFCF7; border: 1px solid #E8DECB;" @click.stop>
            <button type="button" 
                    @click="closeLightbox()"
                    class="absolute top-3 right-3 w-8 h-8 rounded-xl flex items-center justify-center transition-all cursor-pointer"
                    style="background: #FDF8EE; color: #766C60;">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
            <p class="text-[9px] font-bold uppercase tracking-widest" style="color: #766C60;">QR Code Preview</p>
            <img :src="lightboxImgUrl" class="w-full max-w-60 h-auto object-contain rounded-2xl border shadow-xs" style="background: #FFF; border-color: #E8DECB;">
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
            'image' => $c->getImageUrl(),
        ];
    })->values();

    $currentUser = auth()->user();
    $getPaymentImgUrl = function($path) {
        if (empty($path)) return null;
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) return $path;
        $clean = ltrim($path, '/');
        if (str_starts_with($clean, 'uploads/')) return asset($clean);
        return asset('storage/' . $clean);
    };

    $productInitData = [
        'name'             => (string) old('name', ''),
        'categoryId'       => (string) old('CategoryId', ''),
        'targetGroup'      => (string) old('target_group', 'Men'),
        'fabricType'       => (string) old('fabric_type', '100% Piña'),
        'price'            => (string) old('price', ''),
        'shippingFee'      => (string) old('shippingFee', ''),
        'shippingDays'     => (string) old('shippingDays', '5'),
        'sellerId'         => (string) (auth()->id() ?? 'guest'),
        'description'      => (string) old('description', ''),
        'csrfToken'        => (string) csrf_token(),
        'aiSuggestUrl'     => (string) route('ai.seller.suggest'),
        'aiDescriptionUrl' => (string) route('ai.seller.description'),
        'paymentUpdateUrl' => (string) route('seller.profile.update'),
        'gcashNumber'      => (string) ($currentUser?->gcashNumber ?? ''),
        'gcashQrUrl'       => $currentUser?->gcashQrCode ? $getPaymentImgUrl($currentUser->gcashQrCode) : null,
        'hasGcashNumber'   => !empty($currentUser?->gcashNumber),
        'hasGcashQr'       => !empty($currentUser?->gcashQrCode),
        'mayaNumber'       => (string) ($currentUser?->mayaNumber ?? ''),
        'mayaQrUrl'        => $currentUser?->mayaQrCode ? $getPaymentImgUrl($currentUser->mayaQrCode) : null,
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

    referenceMenCategories.forEach((catName) => {
        const item = parsedCats.find(c => c.name && c.name.toLowerCase() === catName.toLowerCase());
        if (item && Array.isArray(item.target_group) && !item.target_group.includes('Men')) {
            item.target_group.push('Men');
        }
    });

    const initData = getProductInitData();

    return {
        step: 1,
        productName: initData.name || '',
        selectedCategories: initData.categoryIds || [],
        targetGroup: initData.targetGroup || 'Men',
        fabricType: initData.fabricType || '100% Piña',
        price: initData.price || '',
        shippingFee: initData.shippingFee || '',
        shippingDays: initData.shippingDays || '5',
        description: initData.description || '',
        fillRate: 15,
        isAiLoading: false,
        hasRestoredDraft: false,
        hasValidSizing: false,
        draftSaveTimer: null,

        // Payment Methods Reactive State
        paymentState: {
            gcashNumber: initData.gcashNumber || '',
            gcashQrUrl: initData.gcashQrUrl || null,
            hasGcashNumber: Boolean(initData.hasGcashNumber),
            hasGcashQr: Boolean(initData.hasGcashQr),
            get isGcashComplete() {
                return Boolean(this.hasGcashNumber && this.hasGcashQr);
            },
            mayaNumber: initData.mayaNumber || '',
            mayaQrUrl: initData.mayaQrUrl || null,
            hasMayaNumber: Boolean(initData.hasMayaNumber),
            hasMayaQr: Boolean(initData.hasMayaQr),
            get isMayaComplete() {
                return Boolean(this.hasMayaNumber && this.hasMayaQr);
            }
        },

        // Payment Methods Modal State
        showPaymentModal: false,
        activePaymentTab: 'all',
        modalGcashNumber: initData.gcashNumber || '',
        modalMayaNumber: initData.mayaNumber || '',
        modalGcashQrPreview: initData.gcashQrUrl || null,
        modalMayaQrPreview: initData.mayaQrUrl || null,
        modalGcashQrFile: null,
        modalMayaQrFile: null,
        isSavingPayment: false,
        paymentModalError: '',
        paymentModalSuccess: '',
        lightboxImgUrl: '',
        showQrLightbox: false,

        openPaymentModal(tab = 'gcash') {
            this.activePaymentTab = (tab === 'maya') ? 'maya' : 'gcash';
            this.modalGcashNumber = this.paymentState.gcashNumber || '';
            this.modalMayaNumber = this.paymentState.mayaNumber || '';
            this.modalGcashQrPreview = this.paymentState.gcashQrUrl || null;
            this.modalMayaQrPreview = this.paymentState.mayaQrUrl || null;
            this.modalGcashQrFile = null;
            this.modalMayaQrFile = null;
            this.paymentModalError = '';
            this.paymentModalSuccess = '';
            const gInput = document.getElementById('modal_gcash_qr_input');
            if (gInput) gInput.value = '';
            const mInput = document.getElementById('modal_maya_qr_input');
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
                this.paymentModalError = 'QR Code image must be 5MB or less.';
                event.target.value = '';
                return;
            }
            if (type === 'gcash') {
                this.modalGcashQrFile = file;
                const reader = new FileReader();
                reader.onload = (e) => {
                    this.modalGcashQrPreview = e.target.result;
                };
                reader.readAsDataURL(file);
            } else if (type === 'maya') {
                this.modalMayaQrFile = file;
                const reader = new FileReader();
                reader.onload = (e) => {
                    this.modalMayaQrPreview = e.target.result;
                };
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

                const url = initData.paymentUpdateUrl || '/seller/profile';
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

        get isPricingComplete() {
            const hasPrice = Boolean(this.price && parseFloat(this.price) >= 1 && parseFloat(this.price) <= 10000);
            const hasFee = Boolean(this.shippingFee !== '' && !isNaN(parseFloat(this.shippingFee)) && parseFloat(this.shippingFee) >= 1 && parseFloat(this.shippingFee) <= 500);
            return hasPrice && hasFee;
        },

        get pricingStatusText() {
            const hasPrice = Boolean(this.price && parseFloat(this.price) >= 1 && parseFloat(this.price) <= 10000);
            const hasFee = Boolean(this.shippingFee !== '' && !isNaN(parseFloat(this.shippingFee)) && parseFloat(this.shippingFee) >= 1 && parseFloat(this.shippingFee) <= 500);
            if (!hasPrice && !hasFee) return 'Price & shipping fee required';
            if (!hasPrice) return 'Base price required';
            if (!hasFee) return 'Shipping fee required (min ₱1)';
            return '✓ Price & shipping configured';
        },

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

        scheduleDraftSave() {
            clearTimeout(this.draftSaveTimer);
            this.draftSaveTimer = setTimeout(() => {
                this.saveDraftState();
            }, 300);
        },

        saveDraftState() {
            try {
                const sellerId = initData.sellerId || 'guest';
                const DRAFT_KEY = 'lumbarong_seller_product_draft_' + sellerId;

                const hasAnyData = Boolean(
                    (this.productName && this.productName.trim()) ||
                    (this.selectedCategories && this.selectedCategories.length) ||
                    (this.price && parseFloat(this.price) > 0) ||
                    (this.variants[0] && this.variants[0].imagePreview) ||
                    (this.description && this.description.trim())
                );

                if (!hasAnyData) return;

                const sizeStocks = {};
                const checkedSizes = [];
                document.querySelectorAll('.size-checkbox').forEach(cb => {
                    if (cb.checked) {
                        checkedSizes.push(cb.value);
                        const stockEl = document.getElementById('stock_' + cb.value);
                        sizeStocks[cb.value] = stockEl ? stockEl.value : '0';
                    }
                });

                const draftData = {
                    step: this.step || 1,
                    productName: this.productName || '',
                    selectedCategories: this.selectedCategories || [],
                    targetGroup: this.targetGroup || 'Men',
                    fabricType: this.fabricType || '100% Piña',
                    price: this.price || '',
                    description: this.description || '',
                    shippingFee: document.getElementById('shippingFeeInput')?.value || '0',
                    shippingDays: document.getElementById('shippingDaysInput')?.value || '5',
                    checkedSizes: checkedSizes,
                    sizeStocks: sizeStocks,
                    isOnSale: document.getElementById('discountToggle')?.checked || false,
                    discountPercentage: document.getElementById('discountPercentage')?.value || '',
                    variants: this.variants.map(v => ({ id: v.id, name: v.name, imagePreview: v.imagePreview })),
                    galleryImages: this.galleryImages.map(g => ({ preview: g.preview })),
                    isGcashAvailable: document.getElementById('gcash_toggle_create')?.checked ?? true,
                    isMayaAvailable: document.getElementById('maya_toggle_create')?.checked ?? false,
                    savedAt: Date.now()
                };

                localStorage.setItem(DRAFT_KEY, JSON.stringify(draftData));

                const indicator = document.getElementById('draft-save-indicator');
                if (indicator) {
                    indicator.style.display = 'inline-block';
                    clearTimeout(window._draftIndicatorTimer);
                    window._draftIndicatorTimer = setTimeout(() => {
                        indicator.style.display = 'none';
                    }, 2000);
                }
            } catch (e) {
                console.warn('Could not save draft to localStorage:', e);
            }
        },

        restoreDraftState() {
            try {
                const sellerId = initData.sellerId || 'guest';
                const DRAFT_KEY = 'lumbarong_seller_product_draft_' + sellerId;
                const raw = localStorage.getItem(DRAFT_KEY);
                if (!raw) return;

                const draft = JSON.parse(raw);
                if (!draft) return;

                const hasContent = Boolean(
                    (draft.productName && draft.productName.trim()) ||
                    (draft.selectedCategories && draft.selectedCategories.length) ||
                    (draft.price && parseFloat(draft.price) > 0) ||
                    (draft.variants && draft.variants[0] && draft.variants[0].imagePreview) ||
                    (draft.description && draft.description.trim())
                );

                if (!hasContent) return;

                if (draft.productName) this.productName = draft.productName;
                if (draft.targetGroup) this.targetGroup = draft.targetGroup;
                if (draft.fabricType) this.fabricType = draft.fabricType;
                if (draft.price) this.price = draft.price;
                if (draft.description) this.description = draft.description;
                if (Array.isArray(draft.selectedCategories)) this.selectedCategories = draft.selectedCategories;
                if (draft.step && [1, 2, 3].includes(draft.step)) this.step = draft.step;

                // Restore shipping
                const shipFeeEl = document.getElementById('shippingFeeInput');
                if (shipFeeEl && draft.shippingFee !== undefined) {
                    shipFeeEl.value = draft.shippingFee;
                    this.shippingFee = draft.shippingFee;
                }
                const shipDaysEl = document.getElementById('shippingDaysInput');
                if (shipDaysEl && draft.shippingDays !== undefined) {
                    shipDaysEl.value = draft.shippingDays;
                    this.shippingDays = draft.shippingDays;
                }

                // Restore discount
                const discToggle = document.getElementById('discountToggle');
                const discPct = document.getElementById('discountPercentage');
                if (discToggle && draft.isOnSale) {
                    discToggle.checked = true;
                    toggleDiscount(discToggle);
                    if (discPct && draft.discountPercentage) {
                        discPct.value = draft.discountPercentage;
                    }
                    updateDiscountPreview();
                }

                // Restore sizes
                if (Array.isArray(draft.checkedSizes)) {
                    draft.checkedSizes.forEach(size => {
                        const cb = document.getElementById('size_cb_' + size);
                        const stockEl = document.getElementById('stock_' + size);
                        if (cb) {
                            cb.checked = true;
                            if (stockEl) {
                                stockEl.removeAttribute('disabled');
                                stockEl.value = (draft.sizeStocks && draft.sizeStocks[size]) ? draft.sizeStocks[size] : '5';
                            }
                        }
                    });
                    calculateTotalStock();
                }

                // Restore payment toggles
                const gcashToggle = document.getElementById('gcash_toggle_create');
                if (gcashToggle && draft.isGcashAvailable !== undefined) {
                    gcashToggle.checked = draft.isGcashAvailable;
                    const gf = document.getElementById('gcash_fields_create');
                    if (gf) gf.style.display = draft.isGcashAvailable ? '' : 'none';
                }
                const mayaToggle = document.getElementById('maya_toggle_create');
                if (mayaToggle && draft.isMayaAvailable !== undefined) {
                    mayaToggle.checked = draft.isMayaAvailable;
                    const mf = document.getElementById('maya_fields_create');
                    if (mf) mf.style.display = draft.isMayaAvailable ? '' : 'none';
                }

                // Restore variants & cover photo
                if (Array.isArray(draft.variants) && draft.variants.length > 0) {
                    this.variants = draft.variants.map((v, idx) => {
                        let fileObj = null;
                        if (v.imagePreview) {
                            fileObj = dataURLtoFile(v.imagePreview, 'variant_' + idx + '.png');
                            if (fileObj && typeof DataTransfer !== 'undefined') {
                                const dt = new DataTransfer();
                                dt.items.add(fileObj);
                                const fileInput = document.getElementById('variant_file_' + idx);
                                if (fileInput) fileInput.files = dt.files;
                            }
                        }
                        return {
                            id: v.id ?? idx,
                            name: v.name || '',
                            file: fileObj,
                            imagePreview: v.imagePreview || null
                        };
                    });
                }

                // Restore gallery images
                if (Array.isArray(draft.galleryImages) && draft.galleryImages.length > 0) {
                    this.galleryImages = draft.galleryImages.map(g => {
                        let fileObj = null;
                        if (g.preview) {
                            fileObj = dataURLtoFile(g.preview, 'gallery_photo.png');
                        }
                        return {
                            file: fileObj,
                            preview: g.preview
                        };
                    });
                }

                this.hasRestoredDraft = true;
                this.calculateFillRate();
            } catch (e) {
                console.warn('Could not restore draft:', e);
            }
        },

        clearDraftAndReset() {
            try {
                const sellerId = initData.sellerId || 'guest';
                localStorage.removeItem('lumbarong_seller_product_draft_' + sellerId);
            } catch(e) {}
            this.hasRestoredDraft = false;
            window.location.href = window.location.pathname;
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
                    this.scheduleDraftSave();
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
            this.scheduleDraftSave();
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
                        this.scheduleDraftSave();
                    }
                };
                reader.readAsDataURL(file);
            });
        },

        removeGalleryImage(index) {
            if (index >= 0 && index < this.galleryImages.length) {
                this.galleryImages.splice(index, 1);
                this.calculateFillRate();
                this.scheduleDraftSave();
            }
        },

        addVariantRow() {
            const nextId = this.variants.length;
            this.variants.push({ id: nextId, name: '', file: null, imagePreview: null });
            this.calculateFillRate();
            this.scheduleDraftSave();
        },

        removeVariantRow(index) {
            if (index === 0) return;
            this.variants.splice(index, 1);
            this.calculateFillRate();
            this.scheduleDraftSave();
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
                    this.scheduleDraftSave();
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
            this.scheduleDraftSave();
        },

        // Real-time categories state
        categoriesList: parsedCats,

        init() {
            this.restoreDraftState();
            this.calculateFillRate();

            const form = document.getElementById('productForm');
            if (form) {
                form.addEventListener('input', () => this.scheduleDraftSave());
                form.addEventListener('change', () => this.scheduleDraftSave());
            }
        },

        get filteredCategories() {
            if (!Array.isArray(this.categoriesList)) return [];
            if (!this.targetGroup) return [];
            return this.categoriesList.filter(c => {
                if (!c) return false;
                let tg = c.target_group;
                if (Array.isArray(tg)) return tg.includes(this.targetGroup);
                if (typeof tg === 'string') return tg === this.targetGroup;
                return false;
            }).sort((a, b) => a.name.localeCompare(b.name));
        },

        toggleCategory(cat) {
            if (!cat) return;
            const idx = this.selectedCategories.indexOf(cat.id);
            if (idx === -1) {
                this.selectedCategories.push(cat.id);
            } else {
                this.selectedCategories.splice(idx, 1);
            }
            const catContainer = document.getElementById('category-cards-container');
            if (catContainer) catContainer.classList.remove('border-red-500');
            this.calculateFillRate();
            this.scheduleDraftSave();
        },

        onTargetGroupChange(group) {
            this.targetGroup = group;
            const tgContainer = document.getElementById('target-group-container');
            if (tgContainer) tgContainer.classList.remove('border-red-500', 'p-1', 'border', 'rounded-xl');

            // Filter out categories that don't belong to the new target group
            if (this.selectedCategories.length > 0) {
                this.selectedCategories = this.selectedCategories.filter(catId => {
                    const cat = Array.isArray(this.categoriesList) ? this.categoriesList.find(c => String(c.id) === String(catId)) : null;
                    if (!cat) return false;
                    let tg = cat.target_group;
                    return Array.isArray(tg) ? tg.includes(group) : (tg === group);
                });
            }

            const catContainer = document.getElementById('category-cards-container');
            if (catContainer) catContainer.classList.remove('border-red-500');
            this.calculateFillRate();
            this.scheduleDraftSave();
        },

        get isStep1Complete() {
            const hasName = Boolean(this.productName && this.productName.trim().length >= 3);
            const hasCategory = this.selectedCategories.length > 0;
            const hasTarget = Boolean(this.targetGroup && ['Men', 'Women', 'Kids'].includes(this.targetGroup));
            const hasMainImage = Boolean(this.variants[0] && this.variants[0].imagePreview !== null);
            return hasName && hasCategory && hasTarget && hasMainImage;
        },

        goToStep2() {
            // Remove previous error highlights
            document.querySelectorAll('.border-red-500, .ring-2.ring-red-400').forEach(el => {
                el.classList.remove('border-red-500', 'ring-2', 'ring-red-400');
            });

            if (!this.variants[0] || !this.variants[0].imagePreview) {
                const v1Box = document.getElementById('variant_upload_box_0');
                if (v1Box) {
                    v1Box.classList.add('border-red-500', 'ring-2', 'ring-red-400');
                    v1Box.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
                return;
            }

            if (!this.productName || this.productName.trim().length < 3) {
                const nameInput = document.getElementById('productNameInput');
                if (nameInput) {
                    nameInput.classList.add('border-red-500', 'ring-2', 'ring-red-400');
                    nameInput.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    nameInput.focus();
                }
                return;
            }

            if (!this.targetGroup || !['Men', 'Women', 'Kids'].includes(this.targetGroup)) {
                const tgContainer = document.getElementById('target-group-container');
                if (tgContainer) {
                    tgContainer.classList.add('border-red-500', 'ring-2', 'ring-red-400');
                    tgContainer.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
                return;
            }

            if (!this.selectedCategories || this.selectedCategories.length === 0) {
                const catContainer = document.getElementById('category-cards-container');
                if (catContainer) {
                    catContainer.classList.add('border-red-500', 'ring-2', 'ring-red-400');
                    catContainer.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
                return;
            }

            this.step = 2;
            this.calculateFillRate();
            this.scheduleDraftSave();
            setTimeout(() => {
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }, 100);
        },

        goToStep3() {
            // Remove previous Step 2 error highlights
            document.querySelectorAll('#price-card, #priceInput, #shipping-fee-card, #shippingFeeInput, #shipping-days-card, #shippingDaysInput, #sizing-section, #stock-card').forEach(el => {
                el.classList.remove('border-red-500', 'ring-2', 'ring-red-400');
            });

            const checkedSizes = document.querySelectorAll('.size-checkbox:checked');
            if (checkedSizes.length === 0) {
                const sizeSec = document.getElementById('sizing-section');
                if (sizeSec) {
                    sizeSec.classList.add('border-red-500', 'ring-2', 'ring-red-400');
                    sizeSec.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
                return;
            }

            const totalStock = parseInt(document.getElementById('total_stock')?.value || 0);
            if (totalStock <= 0) {
                const sizeSec = document.getElementById('sizing-section');
                const stockCard = document.getElementById('stock-card');
                if (sizeSec) sizeSec.classList.add('border-red-500', 'ring-2', 'ring-red-400');
                if (stockCard) stockCard.classList.add('border-red-500', 'ring-2', 'ring-red-400');
                const firstStockInput = document.querySelector('.size-checkbox:checked')?.closest('div')?.querySelector('.size-stock-input');
                if (firstStockInput) {
                    firstStockInput.focus();
                    firstStockInput.scrollIntoView({ behavior: 'smooth', block: 'center' });
                } else if (sizeSec) {
                    sizeSec.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
                return;
            }

            const priceVal = parseFloat(this.price);
            if (isNaN(priceVal) || priceVal < 1 || priceVal > 10000) {
                const priceCard = document.getElementById('price-card');
                const priceInput = document.getElementById('priceInput');
                if (priceCard) priceCard.classList.add('border-red-500', 'ring-2', 'ring-red-400');
                if (priceInput) {
                    priceInput.classList.add('border-red-500');
                    priceInput.focus();
                }
                if (priceCard) priceCard.scrollIntoView({ behavior: 'smooth', block: 'center' });
                return;
            }

            const shipFeeVal = parseFloat(this.shippingFee);
            if (this.shippingFee === '' || isNaN(shipFeeVal) || shipFeeVal < 0 || shipFeeVal > 500) {
                const feeCard = document.getElementById('shipping-fee-card');
                const feeInput = document.getElementById('shippingFeeInput');
                if (feeCard) feeCard.classList.add('border-red-500', 'ring-2', 'ring-red-400');
                if (feeInput) {
                    feeInput.classList.add('border-red-500');
                    feeInput.focus();
                }
                if (feeCard) feeCard.scrollIntoView({ behavior: 'smooth', block: 'center' });
                return;
            }

            const shipDaysVal = parseInt(this.shippingDays);
            if (this.shippingDays === '' || isNaN(shipDaysVal) || shipDaysVal < 1 || shipDaysVal > 30) {
                const daysCard = document.getElementById('shipping-days-card');
                const daysInput = document.getElementById('shippingDaysInput');
                if (daysCard) daysCard.classList.add('border-red-500', 'ring-2', 'ring-red-400');
                if (daysInput) {
                    daysInput.classList.add('border-red-500');
                    daysInput.focus();
                }
                if (daysCard) daysCard.scrollIntoView({ behavior: 'smooth', block: 'center' });
                return;
            }

            this.step = 3;
            this.calculateFillRate();
            this.scheduleDraftSave();
            setTimeout(() => {
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }, 100);
        },

        calculateFillRate() {
            let score = 0;
            if (this.imageCount > 0) score += 20;
            if (this.productName && this.productName.trim().length >= 3) score += 20;
            if (this.selectedCategories && this.selectedCategories.length > 0) score += 15;
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
                const firstCatId = this.selectedCategories[0] || '';
                const selectedCatObj = firstCatId ? (this.categoriesList || []).find(c => String(c.id) === String(firstCatId)) : null;
                const selectedCatName = selectedCatObj ? selectedCatObj.name : '';
                const variantNames = this.variants.map(v => v.name).filter(Boolean);

                const response = await fetch(initData.aiDescriptionUrl || '/ai/seller/generate-description', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': initData.csrfToken || '',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        name: this.productName || '',
                        category: selectedCatName,
                        category_id: firstCatId,
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
                        this.scheduleDraftSave();
                    }
                }
            } catch (e) {
                console.error('AI Description error:', e);
            } finally {
                this.isAiLoading = false;
            }
        },

        submitAsDraft() {
            clearProductDraft();
            document.getElementById('formActionInput').value = 'draft';
            document.getElementById('productForm').submit();
        }
    };
}

function dataURLtoFile(dataurl, filename) {
    try {
        var arr = dataurl.split(','), mime = arr[0].match(/:(.*?);/)[1],
            bstr = atob(arr[1]), n = bstr.length, u8arr = new Uint8Array(n);
        while(n--){
            u8arr[n] = bstr.charCodeAt(n);
        }
        return new File([u8arr], filename, {type:mime});
    } catch(e) {
        return null;
    }
}

function clearProductDraft() {
    try {
        const sellerId = '{{ Auth::id() }}';
        localStorage.removeItem('lumbarong_seller_product_draft_' + sellerId);
    } catch(e) {}
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
        document.getElementById('sizing-section')?.classList.remove('border-red-500', 'ring-2', 'ring-red-400');
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
    if (total > 0) {
        document.getElementById('stock-card')?.classList.remove('border-red-500', 'ring-2', 'ring-red-400');
        document.getElementById('sizing-section')?.classList.remove('border-red-500', 'ring-2', 'ring-red-400');
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
        errors.push('Shipping Fee is required (enter 0 for free shipping).');
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
    const catIds = Array.from(document.querySelectorAll('input[name="category_ids[]"]')).map(i => i.value).filter(Boolean);
    if (!categoryVal && catIds.length === 0) {
        errors.push('Please select at least one Product Category.');
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
    const hasGcashNumber = Boolean(window._currentPaymentState ? window._currentPaymentState.hasGcashNumber : initData.hasGcashNumber);
    const hasGcashQr = Boolean(window._currentPaymentState ? window._currentPaymentState.hasGcashQr : initData.hasGcashQr);
    const hasMayaNumber = Boolean(window._currentPaymentState ? window._currentPaymentState.hasMayaNumber : initData.hasMayaNumber);
    const hasMayaQr = Boolean(window._currentPaymentState ? window._currentPaymentState.hasMayaQr : initData.hasMayaQr);

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

{{-- ================================================================ --}}
{{-- LEAVE PAGE CONFIRMATION MODAL                                     --}}
{{-- ================================================================ --}}
<div id="leave-page-modal" style="display:none;position:fixed;inset:0;z-index:9999;">
    {{-- Backdrop --}}
    <div id="leave-modal-backdrop" style="position:absolute;inset:0;background:rgba(15,10,5,0.6);backdrop-filter:blur(4px);" onclick="closeLeaveModal()"></div>
    {{-- Modal Card --}}
    <div style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);width:calc(100% - 40px);max-width:440px;background:#FFFCF7;border:1px solid #E8DECB;border-radius:24px;padding:32px 28px;box-shadow:0 20px 60px rgba(0,0,0,0.15);">
        {{-- Icon --}}
        <div style="width:50px;height:50px;border-radius:50%;background:#FEF2F2;border:1px solid #FECACA;display:flex;align-items:center;justify-content:center;margin-bottom:20px;color:#DC2626;">
            <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
        </div>
        <h3 style="font-family:ui-serif,Georgia,serif;font-size:20px;font-weight:700;color:#1E1915;margin:0 0 10px 0;">Discard product & leave?</h3>
        <p style="font-size:13.5px;color:#78716C;line-height:1.6;margin:0 0 26px 0;">
            You have unsaved product information. If you leave this page, <strong>all entered details, descriptions, prices, images, and variants will be completely erased</strong>.
        </p>
        <div style="display:flex;flex-direction:column;gap:10px;">
            <button onclick="closeLeaveModal()" style="width:100%;padding:13px;border-radius:14px;background:#1E1915;color:#FFFCF7;font-size:13.5px;font-weight:700;border:none;cursor:pointer;transition:all 0.2s;" onmouseover="this.style.background='#C49520'" onmouseout="this.style.background='#1E1915'">
                Stay and Continue Editing
            </button>
            <button onclick="confirmLeave()" style="width:100%;padding:13px;border-radius:14px;background:#FEF2F2;color:#DC2626;font-size:13.5px;font-weight:700;border:1px solid #FECACA;cursor:pointer;transition:all 0.2s;" onmouseover="this.style.background='#DC2626';this.style.color='#FFFFFF';this.style.borderColor='#DC2626';" onmouseout="this.style.background='#FEF2F2';this.style.color='#DC2626';this.style.borderColor='#FECACA';">
                Discard & Leave Page
            </button>
        </div>
    </div>
</div>

<script>
// ================================================================
// LEAVE CONFIRMATION & NAVIGATION GUARD
// ================================================================
let _pendingLeaveUrl = null;
let _leaveAllowed = false;

function clearProductDraft() {
    try {
        const sellerId = getProductInitData().sellerId || 'guest';
        localStorage.removeItem('lumbarong_seller_product_draft_' + sellerId);
    } catch (e) {}
}

function hasUnsavedData() {
    try {
        const sellerId = getProductInitData().sellerId || 'guest';
        const raw = localStorage.getItem('lumbarong_seller_product_draft_' + sellerId);
        if (!raw) return false;
        const draft = JSON.parse(raw);
        return Boolean(
            (draft.productName && draft.productName.trim()) ||
            (draft.selectedCategories && draft.selectedCategories.length) ||
            (draft.price && parseFloat(draft.price) > 0) ||
            (draft.variants && draft.variants[0] && draft.variants[0].imagePreview)
        );
    } catch(e) {
        return false;
    }
}

function showLeaveModal(href) {
    _pendingLeaveUrl = href;
    const modal = document.getElementById('leave-page-modal');
    if (modal) {
        modal.style.display = 'block';
        document.body.style.overflow = 'hidden';
    }
}

function closeLeaveModal() {
    const modal = document.getElementById('leave-page-modal');
    if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = '';
    }
    _pendingLeaveUrl = null;
}

function confirmLeave() {
    _leaveAllowed = true;
    clearProductDraft();
    const modal = document.getElementById('leave-page-modal');
    if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = '';
    }
    if (_pendingLeaveUrl) {
        window.location.href = _pendingLeaveUrl;
    } else {
        history.back();
    }
}

document.addEventListener('DOMContentLoaded', () => {
    // Intercept back / leave clicks
    document.addEventListener('click', (e) => {
        const anchor = e.target.closest('a[href]');
        if (!anchor) return;
        const href = anchor.getAttribute('href');
        if (!href || href.startsWith('#') || href.startsWith('javascript:')) return;
        
        const currentPath = window.location.pathname;
        let destUrl;
        try {
            destUrl = new URL(href, window.location.origin);
        } catch (err) {
            return;
        }

        if (destUrl.pathname === currentPath && destUrl.search === window.location.search) return;

        if (!hasUnsavedData() || _leaveAllowed) {
            return;
        }

        e.preventDefault();
        showLeaveModal(href);
    });

    // Clean up draft on successful form submit
    const form = document.getElementById('productForm');
    if (form) {
        form.addEventListener('submit', () => {
            _leaveAllowed = true;
            clearProductDraft();
        });
    }
});
</script>
@endsection
