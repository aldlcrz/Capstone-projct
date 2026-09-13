<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>Artisan Store Setup | LumBarong</title>
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('images/apple-touch-icon.png') }}">
    
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Playfair+Display:ital,wght@0,600;0,700;0,800;0,900;1,600;1,700;1,800&display=swap" rel="stylesheet">
    
    <style>
        body { font-family: 'Inter', sans-serif; background: #F7F3EE; color: #1E1915; }
        .font-serif { font-family: 'Playfair Display', serif; }
        [x-cloak] { display: none !important; }
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
    </style>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>
</head>
<body class="min-h-screen flex items-center justify-center p-3 sm:p-6 lg:p-8 relative overflow-x-hidden" id="seller-onboarding-body">
    <!-- Ambient Heritage Glow Orbs -->
    <div class="absolute top-0 right-0 w-96 sm:w-160 h-96 sm:h-160 rounded-full -translate-y-1/3 translate-x-1/4 blur-3xl opacity-[0.06] pointer-events-none bg-[#C0422A]"></div>
    <div class="absolute bottom-0 left-0 w-80 sm:w-130 h-80 sm:h-130 rounded-full translate-y-1/3 -translate-x-1/4 blur-3xl opacity-[0.12] pointer-events-none bg-[#D4B896]"></div>

    <!-- Standalone Global Skip Form -->
    <form id="seller-skip-form" action="{{ route('seller.onboarding.skip') }}" method="POST" class="hidden">
        @csrf
    </form>

    <!-- Main Container Card -->
    <div class="w-full max-w-2xl bg-white rounded-3xl sm:rounded-[2.5rem] border border-[#E5DDD5] p-6 sm:p-9 lg:p-10 shadow-[0_25px_70px_rgba(60,40,20,0.07)] relative z-10 my-4 sm:my-8 transition-all duration-300"
         x-data="sellerOnboarding()"
         x-cloak>
        
        {{-- Top Bar Header --}}
        <div class="flex items-center justify-between pb-4 sm:pb-5 border-b border-[#EFE8DC] mb-6 sm:mb-8">
            <div class="flex items-center gap-2.5">
                <span class="w-2 h-2 rounded-full bg-[#C0422A]"></span>
                <span class="text-[10px] font-black uppercase tracking-[0.25em] text-gray-400">Artisan Onboarding</span>
            </div>
            
            <button type="button" 
                    @click="submitSkip()"
                    :disabled="isSkipping"
                    class="group inline-flex items-center gap-1.5 text-xs font-bold text-gray-500 hover:text-[#C0422A] transition-all py-1.5 px-3.5 rounded-full hover:bg-[#FAF7F2] border border-[#E8DFC8] hover:border-[#C0422A]/30 cursor-pointer shadow-2xs">
                <template x-if="!isSkipping">
                    <span class="inline-flex items-center gap-1.5">
                        <span>Skip setup for now</span>
                        <svg class="w-3.5 h-3.5 transition-transform group-hover:translate-x-0.5 text-gray-400 group-hover:text-[#C0422A]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                    </span>
                </template>
                <template x-if="isSkipping">
                    <span class="inline-flex items-center gap-1.5 text-[#C0422A]">
                        <svg class="animate-spin w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        <span>Opening Dashboard...</span>
                    </span>
                </template>
            </button>
        </div>

        {{-- Branding & Greeting Header --}}
        <div class="text-center mb-6 sm:mb-8">
            <div class="flex justify-center mb-3">
                <div class="w-14 h-14 sm:w-16 sm:h-16 rounded-full p-0.5 bg-gradient-to-tr from-[#C0422A] to-[#D4B896] shadow-sm">
                    <div class="w-full h-full rounded-full bg-white p-1 flex items-center justify-center">
                        <img src="{{ asset('images/logo-icon.png') }}" alt="LumBarong Logo" class="w-full h-full object-contain rounded-full">
                    </div>
                </div>
            </div>
            
            <div class="inline-flex items-center gap-1.5 px-3.5 py-1 rounded-full bg-[#C0422A]/10 text-[#C0422A] text-[10px] font-black uppercase tracking-widest mb-2 border border-[#C0422A]/20">
                <span>Welcome, {{ $user->shopName ?: $user->name }}</span>
            </div>
            
            <h1 class="font-serif text-2xl sm:text-3xl lg:text-[2rem] font-black italic tracking-tight text-gray-900 mb-2 leading-tight">
                Set Up Your Artisan Store
            </h1>
            <p class="text-xs sm:text-[13px] text-gray-600 font-medium max-w-md mx-auto leading-relaxed">
                Configure your payout destination, return guidelines, and prepare to publish your handcrafted creations.
            </p>
        </div>

        {{-- Premium 3-Stage Progress Nav --}}
        <div class="bg-[#FAF8F5] border border-[#ECE3D2] rounded-2xl p-1.5 sm:p-2 mb-6 sm:mb-8 shadow-2xs">
            <div class="grid grid-cols-3 gap-1.5 sm:gap-2">
                {{-- Step 1 Button --}}
                <button type="button" 
                        @click="currentStep = 1" 
                        class="flex items-center justify-center sm:justify-start gap-2 py-2 px-2.5 sm:px-3 rounded-xl transition-all duration-200 cursor-pointer"
                        :class="currentStep === 1 ? 'bg-white shadow-xs border border-[#E5DDD5]' : 'hover:bg-white/60'">
                    <span class="w-5 h-5 rounded-full flex items-center justify-center text-[10px] font-black shrink-0 transition-colors"
                          :class="currentStep === 1 ? 'bg-[#C0422A] text-white shadow-xs' : (hasGcash ? 'bg-emerald-600 text-white' : 'bg-[#EAE2D5] text-gray-600')">
                        <template x-if="hasGcash && currentStep !== 1">✓</template>
                        <template x-if="!hasGcash || currentStep === 1">1</template>
                    </span>
                    <div class="text-left hidden sm:block">
                        <div class="text-[11px] font-bold leading-tight" :class="currentStep === 1 ? 'text-gray-900' : 'text-gray-600'">GCash Payout</div>
                        <div class="text-[9.5px] font-semibold text-gray-400">Payment Setup</div>
                    </div>
                    <span class="sm:hidden text-[10.5px] font-bold" :class="currentStep === 1 ? 'text-[#C0422A]' : 'text-gray-600'">GCash</span>
                </button>

                {{-- Step 2 Button --}}
                <button type="button" 
                        @click="currentStep = 2" 
                        class="flex items-center justify-center sm:justify-start gap-2 py-2 px-2.5 sm:px-3 rounded-xl transition-all duration-200 cursor-pointer"
                        :class="currentStep === 2 ? 'bg-white shadow-xs border border-[#E5DDD5]' : 'hover:bg-white/60'">
                    <span class="w-5 h-5 rounded-full flex items-center justify-center text-[10px] font-black shrink-0 transition-colors"
                          :class="currentStep === 2 ? 'bg-[#C0422A] text-white shadow-xs' : (hasPolicy ? 'bg-emerald-600 text-white' : 'bg-[#EAE2D5] text-gray-600')">
                        <template x-if="hasPolicy && currentStep !== 2">✓</template>
                        <template x-if="!hasPolicy || currentStep === 2">2</template>
                    </span>
                    <div class="text-left hidden sm:block">
                        <div class="text-[11px] font-bold leading-tight" :class="currentStep === 2 ? 'text-gray-900' : 'text-gray-600'">Shop Policy</div>
                        <div class="text-[9.5px] font-semibold text-gray-400">Return & Terms</div>
                    </div>
                    <span class="sm:hidden text-[10.5px] font-bold" :class="currentStep === 2 ? 'text-[#C0422A]' : 'text-gray-600'">Policy</span>
                </button>

                {{-- Step 3 Button --}}
                <button type="button" 
                        @click="currentStep = 3" 
                        class="flex items-center justify-center sm:justify-start gap-2 py-2 px-2.5 sm:px-3 rounded-xl transition-all duration-200 cursor-pointer"
                        :class="currentStep === 3 ? 'bg-white shadow-xs border border-[#E5DDD5]' : 'hover:bg-white/60'">
                    <span class="w-5 h-5 rounded-full flex items-center justify-center text-[10px] font-black shrink-0 transition-colors"
                          :class="currentStep === 3 ? 'bg-[#C0422A] text-white shadow-xs' : 'bg-[#EAE2D5] text-gray-600'">3</span>
                    <div class="text-left hidden sm:block">
                        <div class="text-[11px] font-bold leading-tight" :class="currentStep === 3 ? 'text-gray-900' : 'text-gray-600'">Add Product</div>
                        <div class="text-[9.5px] font-semibold text-gray-400">Catalogue Item</div>
                    </div>
                    <span class="sm:hidden text-[10.5px] font-bold" :class="currentStep === 3 ? 'text-[#C0422A]' : 'text-gray-600'">Product</span>
                </button>
            </div>
        </div>

        {{-- Error Summary Alert --}}
        @if ($errors->any())
            <div class="mb-6 p-4 rounded-2xl bg-red-50/90 border border-red-200 text-red-900 text-xs shadow-2xs animate-fade-in">
                <div class="flex items-center gap-2 font-bold mb-1">
                    <svg class="w-4 h-4 text-red-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span>Please review the highlighted fields:</span>
                </div>
                <ul class="list-disc list-inside space-y-0.5 text-red-700 font-medium pl-1">
                    @foreach ($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Main Setup Wizard Form --}}
        <form action="{{ route('seller.onboarding.save') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
            @csrf
            <input type="hidden" name="redirect_to" x-model="redirectTo">

            {{-- ======================================================== --}}
            {{-- STEP 1: GCASH & PAYOUT SETUP                              --}}
            {{-- ======================================================== --}}
            <div x-show="currentStep === 1" x-transition:enter="transition ease-out duration-250" x-transition:enter-start="opacity-0 translate-y-3" x-transition:enter-end="opacity-100 translate-y-0" class="space-y-5">
                <div class="bg-[#FAF8F5] border border-[#ECE3D2] rounded-2xl sm:rounded-3xl p-5 sm:p-7 space-y-5 shadow-xs">
                    {{-- Section Header with GCash Branding --}}
                    <div class="flex items-center justify-between border-b border-[#ECE3D2] pb-4">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-blue-600 to-sky-400 flex items-center justify-center text-white font-black text-xs shadow-xs shrink-0">
                                ₱
                            </div>
                            <div>
                                <h2 class="text-xs font-black uppercase tracking-wider text-gray-900">1. Artisan GCash Payout</h2>
                                <p class="text-[11px] text-gray-500 font-medium">Customer payments will be deposited directly to this GCash account.</p>
                            </div>
                        </div>
                        <span class="text-[9.5px] font-extrabold text-amber-800 bg-amber-100/90 border border-amber-300/60 px-2.5 py-0.5 rounded-full uppercase tracking-wider shrink-0">Optional</span>
                    </div>

                    {{-- GCash Number Input --}}
                    <div>
                        <label for="gcashNumber" class="block text-[11px] font-bold text-gray-800 uppercase tracking-wider mb-1.5">
                            GCash Mobile Phone Number
                        </label>
                        <div class="flex items-center rounded-xl bg-white border border-[#D8CEBE] overflow-hidden focus-within:border-[#C0422A] focus-within:ring-2 focus-within:ring-[#C0422A]/15 transition-all shadow-xs h-12">
                            <div class="flex items-center justify-center gap-1.5 px-4 bg-[#FAF7F2] border-r border-[#D8CEBE] text-xs font-bold text-gray-700 select-none shrink-0 h-full">
                                <span>🇵🇭 +63</span>
                            </div>
                            <input type="text" 
                                   id="gcashNumber" 
                                   name="gcashNumber" 
                                   x-model="gcashNumber"
                                   value="{{ old('gcashNumber', $user->gcashNumber) }}" 
                                   placeholder="9171234567" 
                                   class="w-full h-full px-4 bg-transparent text-sm font-semibold text-gray-900 outline-none placeholder:text-gray-400">
                        </div>
                        <p class="text-[10.5px] text-gray-500 mt-1.5 font-medium">Enter your 10-digit mobile number starting with 9.</p>
                    </div>

                    {{-- GCash QR Code Upload Card --}}
                    <div>
                        <label class="block text-[11px] font-bold text-gray-800 uppercase tracking-wider mb-1.5">
                            GCash Personal / Merchant QR Code
                        </label>
                        <div class="border-2 border-dashed border-[#D8CEBE] hover:border-[#C0422A] rounded-2xl p-5 bg-white hover:bg-[#FAF8F5] transition-all text-center cursor-pointer relative group shadow-xs">
                            <input type="file" 
                                   name="gcashQrCode" 
                                   accept="image/png,image/jpeg,image/webp" 
                                   @change="previewQr($event)" 
                                   class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10">
                            
                            {{-- Placeholder state --}}
                            <template x-if="!qrPreview">
                                <div class="space-y-2 py-3">
                                    <div class="w-12 h-12 mx-auto rounded-2xl bg-[#FAF7F2] group-hover:bg-[#FAF0ED] flex items-center justify-center text-gray-500 group-hover:text-[#C0422A] border border-[#ECE3D2] transition-colors shadow-2xs">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                    </div>
                                    <div>
                                        <p class="text-xs font-bold text-gray-800 group-hover:text-[#C0422A] transition-colors">Click or drag your GCash QR code here</p>
                                        <p class="text-[10.5px] text-gray-400 font-medium mt-0.5">Supports high-res PNG, JPG, or WEBP up to 5MB</p>
                                    </div>
                                </div>
                            </template>

                            {{-- Active Preview State --}}
                            <template x-if="qrPreview">
                                <div class="py-2 space-y-2.5">
                                    <div class="relative inline-block">
                                        <img :src="qrPreview" alt="GCash QR Code" class="w-32 h-32 object-contain rounded-xl border border-gray-200 mx-auto shadow-md p-1 bg-white">
                                    </div>
                                    <div>
                                        <span class="inline-flex items-center gap-1.5 text-[10.5px] font-bold text-emerald-800 bg-emerald-50 px-3 py-1 rounded-full border border-emerald-200 shadow-2xs">
                                            <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                            QR Code Attached (Click to change)
                                        </span>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                {{-- Step 1 Footer Navigation --}}
                <div class="flex items-center justify-between pt-2">
                    <button type="button" 
                            @click="submitSkip()"
                            :disabled="isSkipping"
                            class="text-xs font-bold text-gray-500 hover:text-[#C0422A] transition-colors py-2 px-1 cursor-pointer flex items-center gap-1.5">
                        <template x-if="!isSkipping">
                            <span>Skip setup & go to Dashboard</span>
                        </template>
                        <template x-if="isSkipping">
                            <span class="text-[#C0422A]">Opening Dashboard...</span>
                        </template>
                    </button>

                    <button type="button" 
                            @click="currentStep = 2" 
                            class="h-13 px-8 rounded-full bg-[#3D2B1F] hover:bg-[#C0422A] text-white font-bold uppercase tracking-[0.18em] text-[10.5px] shadow-md hover:shadow-lg hover:-translate-y-0.5 active:translate-y-0 transition-all duration-300 flex items-center gap-2.5 cursor-pointer">
                        <span>Continue to Policies</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                    </button>
                </div>
            </div>

            {{-- ======================================================== --}}
            {{-- STEP 2: RETURN & REFUND POLICIES                          --}}
            {{-- ======================================================== --}}
            <div x-show="currentStep === 2" x-transition:enter="transition ease-out duration-250" x-transition:enter-start="opacity-0 translate-y-3" x-transition:enter-end="opacity-100 translate-y-0" class="space-y-5">
                <div class="bg-[#FAF8F5] border border-[#ECE3D2] rounded-2xl sm:rounded-3xl p-5 sm:p-7 space-y-5 shadow-xs">
                    {{-- Section Header --}}
                    <div class="flex items-center justify-between border-b border-[#ECE3D2] pb-4">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-amber-700 to-amber-500 flex items-center justify-center text-white font-black text-xs shadow-xs shrink-0">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            </div>
                            <div>
                                <h2 class="text-xs font-black uppercase tracking-wider text-gray-900">2. Shop Policies & Guidelines</h2>
                                <p class="text-[11px] text-gray-500 font-medium">Protect your craft with clear customer return and order cancellation terms.</p>
                            </div>
                        </div>
                        <span class="text-[9.5px] font-extrabold text-[#C0422A] bg-[#C0422A]/10 border border-[#C0422A]/20 px-2.5 py-0.5 rounded-full uppercase tracking-wider shrink-0">Pre-filled</span>
                    </div>

                    {{-- 1-Click Lumban Artisan Presets --}}
                    <div>
                        <span class="block text-[10.5px] font-black text-gray-700 uppercase tracking-wider mb-2">Apply 1-Click Lumban Presets:</span>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                            <button type="button" 
                                    @click="applyStandardPolicy()" 
                                    class="text-left p-3.5 rounded-xl bg-white border border-[#D8CEBE] hover:border-[#C0422A] transition-all shadow-2xs hover:shadow-xs group cursor-pointer">
                                <div class="flex items-center justify-between mb-1">
                                    <span class="text-xs font-bold text-gray-900 group-hover:text-[#C0422A] transition-colors">Standard 7-Day Inspection</span>
                                    <span class="text-[9px] font-extrabold text-emerald-700 bg-emerald-50 px-1.5 py-0.5 rounded border border-emerald-200">Recommended</span>
                                </div>
                                <p class="text-[10.5px] text-gray-500 leading-tight">Covers verified fabric and hand-embroidery defects within 7 days.</p>
                            </button>

                            <button type="button" 
                                    @click="applyCustomBarongPolicy()" 
                                    class="text-left p-3.5 rounded-xl bg-white border border-[#D8CEBE] hover:border-[#C0422A] transition-all shadow-2xs hover:shadow-xs group cursor-pointer">
                                <div class="flex items-center justify-between mb-1">
                                    <span class="text-xs font-bold text-gray-900 group-hover:text-[#C0422A] transition-colors">Bespoke Custom Barongs</span>
                                    <span class="text-[9px] font-extrabold text-amber-700 bg-amber-50 px-1.5 py-0.5 rounded border border-amber-200">Made-to-Order</span>
                                </div>
                                <p class="text-[10.5px] text-gray-500 leading-tight">Tailored pieces with size alteration support and strict cutting policy.</p>
                            </button>
                        </div>
                    </div>

                    {{-- Return Policy Textarea --}}
                    <div>
                        <label for="refund_policy" class="block text-[11px] font-bold text-gray-800 uppercase tracking-wider mb-1.5">
                            Return & Replacement Terms
                        </label>
                        <textarea id="refund_policy" 
                                  name="refund_policy" 
                                  rows="3" 
                                  x-model="refundPolicy" 
                                  class="w-full p-3.5 bg-white border border-[#D8CEBE] rounded-xl text-xs text-gray-900 outline-none focus:border-[#C0422A] focus:ring-2 focus:ring-[#C0422A]/15 transition-all shadow-xs leading-relaxed"
                                  placeholder="Describe conditions for returns and replacements..."></textarea>
                    </div>

                    {{-- Cancellation Policy Textarea --}}
                    <div>
                        <label for="cancellation_policy" class="block text-[11px] font-bold text-gray-800 uppercase tracking-wider mb-1.5">
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

                {{-- Step 2 Footer Navigation --}}
                <div class="flex items-center justify-between pt-2">
                    <button type="button" @click="currentStep = 1" class="h-13 px-6 rounded-full border border-gray-300 hover:bg-gray-100 text-gray-700 text-xs font-bold transition-all cursor-pointer">
                        ← Back
                    </button>
                    <button type="button" 
                            @click="currentStep = 3" 
                            class="h-13 px-8 rounded-full bg-[#3D2B1F] hover:bg-[#C0422A] text-white font-bold uppercase tracking-[0.18em] text-[10.5px] shadow-md hover:shadow-lg hover:-translate-y-0.5 active:translate-y-0 transition-all duration-300 flex items-center gap-2.5 cursor-pointer">
                        <span>Next: Catalogue Launch</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                    </button>
                </div>
            </div>

            {{-- ======================================================== --}}
            {{-- STEP 3: HIGH-END ARTISAN CATALOGUE LAUNCH                 --}}
            {{-- ======================================================== --}}
            <div x-show="currentStep === 3" x-transition:enter="transition ease-out duration-250" x-transition:enter-start="opacity-0 translate-y-3" x-transition:enter-end="opacity-100 translate-y-0" class="space-y-6">
                
                {{-- Hero Celebration Card --}}
                <div class="relative overflow-hidden bg-gradient-to-b from-[#FAF7F2] to-white border border-[#E8DFC8] rounded-3xl p-6 sm:p-9 text-center shadow-xs">
                    
                    {{-- Decorative Top Watermark Pattern --}}
                    <div class="absolute -top-10 -right-10 w-40 h-40 rounded-full bg-[#C0422A]/5 pointer-events-none blur-xl"></div>
                    <div class="absolute -bottom-10 -left-10 w-40 h-40 rounded-full bg-[#D4B896]/15 pointer-events-none blur-xl"></div>

                    {{-- Elegant Artisan Badge --}}
                    <div class="inline-flex items-center gap-2 px-3.5 py-1 rounded-full bg-emerald-50 border border-emerald-200/80 text-emerald-800 text-[10px] font-black uppercase tracking-widest mb-4 shadow-2xs">
                        <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                        <span>Step 3 of 3: Store Launch</span>
                    </div>

                    {{-- Main Title --}}
                    <h2 class="font-serif text-2xl sm:text-3xl lg:text-[2.15rem] font-black italic tracking-tight text-gray-900 mb-2.5 leading-tight">
                        Your Artisan Store is Ready
                    </h2>
                    
                    <p class="text-xs sm:text-[13.5px] text-gray-600 font-medium max-w-lg mx-auto leading-relaxed mb-6">
                        Your payment routing and store policies have been successfully configured. Proceed to the <strong>Product Studio</strong> to publish your first Barong Tagalog or explore your shop dashboard.
                    </p>

                    {{-- Feature Highlight Grid --}}
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 max-w-xl mx-auto mb-8 text-left">
                        {{-- Feature 1 --}}
                        <div class="p-3.5 rounded-2xl bg-white border border-[#ECE3D2] shadow-2xs flex sm:flex-col items-center sm:items-start gap-3 sm:gap-2">
                            <div class="w-8 h-8 rounded-xl bg-[#FAF7F2] border border-[#E0D7C8] flex items-center justify-center text-[#C0422A] shrink-0">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                            </div>
                            <div>
                                <h3 class="text-xs font-bold text-gray-900 leading-tight">Custom Sizing</h3>
                                <p class="text-[10px] text-gray-500 font-medium">Bespoke measurements & standard fits</p>
                            </div>
                        </div>

                        {{-- Feature 2 --}}
                        <div class="p-3.5 rounded-2xl bg-white border border-[#ECE3D2] shadow-2xs flex sm:flex-col items-center sm:items-start gap-3 sm:gap-2">
                            <div class="w-8 h-8 rounded-xl bg-[#FAF7F2] border border-[#E0D7C8] flex items-center justify-center text-[#C0422A] shrink-0">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            </div>
                            <div>
                                <h3 class="text-xs font-bold text-gray-900 leading-tight">Embroidery Gallery</h3>
                                <p class="text-[10px] text-gray-500 font-medium">Multi-photo high-res closeups</p>
                            </div>
                        </div>

                        {{-- Feature 3 --}}
                        <div class="p-3.5 rounded-2xl bg-white border border-[#ECE3D2] shadow-2xs flex sm:flex-col items-center sm:items-start gap-3 sm:gap-2">
                            <div class="w-8 h-8 rounded-xl bg-[#FAF7F2] border border-[#E0D7C8] flex items-center justify-center text-[#C0422A] shrink-0">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                            <div>
                                <h3 class="text-xs font-bold text-gray-900 leading-tight">Stock & Discounts</h3>
                                <p class="text-[10px] text-gray-500 font-medium">Real-time inventory control</p>
                            </div>
                        </div>
                    </div>

                    {{-- Premium Stacked Action Deck --}}
                    <div class="space-y-3 max-w-md mx-auto pt-1">
                        <button type="submit" 
                                @click="redirectTo = 'add_product'"
                                class="w-full h-14 bg-[#3D2B1F] hover:bg-[#C0422A] text-white font-bold uppercase tracking-[0.2em] text-[11px] rounded-full shadow-lg hover:shadow-xl hover:-translate-y-0.5 active:translate-y-0 transition-all duration-300 flex items-center justify-center gap-3 cursor-pointer">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                            <span>Add Your First Product</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                        </button>

                        <button type="submit" 
                                @click="redirectTo = 'dashboard'"
                                class="w-full h-12 bg-[#FAF7F2] hover:bg-[#F2ECE1] text-gray-700 hover:text-gray-900 font-bold uppercase tracking-[0.15em] text-[10.5px] rounded-full border border-[#D8CEBE] hover:border-[#C0422A]/40 transition-all duration-200 flex items-center justify-center gap-2 cursor-pointer">
                            <span>Go to Dashboard</span>
                            <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                        </button>
                    </div>
                </div>

                {{-- Step 3 Footer Navigation --}}
                <div class="flex items-center justify-start pt-1">
                    <button type="button" @click="currentStep = 2" class="h-11 px-5 rounded-full border border-gray-300 hover:bg-gray-100 text-gray-700 text-xs font-bold transition-all cursor-pointer">
                        ← Back to Policies
                    </button>
                </div>
            </div>
        </form>
    </div>

    {{-- Alpine Wizard Logic --}}
    <script>
        function sellerOnboarding() {
            return {
                currentStep: 1,
                isSkipping: false,
                redirectTo: 'add_product',
                gcashNumber: '{{ old("gcashNumber", $user->gcashNumber) }}',
                qrPreview: '{{ $user->gcashQrCode ? asset(ltrim($user->gcashQrCode, "/")) : "" }}',
                refundPolicy: `{!! addslashes($user->refund_policy ?: "Items may be returned or replaced within 7 days of delivery if there is a verified defect in fabric or hand-embroidery. Items must be unwashed, unworn, and in original artisan packaging.") !!}`,
                cancellationPolicy: `{!! addslashes($user->cancellation_policy ?: "Orders may be cancelled within 24 hours of placement prior to production commencement. Customized or bespoke orders cannot be cancelled once cutting and embroidery begins.") !!}`,

                get hasGcash() {
                    return (this.gcashNumber && this.gcashNumber.trim().length >= 10) || !!this.qrPreview;
                },

                get hasPolicy() {
                    return this.refundPolicy && this.refundPolicy.trim().length > 10;
                },

                submitSkip() {
                    this.isSkipping = true;
                    const skipForm = document.getElementById('seller-skip-form');
                    if (skipForm) {
                        skipForm.submit();
                    }
                },

                previewQr(event) {
                    const file = event.target.files[0];
                    if (file) {
                        this.qrPreview = URL.createObjectURL(file);
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
