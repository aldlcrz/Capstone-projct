<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <meta name="color-scheme" content="light">
    <title>Verify Your Gmail | LumBarong</title>
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
<body class="min-h-screen flex items-center justify-center p-6 relative overflow-hidden">
    <!-- Subtle warm blobs -->
    <div class="absolute top-0 right-0 w-140 h-140 rounded-full -translate-y-1/2 translate-x-1/3 blur-3xl opacity-[0.04] pointer-events-none bg-[#C0422A]"></div>
    <div class="absolute bottom-0 left-0 w-95 h-95 rounded-full translate-y-1/2 -translate-x-1/3 blur-3xl opacity-[0.12] pointer-events-none bg-[#D4B896]"></div>

    <div class="verify-card w-full max-w-md bg-white rounded-[2.5rem] border border-[#E5DDD5] p-8 shadow-[0_20px_60px_rgba(60,40,20,0.08)] relative z-10"
         x-data="{
             email: '{{ session('verify_email', $email ?? (auth()->user()->email ?? '')) }}',
             code: '',
             timeLeft: {{ (int) ($remainingSeconds ?? 0) }},
             resendCooldown: {{ (int) ($resendCooldown ?? 0) }},
             timerInterval: null,
             init() {
                 this.timerInterval = setInterval(() => {
                     if (this.timeLeft > 0) {
                         this.timeLeft--;
                     }
                     if (this.resendCooldown > 0) {
                         this.resendCooldown--;
                     }
                     if (this.timeLeft <= 0 && this.resendCooldown <= 0) {
                         clearInterval(this.timerInterval);
                     }
                 }, 1000);
             },
             get formattedTime() {
                 const m = Math.floor(Math.max(0, this.timeLeft) / 60);
                 const s = Math.max(0, this.timeLeft) % 60;
                 return String(m).padStart(2, '0') + ':' + String(s).padStart(2, '0');
             }
         }">
        
        <div class="relative mb-6 text-center">
            <!-- Back to Login / Home -->
            <a href="/login" class="back-btn absolute left-0 top-1/2 -translate-y-1/2 w-10 h-10 rounded-full bg-[#F9F6F2] flex items-center justify-center hover:bg-gray-100 text-gray-600 hover:text-[#C0422A] transition-all shadow-sm" title="Back to Login">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
            </a>
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
                       @input="code = $event.target.value.replace(/\D/g, '').slice(0, 6)"
                       @paste.prevent="
                           const pasted = ($event.clipboardData || window.clipboardData).getData('text');
                           code = pasted.replace(/\D/g, '').slice(0, 6);
                       "
                       maxlength="6" 
                       required 
                       pattern="[0-9]{6}" 
                       inputmode="numeric"
                       autocomplete="one-time-code"
                       placeholder="" 
                       autofocus
                       class="w-full text-center text-3xl font-black tracking-[0.35em] px-4 py-3 bg-[#F9F6F2] border-2 {{ $errors->has('code') ? 'border-red-400' : 'border-transparent' }} focus:border-[#C0422A] focus:bg-white rounded-2xl outline-none transition-all">
                @error('code')
                    <p class="text-xs font-bold text-red-500 text-center mt-2">{{ $message }}</p>
                @enderror
            </div>

            <!-- Real-time 5-minute countdown display -->
            <div class="flex items-center justify-center pt-1 pb-1">
                <template x-if="timeLeft > 0">
                    <div class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full bg-amber-50 border border-amber-200/80 text-amber-800 text-xs font-semibold shadow-xs">
                        <svg class="w-3.5 h-3.5 text-amber-600 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>Code expires in: <strong class="font-mono font-bold text-amber-900" x-text="formattedTime">05:00</strong></span>
                    </div>
                </template>
                <template x-if="timeLeft <= 0">
                    <div class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full bg-red-50 border border-red-200 text-red-700 text-xs font-semibold shadow-xs">
                        <svg class="w-3.5 h-3.5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>Code expired. Please request a new code below.</span>
                    </div>
                </template>
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
                
                <!-- While timer > 0: Locked/Disabled until code expires -->
                <button type="button" 
                        x-show="timeLeft > 0"
                        disabled 
                        class="text-xs font-semibold text-gray-400 bg-gray-100 px-4 py-2 rounded-full cursor-not-allowed inline-flex items-center gap-1.5 transition-all">
                    <span>Resend Code to Gmail</span>
                    <span class="font-mono text-[11px] text-gray-500 font-bold" x-text="'(wait ' + formattedTime + ')'"></span>
                </button>

                <!-- When timer reaches 0: Clickable -->
                <button type="submit" 
                        x-show="timeLeft <= 0"
                        x-cloak
                        class="text-xs font-bold text-white bg-[#C0422A] hover:bg-[#A03520] px-5 py-2.5 rounded-full cursor-pointer inline-flex items-center gap-2 shadow-md hover:shadow-lg transition-all">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    <span>Resend Code to Gmail</span>
                </button>
            </form>
            <p class="mt-3 text-[10px] text-gray-400 leading-relaxed px-2">
                💡 Can't find the email? Check your <span class="font-semibold text-gray-500">Spam</span> or <span class="font-semibold text-gray-500">Junk</span> folder — it may have been filtered automatically.
            </p>
        </div>
    </div>
</body>
</html>
