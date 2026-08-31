<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Set New Password | LumBarong</title>
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
        
        <div class="relative mb-6 text-center">
            <a href="/login" class="absolute left-0 top-1/2 -translate-y-1/2 w-10 h-10 rounded-full bg-[#F9F6F2] flex items-center justify-center hover:bg-gray-100 text-gray-600 hover:text-[#C0422A] transition-all shadow-sm" title="Back to Login">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
            </a>
            <div class="flex justify-center mb-2">
                <img src="{{ asset('images/logo-icon.png') }}" alt="LumBarong Logo" class="w-12 h-12 object-contain rounded-full shadow-md hover:scale-105 transition-transform">
            </div>
            <h1 class="font-serif text-2xl font-black tracking-tight text-gray-900 mb-1">LumBarong</h1>
            <p class="text-[11px] font-bold uppercase tracking-[0.3em] text-gray-400">Set New Password</p>
        </div>

        <div class="text-center mb-6">
            <h2 class="text-lg font-black text-gray-900 tracking-tight">Create New Password</h2>
            <p class="mt-1 text-xs font-medium text-gray-500">
                Setting new credentials for <span class="font-bold text-gray-800">{{ session('validated_reset_email', request('email')) }}</span>
            </p>
        </div>

        <form action="{{ route('password.update.submit') }}" method="POST" class="space-y-4">
            @csrf
            <input type="hidden" name="email" value="{{ session('validated_reset_email', request('email')) }}">

            {{-- New Password --}}
            <div class="space-y-1">
                <label class="text-[10px] font-bold uppercase tracking-widest px-5 block text-gray-500">New Password</label>
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
                        <span class="text-gray-400">Min. 6 chars</span>
                    </div>
                </div>

                @error('password')
                    <p class="text-xs font-bold text-red-500 px-5 mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Confirm Password --}}
            <div class="space-y-1">
                <label class="text-[10px] font-bold uppercase tracking-widest px-5 block text-gray-500">Confirm Password</label>
                <div class="relative flex items-center">
                    <input 
                        :type="showConfirm ? 'text' : 'password'" 
                        name="password_confirmation" 
                        x-model="password_confirmation"
                        required 
                        placeholder="Re-enter new password"
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

            <button type="submit" class="w-full h-14 bg-[#3D2B1F] text-white rounded-full font-bold uppercase tracking-[0.2em] text-[10px] shadow-xl shadow-black/10 hover:bg-[#C0422A] transition-all mt-4">
                Update Password
            </button>
        </form>
    </div>
</body>
</html>
