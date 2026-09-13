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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Playfair+Display:wght@600;700;800&display=swap" rel="stylesheet">
    
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

    <!-- Main Setup Hub Container Card -->
    <div class="w-full max-w-2xl bg-white rounded-3xl sm:rounded-[2.5rem] border border-[#E5DDD5] p-6 sm:p-9 lg:p-10 shadow-[0_25px_70px_rgba(60,40,20,0.07)] relative z-10 my-4 sm:my-8 transition-all duration-300"
         x-data="{ isSkipping: false }"
         x-cloak>
        
        {{-- Top Bar Header --}}
        <div class="flex items-center justify-between pb-4 sm:pb-5 border-b border-[#EFE8DC] mb-6 sm:mb-8">
            <div class="flex items-center gap-2.5">
                <span class="w-2 h-2 rounded-full bg-[#C0422A]"></span>
                <span class="text-[10px] font-black uppercase tracking-[0.25em] text-gray-400">Artisan Onboarding</span>
            </div>
            
            <button type="button" 
                    @click="isSkipping = true; document.getElementById('seller-skip-form').submit()"
                    :disabled="isSkipping"
                    class="group inline-flex items-center gap-1.5 text-xs font-bold text-gray-500 hover:text-[#C0422A] transition-all py-1.5 px-3.5 rounded-full hover:bg-[#FAF7F2] border border-[#E8DFC8] hover:border-[#C0422A]/30 cursor-pointer shadow-2xs">
                <span x-show="!isSkipping" class="inline-flex items-center gap-1.5">
                    <span>Skip setup for now</span>
                    <svg class="w-3.5 h-3.5 transition-transform group-hover:translate-x-0.5 text-gray-400 group-hover:text-[#C0422A]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                </span>
                <span x-show="isSkipping" class="inline-flex items-center gap-1.5 text-[#C0422A]" style="display: none;">
                    <svg class="animate-spin w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                    <span>Opening Dashboard...</span>
                </span>
            </button>
        </div>

        {{-- Branding & Greeting Header --}}
        <div class="text-center mb-6 sm:mb-8">
            <div class="flex justify-center mb-3">
                <div class="w-14 h-14 sm:w-16 sm:h-16 rounded-full p-0.5 bg-[linear-gradient(to_top_right,#C0422A,#D4B896)] shadow-sm">
                    <div class="w-full h-full rounded-full bg-white p-1 flex items-center justify-center">
                        <img src="{{ asset('images/logo-icon.png') }}" alt="LumBarong Logo" class="w-full h-full object-contain rounded-full">
                    </div>
                </div>
            </div>
            
            <div class="inline-flex items-center gap-2 px-3.5 py-1 rounded-full bg-[#FAF0ED] text-[#C0422A] text-[10px] font-black uppercase tracking-[0.2em] mb-2.5 border border-[#C0422A]/20 shadow-2xs">
                <span class="w-1.5 h-1.5 rounded-full bg-[#C0422A]"></span>
                <span>Welcome, {{ $user->shopName ?: $user->name }}</span>
            </div>
            
            <h1 class="font-serif text-2xl sm:text-3xl lg:text-[2.1rem] font-bold tracking-tight text-[#1E1915] mb-2 leading-tight">
                Artisan Store Setup Hub
            </h1>
            <p class="text-xs sm:text-[13px] text-gray-600 font-medium max-w-md mx-auto leading-relaxed">
                Prepare your shop to accept customer orders. Configure your payout account, return guidelines, and list your first handcrafted Barong.
            </p>
        </div>

        {{-- 3-Item Direct Action Setup Cards --}}
        <div class="space-y-4 mb-8">
            
            {{-- Card 1: Payment Method (GCash & Maya) Setup --}}
            <div class="p-5 sm:p-6 rounded-2xl bg-[#FAF8F5] border border-[#ECE3D2] hover:border-[#C0422A]/50 transition-all duration-200 shadow-2xs group flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-start gap-4">
                    <div class="w-11 h-11 rounded-2xl bg-[linear-gradient(to_bottom_right,#1d4ed8,#0ea5e9)] flex items-center justify-center text-white shadow-sm shrink-0 mt-0.5">
                        {{-- Wallet icon --}}
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M21 12V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2h14a2 2 0 002-2v-2"/><path stroke-linecap="round" stroke-linejoin="round" d="M16 12a2 2 0 000 4h5v-4h-5z"/></svg>
                    </div>
                    <div class="space-y-1">
                        <div class="flex items-center gap-2">
                            <h2 class="text-sm font-bold text-gray-900">1. Payment Method <span class="font-medium text-gray-500">(GCash & Maya)</span></h2>
                            @if($hasGcash || $hasMaya)
                                <span class="text-[9.5px] font-extrabold text-emerald-800 bg-emerald-50 border border-emerald-200 px-2 py-0.5 rounded-full">✓ Configured</span>
                            @else
                                <span class="text-[9.5px] font-extrabold text-amber-800 bg-amber-50 border border-amber-200 px-2 py-0.5 rounded-full">Pending Setup</span>
                            @endif
                        </div>
                        <p class="text-[11.5px] text-gray-600 leading-relaxed max-w-md">
                            Link your GCash or Maya account so customer payments are routed directly to you.
                        </p>
                    </div>
                </div>
                <div class="shrink-0 sm:pl-2">
                    <a href="{{ route('seller.profile') }}?open_payment=1" 
                       class="inline-flex items-center justify-center gap-2 h-11 px-5 rounded-full bg-white hover:bg-[#3D2B1F] text-[#3D2B1F] hover:text-white border border-[#D8CEBE] hover:border-[#3D2B1F] text-xs font-bold transition-all duration-200 shadow-2xs group-hover:shadow-xs w-full sm:w-auto cursor-pointer">
                        <span>{{ ($hasGcash || $hasMaya) ? 'Manage Payout' : 'Add Payment Method' }}</span>
                        <svg class="w-3.5 h-3.5 text-gray-400 group-hover:text-white transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                    </a>
                </div>
            </div>

            {{-- Card 2: Store Policies & Terms --}}
            <div class="p-5 sm:p-6 rounded-2xl bg-[#FAF8F5] border border-[#ECE3D2] hover:border-[#C0422A]/50 transition-all duration-200 shadow-2xs group flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-start gap-4">
                    <div class="w-11 h-11 rounded-2xl bg-[linear-gradient(to_bottom_right,#92400e,#f59e0b)] flex items-center justify-center text-white shadow-sm shrink-0 mt-0.5">
                        {{-- Shield check icon --}}
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                    </div>
                    <div class="space-y-1">
                        <div class="flex items-center gap-2">
                            <h2 class="text-sm font-bold text-gray-900">2. Shop Return & Cancellation Policies</h2>
                            @if($hasPolicies)
                                <span class="text-[9.5px] font-extrabold text-emerald-800 bg-emerald-50 border border-emerald-200 px-2 py-0.5 rounded-full">✓ Custom Terms Set</span>
                            @else
                                <span class="text-[9.5px] font-extrabold text-gray-700 bg-gray-100 border border-gray-300/80 px-2 py-0.5 rounded-full">Standard Lumban Terms</span>
                            @endif
                        </div>
                        <p class="text-[11.5px] text-gray-600 leading-relaxed max-w-md">
                            Protect your craft with clear customer return, replacement, and custom tailoring cancellation guidelines.
                        </p>
                    </div>
                </div>
                <div class="shrink-0 sm:pl-2">
                    <a href="{{ route('seller.policies.index') }}" 
                       class="inline-flex items-center justify-center gap-2 h-11 px-5 rounded-full bg-white hover:bg-[#3D2B1F] text-[#3D2B1F] hover:text-white border border-[#D8CEBE] hover:border-[#3D2B1F] text-xs font-bold transition-all duration-200 shadow-2xs group-hover:shadow-xs w-full sm:w-auto cursor-pointer">
                        <span>{{ $hasPolicies ? 'Edit Policies' : 'Policy Studio' }}</span>
                        <svg class="w-3.5 h-3.5 text-gray-400 group-hover:text-white transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                    </a>
                </div>
            </div>

            {{-- Card 3: First Product Catalogue Item --}}
            <div class="p-5 sm:p-6 rounded-2xl bg-[#FAF8F5] border border-[#ECE3D2] hover:border-[#C0422A]/50 transition-all duration-200 shadow-2xs group flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-start gap-4">
                    <div class="w-11 h-11 rounded-2xl bg-[linear-gradient(to_bottom_right,#9f1239,#C0422A)] flex items-center justify-center text-white shadow-sm shrink-0 mt-0.5">
                        {{-- Price tag / product icon --}}
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M3 3h7.5a1.5 1.5 0 011.06.44l8.5 8.5a1.5 1.5 0 010 2.12l-5.5 5.5a1.5 1.5 0 01-2.12 0l-8.5-8.5A1.5 1.5 0 013 10.5V3z"/></svg>
                    </div>
                    <div class="space-y-1">
                        <div class="flex items-center gap-2">
                            <h2 class="text-sm font-bold text-gray-900">3. Publish Your First Barong</h2>
                            @if($productsCount > 0)
                                <span class="text-[9.5px] font-extrabold text-emerald-800 bg-emerald-50 border border-emerald-200 px-2 py-0.5 rounded-full">✓ {{ $productsCount }} Listed</span>
                            @else
                                <span class="text-[9.5px] font-extrabold text-amber-800 bg-amber-50 border border-amber-200 px-2 py-0.5 rounded-full">0 Published</span>
                            @endif
                        </div>
                        <p class="text-[11.5px] text-gray-600 leading-relaxed max-w-md">
                            List your handcrafted Barong Tagalog with high-resolution embroidery closeups, custom sizing, and inventory.
                        </p>
                    </div>
                </div>
                <div class="shrink-0 sm:pl-2">
                    <a href="{{ route('seller.products.create') }}" 
                       class="inline-flex items-center justify-center gap-2 h-11 px-5 rounded-full bg-[#3D2B1F] hover:bg-[#C0422A] text-white text-xs font-bold transition-all duration-200 shadow-md hover:shadow-lg w-full sm:w-auto cursor-pointer">
                        <span>Add Product</span>
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                    </a>
                </div>
            </div>

        </div>

        {{-- Primary Action Deck --}}
        <div class="pt-4 border-t border-[#ECE3D2] text-center space-y-3">
            <button type="button" 
                    @click="isSkipping = true; document.getElementById('seller-skip-form').submit()"
                    :disabled="isSkipping"
                    class="w-full h-14 bg-[#3D2B1F] hover:bg-[#C0422A] text-white font-bold text-xs tracking-wider rounded-full shadow-lg hover:shadow-xl hover:-translate-y-0.5 active:translate-y-0 transition-all duration-200 flex items-center justify-center gap-2 cursor-pointer">
                <span x-show="!isSkipping" class="inline-flex items-center gap-2">
                    <span>Continue to Shop Dashboard</span>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                </span>
                <span x-show="isSkipping" class="inline-flex items-center gap-2" style="display: none;">
                    <svg class="animate-spin w-4 h-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                    <span>Opening Dashboard...</span>
                </span>
            </button>
            <p class="text-[11px] text-gray-500 font-medium">
                You can configure or update your payout, policies, and products anytime from your artisan control panel.
            </p>
        </div>

    </div>
</body>
</html>
