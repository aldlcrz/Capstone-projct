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
                    <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                        <h1 style="font-family:ui-serif,Georgia,Cambria,serif;font-size:24px;font-weight:700;color:#1E1915;letter-spacing:-0.01em;line-height:1.2;margin:0;">
                            New Heritage Piece
                        </h1>
                        <button type="button" 
                                id="tour-create-guide-btn" 
                                @click="$dispatch('start-spotlight-tour', { tourId: 'seller-product-create-step' + (step || 1) })"
                                style="display:inline-flex;align-items:center;gap:6px;padding:4px 12px;border-radius:9999px;font-size:12px;font-weight:700;background:#FAF5E6;border:1px solid #D4AF37;color:#8C6D1F;cursor:pointer;transition:all 0.2s;"
                                onmouseover="this.style.background='#D4AF37'; this.style.color='#FFFFFF';"
                                onmouseout="this.style.background='#FAF5E6'; this.style.color='#8C6D1F';">
                            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <span>Creation Guide</span>
                        </button>
                    </div>
                    <p style="font-size:13px;color:#78716C;margin-top:3px;margin-bottom:0;">
                        List a new handcrafted Lumban creation for discerning buyers
                    </p>
                </div>
            </div>

            {{-- Floating Stepper Card (Exact from screenshot) --}}
            <div id="tour-create-stepper-card" style="background:#FFFFFF;border:1px solid #ECE3D2;border-radius:18px;padding:9px 18px;display:flex;align-items:center;gap:12px;box-shadow:0 2px 8px rgba(0,0,0,0.03);">
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
                <template x-if="variants[0] && variants[0].imagePreview && !variants[0].hasActualFile">
                    <div style="font-size:12px;font-weight:700;color:#D97706;margin-top:4px;display:flex;align-items:center;gap:5px;">
                        <span>⚠️</span>
                        <span>Photo preview restored from draft. Please re-select the image file before publishing.</span>
                    </div>
                </template>
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
    <form action="{{ route('seller.products.store') }}" method="POST" id="productForm" enctype="multipart/form-data" onsubmit="return handleProductFormSubmit(event, false)" class="space-y-6">
        @csrf
        <input type="hidden" name="action" id="formActionInput" value="publish">

        {{-- ========================================================================= --}}
        {{-- STEP 1: MEDIA & CORE INFO (Exact Screenshot Layout & Aesthetics)           --}}
        {{-- ========================================================================= --}}
        <div x-show="step === 1" style="background-color:#FFFFFF !important;border:1px solid #ECE3D2 !important;border-radius:28px !important;box-shadow:0 8px 30px rgba(0,0,0,0.03) !important;color:#1E1915 !important;" class="p-5 sm:p-8 space-y-6">
            
            {{-- 1. What are you listing today? --}}
            <div class="space-y-4" id="tour-create-media-variants">
                <div>
                    <h2 style="font-family:ui-serif,Georgia,Cambria,serif;font-size:18px;font-weight:700;color:#1E1915;margin:0;line-height:1.2;">
                        1. What are you listing today? <span style="color:#DC2626;">*</span>
                    </h2>
                    <p style="font-size:12px;color:#78716C;margin-top:4px;margin-bottom:0;">
                        Enter your master product title and configure your product variations with photos.
                    </p>
                </div>

                {{-- Dedicated Master Product Name Field --}}
                <div class="space-y-1.5 pt-1">
                    <div style="display:flex;align-items:center;justify-content:space-between;">
                        <label for="productNameInput" style="font-size:12px;font-weight:800;text-transform:uppercase;letter-spacing:0.06em;color:#1E1915;">
                            Product Name <span style="color:#DC2626;">*</span>
                        </label>
                        <span style="font-size:11px;color:#A8A096;" x-text="'(' + ((productName || '').length) + '/100)'"></span>
                    </div>
                    <div class="relative flex items-center">
                        <input type="text"
                               name="name"
                               id="productNameInput"
                               x-model="productName"
                               @input="calculateFillRate(); scheduleDraftSave();"
                               placeholder="e.g. Hand-Woven Piña Barong Tagalog with Calado Embroidery"
                               maxlength="100"
                               required
                               style="width:100%;padding:12px 16px;background-color:#FAF8F5;border:1.5px solid #E2D9C8;border-radius:14px;font-size:14px;font-weight:600;color:#1E1915;outline:none;transition:all 0.2s;"
                               onfocus="this.style.borderColor='#C49520';this.style.backgroundColor='#FFFFFF';"
                               onblur="this.style.borderColor='#E2D9C8';this.style.backgroundColor='#FAF8F5';"
                               class="pr-10">
                        <button type="button" 
                                x-show="productName && productName.length > 0"
                                @click="productName = ''; calculateFillRate(); scheduleDraftSave();"
                                style="position:absolute;right:12px;color:#A8A096;background:none;border:none;cursor:pointer;">
                            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>
                    </div>
                    <p style="font-size:11px;color:#78716C;margin:2px 0 0 0;">
                        This overarching title identifies your garment in search, shop catalogue, and order receipts.
                    </p>
                </div>

                {{-- Product Variations & Images Section --}}
                <div class="space-y-4 pt-2">
                    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;">
                        <div>
                            <label style="font-size:12px;font-weight:800;text-transform:uppercase;letter-spacing:0.06em;color:#1E1915;">
                                Product Variations & Images <span style="color:#DC2626;">*</span>
                            </label>
                            <p style="font-size:11.5px;color:#78716C;margin:2px 0 0 0;">
                                Upload product images for each variation (min 1, max 3 photos per variation).
                            </p>
                        </div>
                        <span style="font-size:11px;font-weight:700;background:#FDF8EE;border:1px solid #EEDBBA;color:#7A5505;padding:3px 12px;border-radius:20px;" 
                              x-text="variants.length + ' Variation' + (variants.length > 1 ? 's' : '')"></span>
                    </div>

                    {{-- Hidden inputs to store real files for Laravel form submission --}}
                    <input type="file" id="gallery_files_input" name="images[]" multiple class="hidden">

                    {{-- Variation Cards Loop --}}
                    <div class="space-y-3.5">
                        <template x-for="(variant, index) in variants" :key="variant.id">
                            <div style="background-color:#FFFFFF !important;border:1px solid #ECE3D2 !important;border-radius:18px !important;padding:16px !important;box-shadow:0 2px 8px rgba(0,0,0,0.02) !important;" 
                                 :id="'variant_card_' + index"
                                 class="space-y-3.5 transition-all">
                                {{-- Card Header --}}
                                <div style="display:flex;align-items:center;justify-content:space-between;padding-bottom:10px;border-bottom:1px solid #F2ECE1;">
                                    <div style="display:flex;align-items:center;gap:8px;">
                                        <span style="width:22px;height:22px;border-radius:50%;background-color:#9E6B15;color:#FFFFFF;display:inline-flex;align-items:center;justify-content:center;font-size:11px;font-weight:800;" x-text="index + 1"></span>
                                        <span style="font-family:ui-serif,Georgia,serif;font-size:14.5px;font-weight:700;color:#1E1915;" x-text="index === 0 ? 'Variant 1 (Default Style)' : ('Variant ' + (index + 1))"></span>
                                        <span style="background-color:#FAF8F5;border:1px solid #E2D9C8;color:#78716C;font-size:9px;font-weight:700;border-radius:20px;padding:2px 8px;text-transform:uppercase;letter-spacing:0.04em;" x-text="index === 0 ? 'Original' : 'Style Option'"></span>
                                    </div>
                                    <template x-if="index > 0">
                                        <button type="button" 
                                                @click="removeVariantRow(index)" 
                                                style="font-size:12px;font-weight:600;color:#DC2626;background:none;border:none;cursor:pointer;display:flex;align-items:center;gap:4px;">
                                            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            <span>Remove</span>
                                        </button>
                                    </template>
                                </div>

                                {{-- Hidden file inputs for submission and file picker --}}
                                <input type="hidden" name="variant_indexes[]" :value="index">
                                <input type="file" :id="'variant_files_' + index" :name="'variant_images_' + index + '[]'" multiple class="hidden">
                                <input type="file" :id="'variant_file_' + index" :name="'variant_image_' + index" class="hidden">
                                <input type="file" 
                                       :id="'variant_picker_' + index" 
                                       accept="image/jpeg,image/png,image/webp,image/jpg,image/heic,image/heif,.heic,.heif" 
                                       multiple 
                                       class="hidden" 
                                       @change="handleVariantImagesUpload($event, index)">

                                {{-- Variant Style Name Input --}}
                                <div>
                                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:4px;">
                                        <label style="font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:0.04em;color:#1E1915;display:block;">
                                            <span x-text="index === 0 ? 'Variant 1 Style / Color Name' : ('Variant ' + (index + 1) + ' Style / Color Name')"></span>
                                            <template x-if="index > 0">
                                                <span style="color:#DC2626;">*</span>
                                            </template>
                                            <template x-if="index === 0">
                                                <span style="font-size:9.5px;font-weight:500;color:#78716C;text-transform:none;">(Optional, e.g. Classic Ivory)</span>
                                            </template>
                                        </label>
                                        <span style="font-size:10px;color:#A8A096;font-weight:400;" x-text="'(' + ((variant.name || '').length) + '/100)'"></span>
                                    </div>
                                    <div class="relative flex items-center">
                                        <input type="text" 
                                               :name="'variant_names[' + index + ']'" 
                                               :id="'variant_name_' + index"
                                               x-model="variant.name" 
                                               @input="calculateFillRate(); scheduleDraftSave();"
                                               :placeholder="index === 0 ? 'e.g. Classic Ivory (Optional, defaults to product name)' : 'e.g. Emerald Green, Midnight Blue, Short Sleeve...'" 
                                               maxlength="100"
                                               :required="index > 0"
                                               style="width:100%;padding:10px 14px;background-color:#FAF8F5;border:1px solid #E2D9C8;border-radius:12px;font-size:13px;font-weight:600;color:#1E1915;outline:none;transition:all 0.2s;"
                                               onfocus="this.style.borderColor='#C49520';this.style.backgroundColor='#FFFFFF';"
                                               onblur="this.style.borderColor='#E2D9C8';this.style.backgroundColor='#FAF8F5';"
                                               class="pr-8">
                                        <button type="button" 
                                                x-show="variant.name && variant.name.length > 0"
                                                @click="variant.name = ''; calculateFillRate(); scheduleDraftSave();"
                                                style="position:absolute;right:10px;color:#A8A096;background:none;border:none;cursor:pointer;">
                                            <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                        </button>
                                    </div>
                                </div>

                                    {{-- Product Images Uploader for this Variation (Min 1, Max 3) --}}
                                    <div class="space-y-2">
                                        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:6px;">
                                            <label style="font-size:10.5px;font-weight:800;text-transform:uppercase;letter-spacing:0.04em;color:#1E1915;">
                                                Upload Product Image <span style="color:#DC2626;">*</span>
                                                <span style="font-size:9.5px;font-weight:600;color:#78716C;text-transform:none;letter-spacing:normal;">(Min 1, Max 3)</span>
                                            </label>
                                            <span style="font-size:10.5px;font-weight:700;" 
                                                  :style="variant.images && variant.images.length >= 1 ? 'color:#059669;' : 'color:#DC2626;'" 
                                                  x-text="(variant.images ? variant.images.length : 0) + ' / 3 uploaded' + (variant.images && variant.images.length >= 1 ? ' ✓' : ' (At least 1 required)')"></span>
                                        </div>

                                        {{-- Image Cards Row (Flex layout: previews + upload button) --}}
                                        <div class="flex items-center gap-3 overflow-x-auto pb-1 pt-1" :id="'variant_img_row_' + index">
                                            {{-- Uploaded Images Previews --}}
                                            <template x-for="(img, imgIdx) in (variant.images || [])" :key="img.uid">
                                                <div style="width:110px;height:140px;border-radius:16px;border:1px solid #ECE3D2;background-color:#FAF8F5;position:relative;flex-shrink:0;overflow:hidden;box-shadow:0 1px 4px rgba(0,0,0,0.03);">
                                                    <img :src="img.preview" style="width:100%;height:100%;object-fit:cover;">
                                                    {{-- Photo Badge --}}
                                                    <span style="position:absolute;top:5px;left:5px;background:rgba(30,25,21,0.78);color:#FFFFFF;font-size:8px;font-weight:800;padding:2px 6px;border-radius:6px;text-transform:uppercase;letter-spacing:0.03em;z-index:10;"
                                                          x-text="(index === 0 && imgIdx === 0) ? 'Primary' : ('Photo ' + (imgIdx + 1))"></span>
                                                    {{-- Re-select Warning if from restored draft without File --}}
                                                    <template x-if="!img.hasActualFile">
                                                        <div style="position:absolute;bottom:0;left:0;right:0;background:rgba(217,119,6,0.92);color:#FFFFFF;font-size:8px;font-weight:800;text-align:center;padding:3px 2px;line-height:1.2;cursor:pointer;z-index:10;" 
                                                             @click="triggerVariantPicker(index)">
                                                            Tap to re-select
                                                        </div>
                                                    </template>
                                                    {{-- Remove Button --}}
                                                    <button type="button" 
                                                            @click="removeVariantImageAt(index, imgIdx)" 
                                                            style="position:absolute;top:5px;right:5px;width:18px;height:18px;background-color:#DC2626;color:#FFFFFF;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:9px;font-weight:900;border:none;cursor:pointer;z-index:15;" 
                                                            title="Remove photo">
                                                        ✕
                                                    </button>
                                                </div>
                                            </template>

                                            {{-- Optimizing Loading Card --}}
                                            <template x-if="variant.isOptimizing">
                                                <div style="width:110px;height:140px;border-radius:16px;border:1.5px dashed #C49520;background:#FAF8F5;display:flex;flex-direction:column;align-items:center;justify-content:center;flex-shrink:0;padding:8px;">
                                                    <svg class="animate-spin h-5 w-5 text-[#C49520]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                                    </svg>
                                                    <span style="font-size:8.5px;font-weight:800;color:#7A5505;margin-top:6px;text-align:center;">Compressing...</span>
                                                </div>
                                            </template>

                                            {{-- Upload Button (Shown while count < 3) --}}
                                            <template x-if="!variant.images || variant.images.length < 3">
                                                <button type="button" 
                                                        @click="triggerVariantPicker(index)" 
                                                        :id="'variant_upload_btn_' + index"
                                                        style="width:110px;height:140px;border-radius:16px;border:1.5px dashed #E2D9C8;background-color:#FAF8F5;display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;cursor:pointer;flex-shrink:0;transition:all 0.2s;padding:10px;outline:none;"
                                                        onmouseover="this.style.borderColor='#C49520';this.style.backgroundColor='#FFFFFF';"
                                                        onmouseout="this.style.borderColor='#E2D9C8';this.style.backgroundColor='#FAF8F5';">
                                                    <div style="color:#C49520;margin-bottom:4px;">
                                                        <svg width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                                                        </svg>
                                                    </div>
                                                    <span style="font-size:11px;font-weight:700;color:#1E1915;line-height:1.2;">Upload Photo</span>
                                                    <span style="font-size:9px;color:#78716C;margin-top:4px;" x-text="'Slot ' + ((variant.images ? variant.images.length : 0) + 1) + ' of 3'"></span>
                                                    <span style="font-size:8px;color:#A8A096;margin-top:2px;">Auto-compressed</span>
                                                </button>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>

                        {{-- Add Another Variant Button --}}
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
            </div>

            {{-- Centered Golden Diamond Divider (Exact from Screenshot) --}}
            <div style="position:relative;margin:28px 0;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <div style="width:100%;border-top:1px solid #EAE1D0;"></div>
                <span style="position:absolute;background-color:#FFFFFF;padding:0 12px;color:#C49520;font-size:11px;">◆</span>
            </div>

            {{-- 2. Who is this for? (Target Tag) & Category Selection --}}
            <div class="space-y-4" id="tour-create-target-category">
                {{-- Who is this for? (Target Tag) --}}
                <div class="space-y-2.5">
                    <div style="display:flex;align-items:center;justify-content:space-between;">
                        <h2 style="font-family:ui-serif,Georgia,Cambria,serif;font-size:18px;font-weight:700;color:#1E1915;margin:0;">
                            2. Who is this for? (Target Tag) <span style="color:#DC2626;">*</span>
                        </h2>
                        <span style="font-size:11px;font-weight:700;border-radius:20px;padding:3px 12px;background-color:#E8F5E9;border:1px solid #A5D6A7;color:#2E7D32;transition:all 0.2s;"
                              x-show="targetGroup"
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
                            <button type="button" 
                                    @click="onTargetGroupChange('{{ $group }}')"
                                    class="target-pill" 
                                    :class="targetGroup === '{{ $group }}' ? 'target-pill-selected' : ''">
                                <span>{{ $group }}</span>
                                <span class="target-checkmark" x-show="targetGroup === '{{ $group }}'">✓</span>
                            </button>
                        @endforeach
                        <input type="hidden" name="target_group" id="targetGroupInput" :value="targetGroup">
                    </div>
                </div>

                {{-- Product Category for Selected Tag — only shown after a tag is picked --}}
                <div class="space-y-2.5 pt-2" x-show="targetGroup" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0">
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
            <div id="tour-create-step1-footer" style="margin-top:28px;padding-top:20px;border-top:1px solid #F2ECE1;display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;">
                <div>
                    <div style="font-size:13px;font-weight:700;color:#1E1915;display:flex;align-items:center;gap:6px;">
                        <span>Next: Complete Product Details</span>
                        <span style="font-weight:700;">→</span>
                    </div>
                    <div style="display:flex;align-items:center;gap:8px;margin-top:6px;flex-wrap:wrap;">
                        {{-- Photo Status --}}
                        <span class="rounded-full"
                              style="font-size:10.5px;font-weight:700;border-radius:9999px !important;padding:4px 12px !important;display:inline-flex;align-items:center;gap:4px;"
                              :style="variants[0] && ((variants[0].images && variants[0].images.length > 0) || variants[0].imagePreview) ? 'border-radius:9999px !important;padding:4px 12px !important;font-size:10.5px;font-weight:700;background:#E8F5E9;color:#2E7D32;border:1px solid #A5D6A7;display:inline-flex;align-items:center;' : 'border-radius:9999px !important;padding:4px 12px !important;font-size:10.5px;font-weight:700;background:#FEF2F2;color:#DC2626;border:1px solid #FECACA;display:inline-flex;align-items:center;'">
                            <span x-text="variants[0] && ((variants[0].images && variants[0].images.length > 0) || variants[0].imagePreview) ? '✓ Photo added' : '✕ Photo missing'"></span>
                        </span>

                        {{-- Name Status --}}
                        <span class="rounded-full"
                              style="font-size:10.5px;font-weight:700;border-radius:9999px !important;padding:4px 12px !important;display:inline-flex;align-items:center;gap:4px;"
                              :style="((variants[0] && variants[0].name && variants[0].name.trim().length >= 3) || (productName && productName.trim().length >= 3)) ? 'border-radius:9999px !important;padding:4px 12px !important;font-size:10.5px;font-weight:700;background:#E8F5E9;color:#2E7D32;border:1px solid #A5D6A7;display:inline-flex;align-items:center;' : 'border-radius:9999px !important;padding:4px 12px !important;font-size:10.5px;font-weight:700;background:#FEF2F2;color:#DC2626;border:1px solid #FECACA;display:inline-flex;align-items:center;'">
                            <span x-text="((variants[0] && variants[0].name && variants[0].name.trim().length >= 3) || (productName && productName.trim().length >= 3)) ? '✓ Name set' : '✕ Name missing'"></span>
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
            <div id="tour-create-step2-completeness" style="background-color:#FFFFFF !important;border:1px solid #ECE3D2 !important;border-radius:24px !important;padding:20px 24px !important;box-shadow:0 4px 20px rgba(0,0,0,0.03) !important;" class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
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
            <div id="tour-create-step2-sizing" style="background-color:#FFFFFF !important;border:1px solid #ECE3D2 !important;border-radius:24px !important;padding:24px !important;box-shadow:0 4px 20px rgba(0,0,0,0.03) !important;" class="space-y-4">
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

                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-2.5 pt-1">
                    @foreach(['S', 'M', 'L', 'XL', 'XXL'] as $size)
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
            <div id="tour-create-step2-pricing" style="background-color:#FFFFFF !important;border:1px solid #ECE3D2 !important;border-radius:24px !important;padding:24px !important;box-shadow:0 4px 20px rgba(0,0,0,0.03) !important;" class="space-y-4">
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

                {{-- Lumbarong Seller Sales Discount Panel --}}
                <div style="padding:14px 16px;border-radius:18px;background-color:#FDF8EE;border:1px solid #EEDBBA;">
                    <input type="hidden" name="is_on_sale" id="isOnSaleInput" value="0">
                    <input type="hidden" name="sale_duration" id="saleDurationInput" value="1_week">

                    <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;">
                        <div style="display:flex;align-items:center;gap:8px;min-width:0;flex:1;">
                            <span style="width:8px;height:8px;border-radius:50%;background-color:#C49520;flex-shrink:0;display:inline-block;"></span>
                            <div style="display:flex;align-items:baseline;gap:4px 6px;flex-wrap:wrap;min-width:0;">
                                <span style="font-size:12px;font-weight:700;color:#7A5505;text-transform:uppercase;letter-spacing:0.04em;line-height:1.2;">Lumbarong Specials &amp; Promo</span>
                                <span style="font-size:10px;color:#78716C;font-weight:600;text-transform:uppercase;line-height:1.2;">(Optional)</span>
                            </div>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer shrink-0" style="margin:0;line-height:1;">
                            <input type="checkbox" id="discountToggle" class="sr-only peer" onchange="toggleDiscount(this)">
                            <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-0.5 after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-[#1C160E]"></div>
                        </label>
                    </div>

                    <div id="discountFields" class="hidden space-y-3 pt-3.5 mt-3.5 border-t border-[#EEDBBA]">
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

                        {{-- Sale Duration Pill Selector --}}
                        <div class="pt-2 border-t border-[#EEDBBA]/60">
                            <label style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#78716C;display:block;margin-bottom:6px;">Sale Duration</label>
                            <div class="flex flex-wrap items-center gap-2" id="durationPillsContainer">
                                <button type="button" onclick="selectSaleDuration('1_day')" data-duration="1_day" class="sale-duration-pill px-3.5 py-1.5 rounded-full text-xs font-bold border transition-all cursor-pointer">1 day</button>
                                <button type="button" onclick="selectSaleDuration('1_week')" data-duration="1_week" class="sale-duration-pill px-3.5 py-1.5 rounded-full text-xs font-bold border transition-all cursor-pointer">1 week</button>
                                <button type="button" onclick="selectSaleDuration('1_month')" data-duration="1_month" class="sale-duration-pill px-3.5 py-1.5 rounded-full text-xs font-bold border transition-all cursor-pointer">1 month</button>
                                <button type="button" onclick="selectSaleDuration('3_months')" data-duration="3_months" class="sale-duration-pill px-3.5 py-1.5 rounded-full text-xs font-bold border transition-all cursor-pointer">3 months</button>
                            </div>
                            <p id="saleDurationNotice" class="text-[11px] text-[#7A5505] font-semibold mt-1.5 flex items-center gap-1"></p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Step 2 Navigation Actions --}}
            <div id="tour-create-step2-footer" class="pt-5 flex flex-col-reverse sm:flex-row items-stretch sm:items-center justify-between gap-3 sm:gap-4">
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
            <div id="tour-create-step3-payment" style="background-color:#FFFFFF !important;border:1px solid #ECE3D2 !important;border-radius:24px !important;padding:24px !important;box-shadow:0 4px 20px rgba(0,0,0,0.03) !important;" class="space-y-4">
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
            <div id="tour-create-step3-story" style="background-color:#FFFFFF !important;border:1px solid #ECE3D2 !important;border-radius:24px !important;padding:24px !important;box-shadow:0 4px 20px rgba(0,0,0,0.03) !important;" class="space-y-4">
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
            <div id="tour-create-step3-footer" class="pt-5 flex flex-col-reverse sm:flex-row items-stretch sm:items-center justify-between gap-3 sm:gap-4 border-t border-[#F2ECE1]">
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
        'targetGroup'      => (string) old('target_group', ''),
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
/**
 * Optimized Image Processing Pipeline (Phases 3 & 4)
 * - Automatic HEIC/HEIF conversion for iPhone photos using heic2any
 * - Canvas downscale to maximum 1600px dimension
 * - High-efficiency JPEG/WebP compression (~85% quality)
 * - Produces an optimized File object suitable for standard multipart/form-data upload
 */
