<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'LumBarong') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Styles / Scripts -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        body { font-family: 'Inter', sans-serif; background-color: #FAFAFA; }
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
    </style>
</head>
<body class="antialiased text-gray-900">
    <div class="min-h-screen flex flex-col">
        <!-- Navigation Header -->
        <header class="sticky top-0 z-40 bg-white border-b border-gray-100 w-full shadow-sm">
            <div class="flex items-center justify-between px-4 lg:px-12 py-4 w-full max-w-[1440px] mx-auto">
                <!-- Left: Info -->
                <div class="flex items-center gap-4 flex-1 md:flex-none">
                    @guest
                        <a href="{{ route('seller.register') }}" class="text-[10px] font-bold uppercase tracking-[0.2em] text-gray-500 hover:text-black transition-all">START YOUR SHOP NOW</a>
                    @else
                        @if(Auth::user()->role === 'seller')
                            <a href="/seller/dashboard" class="text-[10px] font-bold uppercase tracking-[0.2em] text-black hover:text-gray-600 transition-all flex items-center gap-2">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg>
                                SELLER HUB
                            </a>
                        @endif
                    @endguest
                </div>
                
                <!-- Center: Logo -->
                <div class="flex items-center md:absolute md:left-1/2 md:-translate-x-1/2">
                    <a href="/" class="flex items-center gap-2">
                        <div class="w-8 h-8 bg-black rounded-full text-white flex items-center justify-center font-bold text-lg shadow-md">L</div>
                        <span class="text-xl font-bold tracking-tight text-black">LumBarong</span>
                    </a>
                </div>

                <!-- Right: Actions -->
                <div class="flex items-center gap-3 md:gap-5 flex-1 md:flex-none justify-end">

                    <!-- Notifications -->
                    <div x-data="{ open: false }" class="relative" @mouseenter="open = true" @mouseleave="open = false">
                        <a href="/notifications" class="relative w-11 h-11 flex items-center justify-center rounded-full border border-gray-100 text-gray-800 hover:border-gray-400 bg-white transition-all shadow-sm">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
                            @auth
                                @php $unreadNotifications = \App\Models\Notification::where('userId', Auth::id())->where('isRead', false)->count(); @endphp
                                @if($unreadNotifications > 0)
                                    <span class="absolute -top-1 -right-1 min-w-[18px] h-[18px] px-1 bg-red-500 text-white text-[9px] font-bold rounded-full flex items-center justify-center border-2 border-white">{{ $unreadNotifications }}</span>
                                @endif
                            @endauth
                        </a>

                        <!-- Notifications Dropdown -->
                        <div x-show="open" 
                             x-transition:enter="transition ease-out duration-200"
                             x-transition:enter-start="opacity-0 translate-y-1"
                             x-transition:enter-end="opacity-100 translate-y-0"
                             x-transition:leave="transition ease-in duration-150"
                             x-transition:leave-start="opacity-100 translate-y-0"
                             x-transition:leave-end="opacity-0 translate-y-1"
                             class="absolute right-0 mt-2 w-80 bg-white rounded-2xl shadow-2xl border border-gray-100 z-50 overflow-hidden"
                             style="display: none;">
                            @auth
                                <div class="px-4 py-3 bg-gray-50/80 border-b border-gray-100">
                                    <span class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Recently Received Notifications</span>
                                </div>
                                <div class="max-h-96 overflow-y-auto no-scrollbar">
                                    @php $recentNotifications = \App\Models\Notification::where('userId', Auth::id())->latest('createdAt')->take(5)->get(); @endphp
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

                    <!-- Cart -->
                    <div x-data="{ open: false }" class="relative" @mouseenter="open = true" @mouseleave="open = false">
                        @php $cartCount = count(session('cart', [])); @endphp
                        <a href="/cart" class="relative w-11 h-11 flex items-center justify-center rounded-full border border-gray-100 text-gray-800 hover:border-gray-400 bg-white transition-all shadow-sm">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg>
                            @if($cartCount > 0)
                                <span class="absolute -top-1 -right-1 min-w-[18px] h-[18px] px-1 bg-black text-white text-[9px] font-bold rounded-full flex items-center justify-center border-2 border-white">{{ $cartCount }}</span>
                            @endif
                        </a>

                        <!-- Cart Dropdown -->
                        <div x-show="open" 
                             x-transition:enter="transition ease-out duration-200"
                             x-transition:enter-start="opacity-0 translate-y-1"
                             x-transition:enter-end="opacity-100 translate-y-0"
                             x-transition:leave="transition ease-in duration-150"
                             x-transition:leave-start="opacity-100 translate-y-0"
                             x-transition:leave-end="opacity-0 translate-y-1"
                             class="absolute right-0 mt-2 w-80 bg-white rounded-2xl shadow-2xl border border-gray-100 z-50 overflow-hidden"
                             style="display: none;">
                            <div class="px-4 py-3 bg-gray-50/80 border-b border-gray-100">
                                <span class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Recently Added Products</span>
                            </div>
                            <div class="max-h-80 overflow-y-auto no-scrollbar">
                                @php $cartItems = array_reverse(session('cart', []), true); $recentCart = array_slice($cartItems, 0, 5, true); @endphp
                                @forelse($recentCart as $id => $item)
                                    <div class="flex items-center gap-4 p-4 border-b border-gray-50 last:border-0 hover:bg-gray-50 transition-all">
                                        <div class="w-12 h-12 rounded-lg bg-gray-100 overflow-hidden shrink-0 border border-gray-200">
                                            @php 
                                                $imgUrl = is_array($item['image']) ? ($item['image'][0] ?? null) : $item['image'];
                                                if ($imgUrl && !str_starts_with($imgUrl, 'http')) $imgUrl = asset($imgUrl);
                                            @endphp
                                            <img src="{{ $imgUrl }}" class="w-full h-full object-cover">
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <div class="text-[11px] font-bold text-black truncate">{{ $item['name'] }}</div>
                                            <div class="text-[10px] text-gray-400">Qty: {{ $item['quantity'] }}</div>
                                        </div>
                                        <div class="text-[11px] font-bold">₱{{ number_format($item['price']) }}</div>
                                    </div>
                                @empty
                                    <div class="p-12 text-center">
                                        <p class="text-xs text-gray-400 italic">Your cart is empty</p>
                                    </div>
                                @endforelse
                            </div>
                            @if($cartCount > 0)
                                <div class="p-4 bg-gray-50/50 border-t border-gray-100">
                                    <a href="/cart" class="block w-full py-3 bg-black text-white text-[10px] font-bold uppercase tracking-widest rounded-xl text-center hover:bg-gray-800 transition-all">View My Shopping Cart</a>
                                </div>
                            @endif
                        </div>
                    </div>
                    
                    <!-- Profile Dropdown -->
                    <div x-data="{ open: false }" class="relative" @mouseenter="open = true" @mouseleave="open = false">
                        @auth
                            <button class="w-11 h-11 rounded-full border border-gray-100 flex items-center justify-center overflow-hidden bg-white hover:border-gray-400 transition-all shadow-sm">
                                <span class="font-bold text-gray-700 text-sm">{{ strtoupper(substr(Auth::user()->name, 0, 1)) }}</span>
                            </button>
                            <div x-show="open" 
                                 x-transition:enter="transition ease-out duration-200"
                                 x-transition:enter-start="opacity-0 translate-y-1"
                                 x-transition:enter-end="opacity-100 translate-y-0"
                                 x-transition:leave="transition ease-in duration-150"
                                 x-transition:leave-start="opacity-100 translate-y-0"
                                 x-transition:leave-end="opacity-0 translate-y-1"
                                 class="absolute right-0 mt-2 w-56 bg-white rounded-2xl shadow-2xl border border-gray-100 py-2 z-50 overflow-hidden"
                                 style="display: none;">
                                <div class="px-4 py-3 border-b border-gray-50 bg-gray-50/50">
                                    <div class="text-[10px] font-bold uppercase tracking-widest text-gray-400 mb-0.5">My Account</div>
                                    <div class="text-sm font-bold text-black truncate">{{ Auth::user()->name }}</div>
                                </div>
                                <a href="/profile" class="flex items-center gap-3 px-4 py-3 text-[11px] font-bold text-gray-600 hover:bg-gray-50 hover:text-black transition-all">My Account</a>
                                <a href="/orders/my-orders" class="flex items-center gap-3 px-4 py-3 text-[11px] font-bold text-gray-600 hover:bg-gray-50 hover:text-black transition-all">My Purchase</a>
                                <form action="{{ route('logout') }}" method="POST" class="border-t border-gray-50 mt-1">
                                    @csrf
                                    <button type="submit" class="w-full text-left px-4 py-3 text-[11px] font-bold text-red-500 hover:bg-red-50 transition-all">Logout</button>
                                </form>
                            </div>
                        @else
                            <a href="/login" class="w-11 h-11 rounded-full border border-gray-100 flex items-center justify-center text-gray-800 hover:border-gray-400 bg-white transition-all shadow-sm">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                            </a>
                            <div x-show="open" 
                                 x-transition:enter="transition ease-out duration-200"
                                 x-transition:enter-start="opacity-0 translate-y-1"
                                 x-transition:enter-end="opacity-100 translate-y-0"
                                 x-transition:leave="transition ease-in duration-150"
                                 x-transition:leave-start="opacity-100 translate-y-0"
                                 x-transition:leave-end="opacity-0 translate-y-1"
                                 class="absolute right-0 mt-2 w-48 bg-white rounded-2xl shadow-2xl border border-gray-100 py-2 z-50 overflow-hidden"
                                 style="display: none;">
                                <a href="{{ route('login') }}" class="flex items-center gap-3 px-4 py-3 text-[11px] font-bold text-gray-600 hover:bg-gray-50 hover:text-black transition-all">Login</a>
                                <a href="{{ route('register') }}" class="flex items-center gap-3 px-4 py-3 text-[11px] font-bold text-gray-600 hover:bg-gray-50 hover:text-black transition-all">Sign Up</a>
                            </div>
                        @endauth
                    </div>
                </div>
            </div>
        </header>

        <!-- Breadcrumbs -->
        @yield('breadcrumbs')

        {{-- Global Flash Messages --}}
        @if(session('success') || session('error') || session('status'))
        <div class="w-full max-w-[1440px] mx-auto px-4 lg:px-12 pt-4">
            @if(session('success'))
                <div class="px-5 py-4 bg-green-50 border border-green-100 rounded-2xl text-green-700 text-xs font-bold flex items-center gap-3 mb-2">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    {{ session('success') }}
                </div>
            @endif
            @if(session('error'))
                <div class="px-5 py-4 bg-red-50 border border-red-100 rounded-2xl text-red-700 text-xs font-bold flex items-center gap-3 mb-2">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    {{ session('error') }}
                </div>
            @endif
            @if(session('status'))
                <div class="px-5 py-4 bg-blue-50 border border-blue-100 rounded-2xl text-blue-700 text-xs font-bold flex items-center gap-3 mb-2">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    {{ session('status') }}
                </div>
            @endif
        </div>
        @endif

        <!-- Page Content -->
        <main class="flex-1 w-full max-w-[1440px] mx-auto px-4 py-8 lg:px-12 pb-28 lg:pb-8">
            @yield('content')
        </main>

        {{-- Mobile Bottom Nav --}}
        <nav class="lg:hidden fixed bottom-0 left-0 right-0 z-50 flex items-center justify-around bg-white border-t border-gray-100 shadow-sm h-[64px]">
            <a href="/" class="flex flex-col items-center gap-1 flex-1 {{ request()->is('/') ? 'text-black' : 'text-gray-400' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                <span class="text-[9px] font-bold uppercase tracking-widest">Shop</span>
            </a>
            <a href="/cart" class="flex flex-col items-center gap-1 flex-1 relative {{ request()->is('cart') ? 'text-black' : 'text-gray-400' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg>
                @if(isset($cartCount) && $cartCount > 0)
                    <span class="absolute top-0 right-5 min-w-[14px] h-[14px] px-0.5 bg-black text-white text-[8px] font-bold rounded-full flex items-center justify-center">{{ $cartCount }}</span>
                @endif
                <span class="text-[9px] font-bold uppercase tracking-widest">Cart</span>
            </a>
            <a href="/wishlist" class="flex flex-col items-center gap-1 flex-1 {{ request()->is('wishlist') ? 'text-black' : 'text-gray-400' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path></svg>
                <span class="text-[9px] font-bold uppercase tracking-widest">Wish</span>
            </a>
            @auth
            <a href="/orders/my-orders" class="flex flex-col items-center gap-1 flex-1 {{ request()->is('orders*') ? 'text-black' : 'text-gray-400' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
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

        <!-- Footer -->
        <footer class="bg-white pt-20 pb-10 border-t border-gray-100 mt-20">
            <div class="max-w-[1440px] mx-auto px-4 lg:px-12">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-12 mb-20">
                    <div class="space-y-6">
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 bg-black rounded-full text-white flex items-center justify-center font-bold text-lg shadow-md">L</div>
                            <span class="text-xl font-bold tracking-tight text-black">LumBarong</span>
                        </div>
                        <p class="text-sm text-gray-500 leading-relaxed max-w-sm">
                            We have clothes that suits your style and which you're proud to wear. From women to men.
                        </p>
                    </div>
                    <div>
                        <h4 class="text-[10px] font-bold uppercase tracking-[0.2em] text-black mb-8">Shop</h4>
                        <ul class="space-y-4">
                            <li><a href="/" class="text-sm text-gray-500 hover:text-black transition-colors">All Products</a></li>
                            <li><a href="/" class="text-sm text-gray-500 hover:text-black transition-colors">New Arrivals</a></li>
                        </ul>
                    </div>
                    <div>
                        <h4 class="text-[10px] font-bold uppercase tracking-[0.2em] text-black mb-8">Support</h4>
                        <ul class="space-y-4">
                            <li><a href="/about" class="text-sm text-gray-500 hover:text-black transition-colors">About LumBarong</a></li>
                            <li><a href="/privacy" class="text-sm text-gray-500 hover:text-black transition-colors">Privacy Policy</a></li>
                            <li><a href="/terms" class="text-sm text-gray-500 hover:text-black transition-colors">Terms & Conditions</a></li>
                        </ul>
                    </div>
                </div>
                <div class="pt-10 border-t border-gray-100 flex flex-col md:flex-row items-center justify-between gap-6">
                    <div class="text-[11px] font-medium text-gray-400 tracking-widest">LumBarong © 2024. All Rights Reserved</div>
                </div>
            </div>
        </footer>
    </div>

    <!-- Global Components -->
    <div class="fixed bottom-6 right-6 z-50">
        <button class="w-12 h-12 bg-black text-white rounded-full flex items-center justify-center shadow-lg hover:bg-gray-800 transition-all">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path></svg>
        </button>
    </div>
    <x-confirmation-modal />
    @stack('scripts')
</body>
</html>
