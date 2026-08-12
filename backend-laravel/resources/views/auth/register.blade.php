<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | LumBarong</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Playfair+Display:wght@700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background: #F7F3EE; transition: background 0.3s, color 0.3s; }
        .font-serif { font-family: 'Playfair Display', serif; }
        [x-cloak] { display: none !important; }

        body.dark { background-color: #121212 !important; color: #f3f4f6 !important; }
        body.dark .login-card { background-color: #1e1e1e !important; border-color: #333333 !important; }
        body.dark .login-card input { background-color: #2a2a2a !important; color: #ffffff !important; border-color: #444 !important; }
        body.dark .login-card label,
        body.dark .login-card p { color: #a1a1aa !important; }
        body.dark .back-btn { background-color: #2a2a2a !important; color: #d4d4d8 !important; }
        body.dark .login-card .text-gray-400 { color: #9ca3af !important; }
    </style>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="min-h-screen flex items-center justify-center p-6 relative overflow-hidden">
    <!-- Subtle warm blobs -->
    <div class="absolute top-0 right-0 w-140 h-140 rounded-full -translate-y-1/2 translate-x-1/3 blur-3xl opacity-[0.04] pointer-events-none bg-[#C0422A]"></div>
    <div class="absolute bottom-0 left-0 w-95 h-95 rounded-full translate-y-1/2 -translate-x-1/3 blur-3xl opacity-[0.12] pointer-events-none bg-[#D4B896]"></div>

    <div class="login-card w-full max-w-md bg-white rounded-[2.5rem] border border-[#E5DDD5] p-8 shadow-[0_20px_60px_rgba(60,40,20,0.08)] relative z-10">
        <div class="relative mb-10 text-center">
            <!-- Back to Home -->
            <a href="/" class="back-btn absolute left-0 top-1/2 -translate-y-1/2 w-10 h-10 rounded-full bg-[#F9F6F2] flex items-center justify-center hover:bg-gray-100 text-gray-600 hover:text-[#C0422A] transition-all shadow-sm" title="Back to Home">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
            </a>
            <!-- Dark / Light Mode Toggle -->
            <button id="dark-mode-toggle"
                onclick="toggleDarkMode()"
                title="Toggle dark / light mode"
                class="back-btn absolute right-0 top-1/2 -translate-y-1/2 w-10 h-10 rounded-full bg-[#F9F6F2] flex items-center justify-center text-gray-500 hover:bg-gray-100 hover:text-[#C0422A] transition-all shadow-sm"
                aria-label="Toggle dark mode">
                <svg id="icon-sun" class="w-4 h-4 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364-6.364l-.707.707M6.343 17.657l-.707.707M17.657 17.657l-.707-.707M6.343 6.343l-.707-.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                </svg>
                <svg id="icon-moon" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                </svg>
            </button>
            <h1 class="font-serif text-2xl font-black italic tracking-tight text-[#C0422A] mb-1">LumBarong</h1>
            <p class="text-[11px] font-bold uppercase tracking-[0.3em] text-gray-400">Join the Collective</p>
        </div>

        @if($errors->any())
        <div 
            x-data="{ show: true, init() { setTimeout(() => this.show = false, 7000) } }"
            x-show="show"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-2"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed top-6 right-6 z-9999 w-full max-w-sm bg-white/95 backdrop-blur-md rounded-2xl shadow-2xl border border-gray-100 p-4 flex items-start gap-3.5"
            style="display: none;"
            x-cloak
        >
            <div class="w-8 h-8 rounded-full bg-red-50 flex items-center justify-center text-red-600 shrink-0 shadow-sm border border-red-100">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path></svg>
            </div>
            <div class="grow pt-0.5">
                <h4 class="text-xs font-black text-black uppercase tracking-wider">Registration Error</h4>
                <p class="text-xs text-gray-500 font-medium mt-0.5 leading-relaxed">{{ $errors->first() }}</p>
            </div>
            <button @click="show = false" class="text-gray-300 hover:text-gray-500 transition-colors shrink-0">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>
        @endif

        <form action="/register" method="POST" class="space-y-4" id="register-form">
            @csrf

            @php
                $pendingIntent = session('pending_intent');
                $pendingAction = $pendingIntent['action'] ?? 'add_to_cart';
                $pendingProduct = !empty($pendingIntent['productId']) ? \App\Models\Product::find($pendingIntent['productId']) : null;
                $pendingSellerName = $pendingIntent['sellerName'] ?? 'Artisan';
            @endphp

            <div id="js-pending-banner" class="{{ empty($pendingIntent) ? 'hidden' : '' }} p-4 rounded-2xl bg-amber-50 border border-amber-200 text-amber-900 text-xs font-medium flex items-start gap-3 shadow-sm mb-4">
                <div class="text-xl shrink-0" id="js-pending-icon">
                    @if($pendingAction === 'chat') 💬 @elseif($pendingAction === 'view_shop') 🏪 @else 🛍️ @endif
                </div>
                <div class="flex-1">
                    <h4 class="font-black uppercase tracking-wider text-[10px] text-[#C0422A] mb-0.5" id="js-pending-title">
                        @if($pendingAction === 'chat') Chat Request Saved @elseif($pendingAction === 'view_shop') Shop View Saved @else Selection Saved @endif
                    </h4>
                    <p class="leading-relaxed text-amber-800 text-xs" id="js-pending-message">
                        @if($pendingAction === 'chat')
                            Register to open the message box with <strong>{{ $pendingSellerName }}</strong>.
                        @elseif($pendingAction === 'view_shop')
                            Register to return directly to the shop view.
                        @else
                            Register to complete adding 
                            <strong id="js-pending-product-name">{{ $pendingProduct ? $pendingProduct->name : 'your selected item' }}</strong>
                            @if(!empty($pendingIntent['size'])) (Size: {{ $pendingIntent['size'] }}) @endif
                            to your account and proceed.
                        @endif
                    </p>
                </div>
            </div>

            <input type="hidden" name="pending_intent" id="hidden_pending_intent" value="{{ json_encode($pendingIntent) }}">
            <div class="space-y-1">
                <label class="text-[10px] font-bold uppercase tracking-widest px-5 block text-gray-500">Username</label>
                <input 
                    type="text" 
                    name="username" 
                    value="{{ old('username') }}"
                    required 
                    class="w-full h-12 bg-[#F9F6F2] rounded-full px-8 text-sm font-medium border-2 border-transparent focus:border-[#C0422A] focus:bg-white outline-none transition-all"
                >
            </div>

            <div class="space-y-1">
                <label class="text-[10px] font-bold uppercase tracking-widest px-5 block text-gray-500">Email Address</label>
                <input 
                    type="email" 
                    name="email" 
                    value="{{ old('email') }}"
                    required 
                    class="w-full h-12 bg-[#F9F6F2] rounded-full px-8 text-sm font-medium border-2 border-transparent focus:border-[#C0422A] focus:bg-white outline-none transition-all"
                >
            </div>

            <div class="space-y-1" x-data="{ showPass: false }">
                <label class="text-[10px] font-bold uppercase tracking-widest px-5 block text-gray-500">Password</label>
                <div class="relative flex items-center">
                    <input 
                        :type="showPass ? 'text' : 'password'" 
                        name="password" 
                        required 
                        class="w-full h-12 bg-[#F9F6F2] rounded-full px-8 pr-14 text-sm font-medium border-2 border-transparent focus:border-[#C0422A] focus:bg-white outline-none transition-all"
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
            </div>

            <div class="space-y-1" x-data="{ showConfirm: false }">
                <label class="text-[10px] font-bold uppercase tracking-widest px-5 block text-gray-500">Confirm Password</label>
                <div class="relative flex items-center">
                    <input 
                        :type="showConfirm ? 'text' : 'password'" 
                        name="password_confirmation" 
                        required 
                        class="w-full h-12 bg-[#F9F6F2] rounded-full px-8 pr-14 text-sm font-medium border-2 border-transparent focus:border-[#C0422A] focus:bg-white outline-none transition-all"
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
            </div>

            <div class="px-2 pt-2 flex items-start gap-2.5">
                <input type="checkbox" name="terms_consent" id="terms_consent" required class="mt-0.5 rounded text-[#C0422A] focus:ring-[#C0422A] cursor-pointer shrink-0">
                <label for="terms_consent" class="text-xs text-gray-500 leading-snug">
                    I have read and agree to the <a href="/terms" target="_blank" class="text-[#C0422A] font-bold hover:underline">Terms and Conditions</a> and <a href="/privacy" target="_blank" class="text-[#C0422A] font-bold hover:underline">Privacy Policy</a>.
                </label>
            </div>

            <button type="submit" class="w-full h-14 bg-[#3D2B1F] text-white rounded-full font-bold uppercase tracking-[0.2em] text-[10px] shadow-xl shadow-black/10 hover:bg-[#C0422A] transition-all mt-4">
                Register
            </button>
        </form>

        <div class="mt-10 text-center">
            <p class="text-[10px] font-bold uppercase tracking-widest text-gray-400">
                Already have an account? 
                <a href="/login" class="text-[#C0422A] ml-1">Log-In</a>
            </p>
        </div>
    </div>

<script>
    (function() {
        const saved = localStorage.getItem('lumbarong_theme');
        if (saved === 'dark') {
            document.body.classList.add('dark');
            document.getElementById('icon-sun').classList.remove('hidden');
            document.getElementById('icon-moon').classList.add('hidden');
        }
    })();

    function toggleDarkMode() {
        const body = document.body;
        const isDark = body.classList.toggle('dark');
        const sun = document.getElementById('icon-sun');
        const moon = document.getElementById('icon-moon');
        if (isDark) {
            sun.classList.remove('hidden');
            moon.classList.add('hidden');
            localStorage.setItem('lumbarong_theme', 'dark');
        } else {
            sun.classList.add('hidden');
            moon.classList.remove('hidden');
            localStorage.setItem('lumbarong_theme', 'light');
        }
    }

    // Client-side Pending Intent Context Sync
    document.addEventListener('DOMContentLoaded', function() {
        try {
            const raw = localStorage.getItem('lumbarong_pending_intent');
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
</script>
</body>
</html>