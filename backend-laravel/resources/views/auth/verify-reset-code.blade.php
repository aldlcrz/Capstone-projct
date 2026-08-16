<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Reset Code | LumBarong</title>
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
<body class="min-h-screen flex items-center justify-center p-6 relative overflow-hidden">
    <div class="absolute top-0 right-0 w-140 h-140 rounded-full -translate-y-1/2 translate-x-1/3 blur-3xl opacity-[0.04] pointer-events-none bg-[#C0422A]"></div>

    <div class="w-full max-w-md bg-white rounded-[2.5rem] border border-[#E5DDD5] p-8 shadow-[0_20px_60px_rgba(60,40,20,0.08)] relative z-10"
         x-data="{
             email: '{{ session('reset_email', $email ?? request('email', '')) }}',
             code: '',
             showAiHelper: false
         }">
        
        <div class="relative mb-6 text-center">
            <a href="{{ route('password.request') }}" class="absolute left-0 top-1/2 -translate-y-1/2 w-10 h-10 rounded-full bg-[#F9F6F2] flex items-center justify-center hover:bg-gray-100 text-gray-600 hover:text-[#C0422A] transition-all shadow-sm" title="Back">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
            </a>
            <div class="flex justify-center mb-2">
                <img src="{{ asset('images/logo-icon.png') }}" alt="LumBarong Logo" class="w-12 h-12 object-contain rounded-full shadow-md hover:scale-105 transition-transform">
            </div>
            <h1 class="font-serif text-2xl font-black italic tracking-tight text-[#C0422A] mb-1">LumBarong</h1>
            <p class="text-[11px] font-bold uppercase tracking-[0.3em] text-gray-400">Password Recovery</p>
        </div>

        <div class="text-center mb-6">
            <div class="w-14 h-14 bg-[#C0420A]/10 text-[#C0420A] rounded-2xl flex items-center justify-center mx-auto mb-3 shadow-xs">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                </svg>
            </div>
            <h2 class="text-lg font-black text-gray-900 tracking-tight">Enter Reset Code</h2>
            <p class="mt-1 text-xs font-medium text-gray-500">
                Please enter the 6-digit code sent to:
            </p>
            <div class="mt-1.5 font-bold text-gray-900 text-xs bg-gray-50 py-1 px-3 rounded-xl border border-gray-200 inline-block max-w-full truncate" x-text="email || 'your registered Gmail'"></div>
        </div>

        @if(session('success'))
            <div class="mb-4 bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-2xl text-xs font-bold flex items-center gap-2">
                <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        <form action="{{ route('password.verify.code.submit') }}" method="POST" class="space-y-4">
            @csrf
            <input type="hidden" name="email" :value="email">

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
                Verify Code & Proceed
            </button>
        </form>

        <div class="mt-6 pt-4 border-t border-gray-100 text-center">
            <a href="{{ route('password.request') }}" class="text-xs font-bold text-[#C0422A] hover:underline">
                ← Request a new reset code
            </a>
        </div>
    </div>
</body>
</html>
