@extends('layouts.app')

@section('title', 'Verify Your Gmail - LumBarong')

@section('content')
<div class="min-h-screen bg-linear-to-b from-amber-50/50 via-white to-gray-50 flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8 bg-white p-8 sm:p-10 rounded-3xl shadow-xl border border-gray-100">
        <div class="text-center">
            <div class="w-16 h-16 bg-[#C0420A]/10 text-[#C0420A] rounded-2xl flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 002-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                </svg>
            </div>
            <h2 class="text-2xl font-black text-gray-900 tracking-tight">Verify Your Gmail</h2>
            <p class="mt-2 text-xs font-semibold text-gray-500">
                We sent a 6-digit verification code to <span class="font-bold text-gray-800">{{ session('verify_email', auth()->user()->email ?? 'your email') }}</span>.
            </p>
        </div>

        @if(session('success'))
            <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-xl text-xs font-bold">
                {{ session('success') }}
            </div>
        @endif

        @if($errors->any())
            <div class="bg-rose-50 border border-rose-200 text-rose-700 px-4 py-3 rounded-xl text-xs font-bold">
                {{ $errors->first() }}
            </div>
        @endif

        <form action="{{ route('verify.email.submit') }}" method="POST" class="mt-8 space-y-6">
            @csrf
            <input type="hidden" name="email" value="{{ session('verify_email', auth()->user()->email ?? '') }}">

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 mb-2 text-center">6-Digit Verification Code</label>
                <input type="text" name="code" maxlength="6" required pattern="[0-9]{6}" placeholder="123456" autofocus
                    class="w-full text-center text-3xl font-black tracking-[0.5em] px-4 py-3.5 bg-gray-50 border border-gray-200 rounded-2xl outline-none focus:border-[#C0420A] focus:ring-4 focus:ring-[#C0420A]/10 transition-all">
            </div>

            <button type="submit" class="w-full py-4 bg-[#C0420A] text-white font-black text-sm uppercase tracking-wider rounded-2xl shadow-lg shadow-[#C0420A]/20 hover:bg-[#a33708] transition-all">
                Activate Account
            </button>
        </form>

        <div class="pt-4 border-t border-gray-100 text-center">
            <p class="text-xs text-gray-500 mb-2">Didn't receive the code?</p>
            <form action="{{ route('verify.email.resend') }}" method="POST">
                @csrf
                <input type="hidden" name="email" value="{{ session('verify_email', auth()->user()->email ?? '') }}">
                <button type="submit" class="text-xs font-bold text-[#C0420A] hover:underline">
                    Resend Verification Code
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
