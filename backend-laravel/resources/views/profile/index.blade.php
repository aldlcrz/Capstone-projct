@extends('layouts.app')

@section('content')
<div class="min-h-[calc(100vh-80px)] bg-[#F2F7F2] py-6 px-4 sm:px-6" x-data="{ showEditModal: false }">
    <div class="max-w-md mx-auto space-y-6">

        {{-- Top Header --}}
        <div class="flex items-center justify-between pt-2">
            <a href="/" class="w-10 h-10 bg-white rounded-2xl flex items-center justify-center text-gray-700 shadow-xs border border-gray-100 hover:bg-gray-50 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <h1 class="text-lg font-extrabold text-gray-900 tracking-tight">Profile</h1>
            <div class="w-10"></div>
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
            <div class="bg-white rounded-3xl pt-16 pb-6 px-6 shadow-xs border border-gray-100/80 text-center relative">
                {{-- Edit Button (Top Right of Card) --}}
                <button type="button"
                        @click="showEditModal = true"
                        class="absolute top-4 right-4 w-9 h-9 bg-gray-50 hover:bg-gray-100 rounded-xl flex items-center justify-center border border-gray-200/80 text-gray-700 shadow-2xs transition-all cursor-pointer group">
                    <svg class="w-4 h-4 text-gray-600 group-hover:text-black transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                </button>

                <h2 class="text-xl font-extrabold text-gray-900 tracking-tight">{{ $user->name }}</h2>
                @if($user->username && $user->username !== $user->name)
                    <p class="text-xs text-gray-400 font-medium mt-0.5">{{ '@' . $user->username }}</p>
                @endif
            </div>
        </div>

        {{-- Account Settings Section --}}
        <div>
            <h3 class="text-sm font-bold text-gray-800 mb-3 px-1">Account setting</h3>

            <div class="space-y-2.5">
                {{-- Email --}}
                <div class="bg-white rounded-2xl p-4 shadow-2xs border border-gray-100 flex items-center justify-between">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="w-9 h-9 rounded-xl bg-[#F0F5F0] text-emerald-800 flex items-center justify-center shrink-0">
                            <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <span class="font-bold text-sm text-gray-900">Email</span>
                    </div>
                    <span class="text-xs font-semibold text-gray-500 truncate max-w-[180px] sm:max-w-xs">{{ $user->email }}</span>
                </div>

                {{-- Saved Address --}}
                <a href="{{ route('profile.addresses') }}" class="bg-white rounded-2xl p-4 shadow-2xs border border-gray-100 flex items-center justify-between hover:bg-gray-50/80 transition-colors group cursor-pointer">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-[#F0F5F0] text-emerald-800 flex items-center justify-center shrink-0">
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
                </a>

                {{-- Orders --}}
                <a href="{{ route('orders') }}" class="bg-white rounded-2xl p-4 shadow-2xs border border-gray-100 flex items-center justify-between hover:bg-gray-50/80 transition-colors group cursor-pointer">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-[#F0F5F0] text-emerald-800 flex items-center justify-center shrink-0">
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

                {{-- Change Password --}}
                <a href="{{ route('profile.change-password') }}" class="bg-white rounded-2xl p-4 shadow-2xs border border-gray-100 flex items-center justify-between hover:bg-gray-50/80 transition-colors group cursor-pointer">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-[#F0F5F0] text-emerald-800 flex items-center justify-center shrink-0">
                            <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                        </div>
                        <span class="font-bold text-sm text-gray-900">Change Password</span>
                    </div>
                    <svg class="w-4 h-4 text-gray-400 group-hover:text-black group-hover:translate-x-0.5 transition-all" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>
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
</div>

<script>
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
