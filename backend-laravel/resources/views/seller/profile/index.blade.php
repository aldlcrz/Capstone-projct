@extends('layouts.seller')

@section('content')
    @php
        $getImgUrl = function($path) {
            if (empty($path)) return null;
            if (str_starts_with($path, 'http')) return $path;
            $clean = ltrim($path, '/');
            if (str_starts_with($clean, 'uploads/')) return asset($clean);
            return asset('storage/' . $clean);
        };
    @endphp
    <div class="max-w-4xl mx-auto space-y-4 sm:space-y-6">
        {{-- Profile Hero Card --}}
        <div class="relative bg-white rounded-3xl p-6 sm:p-8 border border-gray-100 shadow-sm flex flex-col sm:flex-row items-center sm:items-start gap-6">
            {{-- Avatar: Clickable to change photo --}}
            <div class="relative w-28 h-28 shrink-0 group cursor-pointer" onclick="document.getElementById('profilePhotoInput').click()" title="Click to change profile photo">
                <div class="w-full h-full rounded-full bg-linear-to-tr from-[#3D2B1F] to-[#C0420A] flex items-center justify-center text-white font-black text-4xl overflow-hidden shadow-lg border-4 border-white relative">
                    @if($user->profile_photo_url)
                        <img src="{{ $user->profile_photo_url }}" class="w-full h-full object-cover" onerror="this.style.display='none'">
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
        @php
            $sellerListingCount = \App\Models\Product::where('sellerId', $user->id)->count();
            $sellerOrderCount = \App\Models\Order::where('sellerId', $user->id)->count();
            $sellerTotalEarnings = \App\Models\Order::where('sellerId', $user->id)
                ->whereIn('status', ['Completed', 'Delivered', 'delivered', 'completed'])
                ->sum('totalAmount');
            $sellerAvgRating = \App\Models\Review::whereHas('product', fn($q) => $q->where('sellerId', $user->id))->avg('rating');
        @endphp
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
            <div class="bg-white p-4 rounded-2xl border border-gray-100 text-center shadow-sm">
                <div class="text-lg font-black text-[#C0420A]">{{ $sellerListingCount }}</div>
                <div class="text-[9px] font-bold text-gray-400 uppercase tracking-widest">Active Listings</div>
            </div>
            <div class="bg-white p-4 rounded-2xl border border-gray-100 text-center shadow-sm">
                <div class="text-lg font-black text-black">{{ $sellerOrderCount }}</div>
                <div class="text-[9px] font-bold text-gray-400 uppercase tracking-widest">Total Orders</div>
            </div>
            <div class="bg-white p-4 rounded-2xl border border-gray-100 text-center shadow-sm">
                <div class="text-lg font-black text-black">₱{{ number_format($sellerTotalEarnings, 0) }}</div>
                <div class="text-[9px] font-bold text-gray-400 uppercase tracking-widest">Completed Sales</div>
            </div>
            <div class="bg-white p-4 rounded-2xl border border-gray-100 text-center shadow-sm">
                <div class="text-lg font-black text-black">{{ $sellerAvgRating ? number_format($sellerAvgRating, 1) : '—' }}</div>
                <div class="text-[9px] font-bold text-gray-400 uppercase tracking-widest">Rating</div>
            </div>
        </div>

        @if(session('success'))
            <div class="p-4 bg-green-50 border border-green-200 rounded-2xl text-green-700 text-xs font-bold flex items-center gap-2 shadow-xs">
                <svg class="w-4 h-4 text-green-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if(session('error') || $errors->any())
            <div class="p-4 bg-red-50 border border-red-200 rounded-2xl text-red-700 text-xs font-bold flex items-center gap-2 shadow-xs">
                <svg class="w-4 h-4 text-red-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span>{{ session('error') ?? $errors->first() }}</span>
            </div>
        @endif

        {{-- Main Settings Form --}}
        <form action="{{ route('seller.profile.update') }}" 
              method="POST" 
              enctype="multipart/form-data" 
              id="profileForm" 
              x-data="{
                  isEditing: false,
                  initialName: @js(old('name', $user->name ?? '')),
                  initialMobile: @js(old('mobileNumber', $user->mobileNumber ?? '')),
                  initialDescription: @js(old('shopDescription', $user->shopDescription ?? '')),
                  name: @js(old('name', $user->name ?? '')),
                  mobileNumber: @js(old('mobileNumber', $user->mobileNumber ?? '')),
                  shopDescription: @js(old('shopDescription', $user->shopDescription ?? '')),
                  hasChanges() {
                      return this.name !== this.initialName || 
                             this.mobileNumber !== this.initialMobile || 
                             this.shopDescription !== this.initialDescription;
                  },
                  cancelEdit() {
                      this.name = this.initialName;
                      this.mobileNumber = this.initialMobile;
                      this.shopDescription = this.initialDescription;
                      this.isEditing = false;
                  }
              }"
              class="bg-white rounded-3xl p-6 sm:p-8 border border-gray-100 shadow-sm space-y-6">
            @csrf
            @method('PUT')
            
            {{-- Hidden file input triggered by avatar click --}}
            <input type="file" id="profilePhotoInput" name="profilePhoto" accept="image/*" class="hidden" onchange="document.getElementById('profileForm').submit()">
            
            <div>
                <div class="flex items-center justify-between border-b border-gray-100 pb-4 mb-6">
                    <div>
                        <h2 class="text-sm font-black uppercase tracking-widest text-black">Account Settings</h2>
                        <p class="text-[10px] text-gray-400 mt-0.5" x-text="isEditing ? 'Make your adjustments and save your profile changes' : 'Your basic artisan shop details and contact info'"></p>
                    </div>
                    <span x-show="isEditing" x-transition class="text-[9px] font-black uppercase tracking-widest px-2.5 py-1 bg-amber-50 text-amber-600 rounded-full border border-amber-200">
                        Editing Mode
                    </span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-1">
                        <label class="text-[10px] font-bold uppercase text-gray-400">Shop Name</label>
                        <input type="text" 
                               name="name" 
                               x-ref="shopNameInput"
                               x-model="name"
                               :readonly="!isEditing"
                               :class="isEditing ? 'bg-white border-gray-200 ring-2 ring-[#C0420A]/10 text-black shadow-xs' : 'bg-gray-50 border-transparent text-gray-700 cursor-default'"
                               class="w-full px-4 py-3 border rounded-xl text-sm font-bold transition-all">
                    </div>
                    <div class="space-y-1">
                        <label class="text-[10px] font-bold uppercase text-gray-400">Mobile Number</label>
                        <input type="text" 
                               name="mobileNumber" 
                               x-model="mobileNumber"
                               :readonly="!isEditing"
                               :class="isEditing ? 'bg-white border-gray-200 ring-2 ring-[#C0420A]/10 text-black shadow-xs' : 'bg-gray-50 border-transparent text-gray-700 cursor-default'"
                               class="w-full px-4 py-3 border rounded-xl text-sm font-bold transition-all">
                    </div>
                    <div class="col-span-full space-y-1">
                        <label class="text-[10px] font-bold uppercase text-gray-400">Shop Description</label>
                        <textarea name="shopDescription" 
                                  rows="3" 
                                  x-model="shopDescription"
                                  placeholder="Tell customers about your Lumban artisan workshop and history..."
                                  :readonly="!isEditing"
                                  :class="isEditing ? 'bg-white border-gray-200 ring-2 ring-[#C0420A]/10 text-black shadow-xs' : 'bg-gray-50 border-transparent text-gray-700 cursor-default'"
                                  class="w-full px-4 py-3 border rounded-xl text-sm font-medium transition-all"></textarea>
                    </div>
                </div>
            </div>

            <div class="mt-4 sm:mt-5 flex flex-wrap items-center justify-center gap-3">
                {{-- View Mode: Edit Profile Button --}}
                <button type="button" 
                        x-show="!isEditing" 
                        @click="isEditing = true; $nextTick(() => { if ($refs.shopNameInput) $refs.shopNameInput.focus(); })" 
                        class="w-full sm:w-auto px-8 py-3 bg-gray-900 text-white rounded-xl text-xs font-black uppercase tracking-widest hover:bg-[#C0420A] transition-all shadow-md flex items-center justify-center gap-2 cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                    <span>Edit Profile</span>
                </button>

                {{-- Edit Mode: Cancel Button --}}
                <button type="button" 
                        x-show="isEditing" 
                        x-cloak
                        @click="cancelEdit()" 
                        class="w-full sm:w-auto px-6 py-3 bg-gray-100 text-gray-700 rounded-xl text-xs font-black uppercase tracking-widest hover:bg-gray-200 transition-all cursor-pointer">
                    Cancel
                </button>

                {{-- Edit Mode: Save Changes Button (Dynamic State) --}}
                <button type="submit" 
                        x-show="isEditing" 
                        x-cloak
                        :disabled="!hasChanges()"
                        :class="hasChanges() ? 'bg-[#C0420A] text-white hover:bg-black cursor-pointer shadow-md' : 'bg-gray-200 text-gray-400 cursor-not-allowed opacity-75'"
                        class="w-full sm:w-auto px-8 py-3 rounded-xl text-xs font-black uppercase tracking-widest transition-all flex items-center justify-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    <span>Save Changes</span>
                </button>
            </div>
        </form>

        {{-- Quick Links --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 sm:gap-3">
            {{-- Shop Policies Card --}}
            <a href="{{ route('seller.policies.index') }}" class="flex items-center justify-between px-4 py-3 sm:py-3.5 bg-white rounded-2xl border border-gray-100 hover:border-[#C0420A] transition-all">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-xl bg-amber-50 border border-amber-100 flex items-center justify-center">
                        <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    </div>
                    <div>
                        <span class="text-xs font-bold text-black uppercase block">Shop Policies</span>
                        <span class="text-[9px] text-gray-400 font-medium">Cancellation & refund terms</span>
                    </div>
                </div>
                <svg class="w-4 h-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </a>

            <a href="{{ route('seller.products.index') }}" class="flex items-center justify-between px-4 py-3 sm:py-3.5 bg-white rounded-2xl border border-gray-100 hover:border-[#C0420A] transition-all">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-xl bg-orange-50 border border-orange-100 flex items-center justify-center">
                        <svg class="w-4 h-4 text-[#C0420A]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                    </div>
                    <div>
                        <span class="text-xs font-bold text-black uppercase block">My Products</span>
                        <span class="text-[9px] text-gray-400 font-medium">Manage & edit catalogue listings</span>
                    </div>
                </div>
                <svg class="w-4 h-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </a>

            <button id="payment-methods" onclick="document.getElementById('payment-modal').style.display='flex'" class="flex items-center justify-between px-4 py-3 sm:py-3.5 bg-white rounded-2xl border border-gray-100 hover:border-[#C0420A] transition-all text-left cursor-pointer">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-xl bg-blue-50 border border-blue-100 flex items-center justify-center">
                        <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                    </div>
                    <span class="text-xs font-bold text-black uppercase">Payment Methods</span>
                </div>
                <svg class="w-4 h-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </button>

            <button onclick="document.getElementById('legal-modal').style.display='flex'" class="flex items-center justify-between px-4 py-3 sm:py-3.5 bg-white rounded-2xl border border-gray-100 hover:border-[#C0420A] transition-all text-left cursor-pointer">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-xl bg-green-50 border border-green-100 flex items-center justify-center">
                        <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    </div>
                    <span class="text-xs font-bold text-black uppercase">Legal Documents</span>
                </div>
                <svg class="w-4 h-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </button>

            <a href="{{ route('seller.commission') }}" class="flex items-center justify-between px-4 py-3 sm:py-3.5 bg-white rounded-2xl border border-gray-100 hover:border-[#C0420A] transition-all">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-xl bg-amber-50 border border-amber-100 flex items-center justify-center">
                        <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <span class="text-xs font-bold text-black uppercase">Pay Commission</span>
                </div>
                <svg class="w-4 h-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </a>

            <a href="{{ route('seller.customers') }}" class="flex items-center justify-between px-4 py-3 sm:py-3.5 bg-white rounded-2xl border border-gray-100 hover:border-[#C0420A] transition-all">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-xl bg-[#C0420A]/10 border border-[#C0420A]/20 flex items-center justify-center">
                        <svg class="w-4 h-4 text-[#C0420A]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                    </div>
                    <span class="text-xs font-bold text-black uppercase">Customer List</span>
                </div>
                <svg class="w-4 h-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </a>
        </div>

        {{-- Mobile Logout Button --}}
        <div class="lg:hidden pt-2.5 border-t border-gray-100 mt-2.5">
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" class="w-full py-3 bg-red-50 text-red-600 rounded-2xl text-xs font-black uppercase tracking-widest border border-red-100 hover:bg-red-600 hover:text-white transition-all shadow-xs cursor-pointer">
                    Logout
                </button>
            </form>
        </div>
    </div>

    {{-- Payment Methods Modal with In-Modal Editing --}}
    <div id="payment-modal" x-data="{ editing: false }" style="display: none;" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-end sm:items-center justify-center">
        <div class="w-full sm:max-w-lg bg-white rounded-t-3xl sm:rounded-3xl shadow-2xl overflow-hidden flex flex-col max-h-[90vh]">
            <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100 shrink-0">
                <div>
                    <h2 class="text-sm font-black uppercase tracking-widest text-black">Payment Methods</h2>
                    <p class="text-[10px] text-gray-400 mt-0.5" x-text="editing ? 'Edit your payment numbers and upload QR code files' : 'Your payment accounts for receiving payouts & sales'"></p>
                </div>
                <button onclick="document.getElementById('payment-modal').style.display='none'" class="w-8 h-8 rounded-xl bg-gray-100 flex items-center justify-center text-gray-500 hover:bg-gray-200 transition-all">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            {{-- Modal Body --}}
            <div class="overflow-y-auto flex-1 p-5 space-y-4">
                {{-- View Mode --}}
                <div x-show="!editing" class="space-y-3">

                    {{-- GCash Card --}}
                    <div class="rounded-2xl border border-blue-100 overflow-hidden">
                        {{-- Card Header --}}
                        <div class="flex items-center justify-between px-4 py-3 bg-linear-to-r from-blue-600 to-blue-500">
                            <div class="flex items-center gap-2.5">
                                <div class="w-7 h-7 rounded-lg bg-white/20 flex items-center justify-center">
                                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                                </div>
                                <span class="text-[11px] font-black uppercase tracking-widest text-white">GCash</span>
                            </div>
                            @if($user->gcashNumber)
                                <span class="text-[9px] font-bold bg-white/25 text-white px-2.5 py-0.5 rounded-full uppercase tracking-widest">Active</span>
                            @else
                                <span class="text-[9px] font-bold bg-black/20 text-white/70 px-2.5 py-0.5 rounded-full uppercase tracking-widest">Not Set</span>
                            @endif
                        </div>
                        {{-- Card Body --}}
                        <div class="p-4 bg-white flex items-start gap-4">
                            {{-- QR Code: click to view full size --}}
                            <div class="shrink-0">
                                @if(!empty($user->gcashQrCode) && $getImgUrl($user->gcashQrCode))
                                    <img src="{{ $getImgUrl($user->gcashQrCode) }}"
                                         class="w-20 h-20 object-contain rounded-xl border-2 border-blue-100 bg-blue-50/40 shadow-sm cursor-zoom-in"
                                         title="Click to view full size"
                                         onclick="document.getElementById('qr-lightbox-img').src=this.src; document.getElementById('qr-lightbox').style.display='flex'">
                                @else
                                    <div class="w-20 h-20 rounded-xl border-2 border-dashed border-blue-100 bg-blue-50/40 flex flex-col items-center justify-center gap-1">
                                        <svg class="w-6 h-6 text-blue-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
                                        <span class="text-[8px] text-blue-300 font-bold uppercase">No QR</span>
                                    </div>
                                @endif
                            </div>
                            {{-- Info --}}
                            <div class="flex-1 min-w-0">
                                <div class="text-[9px] font-bold text-gray-400 uppercase tracking-widest mb-1">Mobile Number</div>
                                @if($user->gcashNumber)
                                    <div class="text-base font-black text-gray-900 tracking-wider select-all">{{ $user->gcashNumber }}</div>
                                    <div class="text-[9px] text-blue-500 font-bold uppercase tracking-widest mt-1 flex items-center gap-1">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                        Ready to receive payments
                                    </div>
                                @else
                                    <div class="text-sm font-bold text-gray-300 italic">Not configured</div>
                                    <div class="text-[9px] text-gray-400 font-bold mt-1">Click Edit below to add your number</div>
                                @endif
                                @if(!empty($user->gcashQrCode) && $getImgUrl($user->gcashQrCode))
                                    <div class="mt-2 flex items-center gap-1">
                                        <svg class="w-3 h-3 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                        <span class="text-[9px] font-bold text-blue-500 uppercase tracking-widest">QR Code uploaded</span>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- Maya Card --}}
                    <div class="rounded-2xl border border-green-100 overflow-hidden">
                        {{-- Card Header --}}
                        <div class="flex items-center justify-between px-4 py-3 bg-linear-to-r from-green-600 to-green-500">
                            <div class="flex items-center gap-2.5">
                                <div class="w-7 h-7 rounded-lg bg-white/20 flex items-center justify-center">
                                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                                </div>
                                <span class="text-[11px] font-black uppercase tracking-widest text-white">Maya</span>
                            </div>
                            @if($user->mayaNumber)
                                <span class="text-[9px] font-bold bg-white/25 text-white px-2.5 py-0.5 rounded-full uppercase tracking-widest">Active</span>
                            @else
                                <span class="text-[9px] font-bold bg-black/20 text-white/70 px-2.5 py-0.5 rounded-full uppercase tracking-widest">Not Set</span>
                            @endif
                        </div>
                        {{-- Card Body --}}
                        <div class="p-4 bg-white flex items-start gap-4">
                            {{-- QR Code: click to view full size --}}
                            <div class="shrink-0">
                                @if(!empty($user->mayaQrCode) && $getImgUrl($user->mayaQrCode))
                                    <img src="{{ $getImgUrl($user->mayaQrCode) }}"
                                         class="w-20 h-20 object-contain rounded-xl border-2 border-green-100 bg-green-50/40 shadow-sm cursor-zoom-in"
                                         title="Click to view full size"
                                         onclick="document.getElementById('qr-lightbox-img').src=this.src; document.getElementById('qr-lightbox').style.display='flex'">
                                @else
                                    <div class="w-20 h-20 rounded-xl border-2 border-dashed border-green-100 bg-green-50/40 flex flex-col items-center justify-center gap-1">
                                        <svg class="w-6 h-6 text-green-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
                                        <span class="text-[8px] text-green-300 font-bold uppercase">No QR</span>
                                    </div>
                                @endif
                            </div>
                            {{-- Info --}}
                            <div class="flex-1 min-w-0">
                                <div class="text-[9px] font-bold text-gray-400 uppercase tracking-widest mb-1">Mobile Number</div>
                                @if($user->mayaNumber)
                                    <div class="text-base font-black text-gray-900 tracking-wider select-all">{{ $user->mayaNumber }}</div>
                                    <div class="text-[9px] text-green-600 font-bold uppercase tracking-widest mt-1 flex items-center gap-1">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                        Ready to receive payments
                                    </div>
                                @else
                                    <div class="text-sm font-bold text-gray-300 italic">Not configured</div>
                                    <div class="text-[9px] text-gray-400 font-bold mt-1">Click Edit below to add your number</div>
                                @endif
                                @if(!empty($user->mayaQrCode) && $getImgUrl($user->mayaQrCode))
                                    <div class="mt-2 flex items-center gap-1">
                                        <svg class="w-3 h-3 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                        <span class="text-[9px] font-bold text-green-600 uppercase tracking-widest">QR Code uploaded</span>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                </div>

                {{-- Edit Mode Form --}}
                <div x-show="editing" style="display: none;">
                    {{-- Requirement Reminder Banner --}}
                    <div class="p-3 mb-3 bg-amber-50 border border-amber-200 rounded-2xl text-amber-800 text-[10px] font-bold flex items-start gap-2 leading-relaxed">
                        <svg class="w-4 h-4 text-amber-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        <span><strong>Requirement:</strong> Both a <strong>Mobile Number</strong> and a <strong>QR Code Image</strong> are strictly required for GCash and Maya. Providing only one will not work.</span>
                    </div>

                    <form action="{{ route('seller.profile.update') }}" method="POST" enctype="multipart/form-data" class="space-y-4" onsubmit="return validatePaymentModalForm(event, this)">
                        @csrf
                        @method('PUT')
                        {{-- Preserved required profile values --}}
                        <input type="hidden" name="name" value="{{ $user->name }}">
                        <input type="hidden" name="mobileNumber" value="{{ $user->mobileNumber }}">
                        <input type="hidden" name="shopDescription" value="{{ $user->shopDescription }}">

                        {{-- GCash Edit --}}
                        <div class="p-4 border border-blue-100 rounded-2xl bg-blue-50/30 space-y-3">
                            <div class="flex items-center justify-between">
                                <div class="text-[10px] font-black uppercase tracking-widest text-blue-700">GCash Configuration</div>
                                <span class="text-[8px] font-bold uppercase text-blue-500 bg-white/70 px-2 py-0.5 rounded-md border border-blue-100">Number & QR Required</span>
                            </div>
                            <div class="space-y-1">
                                <label class="text-[9px] font-bold uppercase text-gray-500">GCash Mobile Number</label>
                                <input type="text" id="modalGcashNumber" name="gcashNumber" value="{{ old('gcashNumber', $user->gcashNumber) }}" placeholder="e.g. 0917 123 4567" class="w-full px-4 py-2.5 bg-white border border-gray-200 rounded-xl text-xs font-bold outline-none focus:border-blue-500">
                            </div>
                            <div class="space-y-2">
                                <label class="text-[9px] font-bold uppercase text-gray-500">GCash QR Code</label>
                                @if(!empty($user->gcashQrCode) && $getImgUrl($user->gcashQrCode))
                                    <div class="flex items-center gap-3">
                                        <img src="{{ $getImgUrl($user->gcashQrCode) }}"
                                             class="w-16 h-16 object-contain rounded-xl border-2 border-blue-100 bg-white shadow-sm cursor-zoom-in"
                                             title="Click to view"
                                             onclick="document.getElementById('qr-lightbox-img').src=this.src; document.getElementById('qr-lightbox').style.display='flex'">
                                        <div class="text-[9px] text-gray-500 font-bold leading-relaxed">Current QR uploaded.<br><span class="text-blue-600">Choose a new file below to replace it.</span></div>
                                    </div>
                                @endif
                                <input type="file" id="modalGcashQr" name="gcashQrCode" accept="image/*" class="w-full text-xs text-gray-500 file:mr-3 file:py-2 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-blue-600 file:text-white hover:file:bg-blue-700">
                            </div>
                        </div>

                        {{-- Maya Edit --}}
                        <div class="p-4 border border-green-100 rounded-2xl bg-green-50/30 space-y-3">
                            <div class="flex items-center justify-between">
                                <div class="text-[10px] font-black uppercase tracking-widest text-green-700">Maya Configuration</div>
                                <span class="text-[8px] font-bold uppercase text-green-600 bg-white/70 px-2 py-0.5 rounded-md border border-green-100">Number & QR Required</span>
                            </div>
                            <div class="space-y-1">
                                <label class="text-[9px] font-bold uppercase text-gray-500">Maya Mobile Number</label>
                                <input type="text" id="modalMayaNumber" name="mayaNumber" value="{{ old('mayaNumber', $user->mayaNumber) }}" placeholder="e.g. 0917 123 4567" class="w-full px-4 py-2.5 bg-white border border-gray-200 rounded-xl text-xs font-bold outline-none focus:border-green-500">
                            </div>
                            <div class="space-y-2">
                                <label class="text-[9px] font-bold uppercase text-gray-500">Maya QR Code</label>
                                @if(!empty($user->mayaQrCode) && $getImgUrl($user->mayaQrCode))
                                    <div class="flex items-center gap-3">
                                        <img src="{{ $getImgUrl($user->mayaQrCode) }}"
                                             class="w-16 h-16 object-contain rounded-xl border-2 border-green-100 bg-white shadow-sm cursor-zoom-in"
                                             title="Click to view"
                                             onclick="document.getElementById('qr-lightbox-img').src=this.src; document.getElementById('qr-lightbox').style.display='flex'">
                                        <div class="text-[9px] text-gray-500 font-bold leading-relaxed">Current QR uploaded.<br><span class="text-green-600">Choose a new file below to replace it.</span></div>
                                    </div>
                                @endif
                                <input type="file" id="modalMayaQr" name="mayaQrCode" accept="image/*" class="w-full text-xs text-gray-500 file:mr-3 file:py-2 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-green-600 file:text-white hover:file:bg-green-700">
                            </div>
                        </div>

                        <div class="pt-2 flex items-center gap-3">
                            <button type="submit" class="flex-1 py-3 bg-[#C0420A] text-white rounded-2xl text-xs font-black uppercase tracking-widest hover:bg-black transition-all shadow-md cursor-pointer">
                                Save Payment Info
                            </button>
                            <button type="button" @click="editing = false" class="px-5 py-3 bg-gray-100 text-gray-700 rounded-2xl text-xs font-black uppercase tracking-widest hover:bg-gray-200 transition-all cursor-pointer">
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- View Mode Footer --}}
            <div x-show="!editing" class="px-5 py-4 border-t border-gray-100 shrink-0 flex items-center gap-3">
                <button @click="editing = true" class="flex-1 py-3 bg-[#C0420A] text-white rounded-2xl text-xs font-black uppercase tracking-widest hover:bg-black transition-all shadow-md flex items-center justify-center gap-2">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                    Edit Payment Info & QR Codes
                </button>
                <button onclick="document.getElementById('payment-modal').style.display='none'" class="px-5 py-3 bg-gray-100 text-gray-700 rounded-2xl text-xs font-black uppercase tracking-widest hover:bg-gray-200 transition-all">
                    Close
                </button>
            </div>
        </div>
    </div>

    {{-- Legal Documents Modal with In-Modal Upload/Edit --}}
    <div id="legal-modal" x-data="{ editing: false }" style="display: none;" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-end sm:items-center justify-center">
        <div class="w-full sm:max-w-lg bg-white rounded-t-3xl sm:rounded-3xl shadow-2xl overflow-hidden flex flex-col max-h-[90vh]">
            <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100 shrink-0">
                <div>
                    <h2 class="text-sm font-black uppercase tracking-widest text-black">Legal Documents</h2>
                    <p class="text-[10px] text-gray-400 mt-0.5">Verification status: <span class="{{ $user->isVerified ? 'text-green-600' : 'text-amber-500' }} font-bold uppercase">{{ $user->isVerified ? 'Verified ✓' : 'Pending Verification' }}</span></p>
                </div>
                <button onclick="document.getElementById('legal-modal').style.display='none'" class="w-8 h-8 rounded-xl bg-gray-100 flex items-center justify-center text-gray-500 hover:bg-gray-200 transition-all">
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
                            <a href="{{ $getImgUrl($user->{$doc['field']}) }}" target="_blank" class="text-[10px] font-black text-[#C0420A] hover:underline uppercase tracking-widest shrink-0">View ↗</a>
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
                <button onclick="document.getElementById('legal-modal').style.display='none'" class="px-5 py-3 bg-gray-100 text-gray-700 rounded-2xl text-xs font-black uppercase tracking-widest hover:bg-gray-200 transition-all">
                    Close
                </button>
            </div>
        </div>
    </div>

    {{-- QR Code Lightbox --}}
    <div id="qr-lightbox"
         style="display:none;"
         class="fixed inset-0 z-200 bg-black/80 backdrop-blur-sm flex items-center justify-center p-6"
         onclick="if(event.target===this) this.style.display='none'">
        <div class="relative bg-white rounded-3xl p-4 shadow-2xl max-w-xs w-full flex flex-col items-center gap-4">
            <button onclick="document.getElementById('qr-lightbox').style.display='none'"
                class="absolute top-3 right-3 w-8 h-8 rounded-xl bg-gray-100 flex items-center justify-center text-gray-500 hover:bg-gray-200 transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
            <p class="text-[9px] font-bold text-gray-400 uppercase tracking-widest">QR Code Preview</p>
            <img id="qr-lightbox-img" src="" class="w-full max-w-60 h-auto object-contain rounded-2xl border border-gray-100 shadow-sm">
        </div>
    </div>

    <script id="payment-config" type="application/json">
    {
        "hasExistingGcashQr": {{ !empty($user->gcashQrCode) ? 'true' : 'false' }},
        "hasExistingMayaQr": {{ !empty($user->mayaQrCode) ? 'true' : 'false' }}
    }
    </script>

    <script>
    function validatePaymentModalForm(e, form) {
        const configEl = document.getElementById('payment-config');
        const config = configEl ? JSON.parse(configEl.textContent || '{}') : {};
        const hasExistingGcashQr = Boolean(config.hasExistingGcashQr);
        const hasExistingMayaQr = Boolean(config.hasExistingMayaQr);

        const gcashNum = document.getElementById('modalGcashNumber')?.value.trim();
        const gcashFile = (document.getElementById('modalGcashQr')?.files?.length || 0) > 0;

        const mayaNum = document.getElementById('modalMayaNumber')?.value.trim();
        const mayaFile = (document.getElementById('modalMayaQr')?.files?.length || 0) > 0;

        const errors = [];

        // GCash check: if either is provided, both must be present
        if (gcashNum || gcashFile) {
            const hasQr = gcashFile || hasExistingGcashQr;
            if (!gcashNum || !hasQr) {
                if (!gcashNum) errors.push('Please provide a GCash mobile number.');
                if (!hasQr) errors.push('Please upload a GCash QR Code image.');
            }
        }

        // Maya check: if either is provided, both must be present
        if (mayaNum || mayaFile) {
            const hasQr = mayaFile || hasExistingMayaQr;
            if (!mayaNum || !hasQr) {
                if (!mayaNum) errors.push('Please provide a Maya account number.');
                if (!hasQr) errors.push('Please upload a Maya QR Code image.');
            }
        }

        if (errors.length > 0) {
            e.preventDefault();
            alert('Payment Method Incomplete:\n\n• ' + errors.join('\n• ') + '\n\nEvery payment method requires BOTH a valid mobile number and a QR code image.');
            return false;
        }
        return true;
    }

    document.addEventListener('DOMContentLoaded', function() {
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('open_payment') === '1' || window.location.hash === '#payment-methods') {
            const modal = document.getElementById('payment-modal');
            if (modal) {
                modal.style.display = 'flex';
            }
        }
        // Close lightbox on Escape
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                document.getElementById('qr-lightbox').style.display = 'none';
            }
        });
    });
    </script>
@endsection