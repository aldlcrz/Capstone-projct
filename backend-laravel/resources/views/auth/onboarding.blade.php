<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>Complete Your Profile | LumBarong</title>
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('images/apple-touch-icon.png') }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Playfair+Display:wght@700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background: #F7F3EE; }
        .font-serif { font-family: 'Playfair Display', serif; }
        [x-cloak] { display: none !important; }
    </style>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="min-h-screen flex items-center justify-center p-4 sm:p-6 relative overflow-x-hidden">
    <!-- Subtle warm background gradients -->
    <div class="absolute top-0 right-0 w-96 sm:w-140 h-96 sm:h-140 rounded-full -translate-y-1/2 translate-x-1/3 blur-3xl opacity-[0.05] pointer-events-none bg-[#C0422A]"></div>
    <div class="absolute bottom-0 left-0 w-72 sm:w-96 h-72 sm:h-96 rounded-full translate-y-1/2 -translate-x-1/3 blur-3xl opacity-[0.10] pointer-events-none bg-[#D4B896]"></div>

    <div class="w-full max-w-xl bg-white rounded-4xl sm:rounded-[2.5rem] border border-[#E5DDD5] p-6 sm:p-10 shadow-[0_20px_60px_rgba(60,40,20,0.06)] relative z-10 my-4 sm:my-8" x-data="{ isSubmitting: false }">
        
        {{-- Header & Progress --}}
        <div class="text-center mb-8">
            <div class="flex justify-center mb-3">
                <div class="w-14 h-14 rounded-2xl bg-[#C0422A]/10 flex items-center justify-center text-[#C0422A] shadow-xs">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                </div>
            </div>
            <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-[#C0422A]/10 text-[#C0422A] text-[10px] font-bold uppercase tracking-wider mb-2">
                <span>Welcome to LumBarong</span>
            </div>
            <h1 class="font-serif text-2xl sm:text-3xl font-black italic tracking-tight text-gray-900 mb-1.5">
                Set Up Your Profile
            </h1>
            <p class="text-xs text-gray-500 font-medium max-w-md mx-auto leading-relaxed">
                Add your details to expedite checkout and personalize your authentic Lumban artisan experience. You can also skip and update these anytime.
            </p>
        </div>

        {{-- Error Summary Alert --}}
        @if ($errors->any())
            <div class="mb-6 p-4 rounded-2xl bg-red-50 border border-red-200 text-red-900 text-xs">
                <div class="flex items-center gap-2 font-bold mb-1">
                    <svg class="w-4 h-4 text-red-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span>Please review the highlighted details:</span>
                </div>
                <ul class="list-disc list-inside space-y-0.5 text-red-700">
                    @foreach ($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Main Setup Form --}}
        <form action="{{ route('onboarding.save') }}" method="POST" @submit="isSubmitting = true" class="space-y-6">
            @csrf

            {{-- 1. Identity Information --}}
            <div class="space-y-4">
                <div class="flex items-center gap-2 border-b border-gray-100 pb-2">
                    <span class="w-5 h-5 rounded-full bg-[#C0422A] text-white flex items-center justify-center text-[10px] font-bold">1</span>
                    <h2 class="text-xs font-bold uppercase tracking-wider text-gray-800">Basic Information</h2>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    {{-- Full Name --}}
                    <div>
                        <label for="name" class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">
                            Full Name <span class="text-[#C0422A]">*</span>
                        </label>
                        <input type="text" 
                               id="name" 
                               name="name" 
                               value="{{ old('name', Auth::user()->name) }}" 
                               required
                               placeholder="e.g. Maria Santos" 
                               class="w-full h-11 px-3.5 bg-gray-50/80 border {{ $errors->has('name') ? 'border-red-400 bg-red-50/50' : 'border-gray-200' }} rounded-xl text-xs font-semibold text-gray-900 outline-none focus:border-[#C0422A] focus:bg-white focus:ring-2 focus:ring-[#C0422A]/10 transition-all">
                    </div>

                    {{-- Preferred Username --}}
                    <div>
                        <label for="username" class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">
                            Preferred Username <span class="text-[#C0422A]">*</span>
                        </label>
                        <input type="text" 
                               id="username" 
                               name="username" 
                               value="{{ old('username', Auth::user()->username) }}" 
                               required
                               placeholder="e.g. mariasantos" 
                               class="w-full h-11 px-3.5 bg-gray-50/80 border {{ $errors->has('username') ? 'border-red-400 bg-red-50/50' : 'border-gray-200' }} rounded-xl text-xs font-semibold text-gray-900 outline-none focus:border-[#C0422A] focus:bg-white focus:ring-2 focus:ring-[#C0422A]/10 transition-all">
                    </div>

                    {{-- Phone Number --}}
                    <div class="sm:col-span-2">
                        <label for="mobileNumber" class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">
                            Mobile Phone Number <span class="text-gray-400 font-normal text-[10px] lowercase">(e.g. 09171234567)</span>
                        </label>
                        <div class="relative">
                            <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-xs font-bold">🇵🇭 +63</span>
                            <input type="text" 
                                   id="mobileNumber" 
                                   name="mobileNumber" 
                                   value="{{ old('mobileNumber', Auth::user()->mobileNumber) }}" 
                                   placeholder="9171234567 or 09171234567" 
                                   class="w-full h-11 pl-20 pr-3.5 bg-gray-50/80 border {{ $errors->has('mobileNumber') ? 'border-red-400 bg-red-50/50' : 'border-gray-200' }} rounded-xl text-xs font-semibold text-gray-900 outline-none focus:border-[#C0422A] focus:bg-white focus:ring-2 focus:ring-[#C0422A]/10 transition-all">
                        </div>
                    </div>
                </div>
            </div>

            {{-- 2. Delivery Address Setup --}}
            <div class="space-y-4 pt-2">
                <div class="flex items-center justify-between border-b border-gray-100 pb-2">
                    <div class="flex items-center gap-2">
                        <span class="w-5 h-5 rounded-full bg-[#C0422A] text-white flex items-center justify-center text-[10px] font-bold">2</span>
                        <h2 class="text-xs font-bold uppercase tracking-wider text-gray-800">Primary Delivery Address</h2>
                    </div>
                    <span class="text-[10px] font-medium text-gray-400">Optional for now</span>
                </div>

                <div class="space-y-3">
                    {{-- House No & Street --}}
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div class="sm:col-span-1">
                            <label for="houseNo" class="block text-[11px] font-bold text-gray-600 mb-1">House / Unit No.</label>
                            <input type="text" 
                                   id="houseNo" 
                                   name="houseNo" 
                                   value="{{ old('houseNo') }}" 
                                   placeholder="e.g. Lot 4 Block 2" 
                                   class="w-full h-10 px-3 bg-gray-50/80 border border-gray-200 rounded-xl text-xs font-medium text-gray-900 outline-none focus:border-[#C0422A] focus:bg-white transition-all">
                        </div>
                        <div class="sm:col-span-2">
                            <label for="street" class="block text-[11px] font-bold text-gray-600 mb-1">Street / Subdivision</label>
                            <input type="text" 
                                   id="street" 
                                   name="street" 
                                   value="{{ old('street') }}" 
                                   placeholder="e.g. Rizal Street, Heritage Village" 
                                   class="w-full h-10 px-3 bg-gray-50/80 border border-gray-200 rounded-xl text-xs font-medium text-gray-900 outline-none focus:border-[#C0422A] focus:bg-white transition-all">
                        </div>
                    </div>

                    {{-- Barangay & City --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label for="barangay" class="block text-[11px] font-bold text-gray-600 mb-1">Barangay</label>
                            <input type="text" 
                                   id="barangay" 
                                   name="barangay" 
                                   value="{{ old('barangay') }}" 
                                   placeholder="e.g. Barangay Primera" 
                                   class="w-full h-10 px-3 bg-gray-50/80 border border-gray-200 rounded-xl text-xs font-medium text-gray-900 outline-none focus:border-[#C0422A] focus:bg-white transition-all">
                        </div>
                        <div>
                            <label for="city" class="block text-[11px] font-bold text-gray-600 mb-1">City / Municipality</label>
                            <input type="text" 
                                   id="city" 
                                   name="city" 
                                   value="{{ old('city') }}" 
                                   placeholder="e.g. Lumban" 
                                   class="w-full h-10 px-3 bg-gray-50/80 border border-gray-200 rounded-xl text-xs font-medium text-gray-900 outline-none focus:border-[#C0422A] focus:bg-white transition-all">
                        </div>
                    </div>

                    {{-- Province & Postal Code --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label for="province" class="block text-[11px] font-bold text-gray-600 mb-1">Province</label>
                            <input type="text" 
                                   id="province" 
                                   name="province" 
                                   value="{{ old('province') }}" 
                                   placeholder="e.g. Laguna" 
                                   class="w-full h-10 px-3 bg-gray-50/80 border border-gray-200 rounded-xl text-xs font-medium text-gray-900 outline-none focus:border-[#C0422A] focus:bg-white transition-all">
                        </div>
                        <div>
                            <label for="postalCode" class="block text-[11px] font-bold text-gray-600 mb-1">Postal Code</label>
                            <input type="text" 
                                   id="postalCode" 
                                   name="postalCode" 
                                   value="{{ old('postalCode') }}" 
                                   placeholder="e.g. 4014" 
                                   class="w-full h-10 px-3 bg-gray-50/80 border border-gray-200 rounded-xl text-xs font-medium text-gray-900 outline-none focus:border-[#C0422A] focus:bg-white transition-all">
                        </div>
                    </div>
                </div>
            </div>

            {{-- Action Buttons (Save vs Skip for Now) --}}
            <div class="pt-4 space-y-3">
                <button type="submit" 
                        :disabled="isSubmitting"
                        class="w-full h-12 bg-[#C0422A] hover:bg-[#a83620] text-white rounded-xl font-bold uppercase tracking-wider text-xs transition-all shadow-md flex items-center justify-center gap-2 cursor-pointer disabled:opacity-60">
                    <span x-show="!isSubmitting">Save & Continue to LumBarong</span>
                    <span x-show="isSubmitting" x-cloak class="flex items-center gap-2">
                        <svg class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        Saving Details...
                    </span>
                </button>
            </div>
        </form>

        {{-- Skip for Now Form (Secondary subtle action) --}}
        <form action="{{ route('onboarding.skip') }}" method="POST" class="mt-3 text-center">
            @csrf
            <button type="submit" 
                    class="text-xs font-bold text-gray-400 hover:text-gray-700 transition-colors py-2 px-4 rounded-lg hover:bg-gray-100 inline-flex items-center gap-1.5 cursor-pointer">
                <span>Skip for Now</span>
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </button>
        </form>

        <p class="text-center text-[10px] text-gray-400 mt-4">
            🔒 Your personal information is encrypted and never shared with third parties.
        </p>
    </div>
</body>
</html>
