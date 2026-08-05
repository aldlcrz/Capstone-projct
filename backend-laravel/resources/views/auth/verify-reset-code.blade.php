@extends('layouts.app')

@section('title', 'Verify Password Reset Code - LumBarong')

@section('content')
<div class="min-h-screen bg-linear-to-b from-amber-50/50 via-white to-gray-50 flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8 bg-white p-8 sm:p-10 rounded-3xl shadow-xl border border-gray-100">
        <div class="text-center">
            <div class="w-16 h-16 bg-[#C0420A]/10 text-[#C0420A] rounded-2xl flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                </svg>
            </div>
            <h2 class="text-2xl font-black text-gray-900 tracking-tight">Enter Reset Code</h2>
            <p class="mt-2 text-xs font-semibold text-gray-500">
                Please enter the 6-digit password reset code sent to <span class="font-bold text-gray-800">{{ session('reset_email', request('email')) }}</span>.
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

        <form action="{{ route('password.verify.code.submit') }}" method="POST" class="mt-8 space-y-6">
            @csrf
            <input type="hidden" name="email" value="{{ session('reset_email', request('email')) }}">

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 mb-2 text-center">6-Digit Reset Code</label>
                <input type="text" name="code" maxlength="6" required pattern="[0-9]{6}" placeholder="123456" autofocus
                    class="w-full text-center text-3xl font-black tracking-[0.5em] px-4 py-3.5 bg-gray-50 border border-gray-200 rounded-2xl outline-none focus:border-[#C0420A] focus:ring-4 focus:ring-[#C0420A]/10 transition-all">
            </div>

            <button type="submit" class="w-full py-4 bg-[#C0420A] text-white font-black text-sm uppercase tracking-wider rounded-2xl shadow-lg shadow-[#C0420A]/20 hover:bg-[#a33708] transition-all">
                Verify Code & Proceed
            </button>
        </form>
    </div>
</div>
@endsection
