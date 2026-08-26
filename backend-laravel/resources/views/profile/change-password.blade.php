@extends('layouts.app')

@section('content')
<div class="max-w-275 mx-auto py-8 px-4 sm:px-6">
    <div class="flex flex-col md:flex-row gap-6">

        @include('profile._sidebar', ['user' => $user])

        @php
            $emailParts = explode('@', $user->email);
            $namePart = $emailParts[0];
            $domainPart = $emailParts[1] ?? 'gmail.com';
            $maskedName = strlen($namePart) <= 3 
                ? substr($namePart, 0, 1) . '***' 
                : substr($namePart, 0, 2) . str_repeat('*', max(3, strlen($namePart) - 3)) . substr($namePart, -1);
            $maskedEmail = $maskedName . '@' . $domainPart;
        @endphp

        <main class="flex-1 bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden" 
              x-data="passwordSecurityManager('{{ route('profile.change-password.send-code') }}', '{{ route('profile.change-password.verify-code') }}', '{{ csrf_token() }}')">

            {{-- Header --}}
            <div class="px-6 sm:px-8 py-6 border-b border-gray-100 flex items-center justify-between">
                <div>
                    <h1 class="text-lg font-bold text-gray-900">Change Password</h1>
                    <p class="text-xs text-gray-400 mt-0.5">Two-step verified password change to keep your account safe.</p>
                </div>
                <div class="hidden sm:flex items-center gap-2 px-3 py-1 rounded-full bg-amber-50 border border-amber-200 text-amber-800 text-[10px] font-bold uppercase tracking-wider">
                    <span>🛡️ 2-Step Verified</span>
                </div>
            </div>

            {{-- Validation Alerts --}}
            @if($errors->any())
            <div class="mx-6 sm:mx-8 mt-6 p-4 rounded-2xl bg-red-50 border border-red-200 text-red-900 text-xs flex items-start gap-3">
                <div class="w-5 h-5 rounded-full bg-red-100 flex items-center justify-center text-red-600 shrink-0 font-bold">!</div>
                <div class="grow">
                    <h4 class="font-bold mb-1">Please fix the following:</h4>
                    <ul class="list-disc list-inside space-y-0.5 text-red-700">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
            @endif

            @if(session('success'))
            <div class="mx-6 sm:mx-8 mt-6 p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-900 text-xs flex items-start gap-3">
                <div class="w-5 h-5 rounded-full bg-emerald-100 flex items-center justify-center text-emerald-600 shrink-0 font-bold">✓</div>
                <p class="font-semibold">{{ session('success') }}</p>
            </div>
            @endif

            <form action="{{ route('profile.change-password.submit') }}" method="POST" class="px-6 sm:px-8 py-6 sm:py-8 max-w-xl space-y-6" @submit="isSubmitting = true">
                @csrf

                {{-- Step 1: Security Verification Card --}}
                <div class="p-5 rounded-2xl bg-gray-50/70 border border-gray-200/80 space-y-4">
                    <div class="flex items-center justify-between border-b border-gray-200/60 pb-3">
                        <div class="flex items-center gap-2">
                            <span class="w-6 h-6 rounded-full bg-[#C0420A] text-white flex items-center justify-center text-xs font-bold">1</span>
                            <h3 class="text-xs font-bold uppercase tracking-wider text-gray-800">Identity Verification</h3>
                        </div>
                        <span class="text-[11px] font-semibold text-gray-500 font-mono">{{ $maskedEmail }}</span>
                    </div>

                    {{-- Current Password --}}
                    @if($user->hasPasswordSet)
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">
                            Current Password <span class="text-[#C0420A]">*</span>
                        </label>
                        <div class="relative">
                            <input :type="showCurrent ? 'text' : 'password'" 
                                   name="current_password" 
                                   x-model="currentPassword"
                                   required
                                   class="w-full h-10 px-3.5 pr-10 border {{ $errors->has('current_password') ? 'border-red-400 bg-red-50' : 'border-gray-200 bg-white' }} rounded-xl text-xs font-semibold outline-none focus:border-[#C0420A] transition-colors"
                                   placeholder="Enter your current password">
                            <button type="button" @click="showCurrent = !showCurrent" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                <svg x-show="!showCurrent" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                <svg x-show="showCurrent" x-cloak class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                            </button>
                        </div>
                    </div>
                    @endif

                    {{-- Code Action & Status --}}
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider">
                                Email Verification Code <span class="text-[#C0420A]">*</span>
                            </label>

                            {{-- Send / Resend Button --}}
                            <div>
                                <button type="button" 
                                        @click="sendCode()" 
                                        :disabled="isSending || countdown > 0"
                                        class="text-xs font-bold text-[#C0420A] hover:text-[#9e3305] disabled:text-gray-400 transition-colors inline-flex items-center gap-1 cursor-pointer disabled:cursor-not-allowed">
                                    <span x-show="isSending" class="inline-block animate-spin">⏳</span>
                                    <span x-show="!isSending && countdown === 0" x-text="codeSent ? 'Resend Code' : 'Send Code to Gmail'"></span>
                                    <span x-show="countdown > 0" x-text="'Resend in ' + countdown + 's'"></span>
                                </button>
                            </div>
                        </div>

                        <div class="relative">
                            <input type="text" 
                                   name="code" 
                                   x-model="code"
                                   maxlength="6"
                                   required
                                   @input="onCodeInput()"
                                   :class="{
                                       'border-red-400 bg-red-50': codeState === 'incorrect_code' || codeState === 'expired_code',
                                       'border-emerald-500 bg-emerald-50/40': codeState === 'verified',
                                       'border-gray-200 bg-white': codeState !== 'incorrect_code' && codeState !== 'expired_code' && codeState !== 'verified'
                                   }"
                                   class="w-full h-11 px-4 tracking-[0.3em] font-mono text-center text-base font-black border rounded-xl outline-none focus:border-[#C0420A] transition-all"
                                   placeholder="000000">

                            {{-- Verified checkmark icon --}}
                            <div x-show="codeState === 'verified'" x-cloak class="absolute right-3 top-1/2 -translate-y-1/2 text-emerald-600 font-bold text-sm">
                                ✓ Verified
                            </div>
                        </div>

                        {{-- State Messages Display --}}
                        <div class="mt-2 text-xs font-medium">
                            {{-- Code Sent State --}}
                            <div x-show="codeState === 'code_sent'" x-cloak class="text-blue-700 bg-blue-50 p-2.5 rounded-xl border border-blue-200 flex items-center gap-2">
                                <span>✉️</span>
                                <span>Verification code sent to <strong>{{ $maskedEmail }}</strong>. Expires in 10 minutes.</span>
                            </div>

                            {{-- Resend Code State --}}
                            <div x-show="codeState === 'resend_code'" x-cloak class="text-amber-800 bg-amber-50 p-2.5 rounded-xl border border-amber-200 flex items-center gap-2">
                                <span>🔄</span>
                                <span>A fresh 6-digit code was sent to your Gmail inbox.</span>
                            </div>

                            {{-- Incorrect Code State --}}
                            <div x-show="codeState === 'incorrect_code'" x-cloak class="text-red-700 bg-red-50 p-2.5 rounded-xl border border-red-200 flex items-center gap-2">
                                <span>❌</span>
                                <span x-text="errorMessage || 'Incorrect verification code. Please check your Gmail or request a new code.'"></span>
                            </div>

                            {{-- Expired Code State --}}
                            <div x-show="codeState === 'expired_code'" x-cloak class="text-red-700 bg-red-50 p-2.5 rounded-xl border border-red-200 flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <span>⚠️</span>
                                    <span>Code has expired. Click 'Resend Code' above for a fresh code.</span>
                                </div>
                            </div>

                            {{-- Successful Verification State --}}
                            <div x-show="codeState === 'verified'" x-cloak class="text-emerald-800 bg-emerald-50 p-2.5 rounded-xl border border-emerald-200 flex items-center gap-2">
                                <span>✅</span>
                                <span>Security code verified! You can now create your new password below.</span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Step 2: New Password Creation --}}
                <div class="space-y-4 pt-1">
                    <div class="flex items-center gap-2 border-b border-gray-100 pb-2">
                        <span class="w-6 h-6 rounded-full bg-[#C0420A] text-white flex items-center justify-center text-xs font-bold">2</span>
                        <h3 class="text-xs font-bold uppercase tracking-wider text-gray-800">Create New Password</h3>
                    </div>

                    {{-- New Password --}}
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">
                            New Password <span class="text-[#C0420A]">*</span>
                        </label>
                        <div class="relative">
                            <input :type="showNew ? 'text' : 'password'" 
                                   name="password" 
                                   id="password"
                                   x-model="newPassword"
                                   required
                                   class="w-full h-10 px-3.5 pr-10 border {{ $errors->has('password') ? 'border-red-400 bg-red-50' : 'border-gray-200 bg-white' }} rounded-xl text-xs font-semibold outline-none focus:border-[#C0420A] transition-colors"
                                   placeholder="At least 8 characters"
                                   @input="checkStrength($event.target.value)">
                            <button type="button" @click="showNew = !showNew" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                <svg x-show="!showNew" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                <svg x-show="showNew" x-cloak class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                            </button>
                        </div>
                        {{-- Strength bar --}}
                        <div class="mt-2">
                            <div class="flex gap-1">
                                <div class="h-1 flex-1 rounded" :class="strength >= 1 ? (strength === 1 ? 'bg-red-400' : 'bg-yellow-400') : 'bg-gray-100'"></div>
                                <div class="h-1 flex-1 rounded" :class="strength >= 2 ? (strength === 2 ? 'bg-yellow-400' : 'bg-green-400') : 'bg-gray-100'"></div>
                                <div class="h-1 flex-1 rounded" :class="strength >= 3 ? 'bg-green-400' : 'bg-gray-100'"></div>
                                <div class="h-1 flex-1 rounded" :class="strength >= 4 ? 'bg-green-600' : 'bg-gray-100'"></div>
                            </div>
                            <p x-show="strengthLabel" class="text-[11px] font-semibold mt-1" :class="{
                                'text-red-500': strength === 1,
                                'text-yellow-600': strength === 2,
                                'text-green-600': strength >= 3
                            }" x-text="'Password strength: ' + strengthLabel"></p>
                        </div>
                    </div>

                    {{-- Confirm Password --}}
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">
                            Confirm New Password <span class="text-[#C0422A]">*</span>
                        </label>
                        <div class="relative">
                            <input :type="showConfirm ? 'text' : 'password'" 
                                   name="password_confirmation" 
                                   x-model="confirmPassword"
                                   required
                                   class="w-full h-10 px-3.5 pr-10 border border-gray-200 bg-white rounded-xl text-xs font-semibold outline-none focus:border-[#C0422A] transition-colors"
                                   placeholder="Re-enter new password">
                            <button type="button" @click="showConfirm = !showConfirm" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                <svg x-show="!showConfirm" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                <svg x-show="showConfirm" x-cloak class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Submit Button --}}
                <div class="pt-4 border-t border-gray-100 flex items-center justify-between">
                    <a href="{{ route('profile') }}" class="text-xs font-bold text-gray-500 hover:text-gray-800">
                        Cancel
                    </a>

                    <button type="submit"
                            :disabled="isSubmitting || code.length !== 6"
                            class="px-6 py-3 bg-[#C0420A] hover:bg-[#a33506] text-white rounded-xl text-xs font-bold uppercase tracking-wider transition-all shadow-md disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2">
                        <span x-show="!isSubmitting">Update Password</span>
                        <span x-show="isSubmitting" x-cloak class="flex items-center gap-2">
                            <span class="inline-block animate-spin">⏳</span>
                            <span>Updating...</span>
                        </span>
                    </button>
                </div>
            </form>
        </main>
    </div>