window._pendingImageJobs = window._pendingImageJobs || new Set();

function trackImageJob(promise) {
    if (window._pendingImageJobs && promise) {
        window._pendingImageJobs.add(promise);
        promise.finally(() => {
            if (window._pendingImageJobs) {
                window._pendingImageJobs.delete(promise);
            }
        });
    }
    return promise;
}

async function processClientImage(file, maxDimension = 1600, quality = 0.85) {
    if (!file) return null;

    let processingFile = file;

    // Phase 4: Handle HEIC / HEIF conversion client-side
    const isHeic = file.type === 'image/heic' || 
                   file.type === 'image/heif' || 
                   /\.(heic|heif)$/i.test(file.name || '');

    if (isHeic) {
        try {
            if (typeof window.heic2any === 'undefined') {
                await new Promise((resolve, reject) => {
                    const script = document.createElement('script');
                    script.src = 'https://cdn.jsdelivr.net/npm/heic2any@0.0.4/dist/heic2any.min.js';
                    script.onload = resolve;
                    script.onerror = () => reject(new Error('Failed to load HEIC converter library.'));
                    document.head.appendChild(script);
                });
            }
            const convertedBlob = await window.heic2any({
                blob: file,
                toType: 'image/jpeg',
                quality: quality
            });
            const blobResult = Array.isArray(convertedBlob) ? convertedBlob[0] : convertedBlob;
            const newName = (file.name || 'image').replace(/\.(heic|heif)$/i, '.jpg');
            processingFile = new File([blobResult], newName, { type: 'image/jpeg' });
        } catch (heicErr) {
            console.warn('HEIC client-side conversion skipped or failed:', heicErr);
        }
    }

    // Phase 3: Resize & Compress via HTML5 Canvas
    const compressionPromise = new Promise((resolve) => {
        let isResolved = false;
        const finalize = (result) => {
            if (isResolved) return;
            isResolved = true;
            clearTimeout(safetyTimeout);
            resolve(result);
        };

        // Safety timeout: Never hang for more than 4 seconds
        const safetyTimeout = setTimeout(() => {
            console.warn('Image processing reached safety timeout, using direct file fallback.');
            const fallbackPreview = URL.createObjectURL(processingFile);
            finalize({ file: processingFile, preview: fallbackPreview });
        }, 4000);

        if (processingFile.type === 'image/gif' || processingFile.type === 'image/svg+xml') {
            const reader = new FileReader();
            reader.onload = (e) => finalize({ file: processingFile, preview: e.target.result });
            reader.onerror = () => finalize({ file: processingFile, preview: URL.createObjectURL(processingFile) });
            reader.readAsDataURL(processingFile);
            return;
        }

        const img = new Image();
        const objectUrl = URL.createObjectURL(processingFile);

        img.onload = () => {
            URL.revokeObjectURL(objectUrl);
            let width = img.naturalWidth || img.width;
            let height = img.naturalHeight || img.height;

            if (width > maxDimension || height > maxDimension) {
                if (width > height) {
                    height = Math.round((height * maxDimension) / width);
                    width = maxDimension;
                } else {
                    width = Math.round((width * maxDimension) / height);
                    height = maxDimension;
                }
            }

            const canvas = document.createElement('canvas');
            canvas.width = width;
            canvas.height = height;
            const ctx = canvas.getContext('2d');
            ctx.drawImage(img, 0, 0, width, height);

            const targetMime = (processingFile.type === 'image/png' && processingFile.size < 600 * 1024) 
                ? 'image/png' 
                : 'image/jpeg';

            canvas.toBlob((blob) => {
                if (!blob) {
                    finalize({ file: processingFile, preview: canvas.toDataURL(targetMime, quality) });
                    return;
                }

                const ext = targetMime === 'image/png' ? '.png' : '.jpg';
                const baseName = (processingFile.name || 'product').replace(/\.[^/.]+$/, '');
                const optimizedFile = new File([blob], baseName + ext, {
                    type: targetMime,
                    lastModified: Date.now()
                });

                const previewUrl = canvas.toDataURL(targetMime, quality);
                finalize({ file: optimizedFile, preview: previewUrl });
            }, targetMime, quality);
        };

        img.onerror = () => {
            URL.revokeObjectURL(objectUrl);
            const reader = new FileReader();
            reader.onload = (e) => finalize({ file: processingFile, preview: e.target.result });
            reader.onerror = () => finalize({ file: processingFile, preview: URL.createObjectURL(processingFile) });
            reader.readAsDataURL(processingFile);
        };

        // CRITICAL: Trigger image load!
        img.src = objectUrl;
    });
    return trackImageJob(compressionPromise);
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
        'Traditional',
        'Wedding Barong'
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
        targetGroup: initData.targetGroup || '',
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

        // Media State: Variations & Images (Min 1, Max 3 images per variation)
        variants: [
            { id: 0, name: initData.name || '', images: [], file: null, imagePreview: null, hasActualFile: false, isOptimizing: false }
        ],
        _variantImageUidCounter: 1,
        galleryImages: [], // optional gallery fallback
        _galleryUidCounter: 1, // Unique ID counter for stable x-for keys
        isOptimizingGallery: false,

        get imageCount() {
            let count = 0;
            this.variants.forEach(v => {
                if (v && Array.isArray(v.images)) count += v.images.length;
            });
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
                const DRAFT_KEY = 'lumbarong_seller_product_draft_v2_' + sellerId;

                const mainName = (this.variants[0] && this.variants[0].name) ? this.variants[0].name.trim() : (this.productName || '').trim();
                const hasAnyData = Boolean(
                    mainName ||
                    (this.selectedCategories && this.selectedCategories.length) ||
                    (this.price && parseFloat(this.price) > 0) ||
                    (this.variants[0] && ((this.variants[0].images && this.variants[0].images.length > 0) || this.variants[0].imagePreview)) ||
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
                    productName: mainName,
                    selectedCategories: this.selectedCategories || [],
                    targetGroup: this.targetGroup || '',
                    fabricType: this.fabricType || '100% Piña',
                    price: this.price || '',
                    description: this.description || '',
                    shippingFee: document.getElementById('shippingFeeInput')?.value || '0',
                    shippingDays: document.getElementById('shippingDaysInput')?.value || '5',
                    checkedSizes: checkedSizes,
                    sizeStocks: sizeStocks,
                    isOnSale: document.getElementById('discountToggle')?.checked || false,
                    discountPercentage: document.getElementById('discountPercentage')?.value || '',
                    variants: this.variants.map((v, idx) => ({
                        id: v.id,
                        name: (idx === 0 && !v.name && mainName) ? mainName : (v.name || ''),
                        images: Array.isArray(v.images) ? v.images.map(img => ({ preview: img.preview })) : [],
                        imagePreview: v.imagePreview
                    })),
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
                // Clean up any legacy draft that had targetGroup hardcoded to 'Men'
                try {
                    localStorage.removeItem('lumbarong_seller_product_draft_' + sellerId);
                } catch (err) {}

                const DRAFT_KEY = 'lumbarong_seller_product_draft_v2_' + sellerId;
                const raw = localStorage.getItem(DRAFT_KEY);
                if (!raw) return;

                const draft = JSON.parse(raw);
                if (!draft) return;

                const restoredName = draft.productName || (draft.variants && draft.variants[0] && draft.variants[0].name) || '';

                const hasContent = Boolean(
                    restoredName ||
                    (draft.selectedCategories && draft.selectedCategories.length) ||
                    (draft.price && parseFloat(draft.price) > 0) ||
                    (draft.variants && draft.variants[0] && ((draft.variants[0].images && draft.variants[0].images.length > 0) || draft.variants[0].imagePreview)) ||
                    (draft.description && draft.description.trim())
                );

                if (!hasContent) return;

                if (restoredName) this.productName = restoredName;
                if (draft.targetGroup && ['Men', 'Women', 'Kids'].includes(draft.targetGroup)) {
                    this.targetGroup = draft.targetGroup;
                }
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

                // Restore variants & product images (Preview only; explicit re-attachment or auto-recovery)
                if (Array.isArray(draft.variants) && draft.variants.length > 0) {
                    this.variants = draft.variants.map((v, idx) => {
                        let restoredImages = [];
                        if (Array.isArray(v.images) && v.images.length > 0) {
                            restoredImages = v.images.map(img => ({
                                uid: this._variantImageUidCounter++,
                                file: null,
                                preview: img.preview,
                                hasActualFile: false
                            }));
                        } else if (v.imagePreview) {
                            restoredImages = [{
                                uid: this._variantImageUidCounter++,
                                file: null,
                                preview: v.imagePreview,
                                hasActualFile: false
                            }];
                        }
                        return {
                            id: v.id ?? idx,
                            name: (idx === 0 && !v.name && restoredName) ? restoredName : (v.name || ''),
                            images: restoredImages,
                            file: null,
                            imagePreview: restoredImages[0] ? restoredImages[0].preview : (v.imagePreview || null),
                            hasActualFile: false,
                            isOptimizing: false
                        };
                    });
                    if (this.variants[0] && this.variants[0].name) {
                        this.productName = this.variants[0].name;
                    }
                }

                // Restore gallery images (Preview only)
                if (Array.isArray(draft.galleryImages) && draft.galleryImages.length > 0) {
                    this.galleryImages = draft.galleryImages.map(g => {
                        return {
                            uid: this._galleryUidCounter++,
                            file: null,
                            preview: g.preview,
                            hasActualFile: false
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
            clearProductDraft();
            this.hasRestoredDraft = false;
            window.location.href = window.location.pathname;
        },

        triggerVariantPicker(index) {
            const picker = document.getElementById('variant_picker_' + index);
            if (picker) {
                picker.value = '';
                picker.click();
            }
        },

        async handleVariantImagesUpload(event, index) {
            const files = Array.from(event.target.files || []);
            event.target.value = '';
            if (!files.length) return;

            const variant = this.variants[index];
            if (!variant) return;

            if (!Array.isArray(variant.images)) {
                variant.images = [];
            }

            const remainingSlots = 3 - variant.images.length;
            if (remainingSlots <= 0) {
                triggerAppModal('Photo Limit Reached', 'Each variation can have a maximum of 3 photos.', 'warning');
                return;
            }

            const filesToProcess = files.slice(0, remainingSlots);
            if (files.length > remainingSlots) {
                triggerAppModal('Maximum 3 Photos', `You can only upload up to 3 photos per variation. The first ${remainingSlots} photo(s) will be added.`, 'info');
            }

            variant.isOptimizing = true;
            try {
                for (const file of filesToProcess) {
                    if (file.size > 25 * 1024 * 1024) {
                        triggerAppModal('File Too Large', `Photo "${file.name}" exceeds 25MB limit. Please choose a smaller photo.`, 'warning');
                        continue;
                    }
                    if (!file.type.startsWith('image/') && !/\.(jpe?g|png|webp|heic|heif)$/i.test(file.name || '')) {
                        triggerAppModal('Invalid File', `"${file.name}" is not a supported image file.`, 'warning');
                        continue;
                    }

                    try {
                        const result = await processClientImage(file, 1600, 0.85);
                        if (result && result.file) {
                            variant.images.push({
                                uid: this._variantImageUidCounter++,
                                file: result.file,
                                preview: result.preview,
                                hasActualFile: true
                            });
                        }
                    } catch (err) {
                        console.error('Variant photo processing error:', err);
                    }
                }

                if (variant.images.length > 0) {
                    variant.file = variant.images[0].file;
                    variant.imagePreview = variant.images[0].preview;
                    variant.hasActualFile = variant.images[0].hasActualFile;
                }

                this.syncVariantInputs(index);

                const card = document.getElementById('variant_card_' + index);
                if (card) card.classList.remove('border-red-500', 'ring-2', 'ring-red-400');

                this.calculateFillRate();
                this.scheduleDraftSave();
            } finally {
                variant.isOptimizing = false;
            }
        },

        removeVariantImageAt(variantIndex, imageIndex) {
            const variant = this.variants[variantIndex];
            if (!variant || !Array.isArray(variant.images)) return;

            if (imageIndex >= 0 && imageIndex < variant.images.length) {
                variant.images.splice(imageIndex, 1);

                if (variant.images.length > 0) {
                    variant.file = variant.images[0].file;
                    variant.imagePreview = variant.images[0].preview;
                    variant.hasActualFile = variant.images[0].hasActualFile;
                } else {
                    variant.file = null;
                    variant.imagePreview = null;
                    variant.hasActualFile = false;
                }

                this.syncVariantInputs(variantIndex);
                this.calculateFillRate();
                this.scheduleDraftSave();
            }
        },

        syncVariantInputs(variantIndex) {
            try {
                const variant = this.variants[variantIndex];
                if (!variant) return;

                if (typeof DataTransfer !== 'undefined') {
                    // Multi-file input
                    const dtMulti = new DataTransfer();
                    if (Array.isArray(variant.images)) {
                        variant.images.forEach(img => {
                            if (img.file) dtMulti.items.add(img.file);
                        });
                    }
                    const multiInput = document.getElementById('variant_files_' + variantIndex);
                    if (multiInput) multiInput.files = dtMulti.files;

                    // Single-file legacy input
                    const dtSingle = new DataTransfer();
                    if (variant.file) {
                        dtSingle.items.add(variant.file);
                    } else if (Array.isArray(variant.images) && variant.images[0] && variant.images[0].file) {
                        dtSingle.items.add(variant.images[0].file);
                    }
                    const singleInput = document.getElementById('variant_file_' + variantIndex);
                    if (singleInput) singleInput.files = dtSingle.files;

                    // Sync primary images to gallery_files_input for full backward compatibility
                    const dtGallery = new DataTransfer();
                    this.variants.forEach(v => {
                        if (Array.isArray(v.images)) {
                            v.images.forEach(img => {
                                if (img.file) dtGallery.items.add(img.file);
                            });
                        }
                    });
                    const galleryInput = document.getElementById('gallery_files_input');
                    if (galleryInput) galleryInput.files = dtGallery.files;
                }
            } catch (err) {
                console.warn('Could not sync variant inputs:', err);
            }
        },

        syncGalleryFileInput() {
            try {
                if (typeof DataTransfer === 'undefined') return;
                const dt = new DataTransfer();
                this.galleryImages.forEach(g => {
                    if (g.file) dt.items.add(g.file);
                });
                const input = document.getElementById('gallery_files_input');
                if (input) input.files = dt.files;
            } catch(e) {
                console.warn('Could not sync gallery input:', e);
            }
        },

        triggerGalleryUpload() {
            if (this.galleryImages.length >= 10) {
                if (typeof triggerAppModal === 'function') {
                    triggerAppModal('Photo Limit Reached', 'You can upload a maximum of 10 gallery photos.', 'warning');
                } else {
                    alert('You can upload a maximum of 10 gallery photos.');
                }
                return;
            }
            const picker = document.getElementById('gallery_picker_input');
            if (picker) {
                picker.value = '';
                picker.click();
            }
        },

        handleGalleryDrop(event) {
            const dt = event.dataTransfer;
            if (dt && dt.files && dt.files.length) {
                this.processGalleryFileList(Array.from(dt.files));
            }
        },

        async handleGalleryFilesUpload(event) {
            const files = Array.from(event.target.files || []);
            event.target.value = ''; // Always clear picker so selecting the same file triggers change
            if (!files.length) return;
            await this.processGalleryFileList(files);
        },

        async processGalleryFileList(files) {
            if (!files || !files.length) return;

            if (this.galleryImages.length >= 10) {
                if (typeof triggerAppModal === 'function') {
                    triggerAppModal('Limit Reached', 'You can upload up to 10 additional product images.', 'warning');
                }
                return;
            }

            this.isOptimizingGallery = true;
            try {
                for (const file of files) {
                    if (this.galleryImages.length >= 10) break;
                    if (file.size > 25 * 1024 * 1024) {
                        if (typeof triggerAppModal === 'function') {
                            triggerAppModal('File Too Large', `Photo "${file.name}" exceeds the 25MB limit. Please choose a smaller photo.`, 'warning');
                        }
                        continue;
                    }
                    if (!file.type.startsWith('image/') && !/\.(jpe?g|png|webp|heic|heif)$/i.test(file.name || '')) {
                        if (typeof triggerAppModal === 'function') {
                            triggerAppModal('Invalid File', `"${file.name}" is not a supported image file.`, 'warning');
                        }
                        continue;
                    }

                    try {
                        const result = await processClientImage(file, 1600, 0.85);
                        if (result && result.file) {
                            this.galleryImages.push({
                                uid: this._galleryUidCounter++,
                                file: result.file,
                                preview: result.preview,
                                hasActualFile: true
                            });
                        }
                    } catch (err) {
                        console.warn('Gallery image processing warning:', err);
                    }
                }
                this.syncGalleryFileInput();
                this.calculateFillRate();
                this.scheduleDraftSave();
            } finally {
                this.isOptimizingGallery = false;
            }
        },

        removeGalleryImage(index) {
            if (index >= 0 && index < this.galleryImages.length) {
                this.galleryImages.splice(index, 1);
                this.syncGalleryFileInput();
                this.calculateFillRate();
                this.scheduleDraftSave();
            }
        },

        addVariantRow() {
            const nextId = this.variants.length;
            this.variants.push({ id: nextId, name: '', images: [], file: null, imagePreview: null, hasActualFile: false, isOptimizing: false });
            this.calculateFillRate();
            this.scheduleDraftSave();
        },

        removeVariantRow(index) {
            if (index === 0) return;
            this.variants.splice(index, 1);
            this.calculateFillRate();
            this.scheduleDraftSave();
        },

        async handleVariantFile(event, index) {
            await this.handleVariantImagesUpload(event, index);
        },

        removeVariantImage(index) {
            this.removeVariantImageAt(index, 0);
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
            const target = String(this.targetGroup).trim().toLowerCase();
            return this.categoriesList.filter(c => {
                if (!c) return false;
                let tg = c.target_group;
                if (Array.isArray(tg)) {
                    return tg.some(t => String(t).trim().toLowerCase() === target);
                }
                if (typeof tg === 'string') {
                    return tg.trim().toLowerCase() === target;
                }
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
            const target = String(group || '').trim().toLowerCase();
            if (this.selectedCategories.length > 0) {
                this.selectedCategories = this.selectedCategories.filter(catId => {
                    const cat = Array.isArray(this.categoriesList) ? this.categoriesList.find(c => String(c.id) === String(catId)) : null;
                    if (!cat) return false;
                    let tg = cat.target_group;
                    if (Array.isArray(tg)) {
                        return tg.some(t => String(t).trim().toLowerCase() === target);
                    }
                    if (typeof tg === 'string') {
                        return tg.trim().toLowerCase() === target;
                    }
                    return false;
                });
            }

            const catContainer = document.getElementById('category-cards-container');
            if (catContainer) catContainer.classList.remove('border-red-500');
            this.calculateFillRate();
            this.scheduleDraftSave();
        },

        get isStep1Complete() {
            const masterName = (this.productName || '').trim() || ((this.variants[0] && this.variants[0].name) ? this.variants[0].name.trim() : '');
            const hasName = Boolean(masterName.length >= 3);
            const hasCategory = this.selectedCategories.length > 0;
            const hasTarget = Boolean(this.targetGroup && ['Men', 'Women', 'Kids'].includes(this.targetGroup));
            const allVariantsHaveImages = this.variants.length > 0 && this.variants.every(v => {
                const count = (v.images && Array.isArray(v.images)) ? v.images.length : (v.imagePreview ? 1 : 0);
                return count >= 1 && count <= 3;
            });
            const additionalVariantsHaveNames = this.variants.length <= 1 || this.variants.slice(1).every(v => Boolean(v.name && v.name.trim().length > 0));
            return hasName && hasCategory && hasTarget && allVariantsHaveImages && additionalVariantsHaveNames;
        },

        goToStep2() {
            // Remove previous error highlights
            document.querySelectorAll('.border-red-500, .ring-2.ring-red-400').forEach(el => {
                el.classList.remove('border-red-500', 'ring-2', 'ring-red-400');
            });

            // 1. Validate master Product Name
            const masterName = (this.productName || '').trim() || ((this.variants[0] && this.variants[0].name) ? this.variants[0].name.trim() : '');
            if (!masterName || masterName.length < 3) {
                const nameInput = document.getElementById('productNameInput') || document.getElementById('variant_name_0');
                if (nameInput) {
                    nameInput.classList.add('border-red-500', 'ring-2', 'ring-red-400');
                    nameInput.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    nameInput.focus();
                }
                triggerAppModal('Product Name Required', 'Please provide an overarching product title with at least 3 characters (e.g. Hand-Woven Piña Barong Tagalog).', 'warning');
                return;
            }

            // 2. Validate that every variation has at least 1 image and at most 3 images, and non-empty name for variant 2+
            for (let i = 0; i < this.variants.length; i++) {
                const v = this.variants[i];
                const count = (v.images && Array.isArray(v.images)) ? v.images.length : (v.imagePreview ? 1 : 0);
                const vName = i === 0 ? 'Variant 1 (Default Style)' : ('Variant ' + (i + 1));

                if (i > 0 && (!v.name || v.name.trim().length === 0)) {
                    const card = document.getElementById('variant_card_' + i);
                    const vInput = document.getElementById('variant_name_' + i);
                    if (vInput) {
                        vInput.classList.add('border-red-500', 'ring-2', 'ring-red-400');
                        vInput.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        vInput.focus();
                    } else if (card) {
                        card.classList.add('border-red-500', 'ring-2', 'ring-red-400');
                        card.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                    triggerAppModal('Variant Name Required', `Please enter a style/color name for ${vName} (e.g. Emerald Green, Midnight Blue).`, 'warning');
                    return;
                }

                if (count < 1) {
                    const card = document.getElementById('variant_card_' + i);
                    if (card) {
                        card.classList.add('border-red-500', 'ring-2', 'ring-red-400');
                        card.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                    triggerAppModal('Product Image Required', `Please upload at least 1 product image for ${vName} (min 1, max 3 photos).`, 'warning');
                    return;
                }
                if (count > 3) {
                    const card = document.getElementById('variant_card_' + i);
                    if (card) {
                        card.classList.add('border-red-500', 'ring-2', 'ring-red-400');
                        card.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                    triggerAppModal('Maximum 3 Photos', `${vName} has ${count} photos. Maximum is 3 photos per variation.`, 'warning');
                    return;
                }
            }

            if (!this.targetGroup || !['Men', 'Women', 'Kids'].includes(this.targetGroup)) {
                const tgContainer = document.getElementById('target-group-container');
                if (tgContainer) {
                    tgContainer.classList.add('border-red-500', 'ring-2', 'ring-red-400');
                    tgContainer.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
                triggerAppModal('Target Audience Required', 'Please select whether this garment is tailored for Men, Women, or Kids.', 'warning');
                return;
            }

            if (!this.selectedCategories || this.selectedCategories.length === 0) {
                const catContainer = document.getElementById('category-cards-container');
                if (catContainer) {
                    catContainer.classList.add('border-red-500', 'ring-2', 'ring-red-400');
                    catContainer.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
                triggerAppModal('Category Required', 'Please select at least one product category from the catalogue.', 'warning');
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
            document.querySelectorAll('#price-card, #priceInput, #shipping-fee-card, #shippingFeeInput, #shipping-days-card, #shippingDaysInput, #tour-create-step2-sizing, #sizing-section, #stock-card').forEach(el => {
                el.classList.remove('border-red-500', 'ring-2', 'ring-red-400');
            });

            const checkedSizes = document.querySelectorAll('.size-checkbox:checked');
            if (checkedSizes.length === 0) {
                const sizeSec = document.getElementById('tour-create-step2-sizing') || document.getElementById('sizing-section');
                if (sizeSec) {
                    sizeSec.classList.add('border-red-500', 'ring-2', 'ring-red-400');
                    sizeSec.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
                triggerAppModal('Size Selection Required', 'Please check at least one Heritage Size (e.g. S, M, L, XL, XXL) and assign available stock to continue.', 'warning');
                return;
            }

            const totalStock = parseInt(document.getElementById('total_stock')?.value || 0);
            if (totalStock <= 0) {
                const sizeSec = document.getElementById('tour-create-step2-sizing') || document.getElementById('sizing-section');
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
                triggerAppModal('Inventory Stock Required', 'Total stock must be greater than 0. Please enter available inventory quantities for your selected sizes.', 'warning');
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
                triggerAppModal('Valid Price Required', 'Please enter a valid item price between ₱1.00 and ₱10,000.00.', 'warning');
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
                triggerAppModal('Shipping Fee Required', 'Please enter a standard delivery shipping fee between ₱0.00 (Free shipping) and ₱500.00.', 'warning');
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
                triggerAppModal('Shipping Days Required', 'Please specify estimated shipping days between 1 and 30 business days.', 'warning');
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
            const mainName = (this.variants[0] && this.variants[0].name) ? this.variants[0].name.trim() : (this.productName || '').trim();
            if (mainName.length >= 3) score += 20;
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
                const mainName = (this.variants[0] && this.variants[0].name) ? this.variants[0].name.trim() : (this.productName || '').trim();

                const response = await fetch(initData.aiDescriptionUrl || '/ai/seller/generate-description', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': initData.csrfToken || '',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        name: mainName,
                        category: selectedCatName,
                        category_id: firstCatId,
                        target_group: this.targetGroup || '',
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

        async submitAsDraft() {
            if (window._pendingImageJobs && window._pendingImageJobs.size > 0) {
                try {
                    await Promise.all(Array.from(window._pendingImageJobs));
                } catch (e) {}
            }
            if (typeof this.syncGalleryFileInput === 'function') {
                this.syncGalleryFileInput();
            }
            _isSubmittingForm = true;
            _leaveAllowed = true;
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
        localStorage.removeItem('lumbarong_seller_product_draft_v2_' + sellerId);
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
        document.getElementById('tour-create-step2-sizing')?.classList.remove('border-red-500', 'ring-2', 'ring-red-400');
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
    let checkedCount = 0;

    checkboxes.forEach((cb, idx) => {
        if (cb.checked) {
            checkedCount++;
            if (inputs[idx]) {
                const val = parseInt(inputs[idx].value) || 0;
                total += val;
            }
        }
    });

    const totalStockEl = document.getElementById('total_stock');
    if (totalStockEl) totalStockEl.value = total;
    if (total > 0) {
        document.getElementById('stock-card')?.classList.remove('border-red-500', 'ring-2', 'ring-red-400');
        document.getElementById('tour-create-step2-sizing')?.classList.remove('border-red-500', 'ring-2', 'ring-red-400');
        document.getElementById('sizing-section')?.classList.remove('border-red-500', 'ring-2', 'ring-red-400');
    }

    const alpineEl = document.querySelector('[x-data="addProductManager()"]');
    if (alpineEl && window.Alpine) {
        try {
            const alpineData = Alpine.$data(alpineEl);
            if (alpineData) {
                alpineData.hasValidSizing = (checkedCount > 0 && total > 0);
            }
        } catch(e) {}
    }
}

function toggleDiscount(checkbox) {
    const fields = document.getElementById('discountFields');
    const hiddenInput = document.getElementById('isOnSaleInput');
    if (checkbox && checkbox.checked) {
        fields.classList.remove('hidden');
        hiddenInput.value = '1';
        updateDiscountPreview();
        const duration = document.getElementById('saleDurationInput')?.value || '1_week';
        selectSaleDuration(duration);
    } else {
        fields.classList.add('hidden');
        hiddenInput.value = '0';
        document.getElementById('discountPreview').classList.add('hidden');
        const pct = document.getElementById('discountPercentage');
        if (pct) pct.value = '';
    }
}

function selectSaleDuration(val) {
    const hiddenInput = document.getElementById('saleDurationInput');
    if (hiddenInput) hiddenInput.value = val;
    
    document.querySelectorAll('.sale-duration-pill').forEach(pill => {
        if (pill.getAttribute('data-duration') === val) {
            pill.style.backgroundColor = '#1E1915';
            pill.style.color = '#FFFFFF';
            pill.style.borderColor = '#1E1915';
        } else {
            pill.style.backgroundColor = '#FFFFFF';
            pill.style.color = '#78716C';
            pill.style.borderColor = '#E2D9C8';
        }
    });

    const notice = document.getElementById('saleDurationNotice');
    if (notice) {
        const now = new Date();
        let target = new Date();
        if (val === '1_day') target.setDate(now.getDate() + 1);
        else if (val === '1_week') target.setDate(now.getDate() + 7);
        else if (val === '1_month') target.setMonth(now.getMonth() + 1);
        else if (val === '3_months') target.setMonth(now.getMonth() + 3);

        const options = { month: 'short', day: 'numeric', year: 'numeric' };
        notice.innerHTML = `<svg class="w-3.5 h-3.5 inline text-[#C49520]" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/></svg> Sale active until: <strong>${target.toLocaleDateString('en-US', options)}</strong>`;
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

let _isSubmittingForm = false;

async function handleProductFormSubmit(e, isEdit = false) {
    if (_isSubmittingForm) {
        return true;
    }

    if (e && typeof e.preventDefault === 'function') {
        e.preventDefault();
    }

    // 1. If any image optimization jobs are still pending, await all of them!
    if (window._pendingImageJobs && window._pendingImageJobs.size > 0) {
        const submitBtn = document.querySelector('button[type="submit"]');
        let origBtnHtml = '';
        if (submitBtn) {
            submitBtn.disabled = true;
            origBtnHtml = submitBtn.innerHTML;
            submitBtn.innerHTML = '<svg class="animate-spin h-4 w-4 text-white inline-block mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg> Optimizing photos...';
        }

        try {
            await Promise.all(Array.from(window._pendingImageJobs));
        } catch (jobErr) {
            console.warn('Image optimization job encountered an error:', jobErr);
        }

        if (submitBtn) {
            submitBtn.disabled = false;
            if (origBtnHtml) submitBtn.innerHTML = origBtnHtml;
        }
    }

    // 2. Pre-sync any Alpine variant/gallery files to DOM inputs
    try {
        const alpineEl = document.querySelector('[x-data="addProductManager()"]');
        const alpineData = alpineEl && window.Alpine ? Alpine.$data(alpineEl) : null;
        if (alpineData) {
            if (Array.isArray(alpineData.variants)) {
                alpineData.variants.forEach((v, idx) => {
                    if (Array.isArray(v.images)) {
                        v.images.forEach((img, imgIdx) => {
                            if (!img.file && img.preview && typeof img.preview === 'string' && img.preview.startsWith('data:image') && typeof dataURLtoFile === 'function') {
                                img.file = dataURLtoFile(img.preview, `variant_${idx}_img_${imgIdx}.jpg`);
                                img.hasActualFile = true;
                            }
                        });
                        if (v.images.length > 0 && !v.file && v.images[0].file) {
                            v.file = v.images[0].file;
                            v.hasActualFile = true;
                        }
                    } else if (v.imagePreview && !v.file && typeof v.imagePreview === 'string' && v.imagePreview.startsWith('data:image') && typeof dataURLtoFile === 'function') {
                        v.file = dataURLtoFile(v.imagePreview, `variant_${idx}.jpg`);
                        v.hasActualFile = true;
                    }

                    if (typeof alpineData.syncVariantInputs === 'function') {
                        alpineData.syncVariantInputs(idx);
                    }
                });
            }
            if (typeof alpineData.syncGalleryFileInput === 'function') {
                alpineData.syncGalleryFileInput();
            }

            const nameInput = document.getElementById('productNameInput') || document.querySelector('input[name="name"]');
            const masterName = (nameInput && nameInput.value && nameInput.value.trim()) 
                ? nameInput.value.trim() 
                : ((alpineData.productName && alpineData.productName.trim()) 
                    ? alpineData.productName.trim() 
                    : ((alpineData.variants && alpineData.variants[0] && alpineData.variants[0].name) ? alpineData.variants[0].name.trim() : ''));
            if (nameInput && masterName) {
                nameInput.value = masterName;
            }

            const variantFileInput = document.getElementById('variant_files_0') || document.getElementById('variant_file_0');
            console.log('[ImagePipeline:SubmitDiagnostics]', {
                variantCount: alpineData.variants.length,
                variant0FilesCount: variantFileInput ? variantFileInput.files.length : 0,
                pendingJobsCount: window._pendingImageJobs ? window._pendingImageJobs.size : 0,
            });
        }
    } catch (syncErr) {
        console.warn('Pre-submit file sync warning:', syncErr);
    }

    // 3. Run validation
    const isValid = validateProductForm(e, isEdit);
    if (!isValid) {
        return false;
    }

    // 4. Form is valid -> submit!
    _isSubmittingForm = true;
    _leaveAllowed = true;
    clearProductDraft();
    const form = document.getElementById('productForm');
    if (form) {
        form.submit();
    }
    return true;
}

function validateProductForm(e, isEdit = false) {
    const action = document.getElementById('formActionInput')?.value;
    const nameInput = document.getElementById('productNameInput') || document.querySelector('input[name="name"]');
    const variant0Input = document.getElementById('variant_name_0');
    let resolvedName = '';
    if (nameInput && nameInput.value && nameInput.value.trim()) {
        resolvedName = nameInput.value.trim();
    } else if (variant0Input && variant0Input.value && variant0Input.value.trim()) {
        resolvedName = variant0Input.value.trim();
    }
    if (nameInput && resolvedName) {
        nameInput.value = resolvedName;
    }

    if (action === 'draft') {
        if (!resolvedName) {
            if (e && typeof e.preventDefault === 'function') e.preventDefault();
            triggerAppModal('Draft Name Required', 'Please enter at least a product name in Variant 1 to save a draft.', 'warning');
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
    if (!resolvedName) {
        errors.push('Product Name is required (enter in Variant 1).');
        if (variant0Input) variant0Input.classList.add('border-red-500');
        if (nameHidden) nameHidden.classList.add('border-red-500');
    } else if (resolvedName.length < 3) {
        errors.push('Product Name must be at least 3 characters.');
        if (variant0Input) variant0Input.classList.add('border-red-500');
        if (nameHidden) nameHidden.classList.add('border-red-500');
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
    const sizingSection = document.getElementById('tour-create-step2-sizing') || document.getElementById('sizing-section');
    
    if (checkedSizes.length === 0) {
        errors.push('Please select at least one Heritage Size (e.g. S, M, L, XL, XXL).');
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

    // 5. Product Imagery (Each variation requires min 1, max 3 photos)
    if (!isEdit) {
        let alpineData = null;
        try {
            const alpineEl = document.querySelector('[x-data="addProductManager()"]');
            alpineData = alpineEl && window.Alpine ? Alpine.$data(alpineEl) : null;
            if (alpineData && Array.isArray(alpineData.variants)) {
                alpineData.variants.forEach((v, idx) => {
                    if (typeof alpineData.syncVariantInputs === 'function') {
                        alpineData.syncVariantInputs(idx);
                    }
                });
            }
        } catch (syncErr) {
            console.warn('Pre-submit variant sync warning:', syncErr);
        }

        if (alpineData && Array.isArray(alpineData.variants)) {
            alpineData.variants.forEach((v, idx) => {
                const vName = idx === 0 ? 'Variant 1 (Main Style)' : (`Variant ${idx + 1}` + (v.name ? ` - ${v.name}` : ''));
                const images = Array.isArray(v.images) ? v.images : [];
                const filesInput = document.getElementById('variant_files_' + idx);
                const fileInput = document.getElementById('variant_file_' + idx);
                const hasInputFiles = (filesInput && filesInput.files && filesInput.files.length > 0) || (fileInput && fileInput.files && fileInput.files.length > 0);
                const hasActualFiles = images.some(img => img.hasActualFile && img.file);

                if (images.length === 0 && !hasInputFiles) {
                    errors.push(`Please upload at least 1 product image for ${vName} (min 1, max 3 photos).`);
                    const card = document.getElementById('variant_card_' + idx);
                    if (card) card.classList.add('border-red-500');
                } else if (images.length > 0 && !hasActualFiles && !hasInputFiles) {
                    errors.push(`The photos for ${vName} were restored from a draft. Please re-select the image files before publishing.`);
                    const card = document.getElementById('variant_card_' + idx);
                    if (card) card.classList.add('border-amber-500', 'ring-2', 'ring-amber-400');
                } else if (images.length > 3) {
                    errors.push(`${vName} has ${images.length} photos. Maximum is 3 photos per variation.`);
                    const card = document.getElementById('variant_card_' + idx);
                    if (card) card.classList.add('border-red-500');
                }
            });
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

    _leaveAllowed = true;
    clearProductDraft();
    return true;
}
</script>

{{-- ================================================================ --}}
{{-- LEAVE PAGE CONFIRMATION MODAL                                     --}}
{{-- ================================================================ --}}
<style>
    @keyframes leaveModalPop {
        0% { opacity: 0; transform: scale(0.94) translateY(10px); }
        100% { opacity: 1; transform: scale(1) translateY(0); }
    }
    .leave-modal-card {
        animation: leaveModalPop 0.22s cubic-bezier(0.16, 1, 0.3, 1) forwards;
    }
</style>
<div id="leave-page-modal" style="display:none;position:fixed;inset:0;z-index:99999;align-items:center;justify-content:center;padding:16px;">
    {{-- Backdrop --}}
    <div id="leave-modal-backdrop" style="position:fixed;inset:0;background:rgba(20,15,10,0.65);backdrop-filter:blur(6px);transition:opacity 0.2s;" onclick="closeLeaveModal()"></div>
    
    {{-- Modal Card --}}
    <div class="leave-modal-card" style="position:relative;width:100%;max-width:440px;background:#FFFCF7;border:1px solid #E8DECB;border-radius:26px;padding:30px 26px;box-shadow:0 24px 60px rgba(0,0,0,0.22);z-index:10;">
        {{-- Warning Icon Badge --}}
        <div style="width:52px;height:52px;border-radius:18px;background:#FEF2F2;border:1.5px solid #FECACA;display:flex;align-items:center;justify-content:center;margin-bottom:18px;color:#DC2626;box-shadow:0 4px 12px rgba(220,38,38,0.12);">
            <svg width="26" height="26" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
        </div>

        <h3 style="font-family:ui-serif,Georgia,serif;font-size:21px;font-weight:800;color:#1E1915;margin:0 0 10px 0;line-height:1.25;">
            Leave this page?
        </h3>

        {{-- Quote Banner with exact warning --}}
        <div style="background:#FFF5F5;border:1px solid #FED7D7;border-radius:14px;padding:12px 14px;margin-bottom:14px;display:flex;align-items:flex-start;gap:10px;">
            <span style="font-size:16px;line-height:1;margin-top:1px;">⚠️</span>
            <p style="font-size:13px;font-weight:700;color:#9B2C2C;margin:0;line-height:1.45;">
                When you leave this page, the data you entered will be gone.
            </p>
        </div>

        <p style="font-size:13px;color:#78716C;line-height:1.6;margin:0 0 24px 0;">
            You have unsaved product information. If you leave or refresh now, all entered details, images, prices, variants, and descriptions will be lost.
        </p>

        <div style="display:flex;flex-direction:column;gap:10px;">
            <button type="button" onclick="closeLeaveModal()" style="width:100%;padding:13px;border-radius:14px;background:#1E1915;color:#FFFCF7;font-size:13.5px;font-weight:700;border:none;cursor:pointer;transition:all 0.2s;box-shadow:0 3px 10px rgba(0,0,0,0.1);" onmouseover="this.style.background='#C49520'" onmouseout="this.style.background='#1E1915'">
                Stay and Continue Editing
            </button>
            <button type="button" onclick="confirmLeave()" style="width:100%;padding:13px;border-radius:14px;background:#FEF2F2;color:#DC2626;font-size:13.5px;font-weight:700;border:1px solid #FECACA;cursor:pointer;transition:all 0.2s;" onmouseover="this.style.background='#DC2626';this.style.color='#FFFFFF';this.style.borderColor='#DC2626';" onmouseout="this.style.background='#FEF2F2';this.style.color='#DC2626';this.style.borderColor='#FECACA';">
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
window._isProductFormDirty = false;

function clearProductDraft() {
    try {
        const sellerId = '{{ Auth::id() }}' || (typeof getProductInitData === 'function' ? getProductInitData().sellerId : null) || 'guest';
        localStorage.removeItem('lumbarong_seller_product_draft_' + sellerId);
        localStorage.removeItem('lumbarong_seller_product_draft_v2_' + sellerId);
    } catch (e) {}
}

function hasUnsavedData() {
    if (_leaveAllowed) return false;

    // 1. Direct check: dirty flag
    if (window._isProductFormDirty) return true;

    // 2. Direct check: Live form inputs on the page
    try {
        const form = document.getElementById('productForm');
        if (form) {
            const nameInput = document.getElementById('variant_name_0') || document.getElementById('productNameInput') || form.querySelector('input[name="name"]');
            if (nameInput && nameInput.value && nameInput.value.trim().length > 0) return true;

            const priceInput = document.getElementById('priceInput') || form.querySelector('input[name="price"]');
            if (priceInput && priceInput.value && parseFloat(priceInput.value) > 0) return true;

            const descInput = document.getElementById('artisanDescription') || form.querySelector('textarea[name="description"]');
            if (descInput && descInput.value && descInput.value.trim().length > 0) return true;

            // Categories
            const checkedCats = form.querySelectorAll('input[name="category_ids[]"]');
            if (checkedCats && checkedCats.length > 0) return true;

            // Checked sizes
            const checkedSizes = form.querySelectorAll('.size-checkbox:checked');
            if (checkedSizes && checkedSizes.length > 0) return true;

            // Variant 1 cover photo
            const v1File = document.getElementById('variant_file_0');
            if (v1File && v1File.files && v1File.files.length > 0) return true;

            // Gallery images
            const galleryFiles = document.getElementById('gallery_files_input');
            if (galleryFiles && galleryFiles.files && galleryFiles.files.length > 0) return true;

            // Image previews
            const v0Preview = document.getElementById('variant_preview_img_0');
            if (v0Preview && v0Preview.getAttribute('src') && v0Preview.getAttribute('src').startsWith('data:image')) return true;
        }
    } catch (e) {}

    // 3. Direct check: localStorage saved draft
    try {
        const sellerId = '{{ Auth::id() }}' || (typeof getProductInitData === 'function' ? getProductInitData().sellerId : null) || 'guest';
        const raw = localStorage.getItem('lumbarong_seller_product_draft_' + sellerId);
        if (raw) {
            const draft = JSON.parse(raw);
            if (
                (draft.productName && draft.productName.trim()) ||
                (draft.selectedCategories && draft.selectedCategories.length) ||
                (draft.price && parseFloat(draft.price) > 0) ||
                (draft.description && draft.description.trim()) ||
                (draft.variants && draft.variants[0] && draft.variants[0].imagePreview)
            ) {
                return true;
            }
        }
    } catch(e) {}

    return false;
}

function showLeaveModal(href) {
    _pendingLeaveUrl = href || null;
    const modal = document.getElementById('leave-page-modal');
    if (modal) {
        modal.style.display = 'flex';
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

// Mark form as dirty on any input, change, or paste event
function initDirtyTracking() {
    const form = document.getElementById('productForm');
    if (form) {
        ['input', 'change', 'keyup', 'paste'].forEach(evt => {
            form.addEventListener(evt, () => {
                window._isProductFormDirty = true;
            }, { passive: true });
        });
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initDirtyTracking);
} else {
    initDirtyTracking();
}

// Intercept browser reload / tab close
window.addEventListener('beforeunload', (e) => {
    if (!_leaveAllowed && hasUnsavedData()) {
        e.preventDefault();
        e.returnValue = '';
        return '';
    }
});

// Intercept link clicks across the entire page (sidebar, top nav, back link, logo, etc.)
document.addEventListener('click', (e) => {
    if (_leaveAllowed) return;

    const anchor = e.target.closest('a[href]');
    if (!anchor) return;

    // Ignore anchors inside modal itself, target=_blank, download, hash, javascript
    if (anchor.closest('#leave-page-modal')) return;
    if (anchor.target === '_blank' || anchor.hasAttribute('download')) return;

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

    if (hasUnsavedData()) {
        e.preventDefault();
        e.stopPropagation();
        showLeaveModal(href);
    }
}, true); // Capture phase to intercept reliably

// Also ensure form submission sets _leaveAllowed
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('productForm');
    if (form) {
        form.addEventListener('submit', () => {
            _leaveAllowed = true;
            clearProductDraft();
        });
    }
});
</script>

{{-- Contextual Step-Aware Spotlight Tours for Product Creation Wizard --}}
@php
    $createStep1TourSteps = [
        [
            'selector' => '#tour-create-guide-btn',
            'title' => '✨ Step 1: Media & Core Info Guide',
            'text' => 'Welcome to Step 1! Here you enter your product details and upload 1 to 3 photos for each variation.'
        ],
        [
            'selector' => '#tour-create-stepper-card',
            'title' => '🧭 3-Step Wizard Navigation',
            'text' => 'The listing process is divided into 3 intuitive phases: 1) Media & Core Info, 2) Pricing & Sizing Matrix, and 3) Artisan Story & Direct Payouts.'
        ],
        [
            'selector' => '#tour-create-media-variants',
            'title' => '📸 Variations & Product Images',
            'text' => 'Upload 1 to 3 product images for each variation. Click "+ Add Another Variant" to add additional style or color options with their own photos and names.'
        ],
        [
            'selector' => '#tour-create-target-category',
            'title' => '👥 Target Audience & Categories',
            'text' => 'Choose your target demographic (Men, Women, or Kids). Garment categories (such as Barong Tagalog, Filipiniana, or Bolero) will automatically adjust based on your selection.'
        ],
        [
            'selector' => '#tour-create-step1-footer',
            'title' => '🚀 Step 1 Validation & Next',
            'text' => 'Live validation tags confirm required inputs. Click "Save & Continue" to advance to Step 2 for Heritage Sizing & Pricing!'
        ]
    ];

    $createStep2TourSteps = [
        [
            'selector' => '#tour-create-guide-btn',
            'title' => '✨ Step 2: Pricing & Sizing Guide',
            'text' => 'Welcome to Step 2! Configure available stock quantities for standard sizes (S, M, L, XL, XXL) and set fair artisan prices.'
        ],
        [
            'selector' => '#tour-create-step2-completeness',
            'title' => '📊 Listing Completeness Bar',
            'text' => 'Tracks your listing quality score in real time as you complete pricing, stock, and shipping matrices.'
        ],
        [
            'selector' => '#tour-create-step2-sizing',
            'title' => '📐 Heritage Sizing & Inventory Matrix',
            'text' => 'Check the sizes you offer (S to XXL) and specify current available inventory stock for each size. Stock is auto-calculated.'
        ],
        [
            'selector' => '#tour-create-step2-pricing',
            'title' => '💵 Pricing, Shipping & Discounts',
            'text' => 'Set your base item price, shipping fees, delivery lead time, and optionally enable a promotional discount percentage with live price preview.'
        ],
        [
            'selector' => '#tour-create-step2-footer',
            'title' => '🚀 Proceed to Step 3',
            'text' => 'Click "Continue to Step 3" to configure your artisan storytelling and direct payout options, or return to Step 1 anytime.'
        ]
    ];

    $createStep3TourSteps = [
        [
            'selector' => '#tour-create-guide-btn',
            'title' => '✨ Step 3: Story, Payouts & Publishing',
            'text' => 'Welcome to the final step! Tell the handcrafted story behind this Lumban piece and configure your direct GCash/Maya customer payouts.'
        ],
        [
            'selector' => '#tour-create-step3-payment',
            'title' => '📱 Direct Payout Methods',
            'text' => 'Enable GCash or Maya to receive direct customer payments. Click "Settings ↗" to configure your mobile number and payment QR code.'
        ],
        [
            'selector' => '#tour-create-step3-story',
            'title' => '✍️ Artisan Story & AI Auto-Write',
            'text' => 'Highlight fabric provenance, embroidery techniques (Calado, Callado, Burda), and care guidelines. Click "✦ AI Auto-Write" to generate an authentic heritage story instantly!'
        ],
        [
            'selector' => '#tour-create-step3-footer',
            'title' => '🚀 Draft or Submit for Quality Review',
            'text' => 'Click "Save as Draft" to keep working later, or click "Publish Heritage Piece" to submit for Lumban artisan registry verification!'
        ]
    ];
@endphp

{{-- Step 1 Tour --}}
<x-spotlight-tour tour-id="seller-product-create-step1" :auto-start="false" :steps="$createStep1TourSteps" />

{{-- Step 2 Tour --}}
<x-spotlight-tour tour-id="seller-product-create-step2" :auto-start="false" :steps="$createStep2TourSteps" />

{{-- Step 3 Tour --}}
<x-spotlight-tour tour-id="seller-product-create-step3" :auto-start="false" :steps="$createStep3TourSteps" />

{{-- Fallback Tour --}}
<x-spotlight-tour tour-id="seller-product-create" :auto-start="false" :steps="$createStep1TourSteps" />
@endsection
