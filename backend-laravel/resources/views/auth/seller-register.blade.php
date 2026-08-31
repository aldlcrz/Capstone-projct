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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Playfair+Display:ital,wght@0,700;0,800;0,900;1,700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background: #F7F3EE; }
        .font-serif { font-family: 'Playfair Display', serif; }
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
        [x-cloak] { display: none !important; }

        .step-bubble {
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 9999px;
            font-size: 13px;
            font-weight: 800;
            transition: all 0.3s ease;
        }
        .step-active {
            background-color: #C0422A;
            color: #FFFFFF;
            box-shadow: 0 4px 14px rgba(192, 66, 42, 0.3);
        }
        .step-inactive {
            background-color: #F0EDE8;
            color: #A89F91;
            border: 1px solid #E5DDD5;
        }

        .premium-input-box {
            position: relative;
            background: #F9F6F2;
            border: 1.5px solid #E8DFD5;
            border-radius: 9999px;
            transition: all 0.2s ease;
        }
        .premium-input-box:focus-within {
            border-color: #C0422A;
            background: #FFFFFF;
            box-shadow: 0 0 0 3px rgba(192, 66, 42, 0.1);
        }
        .premium-input-box.is-invalid {
            border-color: #EF4444;
            background: #FFFBFB;
        }

        .premium-input-field {
            width: 100%;
            height: 52px;
            background: transparent;
            font-size: 13.5px;
            font-weight: 500;
            color: #1E1915;
            outline: none;
            border: none;
            padding-left: 48px;
            padding-right: 48px;
        }
        .premium-input-field::placeholder {
            color: #A89F91;
        }

        .upload-card-box {
            background: #F9F6F2;
            border-radius: 20px;
            border: 2px dashed #E5DDD5;
            padding: 16px 12px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
            cursor: pointer;
            min-height: 125px;
            text-align: center;
        }
        .upload-card-box:hover {
            border-color: #C0422A;
            background: #FDFBFA;
        }
        .upload-card-box.has-file {
            border-color: #10B981;
            border-style: solid;
            background: #F0FDF4;
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4 sm:p-6 relative overflow-x-hidden">
    <!-- Ambient Decor Blobs -->
    <div class="absolute top-0 right-0 w-140 h-140 rounded-full -translate-y-1/2 translate-x-1/3 blur-3xl opacity-[0.05] pointer-events-none bg-[#C0422A]"></div>
    <div class="absolute bottom-0 left-0 w-100 h-100 rounded-full translate-y-1/2 -translate-x-1/3 blur-3xl opacity-[0.12] pointer-events-none bg-[#D4B896]"></div>

    <div class="w-full max-w-lg bg-white rounded-[2.5rem] sm:rounded-[3rem] border border-[#EADBCC] p-7 sm:p-10 shadow-[0_20px_60px_rgba(60,40,20,0.07)] relative z-10 max-h-[95vh] overflow-y-auto no-scrollbar" 
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
            <button type="button" x-show="step > 1" @click="step--" class="absolute left-0 top-2 w-9 h-9 rounded-full bg-[#F9F6F2] border border-[#E8DFD5] flex items-center justify-center hover:bg-white text-stone-600 hover:text-[#C0422A] transition-all cursor-pointer shadow-2xs" title="Previous Step">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
            </button>
            <button type="button" x-show="step === 1" @click="window.history.back()" class="absolute left-0 top-2 w-9 h-9 rounded-full bg-[#F9F6F2] border border-[#E8DFD5] flex items-center justify-center hover:bg-white text-stone-600 hover:text-[#C0422A] transition-all cursor-pointer shadow-2xs" title="Back">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
            </button>

            <!-- Compact Centered Logo -->
            <div class="flex justify-center mb-2">
                <img src="{{ asset('images/logo-icon.png') }}" alt="LumBarong Logo" style="width: 52px; height: 52px; max-width: 52px; max-height: 52px; object-fit: contain;" class="rounded-full shadow-xs">
            </div>
            
            <h1 class="font-serif text-3xl font-extrabold tracking-tight text-[#C0422A] mb-0.5">LumBarong</h1>
            <p class="text-[9px] font-bold uppercase tracking-[0.3em] text-[#A89F91]">Seller Registration</p>
        </div>

        <!-- Clean Numeric Stepper -->
        <div class="flex items-center justify-center gap-3 mb-7">
            <div class="step-bubble" :class="step === 1 ? 'step-active' : (step > 1 ? 'bg-emerald-600 text-white' : 'step-inactive')">
                <span x-show="step > 1">✓</span>
                <span x-show="step <= 1">1</span>
            </div>
            <div class="h-0.5 w-16 rounded-full transition-colors" :class="step >= 2 ? 'bg-[#C0422A]' : 'bg-stone-200'"></div>
            <div class="step-bubble" :class="step === 2 ? 'step-active' : 'step-inactive'">
                2
            </div>
        </div>

        {{-- Alerts --}}
        @if (session('info'))
            <div class="mb-5 p-3.5 rounded-2xl bg-amber-50 border border-amber-200 text-amber-900 text-xs flex items-start gap-2.5">
                <span class="text-sm shrink-0">ℹ️</span>
                <p class="font-medium leading-relaxed">{{ session('info') }}</p>
            </div>
        @endif
        @if (session('success'))
            <div class="mb-5 p-3.5 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-900 text-xs flex items-start gap-2.5">
                <span class="text-sm shrink-0">✓</span>
                <p class="font-medium leading-relaxed">{{ session('success') }}</p>
            </div>
        @endif
        @if (session('warning'))
            <div class="mb-5 p-3.5 rounded-2xl bg-amber-50 border border-amber-200 text-amber-900 text-xs flex items-start gap-2.5">
                <span class="text-sm shrink-0">⚠️</span>
                <p class="font-medium leading-relaxed">{{ session('warning') }}</p>
            </div>
        @endif

        @php
            $googleSeller = session('google_seller_signup');
        @endphp

        @if($googleSeller)
            <div class="mb-5 p-3.5 rounded-2xl bg-blue-50 border border-blue-200 text-blue-900 text-xs flex items-center justify-between gap-3 shadow-2xs">
                <div class="flex items-center gap-2.5">
                    @if(!empty($googleSeller['picture']))
                        <img src="{{ $googleSeller['picture'] }}" class="w-7 h-7 rounded-full border border-blue-200 shrink-0">
                    @else
                        <span class="text-base">🇬</span>
                    @endif
                    <div class="min-w-0">
                        <div class="font-bold text-[11px]">Connected with Google</div>
                        <div class="text-[10px] text-blue-700 truncate">{{ $googleSeller['email'] }}</div>
                    </div>
                </div>
                <span class="text-[9px] font-black uppercase tracking-wider bg-blue-200/70 text-blue-800 px-2 py-0.5 rounded-md shrink-0">Verified</span>
            </div>
        @endif

        <form action="{{ route('seller.register.submit') }}" method="POST" enctype="multipart/form-data">
            @csrf
            
            <!-- STEP 1: ACCOUNT DETAILS -->
            <div x-show="step === 1" class="space-y-4">
                
                {{-- Registry Name --}}
                <div class="space-y-1">
                    <label class="text-[10px] font-black uppercase tracking-widest px-4 block text-stone-500">Registry Name</label>
                    <div class="premium-input-box" :class="errors.name ? 'is-invalid' : ''">
                        <span class="absolute left-4.5 top-1/2 -translate-y-1/2 text-stone-400 pointer-events-none">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                        </span>
                        <input type="text" name="name" x-model="name" @input="delete errors.name" required class="premium-input-field" placeholder="Your Full Name">
                    </div>
                    <p x-show="errors.name" x-text="errors.name" class="text-xs font-bold text-red-500 px-4 mt-0.5" x-cloak></p>
                    @error('name')
                        <p class="text-xs font-bold text-red-500 px-4 mt-0.5">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Secure Email --}}
                <div class="space-y-1">
                    <label class="text-[10px] font-black uppercase tracking-widest px-4 block text-stone-500">Secure Email</label>
                    <div class="premium-input-box" :class="errors.email ? 'is-invalid' : ''">
                        <span class="absolute left-4.5 top-1/2 -translate-y-1/2 text-stone-400 pointer-events-none">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002 2H5a2 2 0 00-2-2V7a2 2 0 002-2h14a2 2 0 002 2v10" /></svg>
                        </span>
                        <input type="email" name="email" x-model="email" @input="delete errors.email" required class="premium-input-field" placeholder="email@example.com">
                    </div>
                    <p x-show="errors.email" x-text="errors.email" class="text-xs font-bold text-red-500 px-4 mt-0.5" x-cloak></p>
                    @error('email')
                        <p class="text-xs font-bold text-red-500 px-4 mt-0.5">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Password --}}
                <div class="space-y-1">
                    <label class="text-[10px] font-black uppercase tracking-widest px-4 block text-stone-500">Platform Password</label>
                    <div class="premium-input-box" :class="errors.password ? 'is-invalid' : ''">
                        <span class="absolute left-4.5 top-1/2 -translate-y-1/2 text-stone-400 pointer-events-none">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                        </span>
                        <input :type="showPass ? 'text' : 'password'" name="password" x-model="password" @input="delete errors.password; delete errors.password_confirmation" required class="premium-input-field" placeholder="••••••••••••">
                        <button type="button" @click="showPass = !showPass" class="absolute right-4.5 top-1/2 -translate-y-1/2 text-stone-400 hover:text-stone-700 transition-colors p-1 cursor-pointer">
                            <svg x-show="!showPass" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                            <svg x-show="showPass" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" x-cloak><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L5.136 5.136m13.727 13.727L13.875 18.825M21 12a10.025 10.025 0 01-1.12 4.5m-5.878-9.375l2.122-2.122m-8.484 8.484L5.136 5.136m13.727 13.727L21 12" /></svg>
                        </button>
                    </div>
                    <p x-show="errors.password" x-text="errors.password" class="text-xs font-bold text-red-500 px-4 mt-0.5" x-cloak></p>
                    @error('password')
                        <p class="text-xs font-bold text-red-500 px-4 mt-0.5">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Confirm Password --}}
                <div class="space-y-1">
                    <label class="text-[10px] font-black uppercase tracking-widest px-4 block text-stone-500">Confirm Password</label>
                    <div class="premium-input-box" :class="errors.password_confirmation ? 'is-invalid' : ''">
                        <span class="absolute left-4.5 top-1/2 -translate-y-1/2 text-stone-400 pointer-events-none">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                        </span>
                        <input :type="showConfirm ? 'text' : 'password'" name="password_confirmation" x-model="password_confirmation" @input="delete errors.password_confirmation" required class="premium-input-field" placeholder="••••••••••••">
                        <button type="button" @click="showConfirm = !showConfirm" class="absolute right-4.5 top-1/2 -translate-y-1/2 text-stone-400 hover:text-stone-700 transition-colors p-1 cursor-pointer">
                            <svg x-show="!showConfirm" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                            <svg x-show="showConfirm" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" x-cloak><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L5.136 5.136m13.727 13.727L13.875 18.825M21 12a10.025 10.025 0 01-1.12 4.5m-5.878-9.375l2.122-2.122m-8.484 8.484L5.136 5.136m13.727 13.727L21 12" /></svg>
                        </button>
                    </div>
                    <p x-show="errors.password_confirmation" x-text="errors.password_confirmation" class="text-xs font-bold text-red-500 px-4 mt-0.5" x-cloak></p>
                    @error('password_confirmation')
                        <p class="text-xs font-bold text-red-500 px-4 mt-0.5">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Continue CTA --}}
                <div class="pt-2">
                    <button type="button" @click="validateStep1()" class="w-full h-13 bg-[#3D2B1F] text-white rounded-full font-bold uppercase tracking-[0.2em] text-[11px] shadow-md hover:bg-[#C0422A] transition-all flex items-center justify-center gap-2.5 cursor-pointer">
                        <span>Continue to Requirements</span>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" /></svg>
                    </button>
                </div>

                {{-- Google Divider --}}
                @if(config('services.google.client_id') && !$googleSeller)
                    <div class="pt-2">
                        <div class="flex items-center gap-4 mb-3.5">
                            <div class="h-px flex-1 bg-[#E8DFD5]"></div>
                            <span class="text-[9px] font-bold text-stone-400 uppercase tracking-widest">or register with</span>
                            <div class="h-px flex-1 bg-[#E8DFD5]"></div>
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

            <!-- STEP 2: VERIFICATION REQUIREMENTS -->
            <div x-show="step === 2" class="space-y-4">
                <div class="text-center mb-4">
                    <h2 class="text-xs font-bold uppercase tracking-widest text-stone-500 mb-0.5">Seller Verification</h2>
                    <p class="text-[10px] text-stone-400 italic">Please provide your details for workshop verification.</p>
                </div>

                {{-- Shop Name --}}
                <div class="space-y-1">
                    <label class="text-[10px] font-black uppercase tracking-widest px-4 block text-stone-500">Shop / Workshop Name (Optional)</label>
                    <div class="premium-input-box">
                        <input type="text" name="shopName" value="{{ old('shopName') }}" class="premium-input-field px-5" placeholder="e.g. Juan's Traditional Embroidery">
                    </div>
                    @error('shopName')
                        <p class="text-xs font-bold text-red-500 px-4 mt-0.5">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Mobile Number --}}
                <div class="space-y-1">
                    <label class="text-[10px] font-black uppercase tracking-widest px-4 block text-stone-500">Mobile Number *</label>
                    <div class="premium-input-box">
                        <input type="text" name="mobileNumber" value="{{ old('mobileNumber') }}" class="premium-input-field px-5" placeholder="09xx-xxx-xxxx">
                    </div>
                    @error('mobileNumber')
                        <p class="text-xs font-bold text-red-500 px-4 mt-0.5">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Requirements 3-Cards --}}
                <div class="space-y-1">
                    <label class="text-[10px] font-black uppercase tracking-widest px-4 block text-stone-500">Requirements *</label>
                    <div class="grid grid-cols-3 gap-2.5">
                        
                        <!-- Residency Certificate -->
                        <div class="relative" x-data="{ fileName: '' }">
                            <input type="file" name="residencyCertificate" accept="image/*,application/pdf" @change="fileName = $event.target.files[0] ? $event.target.files[0].name : ''" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10" required>
                            <div class="upload-card-box" :class="fileName ? 'has-file' : ''">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-[#C0422A] mb-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" /></svg>
                                <span class="text-[8.5px] font-bold uppercase tracking-wider text-stone-500 text-center leading-tight line-clamp-2" x-text="fileName || 'Residency'"></span>
                            </div>
                        </div>

                        <!-- Business Permit -->
                        <div class="relative" x-data="{ fileName: '' }">
                            <input type="file" name="businessPermit" accept="image/*,application/pdf" @change="fileName = $event.target.files[0] ? $event.target.files[0].name : ''" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10" required>
                            <div class="upload-card-box" :class="fileName ? 'has-file' : ''">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-[#C0422A] mb-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" /></svg>
                                <span class="text-[8.5px] font-bold uppercase tracking-wider text-stone-500 text-center leading-tight line-clamp-2" x-text="fileName || 'Business Permit'"></span>
                            </div>
                        </div>

                        <!-- BIR Document -->
                        <div class="relative" x-data="{ fileName: '' }">
                            <input type="file" name="birDocument" accept="image/*,application/pdf" @change="fileName = $event.target.files[0] ? $event.target.files[0].name : ''" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10" required>
                            <div class="upload-card-box" :class="fileName ? 'has-file' : ''">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-[#C0422A] mb-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" /></svg>
                                <span class="text-[8.5px] font-bold uppercase tracking-wider text-stone-500 text-center leading-tight line-clamp-2" x-text="fileName || 'BIR Document'"></span>
                            </div>
                        </div>

                    </div>

                    @error('residencyCertificate')
                        <p class="text-xs font-bold text-red-500 px-4 mt-0.5">{{ $message }}</p>
                    @enderror
                    @error('businessPermit')
                        <p class="text-xs font-bold text-red-500 px-4 mt-0.5">{{ $message }}</p>
                    @enderror
                    @error('birDocument')
                        <p class="text-xs font-bold text-red-500 px-4 mt-0.5">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Terms Consent --}}
                <div class="px-2 pt-1">
                    <div class="flex items-start gap-2.5">
                        <input type="checkbox" name="terms_consent" id="seller_terms_consent" required class="mt-0.5 w-4 h-4 rounded text-[#C0422A] focus:ring-[#C0422A] cursor-pointer shrink-0 accent-[#C0422A]">
                        <label for="seller_terms_consent" class="text-xs text-stone-500 leading-snug">
                            I have read and agree to the <button type="button" onclick="window.dispatchEvent(new CustomEvent('open-page-modal', { detail: { tab: 'terms' } }))" class="text-[#C0422A] font-bold hover:underline cursor-pointer">Terms and Conditions</button> and <button type="button" onclick="window.dispatchEvent(new CustomEvent('open-page-modal', { detail: { tab: 'privacy' } }))" class="text-[#C0422A] font-bold hover:underline cursor-pointer">Privacy Policy</button>.
                        </label>
                    </div>
                    @error('terms_consent')
                        <p class="text-xs font-bold text-red-500 px-2 mt-0.5">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Submit Button --}}
                <div class="pt-2">
                    <button type="submit" class="w-full h-13 bg-[#C0422A] text-white rounded-full font-bold uppercase tracking-[0.2em] text-[11px] shadow-lg shadow-[#C0422A]/20 hover:scale-[1.01] transition-all cursor-pointer">
                        Submit Application
                    </button>
                </div>
            </div>
        </form>

        {{-- Google GIS Script --}}
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

        {{-- Footer Link --}}
        <div class="mt-8 text-center">
            <p class="text-[10px] font-bold uppercase tracking-widest text-stone-400">
                Already registered? 
                <a href="/login" class="text-[#C0422A] font-black ml-1 hover:underline">Sign-In</a>
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
