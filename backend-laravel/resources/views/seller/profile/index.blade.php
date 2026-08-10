@extends('layouts.seller')

@section('content')
    <div class="max-w-4xl mx-auto space-y-6">
        {{-- Profile Hero Card --}}
        <div class="relative bg-white rounded-3xl p-6 sm:p-8 border border-gray-100 shadow-sm flex flex-col sm:flex-row items-center sm:items-start gap-6">
            {{-- Avatar: Clickable to change photo --}}
            <div class="relative w-28 h-28 shrink-0 group cursor-pointer" onclick="document.getElementById('profilePhotoInput').click()" title="Click to change profile photo">
                <div class="w-full h-full rounded-full bg-linear-to-tr from-[#3D2B1F] to-[#C0420A] flex items-center justify-center text-white font-black text-4xl overflow-hidden shadow-lg border-4 border-white relative">
                    @if($user->profilePhoto)
                        <img src="{{ str_starts_with($user->profilePhoto, 'http') || str_starts_with($user->profilePhoto, '/') ? $user->profilePhoto : asset('storage/' . $user->profilePhoto) }}" class="w-full h-full object-cover" onerror="this.style.display='none'">
                    @else
                        {{ strtoupper(substr($user->name, 0, 1)) }}
                    @endif
                    <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-all flex flex-col items-center justify-center text-white text-[10px] font-bold">
                        <svg class="w-6 h-6 mb-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        <span>Change</span>
                    </div>
                </div>
                <div class="absolute bottom-0 right-0 w-8 h-8 rounded-full bg-[#C0420A] text-white flex items-center justify-center shadow-md border-2 border-white">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                </div>
            </div>

            <div class="text-center sm:text-left grow">
                <h1 class="text-2xl font-black text-black uppercase tracking-tight">{{ $user->name }}</h1>
                <p class="text-gray-400 text-sm font-medium">{{ $user->email }}</p>
                <div class="mt-4 flex flex-wrap gap-2 justify-center sm:justify-start">
                    <span class="px-3 py-1 bg-gray-100 rounded-full text-[10px] font-bold uppercase tracking-widest text-gray-600">Artisan Account</span>
                    @if($user->isVerified)
                        <span class="px-3 py-1 bg-green-50 text-green-600 rounded-full text-[10px] font-bold uppercase tracking-widest border border-green-100">Verified</span>
                    @endif
                </div>
            </div>
        </div>

        {{-- Stats Row --}}
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
            <div class="bg-white p-4 rounded-2xl border border-gray-100 text-center shadow-sm">
                <div class="text-lg font-black text-[#C0420A]">0</div>
                <div class="text-[9px] font-bold text-gray-400 uppercase tracking-widest">Active Listings</div>
            </div>
            <div class="bg-white p-4 rounded-2xl border border-gray-100 text-center shadow-sm">
                <div class="text-lg font-black text-black">0</div>
                <div class="text-[9px] font-bold text-gray-400 uppercase tracking-widest">Total Orders</div>
            </div>
            <div class="bg-white p-4 rounded-2xl border border-gray-100 text-center shadow-sm">
                <div class="text-lg font-black text-black">₱0</div>
                <div class="text-[9px] font-bold text-gray-400 uppercase tracking-widest">Balance</div>
            </div>
            <div class="bg-white p-4 rounded-2xl border border-gray-100 text-center shadow-sm">
                <div class="text-lg font-black text-black">—</div>
                <div class="text-[9px] font-bold text-gray-400 uppercase tracking-widest">Rating</div>
            </div>
        </div>

        {{-- Main Settings Form --}}
        <form action="{{ route('seller.profile.update') }}" method="POST" enctype="multipart/form-data" id="profileForm" class="bg-white rounded-3xl p-6 sm:p-8 border border-gray-100 shadow-sm space-y-6">
            @csrf
            @method('PUT')
            
            {{-- Hidden file input triggered by avatar click --}}
            <input type="file" id="profilePhotoInput" name="profilePhoto" class="hidden" onchange="document.getElementById('profileForm').submit()">
            
            <div>
                <h2 class="text-sm font-black uppercase tracking-widest text-black mb-6 border-b border-gray-100 pb-4">Account Settings</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-1">
                        <label class="text-[10px] font-bold uppercase text-gray-400">Shop Name</label>
                        <input type="text" name="name" value="{{ old('name', $user->name) }}" class="w-full px-4 py-3 bg-gray-50 border-0 rounded-xl text-sm font-bold">
                    </div>
                    <div class="space-y-1">
                        <label class="text-[10px] font-bold uppercase text-gray-400">Mobile Number</label>
                        <input type="text" name="mobileNumber" value="{{ old('mobileNumber', $user->mobileNumber) }}" class="w-full px-4 py-3 bg-gray-50 border-0 rounded-xl text-sm font-bold">
                    </div>
                    <div class="col-span-full space-y-1">
                        <label class="text-[10px] font-bold uppercase text-gray-400">Shop Description</label>
                        <textarea name="shopDescription" rows="3" class="w-full px-4 py-3 bg-gray-50 border-0 rounded-xl text-sm font-medium">{{ old('shopDescription', $user->shopDescription) }}</textarea>
                    </div>
                </div>
            </div>

            <div class="mt-8 flex justify-center">
                <button type="submit" class="px-10 py-3.5 bg-[#C0420A] text-white rounded-xl text-xs font-black uppercase tracking-widest hover:bg-black transition-all shadow-md">Edit Profile</button>
            </div>
        </form>

        {{-- Quick Links --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <button id="payment-methods" onclick="document.getElementById('payment-modal').classList.remove('hidden')" class="flex items-center justify-between p-4 bg-white rounded-2xl border border-gray-100 hover:border-[#C0420A] transition-all text-left">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-blue-50 border border-blue-100 flex items-center justify-center">
                        <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                    </div>
                    <span class="text-xs font-bold text-black uppercase">Payment Methods</span>
                </div>
                <svg class="w-4 h-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </button>

            <button onclick="document.getElementById('legal-modal').classList.remove('hidden')" class="flex items-center justify-between p-4 bg-white rounded-2xl border border-gray-100 hover:border-[#C0420A] transition-all text-left">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-green-50 border border-green-100 flex items-center justify-center">
                        <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    </div>
                    <span class="text-xs font-bold text-black uppercase">Legal Documents</span>
                </div>
                <svg class="w-4 h-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </button>

            <a href="{{ route('seller.commission') }}" class="flex items-center justify-between p-4 bg-white rounded-2xl border border-gray-100 hover:border-[#C0420A] transition-all">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-amber-50 border border-amber-100 flex items-center justify-center">
                        <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <span class="text-xs font-bold text-black uppercase">Pay Commission</span>
                </div>
                <svg class="w-4 h-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </a>

            <a href="{{ route('seller.customers') }}" class="flex items-center justify-between p-4 bg-white rounded-2xl border border-gray-100 hover:border-[#C0420A] transition-all">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-[#C0420A]/10 border border-[#C0420A]/20 flex items-center justify-center">
                        <svg class="w-4 h-4 text-[#C0420A]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                    </div>
                    <span class="text-xs font-bold text-black uppercase">Customer List</span>
                </div>
                <svg class="w-4 h-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </a>
        </div>

        {{-- Mobile Logout Button --}}
        <div class="lg:hidden pt-4 border-t border-gray-100">
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" class="w-full py-3.5 bg-red-50 text-red-600 rounded-2xl text-xs font-black uppercase tracking-widest border border-red-100 hover:bg-red-600 hover:text-white transition-all shadow-sm">
                    Logout
                </button>
            </form>
        </div>
    </div>

    {{-- Payment Methods Modal with In-Modal Editing --}}
    <div id="payment-modal" x-data="{ editing: false }" class="hidden fixed inset-0 bg-black/50 backdrop-blur-sm z-50 items-end sm:items-center justify-center">
        <div class="w-full sm:max-w-lg bg-white rounded-t-3xl sm:rounded-3xl shadow-2xl overflow-hidden flex flex-col max-h-[90vh]">
            <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100 shrink-0">
                <div>
                    <h2 class="text-sm font-black uppercase tracking-widest text-black">Payment Methods</h2>
                    <p class="text-[10px] text-gray-400 mt-0.5" x-text="editing ? 'Edit your payment numbers and upload QR code files' : 'Your payment accounts for receiving payouts & sales'"></p>
                </div>
                <button onclick="document.getElementById('payment-modal').classList.add('hidden')" class="w-8 h-8 rounded-xl bg-gray-100 flex items-center justify-center text-gray-500 hover:bg-gray-200 transition-all">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            {{-- Modal Body --}}
            <div class="overflow-y-auto flex-1 p-5">
                {{-- View Mode --}}
                <div x-show="!editing" class="space-y-4">
                    {{-- GCash --}}
                    <div class="p-4 border border-blue-100 rounded-2xl bg-blue-50/30 space-y-2">
                        <div class="flex items-center gap-2 mb-1">
                            <span class="relative flex h-2 w-2"><span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-blue-400 opacity-75"></span><span class="relative inline-flex rounded-full h-2 w-2 bg-blue-500"></span></span>
                            <span class="text-[10px] font-black uppercase tracking-widest text-blue-700">GCash Account</span>
                        </div>
                        <div class="text-sm font-black text-black">{{ $user->gcashNumber ?: 'No GCash number added' }}</div>
                        @if(!empty($user->gcashQrCode))
                            <div class="pt-2">
                                <span class="text-[9px] font-bold text-gray-400 uppercase tracking-widest block mb-1">Uploaded QR Code:</span>
                                <img src="{{ asset('storage/' . $user->gcashQrCode) }}" class="w-28 h-28 object-contain rounded-xl border border-blue-100 bg-white" onerror="this.parentElement.removeChild(this)">
                            </div>
                        @else
                            <div class="text-[10px] text-gray-400 italic">No QR code uploaded</div>
                        @endif
                    </div>
                    {{-- Maya --}}
                    <div class="p-4 border border-green-100 rounded-2xl bg-green-50/30 space-y-2">
                        <div class="flex items-center gap-2 mb-1">
                            <span class="relative flex h-2 w-2"><span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span><span class="relative inline-flex rounded-full h-2 w-2 bg-green-500"></span></span>
                            <span class="text-[10px] font-black uppercase tracking-widest text-green-700">Maya Account</span>
                        </div>
                        <div class="text-sm font-black text-black">{{ $user->mayaNumber ?: 'No Maya number added' }}</div>
                        @if(!empty($user->mayaQrCode))
                            <div class="pt-2">
                                <span class="text-[9px] font-bold text-gray-400 uppercase tracking-widest block mb-1">Uploaded QR Code:</span>
                                <img src="{{ asset('storage/' . $user->mayaQrCode) }}" class="w-28 h-28 object-contain rounded-xl border border-green-100 bg-white" onerror="this.parentElement.removeChild(this)">
                            </div>
                        @else
                            <div class="text-[10px] text-gray-400 italic">No QR code uploaded</div>
                        @endif
                    </div>
                </div>

                {{-- Edit Mode Form --}}
                <div x-show="editing" style="display: none;">
                    <form action="{{ route('seller.profile.update') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                        @csrf
                        @method('PUT')
                        {{-- Preserved required profile values --}}
                        <input type="hidden" name="name" value="{{ $user->name }}">
                        <input type="hidden" name="mobileNumber" value="{{ $user->mobileNumber }}">
                        <input type="hidden" name="shopDescription" value="{{ $user->shopDescription }}">

                        {{-- GCash Edit --}}
                        <div class="p-4 border border-blue-100 rounded-2xl bg-blue-50/30 space-y-3">
                            <div class="text-[10px] font-black uppercase tracking-widest text-blue-700">GCash Configuration</div>
                            <div class="space-y-1">
                                <label class="text-[9px] font-bold uppercase text-gray-500">GCash Mobile Number</label>
                                <input type="text" name="gcashNumber" value="{{ old('gcashNumber', $user->gcashNumber) }}" placeholder="e.g. 0917 123 4567" class="w-full px-4 py-2.5 bg-white border border-gray-200 rounded-xl text-xs font-bold outline-none focus:border-blue-500">
                            </div>
                            <div class="space-y-1">
                                <label class="text-[9px] font-bold uppercase text-gray-500">Choose GCash QR Code Image</label>
                                <input type="file" name="gcashQrCode" accept="image/*" class="w-full text-xs text-gray-500 file:mr-3 file:py-2 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-blue-600 file:text-white hover:file:bg-blue-700">
                            </div>
                        </div>

                        {{-- Maya Edit --}}
                        <div class="p-4 border border-green-100 rounded-2xl bg-green-50/30 space-y-3">
                            <div class="text-[10px] font-black uppercase tracking-widest text-green-700">Maya Configuration</div>
                            <div class="space-y-1">
                                <label class="text-[9px] font-bold uppercase text-gray-500">Maya Mobile Number</label>
                                <input type="text" name="mayaNumber" value="{{ old('mayaNumber', $user->mayaNumber) }}" placeholder="e.g. 0917 123 4567" class="w-full px-4 py-2.5 bg-white border border-gray-200 rounded-xl text-xs font-bold outline-none focus:border-green-500">
                            </div>
                            <div class="space-y-1">
                                <label class="text-[9px] font-bold uppercase text-gray-500">Choose Maya QR Code Image</label>
                                <input type="file" name="mayaQrCode" accept="image/*" class="w-full text-xs text-gray-500 file:mr-3 file:py-2 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-green-600 file:text-white hover:file:bg-green-700">
                            </div>
                        </div>

                        <div class="pt-2 flex items-center gap-3">
                            <button type="submit" class="flex-1 py-3 bg-[#C0420A] text-white rounded-2xl text-xs font-black uppercase tracking-widest hover:bg-black transition-all shadow-md">
                                Save Payment Info
                            </button>
                            <button type="button" @click="editing = false" class="px-5 py-3 bg-gray-100 text-gray-700 rounded-2xl text-xs font-black uppercase tracking-widest hover:bg-gray-200 transition-all">
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- View Mode Footer --}}
            <div x-show="!editing" class="px-5 py-4 border-t border-gray-100 shrink-0 flex items-center gap-3">
                <button @click="editing = true" class="flex-1 py-3 bg-[#C0420A] text-white rounded-2xl text-xs font-black uppercase tracking-widest hover:bg-black transition-all shadow-md">
                    ✏ Edit Payment Info & QR Codes
                </button>
                <button onclick="document.getElementById('payment-modal').classList.add('hidden')" class="px-5 py-3 bg-gray-100 text-gray-700 rounded-2xl text-xs font-black uppercase tracking-widest hover:bg-gray-200 transition-all">
                    Close
                </button>
            </div>
        </div>
    </div>

    {{-- Legal Documents Modal with In-Modal Upload/Edit --}}
    <div id="legal-modal" x-data="{ editing: false }" class="hidden fixed inset-0 bg-black/50 backdrop-blur-sm z-50 items-end sm:items-center justify-center">
        <div class="w-full sm:max-w-lg bg-white rounded-t-3xl sm:rounded-3xl shadow-2xl overflow-hidden flex flex-col max-h-[90vh]">
            <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100 shrink-0">
                <div>
                    <h2 class="text-sm font-black uppercase tracking-widest text-black">Legal Documents</h2>
                    <p class="text-[10px] text-gray-400 mt-0.5">Verification status: <span class="{{ $user->isVerified ? 'text-green-600' : 'text-amber-500' }} font-bold uppercase">{{ $user->isVerified ? 'Verified ✓' : 'Pending Verification' }}</span></p>
                </div>
                <button onclick="document.getElementById('legal-modal').classList.add('hidden')" class="w-8 h-8 rounded-xl bg-gray-100 flex items-center justify-center text-gray-500 hover:bg-gray-200 transition-all">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            {{-- Modal Body --}}
            <div class="overflow-y-auto flex-1 p-5">
                {{-- View Mode --}}
                <div x-show="!editing" class="divide-y divide-gray-50">
                    @foreach([
                        ['label' => 'Business Permit', 'field' => 'businessPermit'],
                        ['label' => 'BIR Document', 'field' => 'birDocument'],
                        ['label' => 'Residency Certificate', 'field' => 'residencyCertificate'],
                    ] as $doc)
                    <div class="flex items-center gap-3 py-4">
                        <div class="w-9 h-9 rounded-xl {{ $user->{$doc['field']} ? 'bg-green-50 border border-green-100' : 'bg-gray-50 border border-gray-200' }} flex items-center justify-center shrink-0">
                            <svg class="w-4 h-4 {{ $user->{$doc['field']} ? 'text-green-500' : 'text-gray-300' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="text-xs font-bold text-black">{{ $doc['label'] }}</div>
                            @if($user->{$doc['field']})
                                <div class="text-[9px] text-green-600 font-bold uppercase tracking-widest">✓ Uploaded</div>
                            @else
                                <div class="text-[9px] text-gray-400 font-bold uppercase tracking-widest">Not uploaded</div>
                            @endif
                        </div>
                        @if($user->{$doc['field']})
                            <a href="{{ asset('storage/' . $user->{$doc['field']}) }}" target="_blank" class="text-[10px] font-black text-[#C0420A] hover:underline uppercase tracking-widest shrink-0">View ↗</a>
                        @endif
                    </div>
                    @endforeach
                </div>

                {{-- Edit Mode Form --}}
                <div x-show="editing" style="display: none;">
                    <form action="{{ route('seller.profile.update') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                        @csrf
                        @method('PUT')
                        {{-- Preserved required profile values --}}
                        <input type="hidden" name="name" value="{{ $user->name }}">
                        <input type="hidden" name="mobileNumber" value="{{ $user->mobileNumber }}">
                        <input type="hidden" name="shopDescription" value="{{ $user->shopDescription }}">

                        <div class="p-4 bg-gray-50 border border-gray-100 rounded-2xl space-y-2">
                            <label class="text-[10px] font-bold uppercase text-gray-600 block">Choose Business Permit File</label>
                            <input type="file" name="businessPermit" accept=".pdf,image/*" class="w-full text-xs text-gray-500 file:mr-2 file:py-1.5 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-black file:text-white">
                            @if($user->businessPermit)
                                <div class="text-[9px] text-green-600 font-bold uppercase">Current: Uploaded</div>
                            @endif
                        </div>

                        <div class="p-4 bg-gray-50 border border-gray-100 rounded-2xl space-y-2">
                            <label class="text-[10px] font-bold uppercase text-gray-600 block">Choose BIR Document File</label>
                            <input type="file" name="birDocument" accept=".pdf,image/*" class="w-full text-xs text-gray-500 file:mr-2 file:py-1.5 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-black file:text-white">
                            @if($user->birDocument)
                                <div class="text-[9px] text-green-600 font-bold uppercase">Current: Uploaded</div>
                            @endif
                        </div>

                        <div class="p-4 bg-gray-50 border border-gray-100 rounded-2xl space-y-2">
                            <label class="text-[10px] font-bold uppercase text-gray-600 block">Choose Residency Certificate File</label>
                            <input type="file" name="residencyCertificate" accept=".pdf,image/*" class="w-full text-xs text-gray-500 file:mr-2 file:py-1.5 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-black file:text-white">
                            @if($user->residencyCertificate)
                                <div class="text-[9px] text-green-600 font-bold uppercase">Current: Uploaded</div>
                            @endif
                        </div>

                        <div class="pt-2 flex items-center gap-3">
                            <button type="submit" class="flex-1 py-3 bg-[#C0420A] text-white rounded-2xl text-xs font-black uppercase tracking-widest hover:bg-black transition-all shadow-md">
                                Save Legal Documents
                            </button>
                            <button type="button" @click="editing = false" class="px-5 py-3 bg-gray-100 text-gray-700 rounded-2xl text-xs font-black uppercase tracking-widest hover:bg-gray-200 transition-all">
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- View Mode Footer --}}
            <div x-show="!editing" class="px-5 py-4 border-t border-gray-100 shrink-0 flex items-center gap-3">
                <button @click="editing = true" class="flex-1 py-3 bg-[#C0420A] text-white rounded-2xl text-xs font-black uppercase tracking-widest hover:bg-black transition-all shadow-md">
                    ✏ Upload / Edit Legal Documents
                </button>
                <button onclick="document.getElementById('legal-modal').classList.add('hidden')" class="px-5 py-3 bg-gray-100 text-gray-700 rounded-2xl text-xs font-black uppercase tracking-widest hover:bg-gray-200 transition-all">
                    Close
                </button>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('open_payment') === '1' || window.location.hash === '#payment-methods') {
            const modal = document.getElementById('payment-modal');
            if (modal) {
                modal.classList.remove('hidden');
            }
        }
    });
    </script>
@endsection