</div>

<script>
function passwordSecurityManager(sendUrl, verifyUrl, csrfToken) {
    return {
        showCurrent: false,
        showNew: false,
        showConfirm: false,
        currentPassword: '',
        newPassword: '',
        confirmPassword: '',
        code: '',
        codeSent: false,
        isSending: false,
        isSubmitting: false,
        countdown: 0,
        timer: null,
        codeState: 'idle', // 'idle', 'code_sent', 'resend_code', 'incorrect_code', 'expired_code', 'verified'
        errorMessage: '',
        strength: 0,
        strengthLabel: '',

        async sendCode() {
            if (this.isSending || this.countdown > 0) return;
            this.isSending = true;
            this.errorMessage = '';

            try {
                const res = await fetch(sendUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({
                        current_password: this.currentPassword
                    })
                });

                const data = await res.json().catch(() => ({}));

                if (res.ok && data.status === 'code_sent') {
                    this.codeSent = true;
                    this.codeState = this.codeSent ? 'resend_code' : 'code_sent';
                    this.startCountdown(data.cooldown || 60);
                } else if (res.status === 429) {
                    this.startCountdown(data.remaining || 60);
                    this.errorMessage = data.message || 'Please wait before requesting a new code.';
                } else {
                    this.errorMessage = data.message || 'Failed to send verification code. Please check your credentials.';
                    this.codeState = 'incorrect_code';
                }
            } catch (e) {
                this.errorMessage = 'Network error while requesting verification code.';
            } finally {
                this.isSending = false;
            }
        },

        async onCodeInput() {
            this.code = this.code.replace(/[^0-9]/g, '');
            if (this.code.length === 6) {
                this.verifyCode();
            } else if (this.codeState === 'incorrect_code' || this.codeState === 'expired_code') {
                this.codeState = 'idle';
            }
        },

        async verifyCode() {
            try {
                const res = await fetch(verifyUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({ code: this.code })
                });

                const data = await res.json().catch(() => ({}));

                if (res.ok && data.status === 'verified') {
                    this.codeState = 'verified';
                } else if (data.status === 'expired_code') {
                    this.codeState = 'expired_code';
                    this.errorMessage = data.message;
                } else {
                    this.codeState = 'incorrect_code';
                    this.errorMessage = data.message || 'Incorrect verification code.';
                }
            } catch (e) {
                // If offline, rely on final submit validation
            }
        },

        startCountdown(seconds) {
            this.countdown = seconds;
            if (this.timer) clearInterval(this.timer);
            this.timer = setInterval(() => {
                this.countdown--;
                if (this.countdown <= 0) {
                    clearInterval(this.timer);
                    this.countdown = 0;
                }
            }, 1000);
        },

        checkStrength(v) {
            let s = 0;
            if (v.length >= 8) s++;
            if (/[A-Z]/.test(v)) s++;
            if (/[0-9]/.test(v)) s++;
            if (/[^A-Za-z0-9]/.test(v)) s++;
            this.strength = s;
            this.strengthLabel = ['', 'Weak', 'Fair', 'Good', 'Strong'][s] || '';
        }
    };
}
</script>
@endsection
