@extends('layouts.app')

@section('title', 'Verify Password Reset Code - LumBarong')

@section('content')
<div class="min-h-screen bg-linear-to-b from-amber-50/50 via-white to-gray-50 flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-6 bg-white p-8 sm:p-10 rounded-3xl shadow-xl border border-gray-100"
         x-data="{
             email: '{{ session('reset_email', $email ?? request('email', '')) }}',
             code: '',
             showAiHelper: false
         }">
        
        <div class="text-center">
            <div class="w-16 h-16 bg-[#C0420A]/10 text-[#C0420A] rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-sm">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                </svg>
            </div>
            <h2 class="text-2xl font-black text-gray-900 tracking-tight">Enter Reset Code</h2>
            <p class="mt-2 text-xs font-semibold text-gray-500">
                Please enter the 6-digit password reset code sent to:
            </p>
            <div class="mt-1 font-bold text-gray-900 text-sm bg-gray-50 py-1.5 px-3 rounded-xl border border-gray-200 inline-block max-w-full truncate" x-text="email || 'your registered Gmail'"></div>
        </div>

        @if(session('success'))
            <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-2xl text-xs font-bold flex items-center gap-2">
                <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if($errors->any())
            <div class="bg-rose-50 border border-rose-200 text-rose-700 px-4 py-3 rounded-2xl text-xs font-bold flex items-center gap-2">
                <svg class="w-4 h-4 text-rose-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                <span>{{ $errors->first() }}</span>
            </div>
        @endif

        <form action="{{ route('password.verify.code.submit') }}" method="POST" class="mt-6 space-y-5">
            @csrf
            <input type="hidden" name="email" :value="email">

            <!-- Fallback email input if empty -->
            <div x-show="!email" class="space-y-1.5" x-cloak>
                <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-500">Your Registered Gmail</label>
                <input type="email" x-model="email" placeholder="example@gmail.com" required
                       class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm font-bold outline-none focus:border-[#C0420A] focus:ring-2 focus:ring-[#C0420A]/10 transition-all">
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 mb-2 text-center">6-Digit Reset Code</label>
                <input type="text" 
                       name="code" 
                       x-model="code"
                       maxlength="6" 
                       required 
                       pattern="[0-9]{6}" 
                       inputmode="numeric"
                       placeholder="123456" 
                       autofocus
                       class="w-full text-center text-3xl font-black tracking-[0.4em] px-4 py-3.5 bg-gray-50 border border-gray-200 rounded-2xl outline-none focus:border-[#C0420A] focus:ring-4 focus:ring-[#C0420A]/10 transition-all">
            </div>

            <button type="submit" 
                    :disabled="code.length !== 6"
                    :class="code.length === 6 ? 'bg-[#C0420A] hover:bg-[#a33708] shadow-lg shadow-[#C0420A]/20 cursor-pointer' : 'bg-gray-300 opacity-60 cursor-not-allowed'"
                    class="w-full py-4 text-white font-black text-sm uppercase tracking-wider rounded-2xl transition-all">
                Verify Code & Proceed
            </button>
        </form>

        <!-- AI Verification Diagnostics -->
        <div class="bg-amber-50/60 border border-amber-200/70 rounded-2xl p-4 space-y-2.5">
            <div class="flex items-center justify-between cursor-pointer" @click="showAiHelper = !showAiHelper">
                <div class="flex items-center gap-2">
                    <span class="text-base">✨</span>
                    <span class="text-xs font-black text-amber-900 uppercase tracking-wider">AI Delivery Tips</span>
                </div>
                <button type="button" class="text-xs font-bold text-amber-800 hover:text-amber-950">
                    <span x-text="showAiHelper ? 'Hide Tips' : 'Delivery Tips'"></span>
                </button>
            </div>
            
            <div x-show="showAiHelper" x-transition class="text-xs text-amber-900 space-y-2 pt-1 border-t border-amber-200/50">
                <ul class="list-disc list-inside space-y-1 text-[11px] text-amber-800">
                    <li>Check your Gmail <strong>Spam</strong> or <strong>Updates</strong> tab.</li>
                    <li>Search for sender: <code class="bg-amber-100/80 px-1.5 py-0.5 rounded font-mono font-bold">lumbarongsupport@gmail.com</code>.</li>
                    <li>Reset codes are valid for <strong>10 minutes</strong>.</li>
                </ul>
            </div>
        </div>

        <div class="pt-2 border-t border-gray-100 text-center">
            <a href="{{ route('password.request') }}" class="text-xs font-bold text-gray-500 hover:text-[#C0420A] transition-colors">
                ← Request a new reset code
            </a>
        </div>
    </div>
</div>
@endsection
