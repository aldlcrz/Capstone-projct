@extends('layouts.app')

@section('content')
@php
    $emailParts = explode('@', $user->email);
    $namePart = $emailParts[0];
    $domainPart = $emailParts[1] ?? 'gmail.com';
    $maskedName = strlen($namePart) <= 3 
        ? substr($namePart, 0, 1) . '***' 
        : substr($namePart, 0, 2) . str_repeat('*', max(3, strlen($namePart) - 3)) . substr($namePart, -1);
    $maskedEmail = $maskedName . '@' . $domainPart;
    $backRoute = $user->role === 'seller' ? route('seller.profile') : route('profile');
@endphp

<div style="background-color:#FAF8F5;min-height:85vh;padding:32px 16px 60px 16px;">
    <div style="max-width:540px;margin:0 auto;">

        {{-- Back Link --}}
        <div class="mb-4">
            <a href="{{ $backRoute }}" 
               style="display:inline-flex;align-items:center;gap:6px;font-size:12px;font-weight:700;color:#8C827A;text-decoration:none;transition:color 0.2s;"
               class="hover:text-[#1E1915]">
                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                </svg>
                <span>Back to Profile</span>
            </a>
        </div>

        {{-- Main Heritage Card Container --}}
        <div style="background-color:#FDFBF7;border:1px solid #ECE3D2;border-radius:28px;box-shadow:0 4px 20px rgba(0,0,0,0.03);padding:24px 20px 28px 20px;position:relative;overflow:hidden;"
             x-data="passwordSecurityManager('{{ route('profile.change-password.send-code') }}', '{{ route('profile.change-password.verify-code') }}', '{{ csrf_token() }}')">

            {{-- Header with Heritage Medal Icon --}}
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;gap:12px;flex-wrap:wrap;">
                <div style="display:flex;align-items:center;gap:14px;">
                    <div style="width:44px;height:44px;border-radius:50%;border:1.5px solid #C49520;background-color:#FAF4EA;display:flex;align-items:center;justify-content:center;color:#B88728;flex-shrink:0;box-shadow:0 2px 6px rgba(184,135,40,0.12);">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <rect x="3" y="11" width="18" height="11" rx="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M7 11V7a5 5 0 0110 0v4" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    <div>
                        <h1 style="font-family:'Playfair Display', Georgia, serif;font-size:22px;font-weight:800;color:#1E1915;margin:0;line-height:1.2;letter-spacing:-0.02em;">
                            Change Password
                        </h1>
                        <p style="font-size:11px;color:#78716C;margin:2px 0 0 0;font-weight:500;">
                            Two-step verified password change to keep your account safe
                        </p>
                    </div>
                </div>
                <div style="display:inline-flex;align-items:center;gap:5px;padding:4px 10px;border-radius:20px;background-color:#FAF5EA;border:1px solid #E6D8BA;color:#996515;font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:0.05em;">
                    <span>🛡️ 2-Step Verified</span>
                </div>
            </div>

            {{-- Divider --}}
            <div style="position:relative;margin:16px 0 20px 0;text-align:center;">
                <div style="height:1px;background-color:#ECE3D2;width:100%;"></div>
                <div style="position:absolute;top:-4px;left:50%;transform:translateX(-50%);width:9px;height:9px;background-color:#C49520;transform:rotate(45deg);border-radius:1px;"></div>
            </div>

            {{-- Validation Alerts --}}
            @if($errors->any())
            <div style="margin-bottom:16px;padding:12px 16px;border-radius:16px;background-color:#FEF2F2;border:1px solid #FECACA;color:#991B1B;font-size:12px;display:flex;align-items:flex-start;gap:10px;">
                <div style="width:20px;height:20px;border-radius:50%;background-color:#FEE2E2;display:flex;align-items:center;justify-content:center;color:#DC2626;font-weight:800;flex-shrink:0;">!</div>
                <div style="flex:1;">
                    <strong style="display:block;margin-bottom:2px;">Please fix the following:</strong>
                    <ul style="margin:0;padding-left:16px;list-style:disc;">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
            @endif

            @if(session('success'))
            <div style="margin-bottom:16px;padding:12px 16px;border-radius:16px;background-color:#ECFDF5;border:1px solid #A7F3D0;color:#065F46;font-size:12px;display:flex;align-items:center;gap:10px;">
                <div style="width:20px;height:20px;border-radius:50%;background-color:#D1FAE5;display:flex;align-items:center;justify-content:center;color:#059669;font-weight:800;flex-shrink:0;">✓</div>
                <strong>{{ session('success') }}</strong>
            </div>
            @endif

            <form action="{{ route('profile.change-password.submit') }}" method="POST" class="space-y-4" @submit="isSubmitting = true">
                @csrf

                {{-- Step 1: Security Verification Card --}}
                <div style="background-color:#FFFFFF;border:1px solid #ECE3D2;border-radius:20px;padding:18px;box-shadow:0 2px 8px rgba(0,0,0,0.02);" class="space-y-3.5">
                    <div style="display:flex;align-items:center;justify-content:space-between;border-bottom:1px solid #F0EAE0;padding-bottom:10px;">
                        <div style="display:flex;align-items:center;gap:8px;">
                            <span style="width:22px;height:22px;border-radius:50%;background-color:#C0422A;color:#FFFFFF;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:800;">1</span>
                            <h3 style="font-size:11px;font-weight:800;color:#1E1915;text-transform:uppercase;letter-spacing:0.05em;margin:0;">Identity Verification</h3>
                        </div>
                        <span style="font-size:11px;font-weight:700;color:#8C827A;font-family:monospace;">{{ $maskedEmail }}</span>
                    </div>

                    {{-- Current Password --}}
                    @if($user->hasPasswordSet)
                    <div>
                        <label style="display:block;font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:0.05em;color:#78716C;margin-bottom:6px;">
                            Current Password <span style="color:#C0422A;">*</span>
                        </label>
                        <div class="relative">
                            <input :type="showCurrent ? 'text' : 'password'" 
                                   name="current_password" 
                                   x-model="currentPassword"
                                   required
                                   style="background-color:#FAF8F5;border:1px solid #E5DCCF;border-radius:12px;height:42px;padding:0 38px 0 14px;font-size:12px;font-weight:600;color:#1E1915;width:100%;outline:none;transition:border-color 0.2s;"
                                   class="focus:border-[#C0422A] focus:bg-white"
                                   placeholder="Enter your current password">
                            <button type="button" @click="showCurrent = !showCurrent" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-700">
                                <svg x-show="!showCurrent" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                <svg x-show="showCurrent" x-cloak class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                            </button>
                        </div>
                    </div>
                    @endif

                    {{-- Code Action & Status --}}
                    <div>
                        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:6px;">
                            <label style="font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:0.05em;color:#78716C;margin:0;">
                                Email Verification Code <span style="color:#C0422A;">*</span>
                            </label>

                            {{-- Send / Resend Button --}}
                            <div>
                                <button type="button" 
                                        @click="sendCode()" 
                                        :disabled="isSending || countdown > 0"
                                        style="font-size:11px;font-weight:800;color:#C0422A;background:none;border:none;padding:0;cursor:pointer;"
                                        class="hover:underline disabled:opacity-50 disabled:cursor-not-allowed inline-flex items-center gap-1">
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
                                       'border-[#E5DCCF] bg-[#FAF8F5]': codeState !== 'incorrect_code' && codeState !== 'expired_code' && codeState !== 'verified'
                                   }"
                                   style="border-radius:12px;height:44px;padding:0 14px;letter-spacing:0.35em;font-family:monospace;font-size:16px;font-weight:900;color:#1E1915;text-align:center;width:100%;outline:none;transition:border-color 0.2s;"
                                   class="focus:border-[#C0422A] focus:bg-white"
                                   placeholder="000000">

                            {{-- Verified checkmark icon --}}
                            <div x-show="codeState === 'verified'" x-cloak class="absolute right-3 top-1/2 -translate-y-1/2 text-emerald-600 font-bold text-xs">
                                ✓ Verified
                            </div>
                        </div>

                        {{-- State Messages Display --}}
                        <div class="mt-2 text-xs font-medium space-y-1.5">
                            {{-- Code Sent State --}}
                            <div x-show="codeState === 'code_sent'" x-cloak class="text-blue-700 bg-blue-50 p-2.5 rounded-xl border border-blue-200 flex items-center gap-2 text-xs">
                                <span>✉️</span>
                                <span>Verification code sent to <strong>{{ $maskedEmail }}</strong>. Expires in 10 minutes.</span>
                            </div>

                            {{-- Resend Code State --}}
                            <div x-show="codeState === 'resend_code'" x-cloak class="text-amber-800 bg-amber-50 p-2.5 rounded-xl border border-amber-200 flex items-center gap-2 text-xs">
                                <span>🔄</span>
                                <span>A fresh 6-digit code was sent to your Gmail inbox.</span>
                            </div>

                            {{-- Incorrect Code State --}}
                            <div x-show="codeState === 'incorrect_code'" x-cloak class="text-red-700 bg-red-50 p-2.5 rounded-xl border border-red-200 flex items-center gap-2 text-xs">
                                <span>❌</span>
                                <span x-text="errorMessage || 'Incorrect verification code. Please check your Gmail.'"></span>
                            </div>

                            {{-- Expired Code State --}}
                            <div x-show="codeState === 'expired_code'" x-cloak class="text-red-700 bg-red-50 p-2.5 rounded-xl border border-red-200 flex items-center justify-between text-xs">
                                <div class="flex items-center gap-2">
                                    <span>⚠️</span>
                                    <span>Code has expired. Click 'Resend Code' for a new code.</span>
                                </div>
                            </div>

                            {{-- Successful Verification State --}}
                            <div x-show="codeState === 'verified'" x-cloak class="text-emerald-800 bg-emerald-50 p-2.5 rounded-xl border border-emerald-200 flex items-center gap-2 text-xs">
                                <span>✅</span>
                                <span>Security code verified! You can now create your new password below.</span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Step 2: New Password Creation --}}
                <div style="background-color:#FFFFFF;border:1px solid #ECE3D2;border-radius:20px;padding:18px;box-shadow:0 2px 8px rgba(0,0,0,0.02);" class="space-y-3.5">
                    <div style="display:flex;align-items:center;gap:8px;border-bottom:1px solid #F0EAE0;padding-bottom:10px;">
                        <span style="width:22px;height:22px;border-radius:50%;background-color:#C0422A;color:#FFFFFF;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:800;">2</span>
                        <h3 style="font-size:11px;font-weight:800;color:#1E1915;text-transform:uppercase;letter-spacing:0.05em;margin:0;">Create New Password</h3>
                    </div>

                    {{-- New Password --}}
                    <div>
                        <label style="display:block;font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:0.05em;color:#78716C;margin-bottom:6px;">
                            New Password <span style="color:#C0422A;">*</span>
                        </label>
                        <div class="relative">
                            <input :type="showNew ? 'text' : 'password'" 
                                   name="password" 
                                   id="password"
                                   x-model="newPassword"
                                   required
                                   style="background-color:#FAF8F5;border:1px solid #E5DCCF;border-radius:12px;height:42px;padding:0 38px 0 14px;font-size:12px;font-weight:600;color:#1E1915;width:100%;outline:none;transition:border-color 0.2s;"
                                   class="focus:border-[#C0422A] focus:bg-white"
                                   placeholder="At least 8 characters"
                                   @input="checkStrength($event.target.value)">
                            <button type="button" @click="showNew = !showNew" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-700">
                                <svg x-show="!showNew" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                <svg x-show="showNew" x-cloak class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                            </button>
                        </div>
                        {{-- Strength bar --}}
                        <div class="mt-2">
                            <div class="flex gap-1">
                                <div class="h-1 flex-1 rounded" :class="strength >= 1 ? (strength === 1 ? 'bg-red-400' : 'bg-yellow-400') : 'bg-gray-200'"></div>
                                <div class="h-1 flex-1 rounded" :class="strength >= 2 ? (strength === 2 ? 'bg-yellow-400' : 'bg-green-400') : 'bg-gray-200'"></div>
                                <div class="h-1 flex-1 rounded" :class="strength >= 3 ? 'bg-green-400' : 'bg-gray-200'"></div>
                                <div class="h-1 flex-1 rounded" :class="strength >= 4 ? 'bg-green-600' : 'bg-gray-200'"></div>
                            </div>
                            <p x-show="strengthLabel" class="text-[10px] font-bold mt-1" :class="{
                                'text-red-500': strength === 1,
                                'text-yellow-600': strength === 2,
                                'text-green-600': strength >= 3
                            }" x-text="'Password strength: ' + strengthLabel"></p>
                        </div>
                    </div>

                    {{-- Confirm Password --}}
                    <div>
                        <label style="display:block;font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:0.05em;color:#78716C;margin-bottom:6px;">
                            Confirm New Password <span style="color:#C0422A;">*</span>
                        </label>
                        <div class="relative">
                            <input :type="showConfirm ? 'text' : 'password'" 
                                   name="password_confirmation" 
                                   x-model="confirmPassword"
                                   required
                                   style="background-color:#FAF8F5;border:1px solid #E5DCCF;border-radius:12px;height:42px;padding:0 38px 0 14px;font-size:12px;font-weight:600;color:#1E1915;width:100%;outline:none;transition:border-color 0.2s;"
                                   class="focus:border-[#C0422A] focus:bg-white"
                                   placeholder="Re-enter new password">
                            <button type="button" @click="showConfirm = !showConfirm" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-700">
                                <svg x-show="!showConfirm" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                <svg x-show="showConfirm" x-cloak class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Submit Button Actions --}}
                <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;padding-top:8px;">
                    <a href="{{ $backRoute }}" 
                       style="padding:12px 20px;border-radius:14px;background-color:#FAF8F5;border:1px solid #ECE3D2;font-size:11px;font-weight:800;color:#78716C;text-decoration:none;text-transform:uppercase;letter-spacing:0.05em;transition:all 0.2s;"
                       class="hover:bg-[#F0EAE0] hover:text-[#1E1915]">
                        Cancel
                    </a>

                    <button type="submit"
                            :disabled="isSubmitting || code.length !== 6"
                            style="padding:12px 24px;border-radius:14px;background:linear-gradient(135deg, #C0422A 0%, #A33506 100%);color:#FFFFFF;border:none;font-size:11px;font-weight:900;text-transform:uppercase;letter-spacing:0.05em;box-shadow:0 3px 10px rgba(192,66,42,0.25);cursor:pointer;display:inline-flex;align-items:center;gap:8px;transition:all 0.2s;"
                            class="hover:opacity-95 disabled:opacity-50 disabled:cursor-not-allowed">
                        <span x-show="!isSubmitting">Update Password ➔</span>
                        <span x-show="isSubmitting" x-cloak style="display:inline-flex;align-items:center;gap:6px;">
                            <span class="inline-block animate-spin">⏳</span>
                            <span>Updating...</span>
                        </span>
                    </button>
                </div>
            </form>

            {{-- Verified & Trusted Customer Footer Banner --}}
            <div style="margin-top:22px;padding:14px 18px;border-radius:18px;background:linear-gradient(90deg,#F6F0E4 0%,#F2EADA 50%,#EAE0CD 100%);border:1px solid #E2D6C0;display:flex;align-items:center;justify-content:space-between;gap:12px;position:relative;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,0.03);">
                <div style="display:flex;align-items:center;gap:12px;position:relative;z-index:10;">
                    <div style="width:32px;height:32px;border-radius:50%;border:2px solid #B88728;background-color:#FAF4EA;display:flex;align-items:center;justify-content:center;color:#B88728;flex-shrink:0;box-shadow:0 1px 2px rgba(0,0,0,0.05);">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                        </svg>
                    </div>
                    <div>
                        <h5 style="font-size:13px;font-weight:700;color:#1E1915;margin:0;line-height:1.2;">Verified LumBarong Account</h5>
                        <p style="font-size:11px;color:#78716C;margin:2px 0 0 0;">Quality craftsmanship. Authentic Filipino heritage.</p>
                    </div>
                </div>
                <!-- Background Embroidery Flourish Watermark -->
                <svg width="120" height="70" viewBox="0 0 120 80" fill="#C49520" style="position:absolute;right:8px;bottom:-10px;opacity:0.18;pointer-events:none;">
                    <path d="M60 10C40 10 30 30 10 35C30 40 40 60 60 60C80 60 90 40 110 35C90 30 80 10 60 10ZM60 25C65 25 70 30 70 35C70 40 65 45 60 45C55 45 50 40 50 35C50 30 55 25 60 25Z"/>
                </svg>
            </div>
        </div>
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
