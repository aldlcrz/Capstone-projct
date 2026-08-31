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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Playfair+Display:ital,wght@0,600;0,700;0,800;0,900;1,700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background: #F7F3EE; }
        .font-serif { font-family: 'Playfair Display', serif; }
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
        [x-cloak] { display: none !important; }

        .premium-card {
            background: #FFFFFF;
            border: 1px solid #EADBCC;
            box-shadow: 0 24px 70px -12px rgba(30, 25, 21, 0.08), 0 0 0 1px rgba(196, 149, 32, 0.04);
        }

        .premium-input-wrap {
            position: relative;
            background: #FAF7F2;
            border: 1.5px solid #E7DFD5;
            border-radius: 9999px;
            transition: all 0.25s ease;
        }
        .premium-input-wrap:focus-within {
            border-color: #C0422A;
            background: #FFFFFF;
            box-shadow: 0 0 0 4px rgba(192, 66, 42, 0.08), 0 2px 8px rgba(0,0,0,0.02);
        }
        .premium-input-wrap.has-error {
            border-color: #EF4444;
            background: #FFFBFB;
        }

        .premium-input {
            width: 100%;
            height: 52px;
            background: transparent;
            font-size: 13.5px;
            font-weight: 600;
            color: #1E1915;
            outline: none;
            border: none;
        }
        .premium-input::placeholder {
            color: #A89F91;
            font-weight: 500;
        }

        .upload-slot {
            background: #FAF7F2;
            border-radius: 20px;
            border: 2px dashed #E5DDD5;
            padding: 16px 12px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            transition: all 0.25s ease;
            cursor: pointer;
            min-height: 124px;
            text-align: center;
        }
        .upload-slot:hover {
            border-color: #C0422A;
            background: #FDFBFA;
            transform: translateY(-1px);
        }
        .upload-slot.uploaded {
            border-color: #10B981;
            border-style: solid;
            background: #F0FDF4;
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4 sm:p-6 relative overflow-x-hidden">
    <!-- Ambient Heritage Lighting Blobs -->
    <div class="absolute top-0 right-0 w-140 h-140 rounded-full -translate-y-1/2 translate-x-1/3 blur-3xl opacity-[0.05] pointer-events-none bg-[#C0422A]"></div>
    <div class="absolute bottom-0 left-0 w-100 h-100 rounded-full translate-y-1/2 -translate-x-1/3 blur-3xl opacity-[0.14] pointer-events-none bg-[#D4B896]"></div>
    <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-160 h-160 rounded-full blur-3xl opacity-[0.03] pointer-events-none bg-[#C49520]"></div>

    <div class="premium-card w-full max-w-xl rounded-[2.5rem] sm:rounded-[3rem] p-7 sm:p-11 relative z-10 max-h-[95vh] overflow-y-auto no-scrollbar" 
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
        
        <!-- Header Section -->
        <div class="relative mb-8 text-center">
            <!-- Back Button -->
            <button type="button" x-show="step > 1" @click="step--" class="absolute left-0 top-1/2 -translate-y-1/2 w-10 h-10 rounded-full bg-[#FAF7F2] border border-[#E7DFD5] flex items-center justify-center hover:bg-white hover:border-[#C0422A] hover:text-[#C0422A] text-stone-600 transition-all shadow-2xs cursor-pointer" title="Previous Step">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
            </button>
            <button type="button" x-show="step === 1" @click="window.history.back()" class="absolute left-0 top-1/2 -translate-y-1/2 w-10 h-10 rounded-full bg-[#FAF7F2] border border-[#E7DFD5] flex items-center justify-center hover:bg-white hover:border-[#C0422A] hover:text-[#C0422A] text-stone-600 transition-all shadow-2xs cursor-pointer" title="Back">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
            </button>

            <!-- Brand Logo Mark -->
            <div class="flex justify-center mb-3">
                <div class="p-1 rounded-full bg-white border border-[#EADBCC] shadow-xs">
                    <img src="{{ asset('images/logo-icon.png') }}" alt="LumBarong Logo" class="w-13 h-13 object-contain rounded-full hover:scale-105 transition-transform">
                </div>
            </div>
            
            <h1 class="font-serif text-3xl sm:text-4xl font-extrabold tracking-tight text-[#C0422A] mb-1">LumBarong</h1>
            <div class="flex items-center justify-center gap-2">
                <span class="text-[9px] font-black uppercase tracking-[0.3em] text-[#C49520] bg-amber-50/80 px-3 py-0.5 rounded-full border border-amber-200/60">
                    Artisan Partner
                </span>
                <span class="text-[9px] font-bold uppercase tracking-[0.25em] text-stone-400">Seller Registration</span>
            </div>
        </div>

        <!-- 2-Step Modern Stepper -->
        <div class="mb-8 p-3 rounded-2xl bg-[#FAF7F2] border border-[#E7DFD5]/80 flex items-center justify-between gap-2 sm:gap-4">
            <!-- Step 1 Milestone -->
            <div class="flex items-center gap-2.5 flex-1 min-w-0" :class="step >= 1 ? 'opacity-100' : 'opacity-50'">
                <div class="w-8 h-8 rounded-xl flex items-center justify-center font-bold text-xs shrink-0 transition-all"
                     :class="step === 1 ? 'bg-[#C0422A] text-white shadow-sm ring-2 ring-[#C0422A]/20' : (step > 1 ? 'bg-emerald-600 text-white' : 'bg-stone-200 text-stone-600')">
                    <template x-if="step > 1"><span>✓</span></template>
                    <template x-if="step <= 1"><span>1</span></template>
                </div>
                <div class="min-w-0">
                    <div class="text-[8px] font-black uppercase tracking-wider text-stone-400">Step 1</div>
                    <div class="text-xs font-bold truncate" :class="step === 1 ? 'text-[#1E1915]' : 'text-stone-600'">Account Details</div>
                </div>
            </div>

            <!-- Divider Line -->
            <div class="h-0.5 w-6 sm:w-12 rounded-full transition-colors" :class="step >= 2 ? 'bg-[#C0422A]' : 'bg-stone-300'"></div>

            <!-- Step 2 Milestone -->
            <div class="flex items-center gap-2.5 flex-1 min-w-0 justify-end sm:justify-start" :class="step === 2 ? 'opacity-100' : 'opacity-60'">
                <div class="w-8 h-8 rounded-xl flex items-center justify-center font-bold text-xs shrink-0 transition-all"
                     :class="step === 2 ? 'bg-[#C0422A] text-white shadow-sm ring-2 ring-[#C0422A]/20' : 'bg-stone-200 text-stone-600'">
                    2
                </div>
                <div class="min-w-0 hidden sm:block">
                    <div class="text-[8px] font-black uppercase tracking-wider text-stone-400">Step 2</div>
                    <div class="text-xs font-bold truncate" :class="step === 2 ? 'text-[#1E1915]' : 'text-stone-600'">Verification Docs</div>
                </div>
            </div>
        </div>

        {{-- Alerts --}}
        @if (session('info'))
            <div class="mb-6 p-4 rounded-2xl bg-amber-50 border border-amber-200 text-amber-900 text-xs flex items-start gap-2.5 shadow-2xs">
                <span class="text-base shrink-0">ℹ️</span>
                <p class="font-medium leading-relaxed">{{ session('info') }}</p>
            </div>
        @endif
        @if (session('success'))
            <div class="mb-6 p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-900 text-xs flex items-start gap-2.5 shadow-2xs">
                <span class="text-base shrink-0">✓</span>
                <p class="font-medium leading-relaxed">{{ session('success') }}</p>
            </div>
        @endif
        @if (session('warning'))
            <div class="mb-6 p-4 rounded-2xl bg-amber-50 border border-amber-200 text-amber-900 text-xs flex items-start gap-2.5 shadow-2xs">
                <span class="text-base shrink-0">⚠️</span>
                <p class="font-medium leading-relaxed">{{ session('warning') }}</p>
            </div>
        @endif

        @php
            $googleSeller = session('google_seller_signup');
        @endphp

        @if($googleSeller)
            <div class="mb-6 p-4 rounded-2xl bg-blue-50 border border-blue-200 text-blue-900 text-xs flex items-center justify-between gap-3 shadow-2xs">
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
                <span class="text-[9px] font-black uppercase tracking-wider bg-blue-200/60 text-blue-800 px-2.5 py-1 rounded-md shrink-0 border border-blue-300/50">Verified</span>
            </div>
        @endif

        <form action="{{ route('seller.register.submit') }}" method="POST" enctype="multipart/form-data">
            @csrf
            
            <!-- ═════════════════════════════════════════════════════════════════ -->
            <!-- STEP 1: ACCOUNT CREDENTIALS                                       -->
            <!-- ═════════════════════════════════════════════════════════════════ -->
            <div x-show="step === 1" class="space-y-4 sm:space-y-5">
                
                {{-- Registry Name --}}
                <div class="space-y-1.5">
                    <label class="text-[10px] font-black uppercase tracking-[0.15em] px-3 block text-stone-500">Registry Full Name</label>
                    <div class="premium-input-wrap flex items-center px-4.5" :class="errors.name ? 'has-error' : ''">
                        <span class="text-stone-400 mr-3 shrink-0">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                        </span>
                        <input type="text" name="name" x-model="name" @input="delete errors.name" required class="premium-input" placeholder="e.g. Maria Santos">
                    </div>
                    <p x-show="errors.name" x-text="errors.name" class="text-xs font-bold text-red-500 px-3 mt-1" x-cloak></p>
                    @error('name')
                        <p class="text-xs font-bold text-red-500 px-3 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Secure Email --}}
                <div class="space-y-1.5">
                    <label class="text-[10px] font-black uppercase tracking-[0.15em] px-3 block text-stone-500">Secure Email Address</label>
                    <div class="premium-input-wrap flex items-center px-4.5" :class="errors.email ? 'has-error' : ''">
                        <span class="text-stone-400 mr-3 shrink-0">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002 2H5a2 2 0 00-2-2V7a2 2 0 002-2h14a2 2 0 002 2v10" /></svg>
                        </span>
                        <input type="email" name="email" x-model="email" @input="delete errors.email" required class="premium-input" placeholder="artisan@lumbarong.shop">
                    </div>
                    <p x-show="errors.email" x-text="errors.email" class="text-xs font-bold text-red-500 px-3 mt-1" x-cloak></p>
                    @error('email')
                        <p class="text-xs font-bold text-red-500 px-3 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Platform Password --}}
                <div class="space-y-1.5">
                    <label class="text-[10px] font-black uppercase tracking-[0.15em] px-3 block text-stone-500">Platform Password</label>
                    <div class="premium-input-wrap flex items-center px-4.5" :class="errors.password ? 'has-error' : ''">
                        <span class="text-stone-400 mr-3 shrink-0">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                        </span>
                        <input :type="showPass ? 'text' : 'password'" name="password" x-model="password" @input="delete errors.password; delete errors.password_confirmation" required class="premium-input pr-2" placeholder="••••••••••••">
                        <button type="button" @click="showPass = !showPass" class="text-stone-400 hover:text-stone-700 transition-colors p-1 cursor-pointer" title="Toggle password view">
                            <svg x-show="!showPass" xmlns="http://www.w3.org/2000/svg" class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                            <svg x-show="showPass" xmlns="http://www.w3.org/2000/svg" class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" x-cloak><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L5.136 5.136m13.727 13.727L13.875 18.825M21 12a10.025 10.025 0 01-1.12 4.5m-5.878-9.375l2.122-2.122m-8.484 8.484L5.136 5.136m13.727 13.727L21 12" /></svg>
                        </button>
                    </div>
                    <p x-show="errors.password" x-text="errors.password" class="text-xs font-bold text-red-500 px-3 mt-1" x-cloak></p>
                    @error('password')
                        <p class="text-xs font-bold text-red-500 px-3 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Confirm Password --}}
                <div class="space-y-1.5">
                    <label class="text-[10px] font-black uppercase tracking-[0.15em] px-3 block text-stone-500">Confirm Password</label>
                    <div class="premium-input-wrap flex items-center px-4.5" :class="errors.password_confirmation ? 'has-error' : ''">
                        <span class="text-stone-400 mr-3 shrink-0">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                        </span>
                        <input :type="showConfirm ? 'text' : 'password'" name="password_confirmation" x-model="password_confirmation" @input="delete errors.password_confirmation" required class="premium-input pr-2" placeholder="••••••••••••">
                        <button type="button" @click="showConfirm = !showConfirm" class="text-stone-400 hover:text-stone-700 transition-colors p-1 cursor-pointer" title="Toggle password confirmation view">
                            <svg x-show="!showConfirm" xmlns="http://www.w3.org/2000/svg" class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                            <svg x-show="showConfirm" xmlns="http://www.w3.org/2000/svg" class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" x-cloak><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L5.136 5.136m13.727 13.727L13.875 18.825M21 12a10.025 10.025 0 01-1.12 4.5m-5.878-9.375l2.122-2.122m-8.484 8.484L5.136 5.136m13.727 13.727L21 12" /></svg>
                        </button>
                    </div>
                    <p x-show="errors.password_confirmation" x-text="errors.password_confirmation" class="text-xs font-bold text-red-500 px-3 mt-1" x-cloak></p>
                    @error('password_confirmation')
                        <p class="text-xs font-bold text-red-500 px-3 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Continue to Requirements Button --}}
                <div class="pt-2">
                    <button type="button" @click="validateStep1()" class="w-full h-13.5 bg-[#1E1915] text-white rounded-full font-black uppercase tracking-[0.2em] text-[11px] shadow-lg shadow-black/15 hover:bg-[#C0422A] hover:shadow-[#C0422A]/20 transition-all flex items-center justify-center gap-3 cursor-pointer">
                        <span>Continue to Requirements</span>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-[#C49520]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" /></svg>
                    </button>
                </div>

                {{-- Google Sign In Divider --}}
                @if(config('services.google.client_id') && !$googleSeller)
                    <div class="pt-3">
                        <div class="flex items-center gap-4 mb-4">
                            <div class="h-px flex-1 bg-[#E7DFD5]"></div>
                            <span class="text-[9px] font-black text-stone-400 uppercase tracking-widest">or register with</span>
                            <div class="h-px flex-1 bg-[#E7DFD5]"></div>
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

            <!-- ═════════════════════════════════════════════════════════════════ -->
            <!-- STEP 2: ARTISAN VERIFICATION DOCUMENTS                            -->
            <!-- ═════════════════════════════════════════════════════════════════ -->
            <div x-show="step === 2" class="space-y-5">
                <div class="text-center p-3 rounded-2xl bg-amber-50/70 border border-amber-200/70">
                    <h2 class="text-xs font-black uppercase tracking-widest text-[#7A5505]">🛡️ Artisan Verification</h2>
                    <p class="text-[11px] text-amber-900/80 mt-0.5 leading-relaxed">
                        To protect the Lumban heritage craft, verified seller status requires proof of local workshop operation.
                    </p>
                </div>

                {{-- Shop Name --}}
                <div class="space-y-1.5">
                    <label class="text-[10px] font-black uppercase tracking-[0.15em] px-3 block text-stone-500">Shop / Workshop Name (Optional)</label>
                    <div class="premium-input-wrap flex items-center px-4.5">
                        <span class="text-stone-400 mr-3 shrink-0">🏪</span>
                        <input type="text" name="shopName" value="{{ old('shopName') }}" class="premium-input" placeholder="e.g. Juan's Lumban Embroidery Workshop">
                    </div>
                    @error('shopName')
                        <p class="text-xs font-bold text-red-500 px-3 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Mobile Number --}}
                <div class="space-y-1.5">
                    <label class="text-[10px] font-black uppercase tracking-[0.15em] px-3 block text-stone-500">Mobile Number *</label>
                    <div class="premium-input-wrap flex items-center px-4.5">
                        <span class="text-stone-400 mr-3 shrink-0">📱</span>
                        <input type="text" name="mobileNumber" value="{{ old('mobileNumber') }}" class="premium-input" placeholder="09xx-xxx-xxxx">
                    </div>
                    @error('mobileNumber')
                        <p class="text-xs font-bold text-red-500 px-3 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Requirements Upload Section --}}
                <div class="space-y-2">
                    <div class="flex items-center justify-between px-1">
                        <label class="text-[10px] font-black uppercase tracking-[0.15em] block text-stone-500">Verification Documents *</label>
                        <span class="text-[9px] font-bold text-stone-400">PDF, JPG, PNG (Max 5MB)</span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        
                        <!-- 1. Residency Certificate -->
                        <div class="relative" x-data="{ fileName: '' }">
                            <input type="file" name="residencyCertificate" accept="image/*,application/pdf" @change="fileName = $event.target.files[0] ? $event.target.files[0].name : ''" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10" required>
                            <div class="upload-slot" :class="fileName ? 'uploaded' : ''">
                                <div class="w-8 h-8 rounded-full flex items-center justify-center mb-1.5" :class="fileName ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-50 text-[#C0422A]'">
                                    <template x-if="fileName"><span>✓</span></template>
                                    <template x-if="!fileName">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" /></svg>
                                    </template>
                                </div>
                                <div class="text-[10px] font-black uppercase tracking-wider text-stone-800 leading-tight" x-text="fileName ? 'Residency Attached' : 'Barangay Residency'"></div>
                                <p class="text-[9px] text-stone-400 mt-0.5 truncate max-w-full px-1" x-text="fileName || 'Click to upload proof'"></p>
                            </div>
                        </div>

                        <!-- 2. Business Permit -->
                        <div class="relative" x-data="{ fileName: '' }">
                            <input type="file" name="businessPermit" accept="image/*,application/pdf" @change="fileName = $event.target.files[0] ? $event.target.files[0].name : ''" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10" required>
                            <div class="upload-slot" :class="fileName ? 'uploaded' : ''">
                                <div class="w-8 h-8 rounded-full flex items-center justify-center mb-1.5" :class="fileName ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-50 text-[#C0422A]'">
                                    <template x-if="fileName"><span>✓</span></template>
                                    <template x-if="!fileName">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                                    </template>
                                </div>
                                <div class="text-[10px] font-black uppercase tracking-wider text-stone-800 leading-tight" x-text="fileName ? 'Permit Attached' : 'Business Permit'"></div>
                                <p class="text-[9px] text-stone-400 mt-0.5 truncate max-w-full px-1" x-text="fileName || 'Mayor / DTI permit'"></p>
                            </div>
                        </div>

                        <!-- 3. BIR Document -->
                        <div class="relative" x-data="{ fileName: '' }">
                            <input type="file" name="birDocument" accept="image/*,application/pdf" @change="fileName = $event.target.files[0] ? $event.target.files[0].name : ''" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10" required>
                            <div class="upload-slot" :class="fileName ? 'uploaded' : ''">
                                <div class="w-8 h-8 rounded-full flex items-center justify-center mb-1.5" :class="fileName ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-50 text-[#C0422A]'">
                                    <template x-if="fileName"><span>✓</span></template>
                                    <template x-if="!fileName">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" /></svg>
                                    </template>
                                </div>
                                <div class="text-[10px] font-black uppercase tracking-wider text-stone-800 leading-tight" x-text="fileName ? 'BIR Attached' : 'BIR / TIN Certificate'"></div>
                                <p class="text-[9px] text-stone-400 mt-0.5 truncate max-w-full px-1" x-text="fileName || 'BIR registration proof'"></p>
                            </div>
                        </div>

                    </div>

                    @error('residencyCertificate')
                        <p class="text-xs font-bold text-red-500 px-3 mt-1">{{ $message }}</p>
                    @enderror
                    @error('businessPermit')
                        <p class="text-xs font-bold text-red-500 px-3 mt-1">{{ $message }}</p>
                    @enderror
                    @error('birDocument')
                        <p class="text-xs font-bold text-red-500 px-3 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Terms Consent --}}
                <div class="p-3.5 rounded-2xl bg-stone-50 border border-stone-200/80">
                    <div class="flex items-start gap-3">
                        <input type="checkbox" name="terms_consent" id="seller_terms_consent" required class="mt-0.5 w-4 h-4 rounded text-[#C0422A] focus:ring-[#C0422A] cursor-pointer shrink-0 accent-[#C0422A]">
                        <label for="seller_terms_consent" class="text-xs text-stone-600 leading-relaxed select-none">
                            I certify that the information provided is true, and I agree to the <button type="button" onclick="window.dispatchEvent(new CustomEvent('open-page-modal', { detail: { tab: 'terms' } }))" class="text-[#C0422A] font-black hover:underline cursor-pointer">Terms and Conditions</button> and <button type="button" onclick="window.dispatchEvent(new CustomEvent('open-page-modal', { detail: { tab: 'privacy' } }))" class="text-[#C0422A] font-black hover:underline cursor-pointer">Privacy Policy</button>.
                        </label>
                    </div>
                    @error('terms_consent')
                        <p class="text-xs font-bold text-red-500 px-1 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Submit Button --}}
                <div class="pt-2">
                    <button type="submit" class="w-full h-13.5 bg-[#C0422A] text-white rounded-full font-black uppercase tracking-[0.2em] text-[11px] shadow-xl shadow-[#C0422A]/20 hover:bg-[#A83822] hover:scale-[1.01] transition-all flex items-center justify-center gap-2.5 cursor-pointer">
                        <span>Submit Artisan Application</span>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
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
        <div class="mt-8 pt-4 border-t border-[#E7DFD5]/60 text-center">
            <p class="text-[10.5px] font-bold uppercase tracking-widest text-stone-400">
                Already registered? 
                <a href="/login" class="text-[#C0422A] hover:underline font-black ml-1">Sign-In</a>
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
