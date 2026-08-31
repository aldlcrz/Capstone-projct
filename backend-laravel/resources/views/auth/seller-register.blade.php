<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>Seller Registration | LumBarong</title>
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('images/apple-touch-icon.png') }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Playfair+Display:ital,wght@0,700;0,800;0,900;1,700;1,800;1,900&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background: #F7F3EE; }
        .font-serif { font-family: 'Playfair Display', serif; }
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
        [x-cloak] { display: none !important; }

        .step-indicator {
            width: 38px;
            height: 38px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            font-size: 13px;
            font-weight: 700;
            transition: all 0.3s ease;
        }
        .step-active { background: #C0422A; color: white; box-shadow: 0 10px 25px rgba(192, 66, 42, 0.25); }
        .step-inactive { background: #F9F7F4; color: #D1D5DB; }
        
        .upload-card {
            background: #F9F6F2;
            border-radius: 20px;
            padding: 18px 12px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            border: 2px dashed #E5DDD5;
            transition: all 0.3s ease;
            cursor: pointer;
            min-height: 120px;
        }
        .upload-card:hover { border-color: #C0422A; background: #FDFBFA; }
        .upload-card.has-file { border-color: #10B981; border-style: solid; background: #F0FDF4; }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-6 relative overflow-hidden">
    <!-- Subtle warm blobs identical to customer login -->
    <div class="absolute top-0 right-0 w-140 h-140 rounded-full -translate-y-1/2 translate-x-1/3 blur-3xl opacity-[0.04] pointer-events-none bg-[#C0422A]"></div>
    <div class="absolute bottom-0 left-0 w-95 h-95 rounded-full translate-y-1/2 -translate-x-1/3 blur-3xl opacity-[0.12] pointer-events-none bg-[#D4B896]"></div>

    <div class="login-card w-full max-w-md bg-white rounded-[2.5rem] border border-[#E5DDD5] p-8 shadow-[0_20px_60px_rgba(60,40,20,0.08)] relative z-10 max-h-[95vh] overflow-y-auto no-scrollbar" 
         x-data="{
             step: {{ $errors->has('mobileNumber') || $errors->has('residencyCertificate') || $errors->has('businessPermit') || $errors->has('birDocument') || $errors->has('terms_consent') ? 2 : 1 }},
             name: '{{ old('name', $googleSeller['name'] ?? '') }}',
             email: '{{ old('email', $googleSeller['email'] ?? '') }}',
             password: '',
             password_confirmation: '',
             showPass: false,
             showConfirm: false,
             errors: {},
             validateStep1() {
                 this.errors = {};
                 const nameVal = (this.name || '').trim();
                 const emailVal = (this.email || '').trim();
                 const passVal = this.password || '';
                 const passConfVal = this.password_confirmation || '';

                 if (!nameVal) {
                     this.errors.name = 'Full Name is required to proceed.';
                 }

                 if (!emailVal) {
                     this.errors.email = 'Email address is required.';
                 } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailVal)) {
                     this.errors.email = 'Please enter a valid email address.';
                 }

                 if (!passVal) {
                     this.errors.password = 'Platform password is required.';
                 } else if (passVal.length < 6) {
                     this.errors.password = 'Password must be at least 6 characters.';
                 }

                 if (!passConfVal) {
                     this.errors.password_confirmation = 'Please confirm your password.';
                 } else if (passVal !== passConfVal) {
                     this.errors.password_confirmation = 'Passwords do not match.';
                 }

                 if (Object.keys(this.errors).length === 0) {
                     this.step = 2;
                 }
             }
         }" x-cloak>
        
        <!-- Header -->
        <div class="relative mb-6 text-center">
            <!-- Back Button -->
            <button type="button" x-show="step > 1" @click="step--" class="back-btn absolute left-0 top-1/2 -translate-y-1/2 w-10 h-10 rounded-full bg-[#F9F6F2] flex items-center justify-center hover:bg-gray-100 text-gray-600 hover:text-[#C0422A] transition-all shadow-sm cursor-pointer" title="Previous Step">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
            </button>
            <button type="button" x-show="step === 1" @click="window.history.back()" class="back-btn absolute left-0 top-1/2 -translate-y-1/2 w-10 h-10 rounded-full bg-[#F9F6F2] flex items-center justify-center hover:bg-gray-100 text-gray-600 hover:text-[#C0422A] transition-all shadow-sm cursor-pointer" title="Back">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
            </button>

            <div class="flex justify-center mb-3">
                <img src="{{ asset('images/logo-icon.png') }}" alt="LumBarong Logo" class="w-14 h-14 object-contain rounded-full shadow-md hover:scale-105 transition-transform">
            </div>
            <h1 class="font-serif text-2xl font-black tracking-tight text-gray-900 mb-1">LumBarong</h1>
            <p class="text-[11px] font-bold uppercase tracking-[0.3em] text-[#C0422A]">Seller Registration</p>
        </div>

        <!-- Step Indicator -->
        <div class="flex items-center justify-center gap-2 mb-8">
            <div class="step-indicator" :class="step === 1 ? 'step-active' : (step > 1 ? 'bg-emerald-600 text-white' : 'step-inactive')">
                <span x-show="step > 1">✓</span>
                <span x-show="step <= 1">1</span>
            </div>
            <div class="h-px w-24 bg-[#E5DDD5]"></div>
            <div class="step-indicator" :class="step === 2 ? 'step-active' : 'step-inactive'">2</div>
        </div>

        {{-- Alerts --}}
        @if (session('info'))
            <div class="mb-6 p-4 rounded-2xl bg-amber-50 border border-amber-200 text-amber-900 text-xs flex items-start gap-2.5">
                <span class="text-base shrink-0">ℹ️</span>
                <p class="font-medium leading-relaxed">{{ session('info') }}</p>
            </div>
        @endif
        @if (session('success'))
            <div class="mb-6 p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-900 text-xs flex items-start gap-2.5">
                <span class="text-base shrink-0">✓</span>
                <p class="font-medium leading-relaxed">{{ session('success') }}</p>
            </div>
        @endif
        @if (session('warning'))
            <div class="mb-6 p-4 rounded-2xl bg-amber-50 border border-amber-200 text-amber-900 text-xs flex items-start gap-2.5">
                <span class="text-base shrink-0">⚠️</span>
                <p class="font-medium leading-relaxed">{{ session('warning') }}</p>
            </div>
        @endif

        @php
            $googleSeller = session('google_seller_signup');
        @endphp

        @if($googleSeller)
            <div class="mb-6 p-4 rounded-2xl bg-blue-50 border border-blue-200 text-blue-900 text-xs flex items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    @if(!empty($googleSeller['picture']))
                        <img src="{{ $googleSeller['picture'] }}" class="w-8 h-8 rounded-full border border-blue-200 shrink-0">
                    @else
                        <span class="text-lg">🇬</span>
                    @endif
                    <div class="min-w-0">
                        <div class="font-bold text-[11px]">Connected with Google</div>
                        <div class="text-[10px] text-blue-700 truncate">{{ $googleSeller['email'] }}</div>
                    </div>
                </div>
                <span class="text-[9px] font-black uppercase tracking-wider bg-blue-200/60 text-blue-800 px-2 py-1 rounded-md shrink-0">Verified</span>
            </div>
        @endif

        <form action="{{ route('seller.register.submit') }}" method="POST" enctype="multipart/form-data">
            @csrf
            
            <!-- STEP 1 -->
            <div x-show="step === 1" class="space-y-5">
                
                {{-- Registry Name --}}
                <div class="space-y-2">
                    <label class="text-[10px] font-bold uppercase tracking-widest px-5 block text-gray-500">Registry Name</label>
                    <input 
                        type="text" 
                        name="name" 
                        x-model="name" 
                        @input="delete errors.name" 
                        required 
                        placeholder="Your Full Name"
                        class="w-full h-14 bg-[#F9F6F2] rounded-full px-8 text-sm font-medium border-2 {{ $errors->has('name') ? 'border-red-400' : 'border-transparent' }} focus:border-[#C0422A] focus:bg-white outline-none transition-all"
                        :class="errors.name ? 'border-red-400!' : ''"
                    >
                    <p x-show="errors.name" x-text="errors.name" class="text-xs font-bold text-red-500 px-5 mt-1" x-cloak></p>
                    @error('name')
                        <p class="text-xs font-bold text-red-500 px-5 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Secure Email --}}
                <div class="space-y-2">
                    <label class="text-[10px] font-bold uppercase tracking-widest px-5 block text-gray-500">Secure Email</label>
                    <input 
                        type="email" 
                        name="email" 
                        x-model="email" 
                        @input="delete errors.email" 
                        required 
                        placeholder="email@example.com"
                        class="w-full h-14 bg-[#F9F6F2] rounded-full px-8 text-sm font-medium border-2 {{ $errors->has('email') ? 'border-red-400' : 'border-transparent' }} focus:border-[#C0422A] focus:bg-white outline-none transition-all"
                        :class="errors.email ? 'border-red-400!' : ''"
                    >
                    <p x-show="errors.email" x-text="errors.email" class="text-xs font-bold text-red-500 px-5 mt-1" x-cloak></p>
                    @error('email')
                        <p class="text-xs font-bold text-red-500 px-5 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Password --}}
                <div class="space-y-2">
                    <label class="text-[10px] font-bold uppercase tracking-widest px-5 block text-gray-500">Platform Password</label>
                    <div class="relative">
                        <input 
                            :type="showPass ? 'text' : 'password'" 
                            name="password" 
                            x-model="password" 
                            @input="delete errors.password; delete errors.password_confirmation" 
                            required 
                            placeholder="••••••••••••"
                            class="w-full h-14 bg-[#F9F6F2] rounded-full px-8 pr-14 text-sm font-medium border-2 {{ $errors->has('password') ? 'border-red-400' : 'border-transparent' }} focus:border-[#C0422A] focus:bg-white outline-none transition-all"
                            :class="errors.password ? 'border-red-400!' : ''"
                        >
                        <button type="button" @click="showPass = !showPass" class="absolute right-5 top-1/2 -translate-y-1/2 text-gray-400 hover:text-[#C0422A] transition-colors cursor-pointer">
                            <svg x-show="!showPass" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                            <svg x-show="showPass" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" x-cloak>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.477 0-8.268-2.943-9.542-7a9.97 9.97 0 012.458-4.017M6.53 6.53A9.97 9.97 0 0112 5c4.478 0 8.268 2.943 9.542 7a10.05 10.05 0 01-4.132 5.411M15 12a3 3 0 00-3-3m0 0a3 3 0 00-3 3m3-3L3 3m18 18L9 9"/>
                            </svg>
                        </button>
                    </div>
                    <p x-show="errors.password" x-text="errors.password" class="text-xs font-bold text-red-500 px-5 mt-1" x-cloak></p>
                    @error('password')
                        <p class="text-xs font-bold text-red-500 px-5 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Confirm Password --}}
                <div class="space-y-2">
                    <label class="text-[10px] font-bold uppercase tracking-widest px-5 block text-gray-500">Confirm Password</label>
                    <div class="relative">
                        <input 
                            :type="showConfirm ? 'text' : 'password'" 
                            name="password_confirmation" 
                            x-model="password_confirmation" 
                            @input="delete errors.password_confirmation" 
                            required 
                            placeholder="••••••••••••"
                            class="w-full h-14 bg-[#F9F6F2] rounded-full px-8 pr-14 text-sm font-medium border-2 {{ $errors->has('password_confirmation') ? 'border-red-400' : 'border-transparent' }} focus:border-[#C0422A] focus:bg-white outline-none transition-all"
                            :class="errors.password_confirmation ? 'border-red-400!' : ''"
                        >
                        <button type="button" @click="showConfirm = !showConfirm" class="absolute right-5 top-1/2 -translate-y-1/2 text-gray-400 hover:text-[#C0422A] transition-colors cursor-pointer">
                            <svg x-show="!showConfirm" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                            <svg x-show="showConfirm" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" x-cloak>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.477 0-8.268-2.943-9.542-7a9.97 9.97 0 012.458-4.017M6.53 6.53A9.97 9.97 0 0112 5c4.478 0 8.268 2.943 9.542 7a10.05 10.05 0 01-4.132 5.411M15 12a3 3 0 00-3-3m0 0a3 3 0 00-3 3m3-3L3 3m18 18L9 9"/>
                            </svg>
                        </button>
                    </div>
                    <p x-show="errors.password_confirmation" x-text="errors.password_confirmation" class="text-xs font-bold text-red-500 px-5 mt-1" x-cloak></p>
                    @error('password_confirmation')
                        <p class="text-xs font-bold text-red-500 px-5 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <button type="button" @click="validateStep1()" class="w-full h-14 bg-[#3D2B1F] text-white rounded-full font-bold uppercase tracking-[0.2em] text-[10px] shadow-xl shadow-black/10 hover:bg-[#C0422A] transition-all flex items-center justify-center gap-3 cursor-pointer">
                    <span>Continue to Requirements</span>
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" /></svg>
                </button>

                @if(config('services.google.client_id') && !$googleSeller)
                    <div class="pt-2">
                        <div class="flex items-center gap-4 mb-4">
                            <div class="h-px flex-1 bg-[#E5DDD5]"></div>
                            <span class="text-[9px] font-bold text-[#8C7B70] uppercase tracking-widest">or register with</span>
                            <div class="h-px flex-1 bg-[#E5DDD5]"></div>
                        </div>
                        <div class="flex justify-center">
                            <div id="g_id_onload"
                                data-client_id="{{ config('services.google.client_id') }}"
                                data-context="signup"
                                data-ux_mode="popup"
                                data-callback="handleGoogleSellerSignupResponse"
                                data-auto_prompt="false">
                            </div>
                            <div class="g_id_signin"
                                data-type="standard"
                                data-shape="pill"
                                data-theme="outline"
                                data-text="signup_with"
                                data-size="large"
                                data-logo_alignment="left">
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            <!-- STEP 2 -->
            <div x-show="step === 2" class="space-y-5">
                <div class="text-center mb-4">
                    <h2 class="text-xs font-bold uppercase tracking-widest text-gray-500 mb-1">Seller Verification</h2>
                    <p class="text-[10px] text-gray-400 italic">Please provide your details for account verification.</p>
                </div>

                {{-- Shop Name --}}
                <div class="space-y-2">
                    <label class="text-[10px] font-bold uppercase tracking-widest px-5 block text-gray-500">Shop / Workshop Name (Optional)</label>
                    <input 
                        type="text" 
                        name="shopName" 
                        value="{{ old('shopName') }}" 
                        placeholder="e.g. Juan's Traditional Embroidery"
                        class="w-full h-14 bg-[#F9F6F2] rounded-full px-8 text-sm font-medium border-2 {{ $errors->has('shopName') ? 'border-red-400' : 'border-transparent' }} focus:border-[#C0422A] focus:bg-white outline-none transition-all"
                    >
                    @error('shopName')
                        <p class="text-xs font-bold text-red-500 px-5 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Mobile Number --}}
                <div class="space-y-2">
                    <label class="text-[10px] font-bold uppercase tracking-widest px-5 block text-gray-500">Mobile Number</label>
                    <input 
                        type="text" 
                        name="mobileNumber" 
                        value="{{ old('mobileNumber') }}" 
                        placeholder="09xx-xxx-xxxx"
                        class="w-full h-14 bg-[#F9F6F2] rounded-full px-8 text-sm font-medium border-2 {{ $errors->has('mobileNumber') ? 'border-red-400' : 'border-transparent' }} focus:border-[#C0422A] focus:bg-white outline-none transition-all"
                    >
                    @error('mobileNumber')
                        <p class="text-xs font-bold text-red-500 px-5 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Requirements Cards --}}
                <div class="space-y-2">
                    <label class="text-[10px] font-bold uppercase tracking-widest px-5 block text-gray-500">Requirements</label>
                    <div class="grid grid-cols-3 gap-3">
                        <!-- Residency -->
                        <div class="relative" x-data="{ fileName: '' }">
                            <input type="file" name="residencyCertificate" accept="image/*,application/pdf" @change="fileName = $event.target.files[0] ? $event.target.files[0].name : ''" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10" required>
                            <div class="upload-card" :class="fileName ? 'has-file' : ''">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-[#C0422A] mb-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" /></svg>
                                <span class="text-[8px] font-bold uppercase tracking-widest text-gray-500 text-center leading-tight line-clamp-2" x-text="fileName || 'Residency'"></span>
                            </div>
                        </div>

                        <!-- Business Permit -->
                        <div class="relative" x-data="{ fileName: '' }">
                            <input type="file" name="businessPermit" accept="image/*,application/pdf" @change="fileName = $event.target.files[0] ? $event.target.files[0].name : ''" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10" required>
                            <div class="upload-card" :class="fileName ? 'has-file' : ''">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-[#C0422A] mb-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" /></svg>
                                <span class="text-[8px] font-bold uppercase tracking-widest text-gray-500 text-center leading-tight line-clamp-2" x-text="fileName || 'Business Permit'"></span>
                            </div>
                        </div>

                        <!-- BIR Document -->
                        <div class="relative" x-data="{ fileName: '' }">
                            <input type="file" name="birDocument" accept="image/*,application/pdf" @change="fileName = $event.target.files[0] ? $event.target.files[0].name : ''" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10" required>
                            <div class="upload-card" :class="fileName ? 'has-file' : ''">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-[#C0422A] mb-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" /></svg>
                                <span class="text-[8px] font-bold uppercase tracking-widest text-gray-500 text-center leading-tight line-clamp-2" x-text="fileName || 'BIR Document'"></span>
                            </div>
                        </div>
                    </div>
                    @error('residencyCertificate')
                        <p class="text-xs font-bold text-red-500 px-5 mt-1">{{ $message }}</p>
                    @enderror
                    @error('businessPermit')
                        <p class="text-xs font-bold text-red-500 px-5 mt-1">{{ $message }}</p>
                    @enderror
                    @error('birDocument')
                        <p class="text-xs font-bold text-red-500 px-5 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="px-2 pt-2">
                    <div class="flex items-start gap-2.5">
                        <input type="checkbox" name="terms_consent" id="seller_terms_consent" required class="mt-0.5 rounded text-[#C0422A] focus:ring-[#C0422A] cursor-pointer shrink-0 accent-[#C0422A]">
                        <label for="seller_terms_consent" class="text-xs text-gray-500 leading-snug select-none">
                            I have read and agree to the <button type="button" onclick="window.dispatchEvent(new CustomEvent('open-page-modal', { detail: { tab: 'terms' } }))" class="text-[#C0422A] font-bold hover:underline cursor-pointer">Terms and Conditions</button> and <button type="button" onclick="window.dispatchEvent(new CustomEvent('open-page-modal', { detail: { tab: 'privacy' } }))" class="text-[#C0422A] font-bold hover:underline cursor-pointer">Privacy Policy</button>.
                        </label>
                    </div>
                    @error('terms_consent')
                        <p class="text-xs font-bold text-red-500 px-2 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="pt-2">
                    <button type="submit" class="w-full h-14 bg-[#C0422A] text-white rounded-full font-bold uppercase tracking-[0.2em] text-[10px] shadow-xl shadow-[#C0422A]/20 hover:scale-[1.02] transition-all cursor-pointer">
                        Submit Application
                    </button>
                </div>
            </div>
        </form>

        @if(config('services.google.client_id'))
            <script src="https://accounts.google.com/gsi/client" async defer></script>
            <script>
                function handleGoogleSellerSignupResponse(response) {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = '/auth/google/seller/signup';
                    
                    const csrfToken = document.createElement('input');
                    csrfToken.type = 'hidden';
                    csrfToken.name = '_token';
                    csrfToken.value = '{{ csrf_token() }}';
                    form.appendChild(csrfToken);

                    const credentialInput = document.createElement('input');
                    credentialInput.type = 'hidden';
                    credentialInput.name = 'credential';
                    credentialInput.value = response.credential;
                    form.appendChild(credentialInput);

                    document.body.appendChild(form);
                    form.submit();
                }
            </script>
        @endif

        <x-pages-modal />

        <div class="mt-8 text-center">
            <p class="text-[10px] font-bold uppercase tracking-widest text-gray-400">
                Already registered? 
                <a href="/login" class="text-[#C0422A] font-bold ml-1 hover:underline">Sign-In</a>
            </p>
        </div>
    </div>

    <script>
        // Auto-reload on Back/Forward navigation from bfcache to get fresh CSRF token
        window.addEventListener('pageshow', function(event) {
            if (event.persisted || (window.performance && window.performance.navigation && window.performance.navigation.type === 2)) {
                window.location.reload();
            }
        });
    </script>
</body>
</html>
