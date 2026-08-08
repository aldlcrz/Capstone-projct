@extends('layouts.app')

@section('content')
<div class="min-h-[calc(100vh-80px)] bg-white py-6 px-4 sm:px-6" x-data="profileApp()" x-init="init()">
    <div class="max-w-md mx-auto space-y-6">

        {{-- Top Header --}}
        <div class="pt-2">
            <div class="flex items-center gap-2 mb-1">
                <div class="w-5 h-0.5 bg-[#C0422A]"></div>
                <span class="text-[10px] font-bold uppercase tracking-widest text-[#C0422A]">Account</span>
            </div>
            <h1 class="font-serif text-2xl font-bold text-black">My <span class="text-[#C0422A] italic">Profile</span></h1>
        </div>

        {{-- Profile Avatar & User Card --}}
        <div class="relative pt-6">
            {{-- Floating Avatar --}}
            <div class="relative w-28 h-28 mx-auto -mb-14 z-10">
                <div class="w-28 h-28 rounded-full overflow-hidden border-4 border-white shadow-lg bg-emerald-100 flex items-center justify-center">
                    @if($user->profilePhoto)
                        <img id="avatar-display" src="{{ str_starts_with($user->profilePhoto, 'http') || str_starts_with($user->profilePhoto, '/') ? $user->profilePhoto : asset('storage/' . $user->profilePhoto) }}" class="w-full h-full object-cover">
                    @else
                        <span class="text-3xl font-extrabold text-emerald-700">{{ strtoupper(substr($user->name, 0, 1)) }}</span>
                    @endif
                </div>
            </div>

            {{-- User Info Card --}}
            <div class="bg-white rounded-3xl pt-16 pb-5 px-6 shadow-xs border border-gray-100/80 relative">
                <div class="flex items-center justify-between gap-3">
                    {{-- Left-aligned Username & Name --}}
                    <div class="text-left min-w-0 pr-2">
                        <h2 class="text-lg font-extrabold text-gray-900 tracking-tight leading-tight truncate">
                            {{ $user->username ?? $user->name }}
                        </h2>
                        @if($user->username && $user->username !== $user->name)
                            <p class="text-xs text-gray-400 font-medium mt-0.5 truncate">{{ $user->name }}</p>
                        @endif
                    </div>

                    {{-- Edit Button (Top Right of Card) --}}
                    <button type="button"
                            @click="showEditModal = true"
                            class="w-9 h-9 bg-gray-50 hover:bg-gray-100 rounded-xl flex items-center justify-center border border-gray-200/80 text-gray-700 shadow-2xs transition-all cursor-pointer group shrink-0">
                        <svg class="w-4 h-4 text-gray-600 group-hover:text-black transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        {{-- Account Settings Section --}}
        <div>
            <h3 class="text-[10px] font-bold uppercase tracking-widest text-[#C0422A] mb-3 px-1">Account Settings</h3>

            <div class="space-y-2.5">
                {{-- Email --}}
                <div class="bg-white rounded-2xl p-4 shadow-2xs border border-gray-100 flex items-center justify-between">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="w-9 h-9 rounded-xl bg-[#FDF9F4] text-[#C0422A] border border-[#C0422A]/20 flex items-center justify-center shrink-0">
                            <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <span class="font-bold text-sm text-gray-900">Email</span>
                    </div>
                    <span class="text-xs font-semibold text-gray-500 truncate max-w-45 sm:max-w-xs">{{ $user->email }}</span>
                </div>

                {{-- Saved Address (Opens Modal) --}}
                <button type="button"
                        @click="openSavedAddresses()"
                        class="w-full bg-white rounded-2xl p-4 shadow-2xs border border-gray-100 flex items-center justify-between hover:bg-gray-50/80 transition-colors group cursor-pointer text-left">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-[#FDF9F4] text-[#C0422A] border border-[#C0422A]/20 flex items-center justify-center shrink-0">
                            <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                        </div>
                        <span class="font-bold text-sm text-gray-900">Saved address</span>
                    </div>
                    <svg class="w-4 h-4 text-gray-400 group-hover:text-black group-hover:translate-x-0.5 transition-all" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
                    </svg>
                </button>

                {{-- Orders --}}
                <a href="{{ route('orders') }}" class="bg-white rounded-2xl p-4 shadow-2xs border border-gray-100 flex items-center justify-between hover:bg-gray-50/80 transition-colors group cursor-pointer">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-[#FDF9F4] text-[#C0422A] border border-[#C0422A]/20 flex items-center justify-center shrink-0">
                            <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                            </svg>
                        </div>
                        <span class="font-bold text-sm text-gray-900">Orders</span>
                    </div>
                    <svg class="w-4 h-4 text-gray-400 group-hover:text-black group-hover:translate-x-0.5 transition-all" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>

                {{-- Change Password (Opens Modal) --}}
                <button type="button"
                        @click="showPasswordModal = true"
                        class="w-full bg-white rounded-2xl p-4 shadow-2xs border border-gray-100 flex items-center justify-between hover:bg-gray-50/80 transition-colors group cursor-pointer text-left">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-[#FDF9F4] text-[#C0422A] border border-[#C0422A]/20 flex items-center justify-center shrink-0">
                            <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                        </div>
                        <span class="font-bold text-sm text-gray-900">Change Password</span>
                    </div>
                </button>

                {{-- Logout (Mobile Screens Only) --}}
                @auth
                <div class="sm:hidden pt-1">
                    <form x-ref="mobileProfileLogoutForm" action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="button"
                                @click="$dispatch('open-confirmation', { 
                                    title: 'Logout', 
                                    message: 'Are you sure you want to logout?', 
                                    confirmText: 'Logout', 
                                    type: 'danger', 
                                    onConfirm: () => $refs.mobileProfileLogoutForm.submit() 
                                })"
                                class="w-full bg-red-50 hover:bg-red-100/80 rounded-2xl p-4 shadow-2xs border border-red-100 flex items-center justify-between transition-colors group cursor-pointer text-left">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-xl bg-red-100/80 text-red-600 border border-red-200/50 flex items-center justify-center shrink-0">
                                    <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                                    </svg>
                                </div>
                                <span class="font-bold text-sm text-red-600">Logout</span>
                            </div>
                            <svg class="w-4 h-4 text-red-400 group-hover:text-red-600 group-hover:translate-x-0.5 transition-all" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
                            </svg>
                        </button>
                    </form>
                </div>
                @endauth
            </div>
        </div>

    </div>

    {{-- Edit Profile Modal --}}
    <div x-show="showEditModal"
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         @keydown.escape.window="showEditModal = false">

        <div class="relative w-full max-w-sm bg-white rounded-3xl p-6 shadow-2xl border border-gray-100 space-y-4"
             @click.away="showEditModal = false">

            <div class="flex items-center justify-between pb-2 border-b border-gray-100">
                <h3 class="text-base font-extrabold text-gray-900">Edit Profile</h3>
                <button type="button" @click="showEditModal = false" class="text-gray-400 hover:text-black transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <form action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                @csrf
                @method('PUT')

                {{-- Profile Picture Preview & Upload --}}
                <div class="text-center space-y-2">
                    <div class="relative w-20 h-20 mx-auto rounded-full overflow-hidden border-2 border-gray-200 bg-gray-50 flex items-center justify-center group">
                        <img id="modal-avatar-preview"
                             src="{{ $user->profilePhoto ? (str_starts_with($user->profilePhoto, 'http') || str_starts_with($user->profilePhoto, '/') ? $user->profilePhoto : asset('storage/' . $user->profilePhoto)) : asset('uploads/products/default.jpg') }}"
                             class="w-full h-full object-cover">
                        <label class="absolute inset-0 bg-black/40 text-white opacity-0 group-hover:opacity-100 transition-opacity flex flex-col items-center justify-center cursor-pointer">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><circle cx="12" cy="13" r="4"/></svg>
                            <span class="text-[9px] font-bold uppercase mt-0.5">Change</span>
                            <input type="file" name="avatar" accept="image/*" class="hidden" onchange="previewModalAvatar(this)">
                        </label>
                    </div>
                    <p class="text-[10px] text-gray-400 font-medium">Click photo to upload new picture</p>
                </div>

                {{-- Username Input --}}
                <div class="space-y-1">
                    <label class="text-xs font-bold text-gray-700" for="modal-username">Username</label>
                    <input id="modal-username"
                           type="text"
                           name="username"
                           value="{{ old('username', $user->username ?? $user->name) }}"
                           required
                           class="w-full h-10 px-3.5 bg-gray-50 border border-gray-200 rounded-xl text-xs font-medium outline-none focus:border-[#C0422A] focus:bg-white transition-colors">
                </div>

                {{-- Name Input --}}
                <div class="space-y-1">
                    <label class="text-xs font-bold text-gray-700" for="modal-name">Full Name</label>
                    <input id="modal-name"
                           type="text"
                           name="name"
                           value="{{ old('name', $user->name) }}"
                           required
                           class="w-full h-10 px-3.5 bg-gray-50 border border-gray-200 rounded-xl text-xs font-medium outline-none focus:border-[#C0422A] focus:bg-white transition-colors">
                </div>

                {{-- Actions --}}
                <div class="flex items-center gap-3 pt-2">
                    <button type="button"
                            @click="showEditModal = false"
                            class="flex-1 py-2.5 px-4 rounded-xl border border-gray-200 text-gray-700 text-xs font-bold uppercase tracking-wider hover:bg-gray-50 transition-all cursor-pointer">
                        Cancel
                    </button>
                    <button type="submit"
                            class="flex-1 py-2.5 px-4 rounded-xl bg-[#C0422A] hover:bg-black text-white text-xs font-bold uppercase tracking-wider shadow-md active:scale-95 transition-all cursor-pointer">
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Change Password Modal --}}
    <div x-show="showPasswordModal"
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         @keydown.escape.window="showPasswordModal = false">

        <div class="relative w-full max-w-sm bg-white rounded-3xl p-6 shadow-2xl border border-gray-100 space-y-4"
             @click.away="showPasswordModal = false"
             x-data="{ showCurrent: false, showNew: false, showConfirm: false, strength: 0, label: '', checkStrength(v){ let s=0; if(v.length>=8)s++; if(/[A-Z]/.test(v))s++; if(/[0-9]/.test(v))s++; if(/[^A-Za-z0-9]/.test(v))s++; this.strength=s; this.label=['','Weak','Fair','Good','Strong'][s]||''; } }">

            <div class="flex items-center justify-between pb-2 border-b border-gray-100">
                <div>
                    <h3 class="text-base font-extrabold text-gray-900">Change Password</h3>
                    <p class="text-[10px] text-gray-400 font-medium">Do not share your password with others</p>
                </div>
                <button type="button" @click="showPasswordModal = false" class="text-gray-400 hover:text-black transition-colors p-1">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            @if($errors->has('current_password') || $errors->has('password'))
                <div class="p-3 bg-red-50 border border-red-100 rounded-2xl text-xs text-red-600 space-y-1">
                    @foreach($errors->get('current_password') as $err)
                        <p class="font-semibold">• {{ $err }}</p>
                    @endforeach
                    @foreach($errors->get('password') as $err)
                        <p class="font-semibold">• {{ $err }}</p>
                    @endforeach
                </div>
            @endif

            <form action="{{ route('profile.change-password.submit') }}" method="POST" class="space-y-3.5">
                @csrf

                {{-- Current Password --}}
                <div class="space-y-1">
                    <label class="text-xs font-bold text-gray-700">Current Password</label>
                    <div class="relative">
                        <input :type="showCurrent ? 'text' : 'password'"
                               name="current_password"
                               required
                               placeholder="Enter current password"
                               class="w-full h-10 px-3.5 pr-10 bg-gray-50 border {{ $errors->has('current_password') ? 'border-red-400 bg-red-50' : 'border-gray-200' }} rounded-xl text-xs font-medium outline-none focus:border-[#C0422A] focus:bg-white transition-colors">
                        <button type="button" @click="showCurrent = !showCurrent" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                            <svg x-show="!showCurrent" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            <svg x-show="showCurrent" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                        </button>
                    </div>
                </div>

                {{-- New Password --}}
                <div class="space-y-1">
                    <label class="text-xs font-bold text-gray-700">New Password</label>
                    <div class="relative">
                        <input :type="showNew ? 'text' : 'password'"
                               name="password"
                               required
                               placeholder="At least 8 characters"
                               x-on:input="checkStrength($event.target.value)"
                               class="w-full h-10 px-3.5 pr-10 bg-gray-50 border {{ $errors->has('password') ? 'border-red-400 bg-red-50' : 'border-gray-200' }} rounded-xl text-xs font-medium outline-none focus:border-[#C0422A] focus:bg-white transition-colors">
                        <button type="button" @click="showNew = !showNew" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                            <svg x-show="!showNew" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            <svg x-show="showNew" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                        </button>
                    </div>
                    {{-- Strength indicator --}}
                    <div class="pt-0.5">
                        <div class="flex gap-1">
                            <div class="h-1 flex-1 rounded" :class="strength>=1 ? (strength==1?'bg-red-400':'bg-yellow-400') : 'bg-gray-100'"></div>
                            <div class="h-1 flex-1 rounded" :class="strength>=2 ? (strength==2?'bg-yellow-400':'bg-green-400') : 'bg-gray-100'"></div>
                            <div class="h-1 flex-1 rounded" :class="strength>=3 ? 'bg-green-400' : 'bg-gray-100'"></div>
                            <div class="h-1 flex-1 rounded" :class="strength>=4 ? 'bg-green-600' : 'bg-gray-100'"></div>
                        </div>
                        <p x-show="label" class="text-[10px] font-semibold mt-1" :class="{'text-red-500':strength==1,'text-yellow-600':strength==2,'text-green-500':strength>=3}" x-text="'Password strength: '+label"></p>
                    </div>
                </div>

                {{-- Confirm Password --}}
                <div class="space-y-1">
                    <label class="text-xs font-bold text-gray-700">Confirm Password</label>
                    <div class="relative">
                        <input :type="showConfirm ? 'text' : 'password'"
                               name="password_confirmation"
                               required
                               placeholder="Re-enter new password"
                               class="w-full h-10 px-3.5 pr-10 bg-gray-50 border border-gray-200 rounded-xl text-xs font-medium outline-none focus:border-[#C0422A] focus:bg-white transition-colors">
                        <button type="button" @click="showConfirm = !showConfirm" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                            <svg x-show="!showConfirm" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            <svg x-show="showConfirm" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                        </button>
                    </div>
                </div>

                {{-- Actions --}}
                <div class="flex items-center gap-3 pt-2">
                    <button type="button"
                            @click="showPasswordModal = false"
                            class="flex-1 py-2.5 px-4 rounded-xl border border-gray-200 text-gray-700 text-xs font-bold uppercase tracking-wider hover:bg-gray-50 transition-all cursor-pointer">
                        Cancel
                    </button>
                    <button type="submit"
                            class="flex-1 py-2.5 px-4 rounded-xl bg-[#C0422A] hover:bg-black text-white text-xs font-bold uppercase tracking-wider shadow-md active:scale-95 transition-all cursor-pointer">
                        Save Password
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Saved Address Modal --}}
    <div x-show="showAddressModal"
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs"
         x-transition
         @click.self="if (!showDeleteConfirmModal && !addEditModalOpen) showAddressModal = false">

        <div class="relative w-full max-w-lg bg-white rounded-3xl p-6 shadow-2xl border border-gray-100 max-h-[85vh] flex flex-col space-y-4">

            <div class="flex items-center justify-between pb-3 border-b border-gray-100 shrink-0">
                <div>
                    <h3 class="text-base font-extrabold text-gray-900">Saved Addresses</h3>
                    <p class="text-[10px] text-gray-400 font-medium">Manage your shipping destinations</p>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" @click="openAddAddress()" class="px-3 py-1.5 bg-[#C0422A] text-white rounded-xl text-xs font-bold shadow-xs hover:bg-black transition-all">
                        + Add Address
                    </button>
                    <button type="button" @click="showAddressModal = false" class="text-gray-400 hover:text-black transition-colors p-1">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
            </div>

            {{-- Address List Body --}}
            <div class="flex-1 overflow-y-auto space-y-3 pr-1">
                <template x-if="loadingAddresses">
                    <div class="py-12 text-center text-xs text-gray-400">Loading saved addresses...</div>
                </template>

                <template x-if="!loadingAddresses && addresses.length === 0">
                    <div class="py-12 text-center space-y-2">
                        <svg class="w-10 h-10 text-gray-200 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        <p class="text-xs font-bold text-gray-400">No addresses saved yet</p>
                    </div>
                </template>

                <template x-if="!loadingAddresses">
                    <div class="space-y-3">
                        <template x-for="addr in addresses" :key="addr.id">
                            <div class="p-3.5 border border-gray-150 rounded-2xl bg-gray-50/50 hover:bg-white hover:border-gray-300 transition-all space-y-2">
                                <div class="flex items-start justify-between gap-2">
                                    <div>
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <span class="text-xs font-bold text-gray-900" x-text="addr.recipientName"></span>
                                            <span class="text-xs text-gray-400 font-semibold" x-text="addr.phone"></span>
                                            <template x-if="addr.isDefault">
                                                <span class="px-2 py-0.5 bg-[#C0422A]/10 text-[#C0422A] text-[9px] font-black uppercase rounded">Default</span>
                                            </template>
                                        </div>
                                        <p class="text-xs text-gray-600 mt-1 leading-relaxed"
                                           x-text="[addr.houseNo, addr.street, addr.barangay, addr.city, addr.province, addr.postalCode].filter(Boolean).join(', ')">
                                        </p>
                                    </div>
                                    <div class="flex items-center gap-2 shrink-0">
                                        <button type="button" @click="openEditAddress(addr)" class="text-[11px] font-bold text-[#C0422A] hover:underline">Edit</button>
                                        <button type="button" @click="promptDeleteAddress(addr.id)" class="text-[11px] font-bold text-gray-400 hover:text-red-500 hover:underline">Delete</button>
                                    </div>
                                </div>
                                <template x-if="!addr.isDefault">
                                    <button type="button" @click="setDefaultAddress(addr.id)" class="text-[10px] font-bold text-gray-500 hover:text-black border border-gray-200 px-2 py-0.5 rounded-md hover:bg-white transition-all">
                                        Set as Default
                                    </button>
                                </template>
                            </div>
                        </template>
                    </div>
                </template>
            </div>

            {{-- Inner Add / Edit Form Modal Popup --}}
            <div x-show="addEditModalOpen" class="fixed inset-0 z-60 flex items-center justify-center p-4 bg-black/50" style="z-index: 60;" x-cloak>
                <div class="relative bg-white rounded-3xl shadow-2xl w-full max-w-md p-6 space-y-4 max-h-[85vh] overflow-y-auto" @click.away="addEditModalOpen = false">
                    <h4 class="text-sm font-extrabold text-gray-900" x-text="editAddressId ? 'Edit Address' : 'Add New Address'"></h4>

                    <div class="space-y-3 text-xs">
                        {{-- Full Name --}}
                        <div>
                            <label class="font-bold text-gray-700 mb-1 block">Full Name *</label>
                            <input x-model="addressForm.recipientName"
                                   @input="fieldErrors.recipientName = ''; addressForm.recipientName = addressForm.recipientName.replace(/[^a-zA-Z\u00C0-\u024F\s.'-]/g, '')"
                                   type="text" placeholder="Recipient's full name (letters only)"
                                   maxlength="30"
                                   :class="fieldErrors.recipientName ? 'border-red-400 bg-red-50' : 'border-gray-200'"
                                   class="w-full h-9 px-3 border rounded-xl outline-none focus:border-[#C0422A] transition-colors">
                            <p x-show="fieldErrors.recipientName" x-text="fieldErrors.recipientName" class="mt-1 text-[10px] text-red-500 font-semibold"></p>
                        </div>
                        {{-- Phone Number --}}
                        <div>
                            <label class="font-bold text-gray-700 mb-1 block">Phone Number *</label>
                            <input x-model="addressForm.phone"
                                   @input="fieldErrors.phone = ''; addressForm.phone = addressForm.phone.replace(/[^0-9+]/g, '')"
                                   type="text" placeholder="e.g. 09XXXXXXXXX"
                                   maxlength="11"
                                   :class="fieldErrors.phone ? 'border-red-400 bg-red-50' : 'border-gray-200'"
                                   class="w-full h-9 px-3 border rounded-xl outline-none focus:border-[#C0422A] transition-colors">
                            <p x-show="fieldErrors.phone" x-text="fieldErrors.phone" class="mt-1 text-[10px] text-red-500 font-semibold"></p>
                        </div>

                        {{-- Location Dropdown Selector --}}
                        <div class="relative">
                            <label class="font-bold text-gray-700 mb-1 block">Region, Province, City, Barangay *</label>
                            <div @click="toggleLocationDropdown(); fieldErrors.location = ''"
                                 :class="fieldErrors.location ? 'border-red-400 bg-red-50' : 'border-gray-200 bg-gray-50'"
                                 class="w-full h-9 px-3 border rounded-xl flex items-center justify-between cursor-pointer transition-colors">
                                <span class="truncate" :class="getLocationSummary() ? 'text-gray-900 font-semibold' : 'text-gray-400'" x-text="getLocationSummary() || 'Select Region, Province, City, Barangay'"></span>
                                <svg class="w-4 h-4 text-gray-400" :class="locationDropdownOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </div>
                            <p x-show="fieldErrors.location" x-text="fieldErrors.location" class="mt-1 text-[10px] text-red-500 font-semibold"></p>

                            <div x-show="locationDropdownOpen" @click.away="locationDropdownOpen = false"
                                 class="absolute left-0 right-0 z-50 mt-1 bg-white border border-gray-200 rounded-xl shadow-xl overflow-hidden flex flex-col max-h-62.5" x-cloak>
                                <div class="flex border-b border-gray-100 bg-gray-50 text-[10px] font-bold text-gray-500">
                                    <button @click="activeTab = 'region'" type="button" :class="activeTab === 'region' ? 'text-[#C0422A] bg-white' : ''" class="flex-1 py-2 text-center">Region</button>
                                    <button @click="if(selectedRegion && hasProvinces) activeTab = 'province'" type="button" :disabled="!selectedRegion || !hasProvinces" :class="activeTab === 'province' ? 'text-[#C0422A] bg-white' : ''" class="flex-1 py-2 text-center disabled:opacity-40">Province</button>
                                    <button @click="if(selectedProvince || (selectedRegion && !hasProvinces)) activeTab = 'city'" type="button" :disabled="!selectedProvince && (hasProvinces || !selectedRegion)" :class="activeTab === 'city' ? 'text-[#C0422A] bg-white' : ''" class="flex-1 py-2 text-center disabled:opacity-40">City</button>
                                    <button @click="if(selectedCity) activeTab = 'barangay'" type="button" :disabled="!selectedCity" :class="activeTab === 'barangay' ? 'text-[#C0422A] bg-white' : ''" class="flex-1 py-2 text-center disabled:opacity-40">Barangay</button>
                                </div>
                                <div class="p-1.5 border-b border-gray-100 bg-gray-50/50">
                                    <input type="text" x-model="locationSearch" :placeholder="'Search ' + activeTab + '...'" class="w-full h-7 px-2 border border-gray-200 rounded-md text-[11px]">
                                </div>
                                <div class="flex-1 overflow-y-auto max-h-40 divide-y divide-gray-50 text-[11px]">
                                    <template x-if="activeTab === 'region' && !loadingGeoData">
                                        <div>
                                            <template x-for="reg in filteredGeoList(regionsList)" :key="reg.code">
                                                <button type="button" @click="selectRegion(reg)" class="w-full text-left px-3 py-1.5 hover:bg-gray-50 block truncate" x-text="reg.name"></button>
                                            </template>
                                        </div>
                                    </template>
                                    <template x-if="activeTab === 'province' && !loadingGeoData">
                                        <div>
                                            <template x-for="prov in filteredGeoList(provincesList)" :key="prov.code">
                                                <button type="button" @click="selectProvince(prov)" class="w-full text-left px-3 py-1.5 hover:bg-gray-50 block truncate" x-text="prov.name"></button>
                                            </template>
                                        </div>
                                    </template>
                                    <template x-if="activeTab === 'city' && !loadingGeoData">
                                        <div>
                                            <template x-for="ct in filteredGeoList(citiesList)" :key="ct.code">
                                                <button type="button" @click="selectCity(ct)" class="w-full text-left px-3 py-1.5 hover:bg-gray-50 block truncate" x-text="ct.name"></button>
                                            </template>
                                        </div>
                                    </template>
                                    <template x-if="activeTab === 'barangay' && !loadingGeoData">
                                        <div>
                                            <template x-for="bgy in filteredGeoList(barangaysList)" :key="bgy.code">
                                                <button type="button" @click="selectBarangay(bgy)" class="w-full text-left px-3 py-1.5 hover:bg-gray-50 block truncate" x-text="bgy.name"></button>
                                            </template>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>

                        {{-- Street --}}
                        <div>
                            <label class="font-bold text-gray-700 mb-1 block">Street Name, Building, House No. *</label>
                            <input x-model="addressForm.houseNo"
                                   @input="fieldErrors.houseNo = ''"
                                   type="text" placeholder="e.g. Unit 402, Sunset Bldg, Main St."
                                   maxlength="150"
                                   :class="fieldErrors.houseNo ? 'border-red-400 bg-red-50' : 'border-gray-200'"
                                   class="w-full h-9 px-3 border rounded-xl outline-none focus:border-[#C0422A] transition-colors">
                            <p x-show="fieldErrors.houseNo" x-text="fieldErrors.houseNo" class="mt-1 text-[10px] text-red-500 font-semibold"></p>
                        </div>

                        {{-- Postal Code --}}
                        <div>
                            <label class="font-bold text-gray-700 mb-1 block">Postal Code</label>
                            <input x-model="addressForm.postalCode"
                                   @input="fieldErrors.postalCode = ''; addressForm.postalCode = addressForm.postalCode.replace(/[^0-9]/g, '')"
                                   type="text" placeholder="e.g. 1000"
                                   maxlength="4"
                                   :class="fieldErrors.postalCode ? 'border-red-400 bg-red-50' : 'border-gray-200'"
                                   class="w-full h-9 px-3 border rounded-xl outline-none focus:border-[#C0422A] transition-colors">
                            <p x-show="fieldErrors.postalCode" x-text="fieldErrors.postalCode" class="mt-1 text-[10px] text-red-500 font-semibold"></p>
                        </div>

                        <label class="flex items-center gap-2 cursor-pointer pt-1">
                            <input type="checkbox" x-model="addressForm.isDefault" class="accent-[#C0422A]">
                            <span class="text-xs font-semibold text-gray-700">Set as default shipping address</span>
                        </label>
                    </div>

                    <div class="flex gap-3 pt-2">
                        <button type="button" @click="addEditModalOpen = false" class="flex-1 py-2 border border-gray-200 text-xs font-bold text-gray-600 rounded-xl hover:bg-gray-50">Cancel</button>
                        <button type="button" @click="saveAddress()" :disabled="savingAddress" class="flex-1 py-2 bg-[#C0422A] text-white text-xs font-bold rounded-xl hover:bg-black shadow-xs">
                            <span x-text="savingAddress ? 'Saving...' : 'Save Address'"></span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Custom Delete Address Confirmation Modal (sibling, NOT nested) --}}
    <div x-show="showDeleteConfirmModal"
         x-cloak
         class="fixed inset-0 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs"
         style="z-index: 9999;"
         @click.self="showDeleteConfirmModal = false">

        <div class="relative w-full max-w-xs bg-white rounded-3xl p-6 shadow-2xl border border-gray-100 text-center space-y-4">

            <div class="w-12 h-12 rounded-full bg-red-50 text-red-500 border border-red-100 flex items-center justify-center mx-auto shadow-xs">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
            </div>

            <div>
                <h3 class="text-base font-extrabold text-gray-900">Delete Address</h3>
                <p class="text-xs text-gray-500 font-medium mt-1 leading-relaxed">Are you sure you want to delete this address?</p>
            </div>

            <div class="flex items-center gap-3 pt-2">
                <button type="button"
                        @click="showDeleteConfirmModal = false"
                        class="flex-1 py-2.5 px-4 rounded-xl border border-gray-200 text-gray-700 text-xs font-bold uppercase tracking-wider hover:bg-gray-50 transition-all cursor-pointer">
                    Cancel
                </button>
                <button type="button"
                        @click="confirmDeleteAddress()"
                        class="flex-1 py-2.5 px-4 rounded-xl bg-red-600 hover:bg-red-700 text-white text-xs font-bold uppercase tracking-wider shadow-lg shadow-red-600/20 active:scale-95 transition-all cursor-pointer">
                    OK
                </button>
            </div>
        </div>
    </div>

