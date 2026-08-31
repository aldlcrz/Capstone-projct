<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>Verify Your Gmail | LumBarong</title>
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('images/apple-touch-icon.png') }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Playfair+Display:wght@700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background: #F7F3EE; transition: background 0.3s, color 0.3s; }
        .font-serif { font-family: 'Playfair Display', serif; }
        [x-cloak] { display: none !important; }

        body.dark { background-color: #121212 !important; color: #f3f4f6 !important; }
        body.dark .verify-card { background-color: #1e1e1e !important; border-color: #333333 !important; }
        body.dark .verify-card input { background-color: #2a2a2a !important; color: #ffffff !important; border-color: #444 !important; }
        body.dark .verify-card label,
        body.dark .verify-card p { color: #a1a1aa !important; }
        body.dark .back-btn { background-color: #2a2a2a !important; color: #d4d4d8 !important; }
        body.dark .verify-card .text-gray-400 { color: #9ca3af !important; }
    </style>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="min-h-screen flex items-center justify-center p-6 relative overflow-hidden">
    <!-- Subtle warm blobs -->
    <div class="absolute top-0 right-0 w-140 h-140 rounded-full -translate-y-1/2 translate-x-1/3 blur-3xl opacity-[0.04] pointer-events-none bg-[#C0422A]"></div>
    <div class="absolute bottom-0 left-0 w-95 h-95 rounded-full translate-y-1/2 -translate-x-1/3 blur-3xl opacity-[0.12] pointer-events-none bg-[#D4B896]"></div>

    <div class="verify-card w-full max-w-md bg-white rounded-[2.5rem] border border-[#E5DDD5] p-8 shadow-[0_20px_60px_rgba(60,40,20,0.08)] relative z-10"
         x-data="{
             email: '{{ session('verify_email', $email ?? (auth()->user()->email ?? '')) }}',
             code: '',
             showAiHelper: false
         }">
        
        <div class="relative mb-6 text-center">
            <!-- Back to Login / Home -->
            <a href="/login" class="back-btn absolute left-0 top-1/2 -translate-y-1/2 w-10 h-10 rounded-full bg-[#F9F6F2] flex items-center justify-center hover:bg-gray-100 text-gray-600 hover:text-[#C0422A] transition-all shadow-sm" title="Back to Login">
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
            <div class="flex justify-center mb-2">
                <img src="{{ asset('images/logo-icon.png') }}" alt="LumBarong Logo" class="w-12 h-12 object-contain rounded-full shadow-md hover:scale-105 transition-transform">
            </div>
            <h1 class="font-serif text-2xl font-black tracking-tight text-gray-900 mb-1">LumBarong</h1>
            <p class="text-[11px] font-bold uppercase tracking-[0.3em] text-gray-400">Account Verification</p>
        </div>

        <div class="text-center mb-6">
            <div class="w-14 h-14 bg-[#C0420A]/10 text-[#C0420A] rounded-2xl flex items-center justify-center mx-auto mb-3 shadow-xs">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 002-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                </svg>
            </div>
            <h2 class="text-lg font-black text-gray-900 tracking-tight">Check Your Inbox</h2>
            <p class="mt-1 text-xs font-medium text-gray-500">
                We sent a 6-digit verification code to:
            </p>
            <div class="mt-1.5 font-bold text-gray-900 text-xs bg-gray-50 py-1 px-3 rounded-xl border border-gray-200 inline-block max-w-full truncate" x-text="email || 'your registered Gmail'"></div>
        </div>

        @if(session('success'))
            <div class="mb-4 bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-2xl text-xs font-bold flex items-center gap-2">
                <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if(session('warning'))
            <div class="mb-4 bg-amber-50 border border-amber-200 text-amber-800 px-4 py-3 rounded-2xl text-xs font-bold flex items-center gap-2">
                <svg class="w-4 h-4 text-amber-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                <span>{{ session('warning') }}</span>
            </div>
        @endif

        <form action="{{ route('verify.email.submit') }}" method="POST" class="space-y-4">
            @csrf
            <input type="hidden" name="email" value="{{ session('verify_email', $email ?? '') }}" :value="email">

            <!-- Fallback email input if empty -->
            <div x-show="!email" class="space-y-1" x-cloak>
                <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-500">Your Registered Gmail</label>
                <input type="email" x-model="email" placeholder="example@gmail.com" required
                       class="w-full px-4 py-3 bg-[#F9F6F2] border-2 border-transparent focus:border-[#C0422A] focus:bg-white rounded-full text-sm font-bold outline-none transition-all">
                @error('email')
                    <p class="text-xs font-bold text-red-500 px-5 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-widest text-gray-500 mb-2 text-center">6-Digit Code</label>
                <input type="text" 
                       name="code" 
                       x-model="code"
                       maxlength="6" 
                       required 
                       pattern="[0-9]{6}" 
                       inputmode="numeric"
                       placeholder="" 
                       autofocus
                       class="w-full text-center text-3xl font-black tracking-[0.35em] px-4 py-3 bg-[#F9F6F2] border-2 {{ $errors->has('code') ? 'border-red-400' : 'border-transparent' }} focus:border-[#C0422A] focus:bg-white rounded-2xl outline-none transition-all">
                @error('code')
                    <p class="text-xs font-bold text-red-500 text-center mt-2">{{ $message }}</p>
                @enderror
            </div>

            <button type="submit" 
                    :disabled="code.length !== 6"
                    :class="code.length === 6 ? 'bg-[#3D2B1F] hover:bg-[#C0422A] shadow-xl shadow-black/10 cursor-pointer' : 'bg-gray-300 opacity-60 cursor-not-allowed'"
                    class="w-full h-14 text-white font-bold uppercase tracking-[0.2em] text-[10px] rounded-full transition-all">
                Activate Account
            </button>
        </form>

        <div class="mt-6 pt-4 border-t border-gray-100 text-center">
            <p class="text-xs text-gray-400 mb-2">Didn't receive the email?</p>
            <form action="{{ route('verify.email.resend') }}" method="POST">
                @csrf
                <input type="hidden" name="email" :value="email">
                <button type="submit" class="text-xs font-bold text-[#C0422A] hover:underline cursor-pointer">
                    Resend Code to Gmail
                </button>
            </form>
            <p class="mt-3 text-[10px] text-gray-400 leading-relaxed px-2">
                💡 Can't find the email? Check your <span class="font-semibold text-gray-500">Spam</span> or <span class="font-semibold text-gray-500">Junk</span> folder — it may have been filtered automatically.
            </p>
        </div>
    </div>

<script>
    function safeGetStorage(key) {
        try { return localStorage.getItem(key); } catch(e) { return null; }
    }
    function safeSetStorage(key, val) {
        try { localStorage.setItem(key, val); } catch(e) {}
    }

    (function() {
        const saved = safeGetStorage('lumbarong_theme');
        if (saved === 'dark') {
            document.body.classList.add('dark');
            document.getElementById('icon-sun')?.classList.remove('hidden');
            document.getElementById('icon-moon')?.classList.add('hidden');
        }
    })();

    function toggleDarkMode() {
        const body = document.body;
        const isDark = body.classList.toggle('dark');
        const sun = document.getElementById('icon-sun');
        const moon = document.getElementById('icon-moon');
        if (isDark) {
            sun?.classList.remove('hidden');
            moon?.classList.add('hidden');
            safeSetStorage('lumbarong_theme', 'dark');
        } else {
            sun?.classList.add('hidden');
            moon?.classList.remove('hidden');
            safeSetStorage('lumbarong_theme', 'light');
        }
    }
</script>
</body>
</html>
