<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="overflow-x-clip">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover, interactive-widget=resizes-content">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'LumBarong') }}</title>

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('images/apple-touch-icon.png') }}">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Styles / Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://unpkg.com/@alpinejs/collapse@3.14.8/dist/cdn.min.js"></script>
    <script defer src="https://unpkg.com/alpinejs@3.14.8/dist/cdn.min.js"></script>

    <style>
        html, body { overflow-x: clip; max-width: 100vw; }
        body { font-family: 'Inter', sans-serif; background-color: #FAFAFA; }
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
        /* Prevent Alpine.js x-cloak elements from flashing before initialization */
        [x-cloak] { display: none !important; }
        /* Mobile chat widget: sit above bottom nav (64px) + toggle button */
        @media (max-width: 1023px) {
            .chat-widget-panel {
                bottom: 8rem !important;
                height: calc(100svh - 9.5rem) !important;
                max-height: calc(100svh - 9.5rem) !important;
            }
        }
    </style>
</head>
<body class="antialiased text-gray-900 max-w-full relative">
    <div class="min-h-screen flex flex-col w-full max-w-full">
        <!-- Navigation Header -->
        <header class="sticky top-0 z-50 bg-white/95 backdrop-blur-md border-b border-gray-100 w-full shadow-xs">
            <div class="flex items-center justify-between px-2.5 sm:px-4 lg:px-12 py-2.5 sm:py-3.5 w-full max-w-7xl mx-auto gap-2 sm:gap-4">
                <!-- Left: Logo & Seller Hub -->
                <div class="flex items-center gap-2 sm:gap-6 shrink-0">
                    <a href="/" class="flex items-center gap-2.5 group">
                        <img src="{{ asset('images/logo-icon.png') }}" alt="LumBarong Logo" class="w-8 h-8 sm:w-9 sm:h-9 object-contain rounded-full shadow-xs group-hover:scale-105 transition-transform duration-200">
                        <span class="text-xl font-extrabold tracking-tight text-gray-900 hidden sm:inline-block">LumBarong</span>
                    </a>

                    @auth
                        @if(Auth::user()->role === 'superadmin')
                            <a href="/superadmin/dashboard" class="flex text-[9px] sm:text-[10px] font-extrabold uppercase tracking-widest text-amber-600 hover:text-amber-800 transition-colors whitespace-nowrap">👑 Governance</a>
                        @elseif(Auth::user()->role === 'admin')
                            <a href="/admin/dashboard" class="flex text-[9px] sm:text-[10px] font-extrabold uppercase tracking-widest text-gray-700 hover:text-black transition-colors whitespace-nowrap">Admin Panel</a>
                        @elseif(Auth::user()->role === 'seller')
                            <a href="/seller/dashboard" class="flex items-center gap-1 text-[9px] sm:text-[10px] font-extrabold uppercase tracking-widest text-[#C0422A] hover:underline whitespace-nowrap">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg>
                                Seller Hub
                            </a>
                        @endif
                    @else
                        <a href="{{ route('seller.register') }}" class="flex text-[9px] sm:text-[10px] font-extrabold uppercase tracking-widest text-[#C0422A] sm:text-gray-400 hover:text-black transition-colors whitespace-nowrap">Sell on LumBarong</a>
                    @endauth
                </div>
                
                <!-- Center: Modern Pill Search Bar (LumBarong theme) -->
                @if(!request()->is('cart*', 'profile*', 'orders*', 'wishlist*', 'checkout*'))
                <div class="flex-1 min-w-0 max-w-lg mx-auto">
                    <form action="/" method="GET" class="ajax-search-form" style="position: relative; width: 100%;">
                        <input type="text" name="search" value="{{ request('search') }}"
                               placeholder="Search Barongs, Sellers..."
                               style="padding-left: 18px; padding-right: 44px;"
                               class="w-full bg-gray-50/90 hover:bg-white focus:bg-white border border-gray-200 hover:border-gray-300 focus:border-[#C0422A] focus:ring-2 focus:ring-[#C0422A]/20 rounded-full py-2 sm:py-2.5 text-xs sm:text-sm font-medium text-gray-800 placeholder-gray-400 outline-none transition-all shadow-2xs focus:shadow-xs truncate">
                        <button type="submit"
                                style="position: absolute; right: 6px; top: 50%; transform: translateY(-50%); width: 30px; height: 30px;"
                                class="flex items-center justify-center rounded-full bg-[#C0422A] hover:bg-black text-white transition-all shadow-2xs active:scale-95 cursor-pointer border-0"
                                title="Search">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                        </button>
                    </form>
                </div>
                @else
                <div class="flex-1"></div>
                @endif

                <!-- Right: Quick Action Icons (Bell, Cart, Avatar) -->
                <div class="flex items-center gap-1.5 sm:gap-3.5 shrink-0">

                    <!-- Notifications -->
                    <div x-data="{ open: false }" class="relative" @mouseenter="open = true" @mouseleave="open = false" @click.away="open = false">
                        <button type="button" @click="open = !open" class="relative w-9 h-9 sm:w-10 sm:h-10 flex items-center justify-center rounded-full text-gray-600 hover:bg-gray-100 hover:text-black transition-all">
                            <svg class="w-4.5 h-4.5 sm:w-5 sm:h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
                            @auth
                                @php $unreadNotifications = \App\Models\Notification::where('userId', Auth::id())->where('targetRole', 'customer')->where('isRead', false)->count(); @endphp
                                @if($unreadNotifications > 0)
                                    <span class="absolute top-1.5 right-1.5 w-2.5 h-2.5 bg-amber-500 rounded-full border-2 border-white"></span>
                                @endif
                            @endauth
                        </button>

                        <!-- Notifications Dropdown -->
                        <div x-show="open" 
                             x-transition:enter="transition ease-out duration-200"
                             x-transition:enter-start="opacity-0 translate-y-1"
                             x-transition:enter-end="opacity-100 translate-y-0"
                             x-transition:leave="transition ease-in duration-150"
                             x-transition:leave-start="opacity-100 translate-y-0"
                             x-transition:leave-end="opacity-0 translate-y-1"
                             class="absolute right-0 mt-2 w-80 bg-white rounded-2xl shadow-2xl border border-gray-100 z-50 overflow-hidden"
                             style="display: none;"
                             x-cloak>
                            @auth
                                <div class="px-4 py-3 bg-gray-50/80 border-b border-gray-100 flex items-center justify-between">
                                    <span class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Notifications</span>
                                    @if($unreadNotifications > 0)
                                        <form action="{{ route('notifications.read-all') }}" method="POST" class="inline">
                                            @csrf
                                            <button type="submit" class="text-[9px] font-bold uppercase tracking-widest text-red-500 hover:text-black transition-colors">Mark all read</button>
                                        </form>
                                    @endif
                                </div>
                                <div class="max-h-96 overflow-y-auto no-scrollbar">
                                    @php $recentNotifications = \App\Models\Notification::where('userId', Auth::id())->where('targetRole', 'customer')->latest('createdAt')->take(5)->get(); @endphp
                                    @forelse($recentNotifications as $notif)
                                        <a href="{{ $notif->link ?? '#' }}" class="flex items-start gap-3 p-4 hover:bg-gray-50 transition-all border-b border-gray-50 last:border-0">
                                            <div class="w-2 h-2 mt-1.5 rounded-full {{ $notif->isRead ? 'bg-gray-200' : 'bg-red-500' }} shrink-0"></div>
                                            <div class="space-y-0.5">
                                                <div class="text-[11px] font-bold text-black">{{ $notif->title }}</div>
                                                <div class="text-[10px] text-gray-500 leading-relaxed">{{ Str::limit($notif->message, 60) }}</div>
                                            </div>
                                        </a>
                                    @empty
                                        <div class="p-8 text-center">
                                            <div class="text-xs text-gray-400 italic">No notifications yet</div>
                                        </div>
                                    @endforelse
                                </div>
                                <a href="/notifications" class="block w-full py-3 text-center text-[10px] font-bold uppercase tracking-widest text-gray-400 hover:text-black hover:bg-gray-50 border-t border-gray-100 transition-all">View All</a>
                            @else
                                <div class="p-10 text-center">
                                    <p class="text-xs text-gray-500 mb-6 font-medium">Log in to view notifications</p>
                                    <div class="grid grid-cols-2 gap-3">
                                        <a href="{{ route('register') }}" class="py-2.5 text-[10px] font-bold uppercase tracking-widest border border-gray-200 rounded-xl hover:bg-gray-50 transition-all">Sign Up</a>
                                        <a href="{{ route('login') }}" class="py-2.5 text-[10px] font-bold uppercase tracking-widest bg-black text-white rounded-xl transition-all shadow-lg shadow-black/10">Login</a>
                                    </div>
                                </div>
                            @endauth
                        </div>
                    </div>

                    <!-- Cart (Desktop/Laptop only) -->
                    <div x-data="{ 
                        open: false, 
                        cartCount: {{ auth()->check() ? count(session('cart', [])) : 0 }},
                        cartItems: {{ json_encode(array_values(session('cart', [])), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) }},
                        init() {
                            @auth
                                try { localStorage.removeItem('lumbarong_pending_intent'); } catch(e) {}
                            @endauth
                            window.addEventListener('cart-updated', (e) => {
                                this.cartCount = e.detail.cartCount;
                                this.cartItems = Object.values(e.detail.cart || {});
                            });
                        }
                    }" class="relative hidden sm:block" @mouseenter="open = true" @mouseleave="open = false">
                        <a href="/cart" class="relative w-9 h-9 sm:w-11 sm:h-11 flex items-center justify-center rounded-full border border-gray-100 text-gray-800 hover:border-gray-400 bg-white transition-all shadow-sm">
                            <svg class="w-4.5 h-4.5 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg>
                            @auth
                                <span x-show="cartCount > 0" x-text="cartCount" class="absolute -top-1 -right-1 min-w-4 h-4 px-1 bg-black text-white text-[8px] font-bold rounded-full flex items-center justify-center border-2 border-white"></span>
                            @endauth
                        </a>

                        <!-- Cart Dropdown (only for logged-in users) -->
                        @auth
                        <div x-show="open" 
                             x-transition:enter="transition ease-out duration-200"
                             x-transition:enter-start="opacity-0 translate-y-1"
                             x-transition:enter-end="opacity-100 translate-y-0"
                             x-transition:leave="transition ease-in duration-150"
                             x-transition:leave-start="opacity-100 translate-y-0"
                             x-transition:leave-end="opacity-0 translate-y-1"
                             class="absolute right-0 mt-2 w-80 bg-white rounded-2xl shadow-2xl border border-gray-100 z-50 overflow-hidden"
                             style="display: none;"
                             x-cloak>
                            <div class="px-4 py-3 bg-gray-50/80 border-b border-gray-100">
                                <span class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Recently Added Products</span>
                            </div>
                            <div class="max-h-80 overflow-y-auto no-scrollbar">
                                <template x-for="item in cartItems.slice(0, 5)" :key="item.id + '_' + (item.size || '') + '_' + (item.variation || '')">
                                    <div class="flex items-center gap-4 p-4 border-b border-gray-50 last:border-0 hover:bg-gray-50 transition-all">
                                        <div class="w-12 h-12 rounded-lg bg-gray-100 overflow-hidden shrink-0 border border-gray-200">
                                            <img :src="window.getAppProductImage ? window.getAppProductImage(item.image) : '/uploads/products/default.jpg'" class="w-full h-full object-cover" x-on:error="$event.target.src='/uploads/products/default.jpg'">
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <div class="text-[11px] font-bold text-black truncate" x-text="item.name"></div>
                                            <div class="text-[10px] text-gray-400" x-text="'Qty: ' + item.quantity"></div>
                                        </div>
                                        <div class="text-[11px] font-bold" x-text="'₱' + Number(item.price).toLocaleString()"></div>
                                    </div>
                                </template>
                                <div x-show="cartItems.length === 0" class="p-12 text-center">
                                    <p class="text-xs text-gray-400 italic">Your cart is empty</p>
                                </div>
                            </div>
                            <div x-show="cartCount > 0" class="p-4 bg-gray-50/50 border-t border-gray-100">
                                <a href="/cart" class="block w-full py-3 bg-black text-white text-[10px] font-bold uppercase tracking-widest rounded-xl text-center hover:bg-gray-800 transition-all">View My Shopping Cart</a>
                            </div>
                        </div>
                        @endauth
                    </div>
                    
                    <!-- Profile Dropdown -->
                    <div x-data="{ open: false }" class="relative" @click.away="open = false">
                        @auth
                            <button type="button" @click="open = !open" class="w-9 h-9 sm:w-11 sm:h-11 rounded-full border border-gray-100 flex items-center justify-center overflow-hidden bg-white hover:border-gray-400 transition-all shadow-sm cursor-pointer">
                                @if(Auth::user()->profile_photo_url)
                                    <img src="{{ Auth::user()->profile_photo_url }}" alt="{{ Auth::user()->username ?? Auth::user()->name }}" class="w-full h-full object-cover rounded-full">
                                @else
                                    <span class="font-bold text-gray-700 text-xs sm:text-sm">{{ strtoupper(substr(Auth::user()->username ?: Auth::user()->name, 0, 1)) }}</span>
                                @endif
                            </button>
                            <div x-show="open" 
                                 x-transition:enter="transition ease-out duration-200"
                                 x-transition:enter-start="opacity-0 translate-y-1"
                                 x-transition:enter-end="opacity-100 translate-y-0"
                                 x-transition:leave="transition ease-in duration-150"
                                 x-transition:leave-start="opacity-100 translate-y-0"
                                 x-transition:leave-end="opacity-0 translate-y-1"
                                 class="absolute right-0 mt-2 w-56 bg-white rounded-2xl shadow-2xl border border-gray-100 py-2 z-50 overflow-hidden"
                                 style="display: none;"
                                 x-cloak>
                                <div class="px-4 py-3 border-b border-gray-50 bg-gray-50/50 flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-full overflow-hidden shrink-0 border border-gray-200 flex items-center justify-center bg-stone-100">
                                        @if(Auth::user()->profile_photo_url)
                                            <img src="{{ Auth::user()->profile_photo_url }}" alt="{{ Auth::user()->username ?? Auth::user()->name }}" class="w-full h-full object-cover">
                                        @else
                                            <span class="font-bold text-gray-700 text-xs">{{ strtoupper(substr(Auth::user()->username ?: Auth::user()->name, 0, 1)) }}</span>
                                        @endif
                                    </div>
                                    <div class="min-w-0">
                                        <div class="text-[10px] font-bold uppercase tracking-widest text-gray-400 mb-0.5">My Account</div>
                                        <div class="text-sm font-bold text-black truncate">{{ Auth::user()->name }}</div>
                                    </div>
                                </div>
                                @if(Auth::user()->role === 'superadmin')
                                    <a href="/superadmin/dashboard" class="flex items-center gap-3 px-4 py-3 text-[11px] font-bold text-amber-600 hover:bg-amber-50 transition-all">👑 Super Admin Governance</a>
                                @elseif(Auth::user()->role === 'admin')
                                    <a href="/admin/dashboard" class="flex items-center gap-3 px-4 py-3 text-[11px] font-bold text-gray-800 hover:bg-gray-50 transition-all">Admin Panel</a>
                                @elseif(Auth::user()->role === 'seller')
                                    <a href="/seller/dashboard" class="flex items-center gap-3 px-4 py-3 text-[11px] font-bold text-[#C0422A] hover:bg-[#C0422A]/10 transition-all">🏪 Seller Dashboard</a>
                                    <a href="/seller/orders" class="flex items-center gap-3 px-4 py-3 text-[11px] font-bold text-gray-700 hover:bg-gray-50 transition-all">📦 My Sales Orders</a>
                                @endif
                                <a href="/profile" class="group flex items-center gap-3 px-4 py-3 text-[11px] font-bold text-gray-600 hover:bg-gray-50 hover:text-black transition-all">
                                    <svg class="w-4 h-4 text-gray-400 group-hover:text-black transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                    </svg>
                                    <span>My Account</span>
                                </a>
                                @if(Auth::user()->role !== 'seller')
                                    <a href="/orders/my-orders" class="group flex items-center gap-3 px-4 py-3 text-[11px] font-bold text-gray-600 hover:bg-gray-50 hover:text-black transition-all">
                                        <svg class="w-4 h-4 text-gray-400 group-hover:text-black transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                                        </svg>
                                        <span>My Purchase</span>
                                    </a>
                                    <a href="/wishlist" class="group flex items-center gap-3 px-4 py-3 text-[11px] font-bold text-gray-600 hover:bg-gray-50 hover:text-black transition-all">
                                        <svg class="w-4 h-4 text-gray-400 group-hover:text-black transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                                        </svg>
                                        <span>My Wishlist</span>
                                    </a>
                                @endif
                                <form x-ref="logoutForm" action="{{ route('logout') }}" method="POST" class="border-t border-gray-50 mt-1">
                                    @csrf
                                    <button type="button" 
                                            @click="$dispatch('open-confirmation', { 
                                                title: 'Logout', 
                                                message: 'Are you sure you want to logout?', 
                                                confirmText: 'Logout', 
                                                type: 'danger', 
                                                onConfirm: () => $refs.logoutForm.submit() 
                                            })" 
                                            class="w-full text-left px-4 py-3 text-[11px] font-bold text-red-500 hover:bg-red-50 transition-all flex items-center gap-2">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                                        Logout
                                    </button>
                                </form>
                            </div>
                        @else
                            <button type="button" @click="open = !open" class="w-9 h-9 sm:w-11 sm:h-11 rounded-full border border-gray-100 flex items-center justify-center text-gray-800 hover:border-gray-400 bg-white transition-all shadow-sm cursor-pointer" aria-label="User Account Menu">
                                <svg class="w-4.5 h-4.5 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                            </button>
                            <div x-show="open" 
                                 x-transition:enter="transition ease-out duration-200"
                                 x-transition:enter-start="opacity-0 translate-y-1"
                                 x-transition:enter-end="opacity-100 translate-y-0"
                                 x-transition:leave="transition ease-in duration-150"
                                 x-transition:leave-start="opacity-100 translate-y-0"
                                 x-transition:leave-end="opacity-0 translate-y-1"
                                 class="absolute right-0 mt-2 w-48 bg-white rounded-2xl shadow-2xl border border-gray-100 py-2 z-50 overflow-hidden"
                                 style="display: none;"
                                 x-cloak>
                                <a href="{{ route('login') }}" class="group flex items-center gap-3 px-4 py-3 text-[11px] font-bold text-gray-600 hover:bg-gray-50 hover:text-black transition-all">
                                    <svg class="w-4 h-4 text-gray-400 group-hover:text-black transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path>
                                    </svg>
                                    <span>Login</span>
                                </a>
                                <a href="{{ route('register') }}" class="group flex items-center gap-3 px-4 py-3 text-[11px] font-bold text-gray-600 hover:bg-gray-50 hover:text-black transition-all">
                                    <svg class="w-4 h-4 text-gray-400 group-hover:text-black transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
                                    </svg>
                                    <span>Sign Up</span>
                                </a>
                                <a href="{{ route('seller.register') }}" class="group flex items-center gap-3 px-4 py-3 text-[11px] font-bold text-[#C0422A] hover:bg-[#C0422A]/10 transition-all border-t border-gray-100">
                                    <svg class="w-4 h-4 text-[#C0422A]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                    </svg>
                                    <span>Sell on LumBarong</span>
                                </a>
                            </div>
                        @endauth
                    </div>
                </div>
            </div>
        </header>

        <!-- Breadcrumbs -->
        @yield('breadcrumbs')

        {{-- Global Flash Messages (Floating Toasts) --}}
        @if(session('success') || session('error') || session('status'))
        <div 
            x-data="{ 
                show: true, 
                init() { 
                    setTimeout(() => this.show = false, 2500) 
                } 
            }"
            x-show="show"
            x-transition:enter="transition ease-out duration-300 transform"
            x-transition:enter-start="opacity-0 -translate-y-4 -translate-x-1/2"
            x-transition:enter-end="opacity-100 translate-y-0 -translate-x-1/2"
            x-transition:leave="transition ease-in duration-200 transform"
            x-transition:leave-start="opacity-100 translate-y-0 -translate-x-1/2"
            x-transition:leave-end="opacity-0 -translate-y-4 -translate-x-1/2"
            class="fixed top-6 left-1/2 -translate-x-1/2 z-9999 w-[calc(100%-2rem)] max-w-sm bg-white/95 backdrop-blur-md rounded-2xl shadow-2xl border border-gray-100 p-4 flex items-start gap-3.5"
            style="display: none;"
            x-cloak
        >
            @if(session('success'))
                <div class="w-8 h-8 rounded-full bg-green-50 flex items-center justify-center text-green-600 shrink-0 shadow-sm border border-green-100">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                </div>
                <div class="grow pt-0.5">
                    <h4 class="text-xs font-black text-black uppercase tracking-wider">Success</h4>
                    <p class="text-xs text-gray-500 font-medium mt-0.5 leading-relaxed">{{ session('success') }}</p>
                </div>
            @endif

            @if(session('error'))
                <div class="w-8 h-8 rounded-full bg-red-50 flex items-center justify-center text-red-600 shrink-0 shadow-sm border border-red-100">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path></svg>
                </div>
                <div class="grow pt-0.5">
                    <h4 class="text-xs font-black text-black uppercase tracking-wider">Error</h4>
                    <p class="text-xs text-gray-500 font-medium mt-0.5 leading-relaxed">{{ session('error') }}</p>
                </div>
            @endif

            @if(session('status'))
                <div class="w-8 h-8 rounded-full bg-blue-50 flex items-center justify-center text-blue-600 shrink-0 shadow-sm border border-blue-100">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
                <div class="grow pt-0.5">
                    <h4 class="text-xs font-black text-black uppercase tracking-wider">Status Update</h4>
                    <p class="text-xs text-gray-500 font-medium mt-0.5 leading-relaxed">{{ session('status') }}</p>
                </div>
            @endif

            <button @click="show = false" class="text-gray-300 hover:text-gray-500 transition-colors shrink-0">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>
        @endif

        <!-- Page Content -->
        <main class="flex-1 w-full max-w-360 mx-auto px-4 pt-2 sm:pt-4 lg:pt-6 lg:px-12 {{ request()->is('checkout*') ? 'pb-0 lg:pb-6' : 'pb-28 lg:pb-8' }}">
            @yield('content')
        </main>

        {{-- Mobile Bottom Nav --}}
        @if(!request()->is('checkout*'))
        <nav class="lg:hidden fixed bottom-0 left-0 right-0 z-50 flex items-center justify-around bg-white border-t border-gray-100 shadow-sm h-16">
            <a href="/" class="flex flex-col items-center gap-1 flex-1 {{ request()->is('/') ? 'text-black' : 'text-gray-400' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                <span class="text-[9px] font-bold uppercase tracking-widest">Shop</span>
            </a>
            <a href="/cart" class="flex flex-col items-center gap-1 flex-1 relative {{ request()->is('cart') ? 'text-black' : 'text-gray-400' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg>
                @auth
                    @if(isset($cartCount) && $cartCount > 0)
                        <span class="absolute top-0 right-5 min-w-3.5 h-3.5 px-0.5 bg-black text-white text-[8px] font-bold rounded-full flex items-center justify-center">{{ $cartCount }}</span>
                    @endif
                @endauth
                <span class="text-[9px] font-bold uppercase tracking-widest">Cart</span>
            </a>

            @auth
            <a href="/orders/my-orders" class="flex flex-col items-center gap-1 flex-1 {{ request()->is('orders*') ? 'text-black' : 'text-gray-400' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 012-2h2a2 2 0 012-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                <span class="text-[9px] font-bold uppercase tracking-widest">Orders</span>
            </a>
            <a href="/profile" class="flex flex-col items-center gap-1 flex-1 {{ request()->is('profile') ? 'text-black' : 'text-gray-400' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                <span class="text-[9px] font-bold uppercase tracking-widest">Profile</span>
            </a>
            @else
            <a href="/login" class="flex flex-col items-center gap-1 flex-1 text-gray-400">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                <span class="text-[9px] font-bold uppercase tracking-widest">Login</span>
            </a>
            @endauth
        </nav>
        @endif

        <!-- Footer -->
        @if(!request()->is('cart*') && !request()->is('checkout*'))
        <footer class="hidden lg:block bg-white pt-12 pb-12 border-t border-gray-100 mt-8 sm:mt-12">
            <div class="max-w-360 mx-auto px-4 lg:px-12">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-12">
                    <div class="space-y-4">
                        <div class="flex items-center gap-2.5">
                            <img src="{{ asset('images/logo-icon.png') }}" alt="LumBarong Logo" class="w-8 h-8 object-contain rounded-full shadow-sm">
                            <span class="text-xl font-bold tracking-tight text-black">LumBarong</span>
                        </div>
                        <p class="text-sm text-gray-500 leading-relaxed max-w-sm">
                            Preserving Filipino heritage with modern elegance. Authentic handcrafted Barong Tagalog direct from the artisans of Lumban, Laguna.
                        </p>
                        <div class="text-[11px] font-medium text-gray-400 tracking-widest pt-2">
                            LumBarong © {{ date('Y') }}. All Rights Reserved
                        </div>
                    </div>
                    <div>
                        <h4 class="text-[10px] font-bold uppercase tracking-[0.2em] text-black mb-6">Shop</h4>
                        <ul class="space-y-3">
                            <li><a href="/#catalogue-section" class="text-sm text-gray-500 hover:text-black transition-colors">All Products</a></li>
                            <li><a href="/?sort=newest#catalogue-section" class="text-sm text-gray-500 hover:text-black transition-colors">New Arrivals</a></li>
                        </ul>
                    </div>
                    <div>
                        <h4 class="text-[10px] font-bold uppercase tracking-[0.2em] text-black mb-6">Support</h4>
                        <ul class="space-y-3">
                            <li><button type="button" onclick="window.dispatchEvent(new CustomEvent('open-page-modal', { detail: { tab: 'about' } }))" class="text-sm text-gray-500 hover:text-black transition-colors cursor-pointer text-left">About LumBarong</button></li>
                            <li><button type="button" onclick="window.dispatchEvent(new CustomEvent('open-page-modal', { detail: { tab: 'privacy' } }))" class="text-sm text-gray-500 hover:text-black transition-colors cursor-pointer text-left">Privacy Policy</button></li>
                            <li><button type="button" onclick="window.dispatchEvent(new CustomEvent('open-page-modal', { detail: { tab: 'terms' } }))" class="text-sm text-gray-500 hover:text-black transition-colors cursor-pointer text-left">Terms & Conditions</button></li>
                        </ul>
                    </div>
                </div>
            </div>
        </footer>
        @endif
    </div>

    <!-- Global Components -->
    <x-pages-modal />
    <x-auth-gate-modal />
    @if(!request()->is('checkout*'))
    <x-chat-widget />
    @endif
    <x-report-modal />

    <!-- Global dynamic toast component -->
    <div 
        x-data
        x-show="$store.toast.show"
        x-transition:enter="transition ease-out duration-300 transform"
        x-transition:enter-start="opacity-0 -translate-y-4 -translate-x-1/2"
        x-transition:enter-end="opacity-100 translate-y-0 -translate-x-1/2"
        x-transition:leave="transition ease-in duration-200 transform"
        x-transition:leave-start="opacity-100 translate-y-0 -translate-x-1/2"
        x-transition:leave-end="opacity-0 -translate-y-4 -translate-x-1/2"
        class="fixed top-6 left-1/2 -translate-x-1/2 z-9999 w-[calc(100%-2rem)] max-w-sm bg-white/95 backdrop-blur-md rounded-2xl shadow-2xl border border-gray-100 p-4 flex items-start gap-3.5"
        style="display: none;"
        x-cloak
    >
        <!-- Success Icon -->
        <div x-show="$store.toast.type === 'success'" class="w-8 h-8 rounded-full bg-green-50 flex items-center justify-center text-green-600 shrink-0 shadow-sm border border-green-100">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
        </div>
        <!-- Error Icon -->
        <div x-show="$store.toast.type === 'error'" class="w-8 h-8 rounded-full bg-red-50 flex items-center justify-center text-red-600 shrink-0 shadow-sm border border-red-100">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path></svg>
        </div>
        <!-- Status Icon -->
        <div x-show="$store.toast.type === 'status'" class="w-8 h-8 rounded-full bg-blue-50 flex items-center justify-center text-blue-600 shrink-0 shadow-sm border border-blue-100">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
        </div>
        <div class="grow pt-0.5">
            <h4 class="text-xs font-black text-black uppercase tracking-wider" x-text="$store.toast.type === 'success' ? 'Success' : ($store.toast.type === 'error' ? 'Error' : 'Status Update')"></h4>
            <p class="text-xs text-gray-500 font-medium mt-0.5 leading-relaxed" x-text="$store.toast.message"></p>
        </div>
        <button @click="$store.toast.show = false" class="text-gray-300 hover:text-gray-500 transition-colors shrink-0">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
        </button>
    </div>

    <script>
        function getAppProductImage(raw) {
            if (!raw) return '/uploads/products/default.jpg';
            if (Array.isArray(raw)) {
                raw = raw[0] ?? '';
            }
            if (typeof raw === 'string') {
                const trimmed = raw.trim();
                if (trimmed.startsWith('[') || trimmed.startsWith('{')) {
                    try {
                        const parsed = JSON.parse(trimmed);
                        raw = Array.isArray(parsed) ? (parsed[0] ?? '') : (parsed.url || parsed.image || parsed);
                    } catch(e) {}
                }
            }
            if (!raw || typeof raw !== 'string') return '/uploads/products/default.jpg';
            raw = raw.trim();
            if (!raw || raw === 'Array' || raw === '[]' || raw === '[') return '/uploads/products/default.jpg';
            if (raw.startsWith('http://') || raw.startsWith('https://')) return raw;
            if (raw.startsWith('/')) return raw;
            if (raw.startsWith('products/')) return '/storage/' + raw;
            if (raw.startsWith('storage/')) return '/' + raw;
            if (raw.startsWith('uploads/')) return '/' + raw;
            return '/uploads/products/' + raw;
        }
        window.getAppProductImage = getAppProductImage;

        document.addEventListener('alpine:init', () => {
            Alpine.store('toast', {
                show: false,
                message: '',
                type: 'success',
                trigger(msg, type = 'success') {
                    this.message = msg;
                    this.type = type;
                    this.show = true;
                    setTimeout(() => {
                        this.show = false;
                    }, 4000);
                }
            });
        });

        @auth
        (function() {
            let isChecking = false;
            async function verifyActiveSession() {
                if (isChecking) return;
                isChecking = true;
                try {
                    const res = await fetch('{{ route("auth.session-heartbeat") }}', {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        }
                    });
                    if (res.status === 401) {
                        const data = await res.json().catch(() => ({}));
                        if (data.status === 'session_terminated') {
                            window.location.href = '{{ route("login") }}';
                        }
                    }
                } catch(e) {}
                finally {
                    isChecking = false;
                }
            }

            document.addEventListener('visibilitychange', function() {
                if (document.visibilityState === 'visible') {
                    verifyActiveSession();
                }
            });
            window.addEventListener('focus', verifyActiveSession);
            setInterval(verifyActiveSession, 45000);
        })();
        @endauth
    </script>

    <x-confirmation-modal />
    <x-modal-scroll-lock />
    @stack('scripts')
</body>
</html>