</div>


<script>
function profileApp() {
    return {
        showEditModal: false,
        showAddressModal: false,
        showDeleteConfirmModal: false,
        showPasswordModal: {{ $errors->has('current_password') || $errors->has('password') || request()->has('change_password') ? 'true' : 'false' }},
        pendingDeleteAddressId: null,

        // Address management state
        addresses: [],
        loadingAddresses: true,
        addEditModalOpen: false,
        savingAddress: false,
        editAddressId: null,
        addressFormError: '',
        fieldErrors: { recipientName: '', phone: '', location: '', houseNo: '', postalCode: '' },
        addressForm: { recipientName:'', phone:'', houseNo:'', street:'', barangay:'', city:'', province:'', region:'', postalCode:'', isDefault: false },

        // Location picker variables
        locationDropdownOpen: false,
        activeTab: 'region',
        locationSearch: '',
        regionsList: [],
        provincesList: [],
        citiesList: [],
        barangaysList: [],
        selectedRegion: null,
        selectedProvince: null,
        selectedCity: null,
        selectedBarangay: null,
        loadingGeoData: false,
        hasProvinces: true,

        async init() {
            await this.fetchAddresses();
            // Centralised ESC handler — respects modal priority
            window.addEventListener('keydown', (e) => {
                if (e.key !== 'Escape') return;
                e.preventDefault();
                if (this.showDeleteConfirmModal) {
                    this.showDeleteConfirmModal = false;
                } else if (this.addEditModalOpen) {
                    this.addEditModalOpen = false;
                } else if (this.showAddressModal) {
                    this.showAddressModal = false;
                } else if (this.showPasswordModal) {
                    this.showPasswordModal = false;
                } else if (this.showEditModal) {
                    this.showEditModal = false;
                }
            });
        },

        async fetchAddresses() {
            this.loadingAddresses = true;
            try {
                const r = await fetch('/api/addresses', { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                this.addresses = await r.json();
            } catch(e) { this.addresses = []; }
            this.loadingAddresses = false;
        },

        openSavedAddresses() {
            this.showAddressModal = true;
            this.fetchAddresses();
        },

        openAddAddress() {
            this.editAddressId = null;
            this.addressForm = { recipientName:'', phone:'', houseNo:'', street:'', barangay:'', city:'', province:'', region:'', postalCode:'', isDefault: false };
            this.addressFormError = '';
            this.fieldErrors = { recipientName: '', phone: '', location: '', houseNo: '', postalCode: '' };
            this.selectedRegion = null;
            this.selectedProvince = null;
            this.selectedCity = null;
            this.selectedBarangay = null;
            this.activeTab = 'region';
            this.locationSearch = '';
            this.locationDropdownOpen = false;
            this.addEditModalOpen = true;
        },

        openEditAddress(addr) {
            this.editAddressId = addr.id;
            this.addressForm = { ...addr };
            this.addressFormError = '';
            this.fieldErrors = { recipientName: '', phone: '', location: '', houseNo: '', postalCode: '' };
            this.selectedRegion = addr.region ? { name: addr.region } : null;
            this.selectedProvince = addr.province ? { name: addr.province } : null;
            this.selectedCity = addr.city ? { name: addr.city } : null;
            this.selectedBarangay = addr.barangay ? { name: addr.barangay } : null;
            this.activeTab = 'region';
            this.locationSearch = '';
            this.locationDropdownOpen = false;
            this.addEditModalOpen = true;
        },

        async saveAddress() {
            // Per-field validation
            this.fieldErrors = { recipientName: '', phone: '', location: '', houseNo: '', postalCode: '' };
            let hasError = false;

            const name = (this.addressForm.recipientName || '').trim();
            if (!name) {
                this.fieldErrors.recipientName = 'Full name is required.';
                hasError = true;
            } else if (name.length < 2) {
                this.fieldErrors.recipientName = 'Name must be at least 2 characters.';
                hasError = true;
            } else if (!/^[a-zA-Z\u00C0-\u024F\s.'\-]+$/.test(name)) {
                this.fieldErrors.recipientName = 'Full name must contain letters only.';
                hasError = true;
            }

            const phone = (this.addressForm.phone || '').trim();
            if (!phone) {
                this.fieldErrors.phone = 'Phone number is required.';
                hasError = true;
            } else if (!/^09\d{9}$/.test(phone)) {
                this.fieldErrors.phone = 'Enter a valid PH number (09XXXXXXXXX, 11 digits).';
                hasError = true;
            }

            if (!this.addressForm.city || !this.addressForm.province) {
                this.fieldErrors.location = 'Please select Region, Province, City, and Barangay.';
                hasError = true;
            }

            const street = (this.addressForm.houseNo || '').trim();
            if (!street) {
                this.fieldErrors.houseNo = 'Street / house number is required.';
                hasError = true;
            }

            const postal = (this.addressForm.postalCode || '').trim();
            if (postal && (!/^\d{4}$/.test(postal))) {
                this.fieldErrors.postalCode = 'Postal code must be exactly 4 digits.';
                hasError = true;
            }

            if (hasError) return;

            this.addressFormError = '';
            this.savingAddress = true;
            const token = document.querySelector('meta[name="csrf-token"]').content;
            try {
                const url = this.editAddressId ? `/api/addresses/${this.editAddressId}` : '/api/addresses';
                const method = this.editAddressId ? 'PUT' : 'POST';
                const r = await fetch(url, {
                    method, headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token, 'X-Requested-With': 'XMLHttpRequest' },
                    body: JSON.stringify(this.addressForm)
                });
                if (!r.ok) { const d = await r.json(); this.addressFormError = d.message ?? 'Failed to save.'; }
                else { this.addEditModalOpen = false; await this.fetchAddresses(); }
            } catch(e) { this.addressFormError = 'Network error. Please try again.'; }
            this.savingAddress = false;
        },

        promptDeleteAddress(id) {
            this.pendingDeleteAddressId = id;
            this.showDeleteConfirmModal = true;
        },

        async confirmDeleteAddress() {
            if (!this.pendingDeleteAddressId) return;
            const id = this.pendingDeleteAddressId;
            this.showDeleteConfirmModal = false;
            this.pendingDeleteAddressId = null;

            const token = document.querySelector('meta[name="csrf-token"]').content;
            await fetch(`/api/addresses/${id}`, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': token, 'X-Requested-With': 'XMLHttpRequest' } });
            await this.fetchAddresses();
        },

        async setDefaultAddress(id) {
            const token = document.querySelector('meta[name="csrf-token"]').content;
            await fetch(`/api/addresses/${id}/set-default`, { method:'PATCH', headers:{ 'X-CSRF-TOKEN': token, 'X-Requested-With': 'XMLHttpRequest' } });
            await this.fetchAddresses();
        },

        async toggleLocationDropdown() {
            this.locationDropdownOpen = !this.locationDropdownOpen;
            if (this.locationDropdownOpen && this.regionsList.length === 0) {
                await this.loadRegions();
            }
        },

        async loadRegions() {
            this.loadingGeoData = true;
            try {
                const res = await fetch('https://psgc.gitlab.io/api/regions/');
                if (res.ok) this.regionsList = await res.json();
            } catch(e) {}
            this.loadingGeoData = false;
        },

        async selectRegion(region) {
            this.selectedRegion = region;
            this.addressForm.region = region.name;
            this.selectedProvince = null; this.addressForm.province = '';
            this.selectedCity = null; this.addressForm.city = '';
            this.selectedBarangay = null; this.addressForm.barangay = '';
            this.locationSearch = '';
            if (region.code === '130000000') {
                this.hasProvinces = false;
                this.provincesList = [];
                this.addressForm.province = 'Metro Manila';
                this.selectedProvince = { code: '130000000', name: 'Metro Manila' };
                this.activeTab = 'city';
                await this.loadNCRCities();
            } else {
                this.hasProvinces = true;
                this.activeTab = 'province';
                await this.loadProvinces(region.code);
            }
        },

        async loadNCRCities() {
            this.loadingGeoData = true;
            try {
                const res = await fetch('https://psgc.gitlab.io/api/regions/130000000/cities-municipalities/');
                if (res.ok) this.citiesList = await res.json();
            } catch(e) {}
            this.loadingGeoData = false;
        },

        async loadProvinces(regionCode) {
            this.loadingGeoData = true;
            try {
                const res = await fetch(`https://psgc.gitlab.io/api/regions/${regionCode}/provinces/`);
                if (res.ok) this.provincesList = await res.json();
            } catch(e) {}
            this.loadingGeoData = false;
        },

        async selectProvince(province) {
            this.selectedProvince = province;
            this.addressForm.province = province.name;
            this.selectedCity = null; this.addressForm.city = '';
            this.selectedBarangay = null; this.addressForm.barangay = '';
            this.locationSearch = '';
            this.activeTab = 'city';
            await this.loadCities(province.code);
        },

        async loadCities(provinceCode) {
            this.loadingGeoData = true;
            try {
                const res = await fetch(`https://psgc.gitlab.io/api/provinces/${provinceCode}/cities-municipalities/`);
                if (res.ok) this.citiesList = await res.json();
            } catch(e) {}
            this.loadingGeoData = false;
        },

        async selectCity(city) {
            this.selectedCity = city;
            this.addressForm.city = city.name;
            this.selectedBarangay = null; this.addressForm.barangay = '';
            this.locationSearch = '';
            this.activeTab = 'barangay';
            await this.loadBarangays(city.code);
        },

        async loadBarangays(cityCode) {
            this.loadingGeoData = true;
            try {
                const res = await fetch(`https://psgc.gitlab.io/api/cities-municipalities/${cityCode}/barangays/`);
                if (res.ok) this.barangaysList = await res.json();
            } catch(e) {}
            this.loadingGeoData = false;
        },

        selectBarangay(barangay) {
            this.selectedBarangay = barangay;
            this.addressForm.barangay = barangay.name;
            this.locationDropdownOpen = false;
            this.locationSearch = '';
        },

        filteredGeoList(list) {
            if (!this.locationSearch) return list;
            const q = this.locationSearch.toLowerCase();
            return list.filter(item => item.name && item.name.toLowerCase().includes(q));
        },

        getLocationSummary() {
            if (this.addressForm.region || this.addressForm.province || this.addressForm.city || this.addressForm.barangay) {
                return [this.addressForm.region, this.addressForm.province, this.addressForm.city, this.addressForm.barangay].filter(Boolean).join(', ');
            }
            return '';
        }
    };
}

function previewModalAvatar(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => {
            document.getElementById('modal-avatar-preview').src = e.target.result;
            const topDisplay = document.getElementById('avatar-display');
            if (topDisplay) topDisplay.src = e.target.result;
        };
        reader.readAsDataURL(input.files[0]);
    }
}
</script>
@endsection
