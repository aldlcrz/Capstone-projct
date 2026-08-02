@extends('layouts.seller')

@section('content')
    <div class="max-w-225 mx-auto space-y-8">
        <div>
            <div class="text-[10px] font-bold text-[#C0420A] uppercase tracking-[0.2em] mb-1">Artisan Account</div>
            <h1 class="font-serif text-3xl font-bold text-black uppercase">Seller <span
                    class="text-[#C0420A] italic lowercase">profile</span></h1>
        </div>

        @if($errors->any())
            <div x-data="{ show: true, init() { setTimeout(() => this.show = false, 7000) } }" x-show="show"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-2 sm:translate-y-0 sm:translate-x-2"
                x-transition:enter-end="opacity-100 translate-y-0 sm:translate-x-0"
                x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="fixed top-6 right-6 z-9999 w-full max-w-sm bg-white/95 backdrop-blur-md rounded-2xl shadow-2xl border border-gray-100 p-4 flex items-start gap-3.5"
                style="display: none;" x-cloak>
                <div
                    class="w-8 h-8 rounded-full bg-red-50 flex items-center justify-center text-red-600 shrink-0 shadow-sm border border-red-100">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </div>
                <div class="grow pt-0.5">
                    <h4 class="text-xs font-black text-black uppercase tracking-wider">Please fix the following</h4>
                    <ul class="text-xs text-gray-500 font-medium mt-1 leading-relaxed space-y-0.5 list-disc list-inside">
                        @foreach($errors->all() as $e)
                            <li>{{ $e }}</li>
                        @endforeach
                    </ul>
                </div>
                <button @click="show = false" class="text-gray-300 hover:text-gray-500 transition-colors shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        @endif

        <form action="{{ route('seller.profile.update') }}" method="POST" enctype="multipart/form-data"
            class="bg-white rounded-3xl border border-gray-100 shadow-sm overflow-hidden">
            @csrf
            @method('PUT')
            {{-- Profile Header --}}
            <div class="p-8 flex items-center gap-6 transition-all duration-500 bg-linear-to-r from-[#3D2B1F] to-[#C0420A]">
                <div class="relative group w-20 h-20 shrink-0" x-data="{ 
                         avatarPreview: '{{ $user->profilePhoto ? (str_starts_with($user->profilePhoto, 'http') || str_starts_with($user->profilePhoto, '/') ? $user->profilePhoto : asset('storage/' . $user->profilePhoto)) : '' }}',
                         hovering: false 
                     }" @mouseenter="hovering = true" @mouseleave="hovering = false">
                    <input type="file" name="profilePhoto"
                        class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10" @change="
                            const file = $event.target.files[0];
                            if (file) {
                                const reader = new FileReader();
                                reader.onload = (e) => { avatarPreview = e.target.result; };
                                reader.readAsDataURL(file);
                            }
                        ">
                    <div class="w-full h-full rounded-2xl bg-white/20 border-2 border-white/30 flex items-center justify-center text-white font-black text-2xl overflow-hidden relative group-hover:border-white transition-all">
                        <template x-if="avatarPreview">
                            <img :src="avatarPreview" class="w-full h-full object-cover">
                        </template>
                        <template x-if="!avatarPreview">
                            <span>{{ strtoupper(substr($user->name, 0, 1)) }}</span>
                        </template>
                        <div class="absolute inset-0 bg-black/40 flex flex-col items-center justify-center transition-all duration-200 z-20 pointer-events-none"
                            :class="hovering ? 'opacity-100' : 'opacity-0'">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z">
                                </path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                            <span class="text-[8px] text-white font-bold uppercase tracking-widest mt-1">Upload</span>
                        </div>
                    </div>
                </div>
                <div>
                    <div class="text-white font-bold text-lg flex items-center gap-2">
                        {{ $user->name }}
                    </div>
                    <div class="text-white/60 text-[10px] uppercase tracking-widest font-bold">{{ $user->email }}</div>
                    <div class="mt-2 flex items-center gap-2 flex-wrap">
                        @if($user->isVerified)
                            <span
                                class="px-2 py-0.5 bg-green-400/20 text-green-300 border border-green-300/30 rounded text-[9px] font-black uppercase tracking-widest">✓
                                Verified Artisan</span>
                        @else
                            <span
                                class="px-2 py-0.5 bg-amber-400/20 text-amber-300 border border-amber-300/30 rounded text-[9px] font-black uppercase tracking-widest">⏳
                                Pending Verification</span>
                        @endif
                    </div>
                </div>
            </div>

            <div class="p-8 space-y-8">
                {{-- Basic Info --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-2">
                        <label class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Shop Name</label>
                        <input type="text" name="name" required value="{{ old('name', $user->name) }}"
                            class="w-full px-5 py-3.5 bg-gray-50 border border-gray-100 rounded-xl outline-none focus:border-[#C0420A] transition-all text-sm font-medium">
                    </div>
                    <div class="space-y-2">
                        <label class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Telephone
                            Number</label>
                        <input type="text" name="mobileNumber" value="{{ old('mobileNumber', $user->mobileNumber) }}"
                            placeholder="+63 2 8123 4567 or (02) 8123-4567"
                            class="w-full px-5 py-3.5 bg-gray-50 border border-gray-100 rounded-xl outline-none focus:border-[#C0420A] transition-all text-sm font-medium">
                    </div>
                    <div class="space-y-2">
                        <label class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Email Address</label>
                        <div
                            class="px-5 py-3.5 bg-gray-50 rounded-xl border border-gray-100 text-sm text-gray-400 font-medium">
                            {{ $user->email }} <span
                                class="ml-2 text-[9px] text-green-500 font-black uppercase">Verified</span>
                        </div>
                    </div>
                    <div class="space-y-2">
                        <label class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Established On /
                            Created On</label>
                        <div
                            class="px-5 py-3.5 bg-gray-50 rounded-xl border border-gray-100 text-sm text-gray-400 font-medium">
                            {{ $user->createdAt ? $user->createdAt->format('F d, Y') : 'N/A' }}
                        </div>
                    </div>
                </div>



                <div class="space-y-2">
                    <label class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Shop Description</label>
                    <textarea name="shopDescription" rows="3"
                        placeholder="Describe your artisan workshop and heritage crafts..."
                        class="w-full px-5 py-3.5 bg-gray-50 border border-gray-100 rounded-xl outline-none focus:border-[#C0420A] transition-all text-sm font-medium resize-none">{{ old('shopDescription', $user->shopDescription ?? '') }}</textarea>
                </div>

                {{-- Payment Configurations --}}
                <div class="border-t border-gray-100 pt-8 space-y-6">
                    <div>
                        <h3 class="text-sm font-bold text-black uppercase tracking-widest flex items-center gap-2">
                            <svg class="w-4 h-4 text-[#C0420A]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z">
                                </path>
                            </svg>
                            Payment Configuration
                        </h3>
                        <p class="text-[10px] text-gray-400 mt-1">Configure payment methods you want to accept from
                            customers.</p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        {{-- GCash Configuration --}}
                        <div class="p-6 border border-blue-100/60 rounded-3xl space-y-6 shadow-xs"
                            style="background: linear-gradient(to bottom, rgba(239, 246, 255, 0.2), #fff);">
                            <div class="flex items-center gap-2.5 mb-2">
                                <span class="relative flex h-2 w-2">
                                    <span
                                        class="animate-ping absolute inline-flex h-full w-full rounded-full bg-blue-400 opacity-75"></span>
                                    <span class="relative inline-flex rounded-full h-2 w-2 bg-blue-500"></span>
                                </span>
                                <span class="text-[11px] font-black uppercase tracking-widest text-[#0060AA]">GCash
                                    Method</span>
                            </div>

                            <div class="space-y-2">
                                <label class="text-[9px] font-black uppercase tracking-widest text-gray-400">GCash
                                    Number</label>
                                <input type="text" name="gcashNumber" value="{{ old('gcashNumber', $user->gcashNumber) }}"
                                    placeholder="e.g. 0917 123 4567"
                                    class="w-full px-5 py-3.5 bg-white border border-gray-200 rounded-xl outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-100 transition-all text-xs font-bold text-gray-800">
                            </div>

                            <div class="space-y-2">
                                <label class="text-[9px] font-black uppercase tracking-widest text-gray-400">GCash QR
                                    Code</label>
                                <div class="relative group"
                                    x-data="{ qrPreview: '{{ $user->gcashQrCode ? asset('storage/' . $user->gcashQrCode) : '' }}' }">
                                    <input type="file" name="gcashQrCode"
                                        class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10" @change="
                                            const file = $event.target.files[0];
                                            if (file) {
                                                const reader = new FileReader();
                                                reader.onload = (e) => { qrPreview = e.target.result; };
                                                reader.readAsDataURL(file);
                                            }
                                        ">
                                    <div
                                        class="border-2 border-dashed border-gray-200 bg-white rounded-2xl p-6 flex flex-col items-center justify-center text-center hover:border-blue-500 hover:bg-blue-50/5 transition-all min-h-35">
                                        <template x-if="qrPreview">
                                            <div class="relative w-28 h-28">
                                                <img :src="qrPreview" class="w-full h-full object-contain rounded-lg">
                                                <div
                                                    class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 flex items-center justify-center rounded-lg transition-opacity">
                                                    <span
                                                        class="text-[8px] text-white font-bold uppercase tracking-widest">Change
                                                        QR</span>
                                                </div>
                                            </div>
                                        </template>
                                        <template x-if="!qrPreview">
                                            <div class="space-y-2">
                                                <svg class="w-8 h-8 text-gray-300 mx-auto" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12">
                                                    </path>
                                                </svg>
                                                <span
                                                    class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block">Upload
                                                    QR Code</span>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Maya Configuration --}}
                        <div class="p-6 border border-green-100/60 rounded-3xl space-y-6 shadow-xs"
                            style="background: linear-gradient(to bottom, rgba(240, 253, 244, 0.2), #fff);">
                            <div class="flex items-center gap-2.5 mb-2">
                                <span class="relative flex h-2 w-2">
                                    <span
                                        class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                                    <span class="relative inline-flex rounded-full h-2 w-2 bg-green-500"></span>
                                </span>
                                <span class="text-[11px] font-black uppercase tracking-widest text-[#00B050]">Maya
                                    Method</span>
                            </div>

                            <div class="space-y-2">
                                <label class="text-[9px] font-black uppercase tracking-widest text-gray-400">Maya
                                    Number</label>
                                <input type="text" name="mayaNumber" value="{{ old('mayaNumber', $user->mayaNumber) }}"
                                    placeholder="e.g. 0917 123 4567"
                                    class="w-full px-5 py-3.5 bg-white border border-gray-200 rounded-xl outline-none focus:border-green-500 focus:ring-1 focus:ring-green-100 transition-all text-xs font-bold text-gray-800">
                            </div>

                            <div class="space-y-2">
                                <label class="text-[9px] font-black uppercase tracking-widest text-gray-400">Maya QR
                                    Code</label>
                                <div class="relative group"
                                    x-data="{ qrPreview: '{{ $user->mayaQrCode ? asset('storage/' . $user->mayaQrCode) : '' }}' }">
                                    <input type="file" name="mayaQrCode"
                                        class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10" @change="
                                            const file = $event.target.files[0];
                                            if (file) {
                                                const reader = new FileReader();
                                                reader.onload = (e) => { qrPreview = e.target.result; };
                                                reader.readAsDataURL(file);
                                            }
                                        ">
                                    <div
                                        class="border-2 border-dashed border-gray-200 bg-white rounded-2xl p-6 flex flex-col items-center justify-center text-center hover:border-green-500 hover:bg-green-50/5 transition-all min-h-35">
                                        <template x-if="qrPreview">
                                            <div class="relative w-28 h-28">
                                                <img :src="qrPreview" class="w-full h-full object-contain rounded-lg">
                                                <div
                                                    class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 flex items-center justify-center rounded-lg transition-opacity">
                                                    <span
                                                        class="text-[8px] text-white font-bold uppercase tracking-widest">Change
                                                        QR</span>
                                                </div>
                                            </div>
                                        </template>
                                        <template x-if="!qrPreview">
                                            <div class="space-y-2">
                                                <svg class="w-8 h-8 text-gray-300 mx-auto" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12">
                                                    </path>
                                                </svg>
                                                <span
                                                    class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block">Upload
                                                    QR Code</span>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-between pt-4 border-t border-gray-50">
                    <p class="text-[10px] text-gray-400">Member since {{ $user->createdAt->format('F Y') }}</p>
                    <button type="submit"
                        class="px-10 py-4 bg-black text-white rounded-full font-bold uppercase tracking-[0.2em] text-[10px] shadow-xl hover:bg-[#C0420A] transition-all">
                        Save Profile
                    </button>
                </div>
            </div>
        </form>
    </div>
@endsection