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

    <div class="min-h-[calc(100vh-120px)] px-2 py-4 sm:px-4 sm:py-6" 
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

        {{-- Centered Artisan Profile Card (Customer Profile Theme) --}}
        <div style="max-width:540px;margin:0 auto;background-color:#FDFBF7;border:1px solid #EAE2D2;border-radius:28px;box-shadow:0 20px 50px rgba(0,0,0,0.06);padding:28px 24px;color:#1E1915;">

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
                        Personal information & artisan shop settings
                    </p>
                </div>
            </div>

            {{-- Star Divider --}}
            <div style="position:relative;margin:18px 0 20px 0;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <div style="width:100%;border-top:1px solid #EAE1D0;"></div>
                <span style="position:absolute;background-color:#FDFBF7;padding:0 12px;color:#C49520;font-size:12px;">✦</span>
            </div>

            {{-- Flash Messages --}}
            @if(session('success'))
                <div class="mb-4 p-3.5 bg-emerald-50 border border-emerald-200 rounded-2xl text-emerald-800 text-xs font-bold flex items-center gap-2.5 shadow-2xs">
                    <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            @if(session('error') || $errors->any())
                <div class="mb-4 p-3.5 bg-red-50 border border-red-200 rounded-2xl text-red-700 text-xs font-bold flex items-center gap-2.5 shadow-2xs">
                    <svg class="w-4 h-4 text-red-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span>{{ session('error') ?? $errors->first() }}</span>
                </div>
            @endif

            {{-- Profile Avatar & User Card --}}
            <div style="position:relative;padding-top:10px;margin-bottom:20px;">
                {{-- Floating Gold-Ringed Avatar --}}
                <div style="width:92px;height:92px;min-width:92px;max-width:92px;min-height:92px;max-height:92px;border-radius:50%;padding:2.5px;background:linear-gradient(135deg,#996515,#E6CA65,#996515);box-shadow:0 4px 14px rgba(0,0,0,0.12);margin:0 auto -46px auto;position:relative;z-index:10;display:block;">
                    <div style="width:100%;height:100%;border-radius:50%;overflow:hidden;background-color:#FAF8F5;display:flex;align-items:center;justify-content:center;position:relative;">
                        @if($user->profile_photo_url)
                            <img src="{{ $user->profile_photo_url }}" 
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
                            {{ $user->name }}
                        </h2>
                        <p style="font-size:12px;color:#78716C;margin:3px 0 0 0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                            {{ $user->email }} • <span style="color:#A16D19;font-weight:600;">Artisan Shop</span>
                        </p>
                    </div>

                    {{-- Edit Button (Top Right of Card) --}}
                    <button type="button"
                            @click="showEditModal = true"
                            style="width:40px;height:40px;border-radius:12px;background-color:#FAF6EE;border:1px solid #E2D9C8;display:flex;align-items:center;justify-content:center;color:#78716C;cursor:pointer;flex-shrink:0;box-shadow:0 1px 3px rgba(0,0,0,0.05);transition:all 0.2s;"
                            onmouseover="this.style.borderColor='#C49520'; this.style.color='#1E1915';"
                            onmouseout="this.style.borderColor='#E2D9C8'; this.style.color='#78716C';"
                            title="Edit Profile">
                        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/>
                        </svg>
                    </button>
                </div>
            </div>

            {{-- Artisan Quick Metrics Row --}}
            <div style="display:grid;grid-template-columns:repeat(4, 1fr);gap:8px;margin-bottom:20px;">
                <div style="background-color:#FFFFFF;border:1px solid #ECE3D2;border-radius:14px;padding:10px 6px;text-align:center;box-shadow:0 1px 4px rgba(0,0,0,0.02);">
                    <div style="font-size:14px;font-weight:800;color:#C49520;" class="font-sans">{{ $sellerListingCount }}</div>
                    <div style="font-size:8px;font-weight:700;color:#8C827A;text-transform:uppercase;letter-spacing:0.04em;margin-top:2px;">Creations</div>
                </div>
                <div style="background-color:#FFFFFF;border:1px solid #ECE3D2;border-radius:14px;padding:10px 6px;text-align:center;box-shadow:0 1px 4px rgba(0,0,0,0.02);">
                    <div style="font-size:14px;font-weight:800;color:#1E1915;" class="font-sans">{{ $sellerOrderCount }}</div>
                    <div style="font-size:8px;font-weight:700;color:#8C827A;text-transform:uppercase;letter-spacing:0.04em;margin-top:2px;">Orders</div>
                </div>
                <div style="background-color:#FFFFFF;border:1px solid #ECE3D2;border-radius:14px;padding:10px 6px;text-align:center;box-shadow:0 1px 4px rgba(0,0,0,0.02);">
                    <div style="font-size:14px;font-weight:800;color:#1E1915;" class="font-sans">₱{{ number_format($sellerTotalEarnings, 0) }}</div>
                    <div style="font-size:8px;font-weight:700;color:#8C827A;text-transform:uppercase;letter-spacing:0.04em;margin-top:2px;">Sales</div>
                </div>
                <div style="background-color:#FFFFFF;border:1px solid #ECE3D2;border-radius:14px;padding:10px 6px;text-align:center;box-shadow:0 1px 4px rgba(0,0,0,0.02);">
                    <div style="font-size:14px;font-weight:800;color:#1E1915;" class="font-sans">{{ $sellerAvgRating ? number_format($sellerAvgRating, 1) : '5.0' }}★</div>
                    <div style="font-size:8px;font-weight:700;color:#8C827A;text-transform:uppercase;letter-spacing:0.04em;margin-top:2px;">Rating</div>
                </div>
            </div>

            {{-- Account Settings Section --}}
            <div>
                <h3 style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.14em;color:#996515;margin:0 0 12px 2px;">
                    Account & Shop Settings
                </h3>

                <div style="display:flex;flex-direction:column;gap:10px;">
                    {{-- Email Address --}}
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

                    {{-- Mobile Number --}}
                    <div style="background-color:#FFFFFF;border:1px solid #ECE3D2;border-radius:16px;padding:14px 16px;display:flex;align-items:center;justify-content:space-between;box-shadow:0 2px 6px rgba(0,0,0,0.02);">
                        <div style="display:flex;align-items:center;gap:12px;min-width:0;">
                            <div style="width:38px;height:38px;border-radius:11px;background-color:#FAF5EA;border:1px solid #E6D8BA;display:flex;align-items:center;justify-content:center;color:#B88728;flex-shrink:0;">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                </svg>
                            </div>
                            <div style="min-width:0;">
                                <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#8C827A;line-height:1.1;">Mobile Number</div>
                                <div style="font-size:13.5px;font-weight:700;color:#1E1915;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;margin-top:2px;">
                                    {{ $user->mobileNumber ?: 'Not provided yet' }}
                                </div>
                            </div>
                        </div>
                        <button type="button" @click="showEditModal = true" style="font-size:10px;font-weight:800;color:#996515;background-color:#FAF5EA;border:1px solid #E6D8BA;padding:3px 9px;border-radius:6px;text-transform:uppercase;letter-spacing:0.04em;cursor:pointer;">
                            Edit
                        </button>
                    </div>

                    {{-- Shop Description / Bio --}}
                    <div style="background-color:#FFFFFF;border:1px solid #ECE3D2;border-radius:16px;padding:14px 16px;display:flex;align-items:center;justify-content:space-between;box-shadow:0 2px 6px rgba(0,0,0,0.02);">
                        <div style="display:flex;align-items:center;gap:12px;min-width:0;">
                            <div style="width:38px;height:38px;border-radius:11px;background-color:#FAF5EA;border:1px solid #E6D8BA;display:flex;align-items:center;justify-content:center;color:#B88728;flex-shrink:0;">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                </svg>
                            </div>
                            <div style="min-width:0;">
                                <div style="font-size:14px;font-weight:700;color:#1E1915;">Shop Story & Bio</div>
                                <div style="font-size:11px;color:#8C827A;margin-top:1px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:280px;">
                                    {{ $user->shopDescription ? Str::limit($user->shopDescription, 45) : 'Add your Lumban artisan history and background' }}
                                </div>
                            </div>
                        </div>
                        <button type="button" @click="showEditModal = true" style="font-size:10px;font-weight:800;color:#996515;background-color:#FAF5EA;border:1px solid #E6D8BA;padding:3px 9px;border-radius:6px;text-transform:uppercase;letter-spacing:0.04em;cursor:pointer;">
                            Manage
                        </button>
                    </div>

                    {{-- Payment Methods (GCash & Maya) --}}
                    <button type="button"
                            @click="showPaymentModal = true; paymentEditing = false;"
                            style="background-color:#FFFFFF;border:1px solid #ECE3D2;border-radius:16px;padding:14px 16px;display:flex;align-items:center;justify-content:space-between;box-shadow:0 2px 6px rgba(0,0,0,0.02);cursor:pointer;width:100%;text-align:left;transition:all 0.2s;"
                            class="hover:border-[#C49520] hover:bg-[#FDFBF7] group">
                        <div style="display:flex;align-items:center;gap:12px;">
                            <div style="width:38px;height:38px;border-radius:11px;background-color:#FAF5EA;border:1px solid #E6D8BA;display:flex;align-items:center;justify-content:center;color:#B88728;flex-shrink:0;" class="group-hover:scale-105 transition-transform">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                                </svg>
                            </div>
                            <div>
                                <div style="font-size:14px;font-weight:700;color:#1E1915;">Payment Methods</div>
                                <div style="font-size:11px;color:#8C827A;margin-top:1px;">
                                    GCash & Maya accounts & QR codes
                                </div>
                            </div>
                        </div>
                        <div style="display:flex;align-items:center;gap:6px;">
                            <span style="font-size:10px;font-weight:800;color:#996515;background-color:#FAF5EA;border:1px solid #E6D8BA;padding:2px 8px;border-radius:6px;text-transform:uppercase;letter-spacing:0.04em;">Manage</span>
                            <svg width="16" height="16" fill="none" stroke="#8C827A" viewBox="0 0 24 24" stroke-width="2.2" class="group-hover:translate-x-0.5 transition-transform">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                            </svg>
                        </div>
                    </button>

                    {{-- Legal Documents & Certificates --}}
                    <button type="button"
                            @click="showLegalModal = true; legalEditing = false;"
                            style="background-color:#FFFFFF;border:1px solid #ECE3D2;border-radius:16px;padding:14px 16px;display:flex;align-items:center;justify-content:space-between;box-shadow:0 2px 6px rgba(0,0,0,0.02);cursor:pointer;width:100%;text-align:left;transition:all 0.2s;"
                            class="hover:border-[#C49520] hover:bg-[#FDFBF7] group">
                        <div style="display:flex;align-items:center;gap:12px;">
                            <div style="width:38px;height:38px;border-radius:11px;background-color:#FAF5EA;border:1px solid #E6D8BA;display:flex;align-items:center;justify-content:center;color:#B88728;flex-shrink:0;" class="group-hover:scale-105 transition-transform">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                            </div>
                            <div>
                                <div style="font-size:14px;font-weight:700;color:#1E1915;">Legal Documents</div>
                                <div style="font-size:11px;color:#8C827A;margin-top:1px;">Business Permit, BIR & Residency</div>
                            </div>
                        </div>
                        <div style="display:flex;align-items:center;gap:6px;">
                            <span style="font-size:10px;font-weight:800;color:#996515;background-color:#FAF5EA;border:1px solid #E6D8BA;padding:2px 8px;border-radius:6px;text-transform:uppercase;letter-spacing:0.04em;">Manage</span>
                            <svg width="16" height="16" fill="none" stroke="#8C827A" viewBox="0 0 24 24" stroke-width="2.2" class="group-hover:translate-x-0.5 transition-transform">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                            </svg>
                        </div>
                    </button>

                    {{-- Shop Policies (Link) --}}
                    <a href="{{ route('seller.policies.index') }}" 
                       style="background-color:#FFFFFF;border:1px solid #ECE3D2;border-radius:16px;padding:14px 16px;display:flex;align-items:center;justify-content:space-between;box-shadow:0 2px 6px rgba(0,0,0,0.02);cursor:pointer;width:100%;text-decoration:none;transition:all 0.2s;"
                       class="hover:border-[#C49520] hover:bg-[#FDFBF7] group">
                        <div style="display:flex;align-items:center;gap:12px;">
                            <div style="width:38px;height:38px;border-radius:11px;background-color:#FAF5EA;border:1px solid #E6D8BA;display:flex;align-items:center;justify-content:center;color:#B88728;flex-shrink:0;" class="group-hover:scale-105 transition-transform">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                </svg>
                            </div>
                            <span style="font-size:14px;font-weight:700;color:#1E1915;">Shop Policies (Cancellation & Refund)</span>
                        </div>
                        <svg width="16" height="16" fill="none" stroke="#8C827A" viewBox="0 0 24 24" stroke-width="2.2" class="group-hover:translate-x-0.5 transition-transform">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                        </svg>
                    </a>

                    {{-- Orders Ledger Link --}}
                    <a href="{{ route('seller.orders') }}" 
                       style="background-color:#FFFFFF;border:1px solid #ECE3D2;border-radius:16px;padding:14px 16px;display:flex;align-items:center;justify-content:space-between;box-shadow:0 2px 6px rgba(0,0,0,0.02);cursor:pointer;width:100%;text-decoration:none;transition:all 0.2s;"
                       class="hover:border-[#C49520] hover:bg-[#FDFBF7] group">
                        <div style="display:flex;align-items:center;gap:12px;">
                            <div style="width:38px;height:38px;border-radius:11px;background-color:#FAF5EA;border:1px solid #E6D8BA;display:flex;align-items:center;justify-content:center;color:#B88728;flex-shrink:0;" class="group-hover:scale-105 transition-transform">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                                </svg>
                            </div>
                            <span style="font-size:14px;font-weight:700;color:#1E1915;">Orders & Dispatch</span>
                        </div>
                        <svg width="16" height="16" fill="none" stroke="#8C827A" viewBox="0 0 24 24" stroke-width="2.2" class="group-hover:translate-x-0.5 transition-transform">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                        </svg>
                    </a>

                    {{-- Change Password --}}
                    <a href="{{ route('profile.change-password') }}"
                       style="background-color:#FFFFFF;border:1px solid #ECE3D2;border-radius:16px;padding:14px 16px;display:flex;align-items:center;justify-content:space-between;box-shadow:0 2px 6px rgba(0,0,0,0.02);cursor:pointer;width:100%;text-align:left;transition:all 0.2s;text-decoration:none;"
                       class="hover:border-[#C49520] hover:bg-[#FDFBF7] group">
                        <div style="display:flex;align-items:center;gap:12px;">
                            <div style="width:38px;height:38px;border-radius:11px;background-color:#FAF5EA;border:1px solid #E6D8BA;display:flex;align-items:center;justify-content:center;color:#B88728;flex-shrink:0;" class="group-hover:scale-105 transition-transform">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                    <rect x="3" y="11" width="18" height="11" rx="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M7 11V7a5 5 0 0110 0v4" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </div>
                            <span style="font-size:14px;font-weight:700;color:#1E1915;">Change Password</span>
                        </div>
                        <svg width="16" height="16" fill="none" stroke="#8C827A" viewBox="0 0 24 24" stroke-width="2.2" class="group-hover:translate-x-0.5 transition-transform">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                        </svg>
                    </a>
                </div>
            </div>

            {{-- Verified & Trusted LumBarong Account Footer Banner --}}
            <div style="margin-top:18px;padding:14px 18px;border-radius:18px;background:linear-gradient(90deg,#F6F0E4 0%,#F2EADA 50%,#EAE0CD 100%);border:1px solid #E2D6C0;display:flex;align-items:center;justify-content:space-between;gap:12px;position:relative;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,0.03);">
                <div style="display:flex;align-items:center;gap:12px;position:relative;z-index:10;">
                    <div style="width:32px;height:32px;border-radius:50%;border:2px solid #B88728;background-color:#FAF4EA;display:flex;align-items:center;justify-content:center;color:#B88728;flex-shrink:0;box-shadow:0 1px 2px rgba(0,0,0,0.05);">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                        </svg>
                    </div>
                    <div>
                        <h5 style="font-size:13px;font-weight:700;color:#1E1915;margin:0;line-height:1.2;">Verified LumBarong Artisan Shop</h5>
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