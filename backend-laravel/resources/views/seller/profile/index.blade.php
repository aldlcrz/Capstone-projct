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
        $sellerListingCount = \App\Models\Product::where('sellerId', $user->id)->count();
        $sellerOrderCount = \App\Models\Order::where('sellerId', $user->id)->count();
        $sellerTotalEarnings = \App\Models\Order::where('sellerId', $user->id)
            ->whereIn('status', ['Completed', 'Delivered', 'delivered', 'completed'])
            ->sum('totalAmount');
        $sellerAvgRating = \App\Models\Review::whereHas('product', fn($q) => $q->where('sellerId', $user->id))->avg('rating');
    @endphp

    <div class="space-y-6 max-w-5xl pb-12 px-2 sm:px-4" 
         x-data="{ 
             showEditModal: false,
             showPaymentModal: false,
             showLegalModal: false,
             paymentEditing: false,
             legalEditing: false,
             shopName: @js(old('name', $user->name ?? '')),
             mobileNumber: @js(old('mobileNumber', $user->mobileNumber ?? '')),
             shopDescription: @js(old('shopDescription', $user->shopDescription ?? ''))
         }">

        {{-- Page Header --}}
        <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-3 sm:gap-4 pb-3 border-b" style="border-color: #E8DECB;">
            <div>
                <div class="flex items-center gap-2 mb-1">
                    <span class="text-[9px] font-extrabold uppercase tracking-[0.25em]" style="color: #C49520;">✦ Shop Administration</span>
                    <span class="text-xs" style="color: #E8DECB;">•</span>
                    <span class="text-[10px] font-semibold tracking-wider uppercase" style="color: #766C60;">Artisan Settings</span>
                </div>
                <h1 class="font-serif text-2xl sm:text-3xl font-bold tracking-tight" style="color: #1E1915;">
                    Artisan <span class="italic font-normal" style="color: #766C60;">Profile & Shop</span>
                </h1>
                <p class="text-xs font-medium mt-1" style="color: #766C60;">
                    Manage your artisan business profile, contact channels, payment methods, policies, and store credentials.
                </p>
            </div>

            <button type="button" @click="showEditModal = true"
                    class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold uppercase tracking-wider text-white transition-all shadow-xs cursor-pointer"
                    style="background: #1E1915;"
                    onmouseover="this.style.background='#C49520';" onmouseout="this.style.background='#1E1915';">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                <span>Edit Profile</span>
            </button>
        </div>

        {{-- Flash Messages --}}
        @if(session('success'))
            <div class="p-4 bg-emerald-50 border border-emerald-200 rounded-2xl text-emerald-800 text-xs font-bold flex items-center gap-2.5 shadow-2xs">
                <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if(session('error') || $errors->any())
            <div class="p-4 bg-red-50 border border-red-200 rounded-2xl text-red-700 text-xs font-bold flex items-center gap-2.5 shadow-2xs">
                <svg class="w-4 h-4 text-red-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span>{{ session('error') ?? $errors->first() }}</span>
            </div>
        @endif

        {{-- 2-Column Responsive Grid --}}
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

            {{-- LEFT COLUMN: Artisan Card, Quick Metrics & Bio --}}
            <div class="lg:col-span-5 space-y-6">
                
                {{-- Artisan Profile Card --}}
                <div class="rounded-3xl p-6 relative overflow-hidden shadow-xs space-y-5" style="background: #FFFCF7; border: 1px solid #E8DECB;">
                    <div class="flex items-center gap-4">
                        <div style="width:76px;height:76px;min-width:76px;border-radius:50%;padding:2.5px;background:linear-gradient(135deg,#996515,#E6CA65,#996515);box-shadow:0 4px 14px rgba(0,0,0,0.12);" class="shrink-0">
                            <div style="width:100%;height:100%;border-radius:50%;overflow:hidden;background-color:#FAF8F5;display:flex;align-items:center;justify-content:center;">
                                @if($user->profile_photo_url)
                                    <img src="{{ $user->profile_photo_url }}" class="w-full h-full object-cover" alt="{{ $user->name }}">
                                @else
                                    <span style="font-size:26px;font-weight:800;color:#996515;">{{ strtoupper(substr($user->name, 0, 1)) }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[9px] font-extrabold uppercase tracking-wider mb-1" style="background: #FAF5EA; color: #996515; border: 1px solid #E6D8BA;">
                                ✦ {{ $user->isPremiumActive() ? 'Premium Artisan' : 'Verified Artisan Shop' }}
                            </div>
                            <h2 class="font-serif text-xl font-bold truncate" style="color: #1E1915;">{{ $user->name }}</h2>
                            <p class="text-xs truncate font-medium mt-0.5" style="color: #766C60;">{{ $user->email }}</p>
                        </div>
                    </div>

                    {{-- Quick Metrics 4-Grid --}}
                    <div class="grid grid-cols-4 gap-2 pt-2 border-t" style="border-color: #E8DECB;">
                        <a href="{{ route('seller.products.index') }}" class="p-2.5 rounded-2xl text-center transition-all block group hover:border-[#C49520]" style="background: #FDF8EE; border: 1px solid #E8DECB;">
                            <div class="text-base font-black font-sans" style="color: #C49520;">{{ $sellerListingCount }}</div>
                            <div class="text-[8px] font-bold uppercase tracking-wider mt-0.5" style="color: #766C60;">Creations</div>
                        </a>
                        <a href="{{ route('seller.orders') }}" class="p-2.5 rounded-2xl text-center transition-all block group hover:border-[#C49520]" style="background: #FDF8EE; border: 1px solid #E8DECB;">
                            <div class="text-base font-black font-sans" style="color: #1E1915;">{{ $sellerOrderCount }}</div>
                            <div class="text-[8px] font-bold uppercase tracking-wider mt-0.5" style="color: #766C60;">Orders</div>
                        </a>
                        <a href="{{ route('seller.analytics') }}" class="p-2.5 rounded-2xl text-center transition-all block group hover:border-[#C49520]" style="background: #FDF8EE; border: 1px solid #E8DECB;">
                            <div class="text-base font-black font-sans truncate" style="color: #1E1915;">₱{{ number_format($sellerTotalEarnings, 0) }}</div>
                            <div class="text-[8px] font-bold uppercase tracking-wider mt-0.5" style="color: #766C60;">Sales</div>
                        </a>
                        <a href="{{ route('seller.products.index') }}" class="p-2.5 rounded-2xl text-center transition-all block group hover:border-[#C49520]" style="background: #FDF8EE; border: 1px solid #E8DECB;">
                            <div class="text-base font-black font-sans" style="color: #1E1915;">{{ $sellerAvgRating ? number_format($sellerAvgRating, 1) : '5.0' }}★</div>
                            <div class="text-[8px] font-bold uppercase tracking-wider mt-0.5" style="color: #766C60;">Rating</div>
                        </a>
                    </div>

                    {{-- Shop Bio & Story --}}
                    <div class="p-4 rounded-2xl space-y-2" style="background: #FDF8EE; border: 1px solid #E8DECB;">
                        <div class="flex items-center justify-between">
                            <span class="text-[10px] font-bold uppercase tracking-wider" style="color: #766C60;">Artisan Story & Bio</span>
                            <button type="button" @click="showEditModal = true" class="text-[9px] font-bold uppercase tracking-wider hover:underline cursor-pointer" style="color: #C49520;">Edit Story</button>
                        </div>
                        <p class="text-xs leading-relaxed font-medium" style="color: #1E1915;">
                            {{ $user->shopDescription ?: 'Add your Lumban heritage, craftsmanship traditions, and shop story to connect with buyers.' }}
                        </p>
                    </div>

                    {{-- Trust Banner --}}
                    <div class="p-3.5 rounded-2xl flex items-center gap-3" style="background: linear-gradient(90deg, #F6F0E4 0%, #F2EADA 100%); border: 1px solid #E2D6C0;">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center shrink-0" style="background: #FAF4EA; color: #B88728; border: 1.5px solid #B88728;">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                        </div>
                        <div>
                            <div class="text-xs font-bold" style="color: #1E1915;">Verified Lumban Artisan</div>
                            <div class="text-[10px] font-medium" style="color: #766C60;">Authentic Philippine handcrafted embroidery</div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- RIGHT COLUMN: Shop & Account Settings Cards --}}
            <div class="lg:col-span-7 space-y-4">
                <div class="rounded-3xl p-6 shadow-xs space-y-4" style="background: #FFFCF7; border: 1px solid #E8DECB;">
                    <div class="flex items-center justify-between pb-2 border-b" style="border-color: #E8DECB;">
                        <h3 class="font-serif text-base font-bold" style="color: #1E1915;">Account & Shop Management</h3>
                        <span class="text-[10px] font-extrabold uppercase tracking-widest" style="color: #C49520;">Settings</span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        {{-- Email Address --}}
                        <div class="p-4 rounded-2xl flex items-center gap-3 shadow-2xs" style="background: #FDF8EE; border: 1px solid #E8DECB;">
                            <div class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0" style="background: #FAF5EA; border: 1px solid #E6D8BA; color: #B88728;">
                                <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><rect x="2" y="4" width="20" height="16" rx="2" stroke-width="1.8"/><path d="M22 7l-8.97 5.7a1.94 1.94 0 01-2.06 0L2 7" stroke-width="1.8"/></svg>
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="text-[9px] font-bold uppercase tracking-wider" style="color: #766C60;">Email Address</div>
                                <div class="text-xs font-bold truncate mt-0.5" style="color: #1E1915;">{{ $user->email }}</div>
                            </div>
                        </div>

                        {{-- Mobile Number --}}
                        <div class="p-4 rounded-2xl flex items-center justify-between gap-3 shadow-2xs" style="background: #FDF8EE; border: 1px solid #E8DECB;">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0" style="background: #FAF5EA; border: 1px solid #E6D8BA; color: #B88728;">
                                    <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                </div>
                                <div class="min-w-0">
                                    <div class="text-[9px] font-bold uppercase tracking-wider" style="color: #766C60;">Mobile Number</div>
                                    <div class="text-xs font-bold font-sans truncate mt-0.5" style="color: #1E1915;">{{ $user->mobileNumber ?: 'Not provided' }}</div>
                                </div>
                            </div>
                            <button type="button" @click="showEditModal = true" class="text-[9px] font-bold uppercase tracking-wider px-2 py-1 rounded-lg transition-colors cursor-pointer" style="background: #FAF5EA; color: #996515; border: 1px solid #E6D8BA;">Edit</button>
                        </div>
                    </div>

                    {{-- Navigation Action Rows --}}
                    <div class="space-y-3 pt-2">
                        {{-- Analytics --}}
                        <a href="{{ route('seller.analytics') }}" class="p-4 rounded-2xl flex items-center justify-between transition-all group shadow-2xs" style="background: #FDF8EE; border: 1px solid #E8DECB;" onmouseover="this.style.borderColor='#C49520'; this.style.background='#FFF';" onmouseout="this.style.borderColor='#E8DECB'; this.style.background='#FDF8EE';">
                            <div class="flex items-center gap-3.5">
                                <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0 group-hover:scale-105 transition-transform" style="background: #FAF5EA; border: 1px solid #E6D8BA; color: #B88728;">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                                </div>
                                <div>
                                    <div class="text-xs font-bold" style="color: #1E1915;">Shop Analytics & Intelligence</div>
                                    <div class="text-[10px] font-medium" style="color: #766C60;">Sales trends, conversion rate, and store performance</div>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-[9px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-md" style="background: #FAF5EA; color: #996515; border: 1px solid #E6D8BA;">View</span>
                                <svg class="w-4 h-4 text-gray-400 group-hover:translate-x-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            </div>
                        </a>

                        {{-- Payment Methods --}}
                        <button type="button" @click="showPaymentModal = true; paymentEditing = false;" class="w-full text-left p-4 rounded-2xl flex items-center justify-between transition-all group shadow-2xs cursor-pointer" style="background: #FDF8EE; border: 1px solid #E8DECB;" onmouseover="this.style.borderColor='#C49520'; this.style.background='#FFF';" onmouseout="this.style.borderColor='#E8DECB'; this.style.background='#FDF8EE';">
                            <div class="flex items-center gap-3.5">
                                <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0 group-hover:scale-105 transition-transform" style="background: #FAF5EA; border: 1px solid #E6D8BA; color: #B88728;">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                                </div>
                                <div>
                                    <div class="text-xs font-bold" style="color: #1E1915;">Payment Accounts & QR Codes</div>
                                    <div class="text-[10px] font-medium" style="color: #766C60;">Manage GCash & Maya accounts and customer checkout QR</div>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-[9px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-md" style="background: #FAF5EA; color: #996515; border: 1px solid #E6D8BA;">Manage</span>
                                <svg class="w-4 h-4 text-gray-400 group-hover:translate-x-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            </div>
                        </button>

                        {{-- Legal Documents --}}
                        <button type="button" @click="showLegalModal = true; legalEditing = false;" class="w-full text-left p-4 rounded-2xl flex items-center justify-between transition-all group shadow-2xs cursor-pointer" style="background: #FDF8EE; border: 1px solid #E8DECB;" onmouseover="this.style.borderColor='#C49520'; this.style.background='#FFF';" onmouseout="this.style.borderColor='#E8DECB'; this.style.background='#FDF8EE';">
                            <div class="flex items-center gap-3.5">
                                <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0 group-hover:scale-105 transition-transform" style="background: #FAF5EA; border: 1px solid #E6D8BA; color: #B88728;">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                </div>
                                <div>
                                    <div class="text-xs font-bold" style="color: #1E1915;">Legal Documents & Permits</div>
                                    <div class="text-[10px] font-medium" style="color: #766C60;">Business Permit, BIR Certificate of Registration & Barangay Clearance</div>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-[9px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-md" style="background: #FAF5EA; color: #996515; border: 1px solid #E6D8BA;">Manage</span>
                                <svg class="w-4 h-4 text-gray-400 group-hover:translate-x-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            </div>
                        </button>

                        {{-- Shop Policies --}}
                        <a href="{{ route('seller.policies.index') }}" class="p-4 rounded-2xl flex items-center justify-between transition-all group shadow-2xs" style="background: #FDF8EE; border: 1px solid #E8DECB;" onmouseover="this.style.borderColor='#C49520'; this.style.background='#FFF';" onmouseout="this.style.borderColor='#E8DECB'; this.style.background='#FDF8EE';">
                            <div class="flex items-center gap-3.5">
                                <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0 group-hover:scale-105 transition-transform" style="background: #FAF5EA; border: 1px solid #E6D8BA; color: #B88728;">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                                </div>
                                <div>
                                    <div class="text-xs font-bold" style="color: #1E1915;">Shop Policies & Guidelines</div>
                                    <div class="text-[10px] font-medium" style="color: #766C60;">Cancellation windows, return terms, and custom tailoring policies</div>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-[9px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-md" style="background: #FAF5EA; color: #996515; border: 1px solid #E6D8BA;">Configure</span>
                                <svg class="w-4 h-4 text-gray-400 group-hover:translate-x-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            </div>
                        </a>

                        {{-- Change Password --}}
                        <a href="{{ route('profile.change-password') }}" class="p-4 rounded-2xl flex items-center justify-between transition-all group shadow-2xs" style="background: #FDF8EE; border: 1px solid #E8DECB;" onmouseover="this.style.borderColor='#C49520'; this.style.background='#FFF';" onmouseout="this.style.borderColor='#E8DECB'; this.style.background='#FDF8EE';">
                            <div class="flex items-center gap-3.5">
                                <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0 group-hover:scale-105 transition-transform" style="background: #FAF5EA; border: 1px solid #E6D8BA; color: #B88728;">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="11" rx="2" stroke-width="1.8"/><path d="M7 11V7a5 5 0 0110 0v4" stroke-width="1.8"/></svg>
                                </div>
                                <div>
                                    <div class="text-xs font-bold" style="color: #1E1915;">Security & Password</div>
                                    <div class="text-[10px] font-medium" style="color: #766C60;">Update login password and 2-step verification settings</div>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-[9px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-md" style="background: #FAF5EA; color: #996515; border: 1px solid #E6D8BA;">Security</span>
                                <svg class="w-4 h-4 text-gray-400 group-hover:translate-x-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </div>  </div>

        {{-- Edit Profile Modal --}}
        <div x-show="showEditModal"
             x-cloak
             style="display:none;"
             class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             @keydown.escape.window="showEditModal = false">

            <div class="relative w-full max-w-md rounded-3xl p-6 shadow-2xl space-y-4"
                 style="background: #FFFCF7; border: 1px solid #E8DECB;"
                 @click.away="showEditModal = false">

                <div class="flex items-center justify-between pb-3 border-b" style="border-color: #E8DECB;">
                    <div>
                        <h3 class="font-serif text-base font-bold" style="color: #1E1915;">Edit Artisan Profile</h3>
                        <p class="text-[10px]" style="color: #766C60;">Update your shop identity and contact information</p>
                    </div>
                    <button type="button" @click="showEditModal = false" class="w-8 h-8 rounded-xl flex items-center justify-center transition-colors cursor-pointer" style="background: #FDF8EE; color: #766C60;">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <form action="{{ route('seller.profile.update') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                    @csrf
                    @method('PUT')

                    {{-- Profile Picture Preview & Upload --}}
                    <div class="text-center space-y-2">
                        <div style="width:84px;height:84px;border-radius:50%;padding:2.5px;background:linear-gradient(135deg,#996515,#E6CA65,#996515);margin:0 auto;" class="relative group cursor-pointer">
                            <div style="width:100%;height:100%;border-radius:50%;overflow:hidden;background:#FAF8F5;" class="relative">
                                <img id="seller-modal-avatar-preview"
                                     src="{{ $user->profile_photo_url ?: asset('uploads/products/default.jpg') }}"
                                     class="w-full h-full object-cover">
                                <label class="absolute inset-0 bg-black/40 text-white opacity-0 group-hover:opacity-100 transition-opacity flex flex-col items-center justify-center cursor-pointer">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><circle cx="12" cy="13" r="4"/></svg>
                                    <span class="text-[8px] font-bold uppercase mt-0.5">Change</span>
                                    <input type="file" name="profilePhoto" accept="image/*" class="hidden" onchange="previewSellerModalAvatar(this)">
                                </label>
                            </div>
                        </div>
                        <p class="text-[10px]" style="color: #766C60;">Click photo to upload new picture</p>
                    </div>

                    {{-- Shop Name --}}
                    <div class="space-y-1">
                        <label class="text-[10px] font-bold uppercase tracking-wider" style="color: #766C60;">Shop / Artisan Name</label>
                        <input type="text"
                               name="name"
                               value="{{ old('name', $user->name) }}"
                               required
                               class="w-full h-10 px-3.5 rounded-xl text-xs font-semibold outline-none transition-colors"
                               style="background: #FDF8EE; border: 1px solid #E8DECB; color: #1E1915;"
                               onfocus="this.style.borderColor='#C49520'; this.style.background='#FFF';"
                               onblur="this.style.borderColor='#E8DECB'; this.style.background='#FDF8EE';">
                    </div>

                    {{-- Mobile Number --}}
                    <div class="space-y-1">
                        <label class="text-[10px] font-bold uppercase tracking-wider" style="color: #766C60;">Mobile Contact Number</label>
                        <input type="text"
                               name="mobileNumber"
                               value="{{ old('mobileNumber', $user->mobileNumber) }}"
                               placeholder="e.g. 0917 123 4567"
                               class="w-full h-10 px-3.5 rounded-xl text-xs font-semibold outline-none transition-colors"
                               style="background: #FDF8EE; border: 1px solid #E8DECB; color: #1E1915;"
                               onfocus="this.style.borderColor='#C49520'; this.style.background='#FFF';"
                               onblur="this.style.borderColor='#E8DECB'; this.style.background='#FDF8EE';">
                    </div>

                    {{-- Shop Description --}}
                    <div class="space-y-1">
                        <label class="text-[10px] font-bold uppercase tracking-wider" style="color: #766C60;">Artisan Story & Workshop Bio</label>
                        <textarea name="shopDescription"
                                  rows="3"
                                  placeholder="Tell customers about your Lumban artisan craft and history..."
                                  class="w-full p-3 rounded-xl text-xs font-medium outline-none transition-colors resize-none"
                                  style="background: #FDF8EE; border: 1px solid #E8DECB; color: #1E1915;"
                                  onfocus="this.style.borderColor='#C49520'; this.style.background='#FFF';"
                                  onblur="this.style.borderColor='#E8DECB'; this.style.background='#FDF8EE';">{{ old('shopDescription', $user->shopDescription) }}</textarea>
                    </div>

                    {{-- Actions --}}
                    <div class="flex items-center gap-3 pt-2">
                        <button type="button"
                                @click="showEditModal = false"
                                class="flex-1 py-2.5 px-4 rounded-xl text-xs font-bold uppercase tracking-wider transition-all cursor-pointer"
                                style="background: #FDF8EE; border: 1px solid #E8DECB; color: #766C60;">
                            Cancel
                        </button>
                        <button type="submit"
                                class="flex-1 py-2.5 px-4 rounded-xl text-white text-xs font-bold uppercase tracking-wider shadow-md transition-all cursor-pointer"
                                style="background: #1E1915;"
                                onmouseover="this.style.background='#C49520';"
                                onmouseout="this.style.background='#1E1915';">
                            Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Payment Methods Modal with In-Modal Editing --}}
        <div x-show="showPaymentModal" 
             x-cloak 
             style="display: none;" 
             class="fixed inset-0 bg-black/60 backdrop-blur-xs z-50 flex items-end sm:items-center justify-center p-0 sm:p-4">
            <div class="w-full sm:max-w-lg rounded-t-3xl sm:rounded-3xl shadow-2xl overflow-hidden flex flex-col max-h-[90vh]" style="background: #FFFCF7; border: 1px solid #E8DECB;">
                <div class="flex items-center justify-between px-5 py-4 border-b shrink-0" style="border-color: #E8DECB;">
                    <div>
                        <h2 class="font-serif text-sm font-bold uppercase tracking-wider" style="color: #1E1915;">Payment Accounts</h2>
                        <p class="text-[10px]" style="color: #766C60;" x-text="paymentEditing ? 'Edit your mobile numbers and upload QR code files' : 'Your customer payout accounts & QR codes'"></p>
                    </div>
                    <button @click="showPaymentModal = false" class="w-8 h-8 rounded-xl flex items-center justify-center transition-all cursor-pointer" style="background: #FDF8EE; color: #766C60;">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                {{-- Modal Body --}}
                <div class="overflow-y-auto flex-1 p-5 space-y-4">
                    {{-- View Mode --}}
                    <div x-show="!paymentEditing" class="space-y-3">
                        {{-- GCash Card --}}
                        <div class="rounded-2xl border overflow-hidden" style="border-color: #E8DECB;">
                            <div class="flex items-center justify-between px-4 py-2.5" style="background: #1E1915; color: #FFFCF7;">
                                <div class="flex items-center gap-2">
                                    <span class="text-[10px] font-bold uppercase tracking-widest text-[#C49520]">✦ GCash Account</span>
                                </div>
                                @if($user->gcashNumber)
                                    <span class="text-[9px] font-bold px-2 py-0.5 rounded-full uppercase" style="background: rgba(196,149,32,0.25); color: #FFFCF7;">Active</span>
                                @else
                                    <span class="text-[9px] font-bold px-2 py-0.5 rounded-full uppercase opacity-60">Not Set</span>
                                @endif
                            </div>
                            <div class="p-4 flex items-start gap-4" style="background: #FFFFFF;">
                                <div class="shrink-0">
                                    @if(!empty($user->gcashQrCode) && $getImgUrl($user->gcashQrCode))
                                        <img src="{{ $getImgUrl($user->gcashQrCode) }}"
                                             class="w-20 h-20 object-contain rounded-xl border p-1 shadow-2xs cursor-zoom-in"
                                             style="border-color: #E8DECB; background: #FDF8EE;"
                                             title="Click to view full size"
                                             onclick="document.getElementById('qr-lightbox-img').src=this.src; document.getElementById('qr-lightbox').style.display='flex'">
                                    @else
                                        <div class="w-20 h-20 rounded-xl border-2 border-dashed flex flex-col items-center justify-center gap-1" style="border-color: #E8DECB; background: #FDF8EE;">
                                            <span class="text-[9px] font-bold uppercase" style="color: #A09585;">No QR</span>
                                        </div>
                                    @endif
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="text-[9px] font-bold uppercase tracking-widest" style="color: #766C60;">Mobile Number</div>
                                    @if($user->gcashNumber)
                                        <div class="text-base font-bold font-sans select-all" style="color: #1E1915;">{{ $user->gcashNumber }}</div>
                                        <div class="text-[9px] font-bold uppercase tracking-widest mt-1 flex items-center gap-1" style="color: #4A6741;">
                                            ✓ Ready to receive customer payments
                                        </div>
                                    @else
                                        <div class="text-xs italic" style="color: #A09585;">Not configured</div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        {{-- Maya Card --}}
                        <div class="rounded-2xl border overflow-hidden" style="border-color: #E8DECB;">
                            <div class="flex items-center justify-between px-4 py-2.5" style="background: #1E1915; color: #FFFCF7;">
                                <div class="flex items-center gap-2">
                                    <span class="text-[10px] font-bold uppercase tracking-widest text-[#C49520]">✦ Maya Account</span>
                                </div>
                                @if($user->mayaNumber)
                                    <span class="text-[9px] font-bold px-2 py-0.5 rounded-full uppercase" style="background: rgba(196,149,32,0.25); color: #FFFCF7;">Active</span>
                                @else
                                    <span class="text-[9px] font-bold px-2 py-0.5 rounded-full uppercase opacity-60">Not Set</span>
                                @endif
                            </div>
                            <div class="p-4 flex items-start gap-4" style="background: #FFFFFF;">
                                <div class="shrink-0">
                                    @if(!empty($user->mayaQrCode) && $getImgUrl($user->mayaQrCode))
                                        <img src="{{ $getImgUrl($user->mayaQrCode) }}"
                                             class="w-20 h-20 object-contain rounded-xl border p-1 shadow-2xs cursor-zoom-in"
                                             style="border-color: #E8DECB; background: #FDF8EE;"
                                             title="Click to view full size"
                                             onclick="document.getElementById('qr-lightbox-img').src=this.src; document.getElementById('qr-lightbox').style.display='flex'">
                                    @else
                                        <div class="w-20 h-20 rounded-xl border-2 border-dashed flex flex-col items-center justify-center gap-1" style="border-color: #E8DECB; background: #FDF8EE;">
                                            <span class="text-[9px] font-bold uppercase" style="color: #A09585;">No QR</span>
                                        </div>
                                    @endif
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="text-[9px] font-bold uppercase tracking-widest" style="color: #766C60;">Mobile Number</div>
                                    @if($user->mayaNumber)
                                        <div class="text-base font-bold font-sans select-all" style="color: #1E1915;">{{ $user->mayaNumber }}</div>
                                        <div class="text-[9px] font-bold uppercase tracking-widest mt-1 flex items-center gap-1" style="color: #4A6741;">
                                            ✓ Ready to receive customer payments
                                        </div>
                                    @else
                                        <div class="text-xs italic" style="color: #A09585;">Not configured</div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Edit Mode Form --}}
                    <div x-show="paymentEditing" style="display: none;">
                        <form action="{{ route('seller.profile.update') }}" method="POST" enctype="multipart/form-data" class="space-y-4" onsubmit="return validatePaymentModalForm(event, this)">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="name" value="{{ $user->name }}">
                            <input type="hidden" name="mobileNumber" value="{{ $user->mobileNumber }}">
                            <input type="hidden" name="shopDescription" value="{{ $user->shopDescription }}">

                            {{-- GCash Edit --}}
                            <div class="p-4 rounded-2xl space-y-3" style="background: #FDF8EE; border: 1px solid #E8DECB;">
                                <div class="text-[10px] font-bold uppercase tracking-widest" style="color: #A16D19;">GCash Configuration</div>
                                <div class="space-y-1">
                                    <label class="text-[9px] font-bold uppercase" style="color: #766C60;">GCash Mobile Number</label>
                                    <input type="text" id="modalGcashNumber" name="gcashNumber" value="{{ old('gcashNumber', $user->gcashNumber) }}" placeholder="e.g. 0917 123 4567" class="w-full px-3.5 py-2.5 rounded-xl text-xs font-bold outline-none bg-white border" style="border-color: #E8DECB;">
                                </div>
                                <div class="space-y-1">
                                    <label class="text-[9px] font-bold uppercase" style="color: #766C60;">GCash QR Code Image</label>
                                    <input type="file" id="modalGcashQr" name="gcashQrCode" accept="image/*" class="w-full text-xs text-gray-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-[#1E1915] file:text-white">
                                </div>
                            </div>

                            {{-- Maya Edit --}}
                            <div class="p-4 rounded-2xl space-y-3" style="background: #FDF8EE; border: 1px solid #E8DECB;">
                                <div class="text-[10px] font-bold uppercase tracking-widest" style="color: #A16D19;">Maya Configuration</div>
                                <div class="space-y-1">
                                    <label class="text-[9px] font-bold uppercase" style="color: #766C60;">Maya Mobile Number</label>
                                    <input type="text" id="modalMayaNumber" name="mayaNumber" value="{{ old('mayaNumber', $user->mayaNumber) }}" placeholder="e.g. 0917 123 4567" class="w-full px-3.5 py-2.5 rounded-xl text-xs font-bold outline-none bg-white border" style="border-color: #E8DECB;">
                                </div>
                                <div class="space-y-1">
                                    <label class="text-[9px] font-bold uppercase" style="color: #766C60;">Maya QR Code Image</label>
                                    <input type="file" id="modalMayaQr" name="mayaQrCode" accept="image/*" class="w-full text-xs text-gray-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-[#1E1915] file:text-white">
                                </div>
                            </div>

                            <div class="pt-2 flex items-center gap-3">
                                <button type="submit" class="flex-1 py-3 text-white rounded-xl text-xs font-bold uppercase tracking-widest shadow-md cursor-pointer" style="background: #1E1915;">
                                    Save Payment Info
                                </button>
                                <button type="button" @click="paymentEditing = false" class="px-5 py-3 rounded-xl text-xs font-bold uppercase tracking-widest cursor-pointer" style="background: #FDF8EE; border: 1px solid #E8DECB; color: #766C60;">
                                    Cancel
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                {{-- View Mode Footer --}}
                <div x-show="!paymentEditing" class="px-5 py-4 border-t shrink-0 flex items-center gap-3" style="border-color: #E8DECB;">
                    <button @click="paymentEditing = true" class="flex-1 py-2.5 text-white rounded-xl text-xs font-bold uppercase tracking-widest shadow-md flex items-center justify-center gap-2 cursor-pointer" style="background: #1E1915;">
                        Edit Accounts & QR Codes
                    </button>
                    <button @click="showPaymentModal = false" class="px-5 py-2.5 rounded-xl text-xs font-bold uppercase tracking-widest cursor-pointer" style="background: #FDF8EE; border: 1px solid #E8DECB; color: #766C60;">
                        Close
                    </button>
                </div>
            </div>
        </div>

        {{-- Legal Documents Modal --}}
        <div x-show="showLegalModal" 
             x-cloak 
             style="display: none;" 
             class="fixed inset-0 bg-black/60 backdrop-blur-xs z-50 flex items-end sm:items-center justify-center p-0 sm:p-4">
            <div class="w-full sm:max-w-lg rounded-t-3xl sm:rounded-3xl shadow-2xl overflow-hidden flex flex-col max-h-[90vh]" style="background: #FFFCF7; border: 1px solid #E8DECB;">
                <div class="flex items-center justify-between px-5 py-4 border-b shrink-0" style="border-color: #E8DECB;">
                    <div>
                        <h2 class="font-serif text-sm font-bold uppercase tracking-wider" style="color: #1E1915;">Legal Documents</h2>
                        <p class="text-[10px]" style="color: #766C60;">Verification status: <span class="{{ $user->isVerified ? 'text-green-600' : 'text-amber-600' }} font-bold uppercase">{{ $user->isVerified ? 'Verified ✓' : 'Pending Verification' }}</span></p>
                    </div>
                    <button @click="showLegalModal = false" class="w-8 h-8 rounded-xl flex items-center justify-center transition-all cursor-pointer" style="background: #FDF8EE; color: #766C60;">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                {{-- Modal Body --}}
                <div class="overflow-y-auto flex-1 p-5">
                    {{-- View Mode --}}
                    <div x-show="!legalEditing" class="space-y-3">
                        @foreach([
                            ['label' => 'Business Permit', 'field' => 'businessPermit'],
                            ['label' => 'BIR Certificate', 'field' => 'birDocument'],
                            ['label' => 'Residency Certificate', 'field' => 'residencyCertificate'],
                        ] as $doc)
                        <div class="flex items-center justify-between p-3.5 rounded-2xl" style="background: #FDF8EE; border: 1px solid #E8DECB;">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0" style="background: #FAF5EA; border: 1px solid #E6D8BA; color: #C49520;">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                </div>
                                <div>
                                    <div class="text-xs font-bold" style="color: #1E1915;">{{ $doc['label'] }}</div>
                                    @if($user->{$doc['field']})
                                        <div class="text-[9px] font-bold uppercase tracking-widest" style="color: #4A6741;">✓ Uploaded</div>
                                    @else
                                        <div class="text-[9px] font-bold uppercase tracking-widest" style="color: #A09585;">Not uploaded</div>
                                    @endif
                                </div>
                            </div>
                            @if($user->{$doc['field']})
                                <a href="{{ $getImgUrl($user->{$doc['field']}) }}" target="_blank" class="text-[10px] font-bold uppercase tracking-widest hover:underline" style="color: #C49520;">View ↗</a>
                            @endif
                        </div>
                        @endforeach
                    </div>

                    {{-- Edit Mode Form --}}
                    <div x-show="legalEditing" style="display: none;">
                        <form action="{{ route('seller.profile.update') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="name" value="{{ $user->name }}">
                            <input type="hidden" name="mobileNumber" value="{{ $user->mobileNumber }}">
                            <input type="hidden" name="shopDescription" value="{{ $user->shopDescription }}">

                            <div class="p-3.5 rounded-2xl space-y-1.5" style="background: #FDF8EE; border: 1px solid #E8DECB;">
                                <label class="text-[10px] font-bold uppercase" style="color: #766C60;">Business Permit File</label>
                                <input type="file" name="businessPermit" accept=".pdf,image/*" class="w-full text-xs text-gray-500 file:mr-2 file:py-1 file:px-2.5 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-[#1E1915] file:text-white">
                            </div>

                            <div class="p-3.5 rounded-2xl space-y-1.5" style="background: #FDF8EE; border: 1px solid #E8DECB;">
                                <label class="text-[10px] font-bold uppercase" style="color: #766C60;">BIR Document File</label>
                                <input type="file" name="birDocument" accept=".pdf,image/*" class="w-full text-xs text-gray-500 file:mr-2 file:py-1 file:px-2.5 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-[#1E1915] file:text-white">
                            </div>

                            <div class="p-3.5 rounded-2xl space-y-1.5" style="background: #FDF8EE; border: 1px solid #E8DECB;">
                                <label class="text-[10px] font-bold uppercase" style="color: #766C60;">Residency Certificate File</label>
                                <input type="file" name="residencyCertificate" accept=".pdf,image/*" class="w-full text-xs text-gray-500 file:mr-2 file:py-1 file:px-2.5 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-[#1E1915] file:text-white">
                            </div>

                            <div class="pt-2 flex items-center gap-3">
                                <button type="submit" class="flex-1 py-3 text-white rounded-xl text-xs font-bold uppercase tracking-widest shadow-md cursor-pointer" style="background: #1E1915;">
                                    Save Legal Documents
                                </button>
                                <button type="button" @click="legalEditing = false" class="px-5 py-3 rounded-xl text-xs font-bold uppercase tracking-widest cursor-pointer" style="background: #FDF8EE; border: 1px solid #E8DECB; color: #766C60;">
                                    Cancel
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                {{-- View Mode Footer --}}
                <div x-show="!legalEditing" class="px-5 py-4 border-t shrink-0 flex items-center gap-3" style="border-color: #E8DECB;">
                    <button @click="legalEditing = true" class="flex-1 py-2.5 text-white rounded-xl text-xs font-bold uppercase tracking-widest shadow-md cursor-pointer" style="background: #1E1915;">
                        ✏ Upload / Replace Documents
                    </button>
                    <button @click="showLegalModal = false" class="px-5 py-2.5 rounded-xl text-xs font-bold uppercase tracking-widest cursor-pointer" style="background: #FDF8EE; border: 1px solid #E8DECB; color: #766C60;">
                        Close
                    </button>
                </div>
            </div>
        </div>

        {{-- QR Code Lightbox --}}
        <div id="qr-lightbox"
             style="display:none;"
             class="fixed inset-0 z-200 bg-black/80 backdrop-blur-xs flex items-center justify-center p-6"
             onclick="if(event.target===this) this.style.display='none'">
            <div class="relative rounded-3xl p-4 shadow-2xl max-w-xs w-full flex flex-col items-center gap-4" style="background: #FFFCF7; border: 1px solid #E8DECB;">
                <button onclick="document.getElementById('qr-lightbox').style.display='none'"
                    class="absolute top-3 right-3 w-8 h-8 rounded-xl flex items-center justify-center transition-all cursor-pointer"
                    style="background: #FDF8EE; color: #766C60;">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
                <p class="text-[9px] font-bold uppercase tracking-widest" style="color: #766C60;">QR Code Preview</p>
                <img id="qr-lightbox-img" src="" class="w-full max-w-60 h-auto object-contain rounded-2xl border shadow-xs" style="background: #FFF; border-color: #E8DECB;">
            </div>
        </div>

    </div>

    <script id="payment-config" type="application/json">
    {
        "hasExistingGcashQr": {{ !empty($user->gcashQrCode) ? 'true' : 'false' }},
        "hasExistingMayaQr": {{ !empty($user->mayaQrCode) ? 'true' : 'false' }}
    }
    </script>

    <script>
    function previewSellerModalAvatar(input) {
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const img = document.getElementById('seller-modal-avatar-preview');
                if (img) img.src = e.target.result;
            }
            reader.readAsDataURL(input.files[0]);
        }
    }

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

        if (gcashNum || gcashFile) {
            const hasQr = gcashFile || hasExistingGcashQr;
            if (!gcashNum || !hasQr) {
                if (!gcashNum) errors.push('Please provide a GCash mobile number.');
                if (!hasQr) errors.push('Please upload a GCash QR Code image.');
            }
        }

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
            const root = document.querySelector('[x-data]');
            if (root && root._x_dataStack) {
                root._x_dataStack[0].showPaymentModal = true;
            }
        }
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                document.getElementById('qr-lightbox').style.display = 'none';
            }
        });
    });
    </script>
@endsection