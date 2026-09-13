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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Playfair+Display:ital,wght@0,700;0,800;0,900;1,700;1,800&display=swap" rel="stylesheet">
    
    <style>
        body { font-family: 'Inter', sans-serif; background: #F7F3EE; }
        .font-serif { font-family: 'Playfair Display', serif; }
        [x-cloak] { display: none !important; }
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }

        @keyframes pulse-subtle {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.85; transform: scale(1.03); }
        }
        .animate-pulse-subtle {
            animation: pulse-subtle 3s ease-in-out infinite;
        }
    </style>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>
</head>
<body class="min-h-screen flex items-center justify-center p-3 sm:p-6 lg:p-8 relative overflow-x-hidden" id="seller-onboarding-body">
    <!-- Ambient Heritage Glow Orbs -->
    <div class="absolute top-0 right-0 w-96 sm:w-160 h-96 sm:h-160 rounded-full -translate-y-1/3 translate-x-1/4 blur-3xl opacity-[0.07] pointer-events-none bg-[#C0422A]"></div>
    <div class="absolute bottom-0 left-0 w-80 sm:w-130 h-80 sm:h-130 rounded-full translate-y-1/3 -translate-x-1/4 blur-3xl opacity-[0.14] pointer-events-none bg-[#D4B896]"></div>

    <!-- Main Container Card -->
    <div class="w-full max-w-2xl bg-white rounded-3xl sm:rounded-[2.5rem] border border-[#E5DDD5] p-5 sm:p-8 lg:p-10 shadow-[0_25px_70px_rgba(60,40,20,0.08)] relative z-10 my-4 sm:my-8 transition-all duration-300"
         x-data="sellerOnboarding()"
         x-cloak>
        
        {{-- Top Status Header & Skip Action --}}
        <div class="flex items-center justify-between pb-4 sm:pb-5 border-b border-[#EFE8DC] mb-6 sm:mb-8">
            <div class="flex items-center gap-2.5">
                <span class="w-2.5 h-2.5 rounded-full bg-[#C0422A] animate-ping"></span>
                <span class="text-[10.5px] font-black uppercase tracking-[0.2em] text-gray-400">Artisan Onboarding</span>
            </div>
            
            <form action="{{ route('seller.onboarding.skip') }}" method="POST" class="inline">
                @csrf
                <button type="submit" class="group inline-flex items-center gap-1.5 text-xs font-bold text-gray-500 hover:text-[#C0422A] transition-all py-1.5 px-3 rounded-full hover:bg-[#FAF7F2] border border-transparent hover:border-[#E8DFC8]">
                    <span>Skip setup for now</span>
                    <svg class="w-3.5 h-3.5 transition-transform group-hover:translate-x-0.5 text-gray-400 group-hover:text-[#C0422A]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                </button>
            </form>
        </div>

        {{-- Branding & Greeting Header --}}
        <div class="text-center mb-6 sm:mb-8">
            <div class="flex justify-center mb-3">
                <div class="w-14 h-14 sm:w-16 sm:h-16 rounded-full p-0.5 bg-gradient-to-tr from-[#C0422A] to-[#D4B896] shadow-md">
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
                Configure your payout destination, return terms, and showcase your authentic Lumban craftsmanship.
            </p>
        </div>

        {{-- Premium 3-Stage Progress Nav --}}
        <div class="bg-[#FAF8F5] border border-[#ECE3D2] rounded-2xl p-2 sm:p-2.5 mb-6 sm:mb-8 shadow-xs">
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
                          :class="currentStep === 3 ? 'bg-[#C0422A] text-white shadow-xs' : (hasProduct ? 'bg-emerald-600 text-white' : 'bg-[#EAE2D5] text-gray-600')">
                        <template x-if="hasProduct && currentStep !== 3">✓</template>
                        <template x-if="!hasProduct || currentStep === 3">3</template>
                    </span>
                    <div class="text-left hidden sm:block">
                        <div class="text-[11px] font-bold leading-tight" :class="currentStep === 3 ? 'text-gray-900' : 'text-gray-600'">First Product</div>
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
                    <form action="{{ route('seller.onboarding.skip') }}" method="POST">
                        @csrf
                        <button type="submit" class="text-xs font-bold text-gray-500 hover:text-gray-800 transition-colors py-2 px-1">
                            Skip this step
                        </button>
                    </form>
                    <button type="button" 
                            @click="currentStep = 2" 
                            class="h-12 px-7 rounded-xl bg-gradient-to-r from-[#C0422A] via-[#9B2C16] to-[#7D1E0C] hover:from-[#A83520] hover:to-[#6C1708] text-white text-xs font-black uppercase tracking-wider shadow-[0_8px_20px_rgba(192,66,42,0.25)] hover:shadow-[0_12px_28px_rgba(192,66,42,0.35)] hover:-translate-y-0.5 active:translate-y-0 transition-all duration-200 flex items-center gap-2 cursor-pointer">
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
                                📜
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
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                            <button type="button" 
                                    @click="applyStandardPolicy()" 
                                    class="text-left p-3 rounded-xl bg-white border border-[#D8CEBE] hover:border-[#C0422A] transition-all shadow-2xs hover:shadow-xs group cursor-pointer">
                                <div class="flex items-center justify-between mb-1">
                                    <span class="text-xs font-bold text-gray-900 group-hover:text-[#C0422A] transition-colors">Standard 7-Day Inspection</span>
                                    <span class="text-[9px] font-extrabold text-emerald-700 bg-emerald-50 px-1.5 py-0.5 rounded border border-emerald-200">Recommended</span>
                                </div>
                                <p class="text-[10.5px] text-gray-500 leading-tight">Covers verified fabric and hand-embroidery defects within 7 days.</p>
                            </button>

                            <button type="button" 
                                    @click="applyCustomBarongPolicy()" 
                                    class="text-left p-3 rounded-xl bg-white border border-[#D8CEBE] hover:border-[#C0422A] transition-all shadow-2xs hover:shadow-xs group cursor-pointer">
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
                    <button type="button" @click="currentStep = 1" class="h-12 px-6 rounded-xl border border-gray-300 hover:bg-gray-100 text-gray-700 text-xs font-bold transition-all cursor-pointer">
                        ← Back
                    </button>
                    <button type="button" 
                            @click="currentStep = 3" 
                            class="h-12 px-7 rounded-xl bg-gradient-to-r from-[#C0422A] via-[#9B2C16] to-[#7D1E0C] hover:from-[#A83520] hover:to-[#6C1708] text-white text-xs font-black uppercase tracking-wider shadow-[0_8px_20px_rgba(192,66,42,0.25)] hover:shadow-[0_12px_28px_rgba(192,66,42,0.35)] hover:-translate-y-0.5 active:translate-y-0 transition-all duration-200 flex items-center gap-2 cursor-pointer">
                        <span>Continue to First Product</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                    </button>
                </div>
            </div>

            {{-- ======================================================== --}}
            {{-- STEP 3: QUICK ADD FIRST PRODUCT (OPTIONAL)                --}}
            {{-- ======================================================== --}}
            <div x-show="currentStep === 3" x-transition:enter="transition ease-out duration-250" x-transition:enter-start="opacity-0 translate-y-3" x-transition:enter-end="opacity-100 translate-y-0" class="space-y-5">
                <div class="bg-[#FAF8F5] border border-[#ECE3D2] rounded-2xl sm:rounded-3xl p-5 sm:p-7 space-y-5 shadow-xs">
                    {{-- Section Header --}}
                    <div class="flex items-center justify-between border-b border-[#ECE3D2] pb-4">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-rose-800 to-rose-600 flex items-center justify-center text-white font-black text-xs shadow-xs shrink-0">
                                👔
                            </div>
                            <div>
                                <h2 class="text-xs font-black uppercase tracking-wider text-gray-900">3. Showcase Your First Product</h2>
                                <p class="text-[11px] text-gray-500 font-medium">Add your first Barong Tagalog or artisan piece (You can add more anytime in your catalogue).</p>
                            </div>
                        </div>
                        <span class="text-[9.5px] font-extrabold text-amber-800 bg-amber-100/90 border border-amber-300/60 px-2.5 py-0.5 rounded-full uppercase tracking-wider shrink-0">Optional</span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        {{-- Product Name --}}
                        <div class="sm:col-span-2">
                            <label for="product_name" class="block text-[11px] font-bold text-gray-800 uppercase tracking-wider mb-1.5">
                                Product Title / Model
                            </label>
                            <input type="text" 
                                   id="product_name" 
                                   name="product_name" 
                                   x-model="productName"
                                   value="{{ old('product_name') }}" 
                                   placeholder="e.g. Traditional Piña Cocoon Barong Tagalog" 
                                   class="w-full h-12 px-4 bg-white border border-[#D8CEBE] rounded-xl text-xs font-semibold text-gray-900 outline-none focus:border-[#C0422A] focus:ring-2 focus:ring-[#C0422A]/15 transition-all shadow-xs">
                        </div>

                        {{-- Product Category --}}
                        <div>
                            <label for="product_category_id" class="block text-[11px] font-bold text-gray-800 uppercase tracking-wider mb-1.5">
                                Artisan Category
                            </label>
                            <div class="relative">
                                <select id="product_category_id" 
                                        name="product_category_id" 
                                        class="w-full h-12 px-4 bg-white border border-[#D8CEBE] rounded-xl text-xs font-semibold text-gray-900 outline-none focus:border-[#C0422A] focus:ring-2 focus:ring-[#C0422A]/15 transition-all shadow-xs appearance-none">
                                    <option value="">Select Category</option>
                                    @foreach ($categories as $cat)
                                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                    @endforeach
                                </select>
                                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3.5 text-gray-500">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                </div>
                            </div>
                        </div>

                        {{-- Product Price & Stock in Dual Columns --}}
                        <div class="grid grid-cols-2 gap-2.5">
                            <div>
                                <label for="product_price" class="block text-[11px] font-bold text-gray-800 uppercase tracking-wider mb-1.5">
                                    Price (₱)
                                </label>
                                <input type="number" 
                                       id="product_price" 
                                       name="product_price" 
                                       x-model="productPrice"
                                       step="0.01" 
                                       min="1" 
                                       placeholder="2500.00" 
                                       class="w-full h-12 px-3.5 bg-white border border-[#D8CEBE] rounded-xl text-xs font-semibold text-gray-900 outline-none focus:border-[#C0422A] focus:ring-2 focus:ring-[#C0422A]/15 transition-all shadow-xs">
                            </div>
                            <div>
                                <label for="product_stock" class="block text-[11px] font-bold text-gray-800 uppercase tracking-wider mb-1.5">
                                    Initial Stock
                                </label>
                                <input type="number" 
                                       id="product_stock" 
                                       name="product_stock" 
                                       min="0" 
                                       value="1" 
                                       class="w-full h-12 px-3.5 bg-white border border-[#D8CEBE] rounded-xl text-xs font-semibold text-gray-900 outline-none focus:border-[#C0422A] focus:ring-2 focus:ring-[#C0422A]/15 transition-all shadow-xs">
                            </div>
                        </div>

                        {{-- Product Description --}}
                        <div class="sm:col-span-2">
                            <label for="product_description" class="block text-[11px] font-bold text-gray-800 uppercase tracking-wider mb-1.5">
                                Artisan Description & Highlights
                            </label>
                            <textarea id="product_description" 
                                      name="product_description" 
                                      rows="2" 
                                      placeholder="Authentic handcrafted Lumban embroidery with premium hand-woven quality..." 
                                      class="w-full p-3.5 bg-white border border-[#D8CEBE] rounded-xl text-xs text-gray-900 outline-none focus:border-[#C0422A] focus:ring-2 focus:ring-[#C0422A]/15 transition-all shadow-xs leading-relaxed"></textarea>
                        </div>

                        {{-- Product Primary Photo --}}
                        <div class="sm:col-span-2">
                            <label class="block text-[11px] font-bold text-gray-800 uppercase tracking-wider mb-1.5">
                                Product Cover Photo
                            </label>
                            <div class="border-2 border-dashed border-[#D8CEBE] hover:border-[#C0422A] rounded-2xl p-5 bg-white hover:bg-[#FAF8F5] transition-all text-center cursor-pointer relative group shadow-xs">
                                <input type="file" 
                                       name="product_image" 
                                       accept="image/png,image/jpeg,image/webp" 
                                       @change="previewProduct($event)" 
                                       class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10">
                                
                                <template x-if="!productPreview">
                                    <div class="space-y-1.5 py-2">
                                        <div class="w-11 h-11 mx-auto rounded-2xl bg-[#FAF7F2] group-hover:bg-[#FAF0ED] flex items-center justify-center text-gray-500 group-hover:text-[#C0422A] border border-[#ECE3D2] transition-colors shadow-2xs">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                        </div>
                                        <div>
                                            <p class="text-xs font-bold text-gray-800 group-hover:text-[#C0422A] transition-colors">Click or drag product photo</p>
                                            <p class="text-[10.5px] text-gray-400 font-medium mt-0.5">JPG, PNG, or WEBP up to 5MB</p>
                                        </div>
                                    </div>
                                </template>

                                <template x-if="productPreview">
                                    <div class="py-1 space-y-2">
                                        <img :src="productPreview" alt="Product Photo" class="w-28 h-28 object-cover rounded-xl border border-gray-200 mx-auto shadow-md">
                                        <span class="inline-flex items-center gap-1.5 text-[10.5px] font-bold text-emerald-800 bg-emerald-50 px-3 py-0.5 rounded-full border border-emerald-200">
                                            ✓ Photo Attached (Click to change)
                                        </span>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Step 3 Footer Navigation (Complete Action) --}}
                <div class="flex items-center justify-between pt-2">
                    <button type="button" @click="currentStep = 2" class="h-12 px-6 rounded-xl border border-gray-300 hover:bg-gray-100 text-gray-700 text-xs font-bold transition-all cursor-pointer">
                        ← Back
                    </button>
                    <button type="submit" 
                            class="h-12 px-8 rounded-xl bg-gradient-to-r from-[#C0422A] via-[#9B2C16] to-[#7D1E0C] hover:from-[#A83520] hover:to-[#6C1708] text-white text-xs font-black uppercase tracking-wider shadow-[0_10px_25px_rgba(192,66,42,0.3)] hover:shadow-[0_14px_32px_rgba(192,66,42,0.4)] hover:-translate-y-0.5 active:translate-y-0 transition-all duration-200 flex items-center gap-2 cursor-pointer">
                        <span>Complete Setup & Open Shop</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
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
                gcashNumber: '{{ old("gcashNumber", $user->gcashNumber) }}',
                qrPreview: '{{ $user->gcashQrCode ? asset(ltrim($user->gcashQrCode, "/")) : "" }}',
                productName: '{{ old("product_name") }}',
                productPrice: '{{ old("product_price") }}',
                productPreview: '',
                refundPolicy: `{!! addslashes($user->refund_policy ?: "Items may be returned or replaced within 7 days of delivery if there is a verified defect in fabric or hand-embroidery. Items must be unwashed, unworn, and in original artisan packaging.") !!}`,
                cancellationPolicy: `{!! addslashes($user->cancellation_policy ?: "Orders may be cancelled within 24 hours of placement prior to production commencement. Customized or bespoke orders cannot be cancelled once cutting and embroidery begins.") !!}`,

                get hasGcash() {
                    return (this.gcashNumber && this.gcashNumber.trim().length >= 10) || !!this.qrPreview;
                },

                get hasPolicy() {
                    return this.refundPolicy && this.refundPolicy.trim().length > 10;
                },

                get hasProduct() {
                    return this.productName && this.productName.trim().length > 0;
                },

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
