<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>Artisan Shop Setup | LumBarong</title>
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Playfair+Display:wght@700;800;900&display=swap" rel="stylesheet">
    
    <style>
        body { font-family: 'Inter', sans-serif; background: #F7F3EE; }
        .font-serif { font-family: 'Playfair Display', serif; }
        [x-cloak] { display: none !important; }
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
    </style>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>
</head>
<body class="min-h-screen flex items-center justify-center p-3 sm:p-6 relative overflow-x-hidden" id="seller-onboarding-body">
    <!-- Subtle warm background gradients -->
    <div class="absolute top-0 right-0 w-80 sm:w-140 h-80 sm:h-140 rounded-full -translate-y-1/2 translate-x-1/3 blur-3xl opacity-[0.07] pointer-events-none bg-[#C0422A]"></div>
    <div class="absolute bottom-0 left-0 w-72 sm:w-110 h-72 sm:h-110 rounded-full translate-y-1/2 -translate-x-1/3 blur-3xl opacity-[0.12] pointer-events-none bg-[#D4B896]"></div>

    <div class="w-full max-w-2xl bg-white rounded-3xl sm:rounded-[2.5rem] border border-[#E5DDD5] p-5 sm:p-8 shadow-[0_20px_60px_rgba(60,40,20,0.08)] relative z-10 my-4 sm:my-8 transition-all duration-300"
         x-data="sellerOnboarding()"
         x-cloak>
        
        {{-- Top Bar with Step Badges & Skip Action --}}
        <div class="flex items-center justify-between pb-4 border-b border-[#EFE8DC] mb-6">
            <div class="flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full bg-[#C0422A] animate-pulse"></span>
                <span class="text-[11px] font-black uppercase tracking-wider text-gray-500">Artisan Onboarding</span>
            </div>
            
            <form action="{{ route('seller.onboarding.skip') }}" method="POST" class="inline">
                @csrf
                <button type="submit" class="inline-flex items-center gap-1.5 text-xs font-bold text-gray-500 hover:text-[#C0422A] transition-colors py-1 px-2.5 rounded-lg hover:bg-[#FAF7F2]">
                    <span>Skip for now</span>
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                </button>
            </form>
        </div>

        {{-- Header & Branding --}}
        <div class="text-center mb-6">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-[#C0422A]/10 text-[#C0422A] text-[11px] font-black uppercase tracking-wider mb-2 border border-[#C0422A]/20">
                <span>Welcome, {{ $user->shopName ?: $user->name }}</span>
            </div>
            <h1 class="font-serif text-2xl sm:text-3xl font-black italic tracking-tight text-gray-900 mb-1.5">
                Set Up Your Artisan Store
            </h1>
            <p class="text-xs text-gray-600 font-medium max-w-lg mx-auto leading-relaxed">
                Configure your payment reception, return guidelines, and optionally showcase your first handcrafted creation. You can customize or skip any step.
            </p>
        </div>

        {{-- Interactive 3-Step Indicator --}}
        <div class="grid grid-cols-3 gap-2 mb-6 sm:mb-8">
            {{-- Step 1 Indicator --}}
            <button type="button" @click="currentStep = 1" class="text-left group cursor-pointer">
                <div class="h-1.5 rounded-full mb-2 transition-all duration-300"
                     :class="currentStep >= 1 ? 'bg-[#C0422A]' : 'bg-gray-200'"></div>
                <div class="flex items-center gap-1.5">
                    <span class="text-[10px] font-black w-4 h-4 rounded-full flex items-center justify-center shrink-0 transition-colors"
                          :class="currentStep === 1 ? 'bg-[#C0422A] text-white' : (currentStep > 1 ? 'bg-emerald-600 text-white' : 'bg-gray-200 text-gray-600')">
                        <template x-if="currentStep > 1">✓</template>
                        <template x-if="currentStep <= 1">1</template>
                    </span>
                    <span class="text-[11px] font-bold truncate" :class="currentStep === 1 ? 'text-[#C0422A]' : 'text-gray-600'">GCash Payout</span>
                </div>
            </button>

            {{-- Step 2 Indicator --}}
            <button type="button" @click="currentStep = 2" class="text-left group cursor-pointer">
                <div class="h-1.5 rounded-full mb-2 transition-all duration-300"
                     :class="currentStep >= 2 ? 'bg-[#C0422A]' : 'bg-gray-200'"></div>
                <div class="flex items-center gap-1.5">
                    <span class="text-[10px] font-black w-4 h-4 rounded-full flex items-center justify-center shrink-0 transition-colors"
                          :class="currentStep === 2 ? 'bg-[#C0422A] text-white' : (currentStep > 2 ? 'bg-emerald-600 text-white' : 'bg-gray-200 text-gray-600')">
                        <template x-if="currentStep > 2">✓</template>
                        <template x-if="currentStep <= 2">2</template>
                    </span>
                    <span class="text-[11px] font-bold truncate" :class="currentStep === 2 ? 'text-[#C0422A]' : 'text-gray-600'">Shop Policy</span>
                </div>
            </button>

            {{-- Step 3 Indicator --}}
            <button type="button" @click="currentStep = 3" class="text-left group cursor-pointer">
                <div class="h-1.5 rounded-full mb-2 transition-all duration-300"
                     :class="currentStep >= 3 ? 'bg-[#C0422A]' : 'bg-gray-200'"></div>
                <div class="flex items-center gap-1.5">
                    <span class="text-[10px] font-black w-4 h-4 rounded-full flex items-center justify-center shrink-0 transition-colors"
                          :class="currentStep === 3 ? 'bg-[#C0422A] text-white' : 'bg-gray-200 text-gray-600'">3</span>
                    <span class="text-[11px] font-bold truncate" :class="currentStep === 3 ? 'text-[#C0422A]' : 'text-gray-600'">First Product</span>
                </div>
            </button>
        </div>

        {{-- Validation Errors Alert --}}
        @if ($errors->any())
            <div class="mb-5 p-3.5 rounded-2xl bg-red-50 border border-red-200 text-red-900 text-xs">
                <div class="font-bold mb-1">Please review the following:</div>
                <ul class="list-disc list-inside space-y-0.5 text-red-700">
                    @foreach ($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Main Setup Form --}}
        <form action="{{ route('seller.onboarding.save') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
            @csrf

            {{-- STEP 1: GCASH & PAYOUT SETUP --}}
            <div x-show="currentStep === 1" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" class="space-y-4">
                <div class="bg-[#FAF8F5] border border-[#ECE3D2] rounded-2xl sm:rounded-3xl p-4 sm:p-6 space-y-4">
                    <div class="flex items-center justify-between border-b border-[#ECE3D2] pb-3">
                        <div>
                            <h2 class="text-xs font-black uppercase tracking-wider text-[#1E1915]">1. GCash Payout Setup</h2>
                            <p class="text-[11px] text-gray-500 font-medium">Customer payments for artisan orders will be sent to this GCash account.</p>
                        </div>
                        <span class="text-[10px] font-extrabold text-amber-800 bg-amber-100/80 border border-amber-300/50 px-2 py-0.5 rounded-full uppercase tracking-wider">Optional</span>
                    </div>

                    {{-- GCash Number --}}
                    <div>
                        <label for="gcashNumber" class="block text-[11px] font-bold text-gray-800 uppercase tracking-wider mb-1">
                            Artisan GCash Number
                        </label>
                        <div class="flex items-center rounded-xl bg-white border border-[#D8CEBE] overflow-hidden focus-within:border-[#C0422A] focus-within:ring-2 focus-within:ring-[#C0422A]/15 transition-all shadow-xs h-11">
                            <div class="flex items-center justify-center px-3.5 bg-[#FAF7F2] border-r border-[#D8CEBE] text-xs font-bold text-gray-700 select-none shrink-0 h-full">
                                <span>+63</span>
                            </div>
                            <input type="text" 
                                   id="gcashNumber" 
                                   name="gcashNumber" 
                                   value="{{ old('gcashNumber', $user->gcashNumber) }}" 
                                   placeholder="9171234567" 
                                   class="w-full h-full px-3.5 bg-transparent text-xs font-semibold text-gray-900 outline-none placeholder:text-gray-400">
                        </div>
                        <p class="text-[10.5px] text-gray-500 mt-1">Enter 10 digits without leading zero or formatted with 09.</p>
                    </div>

                    {{-- GCash QR Code Upload --}}
                    <div>
                        <label class="block text-[11px] font-bold text-gray-800 uppercase tracking-wider mb-1">
                            GCash QR Code Image
                        </label>
                        <div class="border-2 border-dashed border-[#D8CEBE] rounded-2xl p-4 bg-white hover:bg-[#FAF8F5] transition-colors text-center cursor-pointer relative">
                            <input type="file" 
                                   name="gcashQrCode" 
                                   accept="image/png,image/jpeg,image/webp" 
                                   @change="previewQr($event)" 
                                   class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10">
                            
                            <template x-if="!qrPreview">
                                <div class="space-y-1.5 py-2">
                                    <div class="w-10 h-10 mx-auto rounded-full bg-[#FAF7F2] flex items-center justify-center text-gray-500 border border-[#ECE3D2]">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                    </div>
                                    <p class="text-xs font-bold text-gray-700">Click or drag your GCash QR code here</p>
                                    <p class="text-[10px] text-gray-400">Supports JPG, PNG, WEBP up to 5MB</p>
                                </div>
                            </template>

                            <template x-if="qrPreview">
                                <div class="relative inline-block py-1">
                                    <img :src="qrPreview" alt="GCash QR Preview" class="w-28 h-28 object-contain rounded-xl border border-gray-200 mx-auto shadow-sm">
                                    <span class="mt-2 inline-block text-[10px] font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-full border border-emerald-200">QR Selected (Click to change)</span>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                {{-- Step 1 Actions --}}
                <div class="flex items-center justify-between pt-2">
                    <form action="{{ route('seller.onboarding.skip') }}" method="POST">
                        @csrf
                        <button type="submit" class="text-xs font-bold text-gray-500 hover:text-gray-800 transition-colors">Skip onboarding</button>
                    </form>
                    <button type="button" @click="currentStep = 2" class="h-11 px-6 rounded-xl bg-[#C0422A] hover:bg-[#A33520] text-white text-xs font-black uppercase tracking-wider shadow-md hover:shadow-lg transition-all flex items-center gap-2">
                        <span>Continue to Policy</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                    </button>
                </div>
            </div>

            {{-- STEP 2: RETURN & REFUND POLICIES --}}
            <div x-show="currentStep === 2" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" class="space-y-4">
                <div class="bg-[#FAF8F5] border border-[#ECE3D2] rounded-2xl sm:rounded-3xl p-4 sm:p-6 space-y-4">
                    <div class="flex items-center justify-between border-b border-[#ECE3D2] pb-3">
                        <div>
                            <h2 class="text-xs font-black uppercase tracking-wider text-[#1E1915]">2. Return & Refund Policy</h2>
                            <p class="text-[11px] text-gray-500 font-medium">Define terms for product returns, defect inspections, and order adjustments.</p>
                        </div>
                        <span class="text-[10px] font-extrabold text-amber-800 bg-amber-100/80 border border-amber-300/50 px-2 py-0.5 rounded-full uppercase tracking-wider">Pre-filled</span>
                    </div>

                    {{-- Quick Template Helper Chips --}}
                    <div class="flex flex-wrap gap-1.5 items-center">
                        <span class="text-[10.5px] font-bold text-gray-500 mr-1">Insert Lumban Presets:</span>
                        <button type="button" @click="applyStandardPolicy()" class="text-[10.5px] font-bold px-2.5 py-1 rounded-lg bg-white border border-[#D8CEBE] hover:border-[#C0422A] text-gray-700 hover:text-[#C0422A] transition-colors shadow-2xs">
                            Standard 7-Day Inspection
                        </button>
                        <button type="button" @click="applyCustomBarongPolicy()" class="text-[10.5px] font-bold px-2.5 py-1 rounded-lg bg-white border border-[#D8CEBE] hover:border-[#C0422A] text-gray-700 hover:text-[#C0422A] transition-colors shadow-2xs">
                            Bespoke & Custom Sized Barongs
                        </button>
                    </div>

                    {{-- Return & Refund Policy Textarea --}}
                    <div>
                        <label for="refund_policy" class="block text-[11px] font-bold text-gray-800 uppercase tracking-wider mb-1">
                            Return & Replacement Guidelines
                        </label>
                        <textarea id="refund_policy" 
                                  name="refund_policy" 
                                  rows="4" 
                                  x-model="refundPolicy" 
                                  class="w-full p-3.5 bg-white border border-[#D8CEBE] rounded-xl text-xs text-gray-900 outline-none focus:border-[#C0422A] focus:ring-2 focus:ring-[#C0422A]/15 transition-all shadow-xs leading-relaxed"
                                  placeholder="Describe conditions for returns and replacements..."></textarea>
                    </div>

                    {{-- Cancellation Policy Textarea --}}
                    <div>
                        <label for="cancellation_policy" class="block text-[11px] font-bold text-gray-800 uppercase tracking-wider mb-1">
                            Order Cancellation Policy
                        </label>
                        <textarea id="cancellation_policy" 
                                  name="cancellation_policy" 
                                  rows="2" 
                                  x-model="cancellationPolicy" 
                                  class="w-full p-3.5 bg-white border border-[#D8CEBE] rounded-xl text-xs text-gray-900 outline-none focus:border-[#C0422A] focus:ring-2 focus:ring-[#C0422A]/15 transition-all shadow-xs leading-relaxed"
                                  placeholder="Describe conditions under which orders may be cancelled..."></textarea>
                    </div>
                </div>

                {{-- Step 2 Actions --}}
                <div class="flex items-center justify-between pt-2">
                    <button type="button" @click="currentStep = 1" class="h-11 px-5 rounded-xl border border-gray-300 hover:bg-gray-100 text-gray-700 text-xs font-bold transition-all">
                        ← Back
                    </button>
                    <button type="button" @click="currentStep = 3" class="h-11 px-6 rounded-xl bg-[#C0422A] hover:bg-[#A33520] text-white text-xs font-black uppercase tracking-wider shadow-md hover:shadow-lg transition-all flex items-center gap-2">
                        <span>Continue to First Product</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                    </button>
                </div>
            </div>

            {{-- STEP 3: QUICK ADD FIRST PRODUCT (OPTIONAL) --}}
            <div x-show="currentStep === 3" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" class="space-y-4">
                <div class="bg-[#FAF8F5] border border-[#ECE3D2] rounded-2xl sm:rounded-3xl p-4 sm:p-6 space-y-4">
                    <div class="flex items-center justify-between border-b border-[#ECE3D2] pb-3">
                        <div>
                            <h2 class="text-xs font-black uppercase tracking-wider text-[#1E1915]">3. Quick Add First Product</h2>
                            <p class="text-[11px] text-gray-500 font-medium">Showcase your first Barong Tagalog or artisan piece. (You can also add products later in your catalogue).</p>
                        </div>
                        <span class="text-[10px] font-extrabold text-amber-800 bg-amber-100/80 border border-amber-300/50 px-2 py-0.5 rounded-full uppercase tracking-wider">Optional</span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                        {{-- Product Name --}}
                        <div class="sm:col-span-2">
                            <label for="product_name" class="block text-[11px] font-bold text-gray-800 uppercase tracking-wider mb-1">
                                Product Name
                            </label>
                            <input type="text" 
                                   id="product_name" 
                                   name="product_name" 
                                   value="{{ old('product_name') }}" 
                                   placeholder="e.g. Traditional Piña Cocoon Barong Tagalog" 
                                   class="w-full h-11 px-3.5 bg-white border border-[#D8CEBE] rounded-xl text-xs font-semibold text-gray-900 outline-none focus:border-[#C0422A] focus:ring-2 focus:ring-[#C0422A]/15 transition-all shadow-xs">
                        </div>

                        {{-- Product Category --}}
                        <div>
                            <label for="product_category_id" class="block text-[11px] font-bold text-gray-800 uppercase tracking-wider mb-1">
                                Category
                            </label>
                            <select id="product_category_id" 
                                    name="product_category_id" 
                                    class="w-full h-11 px-3 bg-white border border-[#D8CEBE] rounded-xl text-xs font-semibold text-gray-900 outline-none focus:border-[#C0422A] focus:ring-2 focus:ring-[#C0422A]/15 transition-all shadow-xs">
                                <option value="">Select Category</option>
                                @foreach ($categories as $cat)
                                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Product Price & Stock --}}
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label for="product_price" class="block text-[11px] font-bold text-gray-800 uppercase tracking-wider mb-1">
                                    Price (₱)
                                </label>
                                <input type="number" 
                                       id="product_price" 
                                       name="product_price" 
                                       step="0.01" 
                                       min="1" 
                                       placeholder="2500.00" 
                                       class="w-full h-11 px-3 bg-white border border-[#D8CEBE] rounded-xl text-xs font-semibold text-gray-900 outline-none focus:border-[#C0422A] focus:ring-2 focus:ring-[#C0422A]/15 transition-all shadow-xs">
                            </div>
                            <div>
                                <label for="product_stock" class="block text-[11px] font-bold text-gray-800 uppercase tracking-wider mb-1">
                                    Initial Stock
                                </label>
                                <input type="number" 
                                       id="product_stock" 
                                       name="product_stock" 
                                       min="0" 
                                       value="1" 
                                       class="w-full h-11 px-3 bg-white border border-[#D8CEBE] rounded-xl text-xs font-semibold text-gray-900 outline-none focus:border-[#C0422A] focus:ring-2 focus:ring-[#C0422A]/15 transition-all shadow-xs">
                            </div>
                        </div>

                        {{-- Product Description --}}
                        <div class="sm:col-span-2">
                            <label for="product_description" class="block text-[11px] font-bold text-gray-800 uppercase tracking-wider mb-1">
                                Short Description
                            </label>
                            <textarea id="product_description" 
                                      name="product_description" 
                                      rows="2" 
                                      placeholder="Authentic handcrafted Lumban embroidery with premium quality fabric..." 
                                      class="w-full p-3 bg-white border border-[#D8CEBE] rounded-xl text-xs text-gray-900 outline-none focus:border-[#C0422A] focus:ring-2 focus:ring-[#C0422A]/15 transition-all shadow-xs"></textarea>
                        </div>

                        {{-- Product Primary Photo --}}
                        <div class="sm:col-span-2">
                            <label class="block text-[11px] font-bold text-gray-800 uppercase tracking-wider mb-1">
                                Product Cover Photo
                            </label>
                            <div class="border-2 border-dashed border-[#D8CEBE] rounded-2xl p-4 bg-white hover:bg-[#FAF8F5] transition-colors text-center cursor-pointer relative">
                                <input type="file" 
                                       name="product_image" 
                                       accept="image/png,image/jpeg,image/webp" 
                                       @change="previewProduct($event)" 
                                       class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10">
                                
                                <template x-if="!productPreview">
                                    <div class="space-y-1 py-1">
                                        <div class="w-9 h-9 mx-auto rounded-full bg-[#FAF7F2] flex items-center justify-center text-gray-500 border border-[#ECE3D2]">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                        </div>
                                        <p class="text-xs font-bold text-gray-700">Upload Product Photo</p>
                                        <p class="text-[10px] text-gray-400">JPG, PNG, or WEBP up to 5MB</p>
                                    </div>
                                </template>

                                <template x-if="productPreview">
                                    <div class="relative inline-block py-1">
                                        <img :src="productPreview" alt="Product Photo Preview" class="w-24 h-24 object-cover rounded-xl border border-gray-200 mx-auto shadow-sm">
                                        <span class="mt-1.5 inline-block text-[10px] font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-full border border-emerald-200">Photo Attached</span>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Step 3 Actions (Submit) --}}
                <div class="flex items-center justify-between pt-2">
                    <button type="button" @click="currentStep = 2" class="h-11 px-5 rounded-xl border border-gray-300 hover:bg-gray-100 text-gray-700 text-xs font-bold transition-all">
                        ← Back
                    </button>
                    <button type="submit" class="h-11 px-8 rounded-xl bg-gradient-to-r from-[#C0422A] to-[#8E2815] hover:from-[#A33520] hover:to-[#741F10] text-white text-xs font-black uppercase tracking-wider shadow-lg hover:shadow-xl transition-all flex items-center gap-2">
                        <span>Complete Setup & Open Shop</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    </button>
                </div>
            </div>
        </form>
    </div>

    <script>
        function sellerOnboarding() {
            return {
                currentStep: 1,
                qrPreview: '{{ $user->gcashQrCode ? asset(ltrim($user->gcashQrCode, "/")) : "" }}',
                productPreview: '',
                refundPolicy: `{!! addslashes($user->refund_policy ?: "Items may be returned or replaced within 7 days of delivery if there is a verified defect in fabric or hand-embroidery. Items must be unwashed, unworn, and in original artisan packaging.") !!}`,
                cancellationPolicy: `{!! addslashes($user->cancellation_policy ?: "Orders may be cancelled within 24 hours of placement prior to production commencement. Customized or bespoke orders cannot be cancelled once cutting and embroidery begins.") !!}`,

                previewQr(event) {
                    const file = event.target.files[0];
                    if (file) {
                        this.qrPreview = URL.createObjectURL(file);
                    }
                },

                previewProduct(event) {
                    const file = event.target.files[0];
                    if (file) {
                        this.productPreview = URL.createObjectURL(file);
                    }
                },

                applyStandardPolicy() {
                    this.refundPolicy = "Items may be returned or replaced within 7 days of delivery if there is a verified defect in fabric or hand-embroidery. Items must be unwashed, unworn, and in original artisan packaging.";
                    this.cancellationPolicy = "Orders may be cancelled within 24 hours of placement prior to production commencement.";
                },

                applyCustomBarongPolicy() {
                    this.refundPolicy = "Due to the custom tailored nature of bespoke Barong Tagalogs, returns are only accepted for sizing adjustments or verified craftsmanship defects within 7 days.";
                    this.cancellationPolicy = "Custom bespoke orders cannot be cancelled once artisan cutting and embroidery have commenced.";
                }
            }
        }
    </script>
</body>
</html>
