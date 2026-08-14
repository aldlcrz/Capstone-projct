@extends('layouts.app')

@section('title', 'Set New Password - LumBarong')

@section('content')
<div class="min-h-screen bg-linear-to-b from-amber-50/50 via-white to-gray-50 flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-6 bg-white p-8 sm:p-10 rounded-3xl shadow-xl border border-gray-100"
         x-data="{
             password: '',
             passwordConfirmation: '',
             showPass: false,
             showConfirm: false,
             aiStrength: { score: 'Weak', percent: 10, color: 'text-gray-400', advice: 'Enter a strong password.' },
             
             checkSecurity() {
                 const p = this.password;
                 if (!p) {
                     this.aiStrength = { score: 'Empty', percent: 0, color: 'text-gray-400', advice: 'Enter at least 8 characters.' };
                     return;
                 }
                 const len = p.length;
                 const hasUpper = /[A-Z]/.test(p);
                 const hasLower = /[a-z]/.test(p);
                 const hasDigit = /[0-9]/.test(p);
                 const hasSymbol = /[^A-Za-z0-9]/.test(p);

                 let pool = (hasLower ? 26 : 0) + (hasUpper ? 26 : 0) + (hasDigit ? 10 : 0) + (hasSymbol ? 32 : 0);
                 let entropy = pool > 0 ? len * Math.log2(pool) : 0;
                 let pct = Math.min(100, Math.round((entropy / 70) * 100));

                 let score = 'Weak';
                 let color = 'text-red-500';
                 let barColor = 'bg-red-500';

                 if (len >= 8 && (hasUpper || hasSymbol) && hasDigit) {
                     score = 'Fair';
                     color = 'text-amber-500';
                     barColor = 'bg-amber-500';
                 }
                 if (len >= 10 && hasUpper && hasLower && hasDigit && hasSymbol) {
                     score = 'Strong';
                     color = 'text-emerald-600';
                     barColor = 'bg-emerald-500';
                 }
                 if (len >= 12 && entropy >= 70) {
                     score = 'Very Strong';
                     color = 'text-emerald-700';
                     barColor = 'bg-emerald-600';
                 }

                 let advice = len < 8 ? 'Must be at least 8 characters long.' : 
                             (!hasUpper ? 'Tip: Add an uppercase letter (A-Z) for higher strength.' : 
                             (!hasSymbol ? 'Tip: Include a special character (!@#$%^&*).' : '✨ Excellent entropy! Robust against dictionary & brute-force attacks.'));

                 this.aiStrength = { score, percent: pct, color, barColor, advice };
             }
         }">
        
        <div class="text-center">
            <div class="w-16 h-16 bg-[#C0420A]/10 text-[#C0420A] rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-sm">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                </svg>
            </div>
            <h2 class="text-2xl font-black text-gray-900 tracking-tight">Create New Password</h2>
            <p class="mt-2 text-xs font-semibold text-gray-500">
                Setting new credentials for <span class="font-bold text-gray-800">{{ session('validated_reset_email', request('email')) }}</span>.
            </p>
        </div>

        @if($errors->any())
            <div class="bg-rose-50 border border-rose-200 text-rose-700 px-4 py-3 rounded-2xl text-xs font-bold flex items-center gap-2">
                <svg class="w-4 h-4 text-rose-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                <span>{{ $errors->first() }}</span>
            </div>
        @endif

        <form action="{{ route('password.update.submit') }}" method="POST" class="mt-6 space-y-4">
            @csrf
            <input type="hidden" name="email" value="{{ session('validated_reset_email', request('email')) }}">

            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">New Password</label>
                <div class="relative">
                    <input :type="showPass ? 'text' : 'password'" 
                           name="password" 
                           x-model="password"
                           @input="checkSecurity()"
                           required 
                           minlength="8" 
                           placeholder="••••••••"
                           class="w-full px-4 py-3 pr-12 bg-gray-50 border border-gray-200 rounded-xl outline-none focus:border-[#C0420A] focus:ring-2 focus:ring-[#C0420A]/10 text-sm font-semibold transition-all">
                    <button type="button" @click="showPass = !showPass" class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-[#C0420A]">
                        <svg x-show="!showPass" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                        <svg x-show="showPass" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" x-cloak><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.477 0-8.268-2.943-9.542-7a9.97 9.97 0 012.458-4.017M6.53 6.53A9.97 9.97 0 0112 5c4.478 0 8.268 2.943 9.542 7a10.05 10.05 0 01-4.132 5.411M15 12a3 3 0 00-3-3m0 0a3 3 0 00-3 3m3-3L3 3m18 18L9 9"/></svg>
                    </button>
                </div>

                <!-- AI Password Security Advisor -->
                <div x-show="password.length > 0" x-cloak class="mt-2.5 bg-gray-50 border border-gray-200 rounded-xl p-3 space-y-1.5" x-transition>
                    <div class="flex items-center justify-between text-[11px] font-bold">
                        <span class="flex items-center gap-1.5 text-gray-700">
                            <span>✨</span>
                            <span>AI Security Score:</span>
                            <span :class="aiStrength.color" x-text="aiStrength.score"></span>
                        </span>
                        <span class="text-gray-400 font-mono" x-text="aiStrength.percent + '%'"></span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-1.5 overflow-hidden">
                        <div class="h-full rounded-full transition-all duration-300"
                             :class="aiStrength.barColor || 'bg-gray-400'"
                             :style="'width: ' + aiStrength.percent + '%'"></div>
                    </div>
                    <p class="text-[10px] text-gray-600 font-medium leading-tight" x-text="aiStrength.advice"></p>
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">Confirm New Password</label>
                <div class="relative">
                    <input :type="showConfirm ? 'text' : 'password'" 
                           name="password_confirmation" 
                           x-model="passwordConfirmation"
                           required 
                           minlength="8" 
                           placeholder="••••••••"
                           class="w-full px-4 py-3 pr-12 bg-gray-50 border border-gray-200 rounded-xl outline-none focus:border-[#C0420A] focus:ring-2 focus:ring-[#C0420A]/10 text-sm font-semibold transition-all">
                    <button type="button" @click="showConfirm = !showConfirm" class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-[#C0420A]">
                        <svg x-show="!showConfirm" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                        <svg x-show="showConfirm" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" x-cloak><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.477 0-8.268-2.943-9.542-7a9.97 9.97 0 012.458-4.017M6.53 6.53A9.97 9.97 0 0112 5c4.478 0 8.268 2.943 9.542 7a10.05 10.05 0 01-4.132 5.411M15 12a3 3 0 00-3-3m0 0a3 3 0 00-3 3m3-3L3 3m18 18L9 9"/></svg>
                    </button>
                </div>
                <div x-show="passwordConfirmation && password !== passwordConfirmation" class="text-xs font-bold text-red-500 mt-1" x-cloak>
                    Passwords do not match.
                </div>
            </div>

            <button type="submit" 
                    :disabled="password.length < 8 || password !== passwordConfirmation"
                    :class="(password.length >= 8 && password === passwordConfirmation) ? 'bg-[#C0420A] hover:bg-[#a33708] shadow-lg shadow-[#C0420A]/20 cursor-pointer' : 'bg-gray-300 opacity-60 cursor-not-allowed'"
                    class="w-full py-4 text-white font-black text-sm uppercase tracking-wider rounded-2xl transition-all">
                Update Password & Log In
            </button>
        </form>
    </div>
</div>
@endsection
