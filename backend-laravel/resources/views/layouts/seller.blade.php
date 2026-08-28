<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Seller Dashboard' }} | LumBarong</title>

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('images/apple-touch-icon.png') }}">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script>
        tailwind = {
            config: {
                theme: {
                    extend: {
                        aspectRatio: {
                            '4/5': '4 / 5',
                            '3/4': '3 / 4',
                            '2/1': '2 / 1',
                        },
                        zIndex: {
                            '9999': '9999',
                        }
                    }
                }
            }
        }
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Playfair+Display:wght@700;800&display=swap" rel="stylesheet">
    <script defer src="https://unpkg.com/@alpinejs/collapse@3.x.x/dist/cdn.min.js"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        [x-cloak] { display: none !important; }
        :root {
            --rust: #C0422A;
            --cream: #F8F7F4;
            --charcoal: #2A2A2A;
            --muted: #8E8E8E;
            --border: #E5E5E5;
        }
        body { font-family: 'Inter', sans-serif; background-color: var(--cream); }
        .font-serif { font-family: 'Playfair Display', serif; }
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
    </style>
</head>
<body class="antialiased text-(--charcoal)">
    <div x-data="{ isMobileMenuOpen: false }" class="flex h-screen overflow-hidden">
        
        <!-- Desktop Sidebar -->
        <aside class="hidden lg:flex flex-col w-70 h-full bg-white border-r border-(--border) overflow-hidden">
            <div class="p-8 flex flex-col h-full">
                <div class="mb-10 shrink-0">
                    <a href="/seller/dashboard" class="flex items-center gap-3 group">
                        <img src="{{ asset('images/logo-icon.png') }}" alt="LumBarong Logo" class="w-9 h-9 object-contain rounded-full shadow-xs group-hover:scale-105 transition-transform">
                        <div>
                            <span class="font-serif text-lg font-bold text-(--charcoal) tracking-tight">LUMBARONG</span>
                            <div class="flex items-center gap-1.5 px-0.5 text-(--rust) font-bold tracking-widest text-[9px]">
                                <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg>
                                SELLER HUB
                            </div>
                        </div>
                    </a>
                </div>

                <nav class="flex-1 space-y-10 overflow-y-auto no-scrollbar">
                    <div class="space-y-4">
                        <div class="text-[10px] font-bold text-(--muted) opacity-60 tracking-widest uppercase px-3">MY SHOP</div>
                        <div class="space-y-1.5">
                                @php
                                    $sellerAuthId = Auth::id();
                                    $unreadMsgCount = \App\Models\Message::where('receiverId', $sellerAuthId)->where('read', false)->count();
                                    $pendingOrdersCount = \App\Models\Order::where('sellerId', $sellerAuthId)
                                        ->whereIn('status', ['Pending', 'pending', 'To Ship', 'to_ship', 'processing'])
                                        ->count();
                                    $attentionProductsCount = \App\Models\Product::where('sellerId', $sellerAuthId)
                                        ->where(function($q) {
                                            $q->where('stock', '<=', 5)->orWhere('status', 'pending');
                                        })
                                        ->count();

                                    $menu = [
                                        ['label' => 'Dashboard', 'path' => 'seller/dashboard', 'badge' => 0, 'icon' => '<path d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>'],
                                        ['label' => 'Analytics', 'path' => 'seller/analytics', 'badge' => 0, 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>'],
                                        ['label' => 'Products', 'path' => 'seller/products', 'badge' => $attentionProductsCount, 'icon' => '<path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 11m8 4V4"></path>'],
                                        ['label' => 'Customers', 'path' => 'seller/customers', 'badge' => 0, 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>'],
                                        ['label' => 'Messages', 'path' => 'seller/messages', 'badge' => $unreadMsgCount, 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>'],
                                        ['label' => 'Pay Commission', 'path' => 'seller/commission', 'badge' => 0, 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>'],
                                        ['label' => 'Shop Policies', 'path' => 'seller/policies', 'badge' => 0, 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>'],
                                        ['label' => 'Profile', 'path' => 'seller/profile', 'badge' => 0, 'icon' => '<path d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>'],
                                    ];
                                @endphp
                            @foreach($menu as $item)
                                <a href="/{{ $item['path'] }}" class="flex items-center justify-between px-4 py-3.5 rounded-xl transition-all duration-300 group tracking-wide text-sm font-medium {{ request()->is($item['path'] . '*') ? 'bg-[rgba(192,66,42,0.08)] text-(--rust) border-l-4 border-(--rust)' : 'text-(--charcoal) hover:bg-(--cream) hover:text-(--rust)' }}">
                                    <div class="flex items-center gap-3">
                                        <svg class="w-5 h-5 {{ request()->is($item['path'] . '*') ? 'text-(--rust)' : 'text-(--muted) group-hover:text-(--rust)' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">{!! $item['icon'] !!}</svg>
                                        <span>{{ $item['label'] }}</span>
                                    </div>
                                    @if(($item['badge'] ?? 0) > 0)
                                        <span class="px-2 py-0.5 bg-[#C0420A] text-white text-[10px] font-black rounded-full shadow-xs">{{ $item['badge'] }}</span>
                                    @endif
                                </a>
                            @endforeach
                        </div>
                    </div>

                    <div class="space-y-4">
                        <div class="text-[10px] font-bold text-(--muted) opacity-60 tracking-widest uppercase px-3">SALES</div>
                        <div class="space-y-1.5">
                            <a href="/seller/orders" class="flex items-center justify-between px-4 py-3.5 rounded-xl transition-all duration-300 group tracking-wide text-sm font-medium {{ request()->is('seller/orders*') ? 'bg-[rgba(192,66,42,0.08)] text-(--rust) border-l-4 border-(--rust)' : 'text-(--charcoal) hover:bg-(--cream) hover:text-(--rust)' }}">
                                <div class="flex items-center gap-3">
                                    <svg class="w-5 h-5 {{ request()->is('seller/orders*') ? 'text-(--rust)' : 'text-(--muted) group-hover:text-(--rust)' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg>
                                    <span>My Orders</span>
                                </div>
                                @if($pendingOrdersCount > 0)
                                    <span class="px-2 py-0.5 bg-[#C0420A] text-white text-[10px] font-black rounded-full shadow-xs">{{ $pendingOrdersCount }}</span>
                                @endif
                            </a>
                        </div>
                    </div>
                </nav>

                <div class="mt-10 pt-8 border-t border-(--border) shrink-0">
                    <form x-ref="logoutForm" action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="button" 
                                @click="$dispatch('open-confirmation', { 
                                    title: 'Logout', 
                                    message: 'Are you sure you want to logout?', 
                                    confirmText: 'Logout', 
                                    type: 'danger', 
                                    onConfirm: () => $refs.logoutForm.submit() 
                                    })" 
                                class="flex items-center gap-3 w-full px-4 py-3.5 bg-red-50 text-red-600 rounded-xl hover:bg-red-100 transition-all font-bold text-xs tracking-widest uppercase">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                            Logout
                        </button>
                    </form>
                </div>
            </div>
        </aside>

        <!-- Mobile Drawer Menu -->
        <div x-show="isMobileMenuOpen" 
             x-cloak 
             class="fixed inset-0 z-50 lg:hidden flex"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0">
            
            <div @click="isMobileMenuOpen = false" class="fixed inset-0 bg-black/60 backdrop-blur-xs"></div>

            <div class="relative w-4/5 max-w-xs bg-white h-full flex flex-col justify-between p-6 shadow-2xl z-10 overflow-y-auto no-scrollbar"
                 x-transition:enter="transition ease-out duration-300 transform"
                 x-transition:enter-start="-translate-x-full"
                 x-transition:enter-end="translate-x-0"
                 x-transition:leave="transition ease-in duration-200 transform"
                 x-transition:leave-start="translate-x-0"
                 x-transition:leave-end="-translate-x-full">
                
                <div>
                    <!-- Header inside Drawer -->
                    <div class="flex items-center justify-between pb-6 border-b border-gray-100">
                        <div class="flex items-center gap-2.5">
                            <img src="{{ asset('images/logo-icon.png') }}" alt="LumBarong" class="w-8 h-8 object-contain rounded-full shadow-xs">
                            <div>
                                <span class="font-serif font-black tracking-wider text-base uppercase text-black">LumBarong</span>
                                <span class="block text-[8px] font-black tracking-widest uppercase text-[#C0422A]">Seller Portal</span>
                            </div>
                        </div>
                        <button @click="isMobileMenuOpen = false" class="w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center text-gray-500 hover:bg-gray-200 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>
                    </div>

                    <!-- User Profile Snapshot in Drawer -->
                    <div class="py-4 my-4 px-4 bg-gray-50 rounded-2xl flex items-center gap-3 border border-gray-100">
                        <div class="w-10 h-10 rounded-xl bg-[#2A2A2A] text-white flex items-center justify-center font-bold overflow-hidden shrink-0">
                            @if(Auth::user()->profile_photo_url)
                                <img src="{{ Auth::user()->profile_photo_url }}" class="w-full h-full object-cover">
                            @else
                                {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                            @endif
                        </div>
                        <div class="min-w-0">
                            <div class="text-xs font-bold text-black truncate">{{ Auth::user()->name }}</div>
                            <div class="text-[9px] font-bold uppercase tracking-wider text-[#C0422A] mt-0.5">
                                {{ Auth::user()->isPremiumActive() ? '👑 Premium Seller' : 'Verified Seller' }}
                            </div>
                        </div>
                    </div>

                    <!-- Drawer Nav Items -->
                    <nav class="space-y-1">
                        @php
                            $drawerMenu = [
                                ['label' => 'Dashboard', 'path' => 'seller/dashboard', 'badge' => 0, 'icon' => '<path d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>'],
                                ['label' => 'Analytics', 'path' => 'seller/analytics', 'badge' => 0, 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>'],
                                ['label' => 'Products', 'path' => 'seller/products', 'badge' => $attentionProductsCount, 'icon' => '<path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 11m8 4V4"></path>'],
                                ['label' => 'Customers', 'path' => 'seller/customers', 'badge' => 0, 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>'],
                                ['label' => 'Orders', 'path' => 'seller/orders', 'badge' => $pendingOrdersCount, 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>'],
                                ['label' => 'Messages', 'path' => 'seller/messages', 'badge' => $unreadMsgCount, 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>'],
                                ['label' => 'Pay Commission', 'path' => 'seller/commission', 'badge' => 0, 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>'],
                                ['label' => 'Shop Policies', 'path' => 'seller/policies', 'badge' => 0, 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>'],
                                ['label' => 'Profile', 'path' => 'seller/profile', 'badge' => 0, 'icon' => '<path d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>'],
                            ];
                        @endphp
                        @foreach($drawerMenu as $item)
                            <a href="/{{ $item['path'] }}" class="flex items-center justify-between px-4 py-3 rounded-xl text-xs font-bold transition-all {{ request()->is($item['path'] . '*') ? 'bg-[#C0422A]/10 text-[#C0422A]' : 'text-gray-700 hover:bg-gray-50' }}">
                                <div class="flex items-center gap-3">
                                    <svg class="w-4 h-4 {{ request()->is($item['path'] . '*') ? 'text-[#C0422A]' : 'text-gray-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">{!! $item['icon'] !!}</svg>
                                    <span>{{ $item['label'] }}</span>
                                </div>
                                @if(($item['badge'] ?? 0) > 0)
                                    <span class="px-2 py-0.5 bg-[#C0420A] text-white text-[9px] font-black rounded-full shadow-xs">{{ $item['badge'] }}</span>
                                @endif
                            </a>
                        @endforeach
                    </nav>
                </div>

                <div class="pt-6 border-t border-gray-100">
                    <form x-ref="mobileLogoutForm" action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="button" 
                                @click="$dispatch('open-confirmation', { 
                                    title: 'Logout', 
                                    message: 'Are you sure you want to logout?', 
                                    confirmText: 'Logout', 
                                    type: 'danger', 
                                    onConfirm: () => $refs.mobileLogoutForm.submit() 
                                })" 
                                class="flex items-center gap-3 w-full px-4 py-3 bg-red-50 text-red-600 rounded-xl font-bold text-xs tracking-widest uppercase hover:bg-red-100 transition-all">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                            Logout
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Main Content Area -->
        <div class="flex-1 flex flex-col h-full relative overflow-hidden">
            <!-- Top Header -->
            <header class="sticky top-0 z-40 bg-white border-b border-(--border) h-16 lg:h-18 flex items-center shrink-0">
                <div class="w-full flex items-center justify-between px-4 lg:px-10">
                    <div class="flex items-center gap-2 lg:hidden">
                        <a href="/seller/dashboard" class="flex items-center gap-2">
                            <img src="{{ asset('images/logo-icon.png') }}" alt="LumBarong" class="w-7 h-7 object-contain rounded-full shadow-xs">
                            <span class="font-serif text-base font-bold text-(--charcoal) tracking-tight">LUMBARONG</span>
                        </a>
                    </div>
                    <div class="hidden lg:flex flex-1"></div>
                    <div class="flex items-center gap-2 lg:gap-4">
                        <!-- Seller Notifications -->
                        <div x-data="{ open: false }" class="relative" @click.away="open = false">
                            @php
                                $unreadCount = \App\Models\Notification::where('userId', Auth::id())
                                    ->where('targetRole', 'seller')
                                    ->where('isRead', false)
                                    ->count();
                                $recentNotifications = \App\Models\Notification::where('userId', Auth::id())
                                    ->where('targetRole', 'seller')
                                    ->orderBy('createdAt', 'desc')
                                    ->limit(5)
                                    ->get();
                            @endphp
                            <button @click="open = !open" class="relative w-9 h-9 sm:w-10 sm:h-10 flex items-center justify-center rounded-xl bg-(--cream) text-(--muted) hover:text-(--rust) transition-all border border-(--border)">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                                </svg>
                                @if($unreadCount > 0)
                                    <span class="absolute -top-1 -right-1 min-w-4.5 h-4.5 px-1 bg-red-500 text-white text-[9px] font-bold rounded-full flex items-center justify-center border-2 border-white">{{ $unreadCount }}</span>
                                @endif
                            </button>

                            <!-- Dropdown panel -->
                            <div x-show="open" 
                                 x-transition:enter="transition ease-out duration-200"
                                 x-transition:enter-start="opacity-0 translate-y-1"
                                 x-transition:enter-end="opacity-100 translate-y-0"
                                 x-transition:leave="transition ease-in duration-150"
                                 x-transition:leave-start="opacity-100 translate-y-0"
                                 x-transition:leave-end="opacity-0 translate-y-1"
                                 class="absolute right-0 mt-2 w-72 sm:w-80 bg-white rounded-2xl shadow-2xl border border-gray-100 z-50 overflow-hidden"
                                 style="display: none;"
                                 x-cloak>
                                <div class="px-4 py-3 bg-gray-50/80 border-b border-gray-100 flex items-center justify-between">
                                    <span class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Seller Notifications</span>
                                    @if($unreadCount > 0)
                                        <form action="{{ route('seller.notifications.read-all') }}" method="POST" class="inline">
                                            @csrf
                                            <button type="submit" class="text-[9px] font-bold uppercase tracking-widest text-[#C0422A] hover:text-black transition-colors">Mark all read</button>
                                        </form>
                                    @endif
                                </div>
                                <div class="max-h-96 overflow-y-auto no-scrollbar">
                                    @forelse($recentNotifications as $notif)
                                        @if(str_contains(strtolower($notif->title), 'integrity') || str_contains(strtolower($notif->title), 'report') || str_contains($notif->link ?? '', 'report'))
                                            <button type="button" 
                                                @click="open = false; fetch('{{ route('notifications.read-and-redirect', $notif->id) }}').catch(()=>{}); window.dispatchEvent(new CustomEvent('open-seller-report', { detail: { id: '{{ Str::after($notif->link ?? '', 'view_report=') ?: 'latest' }}', reason: '{{ addslashes($notif->title) }}', message: '{{ addslashes($notif->message) }}' } }))"
                                                class="w-full text-left flex items-start gap-3 p-4 hover:bg-amber-50/50 transition-all border-b border-gray-50 last:border-0 cursor-pointer"
                                            >
                                                <div class="w-2 h-2 mt-1.5 rounded-full {{ $notif->isRead ? 'bg-gray-200' : 'bg-red-500' }} shrink-0"></div>
                                                <div class="space-y-0.5 flex-1 min-w-0">
                                                    <div class="text-[11px] font-bold text-black text-left flex items-center justify-between">
                                                        <span>{{ $notif->title }}</span>
                                                        <span class="text-[8px] font-black text-[#C0422A] uppercase tracking-wider">View Notice</span>
                                                    </div>
                                                    <div class="text-[10px] text-gray-500 text-left leading-relaxed line-clamp-2">{{ $notif->message }}</div>
                                                    <div class="text-[8px] text-gray-400 text-left">{{ $notif->createdAt ? \Carbon\Carbon::parse($notif->createdAt)->diffForHumans() : '' }}</div>
                                                </div>
                                            </button>
                                        @else
                                            <a href="{{ route('notifications.read-and-redirect', $notif->id) }}" class="flex items-start gap-3 p-4 hover:bg-gray-50 transition-all border-b border-gray-50 last:border-0">
                                                <div class="w-2 h-2 mt-1.5 rounded-full {{ $notif->isRead ? 'bg-gray-200' : 'bg-red-500' }} shrink-0"></div>
                                                <div class="space-y-0.5">
                                                    <div class="text-[11px] font-bold text-black text-left">{{ $notif->title }}</div>
                                                    <div class="text-[10px] text-gray-500 text-left leading-relaxed">{{ Str::limit($notif->message, 80) }}</div>
                                                    <div class="text-[8px] text-gray-400 text-left">{{ $notif->createdAt ? \Carbon\Carbon::parse($notif->createdAt)->diffForHumans() : '' }}</div>
                                                </div>
                                            </a>
                                        @endif
                                    @empty
                                        <div class="p-8 text-center">
                                            <div class="text-xs text-gray-400 italic">No seller notifications yet</div>
                                        </div>
                                    @endforelse
                                </div>
                                <a href="{{ route('seller.notifications.index') }}" class="block w-full py-3 text-center text-[10px] font-bold uppercase tracking-widest text-gray-400 hover:text-black hover:bg-gray-50 border-t border-gray-100 transition-all">View All</a>
                            </div>
                        </div>

                        <a href="{{ route('seller.profile') }}" class="flex items-center gap-3 hover:opacity-80 transition-all cursor-pointer" title="Go to Profile">
                            <div class="text-right hidden sm:block">
                                <div class="text-sm font-bold text-(--charcoal) flex items-center gap-1.5 justify-end">
                                    {{ Auth::user()->name }}
                                    @if(Auth::user()->isPremiumActive())
                                        <span class="text-xs text-yellow-500" title="Premium Seller">👑</span>
                                    @endif
                                </div>
                                <div class="text-[10px] font-bold uppercase tracking-widest text-(--muted)">
                                    @if(Auth::user()->isPremiumActive())
                                        <span class="text-yellow-600">Premium Seller</span>
                                    @else
                                        Verified Seller
                                    @endif
                                </div>
                            </div>
                            <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-(--charcoal) text-white flex items-center justify-center font-bold shadow-md overflow-hidden shrink-0 border-2 border-white">
                                @if(Auth::user()->profile_photo_url)
                                    <img src="{{ Auth::user()->profile_photo_url }}" class="w-full h-full object-cover" onerror="this.style.display='none'">
                                @else
                                    {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                                @endif
                            </div>
                        </a>
                    </div>
                </div>
            </header>

            <!-- Page Content -->
            <main class="flex-1 overflow-y-auto p-3 sm:p-6 lg:p-10 pb-28">
                <div class="max-w-300 mx-auto">
                    {{-- Flash Messages (Floating Toasts) --}}
                    @if(session('success') || session('error'))
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

                        <button @click="show = false" class="text-gray-300 hover:text-gray-500 transition-colors shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>
                    </div>
                    @endif
                    @yield('content')
                </div>
            </main>

            <!-- Mobile Bottom Nav: Home | Orders | ➕ Add | Messages | Profile -->
            <nav class="lg:hidden fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200/80 h-16 flex items-center justify-around px-2 z-40 shadow-lg">
                <!-- Home -->
                <a href="{{ route('seller.dashboard') }}" class="flex-1 flex flex-col items-center justify-center gap-0.5 py-1 {{ request()->is('seller/dashboard') || request()->is('seller') ? 'text-[#C0422A]' : 'text-gray-500 hover:text-gray-700' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 12l9-9 9 9M5 10v10a1 1 0 001 1h3a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1h3a1 1 0 001-1V10"></path>
                    </svg>
                    <span class="text-[10px] font-semibold">Home</span>
                </a>

                <!-- Orders -->
                <a href="{{ route('seller.orders') }}" class="flex-1 flex flex-col items-center justify-center gap-0.5 py-1 relative {{ request()->is('seller/orders*') ? 'text-[#C0422A]' : 'text-gray-500 hover:text-gray-700' }}">
                    <div class="relative">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                        </svg>
                        @if(($pendingOrdersCount ?? 0) > 0)
                            <span class="absolute -top-1 -right-2 min-w-3.5 h-3.5 px-1 bg-[#C0420A] text-white text-[8px] font-black rounded-full flex items-center justify-center border-2 border-white">{{ $pendingOrdersCount }}</span>
                        @endif
                    </div>
                    <span class="text-[10px] font-semibold">Orders</span>
                </a>

                <!-- Center Floating Plus Button (Add New Product) -->
                <a href="{{ route('seller.products.create') }}" class="flex flex-col items-center justify-center -mt-5 group shrink-0 px-2" title="Add New Product">
                    <div class="w-12 h-12 rounded-full bg-[#221F1C] text-white flex items-center justify-center shadow-lg group-hover:bg-[#C0422A] transition-all">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M12 4.5v15m7.5-7.5h-15"></path>
                        </svg>
                    </div>
                    <span class="text-[9px] font-semibold text-gray-400 mt-0.5">Add</span>
                </a>

                <!-- Messages -->
                <a href="{{ route('seller.messages') }}" class="flex-1 flex flex-col items-center justify-center gap-0.5 py-1 relative {{ request()->is('seller/messages*') ? 'text-[#C0422A]' : 'text-gray-500 hover:text-gray-700' }}">
                    <div class="relative">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                        </svg>
                        @if(($unreadMsgCount ?? 0) > 0)
                            <span class="absolute -top-1 -right-2 min-w-3.5 h-3.5 px-1 bg-[#C0420A] text-white text-[8px] font-black rounded-full flex items-center justify-center border-2 border-white">{{ $unreadMsgCount }}</span>
                        @endif
                    </div>
                    <span class="text-[10px] font-semibold">Messages</span>
                </a>

                <!-- Profile -->
                <a href="{{ route('seller.profile') }}" class="flex-1 flex flex-col items-center justify-center gap-0.5 py-1 {{ request()->is('seller/profile*') ? 'text-[#C0422A]' : 'text-gray-500 hover:text-gray-700' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                    <span class="text-[10px] font-semibold">Profile</span>
                </a>
            </nav>
        </div>
    </div>
    
    {{-- Live Seller Notification Popup Toast --}}
    <div 
        x-data="{
            popupNotif: null,
            dismissedIds: JSON.parse(sessionStorage.getItem('seen_seller_notif_ids') || '[]'),
            checkNotifications() {
                fetch('/api/notifications?role=seller')
                    .then(r => r.json())
                    .then(data => {
                        if (Array.isArray(data)) {
                            const unread = data.filter(n => !n.isRead && !this.dismissedIds.includes(n.id));
                            if (unread.length > 0) {
                                const latest = unread[0];
                                if (!this.popupNotif || this.popupNotif.id !== latest.id) {
                                    this.popupNotif = latest;
                                    setTimeout(() => { 
                                        if (this.popupNotif && this.popupNotif.id === latest.id) {
                                            this.dismiss(); 
                                        }
                                    }, 8000);
                                }
                            }
                        }
                    })
                    .catch(() => {});
            },
            dismiss() {
                if (this.popupNotif) {
                    this.dismissedIds.push(this.popupNotif.id);
                    sessionStorage.setItem('seen_seller_notif_ids', JSON.stringify(this.dismissedIds));
                    this.popupNotif = null;
                }
            },
            init() {
                this.checkNotifications();
                setInterval(() => this.checkNotifications(), 12000);
            }
        }"
        x-show="popupNotif"
        x-cloak
        x-transition:enter="transition ease-out duration-300 transform"
        x-transition:enter-start="opacity-0 -translate-y-4 sm:translate-x-4 sm:-translate-y-0"
        x-transition:enter-end="opacity-100 translate-y-0 sm:translate-x-0"
        x-transition:leave="transition ease-in duration-200 transform"
        x-transition:leave-start="opacity-100 translate-y-0 sm:translate-x-0"
        x-transition:leave-end="opacity-0 -translate-y-4 sm:translate-x-4 sm:-translate-y-0"
        class="fixed top-4 right-4 sm:top-6 sm:right-6 z-9999 max-w-sm w-[calc(100%-2rem)] bg-white/95 backdrop-blur-md rounded-2xl shadow-2xl border border-gray-100 p-4"
        style="display: none;"
    >
        <div class="flex items-start gap-3">
            <div class="w-9 h-9 rounded-xl bg-[#C0422A]/10 text-[#C0422A] flex items-center justify-center shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
            </div>
            <div class="flex-1 min-w-0">
                <div class="flex items-center justify-between gap-1">
                    <h4 class="text-xs font-black text-black truncate uppercase" x-text="popupNotif?.title || 'Notification'"></h4>
                    <button @click="dismiss()" class="text-gray-400 hover:text-black transition-colors shrink-0">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>
                <p class="text-xs text-gray-600 font-medium mt-0.5 line-clamp-2 leading-relaxed" x-text="popupNotif?.message"></p>
                <div class="mt-2.5 flex items-center justify-between">
                    <span class="text-[9px] font-bold text-gray-400 uppercase tracking-wider">Just now</span>
                    <a :href="'/notifications/' + (popupNotif?.id) + '/read'" class="px-3 py-1 bg-[#C0422A] hover:bg-[#a63721] text-white text-[10px] font-bold rounded-lg transition-all shadow-xs inline-flex items-center gap-1">
                        <span>View</span>
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <x-seller-report-modal />
    <x-confirmation-modal />
    <x-modal-scroll-lock />
    @stack('scripts')
</body>
</html>

