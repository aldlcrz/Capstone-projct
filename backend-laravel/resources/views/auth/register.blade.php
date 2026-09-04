<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>Register | LumBarong</title>
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
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>
</head>
<body class="min-h-screen flex items-center justify-center p-3 sm:p-6 relative overflow-x-hidden overflow-y-auto">
    <!-- Subtle warm blobs -->
    <div class="absolute top-0 right-0 w-140 h-140 rounded-full -translate-y-1/2 translate-x-1/3 blur-3xl opacity-[0.04] pointer-events-none bg-[#C0422A]"></div>
    <div class="absolute bottom-0 left-0 w-95 h-95 rounded-full translate-y-1/2 -translate-x-1/3 blur-3xl opacity-[0.12] pointer-events-none bg-[#D4B896]"></div>

    <div class="login-card w-full max-w-md bg-white rounded-4xl sm:rounded-[2.5rem] border border-[#E5DDD5] p-5 sm:p-8 shadow-[0_20px_60px_rgba(60,40,20,0.08)] relative z-10">
        <div class="relative mb-8 text-center">
            <!-- Back to Home -->
            <a href="/" class="back-btn absolute left-0 top-1/2 -translate-y-1/2 w-10 h-10 rounded-full bg-[#F9F6F2] flex items-center justify-center hover:bg-gray-100 text-gray-600 hover:text-[#C0422A] transition-all shadow-sm" title="Back to Home">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
            </a>

            <div class="flex justify-center mb-3">
                <img src="{{ asset('images/logo-icon.png') }}" alt="LumBarong Logo" class="w-14 h-14 object-contain rounded-full shadow-md hover:scale-105 transition-transform">
            </div>
            <h1 class="font-serif text-2xl font-black tracking-tight text-gray-900 mb-1">LumBarong</h1>
            <p class="text-[11px] font-bold uppercase tracking-[0.3em] text-gray-400">Join the Collective</p>
        </div>

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

        <form action="/register" 
              method="POST" 
              class="space-y-4" 
              id="register-form"
              x-data="{
                  password: '',
                  password_confirmation: '',
                  showPass: false,
                  showConfirm: false,
                  suggestedNotice: false,
                  get strength() {
                      if (!this.password) return { score: 0, text: '', color: 'bg-gray-200' };
                      let s = 0;
                      if (this.password.length >= 6) s++;
                      if (this.password.length >= 8) s++;
                      if (/[A-Za-z]/.test(this.password) && /[0-9]/.test(this.password)) s++;
                      if (/[^A-Za-z0-9]/.test(this.password)) s++;
                      
                      if (s <= 1) return { score: 1, text: 'Weak (Need letters & numbers)', color: 'bg-rose-500', width: 'w-1/3', textCol: 'text-rose-500' };
                      if (s === 2) return { score: 2, text: 'Medium', color: 'bg-amber-500', width: 'w-2/3', textCol: 'text-amber-600' };
                      return { score: 3, text: 'Strong', color: 'bg-emerald-500', width: 'w-full', textCol: 'text-emerald-600' };
                  },
                  suggestPassword() {
                      const chars = 'abcdefghjkmnpqrstuvwxyz';
                      const uppers = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
                      const nums = '23456789';
                      const symbols = '!@#$%&*';
                      
                      let pass = 'Lum';
                      for(let i=0; i<3; i++) pass += chars.charAt(Math.floor(Math.random() * chars.length));
                      for(let i=0; i<3; i++) pass += nums.charAt(Math.floor(Math.random() * nums.length));
                      pass += symbols.charAt(Math.floor(Math.random() * symbols.length));
                      
                      this.password = pass;
                      this.password_confirmation = pass;
                      this.showPass = true;
                      this.showConfirm = true;
                      this.suggestedNotice = true;
                      setTimeout(() => this.suggestedNotice = false, 4000);
                  }
              }">
            @csrf

            <input type="hidden" name="pending_intent" id="hidden_pending_intent" value="{{ json_encode(session('pending_intent')) }}">
            
            {{-- Username Field --}}
            <div class="space-y-1">
                <label class="text-[10px] font-bold uppercase tracking-widest px-5 block text-gray-500">Username</label>
                <input 
                    type="text" 
                    name="username" 
                    value="{{ old('username', session('google_signup.name') ? \Illuminate\Support\Str::slug(session('google_signup.name'), '') : '') }}"
                    required 
                    placeholder="Choose a username"
                    class="w-full h-12 bg-[#F9F6F2] rounded-full px-8 text-sm font-medium border-2 {{ $errors->has('username') ? 'border-red-400' : 'border-transparent' }} focus:border-[#C0422A] focus:bg-white outline-none transition-all"
                >
                @error('username')
                    <p class="text-xs font-bold text-red-500 px-5 mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Email Field --}}
            <div class="space-y-1">
                <label class="text-[10px] font-bold uppercase tracking-widest px-5 block text-gray-500">Email Address</label>
                <input 
                    type="email" 
                    name="email" 
                    value="{{ old('email', session('google_signup.email')) }}"
                    required 
                    placeholder="example@gmail.com"
                    class="w-full h-12 bg-[#F9F6F2] rounded-full px-8 text-sm font-medium border-2 {{ $errors->has('email') ? 'border-red-400' : 'border-transparent' }} focus:border-[#C0422A] focus:bg-white outline-none transition-all"
                >
                @error('email')
                    <p class="text-xs font-bold text-red-500 px-5 mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Password Field --}}
            <div class="space-y-1">
                <label class="text-[10px] font-bold uppercase tracking-widest px-5 block text-gray-500">Password</label>
                <div class="relative flex items-center">
                    <input 
                        :type="showPass ? 'text' : 'password'" 
                        name="password" 
                        x-model="password"
                        required 
                        placeholder="At least 6 chars (letters & numbers)"
                        class="w-full h-12 bg-[#F9F6F2] rounded-full px-8 pr-14 text-sm font-medium border-2 {{ $errors->has('password') ? 'border-red-400' : 'border-transparent' }} focus:border-[#C0422A] focus:bg-white outline-none transition-all"
                    >
                    <button type="button" @click="showPass = !showPass"
                        class="absolute right-4 text-gray-400 hover:text-[#C0422A] transition-colors z-20 cursor-pointer p-1">
                        <svg x-show="!showPass" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                        <svg x-show="showPass" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" x-cloak>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.477 0-8.268-2.943-9.542-7a9.97 9.97 0 012.458-4.017M6.53 6.53A9.97 9.97 0 0112 5c4.478 0 8.268 2.943 9.542 7a10.05 10.05 0 01-4.132 5.411M15 12a3 3 0 00-3-3m0 0a3 3 0 00-3 3m3-3L3 3m18 18L9 9"/>
                        </svg>
                    </button>
                </div>

                {{-- Password Strength Meter --}}
                <div x-show="password.length > 0" class="px-5 pt-1 space-y-1" x-cloak>
                    <div class="h-1.5 w-full bg-gray-100 rounded-full overflow-hidden">
                        <div class="h-full transition-all duration-300 rounded-full" 
                             :class="[strength.color, strength.width]"></div>
                    </div>
                    <div class="flex items-center justify-between text-[10px]">
                        <span class="font-bold" :class="strength.textCol" x-text="strength.text"></span>
                        <span class="text-gray-400">Min. 6 chars with letters & numbers</span>
                    </div>
                </div>

                @error('password')
                    <p class="text-xs font-bold text-red-500 px-5 mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Confirm Password Field --}}
            <div class="space-y-1">
                <label class="text-[10px] font-bold uppercase tracking-widest px-5 block text-gray-500">Confirm Password</label>
                <div class="relative flex items-center">
                    <input 
                        :type="showConfirm ? 'text' : 'password'" 
                        name="password_confirmation" 
                        x-model="password_confirmation"
                        required 
                        placeholder="Re-enter your password"
                        class="w-full h-12 bg-[#F9F6F2] rounded-full px-8 pr-14 text-sm font-medium border-2 {{ $errors->has('password_confirmation') ? 'border-red-400' : 'border-transparent' }} focus:border-[#C0422A] focus:bg-white outline-none transition-all"
                    >
                    <button type="button" @click="showConfirm = !showConfirm"
                        class="absolute right-4 text-gray-400 hover:text-[#C0422A] transition-colors z-20 cursor-pointer p-1">
                        <svg x-show="!showConfirm" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                        <svg x-show="showConfirm" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" x-cloak>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.477 0-8.268-2.943-9.542-7a9.97 9.97 0 012.458-4.017M6.53 6.53A9.97 9.97 0 0112 5c4.478 0 8.268 2.943 9.542 7a10.05 10.05 0 01-4.132 5.411M15 12a3 3 0 00-3-3m0 0a3 3 0 00-3 3m3-3L3 3m18 18L9 9"/>
                        </svg>
                    </button>
                </div>
                @error('password_confirmation')
                    <p class="text-xs font-bold text-red-500 px-5 mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Terms & Consent --}}
            <div class="px-2 pt-2">
                <div class="flex items-start gap-2.5">
                    <input type="checkbox" name="terms_consent" id="terms_consent" required class="mt-0.5 rounded text-[#C0422A] focus:ring-[#C0422A] cursor-pointer shrink-0">
                    <label for="terms_consent" class="text-xs text-gray-500 leading-snug">
                        I have read and agree to the <button type="button" onclick="window.dispatchEvent(new CustomEvent('open-page-modal', { detail: { tab: 'terms' } }))" class="text-[#C0422A] font-bold hover:underline cursor-pointer">Terms and Conditions</button> and <button type="button" onclick="window.dispatchEvent(new CustomEvent('open-page-modal', { detail: { tab: 'privacy' } }))" class="text-[#C0422A] font-bold hover:underline cursor-pointer">Privacy Policy</button>.
                    </label>
                </div>
                @error('terms_consent')
                    <p class="text-xs font-bold text-red-500 px-2 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <button type="submit" class="w-full h-14 bg-[#3D2B1F] text-white rounded-full font-bold uppercase tracking-[0.2em] text-[10px] shadow-xl shadow-black/10 hover:bg-[#C0422A] transition-all mt-4">
                Register
            </button>

            @if(config('services.google.client_id'))
                <div class="pt-2">
                    <div class="flex items-center gap-4 mb-4">
                        <div class="h-px flex-1 bg-[#E5DDD5]"></div>
                        <span class="text-[9px] font-bold text-[#8C7B70] uppercase tracking-widest">or sign up with</span>
                        <div class="h-px flex-1 bg-[#E5DDD5]"></div>
                    </div>
                    <div class="flex justify-center">
                        <div id="g_id_onload"
                            data-client_id="{{ config('services.google.client_id') }}"
                            data-context="signup"
                            data-ux_mode="popup"
                            data-callback="handleGoogleSignupResponse"
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

                    <!-- In-App Browser Notice (Messenger / Facebook / Instagram) -->
                    <div id="in-app-browser-notice" class="hidden mt-4 p-3.5 bg-amber-50/90 border border-amber-200/80 rounded-2xl text-[11px] text-amber-900 leading-snug text-left shadow-2xs">
                        <div class="flex items-start gap-2.5">
                            <span class="text-sm shrink-0">ℹ️</span>
                            <div>
                                <span class="font-bold block mb-0.5 text-amber-950">Using Messenger / In-App Browser?</span>
                                <span>Google blocks sign-up inside in-app webviews. You can register using the form above, or tap <strong class="font-bold">⋮</strong> / <strong class="font-bold">⋯</strong> and choose <strong class="font-bold">Open in Chrome / Safari</strong>.</span>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </form>

        @if(config('services.google.client_id'))
            <script src="https://accounts.google.com/gsi/client" async defer></script>
            <script>
                function handleGoogleSignupResponse(response) {
                    try {
                        const form = document.createElement('form');
                        form.method = 'POST';
                        form.action = '/auth/google/signup';
                        
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
                    } catch(err) {
                        console.error('Google signup submission error:', err);
                    }
                }
            </script>
        @endif

        <x-pages-modal />

        <div class="mt-10 text-center">
            <p class="text-[10px] font-bold uppercase tracking-widest text-gray-400">
                Already have an account? 
                <a href="/login" class="text-[#C0422A] ml-1">Log-In</a>
            </p>
        </div>
    </div>

<script>
    // In-App Browser Detection (Messenger / Facebook / Instagram / Line / etc.)
    (function() {
        try {
            const isIAB = /FBAN|FBAV|FB_IAB|FBSS|Instagram|Line|Twitter|MicroMessenger|Snapchat/i.test(navigator.userAgent || '');
            if (isIAB) {
                const notice = document.getElementById('in-app-browser-notice');
                if (notice) notice.classList.remove('hidden');
            }
        } catch(e) {}
    })();

    // Safe localStorage helpers
    function safeGetStorage(key) {
        try { return localStorage.getItem(key); } catch(e) { return null; }
    }
    function safeSetStorage(key, val) {
        try { localStorage.setItem(key, val); } catch(e) {}
    }



    // Client-side Pending Intent Context Sync
    document.addEventListener('DOMContentLoaded', function() {
        try {
            const raw = safeGetStorage('lumbarong_pending_intent');
            if (raw) {
                const intent = JSON.parse(raw);
                const hiddenInput = document.getElementById('hidden_pending_intent');
                if (hiddenInput && (!hiddenInput.value || hiddenInput.value === 'null')) {
                    hiddenInput.value = JSON.stringify(intent);
                }
                const iconEl = document.getElementById('js-pending-icon');
                const titleEl = document.getElementById('js-pending-title');
                const msgEl = document.getElementById('js-pending-message');

                if (intent.action === 'chat') {
                    if (iconEl) iconEl.textContent = '💬';
                    if (titleEl) titleEl.textContent = 'Chat Request Saved';
                    if (msgEl) msgEl.innerHTML = 'Register to open the message box with <strong>' + (intent.sellerName || 'Artisan') + '</strong>.';
                } else if (intent.action === 'view_shop') {
                    if (iconEl) iconEl.textContent = '🏪';
                    if (titleEl) titleEl.textContent = 'Shop View Saved';
                    if (msgEl) msgEl.innerHTML = 'Register to return directly to the shop view.';
                }

                const banner = document.getElementById('js-pending-banner');
                if (banner && banner.classList.contains('hidden')) {
                    banner.classList.remove('hidden');
                }
            }
        } catch(e) {}
    });

    // Auto-reload on Back/Forward navigation from bfcache to get fresh CSRF token
    window.addEventListener('pageshow', function(event) {
        if (event.persisted || (window.performance && window.performance.navigation && window.performance.navigation.type === 2)) {
            window.location.reload();
        }
    });
</script>
</body>
</html>