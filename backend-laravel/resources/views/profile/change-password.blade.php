@extends('layouts.app')

@section('content')
<div class="max-w-[1100px] mx-auto py-8">
    <div class="flex flex-col md:flex-row gap-6">

        @include('profile._sidebar', ['user' => $user])

        <main class="flex-1 bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">

            {{-- Header --}}
            <div class="px-8 py-6 border-b border-gray-100">
                <h2 class="text-lg font-bold text-gray-900">Change Password</h2>
                <p class="text-xs text-gray-400 mt-0.5">For your account's security, do not share your password with others.</p>
            </div>

            {{-- Alerts --}}
            {{-- success is handled by the global floating toast in layouts/app.blade.php --}}

            @if($errors->any())
            <div 
                x-data="{ show: true, init() { setTimeout(() => this.show = false, 7000) } }"
                x-show="show"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-2 sm:translate-y-0 sm:translate-x-2"
                x-transition:enter-end="opacity-100 translate-y-0 sm:translate-x-0"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="fixed top-6 right-6 z-9999 w-full max-w-sm bg-white/95 backdrop-blur-md rounded-2xl shadow-2xl border border-gray-100 p-4 flex items-start gap-3.5"
                style="display: none;"
                x-cloak
            >
                <div class="w-8 h-8 rounded-full bg-red-50 flex items-center justify-center text-red-600 shrink-0 shadow-sm border border-red-100">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path></svg>
                </div>
                <div class="grow pt-0.5">
                    <h4 class="text-xs font-black text-black uppercase tracking-wider">Please fix the following</h4>
                    <ul class="text-xs text-gray-500 font-medium mt-1 leading-relaxed space-y-0.5 list-disc list-inside">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                <button @click="show = false" class="text-gray-300 hover:text-gray-500 transition-colors shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            @endif

            {{-- Form --}}
            <form action="{{ route('profile.change-password.submit') }}" method="POST" class="px-8 py-8 max-w-lg" x-data="{ showCurrent: false, showNew: false, showConfirm: false }">
                @csrf

                {{-- Current Password --}}
                <div class="grid grid-cols-[180px_1fr] items-start gap-4 mb-5">
                    <label class="text-sm text-gray-500 text-right pt-2.5">Current Password</label>
                    <div class="space-y-1">
                        <div class="relative">
                            <input :type="showCurrent ? 'text' : 'password'" name="current_password"
                                class="w-full h-10 px-4 pr-10 border {{ $errors->has('current_password') ? 'border-red-400 bg-red-50' : 'border-gray-200 bg-white' }} rounded-lg text-sm outline-none focus:border-[#C0420A] transition-colors"
                                placeholder="Enter current password">
                            <button type="button" @click="showCurrent = !showCurrent"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                <svg x-show="!showCurrent" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                <svg x-show="showCurrent" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                            </button>
                        </div>
                        @error('current_password')
                            <p class="text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- New Password --}}
                <div class="grid grid-cols-[180px_1fr] items-start gap-4 mb-5">
                    <label class="text-sm text-gray-500 text-right pt-2.5">New Password</label>
                    <div class="space-y-1">
                        <div class="relative">
                            <input :type="showNew ? 'text' : 'password'" name="password" id="password"
                                class="w-full h-10 px-4 pr-10 border {{ $errors->has('password') ? 'border-red-400 bg-red-50' : 'border-gray-200 bg-white' }} rounded-lg text-sm outline-none focus:border-[#C0420A] transition-colors"
                                placeholder="At least 8 characters"
                                x-on:input="checkStrength($event.target.value)">
                            <button type="button" @click="showNew = !showNew"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                <svg x-show="!showNew" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                <svg x-show="showNew" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                            </button>
                        </div>
                        {{-- Strength bar --}}
                        <div x-data="{ strength: 0, label: '', checkStrength(v){ let s=0; if(v.length>=8)s++; if(/[A-Z]/.test(v))s++; if(/[0-9]/.test(v))s++; if(/[^A-Za-z0-9]/.test(v))s++; this.strength=s; this.label=['','Weak','Fair','Good','Strong'][s]||''; } }">
                            <div class="flex gap-1 mt-2">
                                <div class="h-1 flex-1 rounded" :class="strength>=1 ? (strength==1?'bg-red-400':'bg-yellow-400') : 'bg-gray-100'"></div>
                                <div class="h-1 flex-1 rounded" :class="strength>=2 ? (strength==2?'bg-yellow-400':'bg-green-400') : 'bg-gray-100'"></div>
                                <div class="h-1 flex-1 rounded" :class="strength>=3 ? 'bg-green-400' : 'bg-gray-100'"></div>
                                <div class="h-1 flex-1 rounded" :class="strength>=4 ? 'bg-green-600' : 'bg-gray-100'"></div>
                            </div>
                            <p x-show="label" class="text-[11px] mt-1" :class="{'text-red-500':strength==1,'text-yellow-600':strength==2,'text-green-500':strength>=3}" x-text="'Password strength: '+label"></p>
                        </div>
                        @error('password')
                            <p class="text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- Confirm Password --}}
                <div class="grid grid-cols-[180px_1fr] items-start gap-4 mb-8">
                    <label class="text-sm text-gray-500 text-right pt-2.5">Confirm Password</label>
                    <div class="relative">
                        <input :type="showConfirm ? 'text' : 'password'" name="password_confirmation"
                            class="w-full h-10 px-4 pr-10 border border-gray-200 bg-white rounded-lg text-sm outline-none focus:border-[#C0420A] transition-colors"
                            placeholder="Re-enter new password">
                        <button type="button" @click="showConfirm = !showConfirm"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                            <svg x-show="!showConfirm" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            <svg x-show="showConfirm" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                        </button>
                    </div>
                </div>

                {{-- Submit --}}
                <div class="grid grid-cols-[180px_1fr] items-center gap-4">
                    <div></div>
                    <button type="submit"
                        class="w-36 py-2.5 bg-[#C0420A] text-white text-sm font-semibold rounded-lg hover:bg-[#a83808] transition-colors shadow-sm">
                        Confirm
                    </button>
                </div>
            </form>
        </main>
    </div>
</div>
@endsection
