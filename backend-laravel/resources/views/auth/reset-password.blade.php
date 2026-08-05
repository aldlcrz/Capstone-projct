@extends('layouts.app')

@section('title', 'Set New Password - LumBarong')

@section('content')
<div class="min-h-screen bg-linear-to-b from-amber-50/50 via-white to-gray-50 flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8 bg-white p-8 sm:p-10 rounded-3xl shadow-xl border border-gray-100">
        <div class="text-center">
            <h2 class="text-2xl font-black text-gray-900 tracking-tight">Create New Password</h2>
            <p class="mt-2 text-xs font-semibold text-gray-500">
                Create a strong password for account <span class="font-bold text-gray-800">{{ session('validated_reset_email') }}</span>.
            </p>
        </div>

        @if($errors->any())
            <div class="bg-rose-50 border border-rose-200 text-rose-700 px-4 py-3 rounded-xl text-xs font-bold">
                {{ $errors->first() }}
            </div>
        @endif

        <form action="{{ route('password.update.submit') }}" method="POST" class="mt-8 space-y-5">
            @csrf
            <input type="hidden" name="email" value="{{ session('validated_reset_email') }}">

            <div x-data="{ showPass: false }">
                <label class="block text-xs font-bold text-gray-700 mb-1">New Password</label>
                <div class="relative">
                    <input :type="showPass ? 'text' : 'password'" name="password" required minlength="8" placeholder="••••••••"
                        class="w-full px-4 py-3 pr-12 bg-gray-50 border border-gray-200 rounded-xl outline-none focus:border-[#C0420A] focus:ring-2 focus:ring-[#C0420A]/10 text-sm font-semibold">
                    <button type="button" @click="showPass = !showPass" class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-[#C0420A]">
                        <svg x-show="!showPass" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                        <svg x-show="showPass" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" x-cloak><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.477 0-8.268-2.943-9.542-7a9.97 9.97 0 012.458-4.017M6.53 6.53A9.97 9.97 0 0112 5c4.478 0 8.268 2.943 9.542 7a10.05 10.05 0 01-4.132 5.411M15 12a3 3 0 00-3-3m0 0a3 3 0 00-3 3m3-3L3 3m18 18L9 9"/></svg>
                    </button>
                </div>
            </div>

            <div x-data="{ showConfirm: false }">
                <label class="block text-xs font-bold text-gray-700 mb-1">Confirm New Password</label>
                <div class="relative">
                    <input :type="showConfirm ? 'text' : 'password'" name="password_confirmation" required minlength="8" placeholder="••••••••"
                        class="w-full px-4 py-3 pr-12 bg-gray-50 border border-gray-200 rounded-xl outline-none focus:border-[#C0420A] focus:ring-2 focus:ring-[#C0420A]/10 text-sm font-semibold">
                    <button type="button" @click="showConfirm = !showConfirm" class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-[#C0420A]">
                        <svg x-show="!showConfirm" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                        <svg x-show="showConfirm" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" x-cloak><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.477 0-8.268-2.943-9.542-7a9.97 9.97 0 012.458-4.017M6.53 6.53A9.97 9.97 0 0112 5c4.478 0 8.268 2.943 9.542 7a10.05 10.05 0 01-4.132 5.411M15 12a3 3 0 00-3-3m0 0a3 3 0 00-3 3m3-3L3 3m18 18L9 9"/></svg>
                    </button>
                </div>
            </div>

            <button type="submit" class="w-full py-4 bg-[#C0420A] text-white font-black text-sm uppercase tracking-wider rounded-2xl shadow-lg shadow-[#C0420A]/20 hover:bg-[#a33708] transition-all">
                Update Password & Log In
            </button>
        </form>
    </div>
</div>
@endsection
