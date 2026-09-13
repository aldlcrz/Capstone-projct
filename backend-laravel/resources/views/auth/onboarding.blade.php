<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>Complete Your Profile | LumBarong</title>
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('images/apple-touch-icon.png') }}">
    
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

        input::-ms-reveal,
        input::-ms-clear {
            display: none !important;
        }
    </style>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>
</head>
<body class="min-h-screen flex items-center justify-center p-3 sm:p-6 relative overflow-x-hidden" id="auth-body">
    <!-- Subtle warm background gradients -->
    <div class="absolute top-0 right-0 w-80 sm:w-140 h-80 sm:h-140 rounded-full -translate-y-1/2 translate-x-1/3 blur-3xl opacity-[0.06] pointer-events-none bg-[#C0422A]"></div>
    <div class="absolute bottom-0 left-0 w-72 sm:w-110 h-72 sm:h-110 rounded-full translate-y-1/2 -translate-x-1/3 blur-3xl opacity-[0.12] pointer-events-none bg-[#D4B896]"></div>

    <div class="w-full max-w-xl bg-white rounded-3xl sm:rounded-[2.5rem] border border-[#E5DDD5] p-4 sm:p-7 shadow-[0_20px_60px_rgba(60,40,20,0.08)] relative z-10 my-2 sm:my-6 transition-all duration-300" 
         x-data="onboardingSetup()">
        
        {{-- Top Status --}}
        <div class="flex items-center justify-end mb-2 sm:mb-3">
            <div class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">
                Account Setup
            </div>
        </div>

        {{-- Header & Branding --}}
        <div class="text-center mb-5 sm:mb-7">
            <div class="flex justify-center mb-2.5">
                <div class="w-14 h-14 sm:w-16 sm:h-16 rounded-full overflow-hidden shadow-md border-2 border-[#E8DFC8] bg-white p-0.5 shrink-0 flex items-center justify-center">
                    <img src="{{ asset('images/logo-icon.png') }}" alt="LumBarong Logo" class="w-full h-full object-contain rounded-full">
                </div>
            </div>
            <div class="inline-flex items-center gap-1.5 px-3 py-0.5 rounded-full bg-[#C0422A]/10 text-[#C0422A] text-[10px] font-extrabold uppercase tracking-wider mb-1.5 border border-[#C0422A]/20">
                <span>Welcome to LumBarong</span>
            </div>
            <h1 class="font-serif text-2xl sm:text-3xl font-black italic tracking-tight text-gray-900 mb-1">
                Set Up Your Profile
            </h1>
            <p class="text-xs text-gray-600 font-medium max-w-md mx-auto leading-relaxed px-1">
                Add your details to personalize your authentic Lumban artisan experience and expedite delivery checkout. You can also skip and update these anytime.
            </p>
        </div>

        {{-- Error Summary Alert --}}
        @if ($errors->any())
            <div class="mb-4 p-3.5 rounded-2xl bg-red-50 border border-red-200 text-red-900 text-xs shadow-2xs animate-fade-in">
                <div class="flex items-center gap-2 font-bold mb-1">
                    <svg class="w-4 h-4 text-red-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span>Please review the highlighted details:</span>
                </div>
                <ul class="list-disc list-inside space-y-0.5 text-red-700 font-medium">
                    @foreach ($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Main Setup Form --}}
        <form action="{{ route('onboarding.save') }}" method="POST" @submit="isSubmitting = true" class="space-y-4 sm:space-y-5">
            @csrf

            {{-- 1. BASIC INFORMATION SECTION --}}
            <div class="bg-[#FAF8F5] border border-[#ECE3D2] rounded-2xl sm:rounded-3xl p-3.5 sm:p-5 space-y-3.5">
                <div class="flex items-center justify-between border-b border-[#ECE3D2] pb-2">
                    <h2 class="text-xs font-black uppercase tracking-wider text-[#1E1915]">Basic Information</h2>
                    <span class="text-[9.5px] font-extrabold text-[#C0422A] bg-[#C0422A]/10 border border-[#C0422A]/20 px-2 py-0.5 rounded-full uppercase tracking-wider">Required</span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    {{-- Full Name --}}
                    <div class="sm:col-span-2">
                        <label for="name" class="block text-[11px] font-bold text-gray-800 uppercase tracking-wider mb-1">
                            Full Name <span class="text-[#C0422A]">*</span>
                        </label>
                        <input type="text" 
                               id="name" 
                               name="name" 
                               value="{{ old('name', Auth::user()->name) }}" 
                               required
                               placeholder="e.g. Maria Santos" 
                               class="w-full h-11 px-3.5 bg-white border {{ $errors->has('name') ? 'border-red-400 bg-red-50/50' : 'border-[#D8CEBE]' }} rounded-xl text-xs font-semibold text-gray-900 outline-none focus:border-[#C0422A] focus:ring-2 focus:ring-[#C0422A]/15 transition-all shadow-2xs">
                    </div>

                    {{-- Phone Number --}}
                    <div class="sm:col-span-2">
                        <label for="mobileNumber" class="block text-[11px] font-bold text-gray-800 uppercase tracking-wider mb-1">
                            Mobile Phone Number
                        </label>
                        <div class="flex items-center rounded-xl bg-white border {{ $errors->has('mobileNumber') ? 'border-red-400 bg-red-50/50' : 'border-[#D8CEBE]' }} overflow-hidden focus-within:border-[#C0422A] focus-within:ring-2 focus-within:ring-[#C0422A]/15 transition-all shadow-2xs h-11">
                            <div class="flex items-center justify-center px-3.5 bg-[#FAF7F2] border-r border-[#D8CEBE] text-xs font-bold text-gray-700 select-none shrink-0 h-full">
                                <span>+63</span>
                            </div>
                            <input type="text" 
                                   id="mobileNumber" 
                                   name="mobileNumber" 
                                   value="{{ old('mobileNumber', Auth::user()->mobileNumber) }}" 
                                   placeholder="9123456789" 
                                   class="w-full h-full px-3.5 bg-transparent text-xs font-semibold text-gray-900 outline-none placeholder:text-gray-400">
                        </div>
                    </div>
                </div>
            </div>

            {{-- 2. PRIMARY DELIVERY ADDRESS SECTION --}}
            <div class="bg-[#FAF8F5] border border-[#ECE3D2] rounded-2xl sm:rounded-3xl p-3.5 sm:p-5 space-y-3.5">
                <div class="flex items-center justify-between border-b border-[#ECE3D2] pb-2">
                    <h2 class="text-xs font-black uppercase tracking-wider text-[#1E1915]">Primary Delivery Address</h2>
                    <span class="text-[9.5px] font-extrabold text-amber-800 bg-amber-100/80 border border-amber-300/50 px-2 py-0.5 rounded-full uppercase tracking-wider">Optional</span>
                </div>

                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 p-3.5 rounded-xl bg-white border border-[#E8DFC8]/80 shadow-2xs">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-[#FAF5EA] border border-[#E6D8BA] flex items-center justify-center shrink-0 text-[#C0422A]">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        </div>
                        <div>
                            <p class="text-xs font-bold text-gray-900">No delivery address saved yet</p>
                            <p class="text-[11px] text-gray-500">You can add your complete address now or save and manage it anytime in your profile.</p>
                        </div>
                    </div>
                    <button type="submit" 
                            name="action" 
                            value="add_address"
                            class="w-full sm:w-auto px-4 py-2 rounded-xl bg-white hover:bg-[#FAF5EA] border border-[#D8CEBE] hover:border-[#C0422A]/50 text-[#C0422A] font-bold text-xs tracking-wide transition-all shadow-2xs inline-flex items-center justify-center gap-1.5 shrink-0 cursor-pointer">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                        <span>Add Address</span>
                    </button>
                </div>
            </div>

            {{-- Submit Action Button --}}
            <div class="pt-1.5">
                <button type="submit" 
                        :disabled="isSubmitting"
                        style="background: linear-gradient(to right, #C0422A, #B83D26, #A3341E);"
                        class="w-full h-12 sm:h-13 hover:opacity-95 text-white rounded-xl sm:rounded-2xl font-black uppercase tracking-wider text-xs sm:text-sm transition-all shadow-md shadow-[#C0422A]/20 hover:shadow-lg hover:shadow-[#C0422A]/30 active:scale-[0.99] flex items-center justify-center gap-2 cursor-pointer disabled:opacity-60">
                    <span x-show="!isSubmitting" class="flex items-center gap-2">
                        <span>Save & Continue to LumBarong</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                    </span>
                    <span x-show="isSubmitting" x-cloak class="flex items-center gap-2">
                        <svg class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        Saving Details...
                    </span>
                </button>
            </div>
        </form>

        {{-- Skip for Now Form (Secondary Action) --}}
        <form action="{{ route('onboarding.skip') }}" method="POST" class="mt-2.5 text-center">
            @csrf
            <button type="submit" 
                    class="text-xs font-bold text-gray-400 hover:text-gray-700 transition-colors py-1.5 px-3.5 rounded-xl hover:bg-gray-100 inline-flex items-center gap-1.5 cursor-pointer">
                <span>Skip for Now</span>
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </button>
        </form>

        {{-- Trust & Privacy Footer --}}
        <p class="text-center text-[10px] text-gray-400 mt-3 leading-relaxed">
            🔒 Your personal information is encrypted & never shared with third parties.
        </p>
    </div>

    {{-- Alpine State Script --}}
    <script>
        function onboardingSetup() {
            return {
                isSubmitting: false
            };
        }
    </script>
</body>
</html>
