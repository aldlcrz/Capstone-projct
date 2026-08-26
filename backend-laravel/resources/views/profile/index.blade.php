@extends('layouts.app')

@section('content')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin=""/>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>

<div id="profile-root"
     data-show-password="{{ $errors->has('current_password') || $errors->has('password') || request()->has('change_password') ? 'true' : 'false' }}"
     style="min-height:calc(100vh - 80px);background-color:#FAF8F5;padding:32px 16px;" 
     x-data="profileApp()" 
     x-init="init()">
    <div style="max-width:500px;margin:0 auto;background-color:#FDFBF7;border:1px solid #EAE2D2;border-radius:28px;box-shadow:0 20px 50px rgba(0,0,0,0.06);padding:26px 24px;color:#1E1915;">

        {{-- Top Header with Heraldic Laurel Wreath --}}
        <div style="display:flex;align-items:center;gap:14px;flex-shrink:0;">
            <div style="width:48px;height:48px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <svg width="46" height="46" viewBox="0 0 48 48" fill="none">
                    <!-- Central Medallion -->
                    <circle cx="24" cy="23" r="10.5" stroke="#C49520" stroke-width="1" stroke-dasharray="2 1.5"/>
                    <circle cx="24" cy="23" r="8.5" stroke="#C49520" stroke-width="0.8"/>
                    <path d="M24 17.5l1.6 3.4 3.7.5-2.7 2.6.6 3.7-3.2-1.7-3.2 1.7.6-3.7-2.7-2.6 3.7-.5L24 17.5z" fill="#C49520"/>
                    <!-- Laurel Wreath Left -->
                    <path d="M15 32.5c-4-3.5-6-8.5-6-14 0-3.5 1-6.5 2.5-9" stroke="#C49520" stroke-width="1.3" stroke-linecap="round"/>
                    <path d="M10 12c1.8 1.2 3.5 2.8 4 4.5M8 17.5c2 .6 3.8 1.8 4.8 3.5M8 23.5c2 0 3.8.6 5 2M9.5 29.5c2-.8 3.8-.8 5.2 0M12.5 34c1.8-1.2 3.6-1.5 5-.8" stroke="#C49520" stroke-width="1.2" stroke-linecap="round"/>
                    <!-- Laurel Wreath Right -->
                    <path d="M33 32.5c4-3.5 6-8.5 6-14 0-3.5-1-6.5-2.5-9" stroke="#C49520" stroke-width="1.3" stroke-linecap="round"/>
                    <path d="M38 12c-1.8 1.2-3.5 2.8-4 4.5M40 17.5c-2 .6-3.8 1.8-4.8 3.5M40 23.5c-2 0-3.8.6-5 2M38.5 29.5c-2-.8-3.8-.8-5.2 0M35.5 34c-1.8-1.2-3.6-1.5-5-.8" stroke="#C49520" stroke-width="1.2" stroke-linecap="round"/>
                    <!-- Base Ribbon -->
                    <path d="M19 36c3 1.2 7 1.2 10 0" stroke="#C49520" stroke-width="1.3" stroke-linecap="round"/>
                </svg>
            </div>
            <div>
                <h1 style="font-family:ui-serif,Georgia,Cambria,serif;font-size:22px;font-weight:700;color:#1E1915;letter-spacing:-0.01em;line-height:1.2;margin:0;">
                    My Profile & Account
                </h1>
                <p style="font-size:12.5px;color:#78716C;margin-top:3px;margin-bottom:0;">
                    Personal information & account settings
                </p>
            </div>
        </div>

        {{-- Star Divider --}}
        <div style="position:relative;margin:18px 0 20px 0;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
            <div style="width:100%;border-top:1px solid #EAE1D0;"></div>
            <span style="position:absolute;background-color:#FDFBF7;padding:0 12px;color:#C49520;font-size:12px;">✦</span>
        </div>

        {{-- Profile Avatar & User Card --}}
        <div style="position:relative;padding-top:10px;margin-bottom:20px;">
            {{-- Floating Gold-Ringed Avatar --}}
            <div style="width:92px;height:92px;min-width:92px;max-width:92px;min-height:92px;max-height:92px;border-radius:50%;padding:2.5px;background:linear-gradient(135deg,#996515,#E6CA65,#996515);box-shadow:0 4px 14px rgba(0,0,0,0.12);margin:0 auto -46px auto;position:relative;z-index:10;display:block;">
                <div style="width:100%;height:100%;border-radius:50%;overflow:hidden;background-color:#FAF8F5;display:flex;align-items:center;justify-content:center;">
                    @if($user->profilePhoto)
                        <img id="avatar-display" 
                             src="{{ str_starts_with($user->profilePhoto, 'http') || str_starts_with($user->profilePhoto, '/') ? $user->profilePhoto : asset('storage/' . $user->profilePhoto) }}" 
                             style="width:100%;height:100%;border-radius:50%;object-fit:cover;display:block;"
                             alt="{{ $user->name }}">
                    @else
                        <span style="font-size:30px;font-weight:800;color:#996515;">{{ strtoupper(substr($user->name, 0, 1)) }}</span>
                    @endif
                </div>
            </div>

            {{-- User Info Card --}}
            <div style="background-color:#FFFFFF;border:1px solid #ECE3D2;border-radius:20px;padding:56px 20px 18px 20px;box-shadow:0 2px 8px rgba(0,0,0,0.03);display:flex;align-items:center;justify-content:space-between;position:relative;">
                <div style="text-align:left;min-width:0;padding-right:10px;">
                    <h2 style="font-family:ui-serif,Georgia,serif;font-size:19px;font-weight:700;color:#1E1915;letter-spacing:-0.01em;line-height:1.2;margin:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                        {{ $user->username ?? $user->name }}
                    </h2>
                    <p style="font-size:12px;color:#78716C;margin:3px 0 0 0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                        {{ $user->name }} • Customer
                    </p>
                </div>

                {{-- Edit Button (Top Right of Card) --}}
                <button type="button"
                        @click="showEditModal = true"
                        style="width:40px;height:40px;border-radius:12px;background-color:#FAF6EE;border:1px solid #E2D9C8;display:flex;align-items:center;justify-content:center;color:#78716C;cursor:pointer;flex-shrink:0;box-shadow:0 1px 3px rgba(0,0,0,0.05);transition:all 0.2s;"
                        title="Edit Profile">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/>
                    </svg>
                </button>
            </div>
        </div>

        {{-- Account Settings Section --}}
        <div>
            <h3 style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.14em;color:#996515;margin:0 0 12px 2px;">
                Account Settings
            </h3>

            <div style="display:flex;flex-direction:column;gap:10px;">
                {{-- Email --}}
                <div style="background-color:#FFFFFF;border:1px solid #ECE3D2;border-radius:16px;padding:14px 16px;display:flex;align-items:center;gap:12px;box-shadow:0 2px 6px rgba(0,0,0,0.02);">
                    <div style="width:38px;height:38px;border-radius:11px;background-color:#FAF5EA;border:1px solid #E6D8BA;display:flex;align-items:center;justify-content:center;color:#B88728;flex-shrink:0;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <rect x="2" y="4" width="20" height="16" rx="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M22 7l-8.97 5.7a1.94 1.94 0 01-2.06 0L2 7" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    <div style="min-width:0;flex:1;">
                        <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#8C827A;line-height:1.1;">Email Address</div>
                        <div style="font-size:13.5px;font-weight:700;color:#1E1915;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;margin-top:2px;">
                            {{ $user->email }}
                        </div>
                    </div>
                </div>

                {{-- Saved Address (Opens Modal) --}}
                <button type="button"
                        @click="openSavedAddresses()"
                        style="background-color:#FFFFFF;border:1px solid #ECE3D2;border-radius:16px;padding:14px 16px;display:flex;align-items:center;justify-content:space-between;box-shadow:0 2px 6px rgba(0,0,0,0.02);cursor:pointer;width:100%;text-align:left;transition:all 0.2s;"
                        class="hover:border-[#C49520] hover:bg-[#FDFBF7] group">
                    <div style="display:flex;align-items:center;gap:12px;">
                        <div style="width:38px;height:38px;border-radius:11px;background-color:#FAF5EA;border:1px solid #E6D8BA;display:flex;align-items:center;justify-content:center;color:#B88728;flex-shrink:0;" class="group-hover:scale-105 transition-transform">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 21s-7-4.35-7-10a7 7 0 1114 0c0 5.65-7 10-7 10z"/>
                                <circle cx="12" cy="11" r="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                        <div>
                            <div style="font-size:14px;font-weight:700;color:#1E1915;">Shipping Addresses</div>
                            <div style="font-size:11px;color:#8C827A;margin-top:1px;">Manage delivery destinations & GPS pinpoint</div>
                        </div>
                    </div>
                    <div style="display:flex;align-items:center;gap:6px;">
                        <span style="font-size:10px;font-weight:800;color:#996515;background-color:#FAF5EA;border:1px solid #E6D8BA;padding:2px 8px;border-radius:6px;text-transform:uppercase;letter-spacing:0.04em;">Manage</span>
                        <svg width="16" height="16" fill="none" stroke="#8C827A" viewBox="0 0 24 24" stroke-width="2.2" class="group-hover:translate-x-0.5 transition-transform">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                        </svg>
                    </div>
                </button>

                {{-- Orders --}}
                <a href="{{ route('orders') }}" 
                   style="background-color:#FFFFFF;border:1px solid #ECE3D2;border-radius:16px;padding:14px 16px;display:flex;align-items:center;justify-content:space-between;box-shadow:0 2px 6px rgba(0,0,0,0.02);cursor:pointer;width:100%;text-decoration:none;transition:background-color 0.2s;">
                    <div style="display:flex;align-items:center;gap:12px;">
                        <div style="width:38px;height:38px;border-radius:11px;background-color:#FAF5EA;border:1px solid #E6D8BA;display:flex;align-items:center;justify-content:center;color:#B88728;flex-shrink:0;">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                            </svg>
                        </div>
                        <span style="font-size:14px;font-weight:700;color:#1E1915;">Orders</span>
                    </div>
                    <svg width="16" height="16" fill="none" stroke="#8C827A" viewBox="0 0 24 24" stroke-width="2.2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>

                {{-- Change Password --}}
                <a href="{{ route('profile.change-password') }}"
                   style="background-color:#FFFFFF;border:1px solid #ECE3D2;border-radius:16px;padding:14px 16px;display:flex;align-items:center;justify-content:space-between;box-shadow:0 2px 6px rgba(0,0,0,0.02);cursor:pointer;width:100%;text-align:left;transition:background-color 0.2s;text-decoration:none;">
                    <div style="display:flex;align-items:center;gap:12px;">
                        <div style="width:38px;height:38px;border-radius:11px;background-color:#FAF5EA;border:1px solid #E6D8BA;display:flex;align-items:center;justify-content:center;color:#B88728;flex-shrink:0;">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                <rect x="3" y="11" width="18" height="11" rx="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M7 11V7a5 5 0 0110 0v4" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                        <span style="font-size:14px;font-weight:700;color:#1E1915;">Change Password</span>
                    </div>
                    <svg width="16" height="16" fill="none" stroke="#8C827A" viewBox="0 0 24 24" stroke-width="2.2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>
            </div>
        </div>

        {{-- Verified & Trusted Customer Footer Banner --}}
        <div style="margin-top:18px;padding:14px 18px;border-radius:18px;background:linear-gradient(90deg,#F6F0E4 0%,#F2EADA 50%,#EAE0CD 100%);border:1px solid #E2D6C0;display:flex;align-items:center;justify-content:space-between;gap:12px;position:relative;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,0.03);">
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
             @click.away="showPasswordModal = false">

            <div class="flex items-center justify-between pb-2 border-b border-gray-100">
                <div>
                    <h3 class="text-base font-extrabold text-gray-900">Change Password</h3>
                    <p class="text-[10px] text-gray-400 font-medium">Enhanced 2-Step Gmail verification</p>
                </div>
                <button type="button" @click="showPasswordModal = false" class="text-gray-400 hover:text-black transition-colors p-1">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <div class="py-3 text-center space-y-3">
                <div class="w-14 h-14 rounded-2xl bg-amber-50 text-[#C0422A] mx-auto flex items-center justify-center font-bold text-2xl shadow-xs border border-amber-100">
                    🛡️
                </div>
                <p class="text-xs text-gray-600 leading-relaxed font-medium">
                    To keep your LumBarong account safe, password changes require entering a 6-digit verification code sent to your registered Gmail address.
                </p>
                <a href="{{ route('profile.change-password') }}" 
                   class="block w-full py-2.5 px-4 rounded-xl bg-[#C0422A] hover:bg-[#a33506] text-white text-xs font-bold uppercase tracking-wider shadow-md transition-all text-center">
                    Proceed to Change Password
                </a>
            </div>
        </div>
    </div>

    {{-- Saved Address Modal --}}
    <div x-show="showAddressModal"
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs"
         x-transition
         @click.self="if (!showDeleteConfirmModal && !addEditModalOpen) showAddressModal = false">

        <div style="background-color:#FDFBF7;border:1px solid #EAE2D2;border-radius:28px;box-shadow:0 25px 60px rgba(0,0,0,0.18);padding:24px;width:100%;max-width:520px;max-height:85vh;display:flex;flex-direction:column;gap:16px;position:relative;">

            {{-- Modal Header --}}
            <div style="display:flex;align-items:center;justify-content:space-between;padding-bottom:14px;border-bottom:1px solid #ECE3D2;flex-shrink:0;">
                <div style="display:flex;align-items:center;gap:12px;">
                    <div style="width:40px;height:40px;border-radius:12px;background-color:#FAF5EA;border:1px solid #E6D8BA;display:flex;align-items:center;justify-content:center;color:#B88728;flex-shrink:0;box-shadow:0 2px 5px rgba(0,0,0,0.03);">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 21s-7-4.35-7-10a7 7 0 1114 0c0 5.65-7 10-7 10z"/>
                            <circle cx="12" cy="11" r="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    <div>
                        <h3 style="font-family:ui-serif,Georgia,Cambria,serif;font-size:18px;font-weight:700;color:#1E1915;line-height:1.2;margin:0;">
                            Saved Addresses
                        </h3>
                        <p style="font-size:11.5px;color:#78716C;margin:2px 0 0 0;">
                            Manage your delivery destinations
                        </p>
                    </div>
                </div>
                <div style="display:flex;align-items:center;gap:8px;">
                    <button type="button" 
                            @click="openAddAddress()" 
                            style="background-color:#1E1915;color:#DFC97A;border:1px solid #DFC97A;padding:7px 14px;border-radius:12px;font-size:11px;font-weight:800;letter-spacing:0.06em;text-transform:uppercase;cursor:pointer;display:flex;align-items:center;gap:5px;box-shadow:0 2px 8px rgba(0,0,0,0.12);transition:all 0.2s;"
                            class="hover:bg-black hover:scale-[1.02] active:scale-[0.98]">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                        <span>Add Address</span>
                    </button>
                    <button type="button" 
                            @click="showAddressModal = false" 
                            style="width:32px;height:32px;border-radius:10px;background-color:#FAF5EA;border:1px solid #E6D8BA;color:#78716C;display:flex;align-items:center;justify-content:center;cursor:pointer;transition:all 0.2s;"
                            class="hover:bg-[#1E1915] hover:text-[#DFC97A] hover:border-[#1E1915]">
                        <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
            </div>

            {{-- Address List Body --}}
            <div class="flex-1 overflow-y-auto space-y-3 pr-1" style="max-height:calc(85vh - 120px);">
                <template x-if="loadingAddresses">
                    <div style="padding:48px 0;text-align:center;font-size:12px;color:#8C827A;">
                        <svg class="w-6 h-6 animate-spin text-[#996515] mx-auto mb-2" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        Loading saved addresses...
                    </div>
                </template>

                <template x-if="!loadingAddresses && addresses.length === 0">
                    <div style="padding:48px 0;text-align:center;">
                        <div style="width:48px;height:48px;border-radius:16px;background-color:#FAF5EA;border:1px solid #E6D8BA;display:flex;align-items:center;justify-content:center;color:#B88728;margin:0 auto 12px auto;">
                            <svg width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        </div>
                        <h4 style="font-size:14px;font-weight:700;color:#1E1915;margin:0;">No addresses saved yet</h4>
                        <p style="font-size:12px;color:#78716C;margin:4px 0 0 0;">Add your first shipping destination to proceed with orders.</p>
                    </div>
                </template>

                <template x-if="!loadingAddresses">
                    <div style="display:flex;flex-direction:column;gap:12px;">
                        <template x-for="addr in addresses" :key="addr.id">
                            <div style="background-color:#FFFFFF;border:1.5px solid #ECE3D2;border-radius:18px;padding:16px 18px;box-shadow:0 2px 8px rgba(0,0,0,0.02);transition:all 0.2s;"
                                 class="hover:border-[#C49520] hover:shadow-md">
                                <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;">
                                    <div style="display:flex;align-items:flex-start;gap:12px;min-width:0;flex:1;">
                                        <div style="width:36px;height:36px;border-radius:11px;background-color:#FAF5EA;border:1px solid #E6D8BA;display:flex;align-items:center;justify-content:center;color:#B88728;flex-shrink:0;margin-top:2px;">
                                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 21s-7-4.35-7-10a7 7 0 1114 0c0 5.65-7 10-7 10z"/>
                                                <circle cx="12" cy="11" r="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                                            </svg>
                                        </div>
                                        <div style="min-width:0;flex:1;">
                                            <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                                                <span style="font-size:14.5px;font-weight:700;color:#1E1915;" x-text="addr.recipientName"></span>
                                                <span style="font-size:11.5px;font-weight:600;color:#78716C;background-color:#FAF8F5;border:1px solid #EAE2D2;padding:2px 7px;border-radius:6px;" x-text="addr.phone"></span>
                                                <template x-if="addr.isDefault">
                                                    <span style="background:linear-gradient(135deg,#1E1915,#2C241E);color:#DFC97A;border:1px solid #C49520;font-size:9.5px;font-weight:900;letter-spacing:0.08em;text-transform:uppercase;padding:2px 8px;border-radius:6px;box-shadow:0 1px 4px rgba(0,0,0,0.1);">
                                                        DEFAULT
                                                    </span>
                                                </template>
                                            </div>
                                            <p style="font-size:12.5px;line-height:1.5;color:#59514A;margin:6px 0 0 0;font-weight:500;"
                                               x-text="[addr.houseNo, addr.street, addr.barangay, addr.city, addr.province, addr.postalCode].filter(Boolean).join(', ')">
                                            </p>
                                            <template x-if="!addr.isDefault">
                                                <div style="margin-top:10px;">
                                                    <button type="button" 
                                                            @click="setDefaultAddress(addr.id)" 
                                                            style="background-color:#FAF8F5;border:1px solid #D8CEBE;color:#78716C;padding:4px 10px;border-radius:8px;font-size:10.5px;font-weight:700;cursor:pointer;transition:all 0.2s;"
                                                            class="hover:bg-[#1E1915] hover:text-[#DFC97A] hover:border-[#1E1915]">
                                                        Set as Default
                                                    </button>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                    <div style="display:flex;align-items:center;gap:6px;flex-shrink:0;">
                                        <button type="button" 
                                                @click="openEditAddress(addr)" 
                                                style="background-color:#FAF5EA;border:1px solid #E6D8BA;color:#8C6212;padding:5px 10px;border-radius:8px;font-size:11px;font-weight:700;display:inline-flex;align-items:center;gap:4px;cursor:pointer;transition:all 0.2s;"
                                                class="hover:bg-[#1E1915] hover:text-[#DFC97A] hover:border-[#1E1915]">
                                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path stroke-linecap="round" stroke-linejoin="round" d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                            <span>Edit</span>
                                        </button>
                                        <button type="button" 
                                                @click="promptDeleteAddress(addr.id)" 
                                               style="background-color:#FFF5F5;border:1px solid #FED7D7;color:#E53E3E;padding:5px 9px;border-radius:8px;font-size:11px;font-weight:700;display:inline-flex;align-items:center;gap:3px;cursor:pointer;transition:all 0.2s;"
                                               class="hover:bg-red-600 hover:text-white hover:border-red-600">
                                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </template>
            </div>

            {{-- Inner Add / Edit Form Modal Popup --}}
            <div x-show="addEditModalOpen" class="fixed inset-0 z-60 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs" style="z-index: 60;" x-cloak>
                <div style="background-color:#FDFBF7;border:1px solid #EAE2D2;border-radius:28px;box-shadow:0 25px 60px rgba(0,0,0,0.22);padding:24px;width:100%;max-width:480px;max-height:85vh;overflow-y:auto;position:relative;" 
                     @click.away="addEditModalOpen = false">

                    {{-- Form Header --}}
                    <div style="display:flex;align-items:center;justify-content:space-between;padding-bottom:12px;border-bottom:1px solid #ECE3D2;margin-bottom:14px;">
                        <h4 style="font-family:ui-serif,Georgia,Cambria,serif;font-size:17px;font-weight:700;color:#1E1915;margin:0;" 
                            x-text="editAddressId ? 'Edit Address' : 'Add New Address'"></h4>
                        <button type="button" 
                                @click="addEditModalOpen = false" 
                                style="width:30px;height:30px;border-radius:8px;background-color:#FAF5EA;border:1px solid #E6D8BA;color:#78716C;display:flex;align-items:center;justify-content:center;cursor:pointer;transition:all 0.2s;"
                                class="hover:bg-[#1E1915] hover:text-[#DFC97A] hover:border-[#1E1915]">
                            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <div class="space-y-3.5 text-xs">
                        {{-- Real-Time Interactive Map Location Pinpointer --}}
                        <div class="space-y-2 pb-3 border-b border-[#ECE3D2]">
                            <div class="flex items-center justify-between gap-2">
                                <label style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:#996515;margin:0;">
                                    Pin Exact Delivery Location
                                </label>
                                <button type="button"
                                        @click="locateUserGps()"
                                        :disabled="isLocatingGps"
                                        style="background-color:#FAF5EA;border:1px solid #E6D8BA;color:#8C6212;padding:5px 10px;border-radius:10px;font-size:11px;font-weight:700;display:inline-flex;align-items:center;gap:6px;cursor:pointer;transition:all 0.2s;white-space:nowrap;"
                                        class="hover:bg-[#EAE2D2] disabled:opacity-50 shadow-xs">
                                    <template x-if="isLocatingGps">
                                        <svg class="w-3.5 h-3.5 animate-spin text-[#8C6212]" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                    </template>
                                    <template x-if="!isLocatingGps">
                                        <svg class="w-3.5 h-3.5 text-[#8C6212]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                    </template>
                                    <span x-text="isLocatingGps ? 'Locating GPS...' : 'Use Current Location'"></span>
                                </button>
                            </div>

                            {{-- Map Search Input --}}
                            <div style="position:relative;display:flex;align-items:center;width:100%;">
                                <div style="position:absolute;left:11px;display:flex;align-items:center;pointer-events:none;color:#8C827A;z-index:5;">
                                    <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                                </div>
                                <input type="text"
                                       x-model="mapSearchQuery"
                                       @keydown.enter.prevent="searchMapLocation()"
                                       placeholder="Search street, barangay, or landmark to drop pin..."
                                       style="width:100%;height:38px;padding-left:36px;padding-right:80px;background-color:#FFFFFF;border:1px solid #D8CEBE;border-radius:12px;font-size:12px;color:#1E1915;outline:none;box-shadow:inset 0 1px 2px rgba(0,0,0,0.03);transition:border-color 0.2s;"
                                       class="focus:border-[#996515]">
                                <button type="button"
                                        @click="searchMapLocation()"
                                        :disabled="pinSearching"
                                        style="position:absolute;right:4px;height:30px;padding:0 12px;background-color:#1E1915;color:#DFC97A;border:none;border-radius:8px;font-size:10px;font-weight:800;letter-spacing:0.08em;text-transform:uppercase;cursor:pointer;transition:all 0.2s;display:flex;align-items:center;justify-content:center;z-index:5;"
                                        class="hover:bg-black disabled:opacity-50">
                                    <span x-text="pinSearching ? '...' : 'Search'"></span>
                                </button>
                            </div>

                            {{-- Leaflet Map Container --}}
                            <div style="height:190px;border-radius:14px;overflow:hidden;border:1px solid #ECE3D2;position:relative;z-index:10;box-shadow:inset 0 1px 4px rgba(0,0,0,0.06);"
                                 x-ref="addressMapContainer"></div>

                            {{-- Detected Location Bar --}}
                            <div class="p-2.5 bg-[#FAF8F5] border border-[#ECE3D2] rounded-xl flex items-center justify-between gap-2 text-[10px]">
                                <div class="flex items-center gap-1.5 min-w-0">
                                    <span class="w-2 h-2 rounded-full bg-emerald-500 shrink-0 animate-pulse"></span>
                                    <span class="text-[#78716C] font-medium truncate" x-text="detectedLocationName || 'Drag pin or tap on map to lock coordinates'"></span>
                                </div>
                                <span class="shrink-0 px-2 py-0.5 bg-[#1E1915] text-[#DFC97A] text-[9px] font-black rounded-md uppercase tracking-wider"
                                      x-text="addressForm.latitude && addressForm.longitude ? 'Pin Locked' : 'Set Pin'"></span>
                            </div>
                        </div>

                        {{-- Full Name --}}
                        <div>
                            <label class="font-bold text-gray-700 mb-1 block">Full Name *</label>
                            <input x-model="addressForm.recipientName"
                                   @input="fieldErrors.recipientName = ''; addressForm.recipientName = addressForm.recipientName.replace(/[^a-zA-Z\u00C0-\u024F\s.'-]/g, '')"
                                   type="text" placeholder="Recipient's full name (letters only)"
                                   maxlength="30"
                                   :class="fieldErrors.recipientName ? 'border-red-400 bg-red-50' : 'border-[#D8CEBE]'"
                                   style="width:100%;height:38px;padding:0 12px;background-color:#FFFFFF;border-radius:12px;outline:none;font-size:12.5px;transition:border-color 0.2s;"
                                   class="border focus:border-[#996515]">
                            <p x-show="fieldErrors.recipientName" x-text="fieldErrors.recipientName" class="mt-1 text-[10px] text-red-500 font-semibold"></p>
                        </div>
                        {{-- Phone Number --}}
                        <div>
                            <label class="font-bold text-gray-700 mb-1 block">Phone Number *</label>
                            <input x-model="addressForm.phone"
                                   @input="fieldErrors.phone = ''; addressForm.phone = addressForm.phone.replace(/[^0-9+]/g, '')"
                                   type="text" placeholder="e.g. 09XXXXXXXXX"
                                   maxlength="11"
                                   :class="fieldErrors.phone ? 'border-red-400 bg-red-50' : 'border-[#D8CEBE]'"
                                   style="width:100%;height:38px;padding:0 12px;background-color:#FFFFFF;border-radius:12px;outline:none;font-size:12.5px;transition:border-color 0.2s;"
                                   class="border focus:border-[#996515]">
                            <p x-show="fieldErrors.phone" x-text="fieldErrors.phone" class="mt-1 text-[10px] text-red-500 font-semibold"></p>
                        </div>

                        {{-- Location Dropdown Selector --}}
                        <div class="relative">
                            <label class="font-bold text-gray-700 mb-1 block">Region, Province, City, Barangay *</label>
                            <div @click="toggleLocationDropdown(); fieldErrors.location = ''"
                                 :class="fieldErrors.location ? 'border-red-400 bg-red-50' : 'border-[#D8CEBE] bg-[#FFFFFF]'"
                                 style="width:100%;height:38px;padding:0 12px;border-radius:12px;display:flex;align-items:center;justify-content:space-between;cursor:pointer;transition:border-color 0.2s;"
                                 class="border">
                                <span class="truncate" :class="getLocationSummary() ? 'text-gray-900 font-semibold' : 'text-gray-400'" x-text="getLocationSummary() || 'Select Region, Province, City, Barangay'"></span>
                                <svg class="w-4 h-4 text-gray-400" :class="locationDropdownOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </div>
                            <p x-show="fieldErrors.location" x-text="fieldErrors.location" class="mt-1 text-[10px] text-red-500 font-semibold"></p>

                            <div x-show="locationDropdownOpen" @click.away="locationDropdownOpen = false"
                                 style="position:absolute;left:0;right:0;z-index:50;margin-top:4px;background-color:#FFFFFF;border:1px solid #ECE3D2;border-radius:14px;box-shadow:0 10px 30px rgba(0,0,0,0.12);overflow:hidden;display:flex;flex-direction:column;max-height:250px;" x-cloak>
                                <div class="flex border-b border-gray-100 bg-[#FAF8F5] text-[10px] font-bold text-gray-500">
                                    <button @click="activeTab = 'region'" type="button" :class="activeTab === 'region' ? 'text-[#996515] bg-white border-b-2 border-[#996515]' : ''" class="flex-1 py-2 text-center">Region</button>
                                    <button @click="if(selectedRegion && hasProvinces) activeTab = 'province'" type="button" :disabled="!selectedRegion || !hasProvinces" :class="activeTab === 'province' ? 'text-[#996515] bg-white border-b-2 border-[#996515]' : ''" class="flex-1 py-2 text-center disabled:opacity-40">Province</button>
                                    <button @click="if(selectedProvince || (selectedRegion && !hasProvinces)) activeTab = 'city'" type="button" :disabled="!selectedProvince && (hasProvinces || !selectedRegion)" :class="activeTab === 'city' ? 'text-[#996515] bg-white border-b-2 border-[#996515]' : ''" class="flex-1 py-2 text-center disabled:opacity-40">City</button>
                                    <button @click="if(selectedCity) activeTab = 'barangay'" type="button" :disabled="!selectedCity" :class="activeTab === 'barangay' ? 'text-[#996515] bg-white border-b-2 border-[#996515]' : ''" class="flex-1 py-2 text-center disabled:opacity-40">Barangay</button>
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
                                   :class="fieldErrors.houseNo ? 'border-red-400 bg-red-50' : 'border-[#D8CEBE]'"
                                   style="width:100%;height:38px;padding:0 12px;background-color:#FFFFFF;border-radius:12px;outline:none;font-size:12.5px;transition:border-color 0.2s;"
                                   class="border focus:border-[#996515]">
                            <p x-show="fieldErrors.houseNo" x-text="fieldErrors.houseNo" class="mt-1 text-[10px] text-red-500 font-semibold"></p>
                        </div>

                        {{-- Postal Code --}}
                        <div>
                            <label class="font-bold text-gray-700 mb-1 block">Postal Code</label>
                            <input x-model="addressForm.postalCode"
                                   @input="fieldErrors.postalCode = ''; addressForm.postalCode = addressForm.postalCode.replace(/[^0-9]/g, '')"
                                   type="text" placeholder="e.g. 1000"
                                   maxlength="4"
                                   :class="fieldErrors.postalCode ? 'border-red-400 bg-red-50' : 'border-[#D8CEBE]'"
                                   style="width:100%;height:38px;padding:0 12px;background-color:#FFFFFF;border-radius:12px;outline:none;font-size:12.5px;transition:border-color 0.2s;"
                                   class="border focus:border-[#996515]">
                            <p x-show="fieldErrors.postalCode" x-text="fieldErrors.postalCode" class="mt-1 text-[10px] text-red-500 font-semibold"></p>
                        </div>

                        <label class="flex items-center gap-2 cursor-pointer pt-1">
                            <input type="checkbox" x-model="addressForm.isDefault" class="accent-[#996515]">
                            <span class="text-xs font-semibold text-gray-700">Set as default shipping address</span>
                        </label>
                    </div>

                    <div style="display:flex;align-items:center;gap:12px;padding-top:16px;border-top:1px solid #ECE3D2;margin-top:6px;">
                        <button type="button" 
                                @click="addEditModalOpen = false" 
                                style="flex:1;height:42px;background-color:#FAF8F5;border:1.5px solid #D8CEBE;color:#59514A;border-radius:14px;font-size:12px;font-weight:700;cursor:pointer;transition:all 0.2s;display:flex;align-items:center;justify-content:center;gap:6px;"
                                class="hover:bg-[#EAE2D2] hover:text-[#1E1915]">
                            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                            <span>Cancel</span>
                        </button>
                        <button type="button" 
                                @click="saveAddress()" 
                                :disabled="savingAddress" 
                                style="flex:1.4;height:42px;background:linear-gradient(135deg,#1E1915 0%,#2D241E 50%,#1E1915 100%);color:#DFC97A;border:1.5px solid #C49520;border-radius:14px;font-size:12px;font-weight:800;letter-spacing:0.07em;text-transform:uppercase;cursor:pointer;box-shadow:0 4px 14px rgba(196,149,32,0.22);transition:all 0.2s;display:flex;align-items:center;justify-content:center;gap:8px;"
                                class="hover:bg-black hover:scale-[1.01] active:scale-[0.98] disabled:opacity-50">
                            <template x-if="savingAddress">
                                <svg class="w-4 h-4 animate-spin text-[#DFC97A]" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            </template>
                            <template x-if="!savingAddress">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            </template>
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

        <div style="background-color:#FDFBF7;border:1px solid #EAE2D2;border-radius:28px;padding:24px;box-shadow:0 25px 60px rgba(0,0,0,0.22);width:100%;max-width:340px;text-align:center;display:flex;flex-direction:column;gap:14px;position:relative;">

            <div style="width:48px;height:48px;border-radius:16px;background-color:#FFF5F5;color:#E53E3E;border:1px solid #FED7D7;display:flex;align-items:center;justify-content:center;margin:0 auto;box-shadow:0 2px 6px rgba(229,62,62,0.1);">
                <svg width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
            </div>

            <div>
                <h3 style="font-family:ui-serif,Georgia,Cambria,serif;font-size:17px;font-weight:700;color:#1E1915;margin:0;">Delete Address</h3>
                <p style="font-size:12px;color:#78716C;margin:4px 0 0 0;">Are you sure you want to remove this address from your registry?</p>
            </div>

            <div style="display:flex;align-items:center;gap:10px;padding-top:4px;">
                <button type="button"
                        @click="showDeleteConfirmModal = false"
                        style="flex:1;padding:9px 0;background-color:#FAF8F5;border:1px solid #D8CEBE;color:#78716C;border-radius:12px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;cursor:pointer;transition:all 0.2s;"
                        class="hover:bg-[#EAE2D2]">
                    Cancel
                </button>
                <button type="button"
                        @click="confirmDeleteAddress()"
                        style="flex:1;padding:9px 0;background-color:#E53E3E;color:#FFFFFF;border:1px solid #C53030;border-radius:12px;font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:0.06em;box-shadow:0 4px 12px rgba(229,62,62,0.25);cursor:pointer;transition:all 0.2s;"
                        class="hover:bg-[#C53030] active:scale-95">
                    Delete
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
        showPasswordModal: document.getElementById('profile-root')?.dataset?.showPassword === 'true',
        pendingDeleteAddressId: null,

        // Address management state
        addresses: [],
        loadingAddresses: true,
        addEditModalOpen: false,
        savingAddress: false,
        editAddressId: null,
        addressFormError: '',
        fieldErrors: { recipientName: '', phone: '', location: '', houseNo: '', postalCode: '' },
        addressForm: { recipientName:'', phone:'', houseNo:'', street:'', barangay:'', city:'', province:'', region:'', postalCode:'', latitude: 14.2952, longitude: 121.4647, isDefault: false },

        // Real-Time Map Location Picker state
        map: null,
        marker: null,
        mapSearchQuery: '',
        pinSearching: false,
        isLocatingGps: false,
        detectedLocationName: '',

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
            this.addressForm = { 
                recipientName:'', 
                phone:'', 
                houseNo:'', 
                street:'', 
                barangay:'', 
                city:'', 
                province:'', 
                region:'', 
                postalCode:'', 
                latitude: 14.2952, 
                longitude: 121.4647, 
                isDefault: false 
            };
            this.addressFormError = '';
            this.fieldErrors = { recipientName: '', phone: '', location: '', houseNo: '', postalCode: '' };
            this.selectedRegion = null;
            this.selectedProvince = null;
            this.selectedCity = null;
            this.selectedBarangay = null;
            this.activeTab = 'region';
            this.locationSearch = '';
            this.locationDropdownOpen = false;
            this.mapSearchQuery = '';
            this.detectedLocationName = '';
            this.addEditModalOpen = true;
            this.initAddressMap(14.2952, 121.4647);
        },

        openEditAddress(addr) {
            this.editAddressId = addr.id;
            const lat = parseFloat(addr.latitude) || 14.2952;
            const lng = parseFloat(addr.longitude) || 121.4647;
            this.addressForm = { 
                ...addr,
                latitude: lat,
                longitude: lng
            };
            this.addressFormError = '';
            this.fieldErrors = { recipientName: '', phone: '', location: '', houseNo: '', postalCode: '' };
            this.selectedRegion = addr.region ? { name: addr.region } : null;
            this.selectedProvince = addr.province ? { name: addr.province } : null;
            this.selectedCity = addr.city ? { name: addr.city } : null;
            this.selectedBarangay = addr.barangay ? { name: addr.barangay } : null;
            this.activeTab = 'region';
            this.locationSearch = '';
            this.locationDropdownOpen = false;
            this.mapSearchQuery = '';
            this.detectedLocationName = '';
            this.addEditModalOpen = true;
            this.initAddressMap(lat, lng);
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
            this.syncMapToSelectedLocation();
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
            this.syncMapToSelectedLocation();
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
        },

        // Real-Time Map Location Picker Methods
        initAddressMap(lat = 14.2952, lng = 121.4647) {
            this.$nextTick(() => {
                if (!this.$refs.addressMapContainer) return;

                if (this.map) {
                    this.map.setView([lat, lng], 15);
                    if (this.marker) {
                        this.marker.setLatLng([lat, lng]);
                    }
                    setTimeout(() => {
                        if (this.map) this.map.invalidateSize();
                    }, 300);
                    return;
                }

                if (typeof L === 'undefined') {
                    console.warn('Leaflet library is still loading...');
                    return;
                }

                this.map = L.map(this.$refs.addressMapContainer, {
                    attributionControl: false
                }).setView([lat, lng], 15);

                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19
                }).addTo(this.map);

                const customPinIcon = L.divIcon({
                    className: 'lumbarong-pin-icon',
                    html: `
                        <div style="position:relative;width:32px;height:32px;display:flex;align-items:center;justify-content:center;">
                            <div style="width:28px;height:28px;background:#1E1915;border:2.5px solid #DFC97A;border-radius:50% 50% 50% 0;transform:rotate(-45deg);display:flex;align-items:center;justify-content:center;box-shadow:0 4px 10px rgba(0,0,0,0.35);">
                                <span style="transform:rotate(45deg);color:#DFC97A;font-size:11px;font-weight:900;">✦</span>
                            </div>
                            <div style="position:absolute;bottom:-6px;width:10px;height:4px;background:rgba(0,0,0,0.25);border-radius:50%;filter:blur(1px);"></div>
                        </div>
                    `,
                    iconSize: [32, 32],
                    iconAnchor: [16, 32],
                    popupAnchor: [0, -32]
                });

                this.marker = L.marker([lat, lng], {
                    draggable: true,
                    icon: customPinIcon
                }).addTo(this.map);

                this.map.on('click', (e) => {
                    this.updatePinLocation(e.latlng.lat, e.latlng.lng);
                });

                this.marker.on('dragend', (e) => {
                    const pos = e.target.getLatLng();
                    this.updatePinLocation(pos.lat, pos.lng);
                });

                this.reverseGeocode(lat, lng);

                setTimeout(() => {
                    if (this.map) this.map.invalidateSize();
                }, 350);
            });
        },

        updatePinLocation(lat, lng, doReverseGeocode = true) {
            this.addressForm.latitude = lat;
            this.addressForm.longitude = lng;
            if (this.marker) {
                this.marker.setLatLng([lat, lng]);
            }
            if (this.map) {
                this.map.panTo([lat, lng]);
            }
            if (doReverseGeocode) {
                this.reverseGeocode(lat, lng);
            }
        },

        async locateUserGps() {
            if (!navigator.geolocation) {
                alert('Geolocation is not supported by your browser.');
                return;
            }
            this.isLocatingGps = true;
            navigator.geolocation.getCurrentPosition(
                (pos) => {
                    this.isLocatingGps = false;
                    const lat = pos.coords.latitude;
                    const lng = pos.coords.longitude;
                    this.updatePinLocation(lat, lng);
                    if (this.map) {
                        this.map.setView([lat, lng], 16);
                    }
                },
                (err) => {
                    this.isLocatingGps = false;
                    alert('Unable to retrieve your location. Please check browser GPS permissions.');
                },
                { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
            );
        },

        async searchMapLocation() {
            if (!this.mapSearchQuery.trim()) return;
            this.pinSearching = true;
            try {
                const query = encodeURIComponent(this.mapSearchQuery.trim() + ', Philippines');
                const res = await fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${query}&limit=1`);
                const data = await res.json();
                if (data && data.length > 0) {
                    const loc = data[0];
                    const lat = parseFloat(loc.lat);
                    const lng = parseFloat(loc.lon);
                    this.updatePinLocation(lat, lng);
                    if (this.map) {
                        this.map.setView([lat, lng], 16);
                    }
                } else {
                    alert('Location not found. Please try a different landmark or street.');
                }
            } catch(e) {
                console.error(e);
            }
            this.pinSearching = false;
        },

        async reverseGeocode(lat, lon) {
            try {
                const res = await fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lon}&addressdetails=1&accept-language=en-US`);
                const data = await res.json();
                if (data) {
                    this.detectedLocationName = data.display_name || '';
                    if (data.address) {
                        await this.autoFillFromGeocode(data.address);
                    }
                }
            } catch(e) {
                console.error(e);
            }
        },

        async autoFillFromGeocode(addr) {
            if (!addr) return;

            const rawRegion = addr.region || addr.state || '';
            const rawProvince = addr.province || addr.state_district || addr.county || '';
            const rawCity = addr.city || addr.town || addr.municipality || addr.city_district || '';
            const rawBarangay = addr.village || addr.suburb || addr.neighbourhood || addr.quarter || addr.residential || '';
            const rawStreet = [addr.house_number, addr.road || addr.pedestrian || addr.highway].filter(Boolean).join(' ');
            const rawPostal = addr.postcode || '';

            // Auto-fill Postal code
            if (rawPostal && /^\d{4}$/.test(rawPostal)) {
                this.addressForm.postalCode = rawPostal;
                this.fieldErrors.postalCode = '';
            }

            // Direct fallback fill first
            if (rawProvince) this.addressForm.province = rawProvince;
            if (rawCity) this.addressForm.city = rawCity;
            if (rawBarangay) this.addressForm.barangay = rawBarangay;
            if (rawRegion) this.addressForm.region = rawRegion;

            // Match with official PSGC data
            try {
                if (!this.regionsList || this.regionsList.length === 0) {
                    await this.loadRegions();
                }

                const normalize = (str) => (str || '').toLowerCase().replace(/[^a-z0-9]/g, '');

                const normRegion = normalize(rawRegion);
                const normProv = normalize(rawProvince);
                const normCity = normalize(rawCity);
                const normBgy = normalize(rawBarangay);

                // Match Region
                let matchedRegion = this.regionsList.find(r => {
                    const nr = normalize(r.name);
                    return (normRegion && (nr.includes(normRegion) || normRegion.includes(nr))) ||
                           (normProv && nr.includes(normProv));
                });

                // Special handling for NCR / Metro Manila
                if (!matchedRegion && (normRegion.includes('ncr') || normRegion.includes('metromanila') || normRegion.includes('nationalcapital') || normProv.includes('metromanila') || normCity.includes('manila') || normCity.includes('quezoncity'))) {
                    matchedRegion = this.regionsList.find(r => r.code === '130000000');
                }

                if (matchedRegion) {
                    this.selectedRegion = matchedRegion;
                    this.addressForm.region = matchedRegion.name;

                    if (matchedRegion.code === '130000000') {
                        this.hasProvinces = false;
                        this.selectedProvince = { code: '130000000', name: 'Metro Manila' };
                        this.addressForm.province = 'Metro Manila';
                        await this.loadNCRCities();
                    } else {
                        this.hasProvinces = true;
                        await this.loadProvinces(matchedRegion.code);

                        // Match Province
                        let matchedProv = this.provincesList.find(p => {
                            const np = normalize(p.name);
                            return normProv && (np.includes(normProv) || normProv.includes(np));
                        });
                        if (matchedProv) {
                            this.selectedProvince = matchedProv;
                            this.addressForm.province = matchedProv.name;
                            await this.loadCities(matchedProv.code);
                        }
                    }

                    // Match City
                    if (this.citiesList && this.citiesList.length > 0) {
                        let matchedCity = this.citiesList.find(c => {
                            const nc = normalize(c.name);
                            return normCity && (nc.includes(normCity) || normCity.includes(nc));
                        });
                        if (matchedCity) {
                            this.selectedCity = matchedCity;
                            this.addressForm.city = matchedCity.name;
                            await this.loadBarangays(matchedCity.code);

                            // Match Barangay
                            if (this.barangaysList && this.barangaysList.length > 0) {
                                let matchedBgy = this.barangaysList.find(b => {
                                    const nb = normalize(b.name);
                                    return normBgy && (nb.includes(normBgy) || normBgy.includes(nb));
                                });
                                if (matchedBgy) {
                                    this.selectedBarangay = matchedBgy;
                                    this.addressForm.barangay = matchedBgy.name;
                                }
                            }
                        }
                    }
                }
                this.fieldErrors.location = '';
            } catch(e) {
                console.error('PSGC autofill match error:', e);
            }
        },

        async syncMapToSelectedLocation() {
            const parts = [
                this.addressForm.barangay,
                this.addressForm.city,
                this.addressForm.province,
                'Philippines'
            ].filter(Boolean);
            if (parts.length <= 1) return;
            try {
                const query = encodeURIComponent(parts.join(', '));
                const res = await fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${query}&limit=1`);
                const data = await res.json();
                if (data && data.length > 0) {
                    const loc = data[0];
                    const lat = parseFloat(loc.lat);
                    const lng = parseFloat(loc.lon);
                    this.updatePinLocation(lat, lng, false);
                    if (this.map) {
                        this.map.setView([lat, lng], 15);
                    }
                }
            } catch(e) {}
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
